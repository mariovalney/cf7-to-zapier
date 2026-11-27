<?php
/**
 * Base class of the Contact Form 7 suite: loads the form tag code of Contact Form 7.
 *
 * @package Cf7_To_Zapier
 */

namespace CFTZ\Tests\Cf7;

use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use CFTZ_Module_CF7;
use Cf7_To_Zapier;
use WPCF7_ContactForm;
use WPCF7_FormTagsManager;
use WPCF7_Submission;

abstract class TestCase extends \CFTZ\Tests\TestCase {

    /**
     * Files of Contact Form 7 with form tags, mail tags and pipes. They only call
     * WordPress to add hooks and in the functions mocked in setUp().
     */
    const FILES = array(
        'includes/functions.php',
        'includes/formatting.php',
        'includes/validation-functions.php',
        'includes/pipe.php',
        'includes/form-tag.php',
        'includes/form-tags-manager.php',
        'includes/mail-tag.php',
    );

    /**
     * Modules with the form tags used in the tests. Each one registers its tags in
     * wpcf7_add_form_tag_<module>().
     */
    const MODULES = array( 'text', 'textarea', 'number', 'date', 'select', 'checkbox', 'acceptance', 'file', 'hidden' );

    protected function setUp(): void {
        parent::setUp();

        Functions\when( 'wp_parse_args' )->alias(
            function( $args, $defaults = array() ) {
                if ( is_string( $args ) ) {
                    parse_str( $args, $args );
                }

                return array_merge( (array) $defaults, (array) $args );
            }
        );

        Functions\when( 'wp_unslash' )->alias(
            function( $value ) {
                return is_array( $value ) ? array_map( 'stripslashes', $value ) : stripslashes( $value );
            }
        );

        Functions\when( 'wp_scrub_utf8' )->alias(
            function( $text ) {
                return mb_convert_encoding( $text, 'UTF-8', 'UTF-8' );
            }
        );

        foreach ( self::FILES as $file ) {
            require_once self::cf7_file( $file );
        }

        foreach ( self::MODULES as $module ) {
            require_once self::cf7_file( 'modules/' . $module . '.php' );
            call_user_func( 'wpcf7_add_form_tag_' . $module );
        }
    }

    protected function tearDown(): void {
        $_POST                      = array();
        WPCF7_Submission::$instance = null;

        parent::tearDown();
    }

    /**
     * Absolute path of a file of Contact Form 7.
     *
     * @param string $path Path inside the plugin folder.
     * @return string
     */
    protected static function cf7_file( $path ) {
        return CFTZ_TESTS_CF7_DIR . '/' . $path;
    }

    /**
     * Form tags parsed by Contact Form 7 from the form template.
     *
     * @param string $template Form template, as in the form editor.
     * @return \WPCF7_FormTag[]
     */
    protected function scan( $template ) {
        return WPCF7_FormTagsManager::get_instance()->scan( $template );
    }

    /**
     * Submit a form with the webhook active and return the data of the 'ctz_trigger_webhook' action.
     *
     * @param string $template Form template.
     * @param array  $post     Posted fields, with the slashes WordPress adds.
     * @param array  $settings Webhook settings.
     * @return array
     */
    protected function send( $template, array $post, array $settings = array() ) {
        $_POST                      = $post;
        WPCF7_Submission::$instance = new WPCF7_Submission();

        Functions\when( 'wp_get_upload_dir' )->justReturn(
            array(
                'basedir' => sys_get_temp_dir(),
                'baseurl' => 'https://example.com/wp-content/uploads',
            )
        );

        $sent = null;
        Actions\expectDone( 'ctz_trigger_webhook' )
            ->once()
            ->whenHappen(
                function( $data ) use ( &$sent ) {
                    $sent = $data;
                }
            );

        $settings = array_merge(
            array(
                'activate' => '1',
                'hook_url' => array( 'https://example.com/hook' ),
            ),
            $settings
        );

        $form = new WPCF7_ContactForm( 5, $this->scan( $template ), array( CFTZ_Module_CF7::METADATA => $settings ) );
        ( new CFTZ_Module_CF7( new Cf7_To_Zapier() ) )->wpcf7_mail_sent( $form );

        return $sent;
    }
}
