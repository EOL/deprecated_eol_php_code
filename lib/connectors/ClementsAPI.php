<?php
namespace php_active_record;
// connector: [527]
class ClementsAPI
{
    function __construct($folder)
    {
        $this->page_to_download_the_spreadsheet = "http://www.birds.cornell.edu/clementschecklist/downloadable-clements-checklist";
        $this->taxa = array();
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->resource_reference_ids = array();
        $this->resource_agent_ids = array();
        $this->vernacular_name_ids = array();
        $this->taxon_ids = array();
        $this->SPM = 'http://rs.tdwg.org/ontology/voc/SPMInfoItems';
        $this->single_reference_for_all = "Clements, J. F., T. S. Schulenberg, M. J. Iliff, B.L. Sullivan, C. L. Wood, and D. Roberson. 2012. The eBird/Clements checklist of birds of the world: Version 6.7. Downloaded from http://www.birds.cornell.edu/clementschecklist/downloadable-clements-checklist";
    }

    function get_all_taxa($data_dump_url = false)
    {
        if(!$data_dump_url) $this->data_dump_url = self::get_dump_url();
        else $this->data_dump_url = $data_dump_url;

        $records = self::parse_xls();
        foreach($records as $record)
        {
            debug("\n" . $record["SCIENTIFIC NAME"]);
            if($record["CATEGORY"] == "group (monotypic)") $record["CATEGORY"] = "subspecies";
            if(in_array($record["CATEGORY"], array("species", "subspecies"))) self::parse_record_element($record);
            else debug(" - not valid category");
        }
        $this->create_archive();

        // remove tmp file
        unlink($this->data_dump_url);
        debug("\n temporary file removed: [$this->data_dump_url]");
    }

    private function parse_xls()
    {
        if($this->data_dump_url = Functions::save_remote_file_to_local($this->data_dump_url, 1000000, 600, 5, "xls"))
        {
            require_library('XLSParser');
            $parser = new XLSParser();
            debug("\n reading: " . $this->data_dump_url . "\n");
            $temp = $parser->convert_sheet_to_array($this->data_dump_url);
            $records = $parser->prepare_data($temp, "single", "SCIENTIFIC NAME", "SCIENTIFIC NAME", "CATEGORY", "ENGLISH NAME", "RANGE", "ORDER", "FAMILY", "EXTINCT", "EXTINCT_YEAR");
            return $records;
        }
    }

    private function parse_record_element($rec)
    {
        $reference_ids = array();
        $ref_ids = self::get_object_reference_ids();
        $agent_ids = array();
        $rec = $this->create_instances_from_taxon_object($rec, $reference_ids);
        if($distribution = self::get_distribution($rec)) self::get_texts($distribution, $rec, 'Range', '#Distribution', 'distribution', $ref_ids, $agent_ids);
        if($extinction = self::get_extinction($rec)) self::get_texts($extinction, $rec, '', '#Ecology', 'extinction', $ref_ids, $agent_ids);
    }

    private function get_object_reference_ids()
    {
        $reference_ids = array();
        $r = new \eol_schema\Reference();
        $r->full_reference = (string) $this->single_reference_for_all;
        $r->identifier = md5($r->full_reference);
        $reference_ids[] = $r->identifier;
        if(!in_array($r->identifier, $this->resource_reference_ids)) 
        {
           $this->resource_reference_ids[] = $r->identifier;
           $this->archive_builder->write_object_to_file($r);
        }
        return $reference_ids;
    }

    private function get_dump_url()
    {
        /* partner provides to download their dump file (.xls) from their site */
        $path_parts = pathinfo($this->page_to_download_the_spreadsheet);
        if($html = Functions::get_remote_file($this->page_to_download_the_spreadsheet, 1000000, 120, 5))
        {
            if(preg_match("/class\=\"internal-link\" href\=\"(.*?)\.xls\"><strong>Download the checklist/ims", $html, $arr))
            {
                return $path_parts['dirname'] . "/" . urldecode($arr[1] . ".xls");
            }
        }
        return false;
    }

    private function get_distribution($rec)
    {
        if($rec["RANGE"] != '') return $rec["RANGE"] . ".";
    }

    private function get_extinction($rec)
    {
        if($rec["EXTINCT_YEAR"] == 'xxxx') return "Date of extinction unknown.";
        elseif(is_numeric($rec["EXTINCT_YEAR"])) return "Year last seen in the wild: " . $rec["EXTINCT_YEAR"] . ".";
    }

    private function get_texts($description, $rec, $title, $subject, $code, $reference_ids = null, $agent_ids = null)
    {
            $taxon_id = $rec["taxonID"];
            $mr = new \eol_schema\MediaResource();
            if($reference_ids) $mr->referenceID = implode("; ", $reference_ids);
            if($agent_ids) $mr->agentID = implode("; ", $agent_ids);
            $mr->taxonID = $taxon_id;
            $mr->identifier = $mr->taxonID . "_" . $code;
            $mr->type = 'http://purl.org/dc/dcmitype/Text';
            $mr->language = 'en';
            $mr->format = 'text/html';
            $mr->furtherInformationURL = '';
            $mr->description = $description;
            $mr->CVterm = $this->SPM . $subject;
            $mr->title = $title;
            $mr->creator = '';
            $mr->CreateDate = '';
            $mr->modified = '';
            $mr->UsageTerms = 'http://creativecommons.org/licenses/by-nc/3.0/';
            $mr->Owner = '';
            $mr->publisher = '';
            $mr->audience = 'Everyone';
            $mr->bibliographicCitation = '';
            $this->archive_builder->write_object_to_file($mr);
    }

    function create_instances_from_taxon_object($rec, $reference_ids)
    {
        $taxon = new \eol_schema\Taxon();
        $taxon_id = md5($rec["SCIENTIFIC NAME"]."clements");
        $rec["taxonID"] = $taxon_id;

        if($reference_ids) $taxon->referenceID = implode("; ", $reference_ids);
        $taxon->taxonID = $taxon_id;
        $rank = trim($rec["CATEGORY"]);
        if($rank == "species")
        {
            $temp = explode(" ", trim($rec["SCIENTIFIC NAME"]));
            $genus = trim($temp[0]);
        }
        else $genus = "";

        $taxon->taxonRank                   = (string) $rank;
        $taxon->scientificName              = (string) $rec["SCIENTIFIC NAME"];
        $taxon->scientificNameAuthorship    = "";
        $taxon->vernacularName              = $rec["ENGLISH NAME"];
        $taxon->order                       = $rec["ORDER"];
        $taxon->family                      = (string) self::remove_parenthesis($rec["FAMILY"]);
        debug("\n family: " . $taxon->family);
        $taxon->genus                       = $genus;
        $taxon->specificEpithet             = '';
        $taxon->taxonomicStatus             = '';
        $taxon->nomenclaturalCode           = '';
        $taxon->nomenclaturalStatus         = '';
        $taxon->acceptedNameUsage           = '';
        $taxon->acceptedNameUsageID         = '';
        $taxon->parentNameUsageID           = '';
        $taxon->namePublishedIn             = '';
        $taxon->taxonRemarks                = '';
        $taxon->infraspecificEpithet        = '';
        $this->taxa[$taxon_id] = $taxon;
        return $rec;
    }

    function create_archive()
    {
        foreach($this->taxa as $t)
        {
            $this->archive_builder->write_object_to_file($t);
        }
        $this->archive_builder->finalize();
    }

    private function remove_parenthesis($string)
    {
        $temp = explode("(", $string);
        return trim($temp[0]);
    }

}
?>