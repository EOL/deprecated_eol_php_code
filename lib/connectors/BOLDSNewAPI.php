<?php
namespace php_active_record;
/* connector: [bolds.php]
*/
class BOLDSNewAPI
{
    function __construct($folder = false)
    {
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));

        $this->max_images_per_taxon = 10;
        $this->page['home'] = "http://www.boldsystems.org/index.php/TaxBrowser_Home";
        $this->page['sourceURL'] = "http://www.boldsystems.org/index.php/Taxbrowser_Taxonpage?taxid=";
        $this->service['phylum'] = "http://v2.boldsystems.org/connect/REST/getSpeciesBarcodeStatus.php?phylum=";
        
        $this->service["taxId"] = "http://www.boldsystems.org/index.php/API_Tax/TaxonData?dataTypes=all&includeTree=true&taxId=";
        
        $this->download_options = array('cache' => 1, 'resource_id' => 'BOLDS', 'expire_seconds' => 60*60*24*30*9, 'download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1); //9 months to expire
    }

    function start()
    {
        $taxon_ids = self::get_all_taxon_ids();
    }
    private function get_all_taxon_ids()
    {
        $phylums = self::get_all_phylums();
        $download_options = $this->download_options;
        $download_options['expire_seconds'] = false;
        $k = 0; $m = count($phylums)/5;
        
        
        /*
            [3] => Brachiopoda
            [4] => Bryozoa
            [5] => Chaetognatha
            [7] => Cnidaria
            [8] => Cycliophora
            [9] => Echinodermata
            [10] => Gnathostomulida
            [11] => Hemichordata
            [12] => Mollusca
            [13] => Nematoda
            [14] => Nemertea
            [15] => Onychophora
            [16] => Platyhelminthes
            [17] => Porifera
            [18] => Priapulida
            [19] => Rotifera
            [20] => Sipuncula
            
            'Tardigrada', 'Xenoturbellida', 'Bryophyta', 'Chlorophyta', 'Lycopodiophyta', 'Pinophyta', 'Pteridophyta', 'Rhodophyta'
            'Ascomycota', 'Basidiomycota', 'Chytridiomycota', 'Glomeromycota'
            'Myxomycota', 'Zygomycota', 'Chlorarachniophyta', 'Ciliophora'
        */
        
        $phylums = array_keys($phylums);
        $phylums = array('Arthropoda');
        $phylums = array('Magnoliophyta');
        $phylums = array('Acanthocephala', 'Annelida');
        $phylums = array('Chordata');
        $phylums = array('Pyrrophycophyta', 'Heterokontophyta');
        
        

        foreach($phylums as $phylum) {
            
            /* breakdown when caching:
            $k++;
            $cont = false;
            // if($k >=  1    && $k < $m) $cont = true;
            // if($k >=  $m   && $k < $m*2) $cont = true;
            // if($k >=  $m*2 && $k < $m*3) $cont = true;
            // if($k >=  $m*3 && $k < $m*4) $cont = true;
            if($k >=  $m*4 && $k < $m*5) $cont = true;
            if(!$cont) continue;
            */
            


            echo "\n$phylum ";
            $temp_file = Functions::save_remote_file_to_local($this->service['phylum'].$phylum, $download_options);
            $reader = new \XMLReader();
            $reader->open($temp_file);
            while(@$reader->read()) {
                if($reader->nodeType == \XMLReader::ELEMENT && $reader->name == "record") {
                    $string = $reader->readOuterXML();
                    if($xml = simplexml_load_string($string)) {
                        // print_r($xml);
                        // if(($i % 10000) == 0) echo "\n".number_format($i)." ";
                        $ranks = array('phylum', 'class', 'order', 'family', 'genus', 'species');
                        foreach($ranks as $rank) {
                            echo "\n - $phylum ".@$xml->taxonomy->$rank->taxon->taxid;
                            if($taxid = (string) @$xml->taxonomy->$rank->taxon->taxid) {
                                $final[$taxid] = '';
                                self::process_record($taxid);
                            }
                        }
                    }
                }
            }
            unlink($temp_file);
            // break; //debug
        }
        print_r($final);
    }
    private function process_record($taxid)
    {
        /*
        [sitemap] => http://www.boldsystems.org/index.php/TaxBrowser_Maps_CollectionSites?taxid=2
        [images] => Array
                       (
                           [0] => Array
                               (
                                   [copyright_institution] => Centre for Biodiversity Genomics
                                   [specimenid] => 968120
                                   [copyright] => 
                                   [imagequality] => 5
                                   [photographer] => Nick Jeffery
                                   [image] => ANCN/IMG_6772+1228833566.JPG
                                   [fieldnum] => L#08PUK-055
                                   [sampleid] => 08BBANN-009
                                   [mam_uri] => bold.org/323285
                                   [copyright_license] => CreativeCommons - Attribution Non-Commercial Share-Alike
                                   [meta] => Lateral
                                   [copyright_holder] => CBG Photography Group
                                   [catalognum] => 08BBANN-009
                                   [copyright_contact] => ccdbcol@uoguelph.ca
                                   [copyright_year] => 2008
                                   [taxonrep] => Clitellata
                                   [aspectratio] => 1.499
                                   [original] => 1
                                   [external] => 
                               )
        */
        if($json = Functions::lookup_with_cache($this->service['taxId'].$taxid, $this->download_options))
        {
            $a = json_decode($json, true);
            // print_r($a);
            
        }
        // exit("\n");
    }
    private function get_all_phylums()
    {
        if($html = Functions::lookup_with_cache($this->page['home'], $this->download_options)) {
            /* <li><a class="link" href="/index.php/Taxbrowser_Taxonpage?taxid=11">Acanthocephala [747]</a></li> */
            if(preg_match_all("/Taxbrowser_Taxonpage\?taxid\=(.*?) \[/ims", $html, $a)) {
                foreach($a[1] as $tmp) {
                    $tmp = explode('">', $tmp);
                    $final[$tmp[1]] = $tmp[0];
                }
            }
        }
        // print_r(array_keys($final)); exit;
        return $final;
    }

}
?>