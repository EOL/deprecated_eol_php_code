<?php
namespace php_active_record;
/* connector: [carabidae.php] */

class CarabidaeAPI
{
    function __construct($resource_id)
    {
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $resource_id . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->download_options = array('resource_id' => 'carabidae', 'expire_seconds' => 5184000, 'timeout' => 7200, 'download_wait_time' => 500000); // 2 months expire_seconds
        $this->download_options['expire_seconds'] = false;
        $this->portal_domain = 'http://carabidae.org/';
        $this->taxon_tab_file = CONTENT_RESOURCE_LOCAL_PATH . "/$resource_id/taxon_extension.txt";
    }

    function convert_to_dwca()
    {
        if($OUT = Functions::file_open($this->taxon_tab_file, "w")) //initialize file
        {
            fwrite($OUT, "taxonID"."\t"."scientificName"."\t"."taxonRank"."\t"."Subfamily"."\t"."Tribe"."\t"."Subtribe"."\t"."genus"."\t"."subgenus"."\t"."Species"."\n");
            fclose($OUT);
        }
        
        $letters = "A,B,C,D,E,F,G,H,I,J,K,L,M,N,O,P,Q,R,S,T,U,V,W,X,Y,Z";
        foreach(explode(",", $letters) as $letter)
        {
            $url = $this->portal_domain . "/abc?key=$letter&page=1";
            echo "\n[$url]";
            if($html = Functions::lookup_with_cache($url, $this->download_options))
            {
                $max_page = false;
                if(preg_match("/page={page}',(.*?)\)/ims", $html, $arr)) $max_page = $arr[1];
                echo " - [$max_page]";
                if($max_page)
                {
                    for($page=1; $page <= $max_page; $page++)
                    {
                        $url = $this->portal_domain . "/abc?key=$letter&page=$page";
                        // $url = "http://carabidae.org/abc?key=A&page=78"; //debug
                        if($html = Functions::lookup_with_cache($url, $this->download_options)) self::process_page($html);
                        // break; //debug
                    }
                }
            }
            // break; //debug
        }
        $this->archive_builder->finalize(TRUE);
    }
    
    private function process_page($html)
    {
        if(preg_match("/<ul class=\"taxa-list taxa\">(.*?)<\/ul>/ims", $html, $arr))
        {
            if(preg_match_all("/<li(.*?)<\/li>/ims", $arr[1], $arr2))
            {
                foreach($arr2[1] as $t)
                {
                    $rec = array();
                    // id="t181" data-id="181"><b>Tribe</b> <a href="taxa/abacetini">Abacetini Chaudoir, 1872</a>  (<a href="taxa/pterostichinae-bonelli-1810">Pterostichinae</a>)
                    if(preg_match("/data-id=\"(.*?)\"/ims", $t, $arr3)) $rec['taxon_id'] = $arr3[1];
                    if(preg_match("/<b>(.*?)<\/b>/ims", $t, $arr3))     $rec['rank'] = strtolower(trim($arr3[1]));
                    else
                    {
                        if(preg_match("/>(.*?)<a href/ims", $t, $arr3)) $rec['rank'] = strtolower(trim($arr3[1]));
                    }
                    if(preg_match("/href=(.*?)<\/a>/ims", $t, $arr3))
                    {
                        if(preg_match("/\"(.*?)\"/ims", $arr3[1], $arr4)) $rec['source'] = $arr4[1];
                        if(preg_match("/>(.*?)xxx/ims", $arr3[1].'xxx', $arr4)) $rec['sciname'] = trim($arr4[1]);
                        if(preg_match("/\((.*?)\)/ims", $t, $arr4)) // enclosed by parenthesis
                        {
                            $temp = strip_tags($arr4[1]);
                            $rec['in parenthesis'] = array_map('trim', explode(",", $temp)); //list of parent taxa in array
                        }
                    }
                    if(in_array($rec['rank'], array('tribe', 'subtribe', 'genus', 'subgenus', 'species', 'subspecies'))) $rec = self::process_species_subspecies($rec);
                    self::create_archive($rec);
                }
            }
        }
    }

