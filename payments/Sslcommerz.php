<?php
/**
 * SSLCommerz Payment Gateway Implementation
 *
 * Concrete implementation of the SSLCommerz payment gateway for Tutor LMS.
 * This class handles the complete payment flow including payment creation,
 * transaction validation, and webhook processing for SSLCommerz integration.
 *
 * Features:
 * - Secure payment processing with SSLCommerz API
 * - Transaction validation and hash verification
 * - IPN (Instant Payment Notification) handling
 * - Support for sandbox and live environments
 * - Comprehensive error handling and logging
 *
 * @author Hasin Hayder <https://github.com/hasinhayder>
 * @link https://github.com/hasinhayder/tspay
 */

namespace Payments\Sslcommerz;

use Throwable;
use ErrorException;
use Ollyo\PaymentHub\Core\Support\Arr;
use Ollyo\PaymentHub\Core\Support\System;
use GuzzleHttp\Exception\RequestException;
use Ollyo\PaymentHub\Core\Payment\BasePayment;

/**
 * SSLCommerz Payment Gateway Class
 *
 * This class extends BasePayment to provide SSLCommerz payment gateway functionality.
 * It implements the complete payment lifecycle from initiation to completion,
 * including validation and webhook processing.
 */
class Sslcommerz extends BasePayment {
	/**
	 * SSLCommerz API endpoints and configuration constants
	 */
	private const API_PROCESS_ENDPOINT = '/gwprocess/v4/api.php';
	private const API_VALIDATION_ENDPOINT = '/validator/api/validationserverAPI.php';
	private const DEFAULT_CURRENCY = 'BDT';
	private const DEFAULT_COUNTRY = 'Bangladesh';
	private const DEFAULT_PHONE = '01700000000';
	private const DEFAULT_POSTCODE = '0000';
	private const TRANSACTION_PREFIX = 'TUTOR-';
	private const PRODUCT_CATEGORY = 'education';
	private const PRODUCT_PROFILE = 'non-physical-goods';
	private const SHIPPING_METHOD = 'NO';

	/**
	 * Payment status mapping constants
	 */
	private const STATUS_MAP = [
		'VALID' => 'paid',
		'VALIDATED' => 'paid',
		'FAILED' => 'failed',
		'CANCELLED' => 'cancelled',
		'PENDING' => 'pending',
	];

	/**
	 * Stores the SSLCommerz API client configuration
	 *
	 * @var array
	 */
	protected $client;

	/**
	 * Checks if all required configuration keys are present and not empty.
	 *
	 * Validates that the essential SSLCommerz configuration parameters
	 * (store_id, store_password, mode) are properly configured before
	 * allowing payment processing.
	 *
	 * @return bool Returns true if all required configuration keys are present and not empty, otherwise false.
	 */
	public function check(): bool {
		$configKeys = Arr::make(['store_id', 'store_password', 'mode']);

		$isConfigOk = $configKeys->every(function ($key) {
			return $this->config->has($key) && !empty($this->config->get($key));
		});

		return $isConfigOk;
	}

	/**
	 * Initializes the necessary configurations for the SSLCommerz payment gateway.
	 *
	 * Sets up the client configuration array with store credentials and API domain
	 * required for SSLCommerz API communication. This method must be called before
	 * any payment processing operations.
	 *
	 * @throws Throwable If configuration retrieval fails or invalid data is provided.
	 */
	public function setup(): void {
		try {
			$this->client = [
				'store_id' => $this->config->get('store_id'),
				'store_password' => $this->config->get('store_password'),
				'api_domain' => $this->config->get('api_domain'),
			];
		} catch (Throwable $error) {
			throw $error;
		}
	}

	/**
	 * Sets the payment data according to SSLCommerz requirements.
	 *
	 * Processes and structures the payment data from Tutor LMS into the format
	 * expected by the SSLCommerz API. This includes generating transaction IDs,
	 * formatting amounts, and organizing customer and product information.
	 *
	 * @param  object $data The payment data object from Tutor LMS.
	 * @throws Throwable If the parent `setData` method throws an error or data processing fails.
	 */
	public function setData($data): void {
		try {
			// Structure the payment data according to SSLCommerz requirements
			$structuredData = $this->prepareData($data);
			parent::setData($structuredData);
		} catch (Throwable $error) {
			throw $error;
		}
	}

