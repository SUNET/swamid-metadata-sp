<?php
// Import PHPMailer classes into the global namespace
// These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

//Load composer's autoloader
require 'vendor/autoload.php';

$baseDir = dirname($_SERVER['SCRIPT_FILENAME'], 1);
include $baseDir . '/config.php';

include 'include/Html.php';
$html = new HTML($DiscoveryService, $Mode);

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
}elseif (isset($_GET['showEntityID'])) {
	showEntity($_GET['showEntityID'],true);
} elseif (isset($_GET['rawXML'])) {
	$display->showRawXML($_GET['rawXML']);
} elseif (isset($_GET['rawXMLEntityID'])) {
	$display->showRawXML($_GET['rawXMLEntityID'], true);
} elseif (isset(($_GET['approveAccessRequest']))) {
	approveAccessRequest($_GET['approveAccessRequest']);
} elseif (isset($_GET['show'])) {
	switch($_GET['show']) {
		case 'Pending' :
			showPendingQueue();
			break;
		case 'Info' :
			showInfo();
			break;
		case 'InterIdP' :
			showInterfederation('IDP');
			break;
		case 'InterSP' :
			showInterfederation('SP');
			break;
		case 'feed' :
			if (isset($_GET['id']))
				showFeed($_GET['id']);
			break;
		default :
			showEntityList($_GET['show']);
	}
} else {
	showInfo();
}

$html->showFooter($display->getCollapseIcons(),true);
# End of page

####
# Shows EntityList
####
function showEntityList($show) {
	global $db, $html;

	$query = isset($_GET['query']) ? $_GET['query'] : '';

	switch ($show) {
		case 'IdP' :
			$html->showHeaders('Metadata SWAMID - IdP:s');
			$entitys = $db->prepare("SELECT `id`, `entityID`, `publishIn`, `data` AS OrganizationName FROM Entities LEFT JOIN Organization ON `entity_id` = `id` AND `element` = 'OrganizationName' AND `lang` = 'en' WHERE `status` = 1 AND `isIdP` = 1 AND `entityID` LIKE :Query ORDER BY `entityID` ASC");
			showMenu('IdPs', $query);
			$extraTH = sprintf('<th>AL1</a></th><th>AL2</a></th><th>AL3</a></th><th>SIRTFI</a></th><th>Hide</th>');
			break;
		case 'SP' :
			$html->showHeaders('Metadata SWAMID - SP:s');
			$entitys = $db->prepare("SELECT `id`, `entityID`, `publishIn`, `data` AS OrganizationName FROM Entities LEFT JOIN Organization ON `entity_id` = `id` AND `element` = 'OrganizationName' AND `lang` = 'en' WHERE `status` = 1 AND `isSP` = 1 AND `entityID` LIKE :Query ORDER BY `entityID` ASC");
			showMenu('SPs', $query);
			$extraTH = sprintf('<th>Anon</th><th>Pseuso</th><th>Pers</th><th>CoCo v1</th><th>CoCo v2</th><th>R&S</th><th>ESI</th><th>SIRTFI</th>');
			break;
		case 'All' :
			$html->showHeaders('Metadata SWAMID - All');
			$entitys = $db->prepare("SELECT `id`, `entityID`, `isIdP`, `isSP`, `publishIn`, `data` AS OrganizationName FROM Entities LEFT JOIN Organization ON `entity_id` = `id` AND `element` = 'OrganizationName' AND `lang` = 'en' WHERE `status` = 1 AND `entityID` LIKE :Query ORDER BY `entityID` ASC");
			showMenu('all', $query);
			$extraTH = '<th>IdP</th><th>SP</th>';
			break;
		default :
			$html->showHeaders('Metadata SWAMID - Error');
			print 'Show what ??????';
			return;
	}
	printf ('    <div class="table-responsive"><table id="entities-table" class="table table-striped table-bordered">%s      <thead><tr><th><form>entityID <input type="text" name="query" value="%s"><input type="hidden" name="show" value="%s"><input type="submit" value="Filter"></form></th><th>Published in</th><th>DisplayName</th><th>OrganizationName</th>%s</tr></thead>%s'
		, "\n", $query, $show, $extraTH, "\n");
	$html->addTableSort('entities-table');
	$entitys->bindValue(':Query', "%".$query."%");
	showList($entitys, $show);
}

