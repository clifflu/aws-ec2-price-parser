<?php
/**
 * 
 */

namespace clifflu\aws_tools;

function lookup_dict($key, &$tbl) {
    return isset($tbl[$key]) ? $tbl[$key] : $key;
}

/**
 * 將 remap 中的 _lookup entries 轉換成反向關聯並寫入 $dest 中
 * @param  [type] $src  [description]
 * @param  [type] $dest [description]
 * @return [type]       [description]
 */
function build_lookup_table($src, &$dest) {
    foreach ($src as $key => $val) {
        foreach ($val as $alias)
            $dest[$alias] = $key;
    }
}
    
function num($str) {
    return is_numeric($str) ? $str * 1 : null;
}

function ksort_recursive(&$array) {
    ksort($array);

    foreach($array as $idx => &$itm) {
        if (is_array($itm))
            ksort_recursive($itm);
    }
}

function config_fn ($fn, $subdir = '') {
    return PATH_CONFIG . ($subdir ? $subdir . DS : '') . $fn . '.json';
}

function populate_config($entries, $subdir = '') {
    $config = [];

    foreach ($entries as $key => $val) {
        if (!is_string($val)) {
            $config[$key] = $val;
            continue;
        }

        $fn = config_fn($val, $subdir);
        if (!is_file($fn)) {
            $config[$key] = $val;
            continue;
        }
        if (is_numeric($key)) 
            $key = $val;
        
        $config[$key] = json_decode(file_get_contents($fn), true);
    }

    /* Build Lookup Tables */
    if (isset($config['remap'])) {
        foreach ($config['remap']['_lookup'] as $tbl_name => $contents) {
            $config['remap'][$tbl_name] = array();
            build_lookup_table($contents, $config['remap'][$tbl_name]);
        }
    }

    return $config;
}