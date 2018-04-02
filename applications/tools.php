<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../config/environment.php");
$GLOBALS['ENV_DEBUG'] = false;
echo '<a href="' . WEB_ROOT .'/applications/dwc_validator/">Archive and Spreadsheet Validator</a> | ';
echo '<a href="' . WEB_ROOT .'/applications/validator/">XML File Validator</a> | ';


if($GLOBALS['ENV_NAME'] == 'development') echo '<a href="' . WEB_ROOT .'/applications/xls2dwca/">Excel to EOL Archive Converter</a> | ';
echo '<a href="' . WEB_ROOT .'/applications/xls2dwca_jenkins/">Excel to EOL Archive Converter (Jenkins)</a> | ';


if($GLOBALS['ENV_NAME'] == 'development') echo '<a href="' . WEB_ROOT .'/applications/genHigherClass/">Generate highClassification Tool</a> | ';

echo '<a href="' . WEB_ROOT .'/applications/genHigherClass_jenkins/">Generate highClassification Tool (Jenkins)</a> | ';
echo '<a href="' . WEB_ROOT .'/applications/DwC_branch_extractor/">Darwin Core Branch Extractor</a> | ';
echo "{".$GLOBALS['ENV_NAME']."}";
?>


