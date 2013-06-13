<?php
namespace clifflu\aws_ec2_price_tool\base;
use clifflu\aws_ec2_price_tool as ROOT_NS;

abstract class Util extends Forge{
    // =========================
    // Filename and URL
    // =========================
    public static function local_fn_config($fn, $config) {
        return PATH_TMP . $fn . $config['fetch']['appendix'] ;
    }

    public function local_fn($fn) {
        return static::local_fn_config($fn, $this->config);
    }

    public static function aws_url_config($fn, $config) {
        return $config['fetch']['prefix'] . $fn . $config['fetch']['appendix'];
    }

    public function aws_url($fn) {
        return static::aws_url_config($fn, $this->config);
    }

    // ========================
    // Truncate empty data
    // ========================

    /**
     * 除去 obj 及其子成員中，只包含 None 的 list, 以及不包含任何成員的 list 或 dict
     * @param  [type] $obj [description]
     * @return [type]      [description]
     */
    public static function truncate_nulls(&$obj) {
        while(static::truncate_null_worker($obj)){}
        return $obj;
    }

    public static function truncate_null_worker(&$obj) {
        $tbd = array();
        $fired = false;

        foreach($obj as $key => &$val) {
            if (is_array($val)) {
                if (count($val) == 0) {
                    $tbd[] = $key;
                } else {
                    if (static::truncate_null_worker($val))
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

    // ========================
    // Overrides
    // ========================
    
    /**
     * Populates $CONFIG, ignores $overrides
     * 
     * @return array Default config
     */
    public static function defaults($overrides = null) {
        $CONFIG = array(
            'fetch'=> null, 
            'lang'=> null, 
            'remap'=> null, 
            'tags'=> null
        );

        foreach ($CONFIG as $fn => $val) {
            $CONFIG[$fn] = json_decode(file_get_contents(PATH_CONFIG . $fn . '.json'), true);
        }

        /* Build Lookup Tables */
        foreach ($CONFIG['remap']['_lookup'] as $tbl_name => $contents) {
            $CONFIG['remap'][$tbl_name] = array();
            ROOT_NS\build_lookup_table($contents, $CONFIG['remap'][$tbl_name]);
        }

        return parent::defaults($CONFIG);
    }
}