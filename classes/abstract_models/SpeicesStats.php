<?php
/* the MODEL */
class SpeciesStats extends MysqlBase
{
    function __construct()
    {
        parent::initialize();
    }    
    public function taxa_stat($taxon_concept_ids,$limit,$group)    //group 1=taxa stat; 2=data object stat
    {   
        echo "\n" . Functions::time_elapsed() . "\n";
        
        //start save
        $stats = new SiteStatistics();
        $temp = $stats->insert_stats();
        //print $stats->total_pages();
        //end save

        //#####################################################################################################
        //start -- delete recs, maintain only 8 days of history
        print"<hr>Maintaining 8 records in history...<br>";
        for ($i = 1; $i <= 2; $i++) 
        {
            if($i==1) $tbl='page_stats_taxa';
            else      $tbl='page_stats_dataobjects';
            
            $query="select id from $tbl order by date_created desc, time_created desc limit 8";
            $result = $this->mysqli->query($query);    //1
            while($result && $row=$result->fetch_assoc()){$first_of_eight = $row['id'];}    
            $query = "delete  from $tbl where id < $first_of_eight";    //print "<hr>$query";
            $update = $this->mysqli->query($query);
            echo "all recs with id < $first_of_eight from $tbl were deleted <br>"; //n=" . $this->mysqli->affected_rows;    
        }        
        //end -- delete recs, maintain only 8 days of history
        //#####################################################################################################        
        
        echo "\n" . Functions::time_elapsed() . "\n";
                
        return "";    //no need to return anymore
    }//end function taxa_stat
    
    public function links_stat($limit,$group)    //group 1=taxa stat; 2=data object stat    //for bhl and outlinks
    {
    }//end links_stat() //limit z


