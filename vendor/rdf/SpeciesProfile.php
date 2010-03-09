<?php

class SpeciesProfile
{
    public $class;
    public $hasContent;
    
    function __construct($array)
    {
        $this->url = $url;
        $this->model = ModelFactory::getDefaultModel();
        $this->model->load($url);
    }
}

?>