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
            sleep(3);
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
            sleep(3);
        }
    }
}

?>