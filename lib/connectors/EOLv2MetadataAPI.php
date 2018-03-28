<?php
namespace php_active_record;

class EOLv2MetadataAPI
{
    public function __construct($folder)
    {
        $this->folder = $folder;
        $this->mysqli =& $GLOBALS['db_connection'];
        // IF(cp.description_of_data IS NOT NULL, cp.description_of_data, r.description) as desc_of_data
        // $result = $mysqli->query("SELECT r.hierarchy_id, max(he.id) as max FROM resources r JOIN harvest_events he ON (r.id=he.resource_id) GROUP BY r.hierarchy_id");
        // $result = $mysqli->query("SELECT r.hierarchy_id, max(he.id) as max FROM resources r JOIN harvest_events he ON (r.id=he.resource_id) GROUP BY r.hierarchy_id");
        // $harvest_event = HarvestEvent::find($row['max']);
        // if(!$harvest_event->published_at) $GLOBALS['hierarchy_preview_harvest_event'][$row['hierarchy_id']] = $row['max'];
        $this->path['temp_dir'] = "/Volumes/Thunderbolt4/EOL_V2/";
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->taxon_ids = array();
        
        $this->download_options = array("cache" => 1, "download_wait_time" => 2000000, "timeout" => 3600, "download_attempts" => 1); //"delay_in_minutes" => 1
        $this->download_options['expire_seconds'] = false; //always false, will not change anymore...
        if(Functions::is_production()) $this->download_options['cache_path'] = "/extra/eol_cache_collections/";
        else                           $this->download_options['cache_path'] = "/Volumes/AKiTiO4/eol_cache_collections/";
        $this->url["eol_object"]     = "http://eol.org/api/data_objects/1.0/data_object_id.json?taxonomy=true&cache_ttl=";
        
        // http://eol.org/api/data_objects/1.0/29829638.json?taxonomy=true&cache_ttl=
    }
    public function start_image_sizes() //DATA-1740 - unique do_id in image_sizes = 19,429
    {
        /* test
        $res_info = self::get_resource_info_using_obj_url("http://farm5.static.flickr.com/4097/4776265549_15a03b0c1c.jpg");
        print_r($res_info);
        exit('\n-just test-\n');
        */
        // /* test
        $res_info = self::get_resource_info_last_resort(3286228);
        print_r($res_info);
        exit('\n-just test-\n');
        // */
        
        $sql = "SELECT i.*, o.* from image_sizes i left join data_objects_ImageSizes o on (i.data_object_id = o.id) 
        -- order by i.updated_at desc
        -- limit 10
        ";

        $result = $this->mysqli->query($sql);
        // echo "\n". $result->num_rows . "\n"; exit;
        $recs = array();
        
        $FILE = Functions::file_open($filename = CONTENT_RESOURCE_LOCAL_PATH ."image_crops.txt", "w");
        
        $headers_printed_already = false;
        while($result && $row=$result->fetch_assoc()) {
            $no_tc_id = false;
            $tc_id = false;
            if($tc_id = @$row['taxon_concept_id']) {} //echo "\n With tc_id \n";
            else {
                // echo "\n NO tc_id \n";
                if($tc_id = self::get_tc_id_using_do_id($row['data_object_id'])) {}
                elseif($tc_id = self::get_tc_id_using_dotc($row['data_object_id'])) {} //dotc - data_objects_taxon_concepts
                // elseif($row['ch_object_type'] == "users_submitted_text")
                // {
                //     $a = self::get_tc_id_from_udo($row['data_object_id']); //udo - users_data_objects
                //     $tc_id = @$a['taxon_concept_id'];
                // }
                else {
                    echo("\n\nNo taxon_concept_id found for ".$row['data_object_id']."\n");
                    // print_r($row); //exit;
                    $no_tc_id = true;
                    $info['taxon_name'] = '-orphan object-';
                }
            }
            $info = false;
            if($tc_id) {
                $info = self::get_taxon_info($tc_id);
                // print_r($info); exit;
            }
            $rec = array();
            /* excluded for this report
            $rec['user_id'] = $row['user_id'];
            $rec['user_name'] = $row['user_name'];
            $rec['activity'] = $row['activity'];
            $rec['ch_object_type'] = $row['ch_object_type'];
            */
            $rec['data_object_id'] = $row['data_object_id'];
            $rec['obj_guid'] = $row['guid'];
            $rec['crop_dimensions'] = self::get_crop_dimensions($row);
            $rec['obj_type'] = self::lookup_data_type($row['data_type_id']);
            $rec['obj_url'] = self::lookup_object_url($row, $rec['obj_type']);
            $rec['obj_description'] = $row['description'];
            
            $rec['taxon_concept_id'] = $tc_id;
            $rec['sciname'] = @$info['taxon_name'];
            $rec['rank'] = @$info['rank'];
            $rec['ancestry'] = self::generate_ancestry_as_json($info);
            
            $row['obj_url'] = $rec['obj_url']; //used in lookup_resource_info()
            $resource_info = self::lookup_resource_info($row, 'data_objects_harvest_events_ImageSizes');
            $rec['resource_id'] = @$resource_info['resource_id'];
            $rec['resource_name'] = $resource_info['resource_name'];
            $rec['partner_id'] = @$resource_info['cp_id'];
            $rec['partner_name'] = @$resource_info['cp_name'];
            $rec['collection_id'] = @$resource_info['coll_id'];
            
            //start writing
            if(!$headers_printed_already) {
                fwrite($FILE, implode("\t", array_keys($rec))."\n");
                $headers_printed_already = true;
            }
            fwrite($FILE, implode("\t", $rec)."\n");
        }
        fclose($FILE);
        
    }
    private function get_crop_dimensions($rec)
    {
        $arr = array("height" => $rec['height'], 'width' => $rec['width'], 'crop_x'      => ($rec['crop_x_pct']/100) * $rec['width'], 
                                                                           'crop_y'      => ($rec['crop_y_pct']/100) * $rec['height'], 
                                                                           'crop_width'  => ($rec['crop_width_pct']/100) * $rec['width'],
                                                                           'crop_height' => ($rec['crop_height_pct']/100) * $rec['height']);
        return json_encode($arr);
    }
    public function start_user_comments($type)
    {
        if($type == 'DataObject') $sql = "SELECT c.parent_id as data_object_id, c.*
        ,concat(ifnull(u.given_name,''), ' ', ifnull(u.family_name,''), ' ', if(u.username is not null, concat('(',u.username,')'), '')) as user_name 
        ,o.* from comments c left join data_objects_comments o on (c.parent_id = o.id)
        LEFT JOIN users u ON (c.user_id = u.id) where c.parent_type = 'DataObject' and o.id is not null and c.deleted = 0 order by c.id desc;"; //17440
        
        elseif($type == 'TaxonConcept') $sql = "SELECT c.parent_id as taxon_concept_id, c.* 
        ,concat(ifnull(u.given_name,''), ' ', ifnull(u.family_name,''), ' ', if(u.username is not null, concat('(',u.username,')'), '')) as user_name
        from comments c LEFT JOIN users u ON (c.user_id = u.id) where c.parent_type = 'TaxonConcept' and c.deleted = 0 order by c.id desc;"; //9426
        
        elseif($type == 'Collection') $sql = "SELECT c.* 
        ,concat(ifnull(u.given_name,''), ' ', ifnull(u.family_name,''), ' ', if(u.username is not null, concat('(',u.username,')'), '')) as user_name
        from comments c LEFT JOIN users u ON (c.user_id = u.id) where c.parent_type = 'Collection' and c.deleted = 0 order by c.id desc;"; //1172

        $result = $this->mysqli->query($sql);
        // echo "\n". $result->num_rows . "\n"; exit;
        $recs = array();
        
        $FILE = Functions::file_open($filename = CONTENT_RESOURCE_LOCAL_PATH ."user_comments_".$type.".txt", "w");
        
        $headers_printed_already = false;
        $m = 3488; $k = 0;
        while($result && $row=$result->fetch_assoc()) {
            $k++;
            /* breakdown when caching:
            $cont = false;
            // if($k >=  1    && $k < $m) $cont = true;
            // if($k >=  $m   && $k < $m*2) $cont = true;
            // if($k >=  $m*2 && $k < $m*3) $cont = true;
            // if($k >=  $m*3 && $k < $m*4) $cont = true;
            if($k >=  $m*4 && $k < $m*5) $cont = true;
            if(!$cont) continue;
            */

            $no_tc_id = false;
            $tc_id = false;
            
            if(in_array($type, array('DataObject', 'TaxonConcept'))) {
                if($tc_id = @$row['taxon_concept_id']) {} //echo "\n With tc_id \n";
                else {
                    // echo "\n NO tc_id \n";
                    if($tc_id = self::get_tc_id_using_do_id($row['data_object_id'])) {}
                    elseif($tc_id = self::get_tc_id_using_dotc($row['data_object_id'])) {} //dotc - data_objects_taxon_concepts
                    else {
                        // echo("\n\nNo taxon_concept_id found for ".$row['data_object_id']."\n");
                        // print_r($row); //exit;
                        $no_tc_id = true;
                    }
                }
                $info = false;
                if($tc_id) {
                    $info = self::get_taxon_info($tc_id);
                    // print_r($info); exit;
                }
            }
            
            $rec = array();
            $rec['user_id'] = $row['user_id'];
            $rec['user_name'] = $row['user_name'];
            $rec['type'] = $row['parent_type'];
            $rec['target_id'] = $row['parent_id'];
            $rec['comment'] = $row['body'];
            $rec['date_stamp'] = $row['updated_at'];
            
            if($type == 'DataObject') {
                $rec['obj_guid'] = $row['guid'];
                $rec['obj_type'] = self::lookup_data_type($row['data_type_id']);
                $rec['obj_description'] = $row['description'];
                $rec['object_url'] = self::lookup_object_url($row, $rec['obj_type']);
            }

            if(in_array($type, array('DataObject', 'TaxonConcept'))) {
                $rec['taxon_concept_id'] = $tc_id;
                $rec['sciname'] = @$info['taxon_name'];
                $rec['rank'] = @$info['rank'];
                $rec['ancestry'] = self::generate_ancestry_as_json($info);
            }

            // print_r($rec); //exit;
            // continue; //debug
            
            //start writing
            if(!$headers_printed_already) {
                fwrite($FILE, implode("\t", array_keys($rec))."\n");
                $headers_printed_already = true;
            }
            fwrite($FILE, implode("\t", $rec)."\n");
        }
        fclose($FILE);
    }
    
