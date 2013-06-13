<?php
/**
 * 
 */

namespace clifflu\aws_ec2_price_tool;

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