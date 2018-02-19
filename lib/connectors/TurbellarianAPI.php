<?php
namespace php_active_record;
/* connector [185] Connector scrapes the partner's site, assembles the information and generates a DWC-A */
class TurbellarianAPI
{
    function __construct($folder)
    {
        $this->taxa = array();
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->resource_reference_ids = array();
        $this->resource_agent_ids = array();
        $this->taxon_ids = array();
        $this->object_ids = array();
        $this->domain = "http://turbellaria.umaine.edu";
        $this->taxa_url = $this->domain . "/turb3.php?action=1&code=";
        $this->rights_holder = "National Science Foundation - Turbellarian Taxonomic Database";
        $this->agents = array();
        $this->agents[] = array("role" => "compiler", "homepage" => "http://turbellaria.umaine.edu/", "name" => "Seth Tyler");
        $this->agents[] = array("role" => "compiler", "homepage" => "http://turbellaria.umaine.edu/", "name" => "Steve Schilling");
        $this->agents[] = array("role" => "compiler", "homepage" => "http://turbellaria.umaine.edu/", "name" => "Matt Hooge");
        $this->agents[] = array("role" => "compiler", "homepage" => "http://turbellaria.umaine.edu/", "name" => "Louise Bush");
        $this->SPM = "http://rs.tdwg.org/ontology/voc/SPMInfoItems";
        $this->EOL = 'http://www.eol.org/voc/table_of_contents';
        $this->TEMP_DIR = create_temp_dir() . "/";
        $this->dump_file = $this->TEMP_DIR . "turbellarian_dump.txt";
        $this->dump_file_hierarchy = $this->TEMP_DIR . "turbellarian_hierarchy_dump.txt";
        $this->dump_file_synonyms = $this->TEMP_DIR . "turbellarian_synonyms_dump.txt";
        $this->download_options = array('download_wait_time' => 1000000, 'timeout' => 9600, 'download_attempts' => 2, 'delay_in_minutes' => 1, 'expire_seconds' => 60*60*24*25);
    }

    function get_all_taxa()
    {
        self::initialize_dump_file();
        $this->agent_ids = self::get_object_agents($this->agents);
        // /* normal operation
        $this->save_data_to_text();
        $this->access_dump_file(false, "synonyms");
        $this->access_dump_file(false, "objects");
        $this->process_taxa();
        // */
        
        /* use this if the dump file has already been created - works okay!
        $dump_file = DOC_ROOT . "tmp/turbellarian_dump/turbellarian_dump.txt";
        $this->access_dump_file($dump_file, "synonyms");
        $this->access_dump_file($dump_file, "objects");
        $this->process_taxa($dump_file);
        */
        
        /* manually adding Bilateria */
        $rec = array();
        $rec["sciname"] = "Bilateria";
        $rec["taxon_id"] = "Bilateria";
        $rec["authorship"] = "";
        $rec["parent_id"] = "";
        $rec["status"] = "";
        $this->create_instances_from_taxon_object($rec);
        
        $this->create_archive();
        /* remove temp dir */
        recursive_rmdir($this->TEMP_DIR);
        echo ("\n temporary directory removed: " . $this->TEMP_DIR);
    }

    private function save_data_to_text()
    {
        $urls = array();
        $limit = 100; //hard-coded number of taxon ID //debug orig: 14543
        for ($i = 2; $i <= $limit; $i++) $urls[] = $this->taxa_url . $i; // $i first value is 2
        /* foreach(array(321, 512, 2336, 2337, 2376, 2776, 2797, 5116, 5426, 10894, 10895, 10978, 11393, 12032, 12276, 12823, 12949, 13985) as $i) $urls[] = $this->taxa_url . $i; */ //debug
        $j = 0;
        $total = count($urls);
        foreach($urls as $url)
        {
            $j++;
            echo "\n [$j of $total] $url \n";
            if($html = self::get_html($url))
            {
                if(preg_match("/code=(.*?)\"/ims", $url.'"', $match)) self::parse_html_then_save($html, $match[1]);
                else echo "\n investigate [a1]\n";
            }
        }
        $this->consolidate_hierarchy_and_taxa_list();
    }

    private function consolidate_hierarchy_and_taxa_list()
    {
        $dump_file = $this->dump_file;
        $dump_file_hierarchy = $this->dump_file_hierarchy;
        echo "\n accessing dump file [$dump_file]...\n";
        echo "\n accessing dump file hierarchy [$dump_file_hierarchy]...\n";
        $id_name = array();
        foreach(new FileIterator($dump_file) as $line_number => $line)
        {
            if($line)
            {
                $line = trim($line);
                if($records = json_decode($line, true))
                {
                    foreach($records as $rec)
                    {
                        $id = (string) $rec["taxon_id"];
                        $sciname = (string) $rec["sciname"];
                        $id_name[$id] = $sciname;
                    }
                }
            }
        }
        $recordz = array();
        $added_already = array();
        foreach(new FileIterator($dump_file_hierarchy) as $line_number => $line)
        {
            if($line)
            {
                $line = trim($line);
                if($records = json_decode($line, true))
                {
                    foreach($records as $rec)
                    {
                        if($id = (string) @$rec["taxon_id"])
                        {
                            if(!@$id_name[$id] && !in_array($id, array_keys($added_already)))
                            {
                                $sciname = trim(str_ireplace("&nbsp;", "", $rec["sciname"]));
                                if($sciname)
                                {
                                    echo "\n to be added: " . $sciname . " " . $rec["taxon_id"];
                                    $recordz[] = $rec;
                                    $added_already[$id] = "";
                                }
                            }
                        }
                    }
                }
            }
        }
        if($recordz) self::save_to_dump($recordz, $dump_file);
    }

