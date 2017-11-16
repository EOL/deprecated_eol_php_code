<?php
namespace php_active_record;
/*  connector: [dwca_utility.php _ 24]
    
*/
class AntWebDataAPI
{
    function __construct()
    {
        // $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        // $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        // $this->taxon_ids = array();
        // $this->occurrence_ids = array();
        // $this->media_ids = array();
        // $this->agent_ids = array();
        // $this->debug = array();
        $this->api['genus_list'] = 'http://www.antweb.org/api/v2/?rank=genus&limit=100&offset=';
        $this->api['specimens'] = 'http://www.antweb.org/api/v2/?limit=100&offset='; //&genus=Acanthognathus
        
        $this->limit = 100;
        $this->download_options = array("timeout" => 60*60, "expire_seconds" => 60*60*24*25);
    }
    
    function start($harvester, $row_type)
    {
        $this->uri_values = Functions::get_eol_defined_uris(false, true); //1st param: false means will use 1day cache | 2nd param: opposite direction is true
        // print_r($this->uri_values);
        // echo("\n Philippines: ".$this->uri_values['Philippines']."\n"); exit;
        $genus_list = self::get_all_genus($harvester->process_row_type($row_type));
        echo "\n total genus: ".count($genus_list);
        /* $genus_list = self::get_all_genus_using_api(); //working but instead of genus; family values are given by API */
        self::process_genus($genus_list);
    }
    
    private function process_genus($genus_list)
    {
        foreach($genus_list as $genus) {
            echo "\n processing $genus...";
            $specimens = self::get_specimens_per_genus($genus);
            exit("\n".count($specimens)."\n");
        }
    }

    private function get_specimens_per_genus($genus)
    {
        $final = array();
        $offset = 0;
        while(true) {
            $url = $this->api['specimens'].$offset."&genus=$genus";
            echo "\n[$url]";
            if($json = Functions::lookup_with_cache($url, $this->download_options)) {
                $arr = json_decode($json, true);
                $final = array_merge($final, $arr['specimens']);
                if(count($arr['specimens']) < $this->limit) break;
            }
            $offset += $this->limit;
        }
        return $final;
    }


    private function get_all_genus_using_api()
    {
        $final = array();
        $offset = 0;
        while(true) {
            $url = $this->api['genus_list'].$offset;
            if($json = Functions::lookup_with_cache($url, $this->download_options)) {
                $arr = json_decode($json, true);
                foreach($arr['specimens'] as $specimen) $final[] = $specimen['genus'];
                if(count($arr['specimens']) < $this->limit) break;
            }
            $offset += $this->limit;
        }
        return array_unique($final);
    }
    
    // /* working well but not used. Used API instead
    private function get_all_genus($records)
    {
        $genus_list = array();
        foreach($records as $rec) {
            // $keys = array_keys($rec); print_r($keys);
            $sciname = $rec['http://rs.tdwg.org/dwc/terms/scientificName'];
            $arr = explode(" ", $sciname);
            $genus = $arr[0];
            $genus_list[$genus] = '';
        }
        return array_keys($genus_list);
    }
    // */
}
?>
