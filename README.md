# Tutor SSLCommerz Payment Gateway

**Author:** Hasin Hayder  
**GitHub:** [https://github.com/hasinhayder](https://github.com/hasinhayder)  
**Plugin Repository:** [https://github.com/hasinhayder/tutor-sslcommerz](https://github.com/hasinhayder/tutor-sslcommerz)

SSLCommerz payment gateway integration for Tutor LMS. This plugin enables one-time course payments through SSLCommerz.

## Features

- ✅ One-time payments for course purchases
- ✅ Support for multiple currencies (BDT, USD, EUR, GBP, etc.)
- ✅ Sandbox and Live environment support
- ✅ IPN (Instant Payment Notification) integration
- ✅ Secure payment processing with hash validation
- ✅ Transaction validation through SSLCommerz API
- ✅ Support for all SSLCommerz payment methods (Cards, Mobile Banking, Internet Banking)
- ✅ Internationalization (i18n) support for translations
- ✅ WordPress HTTP API for secure external communications

## Requirements

- WordPress 5.3 or higher
- PHP 7.4 or higher
- Tutor LMS (Free version)
- SSLCommerz merchant account

## Installation

1. Upload the plugin folder to `/wp-content/plugins/tutor-sslcommerz`
2. Activate the plugin through WordPress admin
3. Ensure Tutor LMS is activated
4. Configure settings in Tutor LMS > Settings > Payments

## Configuration

### Step 1: Get SSLCommerz Credentials

**For Sandbox (Testing):**
1. Register at [https://developer.sslcommerz.com/registration/](https://developer.sslcommerz.com/registration/)
2. You'll receive Store ID and Store Password via email

**For Live (Production):**
1. Apply for merchant account at [https://sslcommerz.com/](https://sslcommerz.com/)
2. Complete KYC verification
3. Get your Store ID and Store Password from merchant panel

### Step 2: Configure Plugin

1. Go to **Tutor LMS > Settings > Payments**
2. Find **SSLCommerz** in the payment gateways list
3. Click to enable and configure:
   - **Environment**: Select `Sandbox` for testing or `Live` for production
   - **Store ID**: Enter your SSLCommerz Store ID
   - **Store Password**: Enter your Store Password (NOT your merchant panel login password)
   - **IPN URL**: Copy this URL
   
![SSLCommerz Configuration](https://h1.lwhh.org/sslcommerz/image-1x.jpg)

### Step 3: Configure SSLCommerz Merchant Panel

1. Login to your SSLCommerz merchant panel
2. Go to IPN Settings for your store
3. Add the IPN URL from step 2
4. Save settings

![SSLCommerz Configuration](https://h1.lwhh.org/sslcommerz/image-2.jpg)

## Testing

### Using Sandbox Environment

1. Set environment to "Sandbox"
2. Use sandbox credentials
3. Test with SSLCommerz test cards:
   - Test Card Number: `4111111111111111`
   - Any future expiry date
   - Any CVV

### Test Transaction Flow

1. Create a test course in your LMS
2. Set a price for the course
3. Add course to cart and proceed to checkout
4. Select SSLCommerz as payment method
5. Complete payment on SSLCommerz page
6. Verify order status in Tutor LMS

## How It Works

### Payment Flow

```
Student clicks "Purchase" 
    ↓
Plugin sends payment request to SSLCommerz
    ↓
Student redirected to SSLCommerz payment page
    ↓
Student completes payment
    ↓
SSLCommerz sends IPN notification to your site
    ↓
Plugin validates transaction with SSLCommerz API
    ↓
Order status updated (Success/Failed/Cancelled)
    ↓
Student gets access to course (if successful)
```

### Security Features

1. **Hash Verification**: Validates SSLCommerz callback signatures
2. **Transaction Validation**: Double-checks payment status with SSLCommerz API
3. **Amount Verification**: Ensures paid amount matches order amount
4. **SSL Communication**: All API calls use HTTPS

## Supported Currencies

SSLCommerz supports the following currencies:
- BDT (Bangladeshi Taka) - Primary
- USD (US Dollar)
- EUR (Euro)
- GBP (British Pound)
- SGD (Singapore Dollar)
- INR (Indian Rupee)
- MYR (Malaysian Ringgit)

**Note:** For non-BDT currencies, SSLCommerz converts to BDT at current exchange rates.

## API Integration Details

### Payment Initiation
- **Endpoint**: `{api_domain}/gwprocess/v4/api.php`
- **Method**: POST
- **Authentication**: Store ID & Store Password

### Transaction Validation
- **Endpoint**: `{api_domain}/validator/api/validationserverAPI.php`
- **Method**: GET
- **Purpose**: Verify payment status

### IPN Callback
- Receives POST data from SSLCommerz
- Validates transaction
- Updates order status

## File Structure

```
tutor-sslcommerz/
├── tutor-sslcommerz.php           # Main plugin file
├── composer.json                  # Autoload configuration
├── readme.txt                     # WordPress plugin readme
├── README.md                      # This file
├── assets/                        # Plugin assets
│   └── sslcommerz-logo.png        # Gateway logo (add your own)
├── integration/                   # Tutor LMS integration
│   ├── Init.php                   # Plugin initialization
│   ├── SslcommerzConfig.php       # Configuration class
│   └── SslcommerzGateway.php      # Gateway registration
├── languages/                     # Translation files
│   └── tutor-sslcommerz.pot       # Translation template
├── payments/                      # Payment processing
│   └── Sslcommerz.php             # Core payment logic
└── vendor/                        # Composer autoload
```

## Internationalization (i18n)

This plugin supports internationalization and is translation-ready. All user-facing strings are wrapped with WordPress translation functions.

### For Translators

1. Use the `languages/tutor-sslcommerz.pot` file as a template
2. Create language-specific `.po` files using tools like Poedit or Loco Translate
3. Compile `.mo` files and place them in the `languages/` directory
4. File naming: `tutor-sslcommerz-{locale}.mo` (e.g., `tutor-sslcommerz-bn_BD.mo` for Bengali)

### Text Domain

- **Text Domain:** `tutor-sslcommerz`
- **Domain Path:** `/languages/`

### Available Languages

Currently available in:
- English (default)

Contributions for additional language translations are welcome!

## Troubleshooting

### Payment Not Processing

1. **Check Store Credentials**: Ensure Store ID and Password are correct
2. **Environment Mismatch**: Sandbox credentials won't work in Live mode
3. **IPN URL**: Verify IPN URL is correctly configured in SSLCommerz panel
4. **SSL Certificate**: Ensure your site has valid SSL certificate

### Transaction Validation Failed

1. Check if IPN URL is accessible (not blocked by firewall)
2. Verify webhook_url in plugin settings
3. Enable debug logging in WordPress (WP_DEBUG)
4. Check error logs for detailed messages

### Order Status Not Updating

1. Verify IPN is configured correctly
2. Check if order ID is being passed correctly (value_a parameter)
3. Ensure hash verification is working
4. Check webhook response in browser console

## Known Limitations

1. **No Subscription Support**: SSLCommerz doesn't provide native recurring payment functionality
2. **Currency Conversion**: Non-BDT transactions are auto-converted to BDT
3. **Refunds**: Manual refund processing through SSLCommerz merchant panel required


## Support

For issues related to:
- **Plugin functionality**: Create issue on GitHub or contact plugin developer
- **SSLCommerz API**: Contact SSLCommerz support at support@sslcommerz.com
- **Tutor LMS**: Contact Themeum support

## License

This plugin is licensed under GPLv2 or later.

## Credits

- Developed for Tutor LMS
- SSLCommerz API integration
- Based on Tutor LMS Payment Gateway framework

## Additional Resources

- [SSLCommerz Documentation](https://developer.sslcommerz.com/documentation/)
- [Tutor LMS Documentation](https://docs.themeum.com/tutor-lms/)
- [SSLCommerz Merchant Panel](https://merchant.sslcommerz.com/)
- [SSLCommerz Developer Portal](https://developer.sslcommerz.com/)

