=== CF7 to Webhook ===

Contributors: mariovalney
Donate link: https://www.paypal.com/donate?campaign_id=9AA82JCSNWNFS
Tags: cf7, contact form, zapier, integration, webhook
Requires at least: 4.7
Tested up to: 6.8
Stable tag: 4.0.3
Requires PHP: 7.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Use Contact Form 7 as a trigger to any webhook!

== Description ==

**CF7 to Webhook** is trusted by more than 30.000 WordPress websites and translated in languages!

Thank you!

---

[Contact Form 7 (CF7)](https://wordpress.org/plugins/contact-form-7/ "Install it first, of course") is a awesome plugin used by 1+ million WordPress websites.

Webhooks are endpoint (urls) you can send data!

Now you can join both: the best contact form plugin to WordPress and any webhook which receive JSON!

And Zapier?

[Zapier (Zapier)](https://zapier.com) is a awesome service to connect your apps and automate workflows!

Just activate and configure Zapier to receive data!

Disclaimer: this plugin was created without any encouragement from Zapier or CF7 developers and any webhook/API service.

= How to Use =

Easily and quickly! Just activate "Contact Form 7" and "CF7 to Webhook" and configure a URL to send data (or go to Zapier to create your Zap).

= Translations =

You can [translate CF7 to Webhook](https://translate.wordpress.org/projects/wp-plugins/cf7-to-zapier) to your language.

= Review =

We would be grateful for a [review here](https://wordpress.org/support/plugin/cf7-to-zapier/reviews/).

= Support =

* Contact Form 7 - 6.0.X

Tested with other plugins:

* MultiLine files for Contact Form 7 - 2.9.1

== Installation ==

`Install [Contact Form 7](https://wordpress.org/plugins/contact-form-7/) and activate it.`

* Install "CF7 to Webhook" by plugins dashboard.

Or

* Upload the entire `cf7-to-zapier` folder to the `/wp-content/plugins/` directory.

Then

* Activate the plugin through the 'Plugins' menu in WordPress.

You will find 'Zapier' tab into form configuration.

== Frequently Asked Questions ==

= Did you renamed the plugin? =

Yes. Due to [this](https://make.wordpress.org/plugins/2019/08/08/trademark-enforcement/).

= Does it works with Gutenberg? =

Yes. We support WordPress 5+ and CF7 too.

= Does it works for forms sent out of CF7? =

Nope. The intention here is to integrate CF7 to Zapier (and another webhooks).

= Can I use it without Zapier? =

Yep. We are creating a integration to Zapier webhook, but you can insert any URL to receive a JSON formated data.

= My sent data is empty =

Please, go to [support forum](https://wordpress.org/support/plugin/cf7-to-zapier/) to get help.

= How can I submit one form to multiple webhooks? =

Just add one webhook per line in "Webhook URL" settings.

Please, read [this topic](https://wordpress.org/support/topic/make-multiple-webhooks-optionals/) for more information.

= How can I show webhook errors on form submit? =

We already show WordPress request errors. If you want to add theatment to webhook errors, please [check this post](https://wordpress.org/support/topic/form-sent-to-zapier-randomly/#post-11249864).

= How can I upload files and send link to webhook? =

If you send a form with file, we will copy this to a directory before CF7 remove it and send the link to Zapier.

= How can I rename a field to webhook? =

You can add a "webhook" option to your field on form edit tab.

It's like the "class" option: `[text your-field class:form-control id:field-id webhook:webhook-key]`.

This will create a text field with name "your-field", class "form-control", id "field-id" and will be sent to webhook with key "webhook-key".

= How I can get the free text value? =

We will replace the value for last option (which is the free_text input) with the value.

This way your webhook will receive the free text value and other options if you allow it (like in checkbox).

= I don't see a template for my webhook. =

Templates are created by community so we're constructing this together.

You still are able to add a custom header / body or you can open a ticket and propose a new template.

= Who is the developer? =

[Mário Valney](https://mariovalney.com/me)

Brazilian developer who is part of [WordPress community](https://profiles.wordpress.org/mariovalney).

= Can I help you? =

Yes! Visit [GitHub repository](https://github.com/mariovalney/cf7-to-zapier) or [make a donation](https://www.paypal.com/donate?campaign_id=9AA82JCSNWNFS).

== Screenshots ==

1. Webhook configuration
2. Using templates
3. All request methods

== Changelog ==

= 4.0.2 =

* Improved notification (headers and method added).

= 4.0.1 =

* Fixes 'ctz_post_request_result' action not triggering on errors.
* Added 'ctz_post_request_ignore_errors' filter to ignore error handle.

= 4.0.0 =

* New feature: [TEMPLATES](https://wordpress.org/support/topic/how-templates-works).
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

* New feature: placeholders in webhook URL [read more](https://wordpress.org/support/topic/use-webhook-url-placeholders).
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

= 4.0.3 =

We have a lot of new features and a new UI!
The most cool new feature is templates! Take a look!

It's not a breaking change version, but we recommend to test your form after update (we have new settings: saving the form maybe help).

New options:

* Templates!
* Advanced custom body.
* Error notification and response status check.
* Send file content as Base64 instead of create a link to download.

More changes:

* Tested against new CF7 and WP versions.
* Added support to "MultiLine files for Contact Form 7" plugin.
* Some fixes (including translations).
