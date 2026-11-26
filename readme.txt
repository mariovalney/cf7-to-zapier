=== CF7 to Webhook ===

Contributors: mariovalney
Donate link: https://www.paypal.com/donate?campaign_id=9AA82JCSNWNFS
Tags: cf7, contact form, zapier, integration, webhook
Requires at least: 5.0
Tested up to: 7.1.5
Stable tag: 5.3.1
Requires PHP: 7.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Use Contact Form 7 as a trigger to any webhook!

== Description ==

**CF7 to Webhook** is trusted by more than 30.000 WordPress websites and translated in languages!

Thank you!

---

[Contact Form 7 (CF7)](https://wordpress.org/plugins/contact-form-7/ "Install it first, of course") is a awesome plugin used by 10+ million WordPress websites.

Webhooks are endpoint (urls) you can send data!

Now you can join both: the best contact form plugin to WordPress and any webhook which receive JSON!

And Zapier?

[Zapier (Zapier)](https://zapier.com) is a awesome service to connect your apps and automate workflows!

Just activate and configure Zapier to receive data!

Disclaimer: this plugin was created without any encouragement from Zapier (or any webhook/API service) / CF7 (and other supported plugins) developers.

= How to Use =

Easily and quickly! Just activate "Contact Form 7" and "CF7 to Webhook" and configure a URL to send data (or go to Zapier to create your Zap).

= Translations =

You can [translate CF7 to Webhook](https://translate.wordpress.org/projects/wp-plugins/cf7-to-zapier) to your language.

= Review =

We would be grateful for a [review here](https://wordpress.org/support/plugin/cf7-to-zapier/reviews/).

= Support =

* Contact Form 7 - 6.2.6

Tested with other plugins:

* MultiLine files for Contact Form 7 - 2.9.1
* Contact Form 7 Multi-Step Forms - 4.8.1

== Installation ==

`Install [Contact Form 7](https://wordpress.org/plugins/contact-form-7/) and activate it.`

* Install "CF7 to Webhook" by plugins dashboard.

Or

* Upload the entire `cf7-to-zapier` folder to the `/wp-content/plugins/` directory.

Then

* Activate the plugin through the 'Plugins' menu in WordPress.

You will find the 'Webhook' tab in the form editor.

== Frequently Asked Questions ==

= Did you rename the plugin? =

Yes. It was called CF7 to Zapier and changed its name because of the [trademark rules of WordPress.org](https://make.wordpress.org/plugins/2019/08/08/trademark-enforcement/).

= Does it work with the block editor (Gutenberg)? =

Yes. It works with WordPress 5.0 or later and with the current versions of Contact Form 7.

= Does it work with forms not made with Contact Form 7? =

No. It only sends Contact Form 7 forms.

= Can I use it without Zapier? =

Yes. Zapier is one option, but any URL that receives data works. By default, the plugin sends JSON.

= How can I send one form to multiple webhooks? =

Add one URL per line in "Webhook URL". If one of them fails, the next ones are not sent; see [Make multiple webhooks optionals](https://cf7-to-webhook.valney.dev/docs/how-to/make-multiple-webhooks-optionals).

= How can I send uploaded files? =

The plugin copies each file before Contact Form 7 deletes it and sends its link, or sends the file content. You can also keep the files in the media library and delete them after a number of days. See [Send files](https://cf7-to-webhook.valney.dev/docs/how-to/send-files).

= My data arrives empty or does not arrive. =

See [Troubleshoot missing data](https://cf7-to-webhook.valney.dev/docs/how-to/troubleshoot). If that does not help, ask in the [support forum](https://wordpress.org/support/plugin/cf7-to-zapier/).

= Who is the developer? =

[Mário Valney](https://mariovalney.com/me), a Brazilian developer who is part of the [WordPress community](https://profiles.wordpress.org/mariovalney).

= How can I help? =

Visit the [GitHub repository](https://github.com/mariovalney/cf7-to-zapier) or [make a donation](https://www.paypal.com/donate?campaign_id=9AA82JCSNWNFS).

= More questions? =

See the [FAQ](https://cf7-to-webhook.valney.dev/faq) and the [documentation](https://cf7-to-webhook.valney.dev/docs) on the plugin website.

== Screenshots ==

1. Webhook configuration
2. Using templates
3. All request methods

== Changelog ==

= 5.3.1 =

* Fixed a custom body becoming invalid JSON when a checkbox or multiple select is inside quotes, as in the Slack template. Inside quotes, the options are now sent as text separated by commas (props to @jakhongirikromidinov for the report).
* The 'ctz_post_request_args' filter now receives the form data as its fifth parameter.

= 5.3.0 =

New feature: Delete uploaded files after a number of days (props to @Zodiac1978 for the suggestion).

* New "Delete after" option, in "Media" under Advanced settings: once a day, deletes the files of the form older than that number of days, including their media library attachments. Only administrators can change it.
* Upload folders now start with the submission date (UTC).

= 5.2.0 =

New feature: Add uploaded files to the media library (props to @Zodiac1978 for the suggestion).

* New "Add to Library" option, in "Media" under Advanced settings: each uploaded file becomes a private attachment, even when sending file content.
* New "Delete all files" button to delete every file uploaded with a form, including its media library attachments. Only administrators can use it.
* Upload folders now get an empty index.php, so servers with directory listing on do not list submissions.
* Fixed templates not filling the body with the form fields since 5.1.0.
* Fixed the template selector staying empty on sites with WP_DEBUG on.
* Fixed the form editor asking to confirm leaving the page without changes.
* Fixed header injection through form fields in custom headers (props to @kta1kri for the responsible disclosure).
* Documentation moved to [cf7-to-webhook.valney.dev/docs](https://cf7-to-webhook.valney.dev/docs), with new guides and a FAQ. The Help section of the webhook panel now points there.
* Requires WordPress 5.0 or later.
* Reviewed the plugin with the [WordPress agent skills](https://github.com/WordPress/agent-skills) and made some improvements.
* Debug helpers now need CFTZ_ADD_DEBUG_FUNCTIONS.

= 5.1.1 =

* Fixed webhooks not being sent when the submitted data has invalid UTF-8. Invalid sequences are now replaced, as Contact Form 7 does (props to @Zodiac1978 for a suggestion).
* Fixed fields with invalid UTF-8 being sent empty when using a custom body.
* Fixed a PHP warning ("Undefined variable $data") in error notifications.

= 5.1.0 =

* Fixed XSS vulnerabilities in admin panel output (body preview and form input helpers).
* Added capability check (`wpcf7_edit_contact_form`) on webhook settings save.
* Added server-side HTTP method whitelist (GET, POST, PUT, PATCH, DELETE).
* Sensitive headers (Authorization, x-api-key, etc.) are now redacted from error notification emails.
* Replaced `uniqid()` with `random_bytes()` for uploaded file directory names.
* Replaced raw `<script>` echo with `wp_add_inline_script()` for admin JS data.

Note:

If your site uses custom roles that can edit CF7 forms but do not have the `wpcf7_edit_contact_form` capability, webhook settings will no longer be saved for those users. Grant the capability explicitly or use the `wpcf7_edit_contact_form` capability in your role setup.

= 5.0.1 =

Security patch release.

* Added SSRF protection: outbound webhook requests now use `wp_safe_remote_request()`, blocking requests to private, loopback, and link-local addresses (CVE-2026-11395).

= 5.0.0 =

New feature: Added support for mail tags in headers (props to @anthonypenner).

* Headers can now be replaced by all data sent to webhook.
* Special mail tags are automatically available in headers without explicit configuration.

Support to "Contact Form 7 Multi-Step Forms" plugin.

* Check FAQ.
* Added 'ctz_get_data_from_cf7msm_posted_data' filter to deactivate this support.

Note:

It's not a breaking change update but we had some changes in custom header / body.
They are very complex features that I'm not sure how everyone is using them in the wild. This is the reason we are going to 5.0.0.

= 4.0.2 =

* Improved notification (headers and method added).

= 4.0.1 =

* Fixes 'ctz_post_request_result' action not triggering on errors.
* Added 'ctz_post_request_ignore_errors' filter to ignore error handle.

= 4.0.0 =

* New feature: TEMPLATES.
* New feature: advanced custom body.
* New feature: error notification and status check.
* New feature: send file content as base64 (props to @ozanerturk).
* Settings UI renewed.

* New template: Slack Integration.

= 3.0.2 =

* Avoid empty webhook URLs.

= 3.0.1 =

* Just some docs and donate link.
* Added 'ctz_remove_donation_alert' filter to remove donate link.

= 3.0.0 =

* New feature: placeholders in webhook URL.
* Added 'ctz_hook_url_placeholder' filter.
* Tested against new CF7 and WP versions.

= 2.4.0 =

* Added support to "_raw_" values (label value in [PIPES](https://contactform7.com/selectable-recipient-with-pipes/)).
* Added support to multiple webhook URLs.
* Added 'ctz_trigger_webhook_errors' action to allow trigger submission error after ignoring them.
* Tested against new CF7 and WP versions.

= 2.3.0 =

* Added Custom Header option.
* Added 'ctz_ignore_default_webhook' to allow ignore core submit.
* Added more parameters to 'ctz_trigger_webhook' action.
* Added more parameters to 'ctz_post_request_args' action.

= 2.2.5 =

* Some minor adjustments.
* Tested against new CF7 and WP versions.

= 2.2.4 =

* Support to CF7 new way to load properties.

= 2.2.3 =

* Support to CF7 multiple files upload.
* Support to files with same name.

= 2.2.2 =

* Support to CF7 5.2.1 changing 'wpcf7_special_mail_tags' filter.

= 2.2.1 =

* Support to CF7 5.2 changing 'free_text' input name.

Props to @brunojlt

= 2.2.0 =

* Support to free_text option on radio and checkboxes.

= 2.1.4 =

* Added 'ctz_hook_url' filter to change webhook URL

Props to @shoreline-chrism

= 2.1.2 =

* Fix checkboxes.

= 2.1.1 =

* Fix slashes on POST data.

= 2.1.0 =

* Support to rename fields.

= 2.0.2 =

* Plugin renamed.

= 2.0.0 =

* Support to submit files.

= 1.4.0 =

* Show form error when WordPress request fails and added support to throw or own exceptions.
* Added 'ctz_post_request_result' action after submit.
* Added 'ctz_trigger_webhook_error_message' filter to change form message error.

= 1.3.1 =

* Remove PHP 7+ dependency.
* It's sad... I know.

= 1.3.0 =

* Added support to [Special Mail Tags] (https://contactform7.com/special-mail-tags) on CF7.
* Tested against WP 5.0.2 and CF7 version 5.1.

= 1.2.1 =

* Tested against Contact Form 7 version 5.0.

= 1.2 =

* Added support to [PIPE](https://contactform7.com/selectable-recipient-with-pipes) on CF7.
* Tested against WP 4.9.2.

= 1.1.1 =

* Fixed problem with a function inside empty() prior PHP 5.5.

= 1.1 =

* Added the 'application/json' header by default to POST request.
* Added 'ctz_post_request_args' filter to POST request args.
* Tested against WP 4.9.

= 1.0 =

* It's alive!
* Form configuration.
* Integration to Zapier webhook.
* Ignore or not CF7 mail sent.

== Upgrade Notice ==

= 5.3.1 =

Fixes custom bodies, including the Slack template, being sent as plain text when a checkbox is inside quotes.

= 5.3.0 =

Adds an option to delete uploaded files after a number of days.

= 5.2.0 =

Adds uploaded files to the media library and a button to delete them. Fixes templates not filling the body since 5.1.0.

= 5.1.1 =

Fixes webhooks not being sent when a submission contains invalid UTF-8.

= 5.1.0 =

Security improvements. If your site uses custom roles to edit CF7 forms, ensure those roles have the `wpcf7_edit_contact_form` capability or webhook settings will not be saved.

= 5.0.1 =

Security patch. Strongly recommended for all users. Fixes SSRF vulnerability introduced in 3.0.0 (placeholders in webhook URL). No breaking changes for webhooks pointing to external URLs. Internal/private IP webhooks will now be blocked by design.

= 5.0.0 =

It's not a breaking change update but we had some changes in custom header / body.
They are very complex features that I'm not sure how everyone is using them in the wild. This is the reason we are going to 5.0.0.

Added support for replacement in headers and "Contact Form 7 Multi-Step Forms".
