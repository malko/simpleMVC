<?php


class codemirror_viewHelper extends jsPlugin_viewHelper{

	public $requiredFiles   = array(
		'js/CodeMirror-0.66/js/codemirror.js',
		#- 'js/CodeMirror-0.65/css/docs.css'
	);

	static public $defaultOptions = array(
		'lineNumbers' => true,
		'tabMode' => 'shift',
		'textWrapping'=>false,
		'path' => '/js',
    'height' => "550px",
    'width' => "100%",
		'iframeClass' => 'codeMirrorFrame'
		//'continuousScanning' => 500


	);

	static public $languagueOpts = array(
		'css' => array(
			'parserfile' => "parsecss.js",
			'stylesheet' => "css/csscolors.css",
		),
		'js'=> array(
			'parserfile' =>  array("tokenizejavascript.js", "parsejavascript.js"),
	    'stylesheet' =>  "css/jscolors.css",
		),

		'php' => array(
			'parserfile' => array(
				"parsexml.js", "parsecss.js", "tokenizejavascript.js", "parsejavascript.js",
				"../contrib/php/js/tokenizephp.js", "../contrib/php/js/parsephp.js",
				"../contrib/php/js/parsephphtmlmixed.js"
			),
			'stylesheet' => array(
				"css/xmlcolors.css", "css/jscolors.css",
				"css/csscolors.css", "contrib/php/css/phpcolors.css"
			)
		),
		'xml'=>array(
			'parserfile'=> 'parsexml.js',
			'stylesheet' => 'css/xmlcolors.css',
		)
	);

	function init(){
		self::$defaultOptions['path'] = ROOT_URL.'/js/CodeMirror-0.66/js/';
		foreach(self::$languagueOpts as &$lopts){
			if(! is_array($lopts['stylesheet'])){
				$lopts['stylesheet'] = 	ROOT_URL.'/js/CodeMirror-0.66/'.$lopts['stylesheet'];
			}else{
				foreach($lopts['stylesheet'] as &$s){
					$s = ROOT_URL.'/js/CodeMirror-0.66/'.$s;
				}
			}
		}
	}

	function codemirror($name,$value=null,array $options=null){
		if( empty($options['id']) ){
			$id = jsPlugin_viewHelper::uniqueId();
		}else{
			$id = $options['id'];
			unset($options['id']);
		}
		if( empty($options['class']) ){
			$class = '';
		}else{
			$class = "class=\"$options[class]\"";
			unset($options['class']);
		}
		$this->fromTextArea($id,$options);
		return "<textarea id=\"$id\" name=\"$name\" $class>$value</textarea>";

	}

	function fromTextArea($textareaId,array $options=null){
		if( empty($options['language'])){
			$lang = 'js';
		}else{
			$lang = $options['language'];
			unset($options['language']);
		}
		$options = array_merge(self::$defaultOptions,self::$languagueOpts[$lang],(array) $options);
		$this->_js_script("CodeMirror.fromTextArea('$textareaId',".self::_optionString($options).");");
	}

}

/*

var editor = CodeMirror.fromTextArea('code', {
        height: "350px",
        parserfile: ["parsexml.js", "parsecss.js", "tokenizejavascript.js", "parsejavascript.js",
                     "../contrib/php/js/tokenizephp.js", "../contrib/php/js/parsephp.js",
                     "../contrib/php/js/parsephphtmlmixed.js"],
        stylesheet: ["../../css/xmlcolors.css", "../../css/jscolors.css", "../../css/csscolors.css", "css/phpcolors.css"],
        path: "../../js/",
        continuousScanning: 500
      });



CodeMirror.fromTextArea(elmt.get(0), {
						parserfile: "parsecss.js",
						stylesheet: "css/csscolors.css",
						path: "js/",
						lineNumbers:true,
						tabMode:'shift',
						textWrapping:false,
						saveFunction:function(){
							tkWidget.computeStyle('inject');
							return false;
						},
						initCallback:function(){
							tkWidget._applyOpts('content');
							$(tkWidget.editor.win.document).keypress(function(e){tkWidget.shortCutKey(e)});
							tkWidget.editor.focus();
						}
					}*/