/**
* jquery plugin for progressive enhancement of dropdown select .
* replace any select tag with a visually enhanced widget:
* option tags can use the rel attribute to define an image source to be inserted inside the enhanced select.
* see the demo for more example.
* @licence GPL / MIT
* @author jonathan gotti < jgotti at jgotti dot org > < jgotti at modedemploi dot fr > for modedemploi.fr
* @since 2009-09
* sample usage:
* $('select').enhancedSelect();
* or
* $('select').enhancedSelect(options);
*
* where options is an option object:
* possibles options are:
* - imgAfter:  boolean to insert rel images after options label
* - arrowString: the html string to used as the dropdown button
* - classes:     object to optionnaly override some default classNames
*/
(function($){
	$.fn.enhancedSelect = function(options){ return this.each(function(){ EnhancedSelect($(this),options); }); }
	function EnhancedSelect(elmt, options ){ return this instanceof EnhancedSelect? this.init(elmt,options): new EnhancedSelect(elmt, options); }

	//- exposed defaults options
	$.fn.enhancedSelect.defaults = {
		imgAfter   :false,
		arrowString:'v',//&#8964;
		multipleSeparatorString : ', '
	};
	//- exposed default classes
	$.fn.enhancedSelect.classes = {
		base  :'enhancedSelect',
		label :'enhancedSelect-label',
		arrow :'enhancedSelect-dropdown',
		list  :'enhancedSelect-list',
		item  :'enhancedSelect-item',
		hover :'enhancedSelect-item-hover',
		active:'enhancedSelect-item-active',
		icons :'enhancedSelect-icons'
	}
	//- plugin definition
	$.extend(EnhancedSelect.prototype,{
		elmt:null,
		container:null,
		label:null,
		list:null,
		opts:{},
		classes:{},
		init: function(elmt,options){
			var self = this;
			this.elmt = elmt;
			self.opts = $.extend({}, $.fn.enhancedSelect.defaults, options||{});
			if( !self.opts.classes){
				self.classes = $.fn.enhancedSelect.classes;
			}else{
				self.classes = $.extend({}, $.fn.enhancedSelect.classes, options.classes);
				delete self.opts.classes;
			}
			self._createElemnts();
			self._bindEvents();
			self.elmt.hide();
		},
		//-- internal methods
		_createElemnts: function(){
			var self = this;
			self.container = $(
				'<div class="'+self.classes.base+'" tabindex="'+
				Math.max(self.elmt.attr('tabindex'),0)+'"><div class="'+self.classes.label+
				'">&nbsp;</div><div class="'+self.classes.arrow+'">'+self.opts.arrowString+'</div></div>'
			)
			.insertAfter(self.elmt)
			.width(self.elmt.outerWidth());

			self._copyCssRules(['font','margin','padding','border-top-color','border-left-color','border-right-color','border-bottom-color']);
			self.label = self.container.find('.'+self.classes.label);
			self.list  = self.container.append('<ul class="'+self.classes.list+'"></ul>').find('.'+self.classes.list);
			self.list.css('background',self.container.css('background-color'));
			var o,item,rel,imgStr;
			self.elmt.find('option').each(function(i){
				o = $(this);
				item = $('<li class="'+self.classes.item+'"></li>').html(o.html());
				rel = o.attr('rel');
				if( rel && rel.match(/(\.(jpe?g|gif|png))$/i) ){
					imgStr = '<img src="'+rel+'" alt="" class="'+self.classes.icons+'" />';
					if( self.opts.imgAfter){
						item.append(imgStr);
					}else{
						item.prepend(imgStr);
					}
				}
				item.appendTo(self.list).data('option',o).data('oid',i);
			});
			self._copyCssRules(['border',	'width'],self.container,self.list);
			self._selectChanged();
		},
		_bindEvents:function(){
			var self = this;
			self.elmt.change(function(e){ self._selectChanged(e) });
			self.container.click(function(e){ self.listToggle(e); });
			self.container.focus(function(e){self._focus();});
			self.container.blur(function(e){self._blur();});
			self.list.find('li')
				.hover(
					function(){$(this).addClass(self.classes.hover);},
					function(){$(this).removeClass(self.classes.hover);}
				)
				.click(function(e){
					var o = $(this).data('option');
					if(! self.elmt.attr('multiple')){
						o.attr('selected','selected');
					}else{
						o.attr('selected',o.attr('selected')?false:'selected');
					}
					if( ! e.ctrlKey)
						self.listHide();
					self.elmt.change();
					e.stopPropagation();
				});
		},
		_selectChanged:function(e){
			var self = this;
			var selectedIds = [];
			if(! self.elmt.attr('multiple')){
				selectedIds.push(self.elmt.attr('selectedIndex'));
			}else{
				self.elmt.find('option').each(function(i){
					if( $(this).is(':selected') )
						selectedIds.push(i);
				});
			}
			self.selectItems(selectedIds);
			if( self.list.is(':visible')){
				self.listShow(e);
			}
		},
		_focus:function(e){
			var self=this;
			$('body')
			.bind('click.enhancedSelect',function(e){
				self._blur(e);
			})
			.bind('keydown.enhancedSelect',function(e){
				switch(e.which){
					case 27: // esc
						self.listHide(e);
						break;
					case 13: // return
						if(! self.list.is(':visible')){
							self.listShow(e);
						}else{
							self.listHide(e);
						}
						break;
					case 38: //up
						var sel = self.elmt.find('option:eq('+(Math.max(self.elmt.attr('selectedIndex')-1,0))+')').attr('selected','selected');
						if( self.elmt.attr('multiple') ){
							self.elmt.find('option').not(sel).attr('selected','');
						}
						self.elmt.change();
						e.stopImmediatePropagation();
						return false;
						break;
					case 40: //down
						var opts = self.elmt.find('option');
						var sel = opts.filter(':eq('+(Math.min(self.elmt.attr('selectedIndex')+1,opts.length-1))+')').attr('selected','selected');
						if( self.elmt.attr('multiple') ){
							opts.not(sel).attr('selected','');
						}
						self.elmt.change();
						e.stopImmediatePropagation();
						return false;
						break;
				}
			});
		},
		_blur:function(e){
			$('body').unbind('.enhancedSelect');
			this.listHide(e);
		},
		//-- exposed methods
		listToggle:function(e){
			if(this.list.is(':visible')){
				this.listHide(e);
			}else{
				this.listShow(e);
			}
		},
		listShow:function(e){
			var self = this;
			self.list
				.css({
					top: self.container.outerHeight(),
					left: 0-parseInt(self.container.css('border-left-width'))
				})
				.show();
			if( e)
				e.stopPropagation();
		},
		listHide:function(e){
			this.list.hide();
		},
		/*/--
		be aware that this method don't really select items in the original select, but only affect the visible elements.
		in fact it's called automaticly on orignial element change event to keep the plugin synchronized with the original element state.
		@param itemIds is an Array list of selected option index.
		--/*/
		selectItems: function(itemIds){
			var self  = this;
			var items = self.list.find('li').removeClass(self.classes.active);
			self.label.html(itemIds.length?'':'&nbsp;');
			for(var i=0;i<itemIds.length;i++){
				var item = items.filter(':eq('+itemIds[i]+')');
					item.addClass(self.classes.active);
				if( i > 0 )
					self.label.append(self.opts.multipleSeparatorString);
				self.label.append(item.html());
			}
		},
		//-- utility function that will perhaps go to an external plugin
		_copyCssRules:function (rules,from,to){
			var self = this;
			if( ! from)
				from = self.elmt;
			if(! to)
				to = self.container;
			$.each(rules,function(){
				var rule = this.toString();
				if( rule.toLowerCase() != 'margin'){ // margin cause a stop exec bug with ie
					var val = from.css(rule);
					if( val !== '' )
						to.css(rule,val);
				}
				switch(rule.toLowerCase()){
					case 'border':
						self._copyCssRules(['border-style','border-width','border-color','border-top', 'border-right', 'border-bottom','border-left'],from,to);
						break;
					case 'border-top':
						self._copyCssRules(['border-top-style','border-top-width','border-top-color'],from,to);
						break;
					case 'border-right':
						self._copyCssRules(['border-right-style','border-right-width','border-right-color'],from,to);
						break;
					case 'border-bottom':
						self._copyCssRules(['border-bottom-style','border-bottom-width','border-bottom-color'],from,to);
						break;
					case 'border-left':
						self._copyCssRules(['border-left-style','border-left-width','border-left-color'],from,to);
						break;
					case 'font':
						self._copyCssRules(['font-family','font-size','font-style','font-weight','font-variant'],from,to);
						break;
					case 'margin':
					case 'padding':
						self._copyCssRules([rule+'-top',rule+'-right',rule+'-bottom',rule+'-left'],from,to);
						break;
				}

			});
		}
	});
})(jQuery);
