<?php

//define('DEBUG', true);
//define('MYSQL_DEBUG', true);
//define('DEBUG_TO_FILE', true);
//define("MYSQL_DEBUG", true);
//define('ENVIRONMENT', 'integration');
include_once(dirname(__FILE__) . "/../config/start.php");

$mysqli =& $GLOBALS['mysqli_connection'];




// Running this query first in case there is a problem and we need to quit
echo "starting query\n";
$query = "SELECT tc.id, do.data_type_id, do.visibility_id, do.published FROM taxon_concepts tc STRAIGHT_JOIN hierarchy_entries he ON (tc.id=he.taxon_concept_id) STRAIGHT_JOIN taxa t ON (he.id=t.hierarchy_entry_id) STRAIGHT_JOIN data_objects_taxa dot ON (t.id=dot.taxon_id) STRAIGHT_JOIN data_objects do ON (dot.data_object_id=do.id) WHERE (tc.supercedure_id IS NULL OR tc.supercedure_id=0)";
$result = $mysqli->query($query);
echo "ended query\n";
if(@!$result || @!$result->num_rows) exit;




$visible_id = Visibility::find("visible");

$taxon_concept_content = array();
$i = 0;
while($result && $row=$result->fetch_assoc())
{
    if(($i%20000)==0) echo "$i ".Functions::time_elapsed()."\n";
    $i++;
    
    $id = $row["id"];
    $data_type_id = $row["data_type_id"];
    $visibility_id = $row["visibility_id"];
    $published = $row["published"];
    
    $data_type = new DataType($data_type_id);
    $type_label = strtolower($data_type->label);
    
    if($type_label == "text")
    {
        $attribute = "text";
        $attribute_test = "text";
    }elseif($type_label == "gbif image")
    {
        continue;
    }
    elseif($type_label == "flash" || $type_label == "youtube")
    {
        $attribute = $type_label;
        $attribute_test = "video";
    }
    elseif($type_label == "image")
    {
        $attribute = "image";
        $attribute_test = "image";
    }else continue;
    
    if($visibility_id != Visibility::find("visible") || !$published) $attribute_test .= "_unpublished";
    
    $taxon_concept_content[$id][$attribute] = 1;
    $taxon_concept_content_test[$id][$attribute_test] = 1;
}






$hc_data = fopen(LOCAL_ROOT . "temp/hc.sql", "w+");
$hct_data = fopen(LOCAL_ROOT . "temp/hct.sql", "w+");
$tcc_data = fopen(LOCAL_ROOT . "temp/tcc.sql", "w+");
//$tcct_data = fopen(LOCAL_ROOT . "temp/tcct.sql", "w+");

$i = 0;
$used_tc_id = array();
$query = "SELECT tc.id tc_id, he.id he_id FROM taxon_concepts tc STRAIGHT_JOIN hierarchy_entries he ON (tc.id=he.taxon_concept_id) WHERE (tc.supercedure_id IS NULL OR tc.supercedure_id=0)";
$result = $mysqli->query($query);
while($result && $row=$result->fetch_assoc())
{
    if(($i%20000)==0) echo "$i ".Functions::time_elapsed()."\n";
    $i++;
    
    $tc_id = $row["tc_id"];
    $he_id = $row["he_id"];
    
    if(@!$taxon_concept_content[$tc_id]) continue;
    
    $attributes = array(
        "text"                      => 0,
        "image"                     => 0,
        "child_image"               => 0,
        "flash"                     => 0,
        "youtube"                   => 0,
        "internal_image"            => 0,
        "gbif_image"                => 0,
        "content_level"             => 1,
        "image_object_id"           => 0
    );
    
    $attributes_test = array(
        "text"                      => 0,
        "text_unpublished"          => 0,
        "image"                     => 0,
        "image_unpublished"         => 0,
        "child_image"               => 0,
        "child_image_unpublished"   => 0,
        "video"                     => 0,
        "video_unpublished"         => 0,
        "map"                       => 0,
        "map_unpublished"           => 0,
        "content_level"             => 1,
        "image_object_id"           => 0
    );
    
    if(@$taxon_concept_content[$tc_id])
    {
        foreach($taxon_concept_content[$tc_id] as $attr => $val)
        {
            if(isset($attributes[$attr])) $attributes[$attr] = $val;
        }
    }
        
    if(@$taxon_concept_content_test[$tc_id])
    {
        foreach($taxon_concept_content_test[$tc_id] as $attr => $val)
        {
            if(isset($attributes_test[$attr])) $attributes_test[$attr] = $val;
        }
    }
    
    $content_level = 1;
    if($attributes["text"] && $attributes["image"])
    {
        $content_level = 4;
    }elseif($attributes["text"] || $attributes["image"])
    {
        $content_level = 3;
    }
    $attributes["content_level"] = $content_level;
    $attributes_test["content_level"] = $content_level;
    
    
    if(@!$used_tc_id[$tc_id])
    {
        fwrite($tcc_data, "$tc_id\t". implode("\t", $attributes) ."\n");
        //fwrite($tcct_data, "$tc_id\t". implode("\t", $attributes_test) ."\n");
        $used_tc_id[$tc_id] = 1;
    }
    
    fwrite($hc_data, "$he_id\t". implode("\t", $attributes) ."\n");
    fwrite($hct_data, "$he_id\t". implode("\t", $attributes_test) ."\n");
}

