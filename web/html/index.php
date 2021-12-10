<?php
include 'include/Html.php';
$html = new HTML();

$configFile = dirname($_SERVER['SCRIPT_FILENAME'], 1) . '/config.php' ;
include $configFile;

try {
  $db = new PDO("mysql:host=$dbServername;dbname=$dbName", $dbUsername, $dbPassword);
  // set the PDO error mode to exception
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
  echo "Error: " . $e->getMessage();
}

$collapseIcons = [];

include 'include/MetadataDisplay.php';
$display = new MetadataDisplay($configFile);

if (isset($_GET['showEntity'])) {
	showEntity($_GET['showEntity']);
} elseif (isset($_GET['rawXML'])) {
	$display->showRawXML($_GET['rawXML']);
} else {
	showEntityList();
}

$html->showFooter($display->getCollapseIcons());
# End of page

####
# Shows EntityList
####
function showEntityList() {
	global $db, $html;

	$showAll = true;
	$sortOrder = 'entityID';
	$sort = 'entityID';
	$query = '';
	if (isset($_GET['feed'])) {
		$sortOrder = 'publishIn DESC, entityID';
		$sort = 'feed';
	}

	if (isset($_GET['org'])) {
		$sortOrder = 'OrganizationDisplayName, entityID';
		$sort = 'org';
	}

	if (isset($_GET['query'])) {
		$query = $_GET['query'];
	}
	$csort=$sort;
	$sort .= '&query='.$query;
	
	if (isset($_GET['showIdP'])) {
		$html->showHeaders('Metadata SWAMID - IdP:s');
		$filter = 'showIdP';
		if (isset($_GET['AL'])) {
			$entitys = $db->prepare("SELECT id, entityID, publishIn, data AS OrganizationDisplayName FROM Entities LEFT JOIN Organization ON Organization.entity_id = id AND element = 'OrganizationDisplayName' AND lang = 'en' LEFT JOIN EntityAttributes ON EntityAttributes.entity_id=Entities.id AND type = 'assurance-certification' AND attribute LIKE '%AL%' WHERE status = 1 AND isIdP = 1 AND entityID LIKE :Query ORDER BY $sortOrder");
			$sort = 'AL&query='.$query;;
			$csort='AL';
		} else
			$entitys = $db->prepare("SELECT id, entityID, publishIn, data AS OrganizationDisplayName FROM Entities LEFT JOIN Organization ON entity_id = id AND element = 'OrganizationDisplayName' AND lang = 'en' WHERE status = 1 AND isIdP = 1 AND entityID LIKE :Query ORDER BY $sortOrder");

		print "         <a href=\"./?$sort\">Alla i SWAMID</a> | <b>IdP i SWAMID</b> | <a href=\".?showSP&$sort\">SP i SWAMID</a> | <a href=\"https://metadata.swamid.se/all-idp.html\">IdP via interfederation</a> | <a href=\"https://metadata.swamid.se/all-sp.html\">SP via interfederation</a>\n";
		$extraTH = '<th>AL1</th><th>AL2</th><th><a href="?showIdP&AL">AL3</a></th><th>SIRTFI</th>';
		$showAll = false;
	} elseif (isset($_GET['showSP'])) {
		$html->showHeaders('Metadata SWAMID - SP:s');
		$filter = 'showSP';
		$entitys = $db->prepare("SELECT id, entityID, publishIn, data AS OrganizationDisplayName FROM Entities LEFT JOIN Organization ON entity_id = id AND element = 'OrganizationDisplayName' AND lang = 'en' WHERE status = 1 AND isSP = 1 AND entityID LIKE :Query ORDER BY $sortOrder");
		print "         <a href=\"./?$sort\">Alla i SWAMID</a> | <a href=\".?showIdP&$sort\">IdP i SWAMID</a> | <b>SP i SWAMID</b> | <a href=\"https://metadata.swamid.se/all-idp.html\">IdP via interfederation</a> | <a href=\"https://metadata.swamid.se/all-sp.html\">SP via interfederation</a>\n";
		$extraTH = '<th>CoCo</th><th>R&S</th><th>SIRTFI</th>';
		$showAll = false;
	} else {
		$html->showHeaders('Metadata SWAMID - All');
		$filter = 'all';
		$entitys = $db->prepare("SELECT id, entityID, isIdP, isSP, publishIn, data AS OrganizationDisplayName FROM Entities LEFT JOIN Organization ON entity_id = id AND element = 'OrganizationDisplayName' AND lang = 'en' WHERE status = 1 AND entityID LIKE :Query ORDER BY $sortOrder");
		print "	  <b>Alla i SWAMID</b> | <a href=\".?showIdP&$sort\">IdP i SWAMID</a> | <a href=\".?showSP&$sort\">SP i SWAMID</a> | <a href=\"https://metadata.swamid.se/all-idp.html\">IdP via interfederation</a> | <a href=\"https://metadata.swamid.se/all-sp.html\">SP via interfederation</a>\n";
		$extraTH = '';
	}
	echo <<<EOF
    <table class="table table-striped table-bordered">
      <tr>
EOF;
	if ($showAll)	print '<th>IdP</th><th>SP</th>';

	echo <<<EOF
 <th>Registrerad i</th> <th><a href="?$filter&feed">eduGAIN</a></th> <th><form><a href="?$filter&entityID">entityID</a> <input type="text" name="query" value="$query"><input type="hidden" name="$csort"><input type="hidden" name="$filter"> <input type="submit" value="Filtrera"></form></th><th>DisplayName</th><th><a href="?$filter&org">OrganizationDisplayName</a></th>
EOF;
	print $extraTH . "</tr>\n";
	$entitys->bindValue(':Query', "%".$query."%");
	showList($entitys, $showAll);
}

