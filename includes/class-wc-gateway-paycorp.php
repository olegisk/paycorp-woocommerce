<?php

defined( 'ABSPATH' ) || exit;

// phpcs:disable
require_once __DIR__ . '/../vendor/paycorp-client-php/au_com_gateway_client/GatewayClient.php';
require_once __DIR__ . '/../vendor/paycorp-client-php/au_com_gateway_client_config/ClientConfig.php';
require_once __DIR__ . '/../vendor/paycorp-client-php/au_com_gateway_client_component/RequestHeader.php';
require_once __DIR__ . '/../vendor/paycorp-client-php/au_com_gateway_client_component/CreditCard.php';
require_once __DIR__ . '/../vendor/paycorp-client-php/au_com_gateway_client_component/TransactionAmount.php';
require_once __DIR__ . '/../vendor/paycorp-client-php/au_com_gateway_client_component/Redirect.php';
require_once __DIR__ . '/../vendor/paycorp-client-php/au_com_gateway_client_facade/BaseFacade.php';
require_once __DIR__ . '/../vendor/paycorp-client-php/au_com_gateway_client_facade/Payment.php';
require_once __DIR__ . '/../vendor/paycorp-client-php/au_com_gateway_client_payment/PaymentInitRequest.php';
require_once __DIR__ . '/../vendor/paycorp-client-php/au_com_gateway_client_payment/PaymentInitResponse.php';
require_once __DIR__ . '/../vendor/paycorp-client-php/au_com_gateway_client_payment/PaymentCompleteRequest.php';
require_once __DIR__ . '/../vendor/paycorp-client-php/au_com_gateway_client_payment/PaymentCompleteResponse.php';
require_once __DIR__ . '/../vendor/paycorp-client-php/au_com_gateway_client_root/PaycorpRequest.php';
require_once __DIR__ . '/../vendor/paycorp-client-php/au_com_gateway_client_utils/IJsonHelper.php';
require_once __DIR__ . '/../vendor/paycorp-client-php/au_com_gateway_client_helpers/PaymentInitJsonHelper.php';
require_once __DIR__ . '/../vendor/paycorp-client-php/au_com_gateway_client_helpers/PaymentCompleteJsonHelper.php';
require_once __DIR__ . '/../vendor/paycorp-client-php/au_com_gateway_client_utils/HmacUtils.php';
require_once __DIR__ . '/../vendor/paycorp-client-php/au_com_gateway_client_utils/CommonUtils.php';
require_once __DIR__ . '/../vendor/paycorp-client-php/au_com_gateway_client_utils/RestClient.php';
require_once __DIR__ . '/../vendor/paycorp-client-php/au_com_gateway_client_enums/TransactionType.php';
require_once __DIR__ . '/../vendor/paycorp-client-php/au_com_gateway_client_enums/Version.php';
require_once __DIR__ . '/../vendor/paycorp-client-php/au_com_gateway_client_enums/Operation.php';
require_once __DIR__ . '/../vendor/paycorp-client-php/au_com_gateway_client_facade/Vault.php';
require_once __DIR__ . '/../vendor/paycorp-client-php/au_com_gateway_client_facade/Report.php';
require_once __DIR__ . '/../vendor/paycorp-client-php/au_com_gateway_client_facade/AmexWallet.php';
require_once __DIR__ . '/../vendor/paycorp-client-php/au_com_gateway_client_payment/PaymentRealTimeRequest.php';
require_once __DIR__ . '/../vendor/paycorp-client-php/au_com_gateway_client_payment/PaymentRealTimeResponse.php';
require_once __DIR__ . '/../vendor/paycorp-client-php/au_com_gateway_client_helpers/PaymentRealTimeJsonHelper.php';
// phpcs:enable

