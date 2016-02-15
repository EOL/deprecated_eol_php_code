<?php
namespace php_active_record;
/* connector: [gbif] GBIF archive connector */
class GBIFdwcaAPI
{
    function __construct($folder)
    {
        $this->taxa = array();
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->taxon_ids = array();
        $this->object_ids = array();
        $this->occurrence_ids = array();
        $this->gbifID_taxonID = array();
        // $this->taxon_page = "http://www.marinespecies.org/aphia.php?p=taxdetails&id=";
    }

    function export_gbif_to_eol($params)
    {
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($params["dwca_file"], "meta.xml");
        $archive_path = $paths['archive_path'];
        $temp_dir = $paths['temp_dir'];

        $harvester = new ContentArchiveReader(NULL, $archive_path);
        $tables = $harvester->tables;

        if(!($this->fields["occurrence"] = $tables["http://rs.tdwg.org/dwc/terms/occurrence"][0]->fields)) // take note the index key is all lower case
        {
            debug("Invalid archive file. Program will terminate.");
            return false;
        }
        
        /*
            $harvester->process_row_type() -  this will convert rows into array.
        */
        
        // $r = $harvester->process_row_type('http://rs.tdwg.org/dwc/terms/occurrence');
        // $r = $harvester->process_row_type('http://rs.gbif.org/terms/1.0/Multimedia');
        // print_r($r); exit;

        self::create_instances_from_taxon_object($harvester->process_row_type('http://rs.tdwg.org/dwc/terms/occurrence'));
        self::get_media_objects($harvester->process_row_type('http://rs.gbif.org/terms/1.0/Multimedia'));

        // self::get_objects($harvester->process_row_type('http://eol.org/schema/media/Document'));
        // self::get_references($harvester->process_row_type('http://rs.gbif.org/terms/1.0/Reference'));
        // self::get_agents($harvester->process_row_type('http://eol.org/schema/agent/Agent'));
        // self::get_vernaculars($harvester->process_row_type('http://rs.gbif.org/terms/1.0/VernacularName'));
        
        $this->archive_builder->finalize(TRUE);
        

        // remove temp dir
        recursive_rmdir($temp_dir);
        echo ("\n temporary directory removed: " . $temp_dir);
    }

    private function get_taxa_list($occurrences)
    {
        $taxa = array();
        foreach($occurrences as $o)
        {
            // $taxa[$o->]
        }
    }

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

    private function create_instances_from_taxon_object($records)
    {
        foreach($records as $rec)
        {
            $taxon = new \eol_schema\Taxon();
            $taxon->taxonID         = (string) $rec["http://rs.tdwg.org/dwc/terms/taxonID"];
            $taxon->scientificName  = (string) $rec["http://rs.tdwg.org/dwc/terms/scientificName"];
            if(!$taxon->taxonID) $taxon->taxonID = md5($taxon->scientificName);
            
            $this->gbifID_taxonID[$rec["http://rs.gbif.org/terms/1.0/gbifID"]] = $taxon->taxonID;
            
            $taxon->scientificNameAuthorship  = (string) @$rec["http://rs.tdwg.org/dwc/terms/scientificNameAuthorship"]; // not all records have scientificNameAuthorship
            $taxon->taxonRank       = (string) $rec["http://rs.tdwg.org/dwc/terms/taxonRank"];
            $taxon->taxonomicStatus = (string) $rec["http://rs.tdwg.org/dwc/terms/taxonomicStatus"];
            $taxon->taxonRemarks    = (string) $rec["http://rs.tdwg.org/dwc/terms/taxonRemarks"];
            $taxon->namePublishedIn = (string) $rec["http://rs.tdwg.org/dwc/terms/namePublishedIn"];
            $taxon->rightsHolder    = (string) $rec["http://purl.org/dc/terms/rightsHolder"];
            $this->taxa[$taxon->taxonID] = $taxon;
            
            if(!isset($this->taxon_ids[$taxon->taxonID]))
            {
                $this->taxon_ids[$taxon->taxonID] = '';
                $this->archive_builder->write_object_to_file($taxon);
            }
        }
    }

