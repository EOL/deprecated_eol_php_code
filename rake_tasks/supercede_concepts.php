<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../config/environment.php");
require_library('CodeBridge');
require_library('MergeConceptsHandler');

try
{
    MergeConceptsHandler::merge_concepts(array('id1' => @$argv[1],
                                               'id2' => @$argv[2],
                                               'confirmed' => @$argv[3],
                                               'reindex' => @trim($argv[4])));
}catch (\Exception $e)
{
    echo "\n\n\t", $e->getMessage(), "\n\n";
}

?>
