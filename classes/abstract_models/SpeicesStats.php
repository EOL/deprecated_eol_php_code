<?php

/*
the MODEL
*/


class SpeciesStats extends MysqlBase
{

    function __construct()
    {
        parent::initialize();
    }
    
    public function taxa_stat($taxon_concept_ids,$limit,$group)    //group 1=taxa stat; 2=data object stat
    {
        /*
        Number of taxa with at least one vetted data object in only one category (in COL):      33228
        Number of taxa with at least one vetted data object in only one category (not in COL, i.e. includes Flickr):     44635
        Number of taxa with at least one vetted data object in more than one category (in COL):     59274
        Number of taxa with at least one vetted data object in more than one category (not in COL, i.e. includes Flickr):     41464
        */
        
        //initialize group 1
        $taxa_text=0;
        $taxa_images=0;
        $taxa_text_images=0;
        $taxa_BHL_no_text=0;
        $taxa_links_no_text=0;
        $taxa_images_no_text=0;
        $taxa_text_no_images=0;

        $vet_obj_only_1cat_inCOL=0;
        $vet_obj_only_1cat_notinCOL=0;
        $vet_obj_morethan_1cat_inCOL=0;
        $vet_obj_morethan_1cat_notinCOL=0;
        $vet_obj=0;
        $no_vet_obj=0;
        $with_BHL=0;

        $vetted_not_published=0;
        $vetted_unknown_published_visible_inCol=0;
        $vetted_unknown_published_visible_notinCol=0;

        $taxa_count=0;

        $no_vet_obj2=0;
        $a_taxa_with_text="";

    
        //start main body ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        $image_type_id = DataType::insert("http://purl.org/dc/dcmitype/StillImage");
        $text_type_id  = DataType::insert("http://purl.org/dc/dcmitype/Text");
        $trusted_id  = Vetted::find("trusted");


        $in_col = array();
        $number_of_data_objects = array();
        $number_of_data_objects['vetted']['image'] = array();
        $number_of_data_objects['vetted']['text'] = array();
        $number_of_data_objects['unvetted']['image'] = array();
        $number_of_data_objects['unvetted']['text'] = array();

        $taxa_with_text = array();
        $taxa_with_images = array();

        $total_taxa['in col']['with object'] = array();
        $total_taxa['in col']['without object'] = array();
        $total_taxa['not in col']['with object'] = array();
        $total_taxa['not in col']['without object'] = array();

        /*
        Number of taxa with at least one 'vetted=trusted' 'published=false':
        data object from harvests pending publication, where the taxon doesn't have any published=true data objects (i.e. pages that will become countable after publication)     0

        Number of taxa     in CoL which have *only* 'vetted=unknown' and 'published=true' and 'visibilty=visible' data objects (taxa can be either trusted or not):     0
        Number of taxa NOT in CoL which have *only* 'vetted=unknown' and 'published=true' and 'visibilty=visible' data objects (taxa can be either trusted or not):     0
        */

        $taxa_only_vetted_unknown_published_visible_inCol                     = array();
        $taxa_only_vetted_unknown_published_visible_not_inCol                 = array();
        $opposite_taxa_only_vetted_unknown_published_visible_inCol             = array();
        $opposite_taxa_only_vetted_unknown_published_visible_not_inCol         = array();

        $taxa_vetted_not_published=array();
        
        // this block checks the latest PUBLISHED harvest events for each resource
        $latest_published = array();
        $result = $this->mysqli->query("SELECT resource_id, max(id) max_published FROM harvest_events 
        WHERE published_at IS NOT NULL GROUP BY resource_id");
        while($result && $row=$result->fetch_assoc())
        {
            $latest_published[$row['resource_id']] = $row['max_published'];
        }
        
        //start new
        // this query will only grab the LATEST harvest event, which may be in preview mode
        $query=" Select resources.id resource_id, Max(harvest_events.id) as max
        From resources Inner Join harvest_events ON resources.id = harvest_events.resource_id
        Group By resources.id Order By max ";
        $result = $this->mysqli->query($query);    
        $temp_arr=array();
        while($result && $row=$result->fetch_assoc())
        {
            // if the event is in preview mode, and there is a different PUBLISHED event, then used the published one
            if(@$latest_published[$row['resource_id']])
            {
                $id = $latest_published[$row['resource_id']];
                $temp_arr[$id] = 1;
            }
            else $temp_arr[$row["max"]] = 1;
        }
        $temp_arr = array_keys($temp_arr);
        $result->close();
        //end new
        
        $query = "select distinct tc.id  taxon_concept_id, he.id in_col, do.data_type_id, do.vetted_id, dotoc.toc_id toc_id,
        do.visibility_id , do.published, tc.vetted_id as tc_vetted_id,
        dohe.harvest_event_id,
        do.id as data_object_id from 
        ((taxon_concepts tc left join hierarchy_entries he on (tc.id=he.taxon_concept_id and he.hierarchy_id = ".Hierarchy::col_2009()." ))
        join taxon_concept_names tcn on (tc.id=tcn.taxon_concept_id) 
        join taxa t on (tcn.name_id=t.name_id)
        join data_objects_taxa dot on (t.id=dot.taxon_id) 
        join data_objects do on (dot.data_object_id=do.id)
        join data_objects_harvest_events dohe on (do.id=dohe.data_object_id)) 
        left join data_objects_table_of_contents dotoc on (do.id=dotoc.data_object_id) 
        where tc.supercedure_id=0 and tc.published=1 and (tc.vetted_id=$trusted_id OR tc.vetted_id=0) 
        and dohe.harvest_event_id IN (".implode(",", $temp_arr).")";        
        //$query .= " limit 1 ";    //for debug only
        

        $taxa_published['vetted']    =array();    //PL added item
        $taxa_published['unvetted']    =array();    //PL added item

        $taxa_without_object_inCol = array();
        $taxa_without_object_notinCol = array();
        
        
        $result = $this->mysqli->query($query); //1
        while($result && $row=$result->fetch_assoc())
        {
            $id = $row["taxon_concept_id"];
            $toc_id = $row["toc_id"];

            //PL request start
            if($row["tc_vetted_id"] == $trusted_id)    { $taxa_published['vetted'][$id] = true; }
            else                                    { $taxa_published['unvetted'][$id] = true; }
            //PL request end


            //DP request start
            if($row["in_col"])     
            {
                if    (    ($row["data_type_id"] == $text_type_id && $toc_id or $row["data_type_id"] == $image_type_id)    and
                        $row["vetted_id"] != Vetted::find('Untrusted')
                    )
                        {$total_taxa['in col']['with object'][$id]=true;}
                else    {$total_taxa['in col']['without object'][$id]=true;}
            }
               else                 
            {
                if    (    ($row["data_type_id"] == $text_type_id && $toc_id or $row["data_type_id"] == $image_type_id)    and
                        $row["vetted_id"] != Vetted::find('Untrusted')
                    )
                        {$total_taxa['not in col']['with object'][$id]=true;}
                else    {$total_taxa['not in col']['without object'][$id]=true;}
            }    
            //DP request end
    
            if($row["published"]==1)
            {        
                /* transferred inside text and image */
                if($row["data_type_id"] == $text_type_id && $toc_id)
                {        
                    if($row["in_col"])     {$in_col[$id] = true;}
                    else                 {$in_col[$id] = false;}
            
                    $taxa_with_text[$id] = true;
                       if($row["vetted_id"] == $trusted_id){$number_of_data_objects['vetted']['text'][$id][$toc_id] = true;}
                    else                                {$number_of_data_objects['unvetted']['text'][$id][$toc_id] = true;}
                }
                elseif($row["data_type_id"] == $image_type_id) 
                {

                    if($row["in_col"])     {$in_col[$id] = true;}
                    else                 {$in_col[$id] = false;}
        
                    $taxa_with_images[$id] = true;
                       if($row["vetted_id"] == $trusted_id)    {$number_of_data_objects['vetted']['image'][$id] = true;}
                    else                                    {$number_of_data_objects['unvetted']['image'][$id] = true;}
            
                }
                else{}
    
                //start last 3
                /*
                Number of taxa with at least one 'vetted=trusted' 'published=false':
                data object from harvests pending publication, where the taxon doesn't have any published=true data objects (i.e. pages that will become countable after publication)     0

                Number of taxa     in CoL which have *only* 'vetted=unknown' and 'published=true' and 'visibilty=visible' data objects (taxa can be either trusted or not):     0
                Number of taxa NOT in CoL which have *only* 'vetted=unknown' and 'published=true' and 'visibilty=visible' data objects (taxa can be either trusted or not):     0
                */
        
                   if    (    $row["data_type_id"] == $text_type_id && $toc_id    or
                        $row["data_type_id"] == $image_type_id        
                    )
                {
                    //print" [$id] $row[data_type_id] $row[vetted_id] $row[visibility_id] <br> ";
                    if( $row["vetted_id"] == 0 and $row["visibility_id"] == 1)     
                    {        
                        if($row["in_col"])    {$taxa_only_vetted_unknown_published_visible_inCol[$id]=true;}
                        else                {$taxa_only_vetted_unknown_published_visible_not_inCol[$id]=true;}
                    }
                    else
                    {
                        if($row["in_col"])    {$opposite_taxa_only_vetted_unknown_published_visible_inCol[$id]=true;}
                        else                {$opposite_taxa_only_vetted_unknown_published_visible_not_inCol[$id]=true;}        
                    }
                }
                //end last 3    
            }        

            else    //not published
            {
                if (    $row["data_type_id"] == $text_type_id && $toc_id    or
                        $row["data_type_id"] == $image_type_id        
                    )
                {
                    if($row["vetted_id"] == $trusted_id)
                    {
                        //start new - to check if data_object is in the latest harvest
                        if($row["visibility_id"] == 2)    // anomaly: there are objects with published=0 even when the rest of the objects for that resource is already published
                        {
                            $taxa_vetted_not_published[$id] = 1;    //Approved pages awaiting publication    //taxa with at least one 'vetted=trusted' 'published=false' 
                        }
                        //end new
                    }
                }    
            }    
    

            //start stub page    
            if    (    $row["data_type_id"] == $text_type_id && $toc_id    or
                    $row["data_type_id"] == $image_type_id        
                )
            {}
            else
            {
                if($row["in_col"])     {$taxa_without_object_inCol[$id]=true;    }
                else                 {$taxa_without_object_notinCol[$id]=true;    }
            }//end stub page
        }//end while
        $result->close();

        $taxa_published_vetted      = count($taxa_published['vetted']);
        $taxa_published_unvetted = count($taxa_published['unvetted']);

        $taxa_count = count($in_col);

        //start DP
        $total_taxa_inCol_withObject         = count($total_taxa['in col']['with object']);
        $total_taxa_inCol_withoutObject     = count($total_taxa['in col']['without object']);
        $total_taxa_notinCol_withObject     = count($total_taxa['not in col']['with object']);
        $total_taxa_notinCol_withoutObject    = count($total_taxa['not in col']['without object']);

        $stats = array();
        $stats["in col one category"] = 0;
        $stats["in col more than one category"] = 0;
        $stats["not in col one category"] = 0;
        $stats["not in col more than one category"] = 0;
        
        foreach($in_col as $id => $is_in_col)
        {
            $number_of_categories = 0;
            if(@$number_of_data_objects['vetted']['image'][$id])     $number_of_categories += 1;
            if(@$number_of_data_objects['vetted']['text'][$id])     $number_of_categories += count($number_of_data_objects['vetted']['text'][$id]);
    
            if($is_in_col)
            {
                if        ($number_of_categories == 1)     {$stats["in col one category"]++;                }
                elseif    ($number_of_categories > 1)     {$stats["in col more than one category"]++;        }
            }
            else
            {
                if        ($number_of_categories == 1)     $stats["not in col one category"]++;
                elseif    ($number_of_categories > 1)     $stats["not in col more than one category"]++;
            }
        }// end for loop

        $vet_obj_only_1cat_inCOL =             $stats["in col one category"];
        $vet_obj_morethan_1cat_inCOL =         $stats["in col more than one category"];
        $vet_obj_only_1cat_notinCOL =         $stats["not in col one category"];
        $vet_obj_morethan_1cat_notinCOL =     $stats["not in col more than one category"];


        //end main body ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

        //start 1st part /////////////////////////////////////////////////////////////////////////////////////////////////////////
        // /*
        $pages_incol = array();
        $pages_not_incol = array();
        $query = "Select taxon_concepts.id, he.id as in_col From 
        taxon_concepts  left join hierarchy_entries he on (taxon_concepts.id=he.taxon_concept_id and he.hierarchy_id=".Hierarchy::col_2009().")
        Where taxon_concepts.published = 1 AND
        taxon_concepts.supercedure_id = 0 ";
        //$query .= " limit 1 ";    //for debug only

        $result = $this->mysqli->query($query);

        while($result && $row=$result->fetch_assoc())
        {
            $id = $row["id"];
            if($row["in_col"])  $pages_incol[$id]=true;
            else                $pages_not_incol[$id]=true;    
        }
        $result->close();

        $arr_pages_incol     = array_keys($pages_incol);

        //temporary sol'n
        $pages_incol         = count($pages_incol);
        //$pages_incol = 1299534;

        $pages_not_incol     = count($pages_not_incol);

        // */
        //end 1st part /////////////////////////////////////////////////////////////////////////////////////////////////////////


        //start 2nd part /////////////////////////////////////////////////////////////////////////////////////////////////////////
        $taxa_BHL_no_text     = 0;
        $taxa_links_no_text = 0;
        $with_BHL            = 0;

        //end 2nd part ///////////////////////////////////////////////////////////////////////////////////////////////////////////

        $taxa_with_text = array_keys($taxa_with_text);        //converts the keys to values - in an array
        $a_taxa_with_text = implode("_",$taxa_with_text);

        $taxa_with_images = array_keys($taxa_with_images);

        $taxa_without_object = array_keys($taxa_without_object_inCol);

        $taxa_text = count($taxa_with_text);                                        //Taxa with text:
        $taxa_images = count($taxa_with_images);                                    //Taxa with images:
        $taxa_text_images = count(array_intersect($taxa_with_text, $taxa_with_images));    //Taxa with text and images:                                
        $taxa_images_no_text = count(array_diff($taxa_with_images     , $taxa_with_text));    //Taxa with images and no text:
        $taxa_text_no_images = count(array_diff($taxa_with_text     , $taxa_with_images));    //Taxa with text and no images:
        
        $vet_obj    =     $vet_obj_only_1cat_inCOL         + 
                        $vet_obj_morethan_1cat_inCOL     + 
                        $vet_obj_only_1cat_notinCOL     + 
                        $vet_obj_morethan_1cat_notinCOL;
        
        $no_vet_obj    = count($taxa_without_object);

        //start no_vet_obj2 DP
        $total_taxa_incol_withobject = array_keys($total_taxa['in col']['with object']);

        //temporary sol'n
        $no_vet_obj2                  = count(array_diff($arr_pages_incol , $total_taxa_incol_withobject));    //
        //$no_vet_obj2                = 1299534 - count($total_taxa_incol_withobject);    //

        //end no_vet_obj2

        $taxa_only_vetted_unknown_published_visible_inCol = array_keys($taxa_only_vetted_unknown_published_visible_inCol);
        $taxa_only_vetted_unknown_published_visible_not_inCol = array_keys($taxa_only_vetted_unknown_published_visible_not_inCol);
        $opposite_taxa_only_vetted_unknown_published_visible_inCol = array_keys($opposite_taxa_only_vetted_unknown_published_visible_inCol);
        $opposite_taxa_only_vetted_unknown_published_visible_not_inCol = array_keys($opposite_taxa_only_vetted_unknown_published_visible_not_inCol);

        $a_vetted_unknown_published_visible_inCol = array_diff($taxa_only_vetted_unknown_published_visible_inCol     , $opposite_taxa_only_vetted_unknown_published_visible_inCol);
        $a_vetted_unknown_published_visible_notinCol = array_diff($taxa_only_vetted_unknown_published_visible_not_inCol , $opposite_taxa_only_vetted_unknown_published_visible_not_inCol);

        $vetted_unknown_published_visible_inCol = count($a_vetted_unknown_published_visible_inCol);
        $vetted_unknown_published_visible_notinCol = count($a_vetted_unknown_published_visible_notinCol);


        $a_vetted_unknown_published_visible_inCol = implode("_",$a_vetted_unknown_published_visible_inCol);
        $a_vetted_unknown_published_visible_notinCol = implode("_",$a_vetted_unknown_published_visible_notinCol);

        $vetted_not_published = count(array_keys($taxa_vetted_not_published));
        $a_vetted_not_published    = implode("_",array_keys($taxa_vetted_not_published));
        print "<hr>$a_vetted_not_published<hr>";



        //start save
        $date_created = date('Y-m-d');
        $time_created = date('H:i:s');

            $qry = " insert into page_stats_taxa(    
           taxa_count
         ,  taxa_text 
         ,  taxa_images 
        ,  taxa_text_images 
         ,  taxa_BHL_no_text 
         ,  taxa_links_no_text 
         ,  taxa_images_no_text 
         ,  taxa_text_no_images 

         ,  vet_obj_only_1cat_inCOL 
         ,  vet_obj_only_1cat_notinCOL 
         ,  vet_obj_morethan_1cat_inCOL 
         ,  vet_obj_morethan_1cat_notinCOL 
         ,  vet_obj 
         ,  with_BHL 

         ,  vetted_not_published 
         ,  vetted_unknown_published_visible_inCol 
         ,  vetted_unknown_published_visible_notinCol 
    
        , pages_incol
        , pages_not_incol

         ,  no_vet_obj2 
        ,  a_taxa_with_text

        , date_created,time_created,active

        , a_vetted_not_published
        , a_vetted_unknown_published_visible_inCol     
        , a_vetted_unknown_published_visible_notinCol

        )

        select     
           $taxa_count
         ,  $taxa_text 
         ,  $taxa_images 
         ,  $taxa_text_images 
        ,  $taxa_BHL_no_text 
         ,  $taxa_links_no_text 
         ,  $taxa_images_no_text 
         ,  $taxa_text_no_images 

         ,  $vet_obj_only_1cat_inCOL 
         ,  $vet_obj_only_1cat_notinCOL 
         ,  $vet_obj_morethan_1cat_inCOL 
         ,  $vet_obj_morethan_1cat_notinCOL 
         ,  $vet_obj 
         ,  $with_BHL 

         ,  $vetted_not_published 
         ,  $vetted_unknown_published_visible_inCol 
         ,  $vetted_unknown_published_visible_notinCol 


        , $pages_incol
        , $pages_not_incol

         ,  $no_vet_obj2     

        , '$a_taxa_with_text'        

         ,  '$date_created','$time_created','n'    

        , '$a_vetted_not_published'

        , '$a_vetted_unknown_published_visible_inCol'     
        , '$a_vetted_unknown_published_visible_notinCol'

        ";
    
        $update = $this->mysqli->query($qry);    //1
        //end save
        
        if($group==1) return "";    //no need to return anymore
    }//end function taxa_stat
    
