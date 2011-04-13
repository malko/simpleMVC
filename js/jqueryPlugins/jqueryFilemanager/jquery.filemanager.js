/**
* define filemanager, filemanagerButton, filemanagerEntry plugins
* @require jquery.ui >= 1.7
* @changelog
*           - 2011-03-18 - bug correction in css selector that don't work with latest jquery version
*           - 2010-06-09 - better management of filemanager button dialog size
*           - 2009-10-22 - now filemanagerEntry() trigger a change event when a file is selected
*           - 2009-04-02 - resize rootList when container is scrolled to keep a info buttons clickable
*           - 2009-03-24 - add filemanagerButton extension
*           - 2009-03-23 - add option parameter prefixValue for filemanagerEntries
*                        - implements folderClicked and folderLoaded callbacks
*           - 2009-03-04 - now infos box will be on top of parent ui-dialog if needed
*
*/
function show(){
	var evalStr = 'console.debug(';
	for(var i=0;i<arguments.length;i++)
		evalStr+=(i>0?',':'')+'arguments['+i+']';
	eval(evalStr+',show.caller?(show.caller.name?show.caller.name:show.caller):"");');
}
(function($){
	/** declare plugins inside jquery by adding methods to jquery */
	$.fn.filemanager= function(options){ return this.each(function(){	FILEMANAGER($(this),options); }); }
	function FILEMANAGER(elmt,options){ return this instanceof FILEMANAGER?this.init(elmt,options):new FILEMANAGER(elmt,options); }

	$.fn.filemanagerButton= function(cbFileClicked,options){ return this.each(function(){	FILEMANAGERBUTTON($(this),cbFileClicked,options); }); }
	function FILEMANAGERBUTTON(elmt,cbFileClicked,options){ return this instanceof FILEMANAGERBUTTON?this.init(elmt,cbFileClicked,options):new FILEMANAGERBUTTON(elmt,cbFileClicked,options); }

	$.fn.filemanagerEntry= function(options){ return this.each(function(){	FILEMANAGERENTRY($(this),options); }); }
	function FILEMANAGERENTRY(elmt,options){ return this instanceof FILEMANAGERENTRY?this.init(elmt,options):new FILEMANAGERENTRY(elmt,options); }

	// plugin defaults settings
	$.fn.filemanager.defaults = {
		imgPath:null,
		rootDir:'/',
		connector:'./filemanager.php',
		ajaxUpload:'ajaxupload.3.9.0.js',
		/** callbacks function will receive filemanager instance as this and path as first param */
		fileClicked:undefined,           // happen when a file node is clicked
		folderClicked:'fm.folderToggle', // happen when a folder node is clicked by default toggle the the folder
		folderLoaded:undefined,          // happen when a folder node has just loaded
		uiInfoIcon: 'circle-zoomout',
		langPath:'./filemanagerLang',
		lang:'fr',
		infosToggleEffect:null, // null or undefined for no effect
		itemDefaultStyle:{display:'block',textDecoration:'none',padding:'0 5px',border:'solid transparent 1px'},
		prefixValue:undefined // only concern filemanagerEntries and represent the prefix string to put before choosen path in value.
	};

	/**
	* helper function to create jquery-ui icon buttons
	* @param string icon       the name of ui-icon (without the 'ui-icon-' part)
	* @param string label      optional label
	* @param object opts       object list of options may contains:
	*                          - click:   a callback function to bind on click event
	*                          - tagName: use given tagName instead of 'button' to render the button
	*                          - id:      optionnal id attribute for the button
	* @param bool   returnHtml if true the return the html string to render instead of jquery element
	*/
	function uiButton(icon,label,opts,returnHTML){
		var o = {click:false,tagName:'button',id:false};
		if( typeof(opts) != 'undefined') o = $.extend(o,opts);
		var bt = $('<'+o.tagName+(o.id?' id="'+o.id+'"':'')+(o.tagName==='button'?' type="button"':'')+' class="ui-state-default ui-corner-all" style="vertical-align:bottom;margin:0 0.2em;"><span class="ui-icon ui-icon-'+icon+'"></span></'+o.tagName+'>');
		if(typeof(label)!='undefined' && label.length){
			if( $.browser.msie ){
				bt.append(label).css({position:'relative',paddingLeft:($.browser.version>=8?16:4)+'px'});
				var i = $('span.ui-icon',bt).css({position:'absolute',left:'0px'});
			}else{
				bt.append(label).css({position:'relative',paddingLeft:'20px'});
				var i = $('span.ui-icon',bt).css({position:'absolute',left:$.browser.mozilla?'-20px':0});
			}
		}
		if(o.click)
			bt.click(o.click);
		else
			bt.click(function(){return false;});
		if( typeof(returnHTML) != 'undefined' && returnHTML )
			return $('<div></div>').append(bt).html();
		return bt;
	}

	/** filemanagerButton definition */
	$.extend(FILEMANAGERBUTTON.prototype,{
		elmt:null,
		fmId:undefined,
		dialog: null,
		dialogId: undefined,
		fileClicked:undefined,
		init:function(elmt,cbFileClicked,opts){
			var fmb = this;
			fmb.elmt = elmt;
			var eid  = elmt.attr('id');
			fmb.fmId = 'fmanagerButton_'+eid;
			fmb.dialogId = 'fmanagerButtonDialog_'+eid;
			fmb.dialog = $('<div id="'+fmb.dialogId+'" title="File selection" style="display:none;">\
			<div id="'+fmb.fmId+'" class="fmanagerButtonDialogContent"></div></div>');
			fmb.dialog.appendTo('body')
			fmb.fileClicked = cbFileClicked;
			if( typeof(opts) === 'undefined')
				opts = {};
			opts.fileClicked = fmb.fileClicked;
			fmb.dialog.find('#'+fmb.fmId).filemanager(opts);
			fmb.fm = $('#'+fmb.fmId).data('filemanager');
			fmb.dialog.dialog({
				autoOpen:false
				, resizable:true
				, width:'350px'
				, close:function(){fm.infos.hide()}
				, resizeStart: function(e,ui){ ui.element.find('.fmanagerButtonDialogContent').height('99%') }
				, resizeStop: function(e,ui){ var c = ui.element.find('.fmanagerButtonDialogContent'); c.height(c.height()) }
			});
			fmb.elmt.click(function(){
				fm.infos.hide(); //hide any infoBox on opening
				fmb.dialog.dialog('moveToTop').dialog('open');
				return false;
			})
		}
	});

	/** filemanagerEntry definition */
	$.extend(FILEMANAGERENTRY.prototype,{
		input: null,
		fmId: undefined,
		browseButton: null,
		init:function(elmt,opts){
			var fme = this;
			fme.input = elmt;
			var id = ( fme.input.attr('id').length)?fme.input.attr('id'):fme.input.attr('name');
			// append a selector button to the input field
			fme.browseButton = uiButton('folder-collapsed',$.fn.filemanager.langMsgs['browse'],{id:'bt'+id});
			fme.browseButton.insertAfter(fme.input).filemanagerButton(fme.fileSelected,opts);
		},
		fileSelected: function(path){
			//-- get input
			var inputId = this.id.replace(/^fmanagerButton_bt/,'');
			var d = $('#fmanagerButtonDialog_bt'+inputId).dialog('close');
			var i = $('#'+inputId);
			if( ! i.length )
				i = $('input[name="'+inputId+'"]')
			i.val((this.opts.prefixValue?this.opts.prefixValue:'')+path).change();

			return false;
		}
	});



	$.extend(FILEMANAGER.prototype,{
		container:null,
		id:undefined,
		infoBox:null,
		opts: {},
		loading:null,

		fileClicked:undefined,
		folderClicked:undefined,
		folderLoaded:undefined,
		//ajaxupload:undefined,

		dialog:undefined,
		infos: undefined,

		init:function(elmt,opts){
			var fm = this;
			elmt.get(0).filemanager=this; //-- put pointer to linked filemanager inside element.
			fm.container = elmt.addClass('ui-widget ui-widget-content ui-corner-all');
			fm.id = elmt.attr('id');
			fm.container.data('filemanager',fm);
			fm.opts = $.extend({}, $.fn.filemanager.defaults, opts);
			// first check for css and required script presence if not there then load them
			var basepath = $('script[src$="/jquery.filemanager.js"]').attr('src').replace(/jquery\.filemanager\.js$/,'');
			if(! $('link[href$="/jquery.filemanager.css"]').length )
				$('<link rel="stylesheet" type="text/css" href="'+basepath+'jquery.filemanager.css" charset="utf-8" />').appendTo('head');
			if(! $('script[src$="'+fm.opts.ajaxUpload+'"]').length )
				$('<script type="text/javascript" src="'+basepath+fm.opts.ajaxUpload+'" charset="utf-8"></script>').appendTo('head');
			if( undefined === $.fn.filemanager.langMsgs )
				$('<script type="text/javascript" src="'+basepath+fm.opts.langPath+'/'+fm.opts.lang+'.js" charset="utf-8"></script>').appendTo('head');
			// set imgPath
			if(! fm.opts.imgPath )
				fm.opts.imgPath = basepath+'images';
			//-- registering callbacks
			fm.registerCallBack('fileClicked',fm.opts.fileClicked)
				.registerCallBack('folderClicked',fm.opts.folderClicked)
				.registerCallBack('folderLoaded',fm.opts.folderLoaded);
			// create dialogs
			fm.initDialogs();

			// create rootDir Element and request data for it
			var rootItem = fm.createItem({name:fm.opts.rootDir.replace(/^.*?([^\/]+)\/?$/,'$1'),fullpath:fm.opts.rootDir,"url":'',"locked":false});
			rootItem.addClass('rootItem');
			$('<ul class="jqueryFilemanager"></ul>').appendTo(this.container).append(rootItem);
			//load rootDir
			fm.loadPath(rootItem,this.opts.rootDir);
			//-- resize elements on parent scroll
			fm.container.scroll(function(){
				var container = $(this);
				var fm = this.filemanager;
				$('.rootItem',container).parent('ul').width(container.scrollLeft()+container.width());
			});

		},
		//-- AJAX HELPER METHODS --//
		post: function(datas,successCB){
			fm = this;
			$.ajax({
				type: "POST",
				url: fm.opts.connector,
				data: $.extend(datas,{fmanager:fm.id}),
				dataType: 'json',
				success: successCB,
				error:   function(XMLHttpRequest, textStatus, errorThrown){
					if( fm.loading ){
						fm.loading.remove();
						fm.loading = null;
					}
					return fm.dialog.displayError(textStatus+' : '+errorThrown);
				}
			});
		},
		//--- CALLBACKS HELPER ---//
		registerCallBack: function(event,callBack){
			var fm = this;
			if( typeof(callBack) == 'undefined')
				fm[event] = undefined;
			else if( typeof(callBack) == 'string')
				fm[event] = eval(callBack);
			else
				fm[event] = callBack;
			return fm;
		},

		//--- RENDERING ELEMENTS ---///
		//-- shorten langMsgs acess
		langMsgs: function(idMsg){ return idMsg?	$.fn.filemanager.langMsgs[idMsg] : $.fn.filemanager.langMsgs; },

		initDialogs: function(){
			var fm = this;
			//-- dialog box
			var d = $('#filemanagerDialog');
			var dialogMsgs = fm.langMsgs('dialogBox');
			var infoMsgs = fm.langMsgs('infoBox');
			if( d.length < 1){
				d = $('<div id="filemanagerDialog" style="display:none;" title=""><div class="content"></div></div>');
				d.appendTo('body').dialog({autoOpen:false,modal:true});
				$.extend(d.get(0),{
					setMsg:function(msg,state){
						if( fm.langMsgs(msg))
							msg = fm.langMsgs(msg);
						switch(state){
							case 'error':
								msg = '<p class="ui-corner-all ui-state-error" style="padding:0.5em"><span class="ui-icon ui-icon-alert" style="float:left;margin-right:5px;"></span>'+msg+'</p>';
								break;
						}
						$('.content',this).html(msg);
						return false;
					},
					displayError: function(msg){
						this.setMsg(msg,'error');
						$(this).dialog('option','title',dialogMsgs.titleError)
							.dialog('option','buttons',{})
							.dialog('moveToTop')
							.dialog('open');
						return false;
					},
					displayConfirm:function(msg,buttons,title){
						this.setMsg(msg);
						$(this).dialog('option','title',title?title:dialogMsgs.titleConfirm)
							.dialog('option','buttons',buttons)
							.dialog('moveToTop')
							.dialog('open');
						return false;
					}
				})
			}
			fm.dialog=d.get(0);

			//-- info box
			var i = $('#filemanagerInfos');
			if(i.length < 1 ){
				i = $('\n<div id="filemanagerInfos" class="ui-widget ui-widget-content ui-corner-all" style="display:none;position:absolute;width:350px;">\
					<div class="ui-widget-header ui-corner-top">\
						<span style="float:right;margin-left:5px;cursor:pointer;"><span class="close ui-icon ui-icon-closethick"></span></span>\
						<span class="title">'+infoMsgs.title+'</span>\
					</div>\
					<ul><li><a href="#filemanagerInfos .tabs-1">'+infoMsgs.tabs[0]+'</a></li> <li><a href="#filemanagerInfos .tabs-2">'+infoMsgs.tabs[1]+'</a></li>\
						<li><a href="#filemanagerInfos .tabs-3">'+infoMsgs.tabs[2]+'</a></li>\
						<li><a href="#filemanagerInfos .tabs-4" class="ui-helper-clearfix" title="'+infoMsgs.tabs[3]+'"><div class="ui-icon ui-icon-help"></div></a></li>\
					</ul>\
					<div class="tabs-1">\
						<div class="container">\
							<div id="filemanagerFullpath"><strong>'+infoMsgs.fileFullpath+'</strong>: <span style="display:block;"></span></div>\
							<div id="filemanagerMtime"><strong>'+infoMsgs.mtime+'</strong>: <span style="display:block;"></span></div>\
							<div id="filemanagerSize"><strong>'+infoMsgs.fileSize+'</strong>: <span> style="display:block;"</span></div>\
						</div>\
					</div>\
					<div class="tabs-2">\
						<div class="container">\
							<div id="filemanagerOwner"><strong>'+infoMsgs.owner+'</strong>: <span></span></div>\
							<div id="filemanagerGroup"><strong>'+infoMsgs.group+'</strong>: <span></span></div>\
							<div id="filemanagerPerms"><strong>'+infoMsgs.perms+'</strong>: <span></span></div>\
						</div>\
					</div>\
					<div class="tabs-3">\
						<div class="container">\
							<div>'+uiButton('trash','<span>'+infoMsgs.fileDelete+'</span>',{id:'filemanagerDelete'},true)+'</div>\
							<div>'+uiButton('disk','<span>'+infoMsgs.btUpload+'</span>',{id:'filemanagerUpload'},true)+'</div>\
							<div id="filemanagerNewdir"><strong>'+infoMsgs.newdirLabel+':</strong><br />\
								<input type="text" class="newdir" value="/newdir"/> <button class="ui-state-default ui-corner-all">'+infoMsgs.newdirButton+'</button>\
							</div>\
						</div>\
					</div>\
					<div class="tabs-4" style="text-align:justify;"><div class="container">'+infoMsgs.credits+'</div></div>\
					</div>'
				);
				i.appendTo('body').draggable({handle:'.ui-widget-header',stop:function(){fm.infos.setPosition()}}).tabs({selected:2});
				$('span.close',i).click(function(){$('#filemanagerInfos').toggle(fm.opts.infosToggleEffect);return false;});
				$('ul.ui-tabs-nav',i).removeClass('ui-corner-all').appendTo($('.ui-widget-header',i).get(0));
				if( typeof($.fn.bgiframe) != 'undefined')
					i.bgiframe();
				if( $.browser.msie)// quick & dirty hack
					$('button,input',i).css({	width:'290px' });
				$.extend(i.get(0),{
					datasCache:{},
					positions:{},
					fm:undefined,
					ajaxupload: new AjaxUpload('#filemanagerUpload',{name:'newfile'}),
					setPosition:function(){
						this.positions[this.datasCache.fmanager]=$(this).offset();
					},
					setInfos:function(data){
						$('span.title',this).html(data.name.length?data.name:data.fullpath);
						var d = new Date();d.setTime(data.mtime*1000);
						//general tab setting
						$('#filemanagerFullpath strong',this).html(data.ext?infoMsgs.fileFullpath:infoMsgs.dirFullpath);
						$('#filemanagerFullpath span',this).html(data.fullpath);
						if( data.url)
							$('#filemanagerFullpath span',this).append(uiButton('extlink','',{click:function(){window.open(data.url)}}).css('padding','0'));
						$('#filemanagerMtime span',this).html(d.toLocaleString());
						$('#filemanagerSize strong',this).html(data.ext?infoMsgs.fileSize:infoMsgs.dirSize);
						$('#filemanagerSize span',this).html(data.size);

						// perm tab setting
						$('#filemanagerOwner span',this).html(data.user);
						$('#filemanagerGroup span',this).html(data.group);
						$('#filemanagerPerms span',this).html(data.perms);

						// action tab
						var basepath = ( data.ext === undefined?data.fullpath :data.basepath ).replace(/\/$/,'');
						this.datasCache = data;
						this.fm = $('#'+data.fmanager).data('filemanager');

						var btDel = $('#filemanagerDelete span:not(.ui-icon)',this);
							btDel.html(data.ext?infoMsgs.fileDelete:infoMsgs.dirDelete)
							.attr('disabled',data.perms.match(/\d[2367]\d\d/)?false:'disabled')
						$('#filemanagerNewdir input',this).val(basepath+'/new_dir')
						//-- reset ajax upload settings
						$.extend(this.ajaxupload._settings,{data:{ fmanager:data.fmanager,basepath:basepath},action:this.fm.opts.connector,onComplete:this.fm.newfile});
					},
					hide: function(){
						if($(this).is(':visible'))
							$(this).hide().appendTo('body');
						return false
					},
					show: function(data,attachElemt){
						if(data)
							this.setInfos(data);
						if( attachElemt){
							if( this.positions && this.positions[data.fmanager]){
								var o = this.positions[data.fmanager];
							}else{
								var o = $(attachElemt).offset();
								o.top+=$(attachElemt).height();
							}
							o.zIndex = parseInt($(attachElemt).parents('.ui-dialog').css('zIndex'))+1;
							$(this).css(o);
							//$(this).insertAfter(attachElemt);
						}
						if(! $(this).is(':visible'))
							$(this).toggle(this.fm.opts.infosToggleEffect);
						return false;
					}
				});
				$('#filemanagerNewdir button',i).click(function(){
					var fm = i.get(0).fm;
					fm.post({newdir:$(this).siblings('input[class=newdir]').val()},fm.newDir);
				});
				$('#filemanagerDelete',i).click(function(){
					var fm = i.get(0).fm;
					var buttons = {};
					buttons[fm.langMsgs('dialogBox').btDelete] = function(){
						$(this).dialog('close');
						fm.post({unlink:fm.infos.datasCache.fullpath},fm.unlinked);
					};
					buttons[fm.langMsgs('dialogBox').btCancel] = function(){$(this).dialog('close');};
					fm.dialog.displayConfirm(
						fm.langMsgs(fm.infos.datasCache.ext?'confirmFileDelete':'confirmDirDelete').replace('$path',fm.infos.datasCache.fullpath),
						buttons,
						fm.langMsgs('infoBox')[fm.infos.datasCache.ext?'fileDelete':'dirDelete']
					);
				});
			}
			fm.infos = i.get(0);
		},
		/**
		* create an item element and return it
		* @param json itemData object describing elements infos
		* @return li jquery extended elements
		*/
		createItem: function(itemData){
			var fm = this;
			var item = $('<li><a href="'+itemData.url+'" rel="'+itemData.fullpath+'" target="_blank" class="ui-corner-all">'+itemData.name+'</a></li>');
			var a = $('a',item).css(fm.opts.itemDefaultStyle);

			if( itemData.ext){ //file case
				item.addClass('file ext_'+itemData.ext+(itemData.locked?' locked':''));
				if( fm.fileClicked )
					a.click(function(){ return fm.fileClicked($(this).attr('rel'));});
			}else{ // folder case
				item.addClass('directory collapsed'+(itemData.locked?' locked':''));
				if( fm.folderClicked )
					a.click(function(){return fm.folderClicked($(this).attr('rel'));});
				else
					a.click(function(){return fm.folderToggle(item);});
			}
			// add info button
			a.hover(
				function(){
					$(this).addClass('ui-state-hover').children('button').css('visibility','visible');
				},
				function(){ $(this).removeClass('ui-state-hover').children('button').css('visibility','hidden'); }
			);
			this.itemAddInfoButton(item);
			return item;
		},
		/**
		* append an info button to the item
		*/
		itemAddInfoButton: function(liItem){
			var fm = this;
			var a  = $('a',liItem);
			uiButton(fm.opts.uiInfoIcon,'',{click:function(){return fm.displayInfos($('a',liItem).attr('rel'));}}).css('visibility','hidden').appendTo(a);
		},
		itemSetLoadInfo: function(liItem,show){
			if( show )
				liItem.addClass('wait');
			else
				liItem.removeClass('wait');
		},
		//--- LOADING PATH ACTIONS ---//
		loadPath: function(parentElmt,path){
			var fm = this;
			// create waiting elmt
			if( fm.loading !== null){
				fm.dialog.displayError('postWaitPrevResult');
				return false;
			}
			var ul = $('<ul class="jqueryFilemanager loading"><li class="wait">'+fm.langMsgs('loading')+'</li></ul>').appendTo(parentElmt);
			fm.loading = ul ;
			fm.post({listdir:path},fm.loadedPath);
			return false;
		},
		loadedPath: function(data){
			// remove loading element
			if(! data.fmanager)
				return $('#filemanagerDialog').get(0).displayError(data.error?data.error:$.fn.filemanager.langMsgs.infosMissing);
			var fm = $('#'+data.fmanager).data('filemanager');
			var loading = fm.loading;

			if( loading !== null){
				loading.html('');
				fm.loading = null;
				// display error if required
			}
			if( data.error ){
				if( loading)
					loading.remove();
				if( fm.langMsgs(data.error))
					data.error = fm.langMsgs(data.error);
				return fm.dialog.displayError(data.error);
			}

			// append loaded elements
			$.each(data,function(i){
				if( ! i.match(/^\d+$/))	return; //skip named elements
				fm.createItem(data[i]).appendTo(loading);
			});
			if( ! loading ){
				return false;
			}
			loading.removeClass('loading');
			loading.parent('li').removeClass('collapsed').addClass('expanded');
			if( fm.folderLoaded)
				fm.folderLoaded($('a',loading.parent('li')).attr('rel'))
			return false;
		},

		//--- FOLDERS ACTIONS ---//
		folderToggle: function(liItem){
			if( typeof(liItem)=='string')
				liItem = this.getItem(liItem)
			if(  liItem.hasClass('expanded') )
				this.folderCollapse(liItem);
			else
				this.folderExpand(liItem);
			return false;
		},
		folderExpand: function (liItem){
			this.hideInfos();
			this.loadPath($(liItem),$('a',liItem).attr('rel'));
			return false;
		},
		folderCollapse: function(liItem){
			this.hideInfos();
			$(liItem).removeClass('expanded').addClass('collapsed').children('ul').remove();
			return false;
		},
		folderReload: function(liItem){
			if( liItem.hasClass('expanded') )
				this.folderCollapse(liItem);
			this.folderExpand(liItem);
		},
		getItem: function(path,rootItemOnEmpty){
			var item = $('a[rel="'+path+'"]',this.container).parent('li');
			if( item.length)
				return item;
			return rootItemOnEmpty?$('li.rootItem',fm.container):undefined;
		},

		//--- INFOS METHODS ---//
		displayInfos: function(path){
			var fm=this;
			if( $(fm.infos).is(':visible') && fm.infos.datasCache && fm.infos.datasCache.fullpath == path)
				return fm.infos.hide();
			fm.itemSetLoadInfo(fm.getItem(path,true),true);
			fm.post({getinfos:path},fm.receivedInfos);
			return false;
		},
		/** hide and reparent the infosBox to the body */
		hideInfos: function(){
			$('#filemanagerInfos').hide().appendTo('body');
		},
		receivedInfos: function(data){
			var fm = $('#'+data.fmanager).data('filemanager');
			var msgs = fm.langMsgs('infoBox');
			var attachItem = fm.getItem(data.fullpath,true);//$('a[rel="'+data.fullpath+'"]',fm.container);
			fm.itemSetLoadInfo(attachItem,false);
			fm.infos.hide();
			fm.infos.show(data,attachItem);
			return false;
		},

		//--- ACTIONS METHODS ---//
		unlinked: function(data){
			if( data.error)
				return $('#filemanagerDialog').get(0).displayError(data.error);
			var fm = $('#'+data.fmanager).data('filemanager');
			return fm.folderReload(fm.getItem(data.basepath,true));
		},
		newDir: function(data){
			var fm = $('#'+data.fmanager).data('filemanager');
			if( data.error)
				return fm.dialog.displayError(data.error);
			fm.hideInfos();
			fm.folderReload(fm.getItem(data.basepath,true));
			return false;
		},
		newfile: function(file,response){
			var data = eval('('+response+')');
			var fm = $('#'+data.fmanager).data('filemanager');
			if( data.error)
				return fm.dialog.displayError(data.error);
			fm.hideInfos();
			return fm.folderReload(fm.getItem(data.basepath,true));
		}
	});
})(jQuery);
