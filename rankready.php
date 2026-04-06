<?php
/**
 * Plugin Name:       RankReady – LLM SEO, EEAT & AI Optimization
 * Plugin URI:        https://posimyth.com/rankready/
 * Description:       AI summaries, Article JSON-LD schema with speakable, LLMs.txt generator, Markdown endpoints for LLM crawlers, bulk author changer. Built for LLM SEO, EEAT, and AI Overviews.
 * Version:           2.4.5
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * Author:            POSIMYTH Innovations
 * Author URI:        https://posimyth.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       rankready
 * Domain Path:       /languages
 */

defined( 'ABSPATH' ) || exit;

// ── Constants (guarded to prevent conflicts) ─────────────────────────────────
if ( ! defined( 'RR_VERSION' ) ) {
	define( 'RR_VERSION',  '2.4.5' );
	define( 'RR_FILE',     __FILE__ );
	define( 'RR_DIR',      plugin_dir_path( __FILE__ ) );
	define( 'RR_URL',      plugin_dir_url( __FILE__ ) );
	define( 'RR_BASENAME', plugin_basename( __FILE__ ) );

	// Option keys — AI Summary.
	define( 'RR_OPT_KEY',              'rr_openai_api_key' );
	define( 'RR_OPT_MODEL',            'rr_openai_model' );
	define( 'RR_OPT_POST_TYPES',       'rr_post_types' );
	define( 'RR_OPT_LABEL',            'rr_default_label' );
	define( 'RR_OPT_SHOW_LABEL',       'rr_default_show_label' );
	define( 'RR_OPT_HEADING_TAG',      'rr_default_heading_tag' );
	define( 'RR_OPT_AUTO_DISPLAY',     'rr_auto_display' );
	define( 'RR_OPT_DISPLAY_POSITION', 'rr_display_position' );
	define( 'RR_OPT_CUSTOM_PROMPT',    'rr_custom_prompt' );

	// Option keys — LLMs.txt.
	define( 'RR_OPT_LLMS_ENABLE',       'rr_llms_enable' );
	define( 'RR_OPT_LLMS_SITE_NAME',    'rr_llms_site_name' );
	define( 'RR_OPT_LLMS_SUMMARY',      'rr_llms_summary' );
	define( 'RR_OPT_LLMS_ABOUT',        'rr_llms_about' );
	define( 'RR_OPT_LLMS_POST_TYPES',   'rr_llms_post_types' );
	define( 'RR_OPT_LLMS_MAX_POSTS',    'rr_llms_max_posts' );
	define( 'RR_OPT_LLMS_CACHE_TTL',    'rr_llms_cache_ttl' );
	define( 'RR_OPT_LLMS_FULL_ENABLE',  'rr_llms_full_enable' );

	// Option keys — LLMs.txt taxonomy controls.
	define( 'RR_OPT_LLMS_EXCLUDE_CATS',    'rr_llms_exclude_cats' );
	define( 'RR_OPT_LLMS_EXCLUDE_TAGS',    'rr_llms_exclude_tags' );
	define( 'RR_OPT_LLMS_SHOW_CATEGORIES', 'rr_llms_show_categories' );

	// Option keys — LLM Crawler robots.txt controls.
	define( 'RR_OPT_ROBOTS_ENABLE',   'rr_robots_enable' );
	define( 'RR_OPT_ROBOTS_CRAWLERS', 'rr_robots_crawlers' );

	// Option keys — Markdown.
	define( 'RR_OPT_MD_ENABLE',         'rr_md_enable' );
	define( 'RR_OPT_MD_POST_TYPES',     'rr_md_post_types' );
	define( 'RR_OPT_MD_INCLUDE_META',   'rr_md_include_meta' );

	// Meta keys.
	define( 'RR_META_SUMMARY',   '_rr_summary' );
	define( 'RR_META_HASH',      '_rr_content_hash' );
	define( 'RR_META_GENERATED', '_rr_last_generated' );
	define( 'RR_META_DISABLE',   '_rr_disable_summary' );

	// Cron.
	define( 'RR_CRON_HOOK', 'rr_async_generate' );

	// Bulk state — summary.
	define( 'RR_BULK_QUEUE',   'rr_bulk_queue' );
	define( 'RR_BULK_DONE',    'rr_bulk_done' );
	define( 'RR_BULK_TOTAL',   'rr_bulk_total' );
	define( 'RR_BULK_RUNNING', 'rr_bulk_running' );

	// Bulk state — author.
	define( 'RR_BAC_QUEUE',   'rr_bac_queue' );
	define( 'RR_BAC_TOTAL',   'rr_bac_total' );
	define( 'RR_BAC_DONE',    'rr_bac_done' );
	define( 'RR_BAC_RUNNING', 'rr_bac_running' );
	define( 'RR_BAC_TO',      'rr_bac_to_author' );

	// Transient keys.
	define( 'RR_LLMS_CACHE_KEY',      'rr_llms_txt_cache' );
	define( 'RR_LLMS_FULL_CACHE_KEY', 'rr_llms_full_txt_cache' );
}

