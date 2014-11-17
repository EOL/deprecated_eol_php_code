<?php
namespace php_active_record;

include_once(dirname(__FILE__) . "/../config/environment.php");

// NOTE there are some specific behaviors in this script when you're in the 'test' environment: post-harvesting cleanup will always run (if you don't
// specify a single ID to run), and harvests WON'T skip invalid resources.

// NOTE specifying an ID will cause this script to skip denormalizing tables as well as all of the "saturday tasks"
// NOTE a specified ID will NOT be processed, even if you specify it, if it's not "ready for harvesting".
$specified_id = @$argv[1];
if(!is_numeric($specified_id)) $specified_id = null;
// NOTE --fast is ONLY possible when passing in a specific id; ie harvest_resources_cron_task.php 12 --fast
// NOTE --fast will still be set if you errantly pass in a non-numeric ID, so it would attempt to run all harvests (this is bad);
//   ie: harvest_resources_cron_task.php oops --fast
// --fast will skip publishing and updating collection items, as well as skipping Solr index rebuilding, hierarchy_entry_relationships, creating the
// collection for the resource, and indexing for search.
$fast_for_testing = @$argv[2];
if($fast_for_testing && $fast_for_testing == "--fast") $fast_for_testing = true;
else $fast_for_testing = false;

// this checks to make sure we only have one instance of this script running
// if there are more than one then it means we're still harvesting something from yesterday
// NOTE this isn't particularly robust and is likely subject to race conditions, but we're not worried about that.
// Also note that this would actually see a process that was, say, "grep harvest_resources" and stop, but, again,
// we're not so worried about that.
if(Functions::grep_processlist('harvest_resources') > 2)
{
    // TODO - extract a function to send emails to the admin.
    // TODO - change to ADMIN_EMAIL_ADDRESS or the like.
    $to      = PLEARY_EMAIL_ADDRESS;
    $subject = 'Skipped Harvest';
    // TODO - might be useful if we actually added the matching ps results to this email.
    $message = 'We just skipped a scheduled harvest due to a previous one running long.';
    // TODO - change this reply email to something configurable.
    $headers = 'From: no-reply@eol.org' . "\r\n" .
        'Reply-To: no-reply@eol.org' . "\r\n" .
        'X-Mailer: PHP/' . phpversion();
    mail($to, $subject, $message, $headers);
    exit;
}

// TODO - make better use of the log, perhaps? We could store the current harvesting resource ID, minimally.
$log = HarvestProcessLog::create(array('process_name' => 'Harvesting'));
$resources = Resource::ready_for_harvesting();
foreach($resources as $resource)
{
    // IMPORTANT!
    // We skip a few hard-coded resource IDs, here.
    // 201 is Harvard's MCZ.
    // 224 is 3I Interactive Keys and Taxonomic Databases' "Typhlocybinae" DB.
    // TODO - it would be preferable if this flag were in the DB. ...It looks like using a ResourceStatus could achieve the effect.
    // TODO - output a warning if a resource gets skipped.
    if(in_array($resource->id, array(201, 224))) continue;
    // NOTE that a specified id will get SKIPPED if it's not "ready" for harvesting.
    if($specified_id && $resource->id != $specified_id) continue;
    // IF YOU WANT TO RUN ONLY ONE RESOURCE ON A GIVEN NIGHT, use this line:
    // if(!in_array($resource->id, array(324))) continue;
    if($GLOBALS['ENV_DEBUG']) echo $resource->id."\n";

    $validate = true;
    if($GLOBALS['ENV_NAME'] == 'test') $validate = false;
    // YOU WERE HERE 1
    $resource->harvest($validate, false, $fast_for_testing);
}
$log->finished();


if(!$fast_for_testing)
{
    // publish all pending resources
    shell_exec(PHP_BIN_PATH . dirname(__FILE__)."/publish_resources.php ENV_NAME=". $GLOBALS['ENV_NAME']);

    // setting appropriate TaxonConcept publish flag
    Hierarchy::fix_published_flags_for_taxon_concepts();
    Hierarchy::fix_improperly_trusted_concepts();

    // update collection items which reference superceded concepts
    shell_exec(PHP_BIN_PATH . dirname(__FILE__)."/remove_superceded_collection_items.php ENV_NAME=". $GLOBALS['ENV_NAME']);

    if(!$specified_id)
    {
        // denormalize tables
        shell_exec(PHP_BIN_PATH . dirname(__FILE__)."/denormalize_tables.php ENV_NAME=". $GLOBALS['ENV_NAME']);

        if(defined('SOLR_SERVER'))
        {
            // TODO - Change this conditional to something like run_post_harvest_cleanup?, and have that method check the day; we can then override it when
            // testing.
            // If we're in the test environment
            // OR if today is Saturday:
            if($GLOBALS["ENV_NAME"] == 'test' || date('w') == 6)
            {
                if(SolrAPI::ping(SOLR_SERVER, 'site_search'))
                {
                    $search_indexer = new SiteSearchIndexer();
                    $search_indexer->recreate_index_for_class('DataObject');
                    $search_indexer->recreate_index_for_class('TaxonConcept');
                    $solr = new SolrAPI(SOLR_SERVER, 'site_search');
                    $solr->optimize();
                }

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

                if(SolrAPI::ping(SOLR_SERVER, 'collection_items'))
                {
                    $solr = new SolrAPI(SOLR_SERVER, 'collection_items');
                    $solr->optimize();
                }
            }
        }
    }
}

?>
