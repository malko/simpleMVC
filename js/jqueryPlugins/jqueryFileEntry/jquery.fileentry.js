/**
* plugin specifique pour la gestion des entrées de fichiers.
* @changelog - 2008-05-14 - add support for optional field name
*/
(function($){
	$.fn.fileEntry = function(options){
		return this.each(function() {
				fileEntry($(this),options);
		});
	}
	function fileEntry(elmt, options ){
		return this instanceof fileEntry ? this.init(elmt,options): new fileEntry(elmt, options);
	}

	$.extend(fileEntry.prototype,{
		input:  null,
		browse: null,
		id:     null,

		init: function(elmt,options){
			this.opts  = $.extend({}, $.fn.fileEntry.defaults, options);
			this.input = elmt;
			this.id    = elmt.attr('id');
			if( this.opts.withName !== ''){
				this.name = $('<input type="text" name="'+this.opts.withName+'[]" value="'+this.opts.dfltName+'" />');
				if( this.opts.nameClass)
				this.name.addClass(this.opts.nameClass);
				this.input.before(this.name).before('&nbsp;');
			}
			this.browse= $('<img src="'+this.opts.imgPath+'/fileopen.png" alt="selectionner un fichier sur le serveur." />');
			this.browse.bind('click',{fileEntry:this},this.fileBrowserDialog);
			this.browse.css('vertical-align','middle');
			this.input.after(this.browse).after("&nbsp;");
		},

		fileBrowserDialog: function(e){
			var fe  = e.data.fileEntry;
			var div = $('\
				<div id="rteIframeContainer">\
					<h3>sélection de document</h3>\
					<iframe src="'+fe.opts.rootPath+'/docs/uploadform/embed/'+fe.id+(fe.input.attr('path')?'/fold/'+fe.input.attr('path'):'')+'" style="width:450px;height:250px;border:none;"></iframe>\
				</div>'
			);
			$.facebox(div);
		}
	});
	$.fn.fileEntry.defaults = {
		imgPath: '',
		rootPath:'',
		/** options for name optional name entry */
		withName:'',
		dfltName:'Nom du document',
		nameClass:'noSize'
	}
})(jQuery);