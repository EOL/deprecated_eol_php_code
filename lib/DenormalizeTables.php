<?php

class DenormalizeTables
{
    public static function data_types_taxon_concepts()
    {
        // create a temporary table for this session
        $GLOBALS['db_connection']->query("DROP TABLE IF EXISTS `data_types_taxon_concepts`");
        $GLOBALS['db_connection']->query("CREATE TABLE `data_types_taxon_concepts` (
                  `taxon_concept_id` int unsigned NOT NULL,
                  `data_type_id` smallint unsigned NOT NULL,
                  `visibility_id` smallint unsigned NOT NULL,
                  `published` tinyint unsigned NOT NULL,
                  PRIMARY KEY  (`taxon_concept_id`,`data_type_id`, `visibility_id`, `published`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        
        $start = 0;
        $stop = 0;
        $batch_size = 50000;
        $result = $GLOBALS['db_connection']->query("SELECT MIN(id) min, MAX(id) max FROM data_objects");
        if($result && $row=$result->fetch_assoc())
        {
            $start = $row['min'];
            $stop = $row['max'];
        }
        for($i=$start ; $i<$stop ; $i+=$batch_size)
        {
            echo "Inserting ".(($i-$start+$batch_size)/$batch_size)." of ".ceil(($stop-$start)/$batch_size)."\n";
            $GLOBALS['db_connection']->begin_transaction();
            $GLOBALS['db_connection']->query("INSERT IGNORE INTO data_types_taxon_concepts (SELECT tc.id, do.data_type_id, do.visibility_id, do.published FROM taxon_concepts tc JOIN hierarchy_entries he ON (tc.id=he.taxon_concept_id) JOIN taxa t ON (he.id=t.hierarchy_entry_id) JOIN data_objects_taxa dot ON (t.id=dot.taxon_id)  JOIN data_objects do ON (dot.data_object_id=do.id) WHERE (tc.supercedure_id IS NULL OR tc.supercedure_id=0) AND (do.published=1 OR do.visibility_id!=".Visibility::find('visible').") AND do.id BETWEEN $i AND ". ($i+$batch_size).")");
            $GLOBALS['db_connection']->end_transaction();
            sleep_production(3);
        }
    }
    
    
    public static function data_objects_taxon_concepts()
    {
        // create a temporary table for this session
        $GLOBALS['db_connection']->query("DROP TABLE IF EXISTS `data_objects_taxon_concepts`");
        $GLOBALS['db_connection']->query("CREATE TABLE `data_objects_taxon_concepts` (
                  `taxon_concept_id` int unsigned NOT NULL,
                  `data_object_id` int unsigned NOT NULL,
                  PRIMARY KEY  (`taxon_concept_id`, `data_object_id`),
                  KEY `data_object_id` (`data_object_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        
        $start = 0;
        $stop = 0;
        $batch_size = 50000;
        $result = $GLOBALS['db_connection']->query("SELECT MIN(id) min, MAX(id) max FROM data_objects");
        if($result && $row=$result->fetch_assoc())
        {
            $start = $row['min'];
            $stop = $row['max'];
        }
        for($i=$start ; $i<$stop ; $i+=$batch_size)
        {
            echo "Inserting ".(($i-$start+$batch_size)/$batch_size)." of ".ceil(($stop-$start)/$batch_size)."\n";
            $GLOBALS['db_connection']->begin_transaction();
            $GLOBALS['db_connection']->query("INSERT IGNORE INTO data_objects_taxon_concepts (SELECT tc.id, do.id FROM taxon_concepts tc JOIN hierarchy_entries he ON (tc.id=he.taxon_concept_id) JOIN taxa t ON (he.id=t.hierarchy_entry_id) JOIN data_objects_taxa dot ON (t.id=dot.taxon_id)  JOIN data_objects do ON (dot.data_object_id=do.id) WHERE (tc.supercedure_id IS NULL OR tc.supercedure_id=0) AND (do.published=1 OR do.visibility_id!=".Visibility::find('visible').") AND do.id BETWEEN $i AND ". ($i+$batch_size).")");
            $GLOBALS['db_connection']->end_transaction();
            sleep_production(3);
        }
    }
    
    public static function taxon_concepts_exploded($select_hierarchy_id = 0)
    {
        $result = $GLOBALS['db_connection']->query("SELECT h.id hierarchy_id, count(*) count FROM hierarchies h JOIN hierarchy_entries he ON (h.id=he.hierarchy_id) GROUP BY h.id ORDER BY count ASC");
        //$result = $GLOBALS['db_connection']->query("SELECT h.id hierarchy_id FROM hierarchies h");
        while($result && $row=$result->fetch_assoc())
        {
            $hierarchy_id = $row['hierarchy_id'];
            if($select_hierarchy_id && $select_hierarchy_id!=$hierarchy_id) continue;
            self::explode_hierarchy($hierarchy_id);
        }
    }
    
    public static function explode_hierarchy($id)
    {
        echo "Exploding $id\n";
        
        // make sure our target table exists
        $GLOBALS['db_connection']->query("CREATE TABLE IF NOT EXISTS `hierarchy_entries_exploded` (
                  `hierarchy_entry_id` int unsigned NOT NULL,
                  `ancestor_hierarchy_entry_id` int unsigned NOT NULL,
                  KEY `hierarchy_entry_id` (`hierarchy_entry_id`),
                  KEY `ancestor_hierarchy_entry_id` (`ancestor_hierarchy_entry_id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
        //$GLOBALS['db_connection']->delete("DELETE hex FROM hierarchy_entries_exploded hex JOIN hierarchy_entries he ON (hex.hierarchy_entry_id=he.id) WHERE he.hierarchy_id=$id");
        
        $FILE = fopen(DOC_ROOT .'temp/hierarchy_entries_exploded.sql', 'w+');
        $i=0;
        $children = array();
        $result = $GLOBALS['db_connection']->query("SELECT id, parent_id FROM  hierarchy_entries he WHERE hierarchy_id=$id");
        while($result && $row=$result->fetch_assoc())
        {
            if($i%10000 == 0 ) echo "Memory: ".memory_get_usage()."\n";
            $i++;
            $children[$row['parent_id']][] = $row['id'];
        }
        if($result) $result->free();
        echo "Memory: ".memory_get_usage()."\n";
        
        
        if(isset($children[0]))
        {
            foreach($children[0] as &$child_id)
            {
                self::explode_recursively($child_id, array(), $children, $FILE);
            }
            unset($child_id);
            unset($children[0]);
        }
        
        fclose($FILE);
        echo "inserting: ".time_elapsed()."\n";
        $GLOBALS['db_connection']->insert("LOAD DATA LOCAL INFILE '". DOC_ROOT ."temp/hierarchy_entries_exploded.sql' IGNORE INTO TABLE hierarchy_entries_exploded");
        //$GLOBALS['db_connection']->load_data_infile(DOC_ROOT .'temp/hierarchy_entries_exploded.sql', "hierarchy_entries_exploded", true, 20);
        unlink(DOC_ROOT .'temp/hierarchy_entries_exploded.sql');
        echo "done inserting: ".time_elapsed()."\n";
    }
    
    public static function explode_recursively(&$id, $parents, &$children, &$FILE)
    {
        static $i=0;
        if($i%10000 == 0 ) echo "Memory r: ".memory_get_usage()."\n";
        $i++;
        
        foreach($parents as &$parent_id)
        {
            fwrite($FILE, "$id\t$parent_id\n");
        }
        unset($parent_id);
        
        if(isset($children[$id]))
        {
            $parents[] = $id;
            foreach($children[$id] as &$child_id)
            {
                self::explode_recursively($child_id, $parents, $children, $FILE);
                unset($child_id);
            }
            unset($child_id);
            unset($children[$id]);
        }
    }
}

?>