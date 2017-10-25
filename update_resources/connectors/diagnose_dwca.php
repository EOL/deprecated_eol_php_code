<?php
namespace php_active_record;
/* a utility to diagnose EOL DWC-A */
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/DWCADiagnoseAPI');
$timestart = time_elapsed();

// $source      = DOC_ROOT . "temp/" . "folder2/yyy";
// $destination = DOC_ROOT . "temp/" . "folder2/zzz";
// 

/*
$source      = DOC_ROOT . "temp/";
$destination = DOC_ROOT . "temp2/";
Functions::recursive_copy($source, $destination);
exit("\n\n");
*/

/*
$source      = DOC_ROOT . "app/";
$destination = DOC_ROOT . "app/app2";
echo "\n" . filetype($source) . "\n";
echo "\n" . filetype($destination) . "\n";
exit;
*/

/*
$folder = "/";
$folder = DOC_ROOT . "/temp" . "/folder2";
$folder = CONTENT_RESOURCE_LOCAL_PATH;
Functions::file_rename($source, $destination);
exit("\n\n");
*/


/*
$resource_id = "Coral_Skeletons";//355;
$func = new DWCADiagnoseAPI();
$resource_id = 26; //435609
Functions::count_resource_tab_files($resource_id);
names_breakdown(26);
exit;
*/


// /*
$resource_id = "primate-measurements";
$func = new DWCADiagnoseAPI();
Functions::count_resource_tab_files($resource_id, ".txt");
$result['undefined_uris'] = Functions::get_undefined_uris_from_resource($resource_id);
print_r($result);
exit("\n-end-\n");
// */

//=========================================================
// $func->check_unique_ids($resource_id); return;
//=========================================================
// $func->cannot_delete(); return;
//=========================================================
// $func->get_undefined_uris(); return;
//=========================================================
// $func->list_unique_taxa_from_XML_resource(544);
//=========================================================
// $func->count_rows_in_text_file(DOC_ROOT."applications/genHigherClass/temp/GBIF_Taxa_accepted_pruned_final.tsv"); //2133912 total recs
// $func->count_rows_in_text_file(DOC_ROOT."applications/genHigherClass/temp/GBIF_Taxa_accepted.tsv"); //3111830 total recs

//=========================================================

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";

function names_breakdown($resource_id)
{
    $filename = CONTENT_RESOURCE_LOCAL_PATH . "/$resource_id/taxon.tab";
    $i = 0;
    foreach(new FileIterator($filename) as $line_number => $line)
    {
        $i++;
        $arr = explode("\t", $line);
        if($i == 1) $fields = $arr;
        else
        {
            $k = 0;
            $rec = array();
            foreach($fields as $field)
            {
                $rec[$field] = $arr[$k];
                $k++;
            }
            // print_r($rec); exit;
            //start investigation here
            $debug[$rec['taxonomicStatus']]++;
        }
    }
    print_r($debug);
}
?>
