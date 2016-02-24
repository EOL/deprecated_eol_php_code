<?php
namespace php_active_record;

class TopImagesHandler
{
    // YOU HAVE BEEN WARNED: this takes about 2.5 *DAYS* to complete. Be
    // careful. (UPDATE: that might have been due to a corrupt slave controller.
    // Check the time again.)
    public static function top_images()
    {
      require_library('TopImages');
      $log = HarvestProcessLog::create(array('process_name' => 'Top Images'));
      $top_images = new TopImages();
      $top_images->begin_process();
      $top_images->top_concept_images(true);
      $top_images->top_concept_images(false);
      \Resque::enqueue('harvesting', 'CodeBridge',
        array('cmd' => 'denormalize_tables'));
      $log->finished();
    }
}

?>
