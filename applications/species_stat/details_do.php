<?php

//define("ENVIRONMENT", "integration");	//where stats are stored
//define("ENVIRONMENT", "slave_32");    //where stats are stored
//define("ENVIRONMENT", "data_main");	//where stats are stored

print"<table style='font-family : Arial; font-size : x-small;'><tr><td>";

define("DEBUG", false);
define("MYSQL_DEBUG", false);
require_once("../../config/start.php");
$mysqli =& $GLOBALS['mysqli_connection'];

$step=50;

// this block checks the latest PUBLISHED harvest events for each resource - from PL
$latest_published = array();
$result = $mysqli->query("SELECT resource_id, max(id) max_published FROM harvest_events WHERE published_at IS NOT NULL GROUP BY resource_id");
while($result && $row=$result->fetch_assoc())
{
    $latest_published[$row['resource_id']] = $row['max_published'];
}
   
//start new
$query=" Select Max(harvest_events.id) as max From resources Inner Join harvest_events ON resources.id = harvest_events.resource_id Group By resources.id Order By max ";
$result = $mysqli->query($query);	
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
//end new

set_time_limit(0);

$eol_site = "www.eol.org";
//$eol_site = "app1.eol.org";

$label = get_val_var('label');
$autoctr = get_val_var('autoctr');
$batch = get_val_var('batch');

$show_do = get_val_var('show_do');
if($show_do==''){$show_do='n';}

$what = get_val_var('what');
if($what==''){$what='list';}

if($what == 'resources')
{
	/* dec 6 commented
	$qry="
	Select distinct resources.title , resources.id
	From taxon_concept_names 
	Left Join names ON taxon_concept_names.name_id = names.id Left Join taxa ON names.id = taxa.name_id 
	Left Join data_objects_taxa ON taxa.id = data_objects_taxa.taxon_id Left Join data_objects ON data_objects_taxa.data_object_id = data_objects.id 
	Left Join data_types ON data_objects.data_type_id = data_types.id Left Join mime_types ON data_objects.mime_type_id = mime_types.id 
	Left Join vetted ON data_objects.vetted_id = vetted.id Left Join visibilities ON data_objects.visibility_id = visibilities.id 
	Inner Join data_objects_harvest_events ON data_objects_harvest_events.data_object_id = data_objects.id 
	Inner Join harvest_events ON data_objects_harvest_events.harvest_event_id = harvest_events.id 
	Inner Join resources ON harvest_events.resource_id = resources.id 
	Where harvest_events.id IN (".implode(",", $temp_arr).") 
	";
	*/	
    
    /*
	$qry="
    Select distinct resources.title, resources.id 
    From
    data_objects_taxa
    Inner Join taxa ON data_objects_taxa.taxon_id = taxa.id
    Inner Join hierarchy_entries ON taxa.name_id = hierarchy_entries.name_id
    Inner Join taxon_concepts ON hierarchy_entries.taxon_concept_id = taxon_concepts.id
    Inner Join data_objects ON data_objects.id = data_objects_taxa.data_object_id
    Inner Join data_objects_harvest_events ON data_objects.id = data_objects_harvest_events.data_object_id
    Inner Join harvest_events ON data_objects_harvest_events.harvest_event_id = harvest_events.id
    Inner Join resources ON harvest_events.resource_id = resources.id
    Where harvest_events.id in (".implode(",", $temp_arr).")    ";
    */
    $qry="
    Select distinct resources.title, resources.id     
    From
    data_objects
    Inner Join data_objects_harvest_events ON data_objects.id = data_objects_harvest_events.data_object_id
    Inner Join harvest_events ON data_objects_harvest_events.harvest_event_id = harvest_events.id
    Inner Join resources ON harvest_events.resource_id = resources.id
    Where harvest_events.id in (".implode(",", $temp_arr).")    ";   
	
	if($label == "Approved pages awaiting publication")
	{
		$qry .= "		
		and data_objects.published = 0					
		and data_objects.vetted_id = " . Vetted::find("trusted") . "
		and data_objects.visibility_id = " . Visibility::find('preview') . " 				
		";							
		print "Resources with un-published but vetted data objects <br> <br>";		
	}
	elseif	(	$label == "Pages with CoL names with content that requires curation" or 
				$label == "Pages NOT with CoL names with content that requires curation"	
			)
	{
		$qry .= "
		and data_objects.vetted_id = " . Vetted::find("unknown") . "			
		and data_objects.published = 1			
		and data_objects.visibility_id = " . Visibility::find('visible') . "
		";	
				
		if	  ($label == "Pages with CoL names with content that requires curation")		print "Resources with content that requires curation with Col names <br> <br>";		
		elseif($label == "Pages NOT with CoL names with content that requires curation")	print "Resources with content that requires curation NOT with Col names <br> <br>";		
	}
	
	$sql2 = $mysqli->query($qry);	
	//print "<hr>$qry<hr>";
	
	while( $row2 = $sql2->fetch_assoc() )			
	{print " - $row2[title] <i>[id:$row2[id]]</i> <br>";	
	}		
	$sql2->close();
	
	print "<br><a href='details_do.php?autoctr=$autoctr&label=$label&what=list'>See Details</a> | 
	<a href='display.php'>Back to Main Stats</a> <br>";
	exit;
}//if($what == 'resources')


