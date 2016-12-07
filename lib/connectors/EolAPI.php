<?php
namespace php_active_record;
/* connector: [eol_api.php]
This script uses the different means to access the EOL API.
Can be used for OpenData's customized subsets.
*/

class EolAPI
{
    function __construct($folder = null, $query = null)
    {
        /* add: 'resource_id' => "gbif" ;if you want to add cache inside a folder [gbif] inside [eol_cache_gbif] */
        $this->download_options = array(
            'cache_path'         => '/Volumes/Thunderbolt4/eol_cache/',     //used in Functions.php for all general cache
            'resource_id'        => 'eol_api',                              //resource_id here is just a folder name in cache
            'expire_seconds'     => false,                                  //another option is 1 year to expire
            'download_wait_time' => 3000000, 'timeout' => 600, 'download_attempts' => 1, 'delay_in_minutes' => 1);

        $this->api['Pages'] = "http://eol.org/api/pages/1.0.json?batch=false&images_per_page=75&images_page=1&videos_per_page=75&videos_page=1&sounds_per_page=75&sounds_page=1&maps_per_page=75&maps_page=1&texts_per_page=75&texts_page=1&iucn=false&subjects=overview&licenses=all&details=true&common_names=true&synonyms=true&references=true&taxonomy=true&vetted=0&cache_ttl=&language=en&id=";
        $this->api['DataObjects'][0] = "http://eol.org/api/data_objects/1.0/";
        $this->api['DataObjects'][1] = ".json?taxonomy=true&cache_ttl=&language=en";
        // e.g. http://eol.org/api/data_objects/1.0/19173106.json?taxonomy=true&cache_ttl=&language=en
    }

    function start()
    {
        self::process_all_eol_taxa(); return;                    //make use of tab-delimited text file from JRice
        // self::process_hotlist_spreadsheet(); return;             //make use of hot list spreadsheet from SPG
        // self::process_DL_taxon_list(); return;                   //make use of taxon list from DiscoverLife
        
        $scinames = array();                                        //make use of manual taxon list
        $scinames["Phalacrocorax penicillatus"] = 1048643;
        $scinames["Chanos chanos"] = 224731;
        $scinames["Gadus morhua"] = 206692;
        
        foreach($scinames as $sciname => $taxon_concept_id) self::main_loop($sciname, $taxon_concept_id);
        
        /* API result:
        [offset]        => 0
        [limit]         => 20
        [endOfRecords]  => 
        [count]         => 78842
        [results]       => Array
        */
    }
    
    private function process_all_eol_taxa($listOnly = false)
    {
        if($listOnly) $list = array();
        $path = DOC_ROOT . "/public/tmp/google_maps/taxon_concept_names.tab";
        $i = 0;
        foreach(new FileIterator($path) as $line_number => $line) // 'true' will auto delete temp_filepath
        {
            $line = explode("\t", $line);
            $taxon_concept_id = $line[0];
            $sciname          = Functions::canonical_form($line[1]);
            if($listOnly)
            {
                $list[$sciname] = $taxon_concept_id;
                continue;
            }
            $i++;

            // if(stripos($sciname, " ") !== false) //only species-level taxa
            if(true) //all taxa
            {
                //==================
                // /*
                $m = 50000;
                $cont = false;
                // if($i >=  1    && $i < $m)    $cont = true;
                if($i >=  $m   && $i < $m*2)  $cont = true;
                // if($i >=  $m*2 && $i < $m*3)  $cont = true;
                // if($i >=  $m*3 && $i < $m*4)  $cont = true;
                // if($i >=  $m*4 && $i < $m*5)  $cont = true;
                // if($i >=  $m*5 && $i < $m*6)  $cont = true;
                if(!$cont) continue;
                // */
                //==================
                
                echo "\n".number_format($i).". [$sciname][tc_id = $taxon_concept_id]";
                if($json = Functions::lookup_with_cache($this->api['Pages'].$taxon_concept_id, $this->download_options))
                {
                    $arr = json_decode($json, true);
                    $objects = $arr['dataObjects'];
                    echo " - " . count($objects);
                    // continue; //debug
                    foreach($objects as $o)
                    {
                        echo "\n" . $o['dataObjectVersionID'];
                        $objects = self::get_objects($o['dataObjectVersionID']);
                        foreach($objects['dataObjects'] as $o) echo " - " . @$o['mimeType'];
                        // print_r($objects);
                    }
                }
                
            }
            // else echo "\n[$sciname] will pass higher-level taxa at this time...\n";
        }//end loop
        if($listOnly) return $list;
    }
    
    private function get_objects($data_object_id)
    {
        $call = $this->api['DataObjects'][0].$data_object_id.$this->api['DataObjects'][1];
        if($json = Functions::lookup_with_cache($call, $this->download_options)) return json_decode($json, true);
        return false;
    }

