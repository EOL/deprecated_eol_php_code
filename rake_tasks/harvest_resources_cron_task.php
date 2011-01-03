<?php

include_once(dirname(__FILE__) . "/../config/environment.php");
$GLOBALS['ENV_DEBUG'] = false;

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
$GLOBALS['db_connection']->update("update hierarchies h join hierarchy_entries he on (h.id=he.hierarchy_id) join taxon_concepts tc on (he.taxon_concept_id=tc.id) set tc.published=1 where he.published=1 and he.visibility_id=".Visibility::find('visible')." and tc.published=0");
$GLOBALS['db_connection']->update("update taxon_concepts tc left join hierarchy_entries he on (tc.id=he.taxon_concept_id) set tc.published=0 where tc.published=1 and he.id is null");
$GLOBALS['db_connection']->update("update taxon_concepts set published=0 where supercedure_id!=0 and published=1");


// sleep for 5 minutes to allow changes from transactions to propegate
sleep_production(300);

// denormalize tables
shell_exec(PHP_BIN_PATH . dirname(__FILE__)."/denormalize_tables.php ENV_NAME=". $GLOBALS['ENV_NAME']);

// finally, clear the cache
shell_exec(PHP_BIN_PATH . dirname(__FILE__)."/clear_eol_cache.php ENV_NAME=". $GLOBALS['ENV_NAME']);

if($GLOBALS['ENV_NAME']=='production')
{
    shell_exec(PHP_BIN_PATH . DOC_ROOT ."applications/solr/taxon_concept_index.php ENV_NAME=slave > /dev/null 2>/dev/null &");
}

if(defined('SOLR_SERVER'))
{
    if(SolrAPI::ping(SOLR_SERVER, 'hierarchy_entry_relationship'))
    {
        $solr = new SolrAPI(SOLR_SERVER, 'hierarchy_entry_relationship');
        $solr->optimize();
    }
    if(SolrAPI::ping(SOLR_SERVER, 'hierarchy_entries'))
    {
        $solr = new SolrAPI(SOLR_SERVER, 'hierarchy_entries');
        $solr->optimize();
    }
    
}

?>
