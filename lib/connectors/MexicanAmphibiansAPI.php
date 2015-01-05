<?php
namespace php_active_record;
/* connector: [856] Mexican Amphibians archive connector */
class MexicanAmphibiansAPI
{
    function __construct($folder)
    {
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->dwca_file = "http://localhost/~eolit/cp/MexicanAmphibians/Mex_Amph.zip"; // raw from Anne Thessen
        $this->dwca_file = "http://localhost/~eolit/cp/MexicanAmphibians/Mex_Amph/Archive.zip"; //adjusted meta XML
        $this->dwca_file = "https://dl.dropboxusercontent.com/u/7597512/MexicanAmphibians/Archive.zip";
    }

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
        self::process_fields($harvester->process_row_type('http://rs.tdwg.org/dwc/terms/Taxon'), "taxa");
        self::process_fields($harvester->process_row_type('http://rs.tdwg.org/dwc/terms/MeasurementOrFact'), "measurements");
        self::process_fields($harvester->process_row_type('http://rs.tdwg.org/dwc/terms/Occurrence'), "occurrences");
        $this->archive_builder->finalize(TRUE);
        recursive_rmdir($temp_dir); // remove temp dir
        echo ("\n temporary directory removed: " . $temp_dir);
    }

    private function process_fields($records, $class)
    {
        foreach($records as $rec)
        {
            if    ($class == "vernacular")      $c = new \eol_schema\VernacularName();
            elseif($class == "agent")           $c = new \eol_schema\Agent();
            elseif($class == "reference")       $c = new \eol_schema\Reference();
            elseif($class == "objects")         $c = new \eol_schema\MediaResource();
            elseif($class == "taxa")            $c = new \eol_schema\Taxon();
            elseif($class == "measurements")    $c = new \eol_schema\MeasurementOrFact();
            elseif($class == "occurrences")     $c = new \eol_schema\Occurrence();
            
            $keys = array_keys($rec);
            $r = array();
            foreach($keys as $key)
            {
                $temp = pathinfo($key);
                $field = $temp["basename"];

                // sample way to exclude if field is to be excluded
                if($field == "attribution") continue; //not recognized in eol: http://indiabiodiversity.org/terms/attribution
                
                // some fields have '#', e.g. "http://schemas.talis.com/2005/address/schema#localityName"
                $parts = explode("#", $field);
                if($parts[0]) $field = $parts[0];
                if(@$parts[1]) $field = $parts[1];

                $value = (string) $rec[$key];
                // echo "\n[$field] -- [" . $value . "]";
                $r[$field] = $value;
                $c->$field = $value;
            }
            $save = true;
            if($class == "objects") {} // sample way to filter
            if($save) $this->archive_builder->write_object_to_file($c);
        }
    }

}
?>