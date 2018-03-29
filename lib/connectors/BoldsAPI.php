<?php
namespace php_active_record;
/* connector: 81 
--- BOLDS resource for higher-level taxa [81]. This is now scheduled as a cron task.
Connector uses an API for the higher-level taxon info. If it fails, it scrapes data from BOLDS website.
Before running the connector, it is assumed that this file has already been created: DOC_ROOT . "/update_resources/connectors/files/BOLD/hl_master_list.txt"

Main API page: http://www.boldsystems.org/index.php/Resources

published:  true        false
as of:      14Aug2014   19Aug2014
taxa:       47995       47995
texts       86438       86438
images      47812       38443
*/

class BoldsAPI
{
    const SPECIES_SERVICE_URL = "http://www.boldsystems.org/index.php/Taxbrowser_Taxonpage?taxid=";
    const MAP_PARTIAL_URL = "/index.php/TaxBrowser_Maps_CollectionSites?taxid=";
    const MAP_SCALE = "/libhtml/icons/mapScale_BOLD.png";
    const BOLDS_DOMAIN = "http://www.boldsystems.org";

    public function __construct()
    {
        $this->TEMP_FILE_PATH         = DOC_ROOT . "/update_resources/connectors/files/BOLD/";
        $this->WORK_LIST              = DOC_ROOT . "/update_resources/connectors/files/BOLD/hl_work_list.txt"; //hl - higher level taxa
        $this->WORK_IN_PROGRESS_LIST  = DOC_ROOT . "/update_resources/connectors/files/BOLD/hl_work_in_progress_list.txt";
        $this->INITIAL_PROCESS_STATUS = DOC_ROOT . "/update_resources/connectors/files/BOLD/hl_initial_process_status.txt";
        $this->MASTER_LIST            = DOC_ROOT . "/update_resources/connectors/files/BOLD/hl_master_list.txt";
        // $this->MASTER_LIST            = DOC_ROOT . "/update_resources/connectors/files/BOLD/hl_master_list_small.txt"; // debug
        $this->service["id"] = "http://www.boldsystems.org/index.php/API_Tax/TaxonData?dataTypes=basic,stats,geo&includeTree=true&taxId=";
        // for stats
        $this->TEMP_DIR = create_temp_dir() . "/";
        $this->erroneous_ids = $this->TEMP_DIR . "erroneous_ids.txt";
        $this->does_not_exist_anymore = $this->TEMP_DIR . "does_not_exist_anymore.txt";
        $this->download_options = array('expire_seconds' => 7776000, 'download_wait_time' => 500000, 'timeout' => 1200, 'download_attempts' => 2);
        // $this->download_options['cache_path'] = "/Volumes/Eli blue/eol_cache/";
    }

    function initialize_text_files()
    {
        if(!($f = Functions::file_open($this->WORK_LIST, "w"))) {}
        else fclose($f);
        if(!($f = Functions::file_open($this->WORK_IN_PROGRESS_LIST, "w"))) {}
        else fclose($f);
        if(!($f = Functions::file_open($this->INITIAL_PROCESS_STATUS, "w"))) {}
        else fclose($f);
        //this is not needed but just to have a clean directory
        Functions::delete_temp_files($this->TEMP_FILE_PATH . "batch_", "txt");
        Functions::delete_temp_files($this->TEMP_FILE_PATH . "temp_Bolds_" . "batch_", "xml");
    }

