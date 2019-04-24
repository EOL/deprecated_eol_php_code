<?php
namespace php_active_record;
/* connector: [eol_v3_api.php]
This script uses the different means to access the EOL V3 API.
First client is DATA-1807: EOL stats resource
*/
class Eol_v3_API
{
    function __construct($folder = null, $query = null)
    {
        /* add: 'resource_id' => "eol_api_v3" ;if you want to add the cache inside a folder [eol_api] inside [eol_cache] */
        $this->download_options = array(
            'resource_id'        => 'eol_api_v3',  //resource_id here is just a folder name in cache
            'expire_seconds'     => false,         //maybe 1 month to expire
            'download_wait_time' => 500000, 'timeout' => 60*3, 'download_attempts' => 1, 'delay_in_minutes' => 0.5);

        if(Functions::is_production()) $this->download_options['cache_path'] = "/extra/eol_php_cache/";
        else                           $this->download_options['cache_path'] = "/Volumes/Thunderbolt4/eol_cache/";      //used in Functions.php for all general cache

        $this->api['Pages'] = "http://eol.org/api/pages/1.0.json?batch=false&images_per_page=75&images_page=1&videos_per_page=75&videos_page=1&sounds_per_page=75&sounds_page=1&maps_per_page=75&maps_page=1&texts_per_page=75&texts_page=1&iucn=false&subjects=overview&licenses=all&details=true&common_names=true&synonyms=true&references=true&taxonomy=true&vetted=0&cache_ttl=&language=en&id=";
        $this->api['DataObjects'][0] = "http://eol.org/api/data_objects/1.0/";
        $this->api['DataObjects'][1] = ".json?taxonomy=true&cache_ttl=";
        // e.g. http://eol.org/api/data_objects/1.0/19173106.json?taxonomy=true&cache_ttl=
        // max of 7 simultaneous api calls, still works OK
        
        // for creating archives
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
    }
    function start()
    {
        /* normal operation
        if(Functions::is_production()) $path = "/extra/eol_php_code_public_tmp/google_maps/taxon_concept_names.tab";
        else                           $path = "/Volumes/Thunderbolt4/z backup of AKiTiO4/z backup/eol_php_code_public_tmp/google_maps old/taxon_concept_names.tab";
        self::process_all_eol_taxa($path); return;                    //make use of tab-delimited text file from JRice
        */
        
        // /* tests
        $scinames = array();                                        //make use of manual taxon list
        // $scinames["baby Isaiah"] = 1;
        $scinames["Chanos chanos"] = 224731;
        // $scinames["Gadus morhua"] = 206692;
        foreach($scinames as $sciname => $taxon_concept_id) self::main_loop($sciname, $taxon_concept_id);
        // */
    }
    private function process_all_eol_taxa($path, $listOnly = false)
    {
        if($listOnly) $list = array();
        $i = 0;
        foreach(new FileIterator($path) as $line_number => $line) { // 'true' will auto delete temp_filepath
            $line = explode("\t", $line);
            $taxon_concept_id = $line[0];
            if(!$line[1]) continue;
            $sciname = Functions::canonical_form($line[1]);
            if($listOnly) {
                $list[$sciname] = $taxon_concept_id;
                continue;
            }
            $i++;

            if(stripos($sciname, " ") !== false) { //only species-level taxa - if required this way
            // if(true) { //all taxa - orig
                //==================
                /*
                $m = 75000;
                $cont = false;
                // if($i >=  1    && $i < $m)    $cont = true; done
                // if($i >=  $m   && $i < $m*2)  $cont = true; done
                if(!$cont) continue;
                */
                //==================
                
                if(($i % 100) == 0) echo "\n".number_format($i).". [$sciname][tc_id = $taxon_concept_id]";
                // $taxon_concept_id = 46559686; //force - debug only
                self::api_using_tc_id($taxon_concept_id);
                // if($i >= 5) break; //debug
            }
            // else echo "\n[$sciname] will pass higher-level taxa at this time...\n";
        }//end loop
        if($listOnly) return $list;
    }
    private function api_using_tc_id($taxon_concept_id)
    {
        if($json = Functions::lookup_with_cache($this->api['Pages'].$taxon_concept_id, $this->download_options)) {
            $arr = json_decode($json, true);
            $stats = self::compute_totals($arr);
            $objects = $arr['dataObjects'];
            echo "\nobjects count = " . count($objects)."\n";
            return; //debug
            foreach($objects as $o) {
                echo "\n" . $o['dataObjectVersionID'];
                if($o['dataType'] == "http://purl.org/dc/dcmitype/Text" && strlen($o['description']) >= 199) //cache if desc is long since in tsv descs are substring of 200 chars only
                {
                    $objects = self::get_objects($o['dataObjectVersionID']);
                    foreach($objects['dataObjects'] as $o) echo " - " . @$o['mimeType'];
                    // print_r($objects);
                }
            }
        }
    }
    private function compute_totals($arr)
    {
        print_r($arr); exit;
        
    }
    private function get_objects($data_object_id)
    {
        $call = $this->api['DataObjects'][0].$data_object_id.$this->api['DataObjects'][1];
        if($json = Functions::lookup_with_cache($call, $this->download_options)) return json_decode($json, true);
        return false;
    }
    private function format_datatype_from_api2tsv($str)
    {
        /*
        http://purl.org/dc/dcmitype/MovingImage http://purl.org/dc/dcmitype/Sound http://purl.org/dc/dcmitype/StillImage http://purl.org/dc/dcmitype/Text
        ["Image"] => ["Video"] => ["Sound"] =>
        */ 
        $str = str_replace('http://purl.org/dc/dcmitype/', "", $str);
        $str = str_replace("MovingImage", "Video", $str);
        $str = str_replace("StillImage", "Image", $str);
        return $str;
    }
    private function main_loop($sciname, $taxon_concept_id = null)
    {
        self::api_using_tc_id($taxon_concept_id);
    }
}
?>