/**
 * @SuppressWarnings(PHPMD.CamelCaseClassName)
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 * @SuppressWarnings(PHPMD.CamelCaseParameterName)
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 * @SuppressWarnings(PHPMD.CamelCaseVariableName)
 * @SuppressWarnings(PHPMD.MissingImport)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class WC_Gateway_Paycorp extends WC_Payment_Gateway {
	/**
	 * @var string
	 */
	public $pg_domain = 'https://sampath.paycorp.lk/rest/service/proxy';

	/**
	 * @var string
	 */
	public $client_id;

	/**
	 * @var string
	 */
	public $token_client_id;

	/**
	 * @var string
	 */
	public $customer_id;

	/**
	 * @var string
	 */
	public $hmac_secret;

	/**
	 * @var string
	 */
	public $auth_token;

	/**
	 * @var string
	 */
	public $transaction_type = 'PURCHASE';

	/**
	 * Init
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 */
	public function __construct() {
		$this->id           = 'paycorp';
		$this->has_fields   = true;
		$this->method_title = __( 'Paycorp International/Bancstac IPG', 'paycorp' );
		$this->icon         = apply_filters(
			'wc_gateway_paycorp_icon',
			plugins_url( '/../assets/images/support.jpg', __FILE__ )
		);
		$this->supports     = array(
			'products',
			//'refunds',
			'tokenization',
			'subscriptions',
			'subscription_cancellation',
			'subscription_suspension',
			'subscription_reactivation',
			'subscription_amount_changes',
			'subscription_date_changes',
			'subscription_payment_method_change',
			'subscription_payment_method_change_customer',
			'subscription_payment_method_change_admin',
			//'multiple_subscriptions',
		);

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Define user set variables
		$this->enabled          = isset( $this->settings['enabled'] ) ?
			$this->settings['enabled'] : 'no';
		$this->title            = isset( $this->settings['title'] ) ?
			$this->settings['title'] : '';
		$this->description      = isset( $this->settings['description'] ) ?
			$this->settings['description'] : '';
		$this->pg_domain        = isset( $this->settings['pg_domain'] ) ?
			$this->settings['pg_domain'] : $this->pg_domain;
		$this->client_id        = isset( $this->settings['client_id'] ) ?
			$this->settings['client_id'] : $this->client_id;
		$this->token_client_id  = isset( $this->settings['token_client_id'] ) ?
			$this->settings['token_client_id'] : $this->token_client_id;
		$this->customer_id      = isset( $this->settings['customer_id'] ) ?
			$this->settings['customer_id'] : $this->customer_id;
		$this->hmac_secret      = isset( $this->settings['hmac_secret'] ) ?
			$this->settings['hmac_secret'] : $this->hmac_secret;
		$this->auth_token       = isset( $this->settings['auth_token'] ) ?
			$this->settings['auth_token'] : $this->auth_token;
		$this->transaction_type = isset( $this->settings['transaction_type'] ) ?
			$this->settings['transaction_type'] : $this->transaction_type;

		// Add scripts and styles for admin
		add_action( 'admin_enqueue_scripts', __CLASS__ . '::admin_enqueue_scripts' );

		// Actions and filters
		add_action(
			'woocommerce_update_options_payment_gateways_' . $this->id,
			array( $this, 'process_admin_options' )
		);
		add_action( 'woocommerce_before_thankyou', array( $this, 'thankyou_page' ) );
		add_filter( 'woocommerce_order_has_status', array( $this, 'order_has_status' ), 10, 3 );

		// Action for "Add Payment Method"
		add_action( 'wp_ajax_paycorp_card_store', array( $this, 'paycorp_card_store' ) );
		add_action( 'wp_ajax_nopriv_paycorp_card_store', array( $this, 'paycorp_card_store' ) );

		// Add meta boxes
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
	}

	/**
	 * Initialise Settings Form Fields
	 * @return string|void
	 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'          => array(
				'title'   => __( 'Enable/Disable', 'paycorp' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable plugin', 'paycorp' ),
				'default' => 'no',
			),
			'title'            => array(
				'title'       => __( 'Title', 'paycorp' ),
				'type'        => 'text',
				'description' => __(
					'This controls the title which the user sees during checkout.',
					'paycorp'
				),
				'default'     => __( 'Paycorp Payment Gateway', 'paycorp' ),
			),
			'description'      => array(
				'title'       => __( 'Description', 'paycorp' ),
				'type'        => 'text',
				'description' => __(
					'This controls the description which the user sees during checkout.',
					'paycorp'
				),
				'default'     => __( 'This is a secure purchase through Paycorp Payment Gateway.', 'paycorp' ),
			),
			'pg_domain'        => array(
				'title'             => __( 'PG Domain', 'paycorp' ),
				'type'              => 'text',
				'description'       => __( 'IPG data are submitting to this URL', 'paycorp' ),
				'default'           => $this->pg_domain,
				'custom_attributes' => array(
					'required' => 'required',
				),
				'sanitize_callback' => function ( $value ) {
					if ( empty( $value ) ) {
						throw new Exception( __( '"PG Domain" field can\'t be empty.', 'paycorp' ) );
					}

					return $value;
				},
			),
			'client_id'        => array(
				'title'             => __( 'PG Client Id', 'paycorp' ),
				'type'              => 'text',
				'description'       => __( 'Unique ID for the merchant acc, given by bank.', 'paycorp' ),
				'default'           => $this->client_id,
				'custom_attributes' => array(
					'required' => 'required',
				),
				'sanitize_callback' => function ( $value ) {
					if ( empty( $value ) ) {
						throw new Exception( __( '"PG Client Id" field can\'t be empty.', 'paycorp' ) );
					}

					return $value;
				},
			),
			'token_client_id'  => array(
				'title'             => __( 'PG Client Id (tokenization)', 'paycorp' ),
				'type'              => 'text',
				'description'       => __( 'Unique ID for the merchant acc, given by bank.', 'paycorp' ),
				'default'           => $this->token_client_id,
			),
			'customer_id'      => array(
				'title'             => __( 'PG Customer Id', 'paycorp' ),
				'type'              => 'text',
				'description'       => __( 'Unique ID for the merchant acc, given by bank.', 'paycorp' ),
				'default'           => $this->customer_id,
				'custom_attributes' => array(
					'required' => 'required',
				),
				'sanitize_callback' => function ( $value ) {
					if ( empty( $value ) ) {
						throw new Exception( __( '"PG Customer Id" field can\'t be empty.', 'paycorp' ) );
					}

					return $value;
				},
			),
			'hmac_secret'      => array(
				'title'             => __( 'HMAC Secret', 'paycorp' ),
				'type'              => 'text',
				'description'       => __( 'Collection of mix integers and strings , given by bank.', 'paycorp' ),
				'default'           => $this->hmac_secret,
				'custom_attributes' => array(
					'required' => 'required',
				),
				'sanitize_callback' => function ( $value ) {
					if ( empty( $value ) ) {
						throw new Exception( __( '"HMAC Secret" field can\'t be empty.', 'paycorp' ) );
					}

					return $value;
				},
			),
			'auth_token'       => array(
				'title'             => __( 'Auth Token', 'paycorp' ),
				'type'              => 'text',
				'description'       => __( 'Collection of mix integers and strings , given by bank.', 'paycorp' ),
				'default'           => $this->auth_token,
				'custom_attributes' => array(
					'required' => 'required',
				),
				'sanitize_callback' => function ( $value ) {
					if ( empty( $value ) ) {
						throw new Exception( __( '"Auth Token" field can\'t be empty.', 'paycorp' ) );
					}

					return $value;
				},
			),
			'transaction_type' => array(
				'title'       => __( 'PG Transaction Type', 'paycorp' ),
				'type'        => 'select',
				'options'     => array(
					TransactionType::$AUTHORISATION => 'AUTHORISATION', // phpcs:ignore
					TransactionType::$PURCHASE      => 'PURCHASE', // phpcs:ignore
				),
				'description' => __(
					'Indicates the transaction type, given by bank',
					'paycorp'
				),
				'default'     => $this->transaction_type,
			),
		);
	}

	/**
	 * Generate admin panel fields
	 */
	public function admin_options() {
		?>
		<h3><?php esc_html_e( 'Paycorp International/Bancstac IPG', 'paycorp' ); ?></h3>
		<p>
			<strong>
				<?php esc_html_e( 'You have made a successful secure transaction through Paycorp Gateway', 'paycorp' ); ?>
			</strong>
		</p>
		<a href="https://support.paycorp.com.au/" target="_blank">
			<img src="<?php echo plugins_url( '/../assets/images/support.jpg', __FILE__ ); ?>"
				alt="payment gateway"
				class="wpimage" width="20%" height="15%"/>
		</a>
		<table class="form-table">
			<?php $this->generate_settings_html(); ?>
		</table>
		<?php
	}

	/**
	 * Enqueue Scripts in admin
	 *
	 * @param $hook
	 *
	 * @return void
	 */
	public static function admin_enqueue_scripts( $hook ) {
		if ( 'woocommerce_page_wc-settings' === $hook ) {
			// Scripts
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			wp_enqueue_style(
				'admin-paycorp-css',
				untrailingslashit(
					plugins_url(
						'/',
						__FILE__
					)
				) . '/../assets/css/admin' . $suffix . '.css',
				array(),
				null,
				'all'
			);
		}
	}


	/**
	 * If There are no payment fields show the description if set.
	 */
	public function payment_fields() {
		parent::payment_fields();

		if ( ! empty( $this->token_client_id ) && ! is_add_payment_method_page() ) {
			$this->tokenization_script();
			$this->saved_payment_methods();
			$this->save_payment_method_checkbox();
		}
	}

	/**
	 * Validate frontend fields.
	 *
	 * Validate payment fields on the frontend.
	 *
	 * @return bool
	 */
	public function validate_fields() {
		return true;
	}

	/**
	 * Add Payment Method
	 * @return array
	 * @SuppressWarnings(PHPMD.ExitExpression)
	 */
	public function add_payment_method() {
		$return_url = add_query_arg(
			'action',
			'paycorp_card_store',
			admin_url( 'admin-ajax.php' )
		);

		// Initiate payment api
		try {
			$client_config = new ClientConfig();
			$client_config->setServiceEndpoint( $this->pg_domain );
			$client_config->setAuthToken( $this->auth_token );
			$client_config->setHmacSecret( $this->hmac_secret );
			$client_config->setValidateOnly( false );

			$client = new GatewayClient( $client_config );

			$request = new PaymentInitRequest();
			$request->setClientId( $this->client_id );
			$request->setTransactionType( TransactionType::$TOKEN ); // phpcs:ignore
			$request->setClientRef( uniqid( 'paycorp' ) );
			$request->setComment( '' );
			$request->setTokenize( true );

			$transaction_amount = new TransactionAmount();
			$transaction_amount->setTotalAmount( 0 );
			$transaction_amount->setServiceFeeAmount( 0 );
			$transaction_amount->setPaymentAmount( 0 );
			$transaction_amount->setCurrency( get_woocommerce_currency() );
			$request->setTransactionAmount( $transaction_amount );

			$redirect = new Redirect();
			$redirect->setReturnUrl( $return_url );
			$redirect->setReturnMethod( 'POST' );
			$request->setRedirect( $redirect );

			$response = $client->payment()->init( $request );
			$this->log( WC_Log_Levels::INFO, 'init request: ' . var_export( $request, true ) );
			$this->log( WC_Log_Levels::INFO, 'init response: ' . var_export( $response, true ) );
			if ( ! $response ) {
				throw new Exception( 'Payment api has been failed.' );
			}

			// Redirect
			wp_redirect( $response->responseData->paymentPageUrl ); // phpcs:ignore
			exit();
		} catch ( Exception $e ) {
			wc_add_notice( $e->getMessage(), 'error' );

			return array(
				'result'   => 'failure',
				'redirect' => wc_get_account_endpoint_url( 'payment-methods' ),
			);
		}
	}


	/**
	 * Add Payment Method.
	 *
	 * @return void
	 * @SuppressWarnings(PHPMD.Superglobals)
	 * @SuppressWarnings(PHPMD.ExitExpression)
	 */
	public function paycorp_card_store() {
		if ( empty( $_REQUEST['reqid'] ) ) {
			return;
		}

		try {
			$client_config = new ClientConfig();
			$client_config->setServiceEndpoint( $this->pg_domain );
			$client_config->setAuthToken( $this->auth_token );
			$client_config->setHmacSecret( $this->hmac_secret );
			$client_config->setValidateOnly( false );

			$client = new GatewayClient( $client_config );

			$request = new PaymentCompleteRequest();
			$request->setClientId( $this->client_id );
			$request->setReqid( wc_clean( $_REQUEST['reqid'] ) );

			$response = $client->payment()->complete( $request );
			$this->log( WC_Log_Levels::INFO, 'complete request: ' . var_export( $request, true ) );
			$this->log( WC_Log_Levels::INFO, 'complete response: ' . var_export( $response, true ) );

			if ( $response->error ) {
				throw new Exception(
					sprintf(
						'%s %s',
						$response->error->code,
						$response->error->text
					)
				);
			}

			$response_data = $response->responseData; // phpcs:ignore
			if ( $response_data->tokenized ) {
				$credit_card = $response_data->creditCard; // phpcs:ignore

				$token = new WC_Payment_Token_Paycorp();
				$token->set_gateway_id( $this->id );
				$token->set_token( $response_data->token );
				$token->set_last4( substr( $credit_card->number, - 4 ) );
				$token->set_expiry_year( 2000 + substr( $credit_card->expiry, 2, 2 ) );
				$token->set_expiry_month( substr( $credit_card->expiry, 0, 2 ) );
				$token->set_card_type( strtolower( $credit_card->type ) );
				$token->set_user_id( get_current_user_id() );
				$token->set_masked_card( $credit_card->number );

				// Save token
				$token->save();
				if ( ! $token->get_id() ) {
					throw new Exception( 'Unable to save the card.' );
				}

				wc_add_notice( __( 'Payment method successfully added.', 'paycorp' ) );
				wp_redirect( wc_get_account_endpoint_url( 'payment-methods' ) );
				exit();
			}

			throw new Exception( 'Tokenization is failed' );
		} catch ( Exception $e ) {
			wc_add_notice( $e->getMessage(), 'error' );
			wp_redirect( wc_get_account_endpoint_url( 'add-payment-method' ) );
			exit();
		}
	}

	/**
	 * Process Payment
	 *
	 * @param int $order_id
	 *
	 * @return array|false
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
	 * @SuppressWarnings(PHPMD.Superglobals)
	 */
	public function process_payment( $order_id ) {
		$order           = wc_get_order( $order_id );
		$token_id        = isset( $_POST[ 'wc-' . $this->id . '-payment-token' ] ) ?
			wc_clean( $_POST[ 'wc-' . $this->id . '-payment-token' ] ) : null;
		$maybe_save_card = isset( $_POST[ 'wc-' . $this->id . '-new-payment-method' ] )
			&& (bool) wc_clean( $_POST[ 'wc-' . $this->id . '-new-payment-method' ] );

		$amount   = $order->get_total();
		$currency = $order->get_order_currency();

		// Process payment with a saved card
		if ( absint( $token_id ) > 0 ) {
			$token = new WC_Payment_Token_Paycorp( $token_id );
			if ( ! $token->get_id() ) {
				throw new Exception( 'Failed to load token.' );
			}

			// Add payment token to an order
			$order->add_payment_token( $token );
			$order->save();

			try {
				$this->charge_payment( $order, $token );
			} catch ( Exception $e ) {
				$this->log(
					WC_Log_Levels::WARNING,
					sprintf(
						'charge_payment exception: %s.',
						$e->getMessage()
					)
				);

				throw new Exception( $e->getMessage() );
			}

			return array(
				'result'   => 'success',
				'redirect' => $this->get_return_url( $order ),
			);
		}

		// Get transaction type in depends on mode
		$client_id        = $this->client_id;
		$transaction_type = $this->transaction_type;
		if ( (float) $order->get_total() < 0.01 || self::wcs_is_payment_change() ) {
			$transaction_type = TransactionType::$TOKEN; // phpcs:ignore
			$amount           = 0;
			$maybe_save_card  = true;
		}

		if ( self::order_contains_subscription( $order ) ) {
			$maybe_save_card = true;
		}

		// Initiate payment api
		try {
			$client_config = new ClientConfig();
			$client_config->setServiceEndpoint( $this->pg_domain );
			$client_config->setAuthToken( $this->auth_token );
			$client_config->setHmacSecret( $this->hmac_secret );
			$client_config->setValidateOnly( false );

			$client = new GatewayClient( $client_config );

			$request = new PaymentInitRequest();
			$request->setClientId( $client_id );
			$request->setTransactionType( $transaction_type );
			$request->setClientRef( $order_id );
			$request->setComment( '' );
			$request->setTokenize( $maybe_save_card );
			$request->setExtraData( array( 'order_id' => $order_id ) );

			$transaction_amount = new TransactionAmount();
			$transaction_amount->setTotalAmount( 0 );
			$transaction_amount->setServiceFeeAmount( 0 );
			$transaction_amount->setPaymentAmount( intval( $amount * 100 ) );
			$transaction_amount->setCurrency( $currency );
			$request->setTransactionAmount( $transaction_amount );

			$redirect = new Redirect();
			$redirect->setReturnUrl( html_entity_decode( $this->get_return_url( $order ) ) );
			$redirect->setReturnMethod( 'POST' );
			$request->setRedirect( $redirect );

			$response = $client->payment()->init( $request );
			$this->log( WC_Log_Levels::INFO, 'init request: ' . var_export( $request, true ) );
			$this->log( WC_Log_Levels::INFO, 'init response: ' . var_export( $response, true ) );

			if ( ! $response ) {
				throw new Exception( 'Payment api has been failed.' );
			}
		} catch ( Exception $e ) {
			$message = $e->getMessage();

			$this->log(
				WC_Log_Levels::WARNING,
				sprintf(
					'%s: %s.',
					__METHOD__,
					$e->getMessage()
				)
			);

			wc_add_notice( $message, 'error' );

			return false;
		}

		return array(
			'result'   => 'success',
			'redirect' => $response->responseData->paymentPageUrl, // phpcs:ignore
		);
	}

	/**
	 * Workaround to use actual order status in `woocommerce_before_thankyou`
	 *
	 * @param bool $has_status
	 * @param WC_Order $order
	 * @param string $status
	 *
	 * @return bool
	 */
	public function order_has_status( $has_status, $order, $status ) {
		if ( ! is_wc_endpoint_url( 'order-received' ) ) {
			return $has_status;
		}

		if ( 'failed' === $status ) {
			$order = wc_get_order( $order->get_id() );

			return $status === $order->get_status();
		}

		return $has_status;
	}

	/**
	 * Thank you page
	 *
	 * @param $order_id
	 *
	 * @return void
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
	 * @SuppressWarnings(PHPMD.Superglobals)
	 * @SuppressWarnings(PHPMD.ElseExpression)
	 */
	public function thankyou_page( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		if ( $order->get_payment_method() !== $this->id ) {
			return;
		}

		if ( empty( $_REQUEST['reqid'] ) ) {
			return;
		}

		try {
			$client_config = new ClientConfig();
			$client_config->setServiceEndpoint( $this->pg_domain );
			$client_config->setAuthToken( $this->auth_token );
			$client_config->setHmacSecret( $this->hmac_secret );
			$client_config->setValidateOnly( false );

			$client = new GatewayClient( $client_config );

			$request = new PaymentCompleteRequest();
			$request->setClientId( $this->client_id );
			$request->setReqid( wc_clean( $_REQUEST['reqid'] ) );

			$response = $client->payment()->complete( $request );
			$this->log( WC_Log_Levels::INFO, 'complete request: ' . var_export( $request, true ) );
			$this->log( WC_Log_Levels::INFO, 'complete response: ' . var_export( $response, true ) );

			if ( $response->error ) {
				throw new Exception(
					sprintf(
						'%s %s',
						$response->error->code,
						$response->error->text
					)
				);
			}

			$response_data = $response->responseData; // phpcs:ignore

			//$order_id = $response_data->clientRef;
			$response_code = $response_data->responseCode; // phpcs:ignore
			if ( empty( $response_code ) && TransactionType::$TOKEN === $response_data->transactionType ) { // phpcs:ignore
				// TOKEN transaction doesn't have responseCode field.
				$response_code = '00';
			}

			$transaction_id = $response_data->txnReference; // phpcs:ignore
			$credit_card    = $response_data->creditCard; // phpcs:ignore

			// Save Transaction ID
			$order->set_transaction_id( $transaction_id );
			$order->save();

			$order->add_meta_data( '_pg_card_type', $credit_card->type );
			$order->add_meta_data( '_pg_card_holder', $credit_card->holderName ); // phpcs:ignore
			$order->add_meta_data( '_pg_card_number', $credit_card->number );
			$order->add_meta_data( '_pg_card_expiry', $credit_card->expiry );
			$order->add_meta_data( '_pg_response_code', $response_data->responseCode ); // phpcs:ignore
			$order->add_meta_data( '_pg_response_text', $response_data->responseText ); // phpcs:ignore
			$order->add_meta_data( '_pg_authcode', $response_data->authCode ); // phpcs:ignore
			$order->add_meta_data( '_pg_settlement_date', $response_data->settlementDate ); // phpcs:ignore

			$order->save_meta_data();

			switch ( $response_code ) {
				case '00':
					if ( $response_data->tokenized ) {
						$token = new WC_Payment_Token_Paycorp();
						$token->set_gateway_id( $this->id );
						$token->set_token( $response_data->token );
						$token->set_last4( substr( $credit_card->number, - 4 ) );
						$token->set_expiry_year( 2000 + substr( $credit_card->expiry, 2, 2 ) );
						$token->set_expiry_month( substr( $credit_card->expiry, 0, 2 ) );
						$token->set_card_type( strtolower( $credit_card->type ) );
						$token->set_user_id( $order->get_customer_id() );
						$token->set_masked_card( $credit_card->number );

						// Save token
						try {
							$token->save();
							if ( ! $token->get_id() ) {
								throw new Exception( 'Unable to save the card.' );
							}

							$order->add_payment_token( $token );
							$order->save();

							// Add order note
							$order->add_order_note(
								sprintf(
									'Card %s %s %s/%s has been saved.',
									strtoupper( $token->get_card_type() ),
									$token->get_masked_card(),
									$token->get_expiry_month(),
									$token->get_expiry_year()
								)
							);
						} catch ( Exception $e ) {
							$this->log(
								WC_Log_Levels::ERROR,
								sprintf(
									'There was a problem adding the card.: %s.',
									$e->getMessage()
								)
							);

							$order->add_order_note( __( 'There was a problem adding the card.', 'paycorp' ) );
						}
					}

					// Payment is success
					$order->add_order_note(
						sprintf(
						/* translators: 1: transaction id */                            __( 'Transaction success. Transaction Id: %s', 'paycorp' ),
							$transaction_id
						)
					);

					if ( TransactionType::$PURCHASE === $this->transaction_type ) { // phpcs:ignore
						$order->payment_complete( $transaction_id );
					} else {
						// Reduce stock
						$order_stock_reduced = $order->get_meta( '_order_stock_reduced' );
						if ( ! $order_stock_reduced ) {
							wc_reduce_stock_levels( $order->get_id() );
						}

						$order->update_status( 'on-hold' );
					}

					break;
				default:
					$message = sprintf(
						'Transaction failed. Transaction ID: %s. Code: %s. Message: %s',
						$transaction_id,
						$response_code,
						$response_data->responseText // phpcs:ignore
					);

					$order->update_status( 'failed', $message );

					throw new Exception( $message );
			}
		} catch ( Exception $e ) {
			$this->log(
				WC_Log_Levels::WARNING,
				sprintf(
					'%s: %s.',
					__METHOD__,
					$e->getMessage()
				)
			);

			wc_add_notice( $e->getMessage(), 'error' );
			?>
			<strong><?php echo esc_html( $e->getMessage() ); ?></strong>
			<?php
		}
	}

	/**
	 * @param WC_Order $order
	 * @param WC_Payment_Token_Paycorp $token
	 *
	 * @throws Exception
	 * @SuppressWarnings(PHPMD.ElseExpression)
	 */
	public function charge_payment( $order, $token ) {
		// Check access
		if ( $token->get_user_id() !== $order->get_user_id() ) {
			throw new Exception( 'Access denied.' );
		}

		$client_config = new ClientConfig();
		$client_config->setServiceEndpoint( $this->pg_domain );
		$client_config->setAuthToken( $this->auth_token );
		$client_config->setHmacSecret( $this->hmac_secret );
		$client_config->setValidateOnly( false );

		$client = new GatewayClient( $client_config );

		$card = new CreditCard();
		$card->setNumber( $token->get_token() );
		$card->setExpiry( $token->get_expiry_month() . ( $token->get_expiry_year() - 2000 ) );

		$request = new PaymentRealTimeRequest();
		$request->setClientId( $this->token_client_id );
		$request->setTransactionType( $this->transaction_type );
		$request->setClientRef( $order->get_id() );
		$request->setComment( '' );
		$request->setCreditCard( $card );
		$request->setExtraData( array( 'order_id' => $order->get_id() ) );

		$transaction_amount = new TransactionAmount();
		$transaction_amount->setTotalAmount( 0 );
		$transaction_amount->setServiceFeeAmount( 0 );
		$transaction_amount->setPaymentAmount( intval( $order->get_total() * 100 ) );
		$transaction_amount->setCurrency( $order->get_currency() );
		$request->setTransactionAmount( $transaction_amount );

		$response = $client->payment()->realTime( $request );
		$this->log( WC_Log_Levels::INFO, 'realTime request: ' . var_export( $request, true ) );
		$this->log( WC_Log_Levels::INFO, 'realTime response: ' . var_export( $response, true ) );

		$response_data  = $response->responseData; // phpcs:ignore
		$response_code  = $response_data->responseCode; // phpcs:ignore
		$transaction_id = $response_data->txnReference; // phpcs:ignore

		$order->set_transaction_id( $transaction_id );
		$order->save();

		$order->add_meta_data( '_pg_response_code', $response_data->responseCode ); // phpcs:ignore
		$order->add_meta_data( '_pg_response_text', $response_data->responseText ); // phpcs:ignore
		$order->add_meta_data( '_pg_authcode', $response_data->authCode ); // phpcs:ignore
		$order->add_meta_data( '_pg_settlement_date', $response_data->settlementDate ); // phpcs:ignore
		$order->save_meta_data();

		switch ( $response_code ) {
			case '00':
				// Payment is success
				$order->add_order_note(
					sprintf(
					/* translators: 1: transaction id */                        __( 'Transaction success. Transaction Id: %s', 'paycorp' ),
						$transaction_id
					)
				);

				if ( TransactionType::$PURCHASE === $this->transaction_type ) { // phpcs:ignore
					$order->payment_complete( $transaction_id );
				} else {
					// Reduce stock
					$order_stock_reduced = $order->get_meta( '_order_stock_reduced' );
					if ( ! $order_stock_reduced ) {
						wc_reduce_stock_levels( $order->get_id() );
					}

					$order->update_status( 'on-hold' );
				}

				break;
			default:
				$message = sprintf(
					'Transaction failed. Transaction ID: %s. Code: %s',
					$transaction_id,
					$response_code
				);
				$order->update_status( 'failed', $message );

				throw new Exception( $message );
		}
	}

	/**
	 * Add meta boxes in admin.
	 *
	 * @return void
	 */
	public function add_meta_boxes() {
		global $post_id;

		$order = wc_get_order( $post_id );

		if ( $order ) {
			$payment_method = $order->get_payment_method();
			if ( $this->id === $payment_method ) {
				add_meta_box(
					'paycorp_payment_info',
					__( 'Payment Info', 'paycorp' ),
					__CLASS__ . '::order_meta_box_payment_actions',
					'shop_order',
					'side',
					'default'
				);
			}
		}
	}

	/**
	 * MetaBox for Payment Actions.
	 *
	 * @return void
	 */
	public static function order_meta_box_payment_actions() {
		global $post_id;

		$order = wc_get_order( $post_id );

		wc_get_template(
			'admin/payment-info.php',
			array(
				'card_type'       => $order->get_meta( '_pg_card_type' ),
				'card_holder'     => $order->get_meta( '_pg_card_holder' ),
				'card_number'     => $order->get_meta( '_pg_card_number' ),
				'card_expiry'     => $order->get_meta( '_pg_card_expiry' ),
				'response_code'   => $order->get_meta( '_pg_response_code' ),
				'response_text'   => $order->get_meta( '_pg_response_text' ),
				'authcode'        => $order->get_meta( '_pg_authcode' ),
				'settlement_date' => $order->get_meta( '_pg_settlement_date' ),
				'transaction_id'  => $order->get_transaction_id(),
			),
			'',
			dirname( __FILE__ ) . '/../templates/'
		);
	}

	/**
	 * WC Subscriptions: Is Payment Change.
	 *
	 * @return bool
	 */
	public static function wcs_is_payment_change() {
		return class_exists( 'WC_Subscriptions_Change_Payment_Gateway', false )
			&& WC_Subscriptions_Change_Payment_Gateway::$is_request_to_change_payment;
	}

	/**
	 * Checks an order to see if it contains a subscription.
	 * @see wcs_order_contains_subscription()
	 *
	 * @param WC_Order $order
	 *
	 * @return bool
	 */
	private static function order_contains_subscription( $order ) {
		if ( ! function_exists( 'wcs_order_contains_subscription' ) ) {
			return false;
		}

		return wcs_order_contains_subscription( $order );
	}

	/**
	 * Log a message.
	 *
	 * @param $level
	 * @param $message
	 * @param array $context
	 *
	 * @see WC_Log_Levels
	 */
	private function log( $level, $message, array $context = array() ) {
		$logger = wc_get_logger();

		if ( ! is_string( $message ) ) {
			$message = var_export( $message, true );
		}

		$logger->log(
			$level,
			sprintf(
				'[%s] %s [%s]',
				$level,
				$message,
				count( $context ) > 0 ? var_export( $context, true ) : ''
			),
			array_merge(
				$context,
				array(
					'source'  => $this->id,
					'_legacy' => true,
				)
			)
		);
	}
}
