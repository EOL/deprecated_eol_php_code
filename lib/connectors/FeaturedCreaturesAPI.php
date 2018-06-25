<?php
namespace php_active_record;
// connector: [648] outlinks, [649] articles
class FeaturedCreaturesAPI
{
    function __construct($folder)
    {
        $this->domain = "http://entnemdept.ufl.edu/creatures/";
        $this->taxa_list_url = $this->domain . "main/search_scientific.htm";
        $this->taxa = array();
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->resource_reference_ids = array();
        $this->do_ids = array();
        $this->SPM = 'http://rs.tdwg.org/ontology/voc/SPMInfoItems';
        $this->EOL = 'http://www.eol.org/voc/table_of_contents';
        $this->text_count = 0;
    }

    function get_all_taxa($articles = true)
    {
        if($records = self::parse_html())
        {
            if($articles) self::initialize_subjects();
            print_r($records);
            echo "\n count: " . count($records);
            $i = 0; $total = count($records);
            foreach($records as $rec)
            {
                $i++;
                echo "\n $i of $total: " . $rec["sciname"];
                if($articles) self::prepare_articles($rec);
                else self::prepare_outlinks($rec);
                // if($i == 5) break; // debug
            }
            $this->create_archive();
        }
        echo "\n\n total texts: " . $this->text_count . "\n";
    }

    private function prepare_outlinks($rec)
    {
        $title = "Featured Creatures ";
        if($rec["vernacular"]) $title .= "- " . $rec["vernacular"] . " ";
        $title .= "- " . $rec["sciname"];
        $homepage = "http://entnemdept.ifas.ufl.edu/creatures/";
        $description = "<a href='" . $rec["url"] . "'>$rec[url]</a>" . "<br><br>Founded in 1996 by Thomas Fasulo, Featured Creatures provides in-depth profiles of insects, nematodes, arachnids and other organisms.<br><br>The Featured Creatures site is a cooperative venture of the University of Florida's Entomology and Nematology Department and the Florida Department of Agriculture and Consumer Services' Division of Plant Industry.<br><br>Visit Featured Creatures at <a href='$homepage'>$homepage</a>";
        $identifier = (string) $rec["taxon_id"] . "_outlink";
        if(in_array($identifier, $this->do_ids)) continue;
        else $this->do_ids[] = $identifier;
        $mr = new \eol_schema\MediaResource();
        $mr->taxonID        = (string) $rec["taxon_id"];
        $mr->identifier     = $identifier;
        $mr->type           = "http://purl.org/dc/dcmitype/Text";
        $mr->language       = 'en';
        $mr->format         = "text/html";
        $mr->furtherInformationURL = (string) $rec['url'];
        $mr->CVterm         = $this->EOL . "#EducationResources";
        $mr->title          = (string) $title;
        $mr->UsageTerms     = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
        $mr->description    = (string) $description;
        $this->archive_builder->write_object_to_file($mr);
        $this->create_instances_from_taxon_object($rec, array());
    }

