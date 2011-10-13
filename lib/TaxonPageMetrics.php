<?php
namespace php_active_record;

/* This is the old version of the TaxonPageMetrics. */

define("PAGE_METRICS_TEXT_PATH", DOC_ROOT . "applications/taxon_page_metrics/text_files/");
class TaxonPageMetrics
{
    private $mysqli;

    public function __construct()
    {
        $this->mysqli =& $GLOBALS['mysqli_connection'];
        if($GLOBALS['ENV_NAME'] == 'production' && environment_defined('slave')) $this->mysqli_slave = load_mysql_environment('slave');
        else $this->mysqli_slave =& $this->mysqli;
    }

    /* prepare taxon concept totals for richness calculations */
    public function insert_page_metrics()
    {
        //$tc_id=218284 --- with user-submitted-text    
        //$GLOBALS['test_taxon_concept_ids'] = array(206692,1,218294,7921,218284,328450,213726);
        self::initialize_concepts_list();
        self::get_images_count();                     //1
        self::get_data_objects_count();               //2
        self::get_concept_references();               //3
        self::get_BHL_publications();                 //4
        self::get_content_partner_count();            //5
        self::get_outlinks_count();                   //6
        self::get_GBIF_map_availability();            //7
        self::get_biomedical_terms_availability();    //8
        self::get_user_submitted_text_count();        //9
        self::get_common_names_count(1);              //10
        self::get_synonyms_count(1);                  //11
        self::get_google_stats();                     //12
        self::get_richness_score();                   //13
        self::save_to_table();
    }

    function initialize_concepts_list()
    {
        $time_start = time_elapsed();
        $sql = "SELECT tc.id FROM taxon_concepts tc WHERE tc.published = 1 AND tc.supercedure_id = 0";
        if(isset($GLOBALS['test_taxon_concept_ids'])) $sql.=" and tc.id in (".implode(",", $GLOBALS['test_taxon_concept_ids']).")";
        $outfile = $this->mysqli_slave->select_into_outfile($sql);
        if (!copy($outfile, PAGE_METRICS_TEXT_PATH . "taxon_concept_metrics.txt")) print "\n failed to copy $outfile...\n";
        unlink($outfile);
        print "\n initialize_concepts_list():" . (time_elapsed()-$time_start)/60 . " minutes";
    }

    function get_richness_score()
    {
        print "\n Richness score: [13 of 13] \n";
        $time_start = time_elapsed();
        require_library('PageRichnessCalculator');
        $run = new PageRichnessCalculator();
        $arr_taxa = array();
        $READ = fopen(PAGE_METRICS_TEXT_PATH . "taxon_concept_metrics.txt", "r");
        while(!feof($READ))
        {
            if($line = fgets($READ))
            {
                $line = rtrim($line, "\n");
                $fields = explode("\t", $line);
                $tc_id = $fields[0];
                // /0/ `taxon_concept_id` x 
                // /2/ `image_trusted` x
                // /4/ `image_unreviewed` x
                // /10/ `text_trusted` x
                // /12/ `text_unreviewed` x
                // /14/ `text_trusted_words` x
                // /16/ `text_unreviewed_words` x
                // /18/ `video_trusted` x
                // /20/ `video_unreviewed` x
                // /26/ `sound_trusted` x
                // /28/ `sound_unreviewed` x
                // /34/ `flash_trusted` x
                // /36/ `flash_unreviewed` x
                // /42/ `youtube_trusted` x
                // /44/ `youtube_unreviewed` x
                // /49/ `iucn_total` x
                // /57/ `data_object_references` x
                // /58/ `info_items` x
                // /59/ `BHL_publications` x
                // /60/ `content_partners` x
                // /62/ `has_GBIF_map` x
                // /63/ `has_biomedical_terms` 
                // /64/ `user_submitted_text` x
                $row = array($fields[0], $fields[2], $fields[4], $fields[10], $fields[12], $fields[14], $fields[16], $fields[18], $fields[20], $fields[26], 
                             $fields[28], $fields[34], $fields[36], $fields[42], $fields[44], $fields[49], $fields[57], $fields[58], $fields[60], $fields[62], 
                             $fields[59], $fields[64]);
                print "\n count=".count($fields);
                $scores = $run->calculate_score_from_row($row);
                $arr_taxa[$tc_id] = "\t" . $scores['total'];
            }
        }
        print "\n get_richness_score():" . (time_elapsed()-$time_start)/60 . " minutes";
        self::save_totals_to_cumulative_txt($arr_taxa, "tpm_richness_score"); unset($arr_taxa);
    }

    function get_google_stats()
    {
        $time_start = time_elapsed();
        $arr_taxa = array();
        //get the last 12 months - descending order
        $sql = "SELECT concat(gas.`year`,'_',substr(gas.`month` / 100,3,2)) as `year_month` FROM google_analytics_summaries gas ORDER BY gas.`year` DESC, gas.`month` DESC LIMIT 11,1";
        $result = $this->mysqli_slave->query($sql);
        if($result && $row=$result->fetch_assoc()) $year_month = $row['year_month'];
        $batch = 500000;
        $start_limit = 0;
        while(true)
        {
            print "\n Google stats: page_views, unique_page_views [12 of 13] $start_limit \n";
            $sql = "SELECT gaps.taxon_concept_id, gaps.page_views, gaps.unique_page_views FROM google_analytics_page_stats gaps 
            WHERE concat(gaps.year,'_',substr(gaps.month/100,3,2)) >= '$year_month'";
            if(isset($GLOBALS['test_taxon_concept_ids'])) $sql .= " and gaps.taxon_concept_id IN (".implode(",", $GLOBALS['test_taxon_concept_ids']) .")";
            $sql .= " limit $start_limit, $batch ";
            $outfile = $this->mysqli_slave->select_into_outfile($sql);
            $start_limit += $batch;
            $FILE = fopen($outfile, "r");
            $num_rows = 0;
            while(!feof($FILE))
            {
                if($line = fgets($FILE))
                {
                    $num_rows++; $line = trim($line); $fields = explode("\t", $line);
                    $tc_id             = trim($fields[0]);
                    $page_views        = trim($fields[1]);
                    $unique_page_views = trim($fields[2]);
                    @$arr_taxa[$tc_id]['pv'] += $page_views;
                    @$arr_taxa[$tc_id]['upv'] += $unique_page_views;
                }
            }
            fclose($FILE);unlink($outfile);
            print "\n num_rows: $num_rows";
            if($num_rows < $batch) break; 
        }
        //convert associative array to a regular array
        foreach($arr_taxa as $tc_id => $taxon_views_counts)
        {
            $new_value = "";
            $new_value .= "\t".@$taxon_views_counts['pv'];
            $new_value .= "\t".@$taxon_views_counts['upv'];
            $arr_taxa[$tc_id] = $new_value;
        }
        print "\n get_google_stats():" . (time_elapsed()-$time_start)/60 . " minutes";
        self::save_totals_to_cumulative_txt($arr_taxa, "tpm_google_stats"); unset($arr_taxa);
    }

