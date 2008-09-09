<?php

class formInput_viewHelper extends abstractViewHelper{

	/**
	*
	* @param string $name      name of the input use as default id attribute if none provide as option
	* @param mixed  $value     string or list of values (for multiple selectors or checkboxes)
	* @param string $type      type of input to use
	*                          - select
	*                          - text|txt|password
	*                          - area|textarea
	*                          - check|checkbox
	*                          - radio
	*                          - hidden
	*                          - file
	* @param array  $options   list of optionnal parameters:
	*                          - default is the default value to set if $value is empty.
	*                          - multiple,class, size, id, onchange, maxlength are replaced by the corresponding html attributes
	*                          - default id will be same as name
	*                          - default class will be same as type
	*                          - values is an associative list of key value pairs used with select | checkBox | radio
	*                          - label is an optional label for the input
	*                          -
	*/
	function formInput($name,$value=null,$type='text',array $options=array()){
		$dfltOpts = array(
			'id'    => $name,
			'class' => $type,
		);
		$options = array_merge($dfltOpts,$options);

		$value = null!==$value?$value:(isset($options['default'])?$options['default']:'');
		$labelStr = (isset($options['label'])?"<label for=\"$options[id]\">$options[label]</label>":'');
		switch($type){
			case 'txt':
			case 'text':
			case 'hidden':
			case 'password':
				if($type==='txt')
					$type='text';
				$value = preg_replace('/(?<!\\\\)"/','\"',$value);
				return "$labelStr<input type=\"$type\" name=\"$name\" value=\"$value\"".$this->getAttrStr($options)." />";
				break; //-- dummy break
			case 'area':
			case 'textarea':
				return "$labelStr<textarea name=\"$name\"".$this->getAttrStr($options).">$value</textarea>";
				break;//-- dummy break
			case 'select':
				if( !empty($options['values']) ){
					$opts = '';
					foreach($options['values'] as $k=>$v){
						if( is_array($value) )
							$selected = in_array($k,$value)?' selected="selected"':'';
						else
							$selected = $k==$value?' selected="selected"':'';
						$opts .= "<option value=\"$k\"$selected>$v</option>";
					}
				}
				return "$labelStr<select name=\"$name\"".$this->getAttrStr($options).">$opts</select>";
				break;//-- dummy break
			case 'check':
			case 'checkbox':
			case 'radio':
				if( $type==='check')
					$type = 'checkBox';
				if( isset($options['values']) && is_array($options['values']) && count($options['values'])>0){
					$opts = empty($labelStr)?'':$labelStr;
					$idStr= $options['id'];
					$i=-1;
					foreach($options['values'] as $ok=>$ov){
						if( is_array($value) )
							$checked = in_array($ok,$value)?' checked="checked"':'';
						else
							$checked = $ok==$value?' checked="checked"':'';
						$labelStr = "<label for=\"\">$ov</label>";
						$opts .= "<input type=\"$type\" id=\"$idStr".(++$i)."\" name=\"$name".($type==='radio'?'':"[$ok]")."\" value=\"$ok\"".$this->getAttrStr($options,array('id'))."$checked /><label for=\"$idStr$i\">$ov</label>";
					}
					return $opts;
				}else{
					return "$labelStr<input type=\"$type\" name=\"$name\" value=\"$value\"".$this->getAttrStr($options)." />";
				}
				break;//-- dummy break
			case 'file':
				return "$labelStr<input type=\"file\" name=\"$name\" value=\"$value\"".$this->getAttrStr($options)." />";
				break;//-- dummy break
		}//end switch
	}

	protected function getAttrStr(array $attrs,array $excludeAttrs=null){
		$attrNames = array('class','size','maxlength','rows','cols','id','value','onchange','multiple');
		$attrStr= '';
		foreach($attrs as $ok=>$ov){
			if( is_array($excludeAttrs) && in_array($ok,$excludeAttrs) )
				continue;
			if( in_array($ok,$attrNames) && $ov!==null && $ov !=='')
				$attrStr.=" $ok=\"".preg_replace('/(?<!\\\\)"/','\"',$ov).'"';
		}
		return $attrStr;
	}

}