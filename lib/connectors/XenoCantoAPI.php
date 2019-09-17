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
                        self::write_taxon($rec);
                        $media = self::prepare_media_records($rec);
                        
                    }
                    break;
                }
            }
            else echo "\nnothing found...\n";
        }
        else echo "\nno HTML\n";
        exit;
        // $this->archive_builder->finalize(TRUE);
    }
    private function prepare_media_records($rec)
    {
        print_r($rec);
        if($html = Functions::lookup_with_cache($this->domain.$rec['url'], $this->download_options)) {
            // echo $html;
            
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
        print_r($final); exit;
    }
    private function write_taxon($rec)
    {
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID                  = self::compute_taxonID($a, $taxon->taxonomicStatus);
        $taxon->scientificName           = $a[$this->map['scientificName']];
        $taxon->scientificNameAuthorship = @$a[$this->map['scientificNameAuthorship']];
        $taxon->taxonRank                = self::compute_taxonRank($a);
        if(!isset($this->taxon_ids[$taxon->taxonID])) {
            $this->archive_builder->write_object_to_file($taxon);
            $this->taxon_ids[$taxon->taxonID] = '';
        }
    }
}
?>