####
# Shows menu row
####
function showMenu($menuActive, $query = '') {
	print "\n    ";
	$query = $query == '' ? '' : '&query=' . $query;
	printf('<a href="./?show=All%s"><button type="button" class="btn btn%s-primary">All in SWAMID</button></a>', $query, $menuActive == 'all' ? '' : '-outline');
	printf('<a href="./?show=IdP%s"><button type="button" class="btn btn%s-primary">IdP in SWAMID</button></a>', $query, $menuActive == 'IdPs' ? '' : '-outline');
	printf('<a href="./?show=SP%s"><button type="button" class="btn btn%s-primary">SP in SWAMID</button></a>', $query, $menuActive == 'SPs' ? '' : '-outline');
	printf('<a href="./?show=InterIdP"><button type="button" class="btn btn%s-primary">IdP via interfederation</button></a>', $menuActive == 'fedIdPs' ? '' : '-outline');
	printf('<a href="./?show=InterSP"><button type="button" class="btn btn%s-primary">SP via interfederation</button></a>', $menuActive == 'fedSPs' ? '' : '-outline');
	printf('<a href="./?show=Info%s"><button type="button" class="btn btn%s-primary">Info</button></a>', $query, $menuActive == 'info' ? '' : '-outline');
	print "\n";
}

####
# Shows Entity information
####
function showEntity($Entity_id, $URN = false)  {
	global $db, $html, $display;
	$entityHandler = $URN ? $db->prepare('SELECT `id`, `entityID`, `isIdP`, `isSP`, `publishIn`, `status`, `publishedId` FROM Entities WHERE entityID = :Id AND status = 1;') : $db->prepare('SELECT `id`, `entityID`, `isIdP`, `isSP`, `publishIn`, `status`, `publishedId` FROM Entities WHERE id = :Id;');
	$publishArray = array();
	$publishArrayOld = array();

	$entityHandler->bindParam(':Id', $Entity_id);
	$entityHandler->execute();
	if ($entity = $entityHandler->fetch(PDO::FETCH_ASSOC)) {
		$Entities_id = $entity['id'];
		$html->setDestination('?showEntity='.$Entities_id);
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
					//$oldEntity_id = 0;
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
    </div><?php $display->showStatusbar($Entities_id); ?>

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
		$display->showEntityAttributes($Entities_id, $oldEntity_id);
		if ($entity['isIdP'] ) $display->showIdP($Entities_id, $oldEntity_id);
		if ($entity['isSP'] ) $display->showSp($Entities_id, $oldEntity_id);
		$display->showOrganization($Entities_id, $oldEntity_id);
		$display->showContacts($Entities_id, $oldEntity_id);
		$display->showXML($Entities_id);
	} else {
		$html->showHeaders('Metadata SWAMID - NotFound');
		print "Can't find Entity";
	}
}

