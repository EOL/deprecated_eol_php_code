<?php
namespace php_active_record;
/* connector: [760]
original: DATA-1426 Scrape invasive species data from GISD & CABI ISC
latest ticket: https://eol-jira.bibalex.org/browse/TRAM-794
*/

class InvasiveSpeciesCompendiumAPI
{
    function __construct($folder)
    {
        $this->resource_id = $folder;
        $this->taxa = array();
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->occurrence_ids = array();
        $this->taxon_ids = array();
        $this->download_options = array('resource_id' => $folder, 'download_wait_time' => 1000000, 'timeout' => 60*2, 'download_attempts' => 1, 'cache' => 1); // 'expire_seconds' => 0
        // /*
        // CABI ISC
        $this->CABI_taxa_list_per_page = "http://www.cabi.org/isc/Default.aspx?site=144&page=4066&sort=meta_released_sort+desc&fromab=&LoadModule=CABISearchResults&profile=38&tab=0&start=";
        $this->CABI_taxon_distribution = "http://www.cabi.org/isc/DatasheetDetailsReports.aspx?&iSectionId=DD*0&sSystem=Product&iPageID=481&iCompendiumId=5&iDatasheetID=";
        $this->CABI_references = array();
        $this->CABI_ref_page = "http://www.cabi.org/isc/references.aspx?PAN=";
        // */
        
        $this->debug = array();
        https://raw.githubusercontent.com/eliagbayani/EOL-connector-data-files/master/Invasive%20Species%20Compendium/ExportedRecords.csv
        $this->taxa_list['ISC'] = "http://localhost/cp_new/Invasive%20Species%20Compendium/ExportedRecords.csv";
        $this->taxa_list['ISC'] = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/Invasive%20Species%20Compendium/ExportedRecords.csv";
        $this->taxon_page['ISC'] = "";
    }

