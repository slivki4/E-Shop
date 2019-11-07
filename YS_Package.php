<?php

class YS_Package {
	
	public static function show(WP_REST_Request $request) {
		$input = $request->get_params();
		$productID = getStockIDFromPostID($input['productID']);
		$product = Stocks::get($productID);

		$packages = self::getPackage($productID);
		$additions = self::getAdditions($productID); 

		$data = [
			'productID' => $input['productID'],
			'title' => $product->name,
			'layout' => __DIR__.'/templates/modals/package.php',
			'product' => $product,
			'image' => Images::get($productID, 'medium'),
			'packages' => $packages,
			'additions' => $additions
		];
		
		ob_start();
		include_once(__DIR__.'/templates/modals/modal-window.php');
		$my_html = ob_get_contents();
		ob_end_clean();
		wp_send_json_success($my_html);
	}



	public static function getPackage($productID) {
		return YanakAPI::instance()->apiRequest('stock/package', 'GET', [
			'stockID' => $productID 
		])->items;
	}


	public static function getAdditions($productID){
		return YanakAPI::instance()->apiRequest('stock/modifires', 'GET', [
			'stockID' => $productID 
		])->items;
	}


}


