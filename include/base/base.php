<?php
namespace clifflu\aws_ec2_price_tool\base;
use clifflu\aws_ec2_price_tool as ROOT_NS;

abstract class Base extends Util{
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