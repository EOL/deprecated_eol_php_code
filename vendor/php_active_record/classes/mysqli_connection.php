<?php
namespace php_active_record;

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
        if($this->master_mysqli->errno)
        {
            trigger_error('MySQL insert Error: ' . $this->master_mysqli->error .
                " ($query)", E_USER_WARNING);
            mysql_debug('MySQL insert Error: ' . $this->master_mysqli->error, E_USER_WARNING);
        }
        if($err = mysqli_errno($this->master_mysqli)) return NULL;
        return $this->master_mysqli->insert_id;
    }

    function update($query)
    {
        $this->check();
        $this->debug($query, true);

        $result = $this->master_mysqli->query($query);
        if($this->master_mysqli->errno)
        {
            trigger_error('MySQL update Error: ' . $this->master_mysqli->error .
                " ($query)", E_USER_WARNING);
            mysql_debug('MySQL update Error: ' . $this->master_mysqli->error, E_USER_WARNING);
        }
        if($err = mysqli_errno($this->master_mysqli)) return NULL;
        return $result;
    }

    function master($query)
    {
        return $this->update($query);
    }

    function delete($query)
    {
        $this->check();
        $this->debug($query, true);

        $result = $this->master_mysqli->query($query);
        if($this->master_mysqli->errno)
        {
            trigger_error('MySQL delete Error: ' . $this->master_mysqli->error .
                " ($query)", E_USER_WARNING);
            mysql_debug('MySQL delete Error: ' . $this->master_mysqli->error, E_USER_WARNING);
        }
        if($err = mysqli_errno($this->master_mysqli)) return NULL;
        return $result;
    }

    function select($query)
    {
        $this->check();
        $this->debug($query, false);

        $result = $this->mysqli->query($query);
        if($this->mysqli->errno)
        {
            trigger_error('MySQL select Error: ' . $this->mysqli->error .
                " ($query)", E_USER_WARNING);
            mysql_debug('MySQL select Error: ' . $this->master_mysqli->error, E_USER_WARNING);
        }
        if($err = mysqli_errno($this->mysqli)) return NULL;
        return $result;
    }

    function select_value($query)
    {
        if($result = $this->select($query))
        {
            if($row = $result->fetch_row()) return $row[0];
        }
    }

    public static function is_read_query($query)
    {
        return !self::is_write_query($query);
    }

    public static function is_write_query($query)
    {
        if(preg_match("/^(insert|update|delete|truncate|create|drop|alter|load data)/i", trim($query), $arr))
        {
            return true;
        }
        return false;
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

        return $this->select($query);
    }

    function multi_query($query)
    {
        if(!trim($query)) return;
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
                    trigger_error('MySQL multi_query Error: ' .
                        $this->master_mysqli->error .
                        " ($query)", E_USER_WARNING);
                }

                if($result) $result->free();
            }while(@$this->master_mysqli->next_result());
        }else
        {
            trigger_error('MySQL multi_query Error: ' .
                $this->master_mysqli->error .
                " ($query)", E_USER_WARNING);
        }
    }

    function load_data_infile($path, $table, $action = "IGNORE", $set = '', $udelay = 500000, $maximum_rows_in_file = 50000)
    {
        if($action != "REPLACE") $action = "IGNORE";
        // how many rows to split the larger file into
        if($action == 'REPLACE') $maximum_rows_in_file = 20000;
        $tmp_file_path = temp_filepath();

        $this->begin_transaction();
        //$this->insert("SET FOREIGN_KEY_CHECKS = 0");
        if(!($LOAD_DATA_TEMP = fopen($tmp_file_path, "w+")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$tmp_file_path);
          return(false);
        }
        //flock($LOAD_DATA_TEMP, LOCK_EX);

        $line_counter = 0;
        $batch = 0;
        if(!($FILE = fopen($path, "r")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$path);
          return(false);
        }
        while(!feof($FILE))
        {
            if($line = fgets($FILE, 4096))
            {
                fwrite($LOAD_DATA_TEMP, $line);

                $line_counter++;
                // load data if we have enough rows
                if($line_counter >= $maximum_rows_in_file)
                {
                    $batch++;
                    if($GLOBALS['ENV_DEBUG']) echo "Committing ".$batch*$maximum_rows_in_file." : ".time_elapsed()."\n";
                    @$this->update("LOAD DATA LOCAL INFILE '".str_replace("\\", "/", $tmp_file_path)."' $action INTO TABLE `$table` FIELDS TERMINATED BY '\\t' LINES TERMINATED BY '\\n' $set");
                    $this->commit();
                    usleep_production($udelay);
                    rewind($LOAD_DATA_TEMP);
                    ftruncate($LOAD_DATA_TEMP, 0);
                    $line_counter = 0;
                }
            }
        }
        fclose($FILE);

        // insert the remaining rows
        if(filesize($tmp_file_path))
        {
            @$this->update("LOAD DATA LOCAL INFILE '".str_replace("\\", "/", $tmp_file_path)."' $action INTO TABLE `$table` FIELDS TERMINATED BY '\\t' LINES TERMINATED BY '\\n' $set");
            $this->commit();
            usleep_production($udelay);
        }

        //$this->insert("SET FOREIGN_KEY_CHECKS = 1");
        $this->end_transaction();
        unlink($tmp_file_path);
        return(true);
    }

    function select_into_outfile($query, $escape = false)
    {
        $query = str_replace("\n", " ", $query);
        $query = str_replace("\r", " ", $query);
        $query = str_replace("\t", " ", $query);
        $tmp_file_path = temp_filepath();

        // perpare the command line command
        $command = MYSQL_BIN_PATH . " --host=$this->server --user=$this->user --password=$this->password --database=$this->database --compress --column-names=false";
        if($this->port) $command .= " --port=$this->port";
        if($this->encoding) $command .= " --default-character-set=$this->encoding";
        $command .= " -e \"$query\"  > $tmp_file_path";

        // echo "$command\n";
        // execute the query
        $this->debug($query, true);
        shell_exec($command);
        if(file_exists($tmp_file_path)) return $tmp_file_path;
        return false;
    }

    function delete_from_where($table, $field, $select, $udelay = 1000000)
    {
        $outfile = $this->select_into_outfile($select);

        $ids = array();
        $this->begin_transaction();
        if(!($FILE = fopen($outfile, "r")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$outfile);
          return;
        }
        while(!feof($FILE))
        {
            if($line = fgets($FILE, 4096))
            {
                $ids[] = trim($line);
                if(count($ids)>=10000)
                {
                    $this->delete("DELETE FROM $table WHERE $field IN (".implode(",", $ids).")");
                    $this->commit();
                    usleep_production($udelay);
                    $ids = array();
                }
            }
        }
        if($ids)
        {
            $this->delete("DELETE FROM $table WHERE $field IN (".implode(",", $ids).")");
            $this->commit();
            usleep_production($udelay);
        }
        fclose($FILE);
        $this->end_transaction();
        unlink($outfile);
    }

    function update_where($table, $field, $select, $set, $udelay = 500000, $batch_size = 10000)
    {
        $outfile = $this->select_into_outfile($select);
        $ids = array();
        $this->begin_transaction();
        if(!($FILE = fopen($outfile, "r")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$outfile);
          return;
        }
        while(!feof($FILE))
        {
            if($line = fgets($FILE, 4096))
            {
                $ids[] = trim($line);
                if(count($ids) >= $batch_size)
                {
                    $update_query = "UPDATE $table SET $set WHERE $field IN (".implode(",", $ids).")";
                    $this->update($update_query);
                    $this->commit();
                    usleep_production($udelay);
                    $ids = array();
                }
            }
        }
        if($ids)
        {
            $update_query = "UPDATE $table SET $set WHERE $field IN (".implode(",", $ids).")";
            $this->update($update_query);
            $this->commit();
            usleep_production($udelay);
        }
        fclose($FILE);
        $this->end_transaction();
        unlink($outfile);
    }

    function &iterate($query)
    {
        $result = $this->query($query);
        $result_iterator = new MysqliResultIterator($result);
        return $result_iterator;
    }

    function &iterate_file($query)
    {
        $it = new MysqliResultFileIterator($query, $this);
        return $it;
    }

    function truncate_tables($environment = "test")
    {
        if($GLOBALS['ENV_NAME'] != $environment) return false;
        $this->check();

        $query = "";
        $this->debug("show tables from ".$this->master_database, true);
        $result = $this->master_mysqli->query("show tables from ".$this->master_database);
        while($result && $row=$result->fetch_row())
        {
            $table = $row[0];
            $count_results = $this->master_mysqli->query("select 1 from $table limit 1");
            if($count_results && $count_results->num_rows)
            {
                $query .= "TRUNCATE TABLE $table;";
            }
        }
        if($query) $this->multi_query($query);
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

    function in_transaction()
    {
        $this->check();
        // if @@autocommit == 1 - there is NOT a transaction
        $result = $this->master_mysqli->query("select @@autocommit as not_in_transaction");
        if($result && $row=$result->fetch_assoc())
        {
            if($row['not_in_transaction'] == 0) return true;
        }
        return false;
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
        if($this->master_mysqli !== $this->mysqli) $this->master_mysqli->close();
    }

  function check()
  {
    if (!$this->mysqli) $this->initialize();
    if (!$this->master_mysqli) $this->initialize();
    $this->debug_if_still_disconnected();
  }

  function debug_if_still_disconnected()
  {
    // I'm told that if mysql "goes away," you can simply "bring it back" by re-issuing the same command (if you are configured to do so), so we actually attempt the ping TWICE, here--the first one is allowed to fail.
    $this->mysqli->ping();
    $this->master_mysqli->ping();
    if (!$this->mysqli->ping() || !$this->master_mysqli->ping()) {
      $error = 'MySQL Ping failed: server is down?';
      debug($error);
      throw new \Exception($error);
    }
  }

    function errno()
    {
        return $this->master_mysqli->errno;
    }

    function error()
    {
        return $this->master_mysqli->error;
    }

    function affected_rows()
    {
        return $this->master_mysqli->affected_rows;
    }

    function swap_tables($table_one, $table_two)
    {
        $swap = $table_one . "_to_swap";
        $this->update("RENAME TABLE $table_one TO $swap,
                                    $table_two TO $table_one,
                                    $swap TO $table_two");
    }

    function table_exists($table_name)
    {
        $result = $this->query("SHOW TABLES LIKE '". $this->escape($table_name) ."'");
        if($result && $row=$result->fetch_assoc())
        {
            return true;
        }
        return false;
    }

    function debug($string, $master)
    {
        static $number_of_queries;
        static $number_of_master_queries;
        if((@$GLOBALS['ENV_MYSQL_DEBUG'] && @$GLOBALS['ENV_DEBUG']) || $GLOBALS['ENV_NAME'] == 'test')
        {
            if(!isset($number_of_queries)) $number_of_queries = 1;
            if(!isset($number_of_master_queries)) $number_of_master_queries = 1;

            $this->check();

            $return = "db";
            if($master) $return .= "(M) $number_of_master_queries";
            else $return .= " $number_of_queries";
            $return .= "\n\t$string";

            mysql_debug($return);

            if($master) $number_of_master_queries++;
            else $number_of_queries++;
        }
    }

    function initialize()
    {
        echo "\n\n";
        mysql_debug("Connecting to host:$this->server, database:$this->database");
        $this->mysqli = new \mysqli();
        $this->mysqli->init();
        $this->mysqli->options(MYSQLI_OPT_LOCAL_INFILE, true);
        $this->mysqli->real_connect($this->server, $this->user, $this->password, "", $this->port, $this->socket, MYSQLI_CLIENT_COMPRESS);

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
            $this->master_mysqli = new \mysqli();
            $this->master_mysqli->init();
            $this->master_mysqli->options(MYSQLI_OPT_LOCAL_INFILE, true);
            $this->master_mysqli->real_connect($this->master_server, $this->master_user, $this->master_password, "", $this->master_port, $this->master_socket, MYSQLI_CLIENT_COMPRESS);

            if($this->master_mysqli->connect_errno)
            {
                trigger_error('MySQL Connect Error: ' . $this->master_mysqli->connect_error, E_USER_ERROR);
                exit;
            }

            if(!$this->master_mysqli->select_db($this->database))
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
