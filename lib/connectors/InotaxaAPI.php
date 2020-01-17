<?php
namespace php_active_record;
/* connector: 63
Partner provided a non EOL-compliant XML file for all their species.
Connector parses this XML and generates the EOL-compliant XML.
You can download a copy of the XML here: 
https://github.com/eliagbayani/EOL-connector-data-files/raw/master/INOTAXA/BCA_coleoptv4p3_taXMLit_v4-03-UTF8.xml
*/

define("SUBJECT_LIST", "Associations,Behaviour,Biology,Conservation,ConservationStatus,Cyclicity,Cytology,Description,DiagnosticDescription,Diseases,Dispersal,Distribution,Ecology,Evolution,GeneralDescription,Genetics,Growth,Habitat,Key,Legislation,LifeCycle,LifeExpectancy,Management,Migration,MolecularBiology,Morphology,Physiology,PopulationBiology,Procedures,Reproduction,RiskStatement,Size,TaxonBiology,Threats,Trends,TrophicStrategy,Uses");
define("SOURCE_URL_PREFIX", "http://www.inotaxa.org/jsp/display.jsp?context=TaxonTreatment&taxmlitid=");
define("SOURCE_IMAGE_URL_PREFIX", "http://www.inotaxa.org/jsp/display.jsp?context=Figure&taxmlitid=");
define("MEDIA_URL_PREFIX", "http://www.nhm.ac.uk/hosted-sites/inotaxa/images/img/");

