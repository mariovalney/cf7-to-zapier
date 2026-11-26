<?php
/**
 * Custom body: how form fields replace their placeholders.
 *
 * @package Cf7_To_Zapier
 */

namespace CFTZ\Tests\Unit;

use CFTZ\Tests\TestCase;

class CustomBodyTest extends TestCase {

    const FIELDS = array(
        'your-name'     => 'text',
        'your-checkbox' => 'checkbox',
        'your-consent'  => 'acceptance',
    );

    const DATA = array(
        'your-name'     => 'Ana "Bia"',
        'your-checkbox' => array( 'Google', 'Friend' ),
        'your-consent'  => '1',
    );

    /**
     * @dataProvider bodies
     */
    public function test_replaces_fields( $body, array $data, $expected, $is_json ) {
        $request = $this->trigger(
            array_merge( self::DATA, $data ),
            array( 'custom_body' => $body ),
            $this->make_form( self::FIELDS )
        );

        $this->assertSame( $expected, $request['body'] );
        $this->assertSame( $is_json ? 'application/json; charset=UTF-8' : 'text/plain; charset=UTF-8', $request['headers']['Content-Type'] );
    }

    public function bodies() {
        // Placeholders inside quotes get text, outside get JSON. Issue #34.
        return array(
            'text inside a string'           => array( '{ "name": "[your-name]" }', array(), '{ "name": "Ana \"Bia\"" }', true ),
            'line break inside a string'     => array( '{ "name": "[your-name]" }', array( 'your-name' => "Ana\nBia" ), '{ "name": "Ana\nBia" }', true ),
            'array inside a string'          => array( '{ "how": "[your-checkbox]" }', array(), '{ "how": "Google, Friend" }', true ),
            'one option inside a string'     => array( '{ "how": "[your-checkbox]" }', array( 'your-checkbox' => array( 'Google' ) ), '{ "how": "Google" }', true ),
            'array outside a string'         => array( '{ "how": [your-checkbox] }', array(), '{ "how": ["Google","Friend"] }', true ),
            'documented example'             => array(
                '{ "name": "[your-name]", "how": [your-checkbox], "consent": [your-consent] }',
                array(),
                '{ "name": "Ana \"Bia\"", "how": ["Google","Friend"], "consent": 1 }',
                true,
            ),
            'Slack template'                 => array(
                '{ "text": "*New message!*\n\n[your-name]\n[your-checkbox]\n[your-consent]" }',
                array(),
                '{ "text": "*New message!*\n\nAna \"Bia\"\nGoogle, Friend\n1" }',
                true,
            ),
            'empty checkbox outside a string' => array( '{ "how": [your-checkbox] }', array( 'your-checkbox' => '' ), '{ "how": [] }', true ),
            'empty checkbox inside a string' => array( '{ "how": "[your-checkbox]" }', array( 'your-checkbox' => '' ), '{ "how": "" }', true ),
            'unchecked acceptance outside'   => array( '{ "consent": [your-consent] }', array( 'your-consent' => '' ), '{ "consent": 0 }', true ),
            'unchecked acceptance inside'    => array( '{ "consent": "[your-consent]" }', array( 'your-consent' => '' ), '{ "consent": "0" }', true ),
            'escaped quotes in the template' => array( '{ "msg": "They said \"[your-checkbox]\"" }', array(), '{ "msg": "They said \"Google, Friend\"" }', true ),
            'field as a key'                 => array( '{ "[your-checkbox]": true }', array(), '{ "Google, Friend": true }', true ),
            'placeholder typed in the form'  => array( '{ "name": "[your-name]" }', array( 'your-name' => 'See [your-checkbox]' ), '{ "name": "See [your-checkbox]" }', true ),
            'files sent as content'          => array(
                '{ "in": "[your-checkbox]", "out": [your-checkbox] }',
                array( 'your-checkbox' => array( 'filename' => 'a.pdf', 'content' => 'QUJD' ) ),
                '{ "in": "{\"filename\":\"a.pdf\",\"content\":\"QUJD\"}", "out": {"filename":"a.pdf","content":"QUJD"} }',
                true,
            ),
            'unknown field stays'            => array( '{ "x": "[nope]" }', array(), '{ "x": "[nope]" }', true ),
            'not JSON: urlencoded'           => array( 'name=[your-name]&how=[your-checkbox]', array(), 'name=Ana \"Bia\"&how=["Google","Friend"]', false ),
            'not JSON: unbalanced quote'     => array( 'Say "[your-checkbox]', array(), 'Say "["Google","Friend"]', false ),
        );
    }

    public function test_sends_the_data_as_json_without_custom_body() {
        $request = $this->trigger( self::DATA, array(), $this->make_form( self::FIELDS ) );

        $this->assertSame( json_encode( self::DATA ), $request['body'] );
        $this->assertSame( 'application/json; charset=UTF-8', $request['headers']['Content-Type'] );
    }
}
