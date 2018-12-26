<?php
namespace php_active_record;
/* connector: [fao_species.php] */
class FAOSpeciesAPI
{
    function __construct($folder = null)
    {
        $this->resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->download_options = array(
            'resource_id'        => 'FAO',                              //resource_id here is just a folder name in cache
            'expire_seconds'     => false,
            'download_wait_time' => 1000000, 'timeout' => 60*5, 'download_attempts' => 1, 'delay_in_minutes' => 1, 'cache' => 1);
        $this->debug = array();
        $this->species_list = "http://www.fao.org/figis/ws/factsheets/domain/species/";
        // $this->factsheet_page = "http://www.fao.org/fishery/species/the_id/en";
        $this->local_species_page = "http://localhost/cp_new/FAO_species_catalog/www.fao.org/fishery/species/the_id/en.html";
    }
    function start()
    {
        $ids = self::get_ids(); echo "\n".count($ids)."\n";
        foreach($ids as $id) {
            $rec = self::assemble_record($id);
            exit("\n-stopx-\n");
        }
    }
    private function assemble_record($id)
    {
        $url = str_replace("the_id", $id, $this->local_species_page);
        echo "\n$url\n";
        if($html = Functions::lookup_with_cache($url, $this->download_options)) {
            $html = str_replace(array("\n"), "<p>", $html);
            $html = str_replace(array("\t"), "<br>", $html);
            // echo $html; exit;
            $html = Functions::remove_whitespace($html);
            $sections = array("FAO Names", "Diagnostic Features", "Geographical Distribution", "Habitat and Biology", "Size", "Interest to Fisheries", "Local Names", "Source of Information");
            foreach($sections as $section) {
                if(preg_match("/>$section<(.*?)bgcolor=\"#6699ff\" align=\"left\"/ims", $html, $arr)) {
                    $str = "<".str_replace("<br>", " ", $arr[1]);
                    $str = Functions::remove_whitespace($str);
                    $str = strip_tags($str, "<p><i>");
                    echo "\n[$section]---------------------\n$str\n---------------------\n";
                }
            }
        }
    }
    private function get_ids()
    {
        $xml = Functions::lookup_with_cache($this->species_list, $this->download_options);
        if(preg_match_all("/factsheet=\"(.*?)\"/ims", $xml, $arr)) {
            $ids = array_unique($arr[1]);
            return $ids;
        }
    }
    function utility_SiteSucker()
    {
        $ids = self::get_ids();
        echo "\n".count($ids)."\n";
        /* just one-time - save the output to: http://localhost/fao.html. Then this url will be inputed to SiteSucker. Saved in Desktop/FAO/FAO_Species.suck
        $html = '';
        foreach($ids as $id) $html .= '<br><a href="http://www.fao.org/fishery/species/'.$id.'/en">'.$id.'</a>';
        echo $html; exit;
        */
        exit("\n-end-\n");
    }

}
?>