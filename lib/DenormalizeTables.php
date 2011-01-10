<?php

class DenormalizeTables
{
    public static function data_types_taxon_concepts()
    {
        // create a temporary table for this session
        $GLOBALS['db_connection']->query("DROP TABLE IF EXISTS `data_types_taxon_concepts_tmp`");
        $GLOBALS['db_connection']->query("CREATE TABLE `data_types_taxon_concepts_tmp` (
                  `taxon_concept_id` int unsigned NOT NULL,
                  `data_type_id` smallint unsigned NOT NULL,
                  `visibility_id` smallint unsigned NOT NULL,
                  `published` tinyint unsigned NOT NULL,
                  PRIMARY KEY  (`taxon_concept_id`,`data_type_id`, `visibility_id`, `published`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        $GLOBALS['db_connection']->insert("CREATE TABLE IF NOT EXISTS data_types_taxon_concepts LIKE data_types_taxon_concepts_tmp");
        
        // $GLOBALS['db_connection']->insert("CREATE TABLE IF NOT EXISTS data_types_taxon_concepts_tmp LIKE data_types_taxon_concepts");
        // $GLOBALS['db_connection']->delete("TRUNCATE TABLE data_types_taxon_concepts_tmp");
        
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
            $GLOBALS['db_connection']->load_data_infile($outfile, 'data_types_taxon_concepts_tmp');
            unlink($outfile);
        }
        
        $result = $GLOBALS['db_connection']->query("SELECT 1 FROM data_types_taxon_concepts_tmp LIMIT 1");
        if($result && $row=$result->fetch_assoc())
        {
            $GLOBALS['db_connection']->swap_tables("data_types_taxon_concepts", "data_types_taxon_concepts_tmp");
        }
    }
    
    
    public static function data_objects_taxon_concepts()
    {
        // create a temporary table for this session
        $GLOBALS['db_connection']->query("DROP TABLE IF EXISTS `data_objects_taxon_concepts_tmp`");
        $GLOBALS['db_connection']->query("CREATE TABLE `data_objects_taxon_concepts_tmp` (
                  `taxon_concept_id` int unsigned NOT NULL,
                  `data_object_id` int unsigned NOT NULL,
                  PRIMARY KEY  (`taxon_concept_id`, `data_object_id`),
                  KEY `data_object_id` (`data_object_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        $GLOBALS['db_connection']->insert("CREATE TABLE IF NOT EXISTS data_objects_taxon_concepts LIKE data_objects_taxon_concepts_tmp");
        
        // $GLOBALS['db_connection']->insert("CREATE TABLE IF NOT EXISTS data_objects_taxon_concepts_tmp LIKE data_objects_taxon_concepts");
        // $GLOBALS['db_connection']->delete("TRUNCATE TABLE data_objects_taxon_concepts_tmp");
        
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
            $outfile = $GLOBALS['db_connection']->select_into_outfile("SELECT tc.id, do.id FROM taxon_concepts tc JOIN hierarchy_entries he ON (tc.id=he.taxon_concept_id) JOIN data_objects_hierarchy_entries dohe ON (he.id=dohe.hierarchy_entry_id) JOIN data_objects do ON (dohe.data_object_id=do.id) WHERE (tc.supercedure_id IS NULL OR tc.supercedure_id=0) AND (do.published=1 OR do.visibility_id!=".Visibility::find('visible').") AND do.id BETWEEN $i AND ". ($i+$batch_size));
            $GLOBALS['db_connection']->load_data_infile($outfile, 'data_objects_taxon_concepts_tmp');
            unlink($outfile);
        }
        
        $result = $GLOBALS['db_connection']->query("SELECT 1 FROM data_objects_taxon_concepts_tmp LIMIT 1");
        if($result && $row=$result->fetch_assoc())
        {
            $GLOBALS['db_connection']->swap_tables("data_objects_taxon_concepts", "data_objects_taxon_concepts_tmp");
        }
    }
    
    public static function taxon_concepts_exploded($select_hierarchy_id = 0)
    {
        $GLOBALS['db_connection']->query("DROP TABLE IF EXISTS `taxon_concepts_exploded_tmp`");
        $GLOBALS['db_connection']->query("CREATE TABLE `taxon_concepts_exploded_tmp` (
                  `taxon_concept_id` int unsigned NOT NULL,
                  `parent_id` int unsigned NOT NULL,
                  PRIMARY KEY (`taxon_concept_id`),
                  KEY `parent_id` (`parent_id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
        $GLOBALS['db_connection']->insert("CREATE TABLE IF NOT EXISTS taxon_concepts_exploded LIKE taxon_concepts_exploded_tmp");
        
        // $GLOBALS['db_connection']->insert("CREATE TABLE IF NOT EXISTS taxon_concepts_exploded_tmp LIKE taxon_concepts_exploded");
        // $GLOBALS['db_connection']->delete("TRUNCATE TABLE taxon_concepts_exploded_tmp");
        
        # TODO - dont hardcode IDs
        # COL 2007, 2008, Flickr, GBIF, COL 2009, BioLib.cz, Indiana Dunes
        $hierarchies_to_ignore = array(105,106,114,129,147,394,399);
        
        // do the big ones first
        $result = $GLOBALS['db_connection']->query("SELECT h.id hierarchy_id, count(*) count  FROM hierarchies h JOIN hierarchy_entries he ON (h.id=he.hierarchy_id) GROUP BY h.id ORDER BY count DESC");
        //$result = $GLOBALS['db_connection']->query("SELECT h.id hierarchy_id FROM hierarchies h");
        while($result && $row=$result->fetch_assoc())
        {
            $hierarchy_id = $row['hierarchy_id'];
            if(in_array($hierarchy_id, $hierarchies_to_ignore)) continue;
            if($select_hierarchy_id && $select_hierarchy_id!=$hierarchy_id) continue;
            self::taxon_concept_explode_hierarchy($hierarchy_id);
        }
        
        $result = $GLOBALS['db_connection']->query("SELECT 1 FROM taxon_concepts_exploded_tmp LIMIT 1");
        if($result && $row=$result->fetch_assoc())
        {
            $GLOBALS['db_connection']->swap_tables("taxon_concepts_exploded", "taxon_concepts_exploded_tmp");
        }
    }
    
    public static function taxon_concept_explode_hierarchy($id)
    {
        echo "Exploding $id\n";
        
        // get all concept_id, parent_concept_id which wont create loops
        $outfile = $GLOBALS['db_connection']->select_into_outfile("SELECT he.taxon_concept_id, he_parent.taxon_concept_id FROM hierarchy_entries he LEFT JOIN hierarchy_entries he_parent ON (he.parent_id=he_parent.id) LEFT JOIN taxon_concepts_exploded_tmp tcx ON (he.taxon_concept_id=tcx.parent_id AND tcx.taxon_concept_id=he_parent.taxon_concept_id) WHERE he.hierarchy_id=$id AND tcx.taxon_concept_id IS NULL AND he.taxon_concept_id != he_parent.taxon_concept_id");
        $GLOBALS['db_connection']->load_data_infile($outfile, 'taxon_concepts_exploded_tmp');
        unlink($outfile);
    }
    
    public static function flatten_hierarchies()
    {
        $GLOBALS['db_connection']->query("DROP TABLE IF EXISTS `hierarchy_entries_flattened_tmp`");
        $GLOBALS['db_connection']->query("CREATE TABLE `hierarchy_entries_flattened_tmp` (
                  `hierarchy_entry_id` int unsigned NOT NULL,
                  `ancestor_id` int unsigned NOT NULL,
                  PRIMARY KEY (`hierarchy_entry_id`, `ancestor_id`),
                  KEY `ancestor_id` (`ancestor_id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
        $GLOBALS['db_connection']->insert("CREATE TABLE IF NOT EXISTS hierarchy_entries_flattened LIKE hierarchy_entries_flattened_tmp");
        
        $GLOBALS['db_connection']->query("DROP TABLE IF EXISTS `taxon_concepts_flattened_tmp`");
        $GLOBALS['db_connection']->query("CREATE TABLE `taxon_concepts_flattened_tmp` (
                  `taxon_concept_id` int unsigned NOT NULL,
                  `ancestor_id` int unsigned NOT NULL,
                  PRIMARY KEY (`taxon_concept_id`, `ancestor_id`),
                  KEY `ancestor_id` (`ancestor_id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
        $GLOBALS['db_connection']->insert("CREATE TABLE IF NOT EXISTS taxon_concepts_flattened LIKE taxon_concepts_flattened_tmp");
        
        $he_tmp_file_path = temp_filepath();
        $HE_OUTFILE = fopen($he_tmp_file_path, "w+");
        $tc_tmp_file_path = temp_filepath();
        $TC_OUTFILE = fopen($tc_tmp_file_path, "w+");
        self::flatten_hierarchies_recursive(0, array(), array(), $HE_OUTFILE, $TC_OUTFILE);
        fclose($HE_OUTFILE);
        fclose($TC_OUTFILE);
        
        $GLOBALS['db_connection']->load_data_infile($he_tmp_file_path, 'hierarchy_entries_flattened_tmp');
        unlink($he_tmp_file_path);
        $GLOBALS['db_connection']->load_data_infile($tc_tmp_file_path, 'taxon_concepts_flattened_tmp');
        unlink($tc_tmp_file_path);
        
        $result = $GLOBALS['db_connection']->query("SELECT 1 FROM hierarchy_entries_flattened_tmp LIMIT 1");
        if($result && $row=$result->fetch_assoc())
        {
            $GLOBALS['db_connection']->swap_tables("hierarchy_entries_flattened", "hierarchy_entries_flattened_tmp");
        }
        $result = $GLOBALS['db_connection']->query("SELECT 1 FROM taxon_concepts_flattened_tmp LIMIT 1");
        if($result && $row=$result->fetch_assoc())
        {
            $GLOBALS['db_connection']->swap_tables("taxon_concepts_flattened", "taxon_concepts_flattened_tmp");
        }
    }
    
    public static function flatten_hierarchies_recursive($parent_id, $he_ancestors, $tc_ancestors, $HE_OUTFILE, $TC_OUTFILE)
    {
        static $count = 0;
        $count++;
        if($count%1000 == 0) echo "$count: ".time_elapsed()." : ".memory_get_usage()."\n";
        
        //if($count>=10000) exit;
        $result = $GLOBALS['db_connection']->query("SELECT id, parent_id, taxon_concept_id, (rgt-lft) range FROM hierarchy_entries WHERE parent_id=$parent_id AND published=1 AND visibility_id IN (".Visibility::find('visible').",".Visibility::find('preview').")");
        while($result && $row=$result->fetch_assoc())
        {
            if($he_ancestors)
            {
                foreach($he_ancestors as $ancestor_he_id)
                {
                    fwrite($HE_OUTFILE, $row['id']."\t".$ancestor_he_id."\n");
                }
            }
            if($tc_ancestors)
            {
                foreach($tc_ancestors as $ancestor_tc_id)
                {
                    fwrite($TC_OUTFILE, $row['taxon_concept_id']."\t".$ancestor_tc_id."\n");
                }
            }
            // its not a leaf node
            // leaf nodes have no children so we'll save a bunch of queries by just stopping on leaves
            if($row['range'] > 1)
            {
                $child_he_ancestors = $he_ancestors;
                $child_he_ancestors[] = $row['id'];
                $child_tc_ancestors = $tc_ancestors;
                $child_tc_ancestors[] = $row['taxon_concept_id'];
                self::flatten_hierarchies_recursive($row['id'], $child_he_ancestors, $child_tc_ancestors, $HE_OUTFILE, $TC_OUTFILE);
            }
        }
    }
}

?>