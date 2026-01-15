=== Lunatec Callback Widget ===
Contributors: fujahgabriel
Tags: callback, contact form, floating button, hubspot, lead generation
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A simple, customizable plugin for callback requests via a floating button and modal. Includes Hubspot, Slack and Email integrations.

== Description ==

**Lunatec Callback Widget** adds a floating button to your WordPress site. When clicked, it opens a clean, responsive modal form where visitors can leave their name, phone number, and other details to request a call back.

The plugin is designed to be lightweight, easy to configure, and developer-friendly.

### Features

*   **Floating Request Button**: A customizable floating button that stays visible as users scroll.
*   **Modal Form**: A user-friendly popup form for collecting callback requests.
*   **International Phone Support**: Integrated international telephone input with country flags and codes.
*   **Admin Management**: View all callback requests in a dedicated admin dashboard with status badges.
*   **CSV Export**: Easily export all requests to a CSV file for external processing.
*   **CRM & Integrations**:
    *   **HubSpot Sync**: Automatically create contacts in HubSpot when a request is received.
    *   **Slack Notifications**: Receive instant notifications in your Slack channel via Webhook.
    *   **Email Notifications**: Get notified via email immediately.
*   **Customization**:
    *   Change button text, colors, and positioning (Top/Bottom, Left/Right).
    *   **Precision Control**: Adjust specific X/Y margins for the floating button.
    *   Customize modal texts and size.
*   **Shortcode Support**: Use `[lcbw_callback_button]` to place a button anywhere.

== Installation ==

1.  Upload the `lunatec-callback-widget` folder to the `/wp-content/plugins/` directory.
2.  Activate the plugin through the 'Plugins' menu in WordPress.
3.  Go to **Simple Call Me Back > Settings** to configure the button appearance and form options.
4.  Go to **Simple Call Me Back > Requests** to view submissions.

== Frequently Asked Questions ==

= Can I change the position of the floating button? =
Yes, you can choose between Bottom Right, Bottom Left, Top Right, and Top Left in the settings page. You can also define specific X and Y margins.

= Where is the data stored? =
The data is stored in your WordPress database in a custom table created by the plugin (`wp_lcbw_requests`).

= How do I get a HubSpot API Key? =
Go to your HubSpot Settings > Integrations > Private Apps. Create a new app, select the "crm.objects.contacts.write" scope, and paste the Access Token into the plugin settings.

== External services ==

This plugin may connect to external services depending on your configuration:

= HubSpot CRM =
When HubSpot integration is enabled, this plugin sends contact form data (name, phone, email, message) to HubSpot's CRM API to create or update contacts.
Data is sent only when a user submits the callback form and HubSpot integration is configured.
Service provided by HubSpot: https://legal.hubspot.com/terms-of-service | https://legal.hubspot.com/privacy-policy

= Slack Notifications =
When Slack integration is enabled, this plugin sends form submission notifications to your configured Slack webhook URL.
Data includes form submission details (name, phone, email, message) and is sent only when a user submits the callback form.
Service provided by Slack: https://slack.com/terms-of-service | https://slack.com/privacy-policy

== Screenshots ==

1. Modal Form
2. Settings Page
3. Requests Dashboard

== Changelog ==

= 1.0.2 =
*   New: Added Email Notification support.
*   New: Added Slack Webhook integration.
*   Improvement: Added status badges (New, Contacted, Closed) to the admin list.

= 1.0.1 =
*   New: HubSpot Integration.
*   New: Margin X and Y positioning settings.
*   Fixed: Code refactoring and constants.

= 1.0.0 =
*   Initial Release.
