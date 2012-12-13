<?php
namespace php_active_record;

class PlantConservationAPI
{
    const MIDATLANTIC_TOC_URL = "http://www.nps.gov/plants/alien/pubs/midatlantic/toc.htm";
    const MIDATLANTIC_TAXA_PREFIX = "http://www.nps.gov/plants/alien/pubs/midatlantic/";
    const ALIEN_TOC_URL = "http://www.nps.gov/plants/alien/fact.htm";
    const ALIEN_TAXA_PREFIX = "http://www.nps.gov/plants/alien/";
    
    public function __construct($resource_id)
    {
        $this->resource_id = $resource_id;
    }
    public function build_archive()
    {
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . "/$this->resource_id/";
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->taxa = array();
        $this->midatlantic_citation = "Swearingen, J., B. Slattery, K. Reshetiloff, and S. Zwicker. 2010. Plant Invaders of Mid-Atlantic Natural Areas, 4th ed. National Park Service and U.S. Fish and Wildlife Service. Washington, DC. 168pp.";
        $this->get_midatlantic_taxa();
        $this->get_alien_taxa();
        $this->archive_builder->finalize(true);
    }
    
    public function get_midatlantic_taxa()
    {
        $toc_html = self::lookup_with_cache(self::MIDATLANTIC_TOC_URL);
        if(preg_match("/Introduction(.*)Native Alternatives/ims", $toc_html, $arr)) $toc_html = $arr[1];
        if(preg_match_all("/<a href=\"(.*?)\">(.*?)<\/a>/", $toc_html, $matches, PREG_SET_ORDER))
        {
            foreach($matches as $match)
            {
                $path = $match[1];
                if(preg_match("/^control-/", $path)) continue;
                if(preg_match("/^plants-to-watch.htm#/", $path)) continue;
                
                # lots on the same page
                if($path == "plants-to-watch.htm")
                {
                    $this->write_plants_to_watch(self::MIDATLANTIC_TAXA_PREFIX . $path);
                }else
                {
                    $this->write_midatlantic_taxon(self::MIDATLANTIC_TAXA_PREFIX . $path);
                }
            }
        }
    }
    
    public function get_alien_taxa()
    {
        $toc_html = self::lookup_with_cache(self::ALIEN_TOC_URL);
        if(preg_match_all("/<A HREF=\"(fact\/.*?)\">/", $toc_html, $matches, PREG_SET_ORDER))
        {
            foreach($matches as $match)
            {
                $path = $match[1];
                $this->write_alien_taxon(self::ALIEN_TAXA_PREFIX . $path);
            }
        }
    }
    
    public function write_midatlantic_text($title, $text, $subject, $taxon_ids, $identifier, $url)
    {
        $text = str_replace("\r", "", $text);
        $text = str_replace("\n", "", $text);
        
        if(!is_array($taxon_ids)) $taxon_ids = array($taxon_ids);
        $m = new \eol_schema\MediaResource();
        $m->title = $title;
        $m->description = $text;
        $m->identifier = $identifier;
        $m->taxonID = implode(";", $taxon_ids);
        $m->type = 'http://purl.org/dc/dcmitype/Text';
        $m->format = 'text/html';
        $m->furtherInformationURL = $url;
        $m->CVterm = $subject;
        $m->language = 'en';
        $m->UsageTerms = 'http://creativecommons.org/licenses/by-nc-sa/3.0/';
        $m->bibliographicCitation = $this->midatlantic_citation;
        $this->archive_builder->write_object_to_file($m);
    }
    
    public function write_alien_text($title, $text, $subject, $taxon_ids, $identifier, $url)
    {
        $text = str_replace("\r", "", $text);
        $text = str_replace("\n", "", $text);
        
        if(!is_array($taxon_ids)) $taxon_ids = array($taxon_ids);
        $m = new \eol_schema\MediaResource();
        $m->title = $title;
        $m->description = $text;
        $m->identifier = $identifier;
        $m->taxonID = implode(";", $taxon_ids);
        $m->type = 'http://purl.org/dc/dcmitype/Text';
        $m->format = 'text/html';
        $m->furtherInformationURL = $url;
        $m->CVterm = $subject;
        $m->language = 'en';
        $m->UsageTerms = 'http://creativecommons.org/licenses/by-nc-sa/3.0/';
        $this->write_agents();
        $m->agentID = "1;2";
        $this->archive_builder->write_object_to_file($m);
    }
    
