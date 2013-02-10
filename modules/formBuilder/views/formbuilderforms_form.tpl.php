<h1><?php echo msg('Form builder preparation'); ?></h1>

<?php
	$rteOpts = array(
		'buttonSet'=>'format|class|bold|underline|italic|spacer|orderedList|unorderedList|indent|oudent|spacer|justify|spacer|remove|toggle|link'
		,'classOptions'=> array(
			array('small:small','small')
			,array('big:big','big')
		)
		,'formatOptions' => array(
			array('P', 'Paragraph')
			,array('PRE', 'Preformatted')
			,array('H1','Title 1')
			,array('H2','Title 2')
			,array('H3','Title 3 ')
			,array('H4','Title 4')
			,array('H5','Title 5')
			,array('H6','Title 6')
		)
	);
	$formBuilderPath = smvcModule::getModulePaths('formBuilder')->uri;
	$this->helperLoad('jqueryui');
	$this->helperLoad('button');
	$this->_js_includes(array('js/json2.min.js','js/jquery-ui-i18n.js'));
	$this->_js_includes(array($this->url('forms:index')),true);

	$this->_jqueryToolkit_initPlugins('edip|validable|completionlist');
	$this->_js_includes(array(
		"$formBuilderPath/views/formbuilder.js"
		,"$formBuilderPath/views/formbuilder.css"
	),true);

	//$msg = create_function('$msg,$datas=null','return langManager::msg($msg,$datas,"'.$this->_langManagerDicName.'");');

?>

<form action="#" class="FBuilderForm tk-validable tk-validable-opt-noIcon" method="post">
<?php echo $this->_model_ ? '<input type="hidden" name="formId" value="'.$this->formId.'" id="formId"/>':''; ?>

<div class="row">
	<div class="formInput cell span5">
		<label for="formName"><?php echo msg('labelFormName',$this->dicname); ?></label>
		<input type="text" value="<?php echo $this->name; ?>" name="formName" id="formName" title="no space or special chars." class="tk-validable-req" required="required" pattern="[A-Za-z_0-9-]+"/>
	</div>
	<div class="formInput cell span5">
		<label for="formMethod"><?php echo msg('labelFormMethod'); ?></label>
		<select id="formMethod" name="formMethod">
			<option<?php echo $this->method === 'GET' ?' selected="selected"':''; ?>>GET</option>
			<option<?php echo $this->method==='GET'?'':' selected="selected"'; ?>>POST</option>
		</select>
	</div>
</div>

<div class="row">
	<div class="formInput cell span5">
		<label for="formAction"><?php echo msg('labelFormAction'); ?></label>
		<input type="text" value="<?php echo $this->action; ?>" id="formAction"  name="formAction" required="required" pattern="[^@]+@[^@]+|https?://.+" class="tk-validable-req" />
	</div>
	<div class="formInput cell span5">
		<label for="formTarget"><?php echo msg('labelFormTarget'); ?></label>
		<input type="text" value="<?php echo $this->target; ?>" id="formTarget" name="formTarget"  />
	</div>
</div>

<div class="row">
	<div class="formInput cell span5">
		<label for="cssUri"><?php echo msg('labelCssUri'); ?></label>
		<input type="text" value="<?php echo $this->cssUri; ?>" id="cssUri"  name="cssUri" />
	</div>
	<div class="formInput cell span5">
		<label for="jsUri"><?php echo msg('labelJsUri'); ?></label>
		<input type="text" value="<?php echo $this->jsUri; ?>" id="jsUri"  name="jsUri" />
	</div>
</div>

<div class="row">
	<div class="formInput cell span5">
		<label for="activityPeriodStart"><?php echo msg('labelActivityPeriodStart'); ?></label>
		<input type="text" value="<?php echo $this->activityPeriodStart; ?>" id="activityPeriodStart"  name="activityPeriodStart" />
	</div>
	<div class="formInput cell span5">
		<label for="activityPeriodEnd"><?php echo msg('labelActivityPeriodEnd'); ?></label>
		<input type="text" value="<?php echo $this->activityPeriodEnd; ?>" id="activityPeriodEnd"  name="activityPeriodEnd" />
	</div>
</div>

