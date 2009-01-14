/**
* simpleMVC debug toolbar
* @changelog - 2009-01-14 - add quick cookie management to keep trace of last opened panel
* @svnInfos:
*            - $LastChangedDate$
*            - $LastChangedRevision$
*            - $LastChangedBy$
*            - $HeadURL$
*/
jQuery().ready(function(){

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
			(!path? '': '; path='+path)+ (!domain? '': '; domain='+domain)+ (!secure? '': '; secure');
	},
	del:function(name, path, domain){
			var value=this.get(name);
			document.cookie=name+ '='+ (!path? '': '; path='+path)+
			(!domain? '': '; domain='+domain)+
			'; expires=Thu, 01-Jan-70 00:00:01 GMT';
			return value;
	},
};

	//create toolbar
	$('<div id="sMVCtoolBar"><button id="sMVCshow">Show </button> <button id="sMVCphperr">PHP Errors </button> <button id="sMVCdb">Db::profiler</button></div>\
	<div id="sMVCshow_div"><h1><span class="toggle" style="cursor:pointer;font-weight:normal;float:right;" title="Expand/collapse all">[+/-]</span>Show</h1></div>\
	<div id="sMVCphperr_div"><h1>Php Errors</h1></div>\
	<div id="sMVCdb_div"><h1>Db::profiler</h1></div>\
	').prependTo('body');
	var toolBar = $('#sMVCtoolBar');
	var btShow  = $('#sMVCshow');
	var showDiv = $('#sMVCshow_div');
	var btPhperr  = $('#sMVCphperr');
	var phperrDiv = $('#sMVCphperr_div');
	var showDiv = $('#sMVCshow_div');
	var btDb  = $('#sMVCdb');
	var dbDiv = $('#sMVCdb_div');
	toolBar.css({
		position:'absolute',right:0,top:0,
		padding:5, zIndex:1000, background:'#F0F0F0',
		border:'solid red 1px', color:'red'
	});

	var pannelStyle = {
		background:'white', position:'absolute', left:0, top:0,
		zIndex:1000,  overflow:'auto', display:'none',
		border:'solid red 1px'
	};

	function getWidth(){
		return $('body').width()-toolBar.width()-($.boxModel?14:32);
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
	var showed = $('div.dbg');
	if( showed.length == 0){
		btShow.remove();
		showDiv.remove();
	}else{
		btShow.append('('+showed.length+')');
		showDiv.append(showed)
			.css(pannelStyle);
		$('div.dbg').css('margin','0 20px');
		$('.toggle',showDiv).click(function(){
			var titles   = $('div.dbg strong');
			var opened   = titles.siblings('pre:visible').length
			var closed = titles.length - opened;
			if( opened > closed)
				titles.each(function(){ var t= $(this); if(t.siblings('pre:visible').length) t.click() });
			else
				titles.each(function(){ var t= $(this); if(t.siblings('pre:hidden').length) t.click() });
		});
		btShow.click(function(){
			phperrDiv.hide();
			dbDiv.hide();
			showDiv.width(getWidth())
				.css('maxHeight',"98%")
				.toggle();
			setPanelCookie();
		});
	}

	//-- Manage PHP ERRORS display
	var phped = $('div.php_error');
	if( phped.length==0){
		btPhperr.remove();
		phperrDiv.remove();
	}else{
		btPhperr.append('('+phped.length+')');
		phped.each(function(){this.innerHTML = this.innerHTML.replace(/(^<br\s*\/?>|<br\s*\/?>$)/,'');});
		phperrDiv.append(phped)
			.css(pannelStyle);
		btPhperr.click(function(){
			showDiv.hide();
			dbDiv.hide();
			phperrDiv.width(getWidth())
				.css('maxHeight',"98%")
				.toggle();
			setPanelCookie();
		});
	}

	//-- Manage DB::profiler reports display
	var report = $('#dbProfilerReport').parent('table');
	if( report.length==0){
		btDb.remove();
		dbDiv.remove();
	}else{
		report.children('caption').click();
		dbDiv.append(report)
			.css(pannelStyle);
		btDb.click(function(){
			showDiv.hide();
			phperrDiv.hide();
			dbDiv.width(getWidth())
				.css('maxHeight',"98%")
				.toggle();
			setPanelCookie();
		});
	}
	//-- no report to handle so just remove the bar
	if(toolBar.html().match(/^\s*$/)){
		toolBar.remove();
	}else{
		var openedPanel = cookies.get('SMVCDevBarPanel');
		if( openedPanel )
			eval('bt'+openedPanel+'.click();');
	}

});
