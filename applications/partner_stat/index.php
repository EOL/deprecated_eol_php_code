<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head><title>Content Partner Stats</title></head>
<body>
<?php
define("ENVIRONMENT", "slave_32");
define("MYSQL_DEBUG", false);
define("DEBUG", true);
include_once(dirname(__FILE__) . "/../../config/start.php");
$mysqli =& $GLOBALS['mysqli_connection'];


$agent_id = get_val_var("agent_id");

if($agent_id == "") display_form();
else                process_agent_id($agent_id);

function process_agent_id($agent_id)
{
    global $mysqli;
    $qry = "Select harvest_events.id, harvest_events.published_at From agents_resources Inner Join harvest_events ON agents_resources.resource_id = harvest_events.resource_id Where agents_resources.agent_id = $agent_id 
    order by harvest_events.id desc ";
    $result = $mysqli->query($qry);    
    /*
    $qry = "Select Max(harvest_events.id) AS harvest_event_id, Max(harvest_events.resource_id), agents_resources.agent_id From agents_resources Inner Join harvest_events ON agents_resources.resource_id = harvest_events.resource_id Where agents_resources.agent_id = $agent_id Group By agents_resources.agent_id";
    $result = $mysqli->query($qry);    
    $row = $result->fetch_row();            
    $harvest_event_id = $row[0];
    */
    
    //print "agent_id = $agent_id <br>";
    
    $ctr=0;    
    while($result && $row=$result->fetch_assoc())	    
    {
        $ctr++;
        //print "<hr>harvest_event_id = $row[id] $row[published_at] ";
    
        $query = "SELECT DISTINCT a.full_name, he.taxon_concept_id 
        FROM agents a
        JOIN agents_resources ar ON (a.id=ar.agent_id)
        JOIN harvest_events hev ON (ar.resource_id=hev.resource_id)
        JOIN harvest_events_taxa het ON (hev.id=het.harvest_event_id)
        JOIN taxa t ON (het.taxon_id=t.id)
        join hierarchy_entries he on t.hierarchy_entry_id = he.id
        join taxon_concepts tc on he.taxon_concept_id = tc.id
        WHERE a.id = $agent_id and tc.published = 1 and tc.supercedure_id = 0 
        and hev.id = $row[id] ";    
        $result2 = $mysqli->query($query);    
        $row2 = $result2->fetch_row();            
        $agent_name = $row2[0];
        //print $result2->num_rows . "<hr>";
        
        $data_object_stats = process_do($row["id"],$result2->num_rows,$row["published_at"],$agent_name,$agent_id,$ctr);        
        
        
    }//end while
    
    
    
}

function process_do($harvest_event_id,$taxa_count,$published,$agent_name,$agent_id,$ctr)
{
    global $mysqli;


    if($agent_id == 27)
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

    if($ctr==1) $color='aqua';
    else        $color='white';
    print"
    <table bgcolor='$color' cellpadding='3' cellspacing='0' border='1' style='font-size : x-small; font-family : Arial Narrow;'>    
    
    <tr><td colspan='24'>
        <table>
            <tr><td>
                Agent: <a href='http://www.eol.org/administrator/content_partner_report/show/$agent_id'>$agent_name</a>                
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
        <tr><td>Taxa count</td><td align='right'>" . number_format($taxa_count,0) . "</td></tr>        
        <tr><td>Data objects</td><td align='right'>" . number_format(array_sum($sum)) . "</td></tr>
        </table>
    </td></tr>
    
    </table>";
    

    return $param;    

}


function display_form()
{
    global $mysqli;
    print"<table border='1' cellpadding='5' cellspacing='0'><form action='index.php' method='get'>";
    $qry = "Select distinct agents.full_name AS agent_name, agents.id AS agent_id From agents_resources Inner Join agents ON agents_resources.agent_id = agents.id Inner Join resources ON agents_resources.resource_id = resources.id Inner Join content_partners ON agents.id = content_partners.agent_id where resource_status_id not in (1,3,6,7,9) Order By agents.full_name Asc ";
    $result = $mysqli->query($qry);    

    print"<td><font size='2'><i>Content partner</i></font>
    <select id='agent_id' name=agent_id onChange='proc()' style='font-size : small; font-family : Arial; background-color : Aqua;'><option>";
    while($result && $row=$result->fetch_assoc())
    {
        print"<option value=$row[agent_id]>$row[agent_name] [$row[agent_id]]";    
    }
    print"</select> n=" . $result->num_rows . "</td>
    <tr>
    <td><input type='submit'></td>
    </tr>
    </form></table>";
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