<?php

class MysqlConnection
{
    private $server;
    private $user;
    private $password;
    private $database;
    private $encoding;
    private $mysqli;
    private $master_server;
    private $master_user;
    private $master_password;
    private $master_database;
    private $master_encoding;
    private $master_mysqli;
    private $transaction_in_progres;
    
    function __construct($server, $user, $password, $database, $encoding, $master_server, $master_user, $master_password, $master_database, $master_encoding)
    {
        $this->server = $server;
        $this->user = $user;
        $this->password = $password;
        $this->database = $database;
        $this->encoding = $encoding;
        $this->master_server = $master_server;
        $this->master_user = $master_user;
        $this->master_password = $master_password;
        $this->master_database = $master_database;
        $this->master_encoding = $master_encoding;
        $this->transaction_in_progress = false;
        
        if(!$this->encoding) $this->encoding = "utf8";
        if(!$this->master_encoding) $this->master_encoding = $this->encoding;
    }
    
    function insert($query)
    {
        $this->check();
        $this->debug($query, true);
        
        $this->master_mysqli->query($query);
        return $this->master_mysqli->insert_id;
    }
    
    function query($query)
    {
        if(preg_match("/^(insert|update|delete|truncate|load data)/i", trim($query), $arr))
        {
            switch(strtolower($arr[1]))
            {
                case "insert":
                    return $this->insert($query);
                    break;
                case "update":
                    return $this->update($query);
                    break;
                default:
                    return $this->delete($query);
                    break;
            }
        }
        
        $this->check();
        $this->debug($query, false);
        
        $result = $this->mysqli->query($query);
        return $result;
    }
    
    function update($query)
    {
        $this->check();
        $this->debug($query, true);
        
        $result = $this->master_mysqli->query($query);
        return $result;
    }
    
    function load_data_infile($path, $table)
    {
        $insert_batch_size = 5000;
        $this->begin_transaction();
        $values = array();
        $FILE = fopen($path, "r");
        while(!feof($FILE))
        {
            if($line = fgets($FILE, 4096))
            {
                $line = rtrim($line, "\n\r");
                $values[] = str_replace("\t", ",", $line);
            }
            if(count($values) > $insert_batch_size)
            {
                //echo "INSERT INTO `$table` VALUES (". implode("),(", $values) .")\n";
                $this->insert("INSERT INTO `$table` VALUES (". implode("),(", $values) .")");
                $values = array();
            }
        }
        if(count($values)) $this->insert("INSERT INTO `$table` VALUES (". implode("),(", $values) .")");
        $this->end_transaction();
    }
    
    function delete($query)
    {
        $this->check();
        $this->debug($query, true);
        
        $result = $this->master_mysqli->query($query);
        return $result;
    }
    
    function truncate_tables($environment = "test")
    {
        if(ENVIRONMENT!=$environment) return false;
        
        $this->check();
        $this->debug("show tables from ".$this->master_database, true);
                
        $result = $this->master_mysqli->query("show tables from ".$this->master_database);
        while($result && $row=$result->fetch_assoc())
        {
            $this->debug("TRUNCATE TABLE ".$row["Tables_in_".$this->master_database], true);
            $this->master_mysqli->query("TRUNCATE TABLE ".$row["Tables_in_".$this->master_database]);
        }
    }
    
    function real_escape_string($string)
    {
        $this->check();
        return $this->master_mysqli->real_escape_string($string);
    }
    
    function escape($string)
    {
        $this->check();
        return $this->master_mysqli->real_escape_string($string);
    }
    
    function thread_id($master)
    {
        $this->check();
        if($master) return $this->master_mysqli->thread_id;
        return $this->mysqli->thread_id;
    }
    
    function autocommit($commit)
    {
        $this->check();
        $this->master_mysqli->autocommit($commit);
        if($commit) $this->transaction_in_progress = false;
        else $this->transaction_in_progress = true;
    }
    
    function begin_transaction()
    {
        $this->check();
        $this->autocommit(false);
    }
    
    function end_transaction()
    {
        $this->check();
        $this->commit();
        $this->autocommit(true);
    }
    
    function commit()
    {
        $this->check();
        $this->master_mysqli->commit();
    }
    
    function rollback()
    {
        $this->check();
        $this->master_mysqli->rollback();
    }
    
    function close()
    {
        $this->check();
        $this->mysqli->close();
        $this->master_mysqli->close();
    }
    
    function check()
    {
        if(!$this->mysqli) $this->initialize();
    }
    
    function debug($string, $master)
    {
        static $number_of_queries;
        static $number_of_master_queries;
        if(defined("MYSQL_DEBUG") && MYSQL_DEBUG)
        {
            if(!isset($number_of_queries)) $number_of_queries = 1;
            if(!isset($number_of_master_queries)) $number_of_master_queries = 1;
            
            $return = "";
            if($master) $result = $this->mysqli->query("SELECT @@autocommit");
            else $result = $this->master_mysqli->query("SELECT @@autocommit");
            if($row = $result->fetch_row())
            {
                $return .= "com: ".$row[0].", ";
                $result->free();
            }
            
            if($master) $return .= "M#$number_of_master_queries, ";
            else $return .= "#$number_of_queries, ";
            $return .= "id: ".$this->thread_id($master)."<br>\n";
            $return .= " $string;";
            
            Functions::mysql_debug($return);
            
            if($master) $number_of_master_queries++;
            else $number_of_queries++;
        }
    }
    
    function initialize()
    {
        Functions::mysql_debug("Connecting to host:$this->server, database:$this->database");
        $this->mysqli = new mysqli($this->server, $this->user, $this->password, $this->database);
        $this->mysqli->set_charset($this->encoding);
        
        if($this->master_server)
        {
            Functions::mysql_debug("Connecting to host:$this->master_server, database:$this->master_database");
            $this->master_mysqli = new mysqli($this->master_server, $this->master_user, $this->master_password, $this->master_database);
            $this->master_mysqli->set_charset($this->master_encoding);
        }else
        {
            $this->master_server = $this->server;
            $this->master_user = $this->user;
            $this->master_password = $this->password;
            $this->master_database = $this->database;
            $this->master_encoding = $this->encoding;
            
            $this->master_mysqli = @$this->mysqli;
        }
    }
    
    function time_elapsed()
    {
        static $a;
        if(!isset($a)) $a = microtime(true);
        return (string) round(microtime(true)-$a, 6);
    }
}

?>