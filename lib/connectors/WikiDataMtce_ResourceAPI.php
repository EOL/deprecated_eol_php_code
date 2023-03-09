<?php
namespace php_active_record;
/* 
called from WikiDataMtceAPI.php
*/
class WikiDataMtce_ResourceAPI
{
    function __construct()
    {
        // $this->download_options = array('cache' => 1, 'download_wait_time' => 500000, 'timeout' => 10800, 'expire_seconds' => 60*60*1);

        /*
        name: native range includes     uri: http://eol.org/schema/terms/NativeRange
        name: endemic to                uri: http://eol.org/terms/endemic
        name: geographic distribution   uri: http://eol.org/schema/terms/Present
        Flora do Brasil: 753
            measurementTypes to use:        
            http://eol.org/schema/terms/NativeRange
            http://eol.org/terms/endemic        
        Kubitzki et al: 822
            measurementTypes to use:
            http://eol.org/schema/terms/NativeRange
            http://eol.org/schema/terms/Present
        */
        $this->mType_label_uri['native range includes']   = 'http://eol.org/schema/terms/NativeRange';
        $this->mType_label_uri['native range']            = 'http://eol.org/schema/terms/NativeRange';
        $this->mType_label_uri['endemic to']              = 'http://eol.org/terms/endemic';
        $this->mType_label_uri['geographic distribution'] = 'http://eol.org/schema/terms/Present';
    }

