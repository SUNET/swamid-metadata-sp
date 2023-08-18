<?php
$baseDir = dirname($_SERVER['PHP_SELF'], 2) . '/html';
# Check URLs where lastSeen is older than 30 days and remove if not in use in any entity
// deepcode ignore FileInclusion:
include "$baseDir/include/Metadata.php";

// deepcode ignore FileInclusion:
$metadata = new Metadata();
$metadata->checkOldURLS(30,true);
?>