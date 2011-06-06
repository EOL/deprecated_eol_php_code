<?php
namespace php_active_record;

$GLOBALS["compare_taxon_weights"]["name"] = 4;
$GLOBALS["compare_taxon_weights"]["ancestry"] = 6;
$GLOBALS["compare_taxon_weights"]["children"] = 3;
//$GLOBALS["compare_taxon_weights"]["siblings"] = 4;
$GLOBALS["compare_taxon_weights"]["synonyms"] = 2;

class NamesFunctions
{
    public static function compare_taxon_concepts($concept1, $hierarchy_entries1, $all_names1, $concept2, $hierarchy_entries2, $all_names2, $complete_hierarchy = true)
    {
        $is_unassigned1 = false;
        $is_unassigned2 = false;
        //if(!array_diff($concept1->name_ids(), Name::unassigned_ids())) $is_unassigned1 = true;
        //if(!array_diff($concept2->name_ids(), Name::unassigned_ids())) $is_unassigned2 = true;
        
        if($is_unassigned1 && !$is_unassigned2) return 0;
        if($is_unassigned2 && !$is_unassigned1) return 0;
        
        $final_score = 0;
        if($is_unassigned1 && $is_unassigned2)
        {
            $score = self::compare_taxon_concept_ancestries_unassigned($concept1, $concept2);
            if($score == 1) $final_score = 1;
        }else
        {
            //debug("ancestry - ".Functions::time_elapsed());
            $scores["ancestry"] = self::compare_taxon_concept_ancestries($hierarchy_entries1, $hierarchy_entries2, $complete_hierarchy);
            if($scores["ancestry"]==-2) return 0;
            
            //debug("name - ".Functions::time_elapsed());
            list($scores["name"], $scores["synonyms"]) = self::compare_taxon_concept_names($concept1->id, $all_names1, $concept2->id, $all_names2);
            
            //debug("children - ".Functions::time_elapsed());
            $scores["children"] = self::compare_taxon_concept_children($all_names1["children"], $all_names2["children"]);
            
            //debug("siblings - ".Functions::time_elapsed());
            //$scores["siblings"] = self::compare_taxon_concept_siblings($concept1, $concept2);
            
            $final_score = self::evaluate_score($scores);
        }
        
        if($final_score && DEBUG)
        {
            //self::show_concepts($concept1, $concept2, $scores, $final_score);
        }
        
        if(@$scores) unset($scores);
        
        return $final_score;
    }
    
    public static function evaluate_score($scores)
    {
        $weights["name"] = $GLOBALS["compare_taxon_weights"]["name"];
        $weights["ancestry"] = $GLOBALS["compare_taxon_weights"]["ancestry"];
        $weights["children"] = $GLOBALS["compare_taxon_weights"]["children"];
        //$weights["siblings"] = $GLOBALS["compare_taxon_weights"]["siblings"];
        $weights["synonyms"] = $GLOBALS["compare_taxon_weights"]["synonyms"];
        
        if(@!isset($scores["name"]) || $scores["name"] < 0) $weights["name"] = 0;
        if(@!isset($scores["ancestry"]) || $scores["ancestry"] < 0) $weights["ancestry"] = 0;
        if(@!isset($scores["children"]) || $scores["children"] < 0) $weights["children"] = 0;
        //if(@!isset($scores["siblings"]) || $scores["siblings"] < 0) $weights["siblings"] = 0;
        if(@!isset($scores["synonyms"]) || $scores["synonyms"] < 0) $weights["synonyms"] = 0;
        
        $final_score = 0;
        foreach($scores as $k => $v)
        {
            $final_score += $v * $weights[$k];
        }
        $final_score = $final_score / array_sum($weights);
        
        return $final_score;
    }
    
    public static function compare_names($name1, $name2)
    {
        if($name1->id == $name2->id && $name1->id != 0) return 1;
        if($name1->canonical_form_id == $name2->canonical_form_id && $name2->canonical_form_id != 0) 
        {
            if($name1->string == $name1->canonical_form()->string || $name2->string == $name2->canonical_form()->string) return 1;
            return 0.65;
        }
        
        return 0;
    }
    
