<?php
/**
 * Plugin Name:       Woo Book Importer
 * Plugin URI:        https://mongphu.com/
 * Description:       Import book data from Google Books API into WooCommerce products using an ISBN.
 * Version:           1.5.0
 * Author:            Mong Phu
 * Author URI:        https://mongphu.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       woo-book-importer
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

define( 'WBI_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'WBI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WBI_VERSION', '1.5.0' );

function run_woo_book_importer() {
    if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-error is-dismissible"><p><strong>Woo Book Importer</strong> requires WooCommerce to be installed and active to function.</p></div>';
        });
        return;
    }
    require_once WBI_PLUGIN_PATH . 'includes/api-handler.php';
    require_once WBI_PLUGIN_PATH . 'includes/product-importer.php';
    require_once WBI_PLUGIN_PATH . 'includes/admin-ui.php';
    require_once WBI_PLUGIN_PATH . 'includes/keepa-api-handler.php'; // New file for Keepa

    $plugin = new WBI_Admin_UI();
    $plugin->init();
}
add_action( 'plugins_loaded', 'run_woo_book_importer' );

function wbi_activate_plugin() {
    $upload_dir = wp_upload_dir();
    $book_covers_dir = $upload_dir['basedir'] . '/book-covers';
    if ( ! file_exists( $book_covers_dir ) ) {
        wp_mkdir_p( $book_covers_dir );
    }
}
register_activation_hook( __FILE__, 'wbi_activate_plugin' );