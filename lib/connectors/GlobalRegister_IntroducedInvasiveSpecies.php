<?php
namespace php_active_record;
/* connector: global_register_IIS.php

wget -q http://api.gbif.org/v1/occurrence/download/request/0010139-190918142434337.zip -O /extra/other_files/GBIF_DwCA/Germany_0010139-190918142434337.zip

http://ipt.ala.org.au/
http://ipt.ala.org.au/rss.do
*/
class GlobalRegister_IntroducedInvasiveSpecies
{
    function __construct($resource_id)
    {
        $this->resource_id = $resource_id;
        // $this->archive_builder = $archive_builder;
        
        $this->download_options = array('cache' => 1, 'resource_id' => $resource_id, 'expire_seconds' => 60*60*24*25, 'download_wait_time' => 1000000, 
        'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1);
        // $this->download_options['expire_seconds'] = false; //comment after first harvest
        
        $this->service['list of ISSG datasets'] = 'https://www.gbif.org/api/dataset/search?facet=type&facet=publishing_org&facet=hosting_org&facet=publishing_country&facet=project_id&facet=license&locale=en&offset=OFFSET_NO&publishing_org=cdef28b1-db4e-4c58-aa71-3c5238c2d0b5&type=CHECKLIST';
        $this->service['dataset'] = 'https://api.gbif.org/v1/dataset/DATASET_KEY/document';
        $this->south_africa = '3cabcf37-db13-4dc1-9bf3-e6f3fbfbbe23';
        
        if(Functions::is_production()) {
            // $this->download_options['cache_path'] = "/extra/eol_cache_gbif/";
            $this->dwca_folder = '/extra/other_files/GBIF_DwCA/ISSG/';
        }
        else {
            // $this->download_options['resource_id'] = "gbif";
            // $this->download_options['cache_path'] = "/Volumes/Thunderbolt4/eol_cache/";
            $this->dwca_folder = CONTENT_RESOURCE_LOCAL_PATH.'ISSG/';
        }
        $this->comparison_file = CONTENT_RESOURCE_LOCAL_PATH.'dataset_comparison.txt';
        $this->exclude['taxon'] = array('acceptedNameUsageID', 'genus', 'specificEpithet', 'infraspecificEpithet', 'language', 
        'license', 'rightsHolder', 'bibliographicCitation', 'datasetID', 'datasetName', 'references');
        $this->exclude['speciesprofile'] = array('isMarine', 'isFreshwater', 'isTerrestrial');
    }
    function start()
    {
        $dataset_keys = self::get_all_dataset_keys(); //123 datasets as of Oct 11, 2019
        // print_r($dataset_keys);
        $i = 0;
        foreach($dataset_keys as $dataset_key) { $i++;
            $this->info[$dataset_key] = self::get_dataset_info($dataset_key);
            // print_r($this->info); exit;
            if($i >= 5) break; //debug only
        }
        foreach($dataset_keys as $dataset_key) { $i++;
            self::process_dataset($dataset_key);
            if($i >= 5) break; //debug only
        }
    }
    private function process_dataset($dataset_key)
    {   /*Array(
            [6d9e952f-948c-4483-9807-575348147c7e] => Array(
                    [orig] => https://ipt.inbo.be/resource?r=unified-checklist
                    [download_url] => https://ipt.inbo.be/archive.do?r=unified-checklist
                )
        )*/
        $info = self::download_extract_dwca($this->info[$dataset_key]['download_url'], $dataset_key);
        $temp_dir = $info['temp_dir'];
        $harvester = $info['harvester'];
        $tables = $info['tables'];
        $index = $info['index'];

        $tables = $info['harvester']->tables;
        self::process_taxon($tables['http://rs.tdwg.org/dwc/terms/taxon'][0]);
        // /* un-comment in real operation -- remove temp dir
        recursive_rmdir($temp_dir);
        echo ("\n temporary directory removed: $temp_dir\n");
        // */
    }
    private function process_taxon($meta)
    {   //print_r($meta);
        echo "\nprocess_measurementorfact...\n"; $i = 0;
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
            print_r($rec); exit;
            /*Array(
                [http://rs.tdwg.org/dwc/terms/measurementID] => M1
            )*/
            //===========================================================================================================================================================
            //===========================================================================================================================================================
            //===========================================================================================================================================================
            //===========================================================================================================================================================
            $o = new \eol_schema\Taxon();
            $uris = array_keys($rec);
            foreach($uris as $uri) {
                $field = pathinfo($uri, PATHINFO_BASENAME);
                $o->$field = $rec[$uri];
            }
            $this->archive_builder->write_object_to_file($o);
            // if($i >= 10) break; //debug only
        }
    }
    
