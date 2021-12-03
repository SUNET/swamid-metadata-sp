<?php
switch ($_SERVER['saml_eduPersonPrincipalName']) {
	case 'bjorn@sunet.se' :
	case 'frkand02@umu.se' :
	case 'paulscot@kau.se' :
		$userLevel = 10;
		break;
	case 'ldc-esw@lu.se' :
	case 'johpe12@liu.se' :
	case 'pax@sunet.se' :
	case 'toylon98@umu.se' :
		$userLevel = 5;
		break;
	default :
		$userLevel = 1;
		exit;
}

$configFile = dirname($_SERVER['SCRIPT_FILENAME'], 2) . '/config.php' ;

include $configFile;
try {
	$db = new PDO("mysql:host=$dbServername;dbname=$dbName", $dbUsername, $dbPassword);
	// set the PDO error mode to exception
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  } catch(PDOException $e) {
	echo "Error: " . $e->getMessage();
}

include '../include/Html.php';
$html = new HTML();

include '../include/MetadataDisplay.php';
$display = new MetadataDisplay($configFile);

if (isset($_FILES['XMLfile'])) {
	importXML();
} elseif (isset($_GET['edit'])) {
	if (isset($_GET['Entity']) && (isset($_GET['oldEntity']))) {
		$html->showHeaders('Metadata SWAMID - Edit - '.$_GET['edit']);
		include '../include/MetadataEdit.php';
		$editMeta = new MetadataEdit($configFile, $_GET['Entity'], $_GET['oldEntity']);
		$editMeta->edit($_GET['edit']);
	} else
		showEntityList();
} elseif (isset($_GET['showEntity'])) {
	showEntity($_GET['showEntity']);
} elseif (isset($_GET['validateEntity'])) {
	validateEntity($_GET['validateEntity']);
	showEntity($_GET['validateEntity']);
} elseif (isset($_GET['move2Pending'])) {
	move2Pending($_GET['move2Pending']);
} elseif (isset($_GET['mergeEntity'])) {
	if (isset($_GET['oldEntity'])) {
		mergeEntity($_GET['mergeEntity'], $_GET['oldEntity']);
		validateEntity($_GET['mergeEntity']);
	}
	showEntity($_GET['mergeEntity']);
} elseif (isset($_GET['removeSSO']) && isset($_GET['type'])) {
	removeSSO($_GET['removeSSO'], $_GET['type']);
} elseif (isset($_GET['removeKey']) && isset($_GET['type']) && isset($_GET['use']) && isset($_GET['hash'])) {
	removeKey($_GET['removeKey'], $_GET['type'], $_GET['use'], $_GET['hash']);
} elseif (isset($_GET['rawXML'])) {
	$display->showRawXML($_GET['rawXML']);
} else {
	$menuActive = 'publ';
	if (isset($_GET['action'])) {
		if (isset($_GET['Entity'])) {
			include '../include/Metadata.php';
			$Entity_id = $_GET['Entity'];
			switch($_GET['action']) {
				case 'createDraft' :
					$menuActive = 'new';
					$metadata = new Metadata($configFile, $Entity_id);
					if ($newEntity_id = $metadata->createDraft())
						$metadata->validateXML();
						$metadata->validateSAML();
						showEntity($newEntity_id);
					break;
			}
		} else {
			switch($_GET['action']) {
				case 'new' :
					$menuActive = 'new';
					showEntityList(3);
					break;
				case 'wait' :
					$menuActive = 'wait';
					showEntityList(2);
					break;
				case 'upload' :
					$menuActive = 'upload';
					showUpload();
					break;
				default :
					showEntityList();
			}
		}
	} else
		showEntityList();
}


$html->showFooter($display->getCollapseIcons());
# End of page

