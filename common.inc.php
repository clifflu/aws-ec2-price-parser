<?php
// ========================
// Project Paths
// ========================
$PATH = array();

define('DS', DIRECTORY_SEPARATOR);
define('PATH_ROOT', realpath(__dir__) . DS);
define('PATH_CONFIG', PATH_ROOT . 'config' . DS);
define('PATH_INC', PATH_ROOT . 'include' . DS);
define('PATH_TMP', PATH_ROOT . 'tmp' . DS);

// ========================
// Includes
// ========================
require(PATH_INC . 'util.php');
require(PATH_INC . 'fetch.php');
require(PATH_INC . 'parse.php');

// ========================
// Load Config Files
// ========================

$CONFIG = array(
    'filelist'=> null, 
    'lang'=> null, 
    'remap'=> null, 
    'tags'=> null
);

foreach ($CONFIG as $fn => $val) {
    $CONFIG[$fn] = json_decode(file_get_contents(PATH_CONFIG . $fn . '.json'), true);
}

#
# Build Lookup Tables
#

foreach ($CONFIG['remap']['_lookup'] as $tbl_name => $contents) {
    $CONFIG['remap'][$tbl_name] = array();
    build_lookup_table($contents, $CONFIG['remap'][$tbl_name]);
}