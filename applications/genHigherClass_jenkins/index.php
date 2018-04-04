<form action="form_result.php" method="post" enctype="multipart/form-data">
<table border="1" cellpadding="15" cellspacing="1" align="center">
<tr align="center"><td>Generate higherClassification Tool (Jenkins)</td></tr>
<!---
<tr align="center">
    <td>
        File URL: <input type="text" name="url" size="100" style="font-family : Arial;">
        <br>
        <i>e.g. http://mydomain.org/taxon.tab</i><br>
        <a href="sample/taxon.tab">Download</a> a sample <i>taxon.tab</i> here.<br>
    </td>
</tr>
--->
<tr align="center">
    <td>
        <a href="../genHigherClass/sample/taxon.tab">Download</a> a sample <i>taxon.tab</i> here.<br>
    </td>
</tr>
<tr>
    <td>
        File Upload: <input type="file" name="file_upload" id="file_upload" size="100" style="font-family : Arial;">
        <small><br><br>(.tab, .txt, .tsv) OR<br> Recommended (.tab.zip, .txt.zip, .tsv.zip)
        <br><br>
        *It is recommended that you zip your file before uploading.</small>
    </td>
</tr>
<tr align="center">
    <td>
        <input type="submit" value="Generate higherClassification">
        <input type='reset' value='Reset'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    </td>
</tr>
</table>
</form>
<hr>
<?php require_once("../tools.php"); ?>