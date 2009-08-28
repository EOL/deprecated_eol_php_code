<?php

class SchemaConnection extends MysqlBase
{
    private $resource;
    private $content_manager;
    
    function __construct(&$resource)
    {
        parent::db_connect();
        
        $this->content_manager = new ContentManager(false);
        
        $this->resource =& $resource;
    }
    
    function add_taxon($t)
    {
        list($taxon, $status) = Taxon::find_and_compare($this->resource, $t);
        if(@!$taxon->id) return false;
        
        $this->resource->add_taxon($taxon->id, $t);
        $this->resource->harvest_event->add_taxon($taxon, $status);
        
        if($status!="Unchanged")
        {
            foreach($t->common_names as &$c)
            {
                $taxon->add_common_name($c);
                unset($c);
            }
                    
            foreach($t->refs as &$r)
            {
                $reference = new Reference($r->id);
                if(@$reference->id)
                {
                    $taxon->add_reference($reference->id);
                    foreach($r->identifiers as $i) $reference->add_ref_identifier($i->ref_identifier_type_id, $i->identifier);
                }
                unset($r);
            }
        }
        
        foreach($t->data_objects as &$d)
        {
            $this->add_data_object($taxon, $d);
            unset($d);
        }
        
        Tasks::update_taxon_concept_names($taxon->hierarchy_entry()->taxon_concept_id);
        
        return $taxon;
    }
    
    function add_data_object($taxon, $d)
    {
        // Add default values from resource
        if(@!$d->rights_statement && $this->resource->rights_statement) $d->rights_statement = $this->resource->rights_statement;
        if(@!$d->rights_holder && $this->resource->rights_holder) $d->rights_holder = $this->resource->rights_holder;
        if(@!$d->license_id && $this->resource->license_id) $d->license_id = $this->resource->license_id;
        if(@!$d->language_id && $this->resource->language_id) $d->language_id = $this->resource->language_id;
        
        
        list($data_object, $status) = DataObject::find_and_compare($this->resource, $d, $this->content_manager);
        if(@!$data_object->id) return false;
        
        $taxon->add_data_object($data_object->id, $d);
        $this->resource->harvest_event->add_data_object($data_object, $status);
        
        if($status!="Reused")
        {
            $i = 0;
            foreach($d->agents as &$a)
            {
                $agent_id = Agent::insert($a);
                
                $agent = new Agent($agent_id);
                if($agent->logo_url && !$agent->logo_cache_url)
                {
                    if($logo_cache_url = $this->content_manager->grab_file($agent->logo_url, 0, "partner"))
                    {
                        $agent->update_cache_url($logo_cache_url);
                    }
                }
                
                $data_object->add_agent($agent_id, $a->agent_role_id, $i);
                unset($a);
                $i++;
            }
            
            foreach($d->audience_ids as &$id)
            {
                $data_object->add_audience($id);
                unset($id);
            }
            
            foreach($d->info_items_ids as &$id)
            {
                $data_object->add_info_item($id);
                unset($id);
            }
            
            foreach($d->refs as &$r)
            {
                $reference = new Reference($r->id);
                if(@$reference->id)
                {
                    $data_object->add_reference($reference->id);
                    foreach($r->identifiers as $i) $reference->add_ref_identifier($i->ref_identifier_type_id, $i->identifier);
                }
                unset($r);
            }
        }
    }
    
    public static function print_taxon_xml($taxa)
    {
        header('Content-type: text/xml');
        
        echo self::get_taxon_xml($taxa);
    }
    
    public static function get_taxon_xml($taxa)
    {
        $xml = "<?xml version='1.0' encoding='utf-8' ?>\n";
        $xml .= "<response\n";
        $xml .= "  xmlns='http://www.eol.org/transfer/content/0.2'\n";
        $xml .= "  xmlns:xsd='http://www.w3.org/2001/XMLSchema'\n";
        $xml .= "  xmlns:dc='http://purl.org/dc/elements/1.1/'\n";
        $xml .= "  xmlns:dcterms='http://purl.org/dc/terms/'\n";
        $xml .= "  xmlns:geo='http://www.w3.org/2003/01/geo/wgs84_pos#'\n";
        $xml .= "  xmlns:dwc='http://rs.tdwg.org/dwc/dwcore/'\n";
        $xml .= "  xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance'\n";
        $xml .= "  xsi:schemaLocation='http://www.eol.org/transfer/content/0.2 http://services.eol.org/schema/content_0_2.xsd'>\n";
        
        foreach($taxa as $t)
        {
            $xml .= $t->__toXML();
            //echo $t;
        }
        
        $xml .= "</response>";
        
        return $xml;
    }
}

?>