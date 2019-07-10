<?php
namespace php_active_record;
/* connector: [run.php] */
class MultipleConnJenkinsAPI //this makes use of the GBIF DwCA occurrence downloads
{
    function __construct()
    {
        /* add: 'resource_id' => "gbif" ;if you want to add cache inside a folder [gbif] inside [eol_cache_gbif] */
        $this->download_options = array(
            'expire_seconds'     => false, //60*60*24*30*3, //ideally 3 months to expire
            'download_wait_time' => 1000000, 'timeout' => 60*5, 'download_attempts' => 1, 'delay_in_minutes' => 1);
        // $this->download_options['expire_seconds'] = false; //debug | true -- expires now

        if(Functions::is_production()) $this->download_options['cache_path'] = "/extra/eol_cache_gbif/";
        else                           $this->download_options['cache_path'] = "/Volumes/Thunderbolt4/eol_cache_gbif/";

        if(Functions::is_production()) {}
        else {}
        $this->debug = array();
    }
    function jenkins_call($arr_info, $connector_task)
    {
        $connector = $arr_info['connector'];
        $batches = $arr_info['batches'];
        $divisor = $arr_info['divisor'];
        $total_count = $arr_info['total_count'];
        
        echo "\nCACHE_PATH 01 is ".CACHE_PATH."\n";
        require_once(DOC_ROOT."../LiteratureEditor/Custom/lib/Functions.php");
        require_once(DOC_ROOT."../FreshData/controllers/other.php");
        require_once(DOC_ROOT."../FreshData/controllers/freshdata.php");
        echo "\nCACHE_PATH 02 is ".CACHE_PATH."\n";

        $ctrler = new \freshdata_controller(array());
        ini_set('memory_limit','4096M');
        $postfix = "_run";

        /* was never used here
        $server_http_host = $_SERVER['HTTP_HOST'];
        $server_script_name = $_SERVER['SCRIPT_NAME'];
        $server_script_name = str_replace("form_result.php", "generate_jenkins.php", $server_script_name);
        $params['uuid'] = pathinfo($newfile, PATHINFO_FILENAME);
        //always use DOC_ROOT so u can switch from jenkins to cmdline. BUT DOC_ROOT won't work here either since /config/boot.php is not called here. So use $for_DOC_ROOT instead.
        */
        
        // echo "<pre>"; print_r($parameters); echo "</pre>"; exit;
        $ctr = 0;
        foreach($batches as $batch) {
            $ctr++;
            print_r($batch);
            $param = array();
            $param['range'] = $batch;
            $param['ctr'] = $ctr;
            $param['divisor'] = $divisor;
            $param['total_count'] = $total_count;
            
            $task = $ctrler->get_available_job("map_data_job");
            $json = json_encode($param, true);
            $params['uuid'] = time();

            if    ($connector == "eol_v3_api.php")  $cmd = PHP_PATH.' eol_v3_api.php jenkins ' . "'" . $json . "'";
            elseif($connector == "xxx.php")         $cmd = PHP_PATH.' xxx.php jenkins ' . "'" . $json . "'";
            
            // echo "\n$cmd\n";
            
            // /* works well locally but bit problematic in eol-archive, will abandon for a while. Works OK now, as of Apr 25, 2019.
            $cmd .= " 2>&1";
            $ctrler->write_to_sh($params['uuid'].$postfix, $cmd);
            $cmd = $ctrler->generate_exec_command($params['uuid'].$postfix); //pass the desired basename of the .sh filename (e.g. xxx.sh then pass "xxx")
            $c = $ctrler->build_curl_cmd_for_jenkins($cmd, $task);
            $shell_debug = shell_exec($c);
            // for more debugging...
            // echo "\ncmd: $cmd
            //       \nc: $c";
            // echo "\nshell_debug: [$shell_debug]";

            // break; //debug only -- just run 1 batch
            echo "\nCACHE_PATH 03 is ".CACHE_PATH."\n";
            sleep(20); //this is important so Jenkins will detect that the first job is already taken and will use the next available job. Effective works OK
            // */
        }
    }
    private function total_rows_in_file($file_path)
    {
        $total = shell_exec("wc -l < ".escapeshellarg($file_path));
        $total = trim($total);  echo "\n[$total]\n";
        // $total = 50; //debug force assign
        $total = $total + 10; echo "\nTotal rows ($file_path): [$total]\n"; //just a buffer of +10
        return $total;
    }
    function get_range_batches($total, $divisor)
    {
        $batch = $total/$divisor;
        $batch = ceil($batch);
        for ($x = 1; $x <= $total; $x=$x+$batch) $final[] = array($x, $x+$batch);
        return $final;
    }
    function check_indicator_files_if_ready_2finalize_YN($filename, $divisor)
    {
        for ($x = 1; $x <= $divisor; $x++) {
            $search = str_replace('COUNTER', $x, $filename);
            echo "\nTesting indicator file [$search]...";
            if(file_exists($search)) {
                echo "still exists. Cannot finalize.\n";
                return false;
            }
            else echo "done OK.\n";
        }
        echo "\nCan finalize now!\n";
        return true;
    }
}
?>
