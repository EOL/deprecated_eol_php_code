<?php
namespace php_active_record;
/* connector: [called from DwCA_Utility.php, which is called from brazilian_flora.php for DATA-xxx|email with Jen] */
class BrazilianFloraAPI
{
    function __construct($archive_builder, $resource_id)
    {
        $this->resource_id = $resource_id;
        $this->archive_builder = $archive_builder;
        $this->download_options = array('cache' => 1, 'resource_id' => $resource_id, 'expire_seconds' => 60*60*24*25, 'download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1);
        // $this->download_options['expire_seconds'] = false; //comment after first harvest
        $this->species_page = 'http://reflora.jbrj.gov.br/reflora/floradobrasil/FB';
        $this->citation_4MoF = 'Brazil Flora G (2019). Brazilian Flora 2020 project - Projeto Flora do Brasil 2020. Version 393.206. Instituto de Pesquisas Jardim Botanico do Rio de Janeiro. Checklist dataset https://doi.org/10.15468/1mtkaw accessed via GBIF.org on '.date('Y-m-d');
        $this->debug = array();
    }
    /*================================================================= STARTS HERE ======================================================================*/
    /* 
    The taxon file has a couple of columns we haven't heard of, which I'm comfortable leaving out.
    -> ELI: - generate identifier in references and link these identifier accordingly to taxon with references
    -> ELI: - set furtherInformationURL in taxon (from reference value)
    
    The occurrences file can be constructed as a 1->1 with no additional information.

    The distribution and speciesprofile files can both go to the measurementsOrFacts file. Distribution will need a slightly convoluted mapping:

    speciesprofile also has a convoluted batch of strings in lifeForm. (habitat seems to be empty for now). There may be up to three sections in each cell, of the form:
    {"measurementType":["measurementValue","measurementValue"],"measurementType":["measurementValue","measurementValue"],"measurementType":["measurementValue","measurementValue"]}

    if that makes it clear...

    measurementTypes:
    lifeForm-> http://purl.obolibrary.org/obo/FLOPO_0900022
    habitat-> http://rs.tdwg.org/dwc/terms/habitat
    vegetationType-> http://eol.org/schema/terms/Habitat

    I'll make you a mapping for all the measurementValue strings from both files.
    
    */
    function start($info)
    {   $tables = $info['harvester']->tables;
        // /* Normal operation
        self::process_Vernacularname($tables['http://rs.gbif.org/terms/1.0/vernacularname'][0]);
        self::process_Reference($tables['http://rs.gbif.org/terms/1.0/reference'][0]);
        // print_r($this->taxonID_ref_info); exit;
        self::process_Taxon($tables['http://rs.tdwg.org/dwc/terms/taxon'][0]);
        // */
        
        require_library('connectors/TraitGeneric');
        $this->func = new TraitGeneric($this->resource_id, $this->archive_builder);
        $this->func->initialize_terms_remapping(); //for DATA-1841 terms remapping
        
        self::process_Distribution($tables['http://rs.gbif.org/terms/1.0/distribution'][0]);
        self::process_SpeciesProfile($tables['http://rs.gbif.org/terms/1.0/speciesprofile'][0]);
        if($this->debug) print_r($this->debug);
        
        /* copied template
        self::process_measurementorfact($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0]);
        self::process_occurrence($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0]);
        unset($this->occurrenceID_bodyPart);
        self::initialize_mapping(); //for location string mappings
        */
    }
    private function process_Vernacularname($meta)
    {   //print_r($meta);
        echo "\nprocess_Vernacularname...\n"; $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            $row = Functions::conv_to_utf8($row); //possibly to fix special chars. but from copied template
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            }
            // print_r($rec); //exit;
            /*Array(
                [http://rs.tdwg.org/dwc/terms/taxonID] => 110445
                [http://rs.tdwg.org/dwc/terms/vernacularName] => Embireira-do-campo
                [http://purl.org/dc/terms/language] => PORTUGUES
                [http://rs.tdwg.org/dwc/terms/locality] => 
            )*/
            //===========================================================================================================================================================
            $rec['http://purl.org/dc/terms/language'] = self::get_ISOcode_language($rec['http://purl.org/dc/terms/language']);
            //===========================================================================================================================================================
            $uris = array_keys($rec);
            // print_r($uris);
            $uris = array_diff($uris, array('http://rs.tdwg.org/dwc/terms/locality'));
            // print_r($uris); exit;
            $o = new \eol_schema\VernacularName();
            foreach($uris as $uri) {
                $field = pathinfo($uri, PATHINFO_BASENAME);
                $o->$field = $rec[$uri];
            }
            $this->archive_builder->write_object_to_file($o);
        }
    }
    private function get_ISOcode_language($lang)
    {   switch ($lang) {
            case "PORTUGUES": return "pt";
            case "INGLES": return "en";
            case "ESPANHOL": return "es";
            case "KAXINAWA": return "cbs";
            case "HOLANDES": return "nl";
            case "FRANCES": return "fr";
            default:
                if(!$lang) {}
                else exit("\n lang [$lang] no mapping yet.\n");
        }
        return $lang;
    }
    private function process_SpeciesProfile($meta)
    {   //print_r($meta);
        echo "\nprocess_SpeciesProfile...\n"; $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            $row = Functions::conv_to_utf8($row); //possibly to fix special chars. but from copied template
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            }
            // print_r($rec); //exit;
            /*Array(
                [http://rs.tdwg.org/dwc/terms/taxonID] => 606463
                [http://rs.gbif.org/terms/1.0/lifeForm] => {"lifeForm":["Árvore"],"habitat":["Terrícola"],"vegetationType":["Floresta Ombrófila (= Floresta Pluvial)"]}
                [http://rs.tdwg.org/dwc/terms/habitat] => 
            )*/
            if($json = $rec['http://rs.gbif.org/terms/1.0/lifeForm']) {
                if($arr = json_decode($json, true)) {
                    // print_r($arr);
                    /*Array(
                        [lifeForm] => Array(
                                [0] => Erva
                            )
                        [habitat] => Array(
                                [0] => Terrícola
                            )
                        [vegetationType] => Array(
                                [0] => Área Antrópica
                                [1] => Floresta Ciliar ou Galeria
                                [2] => Floresta Ombrófila (= Floresta Pluvial)
                            )
                    )*/
                    foreach($arr as $key => $terms) {
                        foreach($terms as $term) {
                            if($ret = self::get_mValue_mType_4lifeForm($term, $key)) {
                                $mValue = $ret[1];
                                $mType = $ret[0];
                                $taxon_id = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
                                $save = array();
                                $save['taxon_id'] = $taxon_id;
                                $save["catnum"] = $taxon_id.'_'.$mType.$mValue; //making it unique. no standard way of doing it.
                                $save['source'] = $this->species_page.$taxon_id;
                                $save['measurementRemarks'] = "$key:$term";//json_encode(array($key => array($term)));
                                $save['bibliographicCitation'] = $this->citation_4MoF;
                                if($mValue && $mType) $this->func->add_string_types($save, $mValue, $mType, "true");
                            }
                        }
                    }
                    
                }
            }
        }
    }
    private function process_Distribution($meta)
    {   //print_r($meta);
        echo "\nprocess_Distribution...\n"; $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            // $row = Functions::conv_to_utf8($row); //possibly to fix special chars. but from copied template
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            }
            // print_r($rec); exit;
            /*Array( - Distribution
                [http://rs.tdwg.org/dwc/terms/taxonID] => 121159
                [http://rs.tdwg.org/dwc/terms/locationID] => BR-RS
                [http://rs.tdwg.org/dwc/terms/countryCode] => BR
                [http://rs.tdwg.org/dwc/terms/establishmentMeans] => NATIVA
                [http://rs.tdwg.org/dwc/terms/occurrenceRemarks] => {"endemism":"Não endemica"}
            )
            locationID will be used for measurementValue
            countryCode can be ignored
            
            measurementType is determined by establishmentMeans:
                NATIVA-> http://eol.org/schema/terms/NativeRange
                CULTIVADA-> http://eol.org/schema/terms/IntroducedRange
                NATURALIZADA-> http://eol.org/schema/terms/IntroducedRange
                unless the string "endemism":"Endemica" appears in occurrenceRemarks, in which case the measurementType is http://eol.org/terms/endemic

            The strings CULTIVADA and NATURALIZADA should be preserved in measurementRemarks

            occurrenceRemarks also contains another section, beginning "phytogeographicDomain": and followed by comma separated strings in square brackets. 
            Each string will also be a measurementValue and should get an additional record with the same measurementType, occurrence, etc.
            
            {"endemism":"Não endemica","phytogeographicDomain":["Amazônia","Caatinga","Cerrado","Mata Atlântica","Pantanal"]}
            {"endemism":"Endemica","phytogeographicDomain":["Amazônia","Caatinga","Cerrado"]}

            wrinkle: where measurementType is http://eol.org/terms/endemic for the original records, http://eol.org/schema/terms/NativeRange should be used for any accompanying records 
                based on the occurrenceRemarks strings.
            */
            //===========================================================================================================================================================
            unset($rec['http://rs.tdwg.org/dwc/terms/countryCode']);
            $mValue = self::get_mValue_4distribution($rec['http://rs.tdwg.org/dwc/terms/locationID']);
            $mType = self::get_mType_4distribution($rec['http://rs.tdwg.org/dwc/terms/establishmentMeans'], $rec['http://rs.tdwg.org/dwc/terms/occurrenceRemarks']);
            $taxon_id = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
            $save = array();
            $save['taxon_id'] = $taxon_id;
            $save["catnum"] = $taxon_id.'_'.$mType.$mValue; //making it unique. no standard way of doing it.
            $save['source'] = $this->species_page.$taxon_id;
            $save['measurementRemarks'] = $rec['http://rs.tdwg.org/dwc/terms/establishmentMeans']." (".$rec['http://rs.tdwg.org/dwc/terms/locationID'].")";
            $save['bibliographicCitation'] = $this->citation_4MoF;
            if($mValue && $mType) $this->func->add_string_types($save, $mValue, $mType, "true");
            //===========================================================================================================================================================
            if($domains = self::occurrenceRemarks_has_phytogeographicDomain($rec['http://rs.tdwg.org/dwc/terms/occurrenceRemarks'])) {
                foreach($domains as $domain) {
                    $mValue = self::get_mValue_4phytogeographicDomain($domain); //$domain e.g. Amazônia
                    if($mType == 'http://eol.org/terms/endemic') $mType2 = 'http://eol.org/schema/terms/NativeRange';
                    else                                         $mType2 = $mType;
                    $save = array();
                    $save['taxon_id'] = $taxon_id;
                    $save["catnum"] = $taxon_id.'_'.$mType2.$mValue; //making it unique. no standard way of doing it.
                    $save['source'] = $this->species_page.$taxon_id;
                    $save['measurementRemarks'] = "phytogeographicDomain:$domain"; //"phytogeographicDomain:".implode(", ", $domains);
                    $save['bibliographicCitation'] = $this->citation_4MoF;
                    if($mValue && $mType2) $this->func->add_string_types($save, $mValue, $mType2, "true");
                }
            }
            //===========================================================================================================================================================
        }
    }
    private function occurrenceRemarks_has_phytogeographicDomain($occurrenceRemarks)
    {
        $arr = json_decode($occurrenceRemarks, true);
        if($val = @$arr['phytogeographicDomain']) {
            // print_r($val); exit("\nwith phytogeographicDomain\n");
            return $val;
        }
    }
    private function get_mType_4distribution($establishmentMeans, $occurrenceRemarks) //
    {   
        if(stripos($occurrenceRemarks, '"endemism":"Endemica"') !== false) { //string is found
            return "http://eol.org/terms/endemic";
        }
        switch ($establishmentMeans) {
            case "NATIVA": return "http://eol.org/schema/terms/NativeRange";
            case "CULTIVADA": return "http://eol.org/schema/terms/IntroducedRange";
            case "NATURALIZADA": return "http://eol.org/schema/terms/IntroducedRange";
            default:
                if(!$establishmentMeans) {}
                else exit("\n establishmentMeans [$establishmentMeans] no mapping yet.\n");
        }
        /*
        NATIVA-> http://eol.org/schema/terms/NativeRange
        CULTIVADA-> http://eol.org/schema/terms/IntroducedRange
        NATURALIZADA-> http://eol.org/schema/terms/IntroducedRange
        unless the string "endemism":"Endemica" appears in occurrenceRemarks, in which case the measurementType is http://eol.org/terms/endemic
        */
        return $establishmentMeans;
    }
    private function get_mValue_4phytogeographicDomain($domain)
    {   switch ($domain) {
            case "Amazônia": return "https://www.wikidata.org/entity/Q2841453";
            case "Caatinga": return "https://www.wikidata.org/entity/Q375816";
            case "Cerrado": return "https://www.wikidata.org/entity/Q278512";
            case "Mata Atlântica": return "https://www.wikidata.org/entity/Q477047";
            case "Pampa": return "https://www.wikidata.org/entity/Q184382";
            case "Pantanal": return "https://www.wikidata.org/entity/Q157603";
            default:
                if(!$domain) {}
                else {
                    $this->debug['phytogeographicDomain no mapping yet'][$domain] = '';
                }
        }
        return $domain;
    }
    private function get_mValue_4distribution($locationID) //$locationID is distribution label
    {   switch ($locationID) {
            case "BR-BA": return "https://www.geonames.org/3471168";
            case "BR-DF": return "https://www.geonames.org/3463504";
            case "BR-ES": return "https://www.geonames.org/3463930";
            case "BR-MG": return "https://www.geonames.org/3457153";
            case "BR-MS": return "https://www.geonames.org/3457415";
            case "BR-MT": return "https://www.geonames.org/3457419";
            case "BR-PA": return "https://www.geonames.org/3393129";
            case "BR-PR": return "https://www.geonames.org/3455077";
            case "BR-RJ": return "https://www.geonames.org/3451189";
            case "BR-RS": return "https://www.geonames.org/3451133";
            case "BR-SC": return "https://www.geonames.org/3450387";
            case "BR-SP": return "https://www.geonames.org/3448433";
            case "Amazônia": return "https://www.wikidata.org/entity/Q2841453";
            case "Caatinga": return "https://www.wikidata.org/entity/Q375816";
            case "Cerrado": return "https://www.wikidata.org/entity/Q278512";
            case "Mata Atlântica": return "https://www.wikidata.org/entity/Q477047";
            case "Pampa": return "https://www.wikidata.org/entity/Q184382";
            case "Pantanal": return "https://www.wikidata.org/entity/Q157603";
            /* added */
            case "BR-AM": return "https://www.wikidata.org/entity/Q2841453";
            case "BR-RO": return "http://www.geonames.org/3924825";
            case "BR-GO": return "http://www.geonames.org/3462372";
            case "BR-TO": return "http://www.geonames.org/3474575";
            case "BR-RN": return "http://www.geonames.org/3390290";
            case "BR-PE": return "http://www.geonames.org/3392268";
            case "BR-AL": return "http://www.geonames.org/3408096";
            case "BR-AC": return "http://www.geonames.org/3665474";
            case "BR-PI": return "http://www.geonames.org/3392213";
            case "BR-PB": return "http://www.geonames.org/3393098";
            case "BR-CE": return "http://www.geonames.org/3402362";
            case "BR-AP": return "http://www.geonames.org/3407762";
            case "BR-MA": return "http://www.geonames.org/3395443";
            case "BR-RR": return "http://www.geonames.org/3662560";
            case "BR-SE": return "http://www.geonames.org/3447799";
            default:
                if(!$locationID) {}
                else {
                    $this->debug['locationID no mapping yet'][$locationID] = '';
                }
        }
        return $locationID;
    }
    /*
    Array(
        [http://rs.tdwg.org/dwc/terms/taxonID] => 606463
        [http://rs.gbif.org/terms/1.0/lifeForm] => {"lifeForm":["Árvore"],"habitat":["Terrícola"],"vegetationType":["Floresta Ombrófila (= Floresta Pluvial)"]}
        [http://rs.tdwg.org/dwc/terms/habitat] => 
    )*/
    private function get_mValue_mType_4lifeForm($term, $key)
    {   switch ($term) {
            case "Aquática-Bentos": return array('http://eol.org/schema/terms/Habitat' ,'https://eol.org/schema/terms/freshwater_benthic');
            case "Aquática-Neuston": return array('http://eol.org/schema/terms/EcomorphologicalGuild' ,'https://eol.org/schema/terms/neuston');
            case "Aquática-Plâncton": return array('http://eol.org/schema/terms/EcomorphologicalGuild' ,'http://www.marinespecies.org/traits/Plankton');
            case "Arbusto": return array('http://purl.obolibrary.org/obo/FLOPO_0900032', 'http://purl.obolibrary.org/obo/FLOPO_0900034');
            case "Árvore": return array('http://purl.obolibrary.org/obo/FLOPO_0900032', 'http://purl.obolibrary.org/obo/FLOPO_0900033');
            case "Bambu": return array('http://purl.obolibrary.org/obo/FLOPO_0900032', 'https://www.wikidata.org/entity/Q20660322');
            case "Coxim": return array('http://purl.obolibrary.org/obo/FLOPO_0900032', 'https://www.wikidata.org/entity/Q1508694');
            case "Dendróide": return array('http://purl.obolibrary.org/obo/FLOPO_0900032', 'http://purl.obolibrary.org/obo/PATO_0000402');
            case "Dracenóide": return array('http://purl.obolibrary.org/obo/FLOPO_0900032', 'https://eol.org/schema/terms/dracaenoid');
            case "Epifita": return array('http://purl.obolibrary.org/obo/FLOPO_0900022', 'http://purl.obolibrary.org/obo/FLOPO_0900030');
            case "Erva": return array('http://purl.obolibrary.org/obo/FLOPO_0900032', 'http://purl.obolibrary.org/obo/FLOPO_0022142');
            case "Folhosa": return array('http://purl.obolibrary.org/obo/FLOPO_0900032', 'https://www.wikidata.org/entity/Q757163');
            case "Hemiepífita": return array('http://purl.obolibrary.org/obo/FLOPO_0900022', 'http://eol.org/schema/terms/hemiepiphyte');
            case "Liana/volúvel/trepadeira": return array('http://purl.obolibrary.org/obo/FLOPO_0900032' ,'http://purl.obolibrary.org/obo/FLOPO_0900035');
            case "Palmeira": return array('http://purl.obolibrary.org/obo/FLOPO_0900032', 'https://www.wikidata.org/entity/Q29582371');
            case "Parasita": return array('http://eol.org/schema/terms/TrophicGuild' ,'https://www.wikidata.org/entity/Q12806437');
            case "Pendente": return array('http://purl.obolibrary.org/obo/FLOPO_0900032', 'https://eol.org/schema/terms/weeping');
            case "Rupícola": return array('http://purl.obolibrary.org/obo/FLOPO_0900022', 'http://www.wikidata.org/entity/Q1321691');
            case "Saprobio": return array('http://eol.org/schema/terms/TrophicGuild' ,'https://www.wikidata.org/entity/Q114750');
            case "Simbionte": return array('http://eol.org/schema/terms/TrophicGuild' ,'https://www.wikidata.org/entity/Q2374421');
            case "Subarbusto": return array('http://purl.obolibrary.org/obo/FLOPO_0900032', 'http://eol.org/schema/terms/subshrub');
            case "Suculenta": return array('http://purl.obolibrary.org/obo/FLOPO_0900032', 'http://www.wikidata.org/entity/Q189939');
            case "Tapete": return array('http://purl.obolibrary.org/obo/FLOPO_0900032', 'http://eol.org/schema/terms/trailing');
            case "Terrícola": return array('http://eol.org/schema/terms/Habitat' ,'http://purl.obolibrary.org/obo/ENVO_00000446');
            case "Trama": return array('http://purl.obolibrary.org/obo/FLOPO_0900032', 'https://www.wikidata.org/entity/Q16868813');
            case "Tufo": return array('http://purl.obolibrary.org/obo/FLOPO_0900032', 'https://eol.org/schema/terms/tuft');
            case "Área Antrópica": return array('http://eol.org/schema/terms/Habitat' ,'http://purl.obolibrary.org/obo/ENVO_00002031');
            case "Caatinga (stricto sensu)": return array('https://www.wikidata.org/entity/Q295469' ,'https://www.wikidata.org/entity/Q375816');
            case "Campinarana": return array('https://www.wikidata.org/entity/Q295469' ,'https://www.wikidata.org/entity/Q25067050');
            case "Campo de Altitude": return array('http://eol.org/schema/terms/Habitat' ,'http://purl.obolibrary.org/obo/ENVO_01000194');
            case "Campo de Várzea": return array('http://eol.org/schema/terms/Habitat' ,'http://purl.obolibrary.org/obo/ENVO_01000195');
            case "Campo Limpo": return array('http://eol.org/schema/terms/Habitat' ,'http://purl.obolibrary.org/obo/ENVO_01000177');
            case "Campo rupestre": return array('http://eol.org/schema/terms/Habitat' ,'http://eol.org/schema/terms/rockyGrassland');
            case "Carrasco": return array('http://eol.org/schema/terms/Habitat' ,'http://purl.obolibrary.org/obo/ENVO_01000218');
            case "Cerrado (lato sensu)": return array('https://www.wikidata.org/entity/Q295469' ,'https://www.wikidata.org/entity/Q278512');
            case "Floresta Ciliar ou Galeria": return array('http://eol.org/schema/terms/Habitat' ,'https://www.wikidata.org/entity/Q911190');
            case "Floresta de Igapó": return array('http://eol.org/schema/terms/Habitat' ,'https://www.wikidata.org/entity/Q1476287');
            case "Floresta de Terra Firme": return array('http://eol.org/schema/terms/Habitat' ,'https://www.wikidata.org/entity/Q3518534');
            case "Floresta de Várzea": return array('http://eol.org/schema/terms/Habitat' ,'https://www.wikidata.org/entity/Q1940784');
            case "Floresta Estacional Decidual": return array('http://eol.org/schema/terms/Habitat' ,'http://purl.obolibrary.org/obo/ENVO_01000816');
            case "Floresta Estacional Perenifólia": return array('http://eol.org/schema/terms/Habitat' ,'http://purl.obolibrary.org/obo/ENVO_01000843');
            case "Floresta Estacional Semidecidual": return array('http://eol.org/schema/terms/Habitat' ,'http://purl.obolibrary.org/obo/ENVO_01000388');
            case "Floresta Ombrófila (= Floresta Pluvial)": return array('http://eol.org/schema/terms/Habitat' ,'http://purl.obolibrary.org/obo/ENVO_01000228');
            case "Floresta Ombrófila Mista": return array('http://eol.org/schema/terms/Habitat' ,'http://purl.obolibrary.org/obo/ENVO_00002993');
            case "Manguezal": return array('http://eol.org/schema/terms/Habitat' ,'http://purl.obolibrary.org/obo/ENVO_01000181');
            case "Palmeiral": return array('http://eol.org/schema/terms/Habitat' ,'https://eol.org/schema/terms/palmeiral');
            case "Restinga": return array('https://www.wikidata.org/entity/Q295469' ,'https://www.wikidata.org/entity/Q3305106');
            case "Savana Amazônica": return array('http://eol.org/schema/terms/Habitat' ,'http://purl.obolibrary.org/obo/ENVO_01000178');
            case "Vegetação Aquática": return array('http://eol.org/schema/terms/Habitat' ,'http://eol.org/schema/terms/aquaticVegetation');
            case "Vegetação Sobre Afloramentos Rochosos": return array('http://eol.org/schema/terms/Habitat' ,'https://eol.org/schema/terms/rockyVegetated');
            /* added */
            case "Epífita": return array('http://purl.obolibrary.org/obo/FLOPO_0900022', 'http://purl.obolibrary.org/obo/FLOPO_0900030');
            case "Aquática": return array('http://eol.org/schema/terms/Habitat', 'http://purl.obolibrary.org/obo/ENVO_00002030');
            case "Hemiparasita": return array('http://eol.org/schema/terms/TrophicGuild', 'https://www.wikidata.org/entity/Q20739318');
            case "Saprófita": return array('http://eol.org/schema/terms/TrophicGuild', 'https://www.wikidata.org/entity/Q114750');
            case "Simbionte (incluindo fungos liquenizados)": return array('http://eol.org/schema/terms/TrophicGuild', 'https://www.wikidata.org/entity/Q2374421');
            case "Desconhecida": return false; //'DISCARD';
            case "Desconhecido": return false; //'DISCARD';
            case "Tronco em decomposição": return false; //'DISCARD';
            case "Solo": return false; //'DISCARD';
            case "Planta viva - córtex do caule": return false; //'DISCARD';
            case "Folhedo": return false; //'DISCARD';
            case "Planta viva - folha": return false; //'DISCARD';
            case "Planta viva - fruto": return false; //'DISCARD';
            case "Planta viva - inflorescência": return false; //'DISCARD';
            case "Folhedo aéreo": return false; //'DISCARD';
            case "Areia": return false; //'DISCARD';
            case "Água": return false; //'DISCARD';
            case "Planta viva - raiz": return false; //'DISCARD';
            case "Corticícola": return false; //'DISCARD';
            case "Epixila": return false; //'DISCARD';
            case "Saxícola": return false; //'DISCARD';
            case "Epífila": return false; //'DISCARD';
            case "Sub-aérea": return false; //'DISCARD';
            case "Edáfica": return false; //'DISCARD';
            case "Rocha": return false; //'DISCARD';
            case "Flabelado": return false; //'DISCARD';
            case "Talosa": return false; //'DISCARD';
            default:
                if(!$term) {}
                else {
                    $this->debug['term no mapping yet'][$key][$term] = '';
                }
        }
        return false;
    }
    private function process_Taxon($meta)
    {   //print_r($meta);
        echo "\nprocess_Taxon...\n"; $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            // $row = Functions::conv_to_utf8($row); //possibly to fix special chars. but from copied template
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            }
            // print_r($rec); exit;
            /*Array(
                [http://rs.tdwg.org/dwc/terms/taxonID] => 12
                [http://rs.tdwg.org/dwc/terms/acceptedNameUsageID] => 
                [http://rs.tdwg.org/dwc/terms/parentNameUsageID] => 120181
                [http://rs.tdwg.org/dwc/terms/originalNameUsageID] => 
                [http://rs.tdwg.org/dwc/terms/scientificName] => Agaricales
                [http://rs.tdwg.org/dwc/terms/acceptedNameUsage] => 
                [http://rs.tdwg.org/dwc/terms/parentNameUsage] => Basidiomycota
                [http://rs.tdwg.org/dwc/terms/namePublishedIn] => 
                [http://rs.tdwg.org/dwc/terms/namePublishedInYear] => 
                [http://rs.tdwg.org/dwc/terms/higherClassification] => Flora;Fungos;stricto sensu;Basidiomycota;Agaricales
                [http://rs.tdwg.org/dwc/terms/kingdom] => Fungi
                [http://rs.tdwg.org/dwc/terms/phylum] => Basidiomycota
                [http://rs.tdwg.org/dwc/terms/class] => 
                [http://rs.tdwg.org/dwc/terms/order] => Agaricales
                [http://rs.tdwg.org/dwc/terms/family] => 
                [http://rs.tdwg.org/dwc/terms/genus] => 
                [http://rs.tdwg.org/dwc/terms/specificEpithet] => 
                [http://rs.tdwg.org/dwc/terms/infraspecificEpithet] => 
                [http://rs.tdwg.org/dwc/terms/taxonRank] => ORDEM
                [http://rs.tdwg.org/dwc/terms/scientificNameAuthorship] => 
                [http://rs.tdwg.org/dwc/terms/taxonomicStatus] => NOME_ACEITO
                [http://rs.tdwg.org/dwc/terms/nomenclaturalStatus] => NOME_CORRETO
                [http://purl.org/dc/terms/modified] => 2018-08-10 11:58:06.954
                [http://purl.org/dc/terms/bibliographicCitation] => Flora do Brasil 2020 em construção. Jardim Botânico do Rio de Janeiro. Disponível em: http://floradobrasil.jbrj.gov.br.
                [http://purl.org/dc/terms/references] => http://reflora.jbrj.gov.br/reflora/listaBrasil/FichaPublicaTaxonUC/FichaPublicaTaxonUC.do?id=FB12
            )*/
            //===========================================================================================================================================================
            $rec['http://rs.tdwg.org/ac/terms/furtherInformationURL'] = $rec['http://purl.org/dc/terms/references'];
            $rec['http://purl.org/dc/terms/references'] = '';
            if($arr_ref_ids = @$this->taxonID_ref_info[$rec['http://rs.tdwg.org/dwc/terms/taxonID']]) {
                $rec['http://purl.org/dc/terms/references'] = implode("; ", $arr_ref_ids);
                // echo "\nhit...\n";
            }
            //===========================================================================================================================================================
            $rec['http://rs.tdwg.org/dwc/terms/taxonRank'] = self::conv_2English($rec['http://rs.tdwg.org/dwc/terms/taxonRank']);
            $rec['http://rs.tdwg.org/dwc/terms/taxonomicStatus'] = self::conv_2English($rec['http://rs.tdwg.org/dwc/terms/taxonomicStatus']);
            //===========================================================================================================================================================
            /* The taxa file looks great! We should probably remove some of the original columns that might confuse the harvester. 
            Say… originalNameUsageID, acceptedNameUsage, parentNameUsage, namePublishedInYear, higherClassification, specificEpithet, infraspecificEpithet, and bibliographicCitation. 
            And could you move nomenclaturalStatus to taxonRemarks? The others seem mostly redundant but that one has detail we’d rather not lose.
            */
            unset($rec['originalNameUsageID']);
            unset($rec['acceptedNameUsage']);
            unset($rec['parentNameUsage']);
            unset($rec['namePublishedInYear']);
            unset($rec['higherClassification']);
            unset($rec['specificEpithet']);
            unset($rec['infraspecificEpithet']);
            unset($rec['bibliographicCitation']);
            $rec['http://rs.tdwg.org/dwc/terms/taxonRemarks'] = @$rec['nomenclaturalStatus'];
            unset($rec['nomenclaturalStatus']);
            $remove = array('originalNameUsageID', 'acceptedNameUsage', 'parentNameUsage', 'namePublishedInYear', 'higherClassification', 'specificEpithet', 'infraspecificEpithet', 
            'bibliographicCitation', 'nomenclaturalStatus');
            //===========================================================================================================================================================
            $uris = array_keys($rec);
            $o = new \eol_schema\Taxon();
            foreach($uris as $uri) {
                $field = pathinfo($uri, PATHINFO_BASENAME);
                if(in_array($field, $remove)) continue;
                $o->$field = $rec[$uri];
            }
            $this->archive_builder->write_object_to_file($o);
            // if($i >= 50) break; //debug only
        }
    }
    private function conv_2English($spanish)
    {
        switch ($spanish) {
            case "ORDEM": return "order";
            case "GENERO": return "genus";
            case "ESPECIE": return "species";
            case "VARIEDADE": return "variety";
            case "SUB_ESPECIE": return "subspecies";
            case "CLASSE": return "class";
            case "TRIBO": return "tribe";
            case "FAMILIA": return "family";
            case "SUB_FAMILIA": return "subfamily";
            case "DIVISAO": return "division";
            case "FORMA": return "form";
            case "NOME_ACEITO": return "accepted";
            case "SINONIMO": return "synonym";
            default: 
                if(!$spanish) {}
                else exit("\n[$spanish] no English translation yet.\n");
        }
        return $spanish;
    }
    private function process_Reference($meta)
    {   //print_r($meta);
        echo "\nprocess_Reference...\n"; $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            // $row = Functions::conv_to_utf8($row); //possibly to fix special chars. but from copied template
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            }
            // print_r($rec); exit;
            /*Array(
                [http://rs.tdwg.org/dwc/terms/taxonID] => 264
                [http://purl.org/dc/terms/identifier] => 
                [http://purl.org/dc/terms/bibliographicCitation] => Arch. Jard. Bot. Rio de Janeiro,3: 187,1922
                [http://purl.org/dc/terms/title] => Arch. Jard. Bot. Rio de Janeiro
                [http://purl.org/dc/terms/creator] => 
                [http://purl.org/dc/terms/date] => 1922
                [http://purl.org/dc/terms/type] => 
            )
            The references file is pretty close, although the references are a bit sparse. It looks like the title column is redundant with bibliographicCitation and can be ignored. 
                Could you please concatenate creator, then date, then bibliographicCitation, separated by ". " to make the fullReference?
            */
            //===========================================================================================================================================================
            $fullref = '';
            if($val = $rec['http://purl.org/dc/terms/creator']) $fullref .= "$val. ";
            if($val = $rec['http://purl.org/dc/terms/date']) $fullref .= "$val. ";
            if($val = $rec['http://purl.org/dc/terms/bibliographicCitation']) $fullref .= "$val. ";
            $rec['http://eol.org/schema/reference/full_reference'] = trim($fullref);
            unset($rec['http://purl.org/dc/terms/title']);
            $taxonID = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
            unset($rec['http://rs.tdwg.org/dwc/terms/taxonID']);
            $rec['http://purl.org/dc/terms/identifier'] = md5(json_encode($rec));
            $this->taxonID_ref_info[$taxonID][] = $rec['http://purl.org/dc/terms/identifier'];
            
            unset($rec['http://purl.org/dc/terms/bibliographicCitation']);
            unset($rec['http://purl.org/dc/terms/creator']);
            unset($rec['http://purl.org/dc/terms/date']);
            unset($rec['http://purl.org/dc/terms/type']);
            //===========================================================================================================================================================
            $uris = array_keys($rec);
            $o = new \eol_schema\Reference();
            foreach($uris as $uri) {
                $field = pathinfo($uri, PATHINFO_BASENAME);
                $o->$field = $rec[$uri];
            }
            if(!isset($this->reference_ids[$rec['http://purl.org/dc/terms/identifier']]))
            {
                $this->archive_builder->write_object_to_file($o);
                $this->reference_ids[$rec['http://purl.org/dc/terms/identifier']] = '';
            }
            // if($i >= 10) break; //debug only
        }
    }
    
    //=====================================================ends here
    /*
    private function initialize_mapping()
    {   $mappings = Functions::get_eol_defined_uris(false, true);     //1st param: false means will use 1day cache | 2nd param: opposite direction is true
        echo "\n".count($mappings). " - default URIs from EOL registry.";
        $this->uris = Functions::additional_mappings($mappings); //add more mappings used in the past
    }
    private function process_measurementorfact($meta)
    {   //print_r($meta);
        echo "\nprocess_measurementorfact...\n"; $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            // $row = Functions::conv_to_utf8($row); //possibly to fix special chars. but from copied template
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            } // print_r($rec); exit;
            // Array()
            //===========================================================================================================================================================
            // Data to remove: Katja has heard that records for several of the predicates are suspect. Please remove anything with the predicates below:
            $pred_2remove = array('http://eol.org/schema/terms/NativeIntroducedRange', 'http://eol.org/schema/terms/NativeProbablyIntroducedRange', 
                'http://eol.org/schema/terms/ProbablyIntroducedRange', 'http://eol.org/schema/terms/ProbablyNativeRange', 
                'http://eol.org/schema/terms/ProbablyWaifRange', 'http://eol.org/schema/terms/WaifRange', 'http://eol.org/schema/terms/InvasiveNoxiousStatus');
            $pred_2remove = array_merge($pred_2remove, array('http://eol.org/schema/terms/NativeRange', 'http://eol.org/schema/terms/IntroducedRange')); //will be removed, to get refreshed.
            if(in_array($rec['http://rs.tdwg.org/dwc/terms/measurementType'], $pred_2remove)) continue;
            //===========================================================================================================================================================
            $mtype = $rec['http://rs.tdwg.org/dwc/terms/measurementType'];
            $lifeStage = '';
            if($mtype == 'http://eol.org/schema/terms/SeedlingSurvival') $lifeStage = 'http://purl.obolibrary.org/obo/PPO_0001007';
            elseif($mtype == 'http://purl.obolibrary.org/obo/FLOPO_0015519') $lifeStage = 'http://purl.obolibrary.org/obo/PO_0009010';
            elseif($mtype == 'http://purl.obolibrary.org/obo/TO_0000207') $lifeStage = 'http://purl.obolibrary.org/obo/PATO_0001701';

            $bodyPart = '';
            if($mtype == 'http://purl.obolibrary.org/obo/PATO_0001729') $bodyPart = 'http://purl.obolibrary.org/obo/PO_0025034';
            elseif($mtype == 'http://purl.obolibrary.org/obo/FLOPO_0015519') $bodyPart = 'http://purl.obolibrary.org/obo/PO_0009010';
            elseif($mtype == 'http://purl.obolibrary.org/obo/TO_0000207') $bodyPart = 'http://purl.obolibrary.org/obo/UBERON_0000468';
            
            $rec['http://rs.tdwg.org/dwc/terms/lifeStage'] = $lifeStage;
            $this->occurrenceID_bodyPart[$rec['http://rs.tdwg.org/dwc/terms/occurrenceID']] = $bodyPart;
            //===========================================================================================================================================================
            //===========================================================================================================================================================
            //===========================================================================================================================================================
            $o = new \eol_schema\MeasurementOrFact_specific();
            $uris = array_keys($rec);
            foreach($uris as $uri) {
                $field = pathinfo($uri, PATHINFO_BASENAME);
                $o->$field = $rec[$uri];
            }
            $this->archive_builder->write_object_to_file($o);
            // if($i >= 10) break; //debug only
        }
    }
    private function process_occurrence($meta)
    {   //print_r($meta);
        echo "\nprocess_occurrence...\n"; $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            // $row = Functions::conv_to_utf8($row); //possibly to fix special chars. but from copied template
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            }
            // print_r($rec); exit("\ndebug...\n");
            // Array()
            $uris = array_keys($rec);
            $uris = array('http://rs.tdwg.org/dwc/terms/occurrenceID', 'http://rs.tdwg.org/dwc/terms/taxonID', 'http:/eol.org/globi/terms/bodyPart');
            if($bodyPart = @$this->occurrenceID_bodyPart[$rec['http://rs.tdwg.org/dwc/terms/occurrenceID']]) $rec['http:/eol.org/globi/terms/bodyPart'] = $bodyPart;
            else                                                                                             $rec['http:/eol.org/globi/terms/bodyPart'] = '';
            $o = new \eol_schema\Occurrence_specific();
            foreach($uris as $uri) {
                $field = pathinfo($uri, PATHINFO_BASENAME);
                $o->$field = $rec[$uri];
            }
            $this->archive_builder->write_object_to_file($o);
            // if($i >= 10) break; //debug only
        }
    }
    */
    /*
    private function create_taxon($rec)
    {
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID  = $rec["Symbol"];
        $taxon->scientificName  = $rec["Scientific Name with Author"];
        $taxon->taxonomicStatus = 'valid';
        $taxon->family  = $rec["Family"];
        $taxon->source = $rec['source_url'];
        // $taxon->taxonRank       = '';
        // $taxon->taxonRemarks    = '';
        // $taxon->rightsHolder    = '';
        if(!isset($this->taxon_ids[$taxon->taxonID])) {
            $this->taxon_ids[$taxon->taxonID] = '';
            $this->archive_builder->write_object_to_file($taxon);
        }
    }
    private function create_vernacular($rec)
    {   if($comname = $rec['National Common Name']) {
            $v = new \eol_schema\VernacularName();
            $v->taxonID         = $rec["Symbol"];
            $v->vernacularName  = $comname;
            $v->language        = 'en';
            $this->archive_builder->write_object_to_file($v);
        }
    }
    */
    /*================================================================= ENDS HERE ======================================================================*/
}
?>