    function get_BHL_publications()
    {
        $time_start = time_elapsed();
        $arr_taxa = array();
        print "\n BHL publications [4 of 13]\n";
        $filename = PAGE_METRICS_TEXT_PATH . "taxon_concept_with_bhl_publications.txt";
        $FILE = fopen($filename, "r");
        $num_rows=0;
        while(!feof($FILE))
        {
            if($line = fgets($FILE))
            {
                $num_rows++; $line = trim($line); $fields = explode("\t", $line);
                $tc_id        = trim(@$fields[0]);
                $publications = trim(@$fields[1]);
                $arr_taxa[$tc_id] = "\t".$publications;
            }
        }
        print "\n get_BHL_publications():" . (time_elapsed()-$time_start)/60 . " minutes";
        self::save_totals_to_cumulative_txt($arr_taxa, "tpm_BHL"); unset($arr_taxa);
    }

    function get_biomedical_terms_availability()
    {
        $time_start = time_elapsed();
        $arr_taxa = array();
        print "\n BOA_biomedical_terms [8 of 13]\n";
        $BOA_agent_id = Agent::find('Biology of Aging');
        if(!$BOA_agent_id) 
        {
            self::save_totals_to_cumulative_txt(array(), "tpm_biomedical_terms");
            return;
        }
        $result = $this->mysqli_slave->query("SELECT Max(harvest_events.id) latest_harvent_event_id FROM harvest_events JOIN agents_resources ON agents_resources.resource_id = harvest_events.resource_id WHERE agents_resources.agent_id = $BOA_agent_id AND harvest_events.published_at Is Not Null");
        if($result && $row=$result->fetch_assoc()) $latest_harvent_event_id = $row['latest_harvent_event_id'];
        $sql = "SELECT he.taxon_concept_id tc_id FROM harvest_events_hierarchy_entries hehe JOIN hierarchy_entries he ON hehe.hierarchy_entry_id = he.id WHERE hehe.harvest_event_id = $latest_harvent_event_id ";
        if(isset($GLOBALS['test_taxon_concept_ids'])) $sql .= " and he.taxon_concept_id IN (". implode(",", $GLOBALS['test_taxon_concept_ids']) .")";
        $outfile = $this->mysqli_slave->select_into_outfile($sql);
        $FILE = fopen($outfile, "r");
        $num_rows=0;
        while(!feof($FILE))
        {
            if($line = fgets($FILE))
            {
                $num_rows++; $line = trim($line); $fields = explode("\t", $line);
                $tc_id = trim($fields[0]);
                $arr_taxa[$tc_id]="\t" . "1";
            }
        }
        fclose($FILE);unlink($outfile);
        print "\n num_rows: $num_rows";
        print "\n get_biomedical_terms_availability():" . (time_elapsed()-$time_start)/60 . " minutes";
        self::save_totals_to_cumulative_txt($arr_taxa, "tpm_biomedical_terms"); unset($arr_taxa);
    }

    function get_GBIF_map_availability()
    {
        $time_start = time_elapsed();
        $arr_taxa = array();
        print "\n GBIF_map [7 of 13]\n";
        $sql = "SELECT tc.id tc_id FROM hierarchies_content hc JOIN hierarchy_entries he ON hc.hierarchy_entry_id = he.id JOIN taxon_concepts tc ON he.taxon_concept_id = tc.id WHERE hc.map > 0 AND tc.published = 1 AND tc.supercedure_id=0 ";
        if(isset($GLOBALS['test_taxon_concept_ids'])) $sql .= " and tc.id IN (". implode(",", $GLOBALS['test_taxon_concept_ids']) .")";
        $outfile = $this->mysqli_slave->select_into_outfile($sql);
        $FILE = fopen($outfile, "r");
        $num_rows=0;
        while(!feof($FILE))
        {
            if($line = fgets($FILE))
            {
                $num_rows++; $line = trim($line); $fields = explode("\t", $line);
                $tc_id = trim($fields[0]);
                $arr_taxa[$tc_id]="\t" . "1";
            }
        }
        fclose($FILE);unlink($outfile);
        print "\n num_rows: $num_rows";
        print "\n get_GBIF_map_availability():" . (time_elapsed()-$time_start)/60 . " minutes";
        self::save_totals_to_cumulative_txt($arr_taxa, "tpm_GBIF"); unset($arr_taxa);
    }

