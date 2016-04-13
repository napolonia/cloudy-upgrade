<?php
$urlpath="$staticFile/docker";
$docker_pkg = "docker.io";
$dev = "docker0";
$dockerexec = "/usr/bin/docker";
$containerdev = "eth0";
$dockerpsfile = "/var/local/cDistro/plug/resources/monitor-as/ps_image.dockerfile";
$dockerpsimagename = "ps_test";

//peerstreamer
$pspath="/opt/peerstreamer/";
$psprogram="streamer-udp-grapes-static";
define("pspath", "/opt/peerstreamer/");
define("psprogram", "streamer-udp-grapes-static");
$title="Peer Streamer";

//VLC
$vlcpath="/usr/bin/";
$vlcprogram="cvlc";
$vlcuser="nobody";

//Avahi type
$avahi_ps_type="peerstreamer";
$avahi_tahoe_type="tahoe-lafs";

//psutils
$psutils="/../resources/peerstreamer/pscontroller";

//curl
$curlprogram="/usr/bin/curl";

// Aquest paquest no existeix encar   i per tant pot donar algun problema.
$pspackages="peer_web_gui";

// Mapping between service name and docker image
$service_map = array('peerstreamer'=>'ps_test', 'tahoe-lafs'=>'tahoe_test');

function index() {
	global $title, $urlpath, $docker_pkg, $staticFile, $dockerpsimagename;

	$page = hlc(t("docker_title"));
	$page .= hl(t("docker_desc"), 4);

	if (!isPackageInstall($docker_pkg)) {
		$page .= "<div class='alert alert-error text-center'>".t("docker_not_installed")."</div>\n";
		$page .= addButton(array('label'=>t("docker_install"),'class'=>'btn btn-success', 'href'=>"$urlpath/install"));
		return array('type'=>'render','page'=>$page);
	} elseif (!isRunning()) {
		$page .= "<div class='alert alert-error text-center'>".t("docker_not_running")."</div>\n";
		$page .= addButton(array('label'=>t("docker_start"),'class'=>'btn btn-success', 'href'=>"$urlpath/start"));
		$page .= addButton(array('label'=>t('docker_remove'),'class'=>'btn btn-danger', 'href'=>$staticFile.'/default/uninstall/'.$docker_pkg));
		return array('type'=>'render','page'=>$page);
	} else {
		$page .= ptxt(info_docker());
		$page .= "<div class='alert alert-success text-center'>".t("docker_running")."</div>\n";
		if (!isPSCreated()) {
		 $page .= "<div class='alert alert-error text-center'>".t("Peerstreamer Container")."</div>\n";
		 $page .= addButton(array('label'=>t("Create PeerStreamer Image"), 'class'=>'btn btn-success', 'href'=>"$urlpath/create_peerstreamer"));
		} else {
	 	 $page .= "<div class='alert alert-success text-center'>".t("Peerstreamer Image: <br>(".getImageName($dockerpsimagename)." - ".getImageID($dockerpsimagename).")")."</div>\n";
		 $page .= addButton(array('label'=>t("Launch Peerstreamer Source"), 'class'=>'btn btn-success', 'href'=>"$urlpath/ps_form?ps=source"));
		 $page .= addButton(array('label'=>t("Launch Peerstreamer Peer"), 'class'=>'btn btn-success', 'href'=>"$urlpath/ps_form?ps=peer"));
	//	 $page .= addButton(array('label'=>t("TEST"), 'class'=>'btn btn-success', 'href'=>"$urlpath/publish_serv"));
		 $page .= "<p><div><pre>".info_peerstreamer()."</pre></div></p>";
		 $page .= "<br>";
		}
		$page .= "<div class='alert alert-error text-center'>".t("Other Services Containers")."</div>\n";


		$page .= "<p></p>";
		$page .= addButton(array('label'=>t("docker_stop"),'class'=>'btn btn-danger', 'href'=>"$urlpath/stop"));

		return array('type' => 'render','page' => $page);
	}
}

function publish_serv() {
//FOR TESTING: TO BE REMOVED AFTER
	$page = publish_service("peerstreamer", "test", "6411");
	return array('type' => 'render','page' => $page);
}

