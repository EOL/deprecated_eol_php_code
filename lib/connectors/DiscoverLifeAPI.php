<?php
namespace php_active_record;

/*
Steps before accepting a name from DiscoverLife
- search the name through the API e.g. api/search/Gadus morhua
- if name == canonical_form(entry->title), proceed
- if there is multiple results, use the name with the most no. of data objects
*/

class DiscoverLifeAPI
{
    const DL_MAP_SPECIES_LIST   = "http://www.discoverlife.org/export/species_map.txt";
    const DL_SEARCH_URL         = "http://www.discoverlife.org/mp/20q?search=";
    const DL_MAP_URL            = "http://www.discoverlife.org/20/m?kind=";
    const DL_MAP_SRC            = "http://www.discoverlife.org/mp/20m?map=";
    private static $TEXT_FILE_FOR_DL;

    private static $TEMP_FILE_PATH;
    private static $WORK_LIST;
    private static $WORK_IN_PROGRESS_LIST;
    private static $INITIAL_PROCESS_STATUS;

    function start_process($resource_id, $call_multiple_instance)
    {
        self::$TEMP_FILE_PATH         = DOC_ROOT . "/update_resources/connectors/files/DiscoverLife/";
        self::$WORK_LIST              = DOC_ROOT . "/update_resources/connectors/files/DiscoverLife/work_list.txt";
        self::$WORK_IN_PROGRESS_LIST  = DOC_ROOT . "/update_resources/connectors/files/DiscoverLife/work_in_progress_list.txt";
        self::$INITIAL_PROCESS_STATUS = DOC_ROOT . "/update_resources/connectors/files/DiscoverLife/initial_process_status.txt";

        self::$TEXT_FILE_FOR_DL       = DOC_ROOT . "/update_resources/connectors/files/DiscoverLife/names_without_pages_in_eol.txt"; //report back to DiscoverLife

        if(!trim(Functions::get_a_task(self::$WORK_IN_PROGRESS_LIST)))//don't do this if there are harvesting task(s) in progress
        {
            if(!trim(Functions::get_a_task(self::$INITIAL_PROCESS_STATUS)))//don't do this if initial process is still running
            {
                // Divide the big list of ids into small files
                Functions::add_a_task("Initial process start", self::$INITIAL_PROCESS_STATUS);
                self::divide_text_file(10000); //orig value 10000
                Functions::delete_a_task("Initial process start", self::$INITIAL_PROCESS_STATUS);
            }
        }

        // Run multiple instances, for DiscoverLife ideally a total of 2
        while(true)
        {
            $task = Functions::get_a_task(self::$WORK_LIST);//get a task to work on
            if($task)
            {
                print "\n Process this: $task";
                Functions::delete_a_task($task, self::$WORK_LIST);
                Functions::add_a_task($task, self::$WORK_IN_PROGRESS_LIST);
                $task = str_ireplace("\n", "", $task);//remove carriage return got from text file
                if($call_multiple_instance)
                {
                    Functions::run_another_connector_instance($resource_id, 1); //call 1 other instance for a total of 2 instances running
                    $call_multiple_instance = 0;
                }
                self::get_all_taxa($task);
                print "\n Task $task is done. \n";
                Functions::delete_a_task("$task\n", self::$WORK_IN_PROGRESS_LIST); //remove a task from task list
            }
            else
            {
                print "\n\n [$task] Work list done --- " . date('Y-m-d h:i:s a', time()) . "\n";
                break;
            }
        }
        if(!$task = trim(Functions::get_a_task(self::$WORK_IN_PROGRESS_LIST))) //don't do this if there are task(s) in progress
        {
            // Combine all XML files.
            self::combine_all_xmls($resource_id);
            // Set to Harvest Requested
            if(filesize(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml")) $GLOBALS['db_connection']->update("UPDATE resources SET resource_status_id=" . ResourceStatus::insert('Harvest Requested') . " WHERE id=" . $resource_id);
            // Delete temp files
            self::delete_temp_files(self::$TEMP_FILE_PATH . "batch_", "txt");
            self::delete_temp_files(CONTENT_RESOURCE_LOCAL_PATH . "DiscoverLife/temp_DiscoverLife_" . "batch_", "xml");
        }
    }

    private function get_all_taxa($task)
    {
        require_library('CheckIfNameHasAnEOLPage');
        $func = new CheckIfNameHasAnEOLPage();
        $all_taxa = array();
        $used_collection_ids = array();
        //initialize text file for DiscoverLife: save names without a page in EOL
        self::initialize_text_file(self::$TEXT_FILE_FOR_DL);
        $filename = self::$TEMP_FILE_PATH . $task . ".txt";
        if(!($FILE = fopen($filename, "r")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . $filename);
          return;
        }
        $i = 0; 
        $save_count = 0; 
        $no_eol_page = 0;
        while(!feof($FILE))
        {
            if($line = fgets($FILE))
            {
                sleep(1);
                $name = trim($line);
                $i++;
                //Filter names. Process only those who already have a page in EOL. Report back to DiscoverLife names not found in EOL
                $arr = $func->check_if_name_has_EOL_page($name);
                $if_name_has_page_in_EOL = $arr[0];
                $xml_from_api            = $arr[1];
                if(!$if_name_has_page_in_EOL)
                {
                    print"\n - no EOL page ($name)"; 
                    $no_eol_page++;
                    self::store_name_to_text_file($name, $task);
                    continue;
                }
                $taxon = array();
                $taxon = $func->get_taxon_simple_stat($name, $xml_from_api);
                $taxon["map"] = 1;
                
                if(trim($name) == trim(Functions::canonical_form(trim($taxon['sciname'])))) $taxon["call_back"] = "taxon_concept_id";
                else $taxon["call_back"] = "scientific_name";

                print "\n $i -- " . $taxon['sciname'] . "\n";                
                $arr = self::get_discoverlife_taxa($taxon, $used_collection_ids);
                $page_taxa              = $arr[0];
                $used_collection_ids    = $arr[1];
                if($page_taxa) $all_taxa = array_merge($all_taxa, $page_taxa);
                unset($page_taxa);
            }
        }
        fclose($FILE);

        $xml = SchemaDocument::get_taxon_xml($all_taxa);
        $resource_path = CONTENT_RESOURCE_LOCAL_PATH . "DiscoverLife/temp_DiscoverLife_" . $task . ".xml";
        if(!($OUT = fopen($resource_path, "w")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$resource_path);
          return;
        } 
        fwrite($OUT, $xml); 
        fclose($OUT);

        $with_eol_page = $i - $no_eol_page;
        print "\n\n total = $i \n With EOL page = $with_eol_page \n No EOL page = $no_eol_page \n\n ";
    }

    function get_discoverlife_taxa($taxon, $used_collection_ids)
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
        $taxon = $taxon_rec["orig_sciname"];
        $arr_taxa = array();
        $source = self::DL_SEARCH_URL . str_replace(" ", "+", $taxon);
        $taxon_id = $source;
        $arr_objects = array();
        if(@$taxon_rec["keys"] || @$taxon_rec["map"])
        {
            if($taxon_rec["call_back"]=="taxon_concept_id") $call_back="&b=EOL/pages/" . $taxon_rec["tc_id"];
            else                                            $call_back="&b=EOL/search/" . $taxon_rec["orig_sciname"];
        }
        if(@$taxon_rec["map"])
        {
            /*
                <!--width up to 720, height up to 360, in 2:1 ratio-->
                <!--note &b=EOL/... adds deep back link to EOL pages; &b=EOL to EOL home-->
                <!--map either Genus+species (separated with a '+') or just Genus.  No higher groups yet-->
                <!--<img src="http://www.discoverlife.org/DB/sat/w00/lt_cb.jpg"> sent if no map points-->
                <!--next version: to deal with homonyms,  add &group=Highertaxon (e.g. Plantae, Fabaceae)-->
            */
            $description = "<a href='" . self::DL_MAP_URL . str_replace(" ", "+", $taxon) . $call_back . "'><img src='" . self::DL_MAP_SRC . str_replace(" ", "+", $taxon) . "' width='500' border='0'></a><br><a href='" . self::DL_SEARCH_URL . str_replace(" ","+",$taxon) . $call_back . "'>Discover Life</a> -- click on map for details, credits and terms of use.";
            $identifier = str_replace(" ","_",$taxon) . "_distribution";
            $mimeType   = "text/html";
            $dataType   = "http://purl.org/dc/dcmitype/Text";
            $title = "";
            $subject    = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution";
            $agent = array();
            $agent[] = array("role" => "compiler", "homepage" => "http://www.discoverlife.org/", "fullName" => "John Pickering");
            $mediaURL = ""; 
            $location = "";
            $license = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
            $rightsHolder = "Discover Life and original sources";
            $refs = array();
            $arr_objects[] = self::add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $agent, $license, $location, $rightsHolder, $refs, $subject);
        }

        if(sizeof($arr_objects))
        {
            $arr_taxa[]=array(  "identifier"    => $taxon_id,
                                "source"        => $source,
                                "sciname"       => $taxon,
                                "data_objects"  => $arr_objects
                             );
        }
        return $arr_taxa;
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
                      "subject"      => $subject
                    );
    }

    private function divide_text_file($divisor)
    {
        if(!($FILE = fopen(self::DL_MAP_SPECIES_LIST, "r")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . self::DL_MAP_SPECIES_LIST);
          return;
        }
        $i = 0;
        $file_ctr = 0;
        $str = "";
        print "\n";
        while(!feof($FILE))
        {
            if($line = fgets($FILE))
            {
                $i++;
                $str .= $line;
                print "$i. $line\n";
                if($i == $divisor)//no. of names per text file
                {
                    print"\n";
                    $file_ctr++;
                    $file_ctr_str = Functions::format_number_with_leading_zeros($file_ctr, 3);
                    if(!($OUT = fopen(self::$TEMP_FILE_PATH . "batch_" . $file_ctr_str . ".txt", "w")))
                    {
                      debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .self::$TEMP_FILE_PATH . "batch_" . $file_ctr_str . ".txt");
                      return;
                    }
                    fwrite($OUT, $str);
                    fclose($OUT);
                    $str = "";
                    $i = 0;
                }
            }
        }
        //last writes
        if($str)
        {
            $file_ctr++;
            $file_ctr_str = Functions::format_number_with_leading_zeros($file_ctr, 3);
            if(!($OUT = fopen(self::$TEMP_FILE_PATH . "batch_" . $file_ctr_str . ".txt", "w")))
            {
              debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .self::$TEMP_FILE_PATH . "batch_" . $file_ctr_str . ".txt");
              return;
            }
            fwrite($OUT, $str);
            fclose($OUT);
        }
        //create work_list
        $str = "";
        FOR($i = 1; $i <= $file_ctr; $i++) $str .= "batch_" . Functions::format_number_with_leading_zeros($i, 3) . "\n";
        $filename = self::$TEMP_FILE_PATH . "work_list.txt";
        if($fp = fopen($filename, "w"))
        {
            fwrite($fp, $str);
            fclose($fp);
        }
    }

    private function combine_all_xmls($resource_id)
    {
        debug("\n\n Start compiling all XML...");
        $old_resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id .".xml";
        if(!($OUT = fopen($old_resource_path, "w")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . $old_resource_path);
          return;
        }
        $str = "<?xml version='1.0' encoding='utf-8' ?>\n";
        $str .= "<response\n";
        $str .= "  xmlns='http://www.eol.org/transfer/content/0.3'\n";
        $str .= "  xmlns:xsd='http://www.w3.org/2001/XMLSchema'\n";
        $str .= "  xmlns:dc='http://purl.org/dc/elements/1.1/'\n";
        $str .= "  xmlns:dcterms='http://purl.org/dc/terms/'\n";
        $str .= "  xmlns:geo='http://www.w3.org/2003/01/geo/wgs84_pos#'\n";
        $str .= "  xmlns:dwc='http://rs.tdwg.org/dwc/dwcore/'\n";
        $str .= "  xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance'\n";
        $str .= "  xsi:schemaLocation='http://www.eol.org/transfer/content/0.3 http://services.eol.org/schema/content_0_3.xsd'>\n";
        fwrite($OUT, $str);
        $i = 0;
        while(true)
        {
            $i++;
            $i_str = Functions::format_number_with_leading_zeros($i, 3);
            $filename = CONTENT_RESOURCE_LOCAL_PATH . "DiscoverLife/temp_DiscoverLife_" . "batch_" . $i_str . ".xml";
            if(!is_file($filename))
            {
                print " -end compiling XML's- ";
                break;
            }
            print " $i ";
            if(!($READ = fopen($filename, "r")))
            {
              debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . $filename);
              return;
            }
            $contents = fread($READ, filesize($filename));
            fclose($READ);
            if($contents)
            {
                $pos1 = stripos($contents, "<taxon>");
                $pos2 = stripos($contents, "</response>");
                $str  = substr($contents, $pos1, $pos2 - $pos1);
                fwrite($OUT, $str);
            }
        }
        fwrite($OUT, "</response>");
        fclose($OUT);
        print "\n All XML compiled\n\n";
    }

    private function initialize_text_file($filename)
    {
        if(!($OUT = fopen($filename, "a")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . $filename);
          return;
        }
        fwrite($OUT, "===================" . "\n");
        fwrite($OUT, date("F j, Y, g:i:s a") . "\n");
        fclose($OUT);
    }

    private function store_name_to_text_file($name, $post_name)
    {
        /* This text file will be given to partner so they can fix their names */
        if($fp = fopen(self::$TEXT_FILE_FOR_DL, "a"))
        {
            fwrite($fp, $name. "\n");
            fclose($fp);
        }
    }

    private function delete_temp_files($file_path, $file_extension)
    {
        $i = 0;
        while(true)
        {
            $i++;
            $i_str = Functions::format_number_with_leading_zeros($i, 3);
            $filename = $file_path . $i_str . "." . $file_extension;
            if(file_exists($filename))
            {
                print "\n unlink: $filename";
                unlink($filename);
            }
            else return;
        }
    }
}
?>