<?php
/**
 * Custom headers and the form fields in them.
 *
 * @package Cf7_To_Zapier
 */

namespace CFTZ\Tests\Unit;

use Brain\Monkey\Filters;
use CFTZ\Tests\TestCase;
use CFTZ_Module_Zapier;
use Cf7_To_Zapier;
use Mockery;

class HeadersTest extends TestCase {

    private function headers( $custom, $is_json = true, $data = array() ) {
        $zapier = new CFTZ_Module_Zapier( new Cf7_To_Zapier() );

        return $zapier->create_headers( $custom, $is_json, $data );
    }

    public function test_content_type() {
        $this->assertSame( array( 'Content-Type' => 'application/json; charset=UTF-8' ), $this->headers( '' ) );
        $this->assertSame( array( 'Content-Type' => 'text/plain; charset=UTF-8' ), $this->headers( '', false ) );
    }

    public function test_content_type_without_charset() {
        $this->options['blog_charset'] = '';

        $this->assertSame( array( 'Content-Type' => 'application/json' ), $this->headers( '' ) );
    }

    public function test_parses_one_header_per_line() {
        $headers = $this->headers( "Authorization: Bearer abc\nNo colon here\n  X-Spaces  :  value  \nX-Url: https://example.com/?a=b:c" );

        $this->assertSame( 'Bearer abc', $headers['Authorization'] );
        $this->assertSame( 'value', $headers['X-Spaces'] );
        $this->assertSame( 'https://example.com/?a=b:c', $headers['X-Url'] );
        $this->assertCount( 4, $headers );
    }

    public function test_replaces_fields() {
        $headers = $this->headers(
            "X-Name: [your-name]\nX-Options: [your-checkbox]\nX-Missing: [nope]",
            true,
            array(
                'your-name'     => 'Ana',
                'your-checkbox' => array( 'Google', 'Friend' ),
            )
        );

        $this->assertSame( 'Ana', $headers['X-Name'] );
        $this->assertSame( 'Google, Friend', $headers['X-Options'] );
        $this->assertSame( '', $headers['X-Missing'] );
    }

    public function test_replaces_special_mail_tags() {
        Filters\expectApplied( 'wpcf7_special_mail_tags' )
            ->once()
            ->with( '', '_date', false, Mockery::type( 'WPCF7_MailTag' ) )
            ->andReturn( 'November 26, 2026' );

        $headers = $this->headers( 'X-Date: [_date]' );

        $this->assertSame( 'November 26, 2026', $headers['X-Date'] );
    }

    /**
     * @dataProvider control_characters
     */
    public function test_form_data_cannot_add_headers( $value, $expected ) {
        $headers = $this->headers( 'X-Name: [your-name]', true, array( 'your-name' => $value ) );

        $this->assertSame( $expected, $headers['X-Name'] );
        $this->assertCount( 2, $headers );
    }

    public function control_characters() {
        // Header injection, fixed in 5.2.0.
        return array(
            'CRLF'                => array( "Ana\r\nX-Injected: 1", 'Ana X-Injected: 1' ),
            'LF'                  => array( "Ana\nX-Injected: 1", 'Ana X-Injected: 1' ),
            'null byte and DEL'   => array( "Ana\0\x7FBia", 'Ana Bia' ),
            'tab'                 => array( "Ana\tBia", 'Ana Bia' ),
            'in a checkbox value' => array( array( "Google\r\nX-Injected: 1", 'Friend' ), 'Google X-Injected: 1, Friend' ),
        );
    }

    public function test_special_mail_tags_cannot_add_headers() {
        Filters\expectApplied( 'wpcf7_special_mail_tags' )->once()->andReturn( "Ana\r\nX-Injected: 1" );

        $headers = $this->headers( 'X-Name: [_post_author]' );

        $this->assertSame( 'Ana X-Injected: 1', $headers['X-Name'] );
    }
}