    public function start_user_object_curation() //total 155,763 --> 153,370 without data_point_uri
    {
        $sql = "SELECT cal.user_id, cal.taxon_concept_id, cal.activity_id, cal.target_id as data_object_id ,cot.ch_object_type ,t.name as activity
        ,concat(ifnull(u.given_name,''), ' ', ifnull(u.family_name,''), ' ', if(u.username is not null, concat('(',u.username,')'), '')) as user_name
        ,d.guid, d.description, d.object_url, d.data_type_id
        from eol_logging_production.curator_activity_logs cal 
        LEFT JOIN eol_development.changeable_object_types cot on (cal.changeable_object_type_id = cot.id)
        LEFT JOIN users u ON (cal.user_id = u.id)
        LEFT JOIN eol_logging_production.translated_activities t ON (cal.activity_id = t.activity_id)
        LEFT JOIN eol_development.data_objects_curation d on (cal.target_id = d.id)
        where 1=1 
        and cal.activity_id in(37,82,81,60,53,90,55,89,58,59,50)
        and cot.ch_object_type != 'comment' and cot.ch_object_type != 'data_point_uri'
        and t.language_id = 152
        -- and cot.ch_object_type = 'users_submitted_text'
        order by cal.target_id";
        $result = $this->mysqli->query($sql);
        // echo "\n". $result->num_rows . "\n"; exit;
        $recs = array();
        
        $FILE = Functions::file_open($filename = CONTENT_RESOURCE_LOCAL_PATH ."user_object_curation.txt", "w");
        
        $headers_printed_already = false;
        while($result && $row=$result->fetch_assoc()) {
            $no_tc_id = false;
            $tc_id = false;
            if($tc_id = $row['taxon_concept_id']) {} //echo "\n With tc_id \n";
            else {
                // echo "\n NO tc_id \n";
                if($tc_id = self::get_tc_id_using_do_id($row['data_object_id'])) {}
                elseif($tc_id = self::get_tc_id_using_dotc($row['data_object_id'])) {} //dotc - data_objects_taxon_concepts
                elseif($row['ch_object_type'] == "users_submitted_text")
                {
                    $a = self::get_tc_id_from_udo($row['data_object_id']); //udo - users_data_objects
                    $tc_id = @$a['taxon_concept_id'];
                }
                else {
                    echo("\n\nNo taxon_concept_id found for ".$row['data_object_id']."\n");
                    // print_r($row); //exit;
                    $no_tc_id = true;
                }
            }
            $info = false;
            if($tc_id) {
                $info = self::get_taxon_info($tc_id);
                // print_r($info); exit;
            }
            $rec = array();
            $rec['user_id'] = $row['user_id'];
            $rec['user_name'] = $row['user_name'];
            $rec['activity'] = $row['activity'];
            $rec['ch_object_type'] = $row['ch_object_type'];
            $rec['target_id'] = $row['data_object_id'];
            $rec['guid'] = $row['guid'];
            $rec['type'] = self::lookup_data_type($row['data_type_id']);
            $rec['description'] = $row['description'];
            $rec['object_url'] = self::lookup_object_url($row, $rec['type']);
            
            $rec['taxon_concept_id'] = $tc_id;
            $rec['sciname'] = @$info['taxon_name'];
            $rec['rank'] = @$info['rank'];
            $rec['ancestry'] = self::generate_ancestry_as_json($info);
            
            $row['obj_url'] = $rec['object_url']; //used in lookup_resource_info()
            $resource_info = self::lookup_resource_info($row, 'data_objects_harvest_events_curation');
            $rec['resource_id'] = @$resource_info['resource_id'];
            $rec['resource_name'] = $resource_info['resource_name'];
            $rec['partner_id'] = @$resource_info['cp_id'];
            $rec['partner_name'] = @$resource_info['cp_name'];
            $rec['collection_id'] = @$resource_info['coll_id'];
            
            // if($rec['description'] && $rec['resource_id'] && $rec['type'] == 'Image' && $rec['taxon_concept_id'] && $rec['resource_name']) {
            //     print_r($rec);
            //     exit;
            // }

            // /* good debug
            // if($rec['resource_id']) {
                // print_r($rec); exit;
            // }
            // */
            
            /* good debug
            if($no_tc_id) {
                print_r($rec); exit;
            }
            */
            
            
            /*   [user_id] => 35779
                 [user_name] => Barna Páll-Gergely (Alopia)
                 [activity] => trusted
                 [ch_object_type] => data_object
                 [target_id] => 1495237
                 [taxon_concept_id] => 2366
                 [sciname] => Gastropoda
                 [rank] => class
                 [ancestry] => {"phylum":{"name":"Mollusca","taxon_concept_id":"2195"},"kingdom":{"name":"Animalia","taxon_concept_id":"1"}}
                 [resource_id] => 15
                 [resource_name] => EOL Group on Flickr
                 [cp_id] => 18
                 [cp_name] => Flickr: Encyclopedia of Life Images
                 [coll_id] => 176
            */
            //start writing
            if(!$headers_printed_already) {
                fwrite($FILE, implode("\t", array_keys($rec))."\n");
                $headers_printed_already = true;
            }
            fwrite($FILE, implode("\t", $rec)."\n");
        }
        fclose($FILE);
        
    }
    private function lookup_object_url($row, $type)
    {
        if($val = $row['object_url']) return $val;

        if($type != 'Text') {
            $url = str_replace("data_object_id", $row['data_object_id'], $this->url["eol_object"]);
            if($json = Functions::lookup_with_cache($url, $this->download_options)) {
                $obj = json_decode($json, true);
                if($val = @$obj['dataObjects'][0]['mediaURL']) return $val;
                elseif($val = @$obj['dataObjects'][0]['eolMediaURL']) return $val;
            }
        }
        return false;
    }
    private function lookup_data_type($data_type_id)
    {
        $sql = "SELECT t.label as data_type from translated_data_types t where t.language_id = 152 and t.data_type_id = $data_type_id";
        $result = $this->mysqli->query($sql);
        if($result && $row=$result->fetch_assoc()) {
            return $row['data_type'];
        }
        return false;

    }
    private function get_tc_id_using_dotc($do_id)
    {
        $sql = "SELECT dotc.* from data_objects_taxon_concepts dotc where dotc.data_object_id = $do_id";
        $result = $this->mysqli->query($sql);
        if($result && $row=$result->fetch_assoc()) {
            return $row['taxon_concept_id'];
        }
        return false;
    }
    private function get_tc_id_from_udo($do_id)
    {
        $sql = "SELECT udo.* ,concat(ifnull(u.given_name,''), ' ', ifnull(u.family_name,''), ' ', if(u.username is not null, concat('(',u.username,')'), '')) as user_name
        from users_data_objects udo 
        left join users u on (udo.user_id = u.id)
        where udo.data_object_id = $do_id";
        $result = $this->mysqli->query($sql);
        if($result && $row=$result->fetch_assoc()) {
            // print_r($row);
            return array('taxon_concept_id' => $row['taxon_concept_id'], 'udo_username' => $row['user_name'], 'udo_userid' => $row['user_id']);
        }
    }
    private function generate_ancestry_as_json($info)
    {
        $a = array();
        if($val = @$info['ancestry']) {
            foreach($val as $rec) {
                $a[$rec['rank']] = array("name" => $rec['taxon_name'], "taxon_concept_id" => $rec['taxon_concept_id']);
            }
        }
        // print_r($a); exit;
        if($a) return json_encode($a);
    }
    private function lookup_resource_info($row, $DOHE_tbl = 'data_objects_harvest_events_curation')
    {
        $do_id = $row['data_object_id'];
        if(@$row['ch_object_type'] == 'users_submitted_text') {
            $a = self::get_tc_id_from_udo($do_id); //udo - users_data_objects
            return array('resource_name' => $a['udo_username'], 'resource_id' => $a['udo_userid']);
        }
        else {
            $sql = "SELECT dohe.*, he.resource_id, r.content_partner_id as cp_id, r.title as resource_name, r.collection_id as coll_id, cp.full_name as cp_name
            from $DOHE_tbl dohe
            left join harvest_events he on (dohe.harvest_event_id = he.id)
            left join resources r on (he.resource_id = r.id)
            left join content_partners cp on (r.content_partner_id = cp.id)
            where dohe.data_object_id = $do_id";
            $result = $this->mysqli->query($sql);
            if($result && $row2=$result->fetch_assoc()) {
                return array('resource_name' => $row2['resource_name'], 'resource_id' => $row2['resource_id'], 'cp_name' => $row2['cp_name'], 'cp_id' => $row2['cp_id'], 'coll_id' => $row2['coll_id']);
            }
            else { //bases here: https://eol-jira.bibalex.org/browse/DATA-1740?focusedCommentId=62313&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-62313
                if($res_info = self::get_resource_info_last_resort($do_id)) {
                    print_r($res_info);
                    return $res_info;
                }
                else {
                    if($res_info = self::get_resource_info_using_obj_url($row['obj_url'])) {
                        print_r($res_info);
                        return $res_info;
                    }
                    else {
                        // print_r($row);
                        // exit("\n--Cannot find resource anymore for this do_id [$do_id]--\n");
                    }
                }
            }
        }
        return array('resource_name' => 'Cannot find resource anymore.');
    }
    private function get_resource_info_last_resort($do_id) //3286228
    {
        //step 1 get all do_ids from page: <li><a href="/data_objects/14375915">2011-12-03 08:35:47 UTC</a></li>
        if($html = Functions::lookup_with_cache("http://www.eol.org/data_objects/$do_id", $this->download_options)) {
            if(preg_match_all("/\"\/data_objects\/(.*?)\"/ims", $html, $arr)) {
                $do_ids = $arr[1];
                $do_ids[] = $do_id;
                $do_ids = array_reverse($do_ids);
                print_r($do_ids);
                $DOHE_tbl = 'data_objects_harvest_events';
                foreach($do_ids as $do_id) {
                    $sql = "SELECT dohe.*, he.resource_id, r.content_partner_id as cp_id, r.title as resource_name, r.collection_id as coll_id, cp.full_name as cp_name
                    from $DOHE_tbl dohe
                    left join harvest_events he on (dohe.harvest_event_id = he.id)
                    left join resources r on (he.resource_id = r.id)
                    left join content_partners cp on (r.content_partner_id = cp.id)
                    where dohe.data_object_id = $do_id";
                    $result = $this->mysqli->query($sql);
                    if($result && $row2=$result->fetch_assoc()) {
                        echo "\n OK $do_id";
                        return array('resource_name' => $row2['resource_name'], 'resource_id' => $row2['resource_id'], 'cp_name' => $row2['cp_name'], 'cp_id' => $row2['cp_id'], 'coll_id' => $row2['coll_id']);
                    }
                    else echo "\n not OK $do_id";
                }
            }
        }
        return false;
    }
    private function tbl_from_Jen() //https://eol-jira.bibalex.org/browse/DATA-1740?focusedCommentId=62313&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-62313
    {
        // obj_url_domain resource_ID
        $temp = "www.biolib.cz 11, caliban.mpiz-koeln.mpg.de 12, farm1.staticflickr.com 15, farm2.staticflickr.com 15, farm3.static.flickr.com 15, farm3.staticflickr.com 15, farm4.static.flickr.com 15, farm4.staticflickr.com 15, farm5.static.flickr.com 15, farm5.staticflickr.com 15, farm6.static.flickr.com 15, farm6.staticflickr.com 15, farm7.staticflickr.com 15, farm8.staticflickr.com 15, farm9.static.flickr.com 15, farm9.staticflickr.com 15, mushroomobserver.org 16, pinkava.asu.edu 19, animaldiversity.ummz.umich.edu 22, www.antweb.org 24, images.marinespecies.org 26, www.biopix.com 31, neotropicalfishes.lifedesks.org 35, www.neotropicalfishes.org 35, plants.usda.gov 37, www.fishbase.us 42, www.findingspecies.org 43, eolspecies.lifedesks.org 59, www.tropicallichens.net 69, upload.wikimedia.org 71, indianadunes.lifedesks.org 72, phil.cdc.gov 79, www.morphbank.net 83, alpheidae.lifedesks.org 92, ampullariidae.lifedesks.org 92, eolinterns.lifedesks.org 96, conabioweb.conabio.gob.mx 100, www.sharkeylab.org 103, www.biodiversity.com.au 106, www.habitas.org.uk 107, plantsoftibet.lifedesks.org 114, www.ascidians.com 116, continenticola.lifedesks.org 118, odonata.lifedesks.org 122, scarabaeinae.lifedesks.org 124, sacoglossa.lifedesks.org 129, projects.bebif.be 138, africanamphibians.lifedesks.org 139, chess.lifedesks.org 144, syrphidae.lifedesks.org 147, carex.lifedesks.org 154, vignea.lifedesks.org 155, canopy.lifedesks.org 166, www.bioimages.org.uk 168, archive.serpentproject.com 170, sipuncula.lifedesks.org 174, www.arcodiv.org 181, turbellaria.umaine.edu 185, www.fishwisepro.com 190, compositae.lifedesks.org 199, bioimages.vanderbilt.edu 200, mczbase.mcz.harvard.edu 201, tolweb.org 204, ebivalvia.lifedesks.org 213, terrslugs.lifedesks.org 215, images.mobot.org 218, diptera.myspecies.info 220, www.wallawalla.edu 221, mormyrids.lifedesks.org 222, annelida.lifedesks.org 231, marineinvaders.lifedesks.org 232, snakesoftheworld.lifedesks.org 234, polycladida.lifedesks.org 235, mexinverts.lifedesks.org 236, echinoderms.lifedesks.org 243, neotropnathistory.lifedesks.org 246, korupplants.lifedesks.org 248, salamandersofchina.lifedesks.org 250, www.discoverlife.org 252, liv.ac.uk 256, apoidea.lifedesks.org 258, britishbryozoans.myspecies.info 268, mothphotographersgroup.msstate.edu 270, avesamericanas.lifedesks.org 273, multimedia.inbio.ac.cr 276, www.nhm.ac.uk 281, cephaloleia.lifedesks.org 287, peet.tamu.edu 288, opisthobranchia.lifedesks.org 294, afrotropicalbirds.lifedesks.org 304, www.zimbabweflora.co.zw 327, www.boldsystems.org 329, philbreo.lifedesks.org 331, lifedesk.bibalex.org 335, pngbirds.myspecies.info 363, www.moroccoherps.com 370, butterfliesofamerica.com 374, www.butterfliesandmoths.org 374, www.planetscott.com 380, content.lib.washington.edu 388, www.ecomare.nl 414, lh3.ggpht.com 430, lh4.ggpht.com 430, lh5.ggpht.com 430, lh6.ggpht.com 430, sphotos-a.xx.fbcdn.net 430, sphotos-b.xx.fbcdn.net 430, static.inaturalist.org 430, fbcdn-sphotos-b-a.akamaihd.net 430, scontent-a.xx.fbcdn.net 430, scontent-b.xx.fbcdn.net 430, www.westafricanplants.senckenberg.de 435, caterpillars.lifedesks.org 485, pamba.strandls.com 520, fishdb.sinica.edu.tw 547, entnemdept.ufl.edu 642, erast.ut.ee 677, geokogud.info 677, ubio.org 679, www.chaloklum-diving.com 729, www.obs-vlfr.fr 742, neotropical-pollination.myspecies.info 756, oceandatacenter.ucsc.edu 781, inpn.mnhn.fr 785, www.femorale.com 793, eoldata.taibif.tw 802, phthiraptera.info 884, i1.treknature.com 895, biogeodb.stri.si.edu 902
        , calphotos.berkeley.edu 330 267, www.illinoiswildflowers.info 34 143, data.rbge.org.uk 348 336
        , csdb.ioz.ac.cn 385 412 413 416
        , phytokeys.pensoft.net 826 191
        , pwt.pensoft.net 829 20 826 191 830 492 831 552 553 833 554 555 832 556
        , www.pensoft.net 829 20 826 191 830 492 831 552 553 833 554 555 832 556
        , collections.mnh.si.edu 891 120 176 341 342 343 344 346
        , 1.bp.blogspot.com 424, 2.bp.blogspot.com 424, 3.bp.blogspot.com 424, 4.bp.blogspot.com 424, 89.26.108.66 660";
        $temp = explode(",", $temp);
        $temp = array_map('trim', $temp);
        $final = array();
        foreach($temp as $t) {
            $arr = explode(" ", $t);
            // print_r($arr);
            $index = $arr[0];
            $arr[0] = null;
            $arr = array_filter($arr); //remove null values
            $arr = array_values($arr); //reindex key
            $final[$index] = $arr;
        }
        return $final;
    }
    private function get_resource_info_using_resource_id($resource_id)
    {
        $sql = "SELECT r.content_partner_id as cp_id, r.title as resource_name, r.collection_id as coll_id, cp.full_name as cp_name
        from resources r left join content_partners cp on (r.content_partner_id = cp.id) where r.id = $resource_id";
        $result = $this->mysqli->query($sql);
        if($result && $row=$result->fetch_assoc()) {
            return array('resource_name' => $row['resource_name'], 'resource_id' => $resource_id, 'cp_name' => $row['cp_name'], 'cp_id' => $row['cp_id'], 'coll_id' => $row['coll_id']);
        }
        return false;
    }
    private function get_domain_from_url($url)
    {
        $temp = parse_url($url);
        return @$temp['host'];
    }
    private function get_resource_info_using_obj_url($url)
    {
        $arr_domain_resource_ids = self::tbl_from_Jen();
        $domain = self::get_domain_from_url($url);
        if($resource_id = @$arr_domain_resource_ids[$domain][0]) return self::get_resource_info_using_resource_id($resource_id);
        return false;
    }
    private function get_tc_id_using_do_id($do_id)
    {
        $url = str_replace("data_object_id", $do_id, $this->url["eol_object"]);
        if($json = Functions::lookup_with_cache($url, $this->download_options)) {
            $obj = json_decode($json, true);
            if($val = @$obj['identifier']) return $val; //this is the taxon_concept_id
        }
        return false;
    }
    //======================================================================================================================================
    public function start_user_added_text() //udo = 23848 | published = 13143
    {
        // -- udo.*, tcpe.hierarchy_entry_id, dt.schema_value
        // -- , ii.schema_value
        // -- , tii.label, doii.info_item_id
        // -- LEFT JOIN info_items ii ON (dotoc.toc_id = ii.toc_id)
        // -- LEFT JOIN data_objects_info_items doii ON (udo.data_object_id = doii.data_object_id)
        // -- LEFT JOIN translated_info_items tii ON (doii.info_item_id = tii.info_item_id)
        $sql = "SELECT udo.data_object_id, udo.user_id, udo.taxon_concept_id
        , if(l.iso_639_1 is not null, l.iso_639_1, '') as iso_lang
        , concat(ifnull(u.given_name,''), ' ', ifnull(u.family_name,''), ' ', if(u.username is not null, concat('(',u.username,')'), '')) as user_name
        , lic.title as license
        , d.data_rating, d.description, d.data_type_id, d.rights_statement, d.rights_holder, d.bibliographic_citation, d.object_title as title, d.location, d.source_url
        , d.created_at, d.updated_at
        , ttoc.label as subject , tdt.label as data_type
        , dotoc.toc_id
        FROM users_data_objects udo
        LEFT JOIN data_objects d ON (udo.data_object_id = d.id) 
        LEFT JOIN data_types dt ON (d.data_type_id = dt.id)
        LEFT JOIN users u ON (udo.user_id = u.id)
        LEFT JOIN taxon_concept_preferred_entries tcpe ON (udo.taxon_concept_id = tcpe.taxon_concept_id)
        LEFT JOIN languages l ON (d.language_id=l.id)
        LEFT JOIN licenses lic ON  (d.license_id = lic.id)
        LEFT JOIN data_objects_table_of_contents dotoc ON (udo.data_object_id = dotoc.data_object_id)
        LEFT JOIN translated_table_of_contents ttoc ON (dotoc.toc_id = ttoc.table_of_contents_id)
        LEFT JOIN translated_data_types tdt ON (d.data_type_id = tdt.data_type_id)
        where (ttoc.language_id = 152 OR ttoc.language_id is null) and
              (tdt.language_id = 152 OR tdt.language_id is null) and d.published = 1 and udo.visibility_id = 1";
        // $sql .= " and udo.user_id = 20470 and d.id = 23862470";
        // $sql .= " and d.id = 18679745"; //4926441"; //10194243"; //29733168"; //22464391"; //27221235"; //29321098"; //"; //32590447";//"; //10523111";//4926441";
        // $sql .= " limit 10"; //16900774 data_object_id with associated taxa
        $result = $this->mysqli->query($sql);
        // echo "\n". $result->num_rows . "\n"; exit;
        $recs = array();
        while($result && $row=$result->fetch_assoc()) {
            // if(in_array($row['data_object_id'], array(22464391))) continue;
            
            $info = self::get_taxon_info($row['taxon_concept_id']);
            $objects = $row;
            
            $objects['taxon_concept_id'] = array($row['taxon_concept_id']);
            $associated_tc_ids = self::check_for_added_association_for_this_object($row['data_object_id']);
            if($associated_tc_ids) $objects['taxon_concept_id'] = array_merge($objects['taxon_concept_id'], $associated_tc_ids);
            
            $temp = self::get_object_info($row);
            $objects = array_merge($objects, $temp);
            $objects['refs'] = self::get_refs($row['data_object_id']);
            $recs[] = array(
            // 'iso_lang' => $row['iso_lang']
            // , 'lang_native' => $row['lang_native']
            // , 'lang_english' => $row['lang_english']
            'user_name' => $row['user_name']
            , 'user_id' => $row['user_id']
            , 'taxon_name' => $info['taxon_name']
            , 'taxon_id' => $row['taxon_concept_id']
            , 'rank' => $info['rank']
            , 'he_parent_id' => $info['he_parent_id']
            , 'objects' => $objects
            , 'ancestry' => $info['ancestry'] //temporarily commented
            );
            
            if($associated_tc_ids) {
                $this->debug['obj with associated taxa'][$row['data_object_id']] = '';
                foreach($associated_tc_ids as $tc_id) {
                    $info = self::get_taxon_info($tc_id);
                    $recs[] = array(
                    'taxon_name'  => $info['taxon_name']
                    , 'taxon_id'    => $tc_id
                    , 'rank'        => $info['rank']
                    , 'he_parent_id'=> $info['he_parent_id']
                    , 'ancestry'    => $info['ancestry'] //temporarily commented
                    );
                }
            }
            
        }
        // print_r($recs); //exit("\n".count($recs)."\n");
        // self::write_to_text_comnames($recs);
        self::gen_dwca_resource($recs);
        print_r($this->debug);
    }
    private function check_for_added_association_for_this_object($data_object_id)
    {
        $sql = "select distinct cal.taxon_concept_id from eol_logging_production.curator_activity_logs cal where cal.activity_id = 48 and cal.target_id = $data_object_id";
        /* -- add_association activity id = 48
           -- here target_id is data_object_id */
        $tc_ids = array();
        $result = $this->mysqli->query($sql);
        while($result && $row=$result->fetch_assoc()) {
            $tc_ids[] = $row['taxon_concept_id'];
        }
        return $tc_ids;
    }
    private function get_refs($data_object_id)
    {
        $final = array();
        $sql = "select r.* FROM data_objects_refs dor JOIN refs r ON (dor.ref_id = r.id) where dor.data_object_id = $data_object_id and r.published = 1";
        $result = $this->mysqli->query($sql);
        while($result && $row=$result->fetch_assoc()) {
            /*
            `provider_mangaed_id` varchar(255) DEFAULT NULL,
            `volume` varchar(50) DEFAULT NULL,
            `edition` varchar(50) DEFAULT NULL,
            `publisher` varchar(255) DEFAULT NULL,
            `user_submitted` tinyint(1) NOT NULL DEFAULT '0',
            `visibility_id` tinyint(3) unsigned NOT NULL DEFAULT '0',
            `published` tinyint(3) unsigned NOT NULL DEFAULT '0',
            */
            $rec = array();
            $rec["identifier"] = $row['id'];
            $rec["publicationType"] = '';
            $rec["full_reference"] = $row['full_reference'];
            $rec["primaryTitle"] = '';
            $rec["title"] = $row['title'];
            $rec["pages"] = $row['pages'];
            $rec["pageStart"] = $row['page_start'];
            $rec["pageEnd"] = $row['page_end'];
            $rec["volume"] = $row['volume'];
            $rec["edition"] = $row['edition'];
            $rec["publisher"] = $row['publisher'];
            $rec["authorList"] = $row['authors'];
            $rec["editorList"] = $row['editors'];
            $rec["created"] = $row['publication_created_at'];
            $rec["language"] = $row['language_id'];
            $rec["uri"] = "";
            $rec["doi"] = "";
            $rec["localityName"] = "";
            if($rec['full_reference']) $final[] = $rec;
        }
        return $final;
    }
    private function get_object_info($row)
    {
        $final = array();
        $final['subjectURI'] = self::get_subjectURI($row);
        // print_r($row);
        return $final;
    }
    private function get_subjectURI($row)
    {
        if($row['toc_id'] == 322) return "http://eol.org/schema/eol_info_items.xml#FossilHistory";
        
        if($row['toc_id']) {
            $sql = "SELECT ii.schema_value as subjectURI from info_items ii where ii.toc_id = ".$row['toc_id'];
            /* works OK but doesn't detect if > 1 row is returned
            if($val = $this->mysqli->select_value($sql)) return $val;
            else {
                echo("\n\nInvestigate no toc_id\n");
                print_r($row); exit;
            } */
            $result = $this->mysqli->query($sql);
            // echo "\n".count($result)."\n"; 
            if(count($result) > 1) {
                echo("\n\nInvestigate > 1 subjectURI \n");
                print_r($row); print_r($result); exit;
            }
            while($result && $row2=$result->fetch_assoc()) {
                if($val = $row2['subjectURI']) return $val;
            }
            if(!$result) {
                echo("\n\nInvestigate no subjectURI found\n");
                print_r($row); exit;
            }
        }


        // http://www.eol.org/voc/table_of_contents#FossilHistory (322)
        // http://eol.org/schema/eol_info_items.xml#FossilHistory
        if($row['subject'] == "Fossil History") return "http://eol.org/schema/eol_info_items.xml#FossilHistory";
        
        //2nd option if above didn't get anything
        //loop to info_items.schema_value and find #Education
        $sql = "SELECT ii.schema_value from info_items ii";
        $result = $this->mysqli->query($sql);
        // echo "\n".$row['subject']."\n"; //exit;
        while($result && $row2=$result->fetch_assoc()) {
            if(preg_match("/\\#".$row['subject']."(.*?)xxx/ims", $row2['schema_value']."xxx", $arr)) return $row2['schema_value'];
        }

        echo("\n\nInvestigate STILL no subjectURI found\n");
        print_r($row); exit;
        
    }
    //select if(field_a is not null, field_a, field_b) --- if then else in MySQL
    public function start_user_preferred_comnames() //total recs for agents_synonyms: 113283
    {
        $sql = "select asy.synonym_id, n.id as name_id, n.string as common_name, asy.agent_id, u.given_name, u.family_name, s.hierarchy_entry_id, s.vetted_id, s.preferred
        , he.taxon_concept_id
        , tv.label as vettedness
        , if(l.iso_639_1 is not null, l.iso_639_1, '') as iso_lang, l.source_form as lang_native, s3.label as lang_english
        , concat(ifnull(u.given_name,''), ' ', ifnull(u.family_name,''), ' ', if(u.username is not null, concat('(',u.username,')'), '')) as user_name, u.id as user_id 
        from agents_synonyms asy
        left outer join eol_logging_production.synonyms s on (asy.synonym_id = s.id)
        left outer join eol_logging_production.names n on (s.name_id = n.id)
        left outer join agents a on (asy.agent_id = a.id)
        left outer JOIN users u ON (asy.agent_id = u.agent_id)
        left outer join hierarchy_entries he on (s.hierarchy_entry_id = he.id)
        left outer join translated_vetted tv on (s.vetted_id = tv.vetted_id)
        LEFT JOIN eol_v2.translated_languages s3 ON (s.language_id=s3.original_language_id)
        LEFT JOIN languages l ON (s.language_id=l.id)
        where (tv.language_id = 152 OR tv.language_id is null) and (s3.language_id = 152 OR s3.language_id is null)";
        // $sql .= " and he.taxon_concept_id is null"; //just for testing asy.synonym_id that is no longer existing in synonyms table
        // $sql .= ' and n.string like "atlantic cod%"';
        // $sql .= ' and n.string like "white-throated sparrow%"';
        // $sql .= ' and n.string like "brown bear%"';
        // $sql .= ' and n.string = "Karhu"';
        $sql .= " order by n.string, s3.label";
        // $sql .= " limit 1000";
        $result = $this->mysqli->query($sql);
        // echo "\n". $result->num_rows . "\n"; exit;
        $recs = array();
        while($result && $row=$result->fetch_assoc()) {
            $row = array_map('trim', $row);
            if(!isset($recs[$row['name_id']])) {
                if(!trim($row['common_name'])) continue;
                $info = self::get_taxon_info($row['taxon_concept_id']);
                $recs[$row['name_id']] = array('common_name' => $row['common_name'], 'preferred' => $row['preferred'], 'iso_lang' => $row['iso_lang'], 'lang_native' => $row['lang_native']
                , 'lang_english' => $row['lang_english']
                , 'user_name' => $row['user_name']
                , 'user_id' => $row['user_id']
                , 'taxon_name' => @$info['taxon_name']
                , 'taxon_id' => $row['taxon_concept_id']
                , 'rank' => @$info['rank']
                , 'he_parent_id' => @$info['he_parent_id']
                , 'ancestry' => $info['ancestry'] //working OK but just commented for now
                );
                echo "\n".$recs[$row['name_id']]['common_name'];
            }
        }
        // print_r($recs);
        self::write_to_text_comnames($recs);
        echo "\n". $result->num_rows . "\n"; //exit;
    }

