<?php
/**
 * VZ_Module_Zapier
 *
 * @package         Cf7_To_Zapier
 * @subpackage      VZ_Module_Zapier
 * @since           1.0.0
 *
 */

// If this file is called directly, call the cops.
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

if ( ! class_exists( 'VZ_Module_Zapier' ) ) {
    class VZ_Module_Zapier {

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
            $args = array(
                'method'    => 'POST',
                'body'      => json_encode( $data ),
            );

            wp_remote_post( $hook_url, $args );
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
