<?php
Class Metadata {
	# Setup
	function __construct() {
		$this->result = '';
		$this->warning = '';
		$this->error = '';
		$this->errorNB = '';

		$this->isIdP = false;
		$this->isSP = false;
		$this->registrationInstant = false;

		$a = func_get_args();
		$i = func_num_args();
		if (method_exists($this,$f='__construct'.$i)) {
				include $a[0] . '/config.php';
				include $a[0] . '/include/common.php';
				try {
					$this->metaDb = new PDO("mysql:host=$dbServername;dbname=$dbName", $dbUsername, $dbPassword);
					// set the PDO error mode to exception
					$this->metaDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				} catch(PDOException $e) {
					echo "Error: " . $e->getMessage();
				}
				call_user_func_array(array($this,$f),$a);
		}
		$this->startTimer = time();
	}

	private function __construct1($baseDir) {
		$this->entityID = false;
		$this->entityExists = false;
	}

	private function __construct2($baseDir, $entity_id) {
		$entityHandler = $this->metaDb->prepare('SELECT `id`, `entityID`, `status`, `xml` FROM Entities WHERE `id` = :Id;');
		$entityHandler->bindValue(':Id', $entity_id);
		$entityHandler->execute();
		if ($entity = $entityHandler->fetch(PDO::FETCH_ASSOC)) {
			$this->entityExists = true;
			$this->xml = new DOMDocument;
			$this->xml->preserveWhiteSpace = false;
			$this->xml->formatOutput = true;
			$this->xml->loadXML($entity['xml']);
			$this->xml->encoding = 'UTF-8';
			$this->dbIdNr = $entity['id'];
			$this->status = $entity['status'];
			$this->entityID = $entity['entityID'];
		} else {
			$this->entityExists = false;
			$this->entityID = 'Unknown';
		}
	}

	private function __construct3($baseDir, $entityId = '', $entityStatus = '') {
		$this->entityID = $entityId;

		switch (strtolower($entityStatus)) {
			case 'prod' :
				# In production metadata
				$this->status = 1;
				break;
			case 'requested' :
				# Request sent to OPS to be added.
				$this->status = 2;
				break;
			case 'new' :
			default :
				# New entity/updated entity
				$this->status = 3;
		}

		$entityHandler = $this->metaDb->prepare('SELECT `id`, `xml` FROM Entities WHERE `entityId` = :Id AND `status` = :Status;');
		$entityHandler->bindValue(':Id', $entityId);
		$entityHandler->bindValue(':Status', $this->status);
		$entityHandler->execute();
			if ($entity = $entityHandler->fetch(PDO::FETCH_ASSOC)) {
			$this->entityExists = true;
			$this->xml = new DOMDocument;
			$this->xml->preserveWhiteSpace = false;
			$this->xml->formatOutput = true;
			$this->xml->loadXML($entity['xml']);
			$this->xml->encoding = 'UTF-8';
			$this->dbIdNr = $entity['id'];
		} else {
			$this->entityExists = false;
		}
	}

	private function addURL($url, $type) {
		//type
		// 1 Check reachable
		// 2 Check CoCo privacy
		$urlHandler = $this->metaDb->prepare('SELECT `type` FROM URLs WHERE `URL` = :Url;');
		$urlHandler->bindValue(':Url', $url);
		$urlHandler->execute();

		if ($currentType = $urlHandler->fetch(PDO::FETCH_ASSOC)) {
			if ($currentType['type'] > $type) {
				$type = $currentType['type'];
			}
			$urlUpdateHandler = $this->metaDb->prepare("UPDATE URLs SET `type` = :Type, `lastSeen` = NOW() WHERE `URL` = :Url;");
			$urlUpdateHandler->bindParam(':Url', $url);
			$urlUpdateHandler->bindParam(':Type', $type);
			$urlUpdateHandler->execute();
		} else {
			$urlAddHandler = $this->metaDb->prepare("INSERT INTO URLs (`URL`, `type`, `status`, `lastValidated`, `lastSeen`) VALUES (:Url, :Type, 10, '1972-01-01', NOW());");
			$urlAddHandler->bindParam(':Url', $url);
			$urlAddHandler->bindParam(':Type', $type);
			$urlAddHandler->execute();
		}
	}

	public function validateURLs($limit=10){
		$this->showProgress('validateURLs - start');
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, 'https://metadata.swamid.se/validate');

		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

		curl_setopt($ch, CURLINFO_HEADER_OUT, 1);

		$URLUpdateHandler = $this->metaDb->prepare("UPDATE URLs SET `lastValidated` = NOW(), `status` = :Status, `validationOutput` = :Result WHERE `URL` = :Url;");
		if ($limit > 10) {
			$sql = "SELECT `URL`, `type` FROM URLs WHERE `lastValidated` < ADDTIME(NOW(), '-7 0:0:0') OR (`status` > 0 AND `lastValidated` < ADDTIME(NOW(), '-6:0:0')) ORDER BY `lastValidated` LIMIT $limit;";
		} else {
			$sql = "SELECT `URL`, `type` FROM URLs WHERE `status` > 0 AND `lastValidated` < ADDTIME(NOW(), '-8:0:0') ORDER BY `lastValidated` LIMIT $limit;";
		}
		$URLHandler = $this->metaDb->prepare($sql);
		$URLHandler->execute();
		$count = 0;
		while ($URL = $URLHandler->fetch(PDO::FETCH_ASSOC)) {
			$URLUpdateHandler->bindValue(':Url', $URL['URL']);

			curl_setopt($ch, CURLOPT_URL, $URL['URL']);
			$continue = true;
			while ($continue) {
				$output = curl_exec($ch);
				if (curl_errno($ch)) {
					$URLUpdateHandler->bindValue(':Result', curl_error($ch));
					$URLUpdateHandler->bindValue(':Status', 3);
					$continue = false;
				} else {
					switch ($http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
						case 200 :
							switch ($URL['type']) {
								case 1 :
									$URLUpdateHandler->bindValue(':Result', 'Reachable');
									$URLUpdateHandler->bindValue(':Status', 0);
									break;
								case 2 :
									if (strpos ( $output, 'http://www.geant.net/uri/dataprotection-code-of-conduct/v1') > 1 ) {
										$URLUpdateHandler->bindValue(':Result', 'Policy OK');
										$URLUpdateHandler->bindValue(':Status', 0);
									} else {
										$URLUpdateHandler->bindValue(':Result', 'Policy missing link to http://www.geant.net/uri/dataprotection-code-of-conduct/v1');
										$URLUpdateHandler->bindValue(':Status', 1);
									}
									break;
							}
							$continue = false;
							break;
						case 403 :
							$URLUpdateHandler->bindValue(':Result', "Access denied. Can't check URL.");
							$URLUpdateHandler->bindValue(':Status', 2);
							$continue = false;
							break;
						case 404 :
							$URLUpdateHandler->bindValue(':Result', 'Page not found.');
							$URLUpdateHandler->bindValue(':Status', 2);
							$continue = false;
							break;
						case 503 :
							$URLUpdateHandler->bindValue(':Result', "Service Unavailable. Can't check URL.");
							$URLUpdateHandler->bindValue(':Status', 2);
							$continue = false;
							break;
						default :
							$URLUpdateHandler->bindValue(':Result', "Contact operation@swamid.se. Got code $http_code from web-server. Cant handle :-(");
							$URLUpdateHandler->bindValue(':Status', 2);
							$continue = false;
					}
				}
			}
			$URLUpdateHandler->execute();
			$count ++;
		}
		curl_close($ch);
		$this->showProgress('validateURLs - done');
		if ($limit > 10)
			printf ('Checked %d URL:s', $count);
	}

	# Import an XML  -> metadata.db
	public function importXML($xml) {
		$this->xml = new DOMDocument;
		$this->xml->preserveWhiteSpace = false;
		$this->xml->formatOutput = true;
		$this->xml->loadXML($xml);
		$this->xml->encoding = 'UTF-8';
		$this->cleanOutRoleDescriptor();
		$this->cleanOutAttribuesInIDPSSODescriptor();
		if ($this->entityExists && $this->status == 1) {
			# Update entity in database
			$entityHandlerUpdate = $this->metaDb->prepare('UPDATE Entities SET `isIdP` = 0, `isSP` = 0, `xml` = :Xml , `lastUpdated` = NOW() WHERE `entityId` = :Id AND `status` = :Status;');
			$entityHandlerUpdate->bindValue(':Id', $this->entityID);
			$entityHandlerUpdate->bindValue(':Status', $this->status);
			$entityHandlerUpdate->bindValue(':Xml', $this->xml->saveXML());
			$entityHandlerUpdate->execute();
			$this->result = "Updated in db.\n";
		} else {
			# Add new entity into database
			$entityHandlerInsert = $this->metaDb->prepare('INSERT INTO Entities (`entityId`, `isIdP`, `isSP`, `publishIn`, `status`, `xml`, `lastUpdated`) VALUES(:Id, 0, 0, 0, :Status, :Xml, NOW());');
			$entityHandlerInsert->bindValue(':Id', $this->entityID);
			$entityHandlerInsert->bindValue(':Status', $this->status);
			$entityHandlerInsert->bindValue(':Xml', $this->xml->saveXML());
			$entityHandlerInsert->execute();
			$this->result = "Added to db.\n";
			$this->dbIdNr = $this->metaDb->lastInsertId();
		}
		$this->entityExists = true;
	}

	# Creates / updates XML from Published into Draft
	public function createDraft() {
		if ($this->entityExists && $this->status == 1) {
			# Add new entity into database
			$entityHandlerInsert = $this->metaDb->prepare('INSERT INTO Entities (`entityId`, `isIdP`, `isSP`, `publishIn`, `status`, `xml`, `lastUpdated`) VALUES(:Id, 0, 0, 0, 3, :Xml, NOW());');
			$entityHandlerInsert->bindValue(':Id', $this->entityID);
			$entityHandlerInsert->bindValue(':Xml', $this->xml->saveXML());
			$entityHandlerInsert->execute();
			$this->result = "Added to db.\n";
			$this->dbIdNr = $this->metaDb->lastInsertId();
			$this->status = 3;
			return $this->dbIdNr;
		} else
			return false;
	}

	# Validate xml-code
	public function validateXML($verbose=false) {
		if (! $this->entityExists) {
			$this->result = "$this->entityID doesn't exist!!";
			return 1;
		}

		# Remove old ContactPersons / Organization from previus runs
		$this->metaDb->exec('DELETE FROM EntityAttributes WHERE `entity_id` = ' . $this->dbIdNr .';');
		$this->metaDb->exec('DELETE FROM Mdui WHERE `entity_id` = ' . $this->dbIdNr .';');
		$this->metaDb->exec('DELETE FROM KeyInfo WHERE `entity_id` = ' . $this->dbIdNr .';');
		$this->metaDb->exec('DELETE FROM AttributeConsumingService WHERE `entity_id` = ' . $this->dbIdNr .';');
		$this->metaDb->exec('DELETE FROM AttributeConsumingService_Service WHERE `entity_id` = ' . $this->dbIdNr .';');
		$this->metaDb->exec('DELETE FROM AttributeConsumingService_RequestedAttribute WHERE `entity_id` = ' . $this->dbIdNr .';');
		$this->metaDb->exec('DELETE FROM Organization WHERE `entity_id` = ' . $this->dbIdNr .';');
		$this->metaDb->exec('DELETE FROM ContactPerson WHERE `entity_id` = ' . $this->dbIdNr .';');
		$this->metaDb->exec('DELETE FROM EntityURLs WHERE `entity_id` = ' . $this->dbIdNr .';');
		$this->metaDb->exec('DELETE FROM Scopes WHERE `entity_id` = ' . $this->dbIdNr .';');
		$this->metaDb->exec('UPDATE Entities SET `isIdP` = 0, `isSP` = 0 WHERE `id` = ' . $this->dbIdNr .';');

		$SWAMID_5_1_30_error = false;
		$cleanOutSignature = false;
		# https://docs.oasis-open.org/security/saml/v2.0/saml-metadata-2.0-os.pdf 2.4.1
		$EntityDescriptor = $this->getEntityDescriptor($this->xml);
		$child = $EntityDescriptor->firstChild;
		while ($child) {
			#$this->showProgress($child->nodeName);
			switch ($child->nodeName) {
				case 'ds:Signature' :
					// Should not be in SWAMID-metadata
					$cleanOutSignature = true;
					break;
				case 'md:Extensions' :
					$this->parseExtensions($child);
					break;
				case 'md:RoleDescriptor' :
					//5.1.29 Identity Provider metadata MUST NOT include RoleDescriptor elements.
					$SWAMID_5_1_30_error = true;
					break;
				case 'md:IDPSSODescriptor' :
					$this->metaDb->exec('UPDATE Entities SET `isIdP` = 1 WHERE `id` = '. $this->dbIdNr);
					$this->parseIDPSSODescriptor($child);
					$this->isIdP = true;
					break;
				case 'md:SPSSODescriptor' :
		            $this->metaDb->exec('UPDATE Entities SET `isSP` = 1 WHERE `id` = '. $this->dbIdNr);
					$this->parseSPSSODescriptor($child);
					$this->isSP = true;
					break;
				#case 'md:AuthnAuthorityDescriptor' :
				case 'md:AttributeAuthorityDescriptor' :
					$this->parseAttributeAuthorityDescriptor($child);
					break;
				#case 'md:PDPDescriptor' :
				#case 'md:AffiliationDescriptor' :
				case 'md:Organization' :
					$this->parseOrganization($child);
					break;
				case 'md:ContactPerson' :
					$this->parseContactPerson($child);
					break;
				#case 'md:AdditionalMetadataLocation' :
				default :
					$this->result .= $child->nodeType == 8 ? '' : sprintf("%s missing in validator.\n", $child->nodeName);
			}
			$child = $child->nextSibling;
		}
		if ($cleanOutSignature) $this->cleanOutSignature();
		if ($SWAMID_5_1_30_error) {
			$this->cleanOutRoleDescriptor();
		}

		$resultHandler = $this->metaDb->prepare("UPDATE Entities SET `registrationInstant` = :RegistrationInstant, `validationOutput` = :validationOutput, `warnings` = :Warnings, `errors` = :Errors, `errorsNB` = :ErrorsNB, `xml` = :Xml, `lastValidated` = NOW() WHERE `id` = :Id;");
		$resultHandler->bindValue(':Id', $this->dbIdNr);
		$resultHandler->bindValue(':RegistrationInstant', $this->registrationInstant);
		$resultHandler->bindValue(':validationOutput', $this->result);
		$resultHandler->bindValue(':Warnings', $this->warning);
		$resultHandler->bindValue(':Errors', $this->error);
		$resultHandler->bindValue(':ErrorsNB', $this->errorNB);
		$resultHandler->bindValue(':Xml', $this->xml->saveXML());
		$resultHandler->execute();
	}

	# Extensions
	private function parseExtensions($data) {
		$child = $data->firstChild;
		while ($child) {
			switch ($child->nodeName) {
				case 'mdattr:EntityAttributes' :
					$this->parseExtensions_EntityAttributes($child);
					break;
				case 'mdrpi:RegistrationInfo' :
					$this->registrationInstant = $child->getAttribute('registrationInstant');
					break;
				case 'alg:DigestMethod' :
				case 'alg:SigningMethod' :
				case 'alg:SignatureMethod' :
					break;
				# Errors
				case 'idpdisc:DiscoveryResponse' :
					$this->error .= "DiscoveryResponse found in Extensions should be below SPSSODescriptor/Extensions.\n";
					break;
				case 'shibmd:Scope' :
					$this->error .= "Scope found in Extensions should be below IDPSSODescriptor/Extensions.\n";
					break;
				default :
					$this->result .= sprintf("Extensions->%s missing in validator.\n", $child->nodeName);
			}
			$child = $child->nextSibling;
		}
	}

	# Extensions -> EntityAttributes
	private function parseExtensions_EntityAttributes($data) {
		$child = $data->firstChild;
		while ($child) {
			switch ($child->nodeName) {
				case 'samla:Attribute' :
					$this->parseExtensions_EntityAttributes_Attribute($child);
					break;
				default :
					$this->result .= $child->nodeType == 8 ? '' : sprintf("Extensions->EntityAttributes->%s missing in validator.\n", $child->nodeName);
			}
			$child = $child->nextSibling;
		}
	}

	# Extensions -> EntityAttributes -> Attribute
	private function parseExtensions_EntityAttributes_Attribute($data) {
		$entityAttributeHandler = $this->metaDb->prepare('INSERT INTO EntityAttributes (`entity_id`, `type`, `attribute`) VALUES (:Id, :Type, :Value);');

		if ($data->getAttribute('NameFormat') == 'urn:oasis:names:tc:SAML:2.0:attrname-format:uri') {
			switch ($data->getAttribute('Name')) {
				case 'http://macedir.org/entity-category' :
					$attributeType = 'entity-category';
					break;
				case 'http://macedir.org/entity-category-support' :
					$attributeType = 'entity-category-support';
					break;
				case 'urn:oasis:names:tc:SAML:attribute:assurance-certification' :
					$attributeType = 'assurance-certification';
					break;
				case 'urn:oasis:names:tc:SAML:profiles:subject-id:req' :
					$attributeType = 'subject-id:req';
					break;
				case 'http://www.swamid.se/assurance-requirement' :
					$attributeType = 'swamid/assurance-requirement';
					break;
				default :
					$this->result .= sprintf("Unknown Name (%s) in Extensions/EntityAttributes/Attribute.\n", $data->getAttribute('Name'));
					$attributeType = $data->getAttribute('Name');
			}

			$entityAttributeHandler->bindValue(':Id', $this->dbIdNr);
			$entityAttributeHandler->bindValue(':Type', $attributeType);

			$child = $data->firstChild;
			while ($child) {
				if ($child->nodeName == 'samla:AttributeValue') {
					$entityAttributeHandler->bindValue(':Value', trim($child->textContent));
					$entityAttributeHandler->execute();
				} else {
					$this->result .= 'Extensions -> EntityAttributes -> Attribute -> ' . $child->nodeName . " saknas.\n";
				}
				$child = $child->nextSibling;
			}
		} else
			$this->result .= sprintf("Unknown NameFormat (%s) in Extensions/EntityAttributes/Attribute.\n", $data->getAttribute('NameFormat'));
	}

	#############
	# IDPSSODescriptor
	#############
	private function parseIDPSSODescriptor($data) {
		if ($data->getAttribute('errorURL'))
			$this->addEntityUrl('error', $data->getAttribute('errorURL'));

		$SWAMID_5_1_31_error = false;
		# https://docs.oasis-open.org/security/saml/v2.0/saml-metadata-2.0-os.pdf 2.4.1 + 2.4.2 + 2.4.3
		$child = $data->firstChild;
		while ($child) {
			switch ($child->nodeName) {
				# 2.4.1
				#case 'Signature' :
				case 'md:Extensions' :
					$this->parseIDPSSODescriptor_Extensions($child);
					break;
				case 'md:KeyDescriptor' :
					$this->parseKeyDescriptor($child, 'IDPSSO');
					break;
				# 2.4.2
				case 'md:ArtifactResolutionService' :
				case 'md:SingleLogoutService' :
				#case 'ManageNameIDService' :
					$this->checkSAMLEndpointURL($child,'IDPSSO');
					break;
				case 'md:NameIDFormat' :
					# Skippar då SWAMID inte använder denna del
					break;
				# 2.4.3
				case 'md:SingleSignOnService' :
				case 'md:NameIDMappingService' :
				case 'md:AssertionIDRequestService' :
					$this->checkSAMLEndpointURL($child,'IDPSSO');
					break;
				#case 'md:AttributeProfile' :
				case 'samla:Attribute' :
					# Should not be in SWAMID XML
					$SWAMID_5_1_31_error = true;
					break;
				default :
				$this->result .= $child->nodeType == 8 ? '' : sprintf("IDPSSODescriptor->%s missing in validator.\n", $child->nodeName);
			}
			$child = $child->nextSibling;
		}
		if ($SWAMID_5_1_31_error) {
			$this->cleanOutAttribuesInIDPSSODescriptor();
		}
		return $data;
	}

	private function parseIDPSSODescriptor_Extensions($data) {
		$ScopesHandler = $this->metaDb->prepare('INSERT INTO Scopes (`entity_id`, `scope`, `regexp`) VALUES (:Id, :Scope, :Regexp)');
		$ScopesHandler->bindValue(':Id', $this->dbIdNr);

		#xmlns:shibmd="urn:mace:shibboleth:metadata:1.0"
		$child = $data->firstChild;
		while ($child) {
			switch ($child->nodeName) {
				case 'shibmd:Scope' :
					switch (strtolower($child->getAttribute('regexp'))) {
						case 'false' :
						case '0' :
							$regexp = 0;
							break;
						case 'true' :
						case '1' :
							$regexp = 1;
							break;
						default :
							$this->result .= sprintf("IDPSSODescriptor->Extensions->Scope unknown value for regexp %s.\n", $child->getAttribute('regexp'));
							$regexp = -1;
					}
					$ScopesHandler->bindValue(':Scope', trim($child->textContent));
					$ScopesHandler->bindValue(':Regexp', $regexp);
					$ScopesHandler->execute();
					break;
				case 'mdui:UIInfo' :
					$this->parseSSODescriptor_Extensions_UIInfo($child, 'IDPSSO');
					break;
				case 'mdui:DiscoHints' :
					$this->parseIDPSSODescriptor_Extensions_DiscoHints($child);
					break;
				case 'mdattr:EntityAttributes' :
					$this->error .= "EntityAttributes found in IDPSSODescriptor/Extensions should be below Extensions at root level.\n";
					break;
				default :
					$this->result .= sprintf("IDPSSODescriptor->Extensions->%s missing in validator.\n", $child->nodeName);
			}
			$child = $child->nextSibling;
		}
	}

	#############
	# DiscoHints
	# Used by IDPSSODescriptor
	#############
	private function parseIDPSSODescriptor_Extensions_DiscoHints($data) {
		$SSOUIIHandler = $this->metaDb->prepare("INSERT INTO Mdui (`entity_id`, `type`, `lang`, `height`, `width`, `element`, `data`) VALUES (:Id, 'IDPDisco', :Lang, :Height, :Width, :Element, :Value);");

		$SSOUIIHandler->bindValue(':Id', $this->dbIdNr);
		$SSOUIIHandler->bindParam(':Lang', $lang);
		$SSOUIIHandler->bindParam(':Height', $height);
		$SSOUIIHandler->bindParam(':Width', $width);
		$SSOUIIHandler->bindParam(':Element', $element);

		# https://docs.oasis-open.org/security/saml/Post2.0/sstc-saml-metadata-ui/v1.0/os/sstc-saml-metadata-ui-v1.0-os.html
		$child = $data->firstChild;
		while ($child) {
			switch ($child->nodeName) {
				case 'mdui:IPHint' :
				case 'mdui:DomainHint' :
				case 'mdui:GeolocationHint' :
					$element = substr($child->nodeName, 5);
					break;
				default :
					$this->result .= $child->nodeType == 8 ? '' : sprintf ("Unknown Element (%s) in %s->DiscoHints.\n", $child->nodeName, $type);
					$element = 'Unknown';
			}

			$SSOUIIHandler->bindValue(':Value', trim($child->textContent));
			$SSOUIIHandler->execute();
			$child = $child->nextSibling;
		}
	}

	#############
	# SPSSODescriptor
	#############
	private function parseSPSSODescriptor($data) {
		# https://docs.oasis-open.org/security/saml/v2.0/saml-metadata-2.0-os.pdf 2.4.1 + 2.4.2 + 2.4.4
		$child = $data->firstChild;
		while ($child) {
			#$this->showProgress("SPSSODescriptor->".$child->nodeName);
			switch ($child->nodeName) {
				# 2.4.1
				#case 'md:Signature' :
				case 'md:Extensions' :
					$this->parseSPSSODescriptor_Extensions($child);
					break;
				case 'md:KeyDescriptor' :
					$this->parseKeyDescriptor($child, 'SPSSO');
					break;
				# 2.4.2
				case 'md:ArtifactResolutionService' :
				case 'md:SingleLogoutService' :
				case 'md:ManageNameIDService' :
					$this->checkSAMLEndpointURL($child,'SPSSO');
					break;
				case 'md:NameIDFormat' :
					# Skippar då SWAMID inte använder denna del
					break;
				# 2.4.4
				case 'md:AssertionConsumerService' :
					$this->checkSAMLEndpointURL($child,'SPSSO');
					$this->checkAssertionConsumerService($child);
					break;
				case 'md:AttributeConsumingService' :
					$this->parseSPSSODescriptor_AttributeConsumingService($child);
					break;
				default :
					$this->result .= $child->nodeType == 8 ? '' : sprintf("SPSSODescriptor->%s missing in validator.\n", $child->nodeName);
			}
			$child = $child->nextSibling;
		}
	}

	private function parseSPSSODescriptor_Extensions($data) {
		$child = $data->firstChild;
		while ($child) {
			switch ($child->nodeName) {
				case 'idpdisc:DiscoveryResponse' :
				case 'init:RequestInitiator' :
					break;
				case 'mdui:UIInfo' :
					$this->parseSSODescriptor_Extensions_UIInfo($child, 'SPSSO');
					break;
				case 'mdui:DiscoHints' :
					$this->warning .= "SPSSODescriptor/Extensions should not have a DiscoHints.\n";
					break;
				case 'shibmd:Scope' :
					$this->error .= "Scope found in SPSSODescriptor/Extensions should be below IDPSSODescriptor/Extensions.\n";
					break;
				default :
					$this->result .= $child->nodeType == 8 ? '' : sprintf("SPSSODescriptor->Extensions->%s missing in validator.\n", $child->nodeName);
			}
			$child = $child->nextSibling;
		}
	}

	private function parseSPSSODescriptor_AttributeConsumingService($data) {
		$index = $data->getAttribute('index');
		if ($index == '') {
			$this->error .= "Index is Required in SPSSODescriptor->AttributeConsumingService.\n";
			$index = 0;
		}

		$isDefault = ($data->getAttribute('isDefault') && ($data->getAttribute('isDefault') == 'true' || $data->getAttribute('isDefault') == '1')) ? 1 : 0;

		$ServiceHandler = $this->metaDb->prepare('INSERT INTO AttributeConsumingService(`entity_id`, `Service_index`, `isDefault`) VALUES (:Id, :Index, :Default);');
		$ServiceElementHandler = $this->metaDb->prepare('INSERT INTO AttributeConsumingService_Service (`entity_id`, `Service_index`, `lang`, `element`, `data`) VALUES (:Id, :Index, :Lang, :Element, :Data);');
		$RequestedAttributeHandler = $this->metaDb->prepare('INSERT INTO AttributeConsumingService_RequestedAttribute (`entity_id`, `Service_index`, `FriendlyName`, `Name`, `NameFormat`, `isRequired`) VALUES (:Id, :Index, :FriendlyName, :Name, :NameFormat, :isRequired);');

		$ServiceHandler->bindValue(':Id', $this->dbIdNr);
		$ServiceHandler->bindParam(':Index', $index);
		$ServiceHandler->bindValue(':Default', $isDefault);
		$ServiceHandler->execute();
		$ServiceElementHandler->bindValue(':Id', $this->dbIdNr);
		$ServiceElementHandler->bindParam(':Index', $index);
		$ServiceElementHandler->bindParam(':Lang', $lang);
		$RequestedAttributeHandler->bindValue(':Id', $this->dbIdNr);
		$RequestedAttributeHandler->bindParam(':Index', $index);
		$RequestedAttributeHandler->bindParam(':FriendlyName', $FriendlyName);
		$RequestedAttributeHandler->bindParam(':Name', $Name);
		$RequestedAttributeHandler->bindParam(':NameFormat', $NameFormat);
		$RequestedAttributeHandler->bindParam(':isRequired', $isRequired);

		$ServiceNameFound = false;
		$RequestedAttributeFound = false;

		$child = $data->firstChild;
		while ($child) {
			$lang = $child->getAttribute('xml:lang') ? $child->getAttribute('xml:lang') : '';
			switch ($child->nodeName) {
				case 'md:ServiceName' :
					$ServiceElementHandler->bindValue(':Element', 'ServiceName');
					$ServiceElementHandler->bindValue(':Data', trim($child->textContent));
					$ServiceElementHandler->execute();
					$ServiceNameFound = true;
					break;
				case 'md:ServiceDescription' :
					$ServiceElementHandler->bindValue(':Element', 'ServiceDescription');
					$ServiceElementHandler->bindValue(':Data', trim($child->textContent));
					$ServiceElementHandler->execute();
					break;
				case 'md:RequestedAttribute' :
					$FriendlyName = $child->getAttribute('FriendlyName') ? $child->getAttribute('FriendlyName') : '';
					$NameFormat = '';
					$isRequired = ($child->getAttribute('isRequired') && ($child->getAttribute('isRequired') == 'true' || $child->getAttribute('isRequired') == '1')) ? 1 : 0;
					if ($child->getAttribute('Name')) {
						$Name = $child->getAttribute('Name');
						if ($FriendlyName != '' && isset($this->FriendlyNames[$Name])) {
							if ( $this->FriendlyNames[$Name]['desc'] != $FriendlyName) {
								$this->warning .= sprintf("SWAMID Tech 6.1.20: FriendlyName for %s in RequestedAttribute for index %d is %s (recomended from SWAMID is %s).\n", $Name, $index, $FriendlyName, $this->FriendlyNames[$Name]['desc']);
							}
						}
						if ($child->getAttribute('NameFormat')) {
							$NameFormat = $child->getAttribute('NameFormat');
							if ($NameFormat <> 'urn:oasis:names:tc:SAML:2.0:attrname-format:uri') {
								$this->warning .= sprintf("NameFormat %s for %s in RequestedAttribute for index %d is not recomended.\n", $NameFormat, $Name, $index);
							}
						} else {
							$this->warning .= sprintf("NameFormat is missing for %s in RequestedAttribute for index %d. This might create problmes with some IdP:s\n", $Name, $index);
						}
						$RequestedAttributeHandler->execute();
						$RequestedAttributeFound = true;
					} else {
						$this->error .= sprintf("A Name attribute is Required in SPSSODescriptor->AttributeConsumingService[index=%d]->RequestedAttribute.\n", $index);
					}
					break;
				default :
					$this->result .= $child->nodeType == 8 ? '' : sprintf("SPSSODescriptor->AttributeConsumingService->%s missing in validator.\n", $child->nodeName);
			}
			$child = $child->nextSibling;
		}
		if ( ! $ServiceNameFound )
			$this->error .= sprintf("SWAMID Tech 6.1.17: ServiceName is Required in SPSSODescriptor->AttributeConsumingService[index=%d].\n", $index);
		if ( ! $RequestedAttributeFound )
			$this->error .= sprintf("SWAMID Tech 6.1.19: RequestedAttribute is Required in SPSSODescriptor->AttributeConsumingService[index=%d].\n", $index);
	}

	#############
	# AttributeAuthorityDescriptor
	#############
	private function parseAttributeAuthorityDescriptor($data) {
		# https://docs.oasis-open.org/security/saml/v2.0/saml-metadata-2.0-os.pdf 2.4.1 + 2.4.2 + 2.4.7
		$child = $data->firstChild;
		while ($child) {
			switch ($child->nodeName) {
				# 2.4.1
				#case 'Signature' :
				case 'md:Extensions' :
					# Skippar då SWAMID inte använder denna del
					break;
				case 'md:KeyDescriptor' :
					$this->parseKeyDescriptor($child, 'AttributeAuthority');
					break;
				# 2.4.2
				#case 'md:ArtifactResolutionService' :
				#case 'md:SingleLogoutService' :
				#case 'md:ManageNameIDService' :
				#case 'md:NameIDFormat' :
				# 2.4.7
				case 'md:AttributeService' :
				#case 'md:AssertionIDRequestService' :
				case 'md:NameIDFormat' :
					# Skippar då SWAMID inte använder denna del
					break;
				#case 'md:AttributeProfile' :
				#case 'md:Attribute' :

				default :
					$this->result .= $child->nodeType == 8 ? '' : sprintf("AttributeAuthorityDescriptor->%s missing in validator.\n", $child->nodeName);
			}
			$child = $child->nextSibling;
		}
	}

	#############
	# Organization
	#############
	private function parseOrganization($data) {
		# https://docs.oasis-open.org/security/saml/v2.0/saml-metadata-2.0-os.pdf 2.3.2.1
		$OrganizationHandler = $this->metaDb->prepare('INSERT INTO Organization (`entity_id`, `lang`, `element`, `data`) VALUES (:Id, :Lang, :Element, :Value);');


		$OrganizationHandler->bindValue(':Id', $this->dbIdNr);
		$OrganizationHandler->bindParam(':Lang', $lang);
		$OrganizationHandler->bindParam(':Element', $element);

		$child = $data->firstChild;
		while ($child) {
			$lang = $child->getAttribute('xml:lang') ? $child->getAttribute('xml:lang') : '';
			switch ($child->nodeName) {
				case 'md:OrganizationURL' :
					$this->addURL(trim($child->textContent), 1);
				case 'md:Extensions' :
				case 'md:OrganizationName' :
				case 'md:OrganizationDisplayName' :
					$element = substr($child->nodeName, 3);
					break;
				default :
					$this->result .= sprintf("Organization->%s missing in validator.\n", $child->nodeName);
			}
			$OrganizationHandler->bindValue(':Value', trim($child->textContent));
			$OrganizationHandler->execute();
			$child = $child->nextSibling;
		}
	}

	#############
	# ContactPerson
	#############
	private function parseContactPerson($data) {
		# https://docs.oasis-open.org/security/saml/v2.0/saml-metadata-2.0-os.pdf 2.3.2.2
		$Extensions = '';
		$Company = '';
		$GivenName = '';
		$SurName = '';
		$EmailAddress = '';
		$TelephoneNumber = '';
		$contactType = $data->getAttribute('contactType');
		$subcontactType = '';

		$ContactPersonHandler = $this->metaDb->prepare('INSERT INTO ContactPerson (`entity_id`, `contactType`, `subcontactType`, `company`, `emailAddress`, `extensions`, `givenName`, `surName`, `telephoneNumber`) VALUES (:Id, :ContactType, :SubcontactType, :Company, :EmailAddress, :Extensions, :GivenName, :SurName, :TelephoneNumber);');

		$ContactPersonHandler->bindValue(':Id', $this->dbIdNr);
		$ContactPersonHandler->bindParam(':ContactType', $contactType);
		$ContactPersonHandler->bindParam(':SubcontactType', $subcontactType);
		$ContactPersonHandler->bindParam(':Company', $Company);
		$ContactPersonHandler->bindParam(':EmailAddress', $EmailAddress);
		$ContactPersonHandler->bindParam(':Extensions', $Extensions);
		$ContactPersonHandler->bindParam(':GivenName', $GivenName);
		$ContactPersonHandler->bindParam(':SurName', $SurName);
		$ContactPersonHandler->bindParam(':TelephoneNumber', $TelephoneNumber);

		# https://docs.oasis-open.org/security/saml/Post2.0/sstc-saml-metadata-ui/v1.0/os/sstc-saml-metadata-ui-v1.0-os.html
		switch ($data->getAttribute('contactType')) {
			case 'administrative' :
			case 'billing' :
			case 'support' :
			case 'technical' :
				break;
			case 'other' :
				if ($data->getAttribute('remd:contactType')) {
					if ($data->getAttribute('remd:contactType') == 'http://refeds.org/metadata/contactType/security')
						$subcontactType =  'security';
					else {
						$subcontactType =  'unknown';
						$this->result .= sprintf("ContactPerson->Unknown subcontactType->%s.\n", $data->getAttribute('remd:contactType'));
					}
				} else
					$this->result .= sprintf("ContactPerson->%s->Unknown subcontactType.\n", $data->getAttribute('contactType'));

				break;
			default :
				$contactType = 'Unknown';
				$this->result .= sprintf("Unknown contactType in ContactPerson->%s.\n", $data->getAttribute('contactType'));
		}

		$child = $data->firstChild;
		while ($child) {
			switch ($child->nodeName) {
				case 'md:Extensions' :
					$Extensions = trim($child->textContent);
					break;
				case 'md:Company' :
					$Company = trim($child->textContent);
					break;
				case 'md:GivenName' :
					$GivenName = trim($child->textContent);
					break;
				case 'md:SurName' :
					$SurName = trim($child->textContent);
					break;
				case 'md:EmailAddress' :
					$EmailAddress = trim($child->textContent);
					break;
				case 'md:TelephoneNumber' :
					$TelephoneNumber = trim($child->textContent);
					break;
				default :
					$this->result .= sprintf("ContactPerson->%s missing in validator.\n", $child->nodeName);
			}
			$child = $child->nextSibling;
		}
		$ContactPersonHandler->execute();
	}

	#############
	# UIInfo
	# Used by IDPSSODescriptor and SPSSODescriptor
	#############
	private function parseSSODescriptor_Extensions_UIInfo($data, $type) {
		$SSOUIIHandler = $this->metaDb->prepare('INSERT INTO Mdui (`entity_id`, `type`, `lang`, `height`, `width`, `element`, `data`) VALUES (:Id, :Type, :Lang, :Height, :Width, :Element, :Value);');

		$SSOUIIHandler->bindValue(':Id', $this->dbIdNr);
		$SSOUIIHandler->bindValue(':Type', $type);
		$SSOUIIHandler->bindParam(':Lang', $lang);
		$SSOUIIHandler->bindParam(':Height', $height);
		$SSOUIIHandler->bindParam(':Width', $width);
		$SSOUIIHandler->bindParam(':Element', $element);

		# https://docs.oasis-open.org/security/saml/Post2.0/sstc-saml-metadata-ui/v1.0/os/sstc-saml-metadata-ui-v1.0-os.html
		$child = $data->firstChild;
		while ($child) {
			$lang = $child->getAttribute('xml:lang') ? $child->getAttribute('xml:lang') : '';
			switch ($child->nodeName) {
				case 'mdui:Logo' :
				case 'mdui:InformationURL' :
				case 'mdui:PrivacyStatementURL' :
					$this->addURL(trim($child->textContent), 1);
				case 'mdui:DisplayName' :
				case 'mdui:Description' :
				case 'mdui:Keywords' :
					$element = substr($child->nodeName, 5);
					$height = $child->getAttribute('height') ? $child->getAttribute('height') : 0;
					$width = $child->getAttribute('width') ? $child->getAttribute('width') : 0;
					break;
				default :
					$this->result .= sprintf ("Unknown Element (%s) in %s->UIInfo.\n", $child->nodeName, $type);
					$element = 'Unknown';
			}

			$SSOUIIHandler->bindValue(':Value', trim($child->textContent));
			$SSOUIIHandler->execute();
			$child = $child->nextSibling;
			while ($child && $child->nodeType == 8) $child = $child->nextSibling;
		}
	}

	#############
	# KeyDescriptor
	# Used by AttributeAuthority, IDPSSODescriptor and SPSSODescriptor
	#############
	private function parseKeyDescriptor($data, $type) {
		$use = $data->getAttribute('use') ? $data->getAttribute('use') : 'both';
		#'{urn:oasis:names:tc:SAML:2.0:metadata}EncryptionMethod':
		$child = $data->firstChild;
		while ($child) {
			switch ($child->nodeName) {
				case 'ds:KeyInfo' :
					$this->parseKeyDescriptor_KeyInfo($child, $type, $use);
					break;
				case 'md:EncryptionMethod' :
					break;
				default :
					$this->result .= $child->nodeType == 8 ? '' : sprintf("%sDescriptor->KeyDescriptor->%s missing in validator.\n", $type, $child->nodeName);
			}
			$child = $child->nextSibling;
		}
	}

	#############
	# KeyDescriptor KeyInfo
	#############
	private function parseKeyDescriptor_KeyInfo($data, $type, $use) {
		$name = '';
		$child = $data->firstChild;
		while ($child) {
			switch ($child->nodeName) {
				case 'ds:KeyName' :
					$name = trim($child->textContent);
					break;
				case 'ds:X509Data' :
					$this->parseKeyDescriptor_KeyInfo_X509Data($child, $type, $use, $name);
					break;
				default :
					$this->result .= $child->nodeType == 8 ? '' : sprintf("%sDescriptor->KeyDescriptor->KeyInfo->%s missing in validator.\n", $type, $child->nodeName);
			}
			$child = $child->nextSibling;
		}
	}

	#############
	# KeyDescriptor KeyInfo X509Data
	# Extract Certs and check dates
	#############
	private function parseKeyDescriptor_KeyInfo_X509Data($data, $type, $use, $name) {
		$KeyInfoHandler = $this->metaDb->prepare('INSERT INTO KeyInfo (`entity_id`, `type`, `use`, `name`, `notValidAfter`, `subject`, `issuer`, `bits`, `key_type`, `hash`, `serialNumber`) VALUES (:Id, :Type, :Use, :Name, :NotValidAfter, :Subject, :Issuer, :Bits, :Key_type, :Hash, :SerialNumber);');

		$KeyInfoHandler->bindValue(':Id', $this->dbIdNr);
		$KeyInfoHandler->bindValue(':Type', $type);
		$KeyInfoHandler->bindValue(':Use', $use);
		$KeyInfoHandler->bindParam(':Name', $name);

		$child = $data->firstChild;
		while ($child) {
			switch ($child->nodeName) {
				case'ds:X509IssuerSerial' :
					# Skippar då SWAMID inte använder denna del
					break;
				#case'X509SKI' :
				case 'ds:X509SubjectName' :
					$name = trim($child->textContent);
					break;
				case 'ds:X509Certificate' :
					$cert = "-----BEGIN CERTIFICATE-----\n" . chunk_split(str_replace(array(' ',"\n") ,array('',''),trim($child->textContent)),64) . "-----END CERTIFICATE-----\n";
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

						$KeyInfoHandler->bindValue(':NotValidAfter', date('Y-m-d H:i:s', $cert_info['validTo_time_t']));
						$KeyInfoHandler->bindParam(':Subject', $subject);
						$KeyInfoHandler->bindParam(':Issuer', $issuer);
						$KeyInfoHandler->bindParam(':Bits', $key_info['bits']);
						$KeyInfoHandler->bindParam(':Key_type', $keyType);
						$KeyInfoHandler->bindParam(':Hash', $cert_info['hash']);
						$KeyInfoHandler->bindParam(':SerialNumber', $cert_info['serialNumber']);
					} else {
						$KeyInfoHandler->bindValue(':NotValidAfter', date('Y-m-d H:i:s', $cert_info['validTo_time_t']));
						$KeyInfoHandler->bindValue(':Subject', '?');
						$KeyInfoHandler->bindValue(':Issuer', '?');
						$KeyInfoHandler->bindValue(':Bits', 0);
						$KeyInfoHandler->bindValue(':Key_type', '?');
						$KeyInfoHandler->bindValue(':Hash', '?');
						$KeyInfoHandler->bindValue(':SerialNumber', '?');
						$name = 'Invalid Certificate';
					}
				break;
				#case'ds:X509CRL' :
				default :
					$this->result .= $child->nodeType == 8 ? '' : sprintf("%sDescriptor->KeyDescriptor->KeyInfo->X509Data->%s missing in validator.\n", $type, $child->nodeName);
			}
			$child = $child->nextSibling;
		}
		$KeyInfoHandler->execute();
	}

	# Validate SAML-rules
	public function validateSAML($verbose=false) {
		if (! $this->entityExists) {
			$this->result = "$this->entityID doesn't exist!!";
			return 1;
		}
		$entityHandler = $this->metaDb->prepare('SELECT `isIdP`, `isSP` FROM Entities WHERE `id` = :Id;');
		$entityHandler->bindValue(':Id', $this->dbIdNr);

		$entityHandler->execute();
		$entity = $entityHandler->fetch(PDO::FETCH_ASSOC);
		$this->isIdP = $entity['isIdP'];
		$this->isSP = $entity['isSP'];

		$this->isSP_RandS = false;
		$this->isSP_CoCov1 = false;
		$this->isSIRTFI = false;

		$entityAttributesHandler = $this->metaDb->prepare('SELECT `type`, `attribute` FROM EntityAttributes WHERE `entity_id` = :Id;');
		$entityAttributesHandler->bindValue(':Id', $this->dbIdNr);
		$entityAttributesHandler->execute();
		while ($entityAttribute = $entityAttributesHandler->fetch(PDO::FETCH_ASSOC)) {
			switch ($entityAttribute['attribute']) {
				#case 'http://refeds.org/category/hide-from-discovery' :
				#	if ($entityAttribute->type == 'entity-category' && $this->isIdP)
				#	break;
				case 'http://refeds.org/category/research-and-scholarship' :
					if ($entityAttribute['type'] == 'entity-category' && $this->isSP)
						$this->isSP_RandS = true;
					break;
				case 'http://www.geant.net/uri/dataprotection-code-of-conduct/v1' :
					if ($entityAttribute['type'] == 'entity-category' && $this->isSP)
						$this->isSP_CoCov1 = true;
					break;
				case 'https://refeds.org/sirtfi' :
					if ($entityAttribute['type'] == 'assurance-certification' )
						$this->isSIRTFI = true;
					break;
			}
		}
		// 5.1.1 -> 5.1.5 / 6.1.1 -> 6.1.5
		$this->checkLangElements();

		// 5.1.7 /6.1.7
		if (! (substr($this->entityID, 0, 4) == 'urn:' || substr($this->entityID, 0, 8) == 'https://' || substr($this->entityID, 0, 7) == 'http://' ))
			$this->error .= $this->selectError('5.1.7', '6.1.7', 'entityID MUST start with either urn:, https:// or http://.');

		if (substr($this->entityID, 0, 4) == 'urn:' )
			$this->warning .= $this->selectError('5.1.7', '6.1.7', 'entityID SHOULD NOT start with urn: for new entitys.');

		// 5.1.8 /6.1.8
		if (strlen($this->entityID) > 256)
			$this->error .= $this->selectError('5.1.8', '6.1.8', 'entityID MUST NOT exceed 256 characters.');

		if ($this->isIdP) {
			// 5.1.9 -> 5.1.12
			$this->checkEntityAttributes('IDPSSO');

			// 5.1.13 errorURL
			$this->checkErrorURL();
			// 5.1.15, 5.1.16 Scope
			$this->checkIDPScope();
			// 5.1.17
			$this->checkRequiredMDUIelements('IDPSSO');
			// 5.1.20, 5.2.x
			$this->checkRequiredSAMLcertificates('IDPSSO');
		}
		if ($this->isSP) {
			// 6.1.12
			$this->checkRequiredMDUIelements('SPSSO');
			// 6.1.14, 6.2.x
			$this->checkRequiredSAMLcertificates('SPSSO');
		}

		// 5.1.22 / 6.1.20
		$this->checkRequiredOrganizationElements();

		// 5.1.23 -> 5.1.28 / 6.1.21 -> 6.1.26
		if ($this->isIdP) {
			if ($this->isSP)
				// 5.1.23 -> 5.1.28 / 6.1.21 -> 6.1.26
				$this->checkRequiredContactPersonElements('both');
			else
				// 5.1.23 -> 5.1.28
				$this->checkRequiredContactPersonElements('IDPSSO');
		} else
			// 6.1.21 -> 6.1.26
			$this->checkRequiredContactPersonElements('SPSSO');

		if ($this->isSP_RandS) $this->validateSPRandS();

		if ($this->isSP_CoCov1) $this->validateSPCoCov1();

		$resultHandler = $this->metaDb->prepare("UPDATE Entities SET `validationOutput` = :validationOutput, `warnings` = :Warnings, `errors` = :Errors, `errorsNB` = :ErrorsNB, `lastValidated` = NOW() WHERE `id` = :Id;");
		$resultHandler->bindValue(':Id', $this->dbIdNr);
		$resultHandler->bindValue(':validationOutput', $this->result);
		$resultHandler->bindValue(':Warnings', $this->warning);
		$resultHandler->bindValue(':Errors', $this->error);
		$resultHandler->bindValue(':ErrorsNB', $this->errorNB);
		$resultHandler->execute();
		$this->validateURLs();
	}

	// 5.1.1 -> 5.1.5/ 6.1.1 -> 6.1.5
	private function checkLangElements() {
		#$this->showProgress('checkLang');
		$mduiArray = array();
		$usedLangArray = array();
		$mduiHandler = $this->metaDb->prepare("SELECT `type`, `lang`, `element` FROM Mdui WHERE `type` <> 'IDPDisco' AND `entity_id` = :Id;");
		$mduiHandler->bindValue(':Id', $this->dbIdNr);
		$mduiHandler->execute();
		while ($mdui = $mduiHandler->fetch(PDO::FETCH_ASSOC)) {
			$type = $mdui['type'];
			$lang = $mdui['lang'];
			$element = $mdui['element'];
			if (! $lang == '')
				$usedLangArray[$lang] = $lang;

			if (! isset ($mduiArray[$type]))
				$mduiArray[$type] = array();
			if (! isset ($mduiArray[$type][$element]))
				$mduiArray[$type][$element] = array();

			if (isset($mduiArray[$type][$element][$lang])) {
				if ($element != 'Logo') {
					if ($type == 'IDPSSO')
						$this->error .= sprintf("SWAMID Tech 5.1.2: More than one mdui:%s with lang=%s in %sDescriptor.\n", $element, $lang, $type);
					else
						$this->error .= sprintf("SWAMID Tech 6.1.2: More than one mdui:%s with lang=%s in %sDescriptor.\n", $element, $lang, $type);
				}
			} else
				$mduiArray[$type][$element][$lang] = true;
		}

		$serviceNameArray = array();
		$serviceDescriptionArray = array();

		$ServiceElementHandler = $this->metaDb->prepare('SELECT `element`, `lang`, `Service_index` FROM AttributeConsumingService_Service WHERE `entity_id` = :Id;');
		$ServiceElementHandler->bindValue(':Id', $this->dbIdNr);
		$ServiceElementHandler->execute();
		while ($service = $ServiceElementHandler->fetch(PDO::FETCH_ASSOC)) {
			$element = $service['element'];
			$lang = $service['lang'];
			$index = $service['Service_index'];
			if (! $lang == '')
				$usedLangArray[$lang] = $lang;

			switch ($element) {
				case 'ServiceName' :
					if (! isset ($serviceNameArray[$index]))
						$serviceNameArray[$index] = array();

					if (isset($serviceNameArray[$index][$lang]))
						$this->error .= sprintf("SWAMID Tech 6.1.2: More than one ServiceName with lang=%s in AttributeConsumingService (index=%d).\n", $lang, $index);
					else
						$serviceNameArray[$index][$lang] = true;
					break;
				case 'ServiceDescription' :
					if (! isset ($serviceDescriptionArray[$index]))
						$serviceDescriptionArray[$index] = array();
					if (isset($serviceDescriptionArray[$index][$lang]))
						$this->error .= sprintf("SWAMID Tech 6.1.2: More than one ServiceDescription with lang=%s in AttributeConsumingService (index=%d).\n", $lang, $index);
					else
						$serviceDescriptionArray[$index][$lang] = true;
					break;
				default :
					$this->result .= sprintf("Missing %s in checkLangElements.\n", $element);
			}
		}

		$organizationArray = array();
		$organizationHandler = $this->metaDb->prepare('SELECT `lang`, `element` FROM Organization WHERE `entity_id` = :Id;');
		$organizationHandler->bindValue(':Id', $this->dbIdNr);
		$organizationHandler->execute();
		while ($organization = $organizationHandler->fetch(PDO::FETCH_ASSOC)) {
			$lang = $organization['lang'];
			$element = $organization['element'];
			if (! $lang == '')
				$usedLangArray[$lang] = $lang;

			if (! isset ($organizationArray[$element]))
				$organizationArray[$element] = array();

			if (isset($organizationArray[$element][$lang]))
				$this->error .= $this->selectError('5.1.2', '6.1.2', sprintf('More than one %s with lang=%s in Organization.', $element, $lang));
			else
				$organizationArray[$element][$lang] = true;
		}

		// 5.1.1 Metadata elements that support the lang attribute MUST have a lang attribute
		// 5.1.3 If a lang attribute value is used in one metadata element the same lang attribute value MUST be used for all metadata elements that supports the lang attribute.
		foreach ($mduiArray as $type => $elementArray) {
			foreach ($elementArray as $element => $langArray) {
				foreach ($usedLangArray as $lang) {
					if (! isset($langArray[$lang]))
						if ($type == 'IDPSSO')
							$this->error .= sprintf("SWAMID Tech 5.1.3: Missing lang=%s for mdui:%s in %sDescriptor.\n", $lang, $element, $type);
						else
							$this->error .= sprintf("SWAMID Tech 6.1.3: Missing lang=%s for mdui:%s in %sDescriptor.\n", $lang, $element, $type);
				}
			}
		}
		foreach ($serviceNameArray as $langArray) {
			foreach ($usedLangArray as $lang) {
				if (! isset($langArray[$lang]))
					$this->error .= sprintf("SWAMID Tech 6.1.3: Missing lang=%s for ServiceName in AttributeConsumingService.\n", $lang);
			}
		}
		foreach ($serviceDescriptionArray as $langArray) {
			foreach ($usedLangArray as $lang) {
				if (! isset($langArray[$lang]))
					$this->error .= sprintf("SWAMID Tech 6.1.3: Missing lang=%s for ServiceDescription in AttributeConsumingService.\n", $lang);
			}
		}
		foreach ($organizationArray as $element => $langArray) {
			foreach ($usedLangArray as $lang) {
				if (! isset($langArray[$lang]))
					$this->error .= $this->selectError('5.1.3', '6.1.3', sprintf('Missing lang=%s for %s in Organization.', $lang, $element));
			}
		}

		//5.1.4/6.1.4 Metadata elements that support the lang attribute MUST have a definition with language English (en).
		if (! isset($usedLangArray['en']))
			$this->error .= $this->selectError('5.1.4', '6.1.4', 'Missing MDUI/Organization/... with lang=en.');

		// 5.1.5/6.1.5 Metadata elements that support the lang attribute SHOULD have a definition with language Swedish (sv).
		if (! isset($usedLangArray['sv']))
			$this->warning .= $this->selectError('5.1.5', '6.1.5' ,'Missing MDUI/Organization/... with lang=sv.');
	}

	// 5.1.9 -> 5.1.11 / 6.1.9 -> 6.1.11
	private function checkEntityAttributes($type) {
		$entityAttributesHandler = $this->metaDb->prepare('SELECT `attribute` FROM EntityAttributes WHERE `entity_id` = :Id AND `type` = :Type ;');
		$entityAttributesHandler->bindValue(':Id', $this->dbIdNr);

		if ($type == 'IDPSSO' ) {
			//5.1.9 SWAMID Identity Assurance Profile compliance MUST be registered in the assurance certification entity attribute as defined by the profiles.
			$SWAMID_5_1_9_error = true;
			$entityAttributesHandler->bindValue(':Type', 'assurance-certification');
			$entityAttributesHandler->execute();
			while ($entityAttribute = $entityAttributesHandler->fetch(PDO::FETCH_ASSOC)) {
				if ($entityAttribute['attribute'] == 'http://www.swamid.se/policy/assurance/al1' )
					$SWAMID_5_1_9_error = false;
			}
			if ($SWAMID_5_1_9_error)
				$this->error .= "SWAMID Tech 5.1.9: SWAMID Identity Assurance Profile compliance MUST be registered in the assurance certification entity attribute as defined by the profiles.\n";

			// 5.1.10 Entity Categories applicable to the Identity Provider SHOULD be registered in the entity category entity attribute as defined by the respective Entity Category.
			// Not handled yet.

			$SWAMID_5_1_11_error = true;
			$entityAttributesHandler->bindValue(':Type', 'entity-category-support');
			$entityAttributesHandler->execute();
			while ($entityAttribute = $entityAttributesHandler->fetch(PDO::FETCH_ASSOC)) {
				$SWAMID_5_1_11_error = false;
			}
			if ($SWAMID_5_1_11_error)
				$this->warning .= "SWAMID Tech 5.1.11: Support for Entity Categories SHOULD be registered in the entity category support entity attribute as defined by the respective Entity Category.\n";
		}

		/*$SWAMID_5_1_11_error = true;
		$entityAttributesHandler->bindValue(':Type', 'entity-category-support');
		$entityAttributesHandler->execute();
		while ($entityAttribute = $entityAttributesHandler->fetch(PDO::FETCH_ASSOC)) {
			$SWAMID_5_1_11_error = false;
		}
		if ($SWAMID_5_1_11_error)
			$this->warning .= "SWAMID Tech 5.1.11: Support for Entity Categories SHOULD be registered in the entity category support entity attribute as defined by the respective Entity Category.\n";
			*/
	}

	# 5.1.13 errorURL
	private function checkErrorURL() {
		$errorURLHandler = $this->metaDb->prepare("SELECT DISTINCT `URL` FROM EntityURLs WHERE `entity_id` = :Id AND `type` = 'error';");
		$errorURLHandler->bindParam(':Id', $this->dbIdNr);
		$errorURLHandler->execute();
		if (! $errorURL = $errorURLHandler->fetch(PDO::FETCH_ASSOC))
			$this->error .= "SWAMID Tech 5.1.13: IdP:s MUST have a registered errorURL.\n";
	}

	// 5.1.15, 5.1.16 Scope
	private function checkIDPScope() {
		$scopesHandler = $this->metaDb->prepare('SELECT `scope`, `regexp` FROM Scopes WHERE `entity_id` = :Id;');
		$scopesHandler->bindParam(':Id', $this->dbIdNr);
		$scopesHandler->execute();
		$missingScope = true;
		while ($scope = $scopesHandler->fetch(PDO::FETCH_ASSOC)) {
			$missingScope = false;
			if ($scope['regexp'])
				$this->error .= sprintf("SWAMID Tech 5.1.16: IdP Scopes (%s) MUST NOT include regular expressions.\n", $scope['scope']);
		}
		if ($missingScope)
			$this->error .= "SWAMID Tech 5.1.15: IdP:s MUST have at least one Scope registered.\n";
	}

	// 5.1.17 / 6.1.12
	private function checkRequiredMDUIelements($type) {
		if ($type == 'IDPSSO') {
			$elementArray = array ('DisplayName' => false, 'Description' => false, 'InformationURL' => false, 'PrivacyStatementURL' => false, 'Logo' => false);
		} elseif ($type == 'SPSSO') {
			$elementArray = array ('DisplayName' => false, 'Description' => false, 'InformationURL' => false, 'PrivacyStatementURL' => false);
		}
		$mduiHandler = $this->metaDb->prepare('SELECT DISTINCT `element` FROM Mdui WHERE `entity_id` = :Id AND `type`  = :Type ;');
		$mduiHandler->bindValue(':Id', $this->dbIdNr);
		$mduiHandler->bindValue(':Type', $type);
		$mduiHandler->execute();
		while ($mdui = $mduiHandler->fetch(PDO::FETCH_ASSOC)) {
			$elementArray[$mdui['element']] = true;
		}

		foreach ($elementArray as $element => $value) {
			if (! $value) {
				if ($type == 'IDPSSO')
					$this->error .= sprintf("SWAMID Tech 5.1.17: Missing mdui:%s in IDPSSODecriptor.\n", $element);
				else
					$this->error .= sprintf("SWAMID Tech 6.1.12: Missing mdui:%s in SPSSODecriptor.\n", $element);
			}
		}
	}

	// 5.1.20, 5.2.x / 6.1.14, 6.2.x
	private function checkRequiredSAMLcertificates($type) {
		$keyInfoArray = array ('IDPSSO' => false, 'SPSSO' => false);
		$keyInfoHandler = $this->metaDb->prepare('SELECT `use`, `notValidAfter`, `subject`, `issuer`, `bits`, `key_type` FROM KeyInfo WHERE `entity_id` = :Id AND `type` =:Type ORDER BY notValidAfter DESC;');
		$keyInfoHandler->bindValue(':Id', $this->dbIdNr);
		$keyInfoHandler->bindValue(':Type', $type);
		$keyInfoHandler->execute();

		$SWAMID_5_2_1_Level = array ('encryption' => 0, 'signing' => 0, 'both' => 0);
		$SWAMID_5_2_2_error = false;
		$SWAMID_5_2_2_errorNB = false;
		$SWAMID_5_2_3_warning = false;
		$validEncryptionFound = false;
		$validSigningFound = false;
		$oldCertFound = false;
		$timeNow = date('Y-m-d H:i:00');
		$timeWarn = date('Y-m-d H:i:00', time() + 7776000);  // 90 * 24 * 60 * 60 = 90 days / 3 month
		while ($keyInfo = $keyInfoHandler->fetch(PDO::FETCH_ASSOC)) {
			$validCertExists = false;
			switch ($keyInfo['use']) {
				case 'encryption' :
					if ($keyInfo['notValidAfter'] > $timeNow ) {
						if (($keyInfo['bits'] >= 256 && $keyInfo['key_type'] == "EC") || $keyInfo['bits'] >= 2048) {
							$keyInfoArray['SPSSO'] = true;
						}
						$validEncryptionFound = true;
					} elseif ($validEncryptionFound) {
						$validCertExists = true;
						$oldCertFound = true;
					}
					break;
				case 'signing' :
					if ($keyInfo['notValidAfter'] > $timeNow ) {
						if (($keyInfo['bits'] >= 256 && $keyInfo['key_type'] == "EC") || $keyInfo['bits'] >= 2048) {
							$keyInfoArray['IDPSSO'] = true;
						}
						$validSigningFound = true;
					} elseif ($validSigningFound) {
						$validCertExists = true;
						$oldCertFound = true;
					}
					break;
				case 'both' :
					if ($keyInfo['notValidAfter'] > $timeNow ) {
						if (($keyInfo['bits'] >= 256 && $keyInfo['key_type'] == "EC") || $keyInfo['bits'] >= 2048) {
							$keyInfoArray['SPSSO'] = true;
							$keyInfoArray['IDPSSO'] = true;
						}
						$validEncryptionFound = true;
						$validSigningFound = true;
					} else if ($validEncryptionFound &&  $validSigningFound) {
						$validCertExists = true;
						$oldCertFound = true;
					}
					break;
			}
			switch ($keyInfo['key_type']) {
				case 'RSA' :
				case 'DSA' :
					//if ($keyInfo['bits'] >= 4096 && $keyInfo['notValidAfter'] >= $timeNow ) {
					if ($keyInfo['bits'] >= 4096 ) {
						$SWAMID_5_2_1_Level[$keyInfo['use']] = 2;
					//} elseif ($keyInfo['bits'] >= 2048 && $keyInfo['notValidAfter'] >= $timeNow && $SWAMID_5_2_1_Level[$keyInfo['use']] < 1 ) {
					} elseif ($keyInfo['bits'] >= 2048 && $SWAMID_5_2_1_Level[$keyInfo['use']] < 1 ) {
						$SWAMID_5_2_1_Level[$keyInfo['use']] = 1;
					}
					break;
				case 'EC' :
					//if ($keyInfo['bits'] >= 384 && $keyInfo['notValidAfter'] >= $timeNow ) {
					if ($keyInfo['bits'] >= 384 ) {
							$SWAMID_5_2_1_Level[$keyInfo['use']] = 2;
					//} elseif ($keyInfo['bits'] >= 256 && $keyInfo['notValidAfter'] >= $timeNow && $SWAMID_5_2_1_Level[$keyInfo['use']] < 1 ) {
					} elseif ($keyInfo['bits'] >= 256 && $SWAMID_5_2_1_Level[$keyInfo['use']] < 1 ) {
							$SWAMID_5_2_1_Level[$keyInfo['use']] = 1;
					}
					break;
			}
			if ($keyInfo['notValidAfter'] <= $timeNow ) {
				if ($validCertExists) {
					$SWAMID_5_2_2_errorNB = true;
				} else {
					$SWAMID_5_2_2_error = true;
				}
			} elseif ($keyInfo['notValidAfter'] <= $timeWarn ) {
				$this->warning .= sprintf ("Certificate (%s) %s will soon expire. New certificate should be have a key strength of at least 4096 bits for RSA or 384 bits for EC.\n", $keyInfo['use'], $keyInfo['subject']);
			}

			if ($keyInfo['subject'] != $keyInfo['issuer'])
				$SWAMID_5_2_3_warning = true;
		}

		if (! $keyInfoArray[$type]) {
			if ($type == 'IDPSSO')
				$this->error .= "SWAMID Tech 5.1.20: Identity Providers there MUST have at least one signing certificate.\n";
			else
				$this->error .= "SWAMID Tech 6.1.14: Service Providers there MUST have at least one encryption certificate.\n";
		}
		// 5.2.1 Identity Provider credentials (i.e. entity keys) MUST NOT use shorter comparable key strength (in the sense of NIST SP 800-57) than a 2048-bit RSA key. 4096-bit is RECOMMENDED.
		if ((($SWAMID_5_2_1_Level['encryption'] < 2) || ($SWAMID_5_2_1_Level['signing'] < 2)) && ($SWAMID_5_2_1_Level['both'] < 2)) {
			if ((($SWAMID_5_2_1_Level['encryption'] < 1) && ($SWAMID_5_2_1_Level['signing'] < 1)) && ($SWAMID_5_2_1_Level['both'] < 1)) {
				$this->error .= sprintf("SWAMID Tech %s: Certificate MUST NOT use shorter comparable key strength (in the sense of NIST SP 800-57) than a 2048-bit RSA key. New certificate should be have a key strength of at least 4096 bits for RSA or 384 bits for EC.\n", ($type == 'IDPSSO') ? '5.2.1' : '6.2.1');
			} else {
				$this->warning .= sprintf("SWAMID Tech %s: Certificate is RECOMMENDED NOT use shorter comparable key strength (in the sense of NIST SP 800-57) than a 4096-bit RSA key.\n", ($type == 'IDPSSO') ? '5.2.1' : '6.2.1');
			}
		}

		if ($SWAMID_5_2_2_error) {
			$this->error .= sprintf("SWAMID Tech %s: Signing and encryption certificates MUST NOT be expired. New certificate should be have a key strength of at least 4096 bits for RSA or 384 bits for EC.\n", ($type == 'IDPSSO') ? '5.2.2' : '6.2.2');
		} elseif ($SWAMID_5_2_2_errorNB) {
			$this->errorNB .= sprintf("SWAMID Tech %s: (NonBreaking) Signing and encryption certificates MUST NOT be expired.\n", ($type == 'IDPSSO') ? '5.2.2' : '6.2.2');
		}

		if ($oldCertFound) {
			$this->warning .= "One or more old certs found. Please remove when new certs have propagated.\n";
		}

		if ($SWAMID_5_2_3_warning) {
			$this->warning .= sprintf("SWAMID Tech %s: Signing and encryption certificates SHOULD be self-signed.\n", ($type == 'IDPSSO') ? '5.2.3' : '6.2.3');
		}
	}

	// 5.1.21 / 6.1.15
	private function checkSAMLEndpointURL($data,$type) {
		$name = $data->nodeName;
		$Binding = $data->getAttribute('Binding');
		$Location =$data->getAttribute('Location');
		#$index = $data->getAttribute('index');
		if (substr($Location,0,8) <> 'https://') {
			if ($type == "IDPSSO") {
				$this->error .= sprintf("SWAMID Tech 5.1.21: All SAML endpoints MUST start with https://. Problem in IDPSSODescriptor->%s[Binding=%s].\n", $name, $Binding);
			} else {
				$this->error .= sprintf("SWAMID Tech 6.1.15: All SAML endpoints MUST start with https://. Problem in SPSSODescriptor->%s[Binding=%s].\n", $name, $Binding);
			}
		}
	}

	// 6.1.16
	private function checkAssertionConsumerService($data) {
		$Binding = $data->getAttribute('Binding');
		$Location =$data->getAttribute('Location');
		$index = $data->getAttribute('index');
		if ($Binding == 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect')
			$this->error .= sprintf("SWAMID Tech 6.1.16: Binding with value urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect is no allowed in SPSSODescriptor->AssertionConsumerService[index=%d].\n", $index);
	}

	// 5.1.22 / 6.1.20
	private function checkRequiredOrganizationElements() {
		$elementArray = array('OrganizationName' => false, 'OrganizationDisplayName' => false, 'OrganizationURL' => false);

		$organizationHandler = $this->metaDb->prepare('SELECT DISTINCT `element` FROM Organization WHERE `entity_id` = :Id;');
		$organizationHandler->bindValue(':Id', $this->dbIdNr);
		$organizationHandler->execute();
		while ($organization = $organizationHandler->fetch(PDO::FETCH_ASSOC)) {
			$elementArray[$organization['element']] = true;
		}

		foreach ($elementArray as $element => $value) {
			if (! $value)
				$this->error .= $this->selectError('5.1.22', '6.1.21', sprintf('Missing %s in Organization.', $element));
		}
	}

	// 5.1.23 -> 5.1.28 / 6.1.21 -> 6.1.26
	private function checkRequiredContactPersonElements($type) {
		$usedContactTypes = array();
		$contactPersonHandler = $this->metaDb->prepare('SELECT `contactType`, `subcontactType`, `emailAddress`, `givenName` FROM ContactPerson WHERE `entity_id` = :Id;');
		$contactPersonHandler->bindValue(':Id', $this->dbIdNr);
		$contactPersonHandler->execute();

		while ($contactPerson = $contactPersonHandler->fetch(PDO::FETCH_ASSOC)) {
			$contactType = $contactPerson['contactType'];
			// 5.1.23/6.1.22 ContactPerson elements MUST have an EmailAddress element
			if ($contactPerson['emailAddress'] == '')
				$this->error .= $this->selectError('5.1.23' , '6.1.22', sprintf('ContactPerson [%s] elements MUST have an EmailAddress element.', $contactType));
			elseif (substr($contactPerson['emailAddress'], 0, 7) != 'mailto:')
				$this->error .= $this->selectError('5.1.23', '6.1.22', sprintf('ContactPerson [%s] EmailAddress MUST start with mailto:.', $contactType));

			// 5.1.24/6.1.23 There MUST NOT be more than one ContactPerson element of each type.
			if ( isset($usedContactTypes[$contactType]))
				$this->error .= $this->selectError('5.1.24', '6.1.23', sprintf('There MUST NOT be more than one ContactPerson element of type = %s.', $contactType));
			else
				$usedContactTypes[$contactType] = true;

			// 5.1.28 Identity Providers / 6.1.27 A Relying Party SHOULD have one ContactPerson element of contactType other with remd:contactType http://refeds.org/metadata/contactType/security.
			// If the element is present, a GivenName element MUST be present and the ContactPerson MUST respect the Traffic Light Protocol (TLP) during all incident response correspondence.
			if ($contactType == 'other' &&  $contactPerson['subcontactType'] == 'http://refeds.org/metadata/contactType/security' ) {
				if ( $contactPerson['givenName'] == '')
					$this->error .= $this->selectError('5.1.28', '6.1.27', 'GivenName element MUST be presenten for security ContactPerson.');
			}
		}

		// 5.1.25/6.1.24 Identity Providers MUST have one ContactPerson element of type administrative.
		if (!isset ($usedContactTypes['administrative']))
			$this->error .= $this->selectError('5.1.25','6.1.24','Missing ContactPerson of type administrative');

		// 5.1.26/6.1.25 Identity Providers MUST have one ContactPerson element of type technical.
		if (!isset ($usedContactTypes['technical']))
			$this->error .= $this->selectError('5.1.26','6.1.25','Missing ContactPerson of type technical.');

		// 5.1.27 Identity Providers MUST have one ContactPerson element of type support.
		// 6.1.26 Service Providers SHOULD have one ContactPerson element of type support.
		if (!isset ($usedContactTypes['support'])) {
			if ($type = 'SPSSO')
				$this->warning .= "SWAMID Tech 6.1.26: Missing ContactPerson of type support.\n";
			else
				// $type = IDPSSO or both
				$this->error .= "SWAMID Tech 5.1.27: Missing ContactPerson of type support.\n";
		}

		// 5.1.28 / 6.1.26 Identity Providers SHOULD have one ContactPerson element of contactType other
		if (!isset ($usedContactTypes['other']))
			$this->warning .= $this->selectError('5.1.28', '6.1.27', 'Missing security ContactPerson.');
	}

	# Validate R&S SP
	# https://refeds.org/category/research-and-scholarship
	private function validateSPRandS() {
		$mduiArray = array();
		$mduiHandler = $this->metaDb->prepare("SELECT `lang`, `element` FROM Mdui WHERE `type` = 'SPSSO' AND `entity_id` = :Id;");
		$mduiHandler->bindValue(':Id', $this->dbIdNr);
		$mduiHandler->execute();
		while ($mdui = $mduiHandler->fetch(PDO::FETCH_ASSOC)) {
			$lang = $mdui['lang'];
			$element = $mdui['element'];
			if (! isset ($mduiArray[$element]))
				$mduiArray[$element] = array();
			$mduiArray[$element][$lang] = true;
		}

		if (isset($mduiArray['DisplayName']) && isset($mduiArray['InformationURL'])) {
			if (! (isset($mduiArray['DisplayName']['en']) && isset($mduiArray['InformationURL']['en'])))
				$this->warning .= "REFEDS Research and Scholarship 4.3.3 RECOMMEND a MDUI:DisplayName and a MDUI:InformationURL with lang=en.\n";
		} else
			$this->error .= "REFEDS Research and Scholarship 4.3.3 Require a MDUI:DisplayName and a MDUI:InformationURL.\n";

		$contactPersonHandler = $this->metaDb->prepare("SELECT `emailAddress` FROM ContactPerson WHERE `contactType` = 'technical' AND `entity_id` = :Id;");
		$contactPersonHandler->bindValue(':Id', $this->dbIdNr);
		$contactPersonHandler->execute();
		if (! $contactPersonHandler->fetch(PDO::FETCH_ASSOC)) {
			$this->error .= "REFEDS Research and Scholarship 4.3.4 Require that the Service Provider provides one or more technical contacts in metadata.\n";
		}
	}

	# Validate CoCoSP v1
	private function validateSPCoCov1() {
		$mduiArray = array();
		$mduiElementArray = array();
		$mduiHandler = $this->metaDb->prepare("SELECT `lang`, `element`, `data` FROM Mdui WHERE `type` = 'SPSSO' AND `entity_id` = :Id;");
		$requestedAttributeHandler = $this->metaDb->prepare('SELECT DISTINCT `Service_index` FROM AttributeConsumingService_RequestedAttribute WHERE `entity_id` = :Id;');
		$mduiHandler->bindValue(':Id', $this->dbIdNr);
		$requestedAttributeHandler->bindValue(':Id', $this->dbIdNr);
		$mduiHandler->execute();
		while ($mdui = $mduiHandler->fetch(PDO::FETCH_ASSOC)) {
			$lang = $mdui['lang'];
			$element = $mdui['element'];
			$data = $mdui['data'];
			if (! isset ($mduiArray[$lang]))
				$mduiArray[$lang] = array();
			$mduiArray[$lang][$element] = $data;
			$mduiElementArray[$element] = true;
			if ($element == 'PrivacyStatementURL' ) {
				$this->addURL($data, 2);
			}
		}

		if (isset($mduiArray['en'])) {
			if (! isset($mduiArray['en']['PrivacyStatementURL']))
				$this->error .= "GÉANT Data Protection Code of Conduct Require a MDUI - PrivacyStatementURL with at least lang=en.\n";
			if (! isset($mduiArray['en']['DisplayName']))
				$this->warning .= "GÉANT Data Protection Code of Conduct Recomend a MDUI - DisplayName with at least lang=en.\n";
			if (! isset($mduiArray['en']['Description']))
				$this->warning .= "GÉANT Data Protection Code of Conduct Recomend a MDUI - Description with at least lang=en.\n";
			foreach ($mduiElementArray as $element => $value) {
				if (! isset($mduiArray['en'][$element]))
					$this->error .= sprintf("GÉANT Data Protection Code of Conduct Require a MDUI - %s with lang=en for all present elements.\n", $element);
			}
		} else {
			$this->error .= "GÉANT Data Protection Code of Conduct Require MDUI with lang=en.\n";
		}
		$requestedAttributeHandler->execute();
		if (! $requestedAttributeHandler->fetch(PDO::FETCH_ASSOC))
			$this->error .= "GÉANT Data Protection Code of Conduct Require at least one RequestedAttribute.\n";
	}

	#############
	# Removes RoleDescriptor.
	# SWAMID_5_1_30_error
	#############
	private function cleanOutRoleDescriptor() {
		$removed = false;
		$EntityDescriptor = $this->getEntityDescriptor($this->xml);
		$child = $EntityDescriptor->firstChild;
		while ($child) {
			if ($child->nodeName == 'md:RoleDescriptor') {
				$remChild = $child;
				$child = $child->nextSibling;
				$EntityDescriptor->removeChild($remChild);
				$removed = true;
			} else
				$child = $child->nextSibling;
		}
		if ($removed)
			$this->error .= $this->selectError('5.1.30','6.1.29','entityID MUST NOT include RoleDescriptor elements. Have been removed.');
	}

	#############
	# Removes Signature.
	#############
	private function cleanOutSignature() {
		$EntityDescriptor = $this->getEntityDescriptor($this->xml);
		$child = $EntityDescriptor->firstChild;
		while ($child) {
			if ($child->nodeName == 'ds:Signature') {
				$remChild = $child;
				$child = $child->nextSibling;
				$EntityDescriptor->removeChild($remChild);
				$removed = true;
			} else
				$child = $child->nextSibling;
		}
	}

	#############
	# Removes Attribues from IDPSSODescriptor.
	# SWAMID_5_1_31_error
	#############
	private function cleanOutAttribuesInIDPSSODescriptor() {
		$removed = false;
		$EntityDescriptor = $this->getEntityDescriptor($this->xml);
		$child = $EntityDescriptor->firstChild;
		while ($child) {
			if ($child->nodeName == 'md:IDPSSODescriptor') {
				$subchild = $child->firstChild;
				while ($subchild) {
					if ($subchild->nodeName == 'samla:Attribute') {
						$remChild = $subchild;
						$subchild = $subchild->nextSibling;
						$child->removeChild($remChild);
						$removed = true;
					} else
						$subchild = $subchild->nextSibling;
				}
			}
			$child = $child->nextSibling;
		}
		if ($removed)
			$this->error .= "SWAMID Tech 5.1.31: The Identity Provider IDPSSODescriptor element in metadata MUST NOT include any Attribute elements. Have been removed.\n";

	}

	private function selectError($idpCode,$spCode,$error) {
		if ($this->isIdP) {
			if ($this->isSP) {
				# Both IdP and SP
				return sprintf("SWAMID Tech %s/%s: %s\n", $idpCode, $spCode, $error);
			} else {
				# IdP Only
				return sprintf("SWAMID Tech %s: %s\n", $idpCode, $error);
			}
		} elseif ($this->isSP) {
			# SP Only
			return sprintf("SWAMID Tech %s: %s\n", $spCode, $error);
		}
	}

	#############
	# Adds an url to DB
	#############
	private function addEntityUrl($type, $url) {
		$URLHandler = $this->metaDb->prepare("INSERT INTO EntityURLs (`entity_id`, `URL`, `type`) VALUES (:Id, :URL, :Type)");
		$URLHandler->bindValue(':Id', $this->dbIdNr);
		$URLHandler->bindValue(':URL', $url);
		$URLHandler->bindValue(':Type', $type);
		$URLHandler->execute();
		$this->addURL($url, 1);
	}

	#############
	# Updates which feeds an entity belongs to
	#############
	public function updateFeed($feeds) {
		#1 = Testing
		#2 = SWAMID
		#3 = eduGAIN
		$publishIn = 0;
		foreach (explode(' ', $feeds) as $feed ) {
			switch (strtolower($feed)) {
				case 'testing' :
					$publishIn += 1;
					break;
				case 'swamid' :
					$publishIn += 2;
					break;
				case 'edugain' :
					$publishIn += 4;
					break;
			}
		}
		$publishedHandler = $this->metaDb->prepare('UPDATE Entities SET `publishIn` = :PublishIn WHERE `id` = :Id;');
		$publishedHandler->bindValue(':Id', $this->dbIdNr);
		$publishedHandler->bindValue(':PublishIn', $publishIn);
		$publishedHandler->execute();
	}

	#############
	# Updates which user that is responsible for an entity
	#############
	public function updateResponsible($EPPN,$mail) {
		$userHandler = $this->metaDb->prepare('INSERT INTO Users (`entity_id`, `userID`, `email`) VALUES (:Entity_id, :UserID, :Email) ON DUPLICATE KEY UPDATE `email` = :Email');
		$userHandler->bindValue(':Entity_id', $this->dbIdNr);
		$userHandler->bindValue(':UserID', $EPPN);
		$userHandler->bindValue(':Email', $mail);
		$userHandler->execute();
	}

	#############
	# Removes an entity from database
	#############
	public function removeEntity() {
		# Remove data for this Entity
		$this->metaDb->exec('DELETE FROM EntityAttributes WHERE `entity_id` = ' . $this->dbIdNr .';');
		$this->metaDb->exec('DELETE FROM Mdui WHERE `entity_id` = ' . $this->dbIdNr .';');
		$this->metaDb->exec('DELETE FROM KeyInfo WHERE `entity_id` = ' . $this->dbIdNr .';');
		$this->metaDb->exec('DELETE FROM AttributeConsumingService WHERE `entity_id` = ' . $this->dbIdNr .';');
		$this->metaDb->exec('DELETE FROM AttributeConsumingService_Service WHERE `entity_id` = ' . $this->dbIdNr .';');
		$this->metaDb->exec('DELETE FROM AttributeConsumingService_RequestedAttribute WHERE `entity_id` = ' . $this->dbIdNr .';');
		$this->metaDb->exec('DELETE FROM Organization WHERE `entity_id` = ' . $this->dbIdNr .';');
		$this->metaDb->exec('DELETE FROM ContactPerson WHERE `entity_id` = ' . $this->dbIdNr .';');
		$this->metaDb->exec('DELETE FROM EntityURLs WHERE `entity_id` = ' . $this->dbIdNr .';');
		$this->metaDb->exec('DELETE FROM Scopes WHERE `entity_id` = ' . $this->dbIdNr .';');
		$this->metaDb->exec('DELETE FROM Users WHERE `entity_id` = ' . $this->dbIdNr .';');
		$this->metaDb->exec('DELETE FROM Entities WHERE `id` = ' . $this->dbIdNr .';');
	}

	#############
	# Returns Result
	#############
	public function getResult() {
		return $this->result;
	}

	#############
	# Clear Result
	#############
	public function clearResult() {
		$this->result = '';
	}

	#############
	# Return Warning
	#############
	public function getWarning() {
		return $this->warning;
	}

	#############
	# Clear Warning
	#############
	public function clearWarning() {
		$this->warning = '';
	}

	#############
	# Return Error
	#############
	public function getError() {
		return $this->error . $this->errorNB;
	}

	#############
	# Clear Error
	#############
	public function clearError() {
		$this->error = '';
		$this->errorNB = '';
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

	private function showProgress($info) {
		#if($verbose) {
		#	printf('%d: %s<br>',time() - $this->startTimer, $info);
		#	ob_flush();
		#	flush();
		#}
	}
}
# vim:set ts=2:
