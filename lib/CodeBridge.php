<?php
date_default_timezone_set('America/Denver');  // TODO - This should be moved to the config file, I think.
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
    } else {
      throw new Exception("No command available for " . $this->args['cmd']);
    }
  }

}

?>
