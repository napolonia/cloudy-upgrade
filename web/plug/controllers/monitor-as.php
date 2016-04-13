<?php
//controllers/monitor-as.php

$webpage="../../check-serf/extra.php";
$script="../resources/monitor-as/common.sh";
$execpath="/../../check-serf";



function _installed_monitor_as(){
	global $execpath,$webpage;
	if(is_file($webpage) && is_file($script))
	return "true";

	return "false";
}

function _run_monitor_as(){
	global $graph;
	return "true";
}

function index() {
	global $webpage;

	$page = "";
	$buttons = "";

	$page .= hlc(t("Monitor as a Service"));
	$page .= hl(t("Monitor"),4);
	$page .= par(t("This will generate a graphic of the SERF network, giving relevant information about the status of the nodes and the services."));
	
	if(!_installed_monitor_as()){
	 	$page .= "<div class='alert alert-error text-center'>".t("Monitor As a Service not installed yet")."</div>\n";
		$page .= par(t("How to install?<br>Considerations about installing and running"));
		$buttons .= addButton(array('label'=>t("Install"),'class'=>'btn btn-success', 'href'=>"$urlpath/install"));
	} else {
	$page .= "<div class='alert alert-success text-center'>".t("Monitor as a Service installed")."</div>\n";
	$buttons .= addButton(array('label'=>t("Show Graph"),'class'=>'btn btn-primary', 'type'=>'redirect','href'=>"$urlpath/monitor-as/graph_show"));
	$buttons .= addButton(array('label'=>t("Show Graph E"),'class'=>'btn btn-primary','href'=>"$urlpath/check-serf/extra.php"));

	}

	$page .= $buttons;
	return(array('type' => 'render','page' => $page));
}

function graph_show() {
	global $webpage, $script, $css, $js, $js_end;

	$page ="";
	$buttons = "";
	
//	require "../../check-serf/extra.php";

	$page .= hlc(t("Monitor as a Service"));
        $page .= hl(t("Monitor"),4);
//        $page .= par(t("This will generate a graphic of the SERF network, giving relevant information about the status of the nodes and the services."));

	$page .= "<div>Getting graph from ".$webpage."</div>\n";
	
	$page .= "<div id='canvas' class=''>GRAPH: </div>";
	//$js[]=givejs($js);

	//$page .= $js;

	$page .= $buttons;
	return(array('type'=>'render','page'=>$page));
}