    public static function compare_taxon_concept_names($taxon_concept_id1, $all_names1, $taxon_concept_id2, $all_names2)
    {
        //if(@isset($GLOBALS['function_returns']['compare_taxon_concept_names'][$taxon_concept_id1."|".$taxon_concept_id2])) return $GLOBALS['function_returns']['compare_taxon_concept_names'][$taxon_concept_id1."|".$taxon_concept_id2];
        
        list($names1, $synonyms1) = @array($all_names1["names"], $all_names1["synonyms"]);
        list($names2, $synonyms2) = @array($all_names2["names"], $all_names2["synonyms"]);
        
        return self::compare_taxon_concept_names_sub($taxon_concept_id1, $names1, $synonyms1, $taxon_concept_id2, $names2, $synonyms2);
    }
    
    public static function compare_taxon_concept_names_sub($id1, $names1, $synonyms1, $id2, $names2, $synonyms2)
    {
        //if(@isset($GLOBALS['function_returns']['compare_taxon_concept_names_sub'][$id1."|".$id2])) return $GLOBALS['function_returns']['compare_taxon_concept_names_sub'][$id1."|".$id2];
        
        if(!$names1) $names1 = array();
        if(!$synonyms1) $synonyms1 = array();
        if(!$names2) $names2 = array();
        if(!$synonyms2) $synonyms2 = array();
        
        $max_name_score = 0;
        $max_syn_score = 0;
        
        $all_names1 = array_merge($names1, $synonyms1);
        $all_names2 = array_merge($names2, $synonyms2);
        
        if(!$all_names1 || !$all_names2) return array(-1,-1);
        
        if(count($all_names1) > count($all_names2))
        {
            list($all_names1, $all_names2) = array($all_names2, $all_names1);
            list($names1, $names2) = array($names2, $names1);
            list($synonyms1, $synonyms2) = array($synonyms2, $synonyms1);
        }
        
        $syn_matches = 0;
        
        foreach($all_names1 as $name1)
        {
            $syn_match = 0;
            foreach($all_names2 as $name2)
            {
                $score = self::compare_names($name1, $name2);
                
                //debug("comparing $name1->string to $name2->string");
                
                if($score > $max_name_score && (in_array($name1, $names1) || in_array($name2, $names2)))
                {
                    $max_name_score = $score;
                }
                
                if($score > $syn_match && (in_array($name1, $synonyms1) || in_array($name2, $synonyms2)))
                {
                    $syn_match = $score;
                }
                //echo "$score<br>";
                if($score == 1) break;
            }
            $syn_matches += $syn_match;
        }
        
        //echo "$syn_matches<br>";
        //echo count($all_names1)."<br>";
        //echo count($all_names2)."<br>";
        $syn_score = $syn_matches / min(count($all_names1), count($all_names2));
        
        if(!$names1 || !$names2) $max_name_score = -1;
        if(!$synonyms1 || !$synonyms2) $syn_score = -1;
        
        unset($all_names1);
        unset($all_names2);
        
        //$GLOBALS['function_returns']['compare_taxon_concept_names_sub'][$id1."|".$id2] = array($max_name_score, $syn_score);
        //$GLOBALS['function_returns']['compare_taxon_concept_names_sub'][$id2."|".$id1] = array($max_name_score, $syn_score);
        
        return array($max_name_score, $syn_score);
    }
    
    public static function compare_taxon_concept_children($children1, $children2)
    {
        $max_score = 0;
        if(!$children1 || !$children2) return -1;
        
        if(count($children1) > count($children2)) list($children1, $children2) = array($children2, $children1);
        
        $matches = 0;
        foreach($children1 as $id1 => $all_names1)
        {
            $match = 0;
            list($names1, $synonyms1) = @array($all_names1["names"], $all_names1["synonyms"]);
            
            foreach($children2 as $id2 => $all_names2)
            {
                if($id1 == $id2)
                {
                    $match = 1;
                    break;
                }
                
                list($names2, $synonyms2) = @array($all_names2["names"], $all_names2["synonyms"]);
                
                $scores = self::compare_taxon_concept_names_sub($id1, $names1, $synonyms1, $id2, $names2, $synonyms2);
                $score = self::evaluate_score(array("name" => $scores[0], "synonyms" => $scores[1]));
                if($score > $match) $match = $score;
                
                unset($names2);
                unset($synonyms2);
                if($score == 1) break;
            }
            
            unset($names1);
            unset($synonyms1);
            $matches += $match;
        }
        
        $score = $matches / min(count($children1), count($children2));
        
        return $score;
    }
    
