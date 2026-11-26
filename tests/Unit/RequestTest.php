<?php
/**
 * The webhook request: method, statuses, errors and filters.
 *
 * @package Cf7_To_Zapier
 */

namespace CFTZ\Tests\Unit;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use CFTZ\Tests\TestCase;
use CFTZ_Exception;
use Mockery;
use WP_Error;

class RequestTest extends TestCase {

    const DATA = array( 'your-name' => 'Ana' );

    /**
     * @dataProvider methods
     */
    public function test_allows_only_known_methods( $method, $expected ) {
        $request = $this->trigger( self::DATA, array( 'custom_method' => $method ) );

        $this->assertSame( $expected, $request['method'] );
    }

    public function methods() {
        return array(
            'POST'               => array( 'POST', 'POST' ),
            'lowercase put'      => array( 'put', 'PUT' ),
            'DELETE'             => array( 'DELETE', 'DELETE' ),
            'unknown is POST'    => array( 'TRACE', 'POST' ),
            'injection is POST'  => array( "POST\r\nX-Injected: 1", 'POST' ),
        );
    }

    public function test_get_sends_the_body_as_query_params() {
        $request = $this->trigger(
            self::DATA,
            array(
                'custom_method' => 'GET',
                'custom_body'   => '{ "name": "[your-name]" }',
            )
        );

        $this->assertSame( array( 'name' => 'Ana' ), $request['body'] );
    }

    public function test_get_without_json_body_throws() {
        $this->expectException( CFTZ_Exception::class );
        $this->expectExceptionMessage( 'body is not a JSON' );

        $this->trigger(
            self::DATA,
            array(
                'custom_method' => 'GET',
                'custom_body'   => 'name=[your-name]',
            )
        );
    }

    /**
     * @dataProvider accepted_statuses
     */
    public function test_accepts_success_statuses( $status ) {
        $this->response = $status;

        $this->assertNotNull( $this->trigger( self::DATA ) );
    }

    public function accepted_statuses() {
        return array( array( 200 ), array( 201 ), array( 202 ), array( 204 ), array( 205 ) );
    }

    public function test_other_statuses_throw() {
        $this->response = 500;

        $this->expectException( CFTZ_Exception::class );
        $this->expectExceptionMessage( 'Webhook returned a error code.' );

        $this->trigger( self::DATA );
    }

    public function test_accepted_statuses_come_from_the_settings() {
        $this->response = 500;

        $this->assertNotNull( $this->trigger( self::DATA, array( 'accepted_statuses' => array( 500 ) ) ) );
    }

    public function test_request_errors_throw_with_the_request() {
        $this->response = new WP_Error( 'http_request_failed', 'cURL error 28: timed out' );

        try {
            $this->trigger( self::DATA );
            $this->fail( 'No exception thrown.' );
        } catch ( CFTZ_Exception $exception ) {
            $this->assertSame( 'cURL error 28: timed out', $exception->getMessage() );
            $this->assertSame( 'POST', $exception->get_request_method() );
        }
    }

    public function test_result_action_runs_on_errors() {
        // Issue #18: it did not run when the webhook failed.
        $this->response = 500;

        Actions\expectDone( 'ctz_post_request_result' )
            ->once()
            ->with( array( 'response' => array( 'code' => 500 ) ), 'https://example.com/hook' );

        $this->expectException( CFTZ_Exception::class );

        $this->trigger( self::DATA );
    }

    public function test_ignore_errors_filter_skips_the_error() {
        $this->response = 500;

        Filters\expectApplied( 'ctz_post_request_ignore_errors' )->once()->andReturn( true );

        $this->assertNotNull( $this->trigger( self::DATA ) );
    }

    public function test_ignore_default_webhook_filter_sends_nothing() {
        Filters\expectApplied( 'ctz_ignore_default_webhook' )->once()->andReturn( true );

        $this->assertNull( $this->trigger( self::DATA ) );
    }

    public function test_request_args_filter_gets_the_form_data() {
        $form = $this->make_form( array( 'your-name' => 'text' ) );

        Filters\expectApplied( 'ctz_post_request_args' )
            ->once()
            ->with( Mockery::type( 'array' ), Mockery::type( 'array' ), $form, 'https://example.com/hook', self::DATA )
            ->andReturnUsing(
                function( $args ) {
                    $args['timeout'] = 5;
                    return $args;
                }
            );

        $request = $this->trigger( self::DATA, array(), $form );

        $this->assertSame( 5, $request['timeout'] );
    }

    public function test_data_that_cannot_be_encoded_throws() {
        // Invalid UTF-8. Issue #23.
        $this->expectException( CFTZ_Exception::class );
        $this->expectExceptionMessage( 'Form data could not be encoded as JSON' );

        $this->trigger( array( 'your-name' => "\xB1\x31" ) );
    }
}
