<?php

class FeedDenormalizer
{
    private $mysqli;
    private $iteration_size = 100000;
    
    public function __construct()
    {
        $this->mysqli =& $GLOBALS['db_connection'];
    }
    
    public function begin_process()
    {
        $start = 0;
        $max_id = 0;
        
        $result = $this->mysqli->query("SELECT MIN(id) as min, MAX(id) as max FROM taxon_concepts tc");
        if($result && $row=$result->fetch_assoc())
        {
            $start = $row["min"];
            $max_id = $row["max"];
        }
        
        // create and flush the temporary tables
        // create and flush the temporary tables
        $GLOBALS['db_connection']->query("CREATE TABLE IF NOT EXISTS `feed_data_objects` (
                  `taxon_concept_id` int(10) unsigned NOT NULL,
                  `data_object_id` int(10) unsigned NOT NULL,
                  `data_type_id` smallint(5) unsigned NOT NULL,
                  `created_at` timestamp NOT NULL,
                  PRIMARY KEY  (`taxon_concept_id`,`data_object_id`),
                  KEY `data_object_id` (`data_object_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        
        $this->mysqli->insert("CREATE TABLE IF NOT EXISTS feed_data_objects_tmp LIKE feed_data_objects");
        $this->mysqli->delete("TRUNCATE TABLE feed_data_objects_tmp");
        $this->DATA_FILE = fopen(DOC_ROOT . "temp/feed_data_objects.sql", "w+");
        
        $all_parent_ids = array();
        for($i=$start ; $i<$max_id ; $i+=$this->iteration_size)
        {
            echo "Query ". (($i-$start+$this->iteration_size)/$this->iteration_size) ." of ". (ceil(($max_id-$start)/$this->iteration_size)) ."\n";
            $image_type_id = DataType::find("http://purl.org/dc/dcmitype/StillImage");
            $text_type_id = DataType::find("http://purl.org/dc/dcmitype/Text");
            
            echo "Memory: ".memory_get_usage()."\n";
            $outfile = $this->mysqli->select_into_outfile("
                SELECT tcx.taxon_concept_id, tcx.parent_id, do.id data_object_id, do.data_type_id, do.created_at
                    FROM data_objects_taxon_concepts dotc
                    JOIN data_objects do ON (dotc.data_object_id=do.id)
                    JOIN taxon_concepts_exploded tcx ON (dotc.taxon_concept_id=tcx.taxon_concept_id)
                    WHERE tcx.taxon_concept_id BETWEEN $i AND ".($i+$this->iteration_size)."
                    AND do.data_type_id IN  ($image_type_id, $text_type_id)
                    AND do.created_at IS NOT NULL
                    AND do.created_at!='0000-00-00 00:00:00'
                    AND do.published=1
                    AND do.visibility_id=".Visibility::find('visible')."
                    ORDER BY tcx.taxon_concept_id");
            echo "Memory: ".memory_get_usage()."\n";
            
            $parent_ids = $this->get_data_from_result($outfile, false);
            $all_parent_ids = array_merge($all_parent_ids, array_keys($parent_ids));
        }
        
        // load all the info we just gathered
        fclose($this->DATA_FILE);
        $this->load_data_from_files();
        
        // now load data for all the parents of taxa with images
        if($all_parent_ids) $this->process_parents($all_parent_ids);
        
        // clean up tmp files and swap tmp tables with live ones
        $this->end_load_data();
    }
    
    public function process_parents($parent_ids)
    {
        // truncate the temporary files
        $this->DATA_FILE = fopen(DOC_ROOT . "temp/feed_data_objects.sql", "w+");
        
        $i = 0;
        unset($all_parent_ids);
        $all_parent_ids = array();
        $chunks = array_chunk($parent_ids, 3000);
        while(list($key, $chunk) = each($chunks))
        {
            $i++;
            echo "Chunk $i of ". count($chunks) ."\n";
            
            // searches for all images for THIS concept, same as above
            // but also searches top_images for the best from its decendants
            $outfile = $this->mysqli->select_into_outfile("
            (SELECT tcx.taxon_concept_id, tcx.parent_id, fdo.data_object_id, fdo.data_type_id, fdo.created_at
                FROM taxon_concepts_exploded tcx
                JOIN feed_data_objects_tmp fdo ON (tcx.taxon_concept_id=fdo.taxon_concept_id)
                WHERE tcx.taxon_concept_id IN (". implode($chunk, ",") ."))
            UNION
            (SELECT tcx.taxon_concept_id, tcx.parent_id, fdo.data_object_id, fdo.data_type_id, fdo.created_at
                FROM taxon_concepts_exploded tcx
                JOIN taxon_concepts_exploded tcx_children ON (tcx.taxon_concept_id=tcx_children.parent_id)
                JOIN feed_data_objects_tmp fdo ON  (tcx_children.taxon_concept_id=fdo.taxon_concept_id)
                WHERE tcx.taxon_concept_id IN (". implode($chunk, ",") ."))
            ORDER BY taxon_concept_id");
            echo "Memory: ".memory_get_usage()."\n";
            
            $parent_ids = $this->get_data_from_result($outfile);
            $all_parent_ids = array_merge($all_parent_ids, array_keys($parent_ids));
        }
        // load all info from this series of ancestry
        fclose($this->DATA_FILE);
        $this->load_data_from_files();
        
        // get data for the next ancestor level
        if($all_parent_ids) $this->process_parents($all_parent_ids);
    }
    
    function get_data_from_result($outfile, $delete = true)
    {
        $i = 0;
        $parent_ids = array();
        $last_taxon_concept_id = 0;
        $feed_objects = array();
        $taxon_concept_ids = array();
        
        $visible_id = Visibility::find("visible");
        $RESULT = fopen($outfile, "r");
        while(!feof($RESULT))
        {
            if($line = fgets($RESULT, 4096))
            {
                if($i%2000==0) echo "Memory: ".memory_get_usage()."\n";
                $i++;
                $fields = explode("\t", trim($line));
                $taxon_concept_id = $fields[0];
                $parent_id = $fields[1];
                $data_object_id = $fields[2];
                $data_type_id = $fields[3];
                $created_at = $fields[4];
                if($parent_id) $parent_ids[$parent_id] = 1;
                
                // this is a new entry so commit existing data before adding more
                if($taxon_concept_id != $last_taxon_concept_id)
                {
                    $this->insert_data($feed_objects);
                    // deleting the old values as we'll be calculating the new best
                    if($feed_objects) $taxon_concept_ids[] = $last_taxon_concept_id;
                    $last_taxon_concept_id = $taxon_concept_id;
                    unset($feed_objects);
                    unset($used_data_objects);
                    $feed_objects = array();
                    $used_data_objects = array();
                }
                
                if(isset($used_data_objects[$data_object_id])) continue;
                $used_data_objects[$data_object_id] = 1;
                
                $feed_objects[$data_type_id][$created_at][$data_object_id] = "$taxon_concept_id\t$data_object_id\t$data_type_id\t$created_at";
            }
        }
        fclose($RESULT);
        unlink($outfile);
        
        $this->insert_data($feed_objects);
        
        if($feed_objects) $taxon_concept_ids[] = $last_taxon_concept_id;
        if($delete)
        {
            $split_ids = array_chunk($taxon_concept_ids, 5000);
            while(list($key, $chunk) = each($split_ids))
            {
                $this->mysqli->delete("DELETE FROM feed_data_objects_tmp WHERE taxon_concept_id IN (".implode($chunk, ",").")");
            }
        }
        
        return $parent_ids;
    }
    
    public function insert_data(&$feed_objects)
    {
        // this will limit each data type to their top 100 objects
        ksort($feed_objects);
        while(list($data_type_id, $dates) = each($feed_objects))
        {
            $view_order = 1;
            krsort($dates);
            while(list($date, $object_ids) = each($dates))
            {
                krsort($object_ids);
                while(list($object_id, $data) = each($object_ids))
                {
                    fwrite($this->DATA_FILE, $data . "\n");
                    $view_order++;
                    if($view_order > 100) break;
                }
                if($view_order > 100) break;
            }
        }
    }
    
    function end_load_data()
    {
        echo "removing data files\n";
        shell_exec("rm ". DOC_ROOT ."temp/feed_data_objects.sql");
        
        // swap temporary tables with real tables
        $result = $this->mysqli->query("SELECT 1 FROM feed_data_objects_tmp LIMIT 1");
        if($result && $row=$result->fetch_assoc())
        {
            $this->mysqli->update("RENAME TABLE feed_data_objects TO feed_data_objects_swap,
                                                feed_data_objects_tmp TO feed_data_objects,
                                                feed_data_objects_swap TO feed_data_objects_tmp");
        }
        
        $this->mysqli->end_transaction();
    }
    
    function load_data_from_files()
    {
        echo "inserting new data\n";
        $this->mysqli->load_data_infile(DOC_ROOT ."temp/feed_data_objects.sql", "feed_data_objects_tmp");
        sleep_production(10);
    }
}

?>