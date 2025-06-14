<?php
if ( ! defined( 'WPINC' ) ) {
    die;
}

class WBI_Admin_UI {

    public function init() {
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
        add_action( 'wp_ajax_wbi_import_isbn', [ $this, 'ajax_import_isbn' ] );
        add_action( 'wp_ajax_wbi_fetch_keepa_data', [ $this, 'ajax_fetch_keepa_data' ] );
        add_action( 'wp_ajax_wbi_update_product_data', [ $this, 'ajax_update_product_data' ] );
    }

    public function add_admin_menu() {
        add_menu_page('Book Importer', 'Book Importer', 'manage_woocommerce', 'woo-book-importer', [ $this, 'render_dashboard_page' ], 'dashicons-book-alt', 56 );
        add_submenu_page('woo-book-importer', 'Dashboard', 'Dashboard', 'manage_woocommerce', 'woo-book-importer', [ $this, 'render_dashboard_page' ]);
        add_submenu_page('woo-book-importer', 'Settings', 'Settings', 'manage_options', 'wbi-settings', [ $this, 'render_settings_page' ]);
    }

    public function register_settings() {
        register_setting('wbi_options_group', 'wbi_google_books_api_key', 'sanitize_text_field');
        register_setting('wbi_options_group', 'wbi_keepa_api_key', 'sanitize_text_field');
    }

