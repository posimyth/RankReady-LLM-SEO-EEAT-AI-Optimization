=== RankReady – LLM SEO, EEAT & AI Optimization ===
Contributors: posimyth
Tags: llm seo, ai summary, schema markup, llms.txt, eeat
Requires at least: 6.2
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 2.5.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI summaries, FAQ Generator with brand entity injection, Article JSON-LD schema with speakable, LLMs.txt generator, Markdown endpoints, bulk author changer. Built for LLM SEO, EEAT, and AI Overviews.

== Description ==

RankReady optimizes your WordPress site for AI search engines, LLM crawlers, and Google AI Overviews.

**Features:**

* **AI Summary** — Auto-generates key takeaways on publish/update via OpenAI. Content-hash caching prevents API waste.
* **LLMs.txt Generator** — Serves /llms.txt and /llms-full.txt following the llmstxt.org specification. Helps AI models understand your site.
* **Markdown Endpoints** — Every post available as clean Markdown at its URL + .md (e.g., /post-slug.md). YAML frontmatter, content negotiation via Accept header, auto-discovery via link tag.
* **Article JSON-LD Schema** — Speakable markup injected automatically. Works alongside Yoast, Rank Math, AIOSEO.
* **Gutenberg Block** — Full style controls: colors, border, padding, fonts.
* **Elementor Widget** — Drag-and-drop AI summary widget with style controls.
* **Bulk Author Changer** — Reassign authors across any post type with preview and progress tracking.
* **FAQ Generator** — DataForSEO-powered question discovery + OpenAI answers with semantic triple brand injection. FAQPage JSON-LD schema.
* **Bulk Regenerate** — Regenerate all summaries or FAQs in batches with progress bar.

== Installation ==

1. Upload the `rankready` folder to `/wp-content/plugins/`.
2. Activate the plugin.
3. Go to RankReady in the admin menu.
4. Enter your OpenAI API key in the Settings tab.
5. Configure LLMs.txt and Markdown in the LLM Optimization tab.

== Changelog ==

= 2.5.0 =
* New: FAQ Generator — auto-generate FAQ Q&A pairs using DataForSEO keyword research + OpenAI with brand entity injection
* New: FAQPage JSON-LD schema — compound with Article, duplicate detection for Rank Math/Yoast/AIOSEO FAQ blocks
* New: Semantic triple brand injection — builds brand-entity association in FAQ answers (+642% AI citation lift pattern)
* New: FAQ auto-display with position control (before/after content)
* New: Bulk FAQ Generation — generate FAQs for all existing posts with progress bar
* New: FAQ in Markdown endpoints — FAQ section appended to .md output
* New: FAQ Generator admin tab with DataForSEO credentials, brand terms, FAQ count, heading tag controls
* New: lastmod dates in llms.txt entries — LLMs prioritize fresh content
* New: FAQ cleanup on uninstall — all FAQ options and post meta removed
* Fix: Content freshness signal via dateModified bump when FAQ is generated

= 2.4.5 =
* Fix: robots.txt header uses plain ASCII instead of Unicode box-drawing characters — prevents garbled symbols in physical file
* Fix: Improved regex for cleaning up old RankReady blocks in physical robots.txt (handles both Unicode and ASCII formats)

= 2.4.4 =
* Fix: Auto-flush rewrite rules on plugin update — .md and llms.txt endpoints work immediately after zip update without manual deactivate/reactivate
* Fix: Version-based check ensures robots.txt sync and rewrite flush run once per update

= 2.4.3 =
* Fix: Stacked User-agent lines in single block — no more repeating Allow rules per crawler
* Fix: Clean, compact robots.txt output per RFC 9309 spec

= 2.4.2 =
* Fix: Activation hook now sets default values for robots_enable and robots_crawlers
* Fix: Physical robots.txt sync works on first activation without needing manual settings save

= 2.4.1 =
* Fix: Physical robots.txt support — detects and writes directly to physical robots.txt file
* Fix: When physical robots.txt exists (Rank Math editor, manual file, WP Import Export), WordPress filter never fires — RankReady now handles both cases
* New: Auto-sync on activation — writes RankReady block to physical robots.txt immediately
* New: Clean removal on deactivation — removes RankReady block from physical robots.txt
* New: Settings-triggered sync — any settings change auto-updates the physical file

