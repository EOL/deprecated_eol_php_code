<?php
namespace php_active_record;

class SiteSearchIndexer
{
    private $mysqli;
    private $mysqli_slave;
    private $solr;
    private $objects;
    private $solr_server;
    
    public function __construct($solr_server = SOLR_SERVER)
    {
        $this->mysqli =& $GLOBALS['db_connection'];
        if($GLOBALS['ENV_NAME'] == 'production' && environment_defined('slave')) $this->mysqli_slave = load_mysql_environment('slave');
        else $this->mysqli_slave =& $this->mysqli;
        $this->solr_server = $solr_server;
    }
    
    public function index($optimize = true)
    {
        $this->solr = new SolrAPI($this->solr_server, 'site_search');
        // $this->index_taxa();
        // $this->index_data_objects();
        if($optimize) $this->solr->optimize();
    }
    
    public function index_data_objects()
    {
        return;
        // $this->solr->delete('resource_type:DataObject');
        
        $start = 0;
        $max_id = 0;
        $limit = 100000;
        
        $result = $this->mysqli_slave->query("SELECT MIN(id) as min, MAX(id) as max FROM data_objects");
        if($result && $row=$result->fetch_assoc())
        {
            $start = $row["min"];
            $max_id = $row["max"];
        }
        $start = 11000012;
        for($i=$start ; $i<$max_id ; $i+=$limit)
        {
            unset($this->objects);
            echo "Looking up $i : $limit .. max: $max_id .. Time: ". time_elapsed()." .. Mem: ". memory_get_usage() ."\n";
            $this->lookup_objects($i, $limit);
            echo "Looked up $i : $limit Time: ". time_elapsed()." .. Mem: ". memory_get_usage() ."\n";
            
            if(isset($this->objects)) $this->solr->send_attributes($this->objects);
        }
        
        $this->solr->commit();
    }
    
    public function index_taxa()
    {
        return;
        $this->solr->delete('resource_type:TaxonConcept');
        
        $this->lookup_and_cache_language_iso_codes();
        $start = 0;
        $max_id = 0;
        $limit = 100000;
        
        $result = $this->mysqli_slave->query("SELECT MIN(id) as min, MAX(id) as max FROM taxon_concepts tc");
        if($result && $row=$result->fetch_assoc())
        {
            $start = $row["min"];
            $max_id = $row["max"];
        }
        
        for($i=$start ; $i<$max_id ; $i+=$limit)
        {
            unset($this->objects);
            echo "Looking up $i : $limit .. max: $max_id .. Time: ". time_elapsed()." .. Mem: ". memory_get_usage() ."\n";
            $this->lookup_names($i, $limit);
            // $this->lookup_top_images($i, $limit);
            $this->lookup_ancestors($i, $limit);
            $this->lookup_richness($i, $limit);
            echo "Looked up $i : $limit Time: ". time_elapsed()." .. Mem: ". memory_get_usage() ."\n";
            
            if(isset($this->objects)) $this->send_objects_to_solr();
        }
        
        $this->solr->commit();
    }
    
