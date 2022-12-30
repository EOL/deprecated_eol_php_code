<?php
namespace php_active_record;
class HTML2MediaWikiAPI_StudCont
{
    public function __construct()
    {
        $this->root = str_replace('eol_php_code/', '', DOC_ROOT); // e.g. /opt/homebrew/var/www/
        $this->temp['html'] = DOC_ROOT . '/public/tmp/studcont/temp_a.html';
        $this->temp['wiki'] = DOC_ROOT . '/public/tmp/studcont/temp_a.wiki';
        
        /*
        $this->temp['eoe_report_multiple_titles'] = DOC_ROOT . '/public/tmp/studcont/eoe_report_multiple_titles_forELI.txt';
        $this->temp['Peter_report']               = DOC_ROOT . '/public/tmp/studcont/forPETER.txt';
        */
        
        $this->save_options = array('cache_path' => '/Volumes/MacMini_HD2/cache_studcont/', 'overwrite' => false); //normal operation is 'false'
        $this->only_nav_menu = false;
        $this->exclude_nav_menu = false;

        /*
        $this->temp['eli'] = "/opt/homebrew/var/www//EncyclopediaOfEarth/www.eoearth.org/topics/view/51cbfc7ef702fc2ba812af54/eli.html";
        $this->temp['eli'] = "/opt/homebrew/var/www//EncyclopediaOfEarth/www.eoearth.org/view/article/152610/eli.html";
        */
        
        $this->mediawiki_main_folder = "StudentContributions";
        $this->problematic_titles = false;
        $this->files_updated = 0;
    }
    
    private function significant_part_of_url($url)
    {
        // echo "\n$url\n";
        if(preg_match("/[\"|\'](.*?)[\"|\']/ims", $url, $arr))
        {
            // echo " -- " . $arr[1];
            $temp = explode("../", $arr[1]);
            // print_r($temp);
            return array_pop($temp);
        }
        return;
    }
    
    public function start()
    {
        $client_paths = array();
        $client_paths[] = '/SC_from_sitesucker/eol.org/info/student_contributions.html'; // one-page only
        foreach($client_paths as $client_path)
        {
            $main_url_to_process = $this->root . $client_path;
            $paths = self::get_anchors($main_url_to_process);
            self::generate_wiki_format($paths, $client_path, $main_url_to_process);
        }
        // self::key_values(@$this->debug['menu titles']);
    }
    
    private function generate_wiki_format($paths, $client_path, $main_url_to_process)
    {
        $temp_html_file = $this->temp['html'];
        $temp_wiki_file = $this->temp['wiki'];
        foreach($paths as $key => $possible_titles)
        {
            $fixed_path = self::adjust_path($key, $client_path);
            $url = $this->root . $fixed_path ;
            
            /*
            if(stripos($url, 'www.eoearth.org/index.html')                   !== false) continue; //don't proceed - BUT comment this if you want to generate Wiki for "Encyclopedia of Earth Topics"
            if(stripos($url, '/EncyclopediaOfEarth/www.trunity.net/portal/') !== false) continue; //don't proceed
            if(stripos($url, '/view/view/')                                  !== false) continue; //don't proceed
            */
            
            if(!file_exists($url)) continue;
            else
            {
                if(!self::is_it_html($url)) continue; //File is not HTML...will not process it...";
                if($titles = self::get_valid_titles($possible_titles, $key))
                {
                    foreach($titles as $val)
                    {
                        self::initialize_text_file($temp_html_file);
                        self::initialize_text_file($temp_wiki_file);
                        
                        $val = trim($val);
                        if(!$val) continue;
                        if($val == "Edit_Content") continue;
                        
                        //====================================================================================WORKING...
                        if($this->problematic_titles)
                        {
                            // /* generating non-unique titles...
                            @$this->debug['titles'][ucfirst($val)][$url][$main_url_to_process] = '';
                            continue;
                            // */
                        }
                        //====================================================================================
                        
                        /* no longer needed
                        // if(count(explode("/", $url)) >= 12)
                        // {
                            // if(stripos($url, "www.ars.usda.gov") !== false) {}//string is found
                            // else
                            // {
                            //     echo "\nwill not process, URL too long [$url]\n";
                            //     continue;
                            // }
                        // }
                        */
                        
                        if(isset($this->titles[$val][$url])) continue;
                        else     $this->titles[$val][$url] = '';
                        
                        // continue; //to just count all

                        //====================================================================================
                        // if(substr($val,0,8) == "Site_Map" || substr($val,0,8) == "Site_Map") {}
                        // else continue;

                        // if(substr($val,0,45) == "Climate_Change_and_Foreign_Policy\:_Chapter_5") {}
                        // else continue;

                        // if(substr($val,0,16) == "Content_Partners") {}
                        // else continue;

                        // if(in_array($val, array("Wheat"))) {}
                        // else continue;
                        //====================================================================================
                        
                        //====================================================================================WORKING...
                        /* this is to just counting the nav menu items...report for Jen...WORKING...
                        if(stripos($val, "\(main\)") !== false)  @$this->debug['menu titles'][$val][$url] = '';
                        continue;
                        */
                        //====================================================================================

                        /*--- special case -- //debug ... comment in normal operation
                        if(self::processed_already($val.$url, $this->save_options))
                        {
                            $temp_html = file_get_contents($url);
                            if(stripos($temp_html, "endnote_1") !== false) {}
                            else continue;
                        }
                        */
                        
                        if(self::processed_already($val.$url, $this->save_options)) continue; //debug ... uncomment in normal operation
                        
                        if($this->only_nav_menu)
                        {
                            if(stripos($val, "\(main\)") !== false) {}
                            else continue;
                        }
                        
                        if($this->exclude_nav_menu)
                        {
                            if(stripos($val, "\(main\)") !== false) continue;
                        }
                        
                        
                        // /*
                        //these 2 came from above
                        $images_info = self::get_images_size_info_from_html($url);
                        $this->images_size_info = $images_info['images_size_info'];
                        $this->title_icons_list = $images_info['title_icons_list'];
                        self::adjust_client_html($url, true, $val);
                        
                        if(filesize($temp_html_file))
                        {
                            echo "\n [$val] converting HTML to MediaWiki [$url]";   shell_exec("html2wiki --dialect MediaWiki $temp_html_file > " . $temp_wiki_file);
                            if(filesize($temp_wiki_file))
                            {
                                self::adjust_generated_wiki($temp_wiki_file, $val);
                                echo "\n [$val] generating wiki...";                    shell_exec("php /opt/homebrew/var/www/" . $this->mediawiki_main_folder . "/maintenance/edit.php -s 'Quick edit' -m " . $val . " < " . $temp_wiki_file);
                                self::save_process($val.$url, $this->save_options);
                            }
                            else echo "\nzero file size:[$val][$url]\n";
                        }
                        // */
                        
                    }
                }
            }
        }
    }
    
    private function save_process($combination, $options)
    {
        $md5 = md5($combination);
        $cache1 = substr($md5, 0, 2);
        $cache2 = substr($md5, 2, 2);
        if(!file_exists($options['cache_path'] . $cache1)) mkdir($options['cache_path'] . $cache1);
        if(!file_exists($options['cache_path'] . "$cache1/$cache2")) mkdir($options['cache_path'] . "$cache1/$cache2");
        $cache_path = $options['cache_path'] . "$cache1/$cache2/$md5.txt";
        if($FILE = Functions::file_open($cache_path, 'w')) fclose($FILE);
    }
    
    private function processed_already($combination, $options)
    {
        if($options['overwrite']) return false;
        $md5 = md5($combination);
        $cache1 = substr($md5, 0, 2);
        $cache2 = substr($md5, 2, 2);
        $cache_path = $options['cache_path'] . "$cache1/$cache2/$md5.txt";
        if(file_exists($cache_path)) return true;
        else                         return false;
    }
    
    private function fix_the_More_link($wiki)
    {
        /*
        <div class="inverseContentItem modRow"><span class="img"> [[Image:Computer_keyboard.jpeg.gif]] </span>
        <span class="inverseContainer"> 
        <span class="inverseTitle">[[Submitting an article]]</span> 
        <span class="inverseUpdated">Last Updated on 2014-05-07 15:39:29</span> 
        <span class="inverseContent"> Experts like you are the Encyclopedia’s most valuable asset -- we prominently feature authors and editors on every article. It is essential, ...
        include only that information which you feel comfortable being seen by everyone. While logged in, click on your name (top right) 
        Upload your user profile under “Biography” under the... [[More »]]</span> </span></div>
        */
        if(preg_match_all("/<div class=\"inverseContentItem modRow\">(.*?)<\/div>/ims", $wiki, $arr))
        {
            foreach($arr[1] as $block)
            {
                $new_block = $block;
                if(preg_match("/<span class=\"inverseTitle\">\[\[(.*?)\]\]<\/span>/ims", $block, $arr2)) //get the title
                {
                    $title = $arr2[1];
                    $new_block = str_replace("[[More »]]", "[[$title|More »]]", $new_block);
                }
                $wiki = str_replace($block, $new_block, $wiki);
            }
        }
        
        /*
        <div class="featuredContent vertical right"> OR <div class="featuredContent vertical ">
        * <span class="featureType"> [[Image:icon-article.png|Urushiol]] <span class="featureTypeTitle">Featured Article</span> </span> 
        <span class="img"> [[Image:poisonivyurushiol.jpg|Urushiol]] </span> 
        <span class="featureTitle">[[Urushiol]]</span>
        <div class="content">Introduction Urushiol is an oily toxin produced by plants in the cashew family (anacardiaceae).  These plants include poison ivy, poison oak, and poison sumac, and... [[More »]]
        </div>
        */
        if(preg_match_all("/[<div class=\"featuredContent vertical \">|<div class=\"featuredContent vertical right\">](.*?)<\/div>/ims", $wiki, $arr))
        {
            foreach($arr[1] as $block)
            {
                $new_block = $block;
                if(preg_match("/<span class=\"featureTitle\">\[\[(.*?)\]\]<\/span>/ims", $block, $arr2)) //get the title
                {
                    $title = $arr2[1];
                    $new_block = str_replace("[[More »]]", "[[$title|More »]]", $new_block);
                }
                $wiki = str_replace($block, $new_block, $wiki);
            }
        }
        return $wiki;
    }
    
