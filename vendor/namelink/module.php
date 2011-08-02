<?php

require_once(dirname(__FILE__) ."/TaxonFinderClient.php");
require_once(dirname(__FILE__) ."/NameLink.php");
require_once(dirname(__FILE__) ."/NameTag.php");
require_once(dirname(__FILE__) ."/NewNamesFinder.php");

/* TaxonFinder */
define("TAXONFINDER_SOCKET_SERVER", "127.0.0.1");
define("TAXONFINDER_SOCKET_PORT",   "1234");
define("TAXONFINDER_STOP_KEYWORD",  "asdfib3r234");















if(!defined('UPPER')) define("UPPER","A-ZÀÂÅÅÃÄÁÆČÇÉÈÊËÍÌÎÏÑÓÒÔØÕÖÚÙÛÜßĶŘŠŞŽŒ");
if(!defined('LOWER')) define("LOWER","a-záááàâåãäăæčćçéèêëĕíìîïǐĭñńóòôøõöŏúùûüůśšşřğžźýýÿœœ");


function canonical_form($string)
{
    sci_parts();
    author_parts();
    junk_parts();
    
    if(preg_match("/^X (.*)$/",$string,$arr)) $string = $arr[1];
    $string = str_replace(" tipo veneto","",$string);
    $string = str_replace("×"," ",$string);
    $words = preg_split("/[[:space:]]+/",trim($string));
    $num = count($words);
    if(preg_match("/^\??\"?\[?\(?([^\"\[\]\(\)]*)\)?\]?\"?$/",$words[0],$arr)) $words[0] = $arr[1];
    if(preg_match("/^(.*)\?$/",$words[0],$arr)) $words[0] = $arr[1];
    if($words[0]=="Not") return "";
    $words[0] = str_replace("[","",$words[0]);
    $words[0] = str_replace("]","",$words[0]);
    $words[0] = preg_replace("/{\?}/","",$words[0]);
    $words[0] = preg_replace("/\{[0-9\. ]*\}/","",$words[0]);
    if(preg_match("/^[^".UPPER.LOWER."]*([".UPPER.LOWER."]*)[^".UPPER.LOWER."]*$/u",$words[0],$arr)) $words[0] = $arr[1];
    $words[0] = str_replace("[","",$words[0]);
    $words[0] = str_replace("]","",$words[0]);
    $return_string = $words[0];
    if(@preg_match("/^([".LOWER."].*)\)$/u",$words[1],$arr))
    {
        $words[1] = $arr[1];
        if(preg_match("/^(.*)\?$/",$words[1],$arr)) $words[1] = $arr[1];
        if(preg_match("/^[^".UPPER.LOWER."]*([".UPPER.LOWER."]*)[^".UPPER.LOWER."]*$/u",$words[1],$arr)) $words[1] = $arr[1];
        $return_string.=" $words[1]";
        return $return_string;
    }
    
    for($i=1 ; $i<$num ; $i++)
    {
        if(preg_match("/^[".UPPER."\(]/u",$words[$i]) || (preg_match("/[0-9]/",$words[$i])&&!preg_match("/^[1-2]?[0-9]?\-?[".LOWER."]+$/u",$words[$i]))) continue;
        if(preg_match("/^[^0-9".UPPER.LOWER."]*([0-9".UPPER.LOWER."]*)[^0-9".UPPER.LOWER."]*$/u",$words[$i],$arr)) $words[$i] = $arr[1];
        if(preg_match("/[".UPPER."]/u",$words[$i])||preg_match("/\[/u",$words[$i])||
        preg_match("/.\../u",$words[$i])||!preg_match("/[[:alpha:]]/u",$words[$i])) continue;
        
        if(@$GLOBALS["SCI_PARTS"][$words[$i]] || @$GLOBALS["AUTHOR_PARTS"][$words[$i]] || @$GLOBALS["JUNK_PARTS"][$words[$i]]) continue;
        
        if(strlen($words[$i])>1) $return_string.=" $words[$i]";
    }
    
    unset($string);
    unset($words);
    return trim($return_string);
}

