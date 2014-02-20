<?php

namespace clifflu\awsPrices\base;

use clifflu\awsPrices\util;

/**
 * Parser
 * 
 * Usage:
 *     $p = Parser::forge();
 *     $p->attach(new Fetcher);
 *     $result = $p->get_json(); // json
 */
abstract class Parser extends Base {
    // ===========================
    // Constructor & Overrides
    // ===========================
    
    public static function defaults($config = []) {
        $_ = [
            'lock_patience_s' => 2,
            'expire_hot_s' => 1,
        ];

        if ($config)
            $_ = array_replace_recursive($_, (array) $config);

        return parent::defaults($_);
    }
    
    public static function get_lock_fn() {
        return util\Fs::fn_parser_lock(static::get_domain());
    }

    public function has_cache() {
        return file_exists($this->get_cache_fn());
    }

    public function is_cache_hot() {
        $fn = $this->get_cache_fn();
        return util\Fs::file_age($fn) < $this->config['expire_hot_s'];
    }

    public function is_cache_valid() {
        $fn = $this->get_cache_fn();

        if (!(is_file($fn) && is_readable($fn)))
            return false;

        // still hot
        if ($this->is_cache_hot())
            return true;

        $mtime = util\Fs::file_mtime($fn);
        foreach($this->list_input_fn() as $fn) {
            if (util\Fs::file_mtime($fn) > $mtime)
                return false;
        }

        foreach($this->_fetchers as $f) {
            if (!$f->is_cache_valid())
                return false;
        }

        return true;
    }

    // ===========================
    // Abstract Methods
    // ===========================

    /**
     * Array of absolute path of input files
     */
    abstract public function list_input_fn();

    abstract public function get_cache_fn();
    
    /**
     * 重建快取, 呼叫時已保證 $fetcher 執行完畢
     * 
     * @return array
     */
    abstract protected function _rebuild();

    // ===========================
    // Procedural
    // ===========================
    protected $_fetchers = [];

    public function attach(Fetcher $f) {
        $this->_fetchers[] = $f;
    }

    /**
     * 處理 lock , 生成並寫入重建快取
     * 
     * @return [type] [description]
     */
    protected function rebuild() {
        $lock_fn = util\Fs::fn_parser_lock($this->get_domain());
        $sleep_us = $this->config['sleep_lock_s'] * 1000000;

        if (!$this->lock_acquire()) {
            $this->lock_waive();
            // can't acquire lock, another parser is running.
            $wait_start = microtime(true);

            // wait for it until lock_patience_s
            while(microtime(true) - $wait_start < $this->config['lock_patience_s']) {
                usleep($sleep_us);
                if ($this->is_cache_hot()) {
                    return $this->_get_json_from_cache();
                }
            }

            // he's not done yet, try old results
            $result = $this->_get_json_from_cache();
            if ($result)
                return $result;

            // no such result, keep waiting
            for(;!$this->is_cache_valid(); usleep($sleep_us)){}
            return $this->_get_json_from_cache();
        }

        // feed fetchers
        foreach($this->_fetchers as $f)
            $f->sync();

        $output = util\Data::json_encode($this->_rebuild());
        util\Fs::fdump($this->get_cache_fn(), $output);

        $this->lock_release();

        return $output;
    }

    /**
     * 自快取讀取資料
     * @return [type] [description]
     */
    protected function _get_json_from_cache() {
        $fn = $this->get_cache_fn();
        
        if (file_exists($fn))
            return file_get_contents($fn);

        return '';
    }

    /**
     * 讀取資料
     * @return [type] [description]
     */
    public function get_json() {
        if ($this->is_cache_valid())
            return $this->_get_json_from_cache();

        return $this->rebuild();
    }

    public function get_data() {
        return ROOT_NS\util\Data::json_decode($this->get_json());
    }

    // ===========================
    // Lock
    // ===========================
    private $_lock_fp = null;

    protected function lock_acquire() {
        $lock_fn = util\Fs::fn_parser_lock($this->get_domain());
        $this->_lock_fp = fopen($lock_fn, 'w');
        return flock($this->_lock_fp, LOCK_EX | LOCK_NB);
    }

    protected function lock_waive() {
        if (!is_resource($this->_lock_fp))
            return;

        fclose($this->_lock_fp);
    }

    protected function lock_release() {
        if (!is_resource($this->_lock_fp))
            return;

        $lock_fn = util\Fs::fn_parser_lock($this->get_domain());

        flock($this->_lock_fp, LOCK_UN);
        fclose($this->_lock_fp);
        unlink($lock_fn);
    }
}