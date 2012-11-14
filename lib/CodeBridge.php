<?php
include_once(dirname(__FILE__) . "/../config/environment.php");
if (defined('RESQUE_HOST')) {
  Resque::setBackend(RESQUE_HOST);
}

# Needed for work:
php_active_record\require_library("SplitEntryHandler");
php_active_record\require_library("MoveEntryHandler");
php_active_record\require_library("MergeConceptsHandler");
php_active_record\require_library("ReindexHandler");

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
        php_active_record\ReindexHandler::reindex_concept($this->args);
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
      if (array_key_exists('hierarchy_entry_id', $this->args)) {
        $GLOBALS['db_connection']->query("UPDATE hierarchy_entry_moves SET completed_at = NOW(), error = '" . $msg . "' WHERE hierarchy_entry_id = " . $this->args['hierarchy_entry_id'] . " AND classification_curation_id = " . $this->args['classification_curation_id']);
        echo "++ Updating move for HE " . $this->args['hierarchy_entry_id'] . ", curation " . $this->args['classification_curation_id'] . ". Message: $msg\n";

      } elseif(array_key_exists('classification_curation_id', $this->args)) { // This was a merge; there are no HEs, so we should only have one error on the curation itself:
        $GLOBALS['db_connection']->query("UPDATE classification_curations SET completed_at = NOW(), error = '" . $msg . "' WHERE id = " . $this->args['classification_curation_id']);
        echo "++ Updating curation " . $this->args['classification_curation_id'] . ". Message: $msg\n";
      }
      // Need to check_status_and_notify if we're not reindexing and if we have a curation ID:
      if ($this->args['cmd'] != 'reindex' && array_key_exists('classification_curation_id', $this->args)) {
        \Resque::enqueue('notifications', 'CodeBridge', array('cmd' => 'check_status_and_notify',
                         'classification_curation_id' => $this->args['classification_curation_id']));
        echo "++ Enqueued notifications/CodeBridge/check_status_and_notify(classification_curation_id = " .  $this->args['classification_curation_id'] . ")\n";
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

echo "** [" . date('g:i A', time()) . "] CodeBridge loaded.\n";

?>
