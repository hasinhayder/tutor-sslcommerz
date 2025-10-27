<?php
/**
 * Init class
 *
 * @author Hasin Hayder <https://github.com/hasinhayder>
 * @link https://github.com/hasinhayder/tutor-sslcommerz
 * @since 1.0.0
 */

namespace TutorSslcommerz;

/**
 * Init class
 * 
 * This class initializes the SSLCommerz Payment Gateway by registering hooks and filters for integrating with Tutor's payment 
 * system. It adds the SSLCommerz method to Tutor's list of payment gateways.
 */
final class Init {
    /**
     * SSLCommerz gateway configuration array
     *
     * @since 1.0.0
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
     *
     * @since 1.0.0
     */
    public function __construct() {
        add_filter('tutor_gateways_with_class', [self::class,'payment_gateways_with_ref'], 10, 2);
        add_filter('tutor_payment_gateways_with_class', [self::class,'add_payment_gateways']);
        add_filter('tutor_payment_gateways', [$this, 'add_tutor_sslcommerz_payment_method'], 100);
    }

    /**
     * Add SSLCommerz gateway class references for webhook processing
     *
     * Used by the tutor_gateways_with_class filter to provide class references
     * for SSLCommerz gateway when processing webhook notifications.
     *
     * @since 1.0.0
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
     * @since 1.0.0
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
     * @since 1.0.0
     *
     * @param array $methods Existing Tutor payment methods array.
     *
     * @return array Modified payment methods array with SSLCommerz configuration added.
     */
    public function add_tutor_sslcommerz_payment_method(array $methods): array {
        $sslcommerz_payment_method = [
            'name' => 'sslcommerz',
            'label' => 'SSLCommerz',
            'is_installed' => true,
            'is_active' => true,
            'icon' => TUTOR_SSLCOMMERZ_URL . 'assets/sslcommerz-logo.png',
            'support_subscription' => false, // SSLCommerz doesn't support subscriptions
            'fields' => [
                    [
                        'name' => 'environment',
                        'type' => 'select',
                        'label' => 'Environment',
                        'options' => [
                            'sandbox' => 'Sandbox',
                            'live' => 'Live',
                        ],
                        'value' => 'sandbox',
                    ],
                    [
                        'name' => 'store_id',
                        'type' => 'text',
                        'label' => 'Store ID',
                        'value' => '',
                        'desc' => 'Your SSLCommerz Store ID. For sandbox, register at https://developer.sslcommerz.com/registration/',
                    ],
                    [
                        'name' => 'store_password',
                        'type' => 'secret_key',
                        'label' => 'Store Password',
                        'value' => '',
                        'desc' => 'Your SSLCommerz Store Password (NOT your merchant panel password)',
                    ],
                    [
                        'name' => 'webhook_url',
                        'type' => 'webhook_url',
                        'label' => 'IPN URL',
                        'value' => '',
                        'desc' => 'Copy this URL and add it to your SSLCommerz merchant panel as IPN URL',
                    ],
                ],
        ];

        $methods[] = $sslcommerz_payment_method;
        return $methods;
    }

}