    function get_user_submitted_text_count()
    {
        $time_start = time_elapsed();
        $arr_taxa = array();
        print "\n user_submitted_text, its providers [9 of 13]\n";

        $sql = "SELECT udo.taxon_concept_id tc_id, udo.data_object_id do_id, udo.user_id FROM users_data_objects udo JOIN data_objects do ON udo.data_object_id = do.id WHERE do.published=1 AND (udo.vetted_id IS NULL OR udo.vetted_id != " . Vetted::untrusted()->id .")";


        if(isset($GLOBALS['test_taxon_concept_ids'])) $sql .= " and udo.taxon_concept_id IN (". implode(",", $GLOBALS['test_taxon_concept_ids']) .")";
        $outfile = $this->mysqli_slave->select_into_outfile($sql);
        $FILE = fopen($outfile, "r");
        $num_rows = 0; 
        $tc_do_id = array(); 
        $tc_user_id = array();
        while(!feof($FILE))
        {
            if($line = fgets($FILE))
            {
                $num_rows++; $line = trim($line); $fields = explode("\t", $line);
                $tc_id   = trim($fields[0]);
                $do_id   = trim($fields[1]);
                $user_id = trim($fields[2]);
                $tc_do_id[$tc_id][$do_id] = '';
                $tc_user_id[$tc_id][$user_id] = '';
            }
        }
        fclose($FILE); unlink($outfile); print "\n num_rows: $num_rows";
        foreach($tc_do_id as $id => $rec) {@$arr_taxa[$id]['count'] = sizeof($rec);}
        unset($tc_do_id);
        foreach($tc_user_id as $id => $rec) {@$arr_taxa[$id]['providers'] = sizeof($rec);}
        unset($tc_user_id);

        //convert associative array to a regular array
        foreach($arr_taxa as $tc_id => $taxon_addedText_counts)
        {
            $new_value = "";
            $new_value .= "\t".@$taxon_addedText_counts['count'];
            $new_value .= "\t".@$taxon_addedText_counts['providers'];
            $arr_taxa[$tc_id] = $new_value;
        }
        print "\n get_user_submitted_text_count():" . (time_elapsed()-$time_start)/60 . " minutes";
        self::save_totals_to_cumulative_txt($arr_taxa, "tpm_user_added_text"); unset($arr_taxa);
    }

    function get_content_partner_count()
    {
        $time_start = time_elapsed();
        $arr_taxa = array();
        $batch = 500000; 
        $start_limit = 0;
        while(true)
        {
            $tc_hierarchy_id = array();
            print "\n content_partners [5 of 13] $start_limit \n";
            $sql = "SELECT he.taxon_concept_id tc_id, he.hierarchy_id FROM hierarchy_entries he where he.published = 1 AND he.visibility_id=".Visibility::visible()->id;
            if(isset($GLOBALS['test_taxon_concept_ids'])) $sql .= " and he.taxon_concept_id IN (". implode(",", $GLOBALS['test_taxon_concept_ids']) .")";
            $sql .= " ORDER BY he.taxon_concept_id";
            $sql .= " limit $start_limit, $batch ";
            $outfile = $this->mysqli_slave->select_into_outfile($sql);
            $start_limit += $batch;
            $FILE = fopen($outfile, "r");
            $num_rows = 0;
            while(!feof($FILE))
            {
                if($line = fgets($FILE))
                {
                    $num_rows++; $line = trim($line); $fields = explode("\t", $line);
                    $tc_id          = trim($fields[0]);
                    $hierarchy_id   = trim($fields[1]);
                    $tc_hierarchy_id[$tc_id][$hierarchy_id]='';
                }
            }
            fclose($FILE);unlink($outfile);
            print "\n num_rows: $num_rows";

            foreach($tc_hierarchy_id as $id => $rec){@$arr_taxa[$id] = "\t".sizeof($rec);}
            unset($tc_hierarchy_id);
            if($num_rows < $batch) break;
        }
        print "\n get_content_partner_count():" . (time_elapsed()-$time_start)/60 . " minutes";
        self::save_totals_to_cumulative_txt($arr_taxa, "tpm_content_partners"); unset($arr_taxa);
    }

    /*
    function get_content_partner_count() == old count, counts partners which contributed actual data
    {
        $time_start = time_elapsed();
        $arr_taxa = array();
        $batch = 500000; 
        $start_limit = 0;
        $sql = "SELECT MAX(harvest_events.id) he_id FROM harvest_events WHERE harvest_events.published_at IS NOT NULL GROUP BY harvest_events.resource_id";
        $result = $this->mysqli_slave->query($sql);
        $latest_harvest_event_ids=array();
        while($result && $row=$result->fetch_assoc()){$latest_harvest_event_ids[] = $row["he_id"];}
        $tc_agent_id = array();
        while(true)
        {
            print "\n content_partners [4 of 11] $start_limit \n";
            $sql = "SELECT tc.id tc_id, ar.agent_id FROM taxon_concepts tc JOIN data_objects_taxon_concepts dotc ON tc.id = dotc.taxon_concept_id JOIN data_objects_harvest_events dohe ON dotc.data_object_id = dohe.data_object_id JOIN harvest_events he ON dohe.harvest_event_id = he.id JOIN agents_resources ar ON he.resource_id = ar.resource_id WHERE tc.published=1 AND tc.supercedure_id=0 and he.id in (" . implode($latest_harvest_event_ids, ",") . ")";
            if(isset($GLOBALS['test_taxon_concept_ids'])) $sql .= " and tc.id IN (". implode(",", $GLOBALS['test_taxon_concept_ids']) .")";
            $sql .= " limit $start_limit, $batch ";
            $outfile = $this->mysqli_slave->select_into_outfile($sql);
            $start_limit += $batch;
            $FILE = fopen($outfile, "r");
            $num_rows = 0;
            while(!feof($FILE))
            {
                if($line = fgets($FILE))
                {
                    $num_rows++; $line = trim($line); $fields = explode("\t", $line);
                    $tc_id      = trim($fields[0]);
                    $agent_id   = trim($fields[1]);
                    $tc_agent_id[$tc_id][$agent_id]='';
                }
            }
            fclose($FILE);unlink($outfile);
            print "\n num_rows: $num_rows";
            if($num_rows < $batch) break;
        }
        foreach($tc_agent_id as $id => $rec){@$arr_taxa[$id] = "\t".sizeof($rec);} unset($tc_agent_id);
        print "\n get_content_partner_count():" . (time_elapsed()-$time_start)/60 . " minutes";
        self::save_totals_to_cumulative_txt($arr_taxa, "tpm_content_partners"); unset($arr_taxa);
    }*/

