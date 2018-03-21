<?php
namespace php_active_record;
/* connector: [201]
We received a Darwincore archive file from the partner.
Connector downloads the archive file, extracts, reads the archive file, assembles the data and generates the EOL DWC-A resource.
*/
class MCZHarvardArchiveAPI
{
    function __construct($folder)
    {
        $this->resource_id = $folder;
        $this->taxa = array();
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->object_ids = array();
        $this->EOL = 'http://www.eol.org/voc/table_of_contents';
        $this->dwca_file = "http://digir.mcz.harvard.edu/ipt/archive.do?r=mcz_for_eol";
        // $this->first40k = "https://dl.dropboxusercontent.com/u/7597512/MCZHarvard/First40k.txt";
        $this->first40k = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/MCZ/First40k.txt";

        /* 
        $this->dwca_file = "http://localhost/cp_new/MCZ/dwca-mcz_for_eol.zip";
        $this->first40k  = "http://localhost/cp_new/MCZ/First40k.txt";
        */
        $this->occurrence_ids = array();
        $this->types = array(); // for stats
        $this->page_by_guid = "http://mczbase.mcz.harvard.edu/guid/";
    }

    /*
    images: 74609 | 75330 | 82,083 | 90,371 |   94,261  114,658
    text: 21129
    measurementorfact:                          166,888 201,088
    taxa: 11440 | 12,185 | 13,464 |             14,214  17,499
    */

    function get_all_taxa()
    {
        $this->uris = self::get_uris();
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($this->dwca_file, "meta.xml");
        $archive_path = $paths['archive_path'];
        $temp_dir = $paths['temp_dir'];

        $harvester = new ContentArchiveReader(NULL, $archive_path);
        $tables = $harvester->tables;
        if(!($this->fields["taxa"] = $tables["http://rs.tdwg.org/dwc/terms/taxon"][0]->fields)) // take note the index key is all lower case
        {
            debug("Invalid archive file. Program will terminate.");
            return false;
        }

        self::create_instances_from_taxon_object($harvester->process_row_type('http://rs.tdwg.org/dwc/terms/Taxon'));
        self::get_images($harvester->process_row_type('http://eol.org/schema/media/Document'));
        self::get_texts_v2($harvester->process_row_type('http://rs.gbif.org/terms/1.0/TypesAndSpecimen'));
        $this->archive_builder->finalize(TRUE);
        // remove temp dir
        recursive_rmdir($temp_dir);
        echo ("\n temporary directory removed: " . $temp_dir);
        print_r($this->debug);
    }

    private function create_instances_from_taxon_object($records)
    {
        foreach($records as $rec)
        {
            $taxon = new \eol_schema\Taxon();
            $taxon->taxonID         = (string) $rec["http://rs.tdwg.org/dwc/terms/taxonID"];
            $taxon->scientificName  = (string) $rec["http://rs.tdwg.org/dwc/terms/scientificName"];
            $taxon->kingdom         = (string) $rec["http://rs.tdwg.org/dwc/terms/kingdom"];
            $taxon->phylum          = (string) $rec["http://rs.tdwg.org/dwc/terms/phylum"];
            $taxon->class           = (string) $rec["http://rs.tdwg.org/dwc/terms/class"];
            $taxon->order           = (string) $rec["http://rs.tdwg.org/dwc/terms/order"];
            $taxon->family          = (string) $rec["http://rs.tdwg.org/dwc/terms/family"];
            $taxon->genus           = (string) $rec["http://rs.tdwg.org/dwc/terms/genus"];
            $taxon->subgenus        = (string) $rec["http://rs.tdwg.org/dwc/terms/subgenus"];
            $taxon->rightsHolder    = (string) $rec["http://purl.org/dc/terms/rightsHolder"];
            $this->archive_builder->write_object_to_file($taxon);
        }
    }

