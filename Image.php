<?php

class Image {	
	private static $dirs = [];
	
	
	private static function setDirs($id){
		if(empty(self::$dirs)) {
			self::$dirs = [
				IMAGES_DIR.'large/'.$id.'/',
				IMAGES_DIR.'medium/'.$id.'/',		
				IMAGES_DIR.'thumb/'.$id.'/',				
			];			
		}
	}
	
private static function resize($source_image, $destination, $height, $quality = 100) {
	list($width_orig, $height_orig, $mimetype) = getimagesize($source_image);
	$imgtype = image_type_to_mime_type($mimetype);

	switch ($imgtype) {
	case 'image/jpeg':
		$source = imagecreatefromjpeg($source_image);
		break;
	case 'image/gif':
		$source = imagecreatefromgif($source_image);
		break;
	case 'image/png':
		$source = imagecreatefrompng($source_image);
		break;
	default:
		die('Invalid image type.');
	}

	$aspectRatio = $width_orig / $height_orig;
	$width = $height * $aspectRatio;	

	$newIMG = imagecreatetruecolor(round($width), round($height));
	imagecopyresampled($newIMG, $source, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
	$final = imagecreatetruecolor($width, $height);
	$backgroundColor = imagecolorallocate($final, 255, 255, 255);
	imagefill($final, 0, 0, $backgroundColor);

	imagecopy($final, $newIMG, 0, 0, 0, 0, $width, $height);
	(imagejpeg($final, $destination, $quality));
	imagedestroy($newIMG);
	imagedestroy($final);
}	
	
	
	public function getAll(WP_REST_Request $request, $size = 'large') {
		$input = $request->get_params();
		$dir = IMAGES_DIR.$size.'/'.$input['id'].'/';
		$images = glob($dir . '*.jpg');
		
		if ($images !== false) {
			$data = [];
			foreach($images as $image){
				$data[] = [
					error => '', 
					image_name => pathinfo($image)['basename'],	
					image_data => base64_encode(file_get_contents($image)),
				];
			}
			return $data;
		}
		return false;
	}	
	
	
	public static function create(WP_REST_Request $request) {
		$input = $request->get_params();
		$input['id'] = (int)$input['id'];
		$output = [error => '', image_name => '', image_data => ''];		
		self::setDirs($input['id']);
		
		foreach(self::$dirs as $dir){
			if (!is_dir($dir) && !mkdir($dir)) {	
				$output['error'] = 'Сървърът не можа да създаде директория за стоката.';
				return $output;
			}
		}
		
		$input['image_name'] = pathinfo($input['image_name'], PATHINFO_FILENAME);
		$data = base64_decode($input['image_data']);
		$types = array('.jpg' => 'image/jpeg', '.png' => 'image/png');
		$f = finfo_open();
		$mime_type = finfo_buffer($f, $data, FILEINFO_MIME_TYPE);		
		$type = array_search($mime_type, $types);
		
		if(!in_array($mime_type, $types) || !imagecreatefromstring($data)) {
			$output['error'] = 'Файловия формат не се поддържа.';
			return $output;		
		}
		
		$uniqName = self::$dirs[0].uniqid().$type;
		if(file_put_contents($uniqName, $data)) {
			$filename = $input['image_name'].'.jpg';
			
			self::resize($uniqName, self::$dirs[0].$filename, 800);
			self::resize($uniqName, self::$dirs[1].$filename, 400);
			self::resize($uniqName, self::$dirs[2].$filename, 150);
			
			unlink($uniqName);

			$output['image_name'] = $filename;
			$output['image_data'] = base64_encode(file_get_contents(self::$dirs[0].$filename));
			return $output;
		}
		
		$output['error'] = 'Не е намерена снимка.';
		return $output;		
	}
	
	
	public function rename(WP_REST_Request $request) {
		$input = $request->get_params();	
		self::setDirs($input['id']);		
		
		foreach (self::$dirs as $dir) {
			if(file_exists($dir.$input['new_image_name'])) {
				return [
					error => 'file already exists',
				];
			}
			rename($dir.$input['old_image_name'], $dir.$input['new_image_name']);
		}

		$file = pathinfo(self::$dirs[0].$input['new_image_name']);
		return [
			error => '',
			result => $file['basename']
		];
	}
	
	
	public function remove(WP_REST_Request $request) {
		$input = $request->get_params();
		$output = [error => ''];
		self::setDirs($input['id']);
				
		foreach (self::$dirs as $dir) {
			if(!unlink($dir.$input['image_name'])) {
				$output['error'] = 'Възникна греша в сървъра при изтриване на снимката.';
			}
		}
		return $output;	
	}
	
}
