<?php
/**
 * Deleting uploaded files: the "Delete all files" button and the daily "Delete after" event.
 *
 * Runs on a temporary folder used as the WordPress uploads folder.
 *
 * @package Cf7_To_Zapier
 */

namespace CFTZ\Tests\Unit;

use Brain\Monkey\Functions;
use CFTZ\Tests\JsonResponse;
use CFTZ\Tests\TestCase;
use CFTZ_Module_CF7;
use Cf7_To_Zapier;
use WPCF7_ContactForm;

class DeleteUploadsTest extends TestCase {

    /**
     * The temporary uploads folder.
     *
     * @var string
     */
    private $uploads;

    /**
     * Arguments of the last get_posts() call.
     *
     * @var array|null
     */
    private $attachment_query = null;

    /**
     * Attachment IDs returned by get_posts().
     *
     * @var int[]
     */
    private $attachments = array();

    /**
     * Attachment IDs passed to wp_delete_attachment().
     *
     * @var int[]
     */
    private $deleted_attachments = array();

    protected function setUp(): void {
        parent::setUp();

        $this->uploads = sys_get_temp_dir() . '/ctz-tests-' . bin2hex( random_bytes( 6 ) );
        mkdir( $this->uploads . '/' . CFTZ_UPLOAD_DIR, 0777, true );

        Functions\when( 'wp_get_upload_dir' )->justReturn(
            array(
                'basedir' => $this->uploads,
                'baseurl' => 'https://example.com/wp-content/uploads',
            )
        );

        Functions\when( 'get_posts' )->alias(
            function( $args ) {
                $this->attachment_query = $args;
                return $this->attachments;
            }
        );

        Functions\when( 'wp_delete_attachment' )->alias(
            function( $id, $force ) {
                $this->assertTrue( $force );
                $this->deleted_attachments[] = $id;
                return true;
            }
        );

        Functions\when( 'wp_delete_file' )->alias( 'unlink' );
    }

    protected function tearDown(): void {
        $_POST                    = array();
        WPCF7_ContactForm::$forms = array();
        $this->remove( $this->uploads );

        parent::tearDown();
    }

    public function test_deletes_submissions_before_the_date() {
        $old = $this->submission( 5, '20260101_' . str_repeat( 'a', 32 ) );
        $new = $this->submission( 5, '20261120_' . str_repeat( 'b', 32 ) );
        file_put_contents( $this->form_dir( 5 ) . '/index.php', '' );

        $this->attachments = array( 11, 12 );

        CFTZ_Module_CF7::delete_uploads( 5, $this->time( '2026-11-01' ) );

        $this->assertDirectoryDoesNotExist( $old );
        $this->assertFileExists( $new . '/file.txt' );
        $this->assertFileExists( $this->form_dir( 5 ) . '/index.php' );

        $this->assertSame( array( 11, 12 ), $this->deleted_attachments );
        $this->assertSame( 5, $this->attachment_query['meta_value'] );
        $this->assertSame( '2026-11-01 00:00:00', $this->attachment_query['date_query'][0]['before'] );
        $this->assertSame( 'post_date_gmt', $this->attachment_query['date_query'][0]['column'] );
    }

    public function test_folders_without_date_use_the_modification_time() {
        // Folders created before 5.3.0 have only the random part.
        $old = $this->submission( 5, str_repeat( 'c', 32 ), $this->time( '2026-01-01' ) );
        $new = $this->submission( 5, str_repeat( 'd', 32 ), $this->time( '2026-11-20' ) );

        CFTZ_Module_CF7::delete_uploads( 5, $this->time( '2026-11-01' ) );

        $this->assertDirectoryDoesNotExist( $old );
        $this->assertDirectoryExists( $new );
    }

