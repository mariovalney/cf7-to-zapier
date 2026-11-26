<?php
/**
 * How the posted fields become the data sent to the webhook: slashes, the
 * "webhook" option, free text and uploaded files.
 *
 * Files are copied to a temporary folder used as the WordPress uploads folder.
 *
 * @package Cf7_To_Zapier
 */

namespace CFTZ\Tests\Unit;

use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use CFTZ\Tests\TestCase;
use CFTZ_Module_CF7;
use Cf7_To_Zapier;
use WPCF7_Submission;

class FormDataTest extends TestCase {

    /**
     * Temporary folder with the uploads folder and the files uploaded to Contact Form 7.
     *
     * @var string
     */
    private $tmp;

    protected function setUp(): void {
        parent::setUp();

        $this->tmp = sys_get_temp_dir() . '/ctz-tests-' . bin2hex( random_bytes( 6 ) );
        mkdir( $this->tmp . '/uploads', 0777, true );
        mkdir( $this->tmp . '/cf7' );

        Functions\when( 'wp_get_upload_dir' )->justReturn(
            array(
                'basedir' => $this->tmp . '/uploads',
                'baseurl' => 'https://example.com/wp-content/uploads',
            )
        );

        Functions\when( 'wp_mkdir_p' )->alias(
            function( $dir ) {
                return is_dir( $dir ) || mkdir( $dir, 0777, true );
            }
        );

        Functions\when( 'wp_unique_filename' )->alias(
            function( $dir, $filename ) {
                return $filename;
            }
        );
    }

    protected function tearDown(): void {
        $_POST                      = array();
        WPCF7_Submission::$instance = null;
        $this->remove( $this->tmp );

        parent::tearDown();
    }

    /**
     * Submit the form and return the data of the 'ctz_trigger_webhook' action.
     *
     * @param array $fields   Field name => basetype or tag properties.
     * @param array $post     Posted fields, with the slashes WordPress adds.
     * @param array $settings Webhook settings.
     * @param array $files    Uploaded files: field name => paths.
     * @return array
     */
    private function send( array $fields, array $post, array $settings = array(), array $files = array() ) {
        $_POST                      = $post;
        WPCF7_Submission::$instance = new WPCF7_Submission( $files );

        $sent = null;
        Actions\expectDone( 'ctz_trigger_webhook' )
            ->once()
            ->whenHappen(
                function( $data ) use ( &$sent ) {
                    $sent = $data;
                }
            );

        $settings = array_merge(
            array(
                'activate' => '1',
                'hook_url' => array( 'https://example.com/hook' ),
            ),
            $settings
        );

        $form = $this->make_form( $fields, array( CFTZ_Module_CF7::METADATA => $settings ) );
        ( new CFTZ_Module_CF7( new Cf7_To_Zapier() ) )->wpcf7_mail_sent( $form );

        return $sent;
    }

    public function test_removes_the_slashes_added_by_wordpress() {
        $data = $this->send(
            array(
                'your-name'     => 'text',
                'your-checkbox' => 'checkbox',
            ),
            array(
                'your-name'     => "O\\'Brien",
                'your-checkbox' => array( 'Say \\"hi\\"' ),
            )
        );

        $this->assertSame( "O'Brien", $data['your-name'] );
        $this->assertSame( array( 'Say "hi"' ), $data['your-checkbox'] );
    }

    public function test_renames_fields_with_the_webhook_option() {
        $data = $this->send(
            array(
                'your-email' => array(
                    'basetype' => 'email',
                    'options'  => array( 'webhook:email' ),
                ),
            ),
            array( 'your-email' => 'ana@example.com' )
        );

        $this->assertSame( array( 'email' => 'ana@example.com' ), $data );
    }

    /**
     * @dataProvider free_texts
     */
    public function test_sends_the_free_text_in_place_of_its_option( $basetype, array $post, $expected ) {
        $data = $this->send(
            array(
                'how' => array(
                    'basetype' => $basetype,
                    'options'  => array( 'free_text' ),
                    'values'   => array( 'Google', 'Other' ),
                ),
            ),
            $post
        );

        $this->assertSame( $expected, $data['how'] );
    }

