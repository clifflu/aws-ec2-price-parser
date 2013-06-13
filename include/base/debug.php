<?php
/**
 * 
 */
namespace clifflu\aws_ec2_price_tool\base;

/**
 * 
 */
abstract class Debug {

    const L_TRACE = 0;
    const L_DEBUG = 10;
    const L_INFO = 20;
    const L_WARN = 30;
    const L_ERROR = 40;

    private $_log_buffer = array();

    public static function log($msg, $level = 10) {
        echo preg_replace('/[\s\r\n]+$/', '', self::capture_buffered_output('print_r', $msg))."\n";
    }

    private static function capture_buffered_output($callback, $msg, $html_error = true) {
        if (!ob_start()) {
            throw new Exception('ob_start failed');
        }
        $html_error_ori = ini_get('html_errors');
        if ($html_error !== $html_error_ori) ini_set('html_errors', $html_error);
        call_user_func_array($callback, [$msg]);
        if ($html_error_ori !== $html_error) ini_set('html_errors', $html_error_ori);
        $ret = ob_get_clean();
        
        return $ret;
    }
}