    private function get_texts($rec, $html, $agent_ids)
    {
        $descriptions = array();
        $match = false;
        /* 2 possible start hyperlinks (topic) */
        if(preg_match("/<a href=\"#intro\">(.*?)<\/h3>/ims", $html, $arr) || preg_match("/<a href=#intro>(.*?)<\/h3>/ims", $html, $arr))
        {
            $match = $arr[1];
            $term = "intro";
        }
        elseif(preg_match("/<a href=\"#dist\">(.*?)<\/h3>/ims", $html, $arr) || preg_match("/<a href=\#dist\>(.*?)<\/h3>/ims", $html, $arr))
        {
            $match = $arr[1];
            $term = "dist";
        }
        else echo "\n alert: investigate 30: -- $rec[url]\n";
        if($match)
        {
            $string = '<a href="#' . $term . '">' . $match;
            // echo "\n" . $string . "\n";
            $items = explode("-", $string);
            $items = array_filter(array_map('trim', $items)); // will trim all values of the array
            print_r($items);
            
            // remove language links
            $i = 0;
            foreach($items as $item)
            {
                if(is_numeric(stripos($item, "Versi&oacute;n en Espa&ntilde;ol"))) $items[$i] = NULL;
                elseif(is_numeric(stripos($item, "Version en Espa&ntilde;ol"))) $items[$i] = NULL;
                elseif(is_numeric(stripos($item, "en Espa&ntilde;ol"))) $items[$i] = NULL;
                elseif(is_numeric(stripos($item, 'href="Mahogany_borer'))) $items[$i] = NULL;
                elseif(is_numeric(stripos($item, 'href="mahogany_webworm'))) $items[$i] = NULL;
                elseif(is_numeric(stripos($item, 'Traduction Fran&ccedil;aise'))) $items[$i] = NULL;
                $i++;
            }
            $items = array_values(array_filter($items));

            // strip tags
            $i = 0;
            foreach($items as $item)
            {
                $items[$i] = strip_tags($item, "<a>");
                $i++;
            }
            $items = array_values(array_filter($items));
            print_r($items);
            
            // manual adjustment
            $items = self::topic_order_adjustment($items, $rec["url"]);

            $connections = array();
            foreach($items as $item)
            {
                if(preg_match("/<a href=\"#(.*?)\"/ims", $item, $arr) || preg_match("/<a href=#(.*?)>/ims", $item, $arr)) $name = $arr[1];
                else echo "\n alert: investigate 02: [$item] -- $rec[url]\n";
                if(preg_match("/>(.*?)</ims", $item, $arr)) $title = $arr[1];
                else echo "\n alert: investigate 03: [$item] -- $rec[url]\n";
                $connections[] = array("name" => $name, "title" => $title);
            }
            echo "\n connections:\n";
            $i = 0;
            $count = count($connections);
            foreach($connections as $conn)
            {
                $name = $conn["name"];
                if($i+1 == $count) $href2 = "</ul>";
                else 
                {
                    $name2 = $connections[$i+1]["name"];
                    $href2 = '<a name="' . $name2 . '"';
                    $href2_noquote = '<a name=' . $name2 . '';
                }
                $href1 = '<a name="' . $name . '"';
                $href1_noquote = '<a name=' . $name . '';
                // echo "\n $href1 -- $href2 \n";
                $href1 = str_ireplace("/", "\/", $href1);
                $href2 = str_ireplace("/", "\/", $href2);
                $href1 = str_ireplace("(", "\(", $href1);
                $href2 = str_ireplace("(", "\(", $href2);
                $href1 = str_ireplace(")", "\)", $href1);
                $href2 = str_ireplace(")", "\)", $href2);
                if(preg_match("/$href1(.*?)$href2/ims", $html, $arr)) $connections[$i]["desc"] = $href1 . $arr[1];
                elseif(preg_match("/$href1_noquote(.*?)$href2_noquote/ims", $html, $arr)) $connections[$i]["desc"] = $href1 . $arr[1];
                elseif(preg_match("/$href1(.*?)$href2_noquote/ims", $html, $arr)) $connections[$i]["desc"] = $href1 . $arr[1];
                elseif(preg_match("/$href1_noquote(.*?)$href2/ims", $html, $arr)) $connections[$i]["desc"] = $href1 . $arr[1];
                else echo "\n alert: investigate 04: [$href1][$href2]\n";
                $i++;
            }
            $this->text_count += count($connections);
            echo "\n article count per taxon: " . count($connections);
            $reference_ids = self::prepare_object_refs($connections);
            foreach($connections as $conn)
            {
                $title = trim($conn["title"]);
                if(is_numeric(stripos($title, "References"))) continue;
                $description = $conn["desc"];
                $description = str_ireplace('<a href="#top" class="backtop">(Back to Top)</a>', '', $description);
                $description = strip_tags($description, "<p><br><i><ul><li><table><tr><td><a><img>");
                $path_parts = pathinfo($rec["url"]);
                $description = str_ireplace('<img src="', '<img src="' . $path_parts["dirname"] . '/', $description);
                $description = str_ireplace('<a href="../../', '<a href="http://entnemdept.ufl.edu/creatures/', $description);
                if(!$subject = @$this->subject[$title]) 
                {
                    if(!$subject = self::other_subject_assignment($title))
                    {
                        if(in_array($rec["url"], array("http://entnemdept.ufl.edu/creatures/misc/gastro/snail_eating_snails.htm")))
                        {
                            if($title == Functions::canonical_form($rec["sciname"]))
                            {
                                echo "\n [$title] EXACT taxon for the page \n";
                                $subject = $this->SPM . "#Morphology"; // hasn't divided the diff topics yet
                            }
                            else
                            {
                                echo "\n [$title] not exact taxon for the page \n";
                                echo "\n undefined subject 01: [$title][$description]\n";
                                continue;
                            }
                        }
                        elseif(in_array($rec["url"], array("http://entnemdept.ufl.edu/creatures/misc/jumping_spiders.htm")))
                        {
                            if($title == Functions::canonical_form($rec["sciname"]))
                            {
                                echo "\n [$title] EXACT taxon for the page \n";
                                $subject = $this->SPM . "#Description";
                                if(is_numeric(stripos($description, "Synonym"))) $subject = $this->EOL . "#Taxonomy";
                            }
                            else
                            {
                                echo "\n [$title] not exact taxon for the page \n";
                                echo "\n undefined subject 02: [$title][$description]\n";
                                continue;
                            }
                        }
                        else
                        {
                            echo " --- will continue...[$title][$subject]"; 
                            continue;
                        }
                    }
                    echo "\n final subject: [$title][$subject]\n";
                }

                // remove row before <p>
                $pos = stripos($description, "<p>");
                if(is_numeric($pos) && $pos < 100) $description = trim(substr($description, $pos+3, strlen($description)));

                // echo "\n $title: [$description] \n";

                $identifier = (string) $rec["taxon_id"] . "_" . str_replace(" ", "_", $title);
                if(in_array($identifier, $this->do_ids) || !$description) continue;
                else $this->do_ids[] = $identifier;

                $mr = new \eol_schema\MediaResource();
                if($reference_ids)  $mr->referenceID = implode("; ", $reference_ids);
                if($agent_ids)      $mr->agentID = implode("; ", $agent_ids);
                $mr->taxonID        = (string) $rec["taxon_id"];
                $mr->identifier     = $identifier;
                $mr->type           = "http://purl.org/dc/dcmitype/Text";
                $mr->language       = 'en';
                $mr->format         = "text/html";
                $mr->furtherInformationURL = (string) $rec['url'];
                $mr->CVterm         = (string) $subject;
                $mr->Owner          = "";
                $mr->title          = (string) $title;
                $mr->UsageTerms     = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
                $mr->description    = (string) $description;
                $this->archive_builder->write_object_to_file($mr);
            }
        }
        else echo "\n alert: investigate 01: $rec[url]\n";
    }
    
