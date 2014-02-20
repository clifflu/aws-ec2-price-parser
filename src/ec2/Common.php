<?php

namespace clifflu\awsPrices\ec2;

use clifflu\awsPrices\util;

trait Common {
    public static function get_domain() {
        return 'ec2';
    }

    public static function local_fn($fn) {
        $config = util\Config::get_one('fetch', static::get_domain());
        return util\Fs::fn_tmp($fn . $config['appendix'], static::get_domain()) ;
    }

    public static function aws_url($fn) {
        $config = util\Config::get_one('fetch', static::get_domain());
        return $config['prefix'] . $fn . $config['appendix'];
    }
}