    private function adjust_generated_wiki($wiki_file, $title)
    {
        $wiki = file_get_contents($wiki_file);
        
        $wiki = str_replace(" ", "", $wiki);
        
        //====start this finalizes the wiki ref adjustment --- this is from adjust_client_html() -> implement_wiki_ref()
        if($val = $this->ref_below)
        {
            foreach($val as $ref_id => $value)
            {
                if(stripos($wiki, $ref_id) !== false) //$ref_id is found
                {
                    $wiki = str_replace($ref_id."<", '<ref name="'. $ref_id .'">' . $value . '</ref><', $wiki);
                }
                else //case where ref below exists but was not called from above
                {
                    $wiki .= '<ref name="'. $ref_id .'">' . $value . '</ref>';
                }
            }
        }
        //====end
        
        $wiki = self::apply_image_sizes($wiki);
        $wiki = str_replace('<span class="img floatright"> [[Image:', '<span class="img floatright">' . "\n" . "\n" . '[[Image:', $wiki); //this adds blank row(s) on top of the first image...
        
        //this adds blank row(s) on top of the first image...or first title text e.g. http://localhost/" . $this->mediawiki_main_folder . "/index.php/Climate_Change#Climate_Change
        //==========
        $wiki = str_replace('<div id="pmid_809" class="box module-MemberGroupProfile portalDetail userContent clearfix hasImg" style="border-bottom: none">', 
                            '<div id="pmid_809" class="box module-MemberGroupProfile portalDetail userContent clearfix hasImg" style="border-bottom: none">' . "\n" . "\n" . "\n", $wiki);
        //==========

        $wiki = str_replace('<div class="sideModHeader">[[Image:recentCommentsIcon.png]]</div>', '', $wiki); //remove small recentCommentsIcon

        //remove the settingsGear.png html block
        if(preg_match("/<div class=\"right topicTitle anchorHolder\" style=\"float: right\">(.*?)<\/div><\/div>/ims", $wiki, $arr))
        {
            $wiki = str_replace('<div class="right topicTitle anchorHolder" style="float: right">' . $arr[1] . '</div></div>', "", $wiki);
        }

        $remove = array();
        $remove[] = '[[Image:spacer.png]]'; //removes this image
        //remove these 4 consecutive lines:
        $remove[] = '* ** [../../../members/index.html  Members]'."\n";
        $remove[] = '** [../../../contactus/index.html  Contact]<div class="labelContainer"><div class="label">[[Image:labelPointer.png]] Site Options</div></div>[index.html# [[Image:optionsIcon.png]]]'."\n";
        $remove[] = '* [../../../login/index.html  Login]'."\n";
        $remove[] = '* [../../../signup/index.html  Not a Member?]'."\n";
        foreach($remove as $r) $wiki = str_ireplace($r, '', $wiki);

        if(preg_match("/\[index(.*?)\[\[Image\:searchDown.png\|expand search options\]\] \]/ims", $wiki, $arr))
        {
            $wiki = str_replace("[index" . $arr[1] . "[[Image:searchDown.png|expand search options]] ]", "", $wiki);
        }
        // $remove[] = '[index.html#                                 [[Image:searchDown.png|expand search options]] ]'; //removes this image and anchor text
        //              [index-topic=51cbfc78f702fc2ba8129ea2.html#  [[Image:searchDown.png|expand search options]] ]
        
        $wiki = str_replace('<div style="height: 47px"></div>', '', $wiki); //This removes the top margin

        // this one seems erroneous 
        // /* this will remove the style attribute from this tag: <span class="imageWithCaptionContainer"> */
        // // $wiki = preg_replace('/<(span class="imageWithCaptionContainer") style="[^"]+">/i', '<$1>', $wiki); //uncomment in normal operation
        
        //this removes the style for this tag
        $wiki = preg_replace('/<(div class="thumbinner") style="[^"]+">/i', '<$1>', $wiki); //uncomment in normal operation
        
        /* this will remove the style attribute from this tag: <span class="img" style="width: 121px"> so will look like this: <span class="img"> */
        $wiki = preg_replace('/<(span class="img") style="[^"]+">/i', '<$1>', $wiki); //uncomment in normal operation

        if(preg_match_all("/\[(.*?)\]/ims", $wiki, $arr))
        {
        
            /*This general loop is to replace this [[../dir1/dir2/file.html link_text]] to [[link_text]] */
            foreach($arr[1] as $t)
            {
                //=========================== 1st part is of specific function
                /* this will replace left with this: [[Image:eoe-logo-400x87.png|400px]]
                $wiki = str_ireplace("[index.html [[Image:eoe-logo-400x87.png|400px]]]", "[[Image:eoe-logo-400x87.png|400px]]", $wiki);
                $wiki = str_ireplace("[../index.html [[Image:eoe-logo-400x87.png|400px]]]", "[[Image:eoe-logo-400x87.png|400px]]", $wiki);
                $wiki = str_ireplace("[../../index.html [[Image:eoe-logo-400x87.png|400px]]]", "[[Image:eoe-logo-400x87.png|400px]]", $wiki);
                $wiki = str_ireplace("[../../../index.html [[Image:eoe-logo-400x87.png|400px]]]", "[[Image:eoe-logo-400x87.png|400px]]", $wiki);
                                      ../../../index.html [[Image:eoe-logo-400x87.png|400px
                */
                if(stripos($t, "index.html [[Image:eoe-logo-400x87.png|400px") !== false) //this string is found
                {
                    // echo "\n{$t}\n";
                    $temp_arr = explode(" ", $t);
                    $wiki = str_ireplace("$t]]", "[Image:eoe-logo-400x87.png|400px]", $wiki);
                }
                //=========================== end of 1st part
                
                //=========================== 2nd part
                if(substr($t,0,7) == "[Image:") continue;
                if(substr($t,0,7) == "http://") continue;
                if(substr($t,0,8) == "[http://") continue; //for cases like where [1,2] '1' and '2' are hyperlinks e.g. http://www.eoearth.org/view/article/51cbf2a87896bb431f6aa403/
                if(stripos($t, "[[Image:") !== false) continue; //this string is found --- uncomment in real operation
                
                if(stripos($t, ".pdf ") !== false ||    //e.g. [../../../files/193801_193900/193898/module-two-answers.pdf Suggested Answers]
                   stripos($t, ".xlsx ") !== false ||
                   stripos($t, ".xls ") !== false) //string is found
                {continue;}
                
                $temp = explode(" ", $t);
                if(count($temp) > 1)
                {
                    $first = $temp[0];
                    $second = str_replace($first, "", $t);
                    $second = trim(strip_tags($second));
                    $wiki = str_replace($t, "[$second]", $wiki);
                }
                //=========================== end of 2nd part
            }
        }

        /*This will change this:
        <span class="img"> [../../../view/article/51cbed567896bb431f691862/index-topic=51cbfc78f702fc2ba8129e70.html  [[Image:Coral_500px_NASA.jpg|Coral reefs (collection)]] ] </span>
        to this:
        <span class="img"> [[Image:Coral_500px_NASA.jpg|Coral reefs (collection)]] </span>
        */
        if(preg_match_all("/<span class=\"img\">(.*?)<\/span>/ims", $wiki, $arr))
        {
            $temp = array_map('trim', $arr[1]);
            foreach($temp as $t)
            {
                if(preg_match("/\[\[(.*?)\]\]/ims", $t, $arr2))
                {
                    $wiki = str_replace('<span class="img"> ' . $t . ' </span>', '<span class="img"> [[' . $arr2[1] . ']] </span>', $wiki);
                }
            }
        }

        $wiki = str_ireplace('<div class="formSection searchBox">  [index-topic=51cbfc78f702fc2ba8129e70.html#  [[Image:searchDown.png|expand search options]] ]</div>', "", $wiki);
        $wiki = str_ireplace('<div class="formSection searchBox">  [index-topic=51cbfc79f702fc2ba812a1b8.html#  [[Image:searchDown.png|expand search options]] ]</div>', "", $wiki);
        $wiki = str_replace('<div class="barContainer">'."\n", '<div class="barContainer">', $wiki); //removes a carriage return
        
        $wiki = self::fix_the_More_link($wiki);
        $wiki = self::adjust_image_width($wiki);
        if($title == "Encyclopedia_of_Earth_Topics") $wiki = self::format_navigation($wiki);


        /*This will change this: -- Content Source Index -- /EncyclopediaOfEarth/www.eoearth.org//view/article/51cbed527896bb431f69166a/index-topic=51cbfc8bf702fc2ba812cc39.html
        [../../../contributor/ATSDR.html [[Image:ATSDR.jpg]]]
        ../../../contributor/NIEHS.html [[Image:NIEHS_logo.gif.jpeg
        to this:
        [ATSDR.html] [[Image:ATSDR.jpg]]
        */
        // /*
        if(preg_match_all("/\[(.*?)\]/ims", $wiki, $arr))
        {
            foreach($arr[1] as $t)
            {
                if(stripos($t, ".html [[Image:") !== false) //string is found
                {
                    $temp_arr = explode(" ", $t);
                    if(count($temp_arr > 1))
                    {
                        $filename = pathinfo($temp_arr[0], PATHINFO_BASENAME);
                        $wiki = str_replace("[$t]", "[[$filename]] ".$temp_arr[1], $wiki);
                    }
                }
            }
        }
        // */
        
        //this will put in logo for the big 3
        $wiki = str_replace('<div id="ncse_logo" class="mir">', "<br />".'<div id="ncse_logo" class="mir">', $wiki);
        $big3['http://www.ncseonline.org NCSE'] = 'ncse_logo.gif';
        $big3['http://www.bu.edu Boston University'] = 'bu_logo.gif';
        $big3['http://www.trunity.com Trunity'] = 'trunity_empowered.gif';
        foreach($big3 as $text => $img) $wiki = str_ireplace("[$text]</div>", "[[Image:$img]] [$text]</div>", $wiki);

        /* working but not used - replace trunity logo with wikimedia logo
        $wiki = str_replace('<div id="trunity_empowered" class="mir">[[Image:trunity_empowered.gif]] [http://www.trunity.com Trunity]</div>', '<div id="trunity_empowered" class="mir">[[Image:poweredby_mediawiki_88x31.png]] [https://www.mediawiki.org MediaWiki]</div>', $wiki);
        */
        
        //remove trunity logo
        $wiki = str_replace('<div id="trunity_empowered" class="mir">[[Image:trunity_empowered.gif]] [http://www.trunity.com Trunity]</div>', '', $wiki);
        
        /*this will change: [['''VINOD RAMIREDDY''']] to: '''[[VINOD RAMIREDDY]]''' --- this weird conversion is caused by <strong> in HTML */
        if(preg_match_all("/\[\['''(.*?)'''\]\]/ims", $wiki, $arr))
        {
            foreach($arr[1] as $t) $wiki = str_replace("[['''" . $t . "''']]", "'''[[$t]]'''", $wiki);
        }

        /*this will change: [[''Lobodon'']] to: ''[[Lobodon]]'' --- this weird conversion is caused by <i> in HTML */
        if(preg_match_all("/\[\[''(.*?)''\]\]/ims", $wiki, $arr))
        {
            foreach($arr[1] as $t) $wiki = str_replace("[[''" . $t . "'']]", "''[[$t]]''", $wiki);
        }
        
        /*this will change: [[eli@eol.org]] to: [mailto:eli@eol.org eli@eol.org] */
        if(preg_match_all("/\[\[(.*?)\]\]/ims", $wiki, $arr))
        {
            foreach($arr[1] as $t)
            {
                if(strpos($t, "@") !== false) $wiki = str_replace("[[$t]]", "[mailto:$t $t]", $wiki);
            }
        }
        
        $wiki = str_ireplace("* Attention!\n", "", $wiki);
        $wiki = str_ireplace("* The website id is invalid.\n", "", $wiki);
        $wiki = str_ireplace("* There is no published version of this content, so you must specify a version ID.\n", "", $wiki);
        $wiki = str_ireplace("* You do not have permission to view that content!\n", "", $wiki);
        $wiki = str_ireplace("* [[Login]]\n", "", $wiki);
        $wiki = str_ireplace("* [[Not a Member?]]\n", "", $wiki);
        
        //separate the to with a carriage return: </font>|} --- a problem with e.g. Wheat article
        $wiki = str_replace(">|}", ">" . "\n" . "|}", $wiki);
        $wiki = str_replace(">{|", ">" . "\n" . "{|", $wiki);
        
        // will have this: [http://www.eoearth.org/view/article/51cbed727896bb431f6923b8/Epicenter epicenter]
        // to be just --> epicenter
        if(preg_match_all("/\[http\:\/\/www.eoearth.org\/(.*?)\]/ims", $wiki, $arr))
        {
            foreach($arr[1] as $t)
            {
                $pos = strpos($t, " ");
                if($pos !== false) //string is found
                {
                    $word = trim(substr($t, $pos, strlen($t)));
                    $wiki = str_replace("[http://www.eoearth.org/".$t."]", $word, $wiki);
                }
            }
        }
        
        $wiki = self::add_main_parenthesis_to_sub_topics($wiki);

        $wiki = str_replace("[[index.html]]", "", $wiki);
        $wiki = str_replace("[[More]]", "", $wiki);
        $wiki = str_replace("[[Current Members and Authors]]", "[[Special:ListUsers|Current Members and Authors]]", $wiki);
        
        //to conclude implement_internal_link_to_an_anchor()
        $wiki = str_replace("aaabbbx", "[[", $wiki);
        $wiki = str_replace("cccdddx", "]]", $wiki);
        $wiki = str_replace("eeefffx", "|", $wiki);
        
        $wiki = str_replace("<nowiki>", "", $wiki);
        $wiki = str_replace("</nowiki>", "", $wiki);
        
        if(($OUT = Functions::file_open($wiki_file, "w")))
        {
            fwrite($OUT, $wiki);
            fclose($OUT);
        }
        
        self::fix_the_PDF_link($wiki_file);
    }
    
    private function add_main_parenthesis_to_sub_topics($wiki)
    {
        // [[About the EoE (main)]]
        // ==[[Thermal Maximums]]==
        
        $main = array();
        if(preg_match_all("/\[\[(.*?)\]\]/ims", $wiki, $arr))
        {
            foreach($arr[1] as $t)
            {
                $t = trim($t);
                if(strpos($t, " (main)") !== false) //string is found
                {
                    $main[$t] = '';
                }
            }
        }
        $main = array_keys($main);
        
        //for subtopics' titles
        if(preg_match_all("/\=\=\[\[(.*?)\]\]\=\=/ims", $wiki, $arr))
        {
            // print_r($arr[1]);
            foreach($arr[1] as $t)
            {
                $t = self::clean_html($t);
                foreach($main as $m)
                {
                    $m = trim($m);
                    $m_str = strtolower(substr($m, 0, strlen($t)));
                    // echo "\n[$m_str == " . strtolower($t) . "]";
                    if($m_str == strtolower($t)) $wiki = str_replace("==[[$t]]==", "==[[$m]]==", $wiki);
                }
            }
        }
        return $wiki;
    }
    
    private function format_navigation($wiki)
    {
        /* this will change this:
        <div class="leftNavLinkContainer " style="margin-left: 49px"><div class="leftNavLinkContainerInner">[[About the EoE]]</div><div class="leftNavItemAction">[[Image:right.png|14pxpx|Has Children]]</div></div>
        to this:
        [[About the EoE]] [[Image:right.png|14pxpx|Has Children]]
        */
        
        if(preg_match_all("/<div class=\"leftNavLinkContainer \" style=\"margin-left\: 49px\"><div class=\"leftNavLinkContainerInner\">(.*?)<\/div><\/div>/ims", $wiki, $arr))
        {
            foreach($arr[1] as $t)
            {
                $temp = str_replace('</div><div class="leftNavItemAction">', " ", $t);
                $wiki = str_replace('<div class="leftNavLinkContainer " style="margin-left: 49px"><div class="leftNavLinkContainerInner">'.$t."</div></div>", $temp, $wiki);
            }
        }
        
        /* this will change this:
        <div class="leftNavLinkContainer leftNavSelectedItem" style="margin-left: 49px"><div class="leftNavLinkContainerInner">[[Biodiversity]]</div><div class="leftNavItemAction">[[Image:down-selected.png|14pxpx|Has Children]]</div></div>
        to this:
        [[Biodiversity]] [[Image:down-selected.png|14pxpx|Has Children]]
        */
        if(preg_match_all("/<div class=\"leftNavLinkContainer leftNavSelectedItem\" style=\"margin-left: 49px\"><div class=\"leftNavLinkContainerInner\">(.*?)<\/div><\/div>/ims", $wiki, $arr))
        {
            foreach($arr[1] as $t)
            {
                $temp = str_replace('</div><div class="leftNavItemAction">', " ", $t);
                $wiki = str_replace('<div class="leftNavLinkContainer leftNavSelectedItem" style="margin-left: 49px"><div class="leftNavLinkContainerInner">'.$t."</div></div>", $temp, $wiki);
            }
        }
        
        return $wiki;
    }
    
