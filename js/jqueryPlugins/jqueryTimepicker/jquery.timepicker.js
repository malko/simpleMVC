/**
* jquery timepicker plugin.
* @licence GPL / MIT
* @author jonathan gotti < jgotti at jgotti dot org > < jgotti at modedemploi dot fr > for modedemploi.fr
* @since 2009-01
* @svnInfos:
*            - $LastChangedDate$
*            - $LastChangedRevision$
*            - $LastChangedBy$
*            - $HeadURL$
* @changelog
*            - 2009-01-05 - bind setTime on the givenElement
* sample usage:
* $('input#time').timepicker();
* or
* $('input#time').timepicker(options);
* where options is an object with following possibles values:
* - format -> 12 or 24 Hours
* - showAMPM -> only make sense with format=12
* - showSeconds -> true or false to display or not seconds
*/

(function($){
	$.fn.timepicker = function(options){
		return this.each(function() {
				TIMEPICKER($(this),options);
		});
	}
	function TIMEPICKER(elmt, options ){
		return this instanceof TIMEPICKER ? this.init(elmt,options): new TIMEPICKER(elmt, options);
	}

	// plugin defaults settings
	$.fn.timepicker.defaults = {
		format:12, // one of 12 or 24
		showAMPM:true, // show the AM/PM selector make sense only on format=12
		showSeconds:true // will display seconds or not
	}

	$.extend(TIMEPICKER.prototype,{
		elmt:null,
		id:'',
		_elements:null,
		opts: {},
		init: function(elmt,options){
			this.opts = $.extend({}, $.fn.timepicker.defaults, options);
			this.elmt = elmt;
			$.extend(this.elmt.get(0),{setTime:this.setTime});
			this.id = elmt.attr('id');
			this.initPickerElements();
			this.setTime((elmt.attr('value').length?elmt.attr('value'):'00:00:00 AM'));
		},
		setTime: function(time){
			var t = time.split(':');
			var H = t[0]||'00';
			var M = t[1]||'00';
			var S = t[2]||'00';
			if( S.match(/AM|PM/i) ){
				$('#timepickerAMPM_'+this.id).text(S.match(/PM$/i)?'PM':'AM');
				S = parseInt(S);
			}
			$('#timepickerH_'+this.id).attr('value',H).change();
			$('#timepickerM_'+this.id).attr('value',M).change();
			$('#timepickerS_'+this.id).attr('value',S).change();
		},
		initPickerElements: function(){
			// create selectors
			var selectorsString='<span id="#timepicker_'+this.id+'" >\
			<input type="text" maxlength="2" size="2" id="timepickerH_'+this.id+'" value="00" /> : \
			<input type="text" maxlength="2" size="2" id="timepickerM_'+this.id+'" value="00" /> : \
			<input type="text" maxlength="2" size="2" id="timepickerS_'+this.id+'" value="00" /> \
			<small id="timepickerAMPM_'+this.id+'">AM</small> \
			</span>';
			this.elmt.after(selectorsString);
			if( this.opts.format == 24  || ! this.opts.showAMPM)
				$('#timepickerAMPM_'+this.id).hide();
			$('#timepickerAMPM_'+this.id).bind('click',{tp:this},function(e){var t = $(this);t.text(t.text()=='AM'?'PM':'AM');e.data.tp.update()});
			if( ! this.opts.showSeconds)
				$('#timepickerS_'+this.id).hide();
			$('#timepickerH_'+this.id).bind('change',{tp:this},function(e){
				var tp = e.data.tp;
				var v = this.value;
				if( v <= 0)
					this.value='00';
				else	if( v > 12 && tp.opts.format == 12)
					this.value = 12;
				else	if( v > 23 && tp.opts.format == 24)
					this.value = 23;
			});
			$('#timepickerM_'+this.id+', #timepickerS_'+this.id).bind('change',{tp:this},this.checkLess60);
			$('#timepickerH_'+this.id+', #timepickerM_'+this.id+', #timepickerS_'+this.id).bind('keypress',{tp:this},function(e){
				var v = null;
				var val = Number(this.value);

				switch(e.keyCode){
					case 36: v='00'; break;
					case 33: v=val+10; break;
					case 38: v=val+1; break;
					case 35: v='60' ; break;
					case 34: v=val-10; break;
					case 40: v=val-1; break;
					default:
						return;
						break;
				}
				this.value = e.data.tp.checkNbFormat(v);
				$(this).change();
			});
			// if extension mousewheel exists then use it
			if( $.event.special.mousewheel ){
				$('#timepickerH_'+this.id+', #timepickerM_'+this.id+', #timepickerS_'+this.id).bind('mousewheel',{tp:this},function(e,delta){
					this.value = e.data.tp.checkNbFormat(Number(this.value)+(delta>0?1:-1));
					$(this).change();
				});
			}
			$('#timepickerH_'+this.id+', #timepickerM_'+this.id+', #timepickerS_'+this.id).bind('change',{tp:this},function(e){
				this.value=e.data.tp.checkNbFormat(this.value);
				e.data.tp.update();
			});
			this.elmt.css('display','none');
		},
		checkLess60: function(e){
			var v = this.value;
			if( v < 0)
				this.value = '00';
			else if (v > 59)
				this.value = '59';
		},
		checkNbFormat: function(v){
			if( v.toString().match(/\d\d/))
				return v;
			return v.toString().match(/^\d$/)?'0'+v:'00';
		},
		update: function(){
			var H = $('#timepickerH_'+this.id).attr('value');
			var M = $('#timepickerM_'+this.id).attr('value');
			var S = $('#timepickerS_'+this.id).attr('value');
			var v = H+':'+M+':'+S+( (this.opts.format!=24 && this.opts.showAMPM)?' '+$('#timepickerAMPM_'+this.id).text():'' );
			this.elmt.attr('value',v);
			this.elmt.change();
		}
	});
})(jQuery);
