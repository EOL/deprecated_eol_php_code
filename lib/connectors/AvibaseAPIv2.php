<?php
namespace php_active_record;
/* connectors: [353, 354, 355]
Avibase has 3 resources. Each resource will use this connector. Connector scrapes the site and generates the EOL DWC-A.
355 is the only one that is published at the moment.
*/

class AvibaseAPIv2
{
    const AVIBASE_SOURCE_URL = "http://avibase.bsc-eoc.org/species.jsp?avibaseid=";
    const AVIBASE_SERVICE_URL = "http://avibase.bsc-eoc.org/checklist.jsp?";
    
    public function __construct($resource_id, $checklist_name, $for_testing = false)
    {
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $resource_id . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->download_options = array('expire_seconds' => 5184000, 'timeout' => 7200, 'download_wait_time' => 1000000); // 2 months expire_seconds
        // $this->download_options['expire_seconds'] = false;

        $this->resource_id      = $resource_id;
        $this->checklist_name   = $checklist_name;
        $this->for_testing      = $for_testing;
        
        // other checklists:
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

    function generate_archive()
    {
        self::prepare_list_of_family_taxa();
        $this->archive_builder->finalize(TRUE);
    }

    function prepare_list_of_family_taxa()
    {
        $regions = array(
        'NAM', // north america
        'NA1', // North America (US+CA)
        'AOU', // Amer. Ornithol. Union
        'ABA', // Amer. Birding Association
        'CA',  // Canada
        'PM',  // Saint-Pierre et Miquelon
        'US',  // United States
        'BM',  // Bermuda
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

        // $regions = array_reverse($regions);
        print_r($regions);
        
        if($this->for_testing) $regions = array('nam'); //debug
        foreach($regions as $region) self::get_taxa_list(self::AVIBASE_SERVICE_URL . '&region=' . $region . '&list=' . $this->checklist_name);
    }
    
    private function get_taxa_list($url)
    {
        echo "\n[$url]\n";
        if($html = Functions::lookup_with_cache($url, $this->download_options))
        {
            if(preg_match_all("/<tr valign=bottom>(.*?)<tr valign=bottom>/ims", $html, $arr))
            {
                foreach($arr[1] as $blk)
                {
                    $rec = array();
                    if(preg_match("/<b>(.*?)\:/ims", $blk, $arr2)) $rec['order'] = trim($arr2[1]);
                    if(preg_match("/:(.*?)<\/b>/ims", $blk, $arr2)) $rec['family'] = trim($arr2[1]);
                    if(preg_match_all("/<tr (.*?)<\/tr>/ims", $blk, $arr2))
                    {
                        foreach($arr2[1] as $blk2)
                        {
                            $rek = array();
                            if(preg_match("/260px\'>(.*?)<\/td>/ims", $blk2, $arr3)) $rek['comname'] = $arr3[1];
                            if(preg_match("/avibaseid=(.*?)\"/ims", $blk2, $arr3)) $rek['avibaseid'] = $arr3[1];
                            if(preg_match("/<i>(.*?)<\/i>/ims", $blk2, $arr3)) $rek['sciname'] = $arr3[1];
                            if(preg_match("/<font color=red>(.*?)<\/font>/ims", $blk2, $arr3)) $rek['redlist_status'] = strip_tags($arr3[1]);
                            $rek = array_map('trim', $rek);
                            $rec['taxa'][] = $rek;
                            // break; //debug
                        }
                    }
                    self::process_record($rec);
                    // break; //debug
                }
            }
        }
    }

    private function process_record($rec)
    {
        foreach($rec['taxa'] as $taxon)
        {
            $info = self::parse_taxon_page(self::AVIBASE_SOURCE_URL . $taxon['avibaseid']);
            $taxon['authorship']    = $info['authorship'];
            $taxon['comnames']      = $info['comnames'];
            $taxon['order']         = $rec['order'];
            $taxon['family']        = $rec['family'];
            self::write_archive($taxon);
            // break; //debug
        }
    }
    
    private function write_archive($taxon)
    {
        $t = new \eol_schema\Taxon();
        $t->taxonID                 = $taxon['avibaseid'];
        $t->scientificName          = $taxon['sciname'];
        $t->scientificNameAuthorship = $taxon['authorship'];
        $t->order                   = ucfirst(strtolower($taxon['order']));
        $t->family                  = $taxon['family'];
        $t->genus                   = self::get_genus($taxon['sciname']);
        $t->furtherInformationURL   = self::AVIBASE_SOURCE_URL . $taxon['avibaseid'];
        if(!isset($this->taxon_ids[$t->taxonID]))
        {
            $this->taxon_ids[$t->taxonID] = '';
            $this->archive_builder->write_object_to_file($t);
        }
        
        //write comnames and synonyms
        $language_iso_codes = self::language_iso_codes();
        foreach($taxon['comnames'] as $name)
        {
            if(!@$name['comnames']) continue; // e.g. http://avibase.bsc-eoc.org/species.jsp?avibaseid=483A2A51F4A5E37E -- see Malayalam
            foreach(@$name['comnames'] as $comname)
            {
                if(!($comname = trim($comname))) continue;
                if(!Functions::is_utf8($comname)) $comname = utf8_encode($comname);
                if($name['lang'] == "Latin") // these will be synonyms
                {
                    if($t->scientificName == $comname) continue;
                    $synonym = new \eol_schema\Taxon();
                    $synonym->taxonID               = strtolower(str_ireplace(" ", "_", $comname));
                    $synonym->scientificName        = $comname;
                    $synonym->acceptedNameUsageID   = $taxon['avibaseid'];
                    $synonym->taxonomicStatus       = "synonym";
                    if(!isset($this->taxon_ids[$synonym->taxonID]))
                    {
                        $this->taxon_ids[$synonym->taxonID] = '';
                        $this->archive_builder->write_object_to_file($synonym);
                    }
                }
                else // these will be common names
                {
                    $v = new \eol_schema\VernacularName();
                    $v->taxonID         = $taxon['avibaseid'];
                    $v->vernacularName  = $comname;
                    $v->language        = $language_iso_codes[$name['lang']];
                    $id = md5($v->vernacularName);
                    if(!isset($unique_id[$id]))
                    {
                        $unique_id[$id] = '';
                        $this->archive_builder->write_object_to_file($v);
                    }
                }
            }
        }
    }
    
    private function get_genus($sciname)
    {
        $parts = explode(" ", $sciname);
        if(isset($parts[1])) return $parts[0];
    }
    
    private function parse_taxon_page($url)
    {
        $final = array();
        $options = $this->download_options;
        if($html = Functions::lookup_with_cache($url, $options))
        {
            //get comnames
            if(preg_match("/<b>Other synonyms<\/b>(.*?)<\/font>/ims", $html, $arr))
            {
                $temp = explode("<br>", $arr[1]);
                foreach($temp as $t)
                {
                    $rec = array();
                    if(preg_match("/<b>(.*?)<\/b>/ims", $t, $arr)) $rec['lang'] = trim(str_ireplace(":", "", $arr[1]));
                    $temp = explode("</b>", $t); // get string right side of '</b>'
                    if($val = @$temp[1])
                    {
                        $comnames = explode(",", $val);
                        $rec['comnames'] = array_map('trim',$comnames);
                    }
                    if($rec) $final[] = $rec;
                }
            }
            
            //get authorship
            if(preg_match("/Citation:(.*?)<\/p>/ims", $html, $arr))
            {
                $authorship = Functions::remove_whitespace(strip_tags($arr[1]));
                $authorship = str_ireplace('&nbsp;', '', $authorship);
            }
            else
            {
                // no author! this assumes that a wrong file is cached; this merits a 2nd run of the connector
                $options['expire_seconds'] = 0;
                $html = Functions::lookup_with_cache($url, $options);
                echo "\nconnector has to run again\n";
            }
        }
        
        return array('comnames' => $final, 'authorship' => $authorship);
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
        $lang['Spanish (Peru)'] = 'es-pe';
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
        $lang['Mayan languages'] = ''; //'myn';
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
        $lang['Friulian'] = ''; //'fur';
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
        $lang['Romany'] = ''; //'rom';
        $lang['Ojibwa'] = 'oj';
        $lang['Gaelic'] = 'gd';
        $lang['Galician'] = 'gl';
        $lang['Inuktitut'] = 'iu';
        $lang['Khakas'] = '';
        $lang['Kazakh'] = 'kk';
        $lang['Greenlandic'] = 'kl';
        $lang['Mongolian'] = 'mn';
        $lang['Tuvinian'] = ''; //'tyv';
        $lang['Araucanian'] = '';
        $lang['Mapundungun'] = '';
        $lang['Azerbaijani'] = 'az';
        $lang['Valencian'] = 'ca';
        $lang['Greek (Cypriot)'] = 'el';
        $lang['Chinese (Taiwan)'] = 'zh-tw';
        $lang['Kirghiz'] = 'ky';
        $lang['Spanish (Bolivia)'] = 'es-bo';
        $lang['Modenese'] = '';
        $lang['Creoles and Pidgins, French-based (Other)'] = '';//'cpf';
        $lang['Karelian'] = '';//'krl';
        $lang['Maori'] = 'mi';
        $lang['Rennell'] = '';
        $lang['Rotokas'] = '';
        $lang['Naasioi'] = '';
        $lang['Tai'] = '';//'tai';
        $lang['Aymara'] = 'ay';
        $lang['Reggiano'] = '';
        $lang['Somali'] = '';
        $lang['Uzbek'] = '';
        $lang['Aragonese'] = 'an';
        $lang['Corsican'] = 'co';
        $lang['Kurmanji'] = '';
        $lang['Sicilian'] = '';//'scn';
        $lang['Karachay-Balkar'] = '';//'krc';
        $lang['Napoletano-calabrese'] = '';
        $lang['Aymara'] = 'ay';
        $lang['Sicilian'] = '';//'scn';
        $lang['Biellese'] = '';
        $lang['Piemontese'] = '';
        $lang['Kanuri'] = 'kr';
        $lang['Ossetian'] = 'os';
        $lang['Walloon'] = 'wa';
        $lang['Lombard'] = '';
        $lang['Delaware'] = '';//'del';
        $lang['Bolognese'] = '';
        $lang['Amharic'] = 'am';
        $lang['Mamasa'] = '';
        $lang['Bengali'] = 'bn';
        $lang['Tibetan'] = 'bo';
        $lang['Bosnian'] = 'bs';
        $lang['Napulitano'] = '';
        $lang['Paduan'] = '';
        $lang['Ligurian'] = '';
        $lang['Moksha'] = '';//'mdf';
        $lang['Flemish'] = 'nl';
        $lang['Quechua'] = 'qu';
        $lang['Tamil'] = 'ta';
        $lang['Tatar'] = 'tt';
        $lang['Venetian'] = '';
        $lang['Wolof'] = 'wo';
        $lang['Kalmyk'] = '';//'xal';
        $lang['Tamil'] = 'ta';
        $lang['Thai'] = 'th';
        $lang['Vietnamese'] = 'vi';
        $lang['Cebuano'] = '';//'ceb';
        $lang['Hawaiian'] = '';//'haw';
        $lang['Indonesian'] = 'id';
        $lang['Malay'] = 'ms';
        $lang['Palauan'] = '';//'pau';
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
        $lang['Ladin'] = '';//'lad';
        $lang['Marathi'] = 'mr';
        $lang['Punjabi'] = 'pa';
        $lang['Sanskrit'] = 'sa';
        $lang['Arabic'] = 'ar';
        $lang['Catalan (Balears)'] = 'ca';
        $lang['Armenian'] = 'hy';
        $lang['Georgian'] = 'ka';
        $lang['Asturian'] = '';//'ast';
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
        $lang['Sorbian, Upper'] = '';//'hsb';
        $lang['Sorbian, Lower'] = '';//'dsb';
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
        $lang['Ladino'] = '';//'lad';
        $lang['Latvian'] = 'lv';
        $lang['Macedonian'] = 'mk';
        $lang['Sotho, Northern'] = '';//'nso';
        $lang['Sotho, Southern'] = 'st';
        $lang['Romansh'] = 'rm';
        $lang['Scots'] = '';//'sco';
        $lang['Shona'] = 'sn';
        $lang['Serbian'] = 'sr';
        $lang['Swahili'] = 'sw';
        $lang['Tswana'] = 'tn';
        $lang['Tsonga'] = 'ts';
        $lang['Xhosa'] = 'xh';
        $lang['Zulu'] = 'zu';
        $lang['Spanish (Cuba)'] = 'es-cu';
        $lang['Khakas'] = '';//'kjh';
        $lang['Kwangali'] = '';//'kwn';
        $lang['Guadeloupean Creole French'] = '';//'gcf';
        $lang['Venetian'] = '';//'vec';
        $lang['Emiliano-romagnolo'] = '';//'eml';
        $lang['Siswant'] = 'ss';
        $lang['Araucanian'] = '';//'arn';
        $lang['Shor'] = '';//'cjs';
        $lang['Uzbek'] = 'uz';
        $lang['Paduan'] = '';//'vec';
        $lang['Luxembourgish'] = 'lb';
        $lang['Maldivian'] = 'dv';
        $lang['Malayalam'] = 'ml';
        $lang['Tahitian'] = 'ty';
        return $lang;
    }

}
?>
