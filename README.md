# GeoLocate

### This will provide code for PHP calls to IP2Location web service.

This program will retrieve basic information about an IP using a web service call. The program in this repository is in PHP. This program is a modified copy the PHP code in the IP2Location website.  

I currently use this program to block unwanted IP addresses from accessing my websites. To use this program, embed it in a PHP program using "include".

This can also be called from a command line in Linux terminal. For example calling the program without an IP address:
php geolocate_API.php

Will give the following:
string(267) "{"ip":"8.8.8.8","country_code":"US","country_name":"United States of America","region_name":"California","city_name":"Mountain View","latitude":37.405992,"longitude":-122.078515,"zip_code":"94043","time_zone":"-07:00","asn":"15169","as":"Google LLC","is_proxy":false}"


Adding a IPv4 address:
php geolocate_API.php '4.4.4.4'

Will give the following:
string(264) "{"ip":"4.4.4.4","country_code":"US","country_name":"United States of America","region_name":"Hawaii","city_name":"Honolulu","latitude":21.307796,"longitude":-157.859187,"zip_code":"96801","time_zone":"-10:00","asn":"3356","as":"Level 3 Parent LLC","is_proxy":true}"


or...
php geolocate_API.php '162.142.125.1'

Will give the following:
string(268) "{"ip":"162.142.125.1","country_code":"US","country_name":"United States of America","region_name":"Michigan","city_name":"Ann Arbor","latitude":42.259863,"longitude":-83.719897,"zip_code":"48104","time_zone":"-04:00","asn":"398324","as":"Censys Inc.","is_proxy":false}"


### Prerequisite

1. Obtain a key from the https://www.ip2location.io/ website which is required. After creating a free account, your key will be emailed.
2. Depending on where you store this PHP program, add a folder named "keys". In this folder, create a file called "geolocate.key". This is a simple text file that contains the key obtained from the IP2Locate website from step #1. Do not add anything additional, since this program is not setup to check for comments, json format, etc. in this file.
