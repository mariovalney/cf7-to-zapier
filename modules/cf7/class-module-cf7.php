<?php
/**
 * CFTZ_Module_CF7
 *
 * @package         Cf7_To_Zapier
 * @subpackage      CFTZ_Module_CF7
 * @since           1.0.0
 *
 */

// If this file is called directly, call the cops.
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

if ( ! class_exists( 'CFTZ_Module_CF7' ) ) {
    class CFTZ_Module_CF7 {

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
        const MODULE_SLUG = 'cf7';

        /**
         * Metadata identifier
         *
         * @since    1.0.0
         */
        const METADATA = 'ctz_zapier';

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
            $this->core->add_filter( 'wpcf7_editor_panels', array( $this, 'wpcf7_editor_panels' ) );
            $this->core->add_action( 'wpcf7_save_contact_form', array( $this, 'wpcf7_save_contact_form' ) );
            $this->core->add_filter( 'wpcf7_contact_form_properties', array( $this, 'wpcf7_contact_form_properties' ), 10, 2 );
            $this->core->add_filter( 'wpcf7_skip_mail', array( $this, 'wpcf7_skip_mail' ), 10, 2 );
            $this->core->add_action( 'wpcf7_mail_sent', array( $this, 'wpcf7_mail_sent' ), 10, 1 );

            // Admin Hooks
            $this->core->add_action( 'admin_notices', array( $this, 'check_cf7_plugin' ) );
        }

        /**
         * Check Contact Form 7 Plugin is active
         * It's a dependency in this version
         *
         * @since    1.0.0
         * @access   private
         */
        public function check_cf7_plugin() {
            if ( ! current_user_can( 'activate_plugins' ) ) {
                return;
            }

            if ( is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) ) {
                return;
            }

            echo '<div class="notice notice-error is-dismissible">';
            echo '<p>' . sprintf( __( "You need to install/activate %s Contact Form 7%s plugin to use %s CF7 To Zapier %s", CFTZ_TEXTDOMAIN ), '<a href="http://contactform7.com/" target="_blank">', '</a>', '<strong>', '</strong>' );

            $screen = get_current_screen();
            if ( $screen->id == 'plugins' ) {
                echo '.</p></div>';
                return;
            }

            if ( file_exists( ABSPATH . PLUGINDIR . '/contact-form-7/wp-contact-form-7.php' ) ) {
                $url = 'plugins.php';
            } else {
                $url = 'plugin-install.php?tab=search&s=Contact+form+7';
            }

            echo '. <a href="' . admin_url( $url ) . '">' . __( "Do it now?", CFTZ_TEXTDOMAIN ) . '</a></p>';
            echo '</div>';
        }

        /**
         * Filter the 'wpcf7_editor_panels' to add necessary tabs
         *
         * @since    1.0.0
         * @param    array              $panels     Panels in CF7 Administration
         */
        public function wpcf7_editor_panels( $panels ) {
            $panels['zapier-panel'] = array(
                'title'     => __( 'Zapier', CFTZ_TEXTDOMAIN ),
                'callback'  => array( $this, 'zapier_panel_html' ),
            );

            return $panels;
        }

        /**
         * Add zapier panel HTML
         *
         * @since    1.0.0
         * @param    WPCF7_ContactForm  $contactform    Current ContactForm Obj
         */
        public function zapier_panel_html( WPCF7_ContactForm $contactform ) {
            require plugin_dir_path( __FILE__ ) . 'admin/zapier-panel-html.php';
        }

        /**
         * Action 'wpcf7_save_contact_form' to save properties do Contact Form Post
         *
         * @since    1.0.0
         * @param    WPCF7_ContactForm  $contactform    Current ContactForm Obj
         */
        public function wpcf7_save_contact_form( $contact_form ) {
            $new_properties = array();

            if ( isset( $_POST['ctz-zapier-activate'] ) && $_POST['ctz-zapier-activate'] == '1' ) {
                $new_properties[ 'activate' ] = '1';
            } else {
                $new_properties[ 'activate' ] = '0';
            }

            if ( isset( $_POST['ctz-zapier-hook-url'] ) ) {
                $new_properties[ 'hook_url' ] = esc_url_raw( $_POST['ctz-zapier-hook-url'] );
            }

            if ( isset( $_POST['ctz-zapier-send-mail'] ) && $_POST['ctz-zapier-send-mail'] == '1' ) {
                $new_properties[ 'send_mail' ] = '1';
            } else {
                $new_properties[ 'send_mail' ] = '0';
            }

            if ( isset( $_POST['ctz-special-mail-tags'] ) ) {
                $new_properties[ 'special_mail_tags' ] = sanitize_textarea_field( $_POST['ctz-special-mail-tags'] );
            }

            $properties = $contact_form->get_properties();
            $old_properties = $properties[ self::METADATA ];
            $properties[ self::METADATA ] = array_merge( $old_properties, $new_properties );
            $contact_form->set_properties( $properties );
        }

        /**
         * Filter the 'wpcf7_contact_form_properties' to add necessary properties
         *
         * @since    1.0.0
         * @param    array              $properties     ContactForm Obj Properties
         * @param    obj                $instance       ContactForm Obj Instance
         */
        public function wpcf7_contact_form_properties( $properties, $instance ) {
            if ( ! isset( $properties[ self::METADATA ] ) ) {
                $properties[ self::METADATA ] = array(
                    'activate'          => '0',
                    'hook_url'          => '',
                    'send_mail'         => '0',
                    'special_mail_tags' => '',
                );
            }

            return $properties;
        }

        /**
         * Filter the 'wpcf7_skip_mail' to skip if necessary
         *
         * @since    1.0.0
         * @param    bool               $skip_mail      true/false
         * @param    obj                $contact_form   ContactForm Obj
         */
        public function wpcf7_skip_mail( $skip_mail, $contact_form ) {
            $properties = $contact_form->prop( self::METADATA );

            if ( $this->can_submit_to_zapier( $contact_form ) ) {
                return empty( $properties['send_mail'] );
            }

            return $skip_mail;
        }

        /**
         * Action 'wpcf7_mail_sent' to send data to Zapier
         *
         * @since    1.0.0
         * @param    obj                $contact_form   ContactForm Obj
         */
        public function wpcf7_mail_sent( $contact_form ) {
            $properties = $contact_form->prop( self::METADATA );

            if ( ! $this->can_submit_to_zapier( $contact_form ) ) {
                return;
            }

            $smt_data = $this->get_data_from_special_mail_tags( $contact_form );
            $cf_data = $this->get_data_from_contact_form( $contact_form );

            $data = array_merge( $smt_data, $cf_data );

            // Try/Catch to support exception on request
            try {
                /**
                 * Action: ctz_trigger_webhook
                 *
                 * You can add your own actions to process the hook.
                 * We send it using CFTZ_Module_Zapier::pull_the_trigger().
                 *
                 * @since  1.0.0
                 */
                do_action( 'ctz_trigger_webhook', $data, $properties['hook_url'] );
            } catch (Exception $exception) {
                /**
                 * Filter: ctz_trigger_webhook_error_message
                 *
                 * The 'ctz_trigger_webhook_error_message' filter change the message in case of error.
                 * Default is CF7 error message, but you can access exception to create your own.
                 *
                 * You can ignore errors returning false:
                 * add_filter( 'ctz_trigger_webhook_error_message', '__return_empty_string' );
                 *
                 * @since 1.4.0
                 */
                $error_message =  apply_filters( 'ctz_trigger_webhook_error_message', $contact_form->message( 'mail_sent_ng' ), $exception );

                // If empty ignore
                if ( empty( $error_message ) ) return;

                // Set error and send error message
                $submission = WPCF7_Submission::get_instance();
                $submission->set_status( 'mail_failed' );
                $submission->set_response( $error_message );
            }
        }

        /**
         * Retrieve a array with data from Contact Form data
         *
         * @since    1.0.0
         * @param    obj                $contact_form   ContactForm Obj
         */
        private function get_data_from_contact_form( $contact_form ) {
            $data = array();

            $tags = $contact_form->scan_form_tags();

            foreach ( $tags as $tag ) {
                if ( empty( $tag->name ) ) continue;

                // Regular Tags
                $value = ( ! empty( $_POST[ $tag->name ] ) ) ? $_POST[ $tag->name ] : '';

                // Support to Pipes
                $pipes = $tag->pipes;
                if ( WPCF7_USE_PIPE && $pipes instanceof WPCF7_Pipes && ! $pipes->zero() ) {
                    if ( is_array( $value) ) {
                        $new_value = array();

                        foreach ( $value as $v ) {
                            $new_value[] = $pipes->do_pipe( wp_unslash( $v ) );
                        }

                        $value = $new_value;
                    } else {
                        $value = $pipes->do_pipe( wp_unslash( $value ) );
                    }
                }

                $data[ $tag->name ] = $value;
            }

            /**
             * You can filter data retrieved from Contact Form tags with 'ctz_get_data_from_contact_form'
             *
             * @param $data             Array 'field => data'
             * @param $contact_form     ContactForm obj from 'wpcf7_mail_sent' action
             */
            return apply_filters( 'ctz_get_data_from_contact_form', $data, $contact_form );
        }

        /**
         * Retrieve a array with data from Special Mail Tags
         *
         * @link https://contactform7.com/special-mail-tags
         *
         * @since    1.3.0
         * @param    obj                $contact_form   ContactForm Obj
         */
        private function get_data_from_special_mail_tags( $contact_form ) {
            $tags = [];
            $data = [];

            $properties = $contact_form->prop( self::METADATA );
            if ( ! empty( $properties['special_mail_tags'] ) ) {
                $tags = self::get_special_mail_tags_from_string( $properties['special_mail_tags'] );
            }

            foreach ( $tags as $key => $tag ) {
                $value = apply_filters( 'wpcf7_special_mail_tags', '', $tag, false );
                $data[ $key ] = $value;
            }

            /**
             * You can filter data retrieved from Special Mail Tags with 'ctz_get_data_from_special_mail_tags'
             *
             * @param $data             Array 'field => data'
             * @param $contact_form     ContactForm obj from 'wpcf7_mail_sent' action
             */
            return apply_filters( 'ctz_get_data_from_special_mail_tags', $data, $contact_form );
        }

        /**
         * Check we can submit a form to Zapier
         *
         * @since    1.0.0
         * @param    obj                $contact_form   ContactForm Obj
         */
        private function can_submit_to_zapier( $contact_form ) {
            $properties = $contact_form->prop( self::METADATA );

            if ( empty( $properties ) || empty( $properties['activate'] ) || empty( $properties['hook_url'] ) ) {
                return false;
            }

            return true;
        }

        /**
         * Special Mail Tags from a configuration string
         *
         * @since    1.3.1
         * @param    string     $string
         * @return   array      $data       Array { key => tag }
         */
        public static function get_special_mail_tags_from_string( $string ) {
            $data = [];
            $tags = [];

            preg_match_all( '/\[[^\]]*]/', $string, $tags );
            $tags = ( ! empty( $tags[0] ) ) ? $tags[0] : $tags;

            foreach ( $tags as $tag_data ) {
                if ( ! is_string( $tag_data ) || empty( $tag_data ) ) continue;

                $tag_data = substr( $tag_data, 1, -1 );
                $tag_data = explode( ' ', $tag_data );

                if ( empty( $tag_data[0] ) ) continue;

                $tag = $tag_data[0];
                $key = ( ! empty( $tag_data[1] ) ) ? $tag_data[1] : $tag;

                if ( empty( $key ) ) continue;

                $data[ $key ] = $tag;
            }

            return $data;
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