    public function links_stat($limit,$group)    //group 1=taxa stat; 2=data object stat    //for bhl and outlinks
    {
        $query = "select a_taxa_with_text, id from page_stats_taxa order by id desc limit 1";
        $sql = $this->mysqli->query($query);
        $row = $sql->fetch_row();            
        $comma_separated   = $row[0];
        $rec_id   = $row[1];        
        $taxa_with_text = explode("_",$comma_separated);
        
        ///////////////////////////////////////////////////////////////////////////////////////////////// 
        $taxa_with_links = array();
        $query = "select distinct tc.id taxon_concept_id from taxon_concepts tc STRAIGHT_JOIN taxon_concept_names tcn on (tc.id=tcn.taxon_concept_id)
        STRAIGHT_JOIN mappings m on (tcn.name_id=m.name_id)
        where tc.supercedure_id=0 and tc.published=1 and (tc.vetted_id=".Vetted::find('Trusted')." OR tc.vetted_id=0) ";
        //$query .= " limit 1 ";    //for debug only
        
        $result2 = $this->mysqli->query($query);    //4
        while($result2 && $row2=$result2->fetch_assoc())
        {
            $id = $row2["taxon_concept_id"];
            $taxa_with_links[$id] = true;
        }
        
        ///////////////////////////////////////////////////////////////////////////////////////////////// 
        $taxa_in_bhl = array();
        $query = "select distinct tc.id taxon_concept_id from taxon_concepts tc STRAIGHT_JOIN taxon_concept_names tcn on (tc.id=tcn.taxon_concept_id)
        STRAIGHT_JOIN page_names pn on (tcn.name_id=pn.name_id)
        where tc.supercedure_id=0 and tc.published=1 and (tc.vetted_id=".Vetted::find('Trusted')." OR tc.vetted_id=0) ";
        //$query .= " limit 1 ";    //for debug only
        
        $result = $this->mysqli->query($query);    //3
        while($result && $row=$result->fetch_assoc())
        {
            $id = $row["taxon_concept_id"];
            $taxa_in_bhl[$id] = true;
        }
        
        ///////////////////////////////////////////////////////////////////////////////////////////////// 
        
        $taxa_in_bhl = array_keys($taxa_in_bhl);            //print "[ " . count($taxa_in_bhl) . "]";
        $taxa_with_links = array_keys($taxa_with_links);
        
        $taxa_BHL_no_text = count(array_diff($taxa_in_bhl , $taxa_with_text));    //Taxa in BHL with no text:
        $taxa_links_no_text = count(array_diff($taxa_with_links , $taxa_with_text));    //Taxa with links and no text:    
        
        $with_BHL = count($taxa_in_bhl);        
        
        $query = "update page_stats_taxa set 
                            with_BHL             = $with_BHL, 
                            taxa_BHL_no_text     = $taxa_BHL_no_text, 
                            taxa_links_no_text     = $taxa_links_no_text 
                            where id = $rec_id";
                        
        $update = $this->mysqli->query($query);
        
        //#####################################################################################################
        //new start delete recs, maintain only 8 days of history
        print"<hr>Maintaining 8 records in history...<br>";
        for ($i = 1; $i <= 2; $i++) 
        {
            if($i==1) $tbl='page_stats_taxa';
            else $tbl='page_stats_dataobjects';
            
            $query="select id from $tbl order by date_created desc, time_created desc limit 8";
            $result = $this->mysqli->query($query);    //1
            while($result && $row=$result->fetch_assoc()){$first_of_eight = $row['id'];}    
            $query = "delete  from $tbl where id < $first_of_eight";    //print "<hr>$query";
            $update = $this->mysqli->query($query);
            echo "all recs with id < $first_of_eight from $tbl were deleted <br>"; //n=" . $this->mysqli->affected_rows;    
        }
        
        //new end delete recs, maintain only 8 days of history
        //#####################################################################################################
        
        return "
            $with_BHL,
            $taxa_BHL_no_text,
            $taxa_links_no_text,
            $group
            ";
    }//end links_stat()


