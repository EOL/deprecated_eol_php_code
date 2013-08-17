<?php
namespace php_active_record;
// connector: [671]
class MycoBankAPI
{
    function __construct($folder)
    {
        $this->taxa = array();
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->resource_reference_ids = array();
        $this->resource_agent_ids = array();
        $this->taxon_ids = array();
        $this->service = 'http://www.mycobank.org/Services/Generic/SearchService.svc/rest/xml?layout=14682616000000161&limit=0&filter=NameStatus_="Legitimate" AND Name STARTSWITH ';
        $this->TEMP_DIR = create_temp_dir() . "/";
        $this->dump_file = $this->TEMP_DIR . "mycobank_dump.txt";
        $this->raw_classification = array();
        $this->name_id = array();
        /*
        params with record(s): 1750
        */
    }

    function get_all_taxa()
    {
        print "\n[$this->TEMP_DIR]\n";

        /* to be used if you already have generated dump files.
        $file = "/Users/eolit/Sites/eli/eol_php_code/tmp/mycobank/mycobank_dump.txt";
        $this->process_raw_taxa($file);
        $file = "/Users/eolit/Sites/eli/eol_php_code/tmp/mycobank/raw_classification.txt";
        $this->process_raw_classification($file);
        */
        
        $this->save_data_to_text();
        $this->process_raw_taxa();
        $this->process_raw_classification();

        $this->create_instances_from_taxon_object();
        $this->create_archive();
        // remove temp dir
        recursive_rmdir($this->TEMP_DIR);
        echo ("\n temporary directory removed: " . $this->TEMP_DIR);
    }

    private function save_data_to_text()
    {
        $params = self::get_params_for_webservice();
        // $params = array("Abac", "Aa", "Acab");
        // $params = array("Dfr", "Dfs", "Lec", "Led", "Lee", "Lef", "Leg", "Leh", "Lei", "Lej", "Lek", "Phy");
        $total_params = count($params);
        self::initialize_dump_file();
        $i = 0;
        foreach($params as $param)
        {
            $i++;
            $no_of_results = 0;
            $param = ucfirst(strtolower($param));
            $url = $this->service . '"' . $param . '"';
            print "\n[$param] $i of $total_params \n";

            $trials = 0;
            while($trials <= 5)
            {
                $trials++;
                if($response = Functions::get_hashed_response($url, DOWNLOAD_WAIT_TIME, 10800, 2))
                {
                    $no_of_results = count($response);
                    if($no_of_results > 0)
                    {
                        print " - count: $no_of_results";
                        $records = array();
                        foreach($response as $rec)
                        {
                            $hierarchy = "";
                            $source_url = "";
                            $parent = "";
                            if(preg_match("/title\='(.*?)'/ims", $rec->Classification_, $arr)) 
                            {
                                $hierarchy = $arr[1];
                                $this->raw_classification[$hierarchy] = "";
                                $parent = self::get_parent_from_hierarchy($hierarchy);
                            }
                            if(preg_match("/href\='(.*?)'/ims", $rec->Classification_, $arr)) $source_url = $arr[1];
                            $records[] = array("n" => (string) $rec->Name,
                                               "a" => (string) $rec->Authors_,
                                               "p" => $parent,
                                               "s" => $source_url,
                                               "t" => (string) $rec->MycoBankNr_,
                                               "y" => (string) $rec->NameYear_);
                        }
                        $temp = array();
                        $temp[$param] = $records;
                        self::save_to_dump($temp, $this->dump_file);
                    }
                    break;
                }
                else
                {
                    print "\n\n investigate 01 [$url] will delay 5 minutes... then will try again [$trials]\n";
                    sleep(300);
                }
            }//while
            if(!$response) print "\n trials expired [$trials]\n";
            
            self::sleep_now($no_of_results);
        }
        self::save_to_dump(array_keys($this->raw_classification), $this->TEMP_DIR . "raw_classification.txt");
    }

    private function process_raw_classification($filename = false)
    {
        if(!$filename) $filename = $this->TEMP_DIR . "raw_classification.txt";
        $READ = fopen($filename, "r");
        $contents = fread($READ, filesize($filename));
        fclose($READ);
        $hierarchies = json_decode($contents, true);
        // print_r($hierarchies);
        foreach($hierarchies as $hierarchy)
        {
            $names = explode(",", $hierarchy);
            $names = array_map('trim', $names);
            $i = 0;
            foreach($names as $name)
            {
                if(!isset($this->name_id[$name]))
                {
                    $this->name_id[$name]["p"] = @$names[$i-1];
                }
                $i++;
            }
        }
        // print_r($this->name_id);
    }

    private function process_raw_taxa($dump_file = false)
    {
        if(!$dump_file) $dump_file = $this->dump_file;
        foreach(new FileIterator($dump_file) as $line_number => $line)
        {
            if($line)
            {
                $line = trim($line);
                $records = json_decode($line, true);
                foreach($records as $key => $recs)
                {
                    foreach($recs as $rec)
                    {
                        $name = $rec["n"];
                        if(!isset($this->name_id[$name]))
                        {
                            $this->name_id[$name]["a"] = $rec["a"];
                            $this->name_id[$name]["p"] = $rec["p"];
                            $this->name_id[$name]["s"] = $rec["s"];
                            $this->name_id[$name]["t"] = $rec["t"];
                            $this->name_id[$name]["y"] = $rec["y"];
                        }
                    }
                }
            }
        }
    }

