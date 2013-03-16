<?php
namespace php_active_record;

class NamesCheck
{
    private $mysqli;
    private $mysqli_slave;
    
    public function __construct()
    {
        $this->mysqli =& $GLOBALS['db_connection'];
        if($GLOBALS['ENV_NAME'] == 'production' && environment_defined('slave')) $this->mysqli_slave = load_mysql_environment('slave');
        else $this->mysqli_slave =& $this->mysqli;
        
        require_library('RubyNameParserClient');
        $this->name_parser = new RubyNameParserClient();
    }
    
    public function start()
    {
        $start = 0;
        $max_id = 0;
        $limit = 100000;
        
        $result = $this->mysqli_slave->query("SELECT MIN(id) as min, MAX(id) as max FROM names");
        if($result && $row=$result->fetch_assoc())
        {
            $start = $row["min"];
            $max_id = $row["max"];
        }
        
        for($i=$start ; $i<$max_id ; $i+=$limit)
        {
            $this->lookup_names($i, $limit);
        }
    }
    
    function lookup_names($start, $limit, &$taxon_concept_ids = array())
    {
        debug("querying names");
        $query = "SELECT n.id, n.string FROM names n WHERE n.id ";
        if($taxon_concept_ids) $query .= "IN (". implode(",", $taxon_concept_ids) .")";
        else $query .= "BETWEEN $start AND ". ($start+$limit);
        
        static $i = 0;
        foreach($this->mysqli_slave->iterate_file($query) as $row_num => $row)
        {
            if($i % 100000 == 0) echo "   ===> $i :: ". time_elapsed() ." :: ". memory_get_usage() ."\n";
            $i++;
            $name_id = $row[0];
            $name_string = $row[1];
            if(preg_match("/[0-9]\/[0-9]/", $name_string, $arr))
            {
                if(Name::is_surrogate($name_string)) continue;
                $canonical_form = trim($this->name_parser->lookup_string($name_string));
                echo "$name_id\t$name_string\t$canonical_form\n";
            }
        }
    }
}

?>
