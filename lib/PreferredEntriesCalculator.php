<?php
namespace php_active_record;

class PreferredEntriesCalculator
{
    private $mysqli;
    
    public function __construct()
    {
        $this->mysqli =& $GLOBALS['db_connection'];
    }
    
    public function begin_processing()
    {
        $this->mysqli->query("DROP TABLE IF EXISTS `taxon_concept_preferred_entries_tmp`");
        $this->mysqli->query("CREATE TABLE `taxon_concept_preferred_entries_tmp` (
            `id` int unsigned NOT NULL auto_increment,
            `taxon_concept_id` int unsigned NOT NULL,
            `hierarchy_entry_id` int unsigned NOT NULL,
            `updated_at` timestamp default NOW(),
            PRIMARY KEY  (`id`),
            UNIQUE  (`taxon_concept_id`)) ENGINE=MyISAM DEFAULT CHARSET=utf8");
        $this->mysqli->insert("CREATE TABLE IF NOT EXISTS taxon_concept_preferred_entries LIKE taxon_concept_preferred_entries_tmp");
        
        $start = 0;
        $max_id = 0;
        $limit = 50000;
        
        $result = $this->mysqli->query("SELECT MIN(id) as min, MAX(id) as max FROM taxon_concepts tc");
        if($result && $row=$result->fetch_assoc())
        {
            $start = $row["min"];
            $max_id = $row["max"];
        }
        
        for($i=$start ; $i<$max_id ; $i+=$limit)
        {
            $this->lookup_block($i, $limit);
        }
        
        $result = $this->mysqli->query("SELECT 1 FROM taxon_concept_preferred_entries_tmp LIMIT 1");
        if($result && $row=$result->fetch_assoc())
        {
            $this->mysqli->swap_tables("taxon_concept_preferred_entries", "taxon_concept_preferred_entries_tmp");
        }
    }
    
    private function lookup_block($start, $limit)
    {
        $query = "SELECT tc.id taxon_concept_id, he.id hierarchy_entry_id, he.visibility_id, he.vetted_id, v.view_order vetted_view_order, h.id hierarchy_id, h.browsable, h.label
            FROM taxon_concepts tc
            STRAIGHT_JOIN hierarchy_entries he ON (tc.id=he.taxon_concept_id)
            STRAIGHT_JOIN hierarchies h ON (he.hierarchy_id=h.id)
            STRAIGHT_JOIN vetted v ON (he.vetted_id=v.id)
            WHERE tc.id BETWEEN $start AND ". ($start+$limit) ." AND he.published=1";
        static $j = 0;
        $all_entries = array();
        foreach($this->mysqli->iterate_file($query) as $row_num => $row)
        {
            if($j % 50000 == 0) echo "$start : $j : ". time_elapsed() ." : ". memory_get_usage() ."\n";
            $j++;
            $taxon_concept_id = $row[0];
            $hierarchy_entry_id = $row[1];
            $visibility_id = $row[2];
            $vetted_id = $row[3];
            $vetted_view_order = $row[4];
            $hierarchy_id = $row[5];
            $browsable = $row[6];
            $label = $row[7];
            if(!$browsable) $browsable = 0;
            if($browsable == 'NULL') $browsable = 0;

            if(@!$all_entries[$taxon_concept_id]) $all_entries[$taxon_concept_id] = array();
            $all_entries[$taxon_concept_id][$hierarchy_id] = array(
                'hierarchy_entry_id' => $hierarchy_entry_id,
                'visibility_id' => $visibility_id,
                'vetted_id' => $vetted_id,
                'vetted_view_order' => $vetted_view_order,
                'hierarchy_id' => $hierarchy_id,
                'browsable' => $browsable,
                'hierarchy_sort_order' => self::hierarchy_sort_order($label));
        }
        $curated_best_entries = $this->lookup_curated_best_entries($start, $limit);
        $this->sort_and_insert_best_entries($all_entries, $curated_best_entries);
    }
    
