<?php
namespace php_active_record;
/* connector: [collections_scrape.php]
*/
class CollectionsScrapeAPI
{
    function __construct($folder, $collection_id, $data_types = array('images', 'video', 'sounds')) //for LifeDesks images, video, sounds
    {
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->taxon_ids = array();
        $this->object_ids = array();
        
        // $this->dwca_file = "http://localhost/~eolit/cp/India Biodiversity Portal/520.tar.gz";
        // $this->dwca_file = "https://dl.dropboxusercontent.com/u/7597512/India Biodiversity Portal/520.tar.gz";
        // $this->taxon_page = "http://www.marinespecies.org/aphia.php?p=taxdetails&id=";
        // $this->accessURI = array();
        
        $this->download_options = array("cache" => 1, "download_wait_time" => 2000000, "timeout" => 3600, "download_attempts" => 1); //"delay_in_minutes" => 1
        $this->download_options['expire_seconds'] = false; //always false, will not change anymore...
        if(Functions::is_production()) $this->download_options['cache_path'] = "/extra/eol_cache_collections/";
        else                           $this->download_options['cache_path'] = "/Volumes/AKiTiO4/eol_cache_collections/";
        
        $this->url["eol_collection"] = "https://eol.org/api/collections/1.0/".$collection_id.".json?filter=data_type&sort_by=recently_added&sort_field=&cache_ttl=";
        $this->url["eol_collection_page"] = "http://eol.org/collections/".$collection_id."/data_type?sort_by=1&view_as=3"; //&page=2 
        //e.g. "http://eol.org/collections/9528/images?page=2&sort_by=1&view_as=3";
        $this->url["eol_object"]     = "http://eol.org/api/data_objects/1.0/";
        $this->url["eol_object"]     = "http://eol.org/api/data_objects/1.0/data_object_id.json?taxonomy=true&cache_ttl=";
        $this->url['eol_hierarchy_entries'] = "http://eol.org/api/hierarchy_entries/1.0/hierarchy_entry_id.json?common_names=false&synonyms=false&cache_ttl=&language=en";
        
        
        $this->multimedia_data_types = $data_types; //multimedia types
        /* we can add 'text' here even if text objects came from the orig LifeDesk XML and not from Collections because to have a more complete list of taxa. 
           We used the taxa info to get ancestry and other info. */

        if(Functions::is_production()) $this->lifedesk_images_path = '/extra/other_files/EOL_media/';
        else                           $this->lifedesk_images_path = '/Volumes/AKiTiO4/other_files/EOL_media/';
        $this->media_path = "https://editors.eol.org/other_files/EOL_media/";
    }

    // http://media.eol.org/content/2011/12/18/03/38467_orig.jpg        -> orig
    // http://media.eol.org/content/2012/03/28/09/98457_88_88.jpg       -> thumbnail
    