    function get_outlinks_count()
    {
        $time_start = time_elapsed();
        $arr_taxa = array();
        $batch = 500000; 
        $start_limit = 0;
        while(true)
        {
            $temp = array();
            print "\n outlinks [6 of 13] $start_limit \n";
            $sql = "SELECT he.taxon_concept_id tc_id, h.agent_id FROM hierarchies h JOIN hierarchy_entries he ON h.id = he.hierarchy_id WHERE (he.source_url != '' || ( h.outlink_uri is not null AND he.identifier != ''))";
            if(isset($GLOBALS['test_taxon_concept_ids'])) $sql .= " AND he.taxon_concept_id IN (". implode(",", $GLOBALS['test_taxon_concept_ids']) .")";
            $sql .= " ORDER BY he.taxon_concept_id ";
            $sql .= " limit $start_limit, $batch ";
            $outfile = $this->mysqli_slave->select_into_outfile($sql);
            $start_limit += $batch;
            $FILE = fopen($outfile, "r");
            $num_rows = 0;
            while(!feof($FILE))
            {
                if($line = fgets($FILE))
                {
                    $num_rows++; $fields = explode("\t", $line);
                    $tc_id      = $fields[0];
                    $agent_id   = $fields[1];
                    if(@$temp[$tc_id]) $temp[$tc_id] .= "_" . $agent_id;
                    else               $temp[$tc_id] = $agent_id;
                }
            }
            fclose($FILE); unlink($outfile);
            print "\n num_rows: $num_rows";
            foreach($temp as $id => $rec)
            {
                $arr = explode("_",$rec);
                $arr = array_unique($arr);
                $arr_taxa[$id] = "\t".sizeof($arr);
            }
            unset($temp);
            if($num_rows < $batch) break;
        }
        print "\n get_outlinks_count():" . (time_elapsed()-$time_start)/60 . " minutes";
        self::save_totals_to_cumulative_txt($arr_taxa, "tpm_outlinks"); unset($arr_taxa);
    }

    function get_common_names_count($enable)
    {
        $time_start = time_elapsed();
        $arr_taxa = array();
        if(!$enable)
        {
            self::save_totals_to_cumulative_txt($arr_taxa, "tpm_common_names"); unset($arr_taxa);
            return;
        }
        $batch = 500000; 
        $start_limit = 0;
        $tc_name_id = array(); 
        $tc_hierarchy_id = array();
        while(true)
        {
            print "\n common_names and its providers [10 of 13] $start_limit \n";

            $sql = "SELECT he.taxon_concept_id tc_id, s.name_id, s.hierarchy_id h_id 
            FROM hierarchy_entries he 
            JOIN synonyms s ON he.id = s.hierarchy_entry_id 
            WHERE s.synonym_relation_id IN (" . SynonymRelation::find_by_translated('label', "common name")->id . "," . SynonymRelation::find_by_translated('label', "genbank common name")->id . ")
            AND he.published=1 AND he.visibility_id=". Visibility::visible()->id;



            
            if(isset($GLOBALS['test_taxon_concept_ids'])) $sql .= " and he.taxon_concept_id IN (". implode(",", $GLOBALS['test_taxon_concept_ids']) .")";
            $sql .= " limit $start_limit, $batch ";
            $outfile = $this->mysqli_slave->select_into_outfile($sql);
            $start_limit += $batch;
            $FILE = fopen($outfile, "r");
            $num_rows=0;
            while(!feof($FILE))
            {
                if($line = fgets($FILE))
                {
                    $num_rows++; $line = trim($line); $fields = explode("\t", $line);
                    $tc_id      = trim($fields[0]);
                    $name_id    = trim($fields[1]);
                    $h_id       = trim($fields[2]);
                    $tc_name_id[$tc_id][$name_id]='';
                    $tc_hierarchy_id[$tc_id][$h_id]='';
                }
            }
            fclose($FILE);unlink($outfile);
            print "\n num_rows: $num_rows";
            if($num_rows < $batch) break;
        }
        foreach($tc_name_id as $id => $rec) {@$arr_taxa[$id]['count'] = sizeof($rec);}
        unset($tc_name_id);
        foreach($tc_hierarchy_id as $id => $rec) {@$arr_taxa[$id]['providers'] = sizeof($rec);}
        unset($tc_hierarchy_id);
        //convert associative array to a regular array
        foreach($arr_taxa as $tc_id => $taxon_comname_counts)
        {
            $new_value = "";
            $new_value .= "\t".@$taxon_comname_counts['count'];
            $new_value .= "\t".@$taxon_comname_counts['providers'];
            $arr_taxa[$tc_id] = $new_value;
        }
        print "\n get_common_names_count():" . (time_elapsed()-$time_start)/60 . " minutes";
        self::save_totals_to_cumulative_txt($arr_taxa, "tpm_common_names"); unset($arr_taxa);
    }

