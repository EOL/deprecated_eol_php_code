<?php
namespace php_active_record;
/* 
called from WikiDataMtceAPI.php
*/
class WikiDataMtce_ResourceAPI
{
    function __construct($resource_id = false)
    {
        // $this->resource_id = $resource_id;
        // $this->download_options = array('cache' => 1, 'download_wait_time' => 500000, 'timeout' => 10800, 'expire_seconds' => 60*60*1);
    }

    function run_1_resource_traits($rec, $task)
    {   
        print_r($rec); //exit("\n[$task]\nstop 173\n");
        /* e.g. Array(
            [r.resource_id] => 753
            [trait.source] => 
            [trait.citation] => 
        )*/
        /* good way to run 1 resource for investigation
        if($rec['r.resource_id'] != 753) return; // Flora do Brasil           
        */

        $input = array();
        $input["params"] = array("resource_id" => $rec['r.resource_id']);
        $input["type"] = "wikidata_base_qry_resourceID";
        $input["per_page"] = $this->per_page_2; //1000
        
        $input["trait kind"] = "trait";
        $path1 = $this->generate_report_path($input); echo "\n".$input["trait kind"]." path: [$path1]\n";
        $file1 = $path1.$input['trait kind']."_qry.tsv";
        $this->tmp_batch_export = $path1 . "/temp_export.qs";

        $input["trait kind"] = "inferred_trait";
        $path2 = $this->generate_report_path($input); echo "\n".$input["trait kind"]." path: [$path2]\n";
        $file2 = $path2.$input['trait kind']."_qry.tsv";
        $this->tmp_batch_export = $path2 . "/temp_export.qs";

        // exit("\n$file1\n$file2\nxxx\n");

        $input["trait kind"] = "trait";
        if(file_exists($file1)) {
            if($task == 'generate trait reports') self::create_WD_traits($input);
            elseif($task == 'create WD traits') self::divide_exportfile_send_2quickstatements($input);
        }
        else echo "\n[$file1]\nNo query results yet: ".$input['trait kind']."\n";

        // /* un-comment in real operation
        $input["trait kind"] = "inferred_trait";
        if(file_exists($file2)) {
            if($task == 'generate trait reports') self::create_WD_traits($input);
            elseif($task == 'create WD traits') self::divide_exportfile_send_2quickstatements($input);
            elseif($task == 'remove WD traits') self::divide_exportfile_send_2quickstatements($input, true); //2nd param remove_traits_YN
        }
        else echo "\n[$file2]\nNo query results yet: ".$input['trait kind']."\n";
        // */

        // $func->divide_exportfile_send_2quickstatements($input); exit("\n-end divide_exportfile_send_2quickstatements() -\n");
        return array($path1, $path2);
    }


    function xxx()
    {
        $url = "http://www.marinespecies.org/imis.php?module=person&show=search&fulllist=1";
        $options = $this->download_options;
        $options['expire_seconds'] = 60*60*24*30; //a month to expire
        if($html = Functions::lookup_with_cache($url, $options)) {
        }
    }
}
?>