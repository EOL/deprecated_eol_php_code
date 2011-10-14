<?php
namespace php_active_record;

include_once(dirname(__FILE__) . "/../config/environment.php");
$mysqli = $GLOBALS['db_connection'];


require_library("PageRichnessCalculator");

$calc = new PageRichnessCalculator();



$limit = 50000;
$result = $mysqli->query("SELECT MIN(id) as min, MAX(id) as max FROM taxon_concepts tc");
if($result && $row=$result->fetch_assoc())
{
    $start = $row["min"];
    $max_id = $row["max"];
}
//$start = 11500001;
for($i=$start ; $i<$max_id ; $i+=$limit)
{
    echo "begin_calculating $i AND ". ($i + $limit) ."\n";
    $scores = $calc->begin_calculating(null, "$i AND ". ($i + $limit));
    //print_r($scores);
    //break;
    $mysqli->begin_transaction();
    foreach($scores as $taxon_concept_id => $richness_score)
    {
        $mysqli->update("UPDATE taxon_concept_metrics SET richness_score = ". $richness_score ." WHERE taxon_concept_id = ". $taxon_concept_id);
    }
    $mysqli->end_transaction();
}


// $ids = array();
// foreach(new FileIterator('march_hotlist_names_lookup.txt') as $line_number => $line)
// {
//     $parts = explode("\t", trim($line));
//     if($parts[1] != 0) $ids[] = $parts[1];
// }
// 
// 
// print_r($ids);
// 
// $calc->begin_calculating($ids);


?>
