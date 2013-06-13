<?php
namespace clifflu\aws_ec2_price_tool;

class Fetcher extends base\Base{
    private $_now = false;

    protected function __construct($config) {
        parent::__construct($config);
        $this->_now = time();
    }

    /**
     * Fetch remote files if needed
     * 
     * @return int number of files modified
     */
    public function go() {
        $fetch_list = [];

        foreach ($this->config['fetch']['files'] as $fn) {
            $local_fn = $this->local_fn($fn);
            $aws_url = $this->aws_url($fn);

            if ($this->need_fetch($fn, $local_fn, $aws_url)) {
                $fetch_list[] = [$fn, $local_fn, $aws_url];
            }
        }

        foreach($fetch_list as $entry) {
            $this->fetch($entry[0], $entry[1], $entry[2]);
        }

        return count($fetch_list);
    }
    
    function need_fetch($fn, $local_fn, $aws_url) {
        $mtime = lstat($local_fn)['mtime'];
        
        // don't fetch if modified recently
        if ($mtime > ($this->_now - $this->config['fetch']['expire_hour'] * 3600)) {
            return false;
        }

        // If amazon allows browser cache, do it here
        // Ref: ETag, Expires, etc.
        return true;
    }

    /**
     * Fetch data files from AWS
     * @return [type] [description]
     */
    function fetch($fn, $local_fn, $aws_url) {
        return;
        throw new \Exception('todo');
    }
}
