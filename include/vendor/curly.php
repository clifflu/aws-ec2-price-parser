<?php

class Curly {
    const CO_HEADER_ONLY = 1;

    // =====================
    // Executor
    // =====================

    public static function get_header($url) {
        return static::wrap_curlexec(
            $url, 
            function(&$ch) {static::curlopt($ch, static::CO_HEADER_ONLY);}
        );
    }

    public static function get_body($url) {
        return static::wrap_curlexec(
            $url, 
            function(&$ch) {static::curlopt($ch);}
        );
    }
    // =====================
    // curl_opt helpers
    // =====================

    protected static $_curlopt_tbl = [
        self::CO_HEADER_ONLY => '_curlopt_header_only',
    ];

    public static function curlopt(&$ch, $flags = 0) {
        // generic
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);

        foreach(static::$_curlopt_tbl as $flag => $cb) {
            if ($flags & $flag)
                static::$cb($ch);
        }
    }

    protected static function _curlopt_header_only(&$ch) {
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_NOBODY, 1);
    }

    // =====================
    // Curried $ch executor
    // =====================
    protected static function wrap_curlexec($url, $curlopt_cb) {
        $ch = curl_init($url);
        $curlopt_cb($ch);
        $output = curl_exec($ch);
        return $output;
    }
}