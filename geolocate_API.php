<?php

// This module will determine the IP address of the calling/client IP address.
// Central to this module is the call to the fct_retrieve_IP_info function and its
// ip2location.io external module call. The ip2location.io website will return a
// client's name, address, city, latitude and longitude if it is
// an external WAN IP that calls this program. If if cannot determine if a WAN IP called it (which is not likely),
// A seperate function will determine if a LAN IP (e.g., 127.0.0.1, etc.) called it.
// The IP's latitude and longitude is then compared to a list of approved/whitelist locations and IPs that can use
// the parent/calling program. This information is a JSON file "latlong2.json".
// Set the class's "whitelist" public variable to True/False.

// **** always log the calling client IP address to the "error handler" function.

// The fct_geolocate_comprehensive function will call all the necessary functions in this program
// for ease for the coder, rather than calling each cuntion individually. The coder just needs to instantiate
// a new class in the calling program and execute the fct_geolocate_comprehensive funntion.
// Please see either geolocate_CLI.php or geolocate_CLI_V2.php for examples.



class cls_geolocateapi {
	public $my_key;  //geolocate web api key.
	public $response;  // Response to call to geolocate API (city, state, latitude/longitude. etc)
	public $my_decode_file_json;  // JSON file containing whitelisted latitude/longitude and verbose setting.
	public $client_IP;  // The client WAN IP determined if none was pasased in to program.
	public $LAN_IP = False; // Is this a LAN IP
	public $whitelist;  // Whitelist from JSON file
	public $is_verbose;  // Will produce limited debugging information.
	public $wrk_cls_error_handler;
	public $geolocate_available = False;  // Used for logging

	function __construct(){
		$this->my_key = file_get_contents("/home/ESIS/GeoLocate/keys/geolocate.key"); # This is a simple text file, not JSON
		$my_file_json = file_get_contents("/home/ESIS/GeoLocate/keys/latlong2.json");
		$this->my_decode_file_json = json_decode($my_file_json);
		$this->whitelist = $this->my_decode_file_json->{"whitelist_LATLONG"};
		$this->is_verbose = $this->my_decode_file_json->{"whitelist_verbose"};

		$includestr = '/home/ESIS/errorhandler/error_handler.php';
		if(file_exists($includestr)){
			include $includestr;
			$this->wrk_cls_error_handler = new cls_error_handler();
			#var_dump($includestr);
			$this->geolocate_available = True;
		}
		if($this->is_verbose){
			echo '1. Within geolocate_API, processed __construct'.PHP_EOL;
		}
	}



	// This function will write information to the log via the error_handler "logmessage" php module.
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
			$this->wrk_cls_error_handler->logmessage($msg);
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
		$strcspnpos = strcspn($this->client_IP, ':');
		if(is_numeric($strcspnpos)){
			$str_replace = substr_replace($this->client_IP,'',$strcspnpos);
		}
		if($this->is_verbose){
			echo '<br>2. Within fct_grab_client_IP function FIRST ECHO, the raw client IP retrieved is: ';
			echo $this->client_IP.PHP_EOL;
			echo '<br>3. with fct_grab_client_IP function, the : position found is: '.$strcspnpos.PHP_EOL;
			echo '<br>4. within fct_grab_client_IP function SECOND ECHO, the finished IP obtained is: '.$str_replace.PHP_EOL;
		}
		return $str_replace;  // It's possible this can be an empty variable, esp. if a LAN IP.
	}

	// The following function is straight from ip2location.io documentation
	// This will validate the geolocate key and retrieve geolocation information.
	public function fct_retrieve_IP_info($arg_IP){
		try{
			$ch = curl_init();
			if (!$ch) {
				#die("<br>Within geolocate_API fct_retrieve_IP_info module, couldn't initialize a cURL handle");
				echo "<br>Within geolocate_API fct_retrieve_IP_info module, couldn't initialize a cURL handle";
			}
			curl_setopt($ch, CURLOPT_URL, "https://api.ip2location.io/?" . http_build_query([
				"ip"      => $arg_IP,
				"key"     => $this->my_key,
				"format"  => "json",
			]));
			curl_setopt($ch, CURLOPT_FAILONERROR, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($ch, CURLOPT_TIMEOUT, 10);
			$this->response = curl_exec($ch);  // The return value of geolocation information will be in JSON

			if (empty($this->response)) {
				// some kind of an error happened
				echo '<br> Within geolocate_API fct_retrieve_IP_info, According to curl_exec, the this->response is empty... <br>';
				#die(curl_error($ch));
				curl_close($ch); // close cURL handler
			}
			else {
				$info = curl_getinfo($ch);
				curl_close($ch); // close cURL handler
				if (empty($info['http_code'])) {
				#die("Within geolocate_API fct_retrieve_IP_info, No HTTP code was returned");
				echo "Within geolocate_API fct_retrieve_IP_info, No HTTP code was returned";
				}
				if($this->is_verbose) {
					echo "<br>5. within fct_retrieve_IP_info, the curl_getinfo result with http_code: ";
					echo $info['http_code'] . " " . $http_codes[$info['http_code']];
				}
			}

			if($this->is_verbose){
				echo '<br>6. at the end of fct_retrieve_IP_info function, the raw CH value for the cURL is: '.var_dump($ch).PHP_EOL;
				echo '<br> AND the value of this->response is '.$this->response;
			}
		}
		catch(Exception $e){
			echo $e;
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
						echo '<br>7. within fct_whitelist_validation function, a whitelisted IP is found, the tempobj obtained is: '.$tempobj.PHP_EOL;
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
		$this->LAN_IP = False;
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
			echo '<br>8. within comprehensive function, IP obtained is: '.$arg_incoming;
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
			echo '<br>9. JSON verbose setting: '.$this->is_verbose;
			echo '<br>10. Is IP whitelisted?: '.$is_whitelisted;
			echo '<br>11. Within geolocation check, your IP retrieved is: '.$this->client_IP.' additional IP is: '.$arg_incoming.' <br>and general info (response) is: ';
			var_dump($this->response);
			echo '<br>';

		}
		// Comment the following line to NOT write to syslog.
		if($this->geolocate_available == True){
			$this->fct_geolog($is_whitelisted);
		}
		else{
			echo '<br>12. ******************** Geolocate JSON file does not exist'.PHP_EOL;
		}
		return $is_whitelisted;
	}
}


?>
