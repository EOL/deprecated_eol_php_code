<form action="form_result.php" method="post" enctype="multipart/form-data">
<table border="1" cellpadding="15" cellspacing="1" align="center">
<tr align="center"><td>XLS to EOL format</td></tr>
<tr align="center">
    <td>
        <font size="2">XLS File URL: </font><input type="text" name="url" size="100" style="font-size : x-small; font-family : Arial;">
        <br>
        <font size="2"><i>e.g. http://mydomain.org/my_spreadsheet.xls</i><br>
        <a href="eol.xls">Download</a> the EOL spreadsheet template here.<br>
        </font>
    </td>
</tr>
<tr>
    <td>
        <font size="2">XLS File Upload: </font><input type="file" name="file_upload" id="file_upload" size="100" 
        style="font-size : x-small; font-family : Arial;">
    </td>
</tr>
<tr align="center">
    <td>    
        <input type="submit" value="Convert to EOL-compliant XML">
        <input type='reset' value='Reset'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <input type="checkbox" name="validate">Validate&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    </td>
</tr>
</table>
<table border="0" cellpadding="0" cellspacing="0" align="center"><tr><td><font size="2">
<a href="../specialist_project_converter/main.php">Specialist Project &gt;&gt; </a>
</font></td></tr></table>

</form>