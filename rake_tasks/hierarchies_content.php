#!/usr/local/bin/php
<?php

define('DEBUG', true);
define('MYSQL_DEBUG', true);
define('DEBUG_TO_FILE', true);
//define("MYSQL_DEBUG", true);
$path = "";
if(preg_match("/^(.*\/)[^\/]+/", $_SERVER["_"], $arr)) $path = $arr[1];
include_once($path."../config/start.php");

$mysqli =& $GLOBALS['mysqli_connection'];






$mysqli->begin_transaction();

// Running this query first in case there is a problem and we need to quit
$query = "SELECT distinct tcn.taxon_concept_id, do.data_type_id, do.visibility_id, do.published FROM taxon_concept_names tcn STRAIGHT_JOIN taxa t ON (tcn.name_id=t.name_id) STRAIGHT_JOIN data_objects_taxa dot ON (t.id=dot.taxon_id) STRAIGHT_JOIN data_objects do ON (dot.data_object_id=do.id) WHERE tcn.vern=0";
$result = $mysqli->query($query);
if(@!$result || @!$result->num_rows)
{
    $mysqli->rollback();
    exit;
}

Functions::debug("hierarchies_content_test\n\n");
$mysqli->delete("DELETE FROM hierarchies_content_test");
$mysqli->query("INSERT INTO hierarchies_content_test SELECT id, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0 FROM hierarchy_entries");

Functions::debug("taxon_concept_content_test\n\n");
$mysqli->delete("DELETE FROM taxon_concept_content_test");
$mysqli->query("INSERT INTO taxon_concept_content_test SELECT id, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0 FROM taxon_concepts");

Functions::debug("hierarchies_content\n\n");
$mysqli->delete("DELETE FROM hierarchies_content");
$mysqli->query("INSERT INTO hierarchies_content SELECT id, 0, 0, 0, 0, 0, 0, 0, 1, 0 FROM hierarchy_entries");

Functions::debug("taxon_concept_content\n\n");
$mysqli->delete("DELETE FROM taxon_concept_content");
$mysqli->query("INSERT INTO taxon_concept_content SELECT id, 0, 0, 0, 0, 0, 0, 0, 1, 0 FROM taxon_concepts");


$i = 0;
$last_taxon_concept_id = 0;
$attributes_old = array();
$attributes = array();
while($result && $row=$result->fetch_assoc())
{
    if(($i%1000)==0) echo "$i\n";
    $i++;
    
    $id = $row["taxon_concept_id"];
    $data_type_id = $row["data_type_id"];
    $visibility_id = $row["visibility_id"];
    $published = $row["published"];
    
    if($id != $last_taxon_concept_id)
    {
        if($last_taxon_concept_id && $attributes)
        {
            add_concept_to_cache($last_taxon_concept_id, $attributes);
        }
        $attributes = array();
    }
    $last_taxon_concept_id = $id;
    
    
    $data_type = new DataType($data_type_id);
    $type_label = strtolower($data_type->label);
    
    if($type_label == "text")
    {
        $attribute = "text";
        $attribute_old = "text";
    }elseif($type_label == "gbif image")
    {
        continue;
    }
    elseif($type_label == "flash" || $type_label == "youtube")
    {
        $attribute = "video";
        $attribute_old = $type_label;
    }
    elseif($type_label == "image")
    {
        $attribute = "image";
        $attribute_old = "image";
    }else continue;
    
    if($visibility_id != Visibility::find("visible") || !$published) $attribute .= "_unpublished";
    
    $attributes["new"][$attribute] = 1;
    $attributes["old"][$attribute_old] = 1;
}

if($last_taxon_concept_id && $attributes) add_concept_to_cache($last_taxon_concept_id, $attributes);



$i = 0;
$used = array();
$result = $mysqli->query("SELECT he.id,he.parent_id FROM hierarchies_content_test hc JOIN hierarchy_entries he ON (hc.hierarchy_entry_id=he.id) WHERE hc.image=1");
while($result && $row=$result->fetch_assoc())
{
    $i++;
    if($i%100==0) echo "$i\n";
    
    if(!@$used[$row["id"]])
    {
        if($row["parent_id"]) update($row["id"],$row["parent_id"]);
    }
}



$mysqli->commit();
Functions::debug("gbif_maps\n\n");

$mysqli->update("UPDATE hierarchies_content SET gbif_image=0");
$mysqli->commit();

$mysqli_maps = load_mysql_environment("maps");
if($mysqli_maps)
{
    $result = $mysqli_maps->query("SELECT DISTINCT taxon_id FROM tile_0_taxon");
    $ids = array();
    while($result && $row=$result->fetch_assoc())
    {
        $ids[] = $row["taxon_id"];

        if(count($ids) >= 50000)
        {
            echo "ADDING\n";
            $query = "update hierarchy_entries he join hierarchies_content hc on (he.id=hc.hierarchy_entry_id) set hc.gbif_image=1 where he.hierarchy_id=129 and he.identifier IN ('". implode("','", $ids) ."')";
            //echo $query."\n";
            $mysqli->update($query);
            $mysqli->commit();

            $ids = array();
            //exit;
        }

    }

    if($ids)
    {
        echo "ADDING\n";
        $query = "update hierarchy_entries he join hierarchies_content hc on (he.id=hc.hierarchy_entry_id) set hc.gbif_image=1 where he.hierarchy_id=129 and he.identifier IN (". implode(",", $ids) .")";
        //echo $query."\n";
        $mysqli->update($query);

        $ids = array();
    }
}