####
# Shows a list of entitys
####
function showList($entitys, $show) {
	global $db;
	$entityAttributesHandler = $db->prepare('SELECT * FROM EntityAttributes WHERE entity_id = :Id;');
	$mduiHandler = $db->prepare("SELECT data FROM Mdui WHERE element = 'DisplayName' AND entity_id = :Id ORDER BY type,lang;");

	$countSWAMID = 0;
	$counteduGAIN = 0;
	$countTesting = 0;
	$countECanon = 0;
	$countECpseuso = 0;
	$countECpers = 0;
	$countECcocov1 = 0;
	$countECcocov2 = 0;
	$countECrs = 0;
	$countECesi = 0;
	$countHideFromDisc = 0;
	$countECSanon = 0;
	$countECSpseuso = 0;
	$countECSpers = 0;
	$countECScocov1 = 0;
	$countECScocov2 = 0;
	$countECSrs = 0;
	$countECSesi = 0;
	$countAL1 = 0;
	$countAL2 = 0;
	$countAL3 = 0;
	$countSIRTFI = 0;

	$entitys->execute();
	while ($row = $entitys->fetch(PDO::FETCH_ASSOC)) {
		$isAL1 = '';
		$isAL2 = '';
		$isAL3 = '';
		$isAnon = '';
		$isPseuso = '';
		$isPers = '';
		$isSIRTFI = '';
		$isCocov1 = '';
		$isCocov2 = '';
		$isRS = '';
		$isESI = '';
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
						case 'https://refeds.org/category/anonymous' :
							if ($prodFeed) {
								$countECanon ++;
								$isAnon = 'X';
							} else
								$isAnon = '(X)';
							break;
						case 'https://refeds.org/category/code-of-conduct/v2' :
							if ($prodFeed) {
								$countECcocov2 ++;
								$isCocov2 = 'X';
							} else
								$isCocov2 = '(X)';
							break;
						case 'https://refeds.org/category/pseudonymous' :
							if ($prodFeed) {
								$countECpseuso ++;
								$isPseuso = 'X';
							} else
								$isPseuso = '(X)';
							break;
						case 'https://refeds.org/category/personalized' :
							if ($prodFeed) {
								$countECpers ++;
								$isPers = 'X';
							} else
								$isPers = '(X)';
							break;
						case 'http://refeds.org/category/research-and-scholarship' :
							if ($prodFeed) {
								$countECrs ++;
								$isRS = 'X';
							} else
								$isRS = '(X)';
							break;
						case 'http://www.geant.net/uri/dataprotection-code-of-conduct/v1' :
							if ($prodFeed) {
								$countECcocov1 ++;
								$isCocov1 = 'X';
							} else
								$isCocov1 = '(X)';
							break;
						case 'https://myacademicid.org/entity-categories/esi' :
							if ($prodFeed) {
								$countECesi ++;
								$isESI = 'X';
							} else
								$isESI = '(X)';
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
						case 'https://refeds.org/category/anonymous' :
							if ($prodFeed) $countECSanon ++;
							break;
						case 'https://refeds.org/category/code-of-conduct/v2' :
							if ($prodFeed) $countECScocov2 ++;
							break;
						case 'https://refeds.org/category/personalized' :
							if ($prodFeed) $countECSpers ++;
							break;
						case 'https://refeds.org/category/pseudonymous' :
							if ($prodFeed) $countECSpseuso ++;
							break;
						case 'http://refeds.org/category/research-and-scholarship' :
							if ($prodFeed) $countECSrs ++;
							break;
						case 'http://www.geant.net/uri/dataprotection-code-of-conduct/v1' :
							if ($prodFeed) $countECScocov1 ++;
							break;
						case 'https://myacademicid.org/entity-categories/esi' :
							if ($prodFeed) $countECSesi ++;
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
		switch ($row['publishIn']) {
			case 1 :
				$countTesting ++;
				$registerdIn = 'Test-only';
				break;
			case 3 :
				$countSWAMID ++;
				//$countTesting ++;
				$registerdIn = 'SWAMID';
				break;
			case 7 :
				$countSWAMID ++;
				$counteduGAIN ++;
				//$countTesting ++;
				$registerdIn = ' SWAMID + eduGAIN';
				break;
			default :
				$registerdIn = '';
		}
		printf ('<td><a href=".?showEntity=%s"><span class="text-truncate">%s</span></a></td><td>%s</td><td>%s</td><td>%s</td>', $row['id'], $row['entityID'], $registerdIn, $DisplayName, $row['OrganizationName']);

		switch ($show) {
			case 'IdP' :
				printf ('<td class="text-center">%s</td><td class="text-center">%s</td><td class="text-center">%s</td><td class="text-center">%s</td><td class="text-center">%s</td>', $isAL1, $isAL2, $isAL3, $isSIRTFI, $hasHide);
				break;
			case 'SP' :
				printf ('<td class="text-center">%s</td><td class="text-center">%s</td><td class="text-center">%s</td><td class="text-center">%s</td><td class="text-center">%s</td><td class="text-center">%s</td><td class="text-center">%s</td><td class="text-center">%s</td>', $isAnon, $isPseuso, $isPers, $isCocov1, $isCocov2, $isRS, $isESI, $isSIRTFI);
				break;
			case 'All' :
				print $row['isIdP'] ? '<td class="text-center">X</td>' : '<td></td>';
				print $row['isSP'] ? '<td class="text-center">X</td>' : '<td></td>';
				break;
		}
		print "</tr>\n";
	} ?>
    </table></div>
    <h4>Statistics</h4>
    <table class="table table-striped table-bordered">
      <tr><th rowspan="3">&nbsp;Registered in</th><th>SWAMID-Production</th><td><?=$countSWAMID?></td></tr>
      <tr><th>eduGAIN-Export</th><td><?=$counteduGAIN?></td></tr>
      <tr><th>SWAMID-Test only</th><td><?=$countTesting?></td></tr><?php
	if ($show == 'All' || $show == 'SP') { ?>
      <tr><th rowspan="8">Entity Categories in production<br><i>Excluding testing only (X)</i></th><th>Anonymous</th><td><?=$countECanon?></td></tr>
      <tr><th>Pseudonymous</th><td><?=$countECpseuso?></td></tr>
      <tr><th>Personalized</th><td><?=$countECpers?></td></tr>
      <tr><th>CoCo v1</th><td><?=$countECcocov1?></td></tr>
      <tr><th>CoCo v2</th><td><?=$countECcocov2?></td></tr>
      <tr><th>R&S</th><td><?=$countECrs?></td></tr>
      <tr><th>ESI</th><td><?=$countECesi?></td></tr>
      <tr><th>DS-hide </th><td><?=$countHideFromDisc?></td></tr><?php
	} else {
		printf('      <tr><th>Entity Categories in production<br><i>Excluding testing only (X)</i></th><th>DS-hide </th><td>%d</td></tr>', $countHideFromDisc);
	}
	if ($show == 'All' || $show == 'IdP') { ?>
      <tr><th rowspan="7">Support Categorys in production<br><i>Excluding testing only (X)</i></th><th>Anonymous</th><td><?=$countECSanon?></td></tr>
      <tr><th>Pseudonymous</th><td><?=$countECSpseuso?></td></tr>
      <tr><th>Personalized</th><td><?=$countECSpers?></td></tr>
      <tr><th>CoCo v1</th><td><?=$countECScocov1?></td></tr>
      <tr><th>CoCo v2</th><td><?=$countECScocov2?></td></tr>
      <tr><th>R&S</th><td><?=$countECSrs?></td></tr>
      <tr><th>ESI</th><td><?=$countECSesi?></td></tr>
      <tr><th rowspan="4">Assurance profiles in production<br><i>Excluding testing only (X)</i></th><th>AL1</th><td><?=$countAL1?></td></tr>
      <tr><th>AL2 </th><td><?=$countAL2?></td></tr>
      <tr><th>AL3 </th><td><?=$countAL3?></td></tr>
      <tr><th>SIRTFI </th><td><?=$countSIRTFI?></td></tr><?php
	} else {
		printf('      <tr><th>Assurance profiles in production<br><i>Excluding testing only (X)</i></th><th>SIRTFI </th><td>%d</td></tr>', $countSIRTFI);
	}?>
    </table>

<?php
}