function unpublish_serv() {
//FOR TESTING: TO BE REMOVED AFTER
	$page = unpublish_service("peerstreamer","6411");
	return array('type' => 'render','page' => $page);

}

function getImageId($str) {
	global $dockerexec;
	$cmd = $dockerexec." images | grep ".$str." | awk '{print $3}'";
	$id = execute_program_shell($cmd)['output'];
	return trim($id);
}

function getImageName($str) {
	global $dockerexec;
	$cmd = $dockerexec." images | grep ".$str."| awk '{print $1}'";
	$name = execute_program_shell($cmd)['output'];
	return trim($name);
}

function isPSCreated() {
	global $dockerexec,$dockerpsimagename;
	$cmd = $dockerexec." images | grep ".$dockerpsimagename;
	$ret=execute_program_shell($cmd);
	if(!empty($ret['output']))
		return true;

	return false;
}

function isRunning(){
	$cmd = "/usr/bin/docker ps";
	$ret = execute_program($cmd);
  return ( $ret['return'] ==  0 );
}
function install(){
  global $title, $urlpath, $docker_pkg;

  $page = package_not_install($docker_pkg,t("docker_desc"));
  return array('type' => 'render','page' => $page);
}
function start() {
	global $urlpath;

	execute_program_detached("service docker start");
	setFlash(t('docker_start_message'),"success");
	return(array('type'=> 'redirect', 'url' => $urlpath));
}
function stop() {
	global $urlpath;

	execute_program_detached("service docker stop");
	setFlash(t('docker_stop_message'),"success");
	return(array('type'=> 'redirect', 'url' => $urlpath));
}

function info_docker(){
	global $dev;

	$cmd = "/sbin/ip addr show dev $dev";
	$ret = execute_program($cmd);
  return ( implode("\n", $ret['output']) );

}

function info_peerstreamer($trunc=""){
	global $dev, $staticFile,$dockerpsimagename, $avahi_ps_type;
	$cmd = "docker ps -f image=${dockerpsimagename} ".$trunc;
	$ret = execute_program($cmd);
	//Foreach line add a button
	$lines = $ret['output'];
	foreach ($lines as $l) {
	 //First line does not count
	  $sid = explode(" ", $l)[0];
	  if($sid == "CONTAINER") { $total .=$l."<br>"; continue;}
	  $l .= " ".addButton(array('label'=>t("Stop"), 'class'=>'btn btn-success', 'href'=>"${staticFile}/docker/stop_service?sid=".$sid))."<br>\n";
	  $total .= $l;
	}

	return $total;
}

function create_peerstreamer(){
	global $dev, $urlpath, $staticFile, $dockerpsfile, $dockerpsimagename;
	if (!file_exists($dockerpsfile)) {
		$page = "<pre>The dockerfile could not be located, your Cloudy version may need to be updated.</pre>";
		$page .= addButton(array('label'=>t('Back'),'href'=>$staticFile.'/docker'));
		return array('type' => 'render','page' => $page);
	}

	//Needs to be run as root most probably
	$cmd = "docker build -t ${dockerpsimagename} - < ".$dockerpsfile;
	execute_program_detached($cmd);

	$page = "<pre>Building of Peerstreamer Image has begun in background, it may take some time to finish.</pre>";
	$page .= addButton(array('label'=>t('Back'),'href'=>$staticFile.'/docker'));

	return array('type' => 'render','page' => $page);
}

