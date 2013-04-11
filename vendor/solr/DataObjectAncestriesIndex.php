<?php
namespace php_active_record;

class DataObjectAncestriesIndexer
{
    private $mysqli;
    private $mysqli_slave;
    private $solr;
    
    public function __construct()
    {
        $this->mysqli =& $GLOBALS['db_connection'];
        if($GLOBALS['ENV_NAME'] == 'production' && environment_defined('slave')) $this->mysqli_slave = load_mysql_environment('slave');
        else $this->mysqli_slave =& $this->mysqli;
        $this->solr = new SolrAPI(SOLR_SERVER, 'data_objects');
        
        $this->vetted_field_label_prefixes = array(
            Vetted::trusted()->id => 'trusted_',
            Vetted::unknown()->id => 'unreviewed_',
            Vetted::untrusted()->id => 'untrusted_',
            Vetted::inappropriate()->id => 'inappropriate_');
        $this->visibility_field_label_prefixes = array(
            Visibility::invisible()->id => 'invisible_',
            Visibility::visible()->id => 'visible_',
            Visibility::preview()->id => 'preview_');
    }
    
    public function index_all_data_data_objects()
    {
        // $this->solr->delete_all_documents();
        $limit = 500000;
        $start = $this->mysqli->select_value("SELECT MIN(id) FROM data_objects");
        $max_id = $this->mysqli->select_value("SELECT MAX(id) FROM data_objects");
        for($i=$start ; $i<$max_id ; $i+=$limit)
        {
            $upper_range = $i + $limit - 1;
            if($upper_range > $max_id) $upper_range = $max_id;
            $data_object_ids = range($i, $upper_range);
            $this->index_data_objects($data_object_ids);
        }
        $this->solr->commit_objects_in_file();
    }
    
    public function index_data_objects(&$data_object_ids = array())
    {
        if(!$data_object_ids) return;
        $batches = array_chunk($data_object_ids, 10000);
        foreach($batches as $batch)
        {
            $this->index_batch($batch);
        }
    }
    
