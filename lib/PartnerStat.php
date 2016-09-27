<?php
namespace php_active_record;

class PartnerStat
{
    public function __construct()
    {
        $this->mysqli =& $GLOBALS['mysqli_connection'];
    }
    
    function process_agent_id($agent_id)
    {
        $qry = "Select harvest_events.id, harvest_events.published_at ,
        resources.title resource_title From agents_resources 
        Inner Join harvest_events ON agents_resources.resource_id = harvest_events.resource_id 
        Inner Join resources ON agents_resources.resource_id = resources.id
        Where agents_resources.agent_id = $agent_id 
        order by harvest_events.id desc limit 5 ";
        $result = $this->mysqli->query($qry);    
        $ctr=0;    
        while($result && $row=$result->fetch_assoc())	    
        {
            $ctr++;
            $query = "SELECT a.full_name FROM agents a WHERE a.id = $agent_id";                            		
            $result2 = $this->mysqli->query($query);    
            $row2 = $result2->fetch_row();            
            $agent_name = $row2[0];        
            $taxa_count = self::get_taxon_concept_ids_from_harvest_event($row["id"]);        
            $data_object_stats = self::process_do($row["id"],$taxa_count,$row["published_at"],$agent_name,$agent_id,$ctr,$row["resource_title"]);        		
        }
    }
    
    function get_taxon_concept_ids_from_harvest_event($harvest_event_id)
    {   
        $query = "Select count(distinct he.taxon_concept_id) total_ids
        From harvest_events_hierarchy_entries hehe
        Inner Join hierarchy_entries he ON (hehe.hierarchy_entry_id = he.id)
        Inner Join taxon_concepts tc ON (tc.id = he.taxon_concept_id)
        Where hehe.harvest_event_id = $harvest_event_id    
        and tc.supercedure_id=0 and tc.vetted_id <> " . Vetted::find("untrusted") . " and tc.published=1 ";        
        $result = $this->mysqli->query($query);        
        $row = $result->fetch_row();            
        $all_ids = $row[0];    
        return $all_ids;
    }
    
    function process_do($harvest_event_id,$taxa_count,$published,$agent_name,$agent_id,$ctr,$resource_title)
    {
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
        }    
        //start initialize                            
        $vetted_type = array( 
        1 => array( "id" => Vetted::find("unknown")   , "label" => "Unknown"),
        2 => array( "id" => Vetted::find("untrusted") , "label" => "Untrusted"),
        3 => array( "id" => Vetted::find("trusted")   , "label" => "Trusted"));                
        for ($i = 1; $i <= count($datatype); $i++) 
        {
            for ($j = 1; $j <= count($vetted_type); $j++) 
            {
                $str1 = $vetted_type[$j]['id'];
                $str2 = $datatype[$i]["id"];
                $do[$str1][$str2] = array();        
            }
        }           
        //end initialize        
        $qry="Select data_objects.id, data_objects.data_type_id, data_objects.vetted_id From data_objects_harvest_events Inner Join data_objects ON data_objects_harvest_events.data_object_id = data_objects.id Where data_objects_harvest_events.harvest_event_id = $harvest_event_id";    
        $result = $this->mysqli->query($qry);    
        while($result && $row=$result->fetch_assoc())	    
        {
            $id             = $row["id"];    
            $data_type_id   = $row["data_type_id"];
            $vetted_id      = $row["vetted_id"];            
            $do[$vetted_id][$data_type_id][$id] = true;            
        }    
        $param = array();
        for ($i = 1; $i <= count($datatype); $i++)
        {
            for ($j = 1; $j <= count($vetted_type); $j++) 
            {
                $str1 = $vetted_type[$j]['id'];
                $str2 = $datatype[$i]["id"];
                $param[] = count($do[$str1][$str2]);
            }
        }        
        $arr=$param;    
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
                    &nbsp; [$resource_title] &nbsp;&nbsp;&nbsp;
                    <font size='2'>" . self::iif($published,"Published: $published","-not yet published-") . " &nbsp;&nbsp;&nbsp; Harvest event id: $harvest_event_id</font>
                </td></tr>
            </table>
        </td></tr>    
        <tr align='center'>";
        for ($i = 1; $i <= count($datatype); $i++)
        {
            print"<td colspan='3'>" . $datatype[$i]["label"] . "</td>";
        }      
        print"</tr>";    
        print"
        <tr align='center'>";
        $k=0;
        for ($j = 1; $j <= count($datatype); $j++)
        {
            for ($i = 1; $i <= count($vetted_type); $i++) 
            {
                print"<td>" . $vetted_type[$i]['label'] . "</td>";
                $index = $datatype[$j]["id"];            
                @$sum[$index] = @$sum[$index] + $arr[$k]; 
                $k++;
            }      
        }  
        print"</tr>";
        print"
        <tr align='center'>";
            for ($i = 0; $i < count($arr); $i++) 
            {print"<Td align='right'>" . $arr[$i] . "</td>";}
        print"</tr>";
        print"
        <tr align='center'>";
        $k=0;
        for ($j = 1; $j <= count($datatype); $j++) 
        {print"<td colspan='3' align='right'>" . number_format($sum[$datatype[$j]["id"]]) . "</td>";}  
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
    
    function display_form($with_published_content)
    {        
        print"<table border='1' cellpadding='5' cellspacing='0'>
        <tr><td align='center'><b>Content Partner 'Resource-level' Harvest Stats</b></td></tr>
        <form name='fn' action='index.php' method='get'>";
        $qry = "Select distinct agents.full_name AS agent_name, agents.id AS agent_id 
        From agents
        Inner Join agents_resources ON agents.id = agents_resources.agent_id
        Inner Join content_partners ON agents_resources.agent_id = content_partners.agent_id
        Inner Join resources ON agents_resources.resource_id = resources.id
        Inner Join harvest_events ON resources.id = harvest_events.resource_id ";
        if($with_published_content == 'on')$qry .= " where harvest_events.published_at is not null ";
        $qry .= " Order By agents.full_name Asc ";
        $result = $this->mysqli->query($qry);    
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
        13	Harvest Requested	
        */    
        $checked='';
        if($with_published_content == 'on')$checked='checked';        
        print"<td align='center'>
        <font size='2'>With published data only:</font> <input type='checkbox' name='with_published_content' $checked > <input type='button' value='Refresh list' onclick='javascript:proc()'><br>
        <select id='agent_id' name=agent_id style='font-size : small; font-family : Arial; background-color : Aqua;'><option>";
        while($result && $row=$result->fetch_assoc())
        {print"<option value=$row[agent_id]>$row[agent_name] [$row[agent_id]]";}
        print"</select>
        <br><font size='2'><i>Content partner [Agent ID]</i> &nbsp;&nbsp;&nbsp; n=" . $result->num_rows . "</font></td>
        <tr><td align='center'><input type='submit' value='Taxa & Data object Stats &gt;&gt; '></td></tr>
        </form><tr>
        <td><font size='2'>Access report using URL and Agent ID:<br>
        <i><a href='http://services.eol.org/eol_php_code/applications/partner_stat/index.php?agent_id=97702'>
        http://services.eol.org/eol_php_code/applications/partner_stat/index.php?agent_id=97702</a></i>
        <br>&nbsp;<br><a href='javascript:self.close()'>Exit</a></font>
        </td></tr></table>";
    }
    
    function iif($expression,$true,$false)
    {
        if($expression) return $true;
        else            return $false;
    }    
}        
?>