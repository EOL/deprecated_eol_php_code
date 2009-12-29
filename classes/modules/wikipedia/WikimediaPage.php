<?php

class WikimediaPage
{
    public $xml;
    private $simple_xml;
    
    function __construct($xml)
    {
        $this->xml = $xml;
        $this->simple_xml = @simplexml_load_string($this->xml);
        $this->text = (string) $this->simple_xml->revision->text;
        $this->title = (string) $this->simple_xml->title;
        $this->contributor = (string) $this->simple_xml->revision->contributor->username;
    }
    
    public function information()
    {
        if(isset($this->information)) return $this->information;
        
        $information = array();
        if(preg_match("/(\{\{Information.*?\}\})(.*)/ms", $this->text, $arr))
        {
            list($information_box, $junk) = WikiParser::balance_tags("{{", "}}", $arr[1], $arr[2], true);
            
            $parts = preg_split("/(^|\n)\s*\|/", $information_box);
            while($part = array_shift($parts))
            {
                // further split on |Attribute=
                while(preg_match("/^(.+?)\|([A-Z][a-z]+=.*$)/ms", $part, $arr))
                {
                    $part = $arr[1];
                    array_unshift($parts, $arr[2]);
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
        if(isset($this->taxonomy)) return $this->taxonomy;
        
        $taxonomy = array();
        if(preg_match("/(\{\{Taxonavigation.*?\}\})(.*)/ms", $this->text, $arr))
        {
            list($taxonomy_box, $junk) = WikiParser::balance_tags("{{", "}}", $arr[1], $arr[2], true);
            
            $authority = "";
            $parts = preg_split("/[\n\r]/", trim($taxonomy_box));
            //echo "<hr>$taxonomy_box<br><br>";
            //Functions::print_pre($parts);
            while($part = array_pop($parts))
            {
                //echo "PART:$part<br>";
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
            
            // add the authority to the last entry
            if($authority)
            {
                $value = current($taxonomy);
                $taxonomy[key($taxonomy)] = $value .' '. $authority;
            }
            
            // there are often some extra ranks under the Taxonnavigation box
            if(preg_match("/\}\}\s*\n(\s*----\s*\n)?((\*?(genus|species):.*?\n)*)/ims", $this->text, $arr))
            {
                $entries = explode("\n", $arr[2]);
                foreach($entries as $entry)
                {
                    if(preg_match("/^\*?(genus|species):(.*)/ims", trim($entry), $arr))
                    {
                        $rank = strtolower($arr[1]);
                        $name = preg_replace("/\s+/", " ", WikiParser::strip_syntax(trim($arr[2])));
                        $taxonomy[$rank] = $name;
                    }
                }
            }
        }
        
        foreach($taxonomy as &$name)
        {
            $name = preg_replace("/\s*\|$/", "", trim($name));
            $name = str_replace("<small>", "", $name);
            $name = str_replace("</small>", "", $name);
            if(!Functions::is_utf8($name) || preg_match("/\{/", $name))
            {
                $taxonomy = array();
                break;
            }
        }
        
        $this->taxonomy = $taxonomy;
        return $taxonomy;
    }
    
    public function taxon_parameters()
    {
        if(isset($this->taxon_parameters)) return $this->taxon_parameters;
        $taxonomy = $this->taxonomy();
        
        $taxon_rank = key($taxonomy);
        $taxon_name = current($taxonomy);
        
        $taxon_parameters = array();
        if($taxon_rank!='regnum' && $v = @$taxonomy['regnum']) $taxon_parameters['kingdom'] = $v;
        if($taxon_rank!='phylum' && $v = @$taxonomy['phylum']) $taxon_parameters['phylum'] = $v;
        if($taxon_rank!='classis' && $v = @$taxonomy['classis']) $taxon_parameters['class'] = $v;
        if($taxon_rank!='ordo' && $v = @$taxonomy['ordo']) $taxon_parameters['order'] = $v;
        if($taxon_rank!='familia' && $v = @$taxonomy['familia']) $taxon_parameters['family'] = $v;
        if($taxon_rank!='genus' && $v = @$taxonomy['genus']) $taxon_parameters['genus'] = $v;
        $taxon_parameters['scientificName'] = $taxon_name;
        $taxon_parameters["identifier"] = str_replace(" ", "_", $this->title);
        $taxon_parameters["source"] = "http://commons.wikimedia.org/wiki/".str_replace(" ", "_", $this->title);
        
        
        $taxon_parameters['dataObjects'] = array();
        $this->taxon_parameters = $taxon_parameters;
        return $taxon_parameters;
    }
    
    public function data_object_parameters()
    {
        if(isset($this->data_object_parameters)) return $this->data_object_parameters;
        
        $data_object_parameters = array();
        $licenses = $this->licenses();
        foreach($licenses as $key => $val)
        {
            //PD-USGov-CIA-WF
            if(preg_match("/^(pd|public domain|cc-pd|usaid|nih)($| |-)/i", $val))
            {
                $data_object_parameters["license"] = "http://creativecommons.org/licenses/publicdomain/";
                break;
            }
            //Cc-by-sa-2.5,2.0,1.0-de
            if(preg_match("/^cc-(by(-nc)?(-nd)?(-sa)?)(.*)$/i", $val, $arr))
            {
                $license = strtolower($arr[1]);
                $rest = $arr[2];
                
                if(preg_match("/^-?([0-9]\.[0-9])/", $rest, $arr)) $version = $arr[1];
                else $version = "3.0";
                
                $data_object_parameters["license"] = "http://creativecommons.org/licenses/$license/$version/";
                break;
            }
            //cc-sa-1.0
            if(preg_match("/^(cc-sa)(.*)$/i", $val, $arr))
            {
                $license = "by-sa";
                $rest = $arr[2];
                
                if(preg_match("/^-?([0-9]\.[0-9])/", $rest, $arr)) $version = $arr[1];
                else $version = "3.0";
                
                $data_object_parameters["license"] = "http://creativecommons.org/licenses/$license/$version/";
                break;
            }
            //can be relicensed as cc-by-sa-3.0
            if(preg_match("/migration=relicense/i", $val))
            {
                $data_object_parameters["license"] = "http://creativecommons.org/licenses/by-sa/3.0/";
                break;
            }
        }
        if(!isset($data_object_parameters["license"]))
        {
            echo "LICENSE: $this->title\n";
            print_r($licenses);
            return false;
        }
        
        $data_object_parameters["identifier"] = str_replace(" ", "_", $this->title);
        $data_object_parameters["dataType"] = "http://purl.org/dc/dcmitype/StillImage";
        $data_object_parameters["title"] = $this->title;
        $data_object_parameters["source"] = "http://commons.wikimedia.org/wiki/".str_replace(" ", "_", $this->title);
        $data_object_parameters["description"] = $this->description();
        
        //if($this->description() && preg_match("/([^".UPPER.LOWER."0-9\/,\.;:'\"\(\)\[\]\{\}\|\!\?~@#\$%+_\^&\*<>=\n\r -])/ims", $this->description(), $arr))
        if($data_object_parameters["description"] && !Functions::is_utf8($data_object_parameters["description"]))
        {
            $data_object_parameters["description"] = "";
            
            //echo "THIS IS BAD:<br>\n";
            //echo $this->description()."<br>\n";
        }
        
        $data_object_parameters["agents"] = array();
        if($this->agent_parameters())
        {
            $data_object_parameters["agents"][] = new SchemaAgent($this->agent_parameters());
        }
        
        return $data_object_parameters;
    }
    
    public function agent_parameters()
    {
        if(isset($this->agent_parameters)) return $this->agent_parameters;
        $author = $this->author();
        
        $homepage = "";
        if(preg_match("/<a href='(.*?)'>/", $author, $arr)) $homepage = $arr[1];
        $author = preg_replace("/<a href='(.*?)'>/", "", $author);
        $author = str_replace("</a>", "", $author);
        
        $agent_parameters = array();
        $agent_parameters["fullName"] = htmlspecialchars($author);
        if(Functions::is_ascii($homepage) && !preg_match("/[\[\]\(\)'\",;]/", $homepage)) $agent_parameters["homepage"] = str_replace(" ", "_", $homepage);
        
        $this->agent_parameters = $agent_parameters;
        return $agent_parameters;
    }
    
    public function licenses()
    {
        if(isset($this->licenses)) return $this->licenses;
        
        $licenses = array();
        
        if(preg_match_all("/(\{\{.*?\}\})/", $this->text, $matches, PREG_SET_ORDER))
        {
            foreach($matches as $match)
            {
                //echo "$match[1]<br>";
                while(preg_match("/(\{|\|)(cc-.*?|pd|pd-.*?|gfdl|gfdl-.*?|usaid|nih|copyrighted free use|creative commons.*?|migration=.*?)(\}|\|)(.*)/msi", $match[1], $arr))
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
        
        $this->licenses = $licenses;
        return $licenses;
    }
    
    public function author()
    {
        if(isset($this->author)) return $this->author;
        
        $author = "";
        
        if($info = $this->information())
        {
            foreach($info as $attr => $val)
            {
                if($attr == "author") $author = WikiParser::strip_syntax($val, true);
            }
        }
        if((!$author || !Functions::is_utf8($author)) && $this->contributor) $author = "<a href='".WIKI_USER_PREFIX."$this->contributor'>$this->contributor</a>";
        
        $this->author = $author;
        return $author;
    }
    
    public function description()
    {
        if(isset($this->description)) return $this->description;
        
        $authors = array();
        
        $description = "";
        if($info = $this->information())
        {
            foreach($info as $attr => $val)
            {
                if($attr == "description")
                {
                    $description = WikiParser::strip_syntax($val, true);
                }
            }
        }
        
        $this->description = $description;
        return $description;
    }
    
    public function images()
    {
        $images = array();
        
        $text = $this->text;
        $lines = explode("\n", $text);
        foreach($lines as $line)
        {
            if(preg_match("/^\s*\[{0,2}\s*(image|file)\s*:(.*?)(\||$)/i", $line, $arr))
            {
                $images[] = trim($arr[2]);
            }
        }
        
        return $images;
    }
}






?>