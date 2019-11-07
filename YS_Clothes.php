<?php 

class YS_Clothes {

  public static function getSizes($productID) {
    $result = YanakAPI::instance()->apiRequest('stock/sizes', 'GET', [
			'stockID' => $productID 
    ]);
    return $result->items;
  }

}

