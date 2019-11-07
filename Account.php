<?php

class Account extends YS_User { 

	public static function initHooks() {
		add_rewrite_endpoint( 'company',  EP_PAGES );
		add_rewrite_endpoint( 'password',  EP_PAGES );
		add_rewrite_endpoint( 'address/add',  EP_PAGES );
		add_rewrite_endpoint( 'address/edit',  EP_PAGES );
		add_rewrite_endpoint( 'address/delete',  EP_PAGES );
		flush_rewrite_rules();

		remove_action('template_redirect', array('WC_Form_Handler', 'save_account_details'));
    add_action('template_redirect', array(__CLASS__, 'editAccount'));
		add_action('template_redirect', array(__CLASS__, 'editCompany'));
		add_action('template_redirect', array(__CLASS__, 'EditUserAddress'));
		add_action('template_redirect', array(__CLASS__, 'changePassword'));

		add_action('woocommerce_account_company_endpoint',  array(__CLASS__, 'formCompany'), 999);
		add_action('woocommerce_account_address/add_endpoint',  array(__CLASS__, 'formUserAddress'), 999);
		add_action('woocommerce_account_address/edit_endpoint',  array(__CLASS__, 'formUserAddress'), 999);
		add_action('woocommerce_account_address/delete_endpoint',  array(__CLASS__, 'deleteAddress'), 999);
		add_action('woocommerce_account_password_endpoint',  array(__CLASS__, 'formPassword'), 999);	
		
		add_filter('woocommerce_account_menu_items',  array(__CLASS__, 'accountMenu'), 999, 1 );
		add_filter('woocommerce_get_endpoint_url',  array(__CLASS__, 'endpointURL'), 999, 4 );
		
  }
  

  public static function accountMenu($items) {
		$myMenu = [
			'dashboard' => __('Профил', 'woocommerce'),
			'password' => __('Промяна на парола', 'woocommerce'),
			'company' => __('Данни на фирма', 'woocommerce'),
			'orders' => $items['orders'],
		];

    return $myMenu;
  }