    private function adjust_image_width($wiki)
    {
        /* this moves the 388px to "[[Image:sundogsjosephnhall.jpg|388px]]"
        <span class="imageWithCaptionContainer" style="text-align: left; width: 388px; border: thin solid #B5B5B5; float: left; margin: 10px; padding: 5px"> 
        <span class="imageWithCaptionImage"> [[Image:sundogsjosephnhall.jpg|caption]] </span> 
        <span class="imageWithCaptionText" style="text-align: left; width: 388px; line-height: 1; display: block; margin-top: 10px"><font face="Arial"> Sundog display over the Kluane Range, Alaska. Source: Joseph N.Hall </font>
        </span> </span> 
        */
        if(preg_match_all("/<span class=\"imageWithCaptionContainer\"(.*?)<\/span>/ims", $wiki, $arr))
        {
            foreach($arr[1] as $block)
            {
                if(strpos($block, "|caption]]") !== false) //string is found
                {
                    if(preg_match("/width\:(.*?)px/ims", $block, $arr2))
                    {
                        $number = trim($arr2[1]);
                        if(strlen($number) > 4) exit("\n 001 Investigate: number length > 4...[$number]\n");
                        $new_block = str_replace("|caption]]", "|".$number."px]]", $block);
                        $wiki = str_replace($block, $new_block, $wiki);
                    }
                }
            }
        }
        return $wiki;
    }
    
    private function fix_the_PDF_link($wiki_file)
    {
        //e.g. sample of page with PDF links: http://www.eoearth.org/view/article/51cbf06b7896bb431f6a185e/
        
        /* replace this:
        '''Full Final Report ''' [../../../files/152501_152600/152587/full_report.pdf [[Image:pdf_symbol.jpg]]]
        to this:
        '''Full Final Report ''' [[media: full_report.pdf|full_report.pdf]] [[Image:pdf_symbol.jpg|50px]]
        
        2nd part -- from:
        [../../../files/202501_202600/202550/module-two.recent-climate.change.graphs.pdf Click here to download the graphs only (PDF)]
        to this:
        [[media:module-two.recent-climate.change.graphs.pdf|Click here to download the graphs only (PDF)]]
        ...2nd part also works for .xlsx files, not just .pdf
        */
        $wiki = file_get_contents($wiki_file);
        if($file = Functions::file_open($wiki_file, "r"))
        {
            $topic_navigation_start = true;
            $topic_navigation_end = true;
            while(!feof($file))
            {
                $row = fgets($file);
                $orig_row = Functions::remove_whitespace($row);
                $temp_row = strip_tags($orig_row);
                
                /*was converted using $icons implementation below
                if(stripos($temp_row, "[[Image:pdf_symbol.jpg]]]") !== false) //string is found
                {
                    if(preg_match("/\/(.*?) \[\[Image/ims", $temp_row, $arr))
                    {
                        $filename = pathinfo($arr[1], PATHINFO_BASENAME);
                        echo "\n[$filename]";
                        if(preg_match("/\[(.*?)\]\]\]/ims", $temp_row, $arr)) $new_row = str_replace("[" . $arr[1] . "]]]", "", $temp_row);
                        $wiki = str_replace($row, $new_row . " - {[[media:" . $filename . "|" . $filename . "]] [[Image:pdf_symbol.jpg|25px]]}", $wiki);
                    }
                }
                */
                
                $icons = array("Image:pdf_symbol.jpg" => "media", "Image:Excel-icon.png.jpeg"      => "media", 
                                                                  "Image:ods_icon.png.jpeg"        => "media", 
                                                                  "Image:PowerPoint-icon.png.jpeg" => "media", 
                                                                  "Image:JPEG_Icon.jpg"            => "media");
                foreach($icons as $icon => $ns)
                {
                    if(stripos($temp_row, "[[$icon]]]") !== false) //string is found
                    {
                        if(preg_match_all("/\/(.*?) \[\[Image/ims", $temp_row, $arr))
                        {
                            foreach($arr[1] as $t)
                            {
                                $filename = pathinfo($t, PATHINFO_BASENAME);
                                echo "\n[$filename]";
                                if(preg_match("/\[(.*?)\]\]\]/ims", $temp_row, $arr2)) $new_row = str_replace("[" . $arr2[1] . "]]]", "", $temp_row);
                                $wiki = str_replace($row, $new_row . " - {[[$ns:" . $filename . "|" . "Download" . "]] [[$icon|25px]]}", $wiki);
                            }
                        }
                    }
                }
                
                
                //start of 2nd part:
                // /*
                if(preg_match_all("/\[(.*?)\]/ims", $orig_row, $arr))
                {
                    foreach($arr[1] as $t)
                    {
                        if(stripos($orig_row, ".pdf ") !== false ||
                           stripos($orig_row, ".xlsx ") !== false ||
                           stripos($orig_row, ".xls ") !== false ||
                           stripos($orig_row, ".ppt ") !== false ||
                           stripos($orig_row, ".pptx ") !== false ||
                           stripos($orig_row, ".jpg ") !== false ||
                           stripos($orig_row, ".ods ") !== false
                          ) //string is found
                        {
                            // if(stripos($orig_row, ".ods ") !== false) echo "\n$orig_row\n";
                            $temp = trim($t); //e.g.   ../../../files/202501_202600/202550/module-two.recent-climate.change.graphs.pdf Click here to download the graphs only (PDF)
                            $temp_arr = explode(" ", $temp);
                            $left = $temp_arr[0];
                            if(substr($left,0,7) == "http://") {} //external -- do nothing here.. it will correctly deal with it...
                            elseif(substr($left,0,6) == "ftp://") {} //external -- do nothing here.. it will correctly deal with it...
                            else //local internal
                            {
                                array_shift($temp_arr); //removes first array value
                                $filename = pathinfo($left, PATHINFO_BASENAME);
                                $extension = pathinfo($left, PATHINFO_EXTENSION);
                                // echo "\nextension: [$extension][$left][$t]\n";
                                if(in_array($extension, array("pdf", "xlsx", "xls", "ppt", "pptx", "ods"))) 
                                {
                                    // $wiki = str_replace($t, "[File:".$filename."]", $wiki);       ---> it was used for the longest time...then was replaced by below...
                                    $wiki = str_replace($t, "[media:".$filename."|". implode(" ", $temp_arr) ."]", $wiki);                             
                                }
                                else 
                                {
                                    if(count(strlen($t)) > 1) $wiki = str_replace($t, "[media:".$filename."|". implode(" ", $temp_arr) ."]", $wiki);
                                }   
                            }
                        }
                    }
                }
                // */
                
                //this part is for the topic navigation
                if(stripos($orig_row, '* <div class="leftNavLinkContainer') !== false) //string is found
                {
                    if($topic_navigation_start) //this is just one-time
                    {
                        $topic_navigation_start = false;
                        // $str = '{| class="mw-collapsible mw-collapsed wikitable"
                        // ! Topic Navigation
                        // |-';
                        $str = '{| class="mw-collapsible mw-collapsed wikitable" style="width:400px"'."\n"."! Topic Navigation"."\n"."|-";
                        $wiki = str_replace($orig_row, $str."\n"."|".$orig_row."\n"."|-"."\n", $wiki);
                    }
                    else
                    {
                        $replaced = "|".$orig_row."\n"."|-"."\n";
                        $wiki = str_replace($orig_row, $replaced, $wiki);
                    }
                }
                else
                {
                    if(!$topic_navigation_start && $topic_navigation_end)
                    {
                        $wiki = str_replace($replaced, $replaced."\n"."|}"."\n", $wiki);
                        $topic_navigation_end = false;
                    }
                }
                
            }
            fclose($file);
        }
        
        $wiki = self::format_navigation($wiki);
        $wiki = str_replace("[[]]", "", $wiki);
        
        if(($OUT = Functions::file_open($wiki_file, "w")))
        {
            fwrite($OUT, $wiki);
            fclose($OUT);
        }
    }
    
    private function get_images_size_info_from_html($path)
    {
        // <img alt="" src="../../../files/161201_161300/161230/ucl.jpg" style="width: 133px; height: 39px;" />
        $html = file_get_contents($path);
        $html = self::update_main_topics_title($html);
        
        $final = array();
        if(preg_match_all("/<img (.*?)>/ims", $html, $arr))
        {
            foreach($arr[1] as $t)
            {
                if(preg_match("/src=\"(.*?)\"/ims", $t, $arr3))
                {
                    $filename = pathinfo($arr3[1], PATHINFO_BASENAME);
                    if(preg_match("/width:(.*?)px/ims", $t, $arr2))
                    {
                        $number = trim($arr2[1]);
                        if(strlen($number) > 3) exit("\n 002 Investigate: number length > 3...[$number]\n");
                        $final[$filename] = $number;
                    }
                    elseif(preg_match("/width=\"(.*?)\"/ims", $t, $arr2))
                    {
                        $number = trim($arr2[1]);
                        $number = str_ireplace("px", "", $number);
                        if(strlen($number) > 3) exit("\n 003 Investigate: number length > 3...[$number]\n");
                        $final[$filename] = $number;
                    }
                    
                }
            }
        }
        $images_size_info = $final;
        
        //this is another process
        $title_icons_list = self::get_title_icons_list($html);
        
        return array("images_size_info" => $images_size_info, "title_icons_list" => $title_icons_list);
    }
    
    private function apply_image_sizes($wiki)
    {
        /* this will change: [[Image:ucl.jpg]] to this: [[Image:ucl.jpg|?px]] */
        foreach($this->images_size_info as $filename => $px) $wiki = str_replace("[[Image:" . $filename . "]]", "[[Image:" . $filename . "|" . $px . "px]]", $wiki);
        return $wiki;
    }
    
    private function get_title_tag($url)
    {
        $html = file_get_contents($url);
        return self::get_article_title($html);
    }
    
    private function gen_retrieved_from_line($html, $title)
    {
        //this will implement 'Retrieved from...' 
        if(preg_match("/Retrieved from http\:\/\/(.*?)<\/div>/ims", $html, $arr))
        {
            /* working well but not needed here...
            $client_path = "EncyclopediaOfEarth/".$arr[1];
            $new_path = trim($this->root . $client_path) . "/";
            if(is_dir($new_path))
            {
                $new_path = self::get_file_if_path_is_dir($new_path);
                $title = self::get_title_tag($new_path);
                $title = str_replace(" ", "_", $title);
            }
            $html = str_replace("Retrieved from http://".$arr[1]."</div>", 'Retrieved from <a href="'.$new_path.'" >' . $title. '</a></div> ' . $actual_link, $html);
            */
            $retrieved_from = "http://editors.eol.org/eoearth/wiki/" . str_replace("\\", "", $title);
            $html = str_replace("Retrieved from http://".$arr[1]."</div>", "Retrieved from $retrieved_from"."</div>", $html);
        }
        return $html;
    }
    
