<?php
namespace php_active_record;

include_once(dirname(__FILE__) . "/../config/environment.php");
exit;

$start = 0;
$max_id = 0;
$limit = 500000;
$result = $GLOBALS['db_connection']->query("SELECT MIN(id) as min, MAX(id) as max FROM taxon_concepts WHERE published=1");
if($result && $row=$result->fetch_assoc())
{
    $start = $row["min"];
    $max_id = $row["max"];
}

for($i=$start ; $i<$max_id ; $i+=$limit)
{
    $taxon_concept_ids_needing_name_rebuilding = array();
    $result = $GLOBALS['db_connection']->query("SELECT tc.id
        FROM taxon_concepts tc
        LEFT JOIN taxon_concept_names tcn ON (tc.id=tcn.taxon_concept_id)
        WHERE tc.id BETWEEN $i AND ". ($i+$limit)."
        AND tc.published=1 AND tc.supercedure_id=0 AND tcn.taxon_concept_id IS NULL");
    while($result && $row=$result->fetch_assoc())
    {
        $taxon_concept_ids_needing_name_rebuilding[] = $row['id'];
    }
    
    if($taxon_concept_ids_needing_name_rebuilding)
    {
        Tasks::update_taxon_concept_names($taxon_concept_ids_needing_name_rebuilding);
    }
}



?>
