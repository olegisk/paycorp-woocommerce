<?php // phpcs:ignore
/*
 * Plugin Name: Paycorp International/Bancstac IPG
 * Plugin URI: https://www.bancstac.com/
 * Description: Paycorp International (a wholly owned subsidiary of Bancstac) Payment Gateway for WooCommerce. The plugin provides seamless PCI DSS certified payment processing for credit card payments.
 * Author: Bancstac
 * Author URI: https://profiles.wordpress.org/paycorpsrilanka/
 * License: Apache License 2.0
 * License URI: http://www.apache.org/licenses/LICENSE-2.0
 * Version: 2.0.0
 * Text Domain: paycorp
 * Domain Path: /languages
 * WC requires at least: 5.5.1
 * WC tested up to: 5.8.0
 */

defined( 'ABSPATH' ) || exit;

/**
 *
 * @SuppressWarnings(PHPMD.CamelCaseClassName)
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 * @SuppressWarnings(PHPMD.CamelCaseParameterName)
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 * @SuppressWarnings(PHPMD.CamelCaseVariableName)
 * @SuppressWarnings(PHPMD.MissingImport)
 */
class WC_Paycorp {
	public function __construct() {
		// Actions
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );
		add_action( 'plugins_loaded', array( $this, 'init' ), 0 );
		add_action( 'woocommerce_loaded', array( $this, 'woocommerce_loaded' ), 20 );
	}

	/**
	 * Add relevant links to plugins page
	 *
	 * @param  array $links
	 *
	 * @return array
	 */
	public function plugin_action_links( $links ) {
		$plugin_links = array(
			'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=paycorp' ) . '">' .
			__( 'Settings', 'paycorp' ) . '</a>',
		);

		return array_merge( $plugin_links, $links );
	}

	/**
	 * Init localisations and files
	 */
	public function init() {
		// Localization
		load_plugin_textdomain(
			'paycorp',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/i18n'
		);
	}

	/**
	 * WooCommerce Loaded: load classes
	 * @SuppressWarnings(PHPMD.StaticAccess)
	 */
	public function woocommerce_loaded() {
		include_once( dirname( __FILE__ ) . '/includes/class-wc-gateway-paycorp.php' );
		include_once( dirname( __FILE__ ) . '/includes/class-wc-payment-token-paycorp.php' );
		include_once( dirname( __FILE__ ) . '/includes/class-wc-paycorp-subscriptions.php' );

		// Register Gateways
		WC_Paycorp::register_gateway( WC_Gateway_Paycorp::class );
	}

	/**
	 * Register payment gateway
	 *
	 * @param string $class_name
	 */
	public static function register_gateway( $class_name ) {
		global $pc_gateways;

		if ( ! $pc_gateways ) {
			$pc_gateways = array();
		}

		if ( ! isset( $pc_gateways[ $class_name ] ) ) {
			// Initialize instance
			$gateway = new $class_name;

			if ( $gateway ) {
				$pc_gateways[] = $class_name;

				// Register gateway instance
				add_filter(
					'woocommerce_payment_gateways',
					function ( $methods ) use ( $gateway ) {
						$methods[] = $gateway;

						return $methods;
					}
				);
			}
		}
	}
}

new WC_Paycorp();
