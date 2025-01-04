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
            $this->core->add_action( 'ctz_trigger_webhook', array( $this, 'pull_the_trigger' ), 10, 5 );
        }

        /**
         * Send data to Zapier
         *
         * @since    1.0.0
         * @access   private
         */
        public function pull_the_trigger( array $data, $hook_url, $properties, $contact_form ) {
            /**
             * Filter: ctz_ignore_default_webhook
             *
             * The 'ctz_ignore_default_webhook' filter can be used to ignore
             * core request, if you want to trigger your own request.
             *
             * add_filter( 'ctz_ignore_default_webhook', '__return_true' );
             *
             * @since    2.3.0
             */
            if ( apply_filters( 'ctz_ignore_default_webhook', false ) ) {
                return;
            }

            $body = json_encode( $data );
            $is_json = true;

            if ( ! empty( $properties['custom_body'] ) ) {
                $body = $properties['custom_body'];

                foreach ( $data as $key => $value ) {
                    $value = json_encode( $value );
                    $value = preg_replace('/^"(.*)"$/', '$1', $value);

                    $body = str_replace( '[' . $key . ']', $value, $body );
                }

                if ( json_decode( $body ) === null ) {
                    $is_json = false;
                }
            }

            // Prepare REQUEST
            $args = array(
                'redirection' => 10,
                'timeout'     => 30,
                'method'      => $properties['custom_method'] ?? 'POST',
                'body'        => $body,
                'headers'     => $this->create_headers( $properties['custom_headers'] ?? '', $is_json ),
            );

            // Check is valid GET
            if ( ! empty( $properties['custom_method'] ) && $properties['custom_method'] === 'GET') {
                if ( ! $is_json ) {
                    $error = new WP_Error();
                    $error->add( '0', __( 'Webhook has method GET but body is not a JSON to be passed as query params.', 'cf7-to-zapier' ), [ 'request' => $args ] );
                    throw new CFTZ_Exception( $error );
                }

                $body = json_decode( $body, true );
                $args['body'] = $body;
            }

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
            $args = apply_filters( 'ctz_post_request_args', $args, $properties, $contact_form, $hook_url );
            $result = wp_remote_request( $hook_url, $args );

            /**
             * Action: ctz_post_request_result
             *
             * You can perform a action with the result of the request.
             * By default we will thrown a CFTZ_Exception in webhook errors to send a notification.
             *
             * @since    1.4.0
             */
            do_action( 'ctz_post_request_result', $result, $hook_url );

            /**
             * Filter: ctz_post_request_ignore_errors
             *
             * The 'ctz_post_request_ignore_errors' filter can be used to ignore core error handler (notifications and success statuses).
             *
             * add_filter( 'ctz_post_request_ignore_errors', '__return_true' );
             *
             * @since    4.0.1
             */
            if ( apply_filters( 'ctz_post_request_ignore_errors', false, $hook_url, $result, $contact_form ) ) {
                return;
            }

            // If result is a WP Error, throw a Exception with the message.
            if ( is_wp_error( $result ) ) {
                $result->add_data( [ 'request' => $args ] );
                throw new CFTZ_Exception( $result );
            }

            // Check the accepted code status for webhook
            $code = wp_remote_retrieve_response_code( $result );
            if ( ! in_array( $code, $properties['accepted_statuses'] ) ) {
                $error = new WP_Error();
                $error->add( $code, __( 'Webhook returned a error code.', 'cf7-to-zapier' ), [ 'request' => $args, 'result' => $result ] );
                throw new CFTZ_Exception( $error );
            }
        }

        /**
         * Run the module.
         *
         * @since    1.0.0
         */
        public function run() {
            $this->define_hooks();
        }

        /**
         * Get headers to request.
         *
         * @since    2.3.0
         */
        public function create_headers( $custom, $is_json = true ) {
            $headers = [ 'Content-Type'  => $is_json ? 'application/json' : 'text/plain' ];

            $blog_charset = get_option( 'blog_charset' );
            if ( ! empty( $blog_charset ) ) {
                $headers['Content-Type'] .= '; charset=' . get_option( 'blog_charset' );
            }

            $custom = explode( "\n", $custom );
            foreach ( $custom as $header ) {
                $header = explode( ':', $header, 2 );
                $header = array_map( 'trim', $header );

                if ( count( $header ) === 2 ) {
                    $headers[ $header[0] ] = $header[1];
                }
            }

            return $headers;
        }
    }
}
