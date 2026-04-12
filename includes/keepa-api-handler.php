<?php
defined('ABSPATH') || exit;

class WBI_Keepa_API_Handler {
    private $api_key;
    const API_BASE_URL = 'https://api.keepa.com/';
    const CACHE_EXPIRY = 6 * HOUR_IN_SECONDS;

    public function __construct() {
        $this->api_key = get_option('wbi_keepa_api_key');
    }

    public function fetch_product_data($isbn) {
        if (empty($this->api_key)) {
            return new WP_Error('keepa_api_key_missing', 'Keepa API key is not configured.');
        }

        $cache_key = 'wbi_keepa_' . md5($isbn);
        if ($cached = get_transient($cache_key)) {
            return $cached;
        }

        $response = wp_remote_get(add_query_arg([
            'key' => $this->api_key,
            'domain' => '1',
            'code' => sanitize_text_field($isbn),
            'stats' => '180'
        ], self::API_BASE_URL . 'product'), [
            'timeout' => 25,
            'user-agent' => 'WooBookImporter/1.7.2'
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($data['products'][0])) {
            set_transient($cache_key, [], self::CACHE_EXPIRY);
            return new WP_Error('keepa_not_found', 'No Amazon data for ISBN: ' . $isbn);
        }

        $result = [
            'current_price' => $data['products'][0]['stats']['current'][1] ?? null, // Price in cents
            'avg_price' => $data['products'][0]['stats']['avg'][1] ?? null,
            'sales_rank' => $data['products'][0]['salesRanks']['ALL'][0] ?? null,
            'last_updated' => time(),
            'product_type' => $data['products'][0]['productType'] ?? 'unknown'
        ];

        set_transient($cache_key, $result, self::CACHE_EXPIRY);
        return $result;
    }
}