    private function lookup_curated_best_entries($start, $limit)
    {
        $curated_best_entries = array();
        $query = "SELECT c.taxon_concept_id, c.hierarchy_entry_id
            FROM curated_taxon_concept_preferred_entries c
            JOIN hierarchy_entries he ON (c.hierarchy_entry_id=he.id)
            WHERE c.taxon_concept_id BETWEEN $start AND ". ($start+$limit) ." AND he.published=1 AND he.visibility_id=". Visibility::visible()->id;
        foreach($this->mysqli->iterate_file($query) as $row_num => $row)
        {
            $taxon_concept_id = $row[0];
            $hierarchy_entry_id = $row[1];
            $curated_best_entries[$taxon_concept_id] = $hierarchy_entry_id;
        }
        return $curated_best_entries;
    }
    
    private function sort_and_insert_best_entries($all_entries, $curated_entries)
    {
        $best_entry_for_concept = array();
        foreach($all_entries as $taxon_concept_id => $concept_entries)
        {
            usort($concept_entries, array("php_active_record\PreferredEntriesCalculator", "sort_preferred_entries"));
            $first = array_shift($concept_entries);
            $best_entry_for_concept[$taxon_concept_id] = $first['hierarchy_entry_id'];
        }
        foreach($curated_entries as $taxon_concept_id => $hierarchy_entry_id)
        {
            // its possible to have a saved curated entry for a concept that no longer exits
            // so make sure we are setting the preferred value for a concept that we know about
            if(isset($best_entry_for_concept[$taxon_concept_id]) &&
                $best_entry_for_concept[$taxon_concept_id])
            {
                $best_entry_for_concept[$taxon_concept_id] = $hierarchy_entry_id;
            }
        }
        $this->insert_best_entries($best_entry_for_concept);
    }
    
    
    private function insert_best_entries($best_entry_for_concept)
    {
        $tmp_file_path = DOC_ROOT . "temp/preferred_entries.tmp";
        if(!($TMP_FILE = fopen($tmp_file_path, "w+")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . $tmp_file_path);
          return;
        }
        foreach($best_entry_for_concept as $taxon_concept_id => $hierarchy_entry_id)
        {
            fwrite($TMP_FILE, "NULL\t$taxon_concept_id\t$hierarchy_entry_id\n");
        }
        fclose($TMP_FILE);
        // sleep(600);
        $this->mysqli->load_data_infile($tmp_file_path, 'taxon_concept_preferred_entries_tmp');
        unlink($tmp_file_path);
    }
    
    private static function sort_preferred_entries($a, $b)
    {
        if($a['vetted_view_order'] != $b['vetted_view_order']) return ($a['vetted_view_order'] < $b['vetted_view_order']) ? -1 : 1; // ascending
        if($a['browsable'] != $b['browsable']) return ($a['browsable'] < $b['browsable']) ? 1 : -1; // descending
        if($a['hierarchy_sort_order'] != $b['hierarchy_sort_order']) return ($a['hierarchy_sort_order'] < $b['hierarchy_sort_order']) ? -1 : 1; // ascending
        return ($a['hierarchy_entry_id'] < $b['hierarchy_entry_id']) ? -1 : 1;  // ascending
    }
    
    private static function hierarchy_sort_order($hierarchy_label)
    {
        if(preg_match("/^Species 2000 & ITIS Catalogue of Life/i", $hierarchy_label)) return 1;
        elseif(preg_match("/^Integrated Taxonomic Information System/i", $hierarchy_label)) return 2;
        elseif($hierarchy_label == "Avibase - IOC World Bird Names (2011)") return 3;
        elseif($hierarchy_label == "WORMS Species Information (Marine Species)") return 4;
        elseif($hierarchy_label == "FishBase (Fish Species)") return 5;
        elseif($hierarchy_label == "IUCN Red List (Species Assessed for Global Conservation)") return 6;
        elseif($hierarchy_label == "Index Fungorum") return 7;
        elseif($hierarchy_label == "Paleobiology Database") return 8;
        return 999;
    }
}

?>