    private function get_media_objects($records)
    {
        foreach($records as $rec)
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
    }

    private function get_objects($records)
    {
        // refer to WormsArchiveAPI.php
    }

    private function process_distribution($rec) // structured data
    {
        /* not used yet
        [] => WoRMS:distribution:274241
        [http://purl.org/dc/terms/type] => http://purl.org/dc/dcmitype/Text
        [http://rs.tdwg.org/audubon_core/subtype] => 
        [http://purl.org/dc/terms/format] => text/html
        [http://purl.org/dc/terms/title] => Distribution
        [http://eol.org/schema/media/thumbnailURL] => 
        [http://rs.tdwg.org/ac/terms/furtherInformationURL] => 
        [http://purl.org/dc/terms/language] => en
        [http://ns.adobe.com/xap/1.0/Rating] => 
        [http://purl.org/dc/terms/audience] => 
        [http://ns.adobe.com/xap/1.0/rights/UsageTerms] => http://creativecommons.org/licenses/by/3.0/
        [http://purl.org/dc/terms/rights] => This work is licensed under a Creative Commons Attribution-Share Alike 3.0 License
        [http://eol.org/schema/agent/agentID] => WoRMS:Person:10
        */
        
        // other units:
        $derivedFrom     = "http://rs.tdwg.org/ac/terms/derivedFrom";
        $CreateDate      = "http://ns.adobe.com/xap/1.0/CreateDate"; // 2004-12-21T16:54:05+01:00
        $modified        = "http://purl.org/dc/terms/modified"; // 2004-12-21T16:54:05+01:00
        $LocationCreated = "http://iptc.org/std/Iptc4xmpExt/1.0/xmlns/LocationCreated";
        $spatial         = "http://purl.org/dc/terms/spatial";
        $lat             = "http://www.w3.org/2003/01/geo/wgs84_pos#lat";
        $long            = "http://www.w3.org/2003/01/geo/wgs84_pos#long";
        $alt             = "http://www.w3.org/2003/01/geo/wgs84_pos#alt";
        // for measurementRemarks
        $publisher  = "http://purl.org/dc/terms/publisher";
        $creator    = "http://purl.org/dc/terms/creator"; // db_admin
        $Owner      = "http://ns.adobe.com/xap/1.0/rights/Owner";

        $measurementRemarks = "";
        if($val = $rec["http://purl.org/dc/terms/description"])
        {
                                                        self::add_string_types($rec, "Distribution", $val, "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution");
            if($val = (string) $rec[$derivedFrom])      self::add_string_types($rec, "Derived from", $val, $derivedFrom);
            if($val = (string) $rec[$CreateDate])       self::add_string_types($rec, "Create date", $val, $CreateDate);
            if($val = (string) $rec[$modified])         self::add_string_types($rec, "Modified", $val, $modified);
            if($val = (string) $rec[$LocationCreated])  self::add_string_types($rec, "Location created", $val, $LocationCreated);
            if($val = (string) $rec[$spatial])          self::add_string_types($rec, "Spatial", $val, $spatial);
            if($val = (string) $rec[$lat])              self::add_string_types($rec, "Latitude", $val, $lat);
            if($val = (string) $rec[$long])             self::add_string_types($rec, "Longitude", $val, $long);
            if($val = (string) $rec[$alt])              self::add_string_types($rec, "Altitude", $val, $alt);
            if($val = (string) $rec[$publisher])        self::add_string_types($rec, "Publisher", $val, $publisher);
            if($val = (string) $rec[$creator])          self::add_string_types($rec, "Creator", $val, $creator);
            if($val = (string) $rec[$Owner])            self::add_string_types($rec, "Owner", $val, $Owner);
        }
    }

