<?php

//uses NAMES table


//define("ENVIRONMENT", "development");
//define("ENVIRONMENT", "slave_32");
//define("MYSQL_DEBUG", true);
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

$sep = chr(9);
/*
Acarospora immersa
Accipiter cooperii
Accipiter gentilis
*/

//require_once '../../mtce/db.connect.php';
//require_once '../../mtce/db.config.php';		replaced by patrick's code base


$list 			= get_val_var('list');

$separator 			= get_val_var('separator');
$choice 			= get_val_var('choice');
$choice2 			= get_val_var('choice2');
$data_kind 			= get_val_var('data_kind');

$withCSV 			= get_val_var('withCSV');

$format 			= get_val_var('format');	//1 = info items		2 = objects title

//exit("$withCSV");

if($choice2 == 1){$head = "Found in EoL";}
if($choice2 == 2){$head = "Not found in EoL";}
if($choice2 == 3){$head = "With data objects";}
if($choice2 == 4){$head = "Without data objects";}
if($choice2 == 5){$head = "Find 'Family'";}

$head="Results:";

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


$rd .= "Name $tab EoL $tab CoL $tab Data objects $tab Images $tab Trusted images $tab Text $tab Trusted text $cr";



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

//print "<hr>$list";


// /*
//$list = " eli " . $separator . $list;	//weird behavior - first char must be the separator
// */
//print "<hr>$list";
//exit;

	$arr = explode("$separator", $list);	
	
	/*
	$eli = implode(",", $arr);	
	print $eli;
	exit;
	*/
	
	
	$orig_lenth_of_arr = count($arr);	
	$arr = array_unique($arr);	//print "<hr>";	print_r($arr);	//exit;
	$arr = array_trim($arr,$orig_lenth_of_arr);	// $orig_lenth_of_arr --- this is the length of array after explode function
	sort($arr); //ksort($arr);


print "<font size='2' face='courier'>Total no. of names submitted: " . " " . count($arr) . "</font><hr>";
if(count($arr) == 0){exit;}
//exit;
//print"<table cellpadding='3' cellspacing='0' border='1' style='font-size : small; font-family : Arial Unicode MS;'>";

$us = "&#153;";	//unique separator
$value_list="";



print"<table cellpadding='3' cellspacing='0' border='1' style='font-size : x-small; font-family : Arial Unicode MS;'>	
<tr align='center'>
	<td>Name</td>
	<td>EoL</td>
	<td>CoL</td>
	<td>Data objects</td>
	<td>Images</td>
	<td>Trusted images</td>
	<td>Text</td>
	<td>Trusted text</td>

</tr>	
";
//			<td>Stats</td>

$oldsn="";	
$tbl_color='white';
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
	 
	if($i==0)
	{	//$value_list .= "'" . trim($arr[$i]) . "'";
		$value_list .= "'" . Functions::canonical_form($arr[$i]) . "'";
	}
	else
	{	//$value_list .= "$us'" . trim($arr[$i]) . "'";
		$value_list .= "$us'" . Functions::canonical_form($arr[$i]) . "'";
	}	
	
	//start proc

	$string = $arr[$i];
	
	$arr[$i] = $tempx;
	//Aus bus subsp. cus Linnaeus 2009
	$canonical_form = Functions::canonical_form($string);

	 /*
	echo "Orignal: $string<br>";
	echo "Canonical: $canonical_form<br>";
	 */
	
	
	$string = $canonical_form;
	//print"<hr>111<hr>";
	// /* //dec 7 commented
	$qry="select distinct tcn.taxon_concept_id as id
	From clean_names AS n
	Inner Join taxon_concept_names AS tcn ON (n.name_id = tcn.name_id)
	Inner Join taxon_concepts ON tcn.taxon_concept_id = taxon_concepts.id
	where n.$fld='$string'
	and taxon_concepts.published = 1
	and taxon_concepts.vetted_id = 5	
	and taxon_concepts.supercedure_id = 0
	Order By id Asc	";
	// */
	
	/* doesn't work if u will not use taxon_concept_names
	$qry="select distinct taxon_concepts.id
	From
	hierarchy_entries
	Inner Join clean_names ON hierarchy_entries.name_id = clean_names.name_id
	Inner Join hierarchies ON hierarchy_entries.hierarchy_id = hierarchies.id
	Inner Join taxon_concepts ON hierarchy_entries.taxon_concept_id = taxon_concepts.id
	where clean_names.clean_name ='$string'			
	Order By id Asc	";
	//and tc.published = 1
	//and tc.vetted_id = 5	
	//and taxon_concepts.supercedure_id = 0
	*/
	
	
