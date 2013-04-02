<?php
namespace php_active_record;

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
            debug("Inserting ".(($i-$start+$batch_size)/$batch_size)." of ".ceil(($stop-$start)/$batch_size));
            $outfile = $GLOBALS['db_connection']->select_into_outfile("SELECT tc.id, do.data_type_id, dohe.visibility_id, do.published FROM taxon_concepts tc JOIN hierarchy_entries he ON (tc.id=he.taxon_concept_id) JOIN data_objects_hierarchy_entries dohe ON (he.id=dohe.hierarchy_entry_id) JOIN data_objects do ON (dohe.data_object_id=do.id) WHERE (tc.supercedure_id IS NULL OR tc.supercedure_id=0) AND (do.published=1 OR dohe.visibility_id!=".Visibility::visible()->id.") AND do.id BETWEEN $i AND ". ($i+$batch_size));
            
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
            debug("Inserting ".(($i-$start+$batch_size)/$batch_size)." of ".ceil(($stop-$start)/$batch_size));
            $outfile = $GLOBALS['db_connection']->select_into_outfile("SELECT tc.id, do.id FROM taxon_concepts tc JOIN hierarchy_entries he ON (tc.id=he.taxon_concept_id) JOIN data_objects_hierarchy_entries dohe ON (he.id=dohe.hierarchy_entry_id) JOIN data_objects do ON (dohe.data_object_id=do.id) WHERE (tc.supercedure_id IS NULL OR tc.supercedure_id=0) AND (do.published=1 OR dohe.visibility_id!=".Visibility::visible()->id.") AND do.id BETWEEN $i AND ". ($i+$batch_size));
            $GLOBALS['db_connection']->load_data_infile($outfile, 'data_objects_taxon_concepts_tmp');
            unlink($outfile);
            
            $outfile = $GLOBALS['db_connection']->select_into_outfile("SELECT tc.id, do.id FROM taxon_concepts tc JOIN hierarchy_entries he ON (tc.id=he.taxon_concept_id) JOIN curated_data_objects_hierarchy_entries cdohe ON (he.id=cdohe.hierarchy_entry_id) JOIN data_objects do ON (cdohe.data_object_id=do.id) WHERE (tc.supercedure_id IS NULL OR tc.supercedure_id=0) AND (do.published=1 OR cdohe.visibility_id!=".Visibility::visible()->id.") AND do.id BETWEEN $i AND ". ($i+$batch_size));
            $GLOBALS['db_connection']->load_data_infile($outfile, 'data_objects_taxon_concepts_tmp');
            unlink($outfile);
            
            $outfile = $GLOBALS['db_connection']->select_into_outfile("SELECT tc.id, do.id FROM taxon_concepts tc JOIN users_data_objects udo ON (tc.id=udo.taxon_concept_id) JOIN data_objects do ON (udo.data_object_id=do.id) WHERE (tc.supercedure_id IS NULL OR tc.supercedure_id=0) AND (do.published=1 OR udo.visibility_id!=".Visibility::visible()->id.") AND do.id BETWEEN $i AND ". ($i+$batch_size));
            $GLOBALS['db_connection']->load_data_infile($outfile, 'data_objects_taxon_concepts_tmp');
            unlink($outfile);
        }
        
        $result = $GLOBALS['db_connection']->query("SELECT 1 FROM data_objects_taxon_concepts_tmp LIMIT 1");
        if($result && $row=$result->fetch_assoc())
        {
            $GLOBALS['db_connection']->swap_tables("data_objects_taxon_concepts", "data_objects_taxon_concepts_tmp");
        }
    }
}

?>