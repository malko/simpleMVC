<?php
	#- set editor id (will be also used as part as the storage key)
	if( ! empty($_GET['editorId']) ){
		$editorId =$_GET['editorId'];
	}elseif(!empty($_SERVER['HTTP_REFERER'])){
		$editorId = md5(strpos($_SERVER['HTTP_REFERER'],'/',10)?dirname($_SERVER['HTTP_REFERER']):$_SERVER['HTTP_REFERER']);
	}else{
		$editorId = 'cssEditor';
	}

	//-- saving rawContent
	if( !empty($_POST['rawContent']) ){
		echo file_put_contents($_POST['path'].$_POST['id'].'.rawCss',$_POST['rawContent'])?'rawContent saved':'failed';
		exit;
	}
	//-- saving computedContent
	if( !empty($_POST['compContent']) ){
		echo file_put_contents($_POST['path'].$_POST['id'].'.css',$_POST['compContent'])?'computedContent saved':'failed';
		exit;
	}
	//-- loading saved rawContent
	if( !empty($_POST['getRawContent']) ){
		$rawFile = $_POST['path'].$_POST['id'].'.rawCss';
		if( !file_exists($rawFile)){
			exit(-1);
		}else{
			echo file_get_contents($rawFile);
			exit;
		}
	}
?>
<html>
<head>
	<!--link rel="stylesheet" href="../BespinEmbedded-0.5.2/BespinEmbedded.css" type="text/css" />
	<script src="../BespinEmbedded-0.5.2/BespinEmbedded.js" type="text/javascript"></script-->
	<script src="../jquery.js" type="text/javascript"></script>
	<script src="../jquery.toolkit/src/jquery.toolkit.js" type="text/javascript"></script>
	<script src="../jquery.toolkit/src/jquery.toolkit.storage.js" type="text/javascript"></script>
	<script src="../jquery.toolkit/src/plugins/notify/jquery.tk.notify.js" type="text/javascript"></script>
	<script src="js/codemirror.js" type="text/javascript"></script>
	<title>cssEditor</title>
	<link rel="stylesheet" type="text/css" href="../jquery.toolkit/src/jquery.toolkit.css"/>
	<link rel="stylesheet" type="text/css" href="../jquery.toolkit/src/plugins/notify/jquery.tk.notify.css"/>
	<link rel="stylesheet" type="text/css" href="css/docs.css"/>
	<style>
		body,div{
			margin:0;padding:0;
			font-size:10pt;
		}
		.CodeMirror-line-numbers{
			background:#eee;
			margin:0;
			padding:.4em;
			font-family: monospace;
			font-size: 10pt;
			color: black;
		}
		.CodeMirror-wrapping iframe{
			background:#f9f9f9 !important;
			margin:0;padding:0;
			width:100%!important;
		}
		.tk-cssEditor{
			background:#eee;
			border:solid black 1px;
			padding:0 1.7em 0 0;
			width:98%;
			height:98%
		}
	</style>