class InotaxaAPI
{
    public static function get_all_taxa()
    {
        $all_taxa = array();
        $path= DOC_ROOT . "/update_resources/connectors/files/INOTAXA/";
        $urls = array( 0 => array( "path" => $path . "BCA_coleoptv4p3_taXMLit_v4-03-UTF8.xml", "active" => 0),
                       1 => array( "path" => "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/INOTAXA/BCA_coleoptv4p3_taXMLit_v4-03-UTF8.xml"        , "active" => 1),
                       2 => array( "path" => "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/INOTAXA/Zootaxa_986_Hamilton_taXMLit_v4-03-UTF8.xml"   , "active" => 1)
                     );
        foreach($urls as $url) {
            if($url["active"]) {
                $page_taxa = self::get_inotaxa_taxa($url["path"]);                                
                /*debug
                print"<hr>website: " . $url["path"] . "<br>";
                print"page_taxa count: " . $url["path"] . " -- " . count($page_taxa) . "<hr>";                
                */
                //print"<pre>page_taxa: ";print_r($page_taxa);print"</pre>";                        
                if($page_taxa) {
                    $all_taxa = array_merge($all_taxa,$page_taxa);                                    
                    //or use this => foreach($page_taxa as $t) $all_taxa[] = $t;
                }
            }
        }
        //print"<hr><pre>all_taxa: ";print_r($all_taxa);print"</pre>"; //debug see all records
        //print"total: " . count($all_taxa) . "<br>\n"; //debug       
        return $all_taxa;
    }
    public static function get_inotaxa_taxa($url,$debug=NULL)
    {    
        global $taxon_identifier;
        global $taxon_parameters;
        global $used_taxa;
        global $taxon;        
        global $rightsHolder;
        
        /*
        TaxonomicPublication
            IndividualPublication
                Metadata
                PublicationFrontMatter
                PublicationTaxonomicMatter  Heading="none"
                    TaxonTreatment
                        TreatmentAuthors
                            TreatmentAuthorAtomised
                        TreatmentDate
                        TaxonHeading
                            RankDesignation
                            TaxonHeadingName
                                AlternateUsedInWork
                                    TaxonName
                                    GenusName
                        TaxonCitationGroup
                        Descriptions
                        DistributionAndOrSpecimenCitations
                        Discussions
                PublicationTaxonomicMatter  Heading="Supplement"
                PublicationBackMatter
                OtherNamesIndex
        */
        
        $schema_taxa = array();        
        ///////////////////////////////
        $used_taxa = array();
        /*  If this is outside the main loop then, data objects from a single species
            coming from 2 INOTAXA providers will be placed in 1 <taxon> element.        
            If this is inside the loop then, there will be 2 <taxon> elements for 2 same species from 2 BCA providers.
        */
        ///////////////////////////////        

        $xml = Functions::get_hashed_response($url);        
        if(!($xml = Functions::get_hashed_response($url)))
        {
            print "\n <a href='$url'>$url</a> not accessible";
            return;
        }        
        $i=0;
        $sciname = array();//just for debugging        
        $hierarchy = array();        
        $rightsHolder = trim(strip_tags($xml->IndividualPublication->PublicationFrontMatter->SeriesTitle->Title->asXML()));        
        $main_count=0;
        foreach($xml->IndividualPublication as $main)
        {   $main_count++;                     
            //print"<pre>";print_r($main);print"</pre>";                                
            $ptm_count=0;
            foreach($main->PublicationTaxonomicMatter as $ptm)
            {   $ptm_count++;         
                $tt_count=0;
                foreach($ptm->TaxonTreatment as $tt)
                {
                    $tt_count++; 
                    //print"\n $main_count of " . count($xml->IndividualPublication);
                    //print" | $ptm_count of " . count($main->PublicationTaxonomicMatter);
                    //print" | $tt_count of " . count($ptm->TaxonTreatment);
                
                    $taxon_identifier = @$tt["TaxonID"];
                    $dwc_ScientificName = trim($tt->TaxonHeading->TaxonHeadingName->AlternateUsedInWork->TaxonName);
                    
                    if($debug)
                    {
                        if(in_array($dwc_ScientificName, array("Attelabus ater"))){}else continue; 
                        /*  
                            Thecesternus affinis - Context in original:
                            Thecesternus humeralis - separate or put citation
                        */                                                    
                        /*
                        if(in_array($dwc_ScientificName, array( "Thecesternus humeralis",
                                                                "Ophryastes ovipennis",
                                                                "Anypotactus",
                                                                "Ophryastes bituberosus",
                                                                "Aphrastus angularis",
                                                                "Attelabus ater"
                                                                ))){}                    
                        else continue;                                                                                
                        */                    
                        //debug    
                    }
                    
                    $rank = $tt->TaxonHeading->RankDesignation;
                    $i++;
                    $sciname["$dwc_ScientificName"] = $dwc_ScientificName;        
                    $taxon = str_replace(" ", "_", $dwc_ScientificName);
                    if(@$used_taxa[$taxon])
                    {
                        $taxon_parameters = $used_taxa[$taxon];
                    }
                    else
                    {
                        $taxon_parameters = array();        
                        //start get hierarchy
                        foreach($tt->TaxonHierarchyAbove as $tha)
                        {
                            $ranks = array("kingdom", "phylum", "class", "order", "family", "genus");
                            $rank = $tha->RankDesignation;
                            if(in_array($rank, $ranks))
                            {
                                $taxon_parameters["$rank"] = $tha->TaxonHeadingName->AlternateUsedInWork->TaxonName;
                                $hierarchy["$rank"] = $tha->TaxonHeadingName->AlternateUsedInWork->TaxonName;
                            }
                        }
                        if($hierarchy)//if with taxon hierarcy
                        {
                            $arr = array_keys($hierarchy);
                            foreach($arr as $rank)
                            {
                                $taxon_parameters["$rank"] = $hierarchy["$rank"];
                            }
                        }
                        //end get hierarchy

                        $main_citation="";
                        if(isset($xml->IndividualPublication->Metadata->FullBibliographicCitation))$main_citation = $xml->IndividualPublication->Metadata->FullBibliographicCitation;

                        $taxon_parameters["identifier"] = $taxon_identifier;
                        $taxon_parameters["scientificName"]= $dwc_ScientificName;
                        $taxon_parameters["source"] = SOURCE_URL_PREFIX . $taxon_identifier;
                        
                        if($main_citation)
                        {
                            $taxon_parameters["references"] = array();
                            $referenceParameters = array();
                            $referenceParameters["fullReference"] = trim($main_citation);
                            $references = array();
                            $references[] = new \SchemaReference($referenceParameters);
                            $taxon_parameters["references"] = $references;
                        }

                        $taxon_parameters["dataObjects"]= array();

                        $ref="";
                        if(isset($tt->TaxonCitationGroup->AcceptedOrValidTaxonName->AcceptedOrValidTaxonNameParagraph))
                        {
                            foreach($tt->TaxonCitationGroup->AcceptedOrValidTaxonName->AcceptedOrValidTaxonNameParagraph as $aovtp)
                            {
                                $ref = strip_tags($aovtp->asXML());
                            }
                        }

                        $used_taxa[$taxon] = $taxon_parameters;
                    }

                    /*
                    <Descriptions>
                        <SameLanguageDiagnosis>
                            <SameLanguageDiagnosisParagraph Display="true" Explicit="true" ElementID="BCA-coleoptv4p3-4435">
                                Convex, black; variegated above with a dense clothing of light and dark brown scales, the dark brown scales on the elytra condensed into a transverse or curved mark below the base, extending forward along the third interstice to the anterior margin, an angulate median fascia (not reaching the suture), and a small triangular patch on the disc towards the apex, the scales along the exposed basal margin of the head, at the base of the femora above, and on the under surface paler or whitish; the elytra also thickly set with long, stiff, erect setæ, and the rest of the surface with short setiform scales. Head and rostrum finely canaliculate, the rostrum hollowed towards the apex; joint 2 of the funiculus nearly twice as long as 1. Prothorax much broader than long, rounded at the sides, in the ? not narrower at the apex than at the base, densely, finely punctate. Elytra oval, convex, rather short, constricted immediately below the base, 
                                <pb id="BCA-coleoptv4p3-p339" Explicit="true"/>
                                the base itself not or very little wider than that of the prothorax; coarsely punctate-striate (when seen abraded), the interstices feebly convex.
                            </SameLanguageDiagnosisParagraph>
                            <SameLanguageDiagnosisParagraph Display="true" Explicit="true" ElementID="BCA-coleoptv4p3-4436">
                                Length 4½—5½, breadth 2 1/10—2½ millim. (? ?.)
                            </SameLanguageDiagnosisParagraph>
                        </SameLanguageDiagnosis>
                    </Descriptions>
                    <Discussions>
                        <DiscussionBody Display="true">
                            <DiscussionParagraph KindOfDiscussion="general" Explicit="true" ElementID="BCA-coleoptv4p3-4438">Five specimens.
                            </DiscussionParagraph>
                        </DiscussionBody>
                    </Discussions>                    
                    */
                    
                    if(isset($tt->Descriptions->SameLanguageDescription->SameLanguageDescriptionBody->SameLanguageDescriptionParagraph))
                    {
                        /* this is for Hamilton XML */
                        $title="Description";
                        $arr = $tt->Descriptions->SameLanguageDescription->SameLanguageDescriptionBody->SameLanguageDescriptionParagraph;
                        $temp = self::process_dataobjects($arr,1,$ref,$title,$tt);
                    }
                            
                    /*
                    <Descriptions>
                        <LatinDiagnosis>
                            <LatinDiagnosisParagraph Display="true" Explicit="true" ElementID="BCA-coleoptv4p3-2374">
                                Sat robustus, piceus, parce setoso-squamosus, setis crassiusculis brevibus vestitus; antennis tarsisque rufis; prothorace rugoso, elytris fortiter seriatim punctatis.
                            </LatinDiagnosisParagraph>
                            <LatinDiagnosisParagraph Display="true" Explicit="true" ElementID="BCA-coleoptv4p3-2375">
                                Long. 5½ millim.
                            </LatinDiagnosisParagraph>
                        </LatinDiagnosis>
                    </Descriptions>                    
                    
                    <DistributionAndOrSpecimenCitations>
                        <DistributionAndOrSpecimenParagraph Display="true" Explicit="true" ElementID="BCA-coleoptv4p3-62">
                            <hi rend="italic">Hab.</hi>
                            MEXICO, Omilteme in Guerrero 8000 feet (
                            <hi rend="italic">H. H. Smith</hi>
                            ).
                        </DistributionAndOrSpecimenParagraph>        
                    */                    

                    //start the new one                 
                    if(isset($tt->Descriptions->LatinDiagnosis->LatinDiagnosisParagraph))
                    {
                        $title="Latin Diagnosis";
                        $arr = $tt->Descriptions->LatinDiagnosis->LatinDiagnosisParagraph;
                        $temp = self::process_dataobjects($arr,1,$ref,$title,$tt);    
                    }
                    
                    if(is_numeric(stripos($dwc_ScientificName," ")))//genus doesn't have distribution only species level and below
                    {
                        if(isset($tt->NomenclaturalType->NomenclaturalTypeParagraph))
                        {
                            //$title = "Habitat";
                            $title = "Distribution";
                            $arr = $tt->NomenclaturalType->NomenclaturalTypeParagraph;                           
                            $temp = self::process_dataobjects($arr,1,$ref,$title,$tt);
                        }                                        
                    }                                                

                    if(isset($tt->Descriptions->SameLanguageDiagnosis->SameLanguageDiagnosisParagraph))
                    {
                        $title = "Description";
                        $arr = $tt->Descriptions->SameLanguageDiagnosis->SameLanguageDiagnosisParagraph;
                        $temp = self::process_dataobjects($arr,1,$ref,$title,$tt);    
                    }                            
                    //end the new one                                        

                    if(isset($tt->DistributionAndOrSpecimenCitations->DistributionAndOrSpecimenParagraph))
                    {
                        $arr = $tt->DistributionAndOrSpecimenCitations->DistributionAndOrSpecimenParagraph;
                        $str = $arr->asXML();
                        $str = trim(strip_tags($str));
                        if(substr($str,0,4)=="Hab.")$title = "Distribution";
                        else                        $title = "Distribution";
                        //$title = "Specimen Citations";
                        $temp = self::process_dataobjects($arr,1,$ref,$title,$tt);    
                    }                    

                    if(isset($tt->Discussions->DiscussionBody->DiscussionParagraph))
                    {
                        $title="Discussion";
                        $arr = $tt->Discussions->DiscussionBody->DiscussionParagraph;
                        $temp = self::process_dataobjects($arr,1,$ref,$title,$tt);
                    }                    

                    /*
                    <NomenclaturalType>
                        <NameTypified Explicit="false" NameID="BCA-coleoptv4p3-t687"/>
                        <NomenclaturalTypeParagraph Display="true" Explicit="true" ElementID="BCA-coleoptv4p3-3746">
                            <hi rend="italic">Hab.</hi>
                            MEXICO
                            <ref target="BCA-coleoptv4p3-t687-x1">¹</ref>
                             (
                            <hi rend="italic">coll. Solari, ex Jekel</hi>
                            ), Panistlahuaca in Oaxaca (
                            <hi rend="italic">Sallé</hi>
                            ).
                        </NomenclaturalTypeParagraph>
                    */
                            
                    //start image dataobject
                    if(isset($tt->TaxonHeading->TaxonHeadingParagraph->ref))
                    {
                        $arr = $tt->TaxonHeading->TaxonHeadingParagraph->ref;
                        $temp = self::process_dataobjects($arr,2,"","",array());
                    }
                    //end image dataobject                    
                }
            }
        }// end loop through                                        
        
        foreach($used_taxa as $taxon_parameters)
        {$schema_taxa[] = new \SchemaTaxon($taxon_parameters);}
        
        return $schema_taxa;            
    }//get_all_taxa
    
