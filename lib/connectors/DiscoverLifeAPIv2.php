<?php
namespace php_active_record;
/* connector: 223 
--- DiscoverLife map resource [223]. This is now scheduled as a cron task.
Partner provides a big text file of all their taxa with maps in DL.
But the partner wishes that EOL only gets those taxa where we already have existing EOL pages for.
They admit that their names are not yet totally cleaned and they don't want EOL to be creating new pages for their names.
The connector first checks our database if the DL taxon name has an EOL page. If yes, then connector will process it as a <dataObject> of subtype map.
This will end up in EOL's Maps tab.
If no, then this name will be added to a text file that will be reported back to DL for further investigation by the partner.
*/

class DiscoverLifeAPIv2
{
    const DL_MAP_SPECIES_LIST   = "http://www.discoverlife.org/export/species_map.txt";
    // const DL_MAP_SPECIES_LIST   = "http://localhost/cp/DiscoverLife/species_map.txt";
    // const DL_MAP_SPECIES_LIST   = "http://localhost/cp/DiscoverLife/species_map_small.txt";

    // this is temporary until DL fixes their list
    // const DL_MAP_SPECIES_LIST   = "http://opendata.eol.org/u/7597512/DiscoverLife/species_map 2012 06 26.txt";
    
    const DL_SEARCH_URL         = "http://www.discoverlife.org/mp/20q?search=";
    const DL_MAP_URL            = "http://www.discoverlife.org/20/m?kind=";
    const DL_MAP_SRC            = "http://www.discoverlife.org/mp/20m?map=";

    public function __construct() 
    {           
        $this->TEMP_FILE_PATH         = DOC_ROOT . "/update_resources/connectors/files/DiscoverLife/";
        $this->WORK_LIST              = DOC_ROOT . "/update_resources/connectors/files/DiscoverLife/work_list.txt";
        $this->WORK_IN_PROGRESS_LIST  = DOC_ROOT . "/update_resources/connectors/files/DiscoverLife/work_in_progress_list.txt";
        $this->INITIAL_PROCESS_STATUS = DOC_ROOT . "/update_resources/connectors/files/DiscoverLife/initial_process_status.txt";
        $this->TEXT_FILE_FOR_DL       = DOC_ROOT . "/update_resources/connectors/files/DiscoverLife/names_without_pages_in_eol.txt"; //report back to DiscoverLife
    }

