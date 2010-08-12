<?php

class TaxonFinderClient
{
    private $socket;
    private $socket_ip;
    private $socket_port;
    private $connected;
    
    function __construct()
    {
        $this->connect();
    }
    
    private function connect()
    {
        $this->socket_ip = TAXONFINDER_SOCKET_SERVER;
        $this->socket_port = TAXONFINDER_SOCKET_PORT;
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        $this->connected = socket_connect($this->socket, $this->socket_ip, $this->socket_port);
    }
    
    public function connected()
    {
        if ($this->socket === false)
        {
            $errorcode = socket_last_error();
            $errormsg = socket_strerror($errorcode);

            die("Problem with socket connection: [$errorcode] $errormsg");
        }
        
        return $this->connected;
    }
    
    public function check_word($parameters)
    {
        if(!$this->connected) return false;
        
        $word = $parameters["word"];
        $current_string = @$parameters["current_string"] ? $parameters["current_string"] : "";
        $current_string_state = @$parameters["current_string_state"] ? $parameters["current_string_state"] : "";
        $word_list_matches = @$parameters["word_list_matches"] ? $parameters["word_list_matches"] : "";
        $fuzzy_matching = @$parameters["fuzzy_matching"] ? $parameters["fuzzy_matching"] : "";
        
        $word = trim($word);
        if(!$word) return false;
        
        // This isn't a name and can immediately return
        if($word == TAXONFINDER_STOP_KEYWORD && !$current_string)
        {
            $return_array = array();
            
            $return_array["current_string"] = "";
            $return_array["current_string_state"] = "";
            $return_array["word_list_matches"] = "";
            $return_array["return_string"] = "";
            $return_array["return_code"] = "";
            $return_array["return_string_2"] = "";
            $return_array["return_code_2"] = "";
            $return_array["fuzzy_matching"] = $fuzzy_matching;
            
            return $return_array;
        }
        
        $word = str_replace("\n"," ",$word);
        $word = str_replace("\r"," ",$word);
        $word = preg_replace("/\s/"," ",$word);
        $in = trim($word)."|$current_string|$current_string_state|$word_list_matches|$fuzzy_matching\n";
        
        if(socket_write($this->socket, $in, strlen($in)) === false)
        {
            $errorcode = socket_last_error();
            $errormsg = socket_strerror($errorcode);
            
            die("Problem with socket connection: [$errorcode] $errormsg");
        }
        if($out = socket_read($this->socket, 2048))
        {
            list($cs, $css, $wlm, $rst, $rsc, $rst2, $rsc2) = explode("|",trim($out));
            
            $return_array = array();
            
            $return_array["current_string"] = $cs;
            $return_array["current_string_state"] = $css;
            $return_array["word_list_matches"] = $wlm;
            $return_array["return_string"] = $rst;              // First found namestring
            $return_array["return_code"] = $rsc;                // Score for first found name string
            $return_array["return_string_2"] = $rst2;           // Second found name string
            $return_array["return_code_2"] = $rsc2;             // Scrore for second found name string
            $return_array["fuzzy_matching"] = $fuzzy_matching;
            
            return $return_array;
        }
        
        return false;
    }
}

?>