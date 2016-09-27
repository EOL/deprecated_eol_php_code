<?php class RSS
{    
    public function GetFeed($e,$id,$f_list)
    {
        return $this->getDetails($e,$id,$f_list) . $this->getItems($e,$id);
    }        
    
    function feed_about($e,$id)
    {
        if($e==1){    //last 20 recently harvested
            $qry="Select resources.harvested_at as description, agents.full_name as title, agents.id ,
            concat('http://www.eol.org/administrator/content_partner_report/show/',agents.id) as link
            From resources Inner Join agents_resources ON resources.id = agents_resources.resource_id Inner Join agents ON agents_resources.agent_id = agents.id
            Order By resources.harvested_at Desc limit 50 ";        
            }
        elseif($e==6 or $e==7){    //next harvests
            $qry="Select DATE_ADD(harvested_at, INTERVAL refresh_period_hours HOUR) as next_harvest, 
            harvested_at as last_harvest, agents.full_name as title, agents.id as agent_id ,
            concat('http://www.eol.org/administrator/content_partner_report/show/',agents.id) as link ,
            refresh_period_hours
            From resources Inner Join agents_resources ON resources.id = agents_resources.resource_id Inner Join agents ON agents_resources.agent_id = agents.id";        
            $qry .= " where harvested_at is not null and harvested_at <> '0000-00-00 00:00:00' ";
            $qry .= " Order By DATE_ADD(harvested_at, INTERVAL refresh_period_hours HOUR) desc ";
            }            
        elseif($e==2){    //last 20 recently published*            
            $qry="Select
            if(agents.full_name = resources.title,agents.full_name,concat(agents.full_name,' - ', resources.title)) as title,        
            harvest_events.published_at as description ,            
            concat('http://www.eol.org/administrator/content_partner_report/show/',agents.id) as link        
            From harvest_events Inner Join resources ON harvest_events.resource_id = resources.id
            Inner Join agents_resources ON resources.id = agents_resources.resource_id Inner Join agents ON agents_resources.agent_id = agents.id
            Order By harvest_events.published_at Desc limit 50 ";
                     }
        elseif($e==3){    // harvested, awaiting publication*            
            $qry="Select
            if(agents.full_name = resources.title,agents.full_name,concat(agents.full_name,' - ', resources.title)) as title,
            harvest_events.completed_at as description ,
            concat('http://www.eol.org/administrator/content_partner_report/show/',agents.id) as link
            From harvest_events Inner Join resources ON harvest_events.resource_id = resources.id
            Inner Join agents_resources ON resources.id = agents_resources.resource_id Inner Join agents ON agents_resources.agent_id = agents.id
            where harvest_events.published_at is null 
            and harvest_events.id in (Select Max(harvest_events.id) From harvest_events Group By harvest_events.resource_id)
            Order By harvest_events.completed_at Desc";
            }
        elseif($e==4){    //  resources with errors during harvest*
            $qry="Select distinct
            if(agents.full_name = resources.title,agents.full_name,concat(agents.full_name,' - ', resources.title)) as title,
            trim(concat(if(resources.harvested_at is null,'',concat('Harvested at: ', resources.harvested_at,'<br>')) , 'Comment: ' , resources.notes)) as description ,
            concat('http://www.eol.org/administrator/content_partner_report/show/',agents.id) as link
            From harvest_events 
            right Join resources ON harvest_events.resource_id = resources.id
            left Join agents_resources ON resources.id = agents_resources.resource_id 
            left Join agents ON agents_resources.agent_id = agents.id
            where MID(resources.notes,1,3) = '<b>' Order By resources.harvested_at Desc";                     
            }

        elseif($e >= 8 and $e <= 13)    //*            
        {
            if    ($e==8)$id=3;     //  resources: upload failed
            elseif($e==9)$id=6;     //  resources: Validation Failed
            elseif($e==10)$id=9;    //  resources: Processing Failed
            elseif($e==11)$id=11;   //  resources: Publish Pending
            elseif($e==12)$id=12;   //  resources: Unpublish Pending
            elseif($e==13)$id=13;   //  resources: Harvest Requested
            $qry="Select distinct 
            concat(if(agents.full_name = resources.title,agents.full_name,concat(agents.full_name,' - ', resources.title)),' {',agent_statuses.label,'}') as title, 
            trim(concat(if(resources.harvested_at is null,'',concat('Harvested at: ', resources.harvested_at,'<br>')) , 'Comment: ' , resources.notes)) as description ,             
            concat('http://www.eol.org/administrator/content_partner_report/show/',agents.id) as link
            From harvest_events 
            right Join resources ON harvest_events.resource_id = resources.id 
            left Join agents_resources ON resources.id = agents_resources.resource_id 
            left Join agents ON agents_resources.agent_id = agents.id 
            left join resource_statuses ON resources.resource_status_id = resource_statuses.id 
            left Join agent_statuses ON agents.agent_status_id = agent_statuses.id 
            where resource_statuses.id = $id 
            Order By resources.harvested_at Desc ";            
        }
                     
        elseif($e==5){    //  individual resource info*            
            $qry="Select distinct
            if(agents.full_name = resources.title,agents.full_name,concat(agents.full_name,' - ', resources.title)) as title ,
            trim(concat(if(resources.harvested_at is null,'',concat('Harvested at: ', resources.harvested_at,'<br>')) , 'Comment: ' , resources.notes)) as description ,            
            concat('http://www.eol.org/administrator/content_partner_report/show/',agents.id) as link ,
            harvest_events.began_at, 
            harvest_events.completed_at, 
            harvest_events.published_at,
            agents.id as agent_id
            From harvest_events 
            left Join resources ON harvest_events.resource_id = resources.id
            left Join agents_resources ON resources.id = agents_resources.resource_id 
            left Join agents ON agents_resources.agent_id = agents.id
            where resources.id = $id Order By harvest_events.began_at desc limit 20";
            }                     
        return $qry;
    }
    
    function feed_title($e,$id)
    {
        require("feeds.php");                
        return $feeds[$e]["title"];
    }    
    
    private function getDetails($e,$id,$f_list)
    {
        $title = $this->feed_title($e,$id);
            $details = "<?xml version='1.0' encoding='ISO-8859-1' ?>
                    <rss version='2.0'>
                        <channel>
                            <title>" . $title . "</title>
                            <link></link>
                            <description></description>
                            <language></language>
                            <image>
                                <title></title>
                                <url></url>
                                <link></link>
                                <width></width>
                                <height></height>
                            </image>";

        //start list of links                
        require("feeds.php");
        $links='See other feeds: ';

        $arr = explode(",",$f_list);        
        for ($i = 1; $i <= count($feeds) ; $i++) 
        {
            if (in_array($i, $arr))            
            {
                $links .= " " . "<a href='http://$domain/$feed_path?f=" . $feeds[$i]["feed"]."&f_list=$f_list'>" . $feeds[$i]["title"] . "</a> &nbsp;|&nbsp; ";
            }
        }        
        
        if($links == 'See other feeds: ')$links='';
        else
        {
            $links = trim($links);
            $links = substr($links,0,strlen($links)-7);
            $links .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
        }
        $links .= "<a href='http://services.eol.org/RSS_resource_monitor/'>List of other available RSS feeds&gt;&gt;</a>";
        $links .= "<hr>";                
        $links = "<small>$links</small>";        
        $details .= '<item>
                     <title></title>
                     <link></link>
                     <description><![CDATA['. $links .']]></description>
                     </item>';                            
        //end list of links        
        return $details;
    }    

    private function getItems($e,$id)
    {    
        require_once(dirname(__FILE__) ."/../../config/environment.php");
        $mysqli =& $GLOBALS['mysqli_connection'];
        $conn = $mysqli;                
        $query = $this->feed_about($e,$id);
        $result = $conn->query($query);               
        if($e == 5)
        {
            $row        = $result->fetch_row();            
            $agent_id   = $row[6];
            $result = $conn->query($query); //so pointer goes back to first record of the recordset
        }
    
        $items = '';            
        if($e < 5 or ($e >= 6 and $e <= 13))
        {
            if        ($e == 7 ){require('next_harvest.php');}
            elseif    ($e == 6 ){require('next_harvest_multiple.php');}
            else
            {
                while($result && $row=$result->fetch_assoc())
                {
                    if(in_array($e, array('11','12','13')))$row_desc="";
                    else                                   $row_desc=$row["description"];
                    
                    $items .= '<item>
                                 <title>' . $row["title"] .'</title>
                                 <link>'. $row["link"] .'</link>
                                 <description><![CDATA['. $row_desc .']]></description>
                             </item>';
                }
            }
        }
        else //for individual resource info
        {
            //############################################################################
            $i=0;        
            $tmp="
            <a href='http://services.eol.org/eol_php_code/applications/partner_stat/index.php?agent_id=$agent_id'>See stats</a>            
            <table border='1' cellpadding='2' cellspacing='0'>            
            <tr align='center'>
                <td colspan='2'>Harvest</td>
                <td rowspan='2'>Published</td>
            </tr>
            <tr align='center'>
                <td>Start</td>
                <td>Finish</td>
            </tr>";            
            while($row = $result->fetch_assoc())	
            {
                $i++;
                if($i==1)
                {    
                    $items .= '<item>
                    <title>'. $row["title"] .'</title>
                    <link>'. 'http://www.eol.org/administrator/content_partner_report/show/' . $row["agent_id"] .'</link>
                    <description><![CDATA['. '' .']]></description>
                    </item>';                    
                    $items .= '<item>
                    <title>' . 'Latest harvest status' .'</title>
                    <link>'. '' .'</link>
                    <description><![CDATA['. $row["description"] .']]></description>
                    </item>';    
                }                        
                
                $tmp .= "
                <tr>
                    <td>$row[began_at]</td>
                    <td>$row[completed_at]</td>
                    <td>$row[published_at]</td>
                </tr>
                ";                
                $row_link = $row["link"];
                
            }//end while
            $tmp .= "</table>";
    
            $items .= '<item>
            <title>' . 'Harvest History' .'</title>';
            if(isset($row_link))$items .= '<link>'. $row_link .'</link>';
            $items .= '
            <description><![CDATA['. $tmp .']]></description>
            </item>';        
            
            //############################################################################    
        }//end for individual resource info        
        $items .= '</channel></rss>';
        return $items;
    }
}//end class RSS
?>