function showInfo() {
	global $html;
	$html->showHeaders('Metadata SWAMID - Info');
	showMenu('info',''); ?>
    <div class="row">
      <div class="col">
        <br>
        <h3>SWAMID Metadata Tool</h3>
        <p>Welcome to the SWAMID Metadata Tool. With this tool you can browse and examine metadata availabe through SWAMID.
        <h4>Public available information</h4>
        <p>To view entities, i.e. Identiy Providers and Service Providers, available in SWAMID, select a tab:<ul>
          <li><b>All in SWAMID</b> lists all entities registered in SWAMID.</li>
          <li><b>IdP in SWAMID</b> lists Identity Providers registered in SWAMID including identity assurance profiles.</li>
          <li><b>SP in SWAMID</b> lists Service Providers registered in SWAMID including requested entity categories.</li>
          <li><b>IdP via interfederation</b> lists Identity Providers imported into SWAMID from interfederations.</li>
          <li><b>SP via interfederation</b> lists Service Providers imported into SWAMID from interfederations.</li>
        </ul></p>
        <p>The entities can be sorted and filtered using the headers of the tables and the entityID search form. E.g entering "umu.se" in the entityID search form will list all entities including "umu.se" in their entityID.</p>
        <h4>Update Identity Provider or Service Provider metadata</h4>
        <p>Login using the blue button at the top right corner of this page to add, update or request removal of your entites in SWAMID. SWAMID Operations authenticates and validates all updates before changes are published in the SWAMID metadata. After login, help on adding/updating entites is available in the menu at the top.</p>
        <p>If you do not have an active user account at a SWAMID Identity Provider, you can create an eduID account at <a href="https://eduid.se">eduID.se</a>. Make sure that the primary email address of your eduID account matches an email address associated with a contact person of your entities.</p>
<?php
}

