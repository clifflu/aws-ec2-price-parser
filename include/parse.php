<?php
/**
 * parse.php - 解析由 fetch.php 抓下來的檔案，重整並輸出
 * 
 */


/**
 * Convert downloaded files
 * @return [type] [description]
 */
function convert() {
    global $CONFIG;

    $output = array();
    $fetch_list = $CONFIG['filelist']['files'] ;

    foreach ($fetch_list as $fn)
        parse_file($fn, $output);

    return truncate_nulls($output);
}

/**
 * 開啟並分析 fn，並將資料存至 tbl. 由檔名猜測對應的 os 與 term.
 * @param  [type] $fn  [description]
 * @param  [type] $tbl [description]
 * @return [type]      [description]
 */
function parse_file($fn, &$tbl) {
    global $CONFIG;

    $c_os = guess_os($fn);
    $c_term = guess_term($fn);

    if (!($c_os && $c_term))
        return;

    $src = json_decode(file_get_contents(local_fn($fn)), true);

    // @todo: Currency and Version check

    foreach ($src['config']['regions'] as $src_regional) {
        $c_region = lookup_dict($src_regional['region'], $CONFIG['remap']['regions']);
        
        // @todo: check region

        if (!isset($tbl[$c_region]))
            $tbl[$c_region] = array();

        if (!isset($tbl[$c_region][$c_os]))
            $tbl[$c_region][$c_os] = array();

        parse_instance_type($src_regional['instanceTypes'], $c_term, $tbl[$c_region][$c_os]);
    }
}

function guess_os($fn) {
    global $CONFIG;

    foreach ($CONFIG['tags']['oses'] as $os => $desc) {
        if (strncmp($os.'-', $fn, strlen($os)+1) === 0)
            return $os;
    }
    return false;
}

/**
 * 猜測可能的 term; 由於 RI 合約年數不由檔名決定，因此只回傳 od|l|m|h 或 false
 * @param  [type] $fn [description]
 * @return [type]     [description]
 */
function guess_term($fn){
    if (!preg_match("/-(od|ri-(?:heavy|medium|light))$/", $fn, $matches))
        return false;
    
    switch ($matches[1]){
        case 'od':
            return 'od';
        case 'ri-heavy':
            return 'h';
        case 'ri-medium':
            return 'm';
        case 'ri-light':
            return 'l';
    }
    return false;
}

function is_term_od($term) {
    return $term == 'od';
}

/**
 * Fix some possible typo in AWS data files
 * cc1.8xlarge => cc2.8xlarge
 * cc2.4xlarge => cg1.4xlarge
 * Ref:
 * - http://aws.amazon.com/ec2/instance-types/instance-details/
 * - https://github.com/erans/ec2instancespricing/commit/71a24aaef1d2ceed2f3e4cefecc9b34b6d5f35b6 
 */
function fix_instance_size($c_instance, $c_size) {
    global $CONFIG;

    foreach ($CONFIG['remap']['instance_size'] as $typo) {
        if ($typo['replace']['instance'] == $c_instance and $typo['replace']['size'] == $c_size)
            return array($typo['with']['instance'], $typo['with']['size']);
    }
    return array($c_instance, $c_size);
}

function parse_instance_type($src_its, $c_term, &$tbl_its ) {
    global $CONFIG;
    
    foreach($src_its as $src_it) {
        $c_instance = lookup_dict($src_it['type'], $CONFIG['remap']['instances']);

        foreach ($src_it['sizes'] as $src_sz) {
            $c_size = lookup_dict($src_sz['size'], $CONFIG['remap']['sizes']);

            list($fixed_instance, $fixed_size) = fix_instance_size($c_instance, $c_size);
            
            if (!isset($tbl_its[$fixed_instance]))
                $tbl_its[$fixed_instance] = array();
            
            if (!isset($tbl_its[$fixed_instance][$fixed_size]))
                $tbl_its[$fixed_instance][$fixed_size] = array();

            if (is_term_od($c_term))
                parse_od($src_sz, $tbl_its[$fixed_instance][$fixed_size]);
            else
                parse_ri($src_sz, $c_term, $tbl_its[$fixed_instance][$fixed_size]);
        }
    }
}

function parse_od($src_sz, &$tbl_sz) {
    $src_prices = $src_sz['valueColumns'][0]['prices'];
    $tbl_sz['od'] = array(num($src_prices['USD']));
}

function parse_ri($src_sz, $c_term, &$tbl_sz) {
    $src_vcs = $src_sz['valueColumns'];

    foreach ($src_vcs as $vc) {
        switch($vc['name']){
            case 'yrTerm1':
                $upfront_1 = num($vc['prices']['USD']);
                break;
            case 'yrTerm3':
                $upfront_3 = num($vc['prices']['USD']);
                break;
            case 'yrTerm1Hourly':
                $hourly_1 = num($vc['prices']['USD']);
                break;
            case 'yrTerm3Hourly':
                $hourly_3 = num($vc['prices']['USD']);
                break;
        }
    }

    if (isset($upfront_1) && isset($hourly_1))
        $tbl_sz["y1$c_term"] = array($hourly_1, $upfront_1);

    if (isset($upfront_3) && isset($hourly_3))
        $tbl_sz["y3$c_term"] = array($hourly_3, $upfront_3);
}

// ========================
// Truncate empty data
// ========================

/**
 * 除去 obj 及其子成員中，只包含 None 的 list, 以及不包含任何成員的 list 或 dict
 * @param  [type] $obj [description]
 * @return [type]      [description]
 */
function truncate_nulls(&$obj) {
    while(truncate_null_worker($obj)){}
    return $obj;
}

function truncate_null_worker(&$obj) {
    $tbd = array();
    $fired = false;

    foreach($obj as $key => &$val) {
        if (is_array($val)) {
            if (count($val) == 0) {
                $tbd[] = $key;
            } else {
                if (truncate_null_worker($val))
                    $fired = true;
            }
        } elseif ($val == null)
            $tbd[] = $key;
    }

    if (count($tbd)) {
        $fired = true;
        foreach($tbd as $key)
            unset($obj[$key]);
    }

    return $fired;
}
