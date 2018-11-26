<?php
namespace php_active_record;
/*
commons Sunday 2017-11-26 01:34:01 PM   {"agent.tab":19055,"media_resource.tab":909996,"taxon.tab":89054}
71  Saturday 2017-12-02 04:39:38 AM {"agent.tab":19252,"media_resource.tab":912111,"taxon.tab":89748}
71  Tuesday 2017-12-19 02:05:02 AM  {"agent.tab":19813,"media_resource.tab":932989,"taxon.tab":89753,"vernacular_name.tab":291572}
71  Wednesday 2018-01-03 08:40:51 PM{"agent.tab":19369,"media_resource.tab":935068,"taxon.tab":89806,"vernacular_name.tab":291603}
71  Saturday 2018-04-21 06:58:29 AM {"agent.tab":20658,"media_resource.tab":946750,"taxon.tab":89694,"vernacular_name.tab":291271}
71	Wednesday 2018-05-02 04:59:07 AM{"agent.tab":20658,"media_resource.tab":946750,"taxon.tab":89694,"vernacular_name.tab":291271}
71	Wednesday 2018-06-06 06:59:52 AM{"agent.tab":21060,"media_resource.tab":943420,"taxon.tab":89739,"vernacular_name.tab":291235}
71	Thursday 2018-07-05 11:59:10 AM	{"agent.tab":21251,"media_resource.tab":946050,"taxon.tab":89873,"vernacular_name.tab":291188}

71	Saturday 2018-10-06 04:19:13 AM	 {"agent.tab": 22435,"media_resource.tab": 956972,"taxon.tab": 90844,"vernacular_name.tab":291267}
71	Monday 2018-11-19 11:36:53 AM	 {"agent.tab":267873,"media_resource.tab":1167960,"taxon.tab":113398,"vernacular_name.tab":315324} ??? big increase...will investigate
71	Wednesday 2018-11-21 02:05:16 AM {"agent.tab":267877,"media_resource.tab":1167960,"taxon.tab":113398,"vernacular_name.tab":315324}
71	Saturday 2018-11-24 09:54:22 PM	 {"agent.tab":268413,"media_resource.tab":1168639,"taxon.tab":113399,"vernacular_name.tab":315324}

--- 71_new eventually becomes 71.tar.gz
71_new	Monday 2018-11-19 11:48:24 AM   {"agent.tab":267873,"media_resource.tab":1167960,"taxon.tab":113398}
71_new	Wednesday 2018-11-21 02:16:00 AM{"agent.tab":267877,"media_resource.tab":1167960,"taxon.tab":113398}
71_new	Saturday 2018-11-24 10:05:39 PM	{"agent.tab":268413,"media_resource.tab":1168639,"taxon.tab":113399}

when doing tests locally:
php update_resources/connectors/wikidata.php _ generate_resource

historical investigations:
/01 EOL Projects ++/Wikimedia run status/2018 07 06 consoleText.txt

*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/WikiDataAPI');
$timestart = time_elapsed();
$GLOBALS['ENV_DEBUG'] = false;

/*
$str = "abcd|e|||";
echo "\n[$str]\n";
$str = Functions::remove_this_last_char_from_str($str, "|");
echo "\n[$str]\n";
exit;
*/

/* testing routines...
$str = "Year: [https://www.flickr.com/search/?tags=bookyear1841 1841] ([https://www.flickr.com/search/?tags=bookdecade1840 1840s])";
$str =  "Publisher: [https://www.flickr.com/search/?tags=bookpublisherLeiden_A_Arnz_comp_ Leiden, A. Arnz &amp; comp.]";
echo "\n$str\n";
echo wiki2html($str);
exit("\n");
*/

/* utility
$func = new WikiDataAPI($resource_id, "");
$func->process_wikimedia_txt_dump(); //initial verification of the wikimedia dump file. Not part of the normal operation
exit("\n Finished: just exploring... \n");
*/

/* sample command eol-archive:
php5.6 wikidata.php jenkins generate_resource 1 200000 1of6
php5.6 wikidata.php jenkins generate_resource
php5.6 wikidata.php jenkins generate_resource_force
*/

// print_r($argv);
$params['jenkins_or_cron']  = @$argv[1];
$params['task']             = @$argv[2];
$params['range_from']       = @$argv[3];
$params['range_to']         = @$argv[4];
$params['actual']           = @$argv[5];
print_r($params);

// /* main operation
$resource_id = 71; //Wikimedia Commons is EOL resource = 71 //historical commons.tar.gz also exists on Nov 2017

/* $func = new WikiDataAPI($resource_id, "en", "taxonomy"); //3rd param is boolean taxonomy; true means will generate hierarchy resource. [wikidata-hierarchy] */
$func = new WikiDataAPI($resource_id, "en", "wikimedia"); //Used for Commons - total taxa = 2,208,086

