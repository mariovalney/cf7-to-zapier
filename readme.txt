# CF7 to ActionNetwork #
**Contributors:** [procom.dev](https://procom.dev)), [Mário Valney](https://mariovalney.com/me) 
**Tags:** cf7, contact form, actionnetwork, integration, contact form 7 
**Requires at least:** 4.7  
**Tested up to:** 6.6  
**Stable Tag: 1.0.0
**Requires PHP:** 7.4  
**License:** GPLv2 or later  
**License URI:** http://www.gnu.org/licenses/gpl-2.0.html  

Use Contact Form 7 to send data directly to ActionNetwork with automatic field mapping!

## Description ##

[Contact Form 7 (CF7)](https://wordpress.org/plugins/contact-form-7/) is a widely-used plugin by millions of WordPress websites for creating forms. 

The CF7 to ActionNetwork plugin allows you to send form data to ActionNetwork. The core fields required by ActionNetwork are automatically mapped from your CF7 form fields, while any additional custom fields are included in a "custom_fields" section within the JSON data.

Disclaimer: This plugin was created without any encouragement from ActionNetwork or CF7 developers. 

### How to Use ###

Here's an example to integrate with ActionNetwork:

1. Create an action in ActionNetwork: Forms, Petitions, Evenes, Ticketed Event or Letter Campaigns.
2. Copy the API Endpoint URL (you can find it at the bottom of the right sidebar when managing the action).
3. Insert the URL in your Contact Form 7 configuration under the 'ActionNetwork' tab and activate the integration.
4. Create a form in Contact Form 7. The names of the fields will be mapped to the ones in the ActionNetwork action.


### ActionNetwork Core Fields ###

Below are the core fields used by ActionNetwork:
- family_name
- given_name
- postal_code
- address_lines
- locality
- region
- country
- address
- status
- number

The core fields are automatically mapped to their corresponding keys in ActionNetwork. All other fields are included under "custom_fields".


### Frequently Asked Questions ###


#### Does it work with Gutenberg?
Yes. We it supports WordPress 5+ and CF7 too.

#### Does it work for forms sent out of CF7?
No. The intention here is to integrate CF7 with ActionNetwork.

#### My sent data is empty
Please, visit the [support forum](https://wordpress.org/support/plugin/cf7-to-actionnetwork/) for help.

#### Can I sent it to a non-ActionNEtwork webhook?
If you want to send data to a custom webhook, use this other plugin: [CF7 to Webhook](https://github.com/mariovalney/cf7-to-zapier).

#### How can I upload files and send links to ActionNetwork?
If you submit a form with a file, we will copy this to a directory before CF7 removes it and send the link to ActionNetwork.

#### How can I rename a field to ActionNetwork?
You can add an "actionnetwork" option to your field in the form edit tab. It’s similar to the "class" option: `[text your-field class:form-control id:field-id actionnetwork:actionnetwork-key]`. This will create a text field with name "your-field", class "form-control", id "field-id", and it will be sent to ActionNetwork with the key "actionnetwork-key".

### Who is the original developer?
[Mário Valney](https://mariovalney.com/me)
Brazilian developer who is part of the [WordPress community](https://profiles.wordpress.org/mariovalney).

### Can I help you?
Yes! Visit the [GitHub repository](https://github.com/mariovalney/cf7-to-actionnetwork).

## Installation ##

1. Install [Contact Form 7](https://wordpress.org/plugins/contact-form-7/) and activate it.
2. Install "CF7 to ActionNetwork" by using the plugins dashboard or upload the entire `cf7-to-actionnetwork` folder to the `/wp-content/plugins/` directory.
3. Activate the plugin through the 'Plugins' menu in WordPress.
4. You will find the 'ActionNetwork' tab in the form configuration.


## Changelog ##

### 1.0.0 ###
* Initial release.
* Integration to ActionNetwork with core fields and custom fields support.
* Data mapping from CF7 to ActionNetwork.
* ActionNetwork core fields detection and JSON structure adjustment.