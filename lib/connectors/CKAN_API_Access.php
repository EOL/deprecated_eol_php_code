<?php
namespace php_active_record;
/* 
*/
class CKAN_API_Access
{
    function __construct()
    {
        $this->date_format = "M d, Y h:i:s A";
    }
    private function create_or_update_OpenData_resource()
    {
        if($resource_id = @$this->arr_json['Filename_ID']) {}
        else $resource_id = $this->resource_id;
        
        if($ckan_resource_id = self::get_ckan_resource_id_given_hash("hash-".$resource_id)) self::UPDATE_ckan_resource($resource_id, $ckan_resource_id);
        else self::CREATE_ckan_resource($resource_id);
    }
    function UPDATE_ckan_resource($ckan_resource_id) //https://docs.ckan.org/en/ckan-2.7.3/api/
    {
        $rec = array();
        // $rec['package_id'] = "trait-spreadsheet-repository"; // https://opendata.eol.org/dataset/trait-spreadsheet-repository
        // $rec['clear_upload'] = "true";
        // $rec['url'] = "https://editors.eol.org/eol_php_code/applications/content_server/resources/Trait_Data_Import/1689559574.tar.gz";
        
        // $rec['name'] = $resource_id." name";
        // $rec['hash'] = "hash-".$resource_id;
        // $rec['revision_id'] = $resource_id;
        $rec['id'] = $ckan_resource_id; //e.g. a4b749ea-1134-4351-9fee-ac1e3df91a4f
        $rec['last_modified'] = "2023-07-18T04:36:08";
        // 2023-07-17T04:36:08-04:00
        // 2023-07-17T04:36:08-0400
                         $rec['EOL DwCA resource last updated'] = date($this->date_format); //"July 17, 2023"; //"2023-07-17T03:28:00"; //date("Y-m-d H:s");
        $rec['description'] = "EOL DwCA resource last updated: ".date($this->date_format);

        $json = json_encode($rec);
        
        // $cmd = 'curl https://opendata.eol.org/api/3/action/resource_update';
        $cmd = 'curl https://opendata.eol.org/api/3/action/resource_patch';
        $cmd .= " -d '".$json."'";
        $cmd .= ' -H "Authorization: b9187eeb-0819-4ca5-a1f7-2ed97641bbd4"';


        // curl -X POST  -H "Content-Type: multipart/form-data"  -H "Authorization: XXXX"  -F "id=<resource_id>" -F "upload=@updated_file.csv" https://demo.ckan.org/api/3/action/resource_patch
        
        // sleep(2); //we only upload one at a time, no need for delay
        $output = shell_exec($cmd);
        $output = json_decode($output, true); //print_r($output);

        echo "\n"."Local time: ".date($this->date_format)."\n";

        if($output['success'] == 1) {
            echo "\nOpenData resource UPDATE OK.\n";
            print_r($output);

        }
        else {
            echo "\nERROR: OpenData resource UPDATE failed.\n";
            print_r($output);
        }
        // Array(
        //     [help] => https://opendata.eol.org/api/3/action/help_show?name=resource_update
        //     [success] => 1
        //     [result] => Array(
        //             [cache_last_updated] => 
        //             [cache_url] => 
        //             [mimetype_inner] => 
        //             [hash] => hash-cha_02
        //             [description] => Updated: 2022-02-03 05:36
        //             [format] => Darwin Core Archive
        //             [url] => http://localhost/eol_php_code/applications/content_server/resources/Trait_Data_Import/cha_02.tar.gz
        //             [created] => 2022-02-03T01:40:54.782481
        //             [state] => active
        //             [webstore_last_updated] => 
        //             [webstore_url] => 
        //             [package_id] => dab391f0-7ec0-4055-8ead-66b1dea55f28
        //             [last_modified] => 
        //             [mimetype] => 
        //             [url_type] => 
        //             [position] => 1
        //             [revision_id] => 3c3f2587-c0b3-4fdd-bb5e-c6ae23d79afe
        //             [size] => 
        //             [id] => a4b749ea-1134-4351-9fee-ac1e3df91a4f
        //             [resource_type] => 
        //             [name] => Fishes of Philippines
        //         )
        // )
        // echo "\n$output\n";
    }
    private function CREATE_ckan_resource($resource_id) //https://docs.ckan.org/en/ckan-2.7.3/api/
    {
        $rec = array();
        $rec['package_id'] = "trait-spreadsheet-repository"; // https://opendata.eol.org/dataset/trait-spreadsheet-repository
        $rec['clear_upload'] = "true";
        if(Functions::is_production()) $domain = "https://editors.eol.org";
        else                           $domain = "http://localhost";
        $rec['url'] = $domain.'/eol_php_code/applications/content_server/resources/Taxonomic_Validation/'.$resource_id.'.tar.gz';

        // $rec['name'] = $resource_id." name";
        $rec['hash'] = "hash-".$resource_id;
        // $rec['revision_id'] = $resource_id;
        if($val = @$this->arr_json['Short_Desc']) $rec['name'] = $val;
        $rec['description'] = "Created: ".date("Y-m-d H:s");
        $rec['format'] = "Darwin Core Archive";
        $json = json_encode($rec);
        
        $cmd = 'curl https://opendata.eol.org/api/3/action/resource_create';
        $cmd .= " -d '".$json."'";
        $cmd .= ' -H "Authorization: b9187eeb-0819-4ca5-a1f7-2ed97641bbd4"';
        
        // sleep(2); //we only upload one at a time, no need for delay
        $output = shell_exec($cmd);
        $output = json_decode($output, true);
        if($output['success'] == 1) echo "\nOpenData resource CREATE OK.\n";
        else                        echo "\nERROR: OpenData resource CREATE failed.\n";
    }
    function get_ckan_resource_id_given_hash($hash)
    {
        $ckan_resources = self::get_opendata_resources_given_datasetID("trait-spreadsheet-repository");
        // echo "<pre>"; print_r($ckan_resources); echo "</pre>";
        // Array(
        //     [0] => stdClass Object(
        //             [cache_last_updated] => 
        //             [cache_url] => 
        //             [mimetype_inner] => 
        //             [hash] => cha_02
        //             [description] => Updated: 2022-02-02 20:00
        //             [format] => Darwin Core Archive
        //             [url] => http://localhost/eol_php_code/applications/content_server/resources/Trait_Data_Import/cha_02.tar.gz
        //             [created] => 2022-02-03T00:21:26.418199
        //             [state] => active
        //             [webstore_last_updated] => 
        //             [webstore_url] => 
        //             [package_id] => dab391f0-7ec0-4055-8ead-66b1dea55f28
        //             [last_modified] => 
        //             [mimetype] => 
        //             [url_type] => 
        //             [position] => 0
        //             [revision_id] => 52f079cf-fa6f-40ec-a3f2-b826ed3c3885
        //             [size] => 
        //             [id] => 6f4d804b-6f49-4841-a84e-3e0b02b35043
        //             [resource_type] => 
        //             [name] => cha_02 name
        //         )
        foreach($ckan_resources as $res) {
            if($res->hash == $hash) return $res->id;
        }
        return false;
    }
    private function get_opendata_resources_given_datasetID($dataset, $all_fields = true)
    {
        $options = $this->download_options;
        $options['expire_seconds'] = 0;
        if($json = Functions::lookup_with_cache($this->opendata_dataset_api.$dataset, $options)) {
            $o = json_decode($json);
            if($all_fields) return $o->result->resources;
            foreach($o->result->resources as $res) $final[$res->url] = '';
        }
        return array_keys($final);
    }
    private function process_form_url($form_url, $uuid)
    {   //wget -nc https://content.eol.org/data/media/91/b9/c7/740.027116-1.jpg -O /Volumes/AKiTiO4/other_files/bundle_images/xxx/740.027116-1.jpg
        $ext = pathinfo($form_url, PATHINFO_EXTENSION);
        $target = $this->input['path'].$uuid.".".$ext;
        $cmd = WGET_PATH . " $form_url -O ".$target; //wget -nc --> means 'no overwrite'
        $cmd .= " 2>&1";
        $shell_debug = shell_exec($cmd);
        if(stripos($shell_debug, "ERROR 404: Not Found") !== false) exit("\n<i>URL path does not exist.\n$form_url</i>\n\n"); //string is found
        echo "\n---\n".trim($shell_debug)."\n---\n"; //exit;
        return pathinfo($target, PATHINFO_BASENAME);
    }
    function process_zip_file($filename)
    {
        $test_temp_dir = create_temp_dir();
        $local = Functions::save_remote_file_to_local($this->input['path'].$filename);
        $output = shell_exec("unzip -o $local -d $test_temp_dir");
        if($GLOBALS['ENV_DEBUG']) echo "<hr> [$output] <hr>";
        // $ext = "tab"; //not used anymore
        $new_local = self::get_file_inside_dir_with_this_extension($test_temp_dir."/*.{txt,tsv,tab,csv}");
        $new_local_ext = pathinfo($new_local, PATHINFO_EXTENSION);
        $destination = $this->input['path'].pathinfo($filename, PATHINFO_FILENAME).".$new_local_ext";
        /* debug only
        echo "\n\nlocal file = [$local]";
        echo "\nlocal dir = [$test_temp_dir]";
        echo "\nnew local file = [$new_local]";
        echo "\nnew_local_ext = [$new_local_ext]\n\n";
        echo "\ndestination = [$destination]\n\n";
        */
        if($GLOBALS['ENV_DEBUG']) print_r(pathinfo($destination));
        if(Functions::file_rename($new_local, $destination)) {}
        else echo "\nERRORx: file_rename failed.\n";
        // exit("\nditox 100\n");
        //remove these 2 that were used above if file is a zip file
        unlink($local);
        recursive_rmdir($test_temp_dir);

        return pathinfo($destination, PATHINFO_BASENAME);
    }
    function convert_csv2tsv($csv_file) // temp/1687855441.csv
    {   // exit("<br>source csv: [$csv_file]<br>");
        $tsv_file = str_replace(".csv", ".tsv", $csv_file);
        $WRITE = Functions::file_open($tsv_file, "w");
        $fp = fopen($csv_file, 'r');
        $data = array();
        while (($row = fgetcsv($fp))) { // echo "<pre>"; print_r($row); exit;
            /* Array(
                [0] => taxonID
                [1] => furtherInformationURL
                [2] => scientificName
            ) */
            $tab_separated = implode("\t", $row); 
            fwrite($WRITE, $tab_separated . "\n");
        }
        fclose($fp);
        fclose($WRITE);
        return $tsv_file;
    }
    private function get_file_inside_dir_with_this_extension($files)
    {
        $arr = glob($files, GLOB_BRACE);
        // echo "\nglob() "; print_r($arr); //good debug
        if($val = $arr[0]) return $val;
        else exit("\nERROR: File to process does not exist.\n");
        // foreach (glob($files) as $filename) echo "\n- $filename\n";
    }
    /* =======================================START create DwCA ======================================= */ //copied template
    // private function create_output_file($timestart) {}
    // private function generate_vocabulary() {}
    // private function create_DwCA() {}
    // private function parse_tsv($txtfile, $task) {}
    // private function process_vocab_rec($rec) {}
    // private function write_MoF($rec, $taxonID) {}
    // private function create_child_MoF_startstop($rec, $ret, $taxonID) {}
    // private function log_invalid_values($mType, $mValue, $orig_mType, $orig_mValue) {}
    /* ========================================END create DwCA ======================================== */
    // private function read_input_file($input_file) {}
    // private function read_worksheet($sheet_name, $input_file, $parser) {}
    // private function initialize_file($sheet_name) {}
    // private function write_output_rec_2txt($rec, $sheet_name) {}
    // function test() {}
}
?>