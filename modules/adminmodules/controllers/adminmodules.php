<?php

class adminmodulesController extends abstractController{

	function init(){
		parent::init();
		if( ! checkUserRight('modules.admin') ){
			return $this->msgRedirect('Unauthorized action');
		}
	}
	function indexAction(){
		return $this->redirectAction('list');
	}

	function listAction(){
		$this->_conf = smvcModule::modulesConfig();
		$listDatas = array();
		foreach( $this->_conf as $modname=>$modConf){
			if( $modConf['active'] ){
				$actionLinks = '
					<a href="'.$this->url(':unactive',array($modname),false,null,false).'" class="ui-state-success tk-state-success ui-button ui-button-power" title="unactive">un-activate</a>
					<a href="'.$this->url(':configure',array($modname),false,null,false).'" class="ui-button ui-button-wrench" title="configure">Configure</a>
				';
			}else{
				$actionLinks = '<a href="'.$this->url(':active',array($modname),false,null,false).'" class="ui-state-error tk-state-error ui-button ui-button-power" title="active">active</a>';
			}
			$configLink = $modConf['active']?'<a href="'.$this->url(':configure',array($modname),false,null,false).'" class="ui-button ui-button-wrench">Configure</a>':'';
			$unactiveLink = $modConf['active']?'<a href="'.$this->url(':unactive',array($modname),false,null,false).'" class="ui-button ui-button-minus">un-activate</a>':'';
			$activeLink = $modConf['active']?'':'<a href="'.$this->url(':active',array($modname),false,null,false).'" class="ui-button ui-button-plus">active</a>';
			$listDatas[] = array(
				'weight'=>$modConf['weight']
				,'module'=>$modname
				,'actions'=> "<div class=\"ui-buttonset-small-i\">$actionLinks</div>"
			);
		}
		$this->listDatas = $listDatas;
	}

	function activeAction($moduleName){
		$module = smvcModule::getInstance($moduleName);
		if(! $module instanceof smvcModule){
			return $this->redirect();
		}
		$module->setActive();
		if( (! $module->isInstalled()) && $module->hasInstaller() ){
			$module->install();
		}
		return $this->redirect();
	}
	function unactiveAction($module){
		smvcModule::moduleSetActive($module,false);
		return $this->redirect();
	}

	function configureAction($module){
		$module = smvcModule::getInstance($module);
		if( !empty($module->_configureDispatch) ){
			return $this->forward($module->_configureDispatch);
		}
		if( empty($module->_configureDispatch) ){
			return $this->msgRedirect('nothing to configure','info');
		}
		show($module,$module->getConfig());
	}
}