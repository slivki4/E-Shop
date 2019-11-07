<?php

class YS_Fiscal {
	
	private static $fiscal_labels = [
		'company' => 'Име на фирма',
		'company_address' => 'Адрес на фирма',
		'object_name' => 'Име на обекта / ТИП',
		'object_address' => 'Адрес на обекта',
		'business_type' => 'Дейност, извършвана в обекта',
		'tax_service' => 'Данъчна служба (където е ригстрирана фирмата)',
		'tax_region' => 'Район на данъчна служба на обекта',
		'bulstat' => 'БУЛСТАТ / ЕИК',
		'dds' => 'Регистрация по ЗДДС',
		'mol' => 'МОЛ (име, презиме, фамилия)',
		'phone' => 'Телефон за връзка',
		'email' => 'E-mail'
	];

	public function modalFormShow(WP_REST_Request $request){
		$input = $request->get_params();
		$productID = getStockIDFromPostID($input['productID']);

		$data = [
			'productID' => $input['productID'],
			'title' => 'Данни за Фискализация',
			'layout' => __DIR__.'/templates/modals/fiscal-form.php',
			'product' => Stocks::get($productID),
			'image' => Images::get($productID, 'thumb'),
			'labels' => self::$fiscal_labels
		];
		
		ob_start();
		include_once(__DIR__.'/templates/modals/modal-window.php');
		$my_html = ob_get_contents();
		ob_end_clean();
		wp_send_json_success($my_html);
	}


	public static function modalFormSubmit(WP_REST_Request $request) {
		$input = $request->get_params();
		$validator = new Validator();
	
		$inputs = [
			'company' => sanitize($input['company'], 'trim|stripslashes|replacequotes|htmlspecialchars'),
			'company_address' => sanitize($input['company_address'], 'trim|stripslashes|replacequotes|htmlspecialchars'),
			'object_name' => sanitize($input['object_name'], 'trim|stripslashes|replacequotes|htmlspecialchars'),
			'object_address' => sanitize($input['object_address'], 'trim|stripslashes|replacequotes|htmlspecialchars'),
			'business_type' => sanitize($input['business_type'], 'trim|stripslashes|replacequotes|htmlspecialchars'),
			'tax_service' => sanitize($input['tax_service'], 'trim|stripslashes|replacequotes|htmlspecialchars'),
			'tax_region' => sanitize($input['tax_region'], 'trim|stripslashes|htmlspecialchars'),
			'bulstat' => sanitize($input['bulstat'], 'trim|stripslashes|replacequotes|htmlspecialchars'),
			'dds' => sanitize($input['dds'], 'trim|stripslashes|replacequotes|htmlspecialchars'),
			'mol' => sanitize($input['mol'], 'trim|stripslashes|replacequotes|htmlspecialchars'),
			'phone' => sanitize($input['phone'], 'trim|stripslashes|replacequotes|htmlspecialchars'),
			'email' => sanitize($input['email'], 'trim|stripslashes|replacequotes|htmlspecialchars'),
		];

		$rules = [
			'company' => ['required', 'minlength' => 2, 'maxlength' => 255],
			'company_address' => ['required', 'minlength' => 2, 'maxlength' => 255],
			'object_name' => ['required', 'minlength' => 2, 'maxlength' => 255],
			'object_address' => ['required', 'minlength' => 2, 'maxlength' => 255],
			'business_type' => ['required', 'minlength' => 2, 'maxlength' => 255],
			'tax_service' => ['required', 'minlength' => 2, 'maxlength' => 255],
			'tax_region' => ['required', 'minlength' => 2, 'maxlength' => 255],
			'bulstat' => ['required', 'minlength' => 9, 'maxlength' => 9],
			'dds' => ['required'],
			'mol' => ['required', 'minlength' => 10, 'maxlength' => 255],
			'phone' =>  ['required'],
			'email' => ['required', 'email'],
		]; 
		
		$messages = [
			'company' => [
				'required' => 'Не сте попълнили полето!',
				'minlength' => 'Полето е прекалено късо!',
				'maxlength' => 'Полето е прекалено дълго!'
			],
			'company_address' => [
				'required' => 'Не сте попълнили полето!',
				'minlength' => 'Полето е прекалено късо!',
				'maxlength' => 'Полето е прекалено дълго!'
			],
			'object_name' => [
				'required' => 'Не сте попълнили полето!',
				'minlength' => 'Полето е прекалено късо!',
				'maxlength' => 'Полето е прекалено дълго!'
			],
			'object_address' => [
				'required' => 'Не сте попълнили полето!',
				'minlength' => 'Полето е прекалено късо!',
				'maxlength' => 'Полето е прекалено дълго!'
			],
			'business_type' => [
				'required' => 'Не сте попълнили полето!',
				'minlength' => 'Полето е прекалено късо!',
				'maxlength' => 'Полето е прекалено дълго!'			
			],	
			'tax_service' => [
				'required' => 'Не сте попълнили полето!',
				'minlength' => 'Полето е прекалено късо!',
				'maxlength' => 'Полето е прекалено дълго!'
			],
			'tax_region' => [
				'required' => 'Не сте попълнили полето!',
				'minlength' => 'Полето е прекалено късо!',
				'maxlength' => 'Полето е прекалено дълго!'
			],
			'bulstat' => [
				'required' => 'Не сте попълнили Булстат!',
				'minlength' => 'Невалиден Булстат!',
				'maxlength' => 'Невалиден Булстат!'
			],		
			'dds' => [
				'required' => 'Не сте попълнили полето!',
			],
			'mol' => [
				'required' => 'Не сте попълнили полето!',
				'minlength' => 'Полето е прекалено късо!',
				'maxlength' => 'Полето е прекалено дълго!'
			],
			'email' => [
				'required' => 'Не сте попълнили Email', 
				'email' => 'Невалиден Email'
			],
			'phone' => [
				'required' => 'Не сте попълнили Телефонен номер',
				'minlength' => 'Полето е прекалено късо!', 
				'maxlength' => 'Полето е прекалено дълго!'
			] 
		];
		
		$errors = [];
		if(!$validator->validate($inputs, $rules, $messages)) {
			foreach($validator->getErrors() as $key => $value) {
				$errors[$key] = $value;
			}
		 return wp_send_json_error($errors);
		}

		$fiscal_product = [
			'product' => Stocks::get(getStockIDFromPostID($input['productID']))
		];
		
		foreach($inputs as $key => $value ) {
			if(array_key_exists($key, self::$fiscal_labels)) {
				$fiscal_product[$key] = [
					'name' => self::$fiscal_labels[$key],
					'value' => $value
				];
			}
		};

		$_SESSION['fiscal_product'] = $fiscal_product;
		return wp_send_json_success();
	}

	
	public static function getFiscalInfo() {
		$fiscal_info = 'Продукт'."\n";
		$fiscal_info .= $_SESSION['fiscal_product']['product']->name."\n\n";
		unset($_SESSION['fiscal_product']['product']);

		foreach($_SESSION['fiscal_product'] as $value) {
			$fiscal_info .= $value['name']."\n"; 
			$fiscal_info .= $value['value']."\n\n";		
		}

		unset($_SESSION['fiscal_product']);	
		return $fiscal_info; 
	}


}