    private function add_string_types($rec, $label, $value, $measurementType)
    {
        $taxon_id = (string) $rec["http://rs.tdwg.org/dwc/terms/taxonID"];
        $taxon_id = str_ireplace("urn:lsid:marinespecies.org:taxname:", "", $taxon_id);
        $catnum = (string) $rec["http://purl.org/dc/terms/identifier"];
        $m = new \eol_schema\MeasurementOrFact();
        $occurrence = $this->add_occurrence($taxon_id, $catnum);
        $m->occurrenceID = $occurrence->occurrenceID;
        if($label == "Distribution")
        {   // so that measurementRemarks (and source, contributor, etc.) appears only once in the [measurement_or_fact.tab]
            $m->measurementOfTaxon = 'true';
            $m->measurementRemarks = '';
            $m->source = (string) $rec["http://rs.tdwg.org/ac/terms/accessURI"]; // http://www.marinespecies.org/aphia.php?p=distribution&id=274241
            $m->bibliographicCitation = (string) $rec["http://purl.org/dc/terms/bibliographicCitation"];
            $m->contributor = (string) $rec["http://purl.org/dc/terms/contributor"];
            if($referenceID = self::prepare_reference((string) $rec["http://eol.org/schema/reference/referenceID"]))
            {
                $m->referenceID = $referenceID;
            }
        }
        $m->measurementType = $measurementType;
        $m->measurementValue = (string) $value;
        $m->measurementMethod = '';
        $this->archive_builder->write_object_to_file($m);
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

    private function add_occurrence($taxon_id, $catnum)
    {
        $occurrence_id = $taxon_id . 'O' . $catnum; // suggested by Katja to use -- ['O' . $catnum]
        // $occurrence_id = md5($taxon_id . 'occurrence'); from environments
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
        foreach($this->taxa as $t) $this->archive_builder->write_object_to_file($t);
        $this->archive_builder->finalize(TRUE);
    }

    private function get_vernaculars($records)
    {
        self::process_fields($records, "vernacular");
        // foreach($records as $rec)
        // {
        //     $v = new \eol_schema\VernacularName();
        //     $v->taxonID         = $rec["http://rs.tdwg.org/dwc/terms/taxonID"];
        //     $v->taxonID         = str_ireplace("urn:lsid:marinespecies.org:taxname:", "", $v->taxonID);
        //     $v->vernacularName  = $rec["http://rs.tdwg.org/dwc/terms/vernacularName"];
        //     $v->source          = $rec["http://purl.org/dc/terms/source"];
        //     $v->language        = $rec["http://purl.org/dc/terms/language"];
        //     $v->isPreferredName = $rec["http://rs.gbif.org/terms/1.0/isPreferredName"];
        //     $this->archive_builder->write_object_to_file($v);
        // }
    }

    private function get_agents($records)
    {
        self::process_fields($records, "agent");
        // foreach($records as $rec)
        // {
        //     $r = new \eol_schema\Agent();
        //     $r->identifier      = (string) $rec["http://purl.org/dc/terms/identifier"];
        //     $r->term_name       = (string) $rec["http://xmlns.com/foaf/spec/#term_name"];
        //     $r->term_firstName  = (string) $rec["http://xmlns.com/foaf/spec/#term_firstName"];
        //     $r->term_familyName = (string) $rec["http://xmlns.com/foaf/spec/#term_familyName"];
        //     $r->agentRole       = (string) $rec["http://eol.org/schema/agent/agentRole"];
        //     $r->term_mbox       = (string) $rec["http://xmlns.com/foaf/spec/#term_mbox"];
        //     $r->term_homepage   = (string) $rec["http://xmlns.com/foaf/spec/#term_homepage"];
        //     $r->term_logo       = (string) $rec["http://xmlns.com/foaf/spec/#term_logo"];
        //     $r->term_currentProject = (string) $rec["http://xmlns.com/foaf/spec/#term_currentProject"];
        //     $r->organization        = (string) $rec["http://eol.org/schema/agent/organization"];
        //     $r->term_accountName    = (string) $rec["http://xmlns.com/foaf/spec/#term_accountName"];
        //     $r->term_openid         = (string) $rec["http://xmlns.com/foaf/spec/#term_openid"];
        //     $this->archive_builder->write_object_to_file($r);
        // }
    }
    
    private function get_references($records)
    {
        self::process_fields($records, "reference");
        // foreach($records as $rec)
        // {
        //     $r = new \eol_schema\Reference();
        //     $r->identifier      = (string) $rec["http://purl.org/dc/terms/identifier"];
        //     $r->publicationType = (string) $rec["http://eol.org/schema/reference/publicationType"];
        //     $r->full_reference  = (string) $rec["http://eol.org/schema/reference/full_reference"];
        //     $r->primaryTitle    = (string) $rec["http://eol.org/schema/reference/primaryTitle"];
        //     $r->title           = (string) $rec["http://purl.org/dc/terms/title"];
        //     $r->pages           = (string) $rec["http://purl.org/ontology/bibo/pages"];
        //     $r->pageStart       = (string) $rec["http://purl.org/ontology/bibo/pageStart"];
        //     $r->pageEnd         = (string) $rec["http://purl.org/ontology/bibo/pageEnd"];
        //     $r->volume          = (string) $rec["http://purl.org/ontology/bibo/volume"];
        //     $r->edition         = (string) $rec["http://purl.org/ontology/bibo/edition"];
        //     $r->publisher       = (string) $rec["http://purl.org/dc/terms/publisher"];
        //     $r->authorList      = (string) $rec["http://purl.org/ontology/bibo/authorList"];
        //     $r->editorList      = (string) $rec["http://purl.org/ontology/bibo/editorList"];
        //     $r->created         = (string) $rec["http://purl.org/dc/terms/created"];
        //     $r->language        = (string) $rec["http://purl.org/dc/terms/language"];
        //     $r->uri             = (string) $rec["http://purl.org/ontology/bibo/uri"];
        //     $r->doi             = (string) $rec["http://purl.org/ontology/bibo/doi"];
        //     $r->localityName    = (string) $rec["http://schemas.talis.com/2005/address/schema#localityName"];
        //     if(!isset($this->resource_reference_ids[$r->identifier]))
        //     {
        //        $this->resource_reference_ids[$r->identifier] = 1;
        //        $this->archive_builder->write_object_to_file($r);
        //     }
        // }
    }

    function check_parent_child_links($taxon_file = false)
    {
        // for MycoBank taxon.tab & WORMS taxon.txt This mybe differenct for other taxonomy files
        $taxonID_fld            = "taxonID";
        $parentNameUsageID_fld  = "parentNameUsageID";
        $scientificName_fld     = "scientificName";

        // start assigning values to array - $taxa array
        $taxa = array();
        if(!$taxon_file) $taxon_file = DOC_ROOT . "/temp/taxon.tab"; // or "taxon.txt";
        
        $i = 0;
        foreach(new FileIterator($taxon_file) as $line_number => $line)
        {
            $i++;
            if($line)
            {
                $line = explode("\t", $line);
                if($i == 1) // row is for headers - fields
                {
                    $fields = $line;
                    print_r($fields);
                }
                else
                {
                    $rec = array();
                    foreach($fields as $key => $field)
                    {
                        $rec[$field] = $line[$key];
                    }
                    
                    $taxon_id = $rec[$taxonID_fld];
                    $sciname = $rec[$scientificName_fld];
                    $parent_id = $rec[$parentNameUsageID_fld];

                    $taxa[$taxon_id]["sciname"] = $sciname;
                    $taxa[$taxon_id]["parent_id"] = $parent_id;
                }

            }
        }
        
        // loop to $taxa array and find which one doesn't have a legit parent
        $parent_ids_without_entry = array();
        foreach($taxa as $taxon_id => $rec)
        {
            // echo "\n $taxon_id \n";
            // print_r($rec); exit;

            if($parent_id = $rec["parent_id"])
            {
                if(!isset($taxa[$parent_id]["sciname"]))
                {
                    // echo "\n" . $rec["sciname"];
                    $parent_ids_without_entry[$parent_id] = '';
                }
            }
        }
        // print_r($parent_ids_without_entry);
        
        foreach(array_keys($parent_ids_without_entry) as $id)
        {
            echo "$id - ";
        }
        
    }

}
?>