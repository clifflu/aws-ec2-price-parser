aws-prices
=============================

aws-prices is a collection of AWS pricing utilities, including:

* ec2
An aggregator of separate AWS EC2 pricing json files, which is easier to read and thus much more compact (2.54MB -> ~90 KB ungzipped). 

REQUIREMENTS
-----------------------------
* PHP >= 5.4
* Composer

INSTALLATION
-----------------------------
* git clone http://github.com/clifflu/aws-ec2-prices.git
* make sure tmp/ is writable to httpd
* run composer install

ec2
-----------------------------

Usage:
* Request http://[PATH_TO_REPO]/ec2/index.php
* Demo: http://home.clifflu.net/aws-prices/ec2/

Config:
* config/ec2/ : 
    * fetch.json : file list for download and parse
    * remap.json : entities to be renamed. eg. us-west => us-west-1, apac-sin => ap-southeast-1
    * tags.json : complete list of categories for the output from fetch.py, in the exact same order.



LICENSE
-----------------------------
MIT
