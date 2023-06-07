<?php
namespace php_active_record;
/* 
*/
class RetrieveOrRunAPI
{
    function __construct($task2run, $download_options, $main_path)
    {
        $this->task2run = $task2run;
        $this->dl_options = $download_options;

        if(Functions::is_production()) $this->dl_options['cache_path'] = "/extra/eol_php_cache/";
        else                           $this->dl_options['cache_path'] = "/Volumes/Crucial_2TB/eol_cache/"; //used in Functions.php for all general cache
        $this->main_path = $this->dl_options['cache_path'].$main_path."/";
        if(!is_dir($this->main_path)) mkdir($this->main_path);

        /* how to call:
        $filename = self::generate_path_filename($input);
        $json = self::retrieve_data($input, $filename);
        */
    }
    function retrieve_data($input)
    {   
        $filename = self::generate_path_filename($input);

        // /* a security block that prevents from creating blank cache files
        if(file_exists($filename)) {
            if(filesize($filename) == 0) unlink($filename); //this means this file was left hanging after program is terminated (ctrl+c).
        }
        // */

        if(file_exists($filename)) {
            debug("\nCache already exists. [$filename]\n");
            $file_age_in_seconds = time() - filemtime($filename);
            if($file_age_in_seconds < $this->dl_options['expire_seconds']) return self::retrieve_json($filename); //not yet expired
            if($this->dl_options['expire_seconds'] === false)              return self::retrieve_json($filename); //doesn't expire
            /* ----- At this point, cache is expired already ----- */
            debug("\nCache expired. Will run task now...\n");
            self::run_task($input, $filename);
            return self::retrieve_json($filename);
        }
        else {
            debug("\nRun task for the 1st time...\n");
            self::run_task($input, $filename);
            return self::retrieve_json($filename);
        }
    }
    private function retrieve_json($filename)
    {
        $json = file_get_contents($filename);
        return $json;
    }
    private function run_task($input, $filename)
    {   
        if    ($this->task2run == 'gnparser')   $json = self::task_gnparser($input['sciname']);
        elseif($this->task2run == 'task_?')     $json = "whatever...";
        else exit("\nNo defined task2run. Will terminate.\n");

        if($json) {
            $WRITE = Functions::file_open($filename, "w");
            fwrite($WRITE, $json); fclose($WRITE);
            debug("\nSaved OK [$filename]\n");    
        }
    }
    private function task_gnparser($sciname)
    {   // e.g. gnparser -f pretty "Quadrella steyermarkii (Standl.) Iltis &amp; Cornejo"
        $cmd = GNPARSER_PATH.' -f pretty "'.$sciname.'"';
        if($json = shell_exec($cmd)) return $json;
        else return false;

        /*
        if($sciname = trim($sciname)) {
            $cmd = 'gnparser -f pretty "'.$sciname.'"';
            if($json = shell_exec($cmd)) { //echo "\n$json\n"; //good debug
                if($obj = json_decode($json)) { //print_r($obj); //exit("\nstop muna\n"); //good debug
                    if(@$obj->canonical) {
                        if($type == 'simple') return $obj->canonical->simple;
                        elseif($type == 'full') return $obj->canonical->full;
                        else exit("\nUndefined type. Will exit.\n");    
                    }
                }
            }    
        }
        */
    }
    /* copied template
    private function run_query($qry)
    {
        $in_file = DOC_ROOT."/temp/".$this->basename.".in";
        $WRITE = Functions::file_open($in_file, "w");
        fwrite($WRITE, $qry); fclose($WRITE);
        $destination = DOC_ROOT."temp/".$this->basename.".out.json";
        // worked in eol-archive but may need to add: /bin/cat instead of just 'cat'
        // $cmd = 'wget -O '.$destination.' --header "Authorization: JWT `cat '.DOC_ROOT.'temp/api.token`" https://eol.org/service/cypher?query="`cat '.$in_file.'`"';
        
        $cmd = WGET_PATH.' -O '.$destination.' --header "Authorization: JWT `/bin/cat '.DOC_ROOT.'temp/api.token`" https://eol.org/service/cypher?query="`/bin/cat '.$in_file.'`"';
        
        // $cmd .= ' 2>/dev/null'; //this will throw away the output
        $secs = 60*2; echo "\nSleep $secs secs..."; sleep($secs); echo " Continue...\n"; //delay 2 seconds
        $output = shell_exec($cmd); //$output here is blank since we ended command with '2>/dev/null' --> https://askubuntu.com/questions/350208/what-does-2-dev-null-mean
        // echo "\nTerminal out: [$output]\n"; //good debug
        $json = file_get_contents($destination);
        unlink($in_file);
        unlink($destination);
        return $json;
    }*/
    private function generate_path_filename($input)
    {
        $main_path = $this->main_path;
        $md5 = md5(json_encode($input));
        $cache1 = substr($md5, 0, 2);
        $cache2 = substr($md5, 2, 2);
        if(!file_exists($main_path . $cache1))           mkdir($main_path . $cache1);
        if(!file_exists($main_path . "$cache1/$cache2")) mkdir($main_path . "$cache1/$cache2");
        $filename = $main_path . "$cache1/$cache2/$md5.json";
        return $filename;
    }
}
?>