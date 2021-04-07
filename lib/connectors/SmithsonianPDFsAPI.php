<?php
namespace php_active_record;
/* connector: [process_SI_pdfs.php]
https://docs.google.com/spreadsheets/d/11m5Wxj9NyYfd38LcRvX7VxTq8ew7pSDRQnQ88MKWPZY/edit?ts=606c9760#gid=0
-> list of patterns compiled by Jen
*/
class SmithsonianPDFsAPI
{
    public function __construct($folder)
    {
        $this->resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->download_options = array('resource_id' => "SI", 'timeout' => 172800, 'expire_seconds' => false, 'download_wait_time' => 2000000);
        $this->debug = array();
        // https://repository.si.edu/handle/10088/5097/browse?rpp=20&sort_by=2&type=dateissued&offset=0&etal=-1&order=ASC
        // https://repository.si.edu/handle/10088/5097/browse?rpp=20&sort_by=2&type=dateissued&offset=20&etal=-1&order=ASC
        $this->web['PDFs per page'] = "https://repository.si.edu/handle/10088/5097/browse?rpp=20&sort_by=2&type=dateissued&offset=NUM_OFFSET&etal=-1&order=ASC";
        $this->web['domain'] = 'https://repository.si.edu';
        if(Functions::is_production()) $this->path['working_dir'] = '/extra/other_files/Smithsonian/epub_'.$this->resource_id.'/';
        else                           $this->path['working_dir'] = '/Volumes/AKiTiO4/other_files/Smithsonian/epub_'.$this->resource_id.'/';
        if(!is_dir($this->path['working_dir'])) mkdir($this->path['working_dir']);
        $this->PDFs_that_are_lists = array('xSCtZ-0011', 'xSCtZ-0033');
    }
    function start()
    {
        // /* Initialize other libraries
        require_library('connectors/ParseListTypeAPI');
        require_library('connectors/ParseUnstructuredTextAPI'); $this->func_ParseUnstructured = new ParseUnstructuredTextAPI();
        require_library('connectors/ConvertioAPI');             $this->func_Convertio = new ConvertioAPI();
        // */
        self::process_all_pdfs_for_a_repository(); //includes conversion of .epub to .txt AND generation of filename_tagged.txt.
        /* un-comment in real operation
        self::generate_dwca_for_a_repository();
        */
        $this->archive_builder->finalize(true);
        if($this->debug) print_r($this->debug);
    }
    private function process_all_pdfs_for_a_repository()
    {
        $pdfs_info = self::get_all_pdfs();
        // print_r($pdfs_info); exit;
        /*Array(
            [0] => Array(
                    [url] => https://repository.si.edu//handle/10088/5292
                    [title] => Recent ostracodes of the family Pontocyprididae chiefly from the Indian Ocean)
            [1] => Array(
                    [url] => https://repository.si.edu//handle/10088/5349
                    [title] => Deep-sea Cerviniidae (Copepoda: Harpacticoida) from the Western Indian Ocean, collected with RV Anton Bruun in 1964)
        */
        // /* Utility report for Jen - one time run
        $this->ctr = 0;
        $this->WRITE = fopen(CONTENT_RESOURCE_LOCAL_PATH."/Smithsonian_Contributions_to_Zoology.txt", "w"); //initialize
        $arr = array('Title', "URL", 'Citation', 'DOI');
        fwrite($this->WRITE, implode("\t", $arr)."\n");
        // */
        $i = 0;
        foreach($pdfs_info as $info) { $i++;
            // if(self::valid_pdf($info['title'])) {} //no longer filters our titles with word "checklist"
            self::process_a_pdf($info);
            // print_r($info);
            // if($i == 2) break; //debug only Mac Mini
            // if($i == 20) break; //debug only eol-archive
        }
        // exit("\n-end 1 repository-\n"); //debug only

        // /* Utility report for Jen - one time run
        fclose($this->WRITE);
        // */
    }
    private function process_a_pdf($info)
    {   //print_r($info); exit;
        /*Array(
            [url] => https://repository.si.edu//handle/10088/5292
            [title] => Recent ostracodes of the family Pontocyprididae chiefly from the Indian Ocean
        )*/
        
        $epub_info = self::get_epub_info($info['url']); //within this where $this->meta is generated
        // print_r($epub_info); print_r($this->meta); exit("\n$this->resource_id\n"); //good debug
        // /* Utility report for Jen - one time run
        $w = array();
        if($info['title'] == $this->meta[$epub_info['pdf_id']]['dc.title']) {
            $title = $info['title'];
            $url1 = $info['url'];
            $citation = $this->meta[$epub_info['pdf_id']]['bibliographicCitation'];
            $url2 = $this->meta[$epub_info['pdf_id']]['dc.relation.url'];
            $arr = array($title, $url1, $citation, $url2);
            fwrite($this->WRITE, implode("\t", $arr)."\n");
        }
        else {
            echo "\n===========================================================\n";
            print_r($info); print_r($epub_info); print_r($this->meta[$epub_info['pdf_id']]);
            exit("\ntitles not the same\n");
        }
        return;
        // */
        /*Array(
            [pdf_id] => SCtZ-0007
            [filename] => SCtZ-0007.epub
            [url] => https://repository.si.edu/bitstream/handle/10088/5292/SCtZ-0007.epub
            [checklistYN] => 0
        )
        Array(
            [SCtZ-0007] => Array(
                    [bibliographicCitation] => Maddocks, Rosalie F. 1969. "Recent ostracodes of the family Pontocyprididae chiefly from the Indian Ocean." Smithsonian Contributions to Zoology. 1-56. https://doi.org/10.5479/si.00810282.7
                    [dc.relation.url] => http://dx.doi.org/10.5479/si.00810282.7
                    [dc.title] => Recent ostracodes of the family Pontocyprididae chiefly from the Indian Ocean
                )
        )*/
        if(in_array($epub_info['pdf_id'], $this->PDFs_that_are_lists)) {
            echo "\n[".$epub_info['pdf_id']."] ".$info['title']." - IS A LIST, NOT SPECIES-DESCRIPTION-TYPE\n";
            return;
        }
        
        /*Array(
            [filename] => SCtZ-0007.epub
            [url] => https://repository.si.edu/bitstream/handle/10088/5292/SCtZ-0007.epub
        )*/
        self::download_epub($epub_info);
        $ret = self::convert_epub_to_txt($epub_info); //print_r($ret);
        /*Array(
            [source] => /Volumes/AKiTiO4/other_files/Smithsonian/epub/SCtZ-0007/SCtZ-0007.txt
            [resource_working_dir] => /Volumes/AKiTiO4/other_files/Smithsonian/epub/SCtZ-0007
        )*/

        //start preparing $input to next step: parsing of txt file
        // $input = array('filename' => 'SCtZ-0293.txt', 'lines_before_and_after_sciname' => 2);
        // $input = array('filename' => 'SCtZ-0007.txt', 'lines_before_and_after_sciname' => 1);
        
        $this->lines_before_and_after_sciname['SCtZ-0293.txt'] = 2;
        $this->lines_before_and_after_sciname['SCtZ-0007.txt'] = 1;
        $this->lines_before_and_after_sciname['SCtZ-0029.txt'] = 2;

        // /* working OK -- un-comment in real operation. Comment during caching in eol-archive
        $txt_filename = str_replace(".epub", ".txt", $epub_info['filename']);
        if($LBAAS = @$this->lines_before_and_after_sciname[$txt_filename]) {}
        else {
            // exit("\n[lines_before_and_after_sciname] not yet initialized for [$txt_filename]\n");
            $LBAAS = 2;
        }
        $input = array('filename' => $txt_filename, 'lines_before_and_after_sciname' => $LBAAS);
        $input['epub_output_txts_dir'] = $ret['resource_working_dir'];
        if($input['filename']) $this->func_ParseUnstructured->parse_pdftotext_result($input); //this will generate the xxxxxx_tagged.txt file
        else {
            echo "\n-----------------\nWarning: no input epub file\n";
            print_r($info); print_r($epub_info); print_r($input);
            echo "\n-----------------\n";
            $this->debug['No epub'][] = $info;
        }
        // */
        // exit("\n-done 1 pdf'\n"); //debug only
    }
    private function convert_epub_to_txt($epub_info)
    {   // print_r($epub_info); exit("\nelix\n");
        /*Array(
            [filename] => SCtZ-0007.epub
            [url] => https://repository.si.edu/bitstream/handle/10088/5292/SCtZ-0007.epub
        )*/
        $source = self::create_epub_filename_path($epub_info);
        $destination = str_replace(".epub", ".txt", $source);
        $filename = $epub_info['filename']; //'SCtZ-0007.epub';
        // echo("\nsource: [$source]\ndestination: [$destination]\nfilename: [$filename]\n"); exit; //good debug
        /*
        source:         [/Volumes/AKiTiO4/other_files/Smithsonian/epub/SCtZ-0007/SCtZ-0007.epub]
        destination:    [/Volumes/AKiTiO4/other_files/Smithsonian/epub/SCtZ-0007/SCtZ-0007.txt]
        filename:       [SCtZ-0007.epub]
        */
        
        // get resource_working_dir
        $folder = pathinfo($epub_info['url'], PATHINFO_FILENAME);
        $resource_working_dir = $this->path['working_dir'].$folder."/";
        
        if(file_exists($destination)) {
            echo "\ntxt file already exists: [$destination]\n";
            return array('source' => $destination, 'resource_working_dir' => $resource_working_dir);
        }
        //start Convertio
        $api_id = $this->func_Convertio->initialize_request();
        $this->func_Convertio->upload_local_file($source, $filename, $api_id);
        sleep(60);
        if($obj = $this->func_Convertio->check_status($api_id)) {
            if($txt_url = $obj->data->output->url) {
                $cmd = "wget -nc ".$txt_url." -O $destination";
                $cmd .= " 2>&1";
                $json = shell_exec($cmd);
                if(file_exists($destination)) echo "\n".$destination." downloaded successfully from Convertio.\n";
                else                          exit("\nERROR: can not download ".$epub_info['filename']."\n");
            }
        }
        return array('source' => $destination, 'resource_working_dir' => $resource_working_dir);
        /*stdClass Object(
            [code] => 200
            [status] => ok
            [data] => stdClass Object(
                    [id] => 31d5363e37189a0650835c5c9a26d2b2
                    [step] => finish
                    [step_percent] => 100
                    [minutes] => 1
                    [output] => stdClass Object(
                            [url] => https://s183.convertio.me/p/aiRl8zoMB5y_RX5c0RY4Fw/0faab539f8de23cd027d32cbddd6b620/SCtZ-0007.txt
                            [size] => 157826
                        )
                )
        )*/
    }
    private function download_epub($epub_info)
    {
        $destination = self::create_epub_filename_path($epub_info);
        if(file_exists($destination)) {
            echo "\n".$epub_info['filename']." was already downloaded from Smithsonian.\n";
            return;
        }
        $cmd = "wget -nc ".$epub_info['url']." -O $destination";
        $cmd .= " 2>&1";
        $json = shell_exec($cmd);
        if(file_exists($destination)) echo "\n".$epub_info['filename']." downloaded successfully from Smithsonian.\n";
        else                          exit("\nERROR: can not download ".$epub_info['filename']."\n");
    }
    private function create_epub_filename_path($epub_info)
    {
        $folder = pathinfo($epub_info['url'], PATHINFO_FILENAME);
        $dir = $this->path['working_dir']."$folder";
        if(!is_dir($dir)) mkdir($dir);
        return $dir."/".$epub_info['filename'];
    }
    private function get_epub_info($url)
    {   /*
        <div style="height: 80px;" class="file-link">
        <a href="/bitstream/handle/10088/5551/SCtZ-0081.epub?sequence=2&amp;isAllowed=y">View/<wbr xmlns:i18n="http://apache.org/cocoon/i18n/2.1" />Open</a>
        </div>
        */
        if($html = Functions::lookup_with_cache($url, $this->download_options)) {
            if(preg_match_all("/".preg_quote('class="file-link">', '/')."(.*?)<\/div>/ims", $html, $a)) {
                // print_r($a[1]); exit;
                foreach($a[1] as $line) {
                    if(stripos($line, ".epub") !== false) { //string is found
                        // exit("\n$line\n");
                        /* <a href="/bitstream/handle/10088/5292/SCtZ-0007.epub?sequence=3&amp;isAllowed=y">View/<wbr xmlns:i18n="http://apache.org/cocoon/i18n/2.1" />Open</a> */
                        if(preg_match("/href=\"(.*?)\"/ims", $line, $a)) {
                            $tmp = $this->web['domain'].$a[1];
                            $ret = array();
                            $ret['pdf_id'] = pathinfo($tmp, PATHINFO_FILENAME);
                            // /* Utility report for Jen - one time run
                            $ret['pdf_id'] = @$this->ctr++;
                            // */
                            $ret['filename'] = pathinfo($tmp, PATHINFO_FILENAME).".epub";
                            $ret['url'] = pathinfo($tmp, PATHINFO_DIRNAME)."/".$ret['filename'];
                            // print_r($ret); exit;
                            /*Array(
                                [filename] => SCtZ-0007.epub
                                [url] => https://repository.si.edu/bitstream/handle/10088/5292/SCtZ-0007.epub
                            )*/
                            self::get_metadata_for_pdf($html, $url, pathinfo($ret['filename'], PATHINFO_FILENAME)); //where $this->meta is generated
                            
                            if(self::is_pdf_checklistYN($this->meta[$ret['pdf_id']]['dc.title'])) $ret['checklistYN'] = 1;
                            else $ret['checklistYN'] = 0;
                            return $ret;
                        }
                    }
                }
            }
        }
    }
    private function get_all_pdfs()
    {
        $total_pdfs = self::get_total_pdfs(); //exit("\n[$total_pdfs]\n");
        $total_pages = ceil($total_pdfs/20);
        // $total_pages = ceil(661/20); //total_pages should be 34 OK
        echo "\ntotal pages: [$total_pages]\n"; //exit;
        $page = 0; $offset = 0;
        while($page < $total_pages) { $page++; echo "\n[$page][$offset]\n";
            $url = str_replace("NUM_OFFSET", $offset, $this->web['PDFs per page']);
            if($html = Functions::lookup_with_cache($url, $this->download_options)) {
                /*
                <div class="artifact-title">
                <a href="/handle/10088/5292">Recent ostracodes of the family Pontocyprididae chiefly from the Indian Ocean</a>
                <span class="Z3988"
                */
                if(preg_match_all("/".preg_quote('<div class="artifact-title">', '/')."(.*?)<span/ims", $html, $a)) {
                    $temp = array_map('trim', $a[1]);
                    // print_r($temp); exit;
                    /*Array(
                        [0] => <a href="/handle/10088/5292">Recent ostracodes of the family Pontocyprididae chiefly from the Indian Ocean</a>
                        [1] => <a href="/handle/10088/5349">Deep-sea Cerviniidae (Copepoda: Harpacticoida) from the Western Indian Ocean, collected with RV Anton Bruun in 1964</a>
                    */
                    foreach($temp as $t) {
                        $rec = array();
                        if(preg_match("/href=\"(.*?)\"/ims", $t, $a)) $rec['url'] = $this->web['domain']."/".$a[1];
                        if(preg_match("/\">(.*?)<\/a>/ims", $t, $a)) $rec['title'] = $a[1];
                        $final[] = $rec;
                    }
                }
            }
            $offset = $offset + 20;
            // if($page == 5) break; //debug only
        }
        return $final;
    }
    private function get_total_pdfs()
    {
        $url = str_replace("NUM_OFFSET", 0, $this->web['PDFs per page']);
        if($html = Functions::lookup_with_cache($url, $this->download_options)) {
            /*Now showing items 1-20 of 660</p>*/
            if(preg_match("/Now showing items 1-20 of (.*?)<\/p>/ims", $html, $a)) return trim($a[1]);
        }
    }
    private function is_pdf_checklistYN($title)
    {
        if(stripos($title, "checklist") !== false) return true; //string is found
        return false;
    }
    private function get_metadata_for_pdf($html, $url, $pdf_id)
    {   /*
        <meta name="DCTERMS.bibliographicCitation" content="Maddocks, Rosalie F. 1969. &quot;&lt;a href=&quot;http%3A%2F%2Fdx.doi.org%2F10.5479%2Fsi.00810282.7&quot;&gt;Recent ostracodes of the family Pontocyprididae chiefly from the Indian Ocean&lt;/a&gt;.&quot; &lt;em&gt;Smithsonian Contributions to Zoology&lt;/em&gt;. 1&amp;ndash;56. &lt;a href=&quot;https://doi.org/10.5479/si.00810282.7&quot;&gt;https://doi.org/10.5479/si.00810282.7&lt;/a&gt;" xml:lang="en" />
        <meta name="DC.relation" content="http://dx.doi.org/10.5479/si.00810282.7" />
        https://repository.si.edu//handle/10088/5292
        */
        /* not needed anymore $pdf_id was created above already.
        $left = "/handle/";
        if(preg_match("/".preg_quote($left, '/')."(.*?)elicha/ims", $url."elicha", $a)) $pdf_id = str_replace("/", "_", $a[1]);
        */
        $left = '<meta name="DCTERMS.bibliographicCitation" content="';
        if(preg_match("/".preg_quote($left, '/')."(.*?)\"/ims", $html, $a)) {
            $biblio = $a[1];                                //echo "\n$biblio\n";
            $biblio = urldecode($biblio);                   //echo "\n$biblio\n";
            $biblio = html_entity_decode($biblio);          //echo "\n$biblio\n";
            $biblio = strip_tags($biblio);                  //echo "\n$biblio\n";
            $biblio = str_replace("&ndash;", "-", $biblio); //echo "\n$biblio\n";
            $this->meta[$pdf_id]['bibliographicCitation'] = $biblio;
        }
        $left = '<meta name="DC.relation" content="';
        if(preg_match("/".preg_quote($left, '/')."(.*?)\"/ims", $html, $a)) $this->meta[$pdf_id]['dc.relation.url'] = $a[1];
        $left = '<meta name="DC.title" content="';
        if(preg_match("/".preg_quote($left, '/')."(.*?)\"/ims", $html, $a)) $this->meta[$pdf_id]['dc.title'] = $a[1];
        // print_r($this->meta); exit("\n$url\n");;
    }
    private function generate_dwca_for_a_repository()
    {
        foreach(glob($this->path['working_dir'] . "*") as $folder) {
            $txt_filename = pathinfo($folder, PATHINFO_BASENAME)."_tagged.txt";
            $txt_filename = $folder."/".$txt_filename;
            echo "\n$txt_filename";
            if(file_exists($txt_filename)) { echo " - OK\n";
                $pdf_id = pathinfo($folder, PATHINFO_BASENAME);
                self::process_a_txt_file($txt_filename, $pdf_id);
            }
            else echo " - tagged version not yet generated\n";
        }
        // exit("\nstop munax\n");
    }
    private function process_a_txt_file($txt_filename, $pdf_id)
    {   /*
        <sciname='Pontostratiotes scotti Brodskaya, 1959'> Pontostratiotes scotti Brodskaya, 1959
        </sciname>
        */
        $contents = file_get_contents($txt_filename);;
        if(preg_match_all("/<sciname=(.*?)<\/sciname>/ims", $contents, $a)) {
            foreach($a[1] as $str) {
                $rec = array();
                $rec['pdf_id'] = $pdf_id;
                if(preg_match("/\'(.*?)\'/ims", $str, $a2)) $rec['sciname'] = self::clean_sciname(trim($a2[1]));
                if(preg_match("/>(.*?)elicha/ims", $str."elicha", $a2)) {
                    $tmp = Functions::remove_whitespace(trim($a2[1]));
                    $tmp = str_replace("\n\n\n\n", "\n\n", $tmp);
                    $tmp = str_replace("\n\n\n", "\n\n", $tmp);
                    $tmp = str_replace("\n\n\n", "\n\n", $tmp);
                    $tmp = str_replace("\n\n\n", "\n\n", $tmp);
                    $tmp = str_replace("\n\n\n", "\n\n", $tmp);
                    $tmp = str_replace("\n", "<br>", $tmp);
                    // echo "\n$tmp\n";
                    $rec['body'] = $tmp;
                } // print_r($rec); //exit;
                if($rec['sciname'] && $rec['body']) self::write_archive($rec);
            }
        }
    }
    private function write_archive($rec)
    {   //write taxon
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID         = md5($rec['sciname']);
        $taxon->scientificName  = $rec['sciname'];
        // $taxon->furtherInformationURL = $t['dc_source'];
        if(!isset($this->taxon_ids[$taxon->taxonID])) {
            $this->archive_builder->write_object_to_file($taxon);
            $this->taxon_ids[$taxon->taxonID] = '';
        }
        //write text object
        $mr = new \eol_schema\MediaResource();
        $mr->taxonID        = $taxon->taxonID;
        $mr->identifier     = md5($rec['body']);
        $mr->type           = 'http://purl.org/dc/dcmitype/Text';
        $mr->language       = 'en';
        $mr->format         = 'text/html';
        $mr->UsageTerms     = 'http://creativecommons.org/licenses/by-nc-sa/3.0/';
        $mr->description    = $rec['body'];
        $mr->CVterm         = 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Description'; //ComprehensiveDescription

        $mr->furtherInformationURL = $this->meta[$rec['pdf_id']]['dc.relation.url'];
        $mr->bibliographicCitation = $this->meta[$rec['pdf_id']]['bibliographicCitation'];
        // $mr->Owner          = $o['dc_rightsHolder'];
        // $mr->rights         = $o['dc_rights'];
        // $mr->title          = $o['dc_title'];
        if(!isset($this->object_ids[$mr->identifier])) {
            $this->archive_builder->write_object_to_file($mr);
            $this->object_ids[$mr->identifier] = '';
        }
    }
    private function clean_sciname($name)
    {
        $name = str_ireplace(", new species", "", $name);
        $name = str_ireplace(", new subspecies", "", $name);
        return Functions::remove_whitespace($name);
    }
}