</head>
<body>
<div id="<?=$editorId?>" class="tk-cssEditor"></div>
<script>
(function($){

	$.toolkit('tk.cssEditor',{
		_storableOptions:{
			elementLevel:'content|rawFilePath|compFilePath'
		},

		_init:function(){
			// create elements
			var self = this,
				id = this.elmt.attr('id'),
				areaTag = $.tk.cssEditor.editorApis[this.options.editorApi].areaTag;
			// first add the editor area
			self._area = $('<'+areaTag+' id="cssEditorArea_'+id+'"></'+areaTag+'>')
				.append(self.elmt.contents())
				.appendTo(self.elmt);
			if( ! self.options.content ){
				self.options.content = self._area.text();
			}
			self._applyOpts('editorCss');
			//-- making toolbar
			self._toolbar = $('<div id="cssEditorBar_'+id+'" class="tk-cssEditor-toolbar"></div>').prependTo(self.elmt);

			var bts = [
				['load saved raw',function(){self.loadRawContent();}],
				['save raw',function(){self.saveRawContent();}],
				['save computed',function(){self.saveComputedContent();}],
				['export',function(){self.computeStyle('export');}],
				['render',function(){self.computeStyle('inject');}]
			],l=bts.length,i,bt;
			for( i=0;i<l;i++){
				$('<button type="button">'+(bts[i][0])+'</button>').click(bts[i][1]).appendTo(self._toolbar);
			}

			// self.editor = $.tk.cssEditor.editorApis[self.options.editorApi].init.call(self,self._area);
			$.tk.cssEditor.editorApis[self.options.editorApi].init.call(self,self._area);
			// if notify plugin is present then initialise it
			if( $.tk.notifybox ){
				self.notify = $.tk.notifybox.initDefault({vPos:'top',hPos:'right'});
				self.notify.notifybox('msg','<div class="tk-state-success">editor initialized.</div>');
			}
			// define posts callBacks
			self._loadRawContentResponse= function(data,status){
				if(status==='success' && data.length){
					self.set('content',data);
					if( self.notify){
						self.notify.notifybox('msg','<div class="tk-state-info">raw content loaded</div>');
					}
				}
			};
			self._saveRawContentResponse= function(data,status){
				if( self.notify){
					self.notify.notifybox('msg',data);
				}
			};
			self._saveComputedContentResponse= function(data,status){
				window.opener.location.reload();
				if( self.notify){
					self.notify.notifybox('msg',data);
				}
			};
		},

		_get_content:function(c){
			return $.tk.cssEditor.editorApis[this.options.editorApi].getContent.call(this.editor);
		},
		_set_content:function(c){
			return $.tk.cssEditor.editorApis[this.options.editorApi].setContent.call(this.editor,c);
		},
		_set_editorCss:function (css){
			this._area.css(css);
		},
		_saveContent:function(){
			var c = this.get('content');
			if((! this.options.disableStorage) && $.toolkit.storage && $.toolkit.storage.enable() ){
				$.toolkit.storage.set(this._tk.pluginName+'_content_'+this.elmt.attr('id'),c);
			}
			return c;
		},
		computeStyle: function(action){
			if( typeof(action)!=='string')
				action = 'inject';
			var self = this,
				c = this._saveContent(),
				// lecture des variables: on parse les commentaires en début de fichier
				toParse = c.replace(/^\s*\/\*+([\s\S]*?)\*+\/[\s\S]*$/,'$1').replace(/^@([a-zA-Z_]+[a-zA-Z_0_9]*)\s*=/mg,'var $1 ='),
				out = c.substr(c.indexOf('*/')+2);//- remove first comment
			// read defined rules if any

			try{
				eval(toParse);
			}catch(e){
				if( self.notify){
					self.notify.notifybox('msg','<div class="tk-state-error">'+e+'</div>');
				}else{
					alert(e);
				}
				return false;
			};
			// now apply them
			out = out.replace(/@([a-zA-Z_]+[a-zA-Z_0_9]*)(\(.*?\))?/g,function(m,name,params){
				var r = '';
				try{
					if( !params ){
						r = eval(name);
					}else{
						eval('r='+name+params);
					}
				}catch(e){
					if( self.notify){
						self.notify.notifybox('msg','<div class="tk-state-error">'+e+'</div>');
					}else{
						alert(e);
					}
					return m;
				}
				return r;
			});
			switch(action){
				case 'export':
					var w = window.open('','cssEditorComputedStyleExport','toolbar=no,statusbar=no,scrollbars=yes');
					w.document.write('<pre>'+out+'</pre>');
					break;
				case 'inject':
					// check for a computed style element in parent if none create it
					var parentHead = $('head',window.opener.document),
						s = $('style#cssEditorComputedStyle',parentHead),
						l = $('link[href$='+self.elmt.attr('id')+'.css]',parentHead);
					cssPath = window.location.href.replace(/[^\/]*$/,'')+self.options.compFilePath;
					out = out.replace(/url\((\.\/|)?(?!http:\/\/)/g,'url('+cssPath+(cssPath.substr(-1)==='/'?'':'/'));
					if( l.length)
						l.remove();
					if( s.length)
						s.remove();
					if($.support.style){
						s = $('<style id="cssEditorComputedStyle" type="text/css"></style>').text(out);
					}else{ // ie version
						s = window.opener.document.createElement('STYLE');
						s.setAttribute('type','text/css');
						s.styleSheet.cssText = out;
						s =$(s);
					}
					s.appendTo(parentHead)
					break;
				case 'return':
					this.editor.focus();
					return out;
					break;
			}
			this.editor.focus();
		},
		loadRawContent: function(){
			var self = this,
				datas = {
					id:        self.elmt.attr('id'),
					path:      self.get('rawFilePath'),
					getRawContent:true
				};
			jQuery.post(document.location.href,datas,self._loadRawContentResponse,'text');
		},
		saveRawContent: function(){
			this._saveContent();
			var self = this,
				datas = {
					id:        self.elmt.attr('id'),
					path:      self.get('rawFilePath'),
					rawContent:self.get('content')
				};
			jQuery.post(document.location.href,datas,self._saveRawContentResponse,'text');
		},
		saveComputedContent: function(){
			this._saveContent();
			var self = this,
				datas = {
					id:        self.elmt.attr('id'),
					path:      self.get('compFilePath'),
					compContent:self.computeStyle('return')
				};
			if( false!==datas.compContent){
				jQuery.post(document.location.href,datas,self._saveComputedContentResponse,'text');
			}
		},
		//-- manage some shortcut keys
		shortCutKey:function(e){
			//dbg(e.which,e.ctrlKey)
			if(! e.ctrlKey)
				return true;
			var ed = this.editor;

			switch(e.which){
				case 100://ctrl+d  duplicate line or selection
					var s = ed.selection();
					if( s.length ){
						ed.replaceSelection(s+''+s);
					}else{
						var l = ed.nthLine(ed.currentLine());
						s = ed.lineContent(l);
						ed.setLineContent(l,s+'\n'+s);
					}
					e.preventDefault();
					ed.focus();
					return false;
					break;
				case 115: //s
				case 83:
					if( e.shiftKey){
						this.saveRawContent();
						this.saveComputedContent();
						e.preventDefault();
						return false;
					}
					break;
			}
			return true;
		},
	});
	$.tk.cssEditor.defaults={
		editorApi: 'codeMirror',
		editorCss:{
			width:'100%',
			minHeight:'350px',
			height:'95%'
		},
		content:null,
		rawFilePath:'../../',
		compFilePath:'../../'
	}
	$.tk.cssEditor.editorApis = {
		bespin: {
			areaTag:'div',
			init: function(elmt){
				var tkWidget = this;
				var embed = tiki.require("bespin:embed");
				tkWidget.editor = embed.useBespin(elmt.attr('id'),{
					stealFocus: true,
					settings: {
						tabsize:2,
						//syntax:'css',
						fontsize:'12',
						//autoindent:'on',
						highlightline:'on',
						strictlines:'on',
						tabmode:'tabs'
					}
				});
				tkWidget._applyOpts('content');
			},
			getContent: function(){
				return this.getContent();
			},
			setContent: function(c){
				return this.setContent(c);
			}
		},
		codeMirror:{
			areaTag:'textarea',
			init: function(elmt){
				var tkWidget = this;
				tkWidget.editor = CodeMirror.fromTextArea(elmt.get(0), {
						parserfile: "parsecss.js",
						stylesheet: "css/csscolors.css",
						path: "js/",
						lineNumbers:true,
						tabMode:'shift',
						textWrapping:false,
						saveFunction:function(){
							tkWidget.computeStyle('inject');
						},
						initCallback:function(){
							tkWidget._applyOpts('content');
							$(tkWidget.editor.win.document).keypress(function(e){tkWidget.shortCutKey(e)});
						}
					});
			},
			getContent: function(){
				return this.getCode();
			},
			setContent: function(c){
				return this.setCode(c);
			}
		}
	};

})(jQuery);


jQuery(function(){
	$.toolkit.initPlugins('cssEditor');
	$(window).resize(function(){
		$('.tk-cssEditor').width($(window).width()-$('.CodeMirror-line-numbers').outerWidth()-2);
	});
	setTimeout(function(){$(window).resize();},250);
});
</script>
</body>
</html>