    public function start_user_added_comnames() //total records: 87127
    {
        $sql = "select cal.user_id, cal.taxon_concept_id, cal.activity_id, cal.target_id, cal.changeable_object_type_id
        , s.name_id, s.language_id, n.string as common_name, s.preferred, concat(ifnull(u.given_name,''), ' ', ifnull(u.family_name,''), ' (', ifnull(u.username,''), ')') as user_name, s3.label
        , if(l.iso_639_1 is not null, l.iso_639_1, '') as iso_lang, l.source_form as lang_native, s3.label as lang_english
        from eol_logging_production.curator_activity_logs cal 
        LEFT JOIN eol_logging_production.synonyms s on (cal.target_id=s.id)
        LEFT JOIN eol_logging_production.names n on (s.name_id=n.id)
        LEFT JOIN users u on (cal.user_id=u.id)
        LEFT JOIN eol_v2.translated_languages s3 ON (s.language_id=s3.original_language_id)
        LEFT JOIN languages l ON (s.language_id=l.id)
        where cal.activity_id = 61 and s.name_id is not null and s3.language_id = 152";
        // $sql .= " and cal.user_id = 20470";
        $sql .= " order by n.string";
        // $sql .= " limit 5";
        
        // $m = 10000;
        // $sql .= " limit $m";
        // $sql .= " LIMIT $m OFFSET ".$m;
        // $sql .= " LIMIT $m OFFSET ".$m*2;
        // $sql .= " LIMIT $m OFFSET ".$m*3;
        // $sql .= " LIMIT $m OFFSET ".$m*4;
        // $sql .= " LIMIT $m OFFSET ".$m*5;
        // $sql .= " LIMIT $m OFFSET ".$m*6;
        // $sql .= " LIMIT $m OFFSET ".$m*7;
        // $sql .= " LIMIT $m OFFSET ".$m*8;
        
        // investigate 46326157 46326105
        // and cal.taxon_concept_id = 46326157
        // and cal.user_id = 20470 
        // and cal.taxon_concept_id = 209718 #922651 #209718
        // 61 add_common_name
        // 47 vetted_common_name
        // 73 trust_common_name
        // 26 added_common_name --- NO RECORD 4454117 (no supercedure) 382622 (with supercedure_id)
        $result = $this->mysqli->query($sql);
        // echo "\n". $result->num_rows . "\n"; exit;
        $recs = array();
        while($result && $row=$result->fetch_assoc()) {
            if(!isset($recs[$row['name_id']])) {
                $info = self::get_taxon_info($row['taxon_concept_id']);
                $recs[$row['name_id']] = array('common_name' => $row['common_name'], 'preferred' => $row['preferred'], 'iso_lang' => $row['iso_lang'], 'lang_native' => $row['lang_native']
                , 'lang_english' => $row['lang_english']
                , 'user_name' => $row['user_name']
                , 'user_id' => $row['user_id']
                , 'taxon_name' => $info['taxon_name']
                , 'taxon_id' => $row['taxon_concept_id']
                , 'rank' => $info['rank']
                , 'he_parent_id' => $info['he_parent_id']
                , 'ancestry' => $info['ancestry']
                );
            }
        }
        // print_r($recs); //exit("\n".count($recs)."\n");
        self::write_to_text_comnames($recs);
        self::gen_dwca_resource($recs);
    }
    private function gen_dwca_resource($recs)
    {
        /* 
        [common_name] => Bobbit worm
        [iso_lang] => en
        [lang_native] => English
        [lang_english] => English
        [user_name] => Jennifer Hammock (jhammock)
        [user_id] => 20470
        [taxon_name] => Eunice aphroditois
        [taxon_id] => 404312
        [rank] => species
        [he_parent_id] => 52691614
        */
        foreach($recs as $rec) {
            $taxon = new \eol_schema\Taxon();
            $taxon->taxonID         = $rec['taxon_id'];
            $taxon->scientificName  = $rec['taxon_name'];
            $taxon->taxonRank         = $rec['rank'];
            foreach($rec['ancestry'] as $a) {
                /* 
                [he_id] => 52691614
                [taxon_name] => Eunice
                [taxon_concept_id] => 50908
                [he_parent_id] => 52691523
                [rank] => genus
                */
                if(in_array($a['rank'], array('kingdom','phylum','class','order','family','genus'))) {
                    $taxon->$a['rank'] = ucfirst($a['taxon_name']);
                }
            }
            // $taxon->kingdom         = $t['dwc_Kingdom'];
            // $taxon->phylum          = $t['dwc_Phylum'];
            // $taxon->class           = $t['dwc_Class'];
            // $taxon->order           = $t['dwc_Order'];
            // $taxon->family          = $t['dwc_Family'];
            // $taxon->genus           = $t['dwc_Genus'];
            // if($agent_ids = self::create_agent_extension($rec)) $taxon->agentID = implode("; ", $agent_ids);

            // $taxon->recordedBy = "eli"; - not working
            if(!isset($this->taxon_ids[$taxon->taxonID])) {
                $this->archive_builder->write_object_to_file($taxon);
                $this->taxon_ids[$taxon->taxonID] = '';
            }
            
            if($common_name = @$rec['common_name']) {
                $v = new \eol_schema\VernacularName();
                $v->taxonID         = $taxon->taxonID;
                $v->vernacularName  = $common_name;
                $v->language        = $rec['iso_lang'];
                $v->taxonRemarks    = "Contributed by: ".$rec['user_name']." (".$rec['user_id'].").";
                $v->source          = "http://www.eol.org/users/".$rec['user_id'];
                $v->source          = "http://eol.org/pages/".$taxon->taxonID."/names/common_names";
                // if($agent_ids = self::create_agent_extension($rec)) $v->agentID = implode("; ", $agent_ids); - not working
                $this->archive_builder->write_object_to_file($v);
            }
            
            if($obj = @$rec['objects'])
            {   /* [objects] => Array(
                                    [data_object_id] => 1893733
                                    [user_id] => 11
                                    [taxon_concept_id] => 918848 -- now is an array type
                                    [iso_lang] => en
                                    [user_name] => Paddy Patterson (paddy)
                                    [license] => public domain
                                    [data_rating] => 2.5
                                    [description] => This species has been reported on several continents, and may be presumed to have a world-wide distribution.
                                    [data_type_id] => 3
                                    [subject] => Distribution
                                    [data_type] => Text
                                    [toc_id] => 309
                                    [subjectURI] => http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution
                                )
                */
                if($obj['data_type'] != "Text") {
                    echo "\n\nObject not Text\n";
                    print_r($rec); exit;
                }

                $desc = self::format_str($obj['description'], $obj['data_object_id']);
                
                if(!$desc) continue;
                $mr = new \eol_schema\MediaResource();
                $mr->taxonID        = implode("; ", $obj['taxon_concept_id']);
                $mr->identifier     = $obj['data_object_id'];
                $mr->type           = "http://purl.org/dc/dcmitype/Text";
                $mr->language       = $obj['iso_lang'];
                $mr->format         = "text/html";
                $mr->furtherInformationURL = $obj['source_url']; //"http://www.eol.org/data_objects/".$obj['data_object_id'];
                // $mr->accessURI      = '';
                // $mr->thumbnailURL   = '';
                
                $mr->CVterm         = $obj['subjectURI'];
                $mr->Owner          = $obj['rights_holder'];
                $mr->rights         = $obj['rights_statement'];
                $mr->title          = $obj['title'];
                $mr->UsageTerms     = self::get_license_url($obj['license']);
                // $mr->audience       = 'Everyone';
                
                /* working - good for debug ------------------------------------------------------
                $filename = CONTENT_RESOURCE_LOCAL_PATH ."eli.html";
                $FILE = Functions::file_open($filename, 'w');
                fwrite($FILE, $desc);
                fclose($FILE);
                $desc = file_get_contents($filename);
                */
                
                $mr->description    = $desc;
                $mr->LocationCreated = $obj['location'];
                $mr->bibliographicCitation = $obj['bibliographic_citation'];
                $mr->Rating                = $obj['data_rating'];
                $mr->CreateDate = $obj['created_at'];
                $mr->modified = $obj['updated_at'];

                if($reference_ids = self::create_ref_extension($obj['refs']))  $mr->referenceID = implode("; ", $reference_ids);
                if($agent_ids = self::create_agent_extension($obj)) $mr->agentID = implode("; ", $agent_ids);
            
                if(!isset($this->object_ids[$mr->identifier])) {
                    $this->archive_builder->write_object_to_file($mr);
                    $this->object_ids[$mr->identifier] = '';
                }
            }
        }
        
        $this->archive_builder->finalize(true);
        return;
    }
    private function format_str($str, $data_object_id)
    {
        // if(stripos($str, "style=") !== false) $this->debug['data_object_id'][$data_object_id] = ''; //just debug
        $str = str_replace(array("\n", "\t", "\r", chr(9), chr(10), chr(13)), " ", $str);
        $str = Functions::remove_whitespace($str);
        if(preg_match_all("/style=\"(.*?)\"/ims", $str, $arr)) {
            foreach($arr[1] as $remove) {
                $str = str_ireplace('style="'.$remove.'"', "", $str);
            }
        }
        
        // <!--[if gte mso 9]><xml> <o:OfficeDocumentSettings> <o:AllowPNG/> </o:OfficeDocumentSettings> </xml><![endif]--> 
        if(preg_match_all("/<!--(.*?)-->/ims", $str, $arr)) {
            foreach($arr[1] as $remove) {
                $str = str_ireplace('<!--'.$remove.'-->', "", $str);
            }
        }
        
        // e.g. http://www.eol.org/data_objects/22464391 | http://www.eol.org/data_objects/27431054
        if(stripos($str, 'src="data:image') !== false) { //string is found
            if(preg_match_all("/src=\"data:image(.*?)\"/ims", $str, $arr)) {
                foreach($arr[1] as $remove) {
                    $str = str_ireplace('src="data:image'.$remove.'"', "", $str);
                }
            }
        }
        return trim($str);
    }
    private function remove_utf8_bom($text)
    {
        $bom = pack('H*','EFBBBF');
        $text = preg_replace("/^$bom/", '', $text);
        /* another option:
        text = str_replace("\xEF\xBB\xBF",'',$text); 
        */
        return $text;
    }
    