fclose($hc_data);
fclose($hct_data);
fclose($tcc_data);
//fclose($tcct_data);


// exit if there is no new data
if(!filesize(LOCAL_ROOT ."temp/hc.sql") || !filesize(LOCAL_ROOT ."temp/tcc.sql")) exit;



$mysqli->begin_transaction();

echo "Deleting old data\n";
echo "1 of 2\n";
$mysqli->delete("DELETE FROM hierarchies_content");
$mysqli->delete("DELETE FROM hierarchies_content_test");
echo "2 of 2\n";
$mysqli->delete("DELETE FROM taxon_concept_content");
//$mysqli->delete("DELETE FROM taxon_concept_content_test");


echo "inserting new data\n";
echo "1 of 2\n";
$mysqli->load_data_infile(LOCAL_ROOT ."temp/hc.sql", "hierarchies_content");
$mysqli->load_data_infile(LOCAL_ROOT ."temp/hct.sql", "hierarchies_content_test");
echo "1 of 2\n";
$mysqli->load_data_infile(LOCAL_ROOT ."temp/tcc.sql", "taxon_concept_content");
//$mysqli->load_data_infile(LOCAL_ROOT ."temp/tcct.sql", "taxon_concept_content_test");


echo "deleting files\n";
// shell_exec("rm ". LOCAL_ROOT ."temp/hc.sql");
// shell_exec("rm ". LOCAL_ROOT ."temp/tcc.sql");









echo "inserting empty rows\n";
echo "1 of 2\n";
$mysqli->query("INSERT IGNORE INTO hierarchies_content SELECT id, 0, 0, 0, 0, 0, 0, 0, 1, 0 FROM hierarchy_entries he LEFT JOIN hierarchies_content hc ON (he.id=hc.hierarchy_entry_id) WHERE hc.hierarchy_entry_id IS NULL");
$mysqli->query("INSERT IGNORE INTO hierarchies_content_test SELECT id, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0 FROM hierarchy_entries he LEFT JOIN hierarchies_content_test hc ON (he.id=hc.hierarchy_entry_id) WHERE hc.hierarchy_entry_id IS NULL");
echo "2 of 2\n";
$mysqli->query("INSERT IGNORE INTO taxon_concept_content SELECT id, 0, 0, 0, 0, 0, 0, 0, 1, 0 FROM taxon_concepts tc LEFT JOIN taxon_concept_content tcc ON (tc.id=tcc.taxon_concept_id) WHERE tcc.taxon_concept_id IS NULL");
//$mysqli->query("INSERT IGNORE INTO taxon_concept_content_test SELECT id, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0 FROM taxon_concepts tc LEFT JOIN taxon_concept_content_test tcc ON (tc.id=tcc.taxon_concept_id) WHERE tcc.taxon_concept_id IS NULL");











echo "setting child_image\n";
$i = 0;
$used = array();
$result = $mysqli->query("SELECT he.id,he.parent_id FROM hierarchies_content_test hc JOIN hierarchy_entries he ON (hc.hierarchy_entry_id=he.id) WHERE hc.image=1");
while($result && $row=$result->fetch_assoc())
{
    $i++;
    if($i%5000==0) echo "$i\n";
    
    if(!@$used[$row["id"]])
    {
        if($row["parent_id"]) update($row["id"],$row["parent_id"]);
    }
}











Functions::debug("gbif_maps\n\n");

$mysqli->update("UPDATE hierarchies_content SET gbif_image=0");
$mysqli->commit();

$mysqli_maps = load_mysql_environment("maps");
if($mysqli_maps)
{
    echo "starting gbif maps\n";
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
}else
{
    echo "skipping gbif maps\n";
}

$query = "update hierarchies_content_test hct join hierarchies_content hc using (hierarchy_entry_id) set hct.map=1 where hc.gbif_image=1";
$mysqli->update($query);

$query = "update hierarchies_content hc join hierarchy_entries he on (hc.hierarchy_entry_id=he.id) join taxon_concept_content tcc on (he.taxon_concept_id=tcc.taxon_concept_id) set tcc.gbif_image=1 where hc.gbif_image=1";
$mysqli->update($query);

$query = "update hierarchies_content hc join hierarchy_entries he on (hc.hierarchy_entry_id=he.id) join taxon_concept_content_test tcct on (he.taxon_concept_id=tcct.taxon_concept_id) set tcct.map=1 where hc.gbif_image=1";
$mysqli->update($query);

$mysqli->end_transaction();






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
                update($parent_hierarchy_entry_id, $row2["parent_id"]);
            }
        }
    }
}






?>