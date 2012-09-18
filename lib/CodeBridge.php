<?php
include_once(dirname(__FILE__) . "/../config/environment.php");
if (defined('RESQUE_HOST')) {
  Resque::setBackend(RESQUE_HOST);
}

# Needed for work:
php_active_record\require_library("SplitEntryHandler");
php_active_record\require_library("MoveEntryHandler");
php_active_record\require_library("MergeConceptsHandler");

// This is a way for PHP and Ruby to talk across Resque. If the class names are (exactly) the same, they can pass
// JSON back and forth fairly simply.
class CodeBridge
{

  public function perform()
  {
    $msg = '';
    try {
      $GLOBALS['db_connection']->close();
      $GLOBALS['db_connection']->initialize();
      if ($this->args['cmd'] == 'split') {
        php_active_record\SplitEntryHandler::split_entry($this->args);
      } elseif ($this->args['cmd'] == 'move') {
        // The 'reindex' argument from the command-line doesn't reindex solr, so I'm adding it automatically here:
        if ($this->args['reindex'] == 'reindex') {
          $this->args['reindex_solr'] = 'reindex_solr';
        }
        php_active_record\MoveEntryHandler::move_entry($this->args);
      } elseif ($this->args['cmd'] == 'merge') {
        php_active_record\MergeConceptsHandler::merge_concepts($this->args);
      } elseif ($this->args['cmd'] == 'reindex_taxon_concept') {
        php_active_record\TaxonConcept::reindex_descendants_objects($this->args['taxon_concept_id']);
        php_active_record\TaxonConcept::reindex_for_search($this->args['taxon_concept_id']);
        php_active_record\TaxonConcept::unlock_classifications_by_id($this->args['taxon_concept_id']);
      } else {
        throw new Exception("No command available for ", $this->args['cmd']);
      }
    } catch (Exception $e) {
      $msg = $e->getMessage();
      // Report for logging on the worker:
      echo "** [" . date('g:i A', time()) . "] Command Failed: ", $msg, "\n";
      foreach($this->args as $key => $value) 
      { 
        echo "  '$key' = '$value'\n";
      } 
      // Actual error logs to the DB (note that this can fail if the DB connection was severed),
      // so I'm attempting a reconnect, here:
      $GLOBALS['db_connection']->close();
      $GLOBALS['db_connection']->initialize();
    }
    try {
      if ($this->args['hierarchy_entry_id']) {
        $GLOBALS['db_connection']->query("UPDATE hiearchy_entry_moves SET completed_at = " . $something . ", error = '" . $msg . "' WHERE hierachy_entry_id = " . $this->args['hierarchy_entry_id'] . " AND classification_curation_id = " . $this->args['classification_curation_id']);
      } else { // This was a merge; there are no HEs, so we should only have one error on the curation itself:
        $GLOBALS['db_connection']->query("UPDATE classification_curations SET completed_at = " . $something . ", error = '" . $msg . "' WHERE id = " . $this->args['classification_curation_id']);
      }
      // Don't need to check_status_and_notify if we're reindexing:
      if ($this->args['cmd'] != 'reindex') {
        \Resque::enqueue('notifications', 'CodeBridge', array('cmd' => 'check_status_and_notify',
                         'classification_curation_id' => $this->args['classification_curation_id']));
      }
    } catch (Exception $e) {
      // Well, shoot, logging the error failed... just shout via STDOUT, I suppose:
      echo "** [" . date('g:i A', time()) . "] Logging Failed: ", $e->getMessage(), "\n";
      foreach($this->args as $key => $value) 
      { 
        echo "  '$key' = '$value'\n";
      } 
    }
    $GLOBALS['db_connection']->initialize();
  }

}

?>
