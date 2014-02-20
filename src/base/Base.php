<?php

namespace clifflu\awsPrices\base;

use clifflu\awsPrices\util;

abstract class Base extends Forge{
    // =============================
    //  Overrides
    // =============================
    public static function defaults($overrides = null) {
        $_ = [
            'sleep_io_ms' => 1,
            'sleep_lock_s' => 1,
            'lock_patience_s' => 120,
        ];

        if ($overrides)
            $_ = array_replace_recursive($_, (array) $overrides);

        return parent::defaults($_);
    }

    // =============================
    //  Abstarct methods
    // =============================
    abstract public static function get_domain();

    abstract public static function get_lock_fn();
    /**
     * 是否所有 cache file 都存在
     * @return boolean [description]
     */
    abstract public function has_cache();

    /**
     * 是否所有 local cache 都未過期
     * @return boolean [description]
     */
    abstract public function is_cache_valid();

    // =============================
    //  Utilities
    // =============================
    const S_IN_PROGRESS = 1;
    const S_NOT_EXIST   = 2;
    const S_INVALID = 4;
    /**
     * 對 instance 查驗快取與執行狀態
     * 
     * @return int
     */
    public function get_status() {
        $fn = static::get_lock_fn();
        $cs = $this->get_cache_status();

        if (!file_exists($fn))
            return $cs;

        if (util\Fs::file_age($fn) > $this->config['lock_patience_s'])
            return $cs;

        return $cs | static::S_IN_PROGRESS;
    }

    /**
     * 查驗 Class 快取狀態
     * 
     * @return int
     */
    protected function get_cache_status() {
        if (!$this->has_cache())
            return static::S_NOT_EXIST;
        
        if (!$this->is_cache_valid())
            return static::S_INVALID;

        return 0;
    }
}