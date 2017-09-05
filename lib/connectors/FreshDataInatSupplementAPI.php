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

        $this->increment = 200; //3;//10000; orig is 200 and the max allowable per_page
        $this->inat_created_since_api = "http://api.inaturalist.org/v1/observations?quality_grade=needs_id&per_page=$this->increment"; //2017-08-01
        $this->inat_updated_since_api = "http://api.inaturalist.org/v1/observations?quality_grade=needs_id&per_page=$this->increment"; //2017-08-30T09:40:00-07:00

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
        $folder = $this->folder;
        $func = self::initialize(); //use some functions from FreeDataAPI
        //------------------------------------------------------------------------
        if(self::is_first_day_of_month()) self::reset_initial_resource();
        if(true) self::reset_initial_resource($func);    //debug only
        
        // exit("\njust starting...\n");
        //------------------------------------------------------------------------
        // self::main_loop($func);
        $func->last_part($folder); //this is a folder within CONTENT_RESOURCE_LOCAL_PATH
        if($folder) recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH . $folder);
    }

    private function reset_initial_resource($func)
    {
        $uuids = array();
        $date = date('Y-m-d'); //e.g. 2017-08-01
        $date = "2017-08-01"; //hard-coded for now  -- debug only
        while($date <= date('Y-m-d'))
        {
            echo "\n$date";
            //=======================start loop
            $page = 1;
            while(true)
            {
                $url = $this->inat_created_since_api."&page=$page";
                $url = self::format_date_params($url, "created_in", $date);
                echo "\n$url\n";
                if($json = Functions::lookup_with_cache($url, $this->download_options))
                {
                    $arr = json_decode($json, true);
                    $total = count($arr['results']);
                    echo "\ntotal = [$total] [$page]\n";
                    // /* //---------------------------start loop
                    $x = array();
                    foreach($arr['results'] as $rec) {
                        if(!in_array($rec['uuid'], $uuids)) { //start process here
                            $uuids[] = $rec['uuid'];
                            @$x['NOT in array']++;
                            self::process_record($rec, $func);
                        }
                        else @$x['already in array']++;
                    }
                    print_r($x);
                    // */ //---------------------------end loop
                    if($total < $this->increment) break; //it actually doesn't reach this bec. of the 10k limit
                    if($page == 10) break; //used 10, if 25 that is half of the 50x200 = 10000
                }
                else break; //may have reached the 10k limit
                $page++;
            }
            //=======================end loop
            $date = self::date_tomorrow($date);
            break; //debug only
        }
        // exit("\neli 01\n");
    }
    
    private function process_record($rek, $func)
    {
        if($rek['geojson']['type'] != "Point") return;
        if(!$rek['taxon']['name']) return;
        print_r($rek); exit;
        $rec = array();
        $this->ctr++;
        $rec['id'] = $this->ctr;
        $rec['taxonID']         = $rek['taxon']['id'];
        $rec['scientificName']  = $rek['taxon']['name'];
        $rec['taxonRank']       = $rek['taxon']['rank'];
        $rec['source']          = $rek['uri'];
        $rec['decimalLatitude'] = $rek['geojson']['coordinates'][1];
        $rec['decimalLongitude'] = $rek['geojson']['coordinates'][0];
        $rec['eventDate']       = $rek['time_observed_at'];
        $rec['recordedBy']      = $rek['user']['name'];
        $rec['locality']        = $rek['place_guess'];
        $rec['modified']        = $rek['updated_at'];
        
        $ancestry = array();
        if($arr = @$rek['identifications'][0]['taxon']['ancestors']) $ancestry = self::parse_ancestors($arr);
        print_r($ancestry); //exit;
        
        $rec['kingdom'] = @$ancestry['kingdom'];
        $rec['phylum']  = @$ancestry['phylum'];
        $rec['class']   = @$ancestry['class'];
        $rec['order']   = @$ancestry['order'];
        $rec['family']  = @$ancestry['family'];
        $rec['genus']   = @$ancestry['genus'];
        
        print_r($rec); //exit;
        /*
        [geojson] => Array(
                    [coordinates] => Array(
                            [0] => -111.73022474
                            [1] => 41.51918058
                        )
                    [type] => Point
                )*/

        $rec = array_map('trim', $rec);
        $func->print_header($rec, CONTENT_RESOURCE_LOCAL_PATH . "$this->folder/observations.txt");
        $val = implode("\t", $rec);
        self::save_to_text_file($val);
    }
    private function parse_ancestors($recs)
    {
        $ancestors = array();
        foreach($recs as $rec) $ancestors[$rec['rank']] = $rec['name'];
        return $ancestors;
    }
    
    private function with_lat_long($rec)
    {
        if(!@$rec['decimalLatitude']) return false;
        if(!@$rec['decimalLongitude']) return false;
        return true;
    }
    private function save_to_text_file($row)
    {
        if($row) {
            $WRITE = Functions::file_open($this->destination[$this->folder], "a");
            fwrite($WRITE, $row . "\n");
            fclose($WRITE);
        }
    }

    private function is_first_day_of_month()
    {
        if("01" == date('d')) return true;
        else return false;
    }
    private function format_date_params($url, $what, $date)
    {
        if($what == "created_in") $str = "&created_d1=".$date;
        else                      $str = "&updated_since=".$date."T00:00:00-00:00";
        $url .= $str;
        return $url;
    }
    private function date_tomorrow($date)
    {
        $date1 = str_replace('-', '/', $date);
        $tomorrow = date('Y-m-d',strtotime($date1 . "+1 days"));
        return $tomorrow;
    }

    /*
    private function get_itis_taxon($rec)
    {
        if($ITIStsn = @$rec['ITIStsn']) {
            if($json = Functions::lookup_with_cache($this->solr_taxa_api.$ITIStsn, $this->download_options)) return json_decode($json, true);
        }
        return false;
    }
    */

}
?>