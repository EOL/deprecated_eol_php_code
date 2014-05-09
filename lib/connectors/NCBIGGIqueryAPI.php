<?php
namespace php_active_record;
/* connector: [723] NCBI GGI queries (DATA-1369)
              [730] GGBN Queries for GGI  (DATA-1372)
              [731] GBIF records (DATA-1370)
              [743, 747] Create a resource for more Database Coverage data (BHL and BOLD info) (DATA-1417)

#==== 5 AM, every 4th day of the month -- [Number of sequences in GenBank (DATA-1369)]
00 05 4 * * /usr/bin/php /opt/eol_php_code/update_resources/connectors/723.php > /dev/null

#==== 5 AM, every 5th day of the month -- [Number of DNA and specimen records in GGBN (DATA-1372)]
00 05 5 * * /usr/bin/php /opt/eol_php_code/update_resources/connectors/730.php > /dev/null

#==== 5 AM, every 6th day of the month -- [Number of records in GBIF (DATA-1370)]
00 05 6 * * /usr/bin/php /opt/eol_php_code/update_resources/connectors/731.php > /dev/null

#==== 5 AM, every 7th day of the month -- [Number of pages in BHL (DATA-1417)]
00 05 7 * * /usr/bin/php /opt/eol_php_code/update_resources/connectors/743.php > /dev/null

#==== 5 AM, every 8th day of the month -- [Number of specimens with sequence in BOLDS (DATA-1417)]
00 05 8 * * /usr/bin/php /opt/eol_php_code/update_resources/connectors/747.php > /dev/null

*/
class NCBIGGIqueryAPI
{
    function __construct($folder = null, $query = null)
    {
        if($folder)
        {
            $this->query = $query;
            $this->taxa = array();
            $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
            $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
            $this->occurrence_ids = array();
        }
        $this->download_options = array('download_wait_time' => 2000000, 'timeout' => 10800, 'download_attempts' => 1);

        // local
        $this->families_list = "http://localhost/~eolit/cp/NCBIGGI/falo2.in";
        $this->families_list = "https://dl.dropboxusercontent.com/u/7597512/NCBI_GGI/falo2.in";

        // NCBI service
        $this->family_service_ncbi = "http://www.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?db=nucleotide&usehistory=y&term=";
        // $this->family_service_ncbi = "http://eutils.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?db=nucleotide&usehistory=y&term=";
        /* to be used if u want to get all Id's, that is u will loop to get all Id's so server won't be overwhelmed: &retmax=10&retstart=0 */
        
        // GGBN data portal:
        $this->family_service_ggbn = "http://www.dnabank-network.org/Query.php?family="; // original
        $this->family_service_ggbn = "http://data.ggbn.org/Query.php?family="; // "Dröge, Gabriele" <g.droege@bgbm.org> advised to use this instead, Apr 17, 2014
        
        //GBIF services
        $this->gbif_taxon_info = "http://api.gbif.org/v0.9/species/match?name="; //http://api.gbif.org/v0.9/species/match?name=felidae&kingdom=Animalia
        $this->gbif_record_count = "http://api.gbif.org/v0.9/occurrence/count?taxonKey=";
        
        // BHL services
        $this->bhl_taxon_page = "http://www.biodiversitylibrary.org/name/";
        $this->bhl_taxon_in_csv = "http://www.biodiversitylibrary.org/namelistdownload/?type=c&name=";
        $this->bhl_taxon_in_xml = "http://www.biodiversitylibrary.org/api2/httpquery.ashx?op=NameGetDetail&apikey=deabdd14-65fb-4cde-8c36-93dc2a5de1d8&name=";
        
        // BOLDS portal
        $this->bolds_taxon_page = "http://www.boldsystems.org/index.php/Taxbrowser_Taxonpage?searchTax=&taxon=";
        
        // stats
        $this->TEMP_DIR = create_temp_dir() . "/";
        $this->names_no_entry_from_partner_dump_file = $this->TEMP_DIR . "names_no_entry_from_partner.txt";
        $this->name_from_eol_api_dump_file = $this->TEMP_DIR . "name_from_eol_api.txt";
        
        /* // FALO report
        $this->names_in_falo_but_not_in_irmng = $this->TEMP_DIR . "families_in_falo_but_not_in_irmng.txt";
        $this->names_in_irmng_but_not_in_falo = $this->TEMP_DIR . "families_in_irmng_but_not_in_falo.txt";
        */
        
        $this->ggi_databases = array("ncbi", "ggbn", "gbif", "bhl", "bolds");
        $this->ggi_path = DOC_ROOT . "temp/GGI/";
        
        $this->eol_api["search"]    = "http://eol.org/api/search/1.0.json?page=1&exact=true&filter_by_taxon_concept_id=&filter_by_hierarchy_entry_id=&filter_by_string=&cache_ttl=&q=";
        $this->eol_api["page"][0]   = "http://eol.org/api/pages/1.0/";
        $this->eol_api["page"][1]   = ".json?images=0&videos=0&sounds=0&maps=0&text=0&iucn=false&subjects=overview&licenses=all&details=true&common_names=false&synonyms=false&references=false&vetted=1&cache_ttl=";
        $this->databases_to_check_eol_api["ncbi"] = "NCBI Taxonomy";
        $this->databases_to_check_eol_api["gbif"] = "GBIF Nub Taxonomy";
        $this->databases_to_check_eol_api["bolds"] = "-BOLDS-";
    }

