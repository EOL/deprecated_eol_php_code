<?php

class RDFDocumentElement
{
    public $document;
    public $local_uri;
    
    function __construct($document, $local_uri)
    {
        $this->document = $document;
        $this->local_uri = $local_uri;
    }
    
    function initialize($document, $local_uri)
    {
        $this->document = $document;
        $this->local_uri = $local_uri;
    }
    
    function get_resource_reference($predicate)
    {
        $querystring = "SELECT ?resource WHERE { <$this->local_uri> $predicate ?resource }";
        
        $results = $this->document->sparql_query($querystring);
        
        if(@$resource = $results[0]['?resource']) return $resource->getLabel();
        
        return false;
    }
    
    function get_resource($predicate, $object_type = "RDFDocumentElement")
    {
        $querystring = "SELECT ?resource WHERE { <$this->local_uri> $predicate ?resource }";
        
        $results = $this->document->sparql_query($querystring);
        
        if(@$resource = $results[0]['?resource']) return RDFDocumentElement::create($object_type, $this->document, $resource->getLabel());
        
        return false;
    }

    function get_resources($predicate, $object_type = "RDFDocumentElement")
    {
        $querystring = "SELECT ?resource WHERE { <$this->local_uri> $predicate ?resource }";
        
        $results = $this->document->sparql_query($querystring);
        
        $resources = array();
        foreach($results as $result)
        {
            if(@$resource = $result['?resource'])
            {
                $resource = RDFDocumentElement::create($object_type, $this->document, $resource->getLabel());
                if($resource) $resources[] = $resource;
            }
            
        }
        
        return $resources;
    }
    
    function get_type()
    {
        return $this->get_literal("rdf:type");
    }
    
    function get_id()
    {
        return $this->get_literal("rdf:ID");
    }
    
    function get_literal($predicate)
    {
        $querystring = "SELECT ?literal WHERE { <$this->local_uri> $predicate ?literal }";
        
        $results = $this->document->sparql_query($querystring);
        
        if(@$literal = $results[0]['?literal']) return $literal->getLabel();
        
        return false;
    }
    
    public static function create($class, $document, $uri)
    {
        if(!trim($uri)) return false;
        
        //debug("Creating $class -> document -> $uri");
        if(preg_match("/^([#_\/].*)/", $uri, $arr)) return new $class($document, $uri);
        elseif(preg_match("/^".preg_quote($document->url,"/")."(#.*)$/", $uri, $arr)) return new $class($document, $uri);
        elseif(preg_match("/^urn:lsid:/", $uri))
        {
            if(file_exists("cache/".$uri.".xml"))
            {
                $doc_uri = "cache/".$uri.".xml";
                try { $document = new RDFDocument($doc_uri); }
                catch (Exception $e)
                {
                    //Functions::catch_exception($e);
                }
            }else
            {
                $doc_uri = LSID_RESOLVER . $uri;
                try { $document = new RDFDocument($doc_uri); }
                catch (Exception $e)
                {
                    //Functions::catch_exception($e);
                }
                $document->saveAs("cache/".$uri.".xml");
            }
            
            //debug($doc_uri);
            
            return new $class($document, $uri);
        }
        
        return false;
    }
    
    public function identifier()
    {
        $document_url = $this->document->url;
        if($this->document->source_url) $document_url = $this->document->source_url;
        
        if(preg_match("/^(#.*)/", $this->local_uri, $arr)) return $document_url . $this->local_uri;
        elseif(preg_match("/^".preg_quote($document_url,"/")."(#.*)$/", $this->local_uri, $arr)) return $this->local_uri;
        elseif(preg_match("/^urn:lsid:/", $this->local_uri)) return $this->local_uri;
        
        return "";
    }
    
    public function __toString()
    {
        $string = $this->document->url . $this->local_uri;
        
        return $string;
    }
}

?>