    private function topic_order_adjustment($items, $url)
    {
        if($url == "http://entnemdept.ufl.edu/creatures/fruit/tropical/caribbean_fruit_fly.htm") // inter-change values
        {
            $items[6] = '<a href="#dam">Damage</a>';
            $items[7] = '<a href="#management">Management</a>';
        }
        elseif($url == "http://entnemdept.ufl.edu/creatures/fruit/tropical/queensland_fruit_fly.htm") // inter-change values
        {
            $items[3] = '<a href="#life">Life History</a>';
            $items[4] = '<a href="#ident">Description</a>';
        }
        elseif($url == "http://entnemdept.ufl.edu/creatures/nematode/red_ring_nematode.htm") // inter-change values
        {
            $items[3] = '<a href="#symptoms">Symptoms and Effects</a>';
            $items[4] = '<a href="#life">Life Cycle and Biology</a>';
        }
        elseif($url == "http://entnemdept.ufl.edu/creatures/misc/tiger/tbeetle3.htm") // delete an item
        {
            $items[4] = NULL;
            $items = array_values(array_filter($items));
        }
        elseif($url == "http://entnemdept.ufl.edu/creatures/field/lesser_cornstalk_borer.htm") // inter-change values
        {
            $items[3] = '<a href="#weather">Weather</a>';
            $items[4] = '<a href="#host">Host Plants</a>';
        }
        elseif($url == "http://entnemdept.ufl.edu/creatures/beneficial/bca_parasitoid.htm") // inter-change values
        {
            $items[0] = '<a href="#intro">Introduction</a>';
            $items[1] = '<a href="#classical">Classical Biological Control</a>';
            $items[2] = '<a href="#bio">Aphidiidae Biology</a>';
            $items[3] = '<a href="#tax">Taxonomic Description</a>';
            $items[4] = '<a href="#genetic">Genetic Variability</a>';
            $items[5] = '<a href="#guam">Guam Biotype</a>';
            $items[6] = '<a href="#indian">Indian Biotype</a>';
            $items[7] = '<a href="#host">Host Range</a>';
            $items[8] = '<a href="#potential">Effects on Rare Aphids</a>';
            $items[9] = '<a href="#expecteda">Geographic Range</a>';
            $items[10] = '<a href="#expectede">Environmental Effects</a>';
            $items[11] = '<a href="#desc">Description and Identification</a>';
            $items[12] = '<a href="#releases">Releases in Florida</a>';
            $items[13] = '<a href="#pesticide">Pesticide Selectivity</a>';
            $items[14] = '<a href="#ref">Selected References</a>';
        }
        elseif($url == "http://entnemdept.ufl.edu/creatures/urban/roaches/oriental_cockroach.htm") // inter-change values
        {
            $items[0] = '<a href="#intro">Introduction</a>';
            $items[1] = '<a href="#desc">Description</a>';
            $items[2] = '<a href="#life">Life Cycle</a>';
            $items[3] = '<a href="#detection">Detection</a>';
            $items[4] = '<a href="#habits">Habits and Habitats</a>';
            $items[5] = '<a href="#diet">Diet</a>';
            $items[6] = '<a href="#management">Management</a>';
            $items[7] = '<a href="#ref">Selected References</a>';
        }
        elseif($url == "http://entnemdept.ufl.edu/creatures/bfly/bfly2/cloudless_sulphur.htm") // inter-change values
        {
            $items[5] = '<a href=#hosts>Hosts</a>';
            $items[6] = '<a href=#eco>Economic Importance</a>';
        }
        elseif($url == "http://entnemdept.ufl.edu/creatures/fruit/olive_shootworm.htm")
        {
            $items = array();
            $items[0] = '<a href="#intro">Introduction</a>';
            $items[1] = '<a href="#dist">Distribution</a>';
            $items[2] = '<a href="#diag">Diagnosis</a>';
            $items[3] = '<a href="#key">Key</a>';
            $items[4] = '<a href="#host">Hosts</a>';
            $items[5] = '<a href="#behav">Behavior</a>';
            $items[6] = '<a href="#man">Management</a>';
            $items[7] = '<a href="#ref">Selected References</a>';
        }
        return $items;
    }

    private function prepare_object_refs($connections)
    {
        $reference_ids = array();
        $string = "";
        foreach($connections as $conn)
        {
            if($conn["title"] == "Selected References") $string = $conn["desc"];
        }
        if(preg_match_all("/<li>(.*?)<\/li>/ims", $string, $arr)) 
        {
            $refs = $arr[1];
            foreach($refs as $ref)
            {
                $ref = (string) trim($ref);
                if(!$ref) continue;
                $r = new \eol_schema\Reference();
                $r->full_reference = $ref;
                $r->identifier = md5($ref);
                $reference_ids[] = $r->identifier;
                if(!in_array($r->identifier, $this->resource_reference_ids)) 
                {
                   $this->resource_reference_ids[] = $r->identifier;
                   $this->archive_builder->write_object_to_file($r);
                }
            }
            
        }
        return $reference_ids;
    }

