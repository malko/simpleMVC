<?php
/**
* @package simpleMVC
* @subPackage helpers
* @class formInput_viewHelper
* @changelog
*            - 2010-02-12 - change validable callbacks for confirmations
*            - 2009-10-22 - add support for validable options
*            - 2009-09-04 - add support for selecbuttonset
*            - 2009-06-02 - prefix confirm inputs with _smvc_
*            - 2009-05-05 - add support for text/password confirm fields
*            - 2009-03-27 - replace use of fileEntry with filemanager_Entry plugin
*            - 2009-01-05 - add support for time picker and datetime picker
*            - 2008-11-27 - better multiple select support
*            - 2008-11-26 - add disabled optional attribute
*            - 2008-11-07 - add codepress and skip types
*                         - now radio and checkbox are contained in their label tag
*            - 2008-10-30 - add static property $useFileEntry and svn infos
* @svnInfos:
*            - $LastChangedDate$
*            - $LastChangedRevision$
*            - $LastChangedBy$
*            - $HeadURL$
*/
class formInput_viewHelper extends abstractViewHelper{
	static $useRTE = false;
	static $useFileEntry = true;
	/**
	*
	* @param string $name      name of the input use as default id attribute if none provide as option
	* @param mixed  $value     string or list of values (for multiple selectors or checkboxes)
	* @param string $type      type of input to use
	*                          - select
	*                          - selectbuttonset
	*                          - t[e]xt[Confirm]
	*                          - password[Confirm]
	*                          - [text]area|forcetextarea
	*                          - rte
	*                          - check[box]
	*                          - radio
	*                          - hidden
	*                          - date[picker]
	*                          - time[picker]
	*                          - datetime[picker]
	*                          - file
	*                          - codepress
	* @param array  $options   list of optionnal parameters:
	*                          - default is the default value to set if $value is empty.
	*                          - multiple,class, size, id, onchange, maxlength, rows,cols,style,checked and disabled are replaced by the corresponding html attributes
	*                          - default id will be same as name
	*                          - default class will be same as type
	*                          - values is an associative list of key value pairs (keys are used as values and values are used as labels) used with select | checkBox | radio
	*                          - label is an optional label for the input
	*                          - pickerOptStr is used for datepicker and timepicker fields
	*                          - pickerOpts   is used for datetimepicker (something like that: array(0=>dateOptStr,1=>timeOptStr))
	*                          - rteOpts      is used for rte options
	*                          - confirmOpts  is used for t[e]xtConfirm and passwordConfirm to override default confirmation options.
	*/
	function formInput($name,$value=null,$type='text',array $options=array()){
		$dfltOpts = array(
			'id'    => $name,
			'class' => $type,
			'formatStr' => '<div class="formInput">'.((in_array($type,array('radio','check','checkbox')) && empty($options['values']))?'%input %label':'%label %input').'</div>',
		);
		$options = array_merge($dfltOpts,$options);

		$value = null!==$value?$value:(isset($options['default'])?$options['default']:'');
		$labelStr = (isset($options['label'])?"<label for=\"$options[id]\">$options[label]</label>":'');

		#- check for some validable options.
		$validableOptsNames = array('required','help','minlength','maxlength','rule');
		$supportMaxlength = array('txt','text','pass','password','txtConfirm','passwordConfirm');
		$validableOpts = array();
		$validableForm = $this->view->getController() instanceof modelsController?'form.adminForm':'form';
		foreach($validableOptsNames as $key){
			if( $key==="maxlength" && in_array($type,$supportMaxlength) )
				continue;
			if( ! empty($options[$key])){
				$validableOpts[$key] = $options[$key];
				unset($options[$key]);
			}
		}
		if( !empty($validableOpts)){
			$this->validable($name,$validableOpts,$validableForm);
		}

		switch($type){
			case 'skip':
				return '';
				break; //-- dummy break
			case 'txt':
			case 'text':
			case 'password':
			case 'txtConfirm':
			case 'textConfirm':
			case 'passwordConfirm':
				if(strpos($type,'Confirm')){ //-- manage confirmation if required
					$type = str_replace('Confirm','',$type);
					$confirmOpts = array_merge($options, array(
						'label' => 'Confirmation',
						'class' => $type.'Confirm ui-priority-secondary',
						'id'    => 'formInputConfirm_'.$options['id'],
					));
					if( isset($options['confirmOpts']))
						$confirmOpts = array_merge($confirmOpts,$options['confirmOpts']);

					$confirm = $this->formInput("_smvc_confirm[$name]",$value,$type,$confirmOpts);
					$this->validable("_smvc_confirm[$name]",array('rule'=>'formInputConfirm'),$validableForm);
					$this->_js_scriptOnce("
					window.formInputConfirm = function(val){
						var m = this.id.match(/^formInputConfirm_(.*)/);
						if(m &&  $('#'+m[1]).val()==val){
							return true;
						}
						return false;
					}
					",'formInputCheckConfirm');
					$this->js("
						$('input#$options[id]').bind('change keyup',function(){
							var oValidable = $('input#$confirmOpts[id]').data('validable');
							if( $(this).val())
								oValidable.required= $(this).val()!==''?true:false;
							oValidable.check();
						})
					");
				}
				if($type==='txt')
					$type='text';
				$value = preg_replace('/(?<!\\\\)"/','\"',$value);

				return $this->formatInput(
					$labelStr,
					"<input type=\"$type\" name=\"$name\" value=\"$value\"".$this->getAttrStr($options)." />",
					$options['formatStr']
				).(isset($confirm)?$confirm:'');
				break; //-- dummy break
			case 'hidden':
				$value = preg_replace('/(?<!\\\\)"/','\"',$value);
				return "<input type=\"$type\" name=\"$name\" value=\"$value\"".$this->getAttrStr($options)." />";
				break; //-- dummy break
			case 'area':
			case 'textarea':
			case 'forcetextarea':
				if((! self::$useRTE) || $type==='forcetextarea'){
					return $this->formatInput(
						$labelStr,
						"<textarea name=\"$name\"".$this->getAttrStr($options).">$value</textarea>",
						$options['formatStr']
					);
					break;//-- dummy break
				} #--- else continue to rte
			case 'rte':
					if( !empty($validableOpts) ){
						if( !empty($validableOpts['help'])) // add help display on the iframe focus
							$_rteValidableOpts['helpTrigger'] = "#RTE_FRAME_$options[id]";
							$_rteValidableOpts['helpAfter'] = "#RTE_$options[id]";
						if( (!empty($validableOpts['required'])) && ! isset($validableOpts['rule']))
							$_rteValidableOpts['rule'] = 'requiredRteValidable';
						if( isset($_rteValidableOpts))
							$this->validable($name,$_rteValidableOpts,$validableForm);
					}
					$rteOptions = array('value' => $value,'rows'=>10,'cols'=>40);
					foreach($options as $k=>$o){
						if( in_array($k,array('rows','cols','disabled','style','rteOpts')) )
							$rteOptions[$k] = $o;
					}
					return $this->formatInput(
						$labelStr,
						$this->rte($name,$rteOptions),
						$options['formatStr']
					);
				break;
			case 'select':
			case 'selectbuttonset':
				$opts = '';
				if( $type==='selectbuttonset'){
					$this->view->helperLoad('button');
					$this->_js_scriptOnce("$('.formInput select.ui-selectbuttonset').selectbuttonset();",'formInputSelectButtonSetLoader');
					$options['class'] = 'ui-selectbuttonset'.(empty($options['class'])?'':" $options[class]");
					if(! empty($validableOpts)){
						$this->validable($name,array('helpTrigger'=>"#$options[id] ~ .ui-buttonset .ui-button",'helpAfter'=>"#$options[id] ~ .ui-buttonset"),$validableForm);
					}
				}
				if( !empty($options['values']) ){
					foreach($options['values'] as $k=>$v){
						if( is_array($value) )
							$selected = in_array($k,$value)?' selected="selected"':'';
						else
							$selected = $k==$value?' selected="selected"':'';
						$opts .= "<option value=\"$k\"$selected>$v</option>";
					}
				}
				if( isset($options['multiple']) && empty($options['multiple']))
					unset($options['multiple']);
				return $this->formatInput(
					$labelStr,
					"<select name=\"$name".(isset($options['multiple'])?'[]':'')."\"".$this->getAttrStr($options).">$opts</select>",
					$options['formatStr']
				);
				break;//-- dummy break
			case 'check':
			case 'checkbox':
			case 'radio':
				if( $type==='check')
					$type = 'checkBox';
				if( isset($options['values']) && is_array($options['values']) && count($options['values'])>0){
					$opts = '';
					$idStr= $options['id'];
					$i=-1;
					foreach($options['values'] as $ok=>$ov){
						if( is_array($value) )
							$checked = in_array($ok,$value)?' checked="checked"':'';
						else
							$checked = $ok==$value?' checked="checked"':'';
						#- ~ $labelStr = "<label for=\"\">$ov</label>";
						$opts .= "<label><input type=\"$type\" name=\"$name".($type==='radio'?'':"[$ok]")."\" value=\"$ok\"".$this->getAttrStr($options)."$checked />$ov</label>";
					}
					return $this->formatInput($labelStr,$opts,$options['formatStr']);
				}else{
					if( isset($options['checked']))
						$options['checked'] = empty($options['checked'])?null:'checked';
					return $this->formatInput(
						$labelStr,
						"<input type=\"$type\" name=\"$name\" value=\"$value\"".$this->getAttrStr($options)." />",
						$options['formatStr']
					);
				}
				break;//-- dummy break
			case 'date':
			case 'datepicker':
				if( !empty($validableOpts) ){
					$this->validable($name,array('helpAfter'=> "#$options[id] ~ .ui-datepicker-trigger"),$validableForm);
				}
				return $this->formatInput(
					$labelStr,
					$this->datepicker($name,$value,empty($options['pickerOptStr'])?null:$options['pickerOptStr']),
					$options['formatStr']
				);
				break;//--dummy break
			case 'time':
			case 'timepicker':
				if( !empty($validableOpts) ){
					$this->validable($name,array(
							'helpAfter'=> "#timepicker_$options[id]",
							'helpTrigger'=> "#timepicker_$options[id] input",
							'stateElmt'=> "#timepicker_$options[id] input",
						),
						$validableForm
					);
				}
				return $this->formatInput(
					$labelStr,
					$this->timepicker($name,$value,empty($options['pickerOptStr'])?null:$options['pickerOptStr']),
					$options['formatStr']
				);
			case 'datetime':
			case 'datetimepicker':
				if( !empty($validableOpts) ){
					$this->validable($name,array(
							'helpAfter'=> "#timepicker_time_$options[id]",
							'helpTrigger'=> "#date_$options[id], #timepicker_time_$options[id] input",
							'stateElmt'=> "#date_$options[id], #timepicker_time_$options[id] input",
						),
						$validableForm
					);
				}
				return $this->formatInput(
					$labelStr,
					$this->_datepicker_withTime($name,$value,empty($options['pickerOpts'])?null:$options['pickerOpts']),
					$options['formatStr']
				);
				break;//--dummy break
			case 'file':
			case 'fileentry':
			case 'fileextended':
				if( $type==='fileentry' || ( $type=='file' && self::$useFileEntry ) ){
					if( !empty($validableOpts))
						$this->validable($name,array('helpAfter'=>"#bt$options[id]"),$validableForm);
					$inputStr = $this->_filemanager_entry($name,$value,$options);
				}else{
					$inputStr = "<input type=\"file\" name=\"$name\" ".$this->getAttrStr($options)." />";
					if( $type==='fileextended' && $value){
						$inputStr .= "<br /><label><input type=\"checkbox\" name=\"filedelete[$name]\" value=\"1\" /> ".langManager::msg('Delete current file ')."<a href=\"$value\" target=\"_blank\">".basename($value).'</a></label>';
					}
				}
				return $this->formatInput(
					$labelStr,
					$inputStr,
					$options['formatStr']
				);
				break;//-- dummy break
			case 'codepress':
				return $this->formatInput(
					$labelStr,
					$this->codepress($name,$value,$options),
					$options['formatStr']
				);
				break;//-- dummy break
		}//end switch
	}

	protected function getAttrStr(array $attrs,array $excludeAttrs=null){
		$attrNames = array('class','size','maxlength','rows','cols','id','value','onchange','multiple','style','disabled','checked');
		$attrStr= '';
		foreach($attrs as $ok=>$ov){
			if( is_array($excludeAttrs) && in_array($ok,$excludeAttrs) )
				continue;
			if( in_array($ok,$attrNames) && $ov!==null && $ov !=='')
				$attrStr.=" $ok=\"".preg_replace('/(?<!\\\\)"/','\"',$ov).'"';
		}
		return $attrStr;
	}

	protected function formatInput($labelStr,$input,$formatStr){
		return str_replace(array('%label','%input'),array($labelStr,$input),$formatStr);
	}

}