    function get_all_taxa()
    {
        self::initialize_files();
        /* 
        $families = self::get_families_from_google_spreadsheet(); Google spreadsheets are very slow, it is better to use Dropbox for our online spreadsheets
        $families = self::get_families(); use to read a plain text file
        $families = self::get_families_with_missing_data_xlsx(); - utility
        */
        if($families = self::get_families_xlsx())
        {
            foreach($this->ggi_databases as $database)
            {
                self::create_instances_from_taxon_object($families, false, $database);
                $this->families_with_no_data = array_keys($this->families_with_no_data);
                if($this->families_with_no_data) self::create_instances_from_taxon_object($this->families_with_no_data, true, $database);
            }
            self::compare_previuos_and_current_dumps();
            $this->create_archive();
        }
        echo "\n temp dir: " . $this->TEMP_DIR . "\n";
        // remove temp dir
        recursive_rmdir($this->TEMP_DIR);
        debug("\n temporary directory removed: " . $this->TEMP_DIR);
    }

    private function initialize_files()
    {
        if(!file_exists($this->ggi_path)) mkdir($this->ggi_path);
        foreach($this->ggi_databases as $database)
        {
            $this->ggi_text_file[$database]["previous"] = $this->ggi_path . $database  . ".txt";
            if(!file_exists($this->ggi_text_file[$database]["previous"])) self::initialize_dump_file($this->ggi_text_file[$database]["previous"]);
            //initialize current batch
            $this->ggi_text_file[$database]["current"] = $this->ggi_path . $database  . "_working.txt";
            self::initialize_dump_file($this->ggi_text_file[$database]["current"]);
        }
    }
    
    private function initialize_dump_file($file)
    {
        echo "\n initialize file:[$file]\n";
        $WRITE = fopen($file, "w");
        fclose($WRITE);
    }
    
    private function compare_previuos_and_current_dumps()
    {
        foreach($this->ggi_databases as $database)
        {
            $previous = $this->ggi_text_file[$database]["previous"];
            $current = $this->ggi_text_file[$database]["current"];
            if(self::count_rows($current) >= self::count_rows($previous))
            {
                self::process_text_file($current, $database);
                unlink($previous);
                rename($current, $previous);
            }
            else self::process_text_file($previous, $database);
        }
    }

    private function count_rows($file)
    {
        echo "\n counting: [$file]";
        $i = 0;
        if($handle = fopen($file, "r"))
        {
            while(!feof($handle))
            {
                $line = fgets($handle);
                $i++;
            }
            fclose($handle);
        }
        $i = $i - 1;
        echo "\n total: [$i]\n";
        return $i;
    }

    private function process_text_file($filename, $database)
    {
        foreach(new FileIterator($filename) as $line_number => $line)
        {
            if($line)
            {
                $line = trim($line);
                $values = explode("\t", $line);
                if(count($values) != 7)
                {
                    echo "\n investigate: wrong no. of tabs";
                    print_r($values);
                }
                else
                {
                    $family             = $values[0];
                    $count              = $values[1];
                    $rec["taxon_id"]    = $values[2];
                    $rec["object_id"]   = $values[3];
                    $rec["source"]      = $values[4];
                    $label              = $values[5];
                    $measurement        = $values[6];
                    self::add_string_types($rec, $label, $count, $measurement, $family);
                }
            }
        }
    }
    
    private function create_instances_from_taxon_object($families, $is_subfamily = false, $database)
    {
        $this->families_with_no_data = array();
        $i = 0;
        $total = count($families);
        foreach($families as $family)
        {
            $i++;
            /* breakdown when caching
            $cont = false;
            // if($i >= 1 && $i < 1000)     $cont = true;
            // if($i >= 1000 && $i < 2000)  $cont = true;
            // if($i >= 2000 && $i < 3000)  $cont = true;
            // if($i >= 3000 && $i < 4000)  $cont = true;
            // if($i >= 4000 && $i < 5000)  $cont = true;
            // if($i >= 5000 && $i < 6000)  $cont = true;
            // if($i >= 6000 && $i < 7000)  $cont = true;
            // if($i >= 7000 && $i < 8000)  $cont = true;
            // if($i >= 8000 && $i < 9000)  $cont = true;
            // if($i >= 9000 && $i < 10000) $cont = true;
            // if(in_array($database, array("bhl", "bolds"))) { if($i >= 1 && $i < 100) $cont = true; }
            // else                                           { if($i >= 1 && $i < 10) $cont = true; }
            if(!$cont) continue;
            */
            echo "\n $i of $total - [$family]\n";
            if    ($database == "ncbi")  $with_data = self::query_family_NCBI_info($family, $is_subfamily, $database);
            elseif($database == "ggbn")  $with_data = self::query_family_GGBN_info($family, $is_subfamily, $database);
            elseif($database == "gbif")  $with_data = self::query_family_GBIF_info($family, $is_subfamily, $database);
            elseif($database == "bhl")   $with_data = self::query_family_BHL_info($family, $is_subfamily, $database);
            elseif($database == "bolds") $with_data = self::query_family_BOLDS_info($family, $is_subfamily, $database);
            
            if(($is_subfamily && $with_data) || !$is_subfamily)
            {
                $taxon = new \eol_schema\Taxon();
                $taxon->taxonID         = $family;
                $taxon->scientificName  = $family;
                if(!$is_subfamily) $taxon->taxonRank = "family";
                $this->taxa[$taxon->taxonID] = $taxon;
            }
        }
    }

