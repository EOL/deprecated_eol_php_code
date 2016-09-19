<?php
namespace php_active_record;

class SparqlClient
{
    const BASIC_URI_REGEX       = "/^http:\/\/[^ <>]+$/i";
    const ENCLOSED_URI_REGEX    = "/^<(http:\/\/[^ <>]+)>$/i";
    const NAMESPACED_URI_REGEX  = "/^([a-z0-9_-]{1,30}):([a-z0-9_-]+)$/i";

    function __construct($options = array())
    {
        $this->endpoint_uri = $options['endpoint_uri'];
        $this->username     = $options['username'];
        $this->password     = $options['password'];
        $this->upload_uri   = $options['upload_uri'];
        $this->data_waiting_for_insert = array();
    }

    function __destruct()
    {
        $this->insert_remaining_bulk_data();
    }

    public static function namespaces()
    {
        static $namespaces = array();
        if(!$namespaces)
        {
            $namespaces = array(
                'eol'           => 'http://eol.org/schema/',
                'eolterms'      => 'http://eol.org/schema/terms/',
                'eolreference'  => 'http://eol.org/schema/reference/',
                'dwc'           => 'http://rs.tdwg.org/dwc/terms/',
                'dwct'          => 'http://rs.tdwg.org/dwc/dwctype/',
                'dc'            => 'http://purl.org/dc/terms/',
                'rdf'           => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
                'rdfs'          => 'http://www.w3.org/2000/01/rdf-schema#',
                'foaf'          => 'http://xmlns.com/foaf/0.1/',
                'obis'          => 'http://iobis.org/schema/terms/',
                'owl'           => 'http://www.w3.org/2002/07/owl#',
                'anage'         => 'http://anage.org/schema/terms/');
        }
        return $namespaces;
    }

    public static function connection()
    {
        return new SparqlClient(array(
            'endpoint_uri'  => SPARQL_ENDPOINT,
            'upload_uri'    => SPARQL_UPLOAD_ENDPOINT,
            'username'      => SPARQL_USERNAME,
            'password'      => SPARQL_PASSWORD));
    }

    public static function to_underscore($string)
    {
        $string = str_replace(' ', '_', strtolower($string));
        $string = str_replace('`', '', $string);
        return self::convert($string);
    }

    public static function is_uri($string)
    {
        if(preg_match(self::BASIC_URI_REGEX, $string)) return true;
        if(preg_match(self::ENCLOSED_URI_REGEX, $string)) return true;
        if(preg_match(self::NAMESPACED_URI_REGEX, $string)) return true;
        return false;
    }

    public static function enclose_value($value)
    {
        if(preg_match(self::BASIC_URI_REGEX, $value)) return "<$value>";
        if(preg_match(self::ENCLOSED_URI_REGEX, $value)) return $value;
        if(preg_match(self::NAMESPACED_URI_REGEX, $value)) return $value;
        $value = str_replace("\\", "\\\\", $value);
        $value = str_replace("|", " ", $value);
        $value = \eol_schema\ContentArchiveBuilder::escape_string($value);
        return "\"". str_replace("\"","\\\"", $value) ."\"";
    }

    // # Puts URIs in <brackets>, dereferences namespaces, and quotes literals.
    public static function expand_namespaces($value)
    {
        $namespaces = self::namespaces();
        if(preg_match(self::BASIC_URI_REGEX, $value)) return $value;
        if(preg_match(self::ENCLOSED_URI_REGEX, $value, $arr)) return $arr[1];
        if(preg_match(self::NAMESPACED_URI_REGEX, $value, $arr))
        {
            if(isset($namespaces[$arr[1]])) return $namespaces[$arr[1]] . $arr[2];
            else return false;
        }
        return $value;
    }

    public static function convert($string)
    {
        $string = str_ireplace("&", "&amp;", $string);
        $string = str_ireplace("<", "&lt;", $string);
        $string = str_ireplace(">", "&gt;", $string);
        $string = str_ireplace("'", "&apos;", $string);
        $string = str_ireplace("\"", "&quot;", $string);
        $string = str_ireplace("\\", "", $string);
        $string = str_ireplace("\n", "", $string);
        $string = str_ireplace("\r", "", $string);
        return $string;
    }

    public static function append_namespaces_to_query($query = '')
    {
        foreach(self::namespaces() as $namespace => $uri)
        {
            $query = "PREFIX $namespace: <$uri>\n" . $query;
        }
        return $query;
    }

    // NOTE that a consequence of this is that when we're inserting data in bulk, we *could* have an error in one "block" of data but not the others, and
    // end up with a strangely incomplete set of data (for example, 10,000 lines of data, but lines 2,000-4,000 are missing because of *1* line of errors).
    public function insert_data_in_bulk($options = array())
    {
        if(!isset($this->data_waiting_for_insert[$options['graph_name']])) $this->data_waiting_for_insert[$options['graph_name']] = array();
        $this->data_waiting_for_insert[$options['graph_name']] = array_merge($this->data_waiting_for_insert[$options['graph_name']], $options['data']);
        if(count($this->data_waiting_for_insert[$options['graph_name']]) >= 2000)
        {
            $this->insert_data(array('graph_name' => $options['graph_name'], 'data' => $this->data_waiting_for_insert[$options['graph_name']]));
            $this->data_waiting_for_insert[$options['graph_name']] = array();
        }
    }

