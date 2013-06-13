<?php
namespace clifflu\aws_ec2_price_tool;

class Fetcher extends base\Util{
    private $_now = false;

    protected function __construct($config) {
        parent::__construct($config);
        $this->_now = time();
    }

    /**
     * Fetch remote files if needed
     * 
     * @return int number of files fetched
     */
    public function go() {
        $fetch_list = [];

        foreach ($this->config['fetch']['files'] as $fn) {
            $local_fn = $this->local_fn($fn);
            $aws_url = $this->aws_url($fn);

            if ($this->need_fetch($local_fn, $aws_url)) {
                $fetch_list[] = [
                    'local_fn' => $local_fn, 
                    'aws_url' => $aws_url,
                    'fn' => $fn,
                ];
            }
        }

        $start = microtime(true);
        $cnt = $this->fetch($fetch_list);
        $dur = microtime(true) - $start;

        printf("%d files fetched in %.2f seconds\n", $cnt, $dur);
    }
    
    protected function need_fetch($local_fn, $aws_url) {
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
    protected function fetch($filelist) {
        $cnt = count($filelist);

        while($buffer = array_splice($filelist, 0, $this->config['fetch']['max_threads'])) {
            $this->fetch_worker($buffer);
        }

        return $cnt;
    }

    protected function fetch_worker($filelist) {
        $queue = new \cURL\RequestsQueue;
        $queue->getDefaultOptions()
            ->set(CURLOPT_TIMEOUT, 10)
            ->set(CURLOPT_RETURNTRANSFER, true);

        foreach($filelist as $idx => $entry) {
            $req = new \cURL\Request($entry['aws_url']);
            $filelist[$idx]['UID'] = $req->getUID();
            $queue->attach($req);
        }

        $queue->addListener('complete', function(\cURL\Event $event) use (&$filelist){
            $uid = $event->request->getUID();
            foreach($filelist as $idx => $entry) {
                if ($entry['UID'] != $uid)
                    continue;

                // UID match
                file_put_contents($entry['local_fn'], $event->response->getContent());
            }
        }) ;

        $queue->send();
    }
}