    private function query_family_BOLDS_info($family, $is_subfamily, $database)
    {
        $rec["family"] = $family;
        $rec["taxon_id"] = $family;
        $rec["source"] = $this->bolds_taxon_page . $family;
        if($contents = Functions::lookup_with_cache($rec["source"], $this->download_options))
        {
            if($info = self::get_page_count_from_BOLDS_page($contents))
            {
                if(@$info["specimens"] > 0)
                {
                    $rec["object_id"]   = "_no_of_rec_in_bolds";
                    $rec["count"]       = $info["specimens"];
                    $rec["label"]       = "Number records in BOLDS";
                    $rec["measurement"] = "http://eol.org/schema/terms/NumberRecordsInBOLD";
                    self::save_to_dump($rec, $this->ggi_text_file[$database]["current"]);
                    $rec["object_id"]   = "_rec_in_bolds";
                    $rec["count"]       = "http://eol.org/schema/terms/yes";
                    $rec["label"]       = "Records in BOLDS";
                    $rec["measurement"] = "http://eol.org/schema/terms/RecordInBOLD";
                    self::save_to_dump($rec, $this->ggi_text_file[$database]["current"]);
                }
                else
                {
                    if(!$is_subfamily)
                    {
                        $rec["object_id"] = "_no_of_rec_in_bolds";
                        self::add_string_types($rec, "Number records in BOLDS", 0, "http://eol.org/schema/terms/NumberRecordsInBOLD", $family);
                        $rec["object_id"] = "_rec_in_bolds";
                        self::add_string_types($rec, "Records in BOLDS", "http://eol.org/schema/terms/no", "http://eol.org/schema/terms/RecordInBOLD", $family);
                    }
                }
                if(@$info["public records"] > 0)
                {
                    $rec["object_id"]   = "_no_of_public_rec_in_bolds";
                    $rec["count"]       = $info["public records"];
                    $rec["label"]       = "Number public records in BOLDS";
                    $rec["measurement"] = "http://eol.org/schema/terms/NumberPublicRecordsInBOLD";
                    self::save_to_dump($rec, $this->ggi_text_file[$database]["current"]);
                }
                else
                {
                    if(!$is_subfamily)
                    {
                        $rec["object_id"] = "_no_of_public_rec_in_bolds";
                        self::add_string_types($rec, "Number public records in BOLDS", 0, "http://eol.org/schema/terms/NumberPublicRecordsInBOLD", $family);
                    }
                }
                if(@$info["specimens"] > 0 || @$info["public records"] > 0) return true;
            }
            else self::save_to_dump($family, $this->names_no_entry_from_partner_dump_file);
        }
        else self::save_to_dump($family, $this->names_no_entry_from_partner_dump_file);

        if(!$is_subfamily)
        {
            $rec["object_id"] = "_no_of_rec_in_bolds";
            self::add_string_types($rec, "Number records in BOLDS", 0, "http://eol.org/schema/terms/NumberRecordsInBOLD", $family);
            $rec["object_id"] = "_rec_in_bolds";
            self::add_string_types($rec, "Records in BOLDS", "http://eol.org/schema/terms/no", "http://eol.org/schema/terms/RecordInBOLD", $family);
            $rec["object_id"] = "_no_of_public_rec_in_bolds";
            self::add_string_types($rec, "Number public records in BOLDS", 0, "http://eol.org/schema/terms/NumberPublicRecordsInBOLD", $family);
        }
        if(!self::has_diff_family_name_in_eol_api($family, $database)) self::check_for_sub_family($family);
        return false;
    }
    
    private function get_page_count_from_BOLDS_page($contents)
    {
        $info = array();
        if(preg_match("/Specimens with Sequences:<\/td>(.*?)<\/td>/ims", $contents, $arr))  $info["specimens"] = trim(strip_tags($arr[1]));
        if(preg_match("/Public Records:<\/td>(.*?)<\/td>/ims", $contents, $arr))            $info["public records"] = trim(strip_tags($arr[1]));
        return $info;
    }
    
