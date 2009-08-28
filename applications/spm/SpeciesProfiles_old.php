<?php

class SpeciesProfile
{
    public $url;
    public $model;
    public $prefixes;
    
    function __construct($url)
    {
        $this->url = $url;
        $this->model = ModelFactory::getDefaultModel();
        $this->model->load($url);
    }
    
    function show()
    {
        $this->model->writeAsHtmlTable();

        $it = $this->model->getStatementIterator();
        while ($it->hasNext())
        {
           $statement = $it->next();
           echo "Statement number: " . $it->getCurrentPosition() . "<BR>";
           echo "Subject: " . $statement->getLabelSubject() . "<BR>";
           echo "Predicate: " . $statement->getLabelPredicate() . "<BR>";
           echo "Object: " . $statement->getLabelObject() . "<P>";
        }
    }
    
    function parse()
    {
        $profiles = $this->get_profiles();
        
        foreach($profiles as $profile)
        {
            $profile_id = $profile->getLabel();
            $info_items = $this->get_profile_info_items($profile_id);
            foreach($info_items as $info_item)
            {
                $info_item_id = $info_item->getLabel();
                echo $this->get_info_item_content($info_item_id)."<br>\n";
            }
            
            $taxa = $this->get_profile_taxa($profile_id);
            foreach($taxa as $taxon)
            {
                $taxon_id = $taxon->getLabel();
                $this->get_taxon($taxon_id);
            }
        }
    }
    
    function get_profiles()
    {
        $querystring = "
        PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
        PREFIX spm: <http://rs.tdwg.org/ontology/voc/SpeciesProfileModel#>
        SELECT ?profile
        WHERE { ?profile rdf:type spm:SpeciesProfileModel }";
        
        $result = $this->sparqlQuery($querystring);
        if(!$result) return array();
        
        $profiles = array();
        foreach($result as $line)
        {
            if($id = $line['?profile']) $profiles[] = $id;
        }
        
        return $profiles;
    }
    
    function get_profile_info_items($id)
    {
        $querystring = "
        PREFIX spm: <http://rs.tdwg.org/ontology/voc/SpeciesProfileModel#>
        SELECT ?info_item
        WHERE { <$id> spm:hasInformation ?info_item }";
        
        $result = $this->sparqlQuery($querystring);
        if(!$result) return array();
        
        $info_items = array();
        foreach($result as $line)
        {
            if($id = $line['?info_item']) $info_items[] = $id;
        }
        
        return $info_items;
    }
    
    function get_info_item_content($id)
    {
        $querystring = "
        PREFIX spm: <http://rs.tdwg.org/ontology/voc/SpeciesProfileModel#>
        SELECT ?predicate ?object
        WHERE { <$id> ?predicate ?object }";
        
        $result = $this->sparqlQuery($querystring);
        
        if($content = $result[0]['?content']) return $content->getLabel();
        
        return "";
    }
    
    function get_profile_taxa($id)
    {
        $querystring = "
        PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
        PREFIX spm: <http://rs.tdwg.org/ontology/voc/SpeciesProfileModel#>
        SELECT ?predicate ?object
        WHERE { <$id> ?predicate ?object }";
        
        $result = $this->sparqlQuery($querystring);
        if(!$result) return array();
        
        // $taxa = array();
        // foreach($result as $line)
        // {
        //     if($id = $line['?taxon']) $taxa[] = $id;
        // }
        
        return $taxa;
    }
    
    function get_taxon($id)
    {
        $querystring = "
        PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
        PREFIX tc: <http://rs.tdwg.org/ontology/voc/TaxonConcept#>
        SELECT ?predicate ?object
        WHERE { <$id> ?predicate ?object }";
        
        $result = $this->sparqlQuery($querystring);
        if(!$result) return array();
        
        $taxa = array();
        foreach($result as $line)
        {
            if($id = $line['?taxon']) $taxa[] = $id;
        }
        
        return $taxa;
    }
    
    function sparqlQuery($querystring)
    {
        echo "<br><br><br><hr>$querystring<br>";
        
        $result = $this->model->sparqlQuery($querystring);
        SPARQLEngine::writeQueryResultAsHtmlTable($result);
        
        return $result;
    }
}

?>