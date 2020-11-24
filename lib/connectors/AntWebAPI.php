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
                        print_r($rec);
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

                            if($rek['sciname'] == 'Acromyrmex octospinosus')     $rek = self::parse_summary_page($rek);
                            // $rek = self::parse_summary_page($rek);
                            // print_r($rek);
                        }
                        
                    }
                }
            }
        }
        print_r($this->debug);
    }
    private function parse_summary_page($rek)
    {
        if($html = Functions::lookup_with_cache($rek['source_url'], $this->download_options)) {
            
            $html = str_replace("// Distribution", "<!--", $html);
            
            if(preg_match("/<h3 style=\"float\:left\;\">Distribution Notes\:<\/h3>(.*?)<\!\-\-/ims", $html, $arr)) {
                $rek['Dist_notes'] = Functions::remove_whitespace(strip_tags($arr[1],'<em><span><p>'));
                // print_r($rek); exit;
            }
            if(preg_match("/<h3 style=\"float\:left\;\">Identification\:<\/h3>(.*?)<\!\-\-/ims", $html, $arr)) {
                $rek['Identification'] = Functions::remove_whitespace(strip_tags($arr[1],'<em><span><p>'));
                // print_r($rek); exit;
            }
            if(preg_match("/<h3 style=\"float\:left\;\">Overview\:<\/h3>(.*?)<\!\-\-/ims", $html, $arr)) {
                $rek['Overview'] = Functions::remove_whitespace(strip_tags($arr[1],'<em><span><p>'));
                print_r($rek); exit;
            }
            if(preg_match("/<h3 style=\"float\:left\;\">Biology\:<\/h3>(.*?)<\!\-\-/ims", $html, $arr)) {
                $rek['Biology'] = Functions::remove_whitespace(strip_tags($arr[1],'<em><span><p>'));
                print_r($rek); exit;
            }
            
        }
    }
}
?>