    private function query_family_BHL_info($family, $is_subfamily, $database)
    {
        $rec["family"] = $family;
        $rec["taxon_id"] = $family;
        $rec["source"] = $this->bhl_taxon_page . $family;
        if($contents = Functions::lookup_with_cache($this->bhl_taxon_in_xml . $family, $this->download_options))
        {
            if($count = self::get_page_count_from_BHL_xml($contents))
            {
                if($count > 0)
                {
                    $rec["object_id"]   = "_no_of_page_in_bhl";
                    $rec["count"]       = $count;
                    $rec["label"]       = "Number pages in BHL";
                    $rec["measurement"] = "http://eol.org/schema/terms/NumberReferencesInBHL";
                    self::save_to_dump($rec, $this->ggi_text_file[$database]["current"]);
                    $rec["object_id"]   = "_page_in_bhl";
                    $rec["count"]       = "http://eol.org/schema/terms/yes";
                    $rec["label"]       = "Pages in BHL";
                    $rec["measurement"] = "http://eol.org/schema/terms/ReferenceInBHL";
                    self::save_to_dump($rec, $this->ggi_text_file[$database]["current"]);
                    return true;
                }
            }
            else
            {
                if($contents = Functions::lookup_with_cache($this->bhl_taxon_in_csv . $family, $this->download_options))
                {
                    if($count = self::get_page_count_from_BHL_csv($contents))
                    {
                        if($count > 0)
                        {
                            $rec["object_id"] = "_no_of_page_in_bhl";
                            $rec["count"]       = $count;
                            $rec["label"]       = "Number pages in BHL";
                            $rec["measurement"] = "http://eol.org/schema/terms/NumberReferencesInBHL";
                            self::save_to_dump($rec, $this->ggi_text_file[$database]["current"]);
                            $rec["object_id"]   = "_page_in_bhl";
                            $rec["count"]       = "http://eol.org/schema/terms/yes";
                            $rec["label"]       = "Pages in BHL";
                            $rec["measurement"] = "http://eol.org/schema/terms/ReferenceInBHL";
                            self::save_to_dump($rec, $this->ggi_text_file[$database]["current"]);
                            return true;
                        }
                    }
                    else self::save_to_dump($family, $this->names_no_entry_from_partner_dump_file);
                }
            }
        }
        else self::save_to_dump($family, $this->names_no_entry_from_partner_dump_file);

        if(!$is_subfamily)
        {
            $rec["object_id"] = "_no_of_page_in_bhl";
            self::add_string_types($rec, "Number pages in BHL", 0, "http://eol.org/schema/terms/NumberReferencesInBHL", $family);
            $rec["object_id"] = "_page_in_bhl";
            self::add_string_types($rec, "Pages in BHL", "http://eol.org/schema/terms/no", "http://eol.org/schema/terms/ReferenceInBHL", $family);
        }
        self::check_for_sub_family($family);
        return false;
    }

    private function get_page_count_from_BHL_xml($contents)
    {
        if(preg_match_all("/<PageID>(.*?)<\/PageID>/ims", $contents, $arr)) return count(array_unique($arr[1]));
        return false;
    }

    private function get_page_count_from_BHL_csv($contents)
    {
        $temp_path = temp_filepath();
        if($contents)
        {
            $file = fopen($temp_path, "w");
            fwrite($file, $contents);
            fclose($file);
        }
        $page_ids = array();
        $i = 0;
        $file = fopen($temp_path, "r");
        while(!feof($file))
        {
            $i++;
            if($i == 1) $fields = fgetcsv($file);
            else
            {
                $rec = array();
                $temp = fgetcsv($file);
                $k = 0;
                if(!$temp) continue;
                foreach($temp as $t)
                {
                    $rec[$fields[$k]] = $t;
                    $k++;
                }
                $parts = pathinfo($rec["Url"]);
                $page_ids[$parts["filename"]] = 1;
            }
        }
        fclose($file);
        unlink($temp_path);
        return count(array_keys($page_ids));
    }

