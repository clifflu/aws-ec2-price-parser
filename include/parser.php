<?php
/**
 * parse.php - 解析由 fetch.php 抓下來的檔案，重整並輸出
 * 
 */
namespace clifflu\aws_ec2_price_tool;

class Parser extends base\Util {
    /**
     * Parse fetched files
     * @return [type] [description]
     */
    public function parsed() {
        $output = array();
        $fetch_list = $this->config['fetch']['files'] ;

        foreach ($fetch_list as $fn)
            $this->parse_file($fn, $output);

        return $this->truncate_nulls($output);
    }

    /**
     * 開啟並分析 fn，並將資料存至 tbl. 由檔名猜測對應的 os 與 term.
     * @param  [type] $fn  [description]
     * @param  [type] $tbl [description]
     * @return [type]      [description]
     */
    protected function parse_file($fn, &$tbl) {
        $c_os = $this->guess_os($fn);
        $c_term = $this->guess_term($fn);

        if (!($c_os && $c_term))
            return;

        $src = json_decode(file_get_contents($this->local_fn($fn)), true);

        // @todo: Currency and Version check

        foreach ($src['config']['regions'] as $src_regional) {
            $c_region = lookup_dict($src_regional['region'], $this->config['remap']['regions']);
            
            // @todo: check region

            if (!isset($tbl[$c_region]))
                $tbl[$c_region] = array();

            if (!isset($tbl[$c_region][$c_os]))
                $tbl[$c_region][$c_os] = array();

            $this->parse_instance_type($src_regional['instanceTypes'], $c_term, $tbl[$c_region][$c_os]);
        }
    }

    protected function guess_os($fn) {
        foreach ($this->config['tags']['oses'] as $os => $desc) {
            if (strncmp($os.'-', $fn, strlen($os)+1) === 0)
                return $os;
        }
        return false;
    }

    /**
     * 猜測可能的 term; 由於 RI 合約年數不由檔名決定，因此只回傳 od|l|m|h 或 false
     * @param  [type] $fn [description]
     * @return [type]     [description]
     */
    protected function guess_term($fn){
        if (!preg_match("/-(od|ri-(?:heavy|medium|light))$/", $fn, $matches))
            return false;
        
        switch ($matches[1]){
            case 'od': return 'od';
            case 'ri-heavy': return 'h';
            case 'ri-medium': return 'm';
            case 'ri-light': return 'l';
        }
        return false;
    }

    /**
     * Fix some possible typo in AWS data files
     * cc1.8xlarge => cc2.8xlarge
     * cc2.4xlarge => cg1.4xlarge
     * Ref:
     * - http://aws.amazon.com/ec2/instance-types/instance-details/
     * - https://github.com/erans/ec2instancespricing/commit/71a24aaef1d2ceed2f3e4cefecc9b34b6d5f35b6 
     */
    protected function fix_instance_size($c_instance, $c_size) {
        foreach ($this->config['remap']['instance_size'] as $typo) {
            if ($typo['replace']['instance'] == $c_instance and $typo['replace']['size'] == $c_size)
                return array($typo['with']['instance'], $typo['with']['size']);
        }
        return array($c_instance, $c_size);
    }

    protected static function is_term_od($term) {
        return $term == 'od';
    }

    protected function parse_instance_type($src_its, $c_term, &$tbl_its ) {
        foreach($src_its as $src_it) {
            $c_instance = lookup_dict($src_it['type'], $this->config['remap']['instances']);

            foreach ($src_it['sizes'] as $src_sz) {
                $c_size = lookup_dict($src_sz['size'], $this->config['remap']['sizes']);

                list($fixed_instance, $fixed_size) = $this->fix_instance_size($c_instance, $c_size);
                
                if (!isset($tbl_its[$fixed_instance]))
                    $tbl_its[$fixed_instance] = array();
                
                if (!isset($tbl_its[$fixed_instance][$fixed_size]))
                    $tbl_its[$fixed_instance][$fixed_size] = array();

                if (static::is_term_od($c_term))
                    $this->parse_od($src_sz, $tbl_its[$fixed_instance][$fixed_size]);
                else
                    $this->parse_ri($src_sz, $c_term, $tbl_its[$fixed_instance][$fixed_size]);
            }
        }
    }

    protected function parse_od($src_sz, &$tbl_sz) {
        $src_prices = $src_sz['valueColumns'][0]['prices'];
        $tbl_sz['od'] = array(num($src_prices['USD']));
    }

    protected function parse_ri($src_sz, $c_term, &$tbl_sz) {
        $src_vcs = $src_sz['valueColumns'];

        foreach ($src_vcs as $vc) {
            switch($vc['name']){
                case 'yrTerm1':
                    $upfront_1 = num($vc['prices']['USD']);
                    break;
                case 'yrTerm3':
                    $upfront_3 = num($vc['prices']['USD']);
                    break;
                case 'yrTerm1Hourly':
                    $hourly_1 = num($vc['prices']['USD']);
                    break;
                case 'yrTerm3Hourly':
                    $hourly_3 = num($vc['prices']['USD']);
                    break;
            }
        }

        if (isset($upfront_1) && isset($hourly_1))
            $tbl_sz["y1$c_term"] = array($hourly_1, $upfront_1);

        if (isset($upfront_3) && isset($hourly_3))
            $tbl_sz["y3$c_term"] = array($hourly_3, $upfront_3);
    }
}