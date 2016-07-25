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

    // NOTE: I believe I (JRice) have commented out all occurrences of this
    // method. I'm leaving it here to analyze later; I believe it is screwing up
    // hierarchies. ...It *could* be because it was called twice and there could
    // have been some disconnect between master/slave or something. But I've
    // written nice clean, fast code to do this is Ruby, so we can call it there
    // when needed. ...Really it should only be needed after classification
    // curations and harvests.
    public function flatten_hierarchies_from_concept_id($taxon_concept_id)
    {
        $this->create_temporary_files();

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
                $this->mysqli->delete("DELETE FROM taxon_concepts_flattened WHERE taxon_concept_id IN ($ids)");
                $GLOBALS['db_connection']->delete_from_where('hierarchy_entries_flattened', 'hierarchy_entry_id', "SELECT id FROM hierarchy_entries WHERE taxon_concept_id IN ($ids)");
                sleep(count($ids) * 10); // Give the DB some time to recover from that!
            }
        }

        $result = $this->mysqli->query("SELECT DISTINCT c.id child_id, p.id parent_id, p.taxon_concept_id
            FROM hierarchy_entries c
            LEFT JOIN hierarchy_entries p ON (c.parent_id=p.id)
            WHERE c.taxon_concept_id = $taxon_concept_id
            AND c.published=1
            AND c.visibility_id IN ($this->visibile_id, $this->preview_id)
            AND c.vetted_id!=".Vetted::untrusted()->id);
        while($result && $row=$result->fetch_assoc())
        {
            $ancestor_entry_ids = array();
            $ancestor_concept_ids = array();
            if($row['parent_id'])
            {
                $result2 = $this->mysqli->query("SELECT ancestor_id FROM hierarchy_entries_flattened WHERE hierarchy_entry_id = ". $row['parent_id']);
                while($result2 && $row2=$result2->fetch_assoc())
                {
                    $ancestor_entry_ids[] = $row2['ancestor_id'];
                }
                $ancestor_entry_ids[] = $row['parent_id'];
            }
            if($row['taxon_concept_id'])
            {
                $result2 = $this->mysqli->query("SELECT ancestor_id FROM taxon_concepts_flattened WHERE taxon_concept_id = ". $row['taxon_concept_id']);
                while($result2 && $row2=$result2->fetch_assoc())
                {
                    $ancestor_concept_ids[] = $row2['ancestor_id'];
                }
                $ancestor_concept_ids[] = $row['taxon_concept_id'];
            }
            if($row['parent_id']) $this->flatten_hierarchies_recursive($row['parent_id'], $ancestor_entry_ids, $ancestor_concept_ids, $row['child_id']);
            else $this->flatten_hierarchies_recursive($row['child_id'], array($row['child_id']), array($taxon_concept_id));
        }

        $this->load_data_from_temporary_files();
    }

    // takes an optional child_id to only refresh a particular node and its descendants
    private function flatten_hierarchies_recursive($parent_id, $he_ancestors, $tc_ancestors, $child_id = null)
    {
        static $count = 0;
        $count++;
        if($count%1000 == 0 && $GLOBALS['ENV_DEBUG']) echo "$count: ".time_elapsed()." : ".memory_get_usage()."\n";

        $query = "SELECT id, parent_id, taxon_concept_id, (rgt-lft) therange
            FROM hierarchy_entries
            WHERE parent_id=$parent_id
            AND published=1
            AND visibility_id IN ($this->visibile_id, $this->preview_id)
            AND vetted_id!=".Vetted::untrusted()->id;
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




















    public function begin_process($hierarchy_id = null)
    {
        $this->create_temporary_files();

        if($hierarchy_id) $this->delete_rows_from_hierarchy($hierarchy_id);
        else $this->create_temporary_tables();

        $query = "SELECT he.id FROM hierarchy_entries he
            LEFT JOIN hierarchy_entries he_children ON (he.id=he_children.parent_id)
            WHERE he_children.id IS NULL
            AND he.published=1
            AND he.visibility_id IN ($this->visibile_id, $this->preview_id)
            AND he.vetted_id!=".Vetted::untrusted()->id;
        if($hierarchy_id) $query .= " AND he.hierarchy_id=$hierarchy_id";

        $hierarchy_entry_ids = array();
        foreach($this->mysqli->iterate_file($query) as $row)
        {
            $hierarchy_entry_ids[] = $row[0];
            // only keep about 10,000 entries in memory to help with huge hierarchies
            if(count($hierarchy_entry_ids) >= 10000)
            {
                $this->flatten_hierarchy_entries($hierarchy_entry_ids);
                $hierarchy_entry_ids = array();
            }
        }
        // there may be some remainder less than 10,000
        if($hierarchy_entry_ids) $this->flatten_hierarchy_entries($hierarchy_entry_ids);

        // import the data into the DB
        $temp_tables = $hierarchy_id ? false : true;
        $this->load_data_from_temporary_files($temp_tables);

        if(!$hierarchy_id) $this->swap_temporary_tables();
    }

    private function flatten_hierarchy_entries($hierarchy_entry_ids)
    {
        if(!isset($this->number_of_lookups)) $this->number_of_lookups = 0;
        $this->node_metadata = array();  // this will store row data for all nodes and their ancestors
        $ids_to_lookup = array();
        $ids_already_looked_up = array();
        foreach($hierarchy_entry_ids as $id) $ids_to_lookup[$id] = true;
        while($ids_to_lookup)
        {
            $result = $this->mysqli->query("SELECT he.id, he.taxon_concept_id, he.parent_id
                FROM hierarchy_entries he
                WHERE he.id IN (". implode(",", array_keys($ids_to_lookup)) .")
                AND he.published=1
                AND he.visibility_id IN ($this->visibile_id, $this->preview_id)
                AND he.vetted_id!=".Vetted::untrusted()->id);
            foreach($ids_to_lookup as $id) $ids_already_looked_up[$id] = true;
            $ids_to_lookup = array();
            while($result && $row=$result->fetch_assoc())
            {
                $this->node_metadata[$row['id']] = array('parent_id' => $row['parent_id'], 'taxon_concept_id' => $row['taxon_concept_id']);
                if($row['parent_id'] && !@$ids_already_looked_up[$row['parent_id']]) $ids_to_lookup[$row['parent_id']] = true;
            }
        }
        $this->number_of_lookups += count($hierarchy_entry_ids);
        debug("Starting batch $this->number_of_lookups :: mem ". memory_get_usage() ." :: time ". time_elapsed());
        $this->insert_batch();
    }

    private function insert_batch()
    {
        $this->ancestries = array();
        foreach($this->node_metadata as $id => $meta)
        {
            $ancestries = $this->get_ancestries($id);
            if($ancestries === NULL) continue;
            foreach($ancestries['he'] as $ancestor_he_id => $val)
            {
                fwrite($this->HE_OUTFILE, "$id\t$ancestor_he_id\n");
            }
            foreach($ancestries['tc'] as $ancestor_tc_id => $val)
            {
                fwrite($this->TC_OUTFILE, $meta['taxon_concept_id'] ."\t$ancestor_tc_id\n");
            }
        }
    }

    private function get_ancestries($id)
    {
        if(isset($this->ancestries[$id])) return $this->ancestries[$id];

        $ancestry = array('he' => array(), 'tc' => array());
        if(@$this->node_metadata[$id])
        {
            // entries with no parents will no empty ancestries
            if($parent_id = $this->node_metadata[$id]['parent_id'])
            {
                $ancestry = $this->get_ancestries($parent_id);
                // if the parents ancestry is NULL, then there was some problem above
                // and its descendants should be hidden as well
                if($ancestry !== NULL)
                {
                    if(isset($this->node_metadata[$parent_id]))
                    {
                        $parent_metadata = $this->node_metadata[$parent_id];
                        $ancestry['he'][$parent_id] = true;
                        $ancestry['tc'][$parent_metadata['taxon_concept_id']] = true;
                    }else
                    {
                        # the parent is not set, so it must have been hidden, untrusted, or unpublished (or simply does not exist)
                        $ancestry = NULL;
                    }
                }
            }
        }

        $this->ancestries[$id] = $ancestry;
        return $ancestry;
    }

    private function create_temporary_files()
    {
        $this->he_tmp_file_path = temp_filepath();
        if(!($this->HE_OUTFILE = fopen($this->he_tmp_file_path, "w+")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . $this->he_tmp_file_path);
        }
        $this->tc_tmp_file_path = temp_filepath();
        if(!($this->TC_OUTFILE = fopen($this->tc_tmp_file_path, "w+")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . $this->he_tmp_file_path);
        }
    }

    private function load_data_from_temporary_files($temp_tables = false)
    {
        fclose($this->HE_OUTFILE);
        fclose($this->TC_OUTFILE);

        // batches of 500,000 - .2 second pause in between
        $tmp = $temp_tables ? "_tmp" : "";
        $this->mysqli->load_data_infile($this->he_tmp_file_path, 'hierarchy_entries_flattened'.$tmp, "IGNORE", '', 200000, 500000);
        unlink($this->he_tmp_file_path);
        $this->mysqli->load_data_infile($this->tc_tmp_file_path, 'taxon_concepts_flattened'.$tmp, "IGNORE", '', 200000, 500000);
        unlink($this->tc_tmp_file_path);
    }

    private function create_temporary_tables()
    {
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
    }

    private function swap_temporary_tables()
    {
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

    private function delete_rows_from_hierarchy($hierarchy_id)
    {
        // delete ALL flattened entries for this hierarchy
        // Note this isn't touching concepts as only assertions from this hierarchy will be added
        // which means no concept relationships will get deletes - only potential new ones added
        $GLOBALS['db_connection']->delete_from_where('hierarchy_entries_flattened', 'hierarchy_entry_id',
            "SELECT id FROM hierarchy_entries WHERE hierarchy_id=$hierarchy_id");
    }
}

?>
