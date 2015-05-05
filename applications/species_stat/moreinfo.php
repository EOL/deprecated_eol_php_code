<?php

require_once(dirname(__FILE__) ."/../../config/environment.php");
$mysqli =& $GLOBALS['mysqli_connection'];


if($on = $_GET["on"])
{
    if($on == "lifedesk") $stats = get_lifedesk_stat();
    elseif($on == "dataobjects") $stats = dataobject_stat_more();
    $arr = $stats;
    if($arr[1] == "data_objects_more_stat") published_data_objects($arr[0]);
    elseif($arr[1] == "lifedesk_stat") lifedesk_stat($arr[0]);
else
{
    echo "Wrong entry.";
}

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
        if(@$provider["published"]) $arr = array_keys(@$provider["published"]);
        else                        $arr = array();
        
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
        if(@$provider["unpublished"])$arr = array_keys(@$provider["unpublished"]);
        else                         $arr = array();   
        
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
    
    if(!($OUT = fopen("temp.csv", "w+")))
    {
      debug(__CLASS__ .":". __LINE__ .": Couldn't open file: temp.csv");
      return;
    }

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
        FROM harvest_events_hierarchy_entries hehe 
        JOIN hierarchy_entries he ON (hehe.hierarchy_entry_id=he.id) 
        WHERE hehe.harvest_event_id = $harvest_event_id ";

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
        //$result->close();            
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
        //$result->close();            
        //$all_ids = array_keys($all_ids);
        return $all_ids;
    }//end get_data_object_ids_from_harvest_event($harvest_event_id)    


    
//###################################################################
//start with data objects



    function dataobject_stat_more()    //group 1=taxa stat; 2=data object stat
    {           

        set_time_limit(0);
        ini_set('memory_limit','3500M');

        global $mysqli;
        
        $data_type = array(
        1 => "Image"      , 
        2 => "Sound"      , 
        3 => "Text"       , 
        4 => "Video"      , 
        5 => "GBIF Image" , 
        6 => "IUCN"       , 
        7 => "Flash"      , 
        8 => "YouTube"    );
        $vetted_type = array( 
        1 => array( "id" => Vetted::find("unknown")   , "label" => "Unknown"),      
        2 => array( "id" => Vetted::find("untrusted") , "label" => "Untrusted"),    
        3 => array( "id" => Vetted::find("trusted")   , "label" => "Trusted")       
        );                    
        
        
        //initialize
        for ($i = 1; $i <= count($data_type); $i++) 
        {
            for ($j = 1; $j <= count($vetted_type); $j++) 
            {
                $str1 = $vetted_type[$j]['id'];
                $str2 = $i;
                $do[$str1][$str2] = array();        
            }
        }       
        $query = "Select distinct do.id, do.data_type_id, do.vetted_id, dotoc.toc_id AS toc_id, do.visibility_id 
        From (data_objects AS do) Left Join data_objects_table_of_contents AS dotoc ON (do.id = dotoc.data_object_id) 
        Where do.published = 1 "; 
        //$query .= " limit 100,100 "; //debug only

        //print"<hr>$query";
        
        
        $result = $mysqli->query($query);        
        
        
        
        while($result && $row=$result->fetch_assoc())
        {
            $id = $row["id"];    
            $toc_id = $row["toc_id"];                
            $data_type_id   = $row["data_type_id"];
            $vetted_id      = $row["vetted_id"];            
            $do[$vetted_id][$data_type_id][$id] = true;            
        }
        //$result->close();    
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////              
        $param = array();
        for ($i = 1; $i <= count($data_type); $i++) 
        {
            for ($j = 1; $j <= count($vetted_type); $j++) 
            {
                $str1 = $vetted_type[$j]['id'];
                $str2 = $i;
                $param[] = count($do[$str1][$str2]);
            }
        }
        
                
        //start Flickr count        
        $query = "Select Max(harvest_events.id) From harvest_events Where harvest_events.resource_id = '15' Group By harvest_events.resource_id ";
        $result = $mysqli->query($query);
        $row = $result->fetch_row();			
        $latest_event_id = $row[0];                
        //$result->close();                
        if($latest_event_id)        
        {
            $query = "Select Count(data_objects_harvest_events.data_object_id) From data_objects_harvest_events Where data_objects_harvest_events.harvest_event_id = $latest_event_id";
            $result = $mysqli->query($query);
            $row = $result->fetch_row();			
            $param[] = $row[0];                
            //$result->close();        
        }
        else $param[] = '';
        //end Flickr count                      
                    
        //start user submitted do
            // /*
            //$mysqli2 = load_mysql_environment('eol_production');
            $mysqli2 = load_mysql_environment('slave_eol');
            //$query = "Select Count(users_data_objects.id) From users_data_objects";	//all including unpublished
            $query = "select count(udo.id) as 'total_user_text_objects' 
    		from eol_production.users_data_objects as udo join eol_data_production.data_objects as do on do.id=udo.data_object_id WHERE do.published=1;";
        
	    	$result = $mysqli2->query($query);        
            $row = $result->fetch_row();			
            $param[] = $row[0];                
            $result->close();
            //*/
            
            //$param[] = 0;
            
            
        //end user submitted do        
        
        $comma_separated = implode(",", $param);        

        $return[0]=$param;
        $return[1]="data_objects_more_stat";
        return $return;

    }//end function dataobject_stat_more($group)




function published_data_objects($arr)
{
    global $mysqli;
    
	//global $arr;
	
    print"Published Data Objects: <br/>";
    $flickr_count = $arr[24];
    $user_do_count = $arr[25];

    array_pop($arr);    
    array_pop($arr);        
    
    $data_type = array(
    1 => "Image"      , 
    2 => "Sound"      , 
    3 => "Text"       , 
    4 => "Video"      , 
    5 => "GBIF Image" , 
    6 => "IUCN"       , 
    7 => "Flash"      , 
    8 => "YouTube"    
    );
    $vetted_type = array( 
    1 => array( "id" => "0" , "label" => "Unknown"),
    2 => array( "id" => "4" , "label" => "Untrusted"),
    3 => array( "id" => "5" , "label" => "Trusted")
    );                

    for ($j = 1; $j <= count($data_type); $j++) 
    {
        $sum[$j]=0;
    }  

    print"
    <table cellpadding='3' cellspacing='0' border='1' style='font-size : x-small; font-family : Arial Narrow;'>    
    <tr align='center'>";
        for ($i = 1; $i <= count($data_type); $i++) 
        {
            print"<td colspan='3'>" . $data_type[$i] . "</td>";
        }      
    print"</tr>";
    
    print"
    <tr align='center'>";
    $k=0;
    for ($j = 1; $j <= count($data_type); $j++) 
    {
        for ($i = 1; $i <= count($vetted_type); $i++) 
        {
            print"<td>" . $vetted_type[$i]['label'] . "</td>";
            $sum[$j] = $sum[$j] + $arr[$k];
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
    for ($j = 1; $j <= count($data_type); $j++) 
    {
        print"<td colspan='3' align='right'>" . number_format($sum[$j]) . "</td>";            
    }  
    print"</tr>";
    print"    
    </table>       
    <br> Total published data objects = " . number_format(array_sum($sum)) . "    
    <br> Latest Flickr harvest count = " . number_format($flickr_count) . "    
    <br> User-submitted data objects = " . number_format($user_do_count) . "<br>";    
    
    print("<font size='2'>{as of " . date('Y-m-d H:i:s') . "}</font>");
    
    print"<br><font size='2'><br> <a href='javascript:self.close()'>Exit</a></font>";
}//end func
    
    
?>
