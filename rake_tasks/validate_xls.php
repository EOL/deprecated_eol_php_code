<?php

/*
NOTE the hard-coded file names!  This was a one-off. If you use it again, then
it's time to generalize it. It should take the input file as an argument and
parse out the "temp" name from that. It might do a better job with the output,
too.
*/

namespace php_active_record;
require_once("config/environment.php");
$mysqli = $GLOBALS['db_connection'];
$GLOBALS['ENV_DEBUG'] = false;
$file = '/opt/resources/minerology/mineralogy.xlsx';
$temp = '/opt/resources/minerology/mineralogy';
require_once("lib/ExcelToText.php");
$archive_converter = new ExcelToText($file, $temp);
if($archive_converter->errors())
{
  print "Errors converting the Excel file.";
  exit;
}
if(! $archive_converter->is_new_schema_spreadsheet() )
{
  print "Wasn't a new schema spreadsheet.";
  exit;
}
$archive_converter->convert_to_new_schema_archive();
if($archive_converter->errors())
{
  print "Errors in converting to a new archive.";
  exit;
}
$archive = new ContentArchiveReader(null, $temp);
$validator = new ContentArchiveValidator($archive);
$validator->get_validation_errors();
print "Display errors:";
print_r($validator->display_errors());  // REPORT THESE.
print "Structural errors:";
print_r($validator->structural_errors()); // AND THESE.
print "Display warnings:";
print_r($validator->display_warnings()); // AND THESE!

?>

