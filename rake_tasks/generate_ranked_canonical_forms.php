<?php
namespace php_active_record;

include_once(dirname(__FILE__) . "/../config/environment.php");




require_library('RubyNameParserClient');

$client = new RubyNameParserClient();

// $batch_size = 10000;
// $maximum = 500000;
// $count = 0;

$start = 0;
$max_id = 0;
$limit = 50000;

$result = $GLOBALS['db_connection']->query("SELECT MIN(id) as min, MAX(id) as max FROM names");
if($result && $row=$result->fetch_assoc())
{
    $start = $row["min"];
    $max_id = $row["max"];
}

$start = 12845688;
//$max_id = 49000;

$count = 0;
$GLOBALS['db_connection']->begin_transaction();
for($i=$start ; $i<$max_id ; $i+=$limit)
{
    $query = "SELECT id, string FROM names WHERE id BETWEEN $i AND ". ($i+$limit)." AND (ranked_canonical_form_id IS NULL OR ranked_canonical_form_id=0)";
    foreach($GLOBALS['db_connection']->iterate_file($query) as $row_num => $row)
    {
        $id = trim($row[0]);
        $string = trim($row[1]);
        if(!$string || strlen($string) == 1) continue;
        
        $canonical_form = trim($client->lookup_string($string));
        if($count % 5000 == 0)
        {
            echo "       > Parsed $count names ($id : $string : $canonical_form). Time: ". time_elapsed() ."\n";
        }
        $count++;
        //echo "------\n$string\n$canonical_form\n\n";
        
        if(!$canonical_form)
        {
            echo "$string\n";
            continue;
        }
        
        $canonical_form_id = CanonicalForm::find_or_create_by_string($canonical_form)->id;
        $GLOBALS['db_connection']->query("UPDATE names SET ranked_canonical_form_id=$canonical_form_id WHERE id=$id");
    }
    $GLOBALS['db_connection']->commit();
    echo "COMMITTING\n";
    // break;
}
$GLOBALS['db_connection']->end_transaction();


?>
