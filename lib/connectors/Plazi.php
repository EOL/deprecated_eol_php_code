<?php
namespace php_active_record;

class Plazi
{
    const DOCUMENT_LIST = "http://plazi.cs.umb.edu/exist/rest/db/taxonx_docs/list4EOL.xq?source=plazi";
    // http://plazi.cs.umb.edu/exist/rest/db/taxonx_docs/styles/taxonx2eol.xsl  <-- doc to quasi EOL format
    // http://www.taxonx.org/schema/v1/taxonx1.xsd <-- XSD for TaxonX
    
    
    
    
    
    public static function start($resource_file = null)
    {
        // $document_list_html = Functions::get_remote_file(self::DOCUMENT_LIST);
        $document_list_html = file_get_contents('plazii.html');
        if(preg_match_all("/<a href=\"((http:\/\/plazi.cs.umb.edu\/exist\/rest\/db\/taxonx_docs\/plazi\/(.*?))\?_xsl.*?)\">(.*)<\/a>/", $document_list_html, $matches, PREG_SET_ORDER))
        {
            $count = 0;
            foreach($matches as $match)
            {
                $xml_with_stylesheet_url = $match[1];
                $xml_url = $match[2];
                $xml_filename = $match[3];
                
                echo "$xml_with_stylesheet_url\n";
                self::get_metadata($xml_with_stylesheet_url);
                
                $xml = Functions::lookup_with_cache($xml_url, array('validation_regex' => 'xmlns:'));
                
                
                $count += 1;
                echo "\n   >>>>>>>> COUNT: $count >> ". time_elapsed() ."\n\n";
                // if($count >= 400) break;
            }
        }
    }
    
    public static function get_metadata($url)
    {
        $xml = Functions::lookup_with_cache($url, array('validation_regex' => 'xmlns:'));
        $simple_xml = simplexml_load_string($xml);
        $params = array();
        
        $dcterms = $simple_xml->children("http://dublincore.org/documents/dcmi-terms/");
        $dwc = $simple_xml->children("http://digir.net/schema/conceptual/darwin/2003/1.0");
        $params['source'] = (string) $dcterms->identifier;
        
        $data_object = $simple_xml->dataObject;
        $dcterms = $data_object->children("http://dublincore.org/documents/dcmi-terms/");
        $params['citation'] = (string) $dcterms->bibliographicCitation;
        $params['identifier'] = (string) $dcterms->identifier;
        
        $params['data_type'] = "http://purl.org/dc/dcmitype/Text";
        $params['mime_type'] = "text/html";
        $params['license'] = "not applicable";
        
        $params['agents'] = array();
        foreach($data_object->agent as $agent)
        {
            $agent_name = (string) $agent;
            $attr = $agent->attributes();
            $agent_role = (string) @$attr['role'];
            $params['agents'][] = array($agent_name, $agent_role);
        }
        
        
        print_r($xml);
        // print_r($params);
        echo "\n\n\n";
    }
}

?>