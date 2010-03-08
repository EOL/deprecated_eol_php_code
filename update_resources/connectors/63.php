<?php
//connector for INOTAXA

//http://www.inotaxa.org/jsp/display.jsp?context=TaxonTreatment&taxmlitid=BCA-coleoptv4p3s-t82
//http://www.inotaxa.org/jsp/display.jsp?context=ElementID&taxmlitid=BCA-coleoptv4p3-3313

//exit;

/* good sample for preview
next 9
http://127.0.0.1:3000/harvest_events/8/taxa/732
http://127.0.0.1:3000/harvest_events/8/taxa/620
http://127.0.0.1:3000/harvest_events/8/taxa/515
*/

set_time_limit(0);
ini_set('memory_limit','3500M');

//define("ENVIRONMENT", "development");
//define("ENVIRONMENT", "slave_32");

// /* local
$GLOBALS['ENV_NAME'] = 'slave_32';
define("MYSQL_DEBUG", false);
define("DEBUG", false);
include_once(dirname(__FILE__) . "/../../config/environment.php");
$mysqli =& $GLOBALS['mysqli_connection'];
// */

/* run on beast */
/*
define("MYSQL_DEBUG", false);
define("DEBUG", false);
include_once(dirname(__FILE__) . "/../../config/start.php");
$mysqli =& $GLOBALS['mysqli_connection'];
*/


/*
$mysqli->truncate_tables("development");
Functions::load_fixtures("development");
*/

$resource = new Resource(63);

$subject_arr = array("Associations","Behaviour","Biology","Conservation","ConservationStatus","Cyclicity","Cytology","Description","DiagnosticDescription","Diseases","Dispersal","Distribution","Ecology","Evolution","GeneralDescription","Genetics","Growth","Habitat","Key","Legislation","LifeCycle","LifeExpectancy","Management","Migration","MolecularBiology","Morphology","Physiology","PopulationBiology","Procedures","Reproduction","RiskStatement","Size","TaxonBiology","Threats","Trends","TrophicStrategy","Uses");

$providers = array( 0 => array( "url" => dirname(__FILE__) . "/files/BCA_coleoptv4p3_taXMLit_v4-03-UTF8.xml"      , "active" => 0),
                    1 => array( "url" => dirname(__FILE__) . "/files/Zootaxa_986_Hamilton_taXMLit_v4-03-UTF8.xml" , "active" => 0),
                    2 => array( "url" => "http://128.128.175.77/BCA_coleoptv4p3_taXMLit_v4-03-UTF8.xml"  , "active" => 1)                    
                  );
                  //http://pandanus.eol.org/public/BCA_coleoptv4p3_taXMLit_v4-03-UTF8.xml

$wrap = "\n";
//$wrap = "<br>";

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
/*  if this is outside the main loop then, data objects from a single species
    coming from 2 INOTAXA providers will be placed in 1 <taxon> element.

    if this is outside the loop then, there will be 2 <taxon> elements for 2 same species from 2 BCA providers.
*/
///////////////////////////////