    public static function process_dataobjects($arr,$type,$ref,$title,$tt)//$type 1 = text object; 2 = image object
    {
        global $taxon_identifier;
        global $taxon_parameters;
        global $used_taxa;
        global $taxon;
    
        $description="";
        foreach(@$arr as $item)
        {   
            if($type == 1)//text
            {
                $temp = @$item->asXML();
                if($title == "Discussion")$temp = self::separate_footnote_from_paragraph($temp);
                $description .= "<br>&nbsp;<br>" . trim(strip_tags($temp,"<br>"));                                    
            }
            else //image
            {
                $image_id = trim($item["target"]);
                $ans = strval(stripos($image_id,"pfig"));            
                $description = $ans;
            }
    
            if($description != "")
            {
                if($type == 1)
                {
                    $dc_identifier = $item["ElementID"];
                    /* if($dc_identifier == "")$dc_identifier = "object_" . $taxon_identifier; */
                }
                else $dc_identifier = $image_id;                                                        
                
                /*
                if(isset($item["KindOfDiscussion"]))  $title = trim($item["KindOfDiscussion"]);
                else                                  $title = "Body Description";
                $title = ucfirst(strtolower($title));            
                */
                //$title = "Description";            
                
                if($type == 2)$title = "";
    
                $subjects = explode(",",SUBJECT_LIST);
                if(in_array($title, $subjects))  $subject = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#" . $title;            
                elseif($title == "Latin Diagnosis") $subject = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#DiagnosticDescription";//new
                elseif(in_array($title, array("Discussion","Description"))) //new
                {
                    $title = "Physical description";
                    $subject = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Morphology";                
                }             
                else                                $subject = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#GeneralDescription";
                
                if($type == 2)$subject = "";
             
                if($type == 1)
                {
                    $dc_source = SOURCE_URL_PREFIX . $taxon_identifier;
                    $mediaURL="";
                    $dataType = "http://purl.org/dc/dcmitype/Text";
                    $mimeType = "text/html";
                    $agents = self::get_agents(@$tt->TreatmentAuthors);
                }
                else
                {             
                    $dc_source = SOURCE_IMAGE_URL_PREFIX . $image_id;
                    $mediaURL = MEDIA_URL_PREFIX . $image_id . ".jpg";
                    $dataType = "http://purl.org/dc/dcmitype/StillImage";
                    $mimeType = "image/jpeg";
                    $agents = array();
                                    
                    $description = "";
                    //start get caption
                    if(isset($xml->IndividualPublication->PublicationTaxonomicMatter->PlateOrTable->ImageCaption->SubCaptionComponents))
                    {
                        foreach($xml->IndividualPublication->PublicationTaxonomicMatter->PlateOrTable as $pot)
                        {
                            foreach($pot->ImageCaption as $ic)
                            {
                                foreach($ic->SubCaptionComponents as $scc)
                                {
                                    if($scc["ImageOrTableID"] == "$image_id") $description = trim(strip_tags($scc->SubCaptionText->asXML())); //print("<hr>11<hr>image id = $image_id<hr>" . $description);
                                }
                            }
                        }
                    }
                    //end get caption                
                }
                            
                $dcterms_created = "";
                $dcterms_modified = "";            
                $license = "http://creativecommons.org/licenses/by/3.0/";                                           
                
                if($type == 2 and $dc_identifier != "")
                {
                    $data_object_parameters = self::get_data_object($dc_identifier, $dcterms_created, $dcterms_modified, $license, $description, $subject, $title, $dc_source, $mediaURL, $dataType, $mimeType, $ref, $agents);
                    $taxon_parameters["dataObjects"][] = new \SchemaDataObject($data_object_parameters);
                    $used_taxa[$taxon] = $taxon_parameters;                    
                }            
            }
        }    
    
        $description = trim(substr($description,14,strlen($description)));
        if($type == 1 and $dc_identifier != "")
        {
            $data_object_parameters = self::get_data_object($dc_identifier, $dcterms_created, $dcterms_modified, $license, $description, $subject, $title, $dc_source, $mediaURL, $dataType, $mimeType, $ref, $agents);
            $taxon_parameters["dataObjects"][] = new \SchemaDataObject($data_object_parameters);
            $used_taxa[$taxon] = $taxon_parameters;                    
        }            
    }//function process_dataobjects($arr)
    
