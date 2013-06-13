<?php
/**
 * common.inc.php
 * 
 */

namespace clifflu\aws_ec2_price_tool;

// ========================
// Paths
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

/* util */
require(PATH_INC . 'util.php');

/* autoloader */
require('vendor/autoload.php');

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