    private function implement_internal_link_to_an_anchor($html)
    {   /*
        <a href="index.html#Executive_summary">Executive summary </a>
        <a name="Executive_summary"></a>

        [[#Executive_summary|Executive summary]]
        <div id="Executive_summary">optional text</div>
        */
        
        //1st part: <a href="index.html#Executive_summary">Executive summary </a>
        $anchors = array();
        if(preg_match_all("/<a href=(.*?)<\/a>/ims", $html, $arr))
        {
            foreach($arr[1] as $t)
            {
                if(stripos($t, "#") !== false) // # is found
                {
                    if(stripos($t, "#'") !== false) continue;
                    if(stripos($t, '#"') !== false) continue;
                    if(stripos($t, ':#') !== false) continue;
                    if(preg_match("/\#(.*?)[\"|']>/ims", $t, $arr2))
                    {
                        $anchor = $arr2[1];
                        if(preg_match("/>(.*?)xxx/ims", $t."xxx", $arr2))
                        {
                            $link_text = $arr2[1];
                            if($link_text == "^")                      continue;    //for refs; and ref is implemented elsewhere
                            if(stripos($link_text, "<sup>") !== false) continue;    //for refs; and ref is implemented elsewhere
                            
                            $link_text = str_ireplace(array("<br />", "<br>", "<br/>"), "\n", $link_text);
                            $link_text = strip_tags($link_text);
                            
                            $anchors[] = $anchor;
                            // echo "\n[$anchor][$link_text]";
                            $new_t = "aaabbbx#".$anchor."eeefffx".$link_text."cccdddx"; // [[ | ]]
                            $html = str_replace("<a href=".$t."</a>", $new_t, $html);
                        }
                    }
                }
            }
        }
        $i = 0;
        //2nd part: <a name="Executive_summary"></a> OR <a id="Vulnerability_and_adaptive_capacity" name="Vulnerability_and_adaptive_capacity"></a>
        //wiki form: <div id="Executive_summary">optional text</div>
        
        if(preg_match_all("/<a (.*?)<\/a>/ims", $html, $arr))
        {
            foreach($arr[1] as $t)
            {
                /* $t values:
                name="Executive_summary">
                id="Introduction" name="Introduction">
                */
                if(preg_match("/name=[\"|'](.*?)[\"|']/ims", $t."xxx", $arr2))
                {
                    $name_attrib_value = str_replace(array("'", '"'), "", $arr2[1]);
                    if(in_array($name_attrib_value, $anchors))
                    {
                        $i++; //echo "\n[$t]";
                        if(preg_match("/>(.*?)xxx/ims", $t."xxx", $arr2)) $optional_value = $arr2[1];
                        $new_t = '<div id="'.$name_attrib_value.'">'.$optional_value.'</div>';
                        $html = str_replace("<a ".$t."</a>", $new_t, $html);
                    }
                }
            }
        }
        // echo "\n[$i] = " . count($anchors) . "\n";
        return $html;
    }
    private function adjust_client_html($path, $proceed = true, $title)
    {
        
        if(!$proceed)
        {
            $html = file_get_contents($path);
            if(($OUT = Functions::file_open($this->temp['html'], "w")))
            {
                fwrite($OUT, $html);
                fclose($OUT);
            }
            return;
        }
        
        $html = file_get_contents($path);
        
        $html = self::implement_internal_link_to_an_anchor($html);
        
     
        // - convert this: <a href="mailto:vhutchison@usgs.gov">Vivian&nbsp;Hutchison</a>
        // to this: <a href='mailto:vhutchison@usgs.gov'>vhutchison@usgs.gov</a> Vivian&nbsp;Hutchison
        if(preg_match_all("/<a href=\"mailto\:(.*?)<\/a>/ims", $html, $arr))
        {
            foreach($arr[1] as $t)
            {
                //vhutchison@usgs.gov">Vivian&nbsp;Hutchison
                if(preg_match("/xxx(.*?)\"/ims", "xxx".$t, $arr2))
                {
                    $email = $arr2[1];
                    if(preg_match("/>(.*?)xxx/ims", $t."xxx", $arr2))
                    {
                        $link_text = $arr2[1];
                        $html = str_replace('<a href="mailto:'.$t."</a>", "<a href='mailto:$email'>$email</a> $link_text", $html);
                    }
                }
            } 
        }
        
        $html = str_ireplace("bottom-",        "bottom:", $html);
        $html = str_ireplace("margin-bottom=", "margin-bottom:", $html);

        $html = self::update_main_topics_title($html);
        $html = self::add_desc_for_sub_titles($html);
        $html = str_replace("[[Killer Whale|Killer whales}}", "Killer Whale|Killer whales", $html); //Pinniped page
        $html = str_replace("{harp Seal|Harp seals]]", "harp Seal|Harp seals", $html);              //Pinniped page
        
        $html = self::gen_retrieved_from_line($html, $title);
        
        
        
        // this will remove the un-needed block in main index.html page and just leave the main <img> entry
        if(stripos($path, "www.eoearth.org/index.html") !== false)
        {
            if(preg_match("/<table(.*?)<\/table>/ims", $html, $arr))
            {
                $html = str_ireplace("<table".$arr[1]."</table>", '<img src="files/212701_212800/212795/eoe-logo-400x87.png" style="width: 400px; height: 87px;" />', $html);
            }
        }
        
        $html = str_replace('<a class="external text" ', '<a ', $html); //from Content Partners page
        
        $html = self::insert_title_icons($html);
        $html = str_ireplace(";Arial", ";font-family:Arial", $html); //e.g. Content Source Index


        $html = self::remove_portions($html);
        $html = self::remove_portions2($html);
        
        $html = self::implement_wiki_ref($html); //it is imperative that his comes before explicit_changes()
        
        $html = self::explicit_changes($html);
        $html = self::remove_attribute_value($html, 'font-size');
        $html = str_ireplace(array("<em>", "</em>"), "", $html); //kind a daring...

        
        
        /*remove certain blocks */
        if(preg_match("/<!-- ===== Action Bar =(.*?)<div id='announcementContent'/ims", $html, $arr))
        {
            $html = str_replace("<!-- ===== Action Bar =" . $arr[1], "", $html);
        }

        if(preg_match("/<ul  style='display:none'>(.*?)<\/ul>/ims", $html, $arr))
        {
            $html = str_replace("<ul  style='display:none'>" . $arr[1] . "</ul>", "", $html);
        }

        if(preg_match("/<!-- Site Bar -->(.*?)<!-- Account Bar -->/ims", $html, $arr))
        {
            $html = str_replace($arr[1], "", $html);
        }

        // /*
        if(preg_match("/<div id='cartModal' style='display:none' title='Shopping Cart'>(.*?)<div id='announcementContent'/ims", $html, $arr))
        {
            $html = str_replace("<div id='cartModal' style='display:none' title='Shopping Cart'>" . $arr[1], "", $html);
        }
        if(preg_match("/<ul class=\"sitebar\">(.*?)<\/ul>/ims", $html, $arr))
        {
            $html = str_replace('<ul class="sitebar">' . $arr[1] . "</ul>", "", $html);
        }
        if(preg_match("/<ul class=\"accountbar\">(.*?)<\/ul>/ims", $html, $arr))
        {
            $html = str_replace('<ul class="sitebar">' . $arr[1] . "</ul>", "", $html);
        }
        if(preg_match("/<div class='expandContent searchMenu' style='display:none;'>(.*?)<\/div>/ims", $html, $arr))
        {
            $html = str_replace("<div class='expandContent searchMenu' style='display:none;'>" . $arr[1] . "</div>", "", $html);
        }
        // */
        
        
        
        if(($OUT = Functions::file_open($this->temp['html'], "w")))
        {
            fwrite($OUT, $html);
            fclose($OUT);
        }
        return;
        
        //$title
    }

    /*
    bad
    <span class="reference"><sup class="plainlinksneverexpand" id="ref_4"><a class="external autonumber" href="index.html#endnote_4" rel="nofollow" title="#endnote_4">[4]</a></sup></span>
    <span class="reference"><sup class="plainlinksneverexpand" id="ref_4"><a class="external autonumber" href="index.html#endnote_4" rel="nofollow" title="#endnote_4">[4]</a></sup></span>
    <li>
    		<cite id="endnote_4" style="font-style: normal"><a href="index.html#ref_4" title=""><strong>^</strong></a></cite> MacDonald, G.M., A.A.Velichko, C.V. Kremenetski, O.K. Borisova, A.A. Goleva, A.A. Andreev, L.C. Cwynar, R.T. Riding, S.L. Forman, T.W.D. Edwards, R. Aravena, D. Hammarlund, J.M. Szeicz and V.N. Gattaulin, 2000. Holocene treeline history and climate change across Northern Eurasia. Quaternary Research, 53:302&ndash;311.
    </li>
    
    <span class='reference'><sup id='ref_1' class='plainlinksneverexpand'><a  data-cke-saved-href='#endnote_1' href='index.html#endnote_1' class='external autonumber' title='#endnote_1' rel='nofollow'>[1]</a></sup></span>.
    							</span>
    
    <span class='reference'><sup id='ref_2' class='plainlinksneverexpand'><a  data-cke-saved-href='#endnote_2' href='index.html#endnote_2' class='external autonumber' title='#endnote_2' rel='nofollow'>[2]</a></sup></span>
    
    good
    <span class="reference"><sup class="plainlinksneverexpand" id="ref_23"><a class="external autonumber" href="#endnote_23" rel="nofollow" title="#endnote_23">[23]</a></sup></span>
    <span class="reference"><ref name="endnote_23">Gower, S.</ref></span>
    */
    
    private function implement_wiki_ref($html)
    {   //good implementation here: http://editors.eol.localhost/eoearth/wiki/Climate_change_in_relation_to_carbon_uptake_and_carbon_storage#cite_ref-endnote_6_6-10
        
        // less, likely                                                     <span class="reference"><sup class="plainlinksneverexpand" id="ref_1"><a class="external autonumber" href="index.html#endnote_1" rel="nofollow" title="#endnote_1">[1]</a></sup></span>.&rdquo; 
        // various <a href="../155686/index.html" title="Region">regions</a><span class="reference"><sup class="plainlinksneverexpand" id="ref_2"><a class="external autonumber" href="index.html#endnote_2" rel="nofollow" title="#endnote_2">[2]</a></sup></span>.</p>
        
        //--start fix contents of <li></li> to remove carriage return e.g. file:///opt/homebrew/var/www/EncyclopediaOfEarth/www.eoearth.org/view/article/51cbed437896bb431f690f17/index.html
        if(preg_match_all("/<li>(.*?)<\/li>/ims", $html, $arr))
        {
            foreach($arr[1] as $t) $html = str_replace("<li>$t</li>", "<li>".self::clean_html($t)."</li>", $html);
        }
        //--end
        
        //reference below
        $ref_below = array();
        if(preg_match_all("/<li><cite (.*?)<\/li>/ims", $html, $arr_below))
        {
            // print_r($arr_below[1]);
            foreach($arr_below[1] as $t)
            {
                if(preg_match("/id=\"(.*?)\"/ims", $t, $arr2))
                {
                    $ref_id = $arr2[1];
                    // echo "\n[$ref_id]";
                    if(preg_match("/<\/cite>(.*?)xxx/ims", $t."xxx", $arr2))
                    {
                        $link_text = $arr2[1];
                        $link_text = strip_tags($link_text, "<i><b><a><font><br>");
                        $link_text = self::convert_str_with_anchor_to_wiki($link_text);
                        $ref_below[$ref_id] = Functions::remove_whitespace($link_text);
                    }
                }
            }
        }
        $this->ref_below = array();
        if($val = $ref_below) $this->ref_below = $val;
        print_r($this->ref_below);

        //reference on top -> replace it with: <ref name="test1">additional text.</ref>
        if(preg_match_all("/<span class=[\"|']reference[\"|']>(.*?)<\/span>/ims", $html, $arr))
        {
            // print_r($arr[1]);
            foreach($arr[1] as $t)
            {
                if(preg_match("/href=[\"|'](.*?)[\"|']/ims", $t, $arr2))
                {
                    $temp = $arr2[1];
                    $temp = explode("#", $temp);
                    $ref_id = $temp[1];
                    if($val = @$ref_below[$ref_id])
                    {
                        // echo "\n[$t][$ref_id]-";
                        $html = str_replace($t, $ref_id, $html);
                        $ref_below[$ref_id]['found'] = true;
                    }
                }
            }
            
            //removing old orig ref entries
            foreach($arr_below[1] as $t) $html = str_replace('<li><cite ' . $t . '</li>', '', $html);
        }
        return $html;
    }

    private function convert_str_with_anchor_to_wiki($str)
    {
        /* e.g. <font size="-1"> C. Michael Hogan. 2008.&nbsp;</font>
                <a href="http://www.globaltwitcher.com/artspec_information.asp?thingid=43182" class="external text" title="http://www.globaltwitcher.com/artspec_information.asp?thingid=43182" rel="nofollow"><font size="-1">
                <i>Rough-skinned  Newt (Taricha granulosa)</i></font><font size="-1">, Globaltwitcher, ed. N. Stromberg</font></a>*/
        if(preg_match("/<a href=\"http(.*?)<\/a>/ims", $str, $arr)) //it has to be external link, that is with 'http'
        {
            $temp = $arr[1];
            /* ://www.globaltwitcher.com/artspec_information.asp?thingid=43182" class="external text" title="http://www.globaltwitcher.com/artspec_information.asp?thingid=43182" rel="nofollow"><font size="-1">
            <i>Rough-skinned  Newt (Taricha granulosa)</i></font><font size="-1">, Globaltwitcher, ed. N. Stromberg</font>
            */
            if(preg_match("/>(.*?)xxx/ims", $temp."xxx", $arr2))
            {
                $link_text = $arr2[1];
                if(preg_match("/xxx(.*?)\"/ims", "xxx".$temp, $arr2))
                {
                    $href = "http".$arr2[1];
                    return str_replace('<a href="http'.$temp.'</a>', "[$href $link_text]", $str);
                }
            }
        }
        
        /*Curry, J.A. and A.H. Lynch, 2002. Comparing Arctic Regional Climate Models. Eos,Transactions, American Geophysical Union, 83:87.;<br />
        		-- see also <a href="../155689/index.html" title="Regional modeling of the Arctic">section 4.5.1</a>
        */
        if(preg_match_all("/<a href=\"(.*?)<\/a>/ims", $str, $arr))
        {
            foreach($arr[1] as $t)
            {
                if(substr($t,0,4) == "http") continue;
                if(preg_match("/>(.*?)xxx/ims", $t."xxx", $arr2))
                {
                    $link_text = $arr2[1];
                    $str = str_replace('<a href="'.$t."</a>", "aaabbbx".$link_text."cccdddx", $str);
                }
            }
        }
        
        return $str;
    }

    private function insert_title_icons($html)
    {   
        /* this will add the <img src=''> for the title icons
        <div class='leftNavLinkContainerInner'>
            <a href='../51cbfc8bf702fc2ba812cc39/index.html'  style='line-height:3em'>About the EoE</a>
        </div>
        */
        if(preg_match_all("/<div class='leftNavLinkContainerInner'>(.*?)<\/div>/ims", $html, $arr))
        {
            foreach($arr[1] as $t)
            {
                if(preg_match("/>(.*?)<\/a>/ims", $t, $arr2))
                {
                    $title = strip_tags($arr2[1]);
                    $html = str_replace($t, '<img src="' . $this->title_icons_list[$title] . '">'.$t, $html);
                }
            }
        }
        return $html;
    }
    