    public static function get_data_object($id, $created, $modified, $license, $description, $subject, $title, $dc_source, $mediaURL, $dataType, $mimeType, $ref, $agents_arr)
    {
        global $rightsHolder;
        
        $dataObjectParameters = array();            
        $dataObjectParameters["title"] = $title;
        $dataObjectParameters["rightsHolder"] = $rightsHolder;
        if($subject)
        {
            $dataObjectParameters["subjects"] = array();
            $subjectParameters = array();
            $subjectParameters["label"] = $subject;
            $dataObjectParameters["subjects"][] = new \SchemaSubject($subjectParameters);
        }
        $description = str_replace(array("\n", "\r", "\t", "\o", "\xOB"), '', $description);
        $dataObjectParameters["description"] = trim($description);    
        $dataObjectParameters["identifier"] = $id;
        $dataObjectParameters["created"] = $created;
        $dataObjectParameters["modified"] = $modified;
        $dataObjectParameters["rightsHolder"] = $rightsHolder;
        $dataObjectParameters["dataType"] = $dataType;
        $dataObjectParameters["mimeType"] = $mimeType;
        $dataObjectParameters["language"] = "en";
        $dataObjectParameters["license"] = $license;
        $dataObjectParameters["mediaURL"] = $mediaURL;
        $dataObjectParameters["source"] = $dc_source;
    
        if($agents_arr)
        {
            $agentParameters = array();
            foreach($agents_arr as $agent)
            {
                $agentParameters["role"] = "author";
                $agentParameters["fullName"] = $agent;
                $agents[] = new \SchemaAgent($agentParameters);
            }
            $dataObjectParameters["agents"] = $agents;
        }
        ///////////////////////////////////
        $dataObjectParameters["audiences"] = array();
        $audienceParameters = array();
    
        $audienceParameters["label"] = "Expert users";
        $dataObjectParameters["audiences"][] = new \SchemaAudience($audienceParameters);    
    
        /* bypass $ref that was passed */
        $arr = self::get_ref_from_site($dc_source);
        if($arr)
        {
            $ref        = @$arr[0];
            $ref_url    = @$arr[1];
        }
        if($ref)
        {
            $dataObjectParameters["references"] = array();
            $referenceParameters = array();
            $referenceParameters["fullReference"] = trim($ref);
            if($ref_url)$referenceParameters["referenceIdentifiers"][] = new \SchemaReferenceIdentifier(array("label" => "url" , "value" => $ref_url));                                
            $references[] = new \SchemaReference($referenceParameters);
            $dataObjectParameters["references"] = $references;
        }
        return $dataObjectParameters;
    }    

