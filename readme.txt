=== Connect CF7 to Action Network ===
Contributors: procomdev,mariovalney
Tags: cf7, contact form, actionnetwork, action network, contact form 7
Requires at least: 4.7
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Use Contact Form 7 to send data directly to Action Network with automatic field mapping

== Description ==
[Contact Form 7 (CF7)](https://wordpress.org/plugins/contact-form-7/) is a widely-used plugin by millions of WordPress websites for creating forms. 

The Connect CF7 to ActionNetwork plugin allows you to send form data to ActionNetwork. The core fields required by ActionNetwork are automatically mapped from your CF7 form fields, while any additional custom fields are included in a \"custom_fields\" section within the JSON data.

Disclaimer: This plugin was created without any encouragement from ActionNetwork or ContactForm7 developers.

### How to Use ###

Here\'s an example to integrate with ActionNetwork:

1. Create an action in ActionNetwork: Forms, Petitions, Evenes, Ticketed Event or Letter Campaigns.
2. Copy the API Endpoint URL (you can find it at the bottom of the right sidebar when managing the action).
3. Insert the URL in your Contact Form 7 configuration under the \'ActionNetwork\' tab and activate the integration.
4. Create a form in Contact Form 7. The names of the fields will be mapped to the ones in the ActionNetwork action.

### ActionNetwork Core Fields ###

Below are the core fields used by ActionNetwork:
– family_name
– given_name
– postal_code
– address_lines
– locality
– region
– country
– address
– status
– number

The core fields are automatically mapped to their corresponding keys in ActionNetwork. All other fields are included under \"custom_fields\".

### ActionNetwork source codes ###

Whatever you put in "?source=[here]" at the end of the URL where CF7 form is inserted, will be sent as Source Code to Action Network. Example of source codes
– https://yourwebsite.com/your-form/?source=ads
– https://yourwebsite.com/your-form/?source=email
– ttps://yourwebsite.com/your-form/?source=whatsapp

If no source is provided in the URL parameters, "contact-form-7" will be placed instead.

== Installation ==
1. Install [Contact Form 7](https://wordpress.org/plugins/contact-form-7/) and activate it.

2. Install \"CF7 to ActionNetwork\" by using the plugins dashboard or upload the entire `cf7-to-actionnetwork` folder to the `/wp-content/plugins/` directory.

3. Activate the plugin through the \'Plugins\' menu in WordPress.

4. You will find the \'ActionNetwork\' tab in the form configuration.

== Frequently Asked Questions ==
#### Does it work with Gutenberg?
Yes. We it supports WordPress 5+ and CF7 too.

#### Does it work for forms sent out of CF7?
No. The intention here is to integrate CF7 with ActionNetwork.

#### My sent data is empty
Please, visit the [support forum](https://wordpress.org/support/plugin/cf7-to-actionnetwork/) for help.

#### Can I sent it to a non-ActionNEtwork webhook?
If you want to send data to a custom webhook, use this other plugin: [CF7 to Webhook](https://github.com/mariovalney/cf7-to-zapier).

### Who is the original developer?
[Mário Valney](https://mariovalney.com/me)
Brazilian developer who is part of the [WordPress community](https://profiles.wordpress.org/mariovalney).

== Screenshots ==
1. Screenshot of the tab the plugin adds to any Form on CF7

== Changelog ==
### 1.0.0 ###
* Initial release.
* Integration to ActionNetwork with core fields and custom fields support.
* Data mapping from CF7 to ActionNetwork.
* ActionNetwork core fields detection and JSON structure adjustment.
* Added automatic Source (if any) and Referrer from the URL