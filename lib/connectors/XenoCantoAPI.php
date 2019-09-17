<?php
namespace php_active_record;
// connector: [xeno_canto.php]
class XenoCantoAPI
{
    function __construct($folder)
    {
        $this->resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));

        $this->download_options = array(
            'resource_id'        => $this->resource_id,  //resource_id here is just a folder name in cache
            'expire_seconds'     => 60*60*24*30*3, //expires quarterly
            'download_wait_time' => 1000000, 'timeout' => 60*3, 'download_attempts' => 2, 'delay_in_minutes' => 1, 'cache' => 1);
        // $this->download_options['expire_seconds'] = 0;
        $this->domain = 'https://www.xeno-canto.org';
        $this->species_list = $this->domain.'/collection/species/all';
    }
    function start()
    {   
        if($html = Functions::lookup_with_cache($this->species_list, $this->download_options)) {
            // echo $html;
            if(preg_match_all("/<tr class(.*?)<\/tr>/ims", $html, $arr)) {
                // print_r($arr[1]);
                foreach($arr[1] as $r) {
                    /*[0] => ='new-species'>
                        <td>
                        <span clas='common-name'>
                        <a href="/species/Struthio-camelus">Common Ostrich</a>
                        </span>
                        </td>
                        <td>Struthio camelus</td>
                        <td></td>
                        <td align='right' width='20'>3</td>
                        <td align='right' width='30'>0</td>
                    */
                    $rec = array();
                    if(preg_match("/<span clas='common-name'>(.*?)<\/span>/ims", $r, $arr)) $rec['comname'] = trim(strip_tags($arr[1]));
                    if(preg_match("/href=\"(.*?)\"/ims", $r, $arr)) $rec['url'] = strip_tags($arr[1]);
                    if(preg_match_all("/<td>(.*?)<\/td>/ims", $r, $arr)) {
                        $rec['sciname'] = $arr[1][1];
                    }
                    $rec = array_map('trim', $rec);
                    if($rec['sciname'] && $rec['url']) {
                        $ret = self::prepare_media_records($rec);
                        self::write_taxon($ret['orig_rec']);
                    }
                    break;
                }
            }
            else echo "\nnothing found...\n";
        }
        else echo "\nno HTML\n";
        // exit("\n111\n");
        $this->archive_builder->finalize(TRUE);
    }
    private function parse_order_family($html, $orig_rec)
    {
        // Order: <a href='/explore/taxonomy?o=STRUTHIONIFORMES'>STRUTHIONIFORMES</a>
        if(preg_match("/Order:(.*?)<\/a>/ims", $html, $arr)) {
            if(preg_match("/o=(.*?)\'/ims", $arr[1], $arr2)) {
                $orig_rec['order'] = ucfirst(strtolower($arr2[1]));
            }
        }
        // Family: <a href='/explore/taxonomy?f=Struthionidae'>Struthionidae</a> (Ostriches)
        if(preg_match("/Family:(.*?)<\/a>/ims", $html, $arr)) {
            if(preg_match("/\?f=(.*?)\'/ims", $arr[1], $arr2)) {
                $orig_rec['family'] = ucfirst($arr2[1]);
            }
        }
        $orig_rec['taxonID'] = strtolower(str_replace(" ", "-", $orig_rec['sciname']));
        // print_r($orig_rec); exit;
        return $orig_rec;
    }
    private function prepare_media_records($rec)
    {
        $orig_rec = $rec;
        if($html = Functions::lookup_with_cache($this->domain.$rec['url'], $this->download_options)) {
            // echo $html;
            $orig_rec = self::parse_order_family($html, $orig_rec);
            if(preg_match("/<table class=\"results\">(.*?)<\/table>/ims", $html, $arr)) {
                // echo $arr[1]; exit;
                $str = $arr[1];
                if(preg_match("/<thead>(.*?)<\/thead>/ims", $str, $arr2)) {
                    if(preg_match_all("/<th>(.*?)<\/th>/ims", $arr2[1], $arr)) {
                        // print_r($arr[1]);
                        $fields = array_map('strip_tags', $arr[1]);
                        $fields = array_map('trim', $fields);
                        $fields[0] = 'download';
                        $fields[1] = 'sciname';
                        print_r($fields);
                    }
                }
                
                if(preg_match_all("/<tr (.*?)<\/tr>/ims", $str, $arr)) {
                    // print_r($arr);
                    $final = array();
                    foreach($arr[1] as $r) {
                        if(preg_match_all("/<td(.*?)<\/td>/ims", $r, $arr)) {
                            $values = array_map('trim', $arr[1]);
                            // print_r($values); exit;

                            $rek = array();
                            $i = -1;
                            foreach($fields as $f) { $i++;
                                $rek[$f] = $values[$i];
                            }
                            // print_r($rek); exit;
                            $final[] = $rek;
                        }
                    }
                }
                
            }
            
        }
        // print_r($final); exit;
        return array('orig_rec' => $orig_rec, 'media' => $final);
    }
    private function write_taxon($rec)
    {
        // print_r($rec); exit;
        /*Array(
            [comname] => Common Ostrich
            [url] => /species/Struthio-camelus
            [sciname] => Struthio camelus
            [order] => Struthioniformes
            [family] => Struthionidae
        )*/
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID         = $rec['taxonID'];
        $taxon->scientificName  = $rec['sciname'];
        $taxon->taxonRank       = 'species';
        $taxon->order           = $rec['order'];
        $taxon->family          = $rec['family'];
        if(!isset($this->taxon_ids[$taxon->taxonID])) {
            $this->archive_builder->write_object_to_file($taxon);
            $this->taxon_ids[$taxon->taxonID] = '';
        }
    }
}
?>