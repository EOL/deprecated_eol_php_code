<?php
namespace php_active_record;
/* connector: freedata_inat_supplement.php 
REMINDER!: a change in column, meaning add a new column or delete or re-arrange the position of any column would mean running the RESET HARVEST
*/
class FreshDataInatSupplementAPI
{
    function __construct($folder = null)
    {
        $this->folder = $folder;
        $this->destination[$folder] = CONTENT_RESOURCE_LOCAL_PATH . "$folder/observations.txt";

        $this->ctr = 0;
        $this->debug = array();
        $this->print_header = true;

        $this->download_options = array('expire_seconds' => 60*60*24*60, 'download_wait_time' => 2000000, 'timeout' => 600, 'download_attempts' => 1); //'delay_in_minutes' => 1
        // $this->download_options['expire_seconds'] = false; //debug only. comment in real operation
        
        if(!Functions::is_production()) $this->download_options['cache_path'] = '/Volumes/Thunderbolt4/eol_cache_bison/';
        else {} //no cache_path in production

        $this->increment = 200; //200 is the max allowable per_page
        $this->inat_created_since_api = "http://api.inaturalist.org/v1/observations?quality_grade=needs_id&order_by=date_added&order=asc&per_page=$this->increment"; //2017-08-01
        /*initial versions
        $this->inat_updated_since_api = "http://api.inaturalist.org/v1/observations?quality_grade=needs_id&order_by=date_added&order=asc&per_page=$this->increment"; //2017-08-30T09:40:00-07:00
        $this->inat_updated_since_api = "http://api.inaturalist.org/v1/observations?quality_grade=needs_id&per_page=$this->increment"; //2017-08-30T09:40:00-07:00
        */
        //from pleary https://github.com/inaturalist/inaturalist/issues/1467#issuecomment-328147818
        $this->inat_updated_since_api = "http://api.inaturalist.org/v1/observations?quality_grade=needs_id&order_by=updated_at&order=asc&per_page=$this->increment"; //2017-08-30T09:40:00-07:00

        $this->destination_txt_file = "observations.txt";
        $this->temporary_file = CONTENT_RESOURCE_LOCAL_PATH . "$this->folder/observations_temp.txt";
        
        /*
        GBIF occurrence extension   : file:///Library/WebServer/Documents/cp/GBIF_dwca/atlantic_cod/meta.xml
        DWC terms                   : http://rs.tdwg.org/dwc/terms/index.htm#Occurrence
        */
    }

    private function initialize()
    {
        require_library('connectors/FreeDataAPI');
        $func = new FreeDataAPI();
        $func->create_folder_if_does_not_exist($this->folder);
        $func->create_folder_if_does_not_exist($this->folder."_final");
        return $func;
    }
    function start()
    {
        /*
        $date = "2017-07-01";
        if(self::is_date_first_day_of_month($date)) echo "\nfirst day";
        else echo "\nnot first day";
        exit("\n");
        */
        
        $folder = $this->folder;
        $func = self::initialize(); //use some functions from FreeDataAPI
        if(!self::start_process()) exit("\nConnector is still running. Program will terminate. Will try again tomorrow " .self::date_operation(date('Y-m-d'), "+1 days"). ".\n\n");
        //------------------------------------------------------------------------
        // if(true) //we may only run this once and never again
        if(false) // will use in daily operation
        {
            self::start_harvest($func); //this is the RESET HARVEST
            /* if($folder) recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH . $folder); -> may remove this line permanently */
        }
        else self::start_daily_harvest($func);
        //------------------------------------------------------------------------
        $total_rows = Functions::count_rows_from_text_file($this->destination[$this->folder]);
        echo "\ntotal rows observations before removing old records: [$total_rows]\n";
        self::remove_old_records_from_source();
        self::end_process();

        self::last_part($folder, $func); //this is a folder within CONTENT_RESOURCE_LOCAL_PATH
        // maybe create a version of last_part here
    }
    
