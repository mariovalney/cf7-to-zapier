<?php

/**
 * A helper to debug
 *
 * @package         Cf7_To_Zapier
 * @since           2.3.0
 */

defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

/**
 * Emulate dd function from laravel
 *
 * @SuppressWarnings(PHPMD)
 */
if ( ! function_exists( 'dd' ) ) {
    function dd( $param, $include_pre = true ) {
        echo $include_pre ? '<pre>' : '';
        print_r( $param );
        echo $include_pre ? '</pre>' : '';
        exit;
    }
}

/**
 * Emulate dump function from
 * laravel but write to logs
 */
if ( ! function_exists( 'dump' ) ) {
    function dump( $param ) {
        error_log( $param );
    }
}

