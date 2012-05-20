<?php
namespace php_active_record;

class PaleobiologyAPI
{
    function __construct()
    {
        $this->data_dump_url = "http://testpaleodb.geology.wisc.edu/taxa/all.xml?type=synonyms&limit=50&showref=1&showcode=1&suffix=.xml";
        $this->taxa = array();
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/paleobiology_working/';
    }
    
    function get_all_taxa()
    {
        // download the dump file to temporary file on the server
        $filepath = self::download_resource_data();
        
        // get the parsed xml
        $response = self::parse_xml($filepath);

        foreach($response->Taxon as $t)
        {
            $taxon = new \eol_schema\Taxon();
            $taxon_id = (int)$t->taxonID;
            $rank = (string)$t->taxonRank;
            
            $taxon->taxonID                     = $taxon_id;
            $taxon->taxonRank                   = $rank == 'unranked clade' ? null : $rank;
            $taxon->scientificName              = (string)$t->scientificName;
            $taxon->scientificNameAuthorship    = (string)$t->scientificNameAuthorship;
            $taxon->vernacularName              = (string)$t->vernacularName;
            $taxon->genus                       = (string)$t->genus;
            $taxon->subgenus                    = (string)$t->subgenus;
            $taxon->specificEpithet             = (string)$t->specificEpithet;
            $taxon->taxonomicStatus             = (string)$t->taxonomicStatus;
            $taxon->nomenclaturalCode           = (string)$t->nomenclaturalCode;
            $taxon->nomenclaturalStatus         = (string)$t->nomenclaturalStatus;
            $taxon->acceptedNameUsage           = (string)$t->acceptedNameUsage;
            $taxon->acceptedNameUsageID         = (int)$t->acceptedNameUsageID;
            $taxon->parentNameUsageID           = (int)$t->parentNameUsageID;
            $taxon->namePublishedIn             = (string)$t->namePublishedIn;
            $taxon->taxonRemarks                = (string)$t->taxonRemarks;

            $this->taxa[$taxon_id] = $taxon;
        }
        
        // finalize the process and create the archive
        $this->create_archive();
    }
    
    function create_archive()
    {
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        foreach($this->taxa as $t)
        {
            $this->archive_builder->write_object_to_file($t);
        }
        $this->archive_builder->finalize();
    }
    
    private function download_resource_data()
    {
        $paleobiology_data_path = DOC_ROOT . "/update_resources/connectors/files/paleobiology.xml";
        $paleobiology_data = Functions::get_remote_file($this->data_dump_url, NULL, 300);
        
        $OUT = fopen($paleobiology_data_path, "w+");
        fwrite($OUT, $paleobiology_data);
        fclose($OUT);
        
        return $paleobiology_data_path;
    }
    
    private function parse_xml($url)
    {
        $arr_data=array();
        $paleobiology_xml = file_get_contents($this->data_dump_url);
        $paleobiology_xml = preg_replace('/xmlns[^=]*="[^"]*"/i', '', $paleobiology_xml); // This removes ALL default namespaces.
        $xml = @simplexml_load_string($paleobiology_xml);
        return $xml;
    }
}
?>