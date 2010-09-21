<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
	<title>EOL Resource Monitoring - RSS Feeds</title>
<script language="javascript1.2">
function check_click()
{	txtbox = '';
	var i=1;
	for (i=1;i<=13;i++)
	{	if(i != 5)
		{	var str = 'check_' + i;
			if(document.getElementById(str).checked){txtbox += i + ",";}			
		}				
	}		
	txtbox = txtbox.substr(0,txtbox.length-1);	
	document.getElementById('what').value = txtbox;
    
    temp = txtbox.indexOf(',');
    if(temp > 0)
    {
        document.getElementById('f').value = txtbox.substr(0,temp);	
    }
    else
    {
        document.getElementById('f').value = txtbox;	
    }
}//function check_click()

function proc()
{
	var number = document.getElementById('resource_id').selectedIndex;
	document.getElementById('txt2').value = '';
	document.getElementById('txt2').value = document.getElementById('txt').value +
											document.getElementById('resource_id').options[number].value;	
	document.getElementById('subscribe').href = document.getElementById('txt2').value;
	document.getElementById('preview').href = 'right.php?url=' + document.getElementById('txt2').value;	
}
</script>
</head>
<body>


<?php
require_once("../../config/environment.php");
$mysqli =& $GLOBALS['mysqli_connection'];

require("feeds.php");
for ($i = 1; $i <= count($feeds) ; $i++) 
{
	print"
	<link 	rel='alternate' 
			type='application/rss+xml'
    		title='" . $feeds[$i]["title"] . "' 
			href='" . "http://$domain/$feed_path?f=" . $feeds[$i]["feed"] . "'>		
	";
}

print"<table border='1'>
<form method='get' action='process.php' id='fn'><input type='hidden' id='f' name='f'><input type='hidden' id='what' name='f_list'>
</form>
<tr><td colspan='6' align='center'>EOL Resource Monitoring - RSS Feeds<br>&nbsp;</td></tr>";

$cnt=0;
for ($i = 1; $i <= count($feeds) ; $i++) 
{
	if($i != 5)
	{
		$cnt++;		
		print"
		<tr>
		<td align='right'>$cnt. </td>
		<td>" . $feeds[$i]["title"] . "	</td>	
		<td>
		<a title='Subscribe' target='_top' href='http://$domain/$feed_path?f=" . $feeds[$i]["feed"] . "'>
		<img alt='Subscribe' src='feed_icon_big.png' border='0'>	
		</a></td>";	
		print"<td width='10'></td><td><input type='checkbox' value='$i' onClick='check_click()' id='check_$i'></td>";	
		if($cnt == 1)
		{	print"<td valign='middle' rowspan='12' align='center'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			<a title='Subscribe' target='_top' href='javascript:document.forms.fn.submit()'>
			<img alt='Subscribe' src='feed_icon_big.png' border='0'></a> Related feeds &nbsp;&nbsp;&nbsp;&nbsp;
			<br><i><small>Click checkbox for feeds<br>you want to group</small></i>
			</td>";
		}	
		print"</tr>";
	
	}
}


$qry = "select title, id, service_type_id from resources order by title";
$result = $mysqli->query($qry);	

$cnt++;
print"<td>" . $cnt . ". </td><td colspan='5' valign='middle'><select style='font-size : x-small;' id='resource_id' name=resource_id onChange='proc()'><option>";
while($result && $row=$result->fetch_assoc())
{
	print"<option value=$row[id]>$row[title] [$row[id]] ";
    if($row["service_type_id"]==2)print"[**has connector]";
}
print"</select>
<a id='subscribe' title='Subscribe' target='_top' href=''>
<img alt='Subscribe' src='feed_icon_big.png' border='0'>
</td><td></td>
<input type='hidden' id='txt' value='http://$domain/$feed_path?f=5&resources_id=' size='100'><br>
<input type='hidden' id='txt2' size='100'>
</table>";
?>

<br>
<i><font size="2">Login to www.eol.org to see onward links.</font></i>
</body>
</html>