<?php
namespace php_active_record;
/* connectors: [353, 354, 355]  */
define("AVIBASE_SOURCE_URL", "http://avibase.bsc-eoc.org/species.jsp?avibaseid=");
class AvibaseAPI
{
    public function __construct()
    {
        $this->ancestry = array("kingdom" => "Animalia", "phylum" => "Chordata", "class" => "Aves");
        $this->family_list = self::generate_family_list();
        $this->checklists = array("sibleymonroe", //Sibley &amp; Monroe 1996
                                  "howardmoore",  //Howard &amp; Moore 3rd edition (corrigenda 8)
                                  "clements5",    //Clements 5th edition (updated 2005)
                                  "clements",     //Clements 6th edition (updated 2011)
                                  "cinfo",        //Commission internationale pour les noms français d'oiseaux (CINFO 1993, rev. 2009)
                                  "ioc",          //IOC World Bird Names (2011)
                                  "ebird",        //eBird 1.05 (2010)
                                  "hbw",          //Handbooks of the Birds of the World
                                  "aou"           //American Ornithologist Union 7th edition (including 51st suppl.)
                                 );
        $this->TEMP_FILE_PATH = DOC_ROOT . "/update_resources/connectors/files/Avibase/";
        $this->languages = self::generate_language_list(); // for common names
    }

    function get_all_taxa($resource_id, $taxonomy)
    {
        $taxa = self::prepare_data($taxonomy);
        $taxa = self::get_names($taxa);
        $all_taxa = array();
        $i = 0;
        $total = count($taxa);
        foreach($taxa as $key => $value)
        {
            $i++; 
            $taxon_record["taxon"] = array( "sciname" => $key, 
                                            "family"  => $value["family"], 
                                            "kingdom" => $this->ancestry["kingdom"],
                                            "phylum"  => $this->ancestry["phylum"],
                                            "class"   => $this->ancestry["class"],
                                            "order"   => $this->family_list[$taxonomy][$value["family"]],
                                            "id"      => $value["id"]);
            $taxon_record["common_names"] = @$value['common_names'];
            $taxon_record["references"] = array();
            $taxon_record["synonyms"] = @$value['synonyms'];
            $taxon_record["dataobjects"] = array();
            $arr = self::get_avibase_taxa($taxon_record);
            $page_taxa = $arr[0];
            if($page_taxa) $all_taxa = array_merge($all_taxa, $page_taxa);
            unset($page_taxa);
        }
        $xml = \SchemaDocument::get_taxon_xml($all_taxa);
        $resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
        $OUT = fopen($resource_path, "w");
        fwrite($OUT, $xml);
        fclose($OUT);
    }

    function prepare_data($taxonomy, $for_testing = false)
    {
        if($taxonomy == "avibase") $checklists = $this->checklists;
        else                       $checklists = array($taxonomy);
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
        
        // $regions = array('nam'); //debug
        
        if($for_testing) $regions = array('cam');
        $service_url = 'http://avibase.bsc-eoc.org/checklist.jsp?';
        $taxa = array();
        foreach($checklists as $checklist)
        {
            foreach($regions as $region)
            {
                $url = $service_url . '&region=' . $region . '&list=' . $checklist;
                // print "\n $url";
                $taxa = self::get_taxa_from_html($url, $taxa, $taxonomy);
            }
        }
        // print"\n total: " . count($taxa) . "\n";
        return $taxa;
    }