if($label == "Approved pages awaiting publication")
{
	$id_type="taxa";
	$fld = "a_vetted_not_published";
	$title = "Pages with at least one un-published but vetted data objects";		
}
elseif($label == "Pages with CoL names with content that requires curation")
{
	$id_type="taxa";
	$fld = "a_vetted_unknown_published_visible_inCol";
	$title = $label;			
}
elseif($label == "Pages NOT with CoL names with content that requires curation")
{
	$id_type="taxa";
	$fld = "a_vetted_unknown_published_visible_notinCol";
	$title = $label;			
}
else
{
	$id_type="dataobject";
	
	if($label == "Number of unvetted but visible data objects")
	{	$fld = "a_vetted_unknown_published_visible_uniqueGuid";
		$title = "Data objects that are unvetted but visible";		
	}
	
	if($label == "Number of data objects that are visible and not reliable")
	{	$fld = "a_vetted_untrusted_published_visible_uniqueGuid";
		$title = "Data objects that are visible and not reliable";		
	}
	if($label == "Number of hidden and unvetted data objects")				
	{	$fld = "a_vetted_unknown_published_notVisible_uniqueGuid";
		$title = "Hidden and unvetted data objects";		
	}
	if($label == "Number of hidden and unreliable data objects")			
	{	$fld = "a_vetted_untrusted_published_notVisible_uniqueGuid";
		$title = "Hidden and unreliable data objects";			
	}
	$title .= " | <a href='display.php'>Back to Main Stats</a> &nbsp;&nbsp;&nbsp;&nbsp; <i>Note: Login as administrator in www.eol.org to see all objects</i> ";
}
																	

print " $title";

if	(	$label == "Pages with CoL names with content that requires curation"		or
		$label == "Pages NOT with CoL names with content that requires curation"
	)
{
	print"&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <font size='1'><u><b>With asterisk (*) are taxa with only un-vetted data objects</b></u></font>";
}

print "<br>";

//if($what=='resources'){print"<a href='details_do.php?autoctr=$autoctr&label=$label&what=list'>See Details</a>";}
if($what=='list')
{   print"<a href='details_do.php?autoctr=$autoctr&label=$label&what=resources'>See Resources</a> | 
    <a href='display.php'>Back to Main Stats</a> &nbsp;&nbsp;&nbsp;&nbsp; <i>Note: Login as administrator in www.eol.org to see all objects</i>";
}

if		($id_type == "dataobject")	$tbl='page_stats_dataobjects';
elseif	($id_type == "taxa")		$tbl='page_stats_taxa';

/* use to force assign
$tbl = 'page_stats';
*/

