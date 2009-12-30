<?php

//define("ENVIRONMENT", "slave_215");
//define("MYSQL_DEBUG", true);
//define("DEBUG", true);
require_once("../../config/start.php");
$mysqli =& $GLOBALS['mysqli_connection'];


//$mysqli = slave_conn();

set_time_limit(0);

$eol_site = "www.eol.org";
//$eol_site = "app1.eol.org";

$report = 'list';	//original functionality
//$report = 'stat';

$tbl = "clean_names";	//instead of 'names'
$fld = "clean_name";	//instead of 'string'
$fld_id = "name_id";	//instead of 'id'

/*
Acarospora immersa
Accipiter cooperii
Accipiter gentilis
*/

$list 			= get_val_var('list');

$separator 		= get_val_var('separator');
$choice 			= get_val_var('choice');
$choice2 			= get_val_var('choice2');
$data_kind 		= get_val_var('data_kind');

$withCSV 			= get_val_var('withCSV');

$format 			= get_val_var('format');	//1 = info items		2 = objects title

//exit("$withCSV");

if($choice2 == 1){$head = "Found in EoL";}
if($choice2 == 2){$head = "Not found in EoL";}
if($choice2 == 3){$head = "With data objects";}
if($choice2 == 4){$head = "Without data objects";}
if($choice2 == 5){$head = "Find 'Family'";}

$with_name_source 			= get_val_var('with_name_source');
//print $with_name_source;
//exit;

if(trim($choice) == ""){
print"<i>Please paste your list of names inside the box. <br>Select a filter and separator then click 'Submit'.</i>";
exit;
}

$rd	= "";	//row data
$cr 	= "\n";

$tab = chr(9);

$rd .= $head . $cr;

//exit($choice);
if($separator == '')
{
	switch (true)
	{
	case $choice == 1:  $separator = chr(13);break;
	case $choice == 2:  $separator = chr(10);break;	
	case $choice == 3:  $separator = chr(9);break;	
	case $choice == 4:  $separator = ',';break;	
	default:break;
	}	
}	
	
//print $list;



//$separator = chr(13);

$list = $separator . $list;	//weird behavior - first char must be the separator

$arr = explode("$separator", $list);	
$orig_lenth_of_arr = count($arr);	
$arr = array_unique($arr);	//print "<hr>";	print_r($arr);	//exit;
$arr = array_trim($arr,$orig_lenth_of_arr);	// $orig_lenth_of_arr --- this is the length of array after explode function
sort($arr); //ksort($arr);




print "<font size='2' face='courier'>Total no. of names submitted: " . " " . count($arr) . "</font><hr>";
if(count($arr) == 0){exit;}
//exit;
print"<table cellpadding='3' cellspacing='0' border='1' style='font-size : small; font-family : Arial Unicode MS;'>";

$us = "&#153;";	//unique separator
$value_list="";
for ($i = 0; $i < count($arr); $i++) 
{
	/* list of entries
	print"
	<tr><td>
	$arr[$i]
	</td></tr>";
	*/	
	
	$tempx = $arr[$i];
	$arr[$i] = Functions::clean_name($arr[$i]);
	//print "<br>clean name = " . $arr[$i];	
	if($i==0)	$value_list .= "'" 		. Functions::canonical_form($arr[$i]) . "'";
	else		$value_list .= "$us'" 	. Functions::canonical_form($arr[$i]) . "'";
	
	$arr[$i] = $tempx;		
	 /*
	$canonical_form = Functions::canonical_form($arr[$i]);
	print "<br>canonical form = " . $canonical_form . "<br>";
	 */

}


