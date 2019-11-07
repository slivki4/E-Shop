<?php 

class YS_Order_Factory extends WC_Order_Factory {
	private static $custom_order = null;
	

	public function __construct() {
		add_filter('woocommerce_order_class', array(__CLASS__, 'ys_order_class'), 10, 3); 
		add_filter( 'woocommerce_order_shipping_to_display_shipped_via', array(__CLASS__, 'order_shipping_to_display_shipped_via'), 10, 2);
	//	add_action('woocommerce_order_status_pending_to_processing', array('YS_Order', 'create_order'), 10, 2);
		add_action('woocommerce_checkout_update_order_meta', array(__CLASS__, 'hasCreatedOrder'), 10, 2);
		add_action('current_screen', array(__CLASS__, 'current_screen') );
	}


	public static function order_shipping_to_display_shipped_via($label, $instance){
		return $label = '';
	}


	public function current_screen() {
		$current_screen = get_current_screen();
		if( $current_screen->id === "edit-shop_order" ) {
			// Run some code, only on the admin widgets page
		}
	}


	public static function ys_order_class($classname, $order_type, $order_id ){
		return 'YS_Order';
	}

	public static function hasCreatedOrder($order_id, $data) {
		if($order_id === 0) {
			throw new Exception(__('Възникна грешка при създаването на поръчката! Моля, опитайте отново.', 'woocommerce'));
		}
	}		

	public static function get_order( $order_id = false ) {
		$order_id = self::get_order_id( $order_id );

		if ( ! $order_id ) {
			return false;
		}

		$order_type      = 'shop_order';
		$order_type_data = wc_get_order_type( $order_type );
		if ( $order_type_data ) {
			$classname = $order_type_data['class_name'];
		} else {
			$classname = false;
		}

		// Filter classname so that the class can be overridden if extended.
		$classname = apply_filters( 'woocommerce_order_class', $classname, $order_type, $order_id );

		if ( ! class_exists( $classname ) ) {
			return false;
		}

		try {
			return self::get_custom_order($order_id);
		//	return new $classname( $order_id );
		} catch ( Exception $e ) {
			wc_caught_exception( $e, __FUNCTION__, func_get_args() );
			return false;
		}
	}


	public static function set_order($order){
		$order->set_id(mt_rand(1000, 9999));
		$order->set_date_created(time());
		//$order->set_order_key( 'wc_' . apply_filters( 'woocommerce_generate_order_key', uniqid( 'order_' ) ) );
		$order->set_order_session();		
		self::$custom_order = $order;
	}

	public static function get_custom_order($order_id) {
		if(!self::$custom_order) {
			self::$custom_order = new YS_Order();
			self::$custom_order->set_changes([
				'id' => $order_id,
				'changes' => $_SESSION['custom_order'],
				'items' => $_SESSION['custom_order_items']
			]);
		}
		return self::$custom_order;
	}




}