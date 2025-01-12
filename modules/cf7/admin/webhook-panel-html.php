<?php

// If this file is called directly, call the cops.
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/**
 * Properties
 * $contactform is 'WPCF7_ContactForm' from 'CFTZ_Module_CF7::html_template_panel_html'
 */
$properties = CFTZ_Module_CF7::get_properties();
extract( CFTZ_Module_CF7::get_form_properties( $contactform ) );

/**
 * Preview data
 * Is not the same code but is pretty accurate to same logic
 *
 * TODO: use the same codebase maybe with a empty submission
 */
$preview = array();
$preview_is_json = true;

// Special Tags
$special_tags = array();
$special_tags = CFTZ_Module_CF7::get_special_mail_tags_from_string( $special_mail_tags );

// Form Tags
$form_tags = [];
foreach ( $contactform->scan_form_tags() as $tag ) {
    if ( empty( $tag->name ) ) {
        continue;
    }

    $key = $tag->get_option('webhook');
    if (! empty($key) && ! empty($key[0])) {
        $form_tags[] = $key[0];
        continue;
    }

    $form_tags[] = $tag->name;
}

$tags = array_merge( array_keys( $special_tags ), array_filter( $form_tags ) );

foreach ( $tags as $tag ) {
    if ( empty( $tag ) ) continue;
    $preview[ $tag ] = '??????';
}

// Custom Body
if ( ! empty( $custom_body ) ) {
    $custom_preview = $custom_body;

    foreach ( $preview as $key => $value ) {
        $value = json_encode( $value );
        $value = preg_replace('/^"(.*)"$/', '$1', $value);

        $custom_preview = str_replace( '[' . $key . ']', $value, $custom_preview );
    }

    $custom_sent_json = json_decode( $custom_preview );

    $preview = ( $custom_sent_json === null ) ? $custom_preview : $custom_sent_json;
    $preview_is_json = ( $custom_sent_json !== null );
}

?>

<script type="text/javascript">window.CTZ_ADMIN_TAGS = <?php echo json_encode( array_values( $form_tags ) ); ?></script>

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

    <p class="donation-alert">
        <strong><?php _e( 'Give your support!', 'cf7-to-zapier' ); ?></strong>
        <?php
            printf(
                __( 'You can %s or %s.', 'cf7-to-zapier' ),
                '<a href="https://www.paypal.com/donate?campaign_id=9AA82JCSNWNFS" target="_blank">' . __( 'make a donation', 'cf7-to-zapier' ) . '</a>',
                '<a href="https://wordpress.org/support/plugin/cf7-to-zapier/reviews/#new-post" target="_blank">' . __( 'leave a review', 'cf7-to-zapier' ) . '</a>'
            );
        ?>
    </p>

<?php endif; ?>

<h1 class="ctz-section-title" class="ctz-section-title"><?php _e( 'Webhook', 'cf7-to-zapier' ) ?></h1>

<fieldset>
    <legend>
        <p><?php _e( 'In these options you can activate or deactivate Webhook integration.', 'cf7-to-zapier' ); ?></p>
        <p><?php _e( 'To integrate you should insert your webhook URL below. For example, into Zapier you can create a trigger using "Webhooks" app and choose "Catch Hook" option.', 'cf7-to-zapier' ); ?></p>
    </legend>

    <table class="form-table">
        <tbody>
            <tr>
                <th scope="row">
                    <label>
                        <?php _e( 'Integrate', 'cf7-to-zapier' ) ?>
                    </label>
                </th>
                <td>
                    <p>
                        <label for="ctz-webhook-activate">
                            <?php ctz_checkbox_input( 'activate', $activate ); ?>
                            <?php _e( 'Send to Webhook', 'cf7-to-zapier' ) ?>
                        </label>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label>
                        <?php _e( 'Webhook URL', 'cf7-to-zapier' ) ?>
                    </label>
                </th>
                <td>
                    <p>
                        <label for="ctz-webhook-hook_url">
                            <?php ctz_textarea_input( 'hook_url', $hook_url ); ?>
                        </label>
                    </p>
                    <?php if ( $activate && empty( $hook_url ) ): ?>
                        <p class="description" style="color: #D00;">
                            <?php _e( 'You should insert webhook URL here to finish configuration.' ); ?>
                        </p>
                    <?php else: ?>
                        <p class="description">
                            <?php
                                _e( 'You can add multiple webhook: one per line' );

                                echo '<br>';

                                printf(
                                    __( 'And use placeholders to be replaced by form data: %s', 'cf7-to-zapier' ),
                                    '<span style="font-family: monospace; font-size: 12px; font-weight: bold;">[your-field]</span>'
                                );
                            ?>
                        </p>
                    <?php endif; ?>
                </td>
            </tr>
        </tbody>
    </table>
