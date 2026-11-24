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
         * Daily event that deletes the uploads older than the "Delete after" option of each form
         *
         * @since    5.3.0
         */
        const CRON_DELETE_UPLOADS = 'ctz_delete_expired_uploads';

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
            $this->core->add_action( 'wp_ajax_ctz_delete_uploads', [ $this, 'wp_ajax_ctz_delete_uploads' ] );

            // Cron
            $this->core->add_action( 'admin_init', [ $this, 'schedule_delete_expired_uploads' ] );
            $this->core->add_action( self::CRON_DELETE_UPLOADS, [ $this, 'delete_expired_uploads' ] );
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

            if ( file_exists( WP_PLUGIN_DIR . '/contact-form-7/wp-contact-form-7.php' ) ) {
                $url = 'plugins.php';
            } else {
                $url = 'plugin-install.php?tab=search&s=Contact+form+7';
            }

            echo '. <a href="' . esc_url( admin_url( $url ) ) . '">' . esc_html__( 'Do it now?', 'cf7-to-zapier' ) . '</a></p>';
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
            // In the footer: the webhook panel adds CTZ_ADMIN_TAGS to it with wp_add_inline_script()
            // while rendering, and a script printed in the header would have gone out without it.
            wp_enqueue_script( 'ctz-admin-script', CFTZ_PLUGIN_URL . '/modules/cf7/admin/assets/admin.js', [ 'ctz-select2-script' ], $version, true );

            // A repository checkout loads the templates from the website files, so changes show up before deploy.
            // Released copies do not ship ci/ and always load the published file, even with WP_DEBUG on.
            $templates_url = 'https://cf7-to-webhook.valney.dev/templates.json';
            if ( ctz_is_developing() && file_exists( CFTZ_PLUGIN_PATH . '/ci/website/public/templates.json' ) ) {
                $templates_url = CFTZ_PLUGIN_URL . '/ci/website/public/templates.json';
            }

            wp_localize_script( 'ctz-admin-script', 'CTZ_ADMIN', array(
                'templates_url' => $templates_url,
                'groups'        => array(
                    'default' => __( 'Default', 'cf7-to-zapier' )
                ),
                'messages'      => array(
                    'open_docs'              => __( 'This template has documentation.', 'cf7-to-zapier' ) . '<br>' . __( 'Open in another tab?', 'cf7-to-zapier' ),
                    'confirm_all'            => __( 'This action will replace "Headers" and "Body" and remove "Special Mail Tags".', 'cf7-to-zapier' ),
                    'confirm_body'           => __( 'This action will replace "Body" and remove "Special Mail Tags" and "Headers".', 'cf7-to-zapier' ),
                    'save_to_preview'        => __( 'Save to load preview.', 'cf7-to-zapier' ),
                    'btn_no'                 => __( 'No', 'cf7-to-zapier' ),
                    'btn_yes'                => __( 'Yes', 'cf7-to-zapier' ),
                    'btn_cancel'             => __( 'Cancel', 'cf7-to-zapier' ),
                    'btn_continue'           => __( 'Continue', 'cf7-to-zapier' ),
                    'choose_template'        => __( 'Choose a template', 'cf7-to-zapier' ),
                    'confirm_delete_uploads' => __( 'This action will delete all files uploaded with this form since always.', 'cf7-to-zapier' ) . '<br>' . __( 'Links sent to webhook will stop working.', 'cf7-to-zapier' ),
                    'delete_uploads_error'   => __( 'The files could not be deleted. Reload the page and try again.', 'cf7-to-zapier' ),
                ),
            ) );
        }

        /**
         * Action: 'wp_ajax_ctz_delete_uploads'
         *
         * Delete every file uploaded with a form: its media library attachments and its
         * folder in the uploads, with everything inside.
         *
         * @since    5.2.0
         *
         * @return void
         */
        public function wp_ajax_ctz_delete_uploads() {
            $form_id = absint( $_POST['form_id'] ?? 0 );

            // Only administrators: it deletes files that other users and webhooks may still need.
            if ( ! check_ajax_referer( 'ctz_delete_uploads_' . $form_id, 'nonce', false ) || ! current_user_can( 'manage_options' ) ) {
                wp_send_json_error( null, 403 );
            }

            if ( empty( $form_id ) || ! wpcf7_contact_form( $form_id ) ) {
                wp_send_json_error( null, 404 );
            }

            self::delete_uploads( $form_id );

            wp_send_json_success( array(
                'summary' => self::get_uploads_summary_text( $form_id ),
            ) );
        }

        /**
         * Action: 'admin_init'
         *
         * Schedule the daily event that deletes old uploads. It runs even when no form
         * uses "Delete after", and then does nothing.
         *
         * @since    5.3.0
         *
         * @return void
         */
        public function schedule_delete_expired_uploads() {
            if ( ! wp_next_scheduled( self::CRON_DELETE_UPLOADS ) ) {
                wp_schedule_event( time(), 'daily', self::CRON_DELETE_UPLOADS );
            }
        }

        /**
         * Action: 'ctz_delete_expired_uploads'
         *
         * Delete the uploads of each form older than its "Delete after" days.
         * Days are counted from midnight UTC, like the date in the submission folder name,
         * so files are kept for at least that many days.
         *
         * @since    5.3.0
         *
         * @return void
         */
        public function delete_expired_uploads() {
            if ( ! class_exists( 'WPCF7_ContactForm' ) ) {
                return;
            }

            $now   = time();
            $today = $now - ( $now % DAY_IN_SECONDS );

            foreach ( WPCF7_ContactForm::find() as $contact_form ) {
                $properties = self::get_form_properties( $contact_form );
                $days       = absint( $properties['delete_files_after'] );

                if ( $days < 1 ) {
                    continue;
                }

                self::delete_uploads( $contact_form->id(), $today - $days * DAY_IN_SECONDS );
            }
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
            if ( ! current_user_can( 'wpcf7_edit_contact_form', $contact_form->id() ) ) {
                return;
            }

            $props = static::get_properties();
            $properties = static::get_form_properties( $contact_form );

            foreach ( $props as $prop ) {
                $key = $prop['key'];
                $name = 'ctz-webhook-' . $key;

                $value = ( isset( $_POST[ $name ] ) ) ? $_POST[ $name ] : null;

                // Only administrators, like "Delete all files": the field is disabled for other users, so it is not sent.
                if ( $key === 'delete_files_after' && ! current_user_can( 'manage_options' ) ) {
                    continue;
                }

                // Number
                if ( $prop['type'] === 'number' ) {
                    $properties[ $key ] = (string) max( 0, intval( $value ) );
                    continue;
                }

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
                        $placeholders = ctz_get_string_placeholders( $hook_url );

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

            // We read $_POST directly, so invalid UTF-8 is still here and would break the JSON body.
            $data = $this->scrub_utf8( array_merge( $smt_data, $cf_data ) );
            $errors = [];

            foreach ( (array) $properties['hook_url'] as $hook_url ) {
                if ( empty( $hook_url ) ) {
                    continue;
                }

                // Try/Catch to support exception on request
                try {
                    $placeholders = ctz_get_string_placeholders( $hook_url );
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

                    $this->trigger_error_notification( $error_mails, $contact_form, $hook_url, $exception, $data );

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
         * @param    array              $data           Data sent to the webhook, used when the exception has no request body.
         */
        private function trigger_error_notification( $error_mails, $contact_form, $hook_url, $exception, $data = [] ) {
            if ( empty( $error_mails ) && ! cftz_activated_debug_functions() ) {
                return;
            }

            $form = sprintf( '#%s - %s', $contact_form->id(), $contact_form->title() );

            $request_headers = '';
            if ( method_exists( $exception, 'get_request_headers' ) ) {
                $headers = (array) $exception->get_request_headers();
                // Redact sensitive headers to avoid leaking API keys or tokens in plaintext email.
                $sensitive = [ 'authorization', 'x-api-key', 'api-key', 'x-auth-token', 'cookie' ];
                foreach ( $headers as $name => $val ) {
                    if ( in_array( strtolower( $name ), $sensitive, true ) ) {
                        $headers[ $name ] = '***REDACTED***';
                    }
                }
                $request_headers = wp_json_encode( $headers );
            }

            $replacements = array(
                '[FORM]'                => $form,
                '[WEBHOOK]'             => $hook_url,
                '[EXCEPTION]'           => ( method_exists( $exception, 'get_error') ) ? wp_json_encode( $exception->get_error() ) : $exception->getMessage(),
                '[REQUEST_METHOD]'      => ( method_exists( $exception, 'get_request_method') ) ? $exception->get_request_method() : '(MAYBE) POST',
                '[REQUEST_HEADERS]'     => $request_headers,
                '[REQUEST_BODY]'        => ( method_exists( $exception, 'get_request_body') ) ? wp_json_encode( $exception->get_request_body() ) : wp_json_encode( $data ),
                '[RESPONSE_CODE]'       => ( method_exists( $exception, 'get_response_code') ) ? wp_json_encode( $exception->get_response_code() ) : '',
                '[RESPONSE_MESSAGE]'    => ( method_exists( $exception, 'get_response_message') ) ? wp_json_encode( $exception->get_response_message() ) : '',
                '[RESPONSE_HEADERS]'    => ( method_exists( $exception, 'get_response_headers') ) ? wp_json_encode( $exception->get_response_headers() ) : '',
                '[RESPONSE_BODY]'       => ( method_exists( $exception, 'get_response_body') ) ? wp_json_encode( $exception->get_response_body() ) : '',
            );

            // Log in development, only when the debug helpers were asked for (CFTZ_ADD_DEBUG_FUNCTIONS)
            if ( cftz_activated_debug_functions() && function_exists( 'dump_table' ) ) {
                dump_table( $replacements );
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

            $notification = str_replace( array_keys( $replacements ), array_values( $replacements ), $notification );
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

            // Upload Info: the folder name starts with the date (UTC), used by "Delete after".
            $wp_upload_dir = wp_get_upload_dir();
            $upload_time = time();
            $upload_path = CFTZ_UPLOAD_DIR . '/' . $contact_form->id() . '/' . gmdate( 'Ymd', $upload_time ) . '_' . bin2hex( random_bytes( 16 ) );

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

                    $send_content   = ! empty( $properties['files_send_content'] );
                    $add_to_library = ! empty( $properties['add_to_library'] );

                    $copied_files = [];
                    foreach ( (array) $files as $file ) {
                        // CF7 deletes its upload at the end of the request, so links and the media library need a copy.
                        $copy = '';
                        if ( ! $send_content || $add_to_library ) {
                            wp_mkdir_p( $upload_dir );
                            $this->protect_upload_dirs( array(
                                $wp_upload_dir['basedir'] . '/' . CFTZ_UPLOAD_DIR,
                                $wp_upload_dir['basedir'] . '/' . CFTZ_UPLOAD_DIR . '/' . $contact_form->id(),
                                $upload_dir,
                            ) );

                            $filename = wp_unique_filename( $upload_dir, $tag->name . '-' . basename( $file ) );

                            if ( copy( $file, $upload_dir . '/' . $filename ) ) {
                                $copy = $upload_dir . '/' . $filename;
                            }
                        }

                        if ( $copy && $add_to_library ) {
                            $this->add_file_to_library( $copy, $contact_form, $tag->name, $upload_time );
                        }

                        // Send file content
                        if ( $send_content ) {
                            $file_content = file_get_contents( $file );
                            $file_content = base64_encode( $file_content );

                            $copied_files[] = array(
                                'filename' => basename( $file ),
                                'content' => $file_content,
                            );

                            continue;
                        }

                        // Send file link
                        if ( ! $copy ) {
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

                    // After 5.8.6
                    if ( ! isset( $_POST[ $free_text_name ] ) ) {
                        $free_text_name = '_wpcf7_free_text_' . $tag->name;
                    }

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
             * You can return false to avoid merge data with 'cf7msm_posted_data' (previous step)
             *
             * @param $should_support   True
             * @param $contact_form     ContactForm obj from 'wpcf7_mail_sent' action
             */
            $should_support_cf7msm = apply_filters( 'ctz_get_data_from_cf7msm_posted_data', true, $contact_form );
            if ( $should_support_cf7msm && function_exists( 'cf7msm_get' ) ) {
                $prev_data = cf7msm_get( 'cf7msm_posted_data', '' );
                if ( ! empty( $prev_data ) && is_array( $prev_data ) ) {
                    $cf7msm_pipe_flow_id = ( function_exists( 'cf7msm_pipe_flow_field_name' ) ) ? cf7msm_pipe_flow_field_name() : 'wpcf7msm_pipe_flow_id';
                    $cf7msm_internal_keys = array(
                        'cf7msm-no-ss',
                        'cf7msm_options',
                        $cf7msm_pipe_flow_id
                    );

                    $prev_data = array_diff_key( $prev_data, array_fill_keys( $cf7msm_internal_keys, 'any' ) );
                    $data = array_merge( $prev_data, $data );
                }
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
         * Add a copied upload to the media library as a private attachment.
         *
         * WordPress would create resized copies and a "-scaled" or "-rotated" version next to
         * the file, and point the attachment to them. The filters turn that off, so the
         * attachment keeps the same file and URL sent to the webhook.
         *
         * @since    5.2.0
         *
         * @param    string             $path           Absolute path of the copied file
         * @param    WPCF7_ContactForm  $contact_form   ContactForm Obj
         * @param    string             $field          Name of the file field
         * @param    int                $time           Submission time, the same as the folder date
         * @return   int                $attachment_id  Attachment ID or 0 if it was not added
         */
        private function add_file_to_library( $path, $contact_form, $field, $time ) {
            // Every file type goes in: the file is already public in the uploads folder. CF7 has
            // renamed risky extensions (svg, html, js, php...) to ".txt". The content check gives
            // the real type, and a file whose content does not match its extension, or that
            // WordPress does not know, is stored as a generic binary file.
            $filetype  = wp_check_filetype_and_ext( $path, basename( $path ), wp_get_mime_types() );
            $mime_type = ! empty( $filetype['type'] ) ? $filetype['type'] : 'application/octet-stream';

            // The date comes from the folder, so "Delete after" removes the attachment and its folder on the same day.
            $date_gmt = gmdate( 'Y-m-d H:i:s', $time );

            $attachment_id = wp_insert_attachment( array(
                'post_title'     => sanitize_text_field( pathinfo( $path, PATHINFO_FILENAME ) ),
                'post_mime_type' => $mime_type,
                'post_status'    => 'private',
                'post_date'      => get_date_from_gmt( $date_gmt ),
                'post_date_gmt'  => $date_gmt,
            ), $path, 0, true );

            if ( is_wp_error( $attachment_id ) ) {
                return 0;
            }

            update_post_meta( $attachment_id, '_ctz_contact_form_id', $contact_form->id() );
            update_post_meta( $attachment_id, '_ctz_field', $field );

            require_once ABSPATH . 'wp-admin/includes/image.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';

            add_filter( 'intermediate_image_sizes_advanced', '__return_empty_array', 999 );
            add_filter( 'fallback_intermediate_image_sizes', '__return_empty_array', 999 );
            add_filter( 'big_image_size_threshold', '__return_false', 999 );
            add_filter( 'wp_image_maybe_exif_rotate', '__return_false', 999 );

            wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $path ) );

            remove_filter( 'intermediate_image_sizes_advanced', '__return_empty_array', 999 );
            remove_filter( 'fallback_intermediate_image_sizes', '__return_empty_array', 999 );
            remove_filter( 'big_image_size_threshold', '__return_false', 999 );
            remove_filter( 'wp_image_maybe_exif_rotate', '__return_false', 999 );

            return $attachment_id;
        }

        /**
         * Absolute path of the folder with the files uploaded with a form.
         *
         * @since    5.2.0
         *
         * @param    int                $form_id    Contact form ID
         * @return   string
         */
        public static function get_upload_dir( $form_id ) {
            return wp_get_upload_dir()['basedir'] . '/' . CFTZ_UPLOAD_DIR . '/' . absint( $form_id );
        }

        /**
         * IDs of the media library attachments created from a form's uploads.
         *
         * @since    5.2.0
         *
         * @param    int                $form_id    Contact form ID
         * @param    int                $before     Optional. Only attachments created before this timestamp. Since 5.3.0.
         * @return   array
         */
        public static function get_upload_attachments( $form_id, $before = 0 ) {
            $args = array(
                'post_type'   => 'attachment',
                'post_status' => array( 'inherit', 'private', 'trash' ),
                'numberposts' => -1,
                'fields'      => 'ids',
                'meta_key'    => '_ctz_contact_form_id',
                'meta_value'  => absint( $form_id ),
            );

            if ( ! empty( $before ) ) {
                $args['date_query'] = array(
                    array(
                        'column' => 'post_date_gmt',
                        'before' => gmdate( 'Y-m-d H:i:s', $before ),
                    ),
                );
            }

            return get_posts( $args );
        }

        /**
         * Delete files uploaded with a form: its media library attachments and its submission
         * folders, with everything inside.
         *
         * @since    5.3.0
         *
         * @param    int                $form_id    Contact form ID
         * @param    int                $before     Optional. Only submissions before this timestamp. Default deletes all.
         * @return   void
         */
        public static function delete_uploads( $form_id, $before = 0 ) {
            $dir = self::get_upload_dir( $form_id );

            // Everything: the form folder.
            $expired = array( $dir );

            // Before a date: the submission folders. Listed before deleting the attachments,
            // which changes the modification time of older folders.
            if ( ! empty( $before ) ) {
                $expired = array();

                if ( is_dir( $dir ) ) {
                    foreach ( new DirectoryIterator( $dir ) as $item ) {
                        if ( $item->isDot() || $item->isLink() || ! $item->isDir() ) {
                            continue;
                        }

                        if ( self::get_submission_time( $item->getPathname() ) < $before ) {
                            $expired[] = $item->getPathname();
                        }
                    }
                }
            }

            foreach ( self::get_upload_attachments( $form_id, $before ) as $attachment_id ) {
                wp_delete_attachment( $attachment_id, true );
            }

            foreach ( $expired as $expired_dir ) {
                self::delete_upload_dir( $expired_dir );
            }
        }

        /**
         * When a submission folder was created.
         *
         * The folder name starts with the date (UTC), and this returns its midnight. Folders
         * created before 5.3.0 have only the random part, so it returns their modification
         * time, which does not change after the submission.
         *
         * @since    5.3.0
         *
         * @param    string             $dir        Absolute path of a submission folder
         * @return   int                Timestamp, or PHP_INT_MAX when unknown, so the folder is kept
         */
        private static function get_submission_time( $dir ) {
            if ( preg_match( '/^(\d{8})_/', basename( $dir ), $matches ) ) {
                $date = DateTime::createFromFormat( '!Ymd', $matches[1], new DateTimeZone( 'UTC' ) );

                if ( $date ) {
                    return $date->getTimestamp();
                }
            }

            $time = filemtime( $dir );

            return ( false === $time ) ? PHP_INT_MAX : $time;
        }

        /**
         * Files, total size and media library attachments uploaded with a form.
         *
         * @since    5.2.0
         *
         * @param    int                $form_id    Contact form ID
         * @return   array              { files, size, attachments }
         */
        public static function get_uploads_summary( $form_id ) {
            $files = 0;
            $size  = 0;
            $dir   = self::get_upload_dir( $form_id );

            if ( is_dir( $dir ) ) {
                $iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ) );

                foreach ( $iterator as $file ) {
                    // The index.php files only protect the folders.
                    if ( ! $file->isFile() || 'index.php' === $file->getFilename() ) {
                        continue;
                    }

                    $files++;
                    $size += $file->getSize();
                }
            }

            return array(
                'files'       => $files,
                'size'        => $size,
                'attachments' => count( self::get_upload_attachments( $form_id ) ),
            );
        }

        /**
         * Text for the webhook panel with the summary of a form's uploads.
         *
         * @since    5.2.0
         *
         * @param    int                $form_id    Contact form ID
         * @return   string
         */
        public static function get_uploads_summary_text( $form_id ) {
            $summary = self::get_uploads_summary( $form_id );

            if ( empty( $summary['files'] ) && empty( $summary['attachments'] ) ) {
                return __( 'No files uploaded with this form.', 'cf7-to-zapier' );
            }

            return sprintf(
                /* translators: 1: number of files, 2: total size, 3: number of media library items */
                __( '%1$s (%2$s), %3$s in the media library.', 'cf7-to-zapier' ),
                sprintf( _n( '%s file', '%s files', $summary['files'], 'cf7-to-zapier' ), number_format_i18n( $summary['files'] ) ),
                size_format( $summary['size'] ),
                number_format_i18n( $summary['attachments'] )
            );
        }

        /**
         * Delete an upload folder (of a form or a submission) with everything inside.
         *
         * The real path must be inside the plugin upload folder, so a wrong path or a link
         * cannot delete anything else.
         *
         * @since    5.2.0
         *
         * @param    string             $dir        Absolute path of the folder
         * @return   void
         */
        private static function delete_upload_dir( $dir ) {
            $base = realpath( wp_get_upload_dir()['basedir'] . '/' . CFTZ_UPLOAD_DIR );
            $dir  = realpath( $dir );

            if ( empty( $base ) || empty( $dir ) || 0 !== strpos( $dir, $base . DIRECTORY_SEPARATOR ) ) {
                return;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ),
                RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ( $iterator as $item ) {
                if ( $item->isDir() && ! $item->isLink() ) {
                    rmdir( $item->getPathname() );
                    continue;
                }

                wp_delete_file( $item->getPathname() );
            }

            rmdir( $dir );
        }

        /**
         * Add an empty index.php to upload folders.
         *
         * A server with directory listing on would show every submission from the base
         * and form folders, and every file of a submission from its own folder.
         *
         * @since    5.2.0
         *
         * @param    array              $dirs       Absolute folder paths
         * @return   void
         */
        private function protect_upload_dirs( $dirs ) {
            foreach ( $dirs as $dir ) {
                $index = $dir . '/index.php';

                if ( is_dir( $dir ) && ! file_exists( $index ) ) {
                    file_put_contents( $index, "<?php\n// Silence is golden.\n" );
                }
            }
        }

        /**
         * Replace invalid UTF-8 sequences in a value (or array of values).
         *
         * Contact Form 7 does it to its posted data, but we read $_POST directly
         * and json_encode() fails on invalid UTF-8. See issue #23.
         *
         * @since    5.1.1
         *
         * @param    mixed              $value
         * @return   mixed              $value
         */
        private function scrub_utf8( $value ) {
            if ( is_array( $value ) ) {
                foreach ( $value as $key => $v ) {
                    $value[ $key ] = $this->scrub_utf8( $v );
                }

                return $value;
            }

            if ( ! is_string( $value ) ) {
                return $value;
            }

            // Replaces each invalid sequence with U+FFFD. Since WordPress 6.9.
            if ( function_exists( 'wp_scrub_utf8' ) ) {
                return wp_scrub_utf8( $value );
            }

            // Replaces each invalid sequence with "?".
            if ( function_exists( 'mb_convert_encoding' ) ) {
                return mb_convert_encoding( $value, 'UTF-8', 'UTF-8' );
            }

            // Last resort: empties the value if it is not valid UTF-8.
            return wp_check_invalid_utf8( $value );
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
                    'key'     => 'add_to_library',
                    'default' => '0',
                    'type'    => 'checkbox',
                ],
                [
                    'key'     => 'delete_files_after',
                    'default' => '0',
                    'type'    => 'number',
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
