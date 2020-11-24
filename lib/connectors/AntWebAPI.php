<?php
namespace php_active_record;
/* connector: [24_new] */
class AntWebAPI
{
    public function __construct($folder)
    {
        $this->resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->taxa_ids             = array();
        $this->taxa_reference_ids   = array(); // $this->taxa_reference_ids[taxon_id] = reference_ids
        $this->object_ids           = array();
        $this->object_reference_ids = array();
        $this->object_agent_ids     = array();
        $this->reference_ids        = array();
        $this->agent_ids            = array();
        $this->download_options = array('resource_id' => 24, 'timeout' => 172800, 'expire_seconds' => 60*60*24*45, 'download_wait_time' => 2000000); // expire_seconds = every 45 days in normal operation
        $this->download_options['expire_seconds'] = false; //doesn't expire
        
        $this->page['all_taxa'] = 'https://www.antweb.org/taxonomicPage.do?rank=species';
    }
    function start()
    {
        $options = $this->download_options;
        $options['expire_seconds'] = false;
        if($html = Functions::lookup_with_cache($this->page['all_taxa'], $options)) {
            // echo $html; exit;
            if(preg_match_all("/<div class=\"sd_data\">(.*?)<div class=\"clear\"><\/div>/ims", $html, $arr)) {
                foreach($arr[1] as $str) {
                    if(preg_match_all("/<div (.*?)<\/div>/ims", $str, $arr2)) {
                        $rec = array_map('trim', $arr2[1]);
                        // print_r($rec);
                        /*Array(
                            [0] => class="sd_name pad">
                            <a href='https://www.antweb.org/common/statusDisplayPage.jsp' target="new"> 
                            <img src="https://www.antweb.org/image/valid_name.png" border="0" title="Valid name.  ">
                            </a>
                            <img src="https://www.antweb.org/image/1x1.gif" width="11" height="12" border="0">
                            <img src="https://www.antweb.org/image/1x1.gif" width="11" height="12" border="0">
                            <img src="https://www.antweb.org/image/1x1.gif" width="11" height="12" border="0">
                            <a href="https://www.antweb.org/description.do?genus=xenomyrmex&species=panamanus&rank=species&project=allantwebants">Xenomyrmex panamanus</a>
                            [1] => class="list_extras author_date">(Wheeler, 1922)
                            [2] => class="list_extras specimens"> <a href='https://www.antweb.org/browse.do?genus=xenomyrmex&species=panamanus&rank=species&project=allantwebants'><span class='numbers'>15</span> Specimens</a>
                            [3] => class="list_extras images">No Images
                            [4] => class="list_extras map">
                            <a href="bigMap.do?taxonName=myrmicinaexenomyrmex panamanus">Map</a>
                            [5] => class="list_extras source">
                            <a target='new' href='http://www.antcat.org/catalog/451293'>Antcat</a>
                        )*/
                        if(stripos($rec[0], "Valid name") !== false) { //string is found
                            $rek = array();
                            if(preg_match("/allantwebants\">(.*?)<\/a>/ims", $rec[0], $arr3)) $rek['sciname'] = str_replace(array('&dagger;'), '', $arr3[1]);
                            if(preg_match("/description\.do\?(.*?)\">/ims", $rec[0], $arr3)) $rek['source_url'] = 'https://www.antweb.org/description.do?'.$arr3[1];

                            if($rek['sciname'] == 'Acromyrmex octospinosus') {
                            // if($rek['sciname'] == 'Acanthognathus ocellatus') {
                                $rek = self::parse_summary_page($rek);
                                print_r($rek); exit;
                            }
                            // $rek = self::parse_summary_page($rek);
                        }
                        
                    }
                }
            }
        }
        print_r($this->debug);
    }
    private function format_html_string($str)
    {
        $str = strip_tags($str,'<em><i><span><p><a>');
        // \t --- chr(9) tab key
        // \r --- chr(13) = Carriage Return - (moves cursor to lefttmost side)
        // \n --- chr(10) = New Line (drops cursor down one line) 
        // $str = str_replace(array("\n", chr(10)), "<br>", $str);
        // $str = str_replace(array("\r", chr(13)), "<br>", $str);
        // $str = str_replace(array("\t", chr(9)), "", $str);
        $str = Functions::remove_whitespace(trim($str));
        $str = str_replace(array("<p></p>"), "", $str);
        return $str;
    }
    private function parse_summary_page($rek)
    {
        if($html = Functions::lookup_with_cache($rek['source_url'], $this->download_options)) {
            
            // phylum:arthropoda class:insecta order:hymenoptera family:formicidae 
            if(preg_match("/phylum\:(.*?) /ims", $html, $arr)) $rek['ancestry']['phylum'] = ucfirst($arr[1]);
            if(preg_match("/class\:(.*?) /ims", $html, $arr)) $rek['ancestry']['class'] = ucfirst($arr[1]);
            if(preg_match("/order\:(.*?) /ims", $html, $arr)) $rek['ancestry']['order'] = ucfirst($arr[1]);
            if(preg_match("/family\:(.*?) /ims", $html, $arr)) $rek['ancestry']['family'] = ucfirst($arr[1]);
            
            $html = str_replace("// Distribution", "<!--", $html);
            
            if(preg_match("/<h3 style=\"float\:left\;\">Distribution Notes\:<\/h3>(.*?)<\!\-\-/ims", $html, $arr)) {
                $rek['Distribution_Notes'] = self::format_html_string($arr[1]);
                // print_r($rek); exit;
            }
            if(preg_match("/<h3 style=\"float\:left\;\">Identification\:<\/h3>(.*?)<\!\-\-/ims", $html, $arr)) {
                $rek['Identification'] = self::format_html_string($arr[1]);;
                // print_r($rek); exit;
            }
            if(preg_match("/<h3 style=\"float\:left\;\">Overview\:<\/h3>(.*?)<\!\-\-/ims", $html, $arr)) {
                $rek['Overview'] = self::format_html_string($arr[1]);
                // print_r($rek); exit;
            }
            if(preg_match("/<h3 style=\"float\:left\;\">Biology\:<\/h3>(.*?)<\!\-\-/ims", $html, $arr)) {
                $rek['Biology'] = self::format_html_string($arr[1]);
                // print_r($rek); exit;
            }
            
            $complete = self::complete_header('<h2>Taxonomic History ', '<\/h2>', $html);
            // <h2>Taxonomic History (provided by Barry Bolton, 2020)</h2>
            // exit("\n$complete\n".preg_quote($complete,"/")."\n");
            if(preg_match("/".preg_quote($complete,"/")."(.*?)<\!\-\-/ims", $html, $arr)) {
                $rek['Taxonomic History'] = self::format_html_string($arr[1]);
                // print_r($rek); exit;
            }
            // print_r($rek); //exit;
        }
        return $rek;
    }
    private function complete_header($start, $end, $html)
    {
        if(preg_match("/".$start."(.*?)".$end."/ims", $html, $arr)) return $start.$arr[1].str_replace('<\/h2>', '</h2>', $end);
    }
}
?>