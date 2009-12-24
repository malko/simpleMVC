(function($){
$.toolkit('tk.tooltip',{
	msg:'',
	_wrapper:null,
	_msg:null,
	_pointer:null,
	_pointerBg:null,
	_classNameOptions:{
		stateClass:'|warning|success|error|info',
		position:'|(inner|middle|upper|lower)?([tT]op|[Bb]ottom|[mM]iddle)-(middle|inner|left|right)?([lL]eft|[cC]enter|[rR]ight)?'
	},
	_init:function(){
		var self = this;
		self._wrapper = $('<div class="tk-tooltip-wrapper tk-border tk-corner" ><div class="tk-tooltip-msg"></div><div class="tk-pointer"><div class="tk-pointer-bg"><span/></div></div></div>');
		self._msg = self._wrapper.find('.tk-tooltip-msg');
		self._pointer=self._wrapper.find('.tk-pointer');
		self._pointerBg=self._wrapper.find('.tk-pointer-bg');
		self._wrapper.appendTo('body')
			.positionRelative({related:this.elmt,edgePolicy:'opposite',borderPolicy:'out'})
			.bind('updatehpos updatevpos',function(){self._setPointerColor(true)});

		self.elmt.hover(
			function(){ self.show() },
			function(){ self.hide() }
		);

		// inline options standardisation
		if( this.options.stateClass.match(/^(error|success|warning|info)$/) ){
			this.options.stateClass = 'tk-state-'+this.options.stateClass
		}

		self._applyOpts('stateClass|position|connector|msg|width|height');
		this._setPointerColor(true);

	},
	_set_position:function(pos){
		if( typeof pos==='string' )
			pos = pos.split('-');
		this._wrapper.positionRelative('set',{vPos:pos[0],hPos:pos[1]})
		this._setPointerColor(this._tk.initialized);
		return;
		pos = pos.replace(/[TBRL]/,function(match){return '-'+match.toLowerCase()});
		//- $.toolkit._removeClassExp(this._wrapper,'tk-tooltip-pos-*','tk-tooltip-pos-'+pos);
		this._wrapper.removeClass('tk-tooltip-pos-'+this.options.position).addClass('tk-tooltip-pos-'+pos);
		this.options.position = pos;
		this._setPointerColor(this._tk.initialized);
		if( this._wrapper.is(':visible')){
			this._setDisplayPosition();
		}
		return pos;
	},
	_set_width:function(w){
		this._wrapper.width(w);
	},
	_set_height:function(h){
		this._wrapper.width(h);
	},
	_set_stateClass:function(stateClass){
		this._wrapper.removeClass(this.options.stateClass).addClass(stateClass);
		this._setPointerColor(this._tk.initialized);
	},
	_set_connector:function(c){
		this._pointer.toggle(c);
		this._wrapper.positionRelative('set_space',this.options.spacing+(this.options.connector?16:0))
	},
	_set_msg:function(msg){
		if( msg.toString().length < 1){
			msg = this.elmt.attr('title');
			this.elmt.attr('title','');
		}
		this._msg.html(msg);
	},

	/** correctly set pointer colors */
	_setPointerColor:function(doIt){
		if( ! doIt){ //-- prevent setting colors multiple times at init time
			return false;
		}
		var pos=this._wrapper.positionRelative('return1_realpos'),
			bgColor = this._wrapper.css('backgroundColor');
		this._pointer.attr('style','');
		this._pointerBg.attr('style','');
		switch(pos.v){
			case 'top':
				this._pointer.css('borderTopColor',this._wrapper.css('borderTopColor'));
				this._pointerBg.css('borderTopColor',bgColor);
				break;
			case 'bottom':
				this._pointer.css('borderBottomColor',this._wrapper.css('borderBottomColor'));
				this._pointerBg.css('borderBottomColor',bgColor);
				break;
			case 'middle':
				switch(pos.h){
					case 'left':
						this._pointer.css('borderLeftColor',this._wrapper.css('borderRightColor'));
						this._pointerBg.css('borderLeftColor',bgColor);
						break;
					case 'right':
						this._pointer.css('borderRightColor',this._wrapper.css('borderLeftColor'));
						this._pointerBg.css('borderRightColor',bgColor);
						break;
				}
				break;
		}
	},
	show:function(){
		this._wrapper.show();
		this._wrapper.positionRelative('update');
	},
	hide:function(){
		this._wrapper.hide();
	}

});

$.tk.tooltip.defaults={
	position:'top-center', // one of top[Left|Right]|right[Top|Bottom]|bottom[Left|Right]|left[Top|Bottom]
	stateClass:'tk-state-warning', //  one of warning|error|success|info
	connector:true,
	edgePolicy:'reverse',
	//stickyMouse:true,
	spacing:5,
	width:'auto',
	height:'auto',
	msg:'',
	triggerEvents:['focus|mouseover','blur|mouseout']
}

})(jQuery);