/* if you want to debug or test something...
$func->test(); exit;
*/

if(@$params['task'] == "create_all_taxon_dump") {
    $func->create_all_taxon_dump();     //step 1 (ran 1 connector)
}
elseif(@$params['task'] == "save_all_media_filenames") {
    $status = $func->save_all_media_filenames($params['task'], $params['range_from'], $params['range_to'], $params['actual']);  //step 2 (ran 6 connectors bec of lookup caching. Then ran 1 connector to finalize.)
    if($status) echo "\n---Can now proceed to next step...---\n\n";
    else        exit(1);
}
elseif(@$params['task'] == "create_then_fill_commons_data")                                     //step 3 (ran 1 connector)
{
    $func = new WikiDataAPI($resource_id, "");
    //these 2 functions are ran one after the other, preferably. This is to process a new WikiMedia dump
    $func->create_temp_files_based_on_wikimedia_filenames();     //create blank json files
    $func->fill_in_temp_files_with_wikimedia_dump_data();        //fill-in those blank json files
    echo("\n ==Finished preparing new WikiMedia dump== \n");
}
elseif(@$params['task'] == "generate_resource" || @$params['task'] == "generate_resource_force") { //step 4 (ran 6 connectors initially)
    /* orig when just 1 connector
    $func->generate_resource();
    Functions::finalize_dwca_resource($resource_id);
    */
    $status_arr = $func->generate_resource($params['task'], $params['range_from'], $params['range_to'], $params['actual']);  //step 4 (ran 6 connectors bec of lookup caching. Then ran 1 connector to finalize.)
    if($status_arr[0]) {
        echo "\n".$params['actual']." -- finished\n";
        if($status_arr[1]) {
            echo "\n---Can now proceed - finalize dwca...---\n\n";
            Functions::finalize_dwca_resource($resource_id, true, true); //true means big file, 2nd param true means to delete working folder
        }
        else echo "\nCannot finalize dwca yet.\n";
    }
    else exit(1);
}
// */

/* utility
require_library('connectors/DWCADiagnoseAPI');
$func = new DWCADiagnoseAPI();
$func->check_if_all_parents_have_entries($resource_id, true); //true means output will write to text file
*/

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";

