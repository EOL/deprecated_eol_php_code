<?php
namespace php_active_record;
/* connector: [called from DwCA_Utility.php, which is called from fill_up_undefined_parents.php for wikidata-hierarchy] */
class FillUpMissingParentsAPI
{
    function __construct($archive_builder, $resource_id)
    {
        $this->resource_id = $resource_id;
        $this->archive_builder = $archive_builder;
        $this->download_options = array('cache' => 1, 'resource_id' => $resource_id, 'expire_seconds' => 60*60*24*30*1, 'download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1);
    }
    /*================================================================= STARTS HERE ======================================================================*/
    function start($info)
    {
        require_library('connectors/WikiHTMLAPI');
        require_library('connectors/WikipediaAPI');
        require_library('connectors/WikiDataAPI');
        $langs_with_multiple_connectors = array();  //params in WikiDataAPI.php
        $debug_taxon = false;                       //params in WikiDataAPI.php
        $this->func = new WikiDataAPI(false, "en", "taxonomy", $langs_with_multiple_connectors, $debug_taxon, $this->archive_builder); //this was copied from wikidata.php
        
        // /*
        $tables = $info['harvester']->tables;
        self::process_table($tables['http://rs.tdwg.org/dwc/terms/taxon'][0], 'create_archive');
        if($undefined_parents = self::get_undefined_parents()) {
            self::append_undefined_parents($undefined_parents);
        }
        // */
        
        /* testing...
        $undefined_parents = array("Q102318370", "Q27661141", "Q59153571", "Q5226073", "Q60792312");
        $undefined_parents = array("Q140");
        $undefined_parents = array("Q3018678"); // a sample of Wikidata redirect e.g. goes to Q2780905
        $undefined_parents = array("Q21367139");
        $undefined_parents = array("Q106564172");
        self::append_undefined_parents($undefined_parents);
        */
    }
    private function append_undefined_parents($undefined_parents)
    {
        foreach($undefined_parents as $undefined_id) {
            $obj = $this->func->get_object($undefined_id);
            
            // /* New: a redirect by Wikidata --- use the redirect_id instead
            $keys = array_keys((array) $obj->entities);
            // print_r($keys); //exit;
            $redirect_id = $keys[0];
            if($redirect_id != $undefined_id) {
                $undefined_id = $redirect_id;
                $obj = $this->func->get_object($undefined_id);
            }
            // */
            
            // print_r($obj); exit;
            // print_r($obj->entities->$undefined_id->claims); exit;
            $claims = $obj->entities->$undefined_id->claims;
            $rek = array();
            $rek['taxon_id'] = $undefined_id;
            $rek['taxon'] = $this->func->get_taxon_name($obj->entities->$undefined_id); //echo "\nrank OK";
            $rek['rank'] = $this->func->get_taxon_rank($claims); //echo "\nrank OK";
            $rek['author'] = $this->func->get_authorship($claims); //echo "\nauthorship OK";
            $rek['author_yr'] = $this->func->get_authorship_date($claims); //echo "\nauthorship_date OK";
            $tmp = $this->func->get_taxon_parent($claims, $rek['taxon_id']); //complete with all ancestry - parent, grandparent, etc. to -> Biota
            // $rek['parent'] = $tmp['id'];
            $rek['parent'] = $tmp;
            // print_r($rek); exit("\n-stop muna-\n");
            /*Array(
                [taxon_id] => Q140
                [taxon] => Panthera leo
                [rank] => species
                [author] => Carl Linnaeus
                [author_yr] => +1758-01-01T00:00:00Z
                [parent] => Q127960 --- now a complete ancestry
            )*/
            if($rek['taxon_id']) self::create_archive($rek);
        }//end foreach()
    }
    private function get_undefined_parents()
    {
        require_library('connectors/DWCADiagnoseAPI');
        $func = new DWCADiagnoseAPI();
        $url = CONTENT_RESOURCE_LOCAL_PATH . $this->resource_id."_working" . "/taxon_working.tab";
        $suggested_fields = explode("\t", "taxonID	source	parentNameUsageID	scientificName	taxonRank	scientificNameAuthorship");
        if($undefined = $func->check_if_all_parents_have_entries($this->resource_id, true, $url, $suggested_fields)) { //2nd param True means write to text file
            print_r($undefined);
            echo("\nUndefined: ".count($undefined)."\n");
            return $undefined;
        }
        // exit("\ndid not detect undefined parents\n");
    }
    private function process_table($meta, $what)
    {   //print_r($meta);
        echo "\nprocess: [$what]...\n"; $i = 0;
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
            /*Array( e.g. wikidata-hierarchy data
                [http://rs.tdwg.org/dwc/terms/taxonID] => Q140
                [http://purl.org/dc/terms/source] => https://www.wikidata.org/wiki/Q140
                [http://rs.tdwg.org/dwc/terms/parentNameUsageID] => Q127960
                [http://rs.tdwg.org/dwc/terms/scientificName] => Panthera leo
                [http://rs.tdwg.org/dwc/terms/taxonRank] => species
                [http://rs.tdwg.org/dwc/terms/scientificNameAuthorship] => Carl Linnaeus, 1758
            )*/
            if($what == 'create_archive') {
                $uris = array_keys($rec);
                $o = new \eol_schema\Taxon();
                foreach($uris as $uri) {
                    $field = pathinfo($uri, PATHINFO_BASENAME);
                    $o->$field = $rec[$uri];
                }
                if(!isset($this->taxon_ids[$o->taxonID])) {
                    $this->taxon_ids[$o->taxonID] = '';
                    $this->archive_builder->write_object_to_file($o);
                }
            }
            // if($i >= 10) break; //debug only
        }
    }
    private function create_archive($rec)
    {
        if(!@$rec['taxon']) {
            echo "\nWas not added: "; print_r($rec);
            return;
        }
        $t = new \eol_schema\Taxon();
        $t->taxonID                  = $rec['taxon_id'];
        $t->scientificName           = $rec['taxon'];
        if($t->scientificNameAuthorship = $rec['author']) {
            if($year = $rec['author_yr']) {
                //+1831-01-01T00:00:00Z
                $year = substr($year,1,4);
                $t->scientificNameAuthorship .= ", $year";
            }
        }
        $t->taxonRank                = $rec['rank'];
        $t->parentNameUsageID        = $rec['parent']['id'];
        $t->source = "https://www.wikidata.org/wiki/".$t->taxonID;
        if(!isset($this->taxon_ids[$t->taxonID])) {
            $this->taxon_ids[$t->taxonID] = '';
            $this->archive_builder->write_object_to_file($t);
        }
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
            } //print_r($rec); exit;
            /**/
            //===========================================================================================================================================================
            /*For all records with measurementType
            http://purl.obolibrary.org/obo/VT_0001256
            http://purl.obolibrary.org/obo/VT_0001259
            http://www.wikidata.org/entity/Q245097
            Please add a lifestage item (I suggest a column in MoF) with lifestage=http://www.ebi.ac.uk/efo/EFO_0001272
            Thanks!
            */
            $sought_mtypes = array('http://purl.obolibrary.org/obo/VT_0001256', 'http://purl.obolibrary.org/obo/VT_0001259', 'http://www.wikidata.org/entity/Q245097');
            $mtype = $rec['http://rs.tdwg.org/dwc/terms/measurementType'];
            $lifeStage = '';
            if(in_array($mtype, $sought_mtypes)) $lifeStage = 'http://www.ebi.ac.uk/efo/EFO_0001272';
            $rec['http://rs.tdwg.org/dwc/terms/lifeStage'] = $lifeStage;
            //===========================================================================================================================================================
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
            
            $o->measurementID = Functions::generate_measurementID($o, $this->resource_id);
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
                ...
            )*/
            $uris = array_keys($rec);
            $uris = array('http://rs.tdwg.org/dwc/terms/occurrenceID', 'http://rs.tdwg.org/dwc/terms/taxonID');
            $o = new \eol_schema\Occurrence_specific();
            foreach($uris as $uri) {
                $field = pathinfo($uri, PATHINFO_BASENAME);
                $o->$field = $rec[$uri];
            }
            $this->archive_builder->write_object_to_file($o);
            // if($i >= 10) break; //debug only
        }
    }
    /*================================================================= ENDS HERE ======================================================================*/
}
?>