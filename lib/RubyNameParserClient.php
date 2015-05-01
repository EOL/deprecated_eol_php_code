<?php
namespace php_active_record;

class RubyNameParserClient
{
    private $socket;
    private $socket_ip;
    private $socket_port;
    private $connected;

    function __construct($params = array())
    {
        $this->connect($params);
    }

    private static function start_parserver()
    {
        $gem_is_installed = shell_exec('which parserver');
        if(!$gem_is_installed) return false;
        $maximum_attempts = 2;
        $reconnect_attempts = 0;
        while(!self::is_parserver_running() && $reconnect_attempts < $maximum_attempts)
        {
            shell_exec(rtrim($gem_is_installed) . ' -r --output=canonical > /dev/null 2>/dev/null &');
            sleep(10);
            $reconnect_attempts++;
        }
        if(!self::is_parserver_running())
        {
            trigger_error("NameParserGemClient:: Unable to start parserver. You may need to `sudo gem install biodiversity --version '=1.0.10'`", E_USER_WARNING);
        }
    }

    public static function is_parserver_running()
    {
	return (self::parserver_check('ps -e') or self::parserver_check('ps -e x'));
    }

    public static function parserver_check($ps_cmd)
    {
        $processlist = explode("\n", shell_exec($ps_cmd));
        foreach($processlist as $line)
        {
            if(preg_match("/:[0-9]{2}:[0-9]{2} parserver$/", trim($line))) return true;
            if(preg_match("/bin\/parserver$/", trim($line))) return true;
            if(preg_match("/parserver -r --output=canonical/", trim($line))) return true;
        }
        return false;
    }

    private function connect($params = array())
    {
        self::start_parserver();
        if(@!$params['ip']) $params['ip'] = '127.0.0.1';
        if(@!$params['port']) $params['port'] = '4334';
        $this->timeout_retry_seconds = 120;
        $this->socket_ip = $this->socket_ip ?: $params['ip'];
        $this->socket_port = $this->socket_port ?: $params['port'];
        $this->timeout_time = null;
        $this->fsock = fsockopen($this->socket_ip, $this->socket_port, $this->errno, $this->errstr, 10);
        if($this->fsock === false)
        {
            trigger_error("NameParserGemClient:: Problem with socket connection: [$this->errno] $this->errstr", E_USER_WARNING);
            $this->connected = false;
        }else
        {
            $this->connected = true;
            stream_set_timeout($this->fsock, 2);
        }
    }

    public function lookup_string($string)
    {
        if(!$this->fsock)
        {
            if($this->timeout_time && (microtime(true) - $this->timeout_time) >= $this->timeout_retry_seconds)
            {
                $this->connect();
                $this->lookup_string($string);
            }
            return false;
        }

        $string = str_replace("\n", " ", $string);
        $string = str_replace("\r", " ", $string);
        $string = preg_replace("/\s/", " ", $string);

        $string = trim($string) . "\n";
        if(fwrite($this->fsock, $string) === false)
        {
            $errorcode = socket_last_error();
            $errormsg = socket_strerror($errorcode);
            trigger_error("NameParserGemClient:: Problem with socket connection: [$this->errno] $this->errstr", E_USER_WARNING);
        }

        if($out = fread($this->fsock, 2048))
        {
            return rtrim($out, "\n");
        }

        $info = stream_get_meta_data($this->fsock);
        if($info['timed_out'])
        {
            $this->timeout_time = microtime(true);
            fclose($this->fsock);
            $this->fsock = false;
        }

        return false;
    }
}

?>
