<?php
namespace php_active_record;

class DataObjectsTaxonConcept extends ActiveRecord
{
    public static $belongs_to = array(
            array('data_object'),
            array('taxon_concept')
        );
}

?>