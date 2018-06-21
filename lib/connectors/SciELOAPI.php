<?php
namespace php_active_record;
// connector: [487]
class SciELOAPI
{
    function __construct($folder)
    {
        // $this->data_dump_url = "http://localhost/cp_new/SciELO/Anacardiaceae XML v5 correcao.xml";
        $this->data_dump_url =                                            "http://localhost/cp_new/SciELO/Anacardiaceae V6-parentID.xml";
        $this->data_dump_url = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/SciELO/Anacardiaceae V6-parentID.xml";
        $this->taxa = array();
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->resource_reference_ids = array();
        $this->resource_agent_ids = array();
        $this->vernacular_name_ids = array();
        $this->taxon_ids = array();
        $this->SPM = 'http://rs.tdwg.org/ontology/voc/SPMInfoItems';
        $this->EOL = 'http://www.eol.org/voc/table_of_contents';
    }

    private function parse_xml()
    {
        // $scielo_xml = file_get_contents($this->data_dump_url);
        $scielo_xml = Functions::lookup_with_cache($this->data_dump_url);
        if($xml = @simplexml_load_string($scielo_xml)) return $xml;
        else exit("\n Problem with the XML file: $this->data_dump_url");
    }

    private function parse_record_element($record, $parent = null)
    {
        $sciname = $record->Name . " " . $record->Author;
        // print "\n" . " - " . $sciname . " - " . $record->ID;
        $reference_ids = self::get_taxon_references($record);
        $ref_ids = self::get_object_references($record);
        // $agent_ids = self::get_object_agents($record);
        $agent_ids = array();
        $this->create_instances_from_taxon_object($record, $reference_ids, $parent);
        self::get_vernacular_names($record);
        self::get_synonyms($record);
        if($distribution = self::get_distribution_all($record)) self::get_texts($distribution, $record, '', $this->SPM . '#Distribution', 'distribution', $ref_ids, $agent_ids);
        if($lifeform = self::get_lifeform($record)) self::get_texts($lifeform, $record, '', $this->SPM . '#Morphology', 'life_form', $ref_ids, $agent_ids);
        if($habitat = self::get_habitat_all($record)) self::get_texts($habitat, $record, '', $this->SPM . '#Habitat', 'habitat', $ref_ids, $agent_ids);
        // if($voucher = self::get_voucher($record)) self::get_texts($voucher, $record->ID, 'Voucher', $this->EOL . '#TypeInformation');
    }

    function get_all_taxa()
    {
        $xml = self::parse_xml();
        foreach($xml->row as $rec)
        {
            // print "\n" . $rec->Name;
            self::parse_record_element($rec);
        }
        $this->create_archive();
    }

    private function get_vernacular_names($obj)
    {
        if(!$obj->Vernacular_Names) return;
        // print "\n Vernacular_Names: " . $obj->Vernacular_Names;
        $vernaculars = explode(";", $obj->Vernacular_Names);
        foreach($vernaculars as $name)
        {
            $items = explode(",", $name);
            $items = array_map('trim', $items); //trims all array values in the array
            $common_name = @$items[0];
            if($common_name == '') continue;
            if(in_array("PORTUGUES", $items))   $language = "pt";
            elseif(in_array("Brasil", $items))  $language = "br";
            else                                $language = "";
            $vernacular = new \eol_schema\VernacularName();
            $vernacular->taxonID = $obj->ID;
            $vernacular->vernacularName = (string) $common_name;
            $vernacular->language = $language;
            $vernacular_id = md5("$vernacular->taxonID|$vernacular->vernacularName|$vernacular->language");
            if(!$vernacular->vernacularName) continue;
            if(!isset($this->vernacular_name_ids[$vernacular_id]))
            {
                $this->archive_builder->write_object_to_file($vernacular);
                $this->vernacular_name_ids[$vernacular_id] = 1;
            }
        }
    }

    private function get_synonyms($obj)
    {
        if(!$obj->Synonyms) return;
        // print "\n Synonyms: " . $obj->Synonyms;
        $synonyms = explode(",", $obj->Synonyms);
        foreach($synonyms as $name)
        {
            $synonym = new \eol_schema\Taxon();
            $synonym->scientificName = (string) trim($name);
            $synonym->acceptedNameUsageID = $obj->ID;
            $synonym->taxonomicStatus = 'synonym';
            $synonym->taxonID = md5("$obj->ID|$synonym->scientificName|$synonym->taxonomicStatus");
            if(!$synonym->scientificName) continue;
            if(!isset($this->taxon_ids[$synonym->taxonID]))
            {
                $this->archive_builder->write_object_to_file($synonym);
                $this->taxon_ids[$synonym->taxonID] = 1;
            }
        }
    }

