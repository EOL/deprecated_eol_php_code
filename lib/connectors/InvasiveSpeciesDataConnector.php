<?php
namespace php_active_record;
/* connector: [751] [760]
DATA-1426 Scrape invasive species data from GISD & CABI ISC
*/

class InvasiveSpeciesDataConnector
{
    function __construct($folder, $partner)
    {
        $this->taxa = array();
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->occurrence_ids = array();
        $this->taxon_ids = array();
        $this->download_options = array('download_wait_time' => 2000000, 'timeout' => 10800, 'download_attempts' => 1); // 'expire_seconds' => 0
        // Global Invasive Species Database (GISD)
        $this->GISD_portal_by_letter    = "http://www.issg.org/database/species/search.asp?sts=sss&st=sss&fr=1&x=25&y=12&rn=&hci=-1&ei=-1&lang=EN&sn=";
        $this->GISD_taxa_list           = "http://www.issg.org/database/species/List.asp";
        $this->GISD_taxon_distribution  = "http://www.issg.org/database/species/distribution.asp?si=";
        // CABI ISC
        $this->CABI_taxa_list_per_page = "http://www.cabi.org/isc/Default.aspx?site=144&page=4066&sort=meta_released_sort+desc&fromab=&LoadModule=CABISearchResults&profile=38&tab=0&start=";
        $this->CABI_taxon_distribution = "http://www.cabi.org/isc/DatasheetDetailsReports.aspx?&iSectionId=DD*0&sSystem=Product&iPageID=481&iCompendiumId=5&iDatasheetID=";
        $this->CABI_references = array();
        $this->CABI_ref_page = "http://www.cabi.org/isc/references.aspx?PAN=";
        $this->partner = $partner;
    }

    function generate_invasiveness_data()
    {
        if    ($this->partner == "GISD")     self::process_GISD();
        elseif($this->partner == "CABI ISC") self::process_CABI();
        $this->archive_builder->finalize(TRUE);
    }

    private function process_CABI()
    {
        $taxa = self::get_CABI_taxa();
        $total = count($taxa);
        echo "\n taxa count: " . $total . "\n";
        $i = 0;
        foreach($taxa as $taxon) {
            $i++;
            echo "\n $i of $total ";
            if($taxon) {
                $info = array();
                $info["taxon_id"] = $taxon["taxon_id"];
                $info["schema_taxon_id"] = $taxon["schema_taxon_id"];
                $info["taxon"]["sciname"] = (string) $taxon["sciname"];
                $info["source"] = $taxon["source"];
                $info["citation"] = "CABI International Invasive Species Compendium, " . date("Y") . ". " . $taxon["sciname"] . ". Available from: " . $taxon["source"] . " [Accessed " . date("M-d-Y") . "].";
                if($this->process_CABI_distribution($info)) $this->create_instances_from_taxon_object($info); // only include names with Nativity or Invasiveness info
            }
        }
    }

