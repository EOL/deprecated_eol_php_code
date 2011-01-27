<?php

class HarvestProcessLog extends MysqlBase
{
    function __construct($param, $process_name = null)
    {
        $this->table_name = Functions::class_name(__FILE__);
        parent::initialize($param);
        if(@!$this->id) return;
        if($process_name) $this->process_name = $process_name;
    }
    
    public static function all()
    {
        $all = array();
        $result = $GLOBALS['db_connection']->query("SELECT * FROM harvest_process_logs");
        while($result && $row=$result->fetch_assoc())
        {
            $all[] = new HarvestProcessLog($row);
        }
        return $all;
    }
    
    public function finished()
    {
        $GLOBALS['db_connection']->update("UPDATE harvest_process_logs SET completed_at = NOW() WHERE id=$this->id");
        Functions::log("Ended $this->process_name");
    }
    
    static function create($process_name)
    {
        Functions::log("Starting $process_name");
        $id = $GLOBALS['db_connection']->insert("INSERT INTO harvest_process_logs (`process_name`, `began_at`) VALUES ('". $GLOBALS['db_connection']->escape($process_name) ."', NOW())");
        return new HarvestProcessLog($id, $process_name);
    }
}

?>