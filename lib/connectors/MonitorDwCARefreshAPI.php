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
        // $this->download_options['expire_seconds'] = false; //during dev only
        $this->harvest_dump = "https://editors.eol.org/eol_php_code/applications/content_server/resources/EOL_FreshData_connectors.txt";
        $this->fields = array("ID", "Date", "Stats");
        $this->api_package_list = "https://opendata.eol.org/api/3/action/package_list";
        $this->api_package_show = "https://opendata.eol.org/api/3/action/package_show?id=";
        if(Functions::is_production()) $this->lookup_url = "https://editors.eol.org/eol_php_code/update_resources/connectors/monitor_dwca_refresh.php?dwca_id=";
        else                           $this->lookup_url = "http://localhost/eol_php_code/update_resources/connectors/monitor_dwca_refresh.php?dwca_id=";        
    }
    function start($dwca_id, $series)
    {   
        if($dwca_id == 'wikipedia-en') $dwca_id = 80;
        if($dwca_id == 'wikipedia-de') $dwca_id = 957;

        // echo "\n".php_sapi_name(); exit("\n");
        if(php_sapi_name() == "cli") {
            $this->sep = "\n";
            $pre_tag = "";
        }
        else {
                                     $this->sep = "<br>";
                                     $pre_tag = "<pre>";
                                    //  echo "<font face='Courier' size='small'>";
                                     echo '<p style="font-size:13px; font-family:Courier New">';
        }
        $found_hits_YN = false; //for series 1
        $possible_IDs = false; //for series 2
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
                //--------------------------------------------------------------------
                if($series == "1st") {
                    if($rek['ID'] == $dwca_id) { $found_hits_YN = true;
                        if(!$id_shown_YN) {
                            echo $this->sep . $rek['ID'];
                            $id_shown_YN = true;
                        }
                        echo $this->sep.self::format_str($rek['Date'], 35);
                        echo                             $rek['Stats']; //self::format_str($rek['Stats'], 150);
                    }    
                }
                //--------------------------------------------------------------------
                if($series == "2nd") {
                    if(stripos($rek['ID'], $dwca_id) !== false) { $found_hits_YN = true; //string is found
                        $possible_IDs[$rek['ID']] = '';
                    }    
                }
                //--------------------------------------------------------------------

            } //end foreach()
            // echo $this->sep."--end--".$this->sep;
        }
        if($series == "2nd") {
            if($possible_IDs) {
                echo $pre_tag;
                echo $this->sep."Possible IDs to use: "; //print_r($possible_IDs);
                foreach(array_keys($possible_IDs) as $lookup_id) {                    
                    if(stripos($lookup_id, "of6") !== false) continue; //string is found
                    self::display($dwca_id, $lookup_id);
                }
            }
            echo $this->sep."--end--".$this->sep;
            return $possible_IDs;
        }
        echo $this->sep."--end--".$this->sep;
        return $found_hits_YN;
    }
    function lookup_CKAN_for_DwCA_ID($dwca_id)
    {   $final = array();
        $options = $this->download_options;
        $options['expire_seconds'] = 60*60*1*1; //orig 1 hr expires
        if($json = Functions::lookup_with_cache($this->api_package_list, $options)) {
            $packages = json_decode($json);
            // print_r($packages); exit;
            foreach($packages->result as $ckan_resource_id) { // e.g. wikimedia
                $dwca_id = (string) $dwca_id;
                $ckan_resource_id = (string) $ckan_resource_id;

                // echo $this->sep . "[$dwca_id][$ckan_resource_id]"; exit;
                if(stripos($ckan_resource_id, $dwca_id) !== false) { //string is found
                    // exit("\n-found-\n");
                    $options['expire_seconds'] = 60*60*24*1; //orig 1 day expires
                    if($json = Functions::lookup_with_cache($this->api_package_show.$ckan_resource_id, $options)) {
                        $obj = json_decode($json);
                        // print_r($obj->result->resources); exit;
                        foreach($obj->result->resources as $res) {
                            $url = $res->url;
                            if(substr($url, -7) == ".tar.gz") {
                                // print_r($res); //exit;
                                $basename = pathinfo($url, PATHINFO_BASENAME);      //cicadellinaemetarecoded.tar.gz
                                $basename = str_replace(".tar.gz", "", $basename);  //cicadellinaemetarecoded
                                $final[$dwca_id][] = $basename;
                                // /* new
                                $this->info_basename_to_ckan_resource_id[$basename] = $ckan_resource_id; //for lookup below
                                // */
                            }
                        }
                    }    
                } //end if()
                // else echo " - not found";
            }
        }
        // print_r($final); echo "\n".count($final)."\n"; //good debug
        if($final) {
            foreach($final as $id => $lookup_ids) {
                foreach($lookup_ids as $lookup_id) self::display($id, $lookup_id);
            }
        }
        else echo $this->sep."Nothing found. Please try another ID.".$this->sep;
        echo $this->sep."--end--".$this->sep;
        return $final;
    }
    private function display($id, $lookup_id)
    {
        if(isset($this->info_basename_to_ckan_resource_id)) $ckan_resource_id = $this->info_basename_to_ckan_resource_id[$lookup_id];
        else                                                $ckan_resource_id = '';
        echo " ".$this->sep . self::format_str($id, 20) . " " . self::format_str($ckan_resource_id, 20) . " <a href='".$this->lookup_url . $lookup_id."'>$lookup_id</a>";
    }
    private function format_str($str, $padding)
    {
        return str_pad($str, $padding, "_", STR_PAD_RIGHT);
    }
}
?>