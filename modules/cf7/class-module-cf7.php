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
            $this->core->add_filter( 'wpcf7_editor_panels', [ $this, 'wpcf7_editor_panels' ] );
            $this->core->add_action( 'wpcf7_save_contact_form', [ $this, 'wpcf7_save_contact_form' ] );
            $this->core->add_filter( 'wpcf7_skip_mail', [ $this, 'wpcf7_skip_mail' ], 10, 2 );
            $this->core->add_action( 'wpcf7_mail_sent', [ $this, 'wpcf7_mail_sent' ], 10, 1 );

            $this->core->add_filter( 'wpcf7_contact_form_properties', array( $this, 'wpcf7_contact_form_properties' ), 10, 2 );
            $this->core->add_filter( 'wpcf7_pre_construct_contact_form_properties', array( $this, 'wpcf7_contact_form_properties' ), 10, 2 );

            // Admin Hooks
            $this->core->add_action( 'admin_notices', [ $this, 'check_cf7_plugin' ] );
            $this->core->add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );
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
            echo '<p>' . sprintf( __( "You need to install/activate %s Contact Form 7%s plugin to use %s CF7 to Webhook %s", 'cf7-to-zapier' ), '<a href="http://contactform7.com/" target="_blank">', '</a>', '<strong>', '</strong>' );

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

            echo '. <a href="' . admin_url( $url ) . '">' . __( "Do it now?", "cf7-to-zapier" ) . '</a></p>';
            echo '</div>';
        }

        /**
         * Action: 'admin_enqueue_scripts'
         * Add admin scripts like.
         *
         * @see contact-form-7/admin/admin.php
         *
         * @return void
         */
        public function admin_enqueue_scripts( $page ) {
            if ( false === strpos( $page, 'wpcf7' ) ) {
                return;
            }

            $version = ctz_is_developing() ? uniqid() : CFTZ_VERSION;

            wp_enqueue_style( 'ctz-select2-style', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.9/css/select2.min.css', [], $version );
            wp_enqueue_style( 'ctz-admin-style', CFTZ_PLUGIN_URL . '/modules/cf7/admin/assets/admin.css', [ 'ctz-select2-style' ], $version );

            wp_enqueue_script( 'ctz-select2-script', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.9/js/select2.min.js', [ 'jquery' ], $version );
            wp_enqueue_script( 'ctz-admin-script', CFTZ_PLUGIN_URL . '/modules/cf7/admin/assets/admin.js', [ 'ctz-select2-script' ], $version );

            // For developers: in your enviroment make "/docs" point to "/cf7-to-webhook-valney-dev"
            $templates_url = ctz_is_developing() ? home_url( '/cf7-to-webhook-valney-dev/templates.json' ) : 'https://cf7-to-webhook.valney.dev/templates.json';
            wp_localize_script( 'ctz-admin-script', 'CTZ_ADMIN', array(
                'templates_url' => $templates_url,
                'groups'        => array(
                    'default' => __( 'Default', 'cf7-to-zapier' )
                ),
                'messages'      => array(
                    'open_docs'         => __( 'This template has documentation. Want to open (another tab)?', 'cf7-to-zapier' ),
                    'confirm_all'       => __( 'This action will replace "Headers" and "Body" and remove "Special Mail Tags".', 'cf7-to-zapier' ),
                    'confirm_body'      => __( 'This action will replace "Body" and remove "Special Mail Tags" and "Headers".', 'cf7-to-zapier' ),
                    'save_to_preview'   => __( 'Save to load preview.', 'cf7-to-zapier' ),
                    'btn_no'            => __( 'No', 'cf7-to-zapier' ),
                    'btn_yes'           => __( 'Yes', 'cf7-to-zapier' ),
                    'btn_cancel'        => __( 'Cancel', 'cf7-to-zapier' ),
                    'btn_continue'      => __( 'Continue', 'cf7-to-zapier' ),
                    'choose_template'   => __( 'Choose a template', 'cf7-to-zapier' ),
                ),
            ) );
        }

        /**
         * Filter the 'wpcf7_editor_panels' to add necessary tabs
         *
         * @since    1.0.0
         * @param    array              $panels     Panels in CF7 Administration
         */
        public function wpcf7_editor_panels( $panels ) {
            $panels['webhook-panel'] = array(
                'title'     => __( 'Webhook', 'cf7-to-zapier' ),
                'callback'  => [ $this, 'webhook_panel_html' ],
            );

            return $panels;
        }

        /**
         * Add zapier panel HTML
         *
         * @since    1.0.0
         * @param    WPCF7_ContactForm  $contactform    Current ContactForm Obj
         */
        public function webhook_panel_html( WPCF7_ContactForm $contactform ) {
            require plugin_dir_path( __FILE__ ) . 'admin/webhook-panel-html.php';
        }

        /**
         * Action 'wpcf7_save_contact_form' to save properties do Contact Form Post
         *
         * @since    1.0.0
         * @param    WPCF7_ContactForm  $contactform    Current ContactForm Obj
         */
        public function wpcf7_save_contact_form( $contact_form ) {
            $props = static::get_properties();
            $properties = static::get_form_properties( $contact_form );

            foreach ( $props as $prop ) {
                $key = $prop['key'];
                $name = 'ctz-webhook-' . $key;

                $value = ( isset( $_POST[ $name ] ) ) ? $_POST[ $name ] : null;

                // Checkbox
                if ( $prop['type'] === 'checkbox' ) {
                    $properties[ $key ] = ( $value === '1' ) ? '1' : '0';
                    continue;
                }

                // Textarea
                if ( $prop['type'] === 'textarea' ) {
                    $properties[ $key ] = sanitize_textarea_field( $value );
                    continue;
                }

                // JSON
                if ( $prop['type'] === 'json' ) {
                    $properties[ $key ] = stripslashes( sanitize_textarea_field( $value ) );
                    continue;
                }

                // Hook Urls
                if ( $prop['type'] === 'hookurl' ) {
                    $properties[ $key ] = array_filter( array_map( function( $hook_url ) {
                        $placeholders = self::get_hook_url_placeholders( $hook_url );

                        foreach ( $placeholders as $key => $placeholder ) {
                            $hook_url = str_replace( $placeholder, '_____' . $key . '_____', $hook_url );
                        }

                        $hook_url = esc_url_raw( $hook_url );

                        foreach ( $placeholders as $key => $placeholder ) {
                            $hook_url = str_replace( '_____' . $key . '_____', $placeholder, $hook_url );
                        }

                        return $hook_url;
                    }, explode( PHP_EOL, $value ?? '' ) ) );
                    continue;
                }

                // Mail list
                if ( $prop['type'] === 'maillist' ) {
                    $value = explode( ',', sanitize_text_field( $value ) );
                    $value = array_filter( $value, function( $email ) {
                        return filter_var( trim( $email ), FILTER_VALIDATE_EMAIL );
                    } );

                    $properties[ $key ] = array_unique( $value );
                    continue;
                }

                // Mail list
                if ( $prop['type'] === 'codelist' ) {
                    $value = explode( ',', sanitize_text_field( $value ) );
                    $value = array_map( 'trim', $value );
                    $value = array_map( 'intval', $value );
                    $value = array_unique( array_filter( $value ) );

                    if ( empty( $value ) ) {
                        $value = $prop['default'];
                    }

                    $properties[ $key ] = $value;
                    continue;
                }

                // Default
                $properties[ $key ] = sanitize_text_field( $value );
            }

            // Update
            $form_properties = $contact_form->get_properties();
            $form_properties[ self::METADATA ] = $properties;
            $contact_form->set_properties( $form_properties );
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
                $properties[ self::METADATA ] = static::get_form_properties();
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
            $properties = self::get_form_properties( $contact_form );

            if ( $this->can_submit_to_webhook( $contact_form ) ) {
                return empty( $properties['send_mail'] );
            }

            return $skip_mail;
        }

        /**
         * Action 'wpcf7_mail_sent' to send data to webhook
         *
         * @since    1.0.0
         * @param    obj                $contact_form   ContactForm Obj
         */
        public function wpcf7_mail_sent( $contact_form ) {
            $properties = self::get_form_properties( $contact_form );

            if ( ! $this->can_submit_to_webhook( $contact_form ) ) {
                return;
            }

            $smt_data = $this->get_data_from_special_mail_tags( $contact_form );
            $cf_data = $this->get_data_from_contact_form( $contact_form );

            $data = array_merge( $smt_data, $cf_data );
            $errors = [];

            foreach ( (array) $properties['hook_url'] as $hook_url ) {
                if ( empty( $hook_url ) ) {
                    continue;
                }

                // Try/Catch to support exception on request
                try {
                    $placeholders = CFTZ_Module_CF7::get_hook_url_placeholders( $hook_url );
                    foreach ( $placeholders as $key => $placeholder ) {
                        $value = ( $data[ $key ] ?? '' );
                        if ( ! is_scalar( $value ) ) {
                            $value = implode( '|', $value );
                        }

                        /**
                         * Filter: ctz_hook_url_placeholder
                         *
                         * You can change the placeholder replacement in hook_url;
                         *
                         * @param $value        string      Urlencoded replace value.
                         * @param $placeholder  string      The placeholder to be replaced [$key].
                         * @param $key          string      The key of placeholder.
                         * @param $data         string      Data to be sent to webhook.
                         *
                         * @since 3.0.0
                         */
                        $value =  apply_filters( 'ctz_hook_url_placeholder', urlencode( $value ), $placeholder, $key, $data );

                        $hook_url = str_replace( $placeholder, $value, $hook_url );
                    }

                    /**
                     * Action: ctz_trigger_webhook
                     *
                     * You can add your own actions to process the hook.
                     * We send it using CFTZ_Module_Zapier::pull_the_trigger().
                     *
                     * @since  1.0.0
                     */
                    do_action( 'ctz_trigger_webhook', $data, $hook_url, $properties, $contact_form );
                } catch (Exception $exception) {
                    $errors[] = array(
                        'webhook'   => $hook_url,
                        'exception' => $exception,
                    );

                    /**
                     * Filter: ctz_trigger_webhook_error_mails
                     *
                     * The 'ctz_trigger_webhook_error_mails' filter change the mails we'll notify in case of error.
                     * Default is site administrator, but you can change it in administration.
                     *
                     * @since 4.0.0
                     */
                    $error_mails =  apply_filters( 'ctz_trigger_webhook_error_mails', $properties['error_mails'], $exception );

                    $this->trigger_error_notification( $error_mails, $contact_form, $hook_url, $exception );

                    /**
                     * Filter: ctz_trigger_webhook_error_message
                     *
                     * The 'ctz_trigger_webhook_error_message' filter change the message in case of error.
                     * Default is CF7 error message, but you can access exception to create your own.
                     *
                     * You can ignore errors returning a empty string:
                     * add_filter( 'ctz_trigger_webhook_error_message', '__return_empty_string' );
                     *
                     * @since 1.4.0
                     */
                    $error_message =  apply_filters( 'ctz_trigger_webhook_error_message', $contact_form->message( 'mail_sent_ng' ), $exception );

                    // If empty ignore after sending notification
                    if ( empty( $error_message ) ) continue;

                    // Submission error
                    $submission = WPCF7_Submission::get_instance();
                    $submission->set_status( 'mail_failed' );
                    $submission->set_response( $error_message );
                    break;
                }
            }

            // If empty ignore
            if ( empty( $errors ) ) return;

            /**
             * Action: ctz_trigger_webhook_errors
             *
             * If we have errors, we skiped them in 'ctz_trigger_webhook_error_message' filter.
             * You can now submit your own error.
             *
             * @since  2.4.0
             */
            do_action( 'ctz_trigger_webhook_errors', $errors, $contact_form );
        }

        /**
         * Send a error notification
         *
         * @since    4.0.3
         * @param    obj                $contact_form   ContactForm Obj
         */
        private function trigger_error_notification( $error_mails, $contact_form, $hook_url, $exception ) {
            if ( empty( $error_mails ) && ! ctz_is_developing() ) {
                return;
            }

            $form = sprintf( '#%s - %s', $contact_form->id(), $contact_form->title() );

            $data = array(
                '[FORM]'                => $form,
                '[WEBHOOK]'             => $hook_url,
                '[EXCEPTION]'           => ( method_exists( $exception, 'get_error') ) ? json_encode( $exception->get_error() ) : $exception->getMessage(),
                '[REQUEST_METHOD]'      => ( method_exists( $exception, 'get_request_method') ) ? $exception->get_request_method() : '(MAYBE) POST',
                '[REQUEST_HEADERS]'     => ( method_exists( $exception, 'get_request_headers') ) ? json_encode( $exception->get_request_headers() ) : '',
                '[REQUEST_BODY]'        => ( method_exists( $exception, 'get_request_body') ) ? json_encode( $exception->get_request_body() ) : json_encode( $data ),
                '[RESPONSE_CODE]'       => ( method_exists( $exception, 'get_response_code') ) ? json_encode( $exception->get_response_code() ) : '',
                '[RESPONSE_MESSAGE]'    => ( method_exists( $exception, 'get_response_message') ) ? json_encode( $exception->get_response_message() ) : '',
                '[RESPONSE_HEADERS]'    => ( method_exists( $exception, 'get_response_headers') ) ? json_encode( $exception->get_response_headers() ) : '',
                '[RESPONSE_BODY]'       => ( method_exists( $exception, 'get_response_body') ) ? json_encode( $exception->get_response_body() ) : '',
            );

            // Log in development
            if ( ctz_is_developing() && function_exists( 'dump_table' ) ) {
                dump_table( $data );
                return;
            }

            $notification = __( '
Hey! How are you?

"CF7 to Webhook" has a built-in feature that detects when a webhook fails and notifies you with this automated email.

- Form: [FORM]
- Webhook: [WEBHOOK]
- Error: [EXCEPTION]

Request Method:
[REQUEST_METHOD]

Request Headers:
[REQUEST_HEADERS]

Request Body:
[REQUEST_BODY]

Response Code:
[RESPONSE_CODE]

Response Message:
[RESPONSE_MESSAGE]

Response Headers:
[RESPONSE_HEADERS]

Response Body:
[RESPONSE_BODY]

--

You\'ll receive one notification for each webhook with errors.
Other webhooks maybe were successful.

Please, be careful sharing this data (even in WordPress official support forum).
It may contain sensitive data.
            ', 'cf7-to-zapier' );

            $notification = str_replace( array_keys( $data ), array_values( $data ), $notification );
            $subject = sprintf( __( '[%s] Webhook Error on Form %s', 'cf7-to-zapier' ), wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ), $form );

            wp_mail( $error_mails, $subject, $notification );
        }

        /**
         * Retrieve a array with data from Contact Form data
         *
         * @since    1.0.0
         * @param    obj                $contact_form   ContactForm Obj
         */
        private function get_data_from_contact_form( $contact_form ) {
            $data = [];

            // Form properties
            $properties = self::get_form_properties( $contact_form );

            // Submission
            $submission = WPCF7_Submission::get_instance();
            $uploaded_files = ( ! empty( $submission ) ) ? $submission->uploaded_files() : [];

            // Upload Info
            $wp_upload_dir = wp_get_upload_dir();
            $upload_path = CFTZ_UPLOAD_DIR . '/' . $contact_form->id() . '/' . uniqid();

            $upload_url = $wp_upload_dir['baseurl'] . '/' . $upload_path;
            $upload_dir = $wp_upload_dir['basedir'] . '/' . $upload_path;

            $tags = $contact_form->scan_form_tags();
            foreach ( $tags as $tag ) {
                if ( empty( $tag->name ) ) continue;

                // Regular Tags
                $value = ( isset( $_POST[ $tag->name ] ) ) ? $_POST[ $tag->name ] : '';

                if ( is_array( $value ) ) {
                    foreach ( $value as $key => $v ) {
                        $value[ $key ] = stripslashes( $v );
                    }
                }

                if ( is_string( $value ) ) {
                    $value = stripslashes( $value );
                }

                // Files
                if ( in_array( $tag->basetype, [ 'file', 'multilinefile' ] ) && ! empty( $uploaded_files[ $tag->name ] ) ) {
                    $files = $uploaded_files[ $tag->name ];

                    $copied_files = [];
                    foreach ( (array) $files as $file ) {
                        // Send file content
                        if ( ! empty( $properties['files_send_content'] ) ) {
                            $file_content = file_get_contents( $file );
                            $file_content = base64_encode( $file_content );

                            $copied_files[] = array(
                                'filename' => basename( $file ),
                                'content' => $file_content,
                            );

                            continue;
                        }

                        // Send file link
                        wp_mkdir_p( $upload_dir );

                        $filename = wp_unique_filename( $upload_dir, $tag->name . '-' . basename( $file ) );

                        if ( ! copy( $file, $upload_dir . '/' . $filename ) ) {
                            $submission = WPCF7_Submission::get_instance();
                            $submission->set_status( 'mail_failed' );
                            $submission->set_response( $contact_form->message( 'upload_failed' ) );

                            continue;
                        }

                        $copied_files[] = $upload_url . '/' . $filename;
                    }

                    $value = $copied_files;

                    if ( count( $value ) === 1 ) {
                        $value = $value[0];
                    }
                }

                // Support to Pipes
                $value = $this->get_value_from_pipes( $value, $tag );

                // Support to Free Text on checkbox and radio
                if ( $tag->has_option( 'free_text' ) && in_array( $tag->basetype, [ 'checkbox', 'radio' ] ) ) {
                    $free_text_label = end( $tag->values );
                    $free_text_name  = $tag->name . '_free_text';
                    $free_text_value = ( isset( $_POST[ $free_text_name ] ) ) ? $_POST[ $free_text_name ] : '';

                    if ( is_array( $value ) ) {
                        foreach ( $value as $key => $v ) {
                            if ( $v !== $free_text_label ) {
                                continue;
                            }

                            $value[ $key ] = stripslashes( $free_text_value );
                        }
                    }

                    if ( is_string( $value ) && $value === $free_text_label ) {
                        $value = stripslashes( $free_text_value );
                    }
                }

                // Support to "webhook" option (rename field value)
                $key = $tag->name;
                $webhook_key = $tag->get_option( 'webhook' );

                if ( ! empty( $webhook_key ) && ! empty( $webhook_key[0] ) ) {
                    $key = $webhook_key[0];
                }

                $data[ $key ] = $value;
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

            $properties = self::get_form_properties( $contact_form );
            if ( ! empty( $properties['special_mail_tags'] ) ) {
                $tags = self::get_special_mail_tags_from_string( $properties['special_mail_tags'] );
            }

            foreach ( $tags as $key => $tag ) {
                $mail_tag = new WPCF7_MailTag( sprintf( '[%s]', $tag ), $tag, '' );
                $value = '';

                // Support to "_raw_" values. @see WPCF7_MailTag::__construct()
                if ( $mail_tag->get_option( 'do_not_heat' ) ) {
                    $value = apply_filters( 'wpcf7_special_mail_tags', '', $mail_tag->tag_name(), false, $mail_tag );
                    $value = $_POST[ $mail_tag->field_name() ] ?? '';
                }

                $value = apply_filters( 'wpcf7_special_mail_tags', $value, $mail_tag->tag_name(), false, $mail_tag );
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
         * Check we can submit a form to webhook
         *
         * @since    1.0.0
         * @param    obj                $contact_form   ContactForm Obj
         */
        private function can_submit_to_webhook( $contact_form ) {
            $properties = self::get_form_properties( $contact_form );

            if ( empty( $properties ) || empty( $properties['activate'] ) || empty( $properties['hook_url'] ) ) {
                return false;
            }

            return true;
        }

        /**
         * Retrieve a array with data from Special Mail Tags
         *
         * @link https://contactform7.com/special-mail-tags
         *
         * @since    4.0.3
         *
         * @param    mixed              $value
         * @param    obj                $atag   WPCF7_FormTag
         * @return   mixed              $value
         */
        private function get_value_from_pipes( $value, $tag ) {
            if ( ! WPCF7_USE_PIPE || ! $tag->pipes instanceof WPCF7_Pipes ) {
                return $value;
            }

            /**
             * Check for pipe support
             * @see WPCF7_Submission::setup_posted_data()
             */
            if ( ! wpcf7_form_tag_supports( $tag->type, 'selectable-values' ) || $tag->pipes->zero() ) {
                return $value;
            }

            // if ( wpcf7_form_tag_supports( $tag->type, 'selectable-values' ) ) {

            if ( is_array( $value ) ) {
                $new_value = [];

                foreach ( $value as $v ) {
                    $new_value[] = $tag->pipes->do_pipe( wp_unslash( $v ) );
                }

                return $new_value;
            }

            return $tag->pipes->do_pipe( wp_unslash( $value ) );
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
         * List placeholders from hook_url
         *
         * @since    3.0.0
         * @param    string     $hook_url
         * @return   array      $placeholders
         */
        public static function get_hook_url_placeholders( $hook_url ) {
            $placeholders = [];

            preg_match_all( '/\[{1}[^\[\]]+\]{1}/', $hook_url, $matches );

            foreach ( $matches[0] as $placeholder ) {
                $placeholder = substr( $placeholder, 1, -1 );
                $placeholders[ $placeholder ] = '[' . $placeholder . ']';
            }

            return $placeholders;
        }

        /**
         * Get CF7 To Webhook Properties
         *
         * @since    4.0.0
         * @return   array      $options
         */
        public static function get_properties() {
            return [
                [
                    'key'     => 'activate',
                    'default' => '0',
                    'type'    => 'checkbox',
                ],
                [
                    'key'     => 'hook_url',
                    'default' => [],
                    'type'    => 'hookurl',
                ],
                [
                    'key'     => 'send_mail',
                    'default' => '0',
                    'type'    => 'checkbox',
                ],
                [
                    'key'     => 'custom_method',
                    'default' => 'POST',
                    'type'    => 'text',
                ],
                [
                    'key'     => 'files_send_content',
                    'default' => '0',
                    'type'    => 'checkbox',
                ],
                [
                    'key'     => 'special_mail_tags',
                    'default' => '',
                    'type'    => 'textarea',
                ],
                [
                    'key'     => 'custom_headers',
                    'default' => '',
                    'type'    => 'textarea',
                ],
                [
                    'key'     => 'custom_body',
                    'default' => '',
                    'type'    => 'json',
                ],
                [
                    'key'     => 'error_mails',
                    'default' => [ get_option( 'admin_email' ) ],
                    'type'    => 'maillist',
                ],
                [
                    'key'     => 'accepted_statuses',
                    'default' => [ 200, 201, 202, 204, 205 ],
                    'type'    => 'codelist',
                ],
            ];
        }

        /**
         * GEt contact form properties
         *
         * @since    4.0.0
         * @param    WPCF7_ContactForm  $contactform    Current ContactForm Obj
         * @return   array      $options
         */
        public static function get_form_properties( $contactform = null ) {
            $settings = static::get_properties();

            if ( empty( $contactform ) || ! is_a( $contactform, 'WPCF7_ContactForm' ) ) {
                return array_column( $settings, 'default', 'key' );
            }

            $options = array();
            $properties = $contactform->prop( self::METADATA );
            foreach ( $settings as $setting ) {
                $key = $setting['key'];

                if ( isset( $properties[ $key ] ) ) {
                    $options[ $key ] = $properties[ $key ];
                    continue;
                }

                $options[ $key ] = $setting['default'];
            }

            return $options;
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
