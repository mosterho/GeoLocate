<?php

class cls_geolocateapi{
	public $response;
	function __construct($arg_IP){

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
}


/*
// Begin mainline... incoming argument is IPv4 address
*/

if(isset($argv[1])){
	$arg_incoming = $argv[1];
}
else{
	$arg_incoming = '8.8.8.8';
}
$wrk_cls_api = new cls_geolocateapi($arg_incoming);

### Produce output, whether it's on a command prompt or return to a calling program
if(php_sapi_name() == 'cli'){
  var_dump($wrk_cls_api->response);
}
else {
  return $wrk_cls_api->response;
}


?>
