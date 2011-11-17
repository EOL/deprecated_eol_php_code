<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
    <title>NameStat</title>
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

<!--
Values 
8 - backspace
9 - tab
10 - linefeed
13 - carriage return 
-->

<body>
<form name="fn" target="result" action="result.php" method="post">
Names statistics <font size="2"><i>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Limit 100 names at a time.</i></font><br>
<textarea name="list" rows="10" cols="50" ></textarea>
<table>
    <tr>
        <td>
        # of records returned per taxon: <input style="text-align : right;" type="text" name="return" size="3" value="1"> <i>(blank for all)</i><br>
        Match string type: 
        Default <input type="radio" name="strict" value="default" checked> | 
        Canonical form <input type="radio" name="strict" value="canonical_match"> | 
        Exact string match<input type="radio" name="strict" value="exact_string">        
        <br>
        Sort by:<br>
        &nbsp;&nbsp;&nbsp;<input type="radio" name="sort" value="normal" checked>API search result 
        <small>
        (e.g. <a target="api" href="http://www.eol.org/api/search/Placozoa">http://www.eol.org/api/search/Placozoa</a>)<br>
        </small>
        &nbsp;&nbsp;&nbsp;<input type="radio" name="sort" value="text" ># of Text objects<br>
        &nbsp;&nbsp;&nbsp;<input type="radio" name="sort" value="image" ># of Images<br>
        &nbsp;&nbsp;&nbsp;<input type="radio" name="sort" value="total_objects"># of total objects <i>(Text + Image)</i><br>
        <hr>
        </td>
    </tr>
    <tr>
        <td>Names are:</td>
    </tr>
    <tr>
        <td>
        <input onClick="proc2()"  type="radio" name="choice" value="1" checked>row separated <i>(e.g. copied from spreadsheet or database system)</i><br>
        <input onClick="proc2()"  type="radio" name="choice" value="2" >linefeed separated<br>
        <input onClick="proc2()"  type="radio" name="choice" value="3" >tab separated<br>
        <input onClick="proc2()"  type="radio" name="choice" value="4" >comma separated<br>
        </td>
    </tr>
    <tr>
        <td>Specify separator: <input type="text" name="separator" size="6"></td>
    </tr>
    <tr><td><hr></td></tr>
    <tr>
        <td>
        <input type="submit" value="Submit">
        <input type="reset" value="Reset">&nbsp;&nbsp;&nbsp;&nbsp;
        <input type="hidden" name="withCSV" value="on"></td>
    </tr>
</table>
</form>
</body>
</html>