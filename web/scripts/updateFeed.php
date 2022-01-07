<?php
	include "/var/www/metadata/include/Metadata.php";
	$metadata = new Metadata('/var/www/metadata',$argv[1],'Prod');
	$metadata->updateFeed($argv[2]);
#	if ($metadata->getResult() <> "Updated in db")
#		printf ("Import -> %s\n" ,$metadata->getResult());
?>