    function generate_invasiveness_data()
    {
        self::start_ISC();
        $this->archive_builder->finalize(TRUE);
        if($this->debug) {
            echo "\nun-mapped string location: ".count($this->debug['un-mapped string']['location'])."\n";
            echo "\nun-mapped string habitat: ".count($this->debug['un-mapped string']['habitat'])."\n";
            Functions::start_print_debug($this->debug, $this->resource_id);
        }
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
    
    private function start_ISC()
    {
        /* un-comment in real operation
        $mappings = Functions::get_eol_defined_uris(false, true); //1st param: false means will use 1day cache | 2nd param: opposite direction is true
        echo "\n".count($mappings). " - default URIs from EOL registry.";
        $this->uri_values = Functions::additional_mappings($mappings); //add more mappings used in the past
        */
        // print_r($this->uri_values);
        // exit("\nstopx\n");
        
        $csv_file = Functions::save_remote_file_to_local($this->taxa_list['ISC'], $this->download_options);
        $file = Functions::file_open($csv_file, "r");
        $i = 0;
        while(!feof($file)) {
            $i++;
            if(($i % 100) == 0) echo "\n count:[$i] ";
            $row = fgetcsv($file);
            if($i == 1) $fields = $row;
            else {
                $vals = $row;
                if(count($fields) != count($vals)) {
                    print_r($vals);
                    exit("\nNot same count ".count($fields)." != ".count($vals)."\n");
                }
                if(!$vals[0]) continue;
                $k = -1; $rec = array();
                foreach($fields as $field) {
                    $k++;
                    $rec[$field] = $vals[$k];
                }
                // print_r($rec);

                if(preg_match("/\/datasheet\/(.*?)\//ims", $rec['URL'], $arr)) $rec['taxon_id'] = $arr[1]; // datasheet/121524/aqb
                else exit("\nInvestigate 01 ".$rec['Scientific name']."\n");
                
                if($rec['Scientific name']) {
                    $url = $rec['URL'];
                    if($html = Functions::lookup_with_cache($url, $this->download_options)) {
                        $rec['taxon_ranges'] = self::get_native_introduced_invasive_ranges($html, $rec);
                        $rec['source_url'] = $url;
                    }
                    print_r($rec);
                    /*
                    if($rec['Species'] && ($rec['alien_range'] || $rec['native_range'])) {
                        $this->create_instances_from_taxon_object($rec);
                        $this->process_GISD_distribution($rec);
                    }
                    */
                    if($i == 3) break; //debug only
                }
            }
        }
        unlink($csv_file);
        exit("\nstopx\n");
    }
    private function get_native_introduced_invasive_ranges($html, $rec)
    {
        $final = array();
        if(preg_match("/<div id=\'todistributionTable\'(.*?)<\/div>/ims", $html, $arr)) { //<div id='todistributionTable' class='Product_data-item'>xxx yyy</div>
            // echo "\n".$arr[1];
            if(preg_match_all("/<tr>(.*?)<\/tr>/ims", $arr[1], $arr2)) {
                // print_r($arr2[1]);
                $i = 0;
                foreach($arr2[1] as $block) {
                    if($i === 0) {
                        $fields = self::get_fields($block);
                        // print_r($fields);
                    }
                    else {
                        $cols = self::get_values($block);
                        $rek = array(); $k = 0;
                        foreach($fields as $fld) {
                            $rek[$fld] = $cols[$k];
                            $k++;
                        }
                        if($val = self::valid_rek($rek, $rec)) $final[] = $val;
                    }
                    $i++;
                }
            }
            // exit("\n\n");
        }
        // else exit("\nInvestigate no Distribution Table ".$rec['Scientific name']."\n");
        return $final;
    }
    private function valid_rek($rek, $rec)
    {
        $good = array();
        // print_r($rek);
        /*
        Array(
            [region] => <a href="/isc/datasheet/108785">-Russian Far East</a>
            [Distribution] => Present
            [Last Reported] => 
            [Origin] => Native
            [First Reported] => 
            [Invasive] => Invasive
            [Reference] => <a href="#81FD72A0-4561-4289-9CC1-03CC152F019E">Reshetnikov,
         1998</a>; <a href="#720991F8-F58F-4243-BD6B-7FC4EADDC706">Shed'ko,
         2001</a>; <a href="#9CE8C4A4-6317-4B9A-BB5C-00C8EC2904E9">Kolpakov et al.,
         2010</a>
            [Notes] => Native in Amur drainage and Khanka Lake; introduced and invasive in the Artemovka River (Ussuri Bay, Sea of Japan / East Sea) and Razdolnaya River (Peter the Great Bay, Sea of Japan/East Sea)
        )
        */
        if($val = $rek['Reference']) $refs = self::assemble_references($val, $rec);
        if(in_array($rek['Origin'], array("Native", "Introduced"))) $good[] = array('region' => $rek['region'], 'range' => $rek['Origin'], "refs" => $refs);
        if(in_array($rek['Invasive'], array("Invasive", "Not invasive")))           $good[] = array('region' => $rek['region'], 'range' => $rek['Invasive'], "refs" => $refs);
        return $good;
    }
    private function assemble_references($ref_str, $rec)
    {
        $final = array();
        $html = Functions::lookup_with_cache($rec['URL'], $this->download_options);
        // print_r($rec);
        if(preg_match_all("/<a href=\"(.*?)\"/ims", $ref_str, $arr)) { //<a href="#6F3C79AC-42E4-40E3-A84D-57017C5A9414">
            // print_r($arr[1]);
            foreach($arr[1] as $anchor_id) {
                $final[] = self::lookup_ref_using_anchor_id($anchor_id, $html);
            }
        }
        return $final;
        // exit("\n$ref_str\n");
    }
    private function lookup_ref_using_anchor_id($anchor_id, $html)
    {
        $parts = array();
        $anchor_id = str_replace("#", "", $anchor_id);
        if(preg_match("/<p id=\"".$anchor_id."\" class=\"reference\">(.*?)<\/p>/ims", $html, $arr)) { //<p id="6F3C79AC-42E4-40E3-A84D-57017C5A9414" class="reference">
            // echo "\n$arr[1]\n";
            if(preg_match("/<a href=\"(.*?)\"/ims", $arr[1], $arr2)) $parts['ref_url'] = $arr2[1];
            $parts['full_ref'] = strip_tags($arr[1], "<i>");
        }
        return $parts;
    }
    private function get_values($block)
    {
        $block = str_replace("<td />", "<td></td>", $block);
        if(preg_match_all("/<td>(.*?)<\/td>/ims", $block, $arr)) {
            $cols = $arr[1];
            $cols = self::clean_columns($cols);
            // print_r($cols);
            return $cols;
        }
    }
    private function clean_columns($cols)
    {
        $final = array();
        $cols = array_map('trim', $cols);
        foreach($cols as $col) {
            $col = Functions::remove_whitespace($col);
            $final[] = $col;
        }
        return $final;
    }
    private function get_fields($block)
    {
        if(preg_match_all("/<th>(.*?)<\/th>/ims", $block, $arr)) {
            $fields = $arr[1];
            if($fields[0] == "Continent/Country/Region") {
                $fields[0] = 'region';
                return $fields;
            }
            else exit("\nHeaders changed...\n");
        }
        else exit("\nInvestigate no table headers\n");
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
    /* old
    private function process_GISD()
    {
        $taxa = self::get_GISD_taxa();
        $total = count($taxa); echo "\n taxa count: $total \n";
        $i = 0;
        foreach($taxa as $taxon_id => $taxon) {
            $i++; echo "\n $i of $total ";
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
    */
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
                if($val = $rec["bibliographicCitation"])    self::add_string_types(null, $rec, "Citation", $val, "http://purl.org/dc/terms/bibliographicCitation");
            }
        }
        if($locations = @$rec["native_range"]) {
            foreach($locations as $location) {
                $rec["catnum"] = "native_" . str_replace(" ", "_", $location);
                self::add_string_types("true", $rec, "Native Range", self::get_value_uri($location, 'location'), "http://eol.org/schema/terms/NativeRange", array(), $location);
                // if($val = $rec["Species"])                  self::add_string_types(null, $rec, "Scientific name", $val, "http://rs.tdwg.org/dwc/terms/scientificName");
                if($val = $rec["bibliographicCitation"])    self::add_string_types(null, $rec, "Citation", $val, "http://purl.org/dc/terms/bibliographicCitation");
            }
        }
        // */
        
        if($habitat = strtolower(@$rec["System"])) {
            $rec["catnum"] = str_replace(" ", "_", $habitat);
            if($uri = self::get_value_uri($habitat, 'habitat')) {
                self::add_string_types("true", $rec, "Habitat", $uri, "http://eol.org/schema/terms/Habitat", array(), $habitat);
                if($val = $rec["bibliographicCitation"]) self::add_string_types(null, $rec, "Citation", $val, "http://purl.org/dc/terms/bibliographicCitation");
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
            /* redundant since bibliographicCitation is entered with when measurementOfTaxon == null
            $m->bibliographicCitation = $rec['bibliographicCitation'];
            */
            $m->measurementRemarks = "";
            if($orig_value) $m->measurementRemarks = ucfirst($orig_value).". ";
            $m->measurementRemarks .= $rec['measurementRemarks'];
            // $m->contributor = ''; $m->measurementMethod = '';
        }
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
    }
    */

}
?>