<?php

class cls_geolocateapi {
	public $response;
	public $client_IP;

	// This function is copied from some website
	function fct_grab_client_IP(){
		if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
			//ip from share internet
			$this->client_IP = $_SERVER['HTTP_CLIENT_IP'];
		} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			//ip pass from proxy
			$this->client_IP = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} elseif(!empty($_SERVER['REMOTE_ADDR'])) {
			$this->client_IP = $_SERVER['REMOTE_ADDR'];
		}
		return $this->client_IP;  // It's possible this can be an empty variable.
	}

	// The following function is straight from ip2location.io documentation
	function fct_retrieve_IP_info($arg_IP){
		$my_key = file_get_contents("./keys/geolocate.key"); # This is a simple text file, not JSON
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'https://api.ip2location.io/?' . http_build_query([
			'ip'      => $arg_IP,
			'key'     => $my_key,
			'format'  => 'json',
		]));
		curl_setopt($ch, CURLOPT_FAILONERROR, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		$this->response = curl_exec($ch);  // The return value will be in JSON
	}

	function fct_whitelist_validation(){
		$my_file_json = file_get_contents("./keys/latlong2.json");
		$my_decode_file_json = json_decode($my_file_json);
		$tempobj = json_decode($this->response);
		#var_dump($my_file_json);
		#var_dump($my_decode_file_json);
		#var_dump($tempobj);
		#echo $tempobj->latitude;
		#echo $tempobj->longitude;
		foreach($my_decode_file_json as $key => $value){
			foreach($value as $key2 => $value2){
				#var_dump($tempobj->latitude);
				#var_dump($value2[0]);
				#var_dump($value2[1]);
				#var_dump($value2[3]);
				#var_dump($tempobj->longitude);
				#var_dump($value2[2]);
				#var_dump($value2[4]);
				if($tempobj->latitude <= $value2[1] and $tempobj->latitude >= $value2[3] and $tempobj->longitude >= $value2[2] and $tempobj->longitude <= $value2[4]){
					return True;
				}
			}
		}
		return False;
	}
}



/*
// Begin mainline... incoming argument is IPv4 address
*/

$wrk_cls_api = new cls_geolocateapi();

$arguments = getopt("i::v");
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

#var_dump($arg_incoming);
#var_dump($verbose);

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
		echo 'Whitelisted return value?: '.$is_whitelisted;
	}
}
else {
	return $wrk_cls_api->response;
}

?>