    private function get_title_icons_list($html)
    {
        /*
        <li id='leftNav-51cbfc8bf702fc2ba812cc39-0' memberGroupId='6515' parentCount='1.' linkedFrom='' topicId='51cbfc8bf702fc2ba812cc39' class='leftNavItem  closed'
                                    style='background-image:url("../b.static.trunity.net/files/153901_154000/153953/thumbs/nasa-earth_44x33_crop.jpg"); background-repeat:no-repeat; background-position:5px 50%;' childrenCount='1'>                             
                                     <div class='leftNavLinkContainer ' style='margin-left: 49px;'>
                                        <div class='leftNavLinkContainerInner'>
                                            <a href='topics/view/51cbfc8bf702fc2ba812cc39/index.html'  style='line-height:3em'>About the EoE</a>
                                        </div>
                                        <div class='leftNavItemAction'>
                                             <img src="modules/LeftNav/css/img/right.png" height="30px" width="14px" alt="Has Children"/> 
                                        </div>
                                     </div></li>*/

        // $html = str_ireplace(array("\n", "\r", "\t", "\o", "\xOB", "\11", "\011"), "", trim($html));

        //manual adjustment
        $html = str_replace("><li id='leftNav-", "></li><li id='leftNav-", $html);
        //end manual adjustment
        
        $final = array();
        if(preg_match_all("/<li id=\'leftNav\-(.*?)<\/li>/ims", $html, $arr))
        {
            foreach($arr[1] as $t)
            {
                if(preg_match("/background\-image\:url\(\"(.*?)\"\)/ims", $t, $arr2))
                {
                    $filename = pathinfo($arr2[1], PATHINFO_BASENAME);
                    if(preg_match("/<a href=(.*?)<\/a>/ims", $t, $arr3))
                    {
                        $title = strip_tags("<a href=".$arr3[1]."</a>");
                        $final[$title] = $filename;
                    }
                }
            }
        }
        return $final;
    }
    
    private function remove_attribute_value($html, $sub_attrib) //sub_attrib = "font-size" e.g. font-size:12px;
    {
        if(preg_match_all("/" . $sub_attrib . ":(.*?)[;|\"]/ims", $html, $arr)) //ending char either ; or "
        {
            foreach($arr[1] as $t) $html = str_ireplace("$sub_attrib:$t;", "", $html);
        }
        return $html;
    }
    
    private function adjust_path($key, $client_path)
    {
        $client_path = str_replace("//", "/", $client_path);
        $client_path = str_replace("//", "/", $client_path);
        
        $temp_arr = explode("/", $client_path);
        if($count = substr_count($key, '../'))
        {
            $count++; //plus 1
            for($i=1; $i<=$count; $i++) array_pop($temp_arr);
            $key = str_replace("../", "", $key);
            return implode("/", $temp_arr) . "/$key";
        }
        else
        {
            array_pop($temp_arr);
            return implode("/", $temp_arr) . "/$key";
        }
    }
    
    private function get_file_if_path_is_dir($url)
    {
        $filenames = glob($url."*.html");
        // print_r($filenames);
        usort($filenames, create_function('$a,$b', 'return filemtime($b) - filemtime($a);')); //last modified first
        foreach ($filenames as $filename)
        {
            if(file_exists($filename)) return $filename;
        }
    }
    
    private function get_actual_title_for_3dots($html)
    {
        if(preg_match_all("/<div class='leftNavLinkContainerInner'>(.*?)<\/div>/ims", $html, $arr))
        {
            foreach($arr[1] as $t)
            {
                $rec = array();
                if(preg_match("/>(.*?)<\/a>/ims", $t, $arr2))  $rec['text'] = trim($arr2[1]);
                if(preg_match("/href='(.*?)'/ims", $t, $arr2)) $rec['href'] = trim($arr2[1]);
                
                if(stripos($rec['text'], "...") !== false) // ... is found
                {
                    if($rec['href'] == 'index.html')
                    {
                        $title = self::get_article_title($html);
                        $new_t = str_replace($rec['text'], $title, $t);
                        $html = str_replace("<div class='leftNavLinkContainerInner'>$t</div>", "<div class='leftNavLinkContainerInner'>$new_t</div>", $html);
                        continue;
                    }
                    elseif(substr($rec['href'],0,23) == "http://www.eoearth.org/")
                    {
                        $final = $this->root . str_replace("http:/", "EncyclopediaOfEarth", $rec['href']) . "/";
                        if(is_dir($final)) $final = self::get_file_if_path_is_dir($final);
                    }
                    else
                    {
                        // href => ../51cbfc78f702fc2ba8129ea2/index.html
                        // href => ../topics/view/51cbfc78f702fc2ba8129ea2/index.html
                        $url = str_replace("../", "", $rec['href']);
                        $final = $this->root . "/EncyclopediaOfEarth/www.eoearth.org/topics/view/" . $url;
                        if(file_exists($final)) {}
                        else
                        {
                            $final = $this->root . "/EncyclopediaOfEarth/www.eoearth.org/" . $url;
                        }
                    }

                    if(file_exists($final))
                    {
                        $title = self::get_title_tag($final);
                        $new_t = str_replace($rec['text'], $title, $t);
                        $html = str_replace("<div class='leftNavLinkContainerInner'>$t</div>", "<div class='leftNavLinkContainerInner'>$new_t</div>", $html);
                        continue;
                    }
                    else
                    {
                        print_r($rec);
                        echo "\n[$t]\n";
                        echo("\nfix url [$final]\n");
                    }
                }
            }
        }
        return $html;
    }
    
    private function update_main_topics_title($html)
    {
        /* this one works but below (3 dots) is better
        $html = str_ireplace("style='line-height:3em'>...", 'style="line-height:3em">...', $html);
        $words = array("...");
        $html = self::word_fix($html, $words);
        */
        
        $html = self::get_actual_title_for_3dots($html);
        
        /* Jen took care of these */
        
        if(preg_match_all("/<div class='leftNavLinkContainerInner'>(.*?)<\/div>/ims", $html, $arr))
        {
            foreach($arr[1] as $t)
            {
                $orig_t = $t;
                if(preg_match("/>(.*?)<\/a>/ims", $t, $arr2)) //$arr2[1] is 'About the EoE'
                {
                    $new_t = str_replace($arr2[1], $arr2[1]." (main)", $t);
                    $html = str_replace("<div class='leftNavLinkContainerInner'>".$t."</div>", "<div class='leftNavLinkContainerInner'>".$new_t."</div>", $html);
                }
            }
        }
        
        //<span itemprop="articleSection">Species</span>
        if(preg_match_all("/<span itemprop=\"articleSection\">(.*?)<\/span>/ims", $html, $arr))
        {
            foreach($arr[1] as $t)
            {
                $html = str_replace('<span itemprop="articleSection">' . $t . '</span>', '<span itemprop="articleSection">' . $t . ' (main)</span>', $html);
            }
        }
        return $html;
    }
    
    private function explicit_changes($html)
    {
        // return $html;
        $html = str_replace(" ", "", $html);
        // $html = str_replace("width: ", "width:", $html); --- no effect, thus commented
        $html = str_replace("/view/view/", "/view/article/", $html);
        
        if(stripos($html, '/229013/index.html"><img') !== false) $html = str_replace('/229013/index.html"><img', '/229013/index.html">Energy profiles of countries and regions</a> <a><img', $html);
        if(stripos($html, '/Seas_of_the_world"><img') !== false) $html = str_replace('/Seas_of_the_world"><img', '/Seas_of_the_world">Seas of the World</a> <a><img', $html);
        if(stripos($html, '/155954/index.html"><img') !== false) $html = str_replace('/155954/index.html"><img', '/155954/index.html">Seas of the World</a> <a><img', $html);
        if(stripos($html, '/177117/index.html"><img') !== false) $html = str_replace('/177117/index.html"><img', '/177117/index.html">Ecoregions of Countries Collection</a> <a><img', $html);
        if(stripos($html, '/List_of_Countries_of_the_World"><img') !== false) $html = str_replace('/List_of_Countries_of_the_World"><img', '/List_of_Countries_of_the_World">List of Countries of the World</a> <a><img', $html);
        if(stripos($html, '/154266/index.html"><img') !== false) $html = str_replace('/154266/index.html"><img', '/154266/index.html"> Countries and Regions of the World Collection</a> <a><img', $html);
        
        if($article_title = self::get_article_title($html))
        {
            $html = self::format_numeric_links($html, $article_title);
            
            if($article_title == "Site Map for the Climate Change Collection")
            {
                $html = str_replace(' target="_blank"', "", $html);
                $html = str_replace('/topics/view/54099/index.html"><img', '/topics/view/54099/index.html">Causes (main)</a> <a><img', $html);
                $html = str_replace('/topics/view/54100/index.html"><img', '/topics/view/54100/index.html">Consequences (main)</a> <a><img', $html);
                $html = str_replace('/topics/view/54141/index.html"><img', '/topics/view/54141/index.html">Solutions (main)</a> <a><img', $html);
                $html = str_replace('/topics/view/54214/index.html"><', '/topics/view/54214/index.html">Actions (main)</a> <a><', $html);
                //<span style="color: rgb(0, 0, 255);">Corals</span></a>
                if(preg_match_all("/<span style=\"color\: rgb\(0\, 0\, 255\);\">(.*?)<\/span><\/a>/ims", $html, $arr))
                {
                    foreach($arr[1] as $t)
                    {
                        $t = trim($t);
                        if(strlen($t) > 1 && $t != "&nbsp;") $html = str_replace('<span style="color: rgb(0, 0, 255);">'.$t.'</span></a>', '<span style="color: rgb(0, 0, 255);">'.$t.' (climate change)</span></a>', $html);
                    }
                }
            }
            elseif($article_title == "Global Cycles")
            {
                $html = str_ireplace('/54331/index.html">Water</a>', '/54331/index.html">Water Cycle (article)</a>', $html);
            }
            
            //===========
            if($article_title == "Content Partners")
            {
                /* this is good but below is better
                $html = str_ireplace(array("<strong>", "</strong>"), "", $html);
                $words = array('About');        $html = self::word_fix($html, $words, $article_title);
                $words = array('Collection');   $html = self::word_fix($html, $words, $article_title);
                */
                
                $html = self::explicit_fix_Content_Partners($html);
                
            }
            elseif($article_title == "Add a Resource")
            {
                $html = str_replace('<a href="../../../articles/add/index.html"><img height="94" width="93" alt="" src="../../../files/153801_153900/153839/eoearth_icons-06.jpg" /></a><a href="../../../articles/add/index.html"><img height="93" width="230" src="../../../files/153801_153900/153845/eoearth_icons-10.jpg" alt="" /></a>', '<img height="94" width="93" alt="" src="../../../files/153801_153900/153839/eoearth_icons-06.jpg" /><a href="../../../articles/add/index.html">Add an Article</a>', $html);
                $html = str_replace('<a href="../../../teachingresources/add/index.html"><img height="93" width="93" alt="" src="../../../files/153801_153900/153805/eoearth_icons-04.jpg" /></a><a href="../../../teachingresources/add/index.html"><img height="93" width="382" alt="" src="../../../files/153801_153900/153846/eoearth_icons-09.jpg" /></a>', '<img height="93" width="93" alt="" src="../../../files/153801_153900/153805/eoearth_icons-04.jpg" /><a href="../../../teachingresources/add/index.html">Add a Teaching Resource</a>', $html);
                $html = str_replace('<a href="../../../galleries/add/index.html"><img height="93" width="93" alt="" src="../../../files/153801_153900/153806/eoearth_icons-03.jpg" /></a><a href="../../../galleries/add/index.html"><img height="94" width="258" alt="" src="../../../files/153801_153900/153847/eoearth_icons-08.jpg" /></a>', '<img height="93" width="93" alt="" src="../../../files/153801_153900/153806/eoearth_icons-03.jpg" /><a href="../../../galleries/add/index.html">Create a Gallery</a>', $html);
                $html = str_replace('<a href="../../../video/add/index.html"><img height="93" width="93" alt="" src="../../../files/153801_153900/153807/eoearth_icons-02.jpg" /></a><a href="../../../video/add/index.html"><img height="93" width="247" alt="" src="../../../files/163601_163700/163632/upload-video.jpg" /></a>', '<img height="93" width="93" alt="" src="../../../files/153801_153900/153807/eoearth_icons-02.jpg" /><a href="../../../video/add/index.html">Upload a Video</a>', $html);
            }
            elseif($article_title == "Energy profiles of countries and regions") $html = self::explicit_fix_EnergyProfiles_page($html, array('pre_title' => "Energy profile of"));
            elseif($article_title == "Coral reefs (collection)")
            {
                $html = self::explicit_fix_EnergyProfiles_page($html, array('post_title' => $article_title));
                $words = array('coral diseases', 'threats to coral reefs', 'coral reef resilience');
                foreach($words as $word) $html = str_ireplace(">$word</a>", ">$word ($article_title)</a>", $html);
            }
            
            elseif($article_title == "Biodiversity hotspots (collection)")            $html = self::explicit_fix_EnergyProfiles_page($html,      array('pre_title' => "Biological diversity in the"));  //<ul> then <li>
            elseif($article_title == "Ecology (collection)")                          $html = self::explicit_fix_EnergyProfiles_page($html,      array('post_title' => $article_title));                //<ul> then <li>
            elseif($article_title == "Large marine ecosystems (collection)")          $html = self::explicit_fix_EnergyProfiles_page($html,      array('post_title' => 'LME'));                         //<ul> then <li>
            elseif($article_title == "LAC Collection: Protected Areas and Biosphere Reserves") $html = self::explicit_fix_EnergyProfiles_page($html, array('pre_title' => "Protected areas of"));       //<ul> then <li>
            elseif($article_title == "Ecoregions of Countries")                       $html = self::explicit_fix_EcoregionsCountries_page($html, array('pre_title' => "Ecoregions of"));                //<ol> then <li>

            elseif($article_title == "Latin America and the Caribbean (collection)")  $html = self::explicit_fix_EcoregionsCountries_page($html, array('post_title' => "LAC"));                         //<ol> then <li>
            elseif($article_title == "Countries and Regions of the World Collection") $html = self::explicit_fix_EcoregionsCountries_page($html, array('post_title' => "Geography"));                   //<ol> then <li>
            elseif($article_title == "Africa (collection)")
            {
                                                                                      $html = self::explicit_fix_EcoregionsCountries_page($html, array('post_title' => "Africa (collection)"));         //<ol> then <li>
                // &nbsp;    <a href="../149985/index.html">Algeria</a>
                if(preg_match_all("/&nbsp; (.*?)<\/a>/ims", $html, $arr))
                {
                    foreach($arr[1] as $t) $html = str_replace("&nbsp; ".$t."</a>", "&nbsp; ".$t." (Geography)</a>", $html);
                }
                
            }
            
            
            
            
            elseif($article_title == "Aldo Leopold Collection")                       $html = self::explicit_fix_AldoLeopoldCollection_page($html);
            elseif($article_title == "Browse the EoE")
            {
                $words = array('Topics', 'Collections', 'Authors', 'eBooks', 'Wanted Articles', 'Content Partners', 
                'Articles', 'Environmental Classics', 'Countries of the world', 'Seas of the world', 'Topic Editors', 'International Advisory Board', 'Biographies of notable historical and living figures');
                $html = self::word_fix_single($html, $words, $article_title);
            }
            elseif($article_title == "Find Us Here")
            {
                $words = array('About');
                $html = self::word_fix($html, $words, $article_title);
            }
            elseif($article_title == "Climate Change Content Partners")
            {
                $words = array('Full profile', 'Articles', 'Homepage');
                $html = self::word_fix($html, $words, $article_title);
            }
            
            
            
            if(stripos($html, "report") !== false)
            {
                $words = array('Summary for Policymakers', 'Full Text', 'Front Matter', 'Technical Summary', 'Reviewers', 'Acronyms', 
                'Contributors', 'Permissions', 'Abbreviations, Chemical Symbols', 'Print Version', 'Clicking here');
                $html = self::word_fix($html, $words, $article_title);
            }
            
            //=========== use this if the word is used only once in the article
            $words = array('FAQs', 'FAQ', 'Timeline', 'Biographies', 'Further Reading', 'Terms of Use', 'Acknowledgements', 'Acknowledgement', 'Forward', 'Policies',
                           'References', 'Reference', 'Elements', 'Conclusions and recommendations', 'Case studies', "Editor's note", 'Quotes', 'Image Gallery', 
                           'Frequently Asked Questions', 'General features', 'Key findings', 'Narratives', 'Find Us Here', 'General Overview',
                           'Following chapter', 'Previous chapter', 'Next section', 'Preceding chapter', 'First chapter', 'Former chapter', 'Last chapter', 'Annex');
            $html = self::word_fix_single($html, $words, $article_title);
            //=========== use this if the word is used multiple times in the article
            $html = str_ireplace(array("<strong>", "</strong>"), "", $html);
            $words = array('Section', 'Chapter', 'Article', 'Introduction', 'e-Book', 'Read More', 'here', ' Table of Contents', 'Authors', 'Notes', 'Session', 'Working Group', 'Table', 'Level', 
            'Overview', 'Next chapter', 'Conclusions', 'Appendix', 'Preface', 'Glossary');
            $html = self::word_fix($html, $words, $article_title);
            
            
            //this is after all word_fix
            if($article_title == "IPCC Fourth Assessment Report (full report)")
            {
                $html = self::explicit_fix_EcoregionsCountries_page($html, array('post_title' => "IPCC Report")); //<ol> then <li>
            }
            
        }
        return $html;
    }
    
