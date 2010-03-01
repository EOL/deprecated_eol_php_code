<?php

define("DEBUG", false);
define("MYSQL_DEBUG", false);

require_once("../../config/start.php");
$mysqli =& $GLOBALS['mysqli_connection'];


$stats = get_lifedesk_stat();

print"<hr>";
print"<pre>";print_r($stats);print"</pre>";
exit;


$temp = lifedesk_stat($stats);

function lifedesk_stat($stats)
{       //print"<pre>";print_r($stats);print"</pre>";

        $total_published_taxa=$stats["totals"][0];
        $total_published_do=$stats["totals"][1];
        $total_unpublished_taxa=$stats["totals"][2];
        $total_unpublished_do=$stats["totals"][3];
        
        $total_taxa = $total_published_taxa + $total_unpublished_taxa;
        $total_do = $total_published_do + $total_unpublished_do;
        
        $provider=$stats;
    
        //start display
        $arr = array_keys($provider["published"]);
        print"<p style='font-family : Arial;'>
        These are LifeDesk providers who have registered in the <a target='eol_registry' href='http://www.eol.org/administrator/content_partner_report'>EOL Content Partner Registry</a>.<br>
        </p>
                
        <table cellpadding='3' cellspacing='0' border='1' style='font-size : small; font-family : Arial Narrow;'>
        <tr align='center'><td colspan='3'>LifeDesks</td></tr>
        <tr align='center'>
            <td>Published (n=" . count($arr) . ")</td>
            <td>Taxa pages</td>
            <td>Data objects</td>
        </tr>
        ";
        for ($i = 0; $i < count($arr); $i++) 
        {
            print " <tr>
                        <td>$arr[$i]</td>
                        <td align='right'>" . $provider["published"][$arr[$i]][0] . "</td>
                        <td align='right'>" . $provider["published"][$arr[$i]][1] . "</td>
                    </tr>
                  ";
        }
        print"  <tr align='right'>
                    <td>Total:</td>
                    <td>$total_published_taxa</td>
                    <td>$total_published_do</td>
                </tr>";
        //print"</table>";        
        /////////////////////////////////////////////////////////////////////////////////////////////////
        $arr = array_keys($provider["unpublished"]);
        print"
        <tr align='center'>
            <td>Un-published (n=" . count($arr) . ")</td>
            <td>Taxa pages</td>
            <td>Data objects</td>
        </tr>";
        for ($i = 0; $i < count($arr); $i++) 
        {
            print " <tr>
                        <td>$arr[$i]</td>
                        <td align='right'>" . $provider["unpublished"][$arr[$i]][0] . "</td>
                        <td align='right'>" . $provider["unpublished"][$arr[$i]][1] . "</td>
                    </tr>
                  ";
        }
        print"  <tr align='right'>
                    <td>Total:</td>
                    <td>$total_unpublished_taxa</td>
                    <td>$total_unpublished_do</td>
                </tr>
                <tr align='right' bgcolor='aqua'>
                    <td>Total:</td>
                    <td>$total_taxa</td>
                    <td>$total_do</td>
                </tr>
                ";
        
        /////////////////////////////////////////////////////////////////////////////////////////////////
        /*
        $arr = array_keys($provider["unpublished"]);
        print"
        <tr align='center'>
            <td>Unpublished (n=" . count($arr) . ")</td><td colspan='2'>&nbsp;</td>
        </tr>
        ";
        for ($i = 0; $i < count($arr); $i++) 
        {
            print " <tr>
                        <td>$arr[$i]</td><td colspan='2'>&nbsp;</td>
                    </tr>
                  ";
        }
        */
        print"</table>";        
        print("<font size='2'>{as of " . date('Y-m-d H:i:s') . "} ");
        print" &nbsp;&nbsp;&nbsp; <a href='javascript:self.close()'>Exit</a></font>";
        
        //end display

    print"
    <p style='font-family : Arial;'>
    This is the current list of available LifeDesks. <a href='http://www.lifedesks.org/sites/'>More info</a>
    </p>";
    get_values_fromCSV();

}

