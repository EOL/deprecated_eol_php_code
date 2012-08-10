<?php
/* A certain Jira ticket, requested for BHL numbers.
*/


require_once(dirname(__FILE__) ."/../../config/environment.php");
$mysqli =& $GLOBALS['mysqli_connection'];



//list ($eol_pages_with_do, $taxa_count) = get_eol_pages_with_do(); //print "<hr> eol pages with do       = $eol_pages_with_do <br> eol pages with content  = $taxa_count ";

//$eol_pages_with_bhl_links              = get_eol_pages_with_bhl_links(); //print count($eol_pages_with_bhl_links);
//$all_pages                             = get_all_pages();

$marine_pages_xml                      = get_marine_eol_pages();
$marine_pages_harvest                  = get_taxon_concept_ids_from_harvest_event(1028);

$barcoded_pages                        = get_taxon_concept_ids_from_harvest_event(949);

/* working
$eol_pages_without_do                = array_diff($all_pages , $eol_pages_with_do);
$eol_pages_without_do_with_bhl_links = array_intersect($eol_pages_without_do, $eol_pages_with_bhl_links);
$marine_pages_with_bhl_links         = array_intersect($marine_eol_pages,$eol_pages_with_bhl_links);
*/

$marine_pages_with_barcode_xml           = array_intersect($marine_pages_xml,$barcoded_pages);
$marine_pages_with_barcode_harvest       = array_intersect($marine_pages_harvest,$barcoded_pages);

/*
print"
<table>
<tr><td>Total EOL pages</td><td align='right'>" . number_format(count($all_pages),0) . "</td></tr>
<tr><td>Pages with data objects (text/image)</td><td align='right'>" . number_format(count($eol_pages_with_do),0) . "</td></tr>
<tr><td>Pages without data objects (text/image)</td><td align='right'>" . number_format(count($eol_pages_without_do),0) . "</td></tr>
<tr bgcolor='aqua'><td>Pages with BHL links</td><td align='right'>" . number_format(count($eol_pages_with_bhl_links),0) . "</td></tr>
<tr bgcolor='aqua'><td>Pages with no data objects except links to BHL pages</td><td align='right'>" . number_format(count($eol_pages_without_do_with_bhl_links),0) . "</td></tr>
<tr><td>Marine pages</td><td align='right'>" . number_format(count($marine_eol_pages),0) . "</td></tr>
<tr bgcolor='aqua'><td>Marine pages with BHL links</td><td align='right'>" . number_format(count($marine_pages_with_bhl_links),0) . "</td></tr>
</table>";
*/

/*
*/
print"
<table>
<tr><td><hr></td></tr>
<tr bgcolor='aqua'><td>Pages with Barcode</td><td align='right'>" . number_format(count($barcoded_pages),0) . "</td></tr>
<tr bgcolor='aqua'><td>Marine pages (from harvest_event)</td><td align='right'>" . number_format(count($marine_pages_harvest),0) . "</td></tr>
<tr bgcolor='aqua'><td>Marine pages with Barcode (from harvest)</td><td align='right'>" . number_format(count($marine_pages_with_barcode_harvest),0) . "</td></tr>
<tr><td><hr></td></tr>
<tr bgcolor='aqua'><td>Marine pages (from XML)</td><td align='right'>" . number_format(count($marine_pages_xml),0) . "</td></tr>
<tr bgcolor='aqua'><td>Marine pages with Barcode (from XML)</td><td align='right'>" . number_format(count($marine_pages_with_barcode_xml),0) . "</td></tr>
</table>
";


