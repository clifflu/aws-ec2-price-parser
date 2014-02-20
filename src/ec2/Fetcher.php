<?php

namespace clifflu\awsPrices\ec2;

use clifflu\awsPrices\base;
use clifflu\awsPrices\util;

class Fetcher extends base\Fetcher {
    use Common;

    public static function defaults($config = []) {
        $_ = util\Config::get_one('fetch', static::get_domain());

        if ($config)
            $_ = array_replace_recursive($_, $config);

        return parent::defaults($_);
    }

    protected function __construct($param) {
        parent::__construct($param);

        foreach ($this->config['files'] as $fn) {
            $local_fn = $this->local_fn($fn);
            $aws_url = $this->aws_url($fn);

            $this->queue($local_fn, $aws_url);
        }
    }
}
