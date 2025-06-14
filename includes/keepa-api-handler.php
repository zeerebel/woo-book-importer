<?php
if ( ! defined( 'WPINC' ) ) {
    die;
}

class WBI_Keepa_API_Handler {
    private $api_key;
    const API_BASE_URL = 'https://api.keepa.com/';

    public function __construct() {
        $this->api_key = get_option('wbi_keepa_api_key');
    }

    public function fetch_product_data($isbn) {
        if (empty($this->api_key)) {
            return new WP_Error('keepa_api_key_missing', 'Keepa API key is not set.');
        }

        $request_url = add_query_arg([
            'key'    => $this->api_key,
            'domain' => '1',
            'code'   => $isbn,
            'stats'  => '180'
        ], self::API_BASE_URL . 'product');
        
        $response = wp_remote_get($request_url, ['timeout' => 20]);
        if (is_wp_error($response)) { return new WP_Error('keepa_request_failed', 'Keepa API request failed.'); }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (empty($data['products'][0])) {
            return new WP_Error('keepa_not_found', 'Product not found on Keepa.');
        }

        $product_data = $data['products'][0];
        $stats = $product_data['stats'];

        return [
            'avg_new_price' => isset($stats['avg'][1]) ? '$' . number_format($stats['avg'][1] / 100, 2) : 'N/A',
            'current_new_price' => isset($stats['current'][1]) ? '$' . number_format($stats['current'][1] / 100, 2) : 'N/A',
            'sales_rank' => isset($product_data['salesRanks']['ALL'][0]) ? '#' . number_format($product_data['salesRanks']['ALL'][0]) : 'N/A',
        ];
    }
}