function get_marine_eol_pages()
{
    global $mysqli;
    
    $marine_pages = array();
    $batch_size = 10000;
    //$xml = simplexml_load_file("http://services.eol.org/eol_php_code/applications/content_server/resources/26.xml", null, LIBXML_NOCDATA);
    //$xml = simplexml_load_file("http://10.19.19.226/resources/26.xml", null, LIBXML_NOCDATA);
	
	//$xml = simplexml_load_file("http://www.marinespecies.org/aphia.php?p=eol&action=taxdetails&id=377972", null, LIBXML_NOCDATA);
	$xml = simplexml_load_file("http://127.0.0.1/26.xml", null, LIBXML_NOCDATA);        
    
    foreach($xml->taxon as $t)
    {
        $t_dwc = $t->children("http://rs.tdwg.org/dwc/dwcore/");
        $name = Functions::import_decode($t_dwc->ScientificName);
        $names[$name] = 1;
        $temp_names_array[] = $mysqli->escape($name);
    
        if(count($temp_names_array) >= $batch_size)
        {
            static $batch_num;
            $batch_num++;        
            //echo "Batch $batch_num<br>\n";        
            $marine_pages = get_stats($temp_names_array,$marine_pages);        
            $temp_names_array = array();
            //if($batch_num >= 4) break; //for debugging
        }
    }
    $marine_pages = get_stats($temp_names_array,$marine_pages);    
    return $marine_pages;
}

