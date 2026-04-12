<?php
defined('ABSPATH') || exit;

class WBI_API_Handler {
    private $api_key;
    const API_BASE_URL = 'https://www.googleapis.com/books/v1/volumes';

    public function __construct() {
        $this->api_key = get_option('wbi_google_books_api_key');
    }

    public function fetch_book_data($isbn) {
        if (empty($this->api_key)) {
            return new WP_Error('api_key_missing', 'Google Books API key is missing in settings.');
        }

        $clean_isbn = preg_replace('/[^0-9X]/i', '', $isbn);
        $response = wp_remote_get(add_query_arg([
            'q' => 'isbn:' . $clean_isbn,
            'key' => $this->api_key,
            'country' => 'US'
        ], self::API_BASE_URL), ['timeout' => 20]);

        if (is_wp_error($response)) {
            return new WP_Error('api_error', 'API request failed: ' . $response->get_error_message());
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($data['items'])) {
            return new WP_Error('no_book', 'No book found for ISBN: ' . $clean_isbn);
        }

        return $this->format_data($data['items'][0], $clean_isbn);
    }

    private function format_data($item, $isbn) {
        $info = $item['volumeInfo'];
        return [
            'isbn' => $isbn,
            'title' => sanitize_text_field($info['title'] ?? 'No Title'),
            'authors' => implode(', ', $info['authors'] ?? []),
            'description' => wp_kses_post($info['description'] ?? ''),
            'publisher' => sanitize_text_field($info['publisher'] ?? ''),
            'publishedDate' => sanitize_text_field($info['publishedDate'] ?? ''),
            'imageLinks' => $info['imageLinks'] ?? [],
            'categories' => $info['categories'] ?? [],
            'pageCount' => absint($info['pageCount'] ?? 0)
        ];
    }
}