/**
* simpleMVC ovveridings for jquery.rte
*/
//--- display dialog for image selection inside rte ---//
cbRteImageDialog=function(rte,range){
	if( typeof(rteImageDialog)=='undefined' ){
		rteImageDialog = $('<div id="rteImgDialog" title="Insert an image" class="ui-widget">\
			<label for="rteImageSrc">Image Path</label>\
			<input type="text" name="rteImageSrc" id="rteImageSrc" class="ui-corner-all" />\
			<label for="rteImageAlt">Alternative Text</label>\
			<input type="text" name="rteImageAlt" id="rteImageAlt" class="ui-corner-all" />\
			<label for="rteImageAlign">Alignment</label>\
			<select name="rteImageAlign" id="rteImageAlign"class="ui-corner-all">\
				<option value="">normal</option><option value="float:left;">left</option><option value="float:right;">right</option>\
			</select>\
		</div>\
		');
		var buttons = {
			'save':function(){
				$(this).dialog('close'); //- hide dialog
				var rte = rteImageDialog.get(0).rte;
				var range = rteImageDialog.get(0).range;
				var src = $('#rteImageSrc',this).val();
				var alt = $('#rteImageAlt',this).val();
				var align = $('#rteImageAlign',this).val();
				if(src ==='')
						return false;
					var i = rte.createElement('img');
					i.src = src;
					if(align!=='')
						$(i).attr('style',align);
					if(alt!=='')
						$(i).attr('alt',alt);
					rte.insertNode(i,false,range);
				return false;
			},
			'cancel':function(){ $(this).dialog('close');return false;}
		};
		rteImageDialog.appendTo('body')
			.css({fontSize:'12px'})
			.dialog({autoOpen:false,resizable:false,buttons:buttons,width:'300px'});
		$('label',rteImageDialog).css({display:'block'});
		$('input,select',rteImageDialog).css({width:'230px'});
		$('input#rteImageSrc',rteImageDialog).filemanagerEntry(rteFmanagerOptions);
	}
	var rteDg = rteImageDialog.get(0);
	rteDg.rte = rte;
	rteDg.range = range;
	rteImageDialog.dialog('open');
}
//--- display dialog for link selection inside rte ---//
cbRteTableDialog=function(rte,range){
	if( typeof(rteTableDialog)=='undefined' ){
		rteTableDialog = $('<div id="rteTableDialog" title="Insert a table" class="ui-widget">\
			<label for="rteTableRows">number of rows</label>\
			<input type="text" name="rteTableRows" id="rteTableRows" class="ui-corner-all" value="1"/>\
			<label for="rteTableCols">Number of columns</label>\
			<input type="text" name="rteTableCols" id="rteTableCols" class="ui-corner-all" value="2"/>\
			<label for="rteTableCellspacing">Space size between cells</label>\
			<input type="text" name="rteTableCellspacing" id="rteTableCellspacing" class="ui-corner-all" value="0"/>\
			<label for="rteTableCellpadding">Cells padding</label>\
			<input type="text" name="rteTableCellpadding" id="rteTableCellpadding" class="ui-corner-all" value="2"/>\
			<label for="rteTableValign">Cells vertical alignment</label>\
			<select name="rteTableValign" id="rteTableValign" class="ui-corner-all">\
				<option value=""> --- default ---</option><option value="top">top</option><option value="middle">middle</option><option value="bottom">bottom</option>\
			</select>\
		</div>\
		');
		var buttons = {
			'save':function(){
				$(this).dialog('close'); //- hide dialog
				var rte      = rteTableDialog.get(0).rte;
				var range    = rteTableDialog.get(0).range;
				var rows     = parseInt($('#rteTableRows',this).val());
				var cols     = parseInt($('#rteTableCols',this).val());
				var cspacing = parseInt($('#rteTableCellspacing',this).val());
				var cpadding = parseInt($('#rteTableCellpadding',this).val());
				var cvalign  = $('#rteTableValign',this).val();

				var t = rte.createElement('TABLE');
				t.cellSpacing=cspacing;
				t.cellPadding=cpadding;
				for(var y=0;y<rows;y++){
					var tr = t.insertRow(y);
					for(var x=0; x<cols;x++){
						var td = tr.insertCell(x);
						td.innerHTML='&nbsp;';
						if( cvalign )
							$(td).css('vertical-align',cvalign);
					}
				}
				rte.insertNode(t,false,range);
				return false;
			},
			'cancel':function(){ $(this).dialog('close');return false;}
		};
		rteTableDialog.appendTo('body')
			.css({fontSize:'12px'})
			.dialog({autoOpen:false,resizable:false,buttons:buttons,width:'300px'});
		$('label',rteTableDialog).css({display:'block'});
		$('input,select',rteTableDialog).css({width:'230px'});
	}
	var rteDg = rteTableDialog.get(0);
	rteDg.rte = rte;
	rteDg.range = range;
	rteTableDialog.dialog('open');
}
//--- display dialog for link selection inside rte ---//
cbRteLinkDialog=function(rte,range){
	if( typeof(rteLinkDialog)=='undefined' ){
		rteLinkDialog = $('<div id="rteLinkDialog" title="Insert a link" class="ui-widget">\
			<label for="rteLinkHref">Link url</label>\
			<input type="text" name="rteLinkHref" id="rteLinkHref" class="ui-corner-all" />\
			<label for="rteLinkTarget">Link target</label>\
			<select name="rteLinkTarget" id="rteLinkTarget"class="ui-corner-all">\
				<option></option><option>_ blank</option><option>_self</option><option>_parent</option><option>top</option>\
			</select>\
		</div>\
		');
		var buttons = {
			'save':function(){
				$(this).dialog('close'); //- hide dialog
				var rte = rteLinkDialog.get(0).rte;
				var range = rteLinkDialog.get(0).range;
				var href = $('#rteLinkHref',this).val();
				var target = $('#rteLinkTarget',this).val();
				if( href==='')
					return false;
				var a = rte.createElement('a');
				a.href = href;
				if(target)
					a.target = target;
				rte.surroundContents(a,false,range);
				return false;
			},
			'cancel':function(){ $(this).dialog('close');return false;}
		};
		rteLinkDialog.appendTo('body')
			.css({fontSize:'12px'})
			.dialog({autoOpen:false,resizable:false,buttons:buttons,width:'300px',modal:true});
		$('label',rteLinkDialog).css({display:'block'})
		$('input,select',rteLinkDialog).css({width:'230px'})
		$('input#rteLinkHref',rteLinkDialog).filemanagerEntry(rteFmanagerOptions);
	}
	var rteDg = rteLinkDialog.get(0);
	rteDg.rte = rte;
	rteDg.range = range;
	rteLinkDialog.dialog('open');
}