<?php
/**
 * Plugin Name: Woo Book Importer
 * Description: Import books from Google Books + Keepa into WooCommerce
 * Version: 1.8.2
 * Author: Mark Phu
 * Text Domain: woo-book-importer
 */

defined('ABSPATH') || exit;

define('WBI_VERSION', '1.8.2');
define('WBI_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('WBI_PLUGIN_URL', plugin_dir_url(__FILE__));

class WBI_Core {
    public static function init() {
        if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            add_action('admin_notices', [__CLASS__, 'missing_woocommerce_notice']);
            return;
        }

        require_once WBI_PLUGIN_PATH . 'includes/api-handler.php';
        require_once WBI_PLUGIN_PATH . 'includes/keepa-api-handler.php';
        require_once WBI_PLUGIN_PATH . 'includes/product-importer.php';
        require_once WBI_PLUGIN_PATH . 'includes/admin-ui.php';

        new WBI_Admin_UI();
    }

    public static function missing_woocommerce_notice() {
        echo '<div class="notice notice-error"><p>Woo Book Importer requires WooCommerce to be installed and active.</p></div>';
    }

    public static function activate() {
        $dir = wp_upload_dir()['basedir'] . '/book-covers';
        if (!file_exists($dir)) {
            wp_mkdir_p($dir);
        }
    }
}

// Initialize
add_action('plugins_loaded', ['WBI_Core', 'init']);
register_activation_hook(__FILE__, ['WBI_Core', 'activate']);

// Settings
add_action('admin_init', function() {
    register_setting('wbi_options', 'wbi_google_books_api_key');
    register_setting('wbi_options', 'wbi_keepa_api_key');
});