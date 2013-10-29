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
        $this->taxa = array();
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->taxon_ids = array();
        $this->object_ids = array();
        $this->EOL = 'http://www.eol.org/voc/table_of_contents';
        $this->dwca_file = "http://digir.mcz.harvard.edu/ipt/archive.do?r=mcz_for_eol";
        $this->first40k = "https://dl.dropboxusercontent.com/u/7597512/MCZHarvard/First40k.txt";
        /* 
        $this->dwca_file = "http://localhost/~eolit/cp/dwca-mcz_for_eol.zip";
        $this->first40k = "http://localhost/~eolit/eli/eol_php_code/update_resources/connectors/files/MCZ_Harvard/First40k.txt";
        */
        
        $this->not_utf8 = 0; // for stats
        $this->occurrence_ids = array();
    }

    /*
    images: 74609 | 75330 | 82,083 | 90,371
    text: 21129
    taxa: 11440 | 12,185 | 13,464
    */

    function get_all_taxa()
    {
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

        $this->create_archive();

        // remove temp dir
        recursive_rmdir($temp_dir);
        echo ("\n temporary directory removed: " . $temp_dir);
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
            // EOL might not recognize these two:
            $taxon->subgenus        = (string) $rec["http://rs.tdwg.org/dwc/terms/subgenus"];
            $taxon->rightsHolder    = (string) $rec["http://purl.org/dc/terms/rightsHolder"];
            // if(!isset($this->taxon_ids[$taxon->taxonID]))
            // {
                $this->taxa[$taxon->taxonID] = $taxon;
            //     $this->taxon_ids[$taxon->taxonID] = 1;
            // }
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
            $mr->UsageTerms     = (string) $rec["http://ns.adobe.com/xap/1.0/rights/UsageTerms"];
            $mr->audience       = 'Everyone';
            $mr->description    = (string) $rec["http://purl.org/dc/terms/description"];
            $mr->accessURI      = $mediaURL;
            $mr->thumbnailURL   = $thumbnailURL;
            $mr->furtherInformationURL = (string) $rec["http://rs.tdwg.org/ac/terms/furtherInformationURL"];
            if(!in_array($mr->identifier, $this->object_ids)) 
            {
               $this->object_ids[] = $mr->identifier;
               $this->archive_builder->write_object_to_file($mr);
            }
        }
    }

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

    private function get_texts_v2($records) // structured data
    {
        $i = 0;
        foreach($records as $rec)
        {
            $taxon_id = (string) $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
            if($rec["http://rs.tdwg.org/dwc/terms/typeStatus"])         self::add_string_types($taxon_id, "Type status", $rec["http://rs.tdwg.org/dwc/terms/typeStatus"]);
            if($rec["http://rs.gbif.org/terms/1.0/typeDesignatedBy"])   self::add_string_types($taxon_id, "Type designated by", $rec["http://rs.gbif.org/terms/1.0/typeDesignatedBy"]);
            if($rec["http://rs.tdwg.org/dwc/terms/scientificName"])     self::add_string_types($taxon_id, "Taxon", $rec["http://rs.tdwg.org/dwc/terms/scientificName"]);
            if($rec["http://rs.tdwg.org/dwc/terms/occurrenceID"])       self::add_string_types($taxon_id, "Occurrence ID", $rec["http://rs.tdwg.org/dwc/terms/occurrenceID"]);
            if($rec["http://rs.tdwg.org/dwc/terms/institutionCode"])    self::add_string_types($taxon_id, "Institution code", $rec["http://rs.tdwg.org/dwc/terms/institutionCode"]);
            if($rec["http://rs.tdwg.org/dwc/terms/collectionCode"])     self::add_string_types($taxon_id, "Collection code", $rec["http://rs.tdwg.org/dwc/terms/collectionCode"]);
            if($rec["http://rs.tdwg.org/dwc/terms/catalogNumber"])      self::add_string_types($taxon_id, "Catalog number", $rec["http://rs.tdwg.org/dwc/terms/catalogNumber"]);
            if($rec["http://rs.tdwg.org/dwc/terms/locality"])           self::add_string_types($taxon_id, "Locality", $rec["http://rs.tdwg.org/dwc/terms/locality"]);
            if($rec["http://rs.tdwg.org/dwc/terms/verbatimEventDate"])  self::add_string_types($taxon_id, "Event date", $rec["http://rs.tdwg.org/dwc/terms/verbatimEventDate"]);
            $i++;
            if($i >= 10) break; //debug
        }
        echo "\n Not utf8: [$this->not_utf8] \n";
    }

    private function add_string_types($taxon_id, $label, $value)
    {
        $value = utf8_encode($value);
        if(!Functions::is_utf8($value))
        {
            $this->not_utf8++;
            return;
        }
        $m = new \eol_schema\MeasurementOrFact();
        $m->measurementOfTaxon = 'true';
        $occurrence = $this->add_occurrence($taxon_id, $label, $value);
        $m->occurrenceID = $occurrence->occurrenceID;
        $m->measurementType = "http://mcz.harvard.edu/". SparqlClient::to_underscore($label);
        $m->measurementValue = "http://mcz.harvard.edu/". SparqlClient::to_underscore($value);
        $this->archive_builder->write_object_to_file($m);
    }

    private function add_occurrence($taxon_id, $label, $value)
    {
        $occurrence_id = md5($taxon_id . 'occurrence' . $label . $value);
        if(isset($this->occurrence_ids[$occurrence_id])) return $this->occurrence_ids[$occurrence_id];
        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = $occurrence_id;
        $o->taxonID = $taxon_id;
        $this->archive_builder->write_object_to_file($o);
        $this->occurrence_ids[$occurrence_id] = $o;
        return $o;
    }

    private function create_archive()
    {
        foreach($this->taxa as $t)
        {
            $this->archive_builder->write_object_to_file($t);
        }
        $this->archive_builder->finalize(TRUE);
    }

    private function get_first40k_images()
    {
        $first40k = array();
        if($temp_filepath = Functions::save_remote_file_to_local($this->first40k, array('timeout' => 4800, 'download_attempts' => 2, 'delay_in_minutes' => 2)))
        {
            require_library('connectors/BOLDSysAPI');
            $func = new BOLDSysAPI();
            $first40k = $func::get_array_from_json_file($temp_filepath);
            unlink($temp_filepath);
        }
        else echo "\n Investigate: first 40k images text file is missing. \n";
        return $first40k;
    }

    function get_mediaURL_for_first_40k_images() // a utility
    {
        require_library('connectors/BOLDSysAPI');
        $func = new BOLDSysAPI();
        $source = "http://localhost/~eolit/eli/eol_php_code/update_resources/connectors/files/MCZ_Harvard/MCZimages_still40k.tsv";
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