	public static function editAccount() {
		if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) ) {
			return;
		}

		if ( empty( $_POST['action'] ) || 'save_account_details' !== $_POST['action'] ) {
			return;
		}

		wc_nocache_headers();

		$nonce_value = wc_get_var( $_REQUEST['save-account-details-nonce'], wc_get_var( $_REQUEST['_wpnonce'], '' ) ); // @codingStandardsIgnoreLine.

		if ( ! wp_verify_nonce( $nonce_value, 'save_account_details' ) ) {
			return;
		}

		$inputs = [				
			'full_name' => sanitize($_POST['full_name'], 'trim|stripslashes|htmlspecialchars'),
			'phone' =>  sanitize($_POST['phone'], 'trim|xss'),		
		];
		
		$rules = [
			'full_name' =>  ['required', 'maxlength' => 50],
			'phone' => ['required']
		];
		
		$messages = [
			'full_name' => [
				'required' => 	'Не сте попълнили Имe',
				'maxlength' => 	'Прекалено дълго Име'
			],
			'phone' => [
				'required' => 'Не сте попълнили Телефон'
			]
		];

		$validator = new Validator();
		if(!$validator->validate($inputs, $rules, $messages)) {
			foreach($validator->getErrors() as $error) {
				wc_add_notice($error, 'error');
				break;
			}
			return;
		}
		
		$data['id'] = self::$user_data->id;
		$data['email'] = self::$user_data->email;
		$data['fullName'] = $inputs['full_name'];
		$data['phone'] = $inputs['phone'];

		try {
			$result = YanakAPI::instance()->apiRequest('user', 'PUT', $data);
			if($result->error) throw new Exception(__($result->error, 'woocommerce'));
			
			YS_User::setUserData($result->user);	
			ys_set_notice('success', 'Редакцията завърши успешно!');
			$redirect = wc_get_page_permalink('myaccount');
			wp_redirect($redirect);				
			exit();
		} catch(Exception $ex) {
			wc_add_notice($ex->getMessage(), 'error');
			return;
		}
		
	}

  
  public static function formCompany() {
		global $wp_query;
		if(is_user_logged_in()) {
			$user = clone YS_User::getUserData();
			(!empty($user->vatnumber))? $user->dds = true : $user->dds = false;
	
			$nonce_value = isset($_POST['_wpnonce']) ? $_POST['_wpnonce'] : '';
			if(isset($_POST['action']) && $_POST['action'] === 'save_company_details' && wp_verify_nonce($nonce_value, 'save_company_details') ) {
				$user->company_name = $_POST['company_name'];
				$user->company_mol = $_POST['company_mol'];
				$user->company_bulstat = $_POST['company_bulstat'];
				((int)$_POST['dds'] === 1) ? $user->vatnumber = $_POST['vatnumber'] : $user->vatnumber  = '';	
				$user->company_address = $_POST['company_address'];
			}	

			wc_get_template('myaccount/form-edit-company.php', array('user' => $user));
		}
	}

	
  public static function editCompany($template) {
		$nonce_value = isset($_POST['_wpnonce']) ? $_POST['_wpnonce'] : '';
		if(isset($_POST['action']) && $_POST['action'] === 'save_company_details' && wp_verify_nonce($nonce_value, 'save_company_details') ) {
			wc_nocache_headers();	

			$inputs = [			
				'userID' => self::$user_data->id,
				'company_name' 		=> sanitize($_POST['company_name'], 'trim|stripslashes|htmlspecialchars'),
				'company_mol' 		=> sanitize($_POST['company_mol'], 'trim|stripslashes|htmlspecialchars'),
				'company_bulstat' => sanitize($_POST['company_bulstat'], 'trim|stripslashes|htmlspecialchars'),
				'dds' 						=> (int)(bool)isset($_POST['dds']),
				'vatnumber' 			=> sanitize($_POST['vatnumber'], 'trim|stripslashes|htmlspecialchars'),
				'company_address' =>  sanitize($_POST['company_address'], 'trim|xss'),		
			];

			$rules = [
				'company_name' 		=> ['required', 'minlength' => 2, 'maxlength' => 500],
				'company_mol' 		=> ['required', 'minlength' => 2, 'maxlength' => 50],
				'company_bulstat' => ['required'],
				'company_address' => ['required', 'minlength' => 6, 'maxlength' => 500],
			];


			if($inputs['dds']) {
				$rules['vatnumber'] = ['custom' => function ($inputs){
					return ($inputs['vatnumber']) ? true : false;	
				}];
			}	else {
				$inputs['vatnumber'] = '';
			}


			$messages = [
				'company_name' => [
					'required' 	=> 'Не сте попълнили Име на фирма',
					'minlength' => 'Невалидно Име на фирма',
					'maxlength' => 'Прекалено дълго Име на фирма'
				],
				'company_mol' => [
					'required' => 'Не сте попълнили МОЛ',
					'minlength' => 'Невалиден МОЛ',
					'maxlength' => 'Прекалено дълъг МОЛ'
				],
				'company_bulstat' => [
					'required' => 'Не сте попълнили ЕИК.', 
				],
				'vatnumber' => [
					'custom' => 'Не сте попълнили ДДС №'
				],
				'company_address' => [
					'required' 	=> 'Не сте попълнили Адрес на фирмата',
					'minlength' => 'Невалиден Адрес на фирма',
					'maxlength' => 'Прекалено дълъг Адрес на фирма'
				]
			];

			$validator = new Validator();
			if(!$validator->validate($inputs, $rules, $messages)) {
				foreach($validator->getErrors() as $error) {
					wc_add_notice($error, 'error');
				}
				return;
      }
			
			try{
				$customerID = (int)YS_User::getUserData()->sb_idnumb;
				if($customerID > 0) {
					$method = 'PUT';
					$inputs['id'] = $customerID;
				} else {
					$method = 'POST';
				}
				$result = YanakAPI::instance()->apiRequest('customer', $method, $inputs);
				if($result->error) throw new Exception(__($result->error, 'woocommerce'));
				YS_User::setUserData($result->user);
				ys_set_notice('success', 'Редакцията завърши успешно!');
				$redirect = wc_get_page_permalink('myaccount').'company';
				wp_redirect($redirect);
				exit();					
			}	catch(Exception $ex) {
				wc_add_notice($ex->getMessage(), 'error');
				return;
			} 
		}
	} 
	

  public static function formPassword() {
		global $wp_query;
		if(is_user_logged_in()) {
			wc_get_template('myaccount/form-edit-password.php');
			
		}
	}

	public static function changePassword(){
		$nonce_value = isset($_POST['_wpnonce']) ? $_POST['_wpnonce'] : '';
		if(isset($_POST['action']) && $_POST['action'] === 'change_password' && wp_verify_nonce($nonce_value, 'change_password') ) {
			$inputs = [
				'email' => YS_User::getUserData()->email,
				'current_password' =>  sanitize($_POST['password_current'], 'trim|stripslashes|htmlspecialchars'),
				'password_1' => sanitize($_POST['password_1'], 'trim|stripslashes|htmlspecialchars'),
				'password_2' => sanitize($_POST['password_2'], 'trim|stripslashes|htmlspecialchars'),
			];


			try {
				$result = YanakAPI::instance()->apiRequest('user/password', 'PUT', $inputs);
				if($result->error) {
					throw new Exception(__($result->error, 'woocommerce'));
				}
				$redirect = wc_get_page_permalink('myaccount');
				wp_redirect($redirect);			
				exit();	
			} catch(Exception $ex) {
				wc_add_notice($ex->getMessage(), 'error');
				return;
			}

		}
	}


	public static function formUserAddress() {
		if(is_user_logged_in()) {
			$url = explode('/', $_SERVER['REQUEST_URI']);
			array_shift($url);
			array_pop($url);
			
		if($url[count($url) -1] === "add") {
			$data['title'] = 'Добавяне на адрес';
			$data['method'] = 'POST';
		}
		else if($url[count($url) -2] === "edit"){
			$addressID = (int)end($url);
			$data['title'] = 'Редактиране на адрес';
			$data['method'] = 'PUT';
			foreach(self::$user_data->delivery_address as $value) {
				if($value->id  === $addressID) {
					$data['address'] = $value;
					break;
				}
			}
		}

			wc_get_template('myaccount/form-user-address.php', array('data' => $data));
		}
	}
	

	public static function EditUserAddress() {
		$nonce_value = isset($_POST['_wpnonce']) ? $_POST['_wpnonce'] : '';
		if(isset($_POST['action']) && $_POST['action'] === 'save_user_address' && wp_verify_nonce($nonce_value, 'save_user_address') ) {
			wc_nocache_headers();	
			$inputs = [			
				//'name' 		=> sanitize($_POST['name'], 'trim|stripslashes|htmlspecialchars'),
				'address' =>  sanitize($_POST['address'], 'trim|xss'),
			];
			
			$rules = [
				//'name' => ['required'],
				'address' => ['required',  'minlength' => 6, 'maxlength' => 500],
			];
			
			$messages = [
				// 'name' => [
					// 	'required' => 'Не сте попълнили Име', 
					// ],
					'address' => [
						'required' => 'Не сте попълнили Адрес за доставка',
						'minlength' => 'Невалиден Адрес за доставка',
						'maxlength' => 'Прекалено дълъг Адрес за доставка'
					],
				];
				
			$validator = new Validator();
			if(!$validator->validate($inputs, $rules, $messages)) {
				foreach($validator->getErrors() as $error) {
					wc_add_notice($error, 'error');
				}
				return;
			}	
			else {
				$data = [
					'userID' => self::$user_data->id,
					'delivery_address' => $inputs['address'],
				];
				
				if($_POST['method'] === 'PUT') {
					$data['id'] = (int)sanitize($_POST['addressID'], 'trim|xss');
				}
				
				try{
					$result = YanakAPI::instance()->apiRequest('user/address', $_POST['method'], $data);
					if($result->error) throw new Exception(__($result->error, 'woocommerce'));
					YS_User::setUserData($result->user);
					$redirect = wc_get_page_permalink('myaccount');
					wp_redirect($redirect);
					exit();					
				}	catch(Exception $ex) {
					wc_add_notice($ex->getMessage(), 'error');
					return;
				} 
			}
		}
	}

	public static function deleteAddress(int $addressID) {
		$user = YS_User::getUserData();
		if(count($user->delivery_address) === 1) {
			throw new Exception(__("Не можете да изтриете единственият адрес!", 'woocommerce'));
		}

		$address = [
			'userID' => $user->id
		];

		foreach($user->delivery_address as $value) {
			if($value->id === $addressID) {
				$address['id'] = $value->id;
				$address['delivery_address'] = $value->address;
				break;
			}
		}

		try {
			$result = YanakAPI::instance()->apiRequest('user/address', 'DELETE', $address);
			if ($result->error) {
				wc_add_notice($result->error, 'error');
				return;
			}

			YS_User::setUserData($result->user);
			$redirect = wc_get_page_permalink('myaccount');
			wp_redirect($redirect);
			exit();	

		} catch(Exception $ex) {
			wc_add_notice($ex->getMessage(), 'error');
			return;
		} 
	
	}


	public static function endpointURL ($url, $endpoint, $value, $permalink ){
		if($endpoint === 'customer-logout') {
			return wp_logout_url( get_home_url());
		}
		return $url;
	}

}


