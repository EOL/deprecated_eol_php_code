<?php
namespace php_active_record;
/* connector: [332, 345] 3I Interactive archive connector
We received Darwincore archive files from the partner.
Connector downloads the archive file, extracts, reads it, assembles the data and generates the EOL DWC-A resource.
*/
class I3InteractiveAPI
{
    function __construct($params)
    {
        $this->params = $params;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $params["resource_id"] . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->taxon_ids = array();
        $this->object_ids = array();
        $this->dwca_file = $params["dwca_file"];
        $this->occurrence_ids = array();
        $this->uri_mappings_spreadsheet = "http://localhost/~eolit/cp/NMNH/type_specimen_resource/nmnh mappings.xlsx";
        $this->uri_mappings_spreadsheet = "https://dl.dropboxusercontent.com/u/7597512/NMNH/type_specimen_resource/nmnh mappings.xlsx";
        $this->debug = array();
    }

    function get_all_taxa()
    {
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($this->dwca_file, "meta.xml");
        $archive_path = $paths['archive_path'];
        $harvester = new ContentArchiveReader(NULL, $archive_path);
        $tables = $harvester->tables;
        if(!($this->fields["taxa"] = $tables["http://rs.tdwg.org/dwc/terms/taxon"][0]->fields)) // take note the index key is all lower case
        {
            debug("Invalid archive file. Program will terminate.");
            return false;
        }
        if($records = $harvester->process_row_type('http://rs.gbif.org/terms/1.0/Reference')) self::get_references($records);
        if($records = $harvester->process_row_type('http://rs.tdwg.org/dwc/terms/Taxon'))
        {
            $taxa_id_list = self::get_taxa_id_list($records);
            self::create_instances_from_taxon_object($records, $taxa_id_list);
        }
        if($this->params["process occurrence"])
        {
            echo "\nProcessed OCCURRENCE\n";
            if($records = $harvester->process_row_type('http://rs.tdwg.org/dwc/terms/Occurrence'))
            {
                $this->uris = self::get_uris();
                self::get_occurrences($records);
            }
        }
        if($records = $harvester->process_row_type('http://rs.gbif.org/terms/1.0/Distribution'))    self::get_distributions($records);
        if($records = $harvester->process_row_type('http://rs.gbif.org/terms/1.0/Image'))           self::get_images($records);         //http://eol.org/content_partners/159/resources/345
        if($records = $harvester->process_row_type('http://rs.gbif.org/terms/1.0/Description'))     self::get_descriptions($records);   //http://eol.org/content_partners/159/resources/332
        if($records = $harvester->process_row_type('http://rs.gbif.org/terms/1.0/VernacularName'))  self::get_vernaculars($records);
        $this->archive_builder->finalize(TRUE);
        // remove temp dir
        recursive_rmdir($paths['temp_dir']);
        echo ("\n temporary directory removed: " . $paths['temp_dir']);
        print_r($this->debug);
    }

    private function get_taxa_id_list($records)
    {
        $taxa = array();
        foreach($records as $rec) $taxa[(string) $rec["http://rs.tdwg.org/dwc/terms/taxonID"]] = '';
        return $taxa;
    }
    
