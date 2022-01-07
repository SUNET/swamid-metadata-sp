<?php
include "/var/www/html/config.php";
include "/var/www/html/include/Metadata.php";

if ($argc < 3) {
	usage();
	exit;
}
if (is_numeric($argv[2])) {
	$count = $argv[2];
} else {
	usage();
	printf ("	entitys must be an integer not %s\n", $argv[2]);
	exit;
}

try {
	$db = new PDO("mysql:host=$dbServername;dbname=$dbName", $dbUsername, $dbPassword);
	// set the PDO error mode to exception
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
	echo "Error: " . $e->getMessage();
}

$entitys = $db->prepare("SELECT id, entityID, lastValidated FROM Entities WHERE lastValidated < :Date AND status = 1 ORDER BY lastValidated");
$entitys->bindValue(':Date', $argv[1]);
$entitys->execute();
#$entitys = $db->prepare("SELECT id, entityID, lastValidated FROM Entities");
#print $count;
while ($count > 0 && $row = $entitys->fetch(PDO::FETCH_ASSOC)) {
	printf ("%s -> entityID : %s\n",$row['lastValidated'], $row['entityID']);
	$metadata = new Metadata('/var/www/html',$row['id']);
	if ($metadata->getResult() <> "")
		printf ("%s\n" ,$metadata->getResult());
	$metadata->clearResult();
	$metadata->validateXML();
	$metadata->validateSAML();
	if ($metadata->getResult() <> "")
		printf ("\nValidate ->\n%s#\n" ,$metadata->getResult());
	#if ($metadata->getWarning() <> "")
	#	printf ("\nWarning ->\n%s\n" ,$metadata->getWarning());
	#if ($metadata->getError() <> "")
	#	printf ("\nError ->\n%s\n" ,$metadata->getError());
	$count--;
}

function usage() {
	print "Usage:\n";
	printf("	%s <Date/Time> <entitys>\n", $argv[0]);
	print "	Date/Time - Validate all entitys with lastValidatdion less than this\n";
	print "	entitys - Max nr of entitys to validate\n";
}
?>
