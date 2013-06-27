aws-tools
=============================

aws-tools is my collection of handy tools/snippeps using AWS, including:

* ec2_pricing
An aggregator of separate AWS EC2 pricing json files, which is easier to read and thus much more compact (2.54MB -> ~90 KB ungzipped). 

REQUIREMENTS
-----------------------------
* PHP >= 5.4

INSTALLATION
-----------------------------
* git clone http://github.com/clifflu/aws-tools.git
* make sure tmp/ is writable for httpd

ec2_pricing
-----------------------------

Usage:
    Request http://[PATH_TO_REPO]/ec2_pricing/ec2_pricing.php
    Demo: http://home.clifflu.net/aws-tools/ec2_pricing/ec2_pricing.php

Config:
* config/ec2_pricing/ : 
    * fetch.json : file list for download and parse
    * remap.json : entities to be renamed. eg. us-west => us-west-1, apac-sin => ap-southeast-1
    * tags.json : complete list of categories for the output from fetch.py, in the exact same order.



LICENSE
-----------------------------
MIT
