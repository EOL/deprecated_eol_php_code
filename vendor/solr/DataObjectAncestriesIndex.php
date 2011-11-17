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
    }
    
    public function index()
    {
        //exit;
        //return;
        ini_set('display_errors', true);
        
        if(!defined('SOLR_SERVER') || !SolrAPI::ping(SOLR_SERVER, 'data_objects')) return false;
        $this->solr = new SolrAPI(SOLR_SERVER, 'data_objects');
        
        //$this->solr->delete_all_documents();
        
        $start = 0;
        $max_id = 0;
        $limit = 50000;
        $result = $this->mysqli_slave->query("SELECT MIN(data_object_id) min, MAX(data_object_id) max FROM data_objects_taxon_concepts");
        if($result && $row=$result->fetch_assoc())
        {
            $start = $row["min"];
            $max_id = $row["max"];
        }
        if(!$start) $start = 0;
        $start = 13071166;
        for($i=$start ; $i<=$max_id ; $i+=$limit)
        {
            $this->index_next_block($i, $limit);
        }
        // $this->solr->optimize();
        // $results = $this->solr->get_results('ancestor_id:1');
        // if($results) $this->solr->swap('data_objects_swap', 'data_objects');
    }
    
    public function index_objects(&$data_object_ids = array(), $optimize = false)
    {
        $this->solr = new SolrAPI(SOLR_SERVER, 'data_objects');
        $batches = array_chunk($data_object_ids, 10000);
        foreach($batches as $batch)
        {
            $this->index_next_block(null, null, $batch);
        }
        // $this->solr->commit();
        // if($optimize) $this->solr->optimize();
    }
    
    public function index_next_block($start, $limit, &$data_object_ids = array())
    {
        unset($this->objects);
        echo "Looking up $start Time: ". time_elapsed()." .. Mem: ". memory_get_usage() ."\n";
        $this->lookup_objects($start, $limit, $data_object_ids);
        echo "after DO Time: ". time_elapsed()." .. Mem: ". memory_get_usage() ."\n";
        $this->lookup_ancestries($start, $limit, $data_object_ids);
        echo "after ancestries Time: ". time_elapsed()." .. Mem: ". memory_get_usage() ."\n";
        $this->lookup_curated_ancestries($start, $limit, $data_object_ids);
        echo "after c_a Time: ". time_elapsed()." .. Mem: ". memory_get_usage() ."\n";
        $this->lookup_user_added_ancestries($start, $limit, $data_object_ids);
        echo "after uaa Time: ". time_elapsed()." .. Mem: ". memory_get_usage() ."\n";
        $this->lookup_ancestries_he($start, $limit, $data_object_ids);
        echo "after ancestries_he Time: ". time_elapsed()." .. Mem: ". memory_get_usage() ."\n";
        $this->lookup_curated_ancestries_he($start, $limit, $data_object_ids);
        echo "after curated ancestries Time: ". time_elapsed()." .. Mem: ". memory_get_usage() ."\n";
        $this->lookup_user_added_ancestries_he($start, $limit, $data_object_ids);
        echo "after udo Time: ". time_elapsed()." .. Mem: ". memory_get_usage() ."\n";
        $this->lookup_ignores($start, $limit, $data_object_ids);
        echo "after ignores Time: ". time_elapsed()." .. Mem: ". memory_get_usage() ."\n";
        $this->lookup_curation($start, $limit, $data_object_ids);
        echo "after curation Time: ". time_elapsed()." .. Mem: ". memory_get_usage() ."\n";
        $this->lookup_resources($start, $limit, $data_object_ids);
        echo "after resources Time: ". time_elapsed()." .. Mem: ". memory_get_usage() ."\n";
        $this->lookup_table_of_contents($start, $limit, $data_object_ids);
        echo "after toc Time: ". time_elapsed()." .. Mem: ". memory_get_usage() ."\n";
        $this->lookup_translations($start, $limit, $data_object_ids);
        echo "after translations Time: ". time_elapsed()." .. Mem: ". memory_get_usage() ."\n";
        
        if($data_object_ids)
        {
            foreach($data_object_ids as $id) $queries[] = "data_object_id:$id";
            $this->solr->delete_by_queries($queries);
        }
        
        if(isset($this->objects))
        {
            foreach($this->objects as $id => $attr)
            {
                if(isset($attr['trusted_ancestor_id'])) $this->objects[$id]['max_vetted_weight'] = 5;
                elseif(isset($attr['unreviewed_ancestor_id'])) $this->objects[$id]['max_vetted_weight'] = 4;
                elseif(isset($attr['untrusted_ancestor_id'])) $this->objects[$id]['max_vetted_weight'] = 3;
                elseif(isset($attr['inappropriate_ancestor_id'])) $this->objects[$id]['max_vetted_weight'] = 2;
                else $this->objects[$id]['max_vetted_weight'] = 1;
                
                if(isset($attr['visible_ancestor_id'])) $this->objects[$id]['max_visibility_weight'] = 4;
                elseif(isset($attr['invisible_ancestor_id'])) $this->objects[$id]['max_vetted_weight'] = 3;
                elseif(isset($attr['preview_ancestor_id'])) $this->objects[$id]['max_vetted_weight'] = 2;
                else $this->objects[$id]['max_vetted_weight'] = 1;
            }
            $this->solr->send_attributes($this->objects);
        }
    }
    
    
    private function lookup_objects($start, $limit, &$data_object_ids = array())
    {
        debug("querying objects ($start, $limit)");
        $last_data_object_id = 0;
        $query = "
            SELECT do.id, do.guid, do.data_type_id, do.data_subtype_id, do.language_id, do.license_id, do.published, do.data_rating,
                UNIX_TIMESTAMP(do.created_at), he.id, he.taxon_concept_id, dohe.vetted_id, dohe.visibility_id,
                cuhe.id, cuhe.taxon_concept_id, cudohe.vetted_id, cudohe.visibility_id,
                udo.taxon_concept_id, cudohe.vetted_id, cudohe.visibility_id, udo.user_id
            FROM data_objects do
            LEFT JOIN
                (data_objects_hierarchy_entries dohe JOIN hierarchy_entries he ON (dohe.hierarchy_entry_id=he.id))
                ON (do.id=dohe.data_object_id)
            LEFT JOIN
                (data_objects_hierarchy_entries cudohe JOIN hierarchy_entries cuhe ON (cudohe.hierarchy_entry_id=cuhe.id))
                ON (do.id=cudohe.data_object_id)
            LEFT JOIN users_data_objects udo ON (do.id=udo.data_object_id)
            WHERE (do.published=1 OR dohe.visibility_id=".Visibility::preview()->id.")
            AND do.id ";
        if($data_object_ids) $query .= "IN (". implode(",", $data_object_ids) .")";
        else $query .= "BETWEEN $start AND ". ($start+$limit);
        
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
                $created_at = $row[8];
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
                $this->objects[$id]['created_at'] = date('Y-m-d', $created_at) . "T". date('h:i:s', $created_at) ."Z";
            }
            
            // HierarchyEntry block
            $hierarchy_entry_id = $row[9];
            $taxon_concept_id = $row[10];
            $vetted_id = $row[10];
            $visibility_id = $row[12];
            if($taxon_concept_id && $taxon_concept_id != 'NULL')
            {
                $this->objects[$id]['taxon_concept_id'][$taxon_concept_id] = 1;
                $this->objects[$id]['hierarchy_entry_id'][$hierarchy_entry_id] = 1;
            }
            
            // CuratedHierarchyEntry block
            $hierarchy_entry_id = $row[13];
            $taxon_concept_id = $row[14];
            $vetted_id = $row[15];
            $visibility_id = $row[16];
            if($taxon_concept_id && $taxon_concept_id != 'NULL')
            {
                $this->objects[$id]['taxon_concept_id'][$taxon_concept_id] = 1;
                $this->objects[$id]['hierarchy_entry_id'][$hierarchy_entry_id] = 1;
            }
            
            // UsersDataObject block
            $taxon_concept_id = $row[17];
            $vetted_id = $row[18];
            $visibility_id = $row[19];
            $user_id = $row[20];
            if($taxon_concept_id && $taxon_concept_id != 'NULL')
            {
                $this->objects[$id]['taxon_concept_id'][$taxon_concept_id] = 1;
                $this->objects[$id]['added_by_user_id'] = $user_id;
            }
            
            $last_data_object_id = $id;
        }
    }
    
    private function lookup_ancestries($start, $limit, &$data_object_ids = array())
    {
        debug("querying ancestries ($start, $limit)");
        $query = "
            SELECT do.id, he.taxon_concept_id, dohe.vetted_id, dohe.visibility_id, tcf.ancestor_id
            FROM data_objects do
            JOIN data_objects_hierarchy_entries dohe ON (do.id=dohe.data_object_id)
            JOIN hierarchy_entries he ON (dohe.hierarchy_entry_id=he.id)
            LEFT JOIN taxon_concepts_flattened tcf ON (he.taxon_concept_id=tcf.taxon_concept_id)
            WHERE (do.published=1 OR dohe.visibility_id!=".Visibility::visible()->id.")
            AND do.id ";
        if($data_object_ids) $query .= "IN (". implode(",", $data_object_ids) .")";
        else $query .= "BETWEEN $start AND ". ($start+$limit);
        
        $this->add_ancestries_from_result($query, 'ancestor_id');
    }
    
    private function lookup_curated_ancestries($start, $limit, &$data_object_ids = array())
    {
        debug("querying ancestries ($start, $limit)");
        $query = "
            SELECT do.id, he.taxon_concept_id, dohe.vetted_id, dohe.visibility_id, tcf.ancestor_id
            FROM data_objects do
            JOIN curated_data_objects_hierarchy_entries dohe ON (do.id=dohe.data_object_id)
            JOIN hierarchy_entries he ON (dohe.hierarchy_entry_id=he.id)
            LEFT JOIN taxon_concepts_flattened tcf ON (he.taxon_concept_id=tcf.taxon_concept_id)
            WHERE (do.published=1 OR dohe.visibility_id!=".Visibility::visible()->id.")
            AND do.id ";
        if($data_object_ids) $query .= "IN (". implode(",", $data_object_ids) .")";
        else $query .= "BETWEEN $start AND ". ($start+$limit);
        $this->add_ancestries_from_result($query, 'ancestor_id');
    }
    
    private function lookup_user_added_ancestries($start, $limit, &$data_object_ids = array())
    {
        debug("querying ancestries ($start, $limit)");
        $query = "
            SELECT udo.data_object_id, udo.taxon_concept_id, udo.vetted_id, udo.visibility_id, tcf.ancestor_id
            FROM users_data_objects udo
            LEFT JOIN taxon_concepts_flattened tcf ON (udo.taxon_concept_id=tcf.taxon_concept_id)
            WHERE udo.data_object_id ";
        if($data_object_ids) $query .= "IN (". implode(",", $data_object_ids) .")";
        else $query .= "BETWEEN $start AND ". ($start+$limit);
        $this->add_ancestries_from_result($query, 'ancestor_id');
    }
    
    private function lookup_ancestries_he($start, $limit, &$data_object_ids = array())
    {
        debug("querying ancestries ($start, $limit)");
        $query = "
            SELECT do.id, he_concept.id, dohe.vetted_id, dohe.visibility_id, hef.ancestor_id
            FROM data_objects do
            JOIN data_objects_hierarchy_entries dohe ON (do.id=dohe.data_object_id)
            JOIN hierarchy_entries he ON (dohe.hierarchy_entry_id=he.id)
            JOIN hierarchy_entries he_concept ON (he.taxon_concept_id=he_concept.taxon_concept_id)
            LEFT JOIN hierarchy_entries_flattened hef ON (he_concept.id=hef.hierarchy_entry_id)
            WHERE (do.published=1 OR dohe.visibility_id!=".Visibility::visible()->id.")
            AND he.published=1
            AND do.id ";
        if($data_object_ids) $query .= "IN (". implode(",", $data_object_ids) .")";
        else $query .= "BETWEEN $start AND ". ($start+$limit);
        
        $this->add_ancestries_from_result($query, 'ancestor_he_id');
    }
    
    private function lookup_curated_ancestries_he($start, $limit, &$data_object_ids = array())
    {
        debug("querying ancestries ($start, $limit)");
        $query = "
            SELECT do.id, he_concept.id, dohe.vetted_id, dohe.visibility_id, hef.ancestor_id
            FROM data_objects do
            JOIN curated_data_objects_hierarchy_entries dohe ON (do.id=dohe.data_object_id)
            JOIN hierarchy_entries he ON (dohe.hierarchy_entry_id=he.id)
            JOIN hierarchy_entries he_concept ON (he.taxon_concept_id=he_concept.taxon_concept_id)
            LEFT JOIN hierarchy_entries_flattened hef ON (he_concept.id=hef.hierarchy_entry_id)
            WHERE (do.published=1 OR dohe.visibility_id!=".Visibility::visible()->id.")
            AND (he.published=1 OR he.visibility_id!=".Visibility::visible()->id.")
            AND do.id ";
        if($data_object_ids) $query .= "IN (". implode(",", $data_object_ids) .")";
        else $query .= "BETWEEN $start AND ". ($start+$limit);
        $this->add_ancestries_from_result($query, 'ancestor_he_id');
    }
    
    private function lookup_user_added_ancestries_he($start, $limit, &$data_object_ids = array())
    {
        debug("querying ancestries ($start, $limit)");
        $query = "
            SELECT udo.data_object_id, he_concept.id, udo.vetted_id, udo.visibility_id, hef.ancestor_id
            FROM users_data_objects udo
            JOIN hierarchy_entries he_concept ON (he_concept.taxon_concept_id=udo.taxon_concept_id)
            LEFT JOIN hierarchy_entries_flattened hef ON (he_concept.id=hef.hierarchy_entry_id)
            WHERE udo.data_object_id ";
        if($data_object_ids) $query .= "IN (". implode(",", $data_object_ids) .")";
        else $query .= "BETWEEN $start AND ". ($start+$limit);
        $this->add_ancestries_from_result($query, 'ancestor_he_id');
    }
    
    private function add_ancestries_from_result($query, $field_suffix = 'ancestor_id')
    {
        $vetted_fields = array(
            Vetted::trusted()->id => 'trusted_' . $field_suffix,
            Vetted::unknown()->id => 'unreviewed_' . $field_suffix,
            Vetted::untrusted()->id => 'untrusted_' . $field_suffix,
            Vetted::inappropriate()->id => 'inappropriate_' . $field_suffix);
        $visibility_fields = array(
            Visibility::invisible()->id => 'invisible_' . $field_suffix,
            Visibility::visible()->id => 'visible_' . $field_suffix,
            Visibility::preview()->id => 'preview_' . $field_suffix);
        
        foreach($this->mysqli_slave->iterate_file($query) as $row_num => $row)
        {
            $id = $row[0];
            if(!isset($this->objects[$id])) continue;
            $taxon_concept_id = $row[1];
            $vetted_id = $row[2];
            $visibility_id = $row[3];
            $ancestor_id = $row[4];
            if($taxon_concept_id == 'NULL') $taxon_concept_id = 0;
            if($ancestor_id == 'NULL') $ancestor_id = 0;
            
            if($taxon_concept_id) $this->objects[$id][$field_suffix][$taxon_concept_id] = 1;
            if($ancestor_id) $this->objects[$id][$field_suffix][$ancestor_id] = 1;
            if($field = @$vetted_fields[$vetted_id])
            {
                if($taxon_concept_id) $this->objects[$id][$field][$taxon_concept_id] = 1;
                if($ancestor_id) $this->objects[$id][$field][$ancestor_id] = 1;
            }
            if($field = @$visibility_fields[$visibility_id])
            {
                if($taxon_concept_id) $this->objects[$id][$field][$taxon_concept_id] = 1;
                if($ancestor_id) $this->objects[$id][$field][$ancestor_id] = 1;
            }
        }
    }
    
    
    
    private function lookup_resources($start, $limit, &$data_object_ids = array())
    {
        debug("querying resources ($start, $limit)");
        $query = "
            SELECT dohe.data_object_id, he.resource_id
            FROM data_objects do 
            JOIN data_objects_harvest_events dohe ON (do.id=dohe.data_object_id)
            JOIN   harvest_events he ON (dohe.harvest_event_id=he.id)
            WHERE do.id ";
        if($data_object_ids) $query .= "IN (". implode(",", $data_object_ids) .")";
        else $query .= "BETWEEN $start AND ". ($start+$limit);
        
        foreach($this->mysqli_slave->iterate_file($query) as $row_num => $row)
        {
            $id = $row[0];
            if(!isset($this->objects[$id])) continue;
            $resource_id = $row[1];
            $this->objects[$id]['resource_id'] = $resource_id;
        }
    }
    
    private function lookup_ignores($start, $limit, &$data_object_ids = array())
    {
        $query = "
            SELECT data_object_id, user_id
            FROM worklist_ignored_data_objects
            WHERE data_object_id ";
        if($data_object_ids) $query .= "IN (". implode(",", $data_object_ids) .")";
        else $query .= "BETWEEN $start AND ". ($start+$limit);
        
        foreach($this->mysqli_slave->iterate_file($query) as $row_num => $row)
        {
            $id = $row[0];
            if(!isset($this->objects[$id])) continue;
            $user_id = $row[1];
            $this->objects[$id]['ignored_by_user_id'][$user_id] = 1;
        }
    }
    
    private function lookup_curation($start, $limit, &$data_object_ids = array())
    {
        $query = "
            SELECT cal.object_id, cal.user_id
            FROM ".LOGGING_DB.".curator_activity_logs cal
            JOIN ".LOGGING_DB.".translated_activities ta ON (cal.activity_id=ta.id)
            WHERE ta.name IN ('trusted', 'untrusted', 'hide', 'show', 'inappropriate', 'unreviewed', 'add_association', 'add_common_name')
            AND cal.changeable_object_type_id IN (".ChangeableObjectType::data_object()->id.", ".ChangeableObjectType::data_objects_hierarchy_entry()->id.")
            AND cal.object_id ";
        if($data_object_ids) $query .= "IN (". implode(",", $data_object_ids) .")";
        else $query .= "BETWEEN $start AND ". ($start+$limit);
        
        foreach($this->mysqli_slave->iterate_file($query) as $row_num => $row)
        {
            $id = $row[0];
            if(!isset($this->objects[$id])) continue;
            $user_id = $row[1];
            $this->objects[$id]['curated_by_user_id'][$user_id] = 1;
        }
    }
    
    private function lookup_table_of_contents($start, $limit, &$data_object_ids = array())
    {
        $query = "
            SELECT data_object_id, toc_id
            FROM data_objects_table_of_contents
            WHERE data_object_id ";
        if($data_object_ids) $query .= "IN (". implode(",", $data_object_ids) .")";
        else $query .= "BETWEEN $start AND ". ($start+$limit);
        
        foreach($this->mysqli_slave->iterate_file($query) as $row_num => $row)
        {
            $id = $row[0];
            if(!isset($this->objects[$id])) continue;
            $toc_id = $row[1];
            $this->objects[$id]['toc_id'][$toc_id] = 1;
        }
    }
    
    private function lookup_translations($start, $limit, &$data_object_ids = array())
    {
        $query = "
            SELECT data_object_id, original_data_object_id
            FROM data_object_translations
            WHERE data_object_id ";
        if($data_object_ids) $query .= "IN (". implode(",", $data_object_ids) .")";
        else $query .= "BETWEEN $start AND ". ($start+$limit);
        
        foreach($this->mysqli_slave->iterate_file($query) as $row_num => $row)
        {
            $id = $row[0];
            if(!isset($this->objects[$id])) continue;
            $this->objects[$id]['is_translation'] = true;
        }
    }
    
    
    
}

?>
