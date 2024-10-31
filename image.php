<?php
	$supported_image_types=array("jpg","jpeg","gif","png");
	
	function mbp_pi_get_image_type($filename){
		$file_info=pathinfo($filename);
		return strtolower($file_info['extension']);
	}	
	
	function mbp_pi_make_filename($path,$filename){
		if(substr($path,-1)=='/'){
			return $path.$filename;
		}else{
			return $path.'/'.$filename;
		}
	}	
	
	function mbp_pi_create_image($width,$height){
		if (function_exists("imagecreatetruecolor")){
			return imagecreatetruecolor($width,$height);
		}else{
			return imagecreate($width,$height);
		}
	}	
	
	function mbp_pi_open_image_file($path,$filename){
		$image_file_type = mbp_pi_get_image_type($filename);
		
		if($image_file_type=="gif"){
			 return imagecreatefromgif(mbp_pi_make_filename($path,$filename)); 
		}elseif($image_file_type=="png"){
			 return imagecreatefrompng(mbp_pi_make_filename($path,$filename)); 
		}elseif($image_file_type=="jpg" || $image_file_type=="jpeg"){
			 return imagecreatefromjpeg(mbp_pi_make_filename($path,$filename)); 
		}
	}

	function mbp_pi_save_image_file($path,$filename,$image_res){
		$image_file_type = mbp_pi_get_image_type($filename);
		if($image_file_type=="gif"){
			 imagegif($image_res,mbp_pi_make_filename($path,$filename));
			 @imagedestroy($image_res);
		}elseif($image_file_type=="png"){
			 imagepng($image_res,mbp_pi_make_filename($path,$filename));
			 @imagedestroy($image_res);
		}elseif($image_file_type=="jpg" || $image_file_type=="jpeg"){
			 imagejpeg($image_res,mbp_pi_make_filename($path,$filename),80);
			 @imagedestroy($image_res);
		}
	}

	function mbp_pi_resize_image($src_image,$resize_level){
		$open_image_width=imagesx($src_image);
		$open_image_height=imagesy($src_image);
		$resize_width=round($open_image_width * ($resize_level/100))!=0?round($open_image_width * ($resize_level/100)):1;
		$resize_height=round($open_image_height * ($resize_level/100))!=0?round($open_image_height * ($resize_level/100)):1;
		$new_resized_image=mbp_pi_create_image($resize_width,$resize_height);
		if (function_exists("imagecreatetruecolor")){
			if (function_exists("imagecopyresampled")){
				imagecopyresampled ($new_resized_image,$src_image,0,0,0,0,$resize_width,$resize_height,$open_image_width,$open_image_height);
			}else{
				imagecopyresized ($new_resized_image,$src_image,0,0,0,0,$resize_width,$resize_height,$open_image_width,$open_image_height);
			}
		}else{
			imagecopyresized ($new_resized_image,$src_image,0,0,0,0,$resize_width,$resize_height,$open_image_width,$open_image_height);
		} 
		return $new_resized_image;
	}

	$mode		= isset($_REQUEST['m'])?trim(strtolower($_REQUEST['m'])):'';
	$path		= isset($_REQUEST['p'])?$_REQUEST['p']:'';
	$filename	= isset($_REQUEST['f'])?$_REQUEST['f']:'';
	$level		= isset($_REQUEST['l'])?intval($_REQUEST['l']):100;

if($mode=='r'){
	$open_image=mbp_pi_open_image_file($path,$filename);
	$white = imagecolorallocate($open_image , 255, 255, 255);
	imagefill($open_image, 0, 0, $white);
	$resized_image=mbp_pi_resize_image($open_image,$level);
	header ("Content-type: image/jpeg"); 
	imagejpeg($resized_image,'',80); 
	@imagedestroy($open_image);
	@imagedestroy($resized_image);
	@imagedestroy($new_resized_image);
} elseif ($mode=='t') {
	$open_image=mbp_pi_open_image_file($path,$filename);
	$width_orig=imagesx($open_image);
	$height_orig=imagesy($open_image);
	$width  = 80;
	$height = 80;
	header('Content-type: image/jpeg');
	header('Content-Disposition: attachment; filename="thumb_'.$filename.'.jpg"');
	if ($width && ($width_orig < $height_orig)) {
	   $width = ($height / $height_orig) * $width_orig;
	} else {
	   $height = ($width / $width_orig) * $height_orig;
	}
	$image_p = mbp_pi_create_image($width, $height);
	$white 	 = imagecolorallocate($image_p , 255, 255, 255);
	imagefill($image_p, 0, 0, $white);
	imagecopyresampled($image_p, $open_image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
	imagejpeg($image_p, null, 80);	
	@imagedestroy($open_image);
	@imagedestroy($image_p);
}
?>