function author_parts()
{
    if(@!$GLOBALS["AUTHOR_PARTS"])
    {
        $array = array();
        $array["d'"]=true;
        $array["der"]=true;
        $array["du"]=true;
        $array["den"]=true;
        $array["le"]=true;
        $array["la"]=true;
        $array["de"]=true;
        $array["da"]=true;
        $array["del"]=true;
        $array["delle"]=true;
        $array["della"]=true;
        $array["des"]=true;
        $array["van"]=true;
        $array["von"]=true;
        $array["y"]=true;
        
        $GLOBALS["AUTHOR_PARTS"] = $array;
    }
}

function sci_parts()
{
    if(@!$GLOBALS["SCI_PARTS"])
    {
        $array = array();
        $array["unranked"]=true;
        $array["susbsp"]=true;
        $array["lus"]=true;
        $array["sf"]=true;
        $array["subv"]=true;
        $array["susp"]=true;
        $array["pseudosp"]=true;
        $array["subvariety"]=true;
        $array["variety"]=true;
        $array["subspecies"]=true;
        $array["subgroup"]=true;
        $array["group"]=true;
        $array["subfam"]=true;
        $array["spp"]=true;
        $array["convar"]=true;
        $array["forma"]=true;
        $array["fo"]=true;
        $array["form"]=true;
        $array["subforma"]=true;
        $array["subgen"]=true;
        $array["subg"]=true;
        $array["subf"]=true;
        $array["subvar"]=true;
        $array["nothovar"]=true;
        $array["nothosubsp"]=true;
        $array["variant"]=true;
        $array["var"]=true;
        $array["subsp"]=true;
        $array["sp"]=true;
        $array["ssp"]=true;
        $array["subgenus"]=true;
        $array["group"]=true;
        $array["species"]=true;
        $array["generic"]=true;
        $array["genus"]=true;
        $array["genera"]=true;
        $array["complex"]=true;
        $array["section"]=true;
        $array["genus"]=true;
        $array["morph"]=true;
        $array["mstr"]=true;
        $array["notho"]=true;
        $array["chr"]=true;
        $array["mutation"]=true;
        $array["mutatio"]=true;
        $array["biogroup"]=true;
        $array["sec"]=true;
        $array["lato"]=true;
        $array["juvenile"]=true;
        $array["variété"]=true;
        $array["holotype"]=true;
        $array["cross"]=true;
        $array["f"]=true;
        $array["x"]=true;
        
        $GLOBALS["SCI_PARTS"] = $array;
    }
}

function sci_parts1()
{
    if(@!$GLOBALS["SCI_PARTS1"])
    {
        $array = array();
        $array["unranked"]=true;
        $array["susbsp"]=true;
        $array["lus"]=true;
        $array["sf"]=true;
        $array["subv"]=true;
        $array["susp"]=true;
        $array["pseudosp"]=true;
        $array["subvariety"]=true;
        $array["variety"]=true;
        $array["subspecies"]=true;
        $array["subgroup"]=true;
        $array["group"]=true;
        $array["subfam"]=true;
        $array["spp"]=true;
        $array["convar"]=true;
        $array["fo"]=true;
        $array["form"]=true;
        $array["subforma"]=true;
        $array["subgen"]=true;
        $array["subg"]=true;
        $array["subf"]=true;
        $array["nothovar"]=true;
        $array["nothosubsp"]=true;
        $array["variant"]=true;
        $array["ssp"]=true;
        $array["subgenus"]=true;
        $array["group"]=true;
        $array["species"]=true;
        $array["generic"]=true;
        $array["genus"]=true;
        $array["genera"]=true;
        $array["complex"]=true;
        $array["section"]=true;
        $array["genus"]=true;
        $array["morph"]=true;
        $array["mstr"]=true;
        $array["notho"]=true;
        $array["chr"]=true;
        $array["mutation"]=true;
        $array["mutatio"]=true;
        $array["biogroup"]=true;
        $array["sec"]=true;
        $array["lato"]=true;
        $array["juvenile"]=true;
        $array["variété"]=true;
        $array["holotype"]=true;
        $array["cross"]=true;
        
        $GLOBALS["SCI_PARTS1"] = $array;
    }
}