####
# Shows EntityList
####
function showEntityList($status = 1) {
	global $db, $html;

	$showAll = true;
	$sortOrder = 'entityID';
	$query = '';
	if (isset($_GET['feed'])) {
		$sortOrder = 'publishIn DESC, entityID';
	}

	if (isset($_GET['org'])) {
		$sortOrder = 'OrganizationDisplayName, entityID';
	}

	if (isset($_GET['validationOutput'])) {
		$sortOrder = 'validationOutput DESC, entityID';
	}

	if (isset($_GET['warnings'])) {
		$sortOrder = 'warnings DESC, entityID';
	}

	if (isset($_GET['errors'])) {
		$sortOrder = 'errors DESC, entityID';
	}

	if (isset($_GET['query'])) {
		$query = $_GET['query'];
		$filter = '?query='.$query;
	} else {
		$filter = '?query';
	}

	$html->showHeaders('Metadata SWAMID - New');
	showMenu();
	if (isset($_GET['action']))
		$filter .= '&action='.$_GET['action'];
	$entitys = $db->prepare("SELECT id, entityID, isIdP, isSP, publishIn, data AS OrganizationDisplayName, lastUpdated, lastValidated, validationOutput, warnings, errors FROM Entities LEFT JOIN Organization ON Organization.entity_id = id AND element = 'OrganizationDisplayName' AND lang = 'en' WHERE status = $status AND entityID LIKE :Query ORDER BY $sortOrder");
	$entitys->bindValue(':Query', "%".$query."%");
	$extraTH = '';

	print '
    <table class="table table-striped table-bordered">
      <tr>
	  	<th>IdP</th><th>SP</th>';

	printf('<th>Registrerad i</th> <th><a href="%s&feed">eduGAIN</a></th> <th><form><a href="%s&entityID">entityID</a> <input type="text" name="query" value="%s"> <input type="submit" value="Filtrera"></form></th><th><a href="%s&org">OrganizationDisplayName</a></th><th>lastUpdated</th><th>lastValidated</th><th><a href="%s&validationOutput">validationOutput</a></th><th><a href="%s&warnings">warning</a> / <a href="%s&errors">errors</a></th>', $filter, $filter, $query, $filter, $filter, $filter, $filter);

	print $extraTH . "</tr>\n";
	showList($entitys);
}

####
# Shows Entity information
####
function showEntity($Entity_id)  {
	global $db, $html, $display, $userLevel, $menuActive;
	$entityHandler = $db->prepare('SELECT entityID, isIdP, isSP, publishIn, status, errors FROM Entities WHERE id = :Id;');
	$entityHandlerOld = $db->prepare('SELECT id, isIdP, isSP, publishIn FROM Entities WHERE entityID = :Id AND status = 1;');
	$publishArray = array();
	$publishArrayOld = array();
	$allowEdit = false;

	$entityHandler->bindParam(':Id', $Entity_id);
	$entityHandler->execute();
	if ($entity = $entityHandler->fetch(PDO::FETCH_ASSOC)) {
		if (($entity['publishIn'] & 2) == 2) $publishArray[] = 'SWAMID';
		if (($entity['publishIn'] & 4) == 4) $publishArray[] = 'eduGAIN';
		if (($entity['publishIn'] & 1) == 1) $publishArray[] = 'SWAMID-testing';
		if ($entity['status'] > 1) {
			$entityHandlerOld->bindParam(':Id', $entity['entityID']);
			$entityHandlerOld->execute();
			if ($entityOld = $entityHandlerOld->fetch(PDO::FETCH_ASSOC)) {
				$oldEntity_id = $entityOld['id'];
				if (($entityOld['publishIn'] & 2) == 2) $publishArrayOld[] = 'SWAMID';
				if (($entityOld['publishIn'] & 4) == 4) $publishArrayOld[] = 'eduGAIN';
				if (($entityOld['publishIn'] & 1) == 1) $publishArrayOld[] = 'SWAMID-testing';
			} else {
				$oldEntity_id = 0;
			}
			if ($entity['status'] == 3 ) {
				$headerCol1 = 'New metadata';
				$menuActive = 'new';
				$allowEdit = true;
			} else {
				$headerCol1 = 'Waiting for publishing';
				$menuActive = 'wait';
			}
		} else {
			$headerCol1 = 'Published metadata';
			$menuActive = 'publ';
			$oldEntity_id = 0;
		}
		$html->showHeaders('Metadata SWAMID - ' . $entity['entityID']);
		showMenu();?>
    <div class="row">
      <div class="col">
        <h3>entityID = <?=$entity['entityID']?></h3>
      </div>
    </div><?php
		$display->showStatusbar($Entity_id, $userLevel > 4 ? true : false);
		print "\n" . '    <div class="row">';
		switch ($entity['status']) {
			case 1:
				printf('%s      <a href=".?action=createDraft&Entity=%d"><button type="button" class="btn btn-outline-primary">Create draft</button></a>', "\n", $Entity_id);
				break;
			case 3:
				if ($entity['errors'] == '') {
					printf('%s      <a href=".?move2Pending=%d"><button type="button" class="btn btn-outline-success">Move to Pending</button></a>', "\n", $Entity_id);
				} else {
					printf('%s      <a href=".?move2Pending=%d"><button type="button" class="btn btn-outline-danger">Move to Pending</button></a>', "\n", $Entity_id);
				}
				if ($oldEntity_id > 0) {
					printf('%s      <a href=".?mergeEntity=%d&oldEntity=%d"><button type="button" class="btn btn-outline-primary">Merge missing from published</button></a>', "\n", $Entity_id, $oldEntity_id);
				}
				printf ('%s      <form>%s        <input type="hidden" name="mergeEntity" value="%d">%s        Merge missing from : %s        <select name="oldEntity">', "\n", "\n", $Entity_id, "\n", "\n");
				if ($entity['isIdP'] ) {
					if ($entity['isSP'] ) {
						// is both SP and IdP
						$mergeEntityHandler = $db->prepare('SELECT id, entityID FROM Entities WHERE status = 1 ORDER BY entityID;');
					} else {
						// isIdP only
						$mergeEntityHandler = $db->prepare('SELECT id, entityID FROM Entities WHERE status = 1 AND isIdP = 1 ORDER BY entityID;');
					}
				} else {
					// isSP only
					$mergeEntityHandler = $db->prepare('SELECT id, entityID FROM Entities WHERE status = 1 AND isSP = 1 ORDER BY entityID;');
				}
				$mergeEntitys = $mergeEntityHandler->execute();
				while ($mergeEntity = $mergeEntityHandler->fetch(PDO::FETCH_ASSOC)) {
					printf('%s          <option value="%d">%s</option>', "\n", $mergeEntity['id'], $mergeEntity['entityID']);
				}
				printf ('%s        </select>%s        <button type="submit">Merge</button>%s      </form>', "\n", "\n", "\n");
				break;
		}

		 ?>

    </div>
    <div class="row">
      <div class="col">
        <h3><?=$headerCol1?></h3>
        Published in : <?php
		print (implode (', ', $publishArray));
		printf('<a href="?edit=PublishedIn&Entity=%d&oldEntity=%d"><i class="fa fa-pencil-alt"></i></a>', $Entity_id, $oldEntity_id);
		if ($oldEntity_id > 0) { ?>

      </div>
      <div class="col">
        <h3>Old metadata</h3>
        Published in : <?php
			print (implode (', ', $publishArrayOld));
		} ?>

      </div>
    </div><?php
		$display->showEntityAttributes($Entity_id, $oldEntity_id, $allowEdit);
		$able2beRemoveSSO = ($entity['isIdP'] && $entity['isSP'] );
		if ($entity['isIdP'] ) $display->showIdP($Entity_id, $oldEntity_id, $allowEdit, $able2beRemoveSSO);
		if ($entity['isSP'] ) $display->showSp($Entity_id, $oldEntity_id, $allowEdit, $able2beRemoveSSO);
		$display->showOrganization($Entity_id, $oldEntity_id, $allowEdit);
		$display->showContacts($Entity_id, $oldEntity_id, $allowEdit);
		$display->showXML($Entity_id);

	} else {
		$html->showHeaders('Metadata SWAMID - NotFound');
		print "Can't find Entity";
	}
}

