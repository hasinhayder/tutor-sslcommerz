<?php
/**
 * SSLCommerz Configuration class
 *
 * @author Hasin Hayder <https://github.com/hasinhayder>
 * @link https://github.com/hasinhayder/tutor-sslcommerz
 * @since 1.0.0
 */

namespace TutorSslcommerz;

use Tutor\Ecommerce\Settings;
use Ollyo\PaymentHub\Core\Payment\BaseConfig;
use Tutor\PaymentGateways\Configs\PaymentUrlsTrait;
use Ollyo\PaymentHub\Contracts\Payment\ConfigContract;

/**
 * SslcommerzConfig class.
 * 
 * This class is used to manage the configuration settings for the "SSLCommerz" gateway. It extends the `BaseConfig` 
 * class and implements the `ConfigContract` interface.
 *
 * @since 1.0.0
 */
class SslcommerzConfig extends BaseConfig implements ConfigContract {

	/**
	 * Configuration keys and their types for SSLCommerz gateway
	 *
	 * @since 1.0.0
	 */
	private const CONFIG_KEYS = [
		'environment' => 'select',
		'store_id' => 'text',
		'store_password' => 'secret_key',
	];

	/**
	 * This trait provides methods to retrieve the URLs used in the payment process for success, cancellation, and webhook 
	 * notifications.
	 */
	use PaymentUrlsTrait;

	/**
	 * Stores the environment setting for the payment gateway, such as 'sandbox' or 'live'.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private $environment;

	/**
	 * Stores the SSLCommerz Store ID.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private $store_id;

	/**
	 * Stores the SSLCommerz Store Password.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private $store_password;

	/**
	 * The name of the payment gateway.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $name = 'sslcommerz';

	/**
	 * Constructor.
	 *
	 * Initializes the SSLCommerz configuration by loading gateway settings from Tutor's
	 * payment gateway settings and populating the corresponding properties.
	 * Excludes webhook_url as it's handled separately by the PaymentUrlsTrait.
	 *
	 * @since 1.0.0
	 *
	 * @throws \RuntimeException If unable to load gateway settings.
	 */
	public function __construct() {
		parent::__construct();

		$settings = Settings::get_payment_gateway_settings('sslcommerz');

		if (!is_array($settings)) {
			throw new \RuntimeException(__('Unable to load SSLCommerz gateway settings', 'tutor-sslcommerz'));
		}

		$config_keys = array_keys(self::CONFIG_KEYS);

		foreach ($config_keys as $key) {
			if ('webhook_url' !== $key) {
				$this->$key = $this->get_field_value($settings, $key);
			}
		}
	}

	/**
	 * Retrieves the mode of the SSLCommerz payment gateway.
	 *
	 * @since 1.0.0
	 *
	 * @return string The mode of the payment gateway ('sandbox' or 'live').
	 */
	public function getMode(): string {
		return $this->environment;
	}

	/**
	 * Retrieves the Store ID for the SSLCommerz payment gateway.
	 *
	 * The Store ID is used to identify the merchant account in SSLCommerz API calls.
	 *
	 * @since 1.0.0
	 *
	 * @return string The configured Store ID.
	 */
	public function getStoreId(): string {
		return $this->store_id;
	}

	/**
	 * Retrieves the Store Password for the SSLCommerz payment gateway.
	 *
	 * The Store Password is used for authentication in SSLCommerz API calls.
	 * Note: This is NOT the merchant panel password.
	 *
	 * @since 1.0.0
	 *
	 * @return string The configured Store Password.
	 */
	public function getStorePassword(): string {
		return $this->store_password;
	}

	/**
	 * Get the SSLCommerz API domain based on the configured environment.
	 *
	 * @since 1.0.0
	 *
	 * @return string The appropriate API domain URL for sandbox or live environment.
	 */
	public function getApiDomain(): string {
		return $this->environment === 'sandbox'
			? 'https://sandbox.sslcommerz.com'
			: 'https://securepay.sslcommerz.com';
	}

	/**
	 * Checks if the SSLCommerz payment gateway is properly configured.
	 *
	 * Verifies that both the Store ID and Store Password are configured
	 * and not empty, which are required for SSLCommerz API communication.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if both store ID and password are configured, false otherwise.
	 */
	public function is_configured(): bool {
		return !empty($this->store_id) && !empty($this->store_password);
	}

	/**
	 * Creates and updates the SSLCommerz payment gateway configuration.
	 *
	 * This method extends the parent class configuration and adds SSLCommerz-specific
	 * settings including store credentials and API domain for use by the payment gateway.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function createConfig(): void {
		parent::createConfig();

		$config = [
			'store_id' => $this->getStoreId(),
			'store_password' => $this->getStorePassword(),
			'api_domain' => $this->getApiDomain(),
		];

		$this->updateConfig($config);
	}
}
