<?php
namespace clifflu\aws_tools\util;
use clifflu\aws_tools as ROOT_NS;

/**
 * Utility class for Config manipulation
 *
 * Consumers should call these utilitity methods as frequent as 
 * reasonably possible, not to store and share between them.
 */
class Config {
    static protected $_cache = [];

    public static function get($entries, $domain = '') {
        $config = [];

        foreach ($entries as $key => $val) {
            if (!is_string($val)) {
                $config[$key] = $val;
                continue;
            }

            $fn = Fs::fn_config($val, $domain);
            
            if (!is_file($fn)) {
                $config[$key] = $val;
                continue;
            }

            if (is_numeric($key)) 
                $key = $val;
            
            $config[$key] = static::get_one($fn, $domain);
        }

        return $config;
    }

    public static function get_one($fn, $domain = '') {
        if (!is_file($fn))
            $fn = Fs::fn_config($fn, $domain);
            
        if (!is_file($fn))
            throw new \Exception("Config file '$fn' not found");

        if (isset(static::$_cache[$fn]))
            return static::$_cache[$fn];

        $config = json_decode(file_get_contents($fn), true);

        /* Build Lookup Tables */
        if (isset($config['__lookup__'])) {
            foreach ($config['__lookup__'] as $tbl_name => $contents) {
                if (!isset($config[$tbl_name]))
                    $config[$tbl_name] = array();

                static::build_lookup($contents, $config[$tbl_name]);
            }
        }

        static::$_cache[$fn] = $config;

        return $config;
    }

    /**
     * 將 __lookup__ entries 轉換成反向關聯並寫入 $dest 中
     * 
     * @param  [type] $src  [description]
     * @param  [type] $dest [description]
     * @return [type]       [description]
     */
    protected static function build_lookup($src, &$dest) {
        foreach ($src as $key => $val) {
            foreach ($val as $alias)
                $dest[$alias] = $key;
        }
    }
}