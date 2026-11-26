<?php
/**
 * The core class of the plugin, without the bootstrap of cf7-to-zapier.php.
 *
 * The modules use it to register their hooks, which it only records.
 *
 * @package Cf7_To_Zapier
 */

class Cf7_To_Zapier {

    /**
     * Hooks registered by the modules: { type, hook, callback, priority, accepted_args }.
     *
     * @var array
     */
    public $hooks = array();

    public function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
        $this->hooks[] = array( 'action', $hook, $callback, $priority, $accepted_args );
    }

    public function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
        $this->hooks[] = array( 'filter', $hook, $callback, $priority, $accepted_args );
    }
}
