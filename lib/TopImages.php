<?php
namespace php_active_record;

class TopImages
{
    private $mysqli;
    private $mysqli_slave;
    private $vetted_sort_orders;
    private $TOP_IMAGES_FILE;
    private $TOP_UNPUBLISHED_IMAGES;
    private $iteration_size = 70000;

    public function __construct()
    {
        $this->mysqli =& $GLOBALS['db_connection'];
        if($GLOBALS['ENV_NAME'] == 'production' && environment_defined('slave')) $this->mysqli_slave = load_mysql_environment('slave');
        else $this->mysqli_slave =& $this->mysqli;

        $this->vetted_sort_orders =  array();
        $this->vetted_sort_orders[Vetted::trusted()->id] = 1;
        $this->vetted_sort_orders[Vetted::unknown()->id] = 2;
        $this->vetted_sort_orders[Vetted::untrusted()->id] = 3;
    }

    public function begin_process()
    {
        // create and flush the temporary tables
        $this->mysqli->insert("CREATE TABLE IF NOT EXISTS top_images_tmp LIKE top_images");
        $this->mysqli->delete("TRUNCATE TABLE top_images_tmp");
        $this->mysqli->insert("CREATE TABLE IF NOT EXISTS top_unpublished_images_tmp LIKE top_unpublished_images");
        $this->mysqli->delete("TRUNCATE TABLE top_unpublished_images_tmp");

        $this->all_top_images = array();
        $query = "SELECT DISTINCT dotc.data_object_id, do.data_rating, dohe.visibility_id, dohe.vetted_id
            FROM data_objects_taxon_concepts dotc
            JOIN data_objects do ON (dotc.data_object_id=do.id)
            JOIN data_objects_hierarchy_entries dohe ON (do.id=dohe.data_object_id)
            WHERE do.data_type_id=". DataType::image()->id ."
            AND (do.published=1 OR dohe.visibility_id!=".Visibility::visible()->id.")";  //AND do.id BETWEEN 11407274 AND 11507274
        $i = 0;
        $this->image_data_objects = array();
        foreach($this->mysqli_slave->iterate_file($query) as $row_num => $row)
        {
            $data_object_id = $row[0];
            $data_rating = $row[1];
            $visibility_id = $row[2];
            $vetted_id = $row[3];
            $vetted_view_order = @$this->vetted_sort_orders[$vetted_id];
            if(!$vetted_view_order) continue;

            $this->image_data_objects[$data_object_id] = array('data_rating' => $data_rating,
                                          'visibility_id' => $visibility_id,
                                          'vetted_view_order' => $vetted_view_order);
            if($i % 10000 == 0)
            {
                echo "$i ". memory_get_usage() ." ". time_elapsed() ."\n";
            }
            $i++;
        }
        
        echo "lookup_baseline_image_concepts ". memory_get_usage() ." ". time_elapsed() ."\n";
        $this->lookup_baseline_image_concepts();

        echo "lookup_hierarchy_entry_ids ". memory_get_usage() ." ". time_elapsed() ."\n";
        $this->lookup_hierarchy_entry_ids();

        // add in these objects DIRECTLY linked to concepts
        echo "insert_baseline_objects ". memory_get_usage() ." ". time_elapsed() ."\n";
        $this->insert_baseline_objects();

        // now start the search of the parents of these concepts
        echo "start_process_parents ". memory_get_usage() ." ". time_elapsed() ."\n";
        $this->start_process_parents();

        // finalize the import, clean up, move temp tables to real tables
        echo "end_load_data ". memory_get_usage() ." ". time_elapsed() ."\n";
        $this->end_load_data();
    }

    private function lookup_baseline_image_concepts()
    {
        $ids = array_keys($this->image_data_objects);
        $chunks = array_chunk($ids, 10000);
        unset($ids);

        $this->baseline_concept_images = array();
        $i = 0;
        foreach($chunks as &$chunk)
        {
            $query = "SELECT taxon_concept_id, data_object_id
                      FROM data_objects_taxon_concepts
                      WHERE data_object_id IN (". implode(",", $chunk).")";
            $count = 0;
            foreach($this->mysqli_slave->iterate_file($query) as $row_num => $row)
            {
                $taxon_concept_id = $row[0];
                $data_object_id = $row[1];
                $this->baseline_concept_images[$taxon_concept_id][$data_object_id] = 1;
                $count++;
            }
            $i++;
            echo "Chunk $i of ". count($chunks) ."\n";
            echo "Records: $count ". memory_get_usage() ." ". time_elapsed() ."\n";
        }
    }

