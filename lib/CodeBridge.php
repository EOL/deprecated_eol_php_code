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
    try {
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
        throw new Exception("No command available for ", $this->args['cmd']);
      }
    } catch (Exception $e) {
      $msg = $e->getMessage();
      echo "** Command Failed: ", $msg, "\n";
      // Unlock everything, send errors to watchers:
      if ($this->args['notify']) {
        if ($this->args['bad_match_hierarchy_entry_id']) {
          $tc_id = php_active_record\HierarchyEntry.find($this->args['bad_match_hierarchy_entry_id'])->taxon_concept_id;
          php_active_record\TaxonConcept::unlock_classifications_by_id($tc_id, $this->args['notify'], $msg);
        }
        if ($this->args['to_taxon_concept_id']) {
          php_active_record\TaxonConcept::unlock_classifications_by_id($this->args['to_taxon_concept_id'], $this->args['notify'], $msg);
        }
        if ($this->args['id1']) {
          php_active_record\TaxonConcept::unlock_classifications_by_id($this->args['id1'], $this->args['notify'], $msg);
        }
        if ($this->args['id2']) {
          php_active_record\TaxonConcept::unlock_classifications_by_id($this->args['id2'], $this->args['notify'], $msg);
        }
      }
      foreach($this->args as $key => $value) 
      { 
        echo "  '$key' = '$value'\n";
      } 
    }
  }

}

?>
