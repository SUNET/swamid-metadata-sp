<?php
// file deepcode ignore FileInclusion:
include __DIR__ . '/../html/config.php'; # NOSONAR
// file deepcode ignore FileInclusion:
include __DIR__ . '/../html/include/Metadata.php'; # NOSONAR

try {
  $db = new PDO("mysql:host=$dbServername;dbname=$dbName", $dbUsername, $dbPassword);
  // set the PDO error mode to exception
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
  echo "Error Connecting DB";
}

$entitiesHandler = $db->prepare(
  'SELECT `id`, `entityID` FROM Entities WHERE `status` = 2 ORDER BY lastUpdated ASC, `entityID`');
$entitiesHandler->execute();
while ($pendingEntity = $entitiesHandler->fetch(PDO::FETCH_ASSOC)) {
  $metadata = new Metadata($pendingEntity['id']);
  if ($metadata->checkPendingIfPublished()) {
    $metadata->movePublishedPending();
    printf ("Cleanup: %s removed from Pending\n", $pendingEntity['entityID']);
  } else {
    printf ("Cleanup: Keeping %s in Pending\n", $pendingEntity['entityID']);
  }
}
