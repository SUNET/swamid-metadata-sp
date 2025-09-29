<?php

function usage() {
  global $argv;
  print "Usage:\n";
  printf("    %s service-file.json\n", $argv[0]);
  print "    Load a JSON file in the same format as produced by the Services API\n";
  print "    and mport service URLs into the database.\n";
  print "    Passing '-' as file name will use stdin instead.\n\n";
}

//Load composer's autoloader
require_once __DIR__ . '/../html/vendor/autoload.php';

$config = new \metadata\Configuration();

if ( $argc <= 1 ) {
   usage();
   exit;
}

$filename = $argv[1];
$raw_json_data = file_get_contents($filename == "-" ? 'php://stdin' : $filename);
$services = json_decode(json: $raw_json_data, flags: JSON_THROW_ON_ERROR)->services;

// Run the import within a transaction
if (!$config->getDb()->beginTransaction()) {
   print("Could not start DB transaction\n");
   exit(1);
}

foreach ($services as $service) {
  $metadata = new \metadata\Metadata($service->entityID, 'Prod');
  if ($metadata->id()) {
    printf("Storing service URL %s for %s into %d\n",
        $service->url, $service->entityID, $metadata->id());
    $metadata->storeServiceInfo($metadata->id(), $service->url, 1);
  } else {
    printf("Unknown entityID %s\n", $service->entityID);
  }
}

if (!$config->getDb()->commit()) {
   print("Could not commit DB transaction\n");
   exit(1);
}