    public function send_objects_to_solr()
    {
        $objects_to_send = array();
        foreach($this->objects as $id => $arr)
        {
            $ancestor_ids = @$arr['ancestor_taxon_concept_id'];
            $top_image_id = @$arr['top_image_id'];
            $richness_score = @$arr['richness_score'];
            if(isset($arr['preferred_scientifics']))
            {
                $objects_to_send[] = array(
                    'resource_type'             => 'TaxonConcept',
                    'resource_id'               => $id,
                    'resource_unique_key'       => "TaxonConcept_$id",
                    'keyword_type'              => 'PreferredScientific',
                    'keyword'                   => $arr['preferred_scientifics'],
                    'ancestor_taxon_concept_id' => $ancestor_ids,
                    'richness_score'            => 0,
                    'language'                  => 'sci',
                    'top_image_id'              => $top_image_id,
                    'richness_score'            => $richness_score);
            }
            if(isset($arr['synonyms']))
            {
                $objects_to_send[] = array(
                    'resource_type'             => 'TaxonConcept',
                    'resource_id'               => $id,
                    'resource_unique_key'       => "TaxonConcept_$id",
                    'keyword_type'              => 'Synonym',
                    'keyword'                   => $arr['synonyms'],
                    'ancestor_taxon_concept_id' => $ancestor_ids,
                    'richness_score'            => 0,
                    'language'                  => 'sci',
                    'top_image_id'              => $top_image_id,
                    'richness_score'            => $richness_score);
            }
            if(isset($arr['surrogates']))
            {
                $objects_to_send[] = array(
                    'resource_type'             => 'TaxonConcept',
                    'resource_id'               => $id,
                    'resource_unique_key'       => "TaxonConcept_$id",
                    'keyword_type'              => 'Surrogate',
                    'keyword'                   => $arr['surrogates'],
                    'ancestor_taxon_concept_id' => $ancestor_ids,
                    'richness_score'            => 0,
                    'language'                  => 'sci',
                    'top_image_id'              => $top_image_id,
                    'richness_score'            => $richness_score);
            }
            if(isset($arr['preferred_commons']))
            {
                foreach($arr['preferred_commons'] as $language_iso => $names)
                {
                    $objects_to_send[] = array(
                        'resource_type'             => 'TaxonConcept',
                        'resource_id'               => $id,
                        'resource_unique_key'       => "TaxonConcept_$id",
                        'keyword_type'              => 'PreferredCommonName',
                        'keyword'                   => $names,
                        'ancestor_taxon_concept_id' => $ancestor_ids,
                        'richness_score'            => 0,
                        'language'                  => $language_iso,
                        'top_image_id'              => $top_image_id,
                        'richness_score'            => $richness_score);
                }
            }
            if(isset($arr['commons']))
            {
                foreach($arr['commons'] as $language_iso => $names)
                {
                    $objects_to_send[] = array(
                        'resource_type'             => 'TaxonConcept',
                        'resource_id'               => $id,
                        'resource_unique_key'       => "TaxonConcept_$id",
                        'keyword_type'              => 'CommonName',
                        'keyword'                   => $names,
                        'ancestor_taxon_concept_id' => $ancestor_ids,
                        'richness_score'            => 0,
                        'language'                  => $language_iso,
                        'top_image_id'              => $top_image_id,
                        'richness_score'            => $richness_score);
                }
            }
            unset($this->objects[$id]);
        }
        // print_r($objects_to_send);
        echo "Time: ". time_elapsed()." .. Mem: ". memory_get_usage() ."\n";
        $this->solr->send_attributes($objects_to_send);
    }
    
    function lookup_names($start, $limit, $filter = "1=1", &$taxon_concept_ids = array())
    {
        echo "\nquerying names\n";
        $query = "SELECT tc.id, tc.vetted_id, tcn.preferred, tcn.vern, tcn.language_id, tcn.source_hierarchy_entry_id, n.string FROM taxon_concepts tc LEFT JOIN (taxon_concept_names tcn JOIN names n ON  (tcn.name_id=n.id)) ON (tc.id=tcn.taxon_concept_id) WHERE tc.supercedure_id=0 AND tc.published=1 AND tc.id ";
        if($taxon_concept_ids) $query .= "IN (". implode(",", $taxon_concept_ids) .")";
        else $query .= "BETWEEN $start AND ". ($start+$limit);
        
        $preferred_scientifics = array();
        $synonyms = array();
        $preferred_commons = array();
        $commons = array();
        $surrogates = array();
        $began = false;
        foreach($this->mysqli_slave->iterate_file($query) as $row_num => $row)
        {
            $id = $row[0];
            $vetted_id = $row[1];
            $preferred = $row[2];
            $vern = $row[3];
            $language_id = $row[4];
            $source_hierarchy_entry_id = $row[5];
            $string = $row[6];
            
            if(!$began) echo "done querying\n";
            $began = true;
            
            if(!$string) continue;
            
            if($vern)
            {
                $language_iso = @$GLOBALS['language_iso_codes'][$language_id] ?: 'unknown';
                if($preferred && $language_iso != 'unknown')
                {
                    if(isset($this->objects[$id]['preferred_commons'][$language_iso][$string])) continue;
                    if($name = SolrApi::text_filter($string)) $this->objects[$id]['preferred_commons'][$language_iso][$name] = 1;
                }else
                {
                    if(isset($this->objects[$id]['commons'][$language_iso][$string])) continue;
                    if($name = SolrApi::text_filter($string)) $this->objects[$id]['commons'][$language_iso][$name] = 1;
                }
            }elseif($source_hierarchy_entry_id)
            {
                if(Name::is_surrogate($string))
                {
                    if(isset($this->objects[$id]['surrogates'][$string])) continue;
                    if($name = SolrApi::text_filter($string)) $this->objects[$id]['surrogates'][$name] = 1;
                }elseif($preferred)
                {
                    if(isset($this->objects[$id]['preferred_scientifics'][$string])) continue;
                    if($name = SolrApi::text_filter($string)) $this->objects[$id]['preferred_scientifics'][$name] = 1;
                }else
                {
                    if(isset($this->objects[$id]['synonyms'][$string])) continue;
                    if($name = SolrApi::text_filter($string)) $this->objects[$id]['synonyms'][$name] = 1;
                }
            }
        }
        
        // if any common name is also a scientific name - then remove the common name 
        foreach($this->objects as $id => $arr)
        {
            if(isset($arr['preferred_commons']))
            {
                foreach($arr['preferred_commons'] as $language_iso => $names)
                {
                    foreach($names as $name => $val)
                    {
                        if(isset($this->objects[$id]['preferred_scientifics'][$name]))
                        {
                            unset($this->objects[$id]['preferred_commons'][$name]);
                        }
                        if(isset($this->objects[$id]['synonyms'][$name]))
                        {
                            unset($this->objects[$id]['preferred_commons'][$name]);
                        }
                    }
                }
            }
            if(isset($arr['commons']))
            {
                foreach($arr['commons'] as $language_iso => $names)
                {
                    foreach($names as $name => $val)
                    {
                        if(isset($this->objects[$id]['preferred_scientifics'][$name]))
                        {
                            unset($this->objects[$id]['commons'][$name]);
                        }
                        if(isset($this->objects[$id]['synonyms'][$name]))
                        {
                            unset($this->objects[$id]['commons'][$name]);
                        }
                    }
                }
            }
        }
    }
    
