<?php
/**
 * CFTZ_Module_ActionNetwork
 *
 * @package         Cf7_To_ActionNetwork
 * @subpackage      CFTZ_Module_ActionNetwork
 * @since           1.0.0
 *
 */

// If this file is called directly, call the cops.
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

if ( ! class_exists( 'CFTZ_Module_ActionNetwork' ) ) {
    class CFTZ_Module_ActionNetwork {

        /**
         * The Core object
         *
         * @since    1.0.0
         * @var      Cf7_To_ActionNetwork    $core   The core class
         */
        private $core;

        /**
         * The Module Indentify
         *
         * @since    1.0.0
         */
        const MODULE_SLUG = 'actionnetwork';

        /**
         * Define the core functionalities into plugin.
         *
         * @since    1.0.0
         * @param    Cf7_To_ActionNetwork      $core   The Core object
         */
        public function __construct( Cf7_To_ActionNetwork $core ) {
            $this->core = $core;
        }

        /**
         * Register all the hooks for this module
         *
         * @since    1.0.0
         * @access   private
         */
        private function define_hooks() {
            $this->core->add_action( 'ctz_trigger_actionnetwork', array( $this, 'pull_the_trigger' ), 10, 5 );
        }

        /**
         * Send data to ActionNetwork
         *
         * @since    1.0.0
         * @access   private
         */
        public function pull_the_trigger( array $data, $hook_url, $properties, $contact_form ) {
            /**
             * Filter: ctz_ignore_default_actionnetwork
             *
             * The 'ctz_ignore_default_actionnetwork' filter can be used to ignore
             * core request, if you want to trigger your own request.
             *
             * add_filter( 'ctz_ignore_default_actionnetwork', '__return_true' );
             *
             * @since    2.3.0
             */

            // Before modifying hook url logic
            if (stripos($hook_url, "actionnetwork.org/api/v2/events/") !== false) {
                $hook_url = rtrim($hook_url, '/') . '/attendances';
            } elseif (stripos($hook_url, "actionnetwork.org/api/v2/fundraising_pages/") !== false) {
                $hook_url = rtrim($hook_url, '/') . '/donations';
            } elseif (stripos($hook_url, "actionnetwork.org/api/v2/advocacy_campaigns/") !== false) {
                $hook_url = rtrim($hook_url, '/') . '/outreaches';
            } elseif (stripos($hook_url, "actionnetwork.org/api/v2/petitions/") !== false) {
                $hook_url = rtrim($hook_url, '/') . '/signatures';
            } elseif (stripos($hook_url, "actionnetwork.org/api/v2/forms/") !== false) {
                $hook_url = rtrim($hook_url, '/') . '/submissions';
            }

            if ( apply_filters( 'ctz_ignore_default_actionnetwork', false ) ) {
                return;
            }

            $args = array(
                'method'    => 'POST',
                'body'      => wp_json_encode( $data ),
                'headers'   => $this->create_headers($properties['custom_headers'] ?? ''),
            );

            /**
             * Filter: ctz_hook_url
             *
             * The 'ctz_hook_url' filter actionnetwork URL so developers can use form
             * data or other information to change actionnetwork URL.
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
            $result = wp_remote_post( $hook_url, apply_filters( 'ctz_post_request_args', $args, $properties, $contact_form ) );

            // If result is a WP Error, throw a Exception woth the message.
            if ( is_wp_error( $result ) ) {
                throw new Exception( esc_html( $result->get_error_message() ) );
            }

            /**
             * Action: ctz_post_request_result
             *
             * You can perform a action with the result of the request.
             * By default we do nothing but you can throw a Exception in actionnetwork errors.
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

        /**
         * Get headers to request.
         *
         * @since    2.3.0
         */
        public function create_headers($custom) {
            $headers = array( 'Content-Type'  => 'application/json' );
            $blog_charset = get_option( 'blog_charset' );
            if ( ! empty( $blog_charset ) ) {
                $headers['Content-Type'] .= '; charset=' . get_option( 'blog_charset' );
            }

            $custom = explode("\n", $custom);
            foreach ($custom as $header) {
                $header = explode(':', $header, 2);
                $header = array_map('trim', $header);

                if (count($header) === 2) {
                    $headers[ $header[0] ] = $header[1];
                }
            }

            return $headers;
        }
    }
}
