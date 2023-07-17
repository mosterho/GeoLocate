<?php

include '/var/www/Geolocate/errorhandler/error_handler.php';  // Optional, this will write an entry to the syslog of the web server.

class cls_geolocateapi {
	public $my_key;  //geolocate web api key.
	public $response;  // Response to call to geolocate API (city, state, latitude/longitude. etc)
	public $my_decode_file_json;  // JSON file containing whitelisted latitude/longitude and verbose setting.
	public $client_IP;  // The client WAN IP determined if none was pasased in to program.
	public $whitelist;  // Whitelist from JSON file
	public $is_verbose;  // Will produce limited debugging information.

	function __construct(){
		$this->my_key = file_get_contents("/var/www/Geolocate/keys/geolocate.key"); # This is a simple text file, not JSON
		$my_file_json = file_get_contents("/var/www/Geolocate/keys/latlong2.json");
		$this->my_decode_file_json = json_decode($my_file_json);
		$this->whitelist = $this->my_decode_file_json->{"whitelist_LATLONG"};
		$this->is_verbose = $this->my_decode_file_json->{"whitelist_verbose"};
	}

	function fct_geolog($arg_is_whitelisted){
		// for the white listed value, literally pass in True or False, not the boolean.
		if($arg_is_whitelisted == true){
			$wrk_whitelisted = 'True';
		}
		else{
			$wrk_whitelisted = 'False';
		}
		$msg = ' whitelisted: '.$wrk_whitelisted.' Response: '.$this->response;
		// logmessage is a function in the 'error_handlerxxx.php' program.
		try{
			logmessage($msg);
		}
		catch(Exception $e){
			echo $e;
		}
	}

	// This function is copied from some website
	// This will determine which client WAN IP is the best to use.
	public function fct_grab_client_IP(){
		if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
			//ip from share internet
			$this->client_IP = $_SERVER['HTTP_CLIENT_IP'];
		} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			//ip pass from proxy
			$this->client_IP = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} elseif(!empty($_SERVER['REMOTE_ADDR'])) {
			$this->client_IP = $_SERVER['REMOTE_ADDR'];
		}
		return $this->client_IP;  // It's possible this can be an empty variable, esp. if a LAN IP.
	}

	// The following function is straight from ip2location.io documentation
	// This will validate the geolocate key and retrieve geolocation information.
	public function fct_retrieve_IP_info($arg_IP){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'https://api.ip2location.io/?' . http_build_query([
			'ip'      => $arg_IP,
			'key'     => $this->my_key,
			'format'  => 'jso',
		]));
		curl_setopt($ch, CURLOPT_FAILONERROR, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		$this->response = curl_exec($ch);  // The return value of geolocation information will be in JSON
	}

	// This function will read the JSON file to determine if
	// the IP address is valid within the whitelisted latitude/longitude specs.
	public function fct_whitelist_validation(){
		$tempobj = json_decode($this->response);  // convert returned geolocate information in JSON to php object.
		// Compare the response object's latitude and longitude against the "northwest" and "southeast" rectangle.
		// Format of whitelist array entries is: 1.northwest latitude, 2.northwest longitude, 3.southeast latitude, 4.southeast longitude.
		if(isset($tempobj)){
			foreach($this->whitelist as $key2 => $value2){
				if($tempobj->latitude <= $value2[1] and $tempobj->latitude >= $value2[3] and $tempobj->longitude >= $value2[2] and $tempobj->longitude <= $value2[4]){
					return True;
				}
			}
		}
		return False;
	}

	// This function will perform a basic check for LAN IPs that are passed in
	public function fct_test_LAN($arg_IP){
		if(substr($arg_IP,0,10) == '192.168.0.' or substr($arg_IP,0,7) == '172.16.' or substr($arg_IP,0,3) == '10.' or substr($arg_IP,0,9) == '127.0.0.1'){
			return True;
		}
		else{
			return False;
		}
	}

	// The followikng function will call the above functions.
	// This will make a caller's method calling these routines easier.
	function fct_geolocate_comprehensive($arg_IP=''){
		if($arg_IP == ''){
			$arg_incoming = $this->fct_grab_client_IP();
		}
		else{
			$arg_incoming = $arg_IP;
		}
		// If this is a LAN IP, skip testing
		$is_LANIP = $this->fct_test_LAN($arg_incoming);
		#var_dump($is_LANIP);
		if($is_LANIP == true){
			$is_whitelisted = true;
		}
		// if not a LAN IP, retrieve IP info.
		else{
			$this->fct_retrieve_IP_info($arg_incoming);
			if(isset($this->response)){
				$is_whitelisted = $this->fct_whitelist_validation();
			}
			else{
				$is_whitelisted = false;
			}
		}

		if($this->is_verbose){
			echo 'JSON verbose setting: '.$this->is_verbose?'True':'False';
			echo 'Within geolocation check, your IP is: '.$arg_incoming.' and general info is: '.$this->response;
		}
		// Comment the following line to NOT write to syslog.
		$this->fct_geolog($is_whitelisted);
		return $is_whitelisted;
	}
}


?>
