# GeoLocate

## This will provide code for PHP calls to IP2Location web service.

### Basics
This program will retrieve basic information about an IP using a web service call. The program in this repository is in PHP. This program is a modified copy the PHP code in the IP2Location website.  

I currently use this program to block unwanted IP addresses from accessing my websites. To use this program, embed it in a PHP program using "include".


### How it works
The program reads a json file that contains a "white list" of latitudes and longitudes to compare with results obtained from the program. If you were to look at a map and draw a box surrounding the approximate locations of where an IP address should be allowed, the values correspond to:
1. upper-left latitude;
2. upper-left longitude;
3. lower-right latitude;
4. lower-right longitude.

For example, approximate json array values for all of Colorado and all of South Dakota (with parts of Iowa, Minnesota and Nebraska since the south-eastern corner of South Dakota is not an ideal rectangle) would be:

{  
	"whitelist_LATLONG": [  
		["All of Colorado", 41, -109, 37, -77],  
		["All of South Dakota, parts of Iowa, Minnesota, Nebraska", 46, -104, 42.5, -96.5]  
	]  
}  

### How to call the program
The program can be called from other programs that utilize this as a module. The program can be called from a Linux command line interface (terminal session).

The program uses two optional arguments: an IP address and a verbose flag.

For example calling the program without an IP address or verbose flag:

php geolocate_API.php

Will return nothing to the screen, but a calling program will receive a PHP object with several values retrieved from the geolocate database. Values will not show on the screen because the -v verbose flag is not set.


php geolocate_API.php -v

Will retrieve the client (your) location. I blanked out my personal info. Notice the "Whitelisted return value?" at the very end of the reply. The response on the screen is:  
Response within the class object: {"ip":"##.###.##.##","country_code":"US","country_name":"United States of America","region_name":"mystate","city_name":"My city","latitude":##.######,"longitude":-##.######,"zip_code":"12345","time_zone":"-##:00","asn":"#####","as":"My ISP","is_proxy":false}Whitelisted return value?: 1  

Adding a IPv4 address, but without the verbose flag:  
php geolocate_API.php '4.4.4.4'

Will result in nothing being presented on the screen. A calling module will, however, obtain the PHP object from the geolocator information.


No calling the program while keeping the IP address and adding the verbose flag:  
** php geolocate_API.php -i'4.4.4.4' -v **

Gives the following:
Response within the class object:   {"ip":"4.4.4.4","country_code":"US","country_name":"United States of America","region_name":"Hawaii","city_name":"Honolulu","latitude":21.307796,"longitude":-157.859187,"zip_code":"96801","time_zone":"-10:00","asn":"3356","as":"Level 3 Parent LLC","is_proxy":true}Whitelisted return value?:


Surprisingly found this while testing this IP.  
php geolocate_API.php -i'1.2.3.4' -v

Gave the following:  
Response within the class object: {"ip":"1.2.3.4","country_code":"AU","country_name":"Australia","region_name":"Queensland","city_name":"Brisbane","latitude":-27.46765,"longitude":153.027824,"zip_code":"4000","time_zone":"+10:00","asn":"-","as":"-","is_proxy":false}Whitelisted return value?:


### Prerequisite

1. Obtain a key from the https://www.ip2location.io/ website which is required. After creating a free account, your key will be emailed to you.
2. Within the folder path where you store this PHP program, add a new folder named "keys". In this folder, create a file called "geolocate.key". This is a simple text file that contains the key obtained from the IP2Locate website from step #1. Do not add anything additional, since this program is not setup to check for comments, json format, etc. in this file. If desired, you can modify the program and make the folder path to whatever suits your needs.
