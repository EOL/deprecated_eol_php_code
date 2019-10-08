<?php
namespace php_active_record;
/* connector: [called from DwCA_Utility.php, which is called from brazilian_flora.php for DATA-xxx|email with Jen] */
class BrazilianFloraAPI
{
    function __construct($archive_builder, $resource_id)
    {
        $this->resource_id = $resource_id;
        $this->archive_builder = $archive_builder;
        $this->download_options = array('cache' => 1, 'resource_id' => $resource_id, 'expire_seconds' => 60*60*24*30*4, 'download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1);
        // $this->download_options['expire_seconds'] = false; //comment after first harvest
    }
    /*================================================================= STARTS HERE ======================================================================*/
    /* 
    The occurrences file can be constructed as a 1->1 with no additional information.

    The distribution and speciesprofile files can both go to the measurementsOrFacts file. Distribution will need a slightly convoluted mapping:

    locationID will be used for measurementValue
    countryCode can be ignored

    measurementType is determined by establishmentMeans:

    NATIVA-> http://eol.org/schema/terms/NativeRange
    CULTIVADA-> http://eol.org/schema/terms/IntroducedRange
    NATURALIZADA-> http://eol.org/schema/terms/IntroducedRange
    unless the string "endemism":"Endemica" appears in occurrenceRemarks, in which case the measurementType is http://eol.org/terms/endemic

    The strings CULTIVADA and NATURALIZADA should be preserved in measurementRemarks

    occurrenceRemarks also contains another section, beginning "phytogeographicDomain": and followed by comma separated strings in square brackets. 
    Each string will also be a measurementValue and should get an additional record with the same measurementType, occurrence, etc.

    wrinkle: where measurementType is http://eol.org/terms/endemic for the original records, http://eol.org/schema/terms/NativeRange should be used for any accompanying records 
        based on the occurrenceRemarks strings.

    speciesprofile also has a convoluted batch of strings in lifeForm. (habitat seems to be empty for now). There may be up to three sections in each cell, of the form:

    {"measurementType":["measurementValue","measurementValue"],"measurementType":["measurementValue","measurementValue"],"measurementType":["measurementValue","measurementValue"]}

    if that makes it clear...

    measurementTypes:

    lifeForm-> http://purl.obolibrary.org/obo/FLOPO_0900022
    habitat-> http://rs.tdwg.org/dwc/terms/habitat
    vegetationType-> http://eol.org/schema/terms/Habitat

    I'll make you a mapping for all the measurementValue strings from both files.
    
    */
    function start($info)
    {   $tables = $info['harvester']->tables;
        self::process_Taxon($tables['http://rs.tdwg.org/dwc/terms/taxon'][0]);
        self::process_Reference($tables['http://rs.gbif.org/terms/1.0/reference'][0]);
        /*
        self::process_measurementorfact($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0]);
        self::process_occurrence($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0]);
        unset($this->occurrenceID_bodyPart);
        require_library('connectors/TraitGeneric'); $this->func = new TraitGeneric($this->resource_id, $this->archive_builder);
        self::initialize_mapping(); //for location string mappings
        self::process_per_state();
        */
    }
    private function process_Taxon($meta)
    {   //print_r($meta);
        echo "\nprocess_Reference...\n"; $i = 0;
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
        }
    }
    private function process_Reference($meta)
    {   //print_r($meta);
        echo "\nprocess_Reference...\n"; $i = 0;
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
            /*Array(
                [http://rs.tdwg.org/dwc/terms/taxonID] => 264
                [http://purl.org/dc/terms/identifier] => 
                [http://purl.org/dc/terms/bibliographicCitation] => Arch. Jard. Bot. Rio de Janeiro,3: 187,1922
                [http://purl.org/dc/terms/title] => Arch. Jard. Bot. Rio de Janeiro
                [http://purl.org/dc/terms/creator] => 
                [http://purl.org/dc/terms/date] => 1922
                [http://purl.org/dc/terms/type] => 
            )
            The references file is pretty close, although the references are a bit sparse. It looks like the title column is redundant with bibliographicCitation and can be ignored. 
                Could you please concatenate creator, then date, then bibliographicCitation, separated by ". " to make the fullReference?
            */
            //===========================================================================================================================================================
            $fullref = '';
            if($val = $rec['http://purl.org/dc/terms/creator']) $fullref .= "$val. ";
            if($val = $rec['http://purl.org/dc/terms/date']) $fullref .= "$val. ";
            if($val = $rec['http://purl.org/dc/terms/bibliographicCitation']) $fullref .= "$val. ";
            $rec['http://eol.org/schema/reference/full_reference'] = trim($fullref);
            unset($rec['http://purl.org/dc/terms/title']);

            $rec['http://purl.org/dc/terms/identifier'] = md5(json_encode($rec)).'_'.$rec['http://rs.tdwg.org/dwc/terms/taxonID'];
            unset($rec['http://rs.tdwg.org/dwc/terms/taxonID']);
            unset($rec['http://purl.org/dc/terms/bibliographicCitation']);
            unset($rec['http://purl.org/dc/terms/creator']);
            unset($rec['http://purl.org/dc/terms/date']);
            unset($rec['http://purl.org/dc/terms/type']);
            
            //===========================================================================================================================================================
            $uris = array_keys($rec);
            $o = new \eol_schema\Reference();
            foreach($uris as $uri) {
                $field = pathinfo($uri, PATHINFO_BASENAME);
                $o->$field = $rec[$uri];
            }
            $this->archive_builder->write_object_to_file($o);
            if($i >= 10) break; //debug only
        }
    }
    
    //=====================================================ends here
    private function initialize_mapping()
    {   $mappings = Functions::get_eol_defined_uris(false, true);     //1st param: false means will use 1day cache | 2nd param: opposite direction is true
        echo "\n".count($mappings). " - default URIs from EOL registry.";
        $this->uris = Functions::additional_mappings($mappings); //add more mappings used in the past
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
    /*
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
    */
    /*================================================================= ENDS HERE ======================================================================*/
}
?>
