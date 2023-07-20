<?php

// This is just another way to call the geolocate module and process the results.

/*
// Begin mainline... incoming argument is IPv4 address and verbose flag
*/

try{
	include '/var/www/Geolocate/geolocate_API.php';
	$cls_geolocate = new cls_geolocateapi();
	$is_whitelisted = $cls_geolocate->fct_geolocate_comprehensive();
	if(!$is_whitelisted){
		#echo "Is IP whitelisted?: ".$is_whitelisted?'True':'False'.'  response?------'.$cls_geolocate->response;
		#var_dump($cls_geolocate->response);
		echo "We're having trouble with the web page...";
		exit();
	}
}
catch(Exception $e){
	echo 'Error within Geolocate module: '.$e;
}

?>