    function lookup_ancestors($start, $limit, $filter = "1=1", &$taxon_concept_ids = array())
    {
        echo "\nquerying lookup_ancestors\n";
        $query = "SELECT taxon_concept_id id, ancestor_id FROM taxon_concepts_flattened tcf WHERE tcf.taxon_concept_id ";
        if($taxon_concept_ids) $query .= "IN (". implode(",", $taxon_concept_ids) .")";
        else $query .= "BETWEEN $start AND ". ($start+$limit);
        
        $began = false;
        foreach($this->mysqli_slave->iterate_file($query) as $row_num => $row)
        {
            $id = $row[0];
            $ancestor_id = $row[1];
            $this->objects[$id]['ancestor_taxon_concept_id'][$ancestor_id] = 1;
            
            if(!$began) echo "done querying\n";
            $began = true;
        }
    }
    
    function lookup_top_images($start, $limit, $filter = "1=1", &$taxon_concept_ids = array())
    {
        echo "\nquerying top_images\n";
        $query = " SELECT ti.taxon_concept_id id, ti.data_object_id FROM top_concept_images ti JOIN data_objects do ON (ti.data_object_id=do.id) JOIN vetted v ON (do.vetted_id=v.id) WHERE ti.view_order=1 AND ti.taxon_concept_id ";
        if($taxon_concept_ids) $query .= "IN (". implode(",", $taxon_concept_ids) .")";
        else $query .= "BETWEEN $start AND ". ($start+$limit);
        $query .= " ORDER BY v.view_order ASC, do.data_rating DESC, do.id DESC";
        
        foreach($this->mysqli_slave->iterate_file($query) as $row_num => $row)
        {
            $id = $row[0];
            $data_object_id = $row[1];
            
            if(@!$this->objects[$id]['top_image_id'])
            {
                $this->objects[$id]['top_image_id'] = $data_object_id;
            }
        }
    }
    
    function lookup_richness($start, $limit, $filter = "1=1", &$taxon_concept_ids = array())
    {
        echo "\nquerying richness\n";
        $query = " SELECT taxon_concept_id, richness_score FROM taxon_concept_metrics WHERE taxon_concept_id ";
        if($taxon_concept_ids) $query .= "IN (". implode(",", $taxon_concept_ids) .")";
        else $query .= "BETWEEN $start AND ". ($start+$limit);
        
        foreach($this->mysqli_slave->iterate_file($query) as $row_num => $row)
        {
            $id = $row[0];
            $richness_score = $row[1];
            $this->objects[$id]['richness_score'] = $richness_score;
        }
    }
    
