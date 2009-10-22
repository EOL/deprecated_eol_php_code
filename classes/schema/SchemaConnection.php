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
        
        if($status!="Reused")
        {
            if($he = $taxon->hierarchy_entry())
            {
                if(@$t->common_names)
                {
                    $he->delete_common_names();
                    foreach($t->common_names as &$c)
                    {
                        $name_id = Name::insert($c->common_name);
                        $he->add_synonym($name_id, SynonymRelation::insert('common name'), $c->language_id, 0);
                    }
                }
                if(@$t->synonyms)
                {
                    $he->delete_synonyms();
                    foreach($t->synonyms as &$s)
                    {
                        $he->add_synonym($s->name_id, $s->synonym_relation_id, 0, 0);
                    }
                }
                
                if(@$t->agents)
                {
                    $he->delete_agents();
                    $i = 0;
                    foreach($t->agents as &$a)
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
                        
                        $he->add_agent($agent_id, $a->agent_role_id, $i);
                        unset($a);
                        $i++;
                    }
                }
            }
            
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
}

?>