    private function get_images($records)
    {
        // now we need first to retrieve the first 40k images and use the identifiers used before
        $first40k = self::get_first40k_images();

        foreach($records as $rec)
        {
            if($rec["http://purl.org/dc/terms/type"] != "image") continue;

            $format = (string) $rec["http://purl.org/dc/terms/format"];
            if(!in_array($format, array("image/jpeg", "image/gif"))) continue;

            $mediaURL = (string) $rec["http://rs.tdwg.org/ac/terms/accessURI"];
            if(substr($mediaURL, 0, 4) != "http") $mediaURL = "http://" . $mediaURL;
            
            if(!is_numeric(stripos($mediaURL, "mcz.harvard.edu"))) continue;
            
            $thumbnailURL = (string) $rec["http://eol.org/schema/media/thumbnailURL"];
            if($thumbnailURL == "undefined") $thumbnailURL = "";
            
            if(in_array($mediaURL, $first40k)) 
            {
                /* first orig 40k images, to maintain history i have to use the old identifiers when partner gave us TSV file, which is the basename of the mediaURL */
                $parts = pathinfo($mediaURL);
                $identifier = $parts['basename'];
            }
            else $identifier = (string) $rec["http://purl.org/dc/terms/identifier"]; // new batch of 30k images more

            $mr = new \eol_schema\MediaResource();
            $mr->taxonID        = (string) $rec["http://rs.tdwg.org/dwc/terms/taxonID"];
            $mr->identifier     = $identifier;
            $mr->type           = "http://purl.org/dc/dcmitype/StillImage";
            $mr->language       = 'en';
            $mr->format         = $format;
            $mr->title          = (string) $rec["http://purl.org/dc/terms/title"];
            $mr->creator        = (string) $rec["http://purl.org/dc/terms/creator"];
            $mr->CreateDate     = (string) $rec["http://ns.adobe.com/xap/1.0/CreateDate"];
            $mr->Owner          = (string) $rec["http://ns.adobe.com/xap/1.0/rights/Owner"];
            $mr->rights         = "";
            $mr->UsageTerms     = self::get_license((string) $rec["http://ns.adobe.com/xap/1.0/rights/UsageTerms"]);
            $mr->audience       = 'Everyone';
            $mr->description    = (string) $rec["http://purl.org/dc/terms/description"];
            $mr->accessURI      = $mediaURL;
            $mr->thumbnailURL   = $thumbnailURL;
            $mr->furtherInformationURL = (string) $rec["http://rs.tdwg.org/ac/terms/furtherInformationURL"];
            if((string) $mr->accessURI == $mr->furtherInformationURL) continue;
            if(!isset($this->object_ids[$mr->identifier]))
            {
               $this->object_ids[$mr->identifier] = '';
               $this->archive_builder->write_object_to_file($mr);
            }
        }
    }
    private function get_license($str)
    {
        if($str == "Available under Creative Commons Attribution Share Alike Non Commerical (CC-BY-NC-SA 3.0) license") return "http://creativecommons.org/licenses/by-nc-sa/3.0/";
        else echo "\nUpdate code, unknown license\n";
        return;
    }

    /* no longer being used at the moment, but working before...
    private function get_texts($records)
    {
        $not_utf8 = 0;
        foreach($records as $rec)
        {
            $desc = "";
            if($rec["http://rs.tdwg.org/dwc/terms/typeStatus"]) $desc .= "Type status: " . $rec["http://rs.tdwg.org/dwc/terms/typeStatus"] . "<br>";
            if($rec["http://rs.gbif.org/terms/1.0/typeDesignatedBy"]) $desc .= "Type designated by: " . $rec["http://rs.gbif.org/terms/1.0/typeDesignatedBy"] . "<br>";
            if($rec["http://rs.tdwg.org/dwc/terms/scientificName"]) $desc .= "Taxon: " . $rec["http://rs.tdwg.org/dwc/terms/scientificName"] . "<br>";
            if($rec["http://rs.tdwg.org/dwc/terms/occurrenceID"]) $desc .= "Occurrence ID: " . $rec["http://rs.tdwg.org/dwc/terms/occurrenceID"] . "<br>";
            if($rec["http://rs.tdwg.org/dwc/terms/institutionCode"]) $desc .= "Institution code: " . $rec["http://rs.tdwg.org/dwc/terms/institutionCode"] . "<br>";
            if($rec["http://rs.tdwg.org/dwc/terms/collectionCode"]) $desc .= "Collection code: " . $rec["http://rs.tdwg.org/dwc/terms/collectionCode"] . "<br>";
            if($rec["http://rs.tdwg.org/dwc/terms/catalogNumber"]) $desc .= "Catalog number: " . $rec["http://rs.tdwg.org/dwc/terms/catalogNumber"] . "<br>";
            if($rec["http://rs.tdwg.org/dwc/terms/locality"]) $desc .= "Locality: " . $rec["http://rs.tdwg.org/dwc/terms/locality"] . "<br>";
            if($rec["http://rs.tdwg.org/dwc/terms/verbatimEventDate"]) $desc .= "Event date: " . $rec["http://rs.tdwg.org/dwc/terms/verbatimEventDate"] . "<br>";
            $desc = utf8_encode($desc);
            if(!Functions::is_utf8($desc))
            {
                $not_utf8++;
                continue;
            }
            $identifier = md5($desc);
            $mr = new \eol_schema\MediaResource();
            $mr->taxonID        = (string) $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
            $mr->identifier     = $identifier;
            $mr->type           = "http://purl.org/dc/dcmitype/Text";
            $mr->language       = 'en';
            $mr->format         = "text/html";
            $mr->CVterm         = $this->EOL . "#TypeInformation";
            $mr->UsageTerms     = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
            $mr->audience       = 'Everyone';
            $mr->description    = (string) $desc;
            if(!in_array($mr->identifier, $this->object_ids)) 
            {
               $this->object_ids[] = $mr->identifier;
               $this->archive_builder->write_object_to_file($mr);
            }
        }
        echo "\n Not utf8: [$not_utf8] \n";
    }
    */

