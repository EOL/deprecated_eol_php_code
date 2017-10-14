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
        <title>XML Schema Validator</title>
    </head>
    <body>
        <h2 align="center">XML File Validator</h2>
        <?php render_template("validator/form", array("file_url" => $file_url, "is_eol_schema" => $is_eol_schema)); ?>
        <hr/>
        <?php render_template("validator/result", array("file_url" => $file_url, "file_upload" => $file_upload, "is_eol_schema" => $is_eol_schema, "xsd" => $xsd, "errors" => $errors, "eol_errors" => $eol_errors, "eol_warnings" => $eol_warnings)); ?>
    </body>
</html>
<?php require_once("../tools.php") ?>