    function get_taxa_from_html($url, $taxa, $taxonomy)
    {
        $names = array();
        $ids = array();
        $html = Functions::get_remote_file($url, DOWNLOAD_WAIT_TIME, 999999);
        $html = str_ireplace('<tr valign="bottom">', 'xxx<tr valign="bottom">', $html);
        $html = str_ireplace('<tr valign=bottom>', 'xxx<tr valign="bottom">', $html);
        
        // a way to get the last family block
        $html = str_ireplace('</tbody></table>', 'xxx</tbody></table>', $html);
        $html = str_ireplace('</table>', 'xxx</table>', $html);
        
        if(preg_match_all("/<tr valign=\"bottom\">(.*?)xxx/ims", $html, $matches))
        {
            $html_family_block = $matches[1];
            $i = 0;
            foreach($html_family_block as $block)
            {
                $family = self::get_family($block);
                /* If processing just a single taxonomy e.g. IOC World Bird Names (2011) - (ioc),
                   many taxonomies list the Order in the Family row e.g. GAVIIFORMES: Gaviidae. 
                   If that's the case use that Order and not the Order in the taxon detail page.
                   If processing for the entire Avibase taxa, always go to the taxon detail page to get the Order
                */
                $orig_family = $family;
                $order_family = explode(":", $family);
                $order = "";
                if($taxonomy == "avibase")
                {
                    if(count($order_family) == 2) $family = trim($order_family[1]); // e.g. GAVIIFORMES: Gaviidae
                    //to get Order, we need a taxon id
                    if(preg_match("/avibaseid=(.*?)\">/ims", $block, $matches)) $avibaseid = $matches[1];
                    if(@$this->family_list[$taxonomy][$family] == "") 
                    {
                        $order = self::get_order($avibaseid);
                        $this->family_list[$taxonomy][$family] = $order;
                        print "\naa[$taxonomy][$family] = [[$order]]";
                    }
                }
                else
                {
                    if(count($order_family) == 2) // e.g. GAVIIFORMES: Gaviidae
                    {
                        $order = ucfirst(strtolower($order_family[0]));
                        $family = trim($order_family[1]);
                        $this->family_list[$taxonomy][$family] = $order;
                    }
                    elseif(count($order_family) == 1)
                    {
                        //to get Order, we need a taxon id
                        if(preg_match("/avibaseid=(.*?)\">/ims", $block, $matches)) $avibaseid = $matches[1];
                        if(@$this->family_list[$taxonomy][$family] == "") 
                        {
                            $order = self::get_order($avibaseid);
                            print "\nbb [$family] = [[$order]]";
                            $this->family_list[$taxonomy][$family] = $order;
                        }
                    }
                }
                $taxa = self::get_taxa($taxa, $family, $block);
                $i++;
                //if($i == 2) break; //debug
            }
        }
        return $taxa;
    }

    function get_order($avibaseid)
    {
        $url = AVIBASE_SOURCE_URL . $avibaseid;
        $html = Functions::get_remote_file($url, DOWNLOAD_WAIT_TIME, 999999);
        /* <b>Order:</b><br> &nbsp;&nbsp;Passeriformes<br> */
        if(preg_match("/<b>Order:<\/b><br>(.*?)<br>/ims", $html, $matches)) 
        {
            $html = trim($matches[1]);
            return str_ireplace("&nbsp;", "", $html);
        }
        return "";
    }

    function get_family($html)
    {
        if(preg_match("/<b>(.*?)<\/b>/ims", $html, $matches)) return $matches[1];
        return "";
    }
    
    function get_taxa($taxa, $family, $html)
    {
        $names = array();
        $ids = array();
        if(preg_match_all("/<i>(.*?)<\/i>/ims", $html, $matches)) $names = $matches[1];
        if(preg_match_all("/avibaseid=(.*?)\">/ims", $html, $matches)) $ids = $matches[1];
        if(count($names) == count($ids))
        {
            $i = 0;
            foreach($names as $name)
            {
                $taxa[$name] = array("id" => $ids[$i], "family" => $family);
                $i++;
            }
        }
        return $taxa;
    }

    function get_names($taxa)
    {
        print "\n\nstart getting synonyms and common names...";
        $i = 0;
        $total = count($taxa);
        foreach($taxa as $taxon => $value)
        {
            $i++;
            //if(!in_array($taxon, array('Ortalis vetula', 'Ortalis ruficauda', 'Ortalis poliocephala'))) continue; //debug for synonyms
            //if(!in_array($taxon, array('Numida meleagris'))) continue; //debug for common names
            print "\n $i of $total $taxon";
            if(isset($taxa[$taxon]['synonyms'])) continue;
            $url = AVIBASE_SOURCE_URL . $value['id'];
            $html = Functions::get_remote_file($url, DOWNLOAD_WAIT_TIME, 999999);
            $taxa[$taxon]['common_names'] = self::scrape_common_names($html, $taxon);
            $taxa[$taxon]['synonyms'] = self::scrape_synonyms($html, $taxon);
        }
        return $taxa;
    }

    function scrape_common_names($html)
    {
        $common_names = array();
        if(preg_match("/<b>Other synonyms<\/b>(.*?)<\/table>/ims", $html, $match))
        {
            $html = $match[1] . "<br>";
            $html = str_ireplace("<br><b>" , "&rows[]=", $html);
            $rows = array();
            parse_str($html);
            foreach($rows as $rec)
            {
                $rec = strip_tags($rec);
                if($rec = explode(":", $rec))
                {
                    $lang = trim($rec[0]);
                    $names = explode(",", $rec[1]);
                    foreach($names as $name) 
                    {
                        if($lang != 'Latin') $common_names[] = array("name" => trim($name), "language" => $this->languages[$lang]);
                    }
                }
            }
            if(preg_match_all("/<br><b>(.*?)<br>/ims", $html, $matches)){}
        }
        return $common_names;
    }

