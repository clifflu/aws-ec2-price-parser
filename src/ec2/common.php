<?php

namespace clifflu\aws_prices\ec2;
use clifflu\aws_prices as ROOT_NS;

trait Common {
    public static function get_domain() {
        return 'ec2';
    }

    public static function local_fn($fn) {
        $config = ROOT_NS\util\Config::get_one('fetch', static::get_domain());
        return ROOT_NS\util\Fs::fn_tmp($fn . $config['appendix'], static::get_domain()) ;
    }

    public static function aws_url($fn) {
        $config = ROOT_NS\util\Config::get_one('fetch', static::get_domain());
        return $config['prefix'] . $fn . $config['appendix'];
    }
}
