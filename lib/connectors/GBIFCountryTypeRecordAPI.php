<?php
namespace php_active_record;
/* connector: [872] GBIF country node - type records */
class GBIFCountryTypeRecordAPI
{
    function __construct($folder)
    {
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->taxon_ids = array();
        $this->object_ids = array();
        $this->occurrence_ids = array();
        $this->gbifID_taxonID = array();
        $this->debug = array();
    }

    function export_gbif_to_eol($params)
    {
        $this->uris = self::get_uris($params);
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($params["dwca_file"], "meta.xml");
        $archive_path = $paths['archive_path'];
        $temp_dir = $paths['temp_dir'];
        $this->harvester = new ContentArchiveReader(NULL, $archive_path);
        if(!(@$this->harvester->tables["http://rs.tdwg.org/dwc/terms/occurrence"][0]->fields)) // take note the index key is all lower case
        {
            debug("Invalid archive file. Program will terminate.");
            return false;
        }
        self::process_row_type(array("row_type" => 'http://rs.tdwg.org/dwc/terms/occurrence', "location" => "occurrence.txt", "type" => "taxa"));
        self::process_row_type(array("row_type" => 'http://rs.tdwg.org/dwc/terms/occurrence', "location" => "occurrence.txt", "type" => "data"));
        // self::process_row_type(array("row_type" => 'http://rs.gbif.org/terms/1.0/Multimedia', "location" => "multimedia.txt"));

        /*
        self::create_instances_from_taxon_object($harvester->process_row_type('http://rs.tdwg.org/dwc/terms/occurrence'));
        self::get_media_objects($harvester->process_row_type('http://rs.gbif.org/terms/1.0/Multimedia'));
        self::get_objects($harvester->process_row_type('http://eol.org/schema/media/Document'));
        self::get_references($harvester->process_row_type('http://rs.gbif.org/terms/1.0/Reference'));
        */
        
        $this->archive_builder->finalize(TRUE);
        // remove temp dir
        recursive_rmdir($temp_dir);
        echo ("\n temporary directory removed: " . $temp_dir);
        print_r($this->debug);
    }

    private function get_uris($params)
    {
        require_library('connectors/LifeDeskToScratchpadAPI');
        $func = new LifeDeskToScratchpadAPI();
        $spreadsheet_options = array("cache" => 1, "timeout" => 3600, "file_extension" => "xlsx", 'download_attempts' => 2, 'delay_in_minutes' => 2);
        $spreadsheet_options["expire_seconds"] = false; // false => won't expire; 0 => expires now
        $uris = array();
        if($spreadsheet = @$params["uri_file"])
        {
            if($arr = $func->convert_spreadsheet($spreadsheet, 0, $spreadsheet_options))
            {
                $fields = array();
                $fields["institutionCode"] = "institutionCode_uri";
                $fields["sex"] = "sex_uri";
                $fields["typeStatus"] = "typeStatus_uri";
                 foreach($fields as $key => $value)
                 {
                     $i = 0;
                     foreach($arr[$key] as $item)
                     {
                         if($item) $uris[$item] = $arr[$value][$i];
                         $i++;
                     }
                 }
            }
        }
        return $uris;
    }

    private function process_row_type($params, $callback = NULL, $parameters = NULL)
    {
        $row_type = $params["row_type"];
        $location = $params["location"];
        if(isset($this->harvester->tables[strtolower($row_type)]))
        {
            foreach($this->harvester->tables[strtolower($row_type)] as $table_definition)
            {
                if($table_definition->location != $location) continue;
                $this->harvester->file_iterator_index = 0;
                // rows are on newlines, so we can stream the file with an iterator
                if($table_definition->lines_terminated_by == "\n")
                {
                    $parameters['archive_table_definition'] =& $table_definition;
                    $i = 0;
                    foreach(new FileIterator($table_definition->file_uri) as $line_number => $line)
                    {
                        $i++;
                        if(($i % 10000) == 0) echo "\n" . $params["type"] . " - $i ";
                        $parameters['archive_line_number'] = $line_number;
                        if($table_definition->ignore_header_lines) $parameters['archive_line_number'] += 1;
                        $fields = $this->harvester->parse_table_row($table_definition, $line, $parameters);
                        if($fields == "this is the break message") break;
                        if($fields && $callback) call_user_func($callback, $fields, $parameters);
                        elseif($fields)
                        {
                            if($params["type"] == "taxa") self::create_instances_from_taxon_object($fields);
                            elseif($params["type"] == "data") self::process_type_records($fields);
                            // elseif($row_type == "http://rs.gbif.org/terms/1.0/Multimedia") self::get_media_objects($fields);
                        }
                        // if($i >= 10000) break; //debug - used during preview mode
                    }
                }
                // otherwise we need to load the entire file into memory and split it
                else exit("\n -does not go here- \n");
            }
        }
    }