    private function get_texts_v2($records) // structured data
    {
        $i = 0;
        foreach($records as $rec)
        {
            $this->types[$rec["http://rs.tdwg.org/dwc/terms/typeStatus"]] = 1;
            $measurementRemarks = "";
            if($val = $rec["http://rs.gbif.org/terms/1.0/typeDesignatedBy"])
            {
                if($val != "no citation available") $measurementRemarks .= "Type designated by: " . $val . "<br>";
            }

            $institution_uri = "http://biocol.org/urn:lsid:biocol.org:col:33791"; //Museum of Comparative Zoology, Harvard University
            $typeStatus_uri = false;
            if($val = self::format_type_status($rec["http://rs.tdwg.org/dwc/terms/typeStatus"]))
            {
                $typeStatus_uri = self::get_uri($val, "typeStatus");
            }

            if($institution_uri && $typeStatus_uri)
            {
                self::add_string_types($rec, $institution_uri, "http://eol.org/schema/terms/TypeSpecimenRepository", $measurementRemarks);
                self::add_string_types($rec, $typeStatus_uri, "http://rs.tdwg.org/dwc/terms/typeStatus");
                

                if($val = $rec["http://rs.tdwg.org/dwc/terms/verbatimEventDate"])   self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/eventDate");
                if($val = $rec["http://rs.tdwg.org/dwc/terms/scientificName"])      self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/scientificName");
                
            }
            // else
            // {
            //     echo "\ninvalid rec \n";
            //     print_r($rec);
            // }
            $i++;
            // if($i >= 1000) break; //debug - just first 1000 records during preview phase
        }
    }

    private function add_string_types($rec, $value, $mtype, $measurementRemarks = null)
    {
        $taxon_id = (string) $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
        $catnum = (string) $rec["http://rs.tdwg.org/dwc/terms/catalogNumber"];
        $m = new \eol_schema\MeasurementOrFact();
        $occurrence_id = $this->add_occurrence($taxon_id, $catnum, $rec);
        $m->occurrenceID = $occurrence_id;
        if($mtype == "http://eol.org/schema/terms/TypeSpecimenRepository") $m->measurementOfTaxon = 'true';
        $m->measurementType     = $mtype;
        $m->measurementValue    = utf8_encode((string) $value);
        $m->measurementMethod   = '';
        if($measurementRemarks) // so that measurementRemarks (and source, contributor) appears only once in the [measurement_or_fact.tab]
        {
            $m->measurementRemarks = $measurementRemarks;
            $m->source = $this->page_by_guid . $rec["http://rs.tdwg.org/dwc/terms/occurrenceID"];
            $m->contributor = 'Museum of Comparative Zoology, Harvard';
        }
        $m->measurementID = Functions::generate_measurementID($m, $this->resource_id);
        if(!isset($this->measurement_ids[$m->measurementID])) {
            $this->archive_builder->write_object_to_file($m);
            $this->measurement_ids[$m->measurementID] = '';
        }
    }

