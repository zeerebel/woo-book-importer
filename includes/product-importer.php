<?php
// This is the final stable version from our debugging session.
if ( ! defined( 'WPINC' ) ) { die; }
class WBI_Product_Importer {
    public function import_book( $isbn ) { /* ... */ }
    private function product_exists( $isbn ) { /* ... */ }
    private function create_product( $book_data ) { /* ... */ }
    private function assign_product_categories($product_id, $categories) { /* ... */ }
    private function get_term_by_name_and_parent($name, $taxonomy, $parent_id) { /* ... */ }
    private function set_product_image( $product_id, $book_data ) { /* ... */ }
}
// NOTE: The full code for this file is the one from version 1.4.1 that fixed the category bug.
// It is included in the wbi_final_stable_141 document.