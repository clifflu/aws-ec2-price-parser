<?php
/**
 * common.inc.php
 * 
 */

namespace clifflu\aws_ec2_price_tool;
define('NS', 'clifflu\aws_ec2_price_tool');

// ========================
// Project Paths
// ========================
define('DS', DIRECTORY_SEPARATOR);
define('PATH_ROOT', realpath(__dir__) . DS);
define('PATH_CONFIG', PATH_ROOT . 'config' . DS);
define('PATH_INC', PATH_ROOT . 'include' . DS);
define('PATH_TMP', PATH_ROOT . 'tmp' . DS);

// ========================
// Includes
// ========================

/* vendor */
require(PATH_INC . 'vendor' . DS . 'args.php');
require(PATH_INC . 'vendor' . DS . 'curly.php');

/* util */
require(PATH_INC . 'util.php');
require(PATH_INC . 'parser.php');


/* classes */
require(PATH_INC . 'base' . DS . 'debug.php');
require(PATH_INC . 'base' . DS . 'forge.php');
require(PATH_INC . 'base' . DS . 'util.php');
require(PATH_INC . 'base' . DS . 'base.php');
require(PATH_INC . 'fetcher.php');

// ========================
// Load Config Files
// ========================

$CONFIG = array(
    'fetch'=> null, 
    'lang'=> null, 
    'remap'=> null, 
    'tags'=> null
);

foreach ($CONFIG as $fn => $val) {
    $CONFIG[$fn] = json_decode(file_get_contents(PATH_CONFIG . $fn . '.json'), true);
}

// ========================
// Build Lookup Tables
// ========================

foreach ($CONFIG['remap']['_lookup'] as $tbl_name => $contents) {
    $CONFIG['remap'][$tbl_name] = array();
    build_lookup_table($contents, $CONFIG['remap'][$tbl_name]);
}
