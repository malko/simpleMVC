/**
* simpleMVC debug toolbar
* @svnInfos:
*            - $LastChangedDate$
*            - $LastChangedRevision$
*            - $LastChangedBy$
*            - $HeadURL$
* @changelog
*            - 2009-02-10 - add support for DynCss
*            - 2009-02-07 - cookies path set to slash as default
*            - 2009-01-14 - add quick cookie management to keep trace of last opened panel
*/
jQuery(function(){
var cookies={
	get:function(name){
			var re=new RegExp(name+'=([^;]*);?','gi'), result=re.exec(document.cookie)||[];
			return (result.length>1? unescape(result[1]): undefined);
	},
	set:function(name, value, expirationTime, path, domain, secure){
			var time=new Date;
			if(expirationTime)
					time.setTime(time.getTime()+(expirationTime*1000));
			document.cookie=name+ '='+ escape(value)+ '; '+
			(!expirationTime? '': '; expires='+time.toUTCString())+
			'; path='+(path?path:'/')+ (!domain? '': '; domain='+domain)+ (!secure? '': '; secure');
	},
	del:function(name, path, domain){
			var value=this.get(name);
			document.cookie=name+ '='+ '; path='+(path?path:'/')+
			(!domain? '': '; domain='+domain)+
			'; expires=Thu, 01-Jan-70 00:00:01 GMT';
			return value;
	}
};

	var toolBar = $('#sMVCtoolBar').css({position:'fixed',top:0})
		, positionUsed = toolBar.offset().top===$(window).scrollTop()?'fixed':'absolute'
		, btShow  = $('#sMVCshow')
		, showDiv = $('#sMVCshow_div')
		, btPhperr  = $('#sMVCphperr')
		, phperrDiv = $('#sMVCphperr_div')
		, showDiv = $('#sMVCshow_div')
		, btDb  = $('#sMVCdb')
		, dbDiv = $('#sMVCdb_div')
		, btDataMenu  = $('#sMVCmodels')
		, dataMenu = $('ul#sMVCmodelsList')
		, btToggle = $('#sMVCtoolBarToggle')
		, btCss
		, cssDiv
	;

	//-- styling toolbar
	toolBar.css({position:positionUsed,right:0,zIndex:9999,margin:0});

	var pannelStyle = {
		//- background:'#F0F0F0', position:'absolute', left:0, top:0,
		//- zIndex:999,  overflow:'auto', display:'none', textAlign:'left',padding:'10px',
		//- border:'solid #333 1px',borderTop:'none',borderLeft:'none'
		position:positionUsed, left:"0.5%", top: ".5%",maxHeight:"99%",
		textAlign:'left',zIndex:9998,  overflow:'auto', display:'none',
		fontSize:'12px', width:"99%"
	};
	var pannelTitleStyle = { color:'#555', fontSize:'14px', margin:'10px 0', borderBottom:'solid #555 1px'};
	/*var toolBarButtonStyle = {border:'solid #555 1px','border-top':'none',color:'#333',cursor:'pointer',background:'#F0F0F0',margin:0};
	$('button',toolBar).css(toolBarButtonStyle);*/


	function _toInt(value){ var i = parseInt(value); return isNaN(i)?0:i; }

	function addPanel(bt,pannel,content,addcount){
		if( addcount ){
			bt.append('<small> ('+ ((typeof(addcount)=='number'||typeof(addcount)=='string')?addcount:content.length)+')</small>');
		}
		pannel = $(pannel);
		pannel.append(content).css(pannelStyle).addClass('ui-widget ui-widget-content ui-corner-all');
		$('h1',pannel).css(pannelTitleStyle)
			.addClass('ui-widget-header')
			.prepend('<span class="ui-icon ui-icon-close" style="float:left;" title="close pannel"></span>')
			.find('span.ui-icon')
				.hover(function(){$(this).parent('h1').toggleClass('ui-state-hover');})
				.css({cursor:"pointer"})
				.click(function(){pannel.hide();setPanelCookie(); return false;} )
		;
		bt.click(function(e){
			$('.sMVCpannel').not(pannel).hide();
			pannel.toggle();
			setPanelCookie();
			return false;
		});
	}

	function setPanelCookie(){
		// detect opened panel;
		var visiblePanel = 'none';
		if( showDiv.is(':visible') )
			visiblePanel = 'Show';
		else if( phperrDiv.is(':visible') )
			visiblePanel = 'Phperr';
		else if( dbDiv.is(':visible') )
			visiblePanel = 'Db';
		if( visiblePanel !== 'none' ){
			cookies.set('SMVCDevBarPanel',visiblePanel);
		}else{
			cookies.del('SMVCDevBarPanel');
		}
	}

	//-- Manage show display
	var showed = $('div.show');
	if( showed.length == 0){
		btShow.hide().remove();
		showDiv.hide().remove();
	}else{
		addPanel(btShow,showDiv,showed,true);
		showed.css('margin','0 20px');
		$('strong',showed).click(function(){$(this).siblings('pre').toggle(); return false;}).css({ cursor:'pointer'});
		$('.toggle',showDiv).click(function(){
			var titles = $('div.show strong');
			var opened   = titles.siblings('pre:visible').length
			var closed = titles.length - opened;
			if( opened > closed)
				titles.each(function(){ var t= $(this); if(t.siblings('pre:visible').length) t.click() });
			else
				titles.each(function(){ var t= $(this); if(t.siblings('pre:hidden').length) t.click() });
			return false;
		});
	}

	//-- Manage PHP ERRORS display
	var phped = $('div.php_error');
	if( phped.length==0){
		btPhperr.hide().remove();
		phperrDiv.hide().remove();
	}else{
		//phped.each(function(){this.innerHTML = this.innerHTML.replace(/(^<br\s*\/?>|<br\s*\/?>$)/,'');});
		addPanel(btPhperr,phperrDiv,phped,true);
	}

	//-- Manage DB::profiler reports display
	var report = $('#dbProfilerReport').parent('table');
	var dbMsgs = $('div.dbMsg'); // append eventual class-db errors
	if( (report.length+dbMsgs.length)==0){
		btDb.hide().remove();
		dbDiv.hide().remove();
	}else{
		addPanel(btDb,dbDiv,report,(dbMsgs.length ? $('tbody tr',report).length+'/'+dbMsgs.length : $('tbody tr',report).length/2));
		if( dbMsgs.length){
			$('<div style="margin:1em;padding:.5em;" class="ui-state-highlight"><h2>DB::messages</h2></div>').appendTo(dbDiv).append(dbMsgs.css('display','block'));
			$('h2',dbDiv).addClass('ui-widget-header').css('font-size','0.8em');
			btDb.addClass('ui-state-error');
		}else{
			report.children('caption').click(); // open profiler table
		}
	}

	//-- Manage models menu
	if(! dataMenu.length){
		btDataMenu.hide().remove();
	}else{
		btDataMenu.before(dataMenu);
		dataMenu.css({
			//borderTop:'none',
			position:'absolute',
			zIndex:9999,
			//right:0,
			left:0,
			top:btDataMenu.innerHeight(),
			textAlign:'left',
			listStyleType:'none',
			listStyleImage:'none',
			overflow:'auto',
			margin:'2px 0',
			padding:'0 10px',
			maxHeight:'350px',
			display:'none'
		}).addClass('ui-widget-content ui-button-none ui-corner-bottom ui-corner-top-left');
		$('li',dataMenu).css({fontSize:'12px',padding:'2px'}).filter(':last').css({fontStyle:'italic',border:'none'});
		$('.ui-button',dataMenu).click(function(r){dataMenu.hide();});;
		btDataMenu.click(function(e){
			//dbg($(this).position().left+$(this).width())
			dataMenu.css({
				top:$(this).innerHeight(),
				//left:  $(this).position().left + $(this).width() - dataMenu.width(),
				maxHeight:$(window).height()*0.9
			}).toggle();
			return false;
		});
	}

	/*/-- Manage DynCss
	simpleMVCDynCssAppend= function(e){
		if( ! $('#sMVCcss').length){ //- create elements
			btCss = $('<button id="sMVCcss" class="ui-button">DynCss</button></div>').appendTo(toolBar).button()
			btCss.parent('.ui-buttonset').buttonset('propagateSettings');//.css(toolBarButtonStyle);
			if(! btDataMenu.is(':visible') )
				btCss.attr('style','display:none!important');
			cssDiv= $('<div id="sMVCcss_div" class="sMVCpannel"><h1>jqueryDynCss generated</h1>').appendTo('#sMVCpannels');
			addPanel(btCss,cssDiv);
		}
		$('<pre style="text-align:left;"></pre>').html(e.innerHTML).appendTo(cssDiv);
		if( cookies.get('SMVCDevToggle') != 1)
			btCss.hide();
	};*/

	//-- Manage cssEditor button
	$('#sMVCcssEditor').click(function(){
		var editorUrl = $(this).attr('rel'),
			frontName = editorUrl.replace(/^.*?cssEditor\.php\?editorId=/,'');
		if( window._cssEditor === undefined){
			window._cssEditor = window.open(editorUrl,'cssEditor4'+frontName,'dependent=yes,titlebar=no,status=no,scrollbars=yes,menubar=no,location=yes,toolbar=no,height=600,width=800');
		}else{
			window._cssEditor.close();
			delete window._cssEditor;
		}
		return false;
	});
	//-- show Session button
	$('#sMVCshowSession').click(function(){
		var relUrl = $(this).attr('rel');
		if( window._session === undefined){
			window._session = window.open(relUrl,'session','dependent=yes,titlebar=no,status=no,scrollbars=yes,menubar=no,location=yes,toolbar=no,height=600,width=800');
		}else{
			window._session.close();
			delete window._session;
		}
		return false;
	});
	//-- toggle button
	btToggle.click(function(){
		dataMenu.hide();
		var sibs = $(':button',toolBar).not(this)
			, folded = btToggle.attr('folded')?true:false;//btDataMenu.is(':visible');
		if( !folded){
			sibs.attr('style','display:none!important');
		}else{
			sibs.show();
		}
		btToggle.attr('folded',folded?'':'true').button('option','icon',folded?'ui-icon-circle-triangle-e':'ui-icon-circle-triangle-w').mouseleave();
		cookies.set('SMVCDevToggle',folded?0:1);
		return false;
	});

	if( phped.length ){
		if(! phperrDiv.is(':visible')){
			btPhperr.click();
		}
	}else if( dbMsgs.length ){
		btDb.click();
	}else{
		var openedPanel = cookies.get('SMVCDevBarPanel');
		if( openedPanel ){
			eval('bt'+openedPanel+'.click();');
		}
	}

	if( cookies.get('SMVCDevToggle') == 1 ){ // toggle devel bar
		btToggle.click();
	}

	if( $.isFunction($.fn.buttonset) ){
		$('#sMVCtoolBar').buttonset();
	}
});
