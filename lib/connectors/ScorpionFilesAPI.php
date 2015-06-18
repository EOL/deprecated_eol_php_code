<?php
namespace php_active_record;
// connector: [scorpion]
class ScorpionFilesAPI
{
    function __construct()
    {
		$this->domain = "http://www.ntnu.no/ub/scorpion-files/";
        $this->family_list = $this->domain . "higher_phylogeny.php";
        $this->download_options = array("download_wait_time" => 1000000, "timeout" => 1800, "download_attempts" => 1, "delay_in_minutes" => 1);
		// $this->download_options['expire_seconds'] = false;
    }

    function get_all_taxa()
    {
		$families = self::get_families();
		$taxa = self::get_species_list($families);
		self::save_to_text_file($taxa);
    }

	private function get_families()
	{
		if($html = Functions::lookup_with_cache($this->family_list, $this->download_options))
		{
			$html = self::clean_html($html);
			$html = strip_tags(html_entity_decode($html), "<td><tr><a><img>"); //removes chars in between <!-- and --> except <td><tr><a><img>
			if(preg_match("/logo4.jpg(.*?)higher_phylogeny.php/ims", $html, $arr))
	        {
				if(preg_match_all("/<td>(.*?)<\/td>/ims", $arr[1], $arr2))
				{
					$families = array();
					foreach($arr2[1] as $temp)
					{
						$family = strip_tags($temp);
						$family = self::format_utf8(str_replace("- ", "", $family));
						if(preg_match("/href=\"(.*?)\"/ims", $temp, $arr3)) $families[$family] = $this->domain . $arr3[1];
					}
				}
			}
		}
		return $families;
	}

	private function get_species_list($families)
	{
		$taxa = array();
		foreach($families as $family => $url)
		{
			// $url = "http://www.ntnu.no/ub/scorpion-files/chactidae.php"; //debug

			echo "\n[$url]\n";
			if($html = Functions::lookup_with_cache($url, $this->download_options))
			{
				$article = self::parse_text_object($html);
				$authorship = self::get_family_authorship($family, $html);
				$family = trim($family . " $authorship");
				
				if(preg_match("/HER KOMMER SLEKTSTABELLENE(.*?)<\/TBODY>/ims", $html, $arr))
		        {
					if(preg_match_all("/<td(.*?)<\/td>/ims", $arr[1], $arr2))
					{
						foreach($arr2[1] as $block)
						{
							$block = "<td " . $block;
							$block = strip_tags(html_entity_decode($block), "<td><tr><a><img><tbody><em><strong><br><font>");
							$block = self::clean_html($block);
							
							$block = str_ireplace("<BR>", "<br>", $block);
							$raw = explode("<br>", $block);
							$line_items = self::process_line_items($raw, $url);
							$taxa[$family]['author'] = $authorship;
							$taxa[$family]['items'][] = $line_items;
							$taxa[$family]['text'] = $article;
						}
					}
				}
			}
			// print_r($taxa); // here just one family
		}
		// print_r($taxa); // here for all taxa
		return $taxa;
	}
	
	private function process_line_items($items, $url)
	{
		$items = array_filter($items); //remove null array
		$final = array();
		foreach($items as $item)
		{
			if(preg_match_all("/<font size=\"-2\">(.*?)<\/font>/ims", $item, $arr)) continue; //e.g. http://www.ntnu.no/ub/scorpion-files/buthidae.php - Buthoscorpio Werner, 1936
			if(preg_match_all("/<font size=\"1\">(.*?)<\/font>/ims", $item, $arr)) continue;
				
			$item = strip_tags($item, "<strong>");
			if(is_numeric(stripos($item, "strong")) && !self::is_nomen_dubium($item)) $genus = self::format_utf8(trim(strip_tags($item)));
			else
			{
				if(isset($genus))
				{
					if(!trim($item)) continue;
					$first_char = substr($genus, 0, 1).".";
					$species = Functions::canonical_form($genus) . " " . trim(str_replace($first_char, "", $item));
					$species = strip_tags($species);					
					if($species != Functions::canonical_form($genus) . " ") $final[$genus][] = self::format_utf8($species);					
				}
			}			
		}
		return $final;
	}
	
	private function format_utf8($str)
	{
		if(!Functions::is_utf8($str)) return utf8_encode($str);
		return $str;
	}
	