    private function word_fix_single($html, $words, $article_title = false)
    {
        if(!$article_title) $article_title = self::get_article_title($html);
        foreach($words as $word) $html = str_ireplace(">$word</a>", ">$word ($article_title)</a>", $html);
        return $html;
    }
    
    private function word_fix($html, $words, $article_title = false)
    {
        if(!$article_title) $article_title = self::get_article_title($html);
        foreach($words as $word)
        {
            if(preg_match_all("/\">" . $word . "(.*?)<\/a>/ims", $html, $arr))
            {
                foreach($arr[1] as $t)
                {
                    $new_link_text = $word."$t ($article_title)";
                    $new_link_text = strip_tags($new_link_text);
                    @$count[$new_link_text]++;
                    $c = ($count[$new_link_text] > 1 ? $count[$new_link_text] : ''); //ternary
                    // if($word == 'Section') $c = '';
                    $html = self::str_replace_first(">$word"."$t</a>", ">" . $new_link_text . " " . $c . "</a>", $html);
                }
            }
        }
        return $html;
    }

    private function get_article_title($html)
    {
        if(preg_match("/<title>(.*?)<\/title>/ims", $html, $arr)) return $arr[1];
        return false;
    }

    private function str_replace_first($search, $replace, $subject)
    {
        $pos = stripos($subject, $search);
        if($pos !== false) $subject = substr_replace($subject, $replace, $pos, strlen($search));
        return $subject;
    }

    private function get_anchors($url)
    {
        if(is_dir($url)) $url = self::get_file_if_path_is_dir($url);
        if(!file_exists($url)) return array();
        
        $html = file_get_contents($url);
        $html = self::update_main_topics_title($html);
        $html = self::add_desc_for_sub_titles($html);
        $html = str_ireplace('<a class="mw-redirect"', '<a ', $html);
        $html = self::fix_accented_chars($html);
        $html = str_ireplace('<a class="external text" ', '<a ', $html); //from Content Partners page
        $html = self::explicit_changes($html);
        $html = self::remove_portions($html);
        $html = self::clean_html($html);
        
        // if(preg_match_all("/<a href=(.*?)<\/a>/ims", $html, $arr))
        if(preg_match_all("/ href=(.*?)<\/a>/ims", $html, $arr))
        {
            $final = array();
            $exclude1 = array("index-topic=", "http://", "#");  //exclude if it starts with these
            $exclude2 = array('.css', '#');                     //exclude if it has these strings ... was included before: 51cbee8c7896bb431f698a5c
            
            //to filter anchors to process...
            foreach($arr[1] as $t)
            {
                $to_save = true;
                foreach($exclude1 as $exc)
                {
                    $length = strlen($exc);
                    if(substr($t,1,$length) == $exc) $to_save = false;
                }
                if($to_save) //further filtering
                {
                    foreach($exclude2 as $exc)
                    {
                        if(stripos($t, $exc) !== false) $to_save = false; //exc is found
                    }
                }
                if($to_save) $final[$t] = '';
            }
            
            //now to separate link and link_text
            $temp = $final; 
            $final = array();
            foreach(array_keys($temp) as $t)
            {
                //'../../../view/article/51cbf2057896bb431f6a78e2/index-topic=51cbfc79f702fc2ba812a1b9.html'>Hsieh, Paul A.
                $temp_arr = explode(">", $t);
                $left = $temp_arr[0];
                $left = str_replace("'", '"', $left);
                if(preg_match("/\"(.*?)\"/ims", $left, $arr))
                {
                    if(preg_match("/>(.*?)_xxx/ims", $t.'_xxx', $arr2))
                    {
                        if($val = trim($arr2[1])) $final[$arr[1]][] = $val;
                    }
                }
            }
            return $final;
        }
    }

    private function format_numeric_links($html, $article_title)
    {
        if(preg_match_all("/\">(.*?)<\/a>/ims", $html, $arr))
        {
            foreach($arr[1] as $t)
            {
                $t = trim($t);
                if(!$t) continue;
                if(self::is_numeric_combo($t))
                {
                    $html = str_replace('">' . "$t</a>", '">' . "$t ($article_title)</a>", $html);
                }
            }
        }
        return $html;
    }

    private function explicit_fix_EnergyProfiles_page($html, $options = array())
    {
        //<ul> then <li>
        if(preg_match_all("/<ul>(.*?)<\/ul>/ims", $html, $arr))
        {
            foreach($arr[1] as $t)
            {
                if(preg_match_all("/<li>(.*?)<\/li>/ims", $t, $arr2))
                {
                    foreach($arr2[1] as $t2)
                    {
                        $t2 = Functions::remove_whitespace($t2);
                        $t2 = strip_tags($t2, "<a>");
                        if(preg_match_all("/>(.*?)<\/a>/ims", $t2, $arr3))
                        {
                            foreach($arr3[1] as $t3)
                            {
                                if    ($val = @$options['pre_title'])  $html = str_replace(">$t3</a>", ">$val $t3</a>", $html);
                                elseif($val = @$options['post_title']) $html = str_replace(">$t3</a>", ">$t3 ($val)</a>", $html);
                            }
                        }
                    }
                }
            }
        }
        return $html;
    }
    
    private function explicit_fix_EcoregionsCountries_page($html, $options = array())
    {
        //<ol> then <li>
        /* this will remove the start attribute from this tag: <ol start="61"> so will look like this: <ol> */
        $html = preg_replace('/<(ol) start="[^"]+">/i', '<$1>', $html); //uncomment in normal operation
        
        if(preg_match_all("/<ol>(.*?)<\/ol>/ims", $html, $arr))
        {
            foreach($arr[1] as $t)
            {
                if(preg_match_all("/<li>(.*?)<\/li>/ims", $t, $arr2))
                {
                    foreach($arr2[1] as $t2)
                    {
                        $t2 = Functions::remove_whitespace($t2);
                        $t2 = strip_tags($t2, "<a>");
                        if(preg_match_all("/>(.*?)<\/a>/ims", $t2, $arr3))
                        {
                            foreach($arr3[1] as $t3)
                            {
                                if    ($val = @$options['pre_title'])  $html = str_replace(">$t3</a>", ">$val $t3</a>", $html);
                                elseif($val = @$options['post_title']) $html = str_replace(">$t3</a>", ">$t3 ($val)</a>", $html);
                            }
                        }
                    }
                }
            }
        }
        return $html;
    }
    
    private function explicit_fix_AldoLeopoldCollection_page($html)
    {
        $html = str_ireplace(">Biography</a>"                               , ">Leopold, Aldo (Biography)</a>", $html);
        $html = str_ireplace(">Major Publications</a>"                      , ">Aldo Leopold (Publications)</a>", $html);
        $html = str_ireplace(">Complete Bibliography</a>"                   , ">Aldo Leopold (Complete Bibliography)</a>", $html);
        $html = str_ireplace(">Major Publications About Aldo Leopold</a>"   , ">Major Publications About Aldo Leopold</a>", $html);
        $html = str_ireplace(">A Sand County Almanac</a>"                   , ">A Sand County Almanac</a>", $html);
        $html = str_ireplace(">Timeline</a>"                                , ">Aldo Leopold timeline</a>", $html);
        $html = str_ireplace(">Tributes</a>"                                , ">Tributes to Aldo Leopold</a>", $html);
        $html = str_ireplace(">Additional Resources</a>"                    , ">Aldo Leopold (Additional Resources)</a>", $html);
        $html = str_ireplace(">Acknowledgments</a>"                         , ">Aldo Leopold (Acknowledgments)</a>", $html);
        return $html;
    }
    
    private function fix_accented_chars($html)
    {
        //ç
        $html = str_replace("&#39;", "'", $html);
        $html = str_replace("&quot;", '"', $html);
        
        //todo: implement the capital versions of these...
        $arr = array("&aacute;" => "á", "&eacute;" => "é", "&iacute;" => "í", "&oacute;" => "ó", "&uacute;" => "ú", 
                     "&agrave;" => "à", "&egrave;" => "è", "&igrave;" => "ì", "&ograve;" => "ò", "&ugrave;" => "ù", 
                     "&aumlaut;" => "ä", "&eumlaut;" => "ë", "&iumlaut;" => "ï", "&oumlaut;" => "ö", "&uumlaut;" => "ü", 
                     "&acirc;" => "â", "&ecirc;" => "ê", "&icirc;" => "î", "&ocirc;" => "ô", "&ucirc;" => "û", 
                     "&atilde;" => "ã", "&ntilde;" => "ñ", "&otilde;" => "õ",
                     "&Aacute;" => "Á", "&Eacute;" => "É", "&Iacute;" => "Í", "&Oacute;" => "Ó", "&Uacute;" => "Ú",
                     "&Agrave;" => "À", "&Egrave;" => "È", "&Igrave;" => "Ì", "&Ograve;" => "Ò", "&Ugrave;" => "Ù",
                     "&Aumlaut;" => "Ä", "&Eumlaut;" => "Ë", "&Iumlaut;" => "Ï", "&Oumlaut;" => "Ö", "&Uumlaut;" => "Ü",
                     "&Acirc;" => "Â", "&Ecirc;" => "Ê", "&Icirc;" => "Î", "&Ocirc;" => "Ô", "&Ucirc;" => "Û",
                     "&Atilde;" => "Ã", "&Ntilde;" => "Ñ", "&Otilde;" => "Õ",
                     "&Yacute;" => "Ý", "&yacute;" => "ý", "&yuml;" => "ÿ");
                     
        /*
        'Å', 'Æ', 'Ç', 'Ø', '', 'Þ', 'ß', 'å', 'æ', 'ç', 'ð',
        'ø', '', '', '', '', 'þ', '');
        'Š', 'š', 'Ž', 'ž',
        */
        
        foreach($arr as $key => $value) $html = str_replace($key, "\\".$value, $html);

        //2nd type of entity, different from those above this
        $html = str_replace("&ndash;", "–", $html);
        $html = str_replace("&lsquo;", "‘", $html);
        $html = str_replace("&rsquo;", "’", $html);
        $html = str_replace("&amp;", "&", $html);
        $html = str_replace("&ldquo;", "“", $html);
        $html = str_replace("&rdquo;", "”", $html);
        $html = str_replace("&raquo;", "»", $html);
        return $html;
    }
    
