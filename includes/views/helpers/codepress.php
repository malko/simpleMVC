<?php

class codePress_viewHelper extends jsPlugin_viewHelper{
	public $requiredFiles = array(
		'js/codepress/codepress.js'
	);
	public $requiredPlugins = array(
		'jquery'
	);
	static $defaultOpts   = array(
		'language' => 'php',
		'autocomp' => 'autocomplete-off',
		'rows'     => 25,
		'cols'     => 75,
		'toolSet'     => 'toggleEditorlanguage|lineNumbers|autoComp|readOnly',
	);

	function codepress($name,$value=null,array $options=null){
		$options = array_merge(self::$defaultOpts,(array) $options);
		$toolBar = '';
		if( isset($options['toolSet']) ){
			if( strpos($options['toolSet'],'language') !== false ){
				$langs = array(
					'generic'=>'Generic', 'csharp'=>'C#', 'css'=>'CSS', 'html'=>'HTML',
					'java'=>'Java', 'javascript'=>'JavaScript', 'perl'=>'Perl',
					'ruby'=>'Ruby', 'php'=>'PHP', 'text'=>'Text', 'sql'=>'SQL','vbscript'=>'VBScript'
				);
				foreach($langs as $k=>$v){
					if( $k === $options['language'] || $v === $options['language'])
						$selected = ' selected="selected"';
					else
						$selected = '';
					$toolBar .= "<option value=\"$k\"$selected>$v</option>";

				}
				$toolBar = "
					<select onchange=\"CodePressSetLang($name,this.value);\">
					$toolBar
					</select>
				";
			}
			if( false !== strpos($options['toolSet'],'lineNumbers'))
				$toolBar .= "<button onclick=\"$name.toggleLineNumbers();return false;\">show/hide line numbers</button>";

			if( false !== strpos($options['toolSet'],'autoComp'))
				$toolBar .= "<button onclick=\"$name.toggleAutoComplete();return false;\">turn on/off auto-complete</button>";

			if( false !== strpos($options['toolSet'],'readOnly'))
				$toolBar .= "<button onclick=\"$name.toggleReadOnly();return false;\">turn on/off read only</button>";

			if( false !== strpos($options['toolSet'],'toggleEditor'))
				$toolBar .= "<button onclick=\"$name.toggleEditor();return false;\">turn on/off Editor</button>";

			if( $toolBar )
				$toolBar = "<div class=\"cpToolBar\">$toolBar</div>";
		}
		$this->js("
		/** make codepress toggleEditor at submit time */
		var cpElmt = $('#$name');
		CodePressSetLang = function (cp,language) {
			cp.textarea.value = cp.getCode();
			if(!cp.textarea.disabled) return;
			cp.language = language ? language : cp.getLanguage();
			cp.src = CodePress.path+'codepress.html?language='+cp.language+'&ts='+(new Date).getTime();
			if(cp.attachEvent) cp.attachEvent('onload',cp.initialize);
			else cp.addEventListener('load',cp.initialize,false);
		}

		var p;
		while( undefined !== ( p = cpElmt.parent())){
			if( p.attr('tagName') != 'FORM'){
				cpElmt = p;
				continue;
			}
			p.submit(function(){".$name.".toggleEditor(); return true;});
			break;
		}
		");
		return "$toolBar<textarea name=\"$name\" id=\"$name\" class=\"codepress $options[language]\" rows=\"$options[rows]\" cols=\"$options[cols]\">$value</textarea>";
	}
}
