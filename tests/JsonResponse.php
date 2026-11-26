<?php
/**
 * Thrown by the wp_send_json_success() and wp_send_json_error() mocks.
 *
 * WordPress stops the request after sending JSON, and code after the call must not run.
 * The exception stops the test the same way and carries what would be sent.
 *
 * @package Cf7_To_Zapier
 */

namespace CFTZ\Tests;

class JsonResponse extends \Exception {

    /**
     * @var bool
     */
    public $success;

    /**
     * @var mixed
     */
    public $data;

    /**
     * @var int|null
     */
    public $status;

    public function __construct( $success, $data = null, $status = null ) {
        parent::__construct( $success ? 'JSON success' : 'JSON error' );

        $this->success = $success;
        $this->data    = $data;
        $this->status  = $status;
    }
}
