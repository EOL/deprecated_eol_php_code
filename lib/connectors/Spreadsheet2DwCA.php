<?php
namespace php_active_record;
/* Converts EOL spreadsheets (based upon the EOL spreadsheet template) TO EOL DwcA */

class Spreadsheet2DwCA
{
    function __construct($params)
    {
        $this->params = $params;
    }
    
    public function convert_spreadsheet_2_dwca()
    {
        print_r($this->params);
        $resource_id = $this->params['resource_id'];
        $url = $this->params['spreadsheet'];
        $suffix = ''; //just blank;
        //start
        $final_archive_gzip_url = null;
        $errors = array();
        if(!$url) {
            $errors[] = "No file was provided";
            return;
        }

        //============
        if(pathinfo($url, PATHINFO_EXTENSION) == "zip") //e.g. usda-plants.xlsx.zip of resource_id = 727
        {
            $zipYN = true;
            $test_temp_dir = create_temp_dir();
            $local = Functions::save_remote_file_to_local($url);
            $output = shell_exec("unzip -o $local -d $test_temp_dir");
            echo "<hr> [$output] <hr>";
            /* $ext = self::get_real_extension_of_zip_file($url); --- not used anymore */
            $ext = "xls";
            $new_local = self::get_file_inside_dir_with_this_extension($test_temp_dir."/*.$ext*");
            /* debug only
            echo "\n\nlocal file = [$local]";
            echo "\nlocal dir = [$test_temp_dir]";
            echo "\nnew local file = [$new_local]\n\n";
            */
            $url = $new_local;
            // exit("<hr>elix<hr>");
        }
        else $zipYN = false;
        //============

        // echo("<hr>elix [$url]<hr>");

        if($temp_dir = ContentManager::download_temp_file_and_assign_extension($url, $suffix, array('suffix' => $suffix, 'timeout' => 900))) //15 minutes timeout (900 seconds)
        {
            if($zipYN)
            {   //remove these 3 that were used above if URL is a zip file
                unlink($local);
                unlink($new_local); //$new_local is inside $test_temp_dir
                recursive_rmdir($test_temp_dir);
            }
            
            debug("\ntemp_dir = [$temp_dir]\n");
            if(is_dir($temp_dir)) {
                // debug("\nwent 111\n");
                $errors[] = "The file provided [$temp_dir] is not an Excel file";
                recursive_rmdir($temp_dir);
            }
            else {  //$temp_dir is a file
                // debug("\nwent 222\n");
                $path_parts = pathinfo($temp_dir);
                $extension = @$path_parts['extension'];
                $archive_tmp_dir = @$path_parts['dirname'] ."/". @$path_parts['filename'];
                if($extension == 'xlsx' || $extension == 'xls')
                {
                    // debug("\nwent 333\n");
                    require_library('ExcelToText');
                    $archive_converter = new ExcelToText($temp_dir);
                    if($archive_converter->errors()) {
                        // debug("\nwent 444\n");
                        $errors = $archive_converter->errors();
                    }
                    elseif($archive_converter->is_new_schema_spreadsheet()) {
                        // debug("\nwent 555\n");
                        $archive_tmp_dir = $archive_converter->convert_to_new_schema_archive();
                        if($archive_converter->errors()) {
                            $errors = $archive_converter->errors();
                            recursive_rmdir($archive_tmp_dir);
                        }
                        else {
                            // debug("\nwent 777\n");
                            debug("\nFINAL archive_tmp_dir = [$archive_tmp_dir]\n");
                            unlink($archive_tmp_dir . ".tar.gz"); //e.g. /tmp/dwca_81087.tar.gz
                            if(Functions::file_rename($archive_tmp_dir, CONTENT_RESOURCE_LOCAL_PATH . "/" . $resource_id))
                            {
                                // debug("\nwent 888\n");
                                $command_line = "tar -czf " . CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".tar.gz --directory=" . CONTENT_RESOURCE_LOCAL_PATH . $resource_id . " .";
                                $output = shell_exec($command_line);
                                debug("\n$output\n");
                                
                                self::optional_diagnostics($resource_id); //optional diagnostics
                                
                                recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH . $resource_id); //to save space, since this is not needed and the .tar.gz is already created
                            }
                            else echo "\nCopy problem encountered.\n";
                            /*not needed anymore
                            if(preg_match("/\/(dwca_[0-9]+)$/", $archive_tmp_dir, $arr)) {
                                $final_archive_gzip_url = WEB_ROOT . "tmp/" . $arr[1] . ".tar.gz";
                            }
                            */
                        }
                    }else $errors[] = "Unable to determine the template of the provided Excel file. Are you sure this matches the EOL template provided at https://github.com/eliagbayani/EOL-connector-data-files/raw/master/schema/eol_import_spreadsheet.xlsx ?";
                }else $errors[] = "The file provided is not an Excel file";
                unlink($temp_dir);
            }
        }
        else $errors[] = "There was a problem with the uploaded [$suffix] file.";
        if($errors) print_r($errors);
    }

    private function optional_diagnostics($resource_id)
    {
        $arr = Functions::count_resource_tab_files($resource_id, ".txt");
        print_r($arr);
        Functions::finalize_connector_run($resource_id, json_encode($arr));
        if(false) { //optional
            if($undefined_uris = Functions::get_undefined_uris_from_resource($resource_id)) print_r($undefined_uris);
            echo "\nUndefined URIs: " . count($undefined_uris) . "\n";
            require_library('connectors/DWCADiagnoseAPI');
            $func = new DWCADiagnoseAPI();
            $func->check_unique_ids($resource_id);
        }
    }

    /*
    private function get_real_extension_of_zip_file($zip_file)
    {
        $fn = pathinfo($zip_file, PATHINFO_FILENAME);
        $fn = pathinfo($fn, PATHINFO_EXTENSION);
        return $fn;
    }
    */
    
    private function get_file_inside_dir_with_this_extension($files)
    {
        $arr = glob($files);
        return $arr[0];
        // foreach (glob($files) as $filename)
        // {
        //     echo "\n- $filename\n";
        // }
    }

}
?>