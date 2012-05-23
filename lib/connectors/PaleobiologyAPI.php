<?php
namespace php_active_record;

class PaleobiologyAPI
{
    function __construct()
    {
        $this->data_dump_url = "http://testpaleodb.geology.wisc.edu/taxa/all.xml?type=synonyms&limit=250000&showref=1&showcode=1&suffix=.xml";
        $this->taxa = array();
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/paleobiology_working/';
    }
    
    function get_all_taxa()
    {
        // get the parsed xml
        $response = self::parse_xml();
        foreach($response->Taxon as $t)
        {
            $this->create_instances_from_taxon_object($t);
        }
        
        // finalize the process and create the archive
        $this->create_archive();
    }
    
    function create_instances_from_taxon_object($taxon_object)
    {
        $taxon = new \eol_schema\Taxon();
        $taxon_id = (int)$taxon_object->taxonID;
        $rank = (string)$taxon_object->taxonRank;
        
        $taxon->taxonID                     = $taxon_id;
        $taxon->taxonRank                   = $rank == 'unranked clade' ? null : $rank;
        $taxon->scientificName              = (string)$taxon_object->scientificName;
        $taxon->scientificNameAuthorship    = (string)$taxon_object->scientificNameAuthorship;
        $taxon->vernacularName              = (string)$taxon_object->vernacularName;
        $taxon->genus                       = (string)$taxon_object->genus;
        $taxon->subgenus                    = (string)$taxon_object->subgenus;
        $taxon->specificEpithet             = (string)$taxon_object->specificEpithet;
        $taxon->taxonomicStatus             = (string)$taxon_object->taxonomicStatus;
        $taxon->nomenclaturalCode           = (string)$taxon_object->nomenclaturalCode;
        $taxon->nomenclaturalStatus         = (string)$taxon_object->nomenclaturalStatus;
        $taxon->acceptedNameUsage           = (string)$taxon_object->acceptedNameUsage;
        $taxon->acceptedNameUsageID         = (int)$taxon_object->acceptedNameUsageID;
        $taxon->parentNameUsageID           = (int)$taxon_object->parentNameUsageID;
        $taxon->namePublishedIn             = (string)$taxon_object->namePublishedIn;
        $taxon->taxonRemarks                = (string)$taxon_object->taxonRemarks;
        $taxon->infraspecificEpithet        = (string)$taxon_object->infraSpecificEpithet;

        $this->taxa[$taxon_id] = $taxon;
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
    
    private function parse_xml()
    {
        $paleobiology_xml = file_get_contents($this->data_dump_url);
        $paleobiology_xml = preg_replace('/xmlns[^=]*="[^"]*"/i', '', $paleobiology_xml); // This removes ALL default namespaces.
        $xml = @simplexml_load_string($paleobiology_xml);
        return $xml;
    }
}
?>