$qry = sql_do($value_list,$choice2,$us);
$sql = $mysqli->query($qry);	
//print $qry;
//print check_err($mysqli,$sql,$qry);
	
	
$vsn="";
$i=0;
$value_list_y="";
	
	
$oldLabel="";
$oldSN="";
while( $row = $sql->fetch_assoc() )
{
		
	//if(1==1)
	if($oldLabel != $row["label"] or $oldSN != $row["sn"])
	{
		if($i==0)
		{
			if($choice2 == 3 or $choice2 == 1)	//exist in eol and with data objects
			{
			print"<tr><form name='fn'><td colspan='2'>$head ";
			?>&nbsp;&nbsp;&nbsp;
			n=<input name="cnt" type="text">
			<?php
			print"</td></form></tr>";
			}
		}

		if($i==0)
		{$value_list_y .= "'" . trim($row["sn"]) . "'";}
		else
		{$value_list_y .= "$us'" . trim($row["sn"]) . "'";}	
		$i++;

		if($choice2==3 or $choice2==1 or $choice2==5)
		{
			print"<tr>";
			//if(1==1)	//if you want to see all scinames repeated
			if($vsn != $row["sn"])
			{				
				//$tmp = "<a target='_blank' href='http://$eol_site/search?q=" . urlencode($row["sn"]) . "&search_image=&search_type=text'>$row[sn]</a>";
				$tmp = "<a target='_blank' href='http://$eol_site/pages/$row[tc_id]'>$row[sn]</a>";			
				if	(
						($data_kind == 1 and $choice2 == 3)	or
						($choice2 == 1 and $with_name_source == '')	or
						($choice2 == 5)
					) 	
				{	$rd .= $row["sn"];				
					$rd .= $tab . " http://$eol_site/pages/$row[tc_id]";	
					$rd .= $cr;
				}
				else									
				{	$rd .= $row["sn"] . $tab;				
					$rd .= " http://$eol_site/pages/$row[tc_id]" . $tab;
				}	//ditox
			
				print"<td><i>$tmp</td>";
			}
			else{print"<td></td>";}
			
			if($choice2==5)	//for family search
			{
				print"<td>$row[tc_id]</td>";
				
				/* Dec13 not use taxon_concept_names
				$qry="Select distinct
				taxa.taxon_kingdom, taxa.taxon_phylum, taxa.taxon_class, taxa.taxon_order,
				taxa.taxon_family, taxa.scientific_name, $tbl.$fld as string
				From taxon_concept_names Inner Join $tbl ON taxon_concept_names.name_id = $tbl.$fld_id 
				Left Join taxa ON $tbl.$fld_id = taxa.name_id
				Where taxon_concept_names.taxon_concept_id = '$row[tc_id]' 
				and taxon_concept_names.vern = 0 ";
				*/
				$qry="Select distinct
				taxa.taxon_kingdom, taxa.taxon_phylum, taxa.taxon_class, taxa.taxon_order,
				taxa.taxon_family, taxa.scientific_name, $tbl.$fld as string
				From taxa
				Inner Join clean_names ON taxa.name_id = clean_names.name_id
				Inner Join hierarchy_entries ON clean_names.name_id = hierarchy_entries.name_id
				Inner Join taxon_concepts ON hierarchy_entries.taxon_concept_id = taxon_concepts.id
				Where taxon_concepts.id = '$row[tc_id]' ";
				$qry .= " AND taxa.taxon_family <> '' limit 1 ";
				$sql2 = $mysqli->query($qry);
				print"<td>";
				while( $row2 = $sql2->fetch_assoc() )
				{
					//print "$row2[taxon_family] - $row2[scientific_name] - $row2[string]";
					print "$row2[taxon_family]";
					$rd .= "$row[sn]" . $tab . $row2["taxon_family"] . $cr; //ditox
				}
				print"</td>";
				$sql2->close();								
			}// end for family search
						
				
			if	(	($with_name_source != "" or $choice2 == 3)	and
					$choice2 != 5
					)
			{
				print"
				<td>$row[label]</td>";				
				$rd .= $tab . $row["label"] . $cr; //ditox
			}
				
			print"
			</tr>
			";		
			
		}	
		
		$vsn=$row["sn"];

		}//if($oldLabel != $row[label])
		$oldLabel = $row["label"];
		$oldSN = $row["sn"];
	}
	$sql->close();	
	
	/*
	$us = "&#153;";	//unique separator
	$value_list_y 	= str_ireplace(",", $us, $value_list_y);
	$value_list 	= str_ireplace(",", $us, $value_list);
	*/
	
	$arr = explode("$us",$value_list_y);
	$arr = array_unique($arr);					

	if($choice2==3 or $choice2==1)
	{
    	print"
    	<script language='javascript1.2'>
    	document.forms.fn.cnt.value = " . "n=" . count($arr) . "
    	</script>";
	}

	$value_list_y = implode("$us", $arr);

	

	$arr1 = explode("$us",$value_list_y);
	$arr2 = explode("$us",$value_list);
	$arr3 = array_diff($arr2,$arr1);

	// one package	
	asort($arr3);
	$value_list_final = implode("$us", $arr3);	
	$arr3 = explode("$us",$value_list_final);
	// end one package
	$orig_lenth_of_arr = count($arr3);
		
	/*
	print"
	<tr><td>$value_list</td></tr>
	<tr><td>$value_list_y</td></tr>
	<tr><td>$value_list_final</td></tr>
	<tr><td><hr></td></tr>
	";
	*/	

	$RNWDO=array();	//related names with data objects
	$RNWDO_i=0;
	//if($choice2==4 or $choice2==2)
	if($choice2==4 or $choice2==2 or $choice2==3)//eli new feb25
	{
	
		//$arr3 = array_trim($arr3); 
		$arr3 = array_trim($arr3,$orig_lenth_of_arr); 
	
		//print"<tr><td align='center'>$head &nbsp;&nbsp;&nbsp; n=" . count($arr3) . "<input type='hidden' id='cnt2' ></td>";
		print"<tr><input type='hidden' id='cnt2' >";
		
		if($choice2 == 3)
		{
			print"<td align='center' colspan='2'>With related names having data objects</td>";
			$rd .= $cr . "With related names having data objects" . $cr;
		}
			
		print"</tr>";
		$cnt2=0;
		for ($i = 0; $i < count($arr3); $i++) 
		{
			$tmp = str_ireplace("'","",$arr3[$i]);			
			
			$tmp_sn = $tmp;
			
			/* transferred below
			$rd .= $tmp . $cr;				
			*/
			
			$tmp = "<a target='_blank' href='http://$eol_site/search?q=" . urlencode($tmp) . "&search_image=&search_type=text'>$tmp</a>";
            
            /*			
			if($choice2 == 4)
			{
				print"<tr><td> j <i>$tmp</i></td></tr>";		
			}
            */			
			
			//exit("<hr>[$tmp_sn] - $tmp - " . count($arr3));
			
			//start cyndy request
			$sn_list = get_sn_list("$tmp_sn");
			$qry = sql_do($sn_list,$choice2,$us);

			
			//print "$sn_list";
			if($sn_list != "")
			{
							
				$sql2 = $mysqli->query($qry);										
				
				if($choice2 == 3 or $choice2 == 4)	//With data objects	| Without data objects
				{
				
					
					if($sql2->num_rows != 0)
					{
						if($choice2 == 3){print"<tr><td><i>$tmp</i></td></tr>";$rd .= $tmp_sn . $cr;}
						
						$RNWDO[$RNWDO_i]="'$tmp_sn'"; $RNWDO_i++;
					
						$cnt2++;
						if($choice2 == 3)	//With data objects	
						{
						print"<tr><td></td><td>
						<table cellpadding='3' cellspacing='0' border='1' style='font-size : small; font-family : Arial Unicode MS;'>";
						}
						
						$oldLabel="";
						while( $row2 = $sql2->fetch_assoc() )
						{
							if($oldLabel != $row2["label"])
							{

							
							$orig_sn = $row2["sn"];
							//$sn = "<a target='_parent' href='http://$eol_site/search?q=" . urlencode($row2["sn"]) . "&search_image=&search_type=text'>$row2[sn]</a>";
							
							$sn = "<a target='_blank' href='http://$eol_site/pages/$row2[tc_id]'>$row2[sn]</a>";
							
							if($choice2 == 3)	//With data objects	
							{
								print"
								<tr>
									<td><i>$sn</i></td>
									<td>$row2[label]</td>
								</tr>
								";								
								$rd .= $tab . "$orig_sn" . $tab . "$row2[label]";				//ditox								
								
								$rd .= $tab . "http://$eol_site/pages/$row2[tc_id]";
								
								$rd .= $cr;	
							}
							
							}//if($oldLabel != $row2["label"])
							$oldLabel = $row2["label"];
						}	
						if($choice2 == 3)
						{
							print"</table></td></tr>";
						}
					}
				}
				
				//print " -- $sql2->num_rows ";
				
				/*
				if($choice2 == 4)//without data objects
				{
					if($sql2->num_rows == 0){print"<tr><td> jjj <i>$tmp</i></td></tr>";}
				}
				*/
				//print"<tr><td> jjjj <i>$tmp</i></td></tr>";
				
				$sql2->close();

			}//if($sn_list != "")
						
			//end cyndy request
			
			
		}//end for
		print"
		<script language='javascript1.2'>
		document.getElementById('cnt2').value = $cnt2		
		</script>";


	if($choice2 == 4 or $choice2 == 2)
	{	
		$arr = array_diff($arr3,$RNWDO);	
		
		// one package after array_diff
		asort($arr);
		$value_list_final = implode("$us", $arr);	
		$arr = explode("$us",$value_list_final);
		
		$orig_lenth_of_arr = count($arr);		
		//$arr=array_trim($arr); 
		$arr=array_trim($arr,$orig_lenth_of_arr); 
		// end one package after array_diff
		
		print"<tr><td>$head &nbsp;&nbsp; n = " . count($arr) . "</td></tr>";
		
		$rd .= "$cr $head n = " . count($arr) . $cr;
		
		//exit;
		for ($i = 0; $i < count($arr); $i++)
		{
			$arr[$i] = str_ireplace("'","",$arr[$i]);			
			print"<tr><td><i>$arr[$i]</i></td></tr>";
			$rd .= $arr[$i] . $cr;
		}	

		
		print"
		<script language='javascript1.2'>
		document.getElementById('cnt2').value = " . count($arr) . "		
		</script>";
		
		
	}
	
	if($choice2 == 1 or $choice2 == 3)
	{
		?>
		
		<script language="javascript1.2">
		document.forms.fn.cnt.value = eval(Number(document.forms.fn.cnt.value) + Number(document.getElementById('cnt2').value));
		//alert('eli');
		</script>
		
		<?php
	}
			
}	
	
	
print "</table>";
?>



