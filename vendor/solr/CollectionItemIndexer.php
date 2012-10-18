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
    }
    
    public function index_collection($collection_id, $optimize = true)
    {
        $this->solr = new SolrAPI($this->solr_server, 'collection_items');
        $this->solr->delete('collection_id:'. $collection_id);
        
        $query = "SELECT id FROM collection_items WHERE collection_id = $collection_id";
        $collection_item_ids = array();
        foreach($this->mysqli->iterate_file($query) as $row_num => $row) $collection_item_ids[] = $row[0];
        if($collection_item_ids)
        {
            $this->index_collection_items($collection_item_ids);
        }
    }
    
    public function index_collection_items(&$collection_item_ids = array(), $optimize = false)
    {
        $this->solr = new SolrAPI($this->solr_server, 'collection_items');
        if($collection_item_ids)
        {
            $batches = array_chunk($collection_item_ids, 10000);
            $count = count($batches);
            foreach($batches as $batch)
            {
                unset($this->objects);
                static $num_batch = 0;
                $num_batch++;
                if($GLOBALS['ENV_DEBUG']) echo "Looking up $num_batch of $count .. Time: ". time_elapsed()." .. Mem: ". memory_get_usage() ."\n";
                $this->lookup_data_objects(array('ids' => $batch));
                $this->lookup_taxon_concepts(array('ids' => $batch));
                $this->lookup_users(array('ids' => $batch));
                $this->lookup_collections(array('ids' => $batch));
                $this->lookup_communities(array('ids' => $batch));
                if($GLOBALS['ENV_DEBUG']) echo "Looked up $num_batch of $count .. Time: ". time_elapsed()." .. Mem: ". memory_get_usage() ."\n";
                
                // delete old ones
                $queries = array();
                foreach($batch as $id) $queries[] = "collection_item_id:$id";
                $this->solr->delete_by_queries($queries, false);
                // add new ones if available
                if(isset($this->objects)) $this->solr->send_attributes($this->objects);
            }
        }else
        {
            $start = 0;
            $max_id = 0;
            $limit = 50000;
            $result = $this->mysqli_slave->query("SELECT MIN(id) as min, MAX(id) as max FROM collection_items");
            if($result && $row=$result->fetch_assoc())
            {
                $start = $row["min"];
                $max_id = $row["max"];
            }
            
            // $this->solr->delete("collection_item_id:[* TO ".($start - 1)."]", false);
            for($i=$start ; $i<$max_id ; $i+=$limit)
            {
                unset($this->objects);
                if($GLOBALS['ENV_DEBUG']) echo "Looking up $i : $limit .. max: $max_id .. Time: ". time_elapsed()." .. Mem: ". memory_get_usage() ."\n";
                $this->lookup_data_objects(array('start' => $i, 'limit' => $limit));
                $this->lookup_taxon_concepts(array('start' => $i, 'limit' => $limit));
                $this->lookup_users(array('start' => $i, 'limit' => $limit));
                $this->lookup_collections(array('start' => $i, 'limit' => $limit));
                $this->lookup_communities(array('start' => $i, 'limit' => $limit));
                if($GLOBALS['ENV_DEBUG']) echo "Looked up $i : $limit Time: ". time_elapsed()." .. Mem: ". memory_get_usage() ."\n";
                $this->solr->delete("collection_item_id:[$i TO ". ($i + $limit - 1) ."]");
                
                if(isset($this->objects)) $this->solr->send_attributes($this->objects);
            }
            // $this->solr->delete("collection_item_id:[".($max_id + 1)." TO *]");
        }
        
        $this->solr->commit();
    }
    
    
    function lookup_data_objects($params)
    {
        $sound_type_ids = DataType::sound_type_ids();
        $image_type_ids = DataType::image_type_ids();
        $video_type_ids = DataType::video_type_ids();
        $map_type_ids = DataType::map_type_ids();
        $text_type_ids = DataType::text_type_ids();
        
        if($GLOBALS['ENV_DEBUG']) echo "\nquerying lookup_data_objects\n";
        $query = "SELECT ci.id, ci.annotation, ci.added_by_user_id, UNIX_TIMESTAMP(ci.created_at), UNIX_TIMESTAMP(ci.updated_at),
            ci.object_id, ci.collection_id, do.object_title, do.data_type_id, do.data_rating, ttoc.label
            FROM collection_items ci
            JOIN data_objects do ON (ci.object_id=do.id)
            LEFT JOIN
              (table_of_contents toc JOIN data_objects_table_of_contents dotoc ON (toc.id=dotoc.toc_id)
              JOIN translated_table_of_contents ttoc ON (toc.id=ttoc.table_of_contents_id AND ttoc.language_id=". Language::english()->id ."))
              ON (do.id=dotoc.data_object_id)
            WHERE object_type='DataObject' AND ci.id ";
        if(@$params['ids']) $query .= "IN (". implode(",", $params['ids']) .")";
        else $query .= "BETWEEN ". $params['start'] ." AND ". ($params['start'] + $params['limit']);
        
        $began = false;
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
            $object_title = trim($row[7]);
            $data_type_id = $row[8];
            $data_rating = trim($row[9]);
            $subject = trim($row[10]);
            $title = trim($object_title);
            if($annotation == 'NULL') $annotation = '';
            if($added_by_user_id == 'NULL') $added_by_user_id = 0;
            if($title == 'NULL') $title = '';
            if($collection_id == 'NULL') continue;
            
            $simple_type = "DataObject";
            if(in_array($data_type_id, $text_type_ids)) $simple_type = "Text";
            elseif(in_array($data_type_id, $sound_type_ids)) $simple_type = "Sound";
            elseif(in_array($data_type_id, $image_type_ids)) $simple_type = "Image";
            elseif(in_array($data_type_id, $video_type_ids)) $simple_type = "Video";
            elseif(in_array($data_type_id, $map_type_ids)) $simple_type = "Map";
            else $simple_type = "DataObject";
            
            if(!$title)
            {
                if(in_array($data_type_id, $text_type_ids) && $subject) $title = $subject;
                else $title = $simple_type;
            }
            
            $this->objects[$collection_item_id] = array(
                'object_type'       => $simple_type,
                'object_id'         => $object_id,
                'collection_id'     => $collection_id,
                'annotation'        => SolrAPI::text_filter($annotation),
                'added_by_user_id'  => $added_by_user_id,
                'date_created'      => $created_at ?: '1960-01-01T00:00:01Z',
                'date_modified'     => $updated_at ?: '1960-01-01T00:00:01Z',
                'title'             => (SolrAPI::text_filter($title) ?: 'User'),
                'data_rating'       => $data_rating,
                'richness_score'    => 0);
            $used_ids[$collection_item_id] = true;
        }
    }
    
    function lookup_taxon_concepts($params)
    {
        if($GLOBALS['ENV_DEBUG']) echo "\nquerying lookup_taxon_concepts\n";
        $query = "SELECT ci.id, ci.annotation, ci.added_by_user_id, UNIX_TIMESTAMP(ci.created_at), UNIX_TIMESTAMP(ci.updated_at),
            ci.object_id, ci.collection_id, ci.name, tcm.richness_score, n.string name_string, ci.sort_field
            FROM collection_items ci
            LEFT JOIN taxon_concept_metrics tcm ON (ci.object_id=tcm.taxon_concept_id)
            LEFT JOIN
                (taxon_concept_names tcn JOIN names n ON (tcn.name_id=n.id AND tcn.preferred=1 AND tcn.vern=0)) ON (ci.object_id=tcn.taxon_concept_id)
            WHERE object_type='TaxonConcept' AND ci.id ";
        if(@$params['ids']) $query .= "IN (". implode(",", $params['ids']) .")";
        else $query .= "BETWEEN ". $params['start'] ." AND ". ($params['start'] + $params['limit']);
        $query .= " ORDER BY tcn.source_hierarchy_entry_id ASC";
        
        $began = false;
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
            $title = $row[7];
            $richness_score = $row[8];
            $name_string = $row[9];
            $sort_field = $row[10];
            if($annotation == 'NULL') $annotation = '';
            if($added_by_user_id == 'NULL') $added_by_user_id = 0;
            if($title == 'NULL') $title = $name_string;
            if($title == 'NULL') $title = 'zzz';
            if($richness_score == 'NULL') $richness_score = 0;
            if($collection_id == 'NULL') continue;
            if($sort_field == 'NULL') $sort_field = '';
            
            $this->objects[$collection_item_id] = array(
                'object_type'       => 'TaxonConcept',
                'object_id'         => $object_id,
                'collection_id'     => $collection_id,
                'annotation'        => SolrAPI::text_filter($annotation),
                'added_by_user_id'  => $added_by_user_id,
                'date_created'      => $created_at ?: '1960-01-01T00:00:01Z',
                'date_modified'     => $updated_at ?: '1960-01-01T00:00:01Z',
                'title'             => (SolrAPI::text_filter($title) ?: 'User'),
                'richness_score'    => $richness_score,
                'data_rating'       => 0,
                'sort_field'        => SolrAPI::text_filter($sort_field));
            $used_ids[$collection_item_id] = true;
        }
    }
    
    function lookup_users($params)
    {
        if($GLOBALS['ENV_DEBUG']) echo "\nquerying lookup_users\n";
        $query = "SELECT ci.id, ci.annotation, ci.added_by_user_id, UNIX_TIMESTAMP(ci.created_at), UNIX_TIMESTAMP(ci.updated_at),
            ci.object_id, ci.collection_id, u.username
            FROM collection_items ci
            JOIN users u ON (ci.object_id=u.id)
            WHERE object_type='User' AND ci.id ";
        if(@$params['ids']) $query .= "IN (". implode(",", $params['ids']) .")";
        else $query .= "BETWEEN ". $params['start'] ." AND ". ($params['start'] + $params['limit']);
        
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
            $username = trim($row[7]);
            if($annotation == 'NULL') $annotation = '';
            if($added_by_user_id == 'NULL') $added_by_user_id = 0;
            if($username == 'NULL') $username = '';
            if($collection_id == 'NULL') continue;
            
            $this->objects[$collection_item_id] = array(
                'object_type'       => 'User',
                'object_id'         => $object_id,
                'collection_id'     => $collection_id,
                'annotation'        => SolrAPI::text_filter($annotation),
                'added_by_user_id'  => $added_by_user_id,
                'date_created'      => $created_at ?: '1960-01-01T00:00:01Z',
                'date_modified'     => $updated_at ?: '1960-01-01T00:00:01Z',
                'title'             => (SolrAPI::text_filter($username) ?: 'User'),
                'richness_score'    => 0,
                'data_rating'       => 0);
            $used_ids[$collection_item_id] = true;
        }
    }
    
    function lookup_communities($params)
    {
        if($GLOBALS['ENV_DEBUG']) echo "\nquerying lookup_communities\n";
        $query = "SELECT ci.id, ci.annotation, ci.added_by_user_id, UNIX_TIMESTAMP(ci.created_at), UNIX_TIMESTAMP(ci.updated_at),
            ci.object_id, ci.collection_id, c.name
            FROM collection_items ci
            JOIN communities c ON (ci.object_id=c.id)
            WHERE object_type='Community' AND ci.id ";
        if(@$params['ids']) $query .= "IN (". implode(",", $params['ids']) .")";
        else $query .= "BETWEEN ". $params['start'] ." AND ". ($params['start'] + $params['limit']);
        
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
            $name = trim($row[7]);
            if($annotation == 'NULL') $annotation = '';
            if($added_by_user_id == 'NULL') $added_by_user_id = 0;
            if($name == 'NULL') $title = '';
            if($collection_id == 'NULL') continue;
            
            $this->objects[$collection_item_id] = array(
                'object_type'       => 'Community',
                'object_id'         => $object_id,
                'collection_id'     => $collection_id,
                'annotation'        => SolrAPI::text_filter($annotation),
                'added_by_user_id'  => $added_by_user_id,
                'date_created'      => $created_at ?: '1960-01-01T00:00:01Z',
                'date_modified'     => $updated_at ?: '1960-01-01T00:00:01Z',
                'title'             => (SolrAPI::text_filter($name) ?: 'User'),
                'richness_score'    => 0,
                'data_rating'       => 0);
            $used_ids[$collection_item_id] = true;
        }
    }
    
    function lookup_collections($params)
    {
        if($GLOBALS['ENV_DEBUG']) echo "\nquerying lookup_collections\n";
        $query = "SELECT ci.id, ci.annotation, ci.added_by_user_id, UNIX_TIMESTAMP(ci.created_at), UNIX_TIMESTAMP(ci.updated_at),
            ci.object_id, ci.collection_id, c.name
            FROM collection_items ci
            JOIN collections c ON (ci.object_id=c.id)
            WHERE object_type='Collection' AND ci.id ";
        if(@$params['ids']) $query .= "IN (". implode(",", $params['ids']) .")";
        else $query .= "BETWEEN ". $params['start'] ." AND ". ($params['start'] + $params['limit']);
        
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
            $name = trim($row[7]);
            if($annotation == 'NULL') $annotation = '';
            if($added_by_user_id == 'NULL') $added_by_user_id = 0;
            if($name == 'NULL') $title = '';
            if($collection_id == 'NULL') continue;
            
            $this->objects[$collection_item_id] = array(
                'object_type'       => 'Collection',
                'object_id'         => $object_id,
                'collection_id'     => $collection_id,
                'annotation'        => SolrAPI::text_filter($annotation),
                'added_by_user_id'  => $added_by_user_id,
                'date_created'      => $created_at ?: '1960-01-01T00:00:01Z',
                'date_modified'     => $updated_at ?: '1960-01-01T00:00:01Z',
                'title'             => (SolrAPI::text_filter($name) ?: 'User'),
                'richness_score'    => 0,
                'data_rating'       => 0);
            $used_ids[$collection_item_id] = true;
        }
    }
}

?>