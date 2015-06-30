<?php
namespace php_active_record;
// connector: [527]
class ClementsAPIv2
{
    function __construct($folder)
    {
        $this->page_to_download_the_spreadsheet = "http://www.birds.cornell.edu/clementschecklist/download/";
        $this->taxa = array();
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->resource_reference_ids = array();
        $this->resource_agent_ids = array();
        $this->vernacular_name_ids = array();
        $this->taxon_ids = array();
        $this->SPM = 'http://rs.tdwg.org/ontology/voc/SPMInfoItems';
        $this->single_reference_for_all = false;
        $this->levels = array("kingdom" => 1, "phylum" => 2, "class" => 3, "order" => 4, "family" => 5, "genus" => 6, "species" => 7, "subspecies" => 8);
    }

    function get_all_taxa($data_dump_url = false)
    {
        $this->data_dump_url = self::get_dump_url();
        // $this->data_dump_url = "http://localhost/~eolit/cp/Clements/Clements-Checklists-6.9-2014-fixed small.xlsx"; //debug
        // $this->data_dump_url = "http://localhost/~eolit/cp/Clements/Clements-Checklists-6.9-2014-fixed.xlsx"; //debug

        $records = self::parse_xls();
        $i = 0;
        foreach($records as $record)
        {
            $i++;
            if(($i % 5000) == 0) echo "\n$i";
            if($record["Category"] == "group (monotypic)") $record["Category"] = "subspecies";
            if($record["Category"] != "group (polytypic)") self::parse_record_element($record, $records);
            // else debug(" - not valid category - " . $record["Category"] . "\n");
        }
        $this->create_archive();
        // remove tmp file
        unlink($this->data_dump_url);
        debug("\n temporary file removed: [$this->data_dump_url]");
    }

    private function parse_xls()
    {
        if($this->data_dump_url = Functions::save_remote_file_to_local($this->data_dump_url, array('cache' => 1, 'download_wait_time' => 1000000, 'timeout' => 600, 'download_attempts' => 5, 'file_extension' => 'xlsx')))
        {
            require_library('XLSParser');
            $parser = new XLSParser();
            debug("\n reading: " . $this->data_dump_url . "\n");
            $temp = $parser->convert_sheet_to_array($this->data_dump_url, 0);
            $records = $parser->prepare_data($temp, "single", "Scientific name", "Scientific name", "Category", "English name", "Range", "Order", "Family", "Extinct", "Extinction Year");
            $records = self::add_uppercase_fields($records);
            $records = self::fill_in_missing_names($records);
            $records = self::fill_in_parent_id($records);
            debug("\n" . count($records));
            return $records;
        }
    }

    private function add_uppercase_fields($records)
    {
        foreach($records as $key => $value)
        {
            $records[$key]["FAMILY"] = $value["Family"];
            $records[$key]["ORDER"] = $value["Order"];
        }
        return $records;
    }

    private function parse_record_element($rec, $records)
    {
        $reference_ids = array();
        $ref_ids = self::get_object_reference_ids();
        $agent_ids = array();
        $rec = $this->create_instances_from_taxon_object($rec, $reference_ids, $records);
        if($distribution = self::get_distribution($rec)) self::get_texts($distribution, $rec, 'Range', '#Distribution', 'distribution', $ref_ids, $agent_ids);
        if($extinction = self::get_extinction($rec, $ref_ids)) self::get_texts($extinction, $rec, '', '#ConservationStatus', 'extinction', $ref_ids, $agent_ids);
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
        if($html = Functions::lookup_with_cache($this->page_to_download_the_spreadsheet, array('download_wait_time' => 1000000, 'timeout' => 120, 'download_attempts' => 5)))
        {
            $this->single_reference_for_all = self::get_reference($html);
            /* use the spreadsheet */
            if(preg_match("/<li><strong>Clements Checklists(.*?)Excel<\/a>/ims", $html, $arr))
            {
                if(preg_match("/\"(.*?)\"/ims", $arr[1], $arr)) return $arr[1];
            }
            /* use this if we want to get the CSV file instead
            if(preg_match("/Excel<\/a> spreadsheet or(.*?)\">CSV file<\/a>/ims", $html, $arr))
            {
                if(preg_match("/\"(.*?)xxx/ims", $arr[1]."xxx", $arr)) return $arr[1];
            }
            */
        }
        return false;
    }
    
