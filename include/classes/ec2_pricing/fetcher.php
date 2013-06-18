<?php

namespace clifflu\aws_tools\ec2_pricing;
use clifflu\aws_tools as ROOT_NS;

class Fetcher extends ROOT_NS\base\Fetcher {

    public static function defaults($config = []) {
        $_ = ROOT_NS\util\Config::get_one('fetch', 'ec2-pricing');

        if ($config)
            $_ = array_replace_recursive($_, $config);

        return parent::defaults($_);
    }

    public function load() {
        foreach ($this->config['files'] as $fn) {
            $local_fn = Common::local_fn($fn);
            $aws_url = Common::aws_url($fn);

            $this->queue($local_fn, $aws_url);
        }
    }
}