</fieldset>

<h1 class="ctz-section-title"><?php _e( 'Settings', 'cf7-to-zapier' ) ?></h1>

<fieldset>
    <table class="form-table">
        <tbody>
            <tr>
                <th scope="row">
                    <label>
                        <?php _e( 'Send Mail', 'cf7-to-zapier' ) ?>
                    </label>
                </th>
                <td>
                    <p>
                        <label for="ctz-webhook-send_mail">
                            <?php ctz_checkbox_input( 'send_mail', $send_mail ); ?>
                            <?php _e( 'Send CF7 mail as usually', 'cf7-to-zapier' ) ?>
                        </label>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label>
                        <?php _e( 'Template', 'cf7-to-zapier' ) ?>
                    </label>
                </th>
                <td>
                    <p>
                        <label for="ctz-webhook-template">
                            <select id="ctz-webhook-template" class="ctz-template-select"><option></option></select>
                            <p class="description"><?php _e( 'You can choose a template from community to update your header/body settings.', 'cf7-to-zapier' ) ?></p>
                        </label>
                    </p>
                </td>
            </tr>
        </tbody>
    </table>
</fieldset>

<div class="ctz-accordion-wrapper">
    <div class="ctz-accordion-trigger">
        <div>
            <h2><?php _e( 'Files', 'cf7-to-zapier' ) ?></h2>
            <p class="description"><?php _e( 'Define how you want to send files.', 'cf7-to-zapier' ) ?></p>
        </div>
    </div>

    <div class="ctz-accordion-content">
        <fieldset>
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label>
                                <?php _e( 'Send content', 'cf7-to-zapier' ) ?>
                            </label>
                        </th>
                        <td>
                            <p>
                                <label for="ctz-webhook-files_send_content">
                                    <?php ctz_checkbox_input( 'files_send_content', $files_send_content ); ?>
                                    <?php _e( 'Check to send file content insted of a link', 'cf7-to-zapier' ) ?>
                                </label>
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </fieldset>
    </div>
</div>

<div class="ctz-accordion-wrapper">
    <div class="ctz-accordion-trigger">
        <div>
            <h2><?php _e( 'Special Mail Tags', 'cf7-to-zapier' ) ?></h2>
            <p class="description"><?php _e( 'When you need more information.', 'cf7-to-zapier' ) ?></p>
        </div>
    </div>

    <div class="ctz-accordion-content">
        <fieldset>
            <legend>
                <?php echo _x( 'You can add <a href="https://contactform7.com/special-mail-tags/" target="_blank">Special Mail Tags</a> or <a href="https://contactform7.com/selectable-recipient-with-pipes/" target="_blank">labels from selectable with pipes</a> to the data sent to webhook.', 'The URL should point to CF7 documentation (someday it can be translated).', 'cf7-to-zapier' ); ?>
            </legend>

            <label for="ctz-special_mail_tags">
                <?php ctz_textarea_input( 'special_mail_tags', $special_mail_tags ); ?>
            </label>
            <p class="description"><?php
                printf(
                    __( 'Insert Special Tags like in mail body: %s', 'cf7-to-zapier' ),
                    '<span style="font-family: monospace; font-size: 12px; font-weight: bold;">[_post_title]</span>'
                );

                echo '<br>';

                printf(
                    __( 'Or add a second word to pass as key to Webhook: %s', 'cf7-to-zapier' ),
                    '<span style="font-family: monospace; font-size: 12px; font-weight: bold;">[_post_title title]</span>'
                );
            ?></p>
        </fieldset>
    </div>
</div>

<h1 class="ctz-section-title"><?php _e( 'Advanced settings', 'cf7-to-zapier' ) ?></h1>

