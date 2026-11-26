<?php
/**
 * The core class of the plugin, without the bootstrap of cf7-to-zapier.php.
 *
 * The modules only need it to register hooks, which the tests call directly.
 *
 * @package Cf7_To_Zapier
 */

class Cf7_To_Zapier {

    public function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {}

    public function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {}
}
