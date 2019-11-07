<?php

// RETURN HTTP STATUS CODE
function getHttpCode($http_response_header) {
	if(is_array($http_response_header)) {
		$parts=explode(' ',$http_response_header[0]);
		if(count($parts)>1) //HTTP/1.0 <code> <text>
			return intval($parts[1]); //Get code
	}
	return 0;
}

// kakvi kriterii za sortirane da pokazva na stranicite na grupite
if(!function_exists('woocommerce_catalog_ordering'))  {
	function woocommerce_catalog_ordering() {
		global $wp_query;
 
	 if ( 1 === (int) $wp_query->found_posts || ! woocommerce_products_will_display() || $wp_query->is_search() ) {
		 return;
	 }
	
	 $orderby                 = isset($_GET['orderby']) ? wc_clean($_GET['orderby']) : apply_filters('woocommerce_default_catalog_orderby', get_option('woocommerce_default_catalog_orderby'));
	 $show_default_orderby    = 'menu_order' === apply_filters('woocommerce_default_catalog_orderby', get_option('woocommerce_default_catalog_orderby'));
	 $catalog_orderby_options = apply_filters('woocommerce_catalog_orderby', array(
		 'menu_order' => __('Default sorting', 'woocommerce' ),
		 //'popularity' => __('Sort by popularity', 'woocommerce' ),
		 //'rating'     => __('Sort by average rating', 'woocommerce' ),
		 //'date'       => __('Sort by newness', 'woocommerce' ),
		 'price'      => __('Sort by price: low to high', 'woocommerce' ),
		 'price-desc' => __('Sort by price: high to low', 'woocommerce' ),
	 ));
 
	 if ( ! $show_default_orderby ) {
		 unset( $catalog_orderby_options['menu_order']);
	 }
 
	 if ('no' === get_option('woocommerce_enable_review_rating' ) ) {
		 unset( $catalog_orderby_options['rating']);
	 }
 
	 wc_get_template('loop/orderby.php', array('catalog_orderby_options' => $catalog_orderby_options, 'orderby' => $orderby, 'show_default_orderby' => $show_default_orderby ));
 	}
}


/****************	OVVERIDE wp_get_current_user ****************/
if(!function_exists('wp_get_current_user'))  {
	function wp_get_current_user() {
		return YS_User::get_user();
	}
}


function ys_generate_slug($name, $id) {
	$slug = mb_strtolower($name) . '-' . $id; 
	$slug = str_replace('/', '-', $slug);
	$slug = str_replace(' ', '-', $slug);

	// ако се съберат повече от две - едно до друго, да ги маха
	$slug = preg_replace('/\-{2,}/', '-', $slug);

	return htmlspecialchars(urlencode($slug));
}

function ys_generate_product_slug($name, $id) {
	return ys_generate_slug($name, $id);
}

function ys_generate_group_slug($name, $id) {
	return ys_generate_slug($name, $id);
}


function ys_generate_product_name ($name){
	if(strlen($name) < 72) 
		return $name;
	return mb_substr(($name.'...'), 0, 72).'...';
}


function getStockUrl($stockName, $stockId) {
	return get_site_url(null, '/product/' . ys_generate_product_slug($stockName, $stockId) . '/');
}

function getStockIDFromPostID($postID) {
	return $postID - YS_POSTID_PREFIX;
}

function getPostIDFromStockID($postID){
	return $postID + YS_POSTID_PREFIX;
}

function isStockID($postID) {
	if (is_int($postID) && $postID > YS_POSTID_PREFIX) {
		return true;
	}

	return false;
}


function isYSProduct() {
	global $product;
	return (is_object($product) && $product instanceof Yanak_WC_Product_Simple); 
}

 function sanitize($data, $types){
		$types=  explode('|', $types);
		if(is_array($types)){
			foreach($types as $v){
				if($v=='int'){
					$data=(int)$data;
				}
				if($v=='float'){
					$data=(float)$data;
				}
				if($v=='double'){
					$data=(double)$data;
				}
				if($v=='bool'){
					$data=(bool)$data;
				}
				if($v=='string'){
					$data=(string)$data;
				}
				if($v=='trim'){
					$data=trim($data);
				}
				if($v=='stripslashes') {
					$data = stripslashes($data);
				}
				if($v=='htmlspecialchars') {
					$data = htmlspecialchars($data);
				}
				if($v=='addslashes') {
					$data = addslashes($data);
				}
				if($v=='htmlentities') {
					$data = htmlentities($data, ENT_QUOTES);
				}		
				if($v=='replacequotes')		{
					$data = str_replace('"', '', $data);
				}
			}
		}
		return $data;
	}
	

	function getUserIP(){
    // Get real visitor IP behind CloudFlare network
    if(isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
      $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
			$_SERVER['HTTP_CLIENT_IP'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
    }
    $client  = @$_SERVER['HTTP_CLIENT_IP'];
    $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
    $remote  = $_SERVER['REMOTE_ADDR'];

    if(filter_var($client, FILTER_VALIDATE_IP)){
      $ip = $client;
    } elseif(filter_var($forward, FILTER_VALIDATE_IP)){
      $ip = $forward;
    } else{
    	$ip = $remote;
    }
    return $ip;
	}


	function ys_set_notice($type, $text){
		$_SESSION['ys_notice'] = ['type' => $type, 'text' => $text];
	}

	function ys_get_notice() {
		if(	$_SESSION['ys_notice']) {
			wc_add_notice($_SESSION['ys_notice']['text'], $_SESSION['ys_notice']['type']);
			unset($_SESSION['ys_notice']);
		}
	}