    private function create_ref_extension($refs)
    {
        $reference_ids = array();
        foreach($refs as $rec)
        {
            $r = new \eol_schema\Reference();
            $fields = array_keys($rec);
            foreach($fields as $field) {
                $r->$field = $rec[$field];
            }
            $reference_ids[] = $r->identifier;
            if(!isset($this->reference_ids[$r->identifier])) {
                $this->reference_ids[$r->identifier] = '';
                $this->archive_builder->write_object_to_file($r);
            }
            /* just for reference...
            $rec["identifier"] = $row['id'];
            $rec["publicationType"] = 
            $rec["full_reference"] = $row['full_reference'];
            $rec["primaryTitle"] = 
            $rec["title"] = $row['title'];
            $rec["pages"] = $row['pages'];
            $rec["pageStart"] = $row['page_start'];
            $rec["pageEnd"] = $row['page_end'];
            $rec["volume"] = $row['volume'];
            $rec["edition"] = $row['edition'];
            $rec["publisher"] = $row['publisher'];
            $rec["authorList"] = $row['authors'];
            $rec["editorList"] = $row['editors'];
            $rec["created"] = $row['publication_created_at'];
            $rec["language"] = $row['language_id']
            $rec["uri"] = "";
            $rec["doi"] = "";
            $rec["localityName"] = "";
            */
        }
        return $reference_ids;
    }
    private function get_license_url($license) //e.g. public domain
    {
        if($license == "public domain") return "http://creativecommons.org/licenses/publicdomain/";
        $sql = "SELECT l.source_url from licenses l where l.title = '".$license."' and l.source_url is not null";
        if($val = $this->mysqli->select_value($sql)) return $val;
        elseif($license == "all rights reserved") return $license;
        else exit("\n\nInvestigate no license [$license]\n");
    }
    private function create_agent_extension($rec)
    {
        // [user_name] => Jennifer Hammock (jhammock)
        // [user_id] => 20470
        $r = new \eol_schema\Agent();
        $r->term_name       = $rec['user_name'];
        $r->agentRole       = 'author';
        $r->identifier      = $rec['user_id'];
        $r->term_homepage   = "http://www.eol.org/users/".$rec['user_id'];
        $agent_ids[] = $r->identifier;
        if(!isset($this->agent_ids[$r->identifier])) {
           $this->agent_ids[$r->identifier] = '';
           $this->archive_builder->write_object_to_file($r);
        }
        return $agent_ids;
    }
    
