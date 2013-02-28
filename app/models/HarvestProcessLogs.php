<?php
namespace php_active_record;

class HarvestProcessLog extends ActiveRecord
{
    public static $after_create = array(
            'insert_log'
        );
    
    public function insert_log()
    {
        $this->began_at = 'NOW()';
        $this->save();
        Functions::log("Starting $this->process_name");
    }
    
    public function finished()
    {
        unset($this->began_at);
        $this->completed_at = 'NOW()';
        $this->save();
        $this->refresh();
        Functions::log("Ended $this->process_name");
    }
}

?>