<?php

class CompareHierarchies
{
    private static $multiple_value_fields = array('synonym', 'synonym_canonical', 'common_name');
    private static $rank_priority = array(
                            'family'    => 1,
                            'order'     => .8,
                            'class'     => .6,
                            'phylum'    => .4,
                            'kingdom'   => .2);
    
    public static function compare_hierarchies($hierarchy_id, $compare_to_hierarchy_id, $match_within_same_hierarchy = false)
    {
        Functions::print_pre(SolrAPI::query($query));
    }
    
    public static function process_hierarchy($id, $compare_to_hierarchy_id = 0, $compare_synonyms = false, $search_within_itself = false)
    {
        $page_size = 10000;
        $query = "{!lucene df=hierarchy_id}$id&rows=1";
        $response = SolrAPI::query($query);
        $total_results = $response['numFound'];
        
        for($i=0 ; $i<$total_results ; $i+=$page_size)
        {
            $query = "{!lucene df=hierarchy_id}$id&rows=$page_size&start=$i";
            $result = SolrAPI::query($query);
            foreach($result->doc as $d)
            {
                $entry = self::doc_to_object($d);
                self::compare_entry($entry, $compare_to_hierarchy_id, $compare_synonyms, $search_within_itself);
            }
            //if($i/$page_size>2) exit;
        }
    }
    
    public static function compare_entry($entry, $compare_to_hierarchy_id = 0, $compare_synonyms = false, $search_within_itself = false)
    {
        if($entry->name)
        {
            $search_name = rawurlencode($entry->canonical_form);
            $query = "{!lucene}(canonical_form_string:\"". $search_name ."\"";
            if($compare_synonyms) $query .= " OR synonym_canonical:\"". $search_name ."\"";
            $query .= ")";
            if($compare_to_hierarchy_id) $query .= " AND hierarchy_id:$compare_to_hierarchy_id";
            
            $result = SolrAPI::query($query);
            foreach($result->doc as $d)
            {
                $matching_entry = self::doc_to_object($d);
                self::compare_hierarchy_entries($entry, $matching_entry, $search_within_itself);
            }
            
            // if(!$result->doc)
            // {
            //     static $no_results = 0;
            //     $no_results++;
            //     
            //     echo "NO RESULTS: $no_results<br>";
            //     Functions::print_pre($entry);
            // }
            
            static $total_searches = 0;
            $total_searches++;
            
            if($total_searches % 500 == 0) echo "TIME $total_searches :: ".Functions::time_elapsed()."<br>\n";
        }
    }
    
    public static function compare_hierarchy_entries(&$entry1, &$entry2, $search_within_itself = false)
    {
        if($entry1->id == $entry2->id) return 0;
        if(self::rank_conflict($entry1, $entry2)) return 0;
        if(!$search_within_itself && $entry1->hierarchy_id == $entry2->hierarchy_id) return 0;
        
        // viruses are a pain and will not match properly right now
        if(strtolower($entry1->kingdom) == 'virus' || strtolower($entry1->kingdom) == 'viruses') return 0;
        if(strtolower($entry2->kingdom) == 'virus' || strtolower($entry2->kingdom) == 'viruses') return 0;
        
        $name_match = self::compare_names($entry1, $entry2);
        // // synonym matching
        // if(!$name_match) $name_match = self::compare_synonyms($entry1, $entry2);
        $ancestry_match = self::compare_ancestries($entry1, $entry2);
        
        // an ancestry was empty to use name match only
        if(is_null($ancestry_match)) $total_score = $name_match;
        
        // ancestry match was at a resonable rank, weight scores
        elseif($ancestry_match > .2) $total_score = $name_match * $ancestry_match;
        
        // ancestry match was at kingdom level, succeed if either rank is kingdom but nothing else
        elseif($ancestry_match == .2 &&
            (($entry1->rank_id == Rank::insert('kingdom') && ($entry1->rank_id == $entry2->rank_id || !$entry2->rank_id)) ||
            ($entry2->rank_id == Rank::insert('kingdom') && ($entry2->rank_id == $entry1->rank_id || !$entry1->rank_id)))) $total_score = $name_match * $ancestry_match;
        
        // ancestries did not match at all therefore the match fails
        else $total_score = 0;
        
        
        
        static $count = 0;
        $count++;
        
        if($total_score)
        {
            static $number_of_matches = 0;
            $number_of_matches++;
            
            if($number_of_matches % 100 == 0)
            {
                echo "  # $count<br>
                        GOOD Match $number_of_matches<br>
                        Score $total_score<br>
                        <table border><tr>
                            <td>".Functions::print_pre($entry1, 1)."</td>
                            <td>".Functions::print_pre($entry2, 1)."</td>
                            </tr></table><hr>";
            }
        }else
        {
            static $bad_matches = 0;
            $bad_matches++;
            
            echo "  # $count<br>
                    BAD Match $bad_matches<br>
                    <table border><tr>
                        <td>".Functions::print_pre($entry1, 1)."</td>
                        <td>".Functions::print_pre($entry2, 1)."</td>
                        </tr></table><hr>";
        }
    }
    
