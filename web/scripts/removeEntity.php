<?php
	include "/var/www/metadata/include/Metadata.php";
	$metadata = new Metadata('/var/www/metadata/config.php',$argv[1],$argv[2]);
	$metadata->removeEntity();
?>

