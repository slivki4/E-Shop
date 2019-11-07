<?php

class Stocks implements IYS_Hooks{
	private static $cache = [];
	private static $current = null;

	
	public static function initHooks() {
		// TODO: ima li smisul da pusne edna zaqvka da iztegli i cache-ira vsichki produkti v nachaloto?
		//self::getAll([], 1, 1000);
	}	
	
	public static function getAll($input) {
		$params = [
			//'warehouseID' => YS_Settings::instance()->get('warehouseID'),
			'groups' => $input['groups'],
			'filters' => $input['ys_filters'],
			'filterByName' => $input['filter_by_name'],
			'currentPage' => $input['current_page'],
			'itemsPerPage' => $input['posts_per_page'],
			'countItems' => true,
			'showFilters' => true,
			'orderBy' => $input['orderby'],
			'minPrice' => $input['min_price'],
			'maxPrice' => $input['max_price'] 
		];

		$stocks = YanakAPI::instance()->apiRequest('stocks', 'GET',  $params);

		foreach ($stocks->items as $item) {
			self::$cache[$item->id] = $item;
		}
		return $stocks;
	}
	
	
	public static function get($stockID) {
		if (empty(self::$cache[$stockID])) {
			$result = YanakAPI::instance()->apiRequest('stock', 'GET', [
				'stockID' => $stockID,
				//'show_possible_filters' => 1
			]);
			 
			self::$cache[$stockID] = $result->stock;
		}
		
		return self::$cache[$stockID];
	}
	

	public static function getCurrent() {
		return self::$current;
	}	

	public static function setCurrent($stockID) {
		self::$current = Stocks::get($stockID);
	}

	
	public static function makePosts($items) {
		$posts = [];
		foreach ($items as $item) {
			$post = WP_Post::get_instance(DUMMY_PRODUCT_ID);	// TODO: no need to call db??
			if(strlen($item->name) > 100) {
				$item->name = mb_substr(($item->name.'...'), 0, 72).'...';
			}
			$post->ID = getPostIDFromStockID($item->id);
			$post->post_title = ys_generate_product_name($item->name);
			$post->post_content = 'Whatever you want here. Maybe some cat pictures....';
			$post->post_excerpt = $post->post_title;
			$post->post_status = 'publish';
			$post->comment_status = 'closed';
			$post->ping_status = 'closed';
			$post->post_name = ys_generate_product_slug($post->post_title, $post->ID);
			$post->post_type = 'product';
			$post->filter = 'raw';
			if (Groups::getCurrent()) {
				$post->ys_product_cat = [
					'id' => Groups::getCurrent()->id,
					'name' => urldecode(Groups::getCurrent()->name)
				];
			}	
			$posts[] = $post;
		}
		return $posts;
	}
	
}
