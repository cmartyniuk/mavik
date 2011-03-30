<?php  

/*

TODO fix zoom on bias
TODO remove old files after timeout

*/

class MavikHelper extends AppHelper { 

  var $helpers = array('Html');  
   
  function image($path, $options = array()) {
  
  	$defaults = array(
  		'thumb' => true, 
  		'width' => false,
  		'height' => false,
  		'decorate' => false /*array (
  			'group' => false,
  			'library' => 'slimbox',
  		)*/, 
  		'watermark' => false,
  		'quality' => 80,
  		'class' => false,
  		'style' => false,
  		'alt' => false,
  		'title' => false,
  		'url' => false,
  		'biasX' => false,
  		'biasY' => false,
  		'zoom' => false,
  		'upload' => Configure::read('Mavik.upload_default'),
  		'hash' => Configure::read('Mavik.hash_default'),
		);
  
  	// set up defaults
		$options = array_merge($defaults, $options);

		// confirm and set up decorate options
		if ($options['decorate']) {
		
			$decorateDefaults = array (
  			'group' => false,
  			'library' => 'slimbox',
  			'style' => false,
  			'class' => false,
  		);
	  		
			if (is_string($options['decorate'])) {

				$group = $options['decorate'];
				$options['decorate'] = array_merge($decorateDefaults, array(
	  			'group' => $group,
	  		));

			} else if (!is_array($options['decorate'])) {
			
				$options['decorate'] = $decorateDefaults;
			
			}		
		}

		// set up url options
		if ($options['url']) {
		
			if (is_string($options['url'])) {

				$url = $options['url'];
				$options['url'] = array();
				$options['url']['url'] = $url;

			}
		}

		// confirm and set up watermark options
		if ($options['watermark']) {
				
			if (!is_array($options['watermark'])) {
			
				$watermark = array();
				$watermark['path'] = $options['watermark'];	
				$options['watermark'] = $watermark;

			}

			if ($options['watermark']['path'][0] !== '/') {
				$options['watermark']['path'] = IMAGES_URL . $options['watermark']['path'];
			}
			
			$options['watermark']['realPath'] = Configure::read('App.www_root') . $options['watermark']['path'];
			if (file_exists($options['watermark']['realPath'])) {
	
				$options['watermark']['basename'] = basename($options['watermark']['realPath']);
				
				$watermarkImageSize = @getimagesize($options['watermark']['realPath']);
				
				$options['watermark'] = array_merge ($options['watermark'], array(
					'width' => $watermarkImageSize[0], 
					'height' => $watermarkImageSize[1],
					'mime' => $watermarkImageSize['mime'],
				));
	
			} else {
			
				$options['watermark'] = false; // can't watermark if the file doesn't exist

			}			
		}

		if ($path[0] !== '/') {
			$path = IMAGES_URL . $path;
		}

		$original = array('basename' => basename($path), 'ext' => pathinfo($path, PATHINFO_EXTENSION));

		// get original file info
		$realPath = Configure::read('App.www_root') . $path;
		if (file_exists($realPath)) {

			$imageSize = @getimagesize($realPath);
			$original = array_merge($original, array(
				'path' => $path, // relative to www root
				'realPath' => $realPath, 
				'width' => $imageSize[0], 
				'height' => $imageSize[1],
				'mime' => $imageSize['mime'],
				'crc' => hash_file('crc32', $realPath),
			));

		} else {
		
			return; // local copy has to exist!
			
		}
		
		// if a thumb is specified and the width or height is specified and it's bigger/smaller than the original, create or find thumb
		if (( $options['thumb'] and 
			(( $options['width'] and $options['width'] != $original['width'] ) or 
			( $options['height'] and $options['height'] != $original['height'] )) ) or 
			( $options['biasX'] or $options['biasY'] or $options['zoom'] )	) {

			// if a given dimension of the thumb is missing, do a scale to fit on the other dimension
			if (!$options['width']) 	
				$options['width'] = intval($options['height'] * $original['width'] / $original['height']); 
			
			if (!$options['height']) 
				$options['height'] = intval($options['width'] * $original['height'] / $original['width']);

			if (!$options['width'] and !$options['height']) { // if we're not here because of thumb or dims, but because of bias/zoom
				$options['width'] = $original['width'];
				$options['height'] = $original['height'];
			}

			$options['basename'] = $this->_getThumbName($original, $options);
			
			if (file_exists(Configure::read('App.www_root') . Configure::read('Mavik.thumb_cache') . DS . $options['basename'] . '_s3@')) {

				$options['src'] = $this->_getS3Path($options['basename']);
				
				return $this->_generateResult($original, $options);
					
			} else if (file_exists(Configure::read('App.www_root') . Configure::read('Mavik.thumb_cache') . DS . $options['basename'] )) {
			
				$options['path'] = Configure::read('Mavik.thumb_cache') . DS . $options['basename'];
				$options['realPath'] = Configure::read('App.www_root') . Configure::read('Mavik.thumb_cache') . DS . $options['basename'];
				$options['src'] = Configure::read('Mavik.thumb_cache') . DS . $options['basename'];
			
			} else {
			
				$result = $this->_generateThumb($original, $options);

				$options = array_merge($options, $result);
			
			}
		
		} else if ($options['watermark']) { // we're not thumbing, just watermarking

			$options['basename'] = $this->_getThumbName($original, $options);
		
			$result = $this->_generateWatermarkedImage($original, $options);

			$options = array_merge($options, $result);		
		
		} else {

			$options['basename'] = $original['basename'];
			$options['path'] = $original['path'];
			$options['realPath'] = $original['realPath'];
			$options['src'] = $options['path'];
		
		}
		
		if ($options['upload']) {

			touch(Configure::read('App.www_root') . Configure::read('Mavik.thumb_cache') . DS . $options['basename'] . '_s3');		

		}
		
		return $this->_generateResult($original, $options);

	}
	
