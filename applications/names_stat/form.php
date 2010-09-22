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

<!---
Values 
8 - backspace
9 - tab
10 - linefeed
13 - carriage return 
characters, respectively have no graphical representation but, 
depending on the application, can affect the visual display of text." 
--->

<body>
<form name="fn" target="result" action="result.php" method="post">
<font size="2">Names statistics</font><br>
<textarea name="list" rows="10" cols="50" ></textarea>
<table>
<tr><td>
# of records returned per taxa: <input style="text-align : right;" type="text" name="return" size="3" value="1"> <i>(blank for all)</i> <br>
Sort order: <br>
&nbsp;&nbsp;&nbsp;<input type="radio" name="sort" value="text" ># of Text objects <br>
&nbsp;&nbsp;&nbsp;<input type="radio" name="sort" value="image" ># of Images<br>
&nbsp;&nbsp;&nbsp;<input type="radio" name="sort" value="total_objects" checked ># of total objects <i>(Text + Image)</i> <br>
<hr>
</td></tr>
<tr><td>
Names are:
</td></tr>
<tr><td>
<input onClick="proc2()"  type="radio" name="choice" value="1" checked>row separated <i>(e.g. copied from spreadsheet or database system)</i>	<br>
<input onClick="proc2()"  type="radio" name="choice" value="2" >linefeed separated	<br>
<input onClick="proc2()"  type="radio" name="choice" value="3" >tab separated	<br>
<input onClick="proc2()"  type="radio" name="choice" value="4" >comma separated	<br>
</td></tr>
<tr><td>
Specify separator: <input type="text" name="separator" size="6">
</td></tr>
<tr><td><hr></td></tr>
<tr><td>
<input type="submit" value="Submit">
<input type="reset" value="Reset">
&nbsp;&nbsp;&nbsp;&nbsp;
<input type="hidden" name="withCSV" value="on">
</td></tr>
</table>
</form>
</body>
</html>