function get_stats($names,$marine_pages)
{
    global $mysqli; 
    if(mysqli_connect_errno()) printf("Can't connect to MySQL database (). Errorcode: %s\n", mysqli_connect_error());
    $result = $mysqli->query("SELECT taxon_concept_id id, n.string 
    FROM names n 
    JOIN taxon_concept_names tcn ON (n.id=tcn.name_id) 
    JOIN taxon_concepts tc ON (tcn.taxon_concept_id=tc.id) WHERE n.string     
    IN ('".implode("','", $names)."')     
    AND tc.published=1 AND tc.supercedure_id=0 AND tc.vetted_id IN (" . Vetted::find("trusted") . ")");
    /* in (5,0) */
    while($result && $row=$result->fetch_assoc())
    {
        $id = $row["id"];
        $marine_pages[$id] = 1;
    }
    return $marine_pages;    
}


function get_eol_pages_with_bhl_links()
{	
	global $mysqli;
    $taxa_in_bhl = array();
    $query = "select distinct tc.id taxon_concept_id from taxon_concepts tc STRAIGHT_JOIN taxon_concept_names tcn on (tc.id=tcn.taxon_concept_id)
    STRAIGHT_JOIN page_names pn on (tcn.name_id=pn.name_id)
    where tc.supercedure_id=0 and tc.published=1 and tc.vetted_id <> " . Vetted::find("untrusted") . "
	";
    //$query .= " limit 1 ";    //for debug only
        
    $result = $mysqli->query($query);    
    //$result = $mysqli->query($query);    
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
    $image_type_id = DataType::image()->id;
    $text_type_id  = DataType::text()->id;
    $trusted_id  = Vetted::find("trusted");	
	*/	
    $image_type_id = 1;
    $text_type_id  = 3;
    $trusted_id  = 5;		
	
    $query=" Select Max(harvest_events.id) as max From resources Inner Join harvest_events ON resources.id = harvest_events.resource_id 
    Group By resources.id Order By max ";
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
    ((taxon_concepts tc left join hierarchy_entries he on (tc.id=he.taxon_concept_id and he.hierarchy_id = ".Hierarchy::default_id()." ))
    join hierarchy_entries hent on (tc.id=hent.taxon_concept_id)
    join data_objects_hierarchy_entries dohent on (hent.id=dohent.hierarchy_entry_id) 
    join data_objects do on (dohent.data_object_id=do.id)
    join data_objects_harvest_events dohe on (do.id=dohe.data_object_id)) 
    left join data_objects_table_of_contents dotoc on (do.id=dotoc.data_object_id) 
    where tc.supercedure_id=0 and tc.published=1 and tc.vetted_id <> " . Vetted::find("untrusted") . "
	
	
    and dohe.harvest_event_id IN (".implode(",", $temp_arr).")";        
    $query .= " and do.published=1 ";	//added only for this report
    //$query .= " limit 1 ";    //for debug only
		
    $result = $mysqli->query($query); //1
    //print "<br> recs " . $result->num_rows;
		
    $taxa_with_text = array();
    $taxa_with_images = array();
    $taxa_with_do = array();
    $in_col = array(); //defined here as taxa with data object; whether in col or not in col
		
    while($result && $row=$result->fetch_assoc())
    {
        $id = $row["taxon_concept_id"];
        $toc_id = $row["toc_id"];
        if($row["published"]==1)
        {        
            if($row["data_type_id"] == $text_type_id && $toc_id)
            {        
                $taxa_with_text[$id] = true;
	    		$taxa_with_do[$id] = true;

                if($row["in_col"])  {$in_col[$id] = true;}
                else                {$in_col[$id] = false;}
					
            }
            elseif($row["data_type_id"] == $image_type_id) 
            {
                $taxa_with_images[$id] = true;
    			$taxa_with_do[$id] = true;
					
                if($row["in_col"])   {$in_col[$id] = true;}
                else                 {$in_col[$id] = false;}
            }        
        }        
    }//end while
    $result->close();

	$taxa_with_do   = array_keys($taxa_with_do);
	$taxa_count     = count($in_col);
	
	return array ($taxa_with_do,$taxa_count);   //both returns have same value
	
}

function get_taxon_concept_ids_from_harvest_event($harvest_event_id)
{
    global $mysqli;
    
    $query = "
    Select distinct he.taxon_concept_id as id
    From harvest_events_hierarchy_entries hehe
    Inner Join hierarchy_entries he ON (hehe.hierarchy_entry_id = he.id)
    Inner Join taxon_concepts tc ON (tc.id = he.taxon_concept_id)
    Where hehe.harvest_event_id = $harvest_event_id
    and tc.supercedure_id=0 and tc.published=1 and tc.vetted_id <> " . Vetted::find("untrusted") . "
    ";
    //$query .= " limit 10"; for debug
    
    $result = $mysqli->query($query);                

    while($result && $row=$result->fetch_assoc())
    {
        $all_pages[$row["id"]]=true;    
    }
    $result->close();
        
    $all_pages     = array_keys($all_pages);
    return $all_pages;
}


function get_barcoded_pages()
{
    global $mysqli;
    
    $query = "
    Select distinct he.taxon_concept_id
    From harvest_events_hierarchy_entries hehe
    Join hierarchy_entries he ON (hehe.hierarchy_entry_id=he.id)
    Join taxon_concepts tc ON (tc.id = he.taxon_concept_id)
    Where hehe.harvest_event_id = '949'
    and tc.supercedure_id=0 and tc.published=1 and tc.vetted_id <> " . Vetted::find("untrusted") . "
    ";
    
    $result = $mysqli->query($query);                

    while($result && $row=$result->fetch_assoc())
    {
        $all_pages[$id]=true;    
    }
    $result->close();
        
    $all_pages     = array_keys($all_pages);
    return $all_pages;
    
}

function get_all_pages()
{
    global $mysqli;
    
    //start 1st part /////////////////////////////////////////////////////////////////////////////////////////////////////////
    // /*
    $pages_incol        = array();
    $pages_not_incol    = array();
    $all_pages          = array();
    $query = "Select taxon_concepts.id, he.id as in_col From 
    taxon_concepts  left join hierarchy_entries he on (taxon_concepts.id=he.taxon_concept_id and he.hierarchy_id=".Hierarchy::default_id().")
    Where taxon_concepts.published = 1 AND taxon_concepts.supercedure_id = 0 and taxon_concepts.vetted_id <> " . Vetted::find("untrusted") . "
	";
    //$query .= " limit 1 ";    //for debug only

    $result = $mysqli->query($query);                

    while($result && $row=$result->fetch_assoc())
    {
        $id = $row["id"];
        if($row["in_col"])  $pages_incol[$id]=true;
        else                $pages_not_incol[$id]=true;    
        $all_pages[$id]=true;    
    }
    $result->close();

    /*
    $arr_pages_incol     = array_keys($pages_incol);
    $pages_incol         = count($pages_incol);
    $pages_not_incol     = count($pages_not_incol);
    */
        
    $all_pages     = array_keys($all_pages);
    return $all_pages;
    
    // */
    //end 1st part /////////////////////////////////////////////////////////////////////////////////////////////////////////

}


//$mysqli->close();
?>
