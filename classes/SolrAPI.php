<?php

class SolrAPI
{
    public static function query($query)
    {
        $response = Functions::get_hashed_response(SOLR_SERVER . "/select/?q=". str_replace(" ", "%20", $query), 0);
        return @$response->result;
    }

}

?>