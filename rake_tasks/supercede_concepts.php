<?php
namespace php_active_record;

include_once(dirname(__FILE__) . "/../config/environment.php");
include_once(dirname(__FILE__) . "/../lib/MergeConceptsHandler.php");

MergeConceptsHandler::split_concept(array('id1'       => @$argv[1],
                                          'id2'       => @$argv[2],
                                          'confirmed' => @$argv[3],
                                          'reindex'   => @trim($argv[4])));


?>