    private function has_diff_family_name_in_eol_api($family, $database)
    {
        return false; // this function won't be used yet...until it is proven to increase coverage
        $canonical = "";
        if($json = Functions::lookup_with_cache($this->eol_api["search"] . $family, $this->download_options))
        {
            $json = json_decode($json, true);
            if($json["results"])
            {
                if($id = $json["results"][0]["id"])
                {
                    if($database == "bolds")
                    {
                        if($html = Functions::lookup_with_cache("http://eol.org/pages/$id/resources/partner_links", $this->download_options))
                        {
                            if(preg_match("/boldsystems\.org\/index.php\/Taxbrowser_Taxonpage\?taxid=(.*?)\"/ims", $html, $arr))
                            {
                                echo "\n bolds id: " . $arr[1] . "\n";
                                if($html = Functions::lookup_with_cache("http://www.boldsystems.org/index.php/Taxbrowser_Taxonpage?taxid=" . $arr[1], $this->download_options))
                                {
                                    if(preg_match("/BOLD Systems: Taxonomy Browser -(.*?)\{/ims", $html, $arr))
                                    {
                                        $canonical = trim($arr[1]);
                                        echo "\n Got from bolds.org: [" . $canonical . "]\n";
                                    }
                                    else echo "\n Nothing from bolds.org \n";
                                }
                                else echo "\n investigate bolds.org taxid:[" . $arr[1] . "]\n";
                            }
                            else echo "\n Nothing in Partner Links tab.";
                        }
                    }
                    else // ncbi, gbif
                    {
                        if($json = Functions::lookup_with_cache($this->eol_api["page"][0] . $id . $this->eol_api["page"][1], $this->download_options))
                        {
                            $json = json_decode($json, true);
                            foreach($json["taxonConcepts"] as $tc)
                            {
                                echo "\n -- [" . $tc["nameAccordingTo"] . "]\n";
                                if($this->databases_to_check_eol_api[$database] == $tc["nameAccordingTo"])
                                {
                                    $canonical = $tc["canonicalForm"];
                                }
                            }
                        }
                    }
                }
            }
        }
        echo "\n database:[$database]\n";
        echo "\n db taxonomy:[" . $this->databases_to_check_eol_api[$database] . "]\n";
        echo "\n canonical:[$canonical]\n";
        if($canonical && $canonical != $family)
        {
            echo "\n has diff name in eol api:[$canonical]\n";
            $this->families_with_no_data[$canonical] = 1;
            self::save_to_dump($canonical . "\t" . $database, $this->name_from_eol_api_dump_file);
            return true;
        }
        elseif($canonical == $family) echo "\n Result: Same name in FALO. \n";
        else echo "\n Result: No name found in EOL API or Partner Links tab. \n";
        return false;
    }

    private function query_family_GBIF_info($family, $is_subfamily, $database)
    {
        $rec["family"] = $family;
        $rec["taxon_id"] = $family;
        $rec["source"] = $this->gbif_taxon_info . $family;
        if($json = Functions::lookup_with_cache($this->gbif_taxon_info . $family, $this->download_options))
        {
            $json = json_decode($json);
            $usageKey = false;
            if(!isset($json->usageKey))
            {
                if(isset($json->note)) $usageKey = self::get_usage_key($family);
                else {} // e.g. Fervidicoccaceae
            }
            else $usageKey = trim((string) $json->usageKey);
            
            if($usageKey)
            {
                $count = Functions::lookup_with_cache($this->gbif_record_count . $usageKey, $this->download_options);
                if($count || strval($count) == "0")
                {
                    $rec["source"] = $this->gbif_record_count . $usageKey;
                    if($count > 0)
                    {
                        $rec["object_id"]   = "_no_of_rec_in_gbif";
                        $rec["count"]       = $count;
                        $rec["label"]       = "Number records in GBIF";
                        $rec["measurement"] = "http://eol.org/schema/terms/NumberRecordsInGBIF";
                        self::save_to_dump($rec, $this->ggi_text_file[$database]["current"]);
                        $rec["object_id"]   = "_rec_in_gbif";
                        $rec["count"]       = "http://eol.org/schema/terms/yes";
                        $rec["label"]       = "Records in GBIF";
                        $rec["measurement"] = "http://eol.org/schema/terms/RecordInGBIF";
                        self::save_to_dump($rec, $this->ggi_text_file[$database]["current"]);
                        return true;
                    }
                }
                else self::save_to_dump($family, $this->names_no_entry_from_partner_dump_file);
            }
        }
        else self::save_to_dump($family, $this->names_no_entry_from_partner_dump_file);

        if(!$is_subfamily)
        {
            $rec["object_id"] = "_no_of_rec_in_gbif";
            self::add_string_types($rec, "Number records in GBIF", 0, "http://eol.org/schema/terms/NumberRecordsInGBIF", $family);
            $rec["object_id"] = "_rec_in_gbif";
            self::add_string_types($rec, "Records in GBIF", "http://eol.org/schema/terms/no", "http://eol.org/schema/terms/RecordInGBIF", $family);
        }
        if(!self::has_diff_family_name_in_eol_api($family, $database)) self::check_for_sub_family($family);
        return false;
    }

    private function get_usage_key($family)
    {
        if($json = Functions::lookup_with_cache($this->gbif_taxon_info . $family . "&verbose=true", $this->download_options))
        {
            $usagekeys = array();
            $options = array();
            $json = json_decode($json);
            if(!isset($json->alternatives)) return false;
            foreach($json->alternatives as $rec)
            {
                if($rec->canonicalName == $family)
                {
                    $options[$rec->rank][] = $rec->usageKey;
                    $usagekeys[] = $rec->usageKey;
                }
            }
            if($options)
            {
                if(isset($options["FAMILY"])) return min($options["FAMILY"]);
                else return min($usagekeys);
            }
        }
        return false;
    }
    