    private function process_taxa($dump_file = false)
    {
        $synonyms = self::save_synonym_ids_to_array();
        $i = 0;
        if(!$dump_file) $dump_file = $this->dump_file;
        echo "\n accessing dump file [$dump_file]...\n";
        foreach(new FileIterator($dump_file) as $line_number => $line)
        {
            if($line)
            {
                $i++;
                $line = trim($line);
                if($records = json_decode($line, true))
                {
                    foreach($records as $rec)
                    {
                        if(!in_array($rec["taxon_id"], $synonyms))
                        {
                            if(in_array(@$rec["parent_id"], $synonyms)) $rec["parent_id"] = "";
                            echo "\n" . $rec["sciname"] . " " . $rec["taxon_id"] . " --> \n";
                            $this->create_instances_from_taxon_object($rec);
                        }
                    }
                }
            }
        }
        echo "\n [total recs from dump: $i] \n";
    }

    private function save_synonym_ids_to_array()
    {
        $i = 0;
        $synonyms = array();
        echo "\n accessing synonyms dump file [$this->dump_file_synonyms]...\n";
        if(!is_file($this->dump_file_synonyms)) return array();
        foreach(new FileIterator($this->dump_file_synonyms) as $line_number => $line)
        {
            if($line)
            {
                $i++;
                $line = trim($line);
                $synonyms[$line] = 1;
            }
        }
        echo "\n [total recs from synonyms dump: $i] \n";
        return array_keys($synonyms);
    }

    private function access_dump_file($dump_file = false, $type)
    {
        if($type == "objects") $synonyms = self::save_synonym_ids_to_array(); // will not assign objects to taxa that are synonyms
        $i = 0;
        if(!$dump_file) $dump_file = $this->dump_file;
        echo "\n accessing dump file [$dump_file]...\n";
        foreach(new FileIterator($dump_file) as $line_number => $line)
        {
            if($line)
            {
                $i++;
                $line = trim($line);
                if($records = json_decode($line, true))
                {
                    foreach($records as $rec)
                    {
                        if($type == "objects")
                        {
                            if(in_array($rec["taxon_id"], $synonyms)) continue;
                        }
                        echo "\n" . $rec["sciname"] . " " . $rec["taxon_id"] . " ---> \n";
                        self::process_record($rec, $type);
                    }
                }
            }
        }
        echo "\n [total recs from dump: $i] \n";
    }

    private function process_record($rec, $type)
    {
        if(!@$rec["links"]) return;
        foreach($rec["links"] as $link)
        {
            if(preg_match("/xxx(.*?)\">/ims", "xxx".$link, $arr)) $url = $this->domain . $arr[1];
            // debug - not commented in normal operation
            if($type == "objects")
            {
                if    (is_numeric(stripos($link, "diagnosis")))     self::process_diagnosis($rec, $url);
                elseif(is_numeric(stripos($link, "fig. avail.")))   self::process_images($rec, $url);
                elseif(is_numeric(stripos($link, "dist'n")))        self::process_distribution($rec, $url);
                elseif(is_numeric(stripos($link, "notes")))         self::process_notes($rec, $url);
            }
            elseif($type == "synonyms")
            {
                if(is_numeric(stripos($link, "synonyms"))) self::process_synonyms($rec, $url);
            }
        }
    }

    private function process_notes($rec, $url)
    {
        if($html = self::get_html($url))
        {
            if(preg_match_all("/<hr>(.*?)<hr>/ims", $html, $arr))
            {
                $notes = "";
                foreach($arr[1] as $row)
                {
                    if($notes <> "") $notes .= "<br><br>";
                    $notes .= $row;
                }
                $notes = trim($notes);
                $notes = strip_tags($notes, "<br><a>");
                $notes = str_ireplace('<a href="/turb', '<a href="' . $this->domain . '/turb', $notes);
                if($notes)
                {
                    $reference_ids = array();
                    self::process_text($rec, $url, $notes, $this->EOL . "#Taxonomy", $reference_ids);
                }
                else
                {
                    echo "\n investigate no notes 2 [$url]\n"; // e.g. http://turbellaria.umaine.edu/turb3.php?action=12&code=514&syn=2
                    print_r($rec);
                }
            }
            else
            {
                if(!preg_match("/No notes for this taxon(.*?)xxx/ims", $html."xxx", $arr))
                {
                    echo "\n investigate no notes [$url]\n";
                    print_r($rec);
                }
            }
        }
    }

