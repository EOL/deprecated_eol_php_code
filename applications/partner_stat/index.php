<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head><title>Content Partner Stats</title></head>
<body>
    <script language="javascript1.2">
    function proc()
    {
        document.forms.fn.agent_id.options.selectedIndex=0
        document.forms.fn.submit();
    }
    </script>

<?php

//define("ENVIRONMENT", "slave_32");
//define("ENVIRONMENT", "slave_215");
//define("ENVIRONMENT", "development");
define("MYSQL_DEBUG", false);
define("DEBUG", true);
include_once(dirname(__FILE__) . "/../../config/start.php");
$mysqli =& $GLOBALS['mysqli_connection'];

$agent_id = get_val_var("agent_id");
$agentID = get_val_var('agentID');
if($agentID != "")$agent_id = $agentID;//new, to accommodate agentID, not just agent_id

$with_published_content = get_val_var("with_published_content");
if($agent_id == "") display_form();
else                process_agent_id($agent_id);

function process_agent_id($agent_id)
{
    global $mysqli;
    $qry = "Select harvest_events.id, harvest_events.published_at 
    From agents_resources 
    Inner Join harvest_events ON agents_resources.resource_id = harvest_events.resource_id 
    Where agents_resources.agent_id = $agent_id 
    order by harvest_events.id desc limit 5 ";
    $result = $mysqli->query($qry);    
    //print "agent_id = $agent_id <br>";    
    $ctr=0;    
    while($result && $row=$result->fetch_assoc())	    
    {
        $ctr++;
        //print "<hr>harvest_event_id = $row[id] $row[published_at] ";    
        
        $query = "SELECT a.full_name FROM agents a WHERE a.id = $agent_id";                            		
        $result2 = $mysqli->query($query);    
        $row2 = $result2->fetch_row();            
        $agent_name = $row2[0];
        
        $taxa_count = get_taxon_concept_ids_from_harvest_event($row["id"]);
        
        //$data_object_stats = process_do($row["id"],$result2->num_rows,$row["published_at"],$agent_name,$agent_id,$ctr);        
          $data_object_stats = process_do($row["id"],$taxa_count,$row["published_at"],$agent_name,$agent_id,$ctr);        		

    }//end while
}

function get_taxon_concept_ids_from_harvest_event($harvest_event_id)
{   
    global $mysqli;
    
    $query = "Select distinct hierarchy_entries.taxon_concept_id as id
    From harvest_events_taxa
    Inner Join taxa ON harvest_events_taxa.taxon_id = taxa.id
    Inner Join hierarchy_entries ON taxa.name_id = hierarchy_entries.name_id
    Inner Join taxon_concepts ON taxon_concepts.id = hierarchy_entries.taxon_concept_id
    Where harvest_events_taxa.harvest_event_id = $harvest_event_id    
    and taxon_concepts.supercedure_id=0 and taxon_concepts.vetted_id in(5,0) and taxon_concepts.published=1 ";        
    $result = $mysqli->query($query);        
    //print "<hr>$result->num_rows $query<hr>";
    $all_ids = $result->num_rows;
    
    /* not needed anymore, coz we only need the total count        
    $all_ids=array();
    while($result && $row=$result->fetch_assoc())
    {$all_ids[$row["id"]]=true;}    
    $result->close();                
    $all_ids = array_keys($all_ids);    
    $all_ids = count($all_ids);
    */    
    
    return $all_ids;
}//end get_taxon_concept_ids_from_harvest_event($harvest_event_id)    



