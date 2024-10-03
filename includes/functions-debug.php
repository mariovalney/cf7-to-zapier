<?php

/**
 * A helper to debug
 *
 * @package         Cf7_To_ActionNetwork
 * @since           2.3.0
 */

defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

function cftz_activated_debug_functions() {
    return ( defined( 'WP_DEBUG' ) && WP_DEBUG ) && ! ( defined( 'CFTZ_REMOVE_DEBUG_FUNCTIONS' ) && CFTZ_REMOVE_DEBUG_FUNCTIONS );
}

/**
 * Emulate dd function from laravel
 *
 * @SuppressWarnings(PHPMD)
 */
if ( ! function_exists( 'dd' ) && cftz_activated_debug_functions() ) {
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
if ( ! function_exists( 'dump' ) && cftz_activated_debug_functions() ) {
    function dump( $param ) {
        error_log( $param );
    }
}

