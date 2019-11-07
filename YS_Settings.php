<?php 

class YS_Settings  { 

  private static $instance = null;
  private $settings = [];

	private function __construct() {

  }
  
	public static function instance() {
		if (self::$instance === null) {
			self::$instance = new YS_Settings();				
		}
		return self::$instance;
	}


  public function get($key = null){
    if($key && array_key_exists($key, $this->settings)) {
      return $this->settings[$key];
    }
    return $this->settings;
  }


}