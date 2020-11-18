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
        if(!$options) $options = $this->download_options;
        $local = Functions::save_remote_file_to_local($this->log, $options);
        $i = 0;
        $fields = array('resource', 'date', 'info');
        foreach(new FileIterator($local, false, true) as $line => $row) { //2nd and 3rd param; false and true respectively are default values
            $i++;
            $tmp = explode("\t", $row);
            if(($i % 1000) == 0) echo "\n count:[".number_format($i)."] ";
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
                print_r($rec);
                if($month_year = @$sought['month_year']) {
                    $current_month_year = date('m_Y', $date_string);
                    echo("\n[$current_month_year]\n");
                    if($current_month_year == $month_year) $summary[$resource_id]["sought date $month_year"] = 'present';
                }
            }
            
        } //end loop
        print_r($final);
        print_r($summary);
        print_r($sought);
        unlink($local);
    }
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
}
?>