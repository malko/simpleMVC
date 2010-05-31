(function(){
/**
	allow an element to be positioned at the given offset setting its position handler.
	be aware that margin are taken into consideration when positionning the element.
*/
$.toolkit('tk.positionable',{
	_classNameOptions:{
		marginRef:'|margin',
		yRef:'(inner)?([tT]op|[mM]iddle|[bB]ottom)',
		xRef:'(inner)?([Ll]eft|[cC]enter|[rR]ight)'
	},
	_xRef:0,
	_yRef:0,

	_init:function(){
		this.elmt.css('position','absolute');
		this.update();
	},
	get_options:function(){
		return this.options;
	},
	update:function(){
		this._applyOpts('xRef|yRef');
	},
	//-- at this time only work with percent and px units
	__getPxVal:function(v){
		if( typeof v === 'number'){
			return v;
		}
		v = v.match(/^\s*(-?\d+)\s*(%|em|px)?\s*$/);
		if(! v){
			return 0;
		}
		v[1] = parseFloat(v[1]);
		switch(v[2]?v[2].toLowerCase():''){
			case '%':
				v=this.elmt.width()*v[1]/100;
				break;
			default:
				v=v[1];
		}
		return v;
	},
	_set_marginRef:function(v){
		v = v?true:false;
		if(this._tk.initialized){
			this.options.marginRef=v;
			this.update();
		}
		return v;
	},
	_set_xRef:function(x){
		switch(x){
			case 'left': x=0; break;
			case 'innerLeft': x=this.__getPxVal(this.elmt.css('borderLeftWidth')); break;
			case 'center':
			case 'innerCenter': x=this.elmt.outerWidth()/2; break;
			case 'right': x=this.elmt.outerWidth(); break;
			case 'innerRight': x=this.elmt.outerWidth()-this.__getPxVal(this.elmt.css('borderLeftWidth')); break;
			default:
				x=this.__getPxVal(x);
		}
		if(isNaN(x)){
			x=0;
		}
		if(! this.options.marginRef){
			x += this.__getPxVal(this.elmt.css('marginLeft'));
		}
		this._xRef=Math.round(-x);
		this._applyOpts('x');
	},
	_set_yRef:function(y){
		switch(y){
			case 'top': y=0; break;
			case 'innerTop': y=this.__getPxVal(this.elmt.css('borderTopWidth')); break;
			case 'middle':
			case 'innerMiddle': y=this.elmt.outerHeight()/2; break;
			case 'bottom': y=this.elmt.outerHeight(); break;
			case 'innerBottom': y=this.elmt.outerHeight()-this.__getPxVal(this.elmt.css('borderTopWidth')); break;
			default:
				y=this.__getPxVal(y);
		}
		if(isNaN(y)){
			y=0;
		}
		if(! this.options.marginRef){
			y += this.__getPxVal(this.elmt.css('marginTop'));
		}
		this._yRef=Math.round(-y);
		this._applyOpts('y');
	},
	_set_x:function(x){
		this.elmt.css({
			'right':'',
			'left':Math.round(this.__getPxVal(x)+this._xRef)
		});
	},
	_set_y:function(y){
		this.elmt.css({
			'bottom':'',
			'top':Math.round(this.__getPxVal(y)+this._yRef)
		});
	}

});
$.tk.positionable.defaults={
	xRef:'left', // [inner](left|center|right) or pixel offset from left
	yRef:'top',  // [inner](top|middle|bottom) or pixel offset from top
	marginRef:false, // if true then top and left margin will change the handle position accordingly.
	x:0,
	y:0
}

/** help in positionning element related to another one */
$.toolkit('tk.positionRelative',{
	_classNameOptions:{
		edgePolicy:'|(opposite|stick|none)?(opposite|stick|none)',
		borderPolicy:'|in(out)?|out(in)?',
		vPos: '|(middle|inner)?([tT]op|[bB]ottom)|(upper|lower)?([mM]iddle)',
		hPos: '|(middle|inner)?([lL]eft|[rR]ight)|(right|left)?([cC]enter)',
		space:'|[0-9]+' // set hSpace and vSpace all at once
	},
	_realPos:null,
	_related: null,

	_init:function(){
		// place element in the main area
		this._realPos = {v:null,h:null};
		this.elmt.appendTo('body').positionable({marginRef:false});
		// if none given the rel attribute is considered as a selector
		if(! this.options.related ){
			var rel = this.elmt.attr('rel');
			if( rel != '' ){
				this.options.related = rel;
			}
		}
		this._applyOpts('related|borderPolicy|edgePolicy|space|vPos|hPos');
	},
	_set_related:function(elmt){
		this._related=$(elmt).filter(':first');
		if( this._tk.initialized ){
			this.update();
			this._trigger('changerelated',null,[this.elmt,elmt]);
		}
	},
	_set_borderPolicy:function(p){
		switch(p){
			case 'in':
			case 'out':
				this.set('vBorderPolicy',p);
				this.set('hBorderPolicy',p);
				break;
			case 'inout':
				this.set('vBorderPolicy','in');
				this.set('hBorderPolicy','out');
				break;
			case 'outin':
				this.set('vBorderPolicy','out');
				this.set('hBorderPolicy','in');
				break;
		}
		delete this.options.borderPolicy;
	},
	_set_vBorderPolicy:function(p){
		p=p==='in'?'in':'out';
		if( this._tk.initialized){
			this.options.vBorderPolicy=p;
			this._updateVPos();
		}
		return p;
	},
	_set_hBorderPolicy:function(p){
		p=p==='in'?'in':'out';
		if( this._tk.initialized){
			this.options.hBorderPolicy=p;
			this._updateHPos();
		}
		return p;
	},
	_set_space:function(s){
		this.set('vSpace',s);
		this.set('hSpace',s);
		delete this.options.space;
	},
	_set_vSpace:function(s){
		s = parseInt(s);
		if(isNaN(s)){s=0;}
		this.options.vSpace=s;
		this._updateVPos();
		return s;
	},
	_set_hSpace:function(s){
		s = parseInt(s);
		if(isNaN(s)){s=0;}
		this.options.hSpace=s;
		this._updateHPos();
		return s;
	},
	_set_vPos:function(vPos){
		return this._updateVPos(vPos);
	},
	_set_hPos:function(hPos){
		return this._updateHPos(hPos);
	},
	_set_edgePolicy:function(p){
		if( p === undefined)
			p='none';
		switch(p){
			case 'none':
			case 'opposite':
			case 'stick':
				this.set('vEdgePolicy',p);
				this.set('hEdgePolicy',p);
				break;
			default:
				p = p.match(/(none|opposite|stick)(none|opposite|stick)/);
				if(! p){ p = ['','none','none']; }
				this.set('vEdgePolicy',p[1]);
				this.set('hEdgePolicy',p[2]);
				break;
		}
		delete this.options.edgePolicy;
	},
	_set_vEdgePolicy:function(p){
		if( this._tk.initialized ){
			this.options.vEdgePolicy = p;
			this._updateVPos();
		}
	},
	_set_hEdgePolicy:function(p){
		if( this._tk.initialized ){
			this.options.hEdgePolicy = p;
			this._updateHPos();
		}
	},
	realpos:function(){
		return this._realPos;
	},

	update:function(){
		this._updateHPos();
		this._updateVPos();
	},
	//- internal methods
	_updateVPos:function(vPos){
		if( undefined===vPos ){
			vPos = this.options.vPos;
		}else if(this._tk.initialized && vPos===this.options.vPos){
			return vPos;
		}
		if( ! this._related.length ){
			//- dbg('no related')
			return vPos;
		}
		var offset = this._related.offset(),
			inner = vPos.toString().match(/^inner/),
			s = inner?-this.options.vSpace:this.options.vSpace,
			borderPolicy=this.options.vBorderPolicy==='in'?true:false,
			pos = { yRef:'top', y:0 };

		switch(vPos){
			case 'top':
			case 'middleTop':
			case 'innerTop':
				pos.y += offset.top - s + (borderPolicy?parseFloat(this._related.css('borderTopWidth')):0);
				pos.yRef = vPos.match(/^middle/)?'middle':(inner?'top':'bottom');
				break;
			case 'bottom':
			case 'middleBottom':
			case 'innerBottom':
				pos.y += offset.top + this._related.outerHeight() + s - (borderPolicy?parseFloat(this._related.css('borderBottomWidth')):0);
				pos.yRef = vPos.match(/^middle/)?'middle':(inner?'bottom':'top');
				break;
			case  'middle':
			case  'upperMiddle':
			case  'lowerMiddle':
				if(! borderPolicy ){
					var h = this._related.outerHeight()/2;
				}else{
					var h = this._related.innerHeight()/2 + parseFloat(this._related.css('borderTopWidth'));
				}
				pos.y += offset.top  + h;
				pos.yRef=vPos.match(/^upper/)?'bottom':(vPos.match(/^lower/)?'top':'middle');
				break;
			default:
				pos.y += offset.top + parseFloat(vPos) - s + (borderPolicy?parseFloat(this._related.css('borderTopWidth')):0);
		}
		this.elmt.positionable('set',pos);
		if( vPos !== this._realPos.v ){
			this.elmt.removeClass(this.options.vPrefixClass+this._realPos.v).addClass(this.options.vPrefixClass+vPos);
			this._realPos.v = vPos;
			this._trigger('_updatevpos',false,[this.elmt,vPos]);
		}
		this.vEdgeDetection();
		return vPos;
	},
	_updateHPos:function(hPos){
		if( undefined===hPos ){
			hPos = this.options.hPos;
		}else if(this._tk.initialized && hPos===this.options.hPos){
			return hPos;
		}
		if( ! this._related.length ){
			//- dbg('no related')
			return hPos;
		}
		var inner = hPos.toString().match(/^inner/),
			s = inner?-this.options.hSpace:this.options.hSpace,
			borderPolicy=this.options.hBorderPolicy==='in'?true:false,
			offset = this._related.offset(),
			pos = { xRef:'left', x:0 };

		switch(hPos){
			case 'left':
			case 'middleLeft':
			case 'innerLeft':
				pos.x += offset.left - s + (borderPolicy?parseFloat(this._related.css('borderLeftWidth')):0);
				pos.xRef = hPos.match(/^middle/)?'center':(inner?'left':'right');
				break;
			case 'right':
			case 'middleRight':
			case 'innerRight':
				pos.x += offset.left + this._related.outerWidth()+ s - (borderPolicy?parseFloat(this._related.css('borderRightWidth')):0);
				pos.xRef = hPos.match(/^middle/)?'center':(inner?'right':'left');
				break;
			case 'center':
			case 'leftCenter':
			case 'rightCenter':
				if(! borderPolicy ){
					var w = this._related.outerWidth()/2;
				}else{
					var w = this._related.innerWidth()/2 + parseFloat(this._related.css('borderLeftWidth'));
				}
				pos.x += offset.left + w ;
				pos.xRef = hPos.match(/^left/)?'right':(hPos.match(/^right/)?'left':'center');
				break;
			default:
				pos.x += offset.left + parseFloat(hPos) - s + (borderPolicy?parseFloat(this._related.css('borderLeftWidth')):0);
		}
		this.elmt.positionable('set',pos);
		if( hPos !== this._realPos.h ){
			this.elmt.removeClass(this.options.hPrefixClass+this._realPos.h).addClass(this.options.hPrefixClass+hPos);
			this._realPos.h = hPos;
			this._trigger('_updatehpos',false,[this.elmt,hPos]);
		}
		this.hEdgeDetection();
		return hPos;
	},
	__replaceKeepingFirstCase:function(haystack,needle,replace){
		var fChar = needle.substr(0,1);
		needle = "(["+fChar+fChar.toUpperCase()+"])"+needle.substr(1);
		return haystack.replace(new RegExp(needle),function(a,b){return b.match(/[A-Z]/)?replace.substr(0,1).toUpperCase()+replace.substr(1):replace});
	},
	vEdgeDetection:function(){
		if( this.options.vEdgePolicy==="none" )
			return;
		var w    = $(window),
			wTop   = w.scrollTop(),
			wBottom= wTop + w.height(),
			offset = this.elmt.offset(),
			policy = this.options.vEdgePolicy,
			p = this.options.vPos.match(/(bottom|top|middle)$/i);
		if(! p ){
			return;
		}
		this.options.vEdgePolicy='none';
		p = p[1].toLowerCase();
		switch(p){
			case 'top':
				if( offset.top < wTop ){
					if( policy==="opposite" ){
						this._updateVPos(this.__replaceKeepingFirstCase(this.options.vPos,'top','bottom'));
						if( this.elmt.offset().top+this.elmt.outerHeight() > wBottom ){
							this._updateVPos(this.options.vPos);
						}
					}else if( wTop < (this._related.offset().top + this._related.outerHeight() + this.options.vSpace)){ // mean stick
						this.elmt.positionable('set',{yRef:'top',y:wTop});
					}
				}
				break;
			case 'bottom':
				if(offset.top + this.elmt.outerHeight() > wBottom ){
					if( policy==="opposite" ){
						this._updateVPos(this.__replaceKeepingFirstCase(this.options.vPos,'bottom','top'));
						//-- check we don't put it oustide in the opposite
						if( this.elmt.offset().top < wTop ){
							this._updateVPos(this.options.vPos);
						}
					}else if( wBottom > (this._related.offset().top - this.options.vSpace) ) { //stick policy
						this.elmt.positionable('set',{yRef:'bottom',y:wBottom});
					}
				}
				break;
			//@todo manage pixel and middle positionning
		}
		this.options.vEdgePolicy=policy;
	},
	hEdgeDetection:function(){
		if( this.options.hEdgePolicy==="none" )
			return;
		var w    = $(window),
			wLeft  = w.scrollLeft(),
			wRight = wLeft + w.width(),
			offset = this.elmt.offset(),
			policy = this.options.hEdgePolicy,
			p = this.options.hPos.match(/(left|right|center)$/i);
		if(! p ){
			return;
		}
		this.options.hEdgePolicy='none';
		p = p[1].toLowerCase();
		switch(p){
			case 'left':
				if( offset.left < wLeft ){
					if( policy==="opposite" ){
						this._updateHPos(this.__replaceKeepingFirstCase(this.options.hPos,'left','right'));
						if( this.elmt.offset().left+this.elmt.outerWidth() > wRight ){
							this._updateHPos(this.options.hPos);
						}
					}else if( wLeft < (this._related.offset().left + this._related.outerWidth() + this.options.hSpace)){ // mean stick
						this.elmt.positionable('set',{xRef:'left',x:wLeft});
					}
				}
				break;
			case 'right':
				if(offset.left + this.elmt.outerWidth() > wRight ){
					if( policy==="opposite" ){
						this._updateHPos(this.__replaceKeepingFirstCase(this.options.hPos,'right','left'));
						//-- check we don't put it oustide in the opposite
						if( this.elmt.offset().left < wLeft ){
							this._updateHPos(this.options.hPos);
						}
					}else if( wRight > (this._related.offset().left - this.options.hSpace) ) { //stick policy
						this.elmt.positionable('set',{xRef:'right',x:wRight});
					}
				}
				break;
			//@todo manage pixel and center positionning
		}
		this.options.hEdgePolicy=policy;
	}

});

$.tk.positionRelative.defaults={
	vPos:'middle',            // one of [inner|middle](top|bottom) or middle or a pixel value.
	hPos:'center',            // one of [inner|middle](left|right) or center or a pixel value.
	vSpace:0,                 // spacing to add vertically
	hSpace:0,                 // spacing to add horizontally
	related:null,             // object determining our main position
	vBorderPolicy:'out',      // in or out specify if we work with the in or out side of the vertical border
	hBorderPolicy:'out',      // in or out specify if we work with the in or out side of the horizontal border
	vEdgePolicy:'stick',   // none|opposite|stick
	hEdgePolicy:'stick',        // none|opposite|stick
	vPrefixClass:'tk-vpos-',
	hPrefixClass:'tk-hpos-'
};


$.toolkit.mouseRelative={
	_traking:{ },
	_binded:false,
	ids:0,
	elmt:null,
	init:function(elmt,options){
		var self = this;
		self.elmt = $('<div id="tk-mouse" style="position:absolute;top:1px;left:O;width:1px;height:1px;visibility:hidden;"></div>')
			.appendTo('body');
	},
	/**
	bind/unbind mousemove event as needed
	*/
	_checkBinding:function(){
		var self=this,needed = false,id='';
		for( id in self._traking){ needed = true;break; }
		if( self._binded && ! needed){
			$(window).unbind('mousemove.mouseRelative');
			self._binded = false;
		}else if( needed && ! self._binded ){
			$(window).bind('mousemove.mouseRelative',(function(e){self.update(e);	}));
			self._binded = true;
		}
	},
	trackStart:function(mouseRelative){
		if(! this.elmt){
			this.init();
		}
		this._traking[mouseRelative._mouseRelativeId] = mouseRelative;
		this._checkBinding();
	},
	trackEnd:function(mouseRelative){
		if(! this.elmt){
			this.init();
		}
		delete this._traking[mouseRelative._mouseRelativeId];
		this._checkBinding();
	},
	update:function(e){
		this.elmt.css({top:1+e.pageY,left:1+e.pageX});
		for(var i in this._traking ){
			this._traking[i].elmt.positionRelative('update');
		}
	}
}

$.toolkit('tk.mouseRelative',{
	_mouseRelativeId:0,
	_init:function(){
		var self = this;
		if(null===$.toolkit.mouseRelative.elmt){
			$.toolkit.mouseRelative.init();
		}
		self._mouseRelativeId = $.toolkit.mouseRelative.ids++;
		self.elmt.positionRelative('set',$.extend({},self.options,{related:$.toolkit.mouseRelative.elmt}));
		self._applyOpts('tracking');
		self.elmt.bind('positionRelative_changerelated',function(e,elmt,related){
			if( related !== $.toolkit.mouseRelative.elmt ){
				self._set_tracking(false);
			}
		})
	},
	_set_tracking:function(v){
		v = v?true:false;
		if( v ){
			if( this.elmt.positionRelative('get1_related') !== $.toolkit.mouseRelative.elmt){ // need to reset related element of positionRelative
				this.elmt.positionRelative('set_related',$.toolkit.mouseRelative.elmt);
			}
			$.toolkit.mouseRelative.trackStart(this);
		}else{
			$.toolkit.mouseRelative.trackEnd(this);
		}
		return v;
	}
});

$.tk.mouseRelative.defaults = { tracking:true};

})(jQuery);