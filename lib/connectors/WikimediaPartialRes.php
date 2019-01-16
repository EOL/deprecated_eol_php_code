<?php
namespace php_active_record;
/* connector: [wikimedia_partial.php] 
From request: https://eol-jira.bibalex.org/browse/DATA-1784?focusedCommentId=63160&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-63160
*/
class WikimediaPartialRes
{
    function __construct($folder = NULL, $dwca_file = NULL)
    {
        if($folder) {
            $this->resource_id = $folder;
            $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
            $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        }
        $this->dwca_file = $dwca_file;
        $this->debug = array();
    }
    function generate_partial_wikimedia_resource()
    {
        $tmp_file = CONTENT_RESOURCE_LOCAL_PATH . '/71/media_resource.tab';
        $i = 0;
        foreach(new FileIterator($tmp_file) as $line => $row) {
            $row = Functions::conv_to_utf8($row);
            $i++; 
            if($i == 1) $fields = explode("\t", $row);
            else {
                if(!$row) continue;
                $tmp = explode("\t", $row);
                $rec = array(); $k = 0;
                foreach($fields as $field) {
                    $rec[$field] = $tmp[$k];
                    $k++;
                }
                $rec = array_map('trim', $rec);
                // print_r($rec); exit;
                /*
                http://rs.tdwg.org/ac/terms/accessURI
                http://rs.tdwg.org/audubon_core/subtype
                http://ns.adobe.com/xap/1.0/rights/UsageTerms
                http://ns.adobe.com/xap/1.0/rights/Owner
                http://eol.org/schema/agent/agentID
                Array(
                    [identifier] => 8327010
                    [taxonID] => Q140
                    [type] => http://purl.org/dc/dcmitype/StillImage
                    [subtype] => map
                    [format] => image/png
                    [title] => File:Lion distribution.png
                    [description] => Geographical distribution of lions. Red (and blue) shows areas historically inhabited, blue shows areas currently inhabited.
                    [accessURI] => https://upload.wikimedia.org/wikipedia/commons/b/b9/Lion_distribution.png
                    [furtherInformationURL] => https://commons.wikimedia.org/wiki/File:Lion distribution.png
                    [language] => en
                    [UsageTerms] => http://creativecommons.org/licenses/publicdomain/
                    [Owner] => 
                    [agentID] => 101547869c8c59ff4d957018c28441f8
                )*/
                $mr = new \eol_schema\MediaResource();
                $mr->accessURI  = $rec['accessURI'];
                $mr->subtype    = $rec['subtype'];
                $mr->UsageTerms = $rec['UsageTerms'];
                $mr->Owner      = $rec['Owner'];
                $mr->agentID    = $rec['agentID'];
                if(!isset($this->object_ids[$mr->accessURI])) {
                    $this->archive_builder->write_object_to_file($mr);
                    $this->object_ids[$mr->accessURI] = '';
                }
            }
            // if($i >= 10) break; //debug
        }
        $this->archive_builder->finalize(true);
        self::move_files();
    }
    private function move_files()
    {
        $source = CONTENT_RESOURCE_LOCAL_PATH . '/wikimedia_partial_working/media_resource.tab';
        $destination = CONTENT_RESOURCE_LOCAL_PATH . '/wikimedia_partial';
        if(!is_dir($destination)) mkdir($destination);
        $destination = CONTENT_RESOURCE_LOCAL_PATH . '/wikimedia_partial/media_resource.tab';
        if(copy($source, $destination)) echo "\n - media_resource.tab copied \n";

        $source = CONTENT_RESOURCE_LOCAL_PATH . '/71/agent.tab';
        $destination = CONTENT_RESOURCE_LOCAL_PATH . '/wikimedia_partial/agent.tab';
        if(copy($source, $destination)) echo "\n - agent.tab copied \n";
        //create wikimedia_partial.tar.gz
        $command_line = "tar -czf " . CONTENT_RESOURCE_LOCAL_PATH . "wikimedia_partial" . ".tar.gz --directory=" . CONTENT_RESOURCE_LOCAL_PATH . "wikimedia_partial" . " .";
        $output = shell_exec($command_line);
        echo "\n$output\n";
    }
}
?>
