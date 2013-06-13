<?php
/**
 * 
 */

function lookup_dict($key, &$tbl) {
    return isset($tbl[$key]) ? $tbl[$key] : $key;
}

function aws_url($fn) {
    global $CONFIG;
    return $CONFIG['filelist']['prefix'] + $fn + $CONFIG['filelist']['appendix'];
}

function local_fn($fn) {
    global $CONFIG;
    return PATH_TMP . $fn . $CONFIG['filelist']['appendix'] ;
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