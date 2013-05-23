aws-ec2-price-tool
=============================

aws-ec2-price-tool is deisgned to provide an alternative method to play with AWS EC2 pricing data.
It is composed of three parts:
* src/fetch.py : command-line utility to fetch .json files from AWS, parse them, and output in a much more compact format (2.54MB -> ~98 KB). 
* src/aws-ec2-price.js : 
* config/ : 
    * filelist.json : file list for download and parse
    * lang.json : localization for fetch.py
    * remap.json : entities to be renamed. eg. us-west => us-west-1, apac-sin => ap-southeast-1
    * tags.json : complete list of categories for the output from fetch.py, in the exact same order.

REQUIREMENTS
--------------------------
* fetch.py
    * Python 2 / 3
    * argparse if running on Python < 2.7

fetch.py
--------------------------

*usage*: fetch.py [-h] [--cleanup] [--days-expire DAYS] [--force-fetch]
[--indent WIDTH] [--output FILE] [--pretty] [--tmp-dir PATH]

fetch pricing data files from AWS and re-parse them

*optional arguments*:
* -h, --help
    show this help message and exit
* --cleanup, -c
    cleanup tmp files upon completion
* --days-expire DAYS, -d DAYS
    days before fetch files expire, default = 7
* --force-fetch, -f
    force fetch, ignores file expire check
* --indent WIDTH, -i WIDTH
    sets output indentation, implies pretty output (-p)
* --output FILE, -o FILE
    output to file, not stdout
* --pretty, -p
    pretty output, file gets larger
* --tmp-dir PATH, -t PATH
    override tmp path


aws-ec2-price.js
--------------------------
blah

LICENSE
--------------------------
MIT