    private function get_images($id, $taxonID)
    {
        $mr = new \eol_schema\MediaResource();
        $mr->taxonID = $taxonID;
        $mr->identifier = $id . "_image" . $id;
        $mr->title = '';
        $mr->type = 'http://purl.org/dc/dcmitype/StillImage';
        $mr->language = 'en';
        $mr->description = '';
        $mr->locality = '';
        $mr->creator = '';
        $mr->UsageTerms = 'http://creativecommons.org/licenses/publicdomain/';
        $this->archive_builder->write_object_to_file($mr);
    }

    private function get_taxon_references($obj)
    {
        if(!$obj->References) return;
        // print "\n ref: " . $obj->References;
        $reference_ids = array();
        $references_array = explode(";", $obj->References);
        $reference_ids = self::loop_references($references_array, $reference_ids);
        return $reference_ids;
    }

    private function get_object_references($obj)
    {
        $reference_ids = array();
        $references_array = array();
        // add Bibliography as taxon reference per Katja
        // but as Data object reference per Jose Grillo
        if($obj->Bibliography != '') $references_array[] = $obj->Bibliography;
        $reference_ids = self::loop_references($references_array, $reference_ids);
        return $reference_ids;
    }

    private function get_object_agents($obj)
    {
        $agent_ids = array();
        $agents_array = explode(";", $obj->Authors);
        foreach($agents_array as $agent)
        {
            $agent = (string)trim($agent);
            if(!$agent) continue;
            $r = new \eol_schema\Agent();
            $r->term_name = $agent;
            $r->identifier = md5("$agent|author");
            $r->agentRole = "author";
            $agent_ids[] = $r->identifier;
            if(!in_array($r->identifier, $this->resource_agent_ids)) 
            {
               $this->resource_agent_ids[] = $r->identifier;
               $this->archive_builder->write_object_to_file($r);
            }
        }
        return $agent_ids;
    }

    private function loop_references($references_array, $reference_ids)
    {
        foreach($references_array as $ref)
        {
            $ref = (string)trim($ref);
            if(!$ref) continue;
            $r = new \eol_schema\Reference();
            $r->full_reference = $ref;
            $r->identifier = md5($ref);
            $reference_ids[] = $r->identifier;
            if(!in_array($r->identifier, $this->resource_reference_ids)) 
            {
               $this->resource_reference_ids[] = $r->identifier;
               $this->archive_builder->write_object_to_file($r);
            }
        }
        return $reference_ids;
    }

    private function get_voucher($obj)
    {
        $description = '';
        if(!$obj->Voucher) return;
        $vouchers = explode(";", $obj->Voucher);
        foreach($vouchers as $voucher)
        {
            $description .= $voucher != '' ? "" . $voucher . "<br>" : '';
        }
        return $description;
    }

    private function get_origin($obj)
    {
        $origin = "";
        switch(trim($obj->Origin)) 
        {
            case 'Nativa':
                $origin = "Nativa do Brasil";
                break;
            case 'Cultivada':
                $origin = "Cultivada no Brasil";
                break;
            case 'Naturalizada':
                $origin = "Naturalizada no Brasil";
                break;
        }        
        return $origin;
    }

    private function get_endemic($obj)
    {
        $endemic = "";
        switch(trim($obj->Endemic)) 
        {
            case 'desconhecido':
                $endemic = "Desconhecido se endêmica do Brasil";
                break;
            case 'é endêmica do Brasil':
                $endemic = "É endêmica do Brasil";
                break;
            case 'não é endêmica do Brasil':
                $endemic = "Não é endêmica do Brasil";
                break;
        }        
        return $endemic;
    }

    private function get_occurrence($obj)
    {
        // $occurrence = '';
        // switch(trim($obj->Occurs_in_Brazil)) 
        // {
        //     case 'Sim':
        //         $occurrence = "Ocorre no Brasil";
        //         break;
        //     case 'Não ocorre no Brasil':
        //         $occurrence = "Não ocorre no Brasil";
        //         break;
        // }        
        // return $occurrence;
        if(trim($obj->Occurs_in_Brazil) != "") return "Ocorre no Brasil: " . $obj->Occurs_in_Brazil;
        else return;
    }

    private function get_habitat_all($record)
    {
        $habitat_all = "";
        if($substrate = self::get_substrate($record))           $habitat_all .= $substrate . ". ";
        if($phyto_domain = self::get_phyto($record))            $habitat_all .= $phyto_domain . ". ";
        if($vegetation_types = self::get_vegetation($record))   $habitat_all .= $vegetation_types . ". ";
        return $habitat_all;
    }

    private function get_distribution_all($record)
    {
        $distribution_all = "";
        if($origin = self::get_origin($record))             $distribution_all .= $origin . ". ";
        if($endemic = self::get_endemic($record))           $distribution_all .= $endemic . ". ";
        if($occurrence = self::get_occurrence($record))     $distribution_all .= $occurrence . ". ";
        if($distribution = self::get_distribution($record)) $distribution_all .= $distribution . ". ";
        return $distribution_all;
    }