    function start($taxa_from_orig_LifeDesk_XML)
    {
        if(!is_dir($this->download_options['cache_path'])) mkdir($this->download_options['cache_path']);
        $this->taxa_from_orig_LifeDesk_XML = $taxa_from_orig_LifeDesk_XML;
        if(!is_dir($this->lifedesk_images_path)) mkdir($this->lifedesk_images_path);
        // /* normal operation
        foreach($this->multimedia_data_types as $data_type) {
            $this->data_type = $data_type;
            $do_ids_sciname = self::get_obj_ids_from_html($data_type);
            $arr = array_keys($do_ids_sciname);                             echo "\n".count($arr)."\n";
            $do_ids = self::get_obj_ids_from_collections_api($data_type);   echo "\n".count($do_ids)."\n";
            $do_ids = array_merge($do_ids, $arr);
            $do_ids = array_unique($do_ids);                                echo "\n".count($do_ids)."\n";
            unset($arr); //not needed anymore

            $k = 0; $m = 198270/4;
            foreach($do_ids as $do_id) 
            {
                /* breakdown when caching:
                $k++; echo " $k ";
                $cont = false;
                // if($k >=  1    && $k < $m) $cont = true;
                // if($k >=  $m   && $k < $m*2) $cont = true;
                // if($k >=  $m*2 && $k < $m*3) $cont = true;
                if($k >=  $m*3 && $k < $m*4) $cont = true;
                // if($k >=  $m*4 && $k < $m*5) $cont = true;
                if(!$cont) continue;
                */

                self::process_do_id($do_id, @$do_ids_sciname[$do_id]);
            }
        }
        // */
        /* preview mode
        $do_ids = array(13230214, 30865886, 30866171, 30866142); $do_ids_sciname = array(); //preview mode  ??? no taxon 29246746 29189521 //debug
        foreach($do_ids as $do_id) self::process_do_id($do_id, @$do_ids_sciname[$do_id]);
        */
        $this->archive_builder->finalize(TRUE);
    }
    private function download_multimedia_object($rec)
    {   /* 
        $mr->identifier     = $rec['dataObjectVersionID']; //$rec['identifier'];
        $mr->accessURI      = $rec['eolMediaURL'];
        */
        if($url = @$rec['eolMediaURL'])                             return self::download_proper($rec, $url);
        elseif(@$rec['mediaURL'] && $rec['dataType'] == 'YouTube')  return $rec['mediaURL'];
        elseif($url = @$rec['mediaURL'])                            return self::download_proper($rec, $url);
        return false;
    }
    private function download_proper($rec, $url)
    {
        $options = $this->download_options;
        $options['expire_seconds'] = false; //doesn't need to expire at all
        $filename = $rec['dataObjectVersionID'].".".pathinfo($url, PATHINFO_EXTENSION);

        $folder = substr($rec['dataObjectVersionID'], -2)."/";
        if(!is_dir($this->lifedesk_images_path.$folder)) mkdir($this->lifedesk_images_path.$folder);

        $destination = $this->lifedesk_images_path.$folder.$filename;

        // /* uncomment in real operation. This is just to stop downloading of images.
        if(!file_exists($destination)) {
            $local = Functions::save_remote_file_to_local($url, $options);
            // echo "\n[$local]\n[$destination]";
            if(filesize($local)) {
                Functions::file_rename($local, $destination);
                return $this->media_path.$folder.$filename; //this is media_url for the data_object;
            }
            else {
                if(file_exists($local)) unlink($local);
            }
        }
        // */
        return false;
    }
    
    
    