    public function dataobject_stat($taxon_concept_ids, $limit, $group)    //group 1=taxa stat; 2=data object stat
    {
        //start new
        $query=" Select Max(harvest_events.id) as max
        From resources Inner Join harvest_events ON resources.id = harvest_events.resource_id
        Group By resources.id Order By max ";
        //$query .= " limit 1";//debug
        
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
        $trusted_id  = Vetted::find('Trusted');
        
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
        //$query .= " limit 1";//debug
        
        $result = $this->mysqli->query($query);

        $do_count = array();
                    
        while($result && $row=$result->fetch_assoc())
        {
            $id = $row["id"];    
            $toc_id = $row["toc_id"];    
            
            
            if  (   in_array($row["data_type_id"], array(1,2,3,4,5,6,7,8))  and
                    in_array($row["vetted_id"], array(0,4,5))  
                )   $do_count[$id] = 1;
                                
            
            if($row["data_type_id"] == $text_type_id && $toc_id ||
               $row["data_type_id"] == $image_type_id)
            {
                //start for do stat    
                if($row["vetted_id"] == 0)    //unknown
                {
                    if        ($row["visibility_id"] == 1) $number_of_data_objects['unknown']['visib1'][$id] = true;
                    elseif    ($row["visibility_id"] == '0') $number_of_data_objects['unknown']['visib0'][$id] = true;
                }
                elseif($row["vetted_id"] == 4)    //untrusted
                {
                    if        ($row["visibility_id"] == 1) $number_of_data_objects['untrusted']['visib1'][$id] = true;
                    elseif    ($row["visibility_id"] == '0') $number_of_data_objects['untrusted']['visib0'][$id] = true;
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
        a_vetted_untrusted_published_notVisible_uniqueGuid     
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
        '$a_vetted_untrusted_published_notVisible_uniqueGuid'             
        ";        
    
        $update = $this->mysqli->query($qry);    //1
            //end save
    
        return "
            $vetted_unknown_published_visible_uniqueGuid,
            $vetted_untrusted_published_visible_uniqueGuid,
            $vetted_unknown_published_notVisible_uniqueGuid,
            $vetted_untrusted_published_notVisible_uniqueGuid,
            $group,
            $taxa_count
            ";        
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
        8 => "YouTube"    
        );
        $vetted_type = array( 
        1 => array( "id" => "0"   , "label" => "Unknown"),
        2 => array( "id" => "4"   , "label" => "Untrusted"),
        3 => array( "id" => "5"   , "label" => "Trusted")
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
        //$query .= " limit 100,10";        
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
        $latest_event_id   = $row[0];                
        $result->close();        
        $query = "Select Count(data_objects_harvest_events.data_object_id) From data_objects_harvest_events Where data_objects_harvest_events.harvest_event_id = $latest_event_id";
        $result = $this->mysqli->query($query);
        $row = $result->fetch_row();			
        $param[] = $row[0];                
        $result->close();        
        //end Flickr count                      
        
        //start user submited do
        //$mysqli2 = load_mysql_environment('eol_production');
        $mysqli2 = load_mysql_environment('slave_eol');
        $query = "Select Count(users_data_objects.id) From users_data_objects";
        $result = $mysqli2->query($query);        
        $row = $result->fetch_row();			
        $param[] = $row[0];                
        $result->close();
        //end user submitted do        
        
        $comma_separated = implode(",", $param);        
                
        return "$comma_separated";        

    }//end function dataobject_stat_more($group)


    public function lifedesk_stat($group)
    {   
        $latest_published = array();
        $result = $this->mysqli->query("SELECT resource_id, max(id) max_published FROM harvest_events 
        WHERE published_at IS NOT NULL GROUP BY resource_id");
        while($result && $row=$result->fetch_assoc())
        {$latest_published[$row['resource_id']] = $row['max_published'];}

         
        /* query to get all latest harvest_events for all LifeDesk providers */
        $query = "Select Max(harvest_events.id) as harvest_event_id, harvest_events.resource_id, resources.title
        From resources
        Inner Join harvest_events ON resources.id = harvest_events.resource_id
        Inner Join resource_statuses ON resources.resource_status_id = resource_statuses.id
        Where resources.accesspoint_url Like '%lifedesks.org%'
        Group By harvest_events.resource_id ";
        $result = $this->mysqli->query($query);        
        
        $tc_id_published = array();
        $tc_id_unpublished = array();
        
        $do_id_published = array();
        $do_id_unpublished = array();        
        while($result && $row=$result->fetch_assoc())        
        {
            if(@$latest_published[$row['resource_id']]) $harvest_event_id = $latest_published[$row['resource_id']];
            else                                        $harvest_event_id = $row["harvest_event_id"];        
            
            $arr = $this->get_taxon_concept_ids_from_harvest_event($harvest_event_id);      
            print $row["harvest_event_id"] . " $row[title] taxa pages published = " . count(@$arr["published"]) . "<br>";
            print $row["harvest_event_id"] . "             taxa pages unpublished = " . count(@$arr["unpublished"]) . "<br>";            
            if(@$arr["published"])  $tc_id_published   = array_merge(@$arr["published"],$tc_id_published);
            if(@$arr["unpublished"])$tc_id_unpublished = array_merge(@$arr["unpublished"],$tc_id_unpublished);                        
                    
            $arr = $this->get_data_object_ids_from_harvest_event($harvest_event_id);
            print $row["harvest_event_id"] . " data objects published = " . count(@$arr["published"]) . "<br>";
            print $row["harvest_event_id"] . " data objects unpublished = " . count(@$arr["unpublished"]) . "<hr>";            
            if(@$arr["published"])  $do_id_published   = array_merge(@$arr["published"],$do_id_published);
            if(@$arr["unpublished"])$do_id_unpublished = array_merge(@$arr["unpublished"],$do_id_unpublished);            
            
        }
        
        //$tc_id = array_unique($tc_id);        
        /* not used bec dataobject comes from diff providers
        $do_id = array_unique($do_id);
        */
        
        print "<hr>taxa pages published = " . count(@$tc_id_published);                
        print "<hr>taxa pages unpublished = " . count(@$tc_id_unpublished);                

        print "<hr>data objects published = " . count(@$do_id_published);                
        print "<hr>data objects unpublished = " . count(@$do_id_unpublished);                
        print "<hr>";
        
        
    }//end function dataobject_stat_more($group)    
    
    function get_taxon_concept_ids_from_harvest_event($harvest_event_id)
    {   
        $query = "Select distinct hierarchy_entries.taxon_concept_id as id, taxon_concepts.published
        From harvest_events_taxa
        Inner Join taxa ON harvest_events_taxa.taxon_id = taxa.id
        Inner Join hierarchy_entries ON taxa.name_id = hierarchy_entries.name_id
        Inner Join taxon_concepts ON taxon_concepts.id = hierarchy_entries.taxon_concept_id
        Where harvest_events_taxa.harvest_event_id = $harvest_event_id
        and taxon_concepts.supercedure_id=0 and taxon_concepts.vetted_id in(5,0)";    
        //and taxon_concepts.published=1
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
    }//end get_taxon_concept_ids_from_harvest_event($harvest_event_id)    

    function get_data_object_ids_from_harvest_event($harvest_event_id)
    {   
        $query = "Select distinct data_objects_harvest_events.data_object_id as id, data_objects.published
        From data_objects_harvest_events
        Inner Join data_objects ON data_objects_harvest_events.data_object_id = data_objects.id
        Where
        data_objects_harvest_events.harvest_event_id = $harvest_event_id ";    
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