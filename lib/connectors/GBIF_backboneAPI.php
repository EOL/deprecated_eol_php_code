<?php
namespace php_active_record;
// connector: [gbif_backbone.php]
class GBIF_backboneAPI
{
    function __construct($folder)
    {
        $this->resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->download_options = array('cache' => 1, 'resource_id' => $folder, 'download_wait_time' => 500000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1, 
        'expire_seconds' => 60*60*24*10); //cache expires in 10 days // orig
        // $this->download_options['expire_seconds'] = false; //debug

        if(Functions::is_production()) {
            $this->service["backbone_dwca"] = "http://rs.gbif.org/datasets/backbone/backbone-current.zip";
        }
        else {
            $this->service["backbone_dwca"] = "http://localhost/cp/GBIF_Backbone_Archive/backbone-current.zip";
        }
    }
    private function access_dwca()
    {
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($this->service["backbone_dwca"], "meta.xml", array('timeout' => 172800, 'expire_seconds' => true)); //true means it will re-download, will not use cache. Set TRUE when developing
        print_r($paths); exit;
        
        $archive_path = $paths['archive_path'];
        $temp_dir = $paths['temp_dir'];
        return $archive_path;
    }
    function start()
    {
        $archive_path = self::access_dwca();
        
        $harvester = new ContentArchiveReader(NULL, $archive_path);
        $tables = $harvester->tables;
        if(!($this->fields["taxa"] = $tables["http://rs.tdwg.org/dwc/terms/taxon"][0]->fields)) // take note the index key is all lower case
        {
            debug("Invalid archive file. Program will terminate.");
            return false;
        }
        // remove temp dir
        recursive_rmdir($temp_dir);
        echo ("\n temporary directory removed: " . $temp_dir);
    }
    private function create_taxon_archive($a)
    {
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonomicStatus          = self::compute_taxonomicStatus($a);
        $taxon->taxonID                  = self::compute_taxonID($a, $taxon->taxonomicStatus);
        $taxon->scientificName           = $a[$this->map['scientificName']];
        $taxon->scientificNameAuthorship = @$a[$this->map['scientificNameAuthorship']];
        $taxon->taxonRank                = self::compute_taxonRank($a);
        $taxon->acceptedNameUsageID      = self::numerical_part(@$a[$this->map['acceptedNameUsageID']]);
        $this->archive_builder->write_object_to_file($taxon);
    }
}
?>