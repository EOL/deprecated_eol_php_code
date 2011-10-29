<?php
namespace php_active_record;

include_once(dirname(__FILE__) . "/../config/environment.php");
$GLOBALS['ENV_DEBUG'] = true;

// this checks to make sure we only have one instance of this script running
// if there are more than one then it means we're still harvesting something from yesterday
if(Functions::grep_processlist('harvest_resources') > 2) exit;

$log = HarvestProcessLog::create(array('process_name' => 'Harvesting'));
$resources = Resource::ready_for_harvesting();
foreach($resources as $resource)
{
    if(in_array($resource->id, array(42))) continue;
    // if(!in_array($resource->id, array(15))) continue;
    
    echo $resource->id."\n";
    
    $validate = true;
    if($GLOBALS['ENV_NAME'] == 'test') $validate = false;
    $resource->harvest($validate);
}
$log->finished();



// sleep for 10 minutes to allow changes from transactions to propegate
// sleep_production(300);

// publish all pending resources
shell_exec(PHP_BIN_PATH . dirname(__FILE__)."/publish_resources.php ENV_NAME=". $GLOBALS['ENV_NAME']);


// setting appropriate TaxonConcept publish flag
Hierarchy::publish_wrongly_unpublished_concepts();

// sleep for 5 minutes to allow changes from transactions to propegate
// sleep_production(300);

// denormalize tables
// shell_exec(PHP_BIN_PATH . dirname(__FILE__)."/denormalize_tables.php ENV_NAME=". $GLOBALS['ENV_NAME']);

// if(defined('SOLR_SERVER'))
// {
//     if(SolrAPI::ping(SOLR_SERVER, 'hierarchy_entry_relationship'))
//     {
//         $solr = new SolrAPI(SOLR_SERVER, 'hierarchy_entry_relationship');
//         $solr->optimize();
//     }
//     if(SolrAPI::ping(SOLR_SERVER, 'hierarchy_entries'))
//     {
//         $solr = new SolrAPI(SOLR_SERVER, 'hierarchy_entries');
//         $solr->optimize();
//     }
// }

?>