####
# Shows a list of entitys
####
function showList($entitys, $displayType = 0) {
	global $db;

	$entitys->execute();
	while ($row = $entitys->fetch(PDO::FETCH_ASSOC)) {
		printf ('      <tr>');
		if ($displayType == 0) {
			print $row['isIdP'] ? '<td class="text-center">X</td>' : '<td></td>';
			print $row['isSP'] ? '<td class="text-center">X</td>' : '<td></td>';
		}

		switch ($row['publishIn']) {
			case 1 :
				$registerdIn = 'Test-only';
				$export2Edugain = '';
				break;
			case 3 :
				$registerdIn = 'SWAMID';
				$export2Edugain = '';
				break;
			case 7 :
				$registerdIn = 'SWAMID';
				$export2Edugain = 'X';
				break;
			default :
				$registerdIn = '';
				$export2Edugain = '';
		}
		switch ($displayType) {
			case 1 :
				printf('<td class="text-center">%s</td><td>%s</td><td><a href="?showEntity=%s">%s</a><td style="white-space: nowrap;overflow: hidden;text-overflow: ellipsis;max-width: 20em;">%s</td><td>%s</td><td>%s</td>', $registerdIn, $export2Edugain, $row['id'], $row['entityID'], $row['URL'], $row['lastValidated'], $row['validationOutput']);
				break;
			default :
				$validationStatus = ($row['warnings'] == '') ? '' : '<i class="fas fa-exclamation-triangle"></i>';
				$validationStatus .= ($row['errors'] == '') ? '' : '<i class="fas fa-exclamation"></i>';
				$validationOutput = ($row['validationOutput'] == '') ? '' : '<i class="fas fa-question"></i>';

				printf ('<td class="text-center">%s</td><td class="text-center">%s</td><td><a href="?showEntity=%s">%s</a></td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td>', $registerdIn, $export2Edugain, $row['id'], $row['entityID'], $row['OrganizationDisplayName'], $row['lastUpdated'], $row['lastValidated'], $validationOutput, $validationStatus);
		}

		print "</tr>\n";
	} ?>
    </table>
<?php
}