    function start_process($resource_id, $call_multiple_instance)
    {
        $this->resource_id = $resource_id;
        $this->call_multiple_instance = $call_multiple_instance;
        $this->connectors_to_run = 1;
        if(!trim(Functions::get_a_task($this->WORK_IN_PROGRESS_LIST)))//don't do this if there are harvesting task(s) in progress
        {
            if(!trim(Functions::get_a_task($this->INITIAL_PROCESS_STATUS)))//don't do this if initial process is still running
            {
                // Divide the big list of ids into small files
                Functions::add_a_task("Initial process start", $this->INITIAL_PROCESS_STATUS);
                $batch = Functions::create_work_list_from_master_file($this->MASTER_LIST, 5000, $this->TEMP_FILE_PATH, "batch_", $this->WORK_LIST); //debug orig value 5000
                Functions::delete_a_task("Initial process start", $this->INITIAL_PROCESS_STATUS);
            }
        }
        Functions::process_work_list($this, $batch);
        if(!$task = trim(Functions::get_a_task($this->WORK_IN_PROGRESS_LIST))) //don't do this if there are task(s) in progress
        {
            // Combine all XML files.
            Functions::combine_all_eol_resource_xmls($resource_id, $this->TEMP_FILE_PATH . "temp_Bolds_batch_*.xml");
            // Delete temp files
            Functions::delete_temp_files($this->TEMP_FILE_PATH . "batch_", "txt");
            Functions::delete_temp_files($this->TEMP_FILE_PATH . "temp_Bolds_" . "batch_", "xml");
        }
    }

    function get_all_taxa($task, $temp_path = null, $info = array())
    {
        $all_taxa = array();
        $used_collection_ids = array();
        $filename = $this->TEMP_FILE_PATH . $task . ".txt";
        $i = 0;
        $save_count = 0;
        $no_eol_page = 0;
        $total = Functions::count_rows_from_text_file($filename);
        foreach(new FileIterator($filename) as $line_number => $line)
        {
            $split = explode("\t", trim($line));
            if(!@$split[0]) continue;
            $taxon = array("sciname" => $split[1] , "id" => $split[0], "rank" => @$split[2]);
            $i++;
            echo "\n $info[1] of $info[0]";
            echo "\n $i of $total -- " . $taxon['sciname'] . " $taxon[id] \n";
            $arr = self::get_Bolds_taxa($taxon, $used_collection_ids);
            $page_taxa              = $arr[0];
            $used_collection_ids    = $arr[1];
            if($page_taxa) $all_taxa = array_merge($all_taxa, $page_taxa);
            unset($page_taxa);
            // if($i >= 2) break; //debug

        }
        $xml = \SchemaDocument::get_taxon_xml($all_taxa);
        $xml = str_replace("</mediaURL>", "</mediaURL><additionalInformation><subtype>map</subtype>\n</additionalInformation>\n", $xml);
        $resource_path = $this->TEMP_FILE_PATH . "temp_Bolds_" . $task . ".xml";
        if(!($OUT = Functions::file_open($resource_path, "w"))) return;
        fwrite($OUT, $xml); 
        fclose($OUT);
        echo "\n\n total = $i \n\n";
        
        // remove temp dir
        recursive_rmdir($this->TEMP_DIR); // debug - uncomment in real operation
        echo ("\n temporary directory removed: " . $this->TEMP_DIR);
    }

    function get_Bolds_taxa($taxon, $used_collection_ids)
    {
        $response = self::prepare_object($taxon);//this will output the raw (but structured) array
        $page_taxa = array();
        foreach($response as $rec)
        {
            if(@$used_collection_ids[$rec["identifier"]]) continue;
            $taxon = Functions::prepare_taxon_params($rec);
            if($taxon) $page_taxa[] = $taxon;
            @$used_collection_ids[$rec["identifier"]] = true;
        }
        return array($page_taxa, $used_collection_ids);
    }

    private function prepare_object($taxon_rec)
    {
        $source = self::SPECIES_SERVICE_URL . urlencode($taxon_rec["id"]);
        $arr_taxa = array();
        $arr_objects = array();

        $arr = self::get_taxon_details_via_api($taxon_rec["id"]);
        $taxa = @$arr[0];
        $bold_stats = @$arr[1];
        $species_level = @$arr[2];
        $public_records = @$arr[3];
        $with_map = @$arr[4];

        if(!$taxa && !$bold_stats && !$species_level) return array();

        // check if there is content
        $description = self::check_if_with_content($taxon_rec, $public_records);
        if(!$description && !$taxa) return array();

        //start #########################################################################  

        //same for all text objects
        $mimeType   = "text/html";
        $dataType   = "http://purl.org/dc/dcmitype/Text";
        $subject    = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#MolecularBiology"; //debug MolecularBiology
        $agent = array();
        $agent[] = array("role" => "compiler", "homepage" => self::BOLDS_DOMAIN . "/", "fullName" => "Sujeevan Ratnasingham");
        $agent[] = array("role" => "compiler", "homepage" => self::BOLDS_DOMAIN . "/", "fullName" => "Paul D.N. Hebert");
        $license = "http://creativecommons.org/licenses/by/3.0/";
        $rightsHolder = "Barcode of Life Data Systems";

        //1st text object
        if($description)
        {
            $identifier = $taxon_rec["id"] . "_barcode_data";
            $title      = "Barcode data";
            $mediaURL   = ""; 
            $location   = "";
            $refs       = array();
            $arr_objects[] = self::add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $agent, $license, $location, $rightsHolder, $refs, $subject);
        }