    private function prepare_articles($rec)
    {
        $reference_ids = array();
        $ref_ids = array();
        $agent_ids = array();
        echo "\n\n" . " - " . $rec['sciname'] . " - " . $rec['vernacular'] . " - " . $rec['url'] . "\n";
        if($html = Functions::get_remote_file($rec['url'], array('download_wait_time' => 3000000, 'timeout' => 240, 'download_attempts' => 5)))
        {
            $html = str_ireplace(array("\n", "\r", "\t", "\o", "    "), "", $html);
            // manual adjustment
            $html = str_ireplace("Descripton", "Description", $html);
            $html = str_ireplace("Mangement", "Management", $html);
            $html = str_ireplace("Signifance", "Significance", $html);
            $html = str_ireplace("Distibution", "Distribution", $html);
            $html = str_ireplace("Descriptrion", "Description", $html);
            $html = str_ireplace("Identifiction", "Identification", $html);
            $html = str_ireplace("Indentification", "Identification", $html);
            $html = str_ireplace("Synonomy", "Synonymy", $html);
            $html = str_ireplace("Plexippus  paykulli", "Plexippus paykulli", $html);
            if($rec["url"] == "http://entnemdept.ufl.edu/creatures/misc/jumping_spiders.htm") 
            {
                $html = str_ireplace('<a href="#desc">Description</a>', '<a href="#desc">Menemerus bivittatus</a> <a href="#desc">Description</a>', $html);
                $html = str_ireplace('<a href="#desc2">Description</a>', '<a href="#desc2">Plexippus paykulli</a> <a href="#desc2">Description</a>', $html);
            }
            $html = str_ireplace('<a href =', '<a href=', $html);
            $html = str_ireplace("<a  href", "<a href", $html); // http://entnemdept.ufl.edu/creatures/fruit/tropical/Anastrepha_grandis.htm
            $html = str_ireplace("<A name=key>", '<a name="key" id="key">', $html); // http://entnemdept.ufl.edu/creatures/trees/beetles/click_beetle.htm
            $html = str_ireplace("<a name=ident>", '<a name="ident" id="ident">', $html); // http://entnemdept.ufl.edu/creatures/beneficial/a_grandis.htm
            $html = str_ireplace("<A name=genetic>", '<a name="genetic">', $html);
            $html = str_ireplace('<a name= "', '<a name="', $html);
            $html = str_ireplace("Surveillance  and Management of", "Surveillance and Management of", $html);
            $html = str_ireplace("<ahref=", "<a href=", $html);
            $html = str_ireplace("Fly-Free Zones", "Fly Free Zones", $html); // http://entnemdept.ufl.edu/creatures/fruit/tropical/caribbean_fruit_fly.htm
            if($rec["url"] == "http://entnemdept.ufl.edu/creatures/misc/pleurodontidae_snails.htm") $html = str_ireplace('<a href="#intr">', '<a href="#intro">', $html);
            if($rec["url"] == "http://entnemdept.ufl.edu/creatures/misc/wasps/cotesia_marginiventris.htm") $html = str_ireplace('<h3>Distribution</h3>', '<a name="dist" id="dist"><h3>Distribution</h3>', $html);
            if(in_array($rec["url"], array("http://entnemdept.ufl.edu/creatures/veg/pickleworm.htm", "http://entnemdept.ufl.edu/creatures/veg/corn_earworm.htm", "http://entnemdept.ufl.edu/creatures/field/tobacco_budworm.htm", "http://entnemdept.ufl.edu/creatures/veg/leaf/a_serpentine_leafminer.htm"))) $html = str_ireplace('<a href="#dist">Distribution</a>', '<a href="#intro">Introduction</a>', $html);
            self::get_texts($rec, $html, $agent_ids);
        }
        $this->create_instances_from_taxon_object($rec, $reference_ids);
    }

