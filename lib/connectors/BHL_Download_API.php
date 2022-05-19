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
        $this->download_options = array('resource_id' => "BHL", 'timeout' => 172800, 'expire_seconds' => 60*60*24*30*1, 'download_wait_time' => 2000000);
        $this->Endpoint = "https://www.biodiversitylibrary.org/api3";
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
        $page = 0;
        $results = true;
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
                foreach($objects->Result as $obj) {
                    if($obj->BHLType == 'Part') {
                        $type = 'part';
                        $id = $obj->PartID;
                        $idtype = 'bhl';
                        // self::GetPartMetadata($id, $idtype); //no OCR text yet, but with multiple pages
                    }
                    elseif($obj->BHLType == 'Item') {
                        $type = 'item';
                        $id = $obj->ItemID;
                        // print_r($obj); exit("\ntype == 'Item'\n");
                        $idtype = 'bhl';
                        self::GetItemMetadata($id, $idtype);
                    }
                    else { print_r($obj); exit("\nun-classified BHLType\n"); }
                }
                echo "\nRecords: ".count($objects->Result)."\n";
                $results = $objects->Result;
            }
        }
    }
    function GetPartMetadata($id, $idtype, $method = "GetPartMetadata") //no OCR text yet, but with multiple pages
    {   /* If it has [ExternalUrl], then it won't have [Pages]
        https://www.biodiversitylibrary.org/api3?op=GetPartMetadata
        &id=<identifier of a part (article, chapter, ect)>
        &idtype=<bhl, doi, jstor, biostor, or soulsby (OPTIONAL; "bhl" is the default)>
        &pages=<"t" or "true" to include the part's pages in the response>
        &names=<"t" or "true" to include scientific names in the part in the response>
        &apikey=<API key value>
        */
        $url = $this->Endpoint."?op=$method&id=$id&idtype=$idtype&pages=t&names=t&parts=t&format=json&apikey=".$this->api_key;
        if($json = Functions::lookup_with_cache($url, $this->download_options)) {
            $obj = json_decode($json);
            print_r($obj); //exit("\n-end GetPartMetadata-\n");
        }
    }
    function GetItemMetadata($id, $idtype, $method = "GetItemMetadata") //can consist of multiple pages. 
    {   /*                                                                No need to lookup GetPageMetadata() for OCR text
        https://www.biodiversitylibrary.org/api3?op=GetItemMetadata
        &id=<identifier of an item>
        &idtype=<"bhl" if id is a BHL identifier, "ia" if id is an Internet Archive identifier (OPTIONAL; "bhl" is the default)>
        &pages=<"t" or "true" to include page details in the response>
        &ocr=<"t" or "true" to include the page text in the response>
        &parts=<"t" or "true" to include parts in the item in the response>
        &apikey=<API key value>
        */
        $url = $this->Endpoint."?op=$method&id=$id&idtype=$idtype&pages=t&ocr=t&parts=t&format=json&apikey=".$this->api_key;
        if($json = Functions::lookup_with_cache($url, $this->download_options)) {
            $obj = json_decode($json);
            print_r($obj); //exit("\n-end GetItemMetadata-\n");
        }
    }
    function GetPageMetadata($page_id, $method = "GetPageMetadata")
    {   /* GetPageMetadata
        Return metadata about a page. You may choose to include the text and a list of names found on the page.
        Example - https://www.biodiversitylibrary.org/api3?op=GetPageMetadata&pageid=3137380&ocr=t&names=t&apikey=<key+value>
        pageid - the identifier of an individual page in a scanned book
        ocr - "t" or "true" to return text of the page
        names - "t" or "true" to return the names that appear on the page
        */
        $url = $this->Endpoint."?op=$method&pageid=$page_id&ocr=t&names=t&format=json&apikey=".$this->api_key;
        if($json = Functions::lookup_with_cache($url, $this->download_options)) {
            $obj = json_decode($json);
            print_r($obj);
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