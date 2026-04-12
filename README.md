# Woo Book Importer

A WooCommerce plugin that turns any ISBN into a fully populated product listing. Enter an ISBN (or paste a batch of them), and the plugin pulls metadata from Google Books, pricing from Amazon via Keepa, and cover images from multiple fallback sources — then creates a ready-to-sell WooCommerce product in seconds.

Built for booksellers, resellers, and used-book e-commerce operators who need to catalog inventory fast without manual data entry.

## What It Does

- **Single or bulk ISBN import** — paste one ISBN or a list; each becomes a WooCommerce product with title, author, description, publisher, page count, and cover image.
- **Amazon pricing via Keepa** — automatically pulls current and average Amazon price, sales rank, and stores it as product meta. Prices are set on the product; if no Amazon listing exists, the product is marked out of stock.
- **Cover image sideloading with fallback chain** — tries six Google Books image sizes (extraLarge → smallThumbnail) before falling back to Open Library. Images are sideloaded into the WordPress media library, not hotlinked.
- **Hierarchical category auto-generation** — parses Google Books genre strings (e.g., "Fiction / Science Fiction / Space Opera") into nested WooCommerce product categories, creating any that don't exist.
- **Transient caching** — Keepa responses are cached for 6 hours to avoid redundant API calls.
- **Duplicate detection** — checks SKU (ISBN) before importing; surfaces a direct edit link if the product already exists.
- **Admin dashboard** — custom admin page with import stats (today / this week), a recent-imports table with cover thumbnails, and tabbed single/bulk import UI.
- **Product meta box** — adds a "Book Information" panel to the WooCommerce product editor showing Amazon pricing data at a glance.

## Technical Architecture

```
woo-book-importer.php          → Bootstrap, WooCommerce dependency check, activation hook
includes/
  api-handler.php              → Google Books API client (WBI_API_Handler)
  keepa-api-handler.php        → Keepa/Amazon pricing client (WBI_Keepa_API_Handler)
  product-importer.php         → Product creation, image sideloading, category logic (WBI_Product_Importer)
  admin-ui.php                 → Admin pages, AJAX endpoints, stats, meta box (WBI_Admin_UI)
assets/
  admin.css                    → Dashboard styles, responsive grid layout
  admin.js                     → jQuery AJAX for import, bulk import, live stat/table refresh
```

**Key implementation details:**

- Object-oriented PHP with four single-responsibility classes.
- All user input is sanitized (`sanitize_text_field`, `sanitize_textarea_field`, `wp_kses_post`).
- AJAX endpoints are nonce-verified and capability-checked (`manage_woocommerce`).
- Image URLs are forced to HTTPS before sideloading.
- API timeouts are set explicitly (20s Google Books, 25s Keepa) to prevent admin lockups.
- WordPress Transients API is used for Keepa response caching rather than a custom caching layer.
- Category hierarchy parsing normalizes ` & ` and `, ` separators to `/` before walking the tree.

## Requirements

- WordPress 5.8+
- WooCommerce 6.0+
- PHP 7.4+
- Google Books API key ([Google Cloud Console](https://console.cloud.google.com/apis/library/books.googleapis.com))
- Keepa API key (optional — [keepa.com](https://keepa.com/#!api))

## Installation

1. Download or clone this repository.
2. Upload the `woo-book-importer` folder to `wp-content/plugins/`.
3. Activate the plugin in **Plugins → Installed Plugins**.
4. Go to **Book Importer → Settings** and enter your Google Books API key (and optionally your Keepa API key).
5. Navigate to **Book Importer** in the admin sidebar and start importing.

## Usage

**Single import:** Enter an ISBN-10 or ISBN-13 in the input field and click Import. The plugin creates a draft WooCommerce product with all available metadata.

**Bulk import:** Switch to the Bulk Import tab, paste one ISBN per line, and click Import. Results are displayed inline with success/error status and edit links for each book.

Products are created as drafts so you can review pricing and categories before publishing.

## License

GPLv2 or later.

## Author

[Mark Phu](https://mongphu.com) · Creative Farm Design
