/**
some function to set some css rules accross many browser with dynamic values.
@author jonathan gotti <jgotti at jgotti dot net>
@licence Dual licensed under the MIT (MIT-LICENSE.txt) and GPL (GPL-LICENSE.txt) licenses.

*/
(function($) {
var extendedRules = {
	background  : ['backgroundAttachment','backgroundColor','backgroundImage','backgroundPosition','backgroundRepeat'],
	border      : ['borderColor','borderCollapse','borderStyle','borderWidth','borderTop', 'borderRight', 'borderBottom','borderLeft'],
	borderTop   : ['borderTopStyle','borderTopWidth','borderTopColor'],
	borderRight : ['borderRightStyle','borderRightWidth','borderRightColor'],
	borderBottom: ['borderBottomStyle','borderBottomWidth','borderBottomColor'],
	borderLeft  : ['borderLeftStyle','borderLeftWidth','borderLeftColor'],
	borderRadius: ['borderTopLeftRadius','borderTopRightRadius','borderBottomLeftRadius','borderBottomRightRadius','MozBorderRadius','webkitBorderRadius'],
	borderRadiusTopLeft:['MozBorderRadiusTopLeft','webkitBorderTopLeftRadius'],
	borderRadiusTopRight:['MozBorderRadiusTopRight','webkitBorderTopRightRadius'],
	borderRadiusBottomLeft:['MozBorderRadiusBottomLeft','webkitBorderBottomLeftRadius'],
	borderRadiusBottomRight:['MozBorderRadiusBottomRight','webkitBorderBottomRightRadius'],
	font        : ['fontFamily','fontSize','fontStyle','fontVariant','fontWeight'],
	listStyle   : ['listStyleImage','listStylePosition','listStyleType'],
	margin      : ['marginTop','marginRight','marginBottom','marginLeft'],
	opacity			: ['MozOpacity','webkitOpacity','filter'],
	outline     : ['outlineColor','outlineStyle','outlineWidth'],
	padding     : ['paddingTop','paddingRight','paddingBottom','paddingLeft']
};

var _copyCssRules =function (rules,from,to,transfert){
	if( typeof rules === 'string' ){
		rules = rules.split(/[,|]/);
	}
	from = $(from);
	to   = $(to);
	var l = rules.length,
		rule = '',
		val = '';
	for(var i=0; i<l; i++){
		rule = rules[i];
		if( rule.indexOf('-') ) //-- ensure rule to be camelCased
			rule = rule.replace(/-([a-z])/ig, function(fullmatch,captureOffset1){ return captureOffset1.toUpperCase(); });
		if( rule !== 'margin' ){// margin cause a stop exec bug with ie
			val = from.css(rule);
			if( val && val !== ''){
				to.css(rule,val);
			}
		}
		if( extendedRules[rule] ){
			_copyCssRules(extendedRules[rule],from,to,transfert);
		}
		if( transfert ){
			from.css(rule,'');
		}
	}
};

$.toolkit('tk.xcss',{

	_classNameOptions:{
		opacity:"|opacity\\d+",
		borderRadius:"|b(orderR|r)adius[0-9]+"
	},

	_init:function(){
		//- dbg(this.elmt,this.options)
		this._applyOpts(["opacity","borderRadius"],true);
	},

	importRules:function(rules,from,transfert){
		_copyCssRules(rules,from,this.elmt,transfert);
		return this;
	},
	exportRules:function(rules,to,transfert){
		_copyCssRules(rules,this.elmt,to,transfert);
		return this;
	},

	_set_opacity:function(v){
		this.opacity(v.replace(/^opacity(\d+)$/,"$1"));
	},
	opacity:function(v){
		if( v>1) v = v/100;
		this.elmt.css({
			'-moz-opacity':v,
			'-khtml-opacity':v,
			'opacity':v,
			'filter':'alpha(opacity='+(v*100)+')'
		});
	},

	_set_borderRadius:function(v){
		this.borderRadius(v.replace(/^b(orderR|r)adius(\d+)$/,"$2"));
	},
	borderRadius:function(v){
		//-- work on clone for test
		if( v.toString().match(/^\d+$/)){
			v+='px';
		}
		this.elmt.css({
			'-moz-border-radius':v,
			'-webkit-border-radius':v,
			'border-radius':v
		});
	}

});
$.tk.xcss.defaults={};


})(jQuery);
/*
ie border radius attempt
if( $.browser.msie ||  $.browser.version<8){

			if (!document.namespaces.v) {
				document.namespaces.add("v", "urn:schemas-microsoft-com:vml");
			}

    //var arcSize = Math.min(parseInt(v) / Math.min(this.offsetWidth, this.offsetHeight), 1),
		var arcSize   = parseFloat(v) / (Math.min(this.elmt.outerWidth(),this.elmt.outerHeight())),
		//	var arcSize   = parseInt(v)/90,
				strokeColor = this.elmt.css("borderColor"),
				strokeWeight= parseInt(this.elmt.css("borderWidth")),
				fillColor   = this.elmt.css("backgroundColor"),
				fillSrc     = this.elmt.css("backgroundImage").replace(/^url\("(.+)"\)$/, '$1'),
				deltaPos 		= Math.round(parseInt(strokeWeight)/2),
				sizePerc    = Math.round(100-100*strokeWeight/this.elmt.width());
			++id;
			this.elmt.wrap('<div id="tk-bradius-wrapper'+id+'"></div>')
			.wrap(
				'<v:roundrect id="tk-bradius-vrect'+id+'"style="behavior:url(#default#VML);'+
				'width:'+sizePerc+'%;height:'+sizePerc+'%;antialias:true;zoom:1;'+
				'display:inline-block;position:relative;top:'+deltaPos+'px;left:'+(deltaPos)+'px" '+
				'arcsize="'+Math.min(arcSize,1.0)+'" strokecolor="'+strokeColor+
				'" strokeweight="' + strokeWeight +'" fillcolor="'+fillColor+'" >'+
//- 				(fillSrc?'<v:fill src="'+fillSrc+'" type="tile" style="behavior: url(#default#VML);"></v:fill>':'')+
				'</v:roundrect>'
			);
			if( fillSrc)
				this.elmt.before('<v:fill src="'+fillSrc+'" type="tile" style="behavior:url(#default#VML);"/>');

			var wrapper = $('#tk-bradius-wrapper'+id).css({
				padding:0 ,border:'solid red 1px'
			});
			//- this.exportRules('margin|float|clear|position|left|right|top|bottom|width|height|zoom|display',$('#vrect'+id),true);
			this.exportRules('margin|float|clear|position|left|right|top|bottom|width|height|zoom|display',wrapper,true)
				.exportRules('padding',$('#vrect'+id),true);
			//- this.exportRules('margin|float|clear|position|left|right|top|bottom|width|height',wrapper.find('roundrect'),true);
			//- $('#vrect'+id).css({
				//- margin:'0'
			//- });
			this.elmt.css({
				background:'transparent',
				border:'none',
				width :'100%',
				height:'100%',
				margin:'0px'
			})

}*/