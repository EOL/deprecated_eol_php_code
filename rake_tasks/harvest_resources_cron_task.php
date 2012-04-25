<?php
namespace php_active_record;

include_once(dirname(__FILE__) . "/../config/environment.php");
$GLOBALS['ENV_DEBUG'] = true;

$specified_id = @$argv[1];
if(!is_numeric($specified_id)) $specified_id = null;


// this checks to make sure we only have one instance of this script running
// if there are more than one then it means we're still harvesting something from yesterday
if(Functions::grep_processlist('harvest_resources') > 2)
{
    $to      = 'pleary@mbl.edu';
    $subject = 'Skipped Harvest';
    $message = 'We just skipped a scheduled harvest due to a previous one running long.';
    $headers = 'From: pleary@eol.org' . "\r\n" .
        'Reply-To: pleary@eol.org' . "\r\n" .
        'X-Mailer: PHP/' . phpversion();
    mail($to, $subject, $message, $headers);
    exit;
}

$log = HarvestProcessLog::create(array('process_name' => 'Harvesting'));
$resources = Resource::ready_for_harvesting();
foreach($resources as $resource)
{
    // if(in_array($resource->id, array(42))) continue;
    if($specified_id && $resource->id != $specified_id) continue;
    // if(!in_array($resource->id, array(324))) continue;
    // if($resource->id < 197) continue;
    echo $resource->id."\n";
    
    $validate = true;
    if($GLOBALS['ENV_NAME'] == 'test') $validate = false;
    $resource->harvest($validate);
}
$log->finished();



// publish all pending resources
shell_exec(PHP_BIN_PATH . dirname(__FILE__)."/publish_resources.php ENV_NAME=". $GLOBALS['ENV_NAME']);

// setting appropriate TaxonConcept publish flag
Hierarchy::publish_wrongly_unpublished_concepts();

// update collection items which reference superceded concepts
shell_exec(PHP_BIN_PATH . dirname(__FILE__)."/remove_superceded_collection_items.php ENV_NAME=". $GLOBALS['ENV_NAME']);

if($specified_id) exit;


// denormalize tables
shell_exec(PHP_BIN_PATH . dirname(__FILE__)."/denormalize_tables.php ENV_NAME=". $GLOBALS['ENV_NAME']);

if(defined('SOLR_SERVER'))
{
    if(SolrAPI::ping(SOLR_SERVER, 'site_search'))
    {
        $search_indexer = new SiteSearchIndexer();
        $search_indexer->index_type('DataObject', 'data_objects', 'lookup_objects');
        $search_indexer->index_type('TaxonConcept', 'taxon_concepts', 'index_taxa');
        
        $solr = new SolrAPI(SOLR_SERVER, 'site_search');
        $solr->optimize();
    }
    
    // Only optimize the indices on Saturday which is our lowest traffic day
    if(date('w') == 6)
    {
        if(SolrAPI::ping(SOLR_SERVER, 'data_objects'))
        {
            $solr = new SolrAPI(SOLR_SERVER, 'data_objects');
            $solr->optimize();
        }
        
        if(SolrAPI::ping(SOLR_SERVER, 'hierarchy_entries'))
        {   
            $solr = new SolrAPI(SOLR_SERVER, 'hierarchy_entries');
            $solr->optimize();
        }
        
        if(SolrAPI::ping(SOLR_SERVER, 'hierarchy_entry_relationship'))
        {
            $solr = new SolrAPI(SOLR_SERVER, 'hierarchy_entry_relationship');
            $solr->optimize();
        }
    }
}

?>
