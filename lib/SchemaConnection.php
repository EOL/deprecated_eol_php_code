<?php
namespace php_active_record;

class SchemaConnection
{
    private $resource;
    private $content_manager;
    
    function __construct(&$resource)
    {
        $this->mysqli =& $GLOBALS['db_connection'];
        $this->content_manager = new ContentManager();
        $this->resource =& $resource;
        $this->harvested_data_object_ids = array();
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
        
        $hierarchy_entry->delete_common_names();
        if(@$t['common_names'])
        {
            foreach($t['common_names'] as &$c)
            {
                $name = Name::find_or_create_by_string($c['name']);
                $hierarchy_entry->add_synonym($name->id, SynonymRelation::find_or_create_by_translated_label('common name')->id, @$c['language']->id ?: 0, 0);
            }
        }
        
        $hierarchy_entry->delete_synonyms();
        if(@$t['synonyms'])
        {
            foreach($t['synonyms'] as &$s)
            {
                $hierarchy_entry->add_synonym($s['name']->id, @$s['synonym_relation']->id ?: 0, 0, 0);
            }
        }
        
        $hierarchy_entry->delete_agents();
        if(@$t['agents'])
        {
            $i = 0;
            foreach($t['agents'] as &$a)
            {
                $agent = Agent::find_or_create($a);
                if($agent->logo_url && !$agent->logo_cache_url)
                {
                    if($logo_cache_url = $this->content_manager->grab_file($agent->logo_url, "partner"))
                    {
                        $agent->logo_cache_url = $logo_cache_url;
                        $agent->save();
                    }
                }
                
                $hierarchy_entry->add_agent($agent->id, @$a['agent_role']->id ?: 0, $i);
                unset($a);
                $i++;
            }
        }
        
        $hierarchy_entry->delete_refs();
        if(@$t['refs'])
        {
            foreach($t['refs'] as &$r)
            {
                if(@$r->id)
                {
                    $hierarchy_entry->add_reference($r->id);
                    $r->publish();
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
    
    function add_data_object($hierarchy_entry, $options)
    {
        $d = $options[0];
        $parameters = $options[1];
        // Add default values from resource
        if(@!$d->rights_statement && $this->resource->rights_statement) $d->rights_statement = $this->resource->rights_statement;
        if(@!$d->rights_holder && $this->resource->rights_holder) $d->rights_holder = $this->resource->rights_holder;
        if(@!$d->license_id && $this->resource->license_id) $d->license_id = $this->resource->license_id;
        if(@!$d->language_id && $this->resource->language_id) $d->language_id = $this->resource->language_id;
        
        // print_r($d);
        
        list($data_object, $status, $existing_data_object) = DataObject::find_and_compare($this->resource, $d, $this->content_manager);
        $GLOBALS['db_connection']->commit();
        if(@!$data_object->id) return false;
        
        $vetted_id = Vetted::unknown()->id;
        $visibility_id = Visibility::preview()->id;
        if($existing_data_object)
        {
            // if($existing_data_object && ($this->resource->title != "Wikipedia" || $status == "Unchanged") && $v = $existing_data_object->best_vetted())
            if($existing_data_object && $v = $existing_data_object->best_vetted())
            {
                $vetted_id = $v->id;
            }
            if($existing_data_object && $v = $existing_data_object->best_visibility())
            {
                if($v != Visibility::visible())
                {
                    // if the existing object is visible - this will go on as preview
                    // otherwise this will inherit the visibility (unpublished)
                    $visibility_id = $v->id;
                }
            }
        }
        
        // we only delete the object's entries the first time we see it, to allow for multiple taxa per object
        if(!isset($this->harvested_data_object_ids[$data_object->id])) $data_object->delete_hierarchy_entries();
        $this->harvested_data_object_ids[$data_object->id] = 1;
        
        $hierarchy_entry->add_data_object($data_object->id, $vetted_id, $visibility_id);
        $this->resource->harvest_event->add_data_object($data_object, $status);
        
        if($status!="Reused")
        {
            $i = 0;
            $data_object->delete_agents();
            foreach($parameters['agents'] as &$a)
            {
                $agent = Agent::find_or_create($a);
                if($agent->logo_url && !$agent->logo_cache_url)
                {
                    if($logo_cache_url = $this->content_manager->grab_file($agent->logo_url, "partner"))
                    {
                        $agent->logo_cache_url = $logo_cache_url;
                        $agent->save();
                    }
                }
                
                $data_object->add_agent($agent->id, @$a['agent_role']->id ?: 0, $i);
                unset($a);
                $i++;
            }
            
            $data_object->delete_audiences();
            foreach($parameters['audiences'] as &$a)
            {
                $data_object->add_audience($a->id);
                unset($a);
            }
            
            $data_object->delete_info_items();
            $data_object->delete_table_of_contents();
            if(@$parameters['info_items'])
            {
                foreach($parameters['info_items'] as &$ii)
                {
                    $data_object->add_info_item($ii->id);
                    unset($ii);
                }
            }
            
            $data_object->delete_refs();
            if(@$parameters['refs'])
            {
                foreach($parameters['refs'] as &$r)
                {
                    if(@$r->id)
                    {
                        $data_object->add_reference($r->id);
                        $r->publish();
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
        $content_manager = new ContentManager();
        
        $hierarchy_entry = HierarchyEntry::create_entries_for_taxon($t, $wikipedia_resource->hierarchy_id);
        if(@!$hierarchy_entry->id) return false;
        
        $last_wikipedia_harvest->add_hierarchy_entry($hierarchy_entry, 'inserted');
        
        foreach($t['data_objects'] as &$d)
        {
            list($data_object, $status) = DataObject::find_and_compare($wikipedia_resource, $d, $content_manager);
            if(@!$data_object->id) return false;
            
            $hierarchy_entry->add_data_object($data_object->id);
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
    
    static function add_translated_taxon($t, $resource)
    {
        foreach($t['data_objects'] as &$d)
        {
            self::add_translated_data_object($d, $resource);
            unset($d);
        }
        
        //return $hierarchy_entry;
    }
    
    static function add_translated_data_object($options, $resource)
    {
        $d = $options[0];
        $parameters = $options[1];
        if($d->EOLDataObjectID)
        {
            if($existing_data_object = DataObject::find($d->EOLDataObjectID))
            {
                $new_data_object = clone $existing_data_object;
                $new_data_object->id = NULL;
                $new_data_object->identifier = $d->identifier;
                $new_data_object->object_created_at = $d->object_created_at;
                $new_data_object->object_modified_at = $d->object_modified_at;
                $new_data_object->object_title = $d->object_title;
                $new_data_object->language = $d->language;
                $new_data_object->rights_statement = $d->rights_statement;
                $new_data_object->rights_holder = $d->rights_holder;
                $new_data_object->description = $d->description;
                $new_data_object->description_linked = null;
                $new_data_object->location = $d->location;
                // check to see if this translation exists by looking in data_object_translations
                // if its found, check to see if its different
                // otherwise add it, then add all associations (agents, references, hierarchy_entries)
                // then add new agents
                
                $content_manager = new ContentManager();
                list($data_object, $status) = DataObject::find_and_compare($resource, $new_data_object, $content_manager);
                if(@!$data_object->id) return false;
                
                self::add_entries_for_translated_object($existing_data_object, $data_object, $resource);
                $resource->harvest_event->add_data_object($data_object, $status);
                
                if($status!="Reused")
                {
                    $i = 0;
                    $data_object->delete_agents();
                    foreach($parameters['agents'] as &$a)
                    {
                        $agent = Agent::find_or_create($a);
                        if($agent->logo_url && !$agent->logo_cache_url)
                        {
                            if($logo_cache_url = $this->content_manager->grab_file($agent->logo_url, "partner"))
                            {
                                $agent->logo_cache_url = $logo_cache_url;
                                $agent->save();
                            }
                        }
                        
                        $data_object->add_agent($agent->id, @$a['agent_role']->id ?: 0, $i);
                        unset($a);
                        $i++;
                    }
                    $data_object->delete_translations();
                    $data_object->add_translation($existing_data_object->id, $data_object->language_id);
                    
                    $data_object->delete_info_items();
                    foreach($existing_data_object->info_items as $ii)
                    {
                        $data_object->add_info_item($ii->id);
                    }
                    
                    // audience
                    // info items
                    // table of contents
                    // all related tables....
                    
                }
            }else
            {
                echo "DataObject $d->EOLDataObjectID doesn't exist\n";
            }
        }else
        {
            return false;
        }
    }
    
    static function add_entries_for_translated_object($existing_data_object, $new_data_object, $resource)
    {
        $new_data_object->delete_hierarchy_entries();
        foreach($existing_data_object->hierarchy_entries as $he)
        {
            if($hierarchy_entry = self::lookup_existing_entry_and_ancestors($he, $resource->hierarchy_id))
            {
                $resource->harvest_event->add_hierarchy_entry($hierarchy_entry, 'inserted');
                $hierarchy_entry->add_data_object($new_data_object->id);
            }else return false;
        }
        return true;
    }
    
    static function lookup_existing_entry_and_ancestors($hierarchy_entry, $hierarchy_id)
    {
        $params = array();
        $params["name_id"] = $hierarchy_entry->name_id;
        $params["guid"] = $hierarchy_entry->guid;
        $params["hierarchy_id"] = $hierarchy_id;
        $params["rank_id"] = $hierarchy_entry->rank_id;
        $params["ancestry"] = $hierarchy_entry->ancestry;
        $params["taxon_concept_id"] = $hierarchy_entry->taxon_concept_id;
        $params["parent_id"] = 0;
        // $params["identifier"] = $taxon['identifier'];
        // $params["source_url"] = $taxon['source_url'];
        $params["visibility_id"] = Visibility::preview()->id;
        if($parent = $hierarchy_entry->parent())
        {
            if($parent_entry = self::lookup_existing_entry_and_ancestors($parent, $hierarchy_id))
            {
                $params["parent_id"] = $parent_entry->id;
            }else return false;
        }
        return HierarchyEntry::find_or_create_by_array($params);
    }
    
}

?>