/*
this is a request made by wikimedia harvester (71): this 2 are same, first is a subset of the 2nd. And one is urlencoded() other is not.
http://commons.wikimedia.org/w/api.php?action=query&format=json&prop=imageinfo|categories&iiprop=url|mime|mediatype&cllimit=500&redirects&titles=File%3AROSMARINUS+OFFICINALIS+-+MORROCURT+-+IB-976+(Roman%C3%AD).JPG
http://commons.wikimedia.org/w/api.php?action=query&format=json&prop=imageinfo%7Ccategories&iiprop=url%7Cmime%7Cmediatype&cllimit=500&redirects&titles=File%3AROSMARINUS+OFFICINALIS+-+MORROCURT+-+IB-976+%28Roman%C3%AD%29.JPG%7CFile%3ACapra+hircus+aegagrus-cropped.jpg%7CFile%3AEschrichtius+robustus+01-cropped.jpg%7CFile%3AMonachus+monachus+-+Museo+civico+di+storia+naturale+%28Milan%29-cropped.jpg%7CFile%3AMustela+putorius+01-cropped.jpg%7CFile%3ACondylactis+gigantea+%28giant+Caribbean+sea+anemone%29+closeup.jpg%7CFile%3A20140922-cephalopholis+hemistiktos.jpg%7CFile%3APelecanus+occidentalis+in+flight+at+Bodega+Bay.jpg%7CFile%3AUdotea+flabellum+%28mermaid%27s+fan+alga%29+Bahamas.jpg%7CFile%3ARhipocephalus+phoenix+%28pinecone+alga%29+%28San+Salvador+Island%2C+Bahamas%29.jpg%7CFile%3APadina+boergesenii+%28leafy+rolled-blade+algae+Bahamas.jpg%7CFile%3ABird%27s-nest+Fern+Asplenium+nidus+Leaves+2.jpg%7CFile%3ABird%27s-nest+Fern+Asplenium+nidus+Leaves+1.jpg%7CFile%3ABlack+Caterpillar+Fern+Scyphularia+pentaphylla+Leaves.JPG%7CFile%3ABlunt-lobed+Woodsia+obtusa+Winter+Foliage.JPG%7CFile%3AChinese+Elm+Ulmus+parvifolia+%2832-0052-A%29+Bark+1.JPG%7CFile%3AChinese+Elm+Ulmus+parvifolia+%2832-0052-A%29+Bark+2.JPG%7CFile%3AJapanese+Maple+Acer+palmatum+%282012-055-A%29+Trunk+Bark.JPG%7CFile%3AJapanese+Alder+Alnus+japonica+%2881-305-A%29+Base+Bark.JPG%7CFile%3AJapanese+Maple+Acer+palmatum+%282010-012-A%29+Bark+Closeup+1.JPG%7CFile%3AJapanese+Maple+Acer+palmatum+%282010-012-A%29+Bark+Closeup+2.JPG%7CFile%3AJapanese+Zelkova+serrata+%2881-502-L%29+Upper+Trunk+Bark.JPG%7CFile%3AMiyabe+Maple+Acer+miyabei+%2851-292-A%29+Trunk+Bark+Closeup.JPG%7CFile%3AJapanese+Zelkova+serrata+%2881-502-L%29+Lower+Trunk+Bark.JPG%7CFile%3AMiyabe+Maple+Acer+miyabei+%2851-292-A%29+Trunk+Bark.JPG%7CFile%3ARed+Maple+Acer+rubrum+%2832-0877-A%29+Trunk+Bark.JPG%7CFile%3AWhite+Ash+Fraxinus+americana+%2854-0751-A%29+Trunk+Bark.JPG%7CFile%3ANerita+versicolor+%28four-toothed+nerite+snails%29+Bahamas.jpg%7CFile%3ACenchritis+muricatus+%28beaded+periwinkle+snails%29+Bahamas.jpg%7CFile%3AMonodelphis+domestica+skeleton+-+ZooKeys+465-10.png%7CFile%3AMonodelphis+brevicaudata+skull+-+ZooKeys+465-08.png%7CFile%3APeradectes+molars.png%7CFile%3APediomys+molars.png%7CFile%3AScreen+on+the+supermarket+shelf+%282654814813%29.jpg%7CFile%3ASacpha+Hodgson.jpg%7CFile%3AA+Sulphur+Crested+White+Cockatoo+%28Cacatua+galerita%29%2C+Cronulla%2C+NSW+Australia.jpg%7CFile%3AGOCZA%C5%81KOWICE+ZDR%C3%93J%2C+AB.+071.JPG%7CFile%3AVexillum+ebenus+01.JPG%7CFile%3ASCANDIX+PECTEN-VENERIS+-+AGUDA+-+IB-049+%28Agulles+de+pastor%29.JPG%7CFile%3ASCANDIX+PECTEN-VENERIS+-+AGUDA+-+IB-069+%28Agulles+de+pastor%29.JPG%7CFile%3ACRATAEGUS+MONOGYNA+-+AGUDA+-+IB-082+%28Ar%C3%A7%29.JPG%7CFile%3ACRATAEGUS+MONOGYNA+-+AGUDA+-+IB-089+%28Ar%C3%A7%29.JPG%7CFile%3ATagetes+erecta+23122014+%284%29.jpg%7CFile%3ACalendula+officinalis+27122014+%286%29.jpg%7CFile%3AFlowers+of+Judas+tree.jpg%7CFile%3ASyntrichia+montana+%28a%2C+164114-475524%29+9259.JPG%7CFile%3ASyntrichia+montana+%28a%2C+164114-475524%29+9262.JPG%7CFile%3ASyntrichia+montana+%28a%2C+164114-475524%29+9263.JPG%7CFile%3ASyntrichia+montana+%28a%2C+164114-475524%29+9264.JPG%7CFile%3ASyntrichia+montana+%28a%2C+164114-475524%29+9265.JPG&continue=

http://commons.wikimedia.org/w/api.php?action=query&format=json&prop=imageinfo|categories&iiprop=url|mime|mediatype&cllimit=500&redirects&titles=File:Whales are Paraphyletic.png
*/
/* wikimedia stats:
commons	Sunday 2017-11-26 01:34:01 PM	{"agent.tab":19055,"media_resource.tab":909996,"taxon.tab":89054}
Statistics
    http://rs.tdwg.org/dwc/terms/taxon:
        Total: 73533
    http://eol.org/schema/agent/agent:
        Total: 17793
    http://eol.org/schema/media/document:
        Total by type:
            http://purl.org/dc/dcmitype/StillImage: 806464
            http://purl.org/dc/dcmitype/MovingImage: 6223
            http://purl.org/dc/dcmitype/Sound: 1610
        Total by license:
            http://creativecommons.org/licenses/publicdomain/: 192680
            http://creativecommons.org/licenses/by/3.0/: 495316
            http://creativecommons.org/licenses/by-sa/3.0/: 124755
            http://creativecommons.org/licenses/by-nc/3.0/: 731
            No known copyright restrictions: 804
            http://creativecommons.org/licenses/by-nc-sa/3.0/: 11
        Total by language:
            en: 814297
        Total by format:
            image/jpeg: 769547
            image/png: 18712
            image/svg+xml: 3954
            video/ogg: 4498
            image/tiff: 12955
            audio/ogg: 1581
            image/gif: 1296
            video/webm: 1725
            audio/x-wav: 29
        Total: 814297
*/
?>