<?php

include_once(dirname(__FILE__) . "/../config/environment.php");

$mysqli =& $GLOBALS['mysqli_connection'];



Functions::log("Starting hierarchies_content");

// Running this query first in case there is a problem and we need to quit
echo "starting query\n";
$query = "SELECT tc.id, do.data_type_id, do.visibility_id, do.published FROM taxon_concepts tc STRAIGHT_JOIN hierarchy_entries he ON (tc.id=he.taxon_concept_id) STRAIGHT_JOIN taxa t ON (he.id=t.hierarchy_entry_id) STRAIGHT_JOIN data_objects_taxa dot ON (t.id=dot.taxon_id) STRAIGHT_JOIN data_objects do ON (dot.data_object_id=do.id) WHERE (tc.supercedure_id IS NULL OR tc.supercedure_id=0)";
$result = $mysqli->query($query);
echo "ended query\n";
if(@!$result || @!$result->num_rows) {}
else
{
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
    
        if($type_label == "text" ||  $type_label == "flash" || $type_label == "youtube" || $type_label == "image")
        {
            $attribute = $type_label;
        
            if($visibility_id != $visible_id || !$published)
            {
                if($type_label == "text" || $type_label == "image") $attribute .= "_unpublished";
                else continue;
            }
        
            $taxon_concept_content[$id][$attribute] = 1;
        }
    }






    $hc_data = fopen(LOCAL_ROOT . "temp/hc.sql", "w+");
    $tcc_data = fopen(LOCAL_ROOT . "temp/tcc.sql", "w+");

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
            "text_unpublished"          => 0,
            "image"                     => 0,
            "image_unpublished"         => 0,
            "child_image"               => 0,
            "child_image_unpublished"   => 0,
            "flash"                     => 0,
            "youtube"                   => 0,
            "map"                       => 0,
            "content_level"             => 1,
            "image_object_id"           => 0
        );
    
        // override defaults with info from above
        if(@$taxon_concept_content[$tc_id])
        {
            foreach($taxon_concept_content[$tc_id] as $attr => $val)
            {
                if(isset($attributes[$attr])) $attributes[$attr] = $val;
            }
        }
    
        // set the content level
        if($attributes["text"] && $attributes["image"]) $attributes["content_level"] = 4;
        elseif($attributes["text"] || $attributes["image"]) $attributes["content_level"] = 3;
    
    
        if(@!$used_tc_id[$tc_id])
        {
            fwrite($tcc_data, "$tc_id\t". implode("\t", $attributes) ."\n");
            $used_tc_id[$tc_id] = 1;
        }
    
        fwrite($hc_data, "$he_id\t". implode("\t", $attributes) ."\n");
    }

    fclose($hc_data);
    fclose($tcc_data);


    // exit if there is no new data
    if(filesize(LOCAL_ROOT ."temp/hc.sql") && filesize(LOCAL_ROOT ."temp/tcc.sql"))
    {
        $mysqli->begin_transaction();

        echo "Deleting old data\n";
        echo "1 of 2\n";
        $mysqli->delete("DELETE FROM hierarchies_content");
        echo "2 of 2\n";
        $mysqli->delete("DELETE FROM taxon_concept_content");


        echo "inserting new data\n";
        echo "1 of 2\n";
        $mysqli->load_data_infile(LOCAL_ROOT ."temp/hc.sql", "hierarchies_content");
        echo "1 of 2\n";
        $mysqli->load_data_infile(LOCAL_ROOT ."temp/tcc.sql", "taxon_concept_content");


        echo "deleting files\n";
        // shell_exec("rm ". LOCAL_ROOT ."temp/hc.sql");
        // shell_exec("rm ". LOCAL_ROOT ."temp/tcc.sql");


        echo "inserting empty rows\n";
        echo "1 of 2\n";
        $mysqli->query("INSERT IGNORE INTO hierarchies_content SELECT id, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0 FROM hierarchy_entries he LEFT JOIN hierarchies_content hc ON (he.id=hc.hierarchy_entry_id) WHERE hc.hierarchy_entry_id IS NULL");
        echo "2 of 2\n";
        $mysqli->query("INSERT IGNORE INTO taxon_concept_content SELECT id, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0 FROM taxon_concepts tc LEFT JOIN taxon_concept_content tcc ON (tc.id=tcc.taxon_concept_id) WHERE tcc.taxon_concept_id IS NULL");









        echo "setting child_image\n";
        $i = 0;
        $child_image_ids = array();
        $result = $mysqli->query("SELECT he.id,he.parent_id FROM hierarchies_content hc JOIN hierarchy_entries he ON (hc.hierarchy_entry_id=he.id) WHERE hc.image=1");
        while($result && $row=$result->fetch_assoc())
        {
            $i++;
            if($i%5000==0) echo "$i\n";

            if(!@$child_image_ids[$row["id"]])
            {
                if($row["parent_id"]) update($row["id"], $row["parent_id"]);
            }
        }

        $update_ids = array();
        $update_batch_size = 5000;
        foreach($child_image_ids as $id => $val)
        {
            $update_ids[] = $id;
            if(count($update_ids) > $update_batch_size)
            {
                $mysqli->update("UPDATE hierarchies_content SET child_image=1 WHERE hierarchy_entry_id IN (". implode(",", $update_ids) .")");
                $mysqli->update("UPDATE hierarchies_content SET content_level=2 WHERE content_level=1 AND hierarchy_entry_id IN (". implode(",", $update_ids) .")");

                $mysqli->update("UPDATE hierarchy_entries he JOIN taxon_concept_content tcc ON (he.taxon_concept_id=tcc.taxon_concept_id) SET tcc.child_image=1 WHERE he.id IN (". implode(",", $update_ids) .")");
                $mysqli->update("UPDATE hierarchy_entries he JOIN taxon_concept_content tcc ON (he.taxon_concept_id=tcc.taxon_concept_id) SET tcc.content_level=2 WHERE tcc.content_level=1 AND he.id IN (". implode(",", $update_ids) .")");

                $update_ids = array();
            }
        }
        if(count($update_ids))
        {
            $mysqli->update("UPDATE hierarchies_content SET child_image=1 WHERE hierarchy_entry_id IN (". implode(",", $update_ids) .")");
            $mysqli->update("UPDATE hierarchies_content SET content_level=2 WHERE content_level=1 AND hierarchy_entry_id IN (". implode(",", $update_ids) .")");

            $mysqli->update("UPDATE hierarchy_entries he JOIN taxon_concept_content tcc ON (he.taxon_concept_id=tcc.taxon_concept_id) SET tcc.child_image=1 WHERE he.id IN (". implode(",", $update_ids) .")");
            $mysqli->update("UPDATE hierarchy_entries he JOIN taxon_concept_content tcc ON (he.taxon_concept_id=tcc.taxon_concept_id) SET tcc.content_level=2 WHERE tcc.content_level=1 AND he.id IN (". implode(",", $update_ids) .")");
        }

        unset($child_image_ids);





        Functions::debug("gbif_maps\n\n");

        $mysqli->update("UPDATE hierarchies_content SET map=0");
        $mysqli->update("UPDATE taxon_concept_content SET map=0");
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
                    $query = "update hierarchy_entries he join hierarchies_content hc on (he.id=hc.hierarchy_entry_id) set hc.map=1 where he.hierarchy_id=129 and he.identifier IN ('". implode("','", $ids) ."')";
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
                $query = "update hierarchy_entries he join hierarchies_content hc on (he.id=hc.hierarchy_entry_id) set hc.map=1 where he.hierarchy_id=129 and he.identifier IN (". implode(",", $ids) .")";
                //echo $query."\n";
                $mysqli->update($query);

                $ids = array();
            }
        }else
        {
            echo "skipping gbif maps\n";
        }

        $query = "update hierarchies_content hc join hierarchy_entries he on (hc.hierarchy_entry_id=he.id) join taxon_concept_content tcc on (he.taxon_concept_id=tcc.taxon_concept_id) set tcc.map=1 where hc.map=1";



        $mysqli->end_transaction();
    }
}
Functions::log("Ended hierarchies_content");












function update($hierarchy_entry_id, $parent_hierarchy_entry_id)
{
    global $mysqli;
    global $child_image_ids;
    
    if(!$hierarchy_entry_id) return;
    
    $child_image_ids[$hierarchy_entry_id] = 1;
    
    if(!@$child_image_ids[$parent_hierarchy_entry_id])
    {
        $result = $mysqli->query("SELECT parent_id FROM hierarchy_entries WHERE id=$parent_hierarchy_entry_id");
        if($result && $row=$result->fetch_assoc())
        {
            update($parent_hierarchy_entry_id, $row["parent_id"]);
        }
    }
}



?>