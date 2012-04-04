<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../config/environment.php");

$indexer = new DataObjectAncestriesIndexer();
$indexer->index();

if(SolrAPI::ping(SOLR_SERVER, 'data_objects'))
{
    $solr = new SolrAPI(SOLR_SERVER, 'data_objects');
    $solr->optimize();
}

?>
