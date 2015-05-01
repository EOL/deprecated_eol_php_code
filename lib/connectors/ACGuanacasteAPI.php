<?php
namespace php_active_record;
/* connector: [acg] */
class ACGuanacasteAPI
{
    public function __construct($folder)
    {
        $this->acg_domain = "http://www.acguanacaste.ac.cr";
        $this->acg_biodiversity = $this->acg_domain . "/biodiversidad/";
        $this->acg_gallery_page = $this->acg_biodiversity . "wsBiodiversidadACG.php?w=getVouchers&fInicio=0&fFinal=9999";
        $this->download_options = array('download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1);
        // $this->download_options['expire_seconds'] = false; //debug

        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->taxa_ids   = array();
        $this->object_ids = array();
        $this->agent_ids  = array();
    }

    function get_all_taxa($resource_id)
    {
        $families = self::get_families_list(); //using a web-service
        self::prepare_images($families);
        
        $taxa = self::get_taxa_list(); //screen grab
        self::prepare_texts($taxa);
        $this->archive_builder->finalize(true);
    }
    
    private function get_families_list()
    {
        $families = array();
        if($html = Functions::lookup_with_cache($this->acg_biodiversity, $this->download_options))
        {
            if(preg_match("/<select class=\"form-control\" name=\"familia\" id=\"familia\">(.*?)<\/select>/ims", $html, $match))
            {
                if(preg_match_all("/<option value=\"(.*?)\"/ims", $match[1], $matches))
                {
                    array_shift($matches[1]); // removes first value in array
                    foreach($matches[1] as $temp) $families[] = explode(":", $temp);
                }
            }
        }
        return $families;
    }
    
    private function prepare_images($families)
    {
        $urls = array();
        foreach($families as $f)
        {
            $url = $this->acg_gallery_page;
            if($family = $f[0]) $url .= "&familia=$family";
            if($subfamily = $f[1]) $url .= "&subfamilia=$subfamily";
            $urls[] = $url;
        }
        foreach($urls as $url)
        {
            if($html = Functions::lookup_with_cache($url, $this->download_options))
            {
                $records = json_decode($html);
                echo "\n [$url] " . count($records->responseResult);
                self::create_taxa($records->responseResult);
                self::create_image_objects($records->responseResult);
            }
        }
    }

    private function get_taxa_list()
    {
        $list = array();
        if($html = Functions::lookup_with_cache("http://www.acguanacaste.ac.cr/paginas-de-especies-por-especie", $this->download_options))
        {
            $html = str_replace("Rhuda difficilis", "<i>Rhuda difficilis", $html);
            $html = str_replace("\t", "", $html);
            if(preg_match_all("/<tr class=\"cat-list-row(.*?)<\/tr>/ims", $html, $matches))
            {
                foreach($matches[1] as $match)
                {
                    $rec = array();
                    if(preg_match("/href=\"(.*?)\"/ims", $match, $m))   $rec['href'] = $m[1];
                    if(preg_match("/<i>(.*?)\(/ims", $match, $m))       $rec['sciname'] = strip_tags($m[1]);
                    if(preg_match("/\((.*?)\)/ims", $match, $m))        $rec['family'] = strip_tags($m[1]);
                    elseif(preg_match("/\((.*?)</ims", $match, $m))     $rec['family'] = strip_tags($m[1]);
                    if(preg_match("/mod-articles-category-writtenby\">(.*?)<\/td>/ims", $match, $m)) $rec['author'] = trim($m[1]);
                    if($rec) $list[] = $rec;
                }
            }
        }
        array_shift($list); //removing Adultos Adelpha
        return $list;
    }
    
    private function prepare_texts($taxa)
    {
        $k = 0;
        foreach($taxa as $taxon)
        {
            $k++;
            if(($k % 10) == 0) echo "\n $k - ";
            $taxon = array_map('trim', $taxon);

            // if($taxon['sciname'] == "Liomys salvini") continue; //not insect, will process independently
            // $taxon['href'] = "/paginas-de-especies/insectos/104-nymphalidae/670-i-caligo-illioneus-i-nymphalidae"; //debug

            //create taxon
            $taxon['sciname'] = self::clean_name($taxon['sciname']);
            $taxon['taxon_id'] = strtolower(str_replace(" ", "_", $taxon['sciname']));
            $t = new \eol_schema\Taxon();
            $t->taxonID         = $taxon['taxon_id'];
            $t->scientificName  = $taxon['sciname'];
            $t->family          = $taxon['family'];
            
            if(!@$taxon['family'])
            {
                print_r($taxon); exit;
            }
            
            $t->furtherInformationURL = $this->acg_domain . $taxon['href'];
            if(!isset($this->taxa_ids[$t->taxonID]))
            {
                $this->taxa_ids[$t->taxonID] = '';
                $this->archive_builder->write_object_to_file($t);
            }

            // start prepare objects - image, text
            if($html = Functions::lookup_with_cache($this->acg_domain . $taxon['href'], $this->download_options))
            {
                $html = str_ireplace(array(" ", "&nbsp;", ' colspan="2"', ' rowspan="1"', ' style="margin-bottom: 0in;"', ' lang="es-CR"', ' style="text-align: center;"', ' style="line-height: 1.3em;"'), "", $html);
                $html = str_ireplace("Fig..", "Fig.", $html);
                if(preg_match("/<h1 class=\"titulo-articulo nombreCientifico\">(.*?)<a class=\"top\" href=\"#arriba\">/ims", $html, $match))
                {
                    $str = strip_tags($match[1], "<p><td><tr><table><i><img>");
                    $str = str_ireplace(' min-height: 14px;', '', $str);
                    $str = str_ireplace(' style="margin: 0px; line-height: normal; font-family: Helvetica;"', '', $str);
                    
                    // e.g. $i == 10
                    $str = str_ireplace(' class="p1"', '', $str);
                    $str = str_ireplace(' style="text-align: justify;"', '', $str);
                    
                    //e.g. Aellopos ceculus (Sphingidae)
                    $str = str_ireplace(' style="font: normal normal normal 12px/normal Helvetica; margin: 0px;"', '', $str);
                    
                    //e.g. Calydna sturnula (Riodinidae)
                    $str = str_ireplace(" 'Helvetica Neue'; margin: 0px;", '', $str);
                    $str = str_ireplace(' style="font: normal normal normal 12px/normal"', '', $str);
                    $str = str_ireplace(' style="font: normal normal normal 18px/normal"', '', $str);

                    if(preg_match_all("/<p>(.*?)<\/p>/ims", $str, $match2))
                    {
                        $total_txt = $match2[1]; $i = 0;
                        foreach($total_txt as $r)
                        {
                            if(is_numeric(stripos($r, "src="))) $total_txt[$i] = "";
                            $i++;
                        }
                        if(count($total_txt) < 4 ) echo "\ninvestigate txt: $this->acg_domain" . "$taxon[href] \n";
                        $total_txt = array_map('trim', $total_txt);
                        $total_txt = array_filter($total_txt);
                        $total_txt = array_values($total_txt);

                        //remove <p> in each array value
                        $i = 0;
                        foreach($total_txt as $t)
                        {
                            $total_txt[$i] = str_ireplace("<p>", "", $t);
                            $i++;
                        }
                    }

                    $total_img = array();
                    if(preg_match_all("/<td><img(.*?)<\/td>/ims", $str, $match2))   $total_img = $match2[1];
                    if(preg_match_all("/<p><img(.*?)<\/p>/ims", $str, $match2))     $total_img = array_merge($total_img, $match2[1]);
                    if(count($total_img) < 4 ) echo "\ninvestigate img: $this->acg_domain" . "$taxon[href] \n";

                    $caption_src = array();
                    $final_images = array();
                    foreach($total_img as $img)
                    {
                        $src = false;
                        if(preg_match("/src=\"(.*?)\"/ims", $img, $match2)) $src = $this->acg_domain . trim(str_ireplace("miniaturas/peq_", "", $match2[1]));
                        if(preg_match("/\/>(.*?)\(Click en la imágen para expandir\)/ims", $img, $match2)) $caption = Functions::remove_whitespace($match2[1]);
                        
                        // manual adjustment
                        $caption = self::adjust_caption($caption);
                        if(is_numeric(stripos($src, "http://www.acguanacaste.ac.crdata:image/png"))) continue;
                        
                        if($src)
                        {
                            $final_images[] = array("src" => $src, "caption" => $caption);
                            // assign Figure # with src
                            if(preg_match("/Figura (.*?)\./ims", $caption, $match2))   $caption_src[self::get_number_only($match2[1])] = $src;
                            elseif(preg_match("/Fig.(.*?)\./ims", $caption, $match2))  $caption_src[self::get_number_only($match2[1])] = $src;
                            elseif(preg_match("/Fig(.*?)\./ims", $caption, $match2))   $caption_src[self::get_number_only($match2[1])] = $src;
                            elseif(preg_match("/ﬁgura (.*?)\./ims", $caption, $match2))   $caption_src[self::get_number_only($match2[1])] = $src;
                            /* debug
                            if($src == "http://www.acguanacaste.ac.cr/images/species-home-page/Xylophanes-porcus/12-SRNP-43295-DHJ707153.JPG")
                            {
                                echo "\n" . self::get_number_only($match2[1]);
                                echo "\n $caption";
                                echo "\n $src"; exit;
                            }
                            */
                        }
                    }
                    
                    if($final_images) self::create_image_text_objects($final_images, $taxon, "image");
                    
                    // start adding image links at the end of text objects
                    $i = 0;
                    foreach($total_txt as $txt)
                    {
                        $total_txt[$i] = self::add_image_links($txt, $caption_src);
                        $i++;
                    }
                    if($total_txt)
                    {
                        $total_txt = array_map('trim', $total_txt);
                        $desc = Functions::remove_whitespace(implode("<p>", $total_txt));
                        self::create_image_text_objects(array(0 => $desc), $taxon, "text");
                    }
                    
                }
                else echo("\ninvestigate: no articulo sphomep \n");
            }
            else echo "\ninvestigate: page not found \n";
        }
    }

    private function adjust_caption($caption) // to have the correct index key
    {
        for($i=1; $i <= 20; $i++)
        {
            $caption = str_ireplace("Figura. $i ", "Figura $i.", $caption); // e.g. http://www.acguanacaste.ac.cr/paginas-de-especies-por-familias/104-insectos/nymphalidae/283-dinia-martinez
            $caption = str_ireplace("Fig.$i ", "Fig. $i.", $caption); // e.g. http://www.acguanacaste.ac.cr/paginas-de-especies-por-familias/104-insectos/nymphalidae/352-i-adelpha-barnesia-leucas-i-nymphalidae
            $caption = str_ireplace("Fig.$i,", "Fig. $i.", $caption); // e.g. http://www.acguanacaste.ac.cr/paginas-de-especies-por-familias/103-insectos/hesperiidae/289-i-pyrrhopyge-crida-i-hesperiidae
            $caption = str_ireplace("Fig$i.", "Fig. $i.", $caption); // e.g. http://www.acguanacaste.ac.cr/paginas-de-especies-por-familias/103-insectos/hesperiidae/289-i-pyrrhopyge-crida-i-hesperiidae
        }
        return $caption;
    }
    
    private function get_number_only($string)
    {
        if(preg_match("|\d+|", $string, $m)) return $m[0]; // get only the numeric
    }
    
    private function get_figure_nos($string)
    {
        $final = array();
        if(preg_match_all("/\(fig(.*?)\)/ims", $string, $matches)) 
        {
            foreach($matches[1] as $match)
            {
                // separators are either ',' or 'y' or 'and'
                $arr = explode(",", $match);
                $arr = array_merge($arr, explode("y", $match));
                $arr = array_merge($arr, explode("and", $match));
                $arr = array_merge($arr, explode("-", $match));
                foreach($arr as $r)
                {
                    $r = str_ireplace(array(" ", "."), "", $r); 
                    if(preg_match("|\d+|", $r, $m)) $final[] = $m[0]; // get only the numeric
                }
            }
        }
        $final = array_unique($final);
        asort($final);
        $final = array_values($final);
        return $final;
    }
    
    private function add_image_links($txt, $caption_src)
    {
        $txt = trim($txt);
        if(substr($txt, strlen($txt)-1,1) != ".") $txt .= ".";
        
        $string = "";
        $nos = self::get_figure_nos($txt);
        foreach($nos as $no)
        {
            if(isset($caption_src[$no]))
            {
                if($string) $string .= ", <a href='" . $caption_src[$no] . "'>(Fig. $no)</a>";
                else $string .= " See <a href='" . $caption_src[$no] . "'>(Fig. $no)</a>";
            } 
        }
        if($string) $string .= ".";
        return $txt . $string;
    }
    
    private function create_image_text_objects($records, $taxon, $obj_type)
    {
        if($obj_type == "image")
        {
            if($val = @$records[0]['caption']) //caption of first image
            {
                foreach(array("forest ", "lugar ", "rainfore") as $string)
                {
                    if(is_numeric(stripos($val, $string)))
                    {
                        array_shift($records); //remove first image
                        break;
                        // print_r($records); exit; //debug
                    }
                }
            }
            if(!is_numeric(stripos($taxon['href'], "insectos/"))) return; //only process insects, no plants
            $adj['http://www.acguanacaste.ac.cr/paginas-de-especies/insectos/288-erebidae/632-i-aclytia-albistrigadhj02-i-erebidae']['exc'][]       = "http://www.acguanacaste.ac.cr/images/species-home-page/Aclytia_albistrigaDHJ02/IMG_5106.JPG";
            $adj['http://www.acguanacaste.ac.cr/paginas-de-especies/insectos/276-geometridae/591-i-acrotomia-mucia-i-geometridae']['inc']           = array("http://www.acguanacaste.ac.cr/images/species-home-page/Acrotomia-mucia/01-SRNP-6990-DHJ325614.jpg", "http://www.acguanacaste.ac.cr/images/species-home-page/Acrotomia-mucia/01-SRNP-6990-DHJ325615.jpg", "http://www.acguanacaste.ac.cr/images/species-home-page/Acrotomia-mucia/02-SRNP-5430-DHJ325602.jpg", "http://www.acguanacaste.ac.cr/images/species-home-page/Acrotomia-mucia/02-SRNP-5430-DHJ325603.jpg");
            $adj['http://www.acguanacaste.ac.cr/paginas-de-especies/insectos/104-nymphalidae/537-i-adelpha-melanthe-i-nymphalidae']['exc'][]        = "http://www.acguanacaste.ac.cr/images/species-home-page/Adelpha-melanthe/Fig.10_Arbusto_Trema_micrantha.jpg";
            $adj['http://www.acguanacaste.ac.cr/paginas-de-especies/insectos/101-sphingidae/280-i-aellopos-ceculus-i-sphingidae']['exc'][]          = "http://www.acguanacaste.ac.cr/images/species-home-page/Aellopos-ceculus/IMG_3371.JPG";
            $adj['http://www.acguanacaste.ac.cr/paginas-de-especies/insectos-2/292-strigidae/732-i-asio-clamator-i-strigidae']['exc'][]             = "http://www.acguanacaste.ac.cr/images/miniaturas/Asio-clamator/Fig.3_Habitat_nido.JPG";
            $adj['http://www.acguanacaste.ac.cr/paginas-de-especies/insectos/103-hesperiidae/714-i-astraptes-fruticibus-i-hesperiidae']['exc'][]    = "http://www.acguanacaste.ac.cr/images/species-home-page/Astraptes-fruticibus/Figura-5-de-cuadro-mostrando-los-nombres-de-plantas-que-se-alimenta-Astraptes_fruticibus.jpg";
            $adj['http://www.acguanacaste.ac.cr/paginas-de-especies/insectos/104-nymphalidae/727-i-caligo-sulanus-i-nymphalidae']['exc'][]          = "http://www.acguanacaste.ac.cr/images/species-home-page/Caligo-sulanus/IMG_2225.JPG";
            $adj['http://www.acguanacaste.ac.cr/paginas-de-especies/insectos/103-hesperiidae/219-cephise-nuspezes-hesperiidae']['exc'][]            = "http://www.acguanacaste.ac.cr/images/species-home-page/Cephise-nuspezes/Sendero-Venado-Sector-Caribe-Area-de-Conservaci%C3%B3n-Guanacaste.JPG";
            $adj['http://www.acguanacaste.ac.cr/paginas-de-especies/insectos/103-hesperiidae/398-i-cyclosemia-subcaerula-i-hesperiidae']['exc'][]   = "http://www.acguanacaste.ac.cr/images/species-home-page/Cyclosemia-subcaerula/IMG_6233.JPG";
            $adj['http://www.acguanacaste.ac.cr/paginas-de-especies/insectos/101-sphingidae/214-amphonyx-duponchel-sphingidae']['exc']              = array("http://www.acguanacaste.ac.cr/images/species-home-page/Amphonyx-duponchel/IMG_4422.JPG", "http://www.acguanacaste.ac.cr/images/species-home-page/Amphonyx-duponchel/12-SRNP-21186-DHJ493312.jpg");
            $adj['http://www.acguanacaste.ac.cr/paginas-de-especies/insectos/104-nymphalidae/276-i-archaeoprepona-demophoon-i-nymphalidae']['exc']  = array("http://www.acguanacaste.ac.cr/images/species-home-page/Archaeoprepona/miniaturas/Fig_1._Ocotea_veraguensis.JPG", "http://www.acguanacaste.ac.cr/images/species-home-page/Archaeoprepona/miniaturas/Fig_9._Corteza_ocotea_veraguensis.jpg", "http://www.acguanacaste.ac.cr/images/species-home-page/Archaeoprepona/miniaturas/Fig_10._Ocotea_veraguensis_y_fruto.jpg");
            $adj['http://www.acguanacaste.ac.cr/paginas-de-especies/insectos/103-hesperiidae/216-astraptes-brevicauda-hesperiidae']['exc']          = array("http://www.acguanacaste.ac.cr/images/species-home-page/Astraptes-brevicauda/IMG_5098.jpg", "http://www.acguanacaste.ac.cr/images/species-home-page/Astraptes-brevicauda/IMG_5109.JPG", "http://www.acguanacaste.ac.cr/images/species-home-page/Astraptes-brevicauda/IMG_5101.JPG");
            $adj['http://www.acguanacaste.ac.cr/paginas-de-especies/insectos/103-hesperiidae/217-bungalotis-erythus-hesperiidae']['exc']            = array("http://www.acguanacaste.ac.cr/images/species-home-page/Bungalotis-erythus/IMG_0806.JPG", "http://www.acguanacaste.ac.cr/images/species-home-page/Bungalotis-erythus/IMG_0799.JPG", "http://www.acguanacaste.ac.cr/images/species-home-page/Bungalotis-erythus/IMG_0794.JPG");
            $adj['http://www.acguanacaste.ac.cr/paginas-de-especies/insectos/104-nymphalidae/351-i-brassolis-isidrochaconi-i-nymphalidae']['exc']   = array("http://www.acguanacaste.ac.cr/images/species-home-page/Brassolis-isthmia/13-SRNP-30714-DHJ700876.jpg", "http://www.acguanacaste.ac.cr/images/species-home-page/Brassolis-isthmia/13-SRNP-30714-DHJ700878.jpg");
            $adj['http://www.acguanacaste.ac.cr/paginas-de-especies/insectos/103-hesperiidae/360-i-bungalotis-diophorus-i-hesperiidae']['exc']      = array("http://www.acguanacaste.ac.cr/images/species-home-page/Bungalotis_diophorus/IMG_2773.JPG", "http://www.acguanacaste.ac.cr/images/species-home-page/Bungalotis_diophorus/13-SRNP-42931-DHJ708129.JPG", "http://www.acguanacaste.ac.cr/images/species-home-page/Bungalotis_diophorus/13-SRNP-42931-DHJ708130.JPG");
            $adj['http://www.acguanacaste.ac.cr/paginas-de-especies/insectos/104-nymphalidae/218-caligo-telamonius-nymphalidae']['exc']             = array("http://www.acguanacaste.ac.cr/images/species-home-page/Caligo-telamonius/IMG_1735.JPG", "http://www.acguanacaste.ac.cr/images/species-home-page/Caligo-telamonius/IMG_1750.JPG");
            $adj['http://www.acguanacaste.ac.cr/paginas-de-especies/insectos/103-hesperiidae/608-i-elbella-i-patrobasdhj05-hesperiidae']['exc'][] = "http://www.acguanacaste.ac.cr/images/species-home-page/Elbella-patrobasDHJ05/IMG_2879.JPG";
            $adj['http://www.acguanacaste.ac.cr/paginas-de-especies/insectos/101-sphingidae/546-i-enyo-ocypete-i-sphingidae']['exc'][] = "http://www.acguanacaste.ac.cr/images/species-home-page/Enyo-ocypete/IMG_0329.JPG";
            $adj['http://www.acguanacaste.ac.cr/paginas-de-especies/insectos/105-limacodidae/221-epiperola-vaferella-limacodidae']['exc'] = array("http://www.acguanacaste.ac.cr/images/species-home-page/_Epiperola-vaferella/miniaturas/IMG_4444.JPG", "http://www.acguanacaste.ac.cr/images/species-home-page/_Epiperola-vaferella/miniaturas/IMG_4445.JPG", "http://www.acguanacaste.ac.cr/images/species-home-page/_Epiperola-vaferella/IMG_3973.JPG");
            $adj['http://www.acguanacaste.ac.cr/paginas-de-especies/insectos/103-hesperiidae/515-i-eracon-lachesis-i-hesperiidae']['exc'][] = "http://www.acguanacaste.ac.cr/images/species-home-page/Eracon-lachesis/Habitad_10_Enero_2007.JPG";
            $adj['http://www.acguanacaste.ac.cr/paginas-de-especies/insectos/101-sphingidae/266-i-eumorpha-anchemolus-i-sphingidae']['exc'][] = "http://www.acguanacaste.ac.cr/images/species-home-page/Eumorpha-anchemolus/IMG_3858.JPG";
            $adj['http://www.acguanacaste.ac.cr/paginas-de-especies/insectos/101-sphingidae/236-eumorpha-phorbas-sphingidae']['exc'] = array("http://www.acguanacaste.ac.cr/images/species-home-page/Eumorpha-phorbas/Eumorpha-phorbas-3.jpg", "http://www.acguanacaste.ac.cr/images/species-home-page/Eumorpha-phorbas/Eumorpha-phorbas-4.jpg");
            $adj['http://www.acguanacaste.ac.cr/paginas-de-especies/insectos/102-saturniidae/371-i-lonomia-electra-i-saturniidae']['exc'][] = "http://www.acguanacaste.ac.cr/images/species-home-page/Lonomia-electra/H.Ramirez-02Est.Cacao_07_12_2010.jpg";
            $adj['http://www.acguanacaste.ac.cr/paginas-de-especies/insectos/104-nymphalidae/588-i-memphis-artacaena-i-nymphalidae']['exc'][] = "http://www.acguanacaste.ac.cr/images/species-home-page/Memphis-artacaena/Fig.11_Arbusto_de_Croton_schedianus_Estacion_Pitilla_Sendero_Laguna_Hospedero_de_Memphis_artacaena.jpg";
            $adj['http://www.acguanacaste.ac.cr/paginas-de-especies/insectos/104-nymphalidae/767-i-memphis-pithyusa-i-nymphalidae']['exc'][] = "http://www.acguanacaste.ac.cr/images/species-home-page/Memphis-_pithyusa/Fig.18_Arbusto_de_Croton_schedianus_Estacion_Pitilla_Sendero_Laguna_Hospedero_de_Memphis_artacaena.jpg";
            $adj['http://www.acguanacaste.ac.cr/paginas-de-especies/insectos/104-nymphalidae/178-morpho-catalina-nymphalidae']['exc'][] = "http://www.acguanacaste.ac.cr/images/parataxonomos/mariano-pereira/IMG_1912.jpg";
            $adj['http://www.acguanacaste.ac.cr/paginas-de-especies/insectos/103-hesperiidae/282-i-neoxeniades-burns03-i-hesperiidae']['exc'][] = "http://www.acguanacaste.ac.cr/images/species-home-page/Neoxeniades-Burns03/Fig.7.13-SRNP-30634-DHJ700768.jpg";
            $adj['http://www.acguanacaste.ac.cr/paginas-de-especies/insectos/103-hesperiidae/311-i-neoxeniades-burns04-i-hesperiidae']['exc'][] = "http://www.acguanacaste.ac.cr/images/species-home-page/Neoxeniades-Burns04/miniaturas/peq_06-SRNP-31635-DHJ412722.jpg";
            /*
            [href] => /paginas-de-especies/insectos/121-notodontidae/763-rhuda-difficilis-notodontidae
            [sciname] => Rhuda difficilis
            [family] => Notodontidae
            [author] => Manuel Rios
            [taxon_id] => rhuda_difficilis

            [15] => Array
                (
                    [src] => http://www.acguanacaste.ac.cr/images/species-home-page/Rhuda-difficilis/miniaturas/600x400xpeq_Fig.16.IMG_7812.jpg.pagespeed.ic.emLbMLMMUo.jpg
                    [caption] => Fig.16. Arbol juvenil de <i>Conostegia micrantha</i> (Melastomataceae)
                )
            */
        }
        foreach($records as $rec)
        {
            if($obj_type == "image")
            {
                $used_href = $this->acg_domain . $taxon['href'];
                if($exclude = @$adj[$used_href]['exc'])
                {
                    if(in_array($rec['src'], $exclude)) continue;
                }
                elseif($include = @$adj[$used_href]['inc'])
                {
                    if(!in_array($rec['src'], $include)) continue;
                }

                $proceed = true;
                $strings = array("Planta ", "Plantas ", "planta,", "forest ", "Planta_", "Chimarrhis", "habito ", "follaje ", "fruto", "Actinidiaceae", "Rutaceae", "Malvaceae", "lista ", "Fagaceae", "Bromeliaceae", "Malpighiaceae", "Clusia ", "Hojas", "Tronco", "Arbol", " Arbusto", "hospeder", "alojan", "alojar", "lente", "toldo sobre", "toldos sobre", "Rubiaceae");
                foreach($strings as $string)
                {
                    if(is_numeric(stripos($rec['caption'], $string)))
                    {
                        $proceed = false;
                        break;
                    }
                }
                if(!$proceed) continue;
                
                /*
                continue with: Potamanaxas Burns03 (Hesperiidae) 
                */
                
                $path_info = pathinfo($rec['src']);
                if(!$path_info['extension'])
                {
                    print_r($taxon); print_r($rec);
                    exit("\ninvestigate: no extension\n");
                }
                if(in_array($path_info['extension'], array("png", "gif"))) continue; // exclude PNG, GIF image objects
            }
            
            $mr = new \eol_schema\MediaResource();
            $mr->taxonID = $taxon['taxon_id'];
            
            if($obj_type == "image")
            {
                $mr->identifier = md5($rec['src']);
                $mr->type = 'http://purl.org/dc/dcmitype/StillImage';
                $mr->format = Functions::get_mimetype($rec['src']);
                $mr->accessURI = $rec['src'];
                $mr->description = $rec['caption'];
            }
            else
            {
                $mr->identifier = $taxon['taxon_id'] . "_txt";
                $mr->type = 'http://purl.org/dc/dcmitype/Text';
                $mr->format = "text/html";
                $mr->description = $rec;
                $mr->CVterm = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#GeneralDescription";
            }
            
            $mr->language       = 'es';
            $mr->furtherInformationURL = $this->acg_domain . $taxon['href'];
            $mr->Owner          = 'Guanacaste Conservation Area';
            $mr->rights         = '';
            $mr->UsageTerms     = 'http://creativecommons.org/licenses/by-nc-sa/3.0/';
            if($agent_ids = self::get_agent_ids($taxon['author'])) $mr->agentID = implode("; ", $agent_ids);
            if(!isset($this->object_ids[$mr->identifier]))
            {
                $this->object_ids[$mr->identifier] = '';
                $this->archive_builder->write_object_to_file($mr);
            }
        }
    }
    
    private function create_image_objects($records)
    {
        foreach($records as $rec)
        {
            if(!$rec->url_foto) continue;
            
            $rec->especie = self::clean_name($rec->especie);
            $taxon_id     = strtolower(str_replace(" ", "_", $rec->especie));
            
            $mr = new \eol_schema\MediaResource();
            $mr->taxonID        = $taxon_id;
            $mr->identifier     = md5($rec->url_foto);
            $mr->type           = 'http://purl.org/dc/dcmitype/StillImage';
            $mr->language       = 'es';
            $mr->format         = Functions::get_mimetype($rec->url_foto);
            $mr->furtherInformationURL = "http://www.acguanacaste.ac.cr/biodiversidad/voucher.php?voucher=" . $rec->voucher;
            $mr->accessURI      = $rec->url_foto;
            $mr->thumbnailURL   = $rec->url_thumb;
            $mr->Owner          = 'Guanacaste Conservation Area';
            $mr->rights         = '';
            $mr->title          = '';
            $mr->UsageTerms     = 'http://creativecommons.org/licenses/by-nc-sa/3.0/';
            $mr->description    = self::get_description($rec);
            $mr->LocationCreated = self::get_location($rec);
            if($agent_ids = self::get_agent_ids($rec->autor)) $mr->agentID = implode("; ", $agent_ids);

            if(!isset($this->object_ids[$mr->identifier]))
            {
                $this->object_ids[$mr->identifier] = '';
                $this->archive_builder->write_object_to_file($mr);
            }
        }
    }
    
    private function get_agent_ids($author)
    {
        $agent_ids = array();
        if($author)
        {
            $r = new \eol_schema\Agent();
            $r->term_name       = $author;
            $r->agentRole       = 'author';
            $r->identifier      = md5("$r->term_name|$r->agentRole");
            $r->term_homepage   = '';
            $agent_ids[] = $r->identifier;
            if(!isset($this->agent_ids[$r->identifier]))
            {
               $this->agent_ids[$r->identifier] = '';
               $this->archive_builder->write_object_to_file($r);
            }
        }
        return $agent_ids;
    }
    
    private function get_description($rec)
    {
        $d = "";
        if($val = $rec->especie)               $d .= "<br>Especie: "               . $val;
        if($val = $rec->voucher)               $d .= "<br>Voucher: "               . $val;
        if($val = $rec->voucher_colecta)       $d .= "<br>Voucher de Colecta: "    . $val;
        if($val = $rec->sexo)                  $d .= "<br>Sexo: "                  . $val;

        if($val = $rec->reino)                 $d .= "<br>Reino: "                 . $val;
        if($val = $rec->filo)                  $d .= "<br>Filo: "                  . $val;
        if($val = $rec->clase)                 $d .= "<br>Clase: "                 . $val;
        if($val = $rec->orden)                 $d .= "<br>Orden: "                 . $val;
        if($val = $rec->familia)               $d .= "<br>Familia: "               . $val;
        if($val = $rec->subfamilia)            $d .= "<br>Subfamilia: "            . $val;
        if($val = $rec->tribu)                 $d .= "<br>Tribu: "                 . $val;
        if($val = $rec->subtribu)              $d .= "<br>Subtribu: "              . $val;

        if($val = $rec->fecha_colecta)         $d .= "<br>Fecha de colecta: "      . $val;
        if($val = $rec->pais)                  $d .= "<br>País: "                  . $val;
        if($val = $rec->sector)                $d .= "<br>Sector: "                . $val;
        if($val = $rec->localidad)             $d .= "<br>Lugar: "                 . $val;
        if($val = $rec->latitud)               $d .= "<br>Latitud: "               . $val;
        if($val = $rec->longitud)              $d .= "<br>Longitud: "              . $val;
        if($val = $rec->altura)                $d .= "<br>Altura: "                . $val;

        // added info, not in e.g. http://www.acguanacaste.ac.cr/biodiversidad/voucher.php?voucher=94-SRNP-7938-DHJ38486
        if($val = $rec->fecha_subida)          $d .= "<br>Fecha subida: "          . $val;
        if($val = $rec->enlace_complementario) $d .= "<br>Enlace complementario: " . $val;
        if($val = $rec->comentario)            $d .= "<br>Comentario: "            . $val;
        if($val = $rec->vista)                 $d .= "<br>Vista: "                 . $val;
        if($val = $rec->autor)                 $d .= "<br>Autor: "                 . $val;
        if($val = $rec->email)                 $d .= "<br>Email: "                 . $val;
        if($location = self::get_location($rec)) $d .= $location;
        
        return $d;
    }

    private function get_location($rec)
    {
        $d = "";
        if($val = $rec->provincia)  $d .= "<br>Provincia: "   . $val;
        if($val = $rec->ecosistema) $d .= "<br>Ecosistema: "  . $val;
        return $d;
    }
    
    private function create_taxa($records)
    {
        foreach($records as $rec)
        {
            /* not used yet
            [filo] => Animalia
            [subfamilia] => Arctiinae
            [genero] => 
            [tribu] => Arctiini
            [subtribu] => Pericopina
            */
            $rec->especie = self::clean_name($rec->especie);
            $taxon = new \eol_schema\Taxon();
            $taxon->taxonID         = strtolower(str_replace(" ", "_", $rec->especie));
            $taxon->scientificName  = $rec->especie;
            $taxon->kingdom         = $rec->reino;
            $taxon->class           = $rec->clase;
            $taxon->order           = $rec->orden;
            $taxon->family          = $rec->familia;
            $taxon->furtherInformationURL = $rec->species_homepage;
            if(!isset($this->taxa_ids[$taxon->taxonID]))
            {
                $this->taxa_ids[$taxon->taxonID] = '';
                $this->archive_builder->write_object_to_file($taxon);
            }
        }
    }

    private function clean_name($name)
    {
        for($i=1; $i <= strlen($name); $i++)
        {
            if(ctype_upper(substr($name,$i,1))) break;
        }
        return substr($name,0,$i);
    }
    
}
?>