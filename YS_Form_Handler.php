<?php
/**
 * Handle frontend forms.
 *
 * @version	2.2.0
 * @package	WooCommerce/Classes/
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Form_Handler class.
 */
class YS_Form_Handler extends WC_Form_Handler {

	public static function init() {
    remove_action('wp_loaded', array('WC_Form_Handler', 'process_login'), 20);
    add_action('wp_loaded', array(__CLASS__, 'process_login'), 20);
    
    remove_action('wp_loaded', array('WC_Form_Handler', 'process_registration'), 20);
    add_action('wp_loaded', array(__CLASS__, 'process_registration'), 20);
    add_shortcode( 'ys_registration',   array(__CLASS__, 'registration_form') );

		remove_action('wp_loaded', array('WC_Form_Handler', 'process_lost_password'), 20);
    add_action('wp_loaded', array(__CLASS__, 'process_lost_password'), 20);
    
		remove_action('wp_loaded', array('WC_Form_Handler', 'process_reset_password'), 20);
		add_action('wp_loaded', array(__CLASS__, 'reset_password'), 20);
  }


	public static function process_login() {
		$nonce_value = isset( $_POST['_wpnonce'] ) ? $_POST['_wpnonce'] : '';
		$nonce_value = isset( $_POST['woocommerce-login-nonce'] ) ? $_POST['woocommerce-login-nonce'] : $nonce_value;

		if (!empty( $_POST['login'] ) && wp_verify_nonce($nonce_value, 'woocommerce-login')) {
			$validator = new Validator();
			$rememberme = $_POST['rememberme'];
			$inputs = [
				'email' => sanitize($_POST['username'], 'trim|stripslashes|htmlspecialchars'),
				'password' => sanitize($_POST['password'], 'trim|stripslashes|htmlspecialchars'),
			];
			
			$rules = [
				'email' => [  'required', 'email'],
				'password' => ['required'],
			];

			$messages = [
				'email' => [
					'required' => 'Не сте попълнили Имейл адрес', 
					'email' => 'Невалиден имейл адрес'
				],
				'password' => [
					'required' => 'Не сте попълнили парола', 
				]
			];
			
			if(!$validator->validate($inputs, $rules, $messages)) {
				foreach($validator->getErrors() as $error) {
					wc_add_notice($error, 'error');
				}
				do_action( 'woocommerce_login_failed' );
				return;				
			}
			
			$inputs['sessionID'] = session_id();
			
			try {
				$result = YanakAPI::instance()->apiRequest('user/login', 'POST', $inputs);
				if($result->error) throw new Exception(__($result->error, 'woocommerce'));

				YS_User::setUserData($result->user);

				// извиква оригиналния логин с dummy юзъра
				$_POST['username'] = DUMMY_CUSTOMER_USERNAME;
				$_POST['password'] = DUMMY_CUSTOMER_PASSWORD;
				if(wp_doing_ajax()) {
					return;
				}
				parent::process_login();
			} catch(Exception $ex) {
				wc_add_notice($ex->getMessage(), 'error');
				do_action( 'woocommerce_login_failed' );
			}
			
		}
  }
  

