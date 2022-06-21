<?php
$baseDir = dirname($_SERVER['SCRIPT_FILENAME'], 1);
include $baseDir . '/config.php';

include 'include/Html.php';
$html = new HTML($DiscoveryService);

try {
  $db = new PDO("mysql:host=$dbServername;dbname=$dbName", $dbUsername, $dbPassword);
  // set the PDO error mode to exception
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
  echo "Error: " . $e->getMessage();
}

include 'include/MetadataDisplay.php';
$display = new MetadataDisplay($baseDir);

if (isset($_GET['showEntity'])) {
	showEntity($_GET['showEntity']);
} elseif (isset($_GET['rawXML'])) {
	$display->showRawXML($_GET['rawXML']);
} else {
	showEntityList();
}

$html->showFooter($display->getCollapseIcons(),true);
# End of page

####
# Shows EntityList
####
function showEntityList() {
	global $db, $html;

	$showAll = true;

	$query = '';

	$feedOrder = 'feedDesc';
	$orgOrder = 'orgAsc';
	$entityIDOrder = 'entityIDAsc';
	$feedArrow = '';
	$orgArrow = '';
	$entityIDArrow = '';
	$ALArrow = '';
	$SIRTFIArrow = '';
	if (isset($_GET['feedDesc'])) {
		$sortOrder = '`publishIn` DESC, `entityID`';
		$sort = 'feedDesc';
		$feedOrder = 'feedAsc';
		$feedArrow = '<i class="fa fa-arrow-up"></i>';
	} elseif (isset($_GET['feedAsc'])) {
		$sortOrder = '`publishIn` ASC, `entityID`';
		$sort = 'feedAsc';
		$feedArrow = '<i class="fa fa-arrow-down"></i>';
	} elseif (isset($_GET['orgDesc'])) {
		$sortOrder = '`OrganizationDisplayName` DESC, `entityID`';
		$sort = 'orgDesc';
		$orgArrow = '<i class="fa fa-arrow-up"></i>';
	} elseif (isset($_GET['orgAsc'])) {
		$sortOrder = '`OrganizationDisplayName` ASC, `entityID`';
		$sort = 'orgAsc';
		$orgOrder = 'orgDesc';
		$orgArrow = '<i class="fa fa-arrow-down"></i>';
	} elseif (isset($_GET['entityIDDesc'])) {
		$sortOrder = '`entityID` DESC';
		$sort = 'entityIDDesc';
		$entityIDOrder = 'entityIDAsc';
		$entityIDArrow = '<i class="fa fa-arrow-up"></i>';
	} else {
		$sortOrder = '`entityID` ASC';
		$sort = 'entityIDAsc';
		$entityIDOrder = 'entityIDDesc';
		$entityIDArrow = '<i class="fa fa-arrow-down"></i>';
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
			$entitys = $db->prepare("SELECT `id`, `entityID`, `publishIn`, `data` AS OrganizationDisplayName, MAX(attribute) AS attribute FROM Entities LEFT JOIN Organization ON Organization.`entity_id` = `id` AND `element` = 'OrganizationDisplayName' AND `lang` = 'en' LEFT JOIN EntityAttributes ON EntityAttributes.entity_id=Entities.id AND type = 'assurance-certification' AND attribute LIKE '%AL%' WHERE `status` = 1 AND `isIdP` = 1 AND `entityID` LIKE :Query GROUP BY id ORDER BY `attribute` DESC, $sortOrder");
			$sort = 'AL&query='.$query;;
			$csort='AL';
			$ALArrow = '<i class="fa fa-arrow-down"></i>';
			$entityIDOrder = 'entityIDAsc';
			$entityIDArrow = '';
		} elseif (isset($_GET['SIRTFI'])) {
			$entitys = $db->prepare("SELECT `id`, `entityID`, `publishIn`, `data` AS OrganizationDisplayName, MAX(attribute) AS attribute FROM Entities LEFT JOIN Organization ON Organization.`entity_id` = `id` AND `element` = 'OrganizationDisplayName' AND `lang` = 'en' LEFT JOIN EntityAttributes ON EntityAttributes.entity_id=Entities.id AND type = 'assurance-certification' AND attribute = 'https://refeds.org/sirtfi' WHERE `status` = 1 AND `isIdP` = 1 AND `entityID` LIKE :Query GROUP BY `id` ORDER BY `attribute` DESC,  $sortOrder");
			$sort = 'SIRTFI&query='.$query;;
			$csort='SIRTFI';
			$SIRTFIArrow = '<i class="fa fa-arrow-down"></i>';
			$entityIDOrder = 'entityIDAsc';
			$entityIDArrow = '';
		} else
			$entitys = $db->prepare("SELECT `id`, `entityID`, `publishIn`, `data` AS OrganizationDisplayName FROM Entities LEFT JOIN Organization ON `entity_id` = `id` AND `element` = 'OrganizationDisplayName' AND `lang` = 'en' WHERE `status` = 1 AND `isIdP` = 1 AND `entityID` LIKE :Query ORDER BY $sortOrder");

		print "         <a href=\"./?$sort\">All in SWAMID</a> | <b>IdP in SWAMID</b> | <a href=\".?showSP&$sort\">SP in SWAMID</a> | <a href=\"/all-idp.php\">IdP via interfederation</a> | <a href=\"/all-sp.php\">SP via interfederation</a>\n";
		$extraTH = sprintf('<th><a href="?showIdP&AL">AL1%s</a></th><th><a href="?showIdP&AL">AL2%s</a></th><th><a href="?showIdP&AL">AL3%s</a></th><th><a href="?showIdP&SIRTFI">SIRTFI%s</a></th><th>Hide</th>', $ALArrow, $ALArrow, $ALArrow, $SIRTFIArrow);
		$showAll = false;
	} elseif (isset($_GET['showSP'])) {
		$html->showHeaders('Metadata SWAMID - SP:s');
		$filter = 'showSP';
		if (isset($_GET['SIRTFI'])) {
			$entitys = $db->prepare("SELECT `id`, `entityID`, `publishIn`, `data` AS OrganizationDisplayName, `attribute` FROM Entities LEFT JOIN Organization ON Organization.`entity_id` = `id` AND `element` = 'OrganizationDisplayName' AND `lang` = 'en' LEFT JOIN EntityAttributes ON EntityAttributes.entity_id=Entities.id AND type = 'assurance-certification' AND attribute = 'https://refeds.org/sirtfi' WHERE `status` = 1 AND `isSP` = 1 AND `entityID` LIKE :Query GROUP BY `id` ORDER BY `attribute` DESC, $sortOrder");
			$sort = 'SIRTFI&query='.$query;;
			$csort='SIRTFI';
			$SIRTFIArrow = '<i class="fa fa-arrow-down"></i>';
			$entityIDOrder = 'entityIDAsc';
			$entityIDArrow = '';
		} else
			$entitys = $db->prepare("SELECT `id`, `entityID`, `publishIn`, `data` AS OrganizationDisplayName FROM Entities LEFT JOIN Organization ON `entity_id` = `id` AND `element` = 'OrganizationDisplayName' AND `lang` = 'en' WHERE `status` = 1 AND `isSP` = 1 AND `entityID` LIKE :Query ORDER BY $sortOrder");
		print "         <a href=\"./?$sort\">All in SWAMID</a> | <a href=\".?showIdP&$sort\">IdP in SWAMID</a> | <b>SP in SWAMID</b> | <a href=\"/all-idp.php\">IdP via interfederation</a> | <a href=\"/all-sp.php\">SP via interfederation</a>\n";
		$extraTH = sprintf('<th>CoCo</th><th>R&S</th><th><a href="?showSP&SIRTFI">SIRTFI%s</a></th>', $SIRTFIArrow);
		$showAll = false;
	} else {
		$html->showHeaders('Metadata SWAMID - All');
		$filter = 'all';
		$entitys = $db->prepare("SELECT `id`, `entityID`, `isIdP`, `isSP`, `publishIn`, `data` AS OrganizationDisplayName FROM Entities LEFT JOIN Organization ON `entity_id` = `id` AND `element` = 'OrganizationDisplayName' AND `lang` = 'en' WHERE `status` = 1 AND `entityID` LIKE :Query ORDER BY $sortOrder");
		print "	  <b>All in SWAMID</b> | <a href=\".?showIdP&$sort\">IdP in SWAMID</a> | <a href=\".?showSP&$sort\">SP in SWAMID</a> | <a href=\"/all-idp.php\">IdP via interfederation</a> | <a href=\"/all-sp.php\">SP via interfederation</a>\n";
		$extraTH = '';
	}
	echo <<<EOF
    <table class="table table-striped table-bordered">
      <tr>
