<?php
	include "/var/www/html/include/Metadata.php";
	$metadata = new Metadata($argv[1],$argv[2]);
	$metadata->move2SoftDelete();
?>