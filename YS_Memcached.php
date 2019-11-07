<?php 

class YS_Memcached {
  
  private static $instance = null;
  private $memcached = null;
  private $cachedKey = null;

/*
	 *	Контролери, които позволяват кеширане. Трябва много внимателно тука, че може да изгърмим лошо.
	 *	Трябва да са само контролери за четене от базата и нищо, което да пише в нея (добавяне на стока в баскета и тн).
	 *	Като се добави нов read-only контролер трябва да се добави в този масив, за да се кешира.
	 */
	private $cachableControllers = [
		// 'ysols_get_stocks_groups',
		// 'ysols_get_stocks_info',
    // 'ysols_get_stock_info',
    

    
		//'ysols_get_customer',	// TODO: da se pushtat?
		//'ysols_get_customer_id'
	];

	/*
	 *	Контролери, които като се викнат ще изчистят кеша за закачените към тях контролери
	*/
	private $cacheInvalidators = [
		'ysols_add_basket' => [
			'ysols_show_stocks_basket'
		],
		'ysols_basket_change_quantity' => [
			'ysols_show_stocks_basket'
		],
		'ysols_delete_basket' => [
			'ysols_show_stocks_basket'
		],
		'ysols_make_order' => [
			'ysols_show_stocks_basket'
		]
	];

  private function __construct(){
    if (class_exists('Memcached')) {
			$this->memcached = new Memcached;
			if ($this->memcached->addServer("localhost", 11211) === false || !isset($this->memcached->getStats()["localhost:11211"])) {
				$this->memcached = null;
			}
		}

		if (empty($this->memcached)) {
			throw new Error("Memcached is not available.");
    }
  }


  public static function instance(){
    if(self::$instance === null) {
      self::$instance = new YS_Memcached();
    }
    return self::$instance;
  }



  public function getToken(){
    $key = 'token-'.get_option('ys_email');
    return $this->memcached->get($key);
  }


  public function setToken($data) {
    $key = 'token-'.get_option('ys_email');
    $this->memcached->set($key, $data, MEMCACHED_VALIDATE_STRING_EXPIRE);
  }


  public function hasToken(){
    return (bool)$this->getToken();
  }

  public function deleteToken(){
    $this->memcached->delete('token-'.get_option('ys_email'));
  }


  public function cacheAllowed($controller) {
    if(!empty($this->memcached) && in_array($controller, $this->cachableControllers)) {
      $cacheAllowed = true;
    } else {
      $cacheAllowed = false;
    }

    if(!empty($this->memcached) && array_key_exists($controller, $this->cacheInvalidators)) {
			$this->deleteCacheForControllers($this->cacheInvalidators[$controller]);
    }

    return $cacheAllowed;
  }

  public function getCachedKey(){
    return $this->cachedKey;
  }

  public function setCachedKey ($controller, $input){
    $this->cachedKey = $controller.'-'.md5(json_encode($input));	//TODO: tova pravi md5() na mnogo golqm text - shte tovari???
  }


  public function getCachedData() {
    return $this->memcached->get($this->getCachedKey());
  }


  public function setCachedData($result) {
    $this->memcached->set($this->getCachedKey(), $result, MEMCACHED_CONTROLLERS_EXPIRE);
  }

  public function deleteCachedData() {
    $this->memcached->delete($this->getCachedKey());
  }


  public function getCachableControllers (){
    return $this->cachableControllers;
  }

	public function deleteCacheForControllers($controllers) {
		$keys = $this->memcached->getAllKeys();
		foreach ($keys as $key) {
			foreach ($controllers as $controller) {
				if (substr($key, 0, strlen($controller)) === $controller) {
					$this->memcached->delete($key);
				}
			}
		}
	}


}