    function scrape_synonyms($html, $taxon)
    {
        $synonyms = array();
        if(preg_match("/Latin\:(.*?)<br>/ims", $html, $match))
        {
            $html = trim(strip_tags(trim($match[1])));
            $names = explode(",", $html);
            foreach($names as $name)
            {
                $name = trim($name);
                if($taxon != $name) $synonyms[] = array("synonym" => $name, "relationship" => 'synonym');
            } 
        }
        return $synonyms;
    }

    public static function get_avibase_taxa($taxon_record)
    {
        $response = self::parse_xml($taxon_record);//this will output the raw (but structured) array
        $page_taxa = array();
        foreach($response as $rec)
        {
            $taxon = Functions::prepare_taxon_params($rec);
            if($taxon) $page_taxa[] = $taxon;
        }
        return array($page_taxa);
    }

    private function parse_xml($taxon_record)
    {
        $arr_data = array();
        $arr_data[] = array("identifier"   => $taxon_record['taxon']['id'],
                            "source"       => AVIBASE_SOURCE_URL . $taxon_record['taxon']['id'],
                            "kingdom"      => $taxon_record['taxon']['kingdom'],
                            "phylum"       => $taxon_record['taxon']['phylum'],
                            "class"        => $taxon_record['taxon']['class'],
                            "order"        => $taxon_record['taxon']['order'],
                            "family"       => $taxon_record['taxon']['family'],
                            "genus"        => "",
                            "sciname"      => $taxon_record['taxon']['sciname'],
                            "reference"    => array(),
                            "synonyms"     => $taxon_record['synonyms'],
                            "commonNames"  => $taxon_record['common_names'],
                            "data_objects" => array()
                           );
        return $arr_data;
    }
    
    function generate_language_list()
    {
        //http://www.loc.gov/standards/iso639-2/php/code_list.php
        //http://www.rssboard.org/rss-language-codes
        $lang = array();
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
        $lang['Mayan languages'] = 'myn';
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
        $lang['Friulian'] = 'fur';
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
        $lang['Romany'] = 'rom';
        $lang['Ojibwa'] = 'oj';
        $lang['Gaelic'] = 'gd';
        $lang['Galician'] = 'gl';
        $lang['Inuktitut'] = 'iu';
        $lang['Khakas'] = '';
        $lang['Kazakh'] = 'kk';
        $lang['Greenlandic'] = 'kl';
        $lang['Mongolian'] = 'mn';
        $lang['Tuvinian'] = 'tyv';
        $lang['Araucanian'] = '';
        $lang['Mapundungun'] = '';
        $lang['Azerbaijani'] = 'az';
        $lang['Valencian'] = 'ca';
        $lang['Greek (Cypriot)'] = 'el';
        $lang['Chinese (Taiwan)'] = 'zh-tw';
        $lang['Kirghiz'] = 'ky';
        $lang['Spanish (Bolivia)'] = 'es-bo';
        $lang['Modenese'] = '';
        $lang['Creoles and Pidgins, French-based (Other)'] = 'cpf';
        $lang['Karelian'] = 'krl';
        $lang['Maori'] = 'mi';
        $lang['Rennell'] = '';
        $lang['Rotokas'] = '';
        $lang['Naasioi'] = '';
        $lang['Tai'] = 'tai';
        $lang['Aymara'] = 'ay';
        $lang['Reggiano'] = '';
        $lang['Somali'] = '';
        $lang['Uzbek'] = '';
        $lang['Aragonese'] = 'an';
        $lang['Corsican'] = 'co';
        $lang['Kurmanji'] = '';
        $lang['Sicilian'] = 'scn';
        $lang['Karachay-Balkar'] = 'krc';
        $lang['Napoletano-calabrese'] = '';
        $lang['Aymara'] = 'ay';
        $lang['Sicilian'] = 'scn';
        $lang['Biellese'] = '';
        $lang['Piemontese'] = '';
        $lang['Kanuri'] = 'kr';
        $lang['Ossetian'] = 'os';
        $lang['Walloon'] = 'wa';
        $lang['Lombard'] = '';
        $lang['Delaware'] = 'del';
        $lang['Bolognese'] = '';
        $lang['Amharic'] = 'am';
        $lang['Mamasa'] = '';
        $lang['Bengali'] = 'bn';
        $lang['Tibetan'] = 'bo';
        $lang['Bosnian'] = 'bs';
        $lang['Napulitano'] = '';
        $lang['Paduan'] = '';
        $lang['Ligurian'] = '';
        $lang['Moksha'] = 'mdf';
        $lang['Flemish'] = 'nl';
        $lang['Quechua'] = 'qu';
        $lang['Tamil'] = 'ta';
        $lang['Tatar'] = 'tt';
        $lang['Venetian'] = '';
        $lang['Wolof'] = 'wo';
        $lang['Kalmyk'] = 'xal';
        $lang['Tamil'] = 'ta';
        $lang['Thai'] = 'th';
        $lang['Vietnamese'] = 'vi';
        $lang['Cebuano'] = 'ceb';
        $lang['Hawaiian'] = 'haw';
        $lang['Indonesian'] = 'id';
        $lang['Malay'] = 'ms';
        $lang['Palauan'] = 'pau';
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
        $lang['Ladin'] = 'lad';
        $lang['Marathi'] = 'mr';
        $lang['Punjabi'] = 'pa';
        $lang['Sanskrit'] = 'sa';
        $lang['Arabic'] = 'ar';
        $lang['Catalan (Balears)'] = 'ca';
        $lang['Armenian'] = 'hy';
        $lang['Georgian'] = 'ka';
        $lang['Asturian'] = 'ast';
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
        $lang['Sorbian, Upper'] = 'hsb';
        $lang['Sorbian, Lower'] = 'dsb';
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
        $lang['Ladino'] = 'lad';
        $lang['Latvian'] = 'lv';
        $lang['Macedonian'] = 'mk';
        $lang['Sotho, Northern'] = 'nso';
        $lang['Sotho, Southern'] = 'st';
        $lang['Romansh'] = 'rm';
        $lang['Scots'] = 'sco';
        $lang['Shona'] = 'sn';
        $lang['Serbian'] = 'sr';
        $lang['Swahili'] = 'sw';
        $lang['Tswana'] = 'tn';
        $lang['Tsonga'] = 'ts';
        $lang['Xhosa'] = 'xh';
        $lang['Zulu'] = 'zu';
        return $lang;
    }

