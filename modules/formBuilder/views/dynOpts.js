(function(){
	var registerEvent=function(elmt,event,cb){ // function pour attacher un evenement cross platform
			if( elmt.addEventListener ){
				elmt.addEventListener(event,cb,false);
			}else if( elmt.attachEvent ){
				elmt.attachEvent('on'+event,cb);
			}else{
				elmt['on'+event] = (function(originalHandler){
					return (function(){
						cb.apply(elmt,arguments);
						if( originalHandler ){
							try{ originalHandler.apply(elmt,arguments); }catch(e){};
						}
					});
				}(elmt['on'+event]));
			}
		}
		// function générique pour recuperer un element par l'attribut name
		,getElementsByName = function(elementName){
			if( document.getElementsByName ){
				var elmt =  document.getElementsByName(elementName);
				return elmt ? elmt[0] : elmt;
			}
			for( var i=0,l=document.all.length; i<l; i++ ){
				if( document.all[i].name &&  document.all[i].name === elementName){
					return document.all[i];
				}
			}
			return null;
		}
		,disableElmt = function(elmt,disable){
			disable = (disable || disable===undefined )? true : false;
			elmt.disabled = disable;
			var parent = elmt.parentNode;
			if( parent.className.match(/(radio|check)-options/) ){ // specific case for checkbox and radios
				parent = parent.parentNode;
			}
			if( disable ){
				if(! parent.className.match(/\bdynOptDisabled\b/) ){
					parent.className += " dynOptDisabled";
				}
			}else{
				parent.className = parent.className.replace(/\s*\bdynOptDisabled\b/g,"");
			}
		}
		,enableElmt = function(elmt){ return disableElmt(elmt,false); }
		/**
		*	function générant un callback pour le onchange du parent
		*/
		, dynoptsCB = function(parentElmt,childName,childVals){
			// recup de l'element parent et child
			var childElmt = getElementsByName(childName);

			if( ! (parentElmt && childElmt) ){
				return null;
			}
			return function(event){
				var val = parentElmt.selectedIndex >=0 ? parentElmt.options[parentElmt.selectedIndex].value : null
					,opts = []
					,i,l,k,v
				;
				childElmt.innerHTML = ''; // ici on vire les options actuelles
				if(! childVals[val] ){
					disableElmt(childElmt);
					return null;
				}
				enableElmt(childElmt);
				// on ajoute les nouvelles options
				for( i=0,l=childVals[val].length; i<l ;i++){
					k = childVals[val][i].replace(/:.*$/,'');
					v = childVals[val][i].replace(/^[^:]+:/,'');
					opts.push('<option value="'+k+'">'+v+'</option>');
				}
				childElmt.innerHTML = opts.join('');
			};
		}
	;
	// on expose notre fonction dynOpts en dehors de notre closure
	dynOpts = function(parentName,childName,childValues){
		var parentElmt = getElementsByName(parentName),cb;
		if(! parentElmt ){
			return null;
		}
		cb = dynoptsCB(parentElmt,childName,childValues);
		// si on a un parentElement on register l'evenement
		registerEvent(parentElmt,'change',cb);
		cb(); // on execute directement le callback
	}

	dynDepend = function(childName){
		var elmt = getElementsByName(childName)
			, rel = elmt.getAttribute('rel') || ''
			, depk = rel.replace(/:.*$/,'')
			, depv = rel.replace(/^[^:]*:/,'')
			, depElmt = document.getElementById(depk)
			,cb
		;
		if( depv === depk && depv.length === rel.length ){
			depv=null;
		}
		cb = (function(parent,child,val){return function(){
			var v = parent.value;
			if( val===v || (v!=='' && val===null) ){
				enableElmt(child);
			}else{
				disableElmt(child);
			}
		}})(depElmt,elmt,depv);
		registerEvent(document.getElementById(depk),'change',cb);
		cb();
	}

})();