    private function process_species_subspecies($rec)
    {
        $url = $this->portal_domain . $rec['source'];
        if($html = Functions::lookup_with_cache($url, $this->download_options))
        {
            if(preg_match("/<h1(.*?)<\/h1>/ims", $html, $arr))
            {
                $rec['sciname2'] = Functions::remove_whitespace(strip_tags('<h1' . $arr[1]));

                if($rec['rank'] == 'tribe')        $rec['sciname2'] = str_replace('xxxTribe '   , '', 'xxx'.$rec['sciname2']);
                elseif($rec['rank'] == 'subtribe') $rec['sciname2'] = str_replace('xxxSubtribe ', '', 'xxx'.$rec['sciname2']);
                elseif($rec['rank'] == 'genus')    $rec['sciname2'] = str_replace('xxxGenus '   , '', 'xxx'.$rec['sciname2']);
                elseif($rec['rank'] == 'subgenus') $rec['sciname2'] = str_replace('xxxSubgenus ', '', 'xxx'.$rec['sciname2']);
            }
            
            //for parent taxa
            //<b>Subfamily</b>&nbsp;<a href="taxa/scaritinae">Scaritinae Bonelli, 1810</a>
            if(preg_match("/<b>Subfamily<\/b>(.*?)<\/a>/ims", $html, $arr)) $rec['ancestry']['subfamily'] = self::parse_href_string($arr[1]);
            if(preg_match("/<b>Tribe<\/b>(.*?)<\/a>/ims", $html, $arr))     $rec['ancestry']['tribe']     = self::parse_href_string($arr[1]);
            if(preg_match("/<b>Subtribe<\/b>(.*?)<\/a>/ims", $html, $arr))  $rec['ancestry']['subtribe']  = self::parse_href_string($arr[1]);
            if(preg_match("/\">Genus&nbsp;(.*?)<\/a>/ims", $html, $arr))    $rec['ancestry']['genus']     = self::parse_href_string($arr[1]);
            if(preg_match("/\">Subgenus&nbsp;(.*?)<\/a>/ims", $html, $arr)) $rec['ancestry']['subgenus']  = self::parse_href_string($arr[1]);
            if(preg_match("/\">Species&nbsp;(.*?)<\/a>/ims", $html, $arr))  $rec['ancestry']['species']   = self::parse_href_string($arr[1]);
        }
        return $rec;
    }

    private function parse_href_string($str)
    {
        //&nbsp;<a href="taxa/scaritinae">Scaritinae Bonelli, 1810
        $rek = array();
        if(preg_match("/href=\"(.*?)\"/ims", $str, $arr)) $rek['href'] = $arr[1];
        if(preg_match("/>(.*?)xxx/ims", $str.'xxx', $arr)) $rek['name'] = Functions::remove_whitespace($arr[1]);
        return $rek;
    }
    
    private function create_archive($rec)
    {
        $t = new \eol_schema\Taxon();
        if($val = @$rec['sciname2'])    $t->scientificName = $val;
        elseif($val = @$rec['sciname']) $t->scientificName = $val;
        $t->taxonID     = $rec['taxon_id'];
        $t->taxonRank   = $rec['rank'];

        $ranks = array('subfamily', 'tribe', 'subtribe', 'genus', 'subgenus', 'species', 'subspecies');
        $ranks = array('genus', 'subgenus', 'subspecies');
        foreach($ranks as $rank)
        {
            if($val = @$rec['ancestry'][$rank]['name']) $t->$rank = $val;
        }
        
        $t->furtherInformationURL = $this->portal_domain . $rec['source'];
        if(!isset($this->taxon_ids[$t->taxonID]))
        {
            $this->taxon_ids[$t->taxonID] = '';
            $this->archive_builder->write_object_to_file($t);
            self::generate_taxon_extension_for_dwca($rec); //for a non-EOL extension
        }
        else
        {
            echo "\n[Investigate: taxon already created - $t->taxonID]\n";
        }
    }
    
    private function generate_taxon_extension_for_dwca($rec)
    {
        /*
        <field index="0" term="http://rs.tdwg.org/dwc/terms/taxonID"/>
        <field index="2" term="http://rs.tdwg.org/dwc/terms/scientificName"/>
        <field index="3" term="http://rs.tdwg.org/dwc/terms/taxonRank"/>
        http://rs.tdwg.org/ontology/voc/TaxonRank#Subfamily
        http://rs.tdwg.org/ontology/voc/TaxonRank#Tribe
        http://rs.tdwg.org/ontology/voc/TaxonRank#Subtribe
        <field index="4" term="http://rs.tdwg.org/dwc/terms/genus"/>
        <field index="5" term="http://rs.tdwg.org/dwc/terms/subgenus"/>
        http://rs.tdwg.org/ontology/voc/TaxonRank#Species
        */
        // taxonID  scientificName  taxonRank   Subfamily   Tribe   Subtribe    genus   subgenus    Species
        
        if($OUT = Functions::file_open($this->taxon_tab_file, "a"))
        {
            if($val = @$rec['sciname2'])    $scientificName = $val;
            elseif($val = @$rec['sciname']) $scientificName = $val;
            fwrite($OUT, $rec['taxon_id']."\t");
            fwrite($OUT, $scientificName."\t");
            fwrite($OUT, $rec['rank']."\t");
            fwrite($OUT, @$rec['ancestry']['subfamily']['name']."\t");
            fwrite($OUT, @$rec['ancestry']['tribe']['name']."\t");
            fwrite($OUT, @$rec['ancestry']['subtribe']['name']."\t");
            fwrite($OUT, @$rec['ancestry']['genus']['name']."\t");
            fwrite($OUT, @$rec['ancestry']['subgenus']['name']."\t");
            
            $final_species = "";
            if($species = @$rec['ancestry']['species']['name'])
            {
                $final_species = Functions::canonical_form($rec['ancestry']['genus']['name']);
                if($val = @$rec['ancestry']['subgenus']['name']) $final_species .= " (" . Functions::canonical_form($val) . ")";
                $final_species .= " $species";
            }
            
            fwrite($OUT, $final_species."\n");
            fclose($OUT);
        }
    }

}
?>