    private function create_instances_from_taxon_object($rec)
    {
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID     = self::get_taxon_id($rec);
        // $this->gbifID_taxonID[$rec["http://rs.gbif.org/terms/1.0/gbifID"]] = $taxon->taxonID;
        $taxon->scientificName  = (string) $rec["http://rs.tdwg.org/dwc/terms/scientificName"];
        $taxon->kingdom         = (string) $rec["http://rs.tdwg.org/dwc/terms/kingdom"];
        $taxon->phylum          = (string) $rec["http://rs.tdwg.org/dwc/terms/phylum"];
        $taxon->class           = (string) $rec["http://rs.tdwg.org/dwc/terms/class"];
        $taxon->order           = (string) $rec["http://rs.tdwg.org/dwc/terms/order"];
        $taxon->family          = (string) $rec["http://rs.tdwg.org/dwc/terms/family"];
        $taxon->genus           = (string) $rec["http://rs.tdwg.org/dwc/terms/genus"];
        $taxon->taxonRank       = (string) $rec["http://rs.tdwg.org/dwc/terms/taxonRank"];
        if($taxon->scientificName || $taxon->genus || $taxon->family || $taxon->order || $taxon->class || $taxon->phylum || $taxon->kingdom)
        {
            if(!isset($this->taxon_ids[$taxon->taxonID]))
            {
                $this->taxon_ids[$taxon->taxonID] = '';
                $this->archive_builder->write_object_to_file($taxon);
            }
        }
        /* Haven not specified by Jen based on DATA-1557
        $taxon->scientificNameAuthorship  = (string) @$rec["http://rs.tdwg.org/dwc/terms/scientificNameAuthorship"]; // not all records have scientificNameAuthorship
        $taxon->taxonomicStatus = (string) $rec["http://rs.tdwg.org/dwc/terms/taxonomicStatus"];
        $taxon->taxonRemarks    = (string) $rec["http://rs.tdwg.org/dwc/terms/taxonRemarks"];
        $taxon->namePublishedIn = (string) $rec["http://rs.tdwg.org/dwc/terms/namePublishedIn"];
        $taxon->rightsHolder    = (string) $rec["http://purl.org/dc/terms/rightsHolder"];
        */
    }

    private function get_taxon_id($rec)
    {
        $taxon_id   = (string) $rec["http://rs.tdwg.org/dwc/terms/taxonID"];
        $sciname    = (string) $rec["http://rs.tdwg.org/dwc/terms/scientificName"];
        if(!$taxon_id) $taxon_id = md5($sciname);
        return $taxon_id;
    }

