<?php
namespace eol_schema;

class ContentArchiveBuilder
{
    private $core;
    private $extensions;
    private $tables;

    public function __construct($parameters = array())
    {
        if(isset($parameters['directory_path'])) $this->directory = $parameters['directory_path'];
        else $this->directory = self::unique_archive_directory_path();
        $this->file_handles = array();
        $this->file_classes = array();
        $this->file_columns = array();
        if(!file_exists($this->directory)) {
            echo "\nCreating this directory: [$this->directory]\n";
            mkdir($this->directory);
        }
    }

    // $parameters might be a single array of objects, or an array of arrays of objects
    public function create_archive_from_objects($parameters)
    {
        if(!is_array($parameters)) {
        	debug("Parameters must be an array");
        	return false;
        }
        foreach($parameters as $p)
        {
            if(is_array($p))
            {
                foreach($p as $object)
                {
                    if(get_parent_class($object) == __NAMESPACE__ . '\\DarwinCoreExtensionBase')
                    {
                        $this->write_object_to_file($object);
                    }
                }
            }else
            {
                if(get_parent_class($p) == __NAMESPACE__ . '\\DarwinCoreExtensionBase')
                {
                    $this->write_object_to_file($p);
                }
            }
        }
        $this->finalize();
    }

    public function finalize($compress = false)
    {
        $meta_xml_contents = "<?xml version=\"1.0\"?>\n";
        $meta_xml_contents .= "<archive xmlns=\"http://rs.tdwg.org/dwc/text/\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:schemaLocation=\"http://rs.tdwg.org/dwc/text/  http://services.eol.org/schema/dwca/tdwg_dwc_text.xsd\">\n";
        $table_definition_prefix = "<table encoding=\"UTF-8\" fieldsTerminatedBy=\"\\t\" linesTerminatedBy=\"\\n\" ignoreHeaderLines=\"1\"";

        foreach($this->file_handles as $file_name => $FILE)
        {
            fclose($FILE);
            $this->rebalance_tabs_and_add_header($file_name);
            unlink($this->directory . $file_name);
            $class = $this->file_classes[$file_name];
            $meta_xml_contents .= "  $table_definition_prefix rowType=\"". $class::ROW_TYPE ."\">\n";
            $meta_xml_contents .= "    <files>\n";
            $meta_xml_contents .= "      <location>". str_replace("_working.tab", ".tab", $file_name)."</location>\n";
            $meta_xml_contents .= "    </files>\n";
            foreach($this->file_columns[$class] as $index => $property)
            {
                $meta_xml_contents .= "    <field index=\"$index\" term=\"". $property['uri']."\"/>\n";
            }
            $meta_xml_contents .= "  </table>\n";
        }

        $meta_xml_contents .= "</archive>\n";
        if(!($META_FILE = fopen($this->directory . "meta.xml", 'w+')))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$this->directory . "meta.xml");
          return;
        }
        fwrite($META_FILE, $meta_xml_contents);
        fclose($META_FILE);

        if($compress)
        {
            $info = pathinfo($this->directory);
            $temporary_tarball_path = \php_active_record\temp_filepath();
            $final_tarball_path = $info['dirname'] ."/". $info['basename'] .".tar.gz";
            shell_exec("tar -czf $temporary_tarball_path --directory=". $info['dirname'] ."/". $info['basename'] ." .");
            @unlink($final_tarball_path);
            if(copy($temporary_tarball_path, $final_tarball_path))
              unlink($temporary_tarball_path);
        }
    }

    public function rebalance_tabs_and_add_header($file_name)
    {
        $class = $this->file_classes[$file_name];
        $count_columns = count($this->file_columns[$class]);
        $FILE = $this->get_file_handle($class, true);

        $column_headers = array();
        foreach($this->file_columns[$class] as $index => $property)
        {
            $column_headers[] = $property['name'];
        }
        fwrite($FILE, implode("\t", $column_headers) . "\n");

        if(!($WORKING_FILE = fopen($this->directory . $file_name, "r")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$this->directory . $file_name);
          return;
        }
        while(!feof($WORKING_FILE))
        {
            if($line = fgets($WORKING_FILE, 4096000)) //Eli on Mar 10, 2020 added 0 at the end. It was only 409600 before. Used in Wikipedia media object, which can be long.
            {
                if($line = rtrim($line, "\r\n"))
                {
                    // echo("\n[xx $line xx]\n"); //good debug
                    fwrite($FILE, $line);
                    $number_of_columns = substr_count($line, "\t");
                    fwrite($FILE, str_repeat("\t", $count_columns - $number_of_columns - 1));
                    fwrite($FILE, "\n");
                }
            }
        }
        fclose($WORKING_FILE);
        fclose($FILE);
    }

    public function write_object_to_file($object)
    {
        $object_class = get_class($object);
        $FILE = $this->get_file_handle($object_class);

        $this_line = "";
        $object_properties = $object->assigned_properties();
        $written_properties = array();
        // loop through the columns already in the file
        foreach($this->file_columns[$object_class] as $index => $property)
        {
            foreach($object_properties as $p)
            {
                if($p['property'] == $property)
                {
                    $this_line .= self::escape_string($p['value']);
                    $written_properties[] = $p['property'];
                }
            }
            $this_line .= "\t";
        }

        foreach($object_properties as $p)
        {
            if(in_array($p['property'], $written_properties)) continue;
            $this_line .= self::escape_string($p['value']) . "\t";
            $this->file_columns[$object_class][] = $p['property'];
        }

        if(substr($this_line, -1) == "\t") $this_line = substr($this_line, 0, -1);
        fwrite($FILE, $this_line . "\n");
    }

    private function get_file_handle($object_class, $final_version = false)
    {
        $class = preg_replace("/^.*\\\\/", "", $object_class);
        $file_name = self::to_underscore($class);
        if(!$final_version) $file_name .= "_working";
        $file_name .= ".tab";

        if($final_version)
        {
            if(!($FILE = fopen($this->directory . $file_name, 'w+')))
            {
              debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$this->directory . $file_name);
              return;
            }
        }else
        {
            if(isset($this->file_handles[$file_name])) return $this->file_handles[$file_name];
            if(!($FILE = fopen($this->directory . $file_name, 'w+')))
            {
              debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$this->directory . $file_name);
              return;
            }
            $this->file_handles[$file_name] = $FILE;
            $this->file_classes[$file_name] = $object_class;
            $this->file_columns[$object_class] = array();
        }
        return $FILE;
    }

    public static function escape_string($string)
    {
        $string = str_replace("\n", "\\n", $string);
        $string = str_replace("\r", "\\r", $string);
        $string = str_replace("\t", "\\t", $string);
        return $string;
    }

    private static function unique_archive_directory_path()
    {
        $root_directory = DOC_ROOT . "temp/";
        $random_bit = rand(10000, 99999);
        while(glob($root_directory . "archive_" . $random_bit))
        {
            $random_bit = rand(10000, 99999);
        }
        return $root_directory . "archive_" . $random_bit . "/";
    }

    private static function to_underscore($str)
    {
        $str = preg_replace('/([A-Z])/', '_' . strtolower('\\1'), $str);
        $str = preg_replace('/^_/', '', $str);
        $str = strtolower($str);
        return $str;
    }
}

?>
