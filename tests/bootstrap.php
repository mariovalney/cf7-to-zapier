<?php
/**
 * Bootstrap for the unit tests.
 *
 * WordPress and Contact Form 7 are not loaded: each test mocks the functions it
 * needs with Brain Monkey, and tests/stubs has small versions of their classes.
 *
 * @package Cf7_To_Zapier
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'DAY_IN_SECONDS', 86400 );
define( 'CFTZ_UPLOAD_DIR', 'cf7-to-webhook-uploads' );
define( 'WPCF7_USE_PIPE', true );

$plugin_dir = dirname( __DIR__ );

require $plugin_dir . '/vendor/autoload.php';

require __DIR__ . '/stubs/wordpress.php';
require __DIR__ . '/stubs/contact-form-7.php';
require __DIR__ . '/stubs/plugin.php';

require $plugin_dir . '/includes/functions-debug.php';
require $plugin_dir . '/includes/functions-admin.php';
require $plugin_dir . '/includes/class-exception.php';
require $plugin_dir . '/modules/cf7/class-module-cf7.php';
require $plugin_dir . '/modules/zapier/class-module-zapier.php';
