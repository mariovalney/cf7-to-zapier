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

<?php
    /**
     * Filter: ctz_remove_donation_alert
     *
     * You can remove it returning true:
     * add_filter( 'ctz_remove_donation_alert', '__return_true' );
     *
     * @since 3.0.1
     */
    if ( ! apply_filters( 'ctz_remove_donation_alert', false ) ) :
?>
    <p style="background: #FF4A00; display: block; color: #FFFFFF; padding: 10px 15px; margin: 10px 0 20px;">
        <strong style="margin-right: 5px;"><?php _e( 'Give your support!', CFTZ_TEXTDOMAIN ); ?></strong>
        <?php
            printf(
                __( 'You can %s or %s.', CFTZ_TEXTDOMAIN ),
                '<a href="https://www.paypal.com/donate?campaign_id=9AA82JCSNWNFS" target="_blank" style="color: #FFFFFF;">' . __( 'make a donation', CFTZ_TEXTDOMAIN ) . '</a>',
                '<a href="https://wordpress.org/support/plugin/cf7-to-zapier/reviews/#new-post" target="_blank" style="color: #FFFFFF;">' . __( 'leave a review', CFTZ_TEXTDOMAIN ) . '</a>'
            );
        ?>
    </p>
<?php endif; ?>

<h2>
    <?php _e( 'Webhook', CFTZ_TEXTDOMAIN ) ?>
</h2>

<fieldset>
    <legend>
        <?php _e( 'In these options you can activate or deactivate Webhook integration.', CFTZ_TEXTDOMAIN ); ?>
        <br>
        <?php _e( 'To integrate you should insert your webhook URL below. For example, into Zapier you can create a trigger using "Webhooks" app and choose "Catch Hook" option.', CFTZ_TEXTDOMAIN ); ?>
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
                        <label for="ctz-webhook-activate">
                            <input type="checkbox" id="ctz-webhook-activate" name="ctz-webhook-activate" value="1" <?php checked( $activate, "1" ) ?>>
                            <?php _e( 'Send to Webhook', CFTZ_TEXTDOMAIN ) ?>
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
                        <label for="ctz-webhook-hook-url">
                            <textarea id="ctz-webhook-hook-url" name="ctz-webhook-hook-url" rows="4" style="width: 100%;"><?php echo esc_textarea( implode( PHP_EOL, $hook_url ) ) ?></textarea>
                        </label>
                    </p>
                    <?php if ( $activate && empty( $hook_url ) ): ?>
                        <p class="description" style="color: #D00;">
                            <?php _e( 'You should insert webhook URL here to finish configuration.' ); ?>
                        </p>
                    <?php else: ?>
                        <p class="description" style="font-size: 13px;">
                            <?php
                                _e( 'You can add multiple webhook: one per line' );

                                echo '<br>';

                                printf(
                                    __( 'And use placeholders to be replaced by form data: %s', CFTZ_TEXTDOMAIN ),
                                    '<span style="font-family: monospace; font-size: 12px; font-weight: bold;">[your-field]</span>'
                                );
                            ?>
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
                        <label for="ctz-webhook-send-mail">
                            <input type="checkbox" id="ctz-webhook-send-mail" name="ctz-webhook-send-mail" value="1" <?php checked( $send_mail, "1" ) ?>>
                            <?php _e( 'Send CF7 mail as usually', CFTZ_TEXTDOMAIN ) ?>
                        </label>
                    </p>
                </td>
            </tr>
        </tbody>
    </table>
</fieldset>

<h2>
    <?php _e( 'Special Mail Tags', CFTZ_TEXTDOMAIN ) ?>
</h2>

<fieldset>
    <legend>
        <?php echo _x( 'You can add <a href="https://contactform7.com/special-mail-tags/" target="_blank">Special Mail Tags</a> or <a href="https://contactform7.com/selectable-recipient-with-pipes/" target="_blank">labels from selectable with pipes</a> to the data sent to webhook.', 'The URL should point to CF7 documentation (someday it can be translated).', CFTZ_TEXTDOMAIN ); ?>
    </legend>

    <div style="margin: 20px 0;">
        <label for="ctz-special-mail-tags">
            <?php
                $special_mail_tags = esc_textarea( $special_mail_tags );
                $rows = ( (int) substr_count( $special_mail_tags, "\n" ) ) + 2;
                $rows = max( $rows, 4 );
            ?>
            <textarea id="ctz-special-mail-tags" name="ctz-special-mail-tags" class="large-text code" rows="<?php echo $rows; ?>"><?php echo $special_mail_tags; ?></textarea>
        </label>
        <p class="description"><?php
            printf(
                __( 'Insert Special Tags like in mail body: %s', CFTZ_TEXTDOMAIN ),
                '<span style="font-family: monospace; font-size: 12px; font-weight: bold;">[_post_title]</span>'
            );

            echo '<br>';

            printf(
                __( 'Or add a second word to pass as key to Webhook: %s', CFTZ_TEXTDOMAIN ),
                '<span style="font-family: monospace; font-size: 12px; font-weight: bold;">[_post_title title]</span>'
            );
        ?></p>
    </div>
