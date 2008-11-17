/**
* jquery DropDown menu plugin.
* replace any UL / LI list by a dropdown menu
* @licence GPL / MIT
* @author jonathan gotti < jgotti at jgotti dot org > < jgotti at modedemploi dot fr > for modedemploi.fr
* @since 2008-11
* sample usage:
* $('ul.menu').DDmenu();
* or
* $('ul.menu').DDmenu(options);
* where options is an option object:
* possibles options are:
* - show:  one of false (equiv to show), show, slideDown or fadeIn (the default)
* - hide:  one of false (equiv to hide and the default), hide, slideUp or fadeOut
* - speed: time for in/out animation can be a single value or a pair [(int) showSpeed, (int) hideSpeed]
*/

(function($){
	$.fn.DDmenu = function(options){
		return this.each(function() {
				DDMENU($(this),options);
		});
	}
	function DDMENU(elmt, options ){
		return this instanceof DDMENU ? this.init(elmt,options): new DDMENU(elmt, options);
	}

	// plugin defaults settings
	$.fn.DDmenu.defaults = {
		show:'fadeIn', // one of false (equiv to show), show, slideDown or fadeIn
		hide:false,    // one of false (equiv to hide), hide, slideUp or fadeOut
		speed:[250,0] // time for in/out animation can be a single value or a pair [(int) showSpeed, (int) hideSpeed]
	}

	$.extend(DDMENU.prototype,{
		menuId:false,
		opts: {},
		init: function(elmt,options){
			this.opts = $.extend({}, $.fn.DDmenu.defaults, options);
			if(typeof(this.opts.speed) != 'object'){
				this.opts.speed = [this.opts.speed,this.opts.speed];
			}
			this.menuId = elmt.attr('id');
			// get all childs
			var childs = elmt.children('li').each(function(){$(this).css('display','inline');});
			// lookup each submenus
			var subs  = $('ul',elmt);
			for(var i=0;i<subs.length;i++){
				this.initSub(subs[i]);
			}
		},
		initSub: function(sub){
			sub = $(sub);
			var parent = sub.parent();
			sub.css({display:'none',position:'absolute',zIndex:100});
			parent.bind('mouseover',{DDmenu:this},this.showSub);
			parent.bind('mouseleave',{DDmenu:this},this.hideSub);
		},
		setSubPosition: function(sub){
			sub = $(sub);
			var parent = sub.parent();
			var coords = parent.offset();
			if(parent.parent('ul').attr('id')===this.menuId){
				style = {
					width: parseInt(parent.width())-4.+'px',
					left: coords.left+'px',
					top:  (coords.top+parseInt(parent.height()))+'px'
				};
			}else{
				style = {
					top: (coords.top-parent.parent('ul').offset().top)+'px',
					left:parent.width()+'px'
				};
			}
			sub.css(style);
		},
		hideSub: function(e){
			var parent = $(this);
			var sub = $(parent.children('ul').get(0));
			var ddmenu = e.data.DDmenu;
			if(! sub)
				return false;
			if( ! ddmenu.opts.hide )
				sub.hide(ddmenu.opts.speed[1]);
			else
				sub[ddmenu.opts.hide](ddmenu.opts.speed[1]);
			parent.removeClass('active');
		},
		showSub: function(e){
			var parent = $(this);
			var ddmenu = e.data.DDmenu;
			var sub = $(parent.children('ul').get(0));
			ddmenu.setSubPosition(sub);
			if(! sub)
				return false;
			if( ddmenu.opts.show ){
				sub[ddmenu.opts.show](ddmenu.opts.speed[0]);
			}else{
				sub.show(ddmenu.opts.speed[0]);
			}
			parent.addClass('active');
		}
	});
})(jQuery);
