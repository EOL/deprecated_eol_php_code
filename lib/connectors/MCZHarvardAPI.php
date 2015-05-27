<?php
namespace php_active_record;
// connector: [201]
class MCZHarvardAPI
{
    function __construct($folder)
    {
        $this->data_dump_url = "http://digir.mcz.harvard.edu/forEOL/MCZimages.tsv";
        // $this->data_dump_url = "http://127.0.0.1/eol_php_code/update_resources/connectors/files/MCZ_Harvard/MCZimages_small.tsv";
        // $this->data_dump_url = "http://127.0.0.1/eol_php_code/update_resources/connectors/files/MCZ_Harvard/MCZimages.tsv";

        $this->taxa = array();
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->resource_reference_ids = array();
        $this->resource_agent_ids = array();
        $this->vernacular_name_ids = array();
        $this->taxon_ids = array();
        $this->do_ids = array();
    }

    function get_all_taxa($data_dump_url = false)
    {
        $labels = self::get_headers();
        if($data_dump_url) $this->data_dump_url = $data_dump_url;
        if($temp_filepath = Functions::save_remote_file_to_local($this->data_dump_url, array('timeout' => 4800, 'download_attempts' => 5)))
        {
            $not80 = 0;
            $i = 0;
            foreach(new FileIterator($temp_filepath, true) as $line_number => $line) // 'true' will auto delete temp_filepath
            {
                if($line)
                {
                    $record = self::prepare_row_data(trim($line), $labels);
                    if(count($record) != 80)
                    {
                       $not80++; // means invalid CSV row, needs attention by provider
                       echo "\n investigate: invalid CSV row, needs attention by provider [" . count($record) . "]";
                       print_r($record);
                    }
                    else
                    {
                        if(@$record['SCIENTIFIC_NAME'])
                        {
                            $i++;
                            debug("$i. " . $record['SCIENTIFIC_NAME'] . " [" . count($record) . "]\n");
                            self::parse_record_element($record);
                        }
                    }
                }
            }
            debug("\n not 80: $not80 \n");
            $this->create_archive();
        }
    }

    private function get_headers()
    {
        $labels = "MEDIA_ID, MEDIA_URI, MIME_TYPE, subject, created, CAT_NUM, INSTITUTION_ACRONYM, COLLECTION_CDE, COLLECTION, MINIMUM_ELEVATION, MAXIMUM_ELEVATION, ORIG_ELEV_UNITS, LAST_EDIT_DATE, INDIVIDUALCOUNT, COLL_OBJ_DISPOSITION, COLLECTORS, TYPESTATUS, SEX, PARTS, VERBATIM_DATE, HIGHER_GEOG, CONTINENT_OCEAN, COUNTRY, STATE_PROV, COUNTY, FEATURE, ISLAND, ISLAND_GROUP, QUAD, SEA, SPEC_LOCALITY, MIN_ELEV_IN_M, MAX_ELEV_IN_M, DEC_LAT, DEC_LONG, DATUM, ORIG_LAT_LONG_UNITS, VERBATIMLATITUDE, VERBATIMLONGITUDE, LAT_LONG_REF_SOURCE, COORDINATEUNCERTAINTYINMETERS, GEOREFMETHOD, LAT_LONG_REMARKS, LAT_LONG_DETERMINER, SCIENTIFIC_NAME, IDENTIFIEDBY, MADE_DATE, REMARKS, HABITAT, FULL_TAXON_NAME, PHYLCLASS, KINGDOM, PHYLUM, PHYLORDER, FAMILY, GENUS, SPECIES, SUBSPECIES, INFRASPECIFIC_RANK, AUTHOR_TEXT, IDENTIFICATIONMODIFIER, NOMENCLATURAL_CODE, GUID, BASISOFRECORD, DEPTH_UNITS, MIN_DEPTH, MAX_DEPTH, COLLECTING_METHOD, COLLECTING_SOURCE, DAYOFYEAR, AGE_CLASS, ATTRIBUTES, VERIFICATIONSTATUS, SPECIMENDETAILURL, COLLECTORNUMBER, VERBATIMELEVATION, YEAR, MONTH, DAY, AGENT\n";
        $labels = explode(",", $labels);
        $labels = array_map('trim', $labels); // will trim all values of the array
        return $labels;
    }

    private function prepare_row_data($line, $labels)
    {
        // used in old csv file
        // $line = str_ireplace(", ", "xxxyyy", $line);
        // $fields = str_getcsv($line);

        $line = str_replace('"', '', $line);
        $fields = explode("\t", $line);
        $record = array();
        $i = 0;
        foreach($fields as $field)
        {
            $record[$labels[$i]] = $field;
            $i++;
        }
        return $record;
    }

    private function parse_record_element($rec)
    {
        $reference_ids = array();
        $ref_ids = array();
        $agent_ids = self::get_object_agents($rec);
        $rec = $this->create_instances_from_taxon_object($rec, $reference_ids);
        self::get_images($rec, $ref_ids, $agent_ids);
    }

    private function get_object_agents($rec)
    {
        $agent_ids = array();
        if($rec['AGENT'] && !in_array(trim($rec['AGENT']), array("[no agent data]", "no agent")))
        {
            $agent = (string) trim($rec['AGENT']);
            if(!$agent) continue;
            $r = new \eol_schema\Agent();
            $r->term_name = $agent;
            $r->identifier = md5("$agent|photographer");
            $r->agentRole = "photographer";
            $r->term_homepage = "";
            $agent_ids[] = $r->identifier;
            if(!in_array($r->identifier, $this->resource_agent_ids)) 
            {
               $this->resource_agent_ids[] = $r->identifier;
               $this->archive_builder->write_object_to_file($r);
            }
        }
        return $agent_ids;
    }

