<?php
namespace php_active_record;
/* 
*/
class TaxonomicValidationRules
{
    function __construct()
    {
    }
    function process_user_file($input_file)
    {
        // echo "\n[".$input_file."] [$this->resource_id]\n";
        
        exit("\n-stop muna-\n");
    }
    /*=========================================================================*/ // COPIED TEMPLATE BELOW
    /*=========================================================================*/
    private function initialize_file($sheet_name)
    {
        $filename = $this->resources['path'].$this->resource_id."_".str_replace(" ", "_", $sheet_name).".txt";
        $WRITE = Functions::file_open($filename, "w"); fclose($WRITE);
        
        $filename = $this->resources['path'].$this->resource_id."_invalid_values.txt";
        $WRITE = Functions::file_open($filename, "w"); fclose($WRITE);
    }
    private function write_output_rec_2txt($rec, $sheet_name)
    {
        $filename = $this->resources['path'].$this->resource_id."_".str_replace(" ", "_", $sheet_name).".txt";
        $fields = array_keys($rec);
        $WRITE = Functions::file_open($filename, "a");
        clearstatcache(); //important for filesize()
        if(filesize($filename) == 0) fwrite($WRITE, implode("\t", $fields) . "\n");
        $save = array();
        foreach($fields as $fld) $save[] = $rec[$fld];
        fwrite($WRITE, implode("\t", $save) . "\n");
        fclose($WRITE);
    }
}
?>