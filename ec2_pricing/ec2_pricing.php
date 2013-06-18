<?php
/**
 * pricing.php
 * download and reparse AWS pricing info, and output as json
 *
 * For column info and configurations, see config/*.json
 */
namespace clifflu\aws_tools;

/* http headers */
header('Content-Type: application/json; charset=utf-8');

/* path, autoloader, composer, etc... */
require_once('../include/common.php');

$parser = ec2_pricing\Parser::forge();
$fetcher = ec2_pricing\Fetcher::forge();
$parser->attach($fetcher);

echo $parser->get_json();
