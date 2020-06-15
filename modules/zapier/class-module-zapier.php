<?php
/**
 * CFTZ_Module_Zapier
 *
 * @package         Cf7_To_Zapier
 * @subpackage      CFTZ_Module_Zapier
 * @since           1.0.0
 *
 */

// If this file is called directly, call the cops.
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

if ( ! class_exists( 'CFTZ_Module_Zapier' ) ) {
    class CFTZ_Module_Zapier {

        /**
         * The Core object
         *
         * @since    1.0.0
         * @var      Cf7_To_Zapier    $core   The core class
         */
        private $core;

        /**
         * The Module Indentify
         *
         * @since    1.0.0
         */
        const MODULE_SLUG = 'zapier';

        /**
         * Define the core functionalities into plugin.
         *
         * @since    1.0.0
         * @param    Cf7_To_Zapier      $core   The Core object
         */
        public function __construct( Cf7_To_Zapier $core ) {
            $this->core = $core;
        }

        /**
         * Register all the hooks for this module
         *
         * @since    1.0.0
         * @access   private
         */
        private function define_hooks() {
            $this->core->add_action( 'ctz_trigger_webhook', array( $this, 'pull_the_trigger' ), 10, 2 );
        }

        /**
         * Send data to Zapier
         *
         * @since    1.0.0
         * @access   private
         */
        public function pull_the_trigger( array $data, $hook_url ) {
            $content_type = 'application/json';

            $blog_charset = get_option( 'blog_charset' );
            if ( ! empty( $blog_charset ) ) {
                $content_type .= '; charset=' . get_option( 'blog_charset' );
            }

            $args = array(
                'method'    => 'POST',
                'body'      => json_encode( $data ),
                'headers'   => array(
                    'Content-Type'  => $content_type,
                ),
            );

            /**
             * Filter: ctz_hook_url
             *
             * The 'ctz_hook_url' filter webhook URL so developers can use form
             * data or other information to change webhook URL.
             *
             * @since    2.1.4
             */
            $hook_url = apply_filters( 'ctz_hook_url', $hook_url, $data );

            /**
             * Filter: ctz_post_request_args
             *
             * The 'ctz_post_request_args' filter POST args so developers
             * can modify the request args if any service demands a particular header or body.
             *
             * @since    1.1.0
             */
            $result = wp_remote_post( $hook_url, apply_filters( 'ctz_post_request_args', $args ) );

            // If result is a WP Error, throw a Exception woth the message.
            if ( is_wp_error( $result ) ) {
                throw new Exception( $result->get_error_message() );
            }

            /**
             * Action: ctz_post_request_result
             *
             * You can perform a action with the result of the request.
             * By default we do nothing but you can throw a Exception in webhook errors.
             *
             * @since    1.4.0
             */
            do_action( 'ctz_post_request_result', $result, $hook_url );
        }

        /**
         * Run the module.
         *
         * @since    1.0.0
         */
        public function run() {
            $this->define_hooks();
        }
    }
}
