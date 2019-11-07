<?php
	/*
	Plugin Name: YanakStore
	Plugin URI: http://eyanak.com
	Description: YanakStore settings
	Version: 1.0
	Author: YanakSoft
	Author URI: http://yanaksoft.com
	License: GPL2
	*/

	if (class_exists('woocommerce')){ // WooCommerce is activated
		define('YS_PLUGIN_DIR', WP_PLUGIN_DIR . '/yanakstore');
		define('DUMMY_PRODUCT_ID', 4);
		define('DUMMY_PRODUCT_SLUG', 'test-product');
		define('DUMMY_IMAGE_ID', 12);
		define('DUMMY_CUSTOMER_USERNAME', 'dummycustomer');
		define('DUMMY_CUSTOMER_PASSWORD', 'U)8BC6ysc#)As8HENMN^MMtA');
		define('YS_TERM_PREFIX', 90000);
		define('YS_POSTID_PREFIX', 100000);
		define('IMAGES_DIR', WP_CONTENT_DIR.'/uploads/sites/'.get_current_blog_id().'/images/');
		define('MEMCACHED_VALIDATE_STRING_EXPIRE', 60 * 60 * 24);
		define('MEMCACHED_CONTROLLERS_EXPIRE', 60 * 10);
	
		define('DUMMY_CATEGORY_ID', (int)get_option('default_product_cat'));
		$terms = get_terms([
			'get'											=> "all",
			"number"									=> 1,
			"update_term_meta_cache"	=> false,
			"orderby"									=> "none",
			"suppress_filter" 				=> true,
			"term_taxonomy_id"				=> DUMMY_CATEGORY_ID
		]);
		
		define('DUMMY_CATEGORY_SLUG', $terms[0]->slug);
	
		require_once("IYS_Hooks.php");
		require_once("YanakAPI.php");
		require_once("YS_Memcached.php");
		require_once("YS_Rest_Server.php");
		require_once("Validator.php");
		require_once("YS_Settings.php");
		require_once("Hooks.php");
		require_once("YS_User.php");
		require_once("Account.php");
		require_once("YS_Customer.php");
		require_once("YS_Cart.php");
		require_once("YS_Session_Handler.php");
		require_once("YS_Checkout.php");
		require_once("YS_Order.php");
		require_once("YS_Order_Factory.php");
		require_once("Catalog.php");	
		require_once("Groups.php");
		require_once("Stocks.php");
		require_once("Product.php");
		require_once("Image.php");
		require_once("Images.php");
		require_once("functions.php");
		require_once("admin/YS_Admin.php");
		require_once("wc-classes-overrides.php");
		require_once("YS_Menus.php");
		require_once("YS_Shortcodes.php");
		require_once("YS_Form_Handler.php");
		require_once("YS_Shortcode_My_Account.php");
		require_once("YS_Shortcode_Checkout.php");
		require_once("YS_Fiscal.php");
		require_once("YS_Package.php");
		require_once("YS_Clothes.php");
		require_once("admin/transactions/Transactions.php");
		require_once("admin/fiscal-server/FiscalServer.php");
		require_once("emails/YS_Emails.php");

	if (session_status() == PHP_SESSION_NONE) {
			session_start();
	}
		Hooks::initHooks();
	}