    private function process_type_records($rec) // structured data
    {
        if(!$val = (string) $rec["http://rs.tdwg.org/dwc/terms/scientificName"]) return;
        if((string) $rec["http://rs.tdwg.org/dwc/terms/typeStatus"] == "NOTATYPE") return;
        if((string) $rec["http://rs.tdwg.org/dwc/terms/institutionCode"] == "Museum Darmstadt") return;
        
        /* gbifID --- also, construct http://purl.org/dc/terms/source using this, eg:http://www.gbif.org/occurrence/1022646132 */
        if($val = (string) $rec["http://rs.gbif.org/terms/1.0/gbifID"]) $rec["source"] = "http://www.gbif.org/occurrence/" . $val;

        /* datasetKey --- use to construct http://purl.org/dc/terms/contributor, eg: http://www.gbif.org/dataset/85714c48-f762-11e1-a439-00145eb45e9a */
        if($val = (string) $rec["http://rs.gbif.org/terms/1.0/datasetKey"]) $rec["contributor"] = self::get_contributor_name("http://www.gbif.org/dataset/" . $val);

        if($val = $rec["http://rs.tdwg.org/dwc/terms/institutionCode"])
        {
            self::add_string_types($rec, self::get_uri($val, "institutionCode"), "http://rs.tdwg.org/dwc/terms/institutionCode", "true");
            if($val = (string) $rec["http://rs.tdwg.org/dwc/terms/typeStatus"]) self::add_string_types($rec, self::get_uri($val, "TypeInformation"), "http://eol.org/schema/terms/TypeInformation");
            if($val = (string) $rec["http://rs.tdwg.org/dwc/terms/collectionCode"]) self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/collectionCode");
            if($val = (string) $rec["http://rs.tdwg.org/dwc/terms/countryCode"]) self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/countryCode");
            if($val = (string) $rec["http://rs.tdwg.org/dwc/terms/year"]) self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/year");
            if($val = (string) $rec["http://rs.tdwg.org/dwc/terms/month"]) self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/month");
            if($val = (string) $rec["http://rs.tdwg.org/dwc/terms/day"]) self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/day");
            if($val = (string) $rec["http://rs.tdwg.org/dwc/terms/decimalLatitude"]) self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/decimalLatitude");
            if($val = (string) $rec["http://rs.tdwg.org/dwc/terms/decimalLongitude"]) self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/decimalLongitude");
            /* http://rs.tdwg.org/dwc/terms/eventDate OR you can construct this from day+month+year, whichever works better */
            if($val = (string) $rec["http://rs.tdwg.org/dwc/terms/eventDate"]) self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/eventDate");
            if($val = (string) $rec["http://rs.tdwg.org/dwc/terms/identifiedBy"]) self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/identifiedBy");
            if($val = (string) $rec["http://rs.tdwg.org/dwc/terms/locality"]) self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/locality");
            if($val = (string) $rec["http://rs.tdwg.org/dwc/terms/recordedBy"]) self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/recordedBy");
            if($val = (string) $rec["http://rs.tdwg.org/dwc/terms/sex"]) self::add_string_types($rec, self::get_uri($val, "sex"), "http://rs.tdwg.org/dwc/terms/sex");
            if($val = (string) $rec["http://rs.tdwg.org/dwc/terms/verbatimElevation"]) self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/verbatimElevation");
            if($val = (string) $rec["http://rs.tdwg.org/dwc/terms/verbatimDepth"]) self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/verbatimDepth");
            if($val = (string) $rec["http://rs.tdwg.org/dwc/terms/occurrenceRemarks"]) self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/occurrenceRemarks");
        }
    }

    private function get_uri($value, $field)
    {
        if($val = @$this->uris[$value]) return $val;
        else
        {
            $this->debug[$field][$value] = '';
            return $value;
        }
    }

    private function add_string_types($rec, $value, $measurementType, $measurementOfTaxon = "")
    {
        $taxon_id = self::get_taxon_id($rec);
        $catnum = (string) $rec["http://rs.gbif.org/terms/1.0/gbifID"];
        $m = new \eol_schema\MeasurementOrFact();
        $occurrence_id = $this->add_occurrence($taxon_id, $catnum);
        $m->occurrenceID = $occurrence_id;
        $m->measurementOfTaxon = $measurementOfTaxon;
        if($measurementOfTaxon ==  "true")
        {   // so that measurementRemarks (and source, contributor, etc.) appears only once in the [measurement_or_fact.tab]
            $m->measurementRemarks = '';
            $m->source = $rec["source"];
            $m->contributor = $rec["contributor"];
            /* not used at the moment
            if($referenceID = self::prepare_reference((string) $rec["http://eol.org/schema/reference/referenceID"]))
            {
                $m->referenceID = $referenceID;
            }
            */
        }
        $m->measurementType = $measurementType;
        $m->measurementValue = (string) $value;
        $m->measurementMethod = '';
        $this->archive_builder->write_object_to_file($m);
    }

    private function add_occurrence($taxon_id, $catnum)
    {
        // $occurrence_id = $taxon_id . '_' . $catnum;
        $occurrence_id = $catnum;
        if(isset($this->occurrence_ids[$occurrence_id])) return $occurrence_id;
        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = $occurrence_id;
        $o->taxonID = $taxon_id;
        $this->archive_builder->write_object_to_file($o);
        $this->occurrence_ids[$occurrence_id] = '';
        return $occurrence_id;
    }