    private function remove_portions($html)
    {
        if(preg_match("/<div class=\"addCommentsStatus\">(.*?)<\/div>/ims", $html, $arr)) $html = str_replace('<div class="addCommentsStatus">'.$arr[1].'</div>', "", $html);
        if(preg_match("/<h3>Recent Comments<\/h3>(.*?)<script/ims", $html, $arr))         $html = str_ireplace("<h3>Recent Comments</h3>".$arr[1], "", $html);
        if(preg_match_all("/<div class='panel-overlay'>(.*?)<\/div>/ims", $html, $arr))
        {
            foreach($arr[1] as $t) $html = str_ireplace($t, "", $html);
        }
        return $html;
    }
    
    private function remove_portions2($html)
    {
        //removes all script tags
        if(preg_match_all("/<script type=\"text\/javascript\">(.*?)<\/script>/ims", $html, $arr))
        {
            foreach($arr[1] as $t)
            {
                $html = str_ireplace('<script type="text/javascript">'.$t."</script>", "", $html);
            }
        }
        return $html;
    }

    private function get_valid_titles($titles, $path)
    {
        $final = array();
        foreach($titles as $title)
        {
            $title = str_replace("&nbsp;", " ", $title);
            if($title == "More &raquo;")            continue;
            elseif($title == "More »")              continue;
            elseif(substr($title, 0, 5) == "<img ") continue;

            $title = str_replace("&", "\&", $title);
            $title = str_replace(" ", "_", $title);
            $title = str_replace("(", "\(", $title);
            $title = str_replace(")", "\)", $title);
            $title = str_replace(",", "\,", $title);
            $title = str_replace("'", "\'", $title);
            $title = str_replace('"', '\"', $title);
            $title = str_replace(";", "\;", $title);
            $title = str_replace(":", "\:", $title);
            $title = str_replace("|", "\|", $title);
            $title = str_replace("?", "\?", $title);
            $title = str_replace("–", "\–", $title);
            $title = str_replace("‘", "\‘", $title);
            $title = str_replace("’", "\’", $title);
            $title = str_replace("“", "\“", $title);
            $title = str_replace("”", "\”", $title);
            $title = str_replace("»", "\»", $title);
            
            $title = strip_tags($title);
            $title = Functions::remove_whitespace($title);
            if($title == "articles") continue; //bec. of Content Source Index == manual intervention
            $final[$title] = '';
        }
        if($final) return array_keys($final);
        return array(pathinfo($path, PATHINFO_BASENAME)); //for Content Source Index
    }
    
    private function get_new_title($title)
    {
        $arr = explode(" ", $title);
        $last = end($arr);
        if(strlen($last) == 2 && substr($last,0,1) == "v")
        {
            $num = substr($last,1,strlen($last));
            $num++;
            array_pop($arr);
            $arr[] = "v$num";
            $title = implode(" ", $arr);
        }
        else $title .= " v2";
        return $title;
    }
    
    private function get_all_hrefs_from_page($client_path)
    {
        $url = $this->root . $client_path;
        $final = array();
        if($paths = self::get_anchors($url))
        {
            foreach($paths as $key => $possible_titles)
            {
                if(stripos($key, "mailto:") !== false) continue;

                $fixed_path = self::adjust_path($key, $client_path);

                if(!in_array(pathinfo($fixed_path, PATHINFO_EXTENSION), array("html", "htm", "HTML", "HTM"))) continue;
                
                if(!isset($this->unique_urls[$fixed_path]))
                {
                    $this->unique_urls[$fixed_path] = '';
                    $final[$fixed_path] = '';
                }
            }
        }
        echo " - " . count($final);
        return array_keys($final);
    }

    private function bulk_generate_wiki($client_paths)
    {
        foreach($client_paths as $client_path)
        {
            $main_url_to_process = $this->root . $client_path;
            if($paths = self::get_anchors($main_url_to_process)) self::generate_wiki_format($paths, $client_path, $main_url_to_process);
            // else echo "\nno paths: [$main_url_to_process]\n";
        }
    }
    
    private function get_main_links_from_page($url, $client_path)
    {
        if(!file_exists($url)) return array();
        $html = file_get_contents($url);
        $recs = array();
        if(preg_match_all("/<div class='leftNavLinkContainerInner'>(.*?)<\/div>/ims", $html, $arr))
        {
            /* <a href='topics/view/51cbfc8bf702fc2ba812cc39/index.html'  style='line-height:3em'>About the EoE</a> */
            foreach($arr[1] as $t)
            {
                if(preg_match("/href='(.*?)'/ims", $t, $arr2))
                {
                    if(preg_match("/'>(.*?)<\/a>/ims", $t, $arr3)) $recs[$arr2[1]] = $arr3[1];
                }
            }
        }
        $final = array();
        if($recs)
        {
            foreach($recs as $raw_url => $title)
            {
                // echo "\n raw: [$raw_url] - [$title]";
                $url = self::adjust_path($raw_url, $client_path);
                // echo "\n new: [$url]";
                $final[$url] = '';
            }
        }
        return array_keys($final);
    }
    
    private function is_it_html($url)
    {
        $extension = strtolower(pathinfo($url, PATHINFO_EXTENSION));
        $included_extensions = array("html", "htm", "HTML", "HTM");
        if(in_array($extension, $included_extensions)) return true;
        else
        {
            $this->debug['excluded_urls'][$url] = '';
            return false;
        }
    }
    
    private function explicit_fix_Content_Partners($html)
    {
        /*
        <td valign="center" width="35%">
        	<strong>American Institute of Physics</strong><br />
        	<strong><a class="external text" href="../../../profile/AIP/index.html" rel="nofollow" title="/profile/AIP">About</a></strong>
        </td>

        <div align="left">
        	<strong>Environmental Health Perspectives<br />
        	<a class="external text" href="../../../profile/EHP/index.html" rel="nofollow" title="/profile/EHP">About</a></strong>
        </div>
        
        this is not yet being implemented, that's why there is no 'e-book' nor 'collection' yet
        <td>
            <strong>United Nations Environment Programme</strong><br />
            <strong><a class="external text" href="../../../profile/UNEP/index.html" rel="nofollow" title="/profile/UNEP">About</a> |&nbsp;<a href="../../../view/article/149875/index.html" title="Africa Environment Outlook 2: Our Environment, Our Wealth (e-book)">e-Book</a></strong>
        </td>
        */
        
        $html = str_ireplace(array("<strong>", "</strong>"), "", $html);
        
        if(preg_match_all("/<td(.*?)<\/td>/ims", $html, $arr))
        {
            foreach($arr[1] as $t)
            {
                $orig_t = $t;

                $t = str_replace(array("<br />", "<br>"), " ", $t);
                // $t = strip_tags("<td".$t, "<a><br>");
                $t = strip_tags("<td".$t, "<a>");
                
                $link_text = false; $href = false;
                if(preg_match("/xxx(.*?)</ims", "xxx".$t, $arr2))
                {
                    $link_text = str_replace("&amp;", "&", $arr2[1]);
                    
                    $link_text = Functions::remove_whitespace(strip_tags($link_text));
                    if(preg_match("/href=\"(.*?)\"/ims", $t, $arr2)) $href = $arr2[1];
                }
                if($link_text && $href) $html = str_replace("<td".$orig_t."</td>",  '<a href="' . $href . '">' . $link_text . '</a>', $html);
            }
        }
        if(preg_match_all("/<div(.*?)<\/div>/ims", $html, $arr))
        {
            foreach($arr[1] as $t)
            {
                $orig_t = $t;
                $t = str_replace(array("<br />", "<br>"), " ", $t);
                $t = strip_tags("<td".$t, "<a>");
                $link_text = false; $href = false;
                if(preg_match("/xxx(.*?)</ims", "xxx".$t, $arr2))
                {
                    $link_text = Functions::remove_whitespace(strip_tags($arr2[1]));
                    if(preg_match("/href=\"(.*?)\"/ims", $t, $arr2)) $href = $arr2[1];
                }
                if($link_text && $href) $html = str_replace("<div".$orig_t."</div>",  '<a href="' . $href . '">' . $link_text . '</a>', $html);
            }
        }
        return $html;
    }
    
    /*
    private function files_are_equal($a, $b)
    {
        // // Check if filesize is different
        // if(filesize($a) !== filesize($b)) return false;

        // Check if content is different
        $a_html = self::get_contents_for_file_comparison($a);
        $b_html = self::get_contents_for_file_comparison($b);
        if($a_html == $b_html) return true;
        else return false;
    }
    */
    
    private function get_contents_for_file_comparison($path, $some_id)
    {
        // <div class="publishingInfo">
        // <div itemprop="text">
        // <div id='citation' class="marg_b_15">
        
        $html = file_get_contents($path);
        $html1 = ""; $html2 = "";
        if(preg_match("/<div class=\"publishingInfo\">(.*?)<div itemprop=\"text\">/ims", $html, $arr)) $html1 = Functions::remove_whitespace(trim(self::clean_html($arr[1])));
        if(preg_match("/<div itemprop=\"text\">(.*?)<div id=\'citation\'/ims", $html, $arr))           $html2 = Functions::remove_whitespace(trim(self::clean_html($arr[1])));
        $html = Functions::remove_whitespace(trim(self::clean_html($html1.$html2)));
        
        // /*
        // now remove this first occurrence of articleSection : <span itemprop="articleSection">Agricultural & Resource Economics</span>
        // so to see if actual content is unique...
        if(preg_match("/<span itemprop=\"articleSection\">(.*?)<\/span>/ims", $html, $arr))
        {
            $html = str_replace('<span itemprop="articleSection">'.$arr[1].'</span>', "", $html);
        }
        // */
        $html = str_replace($some_id, "", $html);           //remove some_id to make it non-unique
        if(preg_match_all("/src=(.*?)</ims", $html, $arr))  //remove src to make it non-unique (static.trunity.net vs a.static.trunity.net)
        {
            foreach($arr[1] as $t) $html = str_replace($t, "", $html);
        }
        if(preg_match_all("/href=(.*?)</ims", $html, $arr))  //remove href to make it non-unique (index.html vs index-alksfsdlfsdlk.html)
        {
            foreach($arr[1] as $t) $html = str_replace($t, "", $html);
        }
        return $html;
    }

    private function get_unique_files($files)
    {
        //1st filter is for the pathinfo_dirname - IMPORTANT to remove duplicate URLs
        $temp = array();
        foreach($files as $file)
        {
            $dirname = pathinfo($file, PATHINFO_DIRNAME);
            $temp[$dirname] = $file;
        }
        
        //2nd filter is for the portional HTML content
        $files = $temp;
        $temp = array();
        foreach($files as $key => $file)
        {
            // get some_id
            $x = pathinfo($file, PATHINFO_DIRNAME);
            $x = explode("/", $x);
            $some_id = array_pop($x);
            $content = self::get_contents_for_file_comparison($file, $some_id);
            echo "\n--".strlen($content)."--\n";
            $temp[$content] = $file;
        }
        
        /* NOTE: Cannot use <title></title> to measure uniquenes caz many pages have similar titles but very different content. The 2 filters above work OK. */
        
        $destinations = array();
        foreach($temp as $key => $value) $destinations[$value] = '';
        return $destinations;
    }

    private function update_HTML_fix_problematic_titles()
    {
        $list = array_map(function ($v)
        {   uasort($v, function ($a, $b)
            {   $a = count($a);
                $b = count($b);
                return ($a == $b) ? 0 : (($a < $b) ? 1 : - 1);
            });
            return $v;
        }, $this->debug['titles']);
        unset($this->debug['titles']);
        
        $OUT = Functions::file_open($this->temp['eoe_report_multiple_titles'], "w");
        $this->unique = array();
        foreach($list as $title => $recs)
        {
            $title = trim($title);
            if(!$title) continue;
            
            $orig_title = $title;
            $title = ucfirst($title);
            $title = str_replace("_", " ", $title);
            $title = str_replace("\\", "", $title);
            // if(stripos($title, " (main)") !== false) continue;
            
            // if(!in_array($title, array("Ecosystem services", "Radioactive"))) continue;                    //debug comment in real operation
            
            $destinations = array();
            foreach($recs as $destination => $sources) $destinations[$destination] = '';

            if(count($destinations) > 1)
            {
                $destinations = self::get_unique_files(array_keys($destinations)); //possibility being checked here
                if(count($destinations) < 2) continue;  //normal
                // if(count($destinations) > 6) continue;  //for Eli's investigation

                $str = "";
                $str .= "\n[$title]";
                fwrite($OUT, $str); 

                $first_row = true;
                foreach(array_keys($destinations) as $destination)
                {
                    // /* //use this when UPDATING...
                    if($first_row)
                    {
                        $first_row = false;
                        continue;
                    }
                    // */
                    
                    $orig_destination = $destination;
                    $str2 = "";
                    $str2 .= "\n destination:$destination";
                    $str2 .= "\n - sources:";
                    $str3 = "";
                    if($sources = @$list[$orig_title][$orig_destination])
                    {
                        // if(count($sources) > 5)
                        if(false) //use this when UPDATING...
                        {
                            $str3 .= " A total of " . count($sources) . " pages are using this destination.";
                            $i = 0;
                            foreach(array_keys($sources) as $source)
                            {
                                $i++;
                                // self::update_title_in_raw_html($title, $source);
                                if(self::print_this($source)) $str3 .= "\n   --- $source";
                                if($i == 5) break;
                            }
                        }
                        else
                        {
                            foreach(array_keys($sources) as $source)
                            {
                                self::update_title_in_raw_html($title, $source, $destination);
                                if(self::print_this($source)) $str3 .= "\n   --- $source";
                            }
                        }
                    }
                    fwrite($OUT, $str2.$str3."\n"); 
                }
            }
        }
        fclose($OUT);
    }

