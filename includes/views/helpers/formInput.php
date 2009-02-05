<?php
/**
* @package simpleMVC
* @subPackage helpers
* @class formInput_viewHelper
* @changelog
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
	*                          - t[e]xt
	*                          - password
	*                          - [text]area
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
	*                          - multiple,class, size, id, onchange, maxlength, disabled are replaced by the corresponding html attributes
	*                          - default id will be same as name
	*                          - default class will be same as type
	*                          - values is an associative list of key value pairs (keys are used as values and values are used as labels) used with select | checkBox | radio
	*                          - label is an optional label for the input
	*                          - pickerOptStr is used for datepicker and timepicker fields
	*                          - pickerOpts   is used for datetimepicker (something like that: array(0=>dateOptStr,1=>timeOptStr))
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
		switch($type){
			case 'skip':
				return '';
				break; //-- dummy break
			case 'txt':
			case 'text':
			case 'password':
				if($type==='txt')
					$type='text';
				$value = preg_replace('/(?<!\\\\)"/','\"',$value);
				return $this->formatInput(
					$labelStr,
					"<input type=\"$type\" name=\"$name\" value=\"$value\"".$this->getAttrStr($options)." />",
					$options['formatStr']
				);
				break; //-- dummy break
			case 'hidden':
				$value = preg_replace('/(?<!\\\\)"/','\"',$value);
				return "<input type=\"$type\" name=\"$name\" value=\"$value\"".$this->getAttrStr($options)." />";
				break; //-- dummy break
			case 'area':
			case 'textarea':
				if(! self::$useRTE ){
					return $this->formatInput(
						$labelStr,
						"<textarea name=\"$name\"".$this->getAttrStr($options).">$value</textarea>",
						$options['formatStr']
					);
					break;//-- dummy break
				} #--- else continue to rte
			case 'rte':
					$rteOptions = array('value' => $value,'rows'=>10,'cols'=>50);
					foreach($options as $k=>$o){
						if( in_array($k,array('rows','cols','disabled','style')) )
							$rteOptions[$k] = $o;
					}
					return $this->formatInput(
						$labelStr,
						$this->rte($name,$rteOptions),
						$options['formatStr']
					);
				break;
			case 'select':
				$opts = '';
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
					return $this->formatInput(
						$labelStr,
						"<input type=\"$type\" name=\"$name\" value=\"$value\"".$this->getAttrStr($options)." />",
						$options['formatStr']
					);
				}
				break;//-- dummy break
			case 'date':
			case 'datepicker':
				return $this->formatInput(
					$labelStr,
					$this->datepicker($name,$value,empty($options['pickerOptStr'])?null:$options['pickerOptStr']),
					$options['formatStr']
				);
				break;//--dummy break
			case 'time':
			case 'timepicker':
				return $this->formatInput(
					$labelStr,
					$this->timepicker($name,$value,empty($options['pickerOptStr'])?null:$options['pickerOptStr']),
					$options['formatStr']
				);
			case 'datetime':
			case 'datetimepicker':
				return $this->formatInput(
					$labelStr,
					$this->_datepicker_withTime($name,$value,empty($options['pickerOpts'])?null:$options['pickerOpts']),
					$options['formatStr']
				);
				break;//--dummy break
			case 'file':
				if( self::$useFileEntry && class_exists('fileEntry_viewHelper',false)){
					$inputStr = $this->fileEntry($name,$value,$options);
				}else{
					$inputStr = "<input type=\"file\" name=\"$name\" value=\"$value\"".$this->getAttrStr($options)." />";
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
					$this->codepress($name,$value,empty($options['codepress'])?null:$options['codepress']),
					$options['formatStr']
				);
				break;//-- dummy break
		}//end switch
	}

	protected function getAttrStr(array $attrs,array $excludeAttrs=null){
		$attrNames = array('class','size','maxlength','rows','cols','id','value','onchange','multiple','style','disabled');
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