    private function get_contributor_name($url)
    {
        $options = array('expire_seconds' => false, 'download_wait_time' => 1000000, 'timeout' => 900, 'download_attempts' => 2, 'delay_in_minutes' => 2); // 15mins timeout
        if($html = Functions::lookup_with_cache($url, $options))
        {
            // <title property="dc:title">Herbarium Berolinense - Dataset detail</title>
            if(preg_match("/\"dc:title\">(.*?)\- Dataset detail/ims", $html, $arr)) return trim($arr[1]);
        }
    }

    /*
    private function process_fields($records, $class)
    {
        foreach($records as $rec)
        {
            if    ($class == "vernacular") $c = new \eol_schema\VernacularName();
            elseif($class == "agent")      $c = new \eol_schema\Agent();
            elseif($class == "reference")  $c = new \eol_schema\Reference();
            $keys = array_keys($rec);
            foreach($keys as $key)
            {
                $temp = pathinfo($key);
                $field = $temp["basename"];

                // some fields have '#', e.g. "http://schemas.talis.com/2005/address/schema#localityName"
                $parts = explode("#", $field);
                if($parts[0]) $field = $parts[0];
                if(@$parts[1]) $field = $parts[1];

                $c->$field = $rec[$key];
                if($field == "taxonID") $c->$field = str_ireplace("urn:lsid:marinespecies.org:taxname:", "", $c->$field);
            }
            $this->archive_builder->write_object_to_file($c);
        }
    }
    
    private function get_media_objects($rec)
    {
         // only 1 is not used: [http://purl.org/dc/terms/references] => 
        $mr = new \eol_schema\MediaResource();
        $mr->taxonID        = $this->gbifID_taxonID[$rec["http://rs.gbif.org/terms/1.0/gbifID"]];
        $mr->identifier     = (string) $rec["http://rs.gbif.org/terms/1.0/gbifID"]; // $rec["http://purl.org/dc/terms/identifier"];
        $mr->type           = (string) $rec["http://purl.org/dc/terms/type"];
        $mr->audience       = (string) $rec["http://purl.org/dc/terms/audience"];
        $mr->format         = (string) $rec["http://purl.org/dc/terms/format"];
        $mr->title          = (string) $rec["http://purl.org/dc/terms/title"];
        $mr->CVterm         = (string) $rec["http://purl.org/dc/terms/license"];
        $mr->creator        = (string) $rec["http://purl.org/dc/terms/creator"];
        $mr->CreateDate     = (string) $rec["http://purl.org/dc/terms/created"];
        $mr->Owner          = (string) $rec["http://purl.org/dc/terms/rightsHolder"];
        $mr->title          = (string) $rec["http://purl.org/dc/terms/title"];
        $mr->description    = (string) $rec["http://purl.org/dc/terms/description"];
        $mr->publisher      = (string) $rec["http://purl.org/dc/terms/publisher"];
        $mr->contributor    = (string) $rec["http://purl.org/dc/terms/contributor"];
        $mr->audience       = (string) $rec["http://purl.org/dc/terms/audience"];
        $mr->accessURI      = (string) $rec["http://purl.org/dc/terms/identifier"];
        $mr->furtherInformationURL = (string) $rec["http://purl.org/dc/terms/source"];
        // $mr->rights         = "";
        // $mr->UsageTerms     = "";
        if(!isset($this->object_ids[$mr->identifier]))
        {
           $this->object_ids[$mr->identifier] = '';
           $this->archive_builder->write_object_to_file($mr);
        }
    }

    private function get_objects($records)
    {
        // refer to WormsArchiveAPI.php
    }

    private function prepare_reference($referenceID)
    {
        if($referenceID)
        {
            $ids = explode(",", $referenceID); // not sure yet what separator Worms used, comma or semicolon - or if there are any
            $reference_ids = array();
            foreach($ids as $id) $reference_ids[] = $id;
            return implode("; ", $reference_ids);
        }
        return false;
    }
    
    private function get_vernaculars($records)
    {
        self::process_fields($records, "vernacular");
    }

    private function get_agents($records)
    {
        self::process_fields($records, "agent");
    }
    
    private function get_references($records)
    {
        self::process_fields($records, "reference");
    }
    */

}
?>