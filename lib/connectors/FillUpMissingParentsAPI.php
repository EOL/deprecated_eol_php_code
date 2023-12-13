<?php
namespace php_active_record;
/* connector: [called from DwCA_Utility.php, which is called from fill_up_undefined_parents.php for wikidata-hierarchy] */
class FillUpMissingParentsAPI
{
    function __construct($archive_builder, $resource_id, $archive_path)
    {
        $this->resource_id = $resource_id;
        $this->archive_builder = $archive_builder;
        $this->archive_path = $archive_path;
        // $this->download_options = array('cache' => 1, 'resource_id' => $resource_id, 'expire_seconds' => 60*60*24*30*1, 'download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1);
        $this->redirected_IDs = array();
        // /* for gnfinder
        if(Functions::is_production()) $this->json_path = '/var/www/html/gnfinder/'; //--- for terminal //'/html/gnfinder/'; --- for Jenkins
        else                           $this->json_path = '/Volumes/AKiTiO4/other_files/gnfinder/';
        // */
    }
    /*================================================================= STARTS HERE ======================================================================*/
    function start($info)
    {
        /* Steps:
        1. extract wikidata-hierarchy.tar.gz to a temp
        2. read taxon.tab from temp and generate the undefined_parents list
        3. now add to archive the undefined_parents
        4. now add the original taxon.tab, with implementation of $this->redirected_IDs
        5. check again check_if_all_parents_have_entries() --- this must be zero records
        */
        
        require_library('connectors/WikiHTMLAPI');
        require_library('connectors/WikipediaAPI');
        require_library('connectors/WikiDataAPI');
        $langs_with_multiple_connectors = array();  //params in WikiDataAPI.php
        $debug_taxon = false;                       //params in WikiDataAPI.php
        $this->func = new WikiDataAPI(false, "en", "taxonomy", $langs_with_multiple_connectors, $debug_taxon, $this->archive_builder); //this was copied from wikidata.php
        
        // /*
        if($tables = @$info['harvester']->tables) print_r(array_keys($tables));
        else {
            echo "\nInvestigate: harvester-tables are not accessbile\n";
            return;
        }
        if($undefined_parents = self::get_undefined_parents_v2()) {
            /* or at this point you can add_2undefined_parents_their_parents(), if needed */
            $no_label_defined = self::append_undefined_parents($undefined_parents);
            self::process_table($tables['http://rs.tdwg.org/dwc/terms/taxon'][0], 'create_archive', $no_label_defined);
        }
        else { //no undefined parents
            self::process_table($tables['http://rs.tdwg.org/dwc/terms/taxon'][0], 'create_archive');
        }
        // */
        
        /* start customize */
        // if($this->resource_id == 'wikipedia-war') {
            if($meta_doc = @$tables['http://eol.org/schema/media/document'][0]) self::carry_over($meta_doc, 'document'); //now available for all resources not just the big ones like war, etc.
        // }
        /* end customize */
        
        
        /* testing...
        $undefined_parents = array("Q102318370", "Q27661141", "Q59153571", "Q5226073", "Q60792312");
        $undefined_parents = array("Q140");
        // $undefined_parents = array("Q3018678"); // a sample of Wikidata redirect e.g. goes to Q2780905
        // $undefined_parents = array("Q21367139");
        // $undefined_parents = array("Q7239"); //not 'instance of taxon'
        self::append_undefined_parents($undefined_parents);
        */
    }
    private function get_undefined_parents_v2() //working OK
    {
        require_library('connectors/DWCADiagnoseAPI');
        $func = new DWCADiagnoseAPI();
        $url = $this->archive_path . "/taxon.tab";
        // echo "\n====Will read this path: [$url]\n";
        if($undefined = $func->check_if_all_parents_have_entries($this->resource_id, true, $url)) { //2nd param True means write to text file
            // print_r($undefined);
            echo("\nUndefined v2: ".count($undefined)."\n"); //exit;
            return $undefined;
        }
        // exit("\ndid not detect undefined parents\n");
    }
    private function append_undefined_parents($undefined_parents)
    {
        $to_be_added = array('Q21032607', 'Q68334453', 'Q14476748', 'Q21032613', 'Q2116552'); //last remaining undefined parents. Added here to save one entire loop
        $undefined_parents = array_merge($undefined_parents, $to_be_added);
        $no_label_defined = array();
        foreach($undefined_parents as $undefined_id) {
            $obj = $this->func->get_object($undefined_id);
            
            // /* New: a redirect by Wikidata --- use the redirect_id instead
            $keys = array_keys((array) $obj->entities);
            // print_r($keys); //exit;
            $redirect_id = $keys[0];
            if($redirect_id != $undefined_id) { $this->redirected_IDs[$undefined_id] = $redirect_id; //to be used later
                $undefined_id = $redirect_id;
                $obj = $this->func->get_object($undefined_id);
            }
            // */
            
            // print_r($obj); exit;
            // print_r($obj->entities->$undefined_id->claims); exit;
            $claims = $obj->entities->$undefined_id->claims;
            $rek = array();
            $rek['taxon_id'] = $undefined_id;
            $rek['taxon'] = $this->func->get_taxon_name($obj->entities->$undefined_id, 'REQUIRED'); //echo "\nrank OK";
            $rek['rank'] = $this->func->get_taxon_rank($claims); //echo "\nrank OK";
            $rek['author'] = $this->func->get_authorship($claims); //echo "\nauthorship OK";
            $rek['author_yr'] = $this->func->get_authorship_date($claims); //echo "\nauthorship_date OK";
            $tmp = $this->func->get_taxon_parent($claims, $rek['taxon_id']); //complete with all ancestry - parent, grandparent, etc. to -> Biota
            // $rek['parent'] = $tmp['id'];
            $rek['parent'] = $tmp;
            $rek['instance_of'] = (string) @$obj->entities->$undefined_id->claims->P31[0]->mainsnak->datavalue->value->id; //just metadata, not used for now
            // $this->func->lookup_value($undefined_id, 'instance_of'); --- working but doesn't need to make another call
            // print_r($rek); exit("\n-stop muna-\n");
            /*Array(
                [taxon_id] => Q140
                [taxon] => Panthera leo
                [rank] => species
                [author] => Carl Linnaeus
                [author_yr] => +1758-01-01T00:00:00Z
                [parent] => Q127960 --- now a complete ancestry
            )*/
            if($scientificName = $rek['taxon']) {
                // /* New: Feb 16, 2022
                $rek['canonicalName'] = self::add_cannocial_using_gnparser($scientificName, $rek['rank']);
                // */
                self::create_archive($rek);
            }
            else {
                echo "\nWas not added: "; print_r($rek);
                $no_label_defined[$rek['taxon_id']] = ''; //e.g. Q111551242
            }
        }//end foreach()
        return array_keys($no_label_defined);
    }
    /* working OK - an option to get a taxon.tab that is a "taxon_working.tab"
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
    } */
    private function process_table($meta, $what, $no_label_defined = array())
    {   //print_r($meta);
        echo "\nprocess_table: [$what] [$meta->file_uri]...\n"; $i = 0;
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
                
                // /* implement redirect ID
                $taxonID           = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
                $parentNameUsageID = $rec['http://rs.tdwg.org/dwc/terms/parentNameUsageID'];
                if($val = @$this->redirected_IDs[$taxonID])           $rec['http://rs.tdwg.org/dwc/terms/taxonID'] = $val;
                if($val = @$this->redirected_IDs[$parentNameUsageID]) $rec['http://rs.tdwg.org/dwc/terms/parentNameUsageID'] = $val;
                // */
                
                // /* temporary fix until wikidata dump has reflected my edits in wikidata
                $taxonID = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
                if($taxonID == "Q107694904")    $rec['http://rs.tdwg.org/dwc/terms/scientificName'] = "Trachipleistophora";
                if($taxonID == "Q15657618")     $rec['http://rs.tdwg.org/dwc/terms/scientificName'] = "Hemaris thetis";
                // */
                
                // /* New: Feb 16, 2022
                if(!@$rec['http://rs.gbif.org/terms/1.0/canonicalName']) {
                    $scientificName = $rec['http://rs.tdwg.org/dwc/terms/scientificName'];
                    $rank = $rec['http://rs.tdwg.org/dwc/terms/taxonRank'];
                    $rec['http://rs.gbif.org/terms/1.0/canonicalName'] = self::add_cannocial_using_gnparser($scientificName, $rank);
                }
                // */
                
                // /*
                if($no_label_defined) {
                    if(in_array($rec['http://rs.tdwg.org/dwc/terms/parentNameUsageID'], $no_label_defined)) $rec['http://rs.tdwg.org/dwc/terms/parentNameUsageID'] = '';
                }
                // */
                
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
        $t = new \eol_schema\Taxon();
        $t->taxonID                  = $rec['taxon_id'];
        $t->scientificName           = $rec['taxon'];
        $t->canonicalName            = $rec['canonicalName'];
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
    function add_cannocial_using_gnparser($scientificName, $rank)
    {
        if($this->resource_id != "wikidata-hierarchy-final") return "";
        $md5_id = md5($scientificName.$rank);
        if($obj = self::retrieve_json_obj($md5_id)) {} //{echo "\nobj retrieved\n";}
        else {
            // gnparser "Sarracenia flava 'Maxima'" -f pretty -C
            // $cmd = 'gnparser "'.$scientificName.'" -f compact -C > terminal_gnparser_wikidataTaxonomy.out'; //working OK, for testing
            $cmd = 'gnparser "'.$scientificName.'" -f compact -C';
            // echo "\nrunning: [$cmd]\n";
            $json = shell_exec($cmd);
            $obj = json_decode($json);
            // /* for json object saving/retrieval routine
            self::save_json($md5_id, $json);
            // */
        }
        // print_r($obj); exit;
        /*stdClass Object(
            [parsed] => 1
            [quality] => 1
            [verbatim] => Sarracenia flava 'Maxima'
            [normalized] => Sarracenia flava ‘Maxima’
            [canonical] => stdClass Object(
                    [stemmed] => Sarracenia flau ‘Maxima’
                    [simple] => Sarracenia flava ‘Maxima’
                    [full] => Sarracenia flava ‘Maxima’
                )
            [cardinality] => 3
            [id] => 39178008-65ee-5de3-af88-63ffdd67e00b
            [parserVersion] => v1.6.3
        )
        1. Could you please run the scientificName values through gnparser and put the result in a canonicalName column? 
        This will make it easier for me to work on the page mappings. Please turn on the option to parse cultivars (--cultivars -C). 
        Fetch the full canonical form for taxa of rank subgenus, series, subseries, section, and subsection. 
        Fetch the simple canonical form for all other taxa. 
        For taxa that don't get parsed, please put the scientificName value in the canonicalName column. */
        if(@$obj->parsed == 1) {
            if(in_array($rank, array("subgenus", "series", "subseries", "section", "subsection"))) return self::remove_special_quotes($obj->canonical->full);
            else return self::remove_special_quotes($obj->canonical->simple);
        }
        else return $scientificName;
    }
    private function remove_special_quotes($str)
    {
        return $str;
        return str_replace(array("‘", "’"), "'", $str);
    }
    /* ------------- START: retrieve module ------------- */
    private function retrieve_json_obj($id)
    {   $file = self::retrieve_path($id);
        if(is_file($file)) {
            $json = file_get_contents($file);
            return json_decode($json);
        }
        return false;
    }
    private function retrieve_path($md5)
    {   $filename = "$md5.json";
        $cache1 = substr($md5, 0, 2);
        $cache2 = substr($md5, 2, 2);
        return $this->json_path . "$cache1/$cache2/$filename";
    }
    private function save_json($id, $json)
    {   $file = self::build_path($id);
        if($f = Functions::file_open($file, "w")) {
            fwrite($f, $json);
            fclose($f);
        }
        else exit("\nCannot write file\n");
    }
    private function build_path($md5)
    {
        $filename = "$md5.json";
        $cache1 = substr($md5, 0, 2);
        $cache2 = substr($md5, 2, 2);
        if(!file_exists($this->json_path . $cache1)) mkdir($this->json_path . $cache1);
        if(!file_exists($this->json_path . "$cache1/$cache2")) mkdir($this->json_path . "$cache1/$cache2");
        return $this->json_path . "$cache1/$cache2/$filename";
    }
    /* ------------- END: retrieve module ------------- */
    
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
    private function carry_over($meta, $class)
    {   //print_r($meta);
        echo "\ncarry_over...[$class][$meta->file_uri]\n"; $i = 0;
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
            $uris = array_keys($rec);
            
            if    ($class == "vernacular")          $o = new \eol_schema\VernacularName();
            elseif($class == "agent")               $o = new \eol_schema\Agent();
            elseif($class == "reference")           $o = new \eol_schema\Reference();
            elseif($class == "taxon")               $o = new \eol_schema\Taxon();
            elseif($class == "document")            $o = new \eol_schema\MediaResource();
            elseif($class == "occurrence")          $o = new \eol_schema\Occurrence();
            elseif($class == "occurrence_specific") $o = new \eol_schema\Occurrence_specific(); //1st client is 10088_5097_ENV
            elseif($class == "measurementorfact")   $o = new \eol_schema\MeasurementOrFact();
            else exit("\nUndefined class [$class]. Will terminate.\n");
            
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