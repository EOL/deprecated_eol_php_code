<?php
namespace php_active_record;
class OpenData_utility
{
    private $mysqli;
    private $content_archive_builder;
    public function __construct()
    {   
        if($GLOBALS['ENV_DEBUG'] == false) error_reporting(0);
        $this->mysqli =& $GLOBALS['db_connection'];
    }
    function copy_uploaded_files_to_a_telling_name()
    {
        $i = 0; $debug = array();
        foreach(new FileIterator(CONTENT_RESOURCE_LOCAL_PATH."/CKAN_file_system.txt") as $line_number => $line) {
            $line = explode("\t", $line); $i++; if(($i % 200000) == 0) echo "\n".number_format($i);
            if($i == 1) $fields = $line;
            else {
                if(!$line[0]) break;
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k]; $k++;
                }
                // print_r($rec); //exit;
                /*Array(
                    [resource_id] => 0045454e-1ae4-4ea3-a087-59f8250e53f0
                    [url] => https://opendata.eol.org/dataset/a52ef387-d650-4f26-bcd5-fca5114462ef/resource/0045454e-1ae4-4ea3-a087-59f8250e53f0/download/wikipedia-fr.tar.gz
                    [url_type] => upload
                    [file_id] => 4e-1ae4-4ea3-a087-59f8250e53f0
                    [file_path] => /extra/ckan_resources/004/545/
                )*/
                $basename = pathinfo($rec['url'], PATHINFO_BASENAME);
                // echo "\n$rec[url]\n[$basename]\n";
                $source = $rec['file_path'].$rec['file_id'];
                $destination = $rec['file_path'].$basename;
                if(copy($source, $destination)) {
                    echo "\nCopied OK";
                    if(chmod($destination, 0755)) echo " - mode changed OK";
                    else {
                        echo " - ERROR: mode not changed";
                        $debug['error mode chanage'][$rec['resource_id']] = '';
                    }
                }
                else {
                    echo "\nERROR: not copied";
                    $debug['error copy'][$rec['resource_id']] = '';
                }
                break;
            }
            sleep(1);
        }
        print_r($debug); exit("\n-end copy_uploaded_files_to_a_telling_name-\n");
    }
    /* Ran already. Run once only. Can be commented now.
    function connect_old_file_system_with_new()
    {
        $WRITE = Functions::file_open(CONTENT_RESOURCE_LOCAL_PATH."/CKAN_file_system.txt", 'w');
        $headers = array('resource_id', 'url', 'url_type', 'file_id', 'file_path');
        fwrite($WRITE, implode("\t", $headers)."\n");
        $i = 0;
        foreach(new FileIterator(CONTENT_RESOURCE_LOCAL_PATH."/CKAN_uploaded_files.txt") as $line_number => $line) {
            $line = explode("\t", $line); $i++;
            if($i == 1) $fields = $line;
            else {
                if(!$line[0]) break;
                $file_id = $line[0];
                $file_path = $line[1];
                $result = $this->mysqli->query("SELECT t.* FROM v259_ckan.resource t WHERE t.id LIKE '%".$file_id."'");
                if($result && $row=$result->fetch_assoc()) {
                    @$debug['found in id']++;
                    @$debug['url_type'][$row['url_type']]++;
                    $arr = array($row['id'], $row['url'], $row['url_type'], $file_id, $file_path);
                    self::write_2text($arr, $WRITE);
                }
                else {
                    $result = $this->mysqli->query("SELECT t.* FROM v259_ckan.resource t WHERE t.url LIKE '%".$file_id."%'");
                    if($result && $row=$result->fetch_assoc()) {
                        @$debug['found in url']++;
                        @$debug['url_type'][$row['url_type']]++;
                        $arr = array($row['id'], $row['url'], $row['url_type'], $file_id, $file_path);
                        self::write_2text($arr, $WRITE);
                    }
                    else {
                        $result = $this->mysqli->query("SELECT t.* FROM v259_ckan.resource t WHERE t.revision_id LIKE '%".$file_id."%'");
                        if($result && $row=$result->fetch_assoc()) {
                            @$debug['found in revision_id']++; //nothing was found here...
                            @$debug['url_type'][$row['url_type']]++;
                            $arr = array($row['id'], $row['url'], $row['url_type'], $file_id, $file_path);
                            self::write_2text($arr, $WRITE);
                        }
                        else {
                            print("\nInvestigate [$file_id] [$file_path]");
                            @$debug['not found']++;
                        }
                    }
                }
            }
        }
        print_r($debug);
        fclose($WRITE);
    }
    */
    private function write_2text($arr, $WRITE)
    {
        fwrite($WRITE, implode("\t", $arr)."\n");
    }
    /* Ran already. Run once only. Can be commented now.
    function get_all_ckan_resource_files($path)
    {   //good resource: https://www.sitepoint.com/list-files-and-directories-with-php/
        $WRITE = Functions::file_open(CONTENT_RESOURCE_LOCAL_PATH."/CKAN_uploaded_files.txt", 'w');
        $outer_dirs = scandir($path.".");
        $outer_dirs = array_diff($outer_dirs, array('.', '..')); // print_r($outer_dirs);
        foreach($outer_dirs as $odir) {
            $inner_dirs = scandir($path.$odir."/.");
            $inner_dirs = array_diff($inner_dirs, array('.', '..'));
            foreach($inner_dirs as $idir) {
                $path2save = $path.$odir."/".$idir."/";
                $files = scandir($path2save.".");
                $files = array_diff($files, array('.', '..'));
                foreach($files as $file) {
                    $arr = array($file, $path2save);
                    fwrite($WRITE, implode("\t", $arr)."\n");
                }
            }
        }
        fclose($WRITE);
    }
    */


}
?>