    private function parse_html()
    {
        if($html = Functions::get_remote_file($this->taxa_list_url, array('timeout' => 1200, 'download_attempts' => 5)))
        {
            // manual adjustment
            $html = str_ireplace('<a href="../aquatic/Culiseta_melanura.htm">, black-tailed mosquito </a>', ', black-tailed mosquito', $html);
            $html = str_ireplace('Odonata - dragonflies and damselflies', 'Odonata, dragonflies and damselflies', $html);
            $html = str_ireplace('Aleurocanthus</i> Ashby', 'Aleurocanthus</i>, Ashby', $html);
            $html = substr($html, stripos($html, "<h1>Search by Scientific Name"), strlen($html));
            if(preg_match_all("/<a href=\"..\/(.*?)<br/ims", $html, $matches))
            {
                $records = $matches[1];
                print_r($records);
                $taxa = array();
                foreach($records as $rec)
                {
                    $rec = str_ireplace(' target="_blank"', "", $rec);
                    $parts = explode('">', $rec);
                    $url = trim($parts[0]);
                    $names = trim(strip_tags($parts[1]));
                    $names = str_ireplace(array("\n", "\t"), "", $names);
                    $names = preg_replace("/\([^)]+\)/", "", $names); //remove parenthesis
                    $names_arr = explode(",", $names);
                    $names_arr = array_map('trim', $names_arr);
                    $comma_count = substr_count($names, ",");
                    if($comma_count == 1)
                    {
                        $sciname = $names_arr[0];
                        $vernacular = $names_arr[1];
                    }
                    elseif($comma_count == 2)
                    {
                        $sciname = $names_arr[1] . " " . trim($names_arr[0]);
                        $vernacular = $names_arr[2];
                    }
                    elseif($comma_count == 3)
                    {
                        $sciname = $names_arr[1] . " " . trim($names_arr[0]) . " " . trim($names_arr[2]);
                        $vernacular = $names_arr[3];
                    }
                    elseif($comma_count == 4)
                    {
                        $sciname = $names_arr[1] . " " . trim($names_arr[0]) . " " . trim($names_arr[2]);
                        $vernacular = $names_arr[3];
                    }
                    elseif($comma_count == 0)
                    {
                        echo "\n zero comma count: [$rec]";
                        $sciname = $names_arr[0];
                        $vernacular = "";
                    }
                    else
                    {
                        echo "\n\n alert: [$comma_count] [$rec]";
                        return array();
                    }
                    $sciname = trim($sciname);
                    $vernacular = trim($vernacular);
                    
                    // manual adjustment
                    if($sciname == "Leptoglossus zonatus") $vernacular = "western leaffooted bug";
                    if($sciname == "spp. Tritoma") $sciname = "Tritoma spp.";
                    echo "\n sciname: [$sciname] [$vernacular]";
                    if($sciname) $taxa[$sciname] = array("taxon_id" => str_ireplace(" ", "_", $sciname), "sciname" => $sciname, "vernacular" => $vernacular, "url" => $this->domain . $url);
                }
                return $taxa;
            }
        }
        else
        {
            echo ("\n Problem with the remote file: $this->taxa_list_url");
            return false;
        }
    }

    function create_instances_from_taxon_object($rec, $reference_ids)
    {
        $taxon = new \eol_schema\Taxon();
        if($reference_ids) $taxon->referenceID = implode("; ", $reference_ids);
        $taxon->taxonID = $rec["taxon_id"];
        $taxonRemarks = "";
        $taxon->scientificName              = (string) $rec["sciname"];
        $taxon->vernacularName              = (string) $rec['vernacular'];
        $taxon->furtherInformationURL       = (string) $rec['url'];
        $this->taxa[$rec["taxon_id"]] = $taxon;
    }

    function create_archive()
    {
        foreach($this->taxa as $t)
        {
            $this->archive_builder->write_object_to_file($t);
        }
        $this->archive_builder->finalize(true);
    }

