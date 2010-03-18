<?php


require_once("../../config/environment.php");
$mysqli =& $GLOBALS['mysqli_connection'];

$eol_site = "www.eol.org";
//$eol_site = "app1.eol.org";

$tbl = "page_stats_marine";

$sep = chr(9);
$rd = "";


$group = get_val_var("group");
if($group==''){$group='1';}

/*
$group = 1;	//taxa count
$group = 2;	//data objects count
*/

$consider_gbif_img = get_val_var("consider_gbif_img");
$consider_iucn = get_val_var("consider_iucn");

$recFrom = get_val_var("recFrom");
$recTo = get_val_var("recTo");

$proceed = get_val_var("proceed");

$inc_all_taxa = get_val_var("inc_all_taxa");

if($group == 1)	{$rd .= "Taxon stats \n";}
else			{$rd .= "Data object stats \n";}

$rd .= " GBIF image as category: $consider_gbif_img \n";
$rd .= " IUCN as category: $consider_iucn \n";



?>

<h4>Stats History</h4>
<table border="0" style="font-size : small; font-family : Arial;">
<tr>
<form action="archive.php" method="post" name="fn2">
<td>


<?php 
$id = get_val_var("id");
//print "<hr> id = $id <br>";
$delete = get_val_var("delete");
if($delete=='y')
{
	//print"will delete <br>";
	$qry="delete from $tbl where id = $id";
	$update = $mysqli->query($qry);	
}
elseif($delete=='a')
{
	if($id != "")
	{
		$qry="select type from $tbl where id = $id"; 
		$sql = $mysqli->query($qry);			
		$row = $sql->fetch_row();			
		$type = $row[0];
		$sql->close();
		$qry="update $tbl set active = 'n' where type = '$type'		"; $update = $mysqli->query($qry);		
	
		//$qry="update $tbl set active = 'n' 			   "; $update = $mysqli->query($qry);			
		$qry="update $tbl set active = 'y' where id = $id"; $update = $mysqli->query($qry);			
	}
}

// /* page_stats not in slave
$qry = "select `id`, active, marine_pages as taxa_count, date_created, time_created from $tbl order by date_created desc,time_created desc ";
$sql = $mysqli->query($qry);
	print"n=$sql->num_rows <br> <select name='id' ><option value=''>";
	while( $row = $sql->fetch_assoc() )	
	{	
		$style="";
		if($row["active"]=='y'){$style = "style='color : Red;'";}
		
		print "<option $style value='$row[id]' ";
		if($id == $row['id'])
		{	print "selected";
		}    		
		print ">$row[date_created] $row[time_created] n=$row[taxa_count] </option>";
	}		
	$sql->close();
	print"</select>";
// */
	
?>


<br>
<input type="submit" value="Refresh"		onClick="call_delete('')">
<!---
<input type="button" value="Show" 			onClick="call_delete('s')">
<input type="button" value="Set Active" 	onClick="call_delete('a')">
--->
<input type="button" value="Delete" 		onClick="call_delete('y')">
<input type="hidden" id="delete" name="delete">
</form>

<br>&nbsp;<br>

<a href="display.php">See actual report</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="Save2local.php">Save to local</a>
<br>&nbsp;

<script language="javascript1.2">
function call_delete(x)
{
	document.getElementById('delete').value = x;
	document.forms.fn2.submit();
}
</script>

<?php
	if($delete=='s' and $id != '')
	{
		$qry = "select txtfile from $tbl where id = $id ";
		$sql = $mysqli->query($qry);
		if($sql->num_rows > 0)
		{
			$row = $sql->fetch_row();			
			$txtfile = $row[0];
			print"
			<script language='javascript1.2'>
			location.href = 'temp/$txtfile' + '.txt';	
			</script>
			";
		}		
		$sql->close();
	}
	
function get_val_var($v)
{
	if 		(isset($_GET["$v"])) {$var=$_GET["$v"];}
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


	

?>


</td>
</tr>
</table>


<hr>
<a href="index.php"> << Back to Stats Maintenance</a>