    function run_1_resource_traits($rec, $task)
    {   
        print_r($rec); //exit("\n[$task]\nstop 173\n");
        /* e.g. Array(
            [r.resource_id] => 753
            [trait.source] => 
            [trait.citation] => 
        )*/
        /* good way to run 1 resource for investigation
        if($rec['r.resource_id'] != 753) return; // Flora do Brasil           
        */

        $input = array();
        $input["params"] = array("resource_id" => (int) $rec['r.resource_id']);
        $input["type"] = "wikidata_base_qry_resourceID";
        $input["per_page"] = $this->per_page_2; //1000
        
        $input["trait kind"] = "trait";

        // $json = json_encode($input);
        // print_r($input); exit("\n[$json]\nstop muna2\n");

        $path1 = $this->generate_report_path($input); echo "\n".$input["trait kind"]." path: [$path1]\n";
        $file1 = $path1.$input['trait kind']."_qry.tsv";
        $this->tmp_batch_export = $path1 . "/temp_export.qs";

        $input["trait kind"] = "inferred_trait";
        $path2 = $this->generate_report_path($input); echo "\n".$input["trait kind"]." path: [$path2]\n";
        $file2 = $path2.$input['trait kind']."_qry.tsv";
        $this->tmp_batch_export = $path2 . "/temp_export.qs";

        print_r($input);
        // exit("\n$file1\n$file2\nxxx\n");

        // /*
        $input["trait kind"] = "trait";
        if(file_exists($file1)) {
            if($task == 'generate trait reports') $this->create_WD_traits($input);
            elseif($task == 'create WD traits') self::divide_exportfile_send_2quickstatements($input);
        }
        else echo "\n[$file1]\nNo query results yet: ".$input['trait kind']."\n";
        // */
        // /*
        $input["trait kind"] = "inferred_trait";
        if(file_exists($file2)) {
            if($task == 'generate trait reports') self::create_WD_traits($input);
            elseif($task == 'create WD traits') self::divide_exportfile_send_2quickstatements($input);
            elseif($task == 'remove WD traits') self::divide_exportfile_send_2quickstatements($input, true); //2nd param remove_traits_YN
        }
        else echo "\n[$file2]\nNo query results yet: ".$input['trait kind']."\n";
        // */

        // $func->divide_exportfile_send_2quickstatements($input); exit("\n-end divide_exportfile_send_2quickstatements() -\n");
        return array($path1, $path2);
    }
    function adjust_record($rec)
    {   /*Array(
            [p.canonical] => Amphisolenia schauinslandi
            [p.page_id] => 48894874
            [pred.name] => native range includes
            [stage.name] => 
            [sex.name] => 
            [stat.name] => 
            [obj.name] => Rio Grande Do Sul
            [obj.uri] => https://www.geonames.org/3451133
            [t.measurement] => 
            [units.name] => 
            [t.source] => http://reflora.jbrj.gov.br/reflora/floradobrasil/FB111250
            [t.citation] => Brazil Flora G (2019). Brazilian Flora 2020 project - Projeto Flora do Brasil 2020. Version 393.206. Instituto de Pesquisas Jardim Botanico do Rio de Janeiro. Checklist dataset https://doi.org/10.15468/1mtkaw accessed via GBIF.org on 2023-02-14
            [ref.literal] => Balech, E.. . Los Dinoflagelados del Atlantico Sudoccidental. Publ. Espec. Instituto Español de Oceanografia, Madrid,,.
        )*/
        return $rec;
    }
    function lookup_geonames_4_WD($rec)
    {   /*Array(
            [p.canonical] => Closterium turgidum giganteum
            [p.page_id] => 51840488
            [pred.name] => native range includes
            [stage.name] => 
            [sex.name] => 
            [stat.name] => 
            [obj.name] => Bahia
            [obj.uri] => https://www.geonames.org/3471168
            [t.measurement] => 
            [units.name] => 
            [t.source] => http://reflora.jbrj.gov.br/reflora/floradobrasil/FB107192
            [t.citation] => Brazil Flora G (2019). Brazilian Flora 2020 project - Projeto Flora do Brasil 2020. Version 393.206. Instituto de Pesquisas Jardim Botanico do Rio de Janeiro. Checklist dataset https://doi.org/10.15468/1mtkaw accessed via GBIF.org on 2023-02-14
            [ref.literal] => OLIVEIRA, I.B.; BICUDO, C.E.M. & MOURA, C.W.N.. . Iheringia, Ser. Bot.,(),.
            [how] => identifier-map
        )*/
        if(preg_match("/geonames.org\/(.*?)elix/ims", $rec['obj.uri']."elix", $arr)) {
            $geonames_id = $arr[1];
            if($WD_entity = self::get_WD_id_using_geonames($geonames_id)) return $WD_entity;
        }
    }
    private function get_WD_id_using_geonames($geonames_id) //$geonames_id e.g. 3471168 for "Bahia"
    {
        // https://query.wikidata.org/sparql?query=SELECT ?s WHERE {VALUES ?id {"3393129"} ?s wdt:P1566 ?id }

        // https://www.geonames.org/3451133
        // https://query.wikidata.org/sparql?query=SELECT ?s WHERE {VALUES ?id {"3451133"} ?s wdt:P1566 ?id }
        // http://www.wikidata.org/entity/Q40030

        $qry = 'SELECT ?s WHERE {VALUES ?id {"'.$geonames_id.'"} ?s wdt:P1566 ?id }';
        $url = "https://query.wikidata.org/sparql?query=";
        $url .= urlencode($qry);
        $options = $this->download_options;
        $options['expire_seconds'] = false;
        if($xml = Functions::lookup_with_cache($url, $options)) { // print_r($xml);
            // <uri>http://www.wikidata.org/entity/Q43255</uri>
            // exit("\n".$xml."\n");
            if(preg_match("/<uri>(.*?)<\/uri>/ims", $xml, $arr)) { // print_r($arr[1]);
                return $arr[1];
            }
            else exit("\nmay prob.\n");
        }    
    }
    function xxx()
    {
        $url = "http://www.marinespecies.org/imis.php?module=person&show=search&fulllist=1";
        $options = $this->download_options;
        $options['expire_seconds'] = 60*60*24*30; //a month to expire
        if($html = Functions::lookup_with_cache($url, $options)) {
        }
    }
}
?>