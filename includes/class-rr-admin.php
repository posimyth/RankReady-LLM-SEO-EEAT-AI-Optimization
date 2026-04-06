<?php
/**
 * Admin settings page — tabbed UI using core WordPress styles.
 *
 * Tabs: Settings | LLM Optimization | Tools | Info
 *
 * @package RankReady
 */

defined( 'ABSPATH' ) || exit;

class RR_Admin {

	private const SETTINGS_GROUP   = 'rr_settings_group';
	private const LLMS_GROUP       = 'rr_llms_group';
	private const FAQ_GROUP        = 'rr_faq_group';
	private const MENU_SLUG        = 'rankready';
	private const NONCE_ACTION     = 'rr_test_connection';
	private const NONCE_FIELD      = 'rr_test_nonce';

	public static function init(): void {
		add_action( 'admin_menu',            array( self::class, 'register_menu' ) );
		add_action( 'admin_init',            array( self::class, 'register_settings' ) );
		add_action( 'admin_notices',         array( self::class, 'connection_notice' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_admin_assets' ) );
		add_filter( 'plugin_action_links_' . RR_BASENAME, array( self::class, 'action_links' ) );
		add_action( 'add_meta_boxes',        array( self::class, 'register_meta_box' ) );
		add_action( 'save_post',             array( self::class, 'save_meta_box' ) );
	}

	// ── Menu ──────────────────────────────────────────────────────────────────

	public static function register_menu(): void {
		add_menu_page(
			__( 'RankReady', 'rankready' ),
			__( 'RankReady', 'rankready' ),
			'manage_options',
			self::MENU_SLUG,
			array( self::class, 'render_page' ),
			'dashicons-chart-area',
			81
		);
	}

	// ── Assets ────────────────────────────────────────────────────────────────

	public static function enqueue_admin_assets( $hook ): void {
		if ( 'toplevel_page_' . self::MENU_SLUG !== $hook ) {
			return;
		}
		wp_enqueue_style( 'rr-admin', RR_URL . 'assets/admin.css', array(), RR_VERSION );
		wp_enqueue_script( 'rr-admin', RR_URL . 'assets/admin.js', array(), RR_VERSION, true );
		wp_localize_script( 'rr-admin', 'rrAdmin', array(
			'nonce'   => wp_create_nonce( 'wp_rest' ),
			'apiBase' => rest_url( 'rankready/v1' ),
		) );
	}

	// ── Settings API ──────────────────────────────────────────────────────────

	public static function register_settings(): void {

		// ═══ Settings Tab ═════════════════════════════════════════════════════

		register_setting( self::SETTINGS_GROUP, RR_OPT_KEY, array(
			'type'              => 'string',
			'sanitize_callback' => array( self::class, 'sanitize_api_key' ),
			'default'           => '',
		) );

		register_setting( self::SETTINGS_GROUP, RR_OPT_MODEL, array(
			'type'              => 'string',
			'sanitize_callback' => array( self::class, 'sanitize_model' ),
			'default'           => 'gpt-4o-mini',
		) );

		register_setting( self::SETTINGS_GROUP, RR_OPT_POST_TYPES, array(
			'type'              => 'array',
			'sanitize_callback' => array( self::class, 'sanitize_post_types' ),
			'default'           => array( 'post' ),
		) );

		register_setting( self::SETTINGS_GROUP, RR_OPT_CUSTOM_PROMPT, array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_textarea_field',
			'default'           => '',
		) );

		register_setting( self::SETTINGS_GROUP, RR_OPT_AUTO_DISPLAY, array(
			'type'              => 'string',
			'sanitize_callback' => array( self::class, 'sanitize_auto_display' ),
			'default'           => 'off',
		) );

		register_setting( self::SETTINGS_GROUP, RR_OPT_DISPLAY_POSITION, array(
			'type'              => 'string',
			'sanitize_callback' => array( self::class, 'sanitize_display_position' ),
			'default'           => 'before',
		) );

		register_setting( self::SETTINGS_GROUP, RR_OPT_LABEL, array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'Key Takeaways',
		) );

		register_setting( self::SETTINGS_GROUP, RR_OPT_SHOW_LABEL, array(
			'type'              => 'string',
			'sanitize_callback' => array( self::class, 'sanitize_checkbox_field' ),
			'default'           => '1',
		) );

		register_setting( self::SETTINGS_GROUP, RR_OPT_HEADING_TAG, array(
			'type'              => 'string',
			'sanitize_callback' => array( self::class, 'sanitize_heading_tag' ),
			'default'           => 'h4',
		) );

		// ═══ LLM Optimization Tab ═════════════════════════════════════════════

		register_setting( self::LLMS_GROUP, RR_OPT_LLMS_ENABLE, array(
			'type'              => 'string',
			'sanitize_callback' => array( self::class, 'sanitize_on_off' ),
			'default'           => 'off',
		) );

		register_setting( self::LLMS_GROUP, RR_OPT_LLMS_SITE_NAME, array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		) );

