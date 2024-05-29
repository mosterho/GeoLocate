<?php

// This is just another way to call the geolocate module and process the results.

try{
	include 'geolocate_API.php';
	$cls_geolocate = new cls_geolocateapi();
	$is_whitelisted = $cls_geolocate->fct_geolocate_comprehensive();
	echo '<br>in the Geolocate web application -----------------------';
	if($is_whitelisted == false){
		echo "<br>We're having trouble with the web page...<br>";
	}
	else{
		echo "<br>Valid Geo data: <br>";
	}
	echo "<br>Is IP whitelisted?: ".$is_whitelisted;
	echo '<br>Client IP: '.$cls_geolocate->client_IP.PHP_EOL;
	echo '<br>class response variable?---'.$cls_geolocate->response;
	echo '<br>';
	var_dump($cls_geolocate);

}
catch(Exception $e){
	echo 'Error within Geolocate module: '.$e;
}

?>
