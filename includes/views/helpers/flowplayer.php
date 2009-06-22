<?php

class flowplayer_viewHelper extends jsPlugin_viewHelper{

	//-- path are relative to ROOT_URL
	public $requiredFiles   = array(
		'js/flowplayer/jquery.tools.min.js',
	);
	public $requiredPlugins = array('jquery');
	static public $swfPath  = 'js/flowplayer/flowplayer-3.1.1.swf';

	static private $_currentId = 0;

	/**
	* include a single flowplayer video
	* @param string $flvUrl  path to the video to embed
	* @param string $w       width  of the video
	* @param string $h       height of the video
	* @param array  $options list of options
	*                        - defaultContent : content to display waiting for user click to display something
	*                        - htmlPlaylistId : @see http://www.flowplayer.org/plugins/javascript/playlist.html
	*                                           give it the selector to the item to use as a playList rendering
	*                                           or use 'auto' to get a default html playlist returned
	*                        - autoPlay/autoBuffering set default values for thoose clip options
	*                        - any valid flowplayer options as php arrays
	*/
	function flowplayer($flvUrl,$w=null,$h=null,array $options=null){
		#- determining Id
		if( is_array($options) && isset($options['id']) )
			$id = $options['id'];
		else
			$id = self::uniqueId();

		#- determining defaultContent
		if(! isset($options['defaultContent']) ){
			$dfltContent = '';
		}else{
			$dfltContent = $options['defaultContent'];
			unset($options['defaultContent']);
		}

		#- playlist plugin
		$htmlPlayList = array('','');
		if( ! empty($options['htmlPlaylistId']) ){
			if( 'auto' === $options['htmlPlaylistId'] ){
				$htmlPlayList[0] =	"
				<div class=\"flowplayerPlaylist\" id=\"playlist_$id\">
					<!-- single playlist entry as template -->
					<a href=\"\${ url }\">\${title}<span>\${url}</span><em>\${duration} seconds</em></a>
				</div>";
			}
			$htmlPlayList[1] = "\$f('$id').playlist('#playlist_$id.flowplayerPlaylist', {loop:true});";
			unset($options['htmlPlaylistId']);
		}

		#- always set autoBuffering and autoPlay
		if( isset($options['autoBuffering']) ){
			$options['clip']['autoBuffering'] = $options['autoBuffering'];
		}elseif( isset($options['clip']) && isset($options['clip']['autoBuffering']) ){
			$options['clip']['autoBuffering'] = $options['clip']['autoBuffering'];
		}else{
			$options['clip']['autoBuffering'] = true; // @todo peut etre vérifier le comportement avec defaultcontent
		}
		if( isset($options['autoPlay']) ){
			$options['clip']['autoPlay'] = $options['autoPlay'];
		}elseif( isset($options['clip']) && isset($options['clip']['autoPlay']) ){
			$options['clip']['autoPlay'] = $options['clip']['autoPlay'];
		}else{
			$options['clip']['autoPlay'] = false; // @todo peut etre vérifier le comportement avec defaultcontent
		}

		#- making options
		if( is_string($flvUrl) ){
			$options['clip']['url'] = $flvUrl;
		}else{
			$options['playlist'] = $flvUrl;
			$options['plugins']['controls']['playlist']=true;
		}

		#- options display  (used for overlay)

		$optStr = self::_optionString($options,1);
		#- rendering player
		$this->_js_script("var options$id = $optStr;\n$('#$id').flowplayer('".ROOT_URL.'/'.self::$swfPath."',options$id);\n".$htmlPlayList[1]);
		return " <div id=\"$id\" class=\"flowPlayer\" style=\"display:block;".(empty($w)?'':"width:$w;").(empty($h)?'':"height:$h;")."\">$dfltContent</div>$htmlPlayList[0]";

	}


	function buttonOverlay($buttonSelector,$flvUrl,$w=null,$h=null,array $options=null){
		#- determining Id
		if( is_array($options) && isset($options['id']) )
			$id = $options['id'];
		else
			$id = self::uniqueId();
		$_options = array_merge((array) $options,array('display'=>'none','id'=>"flowerlayed_$id"));
		$maskedPlayer = "<div id=\"overlay_$id\" class=\"overlay\" style=\"display:none;\">". $this->flowPlayer($flvUrl,$w,$h,$_options) .'</div>';
		$this->_js_includes('js/flowplayer/overlay.css');
		$this->_js_script("
			var player_$id = $('#flowerlayed_$id').flowplayer(0);
			$('$buttonSelector').attr('rel','#overlay_$id').overlay({
				// when overlay is opened, load our player
				onLoad: function() {
					player_$id.load().play();
				},
				// when overlay is closed, unload our player
				onClose: function() {
					player_$id.unload();
				}
			});
		");
		return $maskedPlayer;
	}

	static function _optionString($opts,$indentSize=0){
		if(! is_array($opts)){
			if(! preg_match('!^\s*(\[.*\]|\{.*\}|["\'].*["\'])\s*$$!s',$opts) )
				$opts = "'$opts'";
			return $opts;
		}
		$str = array();
		$isObject = false;
		foreach($opts as $k=>$opt){
			if((! $isObject) && ! is_numeric($k) )
				$isObject = true;
			$str[]= ($isObject?"$k:":'').(is_bool($opt)?($opt?'true':'false'):self::_optionString($opt,$indentSize>0?$indentSize+1:0));
		}
		$indentStr = "\n".str_repeat("\t",$indentSize);
		$indentStrEnd = "\n".str_repeat("\t",max(0,$indentSize-1));
		$str = implode(",$indentStr",$str);
		return $isObject?'{'."$indentStr$str$indentStrEnd}":"[$indentStr$str$indentStrEnd]";
	}
}
