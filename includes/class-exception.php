<?php
/**
 * CFTZ_Exception
 *
 * @package         Cf7_To_Zapier
 * @since           4.0.0
 *
 */

// If this file is called directly, call the cops.
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

if ( ! class_exists( 'CFTZ_Exception' ) ) {
    class CFTZ_Exception extends Exception {

        /**
         * @var WP_Error
         */
        private $error = null;

        /**
         * Create the exception
         *
         * @since    4.0.0
         * @param    WP_Error      $error
         */
        public function __construct( WP_Error $error ) {
            $this->error = $error;
            parent::__construct( $this->error->get_error_message() );
        }

        /**
         * Get the error message
         *
         * @since    4.0.0
         * @return   string     $error
         */
        public function get_error() {
            return $this->error->get_error_message();
        }

        /**
         * Get the request
         *
         * @since    4.0.1
         * @return   object     $request
         */
        public function get_request() {
            $result = $this->error->get_error_data() ?? [];
            return $result['request'] ?? [];
        }

        /**
         * Get the result
         *
         * @since    4.0.0
         * @return   object     $result
         */
        public function get_result() {
            $result = $this->error->get_error_data() ?? [];
            return $result['result'] ?? $this->error;
        }

        /**
         * Get the request method
         *
         * @since    4.0.2
         * @return   array     $method
         */
        public function get_request_method() {
            return $this->get_request()['method'] ?? '(MAYBE) POST';
        }

        /**
         * Get the request headers
         *
         * @since    4.0.2
         * @return   array     $headers
         */
        public function get_request_headers() {
            return $this->get_request()['headers'] ?? [];
        }

        /**
         * Get the request body
         *
         * @since    4.0.2
         * @return   array|string     $body
         */
        public function get_request_body() {
            return $this->get_request()['body'] ?? [];
        }

        /**
         * Get the response code
         *
         * @since    4.0.0
         * @return   object     $result
         */
        public function get_response_code() {
            return wp_remote_retrieve_response_code( $this->get_result() );
        }

        /**
         * Get the response message
         *
         * @since    4.0.0
         * @return   object     $result
         */
        public function get_response_message() {
            return wp_remote_retrieve_response_message( $this->get_result() );
        }

        /**
         * Get the response headers
         *
         * @since    4.0.0
         * @return   object     $result
         */
        public function get_response_headers() {
            return wp_remote_retrieve_headers( $this->get_result() );
        }

        /**
         * Get the response body
         *
         * @since    4.0.0
         * @return   object     $result
         */
        public function get_response_body() {
            $body = wp_remote_retrieve_body( $this->get_result() );

            if ( empty( $body ) ) {
                $body = $this->error->get_error_data() ?? [];
                return $body['body'] ?? '';
            }

            return $body;
        }

    }
}