	/**
	 * Prepares the payment data according to SSLCommerz API requirements.
	 *
	 * @param object $data Payment data from Tutor
	 * @return array Formatted data for SSLCommerz
	 */
	private function prepareData(object $data): array {
		// Validate required data
		if (!isset($data->order_id) || empty($data->order_id)) {
			throw new \InvalidArgumentException(__('Order ID is required for payment processing', 'tspay'));
		}

		if (!isset($data->currency) || !isset($data->currency->code)) {
			throw new \InvalidArgumentException(__('Currency information is required for payment processing', 'tspay'));
		}

		if (!isset($data->customer) || !isset($data->customer->email)) {
			throw new \InvalidArgumentException(__('Customer email is required for payment processing', 'tspay'));
		}

		// Generate unique transaction ID
		$tran_id = self::TRANSACTION_PREFIX . $data->order_id . '-' . time();

		// Get total price - Tutor uses 'total_price' property
		$total_price = isset($data->total_price) && !empty($data->total_price) ? (float) $data->total_price : 0;

		// Validate amount
		if ($total_price <= 0) {
			throw new \InvalidArgumentException(__('Payment amount must be greater than zero', 'tspay'));
		}

		// Format amounts for SSLCommerz
		$total_amount = number_format($total_price, 2, '.', '');
		$product_amount = number_format($total_price, 2, '.', '');

		// Prepare SSLCommerz required fields
		$sslcommerzData = [
			// Required transaction information
			'total_amount' => $total_amount,
			'currency' => $data->currency->code,
			'tran_id' => $tran_id,
			'product_category' => self::PRODUCT_CATEGORY,
			'product_name' => $data->order_description ?? __('Course Purchase', 'tspay'),
			'product_profile' => self::PRODUCT_PROFILE,

			// URLs
			'success_url' => $this->config->get('success_url'),
			'fail_url' => $this->config->get('cancel_url'),
			'cancel_url' => $this->config->get('cancel_url'),
			'ipn_url' => $this->config->get('webhook_url'),

			// Customer information
			'cus_name' => $data->customer->name ?? __('Customer', 'tspay'),
			'cus_email' => $data->customer->email,
			'cus_add1' => $data->billing_address->address1 ?? __('N/A', 'tspay'),
			'cus_add2' => $data->billing_address->address2 ?? '',
			'cus_city' => $data->billing_address->city ?? __('N/A', 'tspay'),
			'cus_state' => $data->billing_address->state ?? '',
			'cus_postcode' => $data->billing_address->postal_code ?? self::DEFAULT_POSTCODE,
			'cus_country' => $data->billing_address->country->name ?? ($data->currency->code === self::DEFAULT_CURRENCY ? self::DEFAULT_COUNTRY : __('N/A', 'tspay')),
			'cus_phone' => $data->customer->phone_number ?? self::DEFAULT_PHONE,

			// Shipping information (same as billing for digital products)
			'shipping_method' => self::SHIPPING_METHOD,
			'num_of_item' => 1,
			'ship_name' => $data->customer->name ?? __('Customer', 'tspay'),
			'ship_add1' => $data->billing_address->address1 ?? __('N/A', 'tspay'),
			'ship_add2' => $data->billing_address->address2 ?? '',
			'ship_city' => $data->billing_address->city ?? __('N/A', 'tspay'),
			'ship_state' => $data->billing_address->state ?? '',
			'ship_postcode' => $data->billing_address->postal_code ?? self::DEFAULT_POSTCODE,
			'ship_country' => $data->billing_address->country->name ?? ($data->currency->code === self::DEFAULT_CURRENCY ? self::DEFAULT_COUNTRY : __('N/A', 'tspay')),

			// Additional information
			'value_a' => $data->order_id, // Store our order ID for reference
			'value_b' => $data->customer->email,
			'value_c' => $data->store_name ?? __('Tutor LMS', 'tspay'),
			'product_amount' => $product_amount,
		];

		return $sslcommerzData;
	}

	/**
	 * Creates the payment process by sending data to SSLCommerz gateway.
	 *
	 * @throws ErrorException
	 */
	public function createPayment(): void {
		try {
			$paymentData = $this->getData();

			// Add store credentials
			$paymentData['store_id'] = $this->client['store_id'];
			$paymentData['store_passwd'] = $this->client['store_password'];

			// Make API call to SSLCommerz
			$apiUrl = $this->client['api_domain'] . self::API_PROCESS_ENDPOINT;
			$response = $this->callSslcommerzApi($apiUrl, $paymentData);

			if ($response && isset($response['status']) && $response['status'] === 'SUCCESS') {
				if (isset($response['GatewayPageURL']) && !empty($response['GatewayPageURL'])) {
					// Redirect to SSLCommerz payment page
					header("Location: " . $response['GatewayPageURL']);
					exit;
				} else {
					throw new ErrorException(__('Gateway URL not found in response', 'tspay'));
				}
			} else {
				$errorMessage = $response['failedreason'] ?? __('Unknown error occurred', 'tspay');
				throw new ErrorException(__('SSLCommerz Payment Failed: ', 'tspay') . $errorMessage);
			}

		} catch (RequestException $error) {
			throw new ErrorException($error->getMessage());
		}
	}

