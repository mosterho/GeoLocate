<?php

class cls_geolocateapi {
	public $my_key;  //geolocate web api key.
	public $response;  // Response to call to geolocate API (city, state, latitude/longitude. etc)
	public $my_decode_file_json;  // JSON file containing whitelisted latitude/longitude and verbose setting.
	public $client_IP;  // The client WAN IP determined if none was pasased in to program.
	public $LAN_IP = False; // Is this a LAN IP
	public $whitelist;  // Whitelist from JSON file
	public $is_verbose;  // Will produce limited debugging information.
	public $geolocate_available = False;  // Used for logging

	function __construct(){
		$this->my_key = file_get_contents("/home/site/GeoLocate/keys/geolocate.key"); # This is a simple text file, not JSON
		$my_file_json = file_get_contents("/home/site/GeoLocate/keys/latlong2.json");
		$this->my_decode_file_json = json_decode($my_file_json);
		$this->whitelist = $this->my_decode_file_json->{"whitelist_LATLONG"};
		$this->is_verbose = $this->my_decode_file_json->{"whitelist_verbose"};

		$includestr = '/home/site/errorhandler/error_handler.php';
		if(file_exists($includestr)){
			include $includestr;
			#var_dump($includestr);
			$this->geolocate_available = True;
			if($this->is_verbose){
				echo 'Within geolocate_API, processed __construct'.PHP_EOL;
			}
		}
	}

	// This function will write information to the syslog via the error_handler "logmessage" php module.
	// the "logmessage" function is part of the include '/var/www/Geolocate/errorhandler/error_handler.php' code.
	function fct_geolog($arg_is_whitelisted){
		// To build the actual message, for the white listed value, literally pass in True or False, not the boolean.
		if($arg_is_whitelisted == true){
			$wrk_whitelisted = 'True';
		}
		else{
			$wrk_whitelisted = 'False';
		}
		$msg = ' whitelisted:'.$wrk_whitelisted.' Response:'.$this->response.PHP_EOL;
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
		// If an IP is determined, strip away any port info (e.g., 123.456.789.123:12345)
		echo 'Within fct_grab_client_IP function FIRST ECHO, the Client IP retrieved is: ';
		echo $this->client_IP;
		$strcspnpos = strcspn($this->client_IP, ':');
		echo 'with fct_grab_client_IP function, the : position found is: '.$strcspnpos.PHP_EOL;
		$str_replace = substr_replace($this->client_IP,'',$strcspnpos);
		if($this->is_verbose){
			echo 'within fct_grab_client_IP function SECOND ECHO, the raw IP obtained is: '.$this->client_IP.PHP_EOL;
		}
		return $str_replace;  // It's possible this can be an empty variable, esp. if a LAN IP.
	}

	// The following function is straight from ip2location.io documentation
	// This will validate the geolocate key and retrieve geolocation information.
	public function fct_retrieve_IP_info($arg_IP){
		$ch = curl_init();
		if (!$ch) {
    	die("Couldn't initialize a cURL handle");
		}
		curl_setopt($ch, CURLOPT_URL, 'https://api.ip2location.io/?' . http_build_query([
			'ip'      => $arg_IP,
			'key'     => $this->my_key,
			'format'  => 'json',
		]));
		curl_setopt($ch, CURLOPT_FAILONERROR, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		$this->response = curl_exec($ch);  // The return value of geolocation information will be in JSON

		if (empty($this->response)) {
			// some kind of an error happened
			die(curl_error($ch));
			curl_close($ch); // close cURL handler
		}
		else {
			$info = curl_getinfo($ch);
			curl_close($ch); // close cURL handler

			if (empty($info['http_code'])) {
				die("No HTTP code was returned");
			}
			else {
				// load the HTTP codes
				#$http_codes = parse_ini_file("path/to/the/ini/file/I/pasted/above");
				// echo results
				echo "The server responded: <br />";
				#echo $info['http_code'] . " " . $http_codes[$info['http_code']];
				echo $info['http_code'] . " " ;
			}
		}

		if($this->is_verbose){
			echo 'within fct_retrieve_IP_info function, the raw CH value for the cURL is: '.var_dump($ch).PHP_EOL;
		}
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
					if($this->is_verbose){
						echo 'within fct_whitelist_validation function and a whitelisted IP is found, the tempobj obtained is: '.$tempobj.PHP_EOL;
					}
					return True;
				}
			}
		}
		return False;
	}

	// This function will perform a basic check for LAN IPs that are passed in
	public function fct_test_LAN($arg_IP){
		if($arg_IP != ''){
			if(substr($arg_IP,0,10) == '192.168.0.' or substr($arg_IP,0,7) == '172.16.' or substr($arg_IP,0,3) == '10.' or substr($arg_IP,0,9) == '127.0.0.1'){
				$this->LAN_IP = True;
				return True;
			}
		}
		return False;
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
		if($this->is_verbose){
			echo 'within comprehensive function, IP obtained is: '.$arg_incoming;
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
			echo '<br>JSON verbose setting: '.$this->is_verbose;
			echo '<br>Is IP whitelisted?: '.$is_whitelisted;
			echo '<br>Within geolocation check, your IP retrieved is: '.$this->client_IP.' additional IP is: '.$arg_incoming.' <br>and general info (response) is: ';
			var_dump($this->response);
			echo '<br>';

		}
		// Comment the following line to NOT write to syslog.
		if($this->geolocate_available == True){
			$this->fct_geolog($is_whitelisted);
		}
		else{
			echo 'Geolocate JSON file does not exist'.PHP_EOL;
		}
		return $is_whitelisted;
	}
}


?>
