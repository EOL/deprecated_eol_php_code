<?php
namespace php_active_record;
/* */
class Functions_Pensoft
{
    function __construct() {}
    function initialize_new_patterns()
    {   // exit("\n[$this->new_patterns_4textmined_resources]\nelix1\n");
        $str = file_get_contents($this->new_patterns_4textmined_resources);
        $arr = explode("\n", $str);
        $arr = array_map('trim', $arr);
        // $arr = array_filter($arr); //remove null arrays
        // $arr = array_unique($arr); //make unique
        // $arr = array_values($arr); //reindex key
        // print_r($arr); //exit("\n".count($arr)."\n");
        $i = 0;
        foreach($arr as $row) { $i++;
            $cols = explode("\t", $row);
            if($i == 1) {
                $fields = $cols;
                continue;
            }
            else {
                $k = -1;
                foreach($fields as $fld) { $k++;
                    $rec[$fld] = $cols[$k];
                }
            }
            // print_r($rec); exit;
            /*Array(
                [string] => evergreen
                [measurementType] => http://purl.obolibrary.org/obo/FLOPO_0008548
                [measurementValue] => http://purl.obolibrary.org/obo/PATO_0001733
            )*/
            $this->new_patterns[$rec['string']] = array('mType' => $rec['measurementType'], 'mValue' => $rec['measurementValue']);
        }
    }
}
?>