    private function create_instances_from_taxon_object($records, $taxa_id_list)
    {
        foreach($records as $rec)
        {
            $rec = array_map('trim', $rec);
            $taxon = new \eol_schema\Taxon();
            $taxon->taxonID                     = (string) $rec["http://rs.tdwg.org/dwc/terms/taxonID"];
            $taxon->scientificName              = utf8_encode((string) $rec["http://rs.tdwg.org/dwc/terms/scientificName"]);
            $taxon->scientificNameAuthorship    = (string) $rec["http://rs.tdwg.org/dwc/terms/scientificNameAuthorship"];
            $taxon->furtherInformationURL       = (string) $rec["http://purl.org/dc/terms/source"];

            $acceptedNameUsageID = (string) $rec["http://rs.tdwg.org/dwc/terms/acceptedNameUsageID"];
            if(isset($taxa_id_list[$acceptedNameUsageID])) $taxon->acceptedNameUsageID = $acceptedNameUsageID;
            else echo "\nthis acceptedNameUsageID does not exist [$acceptedNameUsageID]\n";

			if($taxon->acceptedNameUsageID == $taxon->taxonID) $taxon->acceptedNameUsageID = '';

            /* decided to comment this since collection is truncated, possible prob. is a missing node in hierarchy
            $parentNameUsageID = (string) $rec["http://rs.tdwg.org/dwc/terms/parentNameUsageID"];
            if(isset($taxa_id_list[$parentNameUsageID])) $taxon->parentNameUsageID = $parentNameUsageID;
            else echo "\nthis parentNameUsageID does not exist [$parentNameUsageID]\n";
            
            $originalNameUsageID = (string) $rec["http://rs.tdwg.org/dwc/terms/originalNameUsageID"];
            if(isset($taxa_id_list[$originalNameUsageID])) $taxon->originalNameUsageID = $originalNameUsageID;
            else echo "\nthis originalNameUsageID does not exist [$originalNameUsageID]\n";
            */
            
            $taxon->taxonRank                   = (string) $rec["http://rs.tdwg.org/dwc/terms/taxonRank"];
            $taxon->taxonRank                   = trim(str_ireplace("group", "", $taxon->taxonRank));
            $taxon->taxonomicStatus             = (string) $rec["http://rs.tdwg.org/dwc/terms/taxonomicStatus"];
            $taxon->nomenclaturalStatus         = (string) $rec["http://rs.tdwg.org/dwc/terms/nomenclaturalStatus"];
            $taxon->nomenclaturalCode           = (string) $rec["http://rs.tdwg.org/dwc/terms/nomenclaturalCode"];
            $taxon->datasetName                 = (string) $rec["http://rs.tdwg.org/dwc/terms/datasetName"];
            if($arr = @$this->taxon_references[$taxon->taxonID]) $taxon->referenceID = implode(";", $arr);
            if(!isset($this->taxon_ids[$taxon->taxonID]))
            {
                $this->taxon_ids[$taxon->taxonID] = '';
                $this->archive_builder->write_object_to_file($taxon);
            }
            $this->scinames[$taxon->taxonID] = $taxon->scientificName;
        }
    }

    private function get_occurrences($records) //TypeSpecimenRepository implementation
    {
        $i = 0;
        foreach($records as $rec)
        {
            $rec["taxon_id"] = $rec["http://rs.tdwg.org/dwc/terms/taxonID"];
            $rec["catnum"] = $rec["http://rs.tdwg.org/dwc/terms/eventID"];
            /*
            [http://rs.tdwg.org/dwc/terms/taxonID] => 1917
            [http://rs.tdwg.org/dwc/terms/eventID] => 1893
            [http://rs.tdwg.org/dwc/terms/samplingProtocol] => 
            [http://rs.tdwg.org/dwc/terms/eventDate] => 1951-6-1
            [http://rs.tdwg.org/dwc/terms/typeStatus] => 
            [http://rs.tdwg.org/dwc/terms/country] => U.S.A.
            [http://rs.tdwg.org/dwc/terms/stateProvince] => Florida
            [http://rs.tdwg.org/dwc/terms/county] => 
            [http://rs.tdwg.org/dwc/terms/locality] => Sarasota
            [http://rs.tdwg.org/dwc/terms/habitat] => 
            [http://rs.tdwg.org/dwc/terms/verbatimElevation] => 
            [http://rs.tdwg.org/dwc/terms/locationAccordingTo] => 
            [http://rs.tdwg.org/dwc/terms/decimalLatitude] => 
            [http://rs.tdwg.org/dwc/terms/decimalLongitude] => 
            [http://rs.tdwg.org/dwc/terms/coordinateUncertaintyInMeters] => 
            [http://rs.tdwg.org/dwc/terms/catalogNumber] => 
            [http://rs.tdwg.org/dwc/terms/recordedBy] => O. Bryant
            [http://rs.tdwg.org/dwc/terms/individualCount] => 1
            [http://rs.tdwg.org/dwc/terms/sex] => 
            [http://rs.tdwg.org/dwc/terms/associatedTaxa] => 
            [http://purl.org/dc/terms/language] => en
            [http://rs.tdwg.org/dwc/terms/institutionCode] => CAS
            [http://rs.tdwg.org/dwc/terms/datasetName] => 3i - Cicadellinae Database
            [http://purl.org/dc/terms/source] => http://takiya.speciesfile.org/taxahelp.asp?hc=1917&key=Proconia&lng=En
            [http://rs.tdwg.org/dwc/terms/basisOfRecord] => Preserved Specimen
            [http://rs.tdwg.org/dwc/terms/associatedSequences] => 
            */
            
            if($val = $rec["http://purl.org/dc/terms/source"])
            {
                if(substr($val, 0, 5) == "http:") $rec["source"] = $val;
            }
            
            $measurementRemarks = "";
            $institution_uri = false; $typeStatus_uri = false;
            if($val = @$rec["http://rs.tdwg.org/dwc/terms/institutionCode"]) $institution_uri = self::get_uri($val, "institutionCode");
            if($val = $rec["http://rs.tdwg.org/dwc/terms/typeStatus"])      $typeStatus_uri  = self::get_uri($val, "typeStatus");
            if($institution_uri)
            {
                self::add_string_types($rec, $institution_uri, "http://eol.org/schema/terms/TypeSpecimenRepository", "true", $measurementRemarks);
                if($typeStatus_uri)                                                 self::add_string_types($rec, $typeStatus_uri, "http://rs.tdwg.org/dwc/terms/typeStatus");
                if($val = $rec["http://rs.tdwg.org/dwc/terms/institutionCode"])     self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/institutionCode");
                if($val = $rec["http://rs.tdwg.org/dwc/terms/locality"])            self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/locality");
                if($val = $rec["http://rs.tdwg.org/dwc/terms/typeStatus"])          self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/typeStatus");
                if($val = $rec["http://rs.tdwg.org/dwc/terms/country"])             self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/country");
                if($val = $rec["http://rs.tdwg.org/dwc/terms/stateProvince"])       self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/stateProvince");
                if($val = $rec["http://rs.tdwg.org/dwc/terms/locationAccordingTo"]) self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/locationAccordingTo");
                if($val = $rec["http://rs.tdwg.org/dwc/terms/coordinateUncertaintyInMeters"]) self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/coordinateUncertaintyInMeters");
                if($val = $rec["http://purl.org/dc/terms/language"])                self::add_string_types($rec, $val, "http://purl.org/dc/terms/language");
                if($val = $rec["http://rs.tdwg.org/dwc/terms/datasetName"])         self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/datasetName");
                if($val = $rec["http://purl.org/dc/terms/source"])                  self::add_string_types($rec, $val, "http://purl.org/dc/terms/source");
                if($val = $rec["http://rs.tdwg.org/dwc/terms/basisOfRecord"])       self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/basisOfRecord");
                if($val = $rec["http://rs.tdwg.org/dwc/terms/county"])              self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/county");
                if($val = $rec["http://rs.tdwg.org/dwc/terms/associatedTaxa"])      self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/associatedTaxa");
                if($val = $rec["http://rs.tdwg.org/dwc/terms/habitat"])             self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/habitat");
                if($val = $rec["http://rs.tdwg.org/dwc/terms/associatedSequences"]) self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/associatedSequences");
            }
            // else
            // {
            //     echo "\ninvalid rec \n";
            //     print_r($rec);
            // }
            $i++;
            // if($i >= 20) break; //debug - just first 1000 records during preview phase
        }
        
    }

