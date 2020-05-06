<?php
namespace php_active_record;
class WikiHTMLAPI
{
    function __construct()
    {
    }
    private function get_tag_name($html) //set to public during development
    {
        $arr = explode(" ", $html);
        $tag = $arr[0];
        return $tag; //e.g. "<div" or "<table"
    }
    public function get_real_coverage($left, $html)
    {
        $final = array();
        while($html = self::main_real_coverage($left, $html)) {
            if($html) $final[] = $html;
        }
        if($final) return end($final);
        return false;
    }
    public function process_needle($html, $needle, $multipleYN = false) //not multiple process, but only one search of a needle
    {
        $orig = $html;
        if($tmp = $this->get_pre_tag_entry($html, $needle)) {
            $left = $tmp . $needle;
            $html = self::process_left($html, $left);

            if($multipleYN) { //a little dangerous, if not used properly it will nuke the html
                // /* should work but it nukes html
                if($orig == $html) return $html;
                else $html = self::process_needle($html, $needle, true);
                // */
            }
            else return $html; //means single process only
        }
        return $html;
    }
    public function process_left($html, $left)
    {
        if($val = self::get_real_coverage($left, $html)) $html = $val; //get_real_coverage() assumes that html has balanced open and close tags.
        return $html;
    }
    private function main_real_coverage($left, $html)
    {
        //1st: get tag name 
        $tag = self::get_tag_name($left);
        if(!$tag) return false;
        // echo "\ntag_name: [$tag]\n";
        
        //2nd get pos of $left
        $pos = strpos($html, $left);
        if($pos === false) return false;
        // echo "\npos of left: [$pos]\n";
        // echo "\n".substr($html, $pos, 10)."\n"; //debug
        
        //3rd: get ending tag of $tag
        $ending_tag = self::get_ending_tag($tag); // e.g. </table>
        // echo "\nending_tag: [$ending_tag]\n";
        
        //4th: initialize vars
        $open_tag = 1;
        $close_tag = 0;
        $len = strlen($tag);
        
        //5th: start moving substr in search of open_tag (<table) and close_tag (</table>)
        $start_pos = $pos+1;
        while($open_tag >= 1) {
            $str = substr($html, $start_pos, $len);
            // echo "\nopen: [$str]";
            if($str == $tag) $open_tag++;
            
            $str2 = substr($html, $start_pos, $len+2);
            // echo "\nclose: [$str2]";
            if($str2 == "") return false; //meaning the html does not have a balance open and close tags. Invalid html structure.
            if($str2 == $ending_tag) $open_tag--;
            
            if($open_tag < 1) break;
            $start_pos++;
        }
        // echo "\nfinal open: [$str]";
        // echo "\nfinal close: [$str2]";
        
        //6th get substr of entire coverage
        $num = $start_pos + ($len+2);
        $num = $num - $pos;
        $final = substr($html, $pos, $num);
        return str_replace($final, '', $html);
    }
    private function get_ending_tag($tag)
    {   //e.g. "<table"
        return str_replace("<", "</", $tag). ">";
    }
    public function process_external_links($html, $id)
    {
        $left = '<span class="mw-headline" id="'.$id.'"'; $right = '<!--';
        return $this->remove_all_in_between_inclusive($left, $right, $html, false);
    }
    public function during_dev()
    {   $arr = false;
        //lang ce
        if($this->debug_taxon == "Solanum tuberosum")       {$arr = $this->get_object('Q10998'); $arr = $arr->entities->Q10998;}
        if($this->debug_taxon == "Gruidae")                 {$arr = $this->get_object('Q25365'); $arr = $arr->entities->Q25365;}
        if($this->debug_taxon == "Cyanistes caeruleus")     {$arr = $this->get_object('Q25404'); $arr = $arr->entities->Q25404;}
        if($this->debug_taxon == "Haliaeetus albicilla")    {$arr = $this->get_object('Q25438'); $arr = $arr->entities->Q25438;}
        if($this->debug_taxon == "Lynx lynx")               {$arr = $this->get_object('Q43375'); $arr = $arr->entities->Q43375;}
        if($this->debug_taxon == "Ferula assa-foetida")     {$arr = $this->get_object('Q111185'); $arr = $arr->entities->Q111185;}
        if($this->debug_taxon == "Milvus milvus")           {$arr = $this->get_object('Q156250'); $arr = $arr->entities->Q156250;}
        if($this->debug_taxon == "Gratiola")                {$arr = $this->get_object('Q159388'); $arr = $arr->entities->Q159388;}
        if($this->debug_taxon == "Circaetus gallicus")      {$arr = $this->get_object('Q170251'); $arr = $arr->entities->Q170251;}
        if($this->debug_taxon == "Gyps fulvus")             {$arr = $this->get_object('Q177856'); $arr = $arr->entities->Q177856;}
        //lang yo
        if($this->debug_taxon == "Microlophus habelii")         {$arr = $this->get_object('Q3020789'); $arr = $arr->entities->Q3020789;}
        if($this->debug_taxon == "Hesperentomidae")             {$arr = $this->get_object('Q3040549'); $arr = $arr->entities->Q3040549;}
        if($this->debug_taxon == "Paranisentomon")              {$arr = $this->get_object('Q4495001'); $arr = $arr->entities->Q4495001;}
        if($this->debug_taxon == "Silvestridia")                {$arr = $this->get_object('Q4548129'); $arr = $arr->entities->Q4548129;}
        if($this->debug_taxon == "Crocodylus niloticus")        {$arr = $this->get_object('Q168745'); $arr = $arr->entities->Q168745;}
        if($this->debug_taxon == "Necrosyrtes monachus")        {$arr = $this->get_object('Q177386'); $arr = $arr->entities->Q177386;}
        if($this->debug_taxon == "Pandion haliaetus")           {$arr = $this->get_object('Q25332'); $arr = $arr->entities->Q25332;}
        if($this->debug_taxon == "Manihot esculenta")           {$arr = $this->get_object('Q83124'); $arr = $arr->entities->Q83124;}
        if($this->debug_taxon == "Crocuta crocuta")             {$arr = $this->get_object('Q178089'); $arr = $arr->entities->Q178089;}
        if($this->debug_taxon == "Microlophus theresioides")    {$arr = $this->get_object('Q3006767'); $arr = $arr->entities->Q3006767;}
        //lang kv
        if($this->debug_taxon == "Rhinocerotidae")              {$arr = self::get_object('Q34718'); $arr = $arr->entities->Q34718;}
        if($this->debug_taxon == "Sciuridae")                   {$arr = self::get_object('Q9482'); $arr = $arr->entities->Q9482;}
        if($this->debug_taxon == "Vaccinium subg. Oxycoccus")   {$arr = self::get_object('Q13181'); $arr = $arr->entities->Q13181;}
        if($this->debug_taxon == "Litchi chinensis")            {$arr = self::get_object('Q13182'); $arr = $arr->entities->Q13182;}
        //lang vls
        if($this->debug_taxon == "Actinopterygii")              {$arr = self::get_object('Q127282'); $arr = $arr->entities->Q127282;}
        if($this->debug_taxon == "Esox lucius")                 {$arr = self::get_object('Q165278'); $arr = $arr->entities->Q165278;}
        //lang co
        if($this->debug_taxon == "Gallinula chloropus")         {$arr = self::get_object('Q18847'); $arr = $arr->entities->Q18847;}
        if($this->debug_taxon == "Cornales")                    {$arr = self::get_object('Q21769'); $arr = $arr->entities->Q21769;}
        if($this->debug_taxon == "Proteales")                   {$arr = self::get_object('Q21838'); $arr = $arr->entities->Q21838;}
        if($this->debug_taxon == "Felidae")                     {$arr = self::get_object('Q25265'); $arr = $arr->entities->Q25265;}
        if($this->debug_taxon == "Tyto alba")                   {$arr = self::get_object('Q25317'); $arr = $arr->entities->Q25317;}
        if($this->debug_taxon == "Prunus dulcis")               {$arr = self::get_object('Q39918'); $arr = $arr->entities->Q39918;}
        if($this->debug_taxon == "Gagea minima")                {$arr = self::get_object('Q159444'); $arr = $arr->entities->Q159444;}
        if($this->debug_taxon == "Clematis vitalba")            {$arr = self::get_object('Q160100'); $arr = $arr->entities->Q160100;}
        if($this->debug_taxon == "Hyoscyamus niger")            {$arr = self::get_object('Q161058'); $arr = $arr->entities->Q161058;}
        //lang mi
        if($this->debug_taxon == "Hydroprogne caspia")          {$arr = self::get_object('Q27129'); $arr = $arr->entities->Q27129;}
        if($this->debug_taxon == "Fabaceae")                    {$arr = self::get_object('Q44448'); $arr = $arr->entities->Q44448;}
        if($this->debug_taxon == "Xenicus gilviventris")        {$arr = self::get_object('Q135589'); $arr = $arr->entities->Q135589;}
        if($this->debug_taxon == "Knightia excelsa")            {$arr = self::get_object('Q311623'); $arr = $arr->entities->Q311623;}
        if($this->debug_taxon == "Metrosideros excelsa")        {$arr = self::get_object('Q311747'); $arr = $arr->entities->Q311747;}
        if($this->debug_taxon == "Pterodroma macroptera")       {$arr = self::get_object('Q313391'); $arr = $arr->entities->Q313391;}
        if($this->debug_taxon == "Dacrydium cupressinum")       {$arr = self::get_object('Q382469'); $arr = $arr->entities->Q382469;}
        if($this->debug_taxon == "Himantopus novaezelandiae")   {$arr = self::get_object('Q686269'); $arr = $arr->entities->Q686269;}
        if($this->debug_taxon == "Callaeas cinereus")           {$arr = self::get_object('Q760949'); $arr = $arr->entities->Q760949;}
        if($this->debug_taxon == "Agathis australis")           {$arr = self::get_object('Q955413'); $arr = $arr->entities->Q955413;}
        if($this->debug_taxon == "Calamus baratangensis")       {$arr = self::get_object('Q15458835'); $arr = $arr->entities->Q15458835;}
        //lang mdf
        if($this->debug_taxon == "Adenoncos")               {$arr = self::get_object('Q20818'); $arr = $arr->entities->Q20818;}
        if($this->debug_taxon == "Ancistrochilus")          {$arr = self::get_object('Q20957'); $arr = $arr->entities->Q20957;}
        if($this->debug_taxon == "Acanthephippium")         {$arr = self::get_object('Q21118'); $arr = $arr->entities->Q21118;}
        if($this->debug_taxon == "Cucumis sativus")         {$arr = self::get_object('Q23425'); $arr = $arr->entities->Q23425;}
        if($this->debug_taxon == "Solanum lycopersicum")    {$arr = self::get_object('Q23501'); $arr = $arr->entities->Q23501;}
        if($this->debug_taxon == "Brassavola")              {$arr = self::get_object('Q94815'); $arr = $arr->entities->Q94815;}
        if($this->debug_taxon == "Convolvulus arvensis")    {$arr = self::get_object('Q111346'); $arr = $arr->entities->Q111346;}
        //lang to
        if($this->debug_taxon == "Orchidaceae")             {$arr = self::get_object('Q25308'); $arr = $arr->entities->Q25308;}
        if($this->debug_taxon == "Onychoprion anaethetus")  {$arr = self::get_object('Q28490'); $arr = $arr->entities->Q28490;}
        if($this->debug_taxon == "Senna alata")             {$arr = self::get_object('Q41504'); $arr = $arr->entities->Q41504;}
        if($this->debug_taxon == "Curcuma longa")           {$arr = self::get_object('Q42562'); $arr = $arr->entities->Q42562;}
        if($this->debug_taxon == "Anisoptera")              {$arr = self::get_object('Q80066'); $arr = $arr->entities->Q80066;}
        if($this->debug_taxon == "Solanum")                 {$arr = self::get_object('Q146555'); $arr = $arr->entities->Q146555;}
        if($this->debug_taxon == "Euphorbia")               {$arr = self::get_object('Q146567'); $arr = $arr->entities->Q146567;}
        //lang kbd
        if($this->debug_taxon == "Aegolius funereus")     {$arr = self::get_object('Q174466'); $arr = $arr->entities->Q174466;}
        if($this->debug_taxon == "Plegadis falcinellus")  {$arr = self::get_object('Q178811'); $arr = $arr->entities->Q178811;}
        if($this->debug_taxon == "Turdus viscivorus")     {$arr = self::get_object('Q178942'); $arr = $arr->entities->Q178942;}
        if($this->debug_taxon == "Meropidae")             {$arr = self::get_object('Q183147'); $arr = $arr->entities->Q183147;}
        if($this->debug_taxon == "Anser")                 {$arr = self::get_object('Q183361'); $arr = $arr->entities->Q183361;}
        if($this->debug_taxon == "Prunus")                {$arr = self::get_object('Q190545'); $arr = $arr->entities->Q190545;}
        if($this->debug_taxon == "Cairina moschata")      {$arr = self::get_object('Q242851'); $arr = $arr->entities->Q242851;}
        //lang tg
        if($this->debug_taxon == "Jasminum")              {$arr = self::get_object('Q82014'); $arr = $arr->entities->Q82014;}
        //lang mt
        if($this->debug_taxon == "Scandentia")              {$arr = self::get_object('Q231550'); $arr = $arr->entities->Q231550;}
        if($this->debug_taxon == "Peramelemorphia")         {$arr = self::get_object('Q244587'); $arr = $arr->entities->Q244587;}
        if($this->debug_taxon == "Ochotona nubrica")        {$arr = self::get_object('Q311579'); $arr = $arr->entities->Q311579;}
        if($this->debug_taxon == "Ochotona nigritia")       {$arr = self::get_object('Q311599'); $arr = $arr->entities->Q311599;}
        if($this->debug_taxon == "Tupaia belangeri")        {$arr = self::get_object('Q378959'); $arr = $arr->entities->Q378959;}
        if($this->debug_taxon == "Ameridelphia")            {$arr = self::get_object('Q384427'); $arr = $arr->entities->Q384427;}
        if($this->debug_taxon == "Lepus castroviejoi")      {$arr = self::get_object('Q430175'); $arr = $arr->entities->Q430175;}
        if($this->debug_taxon == "Minusculodelphis")        {$arr = self::get_object('Q539208'); $arr = $arr->entities->Q539208;}
        if($this->debug_taxon == "Euarchontoglires")        {$arr = self::get_object('Q471797'); $arr = $arr->entities->Q471797;}
        if($this->debug_taxon == "Lepus granatensis")       {$arr = self::get_object('Q513373'); $arr = $arr->entities->Q513373;}
        if($this->debug_taxon == "Pronolagus rupestris")    {$arr = self::get_object('Q536459'); $arr = $arr->entities->Q536459;}
        if($this->debug_taxon == "Nesolagus timminsi")      {$arr = self::get_object('Q564479'); $arr = $arr->entities->Q564479;}
        if($this->debug_taxon == "Antechinus")              {$arr = self::get_object('Q650656'); $arr = $arr->entities->Q650656;}
        // lang or
        if($this->debug_taxon == "Chiroptera")                  {$arr = self::get_object('Q28425'); $arr = $arr->entities->Q28425;}
        if($this->debug_taxon == "Octopoda")                    {$arr = self::get_object('Q40152'); $arr = $arr->entities->Q40152;}
        if($this->debug_taxon == "Piper nigrum")                {$arr = self::get_object('Q43084'); $arr = $arr->entities->Q43084;}
        if($this->debug_taxon == "Canis latrans")               {$arr = self::get_object('Q44299'); $arr = $arr->entities->Q44299;}
        if($this->debug_taxon == "Dromaius novaehollandiae")    {$arr = self::get_object('Q93208'); $arr = $arr->entities->Q93208;}
        if($this->debug_taxon == "Saltopus")                    {$arr = self::get_object('Q132859'); $arr = $arr->entities->Q132859;}
        if($this->debug_taxon == "Bruhathkayosaurus matleyi")   {$arr = self::get_object('Q132867'); $arr = $arr->entities->Q132867;}
        if($this->debug_taxon == "Eucalyptus globulus")         {$arr = self::get_object('Q159528'); $arr = $arr->entities->Q159528;}
        if($this->debug_taxon == "Mentha arvensis")             {$arr = self::get_object('Q160585'); $arr = $arr->entities->Q160585;}
        if($this->debug_taxon == "Oxalis corniculata")          {$arr = self::get_object('Q162795'); $arr = $arr->entities->Q162795;}
        if($this->debug_taxon == "Jasminum auriculatum")        {$arr = self::get_object('Q623492'); $arr = $arr->entities->Q623492;}
        // general lang
        if($this->debug_taxon == "Lepidoptera")        {$arr = self::get_object('Q28319'); $arr = $arr->entities->Q28319;}
        if($this->debug_taxon == "Mammalia")           {$arr = self::get_object('Q7377'); $arr = $arr->entities->Q7377;}
        // lang bh
        if($this->debug_taxon == "Psilopogon viridis")  {$arr = self::get_object('Q27074836'); $arr = $arr->entities->Q27074836;}
        if($this->debug_taxon == "Gracupica contra")    {$arr = self::get_object('Q27075597'); $arr = $arr->entities->Q27075597;}
        if($this->debug_taxon == "Pyrus")               {$arr = self::get_object('Q434'); $arr = $arr->entities->Q434;}
        if($this->debug_taxon == "Turdus merula")       {$arr = self::get_object('Q25234'); $arr = $arr->entities->Q25234;}
        if($this->debug_taxon == "Cygnus olor")         {$arr = self::get_object('Q25402'); $arr = $arr->entities->Q25402;}
        if($this->debug_taxon == "Aurelia aurita")      {$arr = self::get_object('Q26864'); $arr = $arr->entities->Q26864;}
        if($this->debug_taxon == "Pittidae")            {$arr = self::get_object('Q217472'); $arr = $arr->entities->Q217472;}
        if($this->debug_taxon == "Syzygium cumini")     {$arr = self::get_object('Q232571'); $arr = $arr->entities->Q232571;}
        if($this->debug_taxon == "Porzana parva")       {$arr = self::get_object('Q270680'); $arr = $arr->entities->Q270680;}
        // lang myv
        if($this->debug_taxon == "Secale cereale")      {$arr = self::get_object('Q12099'); $arr = $arr->entities->Q12099;}
        if($this->debug_taxon == "Lacertilia")          {$arr = self::get_object('Q15879'); $arr = $arr->entities->Q15879;}
        if($this->debug_taxon == "Pyrrhula pyrrhula")   {$arr = self::get_object('Q25382'); $arr = $arr->entities->Q25382;}
        // lang bar
        if($this->debug_taxon == "Cyphotilapia frontosa")   {$arr = self::get_object('Q285838'); $arr = $arr->entities->Q285838;}
        if($this->debug_taxon == "Lacerta viridis")         {$arr = self::get_object('Q307047'); $arr = $arr->entities->Q307047;}
        if($this->debug_taxon == "Jacaranda")               {$arr = self::get_object('Q311105'); $arr = $arr->entities->Q311105;}
        if($this->debug_taxon == "Pseudotropheus")          {$arr = self::get_object('Q311686'); $arr = $arr->entities->Q311686;}
        if($this->debug_taxon == "Dryocopus pileatus")      {$arr = self::get_object('Q930712'); $arr = $arr->entities->Q930712;}
        if($this->debug_taxon == "Cupressus ×leylandii")    {$arr = self::get_object('Q1290970'); $arr = $arr->entities->Q1290970;}
        if($this->debug_taxon == "Vespa crabro")            {$arr = self::get_object('Q30258'); $arr = $arr->entities->Q30258;}
        if($this->debug_taxon == "Corvus")                  {$arr = self::get_object('Q43365'); $arr = $arr->entities->Q43365;}
        if($this->debug_taxon == "Prunus spinosa")          {$arr = self::get_object('Q129018'); $arr = $arr->entities->Q129018;}
        if($this->debug_taxon == "Rupicapra rupicapra")     {$arr = self::get_object('Q131340'); $arr = $arr->entities->Q131340;}
        //lang nap
        if($this->debug_taxon == "Malvaceae")               {$arr = self::get_object('Q156551'); $arr = $arr->entities->Q156551;}
        if($this->debug_taxon == "Quercus cerris")          {$arr = self::get_object('Q157277'); $arr = $arr->entities->Q157277;}
        if($this->debug_taxon == "Turdus iliacus")          {$arr = self::get_object('Q184825'); $arr = $arr->entities->Q184825;}
        if($this->debug_taxon == "Conger conger")           {$arr = self::get_object('Q212552'); $arr = $arr->entities->Q212552;}
        if($this->debug_taxon == "Merlangius merlangus")    {$arr = self::get_object('Q273083'); $arr = $arr->entities->Q273083;}
        if($this->debug_taxon == "Quercus ilex")            {$arr = self::get_object('Q218155'); $arr = $arr->entities->Q218155;}
        if($this->debug_taxon == "Octopus vulgaris")        {$arr = self::get_object('Q651361'); $arr = $arr->entities->Q651361;}
        if($this->debug_taxon == "Brassica ruvo")           {$arr = self::get_object('Q702282'); $arr = $arr->entities->Q702282;}
        if($this->debug_taxon == "Ruta")                    {$arr = self::get_object('Q165250'); $arr = $arr->entities->Q165250;}
        if($this->debug_taxon == "Lacerta bilineata")       {$arr = self::get_object('Q739025'); $arr = $arr->entities->Q739025;}
        //lang vo
        if($this->debug_taxon == "Peloneustes")             {$arr = self::get_object('Q242046'); $arr = $arr->entities->Q242046;}
        if($this->debug_taxon == "Odontorhynchus")          {$arr = self::get_object('Q5030514'); $arr = $arr->entities->Q5030514;}
        if($this->debug_taxon == "Carcharodontosaurus")     {$arr = self::get_object('Q14431'); $arr = $arr->entities->Q14431;}
        if($this->debug_taxon == "Sus scrofa domesticus")   {$arr = self::get_object('Q787'); $arr = $arr->entities->Q787;}
        if($this->debug_taxon == "Vulpes vulpes")           {$arr = self::get_object('Q8332'); $arr = $arr->entities->Q8332;}
        if($this->debug_taxon == "Bacteria")                {$arr = self::get_object('Q10876'); $arr = $arr->entities->Q10876;}
        if($this->debug_taxon == "Camelotia")               {$arr = self::get_object('Q29014894'); $arr = $arr->entities->Q29014894;}
        if($this->debug_taxon == "Saurischia")              {$arr = self::get_object('Q186334'); $arr = $arr->entities->Q186334;}
        if($this->debug_taxon == "Theropoda")               {$arr = self::get_object('Q188438'); $arr = $arr->entities->Q188438;}
        if($this->debug_taxon == "Ichthyopterygia")         {$arr = self::get_object('Q2583869'); $arr = $arr->entities->Q2583869;}
        if($this->debug_taxon == "Sanctacaris uncata")      {$arr = self::get_object('Q2718204'); $arr = $arr->entities->Q2718204;}
        if($this->debug_taxon == "Zea mays")                {$arr = self::get_object('Q11575'); $arr = $arr->entities->Q11575;}
        if($this->debug_taxon == "Glycine max")             {$arr = self::get_object('Q11006'); $arr = $arr->entities->Q11006;}
        if($this->debug_taxon == "Keichosaurus")            {$arr = self::get_object('Q2375167'); $arr = $arr->entities->Q2375167;}
        if($this->debug_taxon == "Amiskwia")                {$arr = self::get_object('Q15104428'); $arr = $arr->entities->Q15104428;}
        //lang stq
        if($this->debug_taxon == "Rhinolophus euryale")     {$arr = self::get_object('Q282863'); $arr = $arr->entities->Q282863;}
        if($this->debug_taxon == "Molossidae")              {$arr = self::get_object('Q737399'); $arr = $arr->entities->Q737399;}
        if($this->debug_taxon == "Eumops")                  {$arr = self::get_object('Q371577'); $arr = $arr->entities->Q371577;}
        if($this->debug_taxon == "Microtus pennsylvanicus") {$arr = self::get_object('Q1765085'); $arr = $arr->entities->Q1765085;}
        if($this->debug_taxon == "Cichorium intybus")       {$arr = self::get_object('Q2544599'); $arr = $arr->entities->Q2544599;}
        //lang ha
        if($this->debug_taxon == "Allium cepa")             {$arr = self::get_object('Q23485'); $arr = $arr->entities->Q23485;}
        if($this->debug_taxon == "Crocodilia")              {$arr = self::get_object('Q25363'); $arr = $arr->entities->Q25363;}
        if($this->debug_taxon == "Columba livia")           {$arr = self::get_object('Q42326'); $arr = $arr->entities->Q42326;}
        if($this->debug_taxon == "Nycticorax nycticorax")   {$arr = self::get_object('Q126216'); $arr = $arr->entities->Q126216;}
        if($this->debug_taxon == "Salvadora persica")       {$arr = self::get_object('Q143525'); $arr = $arr->entities->Q143525;}
        if($this->debug_taxon == "Opuntia ficus-indica")    {$arr = self::get_object('Q144412'); $arr = $arr->entities->Q144412;}
        if($this->debug_taxon == "Ardeola ralloides")       {$arr = self::get_object('Q191394'); $arr = $arr->entities->Q191394;}
        if($this->debug_taxon == "Ziziphus mucronata")      {$arr = self::get_object('Q207081'); $arr = $arr->entities->Q207081;}
        if($this->debug_taxon == "Naja nigricollis")        {$arr = self::get_object('Q386619'); $arr = $arr->entities->Q386619;}
        if($this->debug_taxon == "Cola acuminata")          {$arr = self::get_object('Q522881'); $arr = $arr->entities->Q522881;}
        //lang lbe
        if($this->debug_taxon == "Capra cylindricornis")    {$arr = self::get_object('Q854788'); $arr = $arr->entities->Q854788;}
        if($this->debug_taxon == "Mazama")                  {$arr = self::get_object('Q911770'); $arr = $arr->entities->Q911770;}
        if($this->debug_taxon == "Annona purpurea")         {$arr = self::get_object('Q2101601'); $arr = $arr->entities->Q2101601;}
        if($this->debug_taxon == "Juglans")                 {$arr = self::get_object('Q2453469'); $arr = $arr->entities->Q2453469;}
        if($this->debug_taxon == "Allium sativum")          {$arr = self::get_object('Q23400'); $arr = $arr->entities->Q23400;}
        if($this->debug_taxon == "Erinaceidae")             {$arr = self::get_object('Q28257'); $arr = $arr->entities->Q28257;}
        if($this->debug_taxon == "Pongo")                   {$arr = self::get_object('Q41050'); $arr = $arr->entities->Q41050;}
        if($this->debug_taxon == "Talpidae")                {$arr = self::get_object('Q104825'); $arr = $arr->entities->Q104825;}
        if($this->debug_taxon == "Athene noctua")           {$arr = self::get_object('Q129958'); $arr = $arr->entities->Q129958;}
        if($this->debug_taxon == "Rupicapra rupicapra")     {$arr = self::get_object('Q131340'); $arr = $arr->entities->Q131340;}
        //lang lij
        if($this->debug_taxon == "Anas platyrhynchos")      {$arr = self::get_object('Q25348'); $arr = $arr->entities->Q25348;}
        if($this->debug_taxon == "Perissodactyla")          {$arr = self::get_object('Q25374'); $arr = $arr->entities->Q25374;}
        if($this->debug_taxon == "Sus scrofa")              {$arr = self::get_object('Q58697'); $arr = $arr->entities->Q58697;}
        if($this->debug_taxon == "Citrus")                  {$arr = self::get_object('Q81513'); $arr = $arr->entities->Q81513;}
        if($this->debug_taxon == "Mespilus germanica")      {$arr = self::get_object('Q146186'); $arr = $arr->entities->Q146186;}
        if($this->debug_taxon == "Canis")                   {$arr = self::get_object('Q149892'); $arr = $arr->entities->Q149892;}
        if($this->debug_taxon == "Sarcopterygii")           {$arr = self::get_object('Q160830'); $arr = $arr->entities->Q160830;}
        if($this->debug_taxon == "Dipnoi")                  {$arr = self::get_object('Q168422'); $arr = $arr->entities->Q168422;}
        if($this->debug_taxon == "Hemichordata")            {$arr = self::get_object('Q174301'); $arr = $arr->entities->Q174301;}
        if($this->debug_taxon == "Lemur catta")             {$arr = self::get_object('Q185385'); $arr = $arr->entities->Q185385;}
        //lang ace
        if($this->debug_taxon == "Anura")                   {$arr = self::get_object('Q53636'); $arr = $arr->entities->Q53636;}
        if($this->debug_taxon == "Istiophorus")             {$arr = self::get_object('Q127497'); $arr = $arr->entities->Q127497;}
        if($this->debug_taxon == "Bubulcus ibis")           {$arr = self::get_object('Q132669'); $arr = $arr->entities->Q132669;}
        if($this->debug_taxon == "Senna alexandrina")       {$arr = self::get_object('Q132675'); $arr = $arr->entities->Q132675;}
        if($this->debug_taxon == "Typha angustifolia")      {$arr = self::get_object('Q146572'); $arr = $arr->entities->Q146572;}
        if($this->debug_taxon == "Metroxylon sagu")         {$arr = self::get_object('Q164088'); $arr = $arr->entities->Q164088;}
        if($this->debug_taxon == "Cananga odorata")         {$arr = self::get_object('Q220963'); $arr = $arr->entities->Q220963;}
        if($this->debug_taxon == "Geopelia striata")        {$arr = self::get_object('Q288485'); $arr = $arr->entities->Q288485;}
        if($this->debug_taxon == "Lutjanus vitta")          {$arr = self::get_object('Q302516'); $arr = $arr->entities->Q302516;}
        if($this->debug_taxon == "Phyllanthus emblica")     {$arr = self::get_object('Q310050'); $arr = $arr->entities->Q310050;}
        if($this->debug_taxon == "Lantana camara")          {$arr = self::get_object('Q332469'); $arr = $arr->entities->Q332469;}
        if($this->debug_taxon == "Epinephelus coioides")    {$arr = self::get_object('Q591397'); $arr = $arr->entities->Q591397;}
        if($this->debug_taxon == "Channa striata")          {$arr = self::get_object('Q686439'); $arr = $arr->entities->Q686439;}
        if($this->debug_taxon == "Sandoricum koetjape")     {$arr = self::get_object('Q913452'); $arr = $arr->entities->Q913452;}
        if($this->debug_taxon == "Sesbania grandiflora")    {$arr = self::get_object('Q947251'); $arr = $arr->entities->Q947251;}

        return $arr;
    }
}
/* testing nuke html
php update_resources/connectors/wikipedia.php _ 'it' generate_resource_force _ _ _ 'Acacia'
php update_resources/connectors/wikipedia.php _ 'it' generate_resource_force _ _ _ 'Bald Eagle'
php update_resources/connectors/wikipedia.php _ 'it' generate_resource_force _ _ _ 'Rosa'
php update_resources/connectors/wikipedia.php _ 'it' generate_resource_force _ _ _ 'Fungi'
php update_resources/connectors/wikipedia.php _ 'it' generate_resource_force _ _ _ 'Aves'
php update_resources/connectors/wikipedia.php _ 'it' generate_resource_force _ _ _ 'Panthera tigris'
php update_resources/connectors/wikipedia.php _ 'it' generate_resource_force _ _ _ 'sunflower'
php update_resources/connectors/wikipedia.php _ 'it' generate_resource_force _ _ _ 'Hominidae'
php update_resources/connectors/wikipedia.php _ 'it' generate_resource_force _ _ _ 'Coronaviridae'
php update_resources/connectors/wikipedia.php _ 'it' generate_resource_force _ _ _ 'Tracheophyta'

php update_resources/connectors/wikipedia.php _ 'en' generate_resource_force _ _ _ 'Anura'
php update_resources/connectors/wikipedia.php _ 'en' generate_resource_force _ _ _ 'Istiophorus'
php update_resources/connectors/wikipedia.php _ 'en' generate_resource_force _ _ _ 'Bubulcus ibis'
php update_resources/connectors/wikipedia.php _ 'en' generate_resource_force _ _ _ 'Senna alexandrina'
php update_resources/connectors/wikipedia.php _ 'en' generate_resource_force _ _ _ 'Typha angustifolia'
php update_resources/connectors/wikipedia.php _ 'en' generate_resource_force _ _ _ 'Metroxylon sagu'
php update_resources/connectors/wikipedia.php _ 'en' generate_resource_force _ _ _ 'Cananga odorata'
php update_resources/connectors/wikipedia.php _ 'en' generate_resource_force _ _ _ 'Geopelia striata'
php update_resources/connectors/wikipedia.php _ 'en' generate_resource_force _ _ _ 'Lutjanus vitta'
php update_resources/connectors/wikipedia.php _ 'en' generate_resource_force _ _ _ 'Phyllanthus emblica'

php update_resources/connectors/wikipedia.php _ 'es' generate_resource_force _ _ _ 'Chanos chanos'
php update_resources/connectors/wikipedia.php _ 'es' generate_resource_force _ _ _ 'Oreochromis niloticus'
php update_resources/connectors/wikipedia.php _ 'es' generate_resource_force _ _ _ 'Polar bear'
php update_resources/connectors/wikipedia.php _ 'es' generate_resource_force _ _ _ 'Angiosperms'
php update_resources/connectors/wikipedia.php _ 'es' generate_resource_force _ _ _ 'Mus musculus'
php update_resources/connectors/wikipedia.php _ 'es' generate_resource_force _ _ _ 'Rodentia'
php update_resources/connectors/wikipedia.php _ 'es' generate_resource_force _ _ _ 'Animalia'
php update_resources/connectors/wikipedia.php _ 'es' generate_resource_force _ _ _ 'Plantae'
php update_resources/connectors/wikipedia.php _ 'es' generate_resource_force _ _ _ 'Virus'
php update_resources/connectors/wikipedia.php _ 'es' generate_resource_force _ _ _ 'ferns'
*/
?>