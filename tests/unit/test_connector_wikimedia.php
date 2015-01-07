<?php
namespace php_active_record;
require_vendor('wikipedia');

class test_connector_wikimedia extends SimpletestUnitBase
{

    static $Aves_include = <<<XML
  <page>
    <title>Template:Aves</title>
    <ns>10</ns>
    <id>13132279</id>
    <revision>
      <id>94127789</id>
      <parentid>78733152</parentid>
      <timestamp>2013-04-07T13:56:41Z</timestamp>
      <text xml:space="preserve">
{{TaxonavigationIncluded|Domain|Eukaryota|Regnum|Animalia|Phylum|Chordata|Subphylum|Vertebrata|Infraphylum|Gnathostomata|Superclassis|Tetrapoda|Classis|Aves|rank={{{rank|}}}|
categorizeFamiliesIn=Aves|categorizeGeneraIn=Aves|documentTemplate={{{documentTemplate|yes}}}|documentTemplateWithClassification=IOC|categorizeTemplate={{{categorizeTemplate|yes}}}
}}</text>
    </revision>
  </page>
XML;

    static $Angiosperms_include = <<<XML
  <page>
    <title>Template:Angiosperms</title>
    <ns>10</ns>
    <id>13862146</id>
    <revision>
      <id>123628829</id>
      <parentid>78733139</parentid>
      <timestamp>2014-05-10T12:13:25Z</timestamp>
      <text xml:space="preserve">{{TaxonavigationIncluded|Domain|Eukaryota|(unranked)|Archaeplastida|Regnum|Plantae|Cladus|angiosperms|rank={{{rank|}}}|
categorizeFamiliesIn=Plantae|documentTemplate={{{documentTemplate|yes}}}|documentTemplateWithClassification=APG III|categorizeTemplate={{{categorizeTemplate|yes}}}
}}</text>
    </revision>
  </page>
XML;

    function testMediaOnPage()
    {
        $p = new \WikimediaPage('<xml/>');
        $p->text = "
        <gallery>
        File:Boletus vermiculosoides 78531.jpg|[[:Category:Boletus vermiculosoides|''Boletus vermiculosoides'']]
        </gallery>
        ==Related species==
        <gallery>
        Image:Boletus impolitus 2009 G3.jpg|''[[Hemileccinum impolitum]]'' (Syn. ''Boletus impolitus'')
        </gallery>
        <gallery>
        Image:Boletus vermiculosus 348301 crop.jpg|[[:Category:Boletus vermiculosus|''Boletus vermiculosus'']]
        </gallery>";
        $media = $p->media_on_page();
        $this->assertTrue(count($media) == 2);
        $this->assertTrue(in_array('Boletus vermiculosoides 78531.jpg', $media));
        $this->assertTrue(in_array('Boletus vermiculosus 348301 crop.jpg', $media));
        $this->assertFalse(in_array('Image:Boletus impolitus 2009 G3.jpg', $media));
        $this->assertFalse(in_array('Boletus impolitus 2009 G3.jpg', $media));
    }

    function testGPSandFlickr()
    {
        $p = new \WikimediaPage('<xml/>');
        $p->title = "File:Portrait of a father.jpg";
        $p->text = "{{Flickr
|description={{de|[[w:de:Affen| Affenvater mit Sohn.]]}}
{{en|Father [[monkey]] and baby monkey. Gibraltar.}}
|flickr_url=http://flickr.com/photos/55648084@N00/336015450
|title=Portrait of a father
|taken=2006-12-26 17:30:36
|photographer=Karyn Sig
|photographer_url=http://flickr.com/photos/55648084@N00
|reviewer=dongio
|permission={{User:Flickr upload bot/upload|date=13:56, 21 September 2007 (UTC)|reviewer=Dongio}}
{{cc-by-2.0}}
}}
{{Location|36|7|57.8634|N|5|20|55.9962|W}}

