<?php
//Load composer's autoloader
require_once __DIR__ . '/../html/vendor/autoload.php';

include __DIR__ . '/../html/include/Metadata.php'; # NOSONAR
include __DIR__ . '/../html/include/NormalizeXML.php'; # NOSONAR

$config = new metadata\Configuration();

$import = new NormalizeXML();
$import->fromFile($argv[1]);
if ($import->getStatus()) {
  $entityID=$import->getEntityID();
  printf ("%s\n",$entityID);
  $metadata = new Metadata($import->getEntityID(),'Prod');
  $metadata->importXML($import->getXML());
  $metadata->updateFeed($argv[2]);

  $xmlParser = class_exists('\metadata\ParseXML'.$config->getFederation()['extend']) ?
    '\metadata\ParseXML'.$config->getFederation()['extend'] :
    '\metadata\ParseXML';
  $samlValidator = class_exists('\metadata\Validate'.$config->getFederation()['extend']) ?
    '\metadata\Validate'.$config->getFederation()['extend'] :
    '\metadata\Validate';

  $parser = new $xmlParser($metadata->id());

  if ($parser->getResult() <> "Updated in db") {
    printf ("Import -> %s\n" ,$parser->getResult());
  }

  $parser->clearWarning();
  $parser->clearError();
  $parser->parseXML();
  $validator = new $samlValidator($metadata->id());
  $validator->saml();
  $validator->validateURLs();

  if ($validator->getResult() <> "") {
    printf ("\nValidate ->\n%s#\n" ,$validator->getResult());
  }
  if ($validator->getWarning() <> "") {
    printf ("\nWarning ->\n%s\n" ,$validator->getWarning());
  }
  if ($validator->getError() <> "") {
    printf ("\nError ->\n%s\n" ,$validator->getError());
  }
} else {
  print $import->getError();
}