    private function get_distribution($obj)
    {
        if($obj->Regional_Distribution != '' || $obj->State_Distribution != '')
        {
            return "Distribuição geográfica: $obj->Regional_Distribution ($obj->State_Distribution)";
        }
    }

    private function get_lifeform($obj)
    {
        return trim($obj->Life_Form)  != '' ? "Forma de vida: " . $obj->Life_Form : '';
    }

    private function get_substrate($obj)
    {
        return trim($obj->Substratum)  != '' ? "Substrato: " . $obj->Substratum : '';
    }

    private function get_phyto($obj)
    {
        return trim($obj->Phyto_Domain)  != '' ? "Domínios fitogeográficos: " . $obj->Phyto_Domain : '';
    }
    
    private function get_vegetation($obj)
    {
        return trim($obj->Vegetation_types)  != '' ? "Tipos de vegetação: " . $obj->Vegetation_types : '';
    }

    private function get_texts($description, $obj, $title, $subject, $code, $reference_ids = null, $agent_ids = null)
    {
        $taxon_id = $obj->ID;
        $mr = new \eol_schema\MediaResource();
        if($reference_ids) $mr->referenceID = implode("; ", $reference_ids);
        if($agent_ids) $mr->agentID = implode("; ", $agent_ids);
        $mr->taxonID = $taxon_id;
        $mr->identifier = $mr->taxonID . "_" . $code;
        $mr->type = 'http://purl.org/dc/dcmitype/Text';
        $mr->language = 'pt';
        $mr->format = 'text/html';
        $mr->furtherInformationURL = $obj->URL;
        $mr->description = $description;
        $mr->CVterm = $subject;
        $mr->title = '';
        $mr->creator = '';
        $mr->CreateDate = '';
        $mr->modified = $obj->Last_Update;
        $mr->UsageTerms = 'http://creativecommons.org/licenses/by-nc/3.0/';
        $mr->audience = 'Everyone';
        $mr->bibliographicCitation = $obj->Citation;
        // per Katja
        // $mr->Owner = 'Centro de Referência em Informação Ambiental, CRIA';
        // $mr->publisher = 'Centro de Referência em Informação Ambiental, CRIA';
        // per Jose Grillo
        $mr->Owner = 'Jardim Botânico do Rio de Janeiro';
        $mr->publisher = 'Jardim Botânico do Rio de Janeiro';
        $this->archive_builder->write_object_to_file($mr);
    }

    function create_instances_from_taxon_object($taxon_object, $reference_ids, $parent = null)
    {
        $taxon = new \eol_schema\Taxon();
        $taxon_id = (string) $taxon_object->ID;

        if($reference_ids) $taxon->referenceID = implode("; ", $reference_ids);
        $taxon->taxonID = $taxon_id;

        $scielo_rank["FAMILIA"] = 'family';
        $scielo_rank["GENERO"] = 'genus';
        $scielo_rank["ESPECIE"] = 'species';
        $scielo_rank["VARIEDADE"] = 'variety';

        $rank = trim($taxon_object->Rank);
        if($rank == "ESPECIE")
        {
            $temp = explode(" ", trim($taxon_object->Name));
            $genus = trim($temp[0]);
        }
        else $genus = "";

        $taxon->taxonRank                   = (string) @$scielo_rank[$rank];
        $taxon->scientificName              = (string) $taxon_object->Name;
        $taxon->scientificNameAuthorship    = (string) $taxon_object->Author;

        if($rank != "FAMILIA") $family = "Anacardiaceae";
        else $family = "";
        
        $taxon->family                      = (string) $family;
        $taxon->genus                       = (string) $genus;
        $taxon->specificEpithet             = '';
        
        $scielo_taxonStatus["Nome aceito"] = "accepted";
        $scielo_taxonStatus["Sinônimo"] = "synonym";
        
        $taxon->taxonomicStatus             = (string) @$scielo_taxonStatus[trim($taxon_object->Status)];
        $taxon->acceptedNameUsage           = (string) $taxon_object->Status == 'Nome aceito' ? $taxon_object->Name : ''; //'Accepted name'
        $taxon->acceptedNameUsageID         = '';
        $taxon->parentNameUsageID           = (string) $taxon_object->_Parent_ID;
        $taxonRemarks = '';
        $taxonRemarks .= $taxon_object->Qualifier != '' ? "Name qualifier: " . $taxon_object->Qualifier . ". " : '';
        $taxonRemarks .= $taxon_object->Author != '' ? "Author name: " . $taxon_object->Author . ". " : '';
        $taxonRemarks .= $taxon_object->Status != '' ? "Status: " . $taxon_object->Status . ". " : '';
        $taxon->taxonRemarks                = (string) $taxonRemarks;
        $this->taxa[$taxon_id] = $taxon;
    }

    function create_archive()
    {
        foreach($this->taxa as $t)
        {
            $this->archive_builder->write_object_to_file($t);
        }
        $this->archive_builder->finalize(true);
    }

}
?>