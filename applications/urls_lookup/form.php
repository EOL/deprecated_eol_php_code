<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
	<title>Process URLs</title>
    <script language="javascript1.2">
        function proc2()
        {
        	document.forms.fn.separator.value = ''
        }
    </script>
</head>
<!---
Values 8, 9, 10, and 13 convert to backspace, tab, linefeed, and carriage 
return characters, respectively. They have no graphical representation but, 
depending on the application, can affect the visual display of text." 
--->
<body>
<form name="fn" target="result" action="result.php" method="post">
<font size="2">Names compiler</font><br>
<textarea name="list" rows="15" cols="100" style="font-size : x-small; font-family : Arial Narrow;"></textarea>
<table>
<tr><td><font size="2">e.g. http://www.eol.org/pages/206691</font><hr></td></tr>
<tr><td>URLs are:</td></tr>
<tr><td>
<input onClick="proc2()"  type="radio" name="choice" value="1" checked>row separated (e.g. copied from spreadsheet or database system)	<br>
<input onClick="proc2()"  type="radio" name="choice" value="2" >linefeed separated	<br>
<input onClick="proc2()"  type="radio" name="choice" value="3" >tab separated	<br>
<input onClick="proc2()"  type="radio" name="choice" value="4" >comma separated	<br>
</td></tr>
<tr><td>Specify separator: <input type="text" name="separator" size="6"></td></tr>
<tr><td><hr></td></tr>
<tr><td>
<input type="submit" value="Submit">
<input type="reset" value="Reset">
&nbsp;&nbsp;&nbsp;&nbsp;
<input type="checkbox" name="withCSV">With tab-delimited text file output
</td></tr>
</table>
</form>
</body>
</html>