<div class="row">
	<div class="formInput cell span5">
		<label for="active"><?php echo msg('labelActive'); ?></label>
		<select name="active" id="active" >
			<option value="0" ><?php echo msg('no'); ?></option>
			<option value="1"<?php echo $this->active?' selected="selected"':'' ?>"><?php echo msg('yes'); ?></option>
		</select>
	</div>
	<div class="formInput cell span5" style="text-align: right;">
		<label for="formGrouping" style="width:auto;"><?php echo msg('labelFormGrouping'); ?></label>
		<select id="formGrouping" name="formGrouping">
			<option value="fieldsets"<?php echo $this->groupMethod!=='fieldsets'?'':' selected="selected"'; ?>><?php echo msg('groupingFieldsets'); ?></option>
			<option value="tabs"<?php echo $this->groupMethod==='tabs'?' selected="selected"':''; ?>><?php echo msg('groupingTabs'); ?></option>
			<option value="accordions"<?php echo $this->groupMethod==='accordions'?' selected="selected"':''; ?>><?php echo msg('groupingAccordions'); ?></option>
		</select>
	</div>
</div>

<div class="row">
	<div class="cell span5">
		<label for="SubmitButtonLabel"><?php echo msg('labelSubmitButtonLabel'); ?></label>
		<input type="text" name="submitButtonLabel" id="submitButtonLabel" value="Submit" />
	</div>
	<div class="cell span5">
		<label for="resetButtonLabel"><?php echo msg('labelResetButtonLabel'); ?></label>
		<input type="text" name="resetButtonLabel" id="resetButtonLabel" value="Reset" />
	</div>
	<!--div class="formInput cell span5">
		<label for="submitlabel"><?php echo msg('labelSubmitLabel'); ?></label>
		<input type="text" name="submitlabel" id="submitlabel" value="" />
	</div-->
</div>

<div class="row">
	<div class="cell span10">
		<h2 class="tk-edip tk-edip-replicant-text" id="formTitle"><?php echo msg('Put title here'); ?></h2>
	</div>
</div>

<div class="row">
	<?php echo $this->formInput('htmlIntro',$this->htmlIntro,'rte',array(
		'style'=>'height:200px;'
		,'label'=>msg('labelHtmlIntro')
		,'formatStr'=>'<div class="formInput cell span10 rte">%label<br />%input</div>'
		,'rteOpts'=>$rteOpts
	)); ?>
</div>

<div class="row tk-fixedAtTop" id="FBuilderFixedTools">

	<div class="cell span2 widgetButtons">
		<h2><?php echo msg('widgets'); ?></h2>
		<!--span class="bt-widget bt-group">group</span>
		<span class="bt-widget bt-row">row</span-->
		<span class="bt-widget bt-field"><?php echo msg('text'); ?>             </span>
		<span class="bt-widget bt-field"><?php echo msg('textarea'); ?>         </span>
		<span class="bt-widget bt-field"><?php echo msg('select'); ?>           </span>
		<span class="bt-widget bt-field"><?php echo msg('checkbox'); ?>         </span>
		<span class="bt-widget bt-field"><?php echo msg('radio'); ?>            </span>
		<!--span class="bt-widget bt-hidden"><?php echo msg('hidden'); ?>           </span-->
		<span class="bt-widget bt-field"><?php echo msg('paragraph'); ?>        </span>
		<!--span class="bt-widget bt-field"><?php echo msg('password'); ?>         </span-->
		<span class="bt-widget bt-field"><?php echo msg('datepicker'); ?>       </span>
		<span class="bt-widget bt-field"><?php echo msg('info'); ?>       </span>
	</div>

	<div class="cell span8" id="customWidgetContainer">
		<h2><?php echo msg('customWidgets'); ?></h2>
		<div class="widgets"></div>
		<div class="label">
			<?php echo msg('save current widget as'); ?>
		<br />
		<input type="text" id="customWidgetName" value="">
			<button type="button" class="ui-button ui-button-small-i-disk" id="customWidgetSave"><?php echo msg('save'); ?></button>
		</div>
	</div>

	<div class="cell span2" id="widgetProperties">

	</div>


</div>