    private function process_synonyms($rec, $url)
    {
        $synonyms = array();
        if($html = self::get_html($url))
        {
            $html = str_ireplace("<td >", "<td>", $html);
            $html = str_ireplace("<td>&nbsp;</td>", "<td></td>", $html);
            if(preg_match("/\"table of synonyms\">(.*?)<\/table>/ims", $html, $arr))
            {
                if(preg_match_all("/<tr>(.*?)<\/tr>/ims", $arr[1], $match))
                {
                    foreach($match[1] as $row)
                    {
                        if(preg_match_all("/<td>(.*?)<\/td>/ims", $row, $match2))
                        {
                            $cols = $match2[1];
                            $synonym = ""; $syn_id = ""; $syn_url = ""; $syn_author = ""; $syn_remark = "";
                            if(preg_match("/>(.*?)</ims", $cols[1], $match3))
                            {
                                $synonym = trim($match3[1]);
                                if(self::starts_with_small_letter($synonym))
                                {
                                    $synonym = trim(strip_tags($cols[1]));
                                    $synonym = trim(str_replace("  ", " ", $synonym));
                                    if(self::starts_with_small_letter($synonym)) // e.g. http://turbellaria.umaine.edu/turb3.php?action=6&code=5428
                                    {
                                        $synonym = trim(trim($cols[0]) . " " . $synonym);
                                    }
                                }
                            }
                            if(preg_match("/\&code=(.*?)\"/ims", $cols[1], $match3)) $syn_id = trim($match3[1]);
                            if(preg_match("/href=\"(.*?)\"/ims", $cols[1], $match3)) $syn_url = $this->domain . trim($match3[1]);
                            $syn_author = trim($cols[2]);
                            $syn_remark = trim($cols[3]);
                            if($synonym && $syn_id) $synonyms[] = array("synonym"=>$synonym, "syn_id"=>$syn_id, "syn_url"=>$syn_url, "syn_author"=>$syn_author, "syn_remark"=>$syn_remark);
                        }
                    }
                }
            }
            else echo "\n investigate no 'table of synonyms' \n";
        }
        if($synonyms)
        {
            $rec["synonyms"] = $synonyms;
            self::create_synonym($rec);
        }
    }

    private function process_diagnosis($rec, $url)
    {
        if($html = Functions::lookup_with_cache($url, $this->download_options))
        {
            if(preg_match_all("/<pre>(.*?)<\/pre>/ims", $html, $arr) || preg_match_all("/<hr>(.*?)<hr>/ims", $html, $arr) || preg_match_all("/<hr>(.*?)xxx/ims", $html."xxx", $arr)) //ditox
            {
                $diagnosis = "";
                foreach($arr[1] as $row)
                {
                    if($diagnosis <> "") $diagnosis .= "<br><br>";
                    $diagnosis .= $row;
                }
                if($diagnosis)
                {
                    $reference_ids = array();
                    self::process_text($rec, $url, $diagnosis, $this->SPM . "#DiagnosticDescription", $reference_ids);
                }
                else
                {
                    echo "\n investigate no diagnosis 2 [$url]\n";
                    print_r($rec);
                }
            }
            else
            {
                if(preg_match("/No diagnosis for this taxon(.*?)xxx/ims", $html."xxx", $arr)) echo "\n site has: 'No diagnosis for this taxon' [$url]\n";
                else
                {
                    echo "\n investigate no diagnosis [$url]\n";
                    print_r($rec);
                }
            }
        }
    }

    private function process_distribution($rec, $url)
    {
        if($html = self::get_html($url))
        {
            $html = str_ireplace("<td >", "<td>", $html);
            $html = str_ireplace("<td>&nbsp;</td>", "<td></td>", $html);
            if(preg_match_all("/<tr>(.*?)<\/tr>/ims", $html, $arr))
            {
                $dist = "";
                $references = array(); $processed_the_record = false;
                foreach($arr[1] as $row)
                {
                    if(preg_match_all("/<td>(.*?)<\/td>/ims", $row, $match))
                    {
                        $cols = $match[1];
                        if(count($cols) != 11) continue;
                        if($dist <> "") $dist .= "<br><br>";
                        $dist .= $cols[1];
                        if($cols[4]) $dist .= "<br>Collection date: " . $cols[4];
                        if($cols[5]) $dist .= "<br>Kind: " . $cols[5];
                        if($cols[6]) $dist .= "<br>Depth: " . $cols[6];
                        if($cols[7]) $dist .= "<br>Substrate: " . $cols[7];
                        if($cols[8]) $dist .= "<br>Salin: " . $cols[8];
                        if($cols[9]) $dist .= "<br>Comments: " . $cols[9];
                        if($cols[10]) $references[] = $cols[10];
                        $processed_the_record = true;
                    }
                }
                if($dist)
                {
                    $reference_ids = self::process_references(array_unique($references));
                    self::process_text($rec, $url, $dist, $this->SPM . "#Distribution", $reference_ids);
                }
                else
                {   if(!$processed_the_record)
                    {
                        echo "\n investigate no distribution 2 [$url]\n";
                        print_r($rec);
                    } 
                    else echo "\n site doesn't have distribution data \n";
                }
            }
            else
            {   if(preg_match("/No geographic data on this taxon(.*?)xxx/ims", $html."xxx", $arr)) echo "\n site has: 'No geographic data on this taxon' [$url]\n";
                else 
                {
                    echo "\n investigate no distribution [$url]\n";
                    print_r($rec);
                }
            }
        }
    }

