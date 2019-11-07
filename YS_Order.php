<?php 

class YS_Order extends WC_Order {
	private static $ys_order = null;
	protected $emails = [];
	public $custom_methods = null;
	protected $ys_payment_methods = [
		'cod' => 1, 
		'bacs' => 2, 
		'epaybg' => 3, 
		'epaybg_directpay' => 4,
		'epaybg_easypay' => 5
	];


	public function __construct($order = 0) {
		add_filter( 'woocommerce_order_subtotal_to_display', array($this, 'order_subtotal_to_display'), 10, 3);
		parent::__construct($order);
		remove_all_actions( 'woocommerce_order_status_processing' );
		remove_all_actions( 'woocommerce_sadssorder_status_on-hold');
		$file = STYLESHEETPATH.'/Custom_Order.php';
		if(file_exists($file)) {
			$this->custom_methods = require_once($file);
		}
	}


	public function set_order_session() {
		$this->unset_order_session();
		$_SESSION['custom_order'] = $this->get_changes();  
		$_SESSION['custom_order_items'] = [];
		foreach($this->get_items() as $key => $val){
			$_SESSION['custom_order_items']['line_items'][$key] = [
				'class' => get_class($val),
				'changes' => $val->get_changes()
			];
		}

		foreach($this->get_items('shipping') as $key => $val){
			$_SESSION['custom_order_items']['shipping_lines'][$key] = [
				'class' => get_class($val),
				'changes' => $val->get_changes()
			];
		}
	}

	public function unset_order_session() {
		if(isset($_SESSION['custom_order'])) {
			unset($_SESSION['custom_order_items']);
			unset($_SESSION['custom_order']);
		}
	}

	private function getFiscalUID() {
		$nodeJS = new FiscalServer();
		$inputs = [
			'OperationName' => 'GetDeviceAndFiscalMemoryUID',
			'Params' => [],
			'FiscalDevice' => get_option('ys_fiscal_server_device', ''),
			'IPAdress' => get_option('ys_fiscal_server_ip', ''),
			'port' => get_option('ys_fiscal_server_port', '')
		];

		$result = $nodeJS->apiRequest('http://94.156.153.21:3000/', 'get-device-uid', $inputs, 'POST');
		if($result->error) {
			throw new Exception(__( $result->error, 'woocommerce'));
		}
		return $result;
	}

	public function create_order($orderID, $instance) {

		try{
			$order_info = 'Име: '.$instance->get_billing_first_name()."\n";
			$order_info .= 'Имейл: '.$instance->get_billing_email()."\n";
			$order_info .= 'Телефон: '.$instance->get_billing_phone()."\n"; 
			$order_info .= 'Адрес: '.$instance->get_billing_address_1()."\n";
			if(function_exists('ys_get_checkout_fields')) {
				$order_info .= ys_get_checkout_fields($instance);
			}
			
			if($instance->get_customer_note()) {
				$order_info .= 'Забележка:'."\n";
				$order_info .= $instance->get_customer_note();
			}

			$fiscal_server = $this->getFiscalUID();

			$order = YanakAPI::instance()->apiRequest('order', 'POST', [
				'sessionID'	=> session_id(),
				'ip' 									=> WC_Geolocation::get_ip_address(),
				'email' 							=> $instance->get_billing_email(),
				'customerID' 					=> is_user_logged_in() ? YS_User::getUserData()->id : 0,
				'total' 							=> WC()->cart->get_totals()['total'],
				'paymentMethod' 			=> $instance->ys_payment_methods[$instance->get_payment_method()],
				'orderInfo' 					=> $order_info,
				'fiscalMemoryUID' 		=> $fiscal_server->fiscalMemoryUID,
				'deviceUID' 					=> $fiscal_server->deviceUID
			]);
				
			$instance->set_id($order->document_information->document_number);
			$instance->set_order_key($order->document_information->document_id);
			$instance->getYSOrder($instance->get_order_key());
			$_SESSION['custom_order']['order_key'] = $instance->get_order_key();
			return $instance;
		} catch(Exception $e) {
			throw new Exception(__( $e->getMessage(), 'woocommerce'));
		}
	}

