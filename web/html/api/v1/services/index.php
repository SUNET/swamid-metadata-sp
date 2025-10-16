<?php
//Load composer's autoloader
require_once __DIR__ . '/../../../vendor/autoload.php';

$config = new \metadata\Configuration();

header('Content-type: application/json');

$servArray = array();

$serviceHandler = $config->getDb()->prepare(
  // Get all service marked for inclusion in service catalogue (ServiceURL recorded + marked as enabled).
  // Decorate results with Organisation displayName and URL and also Service name, description and logo from SP MDUI.
  // Limit to one result per service (as there might be multiple logos for a single language)
  // Use LEFT JOIN to cover for logos or other Mdui information missing.
  "SELECT e.id, e.entityID, si.serviceURL,
            orgname.data AS o_name, orgurl.data AS o_url,
            spname.data AS s_name, spdesc.data AS s_desc,
            logo.data as logo_url, logo.width AS logo_width, logo.height AS logo_height
       FROM Entities AS e
       JOIN ServiceInfo AS si ON e.id = si.entity_id
       LEFT JOIN Organization AS orgname on e.id = orgname.entity_id AND orgname.element = 'OrganizationDisplayName'
       LEFT JOIN Organization AS orgurl on e.id = orgurl.entity_id AND orgurl.element = 'OrganizationURL'
       LEFT JOIN Mdui AS spname ON e.id = spname.entity_id AND spname.element='DisplayName' AND spname.lang = 'en'
       LEFT JOIN Mdui AS spdesc ON e.id = spdesc.entity_id AND spdesc.element='Description' AND spdesc.lang = 'en'
       LEFT JOIN Mdui AS logo ON e.id = logo.entity_id AND logo.element='Logo' AND logo.lang='en'
       WHERE e.status = 1 AND e.isSP = 1 AND si.enabled GROUP BY id ORDER BY entityID;"
    );

$serviceHandler->execute();
while ($service = $serviceHandler->fetch(PDO::FETCH_ASSOC)) {

  $servObj = new \stdClass();

  $servObj->id = $service['id'];
  $servObj->entityID = $service['entityID'];
  $servObj->displayName = $service['s_name'];
  $servObj->description = $service['s_desc'];
  $servObj->url = $service['serviceURL'];
  $servObj->organization = $service['o_name'];
  $servObj->organizationURL = $service['o_url'];
  $servObj->logo_URL = $service['logo_url'];
  $servObj->logo_width = $service['logo_width'];
  $servObj->logo_height = $service['logo_height'];
  $servArray[] = $servObj;
  unset($servObj);
}
$Obj = new \stdClass();
$Obj->services = $servArray;
print json_encode($Obj, JSON_UNESCAPED_SLASHES);
