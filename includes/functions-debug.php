<?php

/**
 * A helper to debug
 *
 * @package         Cf7_To_Zapier
 * @since           2.3.0
 */

defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

/**
 * Check we are working
 */
if ( ! function_exists( 'ctz_is_developing' ) ) {
    function ctz_is_developing() {
        if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
            return false;
        }

        return true;
    }
}

function cftz_activated_debug_functions() {
    return ctz_is_developing() && ! ( defined( 'CFTZ_REMOVE_DEBUG_FUNCTIONS' ) && CFTZ_REMOVE_DEBUG_FUNCTIONS );
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

/**
 * Create a log table of data
 */
if ( ! function_exists( 'dump_table' ) && cftz_activated_debug_functions() ) {
    function dump_table( $data, $line_limit = 100 ) {
        if ( empty( $data ) ) {
            dump( var_dump( $data ) );
            return;
        }

        $max_line_length = 0;
        $max_key_length = max( array_map( 'strlen', array_keys( $data ) ) );
        $line_limit = max( $max_key_length, $line_limit );

        $output = [];
        foreach ( $data as $key => $value ) {
            $value = (string) $value;

            $wrapped_values = wordwrap( $value, $line_limit, PHP_EOL, true );
            $lines = explode( PHP_EOL, $wrapped_values );

            // Max line length
            $max_line_length  = max( $max_line_length, max( array_map( 'strlen', $lines ) ) );

            $row = '';
            $middle_line = (int) floor( count( $lines ) / 2 );

            foreach ( $lines as $line_key => $line ) {
                $left_column = '';
                if ( $line_key === $middle_line ) {
                    $left_column = $key;
                }

                $row .= str_pad( $left_column, $max_key_length ) . ' | ' . $line . PHP_EOL;
            }

            // Separator
            $output[] = $row;
        }

        $separator = str_repeat( '-', $max_key_length ) . ' | ' . str_repeat( '-', $max_line_length ) . PHP_EOL;
        dump( PHP_EOL . PHP_EOL . implode( $separator, $output ) );
    }
}

