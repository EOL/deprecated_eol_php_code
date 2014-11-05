<?php
namespace php_active_record;
/* connector: [520] India Biodiversity Portal archive connector
*/
class IndiaBiodiversityPortalAPI
{
    function __construct($folder)
    {
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->taxon_ids = array();
        $this->object_ids = array();
        $this->dwca_file = "http://localhost/~eolit/cp/India Biodiversity Portal/520.tar.gz";
        $this->dwca_file = "https://dl.dropboxusercontent.com/u/7597512/India Biodiversity Portal/520.tar.gz";
        $this->taxon_page = "http://www.marinespecies.org/aphia.php?p=taxdetails&id=";
        $this->accessURI = array();
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
        self::create_instances_from_taxon_object($harvester->process_row_type('http://rs.tdwg.org/dwc/terms/Taxon')); //ok
        self::get_objects($harvester->process_row_type('http://eol.org/schema/media/Document')); //ok
        self::get_references($harvester->process_row_type('http://eol.org/schema/reference/Reference')); //ok
        self::get_agents($harvester->process_row_type('http://eol.org/schema/agent/Agent')); //ok
        self::get_vernaculars($harvester->process_row_type('http://rs.gbif.org/terms/1.0/VernacularName')); //ok
        $this->archive_builder->finalize(TRUE);
        // remove temp dir
        recursive_rmdir($temp_dir);
        echo ("\n temporary directory removed: " . $temp_dir);
    }

    private function process_fields($records, $class)
    {
        foreach($records as $rec)
        {
            if    ($class == "vernacular") $c = new \eol_schema\VernacularName();
            elseif($class == "agent")      $c = new \eol_schema\Agent();
            elseif($class == "reference")  $c = new \eol_schema\Reference();
            elseif($class == "objects")    $c = new \eol_schema\MediaResource();
            elseif($class == "taxa")       $c = new \eol_schema\Taxon();
            
            $keys = array_keys($rec);
            $r = array();
            foreach($keys as $key)
            {
                $temp = pathinfo($key);
                $field = $temp["basename"];
                
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
            if($class == "objects")
            {
                if($r["UsageTerms"] == "http://creativecommons.org/licenses/by-nc-nd/3.0/")     $save = false;
                if($r["description"] == "" && $r["type"] == "http://purl.org/dc/dcmitype/Text") $save = false;
                if(!$r["description"] && $r["type"] == "http://purl.org/dc/dcmitype/Text")      $save = false;
                if($r["type"] == "http://purl.org/dc/dcmitype/StillImage")
                {
                    $access_uri = $r["accessURI"];
                    if(isset($this->accessURI[$access_uri])) $save = false;
                    else $this->accessURI[$access_uri] = '';
                }
            }
            if($save) $this->archive_builder->write_object_to_file($c);
        }
    }

    private function create_instances_from_taxon_object($records)
    {
        self::process_fields($records, "taxa");
    }

    private function get_objects($records)
    {
        self::process_fields($records, "objects");
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

}
?>