<?php
ini_set('memory_limit','1000M');
set_time_limit(0);

$mysqli = new mysqli("", "web", "", "eol_data_production");
if (mysqli_connect_errno()) {   printf("Connect failed: %s\n", mysqli_connect_error());   exit();}
printf("Host information: %s\n", $mysqli->host_info);

// /*
//$eol_pages_with_do = get_eol_pages_with_do();
//print count($eol_pages_with_do);

list ($eol_pages_with_do, $taxa_count) = get_eol_pages_with_do();

print "<hr>
eol pages with do = $eol_pages_with_do <br>
eol pages with content = $taxa_count
";

// */

//$eol_pages_with_bhl_links = get_eol_pages_with_bhl_links();
//print count($eol_pages_with_bhl_links);




function get_eol_pages_with_bhl_links()
{	
	global $mysqli;
        $taxa_in_bhl = array();
        $query = "select distinct tc.id taxon_concept_id from taxon_concepts tc STRAIGHT_JOIN taxon_concept_names tcn on (tc.id=tcn.taxon_concept_id)
        STRAIGHT_JOIN page_names pn on (tcn.name_id=pn.name_id)
        where tc.supercedure_id=0 and tc.published=1 and (tc.vetted_id=5 OR tc.vetted_id=0) ";
        //$query .= " limit 1 ";    //for debug only
        
        //$result = $this->mysqli->query($query);    //3
		$result = $mysqli->query($query);    //3
        while($result && $row=$result->fetch_assoc())
        {
            $id = $row["taxon_concept_id"];
            $taxa_in_bhl[$id] = true;
        }
        
        ///////////////////////////////////////////////////////////////////////////////////////////////// 
        
        $taxa_in_bhl = array_keys($taxa_in_bhl);            //print "[ " . count($taxa_in_bhl) . "]";
		return $taxa_in_bhl;	
}


function get_eol_pages_with_do()
{
	
	global $mysqli;
	/*
    $image_type_id = DataType::insert("http://purl.org/dc/dcmitype/StillImage");
    $text_type_id  = DataType::insert("http://purl.org/dc/dcmitype/Text");
    $trusted_id  = Vetted::find("trusted");	
	*/	
    $image_type_id = 1;
    $text_type_id  = 3;
    $trusted_id  = 5;
		
	
        $query=" Select Max(harvest_events.id) as max From resources Inner Join harvest_events ON resources.id = harvest_events.resource_id Group By resources.id Order By max ";
        $result = $mysqli->query($query);    
        $temp_arr=array();
        while($result && $row=$result->fetch_assoc())
        {$temp_arr[$row["max"]]=1;}
        $temp_arr = array_keys($temp_arr);
        $result->close();	

        $query = "select distinct tc.id taxon_concept_id, he.id in_col, do.data_type_id, do.vetted_id, dotoc.toc_id toc_id,
        do.visibility_id , do.published, tc.vetted_id as tc_vetted_id,
        dohe.harvest_event_id,
        do.id as data_object_id from 
        ((taxon_concepts tc left join hierarchy_entries he on (tc.id=he.taxon_concept_id and he.hierarchy_id = 147 ))
        join taxon_concept_names tcn on (tc.id=tcn.taxon_concept_id) 
        join taxa t on (tcn.name_id=t.name_id)
        join data_objects_taxa dot on (t.id=dot.taxon_id) 
        join data_objects do on (dot.data_object_id=do.id)
        join data_objects_harvest_events dohe on (do.id=dohe.data_object_id)) 
        left join data_objects_table_of_contents dotoc on (do.id=dotoc.data_object_id) 
        where tc.supercedure_id=0 and tc.published=1 and (tc.vetted_id=$trusted_id OR tc.vetted_id=0) 
        and dohe.harvest_event_id IN (".implode(",", $temp_arr).")";        
		$query .= " and do.published=1 ";	//added only for this report
        //$query .= " limit 100 ";    //for debug only
		
        $result = $mysqli->query($query); //1
		print "<br> recs " . $result->num_rows;
		
        $taxa_with_text = array();
        $taxa_with_images = array();
		$taxa_with_do = array();
		$in_col = array(); //defined here as taxa with data object; whether in col or not in col
		
        while($result && $row=$result->fetch_assoc())
        {
			//print" a ";
            $id = $row["taxon_concept_id"];
            $toc_id = $row["toc_id"];
            if($row["published"]==1)
            {        
				//print" x ";
                if($row["data_type_id"] == $text_type_id && $toc_id)
                {        
                    $taxa_with_text[$id] = true;
					$taxa_with_do[$id] = true;
					//print" y ";

                    if($row["in_col"])  {$in_col[$id] = true;}
                    else                {$in_col[$id] = false;}

					
                }
                elseif($row["data_type_id"] == $image_type_id) 
                {
                    $taxa_with_images[$id] = true;
					$taxa_with_do[$id] = true;
					//print" z ";
					
                    if($row["in_col"])   {$in_col[$id] = true;}
                    else                 {$in_col[$id] = false;}

					
                }        
            }        
        }//end while
        $result->close();

	print "<br> taxa_with_do = " . count($taxa_with_do) . "<br>";		
	$taxa_with_do = array_keys($taxa_with_do);

	//print "<hr> taxa_count " . count($in_col); exit;	
	
	$taxa_count = count($in_col);
	
	//return $taxa_with_do;
	
	return array ($taxa_with_do,$taxa_count);
	
}

function get_all_pages()
{
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

}


$mysqli->close();
?>
