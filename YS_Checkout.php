<?php
/**
 * Checkout functionality
 *
 * The WooCommerce checkout class handles the checkout process, collecting user data and processing the payment.
 *
 * @package WooCommerce/Classes
 * @version 3.4.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Checkout class.
 */
class YS_Checkout extends WC_Checkout {
  protected static $instance = null;

	private function __construct() {

	}

  public static function instance() {
    if (is_null(self::$instance)) {
			self::$instance = new self();
			add_filter( 'woocommerce_default_address_fields', array(self::$instance, 'default_address_fields'), 1);
			add_filter( 'woocommerce_checkout_fields', array(self::$instance, 'checkout_fields'), 1);
		//add_filter( 'woocommerce_order_shipping_to_display_shipped_via', array(self::$instance, 'order_shipping_to_display_shipped_via'), 10, 2);
			
			add_action( 'woocommerce_checkout_billing', array( self::$instance, 'checkout_form_billing'));
			add_action( 'woocommerce_checkout_shipping', array( self::$instance, 'checkout_form_shipping'));
			do_action( 'woocommerce_checkout_init', self::$instance );			
    }
    return self::$instance;
  }

	public function default_address_fields($fields) {
		unset($fields['company']);
		unset($fields['country']);
	//	unset($fields['address_1']);
		unset($fields['address_2']);
		unset($fields['city']);
		unset($fields['state']);
		unset($fields['postcode']);
		return $fields;
	}


	public function checkout_fields($fields) {
		unset($fields['billing']['billing_first_name']['class'][0]);
		unset($fields['billing']['billing_last_name']);

		$fields['billing']['billing_address_1'] = [
			'label' =>  __('Адрес за доставка'),
			'placeholder' =>  __('Въведете пълния адрес за доставка'),
			'required' => true,
			'class' => ["form-row-wide", "address-field"],
			'type' => 'textarea',
			'priority' => 400,
		];

		
		$form = [
			'billing_first_name' => [
				'label' => __('Име и фамилия'),
				'priority' => 0,
				'login' => function () {
					return [
						'custom_attributes' => ['disabled' => true]
					];
				}
			],

			'billing_email' => [
				'class' => [0 => 'form-row-first'],
				'priority' => 100,
				'login' => function () {
					return  [
						'custom_attributes' => ['disabled' => true]
					];
				}
			],

			'billing_phone' => [
				'class' => [0 => 'form-row-last'],
				'priority' => 200,
				'login' => function (){
					return [
						'custom_attributes' => ['disabled' => true]
					];
				}
			]
		];


		foreach($form as $key => $val) {
			foreach($val as $key2 => $val2) {
				if(array_key_exists($key2, $fields['billing'][$key])) {
					$fields['billing'][$key][$key2] = $val2;
				}
			}

			if(is_user_logged_in()) {
				$fields['billing'][$key] = array_merge($fields['billing'][$key], call_user_func($val['login']));	
			}
		}


		if(is_user_logged_in()) {
			$drop_down_address = [
				'type' => 'select',
				'label' =>  __('Адрес за доставка'),
				'required' => true,
				'class' => ["wps-drop ys_address_dropdown"],
				'priority' => 300,
				'options' => (function (){
					$array = [];
					foreach(YS_User::getUserData()->delivery_address as $value) {
						$array[$value->id] = __($value->address, 'wps');
					}
					$array['custom'] = __('Нов адрес', 'wps');
					return $array; 
				})()
			];


			$fields['billing']['billing_address'] = $drop_down_address;
			$fields['billing']['billing_email']['custom_attributes'] = ['disabled' => true];
			$fields['billing']['billing_phone']['custom_attributes'] = ['disabled' => true];
			array_push($fields['billing']['billing_address_1']['class'], 'ys_address', 'hide') ;
		}
		
		if(function_exists('ys_set_checkout_fields')) {
			$fields = ys_set_checkout_fields($fields);
		}

		return $fields;
	}


