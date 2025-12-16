<?php
/**
 * Plugin Name:    	TS Pay
 * Plugin URI:      https://github.com/hasinhayder/tutor-sslcommerz
 * Description:     SSLCommerz payment gateway integration for Tutor LMS (Free & Pro). Accept online payments directly within your Tutor LMS-powered site.
 * Version:         1.0.7
 * Author:          Hasin Hayder
 * Author URI:      https://github.com/hasinhayder
 * License:         GPLv2 or later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:     tspay
 * Domain Path:     /languages
 */

defined('ABSPATH') || exit;

final class TSPay {

	private static $instance = null;
	public static function get_instance(): self {
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->init();
	}

	/**
	 * Initialize the plugin
	 */
	private function init(): void {
		$this->load_dependencies();
		$this->define_constants();
		$this->init_hooks();
	}

	/**
	 * Load plugin dependencies
	 */
	private function load_dependencies(): void {
		require_once __DIR__ . '/vendor/autoload.php';

		if (!function_exists('is_plugin_active')) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
	}

	/**
	 * Define plugin constants
	 */
	private function define_constants(): void {
		define('TSPAY_VERSION', '1.0.7');
		define('TSPAY_URL', plugin_dir_url(__FILE__));
		define('TSPAY_PATH', plugin_dir_path(__FILE__));
	}

	/**
	 * Initialize WordPress hooks
	 */
	private function init_hooks(): void {
		add_action('plugins_loaded', [$this, 'init_gateway'], 100);
	}

	/**
	 * Initialize the SSLCommerz payment gateway
	 */
	public function init_gateway(): void {
		//works with the free version of Tutor LMS 
		if (is_plugin_active('tutor/tutor.php')) {
			new TSPay\Init();
		}
	}
}

// Initialize the plugin
TSPay::get_instance();