    private function get_names_no_entry_from_partner()
    {
        $names = array();
        $dump_file = "/Users/eolit/Sites/eli/eol_php_code/tmp/gbif/names_no_entry_from_partner.txt";
        foreach(new FileIterator($dump_file) as $line_number => $line)
        {
            if($line) $names[$line] = "";
        }
        return array_keys($names);
    }

    private function save_to_dump($rec, $filename)
    {
        if(isset($rec["measurement"]) && is_array($rec))
        {
            $fields = array("family", "count", "taxon_id", "object_id", "source", "label", "measurement");
            $data = "";
            foreach($fields as $field) $data .= $rec[$field] . "\t";
            $WRITE = fopen($filename, "a");
            fwrite($WRITE, $data . "\n");
            fclose($WRITE);
        }
        else
        {
            $WRITE = fopen($filename, "a");
            if($rec && is_array($rec)) fwrite($WRITE, json_encode($rec) . "\n");
            else                       fwrite($WRITE, $rec . "\n");
            fclose($WRITE);
        }
    }

    private function query_family_GGBN_info($family, $is_subfamily, $database)
    {
        $records = array();
        $rec["family"] = $family;
        $rec["source"] = $this->family_service_ggbn . $family;
        if($html = Functions::lookup_with_cache($rec["source"], $this->download_options))
        {
            $rec["taxon_id"] = $family;
            $has_data = false;
            if(preg_match("/<b>(.*?) entries found/ims", $html, $arr) || preg_match("/<b>(.*?) entry found/ims", $html, $arr))
            {
                if($arr[1] > 0)
                {
                    $rec["object_id"]   = "NumberDNAInGGBN";
                    $rec["count"]       = $arr[1];
                    $rec["label"]       = "Number of DNA records in GGBN";
                    $rec["measurement"] = "http://eol.org/schema/terms/NumberDNARecordsInGGBN";
                    self::save_to_dump($rec, $this->ggi_text_file[$database]["current"]);
                    $has_data = true;
                }
            }
            if(!$has_data)
            {
                if(!$is_subfamily)
                {
                    $rec["object_id"] = "NumberDNAInGGBN";
                    self::add_string_types($rec, "Number of DNA records in GGBN", 0, "http://eol.org/schema/terms/NumberDNARecordsInGGBN", $family);
                }
            }
            $pages = self::get_number_of_pages($html);
            for ($i = 1; $i <= $pages; $i++)
            {
                if($i > 1) $html = Functions::lookup_with_cache($this->family_service_ggbn . $family . "&page=$i", $this->download_options);
                if($temp = self::process_html($html)) $records = array_merge($records, $temp);
            }
            if($records)
            {
                $rec["object_id"] = "NumberSpecimensInGGBN";
                $rec["count"] = count($records);
                $rec["label"] = "NumberSpecimensInGGBN";
                $rec["measurement"] = "http://eol.org/schema/terms/NumberSpecimensInGGBN";
                self::save_to_dump($rec, $this->ggi_text_file[$database]["current"]);

                $rec["object_id"] = "SpecimensInGGBN";
                $rec["count"] = "http://eol.org/schema/terms/yes";
                $rec["label"] = "SpecimensInGGBN";
                $rec["measurement"] = "http://eol.org/schema/terms/SpecimensInGGBN";
                self::save_to_dump($rec, $this->ggi_text_file[$database]["current"]);
            }
            else
            {
                if(!$is_subfamily)
                {
                    $rec["object_id"] = "NumberSpecimensInGGBN";
                    self::add_string_types($rec, "NumberSpecimensInGGBN", 0, "http://eol.org/schema/terms/NumberSpecimensInGGBN", $family);
                    $rec["object_id"] = "SpecimensInGGBN";
                    self::add_string_types($rec, "SpecimensInGGBN", "http://eol.org/schema/terms/no", "http://eol.org/schema/terms/SpecimensInGGBN", $family);
                }
            }
            if($records || $has_data) return true;
        }
        if(!$is_subfamily)
        {
            $rec["object_id"] = "NumberSpecimensInGGBN";
            self::add_string_types($rec, "NumberSpecimensInGGBN", 0, "http://eol.org/schema/terms/NumberSpecimensInGGBN", $family);
            $rec["object_id"] = "SpecimensInGGBN";
            self::add_string_types($rec, "SpecimensInGGBN", "http://eol.org/schema/terms/no", "http://eol.org/schema/terms/SpecimensInGGBN", $family);
            $rec["object_id"] = "NumberDNAInGGBN";
            self::add_string_types($rec, "Number of DNA records in GGBN", 0, "http://eol.org/schema/terms/NumberDNARecordsInGGBN", $family);
        }
        self::check_for_sub_family($family);
        return false;
    }

    private function get_number_of_pages($html)
    {
        if(preg_match_all("/hitlist=true&page=(.*?)\"/ims", $html, $arr)) return array_pop(array_unique($arr[1]));
        return 1;
    }
    