function get_values_fromCSV()
{
    //convert to csv    
    $filename="http://admin.lifedesks.org/files/lifedesk_admin/lifedesk_stats/lifedesk_stats.txt";
    //$filename="http://127.0.0.1/lifedesk_stats.txt";
    
    $OUT = fopen("temp.csv", "w+");            
    $str = Functions::get_remote_file($filename);    
    if($str)
    {
        $str = str_ireplace(',', '&#044;', $str);
        $str = str_ireplace(chr(9), ',', $str);
        fwrite($OUT, $str);        
        fclose($OUT);
    }
    $filename = "temp.csv";
    //end convert to csv    
    
    //start reads csv    
    $row = 0;
    if(!($handle = fopen($filename, "r")))return;
    
    $label=array();
    $arr = array();
    
    print"<table cellpadding='3' cellspacing='0' border='1' style='font-size : small; font-family : Arial Narrow;'>";
    while (($data = fgetcsv($handle)) !== FALSE) 
    {
        if($row == 0) //to get first row, first cell
        {
            print $data[0];    
        }
               
        print"<tr>";                
        //if($row > -1)   //not to bypass first row
        if($row > 0) //to bypass first row, which is the row for the labels
        {                
            $num = count($data);
            //print $num;
            //echo "<p> $num fields in line $row: <br /></p>\n";        
            $num = $row-1;
            if($row == 1)   print"<td align='center'>#</td>";            
            else            print"<td align='right'>" . $num . "</td>";
            //for ($c=0; $c < $num; $c++) 
            for ($c=0; $c < 10; $c++) 
            {        
                $align='center';
                if($row > 0)if(in_array($c, array(3,6,7,8,9)))$align='right';                
                
                print"<td align='$align'>";
                if($c == 1 and $row > 1) print"<a href='$data[$c]'>$data[$c]</a>";
                else                     print $data[$c];
                print"</td>";                
            }                        
            //if($row == 10)break;    
        }
        $row++;
        print"</tr>";
    }//end while
    print"</table>";
    
    return "";

}//end function


