<?php
namespace php_active_record;
/* connector: [xxx.php]
---------------------------------------------------------------------------
*/
class TSVReaderAPI
{
    function __construct($folder = null, $query = null)
    {
    }
    function read_tsv($tsv_file, $task)
    {   $i = 0; $str = ""; $ids = array();
        foreach(new FileIterator($tsv_file) as $line => $row) { $i++;
            // $row = Functions::conv_to_utf8($row);
            if($i == 1) $fields = explode("\t", $row);
            else {
                if(!$row) continue;
                $tmp = explode("\t", $row);
                $rec = array(); $k = 0;
                foreach($fields as $field) { $rec[$field] = $tmp[$k]; $k++; }
                $rec = array_map('trim', $rec);
                // print_r($rec); exit("\nelix1\n");

                $pageID = $rec['pageID'];
                if($task == "comma_sep_pageID") {
                    $str .= "$pageID, ";
                    if($i % 15 == 0) $str .= "\n";
                    $ids[] = $pageID;
                }
                elseif($task == "IDcorrections") {
                    // elseif($page_id == 70351) return self::fix_further($page_id, $canonical, "Q10295328");
                    $newID = $rec['newID'];
                    $row = 'elseif($page_id == '.$pageID.') return self::fix_further($page_id, $canonical, "'.$newID.'");';
                    echo "\n$row";

                }

                // if($i >= 20) break; //debug
            }
        } //end foreach()
        // echo "\n[$str]\n"; //good debug
        if($task == "comma_sep_pageID") return $ids;
    } //end func
}
?>