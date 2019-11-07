<?php 

class YS_Shortcode_Products extends WC_Shortcode_Products {

	protected function get_query_results() {
		$transient_name = $this->get_transient_name();
		$cache          = wc_string_to_bool( $this->attributes['cache'] ) === true;
		$results = $cache ? get_transient( $transient_name ) : false;


		switch($this->type) {
			case "product" : $ys_method = 'YSGetProcuct'; break;
			case "product_category" : $ys_method = 'YSGetProcuctsCategory'; break;		
		}

		if(!empty($results)) {
			$results->ids = $this->{$ys_method}($results->ids);
		}

		if ( false === $results ) {
			if ( 'top_rated_products' === $this->type ) {
				add_filter( 'posts_clauses', array( __CLASS__, 'order_by_rating_post_clauses' ) );
				$query = new WP_Query( $this->query_args );
				remove_filter( 'posts_clauses', array( __CLASS__, 'order_by_rating_post_clauses' ) );
			} else {
				if($ys_method === 'YSGetProcuct') {
					$this->query_args['p'] = DUMMY_PRODUCT_ID;
				}
				$query = new WP_Query( $this->query_args );
			}

			$paginated = ! $query->get( 'no_found_rows' );

			$results = (object) array(
				'ids'          =>  wp_parse_id_list( $this->{$ys_method}($query->posts) ),
				'total'        => $paginated ? (int) $query->found_posts : count( $query->posts ),
				'total_pages'  => $paginated ? (int) $query->max_num_pages : 1,
				'per_page'     => (int) $query->get( 'posts_per_page' ),
				'current_page' => $paginated ? (int) max( 1, $query->get( 'paged', 1 ) ) : 1,
			);

			if ( $cache ) {
				set_transient( $transient_name, $results, DAY_IN_SECONDS * 30 );
			}
		}
		// Remove ordering query arguments which may have been added by get_catalog_ordering_args.
		WC()->query->remove_ordering_args();
		return $results;
	}


	private function YSGetProcuct($data) {
		return [0 => $this->get_attributes()['ids']];
	}


	private function YSGetProcuctsCategory($data){
		$group = explode('-',  $this->get_attributes()['category']);
		$groupID = (int)end($group);
		$stocks = Stocks::getAll([
			'current_page' => 1,
			'posts_per_page' => 10,
			'filter_by_name' => "",
			'groups' => [$groupID],
			'ys_filters' => [],
			'min_price' => -1,
			'max_price' => -1,
			'orderby' => 0
		]);

		if(!empty($stocks->rows)) {
			$data = [];
			foreach($stocks->rows as $val) 
				$data[] = getPostIDFromStockID($val->id);
		}

		return $data;
	} 


}