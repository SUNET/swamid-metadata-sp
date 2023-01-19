<?php
Class Metadata {
	# Setup
	private $result = '';
	private $warning = '';
	private $error = '';
	private $errorNB = '';

	private $isIdP = false;
	private $isSP = false;
	private $feedValue = 0;
	private $registrationInstant = '';

	private $entityID = 'Unknown';
	private $entityExists = false;
	private $dbIdNr = 0;
	private $status = 0;
	private $xml;

	private $user = array ('id' => 0, 'email' => '', 'fullname' => '');

	private $basedDir = '';
	//$startTimer = time();

	function __construct() {
		$a = func_get_args();
		$i = func_num_args();
		if (isset($a[0])) {
			$this->baseDir = array_shift($a);
			include $this->baseDir . '/config.php';
			include $this->baseDir . '/include/common.php';
			try {
				$this->metaDb = new PDO("mysql:host=$dbServername;dbname=$dbName", $dbUsername, $dbPassword);
				// set the PDO error mode to exception
				$this->metaDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			} catch(PDOException $e) {
				echo "Error: " . $e->getMessage();
			}
		}
		if (method_exists($this,$f='__construct'.$i)) {
				call_user_func_array(array($this,$f),$a);
		}
	}

	private function __construct2($entity_id) {
		$entityHandler = $this->metaDb->prepare('SELECT `id`, `entityID`, `isIdP`, `isSP`, `publishIn`, `status`, `xml` FROM Entities WHERE `id` = :Id;');
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
			$this->isIdP = $entity['isIdP'];
			$this->isSP = $entity['isSP'];
			$this->feedValue = $entity['publishIn'];
		}
	}

	private function __construct3($entityID = '', $entityStatus = '') {
		$this->entityID = $entityID;

		switch (strtolower($entityStatus)) {
			case 'prod' :
				# In production metadata
				$this->status = 1;
				break;
			case 'shadow' :
				# Request sent to OPS to be added.
				# Create a shadow entiry
				$this->status = 6;
				break;
			case 'new' :
			default :
				# New entity/updated entity
				$this->status = 3;
		}

		$entityHandler = $this->metaDb->prepare('SELECT `id`, `isIdP`, `isSP`, `publishIn`, `xml` FROM Entities WHERE `entityID` = :Id AND `status` = :Status;');
		$entityHandler->bindValue(':Id', $entityID);
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
			$this->isIdP = $entity['isIdP'];
			$this->isSP = $entity['isSP'];
			$this->feedValue = $entity['publishIn'];
		}
	}

	private function addURL($url, $type) {
		//type
		// 1 Check reachable (OK If reachable)
		// 2 Check reachable (NEED to be reachable)
		// 3 Check CoCo privacy
		$urlHandler = $this->metaDb->prepare('SELECT `type` FROM URLs WHERE `URL` = :Url;');
		$urlHandler->bindValue(':Url', $url);
		$urlHandler->execute();

		if ($currentType = $urlHandler->fetch(PDO::FETCH_ASSOC)) {
			if ($currentType['type'] < $type) {
				// Update type and lastSeen + force revalidate
				$urlUpdateHandler = $this->metaDb->prepare("UPDATE URLs SET `type` = :Type, `lastValidated` = '1972-01-01', `lastSeen` = NOW() WHERE `URL` = :Url;");
				$urlUpdateHandler->bindParam(':Url', $url);
				$urlUpdateHandler->bindParam(':Type', $type);
				$urlUpdateHandler->execute();
			} else {
				// Update lastSeen
				$urlUpdateHandler = $this->metaDb->prepare("UPDATE URLs SET `lastSeen` = NOW() WHERE `URL` = :Url;");
				$urlUpdateHandler->bindParam(':Url', $url);
				$urlUpdateHandler->execute();
			}
		} else {
			$urlAddHandler = $this->metaDb->prepare("INSERT INTO URLs (`URL`, `type`, `status`, `lastValidated`, `lastSeen`) VALUES (:Url, :Type, 10, '1972-01-01', NOW());");
			$urlAddHandler->bindParam(':Url', $url);
			$urlAddHandler->bindParam(':Type', $type);
			$urlAddHandler->execute();
		}
	}

	public function validateURLs($limit=10){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, 'https://metadata.swamid.se/validate');

		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

		curl_setopt($ch, CURLINFO_HEADER_OUT, 1);

		$URLUpdateHandler = $this->metaDb->prepare("UPDATE URLs SET `lastValidated` = NOW(), `status` = :Status, `cocov1Status` = :Cocov1Status, `validationOutput` = :Result WHERE `URL` = :Url;");
		if ($limit > 10) {
			$sql = "SELECT `URL`, `type` FROM URLs WHERE `lastValidated` < ADDTIME(NOW(), '-7 0:0:0') OR ((`status` > 0 OR `cocov1Status` > 0) AND `lastValidated` < ADDTIME(NOW(), '-6:0:0')) ORDER BY `lastValidated` LIMIT $limit;";
		} else {
			$sql = "SELECT `URL`, `type` FROM URLs WHERE `lastValidated` < ADDTIME(NOW(), '-20 0:0:0') OR ((`status` > 0 OR `cocov1Status` > 0) AND `lastValidated` < ADDTIME(NOW(), '-8:0:0')) ORDER BY `lastValidated` LIMIT $limit;";
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
					$URLUpdateHandler->bindValue(':Cocov1Status', 1);
					$continue = false;
				} else {
					switch ($http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
						case 200 :
							switch ($URL['type']) {
								case 1 :
								case 2 :
									$URLUpdateHandler->bindValue(':Result', 'Reachable');
									$URLUpdateHandler->bindValue(':Status', 0);
									$URLUpdateHandler->bindValue(':Cocov1Status', 0);
									break;
								case 3 :
									if (strpos ( $output, 'http://www.geant.net/uri/dataprotection-code-of-conduct/v1') > 1 ) {
										$URLUpdateHandler->bindValue(':Result', 'Policy OK');
										$URLUpdateHandler->bindValue(':Status', 0);
										$URLUpdateHandler->bindValue(':Cocov1Status', 0);
									} else {
										$URLUpdateHandler->bindValue(':Result', 'Policy missing link to http://www.geant.net/uri/dataprotection-code-of-conduct/v1');
										$URLUpdateHandler->bindValue(':Status', 0);
										$URLUpdateHandler->bindValue(':Cocov1Status', 1);
									}
									break;
							}
							$continue = false;
							break;
						case 403 :
							$URLUpdateHandler->bindValue(':Result', "Access denied. Can't check URL.");
							$URLUpdateHandler->bindValue(':Status', 2);
							$URLUpdateHandler->bindValue(':Cocov1Status', 1);
							$continue = false;
							break;
						case 404 :
							$URLUpdateHandler->bindValue(':Result', 'Page not found.');
							$URLUpdateHandler->bindValue(':Status', 2);
							$URLUpdateHandler->bindValue(':Cocov1Status', 1);
							$continue = false;
							break;
						case 503 :
							$URLUpdateHandler->bindValue(':Result', "Service Unavailable. Can't check URL.");
							$URLUpdateHandler->bindValue(':Status', 2);
							$URLUpdateHandler->bindValue(':Cocov1Status', 1);
							$continue = false;
							break;
						default :
							$URLUpdateHandler->bindValue(':Result', "Contact operation@swamid.se. Got code $http_code from web-server. Cant handle :-(");
							$URLUpdateHandler->bindValue(':Status', 2);
							$URLUpdateHandler->bindValue(':Cocov1Status', 1);
							$continue = false;
					}
				}
			}
			$URLUpdateHandler->execute();
			$count ++;
		}
		curl_close($ch);
		if ($limit > 10)
			printf ("Checked %d URL:s\n", $count);
	}

	public function revalidateURL($url) {
		$urlUpdateHandler = $this->metaDb->prepare("UPDATE URLs SET `lastValidated` = '1972-01-01' WHERE `URL` = :Url;");
		$urlUpdateHandler->bindParam(':Url', $url);
		$urlUpdateHandler->execute();
		$this->validateURLs(5);
	}

	public function checkOldURLS($age = 30, $verbose = false) {
		$sql = sprintf("SELECT URL, lastSeen from URLs where lastSeen < ADDTIME(NOW(), '-%d 0:0:0')", $age);
		$URLHandler = $this->metaDb->prepare($sql);
		$URLHandler->execute();
		while ($URLInfo = $URLHandler->fetch(PDO::FETCH_ASSOC)) {
			if ($verbose) printf ("Checking : %s last seen %s\n", $URLInfo['URL'], $URLInfo['lastSeen']);
			$this->checkURLStatus($URLInfo['URL'], $verbose);
		}

	}

	private function checkURLStatus($url, $verbose = false){
		$URLHandler = $this->metaDb->prepare('SELECT `type`, `validationOutput`, `lastValidated` FROM URLs WHERE `URL` = :URL');
		$URLHandler->bindValue(':URL', $url);
		$URLHandler->execute();
		if ($URLInfo = $URLHandler->fetch(PDO::FETCH_ASSOC)) {
			$missing = true;
			$CoCoV1 = false;
			$Logo = false;
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
			if ($Entity = $EntityHandler->fetch(PDO::FETCH_ASSOC)) {
				$missing = false;
			}
			while ($Entity = $SSOUIIHandler->fetch(PDO::FETCH_ASSOC)) {
				if ($Entity['type'] == 'SPSSO' && $Entity['element'] == 'PrivacyStatementURL') {
					$entityAttributesHandler->bindParam(':Id', $Entity['entity_id']);
					$entityAttributesHandler->execute();
					while ($attribute = $entityAttributesHandler->fetch(PDO::FETCH_ASSOC)) {
						if ($attribute['attribute'] == 'http://www.geant.net/uri/dataprotection-code-of-conduct/v1') {
							$CoCoV1 = true;
						}
					}
				}
				switch ($Entity['element']) {
					case 'Logo' :
						$Logo = true;
					case 'InformationURL' :
					case 'PrivacyStatementURL' :
						$missing = false;
						break;
				}
			}
			while ($Entity = $OrganizationHandler->fetch(PDO::FETCH_ASSOC)) {
				if ($Entity['element'] == 'OrganizationURL') {
					$missing = false;
				}
			}
			if ($missing) {
				$URLHandler = $this->metaDb->prepare('DELETE FROM URLs WHERE `URL` = :URL');
				$URLHandler->bindValue(':URL', $url);
				$URLHandler->execute();
				if ($verbose) print "Removing URL. Not in use any more\n";
			} elseif ($URLInfo['type'] > 2 && !$CoCoV1 ) {
				if ($Logo)
					$URLHandler = $this->metaDb->prepare('UPDATE URLs SET `type` = 2 WHERE `URL` = :URL');
				else
					$URLHandler = $this->metaDb->prepare('UPDATE URLs SET `type` = 1 WHERE `URL` = :URL');
				$URLHandler->bindValue(':URL', $url);
				$URLHandler->execute();
				if ($verbose) print "Not CoCo v1 any more. Removes that flag.\n";

			}
		}
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
			$entityHandlerUpdate = $this->metaDb->prepare('UPDATE Entities SET `isIdP` = 0, `isSP` = 0, `xml` = :Xml , `lastUpdated` = NOW() WHERE `entityID` = :Id AND `status` = :Status;');
			$entityHandlerUpdate->bindValue(':Id', $this->entityID);
			$entityHandlerUpdate->bindValue(':Status', $this->status);
			$entityHandlerUpdate->bindValue(':Xml', $this->xml->saveXML());
			$entityHandlerUpdate->execute();
		} else {
			# Add new entity into database
			$entityHandlerInsert = $this->metaDb->prepare('INSERT INTO Entities (`entityID`, `isIdP`, `isSP`, `publishIn`, `status`, `xml`, `lastUpdated`) VALUES(:Id, 0, 0, 0, :Status, :Xml, NOW());');
			$entityHandlerInsert->bindValue(':Id', $this->entityID);
			$entityHandlerInsert->bindValue(':Status', $this->status);
			$entityHandlerInsert->bindValue(':Xml', $this->xml->saveXML());
			$entityHandlerInsert->execute();
			$this->dbIdNr = $this->metaDb->lastInsertId();
		}
		$this->entityExists = true;
	}

	# Creates / updates XML from Published into Draft
	public function createDraft() {
		if ($this->entityExists && ($this->status == 1 || $this->status == 4)) {
			# Add new entity into database
			$entityHandlerInsert = $this->metaDb->prepare('INSERT INTO Entities (`entityID`, `isIdP`, `isSP`, `publishIn`, `status`, `xml`, `lastUpdated`) VALUES(:Id, 0, 0, 0, 3, :Xml, NOW());');
			$entityHandlerInsert->bindValue(':Id', $this->entityID);
			$entityHandlerInsert->bindValue(':Xml', $this->xml->saveXML());
			$entityHandlerInsert->execute();
			$oldDbNr = $this->dbIdNr;
			$this->result = "";
			$this->dbIdNr = $this->metaDb->lastInsertId();
			$this->status = 3;
			$this->copyResponsible($oldDbNr);
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
		$entityAttributesHandler = $this->metaDb->prepare('INSERT INTO EntityAttributes (`entity_id`, `type`, `attribute`) VALUES (:Id, :Type, :Value);');

		if (! $data->hasAttribute('NameFormat') && $data->hasAttribute('Name')) {
			switch ($data->getAttribute('Name')) {
				case 'http://macedir.org/entity-category' :
				case 'http://macedir.org/entity-category-support' :
				case 'urn:oasis:names:tc:SAML:attribute:assurance-certification' :
				case 'urn:oasis:names:tc:SAML:profiles:subject-id:req' :
				case 'http://www.swamid.se/assurance-requirement' :
					$data->setAttribute('NameFormat', 'urn:oasis:names:tc:SAML:2.0:attrname-format:uri');
					$this->result .= sprintf("Added NameFormat urn:oasis:names:tc:SAML:2.0:attrname-format:uri to Extensions/EntityAttributes/Attribute/%s.\n", $data->getAttribute('Name'));;
					break;
			}
		}
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

			$entityAttributesHandler->bindValue(':Id', $this->dbIdNr);
			$entityAttributesHandler->bindValue(':Type', $attributeType);

			$child = $data->firstChild;
			while ($child) {
				if ($child->nodeName == 'samla:AttributeValue') {
					$entityAttributesHandler->bindValue(':Value', trim($child->textContent));
					$entityAttributesHandler->execute();
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
		$keyOrder = 0;
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
					$this->parseKeyDescriptor($child, 'IDPSSO', $keyOrder++);
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
		$this->SWAMID_6_1_16_error = false;
		$keyOrder = 0;
		# https://docs.oasis-open.org/security/saml/v2.0/saml-metadata-2.0-os.pdf 2.4.1 + 2.4.2 + 2.4.4
		$child = $data->firstChild;
		while ($child) {
			switch ($child->nodeName) {
				# 2.4.1
				#case 'md:Signature' :
				case 'md:Extensions' :
					$this->parseSPSSODescriptor_Extensions($child);
					break;
				case 'md:KeyDescriptor' :
					$this->parseKeyDescriptor($child, 'SPSSO', $keyOrder++);
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
		if ($this->SWAMID_6_1_16_error)
			$this->cleanOutAssertionConsumerServiceHTTPRedirect();
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
			switch ($child->nodeName) {
				case 'md:ServiceName' :
					$lang = $child->getAttribute('xml:lang') ? $child->getAttribute('xml:lang') : '';
					$ServiceElementHandler->bindValue(':Element', 'ServiceName');
					$ServiceElementHandler->bindValue(':Data', trim($child->textContent));
					$ServiceElementHandler->execute();
					$ServiceNameFound = true;
					break;
				case 'md:ServiceDescription' :
					$lang = $child->getAttribute('xml:lang') ? $child->getAttribute('xml:lang') : '';
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
							switch ($NameFormat) {
								case 'urn:oasis:names:tc:SAML:2.0:attrname-format:uri' :
									#OK;
									break;
								case 'urn:mace:shibboleth:1.0:attributeNamespace:uri' :
									$this->warning .= sprintf("SAML1 NameFormat %s for %s in RequestedAttribute for index %d is not recomended.\n", $NameFormat, $Name, $index);
									break;
								default :
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
		$keyOrder = 0;
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
					$this->parseKeyDescriptor($child, 'AttributeAuthority', $keyOrder++);
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
		$SSOUIIHandler->bindParam(':Value', $value);

		# https://docs.oasis-open.org/security/saml/Post2.0/sstc-saml-metadata-ui/v1.0/os/sstc-saml-metadata-ui-v1.0-os.html
		$child = $data->firstChild;
		while ($child) {
			if ($child->nodeType != 8) {
				$lang = $child->getAttribute('xml:lang') ? $child->getAttribute('xml:lang') : '';
				$URLtype = 1;
				switch ($child->nodeName) {
					case 'mdui:Logo' :
						$URLtype = 2;
					case 'mdui:InformationURL' :
					case 'mdui:PrivacyStatementURL' :
						$this->addURL(trim($child->textContent), $URLtype);
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

				$value = trim($child->textContent);
				$SSOUIIHandler->execute();
				if ($value == '') {
					$this->error .= sprintf ("Missing value for element %s in %s->MDUI.\n", $element, $type);
				}
			}
			$child = $child->nextSibling;
		}
	}

	#############
	# KeyDescriptor
	# Used by AttributeAuthority, IDPSSODescriptor and SPSSODescriptor
	#############
	private function parseKeyDescriptor($data, $type, $order) {
		$use = $data->getAttribute('use') ? $data->getAttribute('use') : 'both';
		#'{urn:oasis:names:tc:SAML:2.0:metadata}EncryptionMethod':
		$child = $data->firstChild;
		while ($child) {
			switch ($child->nodeName) {
				case 'ds:KeyInfo' :
					$this->parseKeyDescriptor_KeyInfo($child, $type, $use, $order);
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
	private function parseKeyDescriptor_KeyInfo($data, $type, $use, $order) {
		$name = '';
		$child = $data->firstChild;
		while ($child) {
			switch ($child->nodeName) {
				case 'ds:KeyName' :
					$name = trim($child->textContent);
					break;
				case 'ds:X509Data' :
					$this->parseKeyDescriptor_KeyInfo_X509Data($child, $type, $use, $order, $name);
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
	private function parseKeyDescriptor_KeyInfo_X509Data($data, $type, $use, $order, $name) {
		$KeyInfoHandler = $this->metaDb->prepare('INSERT INTO KeyInfo (`entity_id`, `type`, `use`, `order`, `name`, `notValidAfter`, `subject`, `issuer`, `bits`, `key_type`, `serialNumber`) VALUES (:Id, :Type, :Use, :Order, :Name, :NotValidAfter, :Subject, :Issuer, :Bits, :Key_type, :SerialNumber);');

		$KeyInfoHandler->bindValue(':Id', $this->dbIdNr);
		$KeyInfoHandler->bindValue(':Type', $type);
		$KeyInfoHandler->bindValue(':Use', $use);
		$KeyInfoHandler->bindValue(':Order', $order);
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
						$KeyInfoHandler->bindParam(':SerialNumber', $cert_info['serialNumber']);
					} else {
						$KeyInfoHandler->bindValue(':NotValidAfter', '1970-01-01 00:00:00');
						$KeyInfoHandler->bindValue(':Subject', '?');
						$KeyInfoHandler->bindValue(':Issuer', '?');
						$KeyInfoHandler->bindValue(':Bits', 0);
						$KeyInfoHandler->bindValue(':Key_type', '?');
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

		$this->isSP_RandS = false;
		$this->isSP_CoCov1 = false;
		$this->isSP_CoCov2 = false;
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
				case 'https://refeds.org/category/code-of-conduct/v2' :
					if ($entityAttribute['type'] == 'entity-category' && $this->isSP)
						$this->isSP_CoCov2 = true;
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
			$this->checkRequiredMDUIelementsIdP();
			// 5.1.20, 5.2.x
			$this->checkRequiredSAMLcertificates('IDPSSO');
		}
		if ($this->isSP) {
			// 6.1.9 -> 6.1.11
			$this->checkEntityAttributes('SPSSO');
			// 6.1.12
			$this->checkRequiredMDUIelementsSP();
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
		if ($this->isSP_CoCov2) $this->validateSPCoCov2();

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
		} else {
			$entityAttributesHandler->bindValue(':Type', 'entity-category');
			$entityAttributesHandler->execute();
			while ($entityAttribute = $entityAttributesHandler->fetch(PDO::FETCH_ASSOC)) {
				foreach ($this->standardAttributes['entity-category'] as $data) {
					if ($data['value'] == $entityAttribute['attribute'] && ! $data['swamidStd']) {
						$this->error .= sprintf ("Entity Category Error: The entity category %s is deprecated.\n", $entityAttribute['attribute']);
					}
				}
			}
		}
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

	// 5.1.17
	private function checkRequiredMDUIelementsIdP() {
		$elementArray = array ('DisplayName' => false, 'Description' => false, 'InformationURL' => false, 'PrivacyStatementURL' => false, 'Logo' => false);
		$mduiDNUniqHandler = $this->metaDb->prepare("SELECT `entityID` FROM Entities, Mdui WHERE `id` = `entity_id` AND `type`  = 'IDPSSO' AND `element` = 'DisplayName' AND `data` = :Data AND `lang` = :Lang AND `status` = 1 AND `entityID` <> :EntityID;");
		$mduiDNUniqHandler->bindParam(':Data', $data);
		$mduiDNUniqHandler->bindParam(':Lang', $lang);
		$mduiDNUniqHandler->bindParam(':EntityID', $entityID);
		$mduiHandler = $this->metaDb->prepare('SELECT `entityID`, `element`, `data`, `lang` FROM Entities, Mdui WHERE `id` = `entity_id` AND `entity_id` = :Id AND `type`  = :Type ;');
		$mduiHandler->bindValue(':Id', $this->dbIdNr);
		$mduiHandler->bindValue(':Type', 'IDPSSO');
		$mduiHandler->execute();
		while ($mdui = $mduiHandler->fetch(PDO::FETCH_ASSOC)) {
			$elementArray[$mdui['element']] = true;
			switch($mdui['element']) {
				case 'DisplayName' :
					$data = $mdui['data'];
					$lang = $mdui['lang'];
					$entityID = $mdui['entityID'];
					$mduiDNUniqHandler->execute();
					while ($duplicate = $mduiDNUniqHandler->fetch(PDO::FETCH_ASSOC)) {
						$this->error .= sprintf("SWAMID Tech 5.1.17: DisplayName for lang %s is also set on %s.\n", $lang, $duplicate['entityID']);
					}
					break;
				case 'Logo' :
					if (substr($mdui['data'],0,8) != 'https://') {
						$this->error .= "SWAMID Tech 5.1.17: Logo must start with <b>https://</b> .\n";
					}
					break;
				case 'InformationURL' :
				case 'PrivacyStatementURL' :
					if (substr($mdui['data'],0,8) != 'https://' && substr($mdui['data'],0,7) != 'http://') {
						$this->error .= sprintf('SWAMID Tech 5.1.17: %s must be a URL%s', $mdui['element'], ".\n");
					}
					break;
			}
		}

		foreach ($elementArray as $element => $value) {
			if (! $value) {
				$this->error .= sprintf("SWAMID Tech 5.1.17: Missing mdui:%s in IDPSSODecriptor.\n", $element);
			}
		}
	}

	// 6.1.12
	private function checkRequiredMDUIelementsSP() {
		$elementArray = array ('DisplayName' => false, 'Description' => false, 'InformationURL' => false, 'PrivacyStatementURL' => false);
		$mduiHandler = $this->metaDb->prepare("SELECT DISTINCT `element`, `data` FROM Mdui WHERE `entity_id` = :Id AND `type`  = 'SPSSO';");
		$mduiHandler->bindValue(':Id', $this->dbIdNr);
		$mduiHandler->execute();
		while ($mdui = $mduiHandler->fetch(PDO::FETCH_ASSOC)) {
			$elementArray[$mdui['element']] = true;
			switch($mdui['element']) {
				case 'Logo' :
					if (substr($mdui['data'],0,8) != 'https://') {
						$this->error .= "SWAMID Tech 6.1.13: Logo must start with <b>https://</b> .\n";
					}
					break;
				case 'InformationURL' :
				case 'PrivacyStatementURL' :
					if (substr($mdui['data'],0,8) != 'https://' && substr($mdui['data'],0,7) != 'http://') {
						$this->error .= sprintf('SWAMID Tech 6.1.12: %s must be a URL%s', $mdui['element'], ".\n");
					}
					break;
			}
		}

		foreach ($elementArray as $element => $value) {
			if (! $value) {
				$this->error .= sprintf("SWAMID Tech 6.1.12: Missing mdui:%s in SPSSODescriptor.\n", $element);
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
		$SWAMID_5_2_1_Level_2030 = array ('encryption' => 0, 'signing' => 0, 'both' => 0);
		$SWAMID_5_2_1_error = 0;
		$SWAMID_5_2_1_2030_error = 0;
		$SWAMID_5_2_2_error = false;
		$SWAMID_5_2_2_errorNB = false;
		$SWAMID_5_2_3_warning = false;
		$validEncryptionFound = false;
		$validSigningFound = false;
		$oldCertFound = false;
		$smalKeyFound = false;
		$keyFound = false;
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
					if ($keyInfo['bits'] >= 4096 ) {
						$SWAMID_5_2_1_Level[$keyInfo['use']] = 3;
					} elseif ($keyInfo['bits'] >= 2048 && $SWAMID_5_2_1_Level[$keyInfo['use']] < 2 ) {
						if ($keyInfo['notValidAfter'] > '2030-12-31' && $keyInfo['bits'] < 3072) {
							$SWAMID_5_2_1_Level_2030[$keyInfo['use']] = true;
						}
						$SWAMID_5_2_1_Level[$keyInfo['use']] = 2;
					} elseif ($SWAMID_5_2_1_Level[$keyInfo['use']] < 1) {
						$SWAMID_5_2_1_Level[$keyInfo['use']] = 1;
					}
					if ($keyInfo['bits'] < 2048) $smalKeyFound = true;
					break;
				case 'EC' :
					if ($keyInfo['bits'] >= 384 ) {
							$SWAMID_5_2_1_Level[$keyInfo['use']] = 3;
					} elseif ($keyInfo['bits'] >= 256 && $SWAMID_5_2_1_Level[$keyInfo['use']] < 2 ) {
							$SWAMID_5_2_1_Level[$keyInfo['use']] = 2;
						} else {
							$SWAMID_5_2_1_Level[$keyInfo['use']] = 1;
						}
					if ($keyInfo['bits'] < 256) $smalKeyFound = true;
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
				$this->error .= "SWAMID Tech 5.1.20: Identity Providers there MUST have at least one valid signing certificate.\n";
			else
				$this->error .= "SWAMID Tech 6.1.14: Service Providers MUST have at least one valid encryption certificate.\n";
		}
		// 5.2.1 Identity Provider credentials (i.e. entity keys) MUST NOT use shorter comparable key strength (in the sense of NIST SP 800-57) than a 2048-bit RSA key. 4096-bit is RECOMMENDED.
		// 6.2.1 Relying Party credentials (i.e. entity keys) MUST NOT use shorter comparable key strength (in the sense of NIST SP 800-57) than 2048-bit RSA/DSA keysor 256-bit ECC keys. 4096-bit RSA/DSAkeysor 384-bitECC keys are RECOMMENDED
		// At least one cert exist that is used for either signing or encryption, Error = code for cert with lowest # of bits
		foreach (array('encryption', 'signing') as $use) {
			if ($SWAMID_5_2_1_Level[$use] > 0) {
				switch ($SWAMID_5_2_1_Level[$use]) {
					case 3 :
						// Key >= 4096 or >= 384
						// Do nothing. Keep current level.
						break;
					case 2 :
						// Key >= 2048 and < 4096  // >= 256 and <384
						$SWAMID_5_2_1_error = $SWAMID_5_2_1_error == 0 ? 1 : $SWAMID_5_2_1_error;
						$SWAMID_5_2_1_2030_error = $SWAMID_5_2_1_2030_error ? true : $SWAMID_5_2_1_Level_2030[$use];
						break;
					case 1:
						// To small key
						$SWAMID_5_2_1_error = 2;
				}
				$keyFound = true;
			}
		}

		// Cert exist that is used for both signing and encryption
		if ($SWAMID_5_2_1_Level['both'] > 0) {
			// Error code could get better if both is better than encryption/signing
			switch ($SWAMID_5_2_1_Level['both']) {
				case 3 :
					// Key >= 4096 or >= 384
					$SWAMID_5_2_1_error = 0;
					$SWAMID_5_2_1_2030_error = false;
					break;
				case 2 :
					// Key >= 2048 and < 4096  // >= 256 and <384
					if ($keyFound) {
						// If already checked enc/signing lower if we are better
						$SWAMID_5_2_1_error = $SWAMID_5_2_1_error > 1 ? 1 : $SWAMID_5_2_1_error;
					} else {
						// No enc/siging found set warning
						$SWAMID_5_2_1_error = 1;
					}
					$SWAMID_5_2_1_2030_error = $SWAMID_5_2_1_2030_error ? true : $SWAMID_5_2_1_Level_2030['both'];
					break;
				case 1:
					// To small key
					// Flagg if no enc/signing was found
					if (! $keyFound) {
						$SWAMID_5_2_1_error = 2;
					}
			}
		}

		if ($SWAMID_5_2_1_error) {
			if ($SWAMID_5_2_1_error == 1) {
				if ($smalKeyFound) {
					$this->errorNB .= sprintf("SWAMID Tech %s: (NonBreaking) Certificate MUST NOT use shorter comparable key strength (in the sense of NIST SP 800-57) than a 2048-bit RSA key.\n", ($type == 'IDPSSO') ? '5.2.1' : '6.2.1');
				} else {
					$this->warning .= sprintf("SWAMID Tech %s: Certificate key strength under 4096-bit RSA is NOT RECOMMENDED.\n", ($type == 'IDPSSO') ? '5.2.1' : '6.2.1');
				}
			} elseif ($SWAMID_5_2_1_error == 2) {
				$this->error .= sprintf("SWAMID Tech %s: Certificate MUST NOT use shorter comparable key strength (in the sense of NIST SP 800-57) than a 2048-bit RSA key. New certificate should be have a key strength of at least 4096 bits for RSA or 384 bits for EC.\n", ($type == 'IDPSSO') ? '5.2.1' : '6.2.1');
			}
		} else {
			if ($smalKeyFound) {
				$this->errorNB .= sprintf("SWAMID Tech %s: (NonBreaking) Certificate MUST NOT use shorter comparable key strength (in the sense of NIST SP 800-57) than a 2048-bit RSA key.\n", ($type == 'IDPSSO') ? '5.2.1' : '6.2.1');
			}
		}
		if ($SWAMID_5_2_1_2030_error) {
			$this->warning .= sprintf("SWAMID Tech %s: Certificate MUST NOT use shorter comparable key strength (in the sense of NIST SP 800-57) than a 3072-bit RSA key if valid after 2030-12-31.\n", ($type == 'IDPSSO') ? '5.2.1' : '6.2.1');
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
		if ($Binding == 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect') {
			$this->SWAMID_6_1_16_error = true;
		}
	}

	// 5.1.22 / 6.1.21
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

	// 5.1.23 -> 5.1.28 / 6.1.22 -> 6.1.26
	private function checkRequiredContactPersonElements($type) {
		$usedContactTypes = array();
		$contactPersonHandler = $this->metaDb->prepare('SELECT `contactType`, `subcontactType`, `emailAddress`, `givenName` FROM ContactPerson WHERE `entity_id` = :Id;');
		$contactPersonHandler->bindValue(':Id', $this->dbIdNr);
		$contactPersonHandler->execute();

		while ($contactPerson = $contactPersonHandler->fetch(PDO::FETCH_ASSOC)) {
			$contactType = $contactPerson['contactType'];

			// 5.1.28 Identity Providers / 6.1.27 A Relying Party SHOULD have one ContactPerson element of contactType other with remd:contactType http://refeds.org/metadata/contactType/security.
			// If the element is present, a GivenName element MUST be present and the ContactPerson MUST respect the Traffic Light Protocol (TLP) during all incident response correspondence.
			if ($contactType == 'other' &&  $contactPerson['subcontactType'] == 'security' ) {
				$contactType = 'other/security';
				if ( $contactPerson['givenName'] == '')
					$this->error .= $this->selectError('5.1.28', '6.1.27', 'GivenName element MUST be present for security ContactPerson.');
			}

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
		if (!isset ($usedContactTypes['other/security'])) {
			if ($this->isSIRTFI) {
				$this->error .= "REFEDS Sirtfi Require that a security contact is published in the entity’s metadata.\n";
			} else
				$this->warning .= $this->selectError('5.1.28', '6.1.27', 'Missing security ContactPerson.');
		}
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
				$this->addURL($data, 3);
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

	# Validate CoCoSP v2
	private function validateSPCoCov2() {
		$mduiArray = array();
		$mduiElementArray = array();
		$mduiHandler = $this->metaDb->prepare("SELECT `lang`, `element`, `data` FROM Mdui WHERE `type` = 'SPSSO' AND `entity_id` = :Id;");
		$requestedAttributeHandler = $this->metaDb->prepare('SELECT DISTINCT `Service_index` FROM AttributeConsumingService_RequestedAttribute WHERE `entity_id` = :Id;');
		$entityAttributesHandler =  $this->metaDb->prepare('SELECT attribute FROM EntityAttributes WHERE `type` = :Type AND `entity_id` = :Id;');
		$mduiHandler->bindValue(':Id', $this->dbIdNr);
		$requestedAttributeHandler->bindValue(':Id', $this->dbIdNr);
		$entityAttributesHandler->bindValue(':Id', $this->dbIdNr);
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
				$this->error .= "GÉANT Data Protection Code of Conduct (v2) Require a MDUI - PrivacyStatementURL with at least lang=en.\n";
			if (! isset($mduiArray['en']['DisplayName']))
				$this->warning .= "GÉANT Data Protection Code of Conduct (v2) Recomend a MDUI - DisplayName with at least lang=en.\n";
			if (! isset($mduiArray['en']['Description']))
				$this->warning .= "GÉANT Data Protection Code of Conduct (v2) Recomend a MDUI - Description with at least lang=en.\n";
			foreach ($mduiElementArray as $element => $value) {
				if (! isset($mduiArray['en'][$element]))
					$this->error .= sprintf("GÉANT Data Protection Code of Conduct (v2) Require a MDUI - %s with lang=en for all present elements.\n", $element);
			}
		} else {
			$this->error .= "GÉANT Data Protection Code of Conduct (v2) Require MDUI with lang=en.\n";
		}

		$requestedAttributeHandler->execute();
		if (! $requestedAttributeHandler->fetch(PDO::FETCH_ASSOC)) {
			$entityAttributesHandler->bindValue(':Type', 'subject-id:req');
			$entityAttributesHandler->execute();
			if (! $entityAttributesHandler->fetch(PDO::FETCH_ASSOC)) {
				$this->error .= "GÉANT Data Protection Code of Conduct (v2) Require at least one RequestedAttribute OR subject-id:req entity attribute extension.\n";
			}
		}
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
	# Removes AssertionConsumerService with binding = HTTP-Redirect.
	# SWAMID_6_1_16_error
	#############
	private function cleanOutAssertionConsumerServiceHTTPRedirect() {
		$removed = false;
		$EntityDescriptor = $this->getEntityDescriptor($this->xml);
		$child = $EntityDescriptor->firstChild;
		while ($child) {
			if ($child->nodeName == 'md:SPSSODescriptor') {
				$subchild = $child->firstChild;
				while ($subchild) {
					if ($subchild->nodeName == 'md:AssertionConsumerService' && $subchild->getAttribute('Binding') == 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect') {
						$index = $subchild->getAttribute('index');
						$remChild = $subchild;
						$child->removeChild($remChild);
						$subchild = false;
						$child=false;
						$removed = true;
					} else
						$subchild = $subchild->nextSibling;
				}
			} else
				$child = $child->nextSibling;
		}
		if ($removed)
			$this->error .= sprintf("SWAMID Tech 6.1.16: Binding with value urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect is no allowed in SPSSODescriptor->AssertionConsumerService[index=%d]. Have been removed.\n", $index);
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
		$this->feedValue = $publishIn;
	}

	#############
	# Updates which feeds by value
	#############
	public function updateFeedByValue($publishIn) {
		$publishedHandler = $this->metaDb->prepare('UPDATE Entities SET `publishIn` = :PublishIn WHERE `id` = :Id;');
		$publishedHandler->bindValue(':Id', $this->dbIdNr);
		$publishedHandler->bindValue(':PublishIn', $publishIn);
		$publishedHandler->execute();
		$this->feedValue = $publishIn;
	}

	#############
	# Updates which user that is responsible for an entity
	#############
	public function updateResponsible() {
		$entityUserHandler = $this->metaDb->prepare('INSERT INTO EntityUser (`entity_id`, `user_id`, `lastChanged`) VALUES(:Entity_Id, :User_Id, NOW()) ON DUPLICATE KEY UPDATE `lastChanged` = NOW()');
		$entityUserHandler->bindParam(':Entity_Id', $this->dbIdNr);
		$entityUserHandler->bindParam(':User_Id', $this->user['id']);
		$entityUserHandler->execute();
	}

	#############
	# Copies which user that is responsible for an entity from another entity
	#############
	public function copyResponsible($otherEntity_id) {
		$entityUserHandler = $this->metaDb->prepare('INSERT INTO EntityUser (`entity_id`, `user_id`, `lastChanged`) VALUES(:Entity_Id, :User_Id, :LastChanged) ON DUPLICATE KEY UPDATE `lastChanged` = :LastChanged');
		$otherEntityUserHandler = $this->metaDb->prepare('SELECT `user_id`, `lastChanged` FROM EntityUser WHERE `entity_id` = :OtherEntity_Id');

		$entityUserHandler->bindParam(':Entity_Id', $this->dbIdNr);
		$otherEntityUserHandler->bindParam(':OtherEntity_Id', $otherEntity_id);
		$otherEntityUserHandler->execute();
		while ($otherEntityUser = $otherEntityUserHandler->fetch(PDO::FETCH_ASSOC)) {
			$entityUserHandler->bindParam(':User_Id', $otherEntityUser['user_id']);
			$entityUserHandler->bindParam(':LastChanged', $otherEntityUser['lastChanged']);
			$entityUserHandler->execute();
		}
	}

	#############
	# Updates lastUpdated for an entity
	#############
	public function updateLastUpdated($date) {
		$entityHandlerUpdate = $this->metaDb->prepare('UPDATE Entities SET `lastUpdated` = :Date WHERE `id` = :Id;');
		$entityHandlerUpdate->bindValue(':Id', $this->dbIdNr);
		$entityHandlerUpdate->bindValue(':Date', $date);
		$entityHandlerUpdate->execute();
	}

	#############
	# Removes an entity from database
	#############
	public function removeEntity() {
		$this->_removeEntity($this->dbIdNr);
	}
	private function _removeEntity($dbIdNr) {
		$entityHandler = $this->metaDb->prepare('SELECT publishedId FROM Entities WHERE id = :Id;');
		$entityHandler->bindParam(':Id', $dbIdNr);
		$entityHandler->execute();
		if ($Entity = $entityHandler->fetch(PDO::FETCH_ASSOC)) {
			if ($Entity['publishedId'] > 0) {
				#Remove shadow first
				$this->_removeEntity($Entity['publishedId']);
			}
			# Remove data for this Entity
			$this->metaDb->exec('DELETE FROM EntityAttributes WHERE `entity_id` = ' . $dbIdNr .';');
			$this->metaDb->exec('DELETE FROM Mdui WHERE `entity_id` = ' . $dbIdNr .';');
			$this->metaDb->exec('DELETE FROM KeyInfo WHERE `entity_id` = ' . $dbIdNr .';');
			$this->metaDb->exec('DELETE FROM AttributeConsumingService WHERE `entity_id` = ' . $dbIdNr .';');
			$this->metaDb->exec('DELETE FROM AttributeConsumingService_Service WHERE `entity_id` = ' . $dbIdNr .';');
			$this->metaDb->exec('DELETE FROM AttributeConsumingService_RequestedAttribute WHERE `entity_id` = ' . $dbIdNr .';');
			$this->metaDb->exec('DELETE FROM Organization WHERE `entity_id` = ' . $dbIdNr .';');
			$this->metaDb->exec('DELETE FROM ContactPerson WHERE `entity_id` = ' . $dbIdNr .';');
			$this->metaDb->exec('DELETE FROM EntityURLs WHERE `entity_id` = ' . $dbIdNr .';');
			$this->metaDb->exec('DELETE FROM Scopes WHERE `entity_id` = ' . $dbIdNr .';');
			$this->metaDb->exec('DELETE FROM EntityUser WHERE `entity_id` = ' . $dbIdNr .';');
			$this->metaDb->exec('DELETE FROM Entities WHERE `id` = ' . $dbIdNr .';');
		}
	}

	#############
	# Check if an entity from pendingQueue exists with same XML in published
	#############
	public function checkPendingIfPublished() {
		$pendingHandler = $this->metaDb->prepare('SELECT `entityID`, `xml`, `lastUpdated` FROM Entities WHERE `status` = 2 AND `id` = :Id');
		$pendingHandler->bindParam(':Id', $this->dbIdNr);
		$pendingHandler->execute();

		$publishedHandler = $this->metaDb->prepare('SELECT `xml`, `lastUpdated` FROM Entities WHERE `status` = 1 AND `entityID` = :EntityID');
		$publishedHandler->bindParam(':EntityID', $entityID);

		require_once $this->baseDir.'/include/NormalizeXML.php';
		$normalize = new NormalizeXML();

		if ($pendingEntity = $pendingHandler->fetch(PDO::FETCH_ASSOC)) {
			$entityID = $pendingEntity['entityID'];

			$normalize->fromString($pendingEntity['xml']);
			if ($normalize->getStatus() && $normalize->getEntityID() == $entityID) {
				$pendingXML = $normalize->getXML();
				$publishedHandler->execute();
				if ($publishedEntity = $publishedHandler->fetch(PDO::FETCH_ASSOC)) {
					if ($pendingEntity['lastUpdated'] < $publishedEntity['lastUpdated']) {
						if ($pendingXML == $publishedEntity['xml']) {
							return true;
						} else {
							// For new Entities remove RegistrationInfo and compare
							$noRegistrationInfo = $normalize->cleanOutRegistrationInfo($publishedEntity['xml']);
							$normalize->fromString($noRegistrationInfo);
							if ($normalize->getStatus() && $normalize->getEntityID() == $entityID) {
								$publishedXML = $normalize->getXML();
								if ($pendingXML == $publishedXML) {
									return true;
								}
							}
						}
					}
				}
			}
		}
		return false;
	}

	#############
	# Moves an entity from Published to SoftDelete state
	#############
	public function move2SoftDelete() {
		$entityHandler = $this->metaDb->prepare('UPDATE Entities SET `status` = 4, `lastUpdated` = NOW() WHERE `status` = 1 AND `id` = :Id');
		$entityHandler->bindParam(':Id', $this->dbIdNr);
		$entityHandler->execute();
	}

	#############
	# Moves an entity from pendingQueue to publishedPending state
	#############
	public function movePublishedPending() {
		# Check if entity id exist as status pending
		if ($this->status == 2) {
			$publishedEntityHandler = $this->metaDb->prepare('SELECT `id` FROM Entities WHERE `status` = 1 AND `entityID` = :Id');
			# Get id of published version
			$publishedEntityHandler->bindParam(':Id', $this->entityID);
			$publishedEntityHandler->execute();
			if ($publishedEntity = $publishedEntityHandler->fetch(PDO::FETCH_ASSOC)) {
				$entityHandler = $this->metaDb->prepare('SELECT `lastValidated` FROM Entities WHERE `id` = :Id');
				$entityUserHandler = $this->metaDb->prepare('SELECT `user_id`, `lastChanged` FROM EntityUser WHERE `entity_id` = :Entity_Id');
				$addEntityUserHandler = $this->metaDb->prepare('INSERT INTO EntityUser (`entity_id`, `user_id`, `lastChanged`) VALUES(:Entity_Id, :User_Id, :LastChanged) ON DUPLICATE KEY UPDATE `lastChanged` = :LastChanged WHERE `lastChanged` < :LastChanged');
				$updateEntityConfirmationHandler = $this->metaDb->prepare('INSERT INTO EntityConfirmation (`entity_id`, `user_id`, `lastConfirmed`) VALUES (:Entity_Id, :User_Id, :LastConfirmed) ON DUPLICATE KEY UPDATE `user_id` = :User_Id, `lastConfirmed` = :LastConfirmed');

				# Get lastValidated
				$entityHandler->bindParam(':Id', $this->dbIdNr);
				$entityHandler->execute();
				$entity = $entityHandler->fetch(PDO::FETCH_ASSOC);

				$addEntityUserHandler->bindParam(':Entity_id', $publishedEntity['id']);

				# Get users having access to this entityID
				$entityUserHandler->bindParam(':Id', $this->dbIdNr);
				$entityUserHandler->execute();
				while ($entityUser = $entityUserHandler->fetch(PDO::FETCH_ASSOC)) {
					# Copy userId from pending -> published
					$addEntityUserHandler->bindValue(':User_ID', $entityUser['user_id']);
					$addEntityUserHandler->bindValue(':LastChanged', $entityUser['lastChanged']);
					$addEntityUserHandler->execute();
				}
				# Set lastValidated on Pending as lastConfirmed on Published
				$updateEntityConfirmationHandler->bindParam(':Entity_Id', $this->dbIdNr);
				$updateEntityConfirmationHandler->bindParam(':User_Id', $entityUser['user_id']);
				$updateEntityConfirmationHandler->bindParam(':LastConfirmed', $entity['lastValidated']);
				$updateEntityConfirmationHandler->execute();
			}
			# Move entity to status Pending
			$entityUpdateHandler = $this->metaDb->prepare('UPDATE Entities SET `status` = 5, `lastUpdated` = NOW() WHERE `status` = 2 AND `id` = :Id');
			$entityUpdateHandler->bindParam(':Id', $this->dbIdNr);
			$entityUpdateHandler->execute();
		}
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

	#############
	# Return entityID for this entity
	#############
	public function EntityID() {
		return $this->entityID;
	}

	#############
	# Return if this entity is an IdP
	#############
	public function isIdP() {
		return $this->isIdP;
	}

	#############
	# Return if this entity is an SP
	#############
	public function isSP() {
		return $this->isSP;
	}

	#############
	# Moves a Draft into Pending state
	#############
	public function moveDraftToPending($publishedEntity_id) {
		$this->addRegistrationInfo();
		$entityHandler = $this->metaDb->prepare('UPDATE Entities SET `status` = 2, `publishedId` = :PublishedId, `xml` = :Xml WHERE `status` = 3 AND `id` = :Id;');
		$entityHandler->bindParam(':Id', $this->dbIdNr);
		$entityHandler->bindParam(':PublishedId', $publishedEntity_id);
		$entityHandler->bindValue(':Xml', $this->xml->saveXML());

		$entityHandler->execute();
	}

	private function addRegistrationInfo() {
		$EntityDescriptor = $this->getEntityDescriptor($this->xml);
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
			$ts=date("Y-m-d\TH:i:s\Z");
			$EntityDescriptor->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:mdrpi', 'urn:oasis:names:tc:SAML:metadata:rpi');
			$RegistrationInfo = $this->newXml->createElement('mdrpi:RegistrationInfo');
			$RegistrationInfo->setAttribute('registrationAuthority', 'http://www.swamid.se/');
			$RegistrationInfo->setAttribute('registrationInstant', $ts);
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

	#############
	# Return status # for this entity
	#############
	public function status() {
		return $this->status;
	}


	#############
	# Return if this entity exists in the database
	#############
	public function EntityExists() {
		return $this->entityExists;
	}

	#############
	# Return ID for this entity in the database
	#############
	public function ID() {
		return $this->dbIdNr;
	}

	#############
	# Return feed value for this entity in the database
	#############
	public function feedValue() {
		return $this->feedValue;
	}

	public function getTechnicalAndAdministrativeContacts() {
		$addresses = array();

		# If entity in Published will only match one. If entity in draft, will match both draft and published and get addresses from both.
		$contactHandler = $this->metaDb->prepare("SELECT DISTINCT emailAddress FROM Entities, ContactPerson WHERE id = entity_id AND ((entityID = :EntityID AND status = 1) OR (id = :Entity_ID AND status = 3)) AND (contactType='technical' OR contactType='administrative') AND emailAddress <> ''");
		$contactHandler->bindParam(':EntityID',$this->entityID);
		$contactHandler->bindParam(':Entity_ID',$this->dbIdNr);
		$contactHandler->execute();
		while ($address = $contactHandler->fetch(PDO::FETCH_ASSOC)) {
			$addresses[] = substr($address['emailAddress'],7);
		}
		return $addresses;
	}

	#############
	# Return XML for this entity in the database
	#############
	public function XML() {
		return $this->xml->saveXML();
	}

	public function confirmEntity($user_id) {
		$entityConfirmHandler = $this->metaDb->prepare('INSERT INTO EntityConfirmation (`entity_id`, `user_id`, `lastConfirmed`) VALUES (:Id, :User_id, NOW()) ON DUPLICATE KEY UPDATE  `user_id` = :User_id, `lastConfirmed` = NOW()');
		$entityConfirmHandler->bindParam(':Id', $this->dbIdNr);
		$entityConfirmHandler->bindParam(':User_id', $user_id);
		$entityConfirmHandler->execute();
	}

	public function getUser($userID, $email = '', $fullName = '', $add = false) {
		if ($this->user['id'] == 0) {
			$userHandler = $this->metaDb->prepare('SELECT `id`, `email`, `fullName` FROM Users WHERE `userID` = :Id');
			$userHandler->bindValue(':Id', strtolower($userID));
			$userHandler->execute();
			if ($this->user = $this->user = $userHandler->fetch(PDO::FETCH_ASSOC)) {
				if ($add && ($email <> $this->user['email'] || $fullName <>  $this->user['fullName'])) {
					$userHandler = $this->metaDb->prepare('UPDATE Users SET `email` = :Email, `fullName` = :FullName WHERE `userID` = :Id');
					$userHandler->bindValue(':Id', strtolower($userID));
					$userHandler->bindParam(':Email', $email);
					$userHandler->bindParam(':FullName', $fullName);
					$userHandler->execute();
				}
			} elseif ($add) {
				$addNewUserHandler = $this->metaDb->prepare('INSERT INTO Users (`userID`, `email`, `fullName`) VALUES(:Id, :Email, :FullName)');
				$addNewUserHandler->bindValue(':Id', strtolower($userID));
				$addNewUserHandler->bindParam(':Email', $email);
				$addNewUserHandler->bindParam(':FullName', $fullName);
				$this->user['id'] = $this->metaDb->lastInsertId();
				$this->user['email'] = $email;
				$this->user['fullname'] = $fullName;
			} else {
				$this->user['id'] = 0;
				$this->user['email'] = '';
				$this->user['fullname'] = '';
			}
		}
		return $this->user;
	}

	public function getUserId($userID, $email = '', $fullName = '', $add = false) {
		if ($this->user['id'] == 0) {
			$this->getUser($userID, $email, $fullName, $add);
		}
		return $this->user['id'];
	}

	public function updateUser($userID, $email, $fullName) {
		$userHandler = $this->metaDb->prepare('UPDATE Users SET `email` = :Email, `fullName` = :FullName WHERE `userID` = :Id');
		$userHandler->bindValue(':Id', strtolower($userID));
		$userHandler->bindValue(':Email', $email);
		$userHandler->bindValue(':FullName', $fullName);
		$userHandler->execute();
	}

	#############
	# Check if userID is responsible for this entityID
	#############
	public function isResponsible() {
		if ($this->user['id'] > 0) {
			$userHandler = $this->metaDb->prepare('SELECT * FROM EntityUser WHERE `user_id` = :UsersID AND `entity_id`= :EntityID' );
			$userHandler->bindParam(':UsersID', $this->user['id']);
			$userHandler->bindParam(':EntityID', $this->dbIdNr);
			$userHandler->execute();
			if ($userHandler->fetch(PDO::FETCH_ASSOC)) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	public function createAccessRequest($user_id) {
		$hash = hash_hmac('md5',$this->entityID(),time());
		$code = base64_encode(sprintf ('%d:%d:%s', $this->dbIdNr, $user_id, $hash));
		$addNewRequestHandler = $this->metaDb->prepare('INSERT INTO `AccessRequests` (`entity_id`, `user_id`, `hash`, `requestDate`) VALUES (:Entity_id, :User_id, :Hashvalue, NOW()) ON DUPLICATE KEY UPDATE `hash` = :Hashvalue, `requestDate` = NOW()');
		$addNewRequestHandler->bindParam(':Entity_id', $this->dbIdNr);
		$addNewRequestHandler->bindParam(':User_id', $user_id);
		$addNewRequestHandler->bindParam(':Hashvalue', $hash);
		$addNewRequestHandler->execute();
		return $code;
	}

	public function validateCode($user_id, $hash) {
		if ($user_id > 0) {
			$userHandler = $this->metaDb->prepare('SELECT * FROM EntityUser WHERE `user_id` = :UsersID AND `entity_id`= :EntityID' );
			$userHandler->bindParam(':UsersID', $user_id);
			$userHandler->bindParam(':EntityID', $this->dbIdNr);
			$userHandler->execute();
			if ($userHandler->fetch(PDO::FETCH_ASSOC)) {
				return array('returnCode' => 1, 'info' => 'User already had access');
			} else {
				$requestHandler = $this->metaDb->prepare('SELECT `requestDate`, NOW() - INTERVAL 1 DAY AS `limit`, `email`, `fullName`, `entityID` FROM `AccessRequests`, `Users`, `Entities`  WHERE Users.`id` = `user_id` AND `Entities`.`id` = `entity_id` AND `entity_id` =  :Entity_id AND `user_id` = :User_id AND `hash` = :Hashvalue');
				$requestRemoveHandler = $this->metaDb->prepare('DELETE FROM `AccessRequests` WHERE `entity_id` =  :Entity_id AND `user_id` = :User_id');
				$entityUserHandler = $this->metaDb->prepare('INSERT INTO EntityUser (`entity_id`, `user_id`, `lastChanged`) VALUES(:Entity_Id, :User_Id, NOW()) ON DUPLICATE KEY UPDATE `lastChanged` = NOW()');
				$entityUserHandler->bindParam(':Entity_Id', $this->dbIdNr);
				$entityUserHandler->bindParam(':User_Id', $user_id);
				$requestHandler->bindParam(':Entity_id', $this->dbIdNr);
				$requestHandler->bindParam(':User_id', $user_id);
				$requestHandler->bindParam(':Hashvalue', $hash);
				$requestRemoveHandler->bindParam(':Entity_id', $this->dbIdNr);
				$requestRemoveHandler->bindParam(':User_id', $user_id);

				$requestHandler->execute();
				if ($request = $requestHandler->fetch(PDO::FETCH_ASSOC)) {
					$requestRemoveHandler->execute();
					if ($request['limit'] < $request['requestDate']) {
						$entityUserHandler->execute();
						return array('returnCode' => 2, 'info' => 'Access granted.', 'fullName' => $request['fullName'], 'email' => $request['email']);
					} else {
						return array('returnCode' => 11, 'info' => 'Code was expired. Please ask user to request new.');
					}
				} else {
					return array('returnCode' => 12, 'info' => 'Invalid code');
				}
			}
		} else {
			return array('returnCode' => 13, 'info' => 'Error in code');
		}
	}

	public function saveStatus($date = '') {
		if ($date == '') {
			$date = gmdate('Y-m-d');
		}
		$ErrorsTotal = 0;
		$ErrorsSPs = 0;
		$ErrorsIdPs = 0;
		$NrOfEntites = 0;
		$NrOfSPs = 0;
		$NrOfIdPs = 0;
		$Changed = 0;
		$entitys = $this->metaDb->prepare("SELECT `id`, `entityID`, `isIdP`, `isSP`, `publishIn`, `lastUpdated`, `errors` FROM Entities WHERE status = 1 AND publishIn > 2");
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
					if ($row['lastUpdated'] > '2021-12-31') $Changed ++;
					break;
				default :
					printf ("Can't resolve publishIn = %d for enityID = %s", $row['publishIn'], $row['entityID']);
			}
		}
		$statsUpdate = $this->metaDb->prepare("INSERT INTO EntitiesStatus (`date`, `ErrorsTotal`, `ErrorsSPs`, `ErrorsIdPs`, `NrOfEntites`, `NrOfSPs`, `NrOfIdPs`, `Changed`) VALUES ('$date', $ErrorsTotal, $ErrorsSPs, $ErrorsIdPs, $NrOfEntites, $NrOfSPs, $NrOfIdPs, '$Changed')");
		$statsUpdate->execute();
	}
}
# vim:set ts=2