function sci_parts2()
{
    if(@!$GLOBALS["SCI_PARTS2"])
    {
        $array = array();
        $array["var"]=true;
        $array["subsp"]=true;
        $array["sp"]=true;
        $array["forma"]=true;
        $array["f"]=true;
        $array["x"]=true;
        $array["subvar"]=true;
        
        $GLOBALS["SCI_PARTS2"] = $array;
    }
}

function junk_parts()
{
    if(@!$GLOBALS["JUNK_PARTS"])
    {
        $array = array();
        $array["cultiv"]=true;
        $array["enrichment"]=true;
        $array["culture"]=true;
        $array["clone"]=true;
        $array["str"]=true;
        $array["doubtful"]=true;
        $array["dubious"]=true;
        $array["emended"]=true;
        $array["com"]=true;
        $array["auth"]=true;
        $array["sens"]=true;
        $array["partim"]=true;
        $array["fi"]=true;
        $array["indicated"]=true;
        $array["lat"]=true;
        $array["id"]=true;
        $array["ab"]=true;
        $array["loc"]=true;
        $array["and"]=true;
        $array["&"]=true;
        $array["&amp;"]=true;
        $array["corrig"]=true;
        $array["pv"]=true;
        $array["mult"]=true;
        $array["cv"]=true;
        $array["inval"]=true;
        $array["aff"]=true;
        $array["ambig"]=true;
        $array["anon"]=true;
        $array["orth"]=true;
        $array["hyb"]=true;
        $array["gen"]=true;
        $array["nomen"]=true;
        $array["invalid"]=true;
        $array["prep"]=true;
        $array["dela"]=true;
        $array["press"]=true;
        $array["illeg"]=true;
        $array["ssel"]=true;
        $array["hl"]=true;
        $array["ll"]=true;
        $array["super"]=true;
        $array["pro"]=true;
        $array["hybr"]=true;
        $array["plur"]=true;
        $array["nk"]=true;
        $array["as"]=true;
        $array["to"]=true;
        $array["type"]=true;
        $array["nud"]=true;
        $array["et"]=true;
        $array["al"]=true;
        $array["accord"]=true;
        $array["according"]=true;
        $array["orthographic"]=true;
        $array["emend"]=true;
        $array["of"]=true;
        $array["authors"]=true;
        $array["nom"]=true;
        $array["comb"]=true;
        $array["nov"]=true;
        $array["ined"]=true;
        $array["cons"]=true;
        $array["sensu"]=true;
        $array["hort"]=true;
        $array["p.p"]=true;
        $array["not"]=true;
        $array["strain"]=true;
        $array["cf"]=true;
        $array["status"]=true;
        $array["unclear"]=true;
        $array["fide"]=true;
        $array["see"]=true;
        $array["comment"]=true;
        $array["bis"]=true;
        $array["specified"]=true;
        $array["be"]=true;
        $array["filled"]=true;
        $array["fil"]=true;
        $array["questionable"]=true;
        $array["the"]=true;
        $array["arid"]=true;
        $array["acc"]=true;
        $array["region"]=true;
        $array["eul"]=true;
        $array["ms"]=true;
        $array["beauv"]=true;
        $array["prop"]=true;
        $array["nm"]=true;
        $array["fort"]=true;
        $array["mut"]=true;
        $array["stat"]=true;
        $array["plants"]=true;
        $array["nec"]=true;
        $array["given"]=true;
        $array["cited"]=true;
        $array["typ"]=true;
        $array["ign"]=true;
        $array["often"]=true;
        $array["referred"]=true;
        $array["superfl"]=true;
        $array["parte"]=true;
        $array["plants"]=true;
        $array["pl"]=true;
        $array["fig"]=true;
        $array["no"]=true;
        $array["prelo"]=true;
        $array["maly"]=true;
        $array["schneider"]=true;
        $array["apud"]=true;
        $array["sine"]=true;
        $array["typo"]=true;
        $array["abbreviation"]=true;
        $array["recorded"]=true;
        $array["label"]=true;
        $array["on"]=true;
        $array["hybridized"]=true;
        $array["with"]=true;
        $array["unspecified"]=true;
        $array["rke"]=true;
        $array["illegible"]=true;
        $array["biotype"]=true;
        $array["race"]=true;
        $array["biotype"]=true;
        $array["vag"]=true;
        $array["tax"]=true;
        $array["x"]=true;
        $array["west"]=true;
        $array["auctor"]=true;
        $array["toni"]=true;
        $array["assigned"]=true;
        $array["sect"]=true;
        $array["subsect"]=true;
        $array["series"]=true;
        $array["ser"]=true;
        $array["typus"]=true;
        $array["dos"]=true;
        $array["rn"]=true;
        $array["editor"]=true;
        $array["di"]=true;
        $array["list"]=true;
        $array["pl"]=true;
        $array["applicable"]=true;
        $array["undet"]=true;
        $array["species"]=true;
        $array["col"]=true;
        $array["area"]=true;
        $array["op"]=true;
        $array["cit"]=true;
        $array["ey"]=true;
        $array["zu"]=true;
        $array["und"]=true;
        $array["name"]=true;
        $array["only"]=true;
        $array["excl"]=true;
        $array["syn"]=true;
        $array["or"]=true;
        $array["also"]=true;
        $array["by"]=true;
        $array["ex"]=true;
        $array["in"]=true;
        $array["auct"]=true;
        $array["non"]=true;
        $array["date"]=true;
        $array["inter"]=true;
        $array["before"]=true;
        $array["vel"]=true;
        $array["sep"]=true;
        $array["nat"]=true;
        $array["bekannt"]=true;
        $array["ter"]=true;
        $array["É"]=true;
        $array["nr"]=true;
        $array["aberr"]=true;
        $array["nr"]=true;
        $array["between"]=true;
        $array["rus"]=true;
        $array["ent"]=true;
        $array["synanamorph"]=true;
        $array["anamorph"]=true;
        $array["zur"]=true;
        $array["ul"]=true;
        $array["lu"]=true;
        $array["circa"]=true;
        $array["pls"]=true;
        $array["ante"]=true;
        $array["testa"]=true;
        $array["prior"]=true;
        $array["generic"]=true;
        $array["post"]=true;
        $array["etc"]=true;
        $array["binom"]=true;
        //$array["do"]=true;
        $array["nex"]=true;
        $array["auctt"]=true;
        $array["stricto"]=true;
        $array["das"]=true;
        $array["dates"]=true;
        $array["from"]=true;
        $array["doubtful"]=true;
        $array["dubious"]=true;
        $array["emended"]=true;
        $array["com"]=true;
        $array["partim"]=true;
        $array["fi"]=true;
        $array["indicated"]=true;
        $array["lat"]=true;
        $array["ii"]=true;
        $array["ry"]=true;
        $array["ndez"]=true;
        $array["lez"]=true;
        $array["lc"]=true;
        $array["rskov"]=true;
        $array["nudum"]=true;
        $array["sbsp"]=true;
        $array["morpha"]=true;
        $array["esp"]=true;
        $array["mph"]=true;
        $array["s-sp"]=true;
        $array["subs"]=true;
        $array["variété"]=true;
        $array["forme"]=true;
        $array["subspec"]=true;
        $array["sous-type"]=true;
        $array["inte"]=true;
        $array["subspp"]=true;
        $array["indet"]=true;
        $array["corrected"]=true;
        $array["none"]=true;
        $array["iber"]=true;
        $array["eur"]=true;
        $array["balcan"]=true;
        $array["nonn"]=true;
        $array["fl"]=true;
        $array["cauc"]=true;
        $array["armen"]=true;
        $array["inc"]=true;
        $array["orient"]=true;
        $array["ross"]=true;
        $array["med"]=true;
        $array["germ"]=true;
        $array["boreal"]=true;
        $array["boruss"]=true;
        $array["amer"]=true;
        $array["prol"]=true;
        $array["ca"]=true;
        $array["but"]=true;
        $array["misapplied"]=true;
        $array["subst"]=true;
        $array["for"]=true;
        $array["int"]=true;
        $array["several"]=true;
        $array["error"]=true;
        $array["pers"]=true;
        $array["comm"]=true;
        $array["nudum"]=true;
        $array["errore"]=true;
        $array["incertae"]=true;
        $array["sedis"]=true;
        $array["sic"]=true;
        $array["substit"]=true;
        $array["web"]=true;
        $array["site"]=true;
        $array["viii"]=true;
        $array["oblit"]=true;
        $array["new"]=true;
        $array["xxxx"]=true;
        $array["an"]=true;
        $array["objective"]=true;
        $array["synonym"]=true;
        $array["now"]=true;
        $array["bottom"]=true;
        $array["both"]=true;
        $array["pictures"]=true;
        $array["synonymy"]=true;
        $array["uncertain"]=true;
        $array["substit"]=true;
        $array["under"]=true;
        $array["inc"]=true;
        $array["sed"]=true;
        $array["spelling"]=true;
        $array["brit"]=true;
        $array["irj"]=true;
        $array["mf"]=true;
        $array["subfo"]=true;
        $array["sport"]=true;
        $array["tribe"]=true;
        $array["subtribe"]=true;
        $array["subser"]=true;
        $array["subtrib"]=true;
        $array["trib"]=true;
        $array["sebsp"]=true;
        $array["lusus"]=true;
        $array["sub"]=true;
        $array["gr"]=true;
        $array["oblvar"]=true;
        $array["nra"]=true;
        $array["fam"]=true;
        $array["en"]=true;
        $array["mey"]=true;
        $array["susbp"]=true;
        $array["sre"]=true;
        $array["subtr"]=true;
        $array["subdiv"]=true;
        $array["pars"]=true;
        $array["quad"]=true;
        $array["typum"]=true;
        $array["set"]=true;
        $array["rouy"]=true;
        $array["opiz"]=true;
        $array["agsp"]=true;
        $array["ourk"]=true;
        //$array["proles"]=true;
        $array["liu"]=true;
        $array["ecad"]=true;
        $array["substirps"]=true;
        $array["groupa"]=true;
        $array["groupe"]=true;
        $array["divis"]=true;
        $array["nothosect"]=true;
        $array["nothomorph"]=true;
        $array["em"]=true;
        $array["nsubsp"]=true;
        $array["monstr"]=true;
        $array["rev"]=true;
        $array["basionym"]=true;
        $array["quoad"]=true;
        $array["ven"]=true;
        $array["order"]=true;
        $array["mon"]=true;
        $array["superord"]=true;
        $array["ord"]=true;
        $array["subvars"]=true;
        $array["cm"]=true;
        $array["supertrib"]=true;
        $array["mnstr"]=true;
        $array["ren"]=true;
        $array["subset"]=true;
        $array["subtribus"]=true;
        $array["agg"]=true;
        $array["jr"]=true;
        $array["nothof"]=true;
        $array["nothogen"]=true;
        $array["nothosubgen"]=true;
        $array["individual"]=true;
        $array["index"]=true;
        $array["supsp"]=true;
        $array["attr"]=true;
        $array["incorrectly"]=true;
        $array["ined;cf"]=true;
        $array["el"]=true;
        $array["various"]=true;
        $array["cultivars"]=true;
        $array["af"]=true;
        $array["valide"]=true;
        $array["publ"]=true;
        $array["class"]=true;
        $array["sufam"]=true;
        $array["xx"]=true;
        $array["xxx"]=true;
        $array["xxxx"]=true;
        $array["quib"]=true;
        $array["ap"]=true;
        $array["subap"]=true;
        $array["grupo"]=true;
        $array["gruppe"]=true;
        $array["oec"]=true;
        $array["prole"]=true;
        $array["nothsect"]=true;
        $array["nssp"]=true;
        $array["nopthosubsp"]=true;
        $array["jun"]=true;
        $array["rx"]=true;
        $array["like"]=true;
        $array["ascribed"]=true;
        $array["included"]=true;
        $array["rejected"]=true;
        $array["segregates"]=true;
        $array["ngstr"]=true;
        $array["nothosubg"]=true;
        $array["subclassis"]=true;
        $array["eds"]=true;
        $array["spec"]=true;
        $array["ty"]=true;
        $array["ed"]=true;
        $array["herb"]=true;
        
        $GLOBALS["JUNK_PARTS"] = $array;
    }
}


?>