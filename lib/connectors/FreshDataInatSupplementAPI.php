<?php
namespace php_active_record;
/* connector: freedata_inat_supplement.php */
class FreshDataInatSupplementAPI
{
    function __construct($folder = null)
    {
        $this->folder = $folder;
        $this->destination[$folder] = CONTENT_RESOURCE_LOCAL_PATH . "$folder/observations.txt";

        $this->ctr = 0;
        $this->debug = array();
        $this->print_header = true;

        $this->download_options = array('cache_path' => '/Volumes/Thunderbolt4/eol_cache_bison/', 'expire_seconds' => 5184000, 'download_wait_time' => 2000000, 'timeout' => 600, 
        'download_attempts' => 1); //'delay_in_minutes' => 1
        $this->download_options['expire_seconds'] = false; // false -> doesn't expire | true -> expires now

        $this->increment = 200; //200 is the max allowable per_page
        $this->inat_created_since_api = "http://api.inaturalist.org/v1/observations?quality_grade=needs_id&order_by=date_added&order=asc&per_page=$this->increment"; //2017-08-01
        $this->inat_updated_since_api = "http://api.inaturalist.org/v1/observations?quality_grade=needs_id&order_by=date_added&order=asc&per_page=$this->increment"; //2017-08-30T09:40:00-07:00

        $this->destination_txt_file = "observations.txt";
        
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
        if(!self::start_process()) exit("\nConnector is still running. Program will terminate.\n\n");
        //------------------------------------------------------------------------
        // if(self::is_today_first_day_of_month()) //un-comment in real operation
        if(true) //debug only
        {
            self::start_harvest($func); //this is the reset harvest
            $func->last_part($folder); //this is a folder within CONTENT_RESOURCE_LOCAL_PATH
            if($folder) recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH . $folder);
        }
        else self::start_daily_harvest($func);
        //------------------------------------------------------------------------
        self::end_process();
        $total_rows = Functions::count_rows_from_text_file(CONTENT_RESOURCE_LOCAL_PATH . "$folder/observations.txt");
        echo "\ntotal rows observations: [$total_rows]\n";
        
    }
    private function start_daily_harvest($func)
    {
        // /*
        $this->destination_txt_file = "daily.txt";
        $yesterday = self::date_operation(date('Y-m-d'), "-1 days"); //daily harvest will start from 1 day before OR yesterday
        self::start_harvest($func, $yesterday); //this is daily harvest
        // */
        self::append_daily_to_resource();
        $total_rows = Functions::count_rows_from_text_file(CONTENT_RESOURCE_LOCAL_PATH . "$this->folder/daily.txt");
        echo "\ntotal rows daily: [$total_rows]\n";
    }
    private function start_harvest($func, $date = NULL)
    {
        $uuids = array();
        if(!$date) //this is: reset initial resource
        {
            $date = date('Y-m-d'); //e.g. 2017-09-01 -> normal operation
            $date = "2017-09-01"; //hard-coded for now  -- debug only
            $date = self::date_operation($date, "-1 month"); //date last month
            $date = self::date_operation($date, "-5 days"); //less 5 days more, to have an overlap
        }
        else {} //this is: daily harvest

        // exit("\n[$date]\n");
        $first_loop['created_in'] = true;
        $first_loop['updated_since'] = true;
        
        $download_options = $this->download_options;
        // if($this->destination_txt_file == "daily.txt") $download_options['expire_seconds'] = true; //cache expired
        
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
                        if($api == "created_in")
                        {
                            if($rec['created_at_details']['date'] > $date) {
                                echo "\nWILL STOP: [".$rec['created_at_details']['date']."] > [$date]\n"; // exit;
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
                    if($api == "updated_since" && $ready_to_break >= 6) break;
                    
                    /* //seems best to comment this and be sure to get most of the 10k limit
                    if(!$first_loop[$api] && !self::is_date_first_day_of_month($date)) {
                        if($page == 25) break; //used 10, if 25 that is half of the 50x200 = 10000 limit
                    }
                    */
                }
                else break; //may have reached the 10k limit
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
        $uuids_from_daily = self::get_uuids_from_daily();
        // print_r($uuids_from_daily);
        self::delete_records_from_resource_with_these_uuids($uuids_from_daily);
        self::append_daily_2resource();
        
        unlink(CONTENT_RESOURCE_LOCAL_PATH . "$this->folder/observations.txt");
        rename(CONTENT_RESOURCE_LOCAL_PATH . "$this->folder/observations_temp.txt", CONTENT_RESOURCE_LOCAL_PATH . "$this->folder/observations.txt");
    }
    private function delete_records_from_resource_with_these_uuids($uuids_from_daily)
    {
        $WRITE = Functions::file_open(CONTENT_RESOURCE_LOCAL_PATH . "$this->folder/observations_temp.txt", "w");
        $resource = CONTENT_RESOURCE_LOCAL_PATH . "$this->folder/observations.txt";
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
        $WRITE = Functions::file_open(CONTENT_RESOURCE_LOCAL_PATH . "$this->folder/observations_temp.txt", "a");
        $daily = CONTENT_RESOURCE_LOCAL_PATH . "$this->folder/daily.txt";
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
        $daily = CONTENT_RESOURCE_LOCAL_PATH . "$this->folder/daily.txt";
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