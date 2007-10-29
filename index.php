<?php
/**
* front controller for admin site
*/
error_reporting(E_ALL | E_STRICT);
ini_set('default_charset','utf-8');
date_default_timezone_set('Europe/Paris');

#- definition du contexte d'execution
define('FRONT_NAME','admin');
require '../includes/fx-common.php';

#- demmarage de la session si necesaire (on precise le contexte d'execution)
session_start(FRONT_NAME);

# set les repertoires de vue par défaut
abstractController::$defaultViewClass = 'baseView';
abstractController::$defaultViewDirs  = array(ROOT_DIR.'/'.(defined('FRONT_NAME')?FRONT_NAME.'/':'').'views');

#- parametrage du layout par défaut
baseView::$defaultLayout = array(
  'header.tpl.php',
  ':controller_:action.tpl.php|default_:action.tpl.php',
  'footer.tpl.php'
);

#- Recuperation des controllers et actions à executer.
$_controller = isset($_POST['ctrl'])?$_POST['ctrl']:(isset($_GET['ctrl'])?$_GET['ctrl']:'index');
$_action     = isset($_POST['action'])?$_POST['action']:(isset($_GET['action'])?$_GET['action']:'index');

#- instanciation du controller
try{
  $cname = $_controller.'Controller';
  $controller = new $cname;
}catch(Exception $e){
  $controller = new errorController($e);
}
#- appelle de l'action
try{
  $controller->$_action();
}catch(Exception $e){
  $controller = new errorController($e);
}
