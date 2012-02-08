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
*            - 2010-06-03 - add simple benchmark
*            - 2009-02-10 - add support to dynCss
*/

class simpleMVCdevelBar_viewHelper extends  jsPlugin_viewHelper{
	/** path relative to jQuery_viewHelper::$pluginPath/$pluginName */
	public $requiredFiles = array(
		'js/simpleMVC_develBar.js'
	);
	public $requiredPlugins = array(
		'jquery','button'
	);
	static public $disable = false;

	function simpleMVCdevelBar($editorName=FRONT_NAME){
		if( self::$disable)
			return '';

		self::$disable = true;
		$benchInfos = '';
		if( !empty($GLOBALS['_SMVC_BENCH_']) ){
			$memUnit=array('o','Ko','Mo','Go','To','Po');
			$memUsage  = memory_get_usage(true);
			#- $memUsage2 = memory_get_usage(true)-$GLOBALS['_SMVC_BENCH_']['initMem'];
			$memUsage2 = memory_get_peak_usage(true);
			$memUsage2= round($memUsage2/pow(1024,($i=floor(log($memUsage2,1024)))),2).' '.$memUnit[$i];
			$memUsage= round($memUsage/pow(1024,($i=floor(log($memUsage,1024)))),2).' '.$memUnit[$i];

			$benchInfos = '<button>'.round(microtime(true) - $GLOBALS['_SMVC_BENCH_']['start'],4).'s  '.$memUsage.' / '.$memUsage2.'</button>';

		}
		/*if( smvcModule::modulesConfig(true)->filter('active')->count() ){
		}*/
		
		#- check for langManager
		$withLangManager = class_exists('langManager',false);

		$this->button('.ui-button',array('checkButtonset'=>true));

		return '<style type="text/css">#sMVCpannels .sMVCpannel,#sMVCmodelsList {display:none}</style><div id="sMVCtoolBar" class="ui-buttonset ui-buttonset-small"><button id="sMVCtoolBarToggle" class="ui-button ui-button-circle-triangle-e"></button>'
		.(defined('DB_CONNECTION')?'<button id="sMVCmodels" class="ui-button ui-button-gear">Models</button>':'')
		.(defined('MODULES_DIR')?'<a href="'.$this->url('adminmodules:').'" id="sMVCmodules" class="ui-button ui-button">Modules</a>':'')
		.'<button id="sMVCshow" class="ui-button ui-button-info">Show </button><button id="sMVCphperr" class="ui-button ui-button-alert tk-state-error ui-state-error">PHP Errors </button><button id="sMVCdb" class="ui-button ui-button-clock">Db::profiler</button>'
		.'<button id="sMVCcssEditor" rel="'.ROOT_URL.'/js/dryCss/cssEditor.php?editorId='.$editorName.'" class="ui-button ui-button-tag" >cssEditor</button>'
		.($withLangManager?'<button id="sMVClangmanager" class="ui-button ui-button-flag">langManager</button>':'')
		#- .'<button onclick="window.location=\''.$this->url('pages:clearSession').'\';" id="sMVCclearSession" class="ui-button ui-button-person">Clear Session</button>'
		.'<button rel="'.$this->url('pages:showSession').'" id="sMVCshowSession" class="ui-button ui-button-person">$_SESSION</button>'
		.((constant('CACHE_MANAGER_ENABLE') || js_viewHelper::$autoMinify )?'<button onclick="window.location=\''.$this->url('pages:clearCache').'\';" id="sMVCclearcache" class="ui-button ui-button-trash">Clear Cache</button>':'')
		.$benchInfos.'</div>
			<div id="sMVCpannels"><div id="sMVCshow_div" class="sMVCpannel">
				<h1><span class="toggle" style="cursor:pointer;font-weight:normal;float:right;" title="Expand/collapse all">[+/-]</span>Show</h1>
			</div>
			<div id="sMVCphperr_div" class="sMVCpannel"><h1>Php Errors</h1></div>'
			.($withLangManager?'<div id="sMVClangmanager_div" class="sMVCpannel"><h1>LangManager messages</h1></div>':'')
			.'<div id="sMVCdb_div" class="sMVCpannel"><h1>Db::profiler</h1></div></div>'
			. $this->adminModelsMenu("sMVCmodelsList",true,true)
			. (class_exists('dbProfiler',false)?dbProfiler::printReport(true):'')
			. ((defined('JS_TO_HEAD') && JS_TO_HEAD)?'':$this->view->_js_getPending());
	}
}
