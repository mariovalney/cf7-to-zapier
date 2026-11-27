<?php
/**
 * Bootstrap for the Contact Form 7 suite.
 *
 * Like the unit suite, WordPress is not loaded, but the form tag code of a real
 * Contact Form 7 is: its form tags, mail tags and pipes, and its source to check
 * the hooks the plugin uses. Forms and submissions still use the stubs, since they
 * need the database.
 *
 * Contact Form 7 comes from CF7_DIR, or from the plugin folder next to this one.
 *
 * @package Cf7_To_Zapier
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'DAY_IN_SECONDS', 86400 );
define( 'CFTZ_UPLOAD_DIR', 'cf7-to-webhook-uploads' );
define( 'WPCF7_USE_PIPE', true );

$plugin_dir = dirname( __DIR__ );
$cf7_dir    = getenv( 'CF7_DIR' ) ?: dirname( $plugin_dir ) . '/contact-form-7';

if ( ! is_file( $cf7_dir . '/wp-contact-form-7.php' ) ) {
    fwrite( STDERR, "Contact Form 7 not found in {$cf7_dir}. Set CF7_DIR to its folder.\n" );
    exit( 1 );
}

define( 'CFTZ_TESTS_CF7_DIR', realpath( $cf7_dir ) );

preg_match( '/^\s*\*\s*Version:\s*(\S+)/m', file_get_contents( $cf7_dir . '/wp-contact-form-7.php' ), $version );
echo 'Contact Form 7 ' . ( $version[1] ?? '(unknown version)' ) . ' from ' . CFTZ_TESTS_CF7_DIR . "\n\n";

require $plugin_dir . '/vendor/autoload.php';

require __DIR__ . '/stubs/wordpress.php';
require __DIR__ . '/stubs/contact-form-7.php';
require __DIR__ . '/stubs/plugin.php';

require $plugin_dir . '/includes/functions-debug.php';
require $plugin_dir . '/includes/functions-admin.php';
require $plugin_dir . '/includes/class-exception.php';
require $plugin_dir . '/modules/cf7/class-module-cf7.php';
require $plugin_dir . '/modules/zapier/class-module-zapier.php';
