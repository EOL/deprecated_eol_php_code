<?php
namespace php_active_record;

class FlattenHierarchies
{
    private $mysqli;
    private $mysqli_slave;
    private $he_tmp_file_path;
    private $HE_OUTFILE;
    private $tc_tmp_file_path;
    private $TC_OUTFILE;
    
    public function __construct()
    {
        $this->mysqli =& $GLOBALS['db_connection'];
        if($GLOBALS['ENV_NAME'] == 'production' && environment_defined('slave')) $this->mysqli_slave = load_mysql_environment('slave');
        else $this->mysqli_slave =& $this->mysqli;
        
        $this->visibile_id = Visibility::visible()->id;
        $this->preview_id = Visibility::preview()->id;
    }
    
    public function begin_process()
    {
        $this->he_tmp_file_path = temp_filepath();
        $this->HE_OUTFILE = fopen($this->he_tmp_file_path, "w+");
        $this->tc_tmp_file_path = temp_filepath();
        $this->TC_OUTFILE = fopen($this->tc_tmp_file_path, "w+");
        
        $this->mysqli->query("DROP TABLE IF EXISTS `hierarchy_entries_flattened_tmp`");
        $this->mysqli->query("CREATE TABLE `hierarchy_entries_flattened_tmp` (
                  `hierarchy_entry_id` int unsigned NOT NULL,
                  `ancestor_id` int unsigned NOT NULL,
                  PRIMARY KEY (`hierarchy_entry_id`, `ancestor_id`),
                  KEY `ancestor_id` (`ancestor_id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
        $this->mysqli->insert("CREATE TABLE IF NOT EXISTS hierarchy_entries_flattened LIKE hierarchy_entries_flattened_tmp");
        
        $this->mysqli->query("DROP TABLE IF EXISTS `taxon_concepts_flattened_tmp`");
        $this->mysqli->query("CREATE TABLE `taxon_concepts_flattened_tmp` (
                  `taxon_concept_id` int unsigned NOT NULL,
                  `ancestor_id` int unsigned NOT NULL,
                  PRIMARY KEY (`taxon_concept_id`, `ancestor_id`),
                  KEY `ancestor_id` (`ancestor_id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
        $this->mysqli->insert("CREATE TABLE IF NOT EXISTS taxon_concepts_flattened LIKE taxon_concepts_flattened_tmp");
        
        $this->flatten_hierarchies_recursive(0, array(), array());
        fclose($this->HE_OUTFILE);
        fclose($this->TC_OUTFILE);
        
        // batches of 250,000 - .8 second pause in between
        $this->mysqli->load_data_infile($this->he_tmp_file_path, 'hierarchy_entries_flattened_tmp', "IGNORE", '', 800000, 250000);
        unlink($this->he_tmp_file_path);
        $this->mysqli->load_data_infile($this->tc_tmp_file_path, 'taxon_concepts_flattened_tmp', "IGNORE", '', 800000, 250000);
        unlink($this->tc_tmp_file_path);
        
        $result = $this->mysqli->query("SELECT 1 FROM hierarchy_entries_flattened_tmp LIMIT 1");
        if($result && $row=$result->fetch_assoc())
        {
            $this->mysqli->swap_tables("hierarchy_entries_flattened", "hierarchy_entries_flattened_tmp");
        }
        $result = $this->mysqli->query("SELECT 1 FROM taxon_concepts_flattened_tmp LIMIT 1");
        if($result && $row=$result->fetch_assoc())
        {
            $this->mysqli->swap_tables("taxon_concepts_flattened", "taxon_concepts_flattened_tmp");
        }
    }
    
    public function flatten_hierarchies_from_concept_id($taxon_concept_id)
    {
        $this->he_tmp_file_path = temp_filepath();
        $this->HE_OUTFILE = fopen($this->he_tmp_file_path, "w+");
        $this->tc_tmp_file_path = temp_filepath();
        $this->TC_OUTFILE = fopen($this->tc_tmp_file_path, "w+");
        
        // delete ALL rows for descendants of this row
        $taxon_concept_ids_to_delete = array();
        $result = $this->mysqli->query("SELECT DISTINCT taxon_concept_id FROM taxon_concepts_flattened
            WHERE ancestor_id = $taxon_concept_id OR taxon_concept_id = $taxon_concept_id");
        while($result && $row=$result->fetch_assoc()) $taxon_concept_ids_to_delete[] = $row['taxon_concept_id'];
        if($taxon_concept_ids_to_delete)
        {
            $batches = array_chunk($taxon_concept_ids_to_delete, 10000);
            foreach($batches as $batch)
            {
                $ids = implode(",", $batch);
                echo "DELETE FROM taxon_concepts_flattened WHERE taxon_concept_id IN ($ids)\n";
                $this->mysqli->delete("DELETE FROM taxon_concepts_flattened WHERE taxon_concept_id IN ($ids)");
                echo "DELETE hef FROM hierarchy_entries he JOIN hierarchy_entries_flattened hef ON (he.id=hef.hierarchy_entry_id) WHERE he.taxon_concept_id IN ($ids)\n";
                $GLOBALS['db_connection']->delete_from_where('hierarchy_entries_flattened', 'hierarchy_entry_id', "SELECT id FROM hierarchy_entries WHERE taxon_concept_id IN ($ids)");
            }
        }
        
        $result = $this->mysqli->query("SELECT DISTINCT c.id child_id, p.id parent_id, p.taxon_concept_id FROM hierarchy_entries c JOIN hierarchy_entries p ON (c.parent_id=p.id) WHERE c.taxon_concept_id = $taxon_concept_id");
        while($result && $row=$result->fetch_assoc())
        {
            $ancestor_entry_ids = array();
            $ancestor_concept_ids = array();
            $result2 = $this->mysqli->query("SELECT ancestor_id FROM hierarchy_entries_flattened WHERE hierarchy_entry_id = ". $row['parent_id']);
            while($result2 && $row2=$result2->fetch_assoc())
            {
                $ancestor_entry_ids[] = $row2['ancestor_id'];
            }
            $ancestor_entry_ids[] = $row['parent_id'];
            
            $result2 = $this->mysqli->query("SELECT ancestor_id FROM taxon_concepts_flattened WHERE taxon_concept_id = ". $row['taxon_concept_id']);
            while($result2 && $row2=$result2->fetch_assoc())
            {
                $ancestor_concept_ids[] = $row2['ancestor_id'];
            }
            $ancestor_concept_ids[] = $row['taxon_concept_id'];
            
            $this->flatten_hierarchies_recursive($row['parent_id'], $ancestor_entry_ids, $ancestor_concept_ids, $row['child_id']);
        }
        
        fclose($this->HE_OUTFILE);
        fclose($this->TC_OUTFILE);
        
        // batches of 250,000 - .8 second pause in between
        $this->mysqli->load_data_infile($this->he_tmp_file_path, 'hierarchy_entries_flattened', "IGNORE", '', 800000, 250000);
        unlink($this->he_tmp_file_path);
        $this->mysqli->load_data_infile($this->tc_tmp_file_path, 'taxon_concepts_flattened', "IGNORE", '', 800000, 250000);
        unlink($this->tc_tmp_file_path);
    }
    
    // takes an optional child_id to only refresh a particular node and its descendants
    private function flatten_hierarchies_recursive($parent_id, $he_ancestors, $tc_ancestors, $child_id = null)
    {
        static $count = 0;
        $count++;
        if($count%1000 == 0) echo "$count: ".time_elapsed()." : ".memory_get_usage()."\n";
        
        //if($count>=10000) exit;
        $query = "SELECT id, parent_id, taxon_concept_id, (rgt-lft) therange FROM hierarchy_entries WHERE parent_id=$parent_id AND published=1 AND visibility_id IN ($this->visibile_id, $this->preview_id)";
        if($child_id) $query .= " AND id = $child_id";
        $result = $this->mysqli_slave->query($query);
        while($result && $row=$result->fetch_assoc())
        {
            if($he_ancestors)
            {
                foreach($he_ancestors as $ancestor_he_id)
                {
                    fwrite($this->HE_OUTFILE, $row['id']."\t".$ancestor_he_id."\n");
                }
            }
            if($tc_ancestors)
            {
                foreach($tc_ancestors as $ancestor_tc_id)
                {
                    fwrite($this->TC_OUTFILE, $row['taxon_concept_id']."\t".$ancestor_tc_id."\n");
                }
            }
            // its not a leaf node
            // leaf nodes have no children so we'll save a bunch of queries by just stopping on leaves
            if($row['therange'] > 1)
            {
                $child_he_ancestors = $he_ancestors;
                $child_he_ancestors[] = $row['id'];
                $child_tc_ancestors = $tc_ancestors;
                $child_tc_ancestors[] = $row['taxon_concept_id'];
                $this->flatten_hierarchies_recursive($row['id'], $child_he_ancestors, $child_tc_ancestors);
            }
        }
    }
    
}

?>

