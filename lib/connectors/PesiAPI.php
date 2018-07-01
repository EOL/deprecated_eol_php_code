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
        $this->path_summary_page  = $this->pesi_domain . "/portal/taxon.php?GUID=";
        $this->levels = array("kingdom" => 1, "phylum" => 2, "class" => 3, "order" => 4, "family" => 5, "genus" => 6, "species" => 7, "subspecies" => 8);
        $this->language_codes["Israel (Hebrew)"] = "he";
        $this->cache_path = "/Volumes/Thunderbolt4/eol_cache_pesi/eol_PESI_cache/";
        $this->download_options = array('download_wait_time' => 2000000, 'timeout' => 1200, 'download_attempts' => 2, 'delay_in_minutes' => 1, 'resource_id' => 665);
        $this->download_options["expire_seconds"] = false; //debug - false means it will use cache
        $this->ranks = "Kingdom,Subkingdom,Superphylum,Infrakingdom,Division,Phylum,Subdivision,Subphylum,Infraphylum,Superclass,Class,Subclass,Infraclass,Superorder,Order,Suborder,Infraorder,Section,Subsection,Superfamily,Family,Subfamily,Tribe,Subtribe,Genus,Subgenus,Section,Subsection,Series,Subseries,Aggregate,Coll. Species,Species,Grex,Subspecies,Proles,Race,Natio,Convariety,Variety,Subvariety,Form,Subform,Form spec.,Tax. infragen.,Tax. infraspec.";
        /*
        http://www.eu-nomen.eu/portal/soap.php#
        http://www.eu-nomen.eu/portal/taxon.php?GUID=urn:lsid:marinespecies.org:taxname:130223
        http://www.eu-nomen.eu/portal/rest/#!/Taxonomic_data/get_PESIRecordByGUID_GUID
        */
    }

    function get_all_taxa()
    {
        /* tests:
        $rec = self::get_parent_from_portal("urn:lsid:marinespecies.org:taxname:2847");
        print_r($rec); exit;
        */

        $this->TEMP_FILE_PATH = create_temp_dir() . "/";
        self::generate_taxa_list(); /* debug: stop operation here if you only want to generate taxa list */  // return;
        self::save_data_to_text();  /* debug: stop operation here if you only want to generate processed text files */ //return;
        exit("\n temp path with generated texts: " . $this->TEMP_FILE_PATH . "\n\n");

        self::process_text_file();
        $this->archive_builder->finalize(true);
        // remove temp dir
        recursive_rmdir($this->TEMP_FILE_PATH); //debug uncomment in real operation
        echo ("\n temporary directory removed: " . $this->TEMP_FILE_PATH);
    }

    function get_all_taxa_v2($url) //taxa.txt and processed.txt are already generated elsewhere
    {
        $this->TEMP_FILE_PATH = create_temp_dir() . "/";

        $options = $this->download_options;
        $options["expire_seconds"] = 0;
        $contents = Functions::lookup_with_cache($url, $options);
        if($f = Functions::file_open($this->TEMP_FILE_PATH . "/processed.txt", "a"))
        {
            fwrite($f, $contents);
            fclose($f);
        }
        else return;
        
        self::process_text_file();
        $this->archive_builder->finalize(true);
        // remove temp dir
        recursive_rmdir($this->TEMP_FILE_PATH); //debug uncomment in real operation
        echo ("\n temporary directory removed: " . $this->TEMP_FILE_PATH);
    }

    private function save_data_to_text()
    {
        if(!extension_loaded('soap')) dl("php_soap.dll");
        $i = 0;
        if(!($f = Functions::file_open($this->TEMP_FILE_PATH . "/processed.txt", "a"))) return;
        foreach(new FileIterator($this->TEMP_FILE_PATH . "taxa.txt") as $line_number => $line)
        {
            $line = explode("\t", $line);
            $guid = $line[0];
            if(!$guid) continue;
            // echo "\nsciname: ".$line[1]."\n";  //debug
            if($result = self::access_pesi_service_with_retry($guid, 1)) // type 1 is for getPESIRecordByGUID
            {
                if(self::is_array_empty($result, 1))
                {
                    echo "\n investigate guid no record1: [$guid]\n";
                    continue;
                }
                // $info = self::get_parent_taxon($result);
                $info = self::get_parent_from_portal($result->GUID);
                $parent_taxa = $info["taxon"];
                $parent_rank = $info["rank"];
                $parent_guid = $info["guid"];

                if(($i % 100) == 0) echo "\n SOAP response: $i. $result->scientificname -- $result->GUID -- $parent_taxa";
                $line = self::clean_str($result->GUID)              . "\t" .
                        self::clean_str($result->scientificname)    . "\t" .
                        self::clean_str($result->authority)         . "\t" .
                        self::clean_str($result->rank)              . "\t" .
                        self::clean_str($parent_taxa)               . "\t" .
                        self::clean_str($parent_rank)               . "\t" .
                        self::clean_str($result->citation)          . "\t" .
                        self::clean_str($result->url)               . "\t" .
                        self::clean_str($parent_guid)               . "\n";
                fwrite($f, $line);
                $i++;
            }
            else
            {
                echo "\n investigate guid no record2: [$guid]\n";
                continue;
            }
            // if($i >= 20) break; // debug - to limit during development
        }
        fclose($f);
        echo "\n\n total: $i \n\n";
    }

    private function generate_taxa_list($letters = "A,B,C,D,E,F,G,H,I,J,K,L,M,N,O,P,Q,R,S,T,U,V,W,X,Y,Z")
    {
        $options1 = $this->download_options;
        $options1["download_wait_time"] = 5000000;
        $options2 = $options1;
        $options2["download_wait_time"] = 1000000;

        /* debug - comment in normal operation, use to divide when caching. 10 simultaneous connectors is OK  --- best breakdown when caching below
        $letters = "A";
        $letters = "B";
        $letters = "C";
        $letters = "D";
        $letters = "E,F,G";
        $letters = "H,I,J";
        $letters = "K,L,M";
        $letters = "N,O,P";
        $letters = "Q,R,S";
        $letters = "T,U,V,W,X,Y,Z";
        */

        $letters = explode(",", $letters);
        foreach($letters as $letter)
        {
            echo "\n\n processing letter: [$letter]\n\n";
            $path = str_ireplace("start_letter", $letter, $this->path_string_search);
            if($html = Functions::lookup_with_cache($path, $options1))
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

        //for 'unassigned' taxa
        $path = "http://www.eu-nomen.eu/portal/search.php?search=adv&SearchContent=WebSearchName&SearchType=contains&txt_Search=unassigned&accepted=accepted&rankm=%3E%3D&rank=10&belongs=&list=0&listareas=0&listareastatus=0&btn_SearchAdv=Search";
        if($html = Functions::lookup_with_cache($path, $options2)) self::store_taxa_names($html);
        
        //for 11+1+1 undefined parent
        $undefined_parents = array("urn:lsid:marinespecies.org:taxname:131605","urn:lsid:marinespecies.org:taxname:155735","urn:lsid:marinespecies.org:taxname:2",
        "urn:lsid:marinespecies.org:taxname:106680","urn:lsid:marinespecies.org:taxname:6","urn:lsid:marinespecies.org:taxname:740671","urn:lsid:marinespecies.org:taxname:153641",
        "urn:lsid:marinespecies.org:taxname:153648","urn:lsid:marinespecies.org:taxname:7","urn:lsid:faunaeur.org:taxname:257022","urn:lsid:marinespecies.org:taxname:740669",
        "urn:lsid:marinespecies.org:taxname:150936","4F2FE922-4FEC-47ED-9842-A50C1BAE84F6");
        foreach($undefined_parents as $id)
        {
            if($html = Functions::lookup_with_cache($this->path_summary_page.$id, $options2)) self::store_taxon_name($html);
        }
    }

    private function store_taxon_name($html)
    {
        /* <div class='guid'>urn:lsid:marinespecies.org:taxname:21891<br>urn:lsid:indexfungorum.org:names:90031</div> */
        $guid = ""; $taxon = "";
        if(preg_match("/<div class=\'guid\'>(.*?)<\/div>/ims", $html, $arr))
        {
            $guids = $arr[1];
            $guids = explode("<br>", $guids);
            $guid = $guids[0];
        }
        if(preg_match("/<H1>(.*?)<\/H1>/ims", $html, $arr)) $taxon = (string) strip_tags($arr[1]);
        if($guid && $taxon) self::save_to_taxa_text_file($guid . "\t" . self::clean_str($taxon) . "\n");
    }

    private function store_taxa_names($html)
    {
        /* <a href="taxon.php?GUID=8D587FC0-A13D-413C-9A36-C5E2132A08C9"><i>Atriplex semibaccata</i> R. Br.</a> 
           <a href="taxon.php?GUID=urn:lsid:marinespecies.org:taxname:172726">Navicula <i>peregrina</i> var. <i>peregrina</i> f. <i>peregrina</i></a>
        */
        if(preg_match_all("/taxon\.php\?GUID=(.*?)<\/a>/ims", $html, $arr))
        {
            $contents = "";
            foreach($arr[1] as $line)
            {
                /* urn:lsid:faunaeur.org:taxname:405332"><i>Zabrachia minutissima</i> (Zetterstedt, 1838) */
                if(preg_match("/(.*?)\"/ims", $line, $arr)) $guid = $arr[1];
                // if(preg_match("/<i>(.*?)xxx/ims", $line."xxx", $arr)) $taxon = (string) strip_tags($arr[1]); -- was replaced by below
                if(preg_match("/\">(.*?)xxx/ims", $line."xxx", $arr)) $taxon = (string) strip_tags($arr[1]);
                if($guid && $taxon) $contents .= $guid . "\t" . $taxon . "\n";
            }
            self::save_to_taxa_text_file($contents);
        }
    }

    private function save_to_taxa_text_file($contents)
    {
        if(!($f = Functions::file_open($this->TEMP_FILE_PATH . "/taxa.txt", "a"))) return;
        fwrite($f, $contents);
        fclose($f);
    }

    private function get_relevant_ranks_to_process($html)
    {
        $ranks = explode(",", $this->ranks);
        // $ranks = array("Division", "Kingdom", "Phylum", "Class", "Order", "Family", "Genus", "Species", "Subspecies"); // only taxa belonging to these ranks will be harvested
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
            
            /* //start debug
            $char = strtolower(substr($line[1], 0, 1));
            // if(!in_array($char, array("a"))) continue;
            // if(!in_array($char, array("b"))) continue;
            // if(!in_array($char, array("c"))) continue;
            // if(!in_array($char, array("d","e","f","g","h","i"))) continue;
            // if(!in_array($char, array("j","k","l","m","n","o"))) continue;
            // if(!in_array($char, array("p","q","r","s"))) continue;
            // if(!in_array($char, array("t","u","v","w","x","y","z"))) continue;
            */ //end debug
            
            $rec = array("guid"            => (string) $line[0], //"F03821E2-BDA3-4CA7-829D-312BD6D4809B"
                         "scientificname"  => (string) $line[1],
                         "authority"       => (string) $line[2],
                         "rank"            => (string) @$line[3],
                         "parent"          => (string) @$line[4],
                         "parent_rank"     => (string) @$line[5],
                         "citation"        => (string) @$line[6],
                         "url"             => (string) @$line[7],
                         "parent_id"       => (string) @$line[8]);
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
/*
RESTFUL : http://www.eu-nomen.eu/portal/rest/#!/Taxonomic_data/get_PESIRecordsByMatchTaxon_ScientificName
SOAP    : http://www.eu-nomen.eu/portal/soap.php#
{"GUID":"urn:lsid:faunaeur.org:taxname:381594",
    "url":"http:\/\/www.eu-nomen.eu\/portal\/taxon.php?GUID=urn:lsid:faunaeur.org:taxname:381594",
    "scientificname":"Abax (Abax) parallelepipedus alpigradus",
    "authority":"Schauberger, 1927",
    "rank":"Subspecies",
    "status":"accepted",
    "valid_guid":"urn:lsid:faunaeur.org:taxname:381594",
    "valid_name":"Abax (Abax) parallelepipedus alpigradus",
    "valid_authority":"Schauberger, 1927",
    "kingdom":"Animalia",
    "phylum":"Arthropoda",
    "class":"Insecta",
    "order":"Coleoptera",
    "family":"Carabidae",
    "genus":"Abax",
    "citation":"Prof. Augusto Vigna Taglianti. Abax (Abax) parallelepipedus alpigradus Schauberger, 1927. Accessed through: Fauna Europaea at http:\/\/www.faunaeur.org\/full_results.php?id=381594",
    "match_type":"exact"}
*/

    function create_instances_from_taxon_object($rec, $reference_ids)
    {
        $sciname = trim($rec["scientificname"]);
        
        $taxon = new \eol_schema\Taxon();
        if($reference_ids) $taxon->referenceID = implode("; ", $reference_ids);
        $taxon->taxonID                     = (string) $rec["guid"];
        $taxon->taxonRank                   = (string) $rec["rank"];

        $sciname = (string) $rec["scientificname"] . " " . (string) $rec["authority"];
        $taxon->scientificName = trim($sciname);

        // $taxon->scientificNameAuthorship    = (string) $rec["authority"];
        $taxon->bibliographicCitation       = (string) $rec["citation"];
        $taxon->source                      = (string) $rec["url"];
        if($val = $rec["parent_id"]) $taxon->parentNameUsageID = (string) $val;

        // if(!$taxon->parentNameUsageID) exit("\nno parent id - (" . $rec["parent"] . ") [" . $val . "]\n"); //debug

        /* wait... we may not need it anymore
        if($rec["parent"] != "")
        {
            if($taxon->parentNameUsageID = self::get_guid_from_name($rec["parent"])) {echo "\naaa\n";}
            elseif($temp_parent_id = self::get_guid_from_api_using_name($rec["parent"]))
            {
                echo "\nzzz\n";
                
                if($temp_parent_id != $taxon->taxonID) $taxon->parentNameUsageID = $temp_parent_id;
                else //go to genus or family or ... for the parent; since the immediate parent is not 'accepted'
                {    //e.g. 1889. Aphanius dispar dispar [urn:lsid:marinespecies.org:taxname:293545]
                    if($taxon->taxonRank == "Subspecies")   $taxon->parentNameUsageID = self::get_id_of_ancestor_for_this_rank($taxon->taxonID, "genus");
                    elseif($taxon->taxonRank == "Species")  $taxon->parentNameUsageID = self::get_id_of_ancestor_for_this_rank($taxon->taxonID, "family");
                    elseif($taxon->taxonRank == "Genus")    $taxon->parentNameUsageID = self::get_id_of_ancestor_for_this_rank($taxon->taxonID, "order");
                    elseif($taxon->taxonRank == "Family")   $taxon->parentNameUsageID = self::get_id_of_ancestor_for_this_rank($taxon->taxonID, "class");
                    elseif($taxon->taxonRank == "Order")    $taxon->parentNameUsageID = self::get_id_of_ancestor_for_this_rank($taxon->taxonID, "phylum");
                    elseif($taxon->taxonRank == "Class")    $taxon->parentNameUsageID = self::get_id_of_ancestor_for_this_rank($taxon->taxonID, "kingdom");
                    else echo "\nbbb\n";
                }
            }
            if(!$taxon->parentNameUsageID)
            {
                echo "\nddd\n";
                $string_left = self::get_genus($rec["parent"]);
                if($val = self::get_guid_from_name($string_left)) $taxon->parentNameUsageID = $val;
            }
            if(!$taxon->parentNameUsageID)
            {
                //========================================= Abrothallus Adeuomphalus
                if(!in_array($rec["scientificname"], array("xx", "yy"))) exit("\ncreated a new taxon to fill in parent taxon - (" . $rec["parent"] . ")\n");
                //=========================================
                $parent_rank = $rec["parent_rank"];
                if($rec["rank"] != "")
                {
                    $taxon2 = new \eol_schema\Taxon();
                    $taxon2->taxonID                     = (string) str_ireplace(" ", "_", $rec["parent"]);
                    $taxon2->taxonRank                   = (string) $parent_rank;
                    $taxon2->scientificName              = (string) $rec["parent"];
                    // $taxon2->scientificNameAuthorship    = (string) "";
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
        */
        
        if(!isset($this->taxon_ids[$taxon->taxonID]))
        {
            $this->taxon_ids[$taxon->taxonID] = '';
            $this->archive_builder->write_object_to_file($taxon);
        }
    }

    private function get_guid_from_api_using_name($name)
    {
        if($results = self::access_pesi_service_with_retry(false, 4, $name)) // type 4 is for matchTaxon
        {
            foreach($results as $result)
            {
                print_r($result); echo "\nelicha\n";
                $valid = (string) $result->valid_name;
                if($guid = self::get_guid_from_name($valid))
                {
                    echo "\nguid for $name is: [$guid]\n"; //exit("\n111\n");
                    return $guid;
                }
                break; //get only 1 record. Weird the matchTaxon() results into an array of records where there is supposedly only 1 taxon result
            }
        }
        return false;
    }
    
    private function get_id_of_ancestor_for_this_rank($guid, $rank)
    {
        if($result = self::access_pesi_service_with_retry($guid, 1)) // type 1 is for getPESIRecordByGUID
        {
            print_r($result);
            if($rank == "genus")
            {
                if($val = $result->genus) return (string) $val;
                elseif($val = $result->family) return (string) $val;
                elseif($val = $result->order) return (string) $val;
                elseif($val = $result->class) return (string) $val;
                elseif($val = $result->phylum) return (string) $val;
                elseif($val = $result->kingdom) return (string) $val;
            }
            elseif($rank == "family")
            {
                if($val = $result->family) return (string) $val;
                elseif($val = $result->order) return (string) $val;
                elseif($val = $result->class) return (string) $val;
                elseif($val = $result->phylum) return (string) $val;
                elseif($val = $result->kingdom) return (string) $val;
            }
            elseif($rank == "order")
            {
                if($val = $result->order) return (string) $val;
                elseif($val = $result->class) return (string) $val;
                elseif($val = $result->phylum) return (string) $val;
                elseif($val = $result->kingdom) return (string) $val;
            }
            elseif($rank == "class")
            {
                if($val = $result->class) return (string) $val;
                elseif($val = $result->phylum) return (string) $val;
                elseif($val = $result->kingdom) return (string) $val;
            }
            elseif($rank == "phylum")
            {
                if($val = $result->phylum) return (string) $val;
                elseif($val = $result->kingdom) return (string) $val;
            }
            elseif($rank == "kingdom")
            {
                if($val = $result->kingdom) return (string) $val;
            }
            // exit("\nstopx\n");
            
            if(in_array($result->rank, array("Species", "Subspecies")))
            {
                $genus = self::get_genus($result->valid_name);
                return self::get_guid_from_api_using_name($genus);
            }
            
            
        }
        return false;
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
            // return; //debug only - comment in real operation
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

    private function access_pesi_service_with_retry($guid, $type, $name = false)
    {
        if($guid)     $md5 = md5($type . "_" . $guid);
        elseif($name) $md5 = md5($type . "_" . $name);
        $cache1 = substr($md5, 0, 2);
        $cache2 = substr($md5, 2, 2);
        if(!file_exists($this->cache_path . $cache1))           mkdir($this->cache_path . $cache1);
        if(!file_exists($this->cache_path . "$cache1/$cache2")) mkdir($this->cache_path . "$cache1/$cache2");
        $filename = $this->cache_path . "$cache1/$cache2/$md5.txt";
        
        $old_filename = "/Volumes/Thunderbolt4/eol_cache_pesi/eol_old_PESI_cache/" . $type . "_" . $guid . ".txt"; //from last harvest's cache

        // /* uncomment in normal operation
        if(file_exists($old_filename))
        {
            $json = file_get_contents($old_filename); // echo " --- cache retrieved from old filename";
            return json_decode($json);
        }
        elseif(file_exists($filename))
        {
            $json = file_get_contents($filename); // echo " --- cache retrieved";
            return json_decode($json);
        }
        else
        {
            //create the cache
            $obj = self::soap_request($guid, $type, $name);
            if(!($file = Functions::file_open($filename, "w"))) return;
            fwrite($file, json_encode($obj));
            fclose($file);
            echo "\n --- cache created [$type - $filename]";
            usleep(500000); // 5 tenths of a second = 5/10 of a second
            return $obj;
        }
        // */

        /* //force create cache - comment in normal operation - code copied above
        //create the cache
        $obj = self::soap_request($guid, $type, $name);
        if(!($file = Functions::file_open($filename, "w"))) return;
        fwrite($file, json_encode($obj));
        fclose($file);
        echo "\n --- cache created [$type - $filename]";
        usleep(500000); // 5 tenths of a second = 5/10 of a second
        return $obj;
        */
    }

    private function soap_request($guid, $type, $name)
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
                    elseif($type == 4) return $client->matchTaxon($name);
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
        return preg_replace('/\s+/', ' ', $str);
        // return str_ireplace(array("\t","\n","\r"), " ", $str);
    }
    
    private function get_genus($sciname)
    {
        $genus = "";
        if(is_numeric(stripos($sciname, " ")))
        {
            $parts = explode(" ", $sciname);
            $genus = $parts[0];
        }
        return $genus;
    }
    
    private function get_parent_from_portal($guid)
    {   /*
        <small><b>Higher Classification: </b>>
        &nbsp;Kingdom <b><a href="taxon.php?GUID=urn:lsid:marinespecies.org:taxname:2">Animalia</a></b> >
        &nbsp;Phylum <b><a href="taxon.php?GUID=urn:lsid:marinespecies.org:taxname:592916">Xenacoelomorpha</a></b> >
        &nbsp;Subphylum <b><a href="taxon.php?GUID=urn:lsid:marinespecies.org:taxname:380603">Acoelomorpha</a></b> 
        </small>
        */
        $options = $this->download_options;
        $options["expire_seconds"] = false; //always false, doesn't expire coz it is a heavy request and ancestry doesn't change for most taxa
        if($html = Functions::lookup_with_cache($this->path_summary_page.$guid, $options))
        {
            if(preg_match("/<b>Higher Classification(.*?)<\/small>/ims", $html, $arr))
            {
                if(preg_match_all("/&nbsp;(.*?)<\/a>/ims", $arr[1], $arr2))
                {
                    // print_r($arr2[1]);
                    //get last record
                    $last = count($arr2[1]);
                    $temp = $arr2[1][$last-1];
                    // echo "\n".$temp."\n"; //Kingdom <b><a href="taxon.php?GUID=urn:lsid:marinespecies.org:taxname:4">Fungi
                    if(preg_match("/GUID=(.*?)\"/ims", "xxx".$temp, $arr3)) $rec["guid"] = $arr3[1];
                    if(preg_match("/\">(.*?)xxx/ims", $temp."xxx", $arr3)) $rec["taxon"] = $arr3[1];
                    if(preg_match("/xxx(.*?)<b>/ims", "xxx".$temp, $arr3)) $rec["rank"] = trim($arr3[1]);
                    // print_r($rec);
                    return $rec;
                }
            }
        }
    }
    
    /* may not be needed anymore
    
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
        if(stripos($subspecies, "subsp") === false) //string is not found
        {
            $string = explode(" ", $subspecies);
            switch(count($string))
            {
                case 2:
                     $species = $string[0] . " " . $string[1];
                     break;
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
            if(!isset($species))
            {
                echo "\n- will stop -\n";
                print_r($string);
                exit("\nno species variable [$subspecies]\n");
            }
            // echo "\n subspecies:[$subspecies] [" . trim(str_ireplace(" subsp.", "", $species)) . "]\n";
            return trim(str_ireplace(" subsp.", "", $species));
        }
        else
        {
            $string = explode("subsp", $subspecies);
            return trim($string[0]);
        }
    }
    */
    
    /*
    <OPTION VALUE= "10">Kingdom</OPTION>
    <OPTION VALUE= "20">Subkingdom</OPTION>
    <OPTION VALUE= "23">Superphylum</OPTION>
    <OPTION VALUE= "25">Infrakingdom</OPTION>
    <OPTION VALUE= "30">Division</OPTION>
    <OPTION VALUE= "30">Phylum</OPTION>
    <OPTION VALUE= "40">Subdivision</OPTION>
    <OPTION VALUE= "40">Subphylum</OPTION>
    <OPTION VALUE= "45">Infraphylum</OPTION>
    <OPTION VALUE= "50">Superclass</OPTION>
    <OPTION VALUE= "60">Class</OPTION>
    <OPTION VALUE= "70">Subclass</OPTION>
    <OPTION VALUE= "80">Infraclass</OPTION>
    <OPTION VALUE= "90">Superorder</OPTION>
    <OPTION VALUE= "100">Order</OPTION>
    <OPTION VALUE= "110">Suborder</OPTION>
    <OPTION VALUE= "120">Infraorder</OPTION>
    <OPTION VALUE= "121">Section</OPTION>
    <OPTION VALUE= "122">Subsection</OPTION>
    <OPTION VALUE= "130">Superfamily</OPTION>
    <OPTION VALUE= "140">Family</OPTION>
    <OPTION VALUE= "150">Subfamily</OPTION>
    <OPTION VALUE= "160">Tribe</OPTION>
    <OPTION VALUE= "170">Subtribe</OPTION>
    <OPTION VALUE= "180">Genus</OPTION>
    <OPTION VALUE= "190">Subgenus</OPTION>
    <OPTION VALUE= "200">Section</OPTION>
    <OPTION VALUE= "210">Subsection</OPTION>
    <OPTION VALUE= "212">Series</OPTION>
    <OPTION VALUE= "214">Subseries</OPTION>
    <OPTION VALUE= "216">Aggregate</OPTION>
    <OPTION VALUE= "218">Coll. Species</OPTION>
    <OPTION VALUE= "220" SELECTED >Species</OPTION>
    <OPTION VALUE= "225">Grex</OPTION>
    <OPTION VALUE= "230">Subspecies</OPTION>
    <OPTION VALUE= "232">Proles</OPTION>
    <OPTION VALUE= "234">Race</OPTION>
    <OPTION VALUE= "235">Natio</OPTION>
    <OPTION VALUE= "236">Convariety</OPTION>
    <OPTION VALUE= "240">Variety</OPTION>
    <OPTION VALUE= "250">Subvariety</OPTION>
    <OPTION VALUE= "260">Form</OPTION>
    <OPTION VALUE= "270">Subform</OPTION>
    <OPTION VALUE= "275">Form spec.</OPTION>
    <OPTION VALUE= "280">Tax. infragen.</OPTION>
    <OPTION VALUE= "285">Tax. infraspec.</OPTION>
    
    Tax. infraspec. (325)
    Tax. infragen. (12)
    Form spec. (14)
    Subform (83)
    Form (2743)
    Subvariety (294)
    Variety (15033)
    Convariety (4)
    Race (23)
    Proles (55)
    Subspecies (55474)
    Grex (15)
    Species (392290)
    Coll. Species (171)
    Aggregate (281)
    Subsection (11)
    Section (168)
    Subgenus (5275)
    Genus (52198)
    Subtribe (173)
    Tribe (1360)
    Subfamily (2325)
    Family (6526)
    Superfamily (1030)
    Kingdom (7)
    Infraorder (123)
    Suborder (425)
    Order (1161)
    Superorder (98)
    Infraclass (22)
    Subclass (218)
    Class (320)
    Superclass (10)
    Infraphylum (8)
    Subdivision (17)
    Subphylum (45)
    Phylum (81)
    Division (18)
    Infrakingdom (6)
    Subkingdom (14)
    */
}
?>