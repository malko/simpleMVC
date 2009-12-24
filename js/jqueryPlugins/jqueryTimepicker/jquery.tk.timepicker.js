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

	$.toolkit('tk.timepicker',{
		_classNameOptions:{
			format:'|12|24',
			showSeconds:'|nosecs|secs',
			showAMPM:'|ampm'
		},
		elmt:null,
		id:'',
		_elements:null,

		_init: function(){
			if( typeof this.options.showSeconds ==='string' )
				this.options.showSeconds = this.options.showSeconds=='nosecs'?false:true;
			if( this.options.showAMPM ==='ampm' )
				this.options.showAMPM = true;

			this.id = this.elmt.attr('id');
			this._initPickerElements();
			this.setTime((this.elmt.attr('value').length?this.elmt.attr('value'):'00:00:00 AM'));
		},
		_initPickerElements: function(){
			var self = this;
			// create selectors
			var selectorsString='<span id="timepicker_'+this.id+'" >\
			<input type="text" maxlength="2" size="2" id="timepickerH_'+self.id+'" value="00" /> : \
			<input type="text" maxlength="2" size="2" id="timepickerM_'+self.id+'" value="00" /> <span>:</span> \
			<input type="text" maxlength="2" size="2" id="timepickerS_'+self.id+'" value="00" /> \
			<small id="timepickerAMPM_'+self.id+'">AM</small> \
			</span>';
			self.elmt.after(selectorsString);
			if( self.options.format == 24  || ! self.options.showAMPM)
				$('#timepickerAMPM_'+self.id).hide();
			$('#timepickerAMPM_'+self.id).click(function(e){var t = $(this);t.text(t.text()=='AM'?'PM':'AM');self.update()});
			if( ! self.options.showSeconds){
				$('#timepickerS_'+self.id).hide().prev('span').hide();
			}
			$('#timepickerH_'+self.id).change(function(e){
				var t = $(this);
				var v = t.val();
				if( v <= 0)
					t.val('00');
				else	if( v > 12 && self.options.format == 12)
					t.val(12);
				else	if( v > 23 && self.options.format == 24)
					t.val(23);
			});
			$('#timepickerM_'+this.id+', #timepickerS_'+this.id).change(self._checkLess60);
			$('#timepickerH_'+this.id+', #timepickerM_'+this.id+', #timepickerS_'+this.id).keypress(function(e){
				var t = $(this);
				var v = null;
				var val = Number(t.val());

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
				t.val(self._checkNbFormat(v)).change();
			});
			// if extension mousewheel exists then use it
			if( $.event.special.mousewheel ){
				$('#timepickerH_'+this.id+', #timepickerM_'+this.id+', #timepickerS_'+this.id).mousewheel(function(e,delta){
					var t = $(this);
					t.val( self._checkNbFormat(Number(t.val())+(delta>0?1:-1)) ).change();
					return false;
				});
			}
			$('#timepickerH_'+this.id+', #timepickerM_'+this.id+', #timepickerS_'+this.id).change(function(e){
				var t = $(this);
				t.val(self._checkNbFormat(t.val()));
				self.update();
			});
			self.elmt.css('display','none');
		},
		_checkLess60: function(e){
			var t = $(this);
			var v = t.val();
			if( v < 0)
				t.val('00');
			else if (v > 59)
				t.val('59');
		},
		_checkNbFormat: function(v){
			if( v.toString().match(/\d\d/))
				return v;
			return v.toString().match(/^\d$/)?'0'+v:'00';
		},
		getTime: function(){
			var H = $('#timepickerH_'+this.id).attr('value');
			var M = $('#timepickerM_'+this.id).attr('value');
			var S = $('#timepickerS_'+this.id).attr('value');
			return H+':'+M+':'+S+( (this.options.format!=24 && this.options.showAMPM)?' '+$('#timepickerAMPM_'+this.id).text():'' );
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
			$('#timepickerH_'+this.id).val(H).change();
			$('#timepickerM_'+this.id).val(M).change();
			$('#timepickerS_'+this.id).val(S).change();
		},
		update: function(){
			this.elmt.val(this.getTime()).change();
		}
	});


	// plugin defaults settings
	$.tk.timepicker.defaults = {
		format:12, // one of 12 or 24
		showAMPM:true, // show the AM/PM selector make sense only on format=12
		showSeconds:true // will display seconds or not
	}
})(jQuery);