    public function initialize_text_files()
    {
        if(($f = Functions::file_open($this->WORK_LIST, "w"))) fclose($f);
        if(($f = Functions::file_open($this->WORK_IN_PROGRESS_LIST, "w"))) fclose($f);
        if(($f = Functions::file_open($this->INITIAL_PROCESS_STATUS, "w"))) fclose($f);
        //this is not needed but just to have a clean directory
        Functions::delete_temp_files($this->TEMP_FILE_PATH . "batch_", "txt");
        Functions::delete_temp_files($this->TEMP_FILE_PATH . "temp_DiscoverLife_batch_", "xml");
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
                self::divide_text_file(10000); //orig value 10000 debug
                Functions::delete_a_task("Initial process start", $this->INITIAL_PROCESS_STATUS);
            }
        }
        Functions::process_work_list($this);
        if(!$task = trim(Functions::get_a_task($this->WORK_IN_PROGRESS_LIST))) //don't do this if there are task(s) in progress
        {
            // Combine all XML files.
            Functions::combine_all_eol_resource_xmls($resource_id, $this->TEMP_FILE_PATH . "temp_DiscoverLife_batch_*.xml");
            // Set to Harvest Requested
            if(filesize(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml")) $GLOBALS['db_connection']->update("UPDATE resources SET resource_status_id=" . ResourceStatus::harvest_requested()->id . " WHERE id=" . $resource_id);
            // Delete temp files
            Functions::delete_temp_files($this->TEMP_FILE_PATH . "batch_", "txt");
            Functions::delete_temp_files($this->TEMP_FILE_PATH . "temp_DiscoverLife_" . "batch_", "xml");
        }
    }

    function get_all_taxa($task)
    {
        $all_taxa = array();
        $used_collection_ids = array();
        //initialize text file for DiscoverLife: save names without a page in EOL
        self::initialize_text_file($this->TEXT_FILE_FOR_DL);
        $filename = $this->TEMP_FILE_PATH . $task . ".txt";
        $i = 0; 
        $save_count = 0; 
        $no_eol_page = 0;
        foreach(new FileIterator($filename) as $line_number => $line)
        {
            if($line)
            {
                $name = trim($line);
                $i++;
                //Filter names. Process only those who already have a page in EOL. Report back to DiscoverLife names not found in EOL
                if(!$taxon = self::with_eol_page($name))
                {
                    print"\n $i -- no EOL page ($name)"; 
                    $no_eol_page++;
                    self::store_name_to_text_file($name, $task);
                    continue;
                }

                print "\n $i -- " . $taxon['orig_sciname'] . "\n";         
                $arr = self::get_discoverlife_taxa($taxon, $used_collection_ids);
                $page_taxa              = $arr[0];
                $used_collection_ids    = $arr[1];
                if($page_taxa) $all_taxa = array_merge($all_taxa, $page_taxa);
                unset($page_taxa);
            }
        }

        $xml = \SchemaDocument::get_taxon_xml($all_taxa);
        $xml = str_replace("</dataObject>", "<additionalInformation><subtype>map</subtype></additionalInformation></dataObject>", $xml);
        $resource_path = $this->TEMP_FILE_PATH . "temp_DiscoverLife_" . $task . ".xml";
        if(!($OUT = Functions::file_open($resource_path, "w"))) return;
        fwrite($OUT, $xml); 
        fclose($OUT);

        $with_eol_page = $i - $no_eol_page;
        print "\n\n total = $i \n With EOL page = $with_eol_page \n No EOL page = $no_eol_page \n\n ";
    }

    function with_eol_page($name)
    {
        /* orig - working OK I suppose... but uses the database. Will use the API below instead.
        $taxon = array();
        $sql = "SELECT DISTINCT(tcn.taxon_concept_id) FROM canonical_forms cf 
        JOIN names n ON (cf.id=n.canonical_form_id) 
        JOIN taxon_concept_names tcn ON (n.id=tcn.name_id) 
        JOIN taxon_concepts tc ON (tcn.taxon_concept_id=tc.id) 
        LEFT JOIN taxon_concept_metrics tcm ON (tc.id=tcm.taxon_concept_id)
        WHERE cf.string='$name' AND tc.published=1 ORDER BY tcm.richness_score DESC";
        $result = $GLOBALS['db_connection']->select($sql);
        if($result && $row=$result->fetch_assoc()) {
            $taxon_concept_id = $row['taxon_concept_id'];
            $taxon = array( 'orig_sciname' => $name,
                            'tc_id' => $taxon_concept_id,
                            'map' => 1,
                            'call_back' => 'taxon_concept_id'
                );
        } 
        return $taxon;
        */

        $taxon = array();
        $this->eol_api["search"] = "http://eol.org/api/search/1.0.json?page=1&exact=true&filter_by_taxon_concept_id=&filter_by_hierarchy_entry_id=&filter_by_string=&cache_ttl=&q=";
        $this->download_options = array('resource_id' => "eol_api", 'expire_seconds' => false, 'download_wait_time' => 500000, 'timeout' => 10800, 'download_attempts' => 1);
        if($json = Functions::lookup_with_cache($this->eol_api["search"].$name, $this->download_options)) {
            $arr = json_decode($json, true);
            if($arr['totalResults'] >= 1) {
                $taxon = array( 'orig_sciname'  => $name,
                                'tc_id'         => $arr['results'][0]['id'],
                                'map'           => 1,
                                'call_back'     => 'taxon_concept_id');
            }
        }
        return $taxon;
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
            $description = "<br>Please see details, credits, terms of use and the latest version of the map at <a href='" . self::DL_MAP_URL . str_replace(" ", "+", $taxon) . $call_back . "'>Discover Life</a>.";
            $description .= "<br>Explore <a href='" . self::DL_SEARCH_URL . str_replace(" ", "+", $taxon) . $call_back . "'><i>$taxon</i></a> in Discover Life.";

            $identifier = str_replace(" ", "_", $taxon) . "_distribution";
            $mimeType   = "image/jpeg";
            $dataType   = "http://purl.org/dc/dcmitype/StillImage";
            $title = "Discover Life: Point Map of $taxon";
            $subject = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution";
            $agent = array();
            $agent[] = array("role" => "compiler", "homepage" => "http://www.discoverlife.org/", "fullName" => "John Pickering");
            $mediaURL = self::DL_MAP_SRC . str_replace(" ", "+", $taxon); 
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
        $temp_filepath = Functions::save_remote_file_to_local(self::DL_MAP_SPECIES_LIST, array('timeout' => 4800, 'download_attempts' => 5));
        if(!$temp_filepath)
        {
            echo "\n\nExternal file not available. Program will terminate.\n";
            return;
        }
        $i = 0;
        $file_ctr = 0;
        $str = "";
        print "\n";
        foreach(new FileIterator($temp_filepath, true) as $line_number => $line) // 'true' will auto delete temp_filepath
        {
            if($line)
            {
                $line .= "\n"; // FileIterator removes the carriage-return char
                $i++;
                $str .= $line;
                print "$i. $line\n";
                if($i == $divisor)//no. of names per text file
                {
                    print"\n";
                    $file_ctr++;
                    $file_ctr_str = Functions::format_number_with_leading_zeros($file_ctr, 3);
                    if(!($OUT = Functions::file_open($this->TEMP_FILE_PATH . "batch_" . $file_ctr_str . ".txt", "w"))) return;
                    fwrite($OUT, $str);
                    fclose($OUT);
                    $str = "";
                    $i = 0;
                }
            }
            //if($i >= 5) break; //debug
        }
        //last writes
        if($str)
        {
            $file_ctr++;
            $file_ctr_str = Functions::format_number_with_leading_zeros($file_ctr, 3);
            if(!($OUT = Functions::file_open($this->TEMP_FILE_PATH . "batch_" . $file_ctr_str . ".txt", "w"))) return;
            fwrite($OUT, $str);
            fclose($OUT);
        }
        //create work_list
        $str = "";
        FOR($i = 1; $i <= $file_ctr; $i++) $str .= "batch_" . Functions::format_number_with_leading_zeros($i, 3) . "\n";
        $filename = $this->TEMP_FILE_PATH . "work_list.txt";
        if($fp = Functions::file_open($filename, "w"))
        {
            fwrite($fp, $str);
            fclose($fp);
        }
    }

    private function initialize_text_file($filename)
    {
        if(!($OUT = Functions::file_open($filename, "w"))) return;
        fwrite($OUT, "===================" . "\n");
        fwrite($OUT, date("F j, Y, g:i:s a") . "\n");
        fclose($OUT);
    }

    private function store_name_to_text_file($name, $post_name)
    {
        /* This text file will be given to partner so they can fix their names */
        if($fp = Functions::file_open($this->TEXT_FILE_FOR_DL, "a"))
        {
            fwrite($fp, $name. "\n");
            fclose($fp);
        }
    }

}
?>