<?php
	include "/var/www/html/include/Metadata.php";
	$metadata = new Metadata('/var/www/html/config.php',$argv[1],$argv[2]);
	$metadata->removeEntity();
?>

