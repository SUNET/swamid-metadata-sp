<?php
//Load composer's autoloader
require_once __DIR__ . '/../../../vendor/autoload.php';

$config = new \metadata\Configuration();

header('Content-type: application/json');

$organizationHandler = $config->getDb()->prepare(
  "SELECT `OrganizationInfo`.`id` AS orgId,
      `OrganizationDisplayName`, `memberSince`, `notMemberAfter`,
      COUNT(`Entities`.`id`) AS entitiesCount
    FROM `OrganizationInfoData`, `OrganizationInfo`
    LEFT JOIN `Entities` ON `Entities`.`OrganizationInfo_id` = `OrganizationInfo`.`id`
    WHERE `OrganizationInfo`.`id` = `OrganizationInfoData`.`OrganizationInfo_id` AND
      `lang` = 'en'
    GROUP BY(orgId)
    ORDER BY `OrganizationDisplayName`;");
$organizationDataHandler = $config->getDb()->prepare(
  'SELECT `lang`, `OrganizationName`, `OrganizationDisplayName`, `OrganizationURL`
    FROM `OrganizationInfoData`
    WHERE `OrganizationInfo_id` = :Id
    ORDER BY `lang`;');

$organizationHandler->execute();
while ($organization = $organizationHandler->fetch(PDO::FETCH_ASSOC)) {
  $organizationDataHandler->execute(array(\metadata\MetadataDisplay::BIND_ID => $organization['orgId']));

  $orgObj = new \stdClass();
  $orgInfoDataArray = array();

  while ($orgInfoData = $organizationDataHandler->fetch(PDO::FETCH_ASSOC)) {
    $orgInfoDataObj = new \stdClass();
    $orgInfoDataObj->OrganizationName = $orgInfoData['OrganizationName'];
    $orgInfoDataObj->OrganizationDisplayName = $orgInfoData['OrganizationDisplayName'];
    $orgInfoDataObj->OrganizationURL = $orgInfoData['OrganizationURL'];
    $orgInfoDataArray[$orgInfoData['lang']] = $orgInfoDataObj;
    unset($orgInfoDataObj);
  };
  $orgObj->id = $organization['orgId'];
  $orgObj->memberSince = $organization['memberSince'];
  $orgObj->notMemberAfter = $organization['notMemberAfter'];
  $orgObj->entitiesCount = $organization['entitiesCount'];
  $today = date("Y-m-d");
  $expired = $organization['notMemberAfter'] ? ( $today > $organization['notMemberAfter'] ) : false;
  $notYetValid = $organization['memberSince'] ? ( $today < $organization['memberSince'] ) : false;
  $orgObj->active = !$expired && !$notYetValid;
  $orgObj->organizationInfoData = $orgInfoDataArray;
  $orgsArray[] = $orgObj;
  unset($orgObj);
}
$Obj = new \stdClass();
$Obj->organizations = $orgsArray;
print json_encode($Obj);
