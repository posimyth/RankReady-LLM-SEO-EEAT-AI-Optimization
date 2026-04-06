# RankReady - LLM SEO, EEAT & AI Optimization

**Make your WordPress site visible to AI search engines, LLM crawlers, and Google AI Overviews.**

RankReady is the only WordPress plugin that combines all six pillars of LLM SEO into a single, lightweight package: LLMs.txt, Markdown endpoints, AI crawler management, AI-generated summaries, Speakable schema, and E-E-A-T author optimization.

[![WordPress](https://img.shields.io/badge/WordPress-6.2%2B-blue.svg)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-GPL--2.0--or--later-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Version](https://img.shields.io/badge/Version-2.4.5-orange.svg)](https://github.com/posimyth/RankReady-LLM-SEO-EEAT-AI-Optimization)

---

## The Problem

AI search is replacing traditional search. ChatGPT, Perplexity, Google AI Overviews, and Claude are now how people find information. But most WordPress sites are invisible to these AI engines because:

- AI crawlers can't efficiently parse bloated HTML (page builders, scripts, ads, nav menus)
- There's no structured way for LLMs to understand what your site is about
- robots.txt doesn't distinguish between training bots and search/retrieval bots
- Schema markup is missing or incomplete for AI citation
- No content is optimized for how LLMs actually consume information

**The data is clear:**
- 65% of pages cited by Google AI Mode include structured data
- Pages with proper schema get cited 3.1x more frequently in AI Overviews
- Markdown uses 80% fewer tokens than HTML for the same content
- 95% of ChatGPT citations come from content updated within 10 months

RankReady solves all of this.

---

## Features

### 1. LLMs.txt Generator

Serves `/llms.txt` and `/llms-full.txt` following the [llmstxt.org specification](https://llmstxt.org/) — the same standard used by Anthropic, Cloudflare, Stripe, and Vercel.

**What it does:**
- Generates a structured index of your site for AI models to understand your content at a glance
- `/llms.txt` — Links and descriptions (token-efficient index)
- `/llms-full.txt` — Full content inlined as clean markdown (comprehensive context)
- Respects noindex from Rank Math, Yoast, AIOSEO, and SEOPress
- Taxonomy controls: exclude specific categories and tags
- Smart conflict detection: skips generation if another SEO plugin already handles it
- Transient caching with configurable TTL
- Auto-busts cache when posts are published, updated, or deleted

**Why it matters:** Over 844,000 sites now have llms.txt. It's the equivalent of sitemap.xml for AI — a structured entry point that tells AI models what your site contains without them having to crawl every page.

---

### 2. Markdown Endpoints

Every post and page available as clean Markdown at its URL + `.md`.

```
https://example.com/my-blog-post/     -> HTML (normal)
https://example.com/my-blog-post.md   -> Clean Markdown
```

**What it does:**
- YAML frontmatter with title, date, author, description, categories, tags, word count, featured image
- Aggressively strips Elementor, Divi, WPBakery, Beaver Builder wrapper markup
- Content negotiation via `Accept: text/markdown` header (like Cloudflare's paid feature)
- Auto-discovery via `<link rel="alternate" type="text/markdown">` in HTML head
- `Link` HTTP header for crawler discovery
- Canonical `Link` header on `.md` responses pointing back to HTML
- `Vary: Accept` header for proper CDN behavior
- Visible "View as Markdown" link at bottom of content
- Scoped rewrite rules: excludes wp-admin, wp-content, wp-includes, wp-json

**Why it matters:** Markdown is the native language of LLMs. HTML bloat (nav, footer, scripts, ads) wastes context window tokens. A simple heading costs ~3 tokens in Markdown vs 12-15 tokens in HTML. Cloudflare charges for this at the infrastructure level (Pro+ plans). RankReady gives it to every WordPress site for free.

**Developer filters:**
```php
// Disable the visible "View as Markdown" link
add_filter( 'rankready_show_md_link', '__return_false' );
```

---

### 3. LLM Crawler Access (robots.txt)

Per-crawler toggles for 22 AI bots with automatic robots.txt management.

**Supported crawlers:**

| Company | Training Bots | Search/Retrieval Bots |
|---------|--------------|----------------------|
| OpenAI | GPTBot | OAI-SearchBot, ChatGPT-User |
| Anthropic | anthropic-ai | ClaudeBot, Claude-Web |
| Google | Google-Extended | — |
| Perplexity | — | PerplexityBot |
| Apple | — | Applebot-Extended |
| Meta | Meta-ExternalAgent, Meta-ExternalFetcher | — |
| Others | CCBot, Bytespider, Diffbot, ImagesiftBot, Omgili, Timpibot | cohere-ai, YouBot, PetalBot |

**What it does:**
- Enable/disable toggle for the entire robots.txt block
- Individual checkboxes for each of the 22 crawlers
- Select/Deselect All with one click
- Stacked `User-agent` lines in a single block (per RFC 9309 spec)
- Global `Allow` directives for `/llms.txt`, `/llms-full.txt`, `/*.md$`
- Append-only: never modifies existing Rank Math, Yoast, or other plugin rules
- Smart deduplication: skips crawlers already defined by other plugins
- Physical robots.txt support: detects and writes directly when WordPress filter doesn't fire
- Auto-sync on settings change
- Clean removal on deactivation

**Why it matters:** The industry consensus is "block training bots, allow retrieval bots." But most plugins offer blanket allow/block with no granularity. RankReady lets site owners make per-crawler decisions — block GPTBot (training) while allowing OAI-SearchBot (search results). This is the difference between protecting your IP and being invisible in AI search.

---

### 4. AI Summary (Key Takeaways)

Auto-generates bullet-point summaries on publish/update via OpenAI.

**What it does:**
- Generates concise key takeaways from post content using OpenAI API
- Content-hash caching: only regenerates when content actually changes (no API waste)
- Custom prompt support for controlling summary style
- Per-post disable toggle
- Summaries included in Markdown endpoints and Gutenberg block output
- Bulk regenerate with progress bar for existing content
- Supports GPT-4o, GPT-4o-mini, and other OpenAI models

**Why it matters:** LLMs prefer structured, bullet-point information. Sites with clear key takeaways are easier for AI to cite accurately. The content-hash approach means you never waste API calls on unchanged content.

---

### 5. Article JSON-LD Schema with Speakable

Automatic Article schema injection with Speakable markup.

**What it does:**
- Injects complete `Article` / `BlogPosting` JSON-LD schema
- Speakable markup for voice-query optimization
- Includes: headline, datePublished, dateModified, author (with URL), publisher, image, description
- Works alongside Rank Math, Yoast, AIOSEO, SEOPress (doesn't duplicate)
- Checks enabled post types before injecting
- Schema only injected on singular views

**Why it matters:**
- 65% of Google AI Mode citations include structured data
- 71% for ChatGPT
- Schema-compliant pages get cited 3.1x more frequently
- Speakable is still in beta at Google but growing as AI assistants handle voice queries
- Partially-filled or generic schema causes an 18-percentage-point citation penalty vs no schema — RankReady generates complete, accurate schema every time

---

### 6. Bulk Author Changer

Reassign post authors across any post type with preview and progress tracking.

**What it does:**
- Select source author (From) and target author (To)
- Filter by post type, date range
- Preview count before executing
- Batch processing with progress bar
- Race condition guards
- Unhooks summary generation during bulk operations to prevent cascading API calls
- Capped at 10,000 posts per operation for memory safety

**Why it matters:** E-E-A-T (Experience, Expertise, Authoritativeness, Trustworthiness) directly impacts AI citations. Sites with proper author profiles saw 2.6x more traffic. Reassigning posts from generic "admin" accounts to recognized subject-matter experts is one of the highest-impact E-E-A-T signals.

---

### 7. Gutenberg Block & Elementor Widget

Display AI summaries with full design control.

**Gutenberg Block:**
- Color controls (background, text, border)
- Border width and radius
- Padding controls
- Font size and heading tag selection
- Show/hide label toggle
- Live editor preview

**Elementor Widget:**
- Drag-and-drop AI summary widget
- Box, label, and bullet style controls
- Full Elementor style panel integration

---

## Why RankReady vs Alternatives

### vs Rank Math / Yoast / AIOSEO

These are traditional SEO plugins. Their LLM features are an afterthought:

| Feature | RankReady | Rank Math | Yoast Premium | AIOSEO |
|---------|-----------|-----------|---------------|--------|
| LLMs.txt | Full spec + llms-full.txt | Basic (module) | Basic | Basic |
| Per-post Markdown endpoints (.md) | 17 files, YAML frontmatter | No | No | No |
| Per-crawler robots.txt toggles (22 bots) | Yes | No | 3 crawlers only | No |
| AI Summary auto-generation | OpenAI-powered | No | Key Takeaways block | No |
| Speakable schema | Yes | No | No | No |
| Physical robots.txt sync | Yes | Yes | No | No |
| Content negotiation (Accept: text/markdown) | Yes | No | No | No |
| Noindex-aware llms.txt | Yes (RM + Yoast + AIOSEO + SEOPress) | Own only | Own only | Own only |

### vs Cloudflare Markdown for Agents

Cloudflare offers automatic HTML-to-Markdown conversion on Pro+ plans ($20/mo+). RankReady provides the same capability for free, plus:

- YAML frontmatter with categories and tags (Cloudflare omits these)
- `<link rel="alternate" type="text/markdown">` discovery tag in HTML head
- Visible "View as Markdown" link for human discovery
- AI Summary (Key Takeaways) embedded in markdown output
- Noindex-aware llms.txt with taxonomy controls
- Per-crawler robots.txt management

### vs LLMagnet / LovedByAI

These are newer LLM SEO plugins:

| Feature | RankReady | LLMagnet | LovedByAI |
|---------|-----------|----------|-----------|
| LLMs.txt | Yes | Yes | No |
| Markdown endpoints | Yes | No | No |
| Per-crawler robots.txt | 22 bots with toggles | No | No |
| AI Summary | OpenAI-powered | No | No |
| Speakable schema | Yes | No | No |
| Bulk Author Changer | Yes | No | No |
| Bot traffic analytics | No | Yes | Yes |
| AI citation tracking | No | No | Yes |

RankReady focuses on making your content optimally consumable by AI. LLMagnet and LovedByAI focus on tracking what AI does with your content. They're complementary, not competing.

---

## How LLMs Discover Your Content

RankReady creates multiple discovery paths so no AI crawler misses your content:

```
LLM Crawler visits your site
    |
    |-- robots.txt
    |     |-- Allow: /llms.txt
    |     |-- Allow: /llms-full.txt
    |     |-- Allow: /*.md$
    |
    |-- /llms.txt (structured site index)
    |     |-- Links to every published post
    |     |-- Links to /llms-full.txt
    |     |-- Notes about .md availability
    |
    |-- /llms-full.txt (full content dump)
    |     |-- Every post as inline markdown
    |
    |-- /any-post.md (per-post markdown)
    |     |-- YAML frontmatter (metadata)
    |     |-- AI Summary (key takeaways)
    |     |-- Clean content (no HTML bloat)
    |
    |-- HTML page
          |-- <link rel="alternate" type="text/markdown">
          |-- Link HTTP header to .md version
          |-- Article JSON-LD + Speakable schema
          |-- Visible "View as Markdown" link
          |-- Accept: text/markdown negotiation
```

---

## Installation

1. Download the latest release zip
2. Go to **Plugins > Add New > Upload Plugin** in WordPress admin
3. Upload the zip and activate
4. Go to **RankReady** in the admin menu
5. Configure your settings:
   - **Settings tab**: OpenAI API key, post types, summary preferences
   - **LLM Optimization tab**: LLMs.txt, Markdown, Crawler Access settings
   - **Tools tab**: Bulk operations

## Requirements

- WordPress 6.2+
- PHP 7.4+
- OpenAI API key (only needed for AI Summary feature)

---

## Developer Filters

```php
// Force RankReady's llms.txt even if another plugin handles it
add_filter( 'rankready_force_llms_txt', '__return_true' );

// Disable schema injection for specific posts
add_filter( 'rankready_inject_schema', function( $inject, $post ) {
    if ( $post->ID === 123 ) return false;
    return $inject;
}, 10, 2 );

// Exclude specific posts from llms.txt programmatically
add_filter( 'rankready_exclude_from_llms', function( $exclude, $post ) {
    if ( $post->post_type === 'landing-page' ) return true;
    return $exclude;
}, 10, 2 );

// Hide the "View as Markdown" link on specific posts
add_filter( 'rankready_show_md_link', function( $show, $post ) {
    if ( $post->post_type === 'page' ) return false;
    return $show;
}, 10, 2 );
```

---

## Compatibility

Works alongside all major SEO plugins without conflicts:

- **Rank Math** — Respects noindex, skips llms.txt if RM module active, never modifies RM robots.txt rules
- **Yoast SEO** — Respects noindex, skips llms.txt if Yoast enables it
- **AIOSEO** — Respects noindex, skips llms.txt if AIOSEO active
- **SEOPress** — Respects noindex, skips llms.txt if SEOPress 9.5+

Works with all major page builders:

- **Elementor** — Strips all wrapper markup, dedicated Elementor widget included
- **Divi** — Strips et_pb_ wrappers from markdown output
- **WPBakery** — Strips vc_ and wpb_ wrappers
- **Beaver Builder** — Strips fl- wrappers
- **Gutenberg** — Strips block comments, native block included

---

## Security

- Nonce verification on all REST endpoints
- Capability checks (`manage_options`) on all admin actions
- Sanitized inputs on all settings
- Content-hash validation prevents unauthorized summary regeneration
- Bulk operations capped at 10,000 posts (memory safety)
- Race condition guards on concurrent bulk operations
- Rewrite rules scoped to exclude wp-admin, wp-content, wp-includes, wp-json
- Physical robots.txt modifications are append-only and clearly marked for clean removal

---

## License

GPL-2.0-or-later. See [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html).

---

Built by [POSIMYTH Innovations](https://posimyth.com) | [Plugin Page](https://posimyth.com/rankready/)
