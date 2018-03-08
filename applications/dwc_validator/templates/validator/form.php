<?php
namespace php_active_record;
    /* 
        Expects:
            $file_url
    */
$GLOBALS['ENV_DEBUG'] = true;
?>

<form name="validator_form" action="index.php" method="post" enctype="multipart/form-data">
<table align="center">
    <tr>
        <td>File URL:</td>
        <td><input type="text" size="30" name="file_url"<?php if($file_url) echo " value=\"$file_url\""; ?>/></td>
    </tr>
    <tr>
        <td>File Upload:</td>
        <td><input type="file" name="dwca_upload" id="dwca_upload"/></td>
    </tr>
    <tr>
        <td colspan="2" align="center">
            <br/>
            <input type="submit" value="Submit">
            <br/><br/>
            You might also want to try our <a href='../validator/index.php'>XML validator</a>
        </td>
    </tr>
</table>
</form>
