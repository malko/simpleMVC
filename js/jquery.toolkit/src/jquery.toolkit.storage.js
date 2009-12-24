/**
Client side data storage plugin (extension of jQuery.toolkit
@author jonathan gotti <jgotti at jgotti dot net>
@licence gpl / mit
*/
(function($){

$.toolkit.cookies = {
	enabled:function(){
		if( document.cookie.length){
			return true;
		}else{ //-- try to set a cookie and check it to be sure
			$.toolkit.cookies.set("toolkitCookieTest","1");
			if( $.toolkit.cookies.get("toolkitCookieTest") === "1"){
				$.toolkit.cookies.remove("toolkitCookieTest");
				return true;
			}
		}
		return false;
	},
	set:function(name, value, expirationTime, path, domain, secure){
			var time=new Date();
			if(expirationTime){
					time.setTime(time.getTime()+(expirationTime*1000));
			}
			document.cookie=name+ '='+ escape(value)+ '; '+
			(!expirationTime? '': '; expires='+time.toUTCString())+
			'; path='+(path?path:'/')+ (!domain? '': '; domain='+domain)+ (!secure? '': '; secure');
	},
	get:function(name){
			var re=new RegExp(name+'=([^;]*);?','gi'), result=re.exec(document.cookie)||[];
			return (result.length>1? unescape(result[1]): false);
	},
	remove:function(name, path, domain){
			var value=this.get(name);
			document.cookie=name+ '='+ '; path='+(path?path:'/')+
			(!domain? '': '; domain='+domain)+
			'; expires=Thu, 01-Jan-70 00:00:01 GMT';
			return value;
	}
};

$.toolkit.storage = {
	_storage:null,
	_initialized:false,
	useApi:null,
	testApi:[
		//-- persitant apis --//
		'localStorage',
		'globalStorage',
		'userData',
		'gears',
		'cookies',
		//-- not persitant apis --//
		'sessionStorage',
		'windowName' // be aware that window.name api is not secure at ALL (may be read by any domain whatever the origin is)
	],
	apis:{
		localStorage:{
			iface:'storage',
			test: function(){ return typeof(localStorage) != 'undefined'; },
			init: function(){ return localStorage;},
			persist:true
		},
		globalStorage:{
			iface:'storage',
			test: function(){ return typeof(globalStorage) != 'undefined'; },
			init: function(){ return globalStorage[location.host]; },
			persist:true
		},
		sessionStorage:{
			iface:'storage',
			test: function(){ return typeof(sessionStorage) != 'undefined'; },
			init: function(){ return sessionStorage; },
			persist:false
		},
		userData:{
			iface:'userData',
			test: function(){
				var res = false;
				//test for activeX
				if(! window.ActiveXObject){
					return res;
				}
				//then make a double check by trying to effectively use the feature
				var test = $('<div id="tkStorageTesting" style="display:block;behavior:url(#default#userdata);">test</div>')
					.appendTo('body').get(0);
				//if we can now call the load method we should be ok and a get on empty value should work
				try{
					test.load('tkStorageTest');
					if( test.getAttribute('userDataEnabled') !=='on' ){
						test.setAttribute('userDataEnabled','on');
						test.save('tkStorageTest','on');
					}
					res = true;
				}catch(e){};
				document.body.removeChild(test);
				return res;
			},
			init: function(){
				$('<iframe src="'+$.toolkit.storage.apis.userData.location+'" style="display:none;" id="tkStorageIframe" onload="jQuery.toolkit.storage.apis.userData.init2();"></iframe>')
					.appendTo('body');
				return 'userDataTmp';
			},
			init2: function(){
				var iframe = $('#tkStorageIframe').get(0).contentWindow,
					handler = $('#toolkitStorageHandler',iframe.document);
				if(! handler.length){//- no handler found we add it to the frame markup
					$('body',iframe.document).prepend('<div id="toolkitStorageHandler" style="behavior:url(#default#userData);"></div>');
					handler = $('#toolkitStorageHandler',iframe.document);
				}
				return $.toolkit.storage._storage = handler.get(0);

			},
			persist:true,
			// path to a page to load as an iframe to allow userdatas to be shared accross directories (even a 404 will work)
			location:'/js/jquery.toolkit/jquery.toolkit.storage.userDataFrame.html'
		},
		gears:{
			iface:'db',
			test: function(){ return (window.google && google.gears)?true:false; },
			init: function(){
				var db = google.gears.factory.create('beta.database');
				db.open('toolkitStorage');
				db.execute('CREATE TABLE IF NOT EXISTS toolkitStorage ( key TEXT UNIQUE NOT NULL ,value TEXT NOT NULL);');
				return db;
			},
			persist:true
		},
		cookies:{
			iface:'cookies',
			test: function(){ return $.toolkit.cookies.enabled();	},
			init: function(){ return $.toolkit.cookies; },
			pesistTime:86400*15, // 15 days as a default
			persist:true
		},
		windowName:{
			iface:'name',
			test:function(){return true;},
			init:function(){return window.top;},
			persist:false
		}
	},
	//-- ifaces definitions --//
	knownifaces:{
		storage:{
			set: function(key,value){ this._storage.setItem(key,value); },
			get: function(key){
				if( this.useApi !== 'globalStorage'){
					return this._storage.getItem(key);
				}
				var v = this._storage.getItem(key);
				return ( v && undefined!==v.value )?v.value:v;
			},
			remove:function(key){ this._storage.removeItem(key); }
		},
		db:{
			set: function(key,value){ this.remove(key);this._storage.execute('INSERT INTO toolkitStorage VALUES (?,?)',[key,value]); },
			get: function(key){
				var v = null;
				var rs = this._storage.execute('SELECT value FROM toolkitStorage WHERE key=?',[key]);
				if( rs.isValidRow()){
					v=rs.field(0);
				}
				rs.close();
				return v;
			},
			remove:function(key){ this._storage.execute('DELETE FROM toolkitStorage WHERE key=?',[key]); }
		},
		name:{
			set: function(key,value){
				this.remove(key);
				this._storage.name+=(this._storage.name.length?'&':'')+escape(key)+'='+escape(value);
			},
			get: function(key){
				var r = this._storage.name.match(new RegExp('(^|&)'+escape(key)+'=([^&=]*)($|&)'));
				return r?r[2]:null;
			},
			remove: function(key){ this._storage.name = this._storage.name.replace(new RegExp('(^|&)'+escape(key)+'=([^=&]*)(?=$|&)'),'');	}
		},
		userData:{ // userData storage (for ie5+)
			set: function(key,value){
				this._storage.setAttribute(escape(key),value.toString());
				this._storage.save('toolkitStorageDatas');
			},
			get: function(key){
				this._storage.load('toolkitStorageDatas');
				return this._storage.getAttribute(escape(key));
			},
			remove:function(key){
				this._storage.removeAttribute(escape(key));
				this._storage.save('toolkitStorageDatas');
			}
		},
		cookies:{
			set: function(key,value){	this._storage.set(key,value,this.apis.cookies.pesistTime);	},
			get: function(key){ return this._storage.get(key);},
			remove:function(key){ this._storage.remove(key); }
		}
	},
	_init:function(){
		for(var i=0,api;i<this.testApi.length;i++){
			api = this.apis[this.testApi[i]];
			try{
				if( api.test() ){
					this._storage = api.init();
				}
			}catch(err){
				this._storage=null;
			}
			if( null !== this._storage ){
				this.useApi = this.testApi[i];
				break;
			}
		}
		this._initialized = true;
	},
	enable:function(testApi){
		if( testApi ){
			this.testApi =  (typeof testApi==='string')?testApi.split(/[,\|]/):testApi;
		}
		if(! this._initialized){
			this._init();
		}
		return this._storage !== null?true:false;
	},
	isPersistant:function(){
		if( ! this.useApi){
			return false;
		}
		return this.apis[this.useApi].persist;
	},
	set:function(key,value){
		if( value===undefined || value===''){
			this.remove(key);
		}else{
			this.knownifaces[this.apis[this.useApi].iface].set.apply(this,arguments);
		}
	},
	get:function(){
		var v=this.knownifaces[this.apis[this.useApi].iface].get.apply(this,arguments);
		return ( undefined===v || ''===v )?null:v;
	},
	remove:function(){
		this.knownifaces[this.apis[this.useApi].iface].remove.apply(this,arguments);
	}
};

})(jQuery);