    private function get_uris()
    {
        /*
        $spreadsheet = "http://localhost/cp_new/NMNH/type_specimen_resource/nmnh-mappings.xlsx"; //a good source of typeStatus URI's
        $spreadsheet = "https://dl.dropboxusercontent.com/u/7597512/NMNH/type_specimen_resource/nmnh mappings.xlsx";
        */
        $spreadsheet  = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/NMNH/type_specimen_resource/nmnh-mappings.xlsx";
        $params["uri_file"] = $spreadsheet;
        $params["dataset"]  = "GBIF";
        require_library('connectors/GBIFCountryTypeRecordAPI');
        $func = new GBIFCountryTypeRecordAPI("x");
        $uris = $func->get_uris($params, $spreadsheet);
        return $uris;
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

    private function format_type_status($type_status)
    {
        // manual adjustments
        $type_status = str_ireplace(array(" (ms)", " (Paleontology)"), "", $type_status);
        $type_status = str_ireplace("Paralectotpye", "Paralectotype", $type_status);
        $type_status = str_ireplace("ALLOECTOTYPE", "LECTOALLOTYPE", $type_status);
        $type_status = strtoupper($type_status);
        if(in_array($type_status, array("FIGURED", "VOUCHER", "ADDITIONAL MATERIAL", "ERRONEOUS CITATION"))) return false;
        return $type_status;
    }

    private function add_occurrence($taxon_id, $catnum, $rec)
    {
        $occurrence_id = $taxon_id . 'O' . $catnum;
        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = $occurrence_id;
        $o->taxonID      = $taxon_id;
        if($val = $rec["http://rs.tdwg.org/dwc/terms/institutionCode"]) $o->institutionCode = $val;
        if($val = $rec["http://rs.tdwg.org/dwc/terms/collectionCode"])  $o->collectionCode = $val;
        if($val = $rec["http://rs.tdwg.org/dwc/terms/catalogNumber"])   $o->catalogNumber = $val;
        if($val = $rec["http://rs.tdwg.org/dwc/terms/locality"])        $o->locality = $val;

        $o->occurrenceID = Functions::generate_measurementID($o, $this->resource_id, 'occurrence');
        if(isset($this->occurrence_ids[$o->occurrenceID])) return $o->occurrenceID;
        $this->archive_builder->write_object_to_file($o);
        $this->occurrence_ids[$o->occurrenceID] = '';
        return $o->occurrenceID;
        
        /* old ways
        $this->archive_builder->write_object_to_file($o);
        $this->occurrence_ids[$occurrence_id] = '';
        return $occurrence_id;
        */
    }

    private function get_first40k_images()
    {
        $first40k = array();
        if($temp_filepath = Functions::save_remote_file_to_local($this->first40k, array('cache' => 1, 'timeout' => 4800, 'download_attempts' => 2, 'delay_in_minutes' => 2)))
        {
            require_library('connectors/BOLDSysAPI');
            $func = new BOLDSysAPI();
            $first40k = $func->get_array_from_json_file($temp_filepath);
            unlink($temp_filepath);
        }
        else echo "\n Investigate: first 40k images text file is missing. \n";
        return $first40k;
    }

    function get_mediaURL_for_first_40k_images() // a utility
    {
        require_library('connectors/BOLDSysAPI');
        $func = new BOLDSysAPI();
        $source = "http://localhost/eol_php_code/update_resources/connectors/files/MCZ_Harvard/MCZimages_still40k.tsv";
        $destination = DOC_ROOT .                         "/update_resources/connectors/files/MCZ_Harvard/First40k.txt";
        if($temp_filepath = Functions::save_remote_file_to_local($source, array('timeout' => 4800, 'download_attempts' => 2)))
        {
            $records = array();
            foreach(new FileIterator($temp_filepath, true) as $line_number => $line) // 'true' will auto delete temp_filepath
            {
                if($line)
                {
                    $cols = explode("\t", $line);
                    if(count($cols) == 80) $records[str_replace('"', '', $cols[1])] = 1;
                }
            }
        }
        $func::save_to_json_file(array_keys($records), $destination);
    }

}
?>