/**
simple notification plugin
@author jonathan gotti <jgotti at jgotti dot net>
@licence Dual licensed under the MIT (MIT-LICENSE.txt) and GPL (GPL-LICENSE.txt) licenses.
*/
(function($){
//-- notification plugin
$.toolkit('tk.notifybox',{
	_classNameOptions:{
		vPos:'|top|bottom|middle',
		hPos:'|right|left|center',
		width:'|\\d+'
	},
	_init:function(){
		var self = this;
		self.elmt.appendTo('body');
		//-- if no default notifybox exists make the first one the default
		if( null===$.tk.notifybox.defaultBox ||self.options.isDefault ){
			$.tk.notifybox.defaultBox = self;
		}
		self._applyOpts('width|vPos|hPos');
		if( self.elmt.css('position').toLowerCase() ==='absolute'){ //ie-6 position:fixed lack workaround
			$(window).scroll(function(){self._updatePos()});
			self._updatePos();
		}
	},
	_updatePos:function(){
		var vPos = this.get('vPos'),
			hPos = this.get('hPos');

		var scrollY = [
			window.pageYOffset ? window.pageYOffset : 0,
			document.documentElement ? document.documentElement.scrollTop : 0,
			document.body ? document.body.scrollTop : 0
		]
		var sY = scrollY[1] && (!scrollY[0] || scrollY[0]>scrollY[1])?scrollY[1]:scrollY[0];
		sY = scrollY[2] && (!sY || sY>scrollY[1])?scrollY[1]:sY;

		var scrollX = [
			window.pageXOffset ? window.pageXOffset : 0,
			document.documentElement ? document.documentElement.scrollTop : 0,
			document.bodX ? document.bodX.scrollTop : 0
		]
		var sX = scrollX[1] && (!scrollX[0] || scrollX[0]>scrollX[1])?scrollX[1]:scrollX[0];
		sX = scrollX[2] && (!sX || sX>scrollX[1])?scrollX[1]:sX;


		var vCss={};
		switch(vPos){
			case 'top': vCss.top = (0+sY)+'px';break;
			case 'bottom': vCss.bottom = (0+sY)+'px';break;
			default: vCss.top = parseInt(vPos+sY)+'px';
		}
		this.elmt.css({top:null,bottom:null}).css(vCss);
		return;
		//@todo manage hPos too
	},
	notify:function(elmt,options){
		var e = $(elmt).clone();
		return e.notify($.extend({notifybox:this.elmt,destroy:true},options||{})).get(0);
	},
	msg:function(msg,options){
		return $('<div>'+msg+'</div>').notify($.extend({notifybox:this.elmt,destroy:true},options||{})).get(0);
	},
	_set_vPos:function(pos){
		var css={};
		switch(pos){
			case 'top': css.top = 0;break;
			case 'bottom': css.bottom = 0;break;
			default: css.top = pos;
		}
		this.elmt.css({top:null,bottom:null}).css(css);
	},
	_set_hPos:function(pos){
		var css={};
		switch(pos){
			case 'left': css.left = 0;break;
			case 'right': css.right = 0;break;
			case 'center':
				css= {left:'50%',marginLeft:-(parseInt(this.options.width)/2)};
				break;
			default: css.left = pos;
		}
		this.elmt.css({left:'',right:'',marginLeft:''}).css(css);
	},
	_set_width:function(w){this.elmt.width(w+'px');}
});
//-- default notification box for all notify plugins
$.tk.notifybox.defaultBox=null;

$.tk.notifybox.initDefault=function(options){
	if( null === $.tk.notifybox.defaultBox) //-- init a default notification box
		$('<div></div>').notifybox(options);
	return $.tk.notifybox.defaultBox.elmt;
}

$.tk.notifybox.defaults = {
	isDefault:false,
	vPos:'bottom',
	hPos:'right',
	width:'350'
};

$.toolkit('tk.notify',{
	_classNameOptions:{
		closeButton:'|(no)?close|auto',
		effectShow:'|fadeIn|slideDown|show',
		effectHide:'|fadeOut|slideUp|hide',
		ttl:"|[0-9]+"
	},
	wrapper:{},
	_init:function(){
		// first check for box element
		var self = this;
		self._applyOpts('notifybox');
		var closeButton = true;
		if( (self.options.closeButton==='noclose' || !self.options.closeButton) || (self.options.closeButton==="auto" && self.options.ttl>0) ){
			var closeButton = false;
		}
		self.wrapper = $('<div class="'+self.options.wrapperClassName+'">'+(closeButton?'<div class="tk-icon tk-icon-close" title="close">X</div>':'')+'</div>');
		if( self.options.notifybox.notifybox('get_vPos')[0] === 'bottom'){
			self.wrapper.prependTo(self.options.notifybox);
		}else{
			self.wrapper.appendTo(self.options.notifybox);
		}
		self.wrapper.hide()
			.click(function(e){self.hide(e);})
			.append(self.elmt.show())
			.hover(function(){$(this).addClass('tk-notify-wrapper-hover');},function(){$(this).removeClass('tk-notify-wrapper-hover')});
		self.show();
		if( self.options.ttl > 0){
			setTimeout(function(e){self.hide(e)},self.options.ttl);
		}
	},
	_set_notifybox:function(b){
		if( 'default'===b)
			return $.tk.notifybox.initDefault();
	},
	show:function(e){
		if(false === this._trigger('show',e,[this.elmt]) )
			return false;
		this.wrapper[this.options.effectShow]();
		return true;
	},
	hide:function(e){
		var self = this;
		if(false === self._trigger('hide',e,[this.elmt]) )
			return false;
		self.wrapper[self.options.effectHide]();
		if( self.options.destroy){
			setTimeout(function(){self.wrapper.remove()},self.options.ttl);
		}
		return true;
	}
});
$.tk.notify.defaults={
	notifybox:'default',
	ttl:5000, // 0 mean no automaticly hide
	wrapperClassName:'tk-notify-wrapper',
	effectShow:'fadeIn',
	effectHide:'slideUp',
	closeButton:'auto',// one of true|false or auto (auto mean only when ttl 0)
	destroy:true
};
})(jQuery);