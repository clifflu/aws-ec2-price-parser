<?php
/**
 * ec2/index.php
 * download and parse AWS EC2 pricing info, and output as json
 *
 * For column info and configurations, see config/*.json
 */

/* http headers */
header('Content-Type: application/json; charset=utf-8');

/* path, autoloader, composer, etc... */
require_once('../../src/common.php');

use clifflu\awsPrices\ec2;

$parser = ec2\Parser::forge();
$fetcher = ec2\Fetcher::forge();
$parser->attach($fetcher);

echo $parser->get_json();