= 2.4.0 =
* New: LLM Crawler Access settings — per-crawler toggles for 22 AI bots (GPTBot, ClaudeBot, PerplexityBot, Google-Extended, etc.)
* New: Select/deselect all crawlers, grouped by company (OpenAI, Anthropic, Google, Apple, Meta, etc.)
* New: Enable/disable toggle for robots.txt crawler rules
* New: Smart deduplication — skips crawlers already defined by Rank Math or other plugins
* New: Global Allow directives for /llms.txt, /llms-full.txt, /*.md$ endpoints
* Fix: robots.txt rules are append-only — never modifies existing Rank Math, Yoast, or other plugin rules
* Fix: Crawlers driven by admin settings, not hardcoded list

= 2.3.0 =
* New: Taxonomy controls — Exclude Categories and Exclude Tags checkboxes in admin
* New: Show/Hide Categories Section toggle for the "Optional" section in llms.txt
* New: `rankready_exclude_from_llms` filter for developer-level post exclusions
* Fix: Removed X-Robots-Tag: noindex from llms.txt output — SEO plugins handle indexing
* Fix: llms.txt respects noindex from Rank Math (query-level meta_query), Yoast, AIOSEO, SEOPress
* Fix: Removed all hardcoded exclusions — no more auto-excluding by slug, word count, or page type
* Fix: Taxonomy exclusions applied at query level via tax_query for performance

= 2.1.0 =
* Security: Greedy .md rewrite rule scoped — excludes wp-admin, wp-content, wp-includes, wp-json
* Security: LLMs.txt cache flow bug fixed (missing return after cached output)
* Security: Memory exhaustion fix — bulk author capped at 10,000 with no_found_rows
* Security: Race condition guards on bulk operations (summary + author)
* Security: Constants wrapped in defined() guard to prevent conflicts
* Security: Anonymous closures replaced with named static methods
* Fix: SEOPress detection added to schema skip logic (was missing)
* Fix: Smart llms.txt conflict detection — skips if Rank Math/Yoast/AIOSEO/SEOPress already serves it
* Fix: WordPress trailing slash redirect loop on .txt and .md URLs
* Fix: Schema injection now checks enabled post types before injecting
* Fix: get_users() filtered to author-capable roles only (was returning all users)
* Fix: Elementor editor null check on Plugin instance
* Fix: str_word_count() replaced with locale-safe regex (works with CJK/Arabic)
* Fix: Activation hook registers rewrite rules before flushing
* Fix: Uninstall.php now flushes rewrite rules to clean up stale endpoints
* Fix: Bulk author change unhooks summary generation to prevent cascading API calls
* New: robots.txt integration — Allow directives for /llms.txt, /llms-full.txt, /*.md
* New: Visible "View as Markdown" link at bottom of content (like EDD)
* New: llms.txt metadata tells crawlers about .md availability and content negotiation
* New: Developer filters — rankready_inject_schema, rankready_force_llms_txt, rankready_show_md_link

= 2.0.0 =
* New: Tabbed admin interface (Settings, LLM Optimization, Tools, Info)
* New: LLMs.txt generator following llmstxt.org specification
* New: Optional /llms-full.txt with full post content inlined
* New: Markdown endpoints — /post-slug.md for LLM crawlers (llmstxt.org spec)
* New: Accept: text/markdown content negotiation (like Next.js/Cloudflare)
* New: Auto-discovery via link rel alternate type text/markdown
* New: Link HTTP header for markdown endpoint discovery
* New: Canonical Link header on .md responses pointing to HTML
* New: Vary: Accept header for CDN content negotiation
* New: YAML frontmatter in Markdown (title, date, author, tags, categories)
* New: Bulk Author Changer with preview, date range filter, progress bar
* New: Top-level admin menu with dedicated icon
* New: Info tab with quick stats
* New: Cache management for LLMs.txt
* Improved: Plugin now registered as top-level menu instead of under Settings
* Security: Nonce verification on all REST endpoints
* Security: Capability checks on all admin actions

= 1.0.0 =
* Initial release
