<?php
//
function logmessage($arg_message='A default message') {

  // Build a message and utilize the syslog function.
  // https://datatracker.ietf.org/doc/html/rfc5424#section-6.2.1
  // Determine message priority "prival" based on syslog format. Facility "LOCAL0" and severity "notice".

  $my_json = file_get_contents("/var/www/Geolocate/errorhandler/error_handler.json");
  $my_decoded_json = json_decode($my_json);
  $message_defaults = $my_decoded_json->{"message_defaults"};

  $full_message = '';
  if(isset($_SERVER['SERVER_NAME'])){
    $full_message .= $_SERVER['SERVER_NAME'];
  }
  else{
    $full_message .= $message_defaults->{"systemname"}.' ';
  }
  $full_message .= $message_defaults->{"program"}.' ';
  $full_message .= '- - ';  // This is part of normal NILVALUE in syslog format.
  $full_message .= $arg_message;
  $full_message .= PHP_EOL;

  openlog('Geolocate', LOG_PID, LOG_LOCAL0);
  syslog(LOG_NOTICE, $full_message);
  closelog();
}

?>