    private function initialize_subjects()
    {
        $this->subject['Introduction'] = $this->SPM . "#TaxonBiology";
        $this->subject['Introduction and Distribution'] = $this->SPM . "#TaxonBiology";
        $this->subject['Distribution'] = $this->SPM . "#Distribution";
        $this->subject['Seasonal Distribution'] = $this->SPM . "#Distribution";
        $this->subject['Life Cycle and Description'] = $this->SPM . "#Morphology";
        $this->subject['Host Plants'] = $this->SPM . "#Associations";
        $this->subject['Damage'] = $this->SPM . "#RiskStatement";
        $this->subject['Natural Enemies'] = $this->SPM . "#Associations";
        $this->subject['Management'] = $this->SPM . "#Management";
        $this->subject['Synonymy'] = $this->EOL . "#Taxonomy";
        $this->subject['Synonyms'] = $this->EOL . "#Taxonomy";
        $this->subject['Taxonomy'] = $this->EOL . "#Taxonomy";
        $this->subject['Description'] = $this->SPM . "#Morphology";
        $this->subject['Life Cycle'] = $this->SPM . "#LifeCycle";
        $this->subject['Hosts'] = $this->SPM . "#Associations";
        $this->subject['Host'] = $this->SPM . "#Associations";
        $this->subject['Biological Control'] = $this->SPM . "#Reproduction";
        $this->subject['Fly Free Zones'] = $this->SPM . "#Management";
        $this->subject['Host Range'] = $this->SPM . "#Associations";
        $this->subject['Key to species of Florida Belostomatidae'] = $this->SPM . "#Key";
        $this->subject['Economic Importance'] = $this->SPM . "#Uses";
        $this->subject['Life History'] = $this->SPM . "#Behaviour";
        $this->subject['Life Cycle and Biology'] = $this->SPM . "#Biology";
        $this->subject['Nest Architecture'] = $this->SPM . "#Morphology";
        $this->subject['Forage Plants and Habits'] = $this->SPM . "#Behaviour";
        $this->subject['Damage and Economic Importance'] = $this->SPM . "#Uses";
        $this->subject['Defenses'] = $this->SPM . "#Associations";
        $this->subject['Diagnosis'] = $this->SPM . "#Morphology";
        $this->subject['Habits and Habitat'] = $this->SPM . "#Habitat";
        $this->subject['Biology and Ecology'] = $this->SPM . "#Ecology";
        $this->subject['Detection'] = $this->SPM . "#Management";
        $this->subject['Survey and Detection'] = $this->SPM . "#Management";
        $this->subject['Effectiveness'] = $this->SPM . "#Uses";
        $this->subject['Pest Significance'] = $this->SPM . "#Uses";
        $this->subject['Medical Significance'] = $this->SPM . "#Uses";
        $this->subject['Commercial Availability and Use'] = $this->SPM . "#Uses";
        $this->subject['Biology'] = $this->SPM . "#Biology";
        $this->subject['Importance'] = $this->SPM . "#Uses";
        $this->subject['Medical Importance'] = $this->SPM . "#Uses";
        $this->subject['Identification Key'] = $this->SPM . "#Key";
        $this->subject['Descripton and Life Cycle'] = $this->SPM . "#Description";
        $this->subject['Identification'] = $this->SPM . "#Morphology";
        $this->subject['Habitat'] = $this->SPM . "#Habitat";
        $this->subject['Song'] = $this->SPM . "#Associations";
        $this->subject['Rearing'] = $this->SPM . "#Associations";
        $this->subject['Surveillance and Management of Aedes albopictus'] = $this->SPM . "#Management";
        $this->subject['Collecting'] = $this->SPM . "#Management";
        $this->subject['Management'] = $this->SPM . "#Management";
        // http://entnemdept.ufl.edu/creatures/beneficial/c_v_aequoris.htm
        $this->subject['Systematics'] = $this->EOL . "#Taxonomy";
        $this->subject['Description and Identification'] = $this->SPM . "#Description";
        $this->subject['Bionomics'] = $this->SPM . "#Uses";
        $this->subject['Natural Enemies'] = $this->SPM . "#Associations";
        $this->subject['Feeding'] = $this->SPM . "#TrophicStrategy";
        $this->subject['Vector Status'] = $this->SPM . "#TrophicStrategy";
        $this->subject['Economic Injury Level'] = $this->SPM . "#Uses";
        $this->subject['Life Cycles'] = $this->SPM . "#LifeCycle";
        $this->subject['Sound Production'] = $this->SPM . "#Behaviour";
        $this->subject['Behavior'] = $this->SPM . "#Behaviour";
        $this->subject['Description and Diagnosis'] = $this->SPM . "#Description";
        $this->subject['Pest Status'] = $this->SPM . "#Associations";
        $this->subject['Foraging and Feeding'] = $this->SPM . "#Behaviour";
        $this->subject['Nest Sites'] = $this->SPM . "#Behaviour";
        $this->subject['Disease Transmission'] = $this->SPM . "#Associations";
        $this->subject['Biology and Life Cycle'] = $this->SPM . "#Biology";
        $this->subject['Distribution and Hosts'] = $this->SPM . "#Distribution";
        $this->subject['Release and Dispersal'] = $this->SPM . "#Management";
        $this->subject['Bionomics and Host Parasite Relationships'] = $this->SPM . "#Associations";
        $this->subject['Bacterial Associates'] = $this->SPM . "#Associations";
        $this->subject['Biocontrol Capability'] = $this->SPM . "#Management";
        $this->subject['Searching Behavior'] = $this->SPM . "#Behaviour";
        $this->subject['Production and Formulation'] = $this->SPM . "#Reproduction";
        $this->subject['Handling and Effectiveness'] = $this->SPM . "#Management";
        $this->subject['Application Considerations'] = $this->SPM . "#Management";
        $this->subject['Classification'] = $this->EOL . "#Taxonomy";
        $this->subject['Appearance'] = $this->SPM . "#Description";
        $this->subject['Habitat and Food'] = $this->SPM . "#Habitat";
        $this->subject['Structure and Function'] = $this->SPM . "#Biology";
        $this->subject['Causes of Mortality'] = $this->SPM . "#LifeExpectancy";
        $this->subject['Importance to Humans'] = $this->SPM . "#Uses";
        $this->subject['Key to Species in Florida'] = $this->SPM . "#Key";
        $this->subject['Sphecius hogardii'] = $this->SPM . "#TaxonBiology";
        $this->subject['Sphecius speciosus'] = $this->SPM . "#TaxonBiology";
        $this->subject['Symptoms'] = $this->SPM . "#LifeExpectancy";
        $this->subject['Chemical Ecology'] = $this->SPM . "#Ecology";
        $this->subject['Caracolus marginella'] = $this->SPM . "#TaxonBiology";
        $this->subject['Zachrysia provisoria'] = $this->SPM . "#TaxonBiology";
        $this->subject['Zachrysia trinitaria'] = $this->SPM . "#TaxonBiology";
        $this->subject['Dilophus sayi'] = $this->SPM . "#TaxonBiology";
        $this->subject['Conservation Status'] = $this->SPM . "#ConservationStatus";
        $this->subject['Monitoring and Diagnosis'] = $this->SPM . "#Management";
        $this->subject['The Lac Scale Family'] = $this->SPM . "#GeneralDescription";
    }

