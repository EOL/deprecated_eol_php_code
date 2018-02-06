<?php
namespace php_active_record;
/* connector: [520] India Biodiversity Portal archive connector
*/
class IndiaBiodiversityPortalAPI
{
    function __construct($folder)
    {
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->taxon_ids = array();
        $this->object_ids = array();
        $this->dwca_file = "http://localhost/~eolit/cp/India Biodiversity Portal/520.tar.gz";
        $this->dwca_file = "https://dl.dropboxusercontent.com/u/7597512/India Biodiversity Portal/520.tar.gz";
        $this->taxon_page = "http://www.marinespecies.org/aphia.php?p=taxdetails&id=";
        $this->accessURI = array();
    }

    function check_if_image_is_broken() // utility
    {
        $options = array('download_wait_time' => 1000000, 'timeout' => 900, 'download_attempts' => 1); // 15mins timeout
        $broken = array();
        for($i=1; $i<=58; $i++)
        {
            $url = "http://eol.org/collections/94950/images?page=$i&sort_by=3&view_as=3";
            $html = Functions::lookup_with_cache($url, $options);
            {
                echo "\n$i. [$url]";
                // <a href="/data_objects/26326917"><img alt="84925_88_88" height="68" src="http://media.eol.org/content/2013/09/13/13/84925_88_88.jpg" width="68" /></a>
                if(preg_match_all("/<a href=\"\/data_objects\/(.*?)<\/a>/ims", $html, $arr))
                {
                    $rows = $arr[1];
                    $total_rows = count($rows);
                    $k = 0;
                    foreach($rows as $row)
                    {
                        $k++;
                        echo "\n$i of 58 - $k of $total_rows";
                        if(preg_match("/_xxx(.*?)\"/ims", "_xxx".$row, $arr)) $id = $arr[1];
                        if(preg_match("/src=\"(.*?)\"/ims", "_xxx".$row, $arr))
                        {
                            $url = $arr[1];
                            $options['cache_path'] = "/Volumes/Eli blue/eol_cache_2/";
                            if($html = Functions::lookup_with_cache($url, $options)) echo "\nexists:[$url]";
                            else
                            {
                                echo "\nbroken: [$url]";
                                $broken[$id] = $url;
                            }
                            unset($options['cache_path']);
                        }
                    }
                }
                // if($i >= 3) break; //debug
            }
        }
        print_r($broken);
    }
    
    function get_all_taxa()
    {
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($this->dwca_file, "meta.xml");
        $archive_path = $paths['archive_path'];
        $temp_dir = $paths['temp_dir'];
        $harvester = new ContentArchiveReader(NULL, $archive_path);
        $tables = $harvester->tables;
        if(!($this->fields["taxa"] = $tables["http://rs.tdwg.org/dwc/terms/taxon"][0]->fields)) // take note the index key is all lower case
        {
            debug("Invalid archive file. Program will terminate.");
            return false;
        }
        self::create_instances_from_taxon_object($harvester->process_row_type('http://rs.tdwg.org/dwc/terms/Taxon')); //ok
        self::get_objects($harvester->process_row_type('http://eol.org/schema/media/Document')); //ok
        self::get_references($harvester->process_row_type('http://eol.org/schema/reference/Reference')); //ok
        self::get_agents($harvester->process_row_type('http://eol.org/schema/agent/Agent')); //ok
        self::get_vernaculars($harvester->process_row_type('http://rs.gbif.org/terms/1.0/VernacularName')); //ok
        $this->archive_builder->finalize(TRUE);
        // remove temp dir
        recursive_rmdir($temp_dir);
        echo ("\n temporary directory removed: " . $temp_dir);
    }

