<?php
/**
 * Init class
 *
 * @author Hasin Hayder <https://github.com/hasinhayder>
 * @link https://github.com/hasinhayder/tutor-sslcommerz
 */

namespace TutorSslcommerz;
use TutorSslcommerz\SslcommerzOrderProcess;

/**
 * Init class
 * 
 * This class initializes the SSLCommerz Payment Gateway by registering hooks and filters for integrating with Tutor's payment 
 * system. It adds the SSLCommerz method to Tutor's list of payment gateways.
 */
final class Init {
    /**
     * SSLCommerz gateway configuration array
     */
    private const SSLCOMMERZ_GATEWAY_CONFIG = [
        'sslcommerz' => [
            'gateway_class' => SslcommerzGateway::class,
            'config_class' => SslcommerzConfig::class,
        ],
    ];

    /**
     * Constructor - Register hooks and filters
     *
     * Registers WordPress filters to integrate SSLCommerz payment gateway with Tutor LMS:
     * - tutor_gateways_with_class: Adds gateway class references for webhook processing
     * - tutor_payment_gateways_with_class: Adds gateway to checkout integration
     * - tutor_payment_gateways: Adds payment method settings to Tutor admin
     */
    public function __construct() {
        add_filter('tutor_gateways_with_class', [self::class, 'payment_gateways_with_ref'], 10, 2);
        add_filter('tutor_payment_gateways_with_class', [self::class, 'add_payment_gateways']);
        add_filter('tutor_payment_gateways', [$this, 'add_tutor_sslcommerz_payment_method'], 100);
        add_filter('init', [$this, 'process_sslcommerz_form_submission']);
    }

    /**
     * Add SSLCommerz gateway class references for webhook processing
     *
     * Used by the tutor_gateways_with_class filter to provide class references
     * for SSLCommerz gateway when processing webhook notifications.
     *
     * @param array  $value   Existing gateway class references array.
     * @param string $gateway Gateway identifier being requested.
     *
     * @return array Modified gateway class references array.
     */
    public static function payment_gateways_with_ref(array $value, string $gateway): array {
        if (isset(self::SSLCOMMERZ_GATEWAY_CONFIG[$gateway])) {
            $value[$gateway] = self::SSLCOMMERZ_GATEWAY_CONFIG[$gateway];
        }

        return $value;
    }

    /**
     * Add SSLCommerz payment gateway to checkout integration
     *
     * Used by the tutor_payment_gateways_with_class filter to register
     * SSLCommerz gateway classes for checkout processing.
     *
     * @param array $gateways Existing payment gateways array.
     *
     * @return array Modified payment gateways array with SSLCommerz added.
     */
    public static function add_payment_gateways(array $gateways): array {
        return $gateways + self::SSLCOMMERZ_GATEWAY_CONFIG;
    }

    /**
     * Add SSLCommerz payment method configuration to Tutor settings
     *
     * Defines the complete configuration structure for SSLCommerz payment method
     * including all required fields (environment, store credentials, webhook URL)
     * and adds it to Tutor's payment methods list for admin configuration.
     *
     * @param array $methods Existing Tutor payment methods array.
     *
     * @return array Modified payment methods array with SSLCommerz configuration added.
     */
    public function add_tutor_sslcommerz_payment_method(array $methods): array {
        $sslcommerz_payment_method = [
            'name' => 'sslcommerz',
            'label' => __('SSLCommerz', 'tutor-sslcommerz'),
            'is_installed' => true,
            'is_active' => true,
            'icon' => TUTOR_SSLCOMMERZ_URL . 'assets/sslcommerz-logo.png',
            'support_subscription' => false, // SSLCommerz doesn't support subscriptions
            'fields' => [
                [
                    'name' => 'environment',
                    'type' => 'select',
                    'label' => __('Environment', 'tutor-sslcommerz'),
                    'options' => [
                        'sandbox' => __('Sandbox', 'tutor-sslcommerz'),
                        'live' => __('Live', 'tutor-sslcommerz'),
                    ],
                    'value' => 'sandbox',
                ],
                [
                    'name' => 'store_id',
                    'type' => 'text',
                    'label' => __('Store ID', 'tutor-sslcommerz'),
                    'value' => '',
                    'desc' => __('Your SSLCommerz Store ID. For sandbox, register at https://developer.sslcommerz.com/registration/', 'tutor-sslcommerz'),
                ],
                [
                    'name' => 'store_password',
                    'type' => 'secret_key',
                    'label' => __('Store Password', 'tutor-sslcommerz'),
                    'value' => '',
                    'desc' => __('Your SSLCommerz Store Password (NOT your merchant panel password)', 'tutor-sslcommerz'),
                ],
                [
                    'name' => 'webhook_url',
                    'type' => 'webhook_url',
                    'label' => __('IPN URL', 'tutor-sslcommerz'),
                    'value' => '',
                    'desc' => __('Copy this URL and add it to your SSLCommerz merchant panel as IPN URL', 'tutor-sslcommerz'),
                ],
            ],
        ];

        $methods[] = $sslcommerz_payment_method;
        return $methods;
    }

    /**
     * Handle template redirect for SSLCommerz payment gateway
     *
     * This method is hooked to the 'init' action to handle any necessary
     * template redirects related to SSLCommerz payment processing.
     *
     * @return void
     */
    public function process_sslcommerz_form_submission(): void {
        $sslcommerz = new SslcommerzOrderProcess();
        $sslcommerz->process_sslcommerz_form_submission();
    }

}