    private function get_reference($html)
    {
        if(preg_match("/<div class=\"page-content\">(.*?)<\/div>/ims", $html, $arr))
        {
            if(preg_match_all("/<p>(.*?)<\/p>/ims", $arr[1], $arr)) return strip_tags($arr[1][1]); // 2nd paragraph
        }
    }

    private function get_distribution($rec)
    {
        if($val = @$rec["Range"]) return $val . ".";
    }

    private function get_extinction($rec, $ref_ids)
    {
        if(!in_array($rec['Category'], array("species", "subspecies"))) return;

        /* move to structured data
        if(@$rec["Extinction Year"] == 'xxxx') return "Date of extinction unknown.";
        else return false;
        // elseif(is_numeric($rec["Extinction Year"])) return "Year last seen in the wild: " . $rec["Extinction Year"] . "."; // to be moved to the structured data resource
        */
        
        // for structured data
        $rek = array();
        $rek["taxon_id"] = $rec["ID"];

        if(@$rec["Extinct"] == "1") $extinction_status = "http://eol.org/schema/terms/extinct";
        else                        $extinction_status = "http://eol.org/schema/terms/extant";
        $rek["catnum"] = "es"; //extinction status
        self::add_string_types($rek, $extinction_status, "http://eol.org/schema/terms/ExtinctionStatus", "true", $ref_ids);

        /* $rek["catnum"] = "yr"; -- comment this so year can become metadata for 'extinction_status' */
        if(is_numeric($rec["Extinction Year"]) && $rec["Extinction Year"] > 0)
        {
            self::add_string_types($rek, $rec["Extinction Year"], "http://eol.org/schema/terms/TimeOfExtinction", "false");
        }
        elseif($rec["Extinction Year"] == "xxxx")
        {
            self::add_string_types($rek, "Date of extinction unknown.", "http://eol.org/schema/terms/TimeOfExtinction", "false");
        }
    }

    private function add_string_types($rec, $value, $mtype, $measurementOfTaxon, $reference_ids = null)
    {
        $taxon_id = $rec["taxon_id"];
        $catnum = $rec["catnum"];
        $m = new \eol_schema\MeasurementOrFact();
        $occurrence = $this->add_occurrence($taxon_id, $catnum);
        $m->occurrenceID = $occurrence->occurrenceID;
        $m->measurementType = $mtype;
        $m->measurementValue = $value;
        $m->measurementOfTaxon = $measurementOfTaxon;
        if($measurementOfTaxon == "true")
        {
            $m->contributor = 'The Cornell Lab of Ornithology: Clements Checklist'; 
            if($reference_ids) $m->referenceID = implode("; ", $reference_ids);
        }
        // $m->measurementRemarks = ''; // $m->measurementMethod = ''; $m->source = ''; --- no entry for these fields
        $this->archive_builder->write_object_to_file($m);
    }

    private function add_occurrence($taxon_id, $catnum)
    {
        $occurrence_id = $taxon_id . '_' . $catnum;
        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = $occurrence_id;
        $o->taxonID = $taxon_id;
		if(!isset($this->occurrence_ids[$occurrence_id]))
		{
			$this->occurrence_ids[$occurrence_id] = '';
	        $this->archive_builder->write_object_to_file($o);
		}
        return $o;
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
            $mr->description = $description;
            $mr->CVterm = $this->SPM . $subject;
            $mr->title = $title;
            $mr->UsageTerms = 'http://creativecommons.org/licenses/by-nc/3.0/';
            // $mr->audience = 'Everyone';
            // $mr->furtherInformationURL = '';
            // $mr->creator = '';
            // $mr->CreateDate = '';
            // $mr->modified = '';
            // $mr->Owner = '';
            // $mr->publisher = '';
            // $mr->bibliographicCitation = '';
            $this->archive_builder->write_object_to_file($mr);
    }

