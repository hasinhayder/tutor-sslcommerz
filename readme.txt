=== Tutor SSLCommerz ===
Contributors: hasinhayder
Tags: tutor, lms, sslcommerz, payment, bangladesh, e-commerce, gateway
Requires at least: 5.3
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.6
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

SSLCommerz payment gateway integration for Tutor LMS. This plugin enables one-time course payments through SSLCommerz, supporting multiple currencies and secure payment processing.

== Description ==

Tutor SSLCommerz Payment Gateway integrates SSLCommerz, Bangladesh's leading payment gateway, with Tutor LMS to enable seamless course purchases. Accept payments from local and international customers using cards, mobile banking, and internet banking.

= Features =

* One-time payments for course purchases
* Multi-currency support (BDT, USD, EUR, GBP, SGD, INR, MYR)
* Sandbox and Live environments for testing and production
* IPN (Instant Payment Notification) integration for automatic order updates
* Secure payment processing with hash validation and transaction verification
* All SSLCommerz payment methods (Cards, Mobile Banking, Internet Banking)
* WordPress HTTP API for secure external communications
* Comprehensive error handling and logging

= Requirements =

* WordPress 5.3 or higher
* PHP 7.4 or higher
* Tutor LMS (Free version)
* SSLCommerz merchant account

= How It Works =

1. Student initiates course purchase
2. Plugin sends payment request to SSLCommerz
3. Student completes payment on SSLCommerz secure page
4. SSLCommerz sends IPN notification to your site
5. Plugin validates transaction and updates order status
6. Student gains course access upon successful payment

= Security Features =

* Hash verification for callback signatures
* Transaction validation through SSLCommerz API
* Amount verification to prevent tampering
* SSL-secured API communications

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/tutor-sslcommerz`
2. Activate the plugin through the WordPress admin
3. Ensure Tutor LMS is installed and activated
4. Go to **Tutor LMS > Settings > Payments**
5. Enable SSLCommerz and configure settings

= Configuration =

**Step 1: Get SSLCommerz Credentials**

*Sandbox (Testing):*
1. Register at https://developer.sslcommerz.com/registration/
2. Receive Store ID and Store Password via email

*Live (Production):*
1. Apply for merchant account at https://sslcommerz.com/
2. Complete KYC verification
3. Get Store ID and Store Password from merchant panel

**Step 2: Configure Plugin**

1. Go to **Tutor LMS > Settings > Payments**
2. Find **SSLCommerz** in payment gateways
3. Enable and configure:
   * **Environment**: Sandbox for testing, Live for production
   * **Store ID**: Your SSLCommerz Store ID
   * **Store Password**: Your Store Password (not login password)
   * **IPN URL**: Copy this URL

**Step 3: Configure SSLCommerz Panel**

1. Login to SSLCommerz merchant panel
2. Go to IPN Settings
3. Add the IPN URL from plugin settings
4. Save settings

== Frequently Asked Questions ==

= Do I need a SSLCommerz account? =

Yes, you need a merchant account. Sign up at https://sslcommerz.com/ for live or https://developer.sslcommerz.com/registration/ for sandbox.

= Does this support subscriptions? =

No, only one-time payments are supported. SSLCommerz doesn't provide native recurring payment functionality.

= Can I test before going live? =

Yes, use Sandbox environment with test credentials. Test cards available in SSLCommerz documentation.

= What currencies are supported? =

BDT (primary), USD, EUR, GBP, SGD, INR, MYR. Non-BDT currencies are auto-converted to BDT at current rates.

= How do I troubleshoot payment issues? =

1. Verify Store ID and Password are correct
2. Ensure IPN URL is configured in SSLCommerz panel
3. Check environment settings (Sandbox vs Live)
4. Enable WordPress debug logging
5. Verify SSL certificate on your site

= What payment methods are supported? =

All SSLCommerz methods: Credit/Debit Cards, Mobile Banking (bKash, Nagad, Rocket), Internet Banking, and others available in Bangladesh.

= Is there a transaction fee? =

Transaction fees depend on your SSLCommerz merchant agreement. Contact SSLCommerz for pricing details.

= Can I process refunds? =

Refunds must be processed manually through the SSLCommerz merchant panel. The plugin doesn't handle automatic refunds.

== Changelog ==

= 1.0.6 =
* Feature: Added complete internationalization (i18n) support
* Feature: Created translation template (.pot file)
* Improvement: Added languages directory for translation files
* Improvement: Updated plugin constants and code structure
* Improvement: Enhanced documentation with translation information

= 1.0.5 =
Minor Fixes

= 1.0.4 =
Minor Fixes

= 1.0.3 =
* Improvement: Replaced cURL with WordPress HTTP API for better compatibility
* Improvement: Enhanced error handling and JSON validation
* Improvement: More descriptive error messages

= 1.0.2 =
* Security: Fixed fatal errors in IPN handling
* Security: Improved validation for webhook requests
* Improved: Better error logging and debugging

= 1.0.1 =
* Fixed: Corrected payment amount sending (was sending 0)
* Fixed: Updated to use correct Tutor LMS field names
* Improved: Added payment amount validation

= 1.0.0 =
* Initial release
* One-time payment support
* Sandbox and Live environments
* IPN integration
* Multi-currency support
* Transaction validation

== Upgrade Notice ==

= 1.0.6 =
Adds internationalization support and improved documentation.

= 1.0.5 =
Minor Fixes

= 1.0.4 =
Minor Fixes

= 1.0.3 =
Replaces cURL with WordPress HTTP API for improved security and compatibility.

= 1.0.2 =
Fixes for IPN endpoint. Update immediately.

= 1.0.1 =
Fixes payment amount issue. Required for payments to work.

== Support ==

For plugin issues: [GitHub Issues](https://github.com/hasinhayder/tutor-sslcommerz/issues)
For SSLCommerz API: support@sslcommerz.com
For Tutor LMS: Themeum support

== Credits ==

Developed by Hasin Hayder
Based on Tutor LMS Payment Gateway framework
SSLCommerz API integration