  public function process_checkout() {
		//parent::process_checkout();
		
		try {
			$nonce_value = wc_get_var( $_REQUEST['woocommerce-process-checkout-nonce'], wc_get_var( $_REQUEST['_wpnonce'], '' ) ); // @codingStandardsIgnoreLine.
			if ( empty( $nonce_value ) || ! wp_verify_nonce( $nonce_value, 'woocommerce-process_checkout' ) ) {
				WC()->session->set( 'refresh_totals', true );
				throw new Exception( __( 'We were unable to process your order, please try again.', 'woocommerce' ) );
			}

			wc_maybe_define_constant( 'WOOCOMMERCE_CHECKOUT', true );
			wc_set_time_limit( 0 );
			do_action( 'woocommerce_before_checkout_process' );
			if ( WC()->cart->is_empty() ) {
				/* translators: %s: shop cart url */
				throw new Exception( sprintf( __( 'Sorry, your session has expired. <a href="%s" class="wc-backward">Return to shop</a>', 'woocommerce' ), esc_url( wc_get_page_permalink( 'shop' ) ) ) );
			}

			do_action( 'woocommerce_checkout_process' );
			$errors      = new WP_Error();
			$posted_data = $this->get_posted_data();

			// Update session for customer and totals.
			$this->update_session( $posted_data );

			// Validate posted data and cart items before proceeding.
			$this->validate_checkout( $posted_data, $errors );

			foreach ($errors->get_error_messages() as $message ) {
				wc_add_notice( $message, 'error' );
			}

			if ( empty( $posted_data['woocommerce_checkout_update_totals'] ) && 0 === wc_notice_count( 'error' ) ) {
				$this->process_customer($posted_data);
				$order_id = $this->create_order($posted_data);
				$order = wc_get_order($order_id);
				
				if ( is_wp_error($order_id)) {
					throw new Exception($order_id->get_error_message());
				}

				if (!$order) {
					throw new Exception( __('Unable to create order.', 'woocommerce'));
				}

				do_action('woocommerce_checkout_order_processed', $order_id, $posted_data, $order);

				if (WC()->cart->needs_payment()) {
					$order = $order->create_order($order_id, $order);
					$order_id = $order->get_id();
					$this->process_order_payment($order_id, $posted_data['payment_method']);
				} else {
					$this->process_order_without_payment($order_id);
				}
			}

		}	catch (Exception $e) {
			wc_add_notice($e->getMessage(), 'error');
		}
		$this->send_ajax_failure_response();
	}


	public function create_order($data) {
		try {
			$cart_hash = md5( wp_json_encode( wc_clean( WC()->cart->get_cart_for_session() ) ) . WC()->cart->total );
			$available_gateways = WC()->payment_gateways->get_available_payment_gateways();
			$order = new YS_Order();

			$fields_prefix = array(
				'shipping'  => true,
				'billing'   => true,
			);
			$shipping_fields = array(
				'shipping_method'   => true,
				'shipping_total'    => true,
				'shipping_tax'      => true,
			);
			foreach ($data as $key => $value) {
				if (is_callable( array($order, "set_{$key}") ) ){
					$order->{"set_{$key}"}( $value );
					// Store custom fields prefixed with wither shipping_ or billing_. This is for backwards compatibility with 2.6.x.
				} 
				else if(array_key_exists($key, $order->custom_methods['set'])) {
					$order->custom_methods['set'][$key]($value);
				}	
				elseif (isset( $fields_prefix[ current( explode( '_', $key ) ) ] ) ) {
					if (!isset( $shipping_fields[ $key ] ) ) {
						$order->update_meta_data('_'.$key, $value );
					}
				}
			}

			$order->set_created_via( 'checkout' );
			$order->set_cart_hash( $cart_hash );
			$order->set_customer_id( apply_filters( 'woocommerce_checkout_customer_id', get_current_user_id() ) );
			$order->set_currency( get_woocommerce_currency() );
			$order->set_prices_include_tax( 'yes' === get_option( 'woocommerce_prices_include_tax' ) );
			$order->set_customer_ip_address( WC_Geolocation::get_ip_address() );
			$order->set_customer_user_agent( wc_get_user_agent() );
			$order->set_customer_note( isset( $data['order_comments'] ) ? $data['order_comments'] : '' );
			$order->set_payment_method( isset( $available_gateways[ $data['payment_method'] ] ) ? $available_gateways[ $data['payment_method'] ] : $data['payment_method'] );
			$order->set_shipping_total( WC()->cart->get_shipping_total() );
			$order->set_discount_total( WC()->cart->get_discount_total() );
			$order->set_discount_tax( WC()->cart->get_discount_tax() );
			$order->set_cart_tax( WC()->cart->get_cart_contents_tax() + WC()->cart->get_fee_tax() );
			$order->set_shipping_tax( WC()->cart->get_shipping_tax() );
			$order->set_total( WC()->cart->get_total( 'edit' ) );
			$this->create_order_line_items( $order, WC()->cart );
			$this->create_order_fee_lines( $order, WC()->cart );
			$this->create_order_shipping_lines( $order, WC()->session->get( 'chosen_shipping_methods' ), WC()->shipping->get_packages() );
			$this->create_order_tax_lines( $order, WC()->cart );
			$this->create_order_coupon_lines( $order, WC()->cart );
			/**
			 * Action hook to adjust order before save.
			 *
			 * @since 3.0.0
			 */
			do_action( 'woocommerce_checkout_create_order', $order, $data );
			// Save the order.
			WC()->order_factory::set_order($order);
			$order_id = $order->save();
			do_action( 'woocommerce_checkout_update_order_meta', $order_id, $data );
			return $order_id;
		} catch ( Exception $e ) {
			return new WP_Error( 'checkout-error', $e->getMessage() );
		}
	}

