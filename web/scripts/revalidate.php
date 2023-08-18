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

$entitys = $db->prepare(sprintf('SELECT id, entityID FROM Entities WHERE lastValidated <  NOW() - INTERVAL :Days DAY AND status = 1 ORDER BY lastValidated LIMIT %d',$argv[2]));
$entitys->bindValue(':Days', $argv[1]);
$entitys->execute();
while ($row = $entitys->fetch(PDO::FETCH_ASSOC)) {
	printf ("Revalidating entityID : %s\n",$row['entityID']);
	$metadata = new Metadata($row['id']);
	if ($metadata->getResult() <> "")
		printf ("%s\n" ,$metadata->getResult());
	$metadata->clearResult();
	$metadata->validateXML();
	$metadata->validateSAML();
	if ($metadata->getResult() <> "")
		printf ("\nValidate ->\n%s#\n" ,$metadata->getResult());
}

function usage() {
	print "Usage:\n";
	printf("	%s <Days> <entitys>\n", $argv[0]);
	print "	Days - Validate all entitys with lastValidatdion less than this numer of days\n";
	print "	entitys - Max nr of entitys to validate\n";
}
?>
