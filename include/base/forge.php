<?php
namespace clifflu\aws_ec2_price_tool\base;

/**
 * class Base_Factry
 * 
 * 透過 static::defaults() 產生預設 config，並透過 forge() 生成實體
 */
abstract class Forge{
    protected $config;

    // ===========================
    // 
    // ===========================
    protected function __construct($config) {
        $this->config = $config;
    }

    public static function forge($config = null) {
        $config = static::defaults($config);
        return new static($config);
    }

    public static function defaults($overrides = null) {
        if (!$overrides)
            return array();

        return array_merge(array(), (array) $overrides);
    }
}