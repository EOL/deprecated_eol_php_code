<?php
namespace php_active_record;
/* connector: freedata_globi.php 
added 1st column headers in its resource file:
dynamichierarchytrunk14jun201720170615085118/

*/
class DHSmasherOutputAPI
{
    function __construct($params)
    {
        $this->params = $params;
        $this->debug = array();
    }

    private function initialize()
    {
        require_library('connectors/FreeDataAPI');
        $func = new FreeDataAPI();
        $func->create_folder_if_does_not_exist($this->folder);
        return $func;
    }

    private function adjust_filename($url)
    {   // /Library/WebServer/Documents/eol_php_code/
        // http://localhost/cp/dynamic_hierarchy/smasher/EOLDynamicHierarchyDraftAug2017/dwh_taxa.txt
        $url = str_ireplace("http://localhost", "", $url);
        return str_replace("eol_php_code/", "", DOC_ROOT).$url;
    }
    
    function start()
    {
        $excluded_acronyms = array('gbif', 'WOR', 'ictv');
        $smasher_file = self::adjust_filename($this->params["smasher"]["url"]);
        $i = 0;
        foreach(new FileIterator($smasher_file) as $line => $row) {
            $i++;
            if($i == 1) $fields = explode("\t", $row);
            else {
                $rec = explode("\t", $row);
                $k = -1;
                $rek = array();
                foreach($fields as $field) {
                    $k++;
                    if($val = @$rec[$k]) $rek[$field] = $val;
                }
                if($rek)
                {
                    $first_source = self::get_first_source($rek['source']);
                    print_r($first_source);
                    if(in_array($first_source['acronym'], $excluded_acronyms)) continue;
                    self::process_record($rek, $first_source);
                }
            }
        }
    }

    private function get_first_source($source)
    {
        $a = explode(",", $source);
        $f['first_source'] = trim($a[0]);
        $a = explode(":", $f['first_source']);
        $f['acronym'] = trim($a[0]);
        $f['taxon_id'] = trim($a[1]);
        return $f;
    }
    private function get_scientificName($rek, $first_source, $fetched)
    {   /* Here is how the different resources should be treated:
        1. Fetch scientificName from first source without modifications: AMP,EET,gbif,ictv,IOC,lhw,ODO,ONY,PPG,SPI,TER,WOR
        2. Fetch scientificName from first source, add a blank space and then the contents of the scientificNameAuthorship field: 
        APH,BLA,COL,COR,DER,EMB,GRY,LYG,MAN,MNT,ORTH,PHA,PLE,PSO,TPL(except genus and family ranks, see below),trunk,ZOR
        */
        $opt[1] = array("AMP","EET","gbif","ictv","IOC","lhw","ODO","ONY","PPG","SPI","TER","WOR");
        $opt[2] = array("APH","BLA","COL","COR","DER","EMB","GRY","LYG","MAN","MNT","ORTH","PHA","PLE","PSO","TPL","trunk","ZOR"); // TPL (except genus and family ranks, see below)

        $sciname = @$fetched['fetched']['scientificName'];
        $sciname = str_ireplace("†", "", $sciname);

        if(in_array($first_source['acronym'], $opt[1])) $final['scientificName'] = $sciname;
        elseif(in_array($first_source['acronym'], $opt[2]))
        {
            /*if TPL:
                scientificName: Smasher output verbatim
                canonicalName,scientificNameAuthorship,scientificNameID,taxonRemarks,namePublishedIn,furtherInformationURL: nothing
                datasetID: TPL
            */
            if($first_source['acronym'] == "TPL" && in_array($rek['taxonRank'], array("genus", "family"))) $final['scientificName'] = $rek['scientificName'];
            $final['scientificName'] = $sciname." ".$fetched['fetched']['scientificNameAuthorship'];
        }
        else exit("\ninvestigate: [".$first_source['acronym']."] not in the list\n");
        return $final;
    }
    private function process_record($rek, $first_source)
    {
        print_r($rek);

        $rec = array();
        /*
        We want to update the values for the scientificName column and add the following columns to the Smasher output file:
        http://rs.gbif.org/terms/1.0/canonicalName
        http://rs.tdwg.org/dwc/terms/scientificNameAuthorship
        http://rs.tdwg.org/dwc/terms/scientificNameID
        http://rs.tdwg.org/dwc/terms/taxonRemarks
        http://rs.tdwg.org/dwc/terms/namePublishedIn
        http://rs.tdwg.org/ac/terms/furtherInformationURL
        http://rs.tdwg.org/dwc/terms/datasetID
        http://eol.org/schema/EOLid - this is a made-up uri for now
        */
        
        
        if($first_source['acronym'] == "TPL" && in_array($rek['taxonRank'], array("genus", "family"))) $d['fetched'] = array();
        else $d['fetched'] = self::fetch_record($first_source);
        print_r($d);
        
        $rec = self::get_scientificName($rek, $first_source, $d);
        print_r($rec);
        echo "\n-------------------------------------------------------------\n";
        
        // exit("\n")
        // $rec = array_map('trim', $rec);
        // $func->print_header($rec, CONTENT_RESOURCE_LOCAL_PATH . "$this->folder/observations.txt");
        // $val = implode("\t", $rec);
        // self::save_to_text_file($val);
    }
    
    function fetch_record($first)
    {   /* Array (
            [first_source] => gbif:2058421
            [acronym] => gbif
            [taxon_id] => 2058421
        )*/
        $txtfile = self::adjust_filename($this->params[$first['acronym']]["url"]);
        if(!file_exists($txtfile)) exit("\nfile does not exist: [$txtfile]\n");
        else echo "\nfound: [$txtfile]";
        $i = 0;
        foreach(new FileIterator($txtfile) as $line => $row) {
            $i++;
            if($i == 1)
            {
                $fields = explode("\t", $row);
                if(!in_array("taxonID", $fields)) exit("\nCannot search, no taxonID from resource file.\n");
            }
            else {
                $rec = explode("\t", $row);
                $k = -1;
                $rek = array();
                foreach($fields as $field) {
                    $k++;
                    if($val = @$rec[$k]) $rek[$field] = $val;
                }
                if($first['taxon_id'] == @$rek['taxonID']) return $rek;
            }
        }
        return false;
    }
    

    private function save_to_text_file($row)
    {
        if($row)
        {
            $WRITE = Functions::file_open($this->destination[$this->folder], "a");
            fwrite($WRITE, $row . "\n");
            fclose($WRITE);
        }
    }

    function extract_file($zip_path)
    {
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($zip_path, "interactions.tsv", array('timeout' => 172800, 'expire_seconds' => 2592000)); //expires in 1 month
        return $paths;
    }

}
?>