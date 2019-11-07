<?php

class Yanak_WC_Product_Factory extends WC_Product_Factory {
	// TODO: kato id e > 100000 tazi funkciq moje i da ne se overrideva?
	public function get_product( $post = false, $deprecated = array() ) {
		if(isset($post->ID) && $post->ID === DUMMY_PRODUCT_ID)
		return null;

		$product_type = $this->get_product_type(DUMMY_PRODUCT_ID);

		// Backwards compatibility.
		if ( ! empty( $deprecated ) ) {
			wc_deprecated_argument( 'args', '3.0', 'Passing args to the product factory is deprecated. If you need to force a type, construct the product class directly.' );

			if ( isset( $deprecated['product_type'] ) ) {
				$product_type = $this->get_classname_from_product_type($deprecated['product_type']);
			}
		}

		$classname = $this->get_product_classname(DUMMY_PRODUCT_ID, $product_type);

		return new $classname( is_int($post) ? $post : $post->ID, $deprecated);
	}
	
	public static function get_product_classname( $productID, $product_type) {
		return "Yanak_WC_Product_Simple";
	}
}

class Yanak_WC_Product_Simple extends WC_Product_Simple {
	private $product = null;
	public function __construct($postID = 0, $product = null) {
		if (!$postID) 
			throw new Exception("Yanak_WC_Product_Simple::__construct() - No postID specified.");
		
		
		$this->product = $product; 
		if (empty($this->product)) {
			$this->product = Stocks::get(getStockIDFromPostID($postID));	
		}

		parent::__construct( DUMMY_PRODUCT_ID );
		$this->set_id($postID);
		$this->set_name($this->product->name);
		$this->setPrices();
	}

	public function get_permalink() {
		if($this->product->root_stock_id > 0) {
			return getStockUrl($this->product->root_stock_name, getPostIDFromStockID($this->product->root_stock_id) );
		} else {
			return getStockUrl($this->get_name(), $this->get_id());
		}

	}

	public function is_purchasable() {
		$types = [11, 12, 14];

		if(!is_product()) {
			if(in_array($this->product->stkType, $types) || $this->product->specification == 1) {
				return false;
			}
		}

	
		return true;
	}
	
	public function get_image($size = 'shop_thumbnail', $attr = array(), $placeholder = true) {
		if($this->product->root_stock_id > 0) {
			$id =  getPostIDFromStockID($this->product->root_stock_id);
		} else {
			$id = $this->id;
		}
		Images::$currentID = $id;
		return parent::get_image($size, $attr, $placeholder);
	}


	public function setPrices(){
		if($this->product->stkType === 14 && is_product()) { //АКО Е СТОКАTA E ПAKET ЗА ИЗБОР И СМЕ В СТРАНИЦАТА НА ПРОДУКТА.
			$package = YS_Package::getPackage($this->product->id);
			$this->product->price = $package[0]->price;
			$this->product->basic_price = $package[0]->basic_price;
		}

		$this->set_price($this->product->price);
		$this->set_sale_price($this->product->price);

	  if(isset($this->product->basic_price)) {
			$regular_price = $this->product->basic_price;
		} else  {
			$regular_price = $this->product->price;
		}
		$this->set_regular_price($regular_price);
	}


	public function getProduct(){
		return $this->product;
	}
	


	public function add_to_cart_text() {
		$types = [11, 12, 14];
		if(!is_product()) {
			if(in_array($this->product->stkType, $types)) {
				return 'Преглед';
			}
			else if($this->product->specification == 1) {
				return 'Купи';
			}
		}
		return parent::add_to_cart_text();
	}

	public function get_price_html($deprecated = '' ) {
		if($this->product->stkType === 14 && !is_product()) {
			return '';
		}
		return parent::get_price_html();
	}

	
}