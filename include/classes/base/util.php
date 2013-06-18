<?php
namespace clifflu\aws_tools\base;

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

    private static $_now = null;
    
    public static function file_age($filename) {
        if (self::$_now == null)
            self::$_now = time();

        return self::$_now - self::file_mtime($filename);
    }

    public static function file_mtime($filename) {
        if (!(is_file($filename) && is_readable($filename)))
            return 0;
        return lstat($filename)['mtime'];
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
    
}