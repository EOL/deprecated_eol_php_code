#!/usr/local/bin/php
<?php

$path = "";
if(preg_match("/^(.*\/)[^\/]+/", $_SERVER["_"], $arr)) $path = $arr[1];
include_once($path."../config/start.php");

$mysqli =& $GLOBALS['mysqli_connection'];


$mysqli->begin_transaction();

$result = $mysqli->query("SELECT he.id,he.lft,he.rgt,he.taxon_concept_id,he.hierarchy_id FROM hierarchies_content_test hc JOIN hierarchy_entries he ON (hc.hierarchy_entry_id=he.id) WHERE he.hierarchy_id!=105 AND he.hierarchy_id!=129 AND (hc.image=1 OR hc.child_image=1 OR hc.image_unpublished=1 OR hc.child_image_unpublished=1)");
if(@!$result || @!$result->num_rows)
{
    $mysqli->rollback();
    exit;
}

$mysqli->delete("DELETE FROM top_images");
$mysqli->delete("DELETE FROM top_unpublished_images");

$i = 0;
while($result && $row=$result->fetch_assoc())
{
    if($i%100 == 0) echo "$i\n";
    $i++;
    
    $id = $row["id"];
    $lft = $row["lft"];
    $rgt = $row["rgt"];
    $taxon_concept_id = $row["taxon_concept_id"];
    $hierarchy_id = $row["hierarchy_id"];
    
    $top_images = array();
    $top_unpublished_images = array();
    
    $query = "SELECT distinct do.id, do.data_rating, do.visibility_id, do.published FROM hierarchy_entries he STRAIGHT_JOIN taxon_concept_names tcn ON (he.taxon_concept_id=tcn.taxon_concept_id) STRAIGHT_JOIN taxa t ON (tcn.name_id=t.name_id) STRAIGHT_JOIN data_objects_taxa dot ON (t.id=dot.taxon_id) STRAIGHT_JOIN data_objects do ON (dot.data_object_id=do.id) WHERE he.lft BETWEEN $lft AND $rgt AND he.hierarchy_id=$hierarchy_id AND tcn.vern=0 AND do.data_type_id=".DataType::find("http://purl.org/dc/dcmitype/StillImage");
    $result2 = $mysqli->query($query);
    while($result2 && $row2=$result2->fetch_assoc())
    {
        $data_rating = $row2["data_rating"];
        $data_object_id = $row2["id"];
        $visibility_id = $row2["visibility_id"];
        $published = $row2["published"];
        if($visibility_id==Visibility::find("visible") && $published==1) $top_images[$data_rating][$data_object_id] = "INSERT INTO top_images VALUES ($id, $data_object_id, ";
        else $top_unpublished_images[$data_rating][$data_object_id] = "INSERT INTO top_unpublished_images VALUES ($id, $data_object_id, ";
    }
    
    $view_order = 1;
    krsort($top_images);
    $first = true;
    foreach($top_images as $k => $v)
    {
        ksort($v);
        foreach($v as $k2 => $v2)
        {
            if($first)
            {
                $query = "UPDATE hierarchies_content_test SET image_object_id=$k2 WHERE hierarchy_entry_id=$id";
                $mysqli->update($query);
                $query = "UPDATE hierarchies_content SET image_object_id=$k2 WHERE hierarchy_entry_id=$id";
                $mysqli->update($query);
                $first = false;
            }
            $query = $v2 . "$view_order)";
            $mysqli->insert($query);
            $view_order++;
            if($view_order > 500) break;
        }
        if($view_order > 500) break;
    }
    unset($images);
    
    
    $view_order = 1;
    ksort($top_unpublished_images);
    foreach($top_unpublished_images as $k => $v)
    {
        ksort($v);
        foreach($v as $k2 => $v2)
        {
            $query = $v2 . "$view_order)";
            $mysqli->insert($query);
            $view_order++;
            if($view_order > 500) break;
        }
        if($view_order > 500) break;
    }
    unset($images);
}

$result = $mysqli->query("SELECT he.taxon_concept_id, do.id, do.data_rating FROM top_images ti JOIN hierarchy_entries he ON (ti.hierarchy_entry_id=he.id) JOIN data_objects do ON (ti.data_object_id=do.id) GROUP BY he.taxon_concept_id ORDER BY do.data_rating DESC, do.id ASC");
while($result && $row=$result->fetch_assoc())
{
    $query = "UPDATE taxon_concept_content_test SET image_object_id=".$row["id"]." WHERE taxon_concept_id=".$row["taxon_concept_id"];
    $mysqli->update($query);
    
    $query = "UPDATE taxon_concept_content SET image_object_id=".$row["id"]." WHERE taxon_concept_id=".$row["taxon_concept_id"];
    $mysqli->update($query);
}


$mysqli->end_transaction();

?>