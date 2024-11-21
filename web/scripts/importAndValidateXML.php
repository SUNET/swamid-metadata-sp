<?php
//Load composer's autoloader
require_once __DIR__ . '/../html/vendor/autoload.php';

include __DIR__ . '/../html/include/Metadata.php'; # NOSONAR
include __DIR__ . '/../html/include/NormalizeXML.php'; # NOSONAR

$import = new NormalizeXML();
$import->fromFile($argv[1]);
if ($import->getStatus()) {
  $entityID=$import->getEntityID();
  printf ("%s\n",$entityID);
  $metadata = new Metadata($import->getEntityID(),'Prod');
  $metadata->importXML($import->getXML());
  $metadata->updateFeed($argv[2]);

  if ($metadata->getResult() <> "Updated in db") {
    printf ("Import -> %s\n" ,$metadata->getResult());
  }
  $metadata->clearResult();
  $metadata->clearWarning();
  $metadata->clearError();
  $metadata->validateXML();
  $metadata->validateSAML();
  if ($metadata->getResult() <> "") {
    printf ("\nValidate ->\n%s#\n" ,$metadata->getResult());
  }
  if ($metadata->getWarning() <> "") {
    printf ("\nWarning ->\n%s\n" ,$metadata->getWarning());
  }
  if ($metadata->getError() <> "") {
    printf ("\nError ->\n%s\n" ,$metadata->getError());
  }
} else {
  print $import->getError();
}