	/**
	 * Makes a request to SSLCommerz API using WordPress HTTP API
	 *
	 * @param string $url API endpoint
	 * @param array $data Post data
	 * @return array Response data
	 */
	private function callSslcommerzApi(string $url, array $data): array {
		// Set SSL verification based on environment
		$isLocalhost = $this->config->get('mode') === 'sandbox';
		$ssl_verify = !$isLocalhost; // Verify SSL in production, skip in sandbox

		// Prepare arguments for wp_remote_post
		$args = [
			'method' => 'POST',
			'timeout' => 60,
			'redirection' => 5,
			'httpversion' => '1.1',
			'blocking' => true,
			'headers' => [
				'Content-Type' => 'application/x-www-form-urlencoded',
			],
			'body' => $data,
			'sslverify' => $ssl_verify,
		];

		// Make the request
		$response = wp_remote_post($url, $args);

		// Check for errors
		if (is_wp_error($response)) {
			return ['status' => 'FAILED', 'failedreason' => __('Failed to connect with SSLCommerz API: ', 'tspay') . $response->get_error_message()];
		}

		// Get response code and body
		$http_code = wp_remote_retrieve_response_code($response);
		$body = wp_remote_retrieve_body($response);

		if ($http_code == 200 && !empty($body)) {
			$decoded = json_decode($body, true);
			if (json_last_error() === JSON_ERROR_NONE) {
				return $decoded;
			} else {
				return ['status' => 'FAILED', 'failedreason' => __('Invalid JSON response from SSLCommerz API', 'tspay')];
			}
		} else {
			return ['status' => 'FAILED', 'failedreason' => __('Failed to connect with SSLCommerz API (HTTP ', 'tspay') . $http_code . ')'];
		}
	}

	/**
	 * Verifies and processes the order data received from SSLCommerz.
	 *
	 * @param  object $payload Webhook payload
	 * @return object Order data
	 * @throws Throwable
	 */
	public function verifyAndCreateOrderData(object $payload): object {
		$returnData = System::defaultOrderData();

		try {
			// Get POST data from SSLCommerz IPN/Success callback
			$post_data = $payload->post;

			// Validate that we have POST data
			if (empty($post_data) || !is_array($post_data)) {
				$returnData->payment_status = 'failed';
				$returnData->payment_error_reason = __('No transaction data received. IPN endpoint should only receive POST requests from SSLCommerz.', 'tspay');
				return $returnData;
			}

			// Sanitize POST data
			$sanitized_post = [];
			foreach ($post_data as $key => $value) {
				$sanitized_post[$key] = is_array($value) ? array_map('sanitize_text_field', array_map('wp_unslash', $value)) : sanitize_text_field(wp_unslash($value));
			}

			if (empty($sanitized_post['tran_id']) || empty($sanitized_post['status'])) {
				$returnData->payment_status = 'failed';
				$returnData->payment_error_reason = __('Invalid transaction data: Missing transaction ID or status.', 'tspay');
				return $returnData;
			}

			$tran_id = $sanitized_post['tran_id'];
			$amount = $sanitized_post['amount'] ?? 0;
			$currency = $sanitized_post['currency'] ?? 'BDT';
			$status = $sanitized_post['status'];

			// Validate the transaction with SSLCommerz
			$validated = $this->validateTransaction($sanitized_post);

			if ($validated) {
				// Extract order ID from value_a or tran_id
				$order_id = $sanitized_post['value_a'] ?? '';

				// Map SSLCommerz status to Tutor status
				$payment_status = $this->mapPaymentStatus($status);

				$returnData->id = $order_id;
				$returnData->payment_status = $payment_status;
				$returnData->transaction_id = $sanitized_post['bank_tran_id'] ?? $tran_id;
				$returnData->payment_payload = json_encode($sanitized_post);
				$returnData->payment_error_reason = $status !== 'VALID' && $status !== 'VALIDATED' ? ($sanitized_post['error'] ?? __('Payment failed', 'tspay')) : '';

				// Calculate fees and earnings (SSLCommerz deducts their fee)
				$store_amount = floatval($sanitized_post['store_amount'] ?? $amount);
				$gateway_fee = floatval($amount) - $store_amount;

				$returnData->fees = number_format($gateway_fee, 2, '.', '');
				$returnData->earnings = number_format($store_amount, 2, '.', '');
				$returnData->tax_amount = 0; // SSLCommerz doesn't provide tax information

			} else {
				// Validation failed
				$returnData->payment_status = 'failed';
				$returnData->payment_error_reason = __('Transaction validation with SSLCommerz API failed.', 'tspay');
			}

			return $returnData;

		} catch (Throwable $error) {
			// Log the error for debugging if WP_DEBUG is enabled
			if (defined('WP_DEBUG') && WP_DEBUG) {
				error_log('SSLCommerz IPN Error: ' . $error->getMessage());
			}

			// Return failed status instead of throwing
			$returnData->payment_status = 'failed';
			$returnData->payment_error_reason = __('Error processing payment: ', 'tspay') . $error->getMessage();
			return $returnData;
		}
	}

