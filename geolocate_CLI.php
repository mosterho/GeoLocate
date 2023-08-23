<?php

// This is one way to call the geolocate module and process the results. This does NOT call all of
// the modules that is included in the fct_geolocate_comprehensive function.

/*
// Begin mainline... incoming argument is IPv4 address and verbose flag
*/

include 'geolocate_API.php';

$wrk_cls_api = new cls_geolocateapi();

$arguments = getopt("i::v");  //IP address is optional, verbose flag is optional or just plain "-v"
#var_dump($arguments);
if(isset($arguments['i'])){
	$arg_incoming = $arguments['i'];
}
else{
	$arg_incoming = $wrk_cls_api->fct_grab_client_IP();
}

// This will reverse what normally happens with getopt and no value passed with the -v argument.
if(isset($arguments['v'])){
	$verbose = True;
}
else{
	$verbose = False;
}

$wrk_cls_api->fct_retrieve_IP_info($arg_incoming);
#var_dump($wrk_cls_api->response);
if(isset($wrk_cls_api->response)){
	$is_whitelisted = $wrk_cls_api->fct_whitelist_validation();
}
else{
	$is_whitelisted = 'False';
}
### Produce output, whether it's on a command prompt or return to a calling program
if(php_sapi_name() == 'cli'){
	if($verbose){
		echo 'Response within the class object: '.$wrk_cls_api->response;
		echo 'Whitelisted return value?: ';
		echo $is_whitelisted?'True':'False';
		#echo 'Dump of class: '.var_dump($wrk_cls_api).PHP_EOL;
		echo PHP_EOL;
	}
}
else {
	return $wrk_cls_api->response;
}

?>
