<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YS_Price_Filter extends WC_Widget_Price_Filter {
  public function __construct() {
		parent::__construct();
  }

  
	protected function get_filtered_price() {
		return Catalog::getPriceFiler();
	}

  
	

}