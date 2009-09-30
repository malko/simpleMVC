/**
* jquery plugin to manage variables inside css files on the client side
* @licence GPL / MIT
* @author jonathan gotti < jgotti at jgotti dot org > < jgotti at modedemploi dot fr > for modedemploi.fr
* @since 2009-01
* @changelog
*            - 2009-02-10 - add onAfterParse callback support
* sample usage:
* - First step: at the very begining of your css styleSheet define your variables like this:
*     DynCss.rules={ bgred:'background:red;', green:'#00ff00'} // <- this must be valid javascript
* - Second step: use vars in your styleSheet prefixing them by '@' like this:
*     body { @bgred }
*     .green { color:@g; }
* - Third step inside your page include the plugin (using script tag) and import your stylSheets like this:
*     $.DynCss('path/to/your/css.css');
* and voila :)
*/
(function($){
	DynCss = {
		rules: {},
		datas: [],
		/**
		* load a dynamic css file.
		* @param string path         dynamic css file URI
		* @param mixed  onAfterParse callback function (or just it's name as string) to execute when parse is done
		*                            callback prototype has to look like this: function(styleElement,DynCssRules){...}
		*                            where styleElement is the newly created style tag containings parsed css
		*                            and DynCssRules are the defined vars in the dynamic css
		*/
		load : function(path,onAfterParse){
			var parseId = DynCss.datas.length;
			DynCss.datas[parseId] = [path,onAfterParse];
			$.get(path,{DynCssId:parseId},DynCss.parse,'text');
		},
		parse:function(res,state){
			// extract  DynCss rules
			if( res.match('DynCss.rules')){
				eval("var rules = res.replace(/^(.*DynCss\.rules[^\}]+\})(.|\\r?\\n)*$/,'$1');");
				eval(rules);
				// extract real css rules
				res = res.replace(rules,'');
				// do var replacement
				for(p in DynCss.rules){
					eval('res = res.replace(/@'+p+'(?![A-Za-z0-9])/g,DynCss.rules[p]);');
				}
			}
			// then correct url handlings
			var parseId = this.url.replace(/^.*\?DynCssId=(\d+)$/,'$1');
			var datas = DynCss.datas[parseId];
			res = res.replace(/url\s*\(\s*(?!\/|http:\/\/)/ig,'url('+datas[0].replace(/[^\/]+\.css$/,''));
			var e = $('<style type="text/css" isDynCss="true">'+res+'</style>').appendTo('head');
			// finally execute onAfterParse callback
			if( undefined !== datas[1] ){
				if( typeof(datas[1]) === 'string')
					eval(datas[1]+'(e.get(0),DynCss.rules);');
				else
					datas[1](e.get(0),DynCss.rules);
			}
		}
	};
	$.extend({DynCss: DynCss.load});
})(jQuery);