		register_setting( self::LLMS_GROUP, RR_OPT_LLMS_SUMMARY, array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_textarea_field',
			'default'           => '',
		) );

		register_setting( self::LLMS_GROUP, RR_OPT_LLMS_ABOUT, array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_textarea_field',
			'default'           => '',
		) );

		register_setting( self::LLMS_GROUP, RR_OPT_LLMS_POST_TYPES, array(
			'type'              => 'array',
			'sanitize_callback' => array( self::class, 'sanitize_post_types' ),
			'default'           => array( 'post', 'page' ),
		) );

		register_setting( self::LLMS_GROUP, RR_OPT_LLMS_MAX_POSTS, array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 100,
		) );

		register_setting( self::LLMS_GROUP, RR_OPT_LLMS_CACHE_TTL, array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 3600,
		) );

		register_setting( self::LLMS_GROUP, RR_OPT_LLMS_FULL_ENABLE, array(
			'type'              => 'string',
			'sanitize_callback' => array( self::class, 'sanitize_on_off' ),
			'default'           => 'off',
		) );

		register_setting( self::LLMS_GROUP, RR_OPT_LLMS_EXCLUDE_CATS, array(
			'type'              => 'array',
			'sanitize_callback' => array( self::class, 'sanitize_term_ids' ),
			'default'           => array(),
		) );

		register_setting( self::LLMS_GROUP, RR_OPT_LLMS_EXCLUDE_TAGS, array(
			'type'              => 'array',
			'sanitize_callback' => array( self::class, 'sanitize_term_ids' ),
			'default'           => array(),
		) );

		register_setting( self::LLMS_GROUP, RR_OPT_LLMS_SHOW_CATEGORIES, array(
			'type'              => 'string',
			'sanitize_callback' => array( self::class, 'sanitize_on_off' ),
			'default'           => 'on',
		) );

		register_setting( self::LLMS_GROUP, RR_OPT_ROBOTS_ENABLE, array(
			'type'              => 'string',
			'sanitize_callback' => array( self::class, 'sanitize_on_off' ),
			'default'           => 'on',
		) );

		register_setting( self::LLMS_GROUP, RR_OPT_ROBOTS_CRAWLERS, array(
			'type'              => 'array',
			'sanitize_callback' => array( self::class, 'sanitize_crawler_list' ),
			'default'           => array_keys( self::get_llm_crawlers() ),
		) );

		register_setting( self::LLMS_GROUP, RR_OPT_MD_ENABLE, array(
			'type'              => 'string',
			'sanitize_callback' => array( self::class, 'sanitize_on_off' ),
			'default'           => 'off',
		) );

		register_setting( self::LLMS_GROUP, RR_OPT_MD_POST_TYPES, array(
			'type'              => 'array',
			'sanitize_callback' => array( self::class, 'sanitize_post_types' ),
			'default'           => array( 'post', 'page' ),
		) );

		register_setting( self::LLMS_GROUP, RR_OPT_MD_INCLUDE_META, array(
			'type'              => 'string',
			'sanitize_callback' => array( self::class, 'sanitize_checkbox_field' ),
			'default'           => '1',
		) );

		// ═══ FAQ Tab ═════════════════════════════════════════════════════

		register_setting( self::FAQ_GROUP, RR_OPT_DFS_LOGIN, array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		) );

		register_setting( self::FAQ_GROUP, RR_OPT_DFS_PASSWORD, array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		) );

		register_setting( self::FAQ_GROUP, RR_OPT_FAQ_POST_TYPES, array(
			'type'              => 'array',
			'sanitize_callback' => array( self::class, 'sanitize_post_types' ),
			'default'           => array( 'post' ),
		) );

		register_setting( self::FAQ_GROUP, RR_OPT_FAQ_COUNT, array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 5,
		) );

		register_setting( self::FAQ_GROUP, RR_OPT_FAQ_BRAND_TERMS, array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_textarea_field',
			'default'           => '',
		) );

		register_setting( self::FAQ_GROUP, RR_OPT_FAQ_AUTO_DISPLAY, array(
			'type'              => 'string',
			'sanitize_callback' => array( self::class, 'sanitize_on_off' ),
			'default'           => 'off',
		) );

		register_setting( self::FAQ_GROUP, RR_OPT_FAQ_POSITION, array(
			'type'              => 'string',
			'sanitize_callback' => array( self::class, 'sanitize_display_position' ),
			'default'           => 'after',
		) );

		register_setting( self::FAQ_GROUP, RR_OPT_FAQ_HEADING_TAG, array(
			'type'              => 'string',
			'sanitize_callback' => array( self::class, 'sanitize_heading_tag' ),
			'default'           => 'h3',
		) );

		register_setting( self::FAQ_GROUP, RR_OPT_FAQ_SHOW_REVIEWED, array(
			'type'              => 'string',
			'sanitize_callback' => array( self::class, 'sanitize_on_off' ),
			'default'           => 'on',
		) );
	}

	// ── Sanitize callbacks ────────────────────────────────────────────────────

	public static function sanitize_api_key( $value ): string {
		$value = sanitize_text_field( (string) $value );
		if ( false !== strpos( $value, '••••' ) ) {
			return (string) get_option( RR_OPT_KEY, '' );
		}
		if ( ! empty( $value ) && ! preg_match( '/^sk-[A-Za-z0-9\-_]{20,}$/', $value ) ) {
			add_settings_error( RR_OPT_KEY, 'rr_invalid_key',
				__( 'The API key format looks incorrect. It should start with sk-', 'rankready' ), 'error' );
			return (string) get_option( RR_OPT_KEY, '' );
		}
		return $value;
	}

	public static function sanitize_model( $value ): string {
		$allowed = array_keys( self::get_allowed_models() );
		$value   = sanitize_text_field( (string) $value );
		return in_array( $value, $allowed, true ) ? $value : 'gpt-4o-mini';
	}

	public static function sanitize_post_types( $value ): array {
		if ( ! is_array( $value ) ) {
			return array( 'post' );
		}
		$allowed = array_keys( self::get_allowed_post_types() );
		return array_values( array_intersect( array_map( 'sanitize_key', $value ), $allowed ) );
	}

	public static function sanitize_term_ids( $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}
		return array_values( array_map( 'absint', array_filter( $value ) ) );
	}

	public static function sanitize_checkbox_field( $value ): string {
		return ! empty( $value ) ? '1' : '0';
	}

	public static function sanitize_heading_tag( $value ): string {
		$allowed = array( 'h2', 'h3', 'h4', 'h5', 'h6', 'p' );
		$value   = sanitize_text_field( (string) $value );
		return in_array( $value, $allowed, true ) ? $value : 'h4';
	}

	public static function sanitize_auto_display( $value ): string {
		return in_array( $value, array( 'on', 'off' ), true ) ? $value : 'off';
	}

	public static function sanitize_display_position( $value ): string {
		return in_array( $value, array( 'before', 'after' ), true ) ? $value : 'before';
	}

	public static function sanitize_on_off( $value ): string {
		return in_array( $value, array( 'on', 'off' ), true ) ? $value : 'off';
	}

	public static function sanitize_crawler_list( $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}
		$allowed = array_keys( self::get_llm_crawlers() );
		return array_values( array_intersect( array_map( 'sanitize_text_field', $value ), $allowed ) );
	}

	/**
	 * Get the full list of known LLM/AI crawlers with metadata.
	 *
	 * @return array Associative array: user-agent => array( company, purpose ).
	 */
	public static function get_llm_crawlers(): array {
		return array(
			// OpenAI
			'GPTBot'              => array( 'OpenAI', 'ChatGPT search & AI training' ),
			'ChatGPT-User'        => array( 'OpenAI', 'ChatGPT browse mode (user-initiated)' ),
			'OAI-SearchBot'       => array( 'OpenAI', 'SearchGPT / ChatGPT search results' ),
			// Anthropic
			'ClaudeBot'           => array( 'Anthropic', 'Claude AI web retrieval' ),
			'Claude-Web'          => array( 'Anthropic', 'Claude AI (legacy identifier)' ),
			// Google
			'Google-Extended'     => array( 'Google', 'Gemini AI training (does NOT affect search ranking)' ),
			// Apple
			'Applebot-Extended'   => array( 'Apple', 'Apple Intelligence / Siri AI' ),
			// Microsoft
			'Bingbot'             => array( 'Microsoft', 'Bing Search + Copilot (shared UA)' ),
			// Perplexity
			'PerplexityBot'       => array( 'Perplexity', 'Perplexity AI answer engine' ),
			// Meta
			'Meta-ExternalAgent'  => array( 'Meta', 'Meta AI / Llama training' ),
			'FacebookBot'         => array( 'Meta', 'Facebook/Meta content crawling' ),
			// ByteDance
			'Bytespider'          => array( 'ByteDance', 'TikTok / ByteDance AI' ),
			// Amazon
			'Amazonbot'           => array( 'Amazon', 'Alexa AI / Amazon' ),
			// Cohere
			'cohere-ai'           => array( 'Cohere', 'Cohere AI RAG & enterprise' ),
			// Others
			'DuckAssistBot'       => array( 'DuckDuckGo', 'DuckDuckGo AI Assist' ),
			'YouBot'              => array( 'You.com', 'You.com AI search' ),
			'CCBot'               => array( 'Common Crawl', 'Open dataset used by many LLMs' ),
			'AI2Bot'              => array( 'Allen Institute', 'AI2 research crawler' ),
			'Diffbot'             => array( 'Diffbot', 'Diffbot AI extraction' ),
			'PhindBot'            => array( 'Phind', 'Phind AI search for developers' ),
		);
	}

	// ── Main render ───────────────────────────────────────────────────────────

	public static function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'rankready' ) );
		}

		$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'settings';
		$tabs       = array(
			'settings' => __( 'Settings', 'rankready' ),
			'llm'      => __( 'LLM Optimization', 'rankready' ),
			'faq'      => __( 'FAQ Generator', 'rankready' ),
			'tools'    => __( 'Tools', 'rankready' ),
			'info'     => __( 'Info', 'rankready' ),
		);

		if ( ! array_key_exists( $active_tab, $tabs ) ) {
			$active_tab = 'settings';
		}
		?>
		<div class="wrap rr-wrap">
			<div class="rr-header">
				<h1 class="rr-title">
					<span class="dashicons dashicons-chart-area rr-title-icon"></span>
					<?php esc_html_e( 'RankReady', 'rankready' ); ?>
					<span class="rr-version">v<?php echo esc_html( RR_VERSION ); ?></span>
				</h1>
				<p class="rr-subtitle"><?php esc_html_e( 'LLM SEO, EEAT & AI Optimization for WordPress', 'rankready' ); ?></p>
			</div>

			<nav class="nav-tab-wrapper rr-tabs">
				<?php foreach ( $tabs as $slug => $label ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::MENU_SLUG . '&tab=' . $slug ) ); ?>"
					   class="nav-tab <?php echo $active_tab === $slug ? 'nav-tab-active' : ''; ?>">
						<?php echo esc_html( $label ); ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<div class="rr-tab-content">
				<?php
				switch ( $active_tab ) {
					case 'settings':
						self::render_tab_settings();
						break;
					case 'llm':
						self::render_tab_llm();
						break;
					case 'faq':
						self::render_tab_faq();
						break;
					case 'tools':
						self::render_tab_tools();
						break;
					case 'info':
						self::render_tab_info();
						break;
				}
				?>
			</div>
		</div>
		<?php
	}

	// ═══════════════════════════════════════════════════════════════════════════
	// TAB: Settings
	// ═══════════════════════════════════════════════════════════════════════════

	private static function render_tab_settings(): void {
		$key     = (string) get_option( RR_OPT_KEY, '' );
		$display = ! empty( $key ) ? substr( $key, 0, 7 ) . str_repeat( '••••', 6 ) : '';
		?>
		<?php settings_errors(); ?>

		<form method="post" action="options.php" novalidate="novalidate">
			<?php settings_fields( self::SETTINGS_GROUP ); ?>

			<!-- OpenAI Configuration -->
			<div class="rr-card">
				<h2 class="rr-card-title"><?php esc_html_e( 'OpenAI Configuration', 'rankready' ); ?></h2>
				<p class="rr-card-desc"><?php esc_html_e( 'Connect your OpenAI API key to enable AI-powered summary generation.', 'rankready' ); ?></p>

				<table class="form-table rr-form-table">
					<tr>
						<th scope="row"><label for="rr_api_key"><?php esc_html_e( 'API Key', 'rankready' ); ?></label></th>
						<td>
							<input type="password" id="rr_api_key" name="<?php echo esc_attr( RR_OPT_KEY ); ?>"
								   value="<?php echo esc_attr( $display ); ?>" class="regular-text"
								   autocomplete="new-password" spellcheck="false" />
							<p class="description"><?php esc_html_e( 'Your OpenAI secret key. Stored server-side only.', 'rankready' ); ?></p>
							<?php if ( ! empty( $key ) ) : ?>
								<p style="margin-top:8px;">
									<a href="<?php echo esc_url( self::test_connection_url() ); ?>" class="button button-secondary">
										<?php esc_html_e( 'Test Connection', 'rankready' ); ?>
									</a>
								</p>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="rr_model"><?php esc_html_e( 'Model', 'rankready' ); ?></label></th>
						<td>
							<select name="<?php echo esc_attr( RR_OPT_MODEL ); ?>" id="rr_model">
								<?php $current_model = (string) get_option( RR_OPT_MODEL, 'gpt-4o-mini' ); ?>
								<?php foreach ( self::get_allowed_models() as $value => $label ) : ?>
									<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current_model, $value ); ?>>
										<?php echo esc_html( $label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'gpt-4o-mini recommended — fast, cheap, accurate for summaries.', 'rankready' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Post Types', 'rankready' ); ?></th>
						<td>
							<?php $selected_types = (array) get_option( RR_OPT_POST_TYPES, array( 'post' ) ); ?>
							<?php foreach ( self::get_allowed_post_types() as $slug => $label ) : ?>
								<label style="display:block;margin-bottom:4px;">
									<input type="checkbox" name="<?php echo esc_attr( RR_OPT_POST_TYPES ); ?>[]"
										   value="<?php echo esc_attr( $slug ); ?>"
										   <?php checked( in_array( $slug, $selected_types, true ) ); ?> />
									<?php echo esc_html( $label ); ?>
								</label>
							<?php endforeach; ?>
							<p class="description"><?php esc_html_e( 'Summaries will only auto-generate for selected post types.', 'rankready' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="rr_custom_prompt"><?php esc_html_e( 'Custom Prompt', 'rankready' ); ?></label></th>
						<td>
							<textarea name="<?php echo esc_attr( RR_OPT_CUSTOM_PROMPT ); ?>" id="rr_custom_prompt"
									  rows="4" class="large-text"
									  placeholder="<?php esc_attr_e( 'Leave empty to use the default optimized prompt.', 'rankready' ); ?>"
							><?php echo esc_textarea( (string) get_option( RR_OPT_CUSTOM_PROMPT, '' ) ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Optional. Extra instructions appended to the AI prompt.', 'rankready' ); ?></p>
						</td>
					</tr>
				</table>
			</div>

			<!-- Auto Display -->
			<div class="rr-card">
				<h2 class="rr-card-title"><?php esc_html_e( 'Auto Display', 'rankready' ); ?></h2>
				<p class="rr-card-desc"><?php esc_html_e( 'Automatically show the AI summary on posts without placing a block or widget.', 'rankready' ); ?></p>

				<table class="form-table rr-form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Auto Display', 'rankready' ); ?></th>
						<td>
							<?php $auto_display = (string) get_option( RR_OPT_AUTO_DISPLAY, 'off' ); ?>
							<label>
								<input type="radio" name="<?php echo esc_attr( RR_OPT_AUTO_DISPLAY ); ?>" value="on" <?php checked( $auto_display, 'on' ); ?> />
								<?php esc_html_e( 'On — Automatically inject summary into post content', 'rankready' ); ?>
							</label><br/>
							<label>
								<input type="radio" name="<?php echo esc_attr( RR_OPT_AUTO_DISPLAY ); ?>" value="off" <?php checked( $auto_display, 'off' ); ?> />
								<?php esc_html_e( 'Off — Only show via Gutenberg block or Elementor widget', 'rankready' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label><?php esc_html_e( 'Position', 'rankready' ); ?></label></th>
						<td>
							<?php $display_pos = (string) get_option( RR_OPT_DISPLAY_POSITION, 'before' ); ?>
							<select name="<?php echo esc_attr( RR_OPT_DISPLAY_POSITION ); ?>">
								<option value="before" <?php selected( $display_pos, 'before' ); ?>><?php esc_html_e( 'Before content', 'rankready' ); ?></option>
								<option value="after"  <?php selected( $display_pos, 'after' ); ?>><?php esc_html_e( 'After content', 'rankready' ); ?></option>
							</select>
						</td>
					</tr>
				</table>
			</div>

			<!-- Block & Widget Defaults -->
			<div class="rr-card">
				<h2 class="rr-card-title"><?php esc_html_e( 'Block & Widget Defaults', 'rankready' ); ?></h2>
				<p class="rr-card-desc"><?php esc_html_e( 'Defaults for Gutenberg blocks, Elementor widgets, and auto-displayed summaries.', 'rankready' ); ?></p>

				<table class="form-table rr-form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Show Label', 'rankready' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( RR_OPT_SHOW_LABEL ); ?>" value="1"
									   <?php checked( get_option( RR_OPT_SHOW_LABEL, '1' ), '1' ); ?> />
								<?php esc_html_e( 'Show the label heading above the summary bullets', 'rankready' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label><?php esc_html_e( 'Label Text', 'rankready' ); ?></label></th>
						<td>
							<input type="text" name="<?php echo esc_attr( RR_OPT_LABEL ); ?>"
								   value="<?php echo esc_attr( (string) get_option( RR_OPT_LABEL, 'Key Takeaways' ) ); ?>"
								   class="regular-text" />
							<p class="description"><?php esc_html_e( 'e.g. "Key Takeaways", "Article Summary", "TL;DR"', 'rankready' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label><?php esc_html_e( 'Label HTML Tag', 'rankready' ); ?></label></th>
						<td>
							<?php $current_tag = (string) get_option( RR_OPT_HEADING_TAG, 'h4' ); ?>
							<select name="<?php echo esc_attr( RR_OPT_HEADING_TAG ); ?>">
								<?php foreach ( array( 'h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4', 'h5' => 'H5', 'h6' => 'H6', 'p' => 'P' ) as $tag => $label ) : ?>
									<option value="<?php echo esc_attr( $tag ); ?>" <?php selected( $current_tag, $tag ); ?>>
										<?php echo esc_html( $label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
				</table>
			</div>

			<?php submit_button( __( 'Save Settings', 'rankready' ) ); ?>
		</form>
		<?php
	}

	// ═══════════════════════════════════════════════════════════════════════════
	// TAB: LLM Optimization
	// ═══════════════════════════════════════════════════════════════════════════

	private static function render_tab_llm(): void {
		?>
		<?php settings_errors(); ?>

		<form method="post" action="options.php" novalidate="novalidate">
			<?php settings_fields( self::LLMS_GROUP ); ?>

			<!-- LLMs.txt -->
			<div class="rr-card">
				<h2 class="rr-card-title"><?php esc_html_e( 'LLMs.txt Generator', 'rankready' ); ?></h2>
				<p class="rr-card-desc"><?php esc_html_e( 'Generate a /llms.txt file following the llmstxt.org specification. Helps AI models understand your site.', 'rankready' ); ?></p>

				<table class="form-table rr-form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable LLMs.txt', 'rankready' ); ?></th>
						<td>
							<?php $llms_enable = (string) get_option( RR_OPT_LLMS_ENABLE, 'off' ); ?>
							<label class="rr-toggle">
								<input type="checkbox" name="<?php echo esc_attr( RR_OPT_LLMS_ENABLE ); ?>"
									   value="on" <?php checked( $llms_enable, 'on' ); ?>
									   data-toggle-target="rr-llms-fields" />
								<span class="rr-toggle-label"><?php esc_html_e( 'Serve /llms.txt on your site', 'rankready' ); ?></span>
							</label>
							<?php if ( 'on' === $llms_enable ) : ?>
								<p class="description" style="margin-top:8px;">
									<?php esc_html_e( 'Live at:', 'rankready' ); ?>
									<a href="<?php echo esc_url( home_url( '/llms.txt' ) ); ?>" target="_blank"><code><?php echo esc_html( home_url( '/llms.txt' ) ); ?></code></a>
								</p>
							<?php endif; ?>
						</td>
					</tr>
				</table>

				<div id="rr-llms-fields" class="rr-conditional-fields" <?php echo 'on' !== $llms_enable ? 'style="display:none;"' : ''; ?>>
					<table class="form-table rr-form-table">
						<tr>
							<th scope="row"><label for="rr_llms_site_name"><?php esc_html_e( 'Site Name (H1)', 'rankready' ); ?></label></th>
							<td>
								<input type="text" id="rr_llms_site_name" name="<?php echo esc_attr( RR_OPT_LLMS_SITE_NAME ); ?>"
									   value="<?php echo esc_attr( (string) get_option( RR_OPT_LLMS_SITE_NAME, '' ) ); ?>"
									   class="regular-text"
									   placeholder="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" />
								<p class="description"><?php esc_html_e( 'The H1 heading in your llms.txt. Defaults to your site name.', 'rankready' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="rr_llms_summary"><?php esc_html_e( 'Site Summary', 'rankready' ); ?></label></th>
							<td>
								<textarea id="rr_llms_summary" name="<?php echo esc_attr( RR_OPT_LLMS_SUMMARY ); ?>"
										  rows="3" class="large-text"
										  placeholder="<?php esc_attr_e( 'Brief one-line summary of your site (appears as blockquote)', 'rankready' ); ?>"
								><?php echo esc_textarea( (string) get_option( RR_OPT_LLMS_SUMMARY, '' ) ); ?></textarea>
								<p class="description"><?php esc_html_e( 'Rendered as a blockquote below the H1. Keep it concise.', 'rankready' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="rr_llms_about"><?php esc_html_e( 'About Section', 'rankready' ); ?></label></th>
							<td>
								<textarea id="rr_llms_about" name="<?php echo esc_attr( RR_OPT_LLMS_ABOUT ); ?>"
										  rows="5" class="large-text"
										  placeholder="<?php esc_attr_e( 'Detailed description of your site, products, services. Markdown supported.', 'rankready' ); ?>"
								><?php echo esc_textarea( (string) get_option( RR_OPT_LLMS_ABOUT, '' ) ); ?></textarea>
								<p class="description"><?php esc_html_e( 'Detailed info about your site. Supports markdown. Appears after the summary.', 'rankready' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Include Post Types', 'rankready' ); ?></th>
							<td>
								<?php $llms_types = (array) get_option( RR_OPT_LLMS_POST_TYPES, array( 'post', 'page' ) ); ?>
								<?php foreach ( self::get_allowed_post_types() as $slug => $label ) : ?>
									<label style="display:block;margin-bottom:4px;">
										<input type="checkbox" name="<?php echo esc_attr( RR_OPT_LLMS_POST_TYPES ); ?>[]"
											   value="<?php echo esc_attr( $slug ); ?>"
											   <?php checked( in_array( $slug, $llms_types, true ) ); ?> />
										<?php echo esc_html( $label ); ?>
									</label>
								<?php endforeach; ?>
								<p class="description"><?php esc_html_e( 'Which post types to list in llms.txt as file lists.', 'rankready' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="rr_llms_max"><?php esc_html_e( 'Max Posts per Type', 'rankready' ); ?></label></th>
							<td>
								<input type="number" id="rr_llms_max" name="<?php echo esc_attr( RR_OPT_LLMS_MAX_POSTS ); ?>"
									   value="<?php echo esc_attr( (string) get_option( RR_OPT_LLMS_MAX_POSTS, 100 ) ); ?>"
									   min="10" max="500" step="10" class="small-text" />
								<p class="description"><?php esc_html_e( 'Maximum number of posts per post type to include.', 'rankready' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Exclude Categories', 'rankready' ); ?></th>
							<td>
								<?php
								$exclude_cats = (array) get_option( RR_OPT_LLMS_EXCLUDE_CATS, array() );
								$all_cats     = get_categories( array( 'hide_empty' => false, 'orderby' => 'name', 'order' => 'ASC' ) );
								?>
								<?php if ( ! empty( $all_cats ) && ! is_wp_error( $all_cats ) ) : ?>
									<fieldset style="max-height:200px;overflow-y:auto;border:1px solid #ddd;padding:8px 12px;border-radius:4px;">
										<?php foreach ( $all_cats as $cat ) : ?>
											<label style="display:block;margin-bottom:4px;">
												<input type="checkbox" name="<?php echo esc_attr( RR_OPT_LLMS_EXCLUDE_CATS ); ?>[]"
													   value="<?php echo esc_attr( $cat->term_id ); ?>"
													   <?php checked( in_array( (int) $cat->term_id, $exclude_cats, true ) ); ?> />
												<?php echo esc_html( $cat->name ); ?> <span style="color:#999;">(<?php echo esc_html( $cat->count ); ?>)</span>
											</label>
										<?php endforeach; ?>
									</fieldset>
								<?php else : ?>
									<p class="description"><?php esc_html_e( 'No categories found.', 'rankready' ); ?></p>
								<?php endif; ?>
								<p class="description"><?php esc_html_e( 'Posts in checked categories will be excluded from llms.txt. Useful for filtering out demo, test, or irrelevant content.', 'rankready' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Exclude Tags', 'rankready' ); ?></th>
							<td>
								<?php
								$exclude_tags = (array) get_option( RR_OPT_LLMS_EXCLUDE_TAGS, array() );
								$all_tags     = get_tags( array( 'hide_empty' => false, 'orderby' => 'name', 'order' => 'ASC' ) );
								?>
								<?php if ( ! empty( $all_tags ) && ! is_wp_error( $all_tags ) ) : ?>
									<fieldset style="max-height:200px;overflow-y:auto;border:1px solid #ddd;padding:8px 12px;border-radius:4px;">
										<?php foreach ( $all_tags as $tag ) : ?>
											<label style="display:block;margin-bottom:4px;">
												<input type="checkbox" name="<?php echo esc_attr( RR_OPT_LLMS_EXCLUDE_TAGS ); ?>[]"
													   value="<?php echo esc_attr( $tag->term_id ); ?>"
													   <?php checked( in_array( (int) $tag->term_id, $exclude_tags, true ) ); ?> />
												<?php echo esc_html( $tag->name ); ?> <span style="color:#999;">(<?php echo esc_html( $tag->count ); ?>)</span>
											</label>
										<?php endforeach; ?>
									</fieldset>
								<?php else : ?>
									<p class="description"><?php esc_html_e( 'No tags found.', 'rankready' ); ?></p>
								<?php endif; ?>
								<p class="description"><?php esc_html_e( 'Posts with checked tags will be excluded from llms.txt.', 'rankready' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Show Categories Section', 'rankready' ); ?></th>
							<td>
								<?php $show_cats = (string) get_option( RR_OPT_LLMS_SHOW_CATEGORIES, 'on' ); ?>
								<label>
									<input type="checkbox" name="<?php echo esc_attr( RR_OPT_LLMS_SHOW_CATEGORIES ); ?>"
										   value="on" <?php checked( $show_cats, 'on' ); ?> />
									<?php esc_html_e( 'Show "Optional" categories section at the bottom of llms.txt', 'rankready' ); ?>
								</label>
							</td>
						</tr>
						<tr>
							<th scope="row"><label><?php esc_html_e( 'Cache Duration', 'rankready' ); ?></label></th>
							<td>
								<select name="<?php echo esc_attr( RR_OPT_LLMS_CACHE_TTL ); ?>">
									<?php $current_ttl = (int) get_option( RR_OPT_LLMS_CACHE_TTL, 3600 ); ?>
									<?php foreach ( array(
										900   => __( '15 minutes', 'rankready' ),
										3600  => __( '1 hour', 'rankready' ),
										21600 => __( '6 hours', 'rankready' ),
										86400 => __( '24 hours', 'rankready' ),
									) as $seconds => $label ) : ?>
										<option value="<?php echo esc_attr( $seconds ); ?>" <?php selected( $current_ttl, $seconds ); ?>>
											<?php echo esc_html( $label ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<p class="description"><?php esc_html_e( 'How long to cache the generated llms.txt output.', 'rankready' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Full Version', 'rankready' ); ?></th>
							<td>
								<?php $llms_full = (string) get_option( RR_OPT_LLMS_FULL_ENABLE, 'off' ); ?>
								<label>
									<input type="checkbox" name="<?php echo esc_attr( RR_OPT_LLMS_FULL_ENABLE ); ?>"
										   value="on" <?php checked( $llms_full, 'on' ); ?> />
									<?php esc_html_e( 'Also serve /llms-full.txt with full post content inlined', 'rankready' ); ?>
								</label>
								<?php if ( 'on' === $llms_full && 'on' === $llms_enable ) : ?>
									<p class="description" style="margin-top:4px;">
										<a href="<?php echo esc_url( home_url( '/llms-full.txt' ) ); ?>" target="_blank"><code><?php echo esc_html( home_url( '/llms-full.txt' ) ); ?></code></a>
									</p>
								<?php endif; ?>
							</td>
						</tr>
					</table>
				</div>
			</div>

			<!-- Markdown Endpoints -->
			<div class="rr-card">
				<h2 class="rr-card-title"><?php esc_html_e( 'Markdown Endpoints', 'rankready' ); ?></h2>
				<p class="rr-card-desc"><?php esc_html_e( 'Serve every post as clean Markdown at its URL + .md suffix. LLM crawlers get structured content.', 'rankready' ); ?></p>

				<table class="form-table rr-form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable .md Endpoints', 'rankready' ); ?></th>
						<td>
							<?php $md_enable = (string) get_option( RR_OPT_MD_ENABLE, 'off' ); ?>
							<label class="rr-toggle">
								<input type="checkbox" name="<?php echo esc_attr( RR_OPT_MD_ENABLE ); ?>"
									   value="on" <?php checked( $md_enable, 'on' ); ?>
									   data-toggle-target="rr-md-fields" />
								<span class="rr-toggle-label"><?php esc_html_e( 'Add .md endpoint to each post URL', 'rankready' ); ?></span>
							</label>
							<?php if ( 'on' === $md_enable ) : ?>
								<p class="description" style="margin-top:8px;">
									<?php esc_html_e( 'Example:', 'rankready' ); ?>
									<code>yoursite.com/sample-post.md</code>
								</p>
							<?php endif; ?>
						</td>
					</tr>
				</table>

				<div id="rr-md-fields" class="rr-conditional-fields" <?php echo 'on' !== $md_enable ? 'style="display:none;"' : ''; ?>>
					<table class="form-table rr-form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Post Types', 'rankready' ); ?></th>
							<td>
								<?php $md_types = (array) get_option( RR_OPT_MD_POST_TYPES, array( 'post', 'page' ) ); ?>
								<?php foreach ( self::get_allowed_post_types() as $slug => $label ) : ?>
									<label style="display:block;margin-bottom:4px;">
										<input type="checkbox" name="<?php echo esc_attr( RR_OPT_MD_POST_TYPES ); ?>[]"
											   value="<?php echo esc_attr( $slug ); ?>"
											   <?php checked( in_array( $slug, $md_types, true ) ); ?> />
										<?php echo esc_html( $label ); ?>
									</label>
								<?php endforeach; ?>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Include Metadata', 'rankready' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="<?php echo esc_attr( RR_OPT_MD_INCLUDE_META ); ?>"
										   value="1" <?php checked( get_option( RR_OPT_MD_INCLUDE_META, '1' ), '1' ); ?> />
									<?php esc_html_e( 'Add YAML frontmatter (title, date, author, excerpt, tags)', 'rankready' ); ?>
								</label>
							</td>
						</tr>
					</table>
				</div>
			</div>

			<!-- LLM Crawler Access (robots.txt) -->
			<div class="rr-card">
				<h2 class="rr-card-title"><?php esc_html_e( 'LLM Crawler Access (robots.txt)', 'rankready' ); ?></h2>
				<p class="rr-card-desc">
					<?php esc_html_e( 'Control which AI crawlers can access your content via robots.txt. Enabled crawlers get explicit Allow rules appended — never modifies existing rules from Rank Math, Yoast, or any other plugin.', 'rankready' ); ?>
				</p>

				<table class="form-table rr-form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable Crawler Rules', 'rankready' ); ?></th>
						<td>
							<?php $robots_enable = (string) get_option( RR_OPT_ROBOTS_ENABLE, 'on' ); ?>
							<label class="rr-toggle">
								<input type="checkbox" name="<?php echo esc_attr( RR_OPT_ROBOTS_ENABLE ); ?>"
									   value="on" <?php checked( $robots_enable, 'on' ); ?>
									   data-toggle-target="rr-robots-fields" />
								<span class="rr-toggle-label"><?php esc_html_e( 'Append LLM crawler rules to robots.txt', 'rankready' ); ?></span>
							</label>
							<p class="description"><?php esc_html_e( 'Adds per-crawler User-agent blocks with Allow directives. Safe — appends only, never touches existing rules.', 'rankready' ); ?></p>
						</td>
					</tr>
				</table>

				<div id="rr-robots-fields" class="rr-conditional-fields" <?php echo 'on' !== $robots_enable ? 'style="display:none;"' : ''; ?>>
					<table class="form-table rr-form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Allow Crawlers', 'rankready' ); ?></th>
							<td>
								<?php
								$enabled_crawlers = (array) get_option( RR_OPT_ROBOTS_CRAWLERS, array_keys( self::get_llm_crawlers() ) );
								$all_crawlers     = self::get_llm_crawlers();
								$current_company  = '';
								?>
								<fieldset style="max-height:400px;overflow-y:auto;border:1px solid #ddd;padding:12px 16px;border-radius:4px;">
									<p style="margin:0 0 8px;"><strong>
										<label><input type="checkbox" id="rr-crawlers-select-all" /> <?php esc_html_e( 'Select / Deselect All', 'rankready' ); ?></label>
									</strong></p>
									<hr style="margin:8px 0;" />
									<?php foreach ( $all_crawlers as $ua => $info ) : ?>
										<?php if ( $info[0] !== $current_company ) :
											$current_company = $info[0];
											if ( 'OpenAI' !== $current_company ) : ?>
												<hr style="margin:8px 0;border:none;border-top:1px solid #eee;" />
											<?php endif; ?>
											<p style="margin:4px 0 2px;font-weight:600;color:#1d2327;font-size:13px;"><?php echo esc_html( $current_company ); ?></p>
										<?php endif; ?>
										<label style="display:block;margin-bottom:3px;padding-left:16px;">
											<input type="checkbox" name="<?php echo esc_attr( RR_OPT_ROBOTS_CRAWLERS ); ?>[]"
												   value="<?php echo esc_attr( $ua ); ?>"
												   class="rr-crawler-checkbox"
												   <?php checked( in_array( $ua, $enabled_crawlers, true ) ); ?> />
											<code style="font-size:12px;"><?php echo esc_html( $ua ); ?></code>
											<span style="color:#666;font-size:12px;"> — <?php echo esc_html( $info[1] ); ?></span>
										</label>
									<?php endforeach; ?>
								</fieldset>
								<p class="description" style="margin-top:8px;">
									<?php esc_html_e( 'Checked crawlers get "User-agent: X / Allow: /" appended to robots.txt. Helps AI search engines, AI Overviews, and answer engines discover and cite your content.', 'rankready' ); ?>
								</p>
							</td>
						</tr>
					</table>
				</div>
			</div>

			<?php submit_button( __( 'Save LLM Settings', 'rankready' ) ); ?>
		</form>

		<!-- Cache Controls (outside form) -->
		<div class="rr-card rr-card--subtle">
			<h3 class="rr-card-title" style="font-size:14px;"><?php esc_html_e( 'Cache Management', 'rankready' ); ?></h3>
			<p class="description"><?php esc_html_e( 'Clear cached LLMs.txt output to regenerate with latest content.', 'rankready' ); ?></p>
			<p style="margin-top:10px;">
				<button id="rr-flush-llms-cache" class="button button-secondary">
					<?php esc_html_e( 'Flush LLMs.txt Cache', 'rankready' ); ?>
				</button>
				<span id="rr-flush-status" style="margin-left:10px;font-size:13px;color:#00a32a;display:none;">
					<?php esc_html_e( 'Cache cleared.', 'rankready' ); ?>
				</span>
			</p>
		</div>
		<?php
	}

	// ═══════════════════════════════════════════════════════════════════════════
	// TAB: FAQ Generator
	// ═══════════════════════════════════════════════════════════════════════════

	private static function render_tab_faq(): void {
		?>
		<?php settings_errors(); ?>

		<form method="post" action="options.php" novalidate="novalidate">
			<?php settings_fields( self::FAQ_GROUP ); ?>

			<!-- DataForSEO Configuration -->
			<div class="rr-card">
				<h2 class="rr-card-title"><?php esc_html_e( 'DataForSEO API', 'rankready' ); ?></h2>
				<p class="rr-card-desc"><?php esc_html_e( 'DataForSEO powers question discovery via keyword suggestions and related keywords. Sign up at dataforseo.com.', 'rankready' ); ?></p>

				<table class="form-table rr-form-table">
					<tr>
						<th scope="row"><label for="rr_dfs_login"><?php esc_html_e( 'API Login', 'rankready' ); ?></label></th>
						<td>
							<input type="text" id="rr_dfs_login" name="<?php echo esc_attr( RR_OPT_DFS_LOGIN ); ?>"
							       value="<?php echo esc_attr( (string) get_option( RR_OPT_DFS_LOGIN, '' ) ); ?>"
							       class="regular-text" autocomplete="off" />
							<p class="description"><?php esc_html_e( 'Your DataForSEO API login email.', 'rankready' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="rr_dfs_password"><?php esc_html_e( 'API Password', 'rankready' ); ?></label></th>
						<td>
							<input type="password" id="rr_dfs_password" name="<?php echo esc_attr( RR_OPT_DFS_PASSWORD ); ?>"
							       value="<?php echo esc_attr( (string) get_option( RR_OPT_DFS_PASSWORD, '' ) ); ?>"
							       class="regular-text" autocomplete="new-password" />
							<p class="description"><?php esc_html_e( 'Your DataForSEO API password.', 'rankready' ); ?></p>
						</td>
					</tr>
				</table>
			</div>

			<!-- FAQ Settings -->
			<div class="rr-card">
				<h2 class="rr-card-title"><?php esc_html_e( 'FAQ Settings', 'rankready' ); ?></h2>
				<p class="rr-card-desc"><?php esc_html_e( 'Configure how FAQs are generated and displayed. Uses DataForSEO for question discovery and OpenAI for answers with brand entity injection.', 'rankready' ); ?></p>

				<table class="form-table rr-form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Post Types', 'rankready' ); ?></th>
						<td>
							<?php $faq_types = (array) get_option( RR_OPT_FAQ_POST_TYPES, array( 'post' ) ); ?>
							<?php foreach ( self::get_allowed_post_types() as $slug => $label ) : ?>
								<label style="display:block;margin-bottom:4px;">
									<input type="checkbox" name="<?php echo esc_attr( RR_OPT_FAQ_POST_TYPES ); ?>[]"
									       value="<?php echo esc_attr( $slug ); ?>"
									       <?php checked( in_array( $slug, $faq_types, true ) ); ?> />
									<?php echo esc_html( $label ); ?>
								</label>
							<?php endforeach; ?>
							<p class="description"><?php esc_html_e( 'FAQ will be generated for these post types.', 'rankready' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="rr_faq_count"><?php esc_html_e( 'FAQ Count', 'rankready' ); ?></label></th>
						<td>
							<input type="number" id="rr_faq_count" name="<?php echo esc_attr( RR_OPT_FAQ_COUNT ); ?>"
							       value="<?php echo esc_attr( (string) get_option( RR_OPT_FAQ_COUNT, 5 ) ); ?>"
							       min="3" max="10" step="1" class="small-text" />
							<p class="description"><?php esc_html_e( 'Number of FAQ items to generate per post (3-10). More FAQs = more API calls.', 'rankready' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="rr_faq_brand_terms"><?php esc_html_e( 'Brand Terms', 'rankready' ); ?></label></th>
						<td>
							<textarea id="rr_faq_brand_terms" name="<?php echo esc_attr( RR_OPT_FAQ_BRAND_TERMS ); ?>"
							          rows="3" class="large-text"
							          placeholder="<?php esc_attr_e( 'e.g. The Plus Addons, Elementor, NexterWP, UiChemy (one per line or comma-separated)', 'rankready' ); ?>"
							><?php echo esc_textarea( (string) get_option( RR_OPT_FAQ_BRAND_TERMS, '' ) ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Brand/product names to inject as semantic triples in FAQ answers. This builds brand-entity association for LLMs (+642% AI citation lift).', 'rankready' ); ?></p>
						</td>
					</tr>
				</table>
			</div>

			<!-- FAQ Display -->
			<div class="rr-card">
				<h2 class="rr-card-title"><?php esc_html_e( 'FAQ Display', 'rankready' ); ?></h2>
				<p class="rr-card-desc"><?php esc_html_e( 'Control how FAQs appear on the frontend. Can also use the Gutenberg block or Elementor widget instead.', 'rankready' ); ?></p>

				<table class="form-table rr-form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Auto Display', 'rankready' ); ?></th>
						<td>
							<?php $faq_auto = (string) get_option( RR_OPT_FAQ_AUTO_DISPLAY, 'off' ); ?>
							<label>
								<input type="radio" name="<?php echo esc_attr( RR_OPT_FAQ_AUTO_DISPLAY ); ?>" value="on" <?php checked( $faq_auto, 'on' ); ?> />
								<?php esc_html_e( 'On — Automatically inject FAQ into post content', 'rankready' ); ?>
							</label><br/>
							<label>
								<input type="radio" name="<?php echo esc_attr( RR_OPT_FAQ_AUTO_DISPLAY ); ?>" value="off" <?php checked( $faq_auto, 'off' ); ?> />
								<?php esc_html_e( 'Off — Only show via block, widget, or shortcode', 'rankready' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label><?php esc_html_e( 'Position', 'rankready' ); ?></label></th>
						<td>
							<?php $faq_pos = (string) get_option( RR_OPT_FAQ_POSITION, 'after' ); ?>
							<select name="<?php echo esc_attr( RR_OPT_FAQ_POSITION ); ?>">
								<option value="before" <?php selected( $faq_pos, 'before' ); ?>><?php esc_html_e( 'Before content', 'rankready' ); ?></option>
								<option value="after"  <?php selected( $faq_pos, 'after' ); ?>><?php esc_html_e( 'After content', 'rankready' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label><?php esc_html_e( 'Heading Tag', 'rankready' ); ?></label></th>
						<td>
							<?php $faq_tag = (string) get_option( RR_OPT_FAQ_HEADING_TAG, 'h3' ); ?>
							<select name="<?php echo esc_attr( RR_OPT_FAQ_HEADING_TAG ); ?>">
								<?php foreach ( array( 'h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4', 'h5' => 'H5', 'h6' => 'H6' ) as $tag => $label ) : ?>
									<option value="<?php echo esc_attr( $tag ); ?>" <?php selected( $faq_tag, $tag ); ?>>
										<?php echo esc_html( $label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Show "Reviewed" Date', 'rankready' ); ?></th>
						<td>
							<?php $show_reviewed = (string) get_option( RR_OPT_FAQ_SHOW_REVIEWED, 'on' ); ?>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( RR_OPT_FAQ_SHOW_REVIEWED ); ?>"
								       value="on" <?php checked( $show_reviewed, 'on' ); ?> />
								<?php esc_html_e( 'Show "Last reviewed: [date]" below the FAQ section', 'rankready' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Signals content freshness to LLMs and users.', 'rankready' ); ?></p>
						</td>
					</tr>
				</table>
			</div>

			<?php submit_button( __( 'Save FAQ Settings', 'rankready' ) ); ?>
		</form>
		<?php
	}

	// ═══════════════════════════════════════════════════════════════════════════
	// TAB: Tools
	// ═══════════════════════════════════════════════════════════════════════════

	private static function render_tab_tools(): void {
		$post_types = self::get_allowed_post_types();
		$users      = self::get_authors();
		?>

		<!-- Bulk Regenerate Summaries -->
		<div class="rr-card">
			<h2 class="rr-card-title"><?php esc_html_e( 'Bulk Regenerate Summaries', 'rankready' ); ?></h2>
			<p class="rr-card-desc">
				<?php esc_html_e( 'Generate AI summaries for all existing published posts. Processes 5 posts at a time.', 'rankready' ); ?>
			</p>

			<table class="form-table rr-form-table" style="width:auto;">
				<tr>
					<th style="padding:10px 20px 10px 0;"><?php esc_html_e( 'Post Types', 'rankready' ); ?></th>
					<td>
						<?php foreach ( $post_types as $slug => $label ) : ?>
							<label style="display:block;margin-bottom:4px;">
								<input type="checkbox" class="rr-bulk-type" value="<?php echo esc_attr( $slug ); ?>" checked />
								<?php echo esc_html( $label ); ?>
							</label>
						<?php endforeach; ?>
					</td>
				</tr>
				<tr>
					<th></th>
					<td>
						<button id="rr-bulk-start" class="button button-primary"><?php esc_html_e( 'Start Bulk Generate', 'rankready' ); ?></button>
						<button id="rr-bulk-stop" class="button button-secondary" style="display:none;margin-left:8px;"><?php esc_html_e( 'Stop', 'rankready' ); ?></button>
					</td>
				</tr>
			</table>

			<div id="rr-bulk-progress" style="display:none;margin-top:16px;">
				<div class="rr-progress-track">
					<div id="rr-bulk-bar" class="rr-progress-fill"></div>
				</div>
				<p id="rr-bulk-status" class="rr-progress-label"><?php esc_html_e( 'Preparing...', 'rankready' ); ?></p>
			</div>
		</div>

		<!-- Bulk Author Changer -->
		<div class="rr-card">
			<h2 class="rr-card-title"><?php esc_html_e( 'Bulk Author Changer', 'rankready' ); ?></h2>
			<p class="rr-card-desc">
				<?php esc_html_e( 'Reassign authors across any post type. Preview the affected count before executing.', 'rankready' ); ?>
			</p>

			<table class="form-table rr-form-table">
				<!-- Post Types -->
				<tr>
					<th scope="row"><?php esc_html_e( 'Post Types', 'rankready' ); ?></th>
					<td>
						<div class="rr-checkboxes-inline">
							<?php foreach ( self::get_author_post_types() as $slug => $label ) : ?>
								<label>
									<input type="checkbox" class="rr-bac-pt" value="<?php echo esc_attr( $slug ); ?>" />
									<?php echo esc_html( $label ); ?>
								</label>
							<?php endforeach; ?>
						</div>
						<p class="description"><?php esc_html_e( 'Select one or more post types to affect.', 'rankready' ); ?></p>
					</td>
				</tr>
				<!-- From Author -->
				<tr>
					<th scope="row"><label for="rr-bac-from"><?php esc_html_e( 'Current Author (From)', 'rankready' ); ?></label></th>
					<td>
						<select id="rr-bac-from" class="regular-text">
							<option value=""><?php esc_html_e( '-- All authors --', 'rankready' ); ?></option>
							<?php foreach ( $users as $user ) : ?>
								<option value="<?php echo esc_attr( $user->ID ); ?>">
									<?php echo esc_html( $user->display_name . ' (@' . $user->user_login . ')' ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php esc_html_e( 'Leave blank to reassign posts regardless of current author.', 'rankready' ); ?></p>
					</td>
				</tr>
				<!-- To Author -->
				<tr>
					<th scope="row">
						<label for="rr-bac-to"><?php esc_html_e( 'New Author (To)', 'rankready' ); ?> <span style="color:#d63638;">*</span></label>
					</th>
					<td>
						<select id="rr-bac-to" class="regular-text">
							<option value=""><?php esc_html_e( '-- Select new author --', 'rankready' ); ?></option>
							<?php foreach ( $users as $user ) : ?>
								<option value="<?php echo esc_attr( $user->ID ); ?>">
									<?php echo esc_html( $user->display_name . ' (@' . $user->user_login . ')' ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<!-- Date Range -->
				<tr>
					<th scope="row"><?php esc_html_e( 'Date Range', 'rankready' ); ?></th>
					<td>
						<div class="rr-date-range">
							<div>
								<label for="rr-bac-date-from"><?php esc_html_e( 'After', 'rankready' ); ?></label>
								<input type="date" id="rr-bac-date-from" />
							</div>
							<div>
								<label for="rr-bac-date-to"><?php esc_html_e( 'Before', 'rankready' ); ?></label>
								<input type="date" id="rr-bac-date-to" />
							</div>
						</div>
						<p class="description"><?php esc_html_e( 'Optional — leave blank to include all dates.', 'rankready' ); ?></p>
					</td>
				</tr>
				<!-- Actions -->
				<tr>
					<th></th>
					<td>
						<div class="rr-tool-actions">
							<button id="rr-bac-preview" class="button button-secondary"><?php esc_html_e( 'Preview Count', 'rankready' ); ?></button>
							<button id="rr-bac-execute" class="button button-primary" disabled><?php esc_html_e( 'Execute', 'rankready' ); ?></button>
							<button id="rr-bac-stop" class="button" style="display:none;"><?php esc_html_e( 'Stop', 'rankready' ); ?></button>
						</div>
					</td>
				</tr>
			</table>

			<!-- Preview result -->
			<div id="rr-bac-preview-result" style="display:none;" class="rr-notice rr-notice--info"></div>

			<!-- Progress -->
			<div id="rr-bac-progress" style="display:none;margin-top:16px;">
				<div class="rr-progress-track">
					<div id="rr-bac-bar" class="rr-progress-fill"></div>
				</div>
				<p id="rr-bac-status" class="rr-progress-label"></p>
			</div>

			<!-- Done -->
			<div id="rr-bac-done" style="display:none;" class="rr-notice rr-notice--success"></div>
		</div>

		<!-- Bulk Generate FAQs -->
		<div class="rr-card">
			<h2 class="rr-card-title"><?php esc_html_e( 'Bulk Generate FAQs', 'rankready' ); ?></h2>
			<p class="rr-card-desc">
				<?php esc_html_e( 'Generate FAQ Q&A pairs for all existing published posts using DataForSEO + OpenAI. Requires both API keys to be configured.', 'rankready' ); ?>
			</p>

			<table class="form-table rr-form-table" style="width:auto;">
				<tr>
					<th style="padding:10px 20px 10px 0;"><?php esc_html_e( 'Post Types', 'rankready' ); ?></th>
					<td>
						<?php foreach ( $post_types as $slug => $label ) : ?>
							<label style="display:block;margin-bottom:4px;">
								<input type="checkbox" class="rr-faq-bulk-type" value="<?php echo esc_attr( $slug ); ?>" checked />
								<?php echo esc_html( $label ); ?>
							</label>
						<?php endforeach; ?>
					</td>
				</tr>
				<tr>
					<th></th>
					<td>
						<button id="rr-faq-bulk-start" class="button button-primary"><?php esc_html_e( 'Start Bulk FAQ Generate', 'rankready' ); ?></button>
						<button id="rr-faq-bulk-stop" class="button button-secondary" style="display:none;margin-left:8px;"><?php esc_html_e( 'Stop', 'rankready' ); ?></button>
					</td>
				</tr>
			</table>

			<div id="rr-faq-bulk-progress" style="display:none;margin-top:16px;">
				<div class="rr-progress-track">
					<div id="rr-faq-bulk-bar" class="rr-progress-fill"></div>
				</div>
				<p id="rr-faq-bulk-status" class="rr-progress-label"><?php esc_html_e( 'Preparing...', 'rankready' ); ?></p>
			</div>
		</div>
		<?php
	}

	// ═══════════════════════════════════════════════════════════════════════════
	// TAB: Info
	// ═══════════════════════════════════════════════════════════════════════════

	private static function render_tab_info(): void {
		$summary_count = 0;
		$enabled_types = (array) get_option( RR_OPT_POST_TYPES, array( 'post' ) );

		if ( ! empty( $enabled_types ) ) {
			global $wpdb;
			$summary_count = (int) $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value != ''",
				RR_META_SUMMARY
			) );
		}

		$faq_count = 0;
		global $wpdb;
		$faq_count = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value != ''",
			RR_META_FAQ
		) );
		?>
		<div class="rr-card">
			<h2 class="rr-card-title"><?php esc_html_e( 'How It Works', 'rankready' ); ?></h2>

			<div class="rr-info-grid">
				<div class="rr-info-item">
					<span class="dashicons dashicons-update rr-info-icon"></span>
					<h3><?php esc_html_e( 'AI Summary', 'rankready' ); ?></h3>
					<p><?php esc_html_e( 'On publish/update, the plugin generates an AI summary in the background. The API is only called when content changes (verified via content hash).', 'rankready' ); ?></p>
				</div>
				<div class="rr-info-item">
					<span class="dashicons dashicons-editor-code rr-info-icon"></span>
					<h3><?php esc_html_e( 'Schema Markup', 'rankready' ); ?></h3>
					<p><?php esc_html_e( 'Article JSON-LD schema with speakable markup is injected automatically. Skips if Yoast, Rank Math, or AIOSEO is active.', 'rankready' ); ?></p>
				</div>
				<div class="rr-info-item">
					<span class="dashicons dashicons-media-text rr-info-icon"></span>
					<h3><?php esc_html_e( 'LLMs.txt', 'rankready' ); ?></h3>
					<p><?php esc_html_e( 'Generates a /llms.txt file following the llmstxt.org spec. Lists your content for AI models to understand your site structure.', 'rankready' ); ?></p>
				</div>
				<div class="rr-info-item">
					<span class="dashicons dashicons-editor-paste-text rr-info-icon"></span>
					<h3><?php esc_html_e( 'Markdown Endpoints', 'rankready' ); ?></h3>
					<p><?php esc_html_e( 'Appending .md to any post URL serves clean Markdown with YAML frontmatter. Ideal for LLM crawlers and AI agents.', 'rankready' ); ?></p>
				</div>
			</div>
		</div>

		<div class="rr-card">
			<h2 class="rr-card-title"><?php esc_html_e( 'Quick Stats', 'rankready' ); ?></h2>
			<div class="rr-stats-row">
				<div class="rr-stat">
					<span class="rr-stat-number"><?php echo esc_html( number_format_i18n( $summary_count ) ); ?></span>
					<span class="rr-stat-label"><?php esc_html_e( 'AI Summaries Generated', 'rankready' ); ?></span>
				</div>
				<div class="rr-stat">
					<span class="rr-stat-number"><?php echo 'on' === get_option( RR_OPT_LLMS_ENABLE, 'off' ) ? '&#10003;' : '&#10007;'; ?></span>
					<span class="rr-stat-label"><?php esc_html_e( 'LLMs.txt', 'rankready' ); ?></span>
				</div>
				<div class="rr-stat">
					<span class="rr-stat-number"><?php echo 'on' === get_option( RR_OPT_MD_ENABLE, 'off' ) ? '&#10003;' : '&#10007;'; ?></span>
					<span class="rr-stat-label"><?php esc_html_e( 'Markdown Endpoints', 'rankready' ); ?></span>
				</div>
				<div class="rr-stat">
					<span class="rr-stat-number"><?php echo ! empty( get_option( RR_OPT_KEY, '' ) ) ? '&#10003;' : '&#10007;'; ?></span>
					<span class="rr-stat-label"><?php esc_html_e( 'API Key', 'rankready' ); ?></span>
				</div>
				<div class="rr-stat">
					<span class="rr-stat-number"><?php echo esc_html( number_format_i18n( $faq_count ) ); ?></span>
					<span class="rr-stat-label"><?php esc_html_e( 'FAQs Generated', 'rankready' ); ?></span>
				</div>
			</div>
		</div>

		<div class="rr-card rr-card--subtle">
			<p style="margin:0;font-size:13px;color:#646970;">
				<?php
				printf(
					/* translators: %s: POSIMYTH link */
					esc_html__( 'RankReady v%1$s by %2$s', 'rankready' ),
					esc_html( RR_VERSION ),
					'<a href="https://posimyth.com" target="_blank">POSIMYTH Innovations</a>'
				);
				?>
			</p>
		</div>
		<?php
	}

	// ── Per-post meta box ─────────────────────────────────────────────────────

	public static function register_meta_box(): void {
		$post_types = (array) get_option( RR_OPT_POST_TYPES, array( 'post' ) );
		foreach ( $post_types as $pt ) {
			add_meta_box(
				'rr_summary_meta',
				__( 'RankReady -- AI Summary', 'rankready' ),
				array( self::class, 'render_meta_box' ),
				$pt,
				'side',
				'default'
			);
		}
	}

	public static function render_meta_box( $post ): void {
		$disabled  = (bool) get_post_meta( $post->ID, RR_META_DISABLE, true );
		$summary   = (string) get_post_meta( $post->ID, RR_META_SUMMARY, true );
		$generated = (int) get_post_meta( $post->ID, RR_META_GENERATED, true );

		wp_nonce_field( 'rr_meta_box', 'rr_meta_nonce' );
		?>
		<p>
			<label>
				<input type="checkbox" name="rr_disable_summary" value="1" <?php checked( $disabled ); ?> />
				<?php esc_html_e( 'Disable AI summary for this post', 'rankready' ); ?>
			</label>
		</p>
		<?php if ( $generated ) : ?>
			<p class="description">
				<?php
				printf(
					esc_html__( 'Last generated: %s ago', 'rankready' ),
					esc_html( human_time_diff( $generated ) )
				);
				?>
			</p>
		<?php endif; ?>
		<?php if ( ! empty( $summary ) ) :
			$decoded = RR_Generator::decode_summary( $summary );
			if ( 'bullets' === $decoded['type'] ) : ?>
				<ul style="margin:8px 0 0;padding-left:16px;list-style:disc;">
					<?php foreach ( (array) $decoded['data'] as $bullet ) : ?>
						<li style="font-size:12px;margin-bottom:4px;"><?php echo esc_html( $bullet ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<p style="font-size:12px;margin:8px 0 0;"><?php echo esc_html( $decoded['data'] ); ?></p>
			<?php endif;
		else : ?>
			<p class="description" style="font-style:italic;">
				<?php esc_html_e( 'No summary yet. It will generate on publish/update.', 'rankready' ); ?>
			</p>
		<?php endif;
	}

	public static function save_meta_box( $post_id ): void {
		if ( ! isset( $_POST['rr_meta_nonce'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['rr_meta_nonce'] ) ), 'rr_meta_box' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$disabled = isset( $_POST['rr_disable_summary'] ) ? '1' : '';
		update_post_meta( $post_id, RR_META_DISABLE, $disabled );
	}

	// ── Test connection ───────────────────────────────────────────────────────

	private static function test_connection_url(): string {
		return add_query_arg( array(
			'page'           => self::MENU_SLUG,
			'tab'            => 'settings',
			self::NONCE_FIELD => wp_create_nonce( self::NONCE_ACTION ),
			'rr_action'      => 'test',
		), admin_url( 'admin.php' ) );
	}

	public static function connection_notice(): void {
		$screen = get_current_screen();
		if ( ! $screen || 'toplevel_page_' . self::MENU_SLUG !== $screen->id ) {
			return;
		}
		if ( empty( $_GET['rr_action'] ) || 'test' !== $_GET['rr_action'] ) {
			return;
		}
		if ( ! isset( $_GET[ self::NONCE_FIELD ] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET[ self::NONCE_FIELD ] ) ), self::NONCE_ACTION )
		) {
			wp_die( esc_html__( 'Security check failed.', 'rankready' ) );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'rankready' ) );
		}
		$result = RR_Generator::test_api_connection();
		if ( true === $result ) {
			echo '<div class="notice notice-success is-dismissible"><p>'
				. esc_html__( 'Connection successful! Your OpenAI API key is valid.', 'rankready' )
				. '</p></div>';
		} else {
			echo '<div class="notice notice-error is-dismissible"><p>'
				. esc_html__( 'Connection failed: ', 'rankready' )
				. esc_html( $result )
				. '</p></div>';
		}
	}

	// ── Plugin action links ───────────────────────────────────────────────────

	public static function action_links( array $links ): array {
		$settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=' . self::MENU_SLUG ) ) . '">'
			. esc_html__( 'Settings', 'rankready' )
			. '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}

	// ── Helpers ───────────────────────────────────────────────────────────────

	public static function get_allowed_models(): array {
		return array(
			'gpt-4o-mini'   => 'GPT-4o Mini -- fast & cheap (recommended)',
			'gpt-4o'        => 'GPT-4o -- more powerful, higher cost',
			'gpt-3.5-turbo' => 'GPT-3.5 Turbo -- legacy',
		);
	}

	public static function get_allowed_post_types(): array {
		$types  = get_post_types( array( 'public' => true ), 'objects' );
		$result = array();
		foreach ( $types as $slug => $obj ) {
			if ( 'attachment' === $slug ) {
				continue;
			}
			$result[ $slug ] = $obj->labels->singular_name . ' (' . $slug . ')';
		}
		return $result;
	}

	public static function get_author_post_types(): array {
		$types  = get_post_types( array( 'public' => true ), 'objects' );
		$result = array();
		foreach ( $types as $slug => $obj ) {
			if ( post_type_supports( $slug, 'author' ) ) {
				$result[ $slug ] = $obj->labels->singular_name . ' (' . $slug . ')';
			}
		}
		return $result;
	}

	public static function get_authors(): array {
		return get_users( array(
			'role__in' => array( 'administrator', 'editor', 'author', 'contributor' ),
			'orderby'  => 'display_name',
			'order'    => 'ASC',
			'fields'   => array( 'ID', 'display_name', 'user_login' ),
		) );
	}
}
