<?php
namespace php_active_record;
// connector: [xeno_canto.php]
class XenoCantoAPI
{
    function __construct($folder)
    {
        $this->resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));

        $this->download_options = array(
            'resource_id'        => "xeno_c", //$this->resource_id,  //resource_id here is just a folder name in cache
            'expire_seconds'     => 60*60*24*30*3, //expires quarterly
            'download_wait_time' => 1000000, 'timeout' => 60*3, 'download_attempts' => 1, 'delay_in_minutes' => 1, 'cache' => 1);
        $this->domain = 'https://www.xeno-canto.org';        
        $this->species_list     = $this->domain.'/collection/species/all';
        $this->api['query']     = $this->domain.'/api/2/recordings?query=';
        // $this->api['query']     = $this->domain.'/api/2/recordings?query=q:A+';
        $this->recorders_list   = $this->domain.'/contributors?q=all';
        $this->recorder_url     = "https://xeno-canto.org/contributor/"; //append the recorder id e.g. "NQMGMOJOHV"
        $this->sound_file_url   = "https://xeno-canto.org/sounds/uploaded/"; //first part of the accessURI
        $this->debug = array();
    }
    function start()
    {
        $this->recorders_info = self::buildup_recorders_list();
        /* Additional */
        $this->recorders_info["Tony Archer"] = $this->recorders_info["Tony Archer †"];
        $this->recorders_info["Rosendo Fraga"] = "JQSYKZHEMB"; //written only as "Rosendo Fraga", in media object 
        $this->recorders_info["Lars Lachmann"] = "HEYJSRUDZZ";
        // $this->recorders_info["Juan Pablo Culasso"] = $this->recorders_info["juan Pablo Culasso"];
        // $this->recorders_info["Nunes D´Acosta"] = $this->recorders_info["Juvêncio da Costa Nunes Neto"];
        $this->recorders_info["Rafael Martos Martins"] = "VXDVESQDET";
        $this->recorders_info["Joao Menezes"] = "BEYMZHZHIB";
        $this->recorders_info["João Menezes"] = "BEYMZHZHIB";        
        $this->recorders_info["Elisa M. Huanca Plata - Universidad Técnica de Oruro"] = "GHVLJLRSAL";
        $this->recorders_info["Elisa Huanca"] = "GHVLJLRSAL";
        $this->recorders_info["James Lidster"] = "NRUIFMFTXY";
        // print_r($this->recorders_info); exit;
        self::main(); //main operation
        print_r($this->debug);
    }
    function main()
    {   
        if($html = Functions::lookup_with_cache($this->species_list, $this->download_options)) {
            // echo $html;
            if(preg_match_all("/<tr class(.*?)<\/tr>/ims", $html, $arr)) { // print_r($arr[1]);
                $i = 0;
                $total = count($arr[1]);
                foreach($arr[1] as $r) { $i++; 
                    // if(($i % 100) == 0)

                    // /* ---------- during caching only
                    $m = $total/4;
                    if($i >= 1    && $i <= $m   ) {$k=$m;}   else continue;
                    // if($i >= $m   && $i <= $m*2 ) {$k=$m*2;} else continue;
                    // if($i >= $m*2 && $i <= $m*3 ) {$k=$m*3;} else continue;
                    // if($i >= $m*3 && $i <= $m*4 ) {$k=$m*4;} else continue;
                    echo "\n$i of $total [$k]\n";
                    // ---------- */
                    /*
                    echo "\n$i of $total\n"; //normal operation
                    */

                    /*[0] => ='new-species'>
                        <td>
                        <span clas='common-name'>
                        <a href="/species/Struthio-camelus">Common Ostrich</a>
                        </span>
                        </td>
                        <td>Struthio camelus</td>
                        <td></td>
                        <td align='right' width='20'>3</td>
                        <td align='right' width='30'>0</td>
                    */
                    $rec = array();
                    if(preg_match("/<span clas='common-name'>(.*?)<\/span>/ims", $r, $arr)) $rec['comname'] = trim(strip_tags($arr[1]));
                    if(preg_match("/href=\"(.*?)\"/ims", $r, $arr)) $rec['url'] = strip_tags($arr[1]);
                    if(preg_match_all("/<td>(.*?)<\/td>/ims", $r, $arr)) {
                        $rec['sciname'] = $arr[1][1];
                    }
                    $rec = array_map('trim', $rec); print_r($rec); //exit;
                    /*Array(
                        [comname] => Common Ostrich
                        [url] => https://xeno-canto.org/species/Struthio-camelus
                        [sciname] => Struthio camelus
                    )*/
                    if($rec['sciname'] && $rec['url']) {

                        // /* ---------- ver. 2
                        $rec = self::parse_order_family($rec); // print_r($rec); exit;
                        $ret = self::prepare_media_records($rec);
                        // print_r($ret); exit;
                        self::write_taxon($ret['orig_rec']);
                        if($val = $ret['media']) self::write_media($val);
                        else continue; //didn't get anything for media


                        // print_r($rec); exit("\nstop muna\n");
                        // ---------- end ver. 2 */
                    }
                    if($i >= 5) break;
                    // break;
                }
            }
            else echo "\nnothing found...\n";
        }
        else echo "\nno HTML\n";
        // exit("\n111\n");
        $this->archive_builder->finalize(TRUE);
    }
    private function parse_order_family($orig_rec)
    {
        if($html = Functions::lookup_with_cache($orig_rec['url'], $this->download_options)) {
            /*
            <li>Order: <a href='https://xeno-canto.org/explore/taxonomy?ord=STRUTHIONIFORMES'>STRUTHIONIFORMES</a></li>
            <ul class='family'>
            <li>Family: <a href='https://xeno-canto.org/explore/taxonomy?fam=Struthionidae'>Struthionidae</a> (Ostriches)</li>
            */
            if(preg_match("/taxonomy\?ord\=(.*?)\'/ims", $html, $arr)) {
                $orig_rec['order'] = ucfirst(strtolower($arr[1]));
            }
            if(preg_match("/taxonomy\?fam\=(.*?)\'/ims", $html, $arr)) {
                $orig_rec['family'] = ucfirst(strtolower($arr[1]));
            }
            $orig_rec['taxonID'] = strtolower(str_replace(" ", "-", $orig_rec['sciname']));


            // /* --------------- special search for recorders info
            // <a href="https://xeno-canto.org/contributor/NRUIFMFTXY">James Lidster</a>
            if(preg_match_all("/xeno\-canto\.org\/contributor\/(.*?)<\/a>/ims", $html, $arr)) { // print_r($arr[1]); exit;
                /* Array(
                    [0] => YTUXOCTUEM'>Frank Lambert
                    [1] => XKXDFWNSPA'>Jeremy Hegge
                    [2] => XKXDFWNSPA'>Jeremy Hegge
                    [3] => DNKBTPCMSQ'>Derek Solomon
                    [4] => HMBDEWEZVS'>Tony Archer
                    [5] => SGLTZLDXYI'>Lynette Rudman
                )*/
                foreach($arr[1] as $str) {
                    if(preg_match("/elicha(.*?)\'/ims", "elicha".$str, $arr2)) {
                        $index = $arr2[1];
                        if(preg_match("/>(.*?)elicha/ims", $str."elicha", $arr2)) {
                            $name = $arr2[1];
                            $this->recorders_info[$name] = $index;
                        }
                    }
                }
            }
            // exit("\n-wip-\n");
            // --------------- end */
            return $orig_rec;
        }
    }
    private function prepare_media_records($rec) //$rec is $orig_rec
    {
        $final = array();
        // $rec['sciname'] = 'Troglodytes troglodytes'; //debug only
        $url = $this->api['query'].str_replace(" ", "+",$rec['sciname'])."&page=1";
        echo "\nA[$url]...\n";
        if($json = Functions::lookup_with_cache($url, $this->download_options)) {
            $obj = json_decode($json); // print_r($obj); exit;
            for($page = 1; $page <= $obj->numPages; $page++) { // echo "\nPage: $page\n";
                $url = $this->api['query'].str_replace(" ", "+",$rec['sciname'])."&page=$page";
                echo "\nB[$url]...\n";
                if($json = Functions::lookup_with_cache($url, $this->download_options)) {
                    $o = json_decode($json); //print_r($o); exit;
                    $final = array();
                    foreach($o->recordings as $r) { //print_r($o); exit;
                        $rek = array();
                        $rek['identifier']              = $r->id;
                        $rek['taxonID']                 = $rec['taxonID'];
                        if($val = $r->rec) {
                            $rek['agentID'] = self::format_agent_id($val, $r);
                            $rek['Owner'] = $val;
                        }
                        $rek['accessURI']               = self::format_accessURI($r, $rek['agentID']);
                        $rek['format']                  = Functions::get_mimetype($r->{'file-name'});
                        $rek['type']                    = Functions::get_datatype_given_mimetype($rek['format']);
                        if(!$rek['type']) exit("\nInvestigate: DataType must be present [".$rek['format']."]\n");
                        $rek['furtherInformationURL']   = self::format_furtherInfoURL($r, $rec['url']); //$rec['url'];
                        $rek['LocationCreated']         = $r->loc;
                        $rek['lat']                     = $r->lat;
                        $rek['long']                    = $r->lng;
                        $rek['description']             = $r->rmk;
                        $rek['CreateDate']              = $r->date;

                        if($rek['UsageTerms'] = self::parse_usageTerms($r->lic)) {}
                        else continue; //invalid license

                        $rek['bibliographicCitation'] = self::parse_citation($rec, $rek['Owner'], $r->{'file-name'}, $rek['furtherInformationURL']);
                        $final[] = $rek;
                        // print_r($final); exit;
                    }
                    // print_r($final); exit("\nits final\n");
                }
            }
        }
        // print_r($final); exit("\nfinal\n");
        return array('orig_rec' => $rec, 'media' => $final);
    }
    private function format_furtherInfoURL($r, $last_option)
    {   /*
        [file-name] => XC563003-Common Ostrich chicks.mp3
        https://xeno-canto.org/673753
        */
        $arr = explode("-", $r->{'file-name'});
        if(count($arr) >= 2) {
            if($tmp = $arr[0]) {
                if($basename = trim(substr($tmp, 2, strlen(trim($tmp))))) return $this->domain."/".$basename;
            }    
        }
        return $last_option;
    }
    private function format_accessURI($r, $agentID)
    {
        /* sound_file_url + agentID + file-name 
        https://xeno-canto.org/sounds/uploaded/ +   SGLTZLDXYI  +   /   +   XC563003-Common Ostrich chicks.mp3
        */
        if($agentID && $r->{"file-name"}) return $this->sound_file_url . $agentID . "/" . $r->{'file-name'};
        else {
            print_r($r);
            exit("\nInvestigate accessURI: [$agentID]\n");
            return false;
        }
    }
    private function format_agent_id($recorder_name, $r)
    {
        if($recorder_id = @$this->recorders_info[$recorder_name]) {
            $rec = array();
            $rec['identifier'] = $recorder_id;
            $rec['fullName'] = $recorder_name;
            $rec['role'] = "recorder";
            $rec['homepage'] = $this->recorder_url.$recorder_id;
            if($agent_ids = self::create_agents(array($rec))) return implode("; ", $agent_ids);
        }
        print_r($r);
        exit("\nInvestigate: Recorder name not initialized: [$recorder_name]\n");
        $this->debug["Recorder name not initialized"][$recorder_name] = '';

        /* For those recorders not found in members list */
        $rec = array();
        $rec['identifier'] = str_replace(" ", "_", strtolower($recorder_name));
        $rec['fullName'] = $recorder_name;
        $rec['role'] = "recorder";
        $rec['homepage'] = '';
        if($agent_ids = self::create_agents(array($rec))) return implode("; ", $agent_ids);
    }
    private function create_agents($agents)
    {
        $agent_ids = array();
        foreach($agents as $rec){
            if($agent = trim($rec["fullName"])){
                $r = new \eol_schema\Agent();
                $r->term_name       = $agent;
                // $r->identifier      = md5("$agent|" . $rec["role"]. $rec['homepage']);
                $r->identifier      = $rec["identifier"];
                $r->agentRole       = $rec["role"];
                $r->term_homepage   = $rec["homepage"];
                $agent_ids[] = $r->identifier;
                if(!isset($this->resource_agent_ids[$r->identifier])) {
                   $this->resource_agent_ids[$r->identifier] = '';
                   $this->archive_builder->write_object_to_file($r);
                }
            }
        }
        return $agent_ids;
    }

    private function write_media($records)
    {
        foreach($records as $rec) {
            // print_r($rec); //exit("\nstop muna: obj\n");
            /* Array(
                [identifier] => 208209
                [taxonID] => struthio-camelus
                [agentID] => XKXDFWNSPA
                [Owner] => Jeremy Hegge
                [accessURI] => https://xeno-canto.org/sounds/uploaded/XKXDFWNSPA/XC208209-Common Ostrich 2.mp3
                [format] => audio/mpeg
                [type] => http://purl.org/dc/dcmitype/Sound
                [furtherInformationURL] => https://www.xeno-canto.org/208209
                [LocationCreated] => Mmabolela Reserve, Limpopo
                [lat] => -22.677
                [long] => 28.2629
                [description] => Recording modified: Frequencies above 640hz reduced by 12db and frequencies below 320 amplified as well. The overall recording has been amplified significantly by around 30db as well.
            Part of a 13 hour recording session where the microphones were left by a waterhole overnight.
                [CreateDate] => 2014-11-20
                [UsageTerms] => https://creativecommons.org/licenses/by-nc-sa/4.0/
                [bibliographicCitation] => Jeremy Hegge, XC208209. Accessible at www.xeno-canto.org/208209.
            )*/
                        
            $mr = new \eol_schema\MediaResource();
            foreach(array_keys($rec) as $fld) {
                $mr->$fld = $rec[$fld];
            }

            /* copied template
            $mr->thumbnailURL   = ''
            $mr->CVterm         = ''
            $mr->rights         = ''
            $mr->title          = ''
            if($reference_ids = @$this->object_reference_ids[$o['int_do_id']])  $mr->referenceID = implode("; ", $reference_ids);
            */
            
            if(!isset($this->object_ids[$mr->identifier])) {
                $this->archive_builder->write_object_to_file($mr);
                $this->object_ids[$mr->identifier] = '';
            }
        }
    }
    private function buildup_recorders_list()
    {
        $final = array();
        if($html = Functions::lookup_with_cache($this->recorders_list, $this->download_options)) {
            /* <a href='https://xeno-canto.org/contributor/NQMGMOJOHV'>A Edwar H Guarín</a> */
            $left = 'https://xeno-canto.org/contributor/';
            if(preg_match_all("/".preg_quote($left, '/')."(.*?)<\/a>/ims", $html, $arr)) { // print_r($arr[1]); exit;
                /*  [2453] => ETASOPNTYI'>wisconaowl
                    [2454] => ELJNEGDKGC'>Wolbert Hermus
                    [2455] => BYQQJILIAR'>Wolfgang Henkes */
                foreach($arr[1] as $str) {
                    $rek = array();
                    if(preg_match("/elicha(.*?)\'/ims", "elicha".$str, $arr2)) $rek['id'] = trim($arr2[1]);
                    if(preg_match("/\>(.*?)elicha/ims", $str."elicha", $arr2)) $rek['name'] = trim($arr2[1]);
                    if(@$rek['id'] && @$rek['name']) $final[$rek['name']] = $rek['id'];
                }
            }
        }
        return $final;
    }
    private function parse_citation($rec, $owner, $file_name, $furtherInformationURL)
    {
        // print_r($rec); //exit;
        // citation e.g.: Ralf Wendt, XC356323. Accessible at www.xeno-canto.org/356323.
        //e.g. XC207312-Apteryx%20australis141122_T1460
        $arr = explode('-', $file_name);
        return "$owner, $arr[0]. Accessible at " . str_replace('https://', '', $furtherInformationURL).".";
    }
    private function parse_usageTerms($str)
    {
        if(!$str) return false;
        if(stripos($str, "by-nc-nd") !== false) return false; //invalid license
        return "http:".$str;
    }
    private function write_taxon($rec)
    {   // print_r($rec); exit;
        /*Array(
            [comname] => Common Ostrich
            [url] => https://xeno-canto.org/species/Struthio-camelus
            [sciname] => Struthio camelus
            [order] => Struthioniformes
            [family] => Struthionidae
            [taxonID] => struthio-camelus
        )*/
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID         = $rec['taxonID'];
        $taxon->scientificName  = $rec['sciname'];
        $taxon->taxonRank       = 'species';
        $taxon->order           = $rec['order'];
        $taxon->family          = $rec['family'];
        $taxon->furtherInformationURL = $rec['url'];
        if(!isset($this->taxon_ids[$taxon->taxonID])) {
            $this->archive_builder->write_object_to_file($taxon);
            $this->taxon_ids[$taxon->taxonID] = '';
        }
        
        if($rec['comname']) {
            $v = new \eol_schema\VernacularName();
            $v->taxonID         = $rec['taxonID'];
            $v->vernacularName  = $rec['comname'];
            $v->language        = 'en';
            $unique = md5($v->taxonID.$v->vernacularName);
            if(!isset($this->common_names[$unique])) {
                $this->archive_builder->write_object_to_file($v);
                $this->common_names[$unique] = '';
            }    
        }
    }
    /* as of Sep 14, 2023
    The following is a detailed description of the fields of this object:

    id: the catalogue number of the recording on xeno-canto
    gen: the generic name of the species
    sp: the specific name (epithet) of the species
    ssp: the subspecies name (subspecific epithet)
    group: the group to which the species belongs (birds, grasshoppers, bats)
    en: the English name of the species
    rec: the name of the recordist
    cnt: the country where the recording was made
    loc: the name of the locality
    lat: the latitude of the recording in decimal coordinates
    lng: the longitude of the recording in decimal coordinates
    type: the sound type of the recording (combining both predefined terms such as 'call' or 'song' and additional free text options)
    sex: the sex of the animal
    stage: the life stage of the animal (adult, juvenile, etc.)
    method: the recording method (field recording, in the hand, etc.)
    url: the URL specifying the details of this recording
    file: the URL to the audio file
    file-name: the original file name of the audio file
    sono: an object with the urls to the four versions of sonograms
    osci: an object with the urls to the three versions of oscillograms
    lic: the URL describing the license of this recording
    q: the current quality rating for the recording
    length: the length of the recording in minutes
    time: the time of day that the recording was made
    date: the date that the recording was made
    uploaded: the date that the recording was uploaded to xeno-canto
    also: an array with the identified background species in the recording
    rmk: additional remarks by the recordist
    bird-seen: despite the field name (which was kept to ensure backwards compatibility), this field indicates whether the recorded animal was seen
    animal-seen: was the recorded animal seen?
    playback-used: was playback used to lure the animal?
    temperature: temperature during recording (applicable to specific groups only)
    regnr: registration number of specimen (when collected)
    auto: automatic (non-supervised) recording?
    dvc: recording device used
    mic: microphone used
    smp: sample rate    
    */
}
?>