    private function other_subject_assignment($title)
    {
        if(is_numeric(stripos($title, "Key to"))) $subject = $this->SPM . "#Key";
        elseif(is_numeric(stripos($title, "Morphology"))) $subject = $this->SPM . "#Morphology";
        elseif(is_numeric(stripos($title, "Polymorphism"))) $subject = $this->SPM . "#Morphology";
        elseif(is_numeric(stripos($title, "State of Knowledge"))) $subject = $this->SPM . "#GeneralDescription";
        elseif(is_numeric(stripos($title, "Parthenogenesis"))) $subject = $this->SPM . "#Reproduction";
        elseif(is_numeric(stripos($title, "Distribution"))) $subject = $this->SPM . "#Distribution";
        elseif(is_numeric(stripos($title, "Description"))) $subject = $this->SPM . "#Description";
        elseif(is_numeric(stripos($title, "Association"))) $subject = $this->SPM . "#Associations";
        elseif(is_numeric(stripos($title, "Importance"))) $subject = $this->SPM . "#Uses";
        elseif(is_numeric(stripos($title, "Significance"))) $subject = $this->SPM . "#Uses";
        elseif(is_numeric(stripos($title, "Host"))) $subject = $this->SPM . "#Associations";
        elseif(is_numeric(stripos($title, "Survey"))) $subject = $this->SPM . "#Management";
        elseif(is_numeric(stripos($title, "Economic"))) $subject = $this->SPM . "#Uses";
        elseif(is_numeric(stripos($title, " use "))) $subject = $this->SPM . "#Uses";
        elseif(is_numeric(stripos($title, "Biology"))) $subject = $this->SPM . "#Biology";
        elseif(is_numeric(stripos($title, "Life History"))) $subject = $this->SPM . "#Ecology";
        elseif(is_numeric(stripos($title, "History"))) $subject = $this->SPM . "#Ecology";
        elseif(is_numeric(stripos($title, "Collecting"))) $subject = $this->SPM . "#Management";
        elseif(is_numeric(stripos($title, "Nest"))) $subject = $this->SPM . "#Behaviour";
        elseif(is_numeric(stripos($title, "Synonym"))) $subject = $this->EOL . "#Taxonomy";
        elseif(is_numeric(stripos($title, "Damage"))) $subject = $this->SPM . "#RiskStatement";
        elseif(is_numeric(stripos($title, "Weather"))) $subject = $this->SPM . "#Trends";
        elseif(is_numeric(stripos($title, "Identification"))) $subject = $this->SPM . "#Morphology";
        elseif(is_numeric(stripos($title, "Quarantine"))) $subject = $this->SPM . "#Conservation";
        elseif(is_numeric(stripos($title, "Biological Control"))) $subject = $this->SPM . "#Conservation";
        elseif(is_numeric(stripos($title, "Parasite"))) $subject = $this->SPM . "#Associations";
        elseif(is_numeric(stripos($title, "Pest"))) $subject = $this->SPM . "#Associations";
        elseif(is_numeric(stripos($title, "Diet"))) $subject = $this->SPM . "#TrophicStrategy";
        elseif(is_numeric(stripos($title, "Habit"))) $subject = $this->SPM . "#Behaviour";
        elseif(is_numeric(stripos($title, "Introduction"))) $subject = $this->SPM . "#Conservation";
        elseif(is_numeric(stripos($title, "Nectar"))) $subject = $this->SPM . "#TrophicStrategy";
        elseif(is_numeric(stripos($title, "Life Cycle"))) $subject = $this->SPM . "#LifeCycle";
        elseif(is_numeric(stripos($title, "target Species"))) $subject = $this->SPM . "#Associations";
        elseif(is_numeric(stripos($title, "Defensive Secretion"))) $subject = $this->SPM . "#Biology";
        elseif(is_numeric(stripos($title, "Management"))) $subject = $this->SPM . "#Management";
        elseif(is_numeric(stripos($title, "Surveillance"))) $subject = $this->SPM . "#Management";
        elseif(is_numeric(stripos($title, "Enemies"))) $subject = $this->SPM . "#Associations";
        elseif(is_numeric(stripos($title, "Reproduction"))) $subject = $this->SPM . "#Reproduction";
        elseif(is_numeric(stripos($title, "Behavior"))) $subject = $this->SPM . "#Behaviour";
        elseif(is_numeric(stripos($title, " differ"))) $subject = $this->SPM . "#Morphology";
        elseif(is_numeric(stripos($title, "Risk"))) $subject = $this->SPM . "#RiskStatement";
        elseif(is_numeric(stripos($title, "Distribution"))) $subject = $this->SPM . "#Distribution";
        elseif(is_numeric(stripos($title, "Control"))) $subject = $this->SPM . "#Management";
        elseif(is_numeric(stripos($title, "Attractant"))) $subject = $this->SPM . "#Associations";
        elseif(is_numeric(stripos($title, "Action Threshold"))) $subject = $this->SPM . "#Management";
        elseif(is_numeric(stripos($title, "Similar Species"))) $subject = $this->SPM . "#LookAlikes";
        elseif(is_numeric(stripos($title, "Symptoms and Effect"))) $subject = $this->SPM . "#RiskStatement";
        elseif(is_numeric(stripos($title, "Infection"))) $subject = $this->SPM . "#RiskStatement";
        elseif(is_numeric(stripos($title, "Ecology"))) $subject = $this->SPM . "#Ecology";
        elseif(is_numeric(stripos($title, "List of Genera"))) $subject = $this->EOL . "#Taxonomy";
        elseif(is_numeric(stripos($title, "Epidemiology"))) $subject = $this->SPM . "#Biology";
        elseif(is_numeric(stripos($title, "Identifying Char"))) $subject = $this->SPM . "#Morphology";
        elseif(is_numeric(stripos($title, "Detection Note"))) $subject = $this->SPM . "#TrophicStrategy";
        elseif(is_numeric(stripos($title, "Relationship to Human"))) $subject = $this->SPM . "#Associations";
        elseif(is_numeric(stripos($title, "Resistance"))) $subject = $this->SPM . "#RiskStatement";
        elseif(is_numeric(stripos($title, "Field Observation"))) $subject = $this->SPM . "#Behaviour";
        elseif(is_numeric(stripos($title, "Outbreak"))) $subject = $this->SPM . "#Trends";
        elseif(is_numeric(stripos($title, "Seasonality"))) $subject = $this->SPM . "#Cyclicity";
        elseif(is_numeric(stripos($title, "Removal"))) $subject = $this->SPM . "#Management";
        elseif(is_numeric(stripos($title, "Parasitoids"))) $subject = $this->SPM . "#Associations";
        elseif(is_numeric(stripos($title, "Conservation"))) $subject = $this->SPM . "#Conservation";
        elseif(is_numeric(stripos($title, "Sound"))) $subject = $this->SPM . "#Physiology";
        elseif(is_numeric(stripos($title, "Dispersal"))) $subject = $this->SPM . "#Dispersal";
        elseif(is_numeric(stripos($title, "Monitoring"))) $subject = $this->SPM . "#Management";
        elseif(is_numeric(stripos($title, "Field Char"))) $subject = $this->SPM . "#Morphology";
        elseif(is_numeric(stripos($title, "Sampling"))) $subject = $this->SPM . "#Management";
        elseif(is_numeric(stripos($title, "Insecticides"))) $subject = $this->SPM . "#Management";
        elseif(is_numeric(stripos($title, "Infestation"))) $subject = $this->SPM . "#Management";
        elseif(is_numeric(stripos($title, "Migration"))) $subject = $this->SPM . "#Migration";
        elseif(is_numeric(stripos($title, "Larvae"))) $subject = $this->SPM . "#Reproduction";
        elseif(is_numeric(stripos($title, "Effects on Other"))) $subject = $this->SPM . "#Associations";
        elseif(is_numeric(stripos($title, "Environmental Effects"))) $subject = $this->SPM . "#Uses";
        elseif(is_numeric(stripos($title, "Range"))) $subject = $this->SPM . "#Distribution";
        elseif(is_numeric(stripos($title, "Species Found in"))) $subject = $this->SPM . "#Distribution";
        elseif(is_numeric(stripos($title, "Endosymbiont"))) $subject = $this->SPM . "#Associations";
        elseif(is_numeric(stripos($title, "Related Species"))) $subject = $this->SPM . "#GeneralDescription";
        elseif(is_numeric(stripos($title, "Defensive Char"))) $subject = $this->SPM . "#Behaviour";
        elseif(is_numeric(stripos($title, "Similar"))) $subject = $this->SPM . "#LookAlikes";
        elseif(is_numeric(stripos($title, "Mimicry"))) $subject = $this->SPM . "#LookAlikes";
        elseif(is_numeric(stripos($title, "Suspected Case"))) $subject = $this->SPM . "#Threats";
        elseif(is_numeric(stripos($title, "Medical"))) $subject = $this->SPM . "#Threats";
        elseif(is_numeric(stripos($title, "Bite"))) $subject = $this->SPM . "#Threats";
        elseif(is_numeric(stripos($title, "Releases in"))) $subject = $this->SPM . "#Dispersal";
        elseif(is_numeric(stripos($title, "Effects on"))) $subject = $this->SPM . "#Associations";
        elseif(is_numeric(stripos($title, "Biotype"))) $subject = $this->SPM . "#Physiology";
        elseif(is_numeric(stripos($title, "Genetic"))) $subject = $this->SPM . "#Physiology";
        elseif(is_numeric(stripos($title, "Sex"))) $subject = $this->SPM . "#Reproduction";
        elseif(is_numeric(stripos($title, "Predat"))) $subject = $this->SPM . "#Associations";
        elseif(is_numeric(stripos($title, "Species by Family"))) $subject = $this->SPM . "#GeneralDescription";
        elseif(is_numeric(stripos($title, "Interaction"))) $subject = $this->SPM . "#Associations";
        elseif(is_numeric(stripos($title, "Queen"))) $subject = $this->SPM . "#Behaviour";
        elseif(is_numeric(stripos($title, "Species Found in"))) $subject = $this->SPM . "#GeneralDescription";
        elseif(is_numeric(stripos($title, "Regulatory Act"))) $subject = $this->SPM . "#Legislation";
        elseif(is_numeric(stripos($title, "Detection of"))) $subject = $this->SPM . "#Distribution";
        elseif(is_numeric(stripos($title, "Puncture"))) $subject = $this->SPM . "#Behaviour";
        elseif(is_numeric(stripos($title, "Trapping"))) $subject = $this->SPM . "#Conservation";
        elseif(is_numeric(stripos($title, "Dimorphism"))) $subject = $this->SPM . "#Morphology";
        elseif(is_numeric(stripos($title, "Mating"))) $subject = $this->SPM . "#Reproduction";
        elseif(is_numeric(stripos($title, "Spread"))) $subject = $this->SPM . "#Dispersal";
        elseif(is_numeric(stripos($title, "Key"))) $subject = $this->SPM . "#Key";
        elseif(is_numeric(stripos($title, "Treatment"))) $subject = $this->SPM . "#RiskStatement";
        return $subject;
    }

}
?>