[[Category:Macaca sylvanus]]";

    $author = $p->information();
    $this->assertTrue($author['author'] == 'Karyn Sig');
    $point = $p->point();
    $this->assertTrue(number_format($point['latitude'], 5) === "36.13274");
    $this->assertTrue(number_format($point['longitude'], 6) === "-5.348888");
    // We should probably check here that $p->get_data_object_parameters()['agents'][0]->role == 'photographer', and
    // $p->get_data_object_parameters()['agents'][0]->fullName == 'Karyn Sig' but that relies filling data_object_parameters with
    // the results of an online API query using something like $pages = array($p); \WikimediaPage::process_pages_using_API($pages);

    }


    function testUnicodeInFilenamesAndDescriptions()
    {
        $xml1 = <<<XML
  <page>
    <title>File:Żółty bratek.jpg</title>
    <ns>6</ns>
    <id>768224</id>
    <revision>
      <id>109419824</id>
      <parentid>62496366</parentid>
      <timestamp>2013-11-11T15:18:38Z</timestamp>
      <contributor>
        <username>Hazard-Bot</username>
        <id>2396226</id>
      </contributor>
      <minor />
      <comment>[[Commons:Bots|Bot]]: Applied fixes for [[Commons:Template i18n|internationalization support]]</comment>
      <text xml:space="preserve">== {{int:filedesc}} ==
{{Information|
|Description = {{pl|fiołek ogrodowy - bratek}}
               {{en|Pansy}}
|Source      = {{own}}
|Date        = 2006-04-15
|Author      = [[:pl:Wikipedysta:Aisog|Aisog]]
|Permission= / Creative Commons 2.5 Attribution
|other_versions = 
}}</text>
      <sha1>o8m7xztilhi61i3yk2fgcvxx6xr2ej6</sha1>
      <model>wikitext</model>
      <format>text/x-wiki</format>
    </revision>
  </page>
XML;

        $xml2 = <<<XML
  <page>
    <title>File:ആഫ്രിക്കൻ ഒച്ച്‌ (Achatina fulica) കേരളത്തിൽ (2012).JPG</title>
    <ns>6</ns>
    <id>20857037</id>
    <revision>
      <id>77402164</id>
      <parentid>76646663</parentid>
      <timestamp>2012-09-06T06:22:07Z</timestamp>
      <contributor>
        <username>Drajay1976</username>
        <id>2077850</id>
      </contributor>
      <minor />
      <comment>added [[Category:Uploads by Ajay]] using [[Help:Gadget-HotCat|HotCat]]</comment>
      <text xml:space="preserve">=={{int:filedesc}}==
{{Information
|description={{en|1=Giant African Snail (Achatina fulica) has spread to different parts of kerala and has become a pest. The photo was taken from Palluruthy, Kochi, Kerala. The snails had reached this place about 5 years earlier.}}
|date=2012-08-26
|source={{own}}
|author=[[User:Drajay1976|Drajay1976]]
|permission=
|other_versions=
|other_fields=
}}</text>
      <sha1>44v0txnedfh5gk011ki9syyvi8llcgd</sha1>
      <model>wikitext</model>
      <format>text/x-wiki</format>
    </revision>
  </page>

XML;

        $page1 = new \WikimediaPage($xml1);
        $page2 = new \WikimediaPage($xml2);
        //check capitalization doesn't mangle unicode filenames
        $this->assertTrue(\WikiParser::make_valid_pagetitle($page1->title) === $page1->title);
        $this->assertTrue(\WikiParser::make_valid_pagetitle($page2->title) === $page2->title);

        //check unicode makes it through to description field
        $this->assertTrue(Functions::is_utf8($page1->description()));
        $this->assertTrue(preg_match("/fiołek/u", $page1->description()));
    }

    function testRecursiveIncludesPlusSubspeciesVarietiesAndHybrids()
    {
         $include_xml = <<<XML
  <page>
    <title>Template:Orchidaceae (APG)</title>
    <ns>10</ns>
    <id>14618876</id>
    <revision>
      <id>105760723</id>
      <parentid>78475379</parentid>
      <timestamp>2013-09-29T16:24:22Z</timestamp>
      <contributor>
        <username>Liné1</username>
        <id>80857</id>
      </contributor>
      <comment>| mustBeEmpty={{{classification|}}}{{{genus|}}}}}</comment>
      <text xml:space="preserve">{{TaxonavigationIncluded2|
classification=APG III|include=Angiosperms|Cladus|monocots|Ordo|Asparagales|Familia|Orchidaceae|rank={{{rank|}}}|
categorizeSubtribesIn=Orchidaceae|&lt;!--categorizeSpeciesIn &amp; categorizeGeneraIn are subtily managed--&gt;categorizeTribesIn=Orchidaceae|
mustBeEmpty={{{classification|}}}{{{genus|}}}}}</text>
      <sha1>2r711pfbmrznvqwr6ntf4jlnlx8rua2</sha1>
      <model>wikitext</model>
      <format>text/x-wiki</format>
    </revision>
  </page>
