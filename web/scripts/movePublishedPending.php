<?php
# Moves an ID out from queue. Used if published XML was changed compared to what was in request.

//Load composer's autoloader
require_once __DIR__ . '/../html/vendor/autoload.php';

if ($argc < 2) {
  usage($argv[0]);
  exit;
}
if (is_numeric($argv[1])) {
  $id = $argv[1];
} else {
  usage($argv[0]);
  printf ("    entity id must be an integer not %s\n", $argv[1]);
  exit;
}

include __DIR__ . '/../html/include/Metadata.php'; # NOSONAR

$metadata = new \metadata\Metadata($id);
$metadata->movePublishedPending();

function usage($scriptname) {
  print "Usage:\n";
  printf("    %s <entity id>\n", $scriptname);
  print "    entity id - #id of entity that should be moved\n";
}
