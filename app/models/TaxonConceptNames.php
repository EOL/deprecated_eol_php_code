<?php

class TaxonConceptName extends MysqlBase
{
    function __construct($param)
    {
        $this->table_name = Functions::class_name(__FILE__);
        parent::initialize($param);
        if(@!$this->taxon_concept_id) return;
    }
    
    public static function all()
    {
        $mysqli =& $GLOBALS['mysqli_connection'];
        $all = array();
        $result = $mysqli->query("SELECT * FROM taxon_concept_names");
        while($result && $row=$result->fetch_assoc())
        {
            $all[] = new TaxonConceptName($row);
        }
        return $all;
    }
}

?>