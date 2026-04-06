<?php
/**
 * Summary generator — triggers on publish/update, async via shutdown + cron fallback.
 *
 * @package RankReady
 */

defined( 'ABSPATH' ) || exit;

class RR_Generator {

	/** Minimum seconds between API calls for the same post. */
	private const MIN_INTERVAL = 30;

	public static function init(): void {
		add_action( 'wp_after_insert_post', array( self::class, 'schedule_generation' ), 10, 4 );
		add_action( RR_CRON_HOOK, array( self::class, 'run_generation' ) );
	}

	// ── Trigger ───────────────────────────────────────────────────────────────

	public static function schedule_generation( $post_id, $post, $update, $post_before ): void {
		$post_id = (int) $post_id;

		if ( 'publish' !== $post->post_status ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		// REST API autosave check (Gutenberg)
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			$route = isset( $GLOBALS['wp']->query_vars['rest_route'] ) ? $GLOBALS['wp']->query_vars['rest_route'] : '';
			if ( false !== strpos( $route, '/autosaves' ) ) {
				return;
			}
		}

		// Check post type is enabled
		$enabled_types = (array) get_option( RR_OPT_POST_TYPES, array( 'post' ) );
		if ( ! in_array( $post->post_type, $enabled_types, true ) ) {
			return;
		}

		// Per-post disable
		if ( get_post_meta( $post_id, RR_META_DISABLE, true ) ) {
			return;
		}

		// No API key
		if ( empty( get_option( RR_OPT_KEY ) ) ) {
			return;
		}

		// Hash check: only call API if content changed
		$content  = self::get_content_string( $post );
		$new_hash = md5( $content );
		$old_hash = (string) get_post_meta( $post_id, RR_META_HASH, true );

		if ( $new_hash === $old_hash ) {
			return;
		}

		update_post_meta( $post_id, RR_META_HASH, $new_hash );

		// Strategy 1: fastcgi_finish_request (RunCloud/nginx/PHP-FPM)
		register_shutdown_function( function () use ( $post_id, $new_hash ) {
			if ( (string) get_post_meta( $post_id, RR_META_HASH, true ) !== $new_hash ) {
				return;
			}
			if ( function_exists( 'fastcgi_finish_request' ) ) {
				fastcgi_finish_request();
			}
			RR_Generator::run_generation_direct( $post_id );
		} );

		// Strategy 2: WP-Cron fallback
		if ( ! defined( 'DISABLE_WP_CRON' ) || ! DISABLE_WP_CRON ) {
			wp_clear_scheduled_hook( RR_CRON_HOOK, array( $post_id ) );
			wp_schedule_single_event( time() + 10, RR_CRON_HOOK, array( $post_id ) );
			spawn_cron();
		}
	}

	// ── Direct runner (fastcgi path) ──────────────────────────────────────────

	public static function run_generation_direct( $post_id ): void {
		$post_id = (int) $post_id;
		$last    = (int) get_post_meta( $post_id, RR_META_GENERATED, true );
		if ( $last && ( time() - $last ) < self::MIN_INTERVAL ) {
			return;
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return;
		}

		$api_key = (string) get_option( RR_OPT_KEY, '' );
		if ( empty( $api_key ) ) {
			return;
		}

		$content = self::get_content_string( $post );
		$result  = self::call_openai( $content, $post, $api_key );

		if ( $result ) {
			update_post_meta( $post_id, RR_META_SUMMARY,   $result );
			update_post_meta( $post_id, RR_META_GENERATED, time() );
		}
	}

	// ── WP-Cron runner (fallback) ─────────────────────────────────────────────

	public static function run_generation( $post_id ): void {
		$post_id = (int) $post_id;
		$post    = get_post( $post_id );
		if ( ! $post || 'publish' !== $post->post_status ) {
			return;
		}

		// Per-post disable check
		if ( get_post_meta( $post_id, RR_META_DISABLE, true ) ) {
			return;
		}

		$api_key = (string) get_option( RR_OPT_KEY, '' );
		if ( empty( $api_key ) ) {
			return;
		}

		$last = (int) get_post_meta( $post_id, RR_META_GENERATED, true );
		if ( $last && ( time() - $last ) < self::MIN_INTERVAL ) {
			return;
		}

		$content = self::get_content_string( $post );
		$result  = self::call_openai( $content, $post, $api_key );

		if ( $result ) {
			update_post_meta( $post_id, RR_META_SUMMARY,   $result );
			update_post_meta( $post_id, RR_META_GENERATED, time() );
		}
	}

	// ── Force generation (REST) ───────────────────────────────────────────────

	public static function force_generate( $post_id ) {
		$post_id = (int) $post_id;
		$post    = get_post( $post_id );
		if ( ! $post ) {
			return false;
		}

		$api_key = (string) get_option( RR_OPT_KEY, '' );
		if ( empty( $api_key ) ) {
			return false;
		}

		$content = self::get_content_string( $post );
		$result  = self::call_openai( $content, $post, $api_key );

		if ( $result ) {
			update_post_meta( $post_id, RR_META_SUMMARY,   $result );
			update_post_meta( $post_id, RR_META_HASH,      md5( $content ) );
			update_post_meta( $post_id, RR_META_GENERATED, time() );
		}

		return $result;
	}

