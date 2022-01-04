<?php
Class MetadataDisplay {
	# Setup
	function __construct($configFile) {
		include $configFile;
		$this->standardAttributes = $standardAttributes;
		$this->FriendlyNames = $FriendlyNames;

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
		$entityHandler = $this->metaDb->prepare('SELECT `entityID`, `status`, `validationOutput`, `warnings`, `errors` FROM Entities WHERE `id` = :Id;');
		$entityHandler->bindParam(':Id', $Entity_id);
		$urlHandler1 = $this->metaDb->prepare('SELECT `status`, `URL`, `lastValidated`, `validationOutput` FROM URLs WHERE URL IN (SELECT `data` FROM Mdui WHERE `entity_id` = :Id)');
		$urlHandler2 = $this->metaDb->prepare("SELECT `status`, `URL`, `lastValidated`, `validationOutput` FROM URLs WHERE URL IN (SELECT `URL` FROM EntityURLs WHERE `entity_id` = :Id UNION SELECT `data` FROM Organization WHERE `element` = 'OrganizationURL' AND `entity_id` = :Id)");
		$urlHandler3 = $this->metaDb->prepare("SELECT `status`, `URL`, `lastValidated`, `validationOutput` FROM URLs WHERE URL IN (SELECT `data` FROM Organization WHERE `element` = 'OrganizationURL' AND `entity_id` = :Id)");
		#$urlHandler = $this->metaDb->prepare("SELECT `status`, `URL`, `lastValidated`, `validationOutput` FROM URLs WHERE URL IN (SELECT `data` FROM Mdui WHERE `entity_id` = :Id UNION SELECT `URL` FROM EntityURLs WHERE `entity_id` = :Id UNION SELECT `data` FROM Organization WHERE `element` = 'OrganizationURL' AND `entity_id` = :Id)");
		#$urlHandler->bindParam(':Id', $Entity_id);
		$urlHandler1->bindParam(':Id', $Entity_id);
		$urlHandler2->bindParam(':Id', $Entity_id);
		$urlHandler3->bindParam(':Id', $Entity_id);

		$entityHandler->execute();
		if ($entity = $entityHandler->fetch(PDO::FETCH_ASSOC)) {
			$errors = '';
			$urlHandler1->execute();
			while ($url = $urlHandler1->fetch(PDO::FETCH_ASSOC)) {
				if ($url['status'] > 0)
					$errors .= sprintf("%s - %s\n", $url['validationOutput'], $url['URL']);
			}
			$urlHandler2->execute();
			while ($url = $urlHandler2->fetch(PDO::FETCH_ASSOC)) {
				if ($url['status'] > 0)
					$errors .= sprintf("%s - %s\n", $url['validationOutput'], $url['URL']);
			}
			$urlHandler3->execute();
			while ($url = $urlHandler3->fetch(PDO::FETCH_ASSOC)) {
				if ($url['status'] > 0)
					$errors .= sprintf("%s - %s\n", $url['validationOutput'], $url['URL']);
			}
			$errors .= $entity['errors'];
			if ($errors != '') {
				printf('%s    <div class="row alert alert-danger" role="alert">%s      <div class="col">%s        <div class="row"><b>Errors:</b></div>%s        <div class="row">%s</div>%s      </div>%s    </div>', "\n", "\n", "\n", "\n", str_ireplace("\n", "<br>", $errors), "\n", "\n");
			}
			if ($entity['warnings'] != '')
				printf('%s    <div class="row alert alert-warning" role="alert">%s      <div class="col">%s        <div class="row"><b>Warnings:</b></div>%s        <div class="row">%s</div>%s      </div>%s    </div>', "\n", "\n", "\n", "\n", str_ireplace("\n", "<br>", $entity['warnings']), "\n", "\n");
			if ($entity['validationOutput'] != '')
				printf('%s    <div class="row alert alert-primary" role="alert">%s</div>', "\n", str_ireplace("\n", "<br>", $entity['validationOutput']));
		}
		if ($admin)
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
						$error = '';
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
							$error = '';
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
            <div class="col">';
		$this->showErrorURL($Entity_id, $oldEntity_id, true, $allowEdit);
		$this->showScopes($Entity_id, $oldEntity_id, true, $allowEdit);
		if ($oldEntity_id != 0 ) {
			$this->showNewCol(1);
			$this->showErrorURL($oldEntity_id, $Entity_id);
			$this->showScopes($oldEntity_id, $Entity_id);
		}
		print '
            </div>
          </div>';
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
				switch ($lang) {
					case '' :
						$info = ' (NOT RECOMMENDED)';
						break;
					case 'en' :
						$info = ' (REQUIRED)';
						break;
					case 'sv' :
						$info = ' (RECOMMENDED)';
						break;
					default :
						$info = '';
				}
				printf('%s                <b>Lang = "%s"%s</b>%s                <ul>', $showEndUL ? "\n                </ul>\n" : "\n", $lang, $info, "\n");
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
		$keyInfoHandler = $this->metaDb->prepare('SELECT `use`, `name`, `notValidAfter`, `subject`, `issuer`, `bits`, `key_type`, `hash` FROM KeyInfo WHERE `entity_id` = :Id AND `type` = :Type ORDER BY notValidAfter DESC;');
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

		$encryptionFound = false;
		$signingFound = false;
		while ($keyInfo = $keyInfoHandler->fetch(PDO::FETCH_ASSOC)) {
			$okRemove = false;
			switch ($keyInfo['use']) {
				case 'encryption' :
					$use = 'encryption';
					if ($encryptionFound)
						$okRemove = true;
					else
						$encryptionFound = true;
					break;
				case 'signing' :
					$use = 'signing';
					if ($signingFound)
						$okRemove = true;
					else
						$signingFound = true;
					break;
				case 'both' :
					$use = 'encryption & signing';
					if ($encryptionFound && $signingFound)
						$okRemove = true;
					else {
						$encryptionFound = true;
						$signingFound = true;
					}
					break;
			}
			$name = $keyInfo['name'] == '' ? '' : '(' . $keyInfo['name'] .')';

			if ($otherEntity_id) {
				$state = ($added) ? 'success' : 'danger';
				if (isset($otherKeyInfos[$keyInfo['hash']][$keyInfo['use']])) {
					$state = 'dark';
				}
			} else
				$state = 'dark';
				$extraButton = $okRemove && $removable ? sprintf(' <a href="?removeKey=%d&type=%s&use=%s&hash=%s"><i class="fas fa-trash"></i></a>', $Entity_id, $type, $keyInfo['use'], $keyInfo['hash']) : '';
			printf('%s                <span class="text-%s text-truncate"><b>KeyUse = "%s"</b> %s%s</span>
                <ul>
                  <li>notValidAfter = %s</li>
                  <li>Subject = %s</li>
                  <li>Issuer = %s</li>
                  <li>Type / bits = %s / %d</li>
                  <li>Hash = %s</li>
                </ul>', "\n", $state, $use, $name, $extraButton, $keyInfo['notValidAfter'], $keyInfo['subject'], $keyInfo['issuer'], $keyInfo['key_type'], $keyInfo['bits'], $keyInfo['hash']);
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

	function showRawXML($Entity_id) {
		$entityHandler = $this->metaDb->prepare('SELECT `xml` FROM Entities WHERE `id` = :Id;');
		$entityHandler->bindParam(':Id', $Entity_id);
		$entityHandler->execute();
		$entity = $entityHandler->fetch(PDO::FETCH_ASSOC);
		header('Content-Type: application/xml; charset=utf-8');
		if (isset($_GET['download']))
			header('Content-Disposition: attachment; filename=metadata.xml');
		print $entity['xml'];
		exit;
	}

	public function showURLStatus(){
		if(isset($_GET['URL'])) {
			$missing = true;
			$EntityHandler = $this->metaDb->prepare('SELECT `entity_id`, `entityID` FROM EntityURLs, Entities WHERE entity_id = id AND `URL` = :URL');
			$EntityHandler->bindValue(':URL', $_GET['URL']);
			$EntityHandler->execute();
			$SSOUIIHandler = $this->metaDb->prepare('SELECT `entity_id`, `type`, `element`, `lang`, `entityID` FROM Mdui, Entities WHERE entity_id = id AND `data` = :URL');
			$SSOUIIHandler->bindValue(':URL', $_GET['URL']);
			$SSOUIIHandler->execute();
			$OrganizationHandler = $this->metaDb->prepare('SELECT `entity_id`, `element`, `lang`, `entityID` FROM Organization, Entities WHERE entity_id = id AND `data` = :URL');
			$OrganizationHandler->bindValue(':URL', $_GET['URL']);
			$OrganizationHandler->execute();
			printf ('    <h3>URL : %s</h3>%s    <table class="table table-striped table-bordered">%s      <tr><th>Entity</th><th>Part</th><th></tr>%s', $_GET['URL'], "\n", "\n", "\n");
			while ($Entity = $EntityHandler->fetch(PDO::FETCH_ASSOC)) {
				printf ('      <tr><td><a href="?showEntity=%d">%s</td><td>%s</td><tr>%s', $Entity['entity_id'], $Entity['entityID'], 'ErrorURL', "\n");
				$missing = false;
			}
			while ($Entity = $SSOUIIHandler->fetch(PDO::FETCH_ASSOC)) {
				printf ('      <tr><td><a href="?showEntity=%d">%s</td><td>%s:%s[%s]</td><tr>%s', $Entity['entity_id'], $Entity['entityID'], substr($Entity['type'],0,-3), $Entity['element'], $Entity['lang'], "\n");
				$missing = false;
			}
			while ($Entity = $OrganizationHandler->fetch(PDO::FETCH_ASSOC)) {
				printf ('      <tr><td><a href="?showEntity=%d">%s</td><td>%s[%s]</td><tr>%s', $Entity['entity_id'], $Entity['entityID'], $Entity['element'], $Entity['lang'], "\n");
				$missing = false;
			}
			print "    </table>\n";
			if ($missing) {
				$URLHandler = $this->metaDb->prepare('DELETE FROM URLs WHERE `URL` = :URL');
				$URLHandler->bindValue(':URL', $_GET['URL']);
				$URLHandler->execute();
				print "Not used anymore, removed";
			}
		} else {
			$URLWaitHandler = $this->metaDb->prepare("SELECT `URL`, `validationOutput`, `lastValidated`, `lastSeen` FROM URLs WHERE `lastValidated` < ADDTIME(NOW(), '-7 0:0:0') OR (`status` > 0 AND `lastValidated` < ADDTIME(NOW(), '-6:0:0')) ORDER BY `lastValidated`;");
			$URLWaitHandler->execute();
			printf ('    <h3>Waiting for validation</h3>%s    <table class="table table-striped table-bordered">%s      <tr><th>URL</th><th>Last seen</th><th>Last validated</th><th>Result</th></tr>%s', "\n", "\n", "\n");
			while ($URL = $URLWaitHandler->fetch(PDO::FETCH_ASSOC)) {
				printf ('      <tr><td><a href="?action=URLlist&URL=%s">%s</td><td>%s</td><td>%s</td><td>%s</td><tr>%s', urlencode($URL['URL']), $URL['URL'], $URL['lastSeen'], $URL['lastValidated'], $URL['validationOutput'], "\n");
			}
			print "    </table>\n";

			$oldType = 0;
			$URLHandler = $this->metaDb->prepare("SELECT `URL`, `type`, `status`, `lastValidated`, `lastSeen`, `validationOutput` FROM URLs WHERE Status > 0 ORDER BY type DESC, lastValidated DESC;");
			$URLHandler->execute();

			while ($URL = $URLHandler->fetch(PDO::FETCH_ASSOC)) {
				if ($oldType != $URL['type']) {
					switch ($URL['type']) {
						case 1:
							$typeInfo = 'URL check';
							break;
						case 2:
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
		}
	}

	public function showErrorList() {
		$emails = array();
		$EntityHandler = $this->metaDb->prepare("SELECT `id`, `entityID`, `errors` FROM Entities WHERE errors <> '' AND status = 1");
		$EntityHandler->execute();
		$contactPersonHandler = $this->metaDb->prepare('SELECT contactType, emailAddress FROM ContactPerson WHERE `entity_id` = :Id;');
		printf ('    <table class="table table-striped table-bordered">%s      <tr><th>Entity</th><th>Contact address</th><th>Error</th></tr>%s', "\n", "\n");
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
			printf ('      <tr><td><a href="?showEntity=%d"><span class="text-truncate">%s</span></td><td>%s</td><td>%s</td><tr>%s', $Entity['id'], $Entity['entityID'], $email, str_ireplace("\n", "<br>",$Entity['errors']), "\n");
			$missing = false;
		}
		print "    </table>\n";
	}

	#############
	# Return collapseIcons
	#############
	function getCollapseIcons() {
		return $this->collapseIcons;
	}
}
# vim:set ts=2:
