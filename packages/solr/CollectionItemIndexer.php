<?php
namespace php_active_record;

class CollectionItemIndexer
{
    private $mysqli;
    private $mysqli_slave;
    private $solr;
    private $objects;
    private $solr_server;
    
    public function __construct($solr_server = SOLR_SERVER)
    {
        $this->mysqli =& $GLOBALS['db_connection'];
        if($GLOBALS['ENV_NAME'] == 'production' && environment_defined('slave')) $this->mysqli_slave = load_mysql_environment('slave');
        else $this->mysqli_slave =& $this->mysqli;
        $this->solr_server = $solr_server;
        $this->solr = new SolrAPI($this->solr_server, 'collection_items');
        
        $this->sound_type_ids = DataType::sound_type_ids();
        $this->image_type_ids = DataType::image_type_ids();
        $this->video_type_ids = DataType::video_type_ids();
        $this->map_type_ids = DataType::map_type_ids();
        $this->text_type_ids = DataType::text_type_ids();
    }
    
    public function index_collection($collection_id)
    {
        $this->solr->delete('collection_id:'. $collection_id);
        $query = "SELECT id FROM collection_items WHERE collection_id = $collection_id";
        $collection_item_ids = array();
        foreach($this->mysqli->iterate_file($query) as $row_num => $row) $collection_item_ids[] = $row[0];
        if($collection_item_ids)
        {
            $this->index_collection_items($collection_item_ids);
        }
        $this->solr->commit_objects_in_file();
    }
    
    public function index_all_collection_items()
    {
        $this->solr->delete_all_documents();
        $limit = 500000;
        $start = $this->mysqli->select_value("SELECT MIN(id) FROM collection_items");
        $max_id = $this->mysqli->select_value("SELECT MAX(id) FROM collection_items");
        for($i=$start ; $i<$max_id ; $i+=$limit)
        {
            $upper_range = $i + $limit - 1;
            if($upper_range > $max_id) $upper_range = $max_id;
            $collection_item_ids = range($i, $upper_range);
            $this->index_collection_items($collection_item_ids);
        }
        $this->solr->commit_objects_in_file();
    }
    
    public function index_collection_items(&$collection_item_ids = array())
    {
        if(!$collection_item_ids) return;
        $batches = array_chunk($collection_item_ids, 10000);
        foreach($batches as $batch)
        {
            $this->index_batch($batch);
        }
    }
    
    private function index_batch(&$collection_item_ids)
    {
        if(!$collection_item_ids) return;
        unset($this->objects);
        static $num_batch = 0;
        $num_batch++;
        if($GLOBALS['ENV_DEBUG']) echo "Looking up $num_batch .. Time: ". time_elapsed()." .. Mem: ". memory_get_usage() ."\n";
        $this->lookup_data_objects(array('ids' => $collection_item_ids));
        $this->lookup_taxon_concepts(array('ids' => $collection_item_ids));
        $this->lookup_users(array('ids' => $collection_item_ids));
        $this->lookup_collections(array('ids' => $collection_item_ids));
        $this->lookup_communities(array('ids' => $collection_item_ids));
        if($GLOBALS['ENV_DEBUG']) echo "Looked up $num_batch .. Time: ". time_elapsed()." .. Mem: ". memory_get_usage() ."\n";
        
        $this->solr->delete_by_ids($collection_item_ids, false);
        if(isset($this->objects)) $this->solr->send_attributes_in_bulk($this->objects);
        $this->solr->commit();
    }
    
    function lookup_data_objects($params)
    {
        if($GLOBALS['ENV_DEBUG']) echo "\nquerying lookup_data_objects\n";
        $query = "SELECT ci.id, ci.annotation, ci.added_by_user_id, UNIX_TIMESTAMP(ci.created_at), UNIX_TIMESTAMP(ci.updated_at),
            ci.collected_item_id, ci.collection_id, do.object_title, ci.sort_field, NULL, NULL,
            do.data_type_id, do.data_rating, ttoc.label
            FROM collection_items ci
            JOIN data_objects do ON (ci.collected_item_id=do.id)
            LEFT JOIN
              (table_of_contents toc JOIN data_objects_table_of_contents dotoc ON (toc.id=dotoc.toc_id)
              JOIN translated_table_of_contents ttoc ON (toc.id=ttoc.table_of_contents_id AND ttoc.language_id=". Language::english()->id ."))
              ON (do.id=dotoc.data_object_id)
            WHERE collected_item_type='DataObject'
            AND ci.id IN (". implode(",", $params['ids']) .")";
        $this->collect_data_from_query($query, 'DataObject');
    }
    
    function lookup_taxon_concepts($params)
    {
        if($GLOBALS['ENV_DEBUG']) echo "\nquerying lookup_taxon_concepts\n";
        $query = "SELECT ci.id, ci.annotation, ci.added_by_user_id, UNIX_TIMESTAMP(ci.created_at), UNIX_TIMESTAMP(ci.updated_at),
            ci.collected_item_id, ci.collection_id, ci.name, ci.sort_field, tcm.richness_score, n.string name_string, NULL, NULL, NULL
            FROM collection_items ci
            LEFT JOIN taxon_concept_metrics tcm ON (ci.collected_item_id=tcm.taxon_concept_id)
            LEFT JOIN
                (taxon_concept_preferred_entries tcpe
                    JOIN hierarchy_entries he ON (tcpe.hierarchy_entry_id=he.id)
                    JOIN names n ON (he.name_id=n.id))
                ON (ci.collected_item_id=tcpe.taxon_concept_id)
            WHERE collected_item_type='TaxonConcept'
            AND ci.id IN (". implode(",", $params['ids']) .")";
        $this->collect_data_from_query($query, 'TaxonConcept');
    }
    
