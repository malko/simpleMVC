(function($){

if( typeof removeAccents === 'undefined'){
	var accentTable = {
		'µ':'u',
		'À':'A', 'Á':'A', 'Â':'A', 'Ã':'A', 'Ä':'A', 'Å':'A', 'Æ':'AE',
		'Ç':'C', 'È':'E', 'É':'E', 'Ê':'E', 'Ë':'E',
		'Ì':'I', 'Í':'I', 'Î':'I', 'Ï':'I', 'Ð':'D', 'Ñ':'N',
		'Ò':'O', 'Œ':'OE', 'Ó':'O','Ô':'O', 'Õ':'O', 'Ö':'O', 'Ø':'O',
		'Ù':'U', 'Ú':'U', 'Û':'U', 'Ü':'U', 'Ý':'Y', 'ß':'s',
		'à':'a', 'á':'a', 'â':'a', 'ã':'a', 'ä':'a', 'å':'a', 'æ':'ae',
		'ç':'c', 'è':'e', 'é':'e', 'ê':'e', 'ë':'e',
		'ì':'i', 'í':'i', 'î':'i', 'ï':'i', 'ñ':'n',
		'ð':'o', 'œ':'oe', 'ò':'o','ó':'o', 'ô':'o', 'õ':'o', 'ö':'o', 'ø':'o',
		'ù':'u', 'ú':'u', 'û':'u', 'ü':'u', 'ý':'y', 'ÿ':'y',
		'’':'\'','`':'\''
	},
	accentExp=[];
	removeAccents = function(str){
		if( accentExp instanceof Array){
			for(var c in accentTable){
				accentExp.push(c);
			}
			accentExp = new RegExp('('+accentExp.join('|')+')','g');
		}
		return str.replace(accentExp,function(m,c){
			return accentTable[c];
		});
	};
}

var RegEscape = function(str){
	return str.replace(/[-[\]{}()*+?.\\^$\|!\/]/g, "\\$&");
}
//- if( typeof RegExp.escape === 'undefined' ){
	//- RegExp.escape = function (str) { return str.replace(/[-[\]{}()*+?.\\^$|#\s]/g, "\\$&"); };
//- }
//-     return (str + '').replace(new RegExp('[.\\\\+*?\\[\\^\\]$(){}=!<>|:\\' + (delimiter || '') + '-]', 'g'), '\\$&');


//--- EDIT IN PLACE ---//
$.toolkit('tk.edip',{
	inputElmt:null
	,_classNameOptions:{
		cssreplicant:'|replicant'
		,inputType:'|text|textarea'
		,inputName:'(.*)'
	}
	,_init:function(){
		var self = this;
		if( this.options.cssreplicant === 'replicant'){
			this.options.cssreplicant = true;
		}
		if(! self.elmt.attr('tabIndex') ){ // make element tab accessible
			self.elmt.attr('tabIndex',0);
		}
		if( self.options.value === '' ){ // get initial value from elmt content if not setted
			self.options.value = self.elmt.html();
		}
		if( self.options.placeholder==='' && self.elmt.attr('title') ){ // use title as default placeholder
			self.options.placeholder = self.elmt.attr('title');
		}
		self._applyOpts('inputType|placeholder');
		if( self.elmt.css('display') === 'inline' ){
			self.elmt.css('display','inline-block');
		}
		self.elmt
			.bind('click.edip',function(){ self.elmt.focus(); })
			.bind('focus.edip',function(){ self.toggle(); });
	}
	,_get_input:function(){
		return this.inputElmt[0];
	}
	,_get_value:function(){
		return this.options.nl2br ? this.inputElmt.val().replace(/(\r?\n)(?!<br\s?\/?>)/g,'$1<br />') : this.inputElmt.val() ;
	}
	,_set_inputType:function(t){
		var self = this;
		if( self._tk.initialized && t === this.options.inputType ){
			return t;
		}
		self.inputElmt && self.inputElmt.remove();
		switch(t){
			case 'text':
				self.inputElmt = $('<input type="'+t+'"/>').insertAfter(self.elmt).hide();
				break;
			case 'textarea':
				self.inputElmt = $('<'+t+'></'+t+'>').insertAfter(self.elmt).hide();
				break;
		}
		self.inputElmt.bind('blur',function(){
			var v = self._get_value();
			self.set('value',v);
			if( self.options.change ){
				self.options.change.call(self.elmt,v);
			}
			self.toggle();
		});
		this._applyOpts('className|inputName|value');
		return t;
	}
	,_set_className:function(n){ this.inputElmt.addClass(this._tk.baseClass+'-input'+(n?' '+n:'')); }
	,_set_inputName:function(n){ this.inputElmt.attr('name',n); }
	,_set_placeholder:function(p){
		this.inputElmt.attr('placeholder',p);
		if( this.get('value') === ''){
			this.elmt.html('<span class="tk-state-disabled">'+p+'</span>');
		}
	}
	,_set_value:function(v){
		var _ = this;
		_.inputElmt.val(_.options.nl2br?v.replace(/(\r?\n)(?!<br\s?\/?>)/g,'$1<br />'):v);
		_.elmt.html(v);
	}
	,toggle:function(visible){
		if( undefined===visible){
			visible = this.elmt.is(':visible');
		}
		this.inputElmt.toggle(visible);
		if( visible ){
			if( this.options.cssreplicant ){
				this.inputElmt
					.cssreplicant({origin:this.elmt,ruleSet:'font|width|height|lineHeight|padding|margin|display|position|top|left|bottom|right'})
					.cssreplicant('update')
			}
			this.inputElmt.click().focus();
		}
		this.elmt.toggle(!visible);

	}
});

$.tk.edip.defaults = {
	inputType:'text' // text / textarea ...
	,inputName:''
	,className:''
	,value:''
	,placeholder:''
	,cssreplicant:false
	,change:null
	,nl2br:false
}

var replicantExtendedRules = {
	background  : ['backgroundAttachment','backgroundColor','backgroundImage','backgroundPosition','backgroundRepeat']
	,border      : ['borderColor','borderCollapse','borderStyle','borderWidth','borderTop', 'borderRight', 'borderBottom','borderLeft']
	,borderTop   : ['borderTopStyle','borderTopWidth','borderTopColor']
	,borderRight : ['borderRightStyle','borderRightWidth','borderRightColor']
	,borderBottom: ['borderBottomStyle','borderBottomWidth','borderBottomColor']
	,borderLeft  : ['borderLeftStyle','borderLeftWidth','borderLeftColor']
	,borderRadius: ['borderTopLeftRadius','borderTopRightRadius','borderBottomLeftRadius','borderBottomRightRadius','MozBorderRadius','webkitBorderRadius']
	,borderRadiusTopLeft:['MozBorderRadiusTopLeft','webkitBorderTopLeftRadius']
	,borderRadiusTopRight:['MozBorderRadiusTopRight','webkitBorderTopRightRadius']
	,borderRadiusBottomLeft:['MozBorderRadiusBottomLeft','webkitBorderBottomLeftRadius']
	,borderRadiusBottomRight:['MozBorderRadiusBottomRight','webkitBorderBottomRightRadius']
	,font        : ['fontFamily','fontSize','fontStyle','fontVariant','fontWeight']
	,listStyle   : ['listStyleImage','listStylePosition','listStyleType']
	,margin      : ['marginTop','marginRight','marginBottom','marginLeft']
	,opacity     : ['MozOpacity','webkitOpacity','filter']
	,outline     : ['outlineColor','outlineStyle','outlineWidth']
	,padding     : ['paddingTop','paddingRight','paddingBottom','paddingLeft']

};

var _copyCssRules =function (rules,from,to,transfert){
	if( typeof rules === 'string' ){
		rules = rules.split(/[,|]/);
	}
	from = $(from);
	to   = $(to);
	var l = rules.length,
		rule = '',
		val = '';
	for(var i=0; i<l; i++){
		rule = rules[i];
		if( rule.indexOf('-') ){ //-- ensure rule to be camelCased
			rule = rule.replace(/-([a-z])/ig, function(fullmatch,captureOffset1){ return captureOffset1.toUpperCase(); });
		}
		val = '';
		switch(rule){
			case 'margin': // margin cause a stop exec bug with ie
				break;
			case 'display': // don't copy display:none
				val = from.css(rule);
				if( val==='none' ){
					val='';
				}
				break;
			default:
				val = from.css(rule);
				break;
		}
		if( val !== ''){
			to.css(rule,val);
		}
		if( replicantExtendedRules[rule] ){
			_copyCssRules(replicantExtendedRules[rule],from,to);
		}
		if( transfert){ // transfert reset the rule from the from element
			from.css(rule,'');
		}
	}
};

/**
* replic some css rules from an element to another
*/
$.toolkit('tk.cssreplicant',{
	_classNameOptions:{
		updateOn:"|click|change|focus|blur|mouse(over|up|down|leave|enter|out)"
		,ruleSet:"|.*"
	}
	,init:function(){
		this._applyOpts('updateOn|ruleSet|origin');
	}
	,_set_updateOn:function(e){
		var self = this;
		self.elmt.unbind('.cssreplicant');
		if( e ){
			self.elmt.bind(e,function(){ self.update() })
		}
	}
	,_set_ruleSet:function(r){
		return r.replace(/[^a-z0-9\|]+/ig,'|');
	}
	,update:function(){
		if( ! this.options.origin ){
			return;
		}
		_copyCssRules(this.options.ruleSet.split('|'),this.options.origin==='rel'?this.elmt.attr('rel'):this.options.origin,this.elmt);
	}
});

$.tk.cssreplicant.defaults={
	updateOn:null
	,ruleSet:null
	,origin:'rel' // default to replic element pointed in rel attribute
}

/**
allow to set an element fixed at a given top position but only when it will go over it
*/
$.toolkit('tk.fixedAtTop',{
	_classNameOptions:{
		top:'|\\d+'
	}
	,_init:function(){
		var _ = this, maxScroll = _.elmt.offset().top,fixable=true;
		_.fixed = false;
		_.positionOrigin = _.elmt.css('position');
		_.topOrigin = _.elmt.css('top');
		_.placeHolder = _.elmt.clone()
			.attr('id','')
			.addClass('tk-fixedAtTop-placeholder')
			.removeClass('tk-fixedAtTop')
			.css({visibility:'hidden'})
			.insertAfter(_.elmt)
			.hide()
		;
		_.placeHolder.children().remove();
		if( _.elmt.css('position','fixed').css('position') !== 'fixed' ){
			//_.elmt.css('position','absolute');
			fixable=false;
		}
		_.elmt.css('position',_.positionOrigin);
		$(document).on('scroll.fixedAtTop',function(e){
			var scrollTop = $(this).scrollTop(), top, fixed=false;
			if( ! fixable ){
				//top = Math.max( scrollTop+_.options.top, maxScroll );
				if( (scrollTop+_.options.top) > maxScroll ){
					top = scrollTop+_.options.top;
					fixed = true;
				}else{
					top = _.positionOrigin;
				}
			}else{
				//top  = Math.max(_.options.top,maxScroll - scrollTop);
				if( _.options.top > (maxScroll - scrollTop) ){
					top = _.options.top;
					fixed = true;
				}else{
					top = _.topOrigin;
				}
			}
			if( fixed === _.fixed ){
				return ;
			}
			_.fixed = fixed;
			_.placeHolder[fixed?'show':'hide'](0).height(_.elmt.height()).width(_.elmt.width());
			_.elmt
				.toggleClass('tk-fixedAtTop-fixed',fixed)
				.css({top:top+(top.toString().match(/\D/)?'':'px'),position:fixed?(fixable?'fixed':'absolute'):_.positionOrigin})
			;
		});
	}
})
$.tk.fixedAtTop.defaults={top:0}





$.FBuilder = $.FBuilder || {};
$.FBuilder.lang = $.FBuilder.lang?$.FBuilder.lang:'fr';
$.FBuilder.langMsg = {
	fr:{
		groupLegend:'Nom du groupe'
		,groupRemove:'Supprimer le groupe'
		,groupAddRow:'Ajouter une ligne'
		,paragraphPlaceHolder:'Taper le texte de votre paragraphe ici.'
		,content:'Contenu'
		,name:'Nom'
		,groupName:'Nom du groupe'
		,label:'Etiquette'
		,"default":'Valeur par défaut'
		,'required':"Requis"
		,options:"Liste d'options"
		,'2lines':"Sur 2 lignes"
		,'width':"Largeur du container"
		,'info-width':"Largeur de la bulle"
		,'input class':"classe de l'entrée"
		,'label class':"classe de l'étiquette"
		,'container class':"classe du container"
		,noLegend:'Cacher le nom'
	}
	,en:{
		groupLegend:'Group name'
		,groupRemove:'Remove group'
		,groupAddRow:'Add a row'
		,paragraphPlaceHolder:'Type your paragraph text here.'
		,content:'Content'
		,name:'Name'
		,groupName:'Group name'
		,label:'Label'
		,"default":'Default value'
		,'required':"Required"
		,options:"Options list"
		,'2lines':"2 lines"
		,'width':"Cell's width"
		,'info-width':"Bubble width"
		,'input class':"Entry's class"
		,'label class':"Label's class"
		,'container class':"Container's class"
		,noLegend:'Keep name hidden'
	}
};
$.FBuilder.msg = function(msgId,alternateMsg){
	try{
		return $.FBuilder.langMsg[$.FBuilder.lang][msgId]
			? $.FBuilder.langMsg[$.FBuilder.lang][msgId]
			: ($.FBuilder.langMsg[$.FBuilder.lang][alternateMsg] ? $.FBuilder.langMsg[$.FBuilder.lang][alternateMsg] : msgId);
	}catch(e){
		return msgId;
	}
};

$.toolkit('FBuilder.fbContainer',{
	_init:function(){
		var _ = this;
		_.elmt.sortable({
			revert: 50
			,helper:'clone'
			,placeholder: 'tk-state-warning placeholder'
			,forceHelperSize:true
			,forcePlaceholderSize:true
			,dropOnEmpty:true
			//,tolerance:'pointer'
			,delay:30
			,scrollSensitivity: 40
			,receive:function(e,ui){
				// get dropped element
				var dropped = $('#'+ui.item.attr('id'),_.elmt),widgetOptions,elmt;
				if( dropped.is('.FBuilder-fbWidget') ){
					return;
				}
				if(! dropped.is(_.options.accept) ){
					dropped.remove;
				}else{
					widgetOptions = ui.item.fbWidgetButton('get1_pluginInstance').options;
					_.insert(widgetOptions.tpl,widgetOptions,dropped);
				}
				return;
			}
			//- ,over:function(event,ui){
				//-
			//- }
			,connectWith:_.options.connectWith || undefined
		});
	}
	/**
	elmt the element to effectively insert
	widgetOptions contains optional widget options
	dropped is optional element that was originally dropped and will be replaced by elmt
	@return inserted element
	*/
	,insert:function(elmt,widgetOptions,dropped){
		var _ = this;
		elmt = $(elmt);
		if( dropped ){
			elmt.insertAfter(dropped);
		}else{
			elmt.appendTo(_.elmt);
		}
		if( widgetOptions && widgetOptions.dropped ){
			widgetOptions.dropped.call(elmt,_.elmt,dropped);
		}
		if(! widgetOptions ){ widgetOptions = {type:'text'};}
		if( ! widgetOptions.type ){ widgetOptions.type = 'text'; }
		//- elmt.fbWidget( (widgetOptions && widgetOptions.props) ? {props:widgetOptions.props,type:widgetOptions.type} : {type:widgetOptions.type||'text'} );
		elmt.fbWidget( widgetOptions );
		_.elmt.sortable('refresh');
		if( dropped ){
			$(dropped).remove();
		}

		return elmt;
	}
});
$.FBuilder.fbContainer.defaults = {
	accept:'.FBuilder-fbWidgetButton'
	,connectWith:null
};




$.toolkit('FBuilder.fbWidget',{
	_init:function(){
		var _=this
			,close = _.elmt.find('button.removeRow')
			,closeCb = function(){
				if(! confirm('You are about to remove this widget and all it\'s content.\nDo you confirm that action ?') ){
					return false;
				}
				_.remove();
				return false;
			}
		;
		if( close.length ){
			close.click(closeCb);
		}else{
			close = $('<span class="close">&times;</span>').appendTo(_.elmt)
				//.css({position:'absolute',top:'-10px',right:'-10px',border:'solid #000 2px',width:'16px',height:'16px',borderRadius:'4px',display:'block'})
				.hide()
				.click(closeCb)
			;
			_.elmt.hover(function(){close.show()},function(){close.hide();});
		}
		if( _.elmt.css('position') === 'static'){
			_.elmt.css({position:'relative'});
		}

		if( _.options.props === null ){
			return;
		}

		var i,label,input,o;
		_.propContainer = $('<div><h2>'+_.options.type.replace(/^./,function(m){return m.toUpperCase();})+' widget properties</h2></div>').appendTo('#widgetProperties').attr('rel',_.elmt.prop('id'));

		for( i in _.options.props ){
			o = _.options.props[i];
			label = $('<div class="label"><span>'+($.FBuilder.msg(_.options.type+'-'+i,i))+'</span></div>').appendTo(_.propContainer);
			switch(o.type){
				case 'slider':
					input=$('<input type="text" name="'+i+'" class="range"/>').appendTo(label).css('display','none');
					(function(e,o){
						$('<div></div>').insertAfter(e).slider({
							slide:function(event,ui){e.val(ui.value).change();}
							,value:o.value
							,step:1
							,range:'min'
							,min:o.range[0]
							,max:o.range[1]
						});
					})(input,o);
					break;
				case 'bool':
					input=$('<select name="'+i+'"><option value="0" class="ui-icon-close">false</option><option value="1" class="ui-icon-check">true</option></select>')
						.val(o.value==="1" ?'1':'0')
						.appendTo(label)
						.selectbuttonset({size:'tiny',orientation:'i'})
					;
					break;
				case 'textarea':
					input=$('<textarea name="'+i+'" rows="3"></textarea>').appendTo(label);
					break;
				case 'select':
					input=$('<select name="'+i+'"></select>').appendTo(label);
					if( o.values ){
						$.each(o.values,function(k,v){
							input.append('<option value="'+k+'">'+v+'</option>')
						})
					}
					break;
				case 'text':
				default:
					input=$('<input type="text" name="'+i+'" />').appendTo(label);
					break;
			}
			input.val(o.value);
			if( o.init ){
				o.init.call(input,_.elmt);
			}
			if( o.cb ){
				(function(cb){
					input.change(function(e){ return cb.call(this,e,_.elmt);});
				})(o.cb)
			}
			input.change(function(propName,input){ return function(){_.setProp(propName,input.val());}}(i,input)).change();
		}
		_.elmt.bind('click',function(e){
			e.stopImmediatePropagation();
			var active = $("#widgetProperties > div:visible").attr('rel');
			if( active ){
				$('#'+active).removeClass('FBuilder-activeWidget');
			}
			_.elmt.addClass('FBuilder-activeWidget');
			$('#widgetProperties').scrollTop(0).find('> div').hide();
			_.propContainer.show();
			$('#customWidgetName').val(_.options.custom);
		}).click();
	}
	,remove:function(){
		//first remove all child containers first
		$('.FBuilder-fbContainer',this.elmt).fbWidget('remove');
		//then all child widgets
		$('.FBuilder-fbWidget',this.elmt).fbWidget('remove');
		//finally remove the widget himself
		this.propContainer && this.propContainer.remove();
		return this.elmt.remove();
	}
	,getProp:function(o){
		try{
			return this.options.props[o].value;
		}catch(e){
			return null;
		}
	}
	,getProps:function(o){
		var _=this,res = {};
		$.each(_.options.props || {},function(k,v){ res[k] = _.getProp(k); });
		return res;
	}
	,setProp:function(o,v,change){
		if(! this.options.props[o]){
			this.options.props[o] = $.extend(true,{},$.FBuilder.fbWidget.types[this.options.type].props[o]);
		}
		var isBool = this.options.props[o].type === 'bool' ? true :false
			, i = this.propContainer.find('[name="'+o+'"]')
		;
		v = isBool ? (v=='1'?'1':'0') : v;
		if( v !== this.options.props[o].value ){
			this.propContainer.find('[name="'+o+'"]').val(v);
			if( isBool ){ // activate the ui button display
				i.next('.ui-buttonset').children(':eq('+v+')').click();
			}
			this.options.props[o].value = v;
			if( change )
				i.change();
		}
		return this.elmt;
	}
	,serialize:function(){
		//-- read all widget properties:
		var _=this,res = _.getProps();
		res.type = _.options.type;
		switch(res.type){
			case 'group':
				//get rows
				res.rows = _.elmt.find('.rows > .row').fbWidget('return_serialize');
				break;
			case 'row':
				//get row widget
				res.widgets= _.elmt.find('> .FBuilder-fbWidget').fbWidget('return_serialize');
				break;
			default:
				res.custom = this.options.custom;
				break;
		}
		res.rendered = _.render();
		return res;
	}
	,render:function(){
		var _=this
			,type=_.options.type
			,typeDef=$.extend(true,{},$.FBuilder.fbWidget.types[type])
			,props=_.getProps()
			,setAttrs=function(subj,attrs){
				$.each(attrs.split('|'),function(k,v){if( props[v] ){ subj.attr(v,props[v]) }});
			}
			,out
		;
		// recup du template
		out = $(typeDef.renderTpl || typeDef.tpl).addClass((props['container class'] || '' )+' iType-'+type + (props.disabled==='1' ? ' disabled':''));
		if( typeDef.renderMethod ){
			return  typeDef.renderMethod.call(this,$('<div></div>').append(out).html(),props);
		}
		if( ! type.match(/^(row|group|hidden|info)$/) ){
			$.toolkit._removeClassExp(out,'span*','span'+props.width);
		}
		( props['2lines'] === '1' ) && out.addClass('twolines');
		( props.required  === '1' ) && out.addClass('required');

		switch(type){ // crée les entrées nécessaires
			case 'text':
			case 'hidden':
			case 'textarea':
			case 'datepicker':
				var input = out.find(type==='textarea'?'textarea':'input');
				if( ! input.length ){
					input = out;
				}
				input.prop('id',props.name);
				input.attr('value',props['default'] || '');
				props['input class'] && input.addClass(props['input class']);
				( props.required === '1' ) &&  input.attr('required','required');
				( props.disabled === "1" ) && input.attr('disabled','disabled');
				props.dependOn  && input.attr('rel',props.dependOn);
				if( ! props.pattern ){
					setAttrs(input,'name|placeholder');
				}else{
					input.addClass('tk-validable');
					if( !props.pattern.match(/^@/) ){
						input.addClass('tk-validable-noIcon');
						setAttrs(input,'name|placeholder|pattern');
					}else{
						if( props.pattern.indexOf(':') > 0 ){
							input.attr('rel',props.pattern.replace(/^[^:]+:/,'#'));
							props.pattern = props.pattern.replace(/:.*$/,'');
						}
						input.addClass('tk-validable-noIcon-'+props.pattern.substr(1));
						props.pattern = '';
						setAttrs(input,'name|placeholder');
					}
				}
				break;
			case 'select':
				var s = out.find('select').attr('id',props.name).attr('name',props.name)
					,opts = props.options.replace(/^\s*|\s*$/g,'').split(/\r?\n/)
					,multiple = props['default'].match(',') ? true : false
					,selected = new RegExp('^('+RegEscape(props['default']).replace(',','|')+')$');
				;
				s.addClass(props['input class']);
				( props.required === '1' ) && s.attr('required','required');
				( props.disabled === "1" ) && s.attr('disabled','disabled');
				if( props.parentList ){
					s.attr('rel',props.parentList);
				}else{
					$.each(opts,function(){
						var o = this.replace(/^[^:]*:/,''), v = this.replace(/:.*$/,'');
						s.append('<option value="'+v.replace(/"/g,'\"')+'"'+(v.match(selected)?' selected="selected"':'')+'>'+o+'</option>');
					});
				}
				props.dependOn  && s.attr('rel',props.dependOn);
				break;
			case 'radio':
			case 'checkbox':
				var s = out.find('.check-options,.radio-options').attr('id',props.name)
					,opts = props.options.replace(/^\s*|\s*$/g,'').split(/\r?\n/)
					,selected = new RegExp('^('+RegEscape(props['default']).replace(',','|')+')$');
				;
				if( props['input class'] ){
					s.addClass(props['input class']);
				}
				$.each(opts,function(){
					var o = this.replace(/^[^:]*:/,''), v = this.replace(/:.*$/,'');
					s.append(
						' <label><input type="'+type+'" value="'+v.replace(/"/g,'\"')+'" name="'
						+props.name.replace(/"/g,'\"')+(type==='checkbox' && opts.length > 1?'[]':'')+'"'
						+(v.match(selected)?' checked="checked"':'')
						+(props.disabled==='1'?' disabled="disabled"':'')
						+'/> '+o+'</label>'
					);
				});
				props.dependOn  && s.attr('rel',props.dependOn);
				break;
			case 'paragraph':
				out.find('p').html(props.content);
				break;
			case 'group':
				out = $('<div><h3>'+props.groupName+'</h3></div>');
				break;
			case 'info':
				out.find('p')
					.html(props.content)
					.addClass('span'+props.width)
				;
				break;
		}

		if( props.label ){
			out.find('.label').replaceWith(
				'<label for="'+props.name+'"'+(props['label class']?' class="'+props['label class']+'"':'')+'>'+props.label+'</label>'
			);
		}
		// apply properties
		//- out.find('.label:first').replaceWith('<label>)
		return $('<div></div>').append(out).html();
	}
});
$.FBuilder.fbWidget.defaults = {
	//- tpl
	type:'text'
	,custom:null
	,props:null
};

var isPlaceholderType = function(type){ return type.match(/^(text(area)?|password|datepicker)$/)?true:false; }
	,widgetProperties={
		name:{
			type:'text'
			,value:''
			,cb:function(e,widget){
				var v = $(this).val();
				switch(widget.fbWidget('get1_type')){
					case 'radio':
						widget.find(':radio').attr('name',v);
						break;
					case 'checkbox':
						var checks = widget.find(':checkbox');
						checks.attr('name',checks.length>1?v+'[]':v);
						break;
					case 'text':
					case 'textarea':
					case 'select':
					default:
						widget.find(':input:last').attr('name',v);
						break;
				}
			}
		}
		,content:{
			type:'textarea'
			,value:''
			,cb:function(e,widget){
				var v = $(this).val(), type = widget.fbWidget('get1_type');
				if(! v ){
					v = $.FBuilder.msg('paragraphPlaceHolder');
					$(this).val(v);
				}
				widget.find('p').edip('set_value',v.replace(/<\/?p[^>]*>/g,''));
			}
		}
		,groupName:{
			type:'text'
			,value:'Group'
			,cb:function(e,widget){
				widget.find('legend > div').edip('set_value',$(this).val());
			}
		}
		,noLegend:{
			type:'bool'
			,value:'0'
		}
		,label:{
			type:'text'
			,value:'label'
			,cb:function(e,widget){
				var v = $(this).val(), t=widget.fbWidget('get1_type'),placeHolder = widget.fbWidget('return1_getProp','placeholder');
				widget.find('.label').edip('set_value',v);
				//- if( isPlaceholderType(t) && ! placeHolder ){
					//- widget.fbWidget('setProp','placeholder',v,true);
				//- }
			}
		}
		,'default':{
			type:'text'
			,value:''
			,cb:function(e,widget){
				var type = widget.fbWidget('get1_type'), val = $(this).val();
				switch(type){
					case 'radio':
					case 'checkbox':
						if( val.indexOf(',') < 1){
							widget.find(':'+type).prop('checked',false).filter('[value="'+val+'"]').prop('checked',true);
						}else{
							widget.find(':'+type).prop('checked',false).filter('[value="'+val.replace(",",'"],[value="')+'"]').prop('checked',true);
						}
						break;
					case 'text':
					case 'hidden':
					case 'textarea':
					case 'select':
					default:
						widget.find(':input:last').val(val);
						break;
				}
				//alert(widget.fbWidget('get_type'))
				//widget.find('.label').edip('set_value',$(this).val());
			}
		}
		,placeholder:{
			type:'text'
			,value:''
			,cb:function(e,widget){
				var type= widget.fbWidget('get1_type'), placeholder=widget.fbWidget('return1_getProp','placeholder');
				widget.find(':input:last').prop('placeholder',$(this).val());
			}
		}
		,pattern:{
			type:'text'
			,value:''
		}
		,'container class':{type:'text',value:''}
		,'input class':{type:'text',value:''}
		,'label class':{type:'text',value:''}
		,options:{
			type:'textarea'
			,value:'0:empty option'
			,cb:function(e,widget){
				var val=$(this).val()
					,input = widget.find('select,.check-options,.radio-options')
					,isRadio = input.is('.radio-options')
					// ,isCheck = input.is('.check-options')
				;
				input.children().remove();
				if( input.is('select') ){
					$.each(val.split(/\n/),function(k,v){
						var i=v.indexOf(':');
						input.append(
							i < 0 ?
								'<option value="'+v+'">'+v+'</option>'
								:'<option value="'+v.substr(0,i)+'">'+v.substr(i+1)+'</option>'
						);
					});
				}else{
					$.each(val.split(/\n/),function(k,v){
						var i=v.indexOf(':');
						input.append(
							'<label><input type="'+(isRadio?'radio':'checkbox')
							+'" name="'+(widget.fbWidget('getProp','name')||widget.prop('id'))+(isRadio?'':(val.indexOf('\n')>0?'[]':''))+'" value="'+(i<0?v:v.substr(0,i))+'"/> '
							+(i<0?v:v.substr(i+1))
							+'</label>'
						);
					});
				}
			}
		}
		,required:{
			type:'bool'
			,value:0
			,cb:function(e,widget){
				widget.toggleClass('required',$(this).val() === '1');
			}
		}
		,'2lines':{
			type:'bool'
			,value:0
			,cb:function(e,widget){
				widget.toggleClass('twolines',$(this).val() === '1');
			}
		}
		,width:{
			type:'slider'
			,value:3
			,range:[1,10]
			,init:function(widget){
				var available=10;
				$(this).next('div.ui-slider')
					.bind('slidestop',function(event,ui){
						var available = 10, val = ui.value;
						widget.siblings('.cell').each(function(){
							available -= parseInt($(this).prop('class').replace(/^.*\bspan(\d+)\b.*$/,'$1'));
						});
						$(this).slider('value',Math.min(available,val));
					})
				;
			}
			,cb:function(e,widget){
				//-- get row available size
				var available = 10, val = $(this).val();
				widget.siblings('.cell').each(function(){
					available -= parseInt($(this).prop('class').replace(/^.*\bspan(\d+)\b.*$/,'$1'));
				});
				if( available >= val ){
					$.toolkit._removeClassExp(widget,'span*','span'+val);
				}else{
					$.toolkit._removeClassExp(widget,'span*','span'+available);
					$(this).next('div.ui-slider').slider('value',available);
				}
			}
		}
		,disabled:{
			type:'bool'
			,value:0
			,cb:function(e,widget){
				var type = widget.fbWidget('get1_type'), disable = $(this).val()==="1"?true:false;
				switch(type){
					case 'radio':
					case 'checkbox':
						widget.find(':'+type).disable(disable);
						break;
					case 'text':
					case 'hidden':
					case 'textarea':
					case 'select':
					default:
						widget.find(':input:last').disable(disable);
						break;
				}
			}
		}
		,dependOn:{
			type:'text'
			,value:''
			,cb:function(e,widget){
				if( $(this).val() ){
					widget.fbWidget('return1_getProp','disabled') !== null && widget.fbWidget('setProp','disabled',false,true);
				}
			}
		}
		,parentList:{
			type:'text'
			,value:''
			,cb:function(e,widget){
				if( $(this).val() ){
					widget.fbWidget('return1_getProp','dependOn') !== null && widget.fbWidget('setProp','dependOn','');
					widget.fbWidget('return1_getProp','disabled') !== null && widget.fbWidget('setProp','disabled',0,true);
				}
			}
		}
		,regional:{
			type:'select'
			,values:{
				"af":"Afrikaans"
				,"sq":"Albanian (Gjuha shqipe)"
				,"ar-DZ":"Algerian Arabic"
				,"ar":"Arabic (‫(لعربي"
				,"hy":"Armenian (Հայերեն)"
				,"az":"Azerbaijani (Azərbaycan dili)"
				,"eu":"Basque (Euskara)"
				,"bs":"Bosnian (Bosanski)"
				,"bg":"Bulgarian (български език)"
				,"ca":"Catalan (Català)"
				,"zh-HK":"Chinese Hong Kong (繁體中文)"
				,"zh-CN":"Chinese Simplified (简体中文)"
				,"zh-TW":"Chinese Traditional (繁體中文)"
				,"hr":"Croatian (Hrvatski jezik)"
				,"cs":"Czech (čeština)"
				,"da":"Danish (Dansk)"
				,"nl-BE":"Dutch (Belgium)"
				,"nl":"Dutch (Nederlands)"
				,"en-AU":"English/Australia"
				,"en-NZ":"English/New Zealand"
				,"en-GB":"English/UK"
				,"eo":"Esperanto"
				,"et":"Estonian (eesti keel)"
				,"fo":"Faroese (føroyskt)"
				,"fa":"Farsi/Persian (‫(فارسی"
				,"fi":"Finnish (suomi)"
				,"fr":"French (Français)"
				,"fr-CH":"French/Swiss (Français de Suisse)"
				,"gl":"Galician"
				,"ge":"Georgian"
				,"de":"German (Deutsch)"
				,"el":"Greek (Ελληνικά)"
				,"he":"Hebrew (‫(עברית"
				,"hi":"Hindi (हिंदी)"
				,"hu":"Hungarian (Magyar)"
				,"is":"Icelandic (Õslenska)"
				,"id":"Indonesian (Bahasa Indonesia)"
				,"it":"Italian (Italiano)"
				,"ja":"Japanese (日本語)"
				,"kk":"Kazakhstan (Kazakh)"
				,"km":"Khmer"
				,"ko":"Korean (한국어)"
				,"lv":"Latvian (Latvieöu Valoda)"
				,"lt":"Lithuanian (lietuviu kalba)"
				,"lb":"Luxembourgish"
				,"mk":"Macedonian"
				,"ml":"Malayalam"
				,"ms":"Malaysian (Bahasa Malaysia)"
				,"no":"Norwegian (Norsk)"
				,"pl":"Polish (Polski)"
				,"pt":"Portuguese (Português)"
				,"pt-BR":"Portuguese/Brazilian (Português)"
				,"rm":"Rhaeto-Romanic (Romansh)"
				,"ro":"Romanian (Română)"
				,"ru":"Russian (Русский)"
				,"sr":"Serbian (српски језик)"
				,"sr-SR":"Serbian (srpski jezik)"
				,"sk":"Slovak (Slovencina)"
				,"sl":"Slovenian (Slovenski Jezik)"
				,"es":"Spanish (Español)"
				,"sv":"Swedish (Svenska)"
				,"ta":"Tamil (தமிழ்)"
				,"th":"Thai (ภาษาไทย)"
				,"tj":"Tajikistan"
				,"tr":"Turkish (Türkçe)"
				,"uk":"Ukranian (Українська)"
				,"vi":"Vietnamese (Tiếng Việt)"
				,"cy-GB":"Welsh/UK (Cymraeg)"
			}
			,value:'fr'
			,cb:function(e,widget){
				var lc = $(this).val(), r = $.datepicker.regional[lc] || $.datepicker.regional[''];
				widget.find(':input:last').datepicker('option',r);
				widget.find(':input:last').datepicker('option','dateFormat',widget.fbWidget('return1_getProp','dateFormat')).change();
			}
		}
		,dateFormat:{
			type:'select'
			,values:{
				'dd/mm/yy':'dd/mm/yyyy (FR)'
				,'dd.mm.yy':'dd.mm.yyyy'
				,'dd-mm-yy':'dd-mm-yyyy'
				,'mm/dd/yy':'mm/dd/yyyy (UK)'
				,'mm.dd.yy':'mm.dd.yyyy'
				,'mm-dd-yy':'mm-dd-yyyy'
				,'yy-mm-dd':' yyyy-mm-dd (ISO-8601,RFC-3339)'
				,'yy.mm.dd':' yyyy.mm.dd'
				,'yy/mm/dd':' yyyy/mm/dd'
				,'D, d M y':'shortDay, d shortMonth yy (RFC-822, RFC-1036)'
				,'D, d M yy':'shortDay, d shortMonth yyyy  (RFC-1123, RFC-2822)'
				,'D, dd M yy':'shortDay, dd shortMonth yyyy (cookie)'
				,'DD, dd-M-y':'Day, dd-shortMonth-yy (RFC-850)'
				,'DD, dd MM yy':'Day, dd Month yyyy'
				,'@':'Unix Timestamp'
			}
			,value:'yy-mm-dd'
			,cb:function(e,widget){
				widget.find(':input:last').datepicker("option", "dateFormat",$(this).val());
				widget.fbWidget('setProp','default',widget.find(':input:last').val());
			}
		}
	}
	,makeProps=function(userProps,importProps){ /** {} userProps, string importProps */
		var imports = {};
		//#- importProps = importProps.split('|');
		$.each(importProps.split('|'), function(k,v){imports[v]=widgetProperties[v];});
		return $.extend(true,userProps,imports);
	}
	,dfltDroppedCallback=function(widgetContainer,widgetButton){
		var widget = this;
		$('input,textarea,select',this).change(function(){ // manage default value
			if( widget.fbWidget('getProp','default') ){
				widget.fbWidget('setProp','default',$(this).val());
			}
		}).mousedown(function(){widget.trigger('click');});
		// manage label
		$('.label',this).edip({
			cssReplicant:true
			,change:function(val){ // manage default name
				var type = widget.fbWidget('get1_type');
				if( isPlaceholderType(type) && ! widget.fbWidget('get1_getProp','placeholder')){
					widget.fbWidget('setProp','placeholder',val,true);
				}
				widget.fbWidget('setProp','label',val);
				if( ! widget.fbWidget('return1_getProp','name') ){
					widget.fbWidget('setProp','name',removeAccents(val).replace(/[^a-z0-9_\[\]-]+/ig,'_').toLowerCase());
				}
			}
		});
	}
	,groupTpl= '<fieldset class="FBuilder-fbContainer"><legend><div>Group</div></legend>'
		+'<div class="rows"></div><div class="toolbar">'
		+'<button type="button" class="removeRow">Remove group</button>'
		+'<button type="button" class="addRow">Add row</button>'
		+'</div></fieldset>'
	,groupDropped = function(container,dropped){
		var group = $(this), rows = group.find('.rows');
		group.find('legend > div').edip('set',{ cssreplicant:true, change:function(v){ group.fbWidget('setProp','groupName',v); } });
		rows.fbContainer({
			accept:'.bt-row'
			,connectWith:'#FBuilderWidgetContainer fieldset > .rows'
		});
		group.find('button.addRow').click(function(){
			rows.fbContainer('insert',rowTpl,{dropped:rowDropped,props:rowProps,type:'row'})
		});
	}
	,rowTpl='<div class="row"></div>'
	,rowProps=makeProps({},'container class')
	,rowDropped=function(container,dropped){
		$(this).fbContainer({
			accept:'.bt-field'
			,connectWith:'#FBuilderWidgetContainer fieldset > .rows > .row'
		})
	}
	,radioCheckDropped=function(type){
		return (function(widgetContainer,widgetButton){
			var widget = $(this);
			widget.on('change','.'+type+'-options input',function(){
				var dfltVal = [];
				$(this).parents('.'+type+'-options').find('input:checked').each(function(){
					dfltVal.push($(this).val());
				})
				widget.fbWidget('setProp','default',dfltVal.join(','));
			});
			return dfltDroppedCallback.apply(this,arguments);
		});
	}
;
// exports some vars
window.FBUILDER_groupDropped = groupDropped;
window.FBUILDER_groupTpl = groupTpl;
window.FBUILDER_rowDropped = rowDropped;
window.FBUILDER_rowTpl = rowTpl;
$.FBuilder.fbWidget.types = {
	text:{
		tpl:'<div class="cell span3"><span class="label">Label</span><input type="text" name="" value="" /></div>'
		,connectTo: '.FBuilder-fbContainer .rows .row'
		,dropped:dfltDroppedCallback
		,props:makeProps({},'name|label|placeholder|default|2lines|width|required|disabled|pattern|dependOn|input class|label class|container class')
	}
	,textarea:{
		tpl:'<div class="cell span3"><span class="label">Label</span><textarea name=""></textarea></div>'
		,connectTo: '.FBuilder-fbContainer .rows .row'
		,dropped:dfltDroppedCallback
		,props:makeProps({},'name|label|placeholder|default|2lines|width|required|disabled|pattern|dependOn|input class|label class|container class')
	}
	,select:{
		tpl:'<div class="cell span3"><span class="label">Label</span><select></select></div>'
		,connectTo: '.FBuilder-fbContainer .rows .row'
		,dropped:dfltDroppedCallback
		,props:makeProps({},'name|label|options|parentList|default|2lines|width|required|disabled|dependOn|input class|label class|container class')
	}
	,radio:{
		tpl:'<div class="cell span3"><span class="label">Label</span><span class="radio-options"></span></div>'
		,connectTo: '.FBuilder-fbContainer .rows .row'
		,dropped:radioCheckDropped('radio')
		,props:makeProps({},'name|label|options|default|2lines|width|required|disabled|dependOn|input class|label class|container class')
	}
	,checkbox:{
		tpl:'<div class="cell span3"><span class="label">Label</span><span class="check-options"></span></div>'
		,connectTo: '.FBuilder-fbContainer .rows .row'
		,dropped:radioCheckDropped('check')
		,props:makeProps({},'name|label|options|default|2lines|width|required|disabled|dependOn|input class|label class|container class')
	}
	,datepicker:{
		tpl:'<div class="cell span3"><span class="label">Label</span><input type="text" name="" value="" /></div>'
		,connectTo: '.FBuilder-fbContainer .rows .row'
		,dropped:dfltDroppedCallback
		,props:makeProps({},'name|label|placeholder|default|2lines|width|required|disabled|dependOn|dateFormat|regional|input class|label class|container class')
		,dropped:function(container,dropped){
			dfltDroppedCallback.apply(this,arguments);
			$('input:last',this).datepicker();
			//- this.setProp()
		}
	}
	,hidden:{
		tpl:'<div class="cell span3"><input type="text" name="" /></div>'
		,renderTpl:'<input type="hidden" />'
		,connectTo: '#FBuilderHiddenWidgetContainer'
		,dropped:function(widgetContainer,widgetButton){
			var widget = this;
			$('input,textarea,select',this).change(function(){ // manage default value
				if( widget.fbWidget('getProp','default') ){
					widget.fbWidget('setProp','default',$(this).val());
				}
			}).bind('focus mousedown',function(){widget.trigger('click');});
		}
		,props:makeProps({},'name|default')
	}
	,paragraph:{
		tpl:'<div class="cell span3"><p>qsdq</p></div>'
		,connectTo: '.FBuilder-fbContainer .rows .row'
		,dropped:function(container,dropped){
			var widget = this, p = $('p',widget).css({display:'block',width:'auto',padding:0}).attr('tabindex',0);
			p.edip({
				inputType:'textarea'
				,cssreplicant:true
				,nl2br:true
				,change:function(val){
					widget.fbWidget('setProp','content',val.replace(/<\/?p[^>]*>/g,''));
				}
			})
		}
		,props:makeProps({},'width|input class|container class|content')
	}
	,info:{
		tpl:'<div class="cell span1" style="text-align:center;"><span class="infoIcon">i</span><p class="infoMsg"></p></div>'
		,connectTo: '.FBuilder-fbContainer .rows .row'
		,dropped:function(container,dropped){
			var widget = this, i = $('span',widget), p = $('p',widget).hide(0);
			p
				.edip({
					inputType:'textarea'
					,cssreplicant:true
					,nl2br:true
					,change:function(val){
						widget.fbWidget('setProp','content',val.replace(/<\/?p[^>]*>/g,''));
					}
				})
				.next('textarea').css({position:'absolute',})
				.on('click',':not(a)',function(){ p.hide(); })
			;
			i.click(function(){
				p.toggle();
				p.is(':visible') && p.css({marginLeft:-Math.floor(p.width()/2)});
			});

		}
		,props:$.extend(true,makeProps({},'width|container class|content'),{width:{
				cb:function(e,widget){
					//-- get row available size
					var val = $(this).val();
					$.toolkit._removeClassExp(widget.find('p'),'span*','span'+val);
				}
			}})
	}
	,row:{
		tpl:rowTpl
		,connectTo: '.FBuilder-fbContainer .rows'
		,dropped:rowDropped
		,props:rowProps
	}
	,group:{
		tpl:groupTpl
		,connectTo: '#FBuilderWidgetContainer'
		,dropped:groupDropped
		,props: makeProps({},'groupName|noLegend|container class')
	}
};





$.toolkit('FBuilder.fbWidgetButton',{
	_init:function(){
		var _ = this;
		// extend with default type properties
		_.options = $.extend(true,_.options,$.FBuilder.fbWidget.types[_.options.type]);
		_.elmt.draggable({
			helper:'clone'
			,connectToSortable:_.options.connectTo
			,zIndex:_.options.zIndex
		});
	}
});
$.FBuilder.fbWidgetButton.defaults = {
	type:'text'
	,tpl:'<div></div>'
	,dropped:null
	,connectTo:'.FBuilder-fbContainer'
	,zIndex:10
	,props:{}
};







$.toolkit('FBuilder.listtree',{
	_init:function(){
		this.elmt.on('click','li',function(e){ e.stopImmediatePropagation(); $(this).toggleClass('FBuilder-listtree-collapsed');  })
	}
})
$.FBuilder.listtree.defaults={};




//-- on ready document --//
$(function(){
	$.toolkit.initPlugins('all','FBuilder');
})




})(jQuery);