    public function insert_remaining_bulk_data()
    {
        foreach($this->data_waiting_for_insert as $graph_name => $data)
        {
            $this->insert_data(array('graph_name' => $graph_name, 'data' => $data));
        }
        $this->data_waiting_for_insert = array();
    }

    # Virtuoso data is getting posted to upload_uri
    # see http://virtuoso.openlinksw.com/dataspace/doc/dav/wiki/Main/VirtRDFInsert#HTTP POST Example 1
    public function insert_data($options = array())
    {
        if($options['data'])
        {
            $query = self::append_namespaces_to_query();
            $query .= " INSERT DATA INTO <". $options['graph_name'] ."> { ". implode($options['data'], " .\n") ." }";

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->upload_uri);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/sparql-query'));
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_FAILONERROR, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, $this->username .":". $this->password);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $query);

            $result = curl_exec($ch);
            if(curl_errno($ch) == 0)
            {
                curl_close($ch);
                return $result;
            }
            echo "\n\n=========================================\n";
            echo 'Curl error: ' . curl_error($ch) . "\n\n";
            echo "$query\n\n";
            print_r($options);
            print_r(serialize($options['data']));
            echo "===========================================\n\n";
            write_to_resource_harvesting_log("ERROR: Inserting data in virtuoso");
            write_to_resource_harvesting_log('Curl error: ' . curl_error($ch));
            write_to_resource_harvesting_log($query);
            return false;
        }
    }

    public function delete_data($options)
    {
        if($options['graph_name'] && $options['data'])
        {
            $this->update("DELETE DATA { GRAPH <". $options['graph_name'] ."> { ". $options['data'] ." } }");
        }
    }

    public function delete_uri($options)
    {
        if($options['graph_name'] && $options['uri'])
        {
            $this->update("DELETE FROM <". $options['graph_name'] ."> { <". $options['uri'] ."> ?p ?o } WHERE { <". $options['uri'] ."> ?p ?o }");
        }
    }
	
	public function update_taxon_given_trait($traits, $predicates, $new_page, $old_page)
	{
		$default_graph = "<http://eol.org/traitbank>";
		$default_page_uri = "<http://eol.org/pages/";
		
		//delete query
		$delete_query = "WITH GRAPH " . $default_graph . "
			DELETE { ";
		for ($i=0; $i < count($traits); $i++) {
			$delete_query = $delete_query . $default_page_uri . $old_page . "> <" . $predicates[$i] . "> <" . $traits[$i] . ">.";
		}
		$delete_query = $delete_query . "}";
		echo "DELETE QUERY IS: " . $delete_query . "\n";
		$this->update($delete_query);
		
		$insert_query = "WITH GRAPH " . $default_graph . "
			INSERT { ";
			
		for ($i=0; $i < count($traits); $i++) {
			$insert_query = $insert_query . $default_page_uri . $new_page . "> <" . $predicates[$i] . "> <" . $traits[$i] . ">.";
		}
		$insert_query = $insert_query . "}";
		echo "Insert query is: " . $insert_query . "\n";
		$this->update($insert_query);
	}
	
	public function get_traits_from_virtuoso($old_page, $resource_number){
		echo "get traits from virtuoso \n";
		$default_graph = "<http://eol.org/traitbank>";
		$default_page_uri = "<http://eol.org/pages/";
		$default_resource_page = "<http://eol.org/resources/";
		$query = " WITH GRAPH " . $default_graph . "
			SELECT ?trait ?predicate
			WHERE { " . $default_page_uri . $old_page . "> ?predicate ?trait .
				?trait ?trait_resource " . $default_resource_page . $resource_number . "> }";
		echo "query is: ".$query."\n";
		return $this->query_for_select($query);
	}

    public function update($query, $options = array())
    {
        return $this->query($query, $options);
    }

    public function delete_graph($graph_name)
    {
        if(!$graph_name) return;
        $this->update("CLEAR GRAPH <$graph_name>", array('log_enable' => true));
        $this->update("DROP SILENT GRAPH <$graph_name>");
    }

	public function append_namespaces_adjust_endpoint($query, $options = array()){
		$query = self::append_namespaces_to_query($query);
        if(isset($options['log_enable']))
        {
            $query = "DEFINE sql:log-enable 3 ". $query;
        }
        return $this->endpoint_uri . "?format=application/json&query=" . urlencode($query);
	}
	
    public function query($query, $options = array())
    {
        $query_url = $this->append_namespaces_adjust_endpoint($query, $options);
        $decoded_response = json_decode(Functions::get_remote_file($query_url, array('timeout' => 900))); // 15 minutes
        return $decoded_response->results->bindings;
    }
	
	public function query_for_select($query, $options = array()){
		$query_url = $this->append_namespaces_adjust_endpoint($query, $options);
		 $decoded_response = json_decode(Functions::get_remote_file($query_url, array('timeout' => 900)), true); // 15 minutes
		 return $decoded_response["results"]["bindings"];
	}

    public function post_query($query, $options = array())
    {
        $query = self::append_namespaces_to_query($query);
        $decoded_response = json_decode(Functions::curl_post_request($this->endpoint_uri, array('format' => 'application/json', 'query' => $query)));
        return $decoded_response->results->bindings;
    }
    
}

?>
