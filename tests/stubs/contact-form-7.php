<?php
/**
 * Contact Form 7 form and submission classes, reduced to what the tests need.
 *
 * @package Cf7_To_Zapier
 */

class WPCF7_ContactForm {

    /**
     * Forms returned by find().
     *
     * @var WPCF7_ContactForm[]
     */
    public static $forms = array();

    private $id;

    private $tags;

    private $properties;

    public function __construct( $id, array $tags = array(), array $properties = array() ) {
        $this->id         = $id;
        $this->tags       = $tags;
        $this->properties = $properties;
    }

    public static function find() {
        return self::$forms;
    }

    public function id() {
        return $this->id;
    }

    public function title() {
        return 'Contact form ' . $this->id;
    }

    public function message( $status ) {
        $messages = array(
            'mail_sent_ng'  => 'There was an error trying to send your message.',
            'upload_failed' => 'There was an unknown error uploading the file.',
        );

        return $messages[ $status ] ?? '';
    }

    public function scan_form_tags() {
        return $this->tags;
    }

    public function prop( $name ) {
        return $this->properties[ $name ] ?? null;
    }

    public function get_properties() {
        return $this->properties;
    }

    public function set_properties( $properties ) {
        $this->properties = $properties;
    }
}

class WPCF7_Submission {

    /**
     * The current submission, returned by get_instance().
     *
     * @var WPCF7_Submission|null
     */
    public static $instance = null;

    public $status = null;

    public $response = null;

    /**
     * Uploaded files: field name => list of paths, as Contact Form 7 keeps them.
     *
     * @var array
     */
    private $files;

    public function __construct( array $files = array() ) {
        $this->files = $files;
    }

    public static function get_instance() {
        return self::$instance;
    }

    public function uploaded_files() {
        return $this->files;
    }

    public function set_status( $status ) {
        $this->status = $status;
    }

    public function set_response( $response ) {
        $this->response = $response;
    }
}