    private function process_references($references)
    {
        $refs = array();
        foreach($references as $ref)
        {
            //<a href="/turb3.php?action=10&litrec=14420&code=11394">Martens PM, Curini-Galletti MC (1993)</a>: 91
            if(preg_match("/href=\"(.*?)\"/ims", $ref, $match))
            {
                $ref_url = $this->domain . $match[1];
                if($html = self::get_html($ref_url))
                {
                    $html = str_ireplace("<td >", "<td>", $html);
                    if(preg_match_all("/<td>(.*?)<\/td>/ims", $html, $arr))
                    {
                        $whole = "";
                        foreach($arr[1] as $part) $whole .= $part . ". ";
                        $author  = @$arr[1][0];
                        $date    = @$arr[1][1];
                        // $uri     = @$arr[1][2]; can be used later on if needed
                        $title   = @$arr[1][3];
                        $journal = @$arr[1][4];
                        $whole = str_replace("..", ".", $whole);
                        $whole = str_ireplace('<a href="/', '<a href="' . $this->domain . '/', $whole);
                        $refs[] = array("author"=>$author, "date"=>$date, "uri"=>$ref_url, "title"=>$title, "journal"=>$journal, "whole"=>$whole);
                    }
                }
            }
        }
        // start generate the archive for reference
        $ref_ids = array();
        foreach($refs as $ref)
        {
            if($ref)
            {
                $r = new \eol_schema\Reference();
                $r->authorList  = (string) trim($ref["author"]);
                $r->created     = (string) trim($ref["date"]);
                $r->uri         = (string) trim($ref["uri"]);
                $r->title       = (string) trim($ref["title"]);
                $r->full_reference  = (string) trim($ref["whole"]);
                $r->identifier      = md5($r->full_reference);
                if(!in_array($r->identifier, $this->resource_reference_ids))
                {
                   $this->resource_reference_ids[] = $r->identifier;
                   $this->archive_builder->write_object_to_file($r);
                }
                $ref_ids[] = $r->identifier;
            }
        }
        return $ref_ids;
    }

    private function process_text($rec, $source_url, $description, $subject, $reference_ids = array())
    {
        $identifier = self::get_identifier($source_url);
        $description = Functions::import_decode($description);
        if(!Functions::is_utf8($description)) return;
        $mr = new \eol_schema\MediaResource();
        if($reference_ids)  $mr->referenceID = implode("; ", $reference_ids);
        if($this->agent_ids)      $mr->agentID = implode("; ", $this->agent_ids);
        $mr->taxonID        = (string) $rec['taxon_id'];
        $mr->identifier     = $identifier;
        $mr->type           = "http://purl.org/dc/dcmitype/Text";
        $mr->language       = 'en';
        $mr->format         = "text/html";
        $mr->furtherInformationURL = (string) $source_url;
        $mr->CVterm         = $subject;
        $mr->Owner          = $this->rights_holder;
        $mr->title          = "";
        $mr->UsageTerms     = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
        $mr->audience       = 'Everyone';
        $mr->description    = (string) $description;
        if(!in_array($mr->identifier, $this->object_ids)) 
        {
           $this->object_ids[] = $mr->identifier;
           $this->archive_builder->write_object_to_file($mr);
        }
    }

    private function process_images($rec, $url)
    {
        if($html = self::get_html($url))
        {
            // <img src="thb/11395a_thb.gif" alt="fig Calviria solaris">
            $source_urls = self::get_source_urls($html);
            if(preg_match_all("/src=\"thb\/(.*?)\"/ims", $html, $arr))
            {
                // http://turbellaria.umaine.edu/gif/11397a.gif
                $index = 0;
                foreach($arr[1] as $image)
                {
                    // from: http://turbellaria.umaine.edu/thb/12223a_thb.gif to: http://turbellaria.umaine.edu/gif/12223a.gif
                    $info = self::get_folder_of_image($image);
                    $folder = $info["folder"];
                    $extension = $info["extension"];
                    if(!$folder)
                    {
                        // e.g <img src="thb/13158jpg_thb.158" alt="fig Kuma asilhas"> http://turbellaria.umaine.edu/turb3.php?action=2&code=13158&smk=0
                        if(is_numeric($extension))
                        {
                            echo "\n [$image] \n";
                            $parts = explode(".", $image);
                            $image = $parts[0];
                            $image = str_ireplace("jpg_thb", "_thb.jpg", $image);
                            $image = str_ireplace("gif_thb", "_thb.gif", $image);
                            $image = str_ireplace("png_thb", "_thb.png", $image);
                            echo "\n [$image] \n"; 
                            $info = self::get_folder_of_image($image);
                            $folder = $info["folder"];
                            $extension = $info["extension"];
                            if(!$folder) echo "\n investigate no image 2 [$url][$extension] \n";
                        }
                        else
                        {
                            echo "\n investigate no image [$url][$extension] \n";
                            print_r($rec);
                            continue;
                        }
                    }
                    $mediaURL = $this->domain . $folder . str_ireplace("_thb", "", $image);
                    $mr = new \eol_schema\MediaResource();
                    if($this->agent_ids)      $mr->agentID = implode("; ", $this->agent_ids);
                    $mr->taxonID        = (string) $rec["taxon_id"];
                    $mr->identifier     = (string) $mediaURL;
                    $mr->type           = "http://purl.org/dc/dcmitype/StillImage";
                    $mr->language       = 'en';
                    $mr->format         = Functions::get_mimetype($mediaURL);
                    $mr->title          = "";
                    $mr->CreateDate     = "";
                    $mr->Owner          = $this->rights_holder;
                    $mr->rights         = "";
                    $mr->UsageTerms     = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
                    $mr->audience       = 'Everyone';
                    $mr->description    = "";
                    $mr->accessURI      = $mediaURL;
                    if(@$source_urls[$index]) $mr->furtherInformationURL = @$source_urls[$index];
                    if(!in_array($mr->identifier, $this->object_ids)) 
                    {
                       $this->object_ids[] = $mr->identifier;
                       $this->archive_builder->write_object_to_file($mr);
                    }
                    $index++;
                }
            }
        }
    }
    