    function lookup_users($params)
    {
        if($GLOBALS['ENV_DEBUG']) echo "\nquerying lookup_users\n";
        $query = "SELECT ci.id, ci.annotation, ci.added_by_user_id, UNIX_TIMESTAMP(ci.created_at), UNIX_TIMESTAMP(ci.updated_at),
            ci.collected_item_id, ci.collection_id, u.username, ci.sort_field, NULL, NULL, NULL, NULL, NULL
            FROM collection_items ci
            JOIN users u ON (ci.collected_item_id=u.id)
            WHERE collected_item_type='User'
            AND ci.id IN (". implode(",", $params['ids']) .")";
        $this->collect_data_from_query($query, 'User');
    }
    
    function lookup_communities($params)
    {
        if($GLOBALS['ENV_DEBUG']) echo "\nquerying lookup_communities\n";
        $query = "SELECT ci.id, ci.annotation, ci.added_by_user_id, UNIX_TIMESTAMP(ci.created_at), UNIX_TIMESTAMP(ci.updated_at),
            ci.collected_item_id, ci.collection_id, c.name, ci.sort_field, NULL, NULL, NULL, NULL, NULL
            FROM collection_items ci
            JOIN communities c ON (ci.collected_item_id=c.id)
            WHERE collected_item_type='Community'
            AND ci.id IN (". implode(",", $params['ids']) .")";
        $this->collect_data_from_query($query, 'Community');
    }
    
    function lookup_collections($params)
    {
        if($GLOBALS['ENV_DEBUG']) echo "\nquerying lookup_collections\n";
        $query = "SELECT ci.id, ci.annotation, ci.added_by_user_id, UNIX_TIMESTAMP(ci.created_at), UNIX_TIMESTAMP(ci.updated_at),
            ci.collected_item_id, ci.collection_id, c.name, ci.sort_field, NULL, NULL, NULL, NULL, NULL
            FROM collection_items ci
            JOIN collections c ON (ci.collected_item_id=c.id)
            WHERE collected_item_type='Collection'
            AND ci.id IN (". implode(",", $params['ids']) .")";
        $this->collect_data_from_query($query, 'Collection');
    }

    function collect_data_from_query($query, $row_type)
    {
        if(!$query) return;
        if(!$row_type) return;
        
        $used_ids = array();
        foreach($this->mysqli_slave->iterate_file($query) as $row_num => $row)
        {
            $collection_item_id = $row[0];
            if(isset($used_ids[$collection_item_id])) continue;
            $annotation = trim($row[1]);
            $added_by_user_id = trim($row[2]);
            $created_at = SolrAPI::mysql_date_to_solr_date($row[3]);
            $updated_at = SolrAPI::mysql_date_to_solr_date($row[4]);
            $object_id = $row[5];
            $collection_id = $row[6];
            $title = trim($row[7]);
            $sort_field = $row[8];
            // taxon fields
            $richness_score = @$row[9];
            $name_string = @$row[10];
            // object fields
            $data_object_data_type_id = @$row[11];
            $data_object_rating = @$row[12];
            $data_object_subject = @$row[13];
            $data_object_title = @$row[14];
            
            if($annotation == 'NULL') $annotation = '';
            if($added_by_user_id == 'NULL') $added_by_user_id = 0;
            if($title == 'NULL') $title = '';
            if($name_string == 'NULL') $name_string = '';
            if($row_type == 'TaxonConcept' && !$title) $title = $name_string;
            if($row_type == 'TaxonConcept' && !$title) $title = 'zzz';
            if($richness_score == 'NULL') $richness_score = 0;
            if($sort_field == 'NULL') $sort_field = '';
            if($data_object_rating == 'NULL') $data_object_rating = 0;
            if($data_object_subject == 'NULL') $data_object_subject = '';
            if($data_object_title == 'NULL') $data_object_title = '';
            if($collection_id == 'NULL') continue;
            $object_type = $row_type;
            
            if($object_type == "DataObject")
            {
                if(in_array($data_object_data_type_id, $this->text_type_ids)) $object_type = "Text";
                elseif(in_array($data_object_data_type_id, $this->sound_type_ids)) $object_type = "Sound";
                elseif(in_array($data_object_data_type_id, $this->image_type_ids)) $object_type = "Image";
                elseif(in_array($data_object_data_type_id, $this->video_type_ids)) $object_type = "Video";
                elseif(in_array($data_object_data_type_id, $this->map_type_ids)) $object_type = "Map";
                if(!$title)
                {
                    if(in_array($data_object_data_type_id, $this->text_type_ids) && $data_object_subject) $title = $data_object_subject;
                    else $title = $object_type;
                }
            }
            
            $this->objects[$collection_item_id] = array(
                'object_type'       => $object_type,
                'object_id'         => $object_id,
                'collection_id'     => $collection_id,
                'annotation'        => SolrAPI::text_filter($annotation),
                'added_by_user_id'  => $added_by_user_id,
                'date_created'      => $created_at ?: '1960-01-01T00:00:01Z',
                'date_modified'     => $updated_at ?: '1960-01-01T00:00:01Z',
                'title'             => (SolrAPI::text_filter($title) ?: 'User'),
                'richness_score'    => $richness_score,
                'data_rating'       => $data_object_rating,
                'sort_field'        => SolrAPI::text_filter($sort_field));
            $used_ids[$collection_item_id] = true;
        }
    }
}

?>
