# AFC Partner Directory

A custom WordPress plugin built for the AFC Scholarship Fund that allows administrators to manage and display Partner Organizations through both the WordPress admin interface and a public REST API.

The project was developed as part of a take-home assessment and demonstrates WordPress plugin development, Gutenberg integration, REST API design, security best practices, and local development workflows.

## Features

### Partner Management
- Custom Post Type (partner)
- Partner Categories (partner_category taxonomy)
- Website URL field
- Logo upload using the WordPress Media Library
- Admin interface for creating, editing, and managing partners
- Role-based permissions with a custom manage_partners capability for administrators and editors

### Frontend Display
- Custom Gutenberg block (afc/partner-directory)
- Configurable number of columns
- Optional category filtering
- Server-side rendering to ensure content remains up-to-date

### REST API
GET /wp-json/custom/v1/partners

Supports:
- Pagination
- Category filtering
- Cached responses using WordPress transients
- Cache status headers (X-AFC-Cache: HIT/MISS)

### Security
- Nonce verification
- Capability checks
- Input sanitization
- Output escaping
- Autosave protection

---

## Local Development

### Prerequisites
- Docker Desktop
- DDEV v1.22+

### Setup

ddev start

ddev wp core download

ddev wp core install --url=https://afc-partners.ddev.site --title="AFC Partners" --admin_user=admin --admin_password=admin123 --admin_email=admin@example.com

ddev wp plugin activate afc-partner-directory

ddev launch

### Admin Access
- URL: https://afc-partners.ddev.site/wp-admin
- Username: admin
- Password: admin123

---

## Project Structure

afc-partner-directory.php
├── includes/
│   ├── class-cpt.php
│   ├── class-meta-fields.php
│   ├── class-rest-api.php
│   ├── class-block.php
│   └── class-permissions.php
│
├── blocks/
│   └── partner-directory/
│       ├── index.js
│       └── style.css
│
├── assets/
│   └── js/
│       └── admin.js
│
├── tests/
│   └── test-partner-api.php
│
├── phpunit.xml
├── composer.json
└── .github/workflows/
    └── ci.yml

---

## Architecture Notes

I intentionally kept the plugin lightweight and dependency-free while following standard WordPress development patterns.

### Custom Post Type + Taxonomy
Partner records are stored as a dedicated Custom Post Type, while categories are implemented as a WordPress taxonomy rather than a custom field. This provides native support for filtering, querying, and future extensibility.

### Server-Side Block Rendering
The Gutenberg block uses a render_callback rather than saving HTML into post content. This ensures that updates to partner records are reflected immediately wherever the block is used.

### REST API Caching
REST API responses are cached using WordPress transients for five minutes. Cache keys are generated from request parameters so that different filter and pagination combinations are cached independently. A custom response header is included to make cache behavior visible during testing:

X-AFC-Cache: HIT
X-AFC-Cache: MISS

### No Build Step
The Gutenberg block is implemented using the globally available wp.* APIs rather than a Node-based build pipeline. For a larger project I would likely introduce @wordpress/scripts, but for a single block this approach keeps setup simple and reduces dependencies.

### Role-Based Permissions
The plugin registers a custom manage_partners capability on activation. Administrators and Editors are granted this capability automatically. The REST API create endpoint is gated behind this capability and returns a 403 for unauthorized requests. The capability is cleanly removed on plugin deactivation.

---

## REST API

### Endpoint
GET /wp-json/custom/v1/partners

### Query Parameters

| Parameter | Description |
|-----------|-------------|
| category  | Filter by category slug |
| per_page  | Number of results per page (default 20, max 100) |
| page      | Page number (default 1) |

### Authentication
The read endpoint is public because partner information is intended for public display. If sensitive data were involved, I would add authentication using WordPress Application Passwords, JWT authentication, or a custom capability-based permission model.

---

## Testing

The plugin was manually tested using the following workflow:
- Plugin activation and deactivation
- Partner creation and editing
- Logo uploads
- Category assignment
- Meta field persistence
- Gutenberg block rendering
- REST API pagination
- REST API category filtering
- Cache behavior verification
- Gutenberg block confirmed live and rendering at /our-partners on the local development environment

### Automated Tests
PHPUnit tests cover:
- Route registration
- Successful API responses
- Response structure
- Published vs draft content
- Category filtering
- Pagination behavior
- Authorization checks
- URL sanitization

### Running Tests
1. Set up the WordPress test library
2. Set the WP_TESTS_DIR environment variable
3. Run: vendor/bin/phpunit

---

## Tradeoffs

### Why Use Transients?
Transients provide a caching solution that works in a default WordPress installation without requiring additional infrastructure. For a production environment with Redis or Memcached available, I would switch to the WordPress object cache APIs.

### Why a Public REST Endpoint?
The API only exposes public-facing partner information, making public access appropriate and simplifying frontend consumption.

### Why Avoid a Build Process?
The assessment requirements can be met without introducing Node.js tooling. This reduces setup complexity while still demonstrating Gutenberg integration.

---

## Production Deployment Notes

1. Server — A standard LEMP stack or managed WordPress host such as WP Engine, Kinsta, or Pantheon
2. Object caching — Add Redis or Memcached and replace transients with wp_cache_*
3. CDN — Serve partner logos via Cloudflare or BunnyCDN to reduce origin load
4. Plugin deployment — Via GitHub Actions: lint on every push, deploy to staging on merge to main via SSH and WP-CLI cache flush
5. Environment config — Use wp-config.php environment variables to separate credentials across environments

---

## Future Improvements

Given additional time, I would consider:
- Cache invalidation when partners are updated
- Search functionality
- Frontend sorting options
- Placeholder logo support
- Additional admin list table columns
- Expanded PHPUnit coverage
- End-to-end testing

---

## AI Usage Notes

### Tools Used
I used Claude (Anthropic) as my primary development partner throughout this project for architecture decisions, code generation, environment setup, and documentation. The build took approximately 2 hours end-to-end. My role was directing, reviewing, correcting, and verifying the output at each step.

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