	// ── OpenAI call ───────────────────────────────────────────────────────────

	public static function call_openai( $content, $post, $api_key ) {
		$model = (string) get_option( RR_OPT_MODEL, 'gpt-4o-mini' );

		// Use regex-based word count — locale-safe for CJK, Arabic, etc.
		$word_count   = preg_match_all( '/\S+/', $content );
		$word_count   = false !== $word_count ? $word_count : 0;
		$bullet_count = 3;
		if ( $word_count >= 1500 ) {
			$bullet_count = 5;
		} elseif ( $word_count >= 600 ) {
			$bullet_count = 4;
		}

		$content = mb_substr( $content, 0, 12000 );

		$system_prompt = 'You are a technical blog editor. You write precise, factual bullet-point summaries for blog posts. Your bullets are read by humans and indexed by AI search engines. Every bullet must contain specific named entities from the content.';

		// Append custom prompt if set
		$custom_prompt = (string) get_option( RR_OPT_CUSTOM_PROMPT, '' );
		if ( ! empty( $custom_prompt ) ) {
			$system_prompt .= "\n\nAdditional instructions: " . $custom_prompt;
		}

		$user_prompt = sprintf(
			'Summarize the blog post below as exactly %d bullet points.

Rules (follow strictly):
1. Each bullet = one complete, specific insight from the post.
2. Include named entities: product names, feature names, version numbers, tool names.
3. Each bullet starts with a key noun or strong action verb (not "You can", "This", "Learn", "Find out").
4. No filler: no "In this post", "Comprehensive guide", "Dive into".
5. Write in present tense, third-person perspective.
6. Bullets should be scannable in under 3 seconds each.
7. Return ONLY valid JSON: {"bullets":["Bullet 1.","Bullet 2.","Bullet 3."]}

Blog Post:
%s',
			$bullet_count,
			$content
		);

		$response = wp_remote_post( 'https://api.openai.com/v1/chat/completions', array(
			'timeout'    => 25,
			'user-agent' => 'RankReady/' . RR_VERSION . '; WordPress/' . get_bloginfo( 'version' ),
			'headers'    => array(
				'Authorization' => 'Bearer ' . $api_key,
				'Content-Type'  => 'application/json',
			),
			'body' => wp_json_encode( array(
				'model'             => $model,
				'messages'          => array(
					array( 'role' => 'system', 'content' => $system_prompt ),
					array( 'role' => 'user',   'content' => $user_prompt ),
				),
				'max_tokens'        => 500,
				'temperature'       => 0.2,
				'frequency_penalty' => 0.3,
				'response_format'   => array( 'type' => 'json_object' ),
			) ),
		) );

		if ( is_wp_error( $response ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( '[RankReady] OpenAI error: ' . $response->get_error_message() );
			}
			return false;
		}

		$http_code = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 !== $http_code ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( '[RankReady] OpenAI HTTP ' . $http_code . ': ' . wp_remote_retrieve_body( $response ) );
			}
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		$raw  = isset( $body['choices'][0]['message']['content'] ) ? trim( $body['choices'][0]['message']['content'] ) : '';

		if ( empty( $raw ) ) {
			return false;
		}

		$decoded = json_decode( $raw, true );
		if ( ! is_array( $decoded ) || empty( $decoded['bullets'] ) || ! is_array( $decoded['bullets'] ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( '[RankReady] Unexpected JSON structure: ' . $raw );
			}
			return false;
		}

		$decoded['bullets'] = array_values(
			array_filter(
				array_map( 'sanitize_text_field', $decoded['bullets'] )
			)
		);

		return wp_json_encode( $decoded );
	}

	// ── Connection test ───────────────────────────────────────────────────────

	public static function test_api_connection() {
		$api_key = (string) get_option( RR_OPT_KEY, '' );
		if ( empty( $api_key ) ) {
			return __( 'No API key configured.', 'rankready' );
		}

		$response = wp_remote_post( 'https://api.openai.com/v1/chat/completions', array(
			'timeout' => 10,
			'headers' => array(
				'Authorization' => 'Bearer ' . $api_key,
				'Content-Type'  => 'application/json',
			),
			'body' => wp_json_encode( array(
				'model'      => 'gpt-4o-mini',
				'messages'   => array( array( 'role' => 'user', 'content' => 'Reply with OK only.' ) ),
				'max_tokens' => 5,
			) ),
		) );

		if ( is_wp_error( $response ) ) {
			return $response->get_error_message();
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 === $code ) {
			return true;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		return isset( $body['error']['message'] ) ? $body['error']['message'] : 'HTTP ' . $code;
	}

	// ── Helpers ───────────────────────────────────────────────────────────────

	public static function get_content_string( $post ): string {
		$body = wp_strip_all_tags( do_shortcode( $post->post_content ) );
		return $post->post_title . "\n\n" . $body;
	}

	public static function decode_summary( $raw ): array {
		if ( empty( $raw ) ) {
			return array( 'type' => 'empty', 'data' => array() );
		}

		$decoded = json_decode( $raw, true );
		if ( is_array( $decoded ) && ! empty( $decoded['bullets'] ) ) {
			return array( 'type' => 'bullets', 'data' => $decoded['bullets'] );
		}

		return array( 'type' => 'text', 'data' => $raw );
	}
}