    function get_synonyms_count($enable)
    {
        $time_start = time_elapsed();
        $arr_taxa = array();
        if(!$enable)
        {
            self::save_totals_to_cumulative_txt($arr_taxa, "tpm_synonyms"); unset($arr_taxa);
            return;
        }
        $batch = 500000; 
        $start_limit = 0;
        $tc_name_id = array(); 
        $tc_hierarchy_id = array();
        while(true)
        {
            print "\n synonyms and its providers [11 of 13] $start_limit \n";
            $sql = "SELECT he.taxon_concept_id tc_id, s.name_id, s.hierarchy_id h_id 
            FROM hierarchy_entries he 
            JOIN synonyms s ON he.id = s.hierarchy_entry_id 
            JOIN hierarchies h ON s.hierarchy_id = h.id 
            WHERE s.synonym_relation_id NOT IN (" . SynonymRelation::find_by_translated('label', "common name")->id . "," . SynonymRelation::find_by_translated('label', "genbank common name")->id . ")
            AND h.browsable=1 AND he.published=1 AND he.visibility_id=". Visibility::visible()->id;

            if(isset($GLOBALS['test_taxon_concept_ids'])) $sql .= " and he.taxon_concept_id IN (". implode(",", $GLOBALS['test_taxon_concept_ids']) .")";
            $sql .= " limit $start_limit, $batch ";
            $outfile = $this->mysqli_slave->select_into_outfile($sql);
            $start_limit += $batch;
            $FILE = fopen($outfile, "r");
            $num_rows = 0;
            while(!feof($FILE))
            {
                if($line = fgets($FILE))
                {
                    $num_rows++; $line = trim($line); $fields = explode("\t", $line);
                    $tc_id      = trim($fields[0]);
                    $name_id    = trim($fields[1]);
                    $h_id       = trim($fields[2]);
                    $tc_name_id[$tc_id][$name_id] = '';
                    $tc_hierarchy_id[$tc_id][$h_id] = '';
                }
            }
            fclose($FILE); unlink($outfile);
            print "\n num_rows: $num_rows";
            if($num_rows < $batch) break; 
        }
        foreach($tc_name_id as $id => $rec) {@$arr_taxa[$id]['count'] = sizeof($rec);}
        unset($tc_name_id);
        foreach($tc_hierarchy_id as $id => $rec) {@$arr_taxa[$id]['providers'] = sizeof($rec);}
        unset($tc_hierarchy_id);
        //convert associative array to a regular array
        foreach($arr_taxa as $tc_id => $taxon_synonym_counts)
        {
            $new_value = "";
            $new_value .= "\t".@$taxon_synonym_counts['count'];
            $new_value .= "\t".@$taxon_synonym_counts['providers'];
            $arr_taxa[$tc_id] = $new_value;
        }
        print "\n get_synonyms_count():" . (time_elapsed()-$time_start)/60 . " minutes";
        self::save_totals_to_cumulative_txt($arr_taxa, "tpm_synonyms"); unset($arr_taxa);
    }

    function get_images_count()
    {            
        $time_start = time_elapsed(); 
        $concept_data_object_counts = array();
        $trusted_id     = Vetted::trusted()->id;
        $untrusted_id   = Vetted::untrusted()->id;
        $unreviewed_id  = Vetted::unknown()->id;
        $batch = 500000; 
        $start_limit = 0;
        while(true)
        {
            print "\n top images count [1 of 13] $start_limit \n";
            $sql = "SELECT DISTINCT tc.id tc_id, do.description, dohe.vetted_id, do.id 
            FROM taxon_concepts tc 
            JOIN top_concept_images tci ON tc.id = tci.taxon_concept_id 
            JOIN data_objects do ON tci.data_object_id = do.id 
            JOIN data_objects_hierarchy_entries dohe on do.id = dohe.data_object_id
            WHERE tc.published=1 AND tc.supercedure_id=0 AND do.published=1 and dohe.visibility_id=".Visibility::visible()->id;
            if(isset($GLOBALS['test_taxon_concept_ids'])) $sql .= " and tc.id IN (". implode(",", $GLOBALS['test_taxon_concept_ids']) .")";
            $sql .= " limit $start_limit, $batch ";
            
            $outfile = $this->mysqli_slave->select_into_outfile($sql);
            $start_limit += $batch;
            $FILE = fopen($outfile, "r");
            $num_rows=0;
            while(!feof($FILE))
            {
                if($line = fgets($FILE))
                {
                    $num_rows++;
                    $line = trim($line);
                    $fields = explode("\t", $line);
                    $tc_id          = trim($fields[0]);
                    $description    = trim($fields[1]);
                    $vetted_id      = trim($fields[2]);
                    $label = "image";
                    $words_count = str_word_count(strip_tags($description),0);
                    @$concept_data_object_counts[$tc_id][$label]['total']++;
                    @$concept_data_object_counts[$tc_id][$label]['total_w']+= $words_count;
                    if    ($vetted_id == $trusted_id)
                    {
                        @$concept_data_object_counts[$tc_id][$label]['t']++;
                        @$concept_data_object_counts[$tc_id][$label]['t_w']+= $words_count;
                    }
                    elseif($vetted_id == $untrusted_id)
                    {
                        @$concept_data_object_counts[$tc_id][$label]['ut']++;
                        @$concept_data_object_counts[$tc_id][$label]['ut_w']+= $words_count;
                    }
                    elseif($vetted_id == $unreviewed_id)
                    {
                        @$concept_data_object_counts[$tc_id][$label]['ur']++;
                        @$concept_data_object_counts[$tc_id][$label]['ur_w']+= $words_count;
                    }
                }
            }
            fclose($FILE);
            unlink($outfile);
            print "\n num_rows: $num_rows";
            if($num_rows < $batch) break;
        }
        //convert associative array to a regular array
        $data_type="image";
        foreach($concept_data_object_counts as $taxon_concept_id => $taxon_object_counts)
        {
            $new_value = "";
            $new_value .= "\t".@$taxon_object_counts[$data_type]['total'];
            $new_value .= "\t".@$taxon_object_counts[$data_type]['t'];
            $new_value .= "\t".@$taxon_object_counts[$data_type]['ut'];
            $new_value .= "\t".@$taxon_object_counts[$data_type]['ur'];
            $new_value .= "\t".@$taxon_object_counts[$data_type]['total_w'];
            $new_value .= "\t".@$taxon_object_counts[$data_type]['t_w'];
            $new_value .= "\t".@$taxon_object_counts[$data_type]['ut_w'];
            $new_value .= "\t".@$taxon_object_counts[$data_type]['ur_w'];
            $concept_data_object_counts[$taxon_concept_id] = $new_value;
        }
        print "\n get_images_count():" . (time_elapsed()-$time_start)/60 . " minutes";
        self::save_totals_to_cumulative_txt($concept_data_object_counts, "tpm_data_objects_images");
        unset($concept_data_object_counts);
    }