####
# Shows Entity information
####
function showEntity($Entity_id)  {
	global $db, $html, $display;
	$entityHandler = $db->prepare('SELECT entityID, isIdP, isSP, publishIn FROM Entities WHERE id = :Id;');
	$publishArray = array();

	$entityHandler->bindParam(':Id', $Entity_id);
	$entityHandler->execute();
	if ($entity = $entityHandler->fetch(PDO::FETCH_ASSOC)) {
		if (($entity['publishIn'] & 2) == 2) $publishArray[] = 'SWAMID';
		if (($entity['publishIn'] & 4) == 4) $publishArray[] = 'eduGAIN';
		if (($entity['publishIn'] & 1) == 1) $publishArray[] = 'SWAMID-testing';
		$html->showHeaders('Metadata SWAMID - ' . $entity['entityID']); ?>
    <div class="row">
      <div class="col">
        <h3>entityID = <?=$entity['entityID']?></h3>
      </div>
    </div>
    <div class="row">
      <div class="col">
        Published in : <?=implode (', ', $publishArray)?><br>
      </div>
    </div>
    <br><?php
		$display->showStatusbar($Entity_id);
		$display->showEntityAttributes($Entity_id);
		if ($entity['isIdP'] ) $display->showIdP($Entity_id);
		if ($entity['isSP'] ) $display->showSp($Entity_id);
		$display->showOrganization($Entity_id);
		$display->showContacts($Entity_id);
		$display->showXML($Entity_id);
	} else {
		$html->showHeaders('Metadata SWAMID - NotFound');
		print "Can't find Entity";
	}
}

