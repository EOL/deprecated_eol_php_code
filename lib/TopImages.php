<?php

class TopImages
{
    private $mysqli;
    private $vetted_sort_orders;
    private $TOP_IMAGES_FILE;
    private $TOP_UNPUBLISHED_IMAGES;
    private $iteration_size = 100000;
    
    public function __construct()
    {
        $this->mysqli =& $GLOBALS['db_connection'];
        
        $this->vetted_sort_orders =  array();
        $this->vetted_sort_orders[Vetted::find('Trusted')] = 1;
        $this->vetted_sort_orders[Vetted::find('Unknown')] = 2;
        $this->vetted_sort_orders[Vetted::find('Untrusted')] = 3;
    }
    
    public function begin_process()
    {
        $start = 0;
        $max_id = 0;
        
        $result = $this->mysqli->query("SELECT MIN(id) as min, MAX(id) as max FROM hierarchy_entries he");
        if($result && $row=$result->fetch_assoc())
        {
            $start = $row["min"];
            $max_id = $row["max"];
        }
        
        // create and flush the temporary tables
        $this->mysqli->insert("CREATE TABLE IF NOT EXISTS top_images_tmp LIKE top_images");
        $this->mysqli->delete("TRUNCATE TABLE top_images_tmp");
        $this->mysqli->insert("CREATE TABLE IF NOT EXISTS top_unpublished_images_tmp LIKE top_unpublished_images");
        $this->mysqli->delete("TRUNCATE TABLE top_unpublished_images_tmp");
        
        $this->TOP_IMAGES_FILE = fopen(DOC_ROOT . "temp/top_images.sql", "w+");
        $this->TOP_UNPUBLISHED_IMAGES = fopen(DOC_ROOT . "temp/top_unpublished_images.sql", "w+");
        
        $all_parent_ids = array();
        for($i=$start ; $i<$max_id ; $i+=$this->iteration_size)
        {
            echo "Query ". (($i-$start+$this->iteration_size)/$this->iteration_size) ." of ". (ceil(($max_id-$start)/$this->iteration_size)) ."\n";
            $image_type_id = DataType::find("http://purl.org/dc/dcmitype/StillImage");
            
            echo "Memory: ".memory_get_usage()."\n";
            $outfile = $this->mysqli->select_into_outfile("
                SELECT he.id hierarchy_entry_id, he.parent_id, do.id, do.data_rating, do.visibility_id, do.vetted_id, do.published
                    FROM data_objects_taxon_concepts dotc
                    JOIN data_objects do ON (dotc.data_object_id=do.id)
                    JOIN hierarchy_entries he ON (dotc.taxon_concept_id=he.taxon_concept_id)
                    WHERE he.id BETWEEN $i AND ".($i+$this->iteration_size)."
                    AND he.hierarchy_id!=399
                    AND he.hierarchy_id!=105
                    AND he.hierarchy_id!=106
                    AND he.hierarchy_id!=129
                    AND he.hierarchy_id!=394
                    AND do.data_type_id=$image_type_id
                    AND (do.published=1 OR do.visibility_id!=".Visibility::find('visible').")
                    AND (he.published=1 OR he.visibility_id=".Visibility::find('Preview').")
                    ORDER BY he.id");
            echo "Memory: ".memory_get_usage()."\n";
            
            $parent_ids = $this->get_data_from_result($outfile, false);
            $all_parent_ids = array_merge($all_parent_ids, array_keys($parent_ids));
        }
        
        // load all the info we just gathered
        fclose($this->TOP_IMAGES_FILE);
        fclose($this->TOP_UNPUBLISHED_IMAGES);
        $this->load_data_from_files();
        
        // now load data for all the parents of taxa with images
        if($all_parent_ids) $this->process_parents($all_parent_ids);
        
        // clean up tmp files and swap tmp tables with live ones
        $this->end_load_data();
    }
    
    public function process_parents($parent_ids)
    {
        // truncate the temporary files
        $this->TOP_IMAGES_FILE = fopen(DOC_ROOT . "temp/top_images.sql", "w+");
        $this->TOP_UNPUBLISHED_IMAGES = fopen(DOC_ROOT . "temp/top_unpublished_images.sql", "w+");
        
        $i = 0;
        unset($all_parent_ids);
        $all_parent_ids = array();
        $chunks = array_chunk($parent_ids, 7000);
        while(list($key, $chunk) = each($chunks))
        {
            $i++;
            echo "Chunk $i of ". count($chunks) ."\n";
            $image_type_id = DataType::find("http://purl.org/dc/dcmitype/StillImage");
            
            // searches for all images for THIS concept, same as above
            // but also searches top_images for the best from its decendants
            $outfile = $this->mysqli->select_into_outfile("
            (SELECT he.id hierarchy_entry_id, he.parent_id, do.id, do.data_rating, do.visibility_id, do.vetted_id, do.published
                FROM hierarchy_entries he
                JOIN top_images_tmp ti ON (he.id=ti.hierarchy_entry_id)
                JOIN data_objects do ON (ti.data_object_id=do.id)
                WHERE he.id IN (". implode($chunk, ",") .")
                AND do.data_type_id=$image_type_id
                AND (do.published=1 OR do.visibility_id!=".Visibility::find('visible').")
                AND (he.published=1 OR he.visibility_id=".Visibility::find('Preview')."))
            UNION
            (SELECT he.id hierarchy_entry_id, he.parent_id, do.id, do.data_rating, do.visibility_id, do.vetted_id, do.published
                FROM hierarchy_entries he
                JOIN hierarchy_entries he_children ON (he.id=he_children.parent_id)
                JOIN top_images_tmp ti ON (he_children.id=ti.hierarchy_entry_id)
                JOIN data_objects do ON (ti.data_object_id=do.id)
                WHERE he.id IN (". implode($chunk, ",") .")
                AND do.data_type_id=$image_type_id
                AND (do.published=1 OR do.visibility_id!=".Visibility::find('visible').")
                AND (he.published=1 OR he.visibility_id=".Visibility::find('Preview')."))
            ORDER BY hierarchy_entry_id");
            echo "Memory: ".memory_get_usage()."\n";
            
            $parent_ids = $this->get_data_from_result($outfile);
            $all_parent_ids = array_merge($all_parent_ids, array_keys($parent_ids));
        }
        // load all info from this series of ancestry
        fclose($this->TOP_IMAGES_FILE);
        fclose($this->TOP_UNPUBLISHED_IMAGES);
        $this->load_data_from_files();
        
        // get data for the next ancestor level
        if($all_parent_ids) $this->process_parents($all_parent_ids);
    }
    
    public function top_concept_images($published = false)
    {
        $OUT = fopen(DOC_ROOT . "temp/top_concept_images.sql", "w+");
        
        if($published)
        {
            $table_name = 'top_concept_images';
            $select_table_name = 'top_images';
        }else
        {
            $table_name = 'top_unpublished_concept_images';
            $select_table_name = 'top_unpublished_images';
        }
        
        $start = 0;
        $stop = 0;
        $batch_size = 100000;
        
        $result = $this->mysqli->query("SELECT MIN(he.taxon_concept_id) min, MAX(he.taxon_concept_id) max FROM $select_table_name ti JOIN hierarchy_entries he ON (ti.hierarchy_entry_id=he.id)");
        if($result && $row=$result->fetch_assoc())
        {
            $start = $row['min'];
            $stop = $row['max'];
        }
        
        for($i=$start ; $i<$stop ; $i+=$batch_size)
        {
            echo "Page ".(($i-$start+$batch_size)/$batch_size)." of ".ceil(($stop-$start)/$batch_size)."\n";
            
            $last_taxon_concept_id = 0;
            $top_images = array();
            $result = $this->mysqli->query("SELECT  he.taxon_concept_id, do.id data_object_id, v.view_order vetted_sort_order, do.data_rating FROM hierarchy_entries he JOIN $select_table_name ti ON (he.id= ti.hierarchy_entry_id) JOIN data_objects do ON (ti.data_object_id=do.id) JOIN vetted v ON (do.vetted_id=v.id) WHERE he.taxon_concept_id BETWEEN $i  AND ". ($i+$batch_size)." ORDER BY he.taxon_concept_id");
            while($result && $row=$result->fetch_assoc())
            {
                $taxon_concept_id = $row['taxon_concept_id'];
                $data_object_id = $row['data_object_id'];
                $vetted_sort_order = $row['vetted_sort_order'];
                $data_rating = $row['data_rating'];
                
                if($taxon_concept_id != $last_taxon_concept_id)
                {
                    $last_taxon_concept_id = $taxon_concept_id;
                    if($top_images) self::write_sorted_results_to_file($top_images, $OUT);
                    unset($top_images);
                    $top_images = array();
                }
                $top_images[$vetted_sort_order][$data_rating][$data_object_id] = "$taxon_concept_id\t$data_object_id";
            }
            if($top_images) self::write_sorted_results_to_file($top_images, $OUT);
        }
        fclose($OUT);
        
        if(filesize(DOC_ROOT ."temp/top_concept_images.sql"))
        {
            $tmp_table_name = $table_name."_tmp";
            $this->mysqli->insert("CREATE TABLE IF NOT EXISTS $tmp_table_name LIKE $table_name");
            $this->mysqli->delete("TRUNCATE TABLE $tmp_table_name");
            $this->mysqli->load_data_infile(DOC_ROOT ."temp/top_concept_images.sql", $tmp_table_name);
            
            $swap_table_name = $table_name."_swap";
            $this->mysqli->update("RENAME TABLE $table_name TO $swap_table_name,
                                                $tmp_table_name TO $table_name,
                                                $swap_table_name TO $tmp_table_name");
            
        }
        unlink(DOC_ROOT ."temp/top_concept_images.sql");
    }
    
    
    function get_data_from_result($outfile, $delete = true)
    {
        $i = 0;
        $parent_ids = array();
        $last_hierarchy_entry_id = 0;
        $top_images = array();
        $top_unpublished_images = array();
        $hierarchy_entry_ids = array();
        
        $visible_id = Visibility::find("visible");
        $RESULT = fopen($outfile, "r");
        while(!feof($RESULT))
        {
            if($line = fgets($RESULT, 4096))
            {
                if($i%2000==0) echo "Memory: ".memory_get_usage()."\n";
                $i++;
                //he.id hierarchy_entry_id, he.parent_id, do.id, do.data_rating, do.visibility_id, do.vetted_id, do.published
                $fields = explode("\t", trim($line));
                $hierarchy_entry_id = $fields[0];
                $parent_id = $fields[1];
                $data_object_id = $fields[2];
                $data_rating = $fields[3];
                $visibility_id = $fields[4];
                $vetted_id = $fields[5];
                $published = $fields[6];
                if($parent_id) $parent_ids[$parent_id] = 1;
                
                // this is a new entry so commit existing data before adding more
                if($hierarchy_entry_id != $last_hierarchy_entry_id)
                {
                    $this->process_top_images($top_images, $top_unpublished_images);
                    if($top_images) $hierarchy_entry_ids[] = $last_hierarchy_entry_id;
                    $last_hierarchy_entry_id = $hierarchy_entry_id;
                    unset($top_images);
                    unset($top_unpublished_images);
                    unset($used_data_objects);
                    $top_images = array();
                    $top_unpublished_images = array();
                    $used_data_objects = array();
                }
                
                if(isset($used_data_objects[$data_object_id])) continue;
                $used_data_objects[$data_object_id] = 1;
                
                $vetted_sort_order = isset($this->vetted_sort_orders[$vetted_id]) ? $this->vetted_sort_orders[$vetted_id] : 5;
                if($visibility_id==$visible_id && $published==1) $top_images[$vetted_sort_order][$data_rating][$data_object_id] = "$hierarchy_entry_id\t$data_object_id";
                else $top_unpublished_images[$vetted_sort_order][$data_rating][$data_object_id] = "$hierarchy_entry_id\t$data_object_id";
            }
        }
        fclose($RESULT);
        unlink($outfile);
        
        $this->process_top_images($top_images, $top_unpublished_images);
        
        if($top_images) $hierarchy_entry_ids[] = $last_hierarchy_entry_id;
        if($delete)
        {
            $split_ids = array_chunk($hierarchy_entry_ids, 5000);
            while(list($key, $chunk) = each($split_ids))
            {
                $this->mysqli->delete("DELETE FROM top_images_tmp WHERE hierarchy_entry_id IN (".implode($chunk, ",").")");
                $this->mysqli->delete("DELETE FROM top_unpublished_images_tmp WHERE hierarchy_entry_id IN (".implode($chunk, ",").")");
            }
        }
        
        return $parent_ids;
    }
    
    public static function write_sorted_results_to_file(&$top_images, &$FILE)
    {
        if(!$FILE) return false;
        
        // trying to mimic the Rails top images sorting as closely as possible
        $view_order = 1;
        ksort($top_images);
        while(list($vetted_orders, $ratings) = each($top_images))
        {
            krsort($ratings);
            while(list($r, $object_ids) = each($ratings))
            {
                krsort($object_ids);
                while(list($object_id, $data) = each($object_ids))
                {
                    fwrite($FILE, $data . "\t$view_order\n");
                    $view_order++;
                    if($view_order > 500) break;
                }
            }
        }
        unset($top_images);
    }
    
    function process_top_images(&$top_images, &$top_unpublished_images)
    {
        self::write_sorted_results_to_file($top_images, $this->TOP_IMAGES_FILE);
        self::write_sorted_results_to_file($top_unpublished_images, $this->TOP_UNPUBLISHED_IMAGES);
    }
    
    function end_load_data()
    {
        echo "removing data files\n";
        unlink(DOC_ROOT ."temp/top_images.sql");
        unlink(DOC_ROOT ."temp/top_unpublished_images.sql");
        
        // swap temporary tables with real tables
        $result = $this->mysqli->query("SELECT 1 FROM top_images_tmp LIMIT 1");
        if($result && $row=$result->fetch_assoc())
        {
            $this->mysqli->update("RENAME TABLE top_images TO top_images_swap,
                                                top_images_tmp TO top_images,
                                                top_images_swap TO top_images_tmp");
        }
        $result = $this->mysqli->query("SELECT 1 FROM top_unpublished_images_tmp LIMIT 1");
        if($result && $row=$result->fetch_assoc())
        {
            $this->mysqli->update("RENAME TABLE top_unpublished_images TO top_unpublished_images_swap,
                                                top_unpublished_images_tmp TO top_unpublished_images,
                                                top_unpublished_images_swap TO top_unpublished_images_tmp");
        }
        
        echo "Update 1 of 2\n";
        $this->mysqli->update("UPDATE taxon_concept_content tcc JOIN hierarchy_entries he USING (taxon_concept_id) JOIN top_images ti ON (he.id=ti.hierarchy_entry_id) SET tcc.child_image=1, tcc.image_object_id=ti.data_object_id WHERE ti.view_order=1");
        
        echo "Update 2 of 2\n";
        $this->mysqli->update("UPDATE hierarchies_content hc JOIN top_images ti USING (hierarchy_entry_id) SET hc.child_image=1, hc.image_object_id=ti.data_object_id WHERE ti.view_order=1");
        
        $species_rank_ids_array = array();
        if($id = Rank::insert('species')) $species_rank_ids_array[] = $id;
        if($id = Rank::insert('sp')) $species_rank_ids_array[] = $id;
        if($id = Rank::insert('sp.')) $species_rank_ids_array[] = $id;
        if($id = Rank::insert('subspecies')) $species_rank_ids_array[] = $id;
        if($id = Rank::insert('subsp')) $species_rank_ids_array[] = $id;
        if($id = Rank::insert('subsp.')) $species_rank_ids_array[] = $id;
        if($id = Rank::insert('variety')) $species_rank_ids_array[] = $id;
        if($id = Rank::insert('var')) $species_rank_ids_array[] = $id;
        if($id = Rank::insert('var.')) $species_rank_ids_array[] = $id;
        $species_rank_ids = implode(",", $species_rank_ids_array);
        
        // maybe also add where lft=rgt-1??
        echo "top_species_images\n";
        $this->mysqli->delete("DELETE FROM top_species_images");
        $this->mysqli->update("INSERT INTO top_species_images (SELECT ti.* FROM hierarchy_entries he JOIN top_images ti ON (he.id=ti.hierarchy_entry_id) WHERE he.rank_id IN ($species_rank_ids))");
        
        echo "top_unpublished_species_images\n";
        $this->mysqli->delete("DELETE FROM top_unpublished_species_images");
        $this->mysqli->update("INSERT INTO top_unpublished_species_images (SELECT tui.* FROM hierarchy_entries he JOIN top_unpublished_images tui ON (he.id=tui.hierarchy_entry_id) WHERE he.rank_id IN ($species_rank_ids))");
        
        $this->mysqli->end_transaction();
    }
    
    function load_data_from_files()
    {
        echo "inserting new data\n";
        echo "1 of 2\n";
        $this->mysqli->load_data_infile(DOC_ROOT ."temp/top_images.sql", "top_images_tmp");
        
        echo "2 of 2\n";
        $this->mysqli->load_data_infile(DOC_ROOT ."temp/top_unpublished_images.sql", "top_unpublished_images_tmp");
    }
}

?>