    private function insert_baseline_objects()
    {
        if(!($this->TOP_IMAGES_FILE = fopen(DOC_ROOT . "temp/top_images.sql", "w+")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . DOC_ROOT . "temp/top_images.sql");
          return;
        }
        if(!($this->TOP_UNPUBLISHED_IMAGES = fopen(DOC_ROOT . "temp/top_unpublished_images.sql", "w+")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . DOC_ROOT . "temp/top_unpublished_images.sql");
          return;
        }

        $preview_id = Visibility::preview()->id;

        $i = 0;
        while(list($taxon_concept_id, $data_object_ids) = each($this->baseline_concept_images))
        {
            // make sure we have entries for this concept
            if(!isset($this->baseline_hierarchy_entry_ids[$taxon_concept_id])) continue;
            $top_images = array();
            $top_unpublished_images = array();

            while(list($data_object_id, $val) = each($data_object_ids))
            {
                // make sure we have the metadata about this image
                if(!isset($this->image_data_objects[$data_object_id])) continue;

                $data_rating = $this->image_data_objects[$data_object_id]['data_rating'];
                $visibility_id = $this->image_data_objects[$data_object_id]['visibility_id'];
                $vetted_view_order = $this->image_data_objects[$data_object_id]['vetted_view_order'];
                $is_preview = ($visibility_id == $preview_id) ? true : false;

                reset($this->baseline_hierarchy_entry_ids[$taxon_concept_id]);
                while(list($key, $hierarchy_entry_id) = each($this->baseline_hierarchy_entry_ids[$taxon_concept_id]))
                {
                    if($is_preview) $top_unpublished_images[$hierarchy_entry_id][$vetted_view_order][$data_rating][$data_object_id] = "$hierarchy_entry_id\t$data_object_id";
                    else $top_images[$hierarchy_entry_id][$vetted_view_order][$data_rating][$data_object_id] = "$hierarchy_entry_id\t$data_object_id";
                }
            }

            if($top_images || $top_unpublished_images)
            {
                $this->process_top_images($top_images, $top_unpublished_images);
            }
            unset($top_images);
            unset($top_unpublished_images);

            if($i % 1000 == 0) echo "$i ". memory_get_usage() ." ". time_elapsed() ."\n";
            $i++;
        }
        unset($this->image_data_objects);
        unset($this->baseline_concept_images);
        unset($this->baseline_hierarchy_entry_ids);

        fclose($this->TOP_IMAGES_FILE);
        fclose($this->TOP_UNPUBLISHED_IMAGES);
        $this->load_data_from_files();
    }


    private function lookup_hierarchy_entry_ids()
    {
        echo "lookup_hierarchy_entry_ids ". memory_get_usage() ." ". time_elapsed() ."\n";
        $taxon_concept_ids = array_keys($this->baseline_concept_images);
        sort($taxon_concept_ids);
        $this->baseline_hierarchy_entry_ids = array();
        $chunks = array_chunk($taxon_concept_ids, 10000);
        $i = 0;
        foreach($chunks as $chunk)
        {
            echo "$i ". memory_get_usage() ." ". time_elapsed() ."\n";
            $query = "SELECT taxon_concept_id, id FROM hierarchy_entries FORCE INDEX (concept_published_visible) WHERE taxon_concept_id IN (". implode(",", $chunk) .")  AND ((published=1 AND visibility_id=". Visibility::visible()->id .") OR (published=0 AND visibility_id=". Visibility::preview()->id ."))";
            foreach($this->mysqli_slave->iterate_file($query) as $row_num => $row)
            {
                if(!isset($this->baseline_hierarchy_entry_ids[$row[0]])) $this->baseline_hierarchy_entry_ids[$row[0]] = array();
                $this->baseline_hierarchy_entry_ids[$row[0]][] = $row[1];
            }
            $i++;
        }
    }

    private function start_process_parents()
    {
        // parents of all the visible leaf nodes with images
        $query = "SELECT DISTINCT he.parent_id FROM hierarchy_entries he JOIN top_images_tmp ti ON (he.id=ti.hierarchy_entry_id) WHERE he.parent_id!=0";
        $ids = array();
        foreach($this->mysqli_slave->iterate_file($query) as $row_num => $row)
        {
            $ids[] = $row[0];
        }
        echo "SPP ". count($ids) ." ". memory_get_usage() ." ". time_elapsed() ."\n";
        $this->process_parents($ids);
    }

