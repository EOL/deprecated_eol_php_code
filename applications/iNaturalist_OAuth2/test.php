<form name='fn' action="http://localhost/eol_php_code/applications/BOLD2iNAT/index.php" method="post">
  <input type='text' name='inat_response' value='{"access_token":"4334a67655996f81d11b5bf8f2283c2a73f2a4afce6eb6b8dd3b70bb1199162c", "token_type":"Bearer", "scope":"write login", "created_at":1575989930}'>
  <input type='submit'>
</form>
<?php
echo "\nForwarding...\n";
?>
<script>
document.forms.fn.submit()
</script>