<?php

class gd2tools{

	static public $pngFilters = PNG_NO_FILTER; // PNG_ALL_FILTERS
	static public $outputHeaders = true;
	static public $useDithering = false;

	protected $_resource = null;
	protected $_src = null;

	static private $_getter=array(
		'colorstotal' => 'imagecolorstotal',
		'colortransparent' => 'imagecolortransparent',
		'istruecolor' => 'imageistruecolor',
		'sx' => 'imagesx',
		'width' => 'imagesx',
		'sy' => 'imagesy',
		'height' => 'imagesy',
	);
	static private $_setter=array(
		'colortransparent' => 'imagecolortransparent',
		'layereffect' => 'imagelayereffect',
		'brush' => 'imagesetbrush',
		'style' => 'imagesetstyle',
		'thickness' => 'imagesetthickness',
		'tile' => 'imagesettile',

	);

	private function __construct(){ }

	static function fromRes($src){
		$i = new gd2Tools();
		return $i->load($src);
	}
	static function fromFile($src){
		$i = new gd2Tools();
		return $i->load($src);
	}
	static function getNew($w,$h,$trueColor=true){
		$i = new gd2Tools();
		return $i->init($w,$h,$trueColor);
	}


	function load($src){
		if( is_resource($src) ){
			$this->destroy();
			$this->_resource = $src;
			return $this;
		}
		if(! preg_match('!\.(png8?|gd2?|gif|jpe?g|x[bp]m)$!i',$src,$m) ){
			throw new InvalidArgumentException('unknown or unsupported image type');
		}
		$this->destroy();
		$ext = strtolower($m[1]);
		switch($ext){
			case 'png':
			case 'png8':
				$this->_resource = imagecreatefrompng($src);
				break;
			case 'jpeg':
			case 'jpg':
				$this->_resource = imagecreatefromjpeg($src);
				break;
			case 'gif': $this->_resource = imagecreatefromgif($src); break;
			case 'xpm': $this->_resource = imagecreatefromxpm($src); break;
			case 'xbm': $this->_resource = imagecreatefromxbm($src); break;
			case 'gd':  $this->_resource = imagecreatefromgd($src);  break;
			case 'gd2': $this->_resource = imagecreatefromgd2($src); break;
		}
		$this->_src = $src;
		return $this;
	}
	function init($w,$h,$trueColor=true){
		$this->destroy();
		$this->_resource = $trueColor?imagecreatetruecolor($w,$h):imagecreate($w,$h);
		$this->_src = null;
		return $this;
	}
	function destroy(){
		if( null !== $this->_resource ){
			imagedestroy($this->_resource);
			$this->_resource = null;
			$this->_src = null;
		}
	}

	/**
	* return image resource in different manner:
	* @param mixed $dest determine the output method:
	*                    - null will return the image resource
	*                    - jpg|gif|png|xbm|gd|gd2 return directly the content to the browser
	*                    - filename.[jpg|gif|png|xbm|gd|gd2] output the result to file.
	* @param bool $destroy if true then the resources will be destroyed after returned ( non-sense with null as $dest)
	* @param int $quality  0-100 based
	*/
	function output($dest=null,$destroy=false,$quality=75){
		if( null===$dest)
			return $this->_resource;
		if(! preg_match('!(jpg|gif|png|xbm|gd|gd2)$!i',$dest,$m) ){
			throw new InvalidArgumentException('unknown or unsupported output format');
		}
		$ext = $m[1];
		if( $dest === $m[1])
			$dest = null;
		switch(strtolower($ext)){
			case 'jpg':
				if( null === $dest && self::$outputHeaders)
					header('Content-type: image/jpeg');
				imagejpeg($this->_resource,$dest,$quality);
				break;
			case 'png':
				if( null === $dest && self::$outputHeaders )
					header('Content-type: image/png');
				imagepng($this->_resource,$dest,imageistruecolor($this->_resource)?9-round($quality/100*9):0,self::$pngFilters);
				break;
			case 'gif':
				if( null === $dest && self::$outputHeaders )
					header('Content-type: image/gif');
				imagegif($this->_resource,$dest);
				break;
			case 'xbm':
				if( null === $dest && self::$outputHeaders )
					header('Content-type: image/gif');
				imagexbm($this->_resource,$dest);
				break;
			case 'gd': //-- no headers at all doesn't make sense to send it to browser
				imagegd($this->_resource,$dest);
				break;
			case 'gd2': //-- no headers at all doesn't make sense to send it to browser
				imagegd2($this->_resource,$dest);
				break;
		}
		return $destroy?$this->destroy():$this;
	}

