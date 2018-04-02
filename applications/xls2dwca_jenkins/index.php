<form action="form_result.php" method="post" enctype="multipart/form-data">
    <table border="1" cellpadding="15" cellspacing="1" align="center">
        <tr align="center">
            <td>Excel to EOL Archive Converter</td>
        </tr>
        <tr align="center">
            <td>
                <font size="2">XLS File URL: </font><input type="text" name="url" size="100">
                <br>
                <font size="2">
                    <i>e.g. http://mydomain.org/my_spreadsheet.xlsx</i><br>
                    <a href="https://github.com/eliagbayani/EOL-connector-data-files/raw/master/schema/eol_import_spreadsheet.xlsx">Download</a> the EOL spreadsheet template here.
                    <br>
                </font>
            </td>
        </tr>
        <tr>
            <td>
                <font size="2">XLS File Upload: </font><input type="file" name="file_upload" id="file_upload" size="100">
            </td>
        </tr>
        <tr align="center">
            <td>
                <input type="submit" value="Convert to EOL-compliant archive">
                <input type="reset" value="Reset">
                <input type="checkbox" name="validate">Validate
            </td>
        </tr>
    </table>
</form>
<hr>
<?php require_once("../tools.php") ?>