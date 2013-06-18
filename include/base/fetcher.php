<?php
namespace clifflu\aws_tools\base;
use clifflu\aws_tools as ROOT_NS;

/**
 * Fetcher - 將遠端檔案同步至本地
 *
 * Usage:
 *     $f = Fetcher::forge($config);
 *     $f->queue(LOCAL_FN, REMOTE_URL, OPTIONS);
 *     // $f->dequeue(LOCAL_FN);
 *     $f->sync();
 *
 * Cache 分為兩種狀態：hot 與 cold
 *     - 對於 hot cache，系統直接認定為 up to date，因此不發 request
 *     - 對於 cold cache，未來會發 HEAD request 檢查是否有更新，目前比照 Expired
 *     - Expired 則直接丟 GET
 */
abstract class Fetcher extends Base{
    // ===========================
    // Constructor & Overrides
    // ===========================

    public static function defaults($config = []) {
        $_ = [
            'expire_hot_s' => 600,
            'expire_cold_h' => 3,
            'lock_patience_s' => 30,
            'max_threads' => 6,
        ];

        if ($config)
            $_ = array_replace_recursive($_, (array) $config);

        return parent::defaults($_);
    }
    
    public static function get_lock_fn() {
        return ROOT_NS\util\Fs::fn_fetcher_lock(static::get_domain());
    }
    
    public function has_cache() {
        foreach($this->_fetch_queue as $local_fn => $entry) {
            if (!file_exists($local_fn))
                return false;
        }

        return true;
    }

    public function is_cache_valid() {
        foreach($this->_fetch_queue as $local_fn => $entry) {
            if (ROOT_NS\util\Fs::file_age($local_fn) > $this->config['expire_hot_s'])
                return false;
        }

        return true;
    }

    // ===========================
    // Queue management
    // ===========================

    private $_fetch_queue = [];

    public function queue($local_fn, $remote_url, $options = []) {
        if (in_array('ignore_cache', $this->config)) {
            if (!in_array('ignore_cache', $options))
                $options[] = 'ignore_cache';
        }

        if (!$this->can_queue($local_fn, $remote_url, $options))
            throw new Exception;

        // cache hit
        if ($this->is_cache_hot($local_fn, $options))
            return false;
        
        $this->_fetch_queue[$local_fn] = [$remote_url, $options];
        return true;
    }

    public function dequeue($local_fn) {
        unset($this->_fetch_queue[$local_fn]);
    }

    protected function can_queue($local_fn, $remote_url, $options = []) {
        return true;
    }

    // ===========================
    // Do the sync
    // ===========================

    /**
     * 下載列表中的檔案；
     * 若同時間有其它運行中的 fetcher (使用 lockfile 判斷)
     * 
     * @return int      number of files fetched
     */
    public function sync() {
        $cnt = 0;
        while($buffer = array_splice($this->_fetch_queue, 0, $this->config['max_threads'])) {
            $cnt += $this->sync_worker($buffer);
        }

        return $cnt;
    }

    protected function sync_worker($fetch_queue) {
        $joblist = [];
        $cnt = 0;

        $queue = new \cURL\RequestsQueue;
        $queue->getDefaultOptions()
            ->set(CURLOPT_TIMEOUT, 10)
            ->set(CURLOPT_RETURNTRANSFER, true);

        foreach($fetch_queue as $local_fn => $entry) {
            $req = $this->curl_builder($entry[0], $entry[1]);
            $joblist[$local_fn] =  $req->getUID();
            $queue->attach($req);
        }

        $queue->addListener('complete', function(\cURL\Event $event) use (&$joblist, &$cnt){
            $uid = $event->request->getUID();
            $local_fn = array_search($uid, $joblist);

            if (false === $local_fn)
                throw new Excpetion("localfn '$local_fn' not found");

            ROOT_NS\util\Fs::mkdir(dirname($local_fn));
            ROOT_NS\util\Fs::fdump($local_fn, $event->response->getContent());
            unset($joblist[$local_fn]);

            $cnt++;
        }) ;

        $queue->send();
        return $cnt;
    }

    protected function curl_builder($remote_url, $options) {
        return new \cURL\Request($remote_url);
    }
    
    // ===========================
    // Cache
    // ===========================
    
    /**
     * 檢查是否有本地 hot cache
     * 
     * @param  [type]  $local_fn   [description]
     * @param  [type]  $options    [description]
     * @return boolean             [description]
     */
    protected function is_cache_hot($local_fn, $options = []) {
        if (in_array('ignore_cache', $options))
            return false;

        // local file modified recently
        if (ROOT_NS\util\Fs::file_age($local_fn) <= $this->config['expire_hot_s']) {
            return true;
        }

        // If amazon allows browser cache, do it here
        // Ref: ETag, Expires, etc.
        return false;
    }

}