    private function xxx()
    {
        $expire_seconds = false;

        /* debug
        if($sciname == "Falco chrysaetos") $expire_seconds = true;
        else                               $expire_seconds = false;
        */
        
        /*              http://eol.org/api/search/1.0.xml?q=Xanthopsar+flavus&page=1&exact=false&filter_by_taxon_concept_id=&filter_by_hierarchy_entry_id=&filter_by_string=&cache_ttl= */
        $search_call = "http://eol.org/api/search/1.0.xml?q=" . $sciname .  "&page=1&exact=false&filter_by_taxon_concept_id=&filter_by_hierarchy_entry_id=&filter_by_string=&cache_ttl=";
        if($xml = Functions::lookup_with_cache($search_call, array('timeout' => 30, 'expire_seconds' => $expire_seconds, 'resource_id' => 'eol_api'))) //resource_id here is just a folder name in cache
        {
            $xml = simplexml_load_string($xml);
            $sciname = Functions::canonical_form($sciname);
            if($sciname == Functions::canonical_form(@$xml->entry[0]->title)) return false; //sciname is not a synonym but accepted name
            else
            {
                $titles = array();
                foreach($xml->entry as $entry) $titles[] = Functions::canonical_form($entry->title);
                if(in_array($sciname, $titles)) return false; //sciname is not a synonym but accepted name
                else return true;
            }
        }
        return false;
    }

    private function main_loop($sciname, $taxon_concept_id = false)
    {
        $sciname = Functions::canonical_form($sciname); echo "\n[$sciname]\n";
    }

    private function get_sciname($r)
    {
        // if($r->taxonRank == "SPECIES") return $r->species;
        return $r->scientificName;
    }

    private function process_hotlist_spreadsheet()
    {
        require_library('XLSParser');
        $parser = new XLSParser();
        $families = array();
        $doc = "http://localhost/eol_php_code/public/tmp/spreadsheets/SPG Hotlist Official Version.xlsx";
        // $doc = "http://localhost/~eolit/eli/eol_php_code/public/tmp/spreadsheets/SPG Hotlist Official Version.xlsx"; //for MacBook
        echo "\n processing [$doc]...\n";
        if($path = Functions::save_remote_file_to_local($doc, array("timeout" => 3600, "file_extension" => "xlsx", 'download_attempts' => 2, 'delay_in_minutes' => 2)))
        {
            $arr = $parser->convert_sheet_to_array($path);
            $i = -1;
            foreach($arr['Animals'] as $sciname)
            {
                $i++;
                $sciname = trim(Functions::canonical_form($sciname));
                // if(stripos($sciname, " ") !== false) //process only species-level taxa
                if(true)
                {
                    $taxon_concept_id = $arr['1'][$i];
                    echo "\n$i. [$sciname][$taxon_concept_id]";
                    //==================
                    /*
                    $m = 10000;
                    $cont = false;
                    // if($i >=  1    && $i < $m)    $cont = true;
                    // if($i >=  $m   && $i < $m*2)  $cont = true;
                    // if($i >=  $m*2 && $i < $m*3)  $cont = true;
                    // if($i >=  $m*3 && $i < $m*4)  $cont = true;
                    // if($i >=  $m*4 && $i < $m*5)  $cont = true;
                    // if($i >=  $m*5 && $i < $m*6)  $cont = true;
                    // if($i >=  $m*6 && $i < $m*7)  $cont = true;
                    if(!$cont) continue;
                    */
                    self::main_loop($sciname, $taxon_concept_id);
                    //==================
                    // break; //debug - process only 1
                }
            }
            unlink($path);
        }
        else echo "\n [$doc] unavailable! \n";
    }

    private function process_DL_taxon_list()
    {
        $temp_filepath = Functions::save_remote_file_to_local(self::DL_MAP_SPECIES_LIST, array('timeout' => 4800, 'download_attempts' => 5));
        if(!$temp_filepath)
        {
            echo "\n\nExternal file not available. Program will terminate.\n";
            return;
        }
        $i = 0;
        foreach(new FileIterator($temp_filepath, true) as $line_number => $line) // 'true' will auto delete temp_filepath
        {
            $i++;
            if($line)
            {
                $m = 10000;
                $cont = false;
                if($i >=  1    && $i < $m)    $cont = true;
                // if($i >=  $m   && $i < $m*2)  $cont = true;
                // if($i >=  $m*2 && $i < $m*3)  $cont = true;
                // if($i >=  $m*3 && $i < $m*4)  $cont = true;
                // if($i >=  $m*4 && $i < $m*5)  $cont = true;
                if(!$cont) continue;
                
                $arr = explode("\t", $line);
                $sciname = trim($arr[0]);
                echo "\n[$sciname]\n";
                self::main_loop($sciname);
            }
            // if($i >= 5) break; //debug
        }
    }

}
?>