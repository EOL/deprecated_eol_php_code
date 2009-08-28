<?php

class SPMInfoItem
{
    public $class;
    public $hasContent;
    
    function __construct($class, $hasContent)
    {
        $this->url = $url;
        $this->model = ModelFactory::getDefaultModel();
        $this->model->load($url);
    }
}

?>