	function __call($m,$a=null){
		if( strpos($m,'_destroy_')===0){
			$destroy = true;
			$m = substr($m,9);
		}
		if( method_exists($this,$m) ){
			$res = call_user_func_array(array($this,$m),$a);
		}else{
			array_unshift($a,$this->_resource);
			$res = call_user_func_array("image$m",$a);
		}
		if( isset($destroy))
			$this->destroy();
		return is_resource($res)?new gd2Tools($res):$res;
	}

	function __get($key){
		if(isset(self::$_getter[$key])){
			return call_user_func(self::$_getter[$key],$this->_resource);
		}
		throw new UnexpectedValueException("unknown property '$key'");
	}
	function __set($key,$val){
		if(isset(self::$_setter[$key])){
			return call_user_func(self::$_setter[$key],$this->_resource,$val);
		}
		throw new UnexpectedValueException("unknown property '$key'");
	}

	###--- MANIPULATION FUNCTIONS ---###

	function colorallocate($c=null){
		if( func_num_args() >=3 ){
			$c = array();
			list($c[0],$c[1],$c[2]) = func_get_args();
		}else{
			if(null!==$c)
				$c = self::_read_color($c);
		}
		return imagecolorallocate($this->_resource,$c[0],$c[1],$c[2]);
	}
	function colorset($i,$c=null){
		if( func_num_args() >=4 ){
			$c = array();
			list($i,$c[0],$c[1],$c[2]) = func_get_args();
		}else{
			if(null!==$c)
				$c = self::_read_color($c);
		}
		return imagecolorset($this->_resource,$i,$c[0],$c[1],$c[2]);
	}
	/**
	* change the original image
	* @return $this for method chaining
	*/
	function greyscale(){
		if( $this->istruecolor){
			$w = $this->width;
			$h = $this->height;
			$colours = array();
			for ($x=0; $x<$w; $x++){
				for ($y=0; $y<$h; $y++){
					$rgb = $this->colorat($x,$y);
					$rgb = array(($rgb >> 16) & 0xFF, ($rgb >> 8) & 0xFF, $rgb & 0xFF);
					$c = ($rgb[0] + $rgb[1] + $rgb[2]) / 3;
					if(! isset($colours[$c]) )
						$colours[$c] = $this->colorallocate(array($c,$c,$c));
					$this->setpixel($x,$y,$colours[$c]);
				}
			}
		}else{
			$nbColors = $this->colorstotal;
			for($i=0; $i<$nbColors; $i++){
				$c = $this->colorsforindex($i);
				$c = ($c["red"]+$c["green"]+$c["blue"])/3;
				$this->colorset($i,$c,$c,$c);
			}
		}
		return $this;
	}