function showFeed($id) {
	global $db;
	$entity = $db->prepare('SELECT `publishIn` FROM Entities WHERE `id` = :Id');
	$entity->bindParam(':Id', $id);
	$entity->execute();
	if ($row = $entity->fetch(PDO::FETCH_ASSOC)) {
		switch($row['publishIn']) {
			case 1 :
				print "swamid-testing\n";
				break;
			case 3 :
				print "swamid-2.0\n";
				break;
			case 7 :
				print "swamid-edugain\n";
				break;
			default :
				print "swamid-2.0\n";
		}
	} else
		print "swamid-2.0\n";
	exit;
}

function showPendingQueue() {
	global $db;
	$entitys = $db->prepare('SELECT `id`, `entityID` FROM Entities WHERE `status` = 2');
	$entitys->execute();
	while ($row = $entitys->fetch(PDO::FETCH_ASSOC)) {
		printf ('%d	%s%s',$row['id'], $row['entityID'], "\n");
	}
	exit;
}

function showInterfederation($type){
	global $html;
	if ($type == 'IDP') {
		$html->showHeaders('Metadata SWAMID - eduGAIN - IdP:s');
		showMenu('fedIdPs','');
		printf ('    <table  id="IdP-table" class="table table-striped table-bordered">%s      <thead><tr><th>entityID</th><th>Organization</th><th>Contacts</th><th>Scopes</th><th>Entity category support</th><th>Assurance Certification</th><th>Registration Authority</th></tr></thead>', "\n");
		$html->addTableSort('IdP-table');
	} else {
		$html->showHeaders('Metadata SWAMID - eduGAIN - SP:s');
		showMenu('fedSPs','');
		printf ('    <table id="SP-table" class="table table-striped table-bordered">%s      <thead><tr><th>entityID</th><th>Service Name</th><th>Organization</th><th>Contacts</th><th>Entity Categories</th><th>Assurance Certification</th><th>Registration Authority</th></tr></thead>', "\n");

		$html->addTableSort('SP-table');
	}

	$xml = new DOMDocument;
	$xml->preserveWhiteSpace = false;
	$xml->formatOutput = true;
	$xml->load('/opt/metadata/swamid-2.0.xml');
	$xml->encoding = 'UTF-8';

	checkEntities($xml, $type);
}

function checkEntities($xml, $type) {
	foreach ($xml->childNodes as $child) {
		switch (nodeName($child->nodeName)) {
			case 'EntitiesDescriptor' :
				checkEntities($child, $type);
				break;
			case 'EntityDescriptor' :
				checkEntity($child, $type);
				break;
			case 'Signature' :
			case 'Extensions' :
			case '#comment' :
				break;
			default:
				printf ('%s<br>', $child->nodeName);
		}
	}
}

