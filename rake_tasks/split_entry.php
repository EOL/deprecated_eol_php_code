<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../config/environment.php");
require_library('CodeBridge');
require_library('SplitEntryHandler');

try
{
    SplitEntryHandler::split_entry(array('hierarchy_entry_id' => @$argv[1],
                                         'bad_match_hierarchy_entry_id' => @$argv[2],
                                         'confirmed' => @$argv[3],
                                         'reindex' => @trim($argv[4])));
}catch (\Exception $e)
{
    echo "\n\n\t", $e->getMessage(), "\n\n";
}

?>