    private function get_ancestry($he_id)
    {
        $ancestry = array();
        while(true) {
            echo "\n querying he_id [$he_id]";
            $sql = "SELECT n.string as final_name, he.rank_id, h.label, he.id as he_id, he.parent_id as he_parent_id, r.label as rank, he.ancestry, he.lft, he.rgt, he.name_id, he.taxon_concept_id
            FROM hierarchy_entries he
            left outer JOIN eol_logging_production.names n ON (he.name_id=n.id)
            left outer JOIN hierarchies h ON (he.hierarchy_id=h.id)
            LEFT outer JOIN translated_ranks r ON (he.rank_id=r.rank_id)
            WHERE r.language_id = 152 and he.id = $he_id and he.vetted_id = 5";
            $result = $this->mysqli->query($sql);
            $new_he_id = false;
            while($result && $row=$result->fetch_assoc()) {
                $info = array('he_id' => $row['he_id'], 'taxon_name' => ucfirst($row['final_name']), 'taxon_concept_id' => $row['taxon_concept_id'], 'he_parent_id' => $row['he_parent_id'], 'rank' => $row['rank']);
                $ancestry[] = $info;
                // print_r($info);
                $new_he_id = $row['he_parent_id'];
                echo "\n new he_id [$new_he_id]";
            }
            if($he_id != $new_he_id && $new_he_id) $he_id = $new_he_id;
            else break;
        }
        // print_r($ancestry); exit;
        return $ancestry;
    }
    private function get_taxon_info($taxon_id)
    {
        if(!$taxon_id) return array();
        if    ($rec = self::get_taxon_info_from_json($taxon_id)) return $rec;
        elseif($rec = self::query_taxon_info($taxon_id)) return $rec;
        /* debugging only
        $rec = self::query_taxon_info($taxon_id);
        return $rec;
        */
    }
    private function query_taxon_info($taxon_concept_id)
    {
        echo "\nquerying dbase...[$taxon_concept_id]";
        $sql = "SELECT tc.id, n.string, cf.string as final_name, he.rank_id, h.label, he.id as he_id, he.parent_id as he_parent_id, r.label as rank
                FROM taxon_concepts tc
                JOIN taxon_concept_preferred_entries pe ON (tc.id=pe.taxon_concept_id)
                JOIN hierarchy_entries he ON (pe.hierarchy_entry_id=he.id)
                JOIN eol_logging_production.names n ON (he.name_id=n.id)
                JOIN hierarchies h ON (he.hierarchy_id=h.id)
                LEFT JOIN canonical_forms cf ON (n.canonical_form_id=cf.id)
                LEFT JOIN translated_ranks r ON (he.rank_id=r.rank_id)
                WHERE tc.supercedure_id = 0
                AND tc.published = 1
                AND tc.id = $taxon_concept_id
                AND r.language_id = 152";
        $result = $this->mysqli->query($sql);
        while($result && $row=$result->fetch_assoc()) {
            $info = array('taxon_name' => $row['final_name'], 'taxon_concept_id' => $row['id'], 'he_parent_id' => $row['he_parent_id'], 'rank' => $row['rank']);
            if($info) $info['ancestry'] = self::get_ancestry($info['he_parent_id']);
            self::save_taxon_info_to_json($taxon_concept_id, $info);
            return self::get_taxon_info_from_json($taxon_concept_id);
        }
        
        //2nd option is supercedure_id
        if($supercedure_id = self::get_supercedure_id($taxon_concept_id)) {
            if($supercedure_id != $taxon_concept_id) return self::get_taxon_info($supercedure_id);
        }
        echo "\n[$taxon_concept_id get supercedure UN-SUCCESSFUL]\n";
        
        //3rd option
        $sql = "SELECT n.string as final_name, he.taxon_concept_id,
        he.rank_id, h.label, he.id as he_id, he.parent_id as he_parent_id, r.label as rank, he.ancestry, he.lft, he.rgt, he.name_id, he.guid
        FROM hierarchy_entries he
        left outer JOIN eol_logging_production.names n ON (he.name_id=n.id)
        left outer JOIN hierarchies h ON (he.hierarchy_id=h.id)
        LEFT outer JOIN translated_ranks r ON (he.rank_id=r.rank_id)
        WHERE r.language_id = 152 and he.taxon_concept_id = $taxon_concept_id and he.vetted_id = 5";
        $result = $this->mysqli->query($sql);
        while($result && $row=$result->fetch_assoc()) {
            $info = array('taxon_name' => $row['final_name'], 'taxon_concept_id' => $taxon_concept_id, 'he_parent_id' => $row['he_parent_id'], 'rank' => $row['rank']);
            if($info) $info['ancestry'] = self::get_ancestry($info['he_parent_id']);
            self::save_taxon_info_to_json($taxon_concept_id, $info);
            return self::get_taxon_info_from_json($taxon_concept_id);
        }
        echo "\n3rd option UN-SUCCESSFULL \n";
        
        //4th option
        $sql = "SELECT n.string as final_name, he.taxon_concept_id,
                he.rank_id, h.label, he.id as he_id, he.parent_id as he_parent_id, r.label as rank, he.ancestry, he.lft, he.rgt, he.name_id, he.guid
                FROM hierarchy_entries he
                left outer JOIN eol_logging_production.names n ON (he.name_id=n.id)
                left outer JOIN hierarchies h ON (he.hierarchy_id=h.id)
                LEFT outer JOIN translated_ranks r ON (he.rank_id=r.rank_id)
                WHERE (r.language_id = 152 or r.language_id is null) and he.taxon_concept_id = $taxon_concept_id"; // and he.vetted_id = 5
        $result = $this->mysqli->query($sql);
        while($result && $row=$result->fetch_assoc()) {
            $info = array('taxon_name' => $row['final_name'], 'taxon_concept_id' => $taxon_concept_id, 'he_parent_id' => $row['he_parent_id'], 'rank' => $row['rank']);
            if($info) $info['ancestry'] = self::get_ancestry($info['he_parent_id']);
            self::save_taxon_info_to_json($taxon_concept_id, $info);
            return self::get_taxon_info_from_json($taxon_concept_id);
        }
        echo "\n4th option UN-SUCCESSFULL \n";
        exit("\nInvestigate [$taxon_concept_id]\n");
    }
    private function get_supercedure_id($taxon_concept_id)
    {
        $orig = $taxon_concept_id;
        while(true) {
            $sql = "select * from taxon_concepts t where t.id = $taxon_concept_id";
            $result = $this->mysqli->query($sql);
            if($result && $row=$result->fetch_assoc()) {
                // print_r($row);
                $supercedure_id = $row['supercedure_id'];
                if($supercedure_id && $supercedure_id != 0) {
                    $taxon_concept_id = $supercedure_id;
                    echo "\n new tc_id [$taxon_concept_id]";
                }
                else break;
            }
            else break;
        }
        echo("\nfrom: [$orig] to final: tc_id [$taxon_concept_id]\n");
        return $taxon_concept_id;
    }
    private function save_taxon_info_to_json($taxon_id, $info)
    {
        echo "\nsaving to json...";
        $json = json_encode($info);
        $main_path = $this->path['temp_dir'];
        $md5 = md5($taxon_id);
        $cache1 = substr($md5, 0, 2);
        $cache2 = substr($md5, 2, 2);
        if(!file_exists($main_path . $cache1))           mkdir($main_path . $cache1);
        if(!file_exists($main_path . "$cache1/$cache2")) mkdir($main_path . "$cache1/$cache2");
        $filename = $main_path . "$cache1/$cache2/$taxon_id.json";
        /*
        if(file_exists($filename)) {
            $file_age_in_seconds = time() - filemtime($filename);
            if($file_age_in_seconds < $this->download_options['expire_seconds'])    return; //no need to save
            if($this->download_options['expire_seconds'] === false)                 return; //no need to save
        } */
        //saving...
        $FILE = Functions::file_open($filename, 'w');
        fwrite($FILE, $json);
        fclose($FILE);
    }
    private function get_taxon_info_from_json($taxon_id)
    {
        // echo "\nretrieving json...";
        $main_path = $this->path['temp_dir'];
        $md5 = md5($taxon_id);
        $cache1 = substr($md5, 0, 2);
        $cache2 = substr($md5, 2, 2);
        $filename = $main_path . "$cache1/$cache2/$taxon_id.json";
        if(file_exists($filename)) {
            $json = file_get_contents($filename);
            // print_r(json_decode($json, true));
            return json_decode($json, true);
        }
        else return array();
    }
    private function write_to_text_comnames($recs)
    {
        $comname_head   = array("Namestring", "Preferred",  "ISO lang.", "Language"     , "User name", "User EOL ID", "Taxon name", "Taxon ID", "Rank", "Kingdom", "Phylum", "Class", "Order", "Family", "Genus");
        $comname_fields = array('common_name', "preferred", 'iso_lang' , 'lang_english' , 'user_name', 'user_id'    , 'taxon_name', 'taxon_id', 'rank'); //was removed but working: he_parent_id
        $txtfile = CONTENT_RESOURCE_LOCAL_PATH . $this->folder.".txt";
        $FILE = Functions::file_open($txtfile, "w");
        fwrite($FILE, implode("\t", $comname_head)."\n");
        $i = 0;
        foreach($recs as $resource_id => $rec) {
            $cols = array(); $i++;
            foreach($comname_fields as $fld) $cols[] = self::clean_str($rec[$fld], false);
            // if((($i % 30) == 0)) fwrite($FILE, implode("\t", $comname_head)."\n"); --- not needed coz we'll use this text file to generate the final DwCA resource
            //start ancestry inclusion
            $ancestry = array('kingdom' => "", 'phylum' => "", 'class' => "", 'order' => "", 'family' => "", 'genus' => "");
            if(@$rec['ancestry']) {
                foreach(@$rec['ancestry'] as $a) {
                    /* 
                    [he_id] => 52691614
                    [taxon_name] => Eunice
                    [taxon_concept_id] => 50908
                    [he_parent_id] => 52691523
                    [rank] => genus
                    */
                    if(in_array($a['rank'], array('kingdom','phylum','class','order','family','genus'))) {
                        $ancestry[$a['rank']] = $a['taxon_name'];
                    }
                }
            }
            //end ancestry
            $cols = array_merge($cols, $ancestry);
            fwrite($FILE, implode("\t", $cols)."\n");
        }
        fclose($FILE);
    }

