<?php

class Images implements IYS_Hooks{
	
	private static $sizes = [
		'full' => 'large',
		'shop_single' => 'large',
		'woocommerce_single' => 'large',
		'shop_thumbnail' => 'thumb',
		'shop_catalog' => 'thumb'
	];
	
	public static $cache = [
		'thumb' => [],
		'medium' => [], 
		'large' => [],
		'getimagesize' => []
	];

	public static $currentID = null;
	
	public static function initHooks() {		
		add_filter('wp_get_attachment_image_src', array(__CLASS__, 'getAttachmentImageSrc'), 999, 4);
		add_filter('wp_get_attachment_image_attributes', array(__CLASS__, 'getAttachmentImageAttributes'), 999, 3);
		add_filter('ys_wp_get_attachment_url', array(__CLASS__, 'getAttachmentUrl'), 999, 1);
		add_filter('ys_get_attachment_metadata', array(__CLASS__, 'getAttachmentMetadata'), 999, 2);
		add_action('woocommerce_before_single_product_summary', array( __CLASS__, 'beforeSingleProductSummary'));
	}
	
	
	public static function stockID(){
		global $product;
		return getStockIDFromPostID($product->id);
	}
	
		
	private static function scanDir($stockID, $size = 'large'){
		$dir = IMAGES_DIR.$size.'/'.$stockID;
		if(is_dir($dir)) {
			$images = array_slice(scandir($dir), 2);
			if($images) {
				return $images;	
			}
		}
		return false;
	}
		
	
	public static function get($stockID, $size, $item = 0) {
		if(!isset(self::$cache[$size][$stockID][$item])) {
			if(($stockID && $images = self::scanDir($stockID, $size)) && $images[$item]) {
			  $src = self::$cache[$size][$stockID][$item] = wp_upload_dir(null, false)['baseurl'].'/images/'.$size.'/'.$stockID.'/'.$images[$item];	 
			} else {		
				$src = wc_placeholder_img_src('shop_thumbnail'); //content_url('uploads').'/no-image.jpg';
			}
		}
		else {
			$src = self::$cache[$size][$stockID][$item];
		}

		// convert src to local path
		$local_path = $_SERVER['DOCUMENT_ROOT'] . parse_url($src, PHP_URL_PATH);
	
		if (!isset(self::$cache['getimagesize'][$size][$stockID][$item])) {
			list($width, $height) = getimagesize($local_path);
			self::$cache['getimagesize'][$size][$stockID][$item] = [$width, $height];
		}

		list($width, $height) = self::$cache['getimagesize'][$size][$stockID][$item];

		return [$src, $width, $height];	
	}
	
	public static function getAttachmentImageSrc($image, $attachment_id, $size, $icon) {
		global $post;

		if(isYSProduct()) {
			$attachment_id = (int)$attachment_id;
		//	if($attachment_id !== DUMMY_PRODUCT_ID) { ТАЗИ проверка не знам за какво съм я слагал, 
			// явно някъде е трябвала, но ако $attachment_id-то на снимката е равна на DUMMY_PRODUCT_ID, тогава снимката не се визуализира. 
				$img = self::attachImages($image, $attachment_id, $size);
				if (!empty($img) && !empty($img[0])) {
					return $img;
				}
		//	}
		}
	
		else if($post->stockID && ($post->post_type === 'page' || $post->post_type === 'product')) {
			$image = self::get(getStockIDFromPostID($post->stockID), self::$sizes[$size]);
		}

		else if(self::$currentID) {
			$image = self::get(getStockIDFromPostID(self::$currentID), self::$sizes[$size]);
		}
		
		return $image;	
	}
	

	public static function getAttachmentImageAttributes($attr, $attachment, $size) {
		global $post, $product;
		if(isYSProduct()) { 
			if(!is_product()) {
				$attr['src'] = self::get(getStockIDFromPostID($post->ID), 'thumb')[0];	
				$attr['alt'] = $post->post_title;
				$attr['title'] = $post->post_excerpt;	
			}
		}
		return $attr;
	}
	
	public static function beforeSingleProductSummary() {
		global $post, $product;
		$dir =  IMAGES_DIR.'large/'.self::stockID().'/';	
		$images = glob($dir . '*.jpg');
		if($images) {
			$images = array_keys($images);
			array_shift($images);
			$product->set_object_read(false);
			$product->set_gallery_image_ids($images);	
		}
	}
	
		
	public static function attachImages($image, $attachment_id, $size) {
		if(array_key_exists($size, self::$sizes)) {
			if($attachment_id === DUMMY_IMAGE_ID ) {
				$image = self::get(self::stockID(), self::$sizes[$size], 0);
			} else if (!is_array($size)){
				$image = self::get(self::stockID(), self::$sizes[$size], $attachment_id);
			} else {		
				$image = self::get(false, false);
			}
		}

	
		$image[3] = false;
		return $image;
	}
	
	
	public static function getAttachmentUrl($post_id) {
		if($post_id === DUMMY_IMAGE_ID) {
			$post_id = 0;
		} else if(get_post($post_id)->post_type === 'attachment') {
			return false;
		}

		return self::get(self::stockID(), 'large',  $post_id)[0];
	}
	
	
	public static function getAttachmentMetadata($post_id, $size = 'large') {
	$image = self::get(self::stockID(), 'large',  $post_id);
		return [
			'width' => $image[1],
			'height' => $image[2]
		];
	}
		
}
