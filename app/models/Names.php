<?php
namespace php_active_record;

class Name extends ActiveRecord
{
    public static $belongs_to = array(
            array('canonical_form'),
            array('ranked_canonical_form', 'class_name' => 'canonical_form', 'foreign_key' => 'ranked_canonical_form_id')
        );
    
    public static $before_create = array(
            'create_clean_name',
            'create_canonical_form'
        );
    
    public static function is_surrogate($string)
    {
        static $red_flag_words = array('incertae', 'sedis', 'incertaesedis', 'culture', 'clone', 'isolate',
                                'phage', 'sp', 'cf', 'uncultured', 'DNA', 'unclassified', 'sect',
                                'ß', 'str', 'biovar', 'type', 'strain', 'serotype', 'hybrid',
                                'cultivar', 'x', '×', 'pop', 'group', 'environmental', 'sample',
                                'endosymbiont', 'species', 'complex',
                                'unassigned', 'n', 'gen', 'auct', 'non', 'aff',
                                'mixed', 'library', 'genomic', 'unidentified', 'parasite', 'synthetic',
                                'phytoplasma', 'bacterium', 'sect', 'section', 'assigned', 'unclassified');
        if(preg_match("/(^|[^\w])(". implode("|", $red_flag_words) .")([^\w]|$)/i", $string)) return true;
        if(preg_match("/ [abcd] /i", $string)) return true;
        if(preg_match("/virus([^\w]|$)/i", $string)) return true;
        if(preg_match("/viruses([^\w]|$)/i", $string)) return true;
        if(preg_match("/(_|'|\")/i", $string)) return true;
        
        // the rest of the rules involve numbers, so we can return if there isn't a number
        if(!preg_match("/[0-9]/", $string)) return false;
        if(preg_match("/[0-9][a-z]/i", $string)) return true;           // 9c
        if(preg_match("/[a-z][0-9]/i", $string)) return true;           // c9
        if(preg_match("/[a-z]-[0-9]/i", $string)) return true;          // c-9
        if(preg_match("/ [0-9]{1,3}$/", $string)) return true;          // 197
        if(preg_match("/(^|[^\w])[0-9]{1,3}-[0-9]{1,3}(^|[^\w])/", $string)) return true;
        if(preg_match("/[0-9]{5,}/", $string)) return true;             // 19777
        if(preg_match("/[03456789][0-9]{3}/", $string)) return true;    // years should start with 1 or 2
        if(preg_match("/1[02345][0-9]{2}/", $string)) return true;      // 1600 - 1900
        if(preg_match("/2[1-9][0-9]{2}/", $string)) return true;        // 1600 - 1900
        return false;
    }
    
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
            if($canonical_form = CanonicalForm::find_or_create_by_string($string))
            {
                $this->canonical_form_id = $canonical_form->id;
                $this->canonical_verified = 0;
            }
        }
        if(@!$this->ranked_canonical_form_id)
        {
            if($string = Functions::ranked_canonical_form($this->string))
            {
                $ranked_canonical_form = CanonicalForm::find_or_create_by_string($string);
                $this->ranked_canonical_form_id = $ranked_canonical_form->id;
            }
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
