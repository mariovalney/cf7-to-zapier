<?php

function ctz_log( $message, $file = 'errors.log' ) {
    error_log( $message . "\n", 3, CFTZ_PLUGIN_PATH . '/' . $file );
}