function checkEntity($xml, $type) {
	$show = false;
	$EC = '';
	$ECS = '';
	$AC = '';
	foreach ($xml->childNodes as $child) {
		switch (nodeName($child->nodeName)) {
			case 'Extensions' :
				foreach ($child->childNodes as $extChild) {
					switch (nodeName($extChild->nodeName)) {
						case 'RegistrationInfo' :
							$registrationAuthority = $extChild->getAttribute('registrationAuthority');
							$hideSwamid = ($registrationAuthority == 'http://www.swamid.se/') ? false : true;
							$hideSwamid = ($registrationAuthority == 'http://www.swamid.se/loop') ? false : $hideSwamid;
							break;
						case 'EntityAttributes' :
							foreach ($extChild->childNodes as $entAttrChild) {
								if (nodeName($entAttrChild->nodeName) == 'Attribute') {
									switch ($entAttrChild->getAttribute('Name')){
										case 'http://macedir.org/entity-category' :
											foreach ($entAttrChild->childNodes as $attrChild) {
												if (nodeName($attrChild->nodeName) == 'AttributeValue')
													$EC .= $attrChild->nodeValue . ' ';
											}
											break;
										case 'http://macedir.org/entity-category-support' :
											foreach ($entAttrChild->childNodes as $attrChild) {
												if (nodeName($attrChild->nodeName) == 'AttributeValue')
													$ECS .= $attrChild->nodeValue . ' ';
											}
											break;
										case 'urn:oasis:names:tc:SAML:attribute:assurance-certification' :
											foreach ($entAttrChild->childNodes as $attrChild) {
												if (nodeName($attrChild->nodeName) == 'AttributeValue')
													$AC .= $attrChild->nodeValue . ' ';
											}
											break;
									}
								}
							}
					}
				}
				break;
			case 'IDPSSODescriptor' :
				if ($type == 'IDP') {
					$entityID = $xml->getAttribute('entityID');
					$show = true;
				}
				break;
			case 'SPSSODescriptor' :
				if ($type == 'SP') {
					$entityID = $xml->getAttribute('entityID');
					$show = true;
				}
				break;
		}
	}

	if ($show && $hideSwamid) {
		$scope = '';
		$orgURL = '';
		$orgName = '';
		$contacts = array();
		$displayName = '';
		$serviceName = '';
		foreach ($xml->childNodes as $child) {
			switch (nodeName($child->nodeName)) {
				case 'Extensions' :
					break;
				case 'IDPSSODescriptor' :
					foreach ($child->childNodes as $SSOChild) {
						if (nodeName($SSOChild->nodeName) == 'Extensions') {
							foreach ($SSOChild->childNodes as $extChild) {
								if (nodeName($extChild->nodeName) == 'Scope')
									$scope .= $extChild->nodeValue . ' ';
							}
						}
					}
					break;
				case 'SPSSODescriptor' :
					foreach ($child->childNodes as $SSOChild) {
						switch (nodeName($SSOChild->nodeName)) {
							case 'Extensions' :
								foreach ($SSOChild->childNodes as $extChild) {
									if (nodeName($extChild->nodeName) == 'UIInfo') {
										foreach ($extChild->childNodes as $UUIChild) {
											if (nodeName($UUIChild->nodeName) == 'DisplayName') {
												if ($displayName == '')
													$displayName = $UUIChild->nodeValue;
												elseif ($UUIChild->getAttribute('xml:lang') == 'en')
													$displayName = $UUIChild->nodeValue;
											}
										}
									}
								}
								break;
							case 'AttributeConsumingService' :
								foreach ($SSOChild->childNodes as $acsChild) {
									if (nodeName($acsChild->nodeName) == 'ServiceName') {
										if ($serviceName == '')
											$serviceName = $acsChild->nodeValue;
										elseif ($acsChild->getAttribute('xml:lang') == 'en')
											$serviceName = $acsChild->nodeValue;
									}
								}
								break;
						}
					}
					break;
				case 'AttributeAuthorityDescriptor' :
					break;
				case 'Organization' :
					foreach ($child->childNodes as $orgChild) {
						if (nodeName($orgChild->nodeName) == 'OrganizationURL')
							if ($orgURL == '')
								$orgURL = $orgChild->nodeValue;
							elseif ($orgChild->getAttribute('xml:lang') == 'en')
								$orgURL = $orgChild->nodeValue;
						if (nodeName($orgChild->nodeName) == 'OrganizationDisplayName')
							if ($orgName == '')
								$orgName = $orgChild->nodeValue;
							elseif ($orgChild->getAttribute('xml:lang') == 'en')
								$orgName = $orgChild->nodeValue;
					}
					break;
				case 'ContactPerson' :
					$email = '';
					foreach ($child->childNodes as $contactChild) {
						if (nodeName($contactChild->nodeName) == 'EmailAddress')
							$email = $contactChild->nodeValue;
					}
					array_push($contacts, array ('type' => $child->getAttribute('contactType'), 'email' => $email));
					break;
				case '#comment' :
					break;
				default:
					printf ('%s<br>', $child->nodeName);
			}
		}
		if ($type == 'IDP') {
			printf ('<tr><td>%s</td><td><a href="%s">%s</a></td><td>', $entityID, $orgURL, $orgName);
			foreach ($contacts as $contact) {
				printf ('<a href="%s">%s<a><br>', $contact['email'], $contact['type']);
			}
			printf ('</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>%s', $scope, $ECS, $AC, $registrationAuthority, "\n");
		} elseif ($type == 'SP') {
			printf ('<tr><td>%s</td><td>%s<br>%s</td><td><a href="%s">%s</a></td><td>', $entityID, $displayName, $serviceName, $orgURL, $orgName);
			foreach ($contacts as $contact) {
				printf ('<a href="%s">%s<a><br>', $contact['email'], $contact['type']);
			}
			printf ('</td><td>%s</td><td>%s</td><td>%s</td></tr>%s', $EC, $AC, $registrationAuthority, "\n");
		}
	}
}

