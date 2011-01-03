<?php

class HarvestProcessesLog extends MysqlBase
{
    function __construct($param)
    {
        $this->table_name = Functions::class_name(__FILE__);
        parent::initialize($param);
        if(@!$this->id) return;
    }
    
    public static function all()
    {
        $all = array();
        $result = $GLOBALS['db_connection']->query("SELECT * FROM harvest_processes_log");
        while($result && $row=$result->fetch_assoc())
        {
            $all[] = new HarvestProcessesLog($row);
        }
        return $all;
    }
    
    public function finished()
    {
        $GLOBALS['db_connection']->update("UPDATE harvest_processes_log SET completed_at = NOW() WHERE id=$this->id");
        Functions::log("Ended $this->process_name");
    }
    
    static function create($process_name)
    {
        Functions::log("Starting $process_name");
        $id = $GLOBALS['db_connection']->insert("INSERT INTO harvest_processes_log (`process_name`, `started_at`) VALUES ('". $GLOBALS['db_connection']->escape($process_name) ."', NOW())");
        return new HarvestProcessesLog($id);
    }
}

?>