####
# Shows form for upload of new XML
####
function showUpload() {
	global $html;
	$html->showHeaders('Metadata SWAMID - Add new XML');
	showMenu();
	?>
    <form action="." method="post" enctype="multipart/form-data">
      <div class="custom-file">
        <input type="file" class="custom-file-input" name="XMLfile" id="customFile">
        <label class="custom-file-label" for="customFile">Choose file</label>
      </div>
      <div class="mt-3">
        <button type="submit" class="btn btn-primary">Submit</button>
      </div>
    </form><?php
}

####
# Import and validate uploaded XML.
####
function importXML(){
	global $html, $configFile;

	include '../include/NormalizeXML.php';
	$import = new NormalizeXML();
	$import->fromFile($_FILES['XMLfile']['tmp_name']);
	if ($import->getStatus()) {
		$entityID = $import->getEntityID();
		include '../include/Metadata.php';
		$metadata = new Metadata($configFile, $import->getEntityID(), 'New');
		$metadata->importXML($import->getXML());
		$metadata->validateXML(true);
		$metadata->validateSAML(true);
		showEntity($metadata->dbIdNr);
	} else
		print ($import->getError());
}

####
# Remove an IDPSSO / SPSSO Decriptor that isn't used
####
function removeSSO($Entity_id, $type) {
	global $configFile;
	include '../include/MetadataEdit.php';
	$metadata = new MetadataEdit($configFile, $Entity_id);
	$metadata->removeSSO($type);
	validateEntity($Entity_id);
	showEntity($Entity_id);
}

####
# Remove an IDPSSO / SPSSO Key that is old & unused
####
function removeKey($Entity_id, $type, $use, $hash) {
	global $configFile;
	include '../include/MetadataEdit.php';
	$metadata = new MetadataEdit($configFile, $Entity_id);
	$metadata->removeKey($type, $use, $hash);
	validateEntity($Entity_id);
	showEntity($Entity_id);
}

####
# Shows menu row
####
function showMenu() {
	global $userLevel, $menuActive;
	$filter='';
	if (isset($_GET['query']))
		$filter='&query='.$_GET['query'];
	printf('<a href=".?action=new%s"><button type="button" class="btn btn%s-primary">Drafts</button></a>', $filter, $menuActive == 'new' ? '' : '-outline');
	printf('<a href=".?action=wait%s"><button type="button" class="btn btn%s-primary">Pending</button></a>', $filter, $menuActive == 'wait' ? '' : '-outline');
	printf('<a href=".?action=pub%s"><button type="button" class="btn btn%s-primary">Published</button></a>', $filter, $menuActive == 'publ' ? '' : '-outline');
	printf('<a href=".?action=upload%s"><button type="button" class="btn btn%s-primary">Upload new XML</button></a>', $filter, $menuActive == 'upload' ? '' : '-outline');
	print "<br>\n";print "<br>\n";
}

function validateEntity($Entity_id) {
	global $configFile;
	include '../include/Metadata.php';
	$metadata = new Metadata($configFile, $Entity_id);
	$metadata->validateXML(true);
	$metadata->validateSAML();
}

function move2Pending($Entity_id) {
	global $db, $html, $display, $userLevel;
	$entityHandler = $db->prepare('SELECT entityID, errors FROM Entities WHERE status = 3 AND id = :Id;');
	$entityHandler->bindParam(':Id', $Entity_id);
	$entityHandler->execute();
	if ($entity = $entityHandler->fetch(PDO::FETCH_ASSOC)) {
		$html->showHeaders('Metadata SWAMID - ' . $entity['entityID']);
		if ($entity['errors'] == '') {
			printf('%s      <p>You are about to move <b>%s</b> from Draft to Pending</p>', "\n", $entity['entityID']);
			printf('%s      <form>%s        <input type="hidden" name="move2Pending" value="%d">', "\n", "\n", $Entity_id);

			printf('%s        <button type="submit">Move</button>%s      </form>', "\n", "\n");
		} else {
			printf('%s    <div class="row alert alert-danger" role="alert">%s      <div class="col">%s        <div class="row"><b>Please fix the following errors before moving to Pending:</b></div>%s        <div class="row">%s</div>%s      </div>%s    </div>', "\n", "\n", "\n", "\n", str_ireplace("\n", "<br>", $entity['errors']), "\n", "\n");
      		printf('%s    <a href=".?showEntity=%d"><button type="button" class="btn btn-outline-primary">Return to Entity</button></a>', "\n", $Entity_id);
		}
	} else {
		$html->showHeaders('Metadata SWAMID - NotFound');
		print "Can't find Entity";
	}
	print "\n";
}
function mergeEntity($Entity_id, $oldEntity_id){
	global $configFile;
	include '../include/MetadataEdit.php';
	$metadata = new MetadataEdit($configFile, $Entity_id, $oldEntity_id);
	$metadata->mergeFrom();
}
