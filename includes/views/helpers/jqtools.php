<?php
/**
* @class jqtools_viewHelper
* @licence LGPL
* @author author
* @since 2009-10-07
* @package simpleMVC
* @subpackage helpers
*/

class jqtools_viewHelper extends jsPlugin_viewHelper{
	public $requiredFiles = array(
		'js/flowplayer/jquery.tools.min.js'
		#- ~ 'http://cdn.jquerytools.org/1.1.2/jquery.tools.min.js',
	);
	public $requiredPlugins = array(
		'jquery'
	);
	static public $defaultsOptions = array(
		'overlay' => array(
			'autoLoad'=>false,
			'expose'  => '#888',
			'top'     => 'center',
			'left'    => 'center'
		),
		'tooltip' => array(
			'className'=>"tooltip",
			'renderStr'=>'<div id="$tipId" style="display:none;" class="$className">$tipStr</div>'
		),
		'expose'=>array(
			'color'  =>'#888',
			'opacity'=>0.7,
			'loadEvent'=>'click focus'
		)
	);

	function jqtools(){

	}

	function overlay($selector,array $options=null){
		$options = array_merge(self::$defaultsOptions['overlay'],(array) $options);
		$chainStr='';
		if($options['autoLoad']){
			$options['api'] = true;
			$chainStr.='.load()';
		}
		unset($options['autoLoad']);
		$this->view->_js_script("$('$selector').overlay(".self::_optionString($options).")$chainStr;");
	}

	function hecOverlay($content,$title='',$theme='blue'){
		$id = "hecOverlay_".self::uniqueId();
		$this->_js_includes(ELEMENTS_URL.'/overlay/'.$theme.'/style.css',true);
		$this->_jqtools_overlay("#$id",array('target'=>"#$id",'autoLoad'=>true));
		return '
		<table cellspacing="0" cellpadding="0" border="0" class="hecOverlay" id="'.$id.'" style="display:none;">
			<tr class="top"><td class="left"></td><td class="center"></td><td class="right"><div class="close">X<span></span></div></td></tr>'
			.($title?'
			<tr class="title"><td class="left"></td><td class="center">'.$title.'</td><td class="right"></td></tr>
			<tr class="titleshadow"><td class="left"></td><td class="center"></td><td class="right"></td></tr>':'').'
			<tr class="content"><td class="left"></td><td class="center">'.$content.'</td><td class="right"></td></tr>
			<tr class="bottom"><td class="left"></td><td class="center"></td><td class="right"></td></tr>
		</table>
		';
	}

	function expose($selector,array $options=null){
		$options= array_merge(self::$defaultsOptions['expose'],(array) $options);
		$events = $options['loadEvent']; unset($options['loadEvent']);
		$this->_js_script("$('$selector').bind('$events',function(){ $(this).expose(".self::_optionString($options).").load();});");
	}
	function flashembed(){

	}
	function scrollable(){

	}
	function tabs(){ //-- will conflict with jquery-ui

	}
	function tooltip($selector,$tipStr,array $options=null){
		$tipId  = 'jqToolTip_'.self::uniqueId();
		$options= array_merge(self::$defaultsOptions['tooltip'],(array) $options);
		$options['tip'] = "#$tipId";
		$className = $options['className']; unset($options['className']);
		$renderStr = $options['renderStr']; unset($options['renderStr']);
		$this->_js_script("$('$selector').tooltip(".self::_optionString($options).").dynamic();");
		return eval ("return <<<heredoc\n$renderStr\nheredoc;\n");
	}
}