	/**
	* return new rotated image
	*/
	function rotate($degrees, $bgColor=null){
			if( null !== $bgColor ){
				$bgColor = $this->colorallocate($bgColor);
			}
			$res = imagerotate($this->_resource, $degrees, null===$bgColor?0:$bgColor);
			return self::fromRes($res);
	}
	/**
	* return new resized image but according to original ratio
	* @param int $w width
	* @param int $h height
	* @param mixed $fill the fill color for empty space.
	*                    if false then the final image size will respect the orginal ratio
	*                    else the final size will be equal to $w/$h and fill with borders of the given color.
	*                    (true will default to black color)
	*/
	function ratioResize($w,$h=null,$fill=false,$trueColor=true){
		if( null === $h)
			$h = $w;
		$ow  = $this->width;
		$oh  = $this->height;
		$w   = $this->_checkPercent($w,$ow);
		$h   = $this->_checkPercent($h,$oh);
		$ratio = $ow/$oh;
		$outRatio = $w/$h;
		if( $outRatio === $ratio){ // same ratio
			$_h = $h;
			$_w = $w;
		}else if( $outRatio > $ratio){ // new width is too long regarding new height so recalc new w
			$_w = round($h*$ratio);
			$_h = $h;
		}else{ // height is too important reduce it
			$_h = round($w*$oh/$ow);
			$_w = $w;
		}
		if( false === $fill){
			$new = gd2Tools::getNew($_w,$_h,true);
			$x = $y = 0;
		}else{
			$new = gd2Tools::getNew($w,$h,true);
			$fill = $new->colorallocate($fill);
			$new->fill(0,0,$fill);
			$x = floor(($w-$_w)/2);
			$y = floor(($h-$_h)/2);
		}
		$new->copyresized($this->_resource,$x,$y,0,0,$_w,$_h,$ow,$oh);
		if( !$trueColor){
			$new->truecolortopalette(self::$useDithering,256);
		}
		return $new;
	}
	/**
	* function return new resized image
	*/
	function resize($w,$h=null,$trueColor=true){
		if( null === $h)
			$h = $w;
		$ow  = $this->width;
		$oh  = $this->height;
		$w   = $this->_checkPercent($w,$ow);
		$h   = $this->_checkPercent($h,$oh);
		$new = gd2Tools::getNew($w,$h,true);
		$new->copyresized($this->_resource,0,0,0,0,$w,$h,$ow,$oh);
		if( !$trueColor){
			$new->truecolortopalette(self::$useDithering,256);
		}
		return $new;
	}
	/**
	* return copy of the original image resource
	*/
	function fullcopy(){
		$truecolor = $this->istruecolor;
		$w = $this->width;
		$h = $this->height;
		$new = self::getNew($w,$h,$truecolor);
		if(! $truecolor ){
			$new->palettecopy($this->_resource);
		}
		$new->copy($this->_resource,0,0,0,0,$w,$h);
		return $new;
	}
	/**
	* work on original image
	* @param int $luminosity 255 <= luminosity >= -255
	* @return this for method chaining
	*/
	function Luminosity($luminosity, $decalR=0, $decalG=0, $decalB=0){
		if( $this->istruecolor){
			$this->truecolortopalette(self::$useDithering,256);
		}
		$nbcolors = $this->colorstotal;
		for($i=0;$i<$nbcolors;$i++){
			$c = $this->colorsforindex($i);
			$luminance      = ($c["red"]+$c["green"]+$c["blue"])/3;
			$r              = max(0,min(255,$c["red"]+$decalR));
			$g              = max(0,min(255,$c["green"]+$decalG));
			$b              = max(0,min(255,$c["blue"]+$decalB));
			$luminance2     = ($r+$g+$b)/3;
			$r              = max(0,min(255,$r*($luminance/$luminance2)+3+$luminosity));
			$g              = max(0,min(255,$g*($luminance/$luminance2)+3+$luminosity));
			$b              = max(0,min(255,$b*($luminance/$luminance2)+3+$luminosity));
			$this->colorset($i,$r,$g,$b);
		}
		return $this;
	}
	/**
	* work on original resource
	* @return this for method chaining
	*/
	function tint($color){
		$color = self::_read_color($color);
		return $this->Luminosity(0,$color[0],$color[1],$color[2]);
	}
	/**
	* work on original resource
	* @return this for method chaining
	*/
  function negative(){
		if( $this->istruecolor){
			$this->truecolortopalette(self::$useDithering,256);
		}
		$nbcolors = $this->colorstotal;
		for($i=0;$i<$nbcolors;$i++){
			$c = $this->colorsforindex($i);
			$r              = min(255,255-$c["red"]);
			$g              = min(255,255-$c["green"]);
			$b              = min(255,255-$c["blue"]);
			$this->colorset($i,$r,$g,$b);
		}
		return $this;
	}
	function Sepia(){
		return $this->greyscale()->luminosity(10, 255, 60, -10);
	}

	###--- internal helper methods ---###
	protected function _checkPercent($nval,$refval){
		if( substr($nval,-1)==='%' ){
			$nval = (float) substr($nval,0,-1);
			$nval = round($nval/100*$refval);
		}
		return $nval;
	}
	/**
  * read a color in any understandable format and return it as a 255RGB array
  * @param mixed $c
  * @return array 255RGB or FALSE
  * @private
  */
  static protected function _read_color($c){
		if( is_array($c)){
      if(isset($c['red']) && isset($c['green']) &&isset ($c['blue']) ){
        $rgb[0] = $c['red'];
        $rgb[1] = $c['green'];
        $rgb[2] = $c['blue'];
      }else{
        for($i=0;$i<3;$i++){
          $rgb[$i] = (int)$c[$i];
          if($rgb[$i]<0)$rgb[$i]=0;
          if($rgb[$i]>255)$rgb[$i]=255;
        }
      }
    }elseif(is_bool($c) || null===$c){
			return array(0,0,0);
		}elseif(is_string($c)){
      $c = trim($c);
      if($c[0]==='#') # avoid the #
        $c = substr($c,1);
      # check validity of the color or consider it as black
      if(strlen($c)!=6 || !preg_match("!^[0-9a-fA-F]+$!",$c))
        return array(0,0,0);
      # get decimal values for each channel
      for($i=0;$i<3;$i++)$rgb[$i] = hexdec('0X'.substr($c,$i*2,2));
    }else{
      return array(0,0,0);;
    }
    return $rgb;
  }

}