  public static function process_registration() {
		$nonce_value = isset( $_POST['_wpnonce'] ) ? $_POST['_wpnonce'] : '';
		$nonce_value = isset( $_POST['woocommerce-register-nonce'] ) ? $_POST['woocommerce-register-nonce'] : $nonce_value;

		if (!empty( $_POST['register'] ) && wp_verify_nonce( $nonce_value, 'woocommerce-register' ) ) {
			$validator = new Validator();
			$inputs = [
				'email' =>  sanitize($_POST['email'], 'trim|stripslashes|htmlspecialchars'),
				'password' => sanitize($_POST['password'], 'trim|stripslashes|htmlspecialchars'),
				'password_2' => sanitize($_POST['password_2'], 'trim|stripslashes|htmlspecialchars'),				
				'full_name' => sanitize($_POST['full_name'], 'trim|stripslashes|htmlspecialchars'),
				'phone' =>  sanitize($_POST['phone'], 'trim|xss'),
				'delivery_address' => sanitize($_POST['delivery_address'], 'trim|stripslashes|htmlspecialchars'),
				'is_company' => (bool)$_POST['is_company']
			];
			

			$rules = [
				'email' => ['required', 'email'],
				'password' => ['required', 'minlength' => 6, 'maxlength' => 50],
				'password_2' => ['matchStrict' => $inputs['password']],
				'full_name' =>  ['required', 'maxlength' => 50],
				'phone' => ['required'],
				'delivery_address' => ['required', 'minlength' => 6, 'maxlength' => 500]
			];
			
			$messages = [
				'email' => [
					'required' => 'Не сте попълнили Имейл адрес', 
					'email' => 'Невалиден имейл адрес'
				],
				'password' => [
					'required' => 'Не сте попълнили парола', 
					'minlength' => 'Паролата трябва да е минимум 6 символа', 
					'maxlength' => 'Паролата е прекалено дълга'
				],
				'password2' => [
					'matchStrict' => 'Паролите не съвпадат',
				],
				'full_name' => [
					'required' => 	'Не сте попълнили Имe',
					'maxlength' => 	'Прекалено дълго Име'
				],
				'phone' => [
					'required' => 'Не сте попълнили Телефон'
				],
				'delivery_address' => [
					'required' => 'Не сте попълнили Адрес за доставка',
					'minlength' => 'Невалиден Адрес за доставка',
					'maxlength' => 'Прекалено дълъг Адрес за доставка'
				]
			];	
			
			if($inputs['is_company']) {
				$inputs['company_name'] = sanitize($_POST['company_name'], 'trim|stripslashes|htmlspecialchars');
				$inputs['company_mol'] = sanitize($_POST['company_mol'], 'trim|stripslashes|htmlspecialchars');
				$inputs['company_bulstat'] = sanitize($_POST['company_bulstat'], 'trim|stripslashes|htmlspecialchars');
				$inputs['dds'] = (bool)$_POST['dds'];
				$inputs['vatnumber'] = sanitize($_POST['vatnumber'], 'trim|stripslashes|htmlspecialchars');
				$inputs['company_address'] = sanitize($_POST['company_address'], 'trim|stripslashes|htmlspecialchars');	
				

				$rules['company_name'] = ['required', 'minlength' => 2, 'maxlength' => 500];
				$rules['company_mol'] = ['required', 'minlength' => 2, 'maxlength' => 50];
				$rules['company_bulstat'] = ['required'];
				if($inputs['dds']) $rules['vatnumber'] = ['required', 'minlength' => 7];
				else $inputs['vatnumber'] = '';
				
				$rules['company_address'] = ['required',  'minlength' => 6, 'maxlength' => 500];	

				$messages['company_name'] = [
					'required' => 'Не сте попълнили Име на фирма',
					'minlength' => 'Невалидно Име на фирма',
					'maxlength' => 'Прекалено дълго Име на фирма'
				];

				$messages['company_mol'] = [
					'required' => 'Не сте попълнили МОЛ',
					'minlength' => 'Невалиден МОЛ',
					'maxlength' => 'Прекалено дълъг МОЛ'
				];

				$messages['company_bulstat'] = ['required' => 'Не сте попълнили ЕИК'];

				$messages['vatnumber'] = [
					'required' => 'Не сте попълнили ДДС №',
					'minlength' => 'Невалиден ДДС № ',
				];
				

				$messages['company_address'] = [
					'required' => 'Не сте попълнили Адрес на фирмата',
					'minlength' => 'Невалиден Адрес на фирма',
					'maxlength' => 'Прекалено дълъг Адрес на фирма'
				];
			}
      
			if(!$validator->validate($inputs, $rules, $messages)) {
				foreach($validator->getErrors() as $error) {
					wc_add_notice($error, 'error');
				}
				return;				
			}
			
			try {
				$result = YanakAPI::instance()->apiRequest('user', 'POST', $inputs);
				if($result->error) throw new Exception(__($result->error, 'woocommerce'));
				
				YS_User::setUserData($result->user);

				// извиква оригиналния логин с dummy юзъра
				$creds = array(
					'user_password' => DUMMY_CUSTOMER_PASSWORD,
					'user_login' => DUMMY_CUSTOMER_USERNAME,
					'remember'   => false,
				);
	
				$user = wp_signon( apply_filters('woocommerce_login_credentials', $creds ), is_ssl());	
				$default = wc_get_page_permalink('myaccount');
				wp_redirect(wp_validate_redirect(wc_get_page_permalink('shop'), $default));
				exit();
			} catch (Exception $ex) {
				wc_add_notice($ex->getMessage(), 'error');
				do_action('woocommerce_login_failed');
				return;
			}
		}
	}
  

