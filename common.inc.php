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