	/**
	 * Validates transaction with SSLCommerz validation API
	 *
	 * @param array $post_data POST data from callback
	 * @param string $tran_id Transaction ID
	 * @param float $amount Transaction amount
	 * @param string $currency Currency code
	 * @return bool
	 */
	private function validateTransaction(array $post_data): bool {
		// First verify hash if present
		if (!$this->verifyHash($post_data)) {
			return false;
		}

		$tran_id = $post_data['tran_id'];
		$amount = $post_data['amount'] ?? 0;
		$currency = $post_data['currency'] ?? 'BDT';

		// Call SSLCommerz validation API using WordPress HTTP API
		$val_id = urlencode($post_data['val_id'] ?? '');
		$store_id = urlencode($this->client['store_id']);
		$store_passwd = urlencode($this->client['store_password']);

		$validationUrl = $this->client['api_domain'] . self::API_VALIDATION_ENDPOINT . '?val_id=' . $val_id . '&store_id=' . $store_id . '&store_passwd=' . $store_passwd . '&v=1&format=json';

		// Set SSL verification based on environment
		$isLocalhost = $this->config->get('mode') === 'sandbox';
		$ssl_verify = !$isLocalhost;

		// Make GET request using wp_remote_get
		$args = [
			'timeout' => 30,
			'sslverify' => $ssl_verify,
		];

		$response = wp_remote_get($validationUrl, $args);

		// Check for errors
		if (is_wp_error($response)) {
			return false;
		}

		// Get response code and body
		$code = wp_remote_retrieve_response_code($response);
		$body = wp_remote_retrieve_body($response);

		if ($code == 200 && !empty($body)) {
			$result = json_decode($body);

			if (json_last_error() === JSON_ERROR_NONE && isset($result->status) && ($result->status === 'VALID' || $result->status === 'VALIDATED')) {
				// Verify transaction details match
				if ($currency === 'BDT') {
					return trim($tran_id) === trim($result->tran_id) && abs($amount - $result->amount) < 1;
				} else {
					return trim($tran_id) === trim($result->tran_id) && abs($amount - $result->currency_amount) < 1;
				}
			}
		}

		return false;
	}

	/**
	 * Verifies the hash signature from SSLCommerz
	 *
	 * @param array $post_data POST data
	 * @return bool
	 */
	private function verifyHash(array $post_data): bool {
		// Hash verification is optional but recommended
		if (!isset($post_data['verify_sign']) || !isset($post_data['verify_key'])) {
			return true; // If no hash provided, skip verification
		}

		$pre_define_key = explode(',', $post_data['verify_key']);
		$new_data = [];

		foreach ($pre_define_key as $value) {
			if (isset($post_data[$value])) {
				$new_data[$value] = $post_data[$value];
			}
		}

		$new_data['store_passwd'] = md5($this->client['store_password']);
		ksort($new_data);

		$hash_string = "";
		foreach ($new_data as $key => $value) {
			$hash_string .= $key . '=' . $value . '&';
		}
		$hash_string = rtrim($hash_string, '&');

		return md5($hash_string) === $post_data['verify_sign'];
	}

	/**
	 * Maps SSLCommerz payment status to Tutor payment status
	 *
	 * @param string $sslcommerzStatus
	 * @return string
	 */
	private function mapPaymentStatus(string $sslcommerzStatus): string {
		return self::STATUS_MAP[$sslcommerzStatus] ?? 'failed';
	}
}