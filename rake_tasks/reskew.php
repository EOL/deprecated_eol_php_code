<?php

include_once(dirname(__FILE__) . "/../config/environment.php");

// Seems odd to me that this is required (the worker loads it, too), but I was having trouble and tried this:
// TODO - try removing it.
include_once(dirname(__FILE__) . "/../lib/Reskewer.php");

// TODO - This should, of course, be required in a central lib:
require_once(DOC_ROOT . "vendor/php_resque/lib/Resque.php");
Resque::setBackend(RESQUE_SERVER);

$args = array('cmd'                          => @$argv[1], // Really, I don't think we should do this, but instead
                                                           // have separate rake commands for each ...uh... command.
              'taxon_concept_id_from'        => @$argv[2],
              'hierarchy_entry_id'           => @$argv[3],
              'taxon_concept_id_to'          => @$argv[4],
              'bad_match_hierarchy_entry_id' => @$argv[5],
              'confirmed'                    => @$argv[6],
              'reindex'                      => @trim($argv[7]));
Resque::enqueue('php', 'Reskewer', $args);

?>