    public static function get_agents($agents_arr)
    {
        $agents=array();
        if($agents_arr){foreach($agents_arr as $agent){$agents[] = $agent->TreatmentAuthorAtomised;}}
        return $agents;
    }
    
    public static function separate_footnote_from_paragraph($temp)
    {   
        if(substr_count($temp, 'â€') > 1)
        {
            // start separates <footnote> from the paragraph
            $pos = strrpos($temp, 'â€'); //this is the char †     
            if(is_numeric($pos))$temp = substr_replace($temp, '<br><br>', $pos,0) ;                  
        }
        $temp = self::remove_tag($temp,"milestone");    
        $temp = str_ireplace("Context in original:", "", $temp);    
        return $temp;
    }
    
    public static function remove_tag($str,$tag)
    {   /* this will remove <tag>???</tag> from string */
        $needle = "<" . $tag; 
        $pos = stripos($str, $needle);
        if(is_numeric($pos) and $pos > 0)
        {
            $temp1 = substr($str,0,$pos-1);
            $needle = "</" . $tag . ">";
            $pos = stripos($str, $needle);
            $temp2 = substr($str,$pos+(3+strlen($needle)),strlen($str));
            return $temp1 . $temp2;
        }    
        return $str;
    }    

    public static function get_ref_from_site($dc_source)
    {
        $str = Functions::get_remote_file($dc_source);        
            //$beg='"getActiveText()"><nonexplicit>';$end='</nonexplicit>'; 
        $ref = self::get_string_between('\"getActiveText\(\)\"><nonexplicit>','<\/nonexplicit>',$str);
            //$beg='"getActiveText()"><nonexplicit>';$end='</a>'; 
        $str = self::get_string_between('\"getActiveText\(\)\"><nonexplicit>','<\/a>',$str);
        $str .= "xxx";
            //$beg='<a';$end='xxx'; 
        $str = self::get_string_between('<a','xxx',$str);            
        $str = "<a" . $str;    
        $url = self::get_href_from_anchor_tag($str);    
        return array($ref,$url);
    }
    
    public static function get_string_between($str_left,$str_right,$string)
    {
        if(preg_match("/$str_left(.*?)$str_right/ims", $string, $matches))return trim($matches[1]);
        return;            
    }
    
    public static function get_href_from_anchor_tag($str)
    {
        //<a href="reference_detail.cfm?ref_number=58&type=Article">
        return self::get_string_between('href=\"','\"',$str);
    }    
}
?>