<?php

include_once(dirname(__FILE__) . "/../config/environment.php");
$GLOBALS['ENV_DEBUG'] = true;

// this checks to make sure we only have one instance of this script running
// if there are more than one then it means we're still harvesting something from yesterday
if(Functions::grep_processlist('harvest_resources') > 2) exit;


$log = HarvestProcessLog::create('Harvesting');
$resources = Resource::ready_for_harvesting();
foreach($resources as $resource)
{
    //if($resource->id == 31) continue;
    
    echo $resource->id."\n";
    
    $validate = true;
    if($GLOBALS['ENV_NAME'] == 'test') $validate = false;
    $resource->harvest($validate);
}
$log->finished();



// clear the cache in case some images were unpublished but still referenced in denormalized tables
shell_exec(PHP_BIN_PATH . dirname(__FILE__)."/clear_eol_cache.php ENV_NAME=". $GLOBALS['ENV_NAME']);

// sleep for 10 minutes to allow changes from transactions to propegate
sleep_production(600);

// publish all pending resources
shell_exec(PHP_BIN_PATH . dirname(__FILE__)."/publish_resources.php ENV_NAME=". $GLOBALS['ENV_NAME']);


// setting appropriate TaxonConcept publish flag
Hierarchy::publish_wrongly_unpublished_concepts();

// sleep for 5 minutes to allow changes from transactions to propegate
sleep_production(300);

// denormalize tables
shell_exec(PHP_BIN_PATH . dirname(__FILE__)."/denormalize_tables.php ENV_NAME=". $GLOBALS['ENV_NAME']);

// finally, clear the cache
shell_exec(PHP_BIN_PATH . dirname(__FILE__)."/clear_eol_cache.php ENV_NAME=". $GLOBALS['ENV_NAME']);

if($GLOBALS['ENV_NAME']=='production')
{
    shell_exec(PHP_BIN_PATH . DOC_ROOT ."applications/solr/data_object_ancestries_index.php ENV_NAME=slave");
    shell_exec(PHP_BIN_PATH . DOC_ROOT ."applications/solr/taxon_concept_index.php ENV_NAME=slave > /dev/null 2>/dev/null &");
}

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
