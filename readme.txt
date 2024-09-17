=== GTM First-Party Mode ===
Contributors: mariuszbrucki
Donate link: https://mariuszbrucki.pl/
Tags: google tag manager, first-party mode, analytics, data tracking, gtm
Requires at least: 5.0
Tested up to: 6.6
Requires PHP: 7.0
Stable tag: 1.20
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl.html

GTM First-Party Mode routes requests through the WordPress backend to fps.goog and inserts GTM script in <head> for first-party data tracking.

== Description ==

GTM First-Party Mode is a WordPress plugin that helps you manage and route requests to Google Tag Manager (GTM) in a first-party mode. This ensures that all requests are routed through your WordPress backend, enhancing privacy and control over data tracking.

First-party mode lets you deploy GTM using your own first-party infrastructure, hosted on your website's domain. This infrastructure sits between your website and Google's services, making your first-party infrastructure the only technology to interact directly with your website users.

= Features =
* Custom GTM ID configuration
* Set a custom path for serving tags
* REST API endpoint for handling GTM data
* Automatic insertion of GTM script in the <head> section of your site

= External Service Usage =
This plugin communicates with an external service provided by Google Tag Manager in first-party mode. The requests are routed through your WordPress backend to the following domain:
- **fps.goog**

For more information, you can review the service's Terms of Service and Privacy Policies:
- [Google Tag Manager Terms of Service](https://www.google.com/analytics/terms/tag-manager/)
- [Google Privacy Policy](https://policies.google.com/privacy)

Please ensure that your usage of this plugin complies with any legal obligations regarding data transmission and privacy.

== Installation ==

1. Upload the `gtm-first-party-mode` folder to the `/wp-content/plugins/` directory, or install the plugin directly through the WordPress plugins screen.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to 'Settings' > 'GTM First-Party Mode' to configure the plugin.
4. Enter your GTM ID and set the tag serving path. **Caution: This setup reroutes all traffic with the chosen path. To avoid affecting your website, choose a path that's not already in use.**

== Frequently Asked Questions ==

= How do I find my GTM ID? =
Your GTM ID can be found in your Google Tag Manager account. It typically looks like 'GTM-XXXXXX'.

= Can I customize the GTM script that is inserted? =
Currently, the plugin automatically inserts the standard GTM script. Customization options are planned for future updates.

= Can I use the GTM4WP plugin simultaneously with the GTM First-Party Mode Plugin? =
Yes, you can use both plugins simultaneously. However, it is advisable to use a different GTM container for the First-Party Mode. If you need to use the same GTM container, please disable the script injection in the GTM4WP plugin or any other plugin you are using for GTM code injection to avoid conflicts.

= Is the plugin compatible with my theme? =
The plugin is designed to be compatible with most WordPress themes. If you encounter any issues, please contact support.

== Changelog ==

= 1.20 =
* Initial release of GTM First-Party Mode.
* Custom GTM ID and path settings.
* REST API endpoint for GTM data.
* Automatic GTM script insertion.

== Upgrade Notice ==

= 1.20 =
Initial release. Configure your GTM ID and path in the settings.

== Screenshots ==

1. Plugin settings page
2. GTM script insertion in the head section

== Other Notes ==

This plugin is provided as-is without warranty of any kind. Please ensure you backup your site before installing any new plugins.