EOF;
	if ($showAll)	print '<th>IdP</th><th>SP</th>';
	printf('<th>Registered in</th> <th><a href="?%s&%s">eduGAIN%s</a></th> <th><form><a href="?%s&%s">entityID%s</a> <input type="text" name="query" value="%s"><input type="hidden" name="%s"><input type="hidden" name="%s"> <input type="submit" value="Filtrera"></form></th><th>DisplayName</th><th><a href="?%s&%s">OrganizationDisplayName%s</a></th>%s</tr>%s', $filter, $feedOrder, $feedArrow, $filter, $entityIDOrder, $entityIDArrow, $query, $csort, $filter, $filter, $orgOrder, $orgArrow, $extraTH, "\n");
	$entitys->bindValue(':Query', "%".$query."%");
	showList($entitys, $showAll);
}

####
# Shows Entity information
####
function showEntity($Entity_id)  {
	global $db, $html, $display;
	$entityHandler = $db->prepare('SELECT `entityID`, `isIdP`, `isSP`, `publishIn`, `status`, `publishedId` FROM Entities WHERE id = :Id;');
	$publishArray = array();
	$publishArrayOld = array();

	$html->setDestination('?showEntity='.$Entity_id);
	$entityHandler->bindParam(':Id', $Entity_id);
	$entityHandler->execute();
	if ($entity = $entityHandler->fetch(PDO::FETCH_ASSOC)) {
		if (($entity['publishIn'] & 2) == 2) $publishArray[] = 'SWAMID';
		if (($entity['publishIn'] & 4) == 4) $publishArray[] = 'eduGAIN';
		if (($entity['publishIn'] & 1) == 1) $publishArray[] = 'SWAMID-testing';
		if ($entity['status'] > 1 && $entity['status'] < 6) {
			if ($entity['publishedId'] > 0) {
				$entityHandlerOld = $db->prepare('SELECT `id`, `isIdP`, `isSP`, `publishIn` FROM Entities WHERE `id` = :Id AND `status` = 6;');
				$entityHandlerOld->bindParam(':Id', $entity['publishedId']);
				$headerCol2 = 'Old metadata - when requested publication';
			} else {
				$entityHandlerOld = $db->prepare('SELECT `id`, `isIdP`, `isSP`, `publishIn` FROM Entities WHERE `entityID` = :Id AND `status` = 1;');
				$entityHandlerOld->bindParam(':Id', $entity['entityID']);
				$headerCol2 = 'Old metadata - published now';
			}
			$entityHandlerOld->execute();
			if ($entityOld = $entityHandlerOld->fetch(PDO::FETCH_ASSOC)) {
				$oldEntity_id = $entityOld['id'];
				if (($entityOld['publishIn'] & 2) == 2) $publishArrayOld[] = 'SWAMID';
				if (($entityOld['publishIn'] & 4) == 4) $publishArrayOld[] = 'eduGAIN';
				if (($entityOld['publishIn'] & 1) == 1) $publishArrayOld[] = 'SWAMID-testing';
			} else {
				$oldEntity_id = 0;
			}
			switch ($entity['status']) {
				case 3:
					# Draft
					$headerCol1 = 'New metadata';
					break;
				case 4:
					# Soft Delete
					$headerCol1 = 'Deleted metadata';
					$oldEntity_id = 0;
					break;
				case 5:
					# Pending that have been published
				case 6:
					# Copy of published used to compare Pending
					$headerCol1 = 'Already published metadata (might not be the latest!)';
					$oldEntity_id = 0;
					break;
				default:
					$headerCol1 = 'Waiting for publishing';
			}
		} else {
			$headerCol1 = '';
			$oldEntity_id = 0;
		}
		$html->showHeaders('Metadata SWAMID - ' . $entity['entityID']); ?>

    <div class="row">
      <div class="col">
        <h3>entityID = <?=$entity['entityID']?></h3>
      </div>
    </div><?php $display->showStatusbar($Entity_id); ?>

    <div class="row">
      <div class="col">
        <?=($oldEntity_id > 0) ? "<h3>" . $headerCol1 . "</h3>\n" : ''; ?>
        Published in : <?php
		print (implode (', ', $publishArray));
		if ($oldEntity_id > 0) { ?>

      </div>
      <div class="col">
        <h3><?=$headerCol2?></h3>
        Published in : <?php
			print (implode (', ', $publishArrayOld));
		} ?>

      </div>
    </div><?php
		$display->showEntityAttributes($Entity_id, $oldEntity_id);
		if ($entity['isIdP'] ) $display->showIdP($Entity_id, $oldEntity_id);
		if ($entity['isSP'] ) $display->showSp($Entity_id, $oldEntity_id);
		$display->showOrganization($Entity_id, $oldEntity_id);
		$display->showContacts($Entity_id, $oldEntity_id);
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
		$hasHide = '';

		$mduiHandler->bindValue(':Id', $row['id']);
		$mduiHandler->execute();
		if ($mdui = $mduiHandler->fetch(PDO::FETCH_ASSOC)) {
			$DisplayName = $mdui['data'];
		} else {
			$DisplayName = '';
		}
		$prodFeed = ($row['publishIn'] > 1) ? true : false;
		$entityAttributesHandler->bindValue(':Id', $row['id']);
		$entityAttributesHandler->execute();
		while ($attribute = $entityAttributesHandler->fetch(PDO::FETCH_ASSOC)) {
			switch ($attribute['type']) {
				case 'entity-category' :
					switch ($attribute['attribute']) {
						case 'http://refeds.org/category/research-and-scholarship' :
							if ($prodFeed) {
								$countECrs ++;
								$isRS = 'X';
							} else
								$isRS = '(X)';
							break;
						case 'http://www.geant.net/uri/dataprotection-code-of-conduct/v1' :
							if ($prodFeed) {
								$countECcoco ++;
								$isCoco = 'X';
							} else
								$isCoco = '(X)';
							break;
						case 'http://refeds.org/category/hide-from-discovery' :
							if ($prodFeed) {
								$countHideFromDisc ++;
								$hasHide = 'X';
							} else
								$hasHide = '(X)';
							break;
					}
					break;
				case 'entity-category-support' :
					switch ($attribute['attribute']) {
						case 'http://refeds.org/category/research-and-scholarship' :
							if ($prodFeed) $countECSrs ++;
							break;
						case 'http://www.geant.net/uri/dataprotection-code-of-conduct/v1' :
							if ($prodFeed) $countECScoco ++;
							break;
					}
					break;
				case 'assurance-certification' :
					switch ($attribute['attribute']) {
						case 'http://www.swamid.se/policy/assurance/al1' :
							if ($prodFeed) {
								$countAL1 ++;
								$isAL1 = 'X';
							} else
								$isAL1 = '(X)';
							break;
						case 'http://www.swamid.se/policy/assurance/al2' :
							if ($prodFeed) {
								$countAL2 ++;
								$isAL2 = 'X';
							} else
								$isAL2 = '(X)';
							break;
						case 'http://www.swamid.se/policy/assurance/al3' :
							if ($prodFeed) {
								$countAL3 ++;
								$isAL3 = 'X';
							} else
								$isAL3 = '(X)';
							break;
						case 'https://refeds.org/sirtfi' :
							if ($prodFeed) {
								$countSIRTFI ++;
								$isSIRTFI = 'X';
							} else
								$isSIRTFI = '(X)';
							break;
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
			printf ('<td class="text-center">%s</td><td class="text-center">%s</td><td class="text-center">%s</td><td class="text-center">%s</td><td class="text-center">%s</td>', $isAL1, $isAL2, $isAL3, $isSIRTFI, $hasHide);
		} elseif (isset($_GET['showSP'])) {
			printf ('<td class="text-center">%s</td><td class="text-center">%s</td><td class="text-center">%s</td>', $isCoco, $isRS, $isSIRTFI);
		}
		print "</tr>\n";
	} ?>
    </table>
    <h4>Statistics</h4>
    <table class="table table-striped table-bordered">
      <tr><th rowspan="3">&nbsp;Registered in</th><th>SWAMID-Production</th><td><?=$countSWAMID?></td></tr>
      <tr><th>eduGAIN-Export</th><td><?=$counteduGAIN?></td></tr>
      <tr><th>SWAMID-Test only</th><td><?=$countTesting?></td></tr>
      <tr><th rowspan="3">Entity Categories in production<br><i>Excluding testing only (X)</i></th><th>CoCo </th><td><?=$countECcoco?></td></tr>
      <tr><th>R&S</th><td><?=$countECrs?></td></tr>
      <tr><th>DS-hide </th><td><?=$countHideFromDisc?></td></tr>
      <tr><th rowspan="2">Support Categorys in production<br><i>Excluding testing only (X)</i></th><th>CoCo </th><td><?=$countECScoco?></td></tr>
      <tr><th>R&S</th><td><?=$countECSrs?></td></tr>
      <tr><th rowspan="4">Assurance profiles in production<br><i>Excluding testing only (X)</i></th><th>AL1</th><td><?=$countAL1?></td></tr>
      <tr><th>AL2 </th><td><?=$countAL2?></td></tr>
      <tr><th>AL3 </th><td><?=$countAL3?></td></tr>
      <tr><th>SIRTFI </th><td><?=$countSIRTFI?></td></tr>
    </table>

<?php
}