function ps_form() {
	global $urlpath;
	global $paspath,$title;
        global $staticFile;
	$page = "";
	$ps=$_GET['ps'];

	if ($ps == "source") {
	$page = hlc(t($title));
	$page .= hlc(t('Publish a video stream'),2);
	$page .= par(t("Please write a stream source"));
        $page .= par(t("If the URL is a rtmp, please make sure to introduce all the requiered parameters separated ONLY by a simple comma."));
        $page .= createForm(array('class'=>'form-horizontal','action'=>$urlpath.'/ps_publish_post'));
	$page .= "<input type='hidden' name='ps' value='source'>";
        $page .= addInput('url',t('URL Source'),'',array('class'=>'input-xxlarge'));
        $page .= addInput('port',t('Port Address'));
        $page .= addInput('description',t('Describe this channel'));
        $page .= addSubmit(array('label'=>t('Publish'),'class'=>'btn btn-primary'));
        $page .= addButton(array('label'=>t('Cancel'),'href'=>$staticFile.'/docker'));

	} else if ($ps == "peer") {
	$page = hlc(t($title));
        $page .= hlc(t('Connect to a Peer'),2);
        $page .= par(t("You can join a stream through a Peer in the network, or you can find channels in the avahi menu option."));
        $page .= createForm(array('class'=>'form-horizontal','action'=>$urlpath.'/ps_publish_post'));
	$page .= "<input type='hidden' name='ps' value='peer'>";
        $page .= t('Peer:');
        $page .= addInput('ip',t('IP Address'),$peerip);
        $page .= addInput('port',t('Port Address'),$peerport);
        $page .= t('You:');
        $page .= addCheckbox('type', t('Server Type'), array('RTSP'=>t('Create RTSP Server'),'UDP'=>t('Send to UDP Server')));
        $page .= addInput('myport',t('Port'));
        $page .= addSubmit(array('label'=>t('Connect'),'class'=>'btn btn-primary'));
        $page .= addButton(array('label'=>t('Cancel'),'href'=>$staticFile.'/docker'));

	 $page .= "";
	}
	return(array('type'=>'render','page'=> $page));
}

function ps_publish_post() {
	global $urlpath;
	$url = $_POST['url'];
        $port = $_POST['port'];
        $description = $_POST['description'];
        $ip = $_POST['ip'];
	$ps = $_POST['ps'];

        $page = "<pre>";
        $page .= start_peerstreamer($url,$ip,$port,$description,$ps);
        $page .= "</pre>";

        return(array('type' => 'render','page' => $page));
}


function start_peerstreamer($url,$ip,$port,$description,$ps){
	global $urlpath,$staticFile,$containerdev,$dockerexec,$dockerpsimagename,$avahi_ps_type;
	$myip = "127.0.0.1"; //should be either the 10. range ip or the container ip !!!
	$endcmd = "&& /bin/bash"; //The end command has to stay up in foreground for docker to continue the container

	if($ps == "source")
	$cmds = "publish ".$url." ".$port." ".$containerdev." ".$description; //device hardcoded!!!

	$type = $_POST['type'];
	$iport = $_POST['myport'];
	if($ps == "peer" && $type == "RTSP")
	$cmds = "connectrtsp ${ip} ${port} ${iport} ${myip} ${containerdev}"; //to see if its correct

	if($ps == "peer" && $type == "UDP")
	$cmds = "connectudp ${ip} ${port} ${myip} ${iport} ${containerdev}"; //to see if its correct

	//Exporting ports either the source or the peer iport
	$expPorts = "-p ${port}:${port}";
	if(isset($iport)) $expPorts = "-p ${iport}:${iport}";
	
	//IF /var/run/pspeers.conf is not there than we need to create otherwise it will bug
	$startcmd = "touch /var/run/pspeers.conf &&";

	$cmd = $dockerexec." run -tid ${expPorts} ${dockerpsimagename} /bin/bash -c '".$startcmd." /bin/bash /var/local/cDistro/plug/resources/peerstreamer/pscontroller ".$cmds." ".$endcmd."'";
	$ret = execute_program_shell($cmd);
	setFlash(t('Docker Peerstreamer Container'),"success");

	$page = "CONTAINERID: ".trim($ret['output'])."<br>";

	//Need to account for errors and not publish!!!!!
	if(isset($iport)) $port = $iport;
	if(empty($description)) $description = "Republishing";

	$page .= publish_service($avahi_ps_type,$description,$port)."<br>";

	$page .= addButton(array('label'=>t('Back'),'href'=>$staticFile.'/docker'));
	return $page;
}

