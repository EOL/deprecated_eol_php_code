<?php
namespace php_active_record;
/* connector: [called from DwCA_Utility.php, which is called from 727.php for DATA-1819] */
class USDAPlants2019
{
    function __construct($archive_builder, $resource_id)
    {
        $this->resource_id = $resource_id;
        $this->archive_builder = $archive_builder;
        
        $this->download_options = array('cache' => 1, 'resource_id' => $resource_id, 'expire_seconds' => 60*60*24*30*4, 'download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1);
        // $this->download_options['expire_seconds'] = false; //comment after first harvest
        $this->area['L48'] = "Lower 48 United States of America";
        $this->area['AK'] = "Alaska, USA";
        $this->area['HI'] = "Hawaii, USA";
        $this->area['PR'] = "Puerto Rico";
        $this->area['VI'] = "U. S. Virgin Islands";
        $this->area['CAN'] = "Canada";
        $this->area['GL'] = "Greenland (Denmark)";
        $this->area['SPM'] = "St. Pierre and Miquelon (France)";
        $this->area['NA'] = "North America (only non-vascular plants and lichens have Native Status given at this level)"; //"North America";
        $this->area['NAV'] = "Navassa Island (The sole Caribbean member of the United States Minor Outlying Islands)"; //"Navassa Island";
        $this->area['PB'] = "Pacific Basin excluding Hawaii";
        $this->NorI_mType['N'] = 'http://eol.org/schema/terms/NativeRange';
        $this->NorI_mType['I'] = 'http://eol.org/schema/terms/IntroducedRange';
        $this->state_list_page = 'https://plants.sc.egov.usda.gov/dl_state.html';
        $this->service['taxon_page'] = 'https://plants.usda.gov/core/profile?symbol=';
        $this->service['per_state_page'] = 'https://plants.sc.egov.usda.gov/java/stateDownload?statefips=';
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
                [http://rs.tdwg.org/dwc/terms/occurrenceID] => O1
                [http://eol.org/schema/measurementOfTaxon] => true
                [http://eol.org/schema/associationID] => 
                [http://eol.org/schema/parentMeasurementID] => 
                [http://rs.tdwg.org/dwc/terms/measurementType] => http://purl.obolibrary.org/obo/TO_0002725
                [http://rs.tdwg.org/dwc/terms/measurementValue] => http://eol.org/schema/terms/perennial
                [http://rs.tdwg.org/dwc/terms/measurementUnit] => 
                [http://rs.tdwg.org/dwc/terms/measurementAccuracy] => 
                [http://eol.org/schema/terms/statisticalMethod] => 
                [http://rs.tdwg.org/dwc/terms/measurementDeterminedDate] => 
                [http://rs.tdwg.org/dwc/terms/measurementDeterminedBy] => 
                [http://rs.tdwg.org/dwc/terms/measurementMethod] => 
                [http://rs.tdwg.org/dwc/terms/measurementRemarks] => Source term: Duration. Some plants have different Durations...
                [http://purl.org/dc/terms/source] => http://plants.usda.gov/core/profile?symbol=ABGR4
                [http://purl.org/dc/terms/bibliographicCitation] => The PLANTS Database, United States Department of Agriculture,...
                [http://purl.org/dc/terms/contributor] => 
                [http://eol.org/schema/reference/referenceID] => 
            )*/
            //===========================================================================================================================================================
            /* Data to remove: Katja has heard that records for several of the predicates are suspect. Please remove anything with the predicates below: */
            $pred_2remove = array('http://eol.org/schema/terms/NativeIntroducedRange', 'http://eol.org/schema/terms/NativeProbablyIntroducedRange', 
                'http://eol.org/schema/terms/ProbablyIntroducedRange', 'http://eol.org/schema/terms/ProbablyNativeRange', 
                'http://eol.org/schema/terms/ProbablyWaifRange', 'http://eol.org/schema/terms/WaifRange', 'http://eol.org/schema/terms/InvasiveNoxiousStatus');
            $pred_2remove = array_merge($pred_2remove, array('http://eol.org/schema/terms/NativeRange', 'http://eol.org/schema/terms/IntroducedRange')); //will be removed, to get refreshed.
            if(in_array($rec['http://rs.tdwg.org/dwc/terms/measurementType'], $pred_2remove)) continue;
            //===========================================================================================================================================================
            /* Metadata: For records with measurementType=A, please add lifeStage=B
            A B
            http://eol.org/schema/terms/SeedlingSurvival    http://purl.obolibrary.org/obo/PPO_0001007
            http://purl.obolibrary.org/obo/FLOPO_0015519    http://purl.obolibrary.org/obo/PO_0009010
            http://purl.obolibrary.org/obo/TO_0000207       http://purl.obolibrary.org/obo/PATO_0001701
            */
            $mtype = $rec['http://rs.tdwg.org/dwc/terms/measurementType'];
            $lifeStage = '';
            if($mtype == 'http://eol.org/schema/terms/SeedlingSurvival') $lifeStage = 'http://purl.obolibrary.org/obo/PPO_0001007';
            elseif($mtype == 'http://purl.obolibrary.org/obo/FLOPO_0015519') $lifeStage = 'http://purl.obolibrary.org/obo/PO_0009010';
            elseif($mtype == 'http://purl.obolibrary.org/obo/TO_0000207') $lifeStage = 'http://purl.obolibrary.org/obo/PATO_0001701';

            /* and for records with measurementType=C, please add bodyPart=D
            C D
            http://purl.obolibrary.org/obo/PATO_0001729     http://purl.obolibrary.org/obo/PO_0025034
            http://purl.obolibrary.org/obo/FLOPO_0015519    http://purl.obolibrary.org/obo/PO_0009010
            http://purl.obolibrary.org/obo/TO_0000207       http://purl.obolibrary.org/obo/UBERON_0000468
            */
            $bodyPart = '';
            if($mtype == 'http://purl.obolibrary.org/obo/PATO_0001729') $bodyPart = 'http://purl.obolibrary.org/obo/PO_0025034';
            elseif($mtype == 'http://purl.obolibrary.org/obo/FLOPO_0015519') $bodyPart = 'http://purl.obolibrary.org/obo/PO_0009010';
            elseif($mtype == 'http://purl.obolibrary.org/obo/TO_0000207') $bodyPart = 'http://purl.obolibrary.org/obo/UBERON_0000468';
            
            $rec['http://rs.tdwg.org/dwc/terms/lifeStage'] = $lifeStage;
            $this->occurrenceID_bodyPart[$rec['http://rs.tdwg.org/dwc/terms/occurrenceID']] = $bodyPart;
            //===========================================================================================================================================================
            /* Value term to re-map. I think the source's text string is "Subshrub". 
            It's a value for http://purl.obolibrary.org/obo/FLOPO_0900032, eg: for https://plants.usda.gov/core/profile?symbol=VEBR2
            It's currently mapped to http://purl.obolibrary.org/obo/FLOPO_0900034. It should be re-mapped to http://eol.org/schema/terms/subshrub
            ELI: it seems this has now been corrected. Current data uses http://eol.org/schema/terms/subshrub already. No need to code this requirement.
            */
            //===========================================================================================================================================================
            /* debug only - for 'Additional data' investigation
            if($mtype == 'http://eol.org/schema/terms/NativeRange') $this->debug['NorI'][$rec['http://rs.tdwg.org/dwc/terms/measurementValue']] = '';
            if($mtype == 'http://eol.org/schema/terms/IntroducedRange') $this->debug['NorI'][$rec['http://rs.tdwg.org/dwc/terms/measurementValue']] = '';
            $this->debug['mtype'][$mtype] = '';
            */
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
            $mValue = $this->area[$d[0]];
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
                // $save['measurementRemarks'] = '';
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
    /* not used
    private function process_taxon($meta, $ret)
    {   //print_r($meta);
        $i = 0;
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
            // print_r($rec); exit;
            $o = new \eol_schema\Taxon();
            $uris = array_keys($rec);
            foreach($uris as $uri) {
                $field = pathinfo($uri, PATHINFO_BASENAME);
                $o->$field = $rec[$uri];
            }
            $this->archive_builder->write_object_to_file($o);
            // if($i >= 10) break; //debug only
        }
    }*/
}
?>
