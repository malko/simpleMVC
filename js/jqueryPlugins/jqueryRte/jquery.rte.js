/**
* a simple and lightweight rich text editor
* @author jonathan gotti < jgotti at jgotti dot org >
* @licence double licence GPL / MIT
* @package jqueryPlugins
* @since 2008-01
* this work is inspired from:
* - jQuery RTE plugin by 2007 Batiste Bieler (http://batiste.dosimple.ch/blog/2007-09-11-1/)
* - jquery.wisiwyg by Juan M Martinez (http://private.tietokone.com.ar/jquery.wysiwyg/)
* orignal toolbar icons are from famfamfam -> http://www.famfamfam.com/lab/icons/silk/ and distributed under creative commons attribution 2.5 licence
* @svnInfos:
*            - $LastChangedDate$
*            - $LastChangedRevision$
*            - $LastChangedBy$
*            - $HeadURL$
* @changelog
*            - 2009-07-22 - better hasSelection detection
*            - 2009-07-16 - prefixing internal methods with underscore
*                         - major rewriting of buttons (better for future evolution) and changes in options buttonSet where you can now set order of elements
*            - 2009-07-15 - add insetTable support
*            - 2009-03-27 - add rangeObject parameter to insertNode() method
*                         - getSelection will now trigger focus on editable document (prevent image insertion outside editable document when not focused under ie)
*            - 2009-03-26 - now edited documents will use standard mode in ie6 instead of quirks mode
*            - 2009-03-24 - remove use of deprecated jQuery.browser.msie detection
*            - 2009-03-23 - add indent/outdent commands
*            - 2009-01-22 - bug correction that made certain ie6 version to loose link to this.editable after designMode is set to on
*            - 2008-04-15 - now classOption can also refer to callback user function
*            - 2008-02-22 - set frameBody height to 100% (permit to click anywhere instead of firstline only in firefox when empty)
*            - 2008-02-22 - now you can set button that are present in toolbar
*            - 2008-01-30 - new methods createElement, hasSelection, removeLink
*/

