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
            'expire_seconds'     => 60*60*24*30, //maybe 1 month to expire
            'download_wait_time' => 500000, 'timeout' => 60*3, 'download_attempts' => 1, 'delay_in_minutes' => 0.5);

        if(Functions::is_production()) $this->download_options['cache_path'] = "/extra/eol_php_cache/";
        else                           $this->download_options['cache_path'] = "/Volumes/Thunderbolt4/eol_cache/";      //used in Functions.php for all general cache

        $this->api['Pages'] = "http://eol.org/api/pages/1.0.json?batch=false&images_per_page=75&images_page=1&videos_per_page=75&videos_page=1&sounds_per_page=75&sounds_page=1&maps_per_page=75&maps_page=1&texts_per_page=75&texts_page=1&iucn=false&subjects=overview&licenses=all&details=true&common_names=true&synonyms=true&references=true&taxonomy=true&vetted=0&cache_ttl=&language=en&id=";
        $this->api['Pages2'][0] = 'https://eol.org/api/pages/1.0/';
        $this->api['Pages2'][1] = '.json?details=true&xxx_per_page=75&xxx_page=';

        $this->api['DataObjects'][0] = "http://eol.org/api/data_objects/1.0/";
        $this->api['DataObjects'][1] = ".json?taxonomy=true&cache_ttl=";
        // e.g. http://eol.org/api/data_objects/1.0/19173106.json?taxonomy=true&cache_ttl=
        //      http://eol.org/api/data_objects/1.0/EOL-media-509-2828226.json?taxonomy=true&cache_ttl=
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
        // $scinames["baby Isaiah"] = 919224;
        $scinames["Camellia sinensis (L.) Kuntze"] = 482447;
        // $scinames["Gadus morhua"] = 46564415; //206692;
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
            $stats = self::compute_totals($arr, $taxon_concept_id);
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
    private function compute_totals($arr, $taxon_concept_id)
    {   /*Array(
            [0] => identifier
            [1] => scientificName
            [2] => richness_score
            [3] => synonyms
            [4] => vernacularNames
            [5] => references
            [6] => taxonConcepts
            [7] => dataObjects
            [8] => licenses
        A- number of non-map media
        B- number of articles
        C- number of different Subjects represented by the articles
        D- number of languages represented by the articles
        E- number of trait records
        F- number of measurementTypes represented by the trait records
        G- number of maps, including GBIF
        H- number of languages represented among the common names
        */
        // print_r($arr); exit;
        $totals['media_counts'] = self::get_media_counts($arr['taxonConcept']['dataObjects'], $taxon_concept_id);
        $ret = self::get_unique_subjects_of_articles($arr['taxonConcept']['dataObjects'], $taxon_concept_id);
        $totals['unique_subjects_of_articles'] = $ret['subjects'];
        $totals['unique_languages_of_articles'] = $ret['languages'];
        print_r($totals); exit;
    }
    private function get_unique_subjects_of_articles($objects, $taxon_concept_id)
    {
        $ret = self::count_all_media_of_type('Text', $taxon_concept_id, 'unique_subjects');
        return $ret;
    }
    private function get_media_counts($objects, $taxon_concept_id)
    {
        foreach($objects as $o) {
            // print_r($o); //exit;
            // [dataType] => http://purl.org/dc/dcmitype/Text
            // [dataType] => http://purl.org/dc/dcmitype/StillImage
            if($o['dataType'] == 'http://purl.org/dc/dcmitype/Text') @$final['Text']++;
            elseif($o['dataType'] == 'http://purl.org/dc/dcmitype/StillImage') @$final['StillImage']++;
            elseif($o['dataType'] == 'http://purl.org/dc/dcmitype/MovingImage') @$final['MovingImage']++;
            elseif($o['dataType'] == 'http://purl.org/dc/dcmitype/Sound') @$final['Sound']++;
            else exit("\nInvestigate no dataType\n");
        }
        if(@$final['StillImage'] == 75) $final['StillImage'] = self::count_all_media_of_type('StillImage', $taxon_concept_id);
        if(@$final['MovingImage'] == 75) $final['MovingImage'] = self::count_all_media_of_type('MovingImage', $taxon_concept_id);
        if(@$final['Sound'] == 75) $final['Sound'] = self::count_all_media_of_type('Sound', $taxon_concept_id);
        if(@$final['Text'] == 75) $final['Text'] = self::count_all_media_of_type('Text', $taxon_concept_id);
        print_r($final); //exit;
        return $final;
    }
    private function count_all_media_of_type($type, $taxon_concept_id, $purpose = false)
    {
        if($type == 'StillImage')       $xxx = 'images';
        elseif($type == 'MovingImage')  $xxx = 'videos';
        elseif($type == 'Sound')        $xxx = 'sounds';
        elseif($type == 'Text')         $xxx = 'texts';
        else exit("\nInvestigate dataType\n");
        $url = $this->api['Pages2'][0].$taxon_concept_id.$this->api['Pages2'][1];
        // 'https://eol.org/api/pages/1.0/';
        // '.json?details=true&images_per_page=75&images_page=';
        $url = str_replace("xxx", $xxx, $url);
        $ctr = 1; $count = 75; $sum = 0;
        while($count == 75) {
            $arr = self::make_an_api_call($url.$ctr);
            $objects = $arr['taxonConcept']['dataObjects'];
            // ---------------------------------------------------------------------------------------------------
            if($purpose == 'unique_subjects') {
                foreach($objects as $o) {
                    // print_r($o); exit;
                    if($o['dataType'] == 'http://purl.org/dc/dcmitype/Text') {
                        $subjects[$o['subject'][0]] = '';
                        $languages[$o['language']] = '';
                    }
                }
            }
            // ---------------------------------------------------------------------------------------------------
            $count = count($objects);
            $sum = $sum + $count;
            echo "\n$count -- $sum\n";
            $ctr++;
        }
        if($purpose == 'unique_subjects') {
            print_r(array_keys($subjects)); //good debug
            print_r(array_keys($languages)); //good debug
            return array('subjects' => count(array_keys($subjects)), 'languages' => count(array_keys($languages)));
        }
        else return $sum;
    }
    private function make_an_api_call($url)
    {
        if($json = Functions::lookup_with_cache($url, $this->download_options)) return json_decode($json, true);
        return false;
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