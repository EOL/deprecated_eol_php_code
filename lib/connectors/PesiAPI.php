<?php
namespace php_active_record;
// connector: [665]
class PesiAPI
{
    function __construct($folder)
    {
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->resource_reference_ids = array();
        $this->resource_agent_ids = array();
        $this->vernacular_name_ids = array();
        $this->taxon_ids = array();
        $this->pesi_domain = "http://www.eu-nomen.eu";
        $this->path_string_search = $this->pesi_domain . "/portal/search.php?search=adv&SearchContent=WebSearchName&SearchType=begins&txt_Search=start_letter&accepted=accepted&rankm=%3E%3D&rank=10&belongs=&list=0&listareas=0&listareastatus=0&btn_SearchAdv=Search";
        $this->levels = array("kingdom" => 1, "phylum" => 2, "class" => 3, "order" => 4, "family" => 5, "genus" => 6, "species" => 7, "subspecies" => 8);
        $this->language_codes["Israel (Hebrew)"] = "he";
        $this->cache_path = "/Volumes/Eli blue/";
        $this->download_options = array('download_wait_time' => 1000000, 'timeout' => 1200, 'download_attempts' => 2, 'delay_in_minutes' => 1);
        // $this->download_options["expire_seconds"] = false; //debug
    }

    function get_all_taxa($taxa_list_text_file = NULL)
    {
        $this->TEMP_FILE_PATH = create_temp_dir() . "/";
        if($taxa_list_text_file)
        {
            if($contents = Functions::lookup_with_cache($taxa_list_text_file, $this->download_options))  self::save_to_taxa_text_file($contents);
        }
        else self::generate_taxa_list(); /* debug: stop operation here if you only want to generate taxa list */ //return;
        self::save_data_to_text();       /* debug: stop operation here if you only want to generate processed text files */ //return;
        if($taxa_list_text_file) 
        {
            echo "\n\n finished processing: [$taxa_list_text_file]\n\n";
            return; // you need to consolidate the processed text files before proceeding.
        }
        self::process_text_file();
        $this->archive_builder->finalize(true);
        // remove temp dir
        recursive_rmdir($this->TEMP_FILE_PATH);
        echo ("\n temporary directory removed: " . $this->TEMP_FILE_PATH);
    }

    function get_all_taxa_v2($letters)
    {
        $this->TEMP_FILE_PATH = create_temp_dir() . "/";
        self::generate_taxa_list($letters);
        self::save_data_to_text();
        echo "\n\n finished processing: [$letters]\n\n";
        return; // you need to consolidate the processed text files before proceeding.
    }

    private function save_data_to_text()
    {
        if(!extension_loaded('soap')) dl("php_soap.dll");
        $i = 0;
        if(!($f = fopen($this->TEMP_FILE_PATH . "/processed.txt", "a")))
        {
          debug("Couldn't open file: " . $this->TEMP_FILE_PATH . "/processed.txt");
          return;
        }
        foreach(new FileIterator($this->TEMP_FILE_PATH . "taxa.txt") as $line_number => $line)
        {
            $line = explode("\t", $line);
            $guid = $line[0];
            if($result = self::access_pesi_service_with_retry($guid, 1)) // type 1 is for getPESIRecordByGUID
            {
                if(self::is_array_empty($result, 1))
                {
                    echo "\n investigate guid no record: [$guid]\n";
                    continue;
                }
                $info = self::get_parent_taxon($result);
                $parent_taxa = $info["taxon"];
                $parent_rank = $info["rank"];
                if(($i % 100) == 0) echo "\n SOAP response: $i. $result->scientificname -- $result->GUID -- $parent_taxa";
                $line = self::clean_str($result->GUID) . "\t" .
                        self::clean_str($result->scientificname) . "\t" .
                        self::clean_str($result->authority) . "\t" .
                        self::clean_str($result->rank) . "\t" .
                        self::clean_str($parent_taxa) . "\t" .
                        self::clean_str($parent_rank) . "\t" .
                        self::clean_str($result->citation) . "\n";
                fwrite($f, $line);
                $i++;
            }
            else
            {
                echo "\n investigate guid no record: [$guid]\n";
                continue;
            }
            // if($i >= 20) break; // debug - to limit during development
        }
        fclose($f);
        echo "\n\n total: $i \n\n";
    }

