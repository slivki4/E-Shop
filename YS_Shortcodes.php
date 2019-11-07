<?php 

class YS_Shortcodes extends WC_Shortcodes {

	public static function init() {
		parent::init();
		remove_shortcode('woocommerce_my_account');
		remove_shortcode('product_category');
		remove_shortcode('product');
		remove_shortcode('woocommerce_checkout');

		add_shortcode('woocommerce_my_account', __CLASS__ . '::my_account');
		add_shortcode('product_category', __CLASS__ . '::product_category');
		add_shortcode('product', __CLASS__ . '::product');
		add_shortcode('woocommerce_checkout', __CLASS__ . '::checkout');

		add_filter('vc_autocomplete_product_id_callback',  array(__CLASS__, 'product_admin_autocomplete'), 999, 3);
	}


	/**
	 * Display a single product.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function product( $atts ) {
		if ( empty( $atts ) ) {
			return '';
		}
		
		$atts['skus']  = isset( $atts['sku'] ) ? $atts['sku'] : '';
		$atts['ids']   = isset( $atts['id'] ) ? $atts['id'] : '';
		$atts['limit'] = '1';

		require_once(realpath(__DIR__.'/YS_Shortcode_Products.php'));
		$shortcode = new YS_Shortcode_Products( (array) $atts, 'product' );

		return $shortcode->get_content();
	}

	

	public static function product_admin_autocomplete($query, $tag, $param_name) {
		$query = vc_post_param('query');
		$stockID = getStockIDFromPostID($query);

		if($stock = Stocks::get($stockID)) {
			return [
				0 => ['value' => $query, label => "Id: ".$query." - Заглавие: ".$stock->name]
			];
		}
		return [];

	}

	/**
	 * List products in a category shortcode.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function product_category( $atts ) {
		if ( empty( $atts['category'] ) ) {
			return '';
		}

		$atts = array_merge( array(
			'limit'        => '12',
			'columns'      => '4',
			'orderby'      => 'menu_order title',
			'order'        => 'ASC',
			'category'     => '',
			'cat_operator' => 'IN',
		), (array) $atts );

		require_once(realpath(__DIR__.'/YS_Shortcode_Products.php'));
		$shortcode = new YS_Shortcode_Products( $atts, 'product_category' );
		
		return $shortcode->get_content();
	}



		/**
	 * My account page shortcode.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function my_account( $atts ) {
		ys_get_notice();
		return self::shortcode_wrapper( array( 'YS_Shortcode_My_Account', 'output' ), $atts );
	}



	/**
	 * Checkout page shortcode.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function checkout( $atts ) {
		return self::shortcode_wrapper( array( 'YS_Shortcode_Checkout', 'output' ), $atts );
	}


}