    function get_concept_references()
    {
        $concept_references = self::get_array_from_json_file("concept_references");
        $time_start = time_elapsed();
        //$concept_references = array();
        $batch = 500000; 
        $start_limit = 0;
        while(true)
        {
            print "\n taxon ref count [3 of 13] $start_limit \n";
            $sql = "SELECT tc.id tc_id, her.ref_id FROM taxon_concepts tc JOIN hierarchy_entries he ON tc.id = he.taxon_concept_id JOIN hierarchy_entries_refs her ON he.id = her.hierarchy_entry_id WHERE tc.published = 1 AND tc.supercedure_id=0";
            if(isset($GLOBALS['test_taxon_concept_ids'])) $sql .= " and tc.id IN (". implode(",", $GLOBALS['test_taxon_concept_ids']) .")";
            $sql .= " limit $start_limit, $batch ";
            $outfile = $this->mysqli_slave->select_into_outfile($sql);
            $start_limit += $batch;
            $FILE = fopen($outfile, "r");
            $num_rows=0;
            while(!feof($FILE))
            {
                if($line = fgets($FILE))
                {
                    $num_rows++;
                    $line = trim($line);
                    $fields = explode("\t", $line);
                    $tc_id      = trim($fields[0]);
                    $ref_id     = trim($fields[1]);
                    $concept_references[$tc_id][$ref_id]='';
                }
            }
            fclose($FILE); unlink($outfile); print "\n num_rows: $num_rows";
            if($num_rows < $batch) break;
        }
        //==================
        $concept = array();
        $concept_info_items = self::get_array_from_json_file("concept_info_items");
        foreach($concept_info_items as $id => $rec) @$concept[$id]['ii'] = sizeof($rec);
        unset($concept_info_items);
        foreach($concept_references as $id => $rec) @$concept[$id]['ref'] = sizeof($rec);
        unset($concept_references);
        foreach($concept as $taxon_concept_id => $taxon_object_counts)
        {
            $new_value = "";
            $new_value .= "\t" . @$taxon_object_counts["ref"];
            $new_value .= "\t" . @$taxon_object_counts["ii"];
            $concept[$taxon_concept_id] = $new_value;
        }
        print "\n get_concept_references():" . (time_elapsed()-$time_start)/60 . " minutes";
        self::save_totals_to_cumulative_txt($concept, "tpm_references_infoitems");
        unset($concept);
    }

    function get_data_objects_count()
    {
        $time_start = time_elapsed(); 
        $concept_data_object_counts = array();
        $gbif_image_id  = DataType::gbif_image()->id;
        $image_id       = DataType::image()->id;
        $text_id        = DataType::text()->id;
        $video_id       = DataType::video()->id;
        $sound_id       = DataType::sound()->id;
        $flash_id       = DataType::flash()->id;
        $youtube_id     = DataType::youtube()->id;
        $iucn_id        = DataType::iucn()->id;
        $data_type_label[$image_id]      ='image';
        $data_type_label[$sound_id]      ='sound';
        $data_type_label[$text_id]       ='text';
        $data_type_label[$video_id]      ='video';
        $data_type_label[$gbif_image_id] ='gbif';
        $data_type_label[$iucn_id]       ='iucn';
        $data_type_label[$flash_id]      ='flash';
        $data_type_label[$youtube_id]    ='youtube';
        $trusted_id     = Vetted::trusted()->id;
        $untrusted_id   = Vetted::untrusted()->id;
        $unreviewed_id  = Vetted::unknown()->id;
        $batch = 100000; 
        $start_limit = 0;
        $concept_info_items = array();
        $concept_references = array();
        while(true)
        {
            print "\n dataObjects, its infoItems, its references [2 of 13] $start_limit \n";
            $sql = "SELECT dotc.taxon_concept_id tc_id, do.data_type_id, doii.info_item_id, dor.ref_id, do.description, dohe.vetted_id 
                FROM data_objects_taxon_concepts dotc 
                JOIN data_objects do ON dotc.data_object_id = do.id 
                LEFT JOIN data_objects_info_items doii ON do.id = doii.data_object_id 
                LEFT JOIN data_objects_refs dor ON do.id = dor.data_object_id 
                JOIN data_objects_hierarchy_entries dohe on do.id = dohe.data_object_id
                WHERE do.published=1 AND dohe.visibility_id=".Visibility::visible()->id." AND do.data_type_id <> $image_id";
            if(isset($GLOBALS['test_taxon_concept_ids'])) $sql .= " and dotc.taxon_concept_id IN (". implode(",", $GLOBALS['test_taxon_concept_ids']) .")";
            $sql .= " limit $start_limit, $batch ";
            $outfile = $this->mysqli_slave->select_into_outfile($sql);
            $start_limit += $batch;
            $FILE = fopen($outfile, "r");
            $num_rows = 0;
            while(!feof($FILE))
            {
                if($line = fgets($FILE))
                {
                    $num_rows++;
                    $line = trim($line);
                    $fields = explode("\t", $line);
                    $tc_id          = trim($fields[0]);
                    $data_type_id   = trim($fields[1]);
                    $info_item_id   = trim($fields[2]);
                    $ref_id         = trim($fields[3]);
                    $description    = trim($fields[4]);
                    $vetted_id      = trim($fields[5]);
                    $label = @$data_type_label[$data_type_id];
                    $words_count = str_word_count(strip_tags($description),0);
                    @$concept_data_object_counts[$tc_id][$label]['total']++;
                    @$concept_data_object_counts[$tc_id][$label]['total_w']+= $words_count;
                    if($vetted_id == $trusted_id)
                    {
                        @$concept_data_object_counts[$tc_id][$label]['t']++;
                        @$concept_data_object_counts[$tc_id][$label]['t_w']+= $words_count;
                    }
                    elseif($vetted_id == $untrusted_id)
                    {
                        @$concept_data_object_counts[$tc_id][$label]['ut']++;
                        @$concept_data_object_counts[$tc_id][$label]['ut_w']+= $words_count;
                    }
                    elseif($vetted_id == $unreviewed_id)
                    {
                        @$concept_data_object_counts[$tc_id][$label]['ur']++;
                        @$concept_data_object_counts[$tc_id][$label]['ur_w']+= $words_count;
                    }
                    $concept_info_items[$tc_id][$info_item_id]='';
                    $concept_references[$tc_id][$ref_id]='';
                }
            }
            fclose($FILE);
            unlink($outfile);
            print "\n num_rows: $num_rows";
            if($num_rows < $batch) break;
        }
        self::save_to_json_file($concept_info_items,"concept_info_items");
        unset($concept_info_items);
        self::save_to_json_file($concept_references,"concept_references");
        unset($concept_references);
        //convert associative array to a regular array
        $data_type_order_in_file = array("text","video","sound","flash","youtube","iucn");
        foreach($concept_data_object_counts as $taxon_concept_id => $taxon_object_counts)
        {
            $new_value = "";
            foreach($data_type_order_in_file as $data_type)
            {
                $new_value .= "\t".@$taxon_object_counts[$data_type]['total'];
                $new_value .= "\t".@$taxon_object_counts[$data_type]['t'];
                $new_value .= "\t".@$taxon_object_counts[$data_type]['ut'];
                $new_value .= "\t".@$taxon_object_counts[$data_type]['ur'];
                $new_value .= "\t".@$taxon_object_counts[$data_type]['total_w'];
                $new_value .= "\t".@$taxon_object_counts[$data_type]['t_w'];
                $new_value .= "\t".@$taxon_object_counts[$data_type]['ut_w'];
                $new_value .= "\t".@$taxon_object_counts[$data_type]['ur_w'];
            }
            $concept_data_object_counts[$taxon_concept_id] = $new_value;
        }
        print "\n get_data_objects_count():" . (time_elapsed()-$time_start)/60 . " minutes";
        self::save_totals_to_cumulative_txt($concept_data_object_counts, "tpm_data_objects");
        unset($concept_data_object_counts);
    }

