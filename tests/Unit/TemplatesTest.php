<?php
/**
 * The webhook templates in ci/website/public/templates.json.
 *
 * Every installed copy loads this file, so each template must be valid and must
 * give a valid JSON body when the webhook panel fills it with the form fields.
 *
 * @package Cf7_To_Zapier
 */

namespace CFTZ\Tests\Unit;

use CFTZ\Tests\TestCase;

class TemplatesTest extends TestCase {

    const FIELDS = array(
        'your-name'     => 'text',
        'your-message'  => 'textarea',
        'your-checkbox' => 'checkbox',
        'your-consent'  => 'acceptance',
    );

    const DATA = array(
        'your-name'     => 'Ana "Bia"',
        'your-message'  => "Line 1\nLine 2 \\ / <b>",
        'your-checkbox' => array( 'Google', 'Friend' ),
        'your-consent'  => '1',
    );

    private static function file() {
        return dirname( __DIR__, 2 ) . '/ci/website/public/templates.json';
    }

    public function test_file_is_a_json_object_of_groups() {
        $groups = json_decode( file_get_contents( self::file() ), true );

        $this->assertIsArray( $groups, json_last_error_msg() );
        $this->assertArrayHasKey( 'default', $groups );

        foreach ( $groups as $group => $templates ) {
            $this->assertIsString( $group );
            $this->assertIsArray( $templates, $group );
            $this->assertNotEmpty( $templates, $group );
        }
    }

    /**
     * @dataProvider templates
     */
    public function test_template_fields( array $template ) {
        $this->assertIsString( $template['name'] ?? null, 'name' );
        $this->assertNotSame( '', trim( $template['name'] ) );
        $this->assertIsString( $template['body'] ?? null, 'body' );

        foreach ( $template['headers'] ?? array() as $header ) {
            $this->assertMatchesRegularExpression( '/^[^:\s]+:/', $header, 'headers' );
        }

        if ( isset( $template['separator'] ) ) {
            $this->assertIsString( $template['separator'], 'separator' );
        }

        // The panel only opens web addresses.
        if ( isset( $template['docs'] ) ) {
            $this->assertMatchesRegularExpression( '#^https?://#i', $template['docs'], 'docs' );
        }
    }

    /**
     * @dataProvider templates
     */
    public function test_filled_template_sends_valid_json( array $template ) {
        $request = $this->trigger(
            self::DATA,
            array(
                'custom_headers' => implode( "\n", $template['headers'] ?? array() ),
                'custom_body'    => $this->fill( $template, array_keys( self::FIELDS ) ),
            ),
            $this->make_form( self::FIELDS )
        );

        $this->assertNotNull( json_decode( $request['body'] ), $request['body'] );
        $this->assertSame( 'application/json; charset=UTF-8', $request['headers']['Content-Type'] );

        foreach ( $template['headers'] ?? array() as $header ) {
            $this->assertArrayHasKey( trim( explode( ':', $header, 2 )[0] ), $request['headers'] );
        }
    }

    public function templates() {
        $groups    = json_decode( file_get_contents( self::file() ), true );
        $templates = array();

        foreach ( (array) $groups as $group => $items ) {
            foreach ( (array) $items as $index => $template ) {
                $name = $group . ': ' . ( $template['name'] ?? $index );

                $templates[ $name ] = array( (array) $template );
            }
        }

        return $templates;
    }

    /**
     * Fill the body as the template selector of admin.js does.
     *
     * It replaces the first "__VALUES__" with every field as a placeholder, joined
     * with the separator of the template (" | " by default).
     *
     * @param array    $template The template.
     * @param string[] $fields   Field names of the form.
     * @return string
     */
    private function fill( array $template, array $fields ) {
        $values = implode(
            $template['separator'] ?? ' | ',
            array_map(
                function( $field ) {
                    return '[' . $field . ']';
                },
                $fields
            )
        );

        $position = strpos( $template['body'], '__VALUES__' );
        if ( false === $position ) {
            return $template['body'];
        }

        return substr_replace( $template['body'], $values, $position, strlen( '__VALUES__' ) );
    }
}
