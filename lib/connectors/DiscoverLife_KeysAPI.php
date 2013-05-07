<?php
namespace php_active_record;
/* connector: 252 
--- DiscoverLife ID keys resource [252]
Leo Shapiro with his exchanges with the partner, provided a text file (or spreadsheet) to BIG.
The text file is a list of taxa with links (URLs) to the Identification page in DL website.
The connector will check the EOL DB if the DL taxon name has an EOL page. If yes, then EOL will get the ID keys info for it.
If no, then this name will be reported back to DL.
*/

class DiscoverLife_KeysAPI
{
    const DL_SEARCH_URL     = "http://www.discoverlife.org/mp/20q?search=";
    const API_URL           = "http://eol.org/api/search/";
    const TEXT_FILE_FOR_DL  = "/update_resources/connectors/files/DiscoverLife/names_without_pages_in_eol"; //report back to DiscoverLife
    const TEMP_FILE_PATH    = "/update_resources/connectors/files/DiscoverLife/";

    // const ID_KEYS_FILE = "http://dl.dropbox.com/u/7597512/DiscoverLife/ID%20keys%20spreadsheet%2030March2011_small.txt";
    const ID_KEYS_FILE = "http://dl.dropbox.com/u/7597512/DiscoverLife/ID%20keys%20spreadsheet%2030March2011.txt";

    public function get_all_taxa_keys($resource_id)
    {
        require_library('connectors/DiscoverLifeAPIv2');
        $func = new DiscoverLifeAPIv2();
        $taxa_objects = self::process_keys_spreadsheet();
        $all_taxa = array();
        $used_collection_ids = array();

        //initialize text file for DiscoverLife: save names without a page in EOL
        self::initialize_text_file(DOC_ROOT . self::TEXT_FILE_FOR_DL . "_" . "id_keys" . ".txt");

        $i = 0;
        $save_count = 0;
        $no_eol_page = 0;
        foreach($taxa_objects as $name => $fields)
        {
            $i++;
            //filter names. Process only those who already have a page in EOL. Report back to DiscoverLife names not found in EOL
            if(!$taxon = $func->with_eol_page($name))
            {
                print "\n $i - no EOL page ($name)";
                $no_eol_page++;
                self::store_name_to_text_file($name, "ID_Keys");
                continue;
            }
            $taxon["keys"] = array();
            foreach($fields as $field) $taxon["keys"][] = $field;
            print "\n $i -- " . $taxon['orig_sciname'];

            //================================
            $arr = self::get_discoverlife_taxa($taxon, $used_collection_ids);
            $page_taxa              = $arr[0];
            $used_collection_ids    = $arr[1];
            if($page_taxa) $all_taxa = array_merge($all_taxa, $page_taxa);
            unset($page_taxa);
        }

        $xml = \SchemaDocument::get_taxon_xml($all_taxa);
        $resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
        $OUT = fopen($resource_path, "w");
        fwrite($OUT, $xml);
        fclose($OUT);
                
        $with_eol_page = $i-$no_eol_page;
        print "\n\n total = $i \n With EOL page = $with_eol_page \n No EOL page = $no_eol_page \n\n";
    }

    private function process_keys_spreadsheet()
    {
        $taxa_objects = array();
        $filename = Functions::save_remote_file_to_local(self::ID_KEYS_FILE, DOWNLOAD_WAIT_TIME, 4800, 5);
        print "\n[$filename]\n";
        foreach(new FileIterator($filename, true) as $line_number => $line) // 'true' will auto delete temp_filepath
        {
            $line = trim($line);
            $fields = explode("\t", $line);
            $name = trim($fields[0]);
            print "\n name: $name";
            if($id_key1 = trim(@$fields[1])) $taxa_objects[$name][] = $id_key1;
            if($id_key2 = trim(@$fields[2])) $taxa_objects[$name][] = $id_key2;
            if($id_key3 = trim(@$fields[3])) $taxa_objects[$name][] = $id_key3;
        }
        if(count($taxa_objects) <= 1)
        {
            echo "\n\nInvalid text file. Program will terminate.\n";
            return;
        }
        return $taxa_objects;
    }

    function get_discoverlife_taxa($taxon, $used_collection_ids)
    {
        $response = self::prepare_object($taxon);//this will output the raw (but structured) array
        $page_taxa = array();
        foreach($response as $rec)
        {
            if(@$used_collection_ids[$rec["identifier"]]) continue;
            $taxon = Functions::prepare_taxon_params($rec);
            if($taxon) $page_taxa[] = $taxon;
            @$used_collection_ids[$rec["identifier"]] = true;
        }
        return array($page_taxa, $used_collection_ids);
    }

    private function prepare_object($taxon_rec)
    {
        $taxon = $taxon_rec["orig_sciname"];
        $arr_taxa = array();
        $source = self::DL_SEARCH_URL . str_replace(" ", "+", $taxon);
        $taxon_id = $source;
        $arr_objects=array();

        if(@$taxon_rec["keys"])
        {
            $description = "";
            foreach($taxon_rec["keys"] as $idkey)
            {
                $idkey = str_ireplace('""', "'", $idkey);
                $idkey = str_ireplace('"', "", $idkey);
                $target = "";
                if(preg_match("/guide\=(.*?)\'/ims", $idkey, $match)) $target = $match[1];
                $idkey = str_ireplace("<a href", "<a target='$target' href", $idkey);
                $description .= "$idkey <br><br>";
            }
            $identifier = str_replace(" ", "_", $taxon) . "_idkeys";
            $mimeType   = "text/html";
            $dataType   = "http://purl.org/dc/dcmitype/Text";
            $title = "IDnature guides";
            $subject    = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Key"; //orig value: Key
            $agent = array();
            $agent[] = array("role" => "compiler", "homepage" => "http://www.discoverlife.org/", "fullName" => "John Pickering");
            $mediaURL = ""; 
            $location = "";
            $license = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
            $rightsHolder = "Discover Life and original sources";
            $refs = array();
            $arr_objects[] = self::add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $agent, $license, $location, $rightsHolder, $refs, $subject);
        }

        if(sizeof($arr_objects))
        {
            $arr_taxa[] = array("identifier"   => $taxon_id,
                                "source"       => $source,
                                "sciname"      => $taxon,
                                "data_objects" => $arr_objects
                               );
        }
        return $arr_taxa;
    }

    private function add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $agent, $license, $location, $rightsHolder, $refs, $subject)
    {
        return array( "identifier"   => $identifier,
                      "dataType"     => $dataType,
                      "mimeType"     => $mimeType,
                      "title"        => $title,
                      "source"       => $source,
                      "description"  => $description,
                      "mediaURL"     => $mediaURL,
                      "agent"        => $agent,
                      "license"      => $license,
                      "location"     => $location,
                      "rightsHolder" => $rightsHolder,
                      "object_refs"  => $refs,
                      "subject"      => $subject
                    );
    }

    private function initialize_text_file($filename)
    {
        $OUT = fopen($filename, "a");
        fwrite($OUT, "===================" . "\n");
        fwrite($OUT, date("F j, Y, g:i:s a") . "\n");
        fclose($OUT);
    }

    private function store_name_to_text_file($name, $post_name)
    {
        /* This text file will be given to DiscoverLife so they can fix their names */
        if($fp = fopen(DOC_ROOT . self::TEXT_FILE_FOR_DL . "_" . $post_name . ".txt", "a"))
        {
            fwrite($fp, $name . "\n");
            fclose($fp);
        }
    }

}
?>