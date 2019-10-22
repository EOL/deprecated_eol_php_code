<?php
namespace php_active_record;
// connector: [gbif_classification.php]
class GBIF_classificationAPI
{
    function __construct($folder)
    {
        $this->resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));

        $this->download_options = array(
            'resource_id'        => 'eol_api_v3',  //resource_id here is just a folder name in cache
            'expire_seconds'     => 60*60*24*30*3, //expires quarterly
            'download_wait_time' => 1000000, 'timeout' => 60*3, 'download_attempts' => 2, 'delay_in_minutes' => 1, 'cache' => 1);

        if(Functions::is_production()) {
            $this->service["backbone_dwca"] = "http://rs.gbif.org/datasets/backbone/backbone-current.zip";
            $this->service["gbif_classification"] = "https://editors.eol.org/eol_php_code/applications/content_server/resources/gbif_classification.tar.gz";
        }
        else {
            $this->service["backbone_dwca"] = "http://localhost/cp/GBIF_Backbone_Archive/backbone-current.zip";
            $this->service["gbif_classification"] = "/Volumes/MacMini_HD2/work_temp/gbif_classification.tar.gz";
        }
        $this->log_file = CONTENT_RESOURCE_LOCAL_PATH.'gbif_names_not_found_in_eol.txt';

        /* for comparison report DH 0.9 vs my gbif_classification DwCA */
        /*
        $this->service['DH0.9'] = 'https://opendata.eol.org/dataset/0a023d9a-f8c3-4c80-a8d1-1702475cda18/resource/1b375a39-4739-45ba-87cd-328bdd50ec34/download/eoldynamichierarchywithlandmarks.zip';
        $this->service['DH0.9 EOL pageID mappings'] = 'https://opendata.eol.org/dataset/b6bb0c9e-681f-4656-b6de-39aa3a82f2de/resource/118fbbd8-71df-4ef9-90f5-5b4a663c7602/download/eolpageids.csv.gz';
        */
        $this->service['DH0.9'] = 'http://localhost/cp/DATA-1826 GBIF class/eoldynamichierarchywithlandmarks.zip';
        $this->comparison_report = CONTENT_RESOURCE_LOCAL_PATH.'GBIF_id_EOL_id_coverage_comparison_report.txt';
        $this->debug = array();
        /*
        2,845,724 taxon.tab -> gbif_classification
        2,724,940 taxa.txt -> eoldynamichierarchywithlandmarks
        2,724,668 eolpageids.csv
        */
    }
    function utility_compare_2_DH_09()
    {
        self::build_info('gbif_classification');
        self::build_info('DH0.9');
        self::process_eolpageids_csv();
        self::write_comparison_report();
        print_r($this->debug);
    }
    private function write_comparison_report()
    {   /* just reminder for what these 3 vars are. Debug only
        $this->gbif_classification[$rec['http://rs.tdwg.org/dwc/terms/taxonID']] = $rec['http://eol.org/schema/EOLid']; //gbif_id -> EOLid
        $this->DH09[$gbif_id] = $rec['http://rs.tdwg.org/dwc/terms/taxonID']; //gbif_id -> DH_id
        $this->DH_map[$rec['DH_id']] = $rec['EOL_id']; //DH_id -> EOLid
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
        
        /* un-comment in real operation
        recursive_rmdir($temp_dir);
        echo ("\n temporary directory removed: " . $temp_dir);
        */
    }
    private function process_taxon_4report($meta, $dwca)
    {   //print_r($meta);
        echo "\nprocess_taxon...\n"; $i = 0;
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
            if($dwca == 'gbif_classification') {
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
        /* un-comment in real operation
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($this->service[$dwca], "meta.xml", $download_options);
        print_r($paths); exit;
        */
        // /* local when developing
        if($dwca == 'gbif_classification') {
            $paths = Array(
                "archive_path" => "/Library/WebServer/Documents/eol_php_code/tmp/gbif_dir_classification/",
                "temp_dir" => "/Library/WebServer/Documents/eol_php_code/tmp/gbif_dir_classification/"
            );
        }
        if($dwca == 'DH0.9') {
            $paths = Array(
                'archive_path' => "/Library/WebServer/Documents/eol_php_code/tmp/gbif_dir_DH09/",
                'temp_dir' => "/Library/WebServer/Documents/eol_php_code/tmp/gbif_dir_DH09/"
            );
        }
        // */
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
        require_library('connectors/Eol_v3_API');
        $func = new Eol_v3_API();
        
        echo "\nprocess_taxon...\n"; $i = 0;
        $m = 5858200/7; //total rows = 5,858,143. Rounded to 5858200. For caching.
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
            
            /* breakdown when caching
            $cont = false;
            if($i >=  1    && $i < $m)    $cont = true;          //1st run
            // if($i >=  $m   && $i < $m*2)  $cont = true;          //2nd run
            // if($i >=  $m*2 && $i < $m*3)  $cont = true;          //3rd run
            // if($i >=  $m*3 && $i < $m*4)  $cont = true;          //4th run
            // if($i >=  $m*4 && $i < $m*5)  $cont = true;          //5th run
            // if($i >=  $m*5 && $i < $m*6)  $cont = true;          //6th run
            // if($i >=  $m*6 && $i < $m*7)  $cont = true;          //7th run
            if(!$cont) continue;
            */
            
            // print_r($rec); exit;
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
            if(strtoupper($str) == $str) { //probably viruses
                // echo "\nwill ignore [$sciname]\n";
                // self::log_record($rec, $sciname);
                continue;
            }
            else {
                $eol_rec = Array('id' => '', 'title' => '', 'link' => '', 'content' => '');
                // /*
                if($GLOBALS['ENV_DEBUG'] == true) echo "\nwill process [$i][$sciname] "; // print_r($rec);
                if($ret = $func->search_name($sciname, $this->download_options)) {
                    if($GLOBALS['ENV_DEBUG'] == true) echo " - ".count($ret['results']);
                    $eol_rec = self::get_actual_name($ret, $sciname);
                    /* good debug
                    if($rec['http://rs.tdwg.org/dwc/terms/taxonID'] == '4943435') {
                        print_r($rec);
                        print_r($ret);
                        print_r($eol_rec);
                        exit;
                    }
                    */
                }
                if(!$eol_rec['id'] && self::is_subspecies($sciname)) {
                    $species = self::get_species_from_subspecies($sciname);
                    if($ret = $func->search_name($species, $this->download_options)) {
                        if($GLOBALS['ENV_DEBUG'] == true) echo " - ".count($ret['results']);
                        $eol_rec = self::get_actual_name($ret, $sciname, false); //last_resort = false
                    }
                }
                self::write_archive($rec, $eol_rec);
                if(!$eol_rec['id']) { self::log_record($rec, $sciname, '3'); continue; }
                // */
            }
            // if($i >= 90) break;
        }
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
        return $arr[0]." ".$arr[1];
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
    private function get_actual_name($ret, $sciname, $last_resortYN = true)
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
        if($last_resortYN) {
            if($ret['results']) return $ret['results'][0]; //alternatively, just return the first record
        }
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