    function generate_family_list()
    {
        // as of 20-Feb-2012
        $family["avibase"]["Dendrocygnidae"] = "Anseriformes";
        $family["avibase"]["Cerylidae"] = "Coraciiformes";
        $family["avibase"]["Coccyzidae"] = "Cuculiformes";
        $family["avibase"]["Crotophagidae"] = "Cuculiformes";
        $family["avibase"]["Neomorphidae"] = "Cuculiformes";
        $family["avibase"]["Cacatuidae"] = "Psittaciformes";
        $family["avibase"]["Pteroclidae"] = "Pteroclidiformes";
        $family["avibase"]["Incertae sedis (sapayoa)"] = "Passeriformes";
        $family["avibase"]["Pluvianellidae"] = "Charadriiformes";
        $family["avibase"]["Priniidae"] = "Passeriformes";
        $family["avibase"]["Halcyonidae"] = "Coraciiformes";
        $family["avibase"]["Hypocoliidae"] = "Passeriformes";
        $family["avibase"]["Lybiidae"] = "Piciformes";
        $family["avibase"]["Rhinopomastidae"] = "Upupiformes";
        $family["avibase"]["Centropodidae"] = "Cuculiformes";
        $family["avibase"]["Sagittariidae"] = "Falconiformes";
        $family["avibase"]["Megalaimidae"] = "Piciformes";
        $family["avibase"]["Nyctyornithidae"] = "Coraciiformes";
        $family["avibase"]["Batrachostomidae"] = "Caprimulgiformes";
        $family["avibase"]["Eurostopodidae"] = "Caprimulgiformes";
        $family["avibase"]["Melanocharitidae"] = "Passeriformes";
        $family["avibase"]["Turnagridae"] = "Passeriformes";
        $family["avibase"]["Callaeatidae"] = "Passeriformes";
        $family["avibase"]["Pandionidae"] = "Falconiformes";
        $family["avibase"]["Meleagrididae"] = "Galliformes";
        $family["avibase"]["Tetraonidae"] = "Galliformes";
        $family["avibase"]["Sternidae"] = "Charadriiformes";
        $family["avibase"]["Rynchopidae"] = "Charadriiformes";
        $family["avibase"]["Capitonidae"] = "Piciformes";
        $family["avibase"]["Ptilogonatidae"] = "Passeriformes";
        $family["avibase"]["Drepanididae"] = "Passeriformes";
        $family["avibase"]["Chionididae"] = "Charadriiformes";
        $family["avibase"]["Paradoxornithidae"] = "Passeriformes";
        $family["avibase"]["Tichodromidae"] = "Passeriformes";
        $family["avibase"]["Balaenicipididae"] = "Ciconiiformes";
        $family["avibase"]["Leptosomatidae"] = "Coraciiformes";
        $family["avibase"]["Prionopidae"] = "Passeriformes";
        $family["avibase"]["Grallinidae"] = "Passeriformes";
        $family["avibase"]["Epthianuridae"] = "Passeriformes";
        $family["avibase"]["Semnornithidae"] = "Piciformes";
        $family["avibase"]["Sapayoidae"] = "Passeriformes";
        $family["avibase"]["Grallariidae"] = "Passeriformes";
        $family["avibase"]["Oxyruncidae"] = "Passeriformes";
        $family["avibase"]["Tityridae"] = "Passeriformes";
        $family["avibase"]["Cettiidae"] = "Passeriformes";
        $family["avibase"]["Phylloscopidae"] = "Passeriformes";
        $family["avibase"]["Acrocephalidae"] = "Passeriformes";
        $family["avibase"]["Locustellidae"] = "Passeriformes";
        $family["avibase"]["Donacobiidae"] = "Passeriformes";
        $family["avibase"]["Leiothrichidae"] = "Passeriformes";
        $family["avibase"]["Mohoidae"] = "Passeriformes";
        $family["avibase"]["Calcariidae"] = "Passeriformes";
        $family["avibase"]["Melanopareiidae"] = "Passeriformes";
        $family["avibase"]["Panuridae"] = "Passeriformes";
        $family["avibase"]["Calyptomenidae"] = "Passeriformes";
        $family["avibase"]["Chaetopidae"] = "Passeriformes";
        $family["avibase"]["Nicatoridae"] = "Passeriformes";
        $family["avibase"]["Stenostiridae"] = "Passeriformes";
        $family["avibase"]["Macrosphenidae"] = "Passeriformes";
        $family["avibase"]["Bernieridae"] = "Passeriformes";
        $family["avibase"]["Pellorneidae"] = "Passeriformes";
        $family["avibase"]["Hyliotidae"] = "Passeriformes";
        $family["avibase"]["Buphagidae"] = "Passeriformes";
        $family["avibase"]["Psophodidae"] = "Passeriformes";
        $family["avibase"]["Pnoepygidae"] = "Passeriformes";
        $family["avibase"]["Urocynchramidae"] = "Passeriformes";
        $family["avibase"]["Hylocitreidae"] = "Passeriformes";
        $family["avibase"]["Strigopidae"] = "Psittaciformes";
        $family["avibase"]["Notiomystidae"] = "Passeriformes";
        $family["avibase"]["Incertae Sedis"] = "Passeriformes";
        $family["avibase"]["Megapodidae"] = "Galliformes";
        $family["avibase"]["Pluvianidae"] = "Charadriiformes";
        $family["avibase"]["Sarothruridae"] = "Gruiformes";
        $family["avibase"]["Tephrodornithidae"] = "Passeriformes";
        $family["avibase"]["Struthideidae"] = "Passeriformes";
        $family["avibase"]["Pandioninae"] = "Falconiformes";
        $family["avibase"]["Megaluridae"] = "Passeriformes";
        $family["avibase"]["Incertae sedis"] = "Passeriformes";
        
        $family["avibase"]["Rheidae"] = "Rheiformes";
        $family["avibase"]["Anhimidae"] = "Anseriformes";
        $family["avibase"]["Pelecanoididae"] = "Procellariiformes";
        $family["avibase"]["Cariamidae"] = "Gruiformes";
        $family["avibase"]["Psophiidae"] = "Gruiformes";
        $family["avibase"]["Chionidae"] = "Charadriiformes";
        $family["avibase"]["Rostratulidae"] = "Charadriiformes";
        $family["avibase"]["Thinocoridae"] = "Charadriiformes";
        $family["avibase"]["Opisthocomidae"] = "Cuculiformes";
        $family["avibase"]["Conopophagidae"] = "Passeriformes";
        $family["avibase"]["Cisticolidae"] = "Passeriformes";
        $family["avibase"]["Otididae"] = "Gruiformes";
        $family["avibase"]["Turnicidae"] = "Turniciformes";
        $family["avibase"]["Coraciidae"] = "Coraciiformes";
        $family["avibase"]["Meropidae"] = "Coraciiformes";
        $family["avibase"]["Malaconotidae"] = "Passeriformes";
        $family["avibase"]["Oriolidae"] = "Passeriformes";
        $family["avibase"]["Struthionidae"] = "Struthioniformes";
        $family["avibase"]["Scopidae"] = "Ciconiiformes";
        $family["avibase"]["Balaenicipitidae"] = "Ciconiiformes";
        $family["avibase"]["Mesitornithidae"] = "Gruiformes";
        $family["avibase"]["Dromadidae"] = "Charadriiformes";
        $family["avibase"]["Ibidorhynchidae"] = "Charadriiformes";
        $family["avibase"]["Raphidae"] = "Columbiformes";
        $family["avibase"]["Musophagidae"] = "Musophagiformes";
        $family["avibase"]["Coliidae"] = "Coliiformes";
        $family["avibase"]["Brachypteraciidae"] = "Coraciiformes";
        $family["avibase"]["Leptosomidae"] = "Coraciiformes";
        $family["avibase"]["Phoeniculidae"] = "Upupiformes";
        $family["avibase"]["Bucerotidae"] = "Bucerotiformes";
        $family["avibase"]["Bucorvidae"] = "Bucerotiformes";
        $family["avibase"]["Indicatoridae"] = "Piciformes";
        $family["avibase"]["Eurylaimidae"] = "Passeriformes";
        $family["avibase"]["Philepittidae"] = "Passeriformes";
        $family["avibase"]["Pittidae"] = "Passeriformes";
        $family["avibase"]["Platysteiridae"] = "Passeriformes";
        $family["avibase"]["Vangidae"] = "Passeriformes";
        $family["avibase"]["Campephagidae"] = "Passeriformes";
        $family["avibase"]["Dicruridae"] = "Passeriformes";
        $family["avibase"]["Picathartidae"] = "Passeriformes";
        $family["avibase"]["Genera Incertae Sedis"] = "Passeriformes";
        $family["avibase"]["Nectariniidae"] = "Passeriformes";
        $family["avibase"]["Promeropidae"] = "Passeriformes";
        $family["avibase"]["Casuariidae"] = "Casuariiformes";
        $family["avibase"]["Megapodiidae"] = "Galliformes";
        $family["avibase"]["Podargidae"] = "Caprimulgiformes";
        $family["avibase"]["Aegothelidae"] = "Caprimulgiformes";
        $family["avibase"]["Hemiprocnidae"] = "Apodiformes";
        $family["avibase"]["Ptilonorhynchidae"] = "Passeriformes";
        $family["avibase"]["Climacteridae"] = "Passeriformes";
        $family["avibase"]["Maluridae"] = "Passeriformes";
        $family["avibase"]["Acanthizidae"] = "Passeriformes";
        $family["avibase"]["Pomatostomidae"] = "Passeriformes";
        $family["avibase"]["Orthonychidae"] = "Passeriformes";
        $family["avibase"]["Cnemophilidae"] = "Passeriformes";
        $family["avibase"]["Paramythiidae"] = "Passeriformes";
        $family["avibase"]["Eupetidae"] = "Passeriformes";
        $family["avibase"]["Cinclosomatidae"] = "Passeriformes";
        $family["avibase"]["Genera Incertae sedis"] = "Passeriformes";
        $family["avibase"]["Machaerirhynchidae"] = "Passeriformes";
        $family["avibase"]["Cracticidae"] = "Passeriformes";
        $family["avibase"]["Artamidae"] = "Passeriformes";
        $family["avibase"]["Aegithinidae"] = "Passeriformes";
        $family["avibase"]["Pityriaseidae"] = "Passeriformes";
        $family["avibase"]["Neosittidae"] = "Passeriformes";
        $family["avibase"]["Falcunculidae"] = "Passeriformes";
        $family["avibase"]["Pachycephalidae"] = "Passeriformes";
        $family["avibase"]["Colluricinclidae"] = "Passeriformes";
        $family["avibase"]["Rhipiduridae"] = "Passeriformes";
        $family["avibase"]["Paradisaeidae"] = "Passeriformes";
        $family["avibase"]["Petroicidae"] = "Passeriformes";
        $family["avibase"]["Irenidae"] = "Passeriformes";
        $family["avibase"]["Rhabdornithidae"] = "Passeriformes";
        $family["avibase"]["Chloropseidae"] = "Passeriformes";
        $family["avibase"]["Dicaeidae"] = "Passeriformes";
        $family["avibase"]["Dromaiidae"] = "Casuariiformes";
        $family["avibase"]["Apterygidae"] = "Apterygiformes";
        $family["avibase"]["Anseranatidae"] = "Anseriformes";
        $family["avibase"]["Rhynochetidae"] = "Gruiformes";
        $family["avibase"]["Pedionomidae"] = "Charadriiformes";
        $family["avibase"]["Acanthisittidae"] = "Passeriformes";
        $family["avibase"]["Menuridae"] = "Passeriformes";
        $family["avibase"]["Atrichornithidae"] = "Passeriformes";
        $family["avibase"]["Dasyornithidae"] = "Passeriformes";
        $family["avibase"]["Pardalotidae"] = "Passeriformes";
        $family["avibase"]["Callaeidae"] = "Passeriformes";
        $family["avibase"]["Corcoracidae"] = "Passeriformes";
        
        $family["avibase"]["Glareolidae"] = "Charadriiformes";
        $family["avibase"]["Pteroclididae"] = "Pteroclidiformes";
        $family["avibase"]["Todidae"] = "Coraciiformes";
        $family["avibase"]["Upupidae"] = "Upupiformes";
        $family["avibase"]["Meliphagidae"] = "Passeriformes";
        $family["avibase"]["Monarchidae"] = "Passeriformes";
        $family["avibase"]["Dulidae"] = "Passeriformes";
        $family["avibase"]["Pycnonotidae"] = "Passeriformes";
        $family["avibase"]["Zosteropidae"] = "Passeriformes";
        $family["avibase"]["Ploceidae"] = "Passeriformes";
        $family["avibase"]["Viduidae"] = "Passeriformes";
        $family["avibase"]["Prunellidae"] = "Passeriformes";
        
        $family["avibase"]["Tinamidae"] = "Tinamiformes";
        $family["avibase"]["Cracidae"] = "Galliformes";
        $family["avibase"]["Numididae"] = "Galliformes";
        $family["avibase"]["Odontophoridae"] = "Galliformes";
        $family["avibase"]["Phasianidae"] = "Galliformes";
        $family["avibase"]["Anatidae"] = "Anseriformes";
        $family["avibase"]["Spheniscidae"] = "Sphenisciformes";
        $family["avibase"]["Gaviidae"] = "Gaviiformes";
        $family["avibase"]["Diomedeidae"] = "Procellariiformes";
        $family["avibase"]["Procellariidae"] = "Procellariiformes";
        $family["avibase"]["Hydrobatidae"] = "Procellariiformes";
        $family["avibase"]["Podicipedidae"] = "Podicipediformes";
        $family["avibase"]["Phoenicopteridae"] = "Phoenicopteriformes";
        $family["avibase"]["Ciconiidae"] = "Ciconiiformes";
        $family["avibase"]["Threskiornithidae"] = "Ciconiiformes";
        $family["avibase"]["Ardeidae"] = "Ciconiiformes";
        $family["avibase"]["Phaethontidae"] = "Pelecaniformes";
        $family["avibase"]["Fregatidae"] = "Pelecaniformes";
        $family["avibase"]["Pelecanidae"] = "Pelecaniformes";
        $family["avibase"]["Sulidae"] = "Pelecaniformes";
        $family["avibase"]["Phalacrocoracidae"] = "Pelecaniformes";
        $family["avibase"]["Anhingidae"] = "Pelecaniformes";
        $family["avibase"]["Cathartidae"] = "Ciconiiformes";
        $family["avibase"]["Falconidae"] = "Falconiformes";
        $family["avibase"]["Accipitridae"] = "Falconiformes";
        $family["avibase"]["Eurypygidae"] = "Gruiformes";
        $family["avibase"]["Rallidae"] = "Gruiformes";
        $family["avibase"]["Heliornithidae"] = "Gruiformes";
        $family["avibase"]["Gruidae"] = "Gruiformes";
        $family["avibase"]["Aramidae"] = "Gruiformes";
        $family["avibase"]["Burhinidae"] = "Charadriiformes";
        $family["avibase"]["Haematopodidae"] = "Charadriiformes";
        $family["avibase"]["Recurvirostridae"] = "Charadriiformes";
        $family["avibase"]["Charadriidae"] = "Charadriiformes";
        $family["avibase"]["Jacanidae"] = "Charadriiformes";
        $family["avibase"]["Scolopacidae"] = "Charadriiformes";
        $family["avibase"]["Laridae"] = "Charadriiformes";
        $family["avibase"]["Stercorariidae"] = "Charadriiformes";
        $family["avibase"]["Alcidae"] = "Charadriiformes";
        $family["avibase"]["Columbidae"] = "Columbiformes";
        $family["avibase"]["Psittacidae"] = "Psittaciformes";
        $family["avibase"]["Cuculidae"] = "Cuculiformes";
        $family["avibase"]["Tytonidae"] = "Strigiformes";
        $family["avibase"]["Strigidae"] = "Strigiformes";
        $family["avibase"]["Steatornithidae"] = "Caprimulgiformes";
        $family["avibase"]["Nyctibiidae"] = "Caprimulgiformes";
        $family["avibase"]["Caprimulgidae"] = "Caprimulgiformes";
        $family["avibase"]["Apodidae"] = "Apodiformes";
        $family["avibase"]["Trochilidae"] = "Apodiformes";
        $family["avibase"]["Trogonidae"] = "Trogoniformes";
        $family["avibase"]["Alcedinidae"] = "Coraciiformes";
        $family["avibase"]["Momotidae"] = "Coraciiformes";
        $family["avibase"]["Ramphastidae"] = "Piciformes";
        $family["avibase"]["Picidae"] = "Piciformes";
        $family["avibase"]["Galbulidae"] = "Piciformes";
        $family["avibase"]["Bucconidae"] = "Piciformes";
        $family["avibase"]["Sapayoaidae"] = "Passeriformes";
        $family["avibase"]["Pipridae"] = "Passeriformes";
        $family["avibase"]["Cotingidae"] = "Passeriformes";
        $family["avibase"]["Genera Incertae Sedis "] = "Passeriformes";
        $family["avibase"]["Tyrannidae"] = "Passeriformes";
        $family["avibase"]["Thamnophilidae"] = "Passeriformes";
        $family["avibase"]["Rhinocryptidae"] = "Passeriformes";
        $family["avibase"]["Formicariidae"] = "Passeriformes";
        $family["avibase"]["Furnariidae"] = "Passeriformes";
        $family["avibase"]["Dendrocolaptidae"] = "Passeriformes";
        $family["avibase"]["Laniidae"] = "Passeriformes";
        $family["avibase"]["Vireonidae"] = "Passeriformes";
        $family["avibase"]["Corvidae"] = "Passeriformes";
        $family["avibase"]["Bombycillidae"] = "Passeriformes";
        $family["avibase"]["Paridae"] = "Passeriformes";
        $family["avibase"]["Remizidae"] = "Passeriformes";
        $family["avibase"]["Hirundinidae"] = "Passeriformes";
        $family["avibase"]["Aegithalidae"] = "Passeriformes";
        $family["avibase"]["Alaudidae"] = "Passeriformes";
        $family["avibase"]["Sylviidae"] = "Passeriformes";
        $family["avibase"]["Timaliidae"] = "Passeriformes";
        $family["avibase"]["Regulidae"] = "Passeriformes";
        $family["avibase"]["Troglodytidae"] = "Passeriformes";
        $family["avibase"]["Genus Incertae Sedis "] = "Passeriformes";
        $family["avibase"]["Polioptilidae"] = "Passeriformes";
        $family["avibase"]["Sittidae"] = "Passeriformes";
        $family["avibase"]["Certhiidae"] = "Passeriformes";
        $family["avibase"]["Mimidae"] = "Passeriformes";
        $family["avibase"]["Sturnidae"] = "Passeriformes";
        $family["avibase"]["Turdidae"] = "Passeriformes";
        $family["avibase"]["Muscicapidae"] = "Passeriformes";
        $family["avibase"]["Cinclidae"] = "Passeriformes";
        $family["avibase"]["Passeridae"] = "Passeriformes";
        $family["avibase"]["Estrildidae"] = "Passeriformes";
        $family["avibase"]["Peucedramidae"] = "Passeriformes";
        $family["avibase"]["Motacillidae"] = "Passeriformes";
        $family["avibase"]["Fringillidae"] = "Passeriformes";
        $family["avibase"]["Parulidae"] = "Passeriformes";
        $family["avibase"]["Icteridae"] = "Passeriformes";
        $family["avibase"]["Coerebidae"] = "Passeriformes";
        $family["avibase"]["Emberizidae"] = "Passeriformes";
        $family["avibase"]["Thraupidae"] = "Passeriformes";
        $family["avibase"]["Cardinalidae"] = "Passeriformes";
        return $family;
    }

}
?>