<div class="row">
	<div class="cell span10">
		<div id="FBuilderHiddenWidgetContainer">
			<h2><?php echo msg('Hidden fields'); ?></h2>
			<button type="button" class="bt-hidden" id="addHidden" title="<?php echo msg('add hidden field'); ?>"><?php echo msg('add hidden field'); ?></button>
		</div>
		<div id="FBuilderWidgetContainer">

		</div>
		<div style="text-align: right">
			<button type="button" class="addGroup"><?php echo msg('Add new group'); ?></button>
		</div>

		<div class="row">
			<?php echo $this->formInput('htmlOutro',$this->htmlOutro,'rte',array(
				'style'=>'height:200px;'
				,'label'=>msg('labelHtmlOutro')
				,'formatStr'=>'<div class="formInput cell span10 rte">%label<br />%input</div>'
				,'rteOpts'=>$rteOpts
			)); ?>
		</div>

		<div style="text-align: right">
			<button type="button" class="preview"><?php echo msg('preview'); ?></button>
			<button type="button" class="save picto-enter"><?php echo msg('Save'); ?></button>
		</div>
	</div>
</div>

</form>


<script type="text/javascript">
function formNameValidation(v,e){
	if(! v.match(/^[A-Za-z_0-9-]+$/) ){
		return false;
	}
	return true;
}

