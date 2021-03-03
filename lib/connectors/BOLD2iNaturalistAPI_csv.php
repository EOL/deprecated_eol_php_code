<?php
namespace php_active_record;
/* class extends from BOLD2iNaturalistAPI */
class BOLD2iNaturalistAPI_csv
{
    function __construct()
    {
        
    }
    function process_KatieO_csv($filename)
    {
        $csv_file = $this->input['path'].$filename; //exit("\n[$csv_file]\n");
        $i = 0;
        $file = Functions::file_open($csv_file, "r");
        while(!feof($file)) {
            $row = fgetcsv($file);
            if(!$row) break;
            $row = self::clean_html($row); // print_r($row);
            $i++; if(($i % 2000) == 0) echo "\n $i ";
            if($i == 1) {
                $fields = $row; //print_r($fields); //exit("\nfields daw1\n");
                $fields = self::fill_up_blank_fieldnames($fields);
                $count = count($fields);
            }
            else { //main records
                $values = $row;
                if($count != count($values)) { //row validation - correct no. of columns
                    print_r($values); print_r($rec);
                    exit("\nWrong CSV format for this row.\n");
                    // $this->debug['wrong csv'][$class]['identifier'][$rec['identifier']] = '';
                    continue;
                }
                $k = 0;
                $rec = array();
                foreach($fields as $field) {
                    $rec[$field] = $values[$k];
                    $k++;
                }
                $rec = array_map('trim', $rec); //important step
                // print_r($fields); 
                print_r($rec); //exit;
            }
        }
    }
    private function fill_up_blank_fieldnames($cols)
    {
        $i = 0;
        foreach($cols as $col) {
            $i++;
            if(!$col) $final['col_'.$i] = '';
            else      $final[$col] = '';
        }
        return array_keys($final);
    }
    private function clean_html($arr)
    {
        $delimeter = "elicha173";
        $html = implode($delimeter, $arr);
        $html = str_ireplace(array("\n", "\r", "\t", "\o", "\xOB", "\11", "\011"), "", trim($html));
        $html = str_ireplace("> |", ">", $html);
        $arr = explode($delimeter, $html);
        return $arr;
    }

    
}
?>