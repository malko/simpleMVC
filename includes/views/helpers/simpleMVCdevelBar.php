<?php
/**
* helper to incorporate simpleMVC develBar
* @package simpleMVC
* @svnInfos:
*            - $LastChangedDate$
*            - $LastChangedRevision$
*            - $LastChangedBy$
*            - $HeadURL$
*/

class simpleMVCdevelBar_viewHelper extends  jsPlugin_viewHelper{
	/** path relative to jQuery_viewHelper::$pluginPath/$pluginName */
	public $requiredFiles = array(
		'js/simpleMVC_develBar.js'
	);
	public $requiredPlugins = array(
		'jquery'
	);
	function simpleMVCdevelBar(){
		return '<div id="sMVCtoolBar">
			<button id="sMVCtoolBarToggle">&gt;</button><button id="sMVCmodels">Models</button><button id="sMVCshow">Show </button><button id="sMVCphperr">PHP Errors </button><button id="sMVCdb">Db::profiler</button></div>
			<div id="sMVCshow_div" class="sMVCpannel">
				<h1><span class="toggle" style="cursor:pointer;font-weight:normal;float:right;" title="Expand/collapse all">[+/-]</span>Show</h1>
			</div>
			<div id="sMVCphperr_div" class="sMVCpannel"><h1>Php Errors</h1></div>
			<div id="sMVCdb_div" class="sMVCpannel"><h1>Db::profiler</h1></div>'
			. $this->adminModelsMenu("sMVCmodelsList",true,true)
			. (class_exists('dbProfiler',false)?dbProfiler::printReport():'')
			. $this->view->_js_getPending();
	}
}
