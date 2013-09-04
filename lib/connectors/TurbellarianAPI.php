<?php
namespace php_active_record;
/* connector [185] 
Connector scrapes the partner's site, assembles the information and generates a DWC-A
*/

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

        // debug - uncomment in normal operation
        $this->TEMP_DIR = create_temp_dir() . "/";
        $this->dump_file = $this->TEMP_DIR . "turbellarian_dump.txt";
        $this->dump_file_hierarchy = $this->TEMP_DIR . "turbellarian_hierarchy_dump.txt";
    }

    function get_all_taxa()
    {
        self::initialize_dump_file();

        // /* normal operation
        $this->save_data_to_text();
        $this->agent_ids = self::get_object_agents($this->agents);
        $this->consolidate_hierarchy_and_taxa_list();
        $this->access_dump_file();
        // */
        
        /* // use this if the dump file has already been created
        $this->agent_ids = self::get_object_agents($this->agents);
        $dump_file = DOC_ROOT . "tmp/turbellarian_dump_2/turbellarian_dump.txt";
        $dump_file_hierarchy = DOC_ROOT . "tmp/turbellarian_dump_2/turbellarian_hierarchy_dump.txt";
        $this->consolidate_hierarchy_and_taxa_list($dump_file, $dump_file_hierarchy);
        $this->access_dump_file($dump_file);
        */

        $this->create_archive();
        // remove temp dir
        recursive_rmdir($this->TEMP_DIR);
        echo ("\n temporary directory removed: " . $this->TEMP_DIR);
    }

    private function save_data_to_text()
    {
        $urls = array(); // tests 2776 12823 12276 13985 12823 2797 [12032 - 2776 ] 5426-trinomial case
        $limit = 14543; //hard-coded number of taxon ID //debug orig:13998   |   new:14543 | curl error diagnosis 11393
        for ($i = 2; $i <= $limit; $i++) $urls[] = $this->taxa_url . $i; // $i first value is 2
        $j = 0;
        $total = count($urls);
        foreach($urls as $url)
        {
            $j++;
            echo "\n [$j of $total] $url \n";
            if($html = self::get_html($url))
            {
                if(preg_match("/code=(.*?)\"/ims", $url.'"', $match)) $record = self::parse_html($html, $match[1]);
                else echo "\n investigate [a1]\n";
            }
        }
    }

    private function consolidate_hierarchy_and_taxa_list($dump_file = false, $dump_file_hierarchy = false)
    {
        if(!$dump_file) $dump_file = $this->dump_file;
        if(!$dump_file_hierarchy) $dump_file_hierarchy = $this->dump_file_hierarchy;
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

        $records = array();
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
                        $id = (string) $rec["taxon_id"];
                        if(!@$id_name[$id] && !in_array($id, array_keys($added_already)))
                        {
                            echo "\n to be added: " . $rec["sciname"];
                            $records[] = $rec;
                            $added_already[$id] = "";
                        }
                    }
                }
            }
        }
        if($records) self::save_to_dump($records, $dump_file);
    }

    private function access_dump_file($dump_file = false)
    {
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
                        echo "\n" . $rec["sciname"] . " --> \n";
                        self::process_record($rec);
                    }
                }
            }
        }
        echo "\n [total recs from dump: $i] \n";
    }

    private function process_record($rec)
    {
        /*
        [sciname] => Calviria banyulensis
        [authorship] => Martens & Curini-Galletti, 1993
        [taxon_id] => 11397
        [parent_id] => 11394
        [links] => Array
            (
                [0] => /turb3.php?action=2&code=11397&smk=0"> images<img src="/icons/small/movie.png" alt="fig. avail."></a>
                [1] => /turb3.php?action=11&code=11397&syn=0">literature</a>
                [2] => /turb3.php?action=16&code=11397&valid=0">dist'n</a>
            )
        */
        $this->create_instances_from_taxon_object($rec);
        if(!@$rec["links"]) return;
        foreach($rec["links"] as $link)
        {
            if(preg_match("/xxx(.*?)\">/ims", "xxx".$link, $arr)) $url = $this->domain . $arr[1];
            if    (is_numeric(stripos($link, "diagnosis")))     self::process_diagnosis($rec, $url);
            elseif(is_numeric(stripos($link, "fig. avail.")))   self::process_images($rec, $url);
            elseif(is_numeric(stripos($link, "dist'n")))        self::process_distribution($rec, $url);
            elseif(is_numeric(stripos($link, "synonyms")))      self::process_synonyms($rec, $url);
            elseif(is_numeric(stripos($link, "notes")))         self::process_notes($rec, $url);
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
                            if(preg_match("/>(.*?)</ims", $cols[1], $match3)) $synonym = trim($match3[1]);
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
        $rec["synonyms"] = $synonyms;
        self::create_synonym($rec);
    }

    private function process_diagnosis($rec, $url)
    {
        if($html = self::get_html($url))
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
        $this->archive_builder->write_object_to_file($mr);
    }

    private function process_images($rec, $url)
    {
        if($html = self::get_html($url))
        {
            // <img src="thb/11395a_thb.gif" alt="fig Calviria solaris">
            if(preg_match_all("/src=\"thb\/(.*?)\"/ims", $html, $arr))
            {
                // http://turbellaria.umaine.edu/gif/11397a.gif
                foreach($arr[1] as $image)
                {
                    /*
                    from: http://turbellaria.umaine.edu/thb/12223a_thb.gif
                    to  : http://turbellaria.umaine.edu/gif/12223a.gif
                    from: http://turbellaria.umaine.edu/thb/12223b_thb.jpg
                    to  : http://turbellaria.umaine.edu/img/12223b.jpg
                    */
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
                    // if($reference_ids)  $mr->referenceID = implode("; ", $reference_ids);
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
                    if(!in_array($mr->identifier, $this->object_ids)) 
                    {
                       $this->object_ids[] = $mr->identifier;
                       $this->archive_builder->write_object_to_file($mr);
                    }
                }
            }
        }
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
        $trials = 0;
        $html = false;
        while($trials <= 5)
        {
            $trials++;
            if($html = Functions::get_remote_file_fake_browser($url, array('download_wait_time' => 1000000, 'timeout' => 9600, 'download_attempts' => 2))) return $html;
            else
            {
                if($html = self::curl_get_file_contents($url))
                {
                    echo "\n Got it using 'curl_get_file_contents()' \n";
                    return $html;
                }
                else
                {
                    echo "\n\n investigate 01 [$url] will delay 5 minutes... then will try again [$trials]\n";
                    sleep(300);
                }
            }
        }
        if(!$html) echo "\n trials expired [$trials]\n";
        return false;
    }

    private function parse_html($html, $id)
    {
        if($parts = explode("<hr>", $html))
        {
            $hierarchy = self::get_hierarchy($parts[1], $id);
            self::save_to_dump($hierarchy, $this->dump_file_hierarchy);
            $records = self::get_taxa_details($parts[2], $hierarchy); 
            self::save_to_dump($records, $this->dump_file);
            print_r($records);
            echo "\n count: " . count($records);
        }
    }
    
    private function get_taxa_details($string, $hierarchy)
    {
        $records = array();
        $last_hierarchy = $hierarchy[count($hierarchy)-1];
        if(ctype_lower(substr($last_hierarchy["sciname"],0,1))) return; //http://turbellaria.umaine.edu/turb3.php?action=1&code=2312, 2789, 2912, 3398
        // proceed...
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
        // start remove red and green
        $i = 0;
        foreach($records as $rec)
        {
            if(preg_match("/color=\"#00cc00\"(.*?)>/ims", $rec["sciname"], $match))     $records[$i]["status"] = "uncertain";
            elseif(preg_match("/color=\"red\"(.*?)>/ims", $rec["sciname"], $match))     $records[$i] = NULL;
            elseif(preg_match("/color=#00cc00(.*?)>/ims", $rec["sciname"], $match))     $records[$i]["status"] = "uncertain";
            elseif(preg_match("/color=red(.*?)>/ims", $rec["sciname"], $match))         $records[$i] = NULL;
            elseif(preg_match("/color=\'#00cc00\'(.*?)>/ims", $rec["sciname"], $match)) $records[$i]["status"] = "uncertain";
            elseif(preg_match("/color=\'red\'(.*?)>/ims", $rec["sciname"], $match))     $records[$i] = NULL;
            elseif(preg_match("/color=\"00cc00\"(.*?)>/ims", $rec["sciname"], $match))  $records[$i]["status"] = "uncertain";
            $i++;
        }
        $records = array_values(array_filter($records)); //remove null arrays, then reindex keys
        return $records;
    }

    private function process_row($string, $taxon_id = null, $last_hierarchy)
    {
        $string = str_ireplace(" >", ">", $string);
        $string = str_ireplace("<td>&nbsp;</td>", "", $string);
        /*
        <th>Rhabditophora</th>
        <td>Ehlers, 1985</td>
        <td  style="font-size:10">(2 subtax.)</td>
        <td><a href="/turb3.php?action=12&code=12276&syn=0">notes</a></td>
        <td><a href="/turb3.php?action=11&code=12276&syn=0">literature</a></td>
        */
        if(preg_match_all("/<td>(.*?)<\/td>/ims", $string, $match)) $cols = $match[1];
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
            if(ctype_lower(substr($taxon, 0, 1)) || is_numeric(substr($taxon, 0, 1))) // meaning taxon is 'species' part only
            {
                echo " - append (last hierarchy) with species \n";
                $genus_name = trim($last_hierarchy["sciname"]);
                $sciname = $genus_name . " " . trim($cols[0]);
                $authorship = $cols[1];
                if($info = self::get_line_info($string, FALSE, TRUE))
                {
                    $taxon_id = $info["taxon_id"];
                    $links = $info["links"];
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
        return array("sciname" => $sciname, "authorship" => $authorship, "taxon_id" => $taxon_id, "parent_id" => $parent_id, "links" => $links);
    }

    private function remove_quotes($string)
    {
        return str_replace(array("'", '"'), "", $string);
    }

    private function get_line_info($string, $just_links = FALSE, $no_sciname = FALSE)
    {
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
            return array("sciname" => $sciname, "taxon_id" => $taxon_id, "links" => array_filter($links));
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
                $parts[$i] = array("sciname" => self::remove_quotes($sciname), "authorship" => $authorship, "taxon_id" => $taxon_id);
                $i++;
            }
            $i = 0;
            foreach($parts as $part)
            {
                if($parent = @$parts[$i-1]["taxon_id"]) $parts[$i]["parent_id"] = $parent;
                else $parts[$i]["parent_id"] = "Bilateria";
                $i++;
            }
            $last_rec = $parts[count($parts)-1];
            if(ctype_lower(substr($last_rec["sciname"], 0, 1))) // if last entry of hierarchy is lower case then append 2nd to the last with the last
            {
                echo "\n append 2nd to the last with the last \n";
                if($parts) $parts[count($parts)-1]["sciname"] = @$parts[count($parts)-2]["sciname"] . " " . $parts[count($parts)-1]["sciname"];
            }
            return $parts;
        }
    }

    function create_instances_from_taxon_object($rec)
    {
        $sciname = (string) trim(strip_tags($rec["sciname"]));
        $exclude = array("9-spinosa", "`n.sp.'", "conoceraea", "scientificName", "truncata");
        if(in_array($sciname, $exclude)) return;
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID                     = (string) $rec["taxon_id"];
        $taxon->scientificName              = $sciname;
        $taxon->scientificNameAuthorship    = (string) $rec["authorship"];
        $taxon->furtherInformationURL       = (string) $this->taxa_url . $rec["taxon_id"];
        $taxon->parentNameUsageID           = (string) $rec["parent_id"];
        $taxon->taxonRemarks                = (string) @$rec["status"];
        if(!$rec["parent_id"])
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
            if(!Functions::is_utf8($syn["synonym"]) || !Functions::is_utf8($syn["syn_author"])) return;
            $synonym->taxonID                       = (string) $syn["syn_id"];
            if(ctype_lower(substr($syn["synonym"], 0, 1))) // meaning taxon is 'species' part only
            {
                $parts = explode(" ", $rec["sciname"]);
                $syn["synonym"] = $parts[0] . " " . $syn["synonym"];
            }
            $synonym->scientificName                = (string) $syn["synonym"];
            $synonym->scientificNameAuthorship      = (string) $syn["syn_author"];
            $synonym->acceptedNameUsageID           = (string) $rec["taxon_id"];
            $synonym->taxonomicStatus               = (string) "synonym";
            $synonym->taxonRemarks                  = (string) $syn["syn_remark"];
            $synonym->furtherInformationURL         = (string) $syn["syn_url"];
            $synonym->parentNameUsageID             = (string) $rec["parent_id"];
            if(!$synonym->scientificName) return;
            if(!isset($this->taxon_ids[$synonym->taxonID]))
            {
                $this->archive_builder->write_object_to_file($synonym);
                $this->taxon_ids[$synonym->taxonID] = 1;
            }
        }
    }

    function create_archive()
    {
        foreach($this->taxa as $t)
        {
            $this->archive_builder->write_object_to_file($t);
        }
        $this->archive_builder->finalize(TRUE);
    }

    private function save_to_dump($array, $filename)
    {
        $WRITE = fopen($filename, "a");
        fwrite($WRITE, json_encode($array) . "\n");
        fclose($WRITE);
    }

    private function initialize_dump_file()
    {
        $WRITE = fopen($this->dump_file, "w");
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
        $names = array();
        $name_id = array();
        foreach(new FileIterator($file) as $line_number => $line)
        {
            if($line)
            {
                $i++;
                $line = trim($line);
                $cols = explode("\t", $line);
                /*
                Array
                (
                    [0] => taxonID
                    [1] => furtherInformationURL
                    [2] => acceptedNameUsageID
                    [3] => parentNameUsageID
                    [4] => scientificName
                    [5] => scientificNameAuthorship
                    [6] => taxonomicStatus
                    [7] => taxonRemarks
                )
                */
                $sciname = (string) trim($cols[4]);
                if($synonyms_only_YN)
                {
                    if(@$cols[6] != "synonym")
                    {
                        $names[] = $sciname;
                        $name_id[$sciname] = $cols[0];
                    }
                }
                else
                {
                    $names[] = $sciname;
                    $name_id[$sciname] = $cols[0];
                }
                $id = (string) trim($cols[0]);
                $id_name[$id] = $sciname;
            }
        }
        ksort($name_id); print_r($name_id);
        echo "\n count of names: " . count($names) . "\n";
        echo "\n count of names: " . count($name_id) . "\n";
        echo "\n [total recs from taxon.tab: $i] \n";

        // list taxa with parent that has no info
        $no_parent_info = 0;
        foreach(new FileIterator($file) as $line_number => $line)
        {
            if($line)
            {
                $i++;
                $line = trim($line);
                $cols = explode("\t", $line);
                $parent = (string) trim($cols[3]);
                if(!isset($id_name[$parent]))
                {
                    $no_parent_info++;
                    echo "\n no parent info";
                    print_r($cols);
                }
            }
        }
        echo "\n taxa with parent that has no info: " . $no_parent_info . "\n";
    }

}
?>