XML;

        $p1 = new \WikimediaPage('<xml/>');
        $p1->text = "{{Taxonavigation|include=Orchidaceae (APG)|Subfamilia|Orchidoideae|Tribus|Orchideae|Subtribus|Orchidinae|
Nothospecies|Anacamptis × gennarii|
Nothosubspecies|Anacamptis × gennarii ssp bornemanniae|
authority=(Asch.) H.Kretzschmar, Eccarius & H.Dietr. (2007)}}";

        //this is a fake example, not many wikimedia entries are formatted like this, but we should be able to cope with them
        $p2 = new \WikimediaPage('<xml/>');
        $p2->text = "{{Taxonavigation|include=Orchidaceae (APG)|Subfamilia|Orchidoideae|Tribus|Orchideae|Subtribus|Orchidinae|
Nothogenus|× Anacamptis|
Nothospecies|gennarii|
Nothovarietas|dummy|}}";

        $dummy_resource = self::create_resource();
        $dummy_harvester = new WikimediaHarvester($dummy_resource);
        $dummy_harvester->locate_taxonomic_pages(self::$Angiosperms_include);
        $dummy_harvester->locate_taxonomic_pages($include_xml);
        $taxonomy1 = $p1->taxonomy($dummy_harvester->taxonav_includes);
        $taxonomy2 = $p2->taxonomy($dummy_harvester->taxonav_includes);

        //test whether recursive includes have managed to find the kingdom name
        $asTaxonObj = $taxonomy1->asEoLtaxonObject();
        $this->assertTrue($asTaxonObj["kingdom"] == "Plantae");

        //test if we have managed to reconstruct the genus name from the species name
        $this->assertTrue($asTaxonObj["genus"] == "Anacamptis");

        //test whether the scientific name is properly formed, e.g. ssp replaced with subsp.
        $scientificName1 = $taxonomy1->scientificName();
        $this->assertTrue($scientificName1 === html_entity_decode("Anacamptis &times;&nbsp;gennarii subsp. bornemanniae (Asch.) H.Kretzschmar, Eccarius & H.Dietr. (2007)"));
        $scientificName2 = $taxonomy2->scientificName();
        $this->assertTrue($scientificName2 === html_entity_decode("&times;&nbsp;Anacamptis gennarii var. dummy"));
    }

    function testTrancludedCategoriesAndGalleries()
    {
         $include_xml = <<<XML
  <page>
    <title>Template:Accipitridae (IOC)</title>
    <ns>10</ns>
    <id>21271380</id>
    <revision>
      <id>105766220</id>
      <parentid>78556711</parentid>
      <timestamp>2013-09-29T17:14:42Z</timestamp>
      <text xml:space="preserve">{{TaxonavigationIncluded2|classification=IOC|include=Aves|Superordo|Neognathae|Ordo|Accipitriformes|Familia|Accipitridae|rank={{{rank|}}}|
categorizeSpeciesIn=Accipitridae|mustBeEmpty={{{classification|}}}{{{genus|}}}}}</text>
    </revision>
  </page>
XML;

        $main_cat = <<<XML
  <page>
    <title>Category:Milvus milvus</title>
    <ns>14</ns>
    <id>141095</id>
    <revision>
      <id>112065573</id>
      <parentid>100883335</parentid>
      <timestamp>2013-12-18T05:24:27Z</timestamp>
      <text xml:space="preserve">&lt;onlyinclude&gt;{{Taxonavigation|
include=Accipitridae (IOC)|
Genus|Milvus|
Species|Milvus milvus|
authority=(Linnaeus, 1758)}}&lt;/onlyinclude&gt;
{{wikispecies|Milvus milvus}}
{{GeoGroupTemplate}}</text>
    </revision>
  </page>
XML;
        $sub_cat = <<<XML
  <page>
    <title>Category:Milvus milvus in flight</title>
    <ns>14</ns>
    <id>141096</id>
    <revision>
      <id>112065574</id>
      <parentid>100883335</parentid>
      <timestamp>2013-12-18T05:24:27Z</timestamp>
      <text xml:space="preserve">{{Category:milvus_milvus}}
[[Category:Milvus milvus]]
[[Category:Milvus in flight]]</text>
    </revision>
  </page>
XML;

        $dummy_resource = self::create_resource();
        $dummy_harvester = new WikimediaHarvester($dummy_resource);
        $dummy_harvester->locate_taxonomic_pages(self::$Aves_include);
        $dummy_harvester->locate_taxonomic_pages($include_xml);
        $dummy_harvester->locate_taxonomic_pages($main_cat);

        $dummy_harvester->check_taxonomy_and_redirects($main_cat);
        $dummy_harvester->check_taxonomy_and_redirects($sub_cat);

        $mainpage = new \WikimediaPage($main_cat);
        $subpage = new \WikimediaPage($sub_cat);

        //check that we correctly get transcluded categories from the subpage
        $this->assertTrue(in_array($mainpage->title, $subpage->transcluded_categories()));

        //check that we have a taxonomy for the subpage, even though subpage didn't have a Taxonav
        $this->assertTrue(array_key_exists($subpage->title, $dummy_harvester->taxa));

        //check it is the correct taxonomy!
        $this->assertTrue($dummy_harvester->taxa[$subpage->title]->scientificName() === "Milvus milvus (Linnaeus, 1758)");
    }

    function testTaxonomyConflict()
    {
        /* Here we test the parsing of Taxonavigation and TaxonavigationIncluded templates, both for categories and galleries.
           This requires a number of different Wikimedia pages to be parsed */

        $include_xml = <<<XML
  <page>
    <title>Template:Emberizidae (IOC)</title>
    <ns>10</ns>
    <id>21367926</id>
    <revision>
      <id>105833652</id>
      <parentid>78557785</parentid>
      <timestamp>2013-09-30T06:18:45Z</timestamp>
      <contributor>
        <username>Liné1</username>
        <id>80857</id>
      </contributor>
      <comment>| mustBeEmpty={{{classification|}}}{{{genus|}}}}}</comment>
      <text xml:space="preserve">{{TaxonavigationIncluded2|
classification=IOC|
include=Aves|
Superordo|Neognathae|
Ordo|Passeriformes|
Subordo|Passeri|
Superfamilia|Passeroidea|
Familia|Emberizidae|
rank={{{rank|}}}|
categorizeSpeciesIn=Emberizidae|
mustBeEmpty={{{classification|}}}{{{genus|}}}}}</text>
      <sha1>kxqqa77vubx6rq0v35kseyp9k7uo9cl</sha1>
      <model>wikitext</model>
      <format>text/x-wiki</format>
    </revision>
  </page>
