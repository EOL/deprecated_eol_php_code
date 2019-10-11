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
        
        $this->download_options = array('cache' => 1, 'resource_id' => $resource_id, 'expire_seconds' => 60*60*24*25, 'download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1);
        // $this->download_options['expire_seconds'] = false; //comment after first harvest
        
        $this->service['list of ISSG datasets'] = 'https://www.gbif.org/api/dataset/search?facet=type&facet=publishing_org&facet=hosting_org&facet=publishing_country&facet=project_id&facet=license&locale=en&offset=OFFSET_NO&publishing_org=cdef28b1-db4e-4c58-aa71-3c5238c2d0b5&type=CHECKLIST';
        $this->service['dataset'] = 'https://api.gbif.org/v1/dataset/1288ee7d-d67c-4e23-8d95-409973067383/document';
    }
    function compare_meta_between_datasets()
    {
        $dataset_keys = self::get_all_dataset_keys(); //123 datasets as of Oct 11, 2019
        print_r($dataset_keys);

        /* string manipulate from: to:
        https://ipt.inbo.be/resource?r=unified-checklist
        https://ipt.inbo.be/archive.do?r=unified-checklist
        
        http://ipt.ala.org.au/resource?r=griis-united_kingdom
        http://ipt.ala.org.au/archive.do?r=griis-united_kingdom
        */
        exit("\n-end for now-\n");
    }
    private function get_all_dataset_keys()
    {
        if($total_datasets = self::get_total_no_datasets()) {
            $counter = ceil($total_datasets/20) - 1; //minus 1 is important. Needed due to the nature of offset values
            $offset = 0;
            for($i = 0; $i <= $counter; $i++) {
                echo "\n$offset";

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
    /*================================================================= STARTS HERE ======================================================================*/
    function start($info)
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
    function process_per_state()
    {   $state_list = self::parse_state_list_page();
        foreach($state_list as $territory => $states) {
            echo "\n[$territory]\n"; // print_r($states); exit;
            foreach($states as $str) { //[0] => java/stateDownload?statefips=US01">Alabama
                if(preg_match("/statefips=(.*?)\"/ims", $str, $arr)) {
                    // echo "\nDownloading HTML ".$arr[1]."...";
                    if($local = Functions::save_remote_file_to_local($this->service['per_state_page'].$arr[1], $this->download_options)) {
                        self::parse_state_list($local, $arr[1]);
                        if(file_exists($local)) unlink($local);
                    }
                }
            }
        }
    }
    private function parse_state_list_page()
    {   if($html = Functions::lookup_with_cache($this->state_list_page, $this->download_options)) {
            if(preg_match_all("/class=\"BodyTextBlackBold\">(.*?)<\/td>/ims", $html, $arr)) {
                $a = $arr[1];
                $a = array_map('strip_tags', $a); // print_r($a);
                /*Array(
                    [0] => U.S. States
                    [1] => U.S. Territories and Protectorates
                    [2] => Canada
                    [3] => Denmark
                    [4] => France
                )*/
                $i = -1;
                foreach($a as $area) { $i++;
                    if($area == 'France') {
                        if(preg_match("/class=\"BodyTextBlackBold\">".$area."(.*?)<\/table>/ims", $html, $arr)) {
                            if(preg_match_all("/href=\"(.*?)<\/a>/ims", $arr[1], $arr2)) $final[$area] = $arr2[1];
                        }
                    }
                    else {
                        if(preg_match("/class=\"BodyTextBlackBold\">".$area."(.*?)class=\"BodyTextBlackBold\">".$a[$i+1]."/ims", $html, $arr)) {
                            if(preg_match_all("/href=\"(.*?)<\/a>/ims", $arr[1], $arr2)) $final[$area] = $arr2[1];
                        }
                    }
                }
            }
        }
        print_r($final); //exit;
        $this->area_id_info = self::assign_id_2_locations($final);
        return $final;
    }
    private function assign_id_2_locations($state_list)
    {   foreach($state_list as $territory => $states) {
            // echo "\n[$territory]\n"; // print_r($states); exit;
            foreach($states as $str) { //[0] => java/stateDownload?statefips=US01">Alabama
                $id = false; $location = false;
                if(preg_match("/statefips=(.*?)\"/ims", $str, $arr)) $id = $arr[1];
                if(preg_match("/>(.*?)elix/ims", $str.'elix', $arr)) $location = $arr[1];
                if($id && $location) {
                    $final[$id] = $location;
                    /* for stats only
                    if($string_uri = self::get_string_uri($location)) echo $string_uri;
                    else                                              echo " no uri";
                    */
                }
            }
        }
        return $final;
    }
    function parse_state_list($local, $state_id)
    {   echo "\nprocessing [$state_id]\n";
        
        // /* important: check if without data e.g. https://plants.sc.egov.usda.gov/java/stateDownload?statefips=CANFCALB
        $contents = file_get_contents($local);
        if(stripos($contents, "No Data Found") !== false) { //string is found
            echo " -- No Data Found -- \n";
            return;
        }
        // */
        
        $file = fopen($local, 'r');
        $i = 0;
        while(($line = fgetcsv($file)) !== FALSE) {
            $i++;
            if($i == 1) $fields = $line;
            else {
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k]; $k++;
                } // print_r($rec); exit;
                /*Array(
                    [Symbol] => DIBR2
                    [Synonym Symbol] => 
                    [Scientific Name with Author] => Dicliptera brachiata (Pursh) Spreng.
                    [National Common Name] => branched foldwing
                    [Family] => Acanthaceae
                )*/
                if(!$rec['Synonym Symbol'] && @$rec['Symbol']) { //echo " ".$rec['Symbol'];
                    $rec['source_url'] = $this->service['taxon_page'] . $rec['Symbol'];
                    self::create_taxon($rec);
                    self::create_vernacular($rec);
                    if($NorI_data = self::parse_profile_page($this->service['taxon_page'].$rec['Symbol'])) { //NorI = Native or Introduced
                        self::write_NorI_measurement($NorI_data, $rec);
                    }
                    // write presence for this state
                    self::write_presence_measurement_for_state($state_id, $rec);
                }
            }
        }
    }
    private function get_string_uri($string)
    {   if($string_uri = @$this->uris[$string]) return $string_uri;
        switch ($string) { //put here customized mapping
            case "Québec":    return 'http://www.wikidata.org/entity/Q176';             /* The 4 entries here were already added to gen. mappings in Functions.php */
            case "Quebec":    return 'http://www.wikidata.org/entity/Q176';
            case "Qu&eacute;bec":    return 'http://www.wikidata.org/entity/Q176';
            case "St. Pierre and Miquelon": return 'http://www.geonames.org/3424932';
        }
    }
    private function write_presence_measurement_for_state($state_id, $rec)
    {   $string_value = $this->area_id_info[$state_id];
        if($string_uri = self::get_string_uri($string_value)) {}
        else {
            $this->debug['no uri mapping yet'][$string_value];
            $string_uri = $string_value;
        }
        $mValue = $string_uri;
        $mType = 'http://eol.org/schema/terms/Present'; //for generic range
        $taxon_id = $rec['Symbol'];
        $save = array();
        $save['taxon_id'] = $taxon_id;
        $save["catnum"] = $taxon_id.'_'.$mType.$mValue; //making it unique. no standard way of doing it.
        $save['source'] = $rec['source_url'];
        $save['measurementRemarks'] = $string_value;
        // $save['measurementID'] = '';
        $this->func->add_string_types($save, $mValue, $mType, "true");
    }
    private function write_NorI_measurement($NorI_data, $rec)
    {   /*Array([0] => Array(
                    [0] => L48
                    [1] => N
                )
        )*/
        foreach($NorI_data as $d) {
            if($d[0] == 'None') continue;
            $mValue = $this->area[$d[0]]['uri'];
            $mRemarks = @$this->area[$d[0]]['mRemarks'];
            /* seems $d[1] can have values like: I,N,W OR PB ; not just single N or I */
            $arr = explode(",", $d[1]);
            foreach($arr as $type) {
                if(!in_array($type, array('N',"I"))) continue;
                $mType = $this->NorI_mType[$type];
                $taxon_id = $rec['Symbol'];
                $save = array();
                $save['taxon_id'] = $taxon_id;
                $save["catnum"] = $taxon_id.'_'.$mType.$mValue; //making it unique. no standard way of doing it.
                $save['source'] = $rec['source_url'];
                // $save['measurementID'] = '';
                $save['measurementRemarks'] = $mRemarks;
                $this->func->add_string_types($save, $mValue, $mType, "true");
            }
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
    function parse_profile_page($url)
    {   $final = false;
        if($html = Functions::lookup_with_cache($url, $this->download_options)) {
            if(preg_match("/Status<\/strong>(.*?)<\/tr>/ims", $html, $arr)) {
                $str = $arr[1];
                $str = str_ireplace(' valign="top"', '', $str); // echo "\n$str\n";
                if(preg_match("/<td>(.*?)<\/td>/ims", $str, $arr2)) {
                    $str = str_replace(array("\t", "\n", "&nbsp;"), "", $arr2[1]);
                    $str = Functions::remove_whitespace($str); // echo "\n[$str]\n";
                    $arr = explode("<br>", $str);
                    $arr = array_filter($arr); //remove null array
                    // print_r($arr);
                    /*Array(
                        [0] => CAN N
                        [1] => L48 N
                        [2] => SPM N
                    )*/
                    foreach($arr as $a) $final[] = explode(" ", $a);
                }
            }
            else exit("\nInvestigate $url status not found!\n");
        }
        return $final;
    }
    /*================================================================= ENDS HERE ======================================================================*/
}
?>