    private function process_parents($parent_ids)
    {
        // truncate the temporary files
        if(!($this->TOP_IMAGES_FILE = fopen(DOC_ROOT . "temp/top_images.sql", "w+")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . DOC_ROOT . "temp/top_images.sql");
          return;
        }
        if(!($this->TOP_UNPUBLISHED_IMAGES = fopen(DOC_ROOT . "temp/top_unpublished_images.sql", "w+")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . DOC_ROOT . "temp/top_unpublished_images.sql");
          return;
        }
        $i = 0;
        unset($all_parent_ids);
        $all_parent_ids = array();
        $chunks = array_chunk($parent_ids, 5000);
        while(list($key, $chunk) = each($chunks))
        {
            $i++;
            echo "Chunk $i of ". count($chunks) ."\n";

            // searches for all images for THIS concept, same as above
            // but also searches top_images for the best from its decendants
            $outfile = $this->mysqli_slave->select_into_outfile("
            (SELECT he.id hierarchy_entry_id, he.parent_id, do.id, do.data_rating, dohe.visibility_id, dohe.vetted_id, do.published
                FROM hierarchy_entries he
                JOIN top_images_tmp ti ON (he.id=ti.hierarchy_entry_id)
                JOIN data_objects do ON (ti.data_object_id=do.id)
                JOIN data_objects_hierarchy_entries dohe ON (do.id=dohe.data_object_id)
                WHERE he.id IN (". implode($chunk, ",") ."))
            UNION
            (SELECT he.id hierarchy_entry_id, he.parent_id, do.id, do.data_rating, dohe.visibility_id, dohe.vetted_id, do.published
                FROM hierarchy_entries he
                JOIN hierarchy_entries he_children ON (he.id=he_children.parent_id)
                JOIN top_images_tmp ti ON (he_children.id=ti.hierarchy_entry_id)
                JOIN data_objects do ON (ti.data_object_id=do.id)
                JOIN data_objects_hierarchy_entries dohe ON (do.id=dohe.data_object_id)
                WHERE he.id IN (". implode($chunk, ",") ."))
            ORDER BY hierarchy_entry_id");
            echo "Memory: ".memory_get_usage() ." ". time_elapsed() ."\n";

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
        if(!($OUT = fopen(DOC_ROOT . "temp/top_concept_images.sql", "w+")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . DOC_ROOT . "temp/top_concept_images.sql");
          return;
        }
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
        $batch_size = 15000;

        $result = $this->mysqli_slave->query("SELECT MIN(he.taxon_concept_id) min, MAX(he.taxon_concept_id) max FROM $select_table_name ti JOIN hierarchy_entries he ON (ti.hierarchy_entry_id=he.id)");
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
            $result = $this->mysqli_slave->query("SELECT  he.taxon_concept_id, do.id data_object_id, v.view_order vetted_sort_order, do.data_rating FROM hierarchy_entries he JOIN $select_table_name ti ON (he.id= ti.hierarchy_entry_id) JOIN data_objects do ON (ti.data_object_id=do.id) JOIN data_objects_hierarchy_entries dohe ON (do.id=dohe.data_object_id) JOIN vetted v ON (dohe.vetted_id=v.id) WHERE he.taxon_concept_id BETWEEN $i  AND ". ($i+$batch_size)." ORDER BY he.taxon_concept_id");
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
                $top_images[$taxon_concept_id][$vetted_sort_order][$data_rating][$data_object_id] = "$taxon_concept_id\t$data_object_id";
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
        $parent_ids = array();
        $last_hierarchy_entry_id = 0;
        $top_images = array();
        $top_unpublished_images = array();
        $hierarchy_entry_ids = array();

        $visible_id = Visibility::visible()->id;
        if(!($RESULT = fopen($outfile, "r")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . $outfile);
          return;
        }
        while(!feof($RESULT))
        {
            if($line = fgets($RESULT, 4096))
            {
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
                if($visibility_id==$visible_id && $published==1) $top_images[$hierarchy_entry_id][$vetted_sort_order][$data_rating][$data_object_id] = "$hierarchy_entry_id\t$data_object_id";
                else $top_unpublished_images[$hierarchy_entry_id][$vetted_sort_order][$data_rating][$data_object_id] = "$hierarchy_entry_id\t$data_object_id";
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
        ksort($top_images);
        while(list($hierarchy_entry_id, $top_entry_images) = each($top_images))
        {
            $view_order = 1;
            ksort($top_entry_images);
            while(list($vetted_orders, $ratings) = each($top_entry_images))
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

        $species_rank_ids = implode(",", Rank::species_ranks_ids());

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
        echo "Inserting TopImages ". memory_get_usage() ." ". time_elapsed() ."\n";
        $this->mysqli->load_data_infile(DOC_ROOT ."temp/top_images.sql", "top_images_tmp");

        echo "Inserting TopUnpublishedImages ". memory_get_usage() ." ". time_elapsed() ."\n";
        $this->mysqli->load_data_infile(DOC_ROOT ."temp/top_unpublished_images.sql", "top_unpublished_images_tmp");
    }
}

?>
