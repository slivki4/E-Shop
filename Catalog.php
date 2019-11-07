<?php

class Catalog implements IYS_Hooks {
	
	private static $params = [];
	private static $price_filter = null;
	private static $features_filters = [];

	public static function initHooks() {
		;
	}

	private static function setDefaultParams($wp_query){
		global $porto_settings;
		$per_page = explode(',', $porto_settings['category-item']);
		self::$params = [
			'current_page' => $wp_query->query_vars['paged'] === 0 ? 1 : $wp_query->query_vars['paged'],
			'posts_per_page' => (isset($_GET['count'])) ? (int)$_GET['count'] : (int)$per_page[0],
			'filter_by_name' => ($wp_query->is_search) ? $wp_query->query['s'] : '',
			'groups' => [],
			'ys_filters' => [],
			'min_price' => -1,
			'max_price' => -1,
			'orderby' => 1,
		];
	}

	private static function setParams($wp_query){
		if (Groups::getCurrent()) {
			self::$params['groups'][] = Groups::getCurrent()->id;
			self::$params['groups'] = array_merge(self::$params['groups'], Groups::getChildren(Groups::getCurrent()->id));
		}

		if($wp_query->query_vars['taxonomy'] === 'pa_ys') {
			self::$params['ys_filters'] = $wp_query->tax_query->queried_terms['pa_ys']['terms'];
		}

		if(isset($wp_query->query_vars['meta_query']['price_filter'])) {
			self::$params['min_price'] = $wp_query->query_vars['meta_query']['price_filter']['value'][0];
			self::$params['max_price'] = $wp_query->query_vars['meta_query']['price_filter']['value'][1];
		}	

		if(isset($_GET['orderby'])) {
			switch ($_GET['orderby']) {
				case 'price': self::$params['orderby'] = 1; break;
				case 'price-desc': self::$params['orderby'] = 2; break;
			}
		}

	}


	public static function get($wp_query) {
		self::setDefaultParams($wp_query);

		if (isset($wp_query->query['post__in'])) {
			$stocks = [];
			foreach($wp_query->query_vars['post__in'] as $key => $val) {
				$stocks[] = Stocks::get(getStockIDFromPostID($val));
			}
		}	else {
				self::setParams($wp_query);
				$result = Stocks::getAll(self::$params);
				$stocks = $result->items;

				self::setPriceFilter($result);
				self::setFeaturesFilters($result);
				
				$wp_query->found_posts = $result->count;
				$wp_query->max_num_pages = ceil($result->count / self::$params['posts_per_page']);
		}
		
		$wp_query->posts = Stocks::makePosts($stocks);
		$wp_query->is_singular = false;
		$wp_query->post_count = count($wp_query->posts);
		$wp_query->query_vars['posts_per_page'] = self::$params['posts_per_page'];
		$wp_query->is_tax = true;

		if (Groups::getCurrent()) {
			$wp_query->queried_object = get_term_by('slug', DUMMY_CATEGORY_SLUG,  'product_cat');
			$wp_query->queried_object->term_id = $wp_query->queried_object->term_taxonomy_id = $wp_query->queried_object_id = (int) Groups::getCurrent()->id;
			$wp_query->queried_object->name = Groups::getCurrent()->name;
		}
		
		$wp_query->set('paged', self::$params['current_page'] );	

		if ($wp_query->found_posts === 1) {
			$wp_query->post = $wp_query->posts[0];
		}
	}


	public static function getPriceFiler(){
		return self::$price_filter;
	}
	
	public static function setPriceFilter($result) {
		self::$price_filter = new stdClass();
		self::$price_filter->min_price = $result->minPrice;
		self::$price_filter->max_price = $result->maxPrice;
	}


	public static function getFeaturesFilters() {
		return self::$features_filters;
	}

	public static function setFeaturesFilters($result){
		self::$features_filters = $result->filters;
	}

}