    private function last_part($folder, $func)
    {
        echo "\nZipping folder...";
        $func->generate_meta_xml_v2($folder, "observations.txt"); //creates a meta.xml file
        copy($this->destination[$this->folder]                  , CONTENT_RESOURCE_LOCAL_PATH . $folder."_final/observations.txt");
        copy(CONTENT_RESOURCE_LOCAL_PATH . "$folder/meta.xml"   , CONTENT_RESOURCE_LOCAL_PATH . $folder."_final/meta.xml");
        $command_line = "zip -rj " . CONTENT_RESOURCE_LOCAL_PATH . str_replace("_","-",$folder."_final") . ".zip " . CONTENT_RESOURCE_LOCAL_PATH . $folder."_final" . "/"; //may need 'sudo zip -rj...'
        $output = shell_exec($command_line);
        
        $arr = Functions::count_resource_tab_files($folder, "observations.txt");
        Functions::finalize_connector_run($folder, json_encode($arr));
        
        echo "Done.";
    }
    
    private function remove_old_records_from_source()
    {
        echo "\nRemoving old records...\n";
        $WRITE = Functions::file_open($this->temporary_file, "w");
        $resource = $this->destination[$this->folder];
        // echo "\nwrite to file: [$this->temporary_file]\n";
        // echo "\nresource: [$resource]\n";
        $i = 0;
        foreach(new FileIterator($resource) as $line => $row) {
            $i++;
            if($i == 1) {
                $fields = explode("\t", $row);
                fwrite($WRITE, $row . "\n");
            }
            else {
                $rec = explode("\t", $row);
                $k = -1;
                $rek = array();
                foreach($fields as $field) {
                    $k++;
                    if($val = @$rec[$k]) $rek[$field] = $val;
                }
                if($rek) { //2017-08-28T16:05:21+02:00  -> orig value need to trim using substr()
                           //2017-09-06T09:27:59+02:00
                    $created  = substr($rek['created'],0,10);   //2017-08-28
                    $modified = substr($rek['modified'],0,10);  //2017-09-06
                    $date_2months_old = self::date_operation(date('Y-m-d'), "-2 months"); //2 months old from now
                    if($created >= $date_2months_old || $modified >= $date_2months_old) fwrite($WRITE, $row . "\n");
                }
            }
        }
        fclose($WRITE);
        //rename temp to resource
        unlink($this->destination[$this->folder]);
        rename($this->temporary_file, $this->destination[$this->folder]);
        echo "Done.";
        $total_rows = Functions::count_rows_from_text_file($this->destination[$this->folder]);
        echo "\ntotal rows observations after removing old records: [$total_rows]\n";
    }
    
    private function start_daily_harvest($func)
    {
        // /*
        $this->destination_txt_file = "daily_".date('Y-m-d').".txt";
        $yesterday = self::date_operation(date('Y-m-d'), "-1 days"); //daily harvest will start from 1 day before OR yesterday
        self::start_harvest($func, $yesterday); //this is daily harvest
        // */
        self::append_daily_to_resource();
        $total_rows = Functions::count_rows_from_text_file(CONTENT_RESOURCE_LOCAL_PATH . "$this->folder/$this->destination_txt_file");
        echo "\ntotal rows daily: [$total_rows]\n";
    }
    private function start_harvest($func, $date = NULL)
    {
        $uuids = array();
        if(!$date) //this is: reset initial resource
        {
            $date = date('Y-m-d'); //e.g. 2017-09-01 -> normal operation
            $date = self::date_operation($date, "-2 months"); //date 2 months ago
        }
        else {} //this is: daily harvest

        $first_loop['created_in'] = true;
        $first_loop['updated_since'] = true;
        
        $download_options = $this->download_options;
        if($this->destination_txt_file != "observations.txt") $download_options['expire_seconds'] = 60*60*24;//orig is true; //cache expired deliberately for daily harvest
        /* On Apr 4, 2019 changed expiration to 24 hours */
        
        while($date <= date('Y-m-d')) //loops until date today
        {
            echo "\n$date";
            $apis = array("updated_since", "created_in");
            foreach($apis as $api) {
                //=======================start loop
                $page = 1;
                $ready_to_break = 0;
                while(true)
                {
                    // $url = $this->inat_created_since_api."&page=$page"; //moved inside the format_date_params()
                    $url = self::format_date_params($api, $date, $page);
                    echo "\n$url\n";
                    if($json = Functions::lookup_with_cache($url, $download_options))
                    {
                        $arr = json_decode($json, true);
                        $total = count($arr['results']); echo "\ntotal = [$total] [$page] [$api]\n";
                        // /* //---------------------------start loop
                        $x = array();
                        $should_break = false;
                        foreach($arr['results'] as $rec) {
                        
                            if($api == "created_in") {
                                if($rec['created_at_details']['date'] > $date) {
                                    echo "\nWILL STOP: [".$rec['created_at_details']['date']."] > [$date]\n";
                                    $should_break = true;
                                    break;
                                }
                            }

                            if($api == "updated_since") {
                                $updated_at = substr($rec['updated_at'],0,10); 
                                // exit("\n[$updated_at]\n");
                                if($updated_at > $date) {
                                    echo "\nWILL STOP: [".$updated_at."] > [$date]\n";
                                    $should_break = true;
                                    break;
                                }
                            }
                        
                        
                            if(!in_array($rec['uuid'], $uuids)) { //start process here
                                $uuids[] = $rec['uuid'];
                                @$x['NOT yet processed - NEW']++;
                                self::process_record($rec, $func);
                            }
                            else @$x['already processed - DUPLICATE']++;
                        }
                        print_r($x);
                        if(@$x['already processed - DUPLICATE'] == 200) $ready_to_break++;
                        else                                            $ready_to_break = 0; //reset
                        // */ //---------------------------end loop
                        if($total < $this->increment) break; //it actually doesn't reach this bec. of the 10k limit
                        if($should_break) break;
                        // if($api == "updated_since" && $ready_to_break >= 6) break;   //obsolete since pleary allowed us to order_by=updated_at
                    
                        /* //seems best to comment this and be sure to get most of the 10k limit
                        if(!$first_loop[$api] && !self::is_date_first_day_of_month($date)) {
                            if($page == 25) break; //used 10, if 25 that is half of the 50x200 = 10000 limit
                        }
                        */
                    }
                    else break; //may have reached the 10k limit
                    // return; //debug only
                    $page++;
                }
                $first_loop[$api] = false;
                //=======================end loop
            }//end foreach()


            $date = self::date_operation($date, "+1 days"); //date tomorrow
            // break; //debug only
        }
        // exit("\neli 01\n");
    }
    
