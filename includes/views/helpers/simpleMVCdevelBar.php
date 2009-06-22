<?php
/**
* helper to incorporate simpleMVC develBar
* @package simpleMVC
* @svnInfos:
*            - $LastChangedDate$
*            - $LastChangedRevision$
*            - $LastChangedBy$
*            - $HeadURL$
* @changelog
*            - 2009-02-10 - add support to dynCss
*/

class simpleMVCdevelBar_viewHelper extends  jsPlugin_viewHelper{
	/** path relative to jQuery_viewHelper::$pluginPath/$pluginName */
	public $requiredFiles = array(
		'js/jqueryPlugins/jqueryDynCss/jquery.DynCss.js',
		'js/simpleMVC_develBar.js'
	);
	public $requiredPlugins = array(
		'jquery'
	);
	function simpleMVCdevelBar($dynCss=null){
		if( null!==$dynCss){
			if( is_string($dynCss) )
				$dynCss = explode('|',$dynCss);
			foreach($dynCss as $css)
				$this->js('$.DynCss("'.$css.'","simpleMVCDynCssAppend");');
		}
		return '<div id="sMVCtoolBar" class="ui-buttonset ui-buttonset-normal"><button id="sMVCtoolBarToggle" class="ui-button ui-button-circle-triangle-w"></button>'
		.'<button id="sMVCmodels" class="ui-button ui-button-gear">Models</button><button id="sMVCshow" class="ui-button ui-button-info">Show </button>'
		.'<button id="sMVCphperr" class="ui-button ui-button-alert">PHP Errors </button><button id="sMVCdb" class="ui-button ui-button-clock">Db::profiler</button></div>'
		.'<div id="sMVCpannels"><div id="sMVCshow_div" class="sMVCpannel">
				<h1><span class="toggle" style="cursor:pointer;font-weight:normal;float:right;" title="Expand/collapse all">[+/-]</span>Show</h1>
			</div>
			<div id="sMVCphperr_div" class="sMVCpannel"><h1>Php Errors</h1></div>
			<div id="sMVCdb_div" class="sMVCpannel"><h1>Db::profiler</h1></div></div>'
			. $this->adminModelsMenu("sMVCmodelsList",true,true)
			. (class_exists('dbProfiler',false)?dbProfiler::printReport():'')
			. $this->view->_js_getPending();
	}
}
