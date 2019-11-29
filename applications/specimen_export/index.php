<form action="form_result.php" method="post" enctype="multipart/form-data">
    <table border="1" cellpadding="15" cellspacing="1" align="center" width="40%">
        <tr align="center">
            <td>Excel Specimen Export Tool
            <br>
            <small>NMNH specimen export to BOLD specimen import<br><a href='https://eol-jira.bibalex.org/browse/COLLAB-1004'>(work in progress)</a></small>
            </td>
        </tr>
        <tr align="center">
            <td>
                <font size="3">
                    <!--- <i>Sample input template</i><br> --->
                    <a href="https://github.com/eliagbayani/EOL-connector-data-files/raw/master/MarineGEO/input.xlsx">Download</a> the input spreadsheet template here.
                    <br>
                </font>
            </td>
        </tr>

        <tr>
            <td>
                <font size="3">Excel File URL: </font><input type="text" name="form_url" size="100">
                <br>
                <font size="3">
                    <i>e.g. http://mydomain.org/spreadsheets/input.xlsx</i><br>
                </font>
            </td>
        </tr>

        <tr>
            <td>
                <font size="3">Excel File Upload: </font><input type="file" name="file_upload" id="file_upload" size="100">
                <small><br><br>(.xlsx, .xls) OR recommended (.xls.zip, .xlsx.zip)
                <br><br>
                *It is recommended that you zip your file before uploading.</small>                
            </td>
        </tr>
        <tr align="center">
            <td>
                <input type="submit" value="Generate specimen export file">
                <input type="reset" value="Reset">
                <!--- <input type="checkbox" name="validate">Validate --->
            </td>
        </tr>
    </table>
</form>
<hr>
<?php 
// require_once("../tools.php") 
?>