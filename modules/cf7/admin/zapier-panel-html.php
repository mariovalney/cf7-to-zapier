<?php

// If this file is called directly, call the cops.
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/**
 * $contactform is 'WPCF7_ContactForm' from 'CFTZ_Module_CF7::html_template_panel_html'
 */

$activate = '0';
$hook_url = '';
$send_mail = '0';

if ( is_a( $contactform, 'WPCF7_ContactForm' ) ) {
    $properties = $contactform->prop( CFTZ_Module_CF7::METADATA );

    if ( isset( $properties['activate'] ) ) {
        $activate = $properties['activate'];
    }

    if ( isset( $properties['hook_url'] ) ) {
        $hook_url = $properties['hook_url'];
    }

    if ( isset( $properties['send_mail'] ) ) {
        $send_mail = $properties['send_mail'];
    }
}

?>

<h2>
    <?php _e( 'Zapier', CFTZ_TEXTDOMAIN ) ?>
</h2>

<fieldset>
    <legend>
        <?php _e( 'In these options you can activate or deactivate Zapier integration.', CFTZ_TEXTDOMAIN ); ?>
        <br>
        <?php _e( 'To integrate you should create a trigger into Zapier using "Webhooks" app and choose "Catch Hook" option. Then insert webhook URL below.', CFTZ_TEXTDOMAIN ); ?>
    </legend>

    <table class="form-table">
        <tbody>
            <tr>
                <th scope="row">
                    <label>
                        <?php _e( 'Integrate', CFTZ_TEXTDOMAIN ) ?>
                    </label>
                </th>
                <td>
                    <p>
                        <label for="ctz-zapier-activate">
                            <input type="checkbox" id="ctz-zapier-activate" name="ctz-zapier-activate" value="1" <?php checked( $activate, "1" ) ?>>
                            <?php _e( 'Send to Zapier', CFTZ_TEXTDOMAIN ) ?>
                        </label>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label>
                        <?php _e( 'Webhook URL', CFTZ_TEXTDOMAIN ) ?>
                    </label>
                </th>
                <td>
                    <p>
                        <label for="ctz-zapier-hook-url">
                            <input type="url" id="ctz-zapier-hook-url" name="ctz-zapier-hook-url" value="<?php echo $hook_url; ?>" style="width: 100%;">
                        </label>
                    </p>
                    <?php if ( $activate && empty( $hook_url ) ): ?>
                        <p class="description" style="color: #D00;">
                            <?php _e( 'You should insert webhook URL from Zapier here to finish configuration.' ); ?>
                        </p>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label>
                        <?php _e( 'Send Mail', CFTZ_TEXTDOMAIN ) ?>
                    </label>
                </th>
                <td>
                    <p>
                        <label for="ctz-zapier-send-mail">
                            <input type="checkbox" id="ctz-zapier-send-mail" name="ctz-zapier-send-mail" value="1" <?php checked( $send_mail, "1" ) ?>>
                            <?php _e( 'Send CF7 mail as usually', CFTZ_TEXTDOMAIN ) ?>
                        </label>
                    </p>
                </td>
            </tr>
        </tbody>
    </table>
</fieldset>

<h2>
    <?php _e( 'Data sended to Zapier', CFTZ_TEXTDOMAIN ) ?>
</h2>

<p><?php _e( 'We will send your form data as below:', CFTZ_TEXTDOMAIN ) ?></p>

<?php
    $sended_data = array();

    $tags = $contactform->scan_form_tags();
    foreach ( $tags as $tag ) {
        if ( empty( $tag->name ) ) {
            continue;
        }

        $sended_data[$tag->name] = '??????';
    }
?>

<pre style="background: #FFF; border: 1px solid #CCC; padding: 10px; margin: 0;"><?php echo json_encode( $sended_data, JSON_PRETTY_PRINT ); ?></pre>

<p class="description"><?php printf( __( 'You can add URL parameters into form using this shortcode: %s.', CFTZ_TEXTDOMAIN ), '<span style="font-family: monospace; font-size: 12px; font-weight: bold;">[hidden example_get default:get]</span>' ); ?></p>