    public static function compare_taxon_concept_siblings($concept1, $concept2)
    {
        $max_score = 0;
        
        $children1 = $concept1->siblings();
        $children2 = $concept2->siblings();
        
        if(!$children1 || !$children2) return -1;
        
        $matches = 0;
        
        foreach($children1 as $child1)
        {
            $match = 0;
            foreach($children2 as $child2)
            {
                $score = self::compare_taxon_concept_names($child1, $child2);
                if($score > $match) $match = $score;
            }
            $matches += $match;
        }
        
        $score = $matches / min(count($children1), count($children2));
        
        unset($children1);
        unset($children2);
        
        return $score;
    }
    
    function compare_taxon_concept_ancestries($hierarchy_entries1, $hierarchy_entries2, $complete_hierarchy)
    {
        $max_score = 0;
        $min_score = 0;
        
        foreach($hierarchy_entries1 as $entry1)
        {
            foreach($hierarchy_entries2 as $entry2)
            {
                $score = self::compare_ancestry($entry1, $entry2, $complete_hierarchy);
                if($score == -2) return -2;
                if($score > $max_score) $max_score = $score;
                if($score < $min_score) $min_score = $score;
            }
        }
        
        if($max_score) return $max_score;
        if($min_score == -1) return $min_score;
        
        return 0;
    }

    function compare_taxon_concept_ancestries_unassigned($concept1, $concept2)
    {
        $hierarchy_entries1 = $concept1->hierarchy_entries();
        $hierarchy_entries2 = $concept2->hierarchy_entries();
        
        $max_score = 0;
        $min_score = 0;
        
        foreach($hierarchy_entries1 as $entry1)
        {
            foreach($hierarchy_entries2 as $entry2)
            {
                if($entry1->ancestry == $entry2->ancestry) return 1;
            }
        }
        
        unset($hierarchy_entries1);
        unset($hierarchy_entries2);
        
        return 0;
    }
    
    public static function is_in_ancestry_of($entry1, $entry2)
    {
        $canonical_form_id = $entry1->canonical_form_id;
        
        $ancestry = $entry2->ancestry;
        $nodes = explode("|", $ancestry);
        foreach($nodes as $id)
        {
            if($id == $entry1->name_id) return true;
            //$name = new Name($id);
            //if($name->canonical_form_id == $canonical_form_id) return true;
        }
        
        return false;
    }
    
    function compare_ancestry($entry1, $entry2, $complete_hierarchy)
    {
        //same hierarchy, complete_hierarchy says there are no duplicates in there - no match
        if($entry1->hierarchy_id == $entry2->hierarchy_id && $complete_hierarchy) return -2;
        
        //same hierarchy, same ancestry - should be siblings - no match
        if($entry1->hierarchy_id == $entry2->hierarchy_id && $entry1->ancestry == $entry2->ancestry && $complete_hierarchy) return -2;
        
        //same ancestry, different hierarchies - definite match
        if($entry1->hierarchy_id == $entry2->hierarchy_id && $entry1->ancestry == $entry2->ancestry) return 1;
        
        //one has no ancestry - remove ancestry from scoring
        if(!$entry1->ancestry || !$entry2->ancestry) return -1;
        
        if($complete_hierarchy && self::is_in_ancestry_of($entry1, $entry2)) return -2;
        if($complete_hierarchy && self::is_in_ancestry_of($entry2, $entry1)) return -2;
        
        //echo "$complete_hierarchy<br>$entry1->ancestry<br>$entry2->ancestry<br><br>";
        
        $array1 = explode("|",$entry1->ancestry);
        $array2 = explode("|",$entry2->ancestry);
        
        if($complete_hierarchy && abs(count($array1) - count($array2)) > 2) return -2;
        
        $first = array_filter($array1, "unassigned_filter");
        $second = array_filter($array2, "unassigned_filter");
        
        if(count($first) > count($second))
        {
            list($first, $second) = array($second, $first);
        }
        
        $count1 = count($first);
        $count2 = count($second);
        
        $array1 = array();
        $array2 = array();
        
        for($i=0 ; $i<$count1 ; $i++) $array1[$i] = 0;
        for($j=0 ; $j<$count2 ; $j++) $array2[$j] = 0;
        
        $matches = 0;
        
        $next_index = 0;
        foreach($first as $k1 => $v1)
        {
            $match = false;
            
            foreach($second as $k2 => $v2)
            {
                if($k2<$next_index) continue;
                
                if($score = NamesFunctions::ancestry_nodes_equivalent($v1,$v2))
                {
                    $next_index = $k2+1;
                    $match = $score;
                    break;
                }
            }
            
            if($match) $matches += $match;
        }
        
        unset($array1);
        unset($array2);
        
        $score = $matches/min(count($first),count($second));
        
        unset($first);
        unset($second);
        
        return $score;
    }
    