//####################################################

    //function lifedesk_stat()
    function get_lifedesk_stat()
    {   
        global $mysqli;
        
        $latest_published = array();
        $result = $mysqli->query("SELECT resource_id, max(id) max_published FROM harvest_events 
        WHERE published_at IS NOT NULL GROUP BY resource_id");
        while($result && $row=$result->fetch_assoc())
        {$latest_published[$row['resource_id']] = $row['max_published'];}
         
        /* query to get all latest harvest_events for all LifeDesk providers */
        $query = "Select Max(harvest_events.id) AS harvest_event_id,
        harvest_events.resource_id,
        resources.title,
        agents_resources.agent_id
        From resources
        Inner Join harvest_events ON resources.id = harvest_events.resource_id
        Inner Join resource_statuses ON resources.resource_status_id = resource_statuses.id
        Inner Join agents_resources ON resources.id = agents_resources.resource_id
        Where resources.accesspoint_url Like '%lifedesks.org%'
        Group By harvest_events.resource_id ";        
        $result = $mysqli->query($query);                
  
        $total_published_taxa=0;
        $total_published_do=0;
        
        $total_unpublished_taxa=0;
        $total_unpublished_do=0;        

        $provider=array();        
        
        while($result && $row=$result->fetch_assoc())        
        {
            $title = "<a target='resource' href='http://www.eol.org/content_partner/resources/$row[resource_id]/harvest_events?content_partner_id=$row[agent_id]'>$row[title]</a>";
            $provider["$title"]=true;        
            if(@$latest_published[$row['resource_id']]) $harvest_event_id = $latest_published[$row['resource_id']];
            else                                        $harvest_event_id = $row["harvest_event_id"];                                

            //$arr = $this->get_taxon_concept_ids_from_harvest_event($harvest_event_id);      
            $arr = get_taxon_concept_ids_from_harvest_event($harvest_event_id);      
            
            /*not used
            $published_taxa = count(@$arr["published"]);
            $unpublished_taxa = count(@$arr["unpublished"]);
            */
            $all_taxa = count(@$arr["all"]);                                    

            //$arr = $this->get_data_object_ids_from_harvest_event($harvest_event_id);
            $arr = get_data_object_ids_from_harvest_event($harvest_event_id);
            $published_do = count(@$arr["published"]);
            $unpublished_do = count(@$arr["unpublished"]);            
            $all_do = count(@$arr["all"]);

            if($published_do > 0)
            {                   
                $total_published_taxa += $all_taxa;
                $total_published_do += $published_do;                                
                
                $provider["published"]["$title"][0]=$all_taxa;
                $provider["published"]["$title"][1]=$published_do;
            }            

            if($unpublished_do > 0)
            {   
                $total_unpublished_taxa += $all_taxa;
                $total_unpublished_do += $unpublished_do;                                

                $provider["unpublished"]["$title"][0]=$all_taxa;
                $provider["unpublished"]["$title"][1]=$unpublished_do;
            }                
        }                
        $provider["totals"][0]=$total_published_taxa;
        $provider["totals"][1]=$total_published_do;                
        $provider["totals"][2]=$total_unpublished_taxa;
        $provider["totals"][3]=$total_unpublished_do;                

        //return $provider;        

        $return[0]=$provider;
        $return[1]="lifedesk_stat";
        return $return;
        
    }//end function 
    
    function get_taxon_concept_ids_from_harvest_event($harvest_event_id)
    {   
        global $mysqli;
        
        $query = "
        SELECT DISTINCT he.taxon_concept_id as id , 0 as published
        FROM harvest_events_taxa het 
        JOIN taxa t ON (het.taxon_id=t.id) 
        JOIN hierarchy_entries he ON (t.hierarchy_entry_id=he.id) 
        WHERE het.harvest_event_id = $harvest_event_id ";

        $result = $mysqli->query($query);                

        $all_ids=array();
        $all_ids["published"]=array();
        $all_ids["unpublished"]=array();
        while($result && $row=$result->fetch_assoc())
        {
            if($row["published"])$all_ids["published"][]=$row["id"];
            else                 $all_ids["unpublished"][]=$row["id"];

            $all_ids["all"][]=$row["id"];
        }
        $result->close();            
        return $all_ids;

        /*
        $query = "Select distinct hierarchy_entries.taxon_concept_id as id
        From harvest_events_taxa
        Inner Join taxa ON harvest_events_taxa.taxon_id = taxa.id
        Inner Join hierarchy_entries ON taxa.name_id = hierarchy_entries.name_id        
        Where harvest_events_taxa.harvest_event_id = $harvest_event_id        
        ";    
        
        //
        //, taxon_concepts.published
        //Inner Join taxon_concepts ON taxon_concepts.id = hierarchy_entries.taxon_concept_id
        //and taxon_concepts.vetted_id = " . Vetted::find("trusted") . " and taxon_concepts.supercedure_id=0
        //
        
        //and taxon_concepts.published=1 
        //hpogymnia needs in(5,0)
        //odonata needs in(5)
        $result = $this->mysqli->query($query);                
        
        $all_ids=array();
        $all_ids["published"]=array();
        $all_ids["unpublished"]=array();
        while($result && $row=$result->fetch_assoc())
        {
            //
            if($row["published"])$all_ids["published"][]=$row["id"];
            else                 $all_ids["unpublished"][]=$row["id"];
            //
            $all_ids["all"][]=$row["id"];
        }
        $result->close();            
        //$all_ids = array_keys($all_ids);
        return $all_ids;
        */
    }//end get_taxon_concept_ids_from_harvest_event($harvest_event_id)    

    function get_data_object_ids_from_harvest_event($harvest_event_id)
    {  
        global $mysqli; 
        $query = "Select distinct data_objects_harvest_events.data_object_id as id, data_objects.published
        From data_objects_harvest_events
        Inner Join data_objects ON data_objects_harvest_events.data_object_id = data_objects.id
        Where data_objects_harvest_events.harvest_event_id = $harvest_event_id ";    
        //AND data_objects.published = '1' 
        $result = $mysqli->query($query);                

        $all_ids=array();
        while($result && $row=$result->fetch_assoc())
        {
            if($row["published"])$all_ids["published"][]=$row["id"];
            else                 $all_ids["unpublished"][]=$row["id"];
            
            $all_ids["all"][]=$row["id"];
        }
        $result->close();            
        //$all_ids = array_keys($all_ids);
        return $all_ids;
    }//end get_data_object_ids_from_harvest_event($harvest_event_id)    


?>