    public function write_agents()
    {
        static $written_agents = false;
        if(!$written_agents)
        {
            $a1 = new \eol_schema\Agent();
            $a1->identifier = "1";
            $a1->term_name = "Jill Swearingen";
            $a1->agentRole = "Author";
            $this->archive_builder->write_object_to_file($a1);
            
            $a2 = new \eol_schema\Agent();
            $a2->identifier = "2";
            $a2->term_name = "U.S. National Park Service Weeds Gone Wild website";
            $a2->agentRole = "Editor";
            $this->archive_builder->write_object_to_file($a2);
            $written_agents = true;
        }
    }
    
    public function write_taxon($taxon_id, $scientific_name, $canonical_form, $family, $common_name = "")
    {
        if(isset($this->taxa[$taxon_id])) return $this->taxa[$taxon_id];
        $rank = 'species';
        $count_spaces = substr_count($canonical_form, " ");
        if($count_spaces == 2)
        {
            if(preg_match("/ ssp. /", $scientific_name)) $rank = 'subspecies';
            elseif(preg_match("/ var. /", $scientific_name)) $rank = 'variety';
            else $rank = null;
        }
        
        $t = new \eol_schema\Taxon();
        $t->taxonID = $taxon_id;
        $t->taxonRank = $rank;
        $t->scientificName = $scientific_name;
        $t->kingdom = 'Plantae';
        $t->family = $family;
        $this->archive_builder->write_object_to_file($t);
        
        if($common_name)
        {
            $v = new \eol_schema\VernacularName();
            $v->taxonID = $taxon_id;
            $v->vernacularName = $common_name;
            $v->language = "en";
            $this->archive_builder->write_object_to_file($v);
        }
        $this->taxa[$taxon_id] = $t;
    }
    
    
    public function write_midatlantic_taxon($url)
    {
        echo "\n$url<br/>\n";
        $taxa_page_html = self::lookup_with_cache($url);
        
        // Taxon
        if($url == self::MIDATLANTIC_TAXA_PREFIX."bamboos.htm")
        {
            $taxon_ids = array();
            $this->write_taxon("bambusa", "Bambusa", "Bambusa", "Poaceae");
            $taxon_ids[] = 'bambusa';
            $this->write_taxon("bambusa_vulgaris", "Bambusa vulgaris Schrad. ex J.C. Wendl", "Bambusa vulgaris", "Poaceae", "Common bamboo");
            $taxon_ids[] = 'bambusa_vulgaris';
            $this->write_taxon("phyllostachys_aurea", "Phyllostachys aurea Carr. ex A.& C. Rivière", "Phyllostachys aurea", "Poaceae", "Golden bamboo");
            $taxon_ids[] = 'phyllostachys_aurea';
            $this->write_taxon("pseudosasa_japonica", "Pseudosasa japonica (Sieb. & Zucc. ex Steud.) Makino ex Nakai", "Pseudosasa japonica", "Poaceae", "Arrow bamboo");
            $taxon_ids[] = 'pseudosasa_japonica';
            $this->write_taxon("arundinaria_gigantea", "Arundinaria gigantea (Walter) Muhl.", "Arundinaria gigantea", "Poaceae", "Giant cane");
            $taxon_ids[] = 'arundinaria_gigantea';
            $taxon_id = "bambusa";
        }elseif($url == self::MIDATLANTIC_TAXA_PREFIX."privets.htm")
        {
            $taxon_ids = array();
            $this->write_taxon("ligustrum", "Ligustrum", "Ligustrum", "Oleaceae");
            $taxon_ids[] = 'ligustrum';
            $this->write_taxon("ligustrum_obtusifolium", "Ligustrum obtusifolium Sieb. & Zucc.", "Ligustrum obtusifolium", "Oleaceae", "Border privet");
            $taxon_ids[] = 'ligustrum_obtusifolium';
            $this->write_taxon("ligustrum_ovalifolium", "Ligustrum ovalifolium Hassk.", "Ligustrum ovalifolium", "Oleaceae", "California privet");
            $taxon_ids[] = 'ligustrum_ovalifolium';
            $this->write_taxon("ligustrum_sinense", "Ligustrum sinense Lour.", "Ligustrum sinense", "Oleaceae", "Chinese privet");
            $taxon_ids[] = 'ligustrum_sinense';
            $this->write_taxon("ligustrum_vulgare", "Ligustrum vulgare L.", "Ligustrum vulgare", "Oleaceae", "European privet");
            $taxon_ids[] = 'ligustrum_vulgare';
            $taxon_id = "ligustrum";
        }elseif($url == self::MIDATLANTIC_TAXA_PREFIX."ornu-orum.htm")
        {
            $taxon_ids = array();
            $this->write_taxon("ornithogalum", "Ornithogalum", "Ornithogalum", "Liliaceae");
            $taxon_ids[] = 'ornithogalum';
            $this->write_taxon("ornithogalum_nutans", "Ornithogalum nutans L.", "Ornithogalum nutans", "Liliaceae", "Nodding Star-of-Bethlehem");
            $taxon_ids[] = 'ornithogalum_nutans';
            $this->write_taxon("ornithogalum_umbellatum", "Ornithogalum umbellatum L.", "Ornithogalum umbellatum", "Liliaceae", "Sleepydick");
            $taxon_ids[] = 'ornithogalum_umbellatum';
            $taxon_id = "ornithogalum";
        }else
        {
            $common_name = null;
            $scientific_name = null;
            $canonical_form = null;
            $family = null;
            if(preg_match("/<p class=\"heading-1-left-16-pt\">(.*?)<\/p>/ims", $taxa_page_html, $arr))
            {
                $common_name = trim($arr[1]);
                $common_name = str_replace("&rsquo;", "'", $common_name);
            }
            if(preg_match("/<p class=\"real-body-text\">(<i>.*?)<br \/>(.*?<br \/>)?(.*?)\((.*?)\)<\/p>/ims", $taxa_page_html, $arr))
            {
                $scientific_name = trim(html_entity_decode($arr[1], ENT_QUOTES, 'UTF-8'));
                $family1 = trim($arr[3]);
                $family2 = trim($arr[4]);
                $family = $family2;
                if(preg_match("/family/", $family2)) $family = $family1;
            }elseif(preg_match("/<p class=\"real-body-text\">(<i>.*?)<br \/>(.*?) Family<\/p>/ims", $taxa_page_html, $arr))
            {
                $scientific_name = trim(html_entity_decode($arr[1], ENT_QUOTES, 'UTF-8'));
                $family = trim($arr[2]);
            }
            list($scientific_name, $canonical_form, $taxon_id) = self::evaluate_scientific_name($scientific_name);
            $this->write_taxon($taxon_id, $scientific_name, $canonical_form, $family, $common_name);
            $taxon_ids = array($taxon_id);
        }
        
        
        // DataObjects
        if(preg_match("/<p class=\"real-body-text\"><span class=\"header-1\">Origin:<\/span> (.*?)<\/p>/ims", $taxa_page_html, $arr))
        {
            $origin = trim($arr[1]);
            $this->write_midatlantic_text('Origin', $origin, 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution', $taxon_ids, $taxon_id."/midatlantic_origin", $url);
        }
        if(preg_match("/<p class=\"real-body-text\"><b>Background<br \/>.*?<\/b>(.*?)<\/p>/ims", $taxa_page_html, $arr))
        {
            $background = $arr[1];
            $this->write_midatlantic_text('History in the United States', $background, 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#TaxonBiology', $taxon_ids, $taxon_id."/midatlantic_background", $url);
        }
        if(preg_match("/<p class=\"real-body-text\"><b>Distribution and Habitat<br \/>.*?<\/b>(.*?)<\/p>/ims", $taxa_page_html, $arr))
        {
            $distribution = $arr[1];
            $this->write_midatlantic_text('Distribution and Habitat in the United States', $distribution, 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution', $taxon_ids, $taxon_id."/midatlantic_distribution", $url);
        }
        if(preg_match("/<p class=\"real-body-text\"><b>Ecological Threat<br \/>.*?<\/b>(.*?)<\/p>/ims", $taxa_page_html, $arr))
        {
            $threat = $arr[1];
            $this->write_midatlantic_text('Ecological Threat in the United States', $threat, 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#RiskStatement', $taxon_ids, $taxon_id."/midatlantic_threat", $url);
        }
        if(preg_match("/<p class=\"heading-1-left-\">Description and Biology<\/p>(.*?<\/ul>)/ims", $taxa_page_html, $arr))
        {
            $biology = $arr[1];
            $this->write_midatlantic_text('Description and Biology', $biology, 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Morphology', $taxon_ids, $taxon_id."/midatlantic_biology", $url);
        }
        if(preg_match("/<p class=\"real-body-text\"><b>Prevention( and Control)?<br \/>.*?<\/b>(.*?)<\/p>/ims", $taxa_page_html, $arr))
        {
            $prevention = $arr[2];
            $prevention = preg_replace("/ \(see <a href=\".*?\">Control Options<\/a>\)/", "", $prevention);
            if(preg_match("/^See <a href=\".*?\">Control Options<\/a>/", $prevention)) $prevention = "";
            $this->write_midatlantic_text('Prevention and Control', $prevention, 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Management', $taxon_ids, $taxon_id."/midatlantic_prevention", $url);
        }
        // different kind of prevention
        if(preg_match("/<p class=\"heading-1-left-\">Prevention and Biological Control<\/p>.*?<p class=\"real-body-text\">(.*?)<\/p>/ims", $taxa_page_html, $arr))
        {
            $prevention = $arr[1];
            $prevention = preg_replace("/ \(see <a href=\".*?\">Control Options<\/a>\)/", "", $prevention);
            if(preg_match("/^See <a href=\".*?\">Control Options<\/a>/", $prevention)) $prevention = "";
            $this->write_midatlantic_text('Prevention and Control', $prevention, 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Management', $taxon_ids, $taxon_id."/midatlantic_prevention", $url);
        }
    }
    
    public function write_plants_to_watch($url)
    {
        $taxa_page_html = self::lookup_with_cache($url);
        if(preg_match_all("/<p class=\"heading-1-left-\"><a.*?<\/a>(.*?)<\/p>.*?-text\">(.*?)<br \/>(.*?)<\/p>.*?-text\">(.*?)<\/p>/ims", $taxa_page_html, $matches, PREG_SET_ORDER))
        {
            foreach($matches as $match)
            {
                $common_name = trim($match[1]);
                $scientific_name = trim(html_entity_decode($match[2], ENT_QUOTES, 'UTF-8'));
                $family = trim($match[3]);
                $description = trim($match[4]);
                if(preg_match("/\((.*?)\)/", $family, $arr)) $family = $arr[1];
                if(preg_match("/^(.*) \(previously/", $scientific_name, $arr)) $scientific_name = $arr[1];
                list($scientific_name, $canonical_form, $taxon_id) = self::evaluate_scientific_name($scientific_name);
                $this->write_taxon($taxon_id, $scientific_name, $canonical_form, $family, $common_name);
                $this->write_midatlantic_text('Description', $description, 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#TaxonBiology', $taxon_id, $taxon_id."/midatlantic_plants_to_watch", $url);
            }
        }
    }
    
    public function write_alien_taxon($url)
    {
        $taxa_page_html = self::lookup_with_cache($url);

        // Taxon
        if($url == self::ALIEN_TAXA_PREFIX."fact/tama1.htm")
        {
            $taxon_ids = array();
            $this->write_taxon("tamarix", "Tamarix", "Tamarix", "Tamaricaceae");
            $taxon_ids[] = 'tamarix';
            $this->write_taxon("tamarix_aphylla", "Tamarix aphylla", "Tamarix aphylla", "Tamaricaceae");
            $taxon_ids[] = 'tamarix_aphylla';
            $this->write_taxon("tamarix_chinensis", "Tamarix chinensis", "Tamarix chinensis", "Tamaricaceae");
            $taxon_ids[] = 'tamarix_chinensis';
            $this->write_taxon("tamarix_gallica", "Tamarix gallica", "Tamarix gallica", "Tamaricaceae");
            $taxon_ids[] = 'tamarix_gallica';
            $this->write_taxon("tamarix_parviflora", "Tamarix parviflora", "Tamarix parviflora", "Tamaricaceae");
            $taxon_ids[] = 'tamarix_parviflora';
            $this->write_taxon("tamarix_ramosissima", "Tamarix ramosissima", "Tamarix ramosissima", "Tamaricaceae");
            $taxon_ids[] = 'tamarix_ramosissima';
            $taxon_id = "tamarix";
        }elseif($url == self::ALIEN_TAXA_PREFIX."fact/loni1.htm")
        {
            $taxon_ids = array();
            $this->write_taxon("lonicera", "Lonicera", "Lonicera", "Caprifoliaceae");
            $taxon_ids[] = 'lonicera';
            $this->write_taxon("lonicera_fragrantissima", "Lonicera fragrantissima", "Lonicera fragrantissima", "Caprifoliaceae", "fragrant honeysuckle");
            $taxon_ids[] = 'lonicera_fragrantissima';
            $this->write_taxon("lonicera_maackii", "Lonicera maackii", "Lonicera maackii", "Caprifoliaceae", "Amur honeysuckle");
            $taxon_ids[] = 'lonicera_maackii';
            $this->write_taxon("lonicera_morrowii", "Lonicera morrowii", "Lonicera morrowii", "Caprifoliaceae", "Morrow's honeysuckle");
            $taxon_ids[] = 'lonicera_morrowii';
            $this->write_taxon("lonicera_standishii", "Lonicera standishii", "Lonicera standishii", "Caprifoliaceae", "Standish's honeysuckle");
            $taxon_ids[] = 'lonicera_standishii';
            $this->write_taxon("lonicera_tatarica", "Lonicera tatarica", "Lonicera tatarica", "Caprifoliaceae", "Tartarian honeysuckle");
            $taxon_ids[] = 'lonicera_tatarica';
            $this->write_taxon("lonicera_xylosteum", "Lonicera xylosteum", "Lonicera xylosteum", "Caprifoliaceae", "European fly honeysuckle");
            $taxon_ids[] = 'lonicera_xylosteum';
            $this->write_taxon("lonicera_x_bella", "Lonicera X bella", "Lonicera X bella", "Caprifoliaceae", "pretty honeysuckle");
            $taxon_ids[] = 'lonicera_x_bella';
            $taxon_id = "lonicera";
        }else
        {
            $common_name = null;
            $scientific_name = null;
            $canonical_form = null;
            $family = null;
            if(preg_match("/body\" -->.*?<IMG.*? ALT=\"(.*?)\"/ims", $taxa_page_html, $arr))
            {
                $common_name = trim($arr[1]);
            }else echo "****COMMON\n";

            if(preg_match("/<FONT.*?SIZE=\"\+1\">(.*?)<IMG/ims", $taxa_page_html, $arr))
            {
                $scientific_name = trim(html_entity_decode($arr[1], ENT_QUOTES, 'UTF-8'));
                $scientific_name = trim(str_replace("\r", " ", $scientific_name));
                $scientific_name = trim(str_replace("\n", " ", $scientific_name));
                if(preg_match("/^(.*)<.*?>[a-z- ]* family *\((.*?)(\)|, formerly)/i", $scientific_name, $arr))
                {
                    $scientific_name = trim($arr[1]);
                    $family = trim($arr[2]);
                }else echo "****FAMILY\n";

                $scientific_name = str_replace("</FONT></I>", " ", $scientific_name);
                $scientific_name = str_replace("<I><BR>", "<I>", $scientific_name);
                $scientific_name = preg_replace("/<FONT.*?\+1\">/ims", "", $scientific_name);
                if(preg_match("/^(.*?)<\/FONT>/ims", $scientific_name, $arr)) $scientific_name = trim($arr[1]);
                if(preg_match("/^(.*?)<BR>/ims", $scientific_name, $arr)) $scientific_name = trim($arr[1]);
                if(preg_match("/^(.*?)\(previously/ims", $scientific_name, $arr)) $scientific_name = trim($arr[1]);
                while(preg_match("/  /", $scientific_name)) $scientific_name = str_replace("  ", " ", $scientific_name);
                $scientific_name = str_replace(" </I>", "</I>", $scientific_name);
                $scientific_name = str_replace("<I> ", "<I>", $scientific_name);
                $scientific_name = preg_replace("/<\/I>([^ ])/ims", "</I> \\1", $scientific_name);
                $scientific_name = str_replace(".<", ". <", $scientific_name);
                if(preg_match("/<I><EM>/", $scientific_name))
                {
                    $scientific_name = str_replace("<EM>", "", $scientific_name);
                    $scientific_name = str_replace("</EM>", "", $scientific_name);
                }else
                {
                    $scientific_name = str_replace("<EM>", "<I>", $scientific_name);
                    $scientific_name = str_replace("</EM>", "</I>", $scientific_name);
                }

                // too many names
                if(preg_match("/,/", $scientific_name)) return false;

                list($scientific_name, $canonical_form, $taxon_id) = self::evaluate_scientific_name($scientific_name);
            }else echo "****SCIENTIFIC\n";

            if(!$scientific_name || !$taxon_id) return;
            $this->write_taxon($taxon_id, $scientific_name, $canonical_form, $family, $common_name);
            $taxon_ids = array($taxon_id);
        }
        echo "\n$url<br/>\n";
        
        $taxa_page_html = preg_replace("/<IMG( .*?)\/?>/ims", "", $taxa_page_html);
        $taxa_page_html = preg_replace("/<P CLASS=\"style1\">(<B>)?(<I>)?<FONT (SIZE=\"-1\" )?COLOR=\"#(117711|007700|006600)\".*?>/", "<GREEN_COMMENT>", $taxa_page_html);
        $taxa_page_html = preg_replace("/<P (ALIGN=\"LEFT\" )?CLASS=\"style1( style1)*\">(<FONT COLOR=\"#000000\">){0,3}(<SPAN CLASS=\"style1\">)? ?<B>/", "<MARK>", $taxa_page_html);
        
        if(preg_match("/NATIVE.*?RANGE(<BR>.*?<\/B>|<\/B>.*?<BR>)(<\/FONT>)?(.*?)</ims", $taxa_page_html, $arr))
        {
            $native_range = trim(html_entity_decode($arr[3], ENT_QUOTES, 'UTF-8'));
            $native_range = trim(str_replace("\r", " ", $native_range));
            $native_range = trim(str_replace("\n", " ", $native_range));
            while(preg_match("/  /", $native_range)) $native_range = str_replace("  ", " ", $native_range);
            $this->write_alien_text('Distribution', $native_range, 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution', $taxon_ids, $taxon_id."/alien_range", $url);
        }else echo "****NATIVE\n";
        
        if(preg_match("/> *DESCRIPTION(<BR>.*?<\/B>|<\/B>.*?<BR>)(.*?)<MARK>/ims", $taxa_page_html, $arr))
        {
            $description = self::cleanse_alien_description($arr[2]);
            $this->write_alien_text('Morphology', $description, 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Morphology', $taxon_ids, $taxon_id."/alien_description", $url);
        }else echo "****DESCRIPTION\n";
        
        if(preg_match("/> *ECOLOGICAL.*?THREAT(<BR>.*?<\/B>|<\/B>.*?<BR>)(.*?)<MARK>/ims", $taxa_page_html, $arr))
        {
            $threat = self::cleanse_alien_description($arr[2]);
            $this->write_alien_text('Relevance to Humans and Ecosystems: Risks', $threat, 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#RiskStatement', $taxon_ids, $taxon_id."/alien_threat", $url);
        }else echo "****ECOLOGICAL\n";
        
        if(preg_match("/> *DISTRIBUTION.*?IN.*?THE.*?UNITED.*?STATES(<BR>.*?<\/B>|<\/B>.*?<BR>)(.*?)<MARK>/ims", $taxa_page_html, $arr))
        {
            $distribution = self::cleanse_alien_description($arr[2]);
            $this->write_alien_text('Distribution', $distribution, 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution', $taxon_ids, $taxon_id."/alien_distribution", $url);
        }else echo "****DISTRIBUTION\n";
        
        if(preg_match("/> *HABITAT.*?IN.*?THE.*?UNITED.*?STATES(<BR>.*?<\/B>|<\/B>.*?<BR>)(.*?)<MARK>/ims", $taxa_page_html, $arr))
        {
            $habitat = self::cleanse_alien_description($arr[2]);
            $this->write_alien_text('Habitat', $habitat, 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Habitat', $taxon_ids, $taxon_id."/alien_habitat", $url);
        }else echo "****HABITAT\n";
        
        if(preg_match("/> *BACKGROUND(<BR>.*?<\/B>|<\/B>.*?<BR>)(.*?)<MARK>/ims", $taxa_page_html, $arr))
        {
            $background = self::cleanse_alien_description($arr[2]);
            $this->write_alien_text('Brief Summary', $background, 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#TaxonBiology', $taxon_ids, $taxon_id."/alien_background", $url);
        }else echo "****BACKGROUND\n";
        
        if(preg_match("/> *BIOLOGY.*?(&amp;|and).*?SPREAD(<BR>.*?<\/B>|<\/B>.*?<BR>)(.*?)<MARK>/ims", $taxa_page_html, $arr))
        {
            $biology = self::cleanse_alien_description($arr[3]);
            $this->write_alien_text('Life History and Behavior: Reproduction', $biology, 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Reproduction', $taxon_ids, $taxon_id."/alien_biology", $url);
        }else echo "****BIOLOGY\n";
    }
    
    public static function cleanse_alien_description($description)
    {
        $description = trim($description);
        $description = trim(str_replace("\r", " ", $description));
        $description = trim(str_replace("\n", " ", $description));
        $description = trim(str_replace("&nbsp;", " ", $description));
        if(preg_match("/^(.*)<BR> +$/", $description, $arr)) $description = trim($arr[1]);
        $description = preg_replace("/<\/?(FONT|SPAN|A)( .*?)?>/ims", "", $description);
        $description = preg_replace("/<\/P>/ims", "", $description);
        $description = preg_replace("/ *<P( .*?)> */ims", "</P><P>", $description);
        while(preg_match("/<GREEN_COMMENT>/", $description)) $description = preg_replace("/<GREEN_COMMENT>(.*?)($|<GREEN_COMMENT>)/ims", "</P><P><B>\\1</B></P>\\2", $description);
        while(preg_match("/(<\/?B> *){2}/", $description, $arr)) $description = preg_replace("/(<\/?B> *){2}/", "\\1", $description);
        $description = preg_replace("/<\!--.*?-->/ims", "", $description);
        while(preg_match("/  /", $description)) $description = str_replace("  ", " ", $description);
        $description = "<P>".trim($description)."</P>";
        while(preg_match("/(<\/?P> *){2}/", $description, $arr)) $description = preg_replace("/(<\/?P> *){2}/", "\\1", $description);
        $description = preg_replace("/(.)<P>/ims", "\\1</P><P>", $description);
        $description = preg_replace("/<BR><\/P>/ims", "</P>", $description);
        return $description;
    }
    
    private static function evaluate_scientific_name($scientific_name)
    {
        if(preg_match_all("/<i>(.*?)<\/i>/i", trim($scientific_name), $matches, PREG_SET_ORDER))
        {
            $canonical_form = trim($matches[0][1]);
            if(@$matches[1]) $canonical_form .= " ".trim($matches[1][1]);
            if(@$matches[2]) $canonical_form .= " ".trim($matches[2][1]);
            $canonical_form = str_replace("ssp. ", "", $canonical_form);
            $scientific_name = str_ireplace("<i>", "", $scientific_name);
            $scientific_name = str_ireplace("</i>", "", $scientific_name);
            $scientific_name = str_replace("  ", " ", $scientific_name);
            $scientific_name = str_ireplace("&rsquo;", "'", $scientific_name);
        }
        if(@!$canonical_form) $canonical_form = Functions::canonical_form($scientific_name);
        $taxon_id = str_replace(" ", "_", strtolower($canonical_form));
        return array($scientific_name, $canonical_form, $taxon_id);
    }
    
    // default expire time is 30 days
    private static function lookup_with_cache($url, $expire_seconds = 2592000)
    {
        $md5 = md5($url);
        if(!file_exists(DOC_ROOT . 'update_resources/connectors/files/plant_conservation')) mkdir(DOC_ROOT . 'update_resources/connectors/files/plant_conservation');
        if(!file_exists(DOC_ROOT . 'update_resources/connectors/files/plant_conservation/cache')) mkdir(DOC_ROOT . 'update_resources/connectors/files/plant_conservation/cache');
        $cache_path = DOC_ROOT . "update_resources/connectors/files/plant_conservation/cache/". $md5 .".html";
        if(file_exists($cache_path))
        {
            $taxon_page_html = file_get_contents($cache_path);
            // checking for some string that appears on all pages - we may have cached a 404 page for example
            if(preg_match("/<body/ims", $taxon_page_html))
            {
                $file_age_in_seconds = time() - filemtime($cache_path);
                if($file_age_in_seconds < $expire_seconds) return $taxon_page_html;
            }
            @unlink($cache_path);
        }
        $taxon_page_html = Functions::get_remote_file($url, DOWNLOAD_WAIT_TIME, 120);
        $FILE = fopen($cache_path, 'w+');
        fwrite($FILE, $taxon_page_html);
        fclose($FILE);
        return $taxon_page_html;
    }
}

?>