<?php

namespace clifflu\aws_tools\util;
use clifflu\aws_tools as ROOT_NS;

class Data {
    
    public static function json_encode($arr) {
        $str = json_encode($arr, JSON_UNESCAPED_UNICODE);
        return $str;
    }
    /**
     * from http://www.php.net/manual/en/function.base64-encode.php
     * @param byte $data
     * @return string
     */
    public static function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data) , '+/', '-_') , '=');
    }
    /**
     * from http://www.php.net/manual/en/function.base64-encode.php
     * @param string $data
     * @return byte
     */
    public static function base64url_decode($data) {
        return base64_decode(str_pad(strtr($data, '-_', '+/') , strlen($data) % 4, '=', STR_PAD_RIGHT));
    }

    function num($str) {
        return is_numeric($str) ? $str * 1 : null;
    }

    function lookup_dict($key, &$tbl) {
        return isset($tbl[$key]) ? $tbl[$key] : $key;
    }

    public static function ksort_recursive(&$array) {
        ksort($array);

        foreach($array as $idx => &$itm) {
            if (is_array($itm))
                static::ksort_recursive($itm);
        }
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

    protected static function truncate_null_worker(&$obj) {
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
}