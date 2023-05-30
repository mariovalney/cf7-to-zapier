=== CF7 to Webhook ===

Contributors: mariovalney
Donate link: https://github.com/mariovalney/cf7-to-zapier
Tags: cf7, contact form, zapier, integration, contact form 7, webhook, mariovalney
Requires at least: 4.7
Tested up to: 6.2
Stable tag: trunk
Requires PHP: 7.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Use Contact Form 7 as a trigger to any webhook like Zapier!

== Description ==

[Contact Form 7 (CF7)](https://wordpress.org/plugins/contact-form-7/ "Install it first, of course") is a awesome plugin used by 1+ million WordPress websites.

Webhooks are endpoint (urls) you can send data!

Now you can join both: the best contact form plugin to WordPress and any webhook which receive JSON!

And Zapier?

[Zapier (Zapier)](https://zapier.com) is a awesome service to connect your apps and automate workflows!

Just activate and configure Zapier to receive data!

Disclaimer: this plugin was created without any encouragement from Zapier or CF7 developers and any webhook/API service.

= How to Use =

Easily and quickly! Just activate "Contact Form 7" and "CF7 to Webhook" and configure a URL to send data (or go to Zapier to create your Zap).

= Configuration =

This is a example to integrate with Zapier:

1. Create a Zap.
1. Choose your trigger as "Webhooks" app (Screenshot 2).
1. Choose "Catch Hook" option (Screenshot 3).
1. Done! Now insert the URL given (Screenshot 4) into your Contact Form configuration and activate integration.

= Creating your workflow =

After configuration you can send one form to create a example data into Zapier dashboard. Then you can continue creating your workflow with filters and other apps.

= Translations =

You can [translate CF7 to Webhook](https://translate.wordpress.org/projects/wp-plugins/cf7-to-zapier) to your language.

= Review =

We would be grateful for a [review here](https://wordpress.org/support/plugin/cf7-to-zapier/reviews/).

= Support =

* Contact Form 7 - 5.7.6

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

= Who are the developers? =

[Mário Valney](https://mariovalney.com/me)

Brazilian developer who is part of [WordPress community](https://profiles.wordpress.org/mariovalney).

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

= Can I help you? =

Yes! Visit [GitHub repository](https://github.com/mariovalney/cf7-to-zapier).

== Screenshots ==

1. CF7 to Webhook configuration
2. Zapier Step 1 - Choosing you app trigger
3. Zapier Step 2 - Choosing Catch Hook option
4. Zapier Step 3 - Webhook URL (waiting for first data)

== Changelog ==

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

= 2.2.2 =

Support to free_text option on radio and checkboxes.

Now we will replace the value for last option with the "free_text" value.
So we can support the radio "others" and still receive other selected options for checkboxes.

On 2.2.1: support to CF7 5.2 changing 'free_text' input name.
On 2.2.2: Support to CF7 5.2.1 changing 'wpcf7_special_mail_tags' filter.
