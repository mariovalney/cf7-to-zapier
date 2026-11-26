<?php
/**
 * Deleting uploaded files: "Delete all files" and the daily "Delete after" event.
 *
 * Runs on a temporary folder used as the WordPress uploads folder.
 *
 * @package Cf7_To_Zapier
 */

namespace CFTZ\Tests\Unit;

use Brain\Monkey\Functions;
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