    public function enqueue_admin_scripts( $hook ) {
        if ( strpos( $hook, 'woo-book-importer' ) === false && strpos($hook, 'wbi-settings') === false ) { return; }
        wp_enqueue_style( 'wbi-admin-css', WBI_PLUGIN_URL . 'assets/admin.css', [], WBI_VERSION );
        wp_enqueue_script( 'wbi-admin-js', WBI_PLUGIN_URL . 'assets/admin.js', ['jquery'], WBI_VERSION, true );
        wp_localize_script( 'wbi-admin-js', 'wbi_ajax', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'wbi_ajax_nonce' )
        ]);
    }

    public function ajax_import_isbn() {
        check_ajax_referer( 'wbi_ajax_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_woocommerce' ) ) { wp_send_json_error( ['message' => 'Permission denied.'] ); }
        $isbn = isset( $_POST['isbn'] ) ? sanitize_text_field( $_POST['isbn'] ) : '';
        if ( empty($isbn) ) { wp_send_json_error( ['message' => 'Please provide an ISBN.'] ); }
        $importer = new WBI_Product_Importer();
        $result = $importer->import_book( $isbn );
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( ['message' => $result->get_error_message()] );
        } else {
            $product = wc_get_product($result);
            wp_send_json_success(['message' => 'Successfully imported: ' . $product->get_name(), 'product_id' => $result, 'edit_url' => get_edit_post_link($result, 'raw')]);
        }
    }

    public function ajax_fetch_keepa_data() {
        check_ajax_referer( 'wbi_ajax_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_woocommerce' ) ) { wp_send_json_error( ['message' => 'Permission denied.'] ); }
        $isbn = sanitize_text_field($_POST['isbn']);
        $keepa_handler = new WBI_Keepa_API_Handler();
        $keepa_data = $keepa_handler->fetch_product_data($isbn);
        if(is_wp_error($keepa_data)) {
            wp_send_json_error(['message' => $keepa_data->get_error_message()]);
        } else {
            wp_send_json_success($keepa_data);
        }
    }

    public function ajax_update_product_data() {
        check_ajax_referer( 'wbi_ajax_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_woocommerce' ) ) { wp_send_json_error( ['message' => 'Permission denied.'] ); }
        $product_id = intval($_POST['product_id']);
        $price = sanitize_text_field($_POST['price']);
        $stock = intval($_POST['stock']);
        $product = wc_get_product($product_id);
        if(!$product) { wp_send_json_error(['message' => 'Product not found.']); }
        $product->set_regular_price($price);
        $product->set_price($price);
        $product->set_manage_stock(true);
        $product->set_stock_quantity($stock);
        $product->save();
        wp_send_json_success(['message' => 'Product updated!']);
    }

    public function render_dashboard_page() {
        if (!get_option('wbi_google_books_api_key')) {
            echo '<div class="wrap"><div class="notice notice-warning"><p><strong>API Key Required:</strong> Please save your Google Books API key on the <a href="'.admin_url('admin.php?page=wbi-settings').'">settings page</a> to begin.</p></div></div>';
            return;
        }
        ?>
        <div class="wrap wbi-wrap">
            <h1>Book Importer Dashboard</h1>
            <div class="wbi-card">
                 <h2><span class="dashicons dashicons-book-alt"></span> Import New Books</h2>
                 <div class="wbi-importer-area">
                    <form id="wbi-import-form">
                        <input type="text" id="wbi-isbn" placeholder="Enter a single ISBN to import..." required>
                        <button type="submit" class="button button-primary">Import</button>
                    </form>
                    <div id="wbi-single-results"></div>
                 </div>
            </div>

            <div class="wbi-card">
                <h2><span class="dashicons dashicons-products"></span> Manage Imported Books</h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 35%;">1. Book Details</th>
                            <th style="width: 30%;">2. Keepa Data (Amazon)</th>
                            <th style="width: 35%;">3. Your Store (WooCommerce)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $args = ['post_type' => 'product', 'posts_per_page' => 15, 'meta_query' => [['key' => '_wbi_isbn', 'compare' => 'EXISTS']]];
                        $books_query = new WP_Query($args);
                        if ($books_query->have_posts()) : while ($books_query->have_posts()) : $books_query->the_post();
                            $product = wc_get_product(get_the_ID());
                        ?>
                        <tr data-isbn="<?php echo esc_attr($product->get_sku()); ?>" data-product-id="<?php echo esc_attr($product->get_id()); ?>">
                            <td>
                                <strong><a href="<?php echo get_edit_post_link(); ?>"><?php the_title(); ?></a></strong>
                                <div class="wbi-meta">ISBN: <?php echo $product->get_sku(); ?></div>
                            </td>
                            <td class="wbi-keepa-col">
                                <button class="button wbi-fetch-keepa">Fetch Keepa Data</button>
                                <div class="wbi-keepa-results" style="display:none;"></div>
                            </td>
                            <td class="wbi-store-col">
                                <form class="wbi-update-form">
                                    <div class="wbi-form-row"><label>Your Price:</label><input type="text" class="wc_input_price" name="price" value="<?php echo $product->get_price(); ?>"></div>
                                    <div class="wbi-form-row"><label>Stock Qty:</label><input type="number" class="wbi_input_stock" name="stock" value="<?php echo $product->get_stock_quantity(); ?>" step="1"></div>
                                    <button type="submit" class="button button-primary">Save</button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; wp_reset_postdata(); else: ?>
                        <tr><td colspan="3">No imported books found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    public function render_settings_page() {
        ?>
        <div class="wrap wbi-wrap">
            <h1>Book Importer - Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('wbi_options_group'); ?>
                <div class="wbi-card">
                    <h2><span class="dashicons dashicons-admin-network"></span> Google Books API Key</h2>
                    <p>Get a free API key from the <a href="https://console.cloud.google.com/marketplace/product/google/books.googleapis.com" target="_blank">Google Cloud Console</a>.</p>
                    <table class="form-table"><tr><th scope="row"><label for="wbi_google_api_key">Google API Key</label></th><td><input type="text" id="wbi_google_api_key" name="wbi_google_books_api_key" value="<?php echo esc_attr(get_option('wbi_google_books_api_key')); ?>" class="regular-text"></td></tr></table>
                </div>
                <div class="wbi-card">
                    <h2><span class="dashicons dashicons-chart-bar"></span> Keepa API Key (Premium)</h2>
                    <p>Get a paid key from the <a href="https://keepa.com/#!api" target="_blank">Keepa API page</a>.</p>
                    <table class="form-table"><tr><th scope="row"><label for="wbi_keepa_api_key">Keepa API Key</label></th><td><input type="text" id="wbi_keepa_api_key" name="wbi_keepa_api_key" value="<?php echo esc_attr(get_option('wbi_keepa_api_key')); ?>" class="regular-text"></td></tr></table>
                </div>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
