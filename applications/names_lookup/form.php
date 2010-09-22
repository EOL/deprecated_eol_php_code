<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
	<title>Evaluate names</title>
    <script language="javascript1.2">
        function proc()
        {
        	if(document.forms.fn.choice[0].checked == true)
        	{
        		document.forms.fn.separator.value = 'chr(13)'
        	}
        	else
        	{
        		document.forms.fn.separator.value = ','
        	}	
        }
        function proc2()
        {
        	document.forms.fn.separator.value = ''
        }
    </script>
</head>
<body>
<form name="fn" target="result" action="result.php" method="post">
<font size="2">Names look-up</font><br>
<textarea name="list" rows="10" cols="50" ></textarea>
<table>
<tr><td>Select filter:</td></tr>
<tr><td>
<input onClick="proc3()"  type="radio" name="choice2" value="1">Exist in EoL &nbsp;&nbsp;&nbsp;&nbsp;
<input type="checkbox" name="with_name_source">With name source <br>
<input onClick="proc3()"  type="radio" name="choice2" value="2">Do not exist in EoL	<br>
<input onClick="proc3()"  type="radio" name="choice2" value="3" checked >With data objects &nbsp;&nbsp;&nbsp;&nbsp;
<font size="2">
<input type="radio" name="data_kind" value="1" checked>Text
<input type="radio" name="data_kind" value="2">Image | 
<input type="radio" name="format" value="1" checked>Info items
<input type="radio" name="format" value="2">Objects title
</font>
<br>
<input onClick="proc3()"  type="radio" name="choice2" value="4">Without data objects <br>
</td></tr>
<tr><td><hr></td></tr>
<tr><td>Names are:</td></tr>
<tr><td>
<input onClick="proc2()"  type="radio" name="choice" value="1" checked>row separated <i>(e.g. copied from spreadsheet or database system)</i>	<br>
<input onClick="proc2()"  type="radio" name="choice" value="2" >linefeed separated	<br>
<input onClick="proc2()"  type="radio" name="choice" value="3" >tab separated	<br>
<input onClick="proc2()"  type="radio" name="choice" value="4" >comma separated	<br>
</td></tr>
<tr><td>Specify separator: <input type="text" name="separator" size="6"></td></tr>
<tr><td><hr></td></tr>
<tr><td>
<input type="submit" value="Submit">
<input type="reset" value="Reset">&nbsp;&nbsp;&nbsp;&nbsp;
<input type="hidden" name="withCSV" value="on">
</td></tr>
</table>
</form>
</body>
</html>