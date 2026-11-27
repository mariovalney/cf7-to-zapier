<?php
/**
 * What the plugin expects from Contact Form 7, checked against its code.
 *
 * When Contact Form 7 changes one of these, the unit suite keeps passing with its
 * stubs, and this suite fails. Issue #11 and the free text field of 5.8.6 were
 * changes like these.
 *
 * @package Cf7_To_Zapier
 */

namespace CFTZ\Tests\Cf7;

use WPCF7_MailTag;
use WPCF7_Pipes;

class ContractTest extends TestCase {

    /**
     * @dataProvider hooks
     */
    public function test_has_the_hooks_the_plugin_uses( $function, $hook ) {
        $pattern = '/\b' . $function . '(_ref_array)?\(\s*[\'"]' . preg_quote( $hook, '/' ) . '[\'"]/';

        $this->assertMatchesRegularExpression( $pattern, self::source(), "{$function}( '{$hook}' )" );
    }

    public function hooks() {
        return array(
            'webhook panel'                     => array( 'apply_filters', 'wpcf7_editor_panels' ),
            'save the settings'                 => array( 'do_action', 'wpcf7_save_contact_form' ),
            'skip the mail'                     => array( 'apply_filters', 'wpcf7_skip_mail' ),
            'send the webhook'                  => array( 'do_action', 'wpcf7_mail_sent' ),
            'load the settings'                 => array( 'apply_filters', 'wpcf7_contact_form_properties' ),
            'load the settings, issue #11'      => array( 'apply_filters', 'wpcf7_pre_construct_contact_form_properties' ),
            'special mail tags filled by CF7'   => array( 'add_filter', 'wpcf7_special_mail_tags' ),
        );
    }

    public function test_free_text_field_name() {
        // The plugin reads "_wpcf7_free_text_<name>" (since 5.8.6), or "<name>_free_text" before it.
        $this->assertStringContainsString( "'_wpcf7_free_text_%s'", file_get_contents( self::cf7_file( 'modules/checkbox.php' ) ) );
    }

    /**
     * @dataProvider stubs
     */
    public function test_stubs_only_have_methods_of_contact_form_7( $class, $file ) {
        $source = file_get_contents( self::cf7_file( $file ) );

        foreach ( ( new \ReflectionClass( $class ) )->getMethods( \ReflectionMethod::IS_PUBLIC ) as $method ) {
            if ( '__construct' === $method->name ) {
                continue;
            }

            $static = $method->isStatic() ? 'static\s+' : '';
            $this->assertMatchesRegularExpression( '/public\s+' . $static . 'function\s+' . $method->name . '\s*\(/', $source, "{$class}::{$method->name}()" );
        }
    }

    public function stubs() {
        // The stubs of forms and submissions, used by both suites.
        return array(
            'WPCF7_ContactForm' => array( 'WPCF7_ContactForm', 'includes/contact-form.php' ),
            'WPCF7_Submission'  => array( 'WPCF7_Submission', 'includes/submission.php' ),
        );
    }

    public function test_tag_stubs_only_have_methods_of_contact_form_7() {
        // The tag stubs of the unit suite, compared with the real classes loaded here.
        $source = file_get_contents( dirname( __DIR__ ) . '/stubs/contact-form-7-tags.php' );
        preg_match_all( '/^class (\w+) \{(.*?)^\}/ms', $source, $classes, PREG_SET_ORDER );

        $this->assertNotEmpty( $classes );

        foreach ( $classes as $class ) {
            preg_match_all( '/public function (\w+)\(/', $class[2], $methods );

            foreach ( array_diff( $methods[1], array( '__construct' ) ) as $method ) {
                $this->assertTrue( method_exists( $class[1], $method ), "{$class[1]}::{$method}()" );
            }
        }
    }

    public function test_webhook_option() {
        $tag = $this->scan( '[email* your-email webhook:email]' )[0];

        $this->assertSame( 'your-email', $tag->name );
        $this->assertSame( 'email', $tag->basetype );
        $this->assertSame( array( 'email' ), $tag->get_option( 'webhook' ) );
    }

    public function test_free_text_is_the_last_option() {
        $tag = $this->scan( '[checkbox* how free_text "Google" "Other"]' )[0];

        $this->assertSame( 'checkbox', $tag->basetype );
        $this->assertTrue( $tag->has_option( 'free_text' ) );
        $this->assertSame( 'Other', end( $tag->values ) );
    }

    public function test_pipes() {
        $tag = $this->scan( '[select menu "Sales|sales@example.com" "Help|help@example.com"]' )[0];

        $this->assertSame( array( 'Sales', 'Help' ), $tag->values );
        $this->assertInstanceOf( WPCF7_Pipes::class, $tag->pipes );
        $this->assertSame( 'sales@example.com', $tag->pipes->do_pipe( 'Sales' ) );
        $this->assertTrue( wpcf7_form_tag_supports( $tag->type, 'selectable-values' ) );
    }

    public function test_raw_mail_tag() {
        $mail_tag = new WPCF7_MailTag( '[_raw_menu]', '_raw_menu', '' );

        $this->assertSame( 'menu', $mail_tag->field_name() );
        $this->assertTrue( $mail_tag->get_option( 'do_not_heat' ) );
    }

    /**
     * Every PHP file of Contact Form 7, joined.
     *
     * @return string
     */
    private static function source() {
        static $source = null;

        if ( null === $source ) {
            $source   = '';
            $iterator = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( CFTZ_TESTS_CF7_DIR, \FilesystemIterator::SKIP_DOTS ) );

            foreach ( $iterator as $file ) {
                if ( 'php' === $file->getExtension() ) {
                    $source .= file_get_contents( $file->getPathname() );
                }
            }
        }

        return $source;
    }
}