    public static function ancestry_nodes_equivalent($v1,$v2)
    {
        //if(@isset($GLOBALS['function_returns']['ancestry_nodes_equivalent'][$v1."|".$v2])) return $GLOBALS['function_returns']['ancestry_nodes_equivalent'][$v1."|".$v2];
        
        $score = 0;
        
        if($v1==5335536 && $v2==5335536) $score = 0;
        if($v1 && $v1==$v2) $score = 1;
        $score = self::compare_names(new Name($v1), new Name($v2));
        
        //$GLOBALS['function_returns']['ancestry_nodes_equivalent'][$v1."|".$v2] = $score;
        //$GLOBALS['function_returns']['ancestry_nodes_equivalent'][$v2."|".$v1] = $score;
        
        return $score;
    }
    
    public static function show_concepts($concept1, $concept2, $scores, $final_score)
    {
        echo "Score: $final_score<br>";
        
        echo "<pre>";
        @print_r($scores);
        echo "</pre>";
        
        //echo "THIS IS A RESULT: ".$row["id"].": $key - $val<br>";
        
        echo $concept1->id." - ".$concept1->name()->string."<br>\n";
        echo $concept2->id." - ".$concept2->name()->string."<br>\n<br>";
        
        // echo "<table border=1 width=100%><tr><td valign=top width=50%>";
        // $array = $concept1->hierarchy_entries();
        // foreach($array as $hierarchy_entry)
        // {
        //    echo $hierarchy_entry."<br>";
        // }
        // echo "</td><td valign=top>";
        // unset($array);
        // 
        // $array = $concept2->hierarchy_entries();
        // foreach($array as $hierarchy_entry)
        // {
        //    echo $hierarchy_entry."<br>";
        // }
        // unset($array);
        // 
        // ///////////////////////////////////////            
        // 
        // // echo "</tr><tr><td valign=top>";
        // // $array = $concept1->mock_names();
        // // foreach($array as $name)
        // // {
        // //    echo $name->string."<br>";
        // // }
        // // echo "</td><td valign=top>";
        // // unset($array);
        // // 
        // // $array = $concept2->mock_names();
        // // foreach($array as $name)
        // // {
        // //    echo $name->string."<br>";
        // // }
        // // echo "</tr><tr><td valign=top>";
        // // unset($array);
        // 
        // ///////////////////////////////////////            
        // 
        // // echo "</tr><tr><td valign=top>";
        // // $array = $concept1->siblings();
        // // foreach($array as $child)
        // // {
        // //    echo $child->name()->string."<br>";
        // // }
        // // echo "</td><td valign=top>";
        // // $array = $concept2->siblings();
        // // foreach($array as $child)
        // // {
        // //    echo $child->name()->string."<br>";
        // // }
        // // echo "</tr><tr><td valign=top>";
        // 
        // ///////////////////////////////////////            
        // 
        // echo "</tr><tr><td valign=top>";
        // $array = $concept1->children();
        // foreach($array as $child)
        // {
        //    echo $child->name()->string."<br>";
        // }
        // echo "</td><td valign=top>";
        // unset($array);
        // 
        // $array = $concept2->children();
        // foreach($array as $child)
        // {
        //    echo $child->name()->string."<br>";
        // }
        // echo "</tr><tr><td valign=top>";
        // unset($array);
        // 
        // ///////////////////////////////////////
        // 
        // // $array = $concept1->synonym_mock_names();
        // // foreach($array as $name)
        // // {
        // //    echo $name->string."<br>";
        // // }
        // // echo "</td><td valign=top>";
        // // unset($array);
        // // 
        // // $array = $concept2->synonym_mock_names();
        // // foreach($array as $name)
        // // {
        // //    echo $name->string."<br>";
        // // }
        // // echo "</td></tr></table>";
        // // unset($array);


        echo $final_score."<hr>\n\n\n";

        flush();
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    function compare_ancestry_old($entry1,$entry2)
    {
        if($entry1->ancestry == $entry2->ancestry)
        {
            if($entry1->hierarchy_id == $entry2->hierarchy_id) return 0;
            return 1;
        }
        
        if(!$entry1->ancestry || !$entry2->ancestry) return -1;
        
        $first = explode("|",$entry1->ancestry);
        $second = explode("|",$entry2->ancestry);
        
        if(count($first) > count($second))
        {
            list($first, $second) = array($second, $first);
        }
        
        $count1 = count($first);
        $count2 = count($second);
        
        $array1 = array();
        $array2 = array();
        
        for($i=0 ; $i<$count1 ; $i++) $array1[$i] = 0;
        for($j=0 ; $j<$count2 ; $j++) $array2[$j] = 0;
        
        $next_index = 0;
        
        for($i=0 ; $i<$count1 ; $i++)
        {
            $match = false;
            $wildcard1 = false;
            $wildcard2 = false;
            $v1 = $first[$i];
            
            if($v1==5335536)
            {
                $array1[$i] = 1;
                $wildcard1 = true;
            }
            
            for($j=$next_index ; $j<$count2 ; $j++)
            {
                $v2 = $second[$j];
                
                //echo "$v1 - $v2 - ".NamesFunctions::ancestry_nodes_equivalent($v1,$v2)."<br>";
                
                if($v2==5335536)
                {
                    $array2[$j] = 1;
                    $wildcard2 = true;
                }
                
                if(NamesFunctions::ancestry_nodes_equivalent($v1,$v2))
                {
                    $array1[$i] = 1;
                    $array2[$j] = 1;
                    $next_index = $j+1;
                    $match = true;
                    break;
                }
            }
            
            if(!$match && $wildcard1)
            {
                $array2[$next_index] = 1;
            }
            
            if(!$match && $wildcard2)
            {
                $array1[$i] = 1;
            }
        }
        
        //echo implode("|",$array1)."<br>";
        //echo implode("|",$array2)."<br>";
        
        return (array_sum($array1)+array_sum($array2))/($count1+$count2);
    }
    
    
    
    
    
    
    
    
    
    
    
    public static function name_match($name1, $name2)
    {
        return self::namestring_match($name1->canonical_form()->string, $name2->canonical_form()->string);
    }
    
    public static function namestring_match($string1, $string2)
    {
        if(abs(strlen($string1) - strlen($string2)) > 2) return 0;
        if(strlen($string1) <= 4) return 0;
        if(strlen($string2) <= 4) return 0;
        
        $words1 = explode(" ", $string1);
        $words2 = explode(" ", $string2);
        
        if(count($words1) != count($words2)) return 0;
        
        $ratio_sum = 0;
        $number_of_words = count($words1);
        foreach($words1 as $k => $word1)
        {
            $word2 = $words2[$k];
            $max_length = max(strlen($word1), strlen($word2));
            //$lev = levenshtein($word1,$thisWord2);
            $lev = self::DamerauLevenshteinDistance($word1, $word2);
            $ratio = $lev/$max_length;
            
            if($number_of_words > 1 && $k==0)
            {
                if($ratio >= .1) return 0;
                else $ratio += 0;
            }else
            {
                $ratio_sum += $ratio;
            }
        }
        
        $ratio = $ratio_sum / (count($number_of_words)-1);
        
        if($ratio > .12) return 0;
        
        $color = "black";
        if($ratio<=.2) $color = "blue";
        elseif($ratio<=.3) $color = "red";
        elseif($ratio<=.4) $color = "green";
        
        echo "<font color=$color>$string1<br>$string2<br>$max_length - $lev - $ratio</font><br><br>";
        
        return $ratio;
    }
    
    
    
    
    public static function compare_authorship($name_string1, $canonical_form1, $name_string2, $canonical_form2)
    {
        $original_name_string1 = $name_string1;
        $original_name_string2 = $name_string2;
        
        $canonial_form_words = explode(" ",$canonical_form1);
        foreach($canonial_form_words as $word) $name_string1 = str_replace($word, "", $name_string1);
        while(preg_match("/  /",$name_string1)) $name_string1 = str_replace("  ", " ", $name_string1);
        $name_string1 = trim($name_string1);
        
        $canonial_form_words = explode(" ",$canonical_form2);
        foreach($canonial_form_words as $word) $name_string2 = str_replace($word, "", $name_string2);
        while(preg_match("/  /",$name_string2)) $name_string2 = str_replace("  ", " ", $name_string2);
        $name_string2 = trim($name_string2);
        
        $year1 = self::get_year($name_string1);
        $year2 = self::get_year($name_string2);
        
        $non_author1 = self::get_non_author($name_string1);
        $non_author2 = self::get_non_author($name_string2);
        
        $authorship1 = self::prepare_name_for_author_match($name_string1, $non_author1);
        $authorship2 = self::prepare_name_for_author_match($name_string2, $non_author2);
        
        
        //if(!$authorship1 || !$authorship2) return 0;
        //if(strlen($authorship1)<3 || strlen($authorship2)<3) return 0;
        //////THIS IS IMPORTANT
        //////THIS WILL IGNORE STRINGS WITH NO AUTHOR
        //////MANY TIMES THIS SHOULD BE COMMENTED OUT
        
        
        // if($non1 && preg_match("/".preg_quote($non1, "/")."/", $name_string2)) $eval2 = 0;
        // elseif($non2 && preg_match("/".preg_quote($non2, "/")."/", $name_string1)) $eval2 = 0;
        // else $eval2 = NamesFunctions::compare_names_sub($authorship1, $year1, $non1, $authorship2, $year2, $non2);
        
        echo "NamesFunctions::compare_names_sub($authorship1, $year1, $non_author1, $authorship2, $year2, $non_author2);<br>";
        $score = NamesFunctions::compare_names_sub($authorship1, $year1, $non_author1, $authorship2, $year2, $non_author2);
        
        if($score==100) echo "<font color=green>$original_name_string1<br>$original_name_string2<br>$score</font><br><br>";
        elseif($score>=90) echo "<font color=blue>$original_name_string1<br>$original_name_string2<br>$score</font><br><br>";
        elseif($score>=70) echo "<font color=orange>$original_name_string1<br>$original_name_string2<br>$score</font><br><br>";
        elseif($score>=47) echo "<font color=red>$original_name_string1<br>$original_name_string2<br>$score</font><br><br>";
        else echo "<font color=red>$original_name_string1<br>$original_name_string2<br>$score</font><br><br>";
        
        //return $eval2;
    }





    public static function compare_names_sub($authorship1, $year1, $non_author1, $authorship2, $year2, $non_author2)
    {
        if($non_author1 || $non_author2) return 0;
        
        $original_authorship1 = $authorship1;
        $original_authorship2 = $authorship2;
        
        $author_words1 = explode(" ", $authorship1);
        $author_words2 = explode(" ", $authorship2);
        
        $authorship1 = " ". $authorship1 ." ";
        $authorship2 = " ". $authorship2 ." ";
        
        $score = 0;
        
        while(list($key, $val) = each($author_words1))
        {
            $match1 = 0;
            while(list($key2, $val2) = each($author_words2))
            {
                $first_in_second = false;
                if(preg_match("/^".preg_quote($val, "/")."/i", $val2)) $first_in_second = true;
                
                $second_in_first = false;
                if(preg_match("/^".preg_quote($val2, "/")."/i", $val)) $second_in_first = true;
                
                if((strlen($val)>=3 && $first_in_second) || (strlen($val2)>=3 && $second_in_first) || $val==$val2)
                {
                    $authorship1 = str_replace(" $val ", " ", $authorship1);
                    $authorship2 = str_replace(" $val2 ", " ", $authorship2);
                }elseif($first_in_second)
                {
                    $authorship1 = str_replace(" $val "," ",$authorship1);
                }elseif($second_in_first)
                {
                    $authorship2 = str_replace(" $val2 "," ",$authorship2);
                }else
                {
                    $thisMax = max(strlen($val), strlen($val2));
                    //$lev = levenshtein($val,$val2);
                    $lev = self::DamerauLevenshteinDistance($val,$val2);
                    if(($lev/$thisMax)<=.167)
                    {
                        $authorship1 = str_replace(" $val ", " ", $authorship1);
                        $authorship2 = str_replace(" $val2 ", " ", $authorship2);
                    }
                }
            }
            reset($author_words2);
        }
        
        $countBefore1 = strlen(str_replace(" ", "", $original_authorship1));
        $countBefore2 = strlen(str_replace(" ", "", $original_authorship2));
        $countBefore = $countBefore1 + $countBefore2;
        $countAfter1 = strlen(str_replace(" ", "", $authorship1));
        $countAfter2 = strlen(str_replace(" ", "", $authorship2));
        $countAfter = $countAfter1 + $countAfter2;
        
        //echo "$countBefore1:$countAfter1 ========= $countBefore2:$countAfter2<br>";
        
        if(($countAfter1==0 && $countAfter2==0) && $year1 && $year1==$year2) return 100; //Same authors, same year
        elseif(($countAfter1==0 && $countAfter2==0) && $year1 && abs($year1-$year2)<=1) return 54; //Same authors, years off by 1
        //elseif(($countAfter1==0 && $countAfter2==0) && $year1 && abs($year1-$year2)<=2) return 53; //Same authors, years off by 2
        //elseif(($countAfter1==0 && $countAfter2==0) && $year1 && abs($year1-$year2)<=3) return 52; //Same authors, years off by 3
        //elseif(($countAfter1==0 && $countAfter2==0) && $year1 && $year2 && abs($year1-$year2)>3) return 10; //Same authors, years off by more than 3
        elseif(($countAfter1==0 && $countAfter2==0) && $year1 && $year2 && $year1!=$year2) return 0; //Same authors, years not equal
        elseif(($countAfter1==0 && $countAfter2==0) && (!$year1 || !$year2)) return 99; //Same authors, at least one has no year
        elseif(($countAfter1==0 && $countAfter2==0) && (!$year1 || !$year2)) return 0; //Same authors, at least one has no year
        else
        {
            if(($countAfter1==0 || $countAfter2==0) && $year1 && $year1==$year2) return 91; //Author match, same year
            elseif(($countAfter1==0 || $countAfter2==0) && $year1 && abs($year1-$year2)<=1) return 51; //Author match, years off by 1
            //elseif(($countAfter1==0 || $countAfter2==0) && $year1 && abs($year1-$year2)<=2) return 50; //Author match, years off by 2
            //elseif(($countAfter1==0 || $countAfter2==0) && $year1 && abs($year1-$year2)<=3) return 49; //Author match, years off by 3
            //elseif(($countAfter1==0 || $countAfter2==0) && $year1 && $year2 && abs($year1-$year2)>3) return 9; //Author match, years off by more than 3
            elseif(($countAfter1==0 || $countAfter2==0) && $year1 && $year2 && $year1!=$year2) return 0; //Author match, years not equal
            elseif(($countAfter1==0 || $countAfter2==0) && (!$year1 || !$year2)) return 90; //Author match, at least one has no year
            elseif(($countAfter1==0 || $countAfter2==0) && (!$year1 || !$year2)) return 0; //Author match, at least one has no year
            else
            {
                $score = 1-round(($countAfter/$countBefore),2);
                if($score==0 && $year1 && $year1!=$year2) return 0; //No author match, different year
                elseif($score==0 && $year1 && $year1==$year2) return 0; //No author match, same year
                elseif($score>=.3 && $year1 && $year1==$year2) return 89; //Similar authors, same year
                //elseif($score>=.3 && $year1 && abs($year1-$year2)<=1) return 48; //Similar authors, years off by 1
                //elseif($score>=.3 && $year1 && abs($year1-$year2)<=2) return 47; //Similar authors, years off by 2
                //elseif($score>=.3 && $year1 && abs($year1-$year2)<=3) return 46; //Similar authors, years off by 3
                //elseif($score>=.3 && $year1 && $year2 && abs($year1-$year2)>3) return 8; //Similar authors, years off by more than 3
                else return 0;
            }
        }
        return 0;
    }
    
    public function get_non_author($name_string)
    {
        $non_author = null;
        // [ non Linnaeus ]
        if(preg_match("/\[ ?\?? ?non ([^\]]+)\]/u", $name_string, $arr))
        {
            $non_author = trim($arr[1]);
        }
        // ( non Linnaeus )
        elseif(preg_match("/\( ?\?? ?non ([^\]]+)\)/u", $name_string, $arr))
        {
            $non_author = trim($arr[1]);
        }
        
        return $non_author;
    }
    
    public function get_year($name_string)
    {
        $year = null;
        if(preg_match("/(.*)(1[7-9][0-9]{2}|200[0-7])(.*)/", $name_string, $arr))
        {
            $year = $arr[2];
        }
        return $year;
    }
    
    public function prepare_name_for_author_match($name_string, $non_author = null)
    {
        $name_string = " ". $name_string ." ";
        $name_string = str_replace("[", " ", $name_string);
        $name_string = str_replace("]", " ", $name_string);
        $name_string = str_replace("(", " ", $name_string);
        $name_string = str_replace(")", " ", $name_string);
        $name_string = str_replace(".", " ", $name_string);
        $name_string = str_replace(",", " ", $name_string);
        $name_string = str_replace("'", " ", $name_string);
        $name_string = str_replace("-", " ", $name_string);
        $name_string = str_replace("&", " ", $name_string);
        $name_string = str_replace(" et ", " ", $name_string);
        $name_string = str_replace(" and ", " ", $name_string);
        $name_string = str_replace(" ex ", " ", $name_string);
        $name_string = str_replace(" al ", " ", $name_string);
        if($non_author) $name_string = str_replace("non ". $non_author, " ", $name_string);
        while(preg_match("/^(.*[^0-9".UPPER.LOWER."])(1[7-9][0-9]{2}|200[0-7])([^0-9".UPPER.LOWER."].*$|$)/", $name_string, $arr)) $name_string = $arr[1]." ".$arr[3];
        while(preg_match("/^(.*) in (.*)$/", $name_string, $arr)) $name_string = $arr[1];
        while(preg_match("/^(.*) emend (.*)$/", $name_string, $arr)) $name_string = $arr[1];
        while(preg_match("/  /", $name_string)) $name_string = str_replace("  ", " ", $name_string);
        $name_string = trim(strtolower(Functions::utf8_to_ascii($name_string)));
        
        return $name_string;
    }
    
    
    function DamerauLevenshteinDistance($str1,$str2)
    {
        $str1_length = strlen($str1);
        $str2_length = strlen($str2);
        
        $d = array();
        $cost = 0;
        
        for($i=0 ; $i<=$str1_length ; $i++)
        {
            $d[$i][0] = $i;
        }
        
        for($j=1 ; $j<=$str2_length ; $j++)
        {
            $d[0][$j] = $j;
        }
        
        for($i=1 ; $i<=$str1_length ; $i++)
        {
            for($j=1 ; $j<=$str2_length ; $j++)
            {
                $chr1 = $str1[$i-1];
                $chr2 = $str2[$j-1];
                
                if($chr1==$chr2) $cost = 0;
                else $cost = 1;
                
                $d[$i][$j] = min($d[$i-1][$j]+1, $d[$i][$j-1]+1, $d[$i-1][$j-1]+$cost);
                
                if($i>1 && $j>1 && $chr1==$str2[$j-2] && $chr2==$str1[$i-2])
                {
                    $d[$i][$j] = min($d[$i][$j], $d[$i-2][$j-2]+$cost);
                }
            }
        }

        return $d[$str1_length][$str2_length];
    }
}

function unassigned_filter($name_id)
{
    if(in_array($name_id, Name::unassigned_ids())) return false;
    return true;
}

?>