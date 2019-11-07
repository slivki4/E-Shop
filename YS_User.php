<?php 

class YS_User implements IYS_Hooks {

	protected static $user_data = null;

	public static function initHooks() {
		add_action('wp_logout', array(__CLASS__, 'logout'), 20);
		add_filter( 'woocommerce_login_redirect', array(__CLASS__, 'loginRedirect'), 999, 2);
		add_filter('update_user_metadata', array(__CLASS__, 'updateMetadata'), 999, 5);

		if(self::hasUserSession()) self::setUserData();			
	}

	public static function getUserData() {
		return self::$user_data;
	}

	public static function setUserData($data = null){
		if($data) $_SESSION['ys_user_data'] = $data;
		self::$user_data = $_SESSION['ys_user_data'];
	}

	public static function hasUserSession(){
		return (bool)isset($_SESSION['ys_user_data']);
	}

	public static function isLoggedIn() {
		return isset(self::$user_data->id);
	}

	public static function getID(){
	 return self::isLoggedIn() ? self::getUserData()->id : 0;
	}
	
	public static function loginRedirect($redirect, $user){
		return wc_get_page_permalink('shop');
	}
		
	public static function get_user() {
		$user = _wp_get_current_user();
		if (self::$user_data && !$user->set_ys_data) {
			$user->set_ys_data		= true;
			$user->user_email				= self::$user_data->email;
			$user->full_name				= self::$user_data->full_name;
			$user->display_name			= self::$user_data->full_name;
			$user->user_nicename		= self::$user_data->full_name;
			$user->phone 						= self::$user_data->phone;
			$user->delivery_address = self::$user_data->delivery_address;
			$user->company_name			= self::$user_data->company_name;
			
			$user->objects = self::$user_data->objects;
			clean_user_cache($user);
			update_user_caches($user);
		}
		return $user;
	}
	
	
	public static function logout() {
		unset($_SESSION['ys_user_data']);
		self::$user_data = null;
	}

	public static function updateMetadata($check, $object_id, $meta_key, $meta_value, $prev_value ){
		if($meta_key === "_woocommerce_persistent_cart_".get_current_blog_id()) {
			return true;
		}
	}

};