<?php

class Hooks implements IYS_Hooks {
	
	
	public static function initHooks (){
		add_action('woocommerce_init', array( __CLASS__, 'woocommerce_init'));	

		add_filter('taxonomy_get_term', array( __CLASS__, 'taxonomy_get_term'), 999, 2);
		add_filter('get_terms', array( __CLASS__, 'get_terms'), 999, 4);
		add_filter('get_ancestors', array( __CLASS__, 'get_ancestors'), 999, 4);
		add_filter('wp_query_query', array( __CLASS__, 'wp_query_query'), 999, 1);	
		add_filter('wp_query_get_posts', array( __CLASS__, 'wp_query_get_posts'), 999, 1);
		add_filter('get_the_terms', array( __CLASS__, 'get_the_terms'), 999, 3);
		add_filter('woocommerce_product_categories_widget_args', array( __CLASS__, 'product_categories_widget_args'), 999, 1);		
		add_filter('woocommerce_product_related_posts_relate_by_category', array(__CLASS__, 'relatedProductsByCategory'), 10, 2);
		add_filter('woocommerce_session_handler',  array(__CLASS__, 'session_Handler'), 999, 5);
//		add_filter( 'woocommerce_quantity_input_args',  array(__CLASS__, 'quantity_input_args'), 10, 2 ); 
//		add_filter( 'woocommerce_stock_amount',  array(__CLASS__, 'woocommerce_stock_amount'), 10, 1 ); 

		add_action('admin_menu', array( __CLASS__, 'remove_menus'));	
		add_action('wp_loaded', array( __CLASS__, 'my_custom_loaded'));	
		add_action('widgets_init',  array( __CLASS__, 'widgets_init') , 15);
		add_action('admin_enqueue_scripts',  array(__CLASS__, 'custom_admin_scripts' ) );
		add_action('get_footer', array(__CLASS__, 'custom_front_scripts') );
		
		remove_action( 'init', array( 'WC_Shortcodes', 'init'));
		add_action( 'init', array('YS_Shortcodes', 'init'));
		
	}

	public static function woocommerce_init() {
		YS_User::initHooks();
		if(!is_null(WC()->cart)) {
			WC()->cart->empty_cart();
			WC()->cart = new YS_Cart();
		}

		if(!is_null(WC()->customer)) 
			WC()->customer = new YS_Customer(get_current_user_id(), false);
		
		WC()->product_factory = new Yanak_WC_Product_Factory();
		WC()->order_factory = new YS_Order_Factory();
	}

	public static function my_custom_loaded() {
		Account::initHooks();
		Stocks::initHooks();
		Catalog::initHooks();
		Product::initHooks();
		Images::initHooks();
		YS_Menus::initHooks();
		YS_Form_Handler::init();
	}

	/**************** REGISTER YS SCRIPTS ****************/

	private static function loadScripts ($params, $js_vars) {
		wp_enqueue_style('yanak-common-css', plugins_url( 'css/yanak-common.css', __FILE__ ));
		wp_register_script('yanak-common', plugins_url('js/yanak-common.js', __FILE__), ['jquery'], '', true);
		wp_enqueue_script('yanak-common');
		wp_localize_script('yanak-common', 'YSCommon', $js_vars);


		wp_register_script($params['name'], $params['path'], $params['depends'], '', $params['in_footer']);
		wp_enqueue_script($params['name']);
		wp_localize_script($params['name'], $params['unique_var'], $js_vars);
	}



	public static function custom_front_scripts(){
		$params = [
			'name' => 'yanak-front',
			'path' => plugins_url('js/yanak-front.js', __FILE__),
			'depends' => ['yanak-common'],
			'in_footer' => true,
			'unique_var' => 'YSFrontEnd'
		];

		$js_vars = [
			'siteURL' => site_url(),
			'ajaxURL' => admin_url('admin-ajax.php'),		
		];

		//wp_enqueue_style('yanak-front-css', plugins_url( 'css/yanak-front.css', __FILE__ ));
		self::loadScripts($params, $js_vars);
	}



