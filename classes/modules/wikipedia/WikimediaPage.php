<?php

class WikimediaPage
{
    public $xml;
    private $simple_xml;
    
    function __construct($xml)
    {
        $this->xml = $xml;
        $this->simple_xml = simplexml_load_string($this->xml);
        $this->text = (string) $this->simple_xml->revision->text;
        $this->title = (string) $this->simple_xml->title;
        $this->contributor = (string) $this->simple_xml->revision->contributor->username;
    }
    
    public function information()
    {
        if($this->information) return $this->information;
        
        $information = array();
        if(preg_match("/(\{\{Information.*?\}\})(.*)/ms", $this->text, $arr))
        {
            list($information_box, $junk) = WikiParser::balance_tags("{{", "}}", $arr[1], $arr[2], true);
            
            $parts = preg_split("/(^|\n)\s*\|/", $information_box);
            while($part = array_pop($parts))
            {
                // further split on |Attribute=
                while(preg_match("/^(.*?)\|([A-Z][a-z]+=.*$)/ms", $part, $arr))
                {
                    $part = $arr[1];
                    array_push($parts, $arr[2]);
                }
                if(preg_match("/^\s*([^\s]*)\s*=(.*)$/ms", $part, $arr))
                {
                    $attribute = strtolower(trim($arr[1]));
                    $value = trim($arr[2]);
                    $information[$attribute] = $value;
                }
            }
        }
        
        $this->information = $information;
        return $information;
    }
    
    public function taxonomy()
    {
        if($this->taxonomy) return $this->taxonomy;
        
        $taxonomy = array();
        if(preg_match("/(\{\{Taxonavigation.*?\}\})(.*)/ms", $this->text, $arr))
        {
            list($taxonomy_box, $junk) = WikiParser::balance_tags("{{", "}}", $arr[1], $arr[2], true);
            
            $authority = "";
            $parts = explode("\n", $taxonomy_box);
            while($part = array_pop($parts))
            {
                $part = trim($part);
                if(preg_match("/^\s*([a-z]+)\s*\|\s*([^\|]+)\|?$/ims", $part, $arr))
                {
                    $attribute = strtolower(trim($arr[1]));
                    $value = trim($arr[2]);
                    $taxonomy[$attribute] = $value;
                }elseif(preg_match("/^\s*authority\s*=\s*(.*)$/ims", $part, $arr))
                {
                    $authority = WikiParser::strip_syntax(trim($arr[1]));
                }
            }
            
            if($authority)
            {
                $value = current($taxonomy);
                $taxonomy[key($taxonomy)] = $value .' '. $authority;
            }
        }
        
        $this->taxonomy = $taxonomy;
        return $taxonomy;
    }
    
    /*
        Different licenses I found:
        
        PD-Art|PD-old-100
        Cc-by
        self|GFDL|cc-by-sa-2.5,2.0,1.0
        PD-old
        GFDL
        cc-by-2.5
        PD-user|Mtcv
        PD-age
        PD-Art
        Pd/1923|1952
        PD-self
        GFDL-self
        NIH
        PD-PCL-portraits
        GFDL-self
        PD-USGov-FWS
        PD-USGov-Interior-FWS
        PD-USGov-NASA
        self|cc-by|GFDL
        PD-USGov-CIA-WF
        PD-USGov-Congress
        USAID
        cc-by-2.5-pl
        Creative Commons
        Public Domain
    */
    
    public function licenses()
    {
        if($this->licenses) return $this->licenses;
        
        $licenses = array();
        
        if(preg_match_all("/(\{\{.*?\}\})/", $this->text, $matches, PREG_SET_ORDER))
        {
            foreach($matches as $match)
            {
                //echo "$match[1]<br>";
                while(preg_match("/(\{|\|)(cc-.*?|pd|pd-.*?|gfdl|gfdl-.*?|usaid|nih|copyrighted free use|creative commons.*?)(\}|\|)(.*)/msi", $match[1], $arr))
                {
                    $licenses[] = trim($arr[2]);
                    $match[1] = $arr[3].$arr[4];
                }
            }
        }
        
        if(!$licenses && preg_match("/permission\s*=\s*(gpl.*?|public domain.*?|creative commons .*?)(\}|\|)/msi", $this->text, $arr))
        {
            $licenses[] = trim($arr[1]);
        }
        
        // foreach($licenses as $key => $license)
        // {
        //     if(!preg_match("/^(cc|pd|usaid|nih|creative commons|public domain)/i", $license)) unset($licenses[$key]);
        // }
        
        $this->licenses = $licenses;
        return $licenses;
    }
    
    public function author()
    {
        if($this->author) return $this->author;
        
        $author = "";
        
        if($info = $this->information())
        {
            foreach($info as $attr => $val)
            {
                if($attr == "author") $author = WikiParser::strip_syntax($val, true);
            }
        }
        
        $this->author = $author;
        return $author;
    }
    
    public function description()
    {
        if($this->description) return $this->description;
        
        $authors = array();
        
        if($info = $this->information())
        {
            foreach($info as $attr => $val)
            {
                if($attr == "description") $description = WikiParser::strip_syntax($val, true);
            }
        }
        
        $this->description = $description;
        return $description;
    }
    
    public function images()
    {
        $images = array();
        
        $text = $this->text();
        $lines = explode("\n", $text);
        foreach($lines as $line)
        {
            if(preg_match("/^\[*(image|file)\s*:(.*)$/i", $line, $arr))
            {
                $images[] = trim($arr[2]);
            }
        }
        
        return $images;
    }
}

?>