    public function test_does_not_follow_links() {
        $outside = $this->uploads . '/outside';
        mkdir( $outside );
        file_put_contents( $outside . '/keep.txt', 'keep' );

        // A link as a submission folder, and a link inside an expired one.
        $link = $this->form_dir( 5 ) . '/20260101_' . str_repeat( 'e', 32 );
        mkdir( $this->form_dir( 5 ), 0777, true );
        symlink( $outside, $link );

        $old = $this->submission( 5, '20260102_' . str_repeat( 'f', 32 ) );
        symlink( $outside, $old . '/link' );

        CFTZ_Module_CF7::delete_uploads( 5, $this->time( '2026-11-01' ) );

        $this->assertTrue( is_link( $link ) );
        $this->assertDirectoryDoesNotExist( $old );
        $this->assertFileExists( $outside . '/keep.txt' );
    }

    public function test_deletes_every_file_of_the_form() {
        $this->submission( 5, '20261120_' . str_repeat( 'a', 32 ) );
        $other = $this->submission( 6, '20260101_' . str_repeat( 'b', 32 ) );

        $this->attachments = array( 21 );

        CFTZ_Module_CF7::delete_uploads( 5 );

        $this->assertDirectoryDoesNotExist( $this->form_dir( 5 ) );
        $this->assertDirectoryExists( $other );
        $this->assertSame( array( 21 ), $this->deleted_attachments );
        $this->assertArrayNotHasKey( 'date_query', $this->attachment_query );
    }

    public function test_never_deletes_outside_the_uploads_folder() {
        $outside = $this->uploads . '/outside';
        mkdir( $outside );
        file_put_contents( $outside . '/keep.txt', 'keep' );
        symlink( $outside, $this->form_dir( 7 ) );

        CFTZ_Module_CF7::delete_uploads( 7 );

        $this->assertFileExists( $outside . '/keep.txt' );
    }

    public function test_daily_event_uses_the_days_of_each_form() {
        $day   = DAY_IN_SECONDS;
        $today = time() - ( time() % $day );

        // "Delete after" 3 days: files are kept for at least 3 full days.
        $expired = $this->submission( 5, gmdate( 'Ymd', $today - 4 * $day ) . '_' . str_repeat( 'a', 32 ) );
        $kept    = $this->submission( 5, gmdate( 'Ymd', $today - 3 * $day ) . '_' . str_repeat( 'b', 32 ) );
        $current = $this->submission( 5, gmdate( 'Ymd', $today ) . '_' . str_repeat( 'c', 32 ) );

        // "Delete after" 0: never.
        $never = $this->submission( 6, '20200101_' . str_repeat( 'd', 32 ) );

        WPCF7_ContactForm::$forms = array(
            new WPCF7_ContactForm( 5, array(), array( CFTZ_Module_CF7::METADATA => array( 'delete_files_after' => '3' ) ) ),
            new WPCF7_ContactForm( 6, array(), array( CFTZ_Module_CF7::METADATA => array( 'delete_files_after' => '0' ) ) ),
            new WPCF7_ContactForm( 8 ),
        );

        ( new CFTZ_Module_CF7( new Cf7_To_Zapier() ) )->delete_expired_uploads();

        $this->assertDirectoryDoesNotExist( $expired );
        $this->assertDirectoryExists( $kept );
        $this->assertDirectoryExists( $current );
        $this->assertDirectoryExists( $never );
    }

    /**
     * @dataProvider forbidden_requests
     */
    public function test_delete_button_needs_an_administrator_and_the_nonce_of_the_form( array $caps, $nonce ) {
        $submission = $this->submission( 5, '20261120_' . str_repeat( 'a', 32 ) );
        $this->attachments = array( 11 );

        $response = $this->delete_button( 5, $nonce, $caps );

        $this->assertFalse( $response->success );
        $this->assertSame( 403, $response->status );
        $this->assertDirectoryExists( $submission );
        $this->assertSame( array(), $this->deleted_attachments );
    }

    public function forbidden_requests() {
        $admin  = array( 'wpcf7_edit_contact_form', 'manage_options' );
        $editor = array( 'wpcf7_edit_contact_form' );

        return array(
            'no nonce'                   => array( $admin, '' ),
            'nonce of another form'      => array( $admin, 'valid-ctz_delete_uploads_6' ),
            'editor with the form nonce' => array( $editor, 'valid-ctz_delete_uploads_5' ),
        );
    }