    /* working but not yet used
    private function get_occurrences_presense($records) // 'present' structured data
    {
        foreach($records as $rec)
        {
            $rec["taxon_id"] = $rec["http://rs.tdwg.org/dwc/terms/taxonID"];
            $region = "";
            if($val = $rec["http://rs.tdwg.org/dwc/terms/country"]) $region = $val;
            if($val = $rec["http://rs.tdwg.org/dwc/terms/stateProvince"]) $region .= " - " . $val;
            $rec["catnum"] = str_replace("-", "", $region);
            $rec["catnum"] = str_replace("  ", "_", $rec["catnum"]);
            
            //exclude USA or NY -- should be more than 3 chars
            $temp = trim($rec["http://rs.tdwg.org/dwc/terms/country"].$rec["http://rs.tdwg.org/dwc/terms/stateProvince"]);
            if(strlen($temp) <= 3) continue;
            echo "\n[$temp]";
            
            if($val = @$rec["http://purl.org/dc/terms/source"]) $rec["source"] = $val;
            if($arr = @$this->taxon_references[$rec["taxon_id"]]) $rec["reference_id"] = implode(";", $arr);
            if($val = $region) //e.g. 'Canada - Quebec'
            {
                                                              // self::add_string_types($rec, $val, "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution", "true");
                                                              self::add_string_types($rec, $val, "http://eol.org/schema/terms/Present", "true");
                if($val = @$this->scinames[$rec["taxon_id"]]) self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/scientificName");
                else
                {
                    // echo("\nno sciname1:" . $rec["taxon_id"] . "\n"); print_r($rec);
                    continue;
                }
                if($val = $rec["http://rs.tdwg.org/dwc/terms/eventID"])             self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/eventID");
                if($val = $rec["http://rs.tdwg.org/dwc/terms/samplingProtocol"])    self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/samplingProtocol");
                if($val = $rec["http://rs.tdwg.org/dwc/terms/eventDate"])           self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/eventDate");
                if($val = $rec["http://rs.tdwg.org/dwc/terms/typeStatus"])          self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/typeStatus");
                if($val = $rec["http://rs.tdwg.org/dwc/terms/country"])             self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/country");
                if($val = $rec["http://rs.tdwg.org/dwc/terms/stateProvince"])       self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/stateProvince");
                if($val = $rec["http://rs.tdwg.org/dwc/terms/county"])              self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/county");
                if($val = $rec["http://rs.tdwg.org/dwc/terms/locality"])            self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/locality");
                if($val = $rec["http://rs.tdwg.org/dwc/terms/habitat"])             self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/habitat");
                if($val = $rec["http://rs.tdwg.org/dwc/terms/verbatimElevation"])   self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/verbatimElevation");
                if($val = @$rec["http://rs.tdwg.org/dwc/terms/locationAccordingTo"]) self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/locationAccordingTo");
                if($val = @$rec["http://rs.tdwg.org/dwc/terms/decimalLatitude"])     self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/decimalLatitude");
                if($val = @$rec["http://rs.tdwg.org/dwc/terms/decimalLongitude"])    self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/decimalLongitude");
                if($val = @$rec["http://rs.tdwg.org/dwc/terms/coordinateUncertaintyInMeters"]) self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/coordinateUncertaintyInMeters");
                if($val = @$rec["http://rs.tdwg.org/dwc/terms/catalogNumber"])       self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/catalogNumber");
                if($val = @$rec["http://rs.tdwg.org/dwc/terms/recordedBy"])          self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/recordedBy");
                if($val = @$rec["http://rs.tdwg.org/dwc/terms/individualCount"])     self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/individualCount");
                if($val = @$rec["http://rs.tdwg.org/dwc/terms/sex"])                 self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/sex");
                if($val = @$rec["http://rs.tdwg.org/dwc/terms/associatedTaxa"])      self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/associatedTaxa");
                if($val = @$rec["http://purl.org/dc/terms/language"])                self::add_string_types($rec, $val, "http://purl.org/dc/terms/language");
                if($val = @$rec["http://rs.tdwg.org/dwc/terms/institutionCode"])     self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/institutionCode");
                if($val = @$rec["http://rs.tdwg.org/dwc/terms/datasetName"])         self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/datasetName");
                if($val = @$rec["http://rs.tdwg.org/dwc/terms/basisOfRecord"])       self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/basisOfRecord");
                if($val = @$rec["http://rs.tdwg.org/dwc/terms/associatedSequences"]) self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/associatedSequences");
            }
        }
    }
    */
    