    private function process_do_id($do_id, $sciname)
    {
        $url = str_replace("data_object_id", $do_id, $this->url["eol_object"]);
        if($json = Functions::lookup_with_cache($url, $this->download_options)) {
            $obj = json_decode($json, true);
            if(!@$obj['scientificName']) {//e.g. collection_id = 106941 -> has hidden data_objects and dataObject API doesn't have taxon info.
                $obj['scientificName'] = $sciname;
                if($val = self::match_taxa_from_original_LifeDesk_XML($obj)) $obj['identifier'] = $val;
                else                                                         $obj['identifier'] = str_replace(" ", "_", strtolower($sciname));
            }
            else $obj['identifier'] = self::match_taxa_from_original_LifeDesk_XML($obj);
            //print_r($obj); //exit;
            self::create_archive($obj);
        }
    }
    private function match_taxa_from_original_LifeDesk_XML($obj)
    {
        $canonical = Functions::canonical_form($obj['scientificName']); //[scientificName] => Cossypha archeri archeri Sharpe, 1902
        // print_r($obj); print_r($this->taxa_from_orig_LifeDesk_XML); exit;
        foreach($this->taxa_from_orig_LifeDesk_XML as $taxon_id => $name) {
            if($canonical == Functions::canonical_form($name)) return $taxon_id;
        }
        return md5($obj['scientificName']);
    }
    private function get_taxon_info($hierarchy_entry_id)
    {
        $final = array();
        $url = str_replace("hierarchy_entry_id", $hierarchy_entry_id, $this->url['eol_hierarchy_entries']);
        if($json = Functions::lookup_with_cache($url, $this->download_options)) {
            $obj = json_decode($json, true);
            // print_r($obj); //exit;
            /*
            [ancestors] => Array
                        [0] => Array
                                [sourceIdentifier] => 202423
                                [taxonID] => 46216568
                                [parentNameUsageID] => 0
                                [taxonConceptID] => 1
                                [scientificName] => Animalia
                                [taxonRank] => kingdom
                                [source] => http://eol.org/pages/1/hierarchy_entries/46216568/overview
                        [1] => Array ...
                        [2] => Array ...
            */
            /* working OK but removed. Use policy where only what is provided by partner will be included (e.g. taxon rank, ancestry, etc.)
            if($recs = @$obj['ancestors']) {
                foreach($recs as $rec) {
                    $final[@$rec['taxonRank']] = $rec['scientificName'];
                }
            }
            */
            
        }
        return $final;
    }
    private function create_archive($o)
    {   
        if(!@$o['scientificName']) return;
        //   ================================================ FOR TAXON  ================================================
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID         = $o['identifier'];
        /* Used md5(sciname) here so we can combine taxon.tab with LifeDesk text resource (e.g. LD_afrotropicalbirds.tar.gz). See ConvertEOLtoDWCaAPI.php */
        $taxon->scientificName  = $o['scientificName'];
        
        /* working OK but removed. Use policy where only what is provided by partner will be included (e.g. taxon rank, ancestry, etc.)
        if($rank = @$o['taxonConcepts'][0]['taxonRank']) $taxon->taxonRank = $rank;
        */
        
        // print_r($o);
        if($hierarchy_entry_id = @$o['taxonConcepts'][0]['identifier']) {

            /* $ancestry = self::get_taxon_info($hierarchy_entry_id); //working OK but was decided not to force adding of ancestry if partner didn't provide one.
            based here: https://eol-jira.bibalex.org/browse/DATA-1569?focusedCommentId=62079&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-62079 */
            $ancestry = array();
            
            /* Array
                [kingdom] => Animalia
                [subkingdom] => Bilateria
                [class] => Aves
                [order] => Passeriformes
                [family] => Muscicapidae Fleming, 1822
                [genus] => Cossypha Vigors 1825
                [species] => Cossypha archeri Sharpe 1902
            */
            $ranks = array('kingdom', 'phylum', 'class', 'order', 'family', 'genus');
            foreach($ranks as $rank) {
                if($sciname = @$ancestry[$rank]) $taxon->$rank = $sciname;
            }
        }
        
        // $taxon->furtherInformationURL = $this->page['species'].$rec['taxon_id'];
        // if($reference_ids = @$this->taxa_reference_ids[$t['int_id']]) $taxon->referenceID = implode("; ", $reference_ids);
        if(!isset($this->taxon_ids[$taxon->taxonID])) {
            $this->taxon_ids[$taxon->taxonID] = '';
            $this->archive_builder->write_object_to_file($taxon);
        }
        //  ================================================ FOR DATA_OBJECT ================================================
        /* [dataObjects] => Array
                    [0] => Array
                            [identifier] => 40a44c87cf6688bf6f531c75eb33c773
                            [dataObjectVersionID] => 13230214
                            [dataType] => http://purl.org/dc/dcmitype/StillImage
                            [dataSubtype] => 
                            [vettedStatus] => Trusted
                            [dataRatings] => Array
                                    [1] => 0
                                    [2] => 0
                                    [3] => 0
                                    [4] => 0
                                    [5] => 0
                            [dataRating] => 2.5
                            [mimeType] => image/jpeg
                            [created] => 2010-05-13T22:18:58Z
                            [modified] => 2010-05-13T22:18:58Z
                            [title] => Nectophrynoides viviparus from Udzungwa Scarp
                            [language] => en
                            [license] => http://creativecommons.org/licenses/by-nc/3.0/
                            [source] => http://africanamphibians.lifedesks.org/node/768
                            [description] => Nectophrynoides viviparus from Udzungwa Scarp<br><p><em>Nectophrynoides viviparus </em>from Udzungwa Scarp.</p>
                            [mediaURL] => http://africanamphibians.lifedesks.org/image/view/768/_original
                            [eolMediaURL] => http://media.eol.org/content/2011/10/14/16/90814_orig.jpg
                            [eolThumbnailURL] => http://media.eol.org/content/2011/10/14/16/90814_98_68.jpg
                            [agents] => Array
                                    [0] => Array
                                            [full_name] => Zimkus, Breda
                                            [homepage] => 
                                            [role] => photographer
                                        )
                                    [1] => Array
                                            [full_name] => Zimkus, Breda
                                            [homepage] => 
                                            [role] => publisher
                                        )
                            [references] => Array
                                (
                                )
                        )
                )
        */
        // if($this->data_type == 'text') return;  ---> should be commented. Since other EOL XML files use this library now. Filtering should be using $this->multimedia_data_types variable.
        
        if($rec = $o['dataObjects'][0]) {
            
            // print_r($rec);
            
            $mr = new \eol_schema\MediaResource();
            $mr->taxonID        = $taxon->taxonID;
            $mr->identifier     = $rec['dataObjectVersionID']; //$rec['identifier'];
            $mr->type           = $rec['dataType'];
            $mr->subtype        = $rec['dataSubtype'];
            $mr->Rating         = $rec['dataRating'];
            $mr->Owner          = @$rec['rightsHolder'];
            $mr->rights         = @$rec['rights'];
            $mr->language       = @$rec['language'];
            $mr->furtherInformationURL = @$rec['source'];
            $mr->title          = @$rec['title'];
            $mr->UsageTerms     = $rec['license'];
            $mr->description    = self::fix(@$rec['description']);
            $mr->modified       = @$rec['modified'];
            $mr->CreateDate     = @$rec['created'];
            
            if($rec['dataType'] == "http://purl.org/dc/dcmitype/Text") {
                $mr->CVterm = $rec['subject'];
                // if(!@$rec['subject']) {
                //     echo "\n No subject ? -- ";
                //     print_r($rec);
                // }
            }
            else {
                $mr->format = $rec['mimeType'];
                $media_url = self::download_multimedia_object($rec); //working OK - uncomment in real operation
                if(!$media_url) return; //don't save object since the media object wasn't downloaded at all
                $mr->accessURI = $media_url; //$rec['eolMediaURL']; eolMediaURL is e.g. 'http://media.eol.org/content/2011/12/18/03/66694_orig.jpg'
            }
            /*
            $mr->thumbnailURL   = $rec['eolThumbnailURL'];
            $mr->LocationCreated = '';
            $mr->bibliographicCitation = '';
            if($reference_ids = some_func() $mr->referenceID = implode("; ", $reference_ids);
            */
            if($val = @$rec['audience']) $mr->audience = implode("; ", $val);
            if($agent_ids = self::create_agents(@$rec['agents'])) $mr->agentID = implode("; ", $agent_ids);
            if(!isset($this->object_ids[$mr->identifier])) {
                $this->archive_builder->write_object_to_file($mr);
                $this->object_ids[$mr->identifier] = '';
            }
        }
    }
    private function fix($str)
    {
        if(strpos($str, "foRAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5") !== false) return ""; //string is found
        return $str;
    }
    private function create_agents($agents)
    {   /* [agents] => Array
            [0] => Array
                    [full_name] => Zimkus, Breda
                    [homepage] => 
                    [role] => photographer
                )
            [1] => Array
                    [full_name] => Zimkus, Breda
                    [homepage] => 
                    [role] => publisher
                )
        */
        $agent_ids = array();
        foreach($agents as $a) {
            if(!$a['full_name']) continue;
            $r = new \eol_schema\Agent();
            $r->term_name       = $a['full_name'];
            $r->agentRole       = $a['role'];
            $r->identifier      = md5("$r->term_name|$r->agentRole");
            $r->term_homepage   = $a['homepage'];
            $agent_ids[] = $r->identifier;
            if(!isset($this->agent_ids[$r->identifier])) {
               $this->agent_ids[$r->identifier] = '';
               $this->archive_builder->write_object_to_file($r);
            }
        }
        return $agent_ids;
    }
    private function get_obj_ids_from_collections_api($data_type) //this is kinda hack since param 'page' is not working in API. Just used max per_page 500 to get the first 500 records.
    {
        $do_ids = array();
        $url = $this->url["eol_collection"] . "&page=1&per_page=500";
        $url = str_replace('data_type', $data_type, $url);
        if($json = Functions::lookup_with_cache($url, $this->download_options))
        {
            $arr = json_decode($json);
            count($arr->collection_items);
            foreach($arr->collection_items as $r) 
            {
                if($r->object_type != 'TaxonConcept') $do_ids[$r->object_id] = ''; // accepted values: Text, Image, ???
            }
        }
        return array_keys($do_ids);
    }
    private function get_total_pages($data_type)
    {
        $page = 1; $per_page = 50;
        $url = $this->url["eol_collection"] . "&page=$page&per_page=$per_page";
        $url = str_replace("data_type", $data_type, $url);
        if($json = Functions::lookup_with_cache($url, $this->download_options)) {
            $arr = json_decode($json);
            return ceil($arr->total_items/50);
        }
    }
    private function get_obj_ids_from_html($data_type)
    {
        $do_ids_sciname = array(); $do_ids = array();
        $total_pages = self::get_total_pages($data_type);
        echo("\n[$data_type] [$total_pages pages]\n");
        $final = array();
        for($page=1; $page<=$total_pages; $page++) {
            $url = $this->url["eol_collection_page"]."&page=$page";
            $url = str_replace('data_type', $data_type, $url);
            $html = Functions::lookup_with_cache($url, $this->download_options); {
                echo "\n$page. [$url]";
                // <a href="/data_objects/26326917"><img alt="84925_88_88" height="68" src="http://media.eol.org/content/2013/09/13/13/84925_88_88.jpg" width="68" /></a>
                if(preg_match_all("/<a href=\"\/data_objects\/(.*?)<\/a>/ims", $html, $arr)) {
                    $rows = $arr[1];
                    // print_r($rows); exit;
                    $total_rows = count($rows)/4; 
                    $k = 0;
                    foreach($rows as $row) {
                        $k++;
                        // echo "\n$page of $total_pages - $k of $total_rows";
                        $rec = array();
                        if(preg_match("/src=\"(.*?)\"/ims", "_xxx".$row, $arr)) {
                            $rec['media_url'] = $arr[1];
                            if(preg_match("/_xxx(.*?)\"/ims", "_xxx".$row, $arr)) {
                                $rec['do_id'] = $arr[1];
                                $do_ids[$arr[1]] = '';
                            }
                        }
                        /* this part is for the scientificname - only used if dataObject API dosn't give taxon information e.g. https://eol.org/api/data_objects/1.0?id=29246746&taxonomy=true&cache_ttl=&language=en&format=json
                        [12] => 30865705"><img alt="07567_88_88" height="68" src="http://media.eol.org/content/2011/12/18/03/07567_88_88.jpg" width="68" />
                        [13] => 30865705"><span class="icon" title="This item is an image"></span>
                        [14] => 30865705">Cossypha roberti - (partial) distribution map (only focused o...
                        [15] => 30865705">Image of Cossypha roberti
                        */
                        if(preg_match("/\">(.*?)_xxx/ims", $row."_xxx", $arr)) {
                            $temp = $arr[1];
                            if(stripos($temp, " of ") !== false) //string is found -- "taxon"
                            {
                                if(preg_match("/_xxx(.*?)\"/ims", "_xxx".$row, $arr)) {
                                    $do_id = $arr[1];
                                    $temp_arr = explode(" of ", $temp);
                                    if($val = @$temp_arr[1]) $do_ids_sciname[$do_id] = trim($val);
                                }
                            }
                        }
                        //end for the scientificname
                        
                        if($rec) $final[] = $rec;
                    } //end foreach()
                }
                // if($page >= 3) break; //debug
            }
        }
        // print_r($final); echo "\n".count($final)."\n"; exit;
        // print_r($do_ids_sciname); echo "\n".count($do_ids_sciname)."\n"; exit;
        // return array_keys($do_ids);
        // print_r($do_ids_sciname); exit;
        return $do_ids_sciname;
    }

}
?>