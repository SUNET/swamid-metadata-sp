<?php
include "/var/www/html/include/Metadata.php";
include "/var/www/html/include/NormalizeXML.php";

$import = new NormalizeXML();
$import->fromFile($argv[1]);
if ($import->getStatus()) {
	$entityID=$import->getEntityID();
	printf ("%s\n",$entityID);
	#print $import->getXML();
	$metadata = new Metadata('/var/www/html',$import->getEntityID(),'Prod');
	$metadata->importXML($import->getXML());
	$metadata->updateFeed($argv[2]);
	$metadata->updateLastUpdated($argv[3]);

	if ($metadata->getResult() <> "Updated in db")
		printf ("Import -> %s\n" ,$metadata->getResult());
	$metadata->clearResult();
	$metadata->validateXML();
	$metadata->validateSAML();
	if ($metadata->getResult() <> "")
		printf ("\nValidate ->\n%s#\n" ,$metadata->getResult());
	if ($metadata->getWarning() <> "")
		printf ("\nWarning ->\n%s\n" ,$metadata->getWarning());
	if ($metadata->getError() <> "")
		printf ("\nError ->\n%s\n" ,$metadata->getError());
} else
	print ($import->getError());
?>
