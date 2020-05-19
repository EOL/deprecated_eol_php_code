<?php
namespace php_active_record;
// connector: [gbif_classification_v2.php]
class GBIF_classificationAPI_v2
{
    function __construct($folder, $archive_builder = false)
    {
        $this->resource_id = $folder;
        if($archive_builder) { //coming from DwCA_Utility.php. For creating gbif_classification.tar.gz. Created second.
            $this->archive_builder = $archive_builder;
        }
        else { //for creating gbif_classification_pre.tar.gz. Created first.
            $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
            $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        }

        $this->download_options = array(
            'resource_id'        => 'eol_api_v3',  //resource_id here is just a folder name in cache
            'expire_seconds'     => 60*60*24*30*3, //expires quarterly
            'download_wait_time' => 500000, 'timeout' => 60*3, 'download_attempts' => 1, 'delay_in_minutes' => 1/60, 'cache' => 1); //delay_in_minutes of 1/6 is 10 secs, 1/60 is 1 sec.
        // /* i've set to expire false coz DH09 is still relative. There is also DH11
        $this->download_options['expire_seconds'] = false;
        // */
        if(Functions::is_production()) {
            $this->service["backbone_dwca"] = "http://rs.gbif.org/datasets/backbone/backbone-current.zip";
            $this->service["gbif_classification_pre"] = "https://editors.eol.org/eol_php_code/applications/content_server/resources/gbif_classification_pre.tar.gz";
            $this->service['DH0.9'] = 'https://opendata.eol.org/dataset/0a023d9a-f8c3-4c80-a8d1-1702475cda18/resource/1b375a39-4739-45ba-87cd-328bdd50ec34/download/eoldynamichierarchywithlandmarks.zip';
            /* private in opendata.eol.org
            $this->service['DH0.9 EOL pageID mappings'] = 'https://opendata.eol.org/dataset/b6bb0c9e-681f-4656-b6de-39aa3a82f2de/resource/118fbbd8-71df-4ef9-90f5-5b4a663c7602/download/eolpageids.csv.gz';
            */
            $this->service['DH0.9 EOL pageID mappings'] = 'https://editors.eol.org/other_files/GBIF_Classification/eolpageids.csv.gz';
        }
        else {
            $this->service["backbone_dwca"] = "http://localhost/cp/GBIF_Backbone_Archive/backbone-current.zip";
            $this->service["gbif_classification_pre"] = "http://localhost/eol_php_code/applications/content_server/resources/gbif_classification_pre.tar.gz";
            $this->service['DH0.9'] = 'http://localhost/cp/DATA-1826 GBIF class/eoldynamichierarchywithlandmarks.zip';
            $this->service['DH0.9 EOL pageID mappings'] = 'http://localhost/cp/DATA-1826 GBIF class/eolpageids.csv.gz';
        }
        $this->log_file = CONTENT_RESOURCE_LOCAL_PATH.'gbif_names_not_found_in_eol.txt';

        $this->debug = array();
        /*
        2,724,940 taxa.txt -> eoldynamichierarchywithlandmarks
        2,724,668 eolpageids.csv
        */
        require_library('connectors/Eol_v3_API');
        $this->func_eol_v3 = new Eol_v3_API();
    }
    //=================================================START fix remaining conflicts between the API & DH09 mappings
    function fix_remaining_conflicts($info)
    {
        self::build_info_PreferEOL_id_from_API_match(); //from Jira attachment: PreferEOL_id_from_API_match.txt
        
        // /* get info lists
        self::build_info('DH0.9', 60*60*24*30); //builds -> $this->DH09[gbif_id] = DH_id; //gbif_id -> DH_id        2nd param false means expire_seconds = false
        self::process_eolpageids_csv();         //builds -> $this->DH_map[DH_id] = EOLid; //DH_id -> EOLid
        echo "\n resource file DH09: ".count($this->DH09)."\n";
        echo "\n resource file DH_map: ".count($this->DH_map)."\n";
        // */
        $tables = $info['harvester']->tables;
        self::process_fix_taxon($tables['http://rs.tdwg.org/dwc/terms/taxon'][0]);
        print_r($this->debug);
    }
    private function build_info_PreferEOL_id_from_API_match()
    {
        if(Functions::is_production())  $contents = file_get_contents("/extra/other_files/GBIF_Classification/PreferEOL_id_from_API_match.txt");
        else                            $contents = file_get_contents("/Volumes/AKiTiO4/web/cp/DATA-1826 GBIF class/Jira/PreferEOL_id_from_API_match.txt");

        $arr = explode("\n", $contents);
        array_shift($arr); //remove first row which is the header
        $arr = array_filter($arr); //remove null arrays
        $arr = array_values($arr); //reindex key
        foreach($arr as $id) $this->prefer_EOLid_fromAPI_for_these_GBIFids[$id] = '';
    }
    function process_fix_taxon($meta)
    {   //print_r($meta);
        echo "\nprocess_fix_taxon...\n$meta->file_uri\n"; $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            // $row = Functions::conv_to_utf8($row); //possibly to fix special chars. but from copied template
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            }
            // print_r($rec); exit("\ndebug...\n");
            /*Array(
                [http://rs.tdwg.org/dwc/terms/taxonID] => 2588702
                [http://rs.tdwg.org/dwc/terms/acceptedNameUsageID] => 
                [http://rs.tdwg.org/dwc/terms/parentNameUsageID] => 95
                [http://rs.tdwg.org/dwc/terms/originalNameUsageID] => 
                [http://rs.tdwg.org/dwc/terms/scientificName] => Lichenobactridium Diederich & Etayo
                [http://rs.tdwg.org/dwc/terms/nameAccordingTo] => 
                [http://rs.tdwg.org/dwc/terms/namePublishedIn] => Flechten Follmann (Cologne), Contributions to Lichenology in Honour of Gerhard Follmann 212 (1995)
                [http://rs.tdwg.org/dwc/terms/kingdom] => Fungi
                [http://rs.tdwg.org/dwc/terms/phylum] => Ascomycota
                [http://rs.tdwg.org/dwc/terms/class] => 
                [http://rs.tdwg.org/dwc/terms/order] => 
                [http://rs.tdwg.org/dwc/terms/family] => 
                [http://rs.tdwg.org/dwc/terms/genus] => Lichenobactridium
                [http://rs.tdwg.org/dwc/terms/specificEpithet] => 
                [http://rs.tdwg.org/dwc/terms/infraspecificEpithet] => 
                [http://rs.tdwg.org/dwc/terms/taxonRank] => genus
                [http://rs.tdwg.org/dwc/terms/scientificNameAuthorship] => Diederich & Etayo
                [http://rs.tdwg.org/dwc/terms/taxonomicStatus] => accepted
                [http://rs.tdwg.org/dwc/terms/nomenclaturalStatus] => 
                [http://rs.tdwg.org/dwc/terms/taxonRemarks] => 
                [http://rs.tdwg.org/dwc/terms/datasetID] => 7ddf754f-d193-4cc9-b351-99906754a03b
                [http://rs.gbif.org/terms/1.0/canonicalName] => Lichenobactridium
                [http://eol.org/schema/EOLid] => 37570
            )*/
            //========================================================================================================================
            $gbif_id = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
            $DH09_EOL_id = self::get_EOL_id_given_GBIF_id($gbif_id);
            
