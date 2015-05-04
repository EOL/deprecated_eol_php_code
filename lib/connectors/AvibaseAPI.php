<?php
namespace php_active_record;
/* connectors: [353, 354, 355]  
Avibase has 3 resources. Each resource will use this connector. Connector scrapes the site and generates the EOL XML.
*/

class AvibaseAPI
{
    const AVIBASE_SOURCE_URL = "http://avibase.bsc-eoc.org/species.jsp?avibaseid=";
    const AVIBASE_SERVICE_URL = "http://avibase.bsc-eoc.org/checklist.jsp?";
    
    public function __construct($resource_id, $checklist_name, $for_testing = false)
    {
        $this->resource_id = $resource_id;
        $this->checklist_name = $checklist_name;
        $this->for_testing = $for_testing;
        // "sibleymonroe", //Sibley &amp; Monroe 1996
        // "howardmoore",  //Howard &amp; Moore 3rd edition (corrigenda 8)
        // "clements5",    //Clements 5th edition (updated 2005)
        // "clements",     //Clements 6th edition (updated 2011)
        // "cinfo",        //Commission internationale pour les noms français d'oiseaux (CINFO 1993, rev. 2009)
        // "ioc",          //IOC World Bird Names (2011)
        // "ebird",        //eBird 1.05 (2010)
        // "hbw",          //Handbooks of the Birds of the World
        // "aou"           //American Ornithologist Union 7th edition (including 51st suppl.)
    }

    function get_all_taxa()
    {
        // Get the complete list of names for each geographic region in the current checklist
        $this->prepare_list_of_family_taxa();
        
        // Get all the common names and synonyms from screen scraping the taxon pages
        $this->prepare_common_names_and_synonymy();
        
        // start to create the resource
        return $this->prepare_resource();
    }
    
    function prepare_resource()
    {
        if(!($resource_file = fopen(CONTENT_RESOURCE_LOCAL_PATH . $this->resource_id ."_temp.xml", "w+")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . CONTENT_RESOURCE_LOCAL_PATH . $this->resource_id ."_temp.xml");
          return;
        }
        fwrite($resource_file, \SchemaDocument::xml_header());
        
        $language_iso_codes = self::language_iso_codes();
        $avibaseids_added = array();
        foreach($this->names_in_families as $taxon_name => $metadata)
        {
            $taxon_parameters = array();
            $taxon_parameters['identifier'] = $metadata['avibaseid'];
            if(isset($avibaseids_added[$metadata['avibaseid']])) continue;
            $avibaseids_added[$metadata['avibaseid']] = 1;
            $taxon_parameters['kingdom'] = "Animalia";
            $taxon_parameters['phylum'] = "Chordata";
            $taxon_parameters['class'] = "Aves";
            $taxon_parameters['order'] = @$this->family_orders[$metadata['family']];
            $taxon_parameters['family'] = @$metadata['family'];
            $taxon_parameters['scientificName'] = $metadata['taxon_name'];
            $taxon_parameters['source'] = self::AVIBASE_SOURCE_URL . $metadata['avibaseid'];
            if(preg_match("/^([a-z][^ ]+) /i", $metadata['taxon_name'], $arr))
            {
                $taxon_parameters['genus'] = $arr[1];
            }
            if(!$taxon_parameters['scientificName']) continue;
            
            $taxon_parameters['common_names'] = array();
            if(isset($metadata['common_names']))
            {
                foreach($metadata['common_names'] as $language => $common_names)
                {
                    if($language_iso_code = @$language_iso_codes[$language])
                    {
                        foreach($common_names as $common_name => $value)
                        {
                            $taxon_parameters['commonNames'][] = new \SchemaCommonName(array("name" => $common_name, "language" => $language_iso_code));
                        }
                    }
                    else debug("No iso code for: $language \n");
                }
            }
            
            $taxon_parameters['synonyms'] = array();
            if(isset($metadata['synonyms']))
            {
                foreach($metadata['synonyms'] as $synonym => $value)
                {
                    if($synonym == $metadata['taxon_name']) continue;
                    $taxon_parameters['synonyms'][] = new \SchemaSynonym(array("synonym" => $synonym, "relationship" => 'synonym'));
                }
            }
            
            $taxon = new \SchemaTaxon($taxon_parameters);
            fwrite($resource_file, $taxon->__toXML());
        }
        
        fwrite($resource_file, \SchemaDocument::xml_footer());
        fclose($resource_file);
        
        // cache the previous version and make this new version the current version
        @unlink(CONTENT_RESOURCE_LOCAL_PATH . $this->resource_id . "_previous.xml");
        @rename(CONTENT_RESOURCE_LOCAL_PATH . $this->resource_id . ".xml", CONTENT_RESOURCE_LOCAL_PATH . $this->resource_id . "_previous.xml");
        rename(CONTENT_RESOURCE_LOCAL_PATH . $this->resource_id . "_temp.xml", CONTENT_RESOURCE_LOCAL_PATH . $this->resource_id . ".xml");
        
        // returning the last taxon
        return $taxon;
    }