	function _generateResult($original, $options) {
			
		// set up decoration if specified in options
		if ($options['decorate']) {
		
			if (strpos($options['src'], IMAGES_URL)===0) {
				$options['src'] = str_replace(IMAGES_URL, '', $options['src']);
			}
		
			$url = $this->_getDecoratedUrl($options);
						
			$image = $this->Html->image($options['src'], array(
				'width' => $options['width'], 
				'height' => $options['height'],
				'alt' => $options['alt'],
				'title' => $options['title'],
				'class' => $options['class'],
				'style' => $options['style'],
			));

			return $this->Html->link($image, $original['path'], $url['options']);
		
		} else {

			if (strpos($options['src'], IMAGES_URL)===0) {
				$options['src'] = str_replace(IMAGES_URL, '', $options['src']);
			}

			return $this->Html->image($options['src'], array(
				'width' => $options['width'], 
				'height' => $options['height'],
				'alt' => $options['alt'],
				'title' => $options['title'],
				'url' => $options['url']['url'],			
				'class' => $options['class'],
				'style' => $options['style'],
			));
					
		}		
	}
	
	function _getDecoratedUrl($options) {

		switch ($options['decorate']['library']) {
		
			case 'slimbox':
			
				$this->Html->script('/mavik/js/slimbox/js/slimbox2.js', array('inline' => false));
				$this->Html->css('/mavik/js/slimbox/css/slimbox2.css', null, array('inline' => false));

				$options['url']['url'] = $options['src'];
				$options['url']['options'] = array(
					'rel' => 'lightbox[' . $options['decorate']['group'] . ']',
					'alt' => $options['alt'],
					'style' => $options['decorate']['style'],
					'class' => $options['decorate']['class'],				
					'escape' => false,	
				);

				break;
			
			default;
				break;
		
		}
			
		return $options['url'];
	
	}
	
	function _getHash($name) {
		return md5($name);
	}

	function _getS3Path($hash) {
		// split this out so we can create paths later on
		return 'http://' . Configure::read('Mavik.s3_bucket') . '/' . Configure::read('Mavik.s3_root') . '/' . $hash;
	}
		
