<?php
/**
* helper to easily incorporate jquery based Textarea
* @package simpleMVC
* @changelog
*            - 2009-03-27 - integration of filemanager plugin for image selection and replace dialogs with jqueryUI dialogs
*/

class rte_viewHelper extends  jsPlugin_viewHelper{
	/** path relative to jQuery_viewHelper::$pluginPath/$pluginName */
	public $requiredFiles = array(
		'js/jqueryPlugins/jqueryRte/jquery.rte.js',
		'js/jqueryPlugins/jqueryRte/rte.css'
	);
	public $requiredPlugins = array(
		'filemanager'
	);
	protected $areaRegistered=false;

	function init(){
		//-- define default rte options
		if( ! defined('FRONT_URL'))
			define('FRONT_URL',ROOT_URL.'/front');
		$fmOptions = json_encode(filemanager_viewHelper::$defaultOptions);
		$this->_js_script("
		//--- display dialog for image selection inside rte ---//
		cbRteImageDialog=function(rte,range){
			if( typeof(rteImageDialog)=='undefined' ){
				rteImageDialog = $('<div id=\"dialog\" title=\"Insert an image\" class=\"ui-widget\">\
					<label for=\"rteImageSrc\">Image Path</label>\
					<input type=\"text\" name=\"rteImageSrc\" id=\"rteImageSrc\" class=\"ui-corner-all\" />\
					<label for=\"rteImageAlt\">Alternative Text</label>\
					<input type=\"text\" name=\"rteImageAlt\" id=\"rteImageAlt\"  class=\"ui-corner-all\" />\
					<label for=\"rteImageAlign\">Alignment</label>\
					<select name=\"rteImageAlign\" id=\"rteImageAlign\"class=\"ui-corner-all\">\
						<option value=\"\">normal</option><option value=\"float:left;\">left</option><option value=\"float:right;\">right</option>\
					</select>\
				</div>\
				');
				var buttons = {
					'save':function(){
						$(this).dialog('close'); //- hide dialog
						var rte = rteImageDialog.get(0).rte;
						var range = rteImageDialog.get(0).range;
						var src = $('#rteImageSrc',this).val();
						var alt = $('#rteImageAlt',this).val();
						var align = $('#rteImageAlign',this).val();
						if(src ==='')
								return false;
							var i = rte.createElement('img');
							i.src = src;
							if(align!=='')
								$(i).attr('style',align);
							if(alt!=='')
								$(i).attr('alt',alt);
							rte.insertNode(i,false,range);
						return false;
					},
					'cancel':function(){ $(this).dialog('close');return false;}
				};
				rteImageDialog.appendTo('body')
					.css({fontSize:'12px'})
					.dialog({autoOpen:false,resizable:false,buttons:buttons,width:'300px'});
				$('label',rteImageDialog).css({display:'block'})
				$('input,select',rteImageDialog).css({width:'230px'})
				$('input#rteImageSrc',rteImageDialog).filemanagerEntry($fmOptions);
			}
			var rteDg = rteImageDialog.get(0);
			rteDg.rte = rte;
			rteDg.range = range;
			rteImageDialog.dialog('open');
		}
		//--- display dialog for link selection inside rte ---//
		cbRteLinkDialog=function(rte,range){
			if( typeof(rteLinkDialog)=='undefined' ){
				rteLinkDialog = $('<div id=\"dialog\" title=\"Insert a link\" class=\"ui-widget\">\
					<label for=\"rteLinkHref\">Link url</label>\
					<input type=\"text\" name=\"rteLinkHref\" id=\"rteLinkHref\" class=\"ui-corner-all\" />\
					<label for=\"rteLinkTarget\">Link target</label>\
					<select name=\"rteLinkTarget\" id=\"rteLinkTarget\"class=\"ui-corner-all\">\
						<option></option><option>_ blank</option><option>_self</option><option>_parent</option><option>top</option>\
					</select>\
				</div>\
				');
				var buttons = {
					'save':function(){
						$(this).dialog('close'); //- hide dialog
						var rte = rteLinkDialog.get(0).rte;
						var range = rteLinkDialog.get(0).range;
						var href = $('#rteLinkHref',this).val();
						var target = $('#rteLinkTarget',this).val();
						if( href==='')
							return false;
						var a = rte.createElement('a');
						a.href = href;
						if(target)
							a.target = target;
						rte.surroundContents(a,false,range);
						return false;
					},
					'cancel':function(){ $(this).dialog('close');return false;}
				};
				rteLinkDialog.appendTo('body')
					.css({fontSize:'12px'})
					.dialog({autoOpen:false,resizable:false,buttons:buttons,width:'300px',modal:true});
				$('label',rteLinkDialog).css({display:'block'})
				$('input,select',rteLinkDialog).css({width:'230px'})
				$('input#rteLinkHref',rteLinkDialog).filemanagerEntry($fmOptions);
			}
			var rteDg = rteLinkDialog.get(0);
			rteDg.rte = rte;
			rteDg.range = range;
			rteLinkDialog.dialog('open');
		}
		$.extend($.fn.rte.defaults,{
			imgPath:'".ROOT_URL."/js/jqueryPlugins/jqueryRte/',
			//css_url:'".FRONT_URL."/views/default.css',
			classOptions:[
				['small','small'],
				['hr:spacer','spacer']
			],
			insertImage:function(e){
				var rte = e.data.rte;
				if( rte.textarea.is(':visible') )
						return false;
				var range = rte.getSelectedElement(true);
				cbRteImageDialog(rte,range);
				return false;
			},
			createLink:function(e){
				var rte = e.data.rte;
				if( rte.textarea.is(':visible') )
					return false;
				if(! rte.hasSelection()){
					alert('La selection est vide, impossible de créer un lien.');
					return false;
				}
				// we must keep trace of range before focus change under IE
				var range = rte.getSelectedElement(true);
				cbRteLinkDialog(rte,range);
				return false;
			}
		});");
	}

	/**
	* return necessary code to render a wysiwyg editor in your page.
	* @param string $areaSelector string to select textarea
	* @param mixed  $areaOption   this parameter permit you to quickly render textarea.
	*                             by default leave this null and it will look for an existing textarea in your template.
	*                             Providing some options will render the textarea with the given attribute,
	*                             for exemple rte('myArea',array('value'=>'my area content','style'=>'border:solid silver 1px'));
	*                             will return "<textarea name="myArea" style="border:solid silver 1px;">my area content</textarea>.
	*                             Provide anything other than array or null will result in returning a basic empty textarea
	*                             with empty value.
	*                             if any options are passed the $textareaSelector will be used as attribute name for the generated textarea.
	*  @return string.
	*/
	function rte($areaSelector,$areaOptions=null){
		static $fmLoaded =false;
		if($fmLoaded){
			$preStr='';
		}else{
			$preStr = $this->filemanager('rteFm',array('isDialog'=>true,'prefixValue'=>USER_DATAS_URL));
			$fmLoaded=true;
		}
		if( !$this->areaRegistered ){
			$this->areaRegistered = true;
			$this->js("$('.jqueryRte').rte();");
		}
		if( $areaOptions === null ){
			$this->areaSelectors[] = $areaSelector;
			return false;
		}
		if( ! is_array($areaOptions) ){
			return "<textarea name=\"$areaSelector\" class=\"jqueryRte\"></textarea>\n";
		}else{
			$attrs = array();
			$value = '';
			foreach($areaOptions as $k=>$v){
				if(strtolower($k)==='value' ){
					$value = $v;
					continue;
				}
				$attrs[] = $k.'="'.preg_replace('/(?<!\\\\)"/','\"',$v).'"';
			}
			return $preStr.'<textarea name="'.$areaSelector.'"'.(empty($attrs)?'':' '.implode(' ',$attrs))." class=\"jqueryRte\">$value</textarea>\n";
		}
	}

}
