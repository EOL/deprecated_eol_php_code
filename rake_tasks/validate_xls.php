<?php

/*
NOTE the hard-coded file names! This was a one-off. If you use it again, then
you may wish to improve it a bit, as it's rough around the edges. Thanks.
*/

namespace php_active_record;
require_once("config/environment.php");
$mysqli = $GLOBALS['db_connection'];
$GLOBALS['ENV_DEBUG'] = false;
$file = $argv[1]; // '/opt/resources/minerology/mineralogy.xlsx';
// $archive_dir = '/opt/resources/minerology/mineralogy';
$path_parts = pathinfo($file);
$extension = @$path_parts['extension'];
$archive_dir = @$path_parts['dirname'] ."/". @$path_parts['filename'];
print "Will use temp dir $archive_dir...\n";
recursive_rmdir($archive_dir);
print "Converting file...\n";
require_once("lib/ExcelToText.php");
$archive_converter = new ExcelToText($file, $archive_dir);
if($archive_converter->errors())
{
    print "Errors converting the Excel file.\n";
    print_r($archive_converter->errors());
    exit;
}
if(! $archive_converter->is_new_schema_spreadsheet() )
{
    print "Wasn't a new schema spreadsheet.\n";
    exit;
}
$archive_converter->convert_to_new_schema_archive();
if($archive_converter->errors())
{
    print "Errors in converting to a new archive.\n";
    print_r($archive_converter->errors());
    exit;
}
$archive = new ContentArchiveReader(null, $archive_dir);
$validator = new ContentArchiveValidator($archive);
$validator->get_validation_errors();
print "Display errors:\n";
print_r($validator->display_errors());  // REPORT THESE.
print "Structural errors:\n";
print_r($validator->structural_errors()); // AND THESE.
print "Display warnings:\n";
print_r($validator->display_warnings()); // AND THESE!

?>
