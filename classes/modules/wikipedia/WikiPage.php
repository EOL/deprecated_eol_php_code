<?php

class WikiPage
{
    private $xml;
    private $simple_xml;
    
    function __construct($xml)
    {
        $this->xml = $xml;
        $this->simple_xml = simplexml_load_string($this->xml);
        $this->text = $this->simple_xml->revision->text;
    }
    
    public function is_scientific()
    {
        if(preg_match("/\n *\| *(regnum|phylum|classis|superordo|ordo|familia|genus|species|binomial|subspecies|variety|trinomial) *=/i", $this->xml)) return true;
        
        return false;
    }
    
    public function text()
    {
        if($this->simple_xml)
        {
            return $this->simple_xml->revision->text;
        }
        
        return false;
    }
    
    public function title()
    {
        if($this->simple_xml)
        {
            return $this->simple_xml->title;
        }
        
        return false;
    }
    
    public function scientific_names()
    {
        $taxonomy = $this->taxonomy();
        
        $name = "";
        
        // See http://en.wikipedia.org/wiki/Wikipedia:Taxobox_usage
        $priority = array(
                "trinomial",
                "binomial",
                "subspecies",
                "species",
                "species_complex",
                "species_subgroup",
                "species_group",
                "series",
                "sectio",
                "subgenus",
                "genus2",
                "genus",
                "subtribus",
                "tribus",
                "supertribus",
                "subfamilia",
                "familia",
                "superfamilia",
                "unranked_familia",
                "zoosubsectio",
                "zoosectio",
                "zoodivisio",
                "parvordo",
                "superfamilia",
                "infraordo",
                "subordo",
                "ordo",
                "superordo",
                "magnordo",
                "unranked_ordo",
                "infraclassis",
                "subclassis",
                "classis",
                "superclassis",
                "unranked_classis",
                "nanophylum",
                "microphylum",
                "infraphylum",
                "subphylum",
                "subdivisio",
                "phylum",
                "unranked_divisio",
                "divisio",
                "superphylum",
                "superdivisio",
                "unranked_phylum",
                "subregnum",
                "regnum",
                "superregnum",
                "unranked_regnum",
                "domain",
                "virus_group"
            );
        
        $names = array();
        
        if(@$taxonomy["subdivision"])
        {
            // $subdivisions = explode("<br>", $taxonomy["subdivision"]);
            // foreach($subdivisions as $subdivision)
            // {
            //     $subdivision = trim($subdivision);
            //     $names[] = Functions::import_decode($subdivision, true, true);
            // }
        }elseif(@$taxonomy["includes"])
        {
            // $includes = explode("<br>", $taxonomy["includes"]);
            // foreach($includes as $include)
            // {
            //     $include = trim($include);
            //     $include = preg_replace("/^:/", "", $include);
            //     $names[] = Functions::import_decode($include, true, true);
            // }
        }else
        {
            foreach($priority as $rank)
            {
                if(@$taxonomy[$rank] && !preg_match("/<br/",$taxonomy[$rank]))
                {
                    $names[] = Functions::import_decode($taxonomy[$rank], true, true);
                    break;
                }
            }
        }
        
        // Remove some extra junk
        foreach($names as $key => $name)
        {
            while(preg_match("/&dagger;/", $name)) $name = trim(str_replace("&dagger;", " ", $name));
            while(preg_match("/†/u", $name)) $name = trim(str_replace("†", " ", $name));
            while(preg_match("/\*/", $name)) $name = trim(str_replace("*", "", $name));
            while(preg_match("/\?/", $name)) $name = trim(str_replace("?", "", $name));
            while(preg_match("/'/", $name)) $name = trim(str_replace("'", "", $name));
            $name = preg_replace("/ in part$/ims", "", $name);
            
            $names[$key] = $name;
        }
        
        return $names;
    }
    
    public function taxonomy()
    {
        if($this->taxonomy) return $this->taxonomy;
        
        $taxonomy = array();
        //if(preg_match("/\{\{Taxobox(.*?)\}\}/ms", $this->xml, $arr))
        if(preg_match("/(\{\{Taxobox.*?\}\})(.*)/ms", $this->text, $arr))
        {
            list($taxobox, $junk) = WikiParser::balance_tags("{{", "}}", $arr[1], $arr[2], true);
            
            // echo "<pre>>>>>";
            // print_r($taxobox);
            // echo "<<<</pre>";
            
            $stripped_taxobox = WikiParser::strip_syntax($taxobox, false, $this->title());
            
            // echo "<pre>>>>>";
            // print_r($stripped_taxobox);
            // echo "<<<</pre>";
            
            $parts = explode("|", $stripped_taxobox);
            foreach($parts as $part)
            {
                if(preg_match("/^\s*([^\s]*)\s*=(.*)$/ms", $part, $arr))
                {
                    $attribute = trim($arr[1]);
                    $value = trim($arr[2]);
                    
                    // echo "$attribute =  $value<br>";
                    // echo WikiParser::strip_syntax($part)."<br><br>";
                    
                    $value = WikiParser::strip_tags(WikiParser::strip_syntax($value));
                    if(preg_match("/^(.*)In part$/ims", $value, $arr)) $value = $arr[1];
                    if(preg_match("/^(.*)<br>\s*$/ims", $value, $arr)) $value = $arr[1];
                    
                    $taxonomy[$attribute] = $value;
                    // echo "$attribute =  $value<br>\n";
                }
            }
        }
        
        if($taxonomy["species"] && preg_match("/^[A-Z][a-z]{0,2}\.( .*)$/", $taxonomy["species"], $arr))
        {
            if($taxonomy["genus"] && preg_match("/^[^ ]*$/", $taxonomy["genus"])) $taxonomy["species"] = $taxonomy["genus"] . $arr[1];
        }
        
        if($taxonomy["binomial"] && preg_match("/^[A-Z][a-z]{0,2}\.( .*)$/", $taxonomy["binomial"], $arr))
        {
            if($taxonomy["genus"] && preg_match("/^[^ ]*$/", $taxonomy["genus"])) $taxonomy["binomial"] = $taxonomy["genus"] . $arr[1];
        }
        
        if($taxonomy["subspecies"] && preg_match("/^[A-Z][a-z]{0,2}\.( .*)$/", $taxonomy["subspecies"], $arr))
        {
            if($taxonomy["genus"] && preg_match("/^[^ ]*$/", $taxonomy["genus"])) $taxonomy["subspecies"] = $taxonomy["genus"] . $arr[1];
        }
        
        if($taxonomy["trinomial"] && preg_match("/^[A-Z][a-z]{0,2}\.( .*)$/", $taxonomy["trinomial"], $arr))
        {
            if($taxonomy["genus"] && preg_match("/^[^ ]*$/", $taxonomy["genus"])) $taxonomy["trinomial"] = $taxonomy["genus"] . $arr[1];
        }
        
        foreach($taxonomy as $key => $value)
        {
            if(@$taxonomy[$key."_authority"]) $taxonomy[$key] .= " " . $taxonomy[$key."_authority"];
        }
        
        // echo "<pre>";
        // print_r($taxonomy);
        // echo "</pre>";
        
        $this->taxonomy = $taxonomy;
        
        return $taxonomy;
    }
}

?>