    private function get_distributions($records)
    {
        foreach($records as $rec)
        {
            $rec["taxon_id"] = $rec["http://rs.tdwg.org/dwc/terms/taxonID"];
            $rec["catnum"]   = @$rec["http://rs.tdwg.org/dwc/terms/locality"];
            
            if($val = @$rec["http://purl.org/dc/terms/source"]) $rec["reference_id"] = self::prepare_ref_id($val); // this is the 'full_reference' being passed here
            if(!@$rec["reference_id"])
            {
                if($arr = @$this->taxon_references[$rec["taxon_id"]]) $rec["reference_id"] = implode(";", $arr);
            }
            
            if($val = @$rec["http://rs.tdwg.org/dwc/terms/locality"]) //e.g. 'China'
            {
                if(strlen($val) <= 3) continue;
                                                              // self::add_string_types($rec, $val, "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution", "true");
                                                              self::add_string_types($rec, $val, "http://eol.org/schema/terms/Present", "true");
                if($val = @$this->scinames[$rec["taxon_id"]]) self::add_string_types($rec, $val, "http://rs.tdwg.org/dwc/terms/scientificName");
                else exit("\nno sciname2:" . $rec["taxon_id"] . "\n");
            }
        }
    }

    private function prepare_ref_id($full_reference)
    {
        $r = new \eol_schema\Reference();
        $r->identifier      = md5($full_reference);
        $r->full_reference  = $full_reference;
        if(!isset($this->resource_reference_ids[$r->full_reference]))
        {
           $this->resource_reference_ids[$r->full_reference] = $r->identifier;
           $this->archive_builder->write_object_to_file($r);
           return $r->identifier;
        }
        else return $this->resource_reference_ids[$r->full_reference];
    }
    
