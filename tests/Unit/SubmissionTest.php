<?php
/**
 * A form submission: the data sent, the webhook URLs and the errors.
 *
 * @package Cf7_To_Zapier
 */

namespace CFTZ\Tests\Unit;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use CFTZ\Tests\TestCase;
use CFTZ_Exception;
use CFTZ_Module_CF7;
use CFTZ_Module_Zapier;
use Cf7_To_Zapier;
use WP_Error;
use WPCF7_Submission;

class SubmissionTest extends TestCase {

    const FIELDS = array(
        'your-name'     => 'text',
        'your-hidden'   => 'hidden',
        'your-checkbox' => 'checkbox',
    );

    /**
     * Webhook URLs that got the 'ctz_trigger_webhook' action, in order.
     *
     * @var string[]
     */
    private $triggered = array();

    /**
     * Data of the last 'ctz_trigger_webhook' action.
     *
     * @var array|null
     */
    private $sent = null;

    /**
     * Emails sent with wp_mail().
     *
     * @var array
     */
    private $mails = array();

    protected function setUp(): void {
        parent::setUp();

        WPCF7_Submission::$instance = new WPCF7_Submission();

        $this->mock_http();

        Functions\when( 'wp_get_upload_dir' )->justReturn(
            array(
                'basedir' => sys_get_temp_dir(),
                'baseurl' => 'https://example.com/wp-content/uploads',
            )
        );

        Functions\when( 'wp_mail' )->alias(
            function( $to, $subject, $message ) {
                $this->mails[] = array(
                    'to'      => $to,
                    'message' => $message,
                );

                return true;
            }
        );

        Functions\when( 'wp_specialchars_decode' )->returnArg();
    }

    protected function tearDown(): void {
        $_POST                      = array();
        WPCF7_Submission::$instance = null;

        parent::tearDown();
    }

    /**
     * Submit the form, with the webhook active, and record the 'ctz_trigger_webhook' actions.
     *
     * @param array         $post     Posted fields.
     * @param array         $settings Webhook settings.
     * @param callable|null $webhook  Runs for each webhook, like CFTZ_Module_Zapier::pull_the_trigger().
     */
    private function submit( array $post, array $settings = array(), $webhook = null ) {
        $_POST = $post;

        $settings = array_merge(
            array(
                'activate' => '1',
                'hook_url' => array( 'https://example.com/hook' ),
            ),
            $settings
        );

        Actions\expectDone( 'ctz_trigger_webhook' )
            ->zeroOrMoreTimes()
            ->whenHappen(
                function( $data, $hook_url, $properties, $contact_form ) use ( $webhook ) {
                    $this->triggered[] = $hook_url;
                    $this->sent        = $data;

                    if ( $webhook ) {
                        $webhook( $data, $hook_url, $properties, $contact_form );
                    }
                }
            );

        $form = $this->make_form( self::FIELDS, array( CFTZ_Module_CF7::METADATA => $settings ) );

        ( new CFTZ_Module_CF7( new Cf7_To_Zapier() ) )->wpcf7_mail_sent( $form );
    }

    /**
     * A webhook that fails, for every URL or only one.
     *
     * @param string|null $url Only this URL fails.
     * @return callable
     */
    private function failing_webhook( $url = null ) {
        return function( $data, $hook_url ) use ( $url ) {
            if ( null === $url || $url === $hook_url ) {
                throw new CFTZ_Exception( new WP_Error( 500, 'Webhook returned a error code.' ) );
            }
        };
    }

    public function test_sends_zero_values() {
        // Issue #13: a hidden field with 0 was sent empty.
        $this->submit(
            array(
                'your-hidden' => '0',
                'your-name'   => '',
            )
        );

        $this->assertSame( '0', $this->sent['your-hidden'] );
        $this->assertSame( '', $this->sent['your-name'] );
    }