    function compare_meta_between_datasets() //utility to generate a report
    {
        $dataset_keys = self::get_all_dataset_keys(); //123 datasets as of Oct 11, 2019
        print_r($dataset_keys);
        $i = 0;
        foreach($dataset_keys as $dataset_key) { $i++;
            $this->info[$dataset_key] = self::get_dataset_info($dataset_key);
            // print_r($this->info); exit;
            // if($i >= 10) break; //debug only
        }
        
        $this->fhandle = Functions::file_open($this->comparison_file, "w");
        
        echo "\nSouth Africa\n";
        $this->south_africa = self::investigate_dataset($this->south_africa);
        // print_r($this->south_africa);

        // print_r($dataset_keys); exit;
        $i = 0;
        foreach($dataset_keys as $dataset_key) { $i++; echo "\n$i. $dataset_key\n";
            if($dataset_key == $this->south_africa) continue;
            // self::start_comparison('6d9e952f-948c-4483-9807-575348147c7e'); //e.g. Belgium
            self::start_comparison($dataset_key);
            // if($i >= 10) break; //debug only
        }

        fclose($this->fhandle);
        exit("\n-end for now-\n");
    }
    private function start_comparison($dataset_key)
    {
        echo "\n------------------------------------------------------------------------------\n".$this->info[$dataset_key]['dataset_name']."\n";
        fwrite($this->fhandle, "\n----------------------------------START - ".$this->info[$dataset_key]['dataset_name']." ----------------------------------\n"); 
        $country = self::investigate_dataset($dataset_key);
        /* compare no. of rowtypes against South Africa */
        if($arr = array_diff($country['rowtypes'], $this->south_africa['rowtypes'])) {
            echo "\nThere are extra tables not found in South Africa.\n";
            fwrite($this->fhandle, "\nThere are extra tables not found in South Africa.\n");
            $arr = array_values($arr); //reindex key
            print_r($arr);
            //start write to text
            $txt = implode("\n", $arr);
            fwrite($this->fhandle, $txt);
        }
        /* now compare fields in each rowtype */
        foreach($this->south_africa['rowtypes'] as $rt) {
            if($arr = array_diff($country[$rt], $this->south_africa[$rt])) {
                echo "\nThere are extra fields in [$rt], not found in South Africa.\n";
                fwrite($this->fhandle, "\n\nThere are extra fields in [$rt], not found in South Africa.\n");
                $arr = array_values($arr); //reindex key
                print_r($arr);
                //start write to text
                $txt = implode("\n", $arr);
                fwrite($this->fhandle, $txt);
            }
        }
        fwrite($this->fhandle, "\n----------------------------------END - ".$this->info[$dataset_key]['dataset_name']." ----------------------------------\n"); 
    }
    private function investigate_dataset($dataset_key)
    {   /*Array(
            [6d9e952f-948c-4483-9807-575348147c7e] => Array(
                    [orig] => https://ipt.inbo.be/resource?r=unified-checklist
                    [download_url] => https://ipt.inbo.be/archive.do?r=unified-checklist
                )
        )*/
        $info = self::download_extract_dwca($this->info[$dataset_key]['download_url'], $dataset_key);
        $temp_dir = $info['temp_dir'];
        $harvester = $info['harvester'];
        $tables = $info['tables'];
        $index = $info['index'];

        $tables = $info['harvester']->tables;
        // self::process_measurementorfact($tables['http://rs.tdwg.org/dwc/terms/taxon'][0]);
        $rowtypes = array_keys($tables);
        $final = array();
        foreach($rowtypes as $rowtype) {
            $meta = $tables[$rowtype][0];
            // print_r($meta);
            $fields = self::get_all_fields($meta);
            // print_r($fields);
            $final[$rowtype] = $fields;
        }
        $final['rowtypes'] = $rowtypes;

        // /* un-comment in real operation -- remove temp dir
        recursive_rmdir($temp_dir);
        echo ("\n temporary directory removed: $temp_dir\n");
        // */
        // print_r($final);
        return $final;
    }
    private function get_all_fields($meta)
    {
        foreach($meta->fields as $f) $final[$f['term']] = '';
        return array_keys($final);
    }
    private function download_extract_dwca($url, $dataset_key)
    {
        $download_options = array('timeout' => 172800, 'expire_seconds' => 60*60*24*30); //probably default expires in a month 60*60*24*30. Not false.
        $target = $this->dwca_folder."$dataset_key.zip";
        if(!file_exists($target)) {
            $out = shell_exec("wget -q $url -O $target");
            echo "\n$out\n";
        }
        else echo "\nalready exists: [$target]\n";
        
        // /* un-comment in real operation
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($target, "meta.xml", $download_options); //true 'expire_seconds' means it will re-download, will NOT use cache. Set TRUE when developing
        // print_r($paths); exit;
        // */

        /* development only
        $paths = Array (
            'archive_path' => "/Library/WebServer/Documents/eol_php_code/tmp/flora_dir_29170/",
            'temp_dir' => "/Library/WebServer/Documents/eol_php_code/tmp/flora_dir_29170/"
        );
        */
        
        $archive_path = $paths['archive_path'];
        $temp_dir = $paths['temp_dir'];
        $harvester = new ContentArchiveReader(NULL, $archive_path);
        $tables = $harvester->tables;
        $index = array_keys($tables);
        if(!($tables["http://rs.tdwg.org/dwc/terms/taxon"][0]->fields)) { // take note the index key is all lower case
            debug("Invalid archive file. Program will terminate.");
            return false;
        }
        return array("harvester" => $harvester, "temp_dir" => $temp_dir, "tables" => $tables, "index" => $index);
    }
    private function get_dataset_info($dataset_key)
    {
        $url = str_replace('DATASET_KEY', $dataset_key, $this->service['dataset']);
        if($xml = Functions::lookup_with_cache($url, $this->download_options)) {
            if(preg_match_all("/<alternateIdentifier>(.*?)<\/alternateIdentifier>/ims", $xml, $arr)) {
                foreach($arr[1] as $aI) {
                    if(substr($aI,0,4) == 'http') {
                        // echo "\n$aI";
                        /* string manipulate from: $aI to: $download_url
                        https://ipt.inbo.be/resource?r=unified-checklist
                        https://ipt.inbo.be/archive.do?r=unified-checklist

                        http://ipt.ala.org.au/resource?r=griis-united_kingdom
                        http://ipt.ala.org.au/archive.do?r=griis-united_kingdom
                        */

                        if(preg_match("/<title>(.*?)<\/title>/ims", $xml, $arr)) $dataset_name = $arr[1];
                        

                        $download_url = str_replace('resource?', 'archive.do?', $aI);
                        return array('dataset_name' => $dataset_name, 'orig' => $aI, 'download_url' => $download_url);
                    }
                }
            }
        }
    }
    private function get_all_dataset_keys()
    {
        if($total_datasets = self::get_total_no_datasets()) {
            $counter = ceil($total_datasets/20) - 1; //minus 1 is important. Needed due to the nature of offset values
            $offset = 0;
            for($i = 0; $i <= $counter; $i++) {
                // echo "\n$offset";
                $url = str_replace('OFFSET_NO', $offset, $this->service['list of ISSG datasets']);
                if($json = Functions::lookup_with_cache($url, $this->download_options)) {
                    $obj = json_decode($json);
                    foreach($obj->results as $res) $dataset_keys[$res->key] = '';
                }
                $offset = $offset + 20;
            }
            return array_keys($dataset_keys);
        }
    }
    private function get_total_no_datasets()
    {   $url = str_replace('OFFSET_NO', '0', $this->service['list of ISSG datasets']);
        if($json = Functions::lookup_with_cache($url, $this->download_options)) {
            $obj = json_decode($json);
            return $obj->count;
        }
    }
    /*================================================================= copied templates below ======================================================================*/
    function x_start($info)
    {   $tables = $info['harvester']->tables;
        self::process_measurementorfact($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0]);
        self::process_occurrence($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0]);
        unset($this->occurrenceID_bodyPart);
        
        require_library('connectors/TraitGeneric'); $this->func = new TraitGeneric($this->resource_id, $this->archive_builder);
        
        self::initialize_mapping(); //for location string mappings
        self::process_per_state();
    }
    private function initialize_mapping()
    {   $mappings = Functions::get_eol_defined_uris(false, true);     //1st param: false means will use 1day cache | 2nd param: opposite direction is true
        echo "\n".count($mappings). " - default URIs from EOL registry.";
        $this->uris = Functions::additional_mappings($mappings); //add more mappings used in the past
        // self::use_mapping_from_jen();
        // print_r($this->uris);
    }
    private function process_measurementorfact($meta)
    {   //print_r($meta);
        echo "\nprocess_measurementorfact...\n"; $i = 0;
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
            } // print_r($rec); exit;
            /*Array(
                [http://rs.tdwg.org/dwc/terms/measurementID] => M1
            )*/
            //===========================================================================================================================================================
            //===========================================================================================================================================================
            //===========================================================================================================================================================
            //===========================================================================================================================================================
            $o = new \eol_schema\MeasurementOrFact_specific();
            $uris = array_keys($rec);
            foreach($uris as $uri) {
                $field = pathinfo($uri, PATHINFO_BASENAME);
                $o->$field = $rec[$uri];
            }
            $this->archive_builder->write_object_to_file($o);
            // if($i >= 10) break; //debug only
        }
    }
    private function process_occurrence($meta)
    {   //print_r($meta);
        echo "\nprocess_occurrence...\n"; $i = 0;
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
                [http://rs.tdwg.org/dwc/terms/occurrenceID] => O1
                [http://rs.tdwg.org/dwc/terms/taxonID] => ABGR4
                [http://rs.tdwg.org/dwc/terms/eventID] => http://plants.usda.gov/core/profile?symbol=ABGR4
                [http://rs.tdwg.org/dwc/terms/institutionCode] => 
                [http://rs.tdwg.org/dwc/terms/collectionCode] => 
                [http://rs.tdwg.org/dwc/terms/catalogNumber] => 
                [http://rs.tdwg.org/dwc/terms/sex] => 
                [http://rs.tdwg.org/dwc/terms/lifeStage] => 
                [http://rs.tdwg.org/dwc/terms/reproductiveCondition] => 
                [http://rs.tdwg.org/dwc/terms/behavior] => 
                [http://rs.tdwg.org/dwc/terms/establishmentMeans] => 
                [http://rs.tdwg.org/dwc/terms/occurrenceRemarks] => 
                [http://rs.tdwg.org/dwc/terms/individualCount] => 
                [http://rs.tdwg.org/dwc/terms/preparations] => 
                [http://rs.tdwg.org/dwc/terms/fieldNotes] => 
                [http://rs.tdwg.org/dwc/terms/samplingProtocol] => 
                [http://rs.tdwg.org/dwc/terms/samplingEffort] => 
                [http://rs.tdwg.org/dwc/terms/recordedBy] => 
                [http://rs.tdwg.org/dwc/terms/identifiedBy] => 
                [http://rs.tdwg.org/dwc/terms/dateIdentified] => 
                [http://rs.tdwg.org/dwc/terms/eventDate] => 
                [http://purl.org/dc/terms/modified] => 
                [http://rs.tdwg.org/dwc/terms/locality] => 
                [http://rs.tdwg.org/dwc/terms/decimalLatitude] => 
                [http://rs.tdwg.org/dwc/terms/decimalLongitude] => 
                [http://rs.tdwg.org/dwc/terms/verbatimLatitude] => 
                [http://rs.tdwg.org/dwc/terms/verbatimLongitude] => 
                [http://rs.tdwg.org/dwc/terms/verbatimElevation] => 
            )*/
            $uris = array_keys($rec);
            $uris = array('http://rs.tdwg.org/dwc/terms/occurrenceID', 'http://rs.tdwg.org/dwc/terms/taxonID', 'http:/eol.org/globi/terms/bodyPart');
            if($bodyPart = @$this->occurrenceID_bodyPart[$rec['http://rs.tdwg.org/dwc/terms/occurrenceID']]) $rec['http:/eol.org/globi/terms/bodyPart'] = $bodyPart;
            else                                                                                             $rec['http:/eol.org/globi/terms/bodyPart'] = '';
            $o = new \eol_schema\Occurrence_specific();
            foreach($uris as $uri) {
                $field = pathinfo($uri, PATHINFO_BASENAME);
                $o->$field = $rec[$uri];
            }
            $this->archive_builder->write_object_to_file($o);
            // if($i >= 10) break; //debug only
        }
    }
    private function create_taxon($rec)
    {
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID  = $rec["Symbol"];
        $taxon->scientificName  = $rec["Scientific Name with Author"];
        $taxon->taxonomicStatus = 'valid';
        $taxon->family  = $rec["Family"];
        $taxon->source = $rec['source_url'];
        // $taxon->taxonRank       = '';
        // $taxon->taxonRemarks    = '';
        // $taxon->rightsHolder    = '';
        if(!isset($this->taxon_ids[$taxon->taxonID])) {
            $this->taxon_ids[$taxon->taxonID] = '';
            $this->archive_builder->write_object_to_file($taxon);
        }
    }
    private function create_vernacular($rec)
    {   if($comname = $rec['National Common Name']) {
            $v = new \eol_schema\VernacularName();
            $v->taxonID         = $rec["Symbol"];
            $v->vernacularName  = $comname;
            $v->language        = 'en';
            $this->archive_builder->write_object_to_file($v);
        }
    }
    /*================================================================= ENDS HERE ======================================================================*/
}
?>
