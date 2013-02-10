(function(){
	var S = document.getElementsByTagName('script')
		, path= S[S.length - 1 ].src.replace(/[^\/]+\.js$/,'')
		, jsPath = path ? path.replace(/[^\/]+$/,'') : '../'
		, tkPluginPath = jsPath+'/jquery.toolkit/src/plugins/'
	;

	ljs.addAliases({
		jquery: jsPath+'jquery.js'
		,ui: [
			'jquery'
			,jsPath+'css/base/ui.all.css'
			,jsPath+'jquery-ui.min.js'
			,jsPath+'jqueryPlugins/bgiframe/jquery.bgiframe.min.js'
			,function(){ $.ui.dialog.defaults.bgiframe = true; }
		]
		,button:[
			'ui'
			,jsPath+'ui-button.css'
			,jsPath+'ui-button.min.js'
		]
		,tk: [
			'jquery'
			,jsPath+'jquery.toolkit/src/jquery.toolkit.css'
			,jsPath+'jquery.toolkit/src/jquery.toolkit.js'
			,jsPath+'jquery.toolkit/src/jquery.toolkit.storage.js'
		]
		,dialogbox:[
			'tk'
			,'postion'
			,tkPluginPath+'dialogbox/jquery.tk.dialogbox.css'
			,tkPluginPath+'dialogbox/jquery.tk.dialogbox.js'
		]
		,keypad:[
			'tk'
			,tkPluginPath+'keypad/jquery.tk.keypad.css'
			,tkPluginPath+'keypad/jquery.tk.keypad.js'
		]
		,measure:[
			'tk'
			,tkPluginPath+'measure/jquery.tk.measure.css'
			,tkPluginPath+'measure/jquery.tk.measure.js'
		]
		,notify:[
			'tk'
			,'position'
			,tkPluginPath+'notify/jquery.tk.notify.css'
			,tkPluginPath+'notify/jquery.tk.notify.js'
		]
		,position:[
			'tk'
			,tkPluginPath+'position/jquery.tk.position.js'
		]
		,tkPlaceholder:[
			'tk'
			,tkPluginPath+'placeholder/jquery.tk.placeholder.js'
		]
		,shortcuts:[
			'tk'
			,tkPluginPath+'shortcuts/jquery.tk.shortcuts.js'
		]
		,splitpane:[
			'tk'
			,tkPluginPath+'splitpane/jquery.tk.splitpane.css'
			,tkPluginPath+'splitpane/jquery.tk.splitpane.js'
		]
		,tooltip:[
			'tk'
			,tkPluginPath+'tooltip/jquery.tk.tooltip.css'
			,tkPluginPath+'tooltip/jquery.tk.tooltip.js'
		]
		,treemap:[
			'tk'
			,tkPluginPath+'treemap/jquery.tk.treemap.css'
			,tkPluginPath+'treemap/jquery.tk.treemap.js'
		]
		,validable:[
			'tk'
			,'tooltip'
			,tkPluginPath+'validable/jquery.tk.validable.css'
			,tkPluginPath+'validable/jquery.tk.validable.js'
		]
	});
}())