	function _getThumbName($image, $options) {

		$name = str_replace(array('/','\\',':',' ','&','?', '=', '.'), '-', $image['basename']); 

		$watermark = str_replace(array('/','\\',':',' ','&','?', '=', '.'), '-', $options['watermark']['basename']); 

		$thumbName = $name . 
			( $image['crc'] ? '-' . $image['crc'] : '') .
			( $options['width'] ? '-' . $options['width'] . 'x' . $options['height'] : '' ) . 
			( ($options['biasX'] or $options['biasY'] or $options['zoom']) ? '-x' . $options['biasX'] . 'y' . $options['biasY'] . 'z'. $options['zoom'] : '' ) . 
			( $watermark ? '-' . $watermark : '' ) . '.' . $image['ext'];

		if ($options['hash'])
			$thumbName = $this->_getHash($thumbName) . '.' . $image['ext'];
			
		return $thumbName;
			
	}

	function _generateThumb($image, $options) {
			
		// depending on this, create an object
		switch ($image['mime'])
		{
			case 'image/jpeg':
				$orig = imagecreatefromjpeg($image['realPath']);
				break;
			case 'image/png':
				$orig = imagecreatefrompng($image['realPath']);
				break;
			case 'image/gif':
				$orig = imagecreatefromgif($image['realPath']);
				break;
			default:
				// If type is not supported - return tag unchanged
				return $image;
		}
		
		// chrism - crop/resize from http://ca.php.net/manual/en/function.imagecopyresampled.php
		
		$ratio_orig = $image['width'] / $image['height'];
		$ratio_target = $options['width'] / $options['height'];
 
 		// if the target thumb size is wider or taller than the original ratio, then adjust how we'll produce the scaled copy
    if ($ratio_target > $ratio_orig) { // would be wider

			 // take the width and calc a new height
       $new_width = $options['width'];
       $new_height = $new_width/$ratio_orig;

    } elseif ($ratio_target < $ratio_orig) {

				// take the height and calc a new width
       $new_height = $options['height'];
       $new_width = $new_height*$ratio_orig;

    } else {
    
       $new_height = $options['height'];
       $new_width = $options['width'];
    
    }
   	   	
    $x_mid = $new_width/2; // + (($new_width/2) * ($options['biasX']/50));  //horizontal middle
    $y_mid = $new_height/2; // + (($new_height/2) * ($options['biasY']/50)); //vertical middle
    
		// Create  thumb
		$process = imagecreatetruecolor(round($new_width), round($new_height));

    // Process transparency
		$transparent_index = imagecolortransparent($orig);
		if ($transparent_index >= 0)
		{
			// without alpha channel
			$t_c = imagecolorsforindex($orig, $transparent_index);
  		$transparent_index = imagecolorallocate($orig, $t_c['red'], $t_c['green'], $t_c['blue']);
  		imagecolortransparent($process, $transparent_index);

	    $thumb = imagecreatetruecolor($options['width'], $options['height']);    

		} else {
			// with alpha
			imagealphablending ( $process, false );
			imagesavealpha ( $process, true );
			$transparent = imagecolorallocatealpha ( $process, 255, 255, 255, 127 );
			imagefilledrectangle ( $process, 0, 0, round($new_width), round($new_height), $transparent );
		
	    $thumb = imagecreatetruecolor($options['width'], $options['height']);

			imagealphablending ( $thumb, false );
			imagesavealpha ( $thumb, true );
		}
		
		$hOrig = $image['height']; 
		$wOrig = $image['width'];
			
		// what is the new focal point/center after bias is applied?
		
		$yCenter = $hOrig * (0.5 + $options['biasY']/200);
		$xCenter = $wOrig * (0.5 + $options['biasX']/200);

		// what is the new max height considering these new centers? (how close are we to the edges?)
		$hCopyMax = min($hOrig - $yCenter, $yCenter) * 2;
		$wCopyMax = min($wOrig - $xCenter, $xCenter) * 2;
					  		
		// if the new height/max would skew the ratio, the readjust the ratio
    if ($wCopyMax / $hCopyMax > $ratio_orig) { // new ratio would be wider?
    	
       $hCopy = $hCopyMax;
       $wCopy = $hCopy * $ratio_orig; // adjust width
    
    } elseif ($wCopyMax / $hCopyMax < $ratio_orig) { // new ratio would be taller?

       $wCopy = $wCopyMax;
       $hCopy = $wCopy / $ratio_orig;	// adjust height

    } else {
    
       $hCopy = $hCopyMax;
       $wCopy = $wCopyMax;
    	
    }

		if ($options['zoom']) {
		
			$wCopy = $wCopy * ( 1 - ($options['zoom'] / 100) );
			$hCopy = $hCopy * ( 1 - ($options['zoom'] / 100) );
		
		}
				
		// now find the top/left of the source -- snap to edges if we would cross over
		$ySrc = max(0, $yCenter - $hCopy / 2);
		$xSrc = max(0, $xCenter - $wCopy / 2);

//			imagepng($orig, JPATH_SITE . DS . $thumbPath . '0.png');

		imagecopyresampled($process, $orig, 
			0, 0, 
			$xSrc, $ySrc, 
			$new_width, $new_height, 
			$wCopy, $hCopy
		);
		
//			imagepng($process, JPATH_SITE . DS . $thumbPath . '1.png');
    
		imagecopyresampled($thumb, $process, 0, 0, ($x_mid-($options['width']/2)), ($y_mid-($options['height']/2)), 
			$options['width'], $options['height'], $options['width'], $options['height']);

//			imagepng($thumb, JPATH_SITE . DS . $thumbPath . '2.png');
		
		imagedestroy($process);

		if ($options['watermark'])
			$thumb = $this->_applyWatermark($thumb, $options['watermark']);

		$result = array();
		
		$result['basename'] = $options['basename'];
		$result['path'] = Configure::read('Mavik.thumb_cache') . DS . $result['basename'];
		$result['realPath'] = Configure::read('App.www_root') . Configure::read('Mavik.thumb_cache') . DS . $result['basename'];
		$result['src'] = Configure::read('Mavik.thumb_cache') . DS . $result['basename'];
			    		
		// Record thumb in the file
		switch ($image['mime'])
		{
			case 'image/jpeg':
				imagejpeg($thumb, $result['realPath'], $options['quality']);
				break;
			case 'image/png':
				imagepng($thumb, $result['realPath']);
				break;
			case 'image/gif':
				imagegif($thumb, $result['realPath']);
		}

		imagedestroy($orig);
		imagedestroy($thumb);
		
		return $result;

	}
	