  protected function validate_checkout(&$data, &$errors) {
		if(is_user_logged_in()) {
			$user = YS_User::getUserData();
			$data['billing_first_name'] = $user->full_name;
			$data['billing_email'] = $user->email;
			$data['billing_phone'] = $user->phone;
			WC()->customer->set_shipping_country('BG');

			foreach($user->delivery_address as $value) {
				if($value->id === (int)$data['billing_address']) {
					$data['billing_address_1'] = $value->address;
					break;
				}
			}
			unset($data['billing_address']);
		}

		if(round(WC()->cart->get_totals()['total']) <= 0) {
			$errors->add( 'shipping', __( 'Не може да приключите поръчка без сума!', 'woocommerce' ) );
		}

		$validator = new Validator();
		$inputs = [
			'billing_first_name'	=> sanitize($data['billing_first_name'], 'trim|stripslashes|htmlspecialchars'),
			'billing_email'				=> sanitize($data['billing_email'], 'trim|stripslashes|htmlspecialchars'),
			'billing_phone'				=> sanitize($data['billing_phone'], 'trim|stripslashes|htmlspecialchars'),
			'billing_address_1' 	=> sanitize($data['billing_address_1'], 'trim|stripslashes|htmlspecialchars')
		];
		
		$rules = [
			'billing_first_name'	=> ['required'],
			'billing_email' 			=> ['required', 'email'],
			'billing_phone' 			=> ['required'],
			'billing_address_1' 	=> ['required']
		]; 
		
		$messages = [
			'billing_first_name' => [
				'required' => 'Не сте попълнили Име и фамилия', 
			],
			
			'billing_email' => [
				'required' => 	'Не сте попълнили Имейл адрес',
				'email' => 'Невалиден Имейл адрес',
			],
			
			'billing_phone' => [
				'required' => 	'Не сте попълнили Телефон',
			],

			'billing_address_1' => [
				'required' => 	'Не сте попълнили Адрес за доставка',
			]
		];
		
		if(function_exists('ys_validate_checkout')) {
			ys_validate_checkout($data, $inputs, $rules, $messages);
		}

		if(!$validator->validate($inputs, $rules, $messages)) {
			foreach($validator->getErrors() as $error) {
				$errors->add( 'validation', $error, 'woocommerce');
			}	
			return;		
		}
		parent::validate_checkout($data, $errors);
	}
	

	// public function order_shipping_to_display_shipped_via($label, $instance){
	// 	return $label = '';
	// }

}