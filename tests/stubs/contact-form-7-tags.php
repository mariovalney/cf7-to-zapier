<?php
/**
 * Contact Form 7 form tag and mail tag classes, reduced to what the tests need.
 *
 * The unit suite uses them; the Contact Form 7 suite loads the real ones instead.
 *
 * @package Cf7_To_Zapier
 */

class WPCF7_FormTag {

    public $type = '';

    public $basetype = '';

    public $name = '';

    public $options = array();

    public $values = array();

    public $pipes = null;

    public function __construct( array $tag ) {
        foreach ( $tag as $key => $value ) {
            $this->$key = $value;
        }

        if ( empty( $this->type ) ) {
            $this->type = $this->basetype;
        }
    }

    public function has_option( $name ) {
        return false !== $this->get_option( $name );
    }

    public function get_option( $name ) {
        $values = array();

        foreach ( $this->options as $option ) {
            if ( $option === $name ) {
                $values[] = '';
                continue;
            }

            if ( 0 === strpos( $option, $name . ':' ) ) {
                $values[] = substr( $option, strlen( $name ) + 1 );
            }
        }

        return empty( $values ) ? false : $values;
    }
}

class WPCF7_MailTag {

    public $tag;

    public $tagname;

    public $values;

    public function __construct( $tag, $tagname, $values ) {
        $this->tag     = $tag;
        $this->tagname = $tagname;
        $this->values  = $values;
    }
}
