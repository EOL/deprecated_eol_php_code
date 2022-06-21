<?php
namespace php_active_record;
/* This will be an all-in-one BHL download facility. 
https://about.biodiversitylibrary.org/tools-and-services/developer-and-data-tools/#APIs
https://www.biodiversitylibrary.org/docs/api3.html
https://www.biodiversitylibrary.org/docs/api3/GetPageMetadata.xml
*/
class BHL_Download_API //extends Functions_Memoirs
{
    function __construct() {
        $this->api_key = BHL_API_KEY;
        $this->download_options = array('resource_id' => "BHL", 'timeout' => 172800, 'expire_seconds' => false, 'download_wait_time' => 2000000);
        // $this->download_options['expire_seconds'] = 60*60*24*30*6;
        $this->Endpoint = "https://www.biodiversitylibrary.org/api3";
        $this->save_dir = CONTENT_RESOURCE_LOCAL_PATH."reports/BHL";
        if(!is_dir($this->save_dir)) mkdir($this->save_dir);
    }
    function PublicationSearch($searchterm, $method = "PublicationSearch")
    {   /*
        https://www.biodiversitylibrary.org/api3?op=PublicationSearch
        &searchterm=<the text for which to search>
        &searchtype=<'C' for a catalog-only search; 'F' for a catalog+full-text search>
        &page=<1 for the first page of results, 2 for the second, and so on>
        &pageSize=<the maximum number of results to return per page (default = 100)>
        &apikey=<API key value>
        */
        if(strlen($searchterm) < 10) exit("\nSearch term is too short: [$searchterm]\n");
        
        $this->needle = $searchterm;
        
        $this->corpus_file = $this->save_dir."/search_BHL_".$searchterm.".txt";
        $f = Functions::file_open($this->corpus_file, "w"); fclose($f); //initialize file
        
        $page = 0;
        $results = true;
        $this->breakdown = array();
        while($results) { $page++;
            $url = $this->Endpoint."?op=$method&searchterm=$searchterm&searchtype=F&page=$page&pageSize=100&format=json&apikey=".$this->api_key;
            if($json = Functions::lookup_with_cache($url, $this->download_options)) {
                $objects = json_decode($json);
                // print_r($objects); exit;
                /*
                [34] => stdClass Object(
                                    [BHLType] => Part
                                    [FoundIn] => Metadata
                                    [Volume] => 2
                                    [ExternalUrl] => http://www.pensoft.net/journals/zookeys/article/56/
                                    [Authors] => Array()
                                    [PartUrl] => https://www.biodiversitylibrary.org/part/98696
                                    [PartID] => 98696
                [35] => stdClass Object(
                                    [BHLType] => Item
                                    [FoundIn] => Text
                                    [ItemID] => 292464
                */
                
                $debug = array();
                foreach($objects->Result as $obj) @$debug['BHLType'][$obj->BHLType]++;
                if($debug) print_r($debug);
                
                $i = 0; $Part_count = 0; $Item_count = 0;
                foreach($objects->Result as $obj) { $i++;
                    /* good debug
                    if($i == 4) { print_r($obj); exit("\nfirst [$i] obj\n"); }
                    */
                    // /*
                    if($obj->BHLType == 'Part') { $Part_count++;
                        // print_r($obj); exit("\n111\n");
                        /*stdClass Object(
                            [BHLType] => Part
                            [FoundIn] => Both
                            [Volume] => 20
                            [Authors] => Array(
                                    [0] => stdClass Object(
                                            [Name] => Lott, Derek A
                                        )
                                )
                            [PartUrl] => https://www.biodiversitylibrary.org/part/263683
                            [PartID] => 263683
                            [Genre] => Article
                            [Title] => Changes in the saproxylic Coleoptera fauna of four wood pasture sites
                            [ContainerTitle] => British Journal of Entomology and Natural History
                            [Issue] => 3
                            [Date] => 2007
                            [PageRange] => 142--148
                        )*/
                        $type = 'part';
                        $part_id = $obj->PartID; echo("\nPartID: [$part_id] $Part_count of ".$debug['BHLType']['Part']."\n");
                        $idtype = 'bhl';
                        self::GetPartMetadata(array('part_id'=>$part_id, 'idtype'=>$idtype)); //no OCR text yet, but with multiple pages
                    }
                    elseif($obj->BHLType == 'Item') { $Item_count++;
                        // print_r($obj); exit("\n222\n");
                        /*stdClass Object(
                            [BHLType] => Item
                            [FoundIn] => Text
                            [ItemID] => 292464
                            [TitleID] => 177982
                            [Volume] => 2006
                            [ItemUrl] => https://www.biodiversitylibrary.org/item/292464
                            [TitleUrl] => https://www.biodiversitylibrary.org/bibliography/177982
                            [MaterialType] => Published material
                            [PublisherPlace] => Asheville  NC
                            [PublisherName] => U.S. Dept. of Agriculture, Forest Service, Southern Research Station
                            [PublicationDate] => 2006
                            [Authors] => Array(
                                    [0] => stdClass Object(
                                            [Name] => Grove, Simon J
                                        )
                                    [1] => stdClass Object(
                                            [Name] => Hanula, James L. (James Lee),
                                        )
                                    [2] => stdClass Object(
                                            [Name] => United States. Forest Service. Southern Research Station.
                                        )
                                    [3] => stdClass Object(
                                            [Name] => International Congress of Entomology Brisbane, Qld.)
                                        )
                                )
                            [Genre] => Book
                            [Title] => Insect biodiversity and dead wood : proceedings of a symposium for the 22nd International Congress of Entomology
                        )*/
                        $type = 'item';
                        $item_id = $obj->ItemID; echo("\nItemID: [$item_id] $Item_count of ".$debug['BHLType']['Item']."\n");
                        // print_r($obj); exit("\ntype == 'Item'\n");
                        $idtype = 'bhl';
                        self::GetItemMetadata(array('item_id'=>$item_id, 'idtype'=>$idtype, 'needle'=>$this->needle));
                        if($obj->ItemID) $this->breakdown['Item'][$obj->ItemID] = '';
                    }
                    else { print_r($obj); exit("\nun-classified BHLType\n"); }
                    // */
                    
                    // if($i > 5) break; //debug only
                } //end foreach()
                echo "\nRecords: ".count($objects->Result)."\n";
                $results = $objects->Result;
            }
        }
        /* -----start evaluation----- */
        // print_r($this->breakdown);
        $arr_Part = array_keys($this->breakdown['Part']);
        $arr_Item = array_keys($this->breakdown['Item']);
        if(array_intersect($arr_Part, $arr_Item) == $arr_Part) { //$arr_Part is a subset of $arr_Item
            echo "\nOK Part is a subset of Item n=".count($arr_Item)."\n";
            self::generate_corpus_doc($arr_Item);
        }
        else echo "\nPart is not a subset of Item - Investigate\n";
    }
    private function generate_corpus_doc($item_ids)
    {
        // exit;
        foreach($item_ids as $item_id) {
            // echo " $item_id ";
        }
    }
    function GetPartMetadata($params) //1 object (part) result, no OcrText yet, but with multiple pages
    {   /* If it has [ExternalUrl], then it won't have [Pages]
        https://www.biodiversitylibrary.org/api3?op=GetPartMetadata
        &id=<identifier of a part (article, chapter, ect)>
        &idtype=<bhl, doi, jstor, biostor, or soulsby (OPTIONAL; "bhl" is the default)>
        &pages=<"t" or "true" to include the part's pages in the response>
        &names=<"t" or "true" to include scientific names in the part in the response>
        &apikey=<API key value>
        */
        $part_id = $params['part_id']; $idtype = $params['idtype']; $method = "GetPartMetadata";
        
        $url = $this->Endpoint."?op=$method&id=$part_id&idtype=$idtype&pages=t&names=t&parts=t&format=json&apikey=".$this->api_key;
        if($json = Functions::lookup_with_cache($url, $this->download_options)) {
            $objs = json_decode($json);
            // print_r($objs); exit("\nelix1\n");
            echo "\nCount: ".count($objs->Result)."\n";
            // exit("\nexit GetPartMetadata()\n");
            foreach($objs->Result as $obj) { //1 object result only
                
                // print_r($obj); //exit;
                // /* ----- start debug -----
                foreach($obj->Pages as $p) {
                    if($p->ItemID != "0" || $p->ItemID > 0) {
                        print_r($obj);
                        exit("\nhuli ka\n");
                    }
                }
                // ----- ----- */
                
                /*stdClass Object(
                                    [PartUrl] => https://www.biodiversitylibrary.org/part/263683
                                    [PartID] => 263683
                                    [ItemID] => 220677
                */
                if(@$obj->ItemID) $this->breakdown['Part'][$obj->ItemID] = '';
                
                self::write_part_info($obj);
                
                // print_r($obj); exit("\n-end GetPartMetadata-\n");
                /*
                if($pages = @$obj->Pages) self::process_pages_from_part($part_id, $pages);
                elseif($external_url = @$obj->ExternalUrl) echo "\nexternal_url: [$external_url]\n";
                */
                
                /* if there are no Pages, then most likely it should have [ExternalUrl]
                e.g. [ExternalUrl] => https://natureconservation.pensoft.net/articles.php?id=12464
                then u can get the PDF to download:
                https://natureconservation.pensoft.net/article/12464/download/pdf
                */

            }
        }
        else echo "\npart_id not found ($part_id)\n";
    }
    private function process_pages_from_part($part_id, $pages)
    {   $i = 0;
        foreach($pages as $page) { $i++;
            // echo " $page->PageID | ";
            self::GetPageMetadata(array('page_id'=>$page->PageID, 'needle'=>$this->needle, 'ctr'=>$i));
        }
        echo "\nTotal Pages from part_id $part_id: ".count($pages)."\n"; //exit("\ncha1\n");
    }
    function GetItemMetadata($params) //1 object result but can consist of multiple Pages.
    {   /*                                                                No need to lookup GetPageMetadata() for OCR text
        https://www.biodiversitylibrary.org/api3?op=GetItemMetadata
        &id=<identifier of an item>
        &idtype=<"bhl" if id is a BHL identifier, "ia" if id is an Internet Archive identifier (OPTIONAL; "bhl" is the default)>
        &pages=<"t" or "true" to include page details in the response>
        &ocr=<"t" or "true" to include the page text in the response>
        &parts=<"t" or "true" to include parts in the item in the response>
        &apikey=<API key value>
        */
        $item_id = $params['item_id']; $idtype = $params['idtype']; $method = "GetItemMetadata";
        $needle = $params['needle'];
        
        $url = $this->Endpoint."?op=$method&id=$item_id&idtype=$idtype&pages=t&ocr=t&parts=t&format=json&apikey=".$this->api_key;
        if($json = Functions::lookup_with_cache($url, $this->download_options)) {
            $objs = json_decode($json);
            if(count($objs->Result) > 1) exit("\nThis item_id $item_id has multiple results.\n");
            foreach($objs->Result as $obj) { //just 1 object but with multiple pages
                // print_r($obj); exit("\n-end GetItemMetadata-\n");
                echo "\nItemID: ".$obj->ItemID."\n";
                echo "\nItemID has total pages: ".count($obj->Pages)."\n";
                
                self::write_item_info($obj);
                
                /* an item has a TitleID and multiple [Pages] with [OcrText] */
                /* working OK but no longer needed
                foreach($obj->Pages as $page) { //print_r($page); exit;
                    if(stripos($page->OcrText, $needle) !== false) { //string is found
                        echo "\nFound OK $needle in page $page->PageID.\n";
                    }
                    else echo "\nNo $needle in page $page->PageID.\n";
                }
                */
            }
        }
        // exit("\ncha2\n");
    }
    function GetTitleMetadata($params)
    {   /* GetTitleMetadata
        https://www.biodiversitylibrary.org/api3?op=GetTitleMetadata
        &id=<identifier of a title>
        &idtype=<bhl, doi, oclc, issn, isbn, lccn, ddc, nal, nlm, coden, or soulsby (OPTIONAL; "bhl" is the default)>
        &item=<"t" or "true" to include the title's items in the response>
        &format=<"xml" for an XML response or "json" for JSON (OPTIONAL; "xml" is the default)>
        &apikey=<API key value>
        */
        $title_id = $params['title_id'];
        $idtype = $params['idtype'];
        $method = 'GetTitleMetadata';
        
        $url = $this->Endpoint."?op=$method&id=$title_id&idtype=$idtype&item=t&format=json&apikey=".$this->api_key;
        if($json = Functions::lookup_with_cache($url, $this->download_options)) {
            $objs = json_decode($json);
            foreach($objs->Result as $obj) { //just 1 object
                /*stdClass Object(
                    [TitleID] => 177982
                    [Genre] => Monograph/Item
                    [MaterialType] => Published material
                    [FullTitle] => Insect biodiversity and dead wood : proceedings of a symposium for the 22nd International Congress of Entomology
                    [ShortTitle] => Insect biodiversity and dead wood
                    [SortTitle] => Insect biodiversity and dead wood proceedings of a symposi
                    [PublisherPlace] => Asheville,  NC
                    [PublisherName] => U.S. Dept. of Agriculture, Forest Service, Southern Research Station
                    [PublicationDate] => [2006]
                    [TitleUrl] => https://www.biodiversitylibrary.org/bibliography/177982
                    [CreationDate] => 2021/05/15 11:35:32
                    [Authors] => Array()
                    [Subjects] => Array()
                    [Identifiers] => Array()
                    [Variants] => Array()
                    [Notes] => Array()
                )*/
                // print_r($obj); exit("\n444\n");
                return $obj;
            }
        }
        else exit("\nInvestigate: title_id not found [$title_id]\n");
    }
    function GetPageMetadata($params)
    {   /* GetPageMetadata
        Return metadata about a page. You may choose to include the text and a list of names found on the page.
        Example - https://www.biodiversitylibrary.org/api3?op=GetPageMetadata&pageid=3137380&ocr=t&names=t&apikey=<key+value>
        pageid - the identifier of an individual page in a scanned book
        ocr - "t" or "true" to return text of the page
        names - "t" or "true" to return the names that appear on the page
        */
        $page_id = $params['page_id']; $needle = @$params['needle']; $method = "GetPageMetadata"; $ctr = @$params['ctr'];
        
        $url = $this->Endpoint."?op=$method&pageid=$page_id&ocr=t&names=t&format=json&apikey=".$this->api_key;
        if($json = Functions::lookup_with_cache($url, $this->download_options)) {
            $objs = json_decode($json);
            // print_r($objs); exit("\nelix1\n");
            foreach($objs->Result as $obj) { //but just result anyway
                echo "\n$ctr. PageID: ".$obj->PageID."\n";
                /* a page has an [OcrText] */
                if($needle) { //if a value for needle is passed
                    if(stripos($obj->OcrText, $needle) !== false) { //string is found
                        echo "\nFound OK $needle in page $page_id.\n";
                        // print_r($obj); exit;
                    }
                    else echo "\nNo $needle in page $page_id.\n";
                }
            }
        }
        
        else echo "\npage_id not found ($page_id)\n";
    }
    private function write_item_info($obj)
    {
        /* Item metadata more importantly has ItemTextUrl where you can get all OCR text in one place
        [ItemID] => 292464
        [TitleID] => 177982
        [ThumbnailPageID] => 60852632
        [Source] => Internet Archive
        [SourceIdentifier] => CAT30987312
        [IsVirtual] => 0
        [Volume] => 2006
        [Year] => 2006
        [HoldingInstitution] => U.S. Department of Agriculture, National Agricultural Library
        [Sponsor] => U.S. Department of Agriculture, National Agricultural Library
        [Language] => English
        [Rights] => The contributing institution believes that this item is not in copyright
        [CopyrightStatus] => Not provided. Contact contributing library to verify copyright status.
        [ItemUrl] => https://www.biodiversitylibrary.org/item/292464
        [TitleUrl] => https://www.biodiversitylibrary.org/bibliography/177982
        [ItemThumbUrl] => https://www.biodiversitylibrary.org/pagethumb/60852632
        [ItemTextUrl] => https://www.biodiversitylibrary.org/itemtext/292464
        */
        
        $title_obj = self::GetTitleMetadata(array('title_id'=>$obj->TitleID, 'idtype'=>'bhl'));
        
        // /* un-comment in main operation
        if($url = $obj->ItemTextUrl) {
            $options = $this->download_options;
            $options['expire_seconds'] = false; //should always be false
            if($text = Functions::lookup_with_cache($url, $options)) {
                echo "\ntext size: ".strlen($text)."\n"; //exit("\n333\n");
                if(!isset($this->printed_ItemIDs[$obj->ItemID])) {
                    self::write_2_text($text, $title_obj, $obj->ItemID);
                    $this->printed_ItemIDs[$obj->ItemID] = '';
                }
            }
        }
        else exit("\nInvestigate item_id $obj->ItemID\n");
        // */
    }
    private function write_2_text($text, $title_obj, $item_id)
    {
        $f = Functions::file_open($this->corpus_file, "a");
        $title = "FULL TITLE: ".$title_obj->FullTitle;
        $pad = Functions::format_number_with_leading_zeros("", strlen($title));
        fwrite($f, "$pad "."ItemID: $item_id"."\n");
        fwrite($f, $title."\n");
        fwrite($f, "$pad\n\n");
        fwrite($f, $text."\n");
        fwrite($f, "=============== end of file ==============\n\n");
        fclose($f);
    }
    private function write_part_info($obj)
    {
        // [PartUrl] => https://www.biodiversitylibrary.org/part/263683
        // [PartID] => 263683
        // [ItemID] => 220677
        // [Title] => Changes in the saproxylic Coleoptera fauna of four wood pasture sites
        echo "\n-----------------------------Part Title";
        echo "\n".$obj->Title." | ItemID: ".@$obj->ItemID." | Pages count: ".count($obj->Pages); //not all have ItemID here
        if($url = @$obj->ExternalUrl) echo " | External: $url";
        if(count($obj->Pages) == 0) {
            echo " | Subjects: ".count(@$obj->Subjects);
            if(self::is_needle_found_in_Part_metadata($obj, $this->needle)) echo "\nneedle ($this->needle) found in metadata OK\n";
            else                                                            echo "\nInvestigate needle ($this->needle) not found\n";
        }
        echo "\n-----------------------------\n";
        
        // if(count($obj->Pages) == 0) print_r($obj); //good debug
    }
    private function is_needle_found_in_Part_metadata($obj, $needle)
    {
        $arr = (array) $obj;
        $str = json_encode($arr);
        if(stripos($str, $needle) !== false) return true; //string is found
        else {
            exit("\nyyy\n$str\nxxx\nInvestigate: needle ($needle) not found in Part metadata\n");
            return false;
        }
    }
    
