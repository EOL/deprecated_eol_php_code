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
    function __construct($folder, $query)
    {
        $this->query = $query;
        $this->taxa = array();
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->occurrence_ids = array();
        $this->download_options = array('download_wait_time' => 3000000, 'timeout' => 10800, 'download_attempts' => 1);

        // local
        $this->families_list = "http://localhost/~eolit/cp/NCBIGGI/falo2.in";
        $this->families_list = "https://dl.dropboxusercontent.com/u/7597512/NCBI_GGI/falo2.in";
        
        // NCBI service
        $this->family_service_ncbi = "http://www.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?db=nucleotide&usehistory=y&term=";
        // $this->family_service_ncbi = "http://eutils.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?db=nucleotide&usehistory=y&term=";
        /* to be used if u want to get all Id's, that is u will loop to get all Id's so server won't be overwhelmed: &retmax=10&retstart=0 */
        
        // GGBN data portal:
        $this->family_service_ggbn = "http://www.dnabank-network.org/Query.php?family=";
        
        //GBIF services
        $this->gbif_taxon_info = "http://api.gbif.org/v0.9/species/match?name="; //http://api.gbif.org/v0.9/species/match?name=felidae&kingdom=Animalia
        $this->gbif_record_count = "http://api.gbif.org/v0.9/occurrence/count?nubKey=";
        
        // BHL services
        $this->bhl_taxon_page = "http://www.biodiversitylibrary.org/name/";
        $this->bhl_taxon_in_csv = "http://www.biodiversitylibrary.org/namelistdownload/?type=c&name=";
        $this->bhl_taxon_in_xml = "http://www.biodiversitylibrary.org/api2/httpquery.ashx?op=NameGetDetail&apikey=deabdd14-65fb-4cde-8c36-93dc2a5de1d8&name=";
        
        // BOLDS portal
        $this->bolds_taxon_page = "http://www.boldsystems.org/index.php/Taxbrowser_Taxonpage?searchTax=&taxon=";
        
        // stats
        $this->TEMP_DIR = create_temp_dir() . "/";
        $this->names_no_entry_from_partner_dump_file = $this->TEMP_DIR . "names_no_entry_from_partner.txt";
    }

    function get_all_taxa()
    {
        /* 
        $families = self::get_families_from_google_spreadsheet(); Google spreadsheets are very slow, it is better to use Dropbox for our online spreadsheets
        $families = self::get_families(); use to read a plain text file
        $families = self::get_families_with_missing_data_xlsx(); - utility
        */

        if($families = self::get_families_xlsx())
        {
            self::create_instances_from_taxon_object($families);
            $this->create_archive();
        }
        // remove temp dir
        recursive_rmdir($this->TEMP_DIR);
        debug("\n temporary directory removed: " . $this->TEMP_DIR);
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

    private function get_families_xlsx()
    {
        require_library('XLSParser');
        $parser = new XLSParser();
        $families = array();
        $dropbox_xlsx[] = "https://dl.dropboxusercontent.com/s/kqhru8pyc9ujktb/FALO.xlsx?dl=1&token_hash=AAEzlUqBxtGt8_iPX-1soVQ7m61K10w9LyxQIABeMg4LeQ"; // from Cyndy's Dropbox
        $dropbox_xlsx[] = "https://dl.dropboxusercontent.com/s/9x3q0f7burh465k/FALO.xlsx?dl=1&token_hash=AAH94jgsY0_nI3F0MgaieWyU-2NpGpZFUCpQXER-dqZieg"; // from Eli's Dropbox
        $dropbox_xlsx[] = "https://dl.dropboxusercontent.com/u/7597512/NCBI_GGI/FALO.xlsx"; // again from Eli's Dropbox
        // $dropbox_xlsx[] = "http://localhost/~eolit/cp/NCBIGGI/FALO.xlsx"; // local
        foreach($dropbox_xlsx as $doc)
        {
            echo "\n processing [$doc]...\n";
            if($path = Functions::save_remote_file_to_local($doc, array("timeout" => 3600, "file_extension" => "xlsx", 'download_attempts' => 2, 'delay_in_minutes' => 2)))
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

    private function create_instances_from_taxon_object($families)
    {
        $i = 0;
        $total = count($families);
        if(in_array($this->query, array("gbif_info", "bhl_info", "bolds_info")))
        {
            // $names_no_entry_from_partner = self::get_names_no_entry_from_partner(); //debug
            $names_no_entry_from_partner = array();
        }
        foreach($families as $family)
        {
            $i++;
            
            /* breakdown when caching
            $cont = false;
            if($i >= 1 && $i < 1000)     $cont = true;
            if($i >= 1000 && $i < 2000)  $cont = true;
            if($i >= 2000 && $i < 3000)  $cont = true;
            if($i >= 3000 && $i < 4000)  $cont = true;
            if($i >= 4000 && $i < 5000)  $cont = true;
            if($i >= 5000 && $i < 6000)  $cont = true;
            if($i >= 6000 && $i < 7000)  $cont = true;
            if($i >= 7000 && $i < 8000)  $cont = true;
            if($i >= 8000 && $i < 9000)  $cont = true;
            if($i >= 9000 && $i < 10000) $cont = true;
            if(!$cont) continue;
            */
            
            if($family == "Family Unassigned") continue;
            echo "\n $i of $total - [$family]\n";
            
            if    ($this->query == "ncbi_sequence_info")     self::query_family_NCBI_info($family);
            elseif($this->query == "ggbn_dna_specimen_info") self::query_family_GGBN_info($family);
            elseif($this->query == "gbif_info")              self::query_family_GBIF_info($family, $names_no_entry_from_partner);
            elseif($this->query == "bhl_info")               self::query_family_BHL_info($family, $names_no_entry_from_partner);
            elseif($this->query == "bolds_info")             self::query_family_BOLDS_info($family, $names_no_entry_from_partner);
            
            $taxon = new \eol_schema\Taxon();
            $taxon->taxonID         = $family;
            $taxon->scientificName  = $family;
            $taxon->taxonRank       = "family";
            $this->taxa[$taxon->taxonID] = $taxon;
        }
    }

    private function query_family_BOLDS_info($family, $names_no_entry_from_partner)
    {
        $rec["taxon_id"] = $family;
        if(in_array($family, $names_no_entry_from_partner))
        {
            $rec["object_id"] = "_rec_in_bolds";
            self::add_string_types($rec, "Records in BOLDS", "http://eol.org/schema/terms/no", "http://eol.org/schema/terms/RecordInBOLD", $family);
            return;
        }
        $rec["source"] = $this->bolds_taxon_page . $family;
        if($contents = Functions::lookup_with_cache($rec["source"], $this->download_options))
        {
            if($info = self::get_page_count_from_BOLDS_page($contents))
            {
                if(@$info["specimens"] > 0)
                {
                    $rec["object_id"] = "_no_of_rec_in_bolds";
                    self::add_string_types($rec, "Number records in BOLDS", $info["specimens"], "http://eol.org/schema/terms/NumberRecordsInBOLD", $family);
                    $rec["object_id"] = "_rec_in_bolds";
                    self::add_string_types($rec, "Records in BOLDS", "http://eol.org/schema/terms/yes", "http://eol.org/schema/terms/RecordInBOLD", $family);
                }
                if(@$info["public records"] > 0)
                {
                    $rec["object_id"] = "_no_of_public_rec_in_bolds";
                    self::add_string_types($rec, "Number public records in BOLDS", $info["public records"], "http://eol.org/schema/terms/NumberPublicRecordsInBOLD", $family);
                }
                return;
            }
            else
            {
                echo "\n no result for 2: [$family][" . $rec["source"] . "]\n";
                self::save_to_dump($family, $this->names_no_entry_from_partner_dump_file);
            }
        }
        else
        {
            echo "\n no result for 2: [$family][" . $rec["source"] . "]\n";
            self::save_to_dump($family, $this->names_no_entry_from_partner_dump_file);
        }
        
        $rec["object_id"] = "_rec_in_bolds";
        self::add_string_types($rec, "Records in BOLDS", "http://eol.org/schema/terms/no", "http://eol.org/schema/terms/RecordInBOLD", $family);
    }
    
    private function get_page_count_from_BOLDS_page($contents)
    {
        $info = array();
        if(preg_match("/Specimens with Sequences:<\/td>(.*?)<\/td>/ims", $contents, $arr))  $info["specimens"] = trim(strip_tags($arr[1]));
        if(preg_match("/Public Records:<\/td>(.*?)<\/td>/ims", $contents, $arr))            $info["public records"] = trim(strip_tags($arr[1]));
        return $info;
    }
    
    private function query_family_BHL_info($family, $names_no_entry_from_partner)
    {
        $rec["taxon_id"] = $family;

        if(in_array($family, $names_no_entry_from_partner))
        {
            $rec["object_id"] = "_page_in_bhl";
            self::add_string_types($rec, "Pages in BHL", "http://eol.org/schema/terms/no", "http://eol.org/schema/terms/ReferenceInBHL", $family);
            return;
        }
        
        $rec["source"] = $this->bhl_taxon_page . $family;

        if($contents = Functions::lookup_with_cache($this->bhl_taxon_in_xml . $family, $this->download_options))
        {
            if($count = self::get_page_count_from_BHL_xml($contents))
            {
                if($count > 0)
                {
                    $rec["object_id"] = "_no_of_page_in_bhl";
                    self::add_string_types($rec, "Number pages in BHL", $count, "http://eol.org/schema/terms/NumberReferencesInBHL", $family);
                    $rec["object_id"] = "_page_in_bhl";
                    self::add_string_types($rec, "Pages in BHL", "http://eol.org/schema/terms/yes", "http://eol.org/schema/terms/ReferenceInBHL", $family);
                    return;
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
                            self::add_string_types($rec, "Number pages in BHL", $count, "http://eol.org/schema/terms/NumberReferencesInBHL", $family);
                            $rec["object_id"] = "_page_in_bhl";
                            self::add_string_types($rec, "Pages in BHL", "http://eol.org/schema/terms/yes", "http://eol.org/schema/terms/ReferenceInBHL", $family);
                            return;
                        }
                    }
                    else
                    {
                        echo "\n no result for 1: [$family][" . $rec["source"] . "]\n";
                        self::save_to_dump($family, $this->names_no_entry_from_partner_dump_file);
                    }
                }
            }
        }
        else
        {
            echo "\n no result for 2: [$family][" . $rec["source"] . "]\n";
            self::save_to_dump($family, $this->names_no_entry_from_partner_dump_file);
        }

        $rec["object_id"] = "_page_in_bhl";
        self::add_string_types($rec, "Pages in BHL", "http://eol.org/schema/terms/no", "http://eol.org/schema/terms/ReferenceInBHL", $family);
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
            if($i == 1)
            {
                $fields = fgetcsv($file);
            }
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

    private function query_family_GBIF_info($family, $names_no_entry_from_partner)
    {
        $rec["taxon_id"] = $family;
        $rec["source"] = $this->gbif_taxon_info . $family;
        if(in_array($family, $names_no_entry_from_partner))
        {
            $rec["object_id"] = "_rec_in_gbif";
            self::add_string_types($rec, "Records in GBIF", "http://eol.org/schema/terms/no", "http://eol.org/schema/terms/RecordInGBIF", $family);
            return;
        }
        
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
                if($count = Functions::lookup_with_cache($this->gbif_record_count . $usageKey, $this->download_options))
                {
                    $rec["source"] = $this->gbif_record_count . $usageKey;
                    if($count > 0)
                    {
                        $rec["object_id"] = "_no_of_rec_in_gbif";
                        self::add_string_types($rec, "Number records in GBIF", $count, "http://eol.org/schema/terms/NumberRecordsInGBIF", $family);
                        $rec["object_id"] = "_rec_in_gbif";
                        self::add_string_types($rec, "Records in GBIF", "http://eol.org/schema/terms/yes", "http://eol.org/schema/terms/RecordInGBIF", $family);
                        return;
                    }
                }
                else
                {
                    echo "\n no result - cannot access count service: [$count][$family] key:[$usageKey]\n";
                    self::save_to_dump($family, $this->names_no_entry_from_partner_dump_file);
                }
            }

        }
        else
        {
            echo "\n no result - cannot access taxon service: [$family][$usageKey]\n";
            self::save_to_dump($family, $this->names_no_entry_from_partner_dump_file);
        }

        $rec["object_id"] = "_rec_in_gbif";
        self::add_string_types($rec, "Records in GBIF", "http://eol.org/schema/terms/no", "http://eol.org/schema/terms/RecordInGBIF", $family);
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
                if($rec->canonicalName == $family) $options[$rec->rank] = $rec->usageKey;
                $usagekeys[] = $rec->usageKey;
            }
            if($options)
            {
                if(isset($options["FAMILY"])) return $options["FAMILY"];
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

    private function save_to_dump($data, $filename)
    {
        $WRITE = fopen($filename, "a");
        if($data && is_array($data)) fwrite($WRITE, json_encode($data) . "\n");
        else                         fwrite($WRITE, $data . "\n");
        fclose($WRITE);
    }

    private function query_family_GGBN_info($family)
    {
        $records = array();
        $rec["source"] = $this->family_service_ggbn . $family;
        if($html = Functions::lookup_with_cache($rec["source"], $this->download_options))
        {
            $rec["taxon_id"] = $family;
            if(preg_match("/<b>(.*?) entries found/ims", $html, $arr) || preg_match("/<b>(.*?) entry found/ims", $html, $arr))
            {
                $rec["object_id"] = "NumberDNAInGGBN";
                self::add_string_types($rec, "Number of DNA records in GGBN", $arr[1], "http://eol.org/schema/terms/NumberDNARecordsInGGBN", $family); //no measurementType yet
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
                self::add_string_types($rec, "NumberSpecimensInGGBN", count($records), "http://eol.org/schema/terms/NumberSpecimensInGGBN", $family);
                $rec["object_id"] = "SpecimensInGGBN";
                self::add_string_types($rec, "SpecimensInGGBN", "http://eol.org/schema/terms/yes", "http://eol.org/schema/terms/SpecimensInGGBN", $family);
            }
            else
            {
                $rec["object_id"] = "SpecimensInGGBN";
                self::add_string_types($rec, "SpecimensInGGBN", "http://eol.org/schema/terms/no", "http://eol.org/schema/terms/SpecimensInGGBN", $family);
            }
        }
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

    private function query_family_NCBI_info($family)
    {
        $rec["source"] = $this->family_service_ncbi . $family;
        $contents = Functions::lookup_with_cache($rec["source"], $this->download_options);
        if($xml = simplexml_load_string($contents))
        {
            $rec["taxon_id"] = $family;
            $rec["object_id"] = "_no_of_seq_in_genbank";
            self::add_string_types($rec, "Number Of Sequences In GenBank", $xml->Count, "http://eol.org/schema/terms/NumberOfSequencesInGenBank", $family);
        }
    }

    private function add_string_types($rec, $label, $value, $measurementType, $family)
    {
        echo "\n [$label]:[$value]\n";
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
        // $m->measurementMethod = ''; $m->measurementRemarks = ''; $m->contributor = ""; $m->referenceID = "";
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

}
?>