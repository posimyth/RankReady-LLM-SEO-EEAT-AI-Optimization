# RankReady – LLM SEO, EEAT & AI Optimization

**The WordPress plugin that gets your content cited by AI.**

RankReady is the most complete WordPress plugin for AI search optimization. It combines all pillars of LLM SEO into a single, lightweight package: AI-generated content, intelligent schema markup that auto-detects your content type, LLMs.txt, Markdown endpoints, AI crawler management, content freshness monitoring, and E-E-A-T author optimization.

[![WordPress](https://img.shields.io/badge/WordPress-6.2%2B-blue.svg)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-GPL--2.0--or--later-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Version](https://img.shields.io/badge/Version-1.3-orange.svg)](https://github.com/posimyth/RankReady-LLM-SEO-EEAT-AI-Optimization)

---

## Why RankReady Exists

AI search is replacing traditional search. ChatGPT, Perplexity, Google AI Overviews, and Claude are now how people find information. But most WordPress sites are invisible to these AI engines.

**The research is clear:**
- 81% of AI-cited pages include schema markup (AccuraCast 2025)
- Pages with FAQPage schema are 3.2x more likely to appear in AI Overviews
- 65% of AI citations target content updated within the past year
- 44% of citations come from the top third of the page
- ChatGPT search results correlate 87% with Bing's top 10
- AI agent traffic grew 6,900% year-over-year in 2025
- Incomplete schema causes an 18% citation penalty vs. no schema at all

RankReady handles all of this automatically.

---

## What Makes RankReady Different

No other WordPress plugin combines all of these:

| Capability | RankReady | Rank Math | Yoast | AIOSEO | LLMagnet | LovedByAI |
|-----------|-----------|-----------|-------|--------|----------|-----------|
| AI Summary Generation (OpenAI) | Yes | No | No | No | No | No |
| FAQ Generator with Brand Injection | Yes | No | No | No | No | No |
| FAQPage JSON-LD Schema | Yes | Block only | Block only | Block only | No | No |
| Article Schema + Speakable | Yes | Partial | Partial | Partial | No | No |
| **HowTo Schema Auto-Detection** | **Yes** | Manual block | Manual block | No | No | No |
| **ItemList Schema Auto-Detection** | **Yes** | No | No | No | No | No |
| LLMs.txt + llms-full.txt | Yes | Basic | Basic | Basic | Yes | No |
| Markdown Endpoints (.md) | Yes | No | No | No | No | No |
| Per-Crawler Robots.txt (31 bots) | Yes | No | 3 bots | No | No | No |
| Content Freshness Alerts | Yes | No | No | No | No | No |
| Bulk Author Changer (EEAT) | Yes | No | No | No | No | No |
| Content Negotiation (Accept header) | Yes | No | No | No | No | No |
| DataForSEO + OpenAI Usage Tracking | Yes | N/A | N/A | N/A | No | No |
| Health Check Diagnostic | Yes | No | No | No | No | No |

---

## Features

### 1. FAQ Generator with Brand Entity Injection

Auto-generates SEO-optimized FAQ Q&A pairs using a two-stage pipeline:

1. **DataForSEO** discovers real search questions (keyword suggestions + related keywords)
2. **OpenAI** generates answers using your actual page content + brand terms

**Why it matters:**
- FAQPage schema drives 3.2x more AI Overview appearances than any other schema type
- Brand entity injection uses semantic triples (Subject-Predicate-Object) to naturally associate your brand with relevant topics
- Focus keyword auto-detected from Rank Math, Yoast, AIOSEO, or SEOPress
- Content hash prevents duplicate API calls when content hasn't changed
- Bulk generation across all post types with resume capability

### 2. AI Summary (Key Takeaways)

Generates concise bullet-point summaries from post content via OpenAI on publish/update.

- Content-hash caching: only regenerates when content changes
- Custom prompt support for controlling summary style and tone
- Included in Markdown endpoints and schema output
- Bulk regenerate with progress tracking and resume
- Per-post disable toggle

### 3. Intelligent Schema Auto-Detection (NEW in v1.3)

RankReady now automatically detects your content type and injects the right schema — no manual blocks, no configuration needed.

#### Article JSON-LD Schema with Speakable

Complete Article/BlogPosting schema with AI citation optimization:

- `headline`, `datePublished`, `dateModified`, `author`, `publisher`, `image`, `description`
- `speakable` markup for voice-query optimization
- `about` entities from hierarchical taxonomies (categories) — works with ALL custom post types
- `mentions` entities from non-hierarchical taxonomies (tags) — works with ALL custom taxonomies
- `hasPart` with AI summary content
- Automatic detection: skips when Rank Math, Yoast, or AIOSEO is active (no duplicate schema)
- FAQPage schema injected separately when FAQ data exists
- Duplicate detection: skips FAQ schema when Rank Math/Yoast/AIOSEO FAQ blocks exist in content

#### HowTo JSON-LD Schema (Auto-Detected)

Scans your existing post content for step-by-step patterns and injects HowTo schema automatically. No manual HowTo blocks needed — if your content already has steps, RankReady detects them.

**How detection works:**
- Title must contain "how to", "step by step", "tutorial", or "guide to"
- Content is scanned for step patterns using three methods:
  1. **Step N headings**: `<h2>Step 1: Do this</h2>`, `<h3>Step 2 - Do that</h3>`
  2. **Numbered headings**: `<h2>1. Do this</h2>`, `<h3>2) Do that</h3>`
  3. **Ordered lists**: `<ol><li>First do this</li><li>Then do that</li></ol>`
- Requires minimum 2 steps to inject schema
- Extracts step name, description text (up to 500 chars), and step images automatically
- Skips if Rank Math HowTo block or Yoast How-To block already exists in the post

**Why it matters:**
- HowTo schema is one of Google's supported rich result types — eligible for step-by-step carousels in search
- AI models extract HowTo structured data to build direct answers for "how to" queries
- Rank Math and Yoast require a manual HowTo block — RankReady auto-detects from your existing content
- Zero content changes needed — your blog posts already have the steps, RankReady just tells search engines about them

**Example**: A post titled "How to Speed Up WordPress" with `<h2>Step 1: Enable Caching</h2>` headings will automatically get HowTo JSON-LD injected in `<head>`.

#### ItemList JSON-LD Schema (Auto-Detected)

Scans your listicle posts and injects ItemList schema automatically. Perfect for "Best of", "Top N", and comparison posts that AI models use to build recommendation answers.

**How detection works:**
- Title must match listicle patterns:
  - Number + qualifier: "10 Best WordPress Plugins", "Top 5 Elementor Addons"
  - Qualifier + number: "Best 10 Tools for SEO", "Top 5 Page Builders"
  - Number + noun: "7 Plugins Every Developer Needs", "15 Tips for Faster WordPress"
  - Qualifier without number: "Best Elementor Addons for 2025", "Top WordPress Themes"
  - Supports 20+ list nouns: tools, plugins, addons, themes, extensions, widgets, alternatives, tips, tricks, strategies, and more
- Items extracted from content using two methods:
  1. **Numbered headings**: `<h2>1. Elementor Pro</h2>`, `<h3>#2 Beaver Builder</h3>`
  2. **Consecutive headings**: Sequences of 3+ h2/h3 headings (auto-filters generic sections like Introduction, FAQ, Conclusion)
- Requires minimum 3 items to inject schema
- Extracts per item: name, URL (prioritizes links matching the item name), description (first paragraph, 200 chars), and image

**Why it matters:**
- ItemList schema tells Google and AI models "this is a curated list of items" — eligible for list rich results
- AI models like ChatGPT and Perplexity heavily cite listicle pages when answering "best X for Y" queries
- Products mentioned in ItemList schema get stronger entity recognition in AI knowledge graphs
- No other WordPress plugin auto-detects listicle content — Rank Math, Yoast, and AIOSEO don't offer this

**Mutually exclusive with HowTo**: A post gets one schema type or the other based on title patterns. If the title matches "how to" patterns, ItemList detection is skipped. This prevents conflicting schema on the same page.

**Example**: A post titled "10 Best Elementor Addons for 2025" with `<h2>1. Essential Addons</h2>` through `<h2>10. Happy Addons</h2>` will automatically get ItemList JSON-LD with all 10 items, their URLs, descriptions, and images.

### 4. LLMs.txt Generator

Serves `/llms.txt` and `/llms-full.txt` per the [llmstxt.org specification](https://llmstxt.org/).

- Structured site index for AI models to understand your content
- `/llms.txt` — token-efficient links and descriptions
- `/llms-full.txt` — full content as clean markdown (uses `strip_shortcodes()` for performance)
- Respects noindex from Rank Math, Yoast, AIOSEO, SEOPress
- Taxonomy controls: exclude categories and tags
- Transient caching with configurable TTL
- Multisite-safe: skips physical robots.txt sync on multisite

### 5. Markdown Endpoints

Every post available as clean Markdown at `URL.md`:

```
https://example.com/my-post/     -> HTML
https://example.com/my-post.md   -> Clean Markdown
```

- YAML frontmatter: title, date, author, description, categories, tags, word count
- Strips Elementor, Divi, WPBakery, Beaver Builder markup
- Content negotiation via `Accept: text/markdown` header
- `<link rel="alternate" type="text/markdown">` auto-discovery
- 5-minute transient cache keyed by `post_modified` — no repeated processing on bot crawls

### 6. AI Crawler Management (robots.txt)

Per-crawler toggles for **31 AI bots** with automatic robots.txt management:

| Company | Bots |
|---------|------|
| **OpenAI** | GPTBot, ChatGPT-User, OAI-SearchBot |
| **Anthropic** | ClaudeBot, anthropic-ai, Claude-Web |
| **Google** | Google-Extended, GoogleOther |
| **Apple** | Applebot-Extended |
| **Microsoft** | Bingbot |
| **Perplexity** | PerplexityBot |
| **Meta** | Meta-ExternalAgent, Meta-ExternalFetcher, FacebookBot |
| **Mistral** | MistralAI-User |
| **ByteDance** | Bytespider |
| **Amazon** | Amazonbot |
| **Cohere** | cohere-ai |
| **Search Engines** | DuckAssistBot, YouBot, PhindBot |
| **Training/Data** | CCBot, AI2Bot, Diffbot, Omgilibot, PetalBot, Brightbot, magpie-crawler, DataForSeoBot |

**Strategy support:** Block training bots (GPTBot) while allowing search bots (OAI-SearchBot, ChatGPT-User). Per RFC 9309 spec. Append-only, never modifies existing plugin rules.

### 7. Content Freshness Alerts

Monitor content staleness that impacts AI visibility:

- 65% of AI citations target content from the past year
- 50% of citations are from content less than 13 weeks old
- Configurable threshold: 60, 90, 180, or 365 days
- Urgency levels: critical (>1yr), high (>6mo), moderate (>threshold)
- Shows summary/FAQ status per post
- Fresh percentage dashboard
- Direct edit links for stale posts

### 8. Bulk Author Changer (EEAT)

Reassign post authors across any post type for E-E-A-T optimization:

- Filter by post type, date range, source author
- Preview count before executing
- Batch processing with progress tracking
- Capped at 10,000 posts per operation

### 9. Tools Dashboard

- **Health Check**: 12-point diagnostic scan of all plugin features
- **API Usage Tracking**: OpenAI tokens + DataForSEO cost monitoring
- **Error Log**: Recent API errors with source, timestamp, post reference
- **Bulk Operations**: Summary, FAQ, and Start Over with resume capability

---

## Schema Auto-Detection: How It Decides

RankReady reads your post title and content, then picks the right schema automatically:

```
Post Published/Updated
    |
    |-- Is Rank Math / Yoast / AIOSEO active?
    |     |-- YES → Skip Article schema (SEO plugin handles it)
    |     |-- NO  → Inject Article + Speakable JSON-LD
    |
    |-- Does FAQ data exist for this post?
    |     |-- YES → Does Rank Math/Yoast FAQ block exist in content?
    |     |         |-- YES → Skip FAQPage schema (SEO plugin handles it)
    |     |         |-- NO  → Inject FAQPage JSON-LD
    |     |-- NO  → Skip
    |
    |-- Title contains "how to", "tutorial", "step by step"?
    |     |-- YES → Does Rank Math/Yoast HowTo block exist?
    |     |         |-- YES → Skip (SEO plugin handles it)
    |     |         |-- NO  → Extract steps from content → Inject HowTo JSON-LD
    |     |-- NO  → Continue
    |
    |-- Title matches listicle pattern ("Best N", "Top N", "N Tools")?
          |-- YES → Extract items from headings → Inject ItemList JSON-LD
          |-- NO  → Skip
```

**Every schema type has duplicate detection.** RankReady never conflicts with Rank Math, Yoast, or AIOSEO. If a competing plugin already handles a schema type, RankReady steps aside.

---

## How AI Discovers Your Content

```
AI Crawler visits your site
    |
    |-- robots.txt
    |     |-- Allow: /llms.txt
    |     |-- Allow: /llms-full.txt
    |     |-- Allow: /*.md$
    |
    |-- /llms.txt (structured site index)
    |     |-- Links to every published post
    |     |-- Links to /llms-full.txt
    |
    |-- /llms-full.txt (full content dump)
    |     |-- Every post as inline markdown
    |
    |-- /any-post.md (per-post markdown)
    |     |-- YAML frontmatter
    |     |-- AI Summary (key takeaways)
    |     |-- Clean content
    |
    |-- HTML page
          |-- Article JSON-LD + Speakable schema
          |-- FAQPage JSON-LD (if FAQ data exists)
          |-- HowTo JSON-LD (if tutorial/how-to post)
          |-- ItemList JSON-LD (if listicle post)
          |-- <link rel="alternate" type="text/markdown">
          |-- Link HTTP header to .md version
          |-- Accept: text/markdown negotiation
```

---

## Installation

1. Download the latest release zip
2. **Plugins > Add New > Upload Plugin** in WordPress admin
3. Activate and go to **RankReady** in the admin menu
4. Configure:
   - **API Keys tab**: OpenAI key + DataForSEO credentials
   - **AI Summary tab**: Post types, prompts, auto-generate settings
   - **FAQ Generator tab**: FAQ count, brand terms, display settings
   - **LLM Optimization tab**: LLMs.txt, Markdown, Crawler Access
   - **Tools tab**: Bulk operations, freshness alerts, health check

## Requirements

- WordPress 6.2+
- PHP 7.4+
- OpenAI API key (for summary + FAQ generation)
- DataForSEO credentials (for FAQ question discovery)

---

## Security

- All REST endpoints require authentication + capability checks
- `$wpdb->prepare()` with positional placeholders on all dynamic SQL
- `sanitize_callback` on all REST route parameters
- `esc_html()`, `esc_attr()`, `esc_url()` on all output
- API keys never exposed in REST responses or health check output
- Bulk operations capped at 10,000 posts
- Physical robots.txt managed via WP_Filesystem API (WordPress.org compliant)
- Multisite guard on robots.txt sync
- `flush_rewrite_rules()` deferred to `init` hook (prevents corrupting other plugins' rules)
- Clean uninstall removes all options and post meta

---

## Compatibility

**SEO Plugins** (read-only integration):
- Rank Math, Yoast SEO, AIOSEO, SEOPress
- Auto-detects focus keywords, respects noindex, prevents schema duplication
- HowTo/ItemList schema skips injection when competing plugin's blocks exist

**Page Builders** (strips wrapper markup in markdown):
- Elementor, Divi, WPBakery, Beaver Builder, Gutenberg

**Display Options**:
- Gutenberg blocks (Summary + FAQ) with full style controls
- Elementor widgets (Summary + FAQ)
- Auto-display above or below content

---

## Developer Filters

```php
// Force RankReady's llms.txt even if another plugin handles it
add_filter( 'rankready_force_llms_txt', '__return_true' );

// Exclude specific posts from llms.txt
add_filter( 'rankready_exclude_from_llms', function( $exclude, $post ) {
    if ( $post->post_type === 'landing-page' ) return true;
    return $exclude;
}, 10, 2 );

// Hide the "View as Markdown" link
add_filter( 'rankready_show_md_link', '__return_false' );

// Disable schema injection for specific posts
add_filter( 'rankready_inject_schema', function( $inject, $post ) {
    if ( $post->ID === 123 ) return false;
    return $inject;
}, 10, 2 );

// Disable HowTo schema auto-detection
add_filter( 'rankready_inject_howto_schema', '__return_false' );

// Disable ItemList schema auto-detection
add_filter( 'rankready_inject_itemlist_schema', '__return_false' );

// Customize ItemList schema output
add_filter( 'rankready_itemlist_schema', function( $schema, $post ) {
    $schema['itemListOrder'] = 'https://schema.org/ItemListOrderDescending';
    return $schema;
}, 10, 2 );
```

---

## Changelog

### 1.3 (Latest)
- **HowTo JSON-LD schema auto-detection** — scans post content for step-by-step patterns (Step N headings, numbered headings, ordered lists) and injects HowTo schema automatically. No manual blocks needed. Skips when Rank Math or Yoast HowTo blocks exist.
- **ItemList JSON-LD schema auto-detection** — scans listicle posts ("Best N", "Top N", "N Tools/Plugins/Addons") and injects ItemList schema with item names, URLs, descriptions, and images. Supports 20+ list noun patterns and numbered/consecutive heading extraction.
- **Mutually exclusive schema detection** — HowTo and ItemList are automatically exclusive. Title patterns determine which schema type a post gets. No configuration needed.
- **WP_Filesystem API** — all robots.txt file operations now use WordPress Filesystem API instead of direct PHP file functions. Ready for WordPress.org plugin directory submission.
- **Developer filters** — `rankready_inject_howto_schema`, `rankready_inject_itemlist_schema`, and `rankready_itemlist_schema` for full developer control.

### 1.2
- Content Freshness Alerts with urgency scoring (critical/high/moderate)
- Expanded to 31 AI crawlers (was 22) — added anthropic-ai, GoogleOther, Meta-ExternalFetcher, MistralAI-User, PetalBot, Omgilibot, Brightbot, magpie-crawler, DataForSeoBot
- PHP 8.0+ compatibility fix (FAQ return type)
- FAQ OpenAI call uses JSON response format (prevents markdown-wrapped JSON)
- Markdown endpoints cached via 5-minute transients (keyed by post_modified)
- llms-full.txt uses strip_shortcodes for performance (prevents expensive shortcode execution during bulk generation)
- flush_rewrite_rules deferred to init hook

### 0.4.6
- Security hardening: API key leak prevention, SQL prepare with positional placeholders, multisite guard
- get_term_link WP_Error checks in schema
- FAQ OpenAI HTTP status validation
- Migration runs only once via completion flag

### 0.4.5
- Schema about/mentions work with ALL custom post types and custom taxonomies

### 0.4.4
- Health Check diagnostic tool (12-point scan)
- DataForSEO usage tracking
- Resume button for bulk operations

[Full changelog in readme.txt](readme.txt)

---

## License

GPL-2.0-or-later. See [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html).

---

Built by [POSIMYTH Innovations](https://posimyth.com)
