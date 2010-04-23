<?php



$GLOBALS['ENV_NAME'] = "slave";
require_once(dirname(__FILE__) ."/../../config/environment.php");
$mysqli =& $GLOBALS['mysqli_connection'];


//$mysqli = slave_conn();

$eol_site = "www.eol.org";
//$eol_site = "app1.eol.org";

$report = 'list';	//original functionality
//$report = 'stat';

$tbl = "clean_names";	//instead of 'names'
$fld = "clean_name";	//instead of 'string'
$fld_id = "name_id";	//instead of 'id'

$tbl = "names";	//instead of 'names'
$fld = "clean_name";	//instead of 'string'
$fld_id = "id";	//instead of 'id'


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

$api_put_species="http://labs1.eol.org/api/search/";
$api_put_taxid_1="http://labs1.eol.org/api/pages/";
$api_put_taxid_2="?images=75&text=75&subjects=all&vetted=1";    


foreach($arr as $sciname)
{
	print"$sciname<br>";
    $file = $api_put_species . urlencode($sciname);
    $xml = Functions::get_hashed_response($file);
    $arr_details = get_details($xml);
}
function get_details($xml)
{
    $array=
    foreach($xml->entry as $species)
    {
        print "$species->title $species->id<br>";
        $arr_do = get_objects_info($species->id);        
    }            
}
function get_objects_info($id)
{
    global $api_put_taxid_1;    
    global $api_put_taxid_2;    
    
    $file = $api_put_taxid_1 . $id . $api_put_taxid_2;
    $xml = Functions::get_hashed_response($file);    
   
    $text=0;$image=0;
    foreach($xml->taxon as $taxon)
    {
        foreach($taxon->dataObject as $object)
        {
            if      ($object->dataType == "http://purl.org/dc/dcmitype/StillImage") $image++;
            elseif  ($object->dataType == "http://purl.org/dc/dcmitype/Text") $text++;        
        }    
    }
    print "$text $image<br>";    
    return array($id,$text,$image)
}

exit("<hr>stopx");

?>

<!--- ################################################################################################################# --->
<!--- ################################################################################################################# --->
<!--- ################################################################################################################# --->
<!--- ################################################################################################################# --->

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
		Inner Join names ON taxa.name_id = names.id
		Inner Join hierarchy_entries ON names.id = hierarchy_entries.name_id
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
				Inner Join names ON taxa.name_id = names.id
				Inner Join hierarchy_entries ON names.id = hierarchy_entries.name_id
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
                
                Vetted::find("untrusted")
                Visibility::find("visible")
                DataType::find("http://purl.org/dc/dcmitype/Text")
                DataType::find("http://purl.org/dc/dcmitype/StillImage")
                DataType::find("http://purl.org/dc/dcmitype/MovingImage")                
                
				*/

				$qry="Select $addstr $tbl.$fld AS sn, taxon_concepts.id as tc_id,
				if(data_objects.object_title='',data_types.label,data_objects.object_title) AS label			

    			From taxa
	        	Inner Join names ON taxa.name_id = names.id
    			Inner Join hierarchy_entries ON names.id = hierarchy_entries.name_id
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
			Inner Join names ON taxa.name_id = names.id
			Inner Join hierarchy_entries ON names.id = hierarchy_entries.name_id
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
	Inner Join names ON taxa.name_id = names.id
	Inner Join hierarchy_entries ON names.id = hierarchy_entries.name_id
	Inner Join taxon_concepts ON hierarchy_entries.taxon_concept_id = taxon_concepts.id			
	where names.clean_name like '$string%'
	";
    //where names.clean_name='$string' //mar31
	
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
	Inner Join names ON taxa.name_id = names.id
	Inner Join hierarchy_entries ON names.id = hierarchy_entries.name_id
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


