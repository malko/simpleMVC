/**
new javascript widget toolkit based on jquery.
This work is largely inspired by jquery-ui and so you may wonder why another library ?
The answer is not good or bad, just that i like jquery-ui but sometimes i desire to make things differently
for example i like to drive most of enhancement just by giving classnames to my basic markup and that particular stuff
was rejected by the jquery-ui team. As another example many users claim for some additions in the base css-framework
like a ui-state-success or something like that but this was also rejected by the team. Other stuffs like thoose ones
was really missing to better stick to my way of doing things so i start this new library.
( Don't misandurstood me jquery-ui is great library and the base of Tk is largely taken from it. )

@author jonathan gotti <jgotti at jgotti dot net>
@licence Dual licensed under the MIT / GPL licenses.

@changelog
 - 2010-04-04 - add disable jquery method
 - 2010-04-04 - rewrite _trigger
 - 2010-02-24 - add ensureId jquery method and rename uniqueId toolkit method to requestUniqueId as it's more meeningfull
							- make use of ensureId method at widet construction time.
							- $.toolkit._getInstance() now accept jQuery element as first parameter (in which case it will work on the first element of the collection)
 - 2010-02-16 - add get(1)_pluginInstance method
 - 2010-02-10 - add urlElementLevel to storableOptions
 - 2010-01-26 - add uniqueId method and use it for any element promoted to widget that haven't id already set

*/

