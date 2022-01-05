<?php
namespace php_active_record;
/* connector: [737] http://eol.org/content_partners/10/resources/737 
Also see connector [211], related IUCNRedlistAPI().

As of May 26, 2010:
    [Critically Endangered (CR)] => 1
    [Endangered (EN)] => 1
    [Data Deficient (DD)] => 1
    [Extinct (EX)] => 1
    [Lower Risk/near threatened (LR/nt)] => 1
    [Vulnerable (VU)] => 1
    [Near Threatened (NT)] => 1
    [Least Concern (LC)] => 1
    [Lower Risk/least concern (LR/lc)] => 1
    [Lower Risk/conservation dependent (LR/cd)] => 1
    [Extinct in the Wild (EW)] => 1

*/
class IUCNRedlistDataConnector extends ContributorsMapAPI
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
            $url = str_replace('PAGE_NO', $i, $this->api['species list']);
            echo "\n$url\n";
            self::process_species_list_10k_batch($url, "$i of $total_page_no");
            // break; //debug only
        }
        if(isset($this->names_no_entry_from_partner_dump_file)) echo "\nnames_no_entry_from_partner_dump_file: $this->names_no_entry_from_partner_dump_file\n";
    }
    private function process_species_list_10k_batch($url, $msg)
    {
        require_library('connectors/IUCNRedlistAPI'); $func = new IUCNRedlistAPI();
        $names_no_entry_from_partner = $func->get_names_no_entry_from_partner();
        
        $json = Functions::lookup_with_cache($url, $this->download_options);
        $obj = json_decode($json);
        $i = 0;
        foreach($obj->result as $rec) { $i++;
            if(($i % 500) == 0) echo "\nbatch $i [$msg]";
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
            if(in_array($rec->taxonid, $names_no_entry_from_partner)) continue; //will un-comment after generating dump file
            /* obsolete - very old
            if($taxon = $func->get_taxa_for_species(null, $rec->taxonid)) {
            */
            if($ret = $func->get_taxa_for_species_V2($rec->taxonid)) { //print_r($taxon); exit("\n-eli-\n");
                $taxon = $ret[0];
                $species_info = $ret[1];
                // print_r($species_info); exit("\n-eli-\n");
                
                $taxon->source = "http://apiv3.iucnredlist.org/api/v3/website/".str_replace(' ', '%20', $rec->scientific_name); //e.g. http://apiv3.iucnredlist.org/api/v3/website/Panthera%20leo
                $taxon->source = "http://apiv3.iucnredlist.org/api/v3/taxonredirect/".$rec->taxonid; //seems better than above
                $taxon = self::fix_sciname_and_add_locality_if_needed($taxon); //Jul 30, 2019 per Katja: https://eol-jira.bibalex.org/browse/DATA-1815?focusedCommentId=63645&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-63645
                $this->create_instances_from_taxon_object($taxon);
                $this->process_profile_using_xml($taxon, $species_info);
                // break; //debug only
            }
            else {
                debug("\n no result for: " . $rec->taxonid . "\n");
                // /* See above reminder about 'names_no_entry_from_partner'
                if(isset($this->names_no_entry_from_partner_dump_file)) self::save_to_dump($rec->taxonid, $this->names_no_entry_from_partner_dump_file); 
                // */
            }
            // if($i >= 8) break; //debug only
        }
    }
    private function fix_sciname_and_add_locality_if_needed($taxon)
    {
        // print_r($taxon); exit;
        /*SchemaTaxon Object(
            [identifier] => 3
            [source] => http://apiv3.iucnredlist.org/api/v3/taxonredirect/3
            [kingdom] => Animalia
            [phylum] => Mollusca
            [class] => Gastropoda
            [order] => Stylommatophora
            [family] => Endodontidae
            [scientificName] => Aaadonta angaurana Solem, 1976
            ...many more fields below
        */
        // $taxon->scientificName = 'Pristis pristis (Eastern Atlantic subpopulation) (Linnaeus, 1758)'; //debug only - force assign
        // $taxon->scientificName = 'Balaena mysticetus (Svalbard-Barents Sea (Spitsbergen) subpopulation) Linnaeus, 1758'; //debug - force assign
        $sci = $taxon->scientificName;
        if(preg_match("/\((.*?)population\)/ims", $sci, $arr)) {
            $taxon->locality = "(".$arr[1]."population)";
            $sci = trim(str_replace($taxon->locality, "", $sci));
            $taxon->scientificName = Functions::remove_whitespace($sci);
        }
        // print_r($taxon); echo "\n[$taxon->locality] [$taxon->scientificName]\n"; exit;
        return $taxon;
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

        /* contributor map --> working but removed. Original text strings are shown instead of URI mapping.
        $options = array('cache' => 1, 'download_wait_time' => 500000, 'timeout' => 10800, 'expire_seconds' => 60*60*1);
        $this->contributor_mappings = $this->get_contributor_mappings($this->resource_id, $options);
        // print_r($this->contributor_mappings); exit;
        echo "\n contributor_mappings: ".count($this->contributor_mappings)."\n";
        */

        /* new using API */
        self::main();
        $this->archive_builder->finalize(TRUE); //part of main operation
        // exit("\n-end caching-\n"); //only when caching
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
        $taxon->taxonRank               = self::guess_rank($rec->scientificName); //'species';
        $taxon->kingdom                 = $rec->kingdom;
        $taxon->phylum                  = $rec->phylum;
        $taxon->class                   = $rec->class;
        $taxon->order                   = $rec->order;
        $taxon->family                  = $rec->family;
        $taxon->furtherInformationURL   = $rec->source;
        debug(" - " . $taxon->scientificName . " [$taxon->taxonID]");
        $this->archive_builder->write_object_to_file($taxon);
    }
    private function guess_rank($sciname)
    {
        $rank = 'species';
        $sciname = trim($sciname);

        $words = explode(" ", $sciname);
        if($third = @$words[2]) { //has a third word
            $first_char = $third[0];
            if(ctype_lower($first_char)) $rank = '';
            if(ctype_upper($first_char)) $rank = 'species';
            if($first_char == "(") $rank = 'species';
        }
        
        if(stripos($sciname, " spp") !== false) $rank = 'species'; //string is found
        return $rank;
    }
    private function process_profile_using_xml($record, $species_info)
    {
        $details = self::get_details($record);
        
        // /* pop_trend
        $details['pop_trend'] = $species_info['population_trend'];
        // */
        
        // /* habitats
        $habitats = array();
        if(in_array($species_info['marine_system'], array('true', 1, '1'))) $habitats[] = 'marine';
        if(in_array($species_info['freshwater_system'], array('true', 1, '1'))) $habitats[] = 'freshwater';
        if(in_array($species_info['terrestrial_system'], array('true', 1, '1'))) $habitats[] = 'terrestrial';
        $details['habitats'] = $habitats;
        // */
        
        // /* texts
        $r = array();
        $r["red_list_criteria"] = $species_info['criteria'];
        // $r["category_version"]  = $species_info['xxx']; to call another API call
        $r["modified_year"]     = $species_info['published_year'];
        $r["assessors"]         = $species_info['assessor'];
        $r["reviewers"]         = $species_info['reviewer'];
        // $r["contributors"]      = $species_info['xxx']; to be scraped given green light
        $details['texts'] = $r;
        // */
        
        // print_r($details); exit("\nsss\n");
        
        
        $rec = array();
        $rec["taxon_id"] = $record->identifier;
        $rec["source"] = $record->source;
        
        if($val = @$details["RedListCategory"]) {
            $val = self::format_category($val);
            $remarks = self::get_remarks_for_old_designation($val);
            $rec["catnum"] = "_rlc";
            
            // print_r($rec); //exit("\nstop muna\n");

            /* abandoned Eli's idea - putting these 2 fields in parent record
            if($texts = @$details["texts"]) {
                // print_r($texts); exit;
                $texts Array(
                    [red_list_criteria] => B1ab(iii)+2ab(iii)
                    [category_version] => 3.1
                    [modified_year] => 2012
                    [assessors] => Rundell, R.J.
                    [reviewers] => Barker, G., Cowie, R., Triantis, K., García, N. & Seddon, M.
                )
                
                if($val = @$texts['modified_year']) {
                    $rec['measurementDeterminedDate'] = $val;
                    unset($details["texts"]['modified_year']);
                }
                if($val = @$texts['contributors']) {
                    $rec['contributor'] = $val;
                    unset($details["texts"]['contributors']);
                }
            }
            */
            $rec['locality'] = @$record->locality;
            $parentMeasurementID = self::add_string_types("true", $rec, "Red List Category", $val, "http://rs.tdwg.org/ontology/voc/SPMInfoItems#ConservationStatus", $remarks);

            /* abandoned Eli's idea
            //set to blank since they were alredy added in parent record
            $rec['measurementDeterminedDate'] = '';
            $rec['contributor'] = '';
            */
            
            if($texts = @$details["texts"]) {
                $text["red_list_criteria"]["uri"] = "http://eol.org/schema/terms/RedListCriteria";
                $text["category_version"]["uri"] = "http://eol.org/schema/terms/Version";
                $text["assessors"]["uri"] = "http://eol.org/schema/terms/Assessor";
                $text["reviewers"]["uri"] = "http://eol.org/schema/terms/Reviewer";

                // /* remained here, not added in parent record
                $text["modified_year"]["uri"] = "http://rs.tdwg.org/dwc/terms/measurementDeterminedDate"; //"http://eol.org/schema/terms/DateMeasured";
                $text["contributors"]["uri"] = "http://purl.org/dc/terms/contributor"; // similar to $m->contributor
                // */
                foreach($texts as $key => $value) {
                    if(!$value) continue;
                    $rec["catnum"] = "_rlc"; // these fields will appear under "Data about this record".
                    
                    if(in_array($key, array('assessors', 'reviewers'))) {
                        $names = self::separate_names($value);
                        foreach($names as $contributor) {
                            /* works OK but removed: https://eol-jira.bibalex.org/browse/DATA-1881?focusedCommentId=66133&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-66133
                            if($uri = @$this->contributor_mappings[$contributor]) {
                                if(substr($uri,0,4) == 'http') self::add_string_types(NULL, $rec, $key, $uri, $text[$key]["uri"], '', $parentMeasurementID);
                            }
                            else { //no mapping yet for this contributor
                                $this->debug['undefined contributor'][$contributor] = '';
                                // $this->debug['names for Jen'][$contributor] = ''; //redundant
                            }
                            // for stats/report
                            if(substr_count($contributor, ',') > 1) $this->debug['Eli investigates']["if(part == '$contributor') part = '$contributor';"] = '';
                            */
                            
                            // /* goes back to using original text strings for names
                            self::add_string_types(NULL, $rec, $key, $contributor, $text[$key]["uri"], '', $parentMeasurementID);
                            // */
                        }
                    }
                    else { //orig, the rest
                        self::add_string_types(NULL, $rec, $key, $value, $text[$key]["uri"], '', $parentMeasurementID);
                    }
                }
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
    private function get_2nd_to_last_char($str)
    {
        $len = strlen($str);
        return substr($str,$len-2,1);
    }
    function separate_names($str)
    {
        // $str = "Rundell, R.J.";
        //$str = "Barker, G., Cowie, R., Triantis, K., García, N. & Seddon, M.";
        $names = array();
        // echo "\n[$str]\n";
        $arr1 = explode("&", $str);
        $arr1 = array_map('trim', $arr1);
        foreach($arr1 as $a) {
            $arr2 = explode("., ", $a);
            $arr2 = array_map('trim', $arr2);
            foreach($arr2 as $name) {
                $last_char = substr($name, -1);
                if($last_char == ".") $names[] = "$name";
                else {
                    $second_to_last_char = self::get_2nd_to_last_char($name);
                    if($second_to_last_char == " ")     if(ctype_alpha($last_char)) $names[] = "$name.";
                    elseif($second_to_last_char == ".") if(ctype_alpha($last_char)) $names[] = "$name.";
                    else                                $names[] = "$name";
                }
            }
        }
        // print_r($names); exit("-test-");
        // return $names;
        /* start another round */
        $final = array(); $final2 = array();
        foreach($names as $name) {
            if($name == "Global Amphibian Assessment Coordinating Team (Simon Stuart, Janice Chanson, Neil Cox and Bruce Young)") {
                $name = "Global Amphibian Assessment Coordinating Team and Simon Stuart and Janice Chanson and Neil Cox and Bruce Young";
            }
            if($name == "Global Amphibian Assessment Coordinating Team (Simon Stuart, Janice Chanson, Neil Cox and Bruce Young) and Ariadne Angulo") {
                $name = "Global Amphibian Assessment Coordinating Team and Simon Stuart and Janice Chanson and Neil Cox and Bruce Young and Ariadne Angulo";
            }
            if($name == "Daniels, S.R. (University of Stellenbosch), Darwall, W. (Freshwater Biodiversity Assessment Unit) and McIvor, A.") {
                $name = "Daniels, S.R. (University of Stellenbosch) and Darwall, W. (Freshwater Biodiversity Assessment Unit) and McIvor, A.";
            }
            if($name == "Ng, P. Yeo, D. and McIvor, A.") {
                $name = "Ng, P. and Yeo, D. and McIvor, A.";
            }
            
            //$parts = preg_split("/(, | and | and | & )/",$name); //copied template, not used here.
            $parts = explode(" and ", $name);
            foreach($parts as $part) {
                if($part == "Rasoloariniaina, R, Ravelomanana, T.") $part = "Rasoloariniaina, R. and Ravelomanana, T.";
                if($part == "Duarte, J.M.B, Varela, D.") $part = "Duarte, J.M.B. and Varela, D.";
                if($part == "Lowe, C.G, Marshall, A.") $part = "Lowe, C.G. and Marshall, A.";
                if($part == "McCabe, G, Rovero, F.") $part = "McCabe, G. and Rovero, F.";

                if($part == "Racey, P.A. (Chiroptera Red List Authority), Chanson, J.") $part = "Racey, P.A. (Chiroptera Red List Authority) and Chanson, J.";
                if($part == "Skejo, J. Skejo, Szovenyi, G.") $part = "Skejo, J. and Szovenyi, G.";
                if($part == "Snoeks, J. Staissny, M.") $part = "Snoeks, J. and Staissny, M.";
                if($part == "Kadarusman, Unmack, P.") $part = "Kadarusman and Unmack, P.";
                if($part == "Djikstra, K. D, Kipping, J.") $part = "Djikstra, K. D. and Kipping, J.";
                if($part == "NatureServe, Daniels, A.") $part = "NatureServe and Daniels, A.";
                if($part == "Francis, C, Bates, P.") $part = "Francis, C. and Bates, P.";
                if($part == "Davidson, ZD, Kebede, F.") $part = "Davidson, ZD and Kebede, F.";
                if($part == "West, D, Franklin, P.") $part = "West, D. and Franklin, P.";
                if($part == "Allibone, R, Crow, S.") $part = "Allibone, R. and Crow, S.";
                if($part == "West, D, David, B.") $part = "West, D. and David, B.";
                if($part == "West, D, Ling, N.") $part = "West, D. and Ling, N.";
                if($part == "Lang, J, Chowfin, S.") $part = "Lang, J. and Chowfin, S.";
                if($part == "Allibone, R, Closs, G.") $part = "Allibone, R. and Closs, G.";
                if($part == "Ransom, C, Robinson, P.T.") $part = "Ransom, C. and Robinson, P.T.";
                if($part == "Helgen, Bates, P.") $part = "Helgen and Bates, P.";
                if($part == "Weksler, M. Queirolo, D.") $part = "Weksler, M. and Queirolo, D.";
                if($part == "Brockelman, W, Roos, C.") $part = "Brockelman, W. and Roos, C.";
                if($part == "Brockelman, W, Geissmann, T.") $part = "Brockelman, W. and Geissmann, T.";
                if($part == "Darwall, W. (IUCN Freshwater Biodiversity Assessment Unit), Pollock, C.M. (IUCN Red List Unit)") $part = "Darwall, W. (IUCN Freshwater Biodiversity Assessment Unit) and Pollock, C.M. (IUCN Red List Unit)";
                if($part == "Kadarusman, Allen, G.R.") $part = "Kadarusman and Allen, G.R.";
                if($part == "Menzies, Wright, D.") $part = "Menzies and Wright, D.";
                if($part == "Kefelioglu, H. Yigit, N.") $part = "Kefelioglu, H. and Yigit, N.";
                if($part == "Lee, B.P.Y-H, Maryanto, I.") $part = "Lee, B.P.Y-H and Maryanto, I.";
                if($part == "West, D, Crow, S.") $part = "West, D. and Crow, S.";
                if($part == "Vivar, E. , Jayat, J.P.") $part = "Vivar, E. and Jayat, J.P.";
                if($part == "Ram, M. (Sampled Red List Index Coordinating Team), Cox, N.") $part = "Ram, M. (Sampled Red List Index Coordinating Team) and Cox, N.";
                if($part == "Wang, J.Y. , Wells, R.S.") $part = "Wang, J.Y. and Wells, R.S.";
                if($part == "James, D.,Lumsden, L.") $part = "James, D. and Lumsden, L.";
                if($part == "Silva-Rodríguez, E, Pastore, H.") $part = "Silva-Rodríguez, E. and Pastore, H.";
                if($part == "Economou, N, Pollom, R.") $part = "Economou, N. and Pollom, R.";
                if($part == "Mitchell, N.M, Woinarski, J.C.Z.") $part = "Mitchell, N.M. and Woinarski, J.C.Z.";
                if($part == "Quinten, M, Setiawan, A.") $part = "Quinten, M. and Setiawan, A.";
                if($part == "Duarte, J.M.B, Vogliotti, A.") $part = "Duarte, J.M.B. and Vogliotti, A.";
                if($part == "Haxhiu, I,, Sterijovski, B.") $part = "Haxhiu, I. and Sterijovski, B.";
                if($part == "Stevens, D, Fennane, M, Gardner, M.") $part = "Stevens, D. and Fennane, M. and Gardner, M.";
                if($part == "Rumeu Ruiz, B, de Sequeira, M, Elliot, M.") $part = "Rumeu Ruiz, B. and de Sequeira, M. and Elliot, M.";
                if($part == "Marrero-Rodríguez, Á, Medina Hijazo, F.") $part = "Marrero-Rodríguez, Á. and Medina Hijazo, F.";
                if($part == "Zhang, D, Katsuki, T.") $part = "Zhang, D. and Katsuki, T.";
                if($part == "Zhang, D, Li, N.") $part = "Zhang, D. and Li, N.";
                if($part == "Zhang, D, Rushforth, K.") $part = "Zhang, D. and Rushforth, K.";
                if($part == "Mouaxayvaneng, A,, Muangyen, N.") $part = "Mouaxayvaneng, A. and Muangyen, N.";
                if($part == "Zhang, D, Luscombe, D, Liao, W.") $part = "Zhang, D. and Luscombe, D. and Liao, W.";
                if($part == "Purwaningsih, Khoo, E.") $part = "Purwaningsih and Khoo, E.";
                if($part == "Purwaningsih, Kalima, T.") $part = "Purwaningsih and Kalima, T.";
                if($part == "Cuban Plant Specialist Group, Wheeler, L.") $part = "Cuban Plant Specialist Group and Wheeler, L.";
                if($part == "Letsara, R, Rabarison, H.") $part = "Letsara, R. and Rabarison, H.";
                if($part == "Morey, G, Barker, J.") $part = "Morey, G. and Barker, J.";
                if($part == "Blanco-Parra, MP, Briones Bell-lloch, A.") $part = "Blanco-Parra, MP. and Briones Bell-lloch, A.";
                if($part == "Fahmi, Bin Ali, A.") $part = "Fahmi and Bin Ali, A.";
                if($part == "Chapman, D, Simpfendorfer, C.") $part = "Chapman, D. and Simpfendorfer, C.";
                if($part == "Nekaris, K.A.I. , Shekelle, M, Wirdateti, Rode-Margono, E.J.") $part = "Nekaris, K.A.I. and Shekelle, M. and Wirdateti and Rode-Margono, E.J.";
                if($part == "Quinten, M, Cheyne, S.") $part = "Quinten, M. and Cheyne, S.";
                if($part == "Brockelman, W, Molur, S.") $part = "Brockelman, W. and Molur, S.";
                if($part == "Francis, C, Görföl, T.") $part = "Francis, C. and Görföl, T.";
                if($part == "Fitz Maurice, B, Guadalupe Martínez, J.") $part = "Fitz Maurice, B. and Guadalupe Martínez, J.";
                if($part == "Fitz Maurice, B, Fitz Maurice, W.A.") $part = "Fitz Maurice, B. and Fitz Maurice, W.A.";
                if($part == "Fitz Maurice, B, Gómez-Hinostrosa, C.") $part = "Fitz Maurice, B. and Gómez-Hinostrosa, C.";
                if($part == "Fitz Maurice, B, Sotomayor, M.") $part = "Fitz Maurice, B. and Sotomayor, M.";
                if($part == "Mumpuni, Hero, J.") $part = "Mumpuni and Hero, J.";
                if($part == "Han, K.H, Duckworth, J.W.") $part = "Han, K.H. and Duckworth, J.W.";
                if($part == "Silva-Rodríguez, E, Farias, A.") $part = "Silva-Rodríguez, E. and Farias, A.";
                if($part == 'Ebert, D, Musick, J.A.') $part = 'Ebert, D. and Musick, J.A.';
                if($part == 'Beisiegel, B. de Mello, Holland, J.') $part = 'Beisiegel, B. and de Mello and Holland, J.';
                if($part == 'Giman, B, Lynam, A.') $part = 'Giman, B. and Lynam, A.';
                if($part == 'Fahmi, Fernando, D.') $part = 'Fahmi and Fernando, D.';
                if($part == 'Dharmadi, Fahmi, Ho, H.') $part = 'Dharmadi and Fahmi and Ho, H.';
                if($part == 'Roth, B. (Mollusc RLA), Haaker, P.') $part = 'Roth, B. (Mollusc RLA) and Haaker, P.';
                if($part == 'Donaldson, J.S.; Bösenberg, J.D.') $part = 'Donaldson, J.S. and Bösenberg, J.D.';
                if($part == 'Vovides, A. Chemnick, J.') $part = 'Vovides, A. and Chemnick, J.';
                if($part == 'Zhang, D, Christian, T.') $part = 'Zhang, D. and Christian, T.';
                if($part == 'Zhang, D, Farjon, A.') $part = 'Zhang, D. and Farjon, A.';
                if($part == 'Luscombe, D, Carter, G.') $part = 'Luscombe, D. and Carter, G.';
                if($part == 'Rasoloariniaina, R, Randrianizahaisa, H.') $part = 'Rasoloariniaina, R. and Randrianizahaisa, H.';
                if($part == 'Nzabi, T. Onana, J.M.') $part = 'Nzabi, T. and Onana, J.M.';
                if($part == 'Members of the IUCN SSC Madagascar Plant Specialist Group, Faranirina, L.') $part = 'Members of the IUCN SSC Madagascar Plant Specialist Group and Faranirina, L.';
                if($part == 'Caramaschi, U, Mijares, A.') $part = 'Caramaschi, U. and Mijares, A.';
                if($part == 'Lu Shunqing, Yang Datong, Ohler, A.') $part = 'Lu Shunqing and Yang Datong and Ohler, A.';
                if($part == 'Yang Datong, Ohler, A.') $part = 'Yang Datong and Ohler, A.';
                if($part == 'Lu Shunqing, Dutta, S.') $part = 'Lu Shunqing and Dutta, S.';
                if($part == 'Yuan Zhigang, Zhao Ermi, Shi Haitao, Diesmos, A.') $part = 'Yuan Zhigang and Zhao Ermi and Shi Haitao and Diesmos, A.';
                if($part == 'Leong Tzi Ming, Yodchaiy Chuaynkern, Kumthorn Thirakhupt, Das, I.') $part = 'Leong Tzi Ming and Yodchaiy Chuaynkern and Kumthorn Thirakhupt and Das, I.';
                if($part == 'Solofoniaina, A, Razafindranaivo, V.') $part = 'Solofoniaina, A. and Razafindranaivo, V.';
                if($part == 'Clausnitzer, V.Clausnitzer, V.') $part = 'Clausnitzer, V. and Clausnitzer, V.';
                if($part == 'Clausnitzer, V.Suhling, F.') $part = 'Clausnitzer, V. and Suhling, F.';
                if($part == 'Dharmadi, Finucci, B.') $part = 'Dharmadi and Finucci, B.';
                if($part == 'Dharmadi, Fahmi, Fernando, D.') $part = 'Dharmadi and Fahmi and Fernando, D.';
                if($part == 'Dudley, S.F.J, Kyne, P.M.') $part = 'Dudley, S.F.J and Kyne, P.M.';
                if($part == 'Blanco-Parra, MP, Derrick, D.') $part = 'Blanco-Parra, MP and Derrick, D.';
                if($part == 'Lowe, C.G, Smith, W.D.') $part = 'Lowe, C.G. and Smith, W.D.';
                if($part == 'Augerot, X, Whorisky, F.') $part = 'Augerot, X. and Whorisky, F.';
                if($part == 'Notarbartolo di Sciara, G. Serena, F.') $part = 'Notarbartolo di Sciara, G. and Serena, F.';
                if($part == 'Ojeda Land, E,, Bañares Baudet, A.') $part = 'Ojeda Land, E. and Bañares Baudet, A.';
                if($part == 'Marrero-Rodríguez, Á, Naranjo-Suárez, J.') $part = 'Marrero-Rodríguez, Á. and Naranjo-Suárez, J.';
                if($part == 'Mora Vicente, S,. Urdiales Perales, N.') $part = 'Mora Vicente, S. and Urdiales Perales, N.';
                if($part == 'NatureServe, Lambarri Martínez, C.') $part = 'NatureServe and Lambarri Martínez, C.';
                if($part == 'Driggers, III, W.B.') $part = 'Driggers, III, W.B.';
                if($part == 'Blanco-Parra, MP, Chartrain, E.') $part = 'Blanco-Parra, MP and Chartrain, E.';
                if($part == 'Clausnitzer, V. Suhling, F.') $part = 'Clausnitzer, V. and Suhling, F.';
                if($part == 'Barkhuizen, L.M, Swartz, E.R.') $part = 'Barkhuizen, L.M. and Swartz, E.R.';
                if($part == 'Hoffman, A. , Bills, R.') $part = 'Hoffman, A. and Bills, R.';
                if($part == 'Hoffman, A. , Roux, F.') $part = 'Hoffman, A. and Roux, F.';
                if($part == 'Hoffman, A. , Engelbrecht, J.') $part = 'Hoffman, A. and Engelbrecht, J.';
                if($part == 'Curtis, B. Stensgaard, A-S.') $part = 'Curtis, B. and Stensgaard, A-S.';
                if($part == 'Brad Hollingsworth, Santos-Barrera, G.') $part = 'Brad Hollingsworth and Santos-Barrera, G.';
                if($part == 'Fenner, D, Richards, Z.') $part = 'Fenner, D. and Richards, Z.';
                if($part == 'Söderström, L. (IUCN SSC Bryophyte Red List Authority), Raimondo, D.') $part = 'Söderström, L. (IUCN SSC Bryophyte Red List Authority) and Raimondo, D.';
                if($part == 'Pollock, C.M. (IUCN Red List Unit), Ng, P.') $part = 'Pollock, C.M. (IUCN Red List Unit) and Ng, P.';
                if($part == 'Sidibé, A, Sylla, M.') $part = 'Sidibé, A. and Sylla, M.';
                if($part == 'Shekelle, M, Salim, M.') $part = 'Shekelle, M. and Salim, M.';
                if($part == 'Hamilton, S, Helgen, K.') $part = 'Hamilton, S. and Helgen, K.';
                if($part == 'Sunarto, Sanderson, J.') $part = 'Sunarto and Sanderson, J.';
                if($part == 'Brockelman, W, Das, J.') $part = 'Brockelman, W. and Das, J.';
                if($part == 'Fitz Maurice, B, Sánchez , E.') $part = 'Fitz Maurice, B. and Sánchez , E.';
                if($part == 'Collen, B. Richman, N.') $part = 'Collen, B. and Richman, N.';
                if($part == 'Rustamov, A, Sattorov, T.') $part = 'Rustamov, A. and Sattorov, T.';
                if($part == ', Tezcan, S.') $part = 'Tezcan, S.';
                if($part == 'Serena, F.,Ungaro, N.') $part = 'Serena, F. and Ungaro, N.';
                if($part == 'Coelho, R. Blasdale, T.') $part = 'Coelho, R. and Blasdale, T.';
                if($part == 'Dharmadi, Pacoureau, N.') $part = 'Dharmadi and Pacoureau, N.';
                if($part == 'Guallart, J.,Coelho, R.') $part = 'Guallart, J. and Coelho, R.';
                if($part == 'Cavanagh, R.D, Valenti, S.V, Dudley, S.') $part = 'Cavanagh, R.D. and Valenti, S.V. and Dudley, S.';
                if($part == 'Bertozzi, M.,Ungaro. N.') $part = 'Bertozzi, M. and Ungaro. N.';
                if($part == 'Fahmi, Ishihara, H.') $part = 'Fahmi and Ishihara, H.';
                if($part == 'Chiquillo, K.L.C, Crow, K.D.') $part = 'Chiquillo, K.L.C. and Crow, K.D.';
                if($part == 'Blanco-Parra, MP, Cardenosa, D.') $part = 'Blanco-Parra, MP and Cardenosa, D.';
                if($part == 'Dharmadi, Elhassan, I.') $part = 'Dharmadi and Elhassan, I.';
                if($part == 'Bown, RMK, Cheok, J.') $part = 'Bown, RMK and Cheok, J.';
                if($part == 'Dharmadi, Fahmi, Finucci, B.') $part = 'Dharmadi, Fahmi and Finucci, B.';
                if($part == 'Huveneers, C. Stehmann, M.') $part = 'Huveneers, C. and Stehmann, M.';
                if($part == 'Dharmadi, Fahmi, Tanay, D.') $part = 'Dharmadi, Fahmi and Tanay, D.';
                if($part == 'Blanco-Parra, MP, Charvet, P.') $part = 'Blanco-Parra, MP and Charvet, P.';
                if($part == 'Fahmi, Haque, A.B.') $part = 'Fahmi and Haque, A.B.';
                if($part == 'de Carvalho, R, McCord, M.') $part = 'de Carvalho, R. and McCord, M.';
                if($part == 'Carvalho, M.R. de, McCord, M.E.') $part = 'Carvalho, M.R. and de McCord, M.E.';
                if($part == 'Bradai, N. Serena, F.') $part = 'Bradai, N. and Serena, F.';
                if($part == 'Marrero-Rodríguez, Á, Peraza Zurita, M.D.') $part = 'Marrero-Rodríguez, Á. and Peraza Zurita, M.D.';
                if($part == 'Rogers, Alex, Bohm, M.') $part = 'Rogers, Alex and Bohm, M.';
                if($part == 'Zeineb Ghrabi, Rhazi, L.') $part = 'Zeineb Ghrabi and Rhazi, L.';
                if($part == 'Imtinene Ben Haj Jilani, de Bélair, G.') $part = 'Imtinene Ben Haj Jilani and de Bélair, G.';
                if($part == 'Fennane, M, García, N.') $part = 'Fennane, M. and García, N.';
                if($part == 'Zeineb Ghrabi, Limam-Ben Saad, S.') $part = 'Zeineb Ghrabi and Limam-Ben Saad, S.';
                if($part == 'Temple, H. (IUCN Species Programme), Rhazi, L.') $part = 'Temple, H. (IUCN Species Programme) and Rhazi, L.';
                if($part == 'Rustamov, A, Munkhbayar, K.') $part = 'Rustamov, A. and Munkhbayar, K.';
                if($part == 'Papenfuss, T. Shafiei Bafti, S.') $part = 'Papenfuss, T. and Shafiei Bafti, S.';
                if($part == 'Rustamov, A, Nuridjanov, D.') $part = 'Rustamov, A. and Nuridjanov, D.';
                if($part == 'Varol Tok, Ugurtas, I.') $part = 'Varol Tok and Ugurtas, I.';
                if($part == 'Kar, D, Rema Devi, K.R.') $part = 'Kar, D. and Rema Devi, K.R.';
                if($part == 'Kar, D, Juffe Bignoli, D.') $part = 'Kar, D. and Juffe Bignoli, D.';
                if($part == 'Reizl Jose, Juan Carlos Gonzales, Rico, E.') $part = 'Reizl Jose and Juan Carlos Gonzales and Rico, E.';
                if($part == 'Amaro, R, Negrão, R.') $part = 'Amaro, R. and Negrão, R.';
                if($part == 'Amaro, R, Guimarães, E.F.') $part = 'Amaro, R. and Guimarães, E.F.';
                if($part == 'Members of the IUCN SSC Madagascar Plant Specialist Group, Rabarimanarivo, M.') $part = 'Members of the IUCN SSC Madagascar Plant Specialist Group and Rabarimanarivo, M.';
                if($part == 'Ram, M. (Sampled Red List Index Coordinating Team), Tognelli, M.F.') $part = 'Ram, M. (Sampled Red List Index Coordinating Team) and Tognelli, M.F.';
                if($part == 'Cechin, C.T.Z, da Costa, T.B.G.') $part = 'Cechin, C.T.Z. and da Costa, T.B.G.';
                if($part == 'Murphy, J. Zug, G.R.') $part = 'Murphy, J. and Zug, G.R.';
                if($part == 'Sanders, K. Lobo, A.') $part = 'Sanders, K. and Lobo, A.';
                if($part == 'Dominici-Arosemena, A.,Bussing, W.') $part = 'Dominici-Arosemena, A. and Bussing, W.';
                if($part == 'Smith-Vaniz, B, Robertson, R.') $part = 'Smith-Vaniz, B. and Robertson, R.';
                if($part == 'Macdonald, S.M, Shea, G.') $part = 'Macdonald, S.M. and Shea, G.';
                if($part == 'O. Jin Eong, Wan-Hong Yong, J.') $part = 'O. Jin Eong and Wan-Hong Yong, J.';
                if($part == 'Toral-Granda, M.V, Benavides, M.') $part = 'Toral-Granda, M.V. and Benavides, M.';
                if($part == 'Toral-Granda, M.V, Paola Ortiz, E.') $part = 'Toral-Granda, M.V. and Paola Ortiz, E.';
                if($part == 'NatureServe, Sparks, J.S.') $part = 'NatureServe and Sparks, J.S.';
                if($part == 'van der Heiden, Lea, B.') $part = 'van der Heiden and Lea, B.';
                if($part == 'Rivera, R.,Edgar, G.') $part = 'Rivera, R. and Edgar, G.';
                if($part == 'Dominici-Arosemena, A.,, Bussing, W.') $part = 'Dominici-Arosemena, A. and Bussing, W.';
                if($part == 'Ghamizi, M.,Van Damme, D.') $part = 'Ghamizi, M. and Van Damme, D.';
                if($part == 'Williams, J. Craig, M.') $part = 'Williams, J. and Craig, M.';
                if($part == 'Quintana, Y,, McMahan, C.') $part = 'Quintana, Y. and McMahan, C.';
                if($part == 'Walker, KF, Klunzinger, M.') $part = 'Walker, KF and Klunzinger, M.';
                if($part == 'Massuti, E, Palmeri, A.') $part = 'Massuti, E. and Palmeri, A.';
                if($part == 'NatureServe, Hendrickson, D.') $part = 'NatureServe and Hendrickson, D.';
                if($part == 'Mouaxayvaneng, A,, Myint, W.') $part = 'Mouaxayvaneng, A. and Myint, W.';
                if($part == 'Botanic Gardens Conservation International (BGCI), IUCN SSC Global Tree Specialist Group, Strijk, J.S.') $part = 'Botanic Gardens Conservation International (BGCI) and IUCN SSC Global Tree Specialist Group and Strijk, J.S.';
                if($part == 'Sidibé, A, Nunoo, F.') $part = 'Sidibé, A. and Nunoo, F.';
                if($part == 'Sidibé, A, Quartey, R.') $part = 'Sidibé, A. and Quartey, R.';
                if($part == 'Sidibé, A, Djiman, R.') $part = 'Sidibé, A. and Djiman, R.';
                if($part == 'Allibone, R, Ling, N.') $part = 'Allibone, R. and Ling, N.';
                if($part == 'Sidibé, A, de Morais, L.') $part = 'Sidibé, A. and de Morais, L.';
                if($part == 'Allibone, R, West, D, Closs, G.') $part = 'Allibone, R. and West, D. and Closs, G.';
                if($part == 'Allibone, R, West, D, Franklin, P.') $part = 'Allibone, R. and West, D. and Franklin, P.';
                if($part == 'West, D, Allibone, R, Franklin, P.') $part = 'West, D. and Allibone, R. and Franklin, P.';
                if($part == 'Allibone, R, Hitchmough, R.') $part = 'Allibone, R. and Hitchmough, R.';
                if($part == 'Allibone, R, Franklin, P.') $part = 'Allibone, R. and Franklin, P.';
                if($part == 'West, D, Closs, G.') $part = 'West, D. and Closs, G.';
                if($part == 'Allibone, R, David, B.') $part = 'Allibone, R. and  David, B.';
                if($part == 'West, D, Hitchmough, R.') $part = 'West, D. and Hitchmough, R.';
                if($part == 'Sidibé, A, Mbye, E.') $part = 'Sidibé, A. and Mbye, E.';
                if($part == 'Vural, M.,Duman, H.') $part = 'Vural, M. and Duman, H.';
                if($part == 'Nakhutsrishvili, Kikodze, D.') $part = 'Nakhutsrishvili and Kikodze, D.';
                if($part == 'Sebsebe Demissew, Kelbessa, E.') $part = 'Sebsebe Demissew and Kelbessa, E.';
                if($part == 'Bassos-Hull, K, Blanco-Parra, MP, Chartrain, E.') $part = 'Bassos-Hull, K. and Blanco-Parra, MP and Chartrain, E.';
                if($part == 'Skejo, J. Skejo, Fontana, P.') $part = 'Skejo, J. Skejo and Fontana, P.';
                if($part == 'Smith-Vaniz, W.F, Hastings, P.A.') $part = 'Smith-Vaniz, W.F. and Hastings, P.A.';
                if($part == 'Botanic Gardens Conservation International (BGCI), IUCN SSC Global Tree Specialist Group, de Santiago, J.') $part = 'Botanic Gardens Conservation International (BGCI) and IUCN SSC Global Tree Specialist Group and de Santiago, J.';
                if($part == 'Quintana, Y,, Elias, D.') $part = 'Quintana, Y. and Elias, D.';
                if($part == 'Quintana, Y,, Fuentes, C.') $part = 'Quintana, Y. and Fuentes, C.';
                if($part == 'Geethakumary, M.P, Pandurangan, A.G, Haridasan, K.') $part = 'Geethakumary, M.P. and Pandurangan, A.G. and Haridasan, K.';
                if($part == 'Walker, KF, Jones, H. A.') $part = 'Walker, KF and Jones, H. A.';
                if($part == 'Rakotoarimanana, S, Rakotonirina, N.') $part = 'Rakotoarimanana, S. and Rakotonirina, N.';
                if($part == 'Andry My Aina, A.A.A, Letsara, R.') $part = 'Andry My Aina, A.A.A. and Letsara, R.';
                if($part == 'Andry My Aina, A.A.A, Randrianasolo, V.') $part = 'Andry My Aina, A.A.A. and Randrianasolo, V.';
                if($part == 'Letsara, LRK, Rajaonary, F.') $part = 'Letsara, LRK and Rajaonary, F.';
                if($part == 'Skejo, J. Skejo, Pushkar, T.') $part = 'Skejo, J. Skejo and Pushkar, T.';
                if($part == 'Andry My Aina, A.A.A, Razafindrahaja, V.') $part = 'Andry My Aina, A.A.A. and Razafindrahaja, V.';
                if($part == 'Andry My Aina, A.A.A, Rakotonirina, N.') $part = 'Andry My Aina, A.A.A. and Rakotonirina, N.';
                if($part == 'Rakotoarimanana, S, Andriamanohera, A.M.') $part = 'Rakotoarimanana, S. and Andriamanohera, A.M.';
                if($part == 'Rakotoarimanana, S, Rabehevitra, A.D.') $part = 'Rakotoarimanana, S. and Rabehevitra, A.D.';
                if($part == 'Hallingbäck, T, Hedenäs, L.') $part = 'Hallingbäck, T. and Hedenäs, L.';
                if($part == 'Hallingbäck, T, Ignatov, M.') $part = 'Hallingbäck, T. and Ignatov, M.';
                if($part == 'Yulintine, Ho, J.K.I.') $part = 'Yulintine and Ho, J.K.I.';
                if($part == 'Dharmadi, Gutteridge, A.N.') $part = 'Dharmadi and Gutteridge, A.N.';
                if($part == 'Dharmadi, Grant, I.') $part = 'Dharmadi and Grant, I.';
                if($part == 'Blanco-Parra, MP, Espinoza, E.') $part = 'Blanco-Parra, MP and Espinoza, E.';
                if($part == 'Nurainas , Docot, R.V.A.') $part = 'Nurainas and Docot, R.V.A.';
                if($part == 'Thinh Van Ngoc, Roos, C.') $part = 'Thinh Van Ngoc and Roos, C.';
                if($part == 'Hoang Minh Duc, Nijman, V.') $part = 'Hoang Minh Duc and Nijman, V.';
                if($part == 'Fahmi, Finucci, B.') $part = 'Fahmi and Finucci, B.';
                if($part == 'Mouaxayvaneng, A,, Soulinnaphou, S.') $part = 'Mouaxayvaneng, A. and Soulinnaphou, S.';
                if($part == 'Botanic Gardens Conservation International (BGCI), IUCN SSC Global Tree Specialist Group, González-Espinosa, M.') $part = 'Botanic Gardens Conservation International (BGCI) and IUCN SSC Global Tree Specialist Group and González-Espinosa, M.';
                if($part == 'Botanic Gardens Conservation International (BGCI), IUCN SSC Global Tree Specialist Group, Cornejo-Tenorio, G.') $part = 'Botanic Gardens Conservation International (BGCI) and IUCN SSC Global Tree Specialist Group and Cornejo-Tenorio, G.';
                if($part == 'Botanic Gardens Conservation International (BGCI), IUCN SSC Global Tree Specialist Group, Ibarra-Manríquez, G.') $part = 'Botanic Gardens Conservation International (BGCI) and IUCN SSC Global Tree Specialist Group and Ibarra-Manríquez, G.';
                if($part == 'Kromer, TK, Fuentes Claros, A.') $part = 'Kromer, TK and Fuentes Claros, A.';
                if($part == 'Kromer, TK, Mercado Ustariz, J.') $part = 'Kromer, TK and Mercado Ustariz, J.';
                if($part == 'Kromer, TK, Meneses, R.') $part = 'Kromer, TK and Meneses, R.';
                if($part == 'Mohd Hairul, MA, Imin, K, Kiew, R.') $part = 'Mohd Hairul, MA and Imin, K. and Kiew, R.';
                if($part == 'Matusin, Dg Ku Rozianah, M.') $part = 'Matusin, Dg Ku Rozianah';
                if($part == 'Mohd Yusof, Nur Adillah, M.Y.') $part = 'Mohd Yusof and Nur Adillah, M.Y.';
                if($part == 'Botanic Gardens Conservation International (BGCI), IUCN SSC Global Tree Specialist Group, Meave, J.A.') $part = 'Botanic Gardens Conservation International (BGCI) and IUCN SSC Global Tree Specialist Group and Meave, J.A.';
                if($part == 'Botanic Gardens Conservation International (BGCI), IUCN SSC Global Tree Specialist Group, Ramírez-Marcial, N.') $part = 'Botanic Gardens Conservation International (BGCI) and IUCN SSC Global Tree Specialist Group and Ramírez-Marcial, N.';
                if($part == 'Botanic Gardens Conservation International (BGCI), IUCN SSC Global Tree Specialist Group, Valencia-Ávalos, S.') $part = 'Botanic Gardens Conservation International (BGCI) and IUCN SSC Global Tree Specialist Group and Valencia-Ávalos, S.';
                if($part == 'Botanic Gardens Conservation International (BGCI), IUCN SSC Global Tree Specialist Group, González-Espinosa, M, Sánchez-Velázquez, L.') $part = 'Botanic Gardens Conservation International (BGCI) and IUCN SSC Global Tree Specialist Group and González-Espinosa, M. and Sánchez-Velázquez, L.';
                if($part == 'Botanic Gardens Conservation International (BGCI), IUCN SSC Global Tree Specialist Group, Martínez-Gordilllo, J.') $part = 'Botanic Gardens Conservation International (BGCI) and IUCN SSC Global Tree Specialist Group and Martínez-Gordilllo, J.';
                if($part == 'Botanic Gardens Conservation International (BGCI), IUCN SSC Global Tree Specialist Group, González-Espinosa, M, Pineda-López, M.') $part = 'Botanic Gardens Conservation International (BGCI) and IUCN SSC Global Tree Specialist Group and González-Espinosa, M. and Pineda-López, M.';
                if($part == 'Botanic Gardens Conservation International (BGCI), IUCN SSC Global Tree Specialist Group, González-Espinosa, M, Calónico-Soto, J.') $part = 'Botanic Gardens Conservation International (BGCI) and IUCN SSC Global Tree Specialist Group and González-Espinosa, M. and Calónico-Soto, J.';
                if($part == 'Botanic Gardens Conservation International (BGCI), IUCN SSC Global Tree Specialist Group, Luna Vega, I.') $part = 'Botanic Gardens Conservation International (BGCI) and IUCN SSC Global Tree Specialist Group and Luna Vega, I.';
                if($part == 'Rakotoarimanana, S, Rakotoarinivo, M.') $part = 'Rakotoarimanana, S. and Rakotoarinivo, M.';
                if($part == 'Andry My Aina, A.A.A, Ravololomanana, N.') $part = 'Andry My Aina, A.A.A. and Ravololomanana, N.';
                if($part == 'Andry My Aina, A.A.A, Razafiniary, V.') $part = 'Andry My Aina, A.A.A. and Razafiniary, V.';
                if($part == 'Andrianarivelo Fanantenana, S.A.F, Rajaonarivelo, P.') $part = 'Andrianarivelo Fanantenana, S.A.F. and Rajaonarivelo, P.';
                if($part == 'Trillium Working Group 2019, Farmer, S.B.') $part = 'Trillium Working Group 2019 and Farmer, S.B.';
                if($part == 'Botanic Gardens Conservation International (BGCI), IUCN SSC Global Tree Specialist Group, Foden, W.') $part = 'Botanic Gardens Conservation International (BGCI) and IUCN SSC Global Tree Specialist Group and Foden, W.';
                if($part == 'Botanic Gardens Conservation International (BGCI), IUCN SSC Global Tree Specialist Group, von Staden, L.') $part = 'Botanic Gardens Conservation International (BGCI) and IUCN SSC Global Tree Specialist Group and von Staden, L.';
                if($part == 'Botanic Gardens Conservation International (BGCI), IUCN SSC Global Tree Specialist Group, Archer, R.') $part = 'Botanic Gardens Conservation International (BGCI) and IUCN SSC Global Tree Specialist Group and Archer, R.';
                if($part == 'Botanic Gardens Conservation International (BGCI), IUCN SSC Global Tree Specialist Group, Schutte-Vlok, A.L.') $part = 'Botanic Gardens Conservation International (BGCI) and IUCN SSC Global Tree Specialist Group and Schutte-Vlok, A.L.';
                if($part == 'IUCN SSC Global Tree Specialist Group, Botanic Gardens Conservation International (BGCI), Qin, H.N.') $part = 'IUCN SSC Global Tree Specialist Group and Botanic Gardens Conservation International (BGCI) and Qin, H.N.';
                if($part == 'Kadarusman, Ralph, G.') $part = 'Kadarusman and Ralph, G.';
                if($part == 'Guino-o, R.S. II, Leander, N.J.S.') $part = 'Guino-o, R.S. II and Leander, N.J.S.';
                if($part == 'Guino-o, R.S. II, Cecilio, M.A.F.') $part = 'Guino-o, R.S. II and Cecilio, M.A.F.';
                if($part == 'Dharmadi, Dulvy, N.K.') $part = 'Dharmadi and Dulvy, N.K.';
                if($part == 'Márquez-Corro, J.I, Allen, D.J.') $part = 'Márquez-Corro, J.I. and Allen, D.J.';
                if($part == 'Edwards, C.T.T, Maisels, F.') $part = 'Edwards, C.T.T. and Maisels, F.';
                if($part == 'Edwards, C.T.T, Balfour, D.') $part = 'Edwards, C.T.T. and Balfour, D.';
                if($part == 'Amaro, R, Martinelli, G.') $part = 'Amaro, R. and Martinelli, G.';
                if($part == "WWF-Malaysia, Roberton, S.") $part = "WWF-Malaysia and Roberton, S.";

                if($part == 'WS), Down, T.') $part = 'Down, T.';
                if($part == 'Barkhuizen, L.M,') $part = 'Barkhuizen, L.M.';
                if($part == 'de, McCord, M.E.') $part = 'de McCord, M.E.';
                if($part == 'Matusin, Dg Ku Rozianah, M.') $part = 'Matusin, Dg Ku Rozianah';
                
                // if($part == 'Jackson, III, J.J.') $part = 'Jackson, III, J.J.';] => 
                // if($part == 'Driggers, III, W.B.') $part = 'Driggers, III, W.B.';] => 
                
                $arr2 = explode(" and ", $part);
                foreach($arr2 as $item) $final2[trim($item)] = '';
            }
        }
        return array_keys($final2);
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
            case "terrestrial": return "http://purl.obolibrary.org/obo/ENVO_00000446"; //"http://purl.obolibrary.org/obo/ENVO_00002009";
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
        exit("\nUn-mapped value [$cat]\n");
        return false;
    }
    private function get_details($taxon)
    {
        $rec = array();
        foreach($taxon->dataObjects as $o) {
            if($o->title == "IUCNConservationStatus")       $rec["RedListCategory"] = $o->description;
            /* obsolete
            elseif($o->title == "IUCN Red List Assessment") $rec["texts"]           = self::parse_assessment_info($o->description);
            elseif($o->title == "Habitat and Ecology")      $rec["habitats"]        = self::parse_habitat_info($o->description);
            elseif($o->title == "Population")               $rec["pop_trend"]       = $o->description; //self::parse_population_trend($o->description);
            */
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
    private function add_string_types($measurementOfTaxon, $rec, $label, $value, $mtype, $measurementRemarks = null, $parentMeasurementID = '')
    {
        // echo "\n [$label]:[$value]\n";
        $taxon_id = $rec["taxon_id"];
        $catnum = $rec["catnum"];
        $m = new \eol_schema\MeasurementOrFact();
        
        if($measurementOfTaxon == "true") {
            $locality = '';
            if($mtype == 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#ConservationStatus') $locality = $rec['locality'];
            $occurrence_id = $this->add_occurrence($taxon_id, $catnum, $locality);
            $m->occurrenceID = $occurrence_id;
        }
        else $m->parentMeasurementID = $parentMeasurementID;
        
        if($mtype)  $m->measurementType = $mtype;
        else { exit("\ndoesn't go here anymore\n");
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
        
        if($val = @$rec['measurementDeterminedDate']) $m->measurementDeterminedDate = $val;
        if($val = @$rec['contributor']) $m->contributor = $val;
        
        // $m->measurementID = Functions::generate_measurementID($m, $this->resource_id, 'measurement', array('occurrenceID', 'measurementType', 'measurementValue'));
        $m->measurementID = Functions::generate_measurementID($m, $this->resource_id);
        if(!isset($this->measurementIDs[$m->measurementID])) {
            $this->archive_builder->write_object_to_file($m);
            $this->measurementIDs[$m->measurementID] = '';
        }
        return $m->measurementID;
    }
    private function add_occurrence($taxon_id, $catnum, $locality)
    {
        $occurrence_id = $taxon_id . 'O' . $catnum;
        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = $occurrence_id;
        $o->taxonID = $taxon_id;
        $o->locality = $locality;

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
