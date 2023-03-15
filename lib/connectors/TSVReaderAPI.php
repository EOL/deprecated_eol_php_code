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
    {   $i = 0;  

        if($task == "array_of_pageIDs")             $ids = array();
        elseif($task == "IDcorrections_pair")       $pairs = array();
        elseif($task == "IDcorrections_syntax")     {}
        elseif($task == "comm_sep_IDs")             $str = "";
        
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

                if($task == "array_of_pageIDs") {
                    $pageID = $rec['pageID'];
                    $ids[] = $pageID;
                }
                elseif($task == "IDcorrections_pair") {
                    $pageID = $rec['pageID'];
                    if(    $val = @$rec['newID']) $newID = $val;
                    elseif($val = @$rec['NewID']) $newID = $val;
                    else exit("\nNo new ID. [$tsv_file\n");
                    $pairs[] = array($pageID, $newID);
                }
                elseif($task == "IDcorrections_syntax") {
                    // elseif($page_id == 70351) return self::fix_further($page_id, $canonical, "Q10295328");
                    $pageID = $rec['pageID'];
                    $newID = $rec['newID'];
                    $row = 'elseif($page_id == '.$pageID.') return self::fix_further($page_id, $canonical, "'.$newID.'");';
                    echo "\n$row";                
                }
                elseif($task == "comm_sep_IDs") { // no case scenario yet
                    $str .= "$pageID, ";
                    if($i % 15 == 0) $str .= "\n";
                }
                // if($i >= 20) break; //debug
            }
        } //end foreach()
        if($task == "array_of_pageIDs") return $ids;
        elseif($task == "IDcorrections_pair") return $pairs;
        elseif($task == "comm_sep_IDs") {echo "\n[$str]\n"; exit("\nNo return value.\n");}
    } //end func
}
?>