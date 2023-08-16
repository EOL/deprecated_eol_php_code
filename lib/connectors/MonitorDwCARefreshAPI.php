<?php
namespace php_active_record;
/* 
*/
class MonitorDwCARefreshAPI
{
    function __construct()
    {
        $this->download_options = array('cache' => 1, 'resource_id' => 'dwca_monitor', 'timeout' => 3600, 'download_attempts' => 1, 
        'expire_seconds' => 60*60*1*1); // ideal 1 hr cache
        $this->download_options['expire_seconds'] = false; //during dev only
        $this->harvest_dump = "https://editors.eol.org/eol_php_code/applications/content_server/resources/EOL_FreshData_connectors.txt";
        $this->fields = array("ID", "Date", "Stats");
    }
    function start($dwca_id)
    {   
        if($dwca_id == 'wikipedia-en') $dwca_id = 80;
        if($dwca_id == 'wikipedia-de') $dwca_id = 957;

        // echo "\n".php_sapi_name(); exit("\n");
        if(php_sapi_name() == "cli") $sep = "\n";
        else {
                                     $sep = "<br>";
                                    //  echo "<font face='Courier' size='small'>";
                                     echo '<p style="font-size:15px; font-family:Courier New">';
        }
        $options = $this->download_options;
        if($html = Functions::lookup_with_cache($this->harvest_dump, $options)) {
            $rows = explode("\n", $html);
            krsort($rows);
            // print_r($arr);
            $id_shown_YN = false;
            foreach($rows as $row) {
                if(!$row) continue;
                $rec = explode("\t", $row); //print_r($rec);
                /* Array (
                    [0] => wikipedia-eml
                    [1] => Wed 2023-08-16 09:27:06 AM
                    [2] => {"media_resource.tab":270, "taxon.tab":744, "time_elapsed":{"sec":2.49, "min":0.04, "hr":0}}
                ) */
                $rec = array_map('trim', $rec);
                $rek = array();
                $i = -1;
                foreach($rec as $r) { $i++;
                    $field = $this->fields[$i];
                    $rek[$field] = $r;
                }
                // print_r($rek); //exit;
                /* Array (
                    [ID] => wikipedia-eml
                    [Date] => Wed 2023-08-16 09:27:06 AM
                    [Stats] => {"media_resource.tab":270, "taxon.tab":744, "time_elapsed":{"sec":2.49, "min":0.04, "hr":0}}
                ) */
                if($rek['ID'] == $dwca_id) {
                    if(!$id_shown_YN) {
                        echo "\n".$rek['ID'];
                        $id_shown_YN = true;
                    }
                    echo $sep.self::format_str($rek['Date'], 35);
                    echo      self::format_str($rek['Stats'], 100);
                }
            }
            echo $sep."--end--";
        }
    }
    private function format_str($str, $padding)
    {
        return str_pad($str, $padding, "_", STR_PAD_RIGHT);
    }
}
?>