    private function get_parent_taxon($result, $rank = false)
    {
        if(!$rank) $rank = strtolower($result->rank);
        if($rank == "subspecies") return array("taxon" => self::get_species_from_subspecies($result->scientificname), "rank" => "species");
        $num = $this->levels[$rank] - 1;
        for($i=1; $i<=7; $i++)
        {
            if($num >= 1 && $num <= 7)
            {
                foreach($this->levels as $rank => $value)
                {
                    if($num == $value)
                    {
                        if($result->$rank) return array("taxon" => $result->$rank, "rank" => $rank);
                        else
                        {
                            $num = $num - 1;
                            break;
                        }
                    }
                }
            }
        }
        return array("taxon" => "", "rank" => "");
    }

    private function get_species_from_subspecies($subspecies)
    {
        $string = explode(" ", $subspecies);
        switch(count($string))
        {
            case 3:
                 $species = $string[0] . " " . $string[1];
                 break;
            case 4:
                $species = $string[0] . " " . $string[1] . " " . $string[2];
                break;
            case 5:
                $species = $string[0] . " " . $string[1] . " " . $string[2] . " " . $string[3];
                break;
        }
        return trim(str_ireplace(" subsp.", "", $species));
    }

    private function generate_taxa_list($letters = "A,B,C,D,E,F,G,H,I,J,K,L,M,N,O,P,Q,R,S,T,U,V,W,X,Y,Z")
    {
        $options1 = $this->download_options;
        $options1["download_wait_time"] = 5000000;
        $options2 = $options1;
        $options2["download_wait_time"] = 1000000;

        /* debug - comment in normal operation, use to divide when caching
        $letters = "E,F";
        // $letters = "J,K,L,M";
        // $letters = "P,Q,R,S";
        // $letters = "G,N,T,U,V,W,X,Y,Z";
        */

        $letters = explode(",", $letters);
        foreach($letters as $letter)
        {
            echo "\n\n processing letter: [$letter]\n\n";
            if($html = Functions::lookup_with_cache(str_ireplace("start_letter", $letter, $this->path_string_search), $options1))
            {
                if($ranks = self::get_relevant_ranks_to_process($html))
                {
                    foreach($ranks as $rank)
                    {
                        echo "\n\n processing rank: [$rank][$letter]\n\n";
                        if(preg_match("/\'(.*?)\'/ims", $rank, $arr))
                        {
                            if(preg_match("/\((.*?)\)/ims", $rank, $arr2)) $repeatitions = ceil($arr2[1] / 100);
                            else echo "\n\n alert: investigate 01 [$rank] \n\n";
                            $path = $this->pesi_domain . $arr[1];
                            echo "\n\n $repeatitions - $path \n";
                            $taxa = array();
                            // $repeatitions = 1; // debug - if = 2 means pages 1 and 2; commented in normal operation
                            for($i = 1; $i <= $repeatitions; $i++)
                            {
                                echo "\n $i of $repeatitions \n";
                                if($html = Functions::lookup_with_cache($path."&page=$i", $options2))
                                {
                                    if($arr2[1] == 1) self::store_taxon_name($html);
                                    else self::store_taxa_names($html);
                                }
                            }
                        }
                    }
                }
            }
            else echo "\n\n alert: investigate 02 Unsuccesfull letter: [$letter] \n\n";
        }
    }

    private function store_taxon_name($html)
    {
        /* <div class='guid'>urn:lsid:marinespecies.org:taxname:21891<br>urn:lsid:indexfungorum.org:names:90031</div> */
        $guid = ""; $taxon = "";
        if(preg_match("/<div class=\'guid\'>(.*?)<\/div>/ims", $html, $arr)) $guids = $arr[1];
        {
            $guids = explode("<br>", $guids);
            $guid = $guids[0];
        }
        if(preg_match("/<H1>(.*?)<\/H1>/ims", $html, $arr)) $taxon = (string) strip_tags($arr[1]);
        if($guid && $taxon) self::save_to_taxa_text_file($guid . "\t" . $taxon . "\n");
    }

