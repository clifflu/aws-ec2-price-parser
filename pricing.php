<?php
/**
 * pricing.php
 * download and reparse AWS pricing info, and output as json
 *
 * For column info and configurations, see config/*.json
 */

require_once('common.inc.php');

fetch();
//echo json_encode(convert());
