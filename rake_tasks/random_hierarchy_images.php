<?php

//define('DEBUG', true);
//define('MYSQL_DEBUG', true);
//define('DEBUG_TO_FILE', true);
//define('ENVIRONMENT', 'staging');
include_once(dirname(__FILE__) . "/../config/start.php");

$mysqli =& $GLOBALS['mysqli_connection'];



$result = $mysqli->query("SELECT distinct he.id, tcc.image_object_id, he.hierarchy_id, he.taxon_concept_id, n.italicized name FROM taxon_concepts tc JOIN taxon_concept_content tcc ON (tc.id=tcc.taxon_concept_id) JOIN hierarchy_entries he ON (tc.id=he.taxon_concept_id) JOIN data_objects do ON (tcc.image_object_id=do.id) LEFT JOIN names n ON (he.name_id=n.id) WHERE tc.published=1 AND tc.vetted_id=".Vetted::insert("Trusted")." AND tcc.image=1 AND do.vetted_id=".Vetted::insert("Trusted"));
if(@!$result || @!$result->num_rows) {}
{
    $mysqli->begin_transaction();

    $mysqli->delete("DELETE FROM random_hierarchy_images");


    $random_taxa = array();
    while($result && $row=$result->fetch_assoc())
    {
        $id = $row["id"];
        $data_object_id = $row["image_object_id"];
        $hierarchy_id = $row["hierarchy_id"];
        $taxon_concept_id = $row["taxon_concept_id"];
        $name = $mysqli->real_escape_string($row["name"]);
    
        $query = "INSERT INTO random_hierarchy_images VALUES (NULL, $data_object_id, $id, $hierarchy_id, $taxon_concept_id, '$name')";
        $random_taxa[] = $query;
    }

    shuffle($random_taxa);
    foreach($random_taxa as $random)
    {
        $mysqli->insert($random);
    }

    $mysqli->end_transaction();
}

?>