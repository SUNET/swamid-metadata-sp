<?php
# Check URLs where lastSeen is older than 30 days and remove if not in use in any entity
include "/var/www/html/include/Metadata.php";

$metadata = new Metadata('/var/www/html');
$metadata->checkOldURLS(30,true);
?>