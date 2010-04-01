/**
DryCss parser
* @changelog
*            - 2010-03-31 - make deep rule import work from includes
*            - 2010-03-30 - add getDefined method.
*/
dryCss = function(str,options){
	var self = this,
	k='';
	self.dryCss=str;     // the dryCss raw str received at construction time
	self.computedCSS=''; // the computed str
	self.options={};
	self.imported=[];    // external dryCss that are imported and used to compute
	self.imports=[];     // imports rules that will appear at the begining of the computedCss
	self.rules={};
	self._rulesOrder={};
	self.funcs={};       // funcs from imported dryCss + thoose declared in this dryCss
	self.vars={};        // vars from imported dryCss + thoose declared in this dryCss

	//read options
	if( typeof(options) === 'undefined'){
		self.options = dryCss.defaults;
	}else{
		for(k in dryCss.defaults){
			self.options[k] = typeof(options[k])!=='undefined'?options[k]:dryCss.defaults[k];
		}
	}

	self.compute();
}

dryCss.prototype = {

	compute:function(){
		var self=this,
		str = self._cleanStr(self.dryCss);
		// read imports and remove them from the string
		str = self.parseImports(str);
		str = self.parseFuncs(str);
		// read vars definition and remove them from the string too
		str = self.parseVars(str);
		// parse rules in the document
		self.parseRules(str);

		//--declare replacement callbacks
		var replaceCbs = {
			//-- import @:ruleName top level rules, @!:ruleName recursively and @:ruleName[property] values
		 'import':[ // rule import @: and @!:
				/@!?:\s*([^;\n\{\}]+)/g,
				function(m,p){
					var r=false,match;
					p = self.trim(p);
					if( p.indexOf('[') > -1){ // replace @:rules[rule]
						match = p.match(/^(.*)?\s*\[\s*([^\]]+?)\s*\]$/);
						if(null === match){
							self.options.logError('can\'t import inexistant rule '+p);
							return m;
						}
						r = self.lookupRule(match[1]);
						if( false === r){
							self.options.logError('can\'t import inexistant rule property from inexistant rule '+match[1]);
							return m;
						}
						match = r.match(new RegExp(match[2]+'\\s*:\\s*([^\\{\\};]+?)\\s*;'));
						if( null === match){
							self.options.logError('can\'t import inexistant rule property '+p);
							return m;
						}
						return match[1]?match[1]:m;
					}
					if(m.substr(1,1)==='!'){ //- repace @:@mixin
						var id,_id;
						for( id in fullRulesOrder){
							if( id.match(new RegExp('^'+p.replace('.','\\.')+'(?![a-zA-Z0-9_])(.+)$'))){
								_id = id.replace(p,parseKey);
								delayed[_id] = (delayed[_id] ?delayed[_id]:'')+applyCallbacks(self.lookupRule(id));
							}
						}
					}
					r = self.lookupRule(p);
					if( r===false){
						self.options.logError('can\'t import inexistant rule '+p);
						return m;
					}
					return applyCallbacks(r);
			  }
			],
			funcs:[ //function call replacement
				/@=([a-z0-9_]+)\(([^\)]*)\)/ig,
				function(m,func,params){
					if( typeof self.funcs[func] === 'undefined'){
						self.options.logError('Can\'t resolve call to unknown mixin '+func);
						return m;
					}
					var str = self.funcs[func].code;
					params = params.split(/\s*,\s*/);
					for(var i=0,l=self.funcs[func].params.length; i<l; i++){
						if( typeof params[i] === 'undefined' || params[i].match(/^\s*$/) ){
							params[i] = self.funcs[func].params[i][1];
						}
					}
					for( i=0; i<l;i++){
						str=str.replace(new RegExp('@'+self.funcs[func].params[i][0]+'(?=[^a-zA-Z0-9_])','g'),params[i]);
					}

					return applyCallbacks(str);
				}
			],
			//-- replace @varName by the latest defined @varName in the code
			'vars':[ // var replacement
				/@[a-z_][a-z0-9_]*/ig,
				function(m){
					if( typeof self.vars[m] !== 'undefined'){
						return self.vars[m];
					}
					self.options.logError('undefined var '+m);
					return m;
				}
			],
			//-- make correct indentation
			'clean':[
				/([{;])\s*/g,
				self.options.compact?'$1':'$1\n\t'
			]
		},
		delayed={},
		cb,r,i,
		fullRulesOrder=self._getFullRulesOrder(),
		applyCallbacks = function(str){
			if( str.indexOf('@')<0)
				return str.replace(replaceCbs.clean[0],replaceCbs.clean[1]);
			for(cb in replaceCbs ){
				str = str.replace(replaceCbs[cb][0],replaceCbs[cb][1]);
			}
			return str;
		};


		//-- no that we have correctly un-nested all that mess we can execute the rules
		str = self.imports.length?self.imports.join('\n')+'\n':'';
		for(parseKey in self._rulesOrder){
			if( parseKey.match(/^\s*@/)){
				continue;
			}
			r = self.lookupRule(parseKey);
			if(false===r){
				self.options.logError('Undefined rule '+parseKey);
				continue;
			}
			r = applyCallbacks(r);
			if( r ===''){
				continue;
			}
			str += parseKey+(self.options.compact?'{':'{\n\t')+r+(self.options.compact?'}\n':'\n}\n');
			for(i in delayed){
				if( delayed[i] === ''){
					continue;
				}
				if(delayed[i].indexOf('@')>-1){dbg('@found',delayed[i])};
				str += i+(self.options.compact?'{':'{\n\t')+delayed[i]+(self.options.compact?'}\n':'\n}\n');
			}
			delayed = {};
		}
		//-- last cleanup
		self.computedCSS = str.replace(/;(\s*(;))+|\s*^\s*$/mg,'$2');//.replace(/\s*^\s*$/mg,'');
	},
	_getFullRulesOrder:function(){
		var i,id,l=this.imported.length,rulesOrder={};
		for( i=0;i<l;i++){
			for(id in this.imported[i]._rulesOrder){
				rulesOrder[id] = this.imported[i]._rulesOrder[id];
			}
		}
		for(id in this._rulesOrder){
			rulesOrder[id] = this._rulesOrder[id];
		}
		return rulesOrder;
	},
	getDefined:function(){
		var self = this;
		var defined = {vars:[],rules:[],funcs:[]},
			i,y,tmp;
		for(i in self.vars){
			defined.vars.push(i);
		}
		for( i in self.funcs){
			tmp=[];
			for(y=0,l=self.funcs[i].params.length;y<l;y++){
				tmp.push( self.funcs[i].params[y][1]===null?self.funcs[i].params[y][0]:self.funcs[i].params[y].join("="));
			}
			defined.funcs.push('@='+i+'('+tmp.join(',')+')');
		}
		tmp = {};
		for( i in self.imported){
			for( y in self.imported[i]._rulesOrder){
				if( y.indexOf('@')===0 ){
					tmp[y]=null;
				}
			}
		}
		for( y in self._rulesOrder){
			if( y.indexOf('@')===0 )
				tmp[y]=null;
		}
		for( i in tmp){
			defined.rules.push(i);
		}
		return defined;
	},
	lookupRule: function(name){
		var self = this;
		name=name.replace(/\s+/,' ');
		//first look-up for rules in the current instance
		if( typeof self.rules[name] !== 'undefined'){
			return self.rules[name];
		}
		//then in imported rules
		for(var i=0,l=self.imported.length; i<l; i++){
			if( typeof self.imported[i].rules[name] !== 'undefined'){
				return self.imported[i].rules[name];
			}
		}
		//-- here we haven't found any rules with that names fall back to a more complete search
		var ruleExp = new RegExp('(^|,)\s*'+name+'\s*(,|$)');
		for(var rule in self.rules){
			if( rule.match(ruleExp) )
				return self.rules[rule];
		}
		for(var i=0,l=self.imported.length; i<l; i++){
			for(var rule in self.imported[i].rules){
				if( rule.match(ruleExp) )
					return self.imported[i].rules[rule];
			}
		}
		return false;
	},

	/* remove comments and extra lines/spaces */
	_cleanStr:function(str){
		return str.replace(/\/\*[\s\S]*?\*\/|^\s*\/\/.*\n/mg,'') // remove comments
			//- .replace(/^\s*|\s*$/mg,'') // the extra space at start or end of the lines
			.replace(/\s*$\n\s*/mg,'\n') // the extra space at start or end of the lines
			.replace(/([\{\}])/g,'\n$1\n')
			.replace(/\n+/g,'\n');
	},
	_fullKey:function(key,stackKey){
		//-- no parents so leave this as is
		if(! stackKey.length)
			return key;

		if( key.indexOf(',')>0){ //-- key is multiple so make the job for each keys
			key = key.split(/\s*,\s*/);
			for( var i=0,l=key.length,res=[];i<l;i++){
				res.push(this._fullKey(key[i],stackKey));
			}
			return res.join(', ');
		}
		//-- create prefix ( recurse the parent key stack)
		var prefix = stackKey.length? this._fullKey(stackKey[stackKey.length-1],stackKey.slice(0,-1)) : '';
		//-- check if the key must be glued to it's parent
		key = key.replace(/^([:!])?([\s\S]+)$/,function(m,v1,v2){ if(v1){ return v1===':'?m:v2; } return ' '+m;})
			.replace(/\s+/g,' ');
		if( prefix.indexOf(',')<0){
			return prefix+key;
		}
		return prefix.split(/\s*,\s*/).join(key+', ')+key;
	},
	trim:function(str){
		return str.replace(/^\s+|\s+$/,'');
	},
	/**
	process @imports and @!imports rules
	*/
	parseImports:function(str){
		var self = this,
		importUrl='',
		importContent='',
		importContentRaw='';
		while(str.match(/@(!?)import\s+(.*?)\s*(;|$)/m)){
			str = str.replace(/@(!?)import\s+(.*?)\s*(;|$)/mg,function(m,realImport,uri){
				importUrl = uri.match(/^http:\/\//)?uri:self.options.baseImportUrl+uri;
				importContent = self.options.syncXHR(importUrl);
				importContentDry = new dryCss(importContent);
				self.imported.push(importContentDry);
				//-- import the str and make it a new
				if(realImport ==="!" || uri.match(/\.dcss$/i)){
					return importContentDry.toString();
				}else{
					self.imports.push(m);
					return '';
				}
			});
		}
		self.imported.reverse();
		return str;
	},

	/**
	process vars definition
	*/
	parseVars:function(str){
		var self = this,varKey;
		// import vars from imported
		for( var i=0,l=self.imported.length; i<l; i++){
			for( varKey in self.imported[i].vars){
				self.vars[varKey] = self.imported[i].vars[varKey];
			}
		}
		// then read doc vars
		str = str.replace(/\s*(@[a-z_][a-z0-9_]*)\s*:\s*(?:([^;"']*)|("([^"]+|\\")*"|'([^']+|\\')'));/ig,function(m,k,v1,v2){
			if( v1 !== '' ){
				self.vars[k]=v1;
			}else if( v2 != '' ){
				new Function('dry','k','dry.vars[k]='+v2+';')(self,k);
			}
			return '';
		});
		return str;
	},
	/**
	process functions defs
	*/
	parseFuncs:function(str){
		var self = this,funcName;
		// import funcs from imported
		for( var i=0,l=self.imported.length; i<l; i++){
			for( funcName in self.imported[i].funcs){
				self.funcs[funcName] = self.imported[i].funcs[funcName];
			}
		}
		// then read doc funcs
		str = str.replace(/\s*@=([a-z0-9_]+)\s*\(([^\)]*)\)\s*\{([^\}]+|\{[^\}]+\})\}\s*/ig,function(m,f,p,code){
			p = p.replace(/^\s+|\s+$/g).split(/\s*,\s*/);
			var i=0,l=p.length;
			for( i=0,l=p.length; i<l ; i++){
				if( p[i].indexOf('=') < 1 ){
					p[i] = [p[i],null];
					continue;
				}
				p[i] = p[i].split('=');
				p[i] = [p[i][0],p[i].slice(1).join('=')];
			}
			self.funcs[f] = {params:p,code:code};
			return '';
		});
		return str;
	},

	/**
	process rules identifiers
	*/
	parseRules:function(str){
		//- work line by line
		var self = this,
			lines = str.split('\n'),
			l=lines.length,
			parseStr = '',
			parseKey = '',
			stackStr = [],
			stackKey=[];   // pseudo private property to keep trace of the current _stacKey while unnesting the dryCss;

		for(i=0;i<l;i++){
			parseStr += lines[i]+'\n';
			if( parseStr.indexOf('{') > -1){ // look up for an identifier
				match = parseStr.match(/^(\s*|[\s\S]+?[;\}]\s*)([^;\{\}]+)\s*\{([\s\S]*)$/);
				parseKey = self.trim(match[2]);
				parseStr = self.trim(match[3]);
				fullKey = self._fullKey(parseKey,stackKey);
				self._rulesOrder[fullKey]=true;
				stackKey.push(parseKey);
				stackStr.push(self.trim(match[1]));
			}
			endPos = parseStr.indexOf('}');
			if(endPos > -1){ // close current value
				parseKey = stackKey.pop();
				fullKey = self._fullKey(parseKey,stackKey);
				self.rules[fullKey] = (typeof(self.rules[fullKey])!=="undefined"? self.rules[fullKey]+'\n' : '' ) + self.trim(parseStr.substr(0,endPos));
				parseStr = (stackStr.length?stackStr.pop():'')+parseStr.substr(endPos+1);
				continue;
			}
		}
	},
	toString:function(){
		return this.computedCSS;
	}
}
dryCss.defaults={
	baseImportUrl:'./',
	compact:false,
	logError:function(error){
		if(  $ && $.fn.notify ){
			return $('<div class="tk-state-error">'+error+'</div>').notify();
		}else if(typeof console !== 'undefined' && typeof console.debug !== 'undefined' ){
			console.debug('error',error);
		}
	},
	syncXHR:function(url){
		var res ="";
		jQuery.ajax({
			async:false,
			global:false,
			dataType:'text',
			url:url,
			error:function(XHR,status){
				if( typeof(jQuery.tk) !== "undefined" && typeof(jQuery.tk.notify) !== 'undefined' ){
					jQuery('<div class="tk-state-error">'+status+': <br />can\'t import '+url+'</div>').notify();
				}else{
					alert('error while importing '+url+'\n'+status);
				}
			},
			success:function(data){
				res=data;
			}
		});
		return res;
	}
}
