<?php
/**
 * The input helpers of the webhook panel escape what they print.
 *
 * Settings are typed by users who edit forms, and the panel shows them to every
 * user who opens the form, administrators included. XSS fixed in 5.1.0.
 *
 * @package Cf7_To_Zapier
 */

namespace CFTZ\Tests\Unit;

use Brain\Monkey\Functions;
use CFTZ\Tests\TestCase;

class AdminInputsTest extends TestCase {

    const ATTACK = '"><script>alert(1)</script>';

    const ESCAPED = '&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;';

    protected function setUp(): void {
        parent::setUp();

        foreach ( array( 'checked', 'selected' ) as $helper ) {
            Functions\when( $helper )->alias(
                function( $value, $current = true, $display = true ) use ( $helper ) {
                    return (string) $value === (string) $current ? " $helper='$helper'" : '';
                }
            );
        }

        Functions\when( 'disabled' )->alias(
            function( $value, $current = true, $display = true ) {
                return (string) $value === (string) $current ? " disabled='disabled'" : '';
            }
        );
    }

    private function render( callable $helper, array $args ) {
        ob_start();
        call_user_func_array( $helper, $args );

        return ob_get_clean();
    }

    /**
     * @dataProvider inputs
     */
    public function test_escapes_values( $helper, array $args ) {
        $html = $this->render( $helper, $args );

        $this->assertStringNotContainsString( '<script>', $html );
        $this->assertStringContainsString( self::ESCAPED, $html );
    }

    public function inputs() {
        return array(
            'text'            => array( 'ctz_text_input', array( 'hook_url', self::ATTACK ) ),
            'text from list'  => array( 'ctz_text_input', array( 'error_mails', array( 'a@example.com', self::ATTACK ) ) ),
            'number'          => array( 'ctz_number_input', array( 'delete_files_after', self::ATTACK ) ),
            'textarea'        => array( 'ctz_textarea_input', array( 'custom_body', '</textarea>' . self::ATTACK ) ),
            'textarea list'   => array( 'ctz_textarea_input', array( 'hook_url', array( 'https://example.com', self::ATTACK ) ) ),
            'select value'    => array( 'ctz_select_input', array( 'custom_method', self::ATTACK, array( self::ATTACK => 'Label' ) ) ),
            'select label'    => array( 'ctz_select_input', array( 'custom_method', 'POST', array( 'POST' => self::ATTACK ) ) ),
        );
    }

    public function test_textarea_cannot_be_closed_by_its_value() {
        $html = $this->render( 'ctz_textarea_input', array( 'custom_body', '</textarea><p>after</p>' ) );

        $this->assertSame( 1, substr_count( $html, '</textarea>' ) );
        $this->assertStringNotContainsString( '<p>', $html );
    }

    public function test_escapes_the_key() {
        $html = $this->render( 'ctz_checkbox_input', array( 'activate" onclick="alert(1)', '1' ) );

        $this->assertStringNotContainsString( 'onclick="', $html );
        $this->assertStringContainsString( "checked='checked'", $html );
    }
}
