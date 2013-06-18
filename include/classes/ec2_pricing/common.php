<?php

namespace clifflu\aws_tools\ec2_pricing;
use clifflu\aws_tools as ROOT_NS;

class Common {
    public static function json_encode($arr) {
        $str = json_encode($arr, JSON_UNESCAPED_UNICODE);
        return $str;
    }

    public static function local_fn($fn) {
        $config = ROOT_NS\util\Config::get_one('fetch', 'ec2-pricing');
        return PATH_TMP . 'ec2-pricing' . DS . $fn . $config['appendix'] ;
    }

    public static function aws_url($fn) {
        $config = ROOT_NS\util\Config::get_one('fetch', 'ec2-pricing');
        return $config['prefix'] . $fn . $config['appendix'];
    }
}