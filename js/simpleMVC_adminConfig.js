$(function(){
var styleClass = "ui-widget-content ui-corner-all";

//-- make this an accordion
var formsIds = {string: 0, actions: 1, list: 2, filters: 3, forms: 4, messages: 5, config: 6, model: 7};
var activeForm =  window.location.href.match(/#\/?(\w+)/);
activeForm = (null !== activeForm && activeForm.length) ? formsIds[activeForm[1]]: 1;
$("#settings").accordion({
	header: "h3",
	autoHeight: false,
	animated: false,
	active: activeForm,
	collapsible: true,
	change:function(e,ui){
		var pane=ui.newHeader.find('a').attr('name');
		$('#sMVCtoolBar #sMVCmodelsList a[title=configure]').each(function(){
			$(this).attr('href',$(this).attr('href').replace(/(\/#\/[^\/]+)?$/,pane?'/#/'+pane:""));
		});
	}
});
// init confgure buttons
setTimeout(function(){
$('#sMVCtoolBar #sMVCmodelsList a[title=configure]').each(function(){
	var p = window.location.href.replace(/.*?(\/#\/[^\/]+)?$/,'$1');
	$(this).attr('href',$(this).attr('href').replace(/(\/#\/[^\/]+)?$/,p?p:""));
})},1000);

// make fieldname clickable to ease _toStr setting
$(".sMVC_dataField").click(function (){
	var v = $("input[name=_toStr]").val();
	$("input[name=_toStr]").val((v?v+" ":"")+this.innerHTML).focus();
}).css({cursor:"pointer"});

//-- make list fields sortable
$("ul.sortable").sortable({
	placeholder: "ui-state-highlight placeholder ui-corner-all",
	forcePlaceholderSize:true
}).find("li").addClass(styleClass);

//-- add items to list of fields
function listAddItem(fieldName,format){
	var item = $('<li><input id="fields['+fieldName+']" name="fields['+fieldName+']" class="checkbox" type="checkbox" checked="checked" value="'+fieldName+'"/>'+
	'<label for="fields['+fieldName+']">'+fieldName+'</label> '+
	'<div class="formInput"><label for="formatStr['+fieldName+']">format string</label><input id="formatStr['+fieldName+']" class="text" type="text" size="50" value="" name="formatStr['+fieldName+']"/> '+
	'</div>').appendTo("#listItems").addClass(styleClass);
	item.find(".ui-button").button().css({float:"right"}).click(function(){$(this).parent("li").remove();});
}
$("#listAddField").click(function(){
	var fieldName = prompt(langMessages.listFldName);
	if(! fieldName){
		return;
	}
	listAddItem(fieldName,"");
});
$('#listItems .toggleItemFormat').click(function(){
	var elmt = $(this), formatContainer = elmt.siblings('.formInput'), visible=formatContainer.is(':visible');
	elmt
		.toggleClass('ui-icon-circle-triangle-s',visible)
		.toggleClass('ui-icon-circle-triangle-n',!visible)
		;
	formatContainer.toggle();
}).each(function(){
	if(! $(this).siblings('.formInput').find('input').val()){
		$(this)
			.addClass('ui-icon-circle-triangle-s')
			.removeClass('ui-icon-circle-triangle-n')
			.siblings('.formInput').hide();
	}
});

//-- manage ordering and grouping of forms inputs
function updateFieldSets(){
	$("#fieldList .fieldSet").each(function(){
		var val = [],
			labs = $(this).find(".formInput label:first-child");
		labs.each(function(){val.push(this.innerHTML);});
		$(this).find("input[type=hidden][name^=fieldsOrder]").val(val.join(","));
	});
}
$.fn.connectInputSortList = function(){
	$(this).addClass(styleClass).sortable({
			placeholder: "ui-state-highlight placeholder ui-corner-all",
			forcePlaceholderSize:true,
			dropOnEmpty:true,
			items:".formInput",
			scrollSensitivity: 40,
			update:updateFieldSets
		}).find(".formInput").addClass(styleClass);
		var header = $(".ui-widget-header",this);
		$('<span class="ui-icon ui-icon-close"></span>').prependTo(header).click(function(){
			if( $(this).parents(".fieldSet").children(".formInput").length < 1){
				$(this).parents(".fieldSet").remove();
			}	else {
				alert("Can't remove a not empty group");
			}
		});
	$("#fieldList .fieldSet").sortable("option", "connectWith", "#fieldList .fieldSet");
};
$("#fieldList").sortable({
	placeholder: "ui-state-highlight placeholder ui-corner-all",
	forcePlaceholderSize:true,
	items:".fieldSet",
	handle:".ui-widget-header"
}).find(".fieldSet").connectInputSortList();
$("#addFieldSet").click(function(){
	$('<div class="fieldSet" id="fieldSet_'+($("#forms .fieldSet").length+1)+'">'+
		'<label class="ui-widget-header">Group Name: <input type="text" name="fieldSet[]" value="" /></label>'+
		'<input type="hidden" name="fieldsOrder[]" value="" />'+
		'</div>').appendTo("#fieldList").connectInputSortList();
	$("#forms #fieldGroupMethod").show();
});
updateFieldSets();

$('#formAddFieldList span').click(function(){
	//recuperation de la derniere liste
	var fName = $(this).text()
		, fldSet= $('#fieldList .fieldSet:last')
		, clone = fldSet.find('.formInput:last').clone(true)
		, order = fldSet.find('input[type=hidden][name^=fieldsOrder]')
		;
	clone.find('select').attr('id','inputTypes['+fName+']').attr('name','inputTypes['+fName+']');
	clone.find('label:first').attr("for",'inputTypes['+fName+']').text(fName);
	clone.find('input[type=text]').attr('id','inputOptions['+fName+']').attr('name','inputOptions['+fName+']');
	clone.find('label:last').attr("for",'inputOptions['+fName+']');
	order.val(order.val()+','+fName);
	fldSet.append(clone);
	//dbg($('#fieldList .fieldSet:last .formInput:last'));
});

//- manage fields order reset button
$("#resetFieldsOrder").click(function(){
	if( confirm("Are You sure you want to reset form fields order?") ){
		window.location= ADMIN_MODEL_URL+"/#/forms";
	}
});

//-- manage lang pannel
$("select#setLang").change(function(){
	var l = this.options[this.selectedIndex].innerHTML;
	$("div.langMessages").hide();
	$("div#langMessages_"+l).show();
}).change();
$("#addTranslationField").click(function(){
	var fName = prompt(langMessages.translationMsgId);
	if(! fName){
		return;
	}
	// detection des langues possibles
	$("select#setLang option").each(function(i){
		var l = this.innerHTML;
		$("#langMessages_"+l).append("<div class=\"formInput\">"+fName+": <input type=\"text\" name=\"msgs["+l+"]["+fName+"]\" value=\"\" /></div>");
	});
});

//-- add toggle on modelAddons list
$("#modelAddonList li").each(function(){
	var e = $(this),
		code = e.find("code");
	if( code.length < 1){
		return;
	}
	code.hide().css("cursor","text").click(function(){return false;});
	e.css("cursor","pointer").click(function(){code.toggle();});
});

//-- add form option client side validation

//--- test options are valid json
$("#forms input[id^=inputOptions]").keyup(function(){
	var val = $(this).val();
	if( val.length<1){
		return $(this).removeClass("ui-state-error");
	}
	if( (/[^,:\{\}\[\]0-9.\-+Eaeflnr-u \n\r\t]/.test(val.replace(/"(\\.|[^"\\])*"/g, ""))) ){
		return $(this).addClass("ui-state-error");
	}
	try{
		var test =  eval("(" + $(this).val() + ")");
		$(this).removeClass("ui-state-error");
	}catch(e){
		$(this).addClass("ui-state-error");
	}
});
});