    private function index_batch(&$data_object_ids)
    {
        if(!$data_object_ids) return;
        unset($this->objects);
        static $num_batch = 0;
        $num_batch++;
        if($GLOBALS['ENV_DEBUG']) echo "Looking up $num_batch Time: ". time_elapsed()." .. Mem: ". memory_get_usage() ."\n";
        $this->lookup_objects($data_object_ids);
        if($GLOBALS['ENV_DEBUG']) echo "after DO Time: ". time_elapsed()." .. Mem: ". memory_get_usage() ."\n";
        if(isset($this->objects))
        {
            $lookup_ids = array_keys($this->objects);
            $this->lookup_ancestries($lookup_ids);
            if($GLOBALS['ENV_DEBUG']) echo "after ancestries Time: ". time_elapsed()." .. Mem: ". memory_get_usage() ."\n";
            $this->lookup_curated_ancestries($lookup_ids);
            if($GLOBALS['ENV_DEBUG']) echo "after c_a Time: ". time_elapsed()." .. Mem: ". memory_get_usage() ."\n";
            $this->lookup_user_added_ancestries($lookup_ids);
            if($GLOBALS['ENV_DEBUG']) echo "after uaa Time: ". time_elapsed()." .. Mem: ". memory_get_usage() ."\n";
            $this->lookup_ignores($lookup_ids);
            if($GLOBALS['ENV_DEBUG']) echo "after ignores Time: ". time_elapsed()." .. Mem: ". memory_get_usage() ."\n";
            $this->lookup_curation($lookup_ids);
            if($GLOBALS['ENV_DEBUG']) echo "after curation Time: ". time_elapsed()." .. Mem: ". memory_get_usage() ."\n";
            $this->lookup_resources($lookup_ids);
            if($GLOBALS['ENV_DEBUG']) echo "after resources Time: ". time_elapsed()." .. Mem: ". memory_get_usage() ."\n";
            $this->lookup_table_of_contents($lookup_ids);
            if($GLOBALS['ENV_DEBUG']) echo "after toc Time: ". time_elapsed()." .. Mem: ". memory_get_usage() ."\n";
            $this->lookup_translations($lookup_ids);
            if($GLOBALS['ENV_DEBUG']) echo "after translations Time: ". time_elapsed()." .. Mem: ". memory_get_usage() ."\n";
            
            foreach($this->objects as $id => $attr)
            {
                if(isset($attr['trusted_ancestor_id'])) $this->objects[$id]['max_vetted_weight'] = 5;
                elseif(isset($attr['unreviewed_ancestor_id'])) $this->objects[$id]['max_vetted_weight'] = 4;
                elseif(isset($attr['untrusted_ancestor_id'])) $this->objects[$id]['max_vetted_weight'] = 3;
                elseif(isset($attr['inappropriate_ancestor_id'])) $this->objects[$id]['max_vetted_weight'] = 2;
                else $this->objects[$id]['max_vetted_weight'] = 1;
                
                if(isset($attr['visible_ancestor_id'])) $this->objects[$id]['max_visibility_weight'] = 4;
                elseif(isset($attr['invisible_ancestor_id'])) $this->objects[$id]['max_visibility_weight'] = 3;
                elseif(isset($attr['preview_ancestor_id'])) $this->objects[$id]['max_visibility_weight'] = 2;
                else $this->objects[$id]['max_visibility_weight'] = 1;
            }
        }
        
        $this->solr->delete_by_ids($data_object_ids, false);
        if(isset($this->objects)) $this->solr->send_attributes_in_bulk($this->objects);
        $this->solr->commit();
    }
    
    
    private function lookup_objects(&$data_object_ids = array())
    {
        $last_data_object_id = 0;
        $query = "
            SELECT do.id, do.guid, do.data_type_id, do.data_subtype_id, do.language_id, do.license_id, do.published, do.data_rating,
                UNIX_TIMESTAMP(do.created_at), he.id, he.taxon_concept_id, dohe.vetted_id, dohe.visibility_id,
                cuhe.id, cuhe.taxon_concept_id, cudohe.vetted_id, cudohe.visibility_id,
                udo.taxon_concept_id, udo.vetted_id, udo.visibility_id, udo.user_id
            FROM data_objects do
            LEFT JOIN
                (data_objects_hierarchy_entries dohe JOIN hierarchy_entries he ON (dohe.hierarchy_entry_id=he.id))
                ON (do.id=dohe.data_object_id)
            LEFT JOIN
                (curated_data_objects_hierarchy_entries cudohe JOIN hierarchy_entries cuhe ON (cudohe.hierarchy_entry_id=cuhe.id))
                ON (do.id=cudohe.data_object_id)
            LEFT JOIN users_data_objects udo ON (do.id=udo.data_object_id)
            WHERE (do.published=1 OR dohe.visibility_id=".Visibility::preview()->id.")
            AND do.id IN (". implode(",", $data_object_ids) .")";
        
        foreach($this->mysqli_slave->iterate_file($query) as $row_num => $row)
        {
            $id = $row[0];
            // first time we got this object, so svae its metadata
            if($id != $last_data_object_id)
            {
                $guid = $row[1];
                $data_type_id = $row[2];
                $data_subtype_id = $row[3];
                $language_id = $row[4];
                $license_id = $row[5];
                $published = $row[6];
                $data_rating = $row[7];
                $created_at = $row[8] ?: 1;  // setting the default created_at to 1969-12-31T07:00:01Z
                if($data_subtype_id == 'NULL') $data_subtype_id = 0;
                if($language_id == 'NULL') $language_id = 0;
                if($license_id == 'NULL') $license_id = 0;
                
                $this->objects[$id]['guid'] = $guid;
                $this->objects[$id]['data_type_id'] = $data_type_id;
                $this->objects[$id]['data_subtype_id'] = $data_subtype_id;
                $this->objects[$id]['language_id'] = $language_id;
                $this->objects[$id]['license_id'] = $license_id;
                $this->objects[$id]['published'] = $published;
                $this->objects[$id]['data_rating'] = $data_rating;
                $this->objects[$id]['created_at'] = SolrAPI::mysql_date_to_solr_date($created_at);
            }
            
            // HierarchyEntry block
            $hierarchy_entry_id = $row[9];
            $taxon_concept_id = $row[10];
            $vetted_id = $row[11];
            $visibility_id = $row[12];
            $this->add_object_association($id, $hierarchy_entry_id, $taxon_concept_id, $vetted_id, $visibility_id, NULL, 'HierarchyEntry');
            
            // CuratedHierarchyEntry block
            $hierarchy_entry_id = $row[13];
            $taxon_concept_id = $row[14];
            $vetted_id = $row[15];
            $visibility_id = $row[16];
            $this->add_object_association($id, $hierarchy_entry_id, $taxon_concept_id, $vetted_id, $visibility_id, NULL, 'HierarchyEntry');
            
            // UsersDataObject block
            $taxon_concept_id = $row[17];
            $vetted_id = $row[18];
            $visibility_id = $row[19];
            $user_id = $row[20];
            $this->add_object_association($id, NULL, $taxon_concept_id, $vetted_id, $visibility_id, $user_id, 'HierarchyEntry');
            
            $last_data_object_id = $id;
        }
    }
    
