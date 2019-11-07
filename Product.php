<?php

class Product implements IYS_Hooks{
	
	public static $ys_tabs = [];

	public static function initHooks(){
		add_filter( 'woocommerce_product_tabs', array( __CLASS__, 'showTabs'), 999, 1);	
		add_filter( 'woocommerce_get_product_attributes',  array(__CLASS__, 'get_attributes'), 10, 3 );
		add_filter( 'woocommerce_add_to_cart_redirect',  array(__CLASS__, 'add_to_cart_redirect'), 10, 1 );
		add_action( 'woocommerce_before_add_to_cart_button', array(__CLASS__, 'before_add_to_cart_button'), 5 );
		add_action( 'woocommerce_after_add_to_cart_button',  array(__CLASS__, 'after_add_to_cart_button'),  10, 0 );
	}


	public static function get($postID) {
		global $wp_query;
		global $post;
		
		$stockID = getStockIDFromPostID($postID);
		Stocks::setCurrent($stockID);
		$stock = Stocks::getCurrent();
		Groups::setCurrent(Groups::get($stock->group_id));
		
		$wp_query->queried_object = get_term_by('slug', DUMMY_CATEGORY_SLUG,  'product_cat');
		$wp_query->queried_object->post_type = 'product';
		$wp_query->queried_object->post_title = $stock->name;
		//$wp_query->queried_object->taxonomy = 'product';
		
		
		$post = WP_Post::get_instance(DUMMY_PRODUCT_ID);	// TODO: nqma nujda ot db call?
		$post->ID = $postID;
		$post->post_title = $stock->name;
		$post->post_content = $stock->note;
		$post->post_excerpt = $stock->note;
		$post->post_status = 'publish';
		$post->comment_status = 'closed';
		$post->ping_status = 'closed';
		$post->post_name = $post->post_title;
		$post->post_type = 'product';
		$post->filter = 'raw';
		$post->ys_stock_basic_price = $stock->basic_price;		
		$post->ys_stock_price = $stock->price;
		$post->ys_product_cat = [
			'id' => Groups::getCurrent()->id,
			'name' => Groups::getCurrent()->name
		];		
		$wp_query->posts[0] = $wp_query->post = $post;
		$wp_query->is_single = true;

//	$wp_query->post->post_type = 'product';
//	$wp_query->post->post_title = $this->stocks->current->name;
//	$wp_query->post->ID = DUMMY_PRODUCT_ID;
//	$wp_query->post->stockID = $stockID;
//	echo '<pre>'.print_r($wp_query, true).'</pre>';
	}
	
	
	public static function getTab($tabID) {
		$result = YanakAPI::instance()->apiRequest('stock/tab', 'GET', [
			'id' => $tabID, 
		]);

		return $result->item;
	}

	public static function setTab() {
		$stockID = (int)$_POST['stock_id'];
		$tabID = (int)$_POST['tab_id'];
		$tagID = (int)$_POST['tag_id'];
		$title =  sanitize($_POST['tab_title'], 'trim|htmlspecialchars');
		$content = sanitize($_POST['content'], 'trim|htmlspecialchars');

		$result = YanakAPI::instance()->apiRequest('stock/tab', 'POST', [
			'id' => $tabID, 
			'tag' => $tagID,
			'name' => $title,
			'description' => $content,
			'stockID' => $stockID
		]);
		return $result->item;
	}

	public static function removeTab($tabID) {
		$result = YanakAPI::instance()->apiRequest('stock/tab', 'DELETE', [
			'id' => $tabID
		]);
		return $result;
	}


	public static function getTabs($stockID, $getAll = 0) {
		$result = YanakAPI::instance()->apiRequest('stock/tabs', 'GET', [
			'stockID' => $stockID,
			'description' => $getAll
		]);
		if(!empty($result->items)) {
			return $result->items;
		} 
		return false;
	}


	public static function showTabs($tabs) {
		if(isset($tabs['description'])) {
			unset($tabs['description']);
		}

		$stock = Stocks::getCurrent();	
		self::$ys_tabs = self::getTabs($stock->id, 1);

		if(!empty(self::$ys_tabs)) {
			foreach(self::$ys_tabs as $key => $tab) {
				$tabs[] = [
					'title' => $tab->name,
					'priority' => 20,
					'callback' => function ($key) {
						$content = stripcslashes(htmlspecialchars_decode(self::$ys_tabs[$key]->description));
						$content = apply_filters( 'the_content', $content );
						$content = str_replace( ']]>', ']]&gt;', $content );
						echo $content;
				}];
			}
		}

    return $tabs;		
	}


	public static function get_attributes($array, $instance) {
		$stock = Stocks::getCurrent();
		
		foreach($stock->parametters_info as $k => $v) {
			foreach($v->items as $k2 => $v2) {
				if(!$v2->val) continue;
				$attribute = new WC_Product_Attribute();
				$attribute->set_id(0);
				$attribute->set_name($v2->name);
				$attribute->set_options( [$v2->val] );
				$attribute->set_position( 0 );
				$attribute->set_visible( true );
				$attribute->set_variation( false );
				$attributes[] = $attribute;
			}
		}
		return $attributes;
	}

	public static function add_to_cart_redirect($url){
		return wc_get_cart_url();
	}


	public function before_add_to_cart_button() {
		$product = Stocks::getCurrent();
		$type = [11, 12];
		if(in_array($product->stkType, $type)) {
			$sizes = YS_Clothes::getSizes($product->id);	
			require_once(YS_PLUGIN_DIR.'/'.WC()->template_path().'single-product/clothes.php');
		}
		
		else if($product->stkType === 14){
			$packages = YS_Package::getPackage($product->id);
			$additions = YS_Package::getAdditions($product->id); 
			require_once(YS_PLUGIN_DIR.'/'.WC()->template_path().'single-product/package.php');
		}
	}

	public static function after_add_to_cart_button() {
		$product = Stocks::getCurrent();
		if($product->specification === 1) {
			echo '<input type="hidden" name="ys_fiscal_product" value="true" />';
		}
	
		else if($product->stkType === 14){
			echo '<input type="hidden" name="ys_additions" value="" />';
		}
	}

}