function process_do($harvest_event_id,$taxa_count,$published,$agent_name,$agent_id,$ctr)
{
    global $mysqli;
    if($agent_id == 27)//IUCN
    {
        $datatype = array(	
		1 => array(	"label" => "IUCN"	    , "id" => "6")
        );    
    }
    else
    {
        $datatype = array(	
		1 => array(	"label" => "Image"	    , "id" => "1"),
		2 => array(	"label" => "Sound"	    , "id" => "2"),
		3 => array(	"label" => "Text"       , "id" => "3"),
		4 => array(	"label" => "Video"	    , "id" => "4"),
		5 => array(	"label" => "Flash"	    , "id" => "7"),
        6 => array(	"label" => "YouTube"	, "id" => "8"));    
    
        /* orig
        $datatype = array(	
		1 => array(	"label" => "Image"	    , "id" => "1"),
		2 => array(	"label" => "Sound"	    , "id" => "2"),
		3 => array(	"label" => "Text"       , "id" => "3"),
		4 => array(	"label" => "Video"	    , "id" => "4"),
		5 => array(	"label" => "GBIF Image"	, "id" => "5"),
		6 => array(	"label" => "IUCN"	    , "id" => "6"),
		7 => array(	"label" => "Flash"	    , "id" => "7"),
        8 => array(	"label" => "YouTube"	, "id" => "8"));    
        */
    }

    //start initialize
        /*
        $data_type = array(
        1 => "Image"      , 
        2 => "Sound"      , 
        3 => "Text"       , 
        4 => "Video"      , 
        5 => "GBIF Image" , 
        6 => "IUCN"       ,         
        7 => "Flash"      , 
        8 => "YouTube"    ); */

        $vetted_type = array( 
        1 => array( "id" => "0"   , "label" => "Unknown"),
        2 => array( "id" => "4"   , "label" => "Untrusted"),
        3 => array( "id" => "5"   , "label" => "Trusted")
        );                    
        
        //for ($i = 1; $i <= count($data_type); $i++) //Sep24
        for ($i = 1; $i <= count($datatype); $i++) 
        {
            for ($j = 1; $j <= count($vetted_type); $j++) 
            {
                $str1 = $vetted_type[$j]['id'];
                //$str2 = $i;   //Sep24
                $str2 = $datatype[$i]["id"];
                $do[$str1][$str2] = array();        
            }
        }           
    //end initialize
    
    $qry="Select 
    data_objects.id,
    data_objects.data_type_id,
    data_objects.vetted_id    
    From data_objects_harvest_events
    Inner Join data_objects ON data_objects_harvest_events.data_object_id = data_objects.id
    Where data_objects_harvest_events.harvest_event_id = $harvest_event_id 
    ";

    $result = $mysqli->query($qry);    
    while($result && $row=$result->fetch_assoc())	    
    {
        $id             = $row["id"];    
        $data_type_id   = $row["data_type_id"];
        $vetted_id      = $row["vetted_id"];            
        $do[$vetted_id][$data_type_id][$id] = true;            
    }

    $param = array();
    //for ($i = 1; $i <= count($data_type); $i++) //Sep24
    for ($i = 1; $i <= count($datatype); $i++)
    {
        for ($j = 1; $j <= count($vetted_type); $j++) 
        {
            $str1 = $vetted_type[$j]['id'];
            //$str2 = $i; //Sep24
            $str2 = $datatype[$i]["id"];
            $param[] = count($do[$str1][$str2]);
        }
    }    

    //print "<br>";        
    $arr=$param;    
    //for ($j = 1; $j <= count($data_type); $j++) //Sep24
    for ($j = 1; $j <= count($datatype); $j++)
    {
        $sum[$j]=0;
    }

    if ($ctr % 2 == 0)  {$color = '';}
    else                {$color = 'aqua';}        
    
    print"
    <table bgcolor='$color' cellpadding='3' cellspacing='0' border='1' style='font-size : x-small; font-family : Arial Narrow;'>        
    <tr><td colspan='24'>
        <table>
            <tr><td>
                Agent: <a target='eol' href='http://www.eol.org/administrator/content_partner_report/show/$agent_id'>$agent_name</a>                
                &nbsp;&nbsp;&nbsp;
                <font size='2'>" . iif($published,"Published: $published","-not yet published-") . " &nbsp;&nbsp;&nbsp; Harvest event id: $harvest_event_id</font>
            </td></tr>
        </table>
    </td></tr>    
    <tr align='center'>";
    //for ($i = 1; $i <= count($data_type); $i++) //Sep24
    for ($i = 1; $i <= count($datatype); $i++)
    {
        //print"<td colspan='3'>" . $data_type[$i] . "</td>"; Sep24
        print"<td colspan='3'>" . $datatype[$i]["label"] . "</td>";
    }      
    print"</tr>";    
    print"
    <tr align='center'>";
    $k=0;
    //for ($j = 1; $j <= count($data_type); $j++) //Sep24
    for ($j = 1; $j <= count($datatype); $j++)
    {
        for ($i = 1; $i <= count($vetted_type); $i++) 
        {
            print"<td>" . $vetted_type[$i]['label'] . "</td>";
            //$sum[$j] = $sum[$j] + $arr[$k]; //Sep24
            $index = $datatype[$j]["id"];            
            @$sum[$index] = @$sum[$index] + $arr[$k]; 
            $k++;
        }      
    }  
    print"</tr>";
    print"
    <tr align='center'>";
        for ($i = 0; $i < count($arr); $i++) 
        {
            print"<Td align='right'>" . $arr[$i] . "</td>";
        }
    print"</tr>";
    print"
    <tr align='center'>";
    $k=0;
    //for ($j = 1; $j <= count($data_type); $j++) //Sep24
    for ($j = 1; $j <= count($datatype); $j++) 
    {
        //print"<td colspan='3' align='right'>" . number_format($sum[$j]) . "</td>"; //Sep24
        print"<td colspan='3' align='right'>" . number_format($sum[$datatype[$j]["id"]]) . "</td>";            
    }  
    print"</tr>";
    print"    
    <tr><td colspan='24'>
        <table>        
        <tr><td>Taxa count: </td><td align='right'>" . number_format($taxa_count,0) . "</td></tr>        
        <tr><td>Data objects: </td><td align='right'>" . number_format(array_sum($sum)) . "</td></tr>
        </table>
    </td></tr>    
    </table>";
    return "";    
}

