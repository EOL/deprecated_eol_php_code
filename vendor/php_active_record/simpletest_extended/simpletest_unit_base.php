<?php
namespace php_active_record;

require_once(DOC_ROOT . 'vendor/simpletest/autorun.php');

class SimpletestUnitBase extends \UnitTestCase
{
    function setUp()
    {
        Cache::flush();
        $this->fixtures = load_fixtures('test');
    }
    
    function tearDown()
    {
        Cache::flush();
        $GLOBALS['db_connection']->truncate_tables('test');
        unset($this->fixtures);
        
        /* Truncating all Solr indices as well */
        $solr_cores = array(
            'activity_logs',
            'bhl',
            'collection_items',
            'data_objects',
            'hierarchy_entries',
            'hierarchy_entry_relationship',
            'site_search',
            'taxon_concepts');
        foreach($solr_cores as $solr_core)
        {
            if(SolrAPI::ping(SOLR_SERVER, $solr_cores))
            {
                $solr = new SolrAPI(SOLR_SERVER, $solr_cores);
                $solr->delete_all_documents();
            }
        }
        
        $called_class = get_called_class();
        $called_class_name = $called_class;
        if(preg_match("/\\\([^\\\]*)$/", $called_class, $arr)) $called_class_name = $arr[1];
        echo "UnitTest => ".$called_class_name."<br>\n";
        
        flush();
    }
}

?>