    function create_instances_from_taxon_object()
    {
        foreach($this->name_id as $sciname => $rec)
        {
            $parent = "";
            $taxon_id = "";
            $parentNameUsageID = "";
            if($parent = $rec["p"])
            {
                if(!$parentNameUsageID = @$this->name_id[$parent]["t"]) $parentNameUsageID = $parent;
            }
            if(!$taxon_id = @$rec["t"]) $taxon_id = $sciname;
            if(in_array($sciname, array("-", "?"))) continue;
            $taxon = new \eol_schema\Taxon();
            $taxon->taxonID                     = (string) $taxon_id;
            $taxon->scientificName              = (string) $sciname;
            $taxon->scientificNameAuthorship    = (string) @$rec["a"];
            $taxon->furtherInformationURL       = (string) @$rec["s"];
            if(!in_array(trim($parentNameUsageID), array("-", "?"))) $taxon->parentNameUsageID = $parentNameUsageID;
            if(!$parentNameUsageID) 
            {
                echo "\n investigate 02 [$sciname] \n"; // parent is not available for some reason, acceptable cases when checked
                print_r($rec);
            }
            if(!isset($this->taxon_ids[$taxon->taxonID]))
            {
                $this->taxa[$taxon->taxonID] = $taxon;
                $this->taxon_ids[$taxon->taxonID] = 1;
            }
            else
            {
                echo "\n investigate 03 [$sciname] \n"; // this means that a 'legitimate' taxon using this id is already entered, acceptable case when checked
                print_r($rec);
            }
        }
    }

    function utility_append_text()
    {
        $filename = "/Users/eolit/Sites/eli/eol_php_code/tmp/mycobank/mycobank_dump_additional.txt";
        $READ = fopen($filename, "r");
        $contents = fread($READ, filesize($filename));
        fclose($READ);

        $filename = "/Users/eolit/Sites/eli/eol_php_code/tmp/mycobank/mycobank_dump.txt";
        $WRITE = fopen($filename, "a");
        fwrite($WRITE, $contents);
        fclose($WRITE);
    }
    
    private function save_to_dump($array, $filename)
    {
        $WRITE = fopen($filename, "a");
        fwrite($WRITE, json_encode($array) . "\n");
        fclose($WRITE);
    }

    private function initialize_dump_file()
    {
        $WRITE = fopen($this->dump_file, "w");
        fclose($WRITE);
    }

    private function sleep_now($results)
    {
        if($results > 1000) sleep(20);
        elseif($results > 500) sleep(15);
        elseif($results > 250) sleep(10);
        elseif($results > 100) sleep(5);
        elseif($results > 50) sleep(5);
        elseif($results > 25) sleep(2);
        elseif($results > 0) sleep(1);
        elseif($results == 0) usleep(500000);
    }

    private function get_params_for_webservice()
    {
        $params = array();
        $letters = "A,B,C,D,E,F,G,H,I,J,K,L,M,N,O,P,Q,R,S,T,U,V,W,X,Y,Z";
        $letters1 = explode(",", $letters);
        $letters2 = $letters1;
        $letters3 = $letters1;
        $letters4 = $letters1;
        foreach($letters1 as $L1)
        {
            foreach($letters2 as $L2)
            {
                foreach($letters3 as $L3)
                {
                    $params[] = "$L1$L2$L3";
                }
            }
        }
        return $params;
    }

    private function get_parent_from_hierarchy($hierarchy)
    {
        $hierarchy = explode(",", $hierarchy);
        return trim(array_pop($hierarchy));
    }

    private function create_synonym($rec)
    {
        $synonym = new \eol_schema\Taxon();
        if(!is_numeric($rec["taxon_id"])) 
        {
            echo "\n investigate 04";
            print_r($rec);
        }
        $rec["sciname"] = self::remove_brackets($rec["sciname"]);
        $rec["authorship"] = self::remove_brackets($rec["authorship"]);
        if(!Functions::is_utf8($rec["sciname"]) || !Functions::is_utf8($rec["authorship"])) return;
        $synonym->taxonID                       = (string) $rec["taxon_id"];
        $synonym->scientificName                = (string) $rec["sciname"];
        $synonym->scientificNameAuthorship      = (string) $rec["authorship"];
        $synonym->taxonRank                     = (string) $rec["rank"];
        $synonym->acceptedNameUsageID           = (string) $rec["acceptedNameUsageID"];
        $synonym->taxonomicStatus               = (string) "synonym";
        if(!$synonym->scientificName) return;
        if(!isset($this->taxon_ids[$synonym->taxonID]))
        {
            $this->archive_builder->write_object_to_file($synonym);
            $this->taxon_ids[$synonym->taxonID] = 1;
            $this->syn_count++;
        }
    }

    function create_archive()
    {
        foreach($this->taxa as $t)
        {
            $this->archive_builder->write_object_to_file($t);
        }
        $this->archive_builder->finalize(TRUE);
    }

}
?>