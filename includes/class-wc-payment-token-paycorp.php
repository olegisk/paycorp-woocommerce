<?php

defined( 'ABSPATH' ) || exit;

/**
 * @SuppressWarnings(PHPMD.CamelCaseClassName)
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 * @SuppressWarnings(PHPMD.CamelCaseParameterName)
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 * @SuppressWarnings(PHPMD.CamelCaseVariableName)
 * @SuppressWarnings(PHPMD.MissingImport)
 */
class WC_Payment_Token_Paycorp extends WC_Payment_Token_CC {

	/**
	 * Token Type String.
	 *
	 * @var string
	 */
	protected $type = 'Paycorp';

	/**
	 * Stores Credit Card payment token data.
	 *
	 * @var array
	 */
	protected $extra_data = array(
		'last4'        => '',
		'expiry_year'  => '',
		'expiry_month' => '',
		'card_type'    => '',
		'masked_card'  => '',
	);

	/**
	 * Validate credit card payment tokens.
	 *
	 * @return boolean True if the passed data is valid
	 */
	public function validate() {
		if ( false === parent::validate() ) {
			return false;
		}

		if ( ! $this->get_masked_card( 'edit' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Hook prefix
	 * @return string
	 */
	protected function get_hook_prefix() {
		return 'woocommerce_payment_token_paycorp_get_';
	}

	/**
	 * Returns the raw payment token.
	 *
	 * @since  2.6.0
	 * @param  string $context Context in which to call this.
	 * @return string Raw token
	 */
	public function get_token( $context = 'view' ) {
		$token = $this->get_prop( 'token', $context );

		return self::safe_decrypt( $token, self::get_key() );
	}

	/**
	 * Set the raw payment token.
	 *
	 * @since 2.6.0
	 * @param string $token Payment token.
	 */
	public function set_token( $token ) {
		$this->set_prop( 'token', self::safe_encrypt( $token, self::get_key() ) );
	}

	/**
	 * Returns Masked Card
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string Masked Card
	 */
	public function get_masked_card( $context = 'view' ) {
		return $this->get_prop( 'masked_card', $context );
	}

	/**
	 * Set the last four digits.
	 *
	 * @param string $masked_card Masked Card
	 */
	public function set_masked_card( $masked_card ) {
		$this->set_prop( 'masked_card', $masked_card );
	}

	/**
	 * Returns if the token is marked as default.
	 *
	 * @return boolean True if the token is default
	 * @SuppressWarnings(PHPMD.Superglobals)
	 * @SuppressWarnings(PHPMD.StaticAccess)
	 */
	public function is_default() {
		// Mark Method as Checked on "Payment Change" page
		if ( WC_Gateway_Paycorp::wcs_is_payment_change() &&
			 isset( $_GET['change_payment_method'] ) &&
			 abs( $_GET['change_payment_method'] ) > 0 ) {
			$subscription = wcs_get_subscription( wc_clean( $_GET['change_payment_method'] ) );
			$tokens       = $subscription->get_payment_tokens();
			foreach ( $tokens as $token_id ) {
				if ( $this->get_id() === (int) $token_id ) {
					return true;
				}
			}

			return false;
		}

		return parent::is_default();
	}

	/**
	 * Controls the output for credit cards on the my account page.
	 *
	 * @param array $item Individual list item from woocommerce_saved_payment_methods_list.
	 * @param WC_Payment_Token $payment_token The payment token associated with this method entry.
	 *
	 * @return array                           Filtered item.
	 */
	public static function wc_get_account_saved_payment_methods_list_item( $item, $payment_token ) {
		if ( 'paycorp' !== strtolower( $payment_token->get_type() ) ) {
			return $item;
		}

		$card_type               = $payment_token->get_card_type();
		$item['method']['id']    = $payment_token->get_id();
		$item['method']['last4'] = $payment_token->get_last4();
		$item['method']['brand'] = ( ! empty( $card_type ) ? ucfirst( $card_type ) : esc_html__( 'Credit card',
			'woocommerce' ) );
		$item['expires']         = $payment_token->get_expiry_month() . '/' . substr( $payment_token->get_expiry_year(),
				- 2 );

		return $item;
	}

	/**
	 * @return string
	 * @throws Exception
	 */
	private static function get_key() {
		$key = get_option( 'paycorp_encryption_key' );
		if ( $key ) {
			return base64_decode( $key );
		}

		$key = sodium_crypto_secretbox_keygen();
		update_option( 'paycorp_encryption_key', base64_encode( $key ) );

		return $key;
	}

	/**
	 * Encrypt a message
	 *
	 * @param string $message - message to encrypt
	 * @param string $key - encryption key
	 *
	 * @return string
	 */
	private static function safe_encrypt( $message, $key ) {
		$nonce = random_bytes(
			SODIUM_CRYPTO_SECRETBOX_NONCEBYTES
		);

		$cipher = base64_encode(
			$nonce .
			sodium_crypto_secretbox(
				$message,
				$nonce,
				$key
			)
		);

		if ( extension_loaded('sodium') ) {
			sodium_memzero( $message );
			sodium_memzero( $key );
		}

		return $cipher;
	}

	/**
	 * Decrypt a message
	 *
	 * @param string $encrypted - message encrypted with safeEncrypt()
	 * @param string $key - encryption key
	 *
	 * @return string
	 */
	private static function safe_decrypt( $encrypted, $key ) {
		$decoded = base64_decode( $encrypted );
		if ( $decoded === false ) {
			throw new Exception( 'Unable to decode the data.' );
		}

		if ( mb_strlen( $decoded,
				'8bit' ) < ( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES ) ) {
			throw new Exception( 'Data was truncated' );
		}

		$nonce      = mb_substr( $decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit' );
		$ciphertext = mb_substr( $decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit' );

		$plain = sodium_crypto_secretbox_open(
			$ciphertext,
			$nonce,
			$key
		);

		if ( $plain === false ) {
			throw new Exception( 'Data was tampered with in transit' );
		}

		if ( extension_loaded('sodium') ) {
			sodium_memzero( $message );
			sodium_memzero( $key );
		}

		return $plain;
	}

}

add_filter( 'woocommerce_payment_methods_list_item',
	'WC_Payment_Token_Paycorp::wc_get_account_saved_payment_methods_list_item', 10, 2 );