    function lookup_objects($start, $limit, $filter = "1=1", &$data_object_ids = array())
    {
        echo "\nquerying objects ($start, $limit)\n";
        $last_data_object_id = 0;
        $query = "SELECT do.id, do.guid, REPLACE(REPLACE(do.object_title, '\n', ' '), '\r', ' '), REPLACE(REPLACE(do.description, '\n', ' '), '\r', ' '), UNIX_TIMESTAMP(do.created_at), UNIX_TIMESTAMP(do.updated_at),  l.iso_639_1, do.data_type_id FROM data_objects do LEFT JOIN languages l ON (do.language_id=l.id) LEFT JOIN data_objects_hierarchy_entries dohe ON (do.id=dohe.data_object_id) WHERE (do.published=1 AND do.visibility_id=1) AND dohe.data_object_id IS NOT NULL AND do.id ";
        if($data_object_ids) $query .= "IN (". implode(",", $data_object_ids) .")";
        else $query .= "BETWEEN $start AND ". ($start+$limit);
        
        $used_ids = array();
        foreach($this->mysqli_slave->iterate_file($query) as $row_num => $row)
        {
            if(isset($row[3]) && preg_match("/^[0-9a-z]{32}$/i", $row[1]))
            {
                $id = $row[0];
                if(isset($used_ids[$id])) continue;
                $used_ids[$id] = 1;
                
                $guid = $row[1];
                $object_title = self::text_filter($row[2]);
                $description = self::text_filter($row[3]);
                $created_at = $row[4];
                $updated_at = $row[5];
                $language_iso = $row[6];
                $data_type_id = $row[7];
                
                $data_types = array();
                $data_types['DataObject'] = 1;
                if(in_array($data_type_id, array(1))) $data_types['Image'] = 1;
                elseif(in_array($data_type_id, array(2))) $data_types['Sound'] = 1;
                elseif(in_array($data_type_id, array(3))) $data_types['Text'] = 1;
                elseif(in_array($data_type_id, array(4, 7, 8))) $data_types['Video'] = 1;
                else continue;
                
                if($object_title)
                {
                    $this->objects[] = array(
                        'resource_type'             => $data_types,
                        'resource_id'               => $id,
                        'resource_unique_key'       => "DataObject_$id",
                        'keyword_type'              => 'object_title',
                        'keyword'                   => $object_title,
                        'language'                  => 'en',
                        'created_at'                => date('Y-m-d', $created_at) . "T". date('h:i:s', $created_at) ."Z",
                        'updated_at'                => date('Y-m-d', $updated_at) . "T". date('h:i:s', $updated_at) ."Z");
                }
                
                if($description)
                {
                    $this->objects[] = array(
                        'resource_type'             => $data_types,
                        'resource_id'               => $id,
                        'resource_unique_key'       => "DataObject_$id",
                        'keyword_type'              => 'description',
                        'keyword'                   => $description,
                        'full_text'                 => true,
                        'language'                  => 'en',
                        'created_at'                => date('Y-m-d', $created_at) . "T". date('h:i:s', $created_at) ."Z",
                        'updated_at'                => date('Y-m-d', $updated_at) . "T". date('h:i:s', $updated_at) ."Z");
                }
                
                $last_data_object_id = $id;
            }
            
            // // this would be a partial line. DataObjects can contain newlines and MySQL SELECT INTO OUTFILE
            // // does not escape them so one object can span many lines
            // elseif($last_data_object_id && !preg_match("/^([0-9]+)\t([0-9a-z]{32})\t/", $line))
            // {
            //     echo $last_data_object_id."\n";
            //     echo "$line\n\n";
            //     // $this->objects[$last_data_object_id]['description'] .= SolrApi::text_filter($line);
            // }
        }
    }
    
    function lookup_and_cache_language_iso_codes()
    {
        echo memory_get_usage()."\n";
        $GLOBALS['language_iso_codes'] = array();
        $query = "SELECT id, iso_639_1 FROM languages WHERE iso_639_1 != ''";
        foreach($this->mysqli_slave->iterate_file($query) as $row_num => $row)
        {
            $id = $row[0];
            $iso_639_1 = $row[1];
            $GLOBALS['language_iso_codes'][$id] = $iso_639_1;
        }
        echo memory_get_usage()."\n";
    }
    
    public static function text_filter($text, $convert_to_ascii = false)
    {
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