    private function get_images($rec, $reference_ids = null, $agent_ids = null)
    {
        if(trim(@$rec['MEDIA_URI']) != "")
        {
            $mediaURL = urldecode(trim(@$rec['MEDIA_URI']));
            $mimeType = @$rec['MIME_TYPE'];
            $location = "";
            if(trim($rec['SPEC_LOCALITY']) != "[Exact locality unknown]") $location = $rec['SPEC_LOCALITY'];
            if($location != "") $location .= ", " . $rec['HIGHER_GEOG'];
            else                $location .= $rec['HIGHER_GEOG'];
            $desc = "";
            $typestatus = substr($rec['TYPESTATUS'], 0, stripos($rec['TYPESTATUS'], " "));
            if($typestatus)               $desc .= $typestatus.", ";
            if($rec['PARTS'])             $desc .= $rec['PARTS'].", ";
            if($rec['COLLECTING_METHOD']) $desc .= $rec['COLLECTING_METHOD'].", ";
            if($rec['COLLECTORS'] && trim($rec['COLLECTORS']) != "no agent" 
                                && trim($rec['COLLECTORS']) != "Unknown collector"
                                ) $desc .= "collected by " . $rec['COLLECTORS'] . ", ";
            if($rec['IDENTIFIEDBY'])      $desc .= "identified by " . $rec['IDENTIFIEDBY'].", ";
            if($rec['GUID'])              $desc .= "GUID: " . $rec['GUID'].", ";
            $date_created = $rec['created'];
            $date_modified = $rec['LAST_EDIT_DATE'];
            $dc_source = "";
            if(!in_array($rec['SPECIMENDETAILURL'], array("unknown", "unverified"))) $dc_source = $rec['SPECIMENDETAILURL'];
            $path_parts = pathinfo($mediaURL);
            $dc_identifier = $path_parts['basename'];
            $taxon_id = $rec["taxonID"];

            /*
            For location, : [SPEC_LOCALITY], [HIGHER_GEOG]
            For the description, : [first word of TYPESTATUS], [PARTS], [COLLECTING_METHOD], 
            collected by [COLLECTORS], identified by [IDENTIFIEDBY], GUID: [GUID] 
            */

            if(in_array($dc_identifier, $this->do_ids)) return;
            else $this->do_ids[] = $dc_identifier;

            $mr = new \eol_schema\MediaResource();
            if($reference_ids) $mr->referenceID = implode("; ", $reference_ids);
            if($agent_ids) $mr->agentID = implode("; ", $agent_ids);
            $mr->taxonID                = $taxon_id;
            $mr->identifier             = $dc_identifier;
            $mr->type                   = "http://purl.org/dc/dcmitype/StillImage";
            $mr->language               = 'en';
            $mr->format                 = $mimeType;
            $mr->furtherInformationURL  = $dc_source;
            $mr->description            = $desc;
            $mr->CVterm                 = "";
            $mr->title                  = "";
            $mr->creator                = "";
            $mr->CreateDate             = $date_created;
            $mr->modified               = $date_modified;
            $mr->LocationCreated        = $location;
            $mr->UsageTerms             = 'http://creativecommons.org/licenses/by-nc-sa/3.0/';
            $mr->Owner                  = "";
            $mr->publisher              = "";
            $mr->audience               = "";
            $mr->bibliographicCitation  = "";
            $mr->rights                 = "";
            $mr->accessURI              = $mediaURL;
            $mr->Rating                 = 2;
            $this->archive_builder->write_object_to_file($mr);
        }
    }

    function create_instances_from_taxon_object($rec, $reference_ids)
    {
        $taxon = new \eol_schema\Taxon();

        $sciname = trim($rec['SCIENTIFIC_NAME']);
        $taxon_id = "MCZ_" . str_replace(" ", "_", $sciname);
        $rec["taxonID"] = $taxon_id;

        if($reference_ids) $taxon->referenceID = implode("; ", $reference_ids);
        $taxon->taxonID = $taxon_id;

        $taxon->taxonRank                   = "";
        $taxon->scientificName              = (string) trim($sciname . " " . $rec['AUTHOR_TEXT']);
        $taxon->scientificNameAuthorship    = "";
        $taxon->vernacularName              = "";
        $taxon->kingdom                     = (string) $rec['KINGDOM'];
        $taxon->phylum                      = (string) $rec['PHYLUM'];
        $taxon->class                       = (string) $rec['PHYLCLASS'];
        $taxon->order                       = (string) $rec["PHYLORDER"];
        $taxon->family                      = (string) $rec["FAMILY"];
        $taxon->genus                       = (string) $rec["GENUS"];
        $taxon->furtherInformationURL       = (string) $rec["SPECIMENDETAILURL"];
        $taxon->specificEpithet             = "";
        $taxon->taxonomicStatus             = "";
        $taxon->nomenclaturalCode           = "";
        $taxon->nomenclaturalStatus         = "";
        $taxon->acceptedNameUsage           = "";
        $taxon->acceptedNameUsageID         = "";
        $taxon->parentNameUsageID           = "";
        $taxon->namePublishedIn             = "";
        $taxon->taxonRemarks                = "";
        $taxon->infraspecificEpithet        = "";
        $this->taxa[$taxon_id] = $taxon;
        return $rec;
    }

    function create_archive()
    {
        foreach($this->taxa as $t)
        {
            $this->archive_builder->write_object_to_file($t);
        }
        $this->archive_builder->finalize(true);
    }

}
?>