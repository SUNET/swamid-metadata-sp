<?php
Class MetadataEdit {
	# Setup
	function __construct($baseDir, $newID, $oldID = 0) {
		include $baseDir . '/config.php';
		include $baseDir . '/include/common.php';
		try {
			$this->metaDb = new PDO("mysql:host=$dbServername;dbname=$dbName", $dbUsername, $dbPassword);
			// set the PDO error mode to exception
			$this->metaDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} catch(PDOException $e) {
			echo "Error: " . $e->getMessage();
		}
		$this->dbIdNr = $newID;
		$this->dbOldIdNr = $oldID;
		$this->oldExists = false;

		$this->orderAttributeRequestingService = array ('md:ServiceName' => 1,
			'md:ServiceDescription' => 2,
			'md:RequestedAttribute' => 3);

		$this->orderOrganization = array ('md:Extensions'=> 1,
			'md:OrganizationName' => 2,
			'md:OrganizationDisplayName' => 3,
			'md:OrganizationURL' => 4);

		$this->orderContactPerson = array ('md:Company' => 1,
			'md:GivenName' => 2,
			'md:SurName' => 3,
			'md:EmailAddress' => 4,
			'md:TelephoneNumber' => 5,
			'md:Extensions' => 6);
		$entityHandler = $this->metaDb->prepare('SELECT entityID, isIdP, isSP, status, xml FROM Entities WHERE id = :Id;');
		$entityHandler->bindValue(':Id', $newID);
		$entityHandler->execute();
		if ($entity = $entityHandler->fetch(PDO::FETCH_ASSOC)) {
			$this->entityExists = true;
			$this->newXml = new DOMDocument;
			$this->newXml->preserveWhiteSpace = false;
			$this->newXml->formatOutput = true;
			$this->newXml->loadXML($entity['xml']);
			$this->newXml->encoding = 'UTF-8';
			$this->entityID = $entity['entityID'];
			$this->isIdP = $entity['isIdP'];
			$this->isSP = $entity['isSP'];;
		} else {
			$this->entityExists = false;
			$this->entityID = 'Unknown';
			$this->isIdP = false;
			$this->isSP = false;
		}
		if ($this->entityExists && $oldID > 0) {
			$entityHandler->bindValue(':Id', $oldID);
			$entityHandler->execute();
			if ($entity = $entityHandler->fetch(PDO::FETCH_ASSOC)) {
				$this->oldXml = DOMDocument::loadXML($entity['xml']);
				$this->oldentityID = $entity['entityID'];
				$this->oldExists = true;
			}
		}
	}

	public function edit ($part) {
		printf('    <div class="row">
      <div class="col">
        <h3>entityID = %s</h3>
      </div>
    </div>
    <div class="row">
      <div class="col">
        <h3>New metadata</h3>
      </div>', $this->entityID);
		if ($this->oldExists && $part <> "AddIdPKeyInfo" && $part <> "AddSPKeyInfo") {
			printf ('%s      <div class="col">%s        <h3>Old metadata</h3>%s      </div>', "\n", "\n", "\n");
	  	}
		printf('%s    </div>', "\n");
		switch ($part) {
			case 'EntityAttributes' :
				$this->editEntityAttributes();
				break;
			case 'IdPErrorURL' :
				$this->editIdPErrorURL();
				break;
			case 'IdPScopes' :
				$this->editIdPScopes();
				break;
			case 'IdPMDUI' :
				$this->editMDUI('IDPSSO');
				break;
			case 'SPMDUI' :
				$this->editMDUI('SPSSO');
				break;
			case 'IdPKeyInfo' :
				$this->editKeyInfo('IDPSSO');
				break;
			case 'SPKeyInfo' :
				$this->editKeyInfo('SPSSO');
				break;
			case 'AddIdPKeyInfo' :
				$this->addKeyInfo('IDPSSO');
				break;
			case 'AddSPKeyInfo' :
				$this->addKeyInfo('SPSSO');
				break;
			case 'AttributeConsumingService' :
				$this->editAttributeConsumingService();
				break;
			case 'DiscoHints' :
				$this->editDiscoHints();
				break;
			case 'Organization' :
				$this->editOrganization();
				break;
			case 'ContactPersons' :
				$this->editContactPersons();
				break;
			default :
				print "Missing $part";
		}
	}

	private function editEntityAttributes() {
		$entityAttributesHandler = $this->metaDb->prepare('SELECT type, attribute FROM EntityAttributes WHERE entity_id = :Id ORDER BY type, attribute;');

		if (isset($_GET['action']) && isset($_GET['attribute']) && trim($_GET['attribute']) != '' ) {
			switch ($_GET['type']) {
				case 'assurance-certification' :
					$attributeType = 'urn:oasis:names:tc:SAML:attribute:assurance-certification';
					break;
				case 'entity-category' :
					$attributeType = 'http://macedir.org/entity-category';
					break;
				case 'entity-category-support' :
					$attributeType = 'http://macedir.org/entity-category-support';
					break;
				case 'subject-id:req' :
					$attributeType = 'urn:oasis:names:tc:SAML:profiles:subject-id:req';
					break;
				case 'swamid/assurance-requirement' :
					$attributeType ='http://www.swamid.se/assurance-requirement';
					break;
				default :
					printf ('Missing type (%s)', $_GET['type']);
					exit;
			}
			$EntityDescriptor = $this->getEntityDescriptor($this->newXml);

			# Find md:Extensions in XML
			$child = $EntityDescriptor->firstChild;
			$Extensions = false;
			while ($child && ! $Extensions) {
				switch ($child->nodeName) {
					case 'md:Extensions' :
						$Extensions = $child;
						break;
					case 'md:RoleDescriptor' :
					case 'md:SPSSODescriptor' :
					case 'md:IDPSSODescriptor' :
					case 'md:AuthnAuthorityDescriptor' :
					case 'md:AttributeAuthorityDescriptor' :
					case 'md:PDPDescriptor' :
					case 'md:AffiliationDescriptor' :
					case 'md:Organization' :
					case 'md:ContactPerson' :
					case 'md:AdditionalMetadataLocation' :
						$Extensions = $this->newXml->createElement('md:Extensions');
						$EntityDescriptor->insertBefore($Extensions, $child);
						break;
				}
				$child = $child->nextSibling;
			}

			switch ($_GET['action']) {
				case 'Add' :
					if (! $Extensions) {
						# Add if missing
						$Extensions = $this->newXml->createElement('md:Extensions');
						$EntityDescriptor->appendChild($Extensions);
					}

					# Find mdattr:EntityAttributes in XML
					$child = $Extensions->firstChild;
					$EntityAttributes = false;
					while ($child && ! $EntityAttributes) {
						if ($child->nodeName == 'mdattr:EntityAttributes') {
							$EntityAttributes = $child;
						} else
							$child = $child->nextSibling;
					}
					if (! $EntityAttributes) {
						# Add if missing
						$EntityDescriptor->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:mdattr', 'urn:oasis:names:tc:SAML:metadata:attribute');
						$EntityAttributes = $this->newXml->createElement('mdattr:EntityAttributes');
						$Extensions->appendChild($EntityAttributes);
					}

					# Find samla:Attribute in XML
					$child = $EntityAttributes->firstChild;
					$Attribute = false;
					while ($child && ! $Attribute) {
						if ($child->getAttribute('Name') == $attributeType) {
							$Attribute = $child;
						} else
							$child = $child->nextSibling;
					}
					if (! $Attribute) {
						# Add if missing
						$EntityDescriptor->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:samla', 'urn:oasis:names:tc:SAML:2.0:assertion');
						$Attribute = $this->newXml->createElement('samla:Attribute');
						$Attribute->setAttribute('Name', $attributeType);
						$Attribute->setAttribute('NameFormat', 'urn:oasis:names:tc:SAML:2.0:attrname-format:uri');
						$EntityAttributes->appendChild($Attribute);
					}

					# Find samla:AttributeValue in XML
					$child = $Attribute->firstChild;
					$AttributeValue = false;
					while ($child && ! $AttributeValue) {
						if ($child->nodeValue == trim($_GET['attribute'])) {
							$AttributeValue = $child;
						} else
							$child = $child->nextSibling;
					}
					if (! $AttributeValue) {
						# Add if missing
						$AttributeValue = $this->newXml->createElement('samla:AttributeValue');
						$AttributeValue->nodeValue = trim($_GET['attribute']);
						$Attribute->appendChild($AttributeValue);

						$entityAttributesAddHandler = $this->metaDb->prepare('INSERT INTO EntityAttributes (entity_id, type, attribute) VALUES (:Id, :Type, :Attribute) ;');
						$entityAttributesAddHandler->bindParam(':Id', $this->dbIdNr);
						$entityAttributesAddHandler->bindParam(':Type', $_GET['type']);
						$entityAttributesAddHandler->bindValue(':Attribute', trim($_GET['attribute']));
						$entityAttributesAddHandler->execute();
						$this->saveXML();
					}
					break;
				case 'Delete' :
					if ($Extensions) {
						# Find mdattr:EntityAttributes in XML
						$child = $Extensions->firstChild;
						$EntityAttributes = false;
						while ($child && ! $EntityAttributes) {
							if ($child->nodeName == 'mdattr:EntityAttributes') {
								$EntityAttributes = $child;
							}
							$child = $child->nextSibling;
						}
						if ($EntityAttributes) {
							# Find samla:Attribute in XML
							$child = $EntityAttributes->firstChild;
							$Attribute = false;
							$moreAttributes = false;
							while ($child && ! $Attribute) {
								if ($child->getAttribute('Name') == $attributeType) {
									$Attribute = $child;
								}
								$child = $child->nextSibling;
								$moreAttributes = ($moreAttributes) ? true : $child;
							}
							if ($Attribute) {
								# Find samla:Attribute in XML
								$child = $Attribute->firstChild;
								$AttributeValue = false;
								$moreAttributeValues = false;
								while ($child && ! $AttributeValue) {
									if ($child->nodeValue == $_GET['attribute']) {
										$AttributeValue = $child;
									}
									$child = $child->nextSibling;
									$moreAttributeValues = ($moreAttributeValues) ? true : $child;
								}
								if ($AttributeValue) {
									$Attribute->removeChild($AttributeValue);
									if (! $moreAttributeValues) {
										$EntityAttributes->removeChild($Attribute);
										if (! $moreAttributes) $Extensions->removeChild($EntityAttributes);
									}
									$entityAttributesRemoveHandler = $this->metaDb->prepare('DELETE FROM EntityAttributes WHERE entity_id=:Id AND type=:Type AND attribute=:Attribute;');
									$entityAttributesRemoveHandler->bindParam(':Id', $this->dbIdNr);
									$entityAttributesRemoveHandler->bindParam(':Type', $_GET['type']);
									$entityAttributesRemoveHandler->bindParam(':Attribute', $_GET['attribute']);
									$entityAttributesRemoveHandler->execute();
									$this->saveXML();
								}
							}
						}
					}
					break;
			}
		}
		print "\n";
		print '    <div class="row">' . "\n" . '      <div class="col">' . "\n";

		$oldAttributeValues = array();
		$entityAttributesHandler->bindParam(':Id', $this->dbOldIdNr);
		$entityAttributesHandler->execute();
		while ($attribute = $entityAttributesHandler->fetch(PDO::FETCH_ASSOC)) {
			$type = $attribute['type'];
			$value = $attribute['attribute'];
			if (! isset($oldAttributeValues[$type]))
				$oldAttributeValues[$type] = array();
			$oldAttributeValues[$type][$value] = true;
		}

		$existingAttributeValues = array();
		$entityAttributesHandler->bindParam(':Id', $this->dbIdNr);
		$entityAttributesHandler->execute();
		if ($attribute = $entityAttributesHandler->fetch(PDO::FETCH_ASSOC)) {
			$type = $attribute['type'];
			$value = $attribute['attribute'];
			$existingAttributeValues[$type] = array();
			$existingAttributeValues[$type][$value] = true;
			$state = isset($oldAttributeValues[$type][$value]) ? 'dark' : 'success';
			$error = ' class="alert-warning" role="alert"';
			$entityType = '?';
			if (isset($this->standardAttributes[$type])) {
				foreach ($this->standardAttributes[$type] as $data)
					if ($data['value'] == $value) {
						$error = ($data['swamidStd']) ? '' : ' class="alert-danger" role="alert"';
						$entityType = $data['type'];
					}
			}?>
        <b><?=$type?></b>
        <ul>
          <li><div<?=$error?>><span class="text-<?=$state?>"><?=$value?></span> (<?=$entityType?>) <a href="?edit=EntityAttributes&Entity=<?=$this->dbIdNr?>&oldEntity=<?=$this->dbOldIdNr?>&type=<?=$type?>&attribute=<?=$value?>&action=Delete"><i class="fas fa-trash"></i></a></div></li><?php
			$oldType = $type;
			while ($attribute = $entityAttributesHandler->fetch(PDO::FETCH_ASSOC)) {
				$type = $attribute['type'];
				$value = $attribute['attribute'];
				if (isset($oldAttributeValues[$type][$value])) {
					$state = 'dark';
				} else {
					$state = 'success';
				}
				$error = ' class="alert-warning" role="alert"';
				$entityType = '?';
				if (isset($this->standardAttributes[$type])) {
					foreach ($this->standardAttributes[$type] as $data)
						if ($data['value'] == $value) {
							$error = ($data['swamidStd']) ? '' : ' class="alert-danger" role="alert"';
							$entityType = $data['type'];
						}
				}
				if ($oldType != $type) {
					print "\n        </ul>";
					printf ("\n        <b>%s</b>\n        <ul>", $type);
					$oldType = $type;
					if (! isset($existingAttributeValues[$type]) )
						$existingAttributeValues[$type] = array();
				}
				printf ('%s          <li><div%s><span class="text-%s">%s</span> (%s) <a href="?edit=EntityAttributes&Entity=%d&oldEntity=%d&type=%s&attribute=%s&action=Delete"><i class="fas fa-trash"></i></a></div></li>', "\n", $error, $state, $value, $entityType, $this->dbIdNr, $this->dbOldIdNr, $type, $value);
				$existingAttributeValues[$type][$value] = true;
			}
			print "\n        </ul>\n";
		}
		print '        <hr>
        Quick-links
        <ul>';
		foreach ($this->standardAttributes as $type => $values) {
			printf ('%s          <li>%s</li><ul>', "\n", $type);
			foreach ($values as $data) {
				$entityType = $data['type'];
				if (($entityType == 'IdP/SP' || ($entityType == 'IdP' && $this->isIdP) || ($entityType == 'SP' && $this->isSP)) && $data['swamidStd']) {
					$value = $data['value'];
					if (isset($existingAttributeValues[$type]) && isset($existingAttributeValues[$type][$value])) {
						printf ('%s            <li>%s</li>', "\n", $value);
					} else {
						printf ('%s            <li><a href="?edit=EntityAttributes&Entity=%d&oldEntity=%d&type=%s&attribute=%s&action=Add">[copy]<a> %s</li>', "\n", $this->dbIdNr, $this->dbOldIdNr, $type, $value, $value);
					}
				}
			}
			printf ('%s          </ul></li>', "\n");
		}
		print '
        </ul>
        <form>
          <input type="hidden" name="edit" value="EntityAttributes">
          <input type="hidden" name="Entity" value="' . $this->dbIdNr . '">
          <input type="hidden" name="oldEntity" value="' . $this->dbOldIdNr . '">
          <select name="type">
            <option value="assurance-certification">assurance-certification</option>
            <option value="entity-category">entity-category</option>
            <option value="entity-category-support">entity-category-support</option>
            <option value="subject-id:req">subject-id:req</option>
          </select>
          <input type="text" name="attribute">
          <br>
          <input type="submit" name="action" value="Add">
        </form>
        <a href="./?validateEntity=' . $this->dbIdNr . '"><button>Back</button></a>
      </div><!-- end col -->
      <div class="col">' . "\n";

		$entityAttributesHandler->bindParam(':Id', $this->dbOldIdNr);
		$entityAttributesHandler->execute();

		if ($attribute = $entityAttributesHandler->fetch(PDO::FETCH_ASSOC)) {
			if (isset($existingAttributeValues[$attribute['type']][$attribute['attribute']])) {
				$addLink = '';
				$state = 'dark';
			} else {
				$addLink = '<a href="?edit=EntityAttributes&Entity='.$this->dbIdNr.'&oldEntity='.$this->dbOldIdNr.'&type='.$attribute['type'].'&attribute='.$attribute['attribute'].'&action=Add">[copy]</a> ';
				$state = 'danger';
			}?>
        <b><?=$attribute['type']?></b>
        <ul>
          <li><?=$addLink?><span class="text-<?=$state?>"><?=$attribute['attribute']?></span></li><?php
			$oldType = $attribute['type'];
			while ($attribute = $entityAttributesHandler->fetch(PDO::FETCH_ASSOC)) {
				if (isset($existingAttributeValues[$attribute['type']][$attribute['attribute']])) {
					$addLink = '';
					$state = 'dark';
				} else {
					$addLink = '<a href="?edit=EntityAttributes&Entity='.$this->dbIdNr.'&oldEntity='.$this->dbOldIdNr.'&type='.$attribute['type'].'&attribute='.$attribute['attribute'].'&action=Add">[copy]</a> ';
					$state = 'danger';
				}
				if ($oldType != $attribute['type']) {
					print "\n        </ul>";
					printf ("\n        <b>%s</b>\n        <ul>", $attribute['type']);
					$oldType = $attribute['type'];
				}
				printf ('%s          <li>%s<span class="text-%s">%s</span></li>', "\n", $addLink, $state, $attribute['attribute']);
			}?>

        </ul><?php
		}
		print "\n      </div><!-- end col -->\n    </div><!-- end row -->\n";
	}
	private function editIdPErrorURL() {
		if (isset($_GET['action']) && isset($_GET['errorURL']) && $_GET['errorURL'] != '') {
			$EntityDescriptor = $this->getEntityDescriptor($this->newXml);
			$errorURLValue = trim(urldecode($_GET['errorURL']));

			# Find md:IDPSSODescriptor in XML
			$child = $EntityDescriptor->firstChild;
			$IDPSSODescriptor = false;
			while ($child && ! $IDPSSODescriptor) {
				if ($child->nodeName == 'md:IDPSSODescriptor')
					$IDPSSODescriptor = $child;
				$child = $child->nextSibling;
			}

			$update = false;
			switch ($_GET['action']) {
				case 'Update' :
					if ($IDPSSODescriptor) {
						$IDPSSODescriptor->setAttribute('errorURL', $errorURLValue);
						$errorURLUpdateHandler = $this->metaDb->prepare("REPLACE INTO EntityURLs (entity_id, URL, type ) VALUES (:Id, :URL, 'error');");
						$errorURLUpdateHandler->bindParam(':Id', $this->dbIdNr);
						$errorURLUpdateHandler->bindParam(':URL', $errorURLValue);
						$errorURLUpdateHandler->execute();
						$update = true;
					}
					break;
				case 'Delete' :
					if ($IDPSSODescriptor) {
						$IDPSSODescriptor->removeAttribute('errorURL');
						$errorURLUpdateHandler = $this->metaDb->prepare("DELETE FROM EntityURLs WHERE entity_id = :Id AND type = 'error';");
						$errorURLUpdateHandler->bindParam(':Id', $this->dbIdNr);
						$errorURLUpdateHandler->execute();
						$update = true;
					}
					$errorURLValue = '';
					break;
			}
			if ($update) {
				$this->saveXML();
			}
		} else {
			$errorURLValue = '';
		}

		$errorURLHandler = $this->metaDb->prepare("SELECT DISTINCT URL FROM EntityURLs WHERE entity_id = :Id AND type = 'error';");
		$errorURLHandler->bindParam(':Id', $this->dbIdNr);
		$errorURLHandler->execute();
		$newURL = ($errorURL = $errorURLHandler->fetch(PDO::FETCH_ASSOC)) ? $errorURL['URL'] : 'Missing';
		$errorURLHandler->bindParam(':Id', $this->dbOldIdNr);
		$errorURLHandler->execute();
		$oldURL = ($errorURL = $errorURLHandler->fetch(PDO::FETCH_ASSOC)) ? $errorURL['URL'] : 'Missing';

		if ($newURL == $oldURL) {
			$copy = '';
			$newstate = 'dark';
			$oldstate = 'dark';
		} else {
			$copy = sprintf('<a href="?edit=IdPErrorURL&Entity=%d&oldEntity=%d&action=Update&errorURL=%s">[copy]</a> ', $this->dbIdNr, $this->dbOldIdNr, urlencode($errorURL['URL']));
			$newstate = ($newURL == 'Missing') ? 'dark' : 'success';
			$oldstate = ($oldURL == 'Missing') ? 'dark' :'danger';
		}
		$oldURL = ($oldURL == 'Missing') ? 'Missing' : sprintf ('<a href="%s" class="text-%s" target="blank">%s</a>', $oldURL, $oldstate, $oldURL);
		if ($newURL != 'Missing') {
			$baseLink = sprintf('<a href="?edit=IdPErrorURL&Entity=%d&oldEntity=%d&errorURL=%s&action=', $this->dbIdNr, $this->dbOldIdNr, urlencode($newURL));
			$links = $baseLink . 'Copy"><i class="fas fa-pencil-alt"></i></a> ' . $baseLink . 'Delete"><i class="fas fa-trash"></i></a> ';
			$newURL = sprintf ('<a href="%s" class="text-%s" target="blank">%s</a>', $newURL, $newstate, $newURL);
		} else {
			$links = '';
		}

		printf('%s    <div class="row">%s      <div class="col">', "\n", "\n");
		printf('%s        <b>errorURL</b>%s        <ul><li>%s<p class="text-%s" style="white-space: nowrap;overflow: hidden;text-overflow: ellipsis;max-width: 30em;">%s</p></li></ul>', "\n", "\n", $links, $newstate, $newURL);
		print '
        <form>
          <input type="hidden" name="edit" value="IdPErrorURL">
          <input type="hidden" name="Entity" value="' . $this->dbIdNr . '">
          <input type="hidden" name="oldEntity" value="' . $this->dbOldIdNr . '">
          New errorURL :
          <input type="text" name="errorURL" value="' . $errorURLValue . '">
          <br>
          <input type="submit" name="action" value="Update">
        </form>
        <a href="./?validateEntity=' . $this->dbIdNr . '"><button>Back</button></a>
      </div><!-- end col -->
      <div class="col">' . "\n";
		if ($this->oldExists)
			printf('%s        <b>errorURL</b>%s        <ul><li>%s<p class="text-%s" style="white-space: nowrap;overflow: hidden;text-overflow: ellipsis;max-width: 30em;">%s</p></li></ul>', "\n", "\n", $copy, $oldstate, $oldURL);
		print "\n      </div><!-- end col -->\n    </div><!-- end row -->\n";
	}
	private function editIdPScopes() {
		if (isset($_GET['action']) && isset($_GET['value']) && trim($_GET['value']) != '') {
			$changed = false;
			$EntityDescriptor = $this->getEntityDescriptor($this->newXml);
			$scopeValue = trim($_GET['value']);

			# Find md:IDPSSODescriptor in XML
			$child = $EntityDescriptor->firstChild;
			$IDPSSODescriptor = false;
			while ($child && ! $IDPSSODescriptor) {
				if ($child->nodeName == 'md:IDPSSODescriptor')
					$IDPSSODescriptor = $child;
				$child = $child->nextSibling;
			}

			switch ($_GET['action']) {
				case 'Add' :
					if ($IDPSSODescriptor) {
						$child = $IDPSSODescriptor->firstChild;
						$Extensions = false;
						while ($child && ! $Extensions) {
							switch ($child->nodeName) {
								case 'ds:Signature' :
									break;
								case 'md:Extensions' :
									$Extensions = $child;
									break;
								default :
									$Extensions = $this->newXml->createElement('md:Extensions');
									$IDPSSODescriptor->insertBefore($Extensions, $child);
							}
							$child = $child->nextSibling;
						}
						if (! $Extensions) {
							$Extensions = $this->newXml->createElement('md:Extensions');
							$IDPSSODescriptor->appendChild($Extensions);
						}

						$child = $Extensions->firstChild;
						$beforeChild = false;
						$Scope = false;
						$shibmdFound = false;
						while ($child && ! $Scope) {
							switch ($child->nodeName) {
								case 'shibmd:Scope' :
									$shibmdFound = true;
									if ($child->textContent == $scopeValue)
										$Scope = $child;
									break;
								case 'mdui:UIInfo' :
								case 'mdui:DiscoHints' :
									$beforeChild = $beforeChild ? $beforeChild : $child;
									break;
							}
							$child = $child->nextSibling;
						}
						if (! $Scope ) {
							$Scope = $this->newXml->createElement('shibmd:Scope', $scopeValue);
							$Scope->setAttribute('regexp', 'false');
							if ($beforeChild)
								$Extensions->insertBefore($Scope, $beforeChild);
							else
								$Extensions->appendChild($Scope);
							$changed = true;
						}

						if (! $shibmdFound) {
							$EntityDescriptor->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:shibmd', 'urn:mace:shibboleth:metadata:1.0');
						}

						if ($changed) {
							$scopesInsertHandler = $this->metaDb->prepare('INSERT INTO Scopes (`entity_id`, `scope`, `regexp`) VALUES (:Id, :Scope, 0);');
							$scopesInsertHandler->bindParam(':Id', $this->dbIdNr);
							$scopesInsertHandler->bindParam(':Scope', $scopeValue);
							$scopesInsertHandler->execute();
						}
					}
					break;
				case 'Delete' :
					if ($IDPSSODescriptor) {
						$child = $IDPSSODescriptor->firstChild;
						$Extensions = false;
						while ($child && ! $Extensions) {
							if ($child->nodeName == 'md:Extensions' )
								$Extensions = $child;
							$child = $child->nextSibling;
						}

						if ($Extensions) {
							$child = $Extensions->firstChild;
							$moreElements = false;
							$child = $Extensions->firstChild;
							$Scope = false;
							while ($child && ! $Scope) {
								if ($child->nodeName == 'shibmd:Scope' && $child->textContent == $scopeValue) {
									$Extensions->removeChild($child);
									$changed = true;
								} else $moreElements = true;
								$child = $child->nextSibling;
							}
							if (! $moreElements ) {
								$IDPSSODescriptor->removeChild($Extensions);
							}
							if ($changed) {
								$scopesDeleteHandler = $this->metaDb->prepare('DELETE FROM Scopes WHERE entity_id = :Id AND scope = :Scope;');
								$scopesDeleteHandler->bindParam(':Id', $this->dbIdNr);
								$scopesDeleteHandler->bindParam(':Scope', $scopeValue);
								$scopesDeleteHandler->execute();
							}
						}
					}
					$scopeValue = '';
					break;
				}
			if ($changed) {
				$this->saveXML();
			}
		}

		$scopesHandler = $this->metaDb->prepare('SELECT `scope`, `regexp` FROM Scopes WHERE `entity_id` = :Id;');
		$scopesHandler->bindParam(':Id', $this->dbOldIdNr);
		$scopesHandler->execute();
		$oldScopes = array();
		while ($scope = $scopesHandler->fetch(PDO::FETCH_ASSOC))
			$oldScopes[$scope['scope']] = array('regexp' => $scope['regexp'], 'state' => 'removed');

		$scopesHandler->bindParam(':Id', $this->dbIdNr);
		$scopesHandler->execute();
		printf('%s    <div class="row">%s      <div class="col">%s        <b>Scopes</b>%s        <ul>%s', "\n", "\n", "\n", "\n", "\n");
		while ($scope = $scopesHandler->fetch(PDO::FETCH_ASSOC)) {
			if (isset($oldScopes[$scope['scope']])) {
				$state = 'dark';
				$oldScopes[$scope['scope']]['state'] = 'same';
			} else {
				$state = 'success';
			}
			$baseLink = '<a href="?edit=IdPScopes&Entity='.$this->dbIdNr.'&oldEntity='.$this->dbOldIdNr.'&value='.$scope['scope'].'&action=';
			$links = $baseLink . 'Copy"><i class="fas fa-pencil-alt"></i></a> ' . $baseLink . 'Delete"><i class="fas fa-trash"></i></a> ';
			printf ('          <li>%s<span class="text-%s">%s (regexp="%s")</span></li>%s', $links, $state, $scope['scope'], $scope['regexp'] ? 'true' : 'false', "\n");
		}
		print '        </ul>
        <form>
          <input type="hidden" name="edit" value="IdPScopes">
          <input type="hidden" name="Entity" value="' . $this->dbIdNr . '">
          <input type="hidden" name="oldEntity" value="' . $this->dbOldIdNr . '">
          New Scope :
          <input type="text" name="value" value="' . $scopeValue . '">
          <br>
          <input type="submit" name="action" value="Add">
        </form>
        <a href="./?validateEntity=' . $this->dbIdNr . '"><button>Back</button></a>
      </div><!-- end col -->
      <div class="col">';
		if ($this->oldExists) {
			print '
        <b>Scopes</b>
        <ul>' . "\n";
			foreach ($oldScopes as $scope => $data) {
				if ($data['state'] == 'same') {
					$copy = '';
					$state = 'dark';
				} else {
					$copy = sprintf('<a href ="?edit=IdPScopes&Entity=%d&oldEntity=%d&action=Add&value=%s">[copy]</a> ', $this->dbIdNr, $this->dbOldIdNr, $scope);
					$state = 'danger';
				}
				printf ('          <li>%s<span class="text-%s">%s (regexp="%s")</span></li>%s', $copy, $state, $scope, $data['regexp'] ? 'true' : 'false', "\n");
			}
			print ('        </ul>');
		}
		printf ('%s      </div><!-- end col -->%s    </div><!-- end row -->%s', "\n", "\n", "\n");
	}
	private function editMDUI($type) {
		printf ('%s    <div class="row">%s      <div class="col">%s', "\n", "\n", "\n");
		$edit = $type == 'IDPSSO' ? 'IdPMDUI' : 'SPMDUI';
		if (isset($_GET['action'])) {
			$error = '';
			if (isset($_GET['element']) && trim($_GET['element']) != '') {
				$elementValue = trim($_GET['element']);
				$elementmd = 'mdui:'.$elementValue;
			} else {
				$error .= '<br>No Element selected';
				$elementValue = '';
			}
			if (isset($_GET['lang']) && trim($_GET['lang']) != '') {
				$langvalue = strtolower(trim($_GET['lang']));
			} else {
				$error .= $_GET['action'] == "Add" ? '<br>Lang is empty' : '';
				$langvalue = '';
			}
			if (isset($_GET['value']) && trim($_GET['value']) != '') {
				$value = trim($_GET['value']);
			} else {
				$error .= $_GET['action'] == "Add" ? '<br>Value is empty' : '';
				$value = '';
			}
			if (isset($_GET['height']) && $_GET['height'] > 0 ) {
				$heightValue = $_GET['height'];
			} else {
				$error .= $_GET['element'] == "Logo" ? '<br>Height must be larger than 0' : '';
				$heightValue = 0;
			}
			if (isset($_GET['width']) && $_GET['width'] > 0 ) {
				$widthValue = $_GET['width'];
			} else {
				$error .= $_GET['element'] == "Logo" ? '<br>Width must be larger than 0' : '';
				$widthValue = 0;
			}
			if ($error) {
				printf ('<div class="row alert alert-danger" role="alert">Error:%s</div>', $error);
			} else {
				$changed = false;
				$EntityDescriptor = $this->getEntityDescriptor($this->newXml);

				# Find md:IDPSSODescriptor in XML
				$child = $EntityDescriptor->firstChild;
				$SSODescriptor = false;
				while ($child && ! $SSODescriptor) {
					if ($child->nodeName == 'md:'.$type.'Descriptor')
						$SSODescriptor = $child;
					$child = $child->nextSibling;
				}
				switch ($_GET['action']) {
					case 'Add' :
						if ($SSODescriptor) {
							$changed = true;
							$child = $SSODescriptor->firstChild;
							$Extensions = false;
							while ($child && ! $Extensions) {
								switch ($child->nodeName) {
									case 'ds:Signature' :
										break;
									case 'md:Extensions' :
										$Extensions = $child;
										break;
									default :
										$Extensions = $this->newXml->createElement('md:Extensions');
										$SSODescriptor->insertBefore($Extensions, $child);
								}
								$child = $child->nextSibling;
							}
							if (! $Extensions) {
								$Extensions = $this->newXml->createElement('md:Extensions');
								$SSODescriptor->appendChild($Extensions);
							}
							$child = $Extensions->firstChild;
							$beforeChild = false;
							$UUInfo = false;
							$mduiFound = false;
							while ($child && ! $UUInfo) {
								switch ($child->nodeName) {
									case 'mdui:UIInfo' :
										$mduiFound = true;
										$UUInfo = $child;
										break;
									case 'mdui:DiscoHints' :
										$beforeChild = $beforeChild ? $beforeChild : $child;
										$mduiFound = true;
										break;
								}
								$child = $child->nextSibling;
							}
							if (! $UUInfo ) {
								$UUInfo = $this->newXml->createElement('mdui:UIInfo');
								if ($beforeChild)
									$Extensions->insertBefore($UUInfo, $beforeChild);
								else
									$Extensions->appendChild($UUInfo);
							}
							if (! $mduiFound) {
								$EntityDescriptor->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:mdui', 'urn:oasis:names:tc:SAML:metadata:ui');
							}
							# Find mdui:* in XML
							$child = $UUInfo->firstChild;
							$MduiElement = false;
							while ($child && ! $MduiElement) {
								if ($child->nodeName == $elementmd && $child->getAttribute('xml:lang') == $langvalue) {
									if ($elementmd == 'mdui:Logo') {
										if ( $child->getAttribute('height') == $heightValue && $child->getAttribute('width') == $widthValue)
											$MduiElement = $child;
									} else {
										$MduiElement = $child;
									}
								}
								$child = $child->nextSibling;
							}
							if ($elementmd == 'mdui:Logo' || $elementmd == 'mdui:InformationURL' || $elementmd == 'mdui:PrivacyStatementURL') {
								$value = str_replace(' ', '+', $value);
							}
							if ($MduiElement) {
								# Update value
								$MduiElement->nodeValue = htmlspecialchars($value);
								if ($elementmd == 'mdui:Logo') {
									$mduiUpdateHandler = $this->metaDb->prepare('UPDATE Mdui SET data = :Data WHERE type = :Type AND entity_id = :Id AND lang = :Lang AND height = :Height AND  width = :Width AND element = :Element;');
									$mduiUpdateHandler->bindParam(':Height', $heightValue);
									$mduiUpdateHandler->bindParam(':Width', $widthValue);
								} else {
									$mduiUpdateHandler = $this->metaDb->prepare('UPDATE Mdui SET data = :Data WHERE type = :Type AND entity_id = :Id AND lang = :Lang AND element = :Element;');
								}
								$mduiUpdateHandler->bindParam(':Id', $this->dbIdNr);
								$mduiUpdateHandler->bindParam(':Type', $type);
								$mduiUpdateHandler->bindParam(':Lang', $langvalue);
								$mduiUpdateHandler->bindParam(':Element', $elementValue);
								$mduiUpdateHandler->bindParam(':Data', $value);
								$mduiUpdateHandler->execute();
							} else {
								# Add if missing
								$MduiElement = $this->newXml->createElement($elementmd, htmlspecialchars($value));
								$MduiElement->setAttribute('xml:lang', $langvalue);
								if ($elementmd == 'mdui:Logo') {
									$MduiElement->setAttribute('height', $heightValue);
									$MduiElement->setAttribute('width', $widthValue);
									$mduiAddHandler = $this->metaDb->prepare('INSERT INTO Mdui (entity_id, type, lang, height, width, element, data) VALUES (:Id, :Type, :Lang, :Height, :Width, :Element, :Data);');
									$mduiAddHandler->bindParam(':Height', $heightValue);
									$mduiAddHandler->bindParam(':Width', $widthValue);
								} else {
									$mduiAddHandler = $this->metaDb->prepare('INSERT INTO Mdui (entity_id, type, lang, height, width, element, data) VALUES (:Id, :Type, :Lang, 0, 0, :Element, :Data);');
								}
								$UUInfo->appendChild($MduiElement);
								$mduiAddHandler->bindParam(':Id', $this->dbIdNr);
								$mduiAddHandler->bindParam(':Type', $type);
								$mduiAddHandler->bindParam(':Lang', $langvalue);
								$mduiAddHandler->bindParam(':Element', $elementValue);
								$mduiAddHandler->bindParam(':Data', $value);
								$mduiAddHandler->execute();
							}
						}
						break;
					case 'Delete' :
						if ($SSODescriptor) {
							$child = $SSODescriptor->firstChild;
							$Extensions = false;
							while ($child && ! $Extensions) {
								if ($child->nodeName == 'md:Extensions')
									$Extensions = $child;
								$child = $child->nextSibling;
							}
							if ($Extensions) {
								$child = $Extensions->firstChild;
								$UUInfo = false;
								$moreExtentions = false;
								while ($child && ! $UUInfo) {
									switch ($child->nodeName) {
										case 'mdui:UIInfo' :
											$mduiFound = true;
											$UUInfo = $child;
											break;
										default :
											$moreExtentions = true;
											break;
									}
									$child = $child->nextSibling;
								}
								$moreExtentions = $moreExtentions ? true : $child;
								if ($UUInfo) {
									# Find mdui:* in XML
									$child = $UUInfo->firstChild;
									$MduiElement = false;
									$moreMduiElement = false;
									while ($child && ! $MduiElement) {
										if ($child->nodeName == $elementmd && $child->getAttribute('xml:lang') == $langvalue) {
											if ($elementmd == 'mdui:Logo') {
												if ($child->getAttribute('height') == $heightValue && $child->getAttribute('width') == $widthValue) {
													$MduiElement = $child;
												} else {
													$moreMduiElement = true;
												}
											} else {
												$MduiElement = $child;
											}
										} else
											$moreMduiElement = true;
										$child = $child->nextSibling;
									}
									$moreMduiElement = $moreMduiElement ? true : $child;
									if ($MduiElement) {
										# Remove Node
										if ($elementmd == 'mdui:Logo') {
											$mduiRemoveHandler = $this->metaDb->prepare('DELETE FROM Mdui WHERE type = :Type AND entity_id = :Id AND lang = :Lang AND height = :Height AND  width = :Width AND element = :Element;');
											$mduiRemoveHandler->bindParam(':Height', $heightValue);
											$mduiRemoveHandler->bindParam(':Width', $widthValue);
										} else {
											$mduiRemoveHandler = $this->metaDb->prepare('DELETE FROM Mdui WHERE type = :Type AND entity_id = :Id AND lang = :Lang AND element = :Element;');
										}
										$mduiRemoveHandler->bindParam(':Id', $this->dbIdNr);
										$mduiRemoveHandler->bindParam(':Type', $type);
										$mduiRemoveHandler->bindParam(':Lang', $langvalue);
										$mduiRemoveHandler->bindParam(':Element', $elementValue);
										$mduiRemoveHandler->execute();
										$changed = true;
										$UUInfo->removeChild($MduiElement);
										if (! $moreMduiElement) {
											$Extensions->removeChild($UUInfo);
											if (! $moreExtentions)
												$SSODescriptor->removeChild($Extensions);
										}
									}
								}
							}
						}
						$elementValue = '';
						$langvalue = '';
						$value = '';
						$heightValue = 0;
						$widthValue = 0;
						break;
				}
				if ($changed) {
					$this->saveXML();
				}
			}
		} else {
			$elementValue = '';
			$langvalue = '';
			$value = '';
			$heightValue = 0;
			$widthValue = 0;
		}
		$mduiHandler = $this->metaDb->prepare('SELECT element, lang, height, width, data FROM Mdui WHERE entity_id = :Id AND type = :Type ORDER BY lang, element;');
		$mduiHandler->bindParam(':Type', $type);
		$oldMDUIElements = array();
		$mduiHandler->bindParam(':Id', $this->dbOldIdNr);
		$mduiHandler->execute();
		while ($mdui = $mduiHandler->fetch(PDO::FETCH_ASSOC)) {
			if (! isset($oldMDUIElements[$mdui['lang']]) )
				$oldMDUIElements[$mdui['lang']] = array();
			$oldMDUIElements[$mdui['lang']][$mdui['element']] = array('value' => $mdui['data'], 'height' => $mdui['height'], 'width' => $mdui['width'], 'state' => 'removed');
		}
		$oldLang = 'xxxxxxx';
		$mduiHandler->bindParam(':Id', $this->dbIdNr);
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
				printf('%s        <b>Lang = "%s" - %s</b>%s        <ul>', $showEndUL ? "\n        </ul>\n" : '', $lang, $fullLang, "\n");
				$showEndUL = true;
				$oldLang = $lang;
			}
			$element = $mdui['element'];
			$data = $mdui['data'];
			$height = $mdui['height'];
			$width = $mdui['width'];
			if (isset ($oldMDUIElements[$lang][$element])) {
				if ($oldMDUIElements[$lang][$element]['value'] == $data) {
					if ($element == 'Logo') {
						if ($oldMDUIElements[$lang][$element]['height'] == $height && $oldMDUIElements[$lang][$element]['width'] == $width) {
							$state = 'dark';
							$oldMDUIElements[$lang][$element]['state'] = 'same';
						} else {
							$state = 'success';
							$oldMDUIElements[$lang][$element]['state'] = 'changed';
						}
					} else {
						$state = 'dark';
						$oldMDUIElements[$lang][$element]['state'] = 'same';
					}
				} else {
					$state = 'success';
					$oldMDUIElements[$lang][$element]['state'] = 'changed';
				}
			} else {
				$state = 'success';
			}
			$baseLink = '<a href="?edit='.$edit.'&Entity='.$this->dbIdNr.'&oldEntity='.$this->dbOldIdNr.'&element='.$element.'&height='.$height.'&width='.$width.'&lang='.$lang.'&value='.urlencode($data).'&action=';
			$links = $baseLink . 'Copy"><i class="fas fa-pencil-alt"></i></a> ' . $baseLink . 'Delete"><i class="fas fa-trash"></i></a> ';
			switch ($element) {
				case 'InformationURL' :
				case 'Logo' :
				case 'PrivacyStatementURL' :
					$data = sprintf ('<a href="%s" class="text-%s" target="blank">%s</a>', $data, $state, $data);
					break;
			}
			if ($element == 'Logo') {
				printf ('%s          <li>%s<span class="text-%s">%s (%dx%d) = %s</span></li>', "\n", $links, $state, $element, $height, $width, $data);
			} else {
				printf ('%s          <li>%s<span class="text-%s">%s = %s</span></li>', "\n", $links, $state, $element, $data);
			}
		}
		if ($showEndUL) {
			print "\n        </ul>";
		}
		printf('
        <form>
          <input type="hidden" name="edit" value="%s">
          <input type="hidden" name="Entity" value="%d">
          <input type="hidden" name="oldEntity" value="%d">
          <div class="row">
            <div class="col-1">Element: </div>
            <div class="col">
              <select name="element">
                <option value="DisplayName"%s>DisplayName</option>
                <option value="Description"%s>Description</option>
                <option value="Keywords"%s>Keywords</option>
                <option value="Logo"%s>Logo</option>
                <option value="InformationURL"%s>InformationURL</option>
                <option value="PrivacyStatementURL"%s>PrivacyStatementURL</option>
              </select>
            </div>
          </div>
          <div class="row">
            <div class="col-1">Lang: </div>
            <div class="col">', $edit, $this->dbIdNr, $this->dbOldIdNr, $elementValue == 'DisplayName' ? ' selected' : '', $elementValue == 'Description' ? ' selected' : '', $elementValue == 'Keywords' ? ' selected' : '', $elementValue == 'Logo' ? ' selected' : '', $elementValue == 'InformationURL' ? ' selected' : '', $elementValue == 'PrivacyStatementURL' ? ' selected' : '');
			$this->showLangSelector($langvalue);
			printf('            </div>
          </div>
          <div class="row">
            <div class="col-1">Value: </div>
            <div class="col"><input type="text" size="60" name="value" value="%s"></div>
          </div>
          <div class="row">
            <div class="col-1">Height: </div>
            <div class="col"><input type="text" size="4" name="height" value="%d"> (only for Logo)</div>
          </div>
          <div class="row">
            <div class="col-1">Width: </div>
            <div class="col"><input type="text" size="4" name="width" value="%d"> (only for Logo)</div>
          </div>
          <button type="submit" name="action" value="Add">Add/Update</button>
        </form>
        <a href="./?validateEntity=%d"><button>Back</button></a>
      </div><!-- end col -->
      <div class="col">', $value, $heightValue, $widthValue, $this->dbIdNr);

	  foreach ($oldMDUIElements as $lang => $elementValues) {
			printf ('%s        <b>Lang = "%s"</b>%s        <ul>', "\n", $lang, "\n");
			foreach ($elementValues as $element => $data) {
				switch ($data['state']) {
					case 'same' :
						$copy = '';
						$state = 'dark';
						break;
					case 'removed' :
						if ($element == 'Logo') {
							$copy = sprintf('<a href ="?edit=%s&Entity=%d&oldEntity=%d&element=%s&lang=%s&value=%s&height=%d&width=%d&action=Add">[copy]</a> ', $edit, $this->dbIdNr, $this->dbOldIdNr, $element, $lang, $data['value'], $data['height'], $data['width']);
						} else {
							$copy = sprintf('<a href ="?edit=%s&Entity=%d&oldEntity=%d&element=%s&lang=%s&value=%s&action=Add">[copy]</a> ', $edit, $this->dbIdNr, $this->dbOldIdNr, $element, $lang, $data['value']);
						}
						$state = 'danger';
						break;
					default :
						$copy = '';
						$state = 'danger';
				}
				switch ($element) {
					case 'InformationURL' :
					case 'Logo' :
					case 'PrivacyStatementURL' :
						$value = sprintf ('<a href="%s" class="text-%s" target="blank">%s</a>', $data['value'], $state, $data['value']);
						break;
					default :
						$value = $data['value'];
				}
				if ($element == 'Logo') {
					printf ('%s          <li>%s<span class="text-%s">%s (%dx%d) = %s</span></li>', "\n", $copy, $state, $element, $data['height'], $data['width'], $value);
				} else {
					printf ('%s          <li>%s<span class="text-%s">%s = %s</span></li>', "\n", $copy, $state, $element, $value);
				}
			}
			print "\n        </ul>";
		}
		print "\n      </div><!-- end col -->\n    </div><!-- end row -->\n";
	}
	private function editDiscoHints() {
		printf ('%s    <div class="row">%s      <div class="col">', "\n", "\n");
		if (isset($_GET['action'])) {
			$error = '';
			if (isset($_GET['element']) && trim($_GET['element']) != '') {
				$elementValue = trim($_GET['element']);
				$elementmd = 'mdui:'.$elementValue;
			} else {
				$error .= '<br>No Element selected';
				$elementValue = '';
			}
			if (isset($_GET['value']) && trim($_GET['value']) != '') {
				$value = trim($_GET['value']);
			} else {
				$error .= $_GET['action'] == "Add" ? '<br>Value is empty' : '';
				$value = '';
			}
			if ($error) {
				printf ('<div class="row alert alert-danger" role="alert">Error:%s</div>', $error);
			} else {
				$changed = false;
				$EntityDescriptor = $this->getEntityDescriptor($this->newXml);

				# Find md:IDPSSODescriptor in XML
				$child = $EntityDescriptor->firstChild;
				$SSODescriptor = false;
				while ($child && ! $SSODescriptor) {
					if ($child->nodeName == 'md:IDPSSODescriptor')
						$SSODescriptor = $child;
					$child = $child->nextSibling;
				}
				switch ($_GET['action']) {
					case 'Add' :
						if ($SSODescriptor) {
							$changed = true;
							$child = $SSODescriptor->firstChild;
							$Extensions = false;
							while ($child && ! $Extensions) {
								switch ($child->nodeName) {
									case 'ds:Signature' :
										break;
									case 'md:Extensions' :
										$Extensions = $child;
										break;
									default :
										$Extensions = $this->newXml->createElement('md:Extensions');
										$SSODescriptor->insertBefore($Extensions, $child);
								}
								$child = $child->nextSibling;
							}
							if (! $Extensions) {
								$Extensions = $this->newXml->createElement('md:Extensions');
								$SSODescriptor->appendChild($Extensions);
							}
							$child = $Extensions->firstChild;
							$beforeChild = false;
							$DiscoHints = false;
							$mduiFound = false;
							while ($child && ! $DiscoHints) {
								switch ($child->nodeName) {
									case 'mdui:UIInfo' :
										$mduiFound = true;
										break;
									case 'mdui:DiscoHints' :
										$DiscoHints = $child;
										$mduiFound = true;
										break;
								}
								$child = $child->nextSibling;
							}
							if (! $DiscoHints ) {
								$DiscoHints = $this->newXml->createElement('mdui:DiscoHints');
								$Extensions->appendChild($DiscoHints);
							}
							if (! $mduiFound) {
								$EntityDescriptor->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:mdui', 'urn:oasis:names:tc:SAML:metadata:ui');
							}
							# Find mdui:* in XML
							$child = $DiscoHints->firstChild;
							$MduiElement = false;
							while ($child && ! $MduiElement) {
								if ($child->nodeName == $elementmd && $child->nodeValue == $value ) {
									$MduiElement = $child;
								}
								$child = $child->nextSibling;
							}
							if (! $MduiElement) {
								# Add if missing
								$MduiElement = $this->newXml->createElement($elementmd, $value);
								$DiscoHints->appendChild($MduiElement);
								$mduiAddHandler = $this->metaDb->prepare("INSERT INTO Mdui (entity_id, type, element, data) VALUES (:Id, 'IDPDisco', :Element, :Data);");
 								$mduiAddHandler->bindParam(':Id', $this->dbIdNr);
								$mduiAddHandler->bindParam(':Element', $elementValue);
								$mduiAddHandler->bindParam(':Data', $value);
								$mduiAddHandler->execute();
							}
						}
						break;
					case 'Delete' :
						if ($SSODescriptor) {
							$child = $SSODescriptor->firstChild;
							$Extensions = false;
							while ($child && ! $Extensions) {
								if ($child->nodeName == 'md:Extensions')
									$Extensions = $child;
									$child = $child->nextSibling;
							}
							if ($Extensions) {
								$child = $Extensions->firstChild;
								$DiscoHints = false;
								$moreExtentions = false;
								while ($child && ! $DiscoHints) {
									switch ($child->nodeName) {
										case 'mdui:DiscoHints' :
											$mduiFound = true;
											$DiscoHints = $child;
											break;
										default :
											$moreExtentions = true;
											break;
									}
									$child = $child->nextSibling;
								}
								$moreExtentions = $moreExtentions ? true : $child;
								if ($DiscoHints) {
									# Find mdui:* in XML
									$child = $DiscoHints->firstChild;
									$MduiElement = false;
									$moreMduiElement = false;
									while ($child && ! $MduiElement) {
										if ($child->nodeName == $elementmd && $child->nodeValue == $value) {
											$MduiElement = $child;
										} else
											$moreMduiElement = true;
										$child = $child->nextSibling;
									}
									$moreMduiElement = $moreMduiElement ? true : $child;
									if ($MduiElement) {
										# Remove Node
										$mduiRemoveHandler = $this->metaDb->prepare("DELETE FROM Mdui WHERE type = 'IDPDisco' AND entity_id = :Id AND element = :Element AND data = :Data;");
										$mduiRemoveHandler->bindParam(':Id', $this->dbIdNr);
										$mduiRemoveHandler->bindParam(':Element', $elementValue);
										$mduiRemoveHandler->bindParam(':Data', $value);
										$mduiRemoveHandler->execute();
										$changed = true;
										$DiscoHints->removeChild($MduiElement);
										if (! $moreMduiElement) {
											$Extensions->removeChild($DiscoHints);
											if (! $moreExtentions)
												$SSODescriptor->removeChild($Extensions);
										}
									}
								}
							}
						}
						$elementValue = '';
						$value = '';
						break;
				}
				if ($changed) {
					$this->saveXML();
				}
			}
		} else {
			$elementValue = '';
			$value = '';
		}
		$mduiHandler = $this->metaDb->prepare("SELECT element, data FROM Mdui WHERE entity_id = :Id AND type = 'IDPDisco' ORDER BY element;");
		$oldMDUIElements = array();
		$mduiHandler->bindParam(':Id', $this->dbOldIdNr);
		$mduiHandler->execute();
		while ($mdui = $mduiHandler->fetch(PDO::FETCH_ASSOC)) {
			if (! isset($oldMDUIElements[$mdui['element']]) )
			$oldMDUIElements[$mdui['element']] = array();
			$oldMDUIElements[$mdui['element']][$mdui['data']] = 'removed';
		}
		$oldElement = 'xxxxxxx';
		$mduiHandler->bindParam(':Id', $this->dbIdNr);
		$mduiHandler->execute();
		$showEndUL = false;
		while ($mdui = $mduiHandler->fetch(PDO::FETCH_ASSOC)) {
			$element = $mdui['element'];
			$data = $mdui['data'];
			if ($oldElement != $element) {
				printf('%s        <b>%s</b>%s        <ul>', $showEndUL ? "\n        </ul>\n" : "\n", $element, "\n");
				$showEndUL = true;
				$oldElement = $element;
			}
			if (isset ($oldMDUIElements[$element]) && isset($oldMDUIElements[$element][$data])) {
				$oldMDUIElements[$element][$data] = 'same';
				$state = 'dark';
			} else
				$state = 'success';
			$baseLink = '<a href="?edit=DiscoHints&Entity='.$this->dbIdNr.'&oldEntity='.$this->dbOldIdNr.'&element='.$element.'&value='.$data.'&action=';
			$links = $baseLink . 'Copy"><i class="fas fa-pencil-alt"></i></a> ' . $baseLink . 'Delete"><i class="fas fa-trash"></i></a> ';
			printf ('%s          <li>%s<span class="text-%s">%s</span></li>', "\n", $links, $state, $data);
		}
		if ($showEndUL) {
			print "\n        </ul>";
		}
		printf('
        <form>
          <input type="hidden" name="edit" value="DiscoHints">
          <input type="hidden" name="Entity" value="%d">
          <input type="hidden" name="oldEntity" value="%d">
          <div class="row">
            <div class="col-1">Element: </div>
            <div class="col">
              <select name="element">
                <option value="DomainHint"%s>DomainHint</option>
                <option value="GeolocationHint"%s>GeolocationHint</option>
                <option value="IPHint"%s>IPHint</option>
              </select>
            </div>
          </div>
          <div class="row">
            <div class="col-1">Value: </div>
            <div class="col"><input type="text" name="value" value="%s"></div>
          </div>
          <button type="submit" name="action" value="Add">Add/Update</button>
        </form>
        <a href="./?validateEntity=%d"><button>Back</button></a>
      </div><!-- end col -->
      <div class="col">', $this->dbIdNr, $this->dbOldIdNr, $elementValue == 'DomainHint' ? ' selected' : '', $elementValue == 'GeolocationHint' ? ' selected' : '', $elementValue == 'IPHint' ? ' selected' : '', $value, $this->dbIdNr);

		foreach ($oldMDUIElements as $element => $elementValues) {
			printf ('%s        <b>%s</b>%s        <ul>', "\n", $element, "\n");
			foreach ($elementValues as $data => $state) {
				switch ($state) {
					case 'same' :
						$copy = '';
						$state = 'dark';
						break;
					case 'removed' :
						$copy = sprintf('<a href ="?edit=DiscoHints&Entity=%d&oldEntity=%d&element=%s&value=%s&action=Add">[copy]</a> ', $this->dbIdNr, $this->dbOldIdNr, $element, $data);
						$state = 'danger';
						break;
					default :
						$copy = '';
						$state = 'danger';
				}
				printf ('%s          <li>%s<span class="text-%s">%s</span></li>', "\n", $copy, $state, $data);
			}
			print "\n        </ul>";
		}
		print "\n      </div><!-- end col -->\n    </div><!-- end row -->\n";
	}
	private function addKeyInfo($type) {
		$edit = $type == 'IDPSSO' ? 'IdPKeyInfo' : 'SPKeyInfo';
		$added = false;
		if (isset($_POST['certificate']) && isset($_POST['use'])) {
			$certificate = str_replace(array("\r") ,array(''), $_POST['certificate']);
			$use = $_POST['use'];
			$cert = "-----BEGIN CERTIFICATE-----\n" . chunk_split(str_replace(array(' ',"\n",'&#13;') ,array('','',''),$certificate),64) . "-----END CERTIFICATE-----\n";
			if ($cert_info = openssl_x509_parse( $cert)) {
				$key_info = openssl_pkey_get_details(openssl_pkey_get_public($cert));
				switch ($key_info['type']) {
					case OPENSSL_KEYTYPE_RSA :
						$keyType = 'RSA';
						break;
					case OPENSSL_KEYTYPE_DSA :
						$keyType = 'DSA';
						break;
					case OPENSSL_KEYTYPE_DH :
						$keyType = 'DH';
						break;
					case OPENSSL_KEYTYPE_EC :
						$keyType = 'EC';
						break;
					default :
						$keyType = 'Unknown';
				}
				$subject = '';
				$first = true;
				foreach ($cert_info['subject'] as $key => $value){
					if ($first) {
						$first = false;
						$sep = '';
					} else
						$sep = ', ';
					if (is_array($value)) {
						foreach ($value as $subvalue)
							$subject .= $sep . $key . '=' . $subvalue;
					} else
						$subject .= $sep . $key . '=' . $value;
				}
				$issuer = '';
				$first = true;
				foreach ($cert_info['issuer'] as $key => $value){
					if ($first) {
						$first = false;
						$sep = '';
					} else
						$sep = ', ';
					if (is_array($value)) {
						foreach ($value as $subvalue)
							$issuer .= $sep . $key . '=' . $subvalue;
					} else
						$issuer .= $sep . $key . '=' . $value;
				}

				$Descriptor = 'md:'.$type.'Descriptor';
				$EntityDescriptor = $this->getEntityDescriptor($this->newXml);

				# Find md:SSODescriptor in XML
				$child = $EntityDescriptor->firstChild;
				$SSODescriptor = false;
				while ($child && ! $SSODescriptor) {
					if ($child->nodeName == $Descriptor)
						$SSODescriptor = $child;
					$child = $child->nextSibling;
				}
				if ($SSODescriptor) {
					$child = $SSODescriptor->firstChild;
					$beforeChild = false;
					$xmlOrder = 0;
					while ($child && !$beforeChild) {
						if ($child->nodeName == 'md:Extensions') {
							$child = $child->nextSibling;
						} else {
							$beforeChild = $child;
						}
					}

					$KeyDescriptor = $this->newXml->createElement('md:KeyDescriptor');
					if ($use <> "both") $KeyDescriptor->setAttribute('use', $use);

					if ($beforeChild)
						$SSODescriptor->insertBefore($KeyDescriptor, $beforeChild);
					else
						$SSODescriptor->appendChild($KeyDescriptor);

					$KeyInfo = $this->newXml->createElement('ds:KeyInfo');
					$KeyDescriptor->appendChild($KeyInfo);

					$X509Data = $this->newXml->createElement('ds:X509Data');
					$KeyInfo->appendChild($X509Data);

					$X509Certificate = $this->newXml->createElement('ds:X509Certificate');
					$X509Certificate->nodeValue = $certificate;
					$X509Data->appendChild($X509Certificate);

					$this->saveXML();

					$reorderKeyOrderHandler = $this->metaDb->prepare('UPDATE KeyInfo SET `order` = `order` +1  WHERE entity_id = :Id;');
					$reorderKeyOrderHandler->bindParam(':Id', $this->dbIdNr);
					$reorderKeyOrderHandler->execute();

					$KeyInfoHandler = $this->metaDb->prepare('INSERT INTO KeyInfo (`entity_id`, `type`, `use`, `order`, `name`, `notValidAfter`, `subject`, `issuer`, `bits`, `key_type`, `serialNumber`) VALUES (:Id, :Type, :Use, 0, :Name, :NotValidAfter, :Subject, :Issuer, :Bits, :Key_type, :SerialNumber);');
					$KeyInfoHandler->bindValue(':Id', $this->dbIdNr);
					$KeyInfoHandler->bindValue(':Type', $type);
					$KeyInfoHandler->bindValue(':Use', $use);
					$KeyInfoHandler->bindValue(':Name', '');
					$KeyInfoHandler->bindValue(':NotValidAfter', date('Y-m-d H:i:s', $cert_info['validTo_time_t']));
					$KeyInfoHandler->bindParam(':Subject', $subject);
					$KeyInfoHandler->bindParam(':Issuer', $issuer);
					$KeyInfoHandler->bindParam(':Bits', $key_info['bits']);
					$KeyInfoHandler->bindParam(':Key_type', $keyType);
					$KeyInfoHandler->bindParam(':SerialNumber', $cert_info['serialNumber']);
					$added = $KeyInfoHandler->execute();
				}
			} else {
				print '<div class="row alert alert-danger" role="alert">Error: Invalid Certificate</div>';
			}
		} else {
			$certificate = '';
			$use = '';
		}
		if ($added) {
			$this->editKeyInfo($type);
		} else {
			printf('    <form action="?edit=Add%s&Entity=%d&oldEntity=%d" method="POST" enctype="multipart/form-data">%s      <p><label for="certificate">Certificate:<br><i>Add the part from certificate <b>BETWEN</b> -----BEGIN CERTIFICATE----- and -----END CERTIFICATE----- tags<br>-----BEGIN and -----END should not be included in this form</i></label></p>%s      <textarea id="certificate" name="certificate" rows="10" cols="90" placeholder="MIIGMjCCBRqgAwIBAgISBMpHeMtDoua9sjLy4Rcagh+tMA0GCSqGSIb3DQEBCwUA%sMDIxCzAJBgNVBAYTAlVTMRYwFAYDVQQKEw1MZXQncyBFbmNyeXB0MQswCQYDVQQD%sEwJSMzAeFw0yMjA2MTgxMTI5NDVaFw0yMjA5MTYxMTI5NDRaMCExHzAdBgNVBAMT%sFm1ldGFkYXRhLmxhYi5zd2FtaWQuc2UwggIiMA0GCSqGSIb3DQEBAQUAA4ICDwAw%sggIKAoICAQDED9gxnL+2CVtcwzwTcveVYV4fAQs8KT/wVYPCBFGfxaek9Rl30ZdZ%sHe6HPpFey545PkHwH2RRmHzWCILZrQ692w6kBfgmhl+h1FViWXRJL0/C6HVadj/T%sMvBrS8m6r42oSdp5p3VDmCkHW5ZkHeieVLEEvhjgGwWGXF1BIWxPeiJX5zmQy8HF%sVHnpylWc5T1gkdmuDkNQX4v4nXw7KGl9apyi5ArKy6/J7JeCtsMDsylatfGcaQim%s34ogVeE8MtaHX8LjyjYRKdEZUMQWp9dhD4d2Yp0hAuADV2ybyWbJrc5CPM4C6gof%s......">%s</textarea>%s      <p><label for="use">Type of certificate</label></p>%s      <select id="use" name="use">%s        <option %svalue="encryption">Encryption</option>%s        <option %svalue="signing">Signing</option>%s        <option %svalue="both">Encryption & Signing</option>%s      </select><br>%s      <button type="submit" class="btn btn-primary">Submit</button>%s    </form>', $edit, $this->dbIdNr, $this->dbOldIdNr, "\n", "\n", "\n", "\n", "\n", "\n", "\n", "\n", "\n", "\n", "\n", $certificate, "\n", "\n", "\n", $use == "encryption" ? 'selected ' : '', "\n", $use == "signing" ? 'selected ' : '', "\n", $use == "both" ? 'selected ' : '', "\n", "\n", "\n");
			printf('    <a href="?edit=%s&Entity=%d&oldEntity=%d"><button>Back</button></a>%s', $edit, $this->dbIdNr, $this->dbOldIdNr, "\n");
		}
	}
	private function editKeyInfo($type) {
		$timeNow = date('Y-m-d H:i:00');
		$timeWarn = date('Y-m-d H:i:00', time() + 7776000);  // 90 * 24 * 60 * 60 = 90 days / 3 month

		printf ('%s    <div class="row">%s      <div class="col">', "\n", "\n");
		$edit = $type == 'IDPSSO' ? 'IdPKeyInfo' : 'SPKeyInfo';
		$addLink = sprintf('<a href="?edit=Add%s&Entity=%d&oldEntity=%d"><button>Add new certificate</button></a><br>', $edit, $this->dbIdNr, $this->dbOldIdNr);
		if (isset($_GET['action'])) {
			$error = '';
			if ($_GET['action'] == 'Delete' || $_GET['action'] == 'MoveUp' || $_GET['action'] == 'MoveDown' || $_GET['action'] == 'Change' || $_GET['action'] == 'UpdateUse') {
				if (isset($_GET['use'])) {
					$use = $_GET['use'];
				} else {
					$error .= '<br>Missing use';
				}
				if (isset($_GET['serialNumber'])) {
					$serialNumber = $_GET['serialNumber'];
				} else {
					$error .= '<br>Missing serialNumber';
				}
				if (isset($_GET['order'])) {
					$order = $_GET['order'];
				} else {
					$error .= '<br>Missing order';
				}
			}
			if ($_GET['action'] == 'Change') {
				if (isset($_GET['newUse'])) {
					$newUse = $_GET['newUse'];
				} else {
					$error .= '<br>Missing new use';
				}
			}
			if ($error) {
				printf ('<div class="row alert alert-danger" role="alert">Error:%s</div>', $error);
			} else {
				$changed = false;
				$Descriptor = 'md:'.$type.'Descriptor';
				$EntityDescriptor = $this->getEntityDescriptor($this->newXml);

				# Find md:SSODescriptor in XML
				$child = $EntityDescriptor->firstChild;
				$SSODescriptor = false;
				while ($child && ! $SSODescriptor) {
					if ($child->nodeName == $Descriptor)
						$SSODescriptor = $child;
					$child = $child->nextSibling;
				}
				switch ($_GET['action']) {
					case 'MoveUp' :
						if ($SSODescriptor) {
							$child = $SSODescriptor->firstChild;
							$moveKeyDescriptor = false;
							$xmlOrder = 0;
							$changed = false;
							$previusKeyDescriptor = false;
							while ($child) {
								// Loop thrue all KeyDescriptor:s not just the first one!
								if ($child->nodeName == 'md:KeyDescriptor') {
									$usage = $child->getAttribute('use') ? $child->getAttribute('use') : 'both';
									if ( $usage == $use && $order == $xmlOrder) {
										$KeyDescriptor = $child; // Save to be able to move this KeyDescriptor
										$descriptorChild = $KeyDescriptor->firstChild;
										while ($descriptorChild && !$moveKeyDescriptor) {
											if ($descriptorChild->nodeName == 'ds:KeyInfo') {
												$infoChild = $descriptorChild->firstChild;
												while ($infoChild && !$moveKeyDescriptor) {
													if ($infoChild->nodeName == 'ds:X509Data') {
														$x509Child = $infoChild->firstChild;
														while ($x509Child&& !$moveKeyDescriptor) {
															if ($x509Child->nodeName == 'ds:X509Certificate') {
																$cert = "-----BEGIN CERTIFICATE-----\n" . chunk_split(str_replace(array(' ',"\n") ,array('',''),trim($x509Child->textContent)),64) . "-----END CERTIFICATE-----\n";
																if ($cert_info = openssl_x509_parse( $cert)) {
																	if ($cert_info['serialNumber'] == $serialNumber)
																		$moveKeyDescriptor = true;
																}
															}
															$x509Child = $x509Child->nextSibling;
														}
													}
													$infoChild = $infoChild->nextSibling;
												}
											}
											$descriptorChild = $descriptorChild->nextSibling;
										}
									}
									$xmlOrder ++;
								}
								// Move
								if ($moveKeyDescriptor && $previusKeyDescriptor) {
									$SSODescriptor->insertBefore($KeyDescriptor, $previusKeyDescriptor);

									$reorderKeyOrderHandler = $this->metaDb->prepare('UPDATE KeyInfo SET `order` = :NewOrder WHERE entity_id = :Id AND `order` = :OldOrder;');
									$reorderKeyOrderHandler->bindParam(':Id', $this->dbIdNr);
									#Move key out of way
									$reorderKeyOrderHandler->bindValue(':OldOrder', $order);
									$reorderKeyOrderHandler->bindValue(':NewOrder', 255);
									$reorderKeyOrderHandler->execute();
									# Move previus
									$reorderKeyOrderHandler->bindValue(':OldOrder', $order-1);
									$reorderKeyOrderHandler->bindValue(':NewOrder', $order);
									$reorderKeyOrderHandler->execute();
									#Move into previus place
									$reorderKeyOrderHandler->bindValue(':OldOrder', 255);
									$reorderKeyOrderHandler->bindValue(':NewOrder', $order-1);
									$reorderKeyOrderHandler->execute();

									// Reset flag for next KeyDescriptor
									$child = false;
									$changed = true;
								} else {
									$previusKeyDescriptor = $child;
									$child = $child->nextSibling;
								}
							}
						}
						break;
					case 'MoveDown' :
						if ($SSODescriptor) {
							$child = $SSODescriptor->firstChild;
							$moveKeyDescriptor = false;
							$xmlOrder = 0;
							$changed = false;
							while ($child && !$changed) {
								// Loop thrue all KeyDescriptor:s not just the first one!
								if ($child->nodeName == 'md:KeyDescriptor') {
									// Move if found in previus round
									if ($moveKeyDescriptor) {
										$SSODescriptor->insertBefore($child, $KeyDescriptor);

										$reorderKeyOrderHandler = $this->metaDb->prepare('UPDATE KeyInfo SET `order` = :NewOrder WHERE entity_id = :Id AND `order` = :OldOrder;');
										$reorderKeyOrderHandler->bindParam(':Id', $this->dbIdNr);
										#Move key out of way
										$reorderKeyOrderHandler->bindValue(':OldOrder', $order);
										$reorderKeyOrderHandler->bindValue(':NewOrder', 255);
										$reorderKeyOrderHandler->execute();
										# Move previus
										$reorderKeyOrderHandler->bindValue(':OldOrder', $order+1);
										$reorderKeyOrderHandler->bindValue(':NewOrder', $order);
										$reorderKeyOrderHandler->execute();
										#Move into previus place
										$reorderKeyOrderHandler->bindValue(':OldOrder', 255);
										$reorderKeyOrderHandler->bindValue(':NewOrder', $order+1);
										$reorderKeyOrderHandler->execute();

										// Reset flag for next KeyDescriptor
										$changed = true;
									} else {
										$usage = $child->getAttribute('use') ? $child->getAttribute('use') : 'both';
										if ( $usage == $use && $order == $xmlOrder) {
											$KeyDescriptor = $child; // Save to be able to move this KeyDescriptor
											$descriptorChild = $KeyDescriptor->firstChild;
											while ($descriptorChild && !$moveKeyDescriptor) {
												if ($descriptorChild->nodeName == 'ds:KeyInfo') {
													$infoChild = $descriptorChild->firstChild;
													while ($infoChild && !$moveKeyDescriptor) {
														if ($infoChild->nodeName == 'ds:X509Data') {
															$x509Child = $infoChild->firstChild;
															while ($x509Child&& !$moveKeyDescriptor) {
																if ($x509Child->nodeName == 'ds:X509Certificate') {
																	$cert = "-----BEGIN CERTIFICATE-----\n" . chunk_split(str_replace(array(' ',"\n") ,array('',''),trim($x509Child->textContent)),64) . "-----END CERTIFICATE-----\n";
																	if ($cert_info = openssl_x509_parse( $cert)) {
																		if ($cert_info['serialNumber'] == $serialNumber)
																			$moveKeyDescriptor = true;
																	}
																}
																$x509Child = $x509Child->nextSibling;
															}
														}
														$infoChild = $infoChild->nextSibling;
													}
												}
												$descriptorChild = $descriptorChild->nextSibling;
											}
										}
										$xmlOrder ++;
									}
								}
								$child = $child->nextSibling;
							}
						}
						break;
					case 'Delete' :
						if ($SSODescriptor) {
							$child = $SSODescriptor->firstChild;
							$removeKeyDescriptor = false;
							$xmlOrder = 0;
							$changed = false;
							while ($child) {
								// Loop thrue all KeyDescriptor:s not just the first one!
								if ($child->nodeName == 'md:KeyDescriptor') {
									$usage = $child->getAttribute('use') ? $child->getAttribute('use') : 'both';
									if ( $usage == $use && $order == $xmlOrder) {
										$KeyDescriptor = $child; // Save to be able to remove this KeyDescriptor
										$descriptorChild = $KeyDescriptor->firstChild;
										while ($descriptorChild && !$removeKeyDescriptor) {
											if ($descriptorChild->nodeName == 'ds:KeyInfo') {
												$infoChild = $descriptorChild->firstChild;
												while ($infoChild && !$removeKeyDescriptor) {
													if ($infoChild->nodeName == 'ds:X509Data') {
														$x509Child = $infoChild->firstChild;
														while ($x509Child&& !$removeKeyDescriptor) {
															if ($x509Child->nodeName == 'ds:X509Certificate') {
																$cert = "-----BEGIN CERTIFICATE-----\n" . chunk_split(str_replace(array(' ',"\n") ,array('',''),trim($x509Child->textContent)),64) . "-----END CERTIFICATE-----\n";
																if ($cert_info = openssl_x509_parse( $cert)) {
																	if ($cert_info['serialNumber'] == $serialNumber)
																		$removeKeyDescriptor = true;
																}
															}
															$x509Child = $x509Child->nextSibling;
														}
													}
													$infoChild = $infoChild->nextSibling;
												}
											}
											$descriptorChild = $descriptorChild->nextSibling;
										}
									}
									$xmlOrder ++;
								}
								$child = $child->nextSibling;
								// Remove
								if ($removeKeyDescriptor) {

									$SSODescriptor->removeChild($KeyDescriptor);
									$keyInfoDeleteHandler = $this->metaDb->prepare('DELETE FROM KeyInfo WHERE entity_id = :Id AND `type` = :Type AND `use` = :Use AND `serialNumber` = :SerialNumber ORDER BY `order` LIMIT 1;');
									$keyInfoDeleteHandler->bindParam(':Id', $this->dbIdNr);
									$keyInfoDeleteHandler->bindParam(':Type', $type);
									$keyInfoDeleteHandler->bindParam(':Use', $use);
									$keyInfoDeleteHandler->bindParam(':SerialNumber', $serialNumber);
									$keyInfoDeleteHandler->execute();

									$reorderKeyOrderHandler = $this->metaDb->prepare('UPDATE KeyInfo SET `order` = `order` -1  WHERE entity_id = :Id AND `order` > :Order;');
									$reorderKeyOrderHandler->bindParam(':Id', $this->dbIdNr);
									$reorderKeyOrderHandler->bindParam(':Order', $order);
									$reorderKeyOrderHandler->execute();

									// Reset flag for next KeyDescriptor
									$child = false;
									$changed = true;
								}
							}
						}
						break;
					case 'Change' :
						if ($SSODescriptor) {
							$child = $SSODescriptor->firstChild;
							$changeKeyDescriptor = false;
							$xmlOrder = 0;
							$changed = false;
							while ($child) {
								// Loop thrue all KeyDescriptor:s not just the first one!
								if ($child->nodeName == 'md:KeyDescriptor') {
									$usage = $child->getAttribute('use') ? $child->getAttribute('use') : 'both';
									if ( $usage == $use && $order == $xmlOrder) {
										$KeyDescriptor = $child; // Save to be able to update this KeyDescriptor
										$descriptorChild = $KeyDescriptor->firstChild;
										while ($descriptorChild && !$changeKeyDescriptor) {
											if ($descriptorChild->nodeName == 'ds:KeyInfo') {
												$infoChild = $descriptorChild->firstChild;
												while ($infoChild && !$changeKeyDescriptor) {
													if ($infoChild->nodeName == 'ds:X509Data') {
														$x509Child = $infoChild->firstChild;
														while ($x509Child&& !$changeKeyDescriptor) {
															if ($x509Child->nodeName == 'ds:X509Certificate') {
																$cert = "-----BEGIN CERTIFICATE-----\n" . chunk_split(str_replace(array(' ',"\n") ,array('',''),trim($x509Child->textContent)),64) . "-----END CERTIFICATE-----\n";
																if ($cert_info = openssl_x509_parse( $cert)) {
																	if ($cert_info['serialNumber'] == $serialNumber)
																		$changeKeyDescriptor = true;
																}
															}
															$x509Child = $x509Child->nextSibling;
														}
													}
													$infoChild = $infoChild->nextSibling;
												}
											}
											$descriptorChild = $descriptorChild->nextSibling;
										}
									}
									$xmlOrder ++;
								}
								$child = $child->nextSibling;
								// Change ?
								if ($changeKeyDescriptor) {
									if ($newUse == "encryption & signing") {
										$KeyDescriptor->removeAttribute('use');
										$newUse = 'both';
									} else {
										$KeyDescriptor->setAttribute('use', $newUse);
									}
									$keyInfoUpdateHandler = $this->metaDb->prepare('UPDATE KeyInfo SET `use` = :NewUse WHERE entity_id = :Id AND `type` = :Type AND `use` = :Use AND `serialNumber` = :SerialNumber AND `order` = :Order;');
									$keyInfoUpdateHandler->bindParam(':NewUse', $newUse);
									$keyInfoUpdateHandler->bindParam(':Id', $this->dbIdNr);
									$keyInfoUpdateHandler->bindParam(':Type', $type);
									$keyInfoUpdateHandler->bindParam(':Use', $use);
									$keyInfoUpdateHandler->bindParam(':SerialNumber', $serialNumber);
									$keyInfoUpdateHandler->bindParam(':Order', $order);
									$keyInfoUpdateHandler->execute();

									// Reset flag for next KeyDescriptor
									$child = false;
									$changed = true;
								}
							}
						}
						break;
				}
				if ($changed) {
					$this->saveXML();
				}
			}
		}

		$KeyInfoStatusHandler = $this->metaDb->prepare('SELECT `use`, `order`, `notValidAfter` FROM KeyInfo WHERE entity_id = :Id AND type = :Type ORDER BY `order`');
		$KeyInfoStatusHandler->bindParam(':Type', $type);
		$KeyInfoStatusHandler->bindParam(':Id', $this->dbIdNr);
		$KeyInfoStatusHandler->execute();
		$encryptionFound = false;
		$signingFound = false;
		$extraEncryptionFound = false;
		$extraSigningFound = false;
		$validEncryptionFound = false;
		$validSigningFound = false;
		$maxOrder = 0;
		while ($keyInfoStatus = $KeyInfoStatusHandler->fetch(PDO::FETCH_ASSOC)) {
			switch ($keyInfoStatus['use']) {
				case 'encryption' :
					$extraEncryptionFound = $encryptionFound;
					$encryptionFound = true;
					if ($keyInfoStatus['notValidAfter'] > $timeNow ) {
						$validEncryptionFound = true;
					}
					break;
				case 'signing' :
					$extraSigningFound = $signingFound;
					$signingFound = true;
					if ($keyInfoStatus['notValidAfter'] > $timeNow ) {
						$validSigningFound = true;
					}
					break;
				case 'both' :
					$extraEncryptionFound = $encryptionFound;
					$extraSigningFound = $signingFound;
					$encryptionFound = true;
					$signingFound = true;
					if ($keyInfoStatus['notValidAfter'] > $timeNow ) {
						$validEncryptionFound = true;
						$validSigningFound = true;
					}
					break;
			}
			$maxOrder = $keyInfoStatus['order'];
		}
		$keyInfoHandler = $this->metaDb->prepare('SELECT `use`, `order`, `name`, `notValidAfter`, `subject`, `issuer`, `bits`, `key_type`, `serialNumber` FROM KeyInfo WHERE `entity_id` = :Id AND `type` = :Type ORDER BY `order`;');
		$keyInfoHandler->bindParam(':Type', $type);
		$oldKeyInfos = array();
		if ($this->oldExists) {
			$keyInfoHandler->bindParam(':Id', $this->dbOldIdNr);
			$keyInfoHandler->execute();

			while ($keyInfo = $keyInfoHandler->fetch(PDO::FETCH_ASSOC)) {
				$oldKeyInfos[$keyInfo['serialNumber']][$keyInfo['use']] = 'removed';
			}
		}

		$keyInfoHandler->bindParam(':Id', $this->dbIdNr);
		$keyInfoHandler->execute();
		while ($keyInfo = $keyInfoHandler->fetch(PDO::FETCH_ASSOC)) {
			$okRemove = false;
			$error = '';
			$validCertExists = false;
			switch ($keyInfo['use']) {
				case 'encryption' :
					$use = 'encryption';
					$okRemove = $extraEncryptionFound;
					if ($keyInfo['notValidAfter'] <= $timeNow && $validEncryptionFound) {
						$validCertExists = true;
					}
					break;
				case 'signing' :
					$use = 'signing';
					$okRemove = $extraSigningFound;
					if ($keyInfo['notValidAfter'] <= $timeNow && $validSigningFound) {
						$validCertExists = true;
					}
					break;
				case 'both' :
					$use = 'encryption & signing';
					$okRemove = ($extraEncryptionFound && $extraSigningFound);
					if ($keyInfo['notValidAfter'] <= $timeNow && $validEncryptionFound &&  $validSigningFound) {
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

			if (isset($oldKeyInfos[$keyInfo['serialNumber']][$keyInfo['use']])) {
				$state = 'dark';
				$oldKeyInfos[$keyInfo['serialNumber']][$keyInfo['use']] = 'same';
			} else {
				$state = 'success';
			}
			$baseLink = sprintf('%s        <a href="?edit=%s&Entity=%d&oldEntity=%d&type=%s&use=%s&serialNumber=%s&order=%d&action=', "\n", $edit, $this->dbIdNr, $this->dbOldIdNr, $type, $keyInfo['use'], $keyInfo['serialNumber'], $keyInfo['order']);
			$links = $baseLink . 'UpdateUse"><i class="fas fa-pencil-alt"></i></a> ';
			$links .= $okRemove ? sprintf('%sDelete"><i class="fas fa-trash"></i></a> ', $baseLink) : '';
			$links .= $keyInfo['order'] > 0 ? sprintf('%sMoveUp"><i class="fas fa-arrow-up"></i></a> ', $baseLink) : '';
			$links .= $keyInfo['order'] < $maxOrder ? sprintf('%sMoveDown"><i class="fas fa-arrow-down"></i></a> ', $baseLink) : '';

			if (isset($_GET['action']) && $_GET['action'] == 'UpdateUse' && $keyInfo['order'] == $order) {
				$useLink = sprintf ('%s          <form>%s            <input type="hidden" name="edit" value="%s">%s            <input type="hidden" name="Entity" value="%d">%s            <input type="hidden" name="oldEntity" value="%d">%s            <input type="hidden" name="type" value="%s">%s            <input type="hidden" name="use" value="%s">%s            <input type="hidden" name="serialNumber" value="%s">%s            <input type="hidden" name="order" value="%d">%s            <b>KeyUse = <select name="newUse">', "\n", "\n", $edit, "\n", $this->dbIdNr, "\n", $this->dbOldIdNr, "\n", $type, "\n", $keyInfo['use'], "\n", $keyInfo['serialNumber'], "\n", $keyInfo['order'], "\n");
				$useLink .= sprintf ('%s              <option value="encryption"%s>encryption</option>%s              <option value="signing"%s>signing</option>%s              <option value="encryption & signing"%s>encryption & signing</option>', "\n", $use == 'encryption' ? ' selected' : '', "\n", $use == 'signing' ? ' selected' : '', "\n", $use == 'encryption & signing' ? ' selected' : '');
                $useLink .= sprintf ('%s            </select></b>%s            <input type="submit" name="action" value="Change">%s          </form>%s       ', "\n", "\n", "\n", "\n");
			} else {
				$useLink = sprintf ('<b>KeyUse = "%s"</b>', $use);
			}

			printf('%s%s        <span class="text-%s text-truncate">%s %s</span>
        <ul%s>
          <li>notValidAfter = %s</li>
          <li>Subject = %s</li>
          <li>Issuer = %s</li>
          <li>Type / bits = %s / %d</li>
          <li>Serial Number = %s</li>
        </ul>', $links, "\n", $state, $useLink, $name, $error, $keyInfo['notValidAfter'], $keyInfo['subject'], $keyInfo['issuer'], $keyInfo['key_type'], $keyInfo['bits'], $keyInfo['serialNumber']);
		}

		printf('%s        %s%s        <a href="./?validateEntity=%d"><button>Back</button></a>%s      </div><!-- end col -->%s      <div class="col">', "\n", $addLink, "\n", $this->dbIdNr, "\n", "\n");
		if ($this->oldExists) {
			$keyInfoHandler->bindParam(':Id', $this->dbOldIdNr);
			$keyInfoHandler->execute();

			while ($keyInfo = $keyInfoHandler->fetch(PDO::FETCH_ASSOC)) {
				switch ($keyInfo['use']) {
					case 'encryption' :
						$use = 'encryption';
						break;
					case 'signing' :
						$use = 'signing';
						break;
					case 'both' :
						$use = 'encryption & signing';
						break;
				}
				$name = $keyInfo['name'] == '' ? '' : '(' . $keyInfo['name'] .')';
				$state = $oldKeyInfos[$keyInfo['serialNumber']][$keyInfo['use']] == "same" ? 'dark' : 'danger';
				printf('%s        <span class="text-%s text-truncate"><b>KeyUse = "%s"</b> %s</span>
        <ul>
          <li>notValidAfter = %s</li>
          <li>Subject = %s</li>
          <li>Issuer = %s</li>
          <li>Type / bits = %s / %d</li>
          <li>Serial Number = %s</li>
        </ul>', "\n", $state, $use, $name, $keyInfo['notValidAfter'], $keyInfo['subject'], $keyInfo['issuer'], $keyInfo['key_type'], $keyInfo['bits'], $keyInfo['serialNumber']);
			}
		}
		print "\n      </div><!-- end col -->\n    </div><!-- end row -->\n";
	}
	private function editAttributeConsumingService() {
		if (isset($_GET['action'])) {
			$name = '';
			$friendlyName = '';
			$nameFormat = '';
			$isRequired = '';
			$langvalue = '';
			$value = '';
			$elementmd = 'md:';
			$elementValue = '';
			$error = '';
			if ($_GET['action'] == 'AddIndex') {
				$nextServiceIndexHandler = $this->metaDb->prepare('SELECT MAX(Service_index) AS lastIndex FROM AttributeConsumingService WHERE entity_id = :Id;');
				$nextServiceIndexHandler->bindParam(':Id', $this->dbIdNr);

				$nextServiceIndexHandler->execute();
				if ($serviceIndex = $nextServiceIndexHandler->fetch(PDO::FETCH_ASSOC)) {
					$indexValue = $serviceIndex['lastIndex']+1;
				} else {
					$indexValue = 1;
				}
			} elseif ($_GET['action'] == 'SetIndex' || $_GET['action'] == 'DeleteIndex' ) {
				if (isset($_GET['index']) && $_GET['index'] > -1) {
					$indexValue = $_GET['index'];
				} else {
					$error .= '<br>No Index';
					$indexValue = -1;
				}
			} else {
				if (isset($_GET['index']) && $_GET['index'] > -1) {
					$indexValue = $_GET['index'];
				} else {
					$error .= '<br>No Index';
					$indexValue = -1;
				}
				if (isset($_GET['element']) && trim($_GET['element']) != '') {
					$elementValue = trim($_GET['element']);
					$elementmd = 'md:'.$elementValue;
				} else {
					$error .= '<br>No Element selected';
				}
				if (isset($this->orderAttributeRequestingService[$elementmd])) {
					$placement = $this->orderAttributeRequestingService[$elementmd];
				} else {
					$error .= '<br>Unknown element selected';
				}
				if ($placement < 3 ) {
					if (isset($_GET['lang']) && trim($_GET['lang']) != '') {
						$langvalue = strtolower(trim($_GET['lang']));
					} else {
						$error .= $_GET['action'] == "Add" ? '<br>Lang is empty' : '';
					}
					if (isset($_GET['value']) && trim($_GET['value']) != '') {
						$value = trim($_GET['value']);
					} else {
						$error .= $_GET['action'] == "Add" ? '<br>Value is empty' : '';
					}
				} else {
					if (isset($_GET['name']) && trim($_GET['name']) != '') {
						$name = trim($_GET['name']);
					} else {
						$error .= '<br>Name is empty';
					}
					if (isset($_GET['friendlyName']) && trim($_GET['friendlyName']) != '') {
						$friendlyName = trim($_GET['friendlyName']);
					} else {
						if (isset($this->FriendlyNames[$name])) {
							$friendlyName = $this->FriendlyNames[$name]['desc'];
						}
					}
					if (isset($_GET['NameFormat']) && trim($_GET['NameFormat']) != '') {
						$nameFormat = trim($_GET['NameFormat']);
					} else {
						$error .= $_GET['action'] == "Add" ? $error .= '<br>NameFormat is empty' : '';
					}
					$isRequired = isset($_GET['isRequired']) && ($_GET['isRequired'] == 1 || strtolower($_GET['isRequired']) == 'true') ? 1 : 0;
				}
			}
			if ($error) {
				printf ('<div class="row alert alert-danger" role="alert">Error:%s</div>', $error);
			} else {
				$changed = false;
				$EntityDescriptor = $this->getEntityDescriptor($this->newXml);

				# Find md:IDPSSODescriptor in XML
				$child = $EntityDescriptor->firstChild;
				$SSODescriptor = false;
				while ($child && ! $SSODescriptor) {
					if ($child->nodeName == 'md:SPSSODescriptor')
						$SSODescriptor = $child;
					$child = $child->nextSibling;
				}
				switch ($_GET['action']) {
					case 'AddIndex' :
						if ($SSODescriptor) {
							$changed = true;
							$child = $SSODescriptor->firstChild;
							$AttributeConsumingService = false;
							while ($child && ! $AttributeConsumingService) {
								switch ($child->nodeName) {
									case 'md:Signature' :
									case 'md:Extensions' :
									case 'md:KeyDescriptor' :
									case 'md:ArtifactResolutionService' :
									case 'md:SingleLogoutService' :
									case 'md:ManageNameIDService' :
									case 'md:NameIDFormat' :
									case 'md:AssertionConsumerService' :
										break;
									case 'md:AttributeConsumingService' :
										if ($child->getAttribute('index') == $indexValue)
											$AttributeConsumingService = $child;
										break;
								}
								$child = $child->nextSibling;
							}
							if (! $AttributeConsumingService) {
								$AttributeConsumingService = $this->newXml->createElement('md:AttributeConsumingService');
								$AttributeConsumingService->setAttribute('index', $indexValue);
								$SSODescriptor->appendChild($AttributeConsumingService);

								$addServiceIndexHandler = $this->metaDb->prepare('INSERT INTO AttributeConsumingService (entity_id, Service_index) VALUES (:Id, :Index);');
								$serviceElementAddHandler = $this->metaDb->prepare('INSERT INTO AttributeConsumingService_Service (entity_id, Service_index, element, lang, data) VALUES ( :Id, :Index, :Element, :Lang, :Data );');
								$mduiHandler = $this->metaDb->prepare("SELECT lang, data FROM Mdui WHERE entity_id = :Id AND element = 'DisplayName' AND type = 'SPSSO' ORDER BY lang;");

								$addServiceIndexHandler->bindParam(':Id', $this->dbIdNr);
								$addServiceIndexHandler->bindParam(':Index', $indexValue);
								$addServiceIndexHandler->execute();

								$serviceElementAddHandler->bindParam(':Id', $this->dbIdNr);
								$serviceElementAddHandler->bindParam(':Index', $indexValue);
								$serviceElementAddHandler->bindValue(':Element', 'ServiceName');

								$mduiHandler->bindParam(':Id', $this->dbIdNr);
								$mduiHandler->execute();
								while ($mdui = $mduiHandler->fetch(PDO::FETCH_ASSOC)) {
									$AttributeConsumingServiceElement = $this->newXml->createElement('md:ServiceName', $mdui['data']);
									$AttributeConsumingServiceElement->setAttribute('xml:lang', $mdui['lang']);
									$AttributeConsumingService->appendChild($AttributeConsumingServiceElement);

									$serviceElementAddHandler->bindParam(':Lang', $mdui['lang']);
									$serviceElementAddHandler->bindParam(':Data', $mdui['data']);
									$serviceElementAddHandler->execute();
								}
							}
						}
						break;
					case 'DeleteIndex' :
						if ($SSODescriptor) {
							$child = $SSODescriptor->firstChild;
							$AttributeConsumingService = false;
							while ($child && ! $AttributeConsumingService) {
								if ($child->nodeName == 'md:AttributeConsumingService' && $child->getAttribute('index') == $indexValue)
									$AttributeConsumingService = $child;
								$child = $child->nextSibling;
							}
							if ($AttributeConsumingService) {
								$changed = true;
								$SSODescriptor->removeChild($AttributeConsumingService);
								$serviceRemoveHandler = $this->metaDb->prepare('DELETE FROM AttributeConsumingService WHERE entity_id = :Id AND Service_index = :Index;');
								$serviceRemoveHandler->bindParam(':Id', $this->dbIdNr);
								$serviceRemoveHandler->bindParam(':Index', $indexValue);
								$serviceRemoveHandler->execute();

								$serviceElementRemoveHandler = $this->metaDb->prepare('DELETE FROM AttributeConsumingService_Service WHERE entity_id = :Id AND Service_index = :Index;');
								$serviceElementRemoveHandler->bindParam(':Id', $this->dbIdNr);
								$serviceElementRemoveHandler->bindParam(':Index', $indexValue);
								$serviceElementRemoveHandler->execute();

								$requestedAttributeRemoveHandler = $this->metaDb->prepare('DELETE FROM AttributeConsumingService_RequestedAttribute WHERE entity_id = :Id AND Service_index = :Index;');
								$requestedAttributeRemoveHandler->bindParam(':Id', $this->dbIdNr);
								$requestedAttributeRemoveHandler->bindParam(':Index', $indexValue);
								$requestedAttributeRemoveHandler->execute();
							}
						}
						break;
					case 'Add' :
						if ($SSODescriptor) {
							$changed = true;
							$child = $SSODescriptor->firstChild;
							$AttributeConsumingService = false;
							while ($child && ! $AttributeConsumingService) {
								switch ($child->nodeName) {
									case 'md:Signature' :
									case 'md:Extensions' :
									case 'md:KeyDescriptor' :
									case 'md:ArtifactResolutionService' :
									case 'md:SingleLogoutService' :
									case 'md:ManageNameIDService' :
									case 'md:NameIDFormat' :
									case 'md:AssertionConsumerService' :
										break;
									case 'md:AttributeConsumingService' :
										if ($child->getAttribute('index') == $indexValue)
											$AttributeConsumingService = $child;
										break;
								}
								$child = $child->nextSibling;
							}
							if (! $AttributeConsumingService) {
								$AttributeConsumingService = $this->newXml->createElement('md:AttributeConsumingService');
								$AttributeConsumingService->setAttribute('index', $indexValue);
								$SSODescriptor->appendChild($AttributeConsumingService);
								$addServiceIndexHandler = $this->metaDb->prepare('INSERT INTO AttributeConsumingService (entity_id, Service_index) VALUES (:Id, :Index);');
								$addServiceIndexHandler->bindParam(':Id', $this->dbIdNr);
								$addServiceIndexHandler->bindParam(':Index', $indexValue);
								$addServiceIndexHandler->execute();
							}
							$child = $AttributeConsumingService->firstChild;
							$AttributeConsumingServiceElement = false;
							$update = false;
							while ($child && ! $AttributeConsumingServiceElement) {
								if ($placement == 3 && $child->nodeName == $elementmd && $child->getAttribute('Name') == $name) {
									$AttributeConsumingServiceElement = $child;
									$update = true;
								} elseif ($placement != 3 && $child->nodeName == $elementmd && $child->getAttribute('xml:lang') == $langvalue ) {
									$AttributeConsumingServiceElement = $child;
									$update = true;
								} elseif (isset ($this->orderAttributeRequestingService[$child->nodeName]) && $this->orderAttributeRequestingService[$child->nodeName] <= $placement) {
									$child = $child->nextSibling;
								} else {
									if ($placement < 3 ) {
										$AttributeConsumingServiceElement = $this->newXml->createElement($elementmd, $value);
									} else {
										$AttributeConsumingServiceElement = $this->newXml->createElement($elementmd);
									}
									$AttributeConsumingService->insertBefore($AttributeConsumingServiceElement, $child);
								}
							}
							if (!$AttributeConsumingServiceElement) {
								if ($placement < 3 ) {
									$AttributeConsumingServiceElement = $this->newXml->createElement($elementmd, $value);
								} else {
									$AttributeConsumingServiceElement = $this->newXml->createElement($elementmd);
								}
								$AttributeConsumingService->appendChild($AttributeConsumingServiceElement);
							}
							if ($update) {
								if ($placement < 3 ) {
									$AttributeConsumingServiceElement->setAttribute('xml:lang', $langvalue);
									$AttributeConsumingServiceElement->nodeValue = $value;
									$serviceElementUpdateHandler = $this->metaDb->prepare('UPDATE AttributeConsumingService_Service SET data = :Data WHERE entity_id = :Id AND Service_index = :Index AND element = :Element AND lang = :Lang;');
									$serviceElementUpdateHandler->bindParam(':Id', $this->dbIdNr);
									$serviceElementUpdateHandler->bindParam(':Index', $indexValue);
									$serviceElementUpdateHandler->bindParam(':Element', $elementValue);
									$serviceElementUpdateHandler->bindParam(':Lang', $langvalue);
									$serviceElementUpdateHandler->bindParam(':Data', $value);
									$serviceElementUpdateHandler->execute();
								} else {
									if ($friendlyName != '' )
										$AttributeConsumingServiceElement->setAttribute('FriendlyName', $friendlyName);
									$AttributeConsumingServiceElement->setAttribute('Name', $name);
									if ($nameFormat != '' )
										$AttributeConsumingServiceElement->setAttribute('NameFormat', $nameFormat);
									$AttributeConsumingServiceElement->setAttribute('isRequired', $isRequired ? 'true' : 'false');
									$requestedAttributeUpdateHandler = $this->metaDb->prepare('UPDATE AttributeConsumingService_RequestedAttribute SET FriendlyName = :FriendlyName, NameFormat = :NameFormat, isRequired = :IsRequired WHERE entity_id = :Id AND Service_index = :Index AND Name = :Name;');
									$requestedAttributeUpdateHandler->bindParam(':Id', $this->dbIdNr);
									$requestedAttributeUpdateHandler->bindParam(':Index', $indexValue);
									$requestedAttributeUpdateHandler->bindParam(':FriendlyName', $friendlyName);
									$requestedAttributeUpdateHandler->bindParam(':Name', $name);
									$requestedAttributeUpdateHandler->bindParam(':NameFormat', $nameFormat);
									$requestedAttributeUpdateHandler->bindParam(':IsRequired', $isRequired);
									$requestedAttributeUpdateHandler->execute();
								}
							} else {
								# Added NEW, Insert into DB
								if ($placement < 3 ) {
									$AttributeConsumingServiceElement->setAttribute('xml:lang', $langvalue);
									$serviceElementAddHandler = $this->metaDb->prepare('INSERT INTO AttributeConsumingService_Service (entity_id, Service_index, element, lang, data) VALUES ( :Id, :Index, :Element, :Lang, :Data );');
									$serviceElementAddHandler->bindParam(':Id', $this->dbIdNr);
									$serviceElementAddHandler->bindParam(':Index', $indexValue);
									$serviceElementAddHandler->bindParam(':Element', $elementValue);
									$serviceElementAddHandler->bindParam(':Lang', $langvalue);
									$serviceElementAddHandler->bindParam(':Data', $value);
									$serviceElementAddHandler->execute();
								} else {
									if ($friendlyName != '' )
										$AttributeConsumingServiceElement->setAttribute('FriendlyName', $friendlyName);
									$AttributeConsumingServiceElement->setAttribute('Name', $name);
									if ($nameFormat != '' )
										$AttributeConsumingServiceElement->setAttribute('NameFormat', $nameFormat);
									$AttributeConsumingServiceElement->setAttribute('isRequired', $isRequired ? 'true' : 'false');
									$requestedAttributeAddHandler = $this->metaDb->prepare('INSERT INTO AttributeConsumingService_RequestedAttribute (entity_id, Service_index, FriendlyName, Name, NameFormat, isRequired) VALUES ( :Id, :Index, :FriendlyName, :Name, :NameFormat, :IsRequired);');
									$requestedAttributeAddHandler->bindParam(':Id', $this->dbIdNr);
									$requestedAttributeAddHandler->bindParam(':Index', $indexValue);
									$requestedAttributeAddHandler->bindParam(':FriendlyName', $friendlyName);
									$requestedAttributeAddHandler->bindParam(':Name', $name);
									$requestedAttributeAddHandler->bindParam(':NameFormat', $nameFormat);
									$requestedAttributeAddHandler->bindParam(':IsRequired', $isRequired);
									$requestedAttributeAddHandler->execute();
								}
							}
						}
						break;
					case 'Delete' :
						if ($SSODescriptor) {
							$child = $SSODescriptor->firstChild;
							$AttributeConsumingService = false;
							while ($child && ! $AttributeConsumingService) {
								if ($child->nodeName == 'md:AttributeConsumingService' && $child->getAttribute('index') == $indexValue)
									$AttributeConsumingService = $child;
								$child = $child->nextSibling;
							}
							if ($AttributeConsumingService) {
								$child = $AttributeConsumingService->firstChild;
								$AttributeConsumingServiceElement = false;
								$moreElements = false;
								while ($child && ! $AttributeConsumingServiceElement) {
									if ($placement == 3 && $child->nodeName == $elementmd && $child->getAttribute('Name') == $name) {
										$AttributeConsumingServiceElement = $child;
									} elseif ($placement != 3 && $child->nodeName == $elementmd && $child->getAttribute('xml:lang') == $langvalue ) {
										$AttributeConsumingServiceElement = $child;
									} else {
										$moreElements = true;
									}
									$child = $child->nextSibling;
								}
								$moreElements = $moreElements ? true : $child;
								if ($AttributeConsumingServiceElement) {
									# Remove Node
									$changed = true;
									$AttributeConsumingService->removeChild($AttributeConsumingServiceElement);
									if (! $moreElements) {
										$SSODescriptor->removeChild($AttributeConsumingService);
										$serviceRemoveHandler = $this->metaDb->prepare('DELETE FROM AttributeConsumingService WHERE entity_id = :Id AND Service_index = :Index;');
										$serviceRemoveHandler->bindParam(':Id', $this->dbIdNr);
										$serviceRemoveHandler->bindParam(':Index', $indexValue);
										$serviceRemoveHandler->execute();
									}
									if ($placement < 3 ) {
										$serviceElementRemoveHandler = $this->metaDb->prepare('DELETE FROM AttributeConsumingService_Service WHERE entity_id = :Id AND Service_index = :Index AND element = :Element AND lang = :Lang;');
										$serviceElementRemoveHandler->bindParam(':Id', $this->dbIdNr);
										$serviceElementRemoveHandler->bindParam(':Index', $indexValue);
										$serviceElementRemoveHandler->bindParam(':Element', $elementValue);
										$serviceElementRemoveHandler->bindParam(':Lang', $langvalue);
										$serviceElementRemoveHandler->execute();
									} else {
										$requestedAttributeRemoveHandler = $this->metaDb->prepare('DELETE FROM AttributeConsumingService_RequestedAttribute WHERE entity_id = :Id AND Service_index = :Index AND Name = :Name;');
										$requestedAttributeRemoveHandler->bindParam(':Id', $this->dbIdNr);
										$requestedAttributeRemoveHandler->bindParam(':Index', $indexValue);
										$requestedAttributeRemoveHandler->bindParam(':Name', $name);
										$requestedAttributeRemoveHandler->execute();
									}
								}
							}
						}
						$elementValue = '';
						$langvalue = '';
						$value = '';
						$name = '';
						$friendlyName = '';
						$nameFormat = '';
						$isRequired = '';
						break;
				}
				if ($changed) {
					$this->saveXML();
				}
			}
		} else {
			$indexValue = 0;
			$elementValue = '';
			$langvalue = '';
			$value = '';
			$name = '';
			$friendlyName = '';
			$nameFormat = '';
			$isRequired = '';
		}

		$serviceIndexHandler = $this->metaDb->prepare('SELECT Service_index FROM AttributeConsumingService WHERE entity_id = :Id ORDER BY Service_index;');
		$serviceElementHandler = $this->metaDb->prepare('SELECT element, lang, data FROM AttributeConsumingService_Service WHERE entity_id = :Id AND Service_index = :Index ORDER BY element DESC, lang;');
		$serviceElementHandler->bindParam(':Index', $index);
		$requestedAttributeHandler = $this->metaDb->prepare('SELECT FriendlyName, Name, NameFormat, isRequired FROM AttributeConsumingService_RequestedAttribute WHERE entity_id = :Id AND Service_index = :Index ORDER BY isRequired DESC, FriendlyName;');
		$requestedAttributeHandler->bindParam(':Index', $index);

		$serviceIndexHandler->bindParam(':Id', $this->dbOldIdNr);
		$serviceElementHandler->bindParam(':Id', $this->dbOldIdNr);
		$requestedAttributeHandler->bindParam(':Id', $this->dbOldIdNr);
		$serviceIndexHandler->execute();
		while ($serviceIndex = $serviceIndexHandler->fetch(PDO::FETCH_ASSOC)) {
			$index = $serviceIndex['Service_index'];
			$oldServiceIndexes[$index] = $index;
			$oldServiceElements[$index] = array();
			$oldRequestedAttributes[$index] = array();
			$serviceElementHandler->execute();
			while ($serviceElement = $serviceElementHandler->fetch(PDO::FETCH_ASSOC)) {
				$oldServiceElements[$index][$serviceElement['element']][$serviceElement['lang']] = array('value' => $serviceElement['data'], 'state' => 'removed');
			}
			$requestedAttributeHandler->execute();
			while ($requestedAttribute = $requestedAttributeHandler->fetch(PDO::FETCH_ASSOC)) {
				$oldRequestedAttributes[$index][$requestedAttribute['Name']] = array('isRequired' => $requestedAttribute['isRequired'], 'friendlyName' => $requestedAttribute['FriendlyName'], 'nameFormat' => $requestedAttribute['NameFormat'], 'state' => 'removed');
			}
		}
		printf ('%s    <div class="row">%s      <div class="col">', "\n", "\n");
		$serviceIndexHandler->bindParam(':Id', $this->dbIdNr);
		$serviceElementHandler->bindParam(':Id', $this->dbIdNr);
		$requestedAttributeHandler->bindParam(':Id', $this->dbIdNr);
		$serviceIndexHandler->execute();
		while ($serviceIndex = $serviceIndexHandler->fetch(PDO::FETCH_ASSOC)) {
			$index = $serviceIndex['Service_index'];
			if ($indexValue == $index) {
				printf ('%s        <b>Index = %d</b><a href="./?edit=AttributeConsumingService&Entity=%d&oldEntity=%d&index=%d&action=DeleteIndex"><i class="fa fa-trash"></i></a>%s        <ul>', "\n", $index, $this->dbIdNr, $this->dbOldIdNr, $index, "\n");
			} else {
				printf ('%s        <b>Index = %d</b><a href="./?edit=AttributeConsumingService&Entity=%d&oldEntity=%d&index=%d&action=SetIndex"><i class="fa fa-pencil-alt"></i></a>%s        <ul>', "\n", $index, $this->dbIdNr, $this->dbOldIdNr, $index, "\n");
			}
			$serviceElementHandler->execute();
			while ($serviceElement = $serviceElementHandler->fetch(PDO::FETCH_ASSOC)) {
				if (isset($oldServiceElements[$index][$serviceElement['element']][$serviceElement['lang']]) && $oldServiceElements[$index][$serviceElement['element']][$serviceElement['lang']]['value'] == $serviceElement['data']) {
					$state = 'dark';
					$oldServiceElements[$index][$serviceElement['element']][$serviceElement['lang']]['state'] = 'same';
				} else
					$state = 'success';
				$baseLink = sprintf('<a href ="?edit=AttributeConsumingService&Entity=%d&oldEntity=%d&index=%s&element=%s&lang=%s&value=%s&action=', $this->dbIdNr, $this->dbOldIdNr, $index, $serviceElement['element'], $serviceElement['lang'], urlencode($serviceElement['data']));
				$links = $baseLink . 'Copy"><i class="fas fa-pencil-alt"></i></a> ' . $baseLink . 'Delete"><i class="fas fa-trash"></i></a> ';
				printf('%s          <li>%s<span class="text-%s">%s[%s] = %s</span></li>', "\n", $links, $state, $serviceElement['element'], $serviceElement['lang'], $serviceElement['data']);
			}
			print "\n        </ul>";
			if ($indexValue == $index) {
				printf('
            <form>
              <input type="hidden" name="edit" value="AttributeConsumingService">
              <input type="hidden" name="Entity" value="%d">
              <input type="hidden" name="oldEntity" value="%d">
              <input type="hidden" name="index" value="%d">
              <div class="row">
                <div class="col-2">Element: </div>
                <div class="col">
                  <select name="element">
                    <option value="ServiceName"%s>ServiceName</option>
                    <option value="ServiceDescription"%s>ServiceDescription</option>
                  </select>
                </div>
              </div>
              <div class="row">
                <div class="col-2">Lang: </div>
                <div class="col">', $this->dbIdNr, $this->dbOldIdNr, $index, $elementValue == 'ServiceName' ? ' selected' : '', $elementValue == 'ServiceDescription' ? ' selected' : '');
				$this->showLangSelector($langvalue);
				printf('            </div>
              </div>
              <div class="row">
                <div class="col-2">Value: </div>
                <div class="col"><input type="textbox" size="60" name="value" value="%s"></div>
              </div>
              <button type="submit" name="action" value="Add">Add/Update</button>
            </form>', $value);
			}
			$requestedAttributeHandler->execute();
			printf("\n        <b>RequestedAttributes</b>\n        <ul>");
			while ($requestedAttribute = $requestedAttributeHandler->fetch(PDO::FETCH_ASSOC)) {
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
				if (isset($oldRequestedAttributes[$index][$requestedAttribute['Name']]) && $oldRequestedAttributes[$index][$requestedAttribute['Name']]['isRequired'] == $requestedAttribute['isRequired']) {
					$state = 'dark';
					$oldRequestedAttributes[$index][$requestedAttribute['Name']]['state'] = 'same';
				} else {
					$state = 'success';
				}
				$baseLink = sprintf('<a href ="?edit=AttributeConsumingService&Entity=%d&oldEntity=%d&index=%s&element=RequestedAttribute&name=%s&isRequired=%d&action=', $this->dbIdNr, $this->dbOldIdNr, $index, $requestedAttribute['Name'], $requestedAttribute['isRequired']);
				$links = $baseLink . 'Copy"><i class="fas fa-pencil-alt"></i></a> ' . $baseLink . 'Delete"><i class="fas fa-trash"></i></a> ';
				$existingRequestedAttribute[$requestedAttribute['Name']] = true;
				printf('%s            <li%s>%s<span class="text-%s"><b>%s</b> - %s%s</span></li>', "\n", $error, $links, $state, $FriendlyNameDisplay, $requestedAttribute['Name'], $requestedAttribute['isRequired'] == '1' ? ' (Required)' : '');
			}
			print "\n        </ul>";

			if ($indexValue == $index) {
				foreach ($this->FriendlyNames as $nameL => $data) {
					if ($data['swamidStd'])
						if (isset($existingRequestedAttribute[$nameL]))
							printf('<b>%s</b> - %s<br>%s', $data['desc'], $nameL,  "\n");
						else
							printf('<a href="?edit=AttributeConsumingService&Entity=%d&oldEntity=%d&element=RequestedAttribute&index=%d&name=%s&friendlyName=%s&NameFormat=urn:oasis:names:tc:SAML:2.0:attrname-format:uri&isRequired=1&action=Add">[copy]</a> <b>%s</b> - %s<br>%s', $this->dbIdNr, $this->dbOldIdNr, $index, $nameL, $data['desc'], $data['desc'], $nameL,  "\n");
				}
				printf('
        <form>
          <input type="hidden" name="edit" value="AttributeConsumingService">
          <input type="hidden" name="Entity" value="%d">
          <input type="hidden" name="oldEntity" value="%d">
          <input type="hidden" name="element" value="RequestedAttribute">
          <input type="hidden" name="index" value="%d">
          <div class="row">
            <div class="col-2">Name: </div>
            <div class="col"><input type="text" name="name" value="%s"></div>
          </div>
          <div class="row">
            <div class="col-2">Required: </div>
            <div class="col"><input type="checkbox" name="isRequired" value="1"%s></div>
          </div>
          <div class="row">
            <div class="col-2">FriendlyName: </div>
            <div class="col"><input type="text" name="friendlyName" value="%s"></div>
          </div>
          <div class="row">
            <div class="col-2">NameFormat: </div>
            <div class="col">
              <select name="NameFormat">
                <option value="urn:oasis:names:tc:SAML:2.0:attrname-format:uri">urn:oasis:names:tc:SAML:2.0:attrname-format:uri</option>
                <option value="urn:mace:shibboleth:1.0:attributeNamespace:uri">urn:mace:shibboleth:1.0:attributeNamespace:uri</option>
              </select>
            </div>
          </div>
          <button type="submit" name="action" value="Add">Add/Update</button>
        </form>', $this->dbIdNr, $this->dbOldIdNr, $index, $name, $isRequired ? " checked" : '', $friendlyName, $nameFormat);
			}
		}
		printf('        <a href="./?validateEntity=%d"><button>Back</button></a>%s        <a href="./?edit=AttributeConsumingService&Entity=%d&oldEntity=%d&action=AddIndex"><button>Add Index</button></a>%s      </div><!-- end col -->%s      <div class="col">', $this->dbIdNr, "\n", $this->dbIdNr, $this->dbOldIdNr, "\n", "\n");
		# Print Old Info
		if (isset($oldServiceIndexes)) {
			foreach ($oldServiceIndexes as $index) {
				printf('%s        <b>Index = %d</b>%s        <ul>', "\n", $index, "\n");
				foreach ($oldServiceElements[$index] as $element => $elementData) {
					foreach ($elementData as $lang => $data) {
						switch ($data['state']) {
							case 'same' :
								$copy = '';
								$state = 'dark';
								break;
							case 'removed' :
								$copy = sprintf('<a href ="?edit=AttributeConsumingService&Entity=%d&oldEntity=%d&index=%s&element=%s&lang=%s&value=%s&action=Add">[copy]</a> ', $this->dbIdNr, $this->dbOldIdNr, $index, $element, $lang, urlencode($data['value']));
								$state = 'danger';
								break;
							default :
								$copy = '';
								$state = 'danger';
						}
						printf('%s          <li>%s<span class="text-%s">%s[%s] = %s</span></li>', "\n", $copy, $state, $element, $lang, $data['value']);
					}
				}
				print "\n          <li>RequestedAttributes : <ul>";
				foreach ($oldRequestedAttributes[$index] as $name => $data) {
					switch ($data['state']) {
						case 'same' :
							$copy = '';
							$state = 'dark';
							break;
						case 'removed' :
							$copy = sprintf('<a href ="?edit=AttributeConsumingService&Entity=%d&oldEntity=%d&index=%s&element=RequestedAttribute&name=%s&isRequired=%s&NameFormat=%s&action=Add">[copy]</a> ', $this->dbIdNr, $this->dbOldIdNr, $index, $name, $data['isRequired'], $data['nameFormat']);
							$state = 'danger';
							break;
						default :
							$copy = '';
							$state = 'danger';
					}
					printf('%s            <li>%s<span class="text-%s"><b>%s</b> - %s%s</span></li>', "\n", $copy, $state, $data['friendlyName'] == '' ? '(' . $this->FriendlyNames[$name]['desc'] .')' : $data['friendlyName'], $name, $data['isRequired'] == '1' ? ' (Required)' : '');
				}
				print "\n          </ul></li>\n        </ul>";
			}
		}
		print "\n      </div><!-- end col -->\n    </div><!-- end row -->\n";
	}
	private function editOrganization() {
		$organizationHandler = $this->metaDb->prepare('SELECT element, lang, data FROM Organization WHERE entity_id = :Id ORDER BY element, lang;');

		if (isset($_GET['action'])) {
			$error = '';
			if (isset($_GET['element']) && trim($_GET['element']) != '') {
				$element = trim($_GET['element']);
				$elementmd = 'md:'.$element;
				if (isset($this->orderOrganization[$elementmd])) {
					$placement = $this->orderOrganization[$elementmd];
				} else {
					$error .= '<br>Unknown Element selected';
				}
			} else {
				$error .= '<br>No Element selected';
				$elementValue = '';
			}
			if (isset($_GET['lang']) && trim($_GET['lang']) != '') {
				$lang = strtolower(trim($_GET['lang']));
			} else {
				$error .= $_GET['action'] == "Add" ? '<br>Lang is empty' : '';
				$lang = '';
			}
			if (isset($_GET['value']) && $_GET['value'] != '') {
				$value = trim($_GET['value']);
			} else {
				$error .= $_GET['action'] == "Add" ? '<br>Value is empty' : '';
				$value = '';
			}
			if ($error) {
				printf ('<div class="row alert alert-danger" role="alert">Error:%s</div>', $error);
			} else {
				$EntityDescriptor = $this->getEntityDescriptor($this->newXml);

				# Find md:Extensions in XML
				$child = $EntityDescriptor->firstChild;
				$Organization = false;
				while ($child && ! $Organization) {
					switch ($child->nodeName) {
						case 'md:Organization' :
							$Organization = $child;
							break;
						case 'md:ContactPerson' :
						case 'md:AdditionalMetadataLocation' :
							$Organization = $this->newXml->createElement('md:Organization');
							$EntityDescriptor->insertBefore($Organization, $child);
							break;
					}
					$child = $child->nextSibling;
				}

				$changed = false;
				switch ($_GET['action']) {
					case 'Add' :
						if (! $Organization) {
							# Add if missing
							$Organization = $this->newXml->createElement('md:Organization');
							$EntityDescriptor->appendChild($Organization);
						}

						# Find md:Organization* in XML
						$child = $Organization->firstChild;
						$OrganizationElement = false;
						$newOrg = true;
						if ($elementmd == 'md:OrganizationURL') {
							$value = str_replace(' ', '+', $value);
						}
						while ($child && ! $OrganizationElement) {
							if (strtolower($child->getAttribute('xml:lang')) == $lang && $child->nodeName == $elementmd) {
								$OrganizationElement = $child;
								$newOrg = false;
							} elseif (isset ($this->orderOrganization[$child->nodeName]) && $this->orderOrganization[$child->nodeName] <= $placement) {
								$child = $child->nextSibling;
							} else {
								$OrganizationElement = $this->newXml->createElement($elementmd, $value);
								$OrganizationElement->setAttribute('xml:lang', $lang);
								$Organization->insertBefore($OrganizationElement, $child);
							}
						}
						if (! $OrganizationElement) {
							# Add if missing
							$OrganizationElement = $this->newXml->createElement($elementmd, $value);
							$OrganizationElement->setAttribute('xml:lang', $lang);
							$Organization->appendChild($OrganizationElement);
						}
						if ($newOrg) {
							# Add if missing
							$organizationAddHandler = $this->metaDb->prepare('INSERT INTO Organization (entity_id, element, lang, data) VALUES (:Id, :Element, :Lang, :Data) ;');
							$organizationAddHandler->bindParam(':Id', $this->dbIdNr);
							$organizationAddHandler->bindParam(':Element', $element);
							$organizationAddHandler->bindParam(':Lang', $lang);
							$organizationAddHandler->bindParam(':Data', $value);
							$organizationAddHandler->execute();
							$changed = true;
						} elseif ($OrganizationElement->nodeValue != $value) {
							$OrganizationElement->nodeValue = $value;
							$organizationUpdateHandler = $this->metaDb->prepare('UPDATE Organization SET data = :Data WHERE entity_id = :Id AND element = :Element AND lang = :Lang;');
							$organizationUpdateHandler->bindParam(':Id', $this->dbIdNr);
							$organizationUpdateHandler->bindParam(':Element', $element);
							$organizationUpdateHandler->bindParam(':Lang', $lang);
							$organizationUpdateHandler->bindParam(':Data', $_GET['value']);
							$organizationUpdateHandler->execute();
							$changed = true;
						}
						break;
					case 'Delete' :
						if ($Organization) {
							$child = $Organization->firstChild;
							$OrganizationElement = false;
							$moreOrganizationElements = false;
							while ($child && ! $OrganizationElement) {
								if (strtolower($child->getAttribute('xml:lang')) == $lang && $child->nodeName == $elementmd) {
									$OrganizationElement = $child;
								}
								$child = $child->nextSibling;
								$moreOrganizationElements = ($moreOrganizationElements) ? true : $child;
							}

							if ($OrganizationElement) {
								$Organization->removeChild($OrganizationElement);
								if (! $moreOrganizationElements) $EntityDescriptor->removeChild($Organization);

								$organizationRemoveHandler = $this->metaDb->prepare('DELETE FROM Organization WHERE entity_id = :Id AND element = :Element AND lang = :Lang AND data = :Data;');
								$organizationRemoveHandler->bindParam(':Id', $this->dbIdNr);
								$organizationRemoveHandler->bindParam(':Element', $element);
								$organizationRemoveHandler->bindParam(':Lang', $lang);
								$organizationRemoveHandler->bindParam(':Data', $value);
								$organizationRemoveHandler->execute();
								$changed = true;
							}
						}
						$element = '';
						$elementmd = '';
						$lang = '';
						$value = '';
						break;
				}
				if ($changed) {
					$this->saveXML();
				}
			}
		} else {
			$element = '';
			$elementmd = '';
			$lang = '';
			$value = '';
		}
		print "\n";
		print '    <div class="row">' . "\n" . '      <div class="col">' . "\n" . '        <ul>';

		$oldOrganizationElements = array();
		$organizationHandler->bindParam(':Id', $this->dbOldIdNr);
		$organizationHandler->execute();
		while ($organization = $organizationHandler->fetch(PDO::FETCH_ASSOC)) {
			if (! isset($oldOrganizationElements[$organization['element']]) )
				$oldOrganizationElements[$organization['element']] = array();
			$oldOrganizationElements[$organization['element']][$organization['lang']] = array('value' => $organization['data'], 'state' => 'removed');
		}

		$existingOrganizationElements = array();
		$organizationHandler->bindParam(':Id', $this->dbIdNr);
		$organizationHandler->execute();
		while ($organization = $organizationHandler->fetch(PDO::FETCH_ASSOC)) {
			if (! isset($existingOrganizationElements[$organization['element']]) )
				$existingOrganizationElements[$organization['element']] = array();
			if (isset($oldOrganizationElements[$organization['element']][$organization['lang']]) && $oldOrganizationElements[$organization['element']][$organization['lang']]['value'] == $organization['data']) {
				$state = 'dark';
				$oldOrganizationElements[$organization['element']][$organization['lang']]['state'] = 'same';
			} else $state = 'success';
			$baseLink = '<a href="?edit=Organization&Entity='.$this->dbIdNr.'&oldEntity='.$this->dbOldIdNr.'&element='.$organization['element'].'&lang='.$organization['lang'].'&value='.$organization['data'].'&action=';
			$links = $baseLink . 'Copy"><i class="fas fa-pencil-alt"></i></a> ' . $baseLink . 'Delete"><i class="fas fa-trash"></i></a> ';
			printf ('%s          <li>%s<span class="text-%s">%s[%s] = %s</span></li>', "\n", $links, $state, $organization['element'], $organization['lang'], $organization['data']);
			$existingOrganizationElements[$organization['element']][$organization['lang']] = true;
		}
		printf('
        </ul>
        <form>
          <input type="hidden" name="edit" value="Organization">
          <input type="hidden" name="Entity" value="%d">
          <input type="hidden" name="oldEntity" value="%d">
          <div class="row">
            <div class="col-1">Element: </div>
            <div class="col">
              <select name="element">
                <option value="OrganizationName"%s>OrganizationName</option>
                <option value="OrganizationDisplayName"%s>OrganizationDisplayName</option>
                <option value="OrganizationURL"%s>OrganizationURL</option>
                <option value="Extensions"%s>Extensions</option>
              </select>
            </div>
          </div>
          <div class="row">
            <div class="col-1">Lang: </div>
            <div class="col">', $this->dbIdNr, $this->dbOldIdNr, $element == 'OrganizationName' ? ' selected' : '', $element == 'OrganizationDisplayName' ? ' selected' : '', $element == 'OrganizationURL' ? ' selected' : '', $element == 'Extensions' ? ' selected' : '');
			$this->showLangSelector($lang);
			printf('            </div>
          </div>
          <div class="row">
            <div class="col-1">Value: </div>
            <div class="col"><input type="text" size="60" name="value" value="%s"></div>
          </div>
          <button type="submit" name="action" value="Add">Add/Update</button>
        </form>
        <a href="./?validateEntity=' . $this->dbIdNr . '"><button>Back</button></a>
      </div><!-- end col -->
      <div class="col">%s', $value, "\n");

		$organizationHandler->bindParam(':Id', $this->dbOldIdNr);
		$organizationHandler->execute();
		print ('        <ul>');
		while ($organization = $organizationHandler->fetch(PDO::FETCH_ASSOC)) {
			$state = ($oldOrganizationElements[$organization['element']][$organization['lang']]['state'] == 'same') ? 'dark' : 'danger';
			$addLink =  (isset($existingOrganizationElements[$organization['element']][$organization['lang']]) ) ? '' : '<a href="?edit=Organization&Entity='.$this->dbIdNr.'&oldEntity='.$this->dbOldIdNr.'&element='.$organization['element'].'&lang='.$organization['lang'].'&value='.$organization['data'].'&action=Add">[copy]</a> ';
			printf ('%s          <li>%s<span class="text-%s">%s[%s] = %s</span></li>', "\n", $addLink, $state, $organization['element'], $organization['lang'], $organization['data']);
		}
		print ("\n        <ul>");
		print "\n      </div><!-- end col -->\n    </div><!-- end row -->\n";
	}
	private function editContactPersons(){
		$contactPersonHandler = $this->metaDb->prepare('SELECT * FROM ContactPerson WHERE entity_id = :Id ORDER BY contactType;');

		if (isset($_GET['action']) && isset($_GET['type']) && isset($_GET['part']) && isset($_GET['value']) && trim($_GET['value']) != '' ) {
			switch ($_GET['type']) {
				case 'administrative' :
				case 'technical' :
				case 'support' :
				case 'other' :
					$type = $_GET['type'];
					$subType = false;
					break;
				case 'security' :
					$type = 'other';
					$subType = 'http://refeds.org/metadata/contactType/security';
					break;
				default :
					exit;
			}

			$part = $_GET['part'];
			$partmd = 'md:'.$part;
			if (isset($this->orderContactPerson[$partmd])) {
				$placement = $this->orderContactPerson[$partmd];
			} else {
				printf ('Missing %s', $part);
				exit();
			}

			$value = ($part == 'EmailAddress' && substr($_GET['value'],0,7) <> 'mailto:') ? 'mailto:'.trim($_GET['value']) : trim($_GET['value']);

			$EntityDescriptor = $this->getEntityDescriptor($this->newXml);

			# Find md:Extensions in XML
			$child = $EntityDescriptor->firstChild;
			$ContactPerson = false;
			$moreContactPersons = false;

			switch ($_GET['action']) {
				case 'Add' :
					$value = ($part == 'EmailAddress' && substr($_GET['value'],0,7) <> 'mailto:') ? 'mailto:'.trim($_GET['value']) : trim($_GET['value']);
					while ($child && ! $ContactPerson) {
						switch ($child->nodeName) {
							case 'md:ContactPerson' :
								if ($child->getAttribute('contactType') == $type) {
									if ($subType) {
										if ($child->getAttribute('remd:contactType') == $subType)
												$ContactPerson = $child;
											else
												$moreContactPersons = true;
									} else {
										$ContactPerson = $child;
									}
								} else
									$moreContactPersons = true;
								break;
							case 'md:AdditionalMetadataLocation' :
								$ContactPerson = $this->newXml->createElement('md:ContactPerson');
								$ContactPerson->setAttribute('contactType', $type);
								if ($subType) {
									$EntityDescriptor->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:remd', 'http://refeds.org/metadata');
									$ContactPerson->setAttribute('remd:contactType', $subType);
								}
								$EntityDescriptor->insertBefore($ContactPerson, $child);
								break;
						}
						$child = $child->nextSibling;
					}
					if (! $ContactPerson) {
						# Add if missing
						$ContactPerson = $this->newXml->createElement('md:ContactPerson');
						$ContactPerson->setAttribute('contactType', $type);
						if ($subType) {
							$EntityDescriptor->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:remd', 'http://refeds.org/metadata');
							$ContactPerson->setAttribute('remd:contactType', $subType);
						}
						$EntityDescriptor->appendChild($ContactPerson);

						$contactPersonAddHandler = $this->metaDb->prepare('INSERT INTO ContactPerson (entity_id, contactType) VALUES (:Id, :ContactType) ;');
						$contactPersonAddHandler->bindParam(':Id', $this->dbIdNr);
						$contactPersonAddHandler->bindParam(':ContactType', $type);
						$contactPersonAddHandler->execute();
					}

					$child = $ContactPerson->firstChild;
					$ContactPersonElement = false;
					$newContactPerson = true;
					while ($child && ! $ContactPersonElement) {
						if ($child->nodeName == $partmd) {
							$ContactPersonElement = $child;
							$newContactPerson = false;
						} elseif (isset ($this->orderContactPerson[$child->nodeName]) && $this->orderContactPerson[$child->nodeName] < $placement) {
							$child = $child->nextSibling;
						} else {
							$ContactPersonElement = $this->newXml->createElement($partmd);
							$ContactPerson->insertBefore($ContactPersonElement, $child);
						}
					}
					$changed = false;
					if (! $ContactPersonElement) {
						# Add if missing
						$ContactPersonElement = $this->newXml->createElement($partmd);
						$ContactPerson->appendChild($ContactPersonElement);
					}
					if ($ContactPersonElement->nodeValue != $value) {
						$ContactPersonElement->nodeValue = $value;
						$sql="UPDATE ContactPerson SET $part = :Data WHERE entity_id = :Id AND contactType = :ContactType ;";
						$contactPersonUpdateHandler = $this->metaDb->prepare($sql);
						$contactPersonUpdateHandler->bindParam(':Id', $this->dbIdNr);
						$contactPersonUpdateHandler->bindParam(':ContactType', $type);
						$contactPersonUpdateHandler->bindParam(':Data', $value);
						$contactPersonUpdateHandler->execute();
						$changed = true;
					}
					if ($changed) {
						$this->saveXML();
					}
					break;
				case 'Delete' :
					$value = trim($_GET['value']);
					while ($child && ! $ContactPerson) {
						switch ($child->nodeName) {
							case 'md:ContactPerson' :
								if ($child->getAttribute('contactType') == $type) {
									if ($subType) {
										if ($child->getAttribute('remd:contactType') == $subType)
												$ContactPerson = $child;
											else
												$moreContactPersons = true;
									} else {
										$ContactPerson = $child;
									}
								} else
									$moreContactPersons = true;
								break;
						}
						if ($ContactPerson) {
							$childContactPerson = $ContactPerson->firstChild;
							$ContactPersonElement = false;
							$moreContactPersonElements = false;
							while ($childContactPerson && ! $ContactPersonElement) {
								if ($childContactPerson->nodeName == $partmd && $childContactPerson->nodeValue == $value ) {
									$ContactPersonElement = $childContactPerson;
								}
								$childContactPerson = $childContactPerson->nextSibling;
								$moreContactPersonElements = ($moreContactPersonElements) ? true : $childContactPerson;
							}

							if ($ContactPersonElement) {
								$ContactPerson->removeChild($ContactPersonElement);
								if ($moreContactPersonElements) {
									$sql="UPDATE ContactPerson SET $part = '' WHERE entity_id = :Id AND contactType = :ContactType AND $part = :Value;";
									$contactPersonUpdateHandler = $this->metaDb->prepare($sql);
									$contactPersonUpdateHandler->bindParam(':Id', $this->dbIdNr);
									$contactPersonUpdateHandler->bindParam(':ContactType', $type);
									$contactPersonUpdateHandler->bindParam(':Value', $value);
									$contactPersonUpdateHandler->execute();
								} else {
									$EntityDescriptor->removeChild($ContactPerson);
									$contactPersonDeleteHandler = $this->metaDb->prepare('DELETE FROM ContactPerson WHERE entity_id = :Id AND contactType = :ContactType ;');
									$contactPersonDeleteHandler->bindParam(':Id', $this->dbIdNr);
									$contactPersonDeleteHandler->bindParam(':ContactType', $type);
									$contactPersonDeleteHandler->execute();
								}

								$this->saveXML();
							} else {
								$ContactPerson = false;
							}
						}
						$child = $child->nextSibling;
					}
					$type = '';
					$part = '';
					$value = '';
					break;
			}
		} else {
			$type = '';
			$part = '';
			$value = '';
		}
		print "\n";
		print '    <div class="row">
      <div class="col">';

		$oldContactPersons = array();
		$contactPersonHandler->bindParam(':Id', $this->dbOldIdNr);
		$contactPersonHandler->execute();
		while ($contactPerson = $contactPersonHandler->fetch(PDO::FETCH_ASSOC)) {
			if (! isset($oldContactPersons[$contactPerson['contactType']]))
				$oldContactPersons[$contactPerson['contactType']] = array(
					'company' => array('value' => '', 'state' => 'new'),
					'givenName' => array('value' => '', 'state' => 'new'),
					'surName' => array('value' => '', 'state' => 'new'),
					'emailAddress' => array('value' => '', 'state' => 'new'),
					'telephoneNumber' => array('value' => '', 'state' => 'new'),
					'extensions' => array('value' => '', 'state' => 'new')
				);
			if ($contactPerson['company']) {
					$oldContactPersons[$contactPerson['contactType']]['company']['value'] = $contactPerson['company'];
			}
			if ($contactPerson['givenName']) {
					$oldContactPersons[$contactPerson['contactType']]['givenName']['value'] = $contactPerson['givenName'];
			}
			if ($contactPerson['surName']) {
					$oldContactPersons[$contactPerson['contactType']]['surName']['value'] = $contactPerson['surName'];
			}
			if ($contactPerson['emailAddress']) {
					$oldContactPersons[$contactPerson['contactType']]['emailAddress']['value'] = $contactPerson['emailAddress'];
			}
			if ($contactPerson['telephoneNumber']) {
					$oldContactPersons[$contactPerson['contactType']]['telephoneNumber']['value'] = $contactPerson['telephoneNumber'];
			}
			if ($contactPerson['extensions']) {
					$oldContactPersons[$contactPerson['contactType']]['extensions']['value'] = $contactPerson['extensions'];
			}
		}

		$existingContactPersons = array();
		$contactPersonHandler->bindParam(':Id', $this->dbIdNr);
		$contactPersonHandler->execute();
		while ($contactPerson = $contactPersonHandler->fetch(PDO::FETCH_ASSOC)) {
			if (! isset($existingContactPersons[$contactPerson['contactType']]))
				$existingContactPersons[$contactPerson['contactType']] = array();

			$baseLink = '<a href="?edit=ContactPersons&Entity='.$this->dbIdNr.'&oldEntity='.$this->dbOldIdNr.'&type='.$contactPerson['contactType'] . '&value=';
			$copyLink = '&action=Copy"><i class="fas fa-pencil-alt"></i></a> ';
			$removeLink = '&action=Delete"><i class="fas fa-trash"></i></a> ';
			if ($contactPerson['subcontactType'] == '')
				printf ("\n        <b>%s</b><br>\n", $contactPerson['contactType']);
			else
				printf ("\n        <b>%s[%s]</b><br>\n", $contactPerson['contactType'], $contactPerson['subcontactType']);
			print "        <ul>\n";
			if (isset($oldContactPersons[$contactPerson['contactType']])) {
				foreach (array('company', 'givenName', 'surName', 'emailAddress', 'telephoneNumber', 'extensions') as $oldPart) {
					if (isset ($contactPerson[$oldPart]) && $oldContactPersons[$contactPerson['contactType']][$oldPart]['value'] == $contactPerson[$oldPart]) {
						$oldContactPersons[$contactPerson['contactType']][$oldPart]['state'] = 'same';
					} elseif ($contactPerson[$oldPart] == '' ) {
						$oldContactPersons[$contactPerson['contactType']][$oldPart]['state'] = 'removed';
					} else {
						$oldContactPersons[$contactPerson['contactType']][$oldPart]['state'] = 'changed';
					}
				}
			} else {
				$oldContactPersons[$contactPerson['contactType']] = array(
					'company' => array('state' => 'new'),
					'givenName' => array('state' => 'new'),
					'surName' => array('state' => 'new'),
					'emailAddress' => array('state' => 'new'),
					'telephoneNumber' => array('state' => 'new'),
					'extensions' => array('state' => 'new')
				);
			}
			if ($contactPerson['company']) {
				$state = ($oldContactPersons[$contactPerson['contactType']]['company']['state'] == 'same') ? 'dark' : 'success';
				$baseLink2 = $baseLink . $contactPerson['company'] . '&part=Company';
				$links = $baseLink2 . $copyLink . $baseLink2 . $removeLink;
				printf ('          <li>%s<span class="text-%s">Company = %s</span></li>%s', $links, $state, $contactPerson['company'], "\n");
				$existingContactPersons[$contactPerson['contactType']]['company'] = true;
			}
			if ($contactPerson['givenName']) {
				$state = ($oldContactPersons[$contactPerson['contactType']]['givenName']['state'] == 'same') ? 'dark' : 'success';
				$baseLink2 = $baseLink . $contactPerson['givenName'] . '&part=GivenName';
				$links = $baseLink2 . $copyLink . $baseLink2 . $removeLink;
				printf ('          <li>%s<span class="text-%s">GivenName = %s</span></li>%s', $links, $state, $contactPerson['givenName'], "\n");
				$existingContactPersons[$contactPerson['contactType']]['givenName'] = true;
			}
			if ($contactPerson['surName']) {
				$state = ($oldContactPersons[$contactPerson['contactType']]['surName']['state'] == 'same') ? 'dark' : 'success';
				$baseLink2 = $baseLink . $contactPerson['surName'] . '&part=SurName';
				$links = $baseLink2 . $copyLink . $baseLink2 . $removeLink;
				printf ('          <li>%s<span class="text-%s">SurName = %s</span></li>%s', $links, $state, $contactPerson['surName'], "\n");
				$existingContactPersons[$contactPerson['contactType']]['surName'] = true;
			}
			if ($contactPerson['emailAddress']) {
				$state = ($oldContactPersons[$contactPerson['contactType']]['emailAddress']['state'] == 'same') ? 'dark' : 'success';
				$baseLink2 = $baseLink . $contactPerson['emailAddress'] . '&part=EmailAddress';
				$links = $baseLink2 . $copyLink . $baseLink2 . $removeLink;
				printf ('          <li>%s<span class="text-%s">EmailAddress = %s</span></li>%s', $links, $state, $contactPerson['emailAddress'], "\n");
				$existingContactPersons[$contactPerson['contactType']]['emailAddress'] = true;
			}
			if ($contactPerson['telephoneNumber']) {
				$state = ($oldContactPersons[$contactPerson['contactType']]['telephoneNumber']['state'] == 'same') ? 'dark' : 'success';
				$baseLink2 = $baseLink . urlencode($contactPerson['telephoneNumber']) . '&part=TelephoneNumber';
				$links = $baseLink2 . $copyLink . $baseLink2 . $removeLink;
				printf ('          <li>%s<span class="text-%s">TelephoneNumber = %s</span></li>%s', $links, $state, $contactPerson['telephoneNumber'], "\n");
				$existingContactPersons[$contactPerson['contactType']]['telephoneNumber'] = true;
			}
			if ($contactPerson['extensions']) {
				$state = ($oldContactPersons[$contactPerson['contactType']]['extensions']['state'] == 'same') ? 'dark' : 'success';
				$baseLink2 = $baseLink . $contactPerson['extensions'] . '&part=Extensions';
				$links = $baseLink2 . $copyLink . $baseLink2 . $removeLink;
				printf ('          <li>%s<span class="text-%s">Extensions = %s</span></li>%s', $links, $state, $contactPerson['extensions'], "\n");
				$existingContactPersons[$contactPerson['contactType']]['extensions'] = true;
			}
			print '        </ul>';
		}
		printf('        <form>
          <input type="hidden" name="edit" value="ContactPersons">
          <input type="hidden" name="Entity" value="%d">
          <input type="hidden" name="oldEntity" value="%d">
          <div class="row">
            <div class="col-1">Type: </div>
            <div class="col">
              <select name="type">
                <option value="administrative"%s>administrative</option>
                <option value="technical"%s>technical</option>
                <option value="support"%s>support</option>
                <option value="security"%s>security</option>
              </select>
            </div>
          </div>
          <div class="row">
            <div class="col-1">Part: </div>
            <div class="col">
              <select name="part">
                <option value="Company"%s>Company</option>
                <option value="GivenName"%s>GivenName</option>
                <option value="SurName"%s>SurName</option>
                <option value="EmailAddress"%s>EmailAddress</option>
                <option value="TelephoneNumber"%s>TelephoneNumber</option>
                <option value="Extensions"%s>Extensions</option>
              </select>
            </div>
          </div>
          <div class="row">
            <div class="col-1">Value:</div>
            <div class="col"><input type="text" name="value" value="%s"></div>
          </div>
          <button type="submit" name="action" value="Add">Add/Update</button>
        </form>
        <a href="./?validateEntity=%d"><button>Back</button></a>
      </div><!-- end col -->
      <div class="col">', $this->dbIdNr, $this->dbOldIdNr, $type == 'administrative' ? ' selected' : '', $type == 'technical' ? ' selected' : '', $type == 'support' ? ' selected' : '', $type == 'other' ? ' selected' : '', $part == 'Company' ? ' selected' : '', $part == 'GivenName' ? ' selected' : '', $part == 'SurName' ? ' selected' : '', $part == 'EmailAddress' ? ' selected' : '', $part == 'TelephoneNumber' ? ' selected' : '', $part == 'Extensions' ? ' selected' : '', $value, $this->dbIdNr);
		$contactPersonHandler->bindParam(':Id', $this->dbOldIdNr);
		$contactPersonHandler->execute();
		while ($contactPerson = $contactPersonHandler->fetch(PDO::FETCH_ASSOC)) {
			if ($contactPerson['subcontactType'] == '') {
				printf ("\n        <b>%s</b><br>\n", $contactPerson['contactType']);
				$type = $contactPerson['contactType'];
			} else {
				printf ("\n        <b>%s[%s]</b><br>\n", $contactPerson['contactType'], $contactPerson['subcontactType']);
				$type = 'security';
			}
			print "        <ul>\n";
			if ($contactPerson['company']) {
				$state = ($oldContactPersons[$contactPerson['contactType']]['company']['state'] == 'same') ? 'dark' : 'danger';
				$addLink = (isset($existingContactPersons[$contactPerson['contactType']]['company']) ? '' : '<a href="?edit=ContactPersons&Entity='.$this->dbIdNr.'&oldEntity='.$this->dbOldIdNr.'&type='.$type.'&part=Company&value='.urlencode($contactPerson['company']).'&action=Add">[copy]</a> ');
				printf ('          <li>%s<span class="text-%s">Company = %s</span></li>%s', $addLink, $state, $contactPerson['company'], "\n");
			}
			if ($contactPerson['givenName']) {
				$state = ($oldContactPersons[$contactPerson['contactType']]['givenName']['state'] == 'same') ? 'dark' : 'danger';
				$addLink = (isset($existingContactPersons[$contactPerson['contactType']]['givenName']) ? '' : '<a href="?edit=ContactPersons&Entity='.$this->dbIdNr.'&oldEntity='.$this->dbOldIdNr.'&type='.$type.'&part=GivenName&value='.urlencode($contactPerson['givenName']).'&action=Add">[copy]</a> ');
				printf ('          <li>%s<span class="text-%s">GivenName = %s</span></li>%s', $addLink, $state, $contactPerson['givenName'], "\n");
			}
			if ($contactPerson['surName']) {
				$state = ($oldContactPersons[$contactPerson['contactType']]['surName']['state'] == 'same') ? 'dark' : 'danger';
				$addLink = (isset($existingContactPersons[$contactPerson['contactType']]['surName']) ? '' : '<a href="?edit=ContactPersons&Entity='.$this->dbIdNr.'&oldEntity='.$this->dbOldIdNr.'&type='.$type.'&part=SurName&value='.urlencode($contactPerson['surName']).'&action=Add">[copy]</a> ');
				printf ('          <li>%s<span class="text-%s">SurName = %s</span></li>%s', $addLink, $state, $contactPerson['surName'], "\n");
			}
			if ($contactPerson['emailAddress']) {
				$state = ($oldContactPersons[$contactPerson['contactType']]['emailAddress']['state'] == 'same') ? 'dark' : 'danger';
				$addLink = (isset($existingContactPersons[$contactPerson['contactType']]['emailAddress']) ? '' : '<a href="?edit=ContactPersons&Entity='.$this->dbIdNr.'&oldEntity='.$this->dbOldIdNr.'&type='.$type.'&part=EmailAddress&value='.urlencode($contactPerson['emailAddress']).'&action=Add">[copy]</a> ');
				printf ('          <li>%s<span class="text-%s">EmailAddress = %s</span></li>%s', $addLink, $state, $contactPerson['emailAddress'], "\n");
			}
			if ($contactPerson['telephoneNumber']) {
				$state = ($oldContactPersons[$contactPerson['contactType']]['telephoneNumber']['state'] == 'same') ? 'dark' : 'danger';
				$addLink = (isset($existingContactPersons[$contactPerson['contactType']]['telephoneNumber']) ? '' : '<a href="?edit=ContactPersons&Entity='.$this->dbIdNr.'&oldEntity='.$this->dbOldIdNr.'&type='.$type.'&part=TelephoneNumber&value='.urlencode($contactPerson['telephoneNumber']).'&action=Add">[copy]</a> ');
				printf ('          <li>%s<span class="text-%s">TelephoneNumber = %s</span></li>%s', $addLink, $state, $contactPerson['telephoneNumber'], "\n");
			}
			if ($contactPerson['extensions']) {
				$state = ($oldContactPersons[$contactPerson['contactType']]['extensions']['state'] == 'same') ? 'dark' : 'danger';
				$addLink = (isset($existingContactPersons[$contactPerson['contactType']]['extensions']) ? '' : '<a href="?edit=ContactPersons&Entity='.$this->dbIdNr.'&oldEntity='.$this->dbOldIdNr.'&type='.$type.'&part=Extensions&value='.urlencode($contactPerson['extensions']).'&action=Add">[copy]</a> ');
				printf ('          <li>%s<span class="text-%s">Extensions = %s</span></li>%s', $addLink, $state, $contactPerson['extensions'], "\n");
			}
			print '        </ul>';
		}
	}

	public function mergeFrom() {
		if ( !$this->oldExists)
			return;
		$this->mergeRegistrationInfo();
		$this->mergeEntityAttributes();
		if ($this->isIdP) {
			$this->mergeIdpErrorURL();
			$this->mergeIdPScopes();
			$this->mergeUIInfo('IDPSSO');
			$this->mergeDiscoHints();
		}
		if ($this->isSP) {
			$this->mergeUIInfo('SPSSO');
			$this->mergeAttributeConsumingService();
		}
		$this->mergeOrganization();
		$this->mergeContactPersons();
		$this->saveXML();
	}
	public function mergeRegistrationInfo() {
		# Skip if not same entityID. Only migrate if same!!!!
		if ( !$this->oldExists || $this->entityID <> $this->oldentityID )
			return;

		$registrationInstantHandler = $this->metaDb->prepare('SELECT registrationInstant AS ts FROM Entities WHERE id = :Id;');
		$registrationInstantHandler->bindParam(':Id', $this->dbOldIdNr);
		$registrationInstantHandler->execute();
		if ($Instant = $registrationInstantHandler->fetch(PDO::FETCH_ASSOC)) {
			$EntityDescriptor = $this->getEntityDescriptor($this->newXml);
			# Find md:Extensions in XML
			$child = $EntityDescriptor->firstChild;
			$Extensions = false;
			while ($child && ! $Extensions) {
				switch ($child->nodeName) {
					case 'md:Extensions' :
						$Extensions = $child;
						break;
					case 'md:RoleDescriptor' :
					case 'md:SPSSODescriptor' :
					case 'md:IDPSSODescriptor' :
					case 'md:AuthnAuthorityDescriptor' :
					case 'md:AttributeAuthorityDescriptor' :
					case 'md:PDPDescriptor' :
					case 'md:AffiliationDescriptor' :
					case 'md:Organization' :
					case 'md:ContactPerson' :
					case 'md:AdditionalMetadataLocation' :
						$Extensions = $this->newXml->createElement('md:Extensions');
						$EntityDescriptor->insertBefore($Extensions, $child);
						break;
				}
				$child = $child->nextSibling;
			}
			if (! $Extensions) {
				# Add if missing
				$Extensions = $this->newXml->createElement('md:Extensions');
				$EntityDescriptor->appendChild($Extensions);
			}
			# Find mdattr:EntityAttributes in XML
			$child = $Extensions->firstChild;
			$RegistrationInfo = false;
			while ($child && ! $RegistrationInfo) {
				if ($child->nodeName == 'mdrpi:RegistrationInfo') {
					$RegistrationInfo = $child;
				} else
					$child = $child->nextSibling;
			}
			if (! $RegistrationInfo) {
				# Add if missing
				$EntityDescriptor->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:mdrpi', 'urn:oasis:names:tc:SAML:metadata:rpi');
				$RegistrationInfo = $this->newXml->createElement('mdrpi:RegistrationInfo');
				$RegistrationInfo->setAttribute('registrationAuthority', 'http://www.swamid.se/');
				$RegistrationInfo->setAttribute('registrationInstant', $Instant['ts']);
				$Extensions->appendChild($RegistrationInfo);
			}

			# Find samla:Attribute in XML
			$child = $RegistrationInfo->firstChild;
			$RegistrationPolicy = false;
			while ($child && ! $RegistrationPolicy) {
				if ($child->nodeName == 'mdrpi:RegistrationPolicy' && $child->getAttribute('xml:lang') == 'en') {
					$RegistrationPolicy = $child;
				} else {
					$child = $child->nextSibling;
				}
			}
			if (!$RegistrationPolicy) {
				$RegistrationPolicy = $this->newXml->createElement('mdrpi:RegistrationPolicy', 'http://swamid.se/policy/mdrps');
				$RegistrationPolicy->setAttribute('xml:lang', 'en');
				$RegistrationInfo->appendChild($RegistrationPolicy);
			}
		}
	}
	private function mergeEntityAttributes() {
		if ( !$this->oldExists)
			return;
		$entityAttributesHandler = $this->metaDb->prepare('SELECT type, attribute FROM EntityAttributes WHERE entity_id = :Id ORDER BY type, attribute;');
		$entityAttributesHandler->bindParam(':Id', $this->dbOldIdNr);
		$entityAttributesHandler->execute();
		while ($attribute = $entityAttributesHandler->fetch(PDO::FETCH_ASSOC)) {
			switch ($attribute['type']) {
				case 'assurance-certification' :
					$attributeType = 'urn:oasis:names:tc:SAML:attribute:assurance-certification';
					break;
				case 'entity-category' :
					$attributeType = 'http://macedir.org/entity-category';
					break;
				case 'entity-category-support' :
					$attributeType = 'http://macedir.org/entity-category-support';
					break;
				case 'subject-id:req' :
					$attributeType = 'urn:oasis:names:tc:SAML:profiles:subject-id:req';
					break;
				default :
					exit;
			}
			if (! isset($oldAttributeValues[$attributeType]) )
				$oldAttributeValues[$attributeType] = array();
			$oldAttributeValues[$attributeType][$attribute['attribute']] = $attribute['attribute'];
		}
		if(isset($oldAttributeValues)) {
			$EntityDescriptor = $this->getEntityDescriptor($this->newXml);
			# Find md:Extensions in XML
			$child = $EntityDescriptor->firstChild;
			$Extensions = false;
			while ($child && ! $Extensions) {
				switch ($child->nodeName) {
					case 'md:Extensions' :
						$Extensions = $child;
						break;
					case 'md:RoleDescriptor' :
					case 'md:SPSSODescriptor' :
					case 'md:IDPSSODescriptor' :
					case 'md:AuthnAuthorityDescriptor' :
					case 'md:AttributeAuthorityDescriptor' :
					case 'md:PDPDescriptor' :
					case 'md:AffiliationDescriptor' :
					case 'md:Organization' :
					case 'md:ContactPerson' :
					case 'md:AdditionalMetadataLocation' :
						$Extensions = $this->newXml->createElement('md:Extensions');
						$EntityDescriptor->insertBefore($Extensions, $child);
						break;
				}
				$child = $child->nextSibling;
			}
			if (! $Extensions) {
				# Add if missing
				$Extensions = $this->newXml->createElement('md:Extensions');
				$EntityDescriptor->appendChild($Extensions);
			}

			# Find mdattr:EntityAttributes in XML
			$child = $Extensions->firstChild;
			$EntityAttributes = false;
			while ($child && ! $EntityAttributes) {
				if ($child->nodeName == 'mdattr:EntityAttributes') {
					$EntityAttributes = $child;
				} else
					$child = $child->nextSibling;
			}
			if (! $EntityAttributes) {
				# Add if missing
				$EntityDescriptor->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:mdattr', 'urn:oasis:names:tc:SAML:metadata:attribute');
				$EntityAttributes = $this->newXml->createElement('mdattr:EntityAttributes');
				$Extensions->appendChild($EntityAttributes);
			}

			# Find samla:Attribute in XML
			$Attribute = $EntityAttributes->firstChild;
			while ($Attribute) {
				$AttributeValue = $Attribute->firstChild;
				$type = $Attribute->getAttribute('Name');
				while($AttributeValue) {
					$value = $AttributeValue->textContent;
					if (isset($oldAttributeValues[$type][$value]))
						unset($oldAttributeValues[$type][$value]);
					$AttributeValue = $AttributeValue->nextSibling;
				}
				foreach ($oldAttributeValues[$type] as $value) {
					$AttributeValue = $this->newXml->createElement('samla:AttributeValue');
					$AttributeValue->nodeValue = $value;
					$Attribute->appendChild($AttributeValue);
					unset($oldAttributeValues[$type][$value]);
				}
				$Attribute = $Attribute->nextSibling;
			}
			foreach ($oldAttributeValues as $type => $values) {
				if (! empty($values)) {
					$EntityDescriptor->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:samla', 'urn:oasis:names:tc:SAML:2.0:assertion');
					$Attribute = $this->newXml->createElement('samla:Attribute');
					$Attribute->setAttribute('Name', $type);
					$Attribute->setAttribute('NameFormat', 'urn:oasis:names:tc:SAML:2.0:attrname-format:uri');
					$EntityAttributes->appendChild($Attribute);
				}
				foreach ($values as $value) {
					$AttributeValue = $this->newXml->createElement('samla:AttributeValue');
					$AttributeValue->nodeValue = $value;
					$Attribute->appendChild($AttributeValue);
				}
			}
		}
	}
	private function mergeIdpErrorURL () {
		if ( !$this->oldExists)
			return;
		$errorURLHandler = $this->metaDb->prepare("SELECT DISTINCT URL FROM EntityURLs WHERE entity_id = :Id AND type = 'error';");
		$errorURLHandler->bindParam(':Id', $this->dbOldIdNr);
		$errorURLHandler->execute();
		if ($errorURL = $errorURLHandler->fetch(PDO::FETCH_ASSOC)) {
			$EntityDescriptor = $this->getEntityDescriptor($this->newXml);

			# Find md:IDPSSODescriptor in XML
			$child = $EntityDescriptor->firstChild;
			$IDPSSODescriptor = false;
			while ($child && ! $IDPSSODescriptor) {
				if ($child->nodeName == 'md:IDPSSODescriptor')
					$IDPSSODescriptor = $child;
				$child = $child->nextSibling;
			}

			if ($IDPSSODescriptor  && $IDPSSODescriptor->getAttribute('errorURL') == '') {
				$IDPSSODescriptor->setAttribute('errorURL', $errorURL['URL']);
				$errorURLUpdateHandler = $this->metaDb->prepare("REPLACE INTO EntityURLs (entity_id, URL, type ) VALUES (:Id, :URL, 'error');");
				$errorURLUpdateHandler->bindParam(':Id', $this->dbIdNr);
				$errorURLUpdateHandler->bindParam(':URL', $errorURL['URL']);
				$errorURLUpdateHandler->execute();
			}
		}
	}
	private function mergeIdPScopes() {
		if ( !$this->oldExists)
			return;
		$scopesHandler = $this->metaDb->prepare('SELECT `scope`, `regexp` FROM Scopes WHERE `entity_id` = :Id;');
		$scopesHandler->bindParam(':Id', $this->dbOldIdNr);
		$scopesHandler->execute();
		$scopesInsertHandler = $this->metaDb->prepare('INSERT INTO Scopes (`entity_id`, `scope`, `regexp`) VALUES (:Id, :Scope, :Regexp);');
		$scopesInsertHandler->bindParam(':Id', $this->dbIdNr);
		while ($scope = $scopesHandler->fetch(PDO::FETCH_ASSOC)) {
			$oldScopes[$scope['scope']] = true;
		}
		if ($oldScopes) {
			$EntityDescriptor = $this->getEntityDescriptor($this->newXml);

			# Find md:IDPSSODescriptor in XML
			$child = $EntityDescriptor->firstChild;
			$IDPSSODescriptor = false;
			while ($child && ! $IDPSSODescriptor) {
				if ($child->nodeName == 'md:IDPSSODescriptor')
					$IDPSSODescriptor = $child;
				$child = $child->nextSibling;
			}

			if ($IDPSSODescriptor) {
				$child = $IDPSSODescriptor->firstChild;
				$Extensions = false;
				while ($child && ! $Extensions) {
					switch ($child->nodeName) {
						case 'ds:Signature' :
							break;
						case 'md:Extensions' :
							$Extensions = $child;
							break;
						default :
							$Extensions = $this->newXml->createElement('md:Extensions');
							$IDPSSODescriptor->insertBefore($Extensions, $child);
					}
					$child = $child->nextSibling;
				}
				if (! $Extensions) {
					$Extensions = $this->newXml->createElement('md:Extensions');
					$IDPSSODescriptor->appendChild($Extensions);
				}
				$child = $Extensions->firstChild;
				$beforeChild = false;
				$Scope = false;
				$shibmdFound = false;
				while ($child && ! $Scope) {
					switch ($child->nodeName) {
						case 'shibmd:Scope' :
							$shibmdFound = true;
							if (isset ($oldScopes[$child->textContent]))
								unset ($oldScopes[$child->textContent]);
							break;
						case 'mdui:UIInfo' :
						case 'mdui:DiscoHints' :
							$beforeChild = $beforeChild ? $beforeChild : $child;
							break;
					}
					$child = $child->nextSibling;
				}
				foreach ($oldScopes as $scopevalue => $value) {
					$Scope = $this->newXml->createElement('shibmd:Scope', $scopevalue);
					$Scope->setAttribute('regexp', $value);
					if ($beforeChild)
						$Extensions->insertBefore($Scope, $beforeChild);
					else
						$Extensions->appendChild($Scope);
					$scopesInsertHandler->bindParam(':Scope', $scopevalue);
					$scopesInsertHandler->bindParam(':Regexp', $value);
					$scopesInsertHandler->execute();
				}

				if (! $shibmdFound) {
					$EntityDescriptor->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:shibmd', 'urn:mace:shibboleth:metadata:1.0');
				}
			}
		}
	}
	private function mergeUIInfo($type) {
		if ( !$this->oldExists)
			return;
		$mduiHandler = $this->metaDb->prepare('SELECT element, lang, height, width, data FROM Mdui WHERE entity_id = :Id AND type = :Type ORDER BY element, lang;');
		$mduiHandler->bindParam(':Type', $type);
		$mduiHandler->bindParam(':Id', $this->dbOldIdNr);
		$mduiHandler->execute();
		while ($mdui = $mduiHandler->fetch(PDO::FETCH_ASSOC)) {
			$mdelement = 'mdui:'.$mdui['element'];
			$size = $mdui['height'].'x'.$mdui['width'];
			$lang = $mdui['lang'];
			if (! isset($oldMDUIElements[$mdelement]) )
				$oldMDUIElements[$mdelement] = array();
			if (! isset($oldMDUIElements[$mdelement][$lang]) )
				$oldMDUIElements[$mdelement][$lang] = array();
			$oldMDUIElements[$mdelement][$lang][$size] = array('value' => $mdui['data'], 'height' => $mdui['height'], 'width' => $mdui['width']);
		}
		if (isset($oldMDUIElements)) {
			$EntityDescriptor = $this->getEntityDescriptor($this->newXml);

			# Find md:IDPSSODescriptor in XML
			$child = $EntityDescriptor->firstChild;
			$SSODescriptor = false;
			while ($child && ! $SSODescriptor) {
				if ($child->nodeName == 'md:'.$type.'Descriptor')
					$SSODescriptor = $child;
				$child = $child->nextSibling;
			}
			if ($SSODescriptor) {
				$changed = false;
				$child = $SSODescriptor->firstChild;
				$Extensions = false;
				while ($child && ! $Extensions) {
					switch ($child->nodeName) {
						case 'ds:Signature' :
							break;
						case 'md:Extensions' :
							$Extensions = $child;
							break;
						default :
							$Extensions = $this->newXml->createElement('md:Extensions');
							$SSODescriptor->insertBefore($Extensions, $child);
					}
					$child = $child->nextSibling;
				}
				if (! $Extensions) {
					$Extensions = $this->newXml->createElement('md:Extensions');
					$SSODescriptor->appendChild($Extensions);
				}
				$child = $Extensions->firstChild;
				$beforeChild = false;
				$UUInfo = false;
				$mduiFound = false;
				while ($child && ! $UUInfo) {
					switch ($child->nodeName) {
						case 'mdui:UIInfo' :
							$mduiFound = true;
							$UUInfo = $child;
							break;
						case 'mdui:DiscoHints' :
							$beforeChild = $beforeChild ? $beforeChild : $child;
							$mduiFound = true;
							break;
					}
					$child = $child->nextSibling;
				}
				if (! $UUInfo ) {
					$UUInfo = $this->newXml->createElement('mdui:UIInfo');
					if ($beforeChild)
						$Extensions->insertBefore($UUInfo, $beforeChild);
					else
						$Extensions->appendChild($UUInfo);
				}
				if (! $mduiFound) {
					$EntityDescriptor->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:mdui', 'urn:oasis:names:tc:SAML:metadata:ui');
				}
				# Find mdui:* in XML
				$child = $UUInfo->firstChild;
				while ($child) {
					if ($child->nodeType != 8) {
						$lang = $child->getAttribute('xml:lang');
						$height = $child->getAttribute('height') ? $child->getAttribute('height') : 0;
						$width = $child->getAttribute('width') ? $child->getAttribute('width') : 0;
						$element = $child->nodeName;
						if (isset($oldMDUIElements[$element][$lang])) {
							$size = $height.'x'.$width;
							if (isset($oldMDUIElements[$element][$lang][$size])) {
								unset($oldMDUIElements[$element][$lang][$size]);
							}
						}
					}
					$child = $child->nextSibling;
				}
				$mduiAddHandler = $this->metaDb->prepare('INSERT INTO Mdui (entity_id, type, lang, height, width, element, data) VALUES (:Id, :Type, :Lang, :Height, :Width, :Element, :Data);');
				$mduiAddHandler->bindParam(':Id', $this->dbIdNr);
				$mduiAddHandler->bindParam(':Type', $type);
				foreach ($oldMDUIElements as $element => $data) {
					foreach ($data as $lang => $sizeValue) {
						foreach ($sizeValue as $size => $value) {
							# Add if missing
							$MduiElement = $this->newXml->createElement($element, $value['value']);
							if ($lang != '')
								$MduiElement->setAttribute('xml:lang', $lang);
							if ($size != '0x0') {
								$MduiElement->setAttribute('height', $value['height']);
								$MduiElement->setAttribute('width', $value['width']);
							}
							$UUInfo->appendChild($MduiElement);
							$mduiAddHandler->bindParam(':Lang', $lang);
							$mduiAddHandler->bindParam(':Height', $value['height']);
							$mduiAddHandler->bindParam(':Width', $value['width']);
							$mduiAddHandler->bindParam(':Element', $element);
							$mduiAddHandler->bindParam(':Data', $value['value']);
							$mduiAddHandler->execute();
						}
					}
				}
			}
		}
	}
	private function mergeDiscoHints() {
		if ( !$this->oldExists)
			return;
		$mduiHandler = $this->metaDb->prepare("SELECT element, data FROM Mdui WHERE entity_id = :Id AND type = 'IDPDisco' ORDER BY element;");
		$mduiHandler->bindParam(':Id', $this->dbOldIdNr);
		$mduiHandler->execute();
		while ($mdui = $mduiHandler->fetch(PDO::FETCH_ASSOC)) {
			$mdelement = 'mdui:'.$mdui['element'];
			$value = $mdui['data'];
			if (! isset($oldMDUIElements[$mdelement]) )
				$oldMDUIElements[$mdelement] = array();
			$oldMDUIElements[$mdelement][$value] = true;
		}
		if (isset($oldMDUIElements)) {
			$EntityDescriptor = $this->getEntityDescriptor($this->newXml);

			# Find md:IDPSSODescriptor in XML
			$child = $EntityDescriptor->firstChild;
			$SSODescriptor = false;
			while ($child && ! $SSODescriptor) {
				if ($child->nodeName == 'md:IDPSSODescriptor')
					$SSODescriptor = $child;
				$child = $child->nextSibling;
			}
			if ($SSODescriptor) {
				$changed = false;
				$child = $SSODescriptor->firstChild;
				$Extensions = false;
				while ($child && ! $Extensions) {
					switch ($child->nodeName) {
						case 'ds:Signature' :
							break;
						case 'md:Extensions' :
							$Extensions = $child;
							break;
						default :
							$Extensions = $this->newXml->createElement('md:Extensions');
							$SSODescriptor->insertBefore($Extensions, $child);
					}
					$child = $child->nextSibling;
				}
				if (! $Extensions) {
					$Extensions = $this->newXml->createElement('md:Extensions');
					$SSODescriptor->appendChild($Extensions);
				}
				$child = $Extensions->firstChild;
				$beforeChild = false;
				$DiscoHints = false;
				$mduiFound = false;
				while ($child && ! $DiscoHints) {
					switch ($child->nodeName) {
						case 'mdui:UIInfo' :
							$mduiFound = true;
							break;
						case 'mdui:DiscoHints' :
							$UUInfo = $child;
							$mduiFound = true;
							break;
					}
					$child = $child->nextSibling;
				}
				if (! $DiscoHints ) {
					$DiscoHints = $this->newXml->createElement('mdui:DiscoHints');
					$Extensions->appendChild($DiscoHints);
				}
				if (! $mduiFound) {
					$EntityDescriptor->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:mdui', 'urn:oasis:names:tc:SAML:metadata:ui');
				}
				# Find mdui:* in XML
				$child = $DiscoHints->firstChild;
				while ($child) {
					$element = $child->nodeName;
					$value = $child->NodeValue;
					if (isset($oldMDUIElements[$element][$value])) {
						unset($oldMDUIElements[$element][$value]);
					}
					$child = $child->nextSibling;
				}
				$mduiAddHandler = $this->metaDb->prepare("INSERT INTO Mdui (entity_id, type, element, data) VALUES (:Id, 'IDPDisco', :Element, :Data);");
				$mduiAddHandler->bindParam(':Id', $this->dbIdNr);
				foreach ($oldMDUIElements as $element => $valueArray) {
					foreach ($valueArray as $value => $true) {
						# Add if missing
						$MduiElement = $this->newXml->createElement($element, $value);
						$DiscoHints->appendChild($MduiElement);
						$mduiAddHandler->bindParam(':Element', $element);
						$mduiAddHandler->bindParam(':Data', $value);
						$mduiAddHandler->execute();
					}
				}
			}
		}
	}
	private function mergeAttributeConsumingService() {
		if ( !$this->oldExists)
			return;

		$serviceIndexHandler = $this->metaDb->prepare('SELECT Service_index FROM AttributeConsumingService WHERE entity_id = :Id ORDER BY Service_index;');
		$serviceIndexHandler->bindParam(':Id', $this->dbOldIdNr);

		$serviceElementHandler = $this->metaDb->prepare('SELECT element, lang, data FROM AttributeConsumingService_Service WHERE entity_id = :Id AND Service_index = :Index ORDER BY element DESC, lang;');
		$serviceElementHandler->bindParam(':Id', $this->dbOldIdNr);
		$serviceElementHandler->bindParam(':Index', $index);

		$requestedAttributeHandler = $this->metaDb->prepare('SELECT FriendlyName, Name, NameFormat, isRequired FROM AttributeConsumingService_RequestedAttribute WHERE entity_id = :Id AND Service_index = :Index ORDER BY isRequired DESC, FriendlyName;');
		$requestedAttributeHandler->bindParam(':Id', $this->dbOldIdNr);
		$requestedAttributeHandler->bindParam(':Index', $index);

		$serviceIndexHandler->execute();

		while ($serviceIndex = $serviceIndexHandler->fetch(PDO::FETCH_ASSOC)) {
			$index = $serviceIndex['Service_index'];
			$oldServiceIndexes[$index] = $index;
			$oldServiceElements[$index] = array();
			$oldRequestedAttributes[$index] = array();
			$serviceElementHandler->execute();
			while ($serviceElement = $serviceElementHandler->fetch(PDO::FETCH_ASSOC)) {
				$oldServiceElements[$index][$serviceElement['element']][$serviceElement['lang']] = $serviceElement['data'];
			}
			$requestedAttributeHandler->execute();
			while ($requestedAttribute = $requestedAttributeHandler->fetch(PDO::FETCH_ASSOC)) {
				$oldRequestedAttributes[$index][$requestedAttribute['Name']] = array('isRequired' => $requestedAttribute['isRequired'], 'friendlyName' => $requestedAttribute['FriendlyName'], 'nameFormat' => $requestedAttribute['NameFormat']);
			}
		}

		$EntityDescriptor = $this->getEntityDescriptor($this->newXml);

		# Find md:IDPSSODescriptor in XML
		$child = $EntityDescriptor->firstChild;
		$SSODescriptor = false;
		while ($child && ! $SSODescriptor) {
			if ($child->nodeName == 'md:SPSSODescriptor')
				$SSODescriptor = $child;
			$child = $child->nextSibling;
		}
		if ($SSODescriptor && isset($oldServiceIndexes)) {
			$addServiceIndexHandler = $this->metaDb->prepare('INSERT INTO AttributeConsumingService (entity_id, Service_index) VALUES (:Id, :Index);');
			$addServiceIndexHandler->bindParam(':Id', $this->dbIdNr);
			$addServiceIndexHandler->bindParam(':Index', $index);

			$serviceElementAddHandler = $this->metaDb->prepare('INSERT INTO AttributeConsumingService_Service (entity_id, Service_index, element, lang, data) VALUES ( :Id, :Index, :Element, :Lang, :Data );');
			$serviceElementAddHandler->bindParam(':Id', $this->dbIdNr);
			$serviceElementAddHandler->bindParam(':Index', $index);
			$serviceElementAddHandler->bindParam(':Lang', $lang);
			$serviceElementAddHandler->bindParam(':Data', $value);

			$requestedAttributeAddHandler = $this->metaDb->prepare('INSERT INTO AttributeConsumingService_RequestedAttribute (entity_id, Service_index, FriendlyName, Name, NameFormat, isRequired) VALUES ( :Id, :Index, :FriendlyName, :Name, :NameFormat, :IsRequired);');
			$requestedAttributeAddHandler->bindParam(':Id', $this->dbIdNr);
			$requestedAttributeAddHandler->bindParam(':Index', $index);
			$requestedAttributeAddHandler->bindParam(':FriendlyName', $friendlyName);
			$requestedAttributeAddHandler->bindParam(':Name', $name);
			$requestedAttributeAddHandler->bindParam(':NameFormat', $nameFormat);
			$requestedAttributeAddHandler->bindParam(':IsRequired', $isRequired);

			$child = $SSODescriptor->firstChild;
			while ($child) {
				if ($child->nodeName == 'md:AttributeConsumingService' ) {
					$index = $child->getAttribute('index');

					$AttributeConsumingService = $child;
					$servicechild = $AttributeConsumingService->firstChild;
					$nextOrder = 1;
					while ($servicechild) {
						switch ($servicechild->nodeName) {
							case 'md:ServiceName' :
								$lang = $servicechild->getAttribute('xml:lang');
								if (isset($oldServiceElements[$index]['ServiceName'][$lang]))
									unset ($oldServiceElements[$index]['ServiceName'][$lang]);
								break;
							case 'md:ServiceDescription' :
								if ($nextOrder < 2) {
									$serviceElementAddHandler->bindValue(':Element', 'md:ServiceName');
									foreach ($oldServiceElements[$index]['ServiceName'] as $lang => $value) {
										$AttributeConsumingServiceElement = $this->newXml->createElement('md:ServiceName', $value);
										$AttributeConsumingServiceElement->setAttribute('xml:lang', $lang);
										$AttributeConsumingService->insertBefore($AttributeConsumingServiceElement, $servicechild);
										$serviceElementAddHandler->execute();
										unset ($oldServiceElements[$index]['ServiceName'][$lang]);
									}
									unset($oldServiceElements[$index]['ServiceName']);
									$nextOrder = 2;
								}
								$lang = $servicechild->getAttribute('xml:lang');
								if (isset($oldServiceElements[$index]['ServiceDescription'][$lang]))
									unset ($oldServiceElements[$index]['ServiceDescription'][$lang]);
								break;
							case 'md:RequestedAttribute' :
								if ($nextOrder < 3) {
									if(isset($oldServiceElements[$index]['ServiceName'])) {
										$serviceElementAddHandler->bindValue(':Element', 'ServiceName');
										foreach ($oldServiceElements[$index]['ServiceName'] as $lang => $value) {
											$AttributeConsumingServiceElement = $this->newXml->createElement('md:ServiceName', $value);
											$AttributeConsumingServiceElement->setAttribute('xml:lang', $lang);
											$AttributeConsumingService->insertBefore($AttributeConsumingServiceElement, $servicechild);
											$serviceElementAddHandler->execute();
											unset ($oldServiceElements[$index]['ServiceName'][$lang]);
										}
										unset($oldServiceElements[$index]['ServiceName']);
									}
									if (isset($oldServiceElements[$index]['ServiceDescription'])) {
										$serviceElementAddHandler->bindValue(':Element', 'ServiceDescription');
										foreach ($oldServiceElements[$index]['ServiceDescription'] as $lang => $value) {
											$AttributeConsumingServiceElement = $this->newXml->createElement('md:ServiceDescription', $value);
											$AttributeConsumingServiceElement->setAttribute('xml:lang', $lang);
											$AttributeConsumingService->insertBefore($AttributeConsumingServiceElement, $servicechild);
											$serviceElementAddHandler->execute();
											unset ($oldServiceElements[$index]['ServiceDescription'][$lang]);
										}
										unset ($oldServiceElements[$index]['ServiceDescription']);
									}
									$nextOrder = 3;
								}
								$name = $servicechild->getAttribute('Name');
								if (isset($oldRequestedAttributes[$index][$name]))
									unset ($oldRequestedAttributes[$index][$name]);
								break;
							default :
								printf('%s<br>', $servicechild->nodeName);
						}
						$servicechild = $servicechild->nextSibling;
					}
					# Add what is left of this index at the end of this Service
					if(isset($oldServiceElements[$index]['ServiceName'])) {
						$serviceElementAddHandler->bindValue(':Element', 'ServiceName');
						foreach ($oldServiceElements[$index]['ServiceName'] as $lang => $value) {
							$AttributeConsumingServiceElement = $this->newXml->createElement('md:ServiceName', $value);
							$AttributeConsumingServiceElement->setAttribute('xml:lang', $lang);
							$AttributeConsumingService->appendChild($AttributeConsumingServiceElement);
							$serviceElementAddHandler->execute();
						}
					}
					if (isset($oldServiceElements[$index]['ServiceDescription'])) {
						$serviceElementAddHandler->bindValue(':Element', 'ServiceDescription');
						foreach ($oldServiceElements[$index]['ServiceDescription'] as $lang => $value) {
							$AttributeConsumingServiceElement = $this->newXml->createElement('md:ServiceDescription', $value);
							$AttributeConsumingServiceElement->setAttribute('xml:lang', $lang);
							$AttributeConsumingService->appendChild($AttributeConsumingServiceElement);
							$serviceElementAddHandler->execute();
						}
					}
					unset($oldServiceElements[$index]);

					foreach ($oldRequestedAttributes[$index] as $name => $data) {
						$friendlyName = $data['friendlyName'];
						$nameFormat =  $data['nameFormat'];
						$isRequired = $data['isRequired'];

						$AttributeConsumingServiceElement = $this->newXml->createElement('md:RequestedAttribute');
						if ($friendlyName != '' )
							$AttributeConsumingServiceElement->setAttribute('FriendlyName', $friendlyName);
						$AttributeConsumingServiceElement->setAttribute('Name', $name);
						if ($nameFormat != '' )
							$AttributeConsumingServiceElement->setAttribute('NameFormat', $nameFormat);
						$AttributeConsumingServiceElement->setAttribute('isRequired', $isRequired ? 'true' : 'false');
						$AttributeConsumingService->appendChild($AttributeConsumingServiceElement);
						$requestedAttributeAddHandler->execute();
					}
					unset ($oldRequestedAttributes[$index]);
					unset($oldServiceIndexes[$index]);
				}
				$child = $child->nextSibling;
			}
			foreach ($oldServiceIndexes as $index) {
				$AttributeConsumingService = $this->newXml->createElement('md:AttributeConsumingService');
				$AttributeConsumingService->setAttribute('index', $index);
				$SSODescriptor->appendChild($AttributeConsumingService);
				$addServiceIndexHandler->execute();

				if(isset($oldServiceElements[$index]['ServiceName'])) {
					$serviceElementAddHandler->bindValue(':Element', 'ServiceName');
					foreach ($oldServiceElements[$index]['ServiceName'] as $lang => $value) {
						$AttributeConsumingServiceElement = $this->newXml->createElement('md:ServiceName', $value);
						$AttributeConsumingServiceElement->setAttribute('xml:lang', $lang);
						$AttributeConsumingService->appendChild($AttributeConsumingServiceElement);
						$serviceElementAddHandler->execute();
					}
				}
				if (isset($oldServiceElements[$index]['ServiceDescription'])) {
					$serviceElementAddHandler->bindValue(':Element', 'ServiceDescription');
					foreach ($oldServiceElements[$index]['ServiceDescription'] as $lang => $value) {
						$AttributeConsumingServiceElement = $this->newXml->createElement('md:ServiceDescription', $value);
						$AttributeConsumingServiceElement->setAttribute('xml:lang', $lang);
						$AttributeConsumingService->appendChild($AttributeConsumingServiceElement);
						$serviceElementAddHandler->execute();
					}
				}
				unset($oldServiceElements[$index]);

				foreach ($oldRequestedAttributes[$index] as $name => $data) {
					$friendlyName = $data['friendlyName'];
					$nameFormat =  $data['nameFormat'];
					$isRequired = $data['isRequired'];

					$AttributeConsumingServiceElement = $this->newXml->createElement('md:RequestedAttribute');
					if ($friendlyName != '' )
						$AttributeConsumingServiceElement->setAttribute('FriendlyName', $friendlyName);
					$AttributeConsumingServiceElement->setAttribute('Name', $name);
					if ($nameFormat != '' )
						$AttributeConsumingServiceElement->setAttribute('NameFormat', $nameFormat);
					$AttributeConsumingServiceElement->setAttribute('isRequired', $isRequired ? 'true' : 'false');
					$AttributeConsumingService->appendChild($AttributeConsumingServiceElement);
					$requestedAttributeAddHandler->execute();
				}
				unset($oldRequestedAttributes[$index]);
				unset($oldServiceIndexes[$index]);
			}
		}
	}

	private function mergeOrganization() {
		if ( !$this->oldExists)
			return;
		$organizationHandler = $this->metaDb->prepare('SELECT element, lang, data FROM Organization WHERE entity_id = :Id ORDER BY element, lang;');
		$organizationHandler->bindParam(':Id', $this->dbOldIdNr);
		$organizationHandler->execute();
		while ($organization = $organizationHandler->fetch(PDO::FETCH_ASSOC)) {
			$order = $this->orderOrganization['md:'.$organization['element']];
			$oldElements[$order][] = $organization;
		}
		if (isset($oldElements)) {
			$EntityDescriptor = $this->getEntityDescriptor($this->newXml);

			# Find md:Extensions in XML
			$child = $EntityDescriptor->firstChild;
			$Organization = false;
			while ($child && ! $Organization) {
				switch ($child->nodeName) {
					case 'md:Organization' :
						$Organization = $child;
						break;
					case 'md:ContactPerson' :
					case 'md:AdditionalMetadataLocation' :
						$Organization = $this->newXml->createElement('md:Organization');
						$EntityDescriptor->insertBefore($Organization, $child);
						break;
				}
				$child = $child->nextSibling;
			}

			if (! $Organization) {
				# Add if missing
				$Organization = $this->newXml->createElement('md:Organization');
				$EntityDescriptor->appendChild($Organization);
			}

			# Find md:Organization* in XML
			$child = $Organization->firstChild;
			$nextOrder = 1;
			while ($child) {
				if ($child->nodeType != 8) {
					$order = $this->orderOrganization[$child->nodeName];
					while ($order > $nextOrder) {
						if (isset($oldElements[$nextOrder])) {
							foreach ($oldElements[$nextOrder] as $index => $element) {
								$lang = $element['lang'];
								$elementmd = 'md:'.$element['element'];
								$value = $element['data'];
								$OrganizationElement = $this->newXml->createElement($elementmd);
								$OrganizationElement->setAttribute('xml:lang', $lang);
								$OrganizationElement->nodeValue = $value;
								$Organization->insertBefore($OrganizationElement, $child);
								unset($oldElements[$nextOrder][$index]);
							}
						}
						$nextOrder++;
					}
					$lang = $child->getAttribute('xml:lang');
					$elementmd = $child->nodeName;
					if (isset($oldElements[$order])) {
						foreach ($oldElements[$order] as $index => $element) {
							if ($element['lang'] == $lang && 'md:'.$element['element'] == $elementmd) {
								unset ($oldElements[$order][$index]);
							}
						}
					}
				}
				$child = $child->nextSibling;
			}
			while ($nextOrder < 10) {
				if (isset($oldElements[$nextOrder])) {
					foreach ($oldElements[$nextOrder] as $element) {
						$lang = $element['lang'];
						$elementmd = 'md:'.$element['element'];
						$value = $element['data'];
						$OrganizationElement = $this->newXml->createElement($elementmd);
						$OrganizationElement->setAttribute('xml:lang', $lang);
						$OrganizationElement->nodeValue = $value;
						$Organization->appendChild($OrganizationElement);
					}
				}
				$nextOrder++;
			}
		}
	}
	private function mergeContactPersons() {
		if ( !$this->oldExists)
			return;
		$contactPersonHandler = $this->metaDb->prepare('SELECT * FROM ContactPerson WHERE entity_id = :Id;');
		$contactPersonHandler->bindParam(':Id', $this->dbOldIdNr);
		$contactPersonHandler->execute();
		while ($contactPerson = $contactPersonHandler->fetch(PDO::FETCH_ASSOC)) {
			$oldContactPersons[$contactPerson['contactType']] = array (
				'subcontactType' => ($contactPerson['subcontactType'] == 'security') ? 'http://refeds.org/metadata/contactType/security' : '',
				1 => array('part' => 'md:Company', 'value' => $contactPerson['company']),
				2 => array('part' => 'md:GivenName', 'value' => $contactPerson['givenName']),
				3 => array('part' => 'md:SurName', 'value' => $contactPerson['surName']),
				4 => array('part' => 'md:EmailAddress', 'value' => $contactPerson['emailAddress']),
				5 => array('part' => 'md:TelephoneNumber', 'value' => $contactPerson['telephoneNumber']),
				6 => array('part' => 'md:Extensions',  'value' => $contactPerson['extensions']));
		}
		if (isset($oldContactPersons)) {
			$EntityDescriptor = $this->getEntityDescriptor($this->newXml);

			# Find md:Extensions in XML
			$child = $EntityDescriptor->firstChild;
			$ContactPerson = false;
			while ($child) {
				switch ($child->nodeName) {
					case 'md:ContactPerson' :
						$type = $child->getAttribute('contactType');
						if (isset($oldContactPersons[$type])) {
							$subchild = $child->firstChild;
							$nextOrder = 1;
							$order = 1;
							while ($subchild) {
								$order = $this->orderContactPerson[$subchild->nodeName];
								while ($order > $nextOrder) {
									if (!empty($oldContactPersons[$type][$nextOrder]['value'])) {
										$ContactPersonElement = $this->newXml->createElement($oldContactPersons[$type][$nextOrder]['part']);
										$ContactPersonElement->nodeValue = $oldContactPersons[$type][$nextOrder]['value'];
										$child->insertBefore($ContactPersonElement, $subchild);
									}
									$nextOrder++;
								}
								$subchild = $subchild->nextSibling;
								$nextOrder++;
							}
							while ($nextOrder < 7) {
								if (!empty($oldContactPersons[$type][$nextOrder]['value'])) {
									$ContactPersonElement = $this->newXml->createElement($oldContactPersons[$type][$nextOrder]['part']);
									$ContactPersonElement->nodeValue = $oldContactPersons[$type][$nextOrder]['value'];
									$child->appendChild($ContactPersonElement);
								}
								$nextOrder++;
							}
							unset($oldContactPersons[$type]);
						}
						break;
					case 'md:AdditionalMetadataLocation' :
						foreach ($oldContactPersons as $type => $oldContactPerson) {
							$ContactPerson = $this->newXml->createElement('md:ContactPerson');
							$ContactPerson->setAttribute('contactType', $type);
							if ($oldContactPerson['subcontactType']) {
								$EntityDescriptor->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:remd', 'http://refeds.org/metadata');
								$ContactPerson->setAttribute('remd:contactType', $oldContactPerson['subcontactType']);
							}
							$EntityDescriptor->insertBefore($ContactPerson, $child);
							$nextOrder = 1;
							while ($nextOrder < 7) {
								if (!empty($oldContactPerson[$nextOrder]['value'])) {
									$ContactPersonElement = $this->newXml->createElement($oldContactPerson[$nextOrder]['part']);
									$ContactPersonElement->nodeValue = $oldContactPerson[$nextOrder]['value'];
									$ContactPerson->appendChild($ContactPersonElement);
								}
								$nextOrder++;
							}
						}
						break;
				}
				$child = $child->nextSibling;
			}
			foreach ($oldContactPersons as $type => $oldContactPerson) {
				$ContactPerson = $this->newXml->createElement('md:ContactPerson');
				$ContactPerson->setAttribute('contactType', $type);
				if ($oldContactPerson['subcontactType']) {
					$EntityDescriptor->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:remd', 'http://refeds.org/metadata');
					$ContactPerson->setAttribute('remd:contactType', $oldContactPerson['subcontactType']);
				}
				$EntityDescriptor->appendChild($ContactPerson);
				$nextOrder = 1;
				while ($nextOrder < 7) {
					if (!empty($oldContactPerson[$nextOrder]['value'])) {
						$ContactPersonElement = $this->newXml->createElement($oldContactPerson[$nextOrder]['part']);
						$ContactPersonElement->nodeValue = $oldContactPerson[$nextOrder]['value'];
						$ContactPerson->appendChild($ContactPersonElement);
					}
					$nextOrder++;
				}
			}
		}
	}

	public function removeSSO($type) {
		switch ($type) {
			case 'SP' :
				$SSODescriptor = 'md:SPSSODescriptor';
				break;
			case 'IdP' :
				$SSODescriptor = 'md:IDPSSODescriptor';
				break;
			default :
				printf ("Unknown type : %s", $type);
				return;
		}
		$EntityDescriptor = $this->getEntityDescriptor($this->newXml);

		# Find SSODecriptor in XML
		$child = $EntityDescriptor->firstChild;
		while ($child) {
			if ($child->nodeName == $SSODescriptor) {
				$EntityDescriptor->removeChild($child);
			}
			$child = $child->nextSibling;
		}
		$this->saveXML();
	}
	public function removeKey($type, $use, $serialNumber) {
		switch ($type) {
			case 'SPSSO' :
				$Descriptor = 'md:SPSSODescriptor';
				break;
			case 'IDPSSO' :
				$Descriptor = 'md:IDPSSODescriptor';
				break;
		}
		$EntityDescriptor = $this->getEntityDescriptor($this->newXml);

		# Find SSODecriptor in XML
		$child = $EntityDescriptor->firstChild;
		$SSODescriptor = false;
		while ($child && ! $SSODescriptor) {
			if ($child->nodeName == $Descriptor) {
				$SSODescriptor = $child;
			}
			$child = $child->nextSibling;
		}
		if ($SSODescriptor) {
			$child = $SSODescriptor->firstChild;
			$removeKeyDescriptor = false;
			$changed = false;
			while ($child) {
				// Loop thrue all KeyDescriptor:s not just the first one!
				if ($child->nodeName == 'md:KeyDescriptor') {
					$usage = $child->getAttribute('use') ? $child->getAttribute('use') : 'both';
					if ( $usage == $use ) {
						$KeyDescriptor = $child; // Save to be able to remove this KeyDescriptor
						$descriptorChild = $KeyDescriptor->firstChild;
						while ($descriptorChild && !$removeKeyDescriptor) {
							if ($descriptorChild->nodeName == 'ds:KeyInfo') {
								$infoChild = $descriptorChild->firstChild;
								while ($infoChild && !$removeKeyDescriptor) {
									if ($infoChild->nodeName == 'ds:X509Data') {
										$x509Child = $infoChild->firstChild;
										while ($x509Child&& !$removeKeyDescriptor) {
											if ($x509Child->nodeName == 'ds:X509Certificate') {
												$cert = "-----BEGIN CERTIFICATE-----\n" . chunk_split(str_replace(array(' ',"\n") ,array('',''),trim($x509Child->textContent)),64) . "-----END CERTIFICATE-----\n";
												if ($cert_info = openssl_x509_parse( $cert)) {
													if ($cert_info['serialNumber'] == $serialNumber)
														$removeKeyDescriptor = true;
												}
											}
											$x509Child = $x509Child->nextSibling;
										}
									}
									$infoChild = $infoChild->nextSibling;
								}
							}
							$descriptorChild = $descriptorChild->nextSibling;
						}
					}
				}
				$child = $child->nextSibling;
				// Remove
				if ($removeKeyDescriptor) {
					$SSODescriptor->removeChild($KeyDescriptor);
					$keyInfoDeleteHandler = $this->metaDb->prepare('DELETE FROM KeyInfo WHERE entity_id = :Id AND `type` = :Type AND `use` = :Use AND `serialNumber` = :SerialNumber;');
					$keyInfoDeleteHandler->bindParam(':Id', $this->dbIdNr);
					$keyInfoDeleteHandler->bindParam(':Type', $type);
					$keyInfoDeleteHandler->bindParam(':Use', $use);
					$keyInfoDeleteHandler->bindParam(':SerialNumber', $serialNumber);
					$keyInfoDeleteHandler->execute();
					// Reset flag for next KeyDescriptor
					$removeKeyDescriptor = false;
					$changed = true;
				}
			}
			if ($changed) {
				$this->saveXML();
			}
		}
	}

	public function saveXML() {
		$entityHandler = $this->metaDb->prepare('UPDATE Entities SET xml = :Xml WHERE id = :Id;');
		$entityHandler->bindParam(':Id', $this->dbIdNr);
		$entityHandler->bindValue(':Xml', $this->newXml->saveXML());
		$entityHandler->execute();
	}

	private function getEntityDescriptor($xml) {
		$child = $xml->firstChild;
		while ($child) {
			if ($child->nodeName == "md:EntityDescriptor") {
				return $child;
			}
			$child = $child->nextSibling;
		}
		return false;
	}
	private function showLangSelector($langValue) {
		print "\n".'              <select name="lang">';
		foreach ($this->langCodes as $lang => $descr) {
			if ($lang == $langValue)
				printf('%s                <option value="%s" selected>%s</option>', "\n",  $lang, $descr);
			else
				printf('%s                <option value="%s">%s</option>', "\n",  $lang, $descr);
		}
		print "\n              </select>\n";
	}
}