    private function get_descriptions($records)
    {
        //not used
        // [http://purl.org/dc/terms/audience] => experts, general public
        // [http://purl.org/dc/terms/type] => morphology
        
        foreach($records as $rec)
        {
            $desc = $rec["http://purl.org/dc/terms/description"];
            if    (is_numeric(stripos($desc, "<b>Head</b>:")))          $subject = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Morphology";
            elseif(is_numeric(stripos($desc, "<b>Distribution</b>:")))  $subject = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution";
            else continue;
            
            $identifier = md5($rec["http://rs.tdwg.org/dwc/terms/taxonID"].$rec["http://purl.org/dc/terms/description"]);
            
            $mr = new \eol_schema\MediaResource();
            $mr->taxonID        = $rec["http://rs.tdwg.org/dwc/terms/taxonID"];
            $mr->identifier     = $identifier;
            $mr->description    = $desc;
            $mr->furtherInformationURL = @$rec["http://purl.org/dc/terms/source"];
            $mr->creator        = @$rec["http://purl.org/dc/terms/creator"];
            $mr->Owner          = @$rec["http://purl.org/dc/terms/creator"];
            $mr->language       = @$rec["http://purl.org/dc/terms/language"];
            if($val = self::get_license(@$rec["http://purl.org/dc/terms/license"])) $mr->UsageTerms = $val;
            else continue;
            $mr->format         = "text/html";
            $mr->type           = "http://purl.org/dc/dcmitype/Text";
            $mr->CVterm         = $subject;

            // $mr->audience       = (string) $rec["http://purl.org/dc/terms/audience"];
            // $mr->contributor    = (string) $rec["http://purl.org/dc/terms/contributor"];
            // if($agentID = (string) $rec["http://eol.org/schema/agent/agentID"])
            // {
            //     $ids = explode(",", $agentID); // not sure yet what separator Worms used, comma or semicolon - or if there are any
            //     $agent_ids = array();
            //     foreach($ids as $id) $agent_ids[] = $id;
            //     $mr->agentID = implode("; ", $agent_ids);
            // }
            // if($referenceID = self::prepare_reference((string) $rec["http://eol.org/schema/reference/referenceID"])) $mr->referenceID = $referenceID;

            if(!isset($this->object_ids[$mr->identifier]))
            {
                $this->object_ids[$mr->identifier] = '';
                $this->archive_builder->write_object_to_file($mr);
            }
        }
    }

    private function get_images($records)
    {
        foreach($records as $rec)
        {
            /* not used
            [http://purl.org/dc/terms/format] => jpeg
            [http://purl.org/dc/terms/audience] => experts, general public
            */
            
            $mr = new \eol_schema\MediaResource();
            $mr->taxonID        = $rec["http://rs.tdwg.org/dwc/terms/taxonID"];
            $mr->identifier     = $rec["http://purl.org/dc/terms/identifier"];
            $mr->type           = "http://purl.org/dc/dcmitype/StillImage";
            $mr->title          = (string) @$rec["http://purl.org/dc/terms/title"];
            $mr->description    = (string) @$rec["http://purl.org/dc/terms/description"];
            $mr->accessURI      = $rec["http://purl.org/dc/terms/identifier"];
            
            if($val = Functions::get_mimetype($mr->accessURI)) $mr->format = $val;
            else continue;
            
            if($val = self::get_license(@$rec["http://purl.org/dc/terms/license"])) $mr->UsageTerms = $val;
            else continue;
            $mr->creator        = (string) @$rec["http://purl.org/dc/terms/creator"];
            $mr->publisher      = (string) @$rec["http://purl.org/dc/terms/publisher"];
            
            // $mr->subtype        = (string) $rec["http://rs.tdwg.org/audubon_core/subtype"];
            // $mr->Rating         = (string) $rec["http://ns.adobe.com/xap/1.0/Rating"];
            // $mr->audience       = (string) $rec["http://purl.org/dc/terms/audience"];
            // $mr->language       = (string) $rec["http://purl.org/dc/terms/language"];
            // $mr->CVterm         = (string) $rec["http://iptc.org/std/Iptc4xmpExt/1.0/xmlns/CVterm"];
            // $mr->CreateDate     = (string) $rec["http://ns.adobe.com/xap/1.0/CreateDate"];
            // $mr->modified       = (string) $rec["http://purl.org/dc/terms/modified"];
            // $mr->Owner          = (string) $rec["http://ns.adobe.com/xap/1.0/rights/Owner"];
            // $mr->rights         = (string) $rec["http://purl.org/dc/terms/rights"];
            // $mr->bibliographicCitation = (string) $rec["http://purl.org/dc/terms/bibliographicCitation"];
            // $mr->derivedFrom     = (string) $rec["http://rs.tdwg.org/ac/terms/derivedFrom"];
            // $mr->LocationCreated = (string) $rec["http://iptc.org/std/Iptc4xmpExt/1.0/xmlns/LocationCreated"];
            // $mr->spatial         = (string) $rec["http://purl.org/dc/terms/spatial"];
            // $mr->lat             = (string) $rec["http://www.w3.org/2003/01/geo/wgs84_pos#lat"];
            // $mr->long            = (string) $rec["http://www.w3.org/2003/01/geo/wgs84_pos#long"];
            // $mr->alt             = (string) $rec["http://www.w3.org/2003/01/geo/wgs84_pos#alt"];
            // $mr->contributor    = (string) $rec["http://purl.org/dc/terms/contributor"];
            // if($agentID = (string) $rec["http://eol.org/schema/agent/agentID"])
            // {
            //     $ids = explode(",", $agentID); // not sure yet what separator Worms used, comma or semicolon - or if there are any
            //     $agent_ids = array();
            //     foreach($ids as $id) $agent_ids[] = $id;
            //     $mr->agentID = implode("; ", $agent_ids);
            // }
            // if($referenceID = self::prepare_reference((string) $rec["http://eol.org/schema/reference/referenceID"])) $mr->referenceID = $referenceID;
            // $mr->thumbnailURL   = (string) $rec["http://eol.org/schema/media/thumbnailURL"];
            // if($source = (string) $rec["http://rs.tdwg.org/ac/terms/furtherInformationURL"]) $mr->furtherInformationURL = $source;

            if(!isset($this->object_ids[$mr->identifier]))
            {
                $this->object_ids[$mr->identifier] = '';
                $this->archive_builder->write_object_to_file($mr);
            }
        }
    }