$qry="select $fld as fld from $tbl where id = $autoctr";
$sql = $mysqli->query($qry);
//print "<hr> $sql->num_rows $qry<hr>";
while( $row = $sql->fetch_assoc() )
{
	//print "<hr> $row[fld] <hr>";			
	print "<hr>";
	$arr = explode("_",$row["fld"]);
	$totrec = count($arr);

	//start batch
	if($batch == ""){$batch=0;}
	else			{$batch=$batch+$step;}		
	if($batch >= $totrec)	{	$batch=$totrec;
								$range=$totrec;
							}
	else					{	$range=$batch+$step;
							}
	
	print "n = $totrec &nbsp;&nbsp;&nbsp;";
	if($batch > 0 and $batch != $totrec){print"<a href='javascript:history.go(-1)'>Prev</a> &nbsp;&nbsp;";}
	if($batch + $step <= $totrec)
	{print"<a href='details_do.php?autoctr=$autoctr&label=$label&batch=$batch&what=$what&show_do=$show_do'>Next</a>";}		

	if($id_type == "taxa")	
	{
		print" &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ";	
		$diff = $batch-$step;
		if($show_do == 'y'){print"<a href='details_do.php?autoctr=$autoctr&label=$label&batch=" . $diff . "&what=$what&show_do=n'>Hide Data objects</a>";}
		if($show_do == 'n'){print"<a href='details_do.php?autoctr=$autoctr&label=$label&batch=" . $diff . "&what=$what&show_do=y'>Show Data objects</a>";}
	}
	
	print"<br>";
	
	//end batch
	
	print"<table border='1' cellpadding='1' cellspacing='0'  style='font-size : x-small;font-family : Arial;'>";	
	if($id_type == "taxa")	
	{
		print"
		<tr bgcolor='aqua'>
			<td>#</td>
			<td>ID</td>
			<td>Taxa</td>
		</tr>
		";
	}
	else
	{
    	print"
		<tr bgcolor='aqua'>
			<td>#</td>
			<td>Object ID</td>
			<td>Taxa</td>
			<td>Object Desc.</td>
		</tr>
		";	
	}	
	
	//start loop of tc_id or do_id	
	
	for ($i = $batch; $i < $range; $i++) //for ($i = 0; $i < 3; $i++) 
	{		
		if ($i % 2 == 0){$vcolor = 'white';}
		else		   	{$vcolor = '#ccffff';}			
		if(isset($arr[$i]))
		{		
			print"<tr valign='top' bgcolor=$vcolor >";			
			if($id_type == "taxa")	
			{	
				$str = "<a target='eol' href='http://$eol_site/pages/$arr[$i]'>$arr[$i]</a>";
				/* dec 6 commented
				$qry="Select distinct clean_names.clean_name From taxon_concepts
				Inner Join taxon_concept_names ON taxon_concepts.id = taxon_concept_names.taxon_concept_id
				Inner Join clean_names ON taxon_concept_names.name_id = clean_names.name_id
				Where taxon_concepts.id = $arr[$i] and taxon_concept_names.vern = 0
				and taxon_concept_names.preferred = 1 ";
				*/
				$qry="Select distinct clean_names.clean_name
				From clean_names
				Inner Join hierarchy_entries ON clean_names.name_id = hierarchy_entries.name_id
				Inner Join taxon_concepts ON hierarchy_entries.taxon_concept_id = taxon_concepts.id
				Where taxon_concepts.id = $arr[$i] ";
				$sql2 = $mysqli->query($qry);	
				//print"<hr>$qry<hr>";				

				if	(	$label == "Pages with CoL names with content that requires curation"		or
						$label == "Pages NOT with CoL names with content that requires curation"
					)
				{
					$check = check_proc($arr[$i]);		//print"<td>$check</td>";	
				}
				else $check="";

				print"<td>"; print $i+1 . ". </td><td align='center'>" . $str . ""; print"$check</td>";				
				
				print"<td>";while( $row2 = $sql2->fetch_assoc() ){print "<i>[$row2[clean_name]]</i> ";}print"</td>";
				$sql2->close();											
			}
			elseif($id_type == "dataobject")				
			{	$str = @$arr[$i];
				print"<td>"; print $i+1 . ". </td><td>" . $str . ""; print"</td>";

				/* dec 6 commented
				$qry="
				Select distinct taxon_concept_names.taxon_concept_id as tc_id,
				names.`string` as sn From data_objects
				Inner Join data_objects_taxa ON data_objects.id = data_objects_taxa.data_object_id
				Inner Join taxa ON data_objects_taxa.taxon_id = taxa.id
				Inner Join names ON taxa.name_id = names.id
				Inner Join taxon_concept_names ON taxon_concept_names.name_id = names.id
				Inner Join taxon_concepts ON taxon_concept_names.taxon_concept_id = taxon_concepts.id
				Where data_objects.id = $arr[$i] and taxon_concept_names.vern = 0 and taxon_concepts.supercedure_id = 0 ";
				*/
				$qry="
				Select distinct taxon_concepts.id as tc_id,
				names.`string` as sn 
				From data_objects_taxa
				Inner Join taxa ON data_objects_taxa.taxon_id = taxa.id
				Inner Join hierarchy_entries ON taxa.name_id = hierarchy_entries.name_id
				Inner Join taxon_concepts ON hierarchy_entries.taxon_concept_id = taxon_concepts.id
				Inner Join names ON taxa.name_id = names.id
				Where data_objects_taxa.data_object_id = $arr[$i]
				AND taxon_concepts.supercedure_id = 0 
				";
                
								
				//print "<hr>$qry<hr>";		
				$sql2 = $mysqli->query($qry);	
				print"<td>";				
				while( $row2 = $sql2->fetch_assoc() )			
				{	print "<i><a target='eol' href='http://$eol_site/pages/$row2[tc_id]'>$row2[sn]</a></i> ";
				}		
				print"</td>";
				$sql2->close();							
				
				
				//start show do details
				$qry="Select mime_types.label as mime, data_types.label as dt, vetted.label as vet, visibilities.label, 
				data_objects.published, data_objects.object_title as title, data_objects.source_url
				, data_objects.description
				, data_objects.visibility_id
				From data_objects
				left Join mime_types ON data_objects.mime_type_id = mime_types.id
				left Join data_types ON data_objects.data_type_id = data_types.id
				left Join vetted ON data_objects.vetted_id = vetted.id
				left Join visibilities ON data_objects.visibility_id = visibilities.id Where data_objects.id = $arr[$i] ";
				$sql2 = $mysqli->query($qry);	
				print"<td>";				
				while( $row2 = $sql2->fetch_assoc() )			
				{	
					print utf8_decode("
					<i>$row2[mime] $row2[title]</i> [$row2[description]] <a target='do_source' href='$row2[source_url]'>source</a>					
					<br>
					vetted=$row2[vet] | published=$row2[published] | visibility=$row2[label]; id=$row2[visibility_id]					
					");					
				}		
				print"</td>";				
				$sql2->close();			
				//end show do details
				
			}	
			print"</tr>";
			
		}//if(isset($arr[$i]))
		
		if( $id_type == "taxa" and $show_do == "y" )				
		{
			print"<tr><td colspan='3'>";			
		}
		
		
		
		if($id_type == "dataobject"){if(isset($arr[$i])){}}//if($id_type == "dataobject";)				
		

		if(isset($arr[$i]))
		{		
    		if($show_do == 'y')
	    	{		
		        if($id_type == "taxa")
        		{
					/*
		        	$qry="
        			Select distinct
        			taxon_concept_names.taxon_concept_id AS tc_id,
        			data_objects_taxa.data_object_id,
        			data_objects.published,
        			data_objects.object_title,
		        	data_objects.source_url,
        			data_objects.description,
        			data_objects.object_url,
        			data_objects.thumbnail_url,
        			data_types.label AS dtype_label,
        			mime_types.label AS mtype_label,
        			vetted.label AS vetted_label,
        			visibilities.label AS visib_label,
        			data_objects.license_id,
        			data_objects.rights_statement,
        			data_objects.rights_holder,
        			data_objects.bibliographic_citation,
        			data_objects.location,
        			data_objects.object_created_at,
        			data_objects.object_modified_at,
        			data_objects.data_rating,
        			data_objects.curated,
        			harvest_events.resource_id,
        			resources.title
        			From taxon_concept_names
        			Left Join names ON taxon_concept_names.name_id = names.id
        			Left Join taxa ON names.id = taxa.name_id
        			Left Join data_objects_taxa ON taxa.id = data_objects_taxa.taxon_id
        			Left Join data_objects ON data_objects_taxa.data_object_id = data_objects.id
        			Left Join data_types ON data_objects.data_type_id = data_types.id
        			Left Join mime_types ON data_objects.mime_type_id = mime_types.id
        			Left Join vetted ON data_objects.vetted_id = vetted.id
        			Left Join visibilities ON data_objects.visibility_id = visibilities.id			
        			inner Join data_objects_harvest_events ON data_objects_harvest_events.data_object_id = data_objects.id
        			inner Join harvest_events ON data_objects_harvest_events.harvest_event_id = harvest_events.id
        			inner Join resources ON harvest_events.resource_id = resources.id
        			Where taxon_concept_names.taxon_concept_id = $arr[$i] 
        			and harvest_events.id IN (".implode(",", $temp_arr).") ";
					*/
					
					$qry="
					Select distinct
        			taxon_concepts.id AS tc_id,
        			data_objects_taxa.data_object_id,
        			data_objects.published,
        			data_objects.object_title,
		        	data_objects.source_url,
        			data_objects.description,
        			data_objects.object_url,
        			data_objects.thumbnail_url,
        			data_types.label AS dtype_label,
        			mime_types.label AS mtype_label,
        			vetted.label AS vetted_label,
        			visibilities.label AS visib_label,
        			data_objects.license_id,
        			data_objects.rights_statement,
        			data_objects.rights_holder,
        			data_objects.bibliographic_citation,
        			data_objects.location,
        			data_objects.object_created_at,
        			data_objects.object_modified_at,
        			data_objects.data_rating,
        			data_objects.curated,
        			harvest_events.resource_id,
        			resources.title
					From
					data_objects_taxa
					Inner Join taxa ON data_objects_taxa.taxon_id = taxa.id
					Inner Join hierarchy_entries ON taxa.name_id = hierarchy_entries.name_id
					Inner Join taxon_concepts ON hierarchy_entries.taxon_concept_id = taxon_concepts.id
					Inner Join names ON taxa.name_id = names.id
					Inner Join data_objects ON data_objects.id = data_objects_taxa.data_object_id
					Inner Join data_types ON data_objects.data_type_id = data_types.id
					Inner Join mime_types ON data_objects.mime_type_id = mime_types.id
					Inner Join vetted ON data_objects.vetted_id = vetted.id
					Inner Join visibilities ON data_objects.visibility_id = visibilities.id
					Inner Join data_objects_harvest_events ON data_objects.id = data_objects_harvest_events.data_object_id
					Inner Join harvest_events ON data_objects_harvest_events.harvest_event_id = harvest_events.id
					Inner Join resources ON harvest_events.resource_id = resources.id

        			Where 
                    taxon_concepts.id = $arr[$i] 
        			and harvest_events.id IN (".implode(",", $temp_arr).") ";                    
                    

/*				
0 Invisible
1 Visible
2 Preview
3 Inappropriate

0 Unknown
4 Untrusted
5 Trusted
*/
    			
	        		if($label == "Approved pages awaiting publication")
			        {
        				$qry .= "
        				and data_objects.vetted_id = " . Vetted::find("trusted") . "			
        				and data_objects.published = 0							
        				and data_objects.visibility_id = " . Visibility::find('preview') . "
        				";											
		        	}
        			elseif	(	$label == "Pages with CoL names with content that requires curation" 		or 
        						$label == "Pages NOT with CoL names with content that requires curation"	
        					)
        			{
        				$qry .= "
        				and data_objects.vetted_id = " . Vetted::find("unknown") . "
        				and data_objects.published = 1			
        				and data_objects.visibility_id = " . Visibility::find('visible') . "
        				";	
        			}


			
        			//print "<hr>$qry";
        			$sql2 = $mysqli->query($qry);	

        			print"
        			<table border='1' cellpadding='1' cellspacing='0'  style='font-size : x-small;font-family : Arial;'>
        			<tr><td colspan='8'>data objects = $sql2->num_rows</td></tr>
        			<tr>
        			<td>Resource</td>
        			<td>Title</td>
        			<td>Type</td>				
        			<td>Vettedness</td>
        			<td>Visibility</td>
        			<td>Published</td>
        			<td>Description</td>
        			<td>Data object ID</td>
        			</tr>
        			";

        			while( $row2 = $sql2->fetch_assoc() )			
        			{				
        				print"
        				<tr valign='top'>
        				<td>$row2[title]</td>
        				<td>$row2[object_title]</td>
        				<td>$row2[dtype_label]</td>				
        				<td>$row2[vetted_label]</td>
        				<td>$row2[visib_label]</td>
        				<td>$row2[published]</td>
        				<td>$row2[description]</td>
        				<td>
		        			$row2[data_object_id]<br><a target='do_source' href='$row2[source_url]'>source</a>					
        				</td>
        				</tr>
        				";
        				/*
        				<td>Created</td>
        				<td>Modified</td>				
        				<td>$row2[object_created_at]</td>
        				<td>$row2[object_modified_at]</td>
        				*/				
        			}		
        			print"</table>";
        			$sql2->close();					
        		}//if($id_type == "taxa")
        	}//if($show_do == 'y')
		}//if(isset($arr[$i]))		
		//if(isset($arr[$i]))print "<hr>";				
		
		if(isset($arr[$i]))print"</td></tr>";		
		
	}//end for		
	print"</table>";
	//print "-- end --";
	
	if($batch > 0 and $batch != $totrec){print"<a href='javascript:history.go(-1)'>Prev</a> &nbsp;&nbsp;";}
	if($batch + $step <= $totrec)
	{print"<a href='details_do.php?autoctr=$autoctr&label=$label&batch=$batch&what=$what&show_do=$show_do'>Next</a>";}
	
}
//$sql->close();

/*
print"
<table cellpadding='1' cellspacing='0' border='0' style='font-size : small; font-family : Arial Unicode MS;'>
<tr><td>EoL Page Statistics 
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<i>Beta Version</i>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<font size='1'>";
print"</font>
</td></tr>
</table>
<hr>";
*/


$sql->close();

print"</td></tr></table>";


function get_val_var($v)
{
	if 		(isset($_GET["$v"])){$var=$_GET["$v"];}
	elseif 	(isset($_POST["$v"])){$var=$_POST["$v"];}	
	if(isset($var))return $var;
	else return NULL;
}//end func

function does_url_exist($url)
{
	if ($url != "") {
   	 $result = 1;
   	 if (! ereg("^https?://",$url)) {
   	     $status = "This demo requires a fully qualified http:// URL";
   	 } else {
   	     if (@fopen($url,"r")) {
   	         //$status = "This URL s readable";
			 $status = true;
   	     } else {
   	         //$status = "This URL is not readable";
			 $status = false;
   	     }
   	 }
	} else {
	    $result = 0;
	    //$status = "no URL entered (yet)";
		$status = false;
	}

	return $status;
}//end func

function check_proc($tc_id)	//checks if tc_id only has unvetted dataobjects -- using query and XML webservice
{	
	global $mysqli;
	/* Dec 6 commented
	$qry="Select distinct taxon_concept_names.taxon_concept_id,
	data_objects.id, data_objects.vetted_id, data_types.label
	From taxon_concept_names
	Inner Join names ON taxon_concept_names.name_id = names.id
	Inner Join taxa ON names.id = taxa.name_id
	Inner Join data_objects_taxa ON taxa.id = data_objects_taxa.taxon_id
	Inner Join data_objects ON data_objects_taxa.data_object_id = data_objects.id
	Inner Join data_types ON data_objects.data_type_id = data_types.id
	Where taxon_concept_names.taxon_concept_id = $tc_id and
	data_objects.data_type_id not in (5,6) and data_objects.vetted_id <> 0 ";			
	*/	
	$qry="Select distinct taxon_concepts.id as taxon_concept_id,
	data_objects.id, data_objects.vetted_id, data_types.label
	From taxon_concepts
	Inner Join hierarchy_entries ON taxon_concepts.id = hierarchy_entries.taxon_concept_id
	Inner Join taxa ON hierarchy_entries.id = taxa.hierarchy_entry_id
	Inner Join data_objects_taxa ON taxa.id = data_objects_taxa.taxon_id
	Inner Join data_objects ON data_objects_taxa.data_object_id = data_objects.id
	Inner Join data_types ON data_objects.data_type_id = data_types.id
	Where taxon_concepts.id = $tc_id and
	data_objects.data_type_id not in (5,6) and data_objects.vetted_id <> " . Vetted::find("unknown");
	
	$sql = $mysqli->query($qry);	
	$first = $sql->num_rows;	//if > 0 then it has vetted records
	$sql->close();
	
	$url = "http://www.eol.org/pages/$tc_id/images/1.xml";	
	
	//print "<hr>" . does_url_exist($url) . "<hr>";		
	
    //if(@parse_url($url))
	if(does_url_exist($url))
    {
		if(simplexml_load_file($url))
		{
			$xml = simplexml_load_file($url);
			$second = $xml->xpath("num-images");		     
		}
		else $second[0]="";	//if blank then xml file is not found
    }		
	else $second[0]="";	//if blank then xml file is not found
	
	$sec = $second[0];	//if number then compare it with $first; 
	
	///////////////////////////////////////////
	if($first == 0)
	{
		if($sec == "")$ret = "No"; //$ret = "not k";
		else
		{
			if($sec <= $first)$ret = "Yes";//$ret = "k";
			else $ret = "No";//$ret = "not k";
		}
	}
	else $ret = "No";//$ret = "not k";
		
	//return "$first - $sec - $ret";	

	if($ret == "Yes")$ret="*";
	else $ret="";
	return $ret;
	
}//end func


?>


