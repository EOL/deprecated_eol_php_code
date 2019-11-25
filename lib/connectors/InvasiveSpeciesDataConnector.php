<?php
namespace php_active_record;
/* connector: [751] [760 now was moved to: InvasiveSpeciesCompendiumAPI.php]
original: DATA-1426 Scrape invasive species data from GISD & CABI ISC
latest ticket: https://eol-jira.bibalex.org/browse/TRAM-794
*/

class InvasiveSpeciesDataConnector
{
    function __construct($folder, $partner)
    {
        $this->resource_id = $folder;
        $this->partner = $partner;
        $this->taxa = array();
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->occurrence_ids = array();
        $this->taxon_ids = array();
        $this->download_options = array('resource_id' => $folder, 'download_wait_time' => 1000000, 'timeout' => 60*2, 'download_attempts' => 1, 'cache' => 1); // 'expire_seconds' => 0
        $this->debug = array();
        // Global Invasive Species Database (GISD)
        $this->taxa_list['GISD'] = "http://localhost/cp_new/GISD/export_gisd.csv";
        $this->taxa_list['GISD'] = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/GISD/export_gisd.csv";
        $this->taxon_page['GISD'] = "http://www.iucngisd.org/gisd/speciesname/";
    }
    function generate_invasiveness_data()
    {
        /* START DATA-1841 terms remapping */
        require_library('connectors/TraitGeneric');
        $this->func = new TraitGeneric(false, false); //params are false and false bec. we just need to access 1 function.
        $this->func->initialize_terms_remapping();
        /* END DATA-1841 terms remapping */
        
        if    ($this->partner == "GISD")     self::start_GISD();
        $this->archive_builder->finalize(TRUE);
        if($this->debug) {
            /* working but not needed, since debug is printed in /resources/xxx_debug.txt
            $arr = array_keys($this->debug['un-mapped string']['location']); asort($arr); $arr = array_values($arr); print_r($arr);
            $arr = array_keys($this->debug['un-mapped string']['habitat']); asort($arr); $arr = array_values($arr); print_r($arr);
            */
            echo "\nun-mapped string location: ".count($this->debug['un-mapped string']['location'])."\n";
            echo "\nun-mapped string habitat: ".count($this->debug['un-mapped string']['habitat'])."\n";
            Functions::start_print_debug($this->debug, $this->resource_id);
        }
    }
    private function get_CABI_taxa()
    {
        $taxa = array(); $count = 0; $total_count = false;
        while(true) {
            if($html = Functions::lookup_with_cache($this->CABI_taxa_list_per_page . $count, $this->download_options)) {
                if(!$total_count) {
                    if(preg_match("/Showing 1 \- 10 of (.*?)<\/div>/ims", $html, $arr)) $total_count = $arr[1];
                    else {
                        echo "\n investigate: cannot access total count...\n";
                        return array();
                    }
                }
                if(preg_match_all("/<td class=\"cabiSearchResultsText\">(.*?)<\/td>/ims", $html, $arr)) {
                    foreach($arr[1] as $row) {
                        $row = str_ireplace("&amp;", "&", $row);
                        $rec = array();
                        if(preg_match("/<a href=\"(.*?)\"/ims", $row, $arr2)) $rec["source"] = $arr2[1];
                        if(preg_match("/dsid=(.*?)\&/ims", $row, $arr2)) $rec["taxon_id"] = $arr2[1];
                        if(preg_match("/class=\"title\">(.*?)<\/a>/ims", $row, $arr2)) {
                            $sciname = $arr2[1];
                            if(preg_match("/\((.*?)\)/ims", $sciname, $arr3)) $rec["vernacular"] = $arr3[1];
                            $rec["sciname"] = trim(preg_replace('/\s*\([^)]*\)/', '', $sciname)); //remove parenthesis
                        }
                        if($rec) {
                            $rec["schema_taxon_id"] = "cabi_" . $rec["taxon_id"];
                            
                            // manual adjustments
                            $rec["sciname"] = trim(str_ireplace(array("[ISC]", "race 2", "race 1", "of oysters", "small colony type", "/maurini of mussels", ")"), "", $rec["sciname"]));
                            if(ctype_lower(substr($rec["sciname"],0,1))) continue;
                            if(self::term_exists_then_exclude_from_list($rec["sciname"], array("honey", "virus", "fever", "Railways", "infections", " group", "Soil", "Hedges", "Digestion", "Clothing", "production", "Forestry", "Habitat", "plants", "complex", "viral", "disease", "large"))) continue;
                            
                            $taxa[] = $rec;
                        }
                    }
                    if(count($taxa) >= $total_count) break;
                }
                else break; // assumed that connector has gone to all pages already, exits while()
            }
            $count = $count + 10;
            // if($count >= 50) break;//debug - use using preview phase
        }
        return $taxa;
    }
    private function term_exists_then_exclude_from_list($string, $terms)
    {
        foreach($terms as $term) {
            if(is_numeric(stripos($string, $term))) return true;
        }
        return false;
    }
    private function parse_references($ref)
    {
        $refs = array();
        if(preg_match("/aspx\?PAN=(.*?)\'/ims", $ref, $arr)) {
            $ref_ids = explode("|", $arr[1]);
            foreach($ref_ids as $id) {
                $refs[] = $id; // to be used in MeasurementOrFact
                if(!isset($this->CABI_references[$id])) { // doesn't exist yet, scrape and save ref
                    if($html = Functions::lookup_with_cache($this->CABI_ref_page . $id, $this->download_options)) {
                        if(preg_match("/<div id=\"refText\" align=\"left\">(.*?)<\/div>/ims", $html, $arr)) self::add_reference($id, $arr[1], $this->CABI_ref_page . $id);
                    }
                }
            }
        }
        return $refs;
    }
    private function add_reference($id, $full_reference, $uri)
    {
        $r = new \eol_schema\Reference();
        $r->identifier = $id;
        $r->full_reference = $full_reference;
        $r->uri = $uri;
        $this->archive_builder->write_object_to_file($r);
        $this->CABI_references[$id] = 1;
    }
    private function start_GISD()
    {
        $mappings = Functions::get_eol_defined_uris(false, true); //1st param: false means will use 1day cache | 2nd param: opposite direction is true
        echo "\n".count($mappings). " - default URIs from EOL registry.";
        $this->uri_values = Functions::additional_mappings($mappings); //add more mappings used in the past
        // print_r($this->uri_values);
        // exit("\nstopx\n");
        
        $csv_file = Functions::save_remote_file_to_local($this->taxa_list['GISD'], $this->download_options);
        $file = Functions::file_open($csv_file, "r");
        $i = 0;
        while(!feof($file)) {
            $i++;
            if(($i % 100) == 0) echo "\n count:[$i] ";
            
            $row = fgetcsv($file);
            $row = str_replace('"', "", $row[0]);
            if($i == 1) $fields = explode(";", $row);
            else {
                $vals = explode(";", $row);
                if(!$vals[0]) continue;
                $k = -1; $rec = array();
                foreach($fields as $field) {
                    $k++;
                    $rec[$field] = $vals[$k];
                }
                if($rec['Species']) {
                    $url = $this->taxon_page['GISD'].urlencode($rec['Species']);
                    if($html = Functions::lookup_with_cache($url, $this->download_options)) {
                        if(preg_match("/pdf.php\?sc=(.*?)\"/ims", $html, $arr)) { // d/pdf.php?sc=15">Ful
                            $rec['taxon_id'] = $arr[1];
                        }
                        else exit("\nInvestigate 01 ".$rec['Species']."\n");
                        $rec['alien_range'] = self::get_alien_range($html);
                        $rec['native_range'] = self::get_native_range($html);
                        
                        $alien = array(); $native = array();
                        if($val = $rec['alien_range']) $alien = $val;
                        if($val = $rec['native_range']) $native = $val;
                        /* working but excluded
                        $rec['present'] = array_merge($alien, $native);
                        $rec['present'] = array_unique($rec['present']);
                        */
                        $rec['source_url'] = $url;
                        $rec = self::get_citation_and_others($html, $rec);
                    }
                    // print_r($rec);
                    if($rec['Species'] && ($rec['alien_range'] || $rec['native_range'])) {
                        $this->create_instances_from_taxon_object($rec);
                        $this->process_GISD_distribution($rec);
                    }
                    // break; //debug only
                    /* good debug
                    if(!$rec['alien_range']) exit("\nInvestigate no alien range ".$rec['Species']."\n");
                    if(!in_array($rec['id'], array(1343, 192, 1043, 1248))) {
                        if(!$rec['native_range']) exit("\nInvestigate no native range ".$rec['Species']."\n");
                    }
                    */
                }
            }
        }
        unlink($csv_file);
        // exit;
    }
    private function get_alien_range($html)
    {
        if(preg_match("/ALIEN RANGE<\/div>(.*?)<\/div>/ims", $html, $arr)) {
            if(preg_match_all("/class=\"(.*?)\"/ims", $arr[1], $arr2)) {
                $final = self::capitalize_first_letter_of_country_names($arr2[1]);
                return $final;
            }
        }
    }
    private function get_native_range($html)
    {
        if(preg_match("/NATIVE RANGE<\/div>(.*?)<\/div>/ims", $html, $arr)) {
            if(preg_match_all("/<li>(.*?)<\/li>/ims", $arr[1], $arr2)) {
                $final = self::capitalize_first_letter_of_country_names($arr2[1]);
                return $final;
            }
        }
    }
    private function capitalize_first_letter_of_country_names($names)
    {
        $final = array();
        foreach($names as $name) {
            $name = str_replace(array("\n", "\t"), "", $name);
            $name = trim(strtolower($name));
            $name = Functions::remove_whitespace($name);
            $tmp = explode(" ", $name);
            $tmp = array_map('ucfirst', $tmp);
            $tmp = array_map('trim', $tmp);
            $final[] = implode(" ", $tmp);
        }
        return $final;
    }
    private function get_citation_and_others($html, $rec)
    {
        // <p><strong>Recommended citation:</strong> Global Invasive Species Database (2018) Species profile: <i>Anopheles quadrimaculatus</i>. Downloaded from http://www.iucngisd.org/gisd/speciesname/Anopheles%20quadrimaculatus on 18-07-2018.</p>
        if(preg_match("/Recommended citation\:(.*?)<\/p>/ims", $html, $arr)) {
            $str = strip_tags($arr[1], "<i>");
            $rec['bibliographicCitation'] = trim($str);
        }
        // <p><strong>Principal source:</strong> <a href=\"http://www.hear.org/pier/species/abelmoschus_moschatus.htm\"> PIER, 2003. (Pacific Island Ecosystems At Risk) <i>Abelmoschus moschatus</i></a></p>
        if(preg_match("/Principal source\:(.*?)<\/p>/ims", $html, $arr)) {
            $str = strip_tags($arr[1], "<i>");
            $rec['Principal source'] = trim($str);
        }
        // <p><strong>Compiler:</strong> IUCN/SSC Invasive Species Specialist Group (ISSG)</p>
        if(preg_match("/Compiler\:(.*?)<\/p>/ims", $html, $arr)) {
            $str = strip_tags($arr[1], "<i>");
            $rec['Compiler'] = trim($str);
        }
        $rem = "";
        if($val = $rec['Principal source']) $rem .= "Principal source: ".$val.". ";
        if($val = $rec['Compiler']) $rem .= "Compiler: ".$val.". ";
        $rec['measurementRemarks'] = Functions::remove_whitespace(trim($rem));
        return $rec;
    }
    private function create_instances_from_taxon_object($rec)
    {
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID         = $rec["taxon_id"];
        $taxon->scientificName  = $rec["Species"];
        $taxon->kingdom         = $rec['Kingdom'];
        $taxon->phylum          = $rec['Phylum'];
        $taxon->class           = $rec['Class'];
        $taxon->order           = $rec['Order'];
        $taxon->family          = $rec['Family'];
        $taxon->furtherInformationURL = $rec["source_url"];
        if(!isset($this->taxon_ids[$taxon->taxonID])) {
            $this->archive_builder->write_object_to_file($taxon);
            $this->taxon_ids[$taxon->taxonID] = '';
        }
    }
    private function process_GISD_distribution($rec)
    {
        // /*
        if($locations = @$rec["alien_range"]) {
            foreach($locations as $location) {
                $rec["catnum"] = "alien_" . str_replace(" ", "_", $location);
                self::add_string_types("true", $rec, "Alien Range", self::get_value_uri($location, 'location'), "http://eol.org/schema/terms/IntroducedRange", array(), $location);
                // if($val = $rec["Species"])                  self::add_string_types(null, $rec, "Scientific name", $val, "http://rs.tdwg.org/dwc/terms/scientificName");
                /* now moved to the main record
                if($val = $rec["bibliographicCitation"])    self::add_string_types(null, $rec, "Citation", $val, "http://purl.org/dc/terms/bibliographicCitation");
                */
            }
        }
        if($locations = @$rec["native_range"]) {
            foreach($locations as $location) {
                $rec["catnum"] = "native_" . str_replace(" ", "_", $location);
                self::add_string_types("true", $rec, "Native Range", self::get_value_uri($location, 'location'), "http://eol.org/schema/terms/NativeRange", array(), $location);
                // if($val = $rec["Species"])                  self::add_string_types(null, $rec, "Scientific name", $val, "http://rs.tdwg.org/dwc/terms/scientificName");
                /* now moved to the main record
                if($val = $rec["bibliographicCitation"])    self::add_string_types(null, $rec, "Citation", $val, "http://purl.org/dc/terms/bibliographicCitation");
                */
            }
        }
        // */

        /* working but excluded
        //newly added July 24, 2018
        if($locations = @$rec["present"]) {
            foreach($locations as $location) {
                $rec["catnum"] = "present_" . str_replace(" ", "_", $location);
                self::add_string_types("true", $rec, "Presence", self::get_value_uri($location, 'location'), "http://eol.org/schema/terms/Present", array(), $location);
            }
        }
        */
        
        if($habitat = strtolower(@$rec["System"])) {
            $rec["catnum"] = str_replace(" ", "_", $habitat);
            if($uri = self::get_value_uri($habitat, 'habitat')) {
                self::add_string_types("true", $rec, "Habitat", $uri, "http://eol.org/schema/terms/Habitat", array(), $habitat);
                /* now moved to the main record
                if($val = $rec["bibliographicCitation"]) self::add_string_types(null, $rec, "Citation", $val, "http://purl.org/dc/terms/bibliographicCitation");
                */
            }
        }
        
    }
    private function add_string_types($measurementOfTaxon, $rec, $label, $value, $mtype, $reference_ids = array(), $orig_value = "")
    {
        $taxon_id = $rec["taxon_id"];
        $catnum = $rec["catnum"];
        $m = new \eol_schema\MeasurementOrFact();
        $occurrence = $this->add_occurrence($taxon_id, $catnum, $rec);
        $m->occurrenceID = $occurrence->occurrenceID;
        $m->measurementType = $mtype;
        $m->measurementValue = $value;
        if($val = $measurementOfTaxon) {
            $m->measurementOfTaxon = $val;
            $m->source = $rec["source_url"];
            if($reference_ids) $m->referenceID = implode("; ", $reference_ids);
            $m->bibliographicCitation = $rec['bibliographicCitation']; //now added to the main record
            $m->measurementRemarks = "";
            if($orig_value) $m->measurementRemarks = ucfirst($orig_value).". ";
            $m->measurementRemarks .= $rec['measurementRemarks'];
            // $m->contributor = ''; $m->measurementMethod = '';
        }
        
        /* START DATA-1841 terms remapping */
        $m = $this->func->given_m_update_mType_mValue($m);
        /* END DATA-1841 terms remapping */
        
        $m->measurementID = Functions::generate_measurementID($m, $this->resource_id);
        $this->archive_builder->write_object_to_file($m);
    }
    private function add_occurrence($taxon_id, $catnum, $rec)
    {
        $occurrence_id = md5($taxon_id . '_' . $catnum);
        if(isset($this->occurrence_ids[$occurrence_id])) return $this->occurrence_ids[$occurrence_id];
        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = $occurrence_id;
        $o->taxonID = $taxon_id;
        $this->archive_builder->write_object_to_file($o);
        $this->occurrence_ids[$occurrence_id] = $o;
        return $o;
    }
    private function get_value_uri($string, $type)
    {
        if($val = @$this->uri_values[$string]) return $val;
        else {
            switch ($string) { //others were added in https://raw.githubusercontent.com/eliagbayani/EOL-connector-data-files/master/GISD/mapped_location_strings.txt
                case "brackish":                      return "http://purl.obolibrary.org/obo/ENVO_00000570";
                case "marine_freshwater_brackish":    return "http://purl.obolibrary.org/obo/ENVO_00002030"; //based here: https://eol-jira.bibalex.org/browse/TRAM-794?focusedCommentId=62690&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-62690
                case "terrestrial_freshwater_marine": return false; //skip based here: https://eol-jira.bibalex.org/browse/TRAM-794?focusedCommentId=62690&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-62690
            }
            $this->debug['un-mapped string'][$type][$string] = '';
            if($type == 'habitat')      return false;
            elseif($type == 'location') return $string;
        }
    }
    /* not used, just for reference
    private function format_habitat($desc)
    {
        $desc = trim(strtolower($desc));
        elseif($desc == "marine/freshwater")        return "http://eol.org/schema/terms/freshwaterAndMarine";
        elseif($desc == "ubiquitous")               return "http://eol.org/schema/terms/ubiquitous";
        else {
            echo "\n investigate undefined habitat [$desc]\n";
            return $desc;
        }
    }*/
}
?>