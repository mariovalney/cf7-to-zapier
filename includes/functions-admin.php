<?php

/**
 * A helper to admin UI
 *
 * @package         Cf7_To_Zapier
 * @since           4.0.0
 */

defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

/**
 * Create a text input
 */
if ( ! function_exists( 'ctz_text_input' ) ) {
    function ctz_text_input( $key, $value ) {
        if ( is_array( $value ) ) {
            $value = implode( ',', $value );
        }

        echo '<input class="large-text" type="text" id="ctz-webhook-' . $key . '" name="ctz-webhook-' . $key . '" value="' . esc_attr( $value ) . '">';
    }
}

/**
 * Create a checkbox input
 */
if ( ! function_exists( 'ctz_checkbox_input' ) ) {
    function ctz_checkbox_input( $key, $value ) {
        echo '<input type="checkbox" id="ctz-webhook-' . $key . '" name="ctz-webhook-' . $key . '" value="1" ' . checked( $value, '1', false ) . '>';
    }
}

/**
 * Create a textarea input
 */
if ( ! function_exists( 'ctz_textarea_input' ) ) {
    function ctz_textarea_input( $key, $value ) {
        if ( is_array( $value ) ) {
            $value = implode( PHP_EOL, $value );
        }

        $value = esc_textarea( $value );
        $rows = ( (int) substr_count( $value, "\n" ) ) + 2;
        $rows = max( $rows, 4 );

        echo '<textarea id="ctz-webhook-' . $key . '" name="ctz-webhook-' . $key . '" rows="' . $rows . '" class="large-text code">' . $value . '</textarea>';
    }
}

/**
 * Create a select input
 */
if ( ! function_exists( 'ctz_select_input' ) ) {
    function ctz_select_input( $key, $value, $options ) {
        echo '<select id="ctz-webhook-' . $key . '" name="ctz-webhook-' . $key . '" class="select2">';

        foreach ( $options as $key => $label ) {
            if ( is_numeric( $key ) ) {
                $key = $label;
            }

            echo '<option value="' . $key . '" ' . selected( $key, $value, false ) . '>' . $label . '</option>';
        }

        echo '</select>';
    }
}
