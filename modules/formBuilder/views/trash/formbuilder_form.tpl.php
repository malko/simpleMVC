<style>
.tk-edip{
	cursor:pointer;
	min-width:1em;
	min-height:1em;
	_width:1em;
	_height:1em;
	outline:dashed silver 1px;
}
.tk-edip:hover{
	outline:dashed maroon 1px;

}
</style>
<h1>Form</h1>
<form action="<?php echo $this->url('formbuilder:save'); ?>" method="post" class="adminForm tk-validable-noIcon">

<?php

function stringValues($s){
	return array_combine(explode('|',$s),explode('|',$s));
}

echo $this->modelFormInput("formBuilderForms",'name',array(
	'required'=>true
	,'rule'=>'/^[a-zA-Z0-9_-]+$/'
));

echo $this->modelFormInput("formBuilderForms",'action',array(
	'required'=>true
));

echo $this->modelFormInput("formBuilderForms",'method',array(
	'type'=>'selectbuttonset'
	,'values'=>stringValues('get|post')
));

echo $this->formInput('useActivityPeriod','','checkbox',array(
	'label'=>msg('useActivityPeriod')
));

echo $this->modelFormInput("formBuilderForms",'activityPeriodStart');

echo $this->modelFormInput("formBuilderForms",'activityPeriodEnd');

echo $this->modelFormInput("formBuilderForms",'active');

echo $this->modelFormInput("formBuilderForms",'groupMethod',array(
	'type'=>'selectbuttonset'
	,'values'=>stringValues('none|fieldset|tabs|accordion')
));

$this->js('
	$("#useActivityPeriod").change(function(){
		var enabled = $(this).is(":checked");
		$("#activityPeriodEnd,#activityPeriodStart").disable(!enabled).parent().toggle(enabled);
	}).change();
');
?>




<div id="formGroups"></div>
<div><button type="button" id="addGroup"><?php echo msg("add group"); ?></button></div>
</form>
<script>

lang = 'fr'
langMsgs = {
	fr:{
		addGroup: 'Ajouter un groupe'
		,groupName: 'Nom du groupe'
		,addItem: 'Ajouter un élément'
		,itemLabel: 'Étiquette'
		,itemType: 'Type d\'élément'
	}
}
lmsg=function(id,params){
	try{
		var msg = langMsgs[lang][id]
	}catch(e){
		return id;
	}
	if( msg === undefined )
		return id;
	if( params ){
		$.each(params,function(k,v){
			msg = msg.replace(new RegExp("\b#"+k+'\b','g',v));
		});
	}
	return msg;
};


$('#addGroup').html(lmsg('addGroup'));

(function($){
	var
		groupContainer = $('#formGroups')
		,groupMethodSelector = $('#groupMethod')
		,currentGroupMethod = groupMethodSelector.val()
		,itemTypeOptions = (function(types){
			var res ='';
			$.each(types.split('|'),function(k,v){
				res += '<option value='+v+'>'+lmsg(v)+'</option>';
			});
			return res;
		}('text|hidden|password|select|checkbox|radio|textarea|email'))
		,addFormGroup = function(){
			var group = $('<fieldset>'
				+'<legend><span class="tk-edip tk-edip-legend[]">'+lmsg('groupName')+'</span></legend>'
				+'<div class="intro tk-edip tk-edip-textarea-intro[]" title="write here"></div>'
				+'<div class="inputs"></div>'
				+'<div class="toolbar">'
					+'<button type="button" class="addItem">'+lmsg('addItem')+'</button>'
				+'</div>'
				+'<div class="outro tk-edip tk-edip-textarea-outro[]"></div>'
				+'</fieldset>'
			);
			group.appendTo(groupContainer)
				.find('.addItem')
				.click(addItem)
			;
			$.toolkit.initPlugins('edip','tk',group);
		}
		,addItem = function(){
			var elmt = $(this)
				,fieldset = elmt.parents('fieldset')
				,container = fieldset.find('> .inputs')
				,id = container.children(':last').attr('id')
				,item
			;
			id = (id && id.length) ? parseInt(id.replace(/^inputId-/,''),10) : 1;
			item = $('<div>'
					+'<label>'+lmsg('itemLabel')+': <span class="label tk-edip tk-edip-text-label['+id+']">'+lmsg('itemLabel')+'</span></label> '
					+'<label>'+lmsg('itemName')+': <span class="tk-edip tk-edip-name['+id+']">'+lmsg('itemName')+'</span></label> '
					+'<label>'+lmsg('itemType')+': <select name="itemType['+id+']">'+itemTypeOptions+'</select></label> '
					+'<label><input type="checkbox" name="required['+id+']" value="1"/> required</label> '
					+'<label>default value <span class="tk-edip tk-edip-default['+id+']"></span></label> '
					+'</div>'
				)
			;
			item.appendTo(container);
			$.toolkit.initPlugins('edip','tk',item);

		}
	;

	$('#addGroup').click(addFormGroup);
//----------------------------------------------------------------------------//
	$.toolkit('tk.edip',{
		inputElmt:null
		,_classNameOptions:{
			inputType:'|text|textarea'
			,inputName:'(.*)'
		}
		,_init:function(){
			var self = this;
			if(! self.elmt.attr('tabIndex') ){ // make element tab accessible
				self.elmt.attr('tabIndex',0);
			}
			if( self.options.value === '' ){
				self.options.value = self.elmt.html();
			}
			if( self.options.placeholder==='' && self.elmt.attr('title') ){
				self.options.placeholder = self.elmt.attr('title');
			}
			self._applyOpts('inputType|placeholder');
			if( self.elmt.css('display') === 'inline' ){
				self.elmt.css('display','inline-block');
			}
			self.elmt.bind('focus',function(){ self.toggle(); });
		}
		,_get_input:function(){
			return this.inputElmt[0];
		}
		,_get_value:function(){
			return this.inputElmt.val();
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
				self.set('value',self.inputElmt.val());
				self.toggle();
			})
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
		,_set_value:function(v){ this.inputElmt.val(v); this.elmt.html(v); }
		,toggle:function(visible){
			if( undefined===visible){
				visible = this.elmt.is(':visible');
			}
			this.elmt.toggle(!visible);
			this.inputElmt.toggle(visible);
			if( visible ){
				this.inputElmt.select();
			}
		}
	});

	$.tk.edip.defaults = {
		inputType:'text' // text / textarea ...
		,inputName:''
		,className:''
		,value:''
		,placeholder:''
	}

})(jQuery);

</script>