    private function store_taxa_names($html)
    {
        /* <a href="taxon.php?GUID=8D587FC0-A13D-413C-9A36-C5E2132A08C9"><i>Atriplex semibaccata</i> R. Br.</a> */
        if(preg_match_all("/taxon\.php\?GUID=(.*?)<\/a>/ims", $html, $arr))
        {
            $contents = "";
            foreach($arr[1] as $line)
            {
                /* urn:lsid:faunaeur.org:taxname:405332"><i>Zabrachia minutissima</i> (Zetterstedt, 1838) */
                if(preg_match("/(.*?)\"/ims", $line, $arr)) $guid = $arr[1];
                if(preg_match("/<i>(.*?)xxx/ims", $line."xxx", $arr)) $taxon = (string) strip_tags($arr[1]);
                $contents .= $guid . "\t" . $taxon . "\n";
            }
            self::save_to_taxa_text_file($contents);
        }
    }

    private function save_to_taxa_text_file($contents)
    {
        if(!($f=fopen($this->TEMP_FILE_PATH . "/taxa.txt", "a")))
        {
          debug("Couldn't open file: " .$this->TEMP_FILE_PATH . "/taxa.txt");
          return;
        }
        fwrite($f, $contents);
        fclose($f);
    }

    private function get_relevant_ranks_to_process($html)
    {
        $ranks = array("Kingdom", "Phylum", "Class", "Order", "Family", "Genus", "Species", "Subspecies"); // only taxa belonging to these ranks will be harvested
        // $ranks = array("Genus"); // debug - commented in normal operation
        if(preg_match("/please make a selection(.*?)<script type/ims", $html, $arr))
        {
            $html = trim($arr[1]);
            if(preg_match_all("/<a href=(.*?)<\/a>/ims", $html, $arr))
            {
                $lines = $arr[1];
                $i = 0;
                foreach($lines as $line)
                {
                    $set_to_null = true;
                    foreach($ranks as $rank)
                    {
                        if(preg_match("/>$rank (.*?)\(/ims", $line, $arr)) $set_to_null = false;
                    }
                    if($set_to_null) $lines[$i] = NULL;
                    $i++;
                }
                return array_filter($lines);
            }
        }
        return false;
    }

    function process_text_file($file_path = NULL, $file_for_name_id_assignment = NULL)
    {
        if(!$file_path)                   $file_path = $this->TEMP_FILE_PATH . "processed.txt";
        if(!$file_for_name_id_assignment) $file_for_name_id_assignment = $file_path;
        echo "\n\n file_path: " . $file_path;
        echo "\n file_for_name_id_assignment: " . $file_for_name_id_assignment;
        echo "\n\n";
        self::assign_name_and_id($file_for_name_id_assignment);
        $i = 0;
        $link = array();
        $records = array();
        foreach(new FileIterator($file_path) as $line_number => $line)
        {
            $line = explode("\t", $line);
            if(count($line) == 1) continue;
            $rec = array("guid"            => (string) $line[0], //"F03821E2-BDA3-4CA7-829D-312BD6D4809B"
                         "scientificname"  => (string) $line[1],
                         "authority"       => (string) $line[2],
                         "rank"            => (string) @$line[3],
                         "parent"          => (string) @$line[4],
                         "parent_rank"     => (string) @$line[5],
                         "citation"        => (string) @$line[6]);
            $i++;
            echo "\n $i. $rec[scientificname] [$rec[guid]]";
            $this->create_instances_from_taxon_object($rec, array());
            // /* uncomment in normal operation - debug
            self::get_vernacular_names($rec);
            self::get_synonyms($rec);
            // */
            // if($i > 20) break; // debug
        }
    }

