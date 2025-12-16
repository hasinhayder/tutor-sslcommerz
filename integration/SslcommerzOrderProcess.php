<?php
/**
 * SSLCommerz Order Processing Handler
 *
 * Handles SSLCommerz payment gateway callbacks and order processing for Tutor LMS.
 * This class processes payment form submissions, validates transactions with SSLCommerz API,
 * and updates order status in the database accordingly.
 *
 * @author Hasin Hayder <https://github.com/hasinhayder>
 * @link https://github.com/hasinhayder/tspay
 */

namespace TSPay;

/**
 * SSLCommerz Order Process Class
 *
 * This class is responsible for processing SSLCommerz payment callbacks and updating
 * Tutor LMS orders based on the payment status. It validates transactions using
 * SSLCommerz's validation API and ensures secure payment processing.
 */
class SslcommerzOrderProcess {

	private const API_PROCESS_ENDPOINT = '/gwprocess/v4/api.php';
	private const API_VALIDATION_ENDPOINT = '/validator/api/validationserverAPI.php';

	private const STATUS_MAP = [
		'VALID'     => 'paid',
		'VALIDATED' => 'paid',
		'FAILED'    => 'failed',
		'CANCELLED' => 'cancelled',
		'PENDING'   => 'pending',
	];

	/**
	 * SSLCommerz client configuration array
	 *
	 * Stores store credentials and API domain information retrieved from Tutor settings.
	 *
	 * @var array
	 */
	protected $client;

	/**
	 * Processes SSLCommerz form submission callback
	 *
	 * Handles the payment callback from SSLCommerz after a payment attempt.
	 * Validates the transaction, updates the order status in the database,
	 * and ensures secure processing of payment data.
	 *
	 * This method is triggered when SSLCommerz redirects back to the success URL
	 * with payment result data in POST parameters.
	 *
	 * @return void
	 */
	public function process_sslcommerz_form_submission(): void {
        // Sanitize GET parameter
        $order_placement = isset($_GET['tutor_order_placement']) ? sanitize_text_field(wp_unslash($_GET['tutor_order_placement'])) : '';
        if ($order_placement !== 'success') {
            return;
        }

        if (empty($_POST) || !isset($_POST['tran_id'])) {
            return;
        }

        // Sanitize POST data
        $tran_id = isset($_POST['tran_id']) ? sanitize_text_field(wp_unslash($_POST['tran_id'])) : '';
        if (empty($tran_id)) {
            return;
        }

        // Get order_id from POST data (value_a) since SSLCommerz doesn't pass it as query param
        $value_a = isset($_POST['value_a']) ? sanitize_text_field(wp_unslash($_POST['value_a'])) : '';
        $order_id = absint($value_a);
        if (!$order_id) {
            return;
        }

        // Retrieve SSLCommerz settings from Tutor options
        $options = get_option('tutor_option');
        $payment_settings = json_decode($options['payment_settings'], true);

        // Find SSLCommerz payment method settings
        $sslcommerz_settings = null;
        foreach ($payment_settings['payment_methods'] as $method) {
            if ($method['name'] === 'sslcommerz') {
                $sslcommerz_settings = $method;
                break;
            }
        }

        // Skip if SSLCommerz settings not found
        if (!$sslcommerz_settings) {
            return;
        }
        try {
            // Extract store credentials and environment from settings
            foreach ($sslcommerz_settings['fields'] as $field) {
                if (!isset($field['name']) || !isset($field['value'])) {
                    continue;
                }

                switch ($field['name']) {
                    case 'store_id':
                        $this->client['store_id'] = $field['value'];
                        break;
                    case 'store_password':
                        $this->client['store_password'] = $field['value'];
                        break;
                    case 'environment':
                        $this->client['environment'] = $field['value'];
                        break;
                }
            }

            // Validate required client configuration
            if (empty($this->client['store_id']) || empty($this->client['store_password']) || empty($this->client['environment'])) {
                return;
            }

            // Determine API domain based on environment
            $this->client['api_domain'] = $this->client['environment'] === 'sandbox'
                ? 'https://sandbox.sslcommerz.com'
                : 'https://securepay.sslcommerz.com';

            // Sanitize POST data for validation
            $sanitized_post = [];
            foreach ($_POST as $key => $value) {
                $sanitized_post[$key] = is_array($value) ? array_map('sanitize_text_field', array_map('wp_unslash', $value)) : sanitize_text_field(wp_unslash($value));
            }

            // Validate transaction with SSLCommerz API
            if ($this->validateTransaction($sanitized_post)) {
                $status = isset($sanitized_post['status']) ? $sanitized_post['status'] : 'FAILED';
                $payment_status = self::STATUS_MAP[$status] ?? 'failed';

                // Update order in database with payment status
                self::update_order_in_database($order_id, $payment_status, $sanitized_post['tran_id'] ?? '');
            }
        } catch (\Exception $e) {
            // Log error for debugging (consider using WordPress logging in production)
        }



    }

