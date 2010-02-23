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
	$.toolkit('tk.validable',{
		_requiredElmt:null,
		_stateIconElmt:null,
		_labelElmt:null,
		_init:function(){
			var self = this,
				id = self.elmt.attr('id'),
				getStateCB=function(e){return self.getState(e)};
			if( self.elmt.is('form')){
				var dfltInputOptions={
					stateElmt:self.options.stateElmt,
					useIcon:self.options.useIcon
				};
				self.elmt.find(':input').each(function(){
					var input=$(this);
					var iname = input.attr('name');
					if( self.options.rules[iname]){
						input.validable($.extend({},self.dfltInputOptions,self.options.rules[iname]));
					}
				});
				self.elmt.bind('submit.validable',getStateCB);
			}else{
				self._applyOpts('labelElmt|rule|required|useIcon|help|helpTrigger|helpAfter');
				//- check trigger
				self.elmt.bind('change.validable',getStateCB);
				self.elmt.bind('keyup.validable',getStateCB);
			}
			if(self.options.initCheck){
				self.getState();
			}
		},
		_set_labelElmt:function(label){
			var self = this;
			if( label ){
				self._labelElmt = $(label);
			}else{
				label =$('label[for='+self.elmt.attr('id')+']');
				if(! label.length){
					label = self._elmt.parents('label');
				}
				self._labelElmt = label.length?label:null;
			}
			return label;
		},
		_set_rule:function(rule){
			var self = this;
			if( typeof rule === 'string' ){
				var isRegExpString = rule.match(/^\/(.+)\/([mgi]{0,3})?$/);
				if(  isRegExpString ){
					rule = isRegExpString[2]? new RegExp(isRegExpString[1],isRegExpString[2]) : new RegExp(isRegExpString[1]);
				}else if( $.tk.validable.defaultRules[rule] ){
					rule = $.tk.validable.defaultRules[rule];
				}else{ //-- consider this as a callback function name
					try{
						eval('rule ='+rule+';' );
					}catch(e){
						throw(rule+' is not a valid validable rule.')
					}
				}
			}
			return rule;
		},
		_set_maxlength:function(l){ this.elmt.attr('maxlength',l?l:''); },
		_set_minlength:function(l){ this.elmt.attr('minlength',l?l:''); },
		_set_required:function(required){
			var self = this;
			required = required?true:false; // ensure boolean value
			if(! required){
				if(null!==self._requiredElmt){
					self._requiredElmt.remove();
				}
				self._requiredElmt=null;
			}else{
				if(null===self._requiredElmt){
					self._requiredElmt = $(self.options.requiredTemplate);
				}
				if( self._labelElmt){
					self._labelElmt.prepend(self._requiredElmt);
				}else{
					self.elmt.after(self._requiredElmt);
				}
			}
			return required;
		},
		_set_useIcon:function(useIcon){ //@todo manage "auto" value
			if(! useIcon){
				if( this._stateIconElmt !== null){
					this._stateIconElmt.remove();
					this._stateIconElmt=null;
				}
			}else{
				if( this._stateIconElmt === null){
					this._stateIconElmt=$('<span class="tk-validable-state-icon"><span class="ui-icon"></span></span>');
					this._applyOpts('helpAfter');
				}
			}
		},
		_set_help:function(msg){
			if(! msg.length){
				return '';
			}
			var o = $.extend({},this.options.helpOptions,{msg:msg});
			this.elmt.tooltip('set',o);
			return msg;
		},
		_set_helpTrigger:function(trigger){
			if( trigger === null || this.options.help.length<1){
				$(trigger).each(function(){ $(this).unbind('.validable'); });
				return null;
			}
			var self = this;
			$(trigger).each(function(){
				if( this.tagName==='IFRAME' && this.contentDocument){ // @todo check for aleternate method for IE
					//- this.contentDocument.addEventListener('focus',function(){self.getState();self.elmt.tooltip('show');},false);
					//- this.contentDocument.addEventListener('blur',function(){self.getState();self.elmt.tooltip('hide');},false);
					$(this.contentDocument).bind('focus.validable mouseover.validable',function(){self.getState();self.elmt.tooltip('show');});
					$(this.contentDocument).bind('blur.validable mouseout.validable',function(){self.getState();self.elmt.tooltip('hide');});
				}else{
					$(this).bind('focus.validable mouseover.validable',function(){self.getState();self.elmt.tooltip('show');})
						.bind('blur.validable mouseout.validable',function(){self.getState();self.elmt.tooltip('hide');});
					//- $(this).focus(function(){self.check();self.elmt.tooltip('show');})
						//- .blur(function(){self.check();self.elmt.tooltip('hide');});
				}
			});
			return trigger;
		},
		_set_helpAfter:function(elmt){
			if( null===elmt){
				elmt = this.elmt;
			}
			if( this.options.help.length){
				this.elmt.tooltip('get1_pluginInstance')._wrapper.positionRelative('set_related',elmt);
			}
			if( ! this._stateIconElmt ){
				return;
			}
			this._stateIconElmt.insertAfter(elmt);
		},
		_setState:function(state){
			var stateElmt = this.elmt;
			if( this.options.stateElmt==='label' && this._labelElmt ){
				stateElmt = this._labelElmt;
			}else if( this.stateElmt !== 'self'){
				stateElmt = $(this.options.stateElmt);
				if( ! stateElmt.length){
					stateElmt=this.elmt;
				}
			}
			if(state){
				stateElmt.removeClass('tk-state-error')
					.addClass('tk-state-success');
				if( this._stateIconElmt){
					this._stateIconElmt.removeClass('tk-state-error ui-state-error').addClass('tk-state-success ui-state-success')
					.find('.ui-icon').removeClass('ui-icon-cancel').addClass('ui-icon-check');
				}
				if( this.options.help.length>0){
					this.elmt.tooltip('set_stateClass','tk-state-success');
				}
			}else{
				stateElmt.removeClass('tk-state-success')
					.addClass('tk-state-error');
				if( this._stateIconElmt){
					this._stateIconElmt.removeClass('tk-state-success ui-state-success').addClass('tk-state-error ui-state-error')
					.find('.ui-icon').removeClass('ui-icon-check').addClass('ui-icon-cancel');
				}
				if( this.options.help.length>0){
					this.elmt.tooltip('set','stateClass','tk-state-error');
				}
			}
			this._stateIconElmt.toggle((this.options.rule || this.options.required || this.options.minlength || this.options.maxlength)?true:false)
			return state?true:false;
		},

		getState: function(event){
			var self = this;
			if(! self.elmt.is(':input')){
				if(! self.elmt.is('form'))
					return false;
				var res = true,state;
				self.elmt.find(':input.tk-validable').each(function(){
					res = (res && $(this).validable('return1_getState'))?true:false;
				});
				if( event && false===res && $('.tk-notifybox').length && $.toolkit && $.tk.notifybox){ //@todo migrer le plugin vers jquery.toolkit et ici mettre un trigger sur un custom event
					$('.tk-notifybox').notifybox('notify','<div class="tk-state-error" style="border:none;">Les données du formulaire ne sont pas valide, merci de vérifier votre saisie.</div>');
				}
				return res;
			}else{
				if( event && event.type === 'keyup' && event.which == 27	)
					self.elmt.tooltip('hide');
			}
			var val   = self.elmt.val();
			var maxlength = Math.max(0,self.elmt.attr('maxlength'));
			var minlength = Math.max(0,self.elmt.attr('minlength'));
			var length= val.length;

			if( (! val) && ! self.options.required){
				return self._setState(true);
			}
			if( maxlength && length > maxlength ){
				return self._setState(false);
			}
			if( minlength && length < minlength ){
				return self._setState(false);
			}
			if( self.options.rule instanceof RegExp){
				var m = val.match(self.options.rule);
				return self._setState(m===null?false:true);
			}else if(self.options.rule){ //	if( typeof self.options.rule === 'function'){
				try{
					var res = self.options.rule.call(self.elmt.get(0),val);
				}catch(e){
					throw(self.options.rule +' is not a valid validable rule.'+e);
				}
				if(! res )
					return self._setState(false);
			}
			if( self.options.required && val.length < 1 )
				return self._setState(false);
			return self._setState(true);
		}
	});

	$.tk.validable.defaults={
		rule: null,
		initCheck:true,
		required:false,
		stateElmt:'self', //-- may be self, label or any valid selector
		useIcon:'auto',
		labelElmt:null,
		requiredTemplate:'<span class="tk-validable-required"> * </span>',
		help:'',
		helpTrigger:null,
		helpAfter:null,
		helpOptions:{
			position:'middle-right'
		}
	};

	$.tk.validable.defaultRules={
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

})(jQuery);