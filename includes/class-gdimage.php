<?php
/**
* @changelog 
* - 2010-12-02 - add $transparentBackground to getNew() method
*              - add chainedMethods alphablending,savealpha,truecolortopalette
*              - add fill() method
*              - rewrite greyScale(),negative() methods to use imagefilter
*              - colorScale() is totally rewrited
*              - add $copyType parameter to copyTo()
*              - _read_color can now handle hex colors with only 3 (or 4 with alpha) values like #f00 for red
* - 2010-09-22 - add getter for ratio
*              - add some resampled paramters for copy methods
*              - now resize methods will respect original ratio when height or width is passed to null
*/
class gdImage{

	static public $pngFilters = PNG_NO_FILTER; // PNG_ALL_FILTERS
	static public $outputHeaders = true;
	static public $useDithering = false;

	protected $_resource = null;
	protected $_src = null;

	static private $_chainedMethods=array(
		'alphablending',
		'savealpha',
		'truecolortopalette',
	);

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
		$i = new gdImage();
		return $i->load($src);
	}
	static function fromFile($src){
		$i = new gdImage();
		return $i->load($src);
	}
	static function getNew($w,$h=null,$trueColor=true,$transBackground=true){
		$i = new gdImage();
		$i->init($w,null!==$h?$h:$w,$trueColor);
		if( $transBackground ){
			$i->fill();
		}
		return $i;
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
				if(! $this->_resource){
					echo "$src\nmake an error\n";exit();
				}

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

	/**
	* allow direct call to imagexxx method
	* if prefixed by _destroy_ the resource will be freed after method call (and so the image resource will become unusable anymore)
	*/
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
		return in_array(strtolower($m),self::$_chainedMethods,true)?$this:(is_resource($res)?new gdImage($res):$res);
	}

	function __get($key){
		if(isset(self::$_getter[$key])){
			return call_user_func(self::$_getter[$key],$this->_resource);
		}
		if( $key === 'ratio'){
			return $this->width/$this->height;
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
	function fill($x=0,$y=0,$c='#0000007f'){
		if( is_array($c) || (is_string($c) && $c[0] === '#') ){
			$c = $this->colorallocatealpha($c);
		}
		imagefill($this->_resource,$x,$y,$c);
		return $this;
	}
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
	function colorallocatealpha($c=null){
		if( func_num_args() >=4 ){
			$c = array();
			list($c[0],$c[1],$c[2],$c[3]) = func_get_args();
		}else{
			if(null!==$c)
				$c = self::_read_color($c);
		}
		return imagecolorallocatealpha($this->_resource,$c[0],$c[1],$c[2],isset($c[3])?$c[3]:0);
	}
	function colorset($i,$c=null){
		if( func_num_args() >=4 ){
			$c = array();
			if( func_num_args()>=5)
				list($i,$c[0],$c[1],$c[2],$c[3]) = func_get_args();
			else
				list($i,$c[0],$c[1],$c[2]) = func_get_args();
		}else{
			if(null!==$c)
				$c = self::_read_color($c);
		}
		#- return isset($c[3])?imagecolorset($this->_resource,$i,$c[0],$c[1],$c[2],$c[3]):imagecolorset($this->_resource,$i,$c[0],$c[1],$c[2]);
		return imagecolorset($this->_resource,$i,$c[0],$c[1],$c[2]);
	}
	/**
	* change the original image
	* @return $this for method chaining
	*/
	function greyscale(){
		imagefilter($this->_resource,IMG_FILTER_GRAYSCALE);
		return $this;
	}
	function colorScale($color){
		imagefilter($this->_resource,IMG_FILTER_GRAYSCALE);
		$color = self::_read_color($color);
		$luminance=($color[0]+$color[1]+$color[2])/3;
		$brightnessCorrection = $luminance/3;
		if( $luminance < 127 ){
			$brightnessCorrection -= 127/3;
		}
		if(! $this->istruecolor ){
			$nbColors = $this->colorstotal;
			for($i=0; $i<$nbColors; $i++){
				$c = array_values($this->colorsforindex($i));
				for($y=0;$y<3;$y++){
					$c[$y] = max(0,min(255,$c[$y]+($color[$y]-$luminance)+$brightnessCorrection));
				}
				$this->colorset($i,$c);
			}
		}else{
			imagefilter($this->_resource,IMG_FILTER_COLORIZE,$color[0]-$luminance,$color[1]-$luminance,$color[2]-$luminance,isset($color[3])?$color[3]:null);
			imagefilter($this->_resource,IMG_FILTER_BRIGHTNESS,$brightnessCorrection);
		}
		return $this;
	}
	function array_color_shade($startcolor,$endcolor,$steps=16){
		# first take colors to correct format
		$startcolor = gdImage::_read_color($startcolor);
		$endcolor = gdImage::_read_color($endcolor);
		$scale[0] = $startcolor;
		$scale[$steps-1] = $endcolor;

		$channels=count($startcolor);
		$gd = array('red','green','blue','alpha');
		for($i=0;$i<$channels;$i++)$diffs[$i] = $scale[$steps-1][$i] - $scale[0][$i]; # get difference for each canal
		# show($diffs,maroon,0,1);
		#now make the final array
		for($i=1;$i<$steps-1;$i++){
			for($y=0;$y<$channels;$y++)$scale[$i][$y] = intval($scale[0][$y]+$diffs[$y]*$i/$steps);
		}
		ksort($scale);
		return $scale;
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
	* @param int $w width (may be null if $height is given, may be exprimed as %)
	* @param int $h height (if null will be calculated respectively from original ratio, may be exprimed as %)
	* @param mixed $fill the fill color for empty space.
	*                    if false then the final image size will respect the orginal ratio
	*                    else the final size will be equal to $w/$h and fill with borders of the given color.
	*                    (true will default to black color)
	* @param bool $resampled if true will use imagecopyresampled instead of imagecopyresized
	* @param bool $trueColor force new image to true color or not (default value null will use current value of istruecolor)
	* @return new gdImage
	*/
	function ratioResize($w,$h=null,$fill=false,$resampled=false,$trueColor=null){
		if( null === $h){
			$h = $w/$this->ratio;
		}else if( null === $w){
			$w = $h*$this->ratio;
		}
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
			$new = gdImage::getNew($_w,$_h,true);
			$x = $y = 0;
		}else{
			$new = gdImage::getNew($w,$h,true);
			$fill = $new->colorallocate($fill);
			$new->fill(0,0,$fill);
			$x = floor(($w-$_w)/2);
			$y = floor(($h-$_h)/2);
		}
		if( $resampled){
			$new->copyresampled($this->_resource,$x,$y,0,0,$_w,$_h,$ow,$oh);
		}else{
		$new->copyresized($this->_resource,$x,$y,0,0,$_w,$_h,$ow,$oh);
		}
		if( null===$trueColor){
			$trueColor = $this->istruecolor;
		}
		if( !$trueColor){
			$new->truecolortopalette(self::$useDithering,256);
		}
		return $new;
	}
	/**
	* function return new resized image
	* @param int $w width (may be null if $height is given, may be exprimed as %)
	* @param int $h height (if null will be calculated respectively from original ratio, may be exprimed as %)
	* @param bool $resampled if true will use imagecopyresampled instead of imagecopyresized
	* @param bool $trueColor force new image to true color or not (default value null will use current value of istruecolor)
	* @return new gdImage
	*/
	function resize($w,$h=null,$resampled=false,$trueColor=null){
		if( null === $h){
			$h = $w/$this->ratio;
		}else if( null === $w){
			$w = $h*$this->ratio;
		}
		$ow  = $this->width;
		$oh  = $this->height;
		$w   = $this->_checkPercent($w,$ow);
		$h   = $this->_checkPercent($h,$oh);
		$new = gdImage::getNew($w,$h,true);
		if( $resampled ){
			$new->copyresampled($this->_resource,0,0,0,0,$w,$h,$ow,$oh);
		}else{
		$new->copyresized($this->_resource,0,0,0,0,$w,$h,$ow,$oh);
		}
		if( null===$trueColor){
			$trueColor = $this->istruecolor;
		}
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
	* copy current image to the given resource
	* @param mixed $to can be filename, resource or gdImage object
	* @return gdImage $to;
	*/
	function copyTo($to,$toX=0,$toY=0,$fromX=0,$fromY=0,$width=null,$height=null,$copyType=null){
		if(! $to instanceof self){
			$i = new gdImage();
			$to = $i->load($to);
		}
		switch($copyType){
			case 'merge':
				$res = imagecopymerge($to->output(null),$this->_resource,$toX,$toY ,$fromX,$fromY,null!==$width?$width:$this->width, null!==$height?$height:$this->height,100);
				break;
			case 'resampled':
				$res =  imagecopyresampled($to->output(null),$this->_resource,$toX,$toY, $fromX,$fromY, null!==$width?$width:$this->width, null!==$height?$height:$this->height,null!==$width?$width:$this->width, null!==$height?$height:$this->height);
				break;
			case 'resized':
				$res = imagecopyresized($to->output(null),$this->_resource,$toX,$toY ,$fromX,$fromY,null!==$width?$width:$this->width, null!==$height?$height:$this->height,null!==$width?$width:$this->width, null!==$height?$height:$this->height);
				break;
			default:
				$res = imagecopy($to->output(null),$this->_resource,$toX,$toY ,$fromX,$fromY,null!==$width?$width:$this->width, null!==$height?$height:$this->height);
		}
		return $to;
	}
	/**
	* copy from given image to the current one
	* @param mixed $from can be filename, resource or gdImage object
	* @return $this;
	*/
	function copyFrom($from,$toX=0,$toY=0,$fromX=0,$fromY=0,$width=null,$height=null,$copyType=null){
		if( ! is_resource($from) ){
			if( $from instanceof self){
				$from = $from->output(null);
			}else{
				$i = new gdImage();
				$from = $i->load($from)->output(null);
			}
		}
		switch($copyType){
			case 'merge':
				$res = imagecopymerge($this->_resource,$from,$toX,$toY ,$fromX,$fromY,null!==$width?$width:imagesx($from), null!==$height?$height:imagesy($from),100);
				break;
			case 'resampled':
				$res =  imagecopyresampled ($this->_resource,$from,$toX,$toY, $fromX,$fromY, null!==$width?$width:imagesx($from), null!==$height?$height:imagesy($from),null!==$width?$width:imagesx($from), null!==$height?$height:imagesy($from));
				break;
			case 'resized':
				$res = imagecopyresized($this->_resource,$from,$toX,$toY ,$fromX,$fromY,null!==$width?$width:imagesx($from), null!==$height?$height:imagesy($from),null!==$width?$width:imagesx($from), null!==$height?$height:imagesy($from));
				break;
			default:
				$res = imagecopy($this->_resource,$from,$toX,$toY ,$fromX,$fromY,null!==$width?$width:imagesx($from), null!==$height?$height:imagesy($from));
		}
		return $this;
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
		imagefilter($this->_resource,IMG_FILTER_NEGATE);
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
				if( isset($c['alpha']) )
					$rgb[3] = $c['alpha'];
      }else{
        for($i=0;$i<3;$i++){
          $rgb[$i] = (int)$c[$i];
          if($rgb[$i]<0)$rgb[$i]=0;
          if($rgb[$i]>255)$rgb[$i]=255;
        }
				if( isset($c[3]) )
					$rgb[3] = $c[3];
      }
    }elseif(is_bool($c) || null===$c){
			return array(0,0,0);
		}elseif(is_string($c)){
      $c = trim($c);
      if($c[0]==='#') # avoid the #
        $c = substr($c,1);
			$cLength = strlen($c);
      # check validity of the color or consider it as black
			if((! in_array($cLength,array(3,4,6,8))) || !preg_match("!^[0-9a-fA-F]+$!",$c))
        return array(0,0,0);
      # get decimal values for each channel
			if( $cLength > 4){
				$c = explode("\n",chunk_split($c,2,"\n"));
				array_pop($c);
				foreach($c as $k=>$v){
					$rgb[$k] = hexdec("0X$v");
				}
			}else{
				$c = explode("\n",chunk_split($c,1,"\n"));
				array_pop($c);
				foreach($c as $k=>$v){
					$rgb[$k] = hexdec("0X$v$v");
				}
			}
    }else{
      return array(0,0,0);;
    }
    return $rgb;
  }

}
