<?php

/*
for Peter, Cyndy as of April 28

                    agent   CP      resource_id     latest harvest      
Wikipedia           38132   129     80              1584 
Wikimedia Commons   12445   119     71              1577


curation activity includes: hide,approve,disapprove,show,inappropriate
*/

//$GLOBALS['ENV_NAME'] = "integration_logging";
$GLOBALS['ENV_NAME'] = "slave_215";
include_once(dirname(__FILE__) . "/../../config/environment.php");
$mysqli =& $GLOBALS['mysqli_connection'];
$mysqli2 = load_mysql_environment('slave_215_production'); 

//$mysqli2 = load_mysql_environment('staging');        
//$mysqli2 = load_mysql_environment('eol_logging_production'); 
//$mysqli2 = load_mysql_environment('development'); 

$agents = array();
$agents[] = array("name"=>"Wikipedia","agent_id"=>38132);
$agents[] = array("name"=>"Wikimedia","agent_id"=>12445);

foreach($agents as $agent)
{
    print "<hr>" . $agent["name"]."<br>";
    $agent_id = $agent["agent_id"];
    //========================================================================================
    $latest_harvest_id = get_latest_harvest_id($agent_id);
    $arr_do_ids = get_data_object_ids($latest_harvest_id);
    //========================================================================================    
    get_wiki_do_curated($arr_do_ids,"data_object");
    //print"<pre>";print_r($wiki_do_curated);print"</pre>";
    //========================================================================================
}



// /*
print "<hr>" . " DATAOBJECTS Curation Activity" . "<br>";
get_wiki_do_curated(false,"data_object");

print "<hr>" . " COMMENTS Curation Activity" . "<br>";
get_wiki_do_curated(false,"comment");

print "<hr>" . " USER-SUBMITTED-TEXT Curation Activity" . "<br>";
get_wiki_do_curated(false,"users_submitted_text");
// */





//=======================================================================================================================
//=======================================================================================================================

function get_tc_id($do_id,$type)
{
    global $mysqli;
    global $mysqli2;
    /*
    data_object
    comment
    tag
    users_submitted_text    
    */
 
    if($type == "data_object")   
    {   $query="Select data_objects_taxon_concepts.taxon_concept_id,
        data_objects.data_type_id
        From data_objects_taxon_concepts
        Inner Join data_objects ON data_objects_taxon_concepts.data_object_id = data_objects.id
        Where data_objects_taxon_concepts.data_object_id = $do_id ";

        $result = $mysqli->query($query);
        $row = $result->fetch_row();            
        $tc_id = $row[0];
        if($row[1]==3)  $str="text";
        else            $str="image";    
        
    }
    elseif($type == "users_submitted_text")   
    {   $query="Select
        users_data_objects.data_object_id,
        users_data_objects.taxon_concept_id,
        users_data_objects.id
        From users_data_objects 
        Where users_data_objects.id = $do_id ";

        $result = $mysqli2->query($query);
        $row = $result->fetch_row();            
        $do_id = $row[0];
        $tc_id = $row[1];
        $str="text";
        
        /* maybe not needed
        $query="Select data_objects_taxon_concepts.taxon_concept_id,
        data_objects.data_type_id
        From data_objects_taxon_concepts
        Inner Join data_objects ON data_objects_taxon_concepts.data_object_id = data_objects.id
        Where data_objects_taxon_concepts.data_object_id = $do_id ";
        $result = $mysqli->query($query);
        $row = $result->fetch_row();            
        if($row[1]==3)  $str="text";
        else            $str="image";    
        */
        
    }

    
    
    return array($tc_id,$str,$do_id);
    
}


