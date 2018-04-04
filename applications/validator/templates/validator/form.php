<?php
namespace php_active_record;
    /* 
        Expects:
            $file_url
    */
?>

<form name="validator_form" action="index.php" method="post" enctype="multipart/form-data">
<table align="center">
    <tr>
        <td>XML File URL:</td>
        <td><input type="text" size="150" name="file_url"<?php if($file_url) echo " value=\"$file_url\""; ?>/></td>
    </tr>
    <tr>
        <td>XML File Upload:</td>
        <td><input type="file" name="xml_upload" id="xml_upload"/></td>
    </tr>
    <tr>
        <td colspan="2" align="center">
            <br/>
            <input type="submit" value="Submit">
            <br/><br/>
            You might also want to try our <a href='../dwc_validator/index.php'>Archive and Spreadsheet Validator</a>
        </td>
    </tr>
</table>
</form>
