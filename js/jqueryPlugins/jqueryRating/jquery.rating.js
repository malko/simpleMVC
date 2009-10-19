/**
* another jquery star rating system plugin.
* replace any select elements by a star rating system, in a beutifully degradable maner.
* you can easily replace the submit and onclick method to sweet your need (use an ajax call for example)
* @licence GPL / MIT
* @author jonathan gotti < jgotti at jgotti dot org >
* @since 2008-01
* @url https://jqueryplugins.googlecode.com/svn
* @svnInfos:
*            - $LastChangedDate$
*            - $LastChangedRevision$
*            - $LastChangedBy$
*            - $HeadURL$
* @changelog
*            - 2008-02-07 - supress the stars array and add ids on stars
*                         - now you can have multiple ratings on the same page
*                         - new options tohide to permit to pass element selector to hide on create time such as submit button for example.
* @todo add an optionnal cancel button to permitt empty value (if 0 was a valid value on the original select)
*/
(function($){
	$.fn.rating = function(options){
		return this.each(function() {
				RATING($(this),options);
		});
	}
	function RATING(elmt, options ){
		return this instanceof RATING ? this.init(elmt,options): new RATING(elmt, options);
	}
	$.extend(RATING.prototype,{
		val: 0,
		elmt:   null,
		id:        null,
		container: null,
		form: null,
		opts:{},
		init: function(elmt,options){
			// prepare options without overriding default ones
			this.opts   = $.extend({}, $.fn.rating.defaults, options);
			this.elmt   = elmt;
			this.id     = elmt.get(0).name;
			this.form   = elmt.get(0).form;
			this.editable = elmt.attr('disabled')?false:this.opts.editable;
			this.setRange()
				.createElements()
				.makeEditable()
				.setval();
			this.opts.ratingData = this;
		},
		/** recupere les val max et min */
		setRange: function(){
			this.val = this.elmt.val();
			var max =0, min=0;
			this.elmt.children().each(function(){
				var v = $(this).val();
				if( min==0 || min > v)
					min = v;
				if( max == 0 || max < v)
					max = v;
			});
			this.min = min;
			this.max = max;
			return this;
		},
		/** cree les elements */
		createElements: function(){
			this.container = $('<div class="rating" id="rating_'+this.id+'"></div>');
			for(var i=this.min; i<=this.max;i++){
				var a = $('<a class="star" id="star_'+this.id+'_'+i+'">&nbsp;</a>');
				this.container.append(a);
			}
			this.elmt.hide().before(this.container);
			if(this.opts.tohide)
				$(this.opts.tohide).hide();
			return this;
		},
		setval: function(val){
			if(val !== undefined)
				this.val = parseInt(val);
			$(".star",this.container).removeClass('full empty');
			for(var i=this.min; i<=this.max;i++){
				var star = $("#star_"+this.id+"_"+i,this.container);
				var c = (i<=this.val)?'full':'empty';
				star.addClass(c);
			}
			this.elmt.val(this.val);
			return this;
		},
		/** ajoute le necessaire pour l'edition de la note si c'est editable */
		makeEditable: function(){
			if(! this.editable)
				return this;
			for(var i=this.min; i<=this.max;i++){
				var star = $("#star_"+this.id+"_"+i);
				star.attr('href','#')
					.hover(this.hover,this.out)
				  .bind('click',this.opts.onclick);
				star.get(0).ratingData = this;
				star.get(0).val = i;
			}
			return this;
		},
		toggleEditable: function(){
			$(".star",this.container).removeClass('hover');
			this.editable = ! this.editable;
		},
		/** callback function for stars events */
		hover: function(){
			var r = this.ratingData;
			if(! r.editable)
				return false;
			for(var i=r.min ; i <= this.val ; i++){
				$('#star_'+r.id+'_'+i).addClass('hover');
			}
		},
		out: function(){
			if(! this.ratingData.editable)
				return false;
			$(".star",this.container).removeClass('hover');
		}
	});
	// plugin defaults settings
	$.fn.rating.defaults = {
		editable: true,
		autosubmit: true,
		tohide:     false,
		onclick: function(){
			var r = this.ratingData;
			if(! r.editable)
				return false;
			r.setval(this.val);
			if(r.opts.autosubmit)
				r.opts.submit();
			return false;
		},
		submit: function(){
			var r = this.ratingData;
			r.toggleEditable();
			r.form.submit();
		}
	}
})(jQuery);