	private function process_line_items_v1($items, $url)
	{	
		$final = array();
		foreach($items as $item)
		{
			if(!is_numeric(stripos($item, ". ")) && !self::is_nomen_dubium($item)) $genus = trim(strip_tags($item));
			else
			{
				$first_char = substr($genus, 0, 1).".";
				$final[$genus][] = $genus . " " . trim(str_replace($first_char, "", $item));
			}
		}
		return $final;
	}

	private function is_nomen_dubium($str)
	{
		if(is_numeric(stripos($str, "nomen dubium"))) return true;
		if(is_numeric(stripos($str, "incertae sedis"))) return true;
		return false;
	}

	private function parse_text_object($html)
	{
		$html = self::clean_html($html);
		if($pos = stripos($html, "SPECIES FILES:"))
		{
			$i = $pos;
			for($x = $pos; $x >= 0; $x--)
			{
				$substr = substr($html, $x-1, 7);
				if($substr == "<TBODY>")
				{
					$start_pos = $x-1;
					break;
				}
			}
			$article = substr($html, $start_pos, $pos-$start_pos);
			$article = strip_tags($article, "<p><br><font><em>");			
			if(substr($article, -41) == "<P><FONT face=Arial color=#000000 size=4>") $article = substr($article, 0, strlen($article)-41);
			
			$article = str_ireplace(array("<p></p>"), "", $article);
			$article = trim($article);
			return $article;
		}
		return false;
	}
	
	private function get_family_authorship($family, $html)
	{
		$html = self::clean_html($html);
		if(preg_match("/" . $family . "<BR><\/FONT>(.*?)<\/FONT>/ims", $html, $arr)) return self::format_utf8(strip_tags($arr[1]));
		if(preg_match("/" . $family . "<BR> <\/FONT>(.*?)<\/FONT>/ims", $html, $arr)) return self::format_utf8(strip_tags($arr[1]));
		echo "\nnot found [$family]\n";
	}
	
	private function save_to_text_file($taxa)
	{
		//classification
		$filename = DOC_ROOT . "public/tmp/scorpion_classification.txt";
        $WRITE = fopen($filename, "w");
		fwrite($WRITE, "Order" . "\t" . "Family" . "\t" . "Genus" . "\t" . "Species" ."\n");
        foreach($taxa as $family => $rekords)
        {
			foreach($rekords['items'] as $rec)
			{
				foreach($rec as $genus => $species_list)
				{
					foreach($species_list as $species) fwrite($WRITE, "Scorpiones" . "\t" . $family . "\t" . $genus . "\t" . $species . "\n");			            
				}
			}
        }
        fclose($WRITE);
		self::convert_tab_to_xls($filename);

		//family and article
		$filename = DOC_ROOT . "public/tmp/scorpion_families.txt";
        $WRITE = fopen($filename, "w");
		fwrite($WRITE, "Family" . "\t" . "Article" ."\n");
        foreach($taxa as $family => $rekords)
        {
			fwrite($WRITE, $family . "\t" . $rekords['text'] . "\n");
        }
        fclose($WRITE);
		self::convert_tab_to_xls($filename);

	}

    public function convert_tab_to_xls($source)
    {
        require_once DOC_ROOT . '/vendor/PHPExcel/Classes/PHPExcel/IOFactory.php';
        $inputFileName = $source;
        $outputFileName = str_replace(".txt", ".xls", $inputFileName);
        // start conversion
        $objReader = \PHPExcel_IOFactory::createReader('CSV');
        // If the files uses a delimiter other than a comma (e.g. a tab), then tell the reader
        $objReader->setDelimiter("\t");
        // If the files uses an encoding other than UTF-8 or ASCII, then tell the reader
        // $objReader->setInputEncoding('UTF-16LE');
        /* other settings:
        $objReader->setEnclosure(" ");
        $objReader->setLineEnding($endrow);
        */
        $objPHPExcel = $objReader->load($inputFileName);
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save($outputFileName);
    }

	private function clean_html($html)
	{
		$html = str_ireplace(array("\n", "\r", "\t", "\o", "\xOB", "\11", "\011"), "", trim($html));
		return Functions::remove_whitespace($html);
	}
	
}
?>