    function create_instances_from_taxon_object($rec, $reference_ids)
    {
        $sciname = trim($rec["scientificname"]);
        $genus = "";
        if(is_numeric(stripos($sciname, " ")))
        {
            $parts = explode(" ", $sciname);
            $genus = $parts[0];
        }
        $taxon = new \eol_schema\Taxon();
        if($reference_ids) $taxon->referenceID = implode("; ", $reference_ids);
        $taxon->taxonID                     = (string) $rec["guid"];
        $taxon->taxonRank                   = (string) $rec["rank"];
        $taxon->scientificName              = (string) $rec["scientificname"];
        $taxon->scientificNameAuthorship    = (string) $rec["authority"];
        $taxon->genus                       = (string) $genus;
        $taxon->bibliographicCitation       = (string) $rec["citation"];
        $taxon->parentNameUsageID = "";
        if($rec["parent"] != "")
        {
            if($taxon->parentNameUsageID = self::get_guid_from_name($rec["parent"])) {}
            else
            {
                $parent_rank = $rec["parent_rank"];
                if($rec["rank"] != "")
                {
                    $taxon2 = new \eol_schema\Taxon();
                    $taxon2->taxonID                     = (string) str_ireplace(" ", "_", $rec["parent"]);
                    $taxon2->taxonRank                   = (string) $parent_rank;
                    $taxon2->scientificName              = (string) $rec["parent"];
                    $taxon2->scientificNameAuthorship    = (string) "";
                    $taxon2->genus                       = (string) "";
                    $taxon2->parentNameUsageID = "";
                    $taxon->parentNameUsageID = $taxon2->taxonID;
                    if($parent_rank == "species")
                    {
                        $parts = explode(" ", $rec["parent"]);
                        if($taxon2->genus = $parts[0])
                        {
                            $taxon2->parentNameUsageID = self::get_guid_from_name($taxon2->genus, true);
                            if(!$taxon2->parentNameUsageID) echo "\n\n new taxon [" . $rec["parent"] . "] ($parent_rank) no parent info \n";
                        }
                    }
                    else
                    {
                        if($result = self::access_pesi_service_with_retry($rec["guid"], 1)) // type 1 is for getPESIRecordByGUID
                        {
                            $info = self::get_parent_taxon($result, $parent_rank);
                            if($new_taxon_parent_taxa = $info["taxon"])
                            {
                                $taxon2->parentNameUsageID = self::get_guid_from_name($new_taxon_parent_taxa, true);
                                if(!$taxon2->parentNameUsageID) echo "\n\n new taxon [" . $rec["parent"] . "] ($parent_rank) no parent info \n";
                            }
                        }
                    }
                    if(!isset($this->taxon_ids[$taxon2->taxonID]))
                    {
                        $this->taxon_ids[$taxon2->taxonID] = '';
                        $this->archive_builder->write_object_to_file($taxon2);
                    }
                    $this->name_id[$taxon2->scientificName] = $taxon2->taxonID;
                }
            }
            if($taxon->parentNameUsageID == "") echo "\n\n main taxon [" . $taxon->scientificName . "] ($taxon->taxonRank) no parent info \n";
        }
        if(!isset($this->taxon_ids[$taxon->taxonID]))
        {
            $this->taxon_ids[$taxon->taxonID] = '';
            $this->archive_builder->write_object_to_file($taxon);
        }
    }

    private function get_guid_from_name($name, $with_comment = false)
    {
        echo "\n --- get guid for: [$name]";
        if(@$this->name_id[$name]) return (string) $this->name_id[$name];
        else
        {
            if($with_comment) echo " -- [$name] does not exist yet \n";
            return false;
        }
    }

    private function get_synonyms($rec)
    {
        if($results = self::access_pesi_service_with_retry($rec["guid"], 2)) // type 2 is for getPESISynonymsByGUID
        {
            echo "\n guid: " . $rec["guid"] . "\n";
            if(self::is_array_empty($results, 1)) // 1 is for synonyms
            {
                echo "\n no synonyms 01 \n";
                print_r($results);
                return;
            }
            echo "\n with synonym(s)...\n";
            foreach($results as $result)
            {
                if($rec["guid"] != $result->valid_guid)
                {
                    echo "\n alert: this should be equal " . $rec["guid"] . " == " . $result->valid_guid . "\n";
                    continue;
                }
                $synonym = new \eol_schema\Taxon();
                if($result->GUID) $synonym_taxon_id = $result->GUID;
                else $synonym_taxon_id = md5("$result->scientificname|$result->authority|$result->rank|$result->citation");
                echo "\n as synonym: $synonym_taxon_id \n";
                $synonym->taxonID                       = (string) $synonym_taxon_id;
                $synonym->scientificName                = (string) $result->scientificname;
                $synonym->scientificNameAuthorship      = (string) $result->authority;
                $synonym->taxonRank                     = (string) $result->rank;
                $synonym->acceptedNameUsageID           = (string) $result->valid_guid;
                $synonym->taxonomicStatus               = (string) $result->status;
                if(!$synonym->scientificName) continue;
                if(!isset($this->taxon_ids[$synonym->taxonID]))
                {
                    $this->archive_builder->write_object_to_file($synonym);
                    $this->taxon_ids[$synonym->taxonID] = '';
                }
                else
                {
                    echo "\n alert: investigate synonyms"; // means that this synonym is already a synonym of another taxon.
                    print_r($rec);
                    print_r($result);
                }
            }
        }
        else echo "\n no synonyms 02 \n";
    }