    function prepare_list_of_family_taxa()
    {
        $regions = array(
        'nam', // north america
        'cam', // central america
        'sam', // south america
        'eur', // europe
        'afr', // africa*
        'afc', // continental africa*
        'mid', // middle east
        'asi', // asia
        'oce', // oceania*
        'aus', // australasia*
        'pac', // pacific islands
        'hol', // holarctic
        'pal', // palearctic
        'wpa'  // western palearctic
        );
        
        if($this->for_testing) $regions = array('nam'); //debug
        
        
        $this->names_in_families = array();
        $this->family_orders = array();
        foreach($regions as $region)
        {
            $url = self::AVIBASE_SERVICE_URL . '&region=' . $region . '&list=' . $this->checklist_name;
            debug("$url \n");
            self::get_taxa_from_html($url);
        }
    }
    
    function prepare_common_names_and_synonymy()
    {
        static $i = 0;
        $start_time = time_elapsed();
        $taxa_count = count($this->names_in_families);
        
        foreach($this->names_in_families as $taxon_name => $metadata)
        {
            debug($metadata['avibaseid'] . "\n");
            $taxon_page_html = Functions::lookup_with_cache(self::AVIBASE_SOURCE_URL . $metadata['avibaseid'], array('validation_regex' => 'AVBContainerText'));
            $common_names_and_synonyms = self::scrape_common_names_and_synonyms($taxon_page_html);
            
            // Synonyms are stored in the Common Names list with language Latin
            if($synonyms = @$common_names_and_synonyms['Latin'])
            {
                $this->names_in_families[$taxon_name]['synonyms'] = $synonyms;
                unset($common_names_and_synonyms['Latin']);
            }
            
            if($common_names_and_synonyms)
            {
                $this->names_in_families[$taxon_name]['common_names'] = $common_names_and_synonyms;
            }
            
            // Set the order of this taxon's family if we don't know it already
            if(@!$this->family_orders[$metadata['family']])
            {
                $this->family_orders[$metadata['family']] = self::scrape_order_from_taxon_page($taxon_page_html);
            }
            
            $i++;
            // if($i > 100) break;
            if($i % 100 == 0)
            {
                $estimated_total_time = (((time_elapsed() - $start_time) / $i) * count($taxa_count));
                debug("Time spent ($i records) ". time_elapsed() ."\n");
                debug("Estimated total seconds : $estimated_total_time\n");
                debug("Estimated total hours : ". ($estimated_total_time / (60 * 60)) ."\n");
                debug("Memory : ". memory_get_usage() ."\n");
            }
        }
    }