    //==========================================================================================
    public function start_resource_metadata()
    {
        $sql = "SELECT r.id as resource_id, r.title as resource_name, r.collection_id, r.description, r.accesspoint_url as orig_data_source_url
        , r.bibliographic_citation, IF(r.vetted = 1, 'Yes','No') as vettedYN, IF(r.auto_publish = 1, 'Yes','No') as auto_publishYN, r.notes
        , concat(cp.full_name, ' (',cp.id,')') as content_partner
        , '-to be filled up-' as harvest_url_direct
        , '-to be filled up-' as harvest_url_4connector
        , '-to be filled up-' as connector_info
        , l.title  as dataset_license , r.dataset_rights_holder,                  r.dataset_rights_statement
        , l2.title as default_license , r.rights_holder as default_rights_holder, r.rights_statement as default_rights_statement
        , s2.label as resource_status, s3.label as default_language
        FROM resources r
        LEFT OUTER JOIN content_partners cp ON  (r.content_partner_id = cp.id)
        LEFT OUTER JOIN licenses l ON  (r.dataset_license_id = l.id)
        LEFT OUTER JOIN licenses l2 ON (r.license_id         = l2.id)
        LEFT OUTER JOIN translated_resource_statuses s2 ON (r.resource_status_id=s2.resource_status_id)
        LEFT OUTER JOIN translated_languages         s3 ON (r.language_id=s3.original_language_id)
        WHERE s2.language_id = 152 AND s3.language_id = 152";
        // $sql .= " AND r.id = 42";
        $result = $this->mysqli->query($sql);
        // echo "\n". $result->num_rows; exit;
        $recs = array();
        while($result && $row=$result->fetch_assoc()) {
            if(!isset($recs[$row['resource_id']])) {
                $first_pub = $this->mysqli->select_value("SELECT min(he.published_at) as last_published FROM resources r JOIN harvest_events he ON (r.id=he.resource_id) WHERE r.id = ".$row['resource_id']);
                $last_pub = $this->mysqli->select_value("SELECT max(he.published_at) as last_published FROM resources r JOIN harvest_events he ON (r.id=he.resource_id) WHERE r.id = ".$row['resource_id']);
                $recs[$row['resource_id']] = array('resource_id' => $row['resource_id'], 'resource_name' => $row['resource_name']
                , 'first_pub' => $first_pub, 'last_pub' => $last_pub, 'collection_id' => $row['collection_id']
                , 'description' => $row['description']
                , 'orig_data_source_url' => $row['orig_data_source_url']
                , 'harvest_url_direct' => $row['harvest_url_direct']
                , 'harvest_url_4connector' => $row['harvest_url_4connector']
                , 'connector_info' => $row['connector_info']
                , 'dataset_license' => $row['dataset_license'], 'dataset_rights_holder' => $row['dataset_rights_holder'], 'dataset_rights_statement' => $row['dataset_rights_statement']
                , 'default_license' => $row['default_license'], 'default_rights_holder' => $row['default_rights_holder'], 'default_rights_statement' => $row['default_rights_statement']
                , 'bibliographic_citation' => $row['bibliographic_citation'], 'resource_status' => $row['resource_status']
                , 'default_language' => $row['default_language'], 'vettedYN' => $row['vettedYN'], 'auto_publishYN' => $row['auto_publishYN'], 'notes' => $row['notes']
                , 'content_partner' => $row['content_partner']);
            }
        }
        // print_r($recs);
        self::write_to_text_resource($recs);
        self::write_to_html_resource($recs);
    }
    private function write_to_text_resource($recs)
    {
        $resource_head = array("Resource ID", "Resource name", "First Published", "Last Published", "Collection ID", "Description", "Original Data Source URL", "Harvest URL (direct)", 
        "Harvest URL (for connector)", "connector info", "Dataset license", "Dataset Rights Holder", "Dataset Rights Statement", "Default license", "Default Rights Holder", 
        "Default Rights Statement", "Bibliographic Citation", "Default Language", "Vetted", "Auto Publish", "Notes", "Status", "Content Partner");
        $resource_fields = array("resource_id", "resource_name", "first_pub", "last_pub", "collection_id", "description", "orig_data_source_url", "harvest_url_direct",
        "harvest_url_4connector", "connector_info", 
        "dataset_license", "dataset_rights_holder", "dataset_rights_statement", 
        "default_license", "default_rights_holder", "default_rights_statement", "bibliographic_citation", "default_language", "vettedYN", "auto_publishYN", "notes", "resource_status", "content_partner");
        $txtfile = CONTENT_RESOURCE_LOCAL_PATH . "resource_metadata.txt";
        $FILE = Functions::file_open($txtfile, "w");
        fwrite($FILE, implode("\t", $resource_head)."\n");
        $i = 0;
        foreach($recs as $resource_id => $rec) {
            $cols = array(); $i++;
            foreach($resource_fields as $fld) $cols[] = self::clean_str($rec[$fld], false);
            if((($i % 30) == 0)) fwrite($FILE, implode("\t", $resource_head)."\n");
            fwrite($FILE, implode("\t", $cols)."\n");
        }
        fclose($FILE);
    }
    private function write_to_html_resource($recs)
    {
        $resource_head = array("Resource ID", "Resource name", "First Published", "Last Published", "Collection ID", "Description", "Original Data Source URL", "Harvest URL (direct)", 
        "Harvest URL (for connector)", "connector info", "Dataset license", "Dataset Rights Holder", "Dataset Rights Statement", "Default license", "Default Rights Holder", 
        "Default Rights Statement", "Bibliographic Citation", "Default Language", "Vetted", "Auto Publish", "Notes", "Status", "Content Partner");
        $resource_fields = array("resource_id", "resource_name", "first_pub", "last_pub", "collection_id", "description", "orig_data_source_url", "harvest_url_direct",
        "harvest_url_4connector", "connector_info", 
        "dataset_license", "dataset_rights_holder", "dataset_rights_statement", 
        "default_license", "default_rights_holder", "default_rights_statement", "bibliographic_citation", "default_language", "vettedYN", "auto_publishYN", 
        "notes", "resource_status", "content_partner");

        $txtfile = CONTENT_RESOURCE_LOCAL_PATH . "resource_metadata.html";
        $FILE = Functions::file_open($txtfile, "w");
        fwrite($FILE, "<html><body><table border='1'>"."\n");
        
        $i = 0;
        foreach($recs as $resource_id => $rec) {
            $i++;
            if(($i % 2) == 0) $bgcolor = 'lightblue';
            else              $bgcolor = 'lightyellow';
            
            if((($i % 10) == 0) || $i == 1) {
                fwrite($FILE, "<tr bgcolor='$bgcolor'>"."\n");
                foreach($resource_head as $header) fwrite($FILE, "<td align='center' style='font-weight:bold;'>$header</td>"."\n");
                fwrite($FILE, "</tr>"."\n");
            }

            fwrite($FILE, "<tr bgcolor='$bgcolor'>"."\n");
            foreach($resource_fields as $fld) fwrite($FILE, "<td>".self::clean_str($rec[$fld], true, $fld)."</td>"."\n");
            fwrite($FILE, "</tr>"."\n");
        }
        fwrite($FILE, "</table></body></html>"."\n");
        fclose($FILE);
    }
    
