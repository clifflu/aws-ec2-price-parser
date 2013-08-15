<?php
namespace clifflu\aws_prices\util;
use clifflu\aws_prices as ROOT_NS;

class Fs{
    // =========================
    // Filename and URL
    // =========================
    private static $_now = null;
    
    public static function file_age($filename) {
        if (self::$_now == null)
            self::$_now = time();

        return self::$_now - self::file_mtime($filename);
    }

    /**
     * Get file modification time
     * 
     * @param  string $filename
     * @return timestamp    mtime, 0 if file not found or unreadable
     */
    public static function file_mtime($filename) {
        if (!(is_file($filename) && is_readable($filename)))
            return 0;
        return lstat($filename)['mtime'];
    }

    public static function mkdir($dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
            chmod($dir, 0775);
        }
    }

    public static function fdump($filename, $msg) {
        file_put_contents($filename, $msg);
        chmod($filename, 0664);
    }

    // =========================
    // Path
    // =========================
    public static function fn_config($fn, $domain = '') {
        return PATH_CONFIG . ($domain ? $domain . DS : '') . $fn . '.json';
    }

    public static function fn_tmp($fn, $domain = '') {
        return PATH_TMP . ($domain ? $domain . DS : '') . $fn;
    }

    public static function fn_fetcher_lock($domain = '') {
        return PATH_TMP . 'fetcher.' . ($domain ? $domain . '.' : '') . 'lock';
    }

    public static function fn_parser_lock($domain = '') {
        return PATH_TMP . 'parser.' . ($domain ? $domain . '.' : '') . 'lock';
    }
}