    public function test_replaces_invalid_utf8() {
        // Issue #23: bytes posted by scripts (browsers always send UTF-8) broke the JSON body.
        $this->submit(
            array(
                'your-name'     => "Caf\xE9",
                'your-checkbox' => array( "Op\xE7\xE3o" ),
            )
        );

        $this->assertTrue( mb_check_encoding( $this->sent['your-name'], 'UTF-8' ) );
        $this->assertTrue( mb_check_encoding( $this->sent['your-checkbox'][0], 'UTF-8' ) );
        $this->assertNotFalse( json_encode( $this->sent ) );
    }

    public function test_encodes_fields_in_the_webhook_url() {
        $this->submit(
            array(
                'your-name'     => 'Ana / Bia?',
                'your-checkbox' => array( 'Google', 'Friend' ),
            ),
            array( 'hook_url' => array( 'https://example.com/hook/[your-name]?how=[your-checkbox]' ) )
        );

        $this->assertSame( array( 'https://example.com/hook/Ana+%2F+Bia%3F?how=Google%7CFriend' ), $this->triggered );
    }

    public function test_stops_at_the_first_webhook_that_fails() {
        // Issue #19: the next webhooks are not sent and the form shows an error.
        Actions\expectDone( 'ctz_trigger_webhook_errors' )->once();

        $this->submit(
            array(),
            array( 'hook_url' => array( 'https://example.com/one', 'https://example.com/two' ) ),
            $this->failing_webhook()
        );

        $this->assertSame( array( 'https://example.com/one' ), $this->triggered );
        $this->assertSame( 'mail_failed', WPCF7_Submission::$instance->status );
        $this->assertSame( 'There was an error trying to send your message.', WPCF7_Submission::$instance->response );
        $this->assertCount( 1, $this->mails );
    }

    public function test_empty_error_message_sends_the_next_webhooks() {
        // Issue #19: how to make multiple webhooks optional.
        Filters\expectApplied( 'ctz_trigger_webhook_error_message' )->once()->andReturn( '' );

        $this->submit(
            array(),
            array( 'hook_url' => array( 'https://example.com/one', 'https://example.com/two' ) ),
            $this->failing_webhook( 'https://example.com/one' )
        );

        $this->assertSame( array( 'https://example.com/one', 'https://example.com/two' ), $this->triggered );
        $this->assertNull( WPCF7_Submission::$instance->status );
        $this->assertCount( 1, $this->mails );
    }

    public function test_error_email_hides_secret_headers() {
        // Error notification (issue #15), with secret headers hidden since 5.1.0.
        $this->response = 500;

        $this->submit(
            array( 'your-name' => 'Ana' ),
            array(
                'custom_headers' => "Authorization: Bearer secret-token\nX-Api-Key: secret-key\nX-Request-Id: visible-id",
                'error_mails'    => array( 'dev@example.com' ),
            ),
            function( $data, $hook_url, $properties, $contact_form ) {
                ( new CFTZ_Module_Zapier( new Cf7_To_Zapier() ) )->pull_the_trigger( $data, $hook_url, $properties, $contact_form );
            }
        );

        $this->assertCount( 1, $this->mails );
        $this->assertSame( array( 'dev@example.com' ), $this->mails[0]['to'] );

        $message = $this->mails[0]['message'];
        $this->assertStringContainsString( 'https://example.com/hook', $message );
        $this->assertStringContainsString( '"Authorization":"***REDACTED***"', $message );
        $this->assertStringContainsString( '"X-Api-Key":"***REDACTED***"', $message );
        $this->assertStringContainsString( 'visible-id', $message );
        $this->assertStringNotContainsString( 'secret-token', $message );
        $this->assertStringNotContainsString( 'secret-key', $message );
    }

    public function test_inactive_webhook_sends_nothing() {
        $this->submit( array( 'your-name' => 'Ana' ), array( 'activate' => '0' ) );

        $this->assertSame( array(), $this->triggered );
    }
}
