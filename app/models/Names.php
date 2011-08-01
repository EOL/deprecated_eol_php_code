<?php
namespace php_active_record;

class Name extends ActiveRecord
{
    public static $belongs_to = array(
            array('canonical_form')
        );
    
    public static $before_create = array(
            'create_clean_name',
            'create_canonical_form'
        );
    
    public function create_clean_name()
    {
        if(@!$this->clean_name) $this->clean_name = Functions::clean_name($this->string);
        if(@!$this->italicized)
        {
            $this->italicized = Functions::italicized_form($this->string);
            $this->italicized_verified = 0;
        }
    }
    
    public function create_canonical_form()
    {
        if(@!$this->canonical_form_id)
        {
            $string = Functions::canonical_form($this->string);
            $canonical_form = CanonicalForm::find_or_create_by_string($string);
            $this->canonical_form_id = $canonical_form->id;
            $this->canonical_verified = 0;
        }
    }
    
    static function canonical_form_by_name_id($name_id)
    {
        if(!$name_id) return false;
        $mysqli =& $GLOBALS['mysqli_connection'];
        $canonical_form = "";
        
        $result = $mysqli->query("SELECT cf.string FROM names n JOIN canonical_forms cf ON (n.canonical_form_id=cf.id) WHERE n.id=$name_id");
        if($result && $row=$result->fetch_assoc()) $canonical_form = $row["string"];
        
        return $canonical_form;
    }
    
    static function unassigned_ids()
    {
        return array(5335536);
    }
    
    static function find_or_create_by_string($string)
    {
        if($name = Name::find_by_string($string)) return $name;
        return Name::create(array('string' => $string));
    }
    
    static function find_by_string($string)
    {
        $string = trim($string);
        if(!$string) return 0;
        
        while(preg_match("/  /",$string)) $string = str_replace("  "," ",$string);
        $clean_name = Functions::clean_name($string);
        if($clean_name && $name = Name::find_by_clean_name($clean_name)) return $name;
        return 0;
    }
}

?>