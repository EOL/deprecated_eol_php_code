<?php

class Name extends MysqlBase
{
    function __construct($param)
    {
        $this->table_name = Functions::class_name(__FILE__);
        parent::initialize($param);
        if(@!$this->id) return;
        
        $this->string = ucfirst($this->string);
    }
    
    public static function all()
    {
        $mysqli =& $GLOBALS['mysqli_connection'];
        $all = array();
        $result = $mysqli->query("SELECT SQL_NO_CACHE * FROM names");
        while($result && $row=$result->fetch_assoc())
        {
            $all[] = new Name($row);
        }
        return $all;
    }
    
    public function make_scientific()
    {
        $this->add_language(Language::insert("Scientific Name"), 0, 0);
        $canonical_form = $this->canonical_form();
        
        if($this->canonical_form()->string != $this->string)
        {
            $new_name = new Name(Name::insert($this->canonical_form()->string));
            if($new_name->id != $this->id) $new_name->make_scientific();
            unset($new_name);
        }
    }
    
    static function make_scientific_by_name_id($name_id)
    {
        self::add_language_by_name_id($name_id, Language::insert("Scientific Name"), 0, 0);
    }
    
    public function canonical_form()
    {
        if(@$this->canonical_form) return $this->canonical_form;
        
        $this->canonical_form = new CanonicalForm($this->canonical_form_id);
        return $this->canonical_form;
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
    
    public function add_language($language_id, $parent_id, $preferred)
    {
        $result = $this->mysqli->query("SELECT * FROM name_languages WHERE name_id=$this->id AND parent_name_id=$parent_id AND language_id=$language_id");
        if($result && $row=$result->fetch_assoc()) return false;
        
        if($language_id==Language::insert("Common name") && !in_array($language_id, Language::unknown())) 
        {
            $result = $this->mysqli->query("SELECT * FROM name_languages WHERE name_id=$this->id AND parent_name_id=$parent_id AND language_id NOT IN (".Language::insert("Scientific Name").", ".Language::insert("Operational Taxonomic Unit").")");
            if($result && $row=$result->fetch_assoc()) return 0;
        }
        
        $this->mysqli->insert("INSERT INTO name_languages VALUES ($this->id, $language_id, $parent_id, $preferred)");
    }
    
    static function add_language_by_name_id($name_id, $language_id, $parent_id, $preferred)
    {
        if(!$name_id) return false;
        $mysqli =& $GLOBALS['mysqli_connection'];
        
        $result = $mysqli->query("SELECT * FROM name_languages WHERE name_id=$name_id AND parent_name_id=$parent_id AND language_id=$language_id");
        if($result && $row=$result->fetch_assoc()) return false;
        
        if($language_id==Language::insert("Common name") && !in_array($language_id, Language::unknown())) 
        {
            $result = $mysqli->query("SELECT * FROM name_languages WHERE name_id=$name_id AND parent_name_id=$parent_id AND language_id NOT IN (".Language::insert("Scientific Name").", ".Language::insert("Operational Taxonomic Unit").")");
            if($result && $row=$result->fetch_assoc()) return 0;
        }
        
        $mysqli->insert("INSERT INTO name_languages VALUES ($name_id, $language_id, $parent_id, $preferred)");
    }
    
    static function unassigned_ids()
    {
        return array(5335536);
    }
    
    static function insert($string, $given_canonical_form = "")
    {
        $string = trim($string);
        if(!$string) return 0;
        
        if($result = self::find($string)) return $result;
        $mysqli =& $GLOBALS['mysqli_connection'];
        
        while(preg_match("/  /",$string)) $string = trim(str_replace("  "," ",$string));
        
        if($given_canonical_form) $canonical_form = $given_canonical_form;
        else $canonical_form = Functions::canonical_form($string);
        $canonical_form_id = CanonicalForm::insert($canonical_form);
        $italicized_form = Functions::italicized_form($string);
        $clean_name = Functions::clean_name($string);
        
        $id = parent::insert_fields_into(array('string' => $string, 'clean_name' => $clean_name, 'italicized' => $italicized_form, 'italicized_verified' => 0, 'canonical_form_id' => $canonical_form_id, 'canonical_verified' => 0), Functions::class_name(__FILE__));
        
        if($id)
        {
            $name = new Name($id);
            unset($name);
        }
        
        return $id;
    }
    
    static function find($string)
    {
        $string = trim($string);
        if(!$string) return 0;
        
        $mysqli =& $GLOBALS['mysqli_connection'];
        
        $name_id = 0;
        
        while(preg_match("/  /",$string)) $string = str_replace("  "," ",$string);
        
        $clean_name = Functions::clean_name($string);
        
        if($clean_name && $id = Name::find_by_clean_name($clean_name)) return $id;
        
        return 0;
    }
    
    static function find_by_id($id)
    {
        return parent::find_by_id_base("string", $id, Functions::class_name(__FILE__));
    }
    
    static function find_by_clean_name($clean_name)
    {
        return parent::find_by("clean_name", $clean_name, Functions::class_name(__FILE__));
    }
    
}

?>