    private function get_license($str)
    {
        // http://creativecommons.org/licenses/publicdomain/
        if($str == "CC-BY")             return "http://creativecommons.org/licenses/by/3.0/";
        elseif($str == "CC-BY-NC")      return "http://creativecommons.org/licenses/by-nc/3.0/";
        elseif($str == "CC-BY-SA")      return "http://creativecommons.org/licenses/by-sa/3.0/";
        elseif($str == "CC-BY-NC-SA")   return "http://creativecommons.org/licenses/by-nc-sa/3.0/";
        elseif($str == ""){}
        else exit("\nundefined license[$str]\n");
        return false;
    }

    private function add_string_types($rec, $value, $measurementType, $measurementOfTaxon = "")
    {
        $m = new \eol_schema\MeasurementOrFact();
        $occurrence_id = $this->add_occurrence($rec["taxon_id"], $rec["catnum"], $rec);
        $m->occurrenceID        = $occurrence_id;
        $m->measurementOfTaxon  = $measurementOfTaxon;
        $m->measurementType     = $measurementType;
        $m->measurementValue    = $value;
        
        if($measurementOfTaxon == "true")
        {   // so that measurementRemarks (and source, contributor, etc.) appears only once in the [measurement_or_fact.tab]
            $m->measurementRemarks      = '';
            $m->source                  = @$rec["source"];
            $m->bibliographicCitation   = '';
            $m->contributor             = '';
            if($referenceID = @$rec["reference_id"]) $m->referenceID = $referenceID;
        }
        $m->measurementMethod = '';
        $this->archive_builder->write_object_to_file($m);
    }

    private function add_occurrence($taxon_id, $catnum, $rec)
    {
        $occurrence_id = $taxon_id . '_' . $catnum;
        if(isset($this->occurrence_ids[$occurrence_id])) return $occurrence_id;
        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = $occurrence_id;
        $o->taxonID = $taxon_id;

        if($val = @$rec["http://rs.tdwg.org/dwc/terms/eventID"]) $o->eventID = $val;
        if($val = @$rec["http://rs.tdwg.org/dwc/terms/samplingProtocol"]) $o->samplingProtocol = $val;
        if($val = @$rec["http://rs.tdwg.org/dwc/terms/eventDate"]) $o->eventDate = $val;
        if($val = @$rec["http://rs.tdwg.org/dwc/terms/locality"]) $o->locality = $val;
        if($val = @$rec["http://rs.tdwg.org/dwc/terms/verbatimElevation"]) $o->verbatimElevation = $val;
        if($val = @$rec["http://rs.tdwg.org/dwc/terms/decimalLatitude"]) $o->decimalLatitude = $val;
        if($val = @$rec["http://rs.tdwg.org/dwc/terms/decimalLongitude"]) $o->decimalLongitude = $val;
        if($val = @$rec["http://rs.tdwg.org/dwc/terms/catalogNumber"]) $o->catalogNumber = $val;
        if($val = @$rec["http://rs.tdwg.org/dwc/terms/recordedBy"]) $o->recordedBy = $val;
        if($val = @$rec["http://rs.tdwg.org/dwc/terms/individualCount"]) $o->individualCount = $val;
        if($val = @$rec["http://rs.tdwg.org/dwc/terms/sex"]) $o->sex = $val;
        if($val = @$rec["http://rs.tdwg.org/dwc/terms/institutionCode"]) $o->institutionCode = $val;

        $this->archive_builder->write_object_to_file($o);
        $this->occurrence_ids[$occurrence_id] = '';
        return $occurrence_id;
    }

