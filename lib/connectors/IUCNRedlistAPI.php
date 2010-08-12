<?php

class IUCNRedlistAPI
{
    const API_PREFIX = "http://redlist-web-2.gogoego.com/apps/redlist/eol/22823/";
    
    public static function get_taxon_xml()
    {
        $taxa = self::get_all_taxa();
        $xml = SchemaDocument::get_taxon_xml($taxa);
        return $xml;
    }
    
    public static function get_all_taxa()
    {
        $all_taxa = array();
        $per_page = 100;
        
        // if($response)
        // {
            $total_taxa = 1;
            
            // number of API calls to be made
            $total_pages = ceil($total_taxa / 100);
            
            $taxa = array();
            for($i=1 ; $i<=$total_pages ; $i++)
            {
                $page_taxa = self::get_taxa_from_page($i);
                
                if($page_taxa)
                {
                    foreach($page_taxa as $t) $all_taxa[] = $t;
                }
            }
        // }
        return $all_taxa;
    }
    
    public static function get_taxa_from_page($page)
    {
        echo self::API_PREFIX.$page;
        $json = Functions::get_remote_file(self::API_PREFIX.$page);
        echo $json;
        $json_object = json_decode($json);
        print_r($json_object);
    }
}

?>