	public static function custom_admin_scripts() {
		$params = [
			'name' => 'yanak-admin',
			'path' =>	plugins_url( 'admin/js/yanak-admin.js', __FILE__ ),
			'depends' => ['yanak-common'],
			'in_footer' => true,
			'unique_var' => 'YSBackEnd'
		];
		
		$js_vars = [
			'siteURL' => site_url(),
			'ajaxURL' => admin_url('admin-ajax.php'),		
		];

		wp_enqueue_style('yanak-admin-css', plugins_url( 'admin/css/yanak-admin.css', __FILE__ ));
		self::loadScripts($params, $js_vars);
	}


	
	/**************** REMOVE WOOCOMMERCE MENU FROM ADMIN PANEL ****************/
	public static function remove_menus(){
		if (!current_user_can('manage_network')) {
			remove_menu_page( 'woocommerce' ); //WooCommerce admin menu slug
		}
		remove_submenu_page( 'woocommerce', 'edit.php?post_type=shop_order' );
	}

	public static function get_ancestors($ancestors, $object_id, $object_type, $resource_type) {
		if ($resource_type === 'taxonomy' && $object_type === 'product_cat') {
			$ancestors = Groups::getAncestors($object_id, true);
		}
		return $ancestors;
	}

	public static function get_terms($terms, $taxonomy, $query_vars, $term_query) {
		global $wp_query, $wp_meta_boxes;

		$menu_type = null;
		if(isset($wp_meta_boxes['nav-menus']['side']['default']['add-product_cat']['args']->name)) {
			$menu_type = $wp_meta_boxes['nav-menus']['side']['default']['add-product_cat']['args']->name;
		}

		if ($query_vars['taxonomy'][0] === 'product_cat' && $menu_type !== 'product_cat') {
			$get_terms_as_ids = $query_vars['fields'] === 'ids';
			if (empty($query_vars['object_ids'])) {
				switch(true) {
					case is_array($query_vars['include']): $include_arr = $query_vars['include']; break;
					case !empty($query_vars['include']): $include_arr = explode(',', $query_vars['include']); break;
					default: $include_arr = [];
				}

				$terms = Groups::getMultipleGroups($get_terms_as_ids, $query_vars['parent'], $include_arr);
			} else if (isStockID($query_vars['object_ids'][0]) && !empty($wp_query->post->ys_product_cat)) {
				$stockCategoryId = $wp_query->post->ys_product_cat['id'];
				$groupAncestors = Groups::getAncestors($stockCategoryId, $get_terms_as_ids);

				if ($get_terms_as_ids) {
					$terms = [];
					foreach ($groupAncestors as $groupId) {
						$terms[] = Groups::generateWPTermFromYSGroupId($groupId)->term_id;
					}
				} else {
					$terms =  $groupAncestors;
				}
			}
		} 

		return $terms;
	}
	
	
	public static function wp_query_query($query) {
		if(!empty($query)) {
			if (!empty($query['product_cat'])) {
				//razglejdane na grupa
				$group = explode('-', $query['product_cat']);
				$groupID = (int)end($group);
				Groups::setCurrent(Groups::get($groupID));
	
				$query['ys_product_cat']['id'] = Groups::getCurrent()->id;				
				$query['ys_product_cat']['name'] = Groups::getCurrent()->name;
				$query['product_cat'] = DUMMY_CATEGORY_SLUG;
			} else if ($query['post_type'] === 'product' && !empty($query['product'])) {
				//stranica na product
				$query['yanak_product_name'] = $query['name'];
				$query['name'] = DUMMY_PRODUCT_SLUG;
				$query['product'] = DUMMY_PRODUCT_SLUG;
			} 
			else if ($query['post_type'] === 'product' && empty($query['product'])) {
				//nachalnata stranica na magazina - /shop/
				if(isset($query['fields']) && $query['fields'] === 'ids') {
					if($query['tax_query'][1]['terms'][0]) {
						$query['tax_query'][1]['terms'][0] = DUMMY_CATEGORY_SLUG;
					}
				}
			}
		}

		return $query;
	}

	
	public static function wp_query_get_posts($wp_query) {
		if(is_shop() && $wp_query->query_vars['post_type'] === 'product') {
			switch(true) {
				case $wp_query->is_search :	Catalog::get($wp_query); break; //Търсене на продукти
				default: Catalog::get($wp_query); //Начална страница
			}
		}
		
		else if(is_product_category() && isset($wp_query->query_vars['taxonomy'])) {
			switch($wp_query->query_vars['taxonomy']) {
				case 'product_cat': Catalog::get($wp_query); break; //Категория 
				case 'pa_ys':	Catalog::get($wp_query); break; //Филтри
				default: Catalog::get($wp_query); break; // не е прихванато.
			}
		}
		
		else if(is_product() && $wp_query->is_single && $wp_query->query_vars['post_type'] === 'product') {
			$stock = explode('-', $wp_query->query_vars['yanak_product_name']);
			$stockID = (int)(end($stock));
			Product::get($stockID);	//Единичен продукт		
		}

		else if (isset($wp_query->query_vars['post_type'])) {
			switch($wp_query->query_vars['post_type']) { 
				case 'product': {
					if(count($wp_query->query_vars['post__in']) > $wp_query->query_vars['posts_per_page']) {
						$wp_query->query_vars['post__in']  = array_slice($wp_query->query_vars['post__in'], 0, $wp_query->query_vars['posts_per_page']);
					}
					Catalog::get($wp_query);  break; //ПРИХВАЩА Widget-a за последно разглеждани продукти
				} 
			}
			
		}
		
	}
	
	
	public static function get_the_terms( $array, $int, $type ) {
		global $wp_query;

		if($type === 'product_cat' && !empty($wp_query->post->ys_product_cat)) {
			if (empty($array[0]) || is_int($array[0])) {	// TODO: tova is_int() mai ne e ok da e tuka??
				$array[0] = new stdClass();
			}

			$array[0]->name = $wp_query->post->ys_product_cat['name'];
			$array[0]->slug = ys_generate_group_slug($wp_query->post->ys_product_cat['name'], $wp_query->post->ys_product_cat['id']);
			$array[0]->term_id = YS_TERM_PREFIX + $wp_query->post->ys_product_cat['id'];
			$array[0]->taxonomy = 'product_cat';
		}

		return $array; 
	}	
	
	
	public static function taxonomy_get_term($_term, $term) {
		 // id-tata na grupite ot api-to shte gi puskame s YS_TERM_PREFIX + id, che da se razlichavat ot id-tata na wordpress terms
		if ( is_int($term) && $term > YS_TERM_PREFIX ) {
			$_term = WP_Term::get_instance(DUMMY_CATEGORY_ID);
			$current_group = Groups::get($term - YS_TERM_PREFIX);
			$_term->slug = ys_generate_group_slug($current_group->name, $current_group->id);
			$_term->name = $current_group->name;
			return $_term;
		} 
		return $_term;		
	}


