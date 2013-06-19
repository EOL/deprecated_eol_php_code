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
        $this->solr = new SolrAPI($this->solr_server, 'site_search');

        $this->indexable_types = array(
            'Collection' =>     array('class_name' => 'Collection', 'table_name' => 'collections', 'callback' => 'lookup_collections'),
            'Community' =>      array('class_name' => 'Community', 'table_name' => 'communities', 'callback' => 'lookup_communities'),
            'User' =>           array('class_name' => 'User', 'table_name' => 'users', 'callback' => 'lookup_users'),
            'DataObject' =>     array('class_name' => 'DataObject', 'table_name' => 'data_objects', 'callback' => 'lookup_objects'),
            'TaxonConcept' =>   array('class_name' => 'TaxonConcept', 'table_name' => 'taxon_concepts', 'callback' => 'index_taxa'),
            'ContentPage' =>    array('class_name' => 'ContentPage', 'table_name' => 'content_pages', 'callback' => 'lookup_content_pages'));
    }

    public function recreate_index_for_class($class_name)
    {
        if(!($class_parameters = @$this->indexable_types[$class_name])) return;
        $limit = 500000;
        $start = $this->mysqli->select_value("SELECT MIN(id) FROM ". $class_parameters['table_name']);
        $max_id = $this->mysqli->select_value("SELECT MAX(id) FROM ". $class_parameters['table_name']);
        for($i=$start ; $i<$max_id ; $i+=$limit)
        {
            $upper_range = $i + $limit - 1;
            if($upper_range > $max_id) $upper_range = $max_id;
            $ids = range($i, $upper_range);
            $this->index_type($class_name, $ids);
        }
        $this->solr->commit_objects_in_file();
    }

    public function index_type($class_name, &$ids)
    {
        if(!$ids) return;
        if(!($class_parameters = @$this->indexable_types[$class_name])) return;
        $batches = array_chunk($ids, 10000);
        foreach($batches as $batch)
        {
            $this->insert_batch($class_name, $batch);
        }
        $this->solr->commit_objects_in_file();
    }

    private function insert_batch($class_name, &$ids)
    {
        if(!$ids) return;
        if(!($class_parameters = @$this->indexable_types[$class_name])) return;
        $this->objects = array();
        debug("Looking up $class_name .. Time: ". time_elapsed()." .. Mem: ". memory_get_usage());
        call_user_func(array($this, $class_parameters['callback']), $ids);
        debug("Looked up $class_name .. Time: ". time_elapsed()." .. Mem: ". memory_get_usage());

        // delete old ones
        $queries = array();
        $id_chunks = array_chunk($ids, 1000);
        foreach($id_chunks as $id_chunk) $queries[] = "resource_type:$class_name AND resource_id:(". implode(" OR ", $id_chunk) .")";
        $this->solr->delete_by_queries($queries, false);

        // add new ones if available
        if(isset($this->objects) && $this->objects)
        {
            if($class_name == 'TaxonConcept') $this->send_concept_objects_to_solr();
            else $this->solr->send_attributes_in_bulk($this->objects);
        }
        $this->solr->commit();
    }

    public function index_collection($collection_id)
    {
        $ids = array($collection_id);
        $this->index_type('Collection', $ids);
        $this->solr->commit_objects_in_file();
    }

    public function index_taxa($ids)
    {
        $this->lookup_names($ids);
        $this->lookup_ancestors($ids);
        $this->lookup_richness($ids);
    }

    public function send_concept_objects_to_solr()
    {
        $objects_to_send = array();
        foreach($this->objects as $id => $arr)
        {
            $ancestor_ids = @$arr['ancestor_taxon_concept_id'];
            $top_image_id = @$arr['top_image_id'];
            $richness_score = @$arr['richness_score'];
            $base_attributes = array(
                'resource_type'             => 'TaxonConcept',
                'resource_id'               => $id,
                'resource_unique_key'       => "TaxonConcept_$id",
                'ancestor_taxon_concept_id' => $ancestor_ids,
                'top_image_id'              => $top_image_id,
                'richness_score'            => $richness_score);
            if(isset($arr['preferred_scientifics']))
            {
                $objects_to_send[] = $base_attributes + array(
                    'keyword_type'              => 'PreferredScientific',
                    'keyword'                   => $arr['preferred_scientifics'],
                    'language'                  => 'sci',
                    'resource_weight'           => 1);
            }
            if(isset($arr['synonyms']))
            {
                $objects_to_send[] = $base_attributes + array(
                    'keyword_type'              => 'Synonym',
                    'keyword'                   => $arr['synonyms'],
                    'language'                  => 'sci',
                    'resource_weight'           => 3);
            }
            if(isset($arr['surrogates']))
            {
                $objects_to_send[] = $base_attributes + array(
                    'keyword_type'              => 'Surrogate',
                    'keyword'                   => $arr['surrogates'],
                    'language'                  => 'sci',
                    'resource_weight'           => 500);
            }
            if(isset($arr['preferred_commons']))
            {
                foreach($arr['preferred_commons'] as $language_iso => $names)
                {
                    $objects_to_send[] = $base_attributes + array(
                        'keyword_type'              => 'PreferredCommonName',
                        'keyword'                   => $names,
                        'language'                  => $language_iso,
                        'resource_weight'           => 2);
                }
            }
            if(isset($arr['commons']))
            {
                foreach($arr['commons'] as $language_iso => $names)
                {
                    $objects_to_send[] = $base_attributes + array(
                        'keyword_type'              => 'CommonName',
                        'keyword'                   => $names,
                        'language'                  => $language_iso,
                        'resource_weight'           => 4);
                }
            }
            unset($this->objects[$id]);
        }
        $this->solr->send_attributes_in_bulk($objects_to_send);
    }

    function lookup_names(&$ids)
    {
        $this->lookup_and_cache_language_iso_codes();
        $query = "
            SELECT tc.id, tc.vetted_id, tcn.preferred, tcn.vern, tcn.language_id, tcn.source_hierarchy_entry_id, n.string, tcn.vetted_id
            FROM taxon_concepts tc
            JOIN taxon_concept_names tcn ON (tc.id=tcn.taxon_concept_id)
            JOIN names n ON  (tcn.name_id=n.id)
            WHERE tc.supercedure_id=0
            AND tc.published=1
            AND tc.id IN (". implode(",", $ids) .")";
        $untrusted_id = Vetted::untrusted()->id;
        $inappropriate_id = Vetted::inappropriate()->id;
        foreach($this->mysqli_slave->iterate_file($query) as $row)
        {
            $id = $row[0];
            $vetted_id = SolrApi::text_filter($row[1]);
            $preferred = SolrApi::text_filter($row[2]);
            $vern = SolrApi::text_filter($row[3]);
            $language_id = SolrApi::text_filter($row[4]);
            $source_hierarchy_entry_id = SolrApi::text_filter($row[5]);
            $string = SolrApi::text_filter($row[6]);
            $name_vetted_id = SolrApi::text_filter($row[7]);
            if(!$string) continue;

            if($vern)
            {
                $language_iso = @$GLOBALS['language_iso_codes'][$language_id] ?: 'unknown';
                if($preferred && $language_iso != 'unknown')
                {
                    $this->objects[$id]['preferred_commons'][$language_iso][$string] = 1;
                }elseif($name_vetted_id != $untrusted_id && $vetted_id != $inappropriate_id)
                {
                    $this->objects[$id]['commons'][$language_iso][$string] = 1;
                }
            }elseif($source_hierarchy_entry_id)
            {
                if(Name::is_surrogate($string))
                {
                    $this->objects[$id]['surrogates'][$string] = 1;
                }elseif($preferred)
                {
                    $this->objects[$id]['preferred_scientifics'][$string] = 1;
                }else
                {
                    $this->objects[$id]['synonyms'][$string] = 1;
                }
            }
        }
        $this->remove_duplicate_scientific_and_common_names();
    }

    function remove_duplicate_scientific_and_common_names()
    {
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

    function lookup_ancestors(&$ids)
    {
        $query = "
            SELECT taxon_concept_id id, ancestor_id
            FROM taxon_concepts_flattened tcf
            WHERE tcf.taxon_concept_id IN (". implode(",", $ids) .")";
        foreach($this->mysqli_slave->iterate_file($query) as $row)
        {
            $id = $row[0];
            $ancestor_id = $row[1];
            $this->objects[$id]['ancestor_taxon_concept_id'][$ancestor_id] = 1;
        }
    }

    function lookup_richness(&$ids)
    {
        $query = "
            SELECT taxon_concept_id, richness_score
            FROM taxon_concept_metrics
            WHERE taxon_concept_id IN (". implode(",", $ids) .")";
        foreach($this->mysqli_slave->iterate_file($query) as $row)
        {
            $id = $row[0];
            $richness_score = $row[1];
            $this->objects[$id]['richness_score'] = $richness_score;
        }
    }

    function lookup_objects(&$ids)
    {
        $this->agents_for_objects = array();
        $this->lookup_object_agents($ids);
        $this->lookup_object_users($ids);
        $query = "
            SELECT do.id, do.guid,
            REPLACE(REPLACE(do.object_title, '\n', ' '), '\r', ' '),
            REPLACE(REPLACE(do.description, '\n', ' '), '\r', ' '),
            REPLACE(REPLACE(do.rights_statement, '\n', ' '), '\r', ' '),
            REPLACE(REPLACE(do.rights_holder, '\n', ' '), '\r', ' '),
            REPLACE(REPLACE(do.bibliographic_citation, '\n', ' '), '\r', ' '),
            REPLACE(REPLACE(do.location, '\n', ' '), '\r', ' '),
            UNIX_TIMESTAMP(do.created_at), UNIX_TIMESTAMP(do.updated_at),  l.iso_639_1, do.data_type_id
            FROM data_objects do
            LEFT JOIN languages l ON (do.language_id=l.id)
            LEFT JOIN data_objects_hierarchy_entries dohe ON (do.id=dohe.data_object_id)
            LEFT JOIN curated_data_objects_hierarchy_entries cdohe ON (do.id=cdohe.data_object_id)
            LEFT JOIN users_data_objects udo ON (do.id=udo.data_object_id)
            WHERE do.published=1
            AND (dohe.visibility_id=". Visibility::visible()->id ." OR cdohe.visibility_id=". Visibility::visible()->id ." OR udo.visibility_id=". Visibility::visible()->id .")
            AND do.id IN (". implode(",", $ids) .")";
        $used_ids = array();
        foreach($this->mysqli_slave->iterate_file($query) as $row)
        {
            if(isset($row[3]) && preg_match("/^[0-9a-z]{32}$/i", $row[1]))
            {
                $id = $row[0];
                if(isset($used_ids[$id])) continue;
                $used_ids[$id] = 1;

                $guid = $row[1];
                $object_title = SolrApi::text_filter($row[2]);
                $description = SolrApi::text_filter($row[3]);
                $rights_statement = SolrApi::text_filter($row[4]);
                $rights_holder = SolrApi::text_filter($row[5]);
                $bibliographic_citation = SolrApi::text_filter($row[6]);
                $location = SolrApi::text_filter($row[7]);
                $created_at = SolrApi::text_filter($row[8], true);
                $updated_at = SolrApi::text_filter($row[9], true);
                $language_iso = SolrApi::text_filter($row[10]);
                $data_type_id = SolrApi::text_filter($row[11]);
                if($return = $this->get_resource_weight_and_data_types($data_type_id))
                {
                    list($resource_weight, $data_types) = $return;
                }else continue;

                $base_attributes = array(
                    'resource_type'             => $data_types,
                    'resource_id'               => $id,
                    'resource_unique_key'       => "DataObject_$id",
                    'language'                  => 'en',
                    'date_created'              => $created_at,
                    'date_modified'             => $updated_at);

                $fields_to_index = array(
                    array('keyword_type'    => 'object_title',
                          'keyword'         => $object_title,
                          'full_text'       => false,
                          'resource_weight' => $resource_weight),
                    array('keyword_type'    => 'description',
                          'keyword'         => $description,
                          'full_text'       => true,
                          'resource_weight' => $resource_weight + 2),
                    array('keyword_type'    => 'rights_statement',
                          'keyword'         => $rights_statement,
                          'full_text'       => false,
                          'resource_weight' => $resource_weight + 3),
                    array('keyword_type'    => 'rights_holder',
                          'keyword'         => $rights_holder,
                          'full_text'       => false,
                          'resource_weight' => $resource_weight + 4),
                    array('keyword_type'    => 'bibliographic_citation',
                          'keyword'         => $bibliographic_citation,
                          'full_text'       => false,
                          'resource_weight' => $resource_weight + 5),
                    array('keyword_type'    => 'location',
                          'keyword'         => $location,
                          'full_text'       => false,
                          'resource_weight' => $resource_weight + 6)
                );

                foreach($fields_to_index as $field_to_index)
                {
                    $keyword = str_replace("  ", " ", trim($field_to_index['keyword']));
                    if(!$keyword) continue;
                    $this->objects[] = $base_attributes + array(
                        'keyword_type'              => $field_to_index['keyword_type'],
                        'keyword'                   => $keyword,
                        'full_text'                 => $field_to_index['full_text'],
                        'resource_weight'           => $field_to_index['resource_weight']);
                }
                if(isset($this->agents_for_objects[$id]))
                {
                    foreach($this->agents_for_objects[$id] as $agent_name)
                    {
                        $keyword = str_replace("  ", " ", trim($agent_name));
                        if(!$keyword) continue;
                        $this->objects[] = $base_attributes + array(
                            'keyword_type'              => 'agent',
                            'keyword'                   => $keyword,
                            'resource_weight'           => $resource_weight + 1);
                    }
                }
            }
        }
    }

    function lookup_object_agents($ids)
    {
        if(!$ids) return;
        $query = "
            SELECT do.id, a.full_name, a.given_name, a.family_name
            FROM data_objects do
            JOIN agents_data_objects ado ON (do.id=ado.data_object_id)
            JOIN agents a ON (ado.agent_id=a.id)
            WHERE ado.data_object_id IN (". implode(",", $ids) .")
            AND do.published=1";
        foreach($this->mysqli_slave->iterate_file($query) as $row)
        {
            $data_object_id = $row[0];
            $full_name = SolrApi::text_filter($row[1]);
            $given_name = SolrApi::text_filter($row[2]);
            $family_name = SolrApi::text_filter($row[3]);
            if($agent_name = trim($full_name ." ". $given_name ." ". $family_name))
            {
                $this->agents_for_objects[$data_object_id][] = $agent_name;
            }
        }
    }

    function lookup_object_users($ids)
    {
        if(!$ids) return;
        $query = "
            SELECT do.id, u.username, u.given_name, u.family_name
            FROM data_objects do
            JOIN users_data_objects udo ON (do.id=udo.data_object_id)
            JOIN users u ON (udo.user_id=u.id)
            WHERE udo.data_object_id IN (". implode(",", $ids) .")
            AND do.published=1";
        foreach($this->mysqli_slave->iterate_file($query) as $row)
        {
            $data_object_id = $row[0];
            $username = SolrApi::text_filter($row[1]);
            $given_name = SolrApi::text_filter($row[2]);
            $family_name = SolrApi::text_filter($row[3]);
            if($username) $this->agents_for_objects[$data_object_id][] = $username;
            if($user_real_name = trim($given_name ." ". $family_name))
            {
                $this->agents_for_objects[$data_object_id][] = $user_real_name;
            }
        }
    }

    function get_resource_weight_and_data_types($data_type_id)
    {
        $data_types = array();
        $data_types['DataObject'] = 1;
        $resource_weight = 100;
        if(in_array($data_type_id, DataType::image_type_ids()))
        {
            $data_types['Image'] = 1;
            $resource_weight = 60;
        }elseif(in_array($data_type_id, DataType::sound_type_ids()))
        {
            $data_types['Sound'] = 1;
            $resource_weight = 70;
        }elseif(in_array($data_type_id, DataType::text_type_ids()))
        {
            $data_types['Text'] = 1;
            $resource_weight = 40;
        }elseif(in_array($data_type_id, DataType::video_type_ids()))
        {
            $data_types['Video'] = 1;
            $resource_weight = 50;
        }else return null;
        return array($resource_weight, $data_types);
    }

    function lookup_users(&$ids)
    {
        $query = "
            SELECT id, username, given_name, family_name, UNIX_TIMESTAMP(created_at), UNIX_TIMESTAMP(updated_at)
            FROM users
            WHERE active = 1
            AND hidden != 1
            AND id IN (". implode(",", $ids) .")";
        foreach($this->mysqli_slave->iterate_file($query) as $row)
        {
            $id = $row[0];
            $username = SolrApi::text_filter($row[1]);
            $given_name = SolrApi::text_filter($row[2]);
            $family_name = SolrApi::text_filter($row[3]);
            $created_at = SolrApi::text_filter($row[4], true);
            $updated_at = SolrApi::text_filter($row[5], true);

            $base = array(
                'resource_type'             => 'User',
                'resource_id'               => $id,
                'resource_unique_key'       => "User_$id",
                'language'                  => 'en',
                'resource_weight'           => 30,
                'date_created'              => $created_at,
                'date_modified'             => $updated_at);
            $record = $base;
            $record['keyword_type'] = 'username';
            $record['keyword'] = $username;
            if($record['keyword']) $this->objects[] = $record;

            $record = $base;
            $record['keyword_type'] = 'full_name';
            $record['keyword'] = trim($given_name ." ". $family_name);
            if($record['keyword']) $this->objects[] = $record;
        }
    }

    function lookup_collections(&$ids)
    {
        $query = "
            SELECT id, name, description, UNIX_TIMESTAMP(created_at), UNIX_TIMESTAMP(updated_at), special_collection_id, published
            FROM collections
            WHERE id IN (". implode(",", $ids) .")";
        foreach($this->mysqli_slave->iterate_file($query) as $row)
        {
            $id = $row[0];
            $name = SolrApi::text_filter($row[1]);
            $description = SolrApi::text_filter($row[2]);
            $created_at = SolrApi::text_filter($row[3], true);
            $updated_at = SolrApi::text_filter($row[4], true);
            $special_collection_id = SolrApi::text_filter($row[5]);
            $published = SolrApi::text_filter($row[6]);
            if(!$published) continue;
            if($special_collection_id == SpecialCollection::watch()->id) continue; // users watch collection

            $base = array(
                'resource_type'             => 'Collection',
                'resource_id'               => $id,
                'resource_unique_key'       => "Collection_$id",
                'language'                  => 'en',
                'resource_weight'           => 20,
                'date_created'              => $created_at,
                'date_modified'             => $updated_at);
            $record = $base;
            $record['keyword_type'] = 'name';
            $record['keyword'] = $name;
            $this->objects[] = $record;

            if($description)
            {
                $record = $base;
                $record['keyword_type'] = 'description';
                $record['keyword'] = $description;
                $record['full_text'] = true;
                $this->objects[] = $record;
            }
        }
    }

    function lookup_communities($ids)
    {
        $query = "
            SELECT c.id, c.name, c.description, UNIX_TIMESTAMP(c.created_at), UNIX_TIMESTAMP(c.updated_at), c.published
            FROM communities c
            WHERE c.id IN (". implode(",", $ids) .")";
        foreach($this->mysqli_slave->iterate_file($query) as $row)
        {
            $id = $row[0];
            $name = SolrApi::text_filter($row[1]);
            $description = SolrApi::text_filter($row[2]);
            $created_at = SolrApi::text_filter($row[3], true);
            $updated_at = SolrApi::text_filter($row[4], true);
            $published = SolrApi::text_filter($row[5]);
            if(!$published) continue;

            $base = array(
                'resource_type'             => 'Community',
                'resource_id'               => $id,
                'resource_unique_key'       => "Community_$id",
                'language'                  => 'en',
                'resource_weight'           => 10,
                'date_created'              => $created_at,
                'date_modified'             => $updated_at);
            $record = $base;
            $record['keyword_type'] = 'name';
            $record['keyword'] = $name;
            if($record['keyword']) $this->objects[] = $record;

            if($description)
            {
                $record = $base;
                $record['keyword_type'] = 'description';
                $record['keyword'] = $description;
                $record['full_text'] = true;
                if($record['keyword']) $this->objects[] = $record;
            }
        }
    }

    function lookup_content_pages(&$ids)
    {
        $this->lookup_and_cache_language_iso_codes();
        $query = "
            SELECT cp.id, cp.page_name, tcp.language_id, tcp.title, tcp.left_content, tcp.main_content, tcp.meta_keywords,
                tcp.meta_description, UNIX_TIMESTAMP(tcp.created_at) created_at, UNIX_TIMESTAMP(tcp.updated_at) updated_at
            FROM content_pages cp
            JOIN translated_content_pages tcp ON (cp.id=tcp.content_page_id)
            WHERE cp.active=1
            AND tcp.active_translation=1
            AND cp.id IN (". implode(",", $ids) .")";
        foreach($this->mysqli_slave->iterate($query) as $row)
        {
            $id = $row['id'];
            $page_name = SolrApi::text_filter($row['page_name']);
            $language_id = SolrApi::text_filter($row['language_id']);
            $title = SolrApi::text_filter($row['title']);
            $left_content = SolrApi::text_filter($row['left_content']);
            $main_content = SolrApi::text_filter($row['main_content']);
            $meta_keywords = SolrApi::text_filter($row['meta_keywords']);
            $meta_description = SolrApi::text_filter($row['meta_description']);
            $created_at = SolrApi::text_filter($row['created_at'], true);
            $updated_at = SolrApi::text_filter($row['updated_at'], true);
            $language_iso = @$GLOBALS['language_iso_codes'][$language_id];
            if(!$language_iso) continue;

            $base = array(
                'resource_type'             => 'ContentPage',
                'resource_id'               => $id,
                'resource_unique_key'       => "ContentPage_$id",
                'resource_weight'           => 25,
                'date_created'              => $created_at,
                'date_modified'             => $updated_at);

            $record = $base;
            $record['keyword_type'] = 'page_name';
            $record['keyword'] = $page_name;
            $record['language'] = 'en';
            if($record['keyword']) $this->objects[] = $record;

            $record['keyword_type'] = 'title';
            $record['keyword'] = $title;
            $record['language'] = $language_iso;
            if($record['keyword']) $this->objects[] = $record;

            $record['keyword_type'] = 'meta_keywords';
            $record['keyword'] = $meta_keywords;
            if($record['keyword']) $this->objects[] = $record;

            $record['full_text'] = true;
            $record['keyword_type'] = 'left_content';
            $record['keyword'] = $left_content;
            if($record['keyword']) $this->objects[] = $record;

            $record['keyword_type'] = 'main_content';
            $record['keyword'] = $main_content;
            if($record['keyword']) $this->objects[] = $record;

            $record['keyword_type'] = 'meta_description';
            $record['keyword'] = $meta_description;
            if($record['keyword']) $this->objects[] = $record;
        }
    }

    function lookup_and_cache_language_iso_codes()
    {
        if(@$GLOBALS['language_iso_codes']) return $GLOBALS['language_iso_codes'];
        $GLOBALS['language_iso_codes'] = array();
        $query = "SELECT id, iso_639_1 FROM languages WHERE iso_639_1 != ''";
        foreach($this->mysqli_slave->iterate_file($query) as $row)
        {
            $id = $row[0];
            $iso_639_1 = $row[1];
            $GLOBALS['language_iso_codes'][$id] = $iso_639_1;
        }
    }

    function sync_latest_versions_of_type($class_name)
    {
        if(!($class_parameters = @$this->indexable_types[$class_name])) return;
        $maximum_record_id = $this->mysqli->select_value("SELECT MAX(id) FROM ". $class_parameters['table_name']);

        $solr = new SolrAPI(SOLR_SERVER, 'site_search');
        $main_query_parameters = array(
            'query' => 'resource_type:' . $class_name,
            'wt' => 'json',
            'fl' => 'resource_id',
            'group' => 'true',
            'group.field' => 'resource_unique_key',
            'sort' => 'resource_id asc'
        );
        $total_results = $solr->count_groups_by_hash($main_query_parameters);
        echo "There are $total_results total $class_name indexed\n";
        $iteration_size = 100000;
        $starting_record_id = 0;
        $last_record_id = 0;

        for($i=0 ; $i<$total_results ; $i += $iteration_size)
        {
            echo "batch ". (($i + $iteration_size)/$iteration_size) ." of ". (ceil($total_results/$iteration_size)) ." :: ". time_elapsed() ." :: ". memory_get_usage() ."\n";
            $batch_record_ids = array();
            $groups = $solr->get_groups($main_query_parameters + array('rows' => $iteration_size, 'start' => $i));
            foreach($groups as $group)
            {
                $record_id = $group->doclist->docs[0]->resource_id;
                $batch_record_ids[] = $record_id;
                $last_record_id = $record_id;
            }
            $this->sync_batch_of_type($class_parameters, $starting_record_id, $last_record_id, $batch_record_ids);
            $starting_record_id = $last_record_id + 1;
        }

        // add anything higher than $starting_record_id
        $main_query_parameters['sort'] = 'resource_id desc';
        $groups = $solr->get_groups($main_query_parameters + array('rows' => 1, 'start' => 0));
        $maximum_id_in_solr = $groups[0]->doclist->docs[0]->resource_id;
        $higher_published_ids = array();
        $query = "SELECT id FROM ". $class_parameters['table_name'] ." WHERE id > $maximum_id_in_solr";
        if(in_array($class_parameters['class_name'], array('TaxonConcept', 'DataObject', 'Collection', 'Community'))) $query .= " AND published=1";
        elseif($class_parameters['class_name'] == 'User') $query .= " AND active=1 AND hidden!=1";
        foreach($this->mysqli_slave->iterate_file($query) as $row)
        {
            $higher_published_ids[] = $row[0];
        }
        if($higher_published_ids)
        {
            echo "Add these (at the end):\n";
            print_r($higher_published_ids);
            $this->index_type($class_parameters['class_name'], $higher_published_ids);
        }
    }

    function sync_batch_of_type($class_parameters, $starting_record_id, $last_record_id, $batch_record_ids)
    {
        $published_ids_in_this_range = array();
        $query = "SELECT id FROM ". $class_parameters['table_name'] ." WHERE id BETWEEN $starting_record_id AND $last_record_id";
        if(in_array($class_parameters['class_name'], array('TaxonConcept', 'DataObject', 'Collection', 'Community'))) $query .= " AND published=1";
        elseif($class_parameters['class_name'] == 'User') $query .= " AND active=1 AND hidden!=1";
        foreach($this->mysqli_slave->iterate_file($query) as $row)
        {
            $published_ids_in_this_range[] = $row[0];
        }
        $ids_needing_to_be_added = array_diff($published_ids_in_this_range, $batch_record_ids);
        $ids_needing_to_be_removed = array_diff($batch_record_ids, $published_ids_in_this_range);
        if($ids_needing_to_be_added)
        {
            echo "Add these:\n";
            print_r($ids_needing_to_be_added);
            $this->index_type($class_parameters['class_name'], $ids_needing_to_be_added);
        }
        if($ids_needing_to_be_removed)
        {
            echo "Remove these:\n";
            print_r($ids_needing_to_be_removed);
            $this->index_type($class_parameters['class_name'], $ids_needing_to_be_removed);
        }
    }
}

?>