    public function free_texts() {
        return array(
            'checkbox'                   => array(
                'checkbox',
                array(
                    'how'                    => array( 'Google', 'Other' ),
                    '_wpcf7_free_text_how'   => 'Radio',
                ),
                array( 'Google', 'Radio' ),
            ),
            'checkbox before CF7 5.8.6'  => array(
                'checkbox',
                array(
                    'how'           => array( 'Google', 'Other' ),
                    'how_free_text' => 'Radio',
                ),
                array( 'Google', 'Radio' ),
            ),
            'radio'                      => array(
                'radio',
                array(
                    'how'                  => 'Other',
                    '_wpcf7_free_text_how' => "Radio d\\'\u{e1}gua",
                ),
                "Radio d'\u{e1}gua",
            ),
            'free text option not chosen' => array(
                'checkbox',
                array(
                    'how'                  => array( 'Google' ),
                    '_wpcf7_free_text_how' => 'Radio',
                ),
                array( 'Google' ),
            ),
        );
    }

    /**
     * @dataProvider file_types
     */
    public function test_sends_a_link_to_a_copy_of_the_file( $basetype ) {
        $file = $this->upload( 'report.pdf', 'PDF content' );

        $data = $this->send( array( 'your-file' => $basetype ), array(), array(), array( 'your-file' => array( $file ) ) );

        $pattern = '#^https://example\.com/wp-content/uploads/cf7-to-webhook-uploads/5/(' . gmdate( 'Ymd' ) . '_[0-9a-f]{32})/your-file-report\.pdf$#';
        $this->assertMatchesRegularExpression( $pattern, $data['your-file'] );

        preg_match( $pattern, $data['your-file'], $matches );
        $submission = $this->tmp . '/uploads/cf7-to-webhook-uploads/5/' . $matches[1];
        $this->assertStringEqualsFile( $submission . '/your-file-report.pdf', 'PDF content' );

        // An empty index.php, so servers with directory listing on do not list the files.
        $this->assertStringEqualsFile( $this->tmp . '/uploads/cf7-to-webhook-uploads/index.php', "<?php\n// Silence is golden.\n" );
        $this->assertFileExists( $this->tmp . '/uploads/cf7-to-webhook-uploads/5/index.php' );
        $this->assertFileExists( $submission . '/index.php' );
    }

    public function file_types() {
        return array(
            'file'          => array( 'file' ),
            'multilinefile' => array( 'multilinefile' ),
        );
    }

    public function test_sends_several_files_as_a_list() {
        $files = array( $this->upload( 'a.txt', 'A' ), $this->upload( 'b.txt', 'B' ) );

        $data = $this->send( array( 'your-file' => 'file' ), array(), array(), array( 'your-file' => $files ) );

        $this->assertCount( 2, $data['your-file'] );
        $this->assertStringEndsWith( '/your-file-a.txt', $data['your-file'][0] );
        $this->assertStringEndsWith( '/your-file-b.txt', $data['your-file'][1] );
        $this->assertSame( dirname( $data['your-file'][0] ), dirname( $data['your-file'][1] ) );
    }

    public function test_sends_the_content_instead_of_a_link() {
        $file = $this->upload( 'report.pdf', 'PDF content' );

        $data = $this->send(
            array( 'your-file' => 'file' ),
            array(),
            array( 'files_send_content' => '1' ),
            array( 'your-file' => array( $file ) )
        );

        $this->assertSame(
            array(
                'filename' => 'report.pdf',
                'content'  => base64_encode( 'PDF content' ),
            ),
            $data['your-file']
        );

        // Without "Add to Library", nothing is copied.
        $this->assertDirectoryDoesNotExist( $this->tmp . '/uploads/cf7-to-webhook-uploads' );
    }

    public function test_fails_the_submission_when_the_file_cannot_be_copied() {
        // copy() warns when the file is missing. WordPress logs it; here it would end the test.
        $level = error_reporting( error_reporting() & ~E_WARNING );

        try {
            $data = $this->send( array( 'your-file' => 'file' ), array(), array(), array( 'your-file' => array( $this->tmp . '/cf7/missing.pdf' ) ) );
        } finally {
            error_reporting( $level );
        }

        $this->assertSame( array(), $data['your-file'] );
        $this->assertSame( 'mail_failed', WPCF7_Submission::$instance->status );
        $this->assertSame( 'There was an unknown error uploading the file.', WPCF7_Submission::$instance->response );
    }

    /**
     * A file uploaded to Contact Form 7, in its temporary folder.
     *
     * @param string $name    File name.
     * @param string $content File content.
     * @return string Path of the file.
     */
    private function upload( $name, $content ) {
        $path = $this->tmp . '/cf7/' . $name;
        file_put_contents( $path, $content );

        return $path;
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