            // /* Katja's version: https://eol-jira.bibalex.org/browse/DATA-1826?focusedCommentId=64135&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-64135
            // For taxa where Match_YN=No and DH09_taxonID is not null (13,846 taxa) choose the EOL_id_from_DH09 over the EOL_id_from_API_match 
            // except for taxa whose GBIF_id is listed in the attached PreferEOL_id_from_API_match.txt file.
            if($DH09_EOL_id) { @$this->debug['stats']['13K']['total']++;
                if(isset($this->prefer_EOLid_fromAPI_for_these_GBIFids[$gbif_id])) {
                    $rec['http://eol.org/schema/EOLid'] = '';
                    @$this->debug['stats']['from preferred set2blank']++;    //stats only
                }
                else {
                    $rec['http://eol.org/schema/EOLid'] = $DH09_EOL_id;
                    @$this->debug['stats']['used DH09']++;   //stats only
                }
            }
            // */
            //========================================================================================================================
            $uris = array_keys($rec);
            $o = new \eol_schema\Taxon();
            foreach($uris as $uri) {
                $field = pathinfo($uri, PATHINFO_BASENAME);
                $o->$field = $rec[$uri];
            }
            $this->archive_builder->write_object_to_file($o);
            // if($i >= 10) break; //debug only
        }
    }
    // /* New - https://eol-jira.bibalex.org/browse/DATA-1826?focusedCommentId=64864&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-64864
    function create_dwca_without_ancestry($info)
    {
        $tables = $info['harvester']->tables;
        self::create_no_ancestry($tables['http://rs.tdwg.org/dwc/terms/taxon'][0]);
    }
    // */
    private function create_no_ancestry($meta)
    {   //print_r($meta);
        echo "\ncreate_no_ancestry...\n$meta->file_uri\n"; $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            // $row = Functions::conv_to_utf8($row); //possibly to fix special chars. but from copied template
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            }
            // print_r($rec); exit("\ndebug...\n");
            /*Array(
                [http://rs.tdwg.org/dwc/terms/taxonID] => 2588702
                [http://rs.tdwg.org/dwc/terms/acceptedNameUsageID] => 
                [http://rs.tdwg.org/dwc/terms/parentNameUsageID] => 95
                [http://rs.tdwg.org/dwc/terms/originalNameUsageID] => 
                [http://rs.tdwg.org/dwc/terms/scientificName] => Lichenobactridium Diederich & Etayo
                [http://rs.tdwg.org/dwc/terms/nameAccordingTo] => 
                [http://rs.tdwg.org/dwc/terms/namePublishedIn] => Flechten Follmann (Cologne), Contributions to Lichenology in Honour of Gerhard Follmann 212 (1995)

                [http://rs.tdwg.org/dwc/terms/kingdom] => Fungi
                [http://rs.tdwg.org/dwc/terms/phylum] => Ascomycota
                [http://rs.tdwg.org/dwc/terms/class] => 
                [http://rs.tdwg.org/dwc/terms/order] => 
                [http://rs.tdwg.org/dwc/terms/family] => 
                [http://rs.tdwg.org/dwc/terms/genus] => Lichenobactridium
                [http://rs.tdwg.org/dwc/terms/specificEpithet] => 
                [http://rs.tdwg.org/dwc/terms/infraspecificEpithet] => 

                [http://rs.tdwg.org/dwc/terms/taxonRank] => genus
                [http://rs.tdwg.org/dwc/terms/scientificNameAuthorship] => Diederich & Etayo
                [http://rs.tdwg.org/dwc/terms/taxonomicStatus] => accepted
                [http://rs.tdwg.org/dwc/terms/nomenclaturalStatus] => 
                [http://rs.tdwg.org/dwc/terms/taxonRemarks] => 
                [http://rs.tdwg.org/dwc/terms/datasetID] => 7ddf754f-d193-4cc9-b351-99906754a03b
                [http://rs.gbif.org/terms/1.0/canonicalName] => Lichenobactridium
                [http://eol.org/schema/EOLid] => 37570
            )*/
            $uris = array_keys($rec);
            $will_remove_uris = array('http://rs.tdwg.org/dwc/terms/kingdom', 'http://rs.tdwg.org/dwc/terms/phylum', 'http://rs.tdwg.org/dwc/terms/class', 
                                      'http://rs.tdwg.org/dwc/terms/order', 'http://rs.tdwg.org/dwc/terms/family', 'http://rs.tdwg.org/dwc/terms/genus', 
                                      'http://rs.tdwg.org/dwc/terms/specificEpithet', 'http://rs.tdwg.org/dwc/terms/infraspecificEpithet');
            $uris = array_diff($uris, $will_remove_uris);
            $o = new \eol_schema\Taxon();
            foreach($uris as $uri) {
                $field = pathinfo($uri, PATHINFO_BASENAME);
                $o->$field = $rec[$uri];
            }
            $this->archive_builder->write_object_to_file($o);
            // if($i >= 10) break; //debug only
        }
    }
    private function get_EOL_id_given_GBIF_id($gbif_id)
    {
        //builds -> $this->DH09[gbif_id] = DH_id; //gbif_id -> DH_id
        //builds -> $this->DH_map[DH_id] = EOLid; //DH_id -> EOLid
        if($DH_id = @$this->DH09[$gbif_id]) {
            if($EOL_id = @$this->DH_map[$DH_id]) return $EOL_id;
        }
    }
    //=================================================END fix remaining conflicts between the API & DH09 mappings

    private function build_info($dwca, $expire_seconds)
    {
        $paths = self::access_dwca($dwca, $expire_seconds); //false here 2nd param is expire_seconds
        $archive_path = $paths['archive_path'];
        $temp_dir = $paths['temp_dir'];
        $harvester = new ContentArchiveReader(NULL, $archive_path);
        $tables = $harvester->tables;
        // print_r($tables); exit;
        self::process_taxon_4report($tables['http://rs.tdwg.org/dwc/terms/taxon'][0], $dwca);
        
        // /* un-comment in real operation
        recursive_rmdir($temp_dir);
        echo ("\n temporary directory removed: " . $temp_dir);
        // */
    }
    private function process_taxon_4report($meta, $dwca)
    {   //print_r($meta);
        echo "\nprocess_taxon_4report...\n"; $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 300000) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            // $row = Functions::conv_to_utf8($row); //possibly to fix special chars. but from copied template
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            }
            // print_r($rec); exit;
            /* gbif_classification
            Array( some fields were deleted coz its too many to list here:
                [http://rs.tdwg.org/dwc/terms/taxonID] => 2588702
                [http://rs.tdwg.org/dwc/terms/acceptedNameUsageID] => 
                [http://rs.tdwg.org/dwc/terms/parentNameUsageID] => 95
                [http://rs.tdwg.org/dwc/terms/scientificName] => Lichenobactridium Diederich & Etayo
                [http://rs.tdwg.org/dwc/terms/namePublishedIn] => Flechten Follmann (Cologne), Contributions to Lichenology in Honour of Gerhard Follmann 212 (1995)
                [http://rs.tdwg.org/dwc/terms/taxonRank] => genus
                [http://rs.tdwg.org/dwc/terms/scientificNameAuthorship] => Diederich & Etayo
                [http://rs.tdwg.org/dwc/terms/taxonomicStatus] => accepted
                [http://rs.tdwg.org/dwc/terms/datasetID] => 7ddf754f-d193-4cc9-b351-99906754a03b
                [http://rs.gbif.org/terms/1.0/canonicalName] => Lichenobactridium
                [http://eol.org/schema/EOLid] => 37570
            )*/
            if($dwca == 'gbif_classification_pre') {
                $this->gbif_classification[$rec['http://rs.tdwg.org/dwc/terms/taxonID']] = $rec['http://eol.org/schema/EOLid']; //gbif_id -> EOLid
            }
            /* Array( some fields were deleted coz its too many to list here:
                [http://rs.tdwg.org/dwc/terms/taxonID] => -100000
                [http://rs.tdwg.org/dwc/terms/acceptedNameUsageID] => -100000
                [http://rs.tdwg.org/dwc/terms/parentNameUsageID] => -79407
                [http://rs.tdwg.org/dwc/terms/scientificName] => Frescocyathus nagagreboensis Barta-Calmus, 1969
                [http://rs.tdwg.org/dwc/terms/taxonRank] => species
                [http://purl.org/dc/terms/source] => gbif:4943435
                [http://rs.tdwg.org/dwc/terms/taxonomicStatus] => accepted
                [http://rs.gbif.org/terms/1.0/canonicalName] => Frescocyathus nagagreboensis
                [http://rs.tdwg.org/dwc/terms/scientificNameAuthorship] => Barta-Calmus, 1969
                [http://rs.tdwg.org/dwc/terms/datasetID] => 6cfd67d6-4f9b-400b-8549-1933ac27936f
                [http://eol.org/schema/EOLid] => 
                [http://eol.org/schema/EOLidAnnotations] => 
                [http://eol.org/schema/Landmark] => 
            )*/
            if($dwca == 'DH0.9') {
                if($source = $rec['http://purl.org/dc/terms/source']) {
                    // echo $source."\n";
                    if($gbif_id = self::get_gbif_id_from_source($source)) {
                        $this->DH09[$gbif_id] = $rec['http://rs.tdwg.org/dwc/terms/taxonID']; //gbif_id -> DH_id
                    }
                }
            }
        }
    }
    private function access_dwca($dwca, $expire_seconds)
    {   
        $download_options = $this->download_options;
        if($expire_seconds) $download_options['expire_seconds'] = $expire_seconds;
        elseif($expire_seconds === 0) $download_options['expire_seconds'] = $expire_seconds;
        
        // /* un-comment in real operation
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($this->service[$dwca], "meta.xml", $download_options);
        echo "\n[$dwca] - ";
        print_r($paths); //exit;
        // */
        
        /* local when developing, and when running reports and final version: gbif_classification.tar.gz
        if($dwca == 'backbone_dwca') { //for main operation - gbif classification
            // print_r($paths); exit("\nbackbone_dwca\n");
            $paths = Array(
                    'archive_path' => '/Volumes/AKiTiO4/eol_php_code_tmp/dir_21383/',
                    'temp_dir' => '/Volumes/AKiTiO4/eol_php_code_tmp/dir_21383/'
                );
        }
        if($dwca == 'gbif_classification_pre') { //files here are manually moved to this destination, everytime a new version of gbif_classification_pre.tar.gz comes.
            print_r($paths); exit("\n[gbif_classification_pre]\n");
            $paths = Array(
                "archive_path" => "/Library/WebServer/Documents/eol_php_code/tmp/gbif_dir_classification_pre/",
                "temp_dir" => "/Library/WebServer/Documents/eol_php_code/tmp/gbif_dir_classification_pre/"
            );
        }
        if($dwca == 'DH0.9') { //files here are manually moved to this destination:
            // print_r($paths); exit("\n[DH0.9]\n");
            $paths = Array(
                'archive_path' => "/Volumes/AKiTiO4/web/cp/DATA-1826 GBIF class/eoldynamichierarchywithlandmarks/",
                'temp_dir'     => "/Volumes/AKiTiO4/web/cp/DATA-1826 GBIF class/eoldynamichierarchywithlandmarks/"
            );
        }
        */
        return $paths;
    }
    private function process_eolpageids_csv()
    {
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $options = $this->download_options;
        $options['expire_seconds'] = false;
        $paths = $func->extract_archive_file($this->service['DH0.9 EOL pageID mappings'], 'eolpageids.csv', $options, false);
        // print_r($paths); exit;
        
        $csv_file = $paths['archive_path'].'/eolpageids.csv';
        if(!file_exists($csv_file)) exit("\n[DH0.9 EOL pageID mappings] cannot be loaded!\nWill terminate.\n");
        
        $file = fopen($csv_file, 'r'); $i = 0;
        while(($line = fgetcsv($file)) !== FALSE) { $i++; 
            if($i == 1) $fields = $line;
            else {
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k]; $k++;
                }
                // print_r($rec); exit;
                /* Array(
                    [DH_id] => -1
                    [EOL_id] => 2913056
                )*/
                $this->DH_map[$rec['DH_id']] = $rec['EOL_id']; //DH_id -> EOLid
            }
        }
    }
    private function get_gbif_id_from_source($source)
    {   /*  gbif:4562002
            WOR:925487,gbif:9077856
        */
        $arr = explode(",", $source);
        $arr = array_map('trim', $arr);
        foreach($arr as $sors) {
            $arr2 = explode(":", $sors);
            if($arr2[0] == 'gbif') return $arr2[1];
        }
        return false;
    }
    /*-------------------------------------- end report here --------------------------------------------*/
    function start()
    {   $paths = self::access_dwca('backbone_dwca', 60*60*24*30); //2nd param false means expire_seconds = false; BUT ideal value is 1 month OR as only refresh as needed.
        $archive_path = $paths['archive_path'];
        $temp_dir = $paths['temp_dir'];
        
        $harvester = new ContentArchiveReader(NULL, $archive_path);
        $tables = $harvester->tables;
        if(!($this->fields["taxa"] = $tables["http://rs.tdwg.org/dwc/terms/taxon"][0]->fields)) { // take note the index key is all lower case
            debug("Invalid archive file. Program will terminate.");
            return false;
        }

        /* working but not used here...
        if(!($file = Functions::file_open($this->log_file, "w"))) return;
        fwrite($file, implode("\t", array('taxonID', 'scientificName', 'searched string', 'flag'))."\n");
        fclose($file);
        */
        
        self::process_taxon($tables['http://rs.tdwg.org/dwc/terms/taxon'][0]);
        $this->archive_builder->finalize(TRUE);
        print_r($this->debug);
        // /* un-comment in real operation
        recursive_rmdir($temp_dir);
        echo ("\n temporary directory removed: " . $temp_dir);
        // */
    }
    private function process_taxon($meta)
    {   //print_r($meta);
        echo "\nprocess_taxon...\n"; $i = 0;
        $m = 5858200/7; //total rows = 5,858,143. Rounded to 5858200. For caching.
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 200000) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            // $row = Functions::conv_to_utf8($row); //possibly to fix special chars. but from copied template
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            }
            // print_r($rec); //exit;
            /*Array(
                [http://rs.tdwg.org/dwc/terms/taxonID] => 9651193                   [http://rs.tdwg.org/dwc/terms/datasetID] => 61a5f178-b5fb-4484-b6d8-9b129739e59d
                [http://rs.tdwg.org/dwc/terms/parentNameUsageID] => 95
                [http://rs.tdwg.org/dwc/terms/acceptedNameUsageID] =>               [http://rs.tdwg.org/dwc/terms/originalNameUsageID] => 
                [http://rs.tdwg.org/dwc/terms/scientificName] => SH200216.07FU      [http://rs.tdwg.org/dwc/terms/scientificNameAuthorship] => 
                [http://rs.gbif.org/terms/1.0/canonicalName] =>                     [http://rs.gbif.org/terms/1.0/genericName] => 
                [http://rs.tdwg.org/dwc/terms/specificEpithet] =>                   [http://rs.tdwg.org/dwc/terms/infraspecificEpithet] => 
                [http://rs.tdwg.org/dwc/terms/taxonRank] => unranked
                [http://rs.tdwg.org/dwc/terms/nameAccordingTo] =>                   [http://rs.tdwg.org/dwc/terms/namePublishedIn] => 
                [http://rs.tdwg.org/dwc/terms/taxonomicStatus] => accepted          [http://rs.tdwg.org/dwc/terms/nomenclaturalStatus] => 
                [http://rs.tdwg.org/dwc/terms/taxonRemarks] => 
                [http://rs.tdwg.org/dwc/terms/kingdom] => Fungi                     [http://rs.tdwg.org/dwc/terms/phylum] => Ascomycota
                [http://rs.tdwg.org/dwc/terms/class] =>                             [http://rs.tdwg.org/dwc/terms/order] => 
                [http://rs.tdwg.org/dwc/terms/family] =>                            [http://rs.tdwg.org/dwc/terms/genus] => 
            )*/
            $taxonomicStatus = $rec['http://rs.tdwg.org/dwc/terms/taxonomicStatus'];
            $this->debug[$taxonomicStatus] = '';

            /* good debug
            if($rec['http://rs.tdwg.org/dwc/terms/taxonID'] == 4943435) {
                    print_r($rec); //exit;
            }
            else continue;
            */
            
            // if($taxonomicStatus != 'accepted') continue;    //ERROR: There is undefined parent(s): 6160
            if($taxonomicStatus == 'synonym') continue;     //OK: All parents in taxon.tab have entries.
            
            self::write_archive($rec);
            
            // if($i >= 100) break;
        }
    }
    private function write_archive($rec)
    {
        $fields = array_keys($rec);
        // print_r($fields); exit;
        $taxon = new \eol_schema\Taxon();
        // $taxon->EOLid = @$eol_rec['id'];
        foreach($fields as $field) {
            $var = pathinfo($field, PATHINFO_BASENAME);
            if(in_array($var, array('genericName'))) continue;
            $taxon->$var = $rec[$field];
        }
        $this->archive_builder->write_object_to_file($taxon);
    }
}
?>