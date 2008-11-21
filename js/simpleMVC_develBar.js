/**
* simpleMVC debug toolbar
* @svnInfos:
*            - $LastChangedDate$
*            - $LastChangedRevision$
*            - $LastChangedBy$
*            - $HeadURL$
*/
jQuery().ready(function(){

	//create toolbar
	$('<div id="sMVCtoolBar"><button id="sMVCshow">User Errors</button> <button id="sMVCphperr">PHP Errors</button></div>\
	<div id="sMVCshow_div"><h1>User Errors</h1><span class="toggle" style="cursor:pointer;">Expand/collapse all</span></div>\
	<div id="sMVCphperr_div"><h1>Php Errors</h1></div>\
	').prependTo('body');
	var toolBar = $('#sMVCtoolBar');
	var btShow  = $('#sMVCshow');
	var showDiv = $('#sMVCshow_div');
	var btPhperr  = $('#sMVCphperr');
	var phperrDiv = $('#sMVCphperr_div');
	toolBar.css({
		position:'absolute',right:0,top:0,
		padding:5, zIndex:1000, background:'#F0F0F0',
		border:'solid red 1px', color:'red'
	});

	var pannelStyle = {
		background:'white', position:'absolute', left:0, top:0,
		zIndex:1000, maxHeight:'500px', overflow:'auto', display:'none'
	};

	//-- Manage show display
	var showed = $('div.dbg');
	if( showed.length == 0){
		btShow.remove();
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
			showDiv.width($('body').width()-toolBar.width()-12)
				.css('maxHeight',"98%")
				.toggle();
		});
	}
	//-- Manage PHP ERRORS display
	var phped = $('div.php_error');
	if( phped.length==0){
		btPhperr.remove();
	}else{
		btPhperr.append('('+phped.length+')');
		phped.each(function(){this.innerHTML = this.innerHTML.replace(/(^<br\s*\/?>|<br\s*\/?>$)/,'');});
		phperrDiv.append(phped)
			.css(pannelStyle);
		btPhperr.click(function(){
			showDiv.hide();
			phperrDiv.width($('body').width()-toolBar.width()-12)
				.css('maxHeight',"98%")
				.toggle();
		});
	}

});