(function($){

	var _dbg_ = true,
		_throwOnBadCall=true;

	window.dbg = function(){
		if(! _dbg_ ){
			return false;
		}
		if( typeof console !=='undefined' && console.debug){
			if( typeof chrome !== 'undefined')
				console.debug.call(console,Array.prototype.shift.call(arguments,0));
			else
				console.debug(dbg.caller,arguments);
		}else if(typeof opera !== 'undefined' && typeof opera.postError !== 'undefined'){
			var s = [];
			_dbg=function(a){
				var i,s = [];
				if( typeof(a)=='object'){
					for( i in a ){ s.push( i+':'+_dbg(a[i])); }
					return '{'+s.join(', ')+'}';
				}else if (typeof(a)=='array'){
					for( i=0;i<a.length;i++ ){ s.push((typeof(a[i])==='object' || typeof(a[i])==='array' )?_dbg(a[i]):a[i]); }
					return '['+s.join(', ')+']';
				}
				return a;
			}
			for( i=0; i< arguments.length;i++ ){s.push(_dbg(arguments[i]));}
			opera.postError(s.join(', '));
		}
	};

$.toolkit = function(pluginName,prototype){
	//-- make nameSpace optional default to tk.
	var nameSpace = 'tk';
	if( pluginName.indexOf('.')){
		pluginName = pluginName.split('.');
		nameSpace = pluginName[0];
		pluginName= pluginName[1];
	}
	//-- make plugin initializer and put it in the given namespace
	if( undefined===$[nameSpace]){
		$[nameSpace]={};
	}
	$[nameSpace][pluginName] = function(elmt,options) {
		var self = this;
		self._tk ={
			nameSpace : nameSpace,
			pluginName: pluginName,
			baseClass : nameSpace+'-'+pluginName,
			initialized:false
		};
		self.elmt = $(elmt).ensureId();
		self.elmt.data(pluginName,self);
		//-- merge options
		var inlineOptions = self._classNameOptions?$.toolkit._readClassNameOpts(self.elmt,self._tk.baseClass,self._classNameOptions):{};
		self.elmt.addClass(self._tk.baseClass);
		self.options=$.extend(
			{},
			$[nameSpace][pluginName].defaults||{},
			inlineOptions,
			options||{}
		);

		if( self._storableOptions && (! self.options.disableStorage) && $.toolkit.storage && $.toolkit.storage.enable() ){
			if( self._storableOptions.pluginLevel ){
				var v = '',pStored=self._storableOptions.pluginLevel.split(/[,\|]/);
				for(var i=0;i<pStored.length;i++ ){
					v = $.toolkit.storage.get(self._tk.pluginName+'_'+pStored[i]);
					if( null !== v){
						self.options[pStored[i]]=v;
					}
				}
			}
			var id = self.elmt.attr('id'),
			v ='',eStored='',encodedUri=escape(window.location.href);
			if( id && self._storableOptions.elementLevel){
				eStored=self._storableOptions.elementLevel.split(/[,\|]/);
				for(var i=0;i<eStored.length;i++ ){
					v = $.toolkit.storage.get(self._tk.pluginName+'_'+eStored[i]+'_'+id);
					if( null !== v){
						self.options[eStored[i]]=v;
					}
				}
			}
			if( id && self._storableOptions.urlElementLevel){
				eStored=self._storableOptions.urlElementLevel.split(/[,\|]/);
				for(var i=0;i<eStored.length;i++ ){
					v = $.toolkit.storage.get(self._tk.pluginName+'_'+eStored[i]+'_'+id+'_'+encodedUri);
					if( null !== v){
						self.options[eStored[i]]=v;
					}
				}
			}
		}

		if( $.isFunction(self._init) ){
			self._init();
		}
		self._tk.initialized=true;
	};
	//-- extends plugin methods
	$[nameSpace][pluginName].prototype = $.extend(
		true,
		{}, //-- create a new class
		$.toolkit.prototype, //-- extend it with base tk prototype
		prototype //-- finally add plugin own methods
	);

	//-- expose plugin function to the world
	$.fn[pluginName] = function(){
		var method = null,propName=null,onlyOne=false;
		if( typeof arguments[0] === "string"){
			method = Array.prototype.shift.call(arguments,1);
			if( method.match(/^_/)){ //-- avoid call to pseudo private methods
				return this;
			}
			var ret = method.match(/^(get|return)/)?true:false;
			if(! $.isFunction($[nameSpace][pluginName].prototype[method]) ){
				var match = method.match(/^([sg]et|return)(1)?(?:_+(.*))?$/);
				if( null === match){
					if(typeof(_throwOnBadCall)!=='undefined' && _throwOnBadCall){
						throw('jquery.toolkit: '+method+'() unknown method call.')
					}
					return this;
				}
				propName = match[3]?match[3]:Array.prototype.shift.call(arguments,1);
				method   = ('return'===match[1])?propName:match[1];
				onlyOne  = match[2]?true:false
			}
		}
		var args = arguments,
			res = [];
		//- var res = new Array;
		this.each(function(i){
			var instance = $.toolkit._getInstance(this, nameSpace+'.'+pluginName, method?true:(args[0]||{}));
			if( method && $.isFunction(instance[method]) ){
				switch(method){
					case 'get':
						res[i] = instance.get(propName);break;
					case 'set':
						if( propName ){
							instance.set(propName,args[0]);break;
						}
						// continue to default intentionnaly
					default:
						res[i] = instance[method].apply(instance,args);
				}
			}
		});
		return ret?(onlyOne?res[0]:res):this;
	};
};

/**
* Common toolkit plugins prototypes
*/
$.toolkit.prototype = {
	_tk:{
		nameSpace:null,
		pluginName:'tkplugin',
		baseClass:'tk-plugin',
		initialized:false
	},
	/*
	// optional options and their values that may be applyed by element's class attribute. (init options will overwrite them)
	_classNameOptions: {
		optName:'optValue1|optValue2|...optValueN'
	},
	_storableOptions:{ // if set options names given there will try to save their state using available $.toolkit.storage api if enable
		pluginLevel:'optName, ...',    // thoose ones will be stored for all plugin instance
		elementLevel:'optName, ...'    // thoose ones will be stored for each plugin instance depending on their element id attribute.
		urlElementLevel:'optName, ...' // thoose ones will be stored for each plugin instance depending on the url + element id .
	}
	*/
	elmt:null,
	options:{
		disableStorage:false
	},
	// played only once by elmt
	_init: function(){},
	// effectively apply settings by calling set on given options names.
	// additional parameter ifNotDefault will only apply settings if different from default.
	_applyOpts: function(names,ifNotDefault){
		if( typeof names === 'string'){
			names = names.split(/[|,]/);
		}
		if(! ifNotDefault){
			for(var i=0;i<names.length;i++){
				this.set(names[i],this.options[names[i]]);
			}
			return this;
		}
		var defaults = $[this._tk.nameSpace][this._tk.pluginName].defaults;
		for(var i=0;i<names.length;i++){
			if( defaults[names[i]] !== this.options[names[i]] ){
				this.set(names[i],this.options[names[i]]);
			}
		}
		return this;
	},
	//-- event management --//
	_trigger: function(eventName, originalEvent, params){
		if( undefined===params){
			params = [this.elmt];
		}
		switch( eventName.indexOf('_')){
			case -1:
				eventName = this._tk.pluginName+'_'+eventName;break;
			case 0:
				eventName = eventName.substr(1);break;
			default://do nothing
		}
		/*
		next 7 lines from jquery-ui
		copyright (c) 2010 AUTHORS.txt (http://jqueryui.com/about)
		Dual licensed under the MIT (MIT-LICENSE.txt)
		and GPL (GPL-LICENSE.txt) licenses.
		copy original event properties over to the new event
		this would happen if we could call $.event.fix instead of $.Event
		but we don't have a way to force an event to be fixed multiple times*/
		var e = $.Event(originalEvent);
		if ( e.originalEvent ) {
			for ( var i = $.event.props.length, prop; i; ) {
				prop = $.event.props[ --i ];
				e[ prop ] = e.originalEvent[ prop ];
			}
		}
		//var e = $.event.fix(originalEvent||{});
		e.type = eventName;
		//return this.elmt.triggerHandler(e,params);
		this.elmt.trigger(e,params);
		return e.isDefaultPrevented()?false:true;
	},
	_get_pluginInstance:function(){
		return this;
	},
	//-- Exposed methods --//
	get:function(key){
		if( $.isFunction(this['_get_'+key])){
			return this['_get_'+key]();
		}
		return ( typeof this.options[key] !== 'undefined')?this.options[key]:undefined;
	},
	set:function(key,value){
		if( typeof key === 'object'){
			var _key='';
			for( k in key){
				this.set(k,key[k]);
			}
			return;
		}
		if( $.isFunction(this['_set_'+key]) ){
			var v = this['_set_'+key](value);
			if( undefined !== v){
				value = v;
			}
		}
		if( typeof this.options[key] !== 'undefined'){
			this.options[key] = value;
		}
		if( this._storableOptions && (! this.options.disableStorage) && $.toolkit.storage && $.toolkit.storage.enable() ){
			var exp = new RegExp('(^|,|\\|)'+key+'($|,|\\|)');
			if( this._storableOptions.pluginLevel && this._storableOptions.pluginLevel.match(exp) ){
				$.toolkit.storage.set(this._tk.pluginName+'_'+key,value);
			}else if( this._storableOptions.elementLevel && this._storableOptions.elementLevel.match(exp) ){
				$.toolkit.storage.set(this._tk.pluginName+'_'+key+'_'+this.elmt.attr('id'),value);
			}else if( this._storableOptions.urlElementLevel && this._storableOptions.urlElementLevel.match(exp) ){
				$.toolkit.storage.set(this._tk.pluginName+'_'+key+'_'+this.elmt.attr('id')+'_'+escape(window.location.href),value);
			}
		}
	}
};

//-- TOOLKIT HELPER METHODS --//
$.toolkit.initPlugins = function(pluginNames){
	if(typeof pluginNames === 'string'){
		pluginNames = pluginNames.split(/[|,]/);
	}
	for( var i=0,l=pluginNames.length,p='';i<l;i++){
		p=pluginNames[i];
		new Function("jQuery('.tk-"+p+"')."+p+"()")();
	}
};
/**
* allow to be sure to get a plugin instance from plugin instance or element on which the plugin is applyied.
* @param object  elmt         the pluginInstance or the element we want the plugin instance of
* @param string  pluginName   the plugin full name with namespace (namespace.pluginname) namespace will default to tk if not passed.
* @param mixed   defaultToNew if passed as true will ensure to return a plugin instance even on element with no plugin already attached.
*                             if passed as object will be considered as options for new instance creation (only if no attached instance is found)
* return pluginInstance or undefined
*/
$.toolkit._getInstance = function(elmt,pluginName,defaultToNew){
	var nameSpace = 'tk';
	if( pluginName.indexOf('.') > -1){
		pluginName = pluginName.split('.');
		nameSpace = pluginName[0];
		pluginName= pluginName[1];
	}
	if( elmt instanceof $[nameSpace][pluginName]){
		return elmt;
	}
	if( elmt instanceof jQuery){
		elmt = elmt.get(0);
	}
	var instance = $.data(elmt,pluginName);
	if( instance ){
		//dbg('living '+pluginName+' Instance found for',elmt,instance);
		return instance;
	}else if(defaultToNew){
		//dbg('living '+pluginName+' Instance NOT found for',elmt,instance);
		return new $[nameSpace][pluginName](elmt,typeof(defaultToNew)==='object'?defaultToNew:undefined);
	}
	return undefined;
};

/**
* read extra options settings in widget.element's class attribute and return them as object
* @param domElement elmt        the dom or jquery element on which to look class attribute.
* @param string     baseClass   is the plugin baseClass we search for extended version
*                               (ie: baseClass tk-pluginName will look for any tk-pluginName-* class attribute),
* @param array      optionsList an associtive list of optionNames with their possible values separated by a pipe '|'
*                               if an empty value is set at first position it'll be considered optional.
* @return object
*/
$.toolkit._readClassNameOpts=function(elmt,baseClass,optionsList){
	elmt=$(elmt);
	//-- get class attribute if none then return
	var classAttr = elmt.attr('class');
	if( undefined===classAttr || classAttr.length <1){ // nothing to read return
		return {};
	}

	//prepare expression
	var opts={}, optName='', id=0, exp = '(?:^|\\s)'+baseClass+'(?=-)',noCaptureExp = /(^|[^\\])\((?!\?:)/g, oVals;
	for(optName in optionsList ){
		oVals = optionsList[optName].replace(noCaptureExp,'$1(?:');//-- avoid capture parentheses inside expression
		exp += ( oVals.substr(0,1)=='|' )?'(?:-('+oVals.substr(1)+'))?':'-('+oVals+')';
	}
	exp = new RegExp(exp+'(?:$|\\s)');
	var matches = classAttr.match(exp);
	if( null===matches){
		return opts;
	}
	//prepare options objects from matches
	for(optName in optionsList){
		if( matches[++id]){
			opts[optName] = matches[id];
		}
	}
	return opts;
};

/**
* remove matching class names from element and eventually add new class on given element (default to widget.element)
* @param domElement     elmt element on which to work
* @param pseudoRegexp  exp  pseudo expression to search and remove any '*' will be evaluated as alphanum chars, - or _
* @param string        className to also add to the element after removing (shortcut to call $(elmt).addClass() )
* @return jqueryObject
*/
$.toolkit._removeClassExp = function(elmt,exp,add){
	elmt=$(elmt);
	var classAttr = elmt.attr('class');
	if( classAttr ){
		exp = new RegExp('(?:^|\\s)'+exp.replace(/\*/g,'[a-zA-Z_0-9_-]*')+'(?=$|\\s)','g');
		elmt.attr('class',classAttr.replace(exp,''));
	}
	if( undefined!==add ){
		elmt.addClass(add);
	}
	return elmt;
};

/**
* return a unique id for element
*/
$.toolkit.requestUniqueId = function(){
	if( window.top.jQuery && window.top.jQuery.toolkit && window.top.jQuery.toolkit._uniqueId )
		return 'tkUID'+(++window.top.jQuery.toolkit._uniqueId);
	window.top.jQuery.toolkit._uniqueId=1;
	return 'tkUID'+window.top.jQuery.toolkit._uniqueId;
}
$.extend($.fn,{
	ensureId:function(){
		return this.each(function(){
			var e = $(this);
			if( e.attr('id').length < 1){
				e.attr('id',$.toolkit.requestUniqueId());
			}
		});
	},
	disable:function(state){
		state = state?true:false;
		this.attr("disabled",state?false:"disabled")
		.attr("aria-disabled",state?false:"disabled")
		.toggleClass("tk-state-disabled",state?false:true);
	}
});
})(jQuery);
