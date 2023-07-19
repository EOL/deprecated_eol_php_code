<?php
namespace php_active_record;
/* DATA-1885: CKAN metadata display adjustments
https://docs.ckan.org/en/2.9/api/#api-examples
*/
class CKAN_API_Access
{
    function __construct($file_type = "EOL resource", $forced_date)
    {
        $this->file_label = self::format_file_label($file_type);
        if($forced_date) {
            $date = strtotime($forced_date);
        }
        else {
            $forced_date = date("m/d/Y H:i:s"); //date today
            $date = strtotime($forced_date);
        }
        $this->date_format = date("M d, Y h:i A", $date);  //July 13, 2023 08:30 AM
        $this->date_str    = date("Y-m-d H:i:s", $date);   //for ISO date computation  --- 2010-12-30 23:21:46    

        echo "\n".$forced_date;
        echo "\n".$this->date_format;
        echo "\n".$this->date_str."\n";

        $this->api_resource_show = "https://opendata.eol.org/api/3/action/resource_show?id=";
        // e.g. https://opendata.eol.org/api/3/action/resource_show?id=259b34c9-8752-4553-ab37-f85300daf8f2
        $this->download_options = array('cache' => 1, 'resource_id' => 'CKAN', 'timeout' => 3600, 'download_attempts' => 1, 'expire_seconds' => 0);

        // /* for EOL resource mapping with Opendata resource
        $this->api_package_list = "https://opendata.eol.org/api/3/action/package_list";
        $this->api_package_show = "https://opendata.eol.org/api/3/action/package_show?id=";
        // */
    }
    function generate_ckan_id_name_list()
    {   $final = array();
        $options = $this->download_options;
        $options['expire_seconds'] = 60*60*24*1; //1 day expires
        if($json = Functions::lookup_with_cache($this->api_package_list, $options)) {
            $packages = json_decode($json);
            // print_r($packages); exit;
            foreach($packages->result as $ckan_resource_id) { // e.g. wikimedia
                $options['expire_seconds'] = 60*60*24*30; //1 month expires
                if($json = Functions::lookup_with_cache($this->api_package_show.$ckan_resource_id, $options)) {
                    $obj = json_decode($json);
                    // print_r($obj->result->resources); exit;
                    foreach($obj->result->resources as $res) {
                        $url = $res->url;
                        if(substr($url, -7) == ".tar.gz") {
                            // print_r($res); //exit;
                            $basename = pathinfo($url, PATHINFO_BASENAME);      //cicadellinaemetarecoded.tar.gz
                            $basename = str_replace(".tar.gz", "", $basename);  //cicadellinaemetarecoded
                            $final[$basename][] = array($res->name, $res->id, $url);
                        }
                    }
                }
            }
        }
        // print_r($final); echo "\n".count($final)."\n"; //good debug
        return $final;
    }
    function update_CKAN_resource_using_EOL_resourceID($EOL_resource_id)
    {
        $id_name_list = self::generate_ckan_id_name_list();
        if($reks = @$id_name_list[$EOL_resource_id]) { // print_r($reks);
            /* Array(
                [0] => Array(
                        [0] => protisten.tar.gz (DwCA)
                        [1] => 84c7f07a-8b39-467b-923e-b9e9ef5fa45a
                        [2] => https://editors.eol.org/eol_php_code/applications/content_server/resources/protisten.tar.gz
                    )
            )*/
            foreach($reks as $rek) {
                /* Array(
                    [0] => protisten.tar.gz (DwCA)
                    [1] => 84c7f07a-8b39-467b-923e-b9e9ef5fa45a
                    [2] => https://editors.eol.org/eol_php_code/applications/content_server/resources/protisten.tar.gz
                )*/
                if($ckan_resource_id = @$rek[1]) {
                    print_r($rek);
                    self::UPDATE_ckan_resource($ckan_resource_id, "Last updated"); //actual CKAN field is "last_modified"
                }
            }
        }
        else echo "\nResource ID [$EOL_resource_id] is not hosted in opendata.eol.org.\n";
        // exit("\n--01\n");
    }
    function UPDATE_ckan_resource($ckan_resource_id, $field2update) //https://docs.ckan.org/en/ckan-2.7.3/api/
    {
        // /* step 1: retrieve record and update description
        $rec = self::retrieve_ckan_resource_using_id($ckan_resource_id);
        // print_r($rec);
        if($rec['success']) {
            $desc = $rec['result']['description'];      echo "\nOld description: [".$desc."]\n";
            $desc = self::format_description($desc);    echo "\nNew description: [".$desc."]\n";
        }
        // */

        /* step 2: update record */
        $rec = array();
        $rec['id'] = $ckan_resource_id; //e.g. a4b749ea-1134-4351-9fee-ac1e3df91a4f
        if($field2update == "Last updated") $rec['last_modified'] = self::iso_date_format(); //date today in ISO date format
        $rec['description'] = $desc;
        $json = json_encode($rec);
        
        // $cmd = 'curl https://opendata.eol.org/api/3/action/resource_update'; // orig but not used here.
        $cmd = 'curl https://opendata.eol.org/api/3/action/resource_patch';     // those fields not updated will remain
        $cmd .= " -d '".$json."'";
        $cmd .= ' -H "Authorization: b9187eeb-0819-4ca5-a1f7-2ed97641bbd4"';

        /* can be used for future routines:
        curl -X POST  -H "Content-Type: multipart/form-data"  -H "Authorization: XXXX"  -F "id=<resource_id>" -F "upload=@updated_file.csv" https://demo.ckan.org/api/3/action/resource_patch
        */

        // sleep(2); //we only upload one at a time, no need for delay
        $output = shell_exec($cmd);
        $output = json_decode($output, true); //print_r($output);

        if($output['success'] == 1) {
            echo "\nOpenData resource UPDATE OK.\n"; //print_r($output);
        }
        else {
            echo "\nERROR: OpenData resource UPDATE failed.\n"; 
            print_r($output);
        }
        // echo "\n$output\n";
        /* Array(
        [help] => https://opendata.eol.org/api/3/action/help_show?name=resource_patch
        [success] => 1
        [result] => Array(
                [cache_last_updated] => 
                [cache_url] => 
                [mimetype_inner] => 
                [hash] => hash-1689582248
                [description] => AAA is here above.
                BBB is here below.         
                ####--- __EOL DwCA resource last updated: Jul 17, 2023 09:31 AM__ ---####
                [format] => Darwin Core Archive
                [url] => https://editors.eol.org/eol_php_code/applications/content_server/resources/Trait_Data_Import/1689582248.tar.gz
                [created] => 2023-07-17T08:24:23.205420
                [state] => active
                [webstore_last_updated] => 
                [webstore_url] => 
                [package_id] => dab391f0-7ec0-4055-8ead-66b1dea55f28
                [last_modified] => 2023-07-17T09:31:26
                [mimetype] => 
                [url_type] => 
                [position] => 23
                [revision_id] => 6c44da2e-384f-4439-a31a-134270e0be94
                [size] => 
                [id] => 259b34c9-8752-4553-ab37-f85300daf8f2
                [resource_type] => 
                [name] => Eli test Jul 17 05
            )        
        )*/
    }
    private function format_file_label($file_type)
    {
        if($file_type == "EOL resource") return "EOL DwCA resource";
        elseif($file_type == "EOL dump") return "EOL dump"; //tentative
        elseif($file_type == "EOL file") return "EOL file"; //tentative
    }
    private function iso_date_format()
    {
        $date_str = $this->date_str; //date("Y-m-d H:i:s"); //2010-12-30 23:21:46
        $iso_date_str = str_replace(" ", "T", $date_str);
        return $iso_date_str;
        /* not accepted by CKAN API resource_update
        $datetime = new \DateTime($date_str);
        echo "\n".$datetime->format(\DateTime::ATOM); // Updated ISO8601
        */
        /* not accepted by CKAN API resource_update
        echo "\n".date(DATE_ISO8601, strtotime($date_str));
        */
    }
    function retrieve_ckan_resource_using_id($ckan_resource_id) // e.g. 259b34c9-8752-4553-ab37-f85300daf8f2
    {
        $options = $this->download_options;
        // $options['expire_seconds'] = false; //during dev only
        if($json = Functions::lookup_with_cache($this->api_resource_show.$ckan_resource_id, $options)) {
            $rec = json_decode($json, true);
            return $rec;
        }
    }
    function format_description($desc)
    {
        // ####--- __EOL DwCA resource last updated: Jul 17, 2023 07:41 AM__ ---####
        // "####--- __"."EOL DwCA resource last updated: ".$this->date_format."__ ---####";

        $left  = "####--- __";
        $right = "__ ---####";
        $desc = self::remove_all_in_between_inclusive($left, $right, $desc, $includeRight = true);

        $arr = explode("\n", $desc); //print_r($arr);
        // echo "\nlast element is: [".end($arr)."]\n";
        if(end($arr) == "") {} //echo "\nlast element is nothing\n";
        else $desc .= chr(13); //add a next line

        // $this->iso_date_str = self::iso_date_format()
        $add_str = "####--- __".$this->file_label." last updated: ".$this->date_format."__ ---####";
        $desc .= $add_str;
        return $desc;
    }
    private function remove_all_in_between_inclusive($left, $right, $html, $includeRight = true)
    {
        if(preg_match_all("/".preg_quote($left, '/')."(.*?)".preg_quote($right, '/')."/ims", $html, $arr)) {
            foreach($arr[1] as $str) {
                if($includeRight) { //original
                    $substr = $left.$str.$right;
                    $html = str_ireplace($substr, '', $html);
                }
                else { //meaning exclude right
                    $substr = $left.$str.$right;
                    $html = str_ireplace($substr, $right, $html);
                }
            }
        }
        return $html;
    }
    //========================================================== below copied templates =========================================
    /* copied template
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
    private function create_or_update_OpenData_resource()
    {
        if($resource_id = @$this->arr_json['Filename_ID']) {}
        else $resource_id = $this->resource_id;
        
        if($ckan_resource_id = self::get_ckan_resource_id_given_hash("hash-".$resource_id)) self::UPDATE_ckan_resource($resource_id, $ckan_resource_id);
        else self::CREATE_ckan_resource($resource_id);
    }
    */
}
?>