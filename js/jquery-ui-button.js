/**
* jQuery UI Labs - buttons
* - for experimental use only -
* Copyleft (l) 2009 Jonathan gotti aka malko < jgotti at jgotti dot org >
* Dual licensed under the MIT and GPL licenses.
* http://docs.jquery.com/License
* Depends:
*		ui.core.js
*/
(function($){

	// handle some common methods for all derivative plugins
	var uiButtonCommon = {
		/*
		* read extra options settings in widget.element's class attribute and return them as object
		* baseClass is the extended class (ui-button),
		* optionsList an associtive list of optionNames with their possible values separated by a pipe '|'
		* if an empty value is set at first position it'll be considered optional.
		*/
		_readClassNameOpts: function(baseClass,optionsList,elmt){
			elmt=(!elmt)?this.element:$(elmt);
			//prepare expression
			var exp = '(?:^|\\s)'+baseClass+'(?=-)';
			var opts={}, optName;
			var classAttr = elmt.attr('class');
			if(null===classAttr || classAttr.length <1)
				return opts;
			for(optName in optionsList ){
				exp += ( optionsList[optName].substr(0,1)=='|' )?'(?:-('+optionsList[optName].substr(1)+'))?':'-('+optionsList[optName]+')';
			}
			exp = new RegExp(exp+'(?:$|\\s)');
			var matches = classAttr.match(exp);
			if( null==matches)
				return opts;
			//prepare options objects from matches
			var id=1;
			for(optName in optionsList){
				if(matches[id]){
					opts[optName] = matches[id];
				}
				id++;
			}
			return opts;
		},
		// add options settings only if current option setting is different from default option value else just ignore it.
		_mergeOpts: function(opts){
			var defaults = $[this.namespace][this.widgetName].defaults;
			for( var optName in opts){
				if( defaults[optName] === this.options[optName] ){
					this.options[optName] = opts[optName];
				}
			}
			return this;
		},
		// effectively apply settings by calling _setData on given options names.
		// additional parameter ifNotDefault will only apply settings if different from default.
		_applyOpts: function(names,ifNotDefault){
			if(! ifNotDefault){
				for(var i=0;i<names.length;i++){
					this._setData(names[i],this.options[names[i]]);
				}
				return this;
			}
			var defaults = $[this.namespace][this.widgetName].defaults;
			for(var i=0;i<names.length;i++){
				if( defaults[names[i]] !== this.options[names[i]] ){
					this._setData(names[i],this.options[names[i]]);
				}
			}
			return this;
		},
		/**
		* remove matching class names from element and eventually add new class on given element (default to widget.element)
		*/
		_rmExpClass:function(exp,add,elmt){
			elmt=(!elmt)?this.element:$(elmt);
			eval('exp = /(?:^|\\s)'+exp.replace(/\*/g,'[a-zA-Z_0-9-]*')+'(?=$|\\s)/g;');
			elmt.attr('class',elmt.attr('class').replace(exp,''));
			if( undefined!==add ){
				elmt.addClass(add);
			}
			return this;
		}
	}

	// base ui-button plugin
	$.widget("ui.button",$.extend({},uiButtonCommon,{

		elmt_icon:null,
		elmt_iconContainer:null,
		elmt_label:null,
		iconIsImage:false,
		iconBeforeLabel:true,
		_isToggleCB:null,
		_buttonset:null,
		_cornersValue:'',
		_orientationValue:'',
		_sizeValue:'',
		_init:function(){
			var self = this;
			//-- should think about aborting or not init when ui-button-none, ui-buttonset are used.
			if( this.element.attr('class').match(/(?:^|\s+)ui-button(set|-none(\s|$))/) ){
				return $.widget.prototype.destroy.apply(this, arguments);
			}

			// read inline options from class attribute (that can't be null!!!)
			if( this.element.is('[class*=ui-button-]')){
				var inlineOptions=self._readClassNameOpts('ui-button',{size:'|auto|tiny|small|normal|big|huge',orientation:'|auto|[trbli]',icon:'|[a-zA-Z0-9_-]+'})
				self._mergeOpts(inlineOptions);
			}

			self.element.addClass('ui-button ui-widget ui-state-default')
				.hover(self._hover,self._blur);

			// preapre wrapers elements
			self._wrapLabel();
			self._wrapIcon();

			// detect some toggle markup options
			if( self.element.hasClass('toggle') ){
				self.options.isToggle = true;
			}
			if( self.element.hasClass('active') || self.element.hasClass('ui-state-active')){
				self.options.active = true;
			}

			// apply some settings
			self._applyOpts(['size','orientation','icon','corners'])
				._applyOpts(['toggle','active','label','isToggle'],true);

			if(! $.support.style){
				this.element.addClass('ui-button-inlineBlockFix');
				this.elmt_iconContainer.css({margin:0});
				if(null !== this.elmt_icon){
					this.elmt_icon.css({margin:0});
				}
			}
			// auto initialisation of button set on last buttonset element
			if( self.options.checkButtonset){
				var buttonset = self.element.parent('[class*=ui-buttonset]');
				if( buttonset.length > 0){
						self._buttonset = buttonset;
						if( this.element.is(':last-child')){
							buttonset.buttonset();
						}
				}
			}
			return this;
		},
		_hover: function(){
			$(this).addClass('ui-state-hover');
		},
		_blur: function(){
			$(this).removeClass('ui-state-hover');
		},
		_setIcon:function(){
			var ico = this._getData('icon');
			this.iconIsImage =( ico.match(/\.(jpe?g|png|gif|ico)$/i) )?true:false;
			if(null !== this.elmt_icon){
				this.elmt_icon.remove();
			}
			if( '' === ico || null === ico){
				this.elmt_icon = null;
				this.elmt_iconContainer.hide();
				ico='none';
			}
			if( this.iconIsImage){
				this.elmt_icon=$('<img src="'+escape(ico)+'"  />');
			}else{
				this.elmt_icon=$('<span class="ui-icon ui-icon-'+ico+'"></span>');
			}
			if(this.elmt_icon.length && ! $.support.style){
				this.elmt_icon.css({margin:0});
			}
			this.elmt_iconContainer.append(this.elmt_icon);
			this.elmt_iconContainer.show();
		},

		_wrapLabel:function(){
			if( null!==this.elmt_label ){
				return;
			}
			var elmt_label=$('<span class="ui-button-label"></span>');
			if( this.element.html().replace(/\s/,'').length > 0){
				this.element.wrapInner(elmt_label);
			}else{
				this.element.append(elmt_label.append('&nbsp').addClass('ui-button-label-empty'));
			}
			this.elmt_label = this.element.find('>.ui-button-label');
		},

		_wrapIcon:function(){
			if( null!==this.elmt_iconContainer){
				return;
			}
			this.elmt_iconContainer=$('<span class="ui-button-icon"></span>');
			this.element.append(this.elmt_iconContainer);
		},
		_checkElmtPos: function(){
			var actual = this.element.find('span:first').is('.ui-button-icon')?true:false;
			if( actual==this.iconBeforeLabel)
				return this;
			if( this.iconBeforeLabel){
				this.element.prepend(this.elmt_iconContainer);
			}else{
				this.element.append(this.elmt_iconContainer);
			}
			return this;
		},
		_setData:function(key,value){
			var self = this;
			switch(key){
				case 'icon':
					var res = $.widget.prototype._setData.apply(self, arguments);
					this._setIcon();
					return res;
					break;
				case 'corners':
					self._cornersValue = value=='auto'?'all':value;
					self._rmExpClass(self.element,'ui-corner-*','ui-corner-'+self._cornersValue);
					break;
				case 'label':
					if( null!==value){
						if( ''===value){
							self.elmt_label.addClass('ui-button-label-empty').html('&nbsp;');
						}else{
							self.elmt_label.removeClass('ui-button-label-empty')
								.empty().append(value);
						}
					}
					break;
				case 'orientation':
					if( value=='')
						value = 'auto';
					self._orientationValue = (value=='auto'||value=='i')?'l':value;
					if( value==='i'){
						self._setData('label','');
					}
					self._rmExpClass(self.element,'ui-button-orientation-*','ui-button-orientation-'+self._orientationValue);
					self.iconBeforeLabel=( self._orientationValue=='b' || self._orientationValue=='r')?false:true;
					self._checkElmtPos();
					break;
				case 'size':
					self._sizeValue = value=='auto'?'normal':value;
					self._rmExpClass(self.element,'ui-button-size-*','ui-button-size-'+self._sizeValue);
					break;
				case 'isToggle':
					if(! self.isToggleCB){
						self._isToggleCB = function(event){return self.toggle(event);};
					}
					if( value){
						self.element.bind('click',self._isToggleCB);
					}else{
						self.element.unbind('click',self._isToggleCB);
					}
					break;
				case 'active':
					if(! self._getData('isToggle'))
						return false;
					self.element.toggleClass('ui-state-active active',value?true:false);
					self._trigger('setactive',0,self);
					break;
			}
			return $.widget.prototype._setData.apply(this, arguments);
		},
		isActive:function(){
			return this._getData('active');
		},
		importButtonSetSettings:function(buttonSet){
			var self=this;
			var buttonSetSize = buttonSet._getData('size');
			var buttonSetOrientation = buttonSet._getData('orientation');
			if( self._getData('size')==='auto' && buttonSetSize !== 'auto'){
				self._setData('size',buttonSetSize);
				self.options.size='auto';
			}
			if( self._getData('orientation')==='auto' && buttonSetOrientation !== 'auto'){
				self._setData('orientation',buttonSetOrientation);
				self.options.orientation='auto';
			}

			if( 'auto' == self._getData('corners')){
				var isOnlyChild = self.element.is(':only-child');
				if( self.element.is(':first-child') && ! isOnlyChild ){
					self._setData('corners','left');
				}else if(self.element.is(':last-child') && ! isOnlyChild ){
					self._setData('corners','right');
				}else{
					self._setData('corners',isOnlyChild?'all':'none');
				}
				self.options.corners='auto';
			}

		},
		_rmExpClass:function(elmt,exp,add){
			eval('exp = /(?:^|\\s)'+exp.replace(/\*/g,'[a-zA-Z_0-9-]*')+'(?=$|\\s)/g;');
			elmt.attr('class',elmt.attr('class').replace(exp,''));
			if( undefined!==add ){
				elmt.addClass(add);
			}
		},
		//** callbacks **//
		toggle: function(event){
			this._setData('active',this._getData('active')?false:true);
			this._trigger('toggle',event,this);
			return this;
		}

	}));

	$.extend($.ui.button, {
		version: "@VERSION",
		getter:'isActive',
		defaults:{
			size:'auto',
			orientation:'auto',
			corners:'auto',
			icon:'',
			label:null,
			isToggle:false,
			toggle:false,
			active:false,
			checkButtonset:false
		}
	});//*/

	$.widget('ui.buttonset',$.extend({},uiButtonCommon,{
		_orientationValue:'',
		_sizeValue:'',
		_initiated:false,
		_init:function(){
			var self=this;
			// read inline options
			var inlineOptions=self._readClassNameOpts('ui-buttonset',{size:'|auto|tiny|small|normal|big|huge',orientation:'|auto|[trbli]'})
			self._mergeOpts(inlineOptions);

			self.element.addClass('ui-buttonset ui-widget'+(self.element.is('[class*=ui-corner]')?'':' ui-corner-all'));

			if( !$.support.style){
				self.element.addClass('ui-button-inlineBlockFix');
			}
			self._applyOpts(['size','orientation'])
			self._initiated = true;
			self.propagateSettings();

		},
		// propagate settings to child nodes
		propagateSettings:function(){
			var self=this;
			self.element.contents().each(function(){
				if( this.nodeType!=1 || ! this.tagName){
					return $(this).remove();
				}
				$(this).button().button('importButtonSetSettings',self);
			})
		},
		_setData:function(key,value){
			var self = this;
			switch(key){
				case 'orientation':
					self._orientationValue =  value=='auto'?'w':value;
					if( self._initiated){
						self.propagateSettings();
					}
					break;
				case 'size':
					self._sizeValue = value=='auto'?'normal':value;
					if( self._initiated){
						self.propagate();
					}
					break;
			}
			return $.widget.prototype._setData.apply(this, arguments);
		}

	}));

	$.extend($.ui.buttonset,{
		version: "@VERSION",
		defaults:{
			size:'auto',
			orientation:'auto'
		}

	});

	/*
	if( this._getData('buttonsetRemoveSpace')){
		buttonSet.contents().each(function(){
			if(! this.tagName)
				$(this).remove();
		});
	}*/

		$.widget("ui.selectbuttonset",$.extend({},uiButtonCommon,{
			multiple:false,
			buttonset:null,
			_init:function(){
				var self=this;
				// read inline options
				var inlineOptions=self._readClassNameOpts('ui-buttonset',{size:'|auto|tiny|small|normal|big|huge',orientation:'|auto|[trbli]'})
				self._mergeOpts(inlineOptions);

				if( self.element.attr('multiple') ){
					self.multiple = true;
				}
				self.buttonset = $('<div class="ui-buttonset"></div>');
				self.buttonset.buttonset(self.options);
				self.element.hide();
				self.element.after(self.buttonset);
				self.refresh();
			},
			refresh:function(){
				var self = this;
				var orientation = self._getData('orientation');
				var size = self._getData('size');
				var multiple = self.multiple;
				self.element.children('option').each(function(i){
					var option = $(this);
					var label = option.html();
					var optionIcon = option.attr('class').match(/(?:^|\s)ui-icon-(.+)(?:$|\s)/);
					if(null !== optionIcon)
						optionIcon = optionIcon[1];
					$('<button type="button" class="ui-button-'+size+('auto'===orientation?'':'-'+orientation)+(optionIcon?'-'+optionIcon:'')+' toggle'+(option.is(':selected')?' active':'')+'">'+label+'</button>')
						.appendTo(self.buttonset)
						.button({
							'corners':(i==0?'left':(i+1<self.element.attr('options').length?'none':'right')),
							toggle:function(e,ui){
								self._toggle(e,ui,option);
							}
						});
				});
			},
			_toggle:function(event,button,option){
				var self = this;
				option.attr('selected',button.isActive()?'selected':'');
				if(! self.multiple){
					self.buttonset.find('.ui-button').not(button.element).button('option','active',false);
				}
				self.element.change();
				self._trigger('toggle',event,[self.element,button.element]);
			}
		}));
		$.extend($.ui.selectbuttonset, {
			version: "@VERSION",
			defaults:{
				size:'normal',
				orientation:'auto'
			}
		});
})(jQuery);
