#!/usr/local/bin/php
<?php

$path = "";
//define('DEBUG', true);
//define('MYSQL_DEBUG', true);
//define('DEBUG_TO_FILE', true);
if(preg_match("/^(.*\/)[^\/]+/", $_SERVER["_"], $arr)) $path = $arr[1];
include_once($path."../config/start.php");

$mysqli =& $GLOBALS['mysqli_connection'];




$mysqli->begin_transaction();

$result = $mysqli->query("SELECT distinct tc.id, tcct.image_object_id, tcct.content_level, he.rank_id, he.lft, he.rgt, he.id, he.taxon_concept_id, he.name_id, do.object_cache_url, n.italicized FROM taxon_concepts tc JOIN taxon_concept_content_test tcct ON (tc.id=tcct.taxon_concept_id) JOIN hierarchy_entries he ON (tc.id=he.taxon_concept_id) JOIN data_objects do ON (tcct.image_object_id=do.id) LEFT JOIN names n ON (he.name_id=n.id) WHERE tc.published=1 AND tc.vetted_id=".Vetted::insert("Trusted")." AND tcct.image=1 AND do.vetted_id=".Vetted::insert("Trusted"));
if(@!$result || @!$result->num_rows)
{
    $mysqli->rollback();
    exit;
}

$mysqli->delete("DELETE FROM random_taxa");




$random_taxa = array();
while($result && $row=$result->fetch_assoc())
{
    //$entry = new HierarchyEntry($row["id"]);
    
    $id = $row["id"];
    $language_id = 0;
    $data_object_id = $row["image_object_id"];
    $name_id = $row["name_id"];
    $image_url = $row["object_cache_url"];
    $thumb_url = "";
    $name = $row["italicized"];
    $common_name_en = "";
    $common_name_fr = "";
    $content_level = $row["content_level"];
    $taxon_concept_id = $row["taxon_concept_id"];
    
    $query = "INSERT INTO random_taxa VALUES (NULL, $language_id, $data_object_id, $name_id, '$image_url', '$thumb_url', '$name', '$common_name_en', '$common_name_fr', $content_level, NOW(), $taxon_concept_id)";
    $random_taxa[] = $query;
}

shuffle($random_taxa);
foreach($random_taxa as $random)
{
    //echo "$random\n";
    $mysqli->insert($random);
}

$mysqli->end_transaction();

?>