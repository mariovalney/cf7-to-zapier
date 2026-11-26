<?php
/**
 * Base class of the unit tests.
 *
 * @package Cf7_To_Zapier
 */

namespace CFTZ\Tests;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

abstract class TestCase extends PHPUnitTestCase {

    use MockeryPHPUnitIntegration;

    /**
     * Values returned by get_option().
     *
     * @var array
     */
    protected $options = array(
        'admin_email'  => 'admin@example.com',
        'blog_charset' => 'UTF-8',
        'blogname'     => 'Example',
    );

    /**
     * Arguments of the last request sent with wp_safe_remote_request().
     *
     * @var array|null
     */
    protected $request = null;

    /**
     * What wp_safe_remote_request() returns: a status code or a \WP_Error.
     *
     * @var int|\WP_Error
     */
    protected $response = 200;

    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();

        Functions\stubTranslationFunctions();
        Functions\stubEscapeFunctions();

        Functions\when( 'get_option' )->alias(
            function( $name, $default = false ) {
                return $this->options[ $name ] ?? $default;
            }
        );
    }

    protected function tearDown(): void {
        Monkey\tearDown();
        parent::tearDown();
    }

    /**
     * A form with the given fields.
     *
     * @param array $fields     Field name => basetype.
     * @param array $properties Form properties.
     * @param int   $id         Form ID.
     * @return \WPCF7_ContactForm
     */
    protected function make_form( array $fields, array $properties = array(), $id = 5 ) {
        $tags = array();

        foreach ( $fields as $name => $basetype ) {
            $tags[] = new \WPCF7_FormTag(
                array(
                    'name'     => $name,
                    'basetype' => $basetype,
                )
            );
        }

        return new \WPCF7_ContactForm( $id, $tags, $properties );
    }

    /**
     * Send a submission with CFTZ_Module_Zapier::pull_the_trigger() and capture the request.
     *
     * @param array                   $data       Form data.
     * @param array                   $properties Webhook settings, merged with the defaults.
     * @param \WPCF7_ContactForm|null $form       The form.
     * @return array|null The request arguments, or null when nothing was sent.
     */
    protected function trigger( array $data, array $properties = array(), $form = null ) {
        $this->mock_http();

        $form       = $form ?? $this->make_form( array() );
        $properties = array_merge( \CFTZ_Module_CF7::get_form_properties(), $properties );
        $zapier     = new \CFTZ_Module_Zapier( new \Cf7_To_Zapier() );

        $zapier->pull_the_trigger( $data, 'https://example.com/hook', $properties, $form );

        return $this->request;
    }

    /**
     * Mock the WordPress HTTP API: requests are captured in $request and get $response.
     */
    protected function mock_http() {
        Functions\when( 'wp_safe_remote_request' )->alias(
            function( $url, $args ) {
                $this->request = $args;

                if ( $this->response instanceof \WP_Error ) {
                    return $this->response;
                }

                return array( 'response' => array( 'code' => $this->response ) );
            }
        );

        Functions\when( 'wp_remote_retrieve_response_code' )->alias(
            function( $response ) {
                return is_array( $response ) ? $response['response']['code'] : '';
            }
        );

        Functions\stubs(
            array(
                'wp_remote_retrieve_response_message' => '',
                'wp_remote_retrieve_headers'          => array(),
                'wp_remote_retrieve_body'             => '',
            )
        );
    }
}
