<?php
namespace php_active_record;
class OpenData
{
    private $mysqli;
    private $mysqli_slave;
    private $content_archive_builder;
    
    public function __construct()
    {
        $this->mysqli =& $GLOBALS['db_connection'];
        /*
        if($GLOBALS['ENV_NAME'] == 'production' && environment_defined('slave')) $this->mysqli_slave = load_mysql_environment('slave');
        else $this->mysqli_slave =& $this->mysqli;
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => DOC_ROOT . "/temp/eol_names_archive/"));
        */
        /*
        $result = $this->mysqli_slave->query("SELECT MIN(id) as min, MAX(id) as max FROM taxon_concepts tc");
        if($result && $row=$result->fetch_assoc())
        
        $query = "SELECT he.taxon_concept_id, he.rank_id";
        foreach($this->mysqli_slave->iterate_file($query) as $row_num => $row) {}
        
        $result = $this->mysqli_slave->query("SELECT r.id, tr.label FROM ranks r JOIN translated_ranks tr ON (r.id=tr.rank_id) WHERE tr.language_id=". Language::english()->id);
        while($result && $row=$result->fetch_assoc()) {}
        */
    }
    
    function get_id_from_REQUEST_URI($uri)
    {
        $arr = explode("/", $uri);
        return array_pop($arr);
    }
    
    function get_resource_by_id($resource_id)
    {
        // echo "\n$resource_id\n";
        $qry = "SELECT r.* FROM v259_ckan.resource r WHERE r.id = '$resource_id'";
        $result = $this->mysqli->query($qry);
        if($result && $row=$result->fetch_assoc()) {
            // echo "<pre>"; print_r($row); echo "</pre>";
            $arr['id'] = $row['id'];
            $arr['url'] = $row['url'];
            $json = Functions::remove_whitespace(json_encode($row));
            echo $json;
        }
    }
}
?>