    public function start_partner_metadata()
    {   /* orig
        $sql = "SELECT cp.id as partner_id, cp.full_name as partner_name, s.label as status, r.id as resource_id, r.title as resource_title, s2.label as resource_status,
        cp.description as overview, cp.homepage as url, 
        cpa.mou_url as agreement_url, cpa.signed_on_date as signed_date, cpa.signed_by, cpa.created_at as create_date,
        cp.description_of_data as desc_of_data, cp.user_id as manager_eol_id
        FROM content_partners cp
        JOIN translated_content_partner_statuses s ON (cp.content_partner_status_id=s.id)
        JOIN resources r ON (cp.id=r.content_partner_id)
        JOIN translated_resource_statuses s2 ON (r.resource_status_id=s2.id)
        JOIN content_partner_agreements cpa ON (cp.id=cpa.content_partner_id)
        WHERE s.language_id = 152 AND s2.language_id = 152 
        ORDER BY cp.id limit 6000"; */
        //better query than above
        $sql = "SELECT cp.id as partner_id, cp.full_name as partner_name, s.label as status, cpa.is_current,
        cp.description as overview, cp.homepage as url, 
        cpa.mou_url as agreement_url, cpa.signed_on_date as signed_date, cpa.signed_by, cpa.created_at as create_date,
        cp.description_of_data as desc_of_data, cp.user_id as manager_eol_id
        FROM content_partners cp
        LEFT OUTER JOIN translated_content_partner_statuses s ON (cp.content_partner_status_id=s.id)
        LEFT OUTER JOIN content_partner_agreements cpa ON (cp.id=cpa.content_partner_id)
        WHERE s.language_id = 152 
        ORDER BY cp.id, cpa.is_current desc limit 6000";
        $result = $this->mysqli->query($sql);
        // echo "\n". $result->num_rows; exit;
        $recs = array();
        while($result && $row=$result->fetch_assoc()) {
            if(!isset($recs[$row['partner_id']])) {
                $recs[$row['partner_id']] = array('partner_name' => $row['partner_name'], 'partner_id' => $row['partner_id'], 'status' => $row['status'],
                'overview' => $row['overview'], 'url' => $row['url'], 'agreement_url_from_db' => $row['agreement_url'], 'agreement_url' => self::fix_agreement_url($row['agreement_url']), 
                'signed_by' => $row['signed_by'], 'signed_date' => $row['signed_date'], 'create_date' => $row['create_date'], 'desc_of_data' => $row['desc_of_data'],
                'manager_eol_id' => $row['manager_eol_id'] );
                $recs[$row['partner_id']]['mou_url_editors'] = self::move_url_to_editors($recs[$row['partner_id']]['agreement_url']);

                $sql = "SELECT cpc.id as eol_contact_id, cpc.given_name, cpc.family_name, cpc.email, cpc.homepage, cpc.telephone, cpc.address, s.label as contact_role
                FROM content_partner_contacts cpc JOIN translated_contact_roles s ON (cpc.contact_role_id=s.id) 
                WHERE cpc.content_partner_id = ".$row['partner_id']." AND s.language_id = 152 ORDER BY cpc.id";
                $contacts = $this->mysqli->query($sql);
                while($contacts && $row2=$contacts->fetch_assoc()) {
                    $recs[$row['partner_id']]['contacts'][] = array('eol_contact_id' => $row2['eol_contact_id'], 'given_name' => $row2['given_name'], 'family_name' => $row2['family_name'], 'email' => $row2['email'],
                    'homepage' => $row2['homepage'], 'telephone' => $row2['telephone'], 'address' => $row2['address'], 'contact_role' => $row2['contact_role'],);
                }
                
                $sql = "SELECT r.id as resource_id, r.title as resource_title, s2.label as resource_status
                FROM resources r
                JOIN translated_resource_statuses s2 ON (r.resource_status_id=s2.resource_status_id)
                WHERE s2.language_id = 152 and r.content_partner_id = ".$row['partner_id']." ORDER BY r.id";
                $resources = $this->mysqli->query($sql);
                while($resources && $row3=$resources->fetch_assoc()) {
                    $first_pub = $this->mysqli->select_value("SELECT min(he.published_at) as last_published FROM resources r JOIN harvest_events he ON (r.id=he.resource_id) WHERE r.id = ".$row3['resource_id']);
                    $last_pub = $this->mysqli->select_value("SELECT max(he.published_at) as last_published FROM resources r JOIN harvest_events he ON (r.id=he.resource_id) WHERE r.id = ".$row3['resource_id']);
                    $recs[$row['partner_id']]['resources'][] = array('resource_id' => $row3['resource_id'], 'resource_title' => $row3['resource_title'], 'first_pub' => $first_pub, 'last_pub' => $last_pub, 'status' => $row3['resource_status']);
                }
            }
        }
        // print_r($recs);
        self::write_to_text($recs);
        self::write_to_html($recs);
    }
    private function write_to_html($recs)
    {
        $partner_head = array("Partner ID", "Partner name", "Overview", "URL", "Agreement URL", "Signed By", "Signed Date", "Create Date", "Description of Data", "Manager EOL ID", "Status");
        $resource_head = array("Resource ID", "Title", "First Published", "Last Updated", "Status");
        $contact_head = array("Contact ID", "Given Name", "Family Name", "Email", "Homepage", "Telephone", "Address", "Role");
        
        // [agreement_url_from_db] [agreement_url] -> not used for partner
        $partner_fields = array("partner_id", "partner_name", "overview", "url", "mou_url_editors", "signed_by", "signed_date", "create_date", "desc_of_data", "manager_eol_id", "status");
        $resource_fields = array("resource_id", "resource_title", "first_pub", "last_pub", "status");
        $contact_fields = array("eol_contact_id", "given_name", "family_name", "email", "homepage", "telephone", "address", "contact_role");
        
        $txtfile = CONTENT_RESOURCE_LOCAL_PATH . "partner_metadata.html";
        $FILE = Functions::file_open($txtfile, "w");
        fwrite($FILE, "<html><body><table border='1'>"."\n");
        
        $i = 0;
        foreach($recs as $partner_id => $rec) {
            $i++;
            if(($i % 2) == 0) $bgcolor = 'lightblue';
            else              $bgcolor = 'lightyellow';
            
            fwrite($FILE, "<tr bgcolor='$bgcolor'>"."\n");
            foreach($partner_head as $header) fwrite($FILE, "<td align='center' style='font-weight:bold;'>$header</td>"."\n");
            fwrite($FILE, "</tr>"."\n");

            fwrite($FILE, "<tr bgcolor='$bgcolor'>"."\n");
            foreach($partner_fields as $fld) fwrite($FILE, "<td>".self::clean_str($rec[$fld])."</td>"."\n");
            fwrite($FILE, "</tr>"."\n");

            fwrite($FILE, "<tr bgcolor='$bgcolor'>"."\n");

            //contacts
            fwrite($FILE, "<td colspan='5' align='center'>"."\n");
            if(@$rec['contacts']) {
                    fwrite($FILE, "<table border='1'>"."\n");
                    fwrite($FILE, "<tr>"."\n");
                    foreach($contact_head as $header) fwrite($FILE, "<td align='center' style='font-weight:bold;'>$header</td>"."\n");
                    fwrite($FILE, "</tr>"."\n");
                    foreach(@$rec['contacts'] as $rec3) {
                        fwrite($FILE, "<tr>"."\n");
                        foreach($contact_fields as $fld) fwrite($FILE, "<td>".self::clean_str($rec3[$fld])."</td>"."\n");
                        fwrite($FILE, "</tr>"."\n");
                    }
                    fwrite($FILE, "</table>"."\n");
            }
            fwrite($FILE, "</td>"."\n");

            fwrite($FILE, "<td colspan='6' align='center'>"."\n");
            if(@$rec['resources']) {
                fwrite($FILE, "<table border='1'>"."\n");
                // resources
                fwrite($FILE, "<tr>"."\n");
                foreach($resource_head as $header) fwrite($FILE, "<td align='center' style='font-weight:bold;'>$header</td>"."\n");
                fwrite($FILE, "</tr>"."\n");
                foreach(@$rec['resources'] as $rec2) {
                    fwrite($FILE, "<tr>"."\n");
                    foreach($resource_fields as $fld) fwrite($FILE, "<td>".self::clean_str($rec2[$fld])."</td>"."\n");
                    fwrite($FILE, "</tr>"."\n");
                }
                fwrite($FILE, "</table>"."\n");
            }
            fwrite($FILE, "</td>"."\n");

            fwrite($FILE, "</tr>"."\n");
        }
        fwrite($FILE, "</table></body></html>"."\n");
        fclose($FILE);
    }
    private function clean_str($str, $htmlYN = true, $fld = "")
    {
        $str = str_replace(array("\t", "\n", chr(9), chr(13), chr(10)), " ", $str);
        $str = trim($str);
        if($htmlYN) {
            $display = $str;
            if($fld == "notes") {
                if(strlen($str) > 200) $str = substr($str, 0, 200)."...";
            }
            if($fld == "orig_data_source_url") {
                if(strlen($display) > 75) $display = substr($display, 0, 75)."...";
            }
            if(substr($str,0,4) == 'http') $str = "<a href='$str'>".$display."</a>";
        }
        return $str;
        // chr(9) tab key
        // chr(13) = Carriage Return - (moves cursor to lefttmost side)
        // chr(10) = New Line (drops cursor down one line) 
    }
    private function fix_agreement_url($url_from_db)
    {
        //                   /files/pdfs/mou/EOL_FishBase-mou.pdf
        // http://www.eol.org/files/pdfs/mou/EOL_FishBase-mou.pdf
        if(substr($url_from_db, 0, 16) == "/files/pdfs/mou/") $url_from_db = "http://www.eol.org".$url_from_db;
        $url_from_db = str_replace("content8.eol.org", "content.eol.org", $url_from_db);
        $url_from_db = str_replace("content4.eol.org", "content.eol.org", $url_from_db);
        $url_from_db = str_replace("content1.eol.org", "content.eol.org", $url_from_db);
        // self::save_mou_to_local($url_from_db); //will comment this line once MOUs are saved
        return $url_from_db; //returns a transformed $url_from_db
    }
    private function save_mou_to_local($url)
    {
        if(!$url) return;
        if(substr($url,0,5) != "http:") return;
        $options = array('cache' => 1, 'download_wait_time' => 500000, 'timeout' => 10800, 'download_attempts' => 1, 'expire_seconds' => 60*60*24*30*3); //cache expires in 3 months
        $options['file_extension'] = "pdf";
        if($file = Functions::save_remote_file_to_local($url, $options)) {
            echo "\n [$url]: $file\n";
            $final = pathinfo($url, PATHINFO_FILENAME);
            $local = pathinfo($file, PATHINFO_FILENAME);
            $destination = str_replace($local, $final, $file);
            rename($file, $destination);
        }
    }
    public function save_all_MOUs()
    {
        $sql = "SELECT c.mou_url as url FROM content_partner_agreements c WHERE c.mou_url is not null GROUP BY c.mou_url ORDER BY c.mou_url";
        $result = $this->mysqli->query($sql);
        $recs = array();
        while($result && $row=$result->fetch_assoc()) {
            if($val = @$row['url']) self::fix_agreement_url($val);
        }
    }
    private function move_url_to_editors($url)
    {
        if(substr($url,0,5) == 'http:') {
            //https://editors.eol.org/other_files/EOL_Partner_MOUs/EOL_Naturalis-mou.pdf
            $basename = pathinfo($url, PATHINFO_BASENAME);
            return "https://editors.eol.org/other_files/EOL_Partner_MOUs/".$basename;
        }
    }
    private function write_to_text($recs)
    {
        $partner_head = array("Partner ID", "Partner name", "Overview", "URL", "Agreement URL", "Signed By", "Signed Date", "Create Date", "Description of Data", "Manager EOL ID", "Status");
        $resource_head = array("Resource ID", "Title", "First Published", "Last Updated", "Status");
        $contact_head = array("Contact ID", "Given Name", "Family Name", "Email", "Homepage", "Telephone", "Address", "Role");
        
        // [agreement_url_from_db] [agreement_url] -> not used for partner
        $partner_fields = array("partner_id", "partner_name", "overview", "url", "mou_url_editors", "signed_by", "signed_date", "create_date", "desc_of_data", "manager_eol_id", "status");
        $resource_fields = array("resource_id", "resource_title", "first_pub", "last_pub", "status");
        $contact_fields = array("eol_contact_id", "given_name", "family_name", "email", "homepage", "telephone", "address", "contact_role");
        
        $txtfile = CONTENT_RESOURCE_LOCAL_PATH . "partner_metadata.txt";
        $FILE = Functions::file_open($txtfile, "w");
        fwrite($FILE, implode("\t", $partner_head)."\n");
        
        foreach($recs as $partner_id => $rec) {
            $cols = array();
            foreach($partner_fields as $fld) $cols[] = self::clean_str($rec[$fld], false);
            fwrite($FILE, implode("\t", $cols)."\n");

            //resources
            if(@$rec['resources']) {
                fwrite($FILE, "\t".implode("\t", $resource_head)."\n");
                foreach(@$rec['resources'] as $rec2) {
                    $cols = array();
                    foreach($resource_fields as $fld) $cols[] = self::clean_str($rec2[$fld], false);
                    fwrite($FILE, "\t".implode("\t", $cols)."\n");
                }
            }

            //contacts
            if(@$rec['contacts'])
            {
                fwrite($FILE, "\t".implode("\t", $contact_head)."\n");
                foreach(@$rec['contacts'] as $rec3) {
                    $cols = array();
                    foreach($contact_fields as $fld) $cols[] = self::clean_str($rec3[$fld], false);
                    fwrite($FILE, "\t".implode("\t", $cols)."\n");
                }
            }
        }
        fclose($FILE);
    }
    