	public static function registration_form (){
		$user = new stdClass();
		$user->dds = false;
		$nonce_value = isset( $_POST['_wpnonce'] ) ? $_POST['_wpnonce'] : '';
		$nonce_value = isset( $_POST['woocommerce-register-nonce'] ) ? $_POST['woocommerce-register-nonce'] : $nonce_value;
		if (!empty($_POST['register'] ) && wp_verify_nonce( $nonce_value, 'woocommerce-register' ) ) {
			$user->is_company = (bool)isset($_POST['is_company']);
			$user->company_name = $_POST['company_name'];
			$user->company_mol = $_POST['company_mol'];
			$user->company_bulstat = $_POST['company_bulstat'];
			$user->dds = (bool)isset($_POST['dds']);
			if($user->dds) $user->vatnumber = $_POST['vatnumber'];
			else $user->vatnumber  = '';
			
			$user->company_address = $_POST['company_address'];
		}

		wc_get_template('auth/form-registration.php', array('user' => $user));
  }
  


  public static function process_lost_password() {
    if ( isset( $_POST['wc_reset_password'], $_POST['user_login'] ) ) {
      $nonce_value = wc_get_var( $_REQUEST['woocommerce-lost-password-nonce'], wc_get_var( $_REQUEST['_wpnonce'], '' ) ); // @codingStandardsIgnoreLine.

      if ( ! wp_verify_nonce( $nonce_value, 'lost_password' ) ) {
        return;
      }

      $validator = new Validator();
      $inputs = ['email' => sanitize($_POST['user_login'], 'trim|stripslashes|htmlspecialchars'),];
      $rules = ['email' => ['required', 'email'],];	
      $messages = [
        'email' => [
          'required' => 'Не сте попълнили Имейл адрес', 
          'email' => 'Невалиден Имейл адрес'
        ]
      ];			

      
      if(!$validator->validate($inputs, $rules, $messages)) {
        foreach($validator->getErrors() as $error) {
          wc_add_notice($error, 'error');
        }
        do_action('woocommerce_login_failed');
        return;				
      }
      try {
				$exist_email = YanakAPI::instance()->apiRequest('email/check-exist', 'POST', $inputs);
			}	catch(Exception $ex) {
				wc_add_notice($ex->getMessage(), 'error');
				return;	
			}

      if(!$exist_email->exist) {
        wc_add_notice('Taкъв имейл не съществува в нашата база.', 'error');
        return;	
      }

      $success = YS_Shortcode_My_Account::retrieve_password();

      // If successful, redirect to my account with query arg set.
      if ( $success ) {
        wp_redirect( add_query_arg( 'reset-link-sent', 'true', wc_get_account_endpoint_url( 'lost-password' ) ) );
        exit;
      }
    }
  }



	/**
	 * Handle reset password form.
	 */
	public static function reset_password() {
		$posted_fields = array( 'wc_reset_password', 'password_1', 'password_2', 'reset_key', 'reset_login' );

		foreach ( $posted_fields as $field ) {
			if ( ! isset( $_POST[ $field ] ) ) {
				return;
			}
			$posted_fields[ $field ] = $_POST[ $field ];
		}

		$nonce_value = wc_get_var( $_REQUEST['woocommerce-reset-password-nonce'], wc_get_var( $_REQUEST['_wpnonce'], '' ) ); // @codingStandardsIgnoreLine.

		if ( ! wp_verify_nonce( $nonce_value, 'reset_password' ) ) {
			return;
		}

		$user = get_user_by( 'login', 'dummycustomer' );
		$cookie = array_map( 'wc_clean', explode( ':', wp_unslash( $_COOKIE[ 'wp-resetpass-' . COOKIEHASH ] ), 2 ) );
		$user->data->user_email = $cookie[0];

		if ( $user instanceof WP_User ) {
			if ( empty( $posted_fields['password_1'] ) ) {
				wc_add_notice( __( 'Please enter your password.', 'woocommerce' ), 'error' );
			}

			if ( $posted_fields['password_1'] !== $posted_fields['password_2'] ) {
				wc_add_notice( __( 'Passwords do not match.', 'woocommerce' ), 'error' );
			}

			if ( 0 === wc_notice_count( 'error' ) ) {
				try {
					YS_Shortcode_My_Account::reset_password( $user, $posted_fields['password_1'] );
				} catch(Exception $ex) {
					wc_add_notice('<strong>' . __('Error:', 'woocommerce') . '</strong> ' . $ex->getMessage(), 'error');
				}			
			}

			$errors = new WP_Error();
			do_action( 'validate_password_reset', $errors, $user );
			wc_add_wp_error_notices( $errors );

			if ( 0 === wc_notice_count( 'error' ) ) {
				do_action( 'woocommerce_customer_reset_password', $user );
				wp_redirect( add_query_arg( 'password-reset', 'true', wc_get_page_permalink( 'myaccount' ) ) );
				exit;
			}
		}
	} 

}

?>