function get_wiki_do_curated($arr_do_ids,$type)
{
    global $mysqli2;
    
    $query="
Select
actions_histories.object_id data_object_id,
changeable_object_types.ch_object_type,
action_with_objects.action_code code,
users.given_name,
users.family_name,
actions_histories.updated_at, actions_histories.user_id
From
action_with_objects
Inner Join actions_histories ON actions_histories.action_with_object_id = action_with_objects.id
Inner Join changeable_object_types ON actions_histories.changeable_object_type_id = changeable_object_types.id
Inner Join users ON actions_histories.user_id = users.id
where changeable_object_types.ch_object_type = '$type'
    
    ";
    
    if($arr_do_ids) $query .= " and actions_histories.object_id IN (".implode(",", $arr_do_ids).") ";
    
    $query .= " Order By actions_histories.id Desc ";
    
    $result = $mysqli2->query($query);
    
    $ids=array();
    $curators=array();
    while($result && $row=$result->fetch_assoc())
    {
        $ids[$row["data_object_id"]]=1;
        $curators[$row["user_id"]]=1;
    }
    
    print "Curation Activity: $result->num_rows | $type: " . count($ids) . " | Curators: " . count($curators);
    
    $i=0;
    print"<table cellpadding='3' cellspacing='0' border='1' style='font-size : small; font-family : Arial Unicode MS;'>
        <tr>
            <td>#</td>
            <td>Curator</td>
            <td>Activity</td>
            <td>Object ID</td>
            <td>Date Updated</td>
        </tr>    
    ";
    $result = $mysqli2->query($query);
    while($result && $row=$result->fetch_assoc())
    {
        $i++;
        
        //----------------------------------------------
        if(!in_array($type,array("comment","")))
        {
        $arr = get_tc_id($row["data_object_id"],$type);        
        $tc_id = $arr[0];
        $str = $arr[1];        
        $do_id = $arr[2];                
        $permalink = "http://www.eol.org/pages/" . $tc_id . "?" . $str . "_id=$do_id";
        }
        //----------------------------------------------
        
        print "
        <tr>
            <td align='right'>$i</td>
            <td>$row[given_name] $row[family_name]</td>
            <td>$row[code]</td>";
            
            if(!in_array($type,array("comment","")))print"<td><a target='eol' href='$permalink'>$row[data_object_id]</a></td>";
            else print"<td>$row[data_object_id]</td>";
            
            print"
            <td>$row[updated_at]</td>
        </tr>
        ";
        /*
        http://www.eol.org/pages/791511?text_id=6591396
        http://www.eol.org/pages/791511?image_id=5897493
        */
        
    }
    print"</table>";
    
    
    
    
    
}




function get_data_object_ids($latest_harvest_id)
{
    global $mysqli;
    
    $query="Select
    data_objects_harvest_events.data_object_id id
    From harvest_events
    Inner Join data_objects_harvest_events ON harvest_events.id = data_objects_harvest_events.harvest_event_id
    Where harvest_events.id = $latest_harvest_id
    ";
    
    //print"<hr>$query<hr>";    
    
    $result = $mysqli->query($query);
    //print"<pre>";print_r($result);print"</pre>";exit;
    
    $do=array();
    while($result && $row=$result->fetch_assoc())
    {
        $do[$row["id"]]=1;
    }
    $do = array_keys($do);
    
    //print"<pre>";print_r($do);print"</pre>";//exit;
    return $do;
    
}    

        
function get_latest_harvest_id($agent_id)
{
    global $mysqli;
    
    $query="Select agents_resources.agent_id, harvest_events.resource_id, Max(harvest_events.id) max_harvest_event_id
    From harvest_events
    Inner Join agents_resources ON agents_resources.resource_id = harvest_events.resource_id
    Where agents_resources.agent_id = $agent_id
    Group By agents_resources.agent_id, harvest_events.resource_id
    ";
    $result = $mysqli->query($query);
    $row = $result->fetch_row();            
    $latest_harvest_id = $row[2];
    
    //print "latest harvest_event_id = " . $latest_harvest_id . "<br>";
    //exit;
    return $latest_harvest_id;
    
}

    
?>