    private function get_vernacular_names($rec)
    {
        if($results = self::access_pesi_service_with_retry($rec["guid"], 3)) // type 3 is for getPESIVernacularsByGUID
        {
            if(self::is_array_empty($results, 2)) // 2 is for vernaculars
            {
                echo "\n no vernaculars 01 \n";
                return;
            }
            echo "\n with vernacular(s)..." . $rec["guid"]  . "\n";
            foreach($results as $result)
            {
                $vernacular = new \eol_schema\VernacularName();
                $vernacular->taxonID = $rec["guid"];
                $vernacular->vernacularName = $result->vernacular;
                $language_code = (string) $result->language_code;
                if($language_code) $vernacular->language = $language_code;
                else $vernacular->language = @$this->language_codes[$result->language];
                $vernacular_id = md5("$vernacular->taxonID|$vernacular->vernacularName|$result->language");
                if(!$vernacular->vernacularName) continue;
                if(!isset($this->vernacular_name_ids[$vernacular_id]))
                {
                    $this->archive_builder->write_object_to_file($vernacular);
                    $this->vernacular_name_ids[$vernacular_id] = '';
                }
                else
                {
                    echo "\n alert: investigate vernaculars";
                    print_r($rec);
                    print_r($result);
                }
            }
        }
        else echo "\n no vernaculars 02 \n";
    }

    private function assign_name_and_id($file_path)
    {
        $i = 0;
        foreach(new FileIterator($file_path) as $line_number => $line)
        {
            $line = explode("\t", $line);
            if(count($line) > 1)
            {
                $guid = $line[0];
                $scientificname = $line[1];
                $this->name_id[$scientificname] = $guid;
                $i++;
            }
        }
        echo "\n count: " . count($this->name_id) . "\n";
    }

    private function access_pesi_service_with_retry($guid, $type)
    {
        $dirs = array("PESI_cache", "PESI_cache2", "PESI_cache3");
        foreach($dirs as $dir)
        {
            $filename = $this->cache_path . $dir . "/" . $type . "_" . $guid . ".txt";
            if(file_exists($filename))
            {
                $json = file_get_contents($filename);
                echo " --- cache retrieved";
                return json_decode($json);
            }
        }
        echo " --- cache created";
        //create the cache
        $obj = self::soap_request($guid, $type);
        if(!($file = fopen($filename, "w")))
        {
          debug("Couldn't open file: " . $filename);
          return;
        }
        fwrite($file, json_encode($obj));
        fclose($file);
        usleep(300000); // 3 tenths of a second = 3/10 of a second
        return $obj;
    }

    private function soap_request($guid, $type)
    {
        for($i = 1; $i <= 2; $i++) // 2 tries only
        {
            try
            {
                $client = new \SoapClient("http://www.eu-nomen.eu/portal/soap.php?wsdl=1", array("trace"=>false));
                try
                {
                    if($type == 1) return $client->getPESIRecordByGUID($guid);
                    elseif($type == 2) return $client->getPESISynonymsByGUID($guid);
                    elseif($type == 3) return $client->getPESIVernacularsByGUID($guid);
                }
                catch(\SoapFault $sp)
                {
                    echo "\n error in soap call \n";
                }
            }
            catch(\Exception $e)
            {
                echo "\n error in creating soap client \n";
            }
            echo "\n\n retrying after a minute... attempt $i [$guid][type: $type]";
            sleep(60);
        }
        echo "\n investigate " . $i-1 . " attempts failed [$guid][$type]\n";
        return false;
    }

    function is_array_empty($arr, $type)
    {
        if($type == 1) $property = "GUID";
        elseif($type == 2) $property = "vernacular";
        if(is_array($arr))
        {
            foreach($arr as $key => $value)
            {
                if(is_object($value))
                {
                    if(property_exists($value, $property)) return false;
                }
            }
            return true;
        }
        elseif(is_object($arr))
        {
            if(property_exists($arr, $property)) return false;
        }
        return true;
    }

    private function clean_str($str)
    {
        return str_ireplace(array("\t"), " ", $str);
    }

}
?>