<?php
/**
 * A form with every common field, parsed by Contact Form 7, and the data sent to the webhook.
 *
 * @package Cf7_To_Zapier
 */

namespace CFTZ\Tests\Cf7;

class FormDataTest extends TestCase {

    const FORM = <<<'FORM'
<label>Name [text* your-name webhook:name]</label>
<label>Email [email* your-email]</label>
<label>Phone [tel your-phone]</label>
<label>Site [url your-site]</label>
<label>Age [number your-age min:0]</label>
<label>Date [date your-date]</label>
<label>Message [textarea your-message]</label>
<label>Subject [select your-subject "Sales|sales@example.com" "Help|help@example.com"]</label>
<label>Topics [select your-topics multiple "Plugins" "Themes" "Hosting"]</label>
[checkbox your-how free_text "Google" "Friend" "Other"]
[radio your-plan "Free" "Pro"]
[acceptance your-consent] I agree.
[hidden source "website"]
[file your-file]
FORM;

    public function test_sends_every_field() {
        $data = $this->send(
            self::FORM,
            array(
                'your-name'               => 'Ana',
                'your-email'              => 'ana@example.com',
                'your-phone'              => '+55 85 90000-0000',
                'your-site'               => 'https://example.com',
                'your-age'                => '30',
                'your-date'               => '2026-11-26',
                'your-message'            => "O\\'Brien said \\\"hi\\\"\r\nLine 2",
                'your-subject'            => 'Sales',
                'your-topics'             => array( 'Plugins', 'Hosting' ),
                'your-how'                => array( 'Google', 'Other' ),
                '_wpcf7_free_text_your-how' => 'A podcast',
                'your-plan'               => 'Pro',
                'your-consent'            => '1',
                'source'                  => 'website',
            ),
            array( 'special_mail_tags' => '[_raw_your-subject subject-label]' )
        );

        $this->assertSame(
            array(
                // Special mail tags come first: "_raw_" gives the label before the pipe.
                'subject-label' => 'Sales',
                'name'          => 'Ana',
                'your-email'    => 'ana@example.com',
                'your-phone'    => '+55 85 90000-0000',
                'your-site'     => 'https://example.com',
                'your-age'      => '30',
                'your-date'     => '2026-11-26',
                'your-message'  => "O'Brien said \"hi\"\r\nLine 2",
                // The value after the pipe.
                'your-subject'  => 'sales@example.com',
                'your-topics'   => array( 'Plugins', 'Hosting' ),
                'your-how'      => array( 'Google', 'A podcast' ),
                'your-plan'     => 'Pro',
                'your-consent'  => '1',
                'source'        => 'website',
                // No file uploaded.
                'your-file'     => '',
            ),
            $data
        );
    }

    public function test_custom_body_with_empty_checkbox_and_acceptance() {
        // The custom body finds checkbox and acceptance fields by the basetype Contact Form 7 gives them.
        $form = '[checkbox* your-how "Google" "Friend"] [acceptance your-consent] [radio your-plan "Free" "Pro"]';
        $data = $this->send( $form, array( 'your-plan' => 'Pro' ) );

        $request = $this->trigger(
            $data,
            array( 'custom_body' => '{ "how": [your-how], "consent": [your-consent], "plan": "[your-plan]" }' ),
            new \WPCF7_ContactForm( 5, $this->scan( $form ) )
        );

        $this->assertSame( '{ "how": [], "consent": 0, "plan": "Pro" }', $request['body'] );
    }
}