	public function getYSOrder($orderID) {
		if(!self::$ys_order) {
			self::$ys_order = YanakAPI::instance()->apiRequest('order', 'GET', [
				'docID' => (int)$orderID,
			]);
			$this->set_total(self::$ys_order->sum_price);
		}
		return self::$ys_order;
	}

	public function get_items( $types = 'line_item' ) {
		if(($types !== 'line_item')) {
			return parent::get_items($types);
		} 
		else if(($types === 'line_item' && empty(self::$ys_order) )) {
			return parent::get_items($types);
		}
		else {
			$ys_items = [];
			foreach (self::$ys_order->rows as $key => $val) {
				$ys_items[$key] = new WC_Order_Item_Product();
				$ys_items[$key]->set_order_id($this->get_id());

				$ys_items[$key]->set_props(
					array(
						'id' 					 => $key,
						'product_id'   =>  getPostIDFromStockID($val->stock_id),
						'name'         => $val->name,
						'quantity'     => $val->quantity,
						'subtotal'     => $val->sum_price,
						'meta_data'		 => [],
					)
				);
				if(!empty($val->dobavki_info)) {
					$ys_items[$key]->meta_data[0] = new stdClass();
					$ys_items[$key]->meta_data[0]->id = $key+1;
					$ys_items[$key]->meta_data[0]->key = 'Добавки';
					$ys_items[$key]->meta_data[0]->value = '('.$val->dobavki_info.')';
				}
				else if(!empty($val->article_size_str)){
					$ys_items[$key]->meta_data[0] = new stdClass();
					$ys_items[$key]->meta_data[0]->id = $key+1;
					$ys_items[$key]->meta_data[0]->key = 'Размер';
					$ys_items[$key]->meta_data[0]->value = $val->article_size_str;
					if(!empty($val->article_color)) {
						$ys_items[$key]->meta_data[0]->value .= ' / <strong>Цвят: </strong>'.$val->article_color;
					}

				}
			}
			$items = $ys_items;
			$this->set_total(self::$ys_order->sum_price);
			return $items;
		}
	}

	public function save() {
		try {
			$this->maybe_set_user_billing_email();

			if ( $this->data_store ) {
				do_action( 'woocommerce_before_' . $this->object_type . '_object_save', $this, $this->data_store );
			}
			$this->status_transition();
		} catch ( Exception $e ) {
			$logger = wc_get_logger();
			$logger->error(
				sprintf( 'Error saving order #%d', $this->get_id() ), array(
					'order' => $this,
					'error' => $e,
				)
			);
			$this->add_order_note( __( 'Error saving order.', 'woocommerce' ) . ' ' . $e->getMessage() );
		}
		return $this->get_id();
	}

	public function collectEmails($to, $subject, $message) {
		$this->emails[] = [
			'site_title' => get_bloginfo(),
			'to' => $to,
			'subject' => $subject, 
			'text' => $message
		];
	}

	public function sendMail() {
		try {
			YanakAPI::instance()->apiRequest('order/emails/send', 'GET', ['emails' => $this->emails]);
		} catch(Exception $e) {
				throw new Exception(__($e->getMessage(), 'woocommerce'));
		}
	}
	
	
	public function set_changes($data) {
		$this->set_id($data['id']);
		$this->changes = $data['changes'];
		foreach($data['items'] as $key => $val) {
			foreach($val as $key2 => $val2) {
				$this->items[$key][$key2] = new $val2['class']();
				$this->items[$key][$key2]->changes = $val2['changes'];
			}
		}
	}


	public function add_order_note( $note, $is_customer_note = 0, $added_by_user = false ) {
		return true;
	}


	public function order_subtotal_to_display($subtotal, $compound, $instance){
		return false;
	}

}