<script language="javascript1.2">
function proc()
{
    document.forms.fn.agent_id.options.selectedIndex=0
    document.forms.fn.submit();
}
</script>
<?php
include_once(dirname(__FILE__) . "/../../config/environment.php");
$mysqli =& $GLOBALS['mysqli_connection'];
require_library('PartnerStat');
$func = new PartnerStat();
if(isset($_REQUEST['agent_id']))$agent_id = $_REQUEST['agent_id'];
if(isset($_REQUEST['agentID']))$agent_id  = $_REQUEST['agentID'];
$with_published_content="";
if(isset($_REQUEST['with_published_content']))$with_published_content = $_REQUEST['with_published_content'];
if(!isset($agent_id)) $func->display_form($with_published_content);
else 
{
    if($agent_id)$func->process_agent_id($agent_id);
    else $func->display_form($with_published_content);
}
?>