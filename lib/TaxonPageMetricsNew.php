<?php
namespace php_active_record;

define("PAGE_METRICS_TEXT_PATH", DOC_ROOT . "applications/taxon_page_metrics/text_files/");
class TaxonPageMetricsNew
{
    private $mysqli;
    
    public function __construct()
    {
        $this->mysqli =& $GLOBALS['mysqli_connection'];
        // if($GLOBALS['ENV_NAME'] == 'production' && environment_defined('slave')) $this->mysqli_slave = load_mysql_environment('slave');
        // else 
        $this->mysqli_slave =& $this->mysqli;
        
        $this->min_taxon_concept_id = 0;
        $this->max_taxon_concept_id = 0;
        $result = $this->mysqli_slave->query("SELECT MIN(id) min, MAX(id) max FROM taxon_concepts");
        if($result && $row=$result->fetch_assoc())
        {
            $this->min_taxon_concept_id = $row['min'];
            $this->max_taxon_concept_id = $row['max'];
        }
        $this->total_number_of_concepts = $this->max_taxon_concept_id - $this->min_taxon_concept_id;
        
        $this->test_taxon_concept_ids = '';
        /* $tc_id=218284; //with user-submitted-text    //array(206692, 1, 218294, 7921); */
        // $this->test_taxon_concept_ids = implode(",", array(206692, 1, 218284, 1045608));
    }
    
    function print_status($start_index, $batch_size)
    {
        $batch_number = (($start_index - $this->min_taxon_concept_id) / $batch_size) + 1;
        $total_batches = ceil($this->total_number_of_concepts/$batch_size);
        echo "  batch $batch_number of $total_batches\t\t";
        echo "time: ". round(time_elapsed(), 2) ." seconds\t\t";
        echo "memory: ". round(memory_get_usage() / 1048576, 2) ." MB\n";
    }
    
    /* prepare taxon concept totals for richness calculations */
    function insert_page_metrics($single_method_to_run = null)
    {
        // create index concept_published_visible on hierarchy_entries(taxon_concept_id, published, visibility_id);
        
        // $this->initialize_concepts_list();
        $methods_to_run = array();
        //* $methods_to_run[] = 'get_images_count';
        // $methods_to_run[] = 'get_data_objects_count';
        $methods_to_run[] = 'get_concept_references';
        // $methods_to_run[] = 'get_BHL_publications';
        //* $methods_to_run[] = 'get_content_partner_count';
        //* $methods_to_run[] = 'get_outlinks_count';
        //* $methods_to_run[] = 'get_GBIF_map_availability';
        //* $methods_to_run[] = 'get_biomedical_terms_availability';
        //* $methods_to_run[] = 'get_user_submitted_text_count';
        //* $methods_to_run[] = 'get_common_names_count';
        //* $methods_to_run[] = 'get_synonyms_count';
        // $methods_to_run[] = 'get_google_stats';
        $this->run_stats_gathering_methods($methods_to_run, $single_method_to_run);
        // $this->save_to_table();
    }
    
    function run_stats_gathering_methods($methods_to_run, $single_method_to_run = null)
    {
        foreach($methods_to_run as $method)
        {
            if($single_method_to_run && $method != $single_method_to_run) continue;
            $time_start = time_elapsed();
            echo "Calling $method\n";
            
            // create and/or truncate the stats file for this category
            $this->initialize_category_file($method);
            call_user_func(array($this, $method));
            
            echo "Finished $method\n";
            echo "in ". round((time_elapsed()-$time_start)/60, 2) . " minutes\n\n";
        }
    }
    
    function initialize_concepts_list()
    {
        $sql = "SELECT tc.id FROM taxon_concepts tc WHERE tc.published = 1 AND tc.supercedure_id = 0";
        if($this->test_taxon_concept_ids) $sql .= " AND tc.id IN (". $this->test_taxon_concept_ids .")";
        $outfile = $this->mysqli_slave->select_into_outfile($sql);
        if (!copy($outfile, PAGE_METRICS_TEXT_PATH . "taxon_concept_metrics.txt")) print "\n failed to copy $outfile...\n";
        unlink($outfile);
    }
    
    function get_google_stats()
    {
        $arr_taxa = array();
        //get the last 12 months - descending order
        $sql = "Select concat(gas.`year`,'_',substr(gas.`month` / 100,3,2)) as `year_month` From google_analytics_summaries gas Order By gas.`year` Desc, gas.`month` Desc limit 11,1";
        $result = $this->mysqli_slave->query($sql);
        if($result && $row = $result->fetch_assoc()) $year_month = $row['year_month'];
        $batch = 500000;
        $start_limit = 0;
        while(true)
        {
            $sql = "Select gaps.taxon_concept_id, gaps.page_views, gaps.unique_page_views From google_analytics_page_stats gaps Where concat(gaps.year,'_',substr(gaps.month/100,3,2)) >= '$year_month'";
            if($this->test_taxon_concept_ids) $sql .= " and gaps.taxon_concept_id IN (". $this->test_taxon_concept_ids .")";
            $sql .= " limit $start_limit, $batch ";
            $outfile = $this->mysqli_slave->select_into_outfile($sql);
            $start_limit += $batch;
            if(!($FILE = fopen($outfile, "r")))
            {
              debug(__CLASS__ .":". __LINE__ .": Couldn't open file: ". $outfile);
              return;
            }
            $num_rows = 0;
            while(!feof($FILE))
            {
                if($line = fgets($FILE))
                {
                    $num_rows++;
                    $line = trim($line);
                    $fields = explode("\t", $line);
                    $tc_id             = trim($fields[0]);
                    $page_views        = trim($fields[1]);
                    $unique_page_views = trim($fields[2]);
                    
                    @$arr_taxa[$tc_id]['pv'] += $page_views;
                    @$arr_taxa[$tc_id]['upv'] += $unique_page_views;
                }
            }
            fclose($FILE);
            unlink($outfile);
            if($num_rows < $batch) break;
        }
        
        //convert associative array to a regular array
        foreach($arr_taxa as $tc_id => $taxon_views_counts)
        {
            $new_value = "";
            $new_value .= "\t" . @$taxon_views_counts['pv'];
            $new_value .= "\t" . @$taxon_views_counts['upv'];
            $arr_taxa[$tc_id] = $new_value;
        }
        $this->save_totals_to_cumulative_txt($arr_taxa, "tpm_google_stats");
    }
    
