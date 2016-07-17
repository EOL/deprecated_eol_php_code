<?php
namespace php_active_record;

class SolrAPI
{
    private $server;
    private $core;
    private $primary_key;
    private $schema_object;
    private $file_delimiter;
    private $multi_value_delimiter;
    private $csv_path;
    private $csv_bulk_path;
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
        $this->csv_bulk_path = temp_filepath(true);
        $this->multi_valued_fields_in_csv = array();
        $this->action_url = $this->server . $this->core;
        if(preg_match("/^(.*)\/$/", $this->action_url, $arr)) $this->action_url = $arr[1];

        $this->load_schema();
    }

    function __destruct()
    {
        clearstatcache();
        if(file_exists(DOC_ROOT . $this->csv_bulk_path) && filesize(DOC_ROOT . $this->csv_bulk_path)) $this->commit_objects_in_file();
        @unlink(DOC_ROOT . $this->csv_path);
        @unlink(DOC_ROOT . $this->csv_bulk_path);
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
        echo "GET SCHEMA FILE: ".file_get_contents($this->action_url . "/admin/file/?file=schema.xml");
        $response = simplexml_load_string(file_get_contents($this->action_url . "/admin/file/?file=schema.xml"));

		echo "\n --------- \n";
        // set primary key field name
        $this->primary_key = (string) $response->uniqueKey;

		echo "primary key is: ". $this->primary_key . "\n";
        // create empty object that maps to each field name; will be array if multivalued
        $this->schema_object = new \stdClass();
        foreach($response->fields->field as $field)
        {
            $field_name = (string) $field['name'];
            $multi_value = (string) @$field['multiValued'];

            if($multi_value) $this->schema_object->$field_name = array();
            else $this->schema_object->$field_name = '';
        }
    }

    public function convert_hash_to_query($parameter_hash)
    {
        $parameters = array($parameter_hash['query']);
        unset($parameter_hash['query']);
        foreach($parameter_hash as $key => $value) $parameters[] = "$key=$value";
        return implode("&", $parameters);
    }

    public function raw_query($query)
    {
        $url = $this->action_url."/select/?q={!lucene}".str_replace(" ", "%20", $query) ."&wt=json";
        // THIS IS NOT WINDOWS-COMPATIBLE:
        usleep(200000); // Units are millionths of a sec; this is 1/5th of a second.
        return json_decode(file_get_contents($url));
    }

    public function query($query)
    {
        $json = $this->raw_query($query);
        return $json->response;
    }

    public function logged_query($query)
    {
        debug("Solr query: $query\n");
        return $this->query($query);
    }

    public function get_results($query)
    {
        $objects = array();
        $response = $this->query($query);
        return $response->docs;
    }

    public function get_groups($parameter_hash)
    {
        $query = $this->convert_hash_to_query($parameter_hash);
        $response = $this->raw_query($query);
        return $response->grouped->{ $parameter_hash['group.field'] }->groups;
    }

    public function count_results($query)
    {
        $response = $this->query($query . "&rows=0");
        $total_results = $response->numFound;
        unset($response);
        return $total_results;
    }

    public function count_groups_by_hash($parameter_hash)
    {
        $query = $this->convert_hash_to_query($parameter_hash);
        $response = $this->raw_query($query . "&rows=0");
        $total_results = $response->grouped->{ $parameter_hash['group.field'] }->matches;
        unset($response);
        return $total_results;
    }

    public function commit()
    {
        if($GLOBALS['ENV_DEBUG']) echo("Solr commit $this->action_url\n");
        if(!$GLOBALS['ENV_DEBUG']) $extra_bit = " > /dev/null 2>/dev/null";
        $extra_bit = @$extra_bit ?: '';
		echo "inside commit action of solr \n";
		echo $this->action_url . "  and extra bit is: " . $extra_bit . "\n";
        exec("curl ". $this->action_url ."/update -F stream.url=".LOCAL_WEB_ROOT."applications/solr/commit.xml $extra_bit");
    }

    public function optimize()
    {
        if($GLOBALS['ENV_DEBUG']) echo("Solr optimize $this->action_url\n");
        if(!$GLOBALS['ENV_DEBUG']) $extra_bit = " > /dev/null 2>/dev/null";
        $extra_bit = @$extra_bit ?: '';
        exec("curl ". $this->action_url ."/update -F stream.url=".LOCAL_WEB_ROOT."applications/solr/optimize.xml $extra_bit");
    }

    public function delete_all_documents()
    {
        if($GLOBALS['ENV_DEBUG']) echo("Solr delete_all_documents $this->action_url\n");
        if(!$GLOBALS['ENV_DEBUG']) $extra_bit = " > /dev/null 2>/dev/null";
        $extra_bit = @$extra_bit ?: '';
        exec("curl ". $this->action_url ."/update -F stream.url=".LOCAL_WEB_ROOT."applications/solr/delete.xml $extra_bit");
        $this->commit();
        $this->optimize();
    }

    public function swap($from_core, $to_core)
    {
        if($GLOBALS['ENV_DEBUG']) echo("Solr swap $this->action_url\n");
        if(!$GLOBALS['ENV_DEBUG']) $extra_bit = " > /dev/null 2>/dev/null";
        $extra_bit = @$extra_bit ?: '';
        exec("curl ". $this->server ."admin/cores -F action=SWAP -F core=$from_core -F other=$to_core $extra_bit");
    }

    public function reload($core)
    {
        if($GLOBALS['ENV_DEBUG']) echo("Solr reload $this->action_url\n");
        if(!$GLOBALS['ENV_DEBUG']) $extra_bit = " > /dev/null 2>/dev/null";
        $extra_bit = @$extra_bit ?: '';
        exec("curl ". $this->server ."admin/cores -F action=RELOAD -F core=$core $extra_bit");
    }

    public function delete_by_ids($ids, $commit = true)
    {
        if(!$ids) return;
        @unlink(DOC_ROOT . $this->csv_path);
        if(!($OUT = fopen(DOC_ROOT . $this->csv_path, "w+")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$this->csv_path);
          return;
        }
        fwrite($OUT, "<delete>");
        foreach($ids as $id)
        {
            fwrite($OUT, "<id>$id</id>");
        }
        fwrite($OUT, "</delete>");
        fclose($OUT);

        if($GLOBALS['ENV_DEBUG']) echo("Solr delete $this->action_url\n");
        if(!$GLOBALS['ENV_DEBUG']) $extra_bit = " > /dev/null 2>/dev/null";
        $extra_bit = @$extra_bit ?: '';
        exec("curl ". $this->action_url ."/update -F stream.url=".LOCAL_WEB_ROOT."$this->csv_path $extra_bit");
        if($commit) $this->commit();
    }

    public function delete($query, $commit = true)
    {
        $this->delete_by_queries(array($query), $commit);
    }

    public function delete_by_queries($queries, $commit = true)
    {
        if(!$queries) return;
        @unlink(DOC_ROOT . $this->csv_path);
        if(!($OUT = fopen(DOC_ROOT . $this->csv_path, "w+")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$this->csv_path);
          return;
        }
        fwrite($OUT, "<delete>");
        foreach($queries as $query)
        {
            fwrite($OUT, "<query>$query</query>");
        }
        fwrite($OUT, "</delete>");
        fclose($OUT);

        if($GLOBALS['ENV_DEBUG']) echo("Solr delete $this->action_url\n");
        if(!$GLOBALS['ENV_DEBUG']) $extra_bit = " > /dev/null 2>/dev/null";
        $extra_bit = @$extra_bit ?: '';
        exec("curl ". $this->action_url ."/update -F stream.url=".LOCAL_WEB_ROOT."$this->csv_path $extra_bit");
        if($commit) $this->commit();
    }

    public function write_objects_to_file($objects)
    {
        clearstatcache();
        if(!($OUT = fopen(DOC_ROOT . $this->csv_bulk_path, "a+")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$this->csv_bulk_path);
          return;
        }
        $fields = array_keys(get_object_vars($this->schema_object));
		echo ("fields count is: " . count($fields) . "\n");
        if(!filesize(DOC_ROOT . $this->csv_bulk_path))
        {
            if($this->primary_key)
            {
            	echo "there is a primary key here \n";
                fwrite($OUT, $this->primary_key . $this->file_delimiter . implode($this->file_delimiter, $fields) . "\n");
				echo "write on the file the following: ". $this->primary_key . $this->file_delimiter . implode($this->file_delimiter, $fields) . "\n";
            }else
            {
            	echo "No primary index \n";
                fwrite($OUT, implode($this->file_delimiter, $fields) . "\n");
				echo "write on the file the following: ". implode($this->file_delimiter, $fields) ."\n";
            }
        }

		$debug_count = 0;
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
                        $this->multi_valued_fields_in_csv[$attr] = 1;
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
			if ($debug_count < 10){
				echo "write the followin object in the file: ". implode($this->file_delimiter, $this_attr) ."\n";
				$debug_count++;
			}
            fwrite($OUT, implode($this->file_delimiter, $this_attr) . "\n");
        }
        fclose($OUT);
    }

    public function commit_objects_in_file()
    {
        clearstatcache();
        if(!file_exists(DOC_ROOT . $this->csv_bulk_path) || !filesize(DOC_ROOT . $this->csv_bulk_path)){
        	echo "NO CSV FILE TO COMMIT OBJECTS FROM \n";	
        	return;
		}
        $curl = "curl ". $this->action_url ."/update/csv -F overwrite=true -F separator='". $this->file_delimiter ."'";
        foreach($this->multi_valued_fields_in_csv as $field => $bool)
        {
            $curl .= " -F f.$field.split=true -F f.$field.separator='". $this->multi_value_delimiter ."'";
        }
        $curl .= " -F stream.url=".LOCAL_WEB_ROOT."$this->csv_bulk_path -F stream.contentType='text/plain;charset=utf-8'";

        if($GLOBALS['ENV_DEBUG']) echo("Solr send_attributes $curl\n");
        if(!$GLOBALS['ENV_DEBUG']) $extra_bit = " > /dev/null 2>/dev/null";
        $extra_bit = @$extra_bit ?: '';
        exec($curl . $extra_bit);
        $this->commit();
        @unlink(DOC_ROOT . $this->csv_bulk_path);
        $this->multi_valued_fields_in_csv = array();
    }

    public function send_attributes($objects)
    {
        $this->write_objects_to_file($objects);
        $this->commit_objects_in_file();
    }

    public function send_attributes_in_bulk($objects)
    {
    	echo "csv file path is: " . DOC_ROOT . $this->csv_bulk_path . "\n";
        $this->write_objects_to_file($objects);
        debug("Solr attributes file size: ". filesize(DOC_ROOT . $this->csv_bulk_path));
        // 5 MB
        clearstatcache();
        if(filesize(DOC_ROOT . $this->csv_bulk_path) >= 5000000) $this->commit_objects_in_file();
    }

    public function send_from_mysql_result($outfile)
    {
        if(preg_match("/(tmp\/tmp_[0-9]{5}\.file$)/", $outfile, $arr))
        {
             $outfile_path = $arr[1];
             $fields = array_keys(get_object_vars($this->schema_object));
             $curl = "curl ". $this->action_url ."/update/csv -F separator='\t'";
             $curl .= " -F header=false -F fieldnames=".implode(",", $fields);
             $curl .= " -F stream.url=".LOCAL_WEB_ROOT."$outfile_path -F stream.contentType='text/plain;charset=utf-8'";

             if($GLOBALS['ENV_DEBUG']) echo("Solr send_from_mysql_result $this->action_url\n");
             if(!$GLOBALS['ENV_DEBUG']) $extra_bit = " > /dev/null 2>/dev/null";
             $extra_bit = @$extra_bit ?: '';
             exec($curl . $extra_bit);
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


    public static function text_filter(&$text, $is_date = false)
    {
        if($is_date)
        {
            if(!$text || $text == 'NULL') $text = 1;  // setting the default to 1969-12-31T07:00:01Z
            return self::mysql_date_to_solr_date($text);
        }
        if($text == 'NULL') return NULL;
        if(is_numeric($text)) return $text;
        if(preg_match("/^[a-zA-Z0-9 \(\),\.&-]+$/", $text)) return $text;
        if(!Functions::is_utf8($text)) return "";
        $text = str_replace(";", " ", $text);
        $text = str_replace("×", " ", $text);
        $text = str_replace("\"", " ", $text);
        $text = str_replace("'", " ", $text);
        $text = str_replace("|", "", $text);
        $text = str_replace("\n", "", $text);
        $text = str_replace("\r", "", $text);
        $text = str_replace("\t", "", $text);
        while(preg_match("/  /", $text)) $text = str_replace("  ", " ", $text);
        return trim($text);
    }

    public static function mysql_date_to_solr_date($mysql_date)
    {
        if(!$mysql_date) return null;
        return date('Y-m-d', strtotime($mysql_date)) . "T". date('h:i:s', strtotime($mysql_date)) ."Z";
    }
}

?>