XML;

        $category_xml = <<<XML
 <page>
    <title>Category:Spizella passerina</title>
    <ns>14</ns>
    <id>3822294</id>
    <revision>
      <id>111910096</id>
      <parentid>83162075</parentid>
      <timestamp>2013-12-15T19:40:24Z</timestamp>
      <contributor>
        <username>Liné1bot</username>
        <id>1402352</id>
      </contributor>
      <comment>updated IUCN</comment>
      <text xml:space="preserve">{{Taxonavigation|
include=Emberizidae (IOC)|
Genus|Spizella|
Species|Spizella passerina|
authority=([[Johann Matthäus Bechstein|Bechstein]], 1798)}}</text>
      <sha1>m9n7iyv5hjndumchkspmpy4gvllbhc2</sha1>
      <model>wikitext</model>
      <format>text/x-wiki</format>
    </revision>
  </page>
XML;

        $gallery_xml = <<<XML
  <page>
    <title>Passeriformes</title>
    <ns>0</ns>
    <id>352800</id>
    <revision>
      <id>104523666</id>
      <parentid>103229276</parentid>
      <timestamp>2013-09-17T12:10:30Z</timestamp>
      <contributor>
        <username>Kersti Nebelsiek</username>
        <id>103627</id>
      </contributor>
      <text xml:space="preserve">{{Taxonavigation|
classification=IOC|
include=Aves|
Superordo|Neognathae|
Ordo|Passeriformes|
authority=Linnaeus, 1758}}
==== E ====
&lt;gallery&gt;
File:Chipping Sparrow (Spizella passerina) RWD.jpg|''[[Emberizidae]]'' ([[:Category:Emberizidae|cat.]])
File:Chestnut breasted mannikin nov08.jpg|''[[Estrildidae]]'' ([[:Category:Estrildidae|cat.]])
File:Eupetes macrocerus 1838.jpg|''[[Eupetidae]]'' ([[:Category:Eupetidae|cat.]])
File:Corydon sumatranus 1.jpg|''[[Eurylaimidae]]'' ([[:Category:Eurylaimidae|cat.]])
&lt;/gallery&gt;</text>
      <sha1>5dr1jf3m209o5p32r6qmbd7th61sd95</sha1>
      <model>wikitext</model>
      <format>text/x-wiki</format>
    </revision>
  </page>