<?php
function sql_do($val,$i,$us)	
{
	//print"<hr>[$val][$us][$i]<hr>";
	/*
		$val = comma separated scientific names
		$i 	 = 1 or 2 -- exist in eol
			 = 3 or 4 -- with data object
		$us  = unique separator
	*/
	global $with_name_source;
	global $data_kind;
	global $withCSV;
	
	global $report;
	global $format;
	
	global $tbl,$fld,$fld_id;
	
	$val = str_ireplace("$us" , "," , $val);


	if($i == 5)	//family search
	{
		//print"<hr>111<hr>";
		/* dec13 not use taxon_concept_names
		$qry = "Select distinct $tbl.$fld as sn,
		taxon_concept_names.taxon_concept_id as tc_id
		From $tbl Inner Join taxon_concept_names ON $tbl.$fld_id = taxon_concept_names.name_id
		Where $tbl.$fld In ($val)
		Order By $tbl.$fld Asc ";
		*/
		$qry = "Select distinct $tbl.$fld as sn,
		taxon_concepts.id as tc_id
		From taxa
		Inner Join clean_names ON taxa.name_id = clean_names.name_id
		Inner Join hierarchy_entries ON clean_names.name_id = hierarchy_entries.name_id
		Inner Join taxon_concepts ON hierarchy_entries.taxon_concept_id = taxon_concepts.id
		Where $tbl.$fld In ($val)
		Order By $tbl.$fld Asc ";		
	}
	
	if($i == 3 or $i == 4)	//data objects
	{	

		if($data_kind==1)	//text
		{
			if($format==1)
			{
				//print"<hr>222<hr>";
				// /*	// working well for specific info_items label (main topics)
				 /* Dec13 not use taxon_concept_names
				$qry = "Select distinct info_items.label, $tbl.$fld as sn, taxon_concept_names.taxon_concept_id as tc_id
				From data_objects_taxa
				Inner Join taxa ON data_objects_taxa.taxon_id = taxa.id
				Inner Join data_objects ON data_objects_taxa.data_object_id = data_objects.id
				Inner Join data_objects_info_items ON data_objects_info_items.data_object_id = data_objects.id
				Inner Join info_items ON data_objects_info_items.info_item_id = info_items.id
				Inner Join $tbl ON taxa.name_id = $tbl.$fld_id
				Inner Join taxon_concept_names ON $tbl.$fld_id = taxon_concept_names.name_id
				Where $tbl.$fld In ($val)
				Order By $tbl.$fld Asc, info_items.label Asc
				"; 
				 */
				
				// /*				
				$qry="
				Select distinct info_items.label, $tbl.$fld as sn, taxon_concepts.id as tc_id
				From taxa
				Inner Join clean_names ON taxa.name_id = clean_names.name_id
				Inner Join hierarchy_entries ON clean_names.name_id = hierarchy_entries.name_id
				Inner Join taxon_concepts ON hierarchy_entries.taxon_concept_id = taxon_concepts.id
				Inner Join data_objects_taxa ON taxa.id = data_objects_taxa.taxon_id
				Inner Join data_objects_info_items ON data_objects_taxa.data_object_id = data_objects_info_items.data_object_id
				Inner Join info_items ON data_objects_info_items.info_item_id = info_items.id
				Where $tbl.$fld In ($val)
				Order By $tbl.$fld Asc, info_items.label Asc				
				";
				// */
			}

			//data_types.label	
			//data_objects.object_title as label	
		
			if($format==2)		
			{
				//print"<hr>333<hr>";				
				//working for specific data_objects title
				$addstr = '';
				if($report == 'list'){$addstr = ' distinct ';}		
				/*Dec13 not use taxon_concept_names
				$qry = "Select $addstr $tbl.$fld AS sn, taxon_concept_names.taxon_concept_id as tc_id,
				if(data_objects.object_title='',data_types.label,data_objects.object_title) AS label
				From data_objects_taxa 
				Inner Join taxa ON data_objects_taxa.taxon_id = taxa.id 
				Inner Join data_objects ON data_objects_taxa.data_object_id = data_objects.id 
				Inner Join taxon_concept_names ON taxon_concept_names.name_id = taxa.name_id 
				Inner Join $tbl ON $tbl.$fld_id = taxon_concept_names.name_id 
				Inner Join data_types ON data_objects.data_type_id = data_types.id				
				Where $tbl.$fld In ($val)
				AND data_objects.published = 1 AND data_objects.visibility_id = " . Visibility::find("visible") . " 
                AND data_objects.vetted_id != " . Vetted::find("unknown") . " 
				";
                
                Vetted::find("trusted")
                Visibility::find("visible")
                DataType::find("http://purl.org/dc/dcmitype/Text")
                DataType::find("http://purl.org/dc/dcmitype/StillImage")
                DataType::find("http://purl.org/dc/dcmitype/MovingImage")
                
                
				*/

				$qry="Select $addstr $tbl.$fld AS sn, taxon_concepts.id as tc_id,
				if(data_objects.object_title='',data_types.label,data_objects.object_title) AS label			

    			From taxa
	        	Inner Join clean_names ON taxa.name_id = clean_names.name_id
    			Inner Join hierarchy_entries ON clean_names.name_id = hierarchy_entries.name_id
    			Inner Join taxon_concepts ON hierarchy_entries.taxon_concept_id = taxon_concepts.id
    			Inner Join data_objects_taxa ON taxa.id = data_objects_taxa.taxon_id
    			Inner Join data_objects ON data_objects_taxa.data_object_id = data_objects.id
    			Inner Join data_types ON data_objects.data_type_id = data_types.id

				Where $tbl.$fld In ($val)
				AND data_objects.published = 1 AND data_objects.visibility_id = " . Visibility::find("visible") . " 
                AND data_objects.vetted_id != " . Vetted::find("unknown") . " 
				";				
				if($report == 'list'){$qry .= " and data_types.id in (" . DataType::find("http://purl.org/dc/dcmitype/Text") . ") ";}
				$qry .= " Order By $tbl.$fld , label ";	
                
                //print "[$qry]";
                
			}
			//print $qry;					
		
		}
		elseif($data_kind==2)	//image
		{
			//print"<hr>444<hr>";
			/*Dec13 not to use taxon_concept_names
			$qry = "Select distinct $tbl.$fld AS sn, taxon_concept_names.taxon_concept_id as tc_id, data_types.label
			From data_objects_taxa 
			Inner Join taxa ON data_objects_taxa.taxon_id = taxa.id 
			Inner Join data_objects ON data_objects_taxa.data_object_id = data_objects.id 
			Inner Join taxon_concept_names ON taxon_concept_names.name_id = taxa.name_id 
			Inner Join $tbl ON $tbl.$fld_id = taxon_concept_names.name_id 
			Inner Join data_types ON data_objects.data_type_id = data_types.id
			Where $tbl.$fld In ($val) AND data_objects.published = 1 AND data_objects.visibility_id = " . Visibility::find("visible") . " 
            AND data_objects.vetted_id != " . Vetted::find("unknown") . "
			and data_types.id in (" . DataType::find("http://purl.org/dc/dcmitype/StillImage") . "," . DataType::find("http://purl.org/dc/dcmitype/MovingImage") . ")
			Order By $tbl.$fld, label ";	
			*/
			///*Dec13
			$qry="Select distinct $tbl.$fld AS sn, taxon_concepts.id as tc_id, data_types.label
			From taxa
			Inner Join clean_names ON taxa.name_id = clean_names.name_id
			Inner Join hierarchy_entries ON clean_names.name_id = hierarchy_entries.name_id
			Inner Join taxon_concepts ON hierarchy_entries.taxon_concept_id = taxon_concepts.id
			Inner Join data_objects_taxa ON taxa.id = data_objects_taxa.taxon_id
			Inner Join data_objects ON data_objects_taxa.data_object_id = data_objects.id
			Inner Join data_types ON data_objects.data_type_id = data_types.id
			Where $tbl.$fld In ($val)
			AND data_objects.published = 1 AND data_objects.visibility_id = " . Visibility::find("visible") . " 
            AND data_objects.vetted_id != " . Vetted::find("unknown") . "
			and data_types.id in (" . DataType::find("http://purl.org/dc/dcmitype/StillImage") . "," . DataType::find("http://purl.org/dc/dcmitype/MovingImage") . ")
			Order By $tbl.$fld, label			
			";
			//clean
			//*/
		}
	}
	
	if($i == 1 or $i == 2)	//exist in eol
	{	
		//print"<hr>555<hr>";
		//print"[$val]";
		/*
		aeshna umbrosa umbrosa
		aeshna umbrosa umbrosa walker 1908		
		*/	
		
		// /*Dec13 not to use taxon_concept_names
		$qry = "
		Select distinct hierarchies.label, $tbl.$fld AS sn, taxon_concepts.id as tc_id
		From $tbl
		Inner Join taxon_concept_names ON taxon_concept_names.name_id = $tbl.$fld_id
		Inner Join hierarchy_entries ON taxon_concept_names.taxon_concept_id = hierarchy_entries.taxon_concept_id
		Inner Join hierarchies ON hierarchy_entries.hierarchy_id = hierarchies.id
		Inner Join taxon_concepts ON taxon_concepts.id = taxon_concept_names.taxon_concept_id
		Where $tbl.$fld In ($val) 
		AND taxon_concepts.vetted_id <> " . Vetted::find("untrusted") . " 		
		Order By $tbl.$fld Asc, hierarchies.label Asc
		";
		// */
		//AND hierarchy_entries.hierarchy_id = 147
		
		 /*Dec13
		$qry="Select distinct hierarchies.label, $tbl.$fld AS sn, taxon_concepts.id as tc_id
		From hierarchy_entries
		Inner Join clean_names ON hierarchy_entries.name_id = clean_names.name_id
		Inner Join hierarchies ON hierarchy_entries.hierarchy_id = hierarchies.id
		Inner Join taxon_concepts ON hierarchy_entries.taxon_concept_id = taxon_concepts.id
		and taxon_concepts.vetted_id <> " . Vetted::find("untrusted") . "
		Where $tbl.$fld In ($val)		
		Order By $tbl.$fld Asc, hierarchies.label Asc		
		";	
		 */
		//and hierarchy_entries.hierarchy_id = 147
		//
		
		if($with_name_source == "")	
		{
			//print"<hr>777<hr>";
			// /*Dec13 not to use taxon_concept_names
			$qry = "Select distinct $tbl.$fld AS sn, '' as label , taxon_concepts.id as tc_id
			From $tbl
			Inner Join taxon_concept_names ON taxon_concept_names.name_id = $tbl.$fld_id
			Inner Join hierarchy_entries ON taxon_concept_names.taxon_concept_id = hierarchy_entries.taxon_concept_id
			Inner Join taxon_concepts ON taxon_concepts.id = taxon_concept_names.taxon_concept_id
			Where $tbl.$fld In ($val)
			AND taxon_concepts.vetted_id <> " . Vetted::find("untrusted") . "
			Order By sn Asc
			";
			// */
			 //AND hierarchy_entries.hierarchy_id = 147
			
			 /*
			$qry="Select distinct $tbl.$fld AS sn, '' as label , taxon_concepts.id as tc_id
			From
			hierarchy_entries
			Inner Join clean_names ON hierarchy_entries.name_id = clean_names.name_id
			Inner Join hierarchies ON hierarchy_entries.hierarchy_id = hierarchies.id
			Inner Join taxon_concepts ON hierarchy_entries.taxon_concept_id = taxon_concepts.id
			Where $tbl.$fld In ($val)			
			AND taxon_concepts.vetted_id <> " . Vetted::find("untrusted") . "
			Order By sn Asc			
			";
			 */			
			//AND hierarchy_entries.hierarchy_id = 147
		}
	}	
	return $qry;
}//end function



if($withCSV=='on')
{
	$fileidx = time();
	//$filename ="../../temp/names_lookup/" . $fileidx . ".csv"; 
	$filename ="temp/" . $fileidx . ".txt"; 
	$fp = fopen($filename,"a"); // $fp is now the file pointer to file $filename
	if($fp)
	{
		fwrite($fp,$rd);    //    Write information to the file
	    fclose($fp);        //    Close the file
	    echo "<hr><i>Use a spreadsheet to open the tab-delimited TXT file created for ";
		print "<a target='_blank' href=$filename> - Download - </a>
		</i>
		";
		/*
		print "
		<br><br>Right click on the 'Download' link above and 'save target as'";
		*/
	} 
	else 
	{
		echo "Error saving file!";
	}
}


function check_err($conn,$rset,$qry)
{
	$temp='';
	if($conn->affected_rows == -1)
	{
		$temp = "
		<hr>
			$qry							<br>
			[$conn->affected_rows]			<br>		
			[$conn->error]					<br> 
		<hr>
		";

		//exit("<hr>stopped due to sql error... pls investigate");
		/*
			[" . mysql_error($conn) . "] 	<br>
			[" . mysql_error($rset) . "] 	<br>
			[" . mysql_errno($conn) . "] 	<br>
			[" . mysql_errno($rset) . "] 	<br>
		
		*/
	}
	return $temp;
}

function get_val_var($v)
{
	if 		(isset($_GET["$v"])){$var=$_GET["$v"];}
	elseif 	(isset($_POST["$v"])){$var=$_POST["$v"];}
	
	if(isset($var))
	{
		return $var;
	}
	else	
	{
		return NULL;
	}	
}


function get_sn_list($sn)
{
	global $mysqli;
	global $tbl,$fld,$fld_id;
	
	$string = $sn;
	//Aus bus subsp. cus Linnaeus 2009

	$canonical_form = Functions::canonical_form($string);

	//echo "Orignal: $string<br>";
	//echo "Canonical: $canonical_form<br>";

	/*Dec13 not to use taxon_concept_names
	$query = "select distinct tcn.taxon_concept_id 
	from $tbl n join taxon_concept_names tcn 
	on (n.$fld_id=tcn.name_id) 
	where n.$fld='$string'
	";
	*/

	$query = "select distinct taxon_concepts.id 
	From taxa
	Inner Join clean_names ON taxa.name_id = clean_names.name_id
	Inner Join hierarchy_entries ON clean_names.name_id = hierarchy_entries.name_id
	Inner Join taxon_concepts ON hierarchy_entries.taxon_concept_id = taxon_concepts.id			
	where clean_names.$fld='$string'
	";
	
	$sql = $mysqli->query($query);
	$row = $sql->fetch_row();			
	$id   = $row[0];
	$sql->close();

	//echo "id = $id";
	/*Dec13 not to use taxon_concept_names
	$query="
	Select distinct $tbl.$fld as string
	From $tbl Inner Join taxon_concept_names ON taxon_concept_names.name_id = $tbl.$fld_id
	Where taxon_concept_names.taxon_concept_id = '$id' AND
	taxon_concept_names.vern = '0'
	Order By $tbl.$fld Asc
	";
	*/
	$query = "Select distinct $tbl.$fld as string
	From taxa
	Inner Join clean_names ON taxa.name_id = clean_names.name_id
	Inner Join hierarchy_entries ON clean_names.name_id = hierarchy_entries.name_id
	Inner Join taxon_concepts ON hierarchy_entries.taxon_concept_id = taxon_concepts.id			
	Where taxon_concepts.id = '$id'
	Order By $tbl.$fld Asc
	";
	
	
	
	$sql = $mysqli->query($query);
	$str_list="";
	while( $row = $sql->fetch_assoc() )
	{
		$string = str_replace("'", 	"&#039;", $row["string"]);
		
		if($str_list == ""){$str_list = "'$string'";}
		else
		{
			$str_list .= ", '$string'";
		}
	}
	$sql->close();

	return $str_list;

}//end function

function array_trim($a,$len) 
{ 	
	$b=array();
	$j = 0; 
	//print "<hr> -- "; print count($a); print "<hr> -- ";
	for ($i = 0; $i < $len; $i++) 
	{ 
		if (array_key_exists($i,$a))
		{
			if (trim($a[$i]) != "") { $b[$j++] = $a[$i]; } 		
		}
	} 	
	return $b; 
}
//end



?>