    public function dataobject_stat($taxon_concept_ids, $limit, $group)    //group 1=taxa stat; 2=data object stat
    {
        //start new
        $query=" Select Max(harvest_events.id) as max
        From resources Inner Join harvest_events ON resources.id = harvest_events.resource_id
        Group By resources.id Order By max ";
        //$query .= " limit 100 ";//debug
        
        $result = $this->mysqli->query($query);    
        $temp_arr=array();
        while($result && $row=$result->fetch_assoc())
        {
            $temp_arr[$row["max"]]=1;
        }
        $temp_arr = array_keys($temp_arr);
        $result->close();
        //end new

        //initialize group 1                
        $vetted_unknown_published_visible_uniqueGuid=0;
        $vetted_untrusted_published_visible_uniqueGuid=0;
        $vetted_unknown_published_notVisible_uniqueGuid=0;
        $vetted_untrusted_published_notVisible_uniqueGuid=0;

        //start main body ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        $image_type_id = DataType::insert("http://purl.org/dc/dcmitype/StillImage");
        $text_type_id  = DataType::insert("http://purl.org/dc/dcmitype/Text");
        
        $number_of_data_objects = array();
        $number_of_data_objects['vetted']['image'] = array();
        $number_of_data_objects['vetted']['text'] = array();
        $number_of_data_objects['unvetted']['image'] = array();
        $number_of_data_objects['unvetted']['text'] = array();

        $taxa_with_text = array();
        $taxa_with_images = array();

        //for do stats
        $number_of_data_objects['unknown']['visib1'] = array();
        $number_of_data_objects['unknown']['visib0'] = array();
        $number_of_data_objects['untrusted']['visib1'] = array();
        $number_of_data_objects['untrusted']['visib0'] = array();
        //for do stats

        $query = "
        Select distinct do.id, do.data_type_id, do.vetted_id, dotoc.toc_id AS toc_id, do.visibility_id
        From (data_objects AS do)
        Left Join data_objects_table_of_contents AS dotoc ON (do.id = dotoc.data_object_id)
        Where do.published = 1 ";        
        //$query .= " limit 100 ";//debug
        
        $result = $this->mysqli->query($query);

        $do_count = array();
                    
        while($result && $row=$result->fetch_assoc())
        {
            $id = $row["id"];    
            $toc_id = $row["toc_id"];                
            
            if  (   in_array($row["data_type_id"], array(1,2,3,4,5,6,7,8))  and
                    in_array($row["vetted_id"], array(Vetted::find("unknown"),Vetted::find("untrusted"),Vetted::find("trusted")))  
                )   $do_count[$id] = 1;
            
            if($row["data_type_id"] == $text_type_id && $toc_id ||
               $row["data_type_id"] == $image_type_id)
            {
                //start for do stat    
                if($row["vetted_id"] == Vetted::find("unknown"))    //unknown
                {
                    if        ($row["visibility_id"] == Visibility::find("visible"))   $number_of_data_objects['unknown']['visib1'][$id] = true;
                    elseif    ($row["visibility_id"] == Visibility::find("invisible")) $number_of_data_objects['unknown']['visib0'][$id] = true;
                }
                elseif($row["vetted_id"] == Vetted::find("untrusted"))    //untrusted
                {
                    if        ($row["visibility_id"] == Visibility::find("visible")) $number_of_data_objects['untrusted']['visib1'][$id] = true;
                    elseif    ($row["visibility_id"] == Visibility::find("invisible")) $number_of_data_objects['untrusted']['visib0'][$id] = true;
                }//end for do stat
            }
        }//end while
        $result->close();
        
        $taxa_count     = count(array_keys($do_count));
        
        //all 4 now have array list 
        $vetted_unknown_published_visible_uniqueGuid        = count($number_of_data_objects['unknown']['visib1']);
        $vetted_untrusted_published_visible_uniqueGuid      = count($number_of_data_objects['untrusted']['visib1']);
        $vetted_unknown_published_notVisible_uniqueGuid     = count($number_of_data_objects['unknown']['visib0']);
        $vetted_untrusted_published_notVisible_uniqueGuid   = count($number_of_data_objects['untrusted']['visib0']);
        
        
        //end main body ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        
        //start tc_id list    
        $a_vetted_unknown_published_visible_uniqueGuid      = array_keys($number_of_data_objects['unknown']['visib1']);
        $a_vetted_untrusted_published_visible_uniqueGuid    = array_keys($number_of_data_objects['untrusted']['visib1']);
        $a_vetted_unknown_published_notVisible_uniqueGuid   = array_keys($number_of_data_objects['unknown']['visib0']);
        $a_vetted_untrusted_published_notVisible_uniqueGuid = array_keys($number_of_data_objects['untrusted']['visib0']);
        
        $a_vetted_unknown_published_visible_uniqueGuid      = implode("_",$a_vetted_unknown_published_visible_uniqueGuid);
        $a_vetted_untrusted_published_visible_uniqueGuid    = implode("_",$a_vetted_untrusted_published_visible_uniqueGuid);
        $a_vetted_unknown_published_notVisible_uniqueGuid   = implode("_",$a_vetted_unknown_published_notVisible_uniqueGuid);
        $a_vetted_untrusted_published_notVisible_uniqueGuid = implode("_",$a_vetted_untrusted_published_notVisible_uniqueGuid);
        //end tc_id list
        
        //start user submitted do    ; per Peter M.
        $mysqli2 = load_mysql_environment('slave_eol');
        $query = "select count(udo.id) as 'total_user_text_objects' 
		from eol_production.users_data_objects as udo join eol_data_production.data_objects as do on do.id=udo.data_object_id WHERE do.published=1;";
        $result = $mysqli2->query($query);        
        $row = $result->fetch_row();			
        $user_submitted_text = $row[0];                
        $result->close();
        //end user submitted do                
        
        
        //return -------------------------------------------------------------------------------------    
        if($group==1){}
        elseif($group==2)        
        {
            $date_created = date('Y-m-d');
            $time_created = date('H:i:s');
        
            $qry = " insert into page_stats_dataobjects(        
            taxa_count,
            vetted_unknown_published_visible_uniqueGuid         ,
            vetted_untrusted_published_visible_uniqueGuid         ,
            vetted_unknown_published_notVisible_uniqueGuid         ,
            vetted_untrusted_published_notVisible_uniqueGuid     ,
            date_created,
            time_created,
            active,
            a_vetted_unknown_published_visible_uniqueGuid        ,
            a_vetted_untrusted_published_visible_uniqueGuid     ,
            a_vetted_unknown_published_notVisible_uniqueGuid     ,
            a_vetted_untrusted_published_notVisible_uniqueGuid     ,
            user_submitted_text     
            )    
            select     
            $taxa_count,
            $vetted_unknown_published_visible_uniqueGuid         ,
            $vetted_untrusted_published_visible_uniqueGuid         ,
            $vetted_unknown_published_notVisible_uniqueGuid     ,
            $vetted_untrusted_published_notVisible_uniqueGuid     ,
            '$date_created',
            '$time_created',
            'n',
            '$a_vetted_unknown_published_visible_uniqueGuid'        ,
            '$a_vetted_untrusted_published_visible_uniqueGuid'         ,
            '$a_vetted_unknown_published_notVisible_uniqueGuid'     ,
            '$a_vetted_untrusted_published_notVisible_uniqueGuid'   ,
            $user_submitted_text                       
            ";            
            $update = $this->mysqli->query($qry);    //1
            //end save
    
            return "";
        }
    }//end function dataobject_stat

    public function dataobject_stat_more($group)    //group 1=taxa stat; 2=data object stat
    {        
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
        $result = $this->mysqli->query($query);        
        while($result && $row=$result->fetch_assoc())
        {
            $id = $row["id"];    
            $toc_id = $row["toc_id"];                
            $data_type_id   = $row["data_type_id"];
            $vetted_id      = $row["vetted_id"];            
            $do[$vetted_id][$data_type_id][$id] = true;            
        }
        $result->close();    
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
        //print sizeof($param); exit;
                
        //start Flickr count        
        $query = "Select Max(harvest_events.id) From harvest_events Where harvest_events.resource_id = '15' Group By harvest_events.resource_id ";
        $result = $this->mysqli->query($query);
        $row = $result->fetch_row();			
        $latest_event_id = $row[0];                
        $result->close();                
        if($latest_event_id)        
        {
            $query = "Select Count(data_objects_harvest_events.data_object_id) From data_objects_harvest_events Where data_objects_harvest_events.harvest_event_id = $latest_event_id";
            $result = $this->mysqli->query($query);
            $row = $result->fetch_row();			
            $param[] = $row[0];                
            $result->close();        
        }
        else $param[] = '';
        //end Flickr count                      
        
        //start user submitted do
        //$mysqli2 = load_mysql_environment('eol_production');
        $mysqli2 = load_mysql_environment('slave_eol');
        //$query = "Select Count(users_data_objects.id) From users_data_objects";	//all including unpublished
        $query = "select count(udo.id) as 'total_user_text_objects' 
		from eol_production.users_data_objects as udo join eol_data_production.data_objects as do on do.id=udo.data_object_id WHERE do.published=1;";
        
		$result = $mysqli2->query($query);        
        $row = $result->fetch_row();			
        $param[] = $row[0];                
        $result->close();
        //end user submitted do        
        
        $comma_separated = implode(",", $param);        
                
        return "$comma_separated";        

    }//end function dataobject_stat_more($group)


    public function lifedesk_stat()
    {   
        $latest_published = array();
        $result = $this->mysqli->query("SELECT resource_id, max(id) max_published FROM harvest_events 
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
        $result = $this->mysqli->query($query);                
  
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

            $arr = $this->get_taxon_concept_ids_from_harvest_event($harvest_event_id);      
            $published_taxa = count(@$arr["published"]);
            $unpublished_taxa = count(@$arr["unpublished"]);
            $all_taxa = count(@$arr["all"]);                                    
            //$all_taxa = $arr;

            /*            
            print $row["harvest_event_id"] . " $title taxa pages published      = " . $published_taxa . "<br>";
            print $row["harvest_event_id"] . "        taxa pages unpublished    = " . $unpublished_taxa . "<br>";            
            print $row["harvest_event_id"] . "        taxa pages all            = " . $all_taxa . "<br>";            
            */                    
            $arr = $this->get_data_object_ids_from_harvest_event($harvest_event_id);
            $published_do = count(@$arr["published"]);
            $unpublished_do = count(@$arr["unpublished"]);            
            /*
            print $row["harvest_event_id"] . " data objects published   = " . $published_do . "<br>";
            print $row["harvest_event_id"] . " data objects unpublished = " . $unpublished_do ;
            print "<hr>";            
            */
            //print "<hr>";
            if($published_do > 0)
            {   /*
                print "Published <br>";
                print $row["harvest_event_id"] . " <u>taxa pages published     = " . $published_taxa . "</u><br>";
                print $row["harvest_event_id"] . " data objects published   = " . $published_do . "<br>";                
                */
                $total_published_taxa += $published_taxa;
                $total_published_do += $published_do;                                
                $provider["published"]["$title"][0]=$published_taxa;
                $provider["published"]["$title"][1]=$published_do;
            }            
            else
            {
                if($unpublished_do > 0)
                {   /*
                    print "With unpublished data objects <br>";
                    print $row["harvest_event_id"] . " taxa pages unpublished   = " . $all_taxa . "<br>";            
                    print $row["harvest_event_id"] . " data objects unpublished = " . $unpublished_do ;                    
                    */
                    $total_unpublished_taxa += $all_taxa;
                    $total_unpublished_do += $unpublished_do;                                
                    $provider["unpublished"][$title]=true;                    
                }                
            }
            //print "<hr><hr><hr>";            
        }                
        /*       
        print "<hr>";        
        print "<hr>taxa pages published = " . $total_published_taxa;                
        print "<hr>data objects published = " . $total_published_do;                
        print "<hr>";        
        */        
        $provider["totals"][0]=$total_published_taxa;
        $provider["totals"][1]=$total_published_do;                
        return $provider;        
    }//end function dataobject_stat_more($group)    
    
    function get_taxon_concept_ids_from_harvest_event($harvest_event_id)
    {   
        $query = "
        SELECT DISTINCT he.taxon_concept_id as id , '0' as published
        FROM harvest_events_taxa het 
        JOIN taxa t ON (het.taxon_id=t.id) 
        JOIN hierarchy_entries he ON (t.hierarchy_entry_id=he.id) 
        WHERE het.harvest_event_id = $harvest_event_id ";

        $result = $this->mysqli->query($query);                

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
        $all_ids = array_keys($all_ids);
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
    {   $query = "Select distinct data_objects_harvest_events.data_object_id as id, data_objects.published
        From data_objects_harvest_events
        Inner Join data_objects ON data_objects_harvest_events.data_object_id = data_objects.id
        Where data_objects_harvest_events.harvest_event_id = $harvest_event_id ";    
        //AND data_objects.published = '1' 
        $result = $this->mysqli->query($query);                
        
        $all_ids=array();
        while($result && $row=$result->fetch_assoc())
        {
            if($row["published"])$all_ids["published"][]=$row["id"];
            else                 $all_ids["unpublished"][]=$row["id"];
        }
        $result->close();            
        //$all_ids = array_keys($all_ids);
        return $all_ids;
    }//end get_data_object_ids_from_harvest_event($harvest_event_id)    

}//end class

?>