// ── Autoloader ────────────────────────────────────────────────────────────────
spl_autoload_register( function ( string $class ): void {
	if ( 0 !== strpos( $class, 'RR_' ) ) {
		return;
	}
	$file = RR_DIR . 'includes/class-' . strtolower( str_replace( '_', '-', $class ) ) . '.php';
	if ( file_exists( $file ) ) {
		require_once $file;
	}
} );

// ── Bootstrap ─────────────────────────────────────────────────────────────────
add_action( 'plugins_loaded', function (): void {
	load_plugin_textdomain( 'rankready', false, dirname( RR_BASENAME ) . '/languages' );

	if ( version_compare( get_bloginfo( 'version' ), '6.2', '<' ) ) {
		add_action( 'admin_notices', function (): void {
			echo '<div class="notice notice-error"><p>'
				. esc_html__( 'RankReady requires WordPress 6.2 or higher.', 'rankready' )
				. '</p></div>';
		} );
		return;
	}

	// Auto-flush rewrite rules after plugin update (activation hook doesn't fire on updates).
	$stored_version = get_option( 'rr_installed_version', '' );
	if ( $stored_version !== RR_VERSION ) {
		update_option( 'rr_installed_version', RR_VERSION );
		RR_Llms_Txt::add_rewrite_rules();
		RR_Markdown::add_rewrite_rules();
		flush_rewrite_rules();
		RR_Llms_Txt::sync_physical_robots_txt();
	}

	RR_Admin::init();
	RR_Generator::init();
	RR_Block::init();
	RR_Rest::init();
	RR_Llms_Txt::init();
	RR_Markdown::init();

	if ( did_action( 'elementor/loaded' ) ) {
		add_action( 'elementor/widgets/register', function ( $widgets_manager ): void {
			require_once RR_DIR . 'includes/class-rr-elementor.php';
			$widgets_manager->register( new RR_Elementor_Widget() );
		} );

		add_action( 'elementor/frontend/after_enqueue_styles', function (): void {
			wp_enqueue_style( 'rankready-style', RR_URL . 'assets/style.css', array(), RR_VERSION );
		} );
	}
} );

// ── Activation / Deactivation ─────────────────────────────────────────────────
register_activation_hook( RR_FILE, function (): void {
	if ( false === get_option( RR_OPT_POST_TYPES ) ) {
		update_option( RR_OPT_POST_TYPES, array( 'post' ) );
	}
	if ( false === get_option( RR_OPT_LABEL ) ) {
		update_option( RR_OPT_LABEL, 'Key Takeaways' );
	}
	if ( false === get_option( RR_OPT_SHOW_LABEL ) ) {
		update_option( RR_OPT_SHOW_LABEL, true );
	}
	if ( false === get_option( RR_OPT_HEADING_TAG ) ) {
		update_option( RR_OPT_HEADING_TAG, 'h4' );
	}
	if ( false === get_option( RR_OPT_LLMS_ENABLE ) ) {
		update_option( RR_OPT_LLMS_ENABLE, 'off' );
	}
	if ( false === get_option( RR_OPT_MD_ENABLE ) ) {
		update_option( RR_OPT_MD_ENABLE, 'off' );
	}
	if ( false === get_option( RR_OPT_ROBOTS_ENABLE ) ) {
		update_option( RR_OPT_ROBOTS_ENABLE, 'on' );
	}
	if ( false === get_option( RR_OPT_ROBOTS_CRAWLERS ) ) {
		update_option( RR_OPT_ROBOTS_CRAWLERS, array_keys( RR_Admin::get_llm_crawlers() ) );
	}

	// Register rewrite rules before flushing so they get written.
	RR_Llms_Txt::add_rewrite_rules();
	RR_Markdown::add_rewrite_rules();
	flush_rewrite_rules();

	// Sync to physical robots.txt if one exists.
	RR_Llms_Txt::sync_physical_robots_txt();
} );

register_deactivation_hook( RR_FILE, function (): void {
	$timestamp = wp_next_scheduled( RR_CRON_HOOK );
	if ( $timestamp ) {
		wp_unschedule_event( $timestamp, RR_CRON_HOOK );
	}
	update_option( RR_BULK_RUNNING, false );
	update_option( RR_BAC_RUNNING, false );
	delete_transient( RR_LLMS_CACHE_KEY );
	delete_transient( RR_LLMS_FULL_CACHE_KEY );

	// Clean up RankReady block from physical robots.txt on deactivation.
	$robots_file = ABSPATH . 'robots.txt';
	if ( file_exists( $robots_file ) && is_writable( $robots_file ) ) {
		$contents = file_get_contents( $robots_file );
		if ( false !== $contents && false !== strpos( $contents, 'RankReady' ) ) {
			$contents = preg_replace( '/\n?#[^\n]*LLM[^\n]*RankReady[^\n]*\n.*?(?=\n#[^-]|\n?$)/s', '', $contents );
			$contents = rtrim( $contents ) . "\n";
			file_put_contents( $robots_file, $contents );
		}
	}

	flush_rewrite_rules();
} );
