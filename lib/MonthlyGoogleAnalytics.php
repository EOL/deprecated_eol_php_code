<?php
namespace php_active_record;
require_once DOC_ROOT . 'vendor/google_api_php_client/src/Google_Client.php';
require_once DOC_ROOT . 'vendor/google_api_php_client/src/contrib/Google_AnalyticsService.php';
define("PAGE_METRICS_TEXT_PATH", DOC_ROOT . "applications/taxon_page_metrics/text_files/");

class MonthlyGoogleAnalytics
{
    private $mysqli;
    private $bhl_user_id;
    private $col_user_id;
    private $profile_name;
    private $year;
    private $month;
    private $year_month;

    public function __construct($year, $month)
    {
        $this->mysqli =& $GLOBALS['mysqli_connection'];
        $this->year = $year;
        $this->month = $month;
        $this->year_month = $year ."_". $month;
        $this->bhl_user_id = ContentPartner::find_or_create_by_full_name('Biodiversity Heritage Library')->user_id;
        $this->col_user_id = ContentPartner::find_or_create_by_full_name('Catalogue of Life')->user_id;

        // we switched Google Analytics accounts on September 2011 when we launched V2
        if($this->year < 2011 || ($this->year == 2011 && $this->month < 9)) $this->profile_name = "EOLv1";
        else $this->profile_name = "EOLv2";
    }

