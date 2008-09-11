<?php
/**
* helper to easily incorporate jquery based Textarea
* @package simpleMVC
*/

class rte_viewHelper extends  jsPlugin_viewHelper{
	/** path relative to jQuery_viewHelper::$pluginPath/$pluginName */
	public $requiredFiles = array(
		'js/jqueryPlugins/jqueryRte/jquery.rte.js',
		'js/jqueryPlugins/jqueryRte/rte.css'
	);
	public $requiredPlugins = array(
		'facebox',
		'jquery'
	);
	protected $areaRegistered=false;

	function init(){
		$iframeSrc = $this->url('uploadform','docs',array('embed'=>"'+inputId+'"),true);
		$this->js("
			function createRteFaceBox(title,content,onSubmit){
				var div = $('<div id=\"rteUrlDialog\"><h3>'+title+'</h3>'+content+'<div style=\"text-align:right\"><input type=\"submit\"></div></div>');
				$('label',div).css({width:'80px',display:'block',float:'left'});
				$('input[type=submit]',div).click(onSubmit);
				return div;
			}
			rteBrowseServer = function (inputId){
				var iframe = $('#rteIframeContainer');
				if(iframe.length){
					iframe.show();
				}else{
					$('#rteUrlDialog').after('<div id=\"rteIframeContainer\"><hr /><iframe src=\"$iframeSrc\" style=\"width:450px;height:250px;border:none;\"></iframe></div>');
				}
				return false;
			}

			rteMainBoxPro = function(rte){ return rteMainBox(rte,true);}
			rteMainBox = function(rte,pro){
				var d = rte.createElement('div');
				var toplink = '<a href=\"#haut\" title=\"retour haut de page\"><img border=\"0\" alt=\"retour haut de page\" onmouseout=\"this.src=\'".ELEMENTS_URL."/hp_off.gif\'\" onmouseover=\"this.src=\'".ELEMENTS_URL."/hp_on.gif\'\" src=\"".ELEMENTS_URL."/hp_off.gif\"/></a>';
				if( rte.hasSelection() ){
					d.className = 'contentContent';
					rte.surroundContents(d);
					$(d).wrap('<div class=content'+(pro?'Pro':'')+'></div>');
					$(d).before('<div class=top>&nbsp;</div>');
					$(d).after('<div class=bottom>'+toplink+'</div>');
				}else{
					rte.insertNode(d);
					$(d,rte.editable).replaceWith('<div class=content'+(pro?'Pro':'')+'><div class=top>&nbsp;</div><div class=contentContent>Contenu</div><div class=bottom>'+toplink+'</div></div>');
				}
			}

			// use to manage file selection callback
			$.extend($.fn.rte.defaults,{
				imgPath:'".ROOT_URL."/js/jqueryPlugins/jqueryRte/',
				css_url:'".ROOT_URL."/front/views/default.css',
				classOptions:[
					['small','small'],
					['hr:spacer','spacer']
				],
				createLink:function(e){
					var rte = e.data.rte;
					if( rte.textarea.is(':visible') )
						return false;
					if(! rte.hasSelection())
						return alert('La selection est vide, impossible de créer un lien.');
					// we must keep trace of range before focus change under IE
					var range = rte.getSelectedElement(true);
					var div = createRteFaceBox(
						'Create link',
						'<label for=\"rteUrlDialog_url\" class=\"label\">link url</label>\
						 <input type=\"text\" name=\"url\" id=\"rteUrlDialog_url\"/><br /><br />\
						<label for=\"rteUrlDialog_target\" class=\"label\">link target</label>\
						<select name=\"target\" id=\"rteUrlDialog_target\">\
							<option></option><option>_ blank</option><option>_self</option><option>_parent</option><option>top</option>\
						</select><br /><br />',
						function(e){
							var href   = $('#rteUrlDialog_url').val();
							var target = $('#rteUrlDialog_target').val();
							if( href ==='')
								return false;
							var a = rte.createElement('a');
							a.href = href;
							if(target)
								a.target = target;
							$.facebox.close();
							rte.surroundContents(a,false,range);
						}
					);
					$.facebox(div);
					return false;
				},
				insertImage:function(e){
					var rte   = e.data.rte;
					var range = rte.getSelectedElement(true);
					var div = createRteFaceBox(
						'Ajouter une image',
						'<label for=\"rteUrlDialog_src\" class=\"label\">image url</label>\
						 <input type=\"text\" name=\"src\" id=\"rteUrlDialog_src\"/> <input type=\"button\" value=\"browse\" /><br /><br />\
						<label for=\"rteUrlDialog_align\" class=\"label\">image alignment</label>\
						<select name=\"align\" id=\"rteUrlDialog_align\">\
							<option value=\"\">normal</option><option>float:left;</option><option>float:right;</option>\
						</select><br /><br />',
						function(e){
							var src   = $('#rteUrlDialog_src',div).val();
							var style = $('#rteUrlDialog_align',div).val();
							if(src ==='')
								return false;
							var i = rte.createElement('img');
							i.src = src;
							if(style!=='')
								$(i).attr('style',style);
							$.facebox.close();
							rte.insertNode(i);
						}
					);
					$('input[value=browse]',div).click(function(){rteBrowseServer('rteUrlDialog_src');});
					$.facebox(div);
					return false;
				}
			});
		");
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
			return '<textarea name="'.$areaSelector.'"'.(empty($attrs)?'':' '.implode(' ',$attrs))." class=\"jqueryRte\">$value</textarea>\n";
		}
	}

}
