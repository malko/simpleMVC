/**
* @changelog
* - 2010-07-22 - introduce layouts
* - 2010-08-03 - add unique option (not perfect for now but will do the job for now)
*              - trigger a change event on target input elements
*/
(function($){

$.toolkit('tk.keypad',{
	_classNameOptions:{
		randomized:'|random(?:ized)?',
		blockedInput:'|(?:un)?blocked',
		autoShow:'|(no)?auto',
		layout:'|\\S+?'
	},
	_keys:[],
	_target:null,
	_keyWrapper:null,
	_specialWrapper:null,
	_shifted:false,
	_init:function(){
		var self = this;
		self._keys=[];
		//-- prepare wrappers and buttons
		self._keyWrapper = $('<div class="tk-keypad-keyWrapper tk-inlineStack"></div>').appendTo(self.elmt);
		self._specialWrapper = $('<div class="tk-keypad-specialWrapper tk-inlineStack"></div>').appendTo(self.elmt);
		self._addingSpecials();
		self._applyOpts('layout|target|randomized|autoShow|blockedInput');
		self.refresh();
		if( self.options.autoShow){
			self.elmt.hide();
		}
	},
	_addingSpecials:function(){
		var self = this;
		$('<button type="button" class="tk-keypad-specialClose">Fermer</button>')
			.appendTo(self._specialWrapper)
			.click(function(){ self.elmt.slideUp(); });
		$('<button type="button" class="tk-keypad-specialCancel">Effacer</button>')
			.appendTo(self._specialWrapper)
			.click(function(){ self._target.is(':input')?self._target.val(''):self._target.text('') });
		$('<button type="button" class="tk-keypad-specialShift">Shift</button>')
			.appendTo(self._specialWrapper)
			.click(function(){
				self._shifted = ! self._shifted;
				self._keyWrapper.find('button').each(function(){
					var k = $(this), c = k.text();
					if( self.options.layout.shifted !== null && self.options.layout.shifted[c]){
						k.text(self.options.layout.shifted[c]);
					}else if( c.length > 1){
						return;
					}else if( self._shifted){
						k.text(c.toUpperCase());
					}else{
						k.text(c.toLowerCase());
					}
				});
			});
	},
	_set_target:function(t){
		var self = this;
		if( t === undefined || t===null){
			var rel = self.elmt.attr('rel');
			if( rel !== ''){
				t = rel;
			}
		}
		if( self._target ){
			self._target.unbind('keypad');
		}
		self._target = $(t);
		self._target.bind('focus.keypad',function(e){ return self._targetEventCb(e);});
	},
	_set_blockedInput:function(b){
		if( b==='unblocked'){ b=false; }
		else if (b ==="blocked"){ b=true;}
		return b;
	},
	_set_autoShow:function(a){
		if( a ==='noauto'){
			a = false;
		}else{
			a = a?true:false;
		}
		this.elmt.find('.tk-keypad-specialClose').toggle(a);
		return a;
	},
	_targetEventCb:function(event){
		var self = this;
		switch(event.type){
			case 'focus':
				if( self.options.autoShow){
					if( self.options.unique){
						$('.tk-keypad').not(self.elmt).stop(true,true).hide(0);
					}
					self.elmt.slideDown();
				}
				if( self.options.blockedInput){
					//- self._target.blur();
					event.preventDefault();
					self.elmt.find('.'+self.options.buttonClass+':eq(0)').focus().blur();
				}
				break;
		}
	},
	_set_randomized:function(r){
		this.options.randomized = ( r === 'random' || r==='randomized')?true:(r?true:false);
		if( this._tk.initialized){
			this.refresh();
		}
		return this.options.randomized;
	},
	_set_layout:function(layout){
		if( typeof layout === 'string' && $.tk.keypad.layouts[layout] ){
			layout = $.tk.keypad.layouts[layout];
		}
		//--prepare doubling shifted values
		if( this.options.layout.shifted!==null){
			for( var c in layout.shifted ){
				layout.shifted[layout.shifted[c]] = c;
			}
		}
		var c='',i;
		for(i=0,l=layout.layout.length; i<l; i++){
			c = layout.layout.charAt(i);
			switch(c){
				case ' ':
					this._keys.push('&nbsp;');
					break;
				default:
					this._keys.push(c);
			}

		}
		if( this._tk.initialized){
			this.refresh();
		}
		return layout;
	},
	refresh:function(){
		var self = this,
			keys = self._keys,
			i;
		if( self.options.randomized){
			keys.sort(function(){ return 0.5 - Math.random()});
		}
		self._keyWrapper.empty();
		for(i=0,l=keys.length;i<l;i++){
			$('<button type="button" class="'+self.options.buttonClass+'">'+keys[i]+'</button>')
				.appendTo(self._keyWrapper)
				.click(function(e){ return self.clickCb($(this).text(),e) });
		}
		self._specialWrapper.find('.tk-keypad-specialShift').toggle( self.options.layout.shifted !== null)
	},
	clickCb: function(key,e){
		if( false === this._trigger('_keyclick', e, [key,this._target]) ){
			return false;
		}
		if( this._target && this._target.length){
			if( this._target.is(':input') ){
				var event = $.event.fix(e||{});
				event.type = 'change';
				this._target.val(this._target.val()+key).trigger(event);
			}
		}else{
			this._target.append(key);
		}
		this._trigger('_keyclicked', e, [key,this._target]);
	}
});
$.tk.keypad.defaults={
	//- keys:["all","_-"]
	layout:'qwerty'
	,target:null
	,randomized:false
	,blockedInput:true
	,autoShow:true
	,unique:true // if true will hide others keypad before auto-showing itself
	,buttonClass:'key'
};
$.tk.keypad.layouts={
	numeric:{name:'numeric',layout:'0123456789+-*/.',shifted:null},
	numbers:{name:'numbers',layout:'0123456789',shifted:null},
	qwerty:{
		name:'qwerty',
		layout:'`1234567890-=qwertyuiop[]\\asdfghjkl;\'zxcvbnm,./',
		shifted:{
			'`':'~', 1:'!', 2:'@', 3:'#', 4:'$', 5:'%', 6:'^', 7:'&', 8:'*', 9:'(', 0:')', '-':'_',
			'=':'+', '[':'{', ']':'}', '\\':'|', ';':':', "'":'"', ',':'<', '.':'>', '/':'?'
		}
	},
	azerty:{
		name:'azerty',
		layout:'²&é"\'(-è_çà)=azertyuiop$qsdfghjklmù*<wxcvbn,;:!',
		shifted:{
			'&':'1', 'é':'2', '"':'3', "'":'4', '(':'5', '-':'6', 'è':'7', '_':'8', 'ç':'9', 'à':'0',
			')':'°', '=':'+', '$':'£', 'ù':'%', '*':'µ', '<':'>', ',':'?', ';':'.', ':':'/', '!':'§'
		}
	}
};

})(jQuery);