    private function get_source_urls($html)
    {
        $urls = array();
        if(preg_match_all("/href=\"\/turb3\.php\?action\=7(.*?)\"/ims", $html, $arr))
        {
            foreach($arr[1] as $path) $urls[] = $this->domain . "/turb3.php?action=7" . $path;
        }
        return $urls;
    }

    private function get_folder_of_image($image)
    {
        $path_info = pathinfo($image);
        $extension = strtolower($path_info['extension']);
        if(in_array($extension, array("jpg", "png"))) $folder = "/img/";
        elseif($extension == "gif") $folder = "/gif/";
        else $folder = false;
        return array("folder" => $folder, "extension" => $extension);
    }

    private function curl_get_file_contents($URL)
    {
        $c = curl_init();
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_URL, $URL);
        $contents = curl_exec($c);
        curl_close($c);
        if ($contents) return $contents;
        else return FALSE;
    }

    private function get_html($url)
    {
        if($html = Functions::lookup_with_cache($url, $this->download_options)) return $html;
        else
        {
            if($html = self::curl_get_file_contents($url))
            {
                echo "\n Got it using 'curl_get_file_contents()' \n";
                return $html;
            }
        }
        return false;
    }

    private function parse_html_then_save($html, $id)
    {
        if($parts = explode("<hr>", $html))
        {
            $hierarchy = self::get_hierarchy($parts[1], $id);
            self::save_to_dump($hierarchy, $this->dump_file_hierarchy);
            $records = self::get_taxa_details($parts[2], $hierarchy); 
            self::save_to_dump($records, $this->dump_file);
            echo "\n count taxa records: " . count($records);
        }
    }
    
    private function get_taxa_details($string, $hierarchy)
    {
        if(!$hierarchy) return array();
        $records = array();
        $last_hierarchy = $hierarchy[count($hierarchy)-1];
        if(self::starts_with_small_letter($last_hierarchy["sciname"])) return; //http://turbellaria.umaine.edu/turb3.php?action=1&code=2312, 2789, 2912, 3398
        if(count($hierarchy) == 1) // if 1 then process 'table of subtaxa', otherwise not
        {
            echo "\n will process 'table of subtaxa' \n";
            if(preg_match("/table of subtaxa\"><tr>(.*?)<\/tr>/ims", $string, $match))
            {
                if($record = self::process_row($match[1], $hierarchy[0]["taxon_id"], $last_hierarchy)) $records[] = $record;
            }
        }
        if(preg_match("/table of taxa\">(.*?)<\/table/ims", $string, $match)) // start process 'table of taxa'
        {
            $string = $match[1];
            $string = str_ireplace('<tr bgcolor="#ddffff">', '<tr>', $string);
            if(preg_match_all("/<tr>(.*?)<\/tr>/ims", $string, $match))
            {
                foreach($match[1] as $row)
                {
                    if($record = self::process_row($row, NULL, $last_hierarchy)) $records[] = $record;
                }
            }
        }
        // start exclude synonyms
        $i = 0;
        foreach($records as $rec)
        {
            foreach($rec["links"] as $link)
            {
                if(preg_match("/\(syn\)(.*?)</ims", $link, $match)) $records[$i] = NULL;
            }
            $i++;
        }
        return array_values(array_filter($records)); //remove null arrays, then reindex keys
    }
    
    private function process_row($string, $taxon_id = null, $last_hierarchy)
    {
        $status = "";
        $string = str_ireplace(" >", ">", $string);
        $string = str_ireplace("<td>&nbsp;</td>", "", $string);
        /*
        <th>Rhabditophora</th>
        <td>Ehlers, 1985</td>
        <td  style="font-size:10">(2 subtax.)</td>
        <td><a href="/turb3.php?action=12&code=12276&syn=0">notes</a></td>
        <td><a href="/turb3.php?action=11&code=12276&syn=0">literature</a></td>
        */
        if(preg_match_all("/<td>(.*?)<\/td>/ims", $string, $match)) $cols = array_map('trim', $match[1]);
        if(preg_match("/<th>(.*?)<\/th>/ims", $string, $match)) //row from 'table of subtaxa'
        {
            $sciname = $match[1];
            $authorship = $cols[0];
            if($info = self::get_line_info($string, TRUE)) $links = $info["links"];
            else echo "\n investigate cannot get line info 01 \n";
        }
        else //rows from 'table of taxa'
        {
            $authorship = $cols[0];
            /*
            <td  title="3"><a href="/turb3.php?action=1&code=13985">Trepaxonemata</a></td>
            <td  title="4"><a href="/turb3.php?action=1&code=3002">Dolichomacrostomidae</a></td>
            <td  title="1"><a href="/turb3.php?action=1&code=2998"><font color="00cc00">Antromacrostomum</font></a></td>
            <td  title="1"><a href="/turb3.php?action=1&code=12032"><font color="00cc00">Dunwichia</font></a></td>
            <td >beaufortense</td>
            */
            //get taxon - initial test
            if(preg_match("/title=\"(.*?)<\/td>/ims", $string, $match))
            {
                $taxon = trim($match[1]);
                $taxon = substr($taxon, 3, strlen($taxon));
                $taxon = strip_tags($taxon);
            }
            else $taxon = strip_tags($cols[0]);
            echo "\n taxon: [$taxon]\n";
            if(self::starts_with_small_letter($taxon)) // meaning taxon is 'species' part only
            {
                echo " - append (last hierarchy) with species \n";
                $genus_name = trim($last_hierarchy["sciname"]);
                if($cols[0] == $taxon)
                {
                    $sciname = $genus_name . " " . $cols[0];
                    $authorship = $cols[1];
                }
                else // e.g. http://turbellaria.umaine.edu/turb3.php?action=1&code=512
                {
                    $sciname = $genus_name . " " . $taxon;
                    if($cols[0] == strip_tags($cols[0])) $authorship = $cols[0];
                    else $authorship = $cols[1];
                }
                echo "\n sciname: [$sciname] taxon is: [$taxon]";
                if($info = self::get_line_info($string, FALSE, TRUE))
                {
                    $taxon_id = $info["taxon_id"];
                    $links = $info["links"];
                    $status = @$info["status"];
                }
                else echo "\n investigate cannot get line info 02 \n";
            }
            else
            {
                if($info = self::get_line_info($string))
                {
                    $sciname = $info["sciname"];
                    $taxon_id = $info["taxon_id"];
                    $links = $info["links"];
                    $status = @$info["status"];
                }
                else echo "\n investigate cannot get line info 02 \n";
            }
        }
        $sciname = (string) trim($sciname);
        if($last_hierarchy)
        {
            if($sciname != (string) trim($last_hierarchy["sciname"])) $parent_id = $last_hierarchy["taxon_id"];
            else $parent_id = $last_hierarchy["parent_id"];
        }
        else $parent_id = "";
        // e.g. Pseudostomum - http://turbellaria.umaine.edu/turb3.php?action=1&code=3319
        if($sciname == $authorship) $authorship = $cols[1];
        $sciname = trim(str_ireplace("&nbsp;", "", $sciname));
        if(!$sciname) return array();
        return array("sciname" => $sciname, "authorship" => $authorship, "taxon_id" => $taxon_id, "parent_id" => $parent_id, "links" => $links, "status" => $status);
    }

    private function remove_quotes($string)
    {
        return str_replace(array("'", '"'), "", $string);
    }

    private function get_status($string)
    {
        $status = "";
        if(preg_match("/color=\"#00cc00\"(.*?)>/ims", $string, $match))     $status = "uncertain";
        elseif(preg_match("/color=#00cc00(.*?)>/ims", $string, $match))     $status = "uncertain";
        elseif(preg_match("/color=\'#00cc00\'(.*?)>/ims", $string, $match)) $status = "uncertain";
        elseif(preg_match("/color=\"00cc00\"(.*?)>/ims", $string, $match))  $status = "uncertain";
        return $status;
    }
    
    private function get_line_info($string, $just_links = FALSE, $no_sciname = FALSE)
    {
        $status = self::get_status($string);
        //<a href="/turb3.php?action=1&code=871">Ancoratheca</a></td>
        if(preg_match_all("/href=\"(.*?)<\/td>/ims", $string, $match))
        {
            $sciname = "";
            $links = $match[1];
            if($just_links) return array("links" => array_filter($links));
            $i = 0;
            foreach($links as $link)
            {
                $pos = stripos($link, "action=1&");
                if(is_numeric($pos)) // to get sciname e.g. 'Microstomum' http://turbellaria.umaine.edu/turb3.php?action=1&code=3061
                {
                     $links[$i] = null; // because you don't need this link
                     if(preg_match("/\">(.*?)<\/a>/ims", $link, $match)) $sciname = $match[1];
                     else echo "\n investigate no sciname\n";
                }
                if(preg_match("/code=(.*?)&/ims", $link, $match)) $taxon_id = $match[1];
                elseif(preg_match("/code=(.*?)\"/ims", $link, $match)) $taxon_id = $match[1];
                else echo "\n investigate no taxon_id \n";
                $i++;
            }
            if($no_sciname) $sciname = ""; //just not to have 'Undefined variable' message
            else // e.g. http://turbellaria.umaine.edu/turb3.php?action=1&code=3319 --- Pseudostomum
            {
                if(!$sciname)
                {
                    if(preg_match_all("/<td>(.*?)<\/td>/ims", $string, $match2)) $sciname = $match2[1][0];
                }
            }
            return array("sciname" => $sciname, "taxon_id" => $taxon_id, "links" => array_filter($links), "status" => $status);
        }
        return array();
    }

    private function get_hierarchy($string, $id)
    {
        if(preg_match("/<ul>(.*?)<\/ul>/ims", $string, $match))
        {
            $string = $match[1];
            $parts = explode("<ul>", $string);
            $parts = array_map('trim', $parts);
            $i = 0;
            foreach($parts as $part)
            {
                $name_authorship = explode("&nbsp; &nbsp; &nbsp; ", $part);
                $sciname = $name_authorship[0];
                if(preg_match("/\">(.*?)<\/a>/ims", $sciname, $match)) $sciname = trim($match[1]);
                $authorship = @$name_authorship[1]; // needs '@' e.g. http://turbellaria.umaine.edu/turb3.php?action=1&code=2305, 3664
                if(preg_match("/code=(.*?)\"/ims", $part, $match)) $taxon_id = $match[1];
                if($i+1 == count($parts)) $taxon_id = $id;
                
                $sciname = trim(str_ireplace("&nbsp;", "", $sciname));
                if($sciname) $parts[$i] = array("sciname" => self::remove_quotes($sciname), "authorship" => $authorship, "taxon_id" => $taxon_id);
                else $parts[$i] = null;
                $i++;
            }
            $parts = array_filter($parts);
            if($parts)
            {
                $i = 0;
                foreach($parts as $part)
                {
                    if($parent = @$parts[$i-1]["taxon_id"]) $parts[$i]["parent_id"] = $parent;
                    else $parts[$i]["parent_id"] = "Bilateria";
                    $i++;
                }
                $last_rec = $parts[count($parts)-1];
                if(self::starts_with_small_letter($last_rec["sciname"])) // if last entry of hierarchy is lower case then append 2nd to the last with the last
                {
                    echo "\n append 2nd to the last with the last \n";
                    if($parts) $parts[count($parts)-1]["sciname"] = @$parts[count($parts)-2]["sciname"] . " " . $parts[count($parts)-1]["sciname"];
                }
                /* e.g. http://turbellaria.umaine.edu/turb3.php?action=1&code=2844
                last 2 recs is lower case, just ignore all bec you'll get them in another call anyway */
                if($second_to_the_last = @$parts[count($parts)-2])
                {
                    if(self::starts_with_small_letter(@$second_to_the_last["sciname"])) return array();
                }
            }
            return $parts;
        }
    }

    private function create_instances_from_taxon_object($rec)
    {
        $sciname = (string) trim(strip_tags($rec["sciname"]));

        if(self::starts_with_small_letter($sciname))
        {
            echo "\n investigate: [$sciname] is small caps \n";
            return;
        }
        
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID                     = (string) $rec["taxon_id"];
        $taxon->scientificName              = $sciname;
        $taxon->scientificNameAuthorship    = (string) $rec["authorship"];
        $taxon->furtherInformationURL       = (string) $this->taxa_url . $rec["taxon_id"];
        $taxon->parentNameUsageID           = (string) $rec["parent_id"];
        $taxon->taxonRemarks                = (string) @$rec["status"];
        if(!$rec["parent_id"] && $sciname != "Bilateria")
        {
            echo "\n investigate [$sciname] no parent_id \n";
            print_r($rec);
        }
        if(!isset($this->taxon_ids[$taxon->taxonID]))
        {
            $this->taxa[$taxon->taxonID] = $taxon;
            $this->taxon_ids[$taxon->taxonID] = 1;
        }
    }

    private function create_synonym($rec)
    {
        $exclude = array('"species"');
        foreach($rec["synonyms"] as $syn)
        {
            if(in_array($syn["synonym"], $exclude)) continue;
            $synonym = new \eol_schema\Taxon();
            if(!Functions::is_utf8($syn["synonym"]) || !Functions::is_utf8($syn["syn_author"])) continue;
            $synonym->taxonID                       = (string) $syn["syn_id"];
            if(self::starts_with_small_letter($syn["synonym"])) // meaning taxon is 'species' part only
            {
                $parts = explode(" ", $rec["sciname"]);
                $syn["synonym"] = $parts[0] . " " . $syn["synonym"];
            }
            $synonym->scientificName                = (string) $syn["synonym"];
            $synonym->scientificNameAuthorship      = (string) $syn["syn_author"];
            $synonym->acceptedNameUsageID           = (string) $rec["taxon_id"];
            $synonym->taxonomicStatus               = "synonym";
            $synonym->taxonRemarks                  = (string) $syn["syn_remark"];
            $synonym->furtherInformationURL         = (string) $syn["syn_url"];
            
            // special case e.g. http://turbellaria.umaine.edu/turb3.php?action=6&code=1412
            if(@$rec["parent_id"] == 10408) $rec["parent_id"] =  "";
            
            $synonym->parentNameUsageID             = (string) $rec["parent_id"];
            if(!$synonym->scientificName) continue;
            if($synonym->scientificName == $rec["sciname"]) 
            {
                echo "\n alert: synonym == valid name \n";
                print_r($rec);
                self::save_to_dump($synonym->taxonID, $this->dump_file_synonyms);
                continue;
            }
            if(!isset($this->taxon_ids[$synonym->taxonID]))
            {
                $this->archive_builder->write_object_to_file($synonym);
                $this->taxon_ids[$synonym->taxonID] = 1;
                self::save_to_dump($synonym->taxonID, $this->dump_file_synonyms);
            }
        }
    }

    function create_archive()
    {
        foreach($this->taxa as $t) $this->archive_builder->write_object_to_file($t);
        $this->archive_builder->finalize(TRUE);
    }

    private function save_to_dump($array, $filename)
    {
        if(is_array($array)) $item = json_encode($array);
        else $item = $array;
        if(!($WRITE = fopen($filename, "a")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$filename);
          return;
        }
        fwrite($WRITE, $item . "\n");
        fclose($WRITE);
    }

    private function initialize_dump_file()
    {
        if(!($WRITE = fopen($this->dump_file, "w")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$this->dump_file);
          return;
        }
        fclose($WRITE);
    }

    private function get_object_agents($agents)
    {
        $agent_ids = array();
        foreach($agents as $agent)
        {
            $r = new \eol_schema\Agent();
            $r->term_name = $agent["name"];
            $r->identifier = md5($agent["name"]."|".$agent["role"]);
            $r->term_homepage = $agent["homepage"];
            $r->agentRole = $agent["role"];
            $agent_ids[] = $r->identifier;
            if(!in_array($r->identifier, $this->resource_agent_ids))
            {
               $this->resource_agent_ids[] = $r->identifier;
               $this->archive_builder->write_object_to_file($r);
            }
        }
        return $agent_ids;
    }

    function get_identifier($url)
    {
        /* e.g. http://turbellaria.umaine.edu/turb2.php?action=16&code=5933&valid=6001 */
        $arr = parse_url($url);
        $identifier = $arr["query"];
        $identifier = str_ireplace("action=", "", $identifier);
        $identifier = str_ireplace("&code=", "-", $identifier);
        $identifier = str_ireplace("&valid=", "-", $identifier);
        return trim($identifier);
    }

    function check_taxon_tab($file, $synonyms_only_YN)
    {
        $i = 0;
        echo "\n accessing [$file]...\n";
        // list taxa that starts with small letter
        $names = array();
        foreach(new FileIterator($file) as $line_number => $line)
        {
            if($line)
            {
                $i++;
                if($i == 1) continue;
                $line = trim($line);
                $cols = explode("\t", $line);
                $id      = (string) trim($cols[0]);
                $sciname = (string) trim($cols[4]);
                if(self::starts_with_small_letter($sciname))
                {
                    echo "\n small caps [$sciname]\n";
                    print_r($cols);
                } 
                if($synonyms_only_YN)
                {
                    if(@$cols[6] != "synonym")
                    {
                        $names[] = $sciname;
                        $id_name[$id] = $sciname;
                    }
                }
                else
                {
                    $names[] = $sciname;
                    $id_name[$id] = $sciname;
                }
            }
        }
        echo "\n count of names: " . count($names) . "\n";
        echo "\n count of names: " . count($id_name) . "\n";
        echo "\n [total recs from taxon.tab: $i] \n";
        // list taxa with parent that has no info
        $no_parent_info = 0;
        $i = 0;
        foreach(new FileIterator($file) as $line_number => $line)
        {
            if($line)
            {
                $i++;
                if($i == 1) continue;
                $line = trim($line);
                $cols = explode("\t", $line);
                if($parent = (string) trim($cols[3]))
                {
                    if(!@$id_name[$parent])
                    {
                        $no_parent_info++;
                        echo "\n no parent info -- parent_id=$parent \n";
                        print_r($cols);
                    }
                }
            }
        }
        echo "\n taxa with parent that has no info: " . $no_parent_info . "\n";
    }

    private function starts_with_small_letter($orig_string)
    {
        $orig_string = trim($orig_string);
        $string = $orig_string;
        $first_char = substr($string, 0, 1);
        if($first_char == "(") $first_char = substr($string, 1, 1);
        if(!ctype_alpha($first_char)) return true;
        if(ctype_lower($first_char))
        {
            if($orig_string != "incertae sedis") return true;
        }
        return false;
    }

}
?>