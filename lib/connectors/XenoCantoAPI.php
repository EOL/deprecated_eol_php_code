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
                        self::write_media($ret['media']);
                        
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
                            $rek['taxonID'] = $orig_rec['taxonID'];
                            $final[] = $rek;
                        }
                    }
                }
            }
        }
        // print_r($final); exit;
        return array('orig_rec' => $orig_rec, 'media' => $final);
    }
    private function write_media($records)
    {
        foreach($records as $rec) {
            // print_r($rec); exit;
            /*Array(
                [0] => download
                [1] => sciname
                [2] => Length
                [3] => Recordist
                [4] => Date
                [5] => Time
                [6] => Country
                [7] => Location
                [8] => Elev. (m)
                [9] => Type
                [10] => Remarks
                [11] => Actions
                [12] => Cat.nr.
            )*/
            $ret1 = self::parse_location_lat_long($rec['Location']);
            if($ret = self::parse_recordist($rec['Recordist'])) $agent_id = self::write_agent($ret);
            if($uri = self::parse_accessURI($rec['download'])) $accessURI = $uri;
            else continue;
            
            $mr = new \eol_schema\MediaResource();
            $mr->taxonID        = $rec['taxonID'];
            $mr->identifier     = md5($accessURI);
            // $mr->type           = $o['dataType'];
            // $mr->language       = 'en';
            // $mr->format         = $o['mimeType'];
            // if(substr($o['dc_source'], 0, 4) == "http") $mr->furtherInformationURL = self::use_best_fishbase_server($o['dc_source']);
            $mr->accessURI      = $accessURI;
            // $mr->thumbnailURL   = self::use_best_fishbase_server($o['thumbnailURL']);
            // $mr->CVterm         = $o['subject'];
            // $mr->Owner          = $o['dc_rightsHolder'];
            // $mr->rights         = $o['dc_rights'];
            // $mr->title          = $o['dc_title'];
            // $mr->UsageTerms     = $o['license'];
            
            $mr->LocationCreated       = $ret1['location'];
            $mr->lat       = $ret1['lat'];
            $mr->long       = $ret1['long'];
            
            // $mr->description    = utf8_encode($o['dc_description']);
            // if(!Functions::is_utf8($mr->description)) continue;
            // $mr->LocationCreated = $o['location'];
            // $mr->bibliographicCitation = $o['dcterms_bibliographicCitation'];
            // if($reference_ids = @$this->object_reference_ids[$o['int_do_id']])  $mr->referenceID = implode("; ", $reference_ids);
            
            $mr->agentID = $agent_id;
            
            if(!isset($this->object_ids[$mr->identifier])) {
                $this->archive_builder->write_object_to_file($mr);
                $this->object_ids[$mr->identifier] = '';
            }
        }
    }
    private function parse_accessURI($str)
    {
        // data-xc-filepath='//www.xeno-canto.org/sounds/uploaded/DNKBTPCMSQ/Ostrich%20RV%202-10.mp3'>
        if(preg_match("/filepath='(.*?)'/ims", $str, $arr)) return 'https:'.$arr[1];
    }
    private function parse_recordist($str)
    {
        //<a href='/contributor/DNKBTPCMSQ'>Derek Solomon</a>
        $val = array();
        if(preg_match("/href='(.*?)\'/ims", $str, $arr)) $val['homepage'] = $arr[1];
        if(preg_match("/\'>(.*?)<\/a>/ims", $str, $arr)) $val['agent'] = $arr[1];
        // print_r($val); //exit;
        return $val;
    }
    private function parse_location_lat_long($str)
    {
        //<a href="/location/map?lat=-24.3834&long=30.9334&loc=Hoedspruit">Hoedspruit</a>
        $val = array();
        if(preg_match("/lat=(.*?)&/ims", $str, $arr)) $val['lat'] = $arr[1];
        if(preg_match("/long=(.*?)&/ims", $str, $arr)) $val['long'] = $arr[1];
        if(preg_match("/\">(.*?)<\/a>/ims", $str, $arr)) $val['location'] = $arr[1];
        // print_r($val); //exit;
        return $val;
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
    private function write_agent($a)
    {
        $r = new \eol_schema\Agent();
        $r->term_name       = $a['agent'];
        $r->agentRole       = 'recorder';
        $r->term_homepage   = $this->domain.$a['homepage'];
        $r->identifier      = md5("$r->term_name|$r->agentRole|$r->term_homepage");
        if(!isset($this->agent_ids[$r->identifier])) {
           $this->agent_ids[$r->identifier] = '';
           $this->archive_builder->write_object_to_file($r);
        }
        return $r->identifier;
    }
}
?>