<?php
Class MetadataDisplay {
	# Setup
	function __construct($baseDir) {
		include $baseDir . '/config.php';
		include $baseDir . '/include/common.php';
		$this->baseDir = $baseDir;

		try {
			$this->metaDb = new PDO("mysql:host=$dbServername;dbname=$dbName", $dbUsername, $dbPassword);
			// set the PDO error mode to exception
			$this->metaDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} catch(PDOException $e) {
			echo "Error: " . $e->getMessage();
		}
		$this->collapseIcons = array();
	}

	####
	# Shows menu row
	####
	function showStatusbar($Entity_id, $admin = false){
		$entityHandler = $this->metaDb->prepare('SELECT `entityID`, `isIdP`, `isSP`, `validationOutput`, `warnings`, `errors`, `errorsNB`, `status` FROM Entities WHERE `id` = :Id;');
		$entityHandler->bindParam(':Id', $Entity_id);
		$urlHandler1 = $this->metaDb->prepare('SELECT `status`, `cocov1Status`,  `URL`, `lastValidated`, `validationOutput` FROM URLs WHERE URL IN (SELECT `data` FROM Mdui WHERE `entity_id` = :Id)');
		$urlHandler1->bindParam(':Id', $Entity_id);
		$urlHandler2 = $this->metaDb->prepare("SELECT `status`, `URL`, `lastValidated`, `validationOutput` FROM URLs WHERE URL IN (SELECT `URL` FROM EntityURLs WHERE `entity_id` = :Id AND type = 'error')");
		$urlHandler2->bindParam(':Id', $Entity_id);
		$urlHandler3 = $this->metaDb->prepare("SELECT `status`, `URL`, `lastValidated`, `validationOutput` FROM URLs WHERE URL IN (SELECT `data` FROM Organization WHERE `element` = 'OrganizationURL' AND `entity_id` = :Id)");
		$urlHandler3->bindParam(':Id', $Entity_id);

		$testResults = $this->metaDb->prepare('SELECT `test`, `result`, `time` FROM TestResults WHERE entityID = :EntityID');
		$entityAttributesHandler = $this->metaDb->prepare("SELECT `attribute` FROM EntityAttributes WHERE `entity_id` = :Id AND type = :Type;");
		$entityAttributesHandler->bindParam(':Id', $Entity_id);

		$entityHandler->execute();
		if ($entity = $entityHandler->fetch(PDO::FETCH_ASSOC)) {
			$errors = '';
			$warnings = '';

			if ($entity['isIdP']) {
				$ECSTagged = array('http://refeds.org/category/research-and-scholarship' => false, 'http://www.geant.net/uri/dataprotection-code-of-conduct/v1' => false);
				$ECSTested = array('rands' => false, 'cocov1-1' => false);

				$entityAttributesHandler->bindValue(':Type', 'entity-category-support');
				$entityAttributesHandler->execute();
				while ($attribute = $entityAttributesHandler->fetch(PDO::FETCH_ASSOC)) {
					$ECSTagged[$attribute['attribute']] = true;
				}

				$testResults->bindValue(':EntityID', $entity['entityID']);
				$testResults->execute();
				while ($testResult = $testResults->fetch(PDO::FETCH_ASSOC)) {
					$ECSTested[$testResult['test']] = true;
					switch ($testResult['test']) {
						case 'rands' :
							$tag = 'http://refeds.org/category/research-and-scholarship';
							break;
						case 'cocov1-1' :
							$tag = 'http://www.geant.net/uri/dataprotection-code-of-conduct/v1';
							break;
					}
					switch ($testResult['result']) {
						case 'CoCo OK, Entity Category Support OK' :
						case 'R&S attributes OK, Entity Category Support OK' :
						case 'CoCo OK, Entity Category Support missing' :
						case 'R&S attributes OK, Entity Category Support missing' :
							$warnings .= ($ECSTagged[$tag]) ? '' : sprintf("SWAMID Release-check: (%s) %s.\n", $testResult['time'], $testResult['result']);
							break;
						case 'Support for CoCo missing, Entity Category Support missing' :
						case 'R&S attribute missing, Entity Category Support missing' :
						case 'CoCo is not supported, BUT Entity Category Support is claimed' :
						case 'R&S attributes missing, BUT Entity Category Support claimed' :
							$errors .= ($ECSTagged[$tag]) ? sprintf("SWAMID Release-check: (%s) %s.\n", $testResult['time'], $testResult['result']) : '';
							break;
						default:
							printf('Unknown result : %s', $testResult['result']);
					}
				}
				foreach ($ECSTested AS $tag => $tested) {
					$warnings .= ($ECSTested[$tag]) ? '' : sprintf('SWAMID Release-check: Updated test missing please rerun at <a href="https://%s.release-check.swamid.se/">Release-check</a>%s', $tag, "\n");
				}
				// Error URLs
				$urlHandler2->execute();
				while ($url = $urlHandler2->fetch(PDO::FETCH_ASSOC)) {
					if ($url['status'] > 0)
						$errors .= sprintf('%s - <a href="?action=showURL&URL=%s" target="_blank">%s</a>%s', $url['validationOutput'], urlencode($url['URL']), $url['URL'], "\n");
				}
			}

			$CoCov1SP = false;
			if ($entity['isSP']) {
				$entityAttributesHandler->bindValue(':Type', 'entity-category');
				$entityAttributesHandler->execute();
				while ($attribute = $entityAttributesHandler->fetch(PDO::FETCH_ASSOC)) {
					if ($attribute['attribute'] == 'http://www.geant.net/uri/dataprotection-code-of-conduct/v1')
						$CoCov1SP = true;
				}
			}

			// MDUI
			$urlHandler1->execute();
			while ($url = $urlHandler1->fetch(PDO::FETCH_ASSOC)) {
				if ($url['status'] > 0 || ($CoCov1SP  && $url['cocov1Status'] > 0)) 
					$errors .= sprintf('%s - <a href="?action=showURL&URL=%s" target="_blank">%s</a>%s', $url['validationOutput'], urlencode($url['URL']), $url['URL'], "\n");
			}
			// OrganizationURL
			$urlHandler3->execute();
			while ($url = $urlHandler3->fetch(PDO::FETCH_ASSOC)) {
				if ($url['status'] > 0)
					$errors .= sprintf('%s - <a href="?action=showURL&URL=%s" target="_blank">%s</a>%s', $url['validationOutput'], urlencode($url['URL']), $url['URL'], "\n");
			}
			$errors .= $entity['errors'] . $entity['errorsNB'];
			if ($errors != '') {
				printf('%s    <div class="row alert alert-danger" role="alert">%s      <div class="col">%s        <b>Errors:</b><br>%s        %s%s      </div>%s    </div>', "\n", "\n", "\n", "\n", str_ireplace("\n", "<br>", $errors), "\n", "\n");
			}
			$warnings .= $entity['warnings'];
			if ( $warnings != '')
				printf('%s    <div class="row alert alert-warning" role="alert">%s      <div class="col">%s        <b>Warnings:</b><br>%s        %s%s      </div>%s    </div>', "\n", "\n", "\n", "\n", str_ireplace("\n", "<br>", $warnings), "\n", "\n");
			if ($entity['validationOutput'] != '')
				printf('%s    <div class="row alert alert-primary" role="alert">%s</div>', "\n", str_ireplace("\n", "<br>", $entity['validationOutput']));
		}
		if ($admin && $entity['status'] < 4)
			printf('%s    <div class="row"><a href=".?validateEntity=%d"><button type="button" class="btn btn-outline-primary">Validate</button></a></div>', "\n", $Entity_id);
	}

	####
	# Shows CollapseHeader
	####
	function showCollapse($title, $name, $haveSub=true, $step=0, $expanded=true, $extra = false, $Entity_id=0, $oldEntity_id=0){
		$spacer = "\n    ";
		while ($step > 0 ) {
			$spacer .= '      ';
			$step--;
		}
		if ($expanded) {
			$icon = 'down';
			$show = 'show ';
		} else {
			$icon = 'right';
			$show = '';
		}
		switch ($extra) {
			case 'SSO' :
				$extraButton = sprintf('<a href="?removeSSO=%d&type=%s"><i class="fas fa-trash"></i></a>', $Entity_id, $name);
				break;
			case 'EntityAttributes' :
			case 'IdPMDUI' :
			case 'SPMDUI' :
			case 'DiscoHints' :
			case 'AttributeConsumingService' :
			case 'Organization' :
			case 'ContactPersons' :
				$extraButton = sprintf('<a href="?edit=%s&Entity=%d&oldEntity=%d"><i class="fa fa-pencil-alt"></i></a>', $extra, $Entity_id, $oldEntity_id);
				break;
			default :
				$extraButton = '';
		}
		printf('%s<h4><i id="%s-icon" class="fas fa-chevron-circle-%s"></i> <a data-toggle="collapse" href="#%s" aria-expanded="%s" aria-controls="%s">%s</a> %s</h4>%s<div class="%scollapse multi-collapse" id="%s">%s  <div class="row">', $spacer, $name, $icon, $name, $expanded, $name, $title, $extraButton, $spacer, $show, $name, $spacer);
		if ($haveSub) {
			printf('%s    <span class="border-right"><div class="col-md-auto"></div></span>',$spacer);
		}
		printf('%s    <div class="col%s">', $spacer, $oldEntity_id > 0 ? '-6' : '');
		$this->collapseIcons[] = $name;
	}
	function showNewCol($step) {
		$spacer = '';
		while ($step > 0 ) {
			$spacer .= '      ';
			$step--;
		} ?>

        <?=$spacer?></div><!-- end col -->
        <?=$spacer?><div class="col-6"><?php
	}

	####
	# Shows CollapseEnd
	####
	function showCollapseEnd($name, $step = 0){
		$spacer = '';
		while ($step > 0 ) {
			$spacer .= '      ';
			$step--;
		}?>

        <?=$spacer?></div><!-- end col -->
      <?=$spacer?></div><!-- end row -->
    <?=$spacer?></div><!-- end collapse <?=$name?>--><?php
	}

	####
	# Shows EntityAttributes if exists
	####
	function showEntityAttributes($Entity_id, $oldEntity_id=0, $allowEdit = false) {
		if ($allowEdit)
			$this->showCollapse('EntityAttributes', 'Attributes', false, 0, true, 'EntityAttributes', $Entity_id, $oldEntity_id);
		else
			$this->showCollapse('EntityAttributes', 'Attributes', false, 0, true, false, $Entity_id, $oldEntity_id);
		$this->showEntityAttributesPart($Entity_id, $oldEntity_id, true);
		if ($oldEntity_id != 0 ) {
			$this->showNewCol(0);
			$this->showEntityAttributesPart($oldEntity_id, $Entity_id, false);
		}
		$this->showCollapseEnd('Attributes', 0);
	}
	function showEntityAttributesPart($Entity_id, $otherEntity_id, $added) {
		$entityAttributesHandler = $this->metaDb->prepare('SELECT `type`, `attribute` FROM EntityAttributes WHERE `entity_id` = :Id ORDER BY `type`, `attribute`;');
		if ($otherEntity_id) {
			$entityAttributesHandler->bindParam(':Id', $otherEntity_id);
			$entityAttributesHandler->execute();
			while ($attribute = $entityAttributesHandler->fetch(PDO::FETCH_ASSOC)) {
				$type = $attribute['type'];
				$value = $attribute['attribute'];
				if (! isset($otherAttributeValues[$type]))
					$otherAttributeValues[$type] = array();
				$otherAttributeValues[$type][$value] = true;
			}
		}
		$entityAttributesHandler->bindParam(':Id', $Entity_id);
		$entityAttributesHandler->execute();

		if ($attribute = $entityAttributesHandler->fetch(PDO::FETCH_ASSOC)) {
			$type = $attribute['type'];
			$value = $attribute['attribute'];
			if ($otherEntity_id) {
				$state = ($added) ? 'success' : 'danger';
				$state = isset($otherAttributeValues[$type][$value]) ? 'dark' : $state;
			} else
				$state = 'dark';
			$error = ' class="alert-warning" role="alert"';
			if (isset($this->standardAttributes[$type])) {
				foreach ($this->standardAttributes[$type] as $data)
					if ($data['value'] == $value)
						$error = ($data['swamidStd']) ? '' : ' class="alert-danger" role="alert"';
			}
			?>

          <b><?=$type?></b>
          <ul>
            <li><div<?=$error?>><span class="text-<?=$state?>"><?=$value?></span></div></li><?php
			$oldType = $type;
			while ($attribute = $entityAttributesHandler->fetch(PDO::FETCH_ASSOC)) {
				$type = $attribute['type'];
				$value = $attribute['attribute'];
				if ($otherEntity_id) {
					$state = ($added) ? 'success' : 'danger';
					$state = isset($otherAttributeValues[$type][$value]) ? 'dark' : $state;
				} else
					$state = 'dark';
				$error = ' class="alert-warning" role="alert"';
				if (isset($this->standardAttributes[$type])) {
					foreach ($this->standardAttributes[$type] as $data)
						if ($data['value'] == $value)
							$error = ($data['swamidStd']) ? '' : ' class="alert-danger" role="alert"';
				}
				if ($oldType != $type) {
					print "\n          </ul>";
					printf ("\n          <b>%s</b>\n          <ul>", $type);
					$oldType = $type;
				}
				printf ('%s            <li><div%s><span class="text-%s">%s</span></div></li>', "\n", $error, $state, $value);
			}?>

          </ul><?php
		}
	}

	####
	# Shows IdP info
	####
	function showIdP($Entity_id, $oldEntity_id=0, $allowEdit = false, $removable = false) {
		if ($removable)
			$removable = 'SSO';
		$this->showCollapse('IdP data', 'IdP', true, 0, true, $removable, $Entity_id);
		print '
          <div class="row">
            <div class="col-6">';
		$this->showErrorURL($Entity_id, $oldEntity_id, true, $allowEdit);
		$this->showScopes($Entity_id, $oldEntity_id, true, $allowEdit);
		if ($oldEntity_id != 0 ) {
			$this->showNewCol(1);
			$this->showErrorURL($oldEntity_id, $Entity_id);
			$this->showScopes($oldEntity_id, $Entity_id);
		}
		print '
            </div><!-- end col -->
          </div><!-- end row -->';
		if ($allowEdit)
			$this->showCollapse('MDUI', 'UIInfo_IDPSSO', false, 1, true, 'IdPMDUI', $Entity_id, $oldEntity_id);
		else
			$this->showCollapse('MDUI', 'UIInfo_IDPSSO', false, 1, true, false, $Entity_id, $oldEntity_id);
		$this->showMDUI($Entity_id, 'IDPSSO', $oldEntity_id, true);
		if ($oldEntity_id != 0 ) {
			$this->showNewCol(1);
			$this->showMDUI($oldEntity_id, 'IDPSSO', $Entity_id);
		}
		$this->showCollapseEnd('UIInfo_IdPSSO', 1);
		if ($allowEdit)
			$this->showCollapse('DiscoHints', 'DiscoHints', false, 1, true, 'DiscoHints', $Entity_id, $oldEntity_id);
		else
			$this->showCollapse('DiscoHints', 'DiscoHints', false, 1, true, false, $Entity_id, $oldEntity_id);
		$this->showDiscoHints($Entity_id, $oldEntity_id, true);
		if ($oldEntity_id != 0 ) {
			$this->showNewCol(1);
			$this->showDiscoHints($oldEntity_id, $Entity_id);
		}
		$this->showCollapseEnd('DiscoHints', 1);
		$this->showCollapse('KeyInfo', 'KeyInfo_IdPSSO', false, 1, true, false, $Entity_id, $oldEntity_id);
		$this->showKeyInfo($Entity_id, 'IDPSSO', $oldEntity_id, true, $allowEdit);
		if ($oldEntity_id != 0 ) {
			$this->showNewCol(1);
			$this->showKeyInfo($oldEntity_id, 'IDPSSO', $Entity_id);
		}
		$this->showCollapseEnd('KeyInfo_IdPSSO', 1);
		$this->showCollapseEnd('IdP', 0);
	}

	####
	# Shows SP info
	####
	function showSp($Entity_id, $oldEntity_id=0, $allowEdit = false, $removable = false) {
		if ($removable)
			$removable = 'SSO';
		$this->showCollapse('SP data', 'SP', true, 0, true, $removable, $Entity_id);
		if ($allowEdit)
			$this->showCollapse('MDUI', 'UIInfo_SPSSO', false, 1, true, 'SPMDUI', $Entity_id, $oldEntity_id);
		else
			$this->showCollapse('MDUI', 'UIInfo_SPSSO', false, 1, true, false, $Entity_id, $oldEntity_id);
		$this->showMDUI($Entity_id, 'SPSSO', $oldEntity_id, true);
		if ($oldEntity_id != 0 ) {
			$this->showNewCol(1);
			$this->showMDUI($oldEntity_id, 'SPSSO', $Entity_id);
		}
		$this->showCollapseEnd('UIInfo_SPSSO', 1);

		$this->showCollapse('KeyInfo', 'KeyInfo_SPSSO', false, 1, true, false, $Entity_id, $oldEntity_id);
		$this->showKeyInfo($Entity_id, 'SPSSO', $oldEntity_id, true, $allowEdit);
		if ($oldEntity_id != 0 ) {
			$this->showNewCol(1);
			$this->showKeyInfo($oldEntity_id, 'SPSSO', $Entity_id);
		}
		$this->showCollapseEnd('KeyInfo_SPSSO', 1);
		if ($allowEdit)
			$this->showCollapse('AttributeConsumingService', 'AttributeConsumingService', false, 1, true, 'AttributeConsumingService', $Entity_id, $oldEntity_id);
		else
			$this->showCollapse('AttributeConsumingService', 'AttributeConsumingService', false, 1, true, false, $Entity_id, $oldEntity_id);

		$this->showAttributeConsumingService($Entity_id, $oldEntity_id, true);
		if ($oldEntity_id != 0 ) {
			$this->showNewCol(1);
			$this->showAttributeConsumingService($oldEntity_id, $Entity_id);
		}
		$this->showCollapseEnd('AttributeConsumingService', 1);
		$this->showCollapseEnd('SP', 0);
	}

	####
	# Shows erroURL
	####
	private function showErrorURL($Entity_id, $otherEntity_id=0, $added = false, $allowEdit = false) {
		$errorURLHandler = $this->metaDb->prepare("SELECT DISTINCT `URL` FROM EntityURLs WHERE `entity_id` = :Id AND `type` = 'error';");
		if ($otherEntity_id) {
			$errorURLHandler->bindParam(':Id', $otherEntity_id);
			$errorURLHandler->execute();
			if ($errorURL = $errorURLHandler->fetch(PDO::FETCH_ASSOC))
				$otherURL = $errorURL['URL'];
			else
				$otherURL = '';
			$state = ($added) ? 'success' : 'danger';
		} else {
			$otherURL = '';
			$state = 'dark';
		}
		$errorURLHandler->bindParam(':Id', $Entity_id);
		$errorURLHandler->execute();
		$edit = $allowEdit ? sprintf(' <a href="?edit=IdPErrorURL&Entity=%d&oldEntity=%d"><i class="fa fa-pencil-alt"></i></a>', $Entity_id, $otherEntity_id) : '';
		if ($errorURL = $errorURLHandler->fetch(PDO::FETCH_ASSOC)) {
			$thisURL = $errorURL['URL'];
		} else {
			$thisURL = '';
			$state = 'dark';
		}
		if ($otherEntity_id) {
			$state = $thisURL == $otherURL ? 'dark' : $state;
		}
		printf('%s              <b>errorURL%s</b>%s              <ul><li><p class="text-%s" style="white-space: nowrap;overflow: hidden;text-overflow: ellipsis;max-width: 30em;">', "\n", $edit, "\n", $state);
		if ($thisURL != '')
			printf ('<a href="%s" class="text-%s" target="blank">%s</a>', $thisURL, $state, $thisURL);
		else
			print 'Missing';
		print '</p></li></ul>';
	}

	####
	# Shows showScopes
	####
	private function showScopes($Entity_id, $otherEntity_id=0, $added = false, $allowEdit = false) {
		$scopesHandler = $this->metaDb->prepare('SELECT `scope`, `regexp` FROM Scopes WHERE `entity_id` = :Id;');
		if ($otherEntity_id) {
			$scopesHandler->bindParam(':Id', $otherEntity_id);
			$scopesHandler->execute();
			while ($scope = $scopesHandler->fetch(PDO::FETCH_ASSOC)) {
				$otherScopes[$scope['scope']] = $scope['regexp'];
			}
		}
		$edit = $allowEdit ? sprintf(' <a href="?edit=IdPScopes&Entity=%d&oldEntity=%d"><i class="fa fa-pencil-alt"></i></a>', $Entity_id, $otherEntity_id) : '';
		print "\n              <b>Scopes$edit</b>
              <ul>\n";
		$scopesHandler->bindParam(':Id', $Entity_id);
		$scopesHandler->execute();
		while ($scope = $scopesHandler->fetch(PDO::FETCH_ASSOC)) {
			if ($otherEntity_id) {
				$state = ($added) ? 'success' : 'danger';
				$state = (isset($otherScopes[$scope['scope']]) && $otherScopes[$scope['scope']] == $scope['regexp']) ? 'dark' : $state;
			} else
				$state = 'dark';
			printf ('                <li><span class="text-%s">%s (regexp="%s")</span></li>%s', $state, $scope['scope'], $scope['regexp'] ? 'true' : 'false', "\n");
		}
		print '              </ul>';
	}

	####
	# Shows mdui:UIInfo for IdP or SP
	####
	function showMDUI($Entity_id, $type, $otherEntity_id = 0, $added = false) {
		$mduiHandler = $this->metaDb->prepare('SELECT `element`, `lang`, `height`, `width`, `data` FROM Mdui WHERE `entity_id` = :Id AND `type` = :Type ORDER BY `lang`, `element`;');
		$mduiHandler->bindParam(':Type', $type);
		$otherMDUIElements = array();
		$mduiHandler->bindParam(':Id', $otherEntity_id);
		$mduiHandler->execute();
		while ($mdui = $mduiHandler->fetch(PDO::FETCH_ASSOC)) {
			$element = $mdui['element'];
			$size = $mdui['height'].'x'.$mdui['width'];
			if (! isset($otherMDUIElements[$mdui['lang']]) )
				$otherMDUIElements[$mdui['lang']] = array();
			if (! isset($otherMDUIElements[$mdui['lang']][$element]) )
				$otherMDUIElements[$mdui['lang']][$element] = array();
			$otherMDUIElements[$mdui['lang']][$element][$size] = array('value' => $mdui['data'], 'height' => $mdui['height'], 'width' => $mdui['width'], 'state' => 'removed');
		}

		$oldLang = 'xxxxxxx';
		$mduiHandler->bindParam(':Id', $Entity_id);
		$mduiHandler->execute();
		$showEndUL = false;
		while ($mdui = $mduiHandler->fetch(PDO::FETCH_ASSOC)) {
			if ($oldLang != $mdui['lang']) {
				$lang = $mdui['lang'];
				if (isset($this->langCodes[$lang])) {
					$fullLang = $this->langCodes[$lang];
				} elseif ($lang == "") {
					$fullLang = "(NOT ALLOWED - switch to en/sv)";
				} else {
					$fullLang = "Unknown";
				}

				printf('%s                <b>Lang = "%s" - %s</b>%s                <ul>', $showEndUL ? "\n                </ul>\n" : "\n", $lang, $fullLang, "\n");
				$showEndUL = true;
				$oldLang = $lang;
			}
			$element = $mdui['element'];
			$size = $mdui['height'].'x'.$mdui['width'];
			$data = $mdui['data'];
			if ($otherEntity_id) {
				$state = ($added) ? 'success' : 'danger';
				if (isset ($otherMDUIElements[$lang]) && isset ($otherMDUIElements[$lang][$element]) && isset ($otherMDUIElements[$lang][$element][$size])) {
					if ($otherMDUIElements[$lang][$element][$size]['value'] == $data) {
						$state = 'dark';
						$otherMDUIElements[$lang][$element][$size]['state'] = 'same';
					} else {
						$otherMDUIElements[$lang][$element][$size]['state'] = 'changed';
					}
				}
			} else
				$state = 'dark';
			switch ($element) {
				case 'InformationURL' :
				case 'Logo' :
				case 'PrivacyStatementURL' :
					$data = sprintf ('<a href="%s" class="text-%s" target="blank">%s</a>', $data, $state, $data);
					break;
			}
			if ($element == 'Logo') {
				printf ('%s                  <li><span class="text-%s">%s (%s) = %s</span></li>', "\n", $state, $element, $size, $data);
			} else {
				printf ('%s                  <li><span class="text-%s">%s = %s</span></li>', "\n", $state, $element, $data);
			}
		}
		if ($showEndUL) {
			print "\n                </ul>";
		}
	}

	####
	# Shows mdui:DiscoHints for IdP
	####
	function showDiscoHints($Entity_id, $otherEntity_id=0, $added = false) {
		$mduiHandler = $this->metaDb->prepare("SELECT `element`, `data` FROM Mdui WHERE `entity_id` = :Id AND `type` = 'IDPDisco' ORDER BY `element`;");
		$otherMDUIElements = array();
		$mduiHandler->bindParam(':Id', $otherEntity_id);
		$mduiHandler->execute();
		while ($mdui = $mduiHandler->fetch(PDO::FETCH_ASSOC)) {
			$element = $mdui['element'];
			if (! isset($otherMDUIElements[$element]) )
				$otherMDUIElements[$element] = array();
			$otherMDUIElements[$element][$mdui['data']] = true;
		}

		$oldElement = 'xxxxxxx';
		$mduiHandler->bindParam(':Id', $Entity_id);
		$mduiHandler->execute();
		$showEndUL = false;
		while ($mdui = $mduiHandler->fetch(PDO::FETCH_ASSOC)) {
			$element = $mdui['element'];
			$data = $mdui['data'];
			if ($oldElement != $element) {
				printf('%s                <b>%s</b>%s                <ul>', $showEndUL ? "\n                </ul>\n" : "\n", $element, "\n");
				$showEndUL = true;
				$oldElement = $element;
			}

			if ($otherEntity_id) {
				$state = ($added) ? 'success' : 'danger';
				if (isset ($otherMDUIElements[$element]) && isset ($otherMDUIElements[$element][$data])) {
					$state = 'dark';
				}
			} else
				$state = 'dark';
			printf ('%s                  <li><span class="text-%s">%s</span></li>', "\n", $state, $data);
		}
		if ($showEndUL) {
			print "\n                </ul>";
		}
	}

	####
	# Shows KeyInfo for IdP or SP
	####
	function showKeyInfo($Entity_id, $type, $otherEntity_id=0, $added = false, $removable = false) {
		$KeyInfoCountHandler = $this->metaDb->prepare('SELECT `use`, COUNT(`use`) AS count FROM KeyInfo WHERE entity_id = :Id AND type = :Type GROUP BY `use`');
		$KeyInfoCountHandler->bindParam(':Type', $type);
		$KeyInfoCountHandler->bindParam(':Id', $Entity_id);
		$KeyInfoCountHandler->execute();
		$extraEncryptionFound = false;
		$extraSigningFound = false;
		while ($keyInfoCount = $KeyInfoCountHandler->fetch(PDO::FETCH_ASSOC)) {
			if ($keyInfoCount['count'] > 1) {
				switch ($keyInfoCount['use']) {
					case 'encryption' :
						$extraEncryptionFound = true;
						break;
					case 'signing' :
						$extraSigningFound = true;
						break;
					case 'both' :
						$extraEncryptionFound = true;
						$extraSigningFound = true;
						break;
				}
			}
		}

		$keyInfoHandler = $this->metaDb->prepare('SELECT `use`, `name`, `notValidAfter`, `subject`, `issuer`, `bits`, `key_type`, `hash`, `serialNumber` FROM KeyInfo WHERE `entity_id` = :Id AND `type` = :Type ORDER BY notValidAfter DESC;');
		$keyInfoHandler->bindParam(':Type', $type);
		if ($otherEntity_id) {
			$otherKeyInfos = array();
			$keyInfoHandler->bindParam(':Id', $otherEntity_id);
			$keyInfoHandler->execute();

			while ($keyInfo = $keyInfoHandler->fetch(PDO::FETCH_ASSOC)) {
				$otherKeyInfos[$keyInfo['hash']][$keyInfo['use']] = 'removed';
			}
		}

		$keyInfoHandler->bindParam(':Id', $Entity_id);
		$keyInfoHandler->execute();

		$validEncryptionFound = false;
		$validSigningFound = false;
		$timeNow = date('Y-m-d H:i:00');
		$timeWarn = date('Y-m-d H:i:00', time() + 7776000);  // 90 * 24 * 60 * 60 = 90 days / 3 month
		while ($keyInfo = $keyInfoHandler->fetch(PDO::FETCH_ASSOC)) {
			$okRemove = false;
			$error = '';
			$validCertExists = false;
			switch ($keyInfo['use']) {
				case 'encryption' :
					$use = 'encryption';
					$okRemove = $extraEncryptionFound;
					if ($keyInfo['notValidAfter'] > $timeNow ) {
						$validEncryptionFound = true;
					} elseif ($validEncryptionFound) {
						$validCertExists = true;
					}
					break;
				case 'signing' :
					$use = 'signing';
					$okRemove = $extraSigningFound;
					if ($keyInfo['notValidAfter'] > $timeNow ) {
						$validSigningFound = true;
					} elseif ($validSigningFound) {
						$validCertExists = true;
					}
					break;
				case 'both' :
					$use = 'encryption & signing';
					$okRemove = ($extraEncryptionFound && $extraSigningFound);
					if ($keyInfo['notValidAfter'] > $timeNow ) {
						$validEncryptionFound = true;
						$validSigningFound = true;
					} else if ($validEncryptionFound &&  $validSigningFound) {
						$validCertExists = true;
					}
					break;
			}
			$name = $keyInfo['name'] == '' ? '' : '(' . $keyInfo['name'] .')';

			if ($keyInfo['notValidAfter'] <= $timeNow ) {
				$error = ($validCertExists) ? ' class="alert-warning" role="alert"' : ' class="alert-danger" role="alert"';
			} elseif ($keyInfo['notValidAfter'] <= $timeWarn ) {
				$error = ' class="alert-warning" role="alert"';
			}

			if (($keyInfo['bits'] < 2048 && $keyInfo['key_type'] == "RSA") || $keyInfo['bits'] < 256) {
				$error = ' class="alert-danger" role="alert"';
			}

			if ($otherEntity_id) {
				$state = ($added) ? 'success' : 'danger';
				if (isset($otherKeyInfos[$keyInfo['hash']][$keyInfo['use']])) {
					$state = 'dark';
				}
			} else
				$state = 'dark';
				$extraButton = $okRemove && $removable ? sprintf(' <a href="?removeKey=%d&type=%s&use=%s&serialNumber=%s"><i class="fas fa-trash"></i></a>', $Entity_id, $type, $keyInfo['use'], $keyInfo['serialNumber']) : '';
			printf('%s                <span class="text-%s text-truncate"><b>KeyUse = "%s"</b> %s%s</span>
                <ul%s>
                  <li>notValidAfter = %s</li>
                  <li>Subject = %s</li>
                  <li>Issuer = %s</li>
                  <li>Type / bits = %s / %d</li>
                  <li>Key Hash = %s</li>
                  <li>Serial Number = %s</li>
                </ul>', "\n", $state, $use, $name, $extraButton, $error, $keyInfo['notValidAfter'], $keyInfo['subject'], $keyInfo['issuer'], $keyInfo['key_type'], $keyInfo['bits'], $keyInfo['hash'], $keyInfo['serialNumber']);
		}
	}

	####
	# Shows AttributeConsumingService for a SP
	####
	function showAttributeConsumingService($Entity_id, $otherEntity_id=0, $added = false) {
		$serviceIndexHandler = $this->metaDb->prepare('SELECT `Service_index` FROM AttributeConsumingService WHERE `entity_id` = :Id;');
		$serviceElementHandler = $this->metaDb->prepare('SELECT `element`, `lang`, `data` FROM AttributeConsumingService_Service WHERE `entity_id` = :Id AND `Service_index` = :Index ORDER BY `element` DESC, `lang`;');

		$serviceElementHandler->bindParam(':Index', $serviceIndex);
		$requestedAttributeHandler = $this->metaDb->prepare('SELECT `FriendlyName`, `Name`, `NameFormat`, `isRequired` FROM AttributeConsumingService_RequestedAttribute WHERE `entity_id` = :Id AND `Service_index` = :Index ORDER BY `isRequired` DESC, `FriendlyName`;');
		$requestedAttributeHandler->bindParam(':Index', $serviceIndex);
		if ($otherEntity_id) {
			$serviceIndexHandler->bindParam(':Id', $otherEntity_id);
			$serviceElementHandler->bindParam(':Id', $otherEntity_id);
			$requestedAttributeHandler->bindParam(':Id', $otherEntity_id);
			$serviceIndexHandler->execute();
			while ($index = $serviceIndexHandler->fetch(PDO::FETCH_ASSOC)) {
				$serviceIndex = $index['Service_index'];
				$otherServiceElements[$serviceIndex] = array();
				$otherRequestedAttributes[$serviceIndex] = array();
				$serviceElementHandler->execute();
				while ($serviceElement = $serviceElementHandler->fetch(PDO::FETCH_ASSOC)) {
					$otherServiceElements[$serviceIndex][$serviceElement['element']][$serviceElement['lang']] = $serviceElement['data'];
				}
				$requestedAttributeHandler->execute();
				while ($requestedAttribute = $requestedAttributeHandler->fetch(PDO::FETCH_ASSOC)) {
					$otherRequestedAttributes[$serviceIndex][$requestedAttribute['Name']] = $requestedAttribute['isRequired'];
				}
			}
		}

		$serviceIndexHandler->bindParam(':Id', $Entity_id);
		$serviceElementHandler->bindParam(':Id', $Entity_id);
		$requestedAttributeHandler->bindParam(':Id', $Entity_id);

		$serviceIndexHandler->execute();
		while ($index = $serviceIndexHandler->fetch(PDO::FETCH_ASSOC)) {
			$serviceIndex = $index['Service_index'];
			$serviceElementHandler->execute();
			printf ('%s                <b>Index = %d</b>%s                <ul>', "\n", $serviceIndex, "\n");
			while ($serviceElement = $serviceElementHandler->fetch(PDO::FETCH_ASSOC)) {
				if ($otherEntity_id) {
					$state = ($added) ? 'success' : 'danger';
					$state = (isset($otherServiceElements[$serviceIndex][$serviceElement['element']][$serviceElement['lang']]) && $otherServiceElements[$serviceIndex][$serviceElement['element']][$serviceElement['lang']] == $serviceElement['data'] ) ? 'dark' : $state;
				} else {
					$state = 'dark';
				}
				printf('%s                  <li><span class="text-%s">%s[%s] = %s</span></li>', "\n", $state, $serviceElement['element'], $serviceElement['lang'], $serviceElement['data']);
			}
			$requestedAttributeHandler->execute();
			print "\n                  <li>RequestedAttributes : <ul>";
			while ($requestedAttribute = $requestedAttributeHandler->fetch(PDO::FETCH_ASSOC)) {
				if ($otherEntity_id) {
					$state = ($added) ? 'success' : 'danger';
					$state = (isset ($otherRequestedAttributes[$serviceIndex][$requestedAttribute['Name']]) && $otherRequestedAttributes[$serviceIndex][$requestedAttribute['Name']] == $requestedAttribute['isRequired'] ) ? 'dark' : $state;
				} else {
					$state = 'dark';
				}
				$error = '';
				if ($requestedAttribute['FriendlyName'] == '') {
					if (isset($this->FriendlyNames[$requestedAttribute['Name']])) {
						$FriendlyNameDisplay = sprintf('(%s)', $this->FriendlyNames[$requestedAttribute['Name']]['desc']);
						if (! $this->FriendlyNames[$requestedAttribute['Name']]['swamidStd'])
							$error = ' class="alert-warning" role="alert"';
					} else {
						$FriendlyNameDisplay = '(Unknown)';
						$error = ' class="alert-warning" role="alert"';
					}
				} else {
					$FriendlyNameDisplay = $requestedAttribute['FriendlyName'];
					if (isset ($this->FriendlyNames[$requestedAttribute['Name']])) {
						if ($requestedAttribute['FriendlyName'] != $this->FriendlyNames[$requestedAttribute['Name']]['desc'] || ! $this->FriendlyNames[$requestedAttribute['Name']]['swamidStd']) {
							$error = ' class="alert-warning" role="alert"';
						}
					} else {
						$error = ' class="alert-warning" role="alert"';
					}
				}
				printf('%s                    <li%s><span class="text-%s"><b>%s</b> - %s%s</span></li>', "\n", $error, $state, $FriendlyNameDisplay, $requestedAttribute['Name'], $requestedAttribute['isRequired'] == '1' ? ' (Required)' : '');
			}
			print "\n                  </ul></li>\n                </ul>";
		}
	}

	####
	# Shows Organization information if exists
	####
	function showOrganization($Entity_id, $oldEntity_id=0, $allowEdit = false) {
		if ($allowEdit)
			$this->showCollapse('Organization', 'Organization', false, 0, true, 'Organization', $Entity_id, $oldEntity_id);
		else
			$this->showCollapse('Organization', 'Organization', false, 0, true, false, $Entity_id, $oldEntity_id);
		$this->showOrganizationPart($Entity_id, $oldEntity_id, 1);
		if ($oldEntity_id != 0 ) {
			$this->showNewCol(0);
			$this->showOrganizationPart($oldEntity_id, $Entity_id, 0);
		}
		$this->showCollapseEnd('Organization', 0);
	}
	function showOrganizationPart($Entity_id, $otherEntity_id, $added) {
		$organizationHandler = $this->metaDb->prepare('SELECT `element`, `lang`, `data` FROM Organization WHERE `entity_id` = :Id ORDER BY `element`, `lang`;');
		if ($otherEntity_id) {
			$organizationHandler->bindParam(':Id', $otherEntity_id);
			$organizationHandler->execute();
			while ($organization = $organizationHandler->fetch(PDO::FETCH_ASSOC)) {
				if (! isset($otherOrganizationElements[$organization['element']]) )
					$otherOrganizationElements[$organization['element']] = array();
				$otherOrganizationElements[$organization['element']][$organization['lang']] = $organization['data'];
			}
		}
		$organizationHandler->bindParam(':Id', $Entity_id);
		$organizationHandler->execute();
		print ("\n        <ul>");
		while ($organization = $organizationHandler->fetch(PDO::FETCH_ASSOC)) {
			if ($otherEntity_id) {
				$state = ($added) ? 'success' : 'danger';
				$state = (isset ($otherOrganizationElements[$organization['element']][$organization['lang']]) && $otherOrganizationElements[$organization['element']][$organization['lang']] == $organization['data'] ) ? 'dark' : $state;
			} else {
				$state = 'dark';
			}
			if ($organization['element'] == 'OrganizationURL' ) {
				printf ('%s          <li><span class="text-%s">%s[%s] = <a href="%s" class="text-%s">%s</a></span></li>', "\n", $state, $organization['element'], $organization['lang'], $organization['data'], $state, $organization['data']);
			} else {
				printf ('%s          <li><span class="text-%s">%s[%s] = %s</span></li>', "\n", $state, $organization['element'], $organization['lang'], $organization['data']);
			}
		}
		print ("\n        </ul>");
	}

	####
	# Shows Contact information if exists
	####
	function showContacts($Entity_id, $oldEntity_id=0, $allowEdit = false) {
		if ($allowEdit)
			$this->showCollapse('ContactPersons', 'ContactPersons', false, 0, true, 'ContactPersons', $Entity_id, $oldEntity_id);
		else
			$this->showCollapse('ContactPersons', 'ContactPersons', false, 0, true, false, $Entity_id, $oldEntity_id);
		$this->showContactsPart($Entity_id, $oldEntity_id, 1);
		if ($oldEntity_id != 0 ) {
			$this->showNewCol(0);
			$this->showContactsPart($oldEntity_id, $Entity_id, 0);
		}
		$this->showCollapseEnd('ContactPersons', 0);
	}
	private function showContactsPart($Entity_id, $otherEntity_id, $added) {
		$contactPersonHandler = $this->metaDb->prepare('SELECT * FROM ContactPerson WHERE `entity_id` = :Id ORDER BY `contactType`;');
		if ($otherEntity_id) {
			$contactPersonHandler->bindParam(':Id', $otherEntity_id);
			$contactPersonHandler->execute();
			while ($contactPerson = $contactPersonHandler->fetch(PDO::FETCH_ASSOC)) {
				if (! isset($otherContactPersons[$contactPerson['contactType']]))
					$otherContactPersons[$contactPerson['contactType']] = array(
						'company' => '',
						'givenName' => '',
						'surName' => '',
						'emailAddress' => '',
						'telephoneNumber' => '',
						'extensions' => ''
					);
				if ($contactPerson['company']) {
						$otherContactPersons[$contactPerson['contactType']]['company'] = $contactPerson['company'];
				}
				if ($contactPerson['givenName']) {
						$otherContactPersons[$contactPerson['contactType']]['givenName'] = $contactPerson['givenName'];
				}
				if ($contactPerson['surName']) {
						$otherContactPersons[$contactPerson['contactType']]['surName'] = $contactPerson['surName'];
				}
				if ($contactPerson['emailAddress']) {
						$otherContactPersons[$contactPerson['contactType']]['emailAddress'] = $contactPerson['emailAddress'];
				}
				if ($contactPerson['telephoneNumber']) {
						$otherContactPersons[$contactPerson['contactType']]['telephoneNumber'] = $contactPerson['telephoneNumber'];
				}
				if ($contactPerson['extensions']) {
						$otherContactPersons[$contactPerson['contactType']]['extensions'] = $contactPerson['extensions'];
				}
			}
		}
		$contactPersonHandler->bindParam(':Id', $Entity_id);
		$contactPersonHandler->execute();
		while ($contactPerson = $contactPersonHandler->fetch(PDO::FETCH_ASSOC)) {
			if ($contactPerson['subcontactType'] == '')
				printf ("\n        <b>%s</b><br>\n", $contactPerson['contactType']);
			else
				printf ("\n        <b>%s[%s]</b><br>\n", $contactPerson['contactType'], $contactPerson['subcontactType']);
			print "        <ul>\n";
			if ($contactPerson['company']) {
				if ($otherEntity_id) {
					$state = ($added) ? 'success' : 'danger';
					$state = (isset ($otherContactPersons[$contactPerson['contactType']]) && $otherContactPersons[$contactPerson['contactType']]['company'] == $contactPerson['company']) ? 'dark' : $state;
				} else
					$state = 'dark';
				printf ('          <li><span class="text-%s">Company = %s</span></li>%s', $state, $contactPerson['company'], "\n");
			}
			if ($contactPerson['givenName']) {
				if ($otherEntity_id) {
					$state = ($added) ? 'success' : 'danger';
					$state = (isset ($otherContactPersons[$contactPerson['contactType']]) && $otherContactPersons[$contactPerson['contactType']]['givenName'] == $contactPerson['givenName']) ? 'dark' : $state;
				} else
					$state = 'dark';
				printf ('          <li><span class="text-%s">GivenName = %s</span></li>%s', $state, $contactPerson['givenName'], "\n");
			}
			if ($contactPerson['surName']) {
				if ($otherEntity_id) {
					$state = ($added) ? 'success' : 'danger';
					$state = (isset ($otherContactPersons[$contactPerson['contactType']]) && $otherContactPersons[$contactPerson['contactType']]['surName'] == $contactPerson['surName']) ? 'dark' : $state;
				} else
					$state = 'dark';
				printf ('          <li><span class="text-%s">SurName = %s</span></li>%s', $state, $contactPerson['surName'], "\n");
			}
			if ($contactPerson['emailAddress']) {
				if ($otherEntity_id) {
					$state = ($added) ? 'success' : 'danger';
					$state = (isset ($otherContactPersons[$contactPerson['contactType']]) && $otherContactPersons[$contactPerson['contactType']]['emailAddress'] == $contactPerson['emailAddress']) ? 'dark' : $state;
				} else
					$state = 'dark';
				printf ('          <li><span class="text-%s">EmailAddress = %s</span></li>%s', $state, $contactPerson['emailAddress'], "\n");
			}
			if ($contactPerson['telephoneNumber']) {
				if ($otherEntity_id) {
					$state = ($added) ? 'success' : 'danger';
					$state = (isset ($otherContactPersons[$contactPerson['contactType']]) && $otherContactPersons[$contactPerson['contactType']]['telephoneNumber'] == $contactPerson['telephoneNumber']) ? 'dark' : $state;
				} else
					$state = 'dark';
				printf ('          <li><span class="text-%s">TelephoneNumber = %s</span></li>%s', $state, $contactPerson['telephoneNumber'], "\n");
			}
			if ($contactPerson['extensions']) {
				if ($otherEntity_id) {
					$state = ($added) ? 'success' : 'danger';
					$state = (isset ($otherContactPersons[$contactPerson['contactType']]) && $otherContactPersons[$contactPerson['contactType']]['extensions'] == $contactPerson['extensions']) ? 'dark' : $state;
				} else
					$state = 'dark';
				printf ('          <li><span class="text-%s">Extensions = %s</span></li>%s', $state, $contactPerson['extensions'], "\n");
			}
			print "        </ul>";
		}
	}

	####
	# Shows XML for entiry
	####
	function showXML($Entity_id) {
		printf ('%s    <h4><i class="fas fa-chevron-circle-right"></i> <a href=".?rawXML=%s" target="_blank">Show XML</a></h4>%s    <h4><i class="fas fa-chevron-circle-right"></i> <a href=".?rawXML=%s&download" target="_blank">Download XML</a></h4>%s', "\n", $Entity_id, "\n", $Entity_id, "\n");
	}

	public function showRawXML($Entity_id) {
		$entityHandler = $this->metaDb->prepare('SELECT `xml` FROM Entities WHERE `id` = :Id;');
		$entityHandler->bindParam(':Id', $Entity_id);
		$entityHandler->execute();
		if ($entity = $entityHandler->fetch(PDO::FETCH_ASSOC)) {
			header('Content-Type: application/xml; charset=utf-8');
			if (isset($_GET['download']))
				header('Content-Disposition: attachment; filename=metadata.xml');
			print $entity['xml'];
		} else
			print "Not Found";
		exit;
	}

	public function showEditors($Entity_id){
		$this->showCollapse('Editors', 'Editors', false, 0, true, false, $Entity_id, 0);
		$usersHandler = $this->metaDb->prepare('SELECT * FROM Users WHERE `entity_id` = :Id ORDER BY `userID`;');
		$usersHandler->bindParam(':Id', $Entity_id);
		$usersHandler->execute();
		print "        <ul>\n";
		while ($user = $usersHandler->fetch(PDO::FETCH_ASSOC)) {
			printf ('          <li>%s, %s</li>%s', $user['userID'], $user['email'], "\n");
		}
		print "        </ul>";
		$this->showCollapseEnd('Editors', 0);
	}

	public function showURLStatus($url = false){
		if($url) {
			$missing = true;
			$CoCoV1 = false;
			$Logo = false;
			$URLType = 0;
			$URLHandler = $this->metaDb->prepare('SELECT `type`, `validationOutput`, `lastValidated` FROM URLs WHERE `URL` = :URL');
			$URLHandler->bindValue(':URL', $url);
			$URLHandler->execute();
			$EntityHandler = $this->metaDb->prepare('SELECT `entity_id`, `entityID`, `status` FROM EntityURLs, Entities WHERE entity_id = id AND `URL` = :URL');
			$EntityHandler->bindValue(':URL', $url);
			$EntityHandler->execute();
			$SSOUIIHandler = $this->metaDb->prepare('SELECT `entity_id`, `type`, `element`, `lang`, `entityID`, `status` FROM Mdui, Entities WHERE entity_id = id AND `data` = :URL');
			$SSOUIIHandler->bindValue(':URL', $url);
			$SSOUIIHandler->execute();
			$OrganizationHandler = $this->metaDb->prepare('SELECT `entity_id`, `element`, `lang`, `entityID`, `status` FROM Organization, Entities WHERE entity_id = id AND `data` = :URL');
			$OrganizationHandler->bindValue(':URL', $url);
			$OrganizationHandler->execute();
			$entityAttributesHandler = $this->metaDb->prepare("SELECT `attribute` FROM EntityAttributes WHERE `entity_id` = :Id AND type = 'entity-category'");

			printf ('    <table class="table table-striped table-bordered">%s', "\n");
			printf ('      <tr><th>URL</th><td>%s</td></tr>%s', $url, "\n");
			if ($URLInfo = $URLHandler->fetch(PDO::FETCH_ASSOC)) {
				printf ('      <tr><th>Checked</th><td>%s (UTC) <a href=".?action=%s&URL=%s&recheck"><button type="button" class="btn btn-primary">Recheck now</button></a></td></tr>%s', $URLInfo['lastValidated'], $_GET['action'] ,urlencode($url), "\n");
				printf ('      <tr><th>Status</th><td>%s</td></tr>%s', $URLInfo['validationOutput'] , "\n");
				$URLType = $URLInfo['type'];
			}
			printf ('    </table>%s    <table class="table table-striped table-bordered">%s      <tr><th>Entity</th><th>Part</th><th></tr>%s', "\n", "\n", "\n");
			while ($Entity = $EntityHandler->fetch(PDO::FETCH_ASSOC)) {
				printf ('      <tr><td><a href="?showEntity=%d">%s</td><td>%s</td><tr>%s', $Entity['entity_id'], $Entity['entityID'], 'ErrorURL', "\n");
				$missing = false;
			}
			while ($Entity = $SSOUIIHandler->fetch(PDO::FETCH_ASSOC)) {
				$ECInfo = '';
				if ($Entity['type'] == 'SPSSO' && $Entity['element'] == 'PrivacyStatementURL') {
					$entityAttributesHandler->bindParam(':Id', $Entity['entity_id']);
					$entityAttributesHandler->execute();
					while ($attribute = $entityAttributesHandler->fetch(PDO::FETCH_ASSOC)) {
						if ($attribute['attribute'] == 'http://www.geant.net/uri/dataprotection-code-of-conduct/v1') {
							$ECInfo = ' CoCo';
							$CoCoV1 = true;
						}
					}
				}
				switch ($Entity['element']) {
					case 'Logo' :
						$Logo = true;
					case 'InformationURL' :
					case 'PrivacyStatementURL' :
						printf ('      <tr><td><a href="?showEntity=%d">%s</a> (%s)</td><td>%s:%s[%s]%s</td><tr>%s', $Entity['entity_id'], $Entity['entityID'], $this->getEntityStatusType($Entity['status']), substr($Entity['type'],0,-3), $Entity['element'], $Entity['lang'], $ECInfo, "\n");
						$missing = false;
						break;
				}
			}
			while ($Entity = $OrganizationHandler->fetch(PDO::FETCH_ASSOC)) {
				if ($Entity['element'] == 'OrganizationURL') {
					printf ('      <tr><td><a href="?showEntity=%d">%s</a> (%s)</td><td>%s[%s]</td><tr>%s', $Entity['entity_id'], $Entity['entityID'], $this->getEntityStatusType($Entity['status']),  $Entity['element'], $Entity['lang'], "\n");
					$missing = false;
				}
			}
			print "    </table>\n";
			if ($missing) {
				$URLHandler = $this->metaDb->prepare('DELETE FROM URLs WHERE `URL` = :URL');
				$URLHandler->bindValue(':URL', $url);
				$URLHandler->execute();
				print "Not used anymore, removed";
			}
			if ($URLType > 2 && !$CoCoV1 ) {
				if ($Logo)
					$URLHandler = $this->metaDb->prepare('UPDATE URLs SET `type` = 2 WHERE `URL` = :URL');
				else
					$URLHandler = $this->metaDb->prepare('UPDATE URLs SET `type` = 1 WHERE `URL` = :URL');
				$URLHandler->bindValue(':URL', $url);
				$URLHandler->execute();
				print "Not CoCo v1 any more. Removes that flag.";
			}
		} else {
			$oldType = 0;
			$URLHandler = $this->metaDb->prepare("SELECT `URL`, `type`, `status`, `cocov1Status`, `lastValidated`, `lastSeen`, `validationOutput` FROM URLs WHERE `status` > 0 OR `cocov1Status` > 0 ORDER BY type DESC, `URL`;");
			$URLHandler->execute();

			while ($URL = $URLHandler->fetch(PDO::FETCH_ASSOC)) {
				if ($oldType != $URL['type']) {
					switch ($URL['type']) {
						case 1:
							$typeInfo = 'URL check';
							break;
						case 2:
							$typeInfo = 'URL check - Needs to be reachable';
							break;
						case 3:
							$typeInfo = 'CoCo - PrivacyURL';
							break;
						default :
							$typeInfo = '?' . $URL['type'];
					}
					if ($oldType > 0)
						print "    </table>\n";
					printf ('    <h3>%s</h3>%s    <table class="table table-striped table-bordered">%s      <tr><th>URL</th><th>Last seen</th><th>Last validated</th><th>Result</th></tr>%s', $typeInfo, "\n", "\n", "\n");
					$oldType = $URL['type'];
				}
				printf ('      <tr><td><a href="?action=URLlist&URL=%s">%s</a></td><td>%s</td><td>%s</td><td>%s</td><tr>%s', urlencode($URL['URL']), $URL['URL'], $URL['lastSeen'], $URL['lastValidated'], $URL['validationOutput'], "\n");
			}
			if ($oldType > 0) print "    </table>\n";

			$warnTime = date('Y-m-d H:i', time() - 25200 ); // (7 * 60 * 60 =  7 hours)
			$warnTimeweek = date('Y-m-d H:i', time() - 608400 ); // (7 * 24 * 60 * 60 + 3600 =  7 days 1 hour)
			$URLWaitHandler = $this->metaDb->prepare("SELECT `URL`, `validationOutput`, `lastValidated`, `lastSeen`, `status` FROM URLs WHERE `lastValidated` < ADDTIME(NOW(), '-7 0:0:0') OR (`status` > 0 AND `lastValidated` < ADDTIME(NOW(), '-6:0:0')) ORDER BY `lastValidated`;");
			$URLWaitHandler->execute();
			printf ('    <h3>Waiting for validation</h3>%s    <table class="table table-striped table-bordered">%s      <tr><th>URL</th><th>Last seen</th><th>Last validated</th><th>Result</th></tr>%s', "\n", "\n", "\n");
			while ($URL = $URLWaitHandler->fetch(PDO::FETCH_ASSOC)) {
				$warn = (($URL['lastValidated'] < $warnTime && $URL['status'] > 0) || $URL['lastValidated'] < $warnTimeweek) ? '! ' : '';
				printf ('      <tr><td><a href="?action=URLlist&URL=%s">%s%s</td><td>%s</td><td>%s</td><td>%s</td><tr>%s', urlencode($URL['URL']), $warn, $URL['URL'], $URL['lastSeen'], $URL['lastValidated'], $URL['validationOutput'], "\n");
			}
			print "    </table>\n";

		}
	}

	private function getEntityStatusType($status) {
		switch ($status) {
			case 1 :
				return 'Published';
			case 2 :
				return 'Pending';
			case 3 :
				return 'Draft';
			case 4 :
				return 'Deleted';
			case 5 :
				return 'POST Pending';
			case 6 :
				return 'Shadow Pending';
			default :
				return $status . ' unknown status';
		}
	}

	public function showErrorList($download = false) {
		$emails = array();
		if (isset($_GET['showTesting'])) {
			$EntityHandler = $this->metaDb->prepare("SELECT `id`, `publishIn`, `isIdP`, `isSP`, `entityID`, `errors`, `errorsNB` FROM Entities WHERE (`errors` <> '' OR `errorsNB` <> '') AND `status` = 1 ORDER BY entityID");
		} else {
			$EntityHandler = $this->metaDb->prepare("SELECT `id`, `publishIn`, `isIdP`, `isSP`, `entityID`, `errors`, `errorsNB` FROM Entities WHERE (`errors` <> '' OR `errorsNB` <> '') AND `status` = 1 AND publishIn > 1 ORDER BY entityID");
		}
		$EntityHandler->execute();
		$contactPersonHandler = $this->metaDb->prepare('SELECT contactType, emailAddress FROM ContactPerson WHERE `entity_id` = :Id;');
		if ($download) {
			header('Content-Type: text/csv; charset=utf-8');
			header('Content-Disposition: attachment; filename=errorlog.csv');
			print "Type,Feed,Entity,Contact address\n";
		} else {
			printf ('    <a href=".?action=ErrorListDownload"><button type="button" class="btn btn-primary">Download CSV</button></a>%s    <a href=".?action=ErrorList&showTesting"><button type="button" class="btn btn-primary">Include testing</button></a><br>%s', "\n", "\n");
			printf ('    <table class="table table-striped table-bordered">%s      <tr><th>Type</th><th>Feed</th><th>Entity</th><th>Contact address</th><th>Error</th></tr>%s', "\n", "\n");
		}
		while ($Entity = $EntityHandler->fetch(PDO::FETCH_ASSOC)) {
			$contactPersonHandler->bindValue(':Id', $Entity['id']);
			$contactPersonHandler->execute();
			$emails['administrative'] = '';
			$emails['support'] = '';
			$emails['technical'] = '';
			while ($contact = $contactPersonHandler->fetch(PDO::FETCH_ASSOC)) {
				$emails[$contact['contactType']] = substr($contact['emailAddress'],7);
			}
			if ($emails['technical'] != '' ) {
				$email = $emails['technical'];
			} elseif($emails['administrative'] != '') {
				$email = $emails['administrative'];
			} elseif ($emails['support'] != '' ) {
				$email = $emails['support'];
			} else
				$email = 'Missing';
			$type = ($Entity['isIdP']) ? ($Entity['isSP']) ? 'IdP & SP' : 'IdP' : 'SP';
			switch ($Entity['publishIn']) {
				case 1 :
					$feed = 'T';
					break;
				case 3 :
					$feed = 'ES';
					break;
				case 7 :
					$feed = 'E';
					break;
				default :
					$feed = '?';
			}
			if ($download) {
				printf ('%s,%s,%s,%s%s', $type, $feed, $Entity['entityID'], $email, "\n");
			} else {
				printf ('      <tr><td>%s</td><td>%s</td><td><a href="?showEntity=%d"><span class="text-truncate">%s</span></td><td>%s</td><td>%s</td><tr>%s', $type, $feed, $Entity['id'], $Entity['entityID'], $email, str_ireplace("\n", "<br>",$Entity['errors'].$Entity['errorsNB']), "\n");
			}
			$missing = false;
		}
		if (! $download) {
			print "    </table>\n";
		}
	}

	public function showXMLDiff($entity_id1, $entity_id2) {
		$entityHandler = $this->metaDb->prepare('SELECT `id`, `entityID`, `xml` FROM Entities WHERE `id` = :ID');
		$entityHandler->bindValue(':ID', $entity_id1);
		$entityHandler->execute();
		if ($entity1 = $entityHandler->fetch(PDO::FETCH_ASSOC)) {
			$entityHandler->bindValue(':ID', $entity_id2);
			$entityHandler->execute();
			if ($entity2 = $entityHandler->fetch(PDO::FETCH_ASSOC)) {
				printf ('<h4>Diff of %s</h4>', $entity1['entityID']);
				require_once $this->baseDir . '/include/Diff.php';
				require_once $this->baseDir . '/include/Diff/Renderer/Text/Unified.php';
				$options = array(
					//'ignoreWhitespace' => true,
					//'ignoreCase' => true,
				);
				$diff = new Diff(explode("\n", $entity2['xml']), explode("\n", $entity1['xml']), $options);
				$renderer = new Diff_Renderer_Text_Unified;
				printf('<pre>%s</pre>', htmlspecialchars($diff->render($renderer)));
			} else {

			}
		}
	}

	public function showPendingListToRemove() {
		$entitiesHandler = $this->metaDb->prepare('SELECT `id`, `entityID`, `xml`, `lastUpdated`, `email` FROM Entities, Users WHERE `status` = 2 AND `id` = `entity_id` ORDER BY lastUpdated ASC, `entityID`');
		$entityHandler = $this->metaDb->prepare('SELECT `id`, `xml`, `lastUpdated` FROM Entities WHERE `status` = 1 AND `entityID` = :EntityID');
		$entityHandler->bindParam(':EntityID', $entityID);
		$entitiesHandler->execute();

		if (! class_exists('NormalizeXML')) {
			include $this->baseDir.'/include/NormalizeXML.php';
		}
		$normalize = new NormalizeXML();

		printf ('    <table class="table table-striped table-bordered">%s      <tr><th>Entity</th><th>Updater</th><th>Time</th><th>TimeOK</th><th>XML</th></tr>%s', "\n", "\n");
		while ($pendingEntity = $entitiesHandler->fetch(PDO::FETCH_ASSOC)) {
			$entityID = $pendingEntity['entityID'];

			$normalize->fromString($pendingEntity['xml']);
			if ($normalize->getStatus()) {
				if ($normalize->getEntityID() == $entityID) {
					$pendingXML = $normalize->getXML();
					$entityHandler->execute();
					if ($publishedEntity = $entityHandler->fetch(PDO::FETCH_ASSOC)) {
						if ($pendingXML == $publishedEntity['xml'] && $pendingEntity['lastUpdated'] < $publishedEntity['lastUpdated']) {
							$OKRemove = sprintf('<a href=".?action=CleanPending&entity_id=%d">%s</a>',$pendingEntity['id'], $entityID);
						} else {
							$OKRemove = sprintf('%s <a href=".?action=ShowDiff&entity_id1=%d&entity_id2=%d">Diff</a>', $entityID, $pendingEntity['id'], $publishedEntity['id']);
						}
						printf('      <tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>%s', $OKRemove, $pendingEntity['email'], $pendingEntity['lastUpdated'], ($pendingEntity['lastUpdated'] < $publishedEntity['lastUpdated']) ? 'X' : '', ($pendingXML == $publishedEntity['xml']) ? 'X' : '', "\n" );
					} else {
						printf('      <tr><td>%s</td><td>%s</td><td>%s</td><td colspan="2">Not published</td></tr>%s', $entityID, $pendingEntity['email'], $pendingEntity['lastUpdated'], "\n" );
					}
				} else {
					printf('      <tr><td>%s</td><td colspan="4">%s</td></tr>%s',  $entityID, 'Diff in entityID', "\n");
				}
			} else {
				printf('      <tr><td>%s</td><td>%s</td><td>%s</td><td colspan="2">%s</td></tr>%s',  $entityID, 'Problem with XML', "\n");
			}
		}
		print "    </table>\n";
	}

	public function showErrorStatus() {
		$entitieHandler = $this->metaDb->prepare('SELECT `id`, `entityID` FROM Entities WHERE `status` = 1 AND `publishIn` > 1 ORDER BY `entityID`');
		$entitieHandler->execute();

		$organizationHandler = $this->metaDb->prepare("SELECT `data` FROM Organization WHERE `entity_id` = :Id AND `element` = 'OrganizationName' ORDER BY `lang` DESC");
		$organizationHandler->BindParam(':Id', $entity_id);
		$contactPersonHandler = $this->metaDb->prepare("SELECT * FROM ContactPerson WHERE `entity_id` = :Id AND (`contactType` = 'technical' OR `contactType` = 'administrative')");
		$contactPersonHandler->BindParam(':Id', $entity_id);

		$OldECHandler = $this->metaDb->prepare("SELECT `entity_id`, `entityID` FROM Entities, EntityAttributes WHERE Entities.`status` = 1 AND Entities.`id` = EntityAttributes.`entity_id` AND `type` = 'entity-category' AND `attribute` = :EC ORDER BY `entityID`");
		$OldECHandler->bindValue(':EC', 'http://www.swamid.se/category/sfs-1993-1153');
		$OldECHandler->execute();

		$CoCoECHandler = $this->metaDb->prepare("SELECT `type` FROM EntityAttributes WHERE EntityAttributes.`entity_id` = :Id AND `type` = 'entity-category' AND `attribute` = 'http://www.geant.net/uri/dataprotection-code-of-conduct/v1'");
		$CoCoECHandler->BindParam(':Id', $entity_id);
		$NewECHandler = $this->metaDb->prepare("SELECT `attribute` FROM EntityAttributes WHERE EntityAttributes.`entity_id` = :Id AND `type` = 'entity-category' AND `attribute` IN ('http://www.geant.net/uri/dataprotection-code-of-conduct/v1', 'http://refeds.org/category/research-and-scholarship', 'https://refeds.org/category/personalized')");
		$NewECHandler->BindParam(':Id', $entity_id);
		$NINHandler = $this->metaDb->prepare("SELECT `Service_index` FROM AttributeConsumingService_RequestedAttribute WHERE `entity_id` = :Id AND `Name` = 'urn:oid:1.3.6.1.4.1.2428.90.1.5'");
		$NINHandler->BindParam(':Id', $entity_id);
		printf ('    Description of the different error status tabs:%s    <ul>%s      <li>The tab Contacts shows all entities missing required contact information in metadata to get information from SWAMID.</li>%s      <li>The tab Entity category - SFS-1993-1153 shows all entities that has the old entity category SWAMID SFS-1993-1153 but has not start to transfer to the new entity categories.</li>%s      <li>The tab Entity category - R&E shows all entities that has the old entity category REFEDS Research and Scholarship but has not start to transfer to the new entity categories.</li>%s    </ul>%s', "\n", "\n", "\n", "\n", "\n", "\n");
		printf ('    <ul class="nav nav-tabs" id="myTab" role="tablist">%s      <li class="nav-item">%s        <a class="nav-link active" id="contact-tab" data-toggle="tab" href="#contact" role="tab" aria-controls="contact" aria-selected="true">Contacts</a>%s      </li>%s      <li class="nav-item">%s        <a class="nav-link" id="SFS-tab" data-toggle="tab" href="#SFS" role="tab" aria-controls="SFS" aria-selected="false">Entity category - SFS-1993-1153</a>%s      </li>%s      <li class="nav-item">%s        <a class="nav-link" id="RE-tab" data-toggle="tab" href="#RE" role="tab" aria-controls="RE" aria-selected="false">Entity category - R&E</a>%s      </li>%s    </ul>%s', "\n", "\n", "\n", "\n", "\n", "\n", "\n", "\n", "\n", "\n", "\n");
		printf ('    <div class="tab-content" id="myTabContent">%s      <div class="tab-pane fade show active" id="contact" role="tabpanel" aria-labelledby="contact-tab">%s', "\n", "\n");
		printf ('        <h3>Contacts missing</h3>%s        <p>Entites missing both technical and administrative contacts.</p>%s', "\n", "\n");
		printf ('        <table class="table table-striped table-bordered">%s          <tr><th>Entity</th><th>OrganizationName</th><th>Missing contacts</th></tr>%s', "\n", "\n");
		while ($Entity = $entitieHandler->fetch(PDO::FETCH_ASSOC)) {
			$entity_id = $Entity['id'];
			$contactPersonHandler->execute();
			if (! $contactPersonHandler->fetch(PDO::FETCH_ASSOC)) {
				$organizationHandler->execute();
				$OrganizationName = ($organization = $organizationHandler->fetch(PDO::FETCH_ASSOC)) ? $organization['data'] : 'Missing';
				printf('          <tr><td><a href="?showEntity=%d">%s</a></td><td>%s</td><td>X</td></tr>%s', $entity_id, $Entity['entityID'], $OrganizationName, "\n");
			}
		}
		printf ('        </table>%s      </div><!-- End tab-pane contact -->%s      <div class="tab-pane fade" id="SFS" role="tabpanel" aria-labelledby="SFS-tab">%s', "\n", "\n", "\n", "\n");
		printf ('        <h3>SFS-1993-1153</h3>%s        <p>Entites with sfs-1993-1153 but NOT CoCo and norEduPersonNIN</p>%s', "\n", "\n");
		printf ('        <table class="table table-striped table-bordered">%s          <tr><th>Entity</th><th>Missing CoCo</th><th>Missing norEduPersonNIN</th></tr>%s', "\n", "\n");
		while ($Entity = $OldECHandler->fetch(PDO::FETCH_ASSOC)) {
			$entity_id = $Entity['entity_id'];
			$CoCoECHandler->execute();
			$CoCo = $CoCoECHandler->fetch(PDO::FETCH_ASSOC);
			$NINHandler->execute();
			$NIN = $NINHandler->fetch(PDO::FETCH_ASSOC);
			if (! ($CoCo && $NIN)) {
				printf('          <tr><td><a href="?showEntity=%d">%s</a></td><td>%s</td><td>%s</td></tr>%s', $entity_id, $Entity['entityID'], ($CoCo) ? '' : 'X', ($NIN) ? '' : 'X', "\n");
			}
		}
		printf ('        </table>%s      </div><!-- End tab-pane CoCo -->%s      <div class="tab-pane fade" id="RE" role="tabpanel" aria-labelledby="RE-tab">%s', "\n", "\n", "\n");

		$OldECHandler->bindValue(':EC', 'http://www.swamid.se/category/research-and-education');
		$OldECHandler->execute();
		printf ('        <h3>Research and Education</h3>%s        <p>Entites with research-and-education but NOT research-and-scholarship.<br>Entites with research-and-education might also be replaced with CoCo or Personalized. They are listed for verification</p>%s', "\n", "\n");
		printf ('        <table class="table table-striped table-bordered">%s          <tr><th>Entity</th><th>Missing any new Categorys</th><th>Have CoCo</th><th>Have Personalized</th></tr>%s', "\n", "\n");
		while ($Entity = $OldECHandler->fetch(PDO::FETCH_ASSOC)) {
			$entity_id = $Entity['entity_id'];
			$CoCo = false;
			$RandS = false;
			$Personalized = false;
			$NewECHandler->execute();
			while ($EC = $NewECHandler->fetch(PDO::FETCH_ASSOC)) {
				switch ($EC['attribute']) {
					case 'http://www.geant.net/uri/dataprotection-code-of-conduct/v1' :
						$CoCo = true;
						break;
					case 'http://refeds.org/category/research-and-scholarship' :
						$RandS = true;
						break;
					default :
						printf("Saknar : %s<br>", $EC['attribute']);
				}
			}
			if (! ($RandS)) {
				printf('          <tr><td><a href="?showEntity=%d">%s</a></td><td>%s</td><td>%s</td><td>%s</td></tr>%s', $entity_id, $Entity['entityID'], ($CoCo || $RandS || $Personalized) ? '' : 'X', ($CoCo) ? 'X' : '', ($Personalized) ? 'X' : '', "\n");
			}
		}
		printf ('        </table>%s      </div><!-- End tab-pane RE -->%s    </div><!-- End tab-content -->%s', "\n", "\n", "\n");
	}

	public function showErrorStatistics() {
		$statusRows = $this->metaDb->prepare("SELECT `date`, `ErrorsTotal`, `ErrorsSPs`, `ErrorsIdPs`, `NrOfEntites`, `NrOfSPs`, `NrOfIdPs`, `Changed` FROM EntitiesStatus ORDER BY `date` DESC");
		$statusRows->execute();
		$labelsArray = array();
		$totalArray = array();
		$totalOKArray = array();
		$SPArray = array();
		$SPOKArray = array();
		$IdPArray = array();
		$IdPOKArray = array();

		$TotalMin = 1000;
		$SPMin = 1000;
		$IdPMin = 1000;

		$ErrorsTotal = 0;
		$ErrorsSPs = 0;
		$ErrorsIdPs = 0;
		$NrOfEntites = 0;
		$NrOfSPs = 0;
		$NrOfIdPs = 0;
		$entitys = $this->metaDb->prepare("SELECT `id`, `entityID`, `isIdP`, `isSP`, `publishIn`, `errors` FROM Entities WHERE status = 1 AND publishIn > 2");
		$entitys->execute();
		while ($row = $entitys->fetch(PDO::FETCH_ASSOC)) {
			$isIdP = $row['isIdP'];
			$isSP = $row['isSP'];
			switch ($row['publishIn']) {
				case 1 :
					break;
				case 3 :
				case 7 :
					$NrOfEntites ++;
					if ($row['isIdP']) $NrOfIdPs ++;
					if ($row['isSP']) $NrOfSPs ++;
					if ( $row['errors'] <> '' ) {
						$ErrorsTotal ++;
						if ($row['isIdP']) $ErrorsIdPs ++;
						if ($row['isSP']) $ErrorsSPs ++;
					}
					break;
				default :
					printf ("Can't resolve publishIn = %d for enityID = %s", $row['publishIn'], $row['entityID']);
			}
		}

		printf ('    <h3>Totalt entity errors</h3>    <p>Statistics on number of entities not complying to the SWAMID SAML WebSSO Technology Profile.</p>%s    <canvas id="total" width="200" height="50"></canvas>%s    <h3>Totalt SP errors</h3>%s    <p>Statistics on number of Service Providers not complying to the SWAMID SAML WebSSO Technology Profile.</p>%s    <canvas id="SPs" width="200" height="50"></canvas>%s    <h3>Totalt IdP errors</h3>%s    <p>Statistics on number of Identity Providers not complying to the SWAMID SAML WebSSO Technology Profile.</p>%s    <canvas id="IdPs" width="200" height="50"></canvas>%s    <br><br>%s    <h3>Statistics in numbers</h3>%s    <table class="table table-striped table-bordered">%s      <tr><th>Date</th><th>NrOfEntites</th><th>NrOfSPs</th><th>NrOfIdPs</th><th>ErrorsTotal</th><th>ErrorsSPs</th><th>ErrorsIdPs</th></tr>%s', "\n", "\n", "\n", "\n", "\n", "\n", "\n", "\n", "\n", "\n", "\n", "\n", "\n");
		printf('      <tr><td>%s</td><td>%d</td><td>%d</td><td>%d</td><td>%d (%d %%)</td><td>%d (%d %%)</td><td>%d (%d %%)</td></tr>%s', 'Now', $NrOfEntites, $NrOfSPs, $NrOfIdPs, $ErrorsTotal, ($ErrorsTotal / $NrOfEntites * 100), $ErrorsSPs, ($ErrorsSPs / $NrOfSPs * 100), $ErrorsIdPs, ($ErrorsIdPs / $NrOfIdPs * 100), "\n");
		array_unshift($labelsArray, 'Now');
		array_unshift($totalArray, $ErrorsTotal);
		array_unshift($totalOKArray, $NrOfEntites - $ErrorsTotal);
		array_unshift($SPArray, $ErrorsSPs);
		array_unshift($SPOKArray, $NrOfSPs - $ErrorsSPs);
		array_unshift($IdPArray, $ErrorsIdPs);
		array_unshift($IdPOKArray, $NrOfIdPs - $ErrorsIdPs);

		while ($row = $statusRows->fetch(PDO::FETCH_ASSOC)) {
			$week = date('W',mktime(0, 0, 0, substr($row['date'],5,2), substr($row['date'],8,2), substr($row['date'],0,4)));
			$dateLabel = substr($row['date'],2,8);
			printf('      <tr><td>%s</td><td>%d</td><td>%d</td><td>%d</td><td>%d (%d %%)</td><td>%d (%d %%)</td><td>%d (%d %%)</td></tr>%s', $dateLabel, $row['NrOfEntites'], $row['NrOfSPs'], $row['NrOfIdPs'], $row['ErrorsTotal'], ($row['ErrorsTotal'] / $row['NrOfEntites'] * 100), $row['ErrorsSPs'],($row['ErrorsSPs'] / $row['NrOfSPs'] * 100), $row['ErrorsIdPs'], ($row['ErrorsIdPs'] / $row['NrOfIdPs'] * 100), "\n");
			array_unshift($labelsArray, $dateLabel);
			array_unshift($totalArray, $row['ErrorsTotal']);
			array_unshift($totalOKArray, $row['NrOfEntites'] - $row['ErrorsTotal']);
			array_unshift($SPArray, $row['ErrorsSPs']);
			array_unshift($SPOKArray, $row['NrOfSPs'] - $row['ErrorsSPs']);
			array_unshift($IdPArray, $row['ErrorsIdPs']);
			array_unshift($IdPOKArray, $row['NrOfIdPs'] - $row['ErrorsIdPs']);

			$TotalMin = ($row['ErrorsTotal'] < $TotalMin) ? $row['ErrorsTotal'] : $TotalMin;
			$SPMin = ($row['ErrorsSPs'] < $SPMin) ? $row['ErrorsSPs'] : $SPMin;
			$IdPMin = ($row['ErrorsIdPs'] < $IdPMin) ? $row['ErrorsIdPs'] : $IdPMin;
		}
		$labels = implode("','", $labelsArray);
		$total = implode(',', $totalArray);
		$totalOK = implode(',', $totalOKArray);
		$SP = implode(',', $SPArray);
		$SPOK = implode(',', $SPOKArray);
		$IdP = implode(',', $IdPArray);
		$IdPOK = implode(',', $IdPOKArray);

		$TotalMin = ($TotalMin < 100) ? 0 : $TotalMin - 100;
		$SPMin = ($SPMin < 100) ? 0 : $SPMin - 100;
		$IdPMin = ($IdPMin < 100) ? 0 : $IdPMin - 100;
		print "    </table>\n"; ?>
    <script src="/include/chart/chart.min.js"></script>
    <script>
      const ctxTotal = document.getElementById('total').getContext('2d');
      const ctxSP = document.getElementById('SPs').getContext('2d');
      const ctxIdP = document.getElementById('IdPs').getContext('2d');

      const myTotal = new Chart(ctxTotal, {
        type: 'line',
        data: {
          labels: ['<?=$labels?>'],
          datasets: [{
            label: 'Errors',
            backgroundColor: "rgb(220,0,0)",
            data: [<?=$total?>],
            fill: 'origin'
          }, {
            label: 'OK',
            backgroundColor: "rgb(0,220,0)",
            data: [<?=$totalOK?>],
            fill: 0
          }]
        },
        options: {
          responsive: true,
          scales: {
            yAxes: {
              stacked: true,
              suggestedMin: <?= $TotalMin?>
            }
          }
        }
      });
      const mySP = new Chart(ctxSP, {
        type: 'line',
        data: {
          labels: ['<?=$labels?>'],
          datasets: [{
            label: 'Errors',
            backgroundColor: "rgb(220,0,0)",
            data: [<?=$SP?>],
            fill: 'origin'
          }, {
            label: 'OK',
            backgroundColor: "rgb(0,220,0)",
            data: [<?=$SPOK?>],
            fill: 0
          }]
        },
        options: {
          responsive: true,
          scales: {
            yAxes: {
              stacked: true,
              suggestedMin: <?= $SPMin?>
            }
          }
        }
      });
      const myIdP = new Chart(ctxIdP, {
        type: 'line',
        data: {
          labels: ['<?=$labels?>'],
          datasets: [{
            label: 'Errors',
            backgroundColor: "rgb(220,0,0)",
            data: [<?=$IdP?>],
            fill: 'origin'
          }, {
            label: 'OK',
            backgroundColor: "rgb(0,220,0)",
            data: [<?=$IdPOK?>],
			fill: 0
          }]
        },
        options: {
          responsive: true,
          scales: {
            yAxes: {
              stacked: true,
              suggestedMin: <?= $IdPMin?>
            }
          }
        }
      });
    </script><?php print "\n";
	}

	public function showHelp() {
		print "    <p>The SWAMID Metadata Tool is the place where you can see, register, update and remove metadata for Identity Providers and Service Providers in the Academic Identity Federation SWAMID.</p>\n";
		$this->showCollapse('Register a new entity in SWAMID', 'RegisterNewEntity', false, 0, false);?>

          <ol>
            <li>Go to the tab "Upload new XML".</li>
            <li>Upload the metadata file by clicking "Browse" and selecting the file on your local file system. Press "Submit".</li>
            <li>If the new entity is a new version of a existing entity select the EntityId from the "Merge from other entity:" dropdown and click "Merge".</li>
            <li>Add or update metadata information by clicking on the pencil for each metadata section. Continue adding and changing information in the metadata until the information is correct and there are no errors left.<ul>
              <li>For a Service Provider remember to add metadata attributes for entity categories, otherwise you will not get any attributes from Identity Providers without manual configuration in the Identity Providers. For more information on entity categories see the wiki page "Entity Categories for Service Providers".</li>
              <li>It is highly recommended that the service adheres to the security profile <a href="https://refeds.org/sirtfi" target="_blank">Sirtfi</a>.</li>
              <li>You have up to two weeks to work on your draft. Every change is automatically saved. To find out how to pick up where you left off, see the help topic "Continue working on a draft".</li>
            </ul></li>
            <li>When you are finished and there are no more errors press the button ???Request publication???.</li>
            <li>Follow the instructions on the next web page and choose if the entity shall be published in SWAMID and eduGAIN, SWAMID Only or SWAMID test federation.</li>
            <li>Continue to the next step by pressing on the button ???Request publication???.</li>
            <li>An e-mail will be sent to your registered address. Forward this to SWAMID operations as described in the e-mail.</li>
            <li>SWAMID Operations will now check and publish the request.</li>
          </ol><?php
		$this->showCollapseEnd('RegisterNewEntity', 0);
		$this->showCollapse('Update SAML certificates for a published entity in SWAMID', 'UpdateCertificates', false, 0, false);?>

          <ol>
            <li>Go to the tab "Upload new XML".</li>
            <li>Upload the metadata file containing the new certificates by clicking "Browse" and selecting the file on your local file system. Press "Submit".</li>
            <li>Merge the published metadata with the uploaded metadata by pressing the button "Merge from published". By doing this you get all current metadata for the entity merged into the draft.</li>
            <li>Add or update metadata information by clicking on the pencil for each metadata section. Continue adding and changing information in the metadata until the information is correct and there are no errors left.<ul>
              <li>For a Service Provider remember to add metadata attributes for entity categories, otherwise you will not get any attributes from Identity Providers without manual configuration in the Identity Providers. For more information on entity categories see the wiki page "Entity Categories for Service Providers".</li>
              <li>It is highly recommended that the service adheres to the security profile <a href="https://refeds.org/sirtfi" target="_blank">Sirtfi</a>.</li>
              <li>You have up to two weeks to work on your draft. Every change is automatically saved. To find out how to pick up where you left off, see the help topic "Continue working on a draft".</li>
            </ul></li>
            <li>When you are finished and there are no more errors press the button ???Request publication???.</li>
            <li>Follow the instructions on the next web page and choose if the entity shall be published in SWAMID and eduGAIN, SWAMID Only or SWAMID test federation.</li>
            <li>Continue to the next step by pressing on the button ???Request publication???.</li>
            <li>An e-mail will be sent to your registered address. Forward this to SWAMID operations as described in the e-mail.</li>
            <li>SWAMID Operations will now check and publish the request.</li>
          </ol><?php
		$this->showCollapseEnd('UpdateCertificates', 0);
		$this->showCollapse('Update published entity in SWAMID (except SAML certificates)', 'UpdateEntity', false, 0, false);?>

          <ol>
            <li>Go to the tab "Published".</li>
            <li>Choose the entity you want to  update by clicking on its entityID.</li>
            <li>Click on the button "Create draft" to start updating the entity.</li>
            <li>Add or update metadata information by clicking on the pencil for each metadata section. Continue adding and changing information in the metadata until the information is correct and there are no errors left.<ul>
              <li>For a Service Provider remember to add metadata attributes for entity categories, otherwise you will not get any attributes from Identity Providers without manual configuration in the Identity Providers. For more information on entity categories see the wiki page "Entity Categories for Service Providers".</li>
              <li>It is highly recommended that the service adheres to the security profile <a href="https://refeds.org/sirtfi" target="_blank">Sirtfi</a>.</li>
              <li>You have up to two weeks to work on your draft. Every change is automatically saved. To find out how to pick up where you left off, see the help topic "Continue working on a draft".</li>
            </ul></li>
            <li>When you are finished and there are no more errors press the button ???Request publication???.</li>
            <li>Follow the instructions on the next web page and choose if the entity shall be published in SWAMID and eduGAIN, SWAMID Only or SWAMID test federation.</li>
            <li>Continue to the next step by pressing on the button ???Request publication???.</li>
            <li>An e-mail will be sent to your registered address. Forward this to SWAMID operations as described in the e-mail.</li>
            <li>SWAMID Operations will now check and publish the request.</li>
          </ol><?php
		$this->showCollapseEnd('UpdateEntity', 0);
		$this->showCollapse('Continue working on a draft', 'ContinueUpdateEntity', false, 0, false);?>

          <ol>
            <li>Go to the tab "Drafts".</li>
            <li>Select the entity you want to continue to update by clicking on its entityID. You can only remove drafts for entities that you personally have previously started to update.</li>
            <li>Add or update metadata information by clicking on the pencil for each metadata section. Continue adding and changing information in the metadata until the information is correct and there are no errors left.<ul>
              <li>For a Service Provider remember to add metadata attributes for entity categories, otherwise you will not get any attributes from Identity Providers without manual configuration in the Identity Providers. For more information on entity categories see the wiki page "Entity Categories for Service Providers".</li>
              <li>It is highly recommended that the service adheres to the security profile <a href="https://refeds.org/sirtfi" target="_blank">Sirtfi</a>.</li>
            </ul></li>
            <li>When you are finished and there are no more errors press the button ???Request publication???.</li>
            <li>Follow the instructions on the next web page and choose if the entity shall be published in SWAMID and eduGAIN, SWAMID Only or SWAMID test federation.</li>
            <li>Continue to the next step by pressing on the button ???Request publication???.</li>
            <li>An e-mail will be sent to your registered address. Forward this to SWAMID operations as described in the e-mail.</li>
            <li>SWAMID Operations will now check and publish the request.</li>
          </ol><?php
		$this->showCollapseEnd('ContinueUpdateEntity', 0);
		$this->showCollapse('Stop and remove a draft update', 'DiscardDraft', false, 0, false);?>

          <ol>
            <li>Go to the tab "Drafts".</li>
            <li>Select the entity for which you want to remove the draft by clicking on its entityID. You can only remove drafts for entities that you personally have previously started to update.</li>
            <li>Press the button ???Discard Draft???.</li>
            <li>Confirm the action by pressing the button ???Remove???.</li>
          </ol><?php
		$this->showCollapseEnd('DiscardDraft', 0);
		$this->showCollapse('Withdraw a publication request', 'WithdrawPublicationRequest', false, 0, false);?>

          <ol>
            <li>Go to the tab "Pending".</li>
            <li>Choose the entity for which you want to withdraw the publication request. You can only withdraw a publication request for entites where you earlier have requested publication.</li>
            <li>To withdraw the request press the button ???Cancel publication request???.</li>
            <li>To ensure that you are sure of the withdrawel you need to press the button ???Cancel request??? before the request is processed.</li>
            <li>The entity is now back in draft mode so that you can continue to update, if you want to to cancel the update press the buton "Discard Draft" and "Remove" on next page.</li>
          </ol><?php
		$this->showCollapseEnd('WithdrawPublicationRequest', 0);
	}
	#############
	# Return collapseIcons
	#############
	function getCollapseIcons() {
		return $this->collapseIcons;
	}
}
# vim:set ts=2:
