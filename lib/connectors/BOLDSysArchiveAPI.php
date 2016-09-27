<?php
namespace php_active_record;
/* connector: 212 */

class BOLDSysArchiveAPI
{
    public function __construct($folder)
    {
        $this->TEMP_FILE_PATH         = DOC_ROOT . "/update_resources/connectors/files/BOLD/";
        $this->WORK_LIST              = $this->TEMP_FILE_PATH . "sl_work_list.txt"; //sl - species-level taxa
        $this->WORK_IN_PROGRESS_LIST  = $this->TEMP_FILE_PATH . "sl_work_in_progress_list.txt";
        $this->INITIAL_PROCESS_STATUS = $this->TEMP_FILE_PATH . "sl_initial_process_status.txt";
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->taxa = array();
        $this->resource_agent_ids = array();
        $this->do_ids = array();
    }

    function start_process($resource_id, $call_multiple_instance, $connectors_to_run = 1)
    {
        require_library('connectors/BOLDSysAPI');
        $this->func = new BOLDSysAPI();

        $this->resource_id = $resource_id;
        $this->call_multiple_instance = $call_multiple_instance;
        $this->connectors_to_run = $connectors_to_run;
        if(!trim(Functions::get_a_task($this->WORK_IN_PROGRESS_LIST)))//don't do this if there are harvesting task(s) in progress
        {
            if(!trim(Functions::get_a_task($this->INITIAL_PROCESS_STATUS)))//don't do this if initial process is still running
            {
                // Divide the big list of ids into small files
                Functions::add_a_task("Initial process start", $this->INITIAL_PROCESS_STATUS);
                $this->func->create_master_list();
                Functions::delete_a_task("Initial process start", $this->INITIAL_PROCESS_STATUS);
            }
        }
        Functions::process_work_list($this);
        if(!$task = trim(Functions::get_a_task($this->WORK_IN_PROGRESS_LIST))) //don't do this if there are task(s) in progress
        {
            $this->archive_builder->finalize(true);
            // Set to Harvest Requested
            Functions::set_resource_status_to_harvest_requested($resource_id);
            // Delete temp files
            Functions::delete_temp_files($this->TEMP_FILE_PATH . "sl_batch_", "txt");
        }
    }

    function get_all_taxa($task, $temp_file_path)
    {
        $filename = $temp_file_path . $task . ".txt";
        $records = $this->func->get_array_from_json_file($filename);
        $num_rows = sizeof($records); $i = 0;
        foreach($records as $rec)
        {
            $i++; echo "\n [$i of $num_rows] ";
            echo $rec['taxonomy']['species']['taxon']['name'];
            // if(trim($rec['taxonomy']['species']['taxon']['name']) != "Lumbricus centralis") continue; //debug
            $response = $this->func->parse_xml($rec);
            self::create_instances_from_taxon_object($response);
        }
    }

    private function create_instances_from_taxon_object($rec, $reference_ids = null)
    {
        foreach($rec as $r)
        {
            // process taxon entry
            $taxon = new \eol_schema\Taxon();
            $taxon->taxonID                 = $r["identifier"];
            $taxon->scientificName          = $r["sciname"];
            $taxon->kingdom                 = $r["kingdom"];
            $taxon->phylum                  = $r["phylum"];
            $taxon->class                   = $r["class"];
            $taxon->order                   = $r["order"];
            $taxon->family                  = $r["family"];
            $taxon->genus                   = $r["genus"];
            $taxon->furtherInformationURL   = $r["source"];
            if(!isset($this->taxa[$taxon->taxonID]))
            {
                $this->taxa[$taxon->taxonID] = '';
                $this->archive_builder->write_object_to_file($taxon);
            }
            // process object entry
            foreach($r["data_objects"] as $do)
            {
                $mr = new \eol_schema\MediaResource();
                if($agent_ids = self::get_object_agents($do["agent"])) $mr->agentID = implode("; ", $agent_ids);
                $mr->taxonID                = $r["identifier"];
                $mr->identifier             = $do["identifier"];
                $mr->type                   = $do["dataType"];
                $mr->format                 = $do["mimeType"];
                $mr->furtherInformationURL  = $do["source"];
                $mr->description            = $do["description"];
                $mr->title                  = $do["title"];
                $mr->UsageTerms             = $do["license"];
                $mr->Owner                  = $do["rightsHolder"];
                $mr->CVterm                 = $do["subject"];
                $mr->accessURI              = $do["mediaURL"];
                if($mr->type == "http://purl.org/dc/dcmitype/StillImage") $mr->subtype = "map";
                if(!isset($this->do_ids[$do["identifier"]]))
                {
                    $this->do_ids[$do["identifier"]] = '';
                    $this->archive_builder->write_object_to_file($mr);
                }
            }
        }
    }

    private function get_object_agents($records)
    {
        $agent_ids = array();
        foreach($records as $rec)
        {
            $r = new \eol_schema\Agent();
            $r->term_name       = $rec["fullName"];
            $r->identifier      = md5($rec["fullName"].$rec["role"]);
            $r->agentRole       = $rec["role"];
            $r->term_homepage   = $rec["homepage"];
            $agent_ids[] = $r->identifier;
            if(!isset($this->resource_agent_ids[$r->identifier]))
            {
               $this->resource_agent_ids[$r->identifier] = '';
               $this->archive_builder->write_object_to_file($r);
            }
        }
        return $agent_ids;
    }

}
?>