	public static function product_categories_widget_args($args) {
		if( Groups::getCurrent()) 
			$args['current_category'] = YS_TERM_PREFIX + Groups::getCurrent()->id;

		return $args;
	}
	

	public static function relatedProductsByCategory($true, $product_id){
		return false;
	}


	public static function session_Handler(){
		return 'YS_Session_Handler';
	}


	function widgets_init() {
		$widgets = [
			'SShop_Widget_Slider' => 'YS_SShop_Widget_Slider',
			'WC_Widget_Layered_Nav' => 'YS_Features_Filters',
			'WC_Widget_Price_Filter' => 'YS_Price_Filter'
		];

		foreach($widgets as $key => $value) {
			if (class_exists($key)) {
				unregister_widget($key);
				require_once(realpath(__DIR__.'/widgets/'.$value.'.php'));
				register_widget($value);
			}
		}
	}

	public static function quantity_input_args($args, $product) {
		$product = Stocks::get(getStockIDFromPostID($product->id));
		if($product->broima === false) {
			$args['min_value'] = 0.1;
			$args['step'] = 0.1;
			$args['inputmode'] = 'floatval';
			unset($args['pattern']);
		}
		return $args; 
	}

	public function woocommerce_stock_amount($quantity) {
		$product = Stocks::get(getStockIDFromPostID($_REQUEST['add-to-cart']));
		if($product->broima === false) {
			$quantity = floatval(number_format($_REQUEST['quantity'], 2) );
		};
		return $quantity;
	}
	
	
}


