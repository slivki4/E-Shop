<?php

final class YanakAPI {
	private static $instance = null;
	private $validateString = null;
	private $browserSessionID = null;
	private $memcached = null;
	

	private function __construct() {
		if(!YS_Memcached::instance()->hasToken()) {
			$this->setToken();
		}
	}


	public static function instance() {
		if (self::$instance === null) {
			self::$instance = new YanakAPI();				
		}
		return self::$instance;
	}


	private function setToken(){
		$result = $this->apiRequest('login', 'POST', [
			'email' => get_option('ys_email'),
			'username' => get_option('ys_username'),
		]);
		YS_Memcached::instance()->setToken($result->token);	
	}


	public function apiRequest($controller, $method, $input = [], $retryCount = 0) {
		if($cacheAllowed = YS_Memcached::instance()->cacheAllowed($controller)){
			YS_Memcached::instance()->setCachedKey($controller, $input);
			if(!empty($cachedData = YS_Memcached::instance()->getCachedData())) {
				return json_decode($cachedData);
			}
		}
		$url = 'http://94.156.153.21:50980/e-shop/api/' . $controller;
	//	$url = 'https://eyanak.com//e-shop/api/' . $controller;

		$opts = [
			'http' => [
				'method'  => $method,
				'header'  => ['Content-Type: application/json'],
				'content' => json_encode($input),
				'timeout' => 30000,
				'ignore_errors' => true
			]
		];
		
		if (YS_Memcached::instance()->hasToken()) {
			$opts['http']['header'][] = "Authorization: Bearer ".YS_Memcached::instance()->getToken();		
		}

		$context = stream_context_create($opts);
		$result =  file_get_contents($url, false, $context);

		if(getHttpCode($http_response_header) === 401) { // 401 Unauthorized
			YS_Memcached::instance()->deleteToken();
			$this->setToken();
			$this->retryRequest($controller, $method, $input, ++$retryCount, $cacheAllowed);
		}

		else if($controller !== 'login' && !$this->validate($result)) {
			return $this->retryRequest($controller, $method, $input, ++$retryCount, $cacheAllowed);
		}
		
		if($cacheAllowed) {
			YS_Memcached::instance()->setCachedData($result);	
		}

		return json_decode($result);
	}



	private function validate($result) {
		if ($result === FALSE) {
			throw new Error("Грешка при връзката с база данни!");
		}



		$result = json_decode($result);

		if($result->error) {
			$error_msg = '';
			if(isset($result->error_code) && $result->error_code > 0) {
				if($result->error_code === 106) {
					return false;
				}
				$error_msg .= 'Грешка '.$result->error_code.': ';
			}
			$error_msg .= $result->error;
			throw new Exception( __( $error_msg, 'woocommerce' ) );
		}
		return true;
	}

	private function retryRequest($controller, $method, $data, $retryCount, $cacheAllowed) {
		if ($cacheAllowed) {
			YS_Memcached::instance()->deleteCachedData();
		}

		usleep(100000);
		if ($retryCount > 30) {
			throw new Error("Невалиден token");
		}

		$result = $this->apiRequest($controller, $method, $data, $retryCount);	
	}

}
