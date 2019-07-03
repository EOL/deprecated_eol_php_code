<?php
namespace php_active_record;
/* connector: [737] http://eol.org/content_partners/10/resources/737 
Also see connector [211], related IUCNRedlistAPI().
*/
class IUCNRedlistDataConnector
{
    const IUCN_DOMAIN = "http://www.iucnredlist.org";
    const IUCN_EXPORT_DOWNLOAD_PAGE = "/search/saved?id=47427";
    
    function __construct($folder = null)
    {
        $this->resource_id = $folder;
        $this->taxa = array();
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->occurrence_ids = array();
        $this->debug = array();

        $this->export_basename = "export-74550"; //previously "export-47427"
        // $this->species_list_export = "http://localhost/cp_new/IUCN/" . $this->export_basename . ".csv.zip";
        $this->species_list_export = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/IUCN/" . $this->export_basename . ".csv.zip";
        
        /* direct download from IUCN server does not work:
        $this->species_list_export = "http://www.iucnredlist.org/search/download/59026.csv"; -- this doesn't work
        */
        $this->download_options = array('resource_id' => $this->resource_id, 'timeout' => 3600, 'download_attempts' => 1, 'expire_seconds' => 60*60*24*30*3); //orig expires quarterly
        // $this->download_options['expire_seconds'] = false; //debug only

        $this->categories = array("CR" => "Critically Endangered (CR)",
                                  "EN" => "Endangered (EN)",
                                  "VU" => "Vulnerable (VU)",
                                  "LC" => "Least Concern (LC)",
                                  "NT" => "Near Threatened (NT)",
                                  "DD" => "Data Deficient (DD)",
                                  "EX" => "Extinct (EX)",
                                  "EW" => "Extinct in the Wild (EW)",
                                  "LR/lc" => "Lower Risk/least concern (LR/lc)",
                                  "LR/nt" => "Lower Risk/near threatened (LR/nt)",
                                  "LR/cd" => "Lower Risk/conservation dependent (LR/cd)");
        $this->iucn_taxon_page = "http://www.iucnredlist.org/apps/redlist/details/";

        // /*
        // stats only. Also use to generate names_no_entry_from_partner.txt, which happens maybe twice a year.
        $this->TEMP_DIR = create_temp_dir() . "/";
        $this->names_no_entry_from_partner_dump_file = $this->TEMP_DIR . "names_no_entry_from_partner.txt";
        $WRITE = Functions::file_open($this->names_no_entry_from_partner_dump_file, "w"); fclose($WRITE); //initialize
        // */
        
        /* Below here is used to replace the CSV export file. Using API now
        https://eol-jira.bibalex.org/browse/DATA-1813
        This is now making use of their API
        Species list: https://apiv3.iucnredlist.org/api/v3/docs#species
        Species count: https://apiv3.iucnredlist.org/api/v3/docs#species-count
        */
        $this->api['species list'] = 'https://apiv3.iucnredlist.org/api/v3/species/page/PAGE_NO?token=9bb4facb6d23f48efbf424bb05c0c1ef1cf6f468393bc745d42179ac4aca5fee'; //PAGE_NO starts with 0
        $this->api['species count'] = 'https://apiv3.iucnredlist.org/api/v3/speciescount?token=9bb4facb6d23f48efbf424bb05c0c1ef1cf6f468393bc745d42179ac4aca5fee';
    }
    /*==============================================NEW STARTS HERE===================================================*/
    private function main()
    {
        $total_page_no = self::get_total_page_no();
        for($i = 0; $i <= $total_page_no; $i++) {
            if(($i % 1000) == 0) echo "\nbatch $i\n";
            $url = str_replace('PAGE_NO', $i, $this->api['species list']);
            echo "\n$url\n";
            self::process_species_list_10k_batch($url);
            // break; //debug only
        }
        echo "\nnames_no_entry_from_partner_dump_file: $this->names_no_entry_from_partner_dump_file\n";
    }
    private function process_species_list_10k_batch($url)
    {
        require_library('connectors/IUCNRedlistAPI'); $func = new IUCNRedlistAPI();
        $names_no_entry_from_partner = $func->get_names_no_entry_from_partner();
        
        $json = Functions::lookup_with_cache($url, $this->download_options);
        $obj = json_decode($json);
        $i = 0;
        foreach($obj->result as $rec) { $i++;
            // print_r($rec); exit;
            /*stdClass Object(
                [taxonid] => 3
                [kingdom_name] => ANIMALIA
                [phylum_name] => MOLLUSCA
                [class_name] => GASTROPODA
                [order_name] => STYLOMMATOPHORA
                [family_name] => ENDODONTIDAE
                [genus_name] => Aaadonta
                [scientific_name] => Aaadonta angaurana
                [infra_rank] => 
                [infra_name] => 
                [population] => 
                [category] => CR
            )
            */
            // if(in_array($rec->taxonid, $names_no_entry_from_partner)) continue; //will un-comment after generating dump file
            if($taxon = $func->get_taxa_for_species(null, $rec->taxonid)) {
                $this->create_instances_from_taxon_object($taxon);
                $this->process_profile_using_xml($taxon);
            }
            else {
                debug("\n no result for: " . $rec["Species ID"] . "\n");
                // /* for stats only. See above reminder. Comment this line if there is no need to update text file.
                self::save_to_dump($rec->taxonid, $this->names_no_entry_from_partner_dump_file); 
                // */
            }
            // if($i >= 8) break; //debug only
        }
    }
    private function get_total_page_no()
    {
        $json = Functions::lookup_with_cache($this->api['species count'], $this->download_options);
        $obj = json_decode($json);
        print_r($obj);
        $num = ceil($obj->count / 10000); // 10k species per API call
        echo "\nTotal page no. to download the species list: $num\n";
        return $num - 1; //minus 1 bec. species list call starts at 0 zero. Per here: https://apiv3.iucnredlist.org/api/v3/docs#species
    }
    /*==============================================NEW ENDS HERE===================================================*/
    function generate_IUCN_data()
    {   /* old using CSV export -- we abandoned this route
        $basename = $this->export_basename;
        $download_options = $this->download_options;
        $download_options['expire_seconds'] = 60*60*24*25; //orig value is 60*60*24*25
        $text_path = self::load_zip_contents($this->species_list_export, $download_options, array($basename), ".csv");
        print_r($text_path);
        
        self::csv_to_array($text_path[$basename]);
        $this->archive_builder->finalize(TRUE);
        
        // remove temp dir
        $path = $text_path[$basename];
        $parts = pathinfo($path);
        $parts["dirname"] = str_ireplace($basename, "", $parts["dirname"]);
        recursive_rmdir($parts["dirname"]);
        echo "\n temporary directory removed: " . $parts["dirname"];
        print_r($this->debug);
        */

        /* new using API */
        self::main();
        $this->archive_builder->finalize(TRUE);
        print_r($this->debug);
    }
    /* abandoned CSV export
    private function csv_to_array($csv_file)
    {
        require_library('connectors/IUCNRedlistAPI'); $func = new IUCNRedlistAPI();
        $names_no_entry_from_partner = $func->get_names_no_entry_from_partner();
        $i = 0;
        if(!$file = Functions::file_open($csv_file, "r")) return;
        while(!feof($file)) {
            $temp = fgetcsv($file);
            $i++;
            if(($i % 1000) == 0) echo "\nbatch $i";
            if($i == 1) {
                $fields = $temp;
                print_r($fields);
                if(count($fields) != 23) {
                    $this->debug["not23"][$fields[0]] = 1;
                    continue;
                }
            }
            else {
                //  ----------------------------- start
                // breakdown when caching
                // $cont = false;
                // if($i >= 1     && $i < 20000)    $cont = true;
                // if($i >= 20000 && $i < 40000)    $cont = true;
                // if($i >= 40000 && $i < 60000)    $cont = true;
                // if($i >= 60000 && $i <= 80000)   $cont = true;
                // if($i >= 80000 && $i <= 100000)   $cont = true;
                // if(!$cont) continue;
                //  ----------------------------- end

                $rec = array();
                $k = 0;
                // 2 checks if valid record
                if(!$temp) continue;
                if(count($temp) != 23) {
                    $this->debug["not23"][$temp[0]] = 1;
                    continue;
                }
                
                foreach($temp as $t) {
                    $rec[$fields[$k]] = $t;
                    $k++;
                }

                if(in_array($rec["Species ID"], $names_no_entry_from_partner)) {
                    // self::process_profile_using_csv($rec);
                    continue;
                }
                
                // http://api.iucnredlist.org/details/164845
                // $rec["Species ID"] = "164845"; //debug only
                
                if($taxon = $func->get_taxa_for_species(null, $rec["Species ID"])) {
                    $this->create_instances_from_taxon_object($taxon);
                    $this->process_profile_using_xml($taxon);
                }
                else {
                    debug("\n no result for: " . $rec["Species ID"] . "\n");

                    // ----------------------------
                    // for stats only. See above reminder. Comment this line if there is no need to update text file.
                    // self::save_to_dump($rec["Species ID"], $this->names_no_entry_from_partner_dump_file); 
                    // ----------------------------
                    
                    // self::process_profile_using_csv($rec);
                }
                
                // if($i >= 10) break; //debug only
                
            }
        } // end while{}
        fclose($file);
    }
    private function process_profile_using_csv($rec)
    {
        if(count($rec) == 23) {
            $taxon = new \eol_schema\Taxon();
            $taxon->taxonID                 = $rec["Species ID"];
            $taxon->scientificName          = trim($rec["Genus"] . " " . $rec["Species"] . " " . $rec["Authority"]);
            $taxon->kingdom                 = ucfirst(strtolower($rec["Kingdom"]));
            $taxon->phylum                  = ucfirst(strtolower($rec["Phylum"]));
            $taxon->class                   = ucfirst(strtolower($rec["Class"]));
            $taxon->order                   = ucfirst(strtolower($rec["Order"]));
            $taxon->family                  = ucfirst(strtolower($rec["Family"]));
            $taxon->furtherInformationURL   = $this->iucn_taxon_page . $rec["Species ID"];
            // these 2 to imitate EOL XML
            $taxon->identifier = $taxon->taxonID;
            $taxon->source = $taxon->furtherInformationURL;
            $this->create_instances_from_taxon_object($taxon);
            
            $details = array();
            $details["RedListCategory"] = $this->categories[$rec["Red List status"]];
            $details["texts"] = array("red_list_criteria" => $rec["Red List criteria"],
                                      "category_version"  => $rec["Red List criteria version"],
                                      "modified_year"     => $rec["Year assessed"]);
            $details["pop_trend"] = $rec["Population trend"];
            $this->process_profile_using_xml($taxon, $details);
            // [Petitioned] => N
        }
        else $this->debug["not23"][$rec["Species ID"]] = 1;
    }
    */
    private function save_to_dump($data, $filename)
    {
        if(!($WRITE = Functions::file_open($filename, "a"))) return;
        if($data && is_array($data)) fwrite($WRITE, json_encode($data) . "\n");
        else                         fwrite($WRITE, $data . "\n");
        fclose($WRITE);
    }
    private function create_instances_from_taxon_object($rec)
    {
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID                 = $rec->identifier;
        $taxon->scientificName          = $rec->scientificName;
        $taxon->kingdom                 = $rec->kingdom;
        $taxon->phylum                  = $rec->phylum;
        $taxon->class                   = $rec->class;
        $taxon->order                   = $rec->order;
        $taxon->family                  = $rec->family;
        $taxon->furtherInformationURL   = $rec->source;
        debug(" - " . $taxon->scientificName . " [$taxon->taxonID]");
        $this->archive_builder->write_object_to_file($taxon);
    }
    private function process_profile_using_xml($record, $details = false)
    {
        if(!$details) $details = self::get_details($record);
        $rec = array();
        $rec["taxon_id"] = $record->identifier;
        $rec["source"] = $record->source;
        
        if($val = @$details["RedListCategory"]) {
            $val = self::format_category($val);
            $remarks = self::get_remarks_for_old_designation($val);
            $rec["catnum"] = "_rlc";
            self::add_string_types("true", $rec, "Red List Category", $val, "http://rs.tdwg.org/ontology/voc/SPMInfoItems#ConservationStatus", $remarks);
        }

        if($texts = @$details["texts"]) {
            $text["red_list_criteria"]["uri"] = "http://eol.org/schema/terms/RedListCriteria";
            $text["category_version"]["uri"] = "http://eol.org/schema/terms/Version";
            $text["modified_year"]["uri"] = "http://rs.tdwg.org/dwc/terms/measurementDeterminedDate"; //"http://eol.org/schema/terms/DateMeasured";
            $text["assessors"]["uri"] = "http://eol.org/schema/terms/Assessor";
            $text["reviewers"]["uri"] = "http://eol.org/schema/terms/Reviewer";
            $text["contributors"]["uri"] = "http://purl.org/dc/terms/contributor"; // similar to $m->contributor
            foreach($texts as $key => $value) {
                if(!$value) continue;
                $rec["catnum"] = "_rlc"; // these fields will appear under "Data about this record".
                self::add_string_types(NULL, $rec, $key, $value, $text[$key]["uri"]);
            }
        }
        
        if($habitats = @$details["habitats"]) {
            foreach($habitats as $h) {
                $this->debug["habitat"][$h] = 1;
                
                if($value_uri = self::format_habitat_value(strtolower($h))) {
                    $rec["catnum"] = "_" . $h;
                    self::add_string_types("true", $rec, $h, $value_uri, "http://rs.tdwg.org/dwc/terms/habitat");
                }
                else $this->debug["habitat"]["undefined"][$h] = 1;
            }
        }
        
        if($pop_trend = @$details["pop_trend"]) {
            $rec["catnum"] = "_pop_trend";
            self::add_string_types("true", $rec, "Population trend", $pop_trend, 'http://eol.org/schema/terms/population_trend');
        }
    }
    