    function download_resource_files() //
    {
        /* saved the ids to array()
        $sql = "SELECT r.id from resources r order by r.id"; $result = $this->mysqli->query($sql);
        while($result && $row=$result->fetch_assoc()) echo ", " .$row['id'];
        echo "\n";
        */
        $possible = array(".xml", ".xml.gz", ".tar.gz", ".zip");
        $wget_path = '/opt/local/bin/wget';
        $target_folder = '/Library/WebServer/Documents/cp_new/services.eol.org_xml/';
        $services_url = 'http://services.eol.org/resources/';
        $ids = self::get_resource_ids();
        // $ids = array(36, 43); //43
        // print_r($ids);
        $i = 0; $total = count($ids);
        foreach($ids as $id) {
            $i++; echo "\n $i of $total - ";
            $p = array();
            // /opt/local/bin/wget --tries=1 -O /Library/WebServer/Documents/cp_new/services.eol.org_xml/eli.xml "http://services.eol.org/resources/eli.xml" 2>&1
            
            foreach($possible as $extension) {
                $filename = $id.$extension;
                $p['destination'] = $target_folder.$filename;
                $p['url'] = $services_url.$filename;
                
                if(!file_exists($p['destination'])) {
                    //worked on script
                    $cmd = $wget_path.' --tries=3 -O '.$p['destination'].' "'.$p['url'].'"'; //working well with shell_exec()
                    $cmd .= " 2>&1";
                    $info = shell_exec($cmd);
                    echo "\n $info";
                }
                if(!filesize($p['destination'])) unlink($p['destination']); //final for cleaning zero size files
            }
        }
    }
    function test_xml_files()
    {
        $target_folder = '/Library/WebServer/Documents/cp_new/services.eol.org_xml/';
        $ids = self::get_resource_ids();
        foreach($ids as $id) {
            $filename = $id.".xml";
            $filename = $target_folder.$filename;
            // echo "\n [$id] - ";
            if(file_exists($filename)) {
                $xml = simplexml_load_file($filename);
                // echo " total: ".count($xml->taxon)."\n";
            }
            // else echo " - invalid XML";
        }
    }
    private function get_resource_ids()
    {
        return array(4, 6, 8, 9, 11, 12, 13, 15, 16, 17, 18, 19, 20, 21, 22, 24, 26, 27, 28, 30, 31, 33, 34, 35, 36, 37, 39, 40, 41, 42, 43, 44, 45, 48, 51, 58, 59, 61, 62, 63, 64, 
        65, 66, 67, 68, 69, 70, 71, 72, 74, 77, 79, 80, 81, 82, 83, 84, 85, 86, 87, 88, 90, 92, 93, 94, 96, 98, 99, 100, 102, 103, 104, 105, 106, 107, 108, 109, 110, 111, 114, 115, 116, 118, 
        119, 120, 121, 122, 123, 124, 126, 127, 128, 129, 130, 131, 133, 135, 137, 138, 139, 141, 143, 144, 145, 146, 147, 148, 151, 154, 155, 156, 157, 158, 159, 160, 162, 163, 164, 166, 168, 
        169, 170, 171, 172, 173, 174, 175, 176, 177, 178, 179, 180, 181, 182, 183, 184, 185, 186, 187, 188, 189, 190, 191, 192, 193, 194, 195, 196, 197, 198, 199, 200, 201, 204, 205, 206, 207, 
        208, 209, 210, 211, 212, 213, 214, 215, 216, 217, 218, 219, 220, 221, 222, 223, 224, 225, 226, 227, 230, 231, 232, 233, 234, 235, 236, 237, 238, 240, 241, 242, 243, 245, 246, 247, 248, 
        249, 250, 251, 252, 253, 254, 255, 256, 257, 258, 259, 262, 263, 264, 265, 266, 267, 268, 269, 270, 271, 272, 273, 274, 275, 276, 277, 278, 279, 280, 281, 282, 283, 284, 285, 286, 287, 
        288, 289, 290, 291, 292, 294, 295, 296, 297, 298, 299, 300, 301, 302, 304, 306, 307, 316, 320, 321, 322, 323, 324, 327, 328, 329, 330, 331, 332, 333, 334, 335, 336, 337, 338, 339, 340, 
        341, 342, 343, 344, 345, 346, 347, 348, 349, 350, 351, 352, 354, 355, 356, 357, 358, 359, 360, 361, 362, 363, 364, 365, 366, 367, 368, 369, 370, 371, 372, 373, 374, 375, 376, 377, 378, 
        379, 380, 381, 382, 383, 384, 385, 386, 387, 388, 391, 392, 393, 394, 395, 396, 397, 398, 399, 400, 401, 402, 403, 404, 405, 406, 407, 408, 409, 410, 411, 412, 413, 414, 415, 416, 417, 
        418, 419, 420, 421, 422, 423, 424, 425, 426, 427, 428, 429, 430, 431, 432, 434, 435, 436, 437, 438, 439, 440, 441, 442, 443, 444, 445, 446, 447, 448, 449, 450, 451, 452, 453, 454, 455, 
        456, 457, 458, 459, 460, 461, 462, 463, 464, 465, 466, 467, 468, 469, 470, 471, 472, 473, 474, 475, 476, 477, 478, 479, 480, 481, 482, 483, 484, 485, 486, 487, 488, 489, 490, 491, 492, 
        493, 494, 495, 496, 497, 498, 499, 500, 501, 502, 503, 504, 505, 506, 507, 508, 509, 510, 511, 512, 513, 514, 515, 516, 517, 518, 519, 520, 521, 522, 523, 526, 527, 528, 529, 530, 531, 
        532, 533, 534, 535, 536, 537, 538, 539, 540, 541, 542, 543, 544, 545, 546, 547, 548, 549, 550, 551, 552, 553, 554, 555, 556, 557, 558, 559, 560, 561, 562, 563, 564, 565, 566, 567, 568, 
        569, 570, 571, 572, 573, 574, 575, 576, 577, 578, 579, 580, 581, 582, 583, 584, 585, 586, 587, 588, 589, 590, 591, 592, 593, 594, 595, 596, 597, 598, 599, 600, 601, 602, 603, 604, 605, 
        606, 607, 608, 609, 610, 611, 612, 613, 614, 615, 616, 617, 618, 619, 620, 621, 622, 623, 624, 625, 626, 627, 628, 629, 630, 631, 632, 633, 634, 635, 636, 637, 638, 639, 640, 641, 642, 
        643, 644, 645, 646, 647, 648, 649, 650, 651, 652, 653, 654, 655, 656, 657, 658, 659, 660, 662, 663, 664, 665, 666, 667, 668, 670, 671, 672, 673, 674, 675, 676, 677, 678, 679, 680, 681, 
        682, 683, 684, 685, 686, 687, 688, 689, 690, 691, 692, 693, 695, 696, 697, 699, 700, 701, 702, 703, 704, 705, 706, 707, 708, 709, 710, 711, 712, 713, 714, 715, 716, 717, 719, 720, 721, 
        722, 723, 724, 725, 726, 727, 728, 729, 732, 733, 734, 735, 736, 737, 738, 739, 741, 742, 743, 745, 746, 747, 748, 749, 750, 751, 752, 753, 754, 755, 756, 757, 758, 759, 760, 761, 762, 
        763, 764, 765, 766, 768, 769, 770, 771, 772, 773, 774, 775, 776, 777, 778, 779, 780, 781, 782, 783, 784, 785, 786, 787, 788, 789, 790, 791, 792, 793, 794, 795, 796, 797, 798, 799, 800, 
        801, 802, 803, 804, 805, 806, 809, 811, 812, 813, 814, 815, 816, 817, 818, 819, 820, 821, 822, 823, 824, 825, 826, 827, 828, 829, 830, 831, 832, 833, 834, 835, 836, 837, 838, 839, 840, 
        841, 842, 843, 844, 845, 846, 847, 848, 849, 850, 851, 852, 853, 854, 855, 858, 859, 860, 861, 862, 864, 865, 866, 867, 868, 869, 871, 872, 873, 874, 875, 877, 878, 879, 880, 881, 882, 
        883, 884, 885, 886, 887, 888, 889, 890, 891, 892, 893, 894, 895, 896, 897, 898, 899, 900, 901, 902, 903, 904, 905, 906, 907, 908, 909, 910, 911, 912, 915, 916, 917, 918, 919, 920, 921, 
        922, 923, 924, 926, 927, 928, 929, 930, 931, 932, 933, 936, 937, 938, 939, 940, 941, 942, 943, 944, 945, 946, 947, 948, 949, 950, 952, 953, 954, 955, 956, 957, 958, 959, 960, 961, 962, 
        963, 964, 969, 970, 971, 972, 973, 974, 975, 976, 977, 979, 980, 981, 982, 983, 984, 985, 986, 987, 988, 989, 990, 991, 992, 993, 994, 995, 996, 997, 998, 999, 1000, 1001, 1002, 1004, 
        1005, 1006, 1007, 1008, 1009, 1010, 1011, 1012, 1013, 1014, 1015, 1016, 1017, 1018, 1019, 1020, 1021, 1022, 1023, 1024, 1025, 1026, 1027, 1028, 1029, 1030);
    }
    
    /*
    public function begin()
    {
        $start = $this->mysqli->select_value("SELECT MIN(id) FROM names");
        $max_id = $this->mysqli->select_value("SELECT MAX(id) FROM names");
        $limit = 100000;
        
        $this->mysqli->begin_transaction();
        for($i=$start ; $i<$max_id ; $i+=$limit)
        {
            $this->check_st_john($i, $limit);
            // $this->generate_ranked_canonical_forms($i, $limit);
        }
        $this->mysqli->end_transaction();
    }
    
    public function check_st_john($start, $limit)
    {
        $query = "SELECT id, string FROM names WHERE string REGEXP BINARY 'st\\\\\.-[a-z]'
            AND id BETWEEN $start AND ". ($start+$limit-1);
        foreach($this->mysqli->iterate_file($query) as $row)
        {
            $id = $row[0];
            $string = $row[1];
            $canonical_form_string = Functions::canonical_form($string);
            if($canonical_form = CanonicalForm::find_or_create_by_string($canonical_form_string))
            {
                echo "UPDATE names SET canonical_form_id=$canonical_form->id WHERE id=$id\n";
                $this->mysqli->update("UPDATE names SET canonical_form_id=$canonical_form->id, ranked_canonical_form_id=$canonical_form->id WHERE id=$id");
            }
        }
        $this->mysqli->commit();
    }
    
    public function check($start, $limit)
    {
        echo "Looking up $start, $limit\n";
        $query = "SELECT n.id, canonical.string, ranked_canonical.string  FROM names n
            JOIN canonical_forms canonical ON (n.canonical_form_id=canonical.id)
            JOIN canonical_forms ranked_canonical ON (n.ranked_canonical_form_id=ranked_canonical.id)
            WHERE n.ranked_canonical_form_id != n.canonical_form_id
            AND n.id BETWEEN $start AND ". ($start+$limit-1);
        foreach($this->mysqli->iterate_file($query) as $row)
        {
            $name_id = $row[0];
            $canonical = trim($row[1]);
            $ranked_canonical = trim($row[2]);
            
            // ranked has "zz", normal does not. This is indicative of a particular kind of encoding problem
            if(strpos($ranked_canonical, "zz") !== false && strpos($canonical, "zz") === false)
            {
                // echo "UPDATE names SET ranked_canonical_form_id = canonical_form_id WHERE id = $id\n";
                $this->mysqli->update("UPDATE names SET ranked_canonical_form_id = canonical_form_id WHERE id = $name_id");
            }
        }
        $this->mysqli->commit();
    }
    
    public function generate_ranked_canonical_forms($start, $limit)
    {
        echo "Looking up $start, $limit\n";
        $query = "SELECT id, string FROM names
            WHERE id BETWEEN $start AND ". ($start+$limit-1)."
            AND (ranked_canonical_form_id IS NULL OR ranked_canonical_form_id=0)";
        foreach($this->mysqli->iterate_file($query) as $row)
        {
            $id = $row[0];
            $string = trim($row[1]);
            if(!$string || strlen($string) == 1) continue;

            // $canonical_form = trim($client->lookup_string($string));
            // if($count % 5000 == 0)
            // {
            //     echo "       > Parsed $count names ($id : $string : $canonical_form). Time: ". time_elapsed() ."\n";
            // }
            // $count++;
            // 
            // // report this problem
            // if(!$canonical_form) continue;
            // 
            // $canonical_form_id = CanonicalForm::find_or_create_by_string($canonical_form)->id;
            // $GLOBALS['db_connection']->query("UPDATE names SET ranked_canonical_form_id=$canonical_form_id WHERE id=$id");
        }
        $this->mysqli->commit();
    }
    */
}

?>
