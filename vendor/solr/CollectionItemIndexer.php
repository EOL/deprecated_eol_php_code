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
        $this->collection = Collection::find($collection_id);
        if(!$this->collection->id) return;
        $this->solr = new SolrAPI($this->solr_server, 'collection_items');
        $this->solr->delete('collection_id:'. $this->collection->id);
        
        $start = 0;
        $max_id = 0;
        $limit = 50000;
        
        $result = $this->mysqli_slave->query("SELECT MIN(id) as min, MAX(id) as max FROM collection_items WHERE collection_id=". $this->collection->id);
        if($result && $row=$result->fetch_assoc())
        {
            $start = $row["min"];
            $max_id = $row["max"];
        }
        
        for($i=$start ; $i<$max_id ; $i+=$limit)
        {
            unset($this->objects);
            echo "Looking up $i : $limit .. max: $max_id .. Time: ". time_elapsed()." .. Mem: ". memory_get_usage() ."\n";
            $this->lookup_data_objects($i, $limit);
            $this->lookup_taxon_concepts($i, $limit);
            echo "Looked up $i : $limit Time: ". time_elapsed()." .. Mem: ". memory_get_usage() ."\n";
            if(isset($this->objects)) $this->solr->send_attributes($this->objects);
        }
        
        $this->solr->commit();
    }
    
    function lookup_data_objects($start, $limit)
    {
        $sound_type_ids = DataType::sound_type_ids();
        $image_type_ids = DataType::image_type_ids();
        $video_type_ids = DataType::video_type_ids();
        $map_type_ids = DataType::map_type_ids();
        $text_type_ids = DataType::text_type_ids();
        
        debug("querying data_objects ($start, $limit)");
        $query = "SELECT ci.id, ci.annotation, ci.added_by_user_id, UNIX_TIMESTAMP(ci.created_at), UNIX_TIMESTAMP(ci.updated_at), ci.object_id, do.object_title, do.data_type_id, do.data_rating, ttoc.label
        FROM collection_items ci
        JOIN data_objects do ON (ci.object_id=do.id)
        LEFT JOIN
          (table_of_contents toc JOIN data_objects_table_of_contents dotoc ON (toc.id=dotoc.toc_id)
          JOIN translated_table_of_contents ttoc ON (toc.id=ttoc.table_of_contents_id AND ttoc.language_id=". Language::english()->id ."))
          ON (do.id=dotoc.data_object_id)
        WHERE ci.collection_id = ". $this->collection->id ." AND object_type='DataObject' AND ci.id  BETWEEN $start AND ". ($start+$limit);
        
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
            $object_title = trim($row[6]);
            $data_type_id = $row[7];
            $data_rating = trim($row[8]);
            $subject = trim($row[9]);
            $title = trim($object_title);
            
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
                'collection_id'     => $this->collection->id,
                'annotation'        => SolrAPI::text_filter($annotation),
                'added_by_user_id'  => $added_by_user_id,
                'date_created'      => $created_at ?: '1960-01-01T00:00:01Z',
                'date_modified'     => $updated_at ?: '1960-01-01T00:00:01Z',
                'title'             => SolrAPI::text_filter($title),
                'data_rating'       => $data_rating);
            $used_ids[$collection_item_id] = true;
        }
    }
    
    function lookup_taxon_concepts($start, $limit)
    {
        debug("querying data_objects ($start, $limit)");
        $query = "SELECT ci.id, ci.annotation, ci.added_by_user_id, UNIX_TIMESTAMP(ci.created_at), UNIX_TIMESTAMP(ci.updated_at), ci.object_id, ci.name, tcm.richness_score
        FROM collection_items ci
        JOIN taxon_concept_metrics tcm ON (ci.object_id=tcm.taxon_concept_id)
        WHERE ci.collection_id = ". $this->collection->id ." AND object_type='TaxonConcept' AND ci.id  BETWEEN $start AND ". ($start+$limit);
        
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
            $title = $row[6];
            $richness_score = $row[7];
            
            $this->objects[$collection_item_id] = array(
                'object_type'       => 'TaxonConcept',
                'object_id'         => $object_id,
                'collection_id'     => $this->collection->id,
                'annotation'        => SolrAPI::text_filter($annotation),
                'added_by_user_id'  => $added_by_user_id,
                'date_created'      => $created_at ?: '1960-01-01T00:00:01Z',
                'date_modified'     => $updated_at ?: '1960-01-01T00:00:01Z',
                'title'             => SolrAPI::text_filter($title),
                'richness_score'    => $richness_score);
            $used_ids[$collection_item_id] = true;
        }
    }
    
}

?>