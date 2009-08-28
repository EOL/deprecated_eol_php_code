<?
	// The data we will be serving (normally your would connect to a database for example)
	$data = array();
	$data[0] = array(
		1 => array("type"=>"root", "name"=>array("bg"=>"Начало","en"=>"Home","de"=>"Hauptkategorie"), "icon"=>"images/root.png")
	);
	$data[1] = array(
		2 => array("type"=>"folder", "name"=>array("bg"=>"Снимки","en"=>"Pictures","de"=>"Fotos"), "data"=>"{ valid_children : ['picture'] }"),
		3 => array("type"=>"folder", "name"=>array("bg"=>"Музика","en"=>"Music","de"=>"Muzik"), "data"=>"{ valid_children : ['album'] }"),
		4 => array("type"=>"folder", "name"=>array("bg"=>"Документи","en"=>"Documents","de"=>"Fotos")),
		2 => array("type"=>"folder", "name"=>array("bg"=>"Снимки","en"=>"Pictures","de"=>"Fotos"))
	);

	// Make sure nothing is cached
	header("Cache-Control: must-revalidate");
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");
	header("Expires: ".gmdate("D, d M Y H:i:s", mktime(date("H")-2, date("i"), date("s"), date("m"), date("d"), date("Y")))." GMT");
	header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
	// The id of the node being opened
	$id = preg_replace("/.*_/","",$_GET["id"]);
	echo "[\n";
	$nodes = ($id == "0") ? 1 : rand(1,5);
	for($i = 1; $i <= $nodes; $i++) {
		if($i > 1) echo ",\n";
		echo "\t{\n";
		echo "\t\tattributes: { id : '".$_GET["id"]."_".$i."' },\n";
		echo "\t\tstate: '".( ($id == 0 || rand(0,100)%2 == 0) ? "closed" : "" )."',\n";
		echo "\t\tdata: { 'en' : 'Node ".$_GET["id"]."_".$i."', 'bg' : 'Клон ".$_GET["id"]."_".$i."' }\n";
		echo "\t}";
	}
	echo "\n]";
	exit();
?>