    function create_instances_from_taxon_object($rec, $reference_ids, $records)
    {
        $taxon = new \eol_schema\Taxon();
        $taxon_id = $rec["ID"];
        $rec["taxonID"] = $taxon_id;

        if($reference_ids) $taxon->referenceID = implode("; ", $reference_ids);
        $taxon->taxonID = $taxon_id;
        $rank = trim($rec["Category"]);
        
        $taxon->taxonRank                   = (string) $rank;
        $taxon->scientificName              = (string) $rec["Scientific name"];
        $taxon->scientificNameAuthorship    = "";
        $taxon->vernacularName              = @$rec["English name"];
        $taxon->parentNameUsageID           = $rec["Category"] != "kingdom" ? $rec["parent_id"] : "";
        $this->taxa[$taxon_id] = $taxon;
        return $rec;
    }

    function create_archive()
    {
        foreach($this->taxa as $t) $this->archive_builder->write_object_to_file($t);
        $this->archive_builder->finalize(true);
    }

    private function remove_parenthesis($string)
    {
        $temp = explode("(", $string);
        return trim($temp[0]);
    }

    private function fill_in_missing_names($records)
    {
        $others["Animalia"] = array("ID" => "animalia", "Scientific name" => "Animalia", "Category" => "kingdom");
        $others["Chordata"] = array("ID" => "chordata", "Scientific name" => "Chordata", "Category" => "phylum", "KINGDOM" => "Animalia");
        $others["Aves"]     = array("ID" => "aves",     "Scientific name" => "Aves",     "Category" => "class", "PHYLUM" => "Chordata");
        foreach($records as $key => $rec)
        {
            $order = self::remove_parenthesis($rec["ORDER"]);
            $family = self::remove_parenthesis($rec["FAMILY"]);

            $records[$key]["ORDER"] = $order;
            $records[$key]["FAMILY"] = $family;
            $records[$key]["ID"] = strtolower(str_ireplace(" ", "_", $rec["Scientific name"]));
            
            if(!isset($others[$order]))  $others[$order]  = array("ID" => strtolower(str_ireplace(" ", "_", $order)), "Scientific name" => $order, "Category" => "order", "CLASS" => "Aves");
            if(!isset($others[$family])) $others[$family] = array("ID" => strtolower(str_ireplace(" ", "_", $family)), "Scientific name" => $family, "Category" => "family", "ORDER" => $order);
            
            $sciname = trim($rec["Scientific name"]);
            if(is_numeric(stripos($sciname, " ")))
            {
                $parts = explode(" ", $sciname);
                $genus = $parts[0];
                if(!isset($others[$genus])) $others[$genus] = array("ID" => strtolower(str_ireplace(" ", "_", $genus)), "Scientific name" => $genus, "Category" => "genus", "FAMILY" => $family);
                $records[$key]["GENUS"] = $genus;
            }
            
            if($rec["Category"] == "group (monotypic)") 
            {
                $records[$key]["Category"] = "subspecies";
                $records[$key]["SPECIES"] = self::get_species($sciname);
            }

            if($rec["Category"] == "subspecies") 
            {
                $records[$key]["SPECIES"] = self::get_species($sciname);
            }
        }
        $records = array_merge($others, $records);
        return $records;
    }

    private function fill_in_parent_id($records)
    {
        foreach($records as $taxon => $rec)
        {
            if($rec["Category"] == "group (polytypic)") continue;
            $parent_name = "";
            $num = $this->levels[$rec["Category"]] - 1;
            foreach($this->levels as $key => $value)
            {
                if($num == $value)
                {
                    $parent_name = $rec[strtoupper($key)];
                    break;
                }
            }
            if($parent_name) $records[$taxon]["parent_id"] = $records[$parent_name]["ID"];
        }
        return $records;
    }
    
    private function get_species($sciname)
    {
        $parts = explode(" ", $sciname);
        return $parts[0] . " " . $parts[1];
    }

}
?>