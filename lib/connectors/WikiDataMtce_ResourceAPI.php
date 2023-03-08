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

        /*
        name: native range includes     uri: http://eol.org/schema/terms/NativeRange
        name: endemic to                uri: http://eol.org/terms/endemic
        name: geographic distribution   uri: http://eol.org/schema/terms/Present
        Flora do Brasil: 753
            measurementTypes to use:        
            http://eol.org/schema/terms/NativeRange
            http://eol.org/terms/endemic        
        Kubitzki et al: 822
            measurementTypes to use:
            http://eol.org/schema/terms/NativeRange
            http://eol.org/schema/terms/Present
        */
        $this->resourceID_mTypes[753] = array('native range includes', 'endemic to');
        $this->resourceID_mTypes[822] = array('native range includes', 'geographic distribution');
        $this->mType_label_uri['native range includes']   = 'http://eol.org/schema/terms/NativeRange';
        $this->mType_label_uri['endemic to']              = 'http://eol.org/terms/endemic';
        $this->mType_label_uri['geographic distribution'] = 'http://eol.org/schema/terms/Present';
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
        $input["params"] = array("resource_id" => (int) $rec['r.resource_id']);
        $input["type"] = "wikidata_base_qry_resourceID";
        $input["per_page"] = $this->per_page_2; //1000
        
        $input["trait kind"] = "trait";

        // $json = json_encode($input);
        // print_r($input); exit("\n[$json]\nstop muna2\n");

        $path1 = $this->generate_report_path($input); echo "\n".$input["trait kind"]." path: [$path1]\n";
        $file1 = $path1.$input['trait kind']."_qry.tsv";
        $this->tmp_batch_export = $path1 . "/temp_export.qs";

        $input["trait kind"] = "inferred_trait";
        $path2 = $this->generate_report_path($input); echo "\n".$input["trait kind"]." path: [$path2]\n";
        $file2 = $path2.$input['trait kind']."_qry.tsv";
        $this->tmp_batch_export = $path2 . "/temp_export.qs";

        print_r($input);
        // exit("\n$file1\n$file2\nxxx\n");

        // /*
        $input["trait kind"] = "trait";
        if(file_exists($file1)) {
            if($task == 'generate trait reports') $this->create_WD_traits($input);
            elseif($task == 'create WD traits') self::divide_exportfile_send_2quickstatements($input);
        }
        else echo "\n[$file1]\nNo query results yet: ".$input['trait kind']."\n";
        // */
        // /*
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