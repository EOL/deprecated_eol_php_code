<?php
namespace php_active_record;
/* connector: [eol_v3_api.php]
This script uses the different means to access the EOL V3 API.
- First client is DATA-1807: EOL stats resource
- DATA-1826: GBIF Classification
- DATA-1842: EOL image bundles for Katie
---------------------------------------------------------------------------
Cypher info:
https://github.com/EOL/eol_website/blob/master/doc/api-access.md
check if api using cypher is available:
- login to eol.org
- paste in browser: https://eol.org/service/cypher?query=MATCH%20(n:Trait)%20RETURN%20n%20LIMIT%201;
*/
class Eol_v3_API
{
    function __construct($folder = null, $query = null)
    {
        /* add: 'resource_id' => "eol_api_v3" ;if you want to add the cache inside a folder [eol_api] inside [eol_cache] */
        $this->download_options = array(
            'resource_id'        => 'eol_api_v3',  //resource_id here is just a folder name in cache
            'expire_seconds'     => 60*60*24*30, //maybe 1 month to expire
            'download_wait_time' => 750000, 'timeout' => 60*3, 'download_attempts' => 1, 'delay_in_minutes' => 0.5);

        $this->expire_seconds_4cypher_query = 60*60*24; //1 day expires. Used when resource(s) get re-harvested to get latest score based on Trait records.
        $this->expire_seconds_4cypher_query = $this->download_options['expire_seconds'];

        if(Functions::is_production()) $this->download_options['cache_path'] = "/extra/eol_php_cache/";
        else                           $this->download_options['cache_path'] = "/Volumes/Thunderbolt4/eol_cache/";      //used in Functions.php for all general cache
        $this->main_path = $this->download_options['cache_path'].$this->download_options['resource_id']."/";
        if(!is_dir($this->main_path)) mkdir($this->main_path);
        
        $this->api['Pages'] = "http://eol.org/api/pages/1.0.json?batch=false&images_per_page=75&images_page=1&videos_per_page=75&videos_page=1&sounds_per_page=75&sounds_page=1&maps_per_page=75&maps_page=1&texts_per_page=75&texts_page=1&iucn=false&subjects=overview&licenses=all&details=true&common_names=true&synonyms=true&references=true&taxonomy=true&vetted=0&cache_ttl=&language=en&id=";
        $this->api['Pages2'][0] = 'https://eol.org/api/pages/1.0/';
        $this->api['Pages2'][1] = '.json?details=true&xxx_per_page=75&xxx_page=';
        $this->api['Pages3'] = 'https://eol.org/api/pages/1.0/EOL_PAGE_ID.json?details=true'; //for GBIF_classificationAPI.php
        $this->api['Pages4'] = 'https://eol.org/api/pages/1.0/EOL_PAGE_ID.json?details=true&images_per_page=50&images_page=PAGE_NO'; //for DATA-1842: EOL image bundles for Katie
        $this->api['Pages5'] = 'https://eol.org/api/pages/1.0/EOL_PAGE_ID.json?taxonomy=true'; //for https://eol-jira.bibalex.org/browse/DATA-1812?focusedCommentId=64218&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-64218
        $this->api['search_name'] = 'https://eol.org/api/search/1.0.json?q=SCINAME&page=PAGE_NO&exact=true';
        $this->api['Pages6'] = 'https://eol.org/api/data_objects/1.0/dataObjectVersionID.json'; //first client is Katie image bundles. Gets taxon given object ID. Then get ancestry using taxon.
        $this->api['Pages7'] = 'https://eol.org/api/pages/1.0/EOL_PAGE_ID.json';                //first client is Katie image bundles. Gets sciname given eol ID.
        /* https://eol.org/api/search/1.0.json?q=Sphinx&page=1&exact=true 
        https://eol.org/api/pages/1.0/46564415.json?details=true&images_per_page=50&images_page=1
        https://eol.org/api/data_objects/1.0/6735948.json
        */

        $this->api['DataObjects'][0] = "http://eol.org/api/data_objects/1.0/";
        $this->api['DataObjects'][1] = ".json?taxonomy=true&cache_ttl=";
        // e.g. http://eol.org/api/data_objects/1.0/19173106.json?taxonomy=true&cache_ttl=
        //      http://eol.org/api/data_objects/1.0/EOL-media-509-2828226.json?taxonomy=true&cache_ttl=
        // max of 7 simultaneous api calls, still works OK
        
        // for creating archives
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        
        $this->basename = "cypher_".date('YmdHis');
        
        // /* for Katies bundles
        //for Angiosperms
        $this->limit_images_per_family = 10; //DATA-1861
        //for Arthropoda with resource filter
        $this->resource_name = '';
        // */
        $this->debug = array();
    }
    function get_images_per_eol_page_id($param, $options = array(), $destination = false, $items_per_bundle = 1000, $func2) //for Katie's image bundles
    {
        $eol_page_id = $param['eol_page_id'];
        if($val = @$param['limit_images_per_family']) $this->limit_images_per_family = $val;
        if($val = @$param['resource_name']) $this->resource_name = $val;
        
        
        /* For multitaxa taxon groups: Squamata Anura Coleoptera Carnivora */
        if(in_array($eol_page_id, array(1704, 1553, 345, 7662))) $fields = array('identifier', 'dataObjectVersionID', 'dataType', 'dataSubtype', 'vettedStatus', 
                'mediumType', 'dataRating', 'mimeType', 'height', 'width', 'license', 'language', 'rightsHolder', 'source', 'mediaURL', 
                'eolMediaURL', 'eolThumbnailURL');
        /* For Angiosperms, Arthropoda - both need ancestry */
        elseif(in_array($eol_page_id, array(282, 164))) $fields = array('identifier', 'dataObjectVersionID', 'dataType', 'dataSubtype', 'vettedStatus', 
                'mediumType', 'dataRating', 'mimeType', 'height', 'width', 'license', 'language', 'rightsHolder', 'source', 'mediaURL', 
                'eolMediaURL', 'eolThumbnailURL', 'ancestry');
        else exit("\nERROR: eol_page_id is undefined.\n");
        
        if(!$options) $options = $this->download_options;
        $options['expire_seconds'] = false;
        $options['download_wait_time'] = 500000; //half a second        //2000000; //2 seconds orig
        $PAGE_NO = 0; 
        $i = 0; //stats only
        $items_count = 0; $folder_no = 0; $final = array();
        while(true) {
            $PAGE_NO++;
            // if($PAGE_NO >= 2) break; //debug only
            $url = str_replace("EOL_PAGE_ID", $eol_page_id, $this->api['Pages4']);
            $url = str_replace("PAGE_NO", $PAGE_NO, $url);
            if(($PAGE_NO % 100) == 0) echo "\n".number_format($PAGE_NO). " [$url]";
            if($json = Functions::lookup_with_cache($url, $options)) {
                $arr = json_decode($json, true);
                if($objects = @$arr['taxonConcept']['dataObjects']) {
                    if(($PAGE_NO % 100) == 0) echo "\nobjects: ".count($objects)."\n";
                    foreach($objects as $obj) { $i++; //$items_count++; -- transferred below due to new filters
                        unset($obj['dataRatings']);
                        unset($obj['agents']);
                        unset($obj['description']);
                        unset($obj['created']);
                        unset($obj['modified']);
                        unset($obj['license_id']);

                        // print_r($obj); exit("\nstop munax Mar 15\n");
                        /*Array(
                            [identifier] => EOL-media-18-https://www.inaturalist.org/photos/3859832
                            [dataObjectVersionID] => 2775581
                            [dataType] => http://purl.org/dc/dcmitype/StillImage
                            [dataSubtype] => jpg
                            [vettedStatus] => Trusted
                            [mediumType] => image
                            [dataRating] => 2.5
                            [mimeType] => image/jpeg
                            [license] => http://creativecommons.org/licenses/by-sa/4.0/
                            [rightsHolder] => pbedell
                            [source] => https://www.inaturalist.org/photos/3859832
                            [mediaURL] => https://static.inaturalist.org/photos/3859832/original.JPG?1464645840
                            [eolMediaURL] => https://content.eol.org/data/media/39/16/f3/18.https___www_inaturalist_org_photos_3859832.jpg
                            [eolThumbnailURL] => https://content.eol.org/data/media/39/16/f3/18.https___www_inaturalist_org_photos_3859832.98x68.jpg
                        )*/

                        if($this->resource_name == "NMNH Entomology") { //means there is a resource filter
                            if(stripos($obj['mediaURL'], "nmnh.si.edu/services/media.php?env=ento") !== false) { //string is found
                                // print_r($obj); echo "- found $this->resource_name";
                            }
                            else continue;
                        }
                        

                        /* Eli investigates...
                        // if($obj['dataObjectVersionID'] == 1997701) {
                        if($obj['mediaURL'] == 'http://www.discoverlife.org/mp/20p?img=I_MWS21442&res=mx') {
                            print_r($obj);
                            exit("\nFound it!\n");
                        }
                        continue;
                        */
                        if($func2) { //right now Angiosperms (282) and Arthropoda (164) go here...need ancestry.
                            $ancestry = self::get_ancestry_given_object_id($obj['dataObjectVersionID'], $func2);
                            $obj['ancestry'] = $ancestry;
                            /* good debug
                            print_r($obj);
                            if(count($func2->families) >= 5) {
                                print_r($func2->families);
                                print_r($this->count_images_per_family);
                                exit("\nelix here...\n");
                            }
                            */
                            if($eol_page_id == 282) { //only Angiosperms has family limit
                                if(self::is_max_no_of_images_per_family_reached_YN($ancestry, @$func2->families)) continue;
                            }
                        }

                        $items_count++;
                        // /* normal operation
                        $final[] = $obj;
                        if(!isset($fields)) $fields = array_keys($obj);
                        if($items_count == $items_per_bundle) {
                            $folder_no++; //echo "\n$folder_no\n";
                            $items_count = 0;
                            self::write_2file_bundle($final, $param, $folder_no, $destination, $fields);
                            $final = array();
                        }
                        // */
                        
                    } // exit("\ndebug\n");
                }
                else break;
            }
            else break;
        }
        // /* normal operation
        // last batch
        $folder_no++; //echo "\n$folder_no\n";
        self::write_2file_bundle($final, $param, $folder_no, $destination, $fields);
        // */
        if($this->debug) print_r($this->debug);
        /* For Angiosperms */
        if(in_array($eol_page_id, array(282))) print_r($this->count_images_per_family);
    }
    private function is_max_no_of_images_per_family_reached_YN($ancestry, $families)
    {
        $family_in_question = self::get_family_from_ancestry($ancestry, $families);
        @$this->count_images_per_family[$family_in_question]++; //increment count
        if($this->count_images_per_family[$family_in_question] > $this->limit_images_per_family) return true;
        else return false;
    }
    private function get_family_from_ancestry($ancestry, $families)
    {
        $ancestors = explode("|", $ancestry);
        $ancestors = array_map('trim', $ancestors);
        foreach($ancestors as $ancestor) {
            if(isset($families[$ancestor])) return $ancestor;
        }
        // print_r($families); echo "\nancestry: [$ancestry]\n"; //debug only
        // /* for stats only
        if($ancestry) @$this->debug['objects with no family']['with ancestry but no family taxon']++;
        else          @$this->debug['objects with no family']['blank ancestry']++;
        // */
    }
    /* ---------------------------------------------------------- START image bundles ---------------------------------------------------------- */
    function get_ancestry_given_object_id($dataObjectVersionID, $func)
    {
        if($taxon = self::get_taxon_given_object_id($dataObjectVersionID)) { // print_r($taxon); //exit;
            $eol_id = $taxon['taxon_id'];
            if(!$eol_id) return;
            if($ancestry = $func->get_ancestry_of_taxID($eol_id)) { //worked OK // print_r($ancestry);
                array_shift($ancestry); //remove first element, which is the taxon where object belongs to.
                $names = self::convert_ids_to_names($ancestry);
                return $names;
            }
            else debug("\nNo ancestry\n");
            /* not used here. But working OK
            if($children_json = $func->get_children_from_json_cache($eol_id, array(), true)) {
                $children = json_decode($children_json);
                print_r($children); //worked OK
            }
            else echo "\nNo children\n";
            */
        }
    }
    private function convert_ids_to_names($eol_ids)
    {
        $final = array();
        foreach($eol_ids as $eol_id) {
            if(!$eol_id) continue;
            if($name = self::get_name_from_eol_id($eol_id)) $final[$name] = '';
        }
        $final = array_keys($final);
        return implode("|", $final);
    }
    private function get_name_from_eol_id($eol_id)
    {
        $url = str_replace("EOL_PAGE_ID", $eol_id, $this->api['Pages7']); // echo "\n$url\n";
        $options = $this->download_options;
        $options['expire_seconds'] = false; //always false, since an eol ID will only have 1 taxon name in its lifetime.
        $options['download_wait_time'] = 500000; //half a second
        $options['delay_in_minutes'] = 0.125; //7.5 seconds
        if($json = Functions::lookup_with_cache($url, $options)) {
            $arr = json_decode($json, true); // print_r($arr); 
            return functions::canonical_form(@$arr['taxonConcept']['scientificName']);
        }
    }
    function get_taxon_given_object_id($dataObjectVersionID)
    {   $taxon = array();
        $url = str_replace("dataObjectVersionID", $dataObjectVersionID, $this->api['Pages6']);
        // echo "\n$url\n";
        $options = $this->download_options;
        $options['expire_seconds'] = false; //always false, since an object_id will only have 1 same taxon in its lifetime.
        $options['download_wait_time'] = 500000; //half a second
        $options['delay_in_minutes'] = 0.125; //7.5 seconds
        if($json = Functions::lookup_with_cache($url, $options)) {
            $arr = json_decode($json, true); // print_r($arr); 
            $taxon['taxon_id'] = $arr['taxon']['identifier'];
            $taxon['sciname'] = Functions::canonical_form($arr['taxon']['scientificName']);
        }
        return $taxon;
    }
    private function write_2file_bundle($final, $param, $folder_no, $destination, $fields)
    {   echo "\nSaving batch now: ".count($final)."\n";
        // /* multiple file start ----------------------------------------------------
        $orig_destination = $destination;
        $destination = str_replace(".txt", "_breakdown.txt", $orig_destination); //new destination file: images_for_Panthera_leo_breakdown.txt
        $destination2 = str_replace(".txt", "_download.txt", $destination); //new destination2 file: images_for_Panthera_leo_breakdown_download.txt
        $destination = str_replace(".txt", "_".Functions::format_number_with_leading_zeros($folder_no, 6).".txt", $destination);
        $destination2 = str_replace(".txt", "_".Functions::format_number_with_leading_zeros($folder_no, 6).".txt", $destination2);
        if(!($f2 = Functions::file_open($destination2, "w"))) return;
        if(!($f = Functions::file_open($destination, "w"))) return;
        fwrite($f, implode("\t", $fields)."\n");
        foreach($final as $rec) {
            $r = array();
            foreach($fields as $fld) $r[] = @$rec[$fld];
            fwrite($f, implode("\t", $r)."\n");
            fwrite($f2, $rec['eolMediaURL']."\n");
        }
        fclose($f); fclose($f2);
        // ---------------------------------------------------- multiple file end */
    }
    function bundle_images_4download_per_eol_page_id($param, $file) //working but not used for now...
    {
        $items_count = 0; $folder_no = 1;
        if(Functions::is_production()) {
            $this->bundle_folder = '/extra/other_files/bundle_images/';
            $items_per_bundle = 1000;
            $items_per_bundle = 2;
        }
        else {
            $this->bundle_folder = '/Volumes/AKiTiO4/other_files/bundle_images/';
            $items_per_bundle = 2;
        }
        $destination_folder = $this->bundle_folder.$param['eol_page_id']."/";
        if(!is_dir($destination_folder)) mkdir($destination_folder);
        
        $i = 0;
        foreach(new FileIterator($file) as $line_number => $line) {
            $line = explode("\t", $line); $i++; if(($i % 500) == 0) echo "\n".number_format($i);
            if($i == 1) $fields = $line;
            else {
                if(!$line[0]) break;
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k]; $k++;
                }
                // print_r($rec); exit;
                /*Array(
                    [identifier] => EOL-media-542-4888050484
                    [dataObjectVersionID] => 6707831
                    [dataType] => http://purl.org/dc/dcmitype/StillImage
                    [dataSubtype] => jpg
                    [vettedStatus] => Trusted
                    [mediumType] => image
                    [dataRating] => 2.5
                    [mimeType] => image/jpeg
                    [title] => Male Lion - Maasai Mara, Kenya
                    [license] => http://creativecommons.org/licenses/by-nc-sa/2.0/
                    [license_id] => 4
                    [rightsHolder] => David d'O / Schaapmans
                    [source] => https://www.flickr.com/photos/david_o/4888050484/
                    [mediaURL] => https://farm5.staticflickr.com/4093/4888050484_fba7e481cd_o.jpg
                    [eolMediaURL] => https://content.eol.org/data/media/80/19/a6/542.4888050484.jpg
                    [eolThumbnailURL] => https://content.eol.org/data/media/80/19/a6/542.4888050484.98x68.jpg
                )*/
                if($rec['eolMediaURL']) $items_count++;
                else continue;
                
                if($items_count > $items_per_bundle) {
                    $folder_no++;
                    $items_count = 1;
                }
                $target = $destination_folder.$folder_no."/";
                if(!is_dir($target)) mkdir($target);
                $filename = pathinfo($rec['eolMediaURL'], PATHINFO_BASENAME);
                $file_target = $target.$filename;
                if(!file_exists($file_target)) { // "-nc" seems redundant already with this line
                    $cmd = "wget -q -nc ".$rec['eolMediaURL']." -O $file_target"; // "-nc" means will not re-download if file exists already. "-N" means will re-download newer files.
                    echo "\n$cmd\n";
                    $output = shell_exec($cmd); // since using "-q", $output is blank anyway.
                }
                else echo "\nalready exists($file_target)\n";
                if($i >= 11) break; //debug only
            }
        }
    }
    /* ---------------------------------------------------------- END image bundles ------------------------------------------------------------ */
    function search_eol_page_id($eol_page_id, $options = array(), $PagesX = 'Pages3')
    {   if(!$options) {
            $options = $this->download_options;
        }
        $url = str_replace("EOL_PAGE_ID", $eol_page_id, $this->api[$PagesX]);
        if($json = Functions::lookup_with_cache($url, $options)) {
            $arr = json_decode($json, true);
            return $arr;
        }
        else debug("\nnot found [$eol_page_id] in search_eol_page_id()\n");
    }
    
    function search_name($sciname, $options = array(), $PAGE_NO = 1) //this only gets the first 50 or less. No next page yet, not needed right now.
    {
        if(in_array($sciname, array('Viruses', 'var.', 'Phage'))) return false;
        if(!$options) $options = $this->download_options;
        $url = str_replace("SCINAME", $sciname, $this->api['search_name']);
        $url = str_replace("PAGE_NO", $PAGE_NO, $url);
        // exit("\n[$url]\n");
        if($json = Functions::lookup_with_cache($url, $options)) {
            $arr = json_decode($json, true);
            return $arr;
        }
        else echo "\nnot found [$sciname] in search_name()\n";
    }
    function generate_stats($params) //$params came from run.php
    {
        /*$params e.g. Array(
            [range] => Array(
                    [0] => 1271125
                    [1] => 1906687
                )
            [ctr] => 3
        )
        */
        
        // /* will use to check if EOL id has GBIF map
        require_library('connectors/GBIFoccurrenceAPI_DwCA');
        $this->gbif_func = new GBIFoccurrenceAPI_DwCA();
        // */
        
        /* normal operation OLD
        if(Functions::is_production()) $path = "/extra/eol_php_code_public_tmp/google_maps/taxon_concept_names.tab";
        else                           $path = "/Volumes/Thunderbolt4/z backup of AKiTiO4/z backup/eol_php_code_public_tmp/google_maps old/taxon_concept_names.tab";
        self::process_all_eol_taxa($path); return;                    //make use of tab-delimited text file from JRice
        */
        
        // /* normal operation NEW
        if(Functions::is_production()) $path = "/extra/other_files/DWH/from_OpenData/EOL_dynamic_hierarchyV1Revised/taxa.txt";
        else                           $path = "/Volumes/AKiTiO4/other_files/from_OpenData/EOL_dynamic_hierarchyV1Revised/taxa.txt";
        
        $filename = CONTENT_RESOURCE_LOCAL_PATH . 'species_richness_score.txt';
        $this->WRITE = Functions::file_open($filename, "w");
        fwrite($this->WRITE, 'A- number of non-map media'."\n");
        fwrite($this->WRITE, 'B- number of articles'."\n");
        fwrite($this->WRITE, 'C- number of different Subjects represented by the articles'."\n");
        fwrite($this->WRITE, 'D- number of languages represented by the articles'."\n");
        fwrite($this->WRITE, 'E- number of trait records'."\n");
        fwrite($this->WRITE, 'F- number of measurementTypes represented by the trait records'."\n");
        fwrite($this->WRITE, 'G- number of maps, including GBIF'."\n");
        fwrite($this->WRITE, 'H- number of languages represented among the common names'."\n\n");
        $arr = array('EOLid', 'scientificName', 'Richness Score', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H');
        fwrite($this->WRITE, implode("\t", $arr)."\n");
        self::process_all_eol_taxa_using_DH($path, 'main', $params['range']); //make use of Katja's EOL DH with EOL Page IDs -- good choice
        fclose($this->WRITE);
        
        /* ctr becomes zero 0 when finalizing the report */
        if($params['ctr']) unlink(CONTENT_RESOURCE_LOCAL_PATH . "part_EOL_stats_".$params['ctr'].".txt"); //this file was generated in run.php
        else
        {
            /* working OK but replaced with backup using gzip instead
            $destination = CONTENT_RESOURCE_LOCAL_PATH . 'species_richness_score_'.str_replace(' ', '_', date('Y-m-d', time())).'.txt'; //h:i:s a
            if(!copy($filename, $destination)) echo "\nFailed to copy $filename...\n";
                                               echo "\nSaved: [$filename]\nTo: [$destination]\n";

            $destination = CONTENT_RESOURCE_LOCAL_PATH . 'species_richness_score_'.str_replace(' ', '_', date('Y-m', time())).'.txt'; //h:i:s a
            if(!copy($filename, $destination)) echo "\nFailed to copy $filename...\n";
                                               echo "\nSaved: [$filename]\nTo: [$destination]\n";
            */
                                               
            /* compressed file is what is reflected in OpenData: https://opendata.eol.org/dataset/eol-stats-for-dynamic-hierarchy-species-level-pages/resource/199b78b2-0f16-4764-9666-8c5b0af26d53
            https://editors.eol.org/eol_php_code/applications/content_server/resources/species_richness_score.txt.gz */
            echo "\nCompressing...\n";
            
            $gzip_target = $filename.".gz";
            $output = shell_exec("gzip -cv ".$filename." > ".$gzip_target);
            echo "\noutput\nCompressed OK [".$gzip_target."]\n";

            $gzip_target = CONTENT_RESOURCE_LOCAL_PATH . 'species_richness_score_'.str_replace(' ', '_', date('Y-m-d', time())).'.txt.gz';
            $output = shell_exec("gzip -cv ".$filename." > ".$gzip_target);
            echo "\noutput\nCompressed OK [".$gzip_target."]\n";

            $gzip_target = CONTENT_RESOURCE_LOCAL_PATH . 'species_richness_score_'.str_replace(' ', '_', date('Y-m', time())).'.txt.gz';
            $output = shell_exec("gzip -cv ".$filename." > ".$gzip_target);
            echo "\noutput\nCompressed OK [".$gzip_target."]\n";
            
            if(unlink($filename)) echo "\nFile deleted: [$filename]\n"; //species_richness_score.txt
        }
        return;
    }
    function process_all_eol_taxa_using_DH($path, $purpose = 'main', $range = array()) //rows = 1,906,685 -> rank 'species' and with EOLid
    {
        $i = 0; $found = 0;
        foreach(new FileIterator($path) as $line => $row) {
            $i++;
            // if(($i % 5000) == 0) echo "\n".number_format($i); //debug only
            if($i == 1) $fields = explode("\t", $row);
            else {
                $rec = explode("\t", $row);
                $k = -1; $rek = array();
                foreach($fields as $field) {
                    $k++;
                    $rek[$field] = $rec[$k];
                }
                if($rek['taxonRank'] == 'species' && $rek['EOLid']) {
                    // $debug[$rek['EOLid']] = '';
                    // print_r($rek); exit;
                    $found++;
                    if($purpose == 'count only') continue;
                    
                    //==================
                    /* this was the legacy way of breakdown caching. No longer needed.
                    $m = 317781; //1,906,685 diveded by 6
                    $cont = false;
                    // if($found >=  1    && $found < $m)    $cont = true;
                    // if($found >=  $m   && $found < $m*2)  $cont = true;
                    // if($found >=  $m*2 && $found < $m*3)  $cont = true;
                    // if($found >=  $m*3 && $found < $m*4)  $cont = true;
                    // if($found >=  $m*4 && $found < $m*5)  $cont = true;
                    if($found >=  $m*5 && $found < $m*6)  $cont = true;
                    if(!$cont) continue;
                    */
                    //==================
                    
                    if($range) {
                        $cont = false;
                        if($found >= $range[0] && $found < $range[1]) $cont = true;
                        if(!$cont) continue;
                    }
                    
                    $taxon_concept_id = $rek['EOLid'];
                    // $taxon_concept_id = 46564415; //debug only - force assign
                    self::api_using_tc_id($taxon_concept_id, $rek['scientificName']);
                    if(($found % 1000) == 0) echo "\n".number_format($found).". [".$rek['scientificName']."][tc_id = $taxon_concept_id]";
                    // break; //debug only
                    // if($found >= 1000) break; //debug only - run first 1000 species for review
                }
            }
            // if($i >= 5) break; //debug only
        }
        // exit("\n".count($debug)."\n");
        if($purpose == 'count only') return $found;
    }
    private function api_using_tc_id($taxon_concept_id, $sciname)
    {
        if($json = Functions::lookup_with_cache($this->api['Pages'].$taxon_concept_id, $this->download_options)) {
            $arr = json_decode($json, true);
            $stats = self::compute_totals($arr, $taxon_concept_id);
            if($GLOBALS['ENV_DEBUG']) print_r($stats);
            $ret = self::compute_richness_score($stats);
            $ret['EOLid'] = $taxon_concept_id;
            $ret['scientificName'] = $sciname;
            self::write_to_txt_file($ret);
            return;

            /* Not needed for current stats requirements: DATA-1807 - as of Jul 3, 2019
            $objects = @$arr['dataObjects'];
            // echo "\nobjects count = " . count($objects)."\n";
            foreach($objects as $o) {
                echo "\n" . $o['dataObjectVersionID'];
                if($o['dataType'] == "http://purl.org/dc/dcmitype/Text" && strlen($o['description']) >= 199) //cache if desc is long since in tsv descs are substring of 200 chars only
                {
                    $objects = self::get_objects($o['dataObjectVersionID']);
                    foreach($objects['dataObjects'] as $o) echo " - " . @$o['mimeType'];
                    // print_r($objects);
                }
            }
            */
        }
    }
    private function compute_richness_score($s)
    {   /*
        A- number of non-map media
        B- number of articles
        C- number of different Subjects represented by the articles
        D- number of languages represented by the articles
        E- number of trait records
        F- number of measurementTypes represented by the trait records
        G- number of maps, including GBIF
        H- number of languages represented among the common names
        Array(
            [media_counts] => Array(
                    [Text] => 82
                    [StillImage] => 76
                    [MovingImage] => 4
                    [Map] => 1
                )
            [unique_subjects_of_articles] => 18
            [unique_languages_of_articles] => 21
            [unique_languages_of_vernaculars] => 74
            [traits] => Array(
                    [total traits] => 3804
                    [total mtypes] => 33
                )
            [GBIF_map] => 
        )
        */
        $A = $s['media_counts']['StillImage'] - $s['media_counts']['Map'];
        $B = $s['media_counts']['Text'];
        $C = $s['unique_subjects_of_articles'];
        $D = $s['unique_languages_of_articles'];
        $E = $s['traits']['total traits'];
        $F = $s['traits']['total mtypes'];
        $G = $s['media_counts']['Map'] + $s['GBIF_map'];
        $H = $s['unique_languages_of_vernaculars'];
        // R=(A/20 with a max of 1) + (C/8 with a max of 1) + (D/10 with a max of 1) + (G/2 with a max of 1) + (H/10 with a max of 1)+5*(F/12 with a max of 1)
        // R=(A/20 with a max of 1) + (C/8 with a max of 1) + (D/10 with a max of 1) + (G/2 with a max of 1) + (H/10 with a max of 1)+3*(F/12 with a max of 1) +2 IF the page has at least one of each: map, non-map media, article and data record
        if($A >= 20) $nA = 1;
        else         $nA = $A/20;
        if($C >= 8) $nC = 1;
        else         $nC = $C/8;
        if($D >= 10) $nD = 1;
        else         $nD = $D/10;
        if($G >= 2) $nG = 1;
        else         $nG = $G/2;
        if($H >= 10) $nH = 1;
        else         $nH = $H/10;
        if($F >= 12) $nF = 1;
        else         $nF = $F/12;
        $R = ($nA)+($nC)+($nD)+($nG)+($nH)+(3*($nF));
        if(self::page_has_at_least_one_of_each($G, $A, $B, $E)) $R = $R + 2;
        $R = number_format($R, 2);
        return array('A' => $A, 'B' => $B, 'C' => $C, 'D' => $D, 'E' => $E, 'F' => $F, 'G' => $G, 'H' => $H, 'R' => $R);
    }
    private function page_has_at_least_one_of_each($G, $A, $B, $E) // +2 IF the page has at least one of each: map, non-map media, article and data record
    {
        if($G >= 1) {}      //map
        else return false;
        if($A >= 1) {}      //non-map media
        else return false;
        if($B >= 1) {}      //article
        else return false;
        if($E >= 1) {}      //data record
        else return false;
        return true;
    }
    private function write_to_txt_file($s)
    {
        if($GLOBALS['ENV_DEBUG']) print_r($s);
        $arr = array($s['EOLid'], $s['scientificName'], $s['R'], $s['A'], $s['B'], $s['C'], $s['D'], $s['E'], $s['F'], $s['G'], $s['H']);
        fwrite($this->WRITE, implode("\t", $arr)."\n");
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
        B*- number of articles
        C*- number of different Subjects represented by the articles
        D*- number of languages represented by the articles
        E- number of trait records
        F- number of measurementTypes represented by the trait records
        G- number of maps, including GBIF
        H*- number of languages represented among the common names
        R=(A/20 with a max of 1)(C/8 with a max of 1)(D/10 with a max of 1)(G/2 with a max of 1)(H/10 with a max of 1)+5*(F/12 with a max of 1)
        */
        // print_r($arr); exit;
        $totals = Array( //initialize
            'media_counts' => array(),
            'unique_subjects_of_articles' => 0,
            'unique_languages_of_articles' => 0,
            'unique_languages_of_vernaculars' => 0,
            'traits' => array(),
            'GBIF_map' => 0);
        $totals['media_counts'] = Array( //initialize
            'Text' => 0,
            'StillImage' => 0,
            'MovingImage' => 0,
            'Map' => 0);
        if($objects = @$arr['taxonConcept']['dataObjects']) {
            $totals['media_counts'] = self::get_media_counts($objects, $taxon_concept_id);
            $ret = self::get_unique_subjects_and_languages_from_articles($objects, $taxon_concept_id);
            $totals['unique_subjects_of_articles'] = $ret['subjects'];
            $totals['unique_languages_of_articles'] = $ret['languages'];
        }
        $totals['unique_languages_of_vernaculars'] = self::get_unique_languages_of_vernaculars($arr['taxonConcept']['vernacularNames']);
        $totals['traits'] = self::get_trait_totals($taxon_concept_id);
        $totals['GBIF_map'] = self::with_gbif_map_YN($taxon_concept_id);
        return $totals;
    }
    private function with_gbif_map_YN($tc_id)
    {
        if($this->gbif_func->map_data_file_already_been_generated($tc_id)) return 1;
        return 0;
    }
    private function get_trait_totals($tc_id)
    {
        $arr = self::retrieve_trait_totals($tc_id);
        return $arr;
    }
    private function retrieve_trait_totals($tc_id)
    {
        $filename = self::generate_path_filename($tc_id);
        if(file_exists($filename)) {
            if($GLOBALS['ENV_DEBUG']) echo "\nCypher cache already exists. [$filename]\n";
            
            // $this->download_options['expire_seconds'] = 60; //debug only - force assign --- test success
            
            $file_age_in_seconds = time() - filemtime($filename);
            if($file_age_in_seconds < $this->expire_seconds_4cypher_query) return self::retrieve_json($filename); //not yet expired
            if($this->expire_seconds_4cypher_query === false)              return self::retrieve_json($filename); //doesn't expire
            
            if($GLOBALS['ENV_DEBUG']) echo "\nCache expired. Will run cypher now...\n";
            self::run_cypher_query($tc_id, $filename);
            return self::retrieve_json($filename);
        }
        else {
            if($GLOBALS['ENV_DEBUG']) echo "\nRun cypher query...\n";
            self::run_cypher_query($tc_id, $filename);
            return self::retrieve_json($filename);
        }
    }
    private function retrieve_json($filename)
    {
        $json = file_get_contents($filename);
        return json_decode($json, true);
    }
    private function run_cypher_query($tc_id, $filename)
    {
        $saved = array();
        /* total traits */
        $qry = "MATCH (t:Trait)<-[:trait]-(p:Page), (t)-[:supplier]->(r:Resource), (t)-[:predicate]->(pred:Term) WHERE p.page_id = ".$tc_id." OPTIONAL MATCH (t)-[:units_term]->(units:Term) RETURN COUNT(pred.name) LIMIT 5";
        $saved['total traits'] = self::run_query($qry);
        /* total measurementTypes */
        $qry = "MATCH (t:Trait)<-[:trait]-(p:Page), (t)-[:supplier]->(r:Resource), (t)-[:predicate]->(pred:Term) WHERE p.page_id = ".$tc_id." OPTIONAL MATCH (t)-[:units_term]->(units:Term) RETURN COUNT(DISTINCT pred.name) LIMIT 5";
        $saved['total mtypes'] = self::run_query($qry);
        // print_r($saved); exit;
        $WRITE = Functions::file_open($filename, "w");
        fwrite($WRITE, json_encode($saved)); fclose($WRITE);
        if($GLOBALS['ENV_DEBUG']) echo "\nSaved OK [$filename]\n";
    }
    private function run_query($qry)
    {
        $in_file = DOC_ROOT."/temp/".$this->basename.".in";
        $WRITE = Functions::file_open($in_file, "w");
        fwrite($WRITE, $qry); fclose($WRITE);
        $destination = DOC_ROOT."temp/".$this->basename.".out.json";
        /* worked in eol-archive but may need to add: /bin/cat instead of just 'cat'
        $cmd = 'wget -O '.$destination.' --header "Authorization: JWT `cat '.DOC_ROOT.'temp/api.token`" https://eol.org/service/cypher?query="`cat '.$in_file.'`"';
        */
        $cmd = 'wget -O '.$destination.' --header "Authorization: JWT `/bin/cat '.DOC_ROOT.'temp/api.token`" https://eol.org/service/cypher?query="`/bin/cat '.$in_file.'`"';
        
        
        
        $cmd .= ' 2>/dev/null'; //this will throw away the output
        $output = shell_exec($cmd); //$output here is blank since we ended command with '2>/dev/null' --> https://askubuntu.com/questions/350208/what-does-2-dev-null-mean
        $json = file_get_contents($destination);
        $obj = json_decode($json);
        return @$obj->data[0][0];
    }
    private function generate_path_filename($tc_id)
    {
        $main_path = $this->main_path;
        $md5 = md5($tc_id);
        $cache1 = substr($md5, 0, 2);
        $cache2 = substr($md5, 2, 2);
        if(!file_exists($main_path . $cache1))           mkdir($main_path . $cache1);
        if(!file_exists($main_path . "$cache1/$cache2")) mkdir($main_path . "$cache1/$cache2");
        $filename = $main_path . "$cache1/$cache2/$tc_id.json";
        return $filename;
    }
    private function get_unique_languages_of_vernaculars($comnames)
    {
        $final = array();
        foreach($comnames as $comname) $final[$comname['language']] = '';
        $final = array_keys($final);
        if($GLOBALS['ENV_DEBUG']) print_r($final); //exit; //good debug
        return count($final);
    }
    private function get_unique_subjects_and_languages_from_articles($objects, $taxon_concept_id)
    {
        $ret = self::count_all_media_of_type('Text', $taxon_concept_id, 'unique_subj_and_lang');
        return $ret;
    }
    private function get_media_counts($objects, $taxon_concept_id)
    {
        $final = Array( //initialize
                'Text' => 0,
                'StillImage' => 0,
                'MovingImage' => 0,
                'Map' => 0);
        foreach($objects as $o) {
            // print_r($o); //exit;
            // [dataType] => http://purl.org/dc/dcmitype/Text
            // [dataType] => http://purl.org/dc/dcmitype/StillImage
            if($o['dataType'] == 'http://purl.org/dc/dcmitype/Text') @$final['Text']++;
            elseif($o['dataType'] == 'http://purl.org/dc/dcmitype/StillImage') {
                @$final['StillImage']++;
                if($o['mediumType'] == 'map') @$final['Map']++;
                elseif($o['mediumType'] == 'map_image') @$final['Map']++;
            }
            elseif($o['dataType'] == 'http://purl.org/dc/dcmitype/MovingImage') @$final['MovingImage']++;
            elseif($o['dataType'] == 'http://purl.org/dc/dcmitype/Sound') @$final['Sound']++;
            else exit("\nInvestigate no dataType\n");
        }
        if(@$final['StillImage'] == 75) $final['StillImage'] = self::count_all_media_of_type('StillImage', $taxon_concept_id);
        if(@$final['MovingImage'] == 75) $final['MovingImage'] = self::count_all_media_of_type('MovingImage', $taxon_concept_id);
        if(@$final['Sound'] == 75) $final['Sound'] = self::count_all_media_of_type('Sound', $taxon_concept_id);
        if(@$final['Text'] == 75) $final['Text'] = self::count_all_media_of_type('Text', $taxon_concept_id);
        if($GLOBALS['ENV_DEBUG']) print_r($final); //exit;
        return $final;
    }
    private function count_all_media_of_type($type, $taxon_concept_id, $purpose = false)
    {
        if($purpose == 'unique_subj_and_lang') {
            $subjects = array();
            $languages = array();
        }
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
            if(!$purpose && $sum >= 300) break; //means just counting no. of media objects; then we can put a limit
            $arr = self::make_an_api_call($url.$ctr);
            if($objects = @$arr['taxonConcept']['dataObjects']) {
                // ---------------------------------------------------------------------------------------------------
                if($purpose == 'unique_subj_and_lang') {
                    foreach($objects as $o) {
                        // print_r($o); exit;
                        if($o['dataType'] == 'http://purl.org/dc/dcmitype/Text') {
                            $subjects[@$o['subject'][0]] = '';
                            $languages[@$o['language']] = '';
                        }
                    }
                }
                else { //means just counting no. of media objects; then we can put a limit
                }
                // ---------------------------------------------------------------------------------------------------
                $count = count($objects);
                $sum = $sum + $count;
                if($GLOBALS['ENV_DEBUG']) echo "\n$count -- $sum [$purpose]\n";
            }
            else $count = 0;
            $ctr++;
        }
        if($purpose == 'unique_subj_and_lang') {
            if($GLOBALS['ENV_DEBUG']) {
                print_r(array_keys($subjects)); //good debug
                print_r(array_keys($languages)); //good debug
            }
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
    /* not used anymore
    private function process_all_eol_taxa($path, $listOnly = false)
    {
        if($listOnly) $list = array();
        $i = 0;
        $found = 0; //for debug only
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
                $m = 75000;
                $cont = false;
                // if($i >=  1    && $i < $m)    $cont = true; done
                // if($i >=  $m   && $i < $m*2)  $cont = true; done
                if(!$cont) continue;
                //==================
                $found++;
                // if(($i % 100) == 0) echo "\n".number_format($i).". [$sciname][tc_id = $taxon_concept_id]";
                if(($found % 100) == 0) echo "\n".number_format($i).". [$sciname][tc_id = $taxon_concept_id]";
                
                // $taxon_concept_id = 46559686; //force - debug only
                self::api_using_tc_id($taxon_concept_id);
                // if($found >= 10) break; //debug
            }
            // else echo "\n[$sciname] will pass higher-level taxa at this time...\n";
        }//end loop
        if($listOnly) return $list;
    }
    */
}
?>