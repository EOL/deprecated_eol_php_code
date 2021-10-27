<?php
namespace php_active_record;
/* connector: [process_SI_pdfs.php]
https://docs.google.com/spreadsheets/d/11m5Wxj9NyYfd38LcRvX7VxTq8ew7pSDRQnQ88MKWPZY/edit?ts=606c9760#gid=0
-> list of patterns compiled by Jen
*/
class SmithsonianPDFsAPI extends ParseListTypeAPI
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

        $this->web['PDFs per page']['10088_5097'] = "https://repository.si.edu/handle/10088/5097/browse?rpp=20&sort_by=2&type=dateissued&offset=NUM_OFFSET&etal=-1&order=ASC";
        $this->web['PDFs per page']['10088_6943'] = "https://repository.si.edu/handle/10088/6943/browse?rpp=20&sort_by=2&type=dateissued&offset=NUM_OFFSET&etal=-1&order=ASC";

        $this->web['domain'] = 'https://repository.si.edu';
        if(Functions::is_production()) $this->path['working_dir'] = '/extra/other_files/Smithsonian/epub_'.$this->resource_id.'/';
        else                           $this->path['working_dir'] = '/Volumes/AKiTiO4/other_files/Smithsonian/epub_'.$this->resource_id.'/';
        if(!is_dir($this->path['working_dir'])) mkdir($this->path['working_dir']);

        $list_type_from_google_sheet = array('SCtZ-0033', 'SCtZ-0011', 'SCtZ-0010', 'SCtZ-0611', 'SCtZ-0613', 'SCtZ-0609', 'SCtZ-0018',
        'scb-0002');
        $this->PDFs_that_are_lists = array_merge(array('SCtZ-0437'), $list_type_from_google_sheet);
        // SCtZ-0018 - Nearctic Walshiidae: notes and new taxa (Lepidoptera: Gelechioidea). Both list-type and species-sections type
        // SCtZ-0004 - not a list-type
        // SCtZ-0604 - I considered not a list-type but a regular species section type

        $this->PDFs_not_a_monograph = array('SCtZ-0009', 'SCtZ-0107'); //exclude; not a species nor a list type.
        $this->overwrite_tagged_files = true; //orig false means don't overwrite tagged files.
        
        $this->with_epub_count = 0;
        $this->without_epub_count = 0;
        $this->localRunYN = false; //false in normal operation. true during dev. used to force value
    }
    
    // /* used during dev, from parse_unstructured_text.php when working on associations
    function initialize() { require_library('connectors/ParseAssocTypeAPI'); $this->func_Assoc = new ParseAssocTypeAPI(); }
    function archive_builder_finalize() { $this->archive_builder->finalize(true); }
    // */
    
    function start()
    {   // /* Initialize other libraries
        require_library('connectors/ParseListTypeAPI');
        require_library('connectors/ParseUnstructuredTextAPI'); $this->func_ParseUnstructured = new ParseUnstructuredTextAPI();
        require_library('connectors/ConvertioAPI');             $this->func_Convertio = new ConvertioAPI();
        self::initialize();
        // */
        self::clean_repository_of_old_files();
        self::process_all_pdfs_for_a_repository(); //includes conversion of .epub to .txt AND generation of filename_tagged.txt.
        // /* un-comment in real operation
        self::generate_dwca_for_a_repository();
        $this->archive_builder->finalize(true);
        // */
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
        
        /* force value -- good during dev.
        $pdfs_info = array();
        $pdfs_info[] = Array(
                // "url" => "https://repository.si.edu//handle/10088/5495", //SCtZ-0614.epub
                // "title" => "eli is here...");
                
                "url" => "https://repository.si.edu//handle/10088/5322", //SCtZ-0018
                "title" => "eli is here...");
        */
        
        /* Utility report for Jen - one time run
        $this->ctr = 0;
        // $this->WRITE = fopen(CONTENT_RESOURCE_LOCAL_PATH."/Smithsonian_Contributions_to_Zoology.txt", "w"); //initialize
        $this->WRITE = fopen(CONTENT_RESOURCE_LOCAL_PATH."/10088_5097_misfiled_epubs.txt", "w"); //initialize OK
        $arr = array("#", 'Title', "URL", 'DOI', "epub file");
        fwrite($this->WRITE, implode("\t", $arr)."\n");
        */
        $i = 0;
        foreach($pdfs_info as $info) { $i++; echo "\nPDF $i -> \n"; // print_r($info);
            self::process_a_pdf($info); //epub-sensitive
            // self::process_a_pdf_all($info); //epub-INsensitive -> just a utility, used in generating reports
            // if($i == 2) break; //debug only Mac Mini
            // if($i == 20) break; //debug only eol-archive
        }
        /* Utility report for Jen - one time run
        fclose($this->WRITE);
        */
        echo "\nwith_epub_count: $this->with_epub_count\n";
        echo "\nwithout_epub_count: $this->without_epub_count\n";
        // exit("\n-end 1 repository-\n"); //debug only
    }
    private function process_a_pdf($info)
    {   //print_r($info); exit;
        /*Array(
            [url] => https://repository.si.edu//handle/10088/5292
            [title] => Recent ostracodes of the family Pontocyprididae chiefly from the Indian Ocean
        )*/
        
        $epub_info = self::get_epub_info($info['url']); //within this where $this->meta is generated
        // print_r($epub_info); print_r($this->meta); exit("\n$this->resource_id\n"); //good debug
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
        
        // /* Provision to save PDF metadata as json. Although this hasn't been used yet, but a good provision in the future.
        $pdf_id = $epub_info['pdf_id'];
        $json_file = $this->path['working_dir']."$pdf_id/".$pdf_id."_meta.json";
        if(!file_exists($json_file) && is_dir($this->path['working_dir']."$pdf_id/")) {
            $WRITE = fopen($json_file, "w"); //initialize OK
            fwrite($WRITE, json_encode($info));
            fclose($WRITE);
        }
        // */
        
        // /* Provision not to overwrite tagged files
        $pdf_id = $epub_info['pdf_id'];
        $tagged_file = $this->path['working_dir']."$pdf_id/".$pdf_id."_tagged.txt";
        echo "\n[$tagged_file]\n";
        if(!$this->overwrite_tagged_files) {
            if(file_exists($tagged_file)) {echo("\nAlready exists tagged file [$pdf_id], will not overwrite\n"); return;}
            else echo("\nNo tag file yet, will proceed 1 [$tagged_file]..\n");
        }
        else {
            if(file_exists($tagged_file)) {echo("\nAlready exists tagged file [$pdf_id], will overwrite now\n");}
            else echo("\nNo tag file yet, will proceed 2 [$tagged_file]...\n");
        }
        // */
        
        if(in_array($epub_info['pdf_id'], $this->PDFs_not_a_monograph)) return; //Not a taxon nor a list type PDF.
        if(!$epub_info) return;
        
        /* ========================= Utility report for Jen - one time run
        if(in_array($epub_info['pdf_id'], array("SCtZ-0160", "SCtZ-0169", "SCtZ-0150", "SCtZ-0117", "SCtZ-0071", "SCtZ-0077", "SCtZ-0070",
            "SCtZ-0085", "SCtZ-0038", "SCtZ-0028", "SCtZ-0026", "SCtZ-0014", "SCtZ-0005", "SCtZ-0003", "SCtZ-0004", "SCtZ-0018", "SCtZ-0011",
            "SCtZ-0001", "SCtZ-0211", "SCtZ-0177", "SCtZ-0163.1", "SCtZ-0185", "SCtZ-0240", "SCtZ-0219", "SCTZ-0276", "SCtZ-0273",
            "SCTZ-0275", "SCtZ-0278", "SCtZ-0245", "SCtZ-0282", "SCtZ-0235", "SCtZ-0249", "SCtZ-0247", "SCtZ-0218", "SCtZ-0329",
            "SCtZ-0326", "SCtZ-0320", "SCtZ-0293", "SCtZ-0279", "SCtZ-0280", "SCtZ-0344", "SCtZ-0373", "SCtZ-0352", "SCtZ-0314",
            "SCtZ-0337", "SCtZ-0346", "SCtZ-0425", "SCtZ-0417", "SCtZ-0391", "SCtZ-0397", "SCtZ-0411", "SCtZ-0361", "SCtZ-0382",
            "SCtZ-0394", "SCtZ-0376", "SCtZ-0375", "SCtZ-0386", "SCtZ-0432", "SCtZ-0518", "SCtZ-0501", "SCtZ-0498", "SCtZ-0521", "SCtZ-0512",
            "SCtZ-0503", "SCTZ-0480", "SCTZ-0475", "SCtZ-0485", "SCtZ-0479", "SCtZ-0468", "SCtZ-0453", "SCtZ-0513", "SCtZ-0551", "SCtZ-0570",
            "SCtZ-0577", "SCtZ-0610", "SCtZ-0620", "SCtZ-0611", "SCtZ-0614", "SCtZ-0605", "SCtZ-0603", "SCtZ-0606", "SCtZ-0586.1", "SCtZ-0586.2",
            "SCtZ-0598", "scz-0626", "SCtZ-0595"))) return;
        $w = array();
        if($info['title'] == $this->meta[$epub_info['pdf_id']]['dc.title']) {
            // echo "\n".$info['title']."\n";
            // echo "\n".$this->meta[$epub_info['pdf_id']]['dc.title']."\n";
            $title = $info['title'];
            $title = strip_tags(htmlspecialchars_decode($title));
            // echo "\n".$title."\n";
            self::download_epub($epub_info);
            $ret = self::convert_epub_to_txt($epub_info); //print_r($ret); //exit("\neli 100\n");
            // Array(
            //     [source] => /Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0007/SCtZ-0007.txt
            //     [resource_working_dir] => /Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0007/
            // )
            $url1 = $info['url'];
            $citation = @$this->meta[@$epub_info['pdf_id']]['bibliographicCitation'];
            $url2 = @$this->meta[@$epub_info['pdf_id']]['dc.relation.url'];
            if(!$this->is_title_inside_epub_YN($title, $ret['source'])) { $this->ctr++;
                $arr = array($this->ctr, $title, $url1, $url2, $epub_info['pdf_id'].".epub");
                fwrite($this->WRITE, implode("\t", $arr)."\n");
                // echo "\nMisfiled | $title | $url1 | $url2 | ".$epub_info['pdf_id'];
            }
            else return;
        }
        else {
            echo "\n===========================================================\n";
            print_r($info); echo "\n-----\n"; print_r($epub_info); echo "\n-----\n"; print_r($this->meta[$epub_info['pdf_id']]);
            exit("\ntitles not the same\n");
        }
        return;
        ========================= */

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
        
        /* this should be commented. NOT NEEDED AT ALL. This prohibits from running list-type documents bec of the return; row
        if(in_array($epub_info['pdf_id'], $this->PDFs_that_are_lists)) {
            echo "\n[".$epub_info['pdf_id']."] ".$info['title']." - IS A LIST, NOT SPECIES-DESCRIPTION-TYPE 01\n";
            return;
        }
        */
        
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
        $this->lines_before_and_after_sciname['SCtZ-0029.txt'] = 2;
        $this->lines_before_and_after_sciname['SCtZ-0007.txt'] = 1;
        $this->lines_before_and_after_sciname['SCtZ-0025.txt'] = 1;
        $this->lines_before_and_after_sciname['SCtZ-0020.txt'] = 1;
        $this->lines_before_and_after_sciname['SCtZ-0019.txt'] = 1;
        $this->lines_before_and_after_sciname['scb-0001.txt'] = 1;
        $this->lines_before_and_after_sciname['scb-0003.txt'] = 1;
        $this->lines_before_and_after_sciname['scb-0006.txt'] = 1;
        $this->lines_before_and_after_sciname['scb-0004.txt'] = 1;
        $this->lines_before_and_after_sciname['scb-0007.txt'] = 1;
        
        /* list-types */
        $this->lines_before_and_after_sciname['SCtZ-0011.txt'] = 1;
        $this->lines_before_and_after_sciname['SCtZ-0010.txt'] = 1;
        $this->lines_before_and_after_sciname['SCtZ-0611.txt'] = 1;
        $this->lines_before_and_after_sciname['SCtZ-0613.txt'] = 1;
        $this->lines_before_and_after_sciname['scb-0002.txt'] = 1;

        // /* ==================== working OK -- un-comment in real operation. Comment during caching in eol-archive
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
        // ==================== */
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
        // exit("\napi_id: [$api_id]\n");
        if($this->func_Convertio->upload_local_file($source, $filename, $api_id)) {
            sleep(60);
            if($obj = $this->func_Convertio->check_status($api_id, 0, $filename)) { //3rd param $filename is just for debug
                if($txt_url = $obj->data->output->url) {
                    $cmd = "wget -nc ".$txt_url." -O $destination";
                    $cmd .= " 2>&1";
                    $json = shell_exec($cmd);
                    if(file_exists($destination)) echo "\n".$destination." downloaded successfully from Convertio.\n";
                    else                          exit("\nERROR: can not download ".$epub_info['filename']."\n");
                }
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
                            $ret['filename'] = pathinfo($tmp, PATHINFO_FILENAME).".epub";
                            $ret['url'] = pathinfo($tmp, PATHINFO_DIRNAME)."/".$ret['filename'];
                            // print_r($ret); exit;
                            /*Array(
                                [filename] => SCtZ-0007.epub
                                [url] => https://repository.si.edu/bitstream/handle/10088/5292/SCtZ-0007.epub
                            )*/
                            // self::get_metadata_for_pdf($html, $url, pathinfo($ret['filename'], PATHINFO_FILENAME)); //where $this->meta is generated
                            self::get_metadata_for_pdf($html, $url, $ret['pdf_id']); //where $this->meta is generated
                            
                            if(self::is_pdf_checklistYN($this->meta[$ret['pdf_id']]['dc.title'])) $ret['checklistYN'] = 1;
                            else $ret['checklistYN'] = 0;
                            @$this->with_epub_count++;
                            return $ret;
                        }
                    }
                }
            }
        }
        @$this->without_epub_count++;
    }
    private function get_all_pdfs()
    {
        $total_pdfs = self::get_total_pdfs(); //exit("\n[$total_pdfs]\n");
        $total_pages = ceil($total_pdfs/20);
        // $total_pages = ceil(661/20); //total_pages should be 34 OK
        echo "\ntotal pages: [$total_pages]\n"; //exit;
        $page = 0; $offset = 0;
        while($page < $total_pages) { $page++; echo "\n[$page][$offset]\n";
            $url = str_replace("NUM_OFFSET", $offset, $this->web['PDFs per page'][$this->resource_id]);
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
        $url = str_replace("NUM_OFFSET", 0, $this->web['PDFs per page'][$this->resource_id]);
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
        https://repository.si.edu//handle/10088/5292 e.g. $url
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
        if(preg_match("/".preg_quote($left, '/')."(.*?)\"/ims", $html, $a)) {
            if(substr($a[1],0,4) == 'http') $this->meta[$pdf_id]['dc.relation.url'] = $a[1];
            else { //another option to get furtherInformationURL
                
                // /* 2nd option
                // <meta name="DC.identifier" content="http://hdl.handle.net/10088/6301" xml:lang="en_US" scheme="DCTERMS.URI" />
                $left = '<meta name="DC.identifier" content="';
                if(preg_match("/".preg_quote($left, '/')."(.*?)\"/ims", $html, $a)) {
                    if(substr($a[1],0,4) == 'http') $this->meta[$pdf_id]['dc.relation.url'] = $a[1];
                    else { //another option to get furtherInformationURL
                        $this->meta[$pdf_id]['dc.relation.url'] = $url; //3rd last option
                    }
                }
                // */
                
                
                /* But we don't use: ?show=full when accessing $url
                // e.g. from https://repository.si.edu/handle/10088/6301?show=full
                // dc.identifier.uri</td>
                // <td>http://hdl.handle.net/10088/6301</td>
                $left = 'dc.identifier.uri</td>';
                if(preg_match("/".preg_quote($left, '/')."(.*?)<\/td>/ims", $html, $a)) $this->meta[$pdf_id]['dc.relation.url'] = trim(strip_tags(trim($a[1])));
                else $this->meta[$pdf_id]['dc.relation.url'] = $url; //last option
                */
                
            }
        }
        $left = '<meta name="DC.title" content="';
        if(preg_match("/".preg_quote($left, '/')."(.*?)\"/ims", $html, $a)) $this->meta[$pdf_id]['dc.title'] = $a[1];
        // print_r($this->meta); exit("\n$url\n");
    }
    function clean_repository_of_old_files()
    {
        echo "\n".$this->path['working_dir']."\n";
        foreach(glob($this->path['working_dir'] . "*") as $folder) {
            $postfix = array("_tagged.txt", "_tagged_LT.txt", "_edited.txt", "_edited_LT.txt", "_descriptions_LT.txt");
            foreach($postfix as $post) {
                $txt_filename = pathinfo($folder, PATHINFO_BASENAME)."$post";
                $txt_filename = $folder."/".$txt_filename;
                echo "\n$txt_filename - ";
                if(file_exists($txt_filename)) {
                    if(unlink($txt_filename)) echo " deleted OK\n";
                }
                else                          echo " does not exist";
            }
            // echo "\n$folder\n";
            // /Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCTZ-0105
        }
        // exit("\nstop muna\n");
    }
    private function generate_dwca_for_a_repository()
    {   
        if($this->localRunYN) { //forced value
            $pdf_id = 'SCtZ-0018'; $pdf_meta_obj = array();
            $txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0018/SCtZ-0018_tagged.txt";
            if(file_exists($txt_filename)) self::process_a_txt_file($txt_filename, $pdf_id, $pdf_meta_obj);
            $txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0018/SCtZ-0018_descriptions_LT.txt";
            if(file_exists($txt_filename)) self::process_a_txt_file_LT($txt_filename, $pdf_id, $pdf_meta_obj);
            return;
        }
        
        // SPECIES SECTIONS
        foreach(glob($this->path['working_dir'] . "*") as $folder) {
            $txt_filename = pathinfo($folder, PATHINFO_BASENAME)."_tagged.txt";
            $txt_filename = $folder."/".$txt_filename;
            echo "\n$txt_filename";
            // /* Not used at the moment though...
            $pdf_meta_obj = self::get_pdf_meta_from_json(str_replace("_tagged.txt", "_meta.json", $txt_filename));
            // */
            if(file_exists($txt_filename)) { echo " - OK\n";
                $pdf_id = pathinfo($folder, PATHINFO_BASENAME);
                self::process_a_txt_file($txt_filename, $pdf_id, $pdf_meta_obj);
            }
            else echo " - tagged version not yet generated\n";
        }
        
        // LIST-TYPE
        foreach(glob($this->path['working_dir'] . "*") as $folder) {
            $txt_filename = pathinfo($folder, PATHINFO_BASENAME)."_descriptions_LT.txt";
            $txt_filename = $folder."/".$txt_filename;
            echo "\n$txt_filename";
            // /* COPIED TEMPLATE: Not used at the moment though...
            // $pdf_meta_obj = self::get_pdf_meta_from_json(str_replace("_tagged.txt", "_meta.json", $txt_filename));
            $pdf_meta_obj = array();
            // */
            if(file_exists($txt_filename)) { echo " - OK\n";
                $pdf_id = pathinfo($folder, PATHINFO_BASENAME);
                self::process_a_txt_file_LT($txt_filename, $pdf_id, $pdf_meta_obj);
            }
            else echo " - tagged version not yet generated\n";
        }
        // exit("\nstop munax\n");
    }
    private function get_pdf_meta_from_json($json_file)
    {
        if(file_exists($json_file)) {
            $json = file_get_contents($json_file);
            return json_decode($json);
            /*stdClass Object(
                [url] => https://repository.si.edu//handle/10088/5273
                [title] => Species of Spalangia Latreille in the United States National Museum collection (Hymenoptera: Pteromalidae)
            )*/
        }
    }
    function process_a_txt_file($txt_filename, $pdf_id, $pdf_meta_obj)
    {   /*
        <sciname='Pontostratiotes scotti Brodskaya, 1959'> Pontostratiotes scotti Brodskaya, 1959
        </sciname>
        */
        // exit("\ntxt_filename: [$txt_filename]\npdf_id: [$pdf_id]\n");
        $contents = file_get_contents($txt_filename);
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
                    
                    // /* associations block
                    
                    // /* manual customization
                    if($pdf_id == "SCtZ-0439") { //typo
                        $rec['body'] = str_replace("Chrysopsisgraminifolia", "Chrysopsis graminifolia", $rec['body']);
                    }
                    // */

                    /* debug only
                    if($rec['sciname'] == 'Capitophorus ohioensis Smith, 1940:141.') { //exit("\nelix\n");
                        $assoc = $this->func_Assoc->parse_associations($rec['body'], $pdf_id);
                    }
                    */
                    // /* normal operation
                    $assoc = $this->func_Assoc->parse_associations($rec['body'], $pdf_id);
                    // print_r($assoc); exit("\n-SI Contributions-\n");
                    // */
                    
                    $assoc['sciname'] = $rec['sciname']; //just for debug for now
                    // good debug
                    // if($assoc['sciname'] != $rec['sciname']) { --> indeed sometimes they're not equal
                    //     echo "\n[".$assoc['sciname']."]\n[".$rec['sciname']."]\n";
                    //     [Periploca orichalcella (Clemens), new combination]
                    //     [Periploca orichalcella (Clemens)]
                    //     exit("\nInvestigate sciname\nshould not go here\n"); //should not go here
                    // }
                    
                    if($val = @$assoc['assoc']) {
                        $rec['associations'] = $val;
                        if($GLOBALS['ENV_DEBUG']) {
                            echo "\n---------\n"; print_r($assoc); echo "\n---------\n";
                        }
                    }
                    // */
                    
                } //print_r($rec); exit;
                /*Array(
                    [pdf_id] => SCtZ-0001
                    [sciname] => Lysiosquilla capensis Hansen, 1895
                    [body] => Lysiosquilla capensis Hansen, 1895<br><br>Lysiosquilla capensis Hansen, 1895, p. 74.—Stebbing,...
                )*/
                if($rec['sciname'] && $rec['body']) self::write_archive($rec, $pdf_meta_obj);
            }
        }
    }
    function process_a_txt_file_LT($txt_filename, $pdf_id, $pdf_meta_obj)
    {   // exit("\ntxt_filename: [$txt_filename]\npdf_id: [$pdf_id]\n");
        $i = 0;
        foreach(new FileIterator($txt_filename) as $line => $row) { $i++; if(($i % 5000) == 0) echo " $i";
            $row = trim($row);
            if(!$row) continue;
            $arr = explode("\t", $row); // print_r($arr); exit;
            /*Array(
                [0] => Pulicaria crispa
                [1] => *Pulicaria crispa (Forsskål) Bentham and J. D. Hooker f.: Karkur Murr. In all parts of wadi, particularly area of coarse alluvium and sand. Flowering. Shaw (1931): “High up K. Tahl, 3000 ft., low herb 1 ft. high. Found at Kissu.”
                [2] => Annotated List of Plants
            )*/
            $rec = array();
            $rec['pdf_id'] = $pdf_id;
            $rec['sciname'] = $arr[0];
            $body = @$arr[1].". ".@$arr[2];
            $body = str_replace("..", ".", $body);
            $rec['body'] = $body;
            $others['additionalInformation'] = @$arr[2]; //list-header
            if($rec['sciname'] && $rec['body']) self::write_archive($rec, $pdf_meta_obj, "Uses", $others); //maybe just a temp text object, until trait is computed.
        }
    }
    private function write_archive($rec, $pdf_meta_obj, $CVterm = "Description", $others = array())
    {   //write taxon
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID         = md5($rec['sciname']);
        $taxon->scientificName  = $rec['sciname'];
        
        // if($taxon->scientificName == "Halter yellow ………………………… E. halteralis") {
            // print_r($rec); print_r($pdf_meta_obj);
            // exit("\nelix\n");
        // }
        
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
        // $mr->CVterm         = 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Description'; //ComprehensiveDescription
        $mr->CVterm         = 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#'.$CVterm;

        $mr->furtherInformationURL = @$this->meta[$rec['pdf_id']]['dc.relation.url'];
        $mr->bibliographicCitation = @$this->meta[$rec['pdf_id']]['bibliographicCitation'];
        // $mr->Owner          = $o['dc_rightsHolder'];
        // $mr->rights         = $o['dc_rights'];
        // $mr->title          = $o['dc_title'];
        
        if($val = @$others['additionalInformation']) $mr->additionalInformation = $val;
        
        if(!isset($this->object_ids[$mr->identifier])) {
            $this->archive_builder->write_object_to_file($mr);
            $this->object_ids[$mr->identifier] = '';
        }
        // else exit("\nShould not go here. Same text object.\n"); //Eli to investigate soon...
        //write associations
        if($val = @$rec['associations']) {
            $val['pdf_id'] = $rec['pdf_id'];
            $taxon_ids = $this->func_Assoc->write_associations($val, $taxon, $this->archive_builder, @$this->meta, $this->taxon_ids, $mr->bibliographicCitation);
            $this->taxon_ids = $taxon_ids;
        }
    }
    function clean_sciname($name)
    {
        $pos = stripos($name, " (see ");
        if($pos > 5) $name = substr($name, 0, $pos);

        $pos = stripos($name, ". See ");
        if($pos > 5) $name = substr($name, 0, $pos);

        // $name = str_ireplace(", new order", "", $name);
        // $name = str_ireplace(", new species", "", $name);
        // $name = str_ireplace(", new subspecies", "", $name);
        // $name = str_ireplace(", new subgenus", "", $name);
        // $name = str_ireplace(", new combination", "", $name);
        // $name = str_ireplace(", new subfamily", "", $name);
        // $name = str_ireplace(", new status", "", $name);
        // $name = str_ireplace(", new name", "", $name);
        // $name = str_ireplace(", new rank", "", $name);
        
        $pos = stripos($name, ", new ");
        if($pos > 5) $name = substr($name, 0, $pos);

        $pos = stripos($name, "; new ");
        if($pos > 5) $name = substr($name, 0, $pos);
        
        $pos = stripos($name, " new ");
        if($pos > 5) $name = substr($name, 0, $pos);

        $pos = stripos($name, " (of ");
        if($pos > 5) $name = substr($name, 0, $pos);
        
        $pos = stripos($name, " (from ");
        if($pos > 5) $name = substr($name, 0, $pos);

        $pos = stripos($name, " (propinquus ");
        if($pos > 5) $name = substr($name, 0, $pos);
        
        return Functions::remove_whitespace($name);
    }
    private function process_a_pdf_all($info)
    {   //print_r($info); exit;
        /*Array(
            [url] => https://repository.si.edu//handle/10088/5292
            [title] => Recent ostracodes of the family Pontocyprididae chiefly from the Indian Ocean
        )*/
        $epub_info = self::get_epub_info($info['url']); //within this where $this->meta is generated
        // print_r($epub_info); print_r($this->meta); exit("\n$this->resource_id\n"); //good debug
        // /* ========================= Utility report for Jen - one time run
        $w = array();
        if(true) {
            // echo "\n".$info['title']."\n";
            // echo "\n".$this->meta[$epub_info['pdf_id']]['dc.title']."\n";
            $title = $info['title'];
            $title = strip_tags(htmlspecialchars_decode($title));
            // echo "\n".$title."\n";
            // Array(
            //     [source] => /Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0007/SCtZ-0007.txt
            //     [resource_working_dir] => /Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0007/
            // )
            $url1 = $info['url'];
            $citation = @$this->meta[@$epub_info['pdf_id']]['bibliographicCitation'];
            $url2 = @$this->meta[@$epub_info['pdf_id']]['dc.relation.url'];
            $this->ctr++; //remove in normal operation
            $arr = array($this->ctr, $title, $url1, $url2, $epub_info['pdf_id'].".epub");
            fwrite($this->WRITE, implode("\t", $arr)."\n");
        }
        else {
            echo "\n===========================================================\n";
            print_r($info); echo "\n-----\n"; print_r($epub_info); echo "\n-----\n"; print_r($this->meta[$epub_info['pdf_id']]);
            exit("\ntitles not the same\n");
        }
        return;
        // ========================= */
        // exit("\n-done 1 pdf'\n"); //debug only
    }
}