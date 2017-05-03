<form action="form_result.php" method="post" enctype="multipart/form-data">
<table border="1" cellpadding="15" cellspacing="1" align="center">
<tr align="center"><td>Generate higherClassification Tool</td></tr>
<tr align="center">
    <td>
        <font size="2">File URL: </font><input type="text" name="url" size="100" style="font-size : x-small; font-family : Arial;">
        <br>
        <font size="2"><i>e.g. http://mydomain.org/taxon.tab</i><br>
        <a href="sample/taxon.tab">Download</a> a sample taxon.tab here.<br>
        </font>
    </td>
</tr>
<tr>
    <td>
        <font size="2">File Upload: </font><input type="file" name="file_upload" id="file_upload" size="100" 
        style="font-size : x-small; font-family : Arial;">
    </td>
</tr>
<tr align="center">
    <td>
        <input type="submit" value="Generate higherClassification info">
        <input type='reset' value='Reset'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    </td>
</tr>
</table>
</form>