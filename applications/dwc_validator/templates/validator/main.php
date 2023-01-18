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
$GLOBALS['ENV_DEBUG'] = true;
?>

<html>
    <head>
        <title>Archive and Spreadsheet Validator</title>
    </head>
    <body>
        <h2 align="center">Archive and Spreadsheet Validator (eol-archive ver.)</h2>
        <?php render_template("validator/form", array("file_url" => $file_url)); ?>
        <hr/>
        <?php render_template("validator/result", array("file_url" => $file_url, "file_upload" => $file_upload, "errors" => $errors, "structural_errors" => $structural_errors, "warnings" => $warnings, "stats" => $stats)); ?>
    </body>
</html>
<?php require_once("../tools.php") ?>