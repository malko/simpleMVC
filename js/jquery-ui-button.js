(function($){
	$.widget("ui.button",{

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
			var id = self.element.attr('id');
			if( this.element.parent('.ui-button').length || this.element.hasClass('ui-button-none') )
				return null
			if( ! id){
				self.element.attr('id','ui-button'+$('[class*=ui-button]').length);
			}
			// read inline options from class attribute (that can't be null!!!)
			var inlineOptions = self.element.attr('class');
			if( undefined === inlineOptions || null === inlineOptions ){
				inlineOptions = ['ui-button','','',''];
			}else{
				_inlineOptions = inlineOptions.match(/(?:^|\s+)ui-button(?:-(tiny|normal|small|big|huge))?(?:-([iewsn](?=$|\s|-)))?(?:-([\w0-9_-]+))?(?:$|\s+)/);
				if( null !== _inlineOptions){
					inlineOptions = _inlineOptions;
				}else{
					if( this.element.attr('class').match(/(?:^|\s+)ui-buttonset/) )
						return null;
					else
						inlineOptions = ['ui-button','','',''];
				}
			}

			self.element.addClass('ui-widget-content ui-state-default ui-button')
				.hover(self._hover,self._blur);


			// preapre wrapers elements
			self._wrapLabel();
			self._wrapIcon();

			if( 'auto' == self.options.size && inlineOptions[1] && inlineOptions[1].length){
				self.options.size = inlineOptions[1];
			}
			if( 'auto' == self.options.orientation && inlineOptions[2] && inlineOptions[2].length){
				self.options.orientation = inlineOptions[2];
			}

			if( '' == self.options.icon ){
				self.options.icon = (inlineOptions[3] && inlineOptions[3].length)?inlineOptions[3]:'';
			}

			if( self.element.hasClass('toggle') ){
				self.options.isToggle = true;
			}
			if( self.element.hasClass('active')){
				self.options.active = true;
			}

			self._setData('size',self.options.size);
			self._setData('orientation',self.options.orientation);
			self._setData('icon',self.options.icon);
			self._setData('corners',self._getData('corners'));
			if( self.options.isToggle){
				self._setData('isToggle',self.options.isToggle);
			}
			if( self.options.active){
				self._setData('active',self.options.active);
			}
			if( null!==self.options.label){
				self._setData('label',self.options.label);
			}

			if(! $.support.style){
				this.element.addClass('ui-button-inlineBlockFix');
				this.elmt_iconContainer.css({margin:0});
				if(null !== this.elmt_icon){
					this.elmt_icon.css({margin:0});
				}
			}
			// auto initialisation of button set on last buttonset element
			var buttonset = self.element.parent('[class*=ui-buttonset]');
			if( buttonset.length > 0){
					self._buttonset = buttonset;
					if( this.element.is(':last-child')){
						buttonset.buttonset();
					}
			}
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
			}
			if( this.iconIsImage){
				this.elmt_icon=$('<img src="'+escape(ico)+'"  />');
			}else{
				this.elmt_icon=$('<span class="ui-icon ui-icon-'+ico+'"></span>');
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
					self._orientationValue = (value=='auto'||value=='i')?'w':value;
					if( value==='i'){
						self._setData('label','');
					}
					self._rmExpClass(self.element,'ui-button-orientation-*','ui-button-orientation-'+self._orientationValue);
					self.iconBeforeLabel=( self._orientationValue=='n' || self._orientationValue=='w')?true:false;
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
					self.element.toggleClass('ui-state-highlight active',value?true:false);
					self._trigger('setactive',0,self);
					break;
			}
			return $.widget.prototype._setData.apply(this, arguments);
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

	});
	$.extend($.ui.button, {
		version: "@VERSION",
		defaults:{
			size:'auto',
			orientation:'auto',
			corners:'auto',
			icon:'',
			label:null,
			isToggle:false,
			toggle:false,
			active:false
		}
	});//*/

	$.widget('ui.buttonset',{
		_orientationValue:'',
		_sizeValue:'',
		_initiated:false,
		_init:function(){
			var self=this;
			// read inline options
			var inlineOptions = self.element.attr('class').match(/(?:^|\s+)ui-buttonset(?:-(tiny|normal|small|big|huge))?(?:-([ewsin](?=$|\s|-)))?(?:$|\s+)/);
			if( null === inlineOptions){
				return;
			}
			if( 'auto' == self.options.size && inlineOptions[1] && inlineOptions[1].length){
				self.options.size = inlineOptions[1];
			}
			if( 'auto' == self.options.orientation && inlineOptions[2] && inlineOptions[2].length){
				self.options.orientation = inlineOptions[2];
			}
			self.element.addClass('ui-buttonset ui-widget-content'+(self.element.is('[class*=ui-corner]')?'':' ui-corner-all'));

			if( !$.support.style){
				self.element.addClass('ui-button-inlineBlockFix');
			}
			self._setData('size',self.options.size);
			self._setData('orientation',self.options.orientation);
			self._initiated = true;
			self.propagateSettings();

		},

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

	});

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

		$.widget("ui.selectbuttonset",{
			multiple:false,
			buttonset:null,
			_init:function(){
				var self=this;
				// read inline options
				var inlineOptions = self.element.attr('class').match(/(?:^|\s+)ui-buttonset(?:-(tiny|normal|small|big|huge))?(?:-([ewsn](?=$|\s|-)))?(?:$|\s+)/);

				if(inlineOptions && inlineOptions[1] && inlineOptions[1].length ){
					self._setData('size',inlineOptions[1]);
				}
				if(inlineOptions && inlineOptions[2] && inlineOptions[2].length){
					self._setData('orientation',inlineOptions[2]);
				}
				if( self.element.attr('multiple') ){
					self.multiple = true;
				}
				self.buttonset = $('<div class="ui-buttonset"></div>');
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
					var optionIcon = option.attr('class').match(/(?:^|\s)ui-icon-([\w0-9_-]+)(?:$|\s)/);
					if(optionIcon !== null)
						optionIcon = optionIcon[1];
					$('<div class="ui-button-'+size+'-'+orientation+(optionIcon?'-'+optionIcon:'')+' toggle'+(option.attr('selected')?' active':'')+'">'+label+'</div>')
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
				option.attr('selected',button.active?'selected':'');
				if(! self.multiple){
					self.buttonset.find('.ui-button').not(button.element).button('option','active',false);
				}
				self.element.change();
				self._trigger('toggle',event,[self.element,button.element]);
			}
		});
		$.extend($.ui.selectbuttonset, {
			version: "@VERSION",
			defaults:{
				size:'normal',
				orientation:'w',
				buttonsetAddClass:'ui-widget ui-widget-content ui-corner-all'
			}
		});
})(jQuery);