        //another text object
        if($bold_stats)
        {
            $description = "Barcode of Life Data Systems (BOLD) Stats <br> $bold_stats";
            $description = str_ireplace("\t", "", $description);
            $identifier = $taxon_rec["id"] . "_stats";
            $title = "Statistics of barcoding coverage";
            $mediaURL   = ""; 
            $location   = "";
            $refs       = array();
            $arr_objects[] = self::add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $agent, $license, $location, $rightsHolder, $refs, $subject);
        }

        //another text object
        if($with_map)
        {
            $map_url = self::BOLDS_DOMAIN . self::MAP_PARTIAL_URL . $taxon_rec["id"];
            $map_scale_url = self::BOLDS_DOMAIN . self::MAP_SCALE;
            $description = "Collection Sites: world map showing specimen collection locations for <i>" . $taxon_rec["sciname"] . "</i><br><img border='0' src='$map_url'><br><img src='$map_scale_url'>";
            $identifier  = $taxon_rec["id"] . "_map";
            $title = "Locations of barcode samples";
            $mediaURL   = "";
            $location   = "";
            $refs       = array();
            /* DATA-1491 Remove map text objects from BOLDS
            $arr_objects[] = self::add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $agent, $license, $location, $rightsHolder, $refs, $subject);
            */

            // map as image object
            $identifier  = $taxon_rec["id"] . "_image_map";
            $dataType    = "http://purl.org/dc/dcmitype/StillImage"; 
            $mimeType    = "image/png";
            $title       = "BOLDS: Map of specimen collection locations for <i>" . $taxon_rec["sciname"] . "</i>";
            $source      = self::SPECIES_SERVICE_URL . trim($taxon_rec["id"]);
            $mediaURL    = $map_url;
            $description = "Collection Sites: world map showing specimen collection locations for <i>" . $taxon_rec["sciname"] . "</i><br><img src='$map_scale_url'>";
            $arr_objects[] = self::add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $agent, $license, $location, $rightsHolder, $refs, "");
        }
        else echo "\n no map for $taxon_rec[id] \n";
        
        if(sizeof($arr_objects))
        {
            $arr_taxa[]=array(  "identifier"   => $taxon_rec["id"],
                                "source"       => self::SPECIES_SERVICE_URL . urlencode($taxon_rec["id"]),
                                "kingdom"      => utf8_encode(@$taxa["kingdom"]),
                                "phylum"       => utf8_encode(@$taxa["phylum"]),
                                "class"        => utf8_encode(@$taxa["class"]),
                                "order"        => utf8_encode(@$taxa["order"]),
                                "family"       => utf8_encode(@$taxa["family"]),
                                "genus"        => utf8_encode(@$taxa["genus"]),
                                "sciname"      => utf8_encode($taxon_rec["sciname"]),
                                "data_objects" => $arr_objects);
        }
        return $arr_taxa;
    }

    private function get_taxon_details_via_api($taxid)
    {
        /* this function will get:
            taxonomy
            BOLD stats
            boolean if species-level taxa
            if id/url is resolvable
            boolean if taxon has map (collections sites)
        */
        $arr = array();
        $file = $this->service["id"] . $taxid;
        
        $with_map = false;
        $species_level = false;
        $public_records = "";
        $taxa = array();
        $str = "";
        if($json = Functions::lookup_with_cache($file, $this->download_options))
        {
            $rec = json_decode($json, true);
            if(@$rec[$taxid]["sitemap"]) $with_map = true;
            
            if(@$rec[$taxid]["stats"]["publicrecords"] == 0 &&
               @$rec[$taxid]["stats"]["publicspecies"] == 0 &&
               @$rec[$taxid]["stats"]["publicbins"]    == 0) $with_map = false;
            
            if(@$rec[$taxid]["tax_rank"] == "species") $species_level = true;
            $public_records = @$rec[$taxid]["stats"]["publicrecords"];
            //get tree
            if($rec)
            {
                $keys = array_keys($rec);
                foreach($keys as $key)
                {
                    if($key == $taxid) continue;
                    $taxa[$rec[$key]["tax_rank"]] = $rec[$key]["taxon"];
                }
            }
            //get stats
            if(@$rec[$taxid]["stats"])
            {
                $str .= "Specimen Records:"         . @$rec[$taxid]["stats"]["specimenrecords"]     . "<br>";
                $str .= "Specimens with Sequences:" . @$rec[$taxid]["stats"]["sequencedspecimens"]  . "<br>";
                $str .= "Specimens with Barcodes:"  . @$rec[$taxid]["stats"]["barcodespecimens"]    . "<br>";
                $str .= "Species:"                  . @$rec[$taxid]["stats"]["species"]             . "<br>";
                $str .= "Species With Barcodes:"    . @$rec[$taxid]["stats"]["barcodespecies"]      . "<br>";
                $str .= "Public Records:"           . @$rec[$taxid]["stats"]["publicrecords"]       . "<br>";
                $str .= "Public Species:"           . @$rec[$taxid]["stats"]["publicspecies"]       . "<br>";
                $str .= "Public BINs:"              . @$rec[$taxid]["stats"]["publicbins"]          . "<br>";
                // "publicmarkersequences":{"CYTB":8,"COXIII":8,"COII":8,"COI-5P":613,"atp6":3} -- available in api 
            }
        }
        else // fail in lookup_with_cache() but found in website, e.g. taxid = 246761
        {
            echo " -Taxonomy Browser - No Match in API 01 - [$taxid]";
            $arr = self::get_taxon_details($taxid);
            return $arr;
        }

        if(!$taxa && !$str) // found in API but no valid record, e.g taxid = 251768
        {
            echo " -Taxonomy Browser - No Match in API 02 - [$taxid]";
            $arr = self::get_taxon_details($taxid);
            return $arr;
        }
        return array($taxa, $str, $species_level, $public_records, $with_map);
    }

    private function save_to_dump($data, $filename)
    {
        if(!($WRITE = Functions::file_open($filename, "a"))) return;
        if($data && is_array($data)) fwrite($WRITE, json_encode($data) . "\n");
        else                         fwrite($WRITE, $data . "\n");
        fclose($WRITE);
    }

    private function get_taxon_details($taxid)
    {
        echo "\n Will try the website...\n";
        /* this function will get:
            taxonomy
            BOLD stats
            boolean if species-level taxa
            if id/url is resolvable
            boolean if taxon has map (collections sites)
        */

        /*
        <span class="taxon_name">Aphelocoma californica PS-1 {species}&nbsp;
            <a title="phylum"href="taxbrowser.php?taxid=18">Chordata</a>;
            <a title="class"href="taxbrowser.php?taxid=51">Aves</a>;
            <a title="order"href="taxbrowser.php?taxid=321">Passeriformes</a>;
            <a title="family"href="taxbrowser.php?taxid=1160">Corvidae</a>;
            <a title="genus"href="taxbrowser.php?taxid=4698">Aphelocoma</a>;
        </span>
        <span class="taxon_name">Gastrolepidia {genus}&nbsp;
            <a title="phylum"href="taxbrowser.php?taxid=2">Annelida</a>;
            <a title="class"href="taxbrowser.php?taxid=24489">Polychaeta</a>;
            <a title="order"href="taxbrowser.php?taxid=25265">Phyllodocida</a>;
            <a title="family"href="taxbrowser.php?taxid=28521">Polynoidae</a>;
        </span>
        */

        $arr = array();
        $file = self::SPECIES_SERVICE_URL . $taxid;
        $orig_str = Functions::lookup_with_cache($file, $this->download_options);

        if(is_numeric(stripos($orig_str, "Taxonomy Browser - No Match")))
        {
            self::save_to_dump($taxid, $this->does_not_exist_anymore);
            echo " -Taxonomy Browser - No Match in website either- [$taxid]";
            return array(false, false, false, false, false);
        }
        else // report these taxids to BOLDS
        {
            echo "\n Found in website...\n";
            self::save_to_dump($taxid, $this->erroneous_ids);
        }
        
        //check if there is map:
        $pos = stripos($orig_str, self::MAP_PARTIAL_URL);
        if(is_numeric($pos)) $with_map = true;
        else                 $with_map = false;
        
        //side script - to check if id/url is even resolvable
        if(is_numeric(stripos($orig_str, "fatal error")))
        {
            echo " -fatal error found- [$taxid]";
            return array(false, false, false, false, false);
        }

        $str = $orig_str;
        if(preg_match("/taxon_name\">(.*?)<\/span>/ims", $str, $matches)) $str = $matches[1]; 

        //side script to check if species level taxa
        $pos = stripos($str, "{species}");    
        if(is_numeric($pos)) $species_level = true;
        else                 $species_level = false;

        $str = str_ireplace('<a title=', 'xxx<a title=', $str);
        $str = str_ireplace('</a>', '</a>yyy', $str);
        $str = str_ireplace('xxx', "&arr[]=", $str);
        $arr = array();
        parse_str($str);
        $taxa = array();
        foreach ($arr as $a)
        {
            $index = self::get_title_from_anchor_tag($a);
            $taxa[$index] = self::get_str_from_anchor_tag($a);
        }

        //=========================================================================//start get BOLD stats
        $public_records = 0;
        $str = "";
        // if(preg_match("/<h2>BOLD Stats<\/h2>(.*?)<\/table>/ims", $orig_str, $matches)) $str = $matches[1]; old site
        if(preg_match("/<h3><a href=\"#\">BOLD Stats<\/a><\/h3>(.*?)<\/table>/ims", $orig_str, $matches)) 
        {
            $str = $matches[1];
            $str = strip_tags($str, "<tr><td><table>");
            $str = str_ireplace('width="100%"', "", $str);
            $pos = stripos($str, "Species List - Progress"); 
            $str = substr($str, 0, $pos) . "</td></tr></table>";
            $str = str_ireplace('<table width="30%" >', '<table>', $str);

            // get "public records" count
            if(preg_match("/public records:(.*?)<\/tr>/ims", $str, $matches)) 
            {
                $public_records = intval(str_ireplace(",", "", trim(strip_tags($matches[1]))));
                echo "\npublic records:[$public_records]\n";
            }
        }
        //=========================================================================

        return array($taxa, $str, $species_level, $public_records, $with_map);
    }

    private function add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $agent, $license, $location, $rightsHolder, $refs, $subject)
    {
        return array( "identifier"   => $identifier,
                      "dataType"     => $dataType,
                      "mimeType"     => $mimeType,
                      "title"        => $title,
                      "source"       => $source,
                      "description"  => $description,
                      "mediaURL"     => $mediaURL,
                      "agent"        => $agent,
                      "license"      => $license,
                      "location"     => $location,
                      "rightsHolder" => $rightsHolder,
                      "object_refs"  => $refs,
                      "subject"      => $subject);
    }

    private function get_str_from_anchor_tag($str)
    {
        if(preg_match("/\">(.*?)<\/a>/ims", $str, $matches)) return $matches[1];
    }

    private function get_title_from_anchor_tag($str)
    {
        if(preg_match("/<a title=\"(.*?)\"/ims", $str, $matches)) return $matches[1];
    }

    public function check_if_with_content($taxon_rec, $public_records)
    {
        if($public_records > 0)
        {
            // $taxid = $taxon_rec["id"];
            $url = "http://www.boldsystems.org/index.php/Public_SearchTerms?query=" . $taxon_rec["sciname"];
            $description = "<a target='" . $taxon_rec["sciname"] . "' href='$url'>Access Published & Released Data: Download FASTA File</a>";
            return $description;
        }
        else return false;
    }

}
?>