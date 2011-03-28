<?php

class SolrAPI
{
    private $server;
    private $core;
    private $primary_key;
    private $schema_object;
    private $file_delimiter;
    private $multi_value_delimiter;
    private $csv_path;
    private $action_url;
    
    public function __construct($s = SOLR_SERVER, $core = '', $d = SOLR_FILE_DELIMITER, $mv = SOLR_MULTI_VALUE_DELIMETER)
    {
        $this->server = trim($s);
        if(!preg_match("/\/$/", $this->server)) $this->server .= "/";
        $this->core = $core;
        if(preg_match("/^(.*)\/$/", $this->core, $arr)) $this->core = $arr[1];
        $this->file_delimiter = $d;
        $this->multi_value_delimiter = $mv;
        
        $this->csv_path = temp_filepath(true);
        $this->action_url = $this->server . $this->core;
        if(preg_match("/^(.*)\/$/", $this->action_url, $arr)) $this->action_url = $arr[1];
        
        $this->load_schema();
    }
    
    function __destruct()
    {
        @unlink(DOC_ROOT . $this->csv_path);
    }
    
    public static function ping($s = SOLR_SERVER, $c = '')
    {
        $server = trim($s);
        if(!preg_match("/\/$/", $server)) $server .= "/";
        $core = $c;
        if(preg_match("/^(.*)\/$/", $core, $arr)) $core = $arr[1];
        $action_url = $server . $core;
        if(preg_match("/^(.*)\/$/", $action_url, $arr)) $action_url = $arr[1];
        $schema = @file_get_contents($action_url . "/admin/file/?file=schema.xml");
        if($schema) return true;
        return false;
    }
    
    private function load_schema()
    {
        // load schema XML
        $response = simplexml_load_string(file_get_contents($this->action_url . "/admin/file/?file=schema.xml"));
        
        // set primary key field name
        $this->primary_key = (string) $response->uniqueKey;
        
        // create empty object that maps to each field name; will be array if multivalued
        $this->schema_object = new stdClass();
        foreach($response->fields->field as $field)
        {
            $field_name = (string) $field['name'];
            $multi_value = (string) @$field['multiValued'];
            
            if($multi_value) $this->schema_object->$field_name = array();
            else $this->schema_object->$field_name = '';
        }
    }
    
    /* Sample SOLR query JSON response
      {
          "responseHeader": {
              "status":0,
              "QTime":929,
              "params": {
                  "sort":"confidence desc,
                   visibility_id_1 desc",
                  "start":"10000",
                  "q":" { !lucene } (hierarchy_id_1:105 OR hierarchy_id_2:147) AND same_concept:false",
                  "wt":"json",
                  "rows":"2"
              }
          },
          "response": {
              "numFound":1104982,
              "start":10000,
              "docs": { }
          }
      } */
    public function query($query)
    {
        $json = json_decode(file_get_contents($this->action_url."/select/?q={!lucene}".str_replace(" ", "%20", $query) ."&wt=json"));
        return $json->response;
    }
    
    public function get_results($query)
    {
        $objects = array();
        $response = $this->query($query);
        return $response->docs;
    }
    
    public function commit()
    {
        exec("curl ". $this->action_url ."/update -F stream.url=".LOCAL_WEB_ROOT."applications/solr/commit.xml");
    }
    
    public function optimize()
    {
        exec("curl ". $this->action_url ."/update -F stream.url=".LOCAL_WEB_ROOT."applications/solr/optimize.xml");
    }
    
    public function delete_all_documents()
    {
        exec("curl ". $this->action_url ."/update -F stream.url=".LOCAL_WEB_ROOT."applications/solr/delete.xml");
        $this->commit();
        $this->optimize();
    }
    
    public function swap($from_core, $to_core)
    {
        exec("curl ". $this->server ."admin/cores -F action=SWAP -F core=$from_core -F other=$to_core");
    }
    
    public function reload($core)
    {
        exec("curl ". $this->server ."admin/cores -F action=RELOAD -F core=$core");
    }
    
    public function delete($query)
    {
        @unlink(DOC_ROOT . $this->csv_path);
        $OUT = fopen(DOC_ROOT . $this->csv_path, "w+");
        fwrite($OUT, "<delete><query>$query</query></delete>");
        fclose($OUT);
        
        exec("curl ". $this->action_url ."/update -F stream.url=".LOCAL_WEB_ROOT."$this->csv_path");
        $this->commit();
    }
    
    public function send_attributes($objects)
    {
        @unlink(DOC_ROOT . $this->csv_path);
        $OUT = fopen(DOC_ROOT . $this->csv_path, "w+");
        
        $fields = array_keys(get_object_vars($this->schema_object));
        if($this->primary_key)
        {
            fwrite($OUT, $this->primary_key . $this->file_delimiter . implode($this->file_delimiter, $fields) . "\n");
        }else
        {
            fwrite($OUT, implode($this->file_delimiter, $fields) . "\n");
        }
        $multi_values = array();
        
        foreach($objects as $primary_key => $attributes)
        {
            $this_attr = array();
            if($this->primary_key) $this_attr[] = $primary_key;
            foreach($fields as $attr)
            {
                // this object has this attribute
                if(isset($attributes[$attr]))
                {
                    // the attribute is multi-valued
                    if(is_array($attributes[$attr]))
                    {
                        $multi_values[$attr] = 1;
                        $values = array_keys($attributes[$attr]);
                        $this_attr[] = implode($this->multi_value_delimiter, $values);
                    }else
                    {
                        $this_attr[] = $attributes[$attr];
                    }
                }
                // default value is empty string
                else $this_attr[] = "";
            }
            fwrite($OUT, implode($this->file_delimiter, $this_attr) . "\n");
        }
        fclose($OUT);
        
        
        
        
        $curl = "curl ". $this->action_url ."/update/csv -F overwrite=true -F separator='". $this->file_delimiter ."'";
        foreach($multi_values as $field => $bool)
        {
            $curl .= " -F f.$field.split=true -F f.$field.separator='". $this->multi_value_delimiter ."'";
        }
        $curl .= " -F stream.url=".LOCAL_WEB_ROOT."$this->csv_path -F stream.contentType=text/plain;charset=utf-8";
        
        echo "calling: $curl\n";
        exec($curl);
        $this->commit();
    }
    
    public function send_from_mysql_result($outfile)
    {
        if(preg_match("/(tmp\/tmp_[0-9]{5}\.file$)/", $outfile, $arr))
        {
             $outfile_path = $arr[1];
             $fields = array_keys(get_object_vars($this->schema_object));
             $curl = "curl ". $this->action_url ."/update/csv -F separator='\t'";
             $curl .= " -F header=false -F fieldnames=".implode(",", $fields);
             $curl .= " -F stream.url=".LOCAL_WEB_ROOT."$outfile_path -F stream.contentType=text/plain;charset=utf-8";
             
             echo "calling: $curl\n";
             exec($curl);
             $this->commit();
        }
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
            
            if(isset($object->$name) && is_array($object->$name)) array_push($object->$name, $value);
            else $object->$name = $value;
        }
        
        return $object;
    }
    
    
    public static function text_filter($text, $convert_to_ascii = false)
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
        if($convert_to_ascii) $text = Functions::utf8_to_ascii($text);
        while(preg_match("/  /", $text)) $text = str_replace("  ", " ", $text);
        return trim($text);
    }
}

?>