    private function append_daily_to_resource()
    {
        echo "\nAppend daily to resource...";
        $uuids_from_daily = self::get_uuids_from_daily();
        self::delete_records_from_resource_with_these_uuids($uuids_from_daily);
        self::append_daily_2resource();
        unlink($this->destination[$this->folder]);
        rename($this->temporary_file, $this->destination[$this->folder]);
        echo "Done.";
    }
    private function delete_records_from_resource_with_these_uuids($uuids_from_daily)
    {
        $WRITE = Functions::file_open($this->temporary_file, "w");
        $resource = $this->destination[$this->folder];
        $i = 0;
        foreach(new FileIterator($resource) as $line => $row) {
            $i++;
            if($i == 1) {
                $fields = explode("\t", $row);
                fwrite($WRITE, $row . "\n");
            }
            else {
                $rec = explode("\t", $row);
                $k = -1;
                $rek = array();
                foreach($fields as $field) {
                    $k++;
                    if($val = @$rec[$k]) $rek[$field] = $val;
                }
                if($rek) {
                    if(!in_array($rek['occurrenceID'], $uuids_from_daily)) fwrite($WRITE, $row . "\n");
                }
            }
        }
        fclose($WRITE);
    }
    private function append_daily_2resource()
    {
        $WRITE = Functions::file_open($this->temporary_file, "a");
        $daily = CONTENT_RESOURCE_LOCAL_PATH . "$this->folder/$this->destination_txt_file";
        $i = 0;
        foreach(new FileIterator($daily) as $line => $row) {
            $i++;
            if($i != 1) fwrite($WRITE, $row . "\n");
        }
        fclose($WRITE);
    }

    private function get_uuids_from_daily()
    {
        $uuids = array();
        $daily = CONTENT_RESOURCE_LOCAL_PATH . "$this->folder/$this->destination_txt_file";
        $i = 0;
        foreach(new FileIterator($daily) as $line => $row) {
            $i++;
            if($i == 1) $fields = explode("\t", $row);
            else {
                $rec = explode("\t", $row);
                $k = -1;
                $rek = array();
                foreach($fields as $field) {
                    $k++;
                    if($val = @$rec[$k]) $rek[$field] = $val;
                }
                if($rek) {
                    if($val = $rek['occurrenceID']) $uuids[$val] = '';
                    if($val = @$rek['uuid']) $uuids[$val] = ''; //consequence of Jen: https://eol-jira.bibalex.org/browse/DATA-1699?focusedCommentId=61781&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-61781
                }
            }
        }
        return array_keys($uuids);
    }
    
