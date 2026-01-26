<?php

//Load composer's autoloader
require_once __DIR__ . '/../html/vendor/autoload.php';

function usage() {
  global $argv;
  print "Usage:\n";
  printf("    %s [--dry-run|--help]\n", $argv[0]);
  print "    Attempt to assign each entity with no OrganizationInfo link to\n";
  print "    an OrganizationInfo object matching the entity's OrganizationName.\n";
  print "    --dry-run: do not make any changes to the database, just show what would be done.\n";
}

$config = new \metadata\Configuration();

$doUpdate = true;

if ($argc > 1) {
  if ($argc > 2) {
    print "Too many arguments!\n";
    usage();
    exit (1);
  }
  switch ($argv[1]) {
    case '--dry-run':
      $doUpdate = false;
      break;
    case '--help':
      usage();
      exit (0);
    default:
      printf("Invalid argument: %s\n", $argv[1]);
      usage();
      exit(1);
  }
}

// prepare lookup of OrganizationInfo_id by OrganizationName
$orgInfoHandler  = $config->getDb()->prepare("SELECT `OrganizationInfo_id`
    FROM OrganizationInfoData
    WHERE OrganizationName = :Name AND lang='en'");
$org_name = '';
$orgInfoHandler->bindParam(':Name', $org_name);

// prepare update Entity with OrganizationInfo_id
$updateEntitiesHandler = $config->getDb()->prepare('UPDATE Entities SET `OrganizationInfo_id` = :OrgId WHERE `id` = :Id;');
$org_id = 0;
$entity_id = 0;
$updateEntitiesHandler->bindParam(':OrgId', $org_id);
$updateEntitiesHandler->bindParam(':Id', $entity_id);

$orgInfoHandler  = $config->getDb()->prepare("SELECT `OrganizationInfo_id`
    FROM OrganizationInfoData
    WHERE OrganizationName = :Name AND lang='en'");
$org_name = '';
$orgInfoHandler->bindParam(':Name', $org_name);

// Run within a transaction
if (!$config->getDb()->beginTransaction()) {
   print "Could not start DB transaction\n";
   exit(1);
}

$entitiesHandler = $config->getDb()->prepare("SELECT e.`id`, e.`entityID`, e.`OrganizationInfo_id`, o.`data`
    FROM Entities AS e
    LEFT JOIN Organization AS o ON e.id = o.entity_id AND o.element='OrganizationName' AND o.lang='en'
    WHERE COALESCE(e.`OrganizationInfo_id`, 0) = 0
      AND e.`status` = 1
    ORDER BY e.`id`");
$entitiesHandler->execute();

while ($entity = $entitiesHandler->fetch(PDO::FETCH_ASSOC)) {
  printf("Entity %s (%d) has no OrganisationInfo link\n", $entity['entityID'], $entity['id']);
  $org_name = $entity['data'];
  if (!$org_name) {
     print "  Entity has no OrganizationName, cannot match to OrganizationInfo\n";
     continue;
  }
  // look up OrganizationInfo_id
  $orgInfoHandler->execute();
  if ($org_info = $orgInfoHandler->fetch(PDO::FETCH_ASSOC)) {
    printf("  Found OrganizationInfo_id %d for OrganizationName %s\n", $org_info['OrganizationInfo_id'], $org_name);
    if ($doUpdate) {
      $org_id = $org_info['OrganizationInfo_id'];
      $entity_id = $entity['id'];
      $updateEntitiesHandler->execute();
    }
  } else {
    printf("  No OrganizationInfo_id found for OrganizationName %s\n", $org_name);
  }
}

if (!$config->getDb()->commit()) {
   print "Could not commit DB transaction\n";
   exit(1);
}