//0-Unknown , 4-Untrusted , 5-Trusted	
	
	$sql = $mysqli->query($qry);
	$in_str="";

	//print"<hr>$qry<hr>";
	//print "<hr>total no. of tc_id = $sql->num_rows<hr>";	
	
	$cnt=0;	
	
	while( $row = $sql->fetch_assoc() )
	{
		
		//print "[$oldsn] ";
				
		if($oldsn != $arr[$i])	{$tbl_color=change_color($tbl_color);}
	
	
		$cnt++;
		$id = $row["id"];
		//print " $id - ";
		if($in_str=="")	{$in_str = $id;}
		else			{$in_str .= "," . $id;}		
		

		$temp = proc_tc_id($id); 
		$arr2 = explode(",",$temp);
		$objects_cnt		= $arr2[0];
		$images_cnt			= $arr2[1];
		$images_trusted_cnt	= $arr2[2];		
		$text_cnt			= $arr2[3];
		$text_trusted_cnt	= $arr2[4];

		$in_COL = found_in_COL($row["id"]);
				
		print"			
		<tr bgcolor='$tbl_color'>
		<td>$arr[$i]</td>
		<td align='center'>
			<a target='ext_eol_site' href='http://$eol_site/pages/$id'>$row[id]</a>
		</td>		
		<td align='center'>$in_COL</td>		
		<td align='right'>$objects_cnt</td>
		<td align='right'>$images_cnt</td>
		<td align='right'>$images_trusted_cnt</td>
		<td align='right'>$text_cnt</td>
		<td align='right'>$text_trusted_cnt</td>
		
		
		</tr>";		
		/* working

		<td align='center'>
		<a target='species_stat' href='../species_stat/index.php?tc_id=$row[id]&proceed=y'>link</a>
		</td>		
		
		
		<td align='center'>
		<a target='species_stat' href='../species_stat/maintenance.php?tc_id=$row[id]&proceed=y'>link</a>
		</td>		

		*/
		
		
		//<a target='species_stat' href='../species_stat/index.php?tc_id=$row[id]'>link</a>
		//<a target='species_stat' href='../species_stat/index.php?tc_id=$row[id]&erja=173'>link</a>
		
		$rd .= "$arr[$i] $sep 
				$row[id] $sep 
				$in_COL $sep 
				$objects_cnt $sep 
				$images_cnt $sep 
				$images_trusted_cnt $sep 
				$text_cnt $sep 
				$text_trusted_cnt ";
		
		//$rd .= "$sep http://$eol_site/pages/$id'>$row[id] ";
		
		$rd .= chr(13);
		
		$oldsn = $arr[$i]; //print " {$oldsn} ";	
		
	}	
	$sql->close();
	
	
	//print "<hr>$in_str";
	

	
	
	

	
	//end proc
	
	
	
	
}//end for loop

print"</table>";


function change_color($color)	//color is the current color
{
	global $tbl_color;
	
	if($color == 'white')	{return 'aqua';}
	else					{return 'white';}
}

function found_in_COL($id)	//id = taxon_concepts!id
{
	global $mysqli;
	
	//start check if in COL	
	//PL's
	//print"<hr>222<hr>";
	
	/*
	$qry="Select hierarchy_entries.hierarchy_id,
	hierarchy_entries.id as in_col, taxon_concepts.id
	From taxon_concepts Inner Join hierarchy_entries ON taxon_concepts.id = hierarchy_entries.taxon_concept_id
	Where taxon_concepts.id = $id AND hierarchy_entries.hierarchy_id = " . Hierarchy::col_2009();
	*/	
	$qry="Select hierarchies.id as in_col 
	From
	hierarchy_entries
	Inner Join hierarchies ON hierarchy_entries.hierarchy_id = hierarchies.id
	Where
	hierarchy_entries.taxon_concept_id = $id
	and hierarchy_entries.hierarchy_id = 147";
	
	$sql2 = $mysqli->query($qry);
	
	$found_in_col='N';
	
	while( $row2 = $sql2->fetch_assoc() )
	{
		//exit("ditox");
		$in_col = $row2["in_col"];		//hierarchy_entries.id = row[in_col]
    	if(!$in_col)	{$found_in_col='N';}
	    else 			{$found_in_col='Y';}	
	}	
	$sql2->close();
	//end check if in COL
	return $found_in_col;

}//end function


 /*
$canonical_form = Functions::canonical_form($arr[0]);
print $canonical_form;
exit;
 */

