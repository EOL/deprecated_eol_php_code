<?php
namespace php_active_record;
/* */
class ResourceConnectorMngmt
{
    function __construct($folder)
    {
        $this->log = 'https://editors.eol.org/eol_php_code/applications/content_server/resources/EOL_FreshData_connectors.txt';
        $this->download_options = array('cache' => 1, 'expire_seconds' => 60*60*24, 'download_wait_time' => 500000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 0.5);
        $this->debug = array();
    }
    function get_harvests_for_resource_id($resource_id, $options = array(), $sought = array())
    {
        $final = array(); $summary = array();
        if($month_year = @$sought['month_year']) {
            $summary[$resource_id]["sought date"] = 'not present';
        }
        
        if(!$options) $options = $this->download_options;
        $local = Functions::save_remote_file_to_local($this->log, $options);
        $i = 0;
        $fields = array('resource', 'date', 'info');
        foreach(new FileIterator($local, false, true) as $line => $row) { //2nd and 3rd param; false and true respectively are default values
            $i++;
            $tmp = explode("\t", $row);
            // if(($i % 1000) == 0) echo "\n count:[".number_format($i)."] ";
            if(!$row) continue;
            $rec = array(); $k = 0;
            foreach($fields as $field) {
                $rec[$field] = $tmp[$k];
                $k++;
            }
            $rec = array_map('trim', $rec);
            // print_r($rec); //exit;
            /*Array(
                [Resource] => MarylandBio
                [Date] => Tuesday 2017-09-19 08:31:39 AM
                [Info] => {"observations.txt":8390}
            )*/
            
            $date_string = strtotime($rec['date']);
            $date_format = date('m/d/Y', $date_string);
            // print_r($date_format); echo("\n");
            
            if($rec['resource'] == $resource_id) {
                $final[] = $rec;
                // print_r($rec);
                if($month_year = @$sought['month_year']) {
                    $current_month_year = date('m_Y', $date_string);
                    // echo("\n[$current_month_year]\n");
                    if($current_month_year == $month_year) $summary[$resource_id]["sought date"] = 'present';
                }
            }
            
        } //end loop
        // /* good debug
        if($GLOBALS['ENV_DEBUG']) {
            print_r($final);
            print_r($summary);
        }
        // */
        if($summary[$resource_id]["sought date"] == 'not present') {
            if($final) $this->debug['no latest harvest']['with previous harvests'][$resource_id] = '';
            /*
            else       $this->debug['not present']['without previous harvests'][$resource_id] = '';
            */
        }
        
        if($final) {
            $ret = self::compare_last_two_harvests($final);
            if($ret < 0) $this->debug['latest harvest is less than previous'][$resource_id] = "articles decreased by ".$ret*-1;
        }
        
        unlink($local);
    }
    private function compare_last_two_harvests($final)
    {   /*
        [25] => Array(
                [resource] => wikipedia-es
                [date] => Wednesday 2020-07-29 04:21:20 PM
                [info] => {"media_resource.tab":319705, "taxon.tab":175913, "time_elapsed":{"sec":28860.25, "min":481, "hr":8.02}}
            )

        [26] => Array(
                [resource] => wikipedia-es
                [date] => Mon 2020-10-12 01:06:01 AM
                [info] => {"media_resource.tab":324055, "taxon.tab":178175, "time_elapsed":{"sec":28331.05, "min":472.18, "hr":7.87}}
            )
        */
        $total = count($final);
        $last_rec = $total-1;
        $second_to_last_rec = $last_rec-1;
        // print_r($final[$second_to_last_rec]);
        // print_r($final[$last_rec]); exit;
        
        $json_1 = $final[$second_to_last_rec]['info'];
        $arr1 = json_decode($json_1, true);
        $media1 = $arr1['media_resource.tab'];

        $json_2 = $final[$last_rec]['info'];
        $arr2 = json_decode($json_2, true);
        $media2 = $arr2['media_resource.tab'];
        
        $difference = $media2 - $media1;
        // /* good debug
        if($GLOBALS['ENV_DEBUG']) {
            print_r($arr1); print_r($arr2);
            echo "\n$media1 --- $media2 --- $difference\n";
        }
        // */
        if($media2 < $media1) return $difference; //'latest harvest is less than previous';
        return true;
    }
    function get_harvests_for_wikipedias($options = array(), $sought = array())
    {
        require_library('connectors/DwCA_Aggregator_Functions');
        require_library('connectors/DwCA_Aggregator');
        $func = new DwCA_Aggregator('');
        $orig = array('80', '957', 'es', 'it', 'fr', 'ja', 'ko', 'pt', 'ru', 'zh', 'nl', 'pl', 'uk', 'hy', 'mrj', 'pms', 'ga');
        $langs = $func->get_langs(); // print_r($langs);
        $final = array_merge($orig, $langs[1]);
        $final = array_merge($final, $langs[2]);
        echo "\ntotal: ".count($final)."\n"; //exit;
        $i = 0;
        foreach($final as $lang) { $i++;
            if(($i % 20) == 0) echo "\n [".number_format($i)."] ";
            if(is_numeric($lang)) $resource_id = $lang;
            else $resource_id = 'wikipedia-'.$lang;
            self::get_harvests_for_resource_id($resource_id, $options, $sought); //2nd param is download_options
            // exit;
        }
        echo "\n"; print_r($this->debug);
    }
    /*
    private function main()
    {
        foreach(new FileIterator($this->extension_path.$meta['taxon_file'], false, true, @$this->dwc['iterator_options']) as $line => $row) { //2nd and 3rd param; false and true respectively are default values
            $i++;
            if(($i % 500000) == 0) echo "\n count:[".number_format($i)."] ";
            if($meta['ignoreHeaderLines'] && $i == 1) continue;
            if(!$row) continue;
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta['fields'] as $field) {
                $rec[$field] = $tmp[$k];
                $k++;
            }
            $rec = array_map('trim', $rec);
            print_r($rec); exit;
        } //end loop
    }
    */
}
?>