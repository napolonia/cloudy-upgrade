<?php

// Config files
require "config/global.php";
require "config/menus.php";
require "core.php";
require "templates/form.php";
require "templates/view.php";
require "templates/errors.php";
require "templates/utilio.php";
require "templates/session.php";


$css = array('bootstrap.min','bootstrap-responsive.min', 'main');
$js = array('jquery-1.11.0.min','bootstrap.min','main');


// Default 
$controller = "default";
$action="index";
$method=strtolower($_SERVER['REQUEST_METHOD']);

if (isset($Parameters) && is_array($Parameters) && isset($Parameters[0]) && file_exists($documentPath."/plug/".$Parameters[0].".php")){
	$controller = $Parameters[0];
	array_shift($Parameters);
}
// Load Controller

if (isset($Parameters) && isset($Parameters[0])) {
	$action=$Parameters[0];
	array_shift($Parameters);
}

require $documentPath."/plug/".$controller.".php";

if (!is_array($Parameters)){
	$Parameters=array();
} 

// Add method type to action
if(function_exists($action."_".$method)){
	$action=$action."_".$method;
}

if (!function_exists($action)) {
	array_unshift($Parameters, $action);
	array_unshift($Parameters, $controller);	
	$controller = "default";
	$action="notFunctionExist";
}
$cb = call_user_func_array($action,$Parameters);

switch ( $cb['type'] ){

case 'render':
	require "templates/header.php";
	require "templates/menu.php";
	require "templates/begincontent.php";
	require "templates/flash.php";

	echo $cb['page'];

	require "templates/endcontent.php";
	require "templates/footer.php";
	require "templates/endpage.php";
	break;
case 'redirect':
	//Header to redirect!
	header('Location: '.$cb['url'], true, 301);
	break;

default:
	callbackReturnUnknow($cb['type']);
	break;	

}

ob_flush();
?>