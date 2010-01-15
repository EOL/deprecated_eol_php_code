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
        $this->mysqli =& $GLOBALS['mysqli_connection'];
        
        $this->vetted_sort_orders =  array();
        $this->vetted_sort_orders[Vetted::find('Trusted')] = 1;
        $this->vetted_sort_orders[Vetted::find('Unknown')] = 2;
        $this->vetted_sort_orders[Vetted::find('Untrusted')] = 3;
    }
    
    public function begin_process($lookup_ids = null)
    {
        $start = 0;
        $max_id = 0;
        
        $result = $this->mysqli->query("SELECT MIN(id) as min, MAX(id) as max FROM hierarchy_entries he");
        if($result && $row=$result->fetch_assoc())
        {
            $start = $row["min"];
            $max_id = $row["max"];
        }
        
        $this->TOP_IMAGES_FILE = fopen(LOCAL_ROOT . "temp/top_images.sql", "w+");
        $this->TOP_UNPUBLISHED_IMAGES = fopen(LOCAL_ROOT . "temp/top_unpublished_images.sql", "w+");
        
        $all_parent_ids = array();
        for($i=$start ; $i<$max_id ; $i+=$this->iteration_size)
        {
            echo "Query ". (($i-$start+$this->iteration_size)/$this->iteration_size) ." of ". (ceil(($max_id-$start)/$this->iteration_size)) ."\n";
            $image_type_id = DataType::find("http://purl.org/dc/dcmitype/StillImage");
            
            echo "Memory: ".memory_get_usage()."\n";
            $result = $this->mysqli->query("SELECT  he.id hierarchy_entry_id, he.parent_id, do.id, do.data_rating, do.visibility_id, do.vetted_id, do.published FROM  hierarchy_entries he JOIN hierarchy_entries he_concepts ON (he.taxon_concept_id= he_concepts.taxon_concept_id)  JOIN taxa t ON (he_concepts.id=t.hierarchy_entry_id) JOIN data_objects_taxa dot ON (t.id=dot.taxon_id)  JOIN data_objects do ON  (dot.data_object_id= do.id) WHERE he.id   BETWEEN $i AND ".($i+$this->iteration_size)." AND he.hierarchy_id!=399 AND he.hierarchy_id!=105 AND he.hierarchy_id!=129    AND do.data_type_id=$image_type_id ORDER BY he.id");
            echo "Memory: ".memory_get_usage()."\n";
            
            list($parent_ids, $top_images, $top_unpublished_images) = $this->get_data_from_result($result);
            $all_parent_ids = array_merge($all_parent_ids, array_keys($parent_ids));
        }
        fclose($this->TOP_IMAGES_FILE);
        fclose($this->TOP_UNPUBLISHED_IMAGES);
        
        $this->begin_load_data();
        if($all_parent_ids) $this->process_parents($all_parent_ids);
        $this->end_load_data();
    }
    
    public function process_parents($parent_ids)
    {
        $this->TOP_IMAGES_FILE = fopen(LOCAL_ROOT . "temp/top_images.sql", "w+");
        $this->TOP_UNPUBLISHED_IMAGES = fopen(LOCAL_ROOT . "temp/top_unpublished_images.sql", "w+");
        
        $i = 0;
        $all_parent_ids = array();
        $chunks = array_chunk($parent_ids, 20000);
        foreach($chunks as $chunk)
        {
            $i++;
            echo "Chunk $i of ". count($chunks) ."\n";
            $image_type_id = DataType::find("http://purl.org/dc/dcmitype/StillImage");
            
            echo "Memory: ".memory_get_usage()."\n";
            $result = $this->mysqli->query("(SELECT he.id hierarchy_entry_id, he.parent_id, do.id, do.data_rating, do.visibility_id, do.vetted_id, do.published FROM hierarchy_entries he JOIN hierarchy_entries he_concepts  ON (he.taxon_concept_id=he_concepts.taxon_concept_id) JOIN taxa t ON (he_concepts.id=t.hierarchy_entry_id) JOIN data_objects_taxa dot ON (t.id=dot.taxon_id) JOIN data_objects do ON (dot.data_object_id=do.id) WHERE he.id IN (". implode($chunk, ",") .") AND do.data_type_id=$image_type_id)
            UNION
            (SELECT he.id hierarchy_entry_id, he.parent_id, do.id, do.data_rating, do.visibility_id, do.vetted_id, do.published FROM hierarchy_entries he JOIN hierarchy_entries he_children ON (he.id=he_children.parent_id) JOIN top_images ti ON (he_children.id=ti.hierarchy_entry_id) JOIN data_objects do ON (ti.data_object_id=do.id) WHERE he.id IN (". implode($chunk, ",") .") AND do.data_type_id=$image_type_id) ORDER BY hierarchy_entry_id");
            echo "Memory: ".memory_get_usage()."\n";
            
            list($parent_ids, $top_images, $top_unpublished_images) = $this->get_data_from_result($result);
            $all_parent_ids = array_merge($all_parent_ids, array_keys($parent_ids));
        }
        fclose($this->TOP_IMAGES_FILE);
        fclose($this->TOP_UNPUBLISHED_IMAGES);
        
        $this->load_data_from_files();
        if($all_parent_ids) $this->process_parents($all_parent_ids);
    }
    
    public function top_concept_images($published = false)
    {
        $OUT = fopen(LOCAL_ROOT . "temp/top_concept_images.sql", "w+");
        
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
        $batch_size = 200000;
        
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
                    $top_images = array();
                }
                $top_images[$vetted_sort_order][$data_rating][$data_object_id] = "$taxon_concept_id\t$data_object_id";
            }
            if($top_images) self::write_sorted_results_to_file($top_images, $OUT);
        }
        fclose($OUT);
        
        if(filesize(LOCAL_ROOT ."temp/top_concept_images.sql"))
        {
            echo "Deleting old concept data\n";
            $this->mysqli->delete("DELETE FROM $table_name");
            $this->mysqli->load_data_infile(LOCAL_ROOT ."temp/top_concept_images.sql", $table_name, false);
        }
        shell_exec("rm ". LOCAL_ROOT ."temp/top_concept_images.sql");
    }
    
    
    function get_data_from_result(&$result)
    {
        $i = 0;
        $parent_ids = array();
        $last_hierarchy_entry_id = 0;
        $top_images = array();
        $top_unpublished_images = array();
        // $rows = $result->num_rows;
        // for($h=0 ; $h<$rows ; $h++)
        // {
        //     $result->data_seek($h);
        //     $row = $result->fetch_assoc();
        while($result && $row=$result->fetch_assoc())
        {
            if($i%1000==0) echo "Memory: ".memory_get_usage()."\n";
            $i++;
            $hierarchy_entry_id = $row["hierarchy_entry_id"];
            $parent_id = $row["parent_id"];
            $data_object_id = $row["id"];
            $data_rating = $row["data_rating"];
            $visibility_id = $row["visibility_id"];
            $published = $row["published"];
            $vetted_id = $row["vetted_id"];
            if($parent_id) $parent_ids[$parent_id] = 1;
            
            $vetted_sort_order = isset($this->vetted_sort_orders[$vetted_id]) ? $this->vetted_sort_orders[$vetted_id] : 5;
            
            // moving on to the next entry
            if($hierarchy_entry_id != $last_hierarchy_entry_id)
            {
                $this->process_top_images($top_images, $top_unpublished_images);
                $last_hierarchy_entry_id = $hierarchy_entry_id;
                $top_images = array();
                $top_unpublished_images = array();
            }
            
            if($visibility_id==Visibility::find("visible") && $published==1) $top_images[$vetted_sort_order][$data_rating][$data_object_id] = "$hierarchy_entry_id\t$data_object_id";
            else $top_unpublished_images[$vetted_sort_order][$data_rating][$data_object_id] = "$hierarchy_entry_id\t$data_object_id";
            unset($row);
        }
        if($result) $result->free();
        
        $this->process_top_images($top_images, $top_unpublished_images);
        
        return array($parent_ids, $top_images, $top_unpublished_images);
    }
    
    public static function write_sorted_results_to_file($top_images, $FILE)
    {
        if(!$FILE) return false;
        
        $view_order = 1;
        ksort($top_images);
        foreach($top_images as $vetted_orders => $ratings)
        {
            krsort($ratings);
            foreach($ratings as $r => $object_ids)
            {
                krsort($object_ids);
                foreach($object_ids as $object_id => $data)
                {
                    fwrite($FILE, $data . "\t$view_order\n");
                    $view_order++;
                    if($view_order > 500) break;
                }
                unset($object_ids);
            }
            unset($ratings);
        }
        unset($top_images);
    }
    
    function process_top_images($top_images, $top_unpublished_images)
    {
        self::write_sorted_results_to_file($top_images, $this->TOP_IMAGES_FILE);
        self::write_sorted_results_to_file($top_unpublished_images, $this->TOP_UNPUBLISHED_IMAGES);
    }
    
    function begin_load_data()
    {
        if(filesize(LOCAL_ROOT ."temp/top_images.sql"))
        {
            $this->mysqli->begin_transaction();
            
            echo "Deleting old data\n";
            $this->mysqli->delete("DELETE FROM top_images");
            $this->mysqli->delete("DELETE FROM top_unpublished_images");
            
            $this->load_data_from_files();
        }
    }
    
    function end_load_data()
    {
        echo "removing data files\n";
        shell_exec("rm ". LOCAL_ROOT ."temp/top_images.sql");
        shell_exec("rm ". LOCAL_ROOT ."temp/top_unpublished_images.sql");
        
        echo "Update 1 of 2\n";
        $this->mysqli->update("UPDATE taxon_concept_content tcc JOIN hierarchy_entries he USING (taxon_concept_id) JOIN top_images ti ON (he.id=ti.hierarchy_entry_id) SET tcc.child_image=1, tcc.image_object_id=ti.data_object_id WHERE ti.view_order=1");

        echo "Update 2 of 2\n";
        $this->mysqli->update("UPDATE hierarchies_content hc JOIN top_images ti USING (hierarchy_entry_id) SET hc.child_image=1, hc.image_object_id=ti.data_object_id WHERE ti.view_order=1");

        $species_rank_ids_array = array();
        if($id = Rank::find('species')) $species_rank_ids_array[] = $id;
        if($id = Rank::find('sp')) $species_rank_ids_array[] = $id;
        if($id = Rank::find('sp.')) $species_rank_ids_array[] = $id;
        if($id = Rank::find('subspecies')) $species_rank_ids_array[] = $id;
        if($id = Rank::find('subsp')) $species_rank_ids_array[] = $id;
        if($id = Rank::find('subsp.')) $species_rank_ids_array[] = $id;
        if($id = Rank::find('variety')) $species_rank_ids_array[] = $id;
        if($id = Rank::find('var')) $species_rank_ids_array[] = $id;
        if($id = Rank::find('var.')) $species_rank_ids_array[] = $id;
        $species_rank_ids = implode(",", $species_rank_ids_array);
        
        $this->mysqli->delete("DELETE FROM top_species_images");
        $this->mysqli->delete("DELETE FROM top_unpublished_species_images");
        
        // maybe also add where lft=rgt-1??
        echo "top_species_images\n";
        $this->mysqli->update("INSERT INTO top_species_images (SELECT ti.* FROM hierarchy_entries he JOIN top_images ti ON (he.id=ti.hierarchy_entry_id) WHERE he.rank_id IN ($species_rank_ids))");
        
        echo "top_unpublished_species_images\n";
        $this->mysqli->update("INSERT INTO top_unpublished_species_images (SELECT tui.* FROM hierarchy_entries he JOIN top_unpublished_images tui ON (he.id=tui.hierarchy_entry_id) WHERE he.rank_id IN ($species_rank_ids))");
        
        
        $this->mysqli->end_transaction();
    }
    
    function load_data_from_files()
    {
        echo "inserting new data\n";
        echo "1 of 2\n";
        $this->mysqli->load_data_infile(LOCAL_ROOT ."temp/top_images.sql", "top_images", false);
        echo "2 of 2\n";
        $this->mysqli->load_data_infile(LOCAL_ROOT ."temp/top_unpublished_images.sql", "top_unpublished_images", false);
    }
}

?>