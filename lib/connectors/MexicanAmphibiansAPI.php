<?php
namespace php_active_record;
/* connectors: [856] Mexican Amphibians archive connector */
class MexicanAmphibiansAPI
{
    function __construct($folder, $params)
    {
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->dwca_file = $params["dwca_file"];
        
        $this->extensions["Occurrence"]         = "https://dl.dropboxusercontent.com/u/1355101/ontology/occurrence_extension.xml";
        $this->extensions["MeasurementOrFact"]  = "https://dl.dropboxusercontent.com/u/1355101/ontology/measurement_extension.xml";
        $this->extensions["Distribution"]       = "http://eol.org/schema/media_extension.xml";
        $this->extensions["Image"]              = "http://eol.org/schema/media_extension.xml";
        $this->extensions["VernacularName"]     = "http://rs.gbif.org/extension/gbif/1.0/vernacularname.xml";
        $this->extensions["Agent"]              = "http://eol.org/schema/media_agents.xml";
        $this->extensions["Reference"]          = "http://eol.org/schema/reference_extension.xml";
        
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
        
        $row_types = self::get_XML_fields($paths["temp_dir"] . "meta.xml", "rowType");

        /* manual assignment, use this if some referenceID in Measurements don't exist in References.tab
        $row_types = array("http://eol.org/schema/reference/Reference", "http://rs.tdwg.org/dwc/terms/Taxon", "http://rs.tdwg.org/dwc/terms/MeasurementOrFact", "http://rs.tdwg.org/dwc/terms/Occurrence");
        */
        
        print_r($row_types);
        foreach($row_types as $row_type)
        {
            $basename = pathinfo($row_type, PATHINFO_BASENAME);
            if($basename == "Taxon") $allowed_fields = array("taxonID", "scientificName", "parentNameUsageID", "kingdom", "phylum", "class", "order", "family", "genus", "taxonRank", "furtherInformationURL", "taxonomicStatus", "taxonRemarks", "namePublishedIn", "referenceID"); //based on page: http://eol.org/info/329
            else                     $allowed_fields = self::get_XML_fields($this->extensions[$basename], "property name");
            
            //manual adjustment
            if($row_type == "VernacularName") $allowed_fields[] = "taxonID";
            
            self::process_fields($harvester->process_row_type($row_type), $basename, $allowed_fields);
            // e.g. self::process_fields($harvester->process_row_type('http://rs.tdwg.org/dwc/terms/Taxon'), "Taxon");
        }
        
        $this->archive_builder->finalize(TRUE);
        recursive_rmdir($paths['temp_dir']); // remove temp dir
        echo ("\n temporary directory removed: " . $paths['temp_dir']);
        print_r($this->debug);
    }

    private function process_fields($records, $class, $allowed_fields)
    {
        foreach($records as $rec)
        {
            if    ($class == "VernacularName")      $c = new \eol_schema\VernacularName();
            elseif($class == "Agent")               $c = new \eol_schema\Agent();
            elseif($class == "Reference")           $c = new \eol_schema\Reference();
            elseif($class == "Taxon")               $c = new \eol_schema\Taxon();
            elseif($class == "MeasurementOrFact")   $c = new \eol_schema\MeasurementOrFact();
            elseif($class == "Occurrence")          $c = new \eol_schema\Occurrence();
            elseif($class == "Distribution")        $c = new \eol_schema\MediaResource();
            elseif($class == "Image")               $c = new \eol_schema\MediaResource();

            $keys = array_keys($rec);
            $save = true;
            foreach($keys as $key)
            {
                $temp = pathinfo($key);
                $field = $temp["basename"];

                /* resource specifications */
                // if($this->resource_id == "345") //3I Interactive resource
                // if(true)
                // {
                //     if($class == "Image" && $field == "license")            $field = "UsageTerms";
                //     if($class == "Distribution" && $field == "locality")    $field = "Description";
                // }
                /* end specifications */

                // manual adjustment bec. of a typo in meta.xml, without "s"
                if($field == "measurementRemark") $field = "measurementRemarks";

                /*
                // sample way to exclude if field is to be excluded
                if($field == "attribution") continue; //not recognized in eol: http://indiabiodiversity.org/terms/attribution
                */
                
                if(!in_array($field, $allowed_fields))
                {
                    $this->debug["undefined"][$class][$field] = '';
                    continue;
                }
                
                // some fields have '#', e.g. "http://schemas.talis.com/2005/address/schema#localityName"
                $parts = explode("#", $field);
                if($parts[0]) $field = $parts[0];
                if(@$parts[1]) $field = $parts[1];

                $value = trim((string) $rec[$key]);
                $value = trim(Functions::import_decode($value));
                if(!Functions::is_utf8($value)) $save = false;
                
                //special arrangement
                if($class == "Reference")
                {
                    if($field == "identifier") $this->reference_ids[$value] = '';
                    if($field == "full_reference")
                    {
                        if(!$value)
                        {
                            $full_ref = "";
                            if    ($val = (string) @$rec["http://eol.org/schema/reference/primaryTitle"])   $full_ref .= $val;
                            elseif($val = (string) @$rec["http://purl.org/dc/terms/title"])                 $full_ref .= $val;
                            if($val = (string) @$rec["http://purl.org/ontology/bibo/pageStart"])    $full_ref .= ". Page(s) " . $val;
                            if($val = (string) @$rec["http://purl.org/ontology/bibo/pageEnd"])      $full_ref .= " - " . $val;
                            if($val = (string) @$rec["http://purl.org/ontology/bibo/volume"])       $full_ref .= ". Vol. " . $val;
                            if($val = (string) @$rec["http://purl.org/dc/terms/publisher"])         $full_ref .= ". Publisher: " . $val;
                            if($val = (string) @$rec["http://purl.org/ontology/bibo/authorList"])   $full_ref .= ". Author: " . $val;
                            if($val = (string) @$rec["http://purl.org/ontology/bibo/editorList"])   $full_ref .= ". Editor: " . $val;
                            if($val = (string) @$rec["http://purl.org/dc/terms/created"])           $full_ref .= ". " . $val;
                            if($val = $full_ref) $value = $full_ref;
                            else echo " -- still blank";
                        }
                    }
                }
                
                // if($class == "MeasurementOrFact" && $field == "referenceID")
                // {
                //     if($value)
                //     {
                //         if(!isset($this->reference_ids[$value])) echo " -- undefined refid:[$value]";
                //     }
                // }
                
                if($value) $c->$field = $value;
            }
            /* if($class == "objects") {} // sample way to filter */
            if($save) $this->archive_builder->write_object_to_file($c);
        }
    }

    private function get_XML_fields($file, $field)
    {
        $file_content = Functions::lookup_with_cache($file);
        if(preg_match_all("/" . $field . "=\"(.*?)\"/ims", $file_content, $arr)) return $arr[1];
        elseif(preg_match_all("/" . $field . "=\'(.*?)\'/ims", $file_content, $arr)) return $arr[1];
        else echo "\nInvestigate: no [$field] found!\n";
    }

}
?>