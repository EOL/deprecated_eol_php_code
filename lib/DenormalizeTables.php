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
            $outfile = $GLOBALS['db_connection']->select_into_outfile("SELECT dotc.taxon_concept_id, do.data_type_id, do.visibility_id, do.published FROM data_objects_taxon_concepts dotc JOIN data_objects do ON (dotc.data_object_id=do.id) WHERE do.id BETWEEN $i AND ". ($i+$batch_size));
            $GLOBALS['db_connection']->load_data_infile($outfile, 'data_types_taxon_concepts');
            unlink($outfile);
            
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
            $outfile = $GLOBALS['db_connection']->select_into_outfile("SELECT tc.id, do.id FROM taxon_concepts tc JOIN hierarchy_entries he ON (tc.id=he.taxon_concept_id) JOIN taxa t ON (he.id=t.hierarchy_entry_id) JOIN data_objects_taxa dot ON (t.id=dot.taxon_id) JOIN data_objects do ON (dot.data_object_id=do.id) WHERE (tc.supercedure_id IS NULL OR tc.supercedure_id=0) AND (do.published=1 OR do.visibility_id!=".Visibility::find('visible').") AND do.id BETWEEN $i AND ". ($i+$batch_size));
            $GLOBALS['db_connection']->load_data_infile($outfile, 'data_objects_taxon_concepts');
            unlink($outfile);
            
            sleep_production(3);
        }
    }
    
    public static function taxon_concepts_exploded($select_hierarchy_id = 0)
    {
        // do the small ones first
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
        //$GLOBALS['db_connection']->delete('TRUNCATE TABLE hierarchy_entries_exploded');
        //$GLOBALS['db_connection']->delete("DELETE hex FROM hierarchy_entries_exploded hex JOIN hierarchy_entries he ON (hex.hierarchy_entry_id=he.id) WHERE he.hierarchy_id=$id");
        
        $GLOBALS['ids_with_content'] = array();
        $result = $GLOBALS['db_connection']->query("SELECT he.id FROM hierarchies_content hc JOIN hierarchy_entries he ON (hc.hierarchy_entry_id=he.id) WHERE he.hierarchy_id=$id AND (hc.text=1 OR hc.image=1 OR hc.text_unpublished=1 OR hc.image_unpublished=1 OR hc.flash=1 OR hc.youtube=1)");
        while($result && $row=$result->fetch_assoc())
        {
            $GLOBALS['ids_with_content'][$row['id']] = 1;
        }
        if(!$GLOBALS['ids_with_content']) return false;
        
        $i=0;
        $children = array();
        $outfile = $GLOBALS['db_connection']->select_into_outfile("SELECT id, parent_id FROM  hierarchy_entries he WHERE hierarchy_id=$id");
        $RESULT = fopen($outfile, "r");
        while(!feof($RESULT))
        {
            if($line = fgets($RESULT, 4096))
            {
                if($i%10000 == 0 ) echo "Memory: ".memory_get_usage()."\n";
                $i++;
                
                $parts = explode("\t", trim($line));
                $children[$parts[1]][] = $parts[0];
            }
        }
        unlink($outfile);
        echo "Memory: ".memory_get_usage()."\n";
        
        $FILE = fopen(DOC_ROOT .'temp/hierarchy_entries_exploded.sql', 'w+');
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
        $GLOBALS['db_connection']->load_data_infile(DOC_ROOT .'temp/hierarchy_entries_exploded.sql', "hierarchy_entries_exploded");
        unlink(DOC_ROOT .'temp/hierarchy_entries_exploded.sql');
        echo "done inserting: ".time_elapsed()."\n";
    }
    
    public static function explode_recursively(&$id, $parents, &$children, &$FILE)
    {
        static $i=0;
        if($i%10000 == 0 ) echo "Memory r: ".memory_get_usage()."\n";
        $i++;
        
        if(isset($GLOBALS['ids_with_content'][$id]))
        {
            // everything is in its own path
            $str = "$id\t$id\n";
            foreach($parents as &$parent_id)
            {
                $str .= "$id\t$parent_id\n";
            }
            unset($parent_id);
            fwrite($FILE, $str);
        }
        
        if(isset($children[$id]))
        {
            $parents[] = $id;
            foreach($children[$id] as &$child_id)
            {
                self::explode_recursively($child_id, $parents, $children, $FILE);
            }
            unset($child_id);
            unset($children[$id]);
        }
    }
}

?>