    /*++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++*/
    /* seems not used
    function PageSearch($idtype, $id, $method = "PageSearch")
    {
        https://www.biodiversitylibrary.org/api3?op=PageSearch
        &itemid=<BHL identifier of the item to be searched (not used if id/idtype are specified)>
        &idtype=<"item" or "part", designating the type of publication to be searched (not used if itemid is specified)>
        &id=<BHL identifier of the item or part to be searched (not used if itemid is specified)>
        &text=<the text for which to search>
        &apikey=<API key value>
        https://www.biodiversitylibrary.org/api3?op=PageSearch&idType=item&id=22004&text=domestic+cat&apikey=12345678-BBBB-DDDD-FFFF-123456789012
        $url = $this->Endpoint."?op=$method&idtype=$idtype&id=$id&text=&format=json&apikey=".$this->api_key;
        if($json = Functions::lookup_with_cache($url, $this->download_options)) {
            $obj = json_decode($json);
            print_r($obj); exit("\nend PageSearch\n");
        }
    }
    */
    /* copied template
    function initialize_files_and_folders($input)
    {   //print_r($input); exit;
        // Array(
        //     [filename] => 15405.txt
        //     [lines_before_and_after_sciname] => 2
        //     [doc] => BHL
        //     [epub_output_txts_dir] => /Volumes/AKiTiO4/other_files/Smithsonian/BHL/15405/
        // )
        if(!is_dir($input['epub_output_txts_dir'])) mkdir($input['epub_output_txts_dir']);
        $file = $input['epub_output_txts_dir'].$input['filename'];
        if(!file_exists($file)) self::download_txt_file($file, $input);
        else {
            if(!filesize($file)) self::download_txt_file($file, $input);
        }
    }
    private function download_txt_file($destination, $input)
    {   //exit("\n[$destination]\n[$doc]\n");
        $this->paths['BHL']['txt'] = "https://www.biodiversitylibrary.org/itemtext/";
        $this->paths['BHL']['pdf'] = "https://www.biodiversitylibrary.org/itempdf/";

        $doc = $input['doc']; $filename = $input['filename']; 
        $source = $this->paths[$doc]['txt'].str_replace(".txt", "", $filename);
        // $cmd = "wget -nc --no-check-certificate ".$source." -O $destination"; $cmd .= " 2>&1"; --- no overwrite
           $cmd = "wget --no-check-certificate ".$source." -O $destination"; $cmd .= " 2>&1";
        echo "\nDownloading...[$cmd]\n";
        $output = shell_exec($cmd); sleep(10);
        if(file_exists($destination) && filesize($destination)) echo "\n".$destination." downloaded successfully from $doc.\n";
        else                                                    exit("\nERROR: can not download ".$source."\n[$output]\n");
        if(!Functions::is_production()) {
            $destination = str_replace(".txt", ".pdf", $destination);
            $source = $this->paths[$doc]['pdf'].str_replace(".txt", "", $filename);
            // $cmd = "wget -nc --no-check-certificate ".$source." -O $destination"; $cmd .= " 2>&1"; --- no overwrite
               $cmd = "wget --no-check-certificate ".$source." -O $destination"; $cmd .= " 2>&1";
            echo "\nDownloading...[$cmd]\n";
            $output = shell_exec($cmd); //sleep(30);
            if(file_exists($destination) && filesize($destination)) echo "\n".$destination." downloaded successfully from $doc.\n";
            else                                                    exit("\nERROR: can not download ".$source."\n[$output]\n");
            exit("\n-Enough here...-\n");
        }
    }
    */
}
?>