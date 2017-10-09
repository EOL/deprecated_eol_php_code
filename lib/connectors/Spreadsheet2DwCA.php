<?php
namespace php_active_record;
/* Converts EOL spreadsheets (based upon the EOL spreadsheet template) TO EOL DwcA */
define("xxx", LOCAL_ROOT . "aaa/bbb");

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
            $output = shell_exec("unzip $local -d $test_temp_dir");
            $ext = self::get_real_extension_of_zip_file($url);
            $new_local = self::get_file_inside_dir_with_this_extension($test_temp_dir."/*.$ext");
            // /* debug only
            echo "\nlocal file = [$local]\n";
            echo "\nlocal dir = [$test_temp_dir]\n";
            echo "\nnew local file = [$new_local]\n";
            // */
            $url = $new_local;
        }
        else $zipYN = false;
        //============

        if($temp_dir = ContentManager::download_temp_file_and_assign_extension($url, $suffix, array('suffix' => $suffix, 'timeout' => 900))) //15 minutes timeout (900 seconds)
        {
            if($zipYN)
            {   //remove these 3 that were used above if URL is a zip file
                unlink($local);
                unlink($new_local); //$new_local is inside $test_temp_dir
                recursive_rmdir($test_temp_dir);
            }
            
            echo "\ntemp_dir = [$temp_dir]\n";
            if(is_dir($temp_dir)) {
                $errors[] = "The file provided [$temp_dir] is not an Excel file";
                recursive_rmdir($temp_dir);
            }
            else {  //$temp_dir is a file
                $path_parts = pathinfo($temp_dir);
                $extension = @$path_parts['extension'];
                $archive_tmp_dir = @$path_parts['dirname'] ."/". @$path_parts['filename'];
                if($extension == 'xlsx' || $extension == 'xls')
                {
                    require_library('ExcelToText');
                    $archive_converter = new ExcelToText($temp_dir);
                    if($archive_converter->errors()) {
                        $errors = $archive_converter->errors();
                    }
                    elseif($archive_converter->is_new_schema_spreadsheet()) {
                        $archive_tmp_dir = $archive_converter->convert_to_new_schema_archive();
                        if($archive_converter->errors()) {
                            $errors = $archive_converter->errors();
                            recursive_rmdir($archive_tmp_dir);
                        }
                        else {
                            echo "\nFINAL archive_tmp_dir = [$archive_tmp_dir]\n";
                            if(Functions::file_rename($archive_tmp_dir, CONTENT_RESOURCE_LOCAL_PATH . "/" . $resource_id))
                            {
                                $command_line = "tar -czf " . CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".tar.gz --directory=" . CONTENT_RESOURCE_LOCAL_PATH . $resource_id . " .";
                                $output = shell_exec($command_line);
                                echo "\n$output\n";
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
        else $errors[] = "There was a problem with the uploaded $suffix file.";
        if($errors) print_r($errors);
    }
    
    private function get_real_extension_of_zip_file($zip_file)
    {
        $fn = pathinfo($zip_file, PATHINFO_FILENAME);
        return pathinfo($fn, PATHINFO_EXTENSION);
    }
    
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