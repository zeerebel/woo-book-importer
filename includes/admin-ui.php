<?php
defined('ABSPATH') || exit;

class WBI_Admin_UI {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu_pages']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_wbi_import_book', [$this, 'ajax_import_book']);
        add_action('wp_ajax_wbi_bulk_import_books', [$this, 'ajax_bulk_import_books']);
        add_action('wp_ajax_wbi_get_stats', [$this, 'ajax_get_stats']);
        add_action('wp_ajax_wbi_refresh_books_table', [$this, 'ajax_refresh_books_table']);
        add_action('add_meta_boxes', [$this, 'add_book_meta_box']);
    }

    public function add_menu_pages() {
        add_menu_page(
            'Book Importer',
            'Book Importer',
            'manage_woocommerce',
            'wbi-dashboard',
            [$this, 'render_dashboard'],
            'dashicons-book-alt',
            56
        );

        add_submenu_page(
            'wbi-dashboard',
            'Settings',
            'Settings',
            'manage_options',
            'wbi-settings',
            [$this, 'render_settings']
        );
    }

    public function enqueue_assets($hook) {
        if (strpos($hook, 'wbi-') === false) return;
        
        wp_enqueue_style('wbi-admin-css', WBI_PLUGIN_URL . 'assets/admin.css', [], WBI_VERSION);
        
        wp_enqueue_script('wbi-admin-js', WBI_PLUGIN_URL . 'assets/admin.js', ['jquery'], WBI_VERSION, true);

        wp_localize_script('wbi-admin-js', 'wbi_vars', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wbi-import-nonce'),
            'i18n' => [
                'importing' => __('Importing...', 'woo-book-importer'),
                'complete' => __('Complete!', 'woo-book-importer'),
                'processing_bulk' => __('Processing bulk import, please wait...', 'woo-book-importer'),
            ]
        ]);
    }

    public function render_dashboard() {
        ?>
        <div class="wrap wbi-dashboard">
            <h1 class="wp-heading-inline"><?php _e('Book Importer', 'woo-book-importer'); ?></h1>
            <div class="wbi-import-section">
                <div class="wbi-import-box">
                    <h2><?php _e('Import Books', 'woo-book-importer'); ?></h2>
                    <div class="wbi-tabs">
                        <button class="tab-button active" data-tab="single"><?php _e('Single Import', 'woo-book-importer'); ?></button>
                        <button class="tab-button" data-tab="bulk"><?php _e('Bulk Import', 'woo-book-importer'); ?></button>
                    </div>
                    <div id="wbi-single-import" class="wbi-tab-content active">
                        <input type="text" id="wbi-isbn-input" placeholder="<?php _e('Enter ISBN...', 'woo-book-importer'); ?>" class="regular-text">
                        <button id="wbi-import-btn" class="button button-primary"><?php _e('Import Book', 'woo-book-importer'); ?></button>
                        <div id="wbi-import-result"></div>
                    </div>
                    <div id="wbi-bulk-import" class="wbi-tab-content">
                        <textarea id="wbi-bulk-isbns" placeholder="<?php _e('Enter one ISBN per line...', 'woo-book-importer'); ?>" rows="5" class="large-text"></textarea>
                        <button id="wbi-bulk-import-btn" class="button button-primary"><?php _e('Import Books', 'woo-book-importer'); ?></button>
                        <div id="wbi-bulk-results"></div>
                    </div>
                </div>
                <div class="wbi-stats-box">
                    <h3><?php _e('Import Stats', 'woo-book-importer'); ?></h3>
                    <div class="wbi-stats-grid">
                        <div class="wbi-stat-card">
                            <span class="stat-value"><?php echo $this->count_imports_since('today'); ?></span>
                            <span class="stat-label"><?php _e('Today', 'woo-book-importer'); ?></span>
                        </div>
                        <div class="wbi-stat-card">
                            <span class="stat-value"><?php echo $this->count_imports_since('week'); ?></span>
                            <span class="stat-label"><?php _e('This Week', 'woo-book-importer'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="wbi-imported-books">
                <h2 class="wp-heading-inline"><?php _e('Recently Imported Books', 'woo-book-importer'); ?></h2>
                <div id="wbi-books-table-wrapper">
                    <?php $this->render_books_table(); ?>
                </div>
            </div>
            <div class="wbi-support-box">
                <h3><?php _e('Support This Plugin', 'woo-book-importer'); ?></h3>
                <p><?php _e('If you find this plugin useful, please consider supporting its development:', 'woo-book-importer'); ?></p>
                <div class="wbi-donation-buttons">
                    <a href="https://www.paypal.com/donate/?hosted_button_id=83Z86HGGSE2PU" target="_blank" class="button">
                        <span class="dashicons dashicons-money-alt"></span> <?php _e('Donate via PayPal', 'woo-book-importer'); ?>
                    </a>
                    <a href="https://www.buymeacoffee.com/fusionmma" target="_blank" class="button">
                        <span class="dashicons dashicons-coffee"></span> <?php _e('Buy Me a Coffee', 'woo-book-importer'); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
    }

    public function render_settings() {
        ?>
        <div class="wrap">
            <h1><?php _e('Book Importer Settings', 'woo-book-importer'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('wbi_options'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="wbi_google_books_api_key"><?php _e('Google Books API Key', 'woo-book-importer'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="wbi_google_books_api_key" id="wbi_google_books_api_key" value="<?php echo esc_attr(get_option('wbi_google_books_api_key')); ?>" class="regular-text">
                            <p class="description">
                                <?php _e('Get your API key from the', 'woo-book-importer'); ?>
                                <a href="https://console.cloud.google.com/apis/library/books.googleapis.com" target="_blank"><?php _e('Google Cloud Console', 'woo-book-importer'); ?></a>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="wbi_keepa_api_key"><?php _e('Keepa API Key', 'woo-book-importer'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="wbi_keepa_api_key" id="wbi_keepa_api_key" value="<?php echo esc_attr(get_option('wbi_keepa_api_key')); ?>" class="regular-text">
                            <p class="description">
                                <?php _e('Get your key from', 'woo-book-importer'); ?>
                                <a href="https://keepa.com/#!api" target="_blank"><?php _e('Keepa API', 'woo-book-importer'); ?></a>
                            </p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function add_book_meta_box() {
        add_meta_box('wbi_book_data', __('Book Information', 'woo-book-importer'), [$this, 'render_book_meta_box'], 'product', 'normal', 'high');
    }

    public function render_book_meta_box($post) {
        $keepa_data = get_post_meta($post->ID, '_keepa_data', true);
        ?>
        <div class="wbi-meta-box">
            <div class="wbi-meta-row">
                <div class="wbi-meta-label"><?php _e('Amazon Data:', 'woo-book-importer'); ?></div>
                <div class="wbi-meta-value">
                    <?php if (!empty($keepa_data) && is_array($keepa_data)) : ?>
                        <ul>
                            <li><?php _e('Current Price:', 'woo-book-importer'); ?> <?php echo isset($keepa_data['current_price']) ? wc_price($keepa_data['current_price'] / 100) : 'N/A'; ?></li>
                            <li><?php _e('Average Price:', 'woo-book-importer'); ?> <?php echo isset($keepa_data['avg_price']) ? wc_price($keepa_data['avg_price'] / 100) : 'N/A'; ?></li>
                            <li><?php _e('Sales Rank:', 'woo-book-importer'); ?> #<?php echo isset($keepa_data['sales_rank']) ? number_format($keepa_data['sales_rank']) : 'N/A'; ?></li>
                            <li><?php _e('Last Updated:', 'woo-book-importer'); ?> <?php echo isset($keepa_data['last_updated']) ? date_i18n(get_option('date_format'), $keepa_data['last_updated']) : 'N/A'; ?></li>
                        </ul>
                    <?php else : ?>
                        <p><?php _e('No Amazon pricing data available for this product.', 'woo-book-importer'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }

    public function ajax_import_book() {
        check_ajax_referer('wbi-import-nonce', 'nonce');
        if (!current_user_can('manage_woocommerce')) { wp_send_json_error(['message' => __('Permission denied', 'woo-book-importer')]); }
        $isbn = sanitize_text_field($_POST['isbn'] ?? '');
        if (empty($isbn)) { wp_send_json_error(['message' => __('ISBN is required', 'woo-book-importer')]); }
        $result = (new WBI_Product_Importer())->import_book($isbn);
        if (is_wp_error($result)) { wp_send_json_error(['message' => $result->get_error_message()]); }
        wp_send_json_success(['message' => __('Book imported successfully!', 'woo-book-importer'), 'edit_link' => get_edit_post_link($result)]);
    }
    
    public function ajax_bulk_import_books() {
        check_ajax_referer('wbi-import-nonce', 'nonce');
        if (!current_user_can('manage_woocommerce')) { wp_send_json_error(['message' => __('Permission denied', 'woo-book-importer')]); }
        $isbns_raw = sanitize_textarea_field($_POST['isbns'] ?? '');
        if (empty($isbns_raw)) { wp_send_json_error(['message' => __('No ISBNs provided.', 'woo-book-importer')]); }
        $isbns = array_filter(array_map('trim', explode("\n", $isbns_raw)));
        $results = [];
        $importer = new WBI_Product_Importer();
        foreach ($isbns as $isbn) {
            if (empty($isbn)) continue;
            $result = $importer->import_book($isbn);
            if (is_wp_error($result)) {
                $results[] = "<strong>ISBN: {$isbn}</strong> &mdash; Error: " . $result->get_error_message();
            } else {
                $edit_link = get_edit_post_link($result, 'raw');
                $results[] = "<strong>ISBN: {$isbn}</strong> &mdash; Success! <a href='{$edit_link}' target='_blank'>Edit Product</a>";
            }
        }
        wp_send_json_success($results);
    }
    
    public function ajax_get_stats() {
        check_ajax_referer('wbi-import-nonce', 'nonce');
        wp_send_json_success(['today' => $this->count_imports_since('today'), 'week' => $this->count_imports_since('week')]);
    }

    private function count_imports_since($period) {
        global $wpdb;
        $date = new DateTime('now', wp_timezone()); 

        if ($period === 'today') {
            $date->setTime(0, 0, 0);
        } elseif ($period === 'week') {
            $date->modify('monday this week')->setTime(0, 0, 0);
        }
        
        return $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $wpdb->posts p JOIN $wpdb->postmeta pm ON p.ID = pm.post_id WHERE p.post_type = 'product' AND pm.meta_key = '_wbi_imported' AND p.post_date >= %s", $date->format('Y-m-d H:i:s')));
    }

    private function render_books_table() {
        $books = new WP_Query(['post_type' => 'product', 'posts_per_page' => 10, 'orderby' => 'date', 'order' => 'DESC', 'meta_query' => [['key' => '_wbi_imported', 'compare' => 'EXISTS']]]);
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead><tr><th><?php _e('Cover', 'woo-book-importer'); ?></th><th><?php _e('Title', 'woo-book-importer'); ?></th><th><?php _e('ISBN (SKU)', 'woo-book-importer'); ?></th><th><?php _e('Price', 'woo-book-importer'); ?></th><th><?php _e('Date Added', 'woo-book-importer'); ?></th></tr></thead>
            <tbody>
                <?php if ($books->have_posts()) : while ($books->have_posts()) : $books->the_post(); $product = wc_get_product(get_the_ID()); ?>
                    <tr><td><?php echo $product->get_image('thumbnail'); ?></td><td><strong><a href="<?php echo get_edit_post_link(); ?>"><?php the_title(); ?></a></strong></td><td><?php echo esc_html($product->get_sku()); ?></td><td><?php echo $product->get_price_html() ?: '—'; ?></td><td><?php echo get_the_date(); ?></td></tr>
                <?php endwhile; else : ?>
                    <tr><td colspan="5"><?php _e('No books imported yet', 'woo-book-importer'); ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
        wp_reset_postdata();
    }

    public function ajax_refresh_books_table() {
        check_ajax_referer('wbi-import-nonce', 'nonce');
        if (!current_user_can('manage_woocommerce')) { wp_die(__('Permission denied', 'woo-book-importer')); }
        $this->render_books_table();
        wp_die();
    }
}