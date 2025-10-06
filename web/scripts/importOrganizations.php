<?php

function usage() {
  global $argv;
  print "Usage:\n";
  printf("    %s [--use-id] organisations-file.json\n", $argv[0]);
  print "    Load a JSON file in the same format as produced by the organisations API\n";
  print "    and import into the database.\n";
  print "    Passing '-' as file name will use stdin instead.\n\n";
  print "    With --use-id, also reuse the IDs of the organisation\n";
  print "    (otherwise let the DB auto-assign).\n";
}

//Load composer's autoloader
require_once __DIR__ . '/../html/vendor/autoload.php';

$config = new \metadata\Configuration();

if ( $argc <= 1 || ($argv[1] == '--use-id' && $argc <= 2 ) ) {
   usage();
   exit;
}

$use_id = $argv[1] == '--use-id';
$filename = $argv[ $use_id ? 2 : 1];
$raw_json_data = file_get_contents($filename == "-" ? 'php://stdin' : $filename);
$organizations = json_decode(json: $raw_json_data, flags: JSON_THROW_ON_ERROR)->organizations;

// Run the import within a transaction
if (!$config->getDb()->beginTransaction()) {
   print "Could not start DB transaction\n";
   exit(1);
}
// Prepare variables and handler for OrganizationInfo records
$org_id = 0;
$memberSince = '';
$notMemberAfter = '';

$orgHandler = $config->getDb()->prepare('INSERT INTO OrganizationInfo
    (' . ( $use_id ? 'id, ' : '') . 'memberSince, notMemberAfter)
  VALUES
    (' . ($use_id ? ':id ,' : '') . ':memberSince, :notMemberAfter)');
if ($use_id) {
  $orgHandler->bindParam(':id', $org_id);
};
$orgHandler->bindParam(':memberSince', $memberSince);
$orgHandler->bindParam(':notMemberAfter', $notMemberAfter);

// Prepare variables and handler for OrganizationInfoData records
$org_lang = '';
$org_name = '';
$org_displayName = '';
$org_url = '';

$orgInfoDataHandler = $config->getDb()->prepare('INSERT INTO OrganizationInfoData
    (OrganizationInfo_id, lang, OrganizationName, OrganizationDisplayName, OrganizationURL)
  VALUES
    (:OrgInfo_id, :lang, :OrgName, :OrgDisplayName, :OrgURL)');
$orgInfoDataHandler->bindParam(':OrgInfo_id', $org_id);
$orgInfoDataHandler->bindParam(':lang', $org_lang);
$orgInfoDataHandler->bindParam(':OrgName', $org_name);
$orgInfoDataHandler->bindParam(':OrgDisplayName', $org_displayName);
$orgInfoDataHandler->bindParam(':OrgURL', $org_url);


foreach ($organizations as $org) {
  if ($org->active) {
    printf("Found org %d\n", $org->id);
    if ($use_id) {
      $org_id = $org->id;
    };
    $memberSince = $org->memberSince;
    $notMemberAfter = property_exists($org, 'notMemberAfter') ? $org->notMemberAfter : null;
    $orgHandler->execute();

    if (!$use_id) {
      $org_id = $config->getDb()->lastInsertId();
      printf("org got id %d\n", $org_id);
    };

    foreach ($org->organizationInfoData as $lang => $orgInfoData) {
        $org_lang = $lang;
        $org_name = $orgInfoData->OrganizationName;
        $org_displayName = $orgInfoData->OrganizationDisplayName;
        $org_url = $orgInfoData->OrganizationURL;
        $orgInfoDataHandler->execute();
    }
  }
}

if (!$config->getDb()->commit()) {
   print "Could not commit DB transaction\n";
   exit(1);
}
