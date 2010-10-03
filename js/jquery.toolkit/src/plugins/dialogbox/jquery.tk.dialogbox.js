/**
*
* @changelog
* - 2010-09-26 - allow empty button in confirmbox
* - 2010-08-11 - move exposeShadow creation to first showExpose() call
*/

(function($){

//create a dialog shadow once and for all;
var exposeShadow = null
	, exposedDialogbox = 0
	, openedDialogbox = 0
	, escapableDialogbox = {length:0}
	//-- manage showing/hiding exposure
	, showExpose = function(){
			if( null === exposeShadow ){
				exposeShadow =$('<div id="toolkitExposeShadow" class="tk-dialogbox-shadow"></div>').appendTo('body');
			}
			exposeShadow.height($(document).height()).stop();
			if( $.tk.exposeShadow.defaults.show  instanceof Array){
				exposeShadow[$.tk.exposeShadow.defaults.show[0]]($.tk.exposeShadow.defaults.show[1]);
			}else if( typeof $.tk.exposeShadow.defaults.show ==='string'){
				exposeShadow[$.tk.exposeShadow.defaults.show]();
			}else if( $.isFunction($.tk.exposeShadow.defaults.show) ){
				$.tk.exposeShadow.defaults.show(exposeShadow);
			}
		}
	, hideExpose = function(){
			exposeShadow.stop();
			if( $.tk.exposeShadow.defaults.hide instanceof Array){
				exposeShadow[$.tk.exposeShadow.defaults.hide[0]]($.tk.exposeShadow.defaults.hide[1]);
			}else if( typeof $.tk.exposeShadow.defaults.hide ==='string'){
				exposeShadow[$.tk.exposeShadow.defaults.hide]();
			}else if( $.isFunction($.tk.exposeShadow.defaults.hide) ){
				$.tk.exposeShadow.defaults.hide(exposeShadow);
			}
		}
	, _escapeCloseCallback = function(e){
			if( escapableDialogbox.length < 1){
				$(window).unbind('keydown.dialogbox');
				return;
			}else if( e.which !== 27){
				return;
			}
			for( var widgetId in escapableDialogbox){
				if( widgetId !== 'length'){
					escapableDialogbox[widgetId].close();
				}
			}
		}
	,_setEscapeCloseCallback = function(action,widget){
			var elmtId = widget.elmt.attr('id');
			if( action==='add'){
				if( escapableDialogbox[elmtId] === undefined){
					escapableDialogbox.length++;
					escapableDialogbox[elmtId]=widget;
				}
			}else if( escapableDialogbox[elmtId] !== undefined){
				escapableDialogbox.length++;
				delete escapableDialogbox[elmtId];
			}
			if( escapableDialogbox.length < 1 ){
				$(window).unbind('keydown.dialogbox');
			}else if( escapableDialogbox.length===1){ //-- bind only once
				$(window).bind('keydown.dialogbox',_escapeCloseCallback);
			}
		}
	;
//-- settings for exposure
$.tk.exposeShadow ={
	defaults:{
		show:['slideDown',250]
		, hide:['slideUp',250]
	}
};

//-- dialogbox widget
$.toolkit('tk.dialogbox',{
	_classNameOptions:{
		opened:'|closed'
		, exposed:'|exposed|unexposed'
		, escapeClose: '|noescape'
		, title: '|notitle'
	},
	_init:function(){
		var self = this;
		this.elmt.hide();
		self._applyOpts('title|escapeClose|exposed');
		if( self.options.opened && self.options.opened !=='closed' ){
			self.open();
		}
		if( $.fn.bgIframe ){
			self.elmt.bgIframe();
		}
	},
	_set_title: function(title){
		if( title === "notitle"){
			title = false;
		}
		var titleElmt = this.elmt.find('.tk-dialogbox-title'), titleAttr = this.elmt.attr('title'), titleStr='';
		if( title === true){
			titleStr = titleAttr?titleAttr:'';
		}else if( title===false){
			if( titleElmt.length){
				titleElmt.remove();
			}
		}else{
			titleStr = title;
		}
		if( titleStr !==''){
			if( titleElmt.length){
				titleElmt.html(titleStr);
			}else{
				this.elmt.prepend('<h1 class="tk-dialogbox-title '+this.options.titleClasses+'">'+titleStr+'</h1>');
			}
		}
		return title;
	},
	_set_escapeClose:function(escapeClose){
		var self = this;
		if( escapeClose==='noescape'){
			escapeClose = false
		}else{
			escapeClose = escapeClose?true:false;
		}
		if( self.elmt.is(':visible') ){
			if( self.options.escapeClose && ! escapeClose ){
				_setEscapeCloseCallback('rem',self);
			}else if(escapeClose && ! self.options.escapeClose){
				_setEscapeCloseCallback('add',self);
			}
		}
		return escapeClose;
	},
	_set_exposed:function(exposed){
		if( exposed==="unexposed" ){
			exposed = false;
		}else{
			exposed = exposed?true:false;
		}
		if( this.options.exposed === exposed || ! this.elmt.is(':visible') ){
			return exposed;
		}
		if( exposed && exposedDialogbox<1){
			showExpose();
			exposedDialogbox=1;
		}else if( exposedDialogbox === 1 && !exposed){
			hideExpose();
			exposedDialogbox=0;
		}else{
			exposedDialogbox += (exposed?1:-1);
		}
		return exposed;
	},
	open:function(){
		if( this.elmt.is(':visible')){
			return false;
		}
		if( false===this._trigger('open', null, [this])){
			return false;
		}
		if( this.options.exposed){
			if( exposedDialogbox < 1){
				showExpose();
			}
			exposedDialogbox++;
		}
		this.elmt.addClass('tk-dialogbox '+this.options.classes)
			.show()
			.css({ top: $(window).scrollTop()+($(window).height()/2)-this.elmt.height()/2 , marginLeft:-(this.elmt.outerWidth()/2)})
		openedDialogbox++;
		if( this.options.escapeClose ){
			_setEscapeCloseCallback('add',this);
		}
	},
	close:function(){
		if(! this.elmt.is(':visible')){
			return false;
		}
		if( false === this._trigger('close', null, [this]) ){
			return false;
		}
		this.elmt.hide().removeClass('tk-dialogbox '+this.options.classes);
		if( this.options.exposed){
			exposedDialogbox--;
			if( exposedDialogbox < 1){
				hideExpose();
			}
		}
		openedDialogbox--;
		if( this.options.escapeClose ){
			_setEscapeCloseCallback('rem',this);
		}
	}

});

//- default dialog box settings
$.tk.dialogbox.defaults={
	opened:true // start opened or not
	, exposed:true // if false won't use the shadowed background
	, classes:'tk-content tk-border tk-corner tk-state-normal' // default styling classNames for the dialogbox element
	, titleClasses:'tk-border-bottom tk-corner-top' // default styling classNames for the dialogbox element
	, escapeClose:true // if true then pressing escape key will close the dialogbox
	, title: true // can be false for no title, a string to use as title or true to use the title attribute of the element as title
};

$.toolkit('tk.confirmbox','dialogbox',{
	_tk:{
		nameSpace:'tk',
		pluginName:'confirmbox',
		baseClass:'tk-confirmbox',
		initialized:false
	},
	_buttonBox:null,
	_init:function(){
		var self = this;
		self.elmt.hide();
		self._applyOpts('title|escapeClose|exposed');
		self._buttonBox = $('<div class="tk-confirmbox-buttonbox"><button type="button" class="tk-confirmbox-confirmButton tk-button">Confirm</button> <button type="button" class="tk-confirmbox-cancelButton tk-button">Cancel</button></div>').appendTo(self.elmt);
		self._buttonBox.find('.tk-confirmbox-confirmButton').click(function(e){
			if( false !== self._trigger('confirm', e, [self.elmt])){
				self.elmt.confirmbox('close');
			}
		});
		self._buttonBox.find('.tk-confirmbox-cancelButton').click(function(e){
			if( false !== self._trigger('cancel', e, [self.elmt]) ){
				self.elmt.confirmbox('close');
			}
		});
		self._applyOpts('confirm|cancel|confirmLabel|cancelLabel',true);
		if( self.options.opened && self.options.opened !=='closed' ){
			self.open();
		}
		//- if( $.fn.bgIframe ){
			//- self.elmt.bgIframe();
		//- }
	},
	_set_confirm:function(cb){
		if( ! cb ){
			this.elmt.unbind('confirmbox_confirm');
		}else{
			this.elmt.bind('confirmbox_confirm',cb);
		}
	},
	_set_cancel:function(cb){
		if( ! cb ){
			this.elmt.unbind('confirmbox_cancel');
		}else{
			this.elmt.bind('confirmbox_cancel',cb);
		}
	},
	_set_confirmLabel: function(str){
		this._buttonBox.find('.tk-confirmbox-confirmButton')
			.html(str)
			.toggle(str!='');
	},
	_set_cancelLabel: function(str){
		this._buttonBox.find('.tk-confirmbox-cancelButton')
			.html(str)
			.toggle(str!='');
	}

});

$.tk.confirmbox.defaults = $.extend(true,{},$.tk.dialogbox.defaults,{
	confirm:null,
	cancel:null,
	confirmLabel:'confirm',
	cancelLabel:'cancel'
});

})(jQuery);