    private function parse_ancestors($recs)
    {
        $ancestors = array();
        foreach($recs as $rec) $ancestors[$rec['rank']] = $rec['name'];
        return $ancestors;
    }
    
    private function with_lat_long($rek)
    {
        if(!@$rek['geojson']['coordinates'][0]) return false;
        if(!@$rek['geojson']['coordinates'][1]) return false;
        return true;
    }
    private function start_process()
    {
        $filename = CONTENT_RESOURCE_LOCAL_PATH . "$this->folder/currently_running.txt";
        if(file_exists($filename)) return false;
        $WRITE = Functions::file_open($filename, "w");
        fwrite($WRITE, "");
        fclose($WRITE);
        return true;
    }
    private function end_process()
    {
        $filename = CONTENT_RESOURCE_LOCAL_PATH . "$this->folder/currently_running.txt";
        if(file_exists($filename)) unlink($filename);
    }
    private function save_to_text_file($row)
    {
        if($row) {
            $WRITE = Functions::file_open(CONTENT_RESOURCE_LOCAL_PATH . "$this->folder/".$this->destination_txt_file, "a");
            fwrite($WRITE, $row . "\n");
            fclose($WRITE);
        }
    }

    private function is_today_first_day_of_month()
    {
        if("01" == date('d')) return true;
        else return false;
    }
    private function is_date_first_day_of_month($date) //$date e.g. "2017-08-01"
    {
        $date1 = str_replace('-', '/', $date);
        if("01" == date('d',strtotime($date1))) return true;
        else return false;
    }
    
    private function format_date_params($what, $date, $page)
    {
        if($what == "created_in") {
            $url = $this->inat_created_since_api."&page=$page";
            $str = "&created_d1=".$date;
        }
        else {
            $url = $this->inat_updated_since_api."&page=$page";
            $str = "&updated_since=".$date."T00:00:00-00:00";
        }
        $url .= $str;
        return $url;
    }
    private function date_operation($date, $operation)
    {
        $date1 = str_replace('-', '/', $date);
        $tomorrow = date('Y-m-d',strtotime($date1 . $operation));
        return $tomorrow;
    }
    private function process_record($rek, $func)
    {
        if($rek['geojson']['type'] != "Point") return;
        if(!$rek['taxon']['name']) return;
        if(!self::with_lat_long($rek)) return;
        
        // print_r($rek); exit;
        $rec = array();
        $this->ctr++;
        $rec['id'] = $this->ctr;
        $rec['occurrenceID']    = $rek['uuid'];
        $rec['taxonID']         = $rek['taxon']['id'];
        $rec['scientificName']  = $rek['taxon']['name'];
        $rec['taxonRank']       = $rek['taxon']['rank'];
        $rec['source']          = $rek['uri'];
        $rec['decimalLatitude'] = $rek['geojson']['coordinates'][1];
        $rec['decimalLongitude'] = $rek['geojson']['coordinates'][0];
        $rec['eventDate']       = $rek['time_observed_at'];
        $rec['recordedBy']      = @$rek['user']['name'];
        $rec['locality']        = $rek['place_guess'];
        $rec['modified']        = $rek['updated_at'];
        $rec['created']         = $rek['created_at'];
        
        // per: Jen https://eol-jira.bibalex.org/browse/DATA-1699?focusedCommentId=61781&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-61781
        $rec['occurrenceID']    = $rek['uri'];
        $rec['source']          = '';
        $rec['uuid']            = $rek['uuid'];
        //end new instruction - per: Jen
        
        $ancestry = array();
        if($arr = @$rek['identifications'][0]['taxon']['ancestors']) $ancestry = self::parse_ancestors($arr);
        // print_r($ancestry); //exit;
        
        $rec['kingdom'] = @$ancestry['kingdom'];
        $rec['phylum']  = @$ancestry['phylum'];
        $rec['class']   = @$ancestry['class'];
        $rec['order']   = @$ancestry['order'];
        $rec['family']  = @$ancestry['family'];
        $rec['genus']   = @$ancestry['genus'];
        
        // print_r($rec); //exit;
        /*
        [geojson] => Array(
                    [coordinates] => Array(
                            [0] => -111.73022474
                            [1] => 41.51918058
                        )
                    [type] => Point
                )*/

        $rec = array_map('trim', $rec);
        $func->print_header($rec, CONTENT_RESOURCE_LOCAL_PATH . "$this->folder/".$this->destination_txt_file);
        $val = implode("\t", $rec);
        self::save_to_text_file($val);
    }

}
?>