    private function get_vernaculars($records)
    {
        // self::process_fields($records, "vernacular");
        foreach($records as $rec)
        {
            $v = new \eol_schema\VernacularName();
            $v->taxonID         = $rec["http://rs.tdwg.org/dwc/terms/taxonID"];
            $v->vernacularName  = $rec["http://rs.tdwg.org/dwc/terms/vernacularName"];
            $v->language        = $rec["http://purl.org/dc/terms/language"];
            $this->archive_builder->write_object_to_file($v);
            // $v->source          = '';
            // $v->isPreferredName = '';
        }
    }

    private function get_references($records)
    {
        // self::process_fields($records, "reference");
        foreach($records as $rec)
        {
            if(@$rec["http://purl.org/dc/terms/identifier"] && @$rec["http://purl.org/dc/terms/title"])
            {
                $r = new \eol_schema\Reference();
                $r->identifier      = (string) @$rec["http://purl.org/dc/terms/identifier"];
                if(!$r->identifier) continue;
                $r->authorList      = (string) @$rec["http://purl.org/dc/terms/creator"];
                $r->created         = (string) @$rec["http://purl.org/dc/terms/date"];
                $r->primaryTitle    = (string) @$rec["http://purl.org/dc/terms/title"];
                $r->publicationType = (string) @$rec["http://purl.org/dc/terms/type"];
                $r->full_reference  = @$rec["http://purl.org/dc/terms/creator"] . " " .
                                      @$rec["http://purl.org/dc/terms/date"] . ", " .
                                      @$rec["http://purl.org/dc/terms/title"] . " " .
                                      @$rec["http://purl.org/dc/terms/source"];

                // $r->title           = (string) $rec["http://purl.org/dc/terms/title"];
                // $r->pages           = (string) $rec["http://purl.org/ontology/bibo/pages"];
                // $r->pageStart       = (string) $rec["http://purl.org/ontology/bibo/pageStart"];
                // $r->pageEnd         = (string) $rec["http://purl.org/ontology/bibo/pageEnd"];
                // $r->volume          = (string) $rec["http://purl.org/ontology/bibo/volume"];
                // $r->edition         = (string) $rec["http://purl.org/ontology/bibo/edition"];
                // $r->publisher       = (string) $rec["http://purl.org/dc/terms/publisher"];
                // $r->editorList      = (string) $rec["http://purl.org/ontology/bibo/editorList"];
                // $r->language        = (string) $rec["http://purl.org/dc/terms/language"];
                // $r->uri             = (string) $rec["http://purl.org/ontology/bibo/uri"];
                // $r->doi             = (string) $rec["http://purl.org/ontology/bibo/doi"];
                // $r->localityName    = (string) $rec["http://schemas.talis.com/2005/address/schema#localityName"];
                if(!isset($this->resource_reference_ids[$r->full_reference]))
                {
                   $this->resource_reference_ids[$r->full_reference] = $r->identifier;
                   $this->archive_builder->write_object_to_file($r);
                   // build taxa-reference list
                   $this->taxon_references[$rec["http://rs.tdwg.org/dwc/terms/taxonID"]][] = $r->identifier;
                }
            }
        }
    }

    private function get_uri($value, $field)
    {
        if(in_array($field, array("typeStatus"))) $value = trim(strtoupper($value));
        if($field == "typeStatus")
        {
            $value = str_ireplace("TYPES", "TYPE", $value);
            if($value == "LECOTYPE") $value = "LECTOTYPE";
            elseif($value == "TYPE-LOCALITY") $value = "TYPE";
        }
        if($val = @$this->uris[$value]) return $val;
        else
        {
            $this->debug["undefined"][$field][$value] = '';
            return $value;
        }
    }