<div class="ctz-accordion-wrapper">
    <div class="ctz-accordion-trigger">
        <div>
            <h2><?php _e( 'Method', 'cf7-to-zapier' ) ?></h2>
            <p class="description"><?php _e( 'When you are not making a POST.', 'cf7-to-zapier' ) ?></p>
        </div>
    </div>

    <div class="ctz-accordion-content">
        <p class="description"><strong class="ctz-color-red"><?php _e( 'BE CAREFUL!', 'cf7-to-zapier' ) ?></strong> <?php _e( 'If you select GET, we will send your data as "query params" (if your body is not a valid JSON we will trigger a error).', 'cf7-to-zapier' ) ?></p>

        <fieldset>
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label>
                                <?php _e( 'Method', 'cf7-to-zapier' ) ?>
                            </label>
                        </th>
                        <td>
                            <p>
                                <label for="ctz-custom_method">
                                    <?php ctz_select_input( 'custom_method', $custom_method, [ 'GET', 'POST', 'PUT', 'PATCH', 'DELETE' ] ); ?>
                                    <p class="description"><?php _e( 'Choose a HTTP method. In general, you should not change this.', 'cf7-to-zapier' ); ?></p>
                                </label>
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </fieldset>

    </div>
</div>

<div class="ctz-accordion-wrapper">
    <div class="ctz-accordion-trigger">
        <div>
            <h2><?php _e( 'Headers', 'cf7-to-zapier' ) ?></h2>
            <p class="description"><?php _e( 'When you need authentication/authorization.', 'cf7-to-zapier' ) ?></p>
        </div>
    </div>

    <div class="ctz-accordion-content">
        <fieldset>
            <legend>
                <?php echo _x( 'You can add <a href="https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers" target="_blank">HTTP Headers</a> to your webhook request.', 'The URL should point to HTTP Headers documentation in your language.', 'cf7-to-zapier' ); ?>
            </legend>

            <label for="ctz-custom_headers">
                <?php ctz_textarea_input( 'custom_headers', $custom_headers ); ?>
            </label>
            <p class="description"><?php
                printf(
                    __( 'One header by line, separated by colon. Example: %s', 'cf7-to-zapier' ),
                    '<span style="font-family: monospace; font-size: 12px; font-weight: bold;">Authorization: Bearer 99999999999999999999</span>'
                );
            ?></p>
        </fieldset>
    </div>
</div>

<div class="ctz-accordion-wrapper">
    <div class="ctz-accordion-trigger">
        <div>
            <h2><?php _e( 'Body', 'cf7-to-zapier' ) ?></h2>
            <p class="description"><?php _e( 'When you need customize your request.', 'cf7-to-zapier' ) ?></p>
        </div>
    </div>

    <div class="ctz-accordion-content">
        <p class="description"><strong class="ctz-color-red"><?php _e( 'BE CAREFUL!', 'cf7-to-zapier' ) ?></strong> <?php _e( "This can break your integration (check your quotes).", 'cf7-to-zapier' ) ?></p>
        <p class="description ctz-mb3"><strong><?php _e( 'REMEMBER:', 'cf7-to-zapier' ) ?></strong> <?php printf( __( 'You can change field name with webhook config: %s', 'cf7-to-zapier' ), '<span style="font-family: monospace; font-size: 12px; font-weight: bold;">[email* your_email webhook:email]</span>' ); ?></p>

        <fieldset>
            <label for="ctz-custom_body">
                <?php ctz_textarea_input( 'custom_body', $custom_body ); ?>
            </label>
            <p class="description"><?php _e( 'To create your own body, you can replace values like in mail body (case sensitive).', 'cf7-to-zapier' ); ?></p>
            <p class="description"><?php
                printf(
                    __( 'Example: %s', 'cf7-to-zapier' ),
                    '<span style="font-family: monospace; font-size: 12px; font-weight: bold;">{ "message": "Sir [your-name] from [your-city]." }</span>'
                );
            ?></p>
        </fieldset>

        <fieldset class="ctz-mt3">
            <?php

            ?>

            <legend>
                <?php $preview_is_json ? _e( 'We will send your form data as JSON:', 'cf7-to-zapier' ) : _e( 'We will send your form data as plain/text:', 'cf7-to-zapier' ); ?>
            </legend>
            <pre id="ctz-webhook-preview"><?php echo $preview_is_json ? json_encode( $preview, JSON_PRETTY_PRINT ) : $preview; ?></pre>
            <p class="description"><?php _e( 'This is just a example of field names and will not reflect data or customizations.', 'cf7-to-zapier' ); ?></p>
        </fieldset>
    </div>
