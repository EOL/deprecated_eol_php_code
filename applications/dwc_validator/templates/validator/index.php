<?php
namespace php_active_record;
    /* 
        Expects:
            $file_url
            $file_upload
            $is_eol_schema
            $xsd
            $errors
            $eol_errors
            $eol_warnings
    */
?>

<html>
    <head>
        <title>DwC-A Validator</title>
    </head>
    <body>
        <h2 align="center">DwC-A File Validator</h2>
        <?php render_template("validator/form", array("file_url" => $file_url)); ?>
        <hr/>
        <?php render_template("validator/result", array("file_url" => $file_url, "file_upload" => $file_upload, "errors" => $errors, "warnings" => $warnings)); ?>
    </body>
</html>
