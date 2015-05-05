<?php
namespace php_active_record;

include_once(dirname(__FILE__) . "/../config/environment.php");

$mysqli =& $GLOBALS['mysqli_connection'];



$binary = file_get_contents('http://ligercat.ubio.org/eol_ids_with_clouds.txt.gz');
if(!($OUT = fopen(DOC_ROOT . 'temp/eol_ids_with_clouds.txt.gz', 'w+')))
{
  debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . DOC_ROOT . 'temp/eol_ids_with_clouds.txt.gz');
  return;
}
fwrite($OUT, $binary);
fclose($OUT);
shell_exec("gunzip -f ". DOC_ROOT . "temp/eol_ids_with_clouds.txt.gz");



$mysqli->begin_transaction();

$mock_collection = Functions::mock_object("Collection",
array(
    "agent_id"  => Agent::find("Biology of Aging"),
    "title"     => "LigerCat",
    "vetted"    => 1,
    "link"      => "http://ligercat.ubio.org/",
    "logo_url"  => '',
    "uri"       => 'http://ligercat.ubio.org/eol/FOREIGNKEY'));
$collection_id = Collection::insert($mock_collection);
$collection = new Collection($collection_id);

$mysqli->delete("DELETE FROM mappings WHERE collection_id = $collection->id");


$file = file(DOC_ROOT . "temp/eol_ids_with_clouds.txt");
$i = 0;
$ids = array();
foreach($file as $line)
{
    $i++;
    if($i%1000==0) echo "$i\n";
    $parts = explode("\t", rtrim($line, "\n\r"));
    $id = trim($parts[0]);
    if(!$id) continue;
    
    $ids[] = $id;
    if(count($ids) >= 5000)
    {
        $result = $mysqli->query("SELECT name_id,  taxon_concept_id FROM hierarchy_entries WHERE taxon_concept_id IN (". implode (",", $ids) .") GROUP BY taxon_concept_id");
        while($result && $row=$result->fetch_assoc())
        {
            $collection->add_mapping_by_name_id($row['name_id'], $row['taxon_concept_id']);
        }
        $ids = array();
    }
}

if($ids)
{
    $result = $mysqli->query("SELECT name_id,    taxon_concept_id FROM hierarchy_entries WHERE taxon_concept_id IN (". implode (",", $ids) .") GROUP BY taxon_concept_id");
    while($result && $row=$result->fetch_assoc())
    {
        $collection->add_mapping_by_name_id($row['name_id'], $row['taxon_concept_id']);
    }
}

$mysqli->end_transaction();

shell_exec("rm -f ". DOC_ROOT . "temp/eol_ids_with_clouds.txt");



?>