    function get_BHL_publications()
    {
        $arr_taxa = array();
        $filename = PAGE_METRICS_TEXT_PATH . "taxon_concept_with_bhl_publications.txt";
        if(!file_exists($filename)) return;
        if(!($FILE = fopen($filename, "r")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: ". $filename);
          return;
        }
        $num_rows = 0;
        while(!feof($FILE))
        {
            if($line = fgets($FILE))
            {
                $num_rows++;
                $line = trim($line);
                $fields = explode("\t", $line);
                $tc_id        = trim(@$fields[0]);
                $publications = trim(@$fields[1]);
                $arr_taxa[$tc_id] = "\t".$publications;
            }
        }
        $this->save_totals_to_cumulative_txt($arr_taxa, "tpm_BHL");
    }
    
    function get_biomedical_terms_availability()
    {
        $BOA_agent_id = Agent::find('Biology of Aging');
        if(!$BOA_agent_id) return;
        $result = $this->mysqli_slave->query("SELECT MAX(harvest_events.id) latest_harvent_event_id
          FROM harvest_events JOIN agents_resources ON agents_resources.resource_id = harvest_events.resource_id
          WHERE agents_resources.agent_id = $BOA_agent_id AND harvest_events.published_at IS NOT NULL");
        if($result && $row = $result->fetch_assoc()) $latest_harvent_event_id = $row['latest_harvent_event_id'];
        
        $sql = "SELECT he.taxon_concept_id FROM harvest_events_hierarchy_entries hehe
          JOIN hierarchy_entries he ON (hehe.hierarchy_entry_id = he.id)
          WHERE hehe.harvest_event_id = $latest_harvent_event_id ";
        if($this->test_taxon_concept_ids) $sql .= " AND he.taxon_concept_id IN (". $this->test_taxon_concept_ids .")";
        
        $raw_stats = array();
        foreach($this->mysqli_slave->iterate_file($sql) as $row_number => $row)
        {
            $taxon_concept_id = trim($row[0]);
            $raw_stats[$taxon_concept_id] = "1";
        }
        echo count($raw_stats)."\n";
        $this->save_category_stats($raw_stats, "get_biomedical_terms_availability");
    }
    
    function get_GBIF_map_availability()
    {
        $sql = "SELECT he.taxon_concept_id FROM hierarchy_entries he
          WHERE he.hierarchy_id = ". Hierarchy::find_by_label('GBIF Nub Taxonomy')->id ." AND he.identifier!=''";
        if($this->test_taxon_concept_ids) $sql .= " AND he.taxon_concept_id IN (". $this->test_taxon_concept_ids .")";
        
        $raw_stats = array();
        foreach($this->mysqli_slave->iterate_file($sql) as $row_number => $row)
        {
            $taxon_concept_id = trim($row[0]);
            $raw_stats[$taxon_concept_id] = "1";
        }
        $this->save_category_stats($raw_stats, "get_GBIF_map_availability");
    }
    
    function get_user_submitted_text_count()
    {
        $raw_stats = array();
        $sql = "SELECT udo.taxon_concept_id tc_id, udo.data_object_id do_id, udo.user_id FROM users_data_objects udo JOIN data_objects do ON udo.data_object_id = do.id WHERE do.published=1 AND (udo.vetted_id IS NULL OR udo.vetted_id != " . Vetted::untrusted()->id .")";
        if($this->test_taxon_concept_ids) $sql .= " and udo.taxon_concept_id IN (". $this->test_taxon_concept_ids .")";
        foreach($this->mysqli_slave->iterate_file($sql) as $row_number => $row)
        {
            $taxon_concept_id = trim($row[0]);
            $data_object_id = trim($row[1]);
            $user_id = trim($row[2]);
            $raw_stats[$taxon_concept_id]['user_submitted_objects'][$data_object_id] = 1;
            $raw_stats[$taxon_concept_id]['user_who_submitted_objects'][$user_id] = 1;
        }
        
        foreach($raw_stats as $taxon_concept_id => $stats)
        {
            $new_value = (isset($stats['user_submitted_objects']) ? count($stats['user_submitted_objects']) : '');
            $new_value .= "\t" . (isset($stats['user_who_submitted_objects']) ? count($stats['user_who_submitted_objects']) : '');
            $raw_stats[$taxon_concept_id] = $new_value;
        }
        $this->save_category_stats($raw_stats, "get_user_submitted_text_count");
    }
    
    function get_content_partner_count($batch_size = 500000)
    {
        $raw_stats = array();
        for($i=$this->min_taxon_concept_id ; $i<=$this->max_taxon_concept_id ; $i+=$batch_size)
        {
            $this->print_status($i, $batch_size);
            $sql = "SELECT tc.id, he.hierarchy_id FROM taxon_concepts tc
              JOIN hierarchy_entries he FORCE INDEX (concept_published_visible) ON (tc.id=he.taxon_concept_id)
              WHERE he.published = 1 AND he.visibility_id = ".Visibility::visible()->id;
            if($this->test_taxon_concept_ids) $sql .= " AND he.taxon_concept_id IN (". $this->test_taxon_concept_ids .")";
            else $sql .= " AND he.taxon_concept_id BETWEEN $i AND ". ($i + $batch_size);
            
            foreach($this->mysqli_slave->iterate_file($sql) as $row_number => $row)
            {
                $taxon_concept_id = trim($row[0]);
                $hierarchy_id = trim($row[1]);
                $raw_stats[$taxon_concept_id]['partners'][$hierarchy_id] = 1;
            }
            
            //convert associative array to a regular array
            foreach($raw_stats as $taxon_concept_id => $stats)
            {
                $raw_stats[$taxon_concept_id] = count($stats['partners']);
            }
            $this->save_category_stats($raw_stats, "get_content_partner_count");
            $raw_stats = array();
            
            if($this->test_taxon_concept_ids) break;
        }
    }
    
    function get_outlinks_count($batch_size = 100000)
    {
        $raw_stats = array();
        
        $hierarchy_agent_ids = array();
        $hierarchies_with_default_outlinks = array();
        $result = $this->mysqli_slave->query("SELECT id, agent_id, outlink_uri FROM hierarchies");
        while($result && $row=$result->fetch_assoc())
        {
            $hierarchy_agent_ids[$row['id']] = $row['agent_id'];
            if($row['outlink_uri']) $hierarchies_with_default_outlinks[] = $row['id'];
        }
        
        for($i=$this->min_taxon_concept_id ; $i<=$this->max_taxon_concept_id ; $i+=$batch_size)
        {
            $this->print_status($i, $batch_size);
            $sql = "SELECT he.taxon_concept_id, he.hierarchy_id FROM hierarchy_entries he FORCE INDEX (concept_published_visible)
              WHERE (he.source_url != '' || ( he.hierarchy_id IN (". implode(",", $hierarchies_with_default_outlinks) .") AND he.identifier != ''))";
            if($this->test_taxon_concept_ids) $sql .= " AND he.taxon_concept_id IN (". $this->test_taxon_concept_ids .")";
            else $sql .= " AND he.taxon_concept_id BETWEEN $i AND ". ($i + $batch_size);
            
            foreach($this->mysqli_slave->iterate_file($sql) as $row_number => $row)
            {
                $taxon_concept_id = $row[0];
                $hierarchy_id = $row[1];
                $agent_id = @$hierarchy_agent_ids[$hierarchy_id];
                $raw_stats[$taxon_concept_id]['outlink_providers'][$agent_id] = 1;
            }
            //convert associative array to a regular array
            foreach($raw_stats as $taxon_concept_id => $stats)
            {
                $raw_stats[$taxon_concept_id] = count($stats['outlink_providers']);
            }
            $this->save_category_stats($raw_stats, "get_outlinks_count");
            $raw_stats = array();
            if($this->test_taxon_concept_ids) break;
        }
    }
    
    function get_common_names_count($batch_size = 500000)
    {
        $raw_stats = array();
        for($i=$this->min_taxon_concept_id ; $i<=$this->max_taxon_concept_id ; $i+=$batch_size)
        {
            $this->print_status($i, $batch_size);
            $sql = "SELECT he.taxon_concept_id, s.name_id, s.language_id, s.hierarchy_id
            FROM hierarchy_entries he FORCE INDEX (concept_published_visible) JOIN synonyms s ON he.id = s.hierarchy_entry_id
            WHERE s.synonym_relation_id IN (" . SynonymRelation::find_by_translated('label', "common name")->id . "," . SynonymRelation::find_by_translated('label', "genbank common name")->id . ")
            AND he.published=1 AND he.visibility_id=". Visibility::visible()->id;
            if($this->test_taxon_concept_ids) $sql .= " AND he.taxon_concept_id IN (". $this->test_taxon_concept_ids .")";
            else $sql .= " AND he.taxon_concept_id BETWEEN $i AND ". ($i + $batch_size);
            
            foreach($this->mysqli_slave->iterate_file($sql) as $row_number => $row)
            {
                $taxon_concept_id = trim($row[0]);
                $name_id = trim($row[1]);
                $language_id = trim($row[2]);
                $hierarchy_id = trim($row[3]);
                $raw_stats[$taxon_concept_id]['common_names'][$name_id."|".$language_id] = 1;
                $raw_stats[$taxon_concept_id]['common_name_providers'][$hierarchy_id] = 1;
            }
            
            //convert associative array to a regular array
            foreach($raw_stats as $taxon_concept_id => $stats)
            {
                $new_value = (isset($stats['common_names']) ? count($stats['common_names']) : '');
                $new_value .= "\t" . (isset($stats['common_name_providers']) ? count($stats['common_name_providers']) : '');
                $raw_stats[$taxon_concept_id] = $new_value;
            }
            $this->save_category_stats($raw_stats, "get_common_names_count");
            $raw_stats = array();
            
            if($this->test_taxon_concept_ids) break;
        }
    }
    
    function get_synonyms_count($batch_size = 500000)
    {
        $raw_stats = array();
        for($i=$this->min_taxon_concept_id ; $i<=$this->max_taxon_concept_id ; $i+=$batch_size)
        {
            $this->print_status($i, $batch_size);
            $sql = "SELECT he.taxon_concept_id, s.name_id,  s.hierarchy_id
              FROM hierarchy_entries he FORCE INDEX (concept_published_visible)
              JOIN synonyms s ON he.id = s.hierarchy_entry_id JOIN hierarchies h ON s.hierarchy_id = h.id
              WHERE s.synonym_relation_id NOT IN (" . SynonymRelation::find_by_translated('label', "common name")->id . "," . SynonymRelation::find_by_translated('label', "genbank common name")->id . ")
              AND h.browsable=1 AND he.published=1 AND he.visibility_id=". Visibility::visible()->id;
            if($this->test_taxon_concept_ids) $sql .= " AND he.taxon_concept_id IN (". $this->test_taxon_concept_ids .")";
            else $sql .= " AND he.taxon_concept_id BETWEEN $i AND ". ($i + $batch_size);
            
            foreach($this->mysqli_slave->iterate_file($sql) as $row_number => $row)
            {
                $taxon_concept_id = trim($row[0]);
                $name_id = trim($row[1]);
                $hierarchy_id = trim($row[2]);
                $raw_stats[$taxon_concept_id]['synonyms'][$name_id] = 1;
                $raw_stats[$taxon_concept_id]['synonym_providers'][$hierarchy_id] = 1;
            }
            
            //convert associative array to a regular array
            foreach($raw_stats as $taxon_concept_id => $stats)
            {
                $new_value = (isset($stats['synonyms']) ? count($stats['synonyms']) : '');
                $new_value .= "\t" . (isset($stats['synonym_providers']) ? count($stats['synonym_providers']) : '');
                $raw_stats[$taxon_concept_id] = $new_value;
            }
            $this->save_category_stats($raw_stats, "get_synonyms_count");
            $raw_stats = array();
            
            if($this->test_taxon_concept_ids) break;
        }
    }
    
    function get_images_count($batch_size = 500000)
    {
        $trusted_id     = Vetted::trusted()->id;
        $untrusted_id   = Vetted::untrusted()->id;
        $unreviewed_id  = Vetted::unknown()->id;
        $raw_stats = array();
        for($i=$this->min_taxon_concept_id ; $i<=$this->max_taxon_concept_id ; $i+=$batch_size)
        {
            $this->print_status($i, $batch_size);
            $sql = "SELECT tc.id tc_id, do.description, dohe.vetted_id, do.id
                FROM taxon_concepts tc
                JOIN top_concept_images tci ON (tc.id = tci.taxon_concept_id)
                JOIN data_objects do ON (tci.data_object_id = do.id)
                JOIN data_objects_hierarchy_entries dohe ON (do.id=dohe.data_object_id)
                WHERE tc.published=1 AND tc.supercedure_id=0 AND do.published=1 AND dohe.visibility_id=".Visibility::visible()->id;
            if($this->test_taxon_concept_ids) $sql .= " AND tc.id IN (". $this->test_taxon_concept_ids .")";
            else $sql .= " AND tc.id BETWEEN $i AND ". ($i + $batch_size);
            
            $counted_data_objects = array();
            foreach($this->mysqli_slave->iterate_file($sql) as $row_number => $row)
            {
                $taxon_concept_id = trim($row[0]);
                $description = trim($row[1]);
                $vetted_id = trim($row[2]);
                $data_object_id = trim($row[3]);
                
                if(isset($counted_data_objects[$taxon_concept_id][$data_object_id])) continue;
                $counted_data_objects[$taxon_concept_id][$data_object_id] = 1;
                $words_count = str_word_count(strip_tags($description), 0);
                
                @$raw_stats[$taxon_concept_id]['image']['total']++;
                @$raw_stats[$taxon_concept_id]['image']['total_w'] += $words_count;
                
                if($vetted_id == $trusted_id)
                {
                    @$raw_stats[$taxon_concept_id]['image']['t']++;
                    @$raw_stats[$taxon_concept_id]['image']['t_w'] += $words_count;
                }
                elseif($vetted_id == $untrusted_id)
                {
                    @$raw_stats[$taxon_concept_id]['image']['ut']++;
                    @$raw_stats[$taxon_concept_id]['image']['ut_w'] += $words_count;
                }
                elseif($vetted_id == $unreviewed_id)
                {
                    @$raw_stats[$taxon_concept_id]['image']['ur']++;
                    @$raw_stats[$taxon_concept_id]['image']['ur_w'] += $words_count;
                }
            }
            
            //convert associative array to a regular array
            foreach($raw_stats as $taxon_concept_id => $stats)
            {
                $new_value = @$stats['image']['total'];
                $new_value .= "\t" . @$stats['image']['t'];
                $new_value .= "\t" . @$stats['image']['ut'];
                $new_value .= "\t" . @$stats['image']['ur'];
                $new_value .= "\t" . @$stats['image']['total_w'];
                $new_value .= "\t" . @$stats['image']['t_w'];
                $new_value .= "\t" . @$stats['image']['ut_w'];
                $new_value .= "\t" . @$stats['image']['ur_w'];
                $raw_stats[$taxon_concept_id] = $new_value;
            }
            $this->save_category_stats($raw_stats, "get_images_count");
            $raw_stats = array();
            
            if($this->test_taxon_concept_ids) break;
        }
    }
    
    function get_concept_references($batch_size = 500000)
    {
        for($i=$this->min_taxon_concept_id ; $i<=$this->max_taxon_concept_id ; $i+=$batch_size)
        {
            $this->print_status($i, $batch_size);
            $sql = "SELECT he.taxon_concept_id tc_id, her.ref_id
                FROM hierarchy_entries he
                JOIN hierarchy_entries_refs her ON (he.id=her.hierarchy_entry_id)
                JOIN refs r ON (her.ref_id=r.id)
                WHERE he.published = 1 AND he.visibility_id = ".Visibility::visible()->id."
                AND r.published=1 AND r.visibility_id = ".Visibility::visible()->id;
            if($this->test_taxon_concept_ids) $sql .= " AND he.taxon_concept_id IN (". $this->test_taxon_concept_ids .")";
            else $sql .= " AND he.taxon_concept_id BETWEEN $i AND ". ($i + $batch_size);
            
            $ref_counts = array();
            foreach($this->mysqli_slave->iterate_file($sql) as $row_number => $row)
            {
                $taxon_concept_id = trim($row[0]);
                $ref_id = trim($row[1]);
                $ref_counts[$taxon_concept_id][$ref_id] = 1;
            }
            
            // Now count the data object references
            $sql = "SELECT dotc.taxon_concept_id, dor.ref_id
                FROM data_objects_taxon_concepts dotc
                JOIN data_objects do ON (dotc.data_object_id=do.id)
                JOIN data_objects_refs dor ON (do.id=dor.data_object_id)
                JOIN refs r ON (dor.ref_id=r.id)
                WHERE do.published=1 AND do.visibility_id = ".Visibility::visible()->id."
                AND r.published=1 AND r.visibility_id = ".Visibility::visible()->id;
            if($this->test_taxon_concept_ids) $sql .= " AND dotc.taxon_concept_id IN (". $this->test_taxon_concept_ids .")";
            else $sql .= " AND dotc.taxon_concept_id BETWEEN $i AND ". ($i + $batch_size);
            
            $ref_counts = array();
            foreach($this->mysqli_slave->iterate_file($sql) as $row_number => $row)
            {
                $taxon_concept_id = trim($row[0]);
                $ref_id = trim($row[1]);
                $ref_counts[$taxon_concept_id][$ref_id] = 1;
            }
            
            //convert associative array to a regular array
            foreach($ref_counts as $taxon_concept_id => $refs)
            {
                $ref_counts[$taxon_concept_id] = count($refs);
            }
            $this->save_category_stats($ref_counts, "get_concept_references");
            $raw_stats = array();
            
            if($this->test_taxon_concept_ids) break;
        }
    }
    
    function get_data_objects_count($batch_size = 100000)
    {
        $image_id       = DataType::image()->id;
        $text_id        = DataType::text()->id;
        $video_id       = DataType::video()->id;
        $sound_id       = DataType::sound()->id;
        $flash_id       = DataType::flash()->id;
        $youtube_id     = DataType::youtube()->id;
        $iucn_id        = DataType::iucn()->id;
        
        $data_type_label[$text_id]       = 'text';
        $data_type_label[$video_id]      = 'video';
        $data_type_label[$sound_id]      = 'sound';
        $data_type_label[$flash_id]      = 'flash';
        $data_type_label[$youtube_id]    = 'youtube';
        $data_type_label[$iucn_id]       = 'iucn';
        $data_type_order_in_file = array("text", "video", "sound", "flash", "youtube", "iucn");
        
        $trusted_id     = Vetted::trusted()->id;
        $untrusted_id   = Vetted::untrusted()->id;
        $unreviewed_id  = Vetted::unknown()->id;
        
        $raw_stats = array();
        $concept_info_items = array();
        $concept_references = array();
        for($i=$this->min_taxon_concept_id ; $i<=$this->max_taxon_concept_id ; $i+=$batch_size)
        {
            $this->print_status($i, $batch_size);
            $sql = "SELECT  do.guid,
                            dotc.taxon_concept_id,
                            do.data_type_id,
                            doii.info_item_id,
                            dor.ref_id,
                            REPLACE(REPLACE(do.description, '\\\\n', ' '), '\\\\r', ' '),
                            dohe.vetted_id,
                            do.id
            FROM data_objects_taxon_concepts dotc
            STRAIGHT_JOIN data_objects do ON (dotc.data_object_id = do.id)
            JOIN data_objects_hierarchy_entries dohe ON (do.id=dohe.data_object_id)
            LEFT JOIN data_objects_info_items doii ON (do.id = doii.data_object_id)
            LEFT JOIN data_objects_refs dor ON (do.id = dor.data_object_id)
            WHERE do.published = 1 AND dohe.visibility_id = ".Visibility::visible()->id." AND do.data_type_id != $image_id";
            if($this->test_taxon_concept_ids) $sql .= " AND dotc.taxon_concept_id IN (". $this->test_taxon_concept_ids .")";
            else $sql .= " AND dotc.taxon_concept_id BETWEEN $i AND ". ($i + $batch_size);
            
            $counted_data_objects = array();
            foreach($this->mysqli_slave->iterate_file($sql) as $row_number => $row)
            {
                $taxon_concept_id = trim($row[0]);
                $data_type_id = trim($row[1]);
                $info_item_id = trim($row[2]);
                $ref_id = trim($row[3]);
                $description = trim($row[4]);
                $vetted_id = trim($row[5]);
                $data_object_id = trim($row[6]);
                
                if(isset($counted_data_objects[$taxon_concept_id][$data_object_id])) continue;
                $counted_data_objects[$taxon_concept_id][$data_object_id] = 1;
                $label = @$data_type_label[$data_type_id];
                $words_count = str_word_count(strip_tags($description), 0);
                
                @$raw_stats[$taxon_concept_id][$label]['total']++;
                @$raw_stats[$taxon_concept_id][$label]['total_w'] += $words_count;
                
                if($vetted_id == $trusted_id)
                {
                    @$raw_stats[$taxon_concept_id][$label]['t']++;
                    @$raw_stats[$taxon_concept_id][$label]['t_w'] += $words_count;
                }
                elseif($vetted_id == $untrusted_id)
                {
                    @$raw_stats[$taxon_concept_id][$label]['ut']++;
                    @$raw_stats[$taxon_concept_id][$label]['ut_w'] += $words_count;
                }
                elseif($vetted_id == $unreviewed_id)
                {
                    @$raw_stats[$taxon_concept_id][$label]['ur']++;
                    @$raw_stats[$taxon_concept_id][$label]['ur_w'] += $words_count;
                }
                
                $concept_info_items[$taxon_concept_id][$info_item_id] = '';
                $concept_references[$taxon_concept_id][$ref_id] = '';
            }
            
            foreach($raw_stats as $taxon_concept_id => $stats)
            {
                $new_value = "";
                # the stats need to go into the file in a certain order to be imported into the MySQL table
                foreach($data_type_order_in_file as $data_type)
                {
                    $new_value = @$stats[$data_type]['total'];
                    $new_value .= "\t" . @$stats[$data_type]['t'];
                    $new_value .= "\t" . @$stats[$data_type]['ut'];
                    $new_value .= "\t" . @$stats[$data_type]['ur'];
                    $new_value .= "\t" . @$stats[$data_type]['total_w'];
                    $new_value .= "\t" . @$stats[$data_type]['t_w'];
                    $new_value .= "\t" . @$stats[$data_type]['ut_w'];
                    $new_value .= "\t" . @$stats[$data_type]['ur_w'];
                }
                $raw_stats[$taxon_concept_id] = $new_value;
            }
            $this->save_category_stats($raw_stats, "get_data_objects_count");
            $raw_stats = array();

            if($this->test_taxon_concept_ids) break;
        }
        
        // $this->save_to_json_file($concept_info_items, "concept_info_items");
        // unset($concept_info_items);
        // 
        // $this->save_to_json_file($concept_references, "concept_references");
        // unset($concept_references);
    }
    
    function save_to_json_file($arr, $filename)
    {
        if(!($WRITE = fopen(PAGE_METRICS_TEXT_PATH . $filename . ".txt", "w")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: ". PAGE_METRICS_TEXT_PATH . $filename . ".txt");
          return;
        }
        fwrite($WRITE, json_encode($arr));
        fclose($WRITE);
    }
    
    function get_array_from_json_file($filename)
    {
        $filename = PAGE_METRICS_TEXT_PATH . $filename . ".txt";
        if(!($READ = fopen($filename, "r")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: ". $filename);
          return;
        }
        $contents = fread($READ, filesize($filename));
        fclose($READ);
        return json_decode($contents, true);
    }
    
    function initialize_category_file($category)
    {
        $file_path = PAGE_METRICS_TEXT_PATH . "$category.txt";
        if(file_exists($file_path))
        {
            rename($file_path, $file_path .".tmp");
        }
        if(!($OUT = fopen($file_path, "w+")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$file_path);
          return;
        }
        fclose($OUT);
    }
    
    function save_category_stats($stats, $category)
    {
        if(!($OUT = fopen(PAGE_METRICS_TEXT_PATH . "$category.txt", "a+")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: ". PAGE_METRICS_TEXT_PATH . "$category.txt");
          return;
        }
        ksort($stats);
        while(list($taxon_concept_id, $stat) = each($stats))
        {
            fwrite($OUT, "$taxon_concept_id\t$stat\n");
        }
        fclose($OUT);
    }
    
    function save_totals_to_cumulative_txt($arr, $category)
    {
        // expects $arr to equal "\t#" or "\t#\t#\t#"
        rename(PAGE_METRICS_TEXT_PATH . "taxon_concept_metrics.txt", PAGE_METRICS_TEXT_PATH . "taxon_concept_metrics.txt.tmp");
        if(!($WRITE = fopen(PAGE_METRICS_TEXT_PATH . "taxon_concept_metrics.txt", "a")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: ". PAGE_METRICS_TEXT_PATH . "taxon_concept_metrics.txt");
          return;
        }
        
        if(!($READ = fopen(PAGE_METRICS_TEXT_PATH . "taxon_concept_metrics.txt.tmp", "r")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: ".PAGE_METRICS_TEXT_PATH . "taxon_concept_metrics.txt.tmp");
          return;
        }
        $num_rows = 0;
        $str = "";
        
        while(!feof($READ))
        {
            if($line = fgets($READ))
            {
                $num_rows++;
                $line = rtrim($line, "\n");
                $fields = explode("\t", $line);
                $tc_id = trim(@$fields[0]);
                
                fwrite($WRITE, $line);
                if(isset($arr[$tc_id])) fwrite($WRITE, $arr[$tc_id]);
                else
                {
                    if    ($category == "tpm_user_added_text"       ||
                           $category == "get_common_names_count"    ||
                           $category == "get_synonyms_count"        ||
                           $category == "tpm_references_infoitems"  ||
                           $category == "tpm_google_stats")         fwrite($WRITE, str_repeat("\t", 2));
                    elseif($category == "tpm_data_objects")         fwrite($WRITE, str_repeat("\t", 48));
                    elseif($category == "get_images_count")         fwrite($WRITE, str_repeat("\t", 8));
                    else                                            fwrite($WRITE, str_repeat("\t", 1));
                }
                fwrite($WRITE, "\n");
            }
        }
        fclose($READ);
        fclose($WRITE);
    }
    
    function save_to_table()
    {
        $filename = PAGE_METRICS_TEXT_PATH . "taxon_concept_metrics.txt";
        
        $this->mysqli->delete("DROP TABLE IF EXISTS taxon_concept_metrics_tmp");
        $this->mysqli->insert("CREATE TABLE IF NOT EXISTS taxon_concept_metrics_tmp LIKE taxon_concept_metrics");
        $this->mysqli->delete("TRUNCATE TABLE taxon_concept_metrics_tmp");
        
        $this->mysqli->load_data_infile($filename, "taxon_concept_metrics_tmp");
        
        // $result = $this->mysqli->query("SELECT 1 FROM taxon_concept_metrics_tmp LIMIT 1");
        // if($result && $row = $result->fetch_assoc())
        // {
        //     $this->mysqli->swap_tables('taxon_concept_metrics', 'taxon_concept_metrics_tmp');
        // }
    }
    
    /* work in progress - or may use a different approach altogether when we store taxon_concept_id in PAGE_NAMES table.
    public function get_concepts_with_bhl_publications()
    {
        $start = 0;
        $max_id = 9;
        $iteration_size = 10000;

//         $result = $mysqli->query("SELECT min(id) min, max(id) max FROM taxon_concepts WHERE supercedure_id=0 AND published=1");
//         if($result && $row = $result->fetch_assoc())
//         {
//             $start = $row['min'];
//             $max_id = $row['max'];
//         }

        $file_path = PAGE_METRICS_TEXT_PATH . "taxon_concept_with_bhl_links_test.txt";
        $OUT = fopen($file_path, "w+");
        for($i=$start ; $i<$max_id ; $i+=$iteration_size)
        {
            $outfile = $this->mysqli_slave->select_into_outfile("
                SELECT tcn.taxon_concept_id, ip.title_item_id
                FROM taxon_concept_names tcn
                JOIN page_names pn ON (tcn.name_id=pn.name_id)
                JOIN item_pages ip ON (pn.item_page_id=ip.id)
                WHERE tcn.taxon_concept_id BETWEEN $i AND ".($i+$iteration_size)."
                ORDER BY tcn.taxon_concept_id");

            $previous_taxon_concept_id = 0;
            $this_concepts_items = array();
            //$it = new FileIterator($outfile);
            foreach(array(1, 2, 3) as $val)
            {
                $columns = explode("\t", $line);
                $taxon_concept_id = $columns[0];
                $title_item_id = $columns[1];

                // we're on a new concept
                if($taxon_concept_id != $previous_taxon_concept_id)
                {
                    // write this concepts items to file
                    if($this_concepts_items)
                    {
                        fwrite($OUT, "$previous_taxon_concept_id\t". count($this_concepts_items) ."\n");
                    }

                    // reset item counter
                    $this_concepts_items = array();
                }

                // add this item to the counter
                $this_concepts_items[$title_item_id] = 1;
            }

            // write the items for the last concept
            if($this_concepts_items)
            {
                fwrite($OUT, "$previous_taxon_concept_id\t". count($this_concepts_items) ."\n");
            }

            // remove the mysql result file
            unlink($outfile);
        }
        fclose($OUT);
    }
    */
    
    public function generate_taxon_concept_with_bhl_links_textfile() //execution time: 6 mins.
    {
        /*  This will generate the [taxon_concept_with_bhl_links.txt].
            Lists all concepts with BHL links. */
        
        $timestart = microtime(1);
        
        /* this takes 4 hours
        print "\n start - generate_taxon_concept_with_bhl_links_textfile";
        $outfile = $this->mysqli_slave->select_into_outfile("SELECT DISTINCT tc.id tc_id FROM taxon_concepts tc JOIN taxon_concept_names tcn on (tc.id=tcn.taxon_concept_id) JOIN page_names pn on (tcn.name_id=pn.name_id) WHERE tc.supercedure_id=0 AND tc.published=1");
        $fp = fopen($outfile, "r");
        $file_contents = fread($fp, filesize($outfile));
        fclose($fp);
        $filename = PAGE_METRICS_TEXT_PATH . "taxon_concept_with_bhl_links.txt.tmp";
        $fp = fopen($filename, "w"); print "\n writing..."; fwrite($fp, $file_contents); fclose($fp); print "\n saved.";
        */
        
        $arr_taxa = array();
        $batch = 500000;
        $start_limit = 0;
        $tc_ids = array();
        while(true)
        {
            print "\n generate_taxon_concept_with_bhl_links_textfile -- $start_limit \n";
            
            $elapsed_time_sec = microtime(1) - $timestart;
            print "\n elapsed time = " . $elapsed_time_sec/60 . " mins   ";
            print "\n elapsed time = " . $elapsed_time_sec/60/60 . " hrs   ";
            
            $sql = "SELECT distinct tc.id tc_id FROM taxon_concepts tc JOIN taxon_concept_names tcn on (tc.id=tcn.taxon_concept_id) JOIN page_names pn on (tcn.name_id=pn.name_id) WHERE tc.supercedure_id=0 AND tc.published=1";
            $sql .= " limit $start_limit, $batch ";
            
            $outfile = $this->mysqli_slave->select_into_outfile($sql);
            $start_limit += $batch;
            if(!($FILE = fopen($outfile, "r")))
            {
              debug(__CLASS__ .":". __LINE__ .": Couldn't open file: ". $outfile);
              return;
            }
            $num_rows = 0;
            while(!feof($FILE))
            {
                if($line = fgets($FILE))
                {
                    $num_rows++;
                    $fields = explode("\t", $line);
                    $tc_id      = trim($fields[0]);
                    $tc_ids[$tc_id] = "";
                }
            }
            fclose($FILE);
            unlink($outfile);
            print "\n num_rows: $num_rows";
            if($num_rows < $batch) break;
        }
        
        $str = "";
        foreach($tc_ids as $id => $rec) $str .= $id . "\n";
        unset($tc_ids);
        
        $filename = PAGE_METRICS_TEXT_PATH . "taxon_concept_with_bhl_links.txt.tmp";
        if(!($fp = fopen($filename, "w")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: ". $filename);
          return;
        }
        print "\n writing...";
        fwrite($fp, $str);
        fclose($fp);
        print "\n saved.";
        
        //rename
        unlink(PAGE_METRICS_TEXT_PATH . "taxon_concept_with_bhl_links.txt");
        rename(PAGE_METRICS_TEXT_PATH . "taxon_concept_with_bhl_links.txt.tmp", PAGE_METRICS_TEXT_PATH . "taxon_concept_with_bhl_links.txt");
        print "\n end - generate_taxon_concept_with_bhl_links_textfile";
        $elapsed_time_sec = microtime(1) - $timestart;
        print "\n elapsed time = " . $elapsed_time_sec/60 . " mins   ";
        print "\n elapsed time = " . $elapsed_time_sec/60/60 . " hrs   ";
    }
    
    // Working but will be replaced once we store taxon_concept_id in PAGE_NAMES table.
    public function generate_taxon_concept_with_bhl_publications_textfile() //execution time: 5 hrs
    {
        /*  This will generate the [taxon_concept_with_bhl_publications.txt].
            Assigns # of BHL publications for every concept.
        */
        
        $timestart = microtime(1);
        $write_filename = PAGE_METRICS_TEXT_PATH . "taxon_concept_with_bhl_publications.txt.tmp";
        unlink($write_filename);
        if(!($fp = fopen($write_filename, "a")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: ". $write_filename);
          return;
        }
        
        //start reading text file
        print "\n Start reading text file [taxon_concept_with_bhl_links] \n";
        $filename = PAGE_METRICS_TEXT_PATH . "taxon_concept_with_bhl_links.txt";
        if(!($FILE = fopen($filename, "r")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: ". $filename);
          return;
        }
        $i = 0;
        $str = "";
        $save_count = 0;
        while(!feof($FILE))
        {
            if($line = fgets($FILE))
            {
                $line = trim($line);
                $fields = explode("\t", $line);
                if($tc_id = trim($fields[0]))
                {
                    $sql = "Select ip.title_item_id From taxon_concept_names tcn Inner Join page_names pn ON tcn.name_id = pn.name_id Inner Join item_pages ip ON pn.item_page_id = ip.id Where tcn.taxon_concept_id = $tc_id ";
                    $result = $this->mysqli_slave->query($sql);
                    $arr = array();
                    while($result && $row = $result->fetch_assoc())
                    {
                        $title_item_id = $row['title_item_id'];
                        $arr[$title_item_id] = '';
                    }
                    $publications = count(array_keys($arr));
                    $str .= $tc_id . "\t" . $publications . "\n";
                    $i++;
                    print "\n $i. [$tc_id][$publications] ";
                    
                    //saving
                    $save_count++;
                    if($save_count == 10000)
                    {
                        print "\n writing...";
                        fwrite($fp, $str);
                        print " saved.";
                        $str = "";
                        $save_count = 0;
                    }
                    //if($i >= 15) break; //debug
                }
            }
        }
        fclose($FILE);
        //last remaining writes
        print "\n writing...";
        fwrite($fp, $str);
        print " saved.";
        fclose($fp);
        //rename
        unlink(PAGE_METRICS_TEXT_PATH . "taxon_concept_with_bhl_publications.txt");
        rename(PAGE_METRICS_TEXT_PATH . "taxon_concept_with_bhl_publications.txt.tmp", PAGE_METRICS_TEXT_PATH . "taxon_concept_with_bhl_publications.txt");
        
        print "\n end - generate_taxon_concept_with_bhl_publications_textfile";
        $elapsed_time_sec = microtime(1) - $timestart;
        print "\n elapsed time = " . $elapsed_time_sec/60/60 . " hrs   ";
    }

}
?>