    function save_eol_taxa_google_stats()
    {
        $start_date = $this->year ."-". $this->month ."-01";
        $end_date   = $this->year ."-". $this->month ."-". Functions::last_day_of_month($this->month, $this->year);
        print "\n start day = $start_date \n end day = $end_date \n";

        list($service, $profile_id) = $this->GoogleAPI_get_service();
        if(!$service || !$profile_id) return false;

        $initial_results = $this->GoogleAPI_get_results($service, $profile_id, 1, 1, $start_date, $end_date,
            'ga:pagePath', 'ga:pageviews,ga:uniquePageviews,ga:timeOnPage,ga:exits');
        $total_results = $initial_results->totalResults;
        echo "Total: $total_results\n";

        $results_per_batch = 10000;
        $number_of_batches = ceil($total_results / $results_per_batch);

        echo "$number_of_batches\n";

        for($i=0 ; $i<$number_of_batches ; $i++)
        {
            echo "Querying batch ".($i+1)." of $number_of_batches...\n";
            $batch_results = $this->GoogleAPI_get_results($service, $profile_id, (($i * $results_per_batch) + 1), $results_per_batch, $start_date, $end_date, 
                'ga:pagePath', 'ga:pageviews,ga:uniquePageviews,ga:timeOnPage,ga:exits');
            $rows_to_write = array();
            foreach($batch_results->rows as $row)
            {
                $path = $row[0];
                $page_views = $row[1];
                $unique_page_views = $row[2];
                $time_on_page = $row[3];
                $exits = $row[4];

                $taxon_id = parse_url("http://eol.org" . $path, PHP_URL_PATH);
                if(strval(stripos($taxon_id, "/pages/")) != '') $taxon_id = intval(str_ireplace("/pages/", "", $taxon_id));
                else $taxon_id = 0;
                if($taxon_id)
                {
                    // if(isset($rows_to_write[$taxon_id]))
                    // {
                    //     $rows_to_write[$taxon_id]['page_views'] += $page_views;
                    //     $rows_to_write[$taxon_id]['unique_page_views'] += $unique_page_views;
                    //     $rows_to_write[$taxon_id]['time_on_page'] += $time_on_page;
                    //     $rows_to_write[$taxon_id]['exits'] += $exits;
                    // }else
                    // {
                        $rows_to_write[] = array(
                            'taxon_id' => $taxon_id,
                            'page_views' => $page_views,
                            'unique_page_views' => $unique_page_views,
                            'time_on_page' => $time_on_page,
                            'exits' => $exits);
                    // }
                }
            }

            $outfile = temp_filepath();
            if(!($OUT = fopen($outfile, "w+")))
            {
              debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$outfile);
              return;
            }
            foreach($rows_to_write as $taxon_id => &$values)
            {
                if($values['page_views'] - $values['exits'])
                {
                    $values['average_time_on_page'] = Functions::sec2hms(round($values['time_on_page'] / ($values['page_views'] - $values['exits'])), true);
                }else $values['average_time_on_page'] = '00:00:00';
                fwrite($OUT, $values['taxon_id']."\t$this->year\t$this->month\t".$values['page_views']."\t".$values['unique_page_views']."\t".$values['average_time_on_page']."\n");
            }
            fclose($OUT);
            $this->mysqli->load_data_infile($outfile, 'google_analytics_page_stats');
            unlink($outfile);
        }
        return true;
    }

    function save_agent_taxa()
    {
        $user_ids = array();
        $query = self::partners_with_published_data_query();
        $result = $this->mysqli->query($query);
        while($result && $row = $result->fetch_assoc())
        {
            $user_ids[] = $row['id'];
        }
        $user_ids[] = $this->bhl_user_id;
        $user_ids[] = $this->col_user_id;
        
        $count_users = count($user_ids);
        $on_user = 0;
        foreach($user_ids as $user_id)
        {
            $on_user++;
            echo "Getting TaxaIDs for $user_id ($on_user of $count_users)...\n";
            $taxon_concept_ids = self::get_taxon_concept_id_viewed_in_month($user_id, $this->month, $this->year);
            $this->write_partner_taxa($taxon_concept_ids, $user_id);
        }
    }

    function save_agent_monthly_summary()
    {
        $user_ids = array();
        $query = self::partners_with_published_data_query();
        $result = $this->mysqli->query($query);
        while($result && $row = $result->fetch_assoc())
        {
            $user_ids[] = $row['id'];
        }
        $user_ids[] = $this->bhl_user_id;
        $user_ids[] = $this->col_user_id;
        
        $count_users = count($user_ids);
        $on_user = 0;
        foreach($user_ids as $user_id)
        {
            $on_user++;
            echo "Getting Summaries for $user_id ($on_user of $count_users)...\n";
            $totals = self::get_count_of_taxa_pages_per_partner($user_id);
            $count_of_taxa_pages = $totals[0];
            $count_of_taxa_pages_viewed = $totals[1];
            $report = self::get_monthly_summaries_per_partner($user_id, $count_of_taxa_pages, $count_of_taxa_pages_viewed);
            self::write_partner_summaries($report);
        }
    }

    function GoogleAPI_get_service()
    {
        $client_id = GOOGLE_API_CLIENT_ID;
        $service_account_name = GOOGLE_API_ACCOUNT_NAME;
        $key_file = GOOGLE_API_KEY_FILE;

        $service = null;
        $profile_id = null;
        try
        {
            $client = new \Google_Client();
            $client->setApplicationName("Test API App");

            $key = file_get_contents($key_file);
            $client->setAssertionCredentials(new \Google_AssertionCredentials(
                $service_account_name,
                array('https://www.googleapis.com/auth/analytics.readonly'),
                $key)
            );

            $client->setClientId($client_id);
            $client->setUseObjects(true);
            $service = new \Google_AnalyticsService($client);
            $profile_id = $this->GoogleAPI_get_first_profile_id($service);
        }catch(Exception $e) {}
        if($service && $profile_id)
        {
            return array($service, $profile_id);
        }else
        {
            echo "There was a problem connecting to the Google API\n";
            return array(null, null);
        }
    }

    function GoogleAPI_get_first_profile_id(&$service)
    {
        $accounts = $service->management_accounts->listManagementAccounts();
        if(count($accounts->getItems()) > 0)
        {
            $items = $accounts->getItems();
            $firstAccountId = $items[0]->getId();
            $webproperties = $service->management_webproperties->listManagementWebproperties($firstAccountId);
            if(count($webproperties->getItems()) > 0)
            {
                $items = $webproperties->getItems();
                $firstWebpropertyId = $items[0]->getId();
                $profiles = $service->management_profiles->listManagementProfiles($firstAccountId, $firstWebpropertyId);
                if(count($profiles->getItems()) > 0)
                {
                    $items = $profiles->getItems();
                    return $items[0]->getId();
                }else throw new Exception('No profiles found for this user');
            }else throw new Exception('No webproperties found for this user');
        }else throw new Exception('No accounts found for this user');
    }

    function GoogleAPI_get_results(&$service, $profile_id, $start_index, $max_results, $start_date, $end_date, $dimensions, $segments)
    {
        $params = array(
            'dimensions' => $dimensions,
            'max-results' => $max_results,
            'start-index' => $start_index);
        return $service->data_ga->get(
           'ga:' . $profile_id,
           $start_date,
           $end_date,
           $segments,
           $params);
    }

    function write_partner_taxa($taxon_concept_ids, $user_id)
    {
        $outfile = temp_filepath();
        if(!($OUT = fopen($outfile, "w+")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$outfile);
          return;
        }
        foreach($taxon_concept_ids as $taxon_concept_id)
        {
            fwrite($OUT, "$taxon_concept_id\t$user_id\t$this->year\t$this->month\n");
        }
        fclose($OUT);
        $this->mysqli->load_data_infile($outfile, 'google_analytics_partner_taxa');
        unlink($outfile);
    }

    function write_partner_summaries($stats)
    {
        $outfile = temp_filepath();
        if(!($OUT = fopen($outfile, "w+")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$outfile);
          return;
        }
        fwrite($OUT, implode("\t", $stats) . "\n");
        fclose($OUT);
        $this->mysqli->load_data_infile($outfile, 'google_analytics_partner_summaries');
        unlink($outfile);
    }

    function save_eol_monthly_summary()
    {
        $start_date = $this->year ."-". $this->month ."-01";
        $end_date   = $this->year ."-". $this->month ."-". Functions::last_day_of_month($this->month, $this->year);
        list($service, $profile_id) = $this->GoogleAPI_get_service();
        if(!$service || !$profile_id) return false;

        $results = $this->GoogleAPI_get_results($service, $profile_id, 1, 1, $start_date, $end_date,
            '', 'ga:uniquePageviews,ga:bounces,ga:entrances,ga:exits,ga:newVisits,ga:pageviews,ga:timeOnPage,ga:timeOnSite,ga:visitors,ga:visits');
        $stats = $results->rows[0];
        $unique_page_views = $stats[0];
        $bounces = $stats[1];
        $entrances = $stats[2];
        $exits = $stats[3];
        $new_visits = $stats[4];
        $page_views = $stats[5];
        $time_on_page = $stats[6];
        $time_on_site = $stats[7];
        $visitors = $stats[8];
        $visits = $stats[9];
        $average_pages_per_visit = round($page_views/$visits, 2);
        $average_time_on_site = Functions::sec2hms(round($time_on_site / $visits), true);
        $average_time_on_page = Functions::sec2hms(round($time_on_page / ($page_views - $exits)), true);
        $percent_new_visits = round(($new_visits / $visits) * 100, 2);
        $bounce_rate = round(($bounces / $entrances) * 100, 2);
        $percent_exit = round(($exits / $page_views) * 100, 2);

        $query = "SELECT COUNT(*) count FROM taxon_concepts tc WHERE tc.published=1 AND tc.supercedure_id=0";
        $result = $this->mysqli->query($query);
        $row = $result->fetch_row();
        $taxa_pages = $row[0];

        $query = "SELECT google_analytics_page_stats.taxon_concept_id FROM google_analytics_page_stats WHERE year = $this->year AND month = $this->month ";
        $result = $this->mysqli->query($query);
        $taxon_concept_ids = array();
        while($result && $row = $result->fetch_assoc())
        {
            $taxon_concept_ids[$row['taxon_concept_id']] = 1;
        }
        $taxa_pages_viewed = count($taxon_concept_ids);

        $query = "SELECT sum(time_to_sec(google_analytics_page_stats.time_on_page)) time_on_pages FROM google_analytics_page_stats WHERE google_analytics_page_stats.`year` = $this->year AND google_analytics_page_stats.`month` = $this->month ";
        $result = $this->mysqli->query($query);
        $row = $result->fetch_row();
        $time_on_pages = $row[0];

        $query = "INSERT INTO google_analytics_summaries VALUES ($this->year, $this->month, $visits, $visitors, $page_views,
            $unique_page_views, $average_pages_per_visit, '$average_time_on_site', '$average_time_on_page', $percent_new_visits,
            $bounce_rate, $percent_exit, $taxa_pages, $taxa_pages_viewed, $time_on_pages)";
        $this->mysqli->insert($query);
    }

    function get_taxon_concept_id_viewed_in_month($user_id)
    {
        $concepts_ids_viewed_in_month = array();
        if($user_id == $this->bhl_user_id)
        {
            $query = "SELECT taxon_concept_id FROM google_analytics_page_stats WHERE year = $this->year AND month = $this->month";
            $result = $this->mysqli->query($query);
            $concepts_viewed_in_month = array();
            while($result && $row = $result->fetch_assoc())
            {
                $concepts_viewed_in_month[$row['taxon_concept_id']] = 1;
            }

            $bhl_file_path = PAGE_METRICS_TEXT_PATH . "taxon_concept_with_bhl_links.txt";
            $bhl_concept_ids = array();
            foreach(new FileIterator($bhl_file_path) as $line_number => $line)
            {
                $fields = explode("\t", $line);
                $taxon_concept_id = trim($fields[0]);
                $bhl_concept_ids[$taxon_concept_id] = 1;
            }
            $concepts_viewed_in_month = array_keys($concepts_viewed_in_month);
            $bhl_concept_ids = array_keys($bhl_concept_ids);
            $concepts_ids_viewed_in_month = array_intersect($concepts_viewed_in_month, $bhl_concept_ids);
        }elseif($user_id == $this->col_user_id)
        {
            $query = "SELECT he.taxon_concept_id FROM hierarchy_entries he STRAIGHT_JOIN google_analytics_page_stats gaps ON (he.taxon_concept_id = gaps.taxon_concept_id) WHERE he.hierarchy_id  = " . Hierarchy::default_id() . " AND gaps.month = $this->month AND gaps.year = $this->year";
            $result = $this->mysqli->query($query);
            $concepts_viewed_in_month = array();
            while($result && $row = $result->fetch_assoc())
            {
                $taxon_concept_id = $row['taxon_concept_id'];
                $concepts_viewed_in_month[$taxon_concept_id] = 1;
            }
            $concepts_ids_viewed_in_month = array_keys($concepts_viewed_in_month);
        }else
        {
            $concepts_viewed_in_month = array();
            $query = "SELECT r.* FROM resources r JOIN content_partners cp ON r.content_partner_id = cp.id WHERE cp.user_id = $user_id";
            $resources = $this->mysqli->query($query);
            while($resources && $resource_row = $resources->fetch_assoc())
            {
                $query = "SELECT MAX(he.id) latest_harvest_event_id FROM harvest_events he
                    WHERE he.resource_id = ".$resource_row['id']." AND he.published_at IS NOT NULL AND he.published_at < '".$this->year."-".($this->month+1)."-01'";
                $result = $this->mysqli->query($query);
                $row = $result->fetch_row();
                $latest_harvest_event_id = $row[0];
                if(!$latest_harvest_event_id) continue;
                $query = "SELECT tc.id taxon_concept_id
                        FROM harvest_events_hierarchy_entries hehe
                        STRAIGHT_JOIN hierarchy_entries he ON hehe.hierarchy_entry_id = he.id
                        STRAIGHT_JOIN taxon_concepts tc ON he.taxon_concept_id = tc.id
                        STRAIGHT_JOIN google_analytics_page_stats gaps ON tc.id = gaps.taxon_concept_id
                        WHERE tc.published=1 AND tc.supercedure_id=0 AND hehe.harvest_event_id=$latest_harvest_event_id AND gaps.month=$this->month AND gaps.year=$this->year";
                $result = $this->mysqli->query($query);
                while($result && $row = $result->fetch_assoc())
                {
                    $taxon_concept_id = $row['taxon_concept_id'];
                    $concepts_viewed_in_month[$taxon_concept_id] = 1;
                }
            }
            $concepts_ids_viewed_in_month = array_keys($concepts_viewed_in_month);
        }
        return $concepts_ids_viewed_in_month;
    }

    function get_monthly_summaries_per_partner($user_id, $count_of_taxa_pages, $count_of_taxa_pages_viewed)
    {
        //start get count_of_taxa_pages viewed during the month, etc.
        $query = "SELECT
        SUM(gaps.page_views) AS page_views,
        SUM(gaps.unique_page_views) AS unique_page_views,
        SUM(time_to_sec(gaps.time_on_page)) AS time_on_page
        FROM google_analytics_partner_taxa gapt
        JOIN google_analytics_page_stats gaps ON gapt.taxon_concept_id = gaps.taxon_concept_id AND gapt.`year` = gaps.`year` AND gapt.`month` = gaps.`month`
        WHERE gapt.user_id = $user_id AND gapt.`year` = $this->year AND gapt.`month` = $this->month ";
        $result = $this->mysqli->query($query);
        $row = $result->fetch_row();
        $page_views = $row[0];
        $unique_page_views = $row[1];
        $time_on_page = $row[2];
        $fields = array();
        $fields[] = $this->year;
        $fields[] = $this->month;
        $fields[] = $user_id;
        $fields[] = intval($count_of_taxa_pages);
        $fields[] = intval($count_of_taxa_pages_viewed);
        $fields[] = intval($unique_page_views);
        $fields[] = intval($page_views);
        $fields[] = floatval($time_on_page);  //this has to be floatval()
        return $fields;
    }

    function get_count_of_taxa_pages_per_partner($user_id)
    {
        $totals = array();
        if($user_id == $this->bhl_user_id)
        {
            $bhl_file_path = PAGE_METRICS_TEXT_PATH . "taxon_concept_with_bhl_links.txt";
            $bhl_concept_ids = array();
            foreach(new FileIterator($bhl_file_path) as $line_number => $line)
            {
                $fields = explode("\t", $line);
                $taxon_concept_id = trim($fields[0]);
                $bhl_concept_ids[$taxon_concept_id] = 1;
            }
            $totals[] = count($bhl_concept_ids);
        }elseif($user_id == $this->col_user_id)
        {
            $result = $this->mysqli->query("SELECT COUNT(taxon_concept_id) count FROM hierarchy_entries WHERE hierarchy_id = " . Hierarchy::default_id());
            $row = $result->fetch_row();
            $totals[] = $row[0];
        }else
        {
            $concept_ids = array();
            $query = "SELECT r.* FROM resources r JOIN content_partners cp ON r.content_partner_id = cp.id WHERE cp.user_id = $user_id";
            $resources = $this->mysqli->query($query);
            while($resources && $resource_row = $resources->fetch_assoc())
            {
                $query = "SELECT MAX(he.id) latest_harvest_event_id FROM harvest_events he
                    WHERE he.resource_id = ".$resource_row['id']." AND he.published_at IS NOT NULL AND he.published_at < '".$this->year."-".($this->month+1)."-01'";
                $result = $this->mysqli->query($query);
                $row = $result->fetch_row();
                $latest_harvest_event_id = $row[0];
                if(!$latest_harvest_event_id) continue;
                $query = "SELECT tc.id taxon_concept_id
                        FROM harvest_events_hierarchy_entries hehe
                        STRAIGHT_JOIN hierarchy_entries he ON hehe.hierarchy_entry_id = he.id
                        STRAIGHT_JOIN taxon_concepts tc ON he.taxon_concept_id = tc.id
                        WHERE tc.published=1 AND tc.supercedure_id=0 AND hehe.harvest_event_id=$latest_harvest_event_id";
                $result = $this->mysqli->query($query);
                while($result && $row = $result->fetch_assoc())
                {
                    $taxon_concept_id = $row['taxon_concept_id'];
                    $concept_ids[$taxon_concept_id] = '';
                }
            }
            $totals[] = count($concept_ids);
        }
        $query = "SELECT COUNT(gapt.taxon_concept_id) FROM google_analytics_partner_taxa gapt WHERE gapt.user_id = $user_id AND gapt.`year` = $this->year AND gapt.`month` = $this->month";
        $result = $this->mysqli->query($query);
        $row = $result->fetch_row();
        $totals[] = $row[0];
        // $totals[0] => total taxa for user_id
        // $totals[1] => taxa viewed for user_id this month
        return $totals;
    }

    function partners_with_published_data_query()
    {
        //this query now only gets partners with a published data on the time the report was run.
        $query = "SELECT DISTINCT u.id FROM users u
            JOIN content_partners cp ON u.id = cp.user_id JOIN resources r ON cp.id = r.content_partner_id JOIN harvest_events he ON r.id = he.resource_id
            WHERE he.published_at IS NOT NULL AND u.id NOT IN($this->bhl_user_id, $this->col_user_id)";
        $query .= " ORDER BY cp.full_name ";
        return $query;
    }

    function initialize_tables()
    {
        $tables = array('google_analytics_page_stats', 'google_analytics_partner_taxa', 'google_analytics_partner_summaries', 'google_analytics_summaries');
        foreach($tables as $table)
        {
            $this->mysqli->delete("DELETE FROM $table WHERE year=$this->year AND month=$this->month");
        }
    }

    function send_email_notification($year, $month)
    {
        print "\nTesting if stats for the month were successfully saved on 4 tables: ";
        $result = $this->mysqli->query("SELECT g.month FROM google_analytics_page_stats g WHERE g.`year` = $year AND g.`month` = $month LIMIT 1");
        if($result->num_rows == 0) return;
        $result = $this->mysqli->query("SELECT g.month FROM google_analytics_partner_summaries g WHERE g.`year` = $year AND g.`month` = $month LIMIT 1");
        if($result->num_rows == 0) return;
        $result = $this->mysqli->query("SELECT g.month FROM google_analytics_partner_taxa g WHERE g.`year` = $year AND g.`month` = $month LIMIT 1");
        if($result->num_rows == 0) return;
        $result = $this->mysqli->query("SELECT g.month FROM google_analytics_summaries g WHERE g.`year` = $year AND g.`month` = $month LIMIT 1");
        if($result->num_rows == 0) return;
        print "OK \n\n";
        print "\n\nWaiting (10 minutes)... for slaves to catch-up before sending the notification emails \n\n";
        sleep(600);
        file_get_contents("http://eol-app-maint1.rc.fas.harvard.edu/content_cron_tasks/send_monthly_partner_stats_notification");
    }
}
?>