XML;

        $dummy_resource = self::create_resource();
        $dummy_harvester = new WikimediaHarvester($dummy_resource);

        $dummy_harvester->locate_taxonomic_pages(self::$Aves_include);
        $dummy_harvester->locate_taxonomic_pages($include_xml);
        $dummy_harvester->locate_taxonomic_pages($category_xml);
        $dummy_harvester->locate_taxonomic_pages($gallery_xml);

        $dummy_harvester->check_taxonomy_and_redirects($gallery_xml);
        $dummy_harvester->check_taxonomy_and_redirects($category_xml);

        //Gallery page is for the order Passeriformes
        $this->assertTrue(array_key_exists("Passeriformes", $dummy_harvester->taxa));
        //Category page is for the species _Spizella passerina_
        $this->assertTrue(array_key_exists("Category:Spizella passerina", $dummy_harvester->taxa));

        //Check how EoL forms scientific names
        $this->assertTrue($dummy_harvester->taxa["Passeriformes"]->scientificName() === "Passeriformes Linnaeus, 1758");
        $this->assertTrue($dummy_harvester->taxa["Category:Spizella passerina"]->scientificName() === "Spizella passerina (Bechstein, 1798)");

        //Test merging them together, so that e.g. in the case of File:Chipping Sparrow (Spizella passerina) RWD.jpg we only get a single taxonomy
        $names = array_keys($dummy_harvester->taxa);
        $dummy_harvester->remove_duplicate_taxonomies($names);
        //check that we have deleted Passeriformes (as it is a taxonomic subset of Category:Spizella passerina)
        foreach($names as $name) $this->assertFalse($name === "Passeriformes");

    }

    private static function create_resource($args = array())
    {
        // create the user
        $agent = Agent::find_or_create(array('full_name' => 'Test Content Partner'));
        $user = User::find_or_create(array('display_name' => 'Test Content Partner', 'agent_id' => $agent->id));
        // create the partner
        $content_partner = ContentPartner::find_or_create(array('user_id' => $user->id));
        $hierarchy = Hierarchy::find_or_create(array('agent_id' => $agent->id, 'label' => 'Test Content Partner Hierarchy'));
        // create the resource
        $attr = array(  'content_partner_id'    => $content_partner->id,
                        'service_type'          => ServiceType::find_or_create_by_translated_label('EOL Transfer Schema'),
                        'refresh_period_hours'  => 1,
                        'hierarchy_id'          => $hierarchy->id,
                        'resource_status'       => ResourceStatus::validated());
        $resource = Resource::find_or_create($attr);
        return $resource;
    }
}
?>
