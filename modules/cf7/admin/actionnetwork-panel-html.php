<?php

// If this file is called directly, call the cops.
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/**
 * $contactform is 'WPCF7_ContactForm' from 'CFTZ_Module_CF7::html_template_panel_html'
 */

$activate = '0';
$hook_url = [];
$send_mail = '0';
$special_mail_tags = '';
$custom_headers = '';

if ( is_a( $contactform, 'WPCF7_ContactForm' ) ) {
    $properties = $contactform->prop( CFTZ_Module_CF7::METADATA );

    if ( isset( $properties['activate'] ) ) {
        $activate = $properties['activate'];
    }

    if ( isset( $properties['hook_url'] ) ) {
        $hook_url = (array) $properties['hook_url'];
    }

    if ( isset( $properties['send_mail'] ) ) {
        $send_mail = $properties['send_mail'];
    }

    if ( isset( $properties['special_mail_tags'] ) ) {
        $special_mail_tags = $properties['special_mail_tags'];
    }

    if ( isset( $properties['custom_headers'] ) ) {
        $custom_headers = $properties['custom_headers'];
    }
}

?>



<h2>
    <?php _e( 'ActionNetwork', CFTZ_TEXTDOMAIN ) ?>
</h2>

<fieldset>
    <legend>
        <?php _e( 'Send data from you CF7 forms to your ActionNetwork. Compatible with: Forms, Petitions, Evenes, Ticketed Event and Letter Campaigns.', CFTZ_TEXTDOMAIN ); ?>
        <br>
        <?php _e( 'Each field on your CF7 form will be sent with its name and value. Make sure to use the same field names to match those on ActionNetwork.', CFTZ_TEXTDOMAIN ); ?>
    </legend>

    <table class="form-table">
        <tbody>
            <tr>
                <th scope="row">
                    <label>
                        <?php _e( 'Activate', CFTZ_TEXTDOMAIN ) ?>
                    </label>
                </th>
                <td>
                    <p>
                        <label for="ctz-actionnetwork-activate">
                            <input type="checkbox" id="ctz-actionnetwork-activate" name="ctz-actionnetwork-activate" value="1" <?php checked( $activate, "1" ) ?>>
                            <?php _e( 'Send data to ActionNetwork', CFTZ_TEXTDOMAIN ) ?>
                        </label>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label>
                        <?php _e( 'API Endpoint URL', CFTZ_TEXTDOMAIN ) ?>
                    </label>
                </th>
                <td>
                    <p>
                        <label for="ctz-actionnetwork-hook-url">
                            <input type="text" id="ctz-actionnetwork-hook-url" name="ctz-actionnetwork-hook-url" rows="4" style="width: 100%;" value="<?php echo esc_textarea( implode( PHP_EOL, $hook_url ) ) ?>"></input>
                        </label>
                    </p>
                    <?php if ( $activate && empty( $hook_url ) ): ?>
                        <p class="description" style="color: #D00;">
                            <?php _e( 'You have to enter an ActionNetwork API Endpoint' ); ?>
                        </p>
                    <?php endif; ?>
                    <p class="description">
                            <?php _e( 'Each action (Form, Petition...) has its own API Endpoint URL. You can find it at the bottom of the right sidebar when managing the action.' ); ?>
                        </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label>
                        <?php _e( 'Send CF7 email', CFTZ_TEXTDOMAIN ) ?>
                    </label>
                </th>
                <td>
                    <p>
                        <label for="ctz-actionnetwork-send-mail">
                            <input type="checkbox" id="ctz-actionnetwork-send-mail" name="ctz-actionnetwork-send-mail" value="1" <?php checked( $send_mail, "1" ) ?>>
                            <?php _e( 'Send the email from CF7 configured in the "Mail" tab', CFTZ_TEXTDOMAIN ) ?>
                        </label>
                    </p>
                </td>
            </tr>
        </tbody>
    </table>
</fieldset>

<h2>
    <?php _e( 'ActionNetwork core fields', CFTZ_TEXTDOMAIN ) ?>
</h2>
<fieldset>
    <legend>
        <?php _e( 'Below are the core fields used by ActionNetwork:', CFTZ_TEXTDOMAIN ) ?>
    </legend>
    <ul>
        <?php
        $core_fields_list = [
            'family_name',
            'given_name',
            'postal_code',
            'address_lines',
            'locality',
            'region',
            'country',
            'address',
            'status',
            'number'
        ];
        foreach ($core_fields_list as $field) {
            echo '<li>' . esc_html($field) . '</li>';
        }
        ?>
    </ul>
    <p>
        <?php _e('This plugin sends form data from Contact Form 7 to ActionNetwork. Core fields are mapped automatically to the corresponding keys in ActionNetwork, and any additional fields are treated as custom fields.', CFTZ_TEXTDOMAIN); ?>
    </p>
</fieldset>

<h2>
    <?php _e( 'ActionNetwork source codes', CFTZ_TEXTDOMAIN ) ?>
    <p>
        <?php _e('Whatever you put in <em>"?source=[here]"</em> at the end of the URL where CF7 form is inserted, will be sent as Source Code to Action Network. Example of source codes<br /><em>https://yourwebsite.com/your-form/</em>?source=ads<br /><em>https://yourwebsite.com/your-form/</em>?source=email<br /><em>https://yourwebsite.com/your-form/</em>?source=whatsapp<br /><br />(If no source is provided in the URL parameters, "contact-form-7" will be placed instead).', CFTZ_TEXTDOMAIN); ?>
    </p>
</h2>