    public static function rank_conflict(&$entry1, &$entry2)
    {
        // the ranks are not the same
        if($entry1->rank_id && $entry2->rank_id && $entry1->rank_id != $entry2->rank_id) return 1;
        return 0;
    }
    
    public static function compare_names(&$entry1, &$entry2)
    {
        // names are assigned and identical
        if($entry1->name && $entry2->name && $entry1->name == $entry2->name) return 1;
        
        // canonical_forms are assigned and identical
        if($entry1->canonical_form && $entry2->canonical_form && $entry1->canonical_form == $entry2->canonical_form) return .5;
        
        return 0;
    }
    
    public static function compare_synonyms(&$entry1, &$entry2)
    {
        // one name is in the other's synonym list
        if(in_array($entry1->name, $entry2->synonym)) return 1;
        if(in_array($entry2->name, $entry1->synonym)) return 1;
        
        // one canonical_form is in the other's synonym list
        if(in_array($entry1->canonical_form, $entry2->synonym_canonical)) return .5;
        if(in_array($entry2->canonical_form, $entry1->synonym_canonical)) return .5;
        
        return 0;
    }
    
    public static function compare_ancestries(&$entry1, &$entry2)
    {
        // one entry has none if its ancestry listed so disregard ancestry from comparison
        if(!$entry1->kingdom && !$entry1->phylum && !$entry1->class && !$entry1->order && !$entry1->family) return null;
        if(!$entry2->kingdom && !$entry2->phylum && !$entry2->class && !$entry2->order && !$entry2->family) return null;
        
        // check each rank in order of priority and return the respective weight on match
        foreach(self::$rank_priority as $rank => $weight)
        {
            if($entry1->$rank && $entry2->$rank && $entry1->$rank == $entry2->$rank && !preg_match("/^unassigned/i", $entry1->$rank)) return $weight;
        }
        
        return 0;
    }
    
    private static function doc_to_object($doc)
    {
        $attributes = array(
                'name'              => '',
                'canonical_form'    => '',
                'kingdom'           => '',
                'phylum'            => '',
                'class'             => '',
                'order'             => '',
                'family'            => '',
                'genus'             => '',
                'synonym'           => array(),
                'synonym_canonical' => array(),
                'common_name'       => array());
        foreach($doc->arr as $attr)
        {
            if(isset($attr->str)) $value = (string) $attr->str;
            else $value = (int) $attr->int;
            $name = (string) $attr['name'];
            
            if(in_array($name, self::$multiple_value_fields)) $attributes[$name][] = $value;
            else $attributes[$name] = $value;
        }
        
        return (object) $attributes;
    }
    
    
    
    
    
    
    
    
}

?>