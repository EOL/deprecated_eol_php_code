<?php

class MysqliConnection
{
    private $server;
    private $user;
    private $password;
    private $database;
    private $encoding;
    private $port;
    private $socket;
    private $mysqli;
    private $master_server;
    private $master_user;
    private $master_password;
    private $master_database;
    private $master_encoding;
    private $master_port;
    private $master_socket;
    private $master_mysqli;
    private $transaction_in_progres;
    
    function __construct($server, $user, $password, $database, $encoding, $port, $socket, $master_server, $master_user, $master_password, $master_database, $master_encoding, $master_port, $master_socket)
    {
        $this->server = $server;
        $this->user = $user;
        $this->password = $password;
        $this->database = $database;
        $this->encoding = $encoding;
        $this->port = $port;
        $this->socket = $socket;
        $this->master_server = $master_server;
        $this->master_user = $master_user;
        $this->master_password = $master_password;
        $this->master_database = $master_database;
        $this->master_encoding = $master_encoding;
        $this->master_port = $master_port;
        $this->master_socket = $master_socket;  
        $this->transaction_in_progress = false;
        
        if(!$this->encoding) $this->encoding = "utf8";
        if(!$this->port) $this->port = NULL;
        if(!$this->socket) $this->socket = NULL;
        if(!$this->master_port) $this->master_port = NULL;
        if(!$this->master_socket) $this->master_socket = NULL;
        if(!$this->master_encoding) $this->master_encoding = $this->encoding;
    }
    
    function insert($query)
    {
        $this->check();
        $this->debug($query, true);
        
        $this->master_mysqli->query($query);
        if($err = mysqli_errno($this->master_mysqli)) return NULL;
        return $this->master_mysqli->insert_id;
    }
    
    function query($query)
    {
        if(preg_match("/^(insert|update|delete|truncate|create|drop|alter|load data)/i", trim($query), $arr))
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
    
    function multi_query($query)
    {
        $this->check();
        $this->debug($query, true);
        
        if($this->master_mysqli->multi_query($query))
        {
            $results = array();
            do
            {
                if($result = $this->master_mysqli->store_result()) $results[] = $result;
                elseif($this->master_mysqli->errno)
                {
                    trigger_error('MySQL multi_query Error: ' . $this->master_mysql->error, E_USER_WARNING);
                }
                
                if($result) $result->free();
            }while(@$this->master_mysqli->next_result());
        }else
        {
            trigger_error('MySQL multi_query Error: ' . $this->master_mysql->error, E_USER_WARNING);
        }
    }
    
    function master($query)
    {
        return $this->update($query);
    }
    
    function update($query)
    {
        $this->check();
        $this->debug($query, true);
        
        $result = $this->master_mysqli->query($query);
        return $result;
    }
    
    function load_data_infile($path, $table, $do_transaction = true, $commit_batches = false)
    {
        $insert_batch_size = 5000;
        if($do_transaction) $this->begin_transaction();
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
                $this->insert("INSERT IGNORE INTO `$table` VALUES (". implode("),(", $values) .")");
                if($commit_batches) $this->commit();
                $values = array();
            }
        }
        fclose($FILE);
        
        if(count($values)) $this->insert("INSERT IGNORE INTO `$table` VALUES (". implode("),(", $values) .")");
        if($commit_batches) $this->commit();
        unset($values);
        
        if($do_transaction) $this->end_transaction();
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
        if($GLOBALS['ENV_NAME'] != $environment) return false;
        
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
        if(is_null($string)) return NULL;
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
        mysql_debug('Beginning transaction');
        $this->autocommit(false);
    }
    
    function end_transaction()
    {
        $this->check();
        $this->commit();
        mysql_debug('Ending transaction');
        $this->autocommit(true);
    }
    
    function commit()
    {
        $this->check();
        mysql_debug('Committing');
        $this->master_mysqli->commit();
    }
    
    function rollback()
    {
        $this->check();
        mysql_debug('Rolling back');
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
    
    function errno()
    {
        return $this->master_mysqli->errno;
    }
    
    function error()
    {
        return $this->master_mysqli->error;
    }
    
    function debug($string, $master)
    {
        static $number_of_queries;
        static $number_of_master_queries;
        if(@$GLOBALS['ENV_MYSQL_DEBUG'])
        {
            if(!isset($number_of_queries)) $number_of_queries = 1;
            if(!isset($number_of_master_queries)) $number_of_master_queries = 1;
            
            $this->check();
            
            $return = "";
            if($master) $result = $this->mysqli->query("SELECT @@autocommit");
            else $result = $this->master_mysqli->query("SELECT @@autocommit");
            if($result && $row = $result->fetch_row())
            {
                $return .= "com: ".$row[0].", ";
                $result->free();
            }
            
            if($master) $return .= "M#$number_of_master_queries, ";
            else $return .= "#$number_of_queries, ";
            $return .= "id: ".$this->thread_id($master)."<br>\n";
            $return .= " $string;";
            
            mysql_debug($return);
            
            if($master) $number_of_master_queries++;
            else $number_of_queries++;
        }
    }
    
    function initialize()
    {
        mysql_debug("Connecting to host:$this->server, database:$this->database");
        $this->mysqli = @new mysqli($this->server, $this->user, $this->password, "", $this->port, $this->socket);
        
        if($this->mysqli->connect_errno)
        {
            trigger_error('MySQL Connect Error: ' . $this->mysqli->connect_error, E_USER_ERROR);
            exit;
        }
        
        if(!$this->mysqli->select_db($this->database))
        {
            if(@!$GLOBALS['ENV_ALLOW_MISSING_DB'])
            {
                trigger_error('MySQL Error: ' . $this->mysqli->error, E_USER_ERROR);
                exit;
            }
        }
        
        $this->mysqli->set_charset($this->encoding);
        
        if($this->master_server)
        {
            mysql_debug("Connecting to host:$this->master_server, database:$this->master_database");
            $this->master_mysqli = @new \mysqli($this->master_server, $this->master_user, $this->master_password, "", $this->master_port, $this->master_socket);
            
            if($this->master_mysqli->connect_errno)
            {
                trigger_error('MySQL Connect Error: ' . $this->master_mysqli->connect_error, E_USER_ERROR);
                exit;
            }
            
            if(!$this->master_mysqli($this->database))
            {
                if(@!$GLOBALS['ENV_ALLOW_MISSING_DB'])
                {
                    trigger_error('MySQL Error: ' . $this->master_mysqli->error, E_USER_ERROR);
                    exit;
                }
            }
            
            $this->master_mysqli->set_charset($this->master_encoding);
        }else
        {
            $this->master_server = $this->server;
            $this->master_user = $this->user;
            $this->master_password = $this->password;
            $this->master_database = $this->database;
            $this->master_encoding = $this->encoding;
            $this->master_port = $this->port;
            $this->master_socket = $this->socket;
            
            $this->master_mysqli =& $this->mysqli;
        }
    }
}

?>