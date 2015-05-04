<?php

class RDFDocument
{
    public $url;
    public $source_url;
    public $model;
    
    function __construct($url, $source_url = "")
    {
        $this->url = $url;
        $this->source_url = $source_url;
        $this->model = ModelFactory::getDefaultModel();
        
        $document = Functions::get_remote_file($url);
        if(!preg_match("/rdf:rdf/i", $document)) throw new Exception("Not a valid RDF Document: $url");
        if(!($FILE = fopen(DOC_ROOT . "temp/downloaded_rdf.rdf", "w+")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " ."temp/downloaded_rdf.rdf");
          return;
        }
        fwrite($FILE, $document);
        fclose($FILE);
        
        $this->model->load(DOC_ROOT . "temp/downloaded_rdf.rdf");
        
        $this->prefixes = array(    "rdf"           => "http://www.w3.org/1999/02/22-rdf-syntax-ns#",
                                    "spmi"          => "http://rs.tdwg.org/ontology/voc/SPMInfoItems#",
                                    "spm"           => "http://rs.tdwg.org/ontology/voc/SpeciesProfileModel#",
                                    "tc"            => "http://rs.tdwg.org/ontology/voc/TaxonConcept#",
                                    "rdfs"          => "http://www.w3.org/2000/01/rdf-schema#",
                                    "tn"            => "http://rs.tdwg.org/ontology/voc/TaxonName#",
                                    "xsd"           => "http://www.w3.org/2001/XMLSchema#",
                                    "tdwg_common"   => "http://rs.tdwg.org/ontology/voc/Common#",
                                    "dc"            => "http://purl.org/dc/elements/1.1/", 
                                    "dwc"           => "http://digir.net/schema/conceptual/darwin/2003/1.0",
                                    "tbase"         => "http://rs.tdwg.org/ontology/Base#",
                                    "tcom"          => "http://rs.tdwg.org/ontology/voc/Common#",
                                    "tpcit"         => "http://rs.tdwg.org/ontology/voc/PublicationCitation#");
    }
    
    function load_querystring()
    {
        $querystring = "";
        foreach($this->prefixes as $namespace => $prefix)
        {
            $querystring .= "PREFIX ".$namespace.": <".$prefix.">\n";
        }
        
        return $querystring;
    }
    
    function show()
    {
        $this->model->writeAsHtmlTable();
    }
    
    function get_resources($type, $object_type = "RDFDocumentElement")
    {
        $querystring = "SELECT ?resource WHERE { ?resource rdf:type $type }";
        
        $results = $this->sparql_query($querystring);
        
        $resources = array();
        if($results)
        {
            foreach($results as $result)
            {
                if(@$resource = $result['?resource']) $resources[] = RDFDocumentElement::create($object_type, $this, $resource->getLabel());
            }
        }
        
        return $resources;
    }
    
    function sparql_query($querystring)
    {
        $querystring = $this->load_querystring() . $querystring;
        //debug("$querystring<hr>");
        
        $result = $this->model->sparqlQuery($querystring);
        //if(DEBUG) SPARQLEngine::writeQueryResultAsHtmlTable($result);
        
        return $result;
    }
    
    function saveAs($path)
    {
        $this->model->saveAs($path);
    }
}

?>