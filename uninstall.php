<?php
/**
 * Uninstall — clean up all plugin data.
 *
 * @package RankReady
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// ── Delete options ────────────────────────────────────────────────────────────
$options = array(
	// AI Summary.
	'rr_openai_api_key',
	'rr_openai_model',
	'rr_post_types',
	'rr_default_label',
	'rr_default_show_label',
	'rr_default_heading_tag',
	'rr_auto_display',
	'rr_display_position',
	'rr_custom_prompt',
	// Bulk summary state.
	'rr_bulk_queue',
	'rr_bulk_done',
	'rr_bulk_total',
	'rr_bulk_running',
	// LLMs.txt.
	'rr_llms_enable',
	'rr_llms_site_name',
	'rr_llms_summary',
	'rr_llms_about',
	'rr_llms_post_types',
	'rr_llms_max_posts',
	'rr_llms_cache_ttl',
	'rr_llms_full_enable',
	// Markdown.
	'rr_md_enable',
	'rr_md_post_types',
	'rr_md_include_meta',
	// LLMs.txt taxonomy controls.
	'rr_llms_exclude_cats',
	'rr_llms_exclude_tags',
	'rr_llms_show_categories',
	// Robots.txt crawler settings.
	'rr_robots_enable',
	'rr_robots_crawlers',
	// Bulk author state.
	'rr_bac_queue',
	'rr_bac_total',
	'rr_bac_done',
	'rr_bac_running',
	'rr_bac_to_author',
	// Version tracking.
	'rr_installed_version',
);

foreach ( $options as $option ) {
	delete_option( $option );
}

// ── Delete transients ─────────────────────────────────────────────────────────
delete_transient( 'rr_llms_txt_cache' );
delete_transient( 'rr_llms_full_txt_cache' );

// ── Delete post meta ──────────────────────────────────────────────────────────
global $wpdb;

$meta_keys = array(
	'_rr_summary',
	'_rr_content_hash',
	'_rr_last_generated',
	'_rr_disable_summary',
);

foreach ( $meta_keys as $key ) {
	$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => $key ) ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
}

// ── Clear scheduled cron ──────────────────────────────────────────────────────
wp_clear_scheduled_hook( 'rr_async_generate' );

// ── Flush rewrite rules to clean up llms.txt and .md endpoints ───────────────
flush_rewrite_rules( false );
