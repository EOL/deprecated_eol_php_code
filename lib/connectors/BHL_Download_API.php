<?php
namespace php_active_record;
/* connector: bhl_search.php
This will be an all-in-one BHL download facility. 
https://about.biodiversitylibrary.org/tools-and-services/developer-and-data-tools/#APIs
https://www.biodiversitylibrary.org/docs/api3.html
https://www.biodiversitylibrary.org/docs/api3/GetPageMetadata.xml
*/
class BHL_Download_API //extends Functions_Memoirs
{
    function __construct() {
        $this->api_key = BHL_API_KEY;
        $this->download_options = array('resource_id' => "BHL", 'timeout' => 172800, 'expire_seconds' => false, 'download_wait_time' => 1000000);
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
        // /* New: pages maintenance
        $this->pages_ocr_repo = $this->save_dir."/pages_".$searchterm;
        if(!is_dir($this->pages_ocr_repo)) mkdir($this->pages_ocr_repo);
        // */
        
        /* comment so script can accept 'detrivore'
        if(strlen($searchterm) < 10) exit("\nSearch term is too short: [$searchterm]\n");
        */
        $this->needle = $searchterm;
        
        $this->corpus_file = $this->save_dir."/corpus_".$searchterm.".txt";
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
                    
                    // if($i > 20) break; //debug only
                } //end foreach()
                echo "\nRecords: ".count($objects->Result)."\n";
                $results = $objects->Result;
            }
        }
        /* -----start evaluation----- */
        // print_r($this->breakdown);
        $arr_Part = array_keys($this->breakdown['Part']);   echo "\nPart: ".count($arr_Part)."\n";
        $arr_Item = array_keys($this->breakdown['Item']);   echo "\nItem: ".count($arr_Item)."\n";
        if(array_intersect($arr_Part, $arr_Item) == $arr_Part) { //$arr_Part is a subset of $arr_Item
            echo "\nOK Part is a subset of Item n=".count($arr_Item)."\n";
            // self::generate_corpus_doc($arr_Item); //was never used
        }
        else echo "\nPart is not a subset of Item - Investigate\n";
        
        echo "\nscientificNames found: ".count($this->namez)."\n"; //print_r($this->namez);
        self::write_scinames($this->namez, $this->needle);
    }
    private function write_scinames($names, $needle)
    {
        $names = array_keys($names);
        $file = $this->save_dir."/entities_".$needle.".jsonl";
        $f = Functions::file_open($file, "w");
        /* write start */
        $lines = array();
        $lines[] = '{"label": "TERM_POS", "pattern": "'.$needle.'"}';
        $lines[] = '{"label": "TERM_POS", "pattern": "'.ucfirst($needle).'"}';
        $lines[] = '{"label": "TERM_POS_OTHER", "pattern": "other '.$needle.'"   , "_comment_": "new"}';
        $lines[] = '{"label": "TERM_POS_OTHER", "pattern": "other rare '.$needle.'"   , "_comment_": "new"}';
        $lines[] = '{"label": "TERM_POS_COMPARISON", "pattern": "more rare '.$needle.'"   , "_comment_": "new"}';
        $lines[] = '{"label": "TERM_POS_DIRECT", "pattern": "is '.$needle.'"           , "_comment_": "new"}';
        $lines[] = '{"label": "TERM_POS_DIRECT", "pattern": "is a '.$needle.'"         , "_comment_": "new"}';
        $lines[] = '{"label": "TERM_POS_DIRECT", "pattern": "is a typical '.$needle.'" , "_comment_": "new"}';
        $lines[] = '{"label": "TERM_POS_DIRECT", "pattern": "are '.$needle.'"          , "_comment_": "new"}';
        $lines[] = '{"label": "TERM_POS_ENUM", "pattern": "list of '.$needle.'"  , "_comment_": "new"}';
        $lines[] = '{"label": "TERM_POS_ENUM", "pattern": "List of '.$needle.'"  , "_comment_": "new"}';
        $lines[] = '{"label": "TERM_POS_GROUP", "pattern": "entirely '.$needle.'"      , "_comment_": "new"}';
        $lines[] = '{"label": "TERM_POS_GROUP", "pattern": "exclusively '.$needle.'"   , "_comment_": "new"}';
        $lines[] = '{"label": "INCLUDING_PHRASE", "pattern": "including"        , "_comment_": "new"}';
        $lines[] = '{"label": "INCLUDING_PHRASE", "pattern": "includes"         , "_comment_": "new"}';
        $lines[] = '{"label": "INCLUDING_PHRASE", "pattern": "like"             , "_comment_": "new"}';
        $lines[] = '{"label": "INCLUDING_PHRASE", "pattern": "such as"          , "_comment_": "new"}';
        $lines[] = '{"label": "INCLUDING_PHRASE", "pattern": "for example"      , "_comment_": "new"}';
        $lines[] = '{"label": "INCLUDING_PHRASE", "pattern": "e.g."             , "_comment_": "new"}';
        $lines[] = '{"label": "OTHER_TERMS", "pattern": "predator"              , "_comment_": "new"}';
        $lines[] = '{"label": "OTHER_TERMS", "pattern": "herbivorous"           , "_comment_": "new"}';
        $lines[] = '{"label": "OTHER_TERMS", "pattern": "brachypterous"         , "_comment_": "new"}';
        $lines[] = '{"label": "OF_REDIRECT_PHRASE", "pattern": "of other species"                   , "_comment_": "new"}';
        $lines[] = '{"label": "OF_REDIRECT_PHRASE", "pattern": "of herbivorous species"             , "_comment_": "new"}';
        $lines[] = '{"label": "OF_REDIRECT_PHRASE", "pattern": "of predators of '.$needle.' species" , "_comment_": "new"}';
        $lines[] = '{"label": "OF_REDIRECT_PHRASE", "pattern": "its host"                           , "_comment_": "new"}';
        $lines[] = '{"label": "OF_REDIRECT_PHRASE", "pattern": "assemblages in"                     , "_comment_": "new"}';
        $lines[] = '{"label": "OF_REDIRECT_PHRASE", "pattern": "for '.$needle.' inveretebrates"      , "_comment_": "new"}';
        $lines[] = '{"label": "OF_REDIRECT_PHRASE", "pattern": "associates"                         , "_comment_": "new"}';
        $lines[] = '{"label": "EXCEPTION_PHRASE", "pattern": "with the exception of"                , "_comment_": "new"}';
        $lines[] = '{"label": "TERM_NEG", "pattern": "not '.$needle.'"}';
        $lines[] = '{"label": "TERM_NEG", "pattern": "non-'.$needle.'"}';
        $lines[] = '{"label": "TERM_NEG", "pattern": "none '.$needle.'"}';
        $lines[] = '{"label": "TERM_NEG", "pattern": "not entirely '.$needle.'"}';
        $lines[] = '{"label": "TERM_NEG", "pattern": "not exclusively '.$needle.'"}';
        $lines[] = '{"label": "SPECIES_REF_NEG", "pattern": "complex"}';
        $lines[] = '{"label": "SPECIES_REF_NEG", "pattern": "species complex"}';
        $lines[] = '{"label": "SPECIES_REF_NEG", "pattern": "group"}';
        $lines[] = '{"label": "SPECIES_REF_NEG", "pattern": "subgroup"}';
        $lines[] = '{"label": "NAME_POSTFIX", "pattern": "species nova"}';
        $lines[] = '{"label": "AUX_POS", "pattern": "is"}';
        $lines[] = '{"label": "AUX_POS", "pattern": "are"}';
        $lines[] = '{"label": "AUX_NEG", "pattern": "is not"}';
        $lines[] = '{"label": "AUX_NEG", "pattern": "is negatively"}';
        $lines[] = '{"label": "AUX_NEG", "pattern": "are not"}';
        $lines[] = '{"label": "AUX_NEG", "pattern": "are negatively"}';
        $lines[] = '{"label": "GROUP_POS", "pattern": "all"}';
        $lines[] = '{"label": "GROUP_POS", "pattern": "All"}';
        $lines[] = '{"label": "GROUP_NEG", "pattern": "not all"}';
        $lines[] = '{"label": "GROUP_NEG", "pattern": "Not all"}';
        $lines[] = '{"label": "GNRD_HLT", "pattern": "beetles"}';
        $lines[] = '{"label": "GNRD_HLT", "pattern": "insects"}';
        $lines[] = '{"label": "GNRD_HLT", "pattern": "invertebrates"}';
        foreach($lines as $w) fwrite($f, $w."\n");

        /* write static entries in jsonl --- seems abandoned already
        $lines = array();
        $lines[] = '{"label": "NAME_POSTFIX", "pattern": "sp._n."}';
        foreach($lines as $w) fwrite($f, $w."\n");
        */
        
        /* write names */
        foreach($names as $name) {
            // /* manual adjustments due to OCR - works OK
            // if($name == "Eminoculus)\ dorsum") $name = "Eminoculus dorsum";
            // */
            if(self::taxon_is_species_level($name)) $w = '{"label": "GNRD_SLT", "pattern": "'.$name.'"}';
            else                                    $w = '{"label": "GNRD_HLT", "pattern": "'.$name.'"}';
            fwrite($f, $w."\n");
        }
        
        fclose($f);
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
        $needle = @$params['needle'];
        
        $url = $this->Endpoint."?op=$method&id=$item_id&idtype=$idtype&pages=t&ocr=t&parts=t&format=json&apikey=".$this->api_key;
        if($json = Functions::lookup_with_cache($url, $this->download_options)) {
            $objs = json_decode($json); //print_r($objs); exit;
            if(count($objs->Result) > 1) exit("\nThis item_id $item_id has multiple results.\n");
            foreach($objs->Result as $obj) { //just 1 object but with multiple pages
                // print_r($obj); exit("\n-end GetItemMetadata-\n");
                echo "\nItemID: ".$obj->ItemID."\n";
                echo "\nItemID has total pages: ".count($obj->Pages)."\n";
                
                if(!isset($params['needle'])) return $obj;
                
                self::write_item_info($obj);
                
                /* an item has a TitleID and multiple [Pages] with [OcrText] */
                // /* working OK but no longer needed
                foreach($obj->Pages as $page) { //print_r($page); exit;
                    // /* working Ok but not needed atm
                    if(stripos($page->OcrText, $needle) !== false) { //string is found
                        echo "\nFound OK 1 $needle in page $page->PageID.\n";
                        
                        self::save_page_ocr($page);
                        
                        if($names = self::get_names_for_PageID($page->PageID)) { //print_r($names); exit;
                            /*Array(
                                [0] => stdClass Object(
                                        [NameFound] => Araneae
                                        [NameConfirmed] => Araneae
                                        [NameCanonical] => Araneae
                                    )
                            */
                            foreach($names as $name) $this->namez[$name->NameFound] = '';
                        }
                    }
                    // else echo "\nNo $needle in page $page->PageID.\n";
                    // */
                }
                // */
            }
        }
        // exit("\ncha2\n");
    }
    private function get_names_for_PageID($page_id)
    {
        if($ret = self::GetPageMetadata(array('page_id'=>$page_id))) {
            $page_info = $ret->Result[0];
            // print_r($page_info); exit("\neee\n");
            if($names = $page_info->Names) {
                // print_r($names); //exit;
                echo "\nNames in PageID $page_id: ".count($names)."\n";
                return $names;
            }
        }
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
            return $objs;
            // print_r($objs); exit("\nelix1\n");
            foreach($objs->Result as $obj) { //but just 1 result anyway
                echo "\n$ctr. PageID: ".$obj->PageID."\n";
                /* a page has an [OcrText] */
                if($needle) { //if a value for needle is passed
                    if(stripos($obj->OcrText, $needle) !== false) { //string is found
                        echo "\nFound OK 2 $needle in page $page_id.\n";
                        // print_r($obj); exit;
                    }
                    else echo "\nNo $needle in page $page_id.\n";
                }
                // if($obj->Names) {
                //     print_r($obj); exit("\nhuli ka\n");
                // }
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
        // print_r($title_obj); exit;
        
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
        // $text = str_replace("- \n", "", $text); //not a good idea
        fwrite($f, $text."\n");
        fwrite($f, "=============== end of file ==============\n\n");
        fclose($f);
    }
    private function save_page_ocr($page)
    {   // print_r($page); exit;
        /*stdClass Object(
            [PageID] => 60852629
            [ItemID] => 292464
            [TextSource] => OCR
            [PageUrl] => https://www.biodiversitylibrary.org/page/60852629
            [ThumbnailUrl] => https://www.biodiversitylibrary.org/pagethumb/60852629
            [FullSizeImageUrl] => https://www.biodiversitylibrary.org/pageimage/60852629
            [OcrUrl] => https://www.biodiversitylibrary.org/pagetext/60852629
            [OcrText] => Preface 
        In August 2004, the city of Brisbane, Australia, was host to one of the largest recent ...
            [PageTypes] => Array(
                    [0] => stdClass Object(
                            [PageTypeName] => Text
                        )
                )
            [PageNumbers] => Array
                ()
        )
        */
        $f = Functions::file_open($this->pages_ocr_repo."/page_".$page->PageID.".txt", "w");
        $title = "PageID: ".$page->PageID;
        $pad = Functions::format_number_with_leading_zeros("", strlen($title));
        fwrite($f, "$pad "."ItemID: $page->ItemID"."\n");
        fwrite($f, $title."\n");
        fwrite($f, "$pad\n\n");
        fwrite($f, $page->OcrText."\n");
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
    private function taxon_is_species_level($name)
    {
        $arr = explode(" ", trim($name));
        if(count($arr) > 1) return true;
        else return false;
    }
    function get_PageId_where_string_exists_in_ItemID($item_id, $string, $marker, $start_row)
    {
        // /* manual adjustments due to OCR
        if($string == "Leptusa pulchella" && $item_id == 135948) $string = "Lepcusa pulchella";
        if($string == "Epuraea rufomarginata" && $item_id == 285968) $string = "Epnraea rnfomarginata";
        if($string == "Rhyzodiastes mirabilis" && $item_id == 124942) $string = "Rhyzodiastes mirabitis";
        if($string == "Platylomalus terrareginae" && $item_id == 124942) $string = "Plat\'lonialits terrareginae";
        if($string == "Platylomalus saucius" && $item_id == 124942) $string = "Platvlomalus saucius";
        if($string == "Prosopocoilus torresensis" && $item_id == 124942) $string = "Prosopocoilus lorresensis";
        if($string == "Mastachilus australasicus" && $item_id == 124942) $string = "Mastachilus uustralasicus";
        if($string == "Galbodema mannerheimi" && $item_id == 124942) $string = "Galhodema mannerheimi";
        if($string == "Trichalus ater" && $item_id == 124942) $string = "Triehalus ater";
        if($string == "Sphaerarthrum rubriceps" && $item_id == 124942) $string = "Snhaerarthrum rubriceps";
        if($string == "Shoguna termitiformis" && $item_id == 124942) $string = "Shoguna lermitiformis";
        if($string == "Aphanocephalus poropterus" && $item_id == 124942) $string = "Anhanocephalus poropterus";
        if($string == "Stenotarsus pisoniae" && $item_id == 124942) $string = "Stenatarsus pisoniae";
        if($string == "Tentablabus fulvus" && $item_id == 124942) $string = "Tentablabus fulvtis";
        if($string == "Archaeoglenes australis" && $item_id == 124942) $string = "Archaeoglenes auslralis";
        if($string == "Paraphanes nitidus" && $item_id == 124942) $string = "Paraphanes nilidus";
        if($string == "Morpholycus flabellicornis" && $item_id == 124942) $string = "Morpholvcus flabellicornis";
        if($string == "Commista latifrons" && $item_id == 124942) $string = "Commisla latifrons";
        

        // Epnraea bignttata (Thunberg) 
        // Epnraea distincta (Grimmer) 
        // Epnraea marsneli Reitter 
        // Epnraea rnfomarginata (Steph.)
        // 
        // Epuraea biguttata    part_14.txt 235285
        // Epuraea distincta    part_14.txt 235285
        // Epuraea marsueli part_14.txt 235285        
        // */
        
        echo "\nSearching [$string]...\n";
        $idtype = 'bhl';
        $obj = self::GetItemMetadata(array('item_id'=>$item_id, 'idtype'=>$idtype)); // get Pages using ItemID
        if($page_id = self::find_page_given_string_and_Pages_opt1($obj->Pages, $string, $marker, $start_row)) return $page_id; // single row exact match on string
        echo "\npage_id = [$page_id]\n";
        exit("\n-end muna dito\n");
    }
    private function find_page_given_string_and_Pages_opt1($Pages, $string, $marker, $start_row)
    {
        $met_StartRow_YN = false;
        $final = array();
        // Step 1: get all pages where string exists in any of its lines
        foreach($Pages as $page) {
            if($ocr = @$page->OcrText) {
                $lines = explode("\n", $ocr);
                $lines = array_map('trim', $lines);
                foreach($lines as $line) {
                    if(substr($line,0,strlen($start_row)) == $start_row) $met_StartRow_YN = true;
                    if(stripos($line, $string) !== false) { //string is found
                        if($met_StartRow_YN) $final[$page->PageID][] = $line;
                        // $final[$page->PageID][] = $line;
                        // print_r($page);
                    }
                }
            }
        }
        if($final) print_r($final);
        else exit("\nNothing as early as here...\n");
        /*Array( obsolete
            [47086871] => Abraeus globosus 14
            [47086833] => Histeridae: Abraeus globosus (Hoffmann), Paromalus flavicornis (Herbst). Ptilidae:
        )*/
        /*Array( new format of the array()
            [54154962] => Array(
                    [0] => Thamiaraea cinnamomea
                )
            [54154963] => Array(
                    [0] => Thamiaraea cinnamomea
                )
            [54154969] => Array(
                    [0] => Thamiaraea cinnamomea (Grav.)*
                    [1] => Thamiaraea cinnamomea (Grav.) 2
                )
        )*/
        if($marker) { // meaning there is a marker e.g. "*"
            exit("\nShould not go here for now...\n");
            /* Choose the PageID where sciname has the marker e.g. '54154969' in sample above.
               No case with markers for now. That's why I didn't put the necessary code for it yet. */
        }
        else { // choose the smallest strlen
            // Step 2: strlen() the line
            $final2 = array();
            if($final) {
                // foreach($final as $pageID => $line) $final2[$pageID] = strlen($line);
                foreach($final as $pageID => $lines) {
                    foreach($lines as $line) $final2[$pageID][] = strlen($line);
                }
                print_r($final2);
            }
            // Step 3: return the first index key which is the pageID
            if($final2) {
                asort($final2);
                print_r($final2);
                foreach($final2 as $pageID => $length) return $pageID; // return the first pageID
            }
            else exit("\nInvestigate: string not found [$string]. Should not go here\n");
        }
        
        return false;
        exit("\nelix 1\n");
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