$mysqli->end_transaction();








function add_concept_to_cache($taxon_concept_id, $attributes)
{
    global $mysqli;
    
    if(@$attributes["new"]["image"] && @$attributes["new"]["text"])
    {
        $attributes["new"]["content_level"] = 4;
        $attributes["old"]["content_level"] = 4;
    }elseif(@$attributes["new"]["image"] && @$attributes["new"]["text"])
    {
        $attributes["new"]["content_level"] = 3;
        $attributes["old"]["content_level"] = 3;
    }
    
    foreach($attributes["new"] as $attr => &$val)
    {
        $val = "$attr=$val";
    }
    $parameters = "SET ".implode(", ", $attributes["new"]);
    
    foreach($attributes["old"] as $attr => &$val)
    {
        $val = "$attr=$val";
    }
    $parameters_old = "SET ".implode(", ", $attributes["old"]);
    
    $query = "UPDATE taxon_concept_content_test $parameters WHERE taxon_concept_id=$taxon_concept_id";
    //echo "$query\n";
    $mysqli->query($query);
    
    $query = "UPDATE taxon_concept_content $parameters_old WHERE taxon_concept_id=$taxon_concept_id";
    //echo "$query\n";
    $mysqli->update($query);
    
    $result2 = $mysqli->query("SELECT id FROM hierarchy_entries WHERE taxon_concept_id=$taxon_concept_id");
    while($result2 && $row2=$result2->fetch_assoc())
    {
        $image_object_id = 0;
        if(@$attributes["new"]["image"])
        {
            $query = "SELECT do.id FROM taxon_concept_names tcn JOIN taxa t ON (tcn.name_id=t.name_id) JOIN data_objects_taxa dot ON (t.id=dot.taxon_id) JOIN data_objects do ON (dot.data_object_id=do.id) WHERE tcn.taxon_concept_id=$taxon_concept_id AND tcn.vern=0 AND do.visibility_id=".Visibility::find("visible")." AND do.published=1 AND (tcn.source_hierarchy_entry_id=".$row2["id"]." OR tcn.source_hierarchy_entry_id=0) AND do.data_type_id=".DataType::insert("http://purl.org/dc/dcmitype/StillImage")." ORDER BY do.data_rating, do.id ASC LIMIT 0,1";
            //echo "$query\n";
            $result3 = $mysqli->query($query);
            if($result3 && $row3=$result3->fetch_assoc())
            {
                $image_object_id = $row3["id"];
            }
        }
        
        $query = "UPDATE hierarchies_content_test $parameters, image_object_id=$image_object_id WHERE hierarchy_entry_id=".$row2["id"];
        //echo "$query\n";
        $mysqli->query($query);
        
        $query = "UPDATE hierarchies_content $parameters_old, image_object_id=$image_object_id WHERE hierarchy_entry_id=".$row2["id"];
        //echo "$query\n";
        $mysqli->update($query);
    }
}

function update($hierarchy_entry_id,$parent_hierarchy_entry_id)
{
    global $mysqli;
    global $used;
    
    if(!$hierarchy_entry_id) return;
    
    $content_level = 1;
    $result = $mysqli->query("SELECT content_level FROM hierarchies_content_test WHERE hierarchy_entry_id=$hierarchy_entry_id");
    if($result && $row=$result->fetch_assoc())
    {
        $content_level = $row["content_level"];
        
        if($content_level==1) $content_level = 2;
        
        $mysqli->update("UPDATE hierarchies_content_test SET child_image=1, content_level=$content_level WHERE hierarchy_entry_id=$hierarchy_entry_id");
        $mysqli->update("UPDATE hierarchies_content SET child_image=1, content_level=$content_level WHERE hierarchy_entry_id=$hierarchy_entry_id");
        $mysqli->update("UPDATE hierarchy_entries he JOIN taxon_concept_content tcc ON (he.taxon_concept_id=tcc.taxon_concept_id) SET tcc.child_image=1, tcc.content_level=$content_level WHERE he.id=$hierarchy_entry_id");
        
        $used[$hierarchy_entry_id] = 1;
        
        if(!@$used[$parent_hierarchy_entry_id])
        {
            $result2 = $mysqli->query("SELECT parent_id FROM hierarchy_entries WHERE id=$parent_hierarchy_entry_id");
            if($result2 && $row2=$result2->fetch_assoc())
            {
                update($parent_hierarchy_entry_id,$row2["parent_id"]);
            }
        }
    }
}



?>