<?php
/**
 * SSLCommerz Payment Gateway Integration
 *
 * Concrete implementation of the SSLCommerz payment gateway for Tutor LMS.
 * This class provides the necessary integration points for SSLCommerz payment processing
 * within the Tutor e-commerce ecosystem.
 *
 * @author Hasin Hayder <https://github.com/hasinhayder>
 * @link https://github.com/hasinhayder/tutor-sslcommerz
 * @since 1.0.0
 */

namespace TutorSslcommerz;

use Payments\Sslcommerz\Sslcommerz;
use Tutor\PaymentGateways\GatewayBase;

/**
 * SSLCommerz Payment Gateway Class
 *
 * This class extends GatewayBase to provide SSLCommerz payment gateway functionality
 * for Tutor LMS. It defines the gateway's directory structure, payment class, and
 * configuration class for seamless integration with the Tutor payment system.
 *
 * @since 1.0.0
 */
class SslcommerzGateway extends GatewayBase {

	/**
	 * Get the root directory name for the SSLCommerz payment gateway source files.
	 *
	 * This method returns the directory name where SSLCommerz payment gateway
	 * source files are located within the payments directory structure.
	 *
	 * @since 1.0.0
	 *
	 * @return string The directory name ('Sslcommerz').
	 */
	public function get_root_dir_name(): string {
		return 'Sslcommerz';
	}

	/**
	 * Get the payment class name for SSLCommerz integration.
	 *
	 * Returns the fully qualified class name of the SSLCommerz payment processor
	 * from the PaymentHub library, used for handling payment transactions.
	 *
	 * @since 1.0.0
	 *
	 * @return string The SSLCommerz payment class name.
	 */
	public function get_payment_class(): string {
		return Sslcommerz::class;
	}

	/**
	 * Get the configuration class name for SSLCommerz gateway.
	 *
	 * Returns the fully qualified class name of the SSLCommerz configuration class
	 * that manages gateway settings, credentials, and environment configuration.
	 *
	 * @since 1.0.0
	 *
	 * @return string The SSLCommerz configuration class name.
	 */
	public function get_config_class(): string {
		return SslcommerzConfig::class;
	}

	/**
	 * Get the autoload file path for the SSLCommerz payment gateway.
	 *
	 * Returns an empty string as SSLCommerz uses Composer autoloading
	 * and doesn't require a custom autoload file.
	 *
	 * @since 1.0.0
	 *
	 * @return string Empty string (Composer autoloading is used).
	 */
	public static function get_autoload_file(): string {
		return '';
	}
}