    private function add_object_association($data_object_id, $hierarchy_entry_id, $taxon_concept_id, $vetted_id, $visibility_id, $user_id, $type)
    {
        if($taxon_concept_id && $taxon_concept_id != 'NULL')
        {
            $this->objects[$data_object_id]['taxon_concept_id'][$taxon_concept_id] = 1;
            if($hierarchy_entry_id) $this->objects[$data_object_id]['hierarchy_entry_id'][$hierarchy_entry_id] = 1;
            if($user_id) $this->objects[$data_object_id]['added_by_user_id'] = $user_id;
            if($field_prefix = @$this->vetted_field_label_prefixes[$vetted_id])
            {
                $this->objects[$data_object_id][$field_prefix."taxon_concept_id"][$taxon_concept_id] = 1;
            }
            if($field_prefix = @$this->visibility_field_label_prefixes[$visibility_id])
            {
                $this->objects[$data_object_id][$field_prefix."taxon_concept_id"][$taxon_concept_id] = 1;
            }
        }
    }
    
    private function lookup_ancestries(&$data_object_ids)
    {
        $query = "
            SELECT do.id, he.taxon_concept_id, dohe.vetted_id, dohe.visibility_id, tcf.ancestor_id
            FROM data_objects do
            JOIN data_objects_hierarchy_entries dohe ON (do.id=dohe.data_object_id)
            JOIN hierarchy_entries he ON (dohe.hierarchy_entry_id=he.id)
            LEFT JOIN taxon_concepts_flattened tcf ON (he.taxon_concept_id=tcf.taxon_concept_id)
            WHERE (do.published=1 OR dohe.visibility_id!=".Visibility::visible()->id.")
            AND do.id IN (". implode(",", $data_object_ids) .")";
        $this->add_ancestries_from_result($query, 'ancestor_id');
    }
    
    private function lookup_curated_ancestries(&$data_object_ids)
    {
        $query = "
            SELECT do.id, he.taxon_concept_id, cudohe.vetted_id, cudohe.visibility_id, tcf.ancestor_id
            FROM data_objects do
            JOIN curated_data_objects_hierarchy_entries cudohe ON (do.id=cudohe.data_object_id)
            JOIN hierarchy_entries he ON (cudohe.hierarchy_entry_id=he.id)
            LEFT JOIN taxon_concepts_flattened tcf ON (he.taxon_concept_id=tcf.taxon_concept_id)
            WHERE (do.published=1 OR cudohe.visibility_id!=".Visibility::visible()->id.")
            AND do.id IN (". implode(",", $data_object_ids) .")";
        $this->add_ancestries_from_result($query, 'ancestor_id');
    }
    
    private function lookup_user_added_ancestries(&$data_object_ids)
    {
        $query = "
            SELECT udo.data_object_id, udo.taxon_concept_id, udo.vetted_id, udo.visibility_id, tcf.ancestor_id
            FROM users_data_objects udo
            LEFT JOIN taxon_concepts_flattened tcf ON (udo.taxon_concept_id=tcf.taxon_concept_id)
            WHERE udo.data_object_id IN (". implode(",", $data_object_ids) .")";
        $this->add_ancestries_from_result($query, 'ancestor_id');
    }
    