    function save_to_json_file($arr,$filename)
    {
        $WRITE = fopen(PAGE_METRICS_TEXT_PATH . $filename . ".txt", "w");
        fwrite($WRITE, json_encode($arr));
        fclose($WRITE);
    }

    function get_array_from_json_file($filename)
    {
        $filename = PAGE_METRICS_TEXT_PATH . $filename . ".txt";
        $READ = fopen($filename, "r");
        $contents = fread($READ, filesize($filename));
        fclose($READ);
        return json_decode($contents,true);
    }

    function save_totals_to_cumulative_txt($arr, $category)
    {
        // expects $arr to equal "\t#" or "\t#\t#\t#"
        $time_start = time_elapsed(); print "\n $category --- start";
        rename(PAGE_METRICS_TEXT_PATH . "taxon_concept_metrics.txt", PAGE_METRICS_TEXT_PATH . "taxon_concept_metrics.txt.tmp");
        $WRITE = fopen(PAGE_METRICS_TEXT_PATH . "taxon_concept_metrics.txt", "a");
        $READ = fopen(PAGE_METRICS_TEXT_PATH . "taxon_concept_metrics.txt.tmp", "r");
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
                           $category == "tpm_common_names"          ||
                           $category == "tpm_synonyms"              ||
                           $category == "tpm_references_infoitems"  ||
                           $category == "tpm_google_stats")         fwrite($WRITE, str_repeat("\t", 2));
                    elseif($category == "tpm_data_objects")         fwrite($WRITE, str_repeat("\t", 48));
                    elseif($category == "tpm_data_objects_images")  fwrite($WRITE, str_repeat("\t", 8));
                    else                                            fwrite($WRITE, str_repeat("\t", 1));
                }
                fwrite($WRITE, "\n");
            }
        }
        fclose($READ);
        fclose($WRITE);
        print "\n writing... [$num_rows]";
        print "\n $category --- end";
        print "\n $category:" . (time_elapsed()-$time_start)/60 . " minutes";
    }

    function save_to_table()
    {
        $time_start = time_elapsed();
        print "\n saving to table...";
        $filename = PAGE_METRICS_TEXT_PATH . "taxon_concept_metrics.txt";
        $this->mysqli->delete("DROP TABLE IF EXISTS taxon_concept_metrics_tmp");
        $this->mysqli->insert("CREATE TABLE IF NOT EXISTS taxon_concept_metrics_tmp LIKE taxon_concept_metrics");
        $this->mysqli->delete("TRUNCATE TABLE taxon_concept_metrics_tmp");
        $this->mysqli->load_data_infile($filename, "taxon_concept_metrics_tmp");
        $result = $this->mysqli->query("SELECT 1 FROM taxon_concept_metrics_tmp LIMIT 1");
        if($result && $row=$result->fetch_assoc()) $this->mysqli->swap_tables('taxon_concept_metrics', 'taxon_concept_metrics_tmp');                                                                                                
        print "\n table saved \n save_to_table():" . (time_elapsed()-$time_start)/60 . " minutes";
    }

    /* work in progress - or may use a different approach altogether when we store taxon_concept_id in PAGE_NAMES table.
    public function get_concepts_with_bhl_publications()
    {
        $start = 0;
        $max_id = 9;
        $iteration_size = 10000;
        
        // $result = $mysqli->query("SELECT min(id) min, max(id) max FROM taxon_concepts WHERE supercedure_id=0 AND published=1");
        // if($result && $row=$result->fetch_assoc())
        // {
        //     $start = $row['min'];
        //     $max_id = $row['max'];
        // }
        
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
            foreach(array(1,2,3) as $val)
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

    public function generate_taxon_concept_with_bhl_links_textfile() //execution time: 6 minutes
    {
        /*  This will generate the [taxon_concept_with_bhl_links.txt]. Lists all concepts with BHL links. */
        $time_start = time_elapsed();
        /* this takes 4 hours
        print "\n start - generate_taxon_concept_with_bhl_links_textfile";
        $outfile = $this->mysqli_slave->select_into_outfile("SELECT DISTINCT tc.id tc_id FROM taxon_concepts tc JOIN taxon_concept_names tcn on (tc.id=tcn.taxon_concept_id) JOIN page_names pn on (tcn.name_id=pn.name_id) WHERE tc.supercedure_id=0 AND tc.published=1");
        $fp = fopen($outfile,"r");
        $file_contents = fread($fp, filesize($outfile));
        fclose($fp);
        $filename = PAGE_METRICS_TEXT_PATH . "taxon_concept_with_bhl_links.txt.tmp";
        $fp = fopen($filename,"w"); print "\n writing..."; fwrite($fp,$file_contents); fclose($fp); print "\n saved.";
        */
        $arr_taxa = array();
        $batch = 500000; 
        $start_limit = 0;
        $tc_ids = array();
        while(true)
        {
            print "\n generate_taxon_concept_with_bhl_links_textfile -- $start_limit \n";
            $sql = "SELECT DISTINCT tc.id tc_id FROM taxon_concepts tc JOIN taxon_concept_names tcn on (tc.id=tcn.taxon_concept_id) JOIN page_names pn on (tcn.name_id=pn.name_id) WHERE tc.supercedure_id=0 AND tc.published=1";
            $sql .= " limit $start_limit, $batch ";
            $outfile = $this->mysqli_slave->select_into_outfile($sql);
            $start_limit += $batch;
            $FILE = fopen($outfile, "r");
            $num_rows = 0;
            while(!feof($FILE))
            {
                if($line = fgets($FILE))
                {
                    $num_rows++; $fields = explode("\t", $line);
                    $tc_id      = trim($fields[0]);
                    $tc_ids[$tc_id] = "";
                }
            }
            fclose($FILE);unlink($outfile);
            print "\n num_rows: $num_rows";
            if($num_rows < $batch) break;
        }
        $str = "";
        foreach($tc_ids as $id => $rec){$str .= $id . "\n";}
        unset($tc_ids);
        $filename = PAGE_METRICS_TEXT_PATH . "taxon_concept_with_bhl_links.txt.tmp";
        $fp = fopen($filename,"w"); print "\n writing..."; fwrite($fp,$str); fclose($fp); print "\n saved.";
        //rename
        unlink(PAGE_METRICS_TEXT_PATH . "taxon_concept_with_bhl_links.txt");
        rename(PAGE_METRICS_TEXT_PATH . "taxon_concept_with_bhl_links.txt.tmp", PAGE_METRICS_TEXT_PATH . "taxon_concept_with_bhl_links.txt");
        print "\n end - generate_taxon_concept_with_bhl_links_textfile";
        $elapsed_time_sec = time_elapsed() - $time_start;
        print "\n elapsed time = " . $elapsed_time_sec/60 . " minutes ";
        print "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours ";
    }

    // Working but will be replaced once we store taxon_concept_id in PAGE_NAMES table.
    public function generate_taxon_concept_with_bhl_publications_textfile() //execution time: 1.80 hrs
    {
        /*  This will generate the [taxon_concept_with_bhl_publications.txt]. Assigns # of BHL publications for every concept. */
        $time_start = time_elapsed();
        $write_filename = PAGE_METRICS_TEXT_PATH . "taxon_concept_with_bhl_publications.txt.tmp";
        unlink($write_filename);
        //start reading text file
        print "\n Start reading text file [taxon_concept_with_bhl_links] \n";
        $filename = PAGE_METRICS_TEXT_PATH . "taxon_concept_with_bhl_links.txt";
        $FILE = fopen($filename, "r"); $i=0; $str=""; $save_count=0;
        while(!feof($FILE))
        {
            if($line = fgets($FILE))
            {
                $line = trim($line); $fields = explode("\t", $line);
                if($tc_id = trim($fields[0]))
                {
                    $sql = "SELECT ip.title_item_id FROM taxon_concept_names tcn JOIN page_names pn ON tcn.name_id = pn.name_id JOIN item_pages ip ON pn.item_page_id = ip.id WHERE tcn.taxon_concept_id = $tc_id ";
                    $result = $this->mysqli_slave->query($sql);
                    $arr = array();
                    while($result && $row=$result->fetch_assoc())
                    {
                        $title_item_id = $row['title_item_id'];
                        $arr[$title_item_id]='';
                    }
                    $publications = sizeof(array_keys($arr));
                    $str .= $tc_id . "\t" . $publications . "\n";
                    $i++; print "\n $i. [$tc_id][$publications] ";
                    //saving
                    $save_count++;
                    if($save_count == 10000)
                    {
                        $fp = fopen($write_filename,"a"); 
                        print "\n writing...";
                        fwrite($fp,$str);
                        fclose($fp);
                        print " saved.";
                        $str = "";
                        $save_count = 0;
                    }
                    //if($i >= 15)break; //debug
                }
            }
        }
        fclose($FILE);
        //last remaining writes
        $fp = fopen($write_filename,"a"); print "\n writing..."; fwrite($fp,$str);  print " saved."; fclose($fp);
        //rename
        unlink(PAGE_METRICS_TEXT_PATH . "taxon_concept_with_bhl_publications.txt");
        rename(PAGE_METRICS_TEXT_PATH . "taxon_concept_with_bhl_publications.txt.tmp", PAGE_METRICS_TEXT_PATH . "taxon_concept_with_bhl_publications.txt");
        print "\n end - generate_taxon_concept_with_bhl_publications_textfile";
        $elapsed_time_sec = time_elapsed() - $time_start;
        print "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours   ";
    }

}

?>