    private function get_uris()
    {
        $params["dataset"]  = "GBIF";
        require_library('connectors/GBIFCountryTypeRecordAPI');
        $func = new GBIFCountryTypeRecordAPI("x");
        $uris = $func->get_uris($params, $this->uri_mappings_spreadsheet);
        return $uris;
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
    
    private function get_objects($records)
    {
        foreach($records as $rec)
        {
            $identifier = (string) $rec["http://purl.org/dc/terms/identifier"];
            $type       = (string) $rec["http://purl.org/dc/terms/type"];
            $rec["taxon_id"] = (string) $rec["http://rs.tdwg.org/dwc/terms/taxonID"];
            $rec["taxon_id"] = str_ireplace("urn:lsid:marinespecies.org:taxname:", "", $rec["taxon_id"]);
            $rec["catnum"] = "";
            if (strpos($identifier, "WoRMS:distribution:") !== false)
            {
                $rec["catnum"] = (string) $rec["http://purl.org/dc/terms/identifier"];
                // self::process_distribution($rec); removed as per DATA-1522
                $rec["catnum"] = str_ireplace("WoRMS:distribution:", "_", $rec["catnum"]);
                self::process_establishmentMeans_occurrenceStatus($rec); //DATA-1522
                continue;
            }
            $mr = new \eol_schema\MediaResource();
            $mr->taxonID        = $rec["taxon_id"];
            $mr->identifier     = $identifier;
            $mr->type           = $type;
            $mr->subtype        = (string) $rec["http://rs.tdwg.org/audubon_core/subtype"];
            $mr->Rating         = (string) $rec["http://ns.adobe.com/xap/1.0/Rating"];
            $mr->audience       = (string) $rec["http://purl.org/dc/terms/audience"];
            if($val = trim((string) $rec["http://purl.org/dc/terms/language"])) $mr->language = $val;
            else                                                                $mr->language = "en";
            $mr->format         = (string) $rec["http://purl.org/dc/terms/format"];
            $mr->title          = (string) $rec["http://purl.org/dc/terms/title"];
            $mr->CVterm         = (string) $rec["http://iptc.org/std/Iptc4xmpExt/1.0/xmlns/CVterm"];
            $mr->creator        = (string) $rec["http://purl.org/dc/terms/creator"];
            $mr->CreateDate     = (string) $rec["http://ns.adobe.com/xap/1.0/CreateDate"];
            $mr->modified       = (string) $rec["http://purl.org/dc/terms/modified"];
            $mr->Owner          = (string) $rec["http://ns.adobe.com/xap/1.0/rights/Owner"];
            $mr->rights         = (string) $rec["http://purl.org/dc/terms/rights"];
            $mr->UsageTerms     = (string) $rec["http://ns.adobe.com/xap/1.0/rights/UsageTerms"];
            $mr->description    = (string) $rec["http://purl.org/dc/terms/description"];
            $mr->bibliographicCitation = (string) $rec["http://purl.org/dc/terms/bibliographicCitation"];
            $mr->derivedFrom     = (string) $rec["http://rs.tdwg.org/ac/terms/derivedFrom"];
            $mr->LocationCreated = (string) $rec["http://iptc.org/std/Iptc4xmpExt/1.0/xmlns/LocationCreated"];
            $mr->spatial         = (string) $rec["http://purl.org/dc/terms/spatial"];
            $mr->lat             = (string) $rec["http://www.w3.org/2003/01/geo/wgs84_pos#lat"];
            $mr->long            = (string) $rec["http://www.w3.org/2003/01/geo/wgs84_pos#long"];
            $mr->alt             = (string) $rec["http://www.w3.org/2003/01/geo/wgs84_pos#alt"];
            $mr->publisher      = (string) $rec["http://purl.org/dc/terms/publisher"];
            $mr->contributor    = (string) $rec["http://purl.org/dc/terms/contributor"];
            $mr->creator        = (string) $rec["http://purl.org/dc/terms/creator"];
            if($agentID = (string) $rec["http://eol.org/schema/agent/agentID"])
            {
                $ids = explode(",", $agentID); // not sure yet what separator Worms used, comma or semicolon - or if there are any
                $agent_ids = array();
                foreach($ids as $id) $agent_ids[] = $id;
                $mr->agentID = implode("; ", $agent_ids);
            }
            if($referenceID = self::prepare_reference((string) $rec["http://eol.org/schema/reference/referenceID"])) $mr->referenceID = $referenceID;
            $mr->accessURI      = (string) $rec["http://rs.tdwg.org/ac/terms/accessURI"];
            $mr->thumbnailURL   = (string) $rec["http://eol.org/schema/media/thumbnailURL"];
            if($source = (string) $rec["http://rs.tdwg.org/ac/terms/furtherInformationURL"]) $mr->furtherInformationURL = $source;
            if(!isset($this->object_ids[$mr->identifier]))
            {
                $this->object_ids[$mr->identifier] = '';
                $this->archive_builder->write_object_to_file($mr);
            }
        }
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
    */

}
?>