    private function get_CABI_taxa()
    {
        $taxa = array();
        $count = 0;
        $total_count = false;
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
    
    private function process_CABI_distribution($rec)
    {
        $has_data = false;
        if($html = Functions::lookup_with_cache($this->CABI_taxon_distribution . $rec["taxon_id"], $this->download_options)) {
            if(preg_match_all("/Helvetica;padding\: 5px\'>(.*?)<\/tr>/ims", $html, $arr)) {
                foreach($arr[1] as $row) {
                    if(preg_match_all("/<td>(.*?)<\/td>/ims", $row, $arr)) {
                        $row = $arr[1];
                        $country = strip_tags(trim($row[0]));
                        if(substr($country,0,1) != "-") {
                            $reference_ids = self::parse_references(trim(@$row[6]));
                            if(@$row[3]) {
                                $has_data = true;
                                self::process_origin_invasive_objects("origin"  , $row[3], $country, $rec, $reference_ids);
                            }
                            if(@$row[5]) {
                                $has_data = true;
                                self::process_origin_invasive_objects("invasive", $row[5], $country, $rec, $reference_ids);
                            }
                        }
                    }
                }
            }
        }
        return $has_data;
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
    
    private function process_origin_invasive_objects($type, $value, $country, $rec, $reference_ids)
    {
        $uri = false;
        $value = strip_tags(trim($value));
        switch($value) {
            case "Introduced":      $uri = "http://eol.org/schema/terms/IntroducedRange"; break;
            case "Invasive":        $uri = "http://eol.org/schema/terms/InvasiveRange"; break;
            case "Native":          $uri = "http://eol.org/schema/terms/NativeRange"; break;
            case "Not invasive":    $uri = "http://eol.org/schema/terms/NonInvasiveRange"; break;
        }
        if(strpos($value, "introduced") === false) {}
        else $uri = "http://eol.org/schema/terms/IntroducedRange";
        if($uri) {
            $rec["catnum"] = $type . "_" . str_replace(" ", "_", $country);
            self::add_string_types("true", $rec, "", $country, $uri, $reference_ids);
            if($val = $rec["taxon"]["sciname"]) self::add_string_types(null, $rec, "Scientific name", $val, "http://rs.tdwg.org/dwc/terms/scientificName");
            if($val = $rec["citation"])         self::add_string_types(null, $rec, "Citation", $val, "http://purl.org/dc/terms/bibliographicCitation");
        }
        else {
            echo "\n investigate no data\n";
            print_r($rec);
        }
    }
    
    private function process_GISD()
    {
        $taxa = self::get_GISD_taxa();
        $total = count($taxa);
        echo "\n taxa count: $total \n";
        $i = 0;
        foreach($taxa as $taxon_id => $taxon) {
            $i++;
            echo "\n $i of $total ";
            // if($i >= 100) return; //debug -- use during preview phase
            $url = $this->GISD_taxon_distribution . $taxon_id;
            if($html = Functions::lookup_with_cache($url, $this->download_options)) {
                $info = array();
                if(preg_match("/<B>Alien Range<\/B>(.*?)<\/ul>/ims", $html, $arr)) {
                    if(preg_match_all("/<span class=\'ListTitle\'>(.*?)<\/span>/ims", $arr[1], $arr2)) $info["Alien Range"]["locations"] = $arr2[1];
                }
                if(preg_match("/<B>Native Range<\/B>(.*?)<\/ul>/ims", $html, $arr)) {
                    if(preg_match_all("/<span class=\'ListTitle\'>(.*?)<\/span>/ims", $arr[1], $arr2)) $info["Native Range"]["locations"] = $arr2[1];
                }
                if($info) {
                    $info["taxon_id"] = $taxon_id;
                    $info["schema_taxon_id"] = $taxon["schema_taxon_id"];
                    $info["taxon"] = $taxon;
                    $info["source"] = $url;
                    $info["citation"] = "Global Invasive Species Database, " . date("Y") . ". " . $taxon["sciname"] . ". Available from: http://www.issg.org/database/species/ecology.asp?si=" . $taxon_id . "&fr=1&sts=sss [Accessed " . date("M-d-Y") . "].";
                    $this->create_instances_from_taxon_object($info);
                    $this->process_GISD_distribution($info);
                }
            }
        }
    }

    private function get_GISD_taxa()
    {
        $taxa = array();
        if($html = Functions::lookup_with_cache($this->GISD_taxa_list, $this->download_options)) {
            if(preg_match_all("/<a href=\'ecology\.asp\?si=(.*?)<\/i>/ims", $html, $arr)) {
                foreach($arr[1] as $temp) {
                    $id = null; $sciname = null;
                    if(preg_match("/(.*?)\&/ims", $temp, $arr2))             $id = $arr2[1];
                    if(preg_match("/<i>(.*?)<\/i>/ims", $temp . "</i>", $arr2)) $sciname = $arr2[1];
                    if($id && $sciname) {
                        $taxa[$id]["schema_taxon_id"] = "gisd_" . $id;
                        $taxa[$id]["sciname"] = $sciname;
                    }
                }
            }
        }
        return $taxa;
    }

    private function create_instances_from_taxon_object($rec)
    {
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID                 = $rec["schema_taxon_id"];
        $taxon->scientificName = $rec["taxon"]["sciname"];
        $taxon->furtherInformationURL   = $rec["source"];
        echo "\n" . $taxon->scientificName . " [$taxon->taxonID]";
        if(!isset($this->taxon_ids[$taxon->taxonID])) {
            $this->archive_builder->write_object_to_file($taxon);
            $this->taxon_ids[$taxon->taxonID] = 1;
        }
    }

    private function process_GISD_distribution($rec)
    {
        if(isset($rec["Alien Range"]["locations"])) {
            foreach(@$rec["Alien Range"]["locations"] as $location) {
                $rec["catnum"] = "alien_" . str_replace(" ", "_", $location);
                self::add_string_types("true", $rec, "Alien Range", $location, "http://eol.org/schema/terms/IntroducedRange");
                if($val = $rec["taxon"]["sciname"]) self::add_string_types(null, $rec, "Scientific name", $val, "http://rs.tdwg.org/dwc/terms/scientificName");
                if($val = $rec["citation"])         self::add_string_types(null, $rec, "Citation", $val, "http://purl.org/dc/terms/bibliographicCitation");
            }
        }
        if(isset($rec["Native Range"]["locations"])) {
            foreach(@$rec["Native Range"]["locations"] as $location) {
                $rec["catnum"] = "native_" . str_replace(" ", "_", $location);
                self::add_string_types("true", $rec, "Native Range", $location, "http://eol.org/schema/terms/NativeRange");
                if($val = $rec["taxon"]["sciname"]) self::add_string_types(null, $rec, "Scientific name", $val, "http://rs.tdwg.org/dwc/terms/scientificName");
                if($val = $rec["citation"])         self::add_string_types(null, $rec, "Citation", $val, "http://purl.org/dc/terms/bibliographicCitation");
            }
        }
    }
    
    private function add_string_types($measurementOfTaxon, $rec, $label, $value, $mtype, $reference_ids = array())
    {
        $taxon_id = $rec["schema_taxon_id"];
        $catnum = $rec["catnum"];
        $m = new \eol_schema\MeasurementOrFact();
        $occurrence = $this->add_occurrence($taxon_id, $catnum);
        $m->occurrenceID = $occurrence->occurrenceID;
        if($mtype)  $m->measurementType = $mtype;
        else        $m->measurementType = "http://domain.org/". SparqlClient::to_underscore($label); // currently won't pass here
        $m->measurementValue = $value;
        if($val = $measurementOfTaxon) {
            $m->measurementOfTaxon = $val;
            $m->source = $rec["source"];
            if($reference_ids) $m->referenceID = implode("; ", $reference_ids);
            // $m->measurementRemarks = ''; $m->contributor = ''; $m->measurementMethod = '';
        }
        $this->archive_builder->write_object_to_file($m);
    }

    private function add_occurrence($taxon_id, $catnum)
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

}
?>