    private function get_remarks_for_old_designation($category)
    {
        switch($category) {
            case "http://eol.org/schema/terms/leastConcern":          return 'Older designation "Lower Risk/least concern (LR/lc)" indicates this species has not been reevaluated since 2000';
            case "http://eol.org/schema/terms/nearThreatened":        return 'Older designation "Lower Risk/near threatened (LR/nt)" indicates this species has not been reevaluated since 2000';
            case "http://eol.org/schema/terms/conservationDependent": return 'Older designation "Lower Risk/conservation dependent (LR/cd)" indicates this species has not been reevaluated since 2000. When last evaluated, had post 2001 criteria been applied, this species would have been classed as Near Threatened';
            default: return "";
        }
    }
    private function format_habitat_value($habitat)
    {
        switch($habitat) {
            case "marine":      return "http://purl.obolibrary.org/obo/ENVO_00000569";
            case "terrestrial": return "http://purl.obolibrary.org/obo/ENVO_00002009";
            case "freshwater":  return "http://purl.obolibrary.org/obo/ENVO_00002037";
            default:            return false;
        }
    }
    private function format_category($cat)
    {
        /*
        http://eol.org/schema/terms/notEvaluated
        */
        switch($cat) {
            case "Critically Endangered (CR)":  return "http://eol.org/schema/terms/criticallyEndangered";
            case "Endangered (EN)":             return "http://eol.org/schema/terms/endangered";
            case "Vulnerable (VU)":             return "http://eol.org/schema/terms/vulnerable";
            case "Least Concern (LC)":          return "http://eol.org/schema/terms/leastConcern";
            case "Near Threatened (NT)":        return "http://eol.org/schema/terms/nearThreatened";
            case "Data Deficient (DD)":         return "http://eol.org/schema/terms/dataDeficient";
            case "Extinct (EX)":                return "http://eol.org/schema/terms/extinct";
            case "Extinct in the Wild (EW)":    return "http://eol.org/schema/terms/extinctInTheWild";
            case "Lower Risk/least concern (LR/lc)":            return "http://eol.org/schema/terms/leastConcern";
            case "Lower Risk/near threatened (LR/nt)":          return "http://eol.org/schema/terms/nearThreatened";
            case "Lower Risk/conservation dependent (LR/cd)":   return "http://eol.org/schema/terms/conservationDependent";
            default: $this->debug["category undefined"][$cat] = 1;
        }
        return false;
    }
    private function get_details($taxon)
    {
        $rec = array();
        foreach($taxon->dataObjects as $o) {
            if($o->title == "IUCNConservationStatus")       $rec["RedListCategory"] = $o->description;
            elseif($o->title == "IUCN Red List Assessment") $rec["texts"]           = self::parse_assessment_info($o->description);
            elseif($o->title == "Habitat and Ecology")      $rec["habitats"]        = self::parse_habitat_info($o->description);
            elseif($o->title == "Population")               $rec["pop_trend"]       = self::parse_population_trend($o->description);
        }
        $this->debug[@$rec["RedListCategory"]] = 1;
        return $rec;
    }
    private function parse_population_trend($html)
    {
        if(preg_match("/<div id=\"population_trend\">(.*?)<\/div>/ims", $html, $arr)) return $arr[1];
    }
    private function parse_habitat_info($html)
    {
        if(preg_match_all("/<li class=\"system\">(.*?)<\/li>/ims", $html, $arr)) return $arr[1];
    }
    private function parse_assessment_info($html)
    {
        $rec = array();
        /* not used here
        if(preg_match("/<div id=\"red_list_category_code\">(.*?)<\/div>/ims", $html, $arr))  $rec["red_list_category_code"] = trim($arr[1]);
        if(preg_match("/<div id=\"red_list_category_title\">(.*?)<\/div>/ims", $html, $arr)) $rec["red_list_category_title"] = trim($arr[1]);
        */
        if(preg_match("/<div id=\"red_list_criteria\">(.*?)<\/div>/ims", $html, $arr))       $rec["red_list_criteria"] = trim($arr[1]);
        if(preg_match("/<div id=\"category_version\">(.*?)<\/div>/ims", $html, $arr))        $rec["category_version"] = trim($arr[1]);
        if(preg_match("/<div id=\"modified_year\">(.*?)<\/div>/ims", $html, $arr))           $rec["modified_year"] = trim($arr[1]);
        if(preg_match("/<div id=\"assessors\">(.*?)<\/div>/ims", $html, $arr))               $rec["assessors"] = trim($arr[1]);
        if(preg_match("/<div id=\"reviewers\">(.*?)<\/div>/ims", $html, $arr))               $rec["reviewers"] = trim($arr[1]);
        if(preg_match("/<div id=\"contributors\">(.*?)<\/div>/ims", $html, $arr))            $rec["contributors"] = trim($arr[1]);
        return $rec;
    }
    private function add_string_types($measurementOfTaxon, $rec, $label, $value, $mtype, $measurementRemarks = null)
    {
        // echo "\n [$label]:[$value]\n";
        $taxon_id = $rec["taxon_id"];
        $catnum = $rec["catnum"];
        $m = new \eol_schema\MeasurementOrFact();
        $occurrence_id = $this->add_occurrence($taxon_id, $catnum);
        $m->occurrenceID = $occurrence_id;
        if($mtype)  $m->measurementType = $mtype;
        else {
            $m->measurementType = "http://iucn.org/". SparqlClient::to_underscore($label);
            echo "\n*Need to add URI for this [$label] [$value]\n";
        }
        $m->measurementValue = $value;
        if($val = $measurementOfTaxon) $m->measurementOfTaxon = $val;
        if($measurementOfTaxon) {
            $m->source = $rec["source"];
            $m->measurementRemarks = $measurementRemarks;
            // $m->contributor = '';
            // $m->measurementMethod = '';
        }
        // $m->measurementID = Functions::generate_measurementID($m, $this->resource_id, 'measurement', array('occurrenceID', 'measurementType', 'measurementValue'));
        $m->measurementID = Functions::generate_measurementID($m, $this->resource_id);
        $this->archive_builder->write_object_to_file($m);
    }
    private function add_occurrence($taxon_id, $catnum)
    {
        $occurrence_id = $taxon_id . 'O' . $catnum;
        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = $occurrence_id;
        $o->taxonID = $taxon_id;

        $o->occurrenceID = Functions::generate_measurementID($o, $this->resource_id, 'occurrence');
        if(isset($this->occurrence_ids[$o->occurrenceID])) return $o->occurrenceID;

        $this->archive_builder->write_object_to_file($o);
        $this->occurrence_ids[$o->occurrenceID] = '';
        return $o->occurrenceID;
    }
    function load_zip_contents($zip_path, $download_options, $files, $extension)
    {
        $text_path = array();
        $temp_path = create_temp_dir();
        if($file_contents = Functions::lookup_with_cache($zip_path, $download_options)) // resource is set to harvest quarterly and the cache expires by default in a month
        {
            $parts = pathinfo($zip_path);
            $temp_file_path = $temp_path . "/" . $parts["basename"];
            if(!($TMP = Functions::file_open($temp_file_path, "w"))) return;
            fwrite($TMP, $file_contents);
            fclose($TMP);
            $output = shell_exec("unzip -o $temp_file_path -d $temp_path");
            if(file_exists($temp_path . "/" . $files[0] . $extension)) {
                foreach($files as $file) {
                    $text_path[$file] = $temp_path . "/" . $file . $extension;
                }
            }
        }
        else echo "\n\n Connector terminated. Remote files are not ready.\n\n";
        return $text_path;
    }
    /*
    private function get_species_list_export_file() // not currently used
    {
        if($html = Functions::lookup_with_cache(self::IUCN_DOMAIN . self::IUCN_EXPORT_DOWNLOAD_PAGE, $this->download_options))
        {
            //<li><a href="/search/download/38992.csv">Comma-Separated Values (CSV)</a>
            if(preg_match("/<li><a href=\"\/search\/download\/(.*?)\">Comma-Separated Values/ims", $html, $arr))
            {
                // must login
                <form action="/users/sign_in" class="formtastic user" id="new_user" method="post" novalidate="novalidate">
                <input name="utf8" type="hidden" value="✓">
                <input name="authenticity_token" type="hidden" value="zU0AeC1jKqea4XxZ38cV6VMQgLtBjvFyGd7EhnOkTgM=">
                <input id="user_email" maxlength="255" name="user[email]" type="email" value="">
                <input id="user_password" maxlength="128" name="user[password]" type="password">
                <input name="user[remember_me]" type="hidden" value="0">
                <input id="user_remember_me" name="user[remember_me]" type="checkbox" value="1">Remember me</label>
                <input name="commit" type="submit" value="Login">
                </form>

                $authenticity_token = self::get_token();
                $url = "http://www.iucnredlist.org/users/sign_in";
                $params = array("user[email]" => "eli@eol.org", "user[password]" => "jijamali", "authenticity_token" => $authenticity_token, 
                                "commit" => "Login", "user[remember_me]" => "1", "utf8" => "✓");
                $x = Functions::curl_post_request($url, $params);
                
                return self::IUCN_DOMAIN . "/search/download/" . $arr[1];
            }
        }
        return false;
    }
    private function get_token()
    {
        $download_options = $this->download_options;
        $download_options['expire_seconds'] = 0;
        if($html = Functions::lookup_with_cache("http://www.iucnredlist.org/users/sign_in", $download_options))
        {
            if(preg_match("/<input name=\"authenticity_token\" type=\"hidden\" value=\"(.*?)\"/ims", $html, $arr))
            {
                return $arr[1];
            }
        }
    }
    */
}
?>