function publish_service($service, $description, $port, $opts=array()) {
	global $dev,$dockerexec;
	//here we should publish services as was before
	$temp="";
	$einfo="";
	if(!empty($opts)) {
	 foreach($opts as $val)
	 	$einfo.=$val.",";
	}
	//now we get the extra information from service
	// two things, internal and external:

	//Internal:
	$sid=trim(getContainerId($port));
	$cmd = $dockerexec." exec ".$sid." ".getExtraCmd($service,$port,$opts);
	$iobj = execute_program_shell($cmd)['output'];

	//External:
	$cmd = $dockerexec." inspect ".$sid." | jq -c ."; //From here we can take out information
	$cmd = "/bin/bash /var/local/cDistro/plug/resources/monitor-as/common.sh gather_information docker_".$service." ".$sid;
	$eobj = execute_program_shell($cmd)['output'];

	//Need to merge both arrays as is, array_merge does not do that properly
	if($iobj[strlen($iobj)-1]=='}')
	 $ret = substr($iobj, 0, -1).",".substr($eobj,1);
	else
	 $ret = substr($iobj, 0, -2).",".substr($eobj,1);

	$ret = trim(strtr($ret, array(','=>';')));
	$einfo .= "einfo=".addslashes(addslashes($ret));

	$temp=avahi_publish($service, $description, $port, $einfo);

	//$temp .= addButton(array('label'=>t('Back'),'href'=>$staticFile.'/docker'));
	return ptxt($temp);
}

function unpublish_service($service, $port) {
	global $dev, $dockerexec;

	$temp="";
//	$sid=trim(getContainerId($port));
	//May need necessary update to container information on Monitor-AS
	//instead of just unpublishing
	$temp .= avahi_unpublish($service,$port);
//	$temp .= addButton(array('label'=>t('Back'),'href'=>$staticFile.'/docker'));
	return ptxt($temp);
}

function getContainerId($port) {
	global $dockerexec;
	//each container is different by the ports they export
	$cmd=$dockerexec." ps | grep ".$port." |awk '{print $1}'";
	$id = execute_program_shell($cmd)['output'];

	return trim($id);
}

function getExtraCmd($service, $port="", $opts="") {
	//Each service has its own way to gather information
	switch ($service) {
	case 'peerstreamer':
	//getting extra info using the common.sh file?
	return "/bin/bash /var/local/cDistro/plug/resources/monitor-as/common.sh gather_information peerstreamer ".$port;
	case 'tahoe-lafs':
	//Not sure yet, either common.sh or tahoe-lafs.service
	return "/bin/bash /var/local/cDistro/plug/resources/monitor-as/common.sh gather_information tahoe-lafs";
	case 'synchthing':
	///bin/bash /var/local/cDistro/plug/resources/monitor-as/common.sh gather_information synchthing xml_config_file_inside_container
	return "";
	default:
	//maybe has default it should be starting time?
	return "";
	}

}

function getServicePort($sid) {
	$cmd = "docker inspect ".$sid." | jq .[].NetworkSettings.Ports | grep tcp | cut -d'/' -f1|cut -d'\"' -f2 ";

	$ret = execute_program_shell($cmd)['output'];

	return trim($ret);
}

function getServiceBySID($sid) {
	global $service_map;
	$service = trim(execute_program_shell("docker ps|grep ".$sid." | awk '{print $2}'|cut -d':' -f1")['output']);
	// this gets the docker image name, we need to associate image name to service name
	foreach ($service_map as $k => $v) {
		if($v == $service)
		return $k;
	}

	return null;
}

function stop_service() {
	global $dev, $staticFile;

	$sid = $_GET['sid'];
	$service = getServiceBySID($sid);
	$port = getServicePort($sid);

	//IF service == null than there is no service available!

	$page = "";
	//Now we need to stop service by docker stop $sid / docker rm $sid (dont wont the container to stay in drive)
	//unpublish the service as before avahi_unpublish(...); 
	//the service itself may need some command to stop so.. docker exec $sid /bin/bash -c 'stop service from inside container'
	//before stopping the container
	$page .= "<pre>Service ${service} with SID ${sid} on Port ${port} has been stopped and unpublished</pre>";

	$cmd = "docker stop ".$sid;
	$cmd1 = "docker rm ".$sid;
	$page .= "<p> For now will just: docker stop <containerid>, but this should be changed in future</p>";
	
	//Unpublishing service from avahi/serf
	$page .= unpublish_service($service, $port);
	$page .= ptxt(execute_program_shell($cmd)['output']);
	//For now we do not remove containers, should be changed because of harddisk space
//	$page .= ptxt(execute_program_shell($cmd1)['output']);

	$page .= addButton(array('label'=>t('Back'),'href'=>$staticFile.'/docker'));

	return array('type'=>'render','page'=>$page);

}