</div>

<div class="ctz-accordion-wrapper">
    <div class="ctz-accordion-trigger">
        <div>
            <h2><?php _e( 'Errors', 'cf7-to-zapier' ) ?></h2>
            <p class="description"><?php _e( 'How we handle errors.', 'cf7-to-zapier' ) ?></p>
        </div>
    </div>

    <div class="ctz-accordion-content ctz-b0">
        <fieldset>
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label>
                                <?php _e( 'Notification', 'cf7-to-zapier' ) ?>
                            </label>
                        </th>
                        <td>
                            <p>
                                <label for="ctz-webhook-error_mails">
                                    <?php ctz_text_input( 'error_mails', $error_mails ); ?>
                                </label>
                            </p>
                            <p class="description">
                                <?php _e( 'One or more emails (separated by comma) to be notified on webhook error.', 'cf7-to-zapier' ) ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label>
                                <?php _e( 'Success Codes', 'cf7-to-zapier' ) ?>
                            </label>
                        </th>
                        <td>
                            <p>
                                <label for="ctz-webhook-accepted_statuses">
                                    <?php ctz_text_input( 'accepted_statuses', $accepted_statuses ); ?>
                                </label>
                            </p>
                            <p class="description">
                                <?php echo _x( '<a href="https://developer.mozilla.org/en-US/docs/Web/HTTP/Status" target="_blank">HTTP codes</a> (separated by comma) to be considered success. Other codes will trigger a error notification.', 'The URL should point to HTTP response status code documentation in your language.', 'cf7-to-zapier' ); ?>
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </fieldset>
    </div>
</div>

<h1 class="ctz-section-title"><?php _e( 'Help', 'cf7-to-zapier' ) ?></h1>

<div class="ctz-accordion-wrapper">
    <div class="ctz-accordion-trigger">
        <div>
            <h2><?php _e( 'URL Params', 'cf7-to-zapier' ) ?></h2>
            <p class="description"><?php _e( 'Send data in your URL.', 'cf7-to-zapier' ); ?></p>
        </div>
    </div>

    <div class="ctz-accordion-content">
        <fieldset>
            <legend>
                <?php echo _x( 'You can add URL parameters using <a href="https://contactform7.com/hidden-field/" target="_blank">Hidden Fields</a> with <a href="https://contactform7.com/getting-default-values-from-the-context/" target="_blank">default values</a> in your form.', 'The URL should point to CF7 documentation.', 'cf7-to-zapier' ); ?>
            </legend>

            <pre><?php
                _e( 'To get utm_source: https://example.com/?utm_source=example', 'cf7-to-zapier' );
                echo "\n";
                _e( 'Use this shortcode: [hidden utm_source default:get]', 'cf7-to-zapier' );
            ?></pre>
        </fieldset>
    </div>
</div>

<div class="ctz-accordion-wrapper">
    <div class="ctz-accordion-trigger">
        <div>
            <h2><?php _e( 'FAQ', 'cf7-to-zapier' ) ?></h2>
            <p class="description"><?php _e( 'You can check more information or search for help.', 'cf7-to-zapier' ); ?></p>
        </div>
    </div>

    <div class="ctz-accordion-content ctz-b0">
        <ul class="faq-list">
            <li><a target="_blank" href="https://github.com/mariovalney/cf7-to-zapier/issues/19"><?php _e( 'How to make multiple webhooks optionals?', 'cf7-to-zapier' ); ?></a></li>
            <li><a target="_blank" href="https://wordpress.org/plugins/cf7-to-zapier/#faq"><?php _e( 'Check FAQ from plugin page.', 'cf7-to-zapier' ); ?></a></li>
            <li class="new-topic"><?php _e( 'Or...', 'cf7-to-zapier' ); ?> <a target="_blank" href="https://wordpress.org/support/plugin/cf7-to-zapier/#new-topic-0"><?php _e( 'ask a question!', 'cf7-to-zapier' ); ?></a></li>
        </ul>
    </div>
</div>