    function get_taxa_from_html($url)
    {
        $html = Functions::get_remote_file($url, array('timeout' => 1200, 'download_attempts' => 5)); //20mins download timeout, 5 retry attempts
        $parts = explode("<tr valign=bottom>", $html);
        // the first block doesn't contain name information so remove it
        array_shift($parts);
        
        // each block corresponds to a Family and its species
        foreach($parts as $html_block)
        {
            // the last block will also have the tail end of the HTML which we also don't need
            if(preg_match("/^(.*?)<\/table>/ims", $html_block, $arr)) $html_block = $arr[1];
            
            // pull out the family
            if(preg_match("/<b>(.*?)<\/b>/ims", $html_block, $arr)) $family = trim($arr[1]);
            else continue;
            
            // sometimes the Family is really => ORDER: Family
            // Families can be Incertae Sedis, Genera Incertae Sedis, Genus Incertae Sedis, ...
            if(preg_match("/^([a-z]+): (.+)$/ims", $family, $arr))
            {
                $family = ucfirst(strtolower($arr[2]));
                $this->family_orders[$family] = ucfirst(strtolower($arr[1]));
            }
            
            if(preg_match_all("/<tr><td>(.*?)<\/td><td><a href=\"species.jsp\?avibaseid=(.*?)\">(.*?)<\/a><\/td><td>(.*?)<\/td><\/tr>/ims", $html_block, $matches, PREG_SET_ORDER))
            {
                foreach($matches as $match)
                {
                    $common_name = trim($match[1]);
                    $avibaseid = trim($match[2]);
                    $taxon_name = trim($match[3]);
                    $conservation_status = trim($match[4]);
                    if(preg_match("/<i>(.*?)<\/i>/ims", $taxon_name, $arr)) $taxon_name = trim($arr[1]);
                    
                    if($metadata = @$this->names_in_families[$taxon_name])
                    {
                        // this means that in one regional checklist they place this taxon in a different family
                        if($metadata['family'] != $family)
                        {
                            debug("Family Conflict with $taxon_name\n");
                            continue;
                        }
                        
                        // this means that in one regional checklist they use a different URL for the taxon
                        if($metadata['avibaseid'] != $avibaseid)
                        {
                            debug("ID Conflict with $taxon_name\n");
                            continue;
                        }
                    }
                    
                    $this->names_in_families[$taxon_name] = array(
                        'taxon_name' => $taxon_name,
                        'family' => $family,
                        'common_name' => $common_name,
                        'avibaseid' => $avibaseid,
                        'conservation_status' => $conservation_status);
                }
            }
            if($this->for_testing) break;
        }
    }

    public static function scrape_order_from_taxon_page($taxon_page_html)
    {
        if(preg_match("/<b>Order:<\/b><br>(.*?)<br>/ims", $taxon_page_html, $arr))
        {
            $order = $arr[1];
            $order = trim(str_ireplace("&nbsp;", "", $order));
            return $order;
        }
    }

    public static function scrape_common_names_and_synonyms($taxon_page_html)
    {
        $common_names_and_synonyms = array();
        if(preg_match("/<b>Other synonyms<\/b>(.*?)<\/table>/ims", $taxon_page_html, $arr))
        {
            $common_names_html = $arr[1];
            $langauge_names_html = explode("<br>", $common_names_html);
            // remove first item from array
            array_shift($langauge_names_html);
            foreach($langauge_names_html as $html_block)
            {
                // trim last item in array
                if(preg_match("/^(.*)<\/font>/", $html_block)) $html_block = $arr[1];
                if(preg_match("/^<b>(.*): <\/b>(.*)$/", trim($html_block), $arr))
                {
                    $language = trim($arr[1]);
                    $common_names = explode(",", trim($arr[2]));
                    foreach($common_names as &$cn)
                    {
                        $common_names_and_synonyms[$language][trim($cn)] = 1;
                    }
                }
            }
        }
        return $common_names_and_synonyms;
    }

