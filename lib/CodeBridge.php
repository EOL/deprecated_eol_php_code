<?php
include_once(dirname(__FILE__) . "/../config/environment.php");
include_once(dirname(__FILE__) . "/../vendor/php_resque/lib/Resque.php");
if(defined('RESQUE_HOST') && RESQUE_HOST && class_exists('Resque'))
{
  \Resque::setBackend(RESQUE_HOST);
} else {
  print "Cannot set Resque backend!\n";
  exit(1);
}

# Needed for work:
php_active_record\require_library("SplitEntryHandler");
php_active_record\require_library("MoveEntryHandler");
php_active_record\require_library("MergeConceptsHandler");
php_active_record\require_library("ReindexHandler");
php_active_record\require_library("TopImagesHandler");

// This is a way for PHP and Ruby to talk across Resque. If the class names are (exactly) the same, they can pass
// JSON back and forth fairly simply.
class CodeBridge
{
    public function perform()
    {
        CodeBridge::print_message('CodeBridge performing.');

        $mysqli =& $GLOBALS['db_connection'];
        $error_message = '';
        try
        {
            $mysqli->close();
            $mysqli->initialize();
            echo "Code bridge was called with:\n";
            print_r($this->args);
            if ($this->args['cmd'] == 'split') php_active_record\SplitEntryHandler::split_entry($this->args);
            elseif ($this->args['cmd'] == 'move') php_active_record\MoveEntryHandler::move_entry($this->args);
            elseif ($this->args['cmd'] == 'merge') php_active_record\MergeConceptsHandler::merge_concepts($this->args);
            elseif ($this->args['cmd'] == 'reindex_taxon_concept') php_active_record\ReindexHandler::reindex_concept($this->args);
            elseif ($this->args['cmd'] == 'top_images') php_active_record\TopImagesHandler::top_images();
            else throw new Exception("No command available for ", $this->args['cmd']);
        }catch (Exception $e)
        {
            $error_message = $e->getMessage();
            // Report for logging on the worker:
            CodeBridge::print_message("Command Failed: $error_message");
            print_r($this->args);

            // Actual error logs to the DB (note that this can fail if the DB connection was severed),
            // so I'm attempting a reconnect, here:
            $mysqli->close();
            $mysqli->initialize();
        }

        try
        {
            if (array_key_exists('hierarchy_entry_id', $this->args))
            {
                $mysqli->query("UPDATE hierarchy_entry_moves SET completed_at = NOW(), error = '". $mysqli->escape($error_message) ."' WHERE hierarchy_entry_id = " .
                    $this->args['hierarchy_entry_id'] ." AND classification_curation_id = ". $this->args['classification_curation_id']);
                CodeBridge::print_message("Updating move for HE ". $this->args['hierarchy_entry_id'] .", curation ". $this->args['classification_curation_id'] .". Message: $error_message");
            }elseif(array_key_exists('classification_curation_id', $this->args)) // This was a merge; there are no HEs, so we should only have one error on the curation itself:
            {
                $mysqli->query("UPDATE classification_curations SET completed_at = NOW(), error = '". $mysqli->escape($error_message) ."' WHERE id = ". $this->args['classification_curation_id']);
                CodeBridge::print_message("Updating curation ". $this->args['classification_curation_id'] .". Message: $error_message");
            }
            // Need to check_status_and_notify if we're not reindexing and if we have a curation ID:
            if ($this->args['cmd'] == 'reindex_taxon_concept' && array_key_exists('taxon_concept_id', $this->args))
            {
                \Resque::enqueue('notifications', 'CodeBridge', array('cmd' => 'clear_cache',
                    'taxon_concept_id' => $this->args['taxon_concept_id']));
                CodeBridge::print_message("++ Enqueued notifications/CodeBridge/clear_cache(taxon_concept_id = ". $this->args['taxon_concept_id'] .")");
            }elseif (array_key_exists('classification_curation_id', $this->args))
            {
                \Resque::enqueue('notifications', 'CodeBridge', array('cmd' => 'check_status_and_notify',
                    'classification_curation_id' => $this->args['classification_curation_id']));
                CodeBridge::print_message("++ Enqueued notifications/CodeBridge/check_status_and_notify(classification_curation_id = ". $this->args['classification_curation_id'] .")");
            }
        }catch (Exception $e)
        {
            // Well, shoot, logging the error failed... just shout via STDOUT, I suppose:
            CodeBridge::print_message("Logging Failed: ". $e->getMessage());
            print_r($this->args);
        }
        $mysqli->initialize();
    }

    public static function print_message($message)
    {
        echo "\n++ [" . date('g:i A', time()) . "] $message\n\n";
    }

	public static function update_resource_contributions($resource_id)
    {
     	// inform rails when resource finish harvest
        \Resque::enqueue('notifications', 'CodeBridge', array('cmd' => 'update_resource_contributions',
                        'resource_id' => $resource_id));
        CodeBridge::print_message("++ Enqueued notifications/CodeBridge/update_resource_contributions(resource_id = ". $resource_id .")");
    }

}

?>
