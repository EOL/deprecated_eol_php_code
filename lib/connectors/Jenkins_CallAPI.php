<?php
namespace php_active_record;
/* connector: [call_jenkins.php]
*/
class Jenkins_CallAPI
{
    function __construct()
    {
    }
    function jenkins_call($params)
    {
        /* Array(
            [script] => branch painting
            [resource_ID] => 640
            [prod_OR_beta] => beta
        )*/
        
        echo "\nCACHE_PATH 01 is ".CACHE_PATH."\n";
        require_once(DOC_ROOT."../LiteratureEditor/Custom/lib/Functions.php");
        require_once(DOC_ROOT."../FreshData/controllers/other.php");
        require_once(DOC_ROOT."../FreshData/controllers/freshdata.php");
        echo "\nCACHE_PATH 02 is ".CACHE_PATH."\n";

        $ctrler = new \freshdata_controller(array());
        ini_set('memory_limit','10096M'); //15096M
        $postfix = "_map_data";

        // echo "<pre>"; print_r($parameters); echo "</pre>"; exit;
        
        
        $param = array();
        $param['group'] = $group;
        $param['range'] = $batch;
        $param['ctr'] = $ctr;
        $param['rank'] = $filter_rank;
        
        if($connector_task == "breakdown_GBIF_DwCA_file")                   $job_str = "map_data_break_".substr($group,0,3)."_job"; // 'Ani' 'Pla' 'Oth'
        elseif($connector_task == "generate_map_data_using_GBIF_csv_files") $job_str = "map_data_job";
        elseif($connector_task == "gen_map_data_forTaxa_with_children")     $job_str = "map_data_ch_".substr($param['rank'],0,3)."_job"; //map_data_ch_gen_job_4
        
        $task = $ctrler->get_available_job($job_str);
        $json = json_encode($param, true);
        $params['uuid'] = time();

        if    ($connector_task == "breakdown_GBIF_DwCA_file")               $cmd = PHP_PATH.' breakdown_GBIF_DwCA_file.php jenkins ' . "'" . $json . "'";
        elseif($connector_task == "generate_map_data_using_GBIF_csv_files") $cmd = PHP_PATH.' generate_map_data_using_GBIF_csv_files.php jenkins ' . "'" . $json . "'";
        elseif($connector_task == "gen_map_data_forTaxa_with_children")     $cmd = PHP_PATH.' gen_map_data_forTaxa_with_children.php jenkins ' . "'" . $json . "'";
        
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

        echo "\nCACHE_PATH 03 is ".CACHE_PATH."\n";
        sleep(20); //this is important so Jenkins will detect that the first job is already taken and will use the next available job. Effective works OK
        // */
    }
}
?>