    public function test_delete_button_needs_a_form() {
        $response = $this->delete_button( 99, 'valid-ctz_delete_uploads_99' );

        $this->assertFalse( $response->success );
        $this->assertSame( 404, $response->status );
    }

    public function test_delete_button_deletes_the_files_of_the_form() {
        $this->submission( 5, '20261120_' . str_repeat( 'a', 32 ) );
        $other = $this->submission( 6, '20261120_' . str_repeat( 'b', 32 ) );

        $response = $this->delete_button( 5, 'valid-ctz_delete_uploads_5' );

        $this->assertTrue( $response->success );
        $this->assertSame( array( 'summary' => 'No files uploaded with this form.' ), $response->data );
        $this->assertDirectoryDoesNotExist( $this->form_dir( 5 ) );
        $this->assertDirectoryExists( $other );
    }

    /**
     * Click "Delete all files": run the AJAX handler and return its JSON response.
     *
     * A valid nonce is "valid-" followed by its action, as wp_create_nonce() would tie it to the form.
     *
     * @param int      $form_id Posted form ID.
     * @param string   $nonce   Posted nonce.
     * @param string[] $caps    Capabilities of the current user.
     * @return JsonResponse
     */
    private function delete_button( $form_id, $nonce, array $caps = array( 'wpcf7_edit_contact_form', 'manage_options' ) ) {
        $_POST = array(
            'form_id' => (string) $form_id,
            'nonce'   => $nonce,
        );

        Functions\when( 'check_ajax_referer' )->alias(
            function( $action, $query_arg, $stop ) {
                $this->assertFalse( $stop );
                return ( $_POST[ $query_arg ] ?? '' ) === 'valid-' . $action;
            }
        );

        Functions\when( 'current_user_can' )->alias(
            function( $cap ) use ( $caps ) {
                return in_array( $cap, $caps, true );
            }
        );

        Functions\when( 'wpcf7_contact_form' )->alias(
            function( $id ) {
                return in_array( $id, array( 5, 6 ), true ) ? new WPCF7_ContactForm( $id ) : null;
            }
        );

        Functions\when( 'wp_send_json_error' )->alias(
            function( $data = null, $status = null ) {
                throw new JsonResponse( false, $data, $status );
            }
        );

        Functions\when( 'wp_send_json_success' )->alias(
            function( $data = null, $status = null ) {
                throw new JsonResponse( true, $data, $status );
            }
        );

        try {
            ( new CFTZ_Module_CF7( new Cf7_To_Zapier() ) )->wp_ajax_ctz_delete_uploads();
        } catch ( JsonResponse $response ) {
            return $response;
        }

        $this->fail( 'The handler did not send a JSON response.' );
    }

    private function form_dir( $form_id ) {
        return $this->uploads . '/' . CFTZ_UPLOAD_DIR . '/' . $form_id;
    }

    /**
     * Create a submission folder with a file.
     *
     * @param int      $form_id Form ID.
     * @param string   $name    Folder name.
     * @param int|null $mtime   Modification time of the folder.
     * @return string The folder path.
     */
    private function submission( $form_id, $name, $mtime = null ) {
        $dir = $this->form_dir( $form_id ) . '/' . $name;

        mkdir( $dir, 0777, true );
        file_put_contents( $dir . '/file.txt', 'file' );

        if ( null !== $mtime ) {
            touch( $dir, $mtime );
        }

        return $dir;
    }

    private function time( $date ) {
        return strtotime( $date . ' 00:00:00 UTC' );
    }

    private function remove( $path ) {
        if ( is_link( $path ) || is_file( $path ) ) {
            unlink( $path );
            return;
        }

        if ( ! is_dir( $path ) ) {
            return;
        }

        foreach ( scandir( $path ) as $item ) {
            if ( '.' !== $item && '..' !== $item ) {
                $this->remove( $path . '/' . $item );
            }
        }

        rmdir( $path );
    }
}
