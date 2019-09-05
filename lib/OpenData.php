<?php
namespace php_active_record;
class OpenData
{
    private $mysqli;
    private $mysqli_slave;
    private $content_archive_builder;
    
    public function __construct()
    {   /*
        # config.ini 
        # PHP error reporting. supported values are given below. 
        # 0 - Turn off all error reporting 
        # 1 - Running errors 
        # 2 - Running errors + notices 
        # 3 - All errors except notices and warnings 
        # 4 - All errors except notices 
        # 5 - All errors
        */
        if($GLOBALS['ENV_DEBUG'] == false) error_reporting(0);
        $this->mysqli =& $GLOBALS['db_connection'];
        /*
        if($GLOBALS['ENV_NAME'] == 'production' && environment_defined('slave')) $this->mysqli_slave = load_mysql_environment('slave');
        else $this->mysqli_slave =& $this->mysqli;
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => DOC_ROOT . "/temp/eol_names_archive/"));
        */
        /* diff means to access mysql
        $result = $this->mysqli_slave->query("SELECT MIN(id) as min, MAX(id) as max FROM taxon_concepts tc");
        if($result && $row=$result->fetch_assoc())
        
        $query = "SELECT he.taxon_concept_id, he.rank_id";
        foreach($this->mysqli_slave->iterate_file($query) as $row_num => $row) {}
        
        $result = $this->mysqli_slave->query("SELECT r.id, tr.label FROM ranks r JOIN translated_ranks tr ON (r.id=tr.rank_id) WHERE tr.language_id=". Language::english()->id);
        while($result && $row=$result->fetch_assoc()) {}
        */
    }
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
    function get_id_from_REQUEST_URI($uri)
    {
        $arr = explode("/", $uri);
        return array_pop($arr);
    }
    function get_organization_by_id($org_id)
    {
        $sql = "SELECT g.* FROM v259_ckan.group_list g WHERE g.id = '$org_id'";
        self::run_query($sql, 'print json');
    }
    function get_dataset_by_id($dataset_id)
    {
        $sql = "SELECT p.* from v259_ckan.package p WHERE p.id = '$dataset_id' AND p.type = 'dataset'";
        if($row = self::run_query($sql)) {
            self::print_json($row);
        }
        else {
            $sql = "SELECT p.* from v259_ckan.package p WHERE p.name = '$dataset_id' AND p.type = 'dataset'";
            self::run_query($sql, 'print json');
        }
    }
    function get_resource_by_id($resource_id)
    {
        $sql = "SELECT r.* FROM v259_ckan.resource r WHERE r.id = '$resource_id'";
        self::run_query($sql, 'print json');
    }
    private function run_query($sql, $next = '')
    {
        $result = $this->mysqli->query($sql);
        if($result && $row=$result->fetch_assoc()) {
            if($GLOBALS['ENV_DEBUG']) {
                echo "<pre>"; print_r($row); echo "</pre>";
            }
            if($next == 'print json') {
                self::print_json($row);
            }
            else return $row;
        }
    }
    private function print_json($row)
    {
        $json = Functions::remove_whitespace(json_encode($row));
        echo $json;
    }
}
?>
