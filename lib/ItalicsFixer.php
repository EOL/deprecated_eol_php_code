<?php
namespace php_active_record;

class ItalicsFixer
{
    private $mysqli;
    private $mysqli_slave;
    
    public function __construct()
    {
        $this->mysqli =& $GLOBALS['db_connection'];
        if($GLOBALS['ENV_NAME'] == 'production' && environment_defined('slave')) $this->mysqli_slave = load_mysql_environment('slave');
        else $this->mysqli_slave =& $this->mysqli;
    }
    
    public function begin()
    {
        $start = 0;
        $max_id = 0;
        $limit = 50000;
        
        $result = $this->mysqli_slave->query("SELECT MIN(id) as min, MAX(id) as max FROM names");
        if($result && $row=$result->fetch_assoc())
        {
            $start = $row["min"];
            $max_id = $row["max"];
        }
        
        for($i=$start ; $i<$max_id ; $i+=$limit)
        {
            $this->lookup_italics($i, $limit);
        }
    }
    
    function lookup_italics($start, $limit, &$taxon_concept_ids = array())
    {
        $this->mysqli->begin_transaction();
        echo "looking up $start : $limit\n";
        $query = "SELECT id, italicized FROM names WHERE italicized LIKE '%<i>%<i>%' AND id ";
        if($taxon_concept_ids) $query .= "IN (". implode(",", $taxon_concept_ids) .")";
        else $query .= "BETWEEN $start AND ". ($start+$limit);
        
        foreach($this->mysqli_slave->iterate_file($query) as $row_num => $row)
        {
            $name_id = $row[0];
            $italicized = $row[1];
            $modified_italicized = Functions::fix_italics($italicized);
            $count_open = substr_count($modified_italicized, '<i>');
            $count_closed = substr_count($modified_italicized, '</i>');
            if($modified_italicized != $italicized && $count_open == $count_closed)
            {
                // echo "$name_id\n$italicized\n$modified_italicized\n\n";
                echo "UPDATE names SET italicized='".$this->mysqli->escape($modified_italicized)."' WHERE id=$name_id\n";
                // $this->mysqli->query("UPDATE names SET italicized='".$this->mysqli->escape($modified_italicized)."' WHERE id=$name_id");
            }
        }
        $this->mysqli->end_transaction();
    }
}

?>