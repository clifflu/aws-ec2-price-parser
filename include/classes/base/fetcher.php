<?php
namespace clifflu\aws_tools\base;
use clifflu\aws_tools as ROOT_NS;

/**
 * Fetcher - Fetches remote files
 *
 * Usage:
 *     $f = Fetcher::forge($config);
 *     $f->queue(LOCAL_FN, REMOTE_URL, OPTIONS);
 *     $f->dequeue(LOCAL_FN);
 *     $f->fetch();
 */
class Fetcher extends Forge{
    private static $_fetch_queue = [];

    public static function defaults($config = []) {

        $_ = [
            'expire_hour' => 3,
            'max_threads' => 6,
        ];

        if ($config)
            $_ = array_replace_recursive($_, (array) $config);

        return parent::defaults($_);
    }

    public function queue($local_fn, $remote_url, $options = []) {
        if (!$this->can_queue($local_fn, $remote_url, $options))
            throw new Exception;

        // cache hit
        if ($this->has_valid_local_cache($local_fn, $remote_url, $options)) {
            return false;
        }
        
        self::$_fetch_queue[$local_fn] = [$remote_url, $options];
        return true;
    }

    public function dequeue($local_fn) {
        unset(self::$_fetch_queue[$local_fn]);
    }

    /**
     * 下載列表中的檔案；
     * 若同時間有其它運行中的 fetcher (使用 lockfile 判斷)
     * 
     * @return int      number of files fetched
     */
    public function fetch() {
        $cnt = 0;
        while($buffer = array_splice(self::$_fetch_queue, 0, $this->config['max_threads'])) {
            $cnt += $this->fetch_worker($buffer);
        }

        return $cnt;
    }

    protected function fetch_worker($fetch_queue) {
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

            $dir = dirname($local_fn);
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
                chmod($dir, 0775);
            }

            file_put_contents($local_fn, $event->response->getContent());
            chmod($local_fn, 0664);

            unset($joblist[$local_fn]);

            $cnt++;
        }) ;

        $queue->send();
        return $cnt;
    }

    protected function curl_builder($remote_url, $options) {
        return new \cURL\Request($remote_url);
    }

    protected function can_queue($local_fn, $remote_url, $options = []) {
        return true;
    }
    
    /**
     * Check if local cache has expired
     * 
     * @param  [type]  $local_fn   [description]
     * @param  [type]  $remote_url [description]
     * @param  [type]  $options    [description]
     * @return boolean             [description]
     *
     * @todo   should be called from fetch() instead of queue(), so it can fire multiple requests at once
     */
    protected function has_valid_local_cache($local_fn, $remote_url, $options = []) {
        if (in_array('ignore_cache', $options))
            return false;

        // local file modified recently
        if (ROOT_NS\util\Fs::file_age($local_fn) <= $this->config['expire_hour'] * 3600) {
            return true;
        }

        // If amazon allows browser cache, do it here
        // Ref: ETag, Expires, etc.
        return false;
    }
}
