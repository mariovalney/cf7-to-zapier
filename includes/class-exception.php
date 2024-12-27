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
         * Get the response body
         *
         * @since    4.0.0
         * @return   object     $result
         */
        public function get_response_body() {
            return wp_remote_retrieve_body( $this->get_result() );
        }

    }
}