    private function process_fields($records, $class)
    {
        if($class == "objects")
        {
            $broken_images = array("http://pamba.strandls.com/biodiv/images/Leporicypraea mappa/123", "http://pamba.strandls.com/biodiv/images/Pleuroploca trapezium/903.JPG", 
            "http://pamba.strandls.com/biodiv/images/Harpago chiragra/886.JPG", "http://pamba.strandls.com/biodiv/images/Lambis millepeda/988.jpg", 
            "http://pamba.strandls.com/biodiv/images/Lambis truncata/933.jpg", "http://pamba.strandls.com/biodiv/images/Lambis truncata/638.JPG", 
            "http://pamba.strandls.com/biodiv/images/Crassostrea belcheri/800", "http://pamba.strandls.com/biodiv/images/Ostrea chilensis/697", "http://pamba.strandls.com/biodiv/images/Lopha cristagalli/372", "http://pamba.strandls.com/biodiv/images/Pinguitellina pinguis/600", "http://pamba.strandls.com/biodiv/images/Gafrarium pectinatum/629", "http://pamba.strandls.com/biodiv/images/Gafrarium pectinatum/774", "http://pamba.strandls.com/biodiv/images/Gafrarium pectinatum/889", "http://pamba.strandls.com/biodiv/images/Gafrarium pectinatum/280", "http://pamba.strandls.com/biodiv/images/Gafrarium pectinatum/406", "http://pamba.strandls.com/biodiv/images/Gafrarium pectinatum/907", 
            "http://pamba.strandls.com/biodiv/images/Gafrarium pectinatum/379", "http://pamba.strandls.com/biodiv/images/Gafrarium pectinatum/167");
            // 2nd batch of broken images
            $temp = array("http://pamba.strandls.com/biodiv/images/Potanthus pseudomaesa/408.jpg", "http://pamba.strandls.com/biodiv/images/Placuna placenta/225.jpg", 
            "http://pamba.strandls.com/biodiv/images/Talparia talpa/607.jpg", "http://pamba.strandls.com/biodiv/images/Leporicypraea mappa/110.jpg", 
            "http://pamba.strandls.com/biodiv/images/Cypraecassis rufa/761.jpg", "http://pamba.strandls.com/biodiv/images/Lambis truncata/18.jpg", 
            "http://pamba.strandls.com/biodiv/images/Lambis truncata/713.jpg", "http://pamba.strandls.com/biodiv/images/Conus milneedwardsi/701.jpg", 
            "http://pamba.strandls.com/biodiv/images/Conus milneedwardsi/833.jpg", "http://pamba.strandls.com/biodiv/images/Lambis scorpius/666.jpg", 
            "http://pamba.strandls.com/biodiv/images/Milvus migrans/979.png", "http://pamba.strandls.com/biodiv/images/Cellana radiata/374.jpg", 
            "http://pamba.strandls.com/biodiv/images/Cellana radiata/795.jpg", "http://pamba.strandls.com/biodiv/images/Cellana radiata/491.jpg", 
            "http://pamba.strandls.com/biodiv/images/Cellana radiata/258.jpg", "http://pamba.strandls.com/biodiv/images/Ypthima baldus/20.jpg", 
            "http://pamba.strandls.com/biodiv/images/Tirumala limniace/573.png", "http://pamba.strandls.com/biodiv/images/Talparia talpa/331.jpg", 
            "http://pamba.strandls.com/biodiv/images/Gyps bengalensis/292.svg", "http://pamba.strandls.com/biodiv/images/Gyps fulvus/281.PNG", 
            "http://pamba.strandls.com/biodiv/images/Cassis cornuta/89.jpg", "http://pamba.strandls.com/biodiv/images/Lambis crocata/779.jpg", 
            "http://pamba.strandls.com/biodiv/images/Talparia talpa/624.jpg", "http://pamba.strandls.com/biodiv/images/Tectus niloticus/827.jpg", 
            "http://pamba.strandls.com/biodiv/images/Hippopus hippopus/866.jpg", "http://pamba.strandls.com/biodiv/images/Nautilus pompilius/736.jpg", 
            "http://pamba.strandls.com/biodiv/images/Nautilus pompilius/666.jpg", "http://pamba.strandls.com/biodiv/images/Cypraecassis rufa/503.png", 
            "http://pamba.strandls.com/biodiv/images/Lambis crocata/395.jpg", "http://pamba.strandls.com/biodiv/images/Turbo marmoratus/837.jpg", 
            "http://pamba.strandls.com/biodiv/images/Cypraecassis rufa/126.jpg");
            $broken_images = array_merge($broken_images, $temp);
        }
        
        foreach($records as $rec)
        {
            if    ($class == "vernacular") $c = new \eol_schema\VernacularName();
            elseif($class == "agent")      $c = new \eol_schema\Agent();
            elseif($class == "reference")  $c = new \eol_schema\Reference();
            elseif($class == "objects")    $c = new \eol_schema\MediaResource();
            elseif($class == "taxa")       $c = new \eol_schema\Taxon();
            
            $keys = array_keys($rec);
            $r = array();
            foreach($keys as $key)
            {
                $temp = pathinfo($key);
                $field = $temp["basename"];
                
                if($field == "attribution") continue; //not recognized in eol: http://indiabiodiversity.org/terms/attribution
                
                // some fields have '#', e.g. "http://schemas.talis.com/2005/address/schema#localityName"
                $parts = explode("#", $field);
                if($parts[0]) $field = $parts[0];
                if(@$parts[1]) $field = $parts[1];

                $value = (string) $rec[$key];
                // echo "\n[$field] -- [" . $value . "]";
                $r[$field] = $value;
                $c->$field = $value;
            }
            $save = true;
            if($class == "objects")
            {
                if($r["UsageTerms"] == "http://creativecommons.org/licenses/by-nc-nd/3.0/")     $save = false;
                if($r["description"] == "" && $r["type"] == "http://purl.org/dc/dcmitype/Text") $save = false;
                if(!$r["description"] && $r["type"] == "http://purl.org/dc/dcmitype/Text")      $save = false;
                if($r["type"] == "http://purl.org/dc/dcmitype/StillImage")
                {
                    $access_uri = $r["accessURI"];
                    if(isset($this->accessURI[$access_uri])) $save = false;
                    else $this->accessURI[$access_uri] = '';
                    // if(in_array($access_uri, $broken_images)) $save = false;
                    if(self::image_is_broken($access_uri, $broken_images)) $save = false;
                }
                if(is_numeric(stripos($r["derivedFrom"], "http://eol.org/data_objects/"))) $save = false;
                if(is_numeric(stripos($r["derivedFrom"], ".eol.org"))) $save = false;
            }
            if($save) $this->archive_builder->write_object_to_file($c);
        }
    }

    private function image_is_broken($access_uri, $broken_images)
    {
        foreach($broken_images as $broken)
        {
            if(is_numeric(stripos($access_uri, $broken))) return true;
        }
        return false;
    }

    private function create_instances_from_taxon_object($records)
    {
        self::process_fields($records, "taxa");
    }

    private function get_objects($records)
    {
        self::process_fields($records, "objects");
    }

    private function get_vernaculars($records)
    {
        self::process_fields($records, "vernacular");
    }

    private function get_agents($records)
    {
        self::process_fields($records, "agent");
    }
    
    private function get_references($records)
    {
        self::process_fields($records, "reference");
    }

}
?>