function nodeName($str) {
	$array = explode(':', $str);
	if (isset($array[1]))
		return $array[1];
	else
		return $array[0];
}

function approveAccessRequest($code) {
	global $baseDir;
	$codeArray = explode(':', base64_decode($code));
	if (isset($codeArray[2])) {
		include 'include/Metadata.php';
		$metadata = new Metadata($baseDir, $codeArray[0]);
		if ($metadata->EntityExists()) {
			$result = $metadata->validateCode($codeArray[1], $codeArray[2]);
			if ($result['returnCode'] < 10) {
				$info = $result['info'];
				if ($result['returnCode'] == 2) {
					global $SMTPHost, $SASLUser, $SASLPassword, $MailFrom;

					$mail = new PHPMailer(true);
					/*$mail->SMTPDebug = 2;*/
					$mail->isSMTP();
					$mail->Host = $SMTPHost;
					$mail->SMTPAuth = true;
					$mail->SMTPAutoTLS = true;
					$mail->Port = 587;
					$mail->SMTPAuth = true;
					$mail->Username = $SASLUser;
					$mail->Password = $SASLPassword;
					$mail->SMTPSecure = 'tls';

					//Recipients
					$mail->setFrom($MailFrom, 'Metadata');
					$mail->addReplyTo('operations@swamid.se', 'SWAMID Operations');
					$mail->addAddress($result['email']);
					$mail->Body		= sprintf("<p>Hi.</p>\n<p>Your access to %s have been granted.</p>\n<p>-- <br>This mail was sent by SWAMID Metadata Admin Tool, a service provided by SWAMID Operations. If you've any questions please contact operations@swamid.se.</p>", $metadata->entityID());
					$mail->AltBody	= sprintf("Hi.\n\nYour access to %s have been granted.\n\n-- \nThis mail was sent by SWAMID Metadata Admin Tool, a service provided by SWAMID Operations. If you've any questions please contact operations@swamid.se.", $metadata->EntityID());
					$mail->Subject	= 'Access granted for ' . $short_entityid;

					$info = sprintf('<h3>Access granted</h3>Access to <b>%s</b> added for %s (%s).', $metadata->EntityID(), $result['fullName'], $result['email']);

					try {
						$mail->send();
					} catch (Exception $e) {
						echo 'Message could not be sent to contacts.<br>';
						echo 'Mailer Error: ' . $mailContacts->ErrorInfo . '<br>';
					}
				}
				showText($info);
			} else {
				showError($result['info']);
			}
		} else {
			showError('Invalid code');
		}
	} else {
		showError('Invalid code');
	}
}

function showError($error) {
	global $html;
	$html->showHeaders('Metadata SWAMID - Error');
	print $error;
}

function showText($text, $showMenu = false, $error = false) {
	global $html, $menuActive;
	$html->showHeaders('Metadata SWAMID');
	if ($showMenu) showMenu();
	printf ('    <div class="row">%s      <div class="col">%s        %s%s      </div>%s    </div>%s', "\n", "\n", $text, "\n", "\n", "\n");
}