<?php
/**
 * WordPress classes used by the plugin, reduced to what the tests need.
 *
 * @package Cf7_To_Zapier
 */

class WP_Error {

    public $errors = array();

    public $error_data = array();

    public function __construct( $code = '', $message = '', $data = '' ) {
        if ( '' === $code ) {
            return;
        }

        $this->add( $code, $message, $data );
    }

    public function add( $code, $message, $data = '' ) {
        $this->errors[ $code ][] = $message;

        if ( ! empty( $data ) ) {
            $this->error_data[ $code ] = $data;
        }
    }

    public function add_data( $data, $code = '' ) {
        if ( '' === $code ) {
            $code = $this->get_error_code();
        }

        $this->error_data[ $code ] = $data;
    }

    public function get_error_code() {
        $codes = array_keys( $this->errors );

        return $codes[0] ?? '';
    }

    public function get_error_message( $code = '' ) {
        if ( '' === $code ) {
            $code = $this->get_error_code();
        }

        return $this->errors[ $code ][0] ?? '';
    }

    public function get_error_data( $code = '' ) {
        if ( '' === $code ) {
            $code = $this->get_error_code();
        }

        return $this->error_data[ $code ] ?? null;
    }
}