    private function print_this($path)
    {
        return true;
        $a = explode("/", $path);
        if(count($a) >= 7) return true;
        else return false;
    }

    private function print_this_Peter($path)
    {
        return true;
        /*
        $a = explode("/", $path);
        if(count($a) >= 7)
        {
            if(isset($this->unique[$path])) return false;
            $this->unique[$path] = '';
            return true;
        }
        else return false;
        */
        $a = explode("/", $path);
        if(count($a) >= 7) return true;
        else return false;
        
    }

    private function key_values($arr)
    {
        // return;
        if(!$arr) return;
        foreach($arr as $key => $values)
        {
            if(count($values) > 1)
            {
                echo "\n[$key]";
                print_r($values);
            }
        }
    }

    private function update_title_in_raw_html($sought_title, $source, $destination)
    {
        $article_title_of_destination = self::get_title_tag($destination);
        
        // $sought_title = "Temperature";
        // $source = "/opt/homebrew/var/www//EncyclopediaOfEarth/www.eoearth.org/topics/view/51cbfc7ef702fc2ba812af54/index.html";
        
        echo "\n updating HTML [$sought_title][$source][$article_title_of_destination]\n";
        
        $orig_html = file_get_contents($source);
        $html = $orig_html;
        $article_title = self::get_article_title($html);
        $proceed_save = false;
        
        //remove main nav links
        if(preg_match_all("/<div class='leftNavLinkContainerInner'>(.*?)<\/div>/ims", $html, $arr))
        {
            foreach($arr[1] as $t) $html = str_replace("<div class='leftNavLinkContainerInner'>".$t."</div>", "", $html);
        }

        $already_processed = array();
        $count = 0;
        //<a href="../54337/index.html">Temperature</a></h2>
        if(preg_match_all("/ href=(.*?)<\/a>/ims", $html, $arr))
        {
            $lines = array_unique($arr[1]);
            foreach($lines as $line) //"../54337/index.html">Temperature
                                     //"../../../topics/view/54290/index.html" target="_blank"><span style="color: rgb(0, 0, 255);">Temperature</span>
            {
                if(preg_match("/>(.*?)xxx/ims", $line.'xxx', $arr2))
                {
                    $link_text = trim(strip_tags($arr2[1]));
                    $link_text = str_replace("  ", " ", $link_text);
                    $link_text = Functions::remove_whitespace($link_text);

                    $old_line = $line;
                    //================== manual adjustments
                    $line = str_replace("  ", " ", $line);
                    $line = Functions::remove_whitespace($line);
                    $line = str_ireplace(array("<sub>", "</sub>", "<em>", "</em>"), "", $line);
                    //==================
                    $orig_html = str_replace($old_line, $line, $orig_html);
                    
                    // if(isset($already_processed[$link_text])) continue;
                    // else $already_processed[$link_text] = '';
                    
                    // echo "[$link_text][$line][$destination][".self::significant_part_of_url($line)."]\n";
                    if(stripos($destination, self::significant_part_of_url($line)) !== false) {}
                    else
                    {
                        // echo "\ndidn't pass this test [$destination][".self::significant_part_of_url($line)."]\n";
                        continue;
                    }
                    
                    $sought_title = str_replace("'", "&#39;", $sought_title);
                    
                    if(strtolower($link_text) == strtolower($sought_title))
                    {
                        // if($link_text == "CO2") $link_text = "CO<sub>2</sub>";
                        
                        $new_link_text = "$link_text ($article_title_of_destination)";
                        $new_line = str_replace($link_text, $new_link_text, $line);
                        $orig_html = str_replace($line, $new_line, $orig_html);
                        echo "\nxxxyyy[$line][$new_line]\n";
                        $proceed_save = true;
                    }
                }
            }
        }
        
        if($proceed_save)
        {
            // if(($OUT = Functions::file_open($this->temp['eli'], "w")))   //for debugging only
            if(($OUT = Functions::file_open($source, "w")))                 //used in normal operation
            {
                fwrite($OUT, $orig_html);
                fclose($OUT);
                echo "\nSaved OK [$sought_title][$article_title_of_destination]\n";
                $this->files_updated++;
            }
        }
        else echo "\nNot saved! [$sought_title][$article_title_of_destination]\n";
    }
    
    private function add_desc_for_sub_titles($html) // adds the title in parenthesis in the “featured articles” sections for every main topic. This is similar to the effect in the SearchResults page.
    {
        $article_title = self::get_article_title($html);
        if(preg_match_all("/<span class=\"featureTitle\">(.*?)<\/span>/ims", $html, $arr))  $html = self::loop_and_replace_dup_titles($arr[1], $article_title, $html); //articles on top
        if(preg_match_all("/<span class='inverseTitle'>(.*?)<\/span>/ims", $html, $arr))    $html = self::loop_and_replace_dup_titles($arr[1], $article_title, $html); //articles below
        return $html;
    }
    
    private function loop_and_replace_dup_titles($lines, $article_title, $html)
    {
        /*
        $list_of_duplicate_titles = array("Bacteria", "Pollution", "Overgrazing");
        $list_of_duplicate_titles = array("Storms");
        */
        $count = array();
        foreach($lines as $t)
        {
            if(preg_match("/>(.*?)<\/a>/ims", $t, $arr2))
            {
                /*
                $link_text = $arr2[1];
                if(in_array($link_text, $list_of_duplicate_titles))
                {
                    $new_t = str_replace($link_text, "$link_text ($article_title)", $t);
                    $html = str_replace($t, $new_t, $html);
                }
                */
                $link_text = $arr2[1];
                $word_count = str_word_count($link_text);
                if($word_count < 3) $link_text .= " ($article_title)";
                @$count[$link_text]++;
                $c = (@$count[$link_text] > 1 ? $count[$link_text] : ''); //ternary
                $new_t = str_replace($arr2[1], $link_text. " $c", $t);
                $html = str_replace($t, $new_t, $html);
            }
        }
        return $html;
    }
    
    private function is_numeric_combo($str)
    {
        $digits = "0,1,2,3,4,5,6,7,8,9,., ";
        $digits = explode(",", $digits);
        for($i=0; $i<=strlen($str)-1; $i++)
        {
            if(!in_array($str[$i], $digits)) return false;
        }
        return true;
    }
    
    private function initialize_text_file($file)
    {
        if(($OUT = Functions::file_open($file, "w"))) fclose($OUT);
    }
    
    private function clean_html($html)
    {
        $html = str_ireplace('<br />', "", $html);
        $html = str_ireplace(array("\n", "\r", "\t", "\o", "\xOB", "\11", "\011"), "", trim($html));
        return Functions::remove_whitespace($html);
    }

    private function main23()
    {
        $client_paths = array();
        $client_paths[] = '/EncyclopediaOfEarth//www.eoearth.org/topics/view/51cbfc8bf702fc2ba812cc39/index.html'; //  About the EoE
        $client_paths[] = '/EncyclopediaOfEarth//www.eoearth.org/topics/view/51cbfc78f702fc2ba8129ea2/index.html'; //  Agricultural & Resource Ec...
        $client_paths[] = '/EncyclopediaOfEarth//www.eoearth.org/topics/view/51cbfc78f702fc2ba8129e70/index.html'; //  Biodiversity
        $client_paths[] = '/EncyclopediaOfEarth//www.eoearth.org/topics/view/51cbfc64f702fc2ba8125f78/index.html'; //  Biology
        $client_paths[] = '/EncyclopediaOfEarth//www.eoearth.org/topics/view/51cbfc78f702fc2ba8129e7b/index.html'; //  Climate Change
        $client_paths[] = '/EncyclopediaOfEarth//www.eoearth.org/topics/view/51cbfc78f702fc2ba8129e5d/index.html'; //  Ecology
        $client_paths[] = '/EncyclopediaOfEarth//www.eoearth.org/topics/view/51cbfc79f702fc2ba812a064/index.html'; //  Environmental & Earth Science
        $client_paths[] = '/EncyclopediaOfEarth//www.eoearth.org/topics/view/51cbfc78f702fc2ba8129e5f/index.html'; //  Energy
        $client_paths[] = '/EncyclopediaOfEarth//www.eoearth.org/topics/view/51cbfc78f702fc2ba8129e6d/index.html'; //  Environmental Law & Policy
        $client_paths[] = '/EncyclopediaOfEarth//www.eoearth.org/topics/view/51cbfc79f702fc2ba8129f2d/index.html'; //  Environmental Humanities
        $client_paths[] = '/EncyclopediaOfEarth//www.eoearth.org/topics/view/51cbfc78f702fc2ba8129e61/index.html'; //  Food
        $client_paths[] = '/EncyclopediaOfEarth//www.eoearth.org/topics/view/51cbfc78f702fc2ba8129e7a/index.html'; //  Forests
        $client_paths[] = '/EncyclopediaOfEarth//www.eoearth.org/topics/view/51cbfc78f702fc2ba8129e5c/index.html'; //  Geography
        $client_paths[] = '/EncyclopediaOfEarth//www.eoearth.org/topics/view/51cbfc79f702fc2ba8129ec5/index.html'; //  Hazards & Disasters
        $client_paths[] = '/EncyclopediaOfEarth//www.eoearth.org/topics/view/51cbfc79f702fc2ba812a04a/index.html'; //  Health
        $client_paths[] = '/EncyclopediaOfEarth//www.eoearth.org/topics/view/51cbfc78f702fc2ba8129ea4/index.html'; //  Mining & Materials
        $client_paths[] = '/EncyclopediaOfEarth//www.eoearth.org/topics/view/51cbfc78f702fc2ba8129e86/index.html'; //  People
        $client_paths[] = '/EncyclopediaOfEarth//www.eoearth.org/topics/view/51cbfc78f702fc2ba8129ebd/index.html'; //  Physics & Chemistry
        $client_paths[] = '/EncyclopediaOfEarth//www.eoearth.org/topics/view/51cbfc78f702fc2ba8129e67/index.html'; //  Pollution
        $client_paths[] = '/EncyclopediaOfEarth//www.eoearth.org/topics/view/51cbfc79f702fc2ba8129f55/index.html'; //  Society & Environment
        $client_paths[] = '/EncyclopediaOfEarth//www.eoearth.org/topics/view/51cbfc78f702fc2ba8129e73/index.html'; //  Water
        $client_paths[] = '/EncyclopediaOfEarth//www.eoearth.org/topics/view/51cbfc79f702fc2ba8129f1c/index.html'; //  Weather & Climate
        $client_paths[] = '/EncyclopediaOfEarth//www.eoearth.org/topics/view/51cbfc79f702fc2ba812a05b/index.html'; //  Wildlife
        return $client_paths;
    }
    
    /* not being used
    private function escape_accented_chars($str)
    {
        $accented_chars = array('Š', 'š', 'Ž', 'ž', 'À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ø', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'Þ', 'ß', 
        'à', 'á', 'â', 'ã', 'ä', 'å', 'æ', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ð', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'ù', 'ú', 'û', 'ý', 'þ', 'ÿ');
        foreach($accented_chars as $char)
        {
            if(stripos($str, $char) !== false) echo "\nfound:[$char]\n";
            // $str = preg_replace($char, "\\".$char, $str, 1); //replaces just 1 occurrence
        }
        return $str;
    }
    */
    
    // private function it_points_to_PDF_or_the_like_YN($url)
    // {
    //     $extension = strtolower(pathinfo($url, PATHINFO_EXTENSION));
    //     $excluded_extensions = array("pdf", "doc", "txt", "xls", "xlsx", "ppt", "ods",
    //                                  "PDF", "DOC", "TXT", "XLS", "XLSX", "PPT", "ODS");
    //     if(in_array($extension, $excluded_extensions))
    //     {
    //         $this->debug['excluded_urls'][$url] = '';
    //         return true;
    //     }
    //     else return false;
    // }

    // private function remove_tag_attribute_from_html($html, $attrib)
    // {
    //     $dom = new \DOMDocument;                 // init new DOMDocument
    //     $dom->loadHTML($html);                  // load HTML into it
    //     $xpath = new \DOMXPath($dom);            // create a new XPath
    //     $nodes = $xpath->query('//*[@size]');  // Find elements with a style attribute
    //     foreach ($nodes as $node) 
    //     {                                       // Iterate over found elements
    //         $node->removeAttribute('size');    // Remove style attribute
    //     }
    //     return $dom->saveHTML();
    // }

    /*
    *3 diff algae pages:
    file:///opt/homebrew/var/www//EncyclopediaOfEarth/www.eoearth.org/view/article/51cbece57896bb431f68e513/index-topic=51cbfc79f702fc2ba8129ebf.html
    file:///opt/homebrew/var/www//EncyclopediaOfEarth/www.eoearth.org/view/article/51cbece57896bb431f68e513/index-topic=51cbfc64f702fc2ba8125f78.html
    file:///opt/homebrew/var/www//EncyclopediaOfEarth/www.eoearth.org/topics/view/51cbfc79f702fc2ba8129ebf/index.html
    *2 diff wildlife pages:
    file:///opt/homebrew/var/www//EncyclopediaOfEarth/www.eoearth.org/topics/view/51cbfc79f702fc2ba812a05b/index.html
    file:///opt/homebrew/var/www//EncyclopediaOfEarth/www.eoearth.org/view/article/51cbef477896bb431f69d50c/index-topic=51cbfc64f702fc2ba8125f78.html
    */

}
?>
