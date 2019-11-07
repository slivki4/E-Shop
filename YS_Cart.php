<?php
class YS_Cart extends WC_Cart {
	private $cart_totals = []; 
	private $ys_cart_contents = [];

	public function __construct() {
		parent::__construct();
		remove_action('wp_loaded', array('WC_Form_Handler', 'update_cart_action'), 20);
		add_action( 'wp_loaded', array( $this, 'update_cart' ), 20 );
		add_action('woocommerce_before_cart_item_quantity_zero', array($this, 'remove_cart_item'), 10, 1);
		add_filter('woocommerce_loop_add_to_cart_args', array($this, 'loop_add_to_cart_args'), 999, 2 );
		add_filter('woocommerce_loop_add_to_cart_link', array($this, 'loop_add_to_cart_link'), 999, 2 );
		add_filter('woocommerce_get_item_data', array($this, 'ys_get_item_data'), 999, 2 );
		$this->getBasket();
	}

	private function getBasket() {
		$this->cart_contents = [];
		$result = YanakAPI::instance()->apiRequest('cart', 'GET', [
			'sessionID' => session_id(),
			'customerID' => YS_User::getID()
		]);
				
		if(!empty($result->items)) {
			$this->cart_totals = [
				'quantity' => $result->quantity,
				'basicTotal' => $result->basicTotal,
				'total' => $result->total,	
			];

			foreach ($result->items as $value) {
				$this->cart_contents[$value->kasbuf_id] = [
					'key' => $value->stock_id,
					'product_id' =>  getPostIDFromStockID($value->stock_id),
					'kasbuf_id' => $value->kasbuf_id,
					'variation_id' => 0,
					'variation' => '',						
					'quantity' => $value->quantity,
					'data' => new Yanak_WC_Product_Simple(getPostIDFromStockID($value->stock_id), $value)
				];
				$this->ys_cart_contents[$value->kasbuf_id] = $this->cart_contents[$value->kasbuf_id];
			}
 			$this->set_totals();
		}
	}

	public function add_to_cart( $product_id = 0, $quantity = 1, $variation_id = 0, $variation = array(), $cart_item_data = array() ) {
		$inputs = [
			'sessionID' => session_id(),
			'stockID' => getStockIDFromPostID($product_id),
			'customerID' => YS_User::getID(),
			'quantity' => $quantity,
			'information' => $this->getInfo(),
			'additions' => $this->getModifires(),
			'rootStockID' => 0
		];
		
		$inputs = $this->maybeModifyAddToCartInputs($inputs);

		YanakAPI::instance()->apiRequest('cart', 'POST', $inputs);
		$this->getBasket();
		return getStockIDFromPostID($product_id);
	}

	
	public function update_cart() {
		if(!empty($_GET['remove_item']) && !empty($_GET['_wpnonce']) && wp_verify_nonce(wc_get_var($_GET['_wpnonce']), 'woocommerce-cart')) 
			return $this->remove_cart_item($_GET['remove_item']);
		
			
		$nonce_value = isset($_POST['woocommerce-cart-nonce']) ? $_POST['woocommerce-cart-nonce'] : '';
		if(!wp_verify_nonce(wc_get_var($nonce_value), 'woocommerce-cart')) 
			return;

		if (empty($_POST['remove_item'] || $_POST['update_cart'] || $_POST['proceed'])) 
		return;

		wc_nocache_headers();
		$items = $_POST['cart'];

		if(!empty($items)) {
			foreach($this->cart_contents as $key => $value) {
				if($items[$key] && $value['quantity'] != $items[$key]['qty']) {
					$items[$key]['qty'] = (float)$items[$key]['qty'];
					if($items[$key]['qty'] > 0) {
						YanakAPI::instance()->apiRequest('cart', 'PUT', [
							'id' => $value['kasbuf_id'],
							'quantity' => $items[$key]['qty']
						]);	
					} else {
						$this->remove_cart_item($value->stock_id);
					}
				}
			}
			$this->getBasket();
		}

		if (!empty( $_POST['proceed'])) {
			wp_safe_redirect(wc_get_checkout_url());
			exit;
		} else {
			wc_add_notice( __( 'Cart updated.', 'woocommerce' ) );
			$referer = remove_query_arg( array( 'remove_coupon', 'add-to-cart' ), ( wp_get_referer() ? wp_get_referer() : wc_get_cart_url() ) );
			wp_safe_redirect( $referer );
			exit;
		}
	}
	
	
	public function remove_cart_item($cart_item_key) {
		YanakAPI::instance()->apiRequest('cart', 'DELETE', [
			'id' => $this->cart_contents[$cart_item_key]['kasbuf_id']
		]);
		parent::remove_cart_item($cart_item_key);
	}

	
	public function set_totals($value = array()) {
		if(!empty($this->cart_totals)) {
			parent::set_totals([
				'shipping_total' => 0,
				'subtotal' => $this->cart_totals['total'],
				'cart_contents_total' => $this->cart_totals['total'],
				'total' => $this->cart_totals['total'],
			]);
		} else {
			parent::set_totals($value);
		}

		WC()->session->set('cart_totals', $this->get_totals());
	}

	
	public function get_cart_contents_count(){
		return count($this->cart_contents);
	}


