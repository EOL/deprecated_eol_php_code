<?php
namespace php_active_record;

class SSH2Connection
{
    private $ssh2_connection;
    private $sftp_connection;
    
    function __construct($server_ip, $username, $password = false)
    {
        debug("attempting to connect to $server_ip with $username::*****");
        $this->ssh2_connection = @ssh2_connect($server_ip, 22);
        if($this->ssh2_connection)
        {
            debug("Connected to $server_ip");
            $logged_in = @ssh2_auth_password($this->ssh2_connection, $username, $password);
            if($logged_in)
            {
                debug("Created SFTP connection to $server_ip");
                $this->sftp_connection = ssh2_sftp($this->ssh2_connection);
            }
        }
    }
    
    function sync_content($year, $month, $day, $hour)
    {
        if($this->ssh2_connection)
        {
            debug("Syncing $year::$month::$day::$hour");
            ssh2_exec($this->ssh2_connection, "/bin/rsync_scripts/download-updates.cgi $month $day $year $hour");
        }
    }
    
    function sync_logos()
    {
        if($this->ssh2_connection)
        {
            debug("Syncing logos");
            ssh2_exec($this->ssh2_connection, "/bin/rsync_scripts/download-content-partners.cgi");
        }
    }
    
    function remote_file_size($file_path)
    {
        $stat = @ssh2_sftp_stat($this->sftp_connection, $file_path);
        return @$stat["size"];
    }
    
    function download_file($file_path, $new_file_path)
    {
        if($this->sftp_connection) return @ssh2_scp_recv($this->ssh2_connection, $file_path, $new_file_path);
        
        return false;
    }
}

?>