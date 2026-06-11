# AFC Partner Directory

A custom WordPress plugin that registers and displays a directory of Partner Organizations for the AFC Scholarship Fund.

## Features

- Custom Post Type (partner) with admin UI for creating, editing, and managing partner records
- Per-partner fields: Name, Logo (media upload), Website URL, and Category (via taxonomy)
- Gutenberg block (afc/partner-directory) with configurable columns and category filtering
- REST API endpoint at GET /wp-json/custom/v1/partners with pagination, category filtering, and transient caching
- Local development environment via DDEV + Docker

## Local Development Setup

### Prerequisites

- Docker Desktop: https://www.docker.com/products/docker-desktop
- DDEV v1.22+: https://ddev.readthedocs.io/en/stable/users/install/ddev-installation/

### Steps

1. Clone the repository and cd into it
2. Run: ddev start
3. Run: ddev wp core download
4. Run: ddev wp core install --url=https://afc-partners.ddev.site --title="AFC Partners" --admin_user=admin --admin_password=admin123 --admin_email=admin@example.com
5. Run: ddev wp plugin activate afc-partner-directory
6. Run: ddev launch

WordPress admin: https://afc-partners.ddev.site/wp-admin
Username: admin | Password: admin123

## Architecture Overview

The plugin follows a class-based, single-responsibility structure:

- afc-partner-directory.php — Bootstrap: defines constants, loads classes
- includes/class-cpt.php — Registers partner CPT and partner_category taxonomy
- includes/class-meta-fields.php — Admin meta box: website URL and logo upload
- includes/class-rest-api.php — REST endpoint: /wp-json/custom/v1/partners
- includes/class-block.php — Gutenberg block registration and server-side render
- blocks/partner-directory/index.js — Block editor UI (vanilla wp.element, no build step)
- blocks/partner-directory/style.css — Frontend and editor styles
- assets/js/admin.js — WordPress media uploader integration
- .github/workflows/ci.yml — GitHub Actions: lint and deploy pipeline

### Key Design Decisions

No build step required — The Gutenberg block uses the globally available wp.* APIs via an IIFE rather than requiring a Node.js build pipeline. This keeps the setup simple and dependency-free.

Taxonomy for categories — Partner categories are implemented as a proper WordPress taxonomy (partner_category) rather than a freeform meta field. This enables WordPress-native filtering, archive pages, and REST API querying without custom query logic.

Server-side block rendering — The block uses render_callback instead of a save function. This ensures partner data is always fresh on page load.

Transient caching on the REST API — API responses are cached for 5 minutes. Cache keys are derived from query parameters so filtered and paginated requests are cached independently. A custom X-AFC-Cache: HIT/MISS header makes cache behavior observable.

## REST API

Endpoint: GET /wp-json/custom/v1/partners

Parameters:
- category (string) — Filter by partner_category slug
- per_page (integer, default 20) — Results per page, max 100
- page (integer, default 1) — Page number

Authentication and Rate Limiting:
The endpoint is currently public since partner data is intended for public display. For sensitive data, this would be switched to is_user_logged_in or a JWT/Application Password check. Rate limiting would be handled at the infrastructure layer via Nginx or Cloudflare.

## Key Tradeoffs

No npm/build toolchain — Avoids Node.js as a dev dependency. Tradeoff is slightly more verbose code and no JSX. For a larger block library a proper @wordpress/scripts setup would be appropriate.

Transients vs object cache — Transients work out of the box with no infrastructure requirements. In production with Redis or Memcached available, the caching layer would be upgraded to wp_cache_* for better performance.

Public REST endpoint — Appropriate for a public-facing partner directory. If partner data were sensitive, authentication would be added.

## What I Would Improve With More Time

- Cache invalidation on save — bust transient cache keys immediately via a save_post hook
- REST API authentication option — optional require_auth setting for sensitive deployments
- Unit tests — PHPUnit tests for REST API response structure and meta field sanitization
- Block improvements — search/filter UI in the frontend block and sorting options
- Logo fallback — placeholder SVG when no logo is uploaded
- Admin list columns — Website URL and Category columns in the partner list table

## Production Deployment Notes

1. Server — A standard LEMP stack or managed WordPress host such as WP Engine, Kinsta, or Pantheon
2. Object caching — Add Redis or Memcached and replace transients with wp_cache_*
3. CDN — Serve partner logos via Cloudflare or BunnyCDN to reduce origin load
4. Plugin deployment — Via GitHub Actions: lint on every push, deploy to staging on merge to main via SSH and WP-CLI cache flush
5. Environment config — Use wp-config.php environment variables to separate credentials across environments

## AI Usage Notes

### Tools Used
Claude (Anthropic) — used extensively throughout this project as a primary development partner for architecture decisions, code generation, environment setup, and documentation. The build took approximately 2 hours end-to-end. My role was directing, reviewing, correcting, and verifying the output at each step.

### How I Used AI
AI assisted with: initial plugin architecture scaffolding, boilerplate for WordPress hooks and CPT registration, REST API class structure, Gutenberg block IIFE pattern, Docker/DDEV environment setup, and README drafting.

### What I Changed or Reviewed
- REST API permission callback — AI initially suggested a custom permission function. I simplified this to __return_true since the endpoint serves public data, and added a comment explaining the reasoning.
- Nonce verification — AI generated the nonce check but used $_POST['nonce'] directly without unslashing. I corrected this to use wp_unslash() before passing to wp_verify_nonce().
- Logo removal logic — The initial AI-generated admin JS did not handle the Remove Logo button appearing dynamically after upload. I rewrote bindRemoveLogo to use event delegation so it works correctly on dynamically inserted elements.
- Cache key generation — AI suggested using the full query string as a cache key. I changed this to md5(serialize($params)) to keep keys under the 172-character WordPress transient key limit.

### How I Verified Correctness
- Activated the plugin in wp-admin and confirmed no PHP errors on activation
- Created test partner records with logo, URL, and category via the admin UI
- Verified meta fields saved and displayed correctly on re-edit
- Tested the REST API endpoint directly in the browser at /wp-json/custom/v1/partners
- Tested category filtering at /wp-json/custom/v1/partners?category=education
- Confirmed X-AFC-Cache MISS on first request and HIT on subsequent requests
- Added the Gutenberg block to a test page and confirmed it rendered partner cards correctly

### AI Limitations or Mistakes
The AI initially scaffolded the Gutenberg block using JSX and import statements, which would require a Node.js build step. This would not work in a no-build environment. I identified this, rejected that approach, and rewrote the block using the WordPress IIFE pattern with window.wp.* globals.

### Security and Maintainability Review
I specifically reviewed the save_meta function in class-meta-fields.php for the following:
- Nonce verification — confirmed wp_verify_nonce() is called before any data is processed
- Capability check — confirmed current_user_can('edit_post', $post_id) gates all saves
- Input sanitization — esc_url_raw() for the website URL field, absint() for the logo attachment ID
- Output escaping — all values output in render_meta_box() and render_block() use esc_url(), esc_html(), or esc_attr() as appropriate
- Autosave guard — confirmed DOING_AUTOSAVE check prevents unintended saves