    private function process_html($html)
    {
        $temp = array();
        $html = str_ireplace("<tr style='border-top-width:1px;border-top-style:solid;border-color:#CCCCCC'>", "<tr style='elix'>", $html);
        if(preg_match_all("/<tr style=\'elix\'>(.*?)<\/tr>/ims", $html, $arr))
        {
            foreach($arr[1] as $r)
            {
                $r = strip_tags($r, "<td>");
                if(preg_match_all("/<td valign=\'top\'>(.*?)<\/td>/ims", $r, $arr2)) $temp[] = $arr2[1][2]; //get last coloumn (specimen no.)
            }
        }
        return array_unique($temp);
    }

    private function query_family_NCBI_info($family, $is_subfamily, $database)
    {
        $rec["family"] = $family;
        $rec["source"] = $this->family_service_ncbi . $family;
        $rec["taxon_id"] = $family;
        $rec["object_id"] = "_no_of_seq_in_genbank";
        $contents = Functions::lookup_with_cache($rec["source"], $this->download_options);
        if($xml = simplexml_load_string($contents))
        {
            if($xml->Count > 0)
            {
                $rec["count"]       = $xml->Count;
                $rec["label"]       = "Number Of Sequences In GenBank";
                $rec["measurement"] = "http://eol.org/schema/terms/NumberOfSequencesInGenBank";
                self::save_to_dump($rec, $this->ggi_text_file[$database]["current"]);
                return true;
            }
        }
        if(!$is_subfamily) self::add_string_types($rec, "Number Of Sequences In GenBank", 0, "http://eol.org/schema/terms/NumberOfSequencesInGenBank", $family);
        if(!self::has_diff_family_name_in_eol_api($family, $database)) self::check_for_sub_family($family);
        return false;
    }

    private function add_string_types($rec, $label, $value, $measurementType, $family)
    {
        $taxon_id = (string) $rec["taxon_id"];
        $object_id = (string) $rec["object_id"];
        $m = new \eol_schema\MeasurementOrFact();
        $occurrence = $this->add_occurrence($taxon_id, $object_id);
        $m->occurrenceID        = $occurrence->occurrenceID;
        $m->measurementOfTaxon  = 'true';
        $m->source              = @$rec["source"];
        if($val = $measurementType) $m->measurementType = $val;
        else                        $m->measurementType = "http://ggbn.org/". SparqlClient::to_underscore($label);
        $m->measurementValue = (string) $value;
        $this->archive_builder->write_object_to_file($m);
    }

    private function add_occurrence($taxon_id, $object_id)
    {
        $occurrence_id = $taxon_id . 'O' . $object_id;
        if(isset($this->occurrence_ids[$occurrence_id])) return $this->occurrence_ids[$occurrence_id];
        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = $occurrence_id;
        $o->taxonID = $taxon_id;
        $this->archive_builder->write_object_to_file($o);
        $this->occurrence_ids[$occurrence_id] = $o;
        return $o;
    }

    private function create_archive()
    {
        foreach($this->taxa as $t) $this->archive_builder->write_object_to_file($t);
        $this->archive_builder->finalize(TRUE);
    }

    private function get_families_with_missing_data_xlsx() // utility
    {
        require_library('XLSParser');
        $parser = new XLSParser();
        $families = array();
        $dropbox_xlsx[] = "http://localhost/~eolit/cp/NCBIGGI/missing from GBIF.xlsx";
        foreach($dropbox_xlsx as $doc)
        {
            echo "\n processing [$doc]...\n";
            if($path = Functions::save_remote_file_to_local($doc, array("timeout" => 3600, "file_extension" => "xlsx", 'download_attempts' => 2, 'delay_in_minutes' => 2)))
            {
                $arr = $parser->convert_sheet_to_array($path);
                foreach($arr as $key => $fams)
                {
                    $fams[] = "Cenarchaeaceae";
                    foreach($fams as $family)
                    {
                        if($family) $families[$family] = 1;
                    }
                }
                unlink($path);
                break;
            }
            else echo "\n [$doc] unavailable! \n";
        }
        return array_keys($families);
    }

    private function get_families_from_google_spreadsheet()
    {
        $google_spreadsheets[] = array("title" => "FALO",                                            "column_number_to_return" => 16);
        $google_spreadsheets[] = array("title" => "falo_version 2.0.a.11_03-01-14 minus unassigned", "column_number_to_return" => 16);
        $google_spreadsheets[] = array("title" => "FALO_Version 2.0.a.1 minus unassigned",           "column_number_to_return" => 14);
        $sheet = array();
        foreach($google_spreadsheets as $doc)
        {
            echo "\n processing spreadsheet: " . $doc["title"] . "\n";
            if($sheet = Functions::get_google_spreadsheet(array("spreadsheet_title" => $doc["title"], "column_number_to_return" => $doc["column_number_to_return"], "timeout" => 999999)))
            {
                echo "\n successful process: " . $doc["title"] . "\n";
                break;
            }
            else echo "\n un-successful process: " . $doc["title"] . "\n";
        }
        if(!$sheet) return array();
        $families = array();
        foreach($sheet as $family)
        {
            $family = trim(str_ireplace(array("Family ", '"', "FAMILY"), "", $family));
            if(is_numeric($family)) continue;
            if($family) $families[$family] = 1;
        }
        return array_keys($families);
    }

