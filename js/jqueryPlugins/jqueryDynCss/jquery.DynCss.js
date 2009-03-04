/**
* jquery plugin to manage variables inside css files on the client side
* @licence GPL / MIT
* @author jonathan gotti < jgotti at jgotti dot org > < jgotti at modedemploi dot fr > for modedemploi.fr
* @since 2009-01
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
		load : function(path){
			$.get(path,{DynCssPath:encodeURI(path)},DynCss.parse,'text');
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
			var path = decodeURIComponent(this.url.replace(/^.*\?DynCssPath=(.*)$/,'$1')).replace(/[^\/]+\.css$/,'');
			res = res.replace(/url\s*\(\s*(?!\/|http:\/\/)/ig,'url('+path);
			$('head').append('<style type="text/css" isDynCss="true">'+res+'</style>');
		}
	};
	$.extend({ DynCss: DynCss.load});
})(jQuery);


