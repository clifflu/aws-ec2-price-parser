<?php
namespace clifflu\aws_tools\util;
use clifflu\aws_tools as ROOT_NS;

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
}