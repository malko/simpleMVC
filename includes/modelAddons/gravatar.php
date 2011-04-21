<?php
/**
 * modelAddon to ease retrieving of gravatar datas for users models.
 * You can override default settings for a model by declaring static public properties $_gravatarDefault, $_gravatarDefaultSize and $_gravatarEmailField
 * if none of thoose properties is specified the first field containing "mail" in the model will be used as the gravatar primary mail 
 * other parameters will default to gravatarModelAddon default values
 * @author jonathan gotti < jgotti at modedemploi dot fr> for http://www.agence-modedemploi.com
 * @since 2011-04-21
 */ 
class gravatarModelAddon extends modelAddon{
	
	static private $internals = array();
	static public $defaultSize = null;
	static public $default = 'mm';
	
	static private $sslMode = null;
	static private $url = null;
	/**
	 * @internal
	 */
	function _initModelType(){
		#- check for setted key
		$key = abstractModel::_getModelStaticProp($this->modelName,'_gravatarEmailField');
		if(! $key ){
			$datasDefs = abstractModel::_getModelStaticProp($this->modelName,'datasDefs');
			$dfltSize = abstractModel::_getModelStaticProp($this->modelName,'_gravatarDefaultSize');
			if( null === $dfltSize ){
				$dfltSize = self::$defaultSize;
			}
			$dflt = abstractModel::_getModelStaticProp($this->modelName,'_gravatarDefault');
			if( null === $dflt ){
				$dflt = self::$default;
			}
			foreach($datasDefs as $k=>$v){
				if( substr_count(strtolower($k), 'mail') ){
					self::$internals[$this->modelName] = array(
						'field'=>$k
						,'defaultSize'=>$dfltSize
						,'default'=>$dflt
						,'methods'=>array('getGravatar')
					);
					break;
				}
			}
		}
		if( self::$sslMode === null){
			self::useSSL( isset($_SERVER['HTTPS']) && (!empty($_SERVER['HTTPS'])) && $_SERVER['HTTPS'] !== 'off' ) ? true : false;
		}
	}
	/**
	 * set if we use https for generated urls.
	 * The mode is autodetected by the addon to correspond the current request setting
	 * @param bool $use
	 */
	static public function useSSL($use){
		self::$sslMode = $use;
		self::$url = (self::$sslMode?'https://secure':'http://www').'.gravatar.com/';
	} 
	/**
	 * return the gravatar hash
	 * @param string $ext
	 * @return string;
	 */
	public function getGravatarHash($ext=''){
		return md5(strtolower(trim($this->modelInstance->{self::$internals[$this->modelName]['field']}))).$ext;
	}
	/**
	 * return the url of the gravatar image
	 * @param int $size
	 * @return string
	 */
	public function getGravatar($size=null){
		$avatar = self::$url.'avatar/'.$this->getGravatarHash('.jpg');
		$params = array();
		if( self::$internals[$this->modelName]['default'] ){
			$params[]='d='.urlencode(self::$internals[$this->modelName]['default']);
		}
		if( $size || self::$internals[$this->modelName]['defaultSize'] ){
			$size = ($size?$size:self::$internals[$this->modelName]['defaultSize']);
			$params[]='s='.($size?$size:self::$internals[$this->modelName]['defaultSize']);
		}
		if( !empty($params) ){
			$avatar.='?'.implode('&',$params);
		}
		return $avatar;
	}
	/**
	 * return the html img tag of the gravatar
	 * @param int $size
	 * @return string
	 */
	public function getGravatarImg($size=null){
		return '<img src="'.$this->getGravatar($size).'" alt="gravatar" class="gravatar'.($size?" gravatar-$size":'').'"/>';
	}
	
	/**
	 * return the url of the qrcode
	 * @param int $size
	 * @return string
	 */
	public function getGravatarQrCode($size=null){
		$avatar = self::$url.$this->getGravatarHash('.qr');
		if( $size || self::$internals[$this->modelName]['defaultSize'] ){
			$size = ($size?$size:self::$internals[$this->modelName]['defaultSize']);
			$avatar.="?s=$size";
		}
		return $avatar;
	}
	/**
	 * return the html img tag of the qrcode 
	 * @param int $size
	 * @return string
	 */
	public function getGravatarQrCodeImg($size=null){
		return '<img src="'.$this->getGravatarQrCode($size).'" alt="gravatar" class="gravatar'.($size?" gravatar-$size":'').'"/>';
	}
	/**
	 * return the gravatar profile url
	 * @return string
	 */
	public function getGravatarProfileUrl(){
		return self::$url.self::getGravatarHash();
	}
	/**
	 * return the url of the gravatar vcard 
	 * @return string
	 */
	public function getGravatarVCard(){
		return self::$url.self::getGravatarHash('.vcf');
	}
	/**
	 * return the url of the json gravatar profile 
	 * @param string $callBack the javascript function to use as a jsonp callback
	 * @return string
	 */
	public function getGravatarJSON($callBack=''){
		return self::$url.self::getGravatarHash('.json').($callBack?'callback='.$callBack:'');
	}
	/**
	 * return the url of the php serialized gravatar profile
	 * @return string
	 */
	public function getGravatarPHP(){
		return self::$url.self::getGravatarHash('.php');
	}
	/**
	 * return the url of the xml gravatar profile
	 * @return string
	 */
	public function getGravatarXML(){
		return self::$url.self::getGravatarHash('.xml');
	}
}