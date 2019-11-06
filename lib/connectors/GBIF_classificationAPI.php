<?php
namespace php_active_record;
// connector: [gbif_classification.php]
class GBIF_classificationAPI
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
            'download_wait_time' => 500000, 'timeout' => 60*3, 'download_attempts' => 2, 'delay_in_minutes' => 0.5, 'cache' => 1);
        // /* i've set to expire false coz DH09 is still relative. There is also DH11
        $this->download_options['expire_seconds'] = false;
        // */
        if(Functions::is_production()) {
            $this->service["backbone_dwca"] = "http://rs.gbif.org/datasets/backbone/backbone-current.zip";
            $this->service["gbif_classification_pre"] = "https://editors.eol.org/eol_php_code/applications/content_server/resources/gbif_classification_pre.tar.gz";
        }
        else {
            $this->service["backbone_dwca"] = "http://localhost/cp/GBIF_Backbone_Archive/backbone-current.zip";
            $this->service["gbif_classification_pre"] = "http://localhost/eol_php_code/applications/content_server/resources_2/gbif_classification_pre.tar.gz";
        }
        $this->log_file = CONTENT_RESOURCE_LOCAL_PATH.'gbif_names_not_found_in_eol.txt';

        /* for comparison report DH 0.9 vs my gbif_classification DwCA */
        /*
        $this->service['DH0.9'] = 'https://opendata.eol.org/dataset/0a023d9a-f8c3-4c80-a8d1-1702475cda18/resource/1b375a39-4739-45ba-87cd-328bdd50ec34/download/eoldynamichierarchywithlandmarks.zip';
        $this->service['DH0.9 EOL pageID mappings'] = 'https://opendata.eol.org/dataset/b6bb0c9e-681f-4656-b6de-39aa3a82f2de/resource/118fbbd8-71df-4ef9-90f5-5b4a663c7602/download/eolpageids.csv.gz';
        */
        $this->service['DH0.9'] = 'http://localhost/cp/DATA-1826 GBIF class/eoldynamichierarchywithlandmarks.zip'; //the meta.xml is manually edited by Eli. rowtype changed to "http://rs.tdwg.org/dwc/terms/taxon".
        $this->comparison_report = CONTENT_RESOURCE_LOCAL_PATH.'GBIF_id_EOL_id_coverage_comparison_report.txt';
        $this->debug = array();
        /*
        2,845,724 taxon.tab -> gbif_classification
        2,724,940 taxa.txt -> eoldynamichierarchywithlandmarks
        2,724,668 eolpageids.csv
        */
        require_library('connectors/Eol_v3_API');
        $this->func_eol_v3 = new Eol_v3_API();
    }
    //=================================================START fix remaining conflicts between the API & DH09 mappings
    function fix_remaining_conflicts($info)
    {
        // /* get info lists
        self::build_info('DH0.9');                  //builds -> $this->DH09[gbif_id] = DH_id; //gbif_id -> DH_id
        self::process_eolpageids_csv();             //builds -> $this->DH_map[DH_id] = EOLid; //DH_id -> EOLid
        // */
        $tables = $info['harvester']->tables;
        self::process_fix_taxon($tables['http://rs.tdwg.org/dwc/terms/taxon'][0]);
        print_r($this->debug);
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
            $API_EOL_id = $rec['http://eol.org/schema/EOLid'];
            $gbif_id = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
            $DH09_EOL_id = self::get_EOL_id_given_GBIF_id($gbif_id);
            if($API_EOL_id == $DH09_EOL_id) @$this->debug['stats']['Matched OK']++;
            else {
                @$this->debug['stats']['Mis-matched']++;
                if($DH09_EOL_id) @$this->debug['stats with DH09_EOL_id']['Mis-matched']++;
                else             @$this->debug['stats without DH09_EOL_id']['Mis-matched']++;

                if($val = $DH09_EOL_id) $rec['http://eol.org/schema/EOLid'] = $val;
            }
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
    private function get_EOL_id_given_GBIF_id($gbif_id)
    {
        //builds -> $this->DH09[gbif_id] = DH_id; //gbif_id -> DH_id
        //builds -> $this->DH_map[DH_id] = EOLid; //DH_id -> EOLid
        if($DH_id = @$this->DH09[$gbif_id]) {
            if($EOL_id = @$this->DH_map[$DH_id]) return $EOL_id;
        }
    }
    //=================================================END fix remaining conflicts between the API & DH09 mappings

    function utility_compare_2_DH_09() //just ran locally. Not yet in eol-archive
    {
        self::build_info('gbif_classification_pre'); //builds -> $this->gbif_classification[gbif_id] = EOLid; //gbif_id -> EOLid
        self::build_info('DH0.9');                   //builds -> $this->DH09[gbif_id] = DH_id; //gbif_id -> DH_id
        self::process_eolpageids_csv();              //builds -> $this->DH_map[DH_id] = EOLid; //DH_id -> EOLid
        self::write_comparison_report();
        print_r($this->debug);
    }
    private function write_comparison_report()
    {   /* just reminder for what these 3 vars are. Debug only
        $this->gbif_classification[gbif_id] = EOLid; //gbif_id -> EOLid
        $this->DH09[gbif_id] = DH_id; //gbif_id -> DH_id
        $this->DH_map[DH_id] = EOLid; //DH_id -> EOLid
        */
        $headers = array('GBIF_id', 'EOL_id_from_API_match', 'DH09_taxonID', 'EOL_id_from_DH09', 'Match_YN');
        $file = Functions::file_open($this->comparison_report, "w");
        fwrite($file, implode("\t", $headers)."\n");
        foreach($this->gbif_classification as $GBIF_id => $EOL_id) {
            $DH_id = ''; $EOL_id2 = '';
            if($DH_id = @$this->DH09[$GBIF_id]) {
                if($EOL_id2 = @$this->DH_map[$DH_id]) {}
                else $EOL_id2 = '';
            }
            else $DH_id = '';
            $vals = array($GBIF_id, $EOL_id, $DH_id, $EOL_id2, self::match_YN($EOL_id, $EOL_id2, $DH_id, $GBIF_id));
            fwrite($file, implode("\t", $vals)."\n");
        }
        fclose($file);
    }
    private function match_YN($EOL_id, $EOL_id2, $DH_id, $GBIF_id)
    {
        if($EOL_id == $EOL_id2) $ans = 'Yes';
        else $ans = 'No';
        if($DH_id) $withDH = 'with DH';
        else       $withDH = 'without DH';
        @$this->debug['match_YN'][$withDH][$ans]++;
        if($ans == 'No' && $withDH == 'with DH') $this->debug['to investigate']["$GBIF_id | $EOL_id | $DH_id | $EOL_id2"] = '';
        return $ans;
    }
    private function build_info($dwca)
    {
        $paths = self::access_dwca($dwca, false); //false here 2nd param is expire_seconds
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
    private function access_dwca($dwca, $expire_seconds = false)
    {   
        $download_options = $this->download_options;
        if($expire_seconds) $download_options['expire_seconds'] = $expire_seconds;
        // /* un-comment in real operation
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($this->service[$dwca], "meta.xml", $download_options);
        print_r($paths); //exit;
        // */
        /* local when developing
        if($dwca == 'backbone_dwca') { //for main operation - gbif classification
            $paths = Array(
                'archive_path' => '/Library/WebServer/Documents/eol_php_code/tmp/gbif_dir_gbif_backbone/',
                'temp_dir' => '/Library/WebServer/Documents/eol_php_code/tmp/gbif_dir_gbif_backbone/'
            );
        }
        if($dwca == 'gbif_classification_pre') {
            $paths = Array(
                "archive_path" => "/Library/WebServer/Documents/eol_php_code/tmp/gbif_dir_classification_pre/",
                "temp_dir" => "/Library/WebServer/Documents/eol_php_code/tmp/gbif_dir_classification_pre/"
            );
        }
        if($dwca == 'DH0.9') {
            $paths = Array(
                'archive_path' => "/Library/WebServer/Documents/eol_php_code/tmp/gbif_dir_DH09/",
                'temp_dir' => "/Library/WebServer/Documents/eol_php_code/tmp/gbif_dir_DH09/"
            );
        }
        */
        return $paths;
    }
    private function process_eolpageids_csv()
    {
        $file = fopen('/Volumes/AKiTiO4/web/cp/DATA-1826 GBIF class/eolpageids.csv', 'r'); $i = 0;
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
    {   $paths = self::access_dwca('backbone_dwca');
        $archive_path = $paths['archive_path'];
        $temp_dir = $paths['temp_dir'];
        
        $harvester = new ContentArchiveReader(NULL, $archive_path);
        $tables = $harvester->tables;
        if(!($this->fields["taxa"] = $tables["http://rs.tdwg.org/dwc/terms/taxon"][0]->fields)) { // take note the index key is all lower case
            debug("Invalid archive file. Program will terminate.");
            return false;
        }

        if(!($file = Functions::file_open($this->log_file, "w"))) return;
        fwrite($file, implode("\t", array('taxonID', 'scientificName', 'searched string', 'flag'))."\n");
        fclose($file);
        
        self::process_taxon($tables['http://rs.tdwg.org/dwc/terms/taxon'][0]);
        $this->archive_builder->finalize(TRUE);

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
            $i++; if(($i % 1000) == 0) echo "\n".number_format($i);
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
            
            /* debug only
            // if($rec['http://rs.tdwg.org/dwc/terms/taxonID'] != '3269382') continue; //GBIF Ciliophora
            // if($rec['http://rs.tdwg.org/dwc/terms/taxonID'] != '4774221') continue; //GBIF Cavernicola
            // if($rec['http://rs.tdwg.org/dwc/terms/taxonID'] != '1864404') continue; //GBIF Sphinx
            // if($rec['http://rs.tdwg.org/dwc/terms/taxonID'] != '7328508') continue; //GBIF Erica multiflora subsp. multiflora
            // if($rec['http://rs.tdwg.org/dwc/terms/taxonID'] != '1081098') continue; //GBIF Macronotops sexmaculatus
            if($rec['http://rs.tdwg.org/dwc/terms/taxonID'] != '1891') continue; //GBIF Capsosiraceae
            */
            
            /* breakdown when caching
            $cont = false;
            // if($i >=  1    && $i < $m)    $cont = true;          //1st run
            // if($i >=  $m   && $i < $m*2)  $cont = true;          //2nd run
            // if($i >=  $m*2 && $i < $m*3)  $cont = true;          //3rd run
            // if($i >=  $m*3 && $i < $m*4)  $cont = true;          //4th run max 3 347 542
            // if($i >=  $m*4 && $i < $m*5)  $cont = true;          //5th run max 4 184 428
            // if($i >=  $m*5 && $i < $m*6)  $cont = true;          //6th run max 5 021 314
            // if($i >=  $m*6 && $i < $m*7)  $cont = true;          //7th run max 5 858 200
            if(!$cont) continue;
            */

            /* breakdown when caching
            $cont = false;
            // if($i >=  3000000 && $i < $m*4)  $cont = true;          //conn2 max 3 347 542
            // if($i >=  4000000 && $i < $m*5)  $cont = true;          //conn3 max 4 184 428
            // if($i >=  4900000 && $i < $m*6)  $cont = true;          //conn8 max 5 021 314
            if($i >=  5700000 && $i < $m*7)  $cont = true;          //conn9 max 5 858 200
            if(!$cont) continue;
            */

            
            /* breakdown when caching
            $m = 5858200/7/6;
            $cont = false;
            if($i >=  1    && $i < $m)    $cont = true;          //1st run
            // if($i >=  $m   && $i < $m*2)  $cont = true;          //conn2
            // if($i >=  $m*2 && $i < $m*3)  $cont = true;          //conn3 done
            // if($i >=  $m*3 && $i < $m*4)  $cont = true;          //conn8
            // if($i >=  $m*4 && $i < $m*5)  $cont = true;          //conn9
            // if($i >=  $m*5 && $i < $m*6)  $cont = true;          //conn10
            if(!$cont) continue;
            */
            
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

            /* good debug
            if($rec['http://rs.tdwg.org/dwc/terms/taxonID'] == 4943435) {
                    print_r($rec); //exit;
            }
            else continue;
            */
            
            // if($rec['http://rs.tdwg.org/dwc/terms/taxonID'] == 7009828) { print_r($rec); exit; } //debug only

            if($taxonomicStatus != 'accepted') continue;
            
            // $rec['http://rs.gbif.org/terms/1.0/canonicalName'] = "Erica multiflora multiflora"; //debug only

            if($val = $rec['http://rs.gbif.org/terms/1.0/canonicalName'])       $sciname = $val;
            elseif($val = $rec['http://rs.tdwg.org/dwc/terms/scientificName'])  $sciname = Functions::canonical_form($val);
            else { self::log_record($rec, '', '1'); continue; }
            if(!$sciname) { self::log_record($rec, '', '2'); continue; }

            $str = substr($sciname,0,2);
            if(strtoupper($str) == $str) { //probably viruses will ignore [$sciname]
                // self::log_record($rec, $sciname);
                continue;
            }
            else {
                // /* debug only ---------------------------------------------------------------------------
                // $sciname = 'Sphinx';
                // $sciname = 'Erica multiflora multiflora';
                // $sciname = 'Ciliophora'; //e.g. of homonyms #1 in Katja's findings
                // $sciname = 'Cavernicola';
                // ----------------------------------------------------------------------------------------- */
                if($eol_rec = self::main_sciname_search($sciname, $rec)) {
                    self::write_archive($rec, $eol_rec);
                    // print_r($eol_rec); exit("\ndebug only\n");
                }
                else { self::log_record($rec, $sciname, '4'); continue; }
            }
            // if($i >= 90) break;
        }
    }
    function main_sciname_search($sciname, $rec)
    {
        if($GLOBALS['ENV_DEBUG'] == true) echo "\nwill process [$sciname] ";
        $eol_rec = array();
        $hits = self::get_all_hits_for_search_string($sciname);
        if(count($hits) <= 1) {
            if(count($hits) == 1 && $eol_rec = self::search_api_with_moving_offset_number($sciname, $sciname)) {
                // self::write_archive($rec, $eol_rec);
                debug("\nused regular option\n");
            }
            elseif(count($hits) == 0 && self::is_subspecies($sciname)) {
                $species = self::get_species_from_subspecies($sciname);
                if($species == $sciname) return array();
                if($eol_rec = self::search_api_with_moving_offset_number($species, $sciname)) {
                    // self::write_archive($rec, $eol_rec);
                    debug("\nused subspecies option\n");
                    /* Array(
                        [id] => 52540300
                        [title] => Erica multiflora multiflora
                        [link] => https://eol.org/pages/52540300
                        [content] => Erica multiflora multiflora; Erica multiflora subsp. multiflora; Erica multiflora f. alba (Regel) D. Mc Clintock; Erica multiflora var. alba Regel; <i>Erica multiflora alba</i>
                    )*/
                }
            }
        }
        else { //homonym treatment
            if($eol_rec = self::pick_from_multiple_hits($hits, $rec, $sciname)) {
                // self::write_archive($rec, $eol_rec);
                debug("\npicked 1 from multiple hits\n");
                /*Array(
                    [id] => 46724417
                    [title] => Ciliophora
                    [link] => https://eol.org/pages/46724417
                    [content] => Ciliophora; Ciliophora Petrak in H. Sydow & Petrak, 1929
                )*/
            }
        }
        // print_r($eol_rec); exit("\nstopx\n");
        return $eol_rec;
    }
    private function get_all_hits_for_search_string($sciname)
    {   $final = array();
        if($ret = $this->func_eol_v3->search_name($sciname, $this->download_options)) {
            $total_loop = ceil($ret['totalResults']/50);
            for($page_no = 1; $page_no <= $total_loop; $page_no++) { //start loop to all, in batches of 50
                if($ret = $this->func_eol_v3->search_name($sciname, $this->download_options, $page_no)) {
                    foreach($ret['results'] as $r) {
                        if($sciname == $r['title']) $final[] = $r;
                    }
                }
            }
        }
        return $final;
    }
    private function pick_from_multiple_hits($hits, $rec, $sciname)
    {   // print_r($hits); print_r($rec); exit("\n$sciname\n");
        /* Array(
            [0] => Array(
                    [id] => 4666
                    [title] => Ciliophora
                    [link] => https://eol.org/pages/4666
                    [content] => Ciliophora
                )
            [1] => Array(
                    [id] => 46724417
                    [title] => Ciliophora
                    [link] => https://eol.org/pages/46724417
                    [content] => Ciliophora; Ciliophora Petrak in H. Sydow & Petrak, 1929
                )
        */
        $this->hit_final = array(); $choices = array();
        foreach($hits as $hit) {
            $choices[$hit['id']] = $hit;
            self::get_same_rank_as_sciname_in_question($hit, $rec['http://rs.tdwg.org/dwc/terms/taxonRank'], $rec['http://rs.tdwg.org/dwc/terms/taxonID']);
        }
        // print_r($this->hit_final);
        /* Array(
            [46724417] => Array(
                    [ranks] => Array(
                            [genus] => 
                        )
                    [sourceIdentifiers] => Array(
                            [GBIF:3269382] => 
                        )
                )
        )*/
        $final_taxonID = false;
        foreach($this->hit_final as $taxonID => $items) {
            if(@$items['ranks'] && @$items['sourceIdentifiers']) $final_taxonID = $taxonID;
        }
        if(!$final_taxonID) {
            foreach($this->hit_final as $taxonID => $items) {
                if(@$items['sourceIdentifiers']) $final_taxonID = $taxonID;
            }
        }
        if(!$final_taxonID) {
            foreach($this->hit_final as $taxonID => $items) {
                if(@$items['ranks']) $final_taxonID = $taxonID;
            }
        }
        if($final_taxonID) {
            if($val = $choices[$final_taxonID]) return $val;
        }
    }
    private function get_same_rank_as_sciname_in_question($hit, $rank, $taxonID)
    {
        if($rek = $this->func_eol_v3->search_eol_page_id($hit['id'], $this->download_options)) {
            // print_r($rek);
            // echo "\n".count($rek['taxonConcept']['taxonConcepts'])."\n";
            foreach($rek['taxonConcept']['taxonConcepts'] as $r) {
                /*[0] => Array(
                    [identifier] => 7130522
                    [scientificName] => Ciliophora
                    [name] => Ciliophora
                    [nameAccordingTo] => EOL Dynamic Hierarchy 0.9
                    [canonicalForm] => Ciliophora
                    [sourceIdentifier] => -23632
                    [taxonRank] => phylum //not all have taxonRank
                )*/
                
                if(strtolower(@$r['taxonRank']) == strtolower($rank)) @$this->hit_final[$rek['taxonConcept']['identifier']]['ranks'][@$r['taxonRank']] = '';
                
                if(stripos($r['sourceIdentifier'], "gbif:$taxonID") !== false) { //string is found
                    @$this->hit_final[$rek['taxonConcept']['identifier']]['sourceIdentifiers'][$r['sourceIdentifier']] = '';
                }
                
            }
        }
        // if($final) print_r($final);
        // exit("\nstopx\n");
    }
    private function search_api_with_moving_offset_number($sciname, $sciname2use_for_func_get_actual_name)
    {
        // $eol_rec = Array('id' => '', 'title' => '', 'link' => '', 'content' => ''); not used for now...
        $eol_rec = false;
        if($ret = $this->func_eol_v3->search_name($sciname, $this->download_options)) {
            // echo("\n".$ret['totalResults']."\n"); 
            $total_loop = ceil($ret['totalResults']/50);
            // echo("\n".$total_loop."\n");
            
            for($page_no = 1; $page_no <= $total_loop; $page_no++) { //start loop to all, in batches of 50
                if($ret = $this->func_eol_v3->search_name($sciname, $this->download_options, $page_no)) {
                    if($GLOBALS['ENV_DEBUG'] == true) echo " - ".count($ret['results']);
                    if($eol_rec = self::get_actual_name($ret, $sciname2use_for_func_get_actual_name)) return $eol_rec;
                    // good debug
                    // if($rec['http://rs.tdwg.org/dwc/terms/taxonID'] == '4943435') {
                    //     print_r($rec); print_r($ret); print_r($eol_rec); exit;
                    // }
                }
            }
            
            if(!$eol_rec) {
                if($ret = $this->func_eol_v3->search_name($sciname, $this->download_options, 1)) { //alternatively, just return the first record
                    if($ret['results']) return $ret['results'][0];
                }
            }
        }
        return false;
    }
    private function is_subspecies($sciname)
    {
        $arr = explode(" ", $sciname);
        $arr = array_filter($arr); //remove null arrays
        $arr = array_values($arr); //reindex key
        if(count($arr) >= 3) return true;
        else return false;
    }
    private function get_species_from_subspecies($sciname)
    {
        $arr = explode(" ", $sciname);
        $arr = array_filter($arr); //remove null arrays
        $arr = array_values($arr); //reindex key
        return trim($arr[0]." ".$arr[1]);
    }
    private function log_record($rec, $sciname = '', $flag = '')
    {
        if(!($file = Functions::file_open($this->log_file, "a"))) return;
        fwrite($file, implode("\t", array($rec['http://rs.tdwg.org/dwc/terms/taxonID'], $rec['http://rs.tdwg.org/dwc/terms/scientificName'], "[$sciname]", $flag))."\n");
        fclose($file);
    }
    private function write_archive($rec, $eol_rec)
    {
        // print_r($rec); print_r($eol_rec);
        $fields = array_keys($rec);
        // print_r($fields); exit;
        /*Array( $eol_rec
            [id] => 37570
            [title] => Lichenobactridium
            [link] => https://eol.org/pages/37570
            [content] => Lichenobactridium; Lichenobactridium P. Diederich & J. Etayo in F.J.A. Daniels et al., 1995
        )*/
        $taxon = new \eol_schema\Taxon();
        $taxon->EOLid = $eol_rec['id'];
        // $taxon->EOLidAnnotations = $eol_rec['content'];
        foreach($fields as $field) {
            $var = pathinfo($field, PATHINFO_BASENAME);
            if(in_array($var, array('genericName'))) continue;
            $taxon->$var = $rec[$field];
        }
        $this->archive_builder->write_object_to_file($taxon);
    }
    private function get_actual_name($ret, $sciname)
    {
        foreach($ret['results'] as $r) { //first loop gets exact match only
            /*Array(
                [id] => 37570
                [title] => Lichenobactridium
                [link] => https://eol.org/pages/37570
                [content] => Lichenobactridium; Lichenobactridium P. Diederich & J. Etayo in F.J.A. Daniels et al., 1995
            )*/
            if($sciname == $r['title']) return $r;
        }
        return false;
    }
    /*
    private function create_taxon_archive($a)
    {
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonomicStatus          = self::compute_taxonomicStatus($a);
        $taxon->taxonID                  = self::compute_taxonID($a, $taxon->taxonomicStatus);
        $taxon->scientificName           = $a[$this->map['scientificName']];
        $taxon->scientificNameAuthorship = @$a[$this->map['scientificNameAuthorship']];
        $taxon->taxonRank                = self::compute_taxonRank($a);
        $taxon->acceptedNameUsageID      = self::numerical_part(@$a[$this->map['acceptedNameUsageID']]);
        $this->archive_builder->write_object_to_file($taxon);
    }
    */
}
?>