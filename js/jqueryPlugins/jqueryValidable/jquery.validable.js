/**
Simple client side Help/Validation for forms

each input can use an 'inputValidableOptions' that look like this:
inputValidableOptions = {
	rule: /regexp/,       // optional valid RegExp or callback function
	help: 'help message', // optionnal the tooltip message to display when input is focused
	minlength: 5,         // optional minimal length
	maxlength: 25,        // optional maximal length if not passed may be detected in input attribute if set
	required:true,        // optional make the input must have a non empty value to pass validation.
	helpTrigger:'jquerySelector',// allow to attach focus/blur event to display the help message to other elements
	helpAfter:'jquerySelector' // allow to move help and icon display after another element than the original one.
};
and may be added at any time to the script by doing
$('#inputId').validable(inputValidableOptions);

you may declare all validations rules at once by making the parent form validable (u must make it validable anyway if you want to check before submit)
here the options for validable form declaration:
formValidableOptions = {
	rules:{
		inputName: inputValidableOptions,
		inputName1: inputValidableOptions1,
		...
	}
};
$('#myForm').validable(formValidableOptions);
@changelog
           - 2009-10-22 - add options helpTrigger and helpAfter
           - 2009-10-20 - add support for string callbacks
*/
(function($) {
	function show(){
		if( typeof console)
			console.debug(arguments);
	}

	$.fn.validable = function(options){
		return this.each(function(){
			var elmt = $(this);
			if( elmt.is('form')){
				validableFORM(elmt,options);
			}else if(elmt.is(':input')){
				validableINPUT(elmt,options);
			}
		});
	}

	function validableINPUT(elmt,options){
		this instanceof validableINPUT ? this.init(elmt,options): new validableINPUT(elmt,options);
	}
	function validableFORM(elmt,options){
		this instanceof validableFORM ? this.init(elmt,options): new validableFORM(elmt,options);
	}
	$.fn.validable.defaultRules={
		email:/^[^@\s]+@[^@\s]+\.[a-z]{2,5}$/i,
		'int':/^\d+$/,
		'float':/^\d+((\.|,)\d+)?$/,
		zipcode:/^\d{2,5}$/,
		phone:/^\d{10}$/,
		alpha:/^[a-z_\s-]+$/i,
		Alpha:/^[a-z]+$/i,
		alphanum:/^[0-9a-z_\s-]+$/i,
		Alphanum:/^[0-9a-z]+$/i,
		img:/\.(jpe?g|gif|png)$/i,
		video:/\.(mpe?g|avi|flv|mov)$/i,
		flashEmbeddable:/\.(jpe?g|flv|gif|png|swf|mp3)$/i
	};

	var abstractValidable = {
		_elmt:null,
		_id:'',
		__uniqueID:0,
		_checkInstance: function(elmt){
			// check this is not a living instance
			if(this._elmt)
				return this;
			_elmt = $(elmt);
			var self = _elmt.data('validable');
			if( ! self){
				_elmt.data('validable',this).addClass('validable');
				this._elmt = _elmt;
				self = this;
				self._id = _elmt.attr('id');
				if(! self._id.length){
					self._id = 'validable-'+(++abstractValidable.__uniqueID)
					self._elmt.attr('id',self._id);
				}
			}
			return self;
		},

		check: function(event){
			if( this._elmt){
				var self = this
			}else{
				var self = $(this).data('validable');
				if( ! self)
					return false;
			}
			if(! self._elmt.is(':input')){
				if(! self._elmt.is(':form'))
					return false;
				var res = true;
				self._elmt.find(':input.validable').each(function(){
					var _res = $(this).data('validable').check();
					res = res && _res?true:false;
				});
				if( event && false===res && $('.tk-notifybox').length && $.toolkit && $.tk.notifybox){ //@todo migrer le plugin vers jquery.toolkit et ici mettre un trigger sur un custom event
					$('.tk-notifybox').notifybox('notify','<div class="ui-state-error" style="border:none;">Les données du formulaire ne sont pas valide, merci de vérifier votre saisie.</div>');
				}
				return res;
			}else{
				if( event && event.type === 'keyup' && event.which == 27	)
					self._msgHelpElmt.hide();

			}
			var val   = self._elmt.val();
			var maxlength = Math.max(0,self._elmt.attr('maxlength'));
			var minlength = Math.max(0,self._elmt.attr('minlength'));
			var length= val.length;

			if( (! val) && ! self.required)
				return self.setState(true);
			if( maxlength && length > maxlength )
				return self.setState(false);
			if( minlength && length < minlength )
				return self.setState(false);
			if( self.rule instanceof RegExp){
				var m = val.match(self.rule);
				return self.setState(m===null?false:true);
			}else if(self.rule){ //	if( typeof self.rule === 'function'){
				try{
					var res = self.rule.call(self._elmt.get(0),val);
				}catch(e){
					throw(self.rule +'is not a valid validable rule.'+e);
				}
				if(! res )
					return self.setState(false);
			}
			if( self.required && ! val )
				return self.setState(false);
			return self.setState(true);
		}
	}
	//-- validable inputs
	$.extend(validableINPUT.prototype,abstractValidable,{
		rule:null,
		msgHelp:'',
		stateElmt: 'self', //-- may be self, label or any valid selector
		useIcon:true, //-- may be true, false, auto (will display an icon only on required or if any rules is defined (min/max length count as rule))
		_msgHelpElmt:null,
		_id:'',
		_labelElmt:null,
		init:function(elmt,options){
			var self = this._checkInstance(elmt);
			if( typeof options === 'string'){
				switch(options){
					case 'check':
						return self.check();
					case 'showHelp':
						return self.showHelp();
					case 'hideHelp':
						return self.hideHelp();
				}
			}

			var label = $('label[for='+self._id+']');
			if(! label.length)
				label = self._elmt.parent('label');
			if( label.length)
				self._labelElmt = label;

			if( options.rule ){
				if( typeof options.rule === 'string' ){
					var isRegExpString = options.rule.match(/^\/(.+)\/([mgi]{0,3})?$/);
					if(  isRegExpString ){
						options.rule = isRegExpString[2]? new RegExp(isRegExpString[1],isRegExpString[2]) : new RegExp(isRegExpString[1]);
					}else if( $.fn.validable.defaultRules[options.rule] ){
						options.rule = $.fn.validable.defaultRules[options.rule];
					}else{ //-- consider this as a callback function name
						try{
							eval('options.rule ='+options.rule+';' );
						}catch(e){
							throw(options.rule+' is not a valid validable rule.')
						}
					}
				}
				self.rule = options.rule;
			}
			if( options.stateElmt)
				self.stateElmt = options.stateElmt;
			if( options.maxlength)
				self._elmt.attr('maxlength',options.maxlength);
			if( options.minlength)
				self._elmt.attr('minlength',options.minlength);

			//- check trigger
			self._elmt.change(self.check);
			self._elmt.keyup(self.check);

			if( options.required || self._elmt.is('.required')){
				self.required = true;
				var tmpStr = '<span class="validable-required"> * </span>';
				if( self._labelElmt)
					self._labelElmt.prepend(tmpStr);
				else
					self._elmt.after(tmpStr);
			}
			if( typeof options.useIcon !== 'undefined' )
				this.useIcon = options.useIcon;
			if( options.help)
				self.msgHelp = options.help;
			//- append help msg
			if( options.help || this.useIcon!==false){
				var tmpStr = '<div class="validable-helpWrapper" style="border:none!important">'+
					'<div class="ui-state-highlight ui-corner-all validable-helpTip ">'+
						'<div class="validable-helpMsg">'+(self.msgHelp?self.msgHelp:'&nbsp;')+'</div>'+
						'<div class="ui-state-highlight validable-corner"><div class="validable-cornerBg"><div></div></div></div>'+
					'</div>'+(this.useIcon!==false?'<span class="state-icon"></span>':'')+'</div>';
				self._msgHelpElmt = $(tmpStr).insertAfter(options.helpAfter?options.helpAfter:self._elmt).find('.validable-helpTip');
				self._msgHelpElmt.find('.validable-cornerBg').css('border-right-color',self._msgHelpElmt.css('background-color'))
				self._elmt.focus(function(){self.check();self.showHelp();});
				self._elmt.blur(function(){self.check();self.hideHelp();});
				if(options.helpTrigger){
					$(options.helpTrigger).each(function(){
						if( this.tagName==='IFRAME' && this.contentDocument){
							this.contentDocument.addEventListener('focus',function(){self.check();self.showHelp();},false);
							this.contentDocument.addEventListener('blur',function(){self.check();self.hideHelp();},false);
						}else{
							$(this).focus(function(){self.check();self.showHelp();})
								.blur(function(){self.check();self.hideHelp();});
						}
					});
				}
			}
			return self._elmt;
		},

		showHelp:function(){
			if( this.msgHelp)
				this._msgHelpElmt.fadeIn(25);
		},
		hideHelp:function(){
			if( this.msgHelp)
				this._msgHelpElmt.fadeOut(25);
		},

		setState:function(state){
			var stateElmt = this._elmt
			if( this.stateElmt==='label' ){
				if( this._labelElmt)
					stateElmt = this._labelElmt;
			}else if( this.stateElmt !== 'self'){
				stateElmt = $(this.stateElmt);
				if( ! stateElmt.length)
					stateElmt=this._elmt;
			}
			var stateFlag = this._msgHelpElmt.next('.state-icon');
			if(state){
				stateElmt.removeClass('ui-state-error')
					.addClass('ui-state-success');
				if( this._msgHelpElmt || this.useIcon)
					stateFlag
						.removeClass('ui-icon-cancel')
						.addClass('ui-icon ui-icon-check')
						.parent('.validable-helpWrapper').removeClass('ui-state-error').addClass('ui-state-success');
			}else{
				stateElmt.removeClass('ui-state-success')
					.addClass('ui-state-error');
				if( this._msgHelpElmt || this.useIcon){
					stateFlag
						.removeClass('ui-icon-check')
						.addClass('ui-icon ui-icon-cancel')
						.parent('.validable-helpWrapper').removeClass('ui-state-success').addClass('ui-state-error');
				}
			}
			stateFlag.toggle((this.rule || this.required || this.minlength || this.maxlength)?true:false)
			return state;
		}
	});

	//-- validable forms
	$.extend(validableFORM.prototype,abstractValidable,{
		rules:{},
		dfltInputOptions:{
			stateElmt:'elmt',
			useIcon:'auto'
		},
		init:function(elmt,options){
			var self = this._checkInstance(elmt);
			if( options === 'check'){
				return self.check();
			}
			options = $.extend({},options);
			if( options.stateElmt )
				self.dfltInputOptions.stateElmt = options.stateElmt;
			if( options.useIcon )
				self.dfltInputOptions.useIcon   = options.useIcon;
			self.rules = $.extend({},options.rules);

			self._elmt.find(':input').each(function(){
				var input=$(this);
				var iname = input.attr('name');
				if( self.rules[iname]){
					input.validable($.extend({},self.dfltInputOptions,options.rules[iname]));
				}
			});
			self._elmt.submit(self.check);
		}
	});

})(jQuery);