</fieldset>

<h2>
    <?php _e( 'Custom Headers', CFTZ_TEXTDOMAIN ) ?>
</h2>

<fieldset>
    <legend>
        <?php echo _x( 'You can add <a href="https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers" target="_blank">HTTP Headers</a> to your webhook request.', 'The URL should point to HTTP Headers documentation in your language.', CFTZ_TEXTDOMAIN ); ?>
    </legend>

    <div style="margin: 20px 0;">
        <label for="ctz-custom-headers">
            <?php
                $custom_headers = esc_textarea( $custom_headers );
                $rows = ( (int) substr_count( $custom_headers, "\n" ) ) + 2;
                $rows = max( $rows, 4 );
            ?>
            <textarea id="ctz-custom-headers" name="ctz-custom-headers" class="large-text code" rows="<?php echo $rows; ?>"><?php echo $custom_headers; ?></textarea>
        </label>
        <p class="description"><?php
            printf(
                __( 'One header by line, separated by colon. Example: %s', CFTZ_TEXTDOMAIN ),
                '<span style="font-family: monospace; font-size: 12px; font-weight: bold;">Authorization: Bearer 99999999999999999999</span>'
            );
        ?></p>
    </div>
</fieldset>

<hr style="margin: 10px 0 30px 0;">

<h2>
    <?php _e( 'URL Params', CFTZ_TEXTDOMAIN ) ?>
</h2>

<fieldset>
    <legend>
        <?php echo _x( 'You can add URL parameters using <a href="https://contactform7.com/hidden-field/" target="_blank">Hidden Fields</a> with <a href="https://contactform7.com/getting-default-values-from-the-context/" target="_blank">default values</a> in your form.', 'The URL should point to CF7 documentation.', CFTZ_TEXTDOMAIN ); ?>
    </legend>

    <div style="margin: 20px 0;">
        <pre style="background: #FFF; border: 1px solid #CCC; padding: 10px; margin: 0;"><?php
            _e( 'To get utm_source: https://example.com/?utm_source=example', CFTZ_TEXTDOMAIN );
            echo "\n";
            _e( 'Use this shortcode: [hidden utm_source default:get]', CFTZ_TEXTDOMAIN );
        ?></pre>
    </div>
</fieldset>

<h2>
    <?php _e( 'Data sent to Webhook', CFTZ_TEXTDOMAIN ) ?>
</h2>

<fieldset>
    <legend>
        <?php _e( 'We will send your form data as below:', CFTZ_TEXTDOMAIN ) ?>
    </legend>

    <div style="margin: 20px 0;">
        <?php
            $sent_data = array();

            // Special Tags
            $special_tags = array();
            $special_tags = CFTZ_Module_CF7::get_special_mail_tags_from_string( $special_mail_tags );
            $tags = array_keys( $special_tags );

            // Form Tags
            $form_tags = $contactform->scan_form_tags();
            foreach ( $form_tags as $tag ) {
                $key = $tag->get_option('webhook');
                if (! empty($key) && ! empty($key[0])) {
                    $tags[] = $key[0];
                    continue;
                }

                $tags[] = $tag->name;
            }

            foreach ( $tags as $tag ) {
                if ( empty( $tag ) ) continue;

                $sent_data[ $tag ] = '??????';
            }
        ?>

        <pre style="background: #FFF; border: 1px solid #CCC; padding: 10px; margin: 0;"><?php
            echo json_encode( $sent_data, JSON_PRETTY_PRINT );
        ?></pre>
        <p class="description"><?php
            _e( 'This is just a example of field names and will not reflect data or customizations.', CFTZ_TEXTDOMAIN );

            echo '<br>';

            printf(
                __( 'You can change field name with webhook config: %s', CFTZ_TEXTDOMAIN ),
                '<span style="font-family: monospace; font-size: 12px; font-weight: bold;">[email* your_email webhook:email]</span>'
            );
        ?></p>
    </div>
</fieldset>
