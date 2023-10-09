<?php
namespace php_active_record;
/* connector: [run.php] */
class MultipleConnJenkinsAPI //this makes use of the GBIF DwCA occurrence downloads
{
    function __construct()
    {
        /* add: 'resource_id' => "gbif" ;if you want to add cache inside a folder [gbif] inside [eol_cache_gbif] */
        /* not needed so far
        $this->download_options = array(
            'expire_seconds'     => false, //60*60*24*30*3, //ideally 3 months to expire
            'download_wait_time' => 1000000, 'timeout' => 60*5, 'download_attempts' => 1, 'delay_in_minutes' => 1);
        if(Functions::is_production()) $this->download_options['cache_path'] = "/extra/eol_cache_gbif/";
        else                           $this->download_options['cache_path'] = "/Volumes/Thunderbolt4/eol_cache_gbif/";
        */
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
        
        if($connector_task == 'finalize')           $job_name = 'eol_stats_job_finalize'; //finalize wikipedia resource
        elseif($connector_task == 'generate_stats') $job_name = 'eol_stats_job'; //orig for EOL stats
        else exit("\nUndefined job ($connector_task).\n");
        
        $ctr = 0;
        foreach($batches as $batch) {
            $ctr++;
            print_r($batch);
            // /* ---------- START main body ----------
            $param = array();
            $param['range'] = $batch;
            $param['ctr'] = $ctr;
            $param['divisor'] = $divisor;
            $param['total_count'] = $total_count;
            
            $task = $ctrler->get_available_job($job_name);
            $json = json_encode($param, true);
            $params['uuid'] = time();
            echo "\njson param: [$json]\n";
            if    ($connector == "eol_v3_api.php")  $cmd = PHP_PATH.' eol_v3_api.php jenkins ' . "'" . $json . "'";
            elseif($connector == "gen_wikipedia_by_lang")
            {   /*Array(
                    [range] => Array(
                            [0] => 317782
                            [1] => 635563
                        )
                    [ctr] => 2
                    [divisor] => 6
                    [total_count] => 1906685
                )*/
                $lang = $arr_info['langx'];
                $six_coverage = @$arr_info['six_coverage'];
                if($connector_task == 'finalize') {
                    $vparams = array();
                    $vparams['language']        = $lang;
                    $vparams['task']            = "generate_resource";
                    $vparams['range_from']      = '';
                    $vparams['range_to']        = '';
                    $vparams['actual']          = '';
                    $vparams['debug_taxon']     = '';
                    $vparams['six_coverage']    = $six_coverage;
                    //todo: add cont_2next_lang
                    $json = json_encode($vparams, true);
                    $cmd = PHP_PATH.' wikipedia.php jenkins ' . "'" . $json . "'";
                    /* orig
                    $cmd = PHP_PATH." wikipedia.php jenkins $lang generate_resource '' '' '' '' ".$six_coverage; //todo: add cont_2next_lang
                    */
                }
                else {
                    $vparams = array();
                    $vparams['language']        = $lang;
                    $vparams['task']            = "generate_resource";
                    $vparams['range_from']      = $param['range'][0];
                    $vparams['range_to']        = $param['range'][1];
                    $vparams['actual']          = $param['ctr']."of".$param['divisor'];
                    $vparams['debug_taxon']     = '';
                    $vparams['six_coverage']    = $six_coverage;
                    $json = json_encode($vparams, true);
                    $cmd = PHP_PATH.' wikipedia.php jenkins ' . "'" . $json . "'";
                    /* orig
                    $cmd = PHP_PATH." wikipedia.php jenkins $lang generate_resource ".$param['range'][0]." ".$param['range'][1]." ".$param['ctr']."of".$param['divisor']." '' ".$six_coverage;
                    */
                    // wikipedia.php jenkins es generate_resource 1 416666 1of10
                    // wikipedia.php jenkins es generate_resource
                }
            }
            elseif($connector == "gen_wikimedia") {
                /* orig
                php5.6 wikidata.php jenkins generate_resource 1 524435 1of10
                php5.6 wikidata.php jenkins generate_resource 2622175 3450000 10of10 
                */
                // /* new
                $vparams = array();
                $vparams['task']        = "generate_resource";
                $vparams['range_from']  = $param['range'][0];
                $vparams['range_to']    = $param['range'][1];
                $vparams['actual']      = $param['ctr']."of".$param['divisor'];
                $vparams['divisor']     = $param['divisor'];
                $json = json_encode($vparams, true);
                $cmd = PHP_PATH.' wikidata.php jenkins ' . "'" . $json . "'";
                // */
            }
            elseif($connector == "xxx.php")             $cmd = PHP_PATH.' xxx.php jenkins ' . "'" . $json . "'";
            
            echo "\n----------\ncmd1 = [$cmd]\n----------\n";
            self::actual_jenkins_call($params, $postfix, $cmd, $task, $ctrler);
            // */ ---------- END main body ----------
        }
    }
    function jenkins_call_single_run($arr_info, $connector_task)
    {
        $connector = $arr_info['connector'];
        $resource_id = @$arr_info['resource_id'];

        echo "\nCACHE_PATH 01 is ".CACHE_PATH."\n";
        require_once(DOC_ROOT."../LiteratureEditor/Custom/lib/Functions.php");
        require_once(DOC_ROOT."../FreshData/controllers/other.php");
        require_once(DOC_ROOT."../FreshData/controllers/freshdata.php");
        echo "\nCACHE_PATH 02 is ".CACHE_PATH."\n";
        print_r($arr_info);

        $ctrler = new \freshdata_controller(array());
        ini_set('memory_limit','4096M');
        $postfix = "_run";

        if($connector_task == 'fillup missing parents') $job_name = 'fillup_missing_parents';
        elseif($connector_task == 'run wikipedia lang') $job_name = 'run_wikipedia_lang';
        elseif($connector_task == 'Back to Wikimedia Run') $job_name = 'Back_to_Wikimedia_Run';
        else exit("\nUndefined connector_task ($connector_task).\n");

        // /* ---------- START main body ----------
        $param = array();
        $task = $ctrler->get_available_job($job_name);
        $json = json_encode($param, true);
        $params['uuid'] = time();
        echo "\njson param: [$json]\n";
        //==========================================================================================================
        if($connector == "fill_up_undefined_parents") {
            // fill_up_undefined_parents.php jenkins '{"resource_id": "wikipedia-is", "source_dwca": "wikipedia-is", "resource": "fillup_missing_parents"}'
            if($connector_task == 'fillup missing parents') {
                $json = '{"resource_id": "'.$resource_id.'", "source_dwca": "'.$resource_id.'", "resource": "fillup_missing_parents"}';
                $cmd = PHP_PATH.' fill_up_undefined_parents_real.php jenkins ' . "'" . $json . "'";
            }
            else exit("\nUndefined connector task [$connector_task].\n");
        }
        //==========================================================================================================
        elseif($connector == "run_wikipedia_lang" && @$arr_info['langx']) {
            // run.php jenkins '{"connector":"gen_wikipedia_by_lang", "divisor":6, "task":"initial", "langx":"ce", "cont_2next_lang":"Y"}'
            $langx = $arr_info['langx'];
            $cont_2next_lang = $arr_info['cont_2next_lang'];
            $json = '{"connector":"gen_wikipedia_by_lang", "divisor":10, "task":"initial", "langx":"'.$langx.'", "cont_2next_lang":"'.$cont_2next_lang.'"}';
            $cmd = PHP_PATH.' run.php jenkins ' . "'" . $json . "'";
        }
        //==========================================================================================================
        elseif($connector == "Back_to_Wikimedia_Run") {
            $json = '{}';
            $cmd = 'echo "going back to wikimedia..."';
        }
        //==========================================================================================================
        elseif($connector == "xxx.php") $cmd = PHP_PATH.' xxx.php jenkins ' . "'" . $json . "'";
        else exit("\nUndefined connector [$connector].\n");
        //==========================================================================================================
        echo "\n----------\ncmd2 = [$cmd]\n----------\n";
        self::actual_jenkins_call($params, $postfix, $cmd, $task, $ctrler);
        // */ ---------- END main body ----------
    }
    private function actual_jenkins_call($params, $postfix, $cmd, $task, $ctrler)
    {   /* good debug
        print_r($params);
        echo "\npostfix: [$postfix]\n";
        echo "\ncmd: [$cmd]\n";
        echo "\ntask: [$task]\n";
        exit("\nstop muna, investigate...\n");
        */
        // /* works well locally Jul 10, 2019, but will still check if it will work in eol-archive - fingers crossed
        $cmd .= " 2>&1";
        $ctrler->write_to_sh($params['uuid'].$postfix, $cmd);
        $cmd = $ctrler->generate_exec_command($params['uuid'].$postfix); //pass the desired basename of the .sh filename (e.g. xxx.sh then pass "xxx")
        $c = $ctrler->build_curl_cmd_for_jenkins($cmd, $task);
        /* good debug
        echo "\n-------------------------\ncmd: [$cmd]\n";
        echo "\ntask: [$task]\n";
        echo "\nc: [$c]\n";
        exit("\nstop muna, investigate...\n");
        */
        $shell_debug = shell_exec($c);
        // for more debugging...
        // echo "\ncmd: $cmd
        //       \nc: $c";
        // echo "\nshell_debug: [$shell_debug]";
        echo "\nCACHE_PATH 03 is ".CACHE_PATH."\n";
        sleep(20); //this is important so Jenkins will detect that the first job is already taken and will use the next available job. Effective works OK
        // */
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