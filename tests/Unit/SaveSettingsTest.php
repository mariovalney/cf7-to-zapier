<?php
/**
 * Saving the webhook settings of a form.
 *
 * @package Cf7_To_Zapier
 */

namespace CFTZ\Tests\Unit;

use Brain\Monkey\Functions;
use CFTZ\Tests\TestCase;
use CFTZ_Module_CF7;
use Cf7_To_Zapier;

class SaveSettingsTest extends TestCase {

    /**
     * Capabilities of the current user.
     *
     * @var string[]
     */
    private $caps = array( 'wpcf7_edit_contact_form', 'manage_options' );

    protected function setUp(): void {
        parent::setUp();

        Functions\when( 'current_user_can' )->alias(
            function( $cap ) {
                return in_array( $cap, $this->caps, true );
            }
        );

        Functions\when( 'sanitize_text_field' )->alias(
            function( $value ) {
                return trim( (string) $value );
            }
        );

        Functions\when( 'sanitize_textarea_field' )->alias(
            function( $value ) {
                return (string) $value;
            }
        );

        Functions\when( 'esc_url_raw' )->returnArg();
    }

    protected function tearDown(): void {
        $_POST = array();

        parent::tearDown();
    }

    /**
     * Save the form with these fields posted and return its webhook settings.
     *
     * @param array $post   Setting key => posted value.
     * @param array $stored Settings saved before.
     * @return array
     */
    private function save( array $post, array $stored = array() ) {
        $form = $this->make_form( array(), array( CFTZ_Module_CF7::METADATA => $stored ) );

        foreach ( $post as $key => $value ) {
            $_POST[ 'ctz-webhook-' . $key ] = $value;
        }

        ( new CFTZ_Module_CF7( new Cf7_To_Zapier() ) )->wpcf7_save_contact_form( $form );

        return $form->prop( CFTZ_Module_CF7::METADATA );
    }

    /**
     * @dataProvider days
     */
    public function test_delete_after_is_a_whole_number_from_zero( $posted, $expected ) {
        $settings = $this->save( array( 'delete_files_after' => $posted ) );

        $this->assertSame( $expected, $settings['delete_files_after'] );
    }

    public function days() {
        return array(
            'days'     => array( '30', '30' ),
            'zero'     => array( '0', '0' ),
            'negative' => array( '-5', '0' ),
            'decimal'  => array( '2.9', '2' ),
            'text'     => array( 'abc', '0' ),
            'empty'    => array( '', '0' ),
        );
    }

    public function test_only_administrators_change_delete_after() {
        $this->caps = array( 'wpcf7_edit_contact_form' );

        $settings = $this->save( array( 'delete_files_after' => '1' ), array( 'delete_files_after' => '30' ) );

        $this->assertSame( '30', $settings['delete_files_after'] );
    }

    public function test_users_who_cannot_edit_the_form_save_nothing() {
        $this->caps = array();

        $settings = $this->save( array( 'custom_body' => '{ "a": 1 }' ), array( 'custom_body' => '{ "b": 2 }' ) );

        $this->assertSame( array( 'custom_body' => '{ "b": 2 }' ), $settings );
    }

    public function test_checkboxes_are_one_or_zero() {
        $settings = $this->save(
            array(
                'activate'  => '1',
                'send_mail' => 'yes',
            )
        );

        $this->assertSame( '1', $settings['activate'] );
        $this->assertSame( '0', $settings['send_mail'] );
        $this->assertSame( '0', $settings['add_to_library'] );
    }

    public function test_webhook_urls_are_one_per_line() {
        $settings = $this->save( array( 'hook_url' => "https://example.com/[your-name]\n\nhttps://example.org/hook" ) );

        $this->assertSame( array( 'https://example.com/[your-name]', 'https://example.org/hook' ), array_values( $settings['hook_url'] ) );
    }

    public function test_accepted_statuses_fall_back_to_the_defaults() {
        $this->assertSame( array( 200, 201 ), array_values( $this->save( array( 'accepted_statuses' => '200, 201, abc' ) )['accepted_statuses'] ) );
        $this->assertSame( array( 200, 201, 202, 204, 205 ), $this->save( array( 'accepted_statuses' => 'abc' ) )['accepted_statuses'] );
    }

    public function test_error_mails_keep_only_valid_addresses() {
        $settings = $this->save( array( 'error_mails' => 'one@example.com,not-an-email,one@example.com' ) );

        $this->assertSame( array( 'one@example.com' ), array_values( $settings['error_mails'] ) );
    }
}
