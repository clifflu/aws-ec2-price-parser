aws-ec2-price-tool
=============================

aws-ec2-price-tool is deisgned to provide an alternative method to play with AWS EC2 pricing data.
It is composed of three parts:
* src/fetch.py : command-line utility to fetch .json files from AWS, re-parse them, and output in a much more compact format (2.54MB -> 96 KB). 
* src/aws-ec2-price.js : 
* config/ : 
    * cmdline.json : defines default actions for fetch.py
    * filelist.json : file list for download and parse
    * remap.json : entities to be renamed. eg. us-west-1 => us-west, ap-southeast-1 => apac-sin
    * tags.json : complete list of categories for the output from fetch.py, in the exact same order.

fetch.py
--------------------------

aws-ec2-price.js
--------------------------