    public static function language_iso_codes()
    {
        //http://www.loc.gov/standards/iso639-2/php/code_list.php
        //http://www.rssboard.org/rss-language-codes
        
        static $lang = array();
        if($lang) return $lang;
        $lang['Czech'] = 'cs';
        $lang['German'] = 'de';
        $lang['French'] = 'fr';
        $lang['Spanish'] = 'es';
        $lang['Spanish (Colombia)'] = 'es-co';
        $lang['Spanish (Honduras)'] = 'es-hn';
        $lang['Spanish (Nicaragua)'] = 'es-ni';
        $lang['Spanish (Costa Rica)'] = 'es-cr';
        $lang['Spanish (Mexico)'] = 'es-mx';
        $lang['Spanish (Venezuela)'] = 'es-ve';
        $lang['Spanish (Argentine)'] = 'es-ar';
        $lang['Spanish (Chile)'] = 'es-cl';
        $lang['Spanish (Ecuador)'] = 'es-ec';
        $lang['Dutch'] = 'nl';
        $lang['Polish'] = 'pl';
        $lang['Portuguese (Brazil)'] = 'pt-br';
        $lang['Portuguese'] = 'pt-pt';
        $lang['Estonian'] = 'et';
        $lang['Slovak'] = 'sk';
        $lang['Chinese'] = 'cn';
        $lang['Finnish'] = 'fi';
        $lang['Italian'] = 'it';
        $lang['Japanese'] = 'ja';
        // $lang['Mayan languages'] = 'myn';
        $lang['Norwegian'] = 'no';
        $lang['Russian'] = 'ru';
        $lang['Swedish'] = 'sv';
        $lang['Afrikaans'] = 'af';
        $lang['Bulgarian'] = 'bg';
        $lang['Catalan'] = 'ca';
        $lang['Welsh'] = 'cy';
        $lang['English'] = 'en';
        $lang['Spanish (Dominican Rep.)'] = 'es-do';
        $lang['Basque'] = 'eu';
        $lang['Faroese'] = 'fo';
        // $lang['Friulian'] = 'fur';
        $lang['Irish'] = 'ga';
        $lang['Haitian Creole French'] = 'ht';
        $lang['Croatian'] = 'hr';
        $lang['Icelandic'] = 'is';
        $lang['Cornish'] = 'kw';
        $lang['Lithuanian'] = 'lt';
        $lang['Malagasy'] = 'mg';
        $lang['Maltese'] = 'mt';
        $lang['Occitan'] = 'oc';
        $lang['Romanian'] = 'ro';
        // $lang['Romany'] = 'rom';
        $lang['Ojibwa'] = 'oj';
        $lang['Gaelic'] = 'gd';
        $lang['Galician'] = 'gl';
        $lang['Inuktitut'] = 'iu';
        $lang['Khakas'] = '';
        $lang['Kazakh'] = 'kk';
        $lang['Greenlandic'] = 'kl';
        $lang['Mongolian'] = 'mn';
        // $lang['Tuvinian'] = 'tyv';
        $lang['Araucanian'] = '';
        $lang['Mapundungun'] = '';
        $lang['Azerbaijani'] = 'az';
        $lang['Valencian'] = 'ca';
        $lang['Greek (Cypriot)'] = 'el';
        $lang['Chinese (Taiwan)'] = 'zh-tw';
        $lang['Kirghiz'] = 'ky';
        $lang['Spanish (Bolivia)'] = 'es-bo';
        $lang['Modenese'] = '';
        // $lang['Creoles and Pidgins, French-based (Other)'] = 'cpf';
        // $lang['Karelian'] = 'krl';
        $lang['Maori'] = 'mi';
        $lang['Rennell'] = '';
        $lang['Rotokas'] = '';
        $lang['Naasioi'] = '';
        // $lang['Tai'] = 'tai';
        $lang['Aymara'] = 'ay';
        $lang['Reggiano'] = '';
        $lang['Somali'] = '';
        $lang['Uzbek'] = '';
        $lang['Aragonese'] = 'an';
        $lang['Corsican'] = 'co';
        $lang['Kurmanji'] = '';
        // $lang['Sicilian'] = 'scn';
        // $lang['Karachay-Balkar'] = 'krc';
        $lang['Napoletano-calabrese'] = '';
        $lang['Aymara'] = 'ay';
        // $lang['Sicilian'] = 'scn';
        $lang['Biellese'] = '';
        $lang['Piemontese'] = '';
        $lang['Kanuri'] = 'kr';
        $lang['Ossetian'] = 'os';
        $lang['Walloon'] = 'wa';
        $lang['Lombard'] = '';
        // $lang['Delaware'] = 'del';
        $lang['Bolognese'] = '';
        $lang['Amharic'] = 'am';
        $lang['Mamasa'] = '';
        $lang['Bengali'] = 'bn';
        $lang['Tibetan'] = 'bo';
        $lang['Bosnian'] = 'bs';
        $lang['Napulitano'] = '';
        $lang['Paduan'] = '';
        $lang['Ligurian'] = '';
        // $lang['Moksha'] = 'mdf';
        $lang['Flemish'] = 'nl';
        $lang['Quechua'] = 'qu';
        $lang['Tamil'] = 'ta';
        $lang['Tatar'] = 'tt';
        $lang['Venetian'] = '';
        $lang['Wolof'] = 'wo';
        // $lang['Kalmyk'] = 'xal';
        $lang['Tamil'] = 'ta';
        $lang['Thai'] = 'th';
        $lang['Vietnamese'] = 'vi';
        // $lang['Cebuano'] = 'ceb';
        // $lang['Hawaiian'] = 'haw';
        $lang['Indonesian'] = 'id';
        $lang['Malay'] = 'ms';
        // $lang['Palauan'] = 'pau';
        $lang['Spanish (Paraguay)'] = 'es-py';
        $lang['Spanish (Uruguay)'] = 'es-uy';
        $lang['Guarani'] = 'gn';
        $lang['Guadeloupean Creole French'] = '';
        $lang['Limburgish'] = 'li';
        $lang['Shor'] = '';
        $lang['Moldavian'] = 'ro';
        $lang['Emiliano-romagnolo'] = '';
        $lang['Persian'] = 'fa';
        $lang['Gujarati'] = 'gu';
        $lang['Hindi'] = 'hi';
        $lang['Brescian'] = '';
        $lang['Georgian'] = '';
        // $lang['Ladin'] = 'lad';
        $lang['Marathi'] = 'mr';
        $lang['Punjabi'] = 'pa';
        $lang['Sanskrit'] = 'sa';
        $lang['Arabic'] = 'ar';
        $lang['Catalan (Balears)'] = 'ca';
        $lang['Armenian'] = 'hy';
        $lang['Georgian'] = 'ka';
        // $lang['Asturian'] = 'ast';
        $lang['Chuvash'] = 'cv';
        $lang['Georgian'] = 'ka';
        $lang['Kashmiri'] = 'ks';
        $lang['Korean'] = 'ko';
        $lang['Sardinian'] = 'sc';
        $lang['Northern Sami'] = 'se';
        $lang['Slovenian'] = 'sl';
        $lang['Albanian'] = 'sq';
        $lang['Siswant'] = '';
        $lang['Turkmen'] = 'tk';
        $lang['Turkish'] = 'tr';
        $lang['Ukrainian'] = 'uk';
        // $lang['Sorbian, Upper'] = 'hsb';
        // $lang['Sorbian, Lower'] = 'dsb';
        $lang['Belarusian'] = 'be';
        $lang['Breton'] = 'br';
        $lang['Danish'] = 'da';
        $lang['Greek'] = 'el';
        $lang['Esperanto'] = 'eo';
        $lang['Spanish (Cuba)'] = '';
        $lang['Frisian'] = 'fy';
        $lang['Manx'] = 'gv';
        $lang['Hebrew'] = 'he';
        $lang['Hungarian'] = 'hu';
        $lang['Kwangali'] = '';
        // $lang['Ladino'] = 'lad';
        $lang['Latvian'] = 'lv';
        $lang['Macedonian'] = 'mk';
        // $lang['Sotho, Northern'] = 'nso';
        $lang['Sotho, Southern'] = 'st';
        $lang['Romansh'] = 'rm';
        // $lang['Scots'] = 'sco';
        $lang['Shona'] = 'sn';
        $lang['Serbian'] = 'sr';
        $lang['Swahili'] = 'sw';
        $lang['Tswana'] = 'tn';
        $lang['Tsonga'] = 'ts';
        $lang['Xhosa'] = 'xh';
        $lang['Zulu'] = 'zu';
        
        $lang['Spanish (Cuba)'] = 'es-cu';
        // $lang['Khakas'] = 'kjh';
        // $lang['Kwangali'] = 'kwn';
        // $lang['Guadeloupean Creole French'] = 'gcf';
        // $lang['Venetian'] = 'vec';
        // $lang['Emiliano-romagnolo'] = 'eml';
        $lang['Siswant'] = 'ss';
        // $lang['Araucanian'] = 'arn';
        // $lang['Shor'] = 'cjs';
        $lang['Uzbek'] = 'uz';
        // $lang['Paduan'] = 'vec';
        return $lang;
    }
}
?>
