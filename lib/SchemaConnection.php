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
    
    function get_resource()
    {
        return $this->resource;
    }
    
    function add_taxon($t)
    {
        $hierarchy_entry = HierarchyEntry::create_entries_for_taxon($t, $this->resource->hierarchy_id);
        if(@!$hierarchy_entry->id) return false;
        
        $this->resource->harvest_event->add_hierarchy_entry($hierarchy_entry, 'inserted');
        
        if(@$t['common_names'])
        {
            $hierarchy_entry->delete_common_names();
            foreach($t['common_names'] as &$c)
            {
                $name_id = Name::insert($c['common_name']);
                $hierarchy_entry->add_synonym($name_id, SynonymRelation::insert('common name'), $c['language_id'], 0);
            }
        }
        if(@$t['synonyms'])
        {
            $hierarchy_entry->delete_synonyms();
            foreach($t['synonyms'] as &$s)
            {
                $hierarchy_entry->add_synonym($s->name_id, $s->synonym_relation_id, 0, 0);
            }
        }
        
        if(@$t['agents'])
        {
            $hierarchy_entry->delete_agents();
            $i = 0;
            foreach($t['agents'] as &$a)
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
                
                $hierarchy_entry->add_agent($agent_id, $a->agent_role_id, $i);
                unset($a);
                $i++;
            }
        }
        
        if(@$t['refs'])
        {
            $hierarchy_entry->unpublish_refs();
            foreach($t['refs'] as &$r)
            {
                $reference = new Reference($r->id);
                if(@$reference->id)
                {
                    $hierarchy_entry->add_reference($reference->id);
                    $reference->publish();
                    foreach($r->identifiers as $i) $reference->add_ref_identifier($i->ref_identifier_type_id, $i->identifier);
                }
                unset($r);
            }
        }
        
        foreach($t['data_objects'] as &$d)
        {
            $this->add_data_object($hierarchy_entry, $d);
            unset($d);
        }
        
        return $hierarchy_entry;
    }
    
    function add_data_object($hierarchy_entry, $d)
    {
        // Add default values from resource
        if(@!$d->rights_statement && $this->resource->rights_statement) $d->rights_statement = $this->resource->rights_statement;
        if(@!$d->rights_holder && $this->resource->rights_holder) $d->rights_holder = $this->resource->rights_holder;
        if(@!$d->license_id && $this->resource->license_id) $d->license_id = $this->resource->license_id;
        if(@!$d->language_id && $this->resource->language_id) $d->language_id = $this->resource->language_id;
        
        
        list($data_object, $status) = DataObject::find_and_compare($this->resource, $d, $this->content_manager);
        if(@!$data_object->id) return false;
        
        $hierarchy_entry->add_data_object($data_object->id, $d);
        $this->resource->harvest_event->add_data_object($data_object, $status);
        
        if($status!="Reused")
        {
            $i = 0;
            $data_object->delete_agents();
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
            
            if(@$d->info_items_ids)
            {
                $data_object->delete_info_items();
                foreach($d->info_items_ids as &$id)
                {
                    $data_object->add_info_item($id);
                    unset($id);
                }
            }
            
            if(@$d->refs)
            {
                $data_object->unpublish_refs();
                foreach($d->refs as &$r)
                {
                    $reference = new Reference($r->id);
                    if(@$reference->id)
                    {
                        $data_object->add_reference($reference->id);
                        $reference->publish();
                        foreach($r->identifiers as $i) $reference->add_ref_identifier($i->ref_identifier_type_id, $i->identifier);
                    }
                    unset($r);
                }
            }
        }
    }
    
    static function force_wikipedia_taxon($t)
    {
        $wikipedia_resource = Resource::wikipedia();
        $last_wikipedia_harvest = new HarvestEvent($wikipedia_resource->most_recent_published_harvest_event_id());
        $content_manager = new ContentManager(false);
        
        $hierarchy_entry = HierarchyEntry::create_entries_for_taxon($t, $wikipedia_resource->hierarchy_id);
        if(@!$hierarchy_entry->id) return false;
        
        $last_wikipedia_harvest->add_hierarchy_entry($hierarchy_entry, 'inserted');
        
        foreach($t['data_objects'] as &$d)
        {
            list($data_object, $status) = DataObject::find_and_compare($wikipedia_resource, $d, $content_manager);
            if(@!$data_object->id) return false;
            
            $hierarchy_entry->add_data_object($data_object->id, $d);
            $last_wikipedia_harvest->add_data_object($data_object, $status);
            
            if(@$d->info_items_ids)
            {
                $data_object->delete_info_items();
                foreach($d->info_items_ids as &$id)
                {
                    $data_object->add_info_item($id);
                    unset($id);
                }
            }
            
            return array($hierarchy_entry, $data_object);
        }
        
        return false;
    }
}

?>