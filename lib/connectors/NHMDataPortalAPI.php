<?php
namespace php_active_record;
/* connector: [nhm_dataportal] 
NHM Data Portal - this connector will generate the EOL archive with structured data (for Type specimen data). This is done by accessing NHM API service.
*Working but not being used for now. What is used instead is: NMNHTypeRecordAPI.php, where we used their archive file we've downloaded (see DATA-1516).
*/

class NHMDataPortalAPI
{
    function __construct($folder)
    {
        $this->taxa = array();
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->download_options = array('expire_seconds' => false, 'download_wait_time' => 2000000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1);
        $this->service['specimen'] = "http://data.nhm.ac.uk/api/action/datastore_search?resource_id=05ff2255-c38a-40c9-b657-4ccb55ab2feb";
    }

    function get_all_taxa()
    {
        $this->uris = self::get_uris();
        // print_r($this->uris); exit;
        
        // offset = 927 k while caching...
        $limit = 500; $offset = 2552000; //orig limit=500 offset=0
        while(true)
        {
            $url = $this->service['specimen'] . "&limit=$limit&offset=$offset";
            if($contents = Functions::lookup_with_cache($url, $this->download_options))
            {
                $json = json_decode($contents);
                // print_r($json); exit();
                $returned = count($json->result->records);
                echo "\ncount: [$returned]\n";
                // self::process_specimen_records($json);
                // break;
            }
            $offset += $limit;
            if($returned < $limit) break;
        }
        // exit;
        $this->archive_builder->finalize(TRUE);
    }

    private function process_specimen_records($recs)
    {
        foreach($recs->result->records as $rec)
        {
            $rec->sciname = trim(str_replace($rec->scientificNameAuthorship, " ", $rec->scientificName));
            $rec->taxon_id = str_replace(" ", "_", $rec->sciname);
            self::generate_taxon($rec);
            self::generate_type_specimen($rec);
        }
    }
    
    private function generate_taxon($rec)
    {
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID                     = $rec->taxon_id;
        $taxon->scientificName              = $rec->sciname;
        $taxon->scientificNameAuthorship    = $rec->scientificNameAuthorship;
        $taxon->taxonRank                   = $rec->taxonRank;
        $taxon->furtherInformationURL       = '';
        $taxon->kingdom                     = $rec->kingdom;
        $taxon->phylum                      = $rec->phylum;
        $taxon->class                       = $rec->class;
        $taxon->order                       = $rec->order;
        $taxon->family                      = $rec->family;
        $taxon->genus                       = $rec->genus;
        // if($reference_ids) $taxon->referenceID = implode("; ", $reference_ids);
        if(!isset($this->taxon_ids[$taxon->taxonID]))
        {
            $this->taxon_ids[$taxon->taxonID] = '';
            $this->archive_builder->write_object_to_file($taxon);
        }
    }

    private function generate_type_specimen($rec)
    {
        print_r($rec);
        $measurementRemarks = "";
        if(!$institution_uri = self::get_uri($rec->institutionCode, "institutionCode"))
        {
            echo "\nno institution uri\n";
            return;
        }

        $typeStatus_uri = false;
        if($val = $rec->typeStatus) $typeStatus_uri = self::get_uri($val, "typeStatus");
        else echo "\nno type status uri\n";
        if($institution_uri && $typeStatus_uri)
        {
            self::add_string_types($rec, $institution_uri, "http://eol.org/schema/terms/TypeSpecimenRepository", $measurementRemarks);
            self::add_string_types($rec, $typeStatus_uri, "http://rs.tdwg.org/dwc/terms/typeStatus");
            // if($val = $rec["http://rs.tdwg.org/dwc/terms/verbatimEventDate"])   self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/eventDate");
            // if($val = $rec["http://rs.tdwg.org/dwc/terms/scientificName"])      self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/scientificName");
        }
    }

    private function add_string_types($rec, $value, $mtype, $measurementRemarks = null)
    {
        $catnum = (string) $rec->_id;
        $m = new \eol_schema\MeasurementOrFact();
        $occurrence_id = $this->add_occurrence($catnum, $rec);
        $m->occurrenceID = $occurrence_id;
        if($mtype == "http://eol.org/schema/terms/TypeSpecimenRepository") $m->measurementOfTaxon = 'true';
        $m->measurementType     = $mtype;
        $m->measurementValue    = utf8_encode((string) $value);
        // $m->measurementMethod   = '';
        if($measurementRemarks) // so that measurementRemarks (and source, contributor) appears only once in the [measurement_or_fact.tab]
        {
            $m->measurementRemarks = $measurementRemarks;
            // $m->source = '';
            // $m->contributor = '';
        }
        $this->archive_builder->write_object_to_file($m);
    }

    private function add_occurrence($catnum, $rec)
    {
        $occurrence_id = $catnum;
        if(isset($this->occurrence_ids[$occurrence_id])) return $occurrence_id;
        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = $occurrence_id;
        $o->taxonID      = $rec->taxon_id;
        // if($val = $rec["http://rs.tdwg.org/dwc/terms/institutionCode"]) $o->institutionCode = $val;
        // if($val = $rec["http://rs.tdwg.org/dwc/terms/collectionCode"])  $o->collectionCode = $val;
        // if($val = $rec["http://rs.tdwg.org/dwc/terms/catalogNumber"])   $o->catalogNumber = $val;
        // if($val = $rec["http://rs.tdwg.org/dwc/terms/locality"])        $o->locality = $val;
        $this->archive_builder->write_object_to_file($o);
        $this->occurrence_ids[$occurrence_id] = '';
        return $occurrence_id;
    }

    private function get_uri($value, $field)
    {
        if(in_array($field, array("typeStatus"))) $value = strtoupper($value);
        if($field == "typeStatus") $value = str_ireplace("TYPES", "TYPE", $value);
        if($val = @$this->uris[$value]) return $val;
        else
        {
            $this->debug["undefined"][$field][$value] = '';
            return $value;
        }
    }

    private function get_uris()
    {
        $spreadsheet = "http://localhost/~eolit/cp/NMNH/type_specimen_resource/nmnh mappings.xlsx"; //a good source of typeStatus URI's
        $params["uri_file"] = $spreadsheet;
        $params["dataset"]  = "GBIF";
        require_library('connectors/GBIFCountryTypeRecordAPI');
        $func = new GBIFCountryTypeRecordAPI("x");
        $uris = $func->get_uris($params, $spreadsheet);
        return $uris;
    }

}
?>