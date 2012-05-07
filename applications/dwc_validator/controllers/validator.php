<?php
namespace php_active_record;

class dwc_validator_controller extends ControllerBase
{
    public static function index($parameters)
    {
        extract($parameters);
        
        $errors = array();
        $eol_errors = array();
        $eol_warnings = array();
        
        
        $dwca_file = @$file_url;
        if(@$dwca_upload['tmp_name']) $dwca_file = $dwca_upload['tmp_name'];
        
        if($dwca_file)
        {
            if($temp_dir = ContentManager::download_temp_file_and_assign_extension($dwca_file))
            {
                if(file_exists($temp_dir . "/meta.xml"))
                {
                    $archive = new ContentArchiveReader(null, $temp_dir);
                    $validator = new ContentArchiveValidator($archive);
                    $validator->get_validation_errors();
                    list($errors, $warnings) = array($validator->errors(), $validator->warnings());
                }else $errors[] = "Unable to locate a meta.xml file";
                
                $files_in_archive = read_dir($temp_dir);
                foreach($files_in_archive as $file)
                {
                    if(substr($file, 0, 1) == '.') continue;
                    unlink($temp_dir ."/". $file);
                }
                @unlink($temp_dir ."/._meta.xml");
                @rmdir($temp_dir);
            }else $errors[] = "There was a problem with the uploaded file";
        }
        
        render_template("validator/index", array("file_url" => @$file_url, "file_upload" => @$dwca_upload['name'], "errors" => @$errors, "warnings" => @$warnings));
    }
}

?>