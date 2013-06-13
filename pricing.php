<?php
/**
 * pricing.php
 * download and reparse AWS pricing info, and output as json
 *
 * For column info and configurations, see config/*.json
 */

require_once('common.inc.php');

$fetcher = clifflu\aws_ec2_price_tool\Fetcher::forge();
$fetcher->start();

$parser = clifflu\aws_ec2_price_tool\Parser::forge();

echo $parser->get_json();
