<?php
// This file is the same as the last stable version.
if ( ! defined( 'WPINC' ) ) {
    die;
}
class WBI_API_Handler {
    private $api_key;
    const API_BASE_URL = 'https://www.googleapis.com/books/v1/volumes';
    public function __construct() {
        $this->api_key = get_option( 'wbi_google_books_api_key' );
    }
    public function fetch_book_data( $isbn ) {
        if ( empty( $this->api_key ) ) { return new WP_Error('api_key_missing', 'Google Books API key is missing.'); }
        $isbn = preg_replace( '/[^0-9X]/i', '', $isbn );
        $request_url = add_query_arg(['q' => 'isbn:' . $isbn, 'key' => $this->api_key], self::API_BASE_URL);
        $response = wp_remote_get( $request_url, ['timeout' => 20] );
        if ( is_wp_error( $response ) ) { return new WP_Error('api_request_failed', 'API request failed: ' . $response->get_error_message());}
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );
        if ( empty($data['items']) ) { return new WP_Error('book_not_found', sprintf('No book found for ISBN: %s.', $isbn));}
        return $this->format_book_data( $data['items'][0], $isbn );
    }
    private function format_book_data( $item, $original_isbn ) {
        $volume_info = $item['volumeInfo'];
        return [
            'isbn' => $original_isbn, 'title' => sanitize_text_field($volume_info['title'] ?? 'No Title'),
            'authors' => implode(', ', $volume_info['authors'] ?? []), 'description' => wp_kses_post($volume_info['description'] ?? ''),
            'publisher' => sanitize_text_field($volume_info['publisher'] ?? ''), 'publishedDate' => sanitize_text_field($volume_info['publishedDate'] ?? ''),
            'imageLinks' => $volume_info['imageLinks'] ?? [], 'height' => sanitize_text_field($volume_info['dimensions']['height'] ?? ''),
            'width' => sanitize_text_field($volume_info['dimensions']['width'] ?? ''), 'thickness' => sanitize_text_field($volume_info['dimensions']['thickness'] ?? ''),
            'categories' => $volume_info['categories'] ?? [],
        ];
    }
}