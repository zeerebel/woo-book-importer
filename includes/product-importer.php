<?php
defined('ABSPATH') || exit;

class WBI_Product_Importer {
    public function import_book($isbn) {
        if ($existing_id = $this->product_exists($isbn)) {
            return new WP_Error('duplicate', sprintf(
                'Product already exists. <a href="%s" target="_blank">Edit product</a>',
                get_edit_post_link($existing_id)
            ));
        }

        $book_data = (new WBI_API_Handler())->fetch_book_data($isbn);
        if (is_wp_error($book_data)) {
            return $book_data;
        }
        
        return $this->create_product($book_data);
    }

    private function create_product($book_data) {
        $product_id = wp_insert_post([
            'post_title' => $book_data['title'],
            'post_content' => $book_data['description'],
            'post_status' => 'draft',
            'post_type' => 'product'
        ], true);

        if (is_wp_error($product_id)) {
            return $product_id;
        }

        update_post_meta($product_id, '_sku', $book_data['isbn']);
        update_post_meta($product_id, '_wbi_imported', true);
        wp_set_object_terms($product_id, 'simple', 'product_type');
        update_post_meta($product_id, '_book_author', $book_data['authors']);
        update_post_meta($product_id, '_book_publisher', $book_data['publisher']);
        update_post_meta($product_id, '_book_publish_date', $book_data['publishedDate']);
        update_post_meta($product_id, '_book_page_count', $book_data['pageCount']);
        update_post_meta($product_id, '_visibility', 'visible');

        $this->set_price($product_id, $book_data['isbn']);
        $this->assign_categories($product_id, $book_data['categories']);
        $this->import_image($product_id, $book_data);

        return $product_id;
    }

    private function set_price($product_id, $isbn) {
        $price = 0.00; 
        $keepa_data_to_save = null;
        $keepa_api_key = get_option('wbi_keepa_api_key');

        if (!empty($keepa_api_key)) {
            $keepa_data = (new WBI_Keepa_API_Handler())->fetch_product_data($isbn);
            if (!is_wp_error($keepa_data) && !empty($keepa_data['current_price'])) {
                $price = $keepa_data['current_price'] / 100;
                $keepa_data_to_save = $keepa_data;
            }
        }
        
        update_post_meta($product_id, '_regular_price', $price);
        update_post_meta($product_id, '_price', $price);
        
        $stock_status = ($price > 0) ? 'instock' : 'outofstock';
        update_post_meta($product_id, '_stock_status', $stock_status);
        
        if ($keepa_data_to_save) {
            update_post_meta($product_id, '_keepa_data', $keepa_data_to_save);
        }
    }

    private function import_image($product_id, $book_data) {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        foreach (['extraLarge', 'large', 'medium', 'small', 'thumbnail', 'smallThumbnail'] as $size) {
            if (!empty($book_data['imageLinks'][$size])) {
                $url = esc_url_raw(str_replace('http://', 'https://', $book_data['imageLinks'][$size]));
                $image_id = media_sideload_image($url, $product_id, $book_data['title'], 'id');
                if (!is_wp_error($image_id)) {
                    set_post_thumbnail($product_id, $image_id);
                    return;
                }
            }
        }

        $ol_url = "https://covers.openlibrary.org/b/isbn/{$book_data['isbn']}-L.jpg?default=false";
        $response = wp_remote_get($ol_url);
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $image_id = media_sideload_image($ol_url, $product_id, $book_data['title'], 'id');
            if (!is_wp_error($image_id)) {
                set_post_thumbnail($product_id, $image_id);
            }
        }
    }

    private function assign_categories($product_id, $categories_from_api) {
        // Make sure our logging function exists before we use it
        if (!function_exists('log_isbn_debug')) {
            return; 
        }

        // DEBUG: Log the raw data we get from the API
        log_isbn_debug(['step' => 1, 'message' => 'Starting category assignment.', 'raw_api_categories' => $categories_from_api]);

        if (empty($categories_from_api)) {
            $categories_from_api = [];
        }

        $term_ids = [];
        // The 'Books' category is hardcoded to ensure it always exists.
        $category_paths = array_unique(array_merge(['Books'], $categories_from_api));
        
        // DEBUG: Log the combined and unique category paths we will process.
        log_isbn_debug(['step' => 2, 'message' => 'All category paths to be processed.', 'paths' => $category_paths]);

        foreach ($category_paths as $category_path) {
            // This replaces common separators with a standard '/' for hierarchy.
            $category_path_normalized = str_replace([' & ', ', '], '/', $category_path);
            $parent_id = 0; // Reset to top-level for each new path.
            
            // DEBUG: Log each path as we start processing it.
            log_isbn_debug(['step' => 3, 'message' => 'Processing a single category path.', 'original_path' => $category_path, 'normalized_path' => $category_path_normalized]);

            $category_parts = array_map('trim', explode('/', $category_path_normalized));

            foreach ($category_parts as $category_name) {
                if (empty($category_name)) continue;
                
                // DEBUG: Log the individual category part and the parent ID we are checking against.
                log_isbn_debug(['step' => 4, 'message' => 'Looking for or creating term.', 'term_name' => $category_name, 'parent_id' => $parent_id]);

                $term = term_exists($category_name, 'product_cat', $parent_id);

                if (is_array($term)) {
                    $term_id = $term['term_id'];
                } elseif ($term) {
                    $term_id = $term;
                } else {
                    $new_term = wp_insert_term($category_name, 'product_cat', ['parent' => $parent_id]);
                    if (is_wp_error($new__term)) {
                        // DEBUG: Log any errors during term creation. This is critical.
                        log_isbn_debug(['step' => 'ERROR', 'message' => 'Failed to insert term.', 'term_name' => $category_name, 'error' => $new_term->get_error_message()]);
                        $parent_id = 0;
                        continue 2; // Skip to the next category path on error
                    }
                    $term_id = $new_term['term_id'];
                }

                $term_ids[] = $term_id;
                $parent_id = $term_id; // The current term becomes the parent for the next part.
            }
        }

        // DEBUG: Log the final array of term IDs to be assigned to the product.
        log_isbn_debug(['step' => 5, 'message' => 'Final list of term IDs to be assigned.', 'term_ids' => array_unique($term_ids)]);

        if (!empty($term_ids)) {
            wp_set_object_terms($product_id, array_unique($term_ids), 'product_cat', false);
        }
    }

    private function product_exists($isbn) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_sku' AND meta_value = %s LIMIT 1",
            sanitize_text_field($isbn)
        ));
    }
}