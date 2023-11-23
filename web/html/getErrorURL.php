<?php
# Updated via puppet/cosmos to metadata.swamid.se
require_once __DIR__ . '/config.php';

try {
  $db = new PDO("mysql:host=$dbServername;dbname=$dbName", $dbUsername, $dbPassword);
  // set the PDO error mode to exception
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
  echo "Error: " . $e->getMessage();
}

print "<?php\n\n\$helpdesks = array(\n";

$entityHandler = $db->prepare("SELECT `id`, `entityID`, `publishIn` FROM Entities WHERE `status` = 1 AND isIdP = 1 ORDER BY `entityID` ASC");
$displayHandler = $db->prepare("SELECT `lang`, `data` FROM Mdui WHERE `type` = 'IDPSSO' AND `element`= 'DisplayName' AND `entity_id`= :Id ORDER BY `lang` DESC");
$contactHandler = $db->prepare("SELECT `emailAddress` FROM ContactPerson WHERE `contactType` = 'support' AND `entity_id`= :Id");
$errorUrlHandler = $db->prepare("SELECT `URL` FROM EntityURLs WHERE `type` = 'error' AND `entity_id`= :Id");
$displayHandler->bindParam(':Id', $Entity_id);
$contactHandler->bindParam(':Id', $Entity_id);
$errorUrlHandler->bindParam(':Id', $Entity_id);
$entityHandler->execute();
while ($entity = $entityHandler->fetch(PDO::FETCH_ASSOC)) {
  $Entity_id = $entity['id'];
  printf("  '%s' => array(\n    'feed' => '%s',\n    'displayname' => array(\n", $entity['entityID'], $entity['publishIn'] == 1 ? 'Testing'  : 'SWAMID');
  $displayHandler->execute();
  while ($displayName = $displayHandler->fetch(PDO::FETCH_ASSOC)) {
    printf ("      '%s' => '%s',\n", $displayName['lang'], addslashes($displayName['data']));
  }
  print "    ),\n";
  $contactHandler->execute();
  if ($contact = $contactHandler->fetch(PDO::FETCH_ASSOC)) {
    printf ("    'contactperson_email' => '%s',\n", substr($contact['emailAddress'], 7));
  }
  $errorUrlHandler->execute();
  if ($errorURL = $errorUrlHandler->fetch(PDO::FETCH_ASSOC)) {
    printf ("    'errorurl' => '%s',\n", $errorURL['URL']);
  }
  print "  ),\n";
}
print ");\n\n?>\n";