####
# Shows a list of entitys
####
function showList($entitys, $showRole) {
	global $db;
	$entityAttributesHandler = $db->prepare('SELECT * FROM EntityAttributes WHERE entity_id = :Id;');
	$mduiHandler = $db->prepare("SELECT data FROM Mdui WHERE element = 'DisplayName' AND entity_id = :Id ORDER BY type,lang;");

	$countSWAMID = 0;
	$counteduGAIN = 0;
	$countTesting = 0;
	$countECcoco = 0;
	$countECrs = 0;
	$countHideFromDisc = 0;
	$countECScoco = 0;
	$countECSrs = 0;
	$countAL1 = 0;
	$countAL2 = 0;
	$countAL3 = 0;
	$countSIRTFI = 0;

	$entitys->execute();
	while ($row = $entitys->fetch(PDO::FETCH_ASSOC)) {
		$isAL1 = '';
		$isAL2 = '';
		$isAL3 = '';
		$isSIRTFI = '';
		$isCoco = '';
		$isRS = '';

		$mduiHandler->bindValue(':Id', $row['id']);
		$mduiHandler->execute();
		if ($mdui = $mduiHandler->fetch(PDO::FETCH_ASSOC)) {
			$DisplayName = $mdui['data'];
		} else {
			$DisplayName = '';
		}
		if ($row['publishIn'] > 1) {
			$entityAttributesHandler->bindValue(':Id', $row['id']);
			$entityAttributesHandler->execute();
			while ($attribute = $entityAttributesHandler->fetch(PDO::FETCH_ASSOC)) {
				switch ($attribute['type']) {
					case 'entity-category' :
						switch ($attribute['attribute']) {
							case 'http://refeds.org/category/research-and-scholarship' :
								$countECrs ++;
								$isRS = 'X';
								break;
							case 'http://www.geant.net/uri/dataprotection-code-of-conduct/v1' :
								$countECcoco ++;
								$isCoco = 'X';
								break;
							case 'http://refeds.org/category/hide-from-discovery' :
								$countHideFromDisc ++;
								break;
						}
						break;
					case 'entity-category-support' :
						switch ($attribute['attribute']) {
							case 'http://refeds.org/category/research-and-scholarship' :
								$countECSrs ++;
								break;
							case 'http://www.geant.net/uri/dataprotection-code-of-conduct/v1' :
								$countECScoco ++;
								break;
						}
						break;
					case 'assurance-certification' :
						switch ($attribute['attribute']) {
							case 'http://www.swamid.se/policy/assurance/al1' :
								$countAL1 ++;
								$isAL1 = 'X';
								break;
							case 'http://www.swamid.se/policy/assurance/al2' :
								$countAL2 ++;
								$isAL2 = 'X';
								break;
							case 'http://www.swamid.se/policy/assurance/al3' :
								$countAL3 ++;
								$isAL3 = 'X';
								break;
							case 'https://refeds.org/sirtfi' :
								$countSIRTFI ++;
								$isSIRTFI = 'X';
								break;
						}
				}
			}
		}
		printf ('      <tr>');
		if ($showRole) {
			print $row['isIdP'] ? '<td class="text-center">X</td>' : '<td></td>';
			print $row['isSP'] ? '<td class="text-center">X</td>' : '<td></td>';
		}
		switch ($row['publishIn']) {
			case 1 :
				$countTesting ++;
				$registerdIn = 'Test-only';
				$export2Edugain = '';
				break;
			case 3 :
				$countSWAMID ++;
				//$countTesting ++;
				$registerdIn = 'SWAMID';
				$export2Edugain = '';
				break;
			case 7 :
				$countSWAMID ++;
				$counteduGAIN ++;
				//$countTesting ++;
				$registerdIn = 'SWAMID';
				$export2Edugain = 'X';
				break;
			default :
				$registerdIn = '';
				$export2Edugain = '';


		}
		printf ('<td class="text-center">%s</td><td class="text-center">%s</td><td><a href=".?showEntity=%s">%s</a></td><td>%s</td><td>%s</td>', $registerdIn, $export2Edugain, $row['id'], $row['entityID'], $DisplayName, $row['OrganizationDisplayName']);

		if (isset($_GET['showIdP'])) {
			printf ('<td class="text-center">%s</td><td class="text-center">%s</td><td class="text-center">%s</td><td class="text-center">%s</td>', $isAL1, $isAL2, $isAL3, $isSIRTFI);
		} elseif (isset($_GET['showSP'])) {
			printf ('<td class="text-center">%s</td><td class="text-center">%s</td><td class="text-center">%s</td>', $isCoco, $isRS, $isSIRTFI);
		}
		print "</tr>\n";
	} ?>
    </table>
    <h4>Statistik</h4>
    <table class="table table-striped table-bordered">
      <tr><th rowspan="3">&nbsp;Registrerad i </th><th> SWAMID-Produktion </th> <td> <?=$countSWAMID?> </td></tr>
      <tr><th> eduGAIN-Export </th> <td> <?=$counteduGAIN?> </td></tr>
      <tr><th> SWAMID-Test enbart </th> <td> <?=$countTesting?> </td></tr>
      <tr><th rowspan="3"> Entitetskategorier i produktion </th><th> CoCo </th> <td> <?=$countECcoco?> </td></tr>
      <tr><th> R&S</th> <td><?=$countECrs?> </td></tr>
      <tr><th> DS-hide </th> <td> <?=$countHideFromDisc?> </td></tr>
      <tr><th rowspan="2"> Supportkategorier i produktion</th><th> CoCo </th> <td> <?=$countECScoco?> </td></tr>
      <tr><th> R&S</th> <td><?=$countECSrs?> </td></tr>
      <tr><th rowspan="4"> Tillitsprofiler i produktion</th><th> AL1</th> <td><?=$countAL1?> </td></tr>
      <tr><th> AL2 </th> <td> <?=$countAL2?> </td></tr>
      <tr><th> AL3 </th> <td> <?=$countAL3?> </td></tr>
      <tr><th> SIRTFI </th> <td> <?=$countSIRTFI?> </td></tr>
    </table>

<?php
}
