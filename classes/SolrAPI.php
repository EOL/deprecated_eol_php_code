<?php

class SolrAPI
{
    private $server;
    private $primary_key;
    private $schema;
    private $file_delimiter;
    private $multi_value_delimiter;
    
    public function __construct($s = SOLR_SERVER, $pk = PRIMARY_KEY, $schema = array(), $d = SOLR_FILE_DELIMITER, $mv = SOLR_MULTI_VALUE_DELIMETER)
    {
        $this->server = $s;
        $this->primary_key = $pk;
        $this->schema = $schema;
        $this->file_delimiter = $d;
        $this->multi_value_delimiter = $mv;
        $this->csv_path = Functions::temp_filepath(true);
        
        $this->schema_object = new stdClass();
        foreach($this->schema as $attr => $val)
        {
            $this->schema_object->$attr = $val;
        }
    }
    
    public function query($query)
    {
        $response = simplexml_load_string(file_get_contents($this->server . "/select/?q=". str_replace(" ", "%20", $query)));
        return @$response->result;
    }
    
    public function get_results(&$query)
    {
        $objects = array();
        
        $response = simplexml_load_string(file_get_contents($this->server . "/select/?q=". str_replace(" ", "%20", $query)));
        $count = count($response->result->doc);
        for($i=0 ; $i<$count ; $i++)
        {
            $objects[] = $this->doc_to_object($response->result->doc[$i]);
        }
        
        return $objects;
    }
    
    public function commit()
    {
        exec("curl ". $this->server ."/update -F stream.url=".LOCAL_WEB_ROOT."applications/solr/commit.xml");
    }
    
    public function optimize()
    {
        exec("curl ". $this->server ."/update -F stream.url=".LOCAL_WEB_ROOT."applications/solr/optimize.xml");
    }
    
    public function send_attributes($objects, $object_fields)
    {
        @unlink(LOCAL_ROOT . $this->csv_path);
        $OUT = fopen(LOCAL_ROOT . $this->csv_path, "w+");
        
        $fields = array_keys($object_fields);
        fwrite($OUT, $this->primary_key . $this->file_delimiter . implode($this->file_delimiter, $fields) . "\n");
        
        $multi_values = array();
        
        foreach($objects as $primary_key => $attributes)
        {
            $this_attr = array();
            $this_attr[] = $primary_key;
            foreach($fields as $attr)
            {
                // this object has this attribute
                if(isset($attributes[$attr]))
                {
                    // the attribute is multi-valued
                    if(is_array($attributes[$attr]))
                    {
                        $multi_values[$attr] = 1;
                        $values = array_map(array('SolrAPI','text_filter'), array_keys($attributes[$attr]));
                        $this_attr[] = implode($this->multi_value_delimiter, $values);
                    }else
                    {
                        $this_attr[] = SolrAPI::text_filter($attributes[$attr]);
                    }
                }
                // default value is empty string
                else $this_attr[] = "";
            }
            fwrite($OUT, implode($this->file_delimiter, $this_attr) . "\n");
        }
        fclose($OUT);
        
        
        
        
        $curl = "curl ". $this->server ."/update/csv -F separator='". $this->file_delimiter ."'";
        foreach($multi_values as $field => $bool)
        {
            $curl .= " -F f.$field.split=true -F f.$field.separator='". $this->multi_value_delimiter ."'";
        }
        $curl .= " -F stream.url=".LOCAL_WEB_ROOT."$this->csv_path -F stream.contentType=text/plain;charset=utf-8 -F overwrite=true";
        
        echo "calling: $curl\n";
        exec($curl);
        $this->commit();
    }
    
    private function doc_to_object($doc)
    {
        $object = clone $this->schema_object;
        $count = count($doc->arr);
        for($i=0 ; $i<$count ; $i++)
        {
            $attr = $doc->arr[$i];
            if(isset($attr->str)) $value = (string) $attr->str;
            else $value = (int) $attr->int;
            $name = (string) $attr['name'];
            
            if(isset($this->schema[$name]) && is_array($this->schema[$name])) array_push($object->$name, $value);
            else $object->$name = $value;
        }
        
        return $object;
    }
    
    
    private function text_filter($text)
    {
        if(!Functions::is_utf8($text)) return "";
        $text = str_replace(";", " ", $text);
        $text = str_replace("×", " ", $text);
        $text = str_replace("\"", " ", $text);
        $text = str_replace("'", " ", $text);
        $text = str_replace("|", "", $text);
        $text = str_replace("\n", "", $text);
        $text = str_replace("\r", "", $text);
        $text = str_replace("\t", "", $text);
        $text = Functions::utf8_to_ascii($text);
        while(preg_match("/  /", $text)) $text = str_replace("  ", " ", $text);
        return trim($text);
    }
}

?>