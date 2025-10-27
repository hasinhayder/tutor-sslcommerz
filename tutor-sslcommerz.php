<?php
/**
 * Plugin Name:     Tutor SSLCommerz
 * Plugin URI:      https://github.com/hasinhayder/tutor-sslcommerz
 * Description:     SSLCommerz payment gateway integration for Tutor LMS (Free & Pro). Accept online payments directly within your Tutor LMS-powered site.
 * Version:         1.0.6
 * Author:          Hasin Hayder
 * Author URI:      https://github.com/hasinhayder
 * License:         GPLv2 or later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:     tutor-sslcommerz
 * Domain Path:    /languages
 */

defined('ABSPATH') || exit;

/**
 * Main Plugin Class
 *
 * Handles plugin initialization and core functionality.
 *
 * @since 1.0.0
 */
final class Tutor_SSLCommerz_Plugin {

	/**
	 * Single instance of the plugin
	 *
	 * @since 1.0.0
	 * @var Tutor_SSLCommerz_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance
	 *
	 * @since 1.0.0
	 * @return Tutor_SSLCommerz_Plugin
	 */
	public static function get_instance(): self {
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor - Initialize the plugin
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		$this->init();
	}

	/**
	 * Initialize the plugin
	 *
	 * @since 1.0.0
	 */
	private function init(): void {
		$this->load_dependencies();
		$this->define_constants();
		$this->init_hooks();
	}

	/**
	 * Load plugin dependencies
	 *
	 * @since 1.0.0
	 */
	private function load_dependencies(): void {
		require_once __DIR__ . '/vendor/autoload.php';

		if (!function_exists('is_plugin_active')) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
	}

	/**
	 * Define plugin constants
	 *
	 * @since 1.0.0
	 */
	private function define_constants(): void {
		define('TUTOR_SSLCOMMERZ_VERSION', '1.0.6');
		define('TUTOR_SSLCOMMERZ_URL', plugin_dir_url(__FILE__));
		define('TUTOR_SSLCOMMERZ_PATH', plugin_dir_path(__FILE__));
	}

	/**
	 * Initialize WordPress hooks
	 *
	 * @since 1.0.0
	 */
	private function init_hooks(): void {
		add_action('plugins_loaded', [$this, 'load_textdomain'], 1);
		add_action('plugins_loaded', [$this, 'init_gateway'], 100);
	}

	/**
	 * Load plugin text domain for internationalization
	 *
	 * @since 1.0.0
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'tutor-sslcommerz',
			false,
			TUTOR_SSLCOMMERZ_PATH . 'languages/'
		);
	}

	/**
	 * Initialize the SSLCommerz payment gateway
	 *
	 * @since 1.0.0
	 */
	public function init_gateway(): void {
		//works with the free version of Tutor LMS 
		if (is_plugin_active('tutor/tutor.php')) {
			new TutorSslcommerz\Init();
		}
	}
}

// Initialize the plugin
Tutor_SSLCommerz_Plugin::get_instance();