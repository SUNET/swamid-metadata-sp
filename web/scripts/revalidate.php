<?php
//Load composer's autoloader
require_once __DIR__ . '/../html/vendor/autoload.php';

$config = new metadata\Configuration();

include __DIR__ . '/../html/include/Metadata.php'; #NOSONAR

if ($argc < 3) {
  usage();
  exit;
}
if (! is_numeric($argv[2])) {
  usage();
  printf ("    entities must be an integer not %s\n", $argv[2]);
  exit;
}

$entities = $config->getDb()->prepare(sprintf(
  'SELECT id, entityID FROM Entities
  WHERE lastValidated <  NOW() - INTERVAL :Days DAY AND status = 1
  ORDER BY lastValidated LIMIT %d',$argv[2]));
$entities->bindValue(':Days', $argv[1]);
$entities->execute();
while ($row = $entities->fetch(PDO::FETCH_ASSOC)) {
  printf ("Revalidating entityID : %s\n",$row['entityID']);
  $metadata = new Metadata($row['id']);
  if ($metadata->getResult() <> "") {
    printf ("%s\n" ,$metadata->getResult());
  }
  $metadata->clearResult();
  $metadata->clearWarning();
  $metadata->clearError();
  $metadata->validateXML();
  $metadata->validateSAML();
  if ($metadata->getResult() <> "") {
    printf ("\nValidate ->\n%s#\n" ,$metadata->getResult());
  }
}

function usage() {
  global $argv;
  print "Usage:\n";
  printf("    %s <Days> <entities>\n", $argv[0]);
  print "    Days - Validate all entities with lastValidation less than this number of days\n";
  print "    entities - Max nr of entities to validate\n";
}
