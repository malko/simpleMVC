/**
* jquery DropDown menu plugin.
* replace any UL / LI list by a dropdown menu
* @licence GPL / MIT
* @author jonathan gotti < jgotti at jgotti dot org > < jgotti at modedemploi dot fr > for modedemploi.fr
* @since 2008-11
* @svnInfos:
*            - $LastChangedDate$
*            - $LastChangedRevision$
*            - $LastChangedBy$
*            - $HeadURL$
* @changelog
*            - 2009-09-22 - new vertical and rtl option
*                         - now fixedWidth can be a width value or true to be equal to parent size.
*                         - some slight modification to offer better support under ie
*            - 2009-01-27 - new option fixedWidth
* sample usage:
* $('ul.menu').DDmenu();
* or
* $('ul.menu').DDmenu(options);
* where options is an option object:
* possibles options are:
* - show:  one of false (equiv to show), show, slideDown or fadeIn (the default)
* - hide:  one of false (equiv to hide and the default), hide, slideUp or fadeOut
* - speed: time for in/out animation can be a single value or a pair [(int) showSpeed, (int) hideSpeed]
* - fixedWidth: bool default to true. will fixed first level childs to have same width as their parent.
*               set it to false to let the subMenu's width defined by its contents
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
		show:'show', // one of false (equiv to show), show, slideDown or fadeIn
		hide:'hide',    // one of false (equiv to hide), hide, slideUp or fadeOut
		speed:[0,0], // time for in/out animation can be a single value or a pair [(int) showSpeed, (int) hideSpeed]
		fixedWidth:false, // does first level childs as same width as their top level parent
		vertical:false,
		rtl:false
	}
	var DDmenuCount=0;
	$.extend(DDMENU.prototype,{
		menuId:false,
		opts: {},
		init: function(elmt,options){
			this.opts = $.extend({}, $.fn.DDmenu.defaults, options);
			if(typeof(this.opts.speed) != 'object'){
				this.opts.speed = [this.opts.speed,this.opts.speed];
			}
			this.menuId = elmt.attr('id');
			DDmenuCount++;
			if(! this.menuId.length ){
				this.menuId = elmt.attr('id','ddmenu_'+DDmenuCount);
			}
			// get all childs
			var childs = elmt.children('li').css({
				zoom: 1,
				display:this.opts.vertical?'block':'inline'
			});
			childs.find('li').css({zoom:1,display:'block'});
			childs.find('ul').css({padding:0,margin:0});
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
			var parent = sub.parent(),
				coords = parent.offset(),
				firstSubLevel = parent.parent('ul').attr('id')===this.menuId?true:false;

			if( this.opts.fixedWidth && ( firstSubLevel || this.opts.vertical || this.opts.fixedWidth!==true) )
				sub.width(this.opts.fixedWidth===true?parent.outerWidth():this.opts.fixedWidth);

			if(firstSubLevel){
				style = {
					top:   parseInt(coords.top + (this.opts.vertical? 0 : parent.outerHeight() )),
					left:  parseInt(coords.left + ( this.opts.vertical ? (this.opts.rtl? 0- sub.outerWidth(): parent.outerWidth()):0))
				};
			}else{
				style = {
					top:  parseInt(coords.top-parent.parent('ul').offset().top),
					left: this.opts.rtl? 0-sub.outerWidth():parent.outerWidth()
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