	function _generateWatermarkedImage($image, $options) {

		// depending on this, create an object
		switch ($image['mime'])
		{
			case 'image/jpeg':
				$orig = imagecreatefromjpeg($image['realPath']);
				break;
			case 'image/png':
				$orig = imagecreatefrompng($image['realPath']);
				break;
			case 'image/gif':
				$orig = imagecreatefromgif($image['realPath']);
				break;
			default:
				// If type is not supported - return tag unchanged
				return $image;
		}
		
		if ($options['watermark'])
			$orig = $this->_applyWatermark($orig, $options['watermark']);
		
		$result['basename'] = $options['basename'];
		$result['path'] = Configure::read('Mavik.thumb_cache') . DS . $result['basename'];
		$result['realPath'] = Configure::read('App.www_root') . Configure::read('Mavik.thumb_cache') . DS . $result['basename'];
		$result['src'] = Configure::read('Mavik.thumb_cache') . DS . $result['basename'];
			    		
		// Record thumb in the file
		switch ($image['mime'])
		{
			case 'image/jpeg':
				imagejpeg($orig, $result['realPath'], $options['quality']);
				break;
			case 'image/png':
				imagepng($orig, $result['realPath']);
				break;
			case 'image/gif':
				imagegif($orig, $result['realPath']);
		}

		imagedestroy($orig);
		
		return $result;

	}

	function _applyWatermark($image, $watermark) {

		switch ($watermark['mime'])
		{
			case 'image/jpeg':
				$wm = imagecreatefromjpeg($watermark['realPath']);
				break;
			case 'image/png':
				$wm = imagecreatefrompng($watermark['realPath']);
				break;
			case 'image/gif':
				$wm = imagecreatefromgif($watermark['realPath']);
				break;
			default:
			
		}				
	
		if ($wm) {
			$wmWidth = $watermark['width'];
			$wmHeight = $watermark['height'];
			
			imagealphablending ( $image, true );
			imagesavealpha ( $image, true );

			imagecopy($image, $wm, imagesx($image) - $wmWidth, imagesy($image) - $wmHeight, 0, 0, $wmWidth, $wmHeight);
			
			imagedestroy($wm);
		}
		
		return $image;

	}		
	
}
	