	public function loop_add_to_cart_args($args, $instance) {
		if($instance->getProduct()->specification === 1) {
			$args['class'].= ' ys-fiscal';
		}

		else if($instance->getProduct()->stkType === 14) {
			$args['class'].= ' ys-package';
		}

		return $args;
	}


	public function loop_add_to_cart_link($html, $instance) {
		return $html; 
	}


	private function getinfo() {
		$info = '';
		if(isset($_SESSION['fiscal_product'])) {
			$info = YS_Fiscal::getFiscalInfo();
		}
		return $info;
	}


	private function getModifires() {
		$myArray = [];
		if(!empty($_POST['ys_additions'])) {
			$explode = explode(',', $_POST['ys_additions']);
			foreach($explode as $value) {
				$myArray[]['id'] = $value;
			}
		}
		return $myArray;
	}


	public function getYsCartItem($key){
		return $this->ys_cart_contents[$key];
	}


	public function ys_get_item_data($item_data, $cart_item){
		if(!empty($cart_item['data']->getProduct()->additions)) {
			$item_data[0]	= [
				'name' => 'Добавки',
				'value' => '('.$cart_item['data']->getProduct()->additions.')'
			];
		}

		else if(!empty($cart_item['data']->getProduct()->size)) {
			$item_data[0]	= [
				'name' => 'Размер',
				'value' => $cart_item['data']->getProduct()->size
			];
			
			if(!empty($cart_item['data']->getProduct()->color)) {
				$item_data[1] = [
					'name' => '/ Цвят', 
					'value' => $cart_item['data']->getProduct()->color
				];
			}
		}

    return $item_data;
	}

	private function maybeModifyAddToCartInputs($inputs){
		$product = Stocks::get($inputs['stockID']);
		if(isset($product->stkType)) {
			switch($product->stkType) {
				case ($product->stkType == 11 || $product->stkType == 12): { 
					$clothes = YS_Clothes::getSizes($product->id);
					$size = (int)$_POST['size'];
					$color =(int)$_POST['color'];

					$inputs['size'] = $clothes[$size]->razmer;
					$inputs['stockID'] = $clothes[$size]->items[$color]->id;
					$inputs['rootStockID'] = getStockIDFromPostID((int)$_POST['add-to-cart']) ;
				}; break;
				
				case 14: {
					$inputs['stockID'] = (int)$_POST['products-list'];
					$inputs['rootStockID'] = getStockIDFromPostID((int)$_POST['add-to-cart']);
				}; break;
			}
		}

		return $inputs;
	}
}