    private function get_families()
    {
        $families = array();
        if(!$temp_path_filename = Functions::save_remote_file_to_local($this->families_list, $this->download_options)) return;
        foreach(new FileIterator($temp_path_filename) as $line_number => $line)
        {
            if($line)
            {
                $line = trim($line);
                $temp = explode("[", $line);
                $family = trim($temp[0]);
                $families[$family] = 1;
            }
        }
        unlink($temp_path_filename);
        return array_keys($families);
    }

    function falo_gbif_report()
    {
        require_library('connectors/IrmngAPI');
        $func = new IrmngAPI();
        $irmng_families = $func->get_irmng_families();
        $falo_families = self::get_families_xlsx();
        $names_in_falo_but_not_in_irmng = array_diff($falo_families, $irmng_families);
        $names_in_irmng_but_not_in_falo = array_diff($irmng_families, $falo_families);
        echo "\n falo_families:" . count($falo_families);
        echo "\n names_in_falo_but_not_in_irmng:" . count($names_in_falo_but_not_in_irmng);
        echo "\n irmng_families:" . count($irmng_families);
        echo "\n names_in_irmng_but_not_in_falo:" . count($names_in_irmng_but_not_in_falo);
        $names_in_falo_but_not_in_irmng = array_values($names_in_falo_but_not_in_irmng);
        $names_in_irmng_but_not_in_falo = array_values($names_in_irmng_but_not_in_falo);
        self::save_as_tab_delimited($names_in_falo_but_not_in_irmng, $this->names_in_falo_but_not_in_irmng);
        self::save_as_tab_delimited($names_in_irmng_but_not_in_falo, $this->names_in_irmng_but_not_in_falo);
        /*
            falo_families:9672
            names_in_falo_but_not_in_irmng:510
            irmng_families:19998
            names_in_irmng_but_not_in_falo:10836
        */
        // recursive_rmdir($this->TEMP_DIR);
    }
    
    private function save_as_tab_delimited($names, $file)
    {
        foreach($names as $name) self::save_to_dump($name, $file);
    }

    private function check_for_sub_family($family)
    {
        if(substr($family, -3) == "dae")
        {
            $family = str_replace("dae" . "xxx", "nae", $family . "xxx");
            $this->families_with_no_data[$family] = 1;
        }
        /* commented for now bec it is not improving the no. of records
        elseif(substr($family, -4) == "ceae")
        {
            $family = str_replace("ceae" . "xxx", "deae", $family . "xxx");
            $this->families_with_no_data[$family] = 1;
        }
        */
    }

    private function get_families_xlsx()
    {
        require_library('XLSParser');
        $parser = new XLSParser();
        $families = array();
        $dropbox_xlsx[] = "https://dl.dropboxusercontent.com/s/23em67jtf12gbe2/FALO.xlsx?dl=1&token_hash=AAGX4c2ontzzZDj57Ez6EFeDQPm_LrN7Ol85l_LOEMliJw&expiry=1399589083";
        // $dropbox_xlsx[] = "https://dl.dropboxusercontent.com/s/kqhru8pyc9ujktb/FALO.xlsx?dl=1&token_hash=AAEzlUqBxtGt8_iPX-1soVQ7m61K10w9LyxQIABeMg4LeQ"; // from Cyndy's Dropbox
        // $dropbox_xlsx[] = "https://dl.dropboxusercontent.com/s/9x3q0f7burh465k/FALO.xlsx?dl=1&token_hash=AAH94jgsY0_nI3F0MgaieWyU-2NpGpZFUCpQXER-dqZieg"; // from Eli's Dropbox
        // $dropbox_xlsx[] = "https://dl.dropboxusercontent.com/u/7597512/NCBI_GGI/FALO.xlsx"; // again from Eli's Dropbox
        // $dropbox_xlsx[] = "http://localhost/~eolit/cp/NCBIGGI/FALO.xlsx"; // local
        foreach($dropbox_xlsx as $doc)
        {
            echo "\n processing [$doc]...\n";
            if($path = Functions::save_remote_file_to_local($doc, array("cache" => 1, "timeout" => 3600, "file_extension" => "xlsx", 'download_attempts' => 2, 'delay_in_minutes' => 2)))
            {
                $arr = $parser->convert_sheet_to_array($path);
                foreach($arr["FAMILY"] as $family)
                {
                    $family = trim(str_ireplace(array("Family ", '"', "FAMILY"), "", $family));
                    if(is_numeric($family)) continue;
                    if($family) $families[$family] = 1;
                }
                unlink($path);
                break;
            }
            else echo "\n [$doc] unavailable! \n";
        }
        return array_keys($families);
    }

}
?>