?>

<!---###########################################################################################################--->
<!---###########################################################################################################--->
<!---###########################################################################################################--->
<!---###########################################################################################################--->
<!---###########################################################################################################--->


<?php

function proc_tc_id($id)
{
	global $mysqli;
	//print"<hr>333<hr>";
	$qry="
	select distinct 
	do.data_type_id,
	do.published,
	do.vetted_id,
	do.visibility_id,
	do.id
	From taxon_concepts tc 
	Inner Join hierarchy_entries he ON tc.id = he.taxon_concept_id
	inner join taxa t on (he.name_id=t.name_id)
	inner join data_objects_taxa dot on (t.id=dot.taxon_id)
	inner join data_objects do on (dot.data_object_id=do.id)
	where
	tc.id=$id
	and do.visibility_id	= 1	
	and tc.published 		= 1
	and do.published	= 1	
	and do.data_type_id in (1,3)
	order by do.data_type_id
	";		

	$sql3 = $mysqli->query($qry);
	//print "$sql3->num_rows<hr>";
	//print "<hr>$qry<hr>";	
	
	$oldLabel_g1="";
	$count_of_vetted=0;
	
	/*
Name
Exists in EOL (true/false)
Number of data objects
Number of Images
Number of trusted images
Number of text objects
Number of trusted text objects	
	*/

	$objects_cnt		= $sql3->num_rows;
	$images_cnt			= 0;
	$images_trusted_cnt	= 0;
	$text_cnt			= 0;
	$text_trusted_cnt	= 0;
		
	$old_toc_id="";	
	while( $row3 = $sql3->fetch_assoc() )
	{
		$data_type_id = $row3["data_type_id"];
		
		/*
		$label = $row3["label"];
		$info_item_id = $row3["info_item_id"];

		if($label=='Image'){$images_cnt++;}
		if($label=='Text') {$text_cnt++;}
		*/

		if($data_type_id==3 )	{$text_cnt++;}
		if($data_type_id==1 ) 	{$images_cnt++;}
		

		//0-Unknown , 4-Untrusted , 5-Trusted
		// and 
		
		if	(	
				$row3["vetted_id"] 		== 5 
			)	
		{	
			if($data_type_id==3 )	{$text_trusted_cnt++;}
			if($data_type_id==1 )	{$images_trusted_cnt++;}								
		}		
		
		//$old_toc_id = $row3["toc_id"];
	}//end while
	$sql3->close();
	
	$objects_cnt		= $images_cnt	+ $text_cnt	;
	//Corophium insidiosum
	

	return "$objects_cnt,$images_cnt,$images_trusted_cnt,$text_cnt,$text_trusted_cnt";
	
	//amanita muscaria
}



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
	//print"<hr>444<hr>";
	/* dec7 commented
	$query = "select distinct tcn.taxon_concept_id 
	from $tbl n join taxon_concept_names tcn 
	on (n.$fld_id=tcn.name_id) 
	where n.$fld='$string'";
	*/
	$query = "
	Select distinct taxon_concepts.id as taxon_concept_id
	From clean_names 
	Inner Join hierarchy_entries ON clean_names.name_id = hierarchy_entries.name_id
	Inner Join taxon_concepts ON hierarchy_entries.taxon_concept_id = taxon_concepts.id
	where clean_names.clean_name='$string'
	";
	
	$sql = $mysqli->query($query);
	$row = $sql->fetch_row();			
	$id   = $row[0];
	$sql->close();

	//echo "id = $id";
	/*dec 7 commented
	$query="
	Select distinct $tbl.$fld as string
	From $tbl Inner Join taxon_concept_names ON taxon_concept_names.name_id = $tbl.$fld_id
	Where taxon_concept_names.taxon_concept_id = '$id' 
	AND taxon_concept_names.vern = '0'
	Order By $tbl.$fld Asc ";
	*/
	$query = "
	Select distinct $tbl.$fld as string
	From clean_names 
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