    private function add_ancestries_from_result($query, $field_suffix = 'ancestor_id')
    {
        foreach($this->mysqli_slave->iterate_file($query) as $row_num => $row)
        {
            $data_object_id = $row[0];
            if(!isset($this->objects[$data_object_id])) continue;
            $taxon_concept_id = $row[1];
            $vetted_id = $row[2];
            $visibility_id = $row[3];
            $ancestor_id = $row[4];
            if($taxon_concept_id == 'NULL') $taxon_concept_id = 0;
            if($ancestor_id == 'NULL') $ancestor_id = 0;
            
            if($taxon_concept_id) $this->objects[$data_object_id][$field_suffix][$taxon_concept_id] = 1;
            if($ancestor_id) $this->objects[$data_object_id][$field_suffix][$ancestor_id] = 1;
            if($field_prefix = @$this->vetted_field_label_prefixes[$vetted_id])
            {
                if($taxon_concept_id) $this->objects[$data_object_id][$field_prefix.$field_suffix][$taxon_concept_id] = 1;
                if($ancestor_id) $this->objects[$data_object_id][$field_prefix.$field_suffix][$ancestor_id] = 1;
            }
            if($field_prefix = @$this->visibility_field_label_prefixes[$visibility_id])
            {
                if($taxon_concept_id) $this->objects[$data_object_id][$field_prefix.$field_suffix][$taxon_concept_id] = 1;
                if($ancestor_id) $this->objects[$data_object_id][$field_prefix.$field_suffix][$ancestor_id] = 1;
            }
        }
    }
    
    private function lookup_resources(&$data_object_ids)
    {
        static $latest_harvest_event_ids = array();
        if(!$latest_harvest_event_ids)
        {
            $query = "SELECT resource_id, MAX(id) FROM harvest_events he GROUP BY resource_id";
            foreach($this->mysqli_slave->iterate_file($query) as $row_num => $row)
            {
                $latest_harvest_event_ids[] = $row[1];
            }
        }
        $query = "
            SELECT dohe.data_object_id, he.resource_id
            FROM data_objects_harvest_events dohe
            JOIN harvest_events he ON (dohe.harvest_event_id=he.id)
            WHERE dohe.data_object_id IN (". implode(",", $data_object_ids) .")
            AND dohe.harvest_event_id IN (". implode(",", $latest_harvest_event_ids) .")";
        foreach($this->mysqli_slave->iterate_file($query) as $row_num => $row)
        {
            $id = $row[0];
            if(!isset($this->objects[$id])) continue;
            $this->objects[$id]['resource_id'] = $row[1];
        }
    }
    
    private function lookup_ignores(&$data_object_ids)
    {
        $query = "
            SELECT data_object_id, user_id
            FROM worklist_ignored_data_objects
            WHERE data_object_id IN (". implode(",", $data_object_ids) .")";
        foreach($this->mysqli_slave->iterate_file($query) as $row_num => $row)
        {
            $id = $row[0];
            if(!isset($this->objects[$id])) continue;
            $this->objects[$id]['ignored_by_user_id'][$row[1]] = 1;
        }
    }
    
    private function lookup_curation(&$data_object_ids)
    {
        $query = "
            SELECT cal.target_id, cal.user_id
            FROM ".LOGGING_DB.".curator_activity_logs cal
            JOIN ".LOGGING_DB.".translated_activities ta ON (cal.activity_id=ta.id)
            WHERE ta.name IN ('trusted', 'untrusted', 'hide', 'show', 'inappropriate', 'unreviewed', 'add_association', 'add_common_name')
            AND cal.changeable_object_type_id IN (".ChangeableObjectType::data_object()->id.", ".ChangeableObjectType::data_objects_hierarchy_entry()->id.")
            AND cal.target_id  IN (". implode(",", $data_object_ids) .")";
        foreach($this->mysqli_slave->iterate_file($query) as $row_num => $row)
        {
            $id = $row[0];
            if(!isset($this->objects[$id])) continue;
            $this->objects[$id]['curated_by_user_id'][$row[1]] = 1;
        }
    }
    
    private function lookup_table_of_contents(&$data_object_ids)
    {
        $query = "
            SELECT data_object_id, toc_id
            FROM data_objects_table_of_contents
            WHERE data_object_id IN (". implode(",", $data_object_ids) .")";
        foreach($this->mysqli_slave->iterate_file($query) as $row_num => $row)
        {
            $id = $row[0];
            if(!isset($this->objects[$id])) continue;
            $this->objects[$id]['toc_id'][$row[1]] = 1;
        }
    }
    
    private function lookup_translations(&$data_object_ids)
    {
        $query = "
            SELECT data_object_id, original_data_object_id
            FROM data_object_translations
            WHERE data_object_id IN (". implode(",", $data_object_ids) .")";
        foreach($this->mysqli_slave->iterate_file($query) as $row_num => $row)
        {
            $id = $row[0];
            if(!isset($this->objects[$id])) continue;
            $this->objects[$id]['is_translation'] = true;
        }
    }
}

?>