function display_form()
{
    global $mysqli;
    global $with_published_content;
    
    print"<table border='1' cellpadding='5' cellspacing='0'><form name='fn' action='index.php' method='get'>";
    $qry = "Select distinct agents.full_name AS agent_name, agents.id AS agent_id 
    From agents
    Inner Join agents_resources ON agents.id = agents_resources.agent_id
    Inner Join content_partners ON agents_resources.agent_id = content_partners.agent_id
    Inner Join resources ON agents_resources.resource_id = resources.id
    Inner Join harvest_events ON resources.id = harvest_events.resource_id ";
    if($with_published_content == 'on')$qry .= " where harvest_events.published_at is not null ";
    $qry .= " Order By agents.full_name Asc ";
    $result = $mysqli->query($qry);    
    /*
    resource_status_id not in (1,6,7,9)
    1	Uploading	
    2	Uploaded	
    3	Upload Failed	
    4	Moved to Content Server	
    5	Validated	
    6	Validation Failed	
    7	Being Processed	
    8	Processed	
    9	Processing Failed	
    10	Published	
    11	Publish Pending	
    12	Unpublish Pending	
    13	Force Harvest	
    */

    $checked='';
    if($with_published_content == 'on')$checked='checked';    

    print"<td><font size='2'><i>Content partner [Agent ID]</i> &nbsp;&nbsp;&nbsp; n=" . $result->num_rows . "</font><br>
    With published data only: <input type='checkbox' name='with_published_content' $checked > <input type='button' value='Refresh list' onclick='proc()'>
    <br>
    <select id='agent_id' name=agent_id style='font-size : small; font-family : Arial; background-color : Aqua;'><option>";
    while($result && $row=$result->fetch_assoc())
    {
        print"<option value=$row[agent_id]>$row[agent_name] [$row[agent_id]]";    
    }
    print"</select></td>";
    
    
    ?>
    
    
    <?php
    
    print"
    <tr>
        <td>            
            <input type='submit' value='Taxa & Data object Stats &gt;&gt; '> 
        </td>
    </tr>
    </form>
    <tr>
    <td><font size='2'>Access report using URL and Agent ID:<br>
    <i><a href='http://services.eol.org/eol_php_code/applications/partner_stat/index.php?agent_id=2'>
    http://services.eol.org/eol_php_code/applications/partner_stat/index.php?agent_id=2</a></i></font>
    </td>
    </tr>
    </table>";
}//end display_form();
function get_val_var($v)
{
    if     (isset($_GET["$v"])){$var=$_GET["$v"];}
    elseif (isset($_POST["$v"])){$var=$_POST["$v"];}
    else   return NULL;                            
    return $var;    
}
function iif($expression,$true,$false)
{
    if($expression) return $true;
    else            return $false;
}
?>

</body>
</html>