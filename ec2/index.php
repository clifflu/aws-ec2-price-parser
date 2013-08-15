<?php
/**
 * ec2/index.php
 * download and parse AWS EC2 pricing info, and output as json
 *
 * For column info and configurations, see config/*.json
 */
namespace clifflu\aws_prices;

/* http headers */
header('Content-Type: application/json; charset=utf-8');

/* path, autoloader, composer, etc... */
require_once('../include/common.php');

$parser = ec2\Parser::forge();
$fetcher = ec2\Fetcher::forge();
$parser->attach($fetcher);

echo $parser->get_json();