foreach($providers as $provider)
{
    global $wrap;
    // start loop through
    if($provider["active"])
    {
        $url = $provider["url"];
        $xml = @simplexml_load_file($url);        
        if(!($xml = @simplexml_load_file($url)))
        {
            print "$wrap <a href='$url'>$url</a> not accessible";
            continue;
        }

        $i=0;
        $sciname = array();//just for debugging

        $hierarchy = array();

        //start get rights holder
        $rightsHolder = trim(strip_tags($xml->IndividualPublication->PublicationFrontMatter->SeriesTitle->Title->asXML()));
        //end get rights holder

        $main_count=0;
        foreach($xml->IndividualPublication as $main)
        {   $main_count++; 
            
            //print"<pre>";print_r($main);print"</pre>";

            
                        
            $ptm_count=0;
            foreach($main->PublicationTaxonomicMatter as $ptm)
            {   $ptm_count++; 


                //print"<pre>";print_r($ptm);print"</pre>";exit;

            
                $tt_count=0;
                foreach($ptm->TaxonTreatment as $tt)
                {
                    $tt_count++; 
                    print"$wrap $main_count of " . count($xml->IndividualPublication);
                    print" | $ptm_count of " . count($main->PublicationTaxonomicMatter);
                    print" | $tt_count of " . count($ptm->TaxonTreatment);
                
                    $taxon_identifier = @$tt["TaxonID"];
                    $dwc_ScientificName = $tt->TaxonHeading->TaxonHeadingName->AlternateUsedInWork->TaxonName;
                    
                    //print "$wrap $dwc_ScientificName ";                    

                    /*  Aphrastus angularis
                        Attelabus ater
                        Ophryastes ovipennis
                        Thecesternus affinis - Context in original:
                        Thecesternus humeralis - separate or put citation
                    */
                    
                    //if(in_array($dwc_ScientificName, array("Thecesternus humeralis"))){}
                     /*
                    //if(in_array($dwc_ScientificName, array( "Thecesternus humeralis",
                                                            "Aphrastus angularis",
                                                            "Attelabus ater",
                                                            "Ophryastes ovipennis",
                                                            "Thecesternus affinis"))){}                    
                     */
                    //else continue;                    
                    //debug
                    
                    
                    
                    $rank = $tt->TaxonHeading->RankDesignation;
                    //print $dwc_ScientificName . "($taxon_identifier)($rank) | ";
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
                            //print " | "; print $tha->RankDesignation . " ";
                            $ranks = array("kingdom", "phylum", "class", "order", "family", "genus");
                            $rank = $tha->RankDesignation;
                            if(in_array($rank, $ranks))
                            {
                                $taxon_parameters["$rank"] = $tha->TaxonHeadingName->AlternateUsedInWork->TaxonName;
                                $hierarchy["$rank"] = $tha->TaxonHeadingName->AlternateUsedInWork->TaxonName;
                            }
                            //print "[" . utf8_decode($tha->TaxonHeadingName->AlternateUsedInWork->TaxonName) . "] ";
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
                        $taxon_parameters["source"] = "http://www.inotaxa.org/jsp/display.jsp?context=TaxonTreatment&taxmlitid=" . $taxon_identifier;

                        
                        
                        if($main_citation)
                        {
                            $taxon_parameters["references"] = array();
                            $referenceParameters = array();
                            $referenceParameters["fullReference"] = trim($main_citation);
                            $references = array();
                            $references[] = new SchemaReference($referenceParameters);
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
                        $temp = process_dataobjects($arr,1,$ref,$title);
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
                        $temp = process_dataobjects($arr,1,$ref,$title);    
                    }
                    
                    if(isset($tt->NomenclaturalType->NomenclaturalTypeParagraph))
                    {
                        //$title = "Habitat";
                        $title = "Distribution";
                        $arr = $tt->NomenclaturalType->NomenclaturalTypeParagraph;                           
                        $temp = process_dataobjects($arr,1,$ref,$title);
                    }                                        

                    if(isset($tt->Descriptions->SameLanguageDiagnosis->SameLanguageDiagnosisParagraph))
                    {
                        $title = "Description";
                        $arr = $tt->Descriptions->SameLanguageDiagnosis->SameLanguageDiagnosisParagraph;
                        $temp = process_dataobjects($arr,1,$ref,$title);    
                    }
                    
                    //end the new one                                        

                    if(isset($tt->DistributionAndOrSpecimenCitations->DistributionAndOrSpecimenParagraph))
                    {
                        $arr = $tt->DistributionAndOrSpecimenCitations->DistributionAndOrSpecimenParagraph;
                        //---------------------------------------------------------
                        $str = $arr->asXML();
                        $str = trim(strip_tags($str));
                        if(substr($str,0,4)=="Hab.")$title = "Distribution";
                        else                        $title = "Distribution";
                        //$title = "Specimen Citations";
                        //---------------------------------------------------------
                        $temp = process_dataobjects($arr,1,$ref,$title);    
                    }                    

                    if(isset($tt->Discussions->DiscussionBody->DiscussionParagraph))
                    {
                        $title="Discussion";
                        $arr = $tt->Discussions->DiscussionBody->DiscussionParagraph;
                        $temp = process_dataobjects($arr,1,$ref,$title);
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
                    // /*
                    if(isset($tt->TaxonHeading->TaxonHeadingParagraph->ref))
                    {
                        $arr = $tt->TaxonHeading->TaxonHeadingParagraph->ref;
                        $temp = process_dataobjects($arr,2,"","");
                    }
                    // */
                    //end image dataobject
                    

                    //print"$wrap";
                }
                //print"<hr>";
            }
        }


        /*
        print "<hr>
        i = $i $wrap
        sciname = " . count($sciname);
        print "<hr>";
        print count(array_keys($sciname));
        print "<hr>";
        print count(array_keys($used_taxa));
        */

        // end loop through

        print "$wrap $wrap" . $provider["url"];

    }//if($provider["active"])

}//end main loop


foreach($used_taxa as $taxon_parameters)
{
    $schema_taxa[] = new SchemaTaxon($taxon_parameters);
}
////////////////////// ---
$new_resource_xml = SchemaDocument::get_taxon_xml($schema_taxa);
$old_resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource->id . ".xml";
$OUT = fopen($old_resource_path, "w+");
fwrite($OUT, $new_resource_xml);
fclose($OUT);
////////////////////// ---

print "$wrap -Done processing- ";


function get_agents($agents_arr)
{
    $agents=array();
    foreach($agents_arr as $agent)
    {
        $agents[] = $agent->TreatmentAuthorAtomised;
    }
    return $agents;
}

function separate_footnote_from_paragraph($temp)
{   
    if(substr_count($temp, 'â€') > 1)
    {
        // start separates <footnote> from the paragraph
        $pos = strrpos($temp, 'â€'); //this is the char †     
        if(is_numeric($pos))$temp = substr_replace($temp, '<br><br>', $pos,0) ;                  
    }
    $temp = remove_tag($temp,"milestone");    
    $temp = str_ireplace("Context in original:", "", $temp);    
    return $temp;
}

function remove_tag($str,$tag)
{   /* this will remove <tag>xxx</tag> from string */
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


function process_dataobjects($arr,$type,$ref,$title)//$type 1 = text object; 2 = image object
{
    global $taxon_identifier;
    global $tt;
    global $taxon_parameters;
    global $used_taxa;
    global $taxon;
    global $subject_arr;

    //if(is_array($arr))
    //{
    $description="";
    foreach(@$arr as $item)
    {   
        if($type == 1)//text
        {
            $temp = @$item->asXML();
            //$temp = @$item;
            //print"$wrap[[$title]]$wrap";
            if($title == "Discussion")$temp = separate_footnote_from_paragraph($temp);
            
            //print"<hr>$temp";//debug
                        
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
                if($dc_identifier == "")$dc_identifier = "object_" . $taxon_identifier;
            }
            else $dc_identifier = $image_id;                                                        
            
            /*
            if(isset($item["KindOfDiscussion"]))  $title = trim($item["KindOfDiscussion"]);
            else                                  $title = "Body Description";
            $title = ucfirst(strtolower($title));            
            */
            //$title = "Description";            
            
            if($type == 2)$title = "";

            if(in_array($title, $subject_arr))$subject = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#" . $title;
            else                              $subject = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#GeneralDescription";
            if($type == 2)$subject = "";
         
            if($type == 1)
            {
                $dc_source = "http://www.inotaxa.org/jsp/display.jsp?context=TaxonTreatment&taxmlitid=" . $taxon_identifier;
                $mediaURL="";
                $dataType = "http://purl.org/dc/dcmitype/Text";
                $mimeType = "text/html";
                $agents = get_agents($tt->TreatmentAuthors);
            }
            else
            {             
                $dc_source = "http://www.inotaxa.org/jsp/display.jsp?context=Figure&taxmlitid=" . $image_id;
                $mediaURL = "http://www.nhm.ac.uk/hosted-sites/inotaxa/images/img/" . $image_id . ".jpg";
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
            
            if($type == 2)
            {
            $data_object_parameters = get_data_object($dc_identifier, $dcterms_created, $dcterms_modified, $license, $description, $subject, $title, $dc_source, $mediaURL, $dataType, $mimeType, $ref, $agents);
            $taxon_parameters["dataObjects"][] = new SchemaDataObject($data_object_parameters);
            $used_taxa[$taxon] = $taxon_parameters;                    
            }            
        }
    }    
    //}

    //<br>&nbsp;<br>
    $description = trim(substr($description,14,strlen($description)));
    if($type == 1)
    {
        $data_object_parameters = get_data_object($dc_identifier, $dcterms_created, $dcterms_modified, $license, $description, $subject, $title, $dc_source, $mediaURL, $dataType, $mimeType, $ref, $agents);
        $taxon_parameters["dataObjects"][] = new SchemaDataObject($data_object_parameters);
        $used_taxa[$taxon] = $taxon_parameters;                    
    }
    
    
    //else{exit("not an array");}
}//function process_dataobjects($arr)

function get_ref_from_site($dc_source)
{
    set_time_limit(0);
    $str = Functions::get_remote_file($dc_source);        

    $beg='"getActiveText()"><nonexplicit>'; $end1='</nonexplicit>'; 
    $ref = trim(parse_html($str,$beg,$end1,$end1,$end1,$end1,""));            
    
    $beg='"getActiveText()"><nonexplicit>'; $end1='</a>'; 
    $str = trim(parse_html($str,$beg,$end1,$end1,$end1,$end1,""));            
    
    $str .= "xxx";
    $beg='<a'; $end1='xxx'; 
    $str = trim(parse_html($str,$beg,$end1,$end1,$end1,$end1,""));                      
    $str = "<a" . $str;
    
    $url = get_href_from_anchor_tag($str);
    
    return array($ref,$url);
}

function get_href_from_anchor_tag($str)
{
    //      <a href="reference_detail.cfm?ref_number=58&type=Article"> 
    $beg='href="'; $end1='"'; 
    return trim(parse_html($str,$beg,$end1,$end1,$end1,$end1,"",true));//exist on first match = true
}

function parse_html($str,$beg,$end1,$end2,$end3,$end4,$all=NULL)	//str = the html block
{
    //PRINT "[$all]"; exit;
	$beg_len = strlen(trim($beg));
	$end1_len = strlen(trim($end1));
	$end2_len = strlen(trim($end2));
	$end3_len = strlen(trim($end3));	
	$end4_len = strlen(trim($end4));		
	//print "[[$str]]";

	$str = trim($str); 	
	$str = $str . "|||";	
	$len = strlen($str);	
	$arr = array(); $k=0;	
	for ($i = 0; $i < $len; $i++) 
	{
		//if(substr($str,$i,$beg_len) == $beg)
        if(strtolower(substr($str,$i,$beg_len)) == strtolower($beg))
		{	
			$i=$i+$beg_len;
			$pos1 = $i;			
			//print substr($str,$i,10) . "<br>";									
			$cont = 'y';
			while($cont == 'y')
			{
				if(	substr($str,$i,$end1_len) == $end1 or 
					substr($str,$i,$end2_len) == $end2 or 
					substr($str,$i,$end3_len) == $end3 or 
					substr($str,$i,$end4_len) == $end4 or 
					substr($str,$i,3) == '|||' )
				{
					$pos2 = $i - 1; 					
					$cont = 'n';					
					$arr[$k] = substr($str,$pos1,$pos2-$pos1+1);																				
					//print "$arr[$k] $wrap";					
					$k++;
				}
				$i++;
			}//end while
			$i--;			
		}		
	}//end outer loop
    if($all == "")	
    {
        $id='';
	    for ($j = 0; $j < count($arr); $j++){$id = $arr[$j];}		
        return $id;
    }
    elseif($all == "all") return $arr;	
}//end function

function get_data_object($id, $created, $modified, $license, $description, $subject, $title, $dc_source, $mediaURL, $dataType, $mimeType, $ref, $agents_arr)
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
        $dataObjectParameters["subjects"][] = new SchemaSubject($subjectParameters);
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
            $agents[] = new SchemaAgent($agentParameters);
        }
        $dataObjectParameters["agents"] = $agents;
    }

    ///////////////////////////////////
    $dataObjectParameters["audiences"] = array();
    $audienceParameters = array();

    $audienceParameters["label"] = "Expert users";
    $dataObjectParameters["audiences"][] = new SchemaAudience($audienceParameters);

    $audienceParameters["label"] = "General public";
    $dataObjectParameters["audiences"][] = new SchemaAudience($audienceParameters);
    ///////////////////////////////////

    /* bypass $ref that was passed */
    // /*
    $arr = get_ref_from_site($dc_source);
    if($arr)
    {
        $ref        = @$arr[0];
        $ref_url    = @$arr[1];
    }
    // */
    
    /*
    $ref="";
    $ref_url="";
    */

    ///////////////////////////////////
    ///*working
    if($ref)
    {
        $dataObjectParameters["references"] = array();

        $referenceParameters = array();
        $referenceParameters["fullReference"] = trim($ref);
        if($ref_url)$referenceParameters["referenceIdentifiers"][] = new SchemaReferenceIdentifier(array("label" => "url" , "value" => $ref_url));                                
        $references[] = new SchemaReference($referenceParameters);

        $dataObjectParameters["references"] = $references;
    }
    ///////////////////////////////////

    return $dataObjectParameters;
}


?>