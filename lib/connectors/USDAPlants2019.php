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
        
        $this->debug = array();
        
        /* old service
        $this->state_list_page = 'https://plants.sc.egov.usda.gov/dl_state.html';
        $this->service['taxon_page']     = 'https://plants.usda.gov/core/profile?symbol=';
        $this->service['per_state_page'] = 'https://plants.sc.egov.usda.gov/java/stateDownload?statefips=';
        */

        $this->html['taxon_page'] = 'https://plants.usda.gov/home/plantProfile?symbol=';

        // /* new service
        // $this->state_territory_list = 'https://plants.sc.egov.usda.gov/main.2bb5bc1d4bc87d62d061.js'; -- not used atm
        $this->service['per_location'] = 'https://plants.sc.egov.usda.gov/assets/docs/NRCSStateList/STATE_NAME_NRCS_csv.txt';
        // e.g. https://plants.sc.egov.usda.gov/assets/docs/NRCSStateList/Alabama_NRCS_csv.txt
        $this->service['taxon_page'] = 'https://plantsservices.sc.egov.usda.gov/api/PlantProfile?symbol=';
        // */
        // https://plantsservices.sc.egov.usda.gov/api/PlantProfile?symbol=ABPR3

        $this->area['L48']['uri'] = "http://www.wikidata.org/entity/Q578170";
        $this->area['AK']['uri'] = "http://www.geonames.org/5879092";
        $this->area['HI']['uri'] = "http://www.geonames.org/5855797";
        $this->area['PR']['uri'] = "http://www.geonames.org/4566966";
        $this->area['VI']['uri'] = "http://www.geonames.org/4796775";
        $this->area['CAN']['uri'] = "http://www.geonames.org/6251999";
        $this->area['GL']['uri'] = "http://www.geonames.org/3425505";
        $this->area['SPM']['uri'] = "http://www.geonames.org/3424932";
        $this->area['NA']['uri'] = "http://www.geonames.org/6255149"; //"North America";
        $this->area['NAV']['uri'] = "http://www.geonames.org/5854968"; //"Navassa Island";
        $this->area['PB']['uri'] = "Pacific Basin excluding Hawaii";
        
        $this->area['L48']['mRemarks'] = "Lower 48 United States of America";
        $this->area['AK']['mRemarks'] = "Alaska, USA";
        $this->area['HI']['mRemarks'] = "Hawaii, USA";
        $this->area['PR']['mRemarks'] = "Puerto Rico";
        $this->area['VI']['mRemarks'] = "U. S. Virgin Islands";
        $this->area['CAN']['mRemarks'] = "Canada";
        $this->area['GL']['mRemarks'] = "Greenland (Denmark)";
        $this->area['SPM']['mRemarks'] = "St. Pierre and Miquelon (France)";
        $this->area['NA']['mRemarks'] = "North America (only non-vascular plants and lichens have Native Status given at this level)"; //"North America";
        $this->area['NAV']['mRemarks'] = "Navassa Island (The sole Caribbean member of the United States Minor Outlying Islands)"; //"Navassa Island";
        $this->area['PB']['mRemarks'] = "Pacific Basin excluding Hawaii";
        
        $this->NorI_mType['N'] = 'http://eol.org/schema/terms/NativeRange';
        $this->NorI_mType['I'] = 'http://eol.org/schema/terms/IntroducedRange';

        $this->growth["Forb/herb"] = "http://purl.obolibrary.org/obo/FLOPO_0022142";
        $this->growth["Graminoid"] = "http://purl.obolibrary.org/obo/FLOPO_0900036";
        $this->growth["Lichenous"] = "http://eol.org/schema/terms/lichenous";
        $this->growth["Nonvascular"] = "http://eol.org/schema/terms/nonvascular";
        $this->growth["Shrub"] = "http://purl.obolibrary.org/obo/FLOPO_0900034";
        $this->growth["Subshrub"] = "http://eol.org/schema/terms/subshrub";
        $this->growth["Tree"] = "http://purl.obolibrary.org/obo/FLOPO_0900033";
        $this->growth["Vine"] = "http://purl.obolibrary.org/obo/FLOPO_0900035";

        /*
        Other important info:
        https://editors.eol.org/eol_php_code/applications/content_server/resources/usda.html --> old service investigation
        https://plants.usda.gov/assets/docs/PLANTS_Help_Document.pdf#page=8 --> Source of codes and acronyms.
        https://plantsservices.sc.egov.usda.gov/api/StateSearch --> XML of list know states and territories. But not used ATM.
        */
    }
    /*================================================================= STARTS HERE ======================================================================*/
    function start($info)
    {
        /* works OK but was never used since this connector already uses API service for GrowthHabits, NativeStatuses
        require_library('connectors/USDAPlantNewAPI');
        $this->usda_new = new USDAPlantNewAPI();
        $this->usda_new->initialize();
        // print_r($this->usda_new->US_abbrev_state); //good debug
        // $json = $this->usda_new->lookup_profile('ABAB'); $obj = json_decode($json); print_r($obj); exit("\n-stop muna-\n"); //good debug
        */

        require_library('connectors/TraitGeneric'); 
        $this->func = new TraitGeneric($this->resource_id, $this->archive_builder);
        /* START DATA-1841 terms remapping */
        $this->func->initialize_terms_remapping(60*60*24); //param is $expire_seconds. 0 means expire now.
        /* END DATA-1841 terms remapping */

        // /* ========== Bring in the legacy data: 727_24Oct2017.tar.gz ==========
        $tables = $info['harvester']->tables;
        self::process_measurementorfact($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0]);
        self::process_occurrence($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0]);
        unset($this->occurrenceID_bodyPart);
        // */
        
        /* ========== Below here is bringing in MoF data from available USDA service ========== */

        self::initialize_mapping(); //for location string mappings

        /* self::process_per_state(); --> old implementation, using old HTML service */

        // /* using new service: May 10, 2022
        // $aliases = self::get_state_territory_aliases(); --- not used atm, used below instead.
        $aliases = self::get_state_territory_names();
        self::process_per_state_or_territory($aliases);
        // */
        if($this->debug) print_r($this->debug);
    }
    private function initialize_mapping()
    {   /* seems obsolete already
        $mappings = Functions::get_eol_defined_uris(false, true);     //1st param: false means will use 1day cache | 2nd param: opposite direction is true
        echo "\n".count($mappings). " - default URIs from EOL registry.";
        */
        $mappings = array();
        $this->uris = Functions::additional_mappings($mappings); //add more mappings used in the past
        // self::use_mapping_from_jen(); //copied template
        echo "\nmapping URIs: ".count($this->uris)."\n";
        self::assemble_terms_yml();
        echo "\nmapping URIs: ".count($this->uris)." --- EOL term.yml added.\n";
        // exit("\nLouisiana: ".$this->uris['Louisiana']."\n"); //should be "http://www.geonames.org/4331987"
        if($this->uris['Louisiana'] == "http://www.geonames.org/4331987") echo "\nTest passed OK.\n";
    }
    private function assemble_terms_yml()
    {
        require_library('connectors/EOLterms_ymlAPI');
        $func = new EOLterms_ymlAPI($this->resource_id, $this->archive_builder);
        $ret = $func->get_terms_yml('value'); //sought_type is 'value' --- REMINDER: labels can have the same value but different uri
        foreach($ret as $label => $uri) $this->uris[$label] = $uri;
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
            /* https://eol-jira.bibalex.org/browse/DATA-1819?focusedCommentId=64646&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-64646
            where measurementType is http://purl.obolibrary.org/obo/GO_0009399
            and measurementValue is http://purl.bioontology.org/ontology/SNOMEDCT/260413007
            please remove the record. Thanks! */
            $mvalue = $rec['http://rs.tdwg.org/dwc/terms/measurementValue'];
            $occurrenceID = $rec['http://rs.tdwg.org/dwc/terms/occurrenceID'];
            if($mtype == 'http://purl.obolibrary.org/obo/GO_0009399' && $mvalue == 'http://purl.bioontology.org/ontology/SNOMEDCT/260413007') {
                $this->delete_occurrence_id[$occurrenceID] = '';
                continue;
            }
            //===========================================================================================================================================================
            /* https://eol-jira.bibalex.org/browse/DATA-1819?focusedCommentId=65046&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65046
            remove all records with measurementType=http://eol.org/schema/terms/Uses */
            if($mtype == 'http://eol.org/schema/terms/Uses') {
                $this->delete_occurrence_id[$occurrenceID] = '';
                continue;
            }
            // /* remove mValue == 'http://eol.org/schema/terms/colonizing' per: https://eol-jira.bibalex.org/browse/DATA-1819?focusedCommentId=66813&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-66813
            if($mvalue == 'http://eol.org/schema/terms/colonizing') {
                // echo "\nhuli ka colonizing...\n";
                $this->delete_occurrence_id[$occurrenceID] = '';
                continue;
            }
            // */
            //===========================================================================================================================================================

            // /* Eli stats
            $measurementType = $rec['http://rs.tdwg.org/dwc/terms/measurementType']; // => http://purl.obolibrary.org/obo/TO_0002725
            $measurementValue = $rec['http://rs.tdwg.org/dwc/terms/measurementValue']; // => http://eol.org/schema/terms/perennial
            $this->debug['breakdown'][$measurementType][$measurementValue] = '';
            // */

            $o = new \eol_schema\MeasurementOrFact_specific();
            $uris = array_keys($rec);
            foreach($uris as $uri) {
                $field = pathinfo($uri, PATHINFO_BASENAME);
                $o->$field = $rec[$uri];
            }
            
            /* START DATA-1841 terms remapping */
            $o = $this->func->given_m_update_mType_mValue($o);
            // echo "\nLocal: ".count($this->func->remapped_terms)."\n"; //just testing
            /* END DATA-1841 terms remapping */
            
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
            //===========================================================================================================================================================
            $occurrenceID = $rec['http://rs.tdwg.org/dwc/terms/occurrenceID'];
            if(isset($this->delete_occurrence_id[$occurrenceID])) continue;
            //===========================================================================================================================================================
            
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
    /* old service - obsolete
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
    }*/
    private function process_per_state_or_territory($aliases)
    {   echo "\nStates and Territories total: ".count($aliases)."\n";
        $i = 0;
        foreach($aliases as $alias) { $i++;
            echo "\nDownloading CSV ".$alias."..."."$i of ".count($aliases);
            // continue; //debug only
            $options = $this->download_options;
            $url = str_replace("STATE_NAME", str_replace(" ", "", $alias), $this->service['per_location']);
            if($local = Functions::save_remote_file_to_local($url, $options)) {
                self::parse_state_list($local, $alias);
                if(file_exists($local)) unlink($local);
            }
            // break; //debug - process just 1 alias
        }
    }
    private function get_state_territory_names()
    {
        return array("Alabama", "Alaska", "Arkansas", "Arizona", "California", "Colorado", "Connecticut", "Delaware", "Florida", "Georgia", "Hawaii", "Idaho", "Illinois", "Indiana", "Iowa", "Kansas", "Kentucky", "Louisiana", "Maine", "Maryland", "Massachusetts", "Michigan", "Minnesota", "Mississippi", "Missouri", "Montana", "Nebraska", "Nevada", "New Hampshire", "New Jersey", "New Mexico", "New York", "North Carolina", "North Dakota", "Ohio", "Oklahoma", "Oregon", "Pennsylvania", "Rhode Island", "South Carolina", "South Dakota", "Tennessee", "Texas", "Utah", "Vermont", "Virginia", "Washington", "West Virginia", "Wisconsin", "Wyoming", "Puerto Rico", "Virgin Islands");
    }
    private function get_state_territory_aliases() //isn't used atm.
    {   /*
        {State:"Idaho",Alias:"Idaho",CSV:!1},
        {State:"Illinois",Alias:"Illinois",CSV:!1}
        {territory:"Puerto Rico",Alias:"PuertoRico",CSV:!0},
        {territory:"U.S. Minor Outlying Islands",Alias:"U.S.MinorOutlying Islands",CSV:!1}
        {territory:"Virgin Islands",Alias:"VirginIslands",CSV:!0}
        only "CSV:!0" has records for 'territory'
        the "CSV:!1" has no records for 'territory'
        */
        if($js_file = Functions::lookup_with_cache($this->state_territory_list, $this->download_options)) {
            // exit("\n$js_file\n");
            // {State:"Wyoming",Alias:"Wyoming",CSV:!1}
            // {State:"Alabama",Alias:"Alabama",CSV:!1}
            if(preg_match_all("/\{State:\"(.*?)\,CSV/ims", $js_file, $arr)) {
                foreach($arr[1] as $line) {
                    //[28] => New Hampshire",Alias:"NewHampshire"
                    if(preg_match("/Alias:\"(.*?)\"/ims", $line, $arr2)) $aliases[$arr2[1]] = '';
                }
                // print_r($aliases); exit;
                $state_count = count($aliases);
                echo "\nState: ".$state_count."\n";
            }
            else exit("\nNothing found...\n");

            if(preg_match_all("/\{territory:\"(.*?)\}/ims", $js_file, $arr3)) {
                // print_r($arr3[1]); //good debug
                /*
                [4] => Puerto Rico",Alias:"PuertoRico",CSV:!0
                */
                foreach($arr3[1] as $line) {
                    if(preg_match("/Alias:\"(.*?)\"\,CSV:\!0/ims", $line, $arr2)) $aliases[$arr2[1]] = '';
                }
                // print_r($aliases); exit;
                $territory_count = count($aliases) - $state_count;
                echo "\nTerritory: ".$territory_count."\n";
            }
        }
        else exit("\nCannot lookup: [$this->state_territory_list]\n");
        echo "\nTotal: ".count($aliases)."\n";
        return array_keys($aliases);
    }
    /* obselete - not used anymore
    private function parse_state_list_page()
    {   $final = array();
        if($html = Functions::lookup_with_cache($this->state_list_page, $this->download_options)) {
            // good debug
            // $file = CONTENT_RESOURCE_LOCAL_PATH."/usda.html";
            // $fhandle = Functions::file_open($file, "w");
            // fwrite($fhandle, $html);
            // exit("\nHTML saved\n");
            
            if(preg_match_all("/class=\"BodyTextBlackBold\">(.*?)<\/td>/ims", $html, $arr)) {
                $a = $arr[1];
                $a = array_map('strip_tags', $a); // print_r($a);
                // Array(
                //     [0] => U.S. States
                //     [1] => U.S. Territories and Protectorates
                //     [2] => Canada
                //     [3] => Denmark
                //     [4] => France
                // )
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
        else echo "\nCannot lookup: [$this->state_list_page]\n";
        
        print_r($final); //exit;
        if($final) $this->area_id_info = self::assign_id_2_locations($final);
        else echo "\nNo final var\n";
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
                    // start - for stats only
                    // if($string_uri = self::get_string_uri($location)) echo $string_uri;
                    // else                                              echo " no uri";
                    // end - for stats only
                }
            }
        }
        return $final;
    } */
    function parse_state_list($local, $state_id) //state_id here is e.g. "Puerto Rico"
    {   echo "\nprocessing [$local] [$state_id]\n";
        
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
            $i++; if(($i % 100) == 0) echo " -$i- ";
            if($i == 1) $fields = $line;
            else {
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k]; $k++;
                } //print_r($rec); exit("\nstop munax...\n");
                /*Array( OLD
                    [Symbol] => DIBR2
                    [Synonym Symbol] => 
                    [Scientific Name with Author] => Dicliptera brachiata (Pursh) Spreng.
                    [National Common Name] => branched foldwing
                    [Family] => Acanthaceae
                )
                Array( NEW
                    [Symbol] => ABPR3
                    [Synonym Symbol] => 
                    [Scientific Name with Author] => Abrus precatorius L.
                    [State Common Name] => rosarypea
                    [Family] => Fabaceae
                )
                */
                if(!$rec['Synonym Symbol'] && @$rec['Symbol']) { //echo " ".$rec['Symbol'];
                    $api_profile        = $this->service['taxon_page']  .$rec['Symbol'];
                    $rec['source_url']  = $this->html['taxon_page']     .$rec['Symbol'];
                    $rec['taxonRank'] = self::get_profile_data($api_profile, 1, 'rank');
                    self::create_taxon($rec);
                    /* removed in this resource. Will be served by usda_plants_images.tar.gz
                    self::create_vernacular($rec);
                    */
                    //--------------------------------------------------- Native Status
                    if($NorI_data = self::parse_profile_page($api_profile)) { //NorI = Native or Introduced
                        self::write_NorI_measurement($NorI_data, $rec);
                    }
                    //--------------------------------------------------- Growth Habits
                    if($growth_habits = self::get_profile_data($api_profile, 1, 'GrowthHabits')) {
                        self::write_GrowthHabits($growth_habits, $rec);
                    }
                    //--------------------------------------------------- Present data
                    // write presence for this state
                    self::write_presence_measurement_for_state($state_id, $rec);
                    //---------------------------------------------------
                }
            }
            // if($i >= 5) break; //debug --- get 5 rows from CSV only
        }//end loop
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
    private function write_presence_measurement_for_state($state_id, $rec) //state_id here is "Puerto Rico"
    {   $string_value = $state_id; //e.g. 'Alabama' or 'Puerto Rico'         //old -> $this->area_id_info[$state_id];
        if($string_uri = self::get_string_uri($string_value)) {}
        else {
            $this->debug['no uri mapping yet'][$string_value] = '';
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
        // echo "\nLocal: ".count($this->func->remapped_terms)."\n"; //just testing
        $this->func->pre_add_string_types($save, $mValue, $mType, "true");
    }
    private function write_NorI_measurement($NorI_data, $rec)
    {   /*Array([0] => Array(
                    [0] => L48
                    [1] => N
                )
        )*/
        foreach($NorI_data as $d) {
            $d = array_map('trim', $d);
            if($d[0] == 'None') continue;
            $mValue = $this->area[$d[0]]['uri'];
            $mRemarks = @$this->area[$d[0]]['mRemarks'];
            // echo "\nmValue: [$mValue][$d[0]]\n";
            // echo "\nmRemarks: [$mRemarks][$d[0]]\n";
            // print_r($this->area);
            /* seems $d[1] can have values like: I,N,W OR PB ; not just single N or I */
            $arr = explode(",", $d[1]);
            foreach($arr as $type) {
                if(!in_array($type, array("N","I"))) {
                    $this->debug["Un-initialized Native or Introduced code"][$type] = '';
                    continue;
                }
                $mType = $this->NorI_mType[$type];
                $taxon_id = $rec['Symbol'];
                $save = array();
                $save['taxon_id'] = $taxon_id;
                $save["catnum"] = $taxon_id.'_'.$mType.$mValue; //making it unique. no standard way of doing it.
                $save['source'] = $rec['source_url'];
                // $save['measurementID'] = '';
                $save['measurementRemarks'] = $mRemarks;
                // echo "\nLocal: ".count($this->func->remapped_terms)."\n"; //just testing
                $this->func->pre_add_string_types($save, $mValue, $mType, "true");
            }
        }
    }
    private function write_GrowthHabits($growth_habits, $rec)
    {   /*Array(
                [0] => Vine
            )
        */
        foreach($growth_habits as $string_value) {
            if($string_uri = $this->growth[$string_value]) {}
            else {
                $this->debug['no uri mapping yet - Growth Habit'][$string_value] = '';
                $string_uri = $string_value;
            }
            
            $mValue = $string_uri;
            $mType = 'http://purl.obolibrary.org/obo/FLOPO_0900032';
            $taxon_id = $rec['Symbol'];
            $save = array();
            $save['taxon_id'] = $taxon_id;
            $save["catnum"] = $taxon_id.'_'.$mType.$mValue; //making it unique. no standard way of doing it.
            $save['source'] = $rec['source_url'];
            $save['measurementRemarks'] = $string_value;
            // $save['measurementID'] = '';
            // echo "\nLocal: ".count($this->func->remapped_terms)."\n"; //just testing --- copied template
            $this->func->pre_add_string_types($save, $mValue, $mType, "true");
        }
    }
    private function create_taxon($rec)
    {
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID         = $rec["Symbol"];
        $taxon->scientificName  = $rec["Scientific Name with Author"];
        $taxon->taxonomicStatus = 'valid';
        $taxon->family          = $rec["Family"];
        $taxon->source          = $rec['source_url'];
        $taxon->taxonRank       = $rec['taxonRank'];
        // $taxon->taxonRemarks    = '';
        // $taxon->rightsHolder    = '';
        if(!isset($this->taxon_ids[$taxon->taxonID])) {
            $this->taxon_ids[$taxon->taxonID] = '';
            $this->archive_builder->write_object_to_file($taxon);
        }
    }
    private function create_vernacular($rec)
    {   if($comname = $rec['State Common Name']) {
            $v = new \eol_schema\VernacularName();
            $v->taxonID         = $rec["Symbol"];
            $v->vernacularName  = $comname;
            $v->language        = 'en';
            $vernacular_id = md5($v->taxonID.$v->vernacularName.$v->language);
            if(!isset($this->vernacular_ids[$vernacular_id])) {
                $this->vernacular_ids[$vernacular_id] = '';
                $this->archive_builder->write_object_to_file($v);
            }
        }
    }
    /*
    function parse_profile_page_OLD($url, $trialNo = 1)
    {   $final = false;
        if($trialNo == 1) $options = $this->download_options;
        else {
            $options = $this->download_options;
            $options['expire_seconds'] = 0;
        }
        if($html = Functions::lookup_with_cache($url, $options)) {
            if(preg_match("/Status<\/strong>(.*?)<\/tr>/ims", $html, $arr)) {
                $str = $arr[1];
                $str = str_ireplace(' valign="top"', '', $str); // echo "\n$str\n";
                if(preg_match("/<td>(.*?)<\/td>/ims", $str, $arr2)) {
                    $str = str_replace(array("\t", "\n", "&nbsp;"), "", $arr2[1]);
                    $str = Functions::remove_whitespace($str); // echo "\n[$str]\n";
                    $arr = explode("<br>", $str);
                    $arr = array_filter($arr); //remove null array
                    print_r($arr); exit;
                    Array(
                        [0] => CAN N
                        [1] => L48 N
                        [2] => SPM N
                    )
                    foreach($arr as $a) $final[] = explode(" ", $a);
                }
            }
            else {
                echo("\nInvestigate $url status not found! Trial no. [$trialNo]\n");
                if($trialNo == 1) self::parse_profile_page($url, 2);
                else return false;
            }
        }
        return $final;
    }
    */
    private function get_profile_data($url, $trialNo = 1, $needle)
    {
        if($trialNo == 1) $options = $this->download_options;
        else {
            $options = $this->download_options;
            $options['expire_seconds'] = 0;
        }
        if($json = Functions::lookup_with_cache($url, $options)) {
            $obj = json_decode($json); // print_r($obj); exit;
            if($needle == 'rank') return strtolower(@$obj->Rank);
            elseif($needle == 'GrowthHabits') {
                /*
                <GrowthHabits>
                <d2p1:string>Forb/herb</d2p1:string>
                </GrowthHabits>
                */
                return @$obj->GrowthHabits; //return an array value
            }
        }
        else {
            echo("\nCannot access: $url. Will try again. Trial no. [$trialNo]\n");
            if($trialNo == 1) self::get_profile_data($url, 2, $needle);
            else return false;
        }
    }
    function parse_profile_page($url, $trialNo = 1)
    {   $final = false;
        if($trialNo == 1) $options = $this->download_options;
        else {
            $options = $this->download_options;
            $options['expire_seconds'] = 0;
        }
        if($json = Functions::lookup_with_cache($url, $options)) {
            $obj = json_decode($json);
            // print_r($obj->NativeStatuses); //exit;
            /*
            Array(
                [0] => stdClass Object(
                        [Region] => L48
                        [Status] => I
                        [Type] => Introduced
                    )
                [1] => stdClass Object(
                        [Region] => PR
                        [Status] => I
                        [Type] => Introduced
                    )
            */
            $final = array();
            if($obj->NativeStatuses) {
                foreach($obj->NativeStatuses as $o) $final[] = array($o->Region, $o->Status);
            }
            else {
                $this->debug['no NativeStatuses data'][$url] = '';
                /* works but, not needed. Since at this point there is really no Native data for this taxon.
                echo("\nInvestigate $url status not found! Trial no. [$trialNo]\n");
                if($trialNo == 1) self::parse_profile_page($url, 2);
                else return false;
                */
            }
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