(function($){
	$.fn.rte = function(options) {
		// if doable we transform textarea to rich text editors
		if(document.designMode || document.contentEditable) // iterate and reformat each matched element
			return this.each(function(){ RTE($(this),options); });
		else
			return this;
	}
	function RTE(elmt, options ){
		return this instanceof RTE ? this.init(elmt,options): new RTE(elmt, options);
	}

	$.extend(RTE.prototype,{
		opts:     $.fn.rte.defaults,
		container: null,
		textarea: null,
		toolbar:  null,
		iframe:   null,
		editable: null,
		content:  '',
		id:       '',
		_buttonsDef:{
			format:{
				onchange:function(e){
					var selected = this.options[this.selectedIndex].value;
					e.data.rte.formatText("formatblock", '<'+selected+'>');
				},
				empty:'Apply format',
				className:'formatSel'
			},
			'class':{
				onchange:function(e){
					var selected = this.options[this.selectedIndex].value;
					if(selected != 0){ // add class
						var editable = e.data.rte.editable;
						var _tag = selected.split(':');
						if( _tag[0] === 'func'){
							 eval(_tag[1]+'(e.data.rte);');
						}else{
							var tag  = editable.createElement(_tag[0]);
							if(_tag[1])
								tag.className = _tag[1];
							e.data.rte.surroundContents(tag);
						}
					}
					e.data.rte.syncFromEditor();
				},
				empty:'Apply style',
				className:'classSel'
			},
			bold:{cmd:'bold',label:'bold',img:'format-text-bold.png'},
			underline:{cmd:'underline',label:'underline',img:'format-text-underline.png'},
			italic:{cmd:'italic',label:'italic',img:'format-text-italic.png'},
			orderedList:{cmd:'insertorderedlist',label:'ordered list',img:'text_list_numbers.png'},
			unorderedList:{cmd:'insertunorderedlist',label:'unordered list',img:'text_list_bullets.png'},
			indent:[
				{cmd:'indent',label:'indent',img:'text_indent.png'},
				{cmd:'outdent',label:'outdent',img:'text_indent_remove.png'}
			],
			justify:[
				{cmd:'justifyleft',label:'left alignment',img:'format-justify-left.png'},
				{cmd:'justifycenter',label:'centered alignment',img:'format-justify-center.png'},
				{cmd:'justifyright',label:'right alignment',img:'format-justify-right.png'},
				{cmd:'justifyfull',label:'justify alignment',img:'format-justify-fill.png'}
			],
			link:[
				{cmd:{from:'options',name:'createLink'},label:'create link',img:'link_add.png'},
				{cmd:{from:'rte',name:'removeLinkCB'},label:'remove link',img:'link_break.png'}
			],
			image:{cmd:{from:'options',name:'insertImage'},label:'insert image',img:'image_add.png'},
			table:{cmd:{from:'options',name:'insertTable'},label:'insert table',img:'table_add.png'},
			remove:{cmd:{from:'rte',name:'cleanContents'},label:'remove format',img:'html_delete.png'},
			toggle:{cmd:{from:'rte',name:'toggleEditModeCB'},label:'toggle edit mode',img:'tag.png',className:'toggle'},
			spacer:{cmd:'spacer',label:null,img:null}
		},

		init: function(elmt,options){
			// prepare options without overriding default ones
			this.opts     = $.extend({}, $.fn.rte.defaults, options);
			this.opts._buttonExp = new RegExp('.*('+this.opts.buttonSet+').*','i'); // used internally to easily [en/dis]able some buttons
			this.id       = elmt.attr('id')?elmt.attr('id'):(elmt.attr('name')?elmt.attr('name'):'');
			this.textarea = elmt;
			// create iframe elments
			this._initIframe()
				._initToolBar()     // create toolbar elements
				._arrangeElements() // put all together
				._initIframe();     // set iframe content and make it editable.

			if(this.textarea.is(':disabled')){
				$(this.iframe).hide();
			}else{
				this.textarea.hide();
			}

			// data synchronisation between textarea/iframe */
			$(this.editable).bind('mouseup',{rte:this},function(e){e.data.rte.syncFromEditor();});
			$(this.editable).bind('keyup',{rte:this},function(e){e.data.rte.syncFromEditor();});
			this.textarea.bind('keyup',{rte:this},function(e){e.data.rte.syncFromTextarea();});
			/*/ set to use paragraph on return to behave same on firefox as on ie. (doesn't work)
			this.formatText("insertbronreturn",false);
			this.formatText("enableinlinetableediting",false);*/
		},
		_initIframe: function(){
			if(! this.iframe){
				this.iframe = document.createElement("iframe");
				this.iframe.frameBorder = this.iframe.frameMargin = this.iframe.framePadding=0;
				this.iframe.id = 'RTE_FRAME_'+this.id;
				$(this.iframe).width(this.textarea.width()).height(this.textarea.height());
			}else{
				var css = this.opts.css_url?"<link type='text/css' rel='stylesheet' href='"+this.opts.css_url+"' />":'';
				this.content = this.textarea.val();
				var contentDoc = window.document.getElementById(this.iframe.id).contentDocument; //-- IE won't get a contentDoc
				//- var contentDoc = this.iframe.contentDocument; //-- IE won't get a contentDoc
				if($.trim(this.content)=='' && contentDoc){// Mozilla need this to display caret
					this.content = '<br />';
					this.textarea.parent('form').bind('submit',{rte:this},function(e){
						var t=e.data.rte.textarea;t.val(t.val().replace(/(^<br( \/)?>|<br( \/)?>$)/,''));
						return true;
					});
				}
				if(contentDoc)
					this.editable = contentDoc;
				else// IE
					this.editable = this.iframe.contentWindow.document;
				this.editable.open();
				this.editable.write('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"><html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"><head>'+css+"<style>td{border:solid black 1px !important;}</style></head><body class='frameBody' style='height:100%;margin:0;'>"+this.content+"</body></html>");
				this.editable.close();
				this.editable.contentEditable = 'true';
				this.editable.designMode = 'on';
				if(! contentDoc)// some ie6 version may loose this.editable after setting designMode to on so relink it
					this.editable = this.iframe.contentWindow.document;
			}
			return this;
		},
		_arrangeElements: function(){
			this.textarea.wrap('<div class="rte" id="RTE_'+this.id+'"></div>').before(this.iframe);
			$(this.iframe).before(this.toolbar);
			this.container = $('#RTE_'+this.id);
			this.container.width(this.opts.width!=0 ? this.opts.width : this.textarea.width())
				.height(this.opts.height!=0 ? this.opts.height : (parseInt(this.textarea.height())+parseInt(this.toolbar.outerHeight())+'px'))
				.css('text-align','center');
			return this;
		},
		_initToolBar: function(){
			this.toolbar = $("<div class='toolbar'></div>");
			// prepare all buttons
			for(var i=0,bset=this.opts.buttonSet.split('|');i<bset.length;i++){
				var bName = bset[i];
				var bDef  = this._buttonsDef[bName];
				if( ! bDef)
					continue;
				if( bDef.onchange){ // case of select buttons
					var select = '<select class="'+bDef.className+'"><option value="" >'+bDef.empty+'</option>';
					var opts = this.opts[bName+'Options'];
					for(var z in opts)
						select += '<option value="'+opts[z][0]+'">'+opts[z][1]+'</option>';
					select += '</select>';
					$(select).bind('change',{rte:this},bDef.onchange).appendTo(this.toolbar).css({margin:'0 0.4em'})
				}else if( bDef instanceof Array){
					for( var z=0,l=bDef.length; z<l;z++)
						this._appendFormatButtonFromDef(bDef[z]);
				}else{
					this._appendFormatButtonFromDef(bDef);
				}
			}
			return this;
		},
		_appendFormatButtonFromDef: function(def){
			var cmd = def.cmd;
			if( cmd instanceof Object){
				if( cmd.from === 'rte')
					cmd = this[cmd.name];
				else if( cmd.from === 'options')
					cmd = this.opts[cmd.name];
			}
			return this.appendFormatButton(cmd,def.label,def.img,def.className);
		},

		appendFormatButton: function(cmd,label,image,className){
			if(cmd==='spacer'){
				this.toolbar.append('&nbsp;&nbsp;');
				return this;
			}
			label = label.replace(/[^\\]"/,'"');
			var img = image?'<img src="'+this.opts.imgPath+image+'" alt="'+label+'" border="0" />':label;
			var b = $('<a href="#" title="'+label+(className?'" class="'+className:'')+'">'+img+' </a>');
			this.toolbar.append(b);
			if( cmd.toString().match(/^(bold|italic|undo|redo|underline|formatblock|removeformat|justify|insert(un)?orderedlist|(in|out)dent)/i) ){
				b.bind('click',{rte:this},function(e){ e.data.rte.formatText(cmd); return false;});
			}else{
				b.bind('click',{rte:this},cmd);
			}
			return this;
		},

		/** return a node created in the context of the editable document. */
		createElement: function(tag){
			return this.editable.createElement(tag);
		},

		syncFromTextarea: function(){
			$(this.editable).find('body').html(this.textarea.val());
		},

		syncFromEditor: function(){
			this.setSelectors();
			this.textarea.val($(this.editable).find('body').html());
		},

		setSelectors: function(){
			if(! this.opts.buttonSet.match(/format|class/) )
				return;
			var node = this.getSelectedElement();
			var classIndex = formatIndex = 0;
			var classSel = $('select.classSel', this.toolbar).get(0);
			var formatSel=$('select.formatSel', this.toolbar).get(0);

			while(node.parentNode && classIndex===0 && formatIndex===0 ){
				var nName = node.nodeName.toLowerCase();
				if( formatSel && formatIndex === 0 ){
					for(var i=0;i<formatSel.options.length;i++){
						if(nName==formatSel.options[i].value.toLowerCase()){
							formatIndex=i;
							break;
						}
					}
				}
				if( classSel &&classIndex === 0 ){
					var cName = $(node).attr('class');
					if( cName ){
						for(var i=0;i<classSel.options.length;i++){
							if(nName+':'+cName==classSel.options[i].value){
								classIndex=i;
								break;
							}
						}
					}
				}
				node = node.parentNode;
			}
			if(formatSel)
				formatSel.selectedIndex=formatIndex;
			if(classSel)
				classSel.selectedIndex=classIndex;
			return true;
		},

		toggleEditModeCB: function(e){ e.data.rte.toggleEditMode(); return false},
		toggleEditMode: function(){
			if(this.textarea.is(':disabled'))
				return this;
			if(this.textarea.is(':visible')){
				this.textarea.hide();
				$(this.iframe).show();
			}else{
				this.textarea.show();
				$(this.iframe).hide();
			}
			return this;
		},

		/**
		* you must create the node in the editable document context to get it work with ie.
		* you can use the rte.createElement method to achieve this.
		* @param domNode    node the node to add
		* @param bool       returnNode will return node instead of rte instance if set to true
		* @param rangeOject permit you to pass your own rangeObject it's usefull when replacing the default createLink method.
		* @return rte or domeNode depending on returnNode value.
		*/
		surroundContents: function(node,returnNode,rangeObject){
			if(this.textarea.is(':visible'))
				return returnNode?this:node;
			var r = rangeObject?rangeObject:this.getSelectedElement(true);
			if(r){
				if(r.surroundContents){// the normal way
					r.surroundContents(node);
				}else if(r.htmlText!==undefined){ // do it the dirty ie way (dirty hack if you have better way i take it)
					//-- don't ask me why i can't achieve the same thing by using jquery methods (buggy ie drive me crazy)
				//alert(r.htmlText+'/'+r.text);
					var tmpParent = this.createElement('div');
					node.innerHTML += r.htmlText;
					tmpParent.appendChild(node);
					r.pasteHTML(tmpParent.innerHTML);
				}
				this.syncFromEditor();
			}
			return returnNode?this:node;
		},
		/**
		* you must create the node in the editable document context to get it work with ie.
		* you can use the rte.createElement method to achieve this.
		* @param domNode    node the node to add
		* @param bool       returnNode will return node instead of rte instance if set to true
		* @param rangeOject permit you to pass your own rangeObject it's usefull when replacing the default insertImage method.
		*/
		insertNode: function(node,returnNode,rangeObject){
			if(this.textarea.is(':visible'))
				return returnNode?this:node;
			var r = rangeObject?rangeObject:this.getSelectedElement(true);
			if(r.insertNode){ // normal way
				r.insertNode(node);
			}else{ // ugly ie hack
				var tmpParent = this.createElement('div');
				tmpParent.appendChild(node);
				r.collapse();
				r.pasteHTML(tmpParent.innerHTML);
			}
			this.syncFromEditor();
			return returnNode?this:node;
		},

		/** really clean content from any tag (called as a callback) */
		cleanContents: function(event){
			var rte = event.data.rte
			if(rte.textarea.is(':visible'))
				return false;
			r = rte.getSelectedElement(true);
			if(r.htmlText!==undefined){
				r.text = r.text;
			}else if(r.extractContents !== undefined && r.insertNode !== undefined){
				var tmp = r.extractContents();
				$(tmp).children().each(function(){
					$(this).replaceWith($(this).text());
				});
				r.insertNode(tmp);
			}else{
				rte.formatText('removeFormat');
			}
			rte.syncFromEditor();
			return false;
		},

		hasSelection: function(){
			var r = this.getSelectedElement(true);
			if(r.htmlText !== undefined)
				return r.htmlText===''?false:true;
			return ( r.startOffset-r.endOffset != 0)?true:false;

		},
		removeLinkCB: function(e){ e.data.rte.removeLink(); return false; },
		removeLink: function(){
			var p = this.getSelectedElement();
			if(p.tagName !== undefined && p.tagName.match(/a/i)){
				$(p).replaceWith(p.innerHTML);
			}else{
				p = $(p).parent('a');
				p.replaceWith(p.html());
			}
			this.syncFromEditor();
		},

		/** return the parent node of the selection or range if returnRange is true */
		getSelectedElement: function(returnRange) {
			if(this.textarea.is(':visible'))
				return false;
			this.editable.body.focus(); // ensure editable to be focused
			if(this.editable.selection) { // IE selections
				selection = this.editable.selection;
				range = selection.createRange();
				try {
					node = range.parentElement();
				}catch(e){
					return false;
				}
			}else{ // Mozilla selections
				try {
					selection = this.iframe.contentWindow.getSelection();
					range = selection.getRangeAt(0);
				}catch(e){
					return false;
				}
				node = range.commonAncestorContainer;
			}
			return returnRange?range:node;
		},

		formatText: function(command, option) {
			if(this.textarea.is(':visible'))
				return this;
			$(this.editable).focus();
			try{
				this.editable.execCommand(command, false, option?option:null);
			}catch(e){console.log(e)}
			$(this.editable).focus();

			// quick and dirty hack to avoid empty tags when trying to remove formatblocks
			if(command === 'formatblock' && option === '<>'){
				this.syncFromEditor();
				var val = this.textarea.get(0).value;
				this.textarea.get(0).value = val.replace(/<<>>|<\/<>>/g,'');
				this.syncFromTextarea();
			}

			this.syncFromEditor();
		}

	});

	// plugin defaults settings
	$.fn.rte.defaults = {
		css_url: '',
		imgPath: '/js/jqueryPlugins/jqueryRte/',
		width:0,
		height:0,
		/** thoose are the commonly accepted values that work */
		formatOptions: [
			['P', 'Paragraph'],
			['PRE', 'Preformatted'],
			['ADDRESS','Address'],
			['H1','Title 1'],
			['H2','Title 2'],
			['H3','Title 3 '],
			['H4','Title 4'],
			['H5','Title 5'],
			['H6','Title 6']
		],
		/**
		* classOptions si a list of format tags with specified class like this
		* array[ 'tagName:className','display label']
		* but can also be used to call your own callback function like this:
		* ['func:jsfunctionName','display label']
		* the callback function will receive rte object as first argument.
		* you should define the function like this:
		* jsfunctionName = function(rte){};
		*/
		classOptions: [
			['span:title','title'],
			['div:test','test']
		],
		/**
		* set what is viewable or not in toolbar and in which order
		* list of available buttons/selector separated by pipes possible values are:
		* format | class | bold | underline | italic | orderList | unorderList
		* justify (enable all justify buttons)
		* link | image | table | remove | toggle | spacer
		*/
		buttonSet: 'format|class|bold|underline|italic|spacer|orderedList|unorderedList|indent|oudent|spacer|justify|spacer|link|image|spacer|remove|toggle',
		/** overwritable callback function */
		createLink: function(e){
			var url = prompt('Insert link URL');
			if(url)
				e.data.rte.formatText('createlink',url);
			return false;
		},
		insertImage: function(e){
			var url = prompt('Insert image URL');
			if(url){
				var i = e.data.rte.createElement('img');
				i.src = url;
				e.data.rte.insertNode(i);
			}
			return false;
		},
		insertTable:function(e){
			var size = prompt('Insert new table:\ngive size in terms of columns by rows.\nTable size: ','2x1');
			if(size &&  size.length){
				var t = e.data.rte.createElement('TABLE');
				t.cellSpacing=0;
				size = size.split(/\D/,2);
				var maxx = parseInt(size[0]);
				for(var y=0,maxy=parseInt(size[1]);y<maxy;y++){
					var tr = t.insertRow(y);
					for(var x=0; x<maxx;x++)
						tr.insertCell(x).innerHTML='&nbsp;';
				}
				e.data.rte.insertNode(t);
			}
			return false;
		}
	};

})(jQuery);
