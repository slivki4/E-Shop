<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YS_Features_Filters extends WC_Widget_Layered_Nav {
	private $ys_filters = [];
	
	public function __construct() {
		parent::__construct();
  }
	
	protected  function layered_nav_list($terms, $taxonomy, $query_type) {
		global $wp;
		if (empty(Catalog::getFeaturesFilters())) {
			return false;
		}

		$filter_name    = 'filter_' . sanitize_title( str_replace( 'pa_', '', $taxonomy ) );
		$current_filters = isset( $_GET[ $filter_name ] ) ? explode( ',', wc_clean( wp_unslash( $_GET[ $filter_name ] ) ) ) : array();
		$current_filters = array_map( 'sanitize_title', $current_filters );

		if(!empty($current_filters )) {
			foreach($current_filters as $val) {
				$this->ys_filters[$val] = (int)$val;
			}
		}

	
		$html = '<div id="ys-filters">';
    foreach(Catalog::getFeaturesFilters() as $sections) {
      foreach ($sections->parametters as $filters ) {		
				$html .='<div>';
				$html .='<strong>'.$filters->name.'</strong>';
				$html .='<ul class="woocommerce-widget-layered-nav-list">';		
				foreach($filters->parametters as $parametter) {
					$html .='<li class="woocommerce-widget-layered-nav-list__item wc-layered-nav-term">';
					$html .= $this->createLink($parametter, $taxonomy);
					$html .='</li>';
				}
				$html .='</ul>';
				$html .='</div>';
			}
		}
		
		$html .='</div>';
		echo $html;
		return true;
	}

	protected function createLink($parametter, $taxonomy){
		$output = '';
		$checked = '';
		$filter_name	= 'filter_' . sanitize_title( str_replace( 'pa_', '', $taxonomy ) );

		$group = Groups::getCurrent();
		$slug = ys_generate_group_slug($group->name, $group->id);

		$link = remove_query_arg( $filter_name, $this->get_current_page_url() );
		$link = str_replace("ys_categories", $slug, $link);

		if(empty($this->ys_filters)) {
			$link = add_query_arg( $filter_name, $parametter->id, $link );
		}	else if(array_key_exists($parametter->id, $this->ys_filters)){
			if(count($this->ys_filters) > 1) {
				$new_filters = $this->ys_filters;
				unset($new_filters[$parametter->id]);
				$link = add_query_arg( $filter_name, implode( ',', $new_filters ), $link );
			}
			$checked = 'checked';
		} else {
			$new_filters = $this->ys_filters;
			array_unshift($new_filters, $parametter->id);
			$link = add_query_arg( $filter_name, implode( ',', $new_filters ), $link );
		}

		$output .='<a href="'.$link.'">';
		$output .='<input id="'.$parametter->id.'" type="checkbox" '.$checked.' />';
		$output .='<span>'.$parametter->name.'</span></a>';
		$output .='</a>';
		return $output;
	}

}