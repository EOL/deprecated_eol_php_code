<?php
namespace php_active_record;
/* connector: [environments_2_eol.php]
*/
class Environments2EOLAPI
{
    function __construct($param)
    {
        print_r($param);
        // if($folder) {
        //     $this->resource_id = $folder;
        //     $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        //     $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        // }
        // $this->debug = array();
        $this->DwCA_URLs['AmphibiaWeb text'] = 'https://editors.eol.org/eol_php_code/applications/content_server/resources/21.tar.gz';
        
        $this->num_of_saved_recs_bef_run_tagger = 1000; //1000 orig;
        $this->root_path            = '/u/scripts/vangelis_tagger/';
        $this->eol_tagger_path      = $this->root_path.'eol_tagger/';
        $this->text_data_path       = $this->root_path.'test_text_data/';
        $this->eol_scripts_path     = $this->root_path.'eol_scripts/';
        $this->eol_tags_path        = $this->root_path.'eol_tags/';
        $this->eol_tags_destination = $this->eol_tags_path.'eol_tags.tsv';
    }
    function gen_txt_files_4_articles($resource)
    {
        self::initialize_files();
        $info = self::parse_dwca($resource); // print_r($info); exit;
        $tables = $info['harvester']->tables;
        self::process_table($tables['http://eol.org/schema/media/document'][0]);
        print_r($this->debug);
        self::gen_noParentTerms();
        recursive_rmdir($info['temp_dir']); //remove temp folder used for DwCA parsing
    }
    private function initialize_files()
    {
        $files = array($this->eol_tags_destination);
        foreach($files as $file) {
            if($f = Functions::file_open($file, "w")) {
                fclose($f);
                echo "\nFile truncated: [$file]\n";
            }
        }
    }
    private function parse_dwca($resource, $download_options = array('timeout' => 172800, 'expire_seconds' => 60*60*24*30))
    {   
        // /* un-comment in real operation
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($this->DwCA_URLs[$resource], "meta.xml", $download_options); //true 'expire_seconds' means it will re-download, will NOT use cache. Set TRUE when developing
        // print_r($paths); exit("\n-exit muna-\n");
        // */
        /* development only
        $paths = Array("archive_path" => "/Volumes/AKiTiO4/eol_php_code_tmp/dir_64006/",
                       "temp_dir" => "/Volumes/AKiTiO4/eol_php_code_tmp/dir_64006/");
        */
        $archive_path = $paths['archive_path'];
        $temp_dir = $paths['temp_dir'];
        $harvester = new ContentArchiveReader(NULL, $archive_path);
        $tables = $harvester->tables;
        $index = array_keys($tables);
        if(!($tables["http://rs.tdwg.org/dwc/terms/taxon"][0]->fields)) { // take note the index key is all lower case
            debug("Invalid archive file. Program will terminate.");
            return false;
        }
        return array("harvester" => $harvester, "temp_dir" => $temp_dir, "tables" => $tables, "index" => $index);
    }
    private function process_table($meta)
    {   //print_r($meta);
        echo "\nprocess media tab...\n";
        $i = 0; $saved = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 1000) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            $row = Functions::conv_to_utf8($row); //possibly to fix special chars
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            }
            // print_r($rec); exit;
            if(self::valid_record($rec)) {
                $this->debug['subjects'][$rec['http://iptc.org/std/Iptc4xmpExt/1.0/xmlns/CVterm']] = '';
                $this->debug['titles'][$rec['http://purl.org/dc/terms/title']] = '';
                $saved++;
                self::save_article_2_txtfile($rec);
                if($saved == $this->num_of_saved_recs_bef_run_tagger) {
                    self::run_environment_tagger();
                    $saved = 0;
                }
            }
            // if($i >= 100) break; //debug only
        }
        echo "\nLast round...\n";
        echo (count(glob("$this->text_data_path/*")) === 0) ? "\nEmpty!" : "\nNot empty - OK ";
        self::run_environment_tagger(); //process remaining txt files.
        echo (count(glob("$this->text_data_path/*")) === 0) ? "\nEmpty - OK\n" : "\nNot empty!\n";
    }
    private function save_article_2_txtfile($rec)
    {   /* Array(
        [http://purl.org/dc/terms/identifier] => 8687_distribution
        [http://rs.tdwg.org/dwc/terms/taxonID] => 8687
        [http://purl.org/dc/terms/type] => http://purl.org/dc/dcmitype/Text
        [http://purl.org/dc/terms/format] => text/plain
        [http://iptc.org/std/Iptc4xmpExt/1.0/xmlns/CVterm] => http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution
        [http://purl.org/dc/terms/title] => Distribution and Habitat
        [http://purl.org/dc/terms/description] => <p><i>Abavorana nazgul</i> is only known from the mountain, Gunung Jerai, in the state of Kedah on the west coast of Peninsular Malaysia. It is associated with riparian habitats, and can be found near streams. It has been only been found at elevations between 800 – 1200 m (Quah et al. 2017).</p>
        [http://rs.tdwg.org/ac/terms/furtherInformationURL] => http://amphibiaweb.org/cgi/amphib_query?where-genus=Abavorana&where-species=nazgul&account=amphibiaweb
        [http://purl.org/dc/terms/language] => en
        [http://ns.adobe.com/xap/1.0/rights/UsageTerms] => http://creativecommons.org/licenses/by/3.0/
        [http://eol.org/schema/agent/agentID] => 40dafcb8c613187d62bc1033004b43b9
        [http://eol.org/schema/reference/referenceID] => d08a99802fc760abbbfc178a391f9336; 8d5b9dee4f523c6243387c962196b8e0; 4d496c9853b52d6d4ee443b4a6103cca
        )*/
        $basename = $rec['http://rs.tdwg.org/dwc/terms/taxonID']."_-_".$rec['http://purl.org/dc/terms/identifier'];
        $file = $this->text_data_path.$basename.".txt";
        if($f = Functions::file_open($file, "w")) {
            $desc = strip_tags($rec['http://purl.org/dc/terms/description']);
            $desc = trim(Functions::remove_whitespace($desc));
            fwrite($f, $basename."\n".$desc."\n");
            fclose($f);
        }
    }
    private function run_environment_tagger()
    {   echo "\nRun run_environment_tagger()...";
        $current_dir = getcwd(); //get current dir
        chdir($this->eol_tagger_path);
        /*
        ./environments_tagger /u/scripts/vangelis_tagger/test_text_data/ &> /u/scripts/vangelis_tagger/eol_tags/eol_tags.tsv
        */
        $cmd = "./environments_tagger $this->text_data_path &>> $this->eol_tags_destination";
        shell_exec($cmd);
        chdir($current_dir); //go back to current dir
        Functions::delete_temp_files($this->text_data_path, 'txt');
    }
    private function gen_noParentTerms()
    {   echo "\nRun gen_noParentTerms()...\n";
        $current_dir = getcwd(); //get current dir
        chdir($this->root_path);
        /*
        ./eol_scripts/exclude-parents-E.pl eol_tags/eol_tags.tsv eol_scripts/envo_child_parent.tsv > eol_tags/eol_tags_noParentTerms.tsv
        */
        $cmd = "./eol_scripts/exclude-parents-E.pl $this->eol_tags_destination $this->eol_scripts_path"."envo_child_parent.tsv > $this->eol_tags_path"."eol_tags_noParentTerms.tsv";
        shell_exec($cmd);
        chdir($current_dir); //go back to current dir
    }
    private function valid_record($rec)
    {   if($rec['http://purl.org/dc/terms/type'] == 'http://purl.org/dc/dcmitype/Text' &&
           $rec['http://iptc.org/std/Iptc4xmpExt/1.0/xmlns/CVterm'] && $rec['http://purl.org/dc/terms/description'] &&
           $rec['http://rs.tdwg.org/dwc/terms/taxonID'] && $rec['http://purl.org/dc/terms/identifier']) return true;
        else return false;
    }
}
?>
