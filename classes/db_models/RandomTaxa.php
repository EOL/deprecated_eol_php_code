<?php

class RandomTaxon extends MysqlBase
{
    function __construct($param)
    {
        $this->table_name = Functions::class_name(__FILE__);
        parent::initialize($param);
        if(@!$this->id) return;
    }
    
    public function add_ref_identifier($type_id, $value)
    {
        $value = $this->mysqli->escape($value);
        $this->mysqli->insert("INSERT INTO ref_identifiers VALUES ($this->id,$type_id,'$value')");
    }
    
    static function insert($parameters)
    {
        if(!$parameters) return 0;
        
        if(get_class($parameters)=="RandomTaxon")
        {
            if($result = self::find_by_mock_object($parameters)) return $result;
            
            if(@!$parameters->taxon_concept_id) $parameters->taxon_concept_id = TaxonConcept::insert();
            return parent::insert_object_into($parameters, Functions::class_name(__FILE__));
        }
        
        if($result = self::find($parameters)) return $result;
        
        if(@!$parameters['taxon_concept_id']) $parameters['taxon_concept_id'] = TaxonConcept::insert();
        return parent::insert_fields_into($parameters, Functions::class_name(__FILE__));
    }
    
    static function find($string)
    {
        return parent::find_by("full_reference", $string, Functions::class_name(__FILE__));
    }
    
    static function find_by_mock_object($mock)
    {
        $search_object = clone $mock;
        
        return parent::find_by("taxon_concept_id", $string, Functions::class_name(__FILE__));
    }
}

?>