$('#formName').change(function(){
	// then checked for valid form name
	var e = $(this),v=e.val();
	window.forms.formNameExists([v,$('#formId').val()],function(res){
		if( res.code !== undefined){
			return false;
		}
		if( res > 0 && v === e.val() ){ // check again e.val() in case it has change before we get the response
			$.tk.notify.msg("form name already exists please choose another one.",{state:'error'});
			e.val(v+'*').validable('getState');
		}
	});
});
$(function(){
 $('#test1').fixedAtTop();
 var regional = $.datepicker.regional["<?php echo langManager::getCurrentLang() !== 'en'?langManager::getCurrentLang():''; ?>"];
 $('#activityPeriodStart,#activityPeriodEnd').datepicker($.extend({},regional,{dateFormat:'yy-mm-dd'}));
 $('#active').selectbuttonset();
$.FBuilder.langMsg.<?php echo langManager::getCurrentLang(); ?>.groupLegend = "<?php echo msg('Group'); ?>";
$.FBuilder.langMsg.<?php echo langManager::getCurrentLang(); ?>.groupRemove = "<?php echo msg('Remove group'); ?>";
$.FBuilder.langMsg.<?php echo langManager::getCurrentLang(); ?>.groupAddRow = "<?php echo msg('Add row'); ?>";
$.FBuilder.langMsg.<?php echo langManager::getCurrentLang(); ?>.paragraphPlaceHolder = "<?php echo msg('Type your text here'); ?>";
$.FBuilder.langMsg.<?php echo langManager::getCurrentLang(); ?>.dependOn = "<?php echo msg('dependOn'); ?>";
$.FBuilder.langMsg.<?php echo langManager::getCurrentLang(); ?>.parentList = "<?php echo msg('parentList'); ?>";
/** Managge ui nested draggable bug in ie ** /
$.extend($.ui.draggable.prototype, (function (orig) {
	return {
    _mouseCapture:function (event) {
      var result = orig.call(this, event);
      event.stopImmediatePropagation();
			setTimeout(function(){ alert(event.type); },1000)
      return result;
    }
  };
})($.ui.draggable.prototype["_mouseCapture"]));*/


	$('#formMethod,#formGrouping').selectbuttonset({size:"tiny"})//.next('.ui-buttonset').css({marginLeft:'.0em'});

	$('#FBuilderFixedTools').fixedAtTop();
	var iconsPath = '<?php echo $formBuilderPath; ?>/views/elements/actions'
		,widgetContainer = $('#FBuilderWidgetContainer')
		,hiddenContainer = $('#FBuilderHiddenWidgetContainer')
		,refreshCutomWidgets=function(){
			$.getJSON('<?php echo $this->url('formbuilderforms:getCustomWidgets'); ?>',function(data,status,XHR){
				var container = $('#customWidgetContainer .widgets');
				container.children().remove();
				$.each(data,function(k,widgetprops){
					var type = widgetprops.type,typeProps=$.FBuilder.fbWidget.types[type].props,props={};
					$.each(typeProps,function(k,v){
						props[k] = v;
						props[k].value = widgetprops[k] || v.value;
					});
					$('<span class="bt-widget bt-field" title="'+type+'">'+widgetprops.customName+'</span>' ).appendTo(container)
					.button({orientation:'r',icon:iconsPath+'/add'+(type.replace(/^[a-z]/,function(a){return a.toUpperCase();}))+'.png'})
						.fbWidgetButton({type:type,props:props,custom:widgetprops.customName})
					;
				});
			});

		}
		,extractWidgetProps = function(widget,props){
			var wprops = $.extend(true,{},$.FBuilder.fbWidget.types[widget.type].props);
			$.each(wprops,function(propName,propObj){
				if( widget[propName] !== undefined ){
					wprops[propName].value = widget[propName];
				}
			});
			return wprops;
		}
	;


	/***** PREPARE WIDGET BUTTONS *****/
	$('#FBuilderFixedTools .bt-widget')
		.each(function(){
			var b = $(this)
			;
			if( ! b.attr('title') ){
				b.prop('title',b.text().replace(/^\s+|\s+$/g,''));
			}
			switch(b.text().replace(/^\s+|\s+$/g,'')){
				case 'text':
					b.button({orientation:'i',icon:iconsPath+'/addText.png'})
						.fbWidgetButton({type:'text'})
					;
					break;
				case 'textarea':
					b.button({orientation:'i',icon:iconsPath+'/addArea.png'})
						.fbWidgetButton({type:'textarea'})
					;
					break;
				case 'select':
					b.button({orientation:'i',icon:iconsPath+'/addSelect.png'})
						.fbWidgetButton({type:'select'})
					;
					break;
				case 'radio':
					b.button({orientation:'i',icon:iconsPath+'/addRadio.png'})
						.fbWidgetButton({type:'radio'})
					;
					break;
				case 'checkbox':
					b.button({orientation:'i',icon:iconsPath+'/addCheckbox.png'})
						.fbWidgetButton({type:'checkbox'})
					;
					break;
				case 'password':
					b.button({orientation:'i',icon:iconsPath+'/addPass.png'});
					break;
				case 'datepicker':
					b.button({orientation:'i',icon:iconsPath+'/addDate.png'})
						.fbWidgetButton({type:'datepicker'})
					;
					break;
				/*case 'hidden':
					b.button({orientation:'i',icon:iconsPath+'/addHidden.png'})
						.fbWidgetButton({type:'hidden'})
					;
					break;
				*/
				case 'paragraph':
					b.button({orientation:'i',icon:iconsPath+'/addParagraphe.png'})
						.fbWidgetButton({type:'paragraph'})
					;
					break;
				case 'info':
					b.button({orientation:'i',icon:iconsPath+'/addInfo.png'})
						.fbWidgetButton({type:'info'})
					;
					break;
				/*case 'row':
					b.button({orientation:'i',icon:iconsPath+'/resizerow.png'})
						.fbWidgetButton({type:'row'})
					;
					break;
				case 'group':
					b.button({orientation:'i',icon:iconsPath+'/frame_edit.png'})
						.fbWidgetButton({type:'group'})
					;
					break;
				*/
			}
		})
		.click(function(e){e.preventDefault(e);})
		.find('img').attr('draggable',false)
	;
	widgetContainer
		.fbContainer({
			accept:'.bt-group'
		})
	;
	hiddenContainer.fbContainer({accept:'.bt-hidden'});

	// bind addGroup button to add a group and then a row
	$('button.addGroup')
		.click(function(){
			var group = widgetContainer.fbContainer(
				'return1_insert'
				,FBUILDER_groupTpl
				,{
					dropped:FBUILDER_groupDropped
					,type:'group'
					,connectTo:$.FBuilder.fbWidget.types.group.connectTo
					,props:$.extend(true,{},$.FBuilder.fbWidget.types.group.props)
				}
			);
			group.find('button.addRow').click();
		})
	;
	// bind addHidden button
	$('#addHidden').click(function(){
		hiddenContainer.fbContainer('insert',$.FBuilder.fbWidget.types.hidden.tpl,{
			type:'hidden'
			,connectTo: '#FBuilderHiddenWidgetContainer'
			,dropped:$.FBuilder.fbWidget.types.hidden.dropped
			,props:$.extend(true,{},$.FBuilder.fbWidget.types.hidden.props)
		});
	}).button({orientation:'i',icon:iconsPath+'/addHidden.png'});


	// $('#formName').validable({required:true,rule:'alpha'})
	$('form').validable();




	/***** SAVE AND PREVIEW *****/
	$('button.save,button.preview').click(function(){

		//first get form parameters
		var form = {
				name:$('#formName').val()
				,method:$('#formMethod').val()
				,action:$('#formAction').val()
				,target:$('#formTarget').val()
				,groupMethod:$('#formGrouping').val()
				,activityPeriodStart:$('#activityPeriodStart').val()
				,activityPeriodEnd:$('#activityPeriodEnd').val()
				,hiddens:hiddenContainer.find('.FBuilder-fbWidget').fbWidget('return_serialize')
				,groups:$('.FBuilderForm fieldset').fbWidget('return_serialize')
				,title:$('#formTitle').edip('get1_value').replace(/<|>/,function(a){ return a=='<' ? '&lt;':'&gt;' ;})
				,submitLabel:$('#submitButtonLabel').val() || 'Submit'
				,resetLabel:$('#resetButtonLabel').val()
				,rendered:''
			}
			,intro=$('#htmlIntro').val()
			,outro=$('#htmlOutro').val()
			,needValidable=false
			,needPlaceholder=false
			,needDynOpts=false
			,needQueryString = false
			,hasInfo = false
			,datepickers = []
			,dynOptsCB=[]
			,js=[]
			,concatGroupContent = function(group,groupId){
				var res = $('<div class="rows"></div>');
				if( groupId ){
					res.attr('id',groupId);
				}
				$.each(group.rows,function(rId,rowObj){
					var row = $(rowObj.rendered);
					row.appendTo(res);
					$.each(rowObj.widgets || [],function(wId,widgetObj){
						if( widgetObj.placeholder ){ needPlaceholder=true; }
						if( widgetObj.pattern || widgetObj.required==='1' ){ needValidable=true; }
						if( widgetObj.parentList ){ // manage list dependencies
							needDynOpts  = true;
							var tmp = widgetObj.options.split(/\r?\n/g)
								,options={}
								,i,l,parentK,optionV
							;
							for( i=0,l=tmp.length; i<l; i++ ){
								parentK = tmp[i].replace(/:.*$/,''),optionV = tmp[i].replace(/^[^:]+:/,'');
								options[parentK] ? options[parentK].push(optionV) : (options[parentK]=[optionV]);
							}
							dynOptsCB.push('dynOpts("'+widgetObj.parentList+'","'+widgetObj.name+'",'+JSON.stringify(options)+');');
							//- js.push('dynOpts("'+widgetObj.parentList+'","'+widgetObj.name+'",'+JSON.stringify(options)+');');
						}else if( widgetObj.dependOn ){
							dynOptsCB.push('dynDepend("'+widgetObj.name+'");');
						}
						switch(widgetObj.type){
							case 'datepicker':
								datepickers.push(
									"$('#"+widgetObj.name+"').datepicker($.extend({},$.datepicker.regional['"+(widgetObj.regional)+"'],{dateFormat:'"+widgetObj.dateFormat+"'}));"
								);
								break;
							case 'info':
								hasInfo = true;
								break;
						}
						row.append(widgetObj.rendered);
					});
				});
				return $('<div></div>').append(res).html();
			}
		;

		//-- manage default formAction
		if( form.action.match(/^(|[^@\/]+@[^@\/]+)$/) ){
			form.action = "<?php
				echo (defined('FORM_BUILDER_DEFAULTSUBMIT') && FORM_BUILDER_DEFAULTSUBMIT)? FORM_BUILDER_DEFAULTSUBMIT : $this->url('forms:result',array('%formName'),true,null,false);
			?>";
		}
		form.action = form.action.replace(/%formName/,form.name);
		//if( $(this).is('.preview') ){
			form.rendered = ($('#cssUri').val()?'<link rel="stylesheet" type="text/css" href="'+$('#cssUri').val()+'" />':'')
				+'<form method="'+form.method+'" action="'+form.action+'"'+(form.target?' "target="'+form.target+'"':'')+' id="form'+form.name+'" class="formbuilder grid">'
				+(function(){ // get hidden fields and manage getFields
					var o = []; $.each(form.hiddens,function(k,v){
						o.push(v.rendered);
						if( v['default'].match(/^\$/) ){
							//--
							if(! needQueryString ){
								needQueryString = true;
								js.push('var QueryStringParams = (function(){ '
								+'var o={},h = window.location.href;'
								+'if( h.match(/\\?/) ){'
								+'h=h.replace(/^[^\\?]*\\?/,"").split("&");'
								+'$.each(h,function(k,v){ v = v.split("=",2);o[unescape(v[0])] = unescape(v[1]) });'
								+'} return o;}());');
							}
							js.push('$("#'+v.name+'").val(QueryStringParams["'+v['default'].substr(1)+'"] || "");');
						}
					}); return o.join('');
				}())
				+(form.title? '<h1 class="formTitle">'+form.title+'</h1>':'')
				+(intro? '<div class="row"><div class="cell span10 intro">'+intro+'</div></div>':'')
			;
			switch(form.groupMethod){
				case 'fieldsets':
					$.each(form.groups,function(gId,group){
						form.rendered += '<fieldset'+(group['container class']?' class="'+group['container class']+'"':"")+'>'
							+(group.noLegend!=='1'?'<legend>'+group.groupName+'</legend>':'')+concatGroupContent(group)+'</fieldset>'
						;
					});
					break;
				case 'tabs':
					var titles = [],rows=[];
					$.each(form.groups,function(gId,group){
						titles.push('<li><a href="#form'+form.name+'-tab-'+gId+'">'+group.groupName+'</a></li>');
						rows.push(concatGroupContent(group,'form'+form.name+'-tab-'+gId));
					});
					form.rendered += '<div class="groups"><ul>'+titles.join('\n\t')+'</ul>'+rows.join('\n')+'</div>';
					js.push('ljs.load("<?php echo ROOT_URL; ?>/js/css/base/ui.all.css","<?php echo ROOT_URL; ?>/js/jquery-ui.min.js",function(){$("#form'+form.name+' > div.groups").tabs();});');
					break;
				case 'accordions':
					var rows=$('<div></div>'),titles=[];
					$.each(form.groups,function(gId,group){
						rows.append('<h3><a href="#form'+form.name+'-accordion-'+gId+'">'+group.groupName+'</a></h3>'+concatGroupContent(group,'form'+form.name+'-accordion-'+gId))
					});
					form.rendered += '<div class="groups">'+rows.html()+'</div>';
					js.push('ljs.load("<?php echo ROOT_URL; ?>/js/css/base/ui.all.css","<?php echo ROOT_URL; ?>/js/jquery-ui.min.js",function(){$("#form'+form.name+' > div.groups").accordion();});');
					break;
			}

			form.rendered += '<div class="row"><div class="cell span10" style="text-align:center">'
				+(form.resetLabel ? '<button type="reset">'+form.resetLabel+'</button>' : '' )
				+'<button type="submit">'+form.submitLabel+'</button></div></div>'
				+(outro? '<div class="row"><div class="cell span10 outro">'+outro+'</div></div>':'')
				+'</form>'
				+($('#jsUri').val()?'\n<script src="'+$('#jsUri').val()+'"></'+'script>':'')
			;
			//form.rendered += '<button type="submit">Submit</button></form>';
			/*if( needValidable || needPlaceholder ){
				js.push('ljs.aliases.tk = ["<?php echo ROOT_URL; ?>/js/jquery.toolkit/src/jquery.toolkit.css","<?php echo ROOT_URL; ?>/js/jquery.toolkit/src/jquery.toolkit.js"];');
			}*/
			if( dynOptsCB ){
				js.push('ljs.load("<?php echo "$formBuilderPath/views/dynOpts.".(DEVEL_MODE_ACTIVE()?'js':'min.js'); ?>",function(){'+dynOptsCB.join(' ')+'});')
			}
			if( needValidable ){
				js.push(([
					'ljs.load('
						,'"validable"'
						,',function(){'
						,'$("form").addClass("tk-validable tk-validable-noIcon");'
						,'$.toolkit.initPlugins("position|tooltip|validable","tk");'
						,'});'
					]).join('\n\t')
				);
			}
			if( needPlaceholder ){
				js.push(
					'ljs.load(\n\t'
						+'"tk"\n\t'
						//+',"<?php echo ROOT_URL; ?>/js/jquery.toolkit/src/plugins/placeholder/jquery.tk.placeholder.css"\n\t'
						+',"<?php echo ROOT_URL; ?>/js/jquery.toolkit/src/plugins/placeholder/jquery.tk.placeholder.js"\n\t'
						+',function(){\n\t\t'
						+'$("input[placeholder],textarea[placeholder]").addClass("tk-placeholder");\n\t\t'
						+'$.toolkit.initPlugins("placeholder","tk");\n\t'
						+'}\n);'
				);
			}
			if( datepickers.length ){
				js.push(([
					'\nljs.load('
						,'"<?php echo ROOT_URL; ?>/js/jquery-ui.js"'
						,',"<?php echo ROOT_URL; ?>/js/jquery-ui-i18n.js"'
						,',function(){'
						,'\t'+datepickers.join('\n\t\t')
						,'});'
					]).join('\n\t')
				)
			}
			if( hasInfo ){
				js.push(
					'$(".iType-info span.infoIcon").each(function(){\n\t'
					+'var i=$(this),p=i.next("p").hide();\n\t'
					+'i.click(function(){ p.toggle(); p.is(":visible") && p.css({marginLeft:-Math.floor(p.width()/2)}); });\n'
					+'})'
				);
			}
		//}

		if( $(this).is('.preview') ){
			var w = window.open('test','','toobar=no,scrollbars=yes');
			w.document.write(
				'<html><head>'
				//+'<link rel="stylesheet" type="text/css" href="<?php echo $formBuilderPath; ?>/views/formbuilder.css" />'
				+'<link rel="stylesheet" type="text/css" href="<?php echo $formBuilderPath; ?>/views/formbuilder.css" />'
				+'<link rel="stylesheet" type="text/css" href="<?php echo ROOT_URL.'/'.FRONT_NAME.'.css'; ?>" />'
				+'<link rel="stylesheet" type="text/css" href="<?php echo ROOT_URL; ?>/js/css/base/ui.all.css" />'
				+'\n<script src="<?php echo ROOT_URL; ?>/js/jquery-1.7.1.min.js"></'+'script>'
				+'\n<script src="<?php echo ROOT_URL; ?>/js/jquery-ui.js"></'+'script>'
				+'\n<script src="<?php echo ROOT_URL; ?>/js/l.js/l.js?checkLoaded">\n\tljs.load("<?php echo ROOT_URL; ?>/js/ljs.aliases.js#ljsAliases",function(){\n\t\t'
				+js.join('\n\t\t')+'\n\t});\n</'+'script>'
				+'</head><body><div class="grid">'
			);
			w.document.write(form.rendered);
			//w.document.write('</div><textarea>'+JSON.stringify(form)+'</textarea><script>'+js.join('\n')+'</'+'script></body></html>');
			w.document.write('</div><button onclick="javascript:window.close();" style="margin:auto;">close</button></body></html>');
			w.document.close();
		}else{
			var origForm = $('form.FBuilderForm'); formElmt = origForm.clone();
			$.each(('formGrouping|formMethod|htmlIntro|htmlOutro|submitText|jsUri|cssUri').split('|'),function(k,v){ // import value
				formElmt.find('#'+v).val($('#'+v,origForm).val());
			});
			formElmt.find('#FBuilderHiddenWidgetContainer,#FBuilderWidgetContainer,#FBuilderFixedTools').remove();
			$('<textarea name="html"></textarea>').appendTo(formElmt)
				.val(
					form.rendered
					//+(needDynOpts?'\n<script src="<?php echo "$formBuilderPath/views/dynOpts.".(DEVEL_MODE_ACTIVE()?'js':'min.js'); ?>"></'+'script>':'')
					+'\n<script src="<?php echo ROOT_URL; ?>/js/l.js/l.js?checkLoaded">'
					+'\n\tljs.load("<?php echo ROOT_URL; ?>/js/ljs.aliases.js#ljsAliases",function(){\n\t\t'+js.join('\n\t\t')
					+'\n\t});\n</'+'script>'
					//+'\n<script>\n\t'+js.join('\n\t')+'\n</'+'script>'
				)
			;
			delete form.rendered;
			$('<input type="text" name="rawData" />').appendTo(formElmt).val(JSON.stringify(form));
			formElmt
				.attr('action','<?php echo $this->actionUrl; ?>')
				.hide()
				.appendTo('body')  // must append to dom to get it submitted in firefox
				.submit()
			;
		}
	});

	/***** SAUVEGARDE D'UN WIDGET *****/
	$('#customWidgetSave').click(function(){
		//first lookup the active widget
		var active = $('#widgetProperties > div:visible')
			,customName=$('#customWidgetName').val().replace(/^\s+|\s+$/g,'')
		;
		if(! ( active.length && customName.length) ){
			$.tk.notify.msg(customName?'select a widget first':'You must specify a custom widget name',{state:'warning'});
			return false;
		}
		// get corresponding widget
		var widget = $("#"+active.attr('rel'));
		if(! widget.length ){
			$.tk.notify.msg('There\'s no active widget',{state:'warning'});
			return false;
		}

		var wType = widget.fbWidget('get1_type');
		if( wType === 'group' || wType === 'row'){
			$.tk.notify.msg('Groups and rows can\'t be saved',{state:'warning'});
			return false;
		}
		var postData = widget.fbWidget('return1_serialize');
		postData.customName = customName;
		delete postData.rendered;
		$.post(
			"<?php echo $this->url('formbuilderforms:saveCustomWidget'); ?>"
			,postData
			,function(data,status,XHR){ // success
				if( data.match(/^error:/) ){
					$.tk.notify.msg(data,{state:'error'});
				}
				refreshCutomWidgets();
			}
		)
	});
	refreshCutomWidgets();


	/**** INITIALIZE FORM *****/
	rawData = <?php echo $this->rawData?$this->rawData:"false"; ?>;
	FBuilderINIT = function(){
		if(! rawData ){
			$('button.addGroup').click();
		}else{ // try to restore previously saved form
			$('#formTitle').edip('set_value',rawData.title);
			$('#submitButtonLabel').val(rawData.submitLabel || 'Submit');
			$('#resetButtonLabel').val(rawData.resetLabel);
			$.each(rawData.hiddens,function(k,hidden){
				hiddenContainer.fbContainer('insert',$.FBuilder.fbWidget.types.hidden.tpl,{
					type:'hidden'
					,connectTo: '#FBuilderHiddenWidgetContainer'
					,dropped:$.FBuilder.fbWidget.types.hidden.dropped
					,props:extractWidgetProps(hidden)
				});
			});
			$.each(rawData.groups,function(k,group){
				var gElmt = widgetContainer.fbContainer('return1_insert',FBUILDER_groupTpl,{
						dropped:FBUILDER_groupDropped
						,connectTo:$.FBuilder.fbWidget.types.group.connectTo
						,type:'group'
						,props:extractWidgetProps(group)
					})
					,rows = gElmt.find('.rows')
				;
				$.each(group.rows,function(k,row){
					var rElmt = rows.fbContainer('return1_insert',FBUILDER_rowTpl,{
							dropped:FBUILDER_rowDropped
							,connectTo:$.FBuilder.fbWidget.types.row.connectTo
							,type:'row'
							,props:extractWidgetProps(row)
						})
					;
					$.each(row.widgets,function(k,widget){
						rElmt.fbContainer('return1_insert',$.FBuilder.fbWidget.types[widget.type].tpl,{
								dropped:$.FBuilder.fbWidget.types[widget.type].dropped
								,connectTo:$.FBuilder.fbWidget.types[widget.type].connectTo
								,type:widget.type
								,custom:widget.custom
								,props:extractWidgetProps(widget)
							})
						;
					});
				});
			})
		}
	};
	if( typeof FBuilderDeferInit === undefined || ! FBuilderDeferInit ){
		FBuilderINIT();
	}


	//-- bind dependencies field and pattern selection
	patternCompList = function(){
		var res = [];
		$.each($.tk.validable.defaultRules,function(k,v){
			res.push('@'+k);
		});
		return res;
	}();
	$('#widgetProperties').on('keydown',' input[name=pattern]',function(){
		$(this).autocomplete({
			list:patternCompList
		});
	});
	$('#widgetProperties').on('keydown',' input[name=dependOn]',function(){
		var rejectName = $(this).parent().parent().find('[name=name]').val();
		$(this).autocomplete({
			list:(function(){
				var res=[];
				$('form.FBuilderForm fieldset :input').each(function(){ this.name && (this.name !== rejectName) && res.push(this.name);});
				return res;
			}())
		});
	});
	$('#widgetProperties').on('keydown',' input[name=parentList]',function(){
		var rejectName = $(this).parent().parent().find('[name=name]').val();
		$(this).autocomplete({
			list:(function(){
				var res=[];
				$('form.FBuilderForm fieldset select').each(function(){ this.name && (this.name !== rejectName) && res.push(this.name);});
				return res;
			}())
		});
	});
});

</script>