    /**
     * Verifies SSLCommerz hash for transaction security
     *
     * Validates the hash provided by SSLCommerz to ensure the callback data
     * has not been tampered with. Hash verification is optional but recommended
     * for enhanced security.
     *
     * @param array $post_data POST data containing hash verification fields
     * @return bool True if hash is valid or not provided, false if invalid
     */
    private function verifyHash(array $post_data): bool {
        // Hash verification is optional but recommended
        if (!isset($post_data['verify_sign']) || !isset($post_data['verify_key'])) {
            return true; // If no hash provided, skip verification
        }

        // Sanitize verify_key
        $verify_key = sanitize_text_field(wp_unslash($post_data['verify_key']));
        $pre_define_key = explode(',', $verify_key);
        $new_data = [];

        foreach ($pre_define_key as $value) {
            $sanitized_key = sanitize_key($value);
            if (isset($post_data[$sanitized_key])) {
                $new_data[$sanitized_key] = sanitize_text_field(wp_unslash($post_data[$sanitized_key]));
            }
        }

        $new_data['store_passwd'] = md5($this->client['store_password']);
        ksort($new_data);

        $hash_string = "";
        foreach ($new_data as $key => $value) {
            $hash_string .= $key . '=' . $value . '&';
        }
        $hash_string = rtrim($hash_string, '&');

        // Sanitize verify_sign for comparison
        $verify_sign = sanitize_text_field(wp_unslash($post_data['verify_sign']));
        return md5($hash_string) === $verify_sign;
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

        // Sanitize transaction data
        $tran_id = sanitize_text_field($post_data['tran_id'] ?? '');
        $amount = isset($post_data['amount']) ? floatval($post_data['amount']) : 0.0;
        $currency = sanitize_text_field($post_data['currency'] ?? 'BDT');

        // Call SSLCommerz validation API using WordPress HTTP API
        $val_id = urlencode(sanitize_text_field($post_data['val_id'] ?? ''));
        $store_id = urlencode(sanitize_text_field($this->client['store_id']));
        $store_passwd = urlencode(sanitize_text_field($this->client['store_password']));

        $validationUrl = $this->client['api_domain'] . self::API_VALIDATION_ENDPOINT . '?val_id=' . $val_id . '&store_id=' . $store_id . '&store_passwd=' . $store_passwd . '&v=1&format=json';

        // Set SSL verification based on environment
        $isLocalhost = $this->client['environment'] === 'sandbox';
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
     * Update order status in the database
     *
     * Static method that updates the tutor_orders table with payment status and transaction details.
     * Also updates the order_status to 'completed' when payment is marked as paid.
     *
     * @param int $order_id The order ID
     * @param string $payment_status The payment status
     * @param string $transaction_id The transaction ID
     * @return void
     */
    private static function update_order_in_database(int $order_id, string $payment_status, string $transaction_id): void {
        global $wpdb;

        // Sanitize inputs
        $sanitized_payment_status = sanitize_text_field($payment_status);
        $sanitized_transaction_id = sanitize_text_field($transaction_id);

        $update_data = [
            'payment_status' => $sanitized_payment_status,
            'transaction_id' => $sanitized_transaction_id,
        ];

        // If payment is successful, mark order as completed
        if ($sanitized_payment_status === 'paid') {
            $update_data['order_status'] = 'completed';
        }

        $wpdb->update(
            $wpdb->prefix . 'tutor_orders',
            $update_data,
            ['id' => $order_id],
            array_fill(0, count($update_data), '%s'),
            ['%d']
        );
    }

}