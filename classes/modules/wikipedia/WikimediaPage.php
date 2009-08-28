<?php

class WikimediaPage
{
    private $xml;
    private $simple_xml;
    
    function __construct($xml)
    {
        $this->xml = $xml;
        $this->simple_xml = simplexml_load_string($this->xml);
        $this->text = (string) $this->simple_xml->revision->text;
        $this->title = (string) $this->simple_xml->title;
    }
    
    public function information()
    {
        if($this->information) return $this->information;
        
        $information = array();
        if(preg_match("/(\{\{Information.*?\}\})(.*)/ms", $this->text, $arr))
        {
            list($information_box, $junk) = WikiParser::balance_tags("{{", "}}", $arr[1], $arr[2], true);
            
            $parts = preg_split("/(^|\n)\s*\|/", $information_box);
            foreach($parts as $part)
            {
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
    
    public function licenses()
    {
        if($this->licenses) return $this->licenses;
        
        $licenses = array();
        /*
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
        */
        if(preg_match_all("/(\{\{.*?\}\})/", $this->text, $matches, PREG_SET_ORDER))
        {
            foreach($matches as $match)
            {
                //echo "$match[1]<br>";
                if(preg_match("/(\{|\|)((cc-.*?|pd|pd-.*?|gfdl|gfdl-.*?|usaid|nih|copyrighted free use))(\}|\|)/msi", $match[1], $arr))
                {
                    $licenses[] = $arr[2];
                }
            }
        }
        
        // foreach($licenses as $key => $license)
        // {
        //     if(!preg_match("/^(cc|pd|usaid|nih)/i", $license)) unset($licenses[$key]);
        // }
        
        $this->licenses = $licenses;
        return $licenses;
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