(function($){

$.toolkit('tk.tabbed',{
	/*_classNameOptions: {
		optName:'optValue1|optValue2|...optValueN'
	},* /
	_storableOptions:{ // if set options names given there will try to save their state using available $.toolkit.storage api if enable
		urlElementLevel:'selected' // thoose ones will be stored for each plugin instance depending on the url + element id .
	},//*/

	_init:function(){
		var self = this,
			maxTabItemHeight=0;
		self.elmt.addClass(self.options.wrapperClass);
		self._tabsNavItems=[]; //- will become a jQuery collection
		self._pannelsElmts={};

		self._applyOpts('tabsNav');

		//-- lookup for tabs
		self._tabsNav.find('a').each(function(){
			// transform them as tab
			// then attach them a callback
			self._linkNavItem(this);
			self._tabsNavItems.push(this);
			maxTabItemHeight = Math.max(maxTabItemHeight,$(this).height());
		});
		self._tabsNavItems = $(self._tabsNavItems);
		self._tabsNavItems.addClass(self.options.tabsNavItemClass).height(maxTabItemHeight);

		var pannels = self.elmt.children()
			.not(self._tabsNav)
			.addClass(self.options.pannelsItemClass);
		self._pannelsWrapper = $("<div></div>")
			.insertAfter(self._tabsNav)
			.addClass(self.options.pannelsWrapperClass)
			.append(pannels);
		self._applyOpts('fixedHeight|selected|selectEvent');
	},

	_set_fixedHeight:function(h){
		var self= this,
			maxH=0,p;
		if( h===true ){
			for(p in self._pannelsElmts){
				maxH = Math.max(maxH,self._pannelsElmts[p].show(0).outerHeight());
				self._pannelsElmts[p].hide(0);
			}
			h = maxH;
		}
		if(parseInt(h) > 0){
			self._pannelsWrapper.addClass('tk-tabbed-panels-fixedHeight').height(h);
		}else{
			self._pannelsWrapper.removeClass('tk-tabbed-panels-fixedHeight').height('');
			return false;
		}
	},
	_set_selectEvent:function(eventName){
		var self = this;
		self._tabsNavItems.unbind('.tabbed');
		if( typeof eventName !== 'Array'){
			eventName = eventName.replace(/^\s*|\s*$/,'').split(/\s+/);
		}
		for( var i=0,l=eventName.length; i<l; i++){
			self._tabsNavItems.bind(eventName[i]+'.tabbed',function(e){
				self.elmt.tabbed('set_selected',$(this).attr('href').replace(/^[^#]*#/,''),e);
				e.preventDefault();
			});
		}
	},
	_set_selected:function(id,event){
		var self = this,p=null,res;
		res = self._trigger('_tabselect',event,[self,id,self.options.selected]);
		if( res === false){
			return self.options.selected;
		}
		if( id===null){
			id = window.location.href.replace(/^[^#]+(#.*)$/,'$1');
			p = self._tabsNavItems.filter('[href$='+id+']'); // use p as a tmp var
			id=p.length?p.attr('href').replace(/^[^#]*#/,''):0;
			p=null;
		}
		if( typeof self._pannelsElmts[id] === 'undefined' && self._tabsNavItems.eq(id).length){
			id = self._tabsNavItems.eq(id).attr('href').replace(/^[^#]*#/,'');
		}
		if( typeof self._pannelsElmts[id] !== 'undefined' ){
			p = self._pannelsElmts[id];
		}
		if( p === null){
			return self.options.selected;
		}
		res = self._trigger('_tabselected',event,[self,id]);
		if( res === false){
			return self.options.selected;
		}
		self._tabsNavItems.removeClass(self.options.tabsNavSelectedItemClass)
			.filter('[href$='+id+']')
			.addClass(self.options.tabsNavSelectedItemClass);
		self._pannelsWrapper.children().hide();
		p.show();
		return id;
	},
	_set_tabsNav:function(elmt){
		if( typeof this._tabsNav !== 'undefined'){
			this._tabsNav.removeClass(this.options.tabsNavClass);
		}
		if( elmt instanceof jQuery){
			this._tabsNav = elmt;
		}
		this._tabsNav = this.elmt.find(elmt);
		this._tabsNav.addClass(this.options.tabsNavClass);
	},
	_linkNavItem:function(item){
		item = $(item).ensureId();
		var	self=this;
			rel = item.attr('href'),
			pId = rel.replace(/^[^#]*#/,''),
			p=null;
		// lookup for related pannel and attach it to the elmt
		if( rel.match(/^#/) ){
			p = self.elmt.find(rel);// look elmt by id
			rel =  self.elmt.find('a[name='+rel.substr(1)+']');
			if( p.length < 1){ // failed so look if anchor exists
				p = (rel.length<1)?null:rel.filter(':eq(0)').parent();
			}
		}
		if( p === null ){
			p = $('<div></div>').appendTo(self._pannelsWrapper);
		}
		self._pannelsElmts[pId]=p;
	}




});

$.tk.tabbed.defaults = {
	tabsNav: '> ul, > ol',
	tabsNavClass: 'tk-tabbed-nav',
	tabsNavItemClass: 'tk-tabbed-nav-item tk-inlineStack tk-border-top tk-border-left tk-border-right tk-corner-top',
	tabsNavSelectedItemClass:'tk-tabbed-active',
	pannelsWrapperClass: 'tk-tabbed-pannels tk-border tk-corner-bottom',
	pannelsItemClass: 'tk-tabbed-pannels-item',
	wrapperClass:'',
	selected:null,
	selectEvent:'click',
	fixedHeight:false
}


})(jQuery);