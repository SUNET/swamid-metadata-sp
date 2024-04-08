<?php
class Metadata {
  # Setup
  private $result = '';
  private $warning = '';
  private $error = '';
  private $errorNB = '';

  private $isIdP = false;
  private $isSP = false;
  private $isAA = false;
  private $feedValue = 0;
  private $registrationInstant = '';

  private $entityID = 'Unknown';
  private $entityExists = false;
  private $entityDisplayName = false;
  private $dbIdNr = 0;
  private $status = 0;
  private $xml;

  private $user = array ('id' => 0, 'email' => '', 'fullname' => '');

  const BIND_APPROVEDBY = ':ApprovedBy';
  const BIND_BITS = ':Bits';
  const BIND_COCOV1STATUS = ':Cocov1Status';
  const BIND_COMPANY = ':Company';
  const BIND_CONTACTTYPE = ':ContactType';
  const BIND_DATA = ':Data';
  const BIND_DATE = ':Date';
  const BIND_DEFAULT = ':Default';
  const BIND_ELEMENT = ':Element';
  const BIND_EMAIL = ':Email';
  const BIND_EMAILADDRESS = ':EmailAddress';
  const BIND_ENTITYID = ':EntityID';
  const BIND_ENTITY_ID = ':Entity_id';
  const BIND_ERRORS = ':Errors';
  const BIND_ERRORSNB = ':ErrorsNB';
  const BIND_EXTENSIONS = ':Extensions';
  const BIND_FRIENDLYNAME = ':FriendlyName';
  const BIND_FULLNAME = ':FullName';
  const BIND_GIVENNAME = ':GivenName';
  const BIND_HASHVALUE = ':Hashvalue';
  const BIND_HEIGHT = ':Height';
  const BIND_ID = ':Id';
  const BIND_INDEX = ':Index';
  const BIND_ISREQUIRED = ':IsRequired';
  const BIND_ISSUER = ':Issuer';
  const BIND_KEY_TYPE = ':Key_type';
  const BIND_LANG = ':Lang';
  const BIND_LASTCHANGED = ':LastChanged';
  const BIND_LASTCONFIRMED = ':LastConfirmed';
  const BIND_NAME = ':Name';
  const BIND_NAMEFORMAT = ':NameFormat';
  const BIND_NOSIZE = ':NoSize';
  const BIND_NOTVALIDAFTER = ':NotValidAfter';
  const BIND_ORDER = ':Order';
  const BIND_OTHERENTITY_ID = ':OtherEntity_Id';
  const BIND_PUBLISHIN = ':PublishIn';
  const BIND_PUBLISHEDID = ':PublishedId';
  const BIND_REGEXP = ':Regexp';
  const BIND_REGISTRATIONINSTANT = ':RegistrationInstant';
  const BIND_RESULT = ':Result';
  const BIND_SCOPE = ':Scope';
  const BIND_SERIALNUMBER = ':SerialNumber';
  const BIND_STATUS = ':Status';
  const BIND_SUBCONTACTTYPE = ':SubcontactType';
  const BIND_SUBJECT = ':Subject';
  const BIND_SURNAME = ':SurName';
  const BIND_TELEPHONENUMBER = ':TelephoneNumber';
  const BIND_TYPE = ':Type';
  const BIND_URL = ':URL';
  const BIND_USE = ':Use';
  const BIND_USER_ID = ':User_id';
  const BIND_VALIDATIONOUTPUT = ':validationOutput';
  const BIND_VALUE = ':Value';
  const BIND_WARNINGS = ':Warnings';
  const BIND_WIDTH = ':Width';
  const BIND_XML = ':Xml';

  const SAML_IDPDISC_DISCOVERYRESPONSE = 'idpdisc:DiscoveryResponse';
  const SAML_ALG_DIGESTMETHOD = 'alg:DigestMethod';
  const SAML_ALG_SIGNATUREMETHOD = 'alg:SignatureMethod';
  const SAML_ALG_SIGNINGMETHOD = 'alg:SigningMethod';
  const SAML_EC_COCOV1 = 'http://www.geant.net/uri/dataprotection-code-of-conduct/v1'; # NOSONAR Should be http://
  const SAML_MD_ADDITIONALMETADATALOCATION = 'md:AdditionalMetadataLocation';
  const SAML_MD_AFFILIATIONDESCRIPTOR = 'md:AffiliationDescriptor';
  const SAML_MD_ARTIFACTRESOLUTIONSERVICE = 'md:ArtifactResolutionService';
  const SAML_MD_ASSERTIONCONSUMERSERVICE = 'md:AssertionConsumerService';
  const SAML_MD_ASSERTIONIDREQUESTSERVICE = 'md:AssertionIDRequestService';
  const SAML_MD_ATTRIBUTE = 'md:Attribute';
  const SAML_MD_ATTRIBUTEAUTHORITYDESCRIPTOR = 'md:AttributeAuthorityDescriptor';
  const SAML_MD_ATTRIBUTECONSUMINGSERVICE = 'md:AttributeConsumingService';
  const SAML_MD_ATTRIBUTEPROFILE = 'md:AttributeProfile';
  const SAML_MD_ATTRIBUTESERVICE = 'md:AttributeService';
  const SAML_MD_AUTHNAUTHORITYDESCRIPTOR = 'md:AuthnAuthorityDescriptor';
  const SAML_MD_COMPANY = 'md:Company';
  const SAML_MD_CONTACTPERSON = 'md:ContactPerson';
  const SAML_MD_EMAILADDRESS = 'md:EmailAddress';
  const SAML_MD_ENCRYPTIONMETHOD = 'md:EncryptionMethod';
  const SAML_MD_ENTITYDESCRIPTOR = 'md:EntityDescriptor';
  const SAML_MD_EXTENSIONS = 'md:Extensions';
  const SAML_MD_GIVENNAME = 'md:GivenName';
  const SAML_MD_IDPSSODESCRIPTOR = 'md:IDPSSODescriptor';
  const SAML_MD_KEYDESCRIPTOR = 'md:KeyDescriptor';
  const SAML_MD_MANAGENAMEIDSERVICE = 'md:ManageNameIDService';
  const SAML_MD_NAMEIDFORMAT = 'md:NameIDFormat';
  const SAML_MD_NAMEIDMAPPINGSERVICE = 'md:NameIDMappingService';
  const SAML_MD_ORGANIZATION = 'md:Organization';
  const SAML_MD_ORGANIZATIONDISPLAYNAME = 'md:OrganizationDisplayName';
  const SAML_MD_ORGANIZATIONNAME = 'md:OrganizationName';
  const SAML_MD_ORGANIZATIONURL = 'md:OrganizationURL';
  const SAML_MD_PDPDESCRIPTOR = 'md:PDPDescriptor';
  const SAML_MD_REQUESTEDATTRIBUTE = 'md:RequestedAttribute';
  const SAML_MD_SERVICEDESCRIPTION = 'md:ServiceDescription';
  const SAML_MD_SERVICENAME = 'md:ServiceName';
  const SAML_MD_SINGLELOGOUTSERVICE = 'md:SingleLogoutService';
  const SAML_MD_SINGLESIGNONSERVICE = 'md:SingleSignOnService';
  const SAML_MD_SPSSODESCRIPTOR = 'md:SPSSODescriptor';
  const SAML_MD_SURNAME = 'md:SurName';
  const SAML_MD_TELEPHONENUMBER = 'md:TelephoneNumber';
  const SAML_MDATTR_ENTITYATTRIBUTES = 'mdattr:EntityAttributes';
  const SAML_MDRPI_REGISTRATIONINFO = 'mdrpi:RegistrationInfo';
  const SAML_MDUI_DESCRIPTION = 'mdui:Description';
  const SAML_MDUI_DISCOHINTS = 'mdui:DiscoHints';
  const SAML_MDUI_DISPLAYNAME = 'mdui:DisplayName';
  const SAML_MDUI_DOMAINHINT = 'mdui:DomainHint';
  const SAML_MDUI_GEOLOCATIONHINT = 'mdui:GeolocationHint';
  const SAML_MDUI_IPHINT = 'mdui:IPHint';
  const SAML_MDUI_INFORMATIONURL = 'mdui:InformationURL';
  const SAML_MDUI_KEYWORDS = 'mdui:Keywords';
  const SAML_MDUI_LOGO = 'mdui:Logo';
  const SAML_MDUI_PRIVACYSTATEMENTURL = 'mdui:PrivacyStatementURL';
  const SAML_MDUI_UIINFO = 'mdui:UIInfo';
  CONST SAML_PSC_REQUESTEDPRINCIPALSELECTION = 'psc:RequestedPrincipalSelection';
  const SAML_SHIBMD_SCOPE = 'shibmd:Scope';
  const SAML_SAMLA_ATTRIBUTE = 'samla:Attribute';
  const SAML_SAMLA_ATTRIBUTEVALUE = 'samla:AttributeValue';

  const SAMLNF_URI = 'urn:oasis:names:tc:SAML:2.0:attrname-format:uri';

  public function __construct() {
    $a = func_get_args();
    $i = func_num_args();
    require __DIR__  . '/../config.php'; #NOSONAR
    require __DIR__ . '/common.php'; #NOSONAR
    try {
      $this->metaDb = new PDO("mysql:host=$dbServername;dbname=$dbName", $dbUsername, $dbPassword);
      // set the PDO error mode to exception
      $this->metaDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch(PDOException $e) {
      echo "Error: " . $e->getMessage();
    }
    if (method_exists($this,$f='construct'.$i)) {
        call_user_func_array(array($this,$f),$a);
    }
  }

  private function construct1($id) { #NOSONAR is called from construct above
    $entityHandler = $this->metaDb->prepare('
      SELECT `id`, `entityID`, `isIdP`, `isSP`, `isAA`, `publishIn`, `status`, `xml`
        FROM Entities WHERE `id` = :Id');
    $entityHandler->bindValue(self::BIND_ID, $id);
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
      $this->isAA = $entity['isAA'];
      $this->feedValue = $entity['publishIn'];
    }
  }

  private function construct2($entityID = '', $entityStatus = '') { #NOSONAR is called from construct above
    $this->entityID = $entityID;

    switch (strtolower($entityStatus)) {
      case 'prod' :
        # In production metadata
        $this->status = 1;
        break;
      case 'shadow' :
        # Request sent to OPS to be added.
        # Create a shadow entity
        $this->status = 6;
        break;
      case 'new' :
      default :
        # New entity/updated entity
        $this->status = 3;
    }

    $entityHandler = $this->metaDb->prepare('
      SELECT `id`, `isIdP`, `isSP`, `isAA`, `publishIn`, `xml`
        FROM Entities WHERE `entityID` = :Id AND `status` = :Status');
    $entityHandler->bindValue(self::BIND_ID, $entityID);
    $entityHandler->bindValue(self::BIND_STATUS, $this->status);
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
      $this->isAA = $entity['isAA'];
      $this->feedValue = $entity['publishIn'];
    }
  }

  private function addURL($url, $type) {
    //type
    // 1 Check reachable (OK If reachable)
    // 2 Check reachable (NEED to be reachable)
    // 3 Check CoCo privacy
    $urlHandler = $this->metaDb->prepare('SELECT `type` FROM URLs WHERE `URL` = :URL');
    $urlHandler->bindValue(self::BIND_URL, $url);
    $urlHandler->execute();

    if ($currentType = $urlHandler->fetch(PDO::FETCH_ASSOC)) {
      if ($currentType['type'] < $type) {
        // Update type and lastSeen + force revalidate
        $urlUpdateHandler = $this->metaDb->prepare("
          UPDATE URLs SET `type` = :Type, `lastValidated` = '1972-01-01', `lastSeen` = NOW() WHERE `URL` = :URL;");
        $urlUpdateHandler->bindParam(self::BIND_URL, $url);
        $urlUpdateHandler->bindParam(self::BIND_TYPE, $type);
        $urlUpdateHandler->execute();
      } else {
        // Update lastSeen
        $urlUpdateHandler = $this->metaDb->prepare("UPDATE URLs SET `lastSeen` = NOW() WHERE `URL` = :URL;");
        $urlUpdateHandler->bindParam(self::BIND_URL, $url);
        $urlUpdateHandler->execute();
      }
    } else {
      $urlAddHandler = $this->metaDb->prepare("INSERT INTO URLs
        (`URL`, `type`, `status`, `lastValidated`, `lastSeen`)
        VALUES (:URL, :Type, 10, '1972-01-01', NOW());");
      $urlAddHandler->bindParam(self::BIND_URL, $url);
      $urlAddHandler->bindParam(self::BIND_TYPE, $type);
      $urlAddHandler->execute();
    }
  }

  public function validateURLs($limit=10, $verbose = false){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'https://metadata.swamid.se/validate');

    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

    curl_setopt($ch, CURLINFO_HEADER_OUT, 0);

    $urlUpdateHandler = $this->metaDb->prepare("UPDATE URLs
      SET `lastValidated` = NOW(), `status` = :Status, `cocov1Status` = :Cocov1Status,
        `height` = :Height, `width` = :Width, `nosize` = :NoSize, `validationOutput` = :Result
      WHERE `URL` = :URL;");
    if ($limit > 10) {
      $sql = "SELECT `URL`, `type` FROM URLs
        WHERE `lastValidated` < ADDTIME(NOW(), '-7 0:0:0')
          OR ((`status` > 0 OR `cocov1Status` > 0) AND `lastValidated` < ADDTIME(NOW(), '-6:0:0'))
        ORDER BY `lastValidated` LIMIT $limit;";
    } else {
      $sql = "SELECT `URL`, `type`
        FROM URLs
        WHERE `lastValidated` < ADDTIME(NOW(), '-20 0:0:0')
          OR ((`status` > 0 OR `cocov1Status` > 0) AND `lastValidated` < ADDTIME(NOW(), '-8:0:0'))
        ORDER BY `lastValidated` LIMIT $limit;";
    }
    $urlHandler = $this->metaDb->prepare($sql);
    $urlHandler->execute();
    $count = 0;
    if ($verbose) {
      printf ('    <table class="table table-striped table-bordered">%s', "\n");
    }
    while ($url = $urlHandler->fetch(PDO::FETCH_ASSOC)) {
      $urlUpdateHandler->bindValue(self::BIND_URL, $url['URL']);

      curl_setopt($ch, CURLOPT_URL, $url['URL']);
      $height = 0;
      $width = 0;
      $nosize = 0;
      $verboseInfo = sprintf('<tr><td>%s</td><td>', $url['URL']);
      $output = curl_exec($ch);
      if (curl_errno($ch)) {
        $verboseInfo .= 'Curl error';
        $urlUpdateHandler->bindValue(self::BIND_RESULT, curl_error($ch));
        $urlUpdateHandler->bindValue(self::BIND_STATUS, 3);
        $urlUpdateHandler->bindValue(self::BIND_COCOV1STATUS, 1);
      } else {
        switch ($http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
          case 200 :
            $verboseInfo .= 'OK : content-type = ' . curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            if (substr(curl_getinfo($ch, CURLINFO_CONTENT_TYPE),0,6) == 'image/') {
              if (substr(curl_getinfo($ch, CURLINFO_CONTENT_TYPE),0,13) == 'image/svg+xml') {
                $nosize = 1;
              } else {
                $size = getimagesizefromstring($output);
                $width = $size[0];
                $height = $size[1];
              }
            }
            switch ($url['type']) {
              case 1 :
              case 2 :
                $urlUpdateHandler->bindValue(self::BIND_RESULT, 'Reachable');
                $urlUpdateHandler->bindValue(self::BIND_STATUS, 0);
                $urlUpdateHandler->bindValue(self::BIND_COCOV1STATUS, 0);
                break;
              case 3 :
                if (strpos ( $output, self::SAML_EC_COCOV1) > 1 ) {
                  $urlUpdateHandler->bindValue(self::BIND_RESULT, 'Policy OK');
                  $urlUpdateHandler->bindValue(self::BIND_STATUS, 0);
                  $urlUpdateHandler->bindValue(self::BIND_COCOV1STATUS, 0);
                } else {
                  $urlUpdateHandler->bindValue(self::BIND_RESULT,
                    'Policy missing link to ' . self::SAML_EC_COCOV1);
                  $urlUpdateHandler->bindValue(self::BIND_STATUS, 0);
                  $urlUpdateHandler->bindValue(self::BIND_COCOV1STATUS, 1);
                }
                break;
              default :
                break;
            }
            break;
          case 403 :
            $verboseInfo .= '403';
            $urlUpdateHandler->bindValue(self::BIND_RESULT, "Access denied. Can't check URL.");
            $urlUpdateHandler->bindValue(self::BIND_STATUS, 2);
            $urlUpdateHandler->bindValue(self::BIND_COCOV1STATUS, 1);
            break;
          case 404 :
            $verboseInfo .= '404';
            $urlUpdateHandler->bindValue(self::BIND_RESULT, 'Page not found.');
            $urlUpdateHandler->bindValue(self::BIND_STATUS, 2);
            $urlUpdateHandler->bindValue(self::BIND_COCOV1STATUS, 1);
            break;
          case 503 :
            $verboseInfo .= '503';
            $urlUpdateHandler->bindValue(self::BIND_RESULT, "Service Unavailable. Can't check URL.");
            $urlUpdateHandler->bindValue(self::BIND_STATUS, 2);
            $urlUpdateHandler->bindValue(self::BIND_COCOV1STATUS, 1);
            break;
          default :
            $verboseInfo .= $http_code;
            $urlUpdateHandler->bindValue(self::BIND_RESULT,
              "Contact operation@swamid.se. Got code $http_code from web-server. Cant handle :-(");
            $urlUpdateHandler->bindValue(self::BIND_STATUS, 2);
            $urlUpdateHandler->bindValue(self::BIND_COCOV1STATUS, 1);
        }
      }
      $urlUpdateHandler->bindValue(self::BIND_HEIGHT, $height);
      $urlUpdateHandler->bindValue(self::BIND_WIDTH, $width);
      $urlUpdateHandler->bindValue(self::BIND_NOSIZE, $nosize);
      $urlUpdateHandler->execute();
      $count ++;
      if ($verbose) {
        printf ('      %s</td></tr>%s', $verboseInfo, "\n");
      }
    }
    if ($verbose) {
      printf ('    </table>%s', "\n");
    }
    curl_close($ch);
    if ($limit > 10) {
      printf ("Checked %d URL:s\n", $count);
    }
  }

  public function revalidateURL($url, $verbose = false) {
    $urlUpdateHandler = $this->metaDb->prepare("UPDATE URLs SET `lastValidated` = '1972-01-01' WHERE `URL` = :URL;");
    $urlUpdateHandler->bindParam(self::BIND_URL, $url);
    $urlUpdateHandler->execute();
    $this->validateURLs(5, $verbose);
  }

  public function checkOldURLS($age = 30, $verbose = false) {
    $sql = sprintf("SELECT URL, lastSeen from URLs where lastSeen < ADDTIME(NOW(), '-%d 0:0:0')", $age);
    $urlHandler = $this->metaDb->prepare($sql);
    $urlHandler->execute();
    while ($urlInfo = $urlHandler->fetch(PDO::FETCH_ASSOC)) {
      if ($verbose) { printf ("Checking : %s last seen %s\n", $urlInfo['URL'], $urlInfo['lastSeen']); }
      $this->checkURLStatus($urlInfo['URL'], $verbose);
    }
  }

  private function checkURLStatus($url, $verbose = false){
    $urlHandler = $this->metaDb->prepare('SELECT `type`, `validationOutput`, `lastValidated`
      FROM URLs WHERE `URL` = :URL');
    $urlHandler->bindValue(self::BIND_URL, $url);
    $urlHandler->execute();
    if ($urlInfo = $urlHandler->fetch(PDO::FETCH_ASSOC)) {
      $missing = true;
      $coCoV1 = false;
      $logo = false;
      $entityHandler = $this->metaDb->prepare('SELECT `entity_id`, `entityID`, `status`
        FROM EntityURLs, Entities WHERE entity_id = id AND `URL` = :URL AND `status`< 4');
      $entityHandler->bindValue(self::BIND_URL, $url);
      $entityHandler->execute();
      $ssoUIIHandler = $this->metaDb->prepare('SELECT `entity_id`, `type`, `element`, `lang`, `entityID`, `status`
        FROM `Mdui`, `Entities` WHERE `Mdui`.`entity_id` = `Entities`.`id` AND `data` = :URL AND `status`< 4');
      $ssoUIIHandler->bindValue(self::BIND_URL, $url);
      $ssoUIIHandler->execute();
      $organizationHandler = $this->metaDb->prepare('SELECT `entity_id`, `element`, `lang`, `entityID`, `status`
        FROM Organization, Entities WHERE entity_id = id AND `data` = :URL AND `status`< 4');
      $organizationHandler->bindValue(self::BIND_URL, $url);
      $organizationHandler->execute();
      $entityAttributesHandler = $this->metaDb->prepare("SELECT `attribute`
        FROM EntityAttributes WHERE `entity_id` = :Id AND type = 'entity-category'");
      if ($entityHandler->fetch(PDO::FETCH_ASSOC)) {
        $missing = false;
      }
      while ($entity = $ssoUIIHandler->fetch(PDO::FETCH_ASSOC)) {
        if ($entity['type'] == 'SPSSO' && $entity['element'] == 'PrivacyStatementURL') {
          $entityAttributesHandler->bindParam(self::BIND_ID, $entity['entity_id']);
          $entityAttributesHandler->execute();
          while ($attribute = $entityAttributesHandler->fetch(PDO::FETCH_ASSOC)) {
            if ($attribute['attribute'] == 'http://www.geant.net/uri/dataprotection-code-of-conduct/v1') { # NOSONAR Should be http://
              $coCoV1 = true;
            }
          }
        }
        switch ($entity['element']) {
          case 'Logo' :
            $logo = true;
            $missing = false;
            break;
          case 'InformationURL' :
          case 'PrivacyStatementURL' :
            $missing = false;
            break;
          default :
            break;
        }
      }
      while ($entity = $organizationHandler->fetch(PDO::FETCH_ASSOC)) {
        if ($entity['element'] == 'OrganizationURL') {
          $missing = false;
        }
      }
      if ($missing) {
        $urlHandler = $this->metaDb->prepare('DELETE FROM URLs WHERE `URL` = :URL');
        $urlHandler->bindValue(self::BIND_URL, $url);
        $urlHandler->execute();
        if ($verbose) { print "Removing URL. Not in use any more\n"; }
      } elseif ($urlInfo['type'] > 2 && !$coCoV1 ) {
        if ($logo) {
          $urlHandler = $this->metaDb->prepare('UPDATE URLs SET `type` = 2 WHERE `URL` = :URL');
        } else {
          $urlHandler = $this->metaDb->prepare('UPDATE URLs SET `type` = 1 WHERE `URL` = :URL');
        }
        $urlHandler->bindValue(self::BIND_URL, $url);
        $urlHandler->execute();
        if ($verbose) { print "Not CoCo v1 any more. Removes that flag.\n"; }
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
    $this->cleanOutAttribuesInIDPSSODescriptor();
    if ($this->entityExists && $this->status == 1) {
      # Update entity in database
      $entityHandlerUpdate = $this->metaDb->prepare('UPDATE Entities
        SET `isIdP` = 0, `isSP` = 0, `isAA` = 0, `xml` = :Xml , `lastUpdated` = NOW()
        WHERE `entityID` = :Id AND `status` = :Status');
      $entityHandlerUpdate->bindValue(self::BIND_ID, $this->entityID);
      $entityHandlerUpdate->bindValue(self::BIND_STATUS, $this->status);
      $entityHandlerUpdate->bindValue(self::BIND_XML, $this->xml->saveXML());
      $entityHandlerUpdate->execute();
    } else {
      # Add new entity into database
      $entityHandlerInsert = $this->metaDb->prepare('INSERT INTO Entities
        (`entityID`, `isIdP`, `isSP`, `publishIn`, `status`, `xml`, `lastUpdated`)
        VALUES(:Id, 0, 0, 0, :Status, :Xml, NOW())');
      $entityHandlerInsert->bindValue(self::BIND_ID, $this->entityID);
      $entityHandlerInsert->bindValue(self::BIND_STATUS, $this->status);
      $entityHandlerInsert->bindValue(self::BIND_XML, $this->xml->saveXML());
      $entityHandlerInsert->execute();
      $this->dbIdNr = $this->metaDb->lastInsertId();
    }
    $this->isIdP = false;
    $this->isSP = false;
    $this->isAA = false;
    $this->entityExists = true;
  }

  # Creates / updates XML from Published into Draft
  public function createDraft() {
    if ($this->entityExists && ($this->status == 1 || $this->status == 4)) {
      # Add new entity into database
      $entityHandlerInsert = $this->metaDb->prepare('
        INSERT INTO Entities (`entityID`, `isIdP`, `isSP`, `isAA`, `publishIn`, `status`, `xml`, `lastUpdated`)
          VALUES(:Id, 0, 0, 0, 0, 3, :Xml, NOW())');
      $entityHandlerInsert->bindValue(self::BIND_ID, $this->entityID);
      $entityHandlerInsert->bindValue(self::BIND_XML, $this->xml->saveXML());
      $entityHandlerInsert->execute();
      $oldDbNr = $this->dbIdNr;
      $this->result = "";
      $this->dbIdNr = $this->metaDb->lastInsertId();
      $this->status = 3;
      $this->copyResponsible($oldDbNr);
      return $this->dbIdNr;
    } else {
      return false;
    }
  }

  # Validate xml-code
  public function validateXML() {
    if (! $this->entityExists) {
      $this->result = "$this->entityID doesn't exist!!";
      return 1;
    }

    # Remove old ContactPersons / Organization from previus runs
    $this->metaDb->prepare('DELETE FROM EntityAttributes WHERE `entity_id` = :Id')->execute(
      array(self::BIND_ID => $this->dbIdNr));
    $this->metaDb->prepare('DELETE FROM Mdui WHERE `entity_id` = :Id')->execute(
      array(self::BIND_ID => $this->dbIdNr));
    $this->metaDb->prepare('DELETE FROM KeyInfo WHERE `entity_id` = :Id')->execute(
      array(self::BIND_ID => $this->dbIdNr));
    $this->metaDb->prepare('DELETE FROM AttributeConsumingService WHERE `entity_id` = :Id')->execute(
      array(self::BIND_ID => $this->dbIdNr));
    $this->metaDb->prepare('DELETE FROM AttributeConsumingService_Service WHERE `entity_id` = :Id')->execute(
      array(self::BIND_ID => $this->dbIdNr));
    $this->metaDb->prepare('DELETE FROM AttributeConsumingService_RequestedAttribute
      WHERE `entity_id` = :Id')->execute(array(self::BIND_ID => $this->dbIdNr));
    $this->metaDb->prepare('DELETE FROM Organization WHERE `entity_id` = :Id')->execute(
      array(self::BIND_ID => $this->dbIdNr));
    $this->metaDb->prepare('DELETE FROM ContactPerson WHERE `entity_id` = :Id')->execute(
      array(self::BIND_ID => $this->dbIdNr));
    $this->metaDb->prepare('DELETE FROM EntityURLs WHERE `entity_id` = :Id')->execute(
      array(self::BIND_ID => $this->dbIdNr));
    $this->metaDb->prepare('DELETE FROM Scopes WHERE `entity_id` = :Id')->execute(
      array(self::BIND_ID => $this->dbIdNr));
    $this->metaDb->prepare('UPDATE Entities SET `isIdP` = 0, `isSP` = 0, `isAA` = 0 WHERE `id` = :Id')->execute(
      array(self::BIND_ID => $this->dbIdNr));
    $this->isIdP = false;
    $this->isSP = false;
    $this->isAA = false;

    # https://docs.oasis-open.org/security/saml/v2.0/saml-metadata-2.0-os.pdf 2.4.1
    $entityDescriptor = $this->getEntityDescriptor($this->xml);
    $child = $entityDescriptor->firstChild;
    while ($child) {
      switch ($child->nodeName) {
        case self::SAML_MD_EXTENSIONS :
          $this->parseExtensions($child);
          break;
        case self::SAML_MD_IDPSSODESCRIPTOR :
          $this->metaDb->prepare('UPDATE Entities SET `isIdP` = 1 WHERE `id` = :Id')->execute(
            array(self::BIND_ID => $this->dbIdNr));
          $this->parseIDPSSODescriptor($child);
          $this->isIdP = true;
          break;
        case self::SAML_MD_SPSSODESCRIPTOR :
          $this->metaDb->prepare('UPDATE Entities SET `isSP` = 1 WHERE `id` = :Id')->execute(
            array(self::BIND_ID => $this->dbIdNr));
          $this->parseSPSSODescriptor($child);
          $this->isSP = true;
          break;
        #case self::SAML_MD_AUTHNAUTHORITYDESCRIPTOR :
        case self::SAML_MD_ATTRIBUTEAUTHORITYDESCRIPTOR :
          $this->metaDb->prepare('UPDATE Entities SET `isAA` = 1 WHERE `id` = :Id')->execute(
            array(self::BIND_ID => $this->dbIdNr));
          $this->parseAttributeAuthorityDescriptor($child);
          $this->isAA = true;
          break;
        #case self::SAML_MD_PDPDESCRIPTOR :
        #case self::SAML_MD_AFFILIATIONDESCRIPTOR :
        case self::SAML_MD_ORGANIZATION :
          $this->parseOrganization($child);
          break;
        case self::SAML_MD_CONTACTPERSON :
          $this->parseContactPerson($child);
          break;
        #case self::SAML_MD_ADDITIONALMETADATALOCATION :
        default :
          $this->result .= $child->nodeType == 8 ? '' : sprintf("%s missing in validator.\n", $child->nodeName);
      }
      $child = $child->nextSibling;
    }

    $resultHandler = $this->metaDb->prepare("UPDATE Entities
      SET `registrationInstant` = :RegistrationInstant, `validationOutput` = :validationOutput,
        `warnings` = :Warnings, `errors` = :Errors, `errorsNB` = :ErrorsNB, `xml` = :Xml, `lastValidated` = NOW()
      WHERE `id` = :Id;");
    $resultHandler->bindValue(self::BIND_ID, $this->dbIdNr);
    $resultHandler->bindValue(self::BIND_REGISTRATIONINSTANT, $this->registrationInstant);
    $resultHandler->bindValue(self::BIND_VALIDATIONOUTPUT, $this->result);
    $resultHandler->bindValue(self::BIND_WARNINGS, $this->warning);
    $resultHandler->bindValue(self::BIND_ERRORS, $this->error);
    $resultHandler->bindValue(self::BIND_ERRORSNB, $this->errorNB);
    $resultHandler->bindValue(self::BIND_XML, $this->xml->saveXML());
    $resultHandler->execute();
  }

  # Extensions
  private function parseExtensions($data) {
    $child = $data->firstChild;
    while ($child) {
      switch ($child->nodeName) {
        case self::SAML_MDATTR_ENTITYATTRIBUTES :
          $this->parseExtensionsEntityAttributes($child);
          break;
        case self::SAML_MDRPI_REGISTRATIONINFO :
          $this->registrationInstant = $child->getAttribute('registrationInstant');
          break;
        case self::SAML_ALG_DIGESTMETHOD :
        case self::SAML_ALG_SIGNINGMETHOD :
        case self::SAML_ALG_SIGNATUREMETHOD :
          break;
        # Errors
        case self::SAML_IDPDISC_DISCOVERYRESPONSE :
          $this->error .= "DiscoveryResponse found in Extensions should be below SPSSODescriptor/Extensions.\n";
          break;
        case self::SAML_SHIBMD_SCOPE :
          $this->error .= "Scope found in Extensions should be below IDPSSODescriptor/Extensions.\n";
          break;
        default :
          $this->result .= sprintf("Extensions->%s missing in validator.\n", $child->nodeName);
      }
      $child = $child->nextSibling;
    }
  }

  # Extensions -> EntityAttributes
  private function parseExtensionsEntityAttributes($data) {
    $child = $data->firstChild;
    while ($child) {
      if ($child->nodeName == self::SAML_SAMLA_ATTRIBUTE ) {
        $this->parseExtensionsEntityAttributesAttribute($child);
      } else {
        $this->result .= $child->nodeType == 8 ? '' :
        sprintf("Extensions->EntityAttributes->%s missing in validator.\n", $child->nodeName);
      }
      $child = $child->nextSibling;
    }
  }

  # Extensions -> EntityAttributes -> Attribute
  private function parseExtensionsEntityAttributesAttribute($data) {
    $entityAttributesHandler = $this->metaDb->prepare('INSERT INTO EntityAttributes (`entity_id`, `type`, `attribute`)
      VALUES (:Id, :Type, :Value)');

    if (! $data->hasAttribute('NameFormat') && $data->hasAttribute('Name')) {
      switch ($data->getAttribute('Name')) {
        case 'http://macedir.org/entity-category' : # NOSONAR Should be http://
        case 'http://macedir.org/entity-category-support' : # NOSONAR Should be http://
        case 'urn:oasis:names:tc:SAML:attribute:assurance-certification' :
        case 'urn:oasis:names:tc:SAML:profiles:subject-id:req' :
        case 'http://www.swamid.se/assurance-requirement' : # NOSONAR Should be http://
          $data->setAttribute('NameFormat', self::SAMLNF_URI);
          $this->result .= sprintf(
            "Added NameFormat %s to Extensions/EntityAttributes/Attribute/%s.\n",
            self::SAMLNF_URI, $data->getAttribute('Name'));
          break;
        default:
        $this->result .= sprintf("Unknown Name (%s) in Extensions/EntityAttributes/Attribute.\n",
          $data->getAttribute('Name'));
        break;
      }
    }
    if ($data->getAttribute('NameFormat') == self::SAMLNF_URI) {
      switch ($data->getAttribute('Name')) {
        case 'http://macedir.org/entity-category' : # NOSONAR Should be http://
          $attributeType = 'entity-category';
          break;
        case 'http://macedir.org/entity-category-support' : # NOSONAR Should be http://
          $attributeType = 'entity-category-support';
          break;
        case 'urn:oasis:names:tc:SAML:attribute:assurance-certification' :
          $attributeType = 'assurance-certification';
          break;
        case 'urn:oasis:names:tc:SAML:profiles:subject-id:req' :
          $attributeType = 'subject-id:req';
          break;
        case 'http://www.swamid.se/assurance-requirement' : # NOSONAR Should be http://
          $attributeType = 'swamid/assurance-requirement';
          break;
        default :
          $this->result .= sprintf("Unknown Name (%s) in Extensions/EntityAttributes/Attribute.\n",
            $data->getAttribute('Name'));
          $attributeType = $data->getAttribute('Name');
      }

      $entityAttributesHandler->bindValue(self::BIND_ID, $this->dbIdNr);
      $entityAttributesHandler->bindValue(self::BIND_TYPE, $attributeType);

      $child = $data->firstChild;
      while ($child) {
        if ($child->nodeName == self::SAML_SAMLA_ATTRIBUTEVALUE) {
          $entityAttributesHandler->bindValue(self::BIND_VALUE, trim($child->textContent));
          $entityAttributesHandler->execute();
        } else {
          $this->result .= 'Extensions -> EntityAttributes -> Attribute -> ' . $child->nodeName . " saknas.\n";
        }
        $child = $child->nextSibling;
      }
    } else {
      $this->result .= sprintf("Unknown NameFormat (%s) in Extensions/EntityAttributes/Attribute.\n",
        $data->getAttribute('NameFormat'));
    }
  }

  #############
  # IDPSSODescriptor
  #############
  private function parseIDPSSODescriptor($data) {
    if ($data->getAttribute('errorURL'))  {
      $this->addEntityUrl('error', $data->getAttribute('errorURL'));
    }

    $swamid5131error = false;
    $keyOrder = 0;
    # https://docs.oasis-open.org/security/saml/v2.0/saml-metadata-2.0-os.pdf 2.4.1 + 2.4.2 + 2.4.3
    $child = $data->firstChild;
    while ($child) {
      switch ($child->nodeName) {
        # 2.4.1
        #case 'Signature' :
        case self::SAML_MD_EXTENSIONS :
          $this->parseIDPSSODescriptorExtensions($child);
          break;
        case self::SAML_MD_KEYDESCRIPTOR :
          $this->parseKeyDescriptor($child, 'IDPSSO', $keyOrder++);
          break;
        # 2.4.2
        case self::SAML_MD_ARTIFACTRESOLUTIONSERVICE :
        case self::SAML_MD_SINGLELOGOUTSERVICE :
        #case 'ManageNameIDService' :
          $this->checkSAMLEndpointURL($child,'IDPSSO');
          break;
        case self::SAML_MD_NAMEIDFORMAT :
          # Skippar då SWAMID inte använder denna del
          break;
        # 2.4.3
        case self::SAML_MD_SINGLESIGNONSERVICE :
        case self::SAML_MD_NAMEIDMAPPINGSERVICE :
        case self::SAML_MD_ASSERTIONIDREQUESTSERVICE :
          $this->checkSAMLEndpointURL($child,'IDPSSO');
          break;
        #case self::SAML_MD_ATTRIBUTEPROFILE :
        case self::SAML_SAMLA_ATTRIBUTE :
          # Should not be in SWAMID XML
          $swamid5131error = true;
          break;
        default :
        $this->result .= $child->nodeType == 8 ? '' :
          sprintf("IDPSSODescriptor->%s missing in validator.\n", $child->nodeName);
      }
      $child = $child->nextSibling;
    }
    if ($swamid5131error) {
      $this->cleanOutAttribuesInIDPSSODescriptor();
    }
    return $data;
  }

  private function parseIDPSSODescriptorExtensions($data) {
    $scopesHandler = $this->metaDb->prepare('INSERT INTO Scopes (`entity_id`, `scope`, `regexp`)
      VALUES (:Id, :Scope, :Regexp)');
    $scopesHandler->bindValue(self::BIND_ID, $this->dbIdNr);

    #xmlns:shibmd="urn:mace:shibboleth:metadata:1.0"
    $child = $data->firstChild;
    while ($child) {
      switch ($child->nodeName) {
        case self::SAML_SHIBMD_SCOPE :
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
              $this->result .= sprintf("IDPSSODescriptor->Extensions->Scope unknown value for regexp %s.\n",
                $child->getAttribute('regexp'));
              $regexp = -1;
          }
          $scopesHandler->bindValue(self::BIND_SCOPE, trim($child->textContent));
          $scopesHandler->bindValue(self::BIND_REGEXP, $regexp);
          $scopesHandler->execute();
          break;
        case self::SAML_MDUI_UIINFO :
          $this->parseSSODescriptorExtensionsUIInfo($child, 'IDPSSO');
          break;
        case self::SAML_MDUI_DISCOHINTS :
          $this->parseIDPSSODescriptorExtensionsDiscoHints($child);
          break;
        case self::SAML_MDATTR_ENTITYATTRIBUTES :
          $this->error .=
            "EntityAttributes found in IDPSSODescriptor/Extensions should be below Extensions at root level.\n";
          break;
        case self::SAML_ALG_DIGESTMETHOD :
        case self::SAML_ALG_SIGNINGMETHOD :
        case self::SAML_PSC_REQUESTEDPRINCIPALSELECTION :
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
  private function parseIDPSSODescriptorExtensionsDiscoHints($data) {
    $ssoUIIHandler = $this->metaDb->prepare("INSERT INTO Mdui
      (`entity_id`, `type`, `lang`, `height`, `width`, `element`, `data`)
      VALUES (:Id, 'IDPDisco', :Lang, :Height, :Width, :Element, :Value)");

    $ssoUIIHandler->bindValue(self::BIND_ID, $this->dbIdNr);
    $ssoUIIHandler->bindParam(self::BIND_LANG, $lang);
    $ssoUIIHandler->bindParam(self::BIND_HEIGHT, $height);
    $ssoUIIHandler->bindParam(self::BIND_WIDTH, $width);
    $ssoUIIHandler->bindParam(self::BIND_ELEMENT, $element);

    # https://docs.oasis-open.org/security/saml/Post2.0/sstc-saml-metadata-ui/v1.0/os/sstc-saml-metadata-ui-v1.0-os.html
    $child = $data->firstChild;
    while ($child) {
      switch ($child->nodeName) {
        case self::SAML_MDUI_IPHINT :
        case self::SAML_MDUI_DOMAINHINT :
        case self::SAML_MDUI_GEOLOCATIONHINT :
          $element = substr($child->nodeName, 5); #NOSONAR $element is used in Bind above
          break;
        default :
          $this->result .= $child->nodeType == 8
            ? ''
            : sprintf ("Unknown Element (%s) in DiscoHints.\n", $child->nodeName);
          $element = 'Unknown'; #NOSONAR $element is used in Bind above
      }

      $ssoUIIHandler->bindValue(self::BIND_VALUE, trim($child->textContent));
      $ssoUIIHandler->execute();
      $child = $child->nextSibling;
    }
  }

  #############
  # SPSSODescriptor
  #############
  private function parseSPSSODescriptor($data) {
    $this->swamid6116error = false;
    $keyOrder = 0;
    # https://docs.oasis-open.org/security/saml/v2.0/saml-metadata-2.0-os.pdf 2.4.1 + 2.4.2 + 2.4.4
    $child = $data->firstChild;
    while ($child) {
      switch ($child->nodeName) {
        # 2.4.1
        case self::SAML_MD_EXTENSIONS :
          $this->parseSPSSODescriptorExtensions($child);
          break;
        case self::SAML_MD_KEYDESCRIPTOR :
          $this->parseKeyDescriptor($child, 'SPSSO', $keyOrder++);
          break;
        # 2.4.2
        case self::SAML_MD_ARTIFACTRESOLUTIONSERVICE :
        case self::SAML_MD_SINGLELOGOUTSERVICE :
        case self::SAML_MD_MANAGENAMEIDSERVICE :
          $this->checkSAMLEndpointURL($child,'SPSSO');
          break;
        case self::SAML_MD_NAMEIDFORMAT :
          # Skippar då SWAMID inte använder denna del
          break;
        # 2.4.4
        case self::SAML_MD_ASSERTIONCONSUMERSERVICE :
          $this->checkSAMLEndpointURL($child,'SPSSO');
          $this->checkAssertionConsumerService($child);
          break;
        case self::SAML_MD_ATTRIBUTECONSUMINGSERVICE :
          $this->parseSPSSODescriptorAttributeConsumingService($child);
          break;
        default :
          $this->result .= $child->nodeType == 8 ? '' :
            sprintf("SPSSODescriptor->%s missing in validator.\n", $child->nodeName);
      }
      $child = $child->nextSibling;
    }
    if ($this->swamid6116error) {
      $this->cleanOutAssertionConsumerServiceHTTPRedirect();
    }
  }

  private function parseSPSSODescriptorExtensions($data) {
    $child = $data->firstChild;
    while ($child) {
      switch ($child->nodeName) {
        case self::SAML_IDPDISC_DISCOVERYRESPONSE :
        case 'init:RequestInitiator' :
          break;
        case self::SAML_MDUI_UIINFO :
          $this->parseSSODescriptorExtensionsUIInfo($child, 'SPSSO');
          break;
        case self::SAML_MDUI_DISCOHINTS :
          $this->warning .= "SPSSODescriptor/Extensions should not have a DiscoHints.\n";
          break;
        case self::SAML_SHIBMD_SCOPE :
          $this->error .= "Scope found in SPSSODescriptor/Extensions should be below IDPSSODescriptor/Extensions.\n";
          break;
        default :
          $this->result .= $child->nodeType == 8 ? '' :
            sprintf("SPSSODescriptor->Extensions->%s missing in validator.\n", $child->nodeName);
      }
      $child = $child->nextSibling;
    }
  }

  private function parseSPSSODescriptorAttributeConsumingService($data) {
    $index = $data->getAttribute('index');
    if ($index == '') {
      $this->error .= "Index is Required in SPSSODescriptor->AttributeConsumingService.\n";
      $index = 0;
    }

    $isDefault = ($data->getAttribute('isDefault') &&
      ($data->getAttribute('isDefault') == 'true' || $data->getAttribute('isDefault') == '1')) ? 1 : 0;

    $serviceHandler = $this->metaDb->prepare('INSERT INTO AttributeConsumingService
      (`entity_id`, `Service_index`, `isDefault`) VALUES (:Id, :Index, :Default)');
    $serviceElementHandler = $this->metaDb->prepare('INSERT INTO AttributeConsumingService_Service
      (`entity_id`, `Service_index`, `lang`, `element`, `data`) VALUES (:Id, :Index, :Lang, :Element, :Data)');
    $requestedAttributeHandler = $this->metaDb->prepare('INSERT INTO AttributeConsumingService_RequestedAttribute
      (`entity_id`, `Service_index`, `FriendlyName`, `Name`, `NameFormat`, `isRequired`)
      VALUES (:Id, :Index, :FriendlyName, :Name, :NameFormat, :IsRequired)');

    $serviceHandler->bindValue(self::BIND_ID, $this->dbIdNr);
    $serviceHandler->bindParam(self::BIND_INDEX, $index);
    $serviceHandler->bindValue(self::BIND_DEFAULT, $isDefault);
    $serviceHandler->execute();
    $serviceElementHandler->bindValue(self::BIND_ID, $this->dbIdNr);
    $serviceElementHandler->bindParam(self::BIND_INDEX, $index);
    $serviceElementHandler->bindParam(self::BIND_LANG, $lang);
    $requestedAttributeHandler->bindValue(self::BIND_ID, $this->dbIdNr);
    $requestedAttributeHandler->bindParam(self::BIND_INDEX, $index);
    $requestedAttributeHandler->bindParam(self::BIND_FRIENDLYNAME, $friendlyName);
    $requestedAttributeHandler->bindParam(self::BIND_NAME, $name);
    $requestedAttributeHandler->bindParam(self::BIND_NAMEFORMAT, $nameFormat);
    $requestedAttributeHandler->bindParam(self::BIND_ISREQUIRED, $isRequired);

    $serviceNameFound = false;
    $requestedAttributeFound = false;

    $child = $data->firstChild;
    while ($child) {
      switch ($child->nodeName) {
        case self::SAML_MD_SERVICENAME :
          $lang = $child->getAttribute('xml:lang') ? $child->getAttribute('xml:lang') : ''; #NOSONAR $lnag is used in Bind above
          $serviceElementHandler->bindValue(self::BIND_ELEMENT, 'ServiceName');
          $serviceElementHandler->bindValue(self::BIND_DATA, trim($child->textContent));
          $serviceElementHandler->execute();
          $serviceNameFound = true;
          break;
        case self::SAML_MD_SERVICEDESCRIPTION :
          $lang = $child->getAttribute('xml:lang') ? $child->getAttribute('xml:lang') : ''; #NOSONAR $lnag is used in Bind above
          $serviceElementHandler->bindValue(self::BIND_ELEMENT, 'ServiceDescription');
          $serviceElementHandler->bindValue(self::BIND_DATA, trim($child->textContent));
          $serviceElementHandler->execute();
          break;
        case self::SAML_MD_REQUESTEDATTRIBUTE :
          $friendlyName = $child->getAttribute('FriendlyName') ? $child->getAttribute('FriendlyName') : '';
          $nameFormat = '';
          $isRequired = ($child->getAttribute('isRequired') # NOSONAR
            && ($child->getAttribute('isRequired') == 'true' || $child->getAttribute('isRequired') == '1')) ? 1 : 0;
          if ($child->getAttribute('Name')) {
            $name = $child->getAttribute('Name');
            if ($friendlyName != '' &&
              isset($this->FriendlyNames[$name]) &&
              $this->FriendlyNames[$name]['desc'] != $friendlyName) {
                $this->warning .= sprintf(
                  "SWAMID Tech 6.1.20: FriendlyName for %s %s %d is %s (recomended from SWAMID is %s).\n",
                  $name, 'in RequestedAttribute for index', $index, $friendlyName, $this->FriendlyNames[$name]['desc']);
            }
            if ($child->getAttribute('NameFormat')) {
              $nameFormat = $child->getAttribute('NameFormat');
              switch ($nameFormat) {
                case 'urn:oasis:names:tc:SAML:2.0:attrname-format:uri' :
                  // This Is OK
                  break;
                case 'urn:mace:shibboleth:1.0:attributeNamespace:uri' :
                  $this->warning .=
                    sprintf("SAML1 NameFormat %s for %s in RequestedAttribute for index %d is not recomended.\n",
                      $nameFormat, $name, $index);
                  break;
                default :
                  $this->warning .=
                    sprintf("NameFormat %s for %s in RequestedAttribute for index %d is not recomended.\n",
                      $nameFormat, $name, $index);
              }
            } else {
              $this->warning .=
                sprintf("NameFormat is missing for %s in RequestedAttribute for index %d. %s\n",
                  $name, $index, 'This might create problmes with some IdP:s');
            }
            $requestedAttributeHandler->execute();
            $requestedAttributeFound = true;
          } else {
            $this->error .=
              sprintf("%sSPSSODescriptor->AttributeConsumingService[index=%d]->RequestedAttribute.\n",
                'A Name attribute is Required in ', $index);
          }
          break;
        default :
          $this->result .= $child->nodeType == 8 ? '' :
            sprintf("SPSSODescriptor->AttributeConsumingService->%s missing in validator.\n", $child->nodeName);
      }
      $child = $child->nextSibling;
    }
    if ( ! $serviceNameFound ) {
      $this->error .= sprintf(
          "SWAMID Tech 6.1.17: ServiceName is Required in SPSSODescriptor->AttributeConsumingService[index=%d].\n",
          $index);
    }
    if ( ! $requestedAttributeFound ) {
      $this->error .= sprintf(
      "SWAMID Tech 6.1.19: RequestedAttribute is Required in SPSSODescriptor->AttributeConsumingService[index=%d].\n",
      $index);
    }
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
        case self::SAML_MD_EXTENSIONS :
          # Skippar då SWAMID inte använder denna del
          break;
        case self::SAML_MD_KEYDESCRIPTOR :
          $this->parseKeyDescriptor($child, 'AttributeAuthority', $keyOrder++);
          break;
        # 2.4.2
        #case self::SAML_MD_ARTIFACTRESOLUTIONSERVICE :
        #case self::SAML_MD_SINGLELOGOUTSERVICE :
        #case self::SAML_MD_MANAGENAMEIDSERVICE :
        # 2.4.7
        case self::SAML_MD_ATTRIBUTESERVICE :
        #case self::SAML_MD_ASSERTIONIDREQUESTSERVICE :
        case self::SAML_MD_NAMEIDFORMAT :
          # Skippar då SWAMID inte använder denna del
          break;
        #case self::SAML_MD_ATTRIBUTEPROFILE :
        #case self::SAML_MD_ATTRIBUTE :

        default :
          $this->result .= $child->nodeType == 8 ? '' :
            sprintf("AttributeAuthorityDescriptor->%s missing in validator.\n", $child->nodeName);
      }
      $child = $child->nextSibling;
    }
  }

  #############
  # Organization
  #############
  private function parseOrganization($data) {
    # https://docs.oasis-open.org/security/saml/v2.0/saml-metadata-2.0-os.pdf 2.3.2.1
    $organizationHandler = $this->metaDb->prepare('INSERT INTO Organization (`entity_id`, `lang`, `element`, `data`)
      VALUES (:Id, :Lang, :Element, :Value)');

    $organizationHandler->bindValue(self::BIND_ID, $this->dbIdNr);
    $organizationHandler->bindParam(self::BIND_LANG, $lang);
    $organizationHandler->bindParam(self::BIND_ELEMENT, $element);

    $child = $data->firstChild;
    while ($child) {
      $lang = $child->getAttribute('xml:lang') ? $child->getAttribute('xml:lang') : ''; #NOSONAR Used in Bind above
      switch ($child->nodeName) {
        case self::SAML_MD_ORGANIZATIONURL :
          $this->addURL(trim($child->textContent), 1);
          $element = substr($child->nodeName, 3); #NOSONAR Used in Bind above
          break;
        case self::SAML_MD_EXTENSIONS :
        case self::SAML_MD_ORGANIZATIONNAME :
        case self::SAML_MD_ORGANIZATIONDISPLAYNAME :
          $element = substr($child->nodeName, 3); #NOSONAR Used in Bind above
          break;
        default :
          $this->result .= sprintf("Organization->%s missing in validator.\n", $child->nodeName);
      }
      $organizationHandler->bindValue(self::BIND_VALUE, trim($child->textContent));
      $organizationHandler->execute();
      $child = $child->nextSibling;
    }
  }

  #############
  # ContactPerson
  #############
  private function parseContactPerson($data) {
    # https://docs.oasis-open.org/security/saml/v2.0/saml-metadata-2.0-os.pdf 2.3.2.2
    $extensions = '';
    $company = '';
    $givenName = '';
    $surName = '';
    $emailAddress = '';
    $telephoneNumber = '';
    $contactType = $data->getAttribute('contactType');
    $subcontactType = '';

    $contactPersonHandler = $this->metaDb->prepare('INSERT INTO ContactPerson
      (`entity_id`, `contactType`, `subcontactType`, `company`, `emailAddress`,
        `extensions`, `givenName`, `surName`, `telephoneNumber`)
      VALUES (:Id, :ContactType, :SubcontactType, :Company, :EmailAddress,
        :Extensions, :GivenName, :SurName, :TelephoneNumber)');

    $contactPersonHandler->bindValue(self::BIND_ID, $this->dbIdNr);

    # https://docs.oasis-open.org/security/saml/Post2.0/sstc-saml-metadata-ui/v1.0/os/sstc-saml-metadata-ui-v1.0-os.html
    switch ($data->getAttribute('contactType')) {
      case 'administrative' :
      case 'billing' :
      case 'support' :
      case 'technical' :
        break;
      case 'other' :
        if ($data->getAttribute('remd:contactType')) {
          if ($data->getAttribute('remd:contactType') == 'http://refeds.org/metadata/contactType/security') { # NOSONAR Should be http://
            $subcontactType =  'security';
          } else {
            $subcontactType =  'unknown';
            $this->result .= sprintf("ContactPerson->Unknown subcontactType->%s.\n",
              $data->getAttribute('remd:contactType'));
          }
        } else {
          $this->result .= sprintf("ContactPerson->%s->Unknown subcontactType.\n", $data->getAttribute('contactType'));
        }
        break;
      default :
        $contactType = 'Unknown';
        $this->result .= sprintf("Unknown contactType in ContactPerson->%s.\n", $data->getAttribute('contactType'));
    }

    $child = $data->firstChild;
    while ($child) {
      switch ($child->nodeName) {
        case self::SAML_MD_EXTENSIONS :
          $extensions = trim($child->textContent);
          break;
        case self::SAML_MD_COMPANY :
          $company = trim($child->textContent);
          break;
        case self::SAML_MD_GIVENNAME :
          $givenName = trim($child->textContent);
          break;
        case self::SAML_MD_SURNAME :
          $surName = trim($child->textContent);
          break;
        case self::SAML_MD_EMAILADDRESS :
          $emailAddress = trim($child->textContent);
          break;
        case self::SAML_MD_TELEPHONENUMBER :
          $telephoneNumber = trim($child->textContent);
          break;
        default :
          $this->result .= sprintf("ContactPerson->%s missing in validator.\n", $child->nodeName);
      }
      $child = $child->nextSibling;
    }
    $contactPersonHandler->bindParam(self::BIND_CONTACTTYPE, $contactType);
    $contactPersonHandler->bindParam(self::BIND_SUBCONTACTTYPE, $subcontactType);
    $contactPersonHandler->bindParam(self::BIND_COMPANY, $company);
    $contactPersonHandler->bindParam(self::BIND_EMAILADDRESS, $emailAddress);
    $contactPersonHandler->bindParam(self::BIND_EXTENSIONS, $extensions);
    $contactPersonHandler->bindParam(self::BIND_GIVENNAME, $givenName);
    $contactPersonHandler->bindParam(self::BIND_SURNAME, $surName);
    $contactPersonHandler->bindParam(self::BIND_TELEPHONENUMBER, $telephoneNumber);
    $contactPersonHandler->execute();
  }

  #############
  # UIInfo
  # Used by IDPSSODescriptor and SPSSODescriptor
  #############
  private function parseSSODescriptorExtensionsUIInfo($data, $type) {
    $ssoUIIHandler = $this->metaDb->prepare('INSERT INTO Mdui
      (`entity_id`, `type`, `lang`, `height`, `width`, `element`, `data`)
      VALUES (:Id, :Type, :Lang, :Height, :Width, :Element, :Value)');
    $urlHandler = $this->metaDb->prepare('SELECT `nosize`, `height`, `width`, `status` FROM URLs WHERE `URL` = :URL');

    $ssoUIIHandler->bindValue(self::BIND_ID, $this->dbIdNr);
    $ssoUIIHandler->bindValue(self::BIND_TYPE, $type);
    $ssoUIIHandler->bindParam(self::BIND_LANG, $lang);
    $ssoUIIHandler->bindParam(self::BIND_HEIGHT, $height);
    $ssoUIIHandler->bindParam(self::BIND_WIDTH, $width);
    $ssoUIIHandler->bindParam(self::BIND_ELEMENT, $element);
    $ssoUIIHandler->bindParam(self::BIND_VALUE, $value);

    # https://docs.oasis-open.org/security/saml/Post2.0/sstc-saml-metadata-ui/v1.0/os/sstc-saml-metadata-ui-v1.0-os.html
    $child = $data->firstChild;
    while ($child) {
      if ($child->nodeType != 8) {
        $height = 0;
        $width = 0;
        $lang = $child->getAttribute('xml:lang') ?
          $child->getAttribute('xml:lang') : '';
        $urltype = 1;
        switch ($child->nodeName) {
          case self::SAML_MDUI_LOGO :
            $urltype = 2;
            $this->addURL(trim($child->textContent), $urltype);
            $element = substr($child->nodeName, 5);
            $height = $child->getAttribute('height') ? $child->getAttribute('height') : 0;
            $width = $child->getAttribute('width') ? $child->getAttribute('width') : 0;
            $urlHandler->execute(array(self::BIND_URL => trim($child->textContent)));
            if ($urlInfo = $urlHandler->fetch(PDO::FETCH_ASSOC)) {
              if ($urlInfo['height'] != $height && $urlInfo['status'] == 0 && $urlInfo['nosize'] == 0) {
                $this->error .= sprintf(
                  "Logo (%dx%d) lang=%s is marked with height %s in metadata but actual height is %d.\n",
                  $height, $width, $lang, $height, $urlInfo['height']);
              }
              if ($urlInfo['width'] != $width && $urlInfo['status'] == 0 && $urlInfo['nosize'] == 0) {
                $this->error .= sprintf(
                  "Logo (%dx%d) lang=%s is marked with width %s in metadata but actual width is %d.\n",
                  $height, $width, $lang, $width, $urlInfo['width']);
              }
            } else {
              $this->warning .= sprintf("Logo (%dx%d) lang=%s is not checked.\n", $height, $width, $lang);
            }
            break;
          case self::SAML_MDUI_INFORMATIONURL :
          case self::SAML_MDUI_PRIVACYSTATEMENTURL :
            $this->addURL(trim($child->textContent), $urltype);
            $element = substr($child->nodeName, 5);
            break;
          case self::SAML_MDUI_DISPLAYNAME :
          case self::SAML_MDUI_DESCRIPTION :
          case self::SAML_MDUI_KEYWORDS :
            $element = substr($child->nodeName, 5);
            break;
          default :
            $this->result .= sprintf ("Unknown Element (%s) in %s->UIInfo.\n", $child->nodeName, $type);
            $element = 'Unknown';
        }

        $value = trim($child->textContent);
        $ssoUIIHandler->execute();
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
          $this->parseKeyDescriptorKeyInfo($child, $type, $use, $order);
          break;
        case self::SAML_MD_ENCRYPTIONMETHOD :
          break;
        default :
          $this->result .= $child->nodeType == 8 ? '' :
            sprintf("%sDescriptor->KeyDescriptor->%s missing in validator.\n", $type, $child->nodeName);
      }
      $child = $child->nextSibling;
    }
  }

  #############
  # KeyDescriptor KeyInfo
  #############
  private function parseKeyDescriptorKeyInfo($data, $type, $use, $order) {
    $name = '';
    $child = $data->firstChild;
    while ($child) {
      switch ($child->nodeName) {
        case 'ds:KeyName' :
          $name = trim($child->textContent);
          break;
        case 'ds:X509Data' :
          $this->parseKeyDescriptorKeyInfoX509Data($child, $type, $use, $order, $name);
          break;
        default :
          $this->result .= $child->nodeType == 8 ? '' :
            sprintf("%sDescriptor->KeyDescriptor->KeyInfo->%s missing in validator.\n", $type, $child->nodeName);
      }
      $child = $child->nextSibling;
    }
  }

  #############
  # KeyDescriptor KeyInfo X509Data
  # Extract Certs and check dates
  #############
  private function parseKeyDescriptorKeyInfoX509Data($data, $type, $use, $order, $name) {
    $keyInfoHandler = $this->metaDb->prepare('INSERT INTO KeyInfo
      (`entity_id`, `type`, `use`, `order`, `name`, `notValidAfter`,
        `subject`, `issuer`, `bits`, `key_type`, `serialNumber`)
      VALUES (:Id, :Type, :Use, :Order, :Name, :NotValidAfter,
        :Subject, :Issuer, :Bits, :Key_type, :SerialNumber)');

    $keyInfoHandler->bindValue(self::BIND_ID, $this->dbIdNr);
    $keyInfoHandler->bindValue(self::BIND_TYPE, $type);
    $keyInfoHandler->bindValue(self::BIND_USE, $use);
    $keyInfoHandler->bindValue(self::BIND_ORDER, $order);
    $keyInfoHandler->bindParam(self::BIND_NAME, $name);

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
          $cert = "-----BEGIN CERTIFICATE-----\n" .
            chunk_split(str_replace(array(' ',"\n") ,array('',''),trim($child->textContent)),64) .
            "-----END CERTIFICATE-----\n";
          if ($certInfo = openssl_x509_parse( $cert)) {
            $keyInfo = openssl_pkey_get_details(openssl_pkey_get_public($cert));
            switch ($keyInfo['type']) {
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
            foreach ($certInfo['subject'] as $key => $value){
              if ($first) {
                $first = false;
                $sep = '';
              } else {
                $sep = ', ';
              }
              if (is_array($value)) {
                foreach ($value as $subvalue) {
                  $subject .= $sep . $key . '=' . $subvalue;
                }
              } else {
                $subject .= $sep . $key . '=' . $value;
              }
            }
            $issuer = '';
            $first = true;
            foreach ($certInfo['issuer'] as $key => $value){
              if ($first) {
                $first = false;
                $sep = '';
              } else {
                $sep = ', ';
              }
              if (is_array($value)) {
                foreach ($value as $subvalue) {
                  $issuer .= $sep . $key . '=' . $subvalue;
                }
              } else {
                $issuer .= $sep . $key . '=' . $value;
              }
            }

            $keyInfoHandler->bindValue(self::BIND_NOTVALIDAFTER, date('Y-m-d H:i:s', $certInfo['validTo_time_t']));
            $keyInfoHandler->bindParam(self::BIND_SUBJECT, $subject);
            $keyInfoHandler->bindParam(self::BIND_ISSUER, $issuer);
            $keyInfoHandler->bindParam(self::BIND_BITS, $keyInfo['bits']);
            $keyInfoHandler->bindParam(self::BIND_KEY_TYPE, $keyType);
            $keyInfoHandler->bindParam(self::BIND_SERIALNUMBER, $certInfo['serialNumber']);
          } else {
            $keyInfoHandler->bindValue(self::BIND_NOTVALIDAFTER, '1970-01-01 00:00:00');
            $keyInfoHandler->bindValue(self::BIND_SUBJECT, '?');
            $keyInfoHandler->bindValue(self::BIND_ISSUER, '?');
            $keyInfoHandler->bindValue(self::BIND_BITS, 0);
            $keyInfoHandler->bindValue(self::BIND_KEY_TYPE, '?');
            $keyInfoHandler->bindValue(self::BIND_SERIALNUMBER, '?');
            $name = 'Invalid Certificate';
          }
        break;
        #case'ds:X509CRL' :
        default :
          $this->result .= $child->nodeType == 8 ? '' :
            sprintf("%sDescriptor->KeyDescriptor->KeyInfo->X509Data->%s missing in validator.\n",
              $type, $child->nodeName);
      }
      $child = $child->nextSibling;
    }
    $keyInfoHandler->execute();
  }

  # Validate SAML-rules
  public function validateSAML() {
    if (! $this->entityExists) {
      $this->result = "$this->entityID doesn't exist!!";
      return 1;
    }

    $this->isSP_RandS = false;
    $this->isSP_CoCov1 = false;
    $this->isSP_CoCov2 = false;
    $this->isSIRTFI = false;

    $entityAttributesHandler = $this->metaDb->prepare('SELECT `type`, `attribute`
      FROM EntityAttributes WHERE `entity_id` = :Id');
    $entityAttributesHandler->bindValue(self::BIND_ID, $this->dbIdNr);
    $entityAttributesHandler->execute();
    while ($entityAttribute = $entityAttributesHandler->fetch(PDO::FETCH_ASSOC)) {
      switch ($entityAttribute['attribute']) {
        case 'http://refeds.org/category/research-and-scholarship' : # NOSONAR Should be http://
          if ($entityAttribute['type'] == 'entity-category' && $this->isSP) {
            $this->isSP_RandS = true;
          }
          break;
        case 'http://www.geant.net/uri/dataprotection-code-of-conduct/v1' : # NOSONAR Should be http://
          if ($entityAttribute['type'] == 'entity-category' && $this->isSP) {
            $this->isSP_CoCov1 = true;
          }
          break;
        case 'https://refeds.org/category/code-of-conduct/v2' :
          if ($entityAttribute['type'] == 'entity-category' && $this->isSP)
            $this->isSP_CoCov2 = true;
          break;
        case 'https://refeds.org/sirtfi' :
          if ($entityAttribute['type'] == 'assurance-certification' )
            $this->isSIRTFI = true;
          break;
        default:
      }
    }

    if ($this->feedValue == 1) {
      $this->error .= 'SWAMID-Testing is deprecated and will be removed after 2024-06-30. It is no longer possible to update this entity.';
    }

    // 5.1.1 -> 5.1.5 / 6.1.1 -> 6.1.5
    $this->checkLangElements();

    // 5.1.7 /6.1.7
    if (! (substr($this->entityID, 0, 4) == 'urn:' ||
      substr($this->entityID, 0, 8) == 'https://' ||
      substr($this->entityID, 0, 7) == 'http://' )) {
        $this->error .= $this->selectError('5.1.7', '6.1.7',
          'entityID MUST start with either urn:, https:// or http://.');
    }

    if (substr($this->entityID, 0, 4) == 'urn:' ) {
      $this->warning .= $this->selectError('5.1.7', '6.1.7', 'entityID SHOULD NOT start with urn: for new entitys.');
    }

    // 5.1.8 /6.1.8
    if (strlen($this->entityID) > 256) {
      $this->error .= $this->selectError('5.1.8', '6.1.8', 'entityID MUST NOT exceed 256 characters.');
    }

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
    if ($this->isAA) {
      // 5.1.20, 5.2.x
      $this->checkRequiredSAMLcertificates('AttributeAuthority');
    }

    // 5.1.22 / 6.1.20
    $this->checkRequiredOrganizationElements();

    // 5.1.23 -> 5.1.28 / 6.1.21 -> 6.1.26
    if ($this->isIdP) {
      if ($this->isSP) {
        // 5.1.23 -> 5.1.28 / 6.1.21 -> 6.1.26
        $this->checkRequiredContactPersonElements('both');
      } else {
        // 5.1.23 -> 5.1.28
        $this->checkRequiredContactPersonElements('IDPSSO');
      }
    } else {
      // 6.1.21 -> 6.1.26
      $this->checkRequiredContactPersonElements('SPSSO');
    }

    if ($this->isSP_RandS) { $this->validateSPRandS(); }

    if ($this->isSP_CoCov1) { $this->validateSPCoCov1(); }
    if ($this->isSP_CoCov2) { $this->validateSPCoCov2(); }

    $resultHandler = $this->metaDb->prepare("UPDATE Entities
      SET `validationOutput` = :validationOutput,
        `warnings` = :Warnings,
        `errors` = :Errors,
        `errorsNB` = :ErrorsNB,
        `lastValidated` = NOW()
      WHERE `id` = :Id;");
    $resultHandler->bindValue(self::BIND_ID, $this->dbIdNr);
    $resultHandler->bindValue(self::BIND_VALIDATIONOUTPUT, $this->result);
    $resultHandler->bindValue(self::BIND_WARNINGS, $this->warning);
    $resultHandler->bindValue(self::BIND_ERRORS, $this->error);
    $resultHandler->bindValue(self::BIND_ERRORSNB, $this->errorNB);
    $resultHandler->execute();
    $this->validateURLs();
  }

  // 5.1.1 -> 5.1.5/ 6.1.1 -> 6.1.5
  private function checkLangElements() {
    $mduiArray = array();
    $usedLangArray = array();
    $mduiHandler = $this->metaDb->prepare("SELECT `type`, `lang`, `element`
      FROM Mdui WHERE `type` <> 'IDPDisco' AND `entity_id` = :Id;");
    $mduiHandler->bindValue(self::BIND_ID, $this->dbIdNr);
    $mduiHandler->execute();
    while ($mdui = $mduiHandler->fetch(PDO::FETCH_ASSOC)) {
      $type = $mdui['type'];
      $lang = $mdui['lang'];
      $element = $mdui['element'];
      if (! $lang == '') {
        $usedLangArray[$lang] = $lang;
      }

      if (! isset ($mduiArray[$type])) {
        $mduiArray[$type] = array();
      }
      if (! isset ($mduiArray[$type][$element])) {
        $mduiArray[$type][$element] = array();
      }

      if (isset($mduiArray[$type][$element][$lang])) {
        if ($element != 'Logo') {
          if ($type == 'IDPSSO') {
            $this->error .= sprintf("SWAMID Tech 5.1.2: More than one mdui:%s with lang=%s in %sDescriptor.\n",
              $element, $lang, $type);
          } else {
            $this->error .= sprintf("SWAMID Tech 6.1.2: More than one mdui:%s with lang=%s in %sDescriptor.\n",
              $element, $lang, $type);
          }
        }
      } else {
        $mduiArray[$type][$element][$lang] = true;
      }
    }

    $serviceNameArray = array();
    $serviceDescriptionArray = array();

    $serviceElementHandler = $this->metaDb->prepare('SELECT `element`, `lang`, `Service_index`
      FROM AttributeConsumingService_Service WHERE `entity_id` = :Id');
    $serviceElementHandler->bindValue(self::BIND_ID, $this->dbIdNr);
    $serviceElementHandler->execute();
    while ($service = $serviceElementHandler->fetch(PDO::FETCH_ASSOC)) {
      $element = $service['element'];
      $lang = $service['lang'];
      $index = $service['Service_index'];
      if (! $lang == '') {
        $usedLangArray[$lang] = $lang;
      }

      switch ($element) {
        case 'ServiceName' :
          if (! isset ($serviceNameArray[$index])) {
            $serviceNameArray[$index] = array();
          }

          if (isset($serviceNameArray[$index][$lang])) {
            $this->error .= sprintf(
              "SWAMID Tech 6.1.2: More than one ServiceName with lang=%s in AttributeConsumingService (index=%d).\n",
              $lang, $index);
          } else {
            $serviceNameArray[$index][$lang] = true;
          }
          break;
        case 'ServiceDescription' :
          if (! isset ($serviceDescriptionArray[$index])) {
            $serviceDescriptionArray[$index] = array();
          }
          if (isset($serviceDescriptionArray[$index][$lang])) {
            $this->error .= 'SWAMID Tech 6.1.2: More than one ServiceDescription with ';
            $this->error .= sprintf(
              "lang=%s in AttributeConsumingService (index=%d).\n",
              $lang, $index);
          } else {
            $serviceDescriptionArray[$index][$lang] = true;
          }
          break;
        default :
          $this->result .= sprintf("Missing %s in checkLangElements.\n", $element);
      }
    }

    $organizationArray = array();
    $organizationHandler = $this->metaDb->prepare('SELECT `lang`, `element` FROM Organization WHERE `entity_id` = :Id');
    $organizationHandler->bindValue(self::BIND_ID, $this->dbIdNr);
    $organizationHandler->execute();
    while ($organization = $organizationHandler->fetch(PDO::FETCH_ASSOC)) {
      $lang = $organization['lang'];
      $element = $organization['element'];
      if (! $lang == '') {
        $usedLangArray[$lang] = $lang;
      }

      if (! isset ($organizationArray[$element])) {
        $organizationArray[$element] = array();
      }

      if (isset($organizationArray[$element][$lang])) {
        $this->error .= $this->selectError('5.1.2', '6.1.2',
          sprintf('More than one %s with lang=%s in Organization.', $element, $lang));
      } else {
        $organizationArray[$element][$lang] = true;
      }
    }

    // 5.1.1 Metadata elements that support the lang attribute MUST have a lang attribute
    // 5.1.3 If a lang attribute value is used in one metadata element the same lang attribute value MUST
    //       be used for all metadata elements that supports the lang attribute.
    foreach ($mduiArray as $type => $elementArray) {
      foreach ($elementArray as $element => $langArray) {
        foreach ($usedLangArray as $lang) {
          if (! isset($langArray[$lang])) {
            if ($type == 'IDPSSO') {
              $this->error .= sprintf("SWAMID Tech 5.1.3: Missing lang=%s for mdui:%s in %sDescriptor.\n",
                $lang, $element, $type);
            } else {
              $this->error .= sprintf("SWAMID Tech 6.1.3: Missing lang=%s for mdui:%s in %sDescriptor.\n",
                $lang, $element, $type);
            }
          }
        }
      }
    }
    foreach ($serviceNameArray as $langArray) {
      foreach ($usedLangArray as $lang) {
        if (! isset($langArray[$lang])) {
          $this->error .= sprintf("SWAMID Tech 6.1.3: Missing lang=%s for ServiceName in AttributeConsumingService.\n",
            $lang);
        }
      }
    }
    foreach ($serviceDescriptionArray as $langArray) {
      foreach ($usedLangArray as $lang) {
        if (! isset($langArray[$lang])) {
          $this->error .= sprintf('SWAMID Tech 6.1.3: Missing lang=%s', $lang);
          $this->error .= " for ServiceDescription in AttributeConsumingService.\n";
        }
      }
    }
    foreach ($organizationArray as $element => $langArray) {
      foreach ($usedLangArray as $lang) {
        if (! isset($langArray[$lang])) {
          $this->error .= $this->selectError('5.1.3', '6.1.3',
            sprintf('Missing lang=%s for %s in Organization.', $lang, $element));
        }
      }
    }

    //5.1.4/6.1.4 Metadata elements that support the lang attribute MUST have a definition with language English (en).
    if (! isset($usedLangArray['en'])) {
      $this->error .= $this->selectError('5.1.4', '6.1.4', 'Missing MDUI/Organization/... with lang=en.');
    }
    // 5.1.5/6.1.5 Metadata elements that support the lang attribute SHOULD
    //             have a definition with language Swedish (sv).
    if (! isset($usedLangArray['sv'])) {
      $this->warning .= $this->selectError('5.1.5', '6.1.5' ,'Missing MDUI/Organization/... with lang=sv.');
    }
  }

  // 5.1.9 -> 5.1.11 / 6.1.9 -> 6.1.11
  private function checkEntityAttributes($type) {
    $entityAttributesHandler = $this->metaDb->prepare('SELECT `attribute`
      FROM EntityAttributes WHERE `entity_id` = :Id AND `type` = :Type');
    $entityAttributesHandler->bindValue(self::BIND_ID, $this->dbIdNr);

    if ($type == 'IDPSSO' ) {
      //5.1.9 SWAMID Identity Assurance Profile compliance MUST be registered in
      //      the assurance certification entity attribute as defined by the profiles.
      $swamid519error = true;
      $entityAttributesHandler->bindValue(self::BIND_TYPE, 'assurance-certification');
      $entityAttributesHandler->execute();
      while ($entityAttribute = $entityAttributesHandler->fetch(PDO::FETCH_ASSOC)) {
        if ($entityAttribute['attribute'] == 'http://www.swamid.se/policy/assurance/al1' ) { # NOSONAR Should be http://
          $swamid519error = false;
        }
      }
      if ($swamid519error) {
        $this->error .= 'SWAMID Tech 5.1.9: SWAMID Identity Assurance Profile compliance MUST';
        $this->error .= " be registered in the assurance certification entity attribute as defined by the profiles.\n";
      }

      // 5.1.10 Entity Categories applicable to the Identity Provider SHOULD be registered in
      ///       the entity category entity attribute as defined by the respective Entity Category.
      // Not handled yet.

      $swamid5111error = true;
      $entityAttributesHandler->bindValue(self::BIND_TYPE, 'entity-category-support');
      $entityAttributesHandler->execute();
      if ($entityAttributesHandler->fetch(PDO::FETCH_ASSOC)) {
        $swamid5111error = false;
      }
      if ($swamid5111error) {
        $this->warning .= 'SWAMID Tech 5.1.11: Support for Entity Categories SHOULD be registered in the';
        $this->warning .= " entity category support entity attribute as defined by the respective Entity Category.\n";
      }
    } else {
      $entityAttributesHandler->bindValue(self::BIND_TYPE, 'entity-category');
      $entityAttributesHandler->execute();
      while ($entityAttribute = $entityAttributesHandler->fetch(PDO::FETCH_ASSOC)) {
        foreach ($this->standardAttributes['entity-category'] as $data) {
          if ($data['value'] == $entityAttribute['attribute'] && ! $data['swamidStd']) {
            $this->error .= sprintf ("Entity Category Error: The entity category %s is deprecated.\n",
              $entityAttribute['attribute']);
          }
        }
      }
    }
  }

  # 5.1.13 errorURL
  private function checkErrorURL() {
    $errorURLHandler = $this->metaDb->prepare("SELECT DISTINCT `URL`
      FROM EntityURLs WHERE `entity_id` = :Id AND `type` = 'error';");
    $errorURLHandler->bindParam(self::BIND_ID, $this->dbIdNr);
    $errorURLHandler->execute();
    if (! $errorURLHandler->fetch(PDO::FETCH_ASSOC)) {
      $this->error .= "SWAMID Tech 5.1.13: IdP:s MUST have a registered errorURL.\n";
    }
  }

  // 5.1.15, 5.1.16 Scope
  private function checkIDPScope() {
    $scopesHandler = $this->metaDb->prepare('SELECT `scope`, `regexp` FROM Scopes WHERE `entity_id` = :Id');
    $scopesHandler->bindParam(self::BIND_ID, $this->dbIdNr);
    $scopesHandler->execute();
    $missingScope = true;
    while ($scope = $scopesHandler->fetch(PDO::FETCH_ASSOC)) {
      $missingScope = false;
      if ($scope['regexp']) {
        $this->error .= sprintf("SWAMID Tech 5.1.16: IdP Scopes (%s) MUST NOT include regular expressions.\n",
          $scope['scope']);
      }
    }
    if ($missingScope) {
      $this->error .= "SWAMID Tech 5.1.15: IdP:s MUST have at least one Scope registered.\n";
    }
  }

  // 5.1.17
  private function checkRequiredMDUIelementsIdP() {
    $elementArray = array ('DisplayName' => false,
      'Description' => false,
      'InformationURL' => false,
      'PrivacyStatementURL' => false,
      'Logo' => false);
    $mduiDNUniqHandler = $this->metaDb->prepare("SELECT `entityID`
      FROM `Entities`, `Mdui`
      WHERE `Entities`.`id` = `entity_id`
        AND `type`  = 'IDPSSO'
        AND `element` = 'DisplayName'
        AND `data` = :Data
        AND `lang` = :Lang
        AND `status` = 1
        AND `entityID` <> :EntityID;");
    $mduiDNUniqHandler->bindParam(self::BIND_DATA, $data);
    $mduiDNUniqHandler->bindParam(self::BIND_LANG, $lang);
    $mduiDNUniqHandler->bindParam(self::BIND_ENTITYID, $entityID);
    $mduiHandler = $this->metaDb->prepare('SELECT `entityID`, `element`, `data`, `lang`
      FROM `Entities`, `Mdui` WHERE `Entities`.`id` = `entity_id` AND `entity_id` = :Id AND `type`  = :Type');
    $mduiHandler->bindValue(self::BIND_ID, $this->dbIdNr);
    $mduiHandler->bindValue(self::BIND_TYPE, 'IDPSSO');
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
            $this->error .= sprintf("SWAMID Tech 5.1.17: DisplayName for lang %s is also set on %s.\n",
              $lang, $duplicate['entityID']);
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
        default:
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
    $elementArray = array ('DisplayName' => false,
      'Description' => false,
      'InformationURL' => false,
      'PrivacyStatementURL' => false);
    $mduiHandler = $this->metaDb->prepare("SELECT DISTINCT `element`, `data`
      FROM Mdui WHERE `entity_id` = :Id AND `type`  = 'SPSSO';");
    $mduiHandler->bindValue(self::BIND_ID, $this->dbIdNr);
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
        default:
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
    $keyInfoArray = array ('IDPSSO' => false, 'SPSSO' => false, 'AttributeAuthority' => false);
    $keyInfoHandler = $this->metaDb->prepare('SELECT `use`, `notValidAfter`, `subject`, `issuer`, `bits`, `key_type`
      FROM KeyInfo
      WHERE `entity_id` = :Id AND `type` =:Type
      ORDER BY notValidAfter DESC');
    $keyInfoHandler->bindValue(self::BIND_ID, $this->dbIdNr);
    $keyInfoHandler->bindValue(self::BIND_TYPE, $type);
    $keyInfoHandler->execute();

    $swamid521Level = array ('encryption' => 0, 'signing' => 0, 'both' => 0);
    $swamid521Level2030 = array ('encryption' => 0, 'signing' => 0, 'both' => 0);
    $swamid521error = 0;
    $swamid5212030error = 0;
    $swamid522error = false;
    $swamid522errorNB = false;
    $swamid523warning = false;
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
              $keyInfoArray['AttributeAuthority'] = true;
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
              $keyInfoArray['AttributeAuthority'] = true;
            }
            $validEncryptionFound = true;
            $validSigningFound = true;
          } elseif ($validEncryptionFound &&  $validSigningFound) {
            $validCertExists = true;
            $oldCertFound = true;
          }
          break;
        default:
          break;
      }
      switch ($keyInfo['key_type']) {
        case 'RSA' :
        case 'DSA' :
          if ($keyInfo['bits'] >= 4096 ) {
            $swamid521Level[$keyInfo['use']] = 3;
          } elseif ($keyInfo['bits'] >= 2048 && $swamid521Level[$keyInfo['use']] < 2 ) {
            if ($keyInfo['notValidAfter'] > '2030-12-31' && $keyInfo['bits'] < 3072) {
              $swamid521Level2030[$keyInfo['use']] = true;
            }
            $swamid521Level[$keyInfo['use']] = 2;
          } elseif ($swamid521Level[$keyInfo['use']] < 1) {
            $swamid521Level[$keyInfo['use']] = 1;
          }
          if ($keyInfo['bits'] < 2048) { $smalKeyFound = true; }
          break;
        case 'EC' :
          if ($keyInfo['bits'] >= 384 ) {
              $swamid521Level[$keyInfo['use']] = 3;
          } elseif ($keyInfo['bits'] >= 256 && $swamid521Level[$keyInfo['use']] < 2 ) {
              $swamid521Level[$keyInfo['use']] = 2;
            } else {
              $swamid521Level[$keyInfo['use']] = 1;
            }
          if ($keyInfo['bits'] < 256) { $smalKeyFound = true; }
          break;
        default:
            break;
      }
      if ($keyInfo['notValidAfter'] <= $timeNow ) {
        if ($validCertExists) {
          $swamid522errorNB = true;
        } else {
          $swamid522error = true;
        }
      } elseif ($keyInfo['notValidAfter'] <= $timeWarn ) {
        $this->warning .= sprintf (
          "Certificate (%s) %s will soon expire. %s\n",
          $keyInfo['use'], $keyInfo['subject'],
          'New certificate should be have a key strength of at least 4096 bits for RSA or 384 bits for EC.');
      }

      if ($keyInfo['subject'] != $keyInfo['issuer']) {
        $swamid523warning = true;
      }
    }

    if (! $keyInfoArray[$type]) {
      if ($type == 'IDPSSO') {
        $this->error .=
          "SWAMID Tech 5.1.20: Identity Providers MUST have at least one valid signing certificate.\n";
      } elseif ($type == 'AttributeAuthority') {
        $this->error .=
          "SWAMID Tech 5.1.20: Attribute Authorities MUST have at least one valid signing certificate.\n";
      } else {
        $this->error .=
          "SWAMID Tech 6.1.14: Service Providers MUST have at least one valid encryption certificate.\n";
      }
    }
    // 5.2.1 Identity Provider credentials (i.e. entity keys)
    //       MUST NOT use shorter comparable key strength
    //       (in the sense of NIST SP 800-57) than a 2048-bit RSA key. 4096-bit is RECOMMENDED.
    // 6.2.1 Relying Party credentials (i.e. entity keys)
    //       MUST NOT use shorter comparable key strength (in the sense of NIST SP 800-57)
    //       than 2048-bit RSA/DSA keysor 256-bit ECC keys. 4096-bit RSA/DSAkeysor 384-bitECC keys are RECOMMENDED
    // At least one cert exist that is used for either signing or encryption,
    //  Error = code for cert with lowest # of bits
    foreach (array('encryption', 'signing') as $use) {
      if ($swamid521Level[$use] > 0) {
        switch ($swamid521Level[$use]) {
          case 3 :
            // Key >= 4096 or >= 384
            // Do nothing. Keep current level.
            break;
          case 2 :
            // Key >= 2048 and < 4096  // >= 256 and <384
            $swamid521error = $swamid521error == 0 ? 1 : $swamid521error;
            $swamid5212030error = $swamid5212030error ? true : $swamid521Level2030[$use];
            break;
          case 1:
            // To small key
            $swamid521error = 2;
          default:
            break;
        }
        $keyFound = true;
      }
    }

    // Cert exist that is used for both signing and encryption
    if ($swamid521Level['both'] > 0) {
      // Error code could get better if both is better than encryption/signing
      switch ($swamid521Level['both']) {
        case 3 :
          // Key >= 4096 or >= 384
          $swamid521error = 0;
          $swamid5212030error = false;
          break;
        case 2 :
          // Key >= 2048 and < 4096  // >= 256 and <384
          if ($keyFound) {
            // If already checked enc/signing lower if we are better
            $swamid521error = $swamid521error > 1 ? 1 : $swamid521error;
          } else {
            // No enc/siging found set warning
            $swamid521error = 1;
          }
          $swamid5212030error = $swamid5212030error ? true : $swamid521Level2030['both'];
          break;
        case 1:
          // To small key
          // Flagg if no enc/signing was found
          if (! $keyFound) {
            $swamid521error = 2;
          }
          break;
        default :
      }
    }

    if ($swamid521error) {
      if ($swamid521error == 1) {
        if ($smalKeyFound) {
          $this->errorNB .= sprintf('SWAMID Tech %s: (NonBreaking) Certificate MUST NOT use shorter comparable',
            ($type == 'SPSSO') ? '6.2.1' : '5.2.1');
          $this->errorNB .= " key strength (in the sense of NIST SP 800-57) than a 2048-bit RSA key.\n";
        } else {
          $this->warning .= sprintf('SWAMID Tech %s:', ($type == 'SPSSO') ? '6.2.1' : '5.2.1');
          $this->warning .= " Certificate key strength under 4096-bit RSA is NOT RECOMMENDED.\n";
        }
      } elseif ($swamid521error == 2) {
        $this->error .= sprintf('SWAMID Tech %s: Certificate MUST NOT use shorter comparable',
          ($type == 'SPSSO') ? '6.2.1' : '5.2.1');
        $this->error .= ' key strength (in the sense of NIST SP 800-57) than a 2048-bit RSA key. New certificate';
        $this->error .= " should be have a key strength of at least 4096 bits for RSA or 384 bits for EC.\n";
      }
    } else {
      if ($smalKeyFound) {
        $this->errorNB .= sprintf('SWAMID Tech %s: (NonBreaking) Certificate MUST NOT use shorter comparable',
          ($type == 'SPSSO') ? '6.2.1' : '5.2.1');
        $this->errorNB .= " key strength (in the sense of NIST SP 800-57) than a 2048-bit RSA key.\n";
      }
    }
    if ($swamid5212030error) {
      $this->warning .= sprintf('SWAMID Tech %s: Certificate MUST NOT use shorter comparable key strength',
        ($type == 'SPSSO') ? '6.2.1' : '5.2.1');
      $this->warning .= " (in the sense of NIST SP 800-57) than a 3072-bit RSA key if valid after 2030-12-31.\n";
    }

    if ($swamid522error) {
      $this->error .= sprintf('SWAMID Tech %s: Signing and encryption certificates MUST NOT be expired. New',
        ($type == 'SPSSO') ? '6.2.2' : '5.2.2');
      $this->error .= " certificate should be have a key strength of at least 4096 bits for RSA or 384 bits for EC.\n";
    } elseif ($swamid522errorNB) {
      $this->errorNB .= sprintf('SWAMID Tech %s: (NonBreaking) Signing and encryption certificates',
        ($type == 'SPSSO') ? '6.2.2' : '5.2.2');
      $this->errorNB .= " MUST NOT be expired.\n";
    }

    if ($oldCertFound) {
      $this->warning .= "One or more old certs found. Please remove when new certs have propagated.\n";
    }

    if ($swamid523warning) {
      $this->warning .= sprintf('SWAMID Tech %s:', ($type == 'SPSSO') ? '6.2.3' : '5.2.3');
      $this->warning .= " Signing and encryption certificates SHOULD be self-signed.\n";
    }
  }

  // 5.1.21 / 6.1.15
  private function checkSAMLEndpointURL($data,$type) {
    $name = $data->nodeName;
    $binding = $data->getAttribute('Binding');
    $location =$data->getAttribute('Location');
    if (substr($location,0,8) <> 'https://') {
      if ($type == "IDPSSO") {
        $this->error .= sprintf(
          "%s https://. Problem in IDPSSODescriptor->%s[Binding=%s].\n",
          'SWAMID Tech 5.1.21: All SAML endpoints MUST start with', $name, $binding);
      } else {
        $this->error .= sprintf(
          "%s https://. Problem in SPSSODescriptor->%s[Binding=%s].\n",
          'SWAMID Tech 6.1.15: All SAML endpoints MUST start with', $name, $binding);
      }
    }
  }

  // 6.1.16
  private function checkAssertionConsumerService($data) {
    $binding = $data->getAttribute('Binding');
    if ($binding == 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect') {
      $this->swamid6116error = true;
    }
  }

  // 5.1.22 / 6.1.21
  private function checkRequiredOrganizationElements() {
    $elementArray = array('OrganizationName' => false, 'OrganizationDisplayName' => false, 'OrganizationURL' => false);

    $organizationHandler = $this->metaDb->prepare('SELECT DISTINCT `element`
      FROM Organization WHERE `entity_id` = :Id');
    $organizationHandler->bindValue(self::BIND_ID, $this->dbIdNr);
    $organizationHandler->execute();
    while ($organization = $organizationHandler->fetch(PDO::FETCH_ASSOC)) {
      $elementArray[$organization['element']] = true;
    }

    foreach ($elementArray as $element => $value) {
      if (! $value) {
        $this->error .= $this->selectError('5.1.22', '6.1.21', sprintf('Missing %s in Organization.', $element));
      }
    }
  }

  // 5.1.23 -> 5.1.28 / 6.1.22 -> 6.1.26
  private function checkRequiredContactPersonElements($type) {
    $usedContactTypes = array();
    $contactPersonHandler = $this->metaDb->prepare('SELECT `contactType`, `subcontactType`, `emailAddress`, `givenName`
      FROM ContactPerson WHERE `entity_id` = :Id');
    $contactPersonHandler->bindValue(self::BIND_ID, $this->dbIdNr);
    $contactPersonHandler->execute();

    while ($contactPerson = $contactPersonHandler->fetch(PDO::FETCH_ASSOC)) {
      $contactType = $contactPerson['contactType'];

      // 5.1.28 Identity Providers / 6.1.27 A Relying Party SHOULD have one ContactPerson element
      //        of contactType other with remd:contactType http://refeds.org/metadata/contactType/security.
      // If the element is present, a GivenName element MUST be present and the ContactPerson MUST
      //  respect the Traffic Light Protocol (TLP) during all incident response correspondence.
      if ($contactType == 'other' &&  $contactPerson['subcontactType'] == 'security' ) {
        $contactType = 'other/security';
        if ( $contactPerson['givenName'] == '') {
          $this->error .= $this->selectError('5.1.28', '6.1.27',
            'GivenName element MUST be present for security ContactPerson.');
        }
      }

      // 5.1.23/6.1.22 ContactPerson elements MUST have an EmailAddress element
      if ($contactPerson['emailAddress'] == '') {
        $this->error .= $this->selectError('5.1.23' , '6.1.22',
          sprintf('ContactPerson [%s] elements MUST have an EmailAddress element.', $contactType));
      } elseif (substr($contactPerson['emailAddress'], 0, 7) != 'mailto:') {
        $this->error .= $this->selectError('5.1.23', '6.1.22',
          sprintf('ContactPerson [%s] EmailAddress MUST start with mailto:.', $contactType));
      }
      // 5.1.24/6.1.23 There MUST NOT be more than one ContactPerson element of each type.
      if ( isset($usedContactTypes[$contactType])) {
        $this->error .= $this->selectError('5.1.24', '6.1.23',
          sprintf('There MUST NOT be more than one ContactPerson element of type = %s.', $contactType));
      } else {
        $usedContactTypes[$contactType] = true;
      }
    }

    // 5.1.25/6.1.24 Identity Providers MUST have one ContactPerson element of type administrative.
    if (!isset ($usedContactTypes['administrative'])) {
      $this->error .= $this->selectError('5.1.25','6.1.24','Missing ContactPerson of type administrative');
    }

    // 5.1.26/6.1.25 Identity Providers MUST have one ContactPerson element of type technical.
    if (!isset ($usedContactTypes['technical'])) {
      $this->error .= $this->selectError('5.1.26','6.1.25','Missing ContactPerson of type technical.');
    }

    // 5.1.27 Identity Providers MUST have one ContactPerson element of type support.
    // 6.1.26 Service Providers SHOULD have one ContactPerson element of type support.
    if (!isset ($usedContactTypes['support'])) {
      if ($type == 'SPSSO') {
        $this->warning .= "SWAMID Tech 6.1.26: Missing ContactPerson of type support.\n";
      } else {
        // $type = IDPSSO or both
        $this->error .= "SWAMID Tech 5.1.27: Missing ContactPerson of type support.\n";
      }
    }

    // 5.1.28 / 6.1.26 Identity Providers SHOULD have one ContactPerson element of contactType other
    if (!isset ($usedContactTypes['other/security'])) {
      if ($this->isSIRTFI) {
        $this->error .= "REFEDS Sirtfi Require that a security contact is published in the entity’s metadata.\n";
      } else {
        $this->warning .= $this->selectError('5.1.28', '6.1.27', 'Missing security ContactPerson.');
      }
    }
  }

  # Validate R&S SP
  # https://refeds.org/category/research-and-scholarship
  private function validateSPRandS() {
    $mduiArray = array();
    $mduiHandler = $this->metaDb->prepare("SELECT `lang`, `element`
      FROM Mdui WHERE `type` = 'SPSSO' AND `entity_id` = :Id;");
    $mduiHandler->bindValue(self::BIND_ID, $this->dbIdNr);
    $mduiHandler->execute();
    while ($mdui = $mduiHandler->fetch(PDO::FETCH_ASSOC)) {
      $lang = $mdui['lang'];
      $element = $mdui['element'];
      if (! isset ($mduiArray[$element])) {
        $mduiArray[$element] = array();
      }
      $mduiArray[$element][$lang] = true;
    }

    if (isset($mduiArray['DisplayName']) && isset($mduiArray['InformationURL'])) {
      if (! (isset($mduiArray['DisplayName']['en']) && isset($mduiArray['InformationURL']['en']))) {
        $this->warning .= 'REFEDS Research and Scholarship 4.3.3 RECOMMEND a MDUI:DisplayName';
        $this->warning .= " and a MDUI:InformationURL with lang=en.\n";
      }
    } else {
      $this->error .= "REFEDS Research and Scholarship 4.3.3 Require a MDUI:DisplayName and a MDUI:InformationURL.\n";
    }

    $contactPersonHandler = $this->metaDb->prepare("SELECT `emailAddress`
      FROM ContactPerson WHERE `contactType` = 'technical' AND `entity_id` = :Id;");
    $contactPersonHandler->bindValue(self::BIND_ID, $this->dbIdNr);
    $contactPersonHandler->execute();
    if (! $contactPersonHandler->fetch(PDO::FETCH_ASSOC)) {
      $this->error .= 'REFEDS Research and Scholarship 4.3.4 Require that the Service Provider provides';
      $this->error .= " one or more technical contacts in metadata.\n";
    }
  }

  # Validate CoCoSP v1
  private function validateSPCoCov1() {
    $mduiArray = array();
    $mduiElementArray = array();
    $mduiHandler = $this->metaDb->prepare("SELECT `lang`, `element`, `data`
      FROM Mdui WHERE `type` = 'SPSSO' AND `entity_id` = :Id;");
    $requestedAttributeHandler = $this->metaDb->prepare('SELECT DISTINCT `Service_index`
      FROM AttributeConsumingService_RequestedAttribute WHERE `entity_id` = :Id');
    $mduiHandler->bindValue(self::BIND_ID, $this->dbIdNr);
    $requestedAttributeHandler->bindValue(self::BIND_ID, $this->dbIdNr);
    $mduiHandler->execute();
    while ($mdui = $mduiHandler->fetch(PDO::FETCH_ASSOC)) {
      $lang = $mdui['lang'];
      $element = $mdui['element'];
      $data = $mdui['data'];
      if (! isset ($mduiArray[$lang])) {
        $mduiArray[$lang] = array();
      }
      $mduiArray[$lang][$element] = $data;
      $mduiElementArray[$element] = true;
      if ($element == 'PrivacyStatementURL' ) {
        $this->addURL($data, 3);
      }
    }

    if (isset($mduiArray['en'])) {
      if (! isset($mduiArray['en']['PrivacyStatementURL'])) {
        $this->error .= 'GÉANT Data Protection Code of Conduct Require a';
        $this->error .= " MDUI - PrivacyStatementURL with at least lang=en.\n";
      }
      if (! isset($mduiArray['en']['DisplayName'])) {
        $this->warning .= 'GÉANT Data Protection Code of Conduct Recomend a';
        $this->warning .= " MDUI - DisplayName with at least lang=en.\n";
      }
      if (! isset($mduiArray['en']['Description'])) {
        $this->warning .= 'GÉANT Data Protection Code of Conduct Recomend a';
        $this->warning .= " MDUI - Description with at least lang=en.\n";
      }
      foreach ($mduiElementArray as $element => $value) {
        if (! isset($mduiArray['en'][$element])) {
          $this->error .= 'GÉANT Data Protection Code of Conduct Require a';
          $this->error .= sprintf(" MDUI - %s with lang=en for all present elements.\n", $element);
        }
      }
    } else {
      $this->error .= "GÉANT Data Protection Code of Conduct Require MDUI with lang=en.\n";
    }
    $requestedAttributeHandler->execute();
    if (! $requestedAttributeHandler->fetch(PDO::FETCH_ASSOC)) {
      $this->error .= "GÉANT Data Protection Code of Conduct Require at least one RequestedAttribute.\n";
    }
  }

  # Validate CoCoSP v2
  private function validateSPCoCov2() {
    $mduiArray = array();
    $mduiElementArray = array();
    $mduiHandler = $this->metaDb->prepare("SELECT `lang`, `element`, `data`
      FROM Mdui WHERE `type` = 'SPSSO' AND `entity_id` = :Id;");
    $requestedAttributeHandler = $this->metaDb->prepare('SELECT DISTINCT `Service_index`
      FROM AttributeConsumingService_RequestedAttribute WHERE `entity_id` = :Id');
    $entityAttributesHandler =  $this->metaDb->prepare('SELECT attribute
      FROM EntityAttributes WHERE `type` = :Type AND `entity_id` = :Id');
    $mduiHandler->bindValue(self::BIND_ID, $this->dbIdNr);
    $requestedAttributeHandler->bindValue(self::BIND_ID, $this->dbIdNr);
    $entityAttributesHandler->bindValue(self::BIND_ID, $this->dbIdNr);
    $mduiHandler->execute();
    while ($mdui = $mduiHandler->fetch(PDO::FETCH_ASSOC)) {
      $lang = $mdui['lang'];
      $element = $mdui['element'];
      $data = $mdui['data'];
      if (! isset ($mduiArray[$lang])) {
        $mduiArray[$lang] = array();
      }
      $mduiArray[$lang][$element] = $data;
      $mduiElementArray[$element] = true;
      if ($element == 'PrivacyStatementURL' ) {
        $this->addURL($data, 2);
      }
    }

    if (isset($mduiArray['en'])) {
      if (! isset($mduiArray['en']['PrivacyStatementURL'])) {
        $this->error .= 'GÉANT Data Protection Code of Conduct (v2) Require';
        $this->error .= " a MDUI - PrivacyStatementURL with at least lang=en.\n";
      }
      if (! isset($mduiArray['en']['DisplayName'])) {
        $this->warning .= 'GÉANT Data Protection Code of Conduct (v2) Recomend';
        $this->warning .= " a MDUI - DisplayName with at least lang=en.\n";
      }
      if (! isset($mduiArray['en']['Description'])) {
        $this->warning .= 'GÉANT Data Protection Code of Conduct (v2) Recomend';
        $this->warning .= " a MDUI - Description with at least lang=en.\n";
      }
      foreach ($mduiElementArray as $element => $value) {
        if (! isset($mduiArray['en'][$element])) {
          $this->error .= 'GÉANT Data Protection Code of Conduct (v2) Require';
          $this->error .= sprintf(" a MDUI - %s with lang=en for all present elements.\n", $element);
        }
      }
    } else {
      $this->error .= "GÉANT Data Protection Code of Conduct (v2) Require MDUI with lang=en.\n";
    }

    $requestedAttributeHandler->execute();
    if (! $requestedAttributeHandler->fetch(PDO::FETCH_ASSOC)) {
      $entityAttributesHandler->bindValue(self::BIND_TYPE, 'subject-id:req');
      $entityAttributesHandler->execute();
      if (! $entityAttributesHandler->fetch(PDO::FETCH_ASSOC)) {
        $this->error .= 'GÉANT Data Protection Code of Conduct (v2) Require';
        $this->error .= " at least one RequestedAttribute OR subject-id:req entity attribute extension.\n";
      }
    }
  }

  #############
  # Removes AssertionConsumerService with binding = HTTP-Redirect.
  # swamid6116error
  #############
  private function cleanOutAssertionConsumerServiceHTTPRedirect() {
    $removed = false;
    $entityDescriptor = $this->getEntityDescriptor($this->xml);
    $child = $entityDescriptor->firstChild;
    while ($child) {
      if ($child->nodeName == self::SAML_MD_SPSSODESCRIPTOR) {
        $subchild = $child->firstChild;
        while ($subchild) {
          if ($subchild->nodeName == self::SAML_MD_ASSERTIONCONSUMERSERVICE
            && $subchild->getAttribute('Binding') == 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect') {
            $index = $subchild->getAttribute('index');
            $remChild = $subchild;
            $child->removeChild($remChild);
            $subchild = false;
            $child=false;
            $removed = true;
          } else {
            $subchild = $subchild->nextSibling;
          }
        }
      } else {
        $child = $child->nextSibling;
      }
    }
    if ($removed) {
      $this->error .= 'SWAMID Tech 6.1.16: Binding with value urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect';
      $this->error .= ' is not allowed in';
      $this->error .= sprintf(" SPSSODescriptor->AssertionConsumerService[index=%d]. Have been removed.\n", $index);
    }
  }

  #############
  # Removes Attribues from IDPSSODescriptor.
  # swamid5131error
  #############
  private function cleanOutAttribuesInIDPSSODescriptor() {
    $removed = false;
    $entityDescriptor = $this->getEntityDescriptor($this->xml);
    $child = $entityDescriptor->firstChild;
    while ($child) {
      if ($child->nodeName == self::SAML_MD_IDPSSODESCRIPTOR) {
        $subchild = $child->firstChild;
        while ($subchild) {
          if ($subchild->nodeName == self::SAML_SAMLA_ATTRIBUTE) {
            $remChild = $subchild;
            $subchild = $subchild->nextSibling;
            $child->removeChild($remChild);
            $removed = true;
          } else {
            $subchild = $subchild->nextSibling;
          }
        }
      }
      $child = $child->nextSibling;
    }
    if ($removed) {
      $this->error .= 'SWAMID Tech 5.1.31: The Identity Provider IDPSSODescriptor element in metadata';
      $this->error .= " MUST NOT include any Attribute elements. Have been removed.\n";
    }
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
    $urlHandler = $this->metaDb->prepare("INSERT INTO EntityURLs
      (`entity_id`, `URL`, `type`) VALUES (:Id, :URL, :Type)");
    $urlHandler->bindValue(self::BIND_ID, $this->dbIdNr);
    $urlHandler->bindValue(self::BIND_URL, $url);
    $urlHandler->bindValue(self::BIND_TYPE, $type);
    $urlHandler->execute();
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
        default :
      }
    }
    $publishedHandler = $this->metaDb->prepare('UPDATE Entities SET `publishIn` = :PublishIn WHERE `id` = :Id');
    $publishedHandler->bindValue(self::BIND_ID, $this->dbIdNr);
    $publishedHandler->bindValue(self::BIND_PUBLISHIN, $publishIn);
    $publishedHandler->execute();
    $this->feedValue = $publishIn;
  }

  #############
  # Updates which feeds by value
  #############
  public function updateFeedByValue($publishIn) {
    $publishedHandler = $this->metaDb->prepare('UPDATE Entities SET `publishIn` = :PublishIn WHERE `id` = :Id');
    $publishedHandler->bindValue(self::BIND_ID, $this->dbIdNr);
    $publishedHandler->bindValue(self::BIND_PUBLISHIN, $publishIn);
    $publishedHandler->execute();
    $this->feedValue = $publishIn;
  }

  #############
  # Updates which user that is responsible for an entity
  #############
  public function updateResponsible($approvedBy) {
    $entityUserHandler = $this->metaDb->prepare('INSERT INTO EntityUser (`entity_id`, `user_id`, `approvedBy`, `lastChanged`) VALUES(:Entity_id, :User_id, :ApprovedBy, NOW()) ON DUPLICATE KEY UPDATE `lastChanged` = NOW()');
    $entityUserHandler->bindParam(self::BIND_ENTITY_ID, $this->dbIdNr);
    $entityUserHandler->bindParam(self::BIND_USER_ID, $this->user['id']);
    $entityUserHandler->bindParam(self::BIND_APPROVEDBY, $approvedBy);
    $entityUserHandler->execute();
  }

  #############
  # Copies which user that is responsible for an entity from another entity
  #############
  public function copyResponsible($otherEntity_id) {
    $entityUserHandler = $this->metaDb->prepare(
      'INSERT INTO EntityUser (`entity_id`, `user_id`, `approvedBy`, `lastChanged`)
      VALUES(:Entity_id, :User_id, :ApprovedBy, :LastChanged)
      ON DUPLICATE KEY UPDATE `lastChanged` = :LastChanged');
    $otherEntityUserHandler = $this->metaDb->prepare(
      'SELECT `user_id`, `approvedBy`, `lastChanged`
      FROM EntityUser
      WHERE `entity_id` = :OtherEntity_Id');

    $entityUserHandler->bindParam(self::BIND_ENTITY_ID, $this->dbIdNr);
    $otherEntityUserHandler->bindParam(self::BIND_OTHERENTITY_ID, $otherEntity_id);
    $otherEntityUserHandler->execute();
    while ($otherEntityUser = $otherEntityUserHandler->fetch(PDO::FETCH_ASSOC)) {
      $entityUserHandler->bindParam(self::BIND_USER_ID, $otherEntityUser['user_id']);
      $entityUserHandler->bindParam(self::BIND_APPROVEDBY, $otherEntityUser['approvedBy']);
      $entityUserHandler->bindParam(self::BIND_LASTCHANGED, $otherEntityUser['lastChanged']);
      $entityUserHandler->execute();
    }
  }

  #############
  # Removes an entity from database
  #############
  public function removeEntity() {
    $this->removeEntityReal($this->dbIdNr);
  }
  private function removeEntityReal($dbIdNr) {
    $entityHandler = $this->metaDb->prepare('SELECT publishedId FROM Entities WHERE id = :Id');
    $entityHandler->bindParam(self::BIND_ID, $dbIdNr);
    $entityHandler->execute();
    if ($entity = $entityHandler->fetch(PDO::FETCH_ASSOC)) {
      if ($entity['publishedId'] > 0) {
        #Remove shadow first
        $this->removeEntityReal($entity['publishedId']);
      }
      # Remove data for this Entity
      $this->metaDb->beginTransaction();
      $this->metaDb->prepare('DELETE FROM EntityAttributes WHERE `entity_id` = :Id')->execute(
        array(self::BIND_ID => $dbIdNr));
      $this->metaDb->prepare('DELETE FROM Mdui WHERE `entity_id` = :Id')->execute(array(self::BIND_ID => $dbIdNr));
      $this->metaDb->prepare('DELETE FROM KeyInfo WHERE `entity_id` = :Id')->execute(array(self::BIND_ID => $dbIdNr));
      $this->metaDb->prepare('DELETE FROM AttributeConsumingService WHERE `entity_id` = :Id')->execute(
        array(self::BIND_ID => $dbIdNr));
      $this->metaDb->prepare('DELETE FROM AttributeConsumingService_Service WHERE `entity_id` = :Id')->execute(
        array(self::BIND_ID => $dbIdNr));
      $this->metaDb->prepare('DELETE FROM AttributeConsumingService_RequestedAttribute
        WHERE `entity_id` = :Id')->execute(array(self::BIND_ID => $dbIdNr));
      $this->metaDb->prepare('DELETE FROM Organization WHERE `entity_id` = :Id')->execute(
        array(self::BIND_ID => $dbIdNr));
      $this->metaDb->prepare('DELETE FROM ContactPerson WHERE `entity_id` = :Id')->execute(
        array(self::BIND_ID => $dbIdNr));
      $this->metaDb->prepare('DELETE FROM EntityURLs WHERE `entity_id` = :Id')->execute(array(self::BIND_ID => $dbIdNr));
      $this->metaDb->prepare('DELETE FROM Scopes WHERE `entity_id` = :Id')->execute(array(self::BIND_ID => $dbIdNr));
      $this->metaDb->prepare('DELETE FROM EntityUser WHERE `entity_id` = :Id')->execute(array(self::BIND_ID => $dbIdNr));
      $this->metaDb->prepare('DELETE FROM Entities WHERE `id` = :Id')->execute(array(self::BIND_ID => $dbIdNr));
      $this->metaDb->commit();
    }
  }

  #############
  # Check if an entity from pendingQueue exists with same XML in published
  #############
  public function checkPendingIfPublished() {
    $pendingHandler = $this->metaDb->prepare('SELECT `entityID`, `xml`, `lastUpdated`
      FROM Entities WHERE `status` = 2 AND `id` = :Id');
    $pendingHandler->bindParam(self::BIND_ID, $this->dbIdNr);
    $pendingHandler->execute();

    $publishedHandler = $this->metaDb->prepare('SELECT `xml`, `lastUpdated`
      FROM Entities WHERE `status` = 1 AND `entityID` = :EntityID');
    $publishedHandler->bindParam(self::BIND_ENTITYID, $entityID);

    require_once __DIR__  . '/NormalizeXML.php';
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
            }
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
    return false;
  }

  #############
  # Moves an entity from Published to SoftDelete state
  #############
  public function move2SoftDelete() {
    $entityHandler = $this->metaDb->prepare('UPDATE Entities
      SET `status` = 4, `lastUpdated` = NOW() WHERE `status` = 1 AND `id` = :Id');
    $entityHandler->bindParam(self::BIND_ID, $this->dbIdNr);
    $entityHandler->execute();
  }

  #############
  # Moves an entity from pendingQueue to publishedPending state
  #############
  public function movePublishedPending() {
    # Check if entity id exist as status pending
    if ($this->status == 2) {
      $publishedEntityHandler = $this->metaDb->prepare('SELECT `id`
        FROM Entities WHERE `status` = 1 AND `entityID` = :Id');
      # Get id of published version
      $publishedEntityHandler->bindParam(self::BIND_ID, $this->entityID);
      $publishedEntityHandler->execute();
      if ($publishedEntity = $publishedEntityHandler->fetch(PDO::FETCH_ASSOC)) {
        $entityHandler = $this->metaDb->prepare('SELECT `lastValidated` FROM Entities WHERE `id` = :Id');
        $entityUserHandler = $this->metaDb->prepare('SELECT `user_id`, `approvedBy`, `lastChanged`
          FROM EntityUser WHERE `entity_id` = :Entity_id ORDER BY `lastChanged`');
        $addEntityUserHandler = $this->metaDb->prepare('INSERT INTO EntityUser
          (`entity_id`, `user_id`, `approvedBy`, `lastChanged`)
          VALUES(:Entity_id, :User_id, :ApprovedBy, :LastChanged)
          ON DUPLICATE KEY
          UPDATE `lastChanged` =
            IF(lastChanged < VALUES(lastChanged), VALUES(lastChanged), lastChanged)');
        $updateEntityConfirmationHandler = $this->metaDb->prepare('INSERT INTO EntityConfirmation
          (`entity_id`, `user_id`, `lastConfirmed`)
          VALUES (:Entity_id, :User_id, :LastConfirmed)
          ON DUPLICATE KEY UPDATE `user_id` = :User_id, `lastConfirmed` = :LastConfirmed');

        # Get lastValidated
        $entityHandler->bindParam(self::BIND_ID, $this->dbIdNr);
        $entityHandler->execute();
        $entity = $entityHandler->fetch(PDO::FETCH_ASSOC);

        $addEntityUserHandler->bindParam(self::BIND_ENTITY_ID, $publishedEntity['id']);

        # Get users having access to this entityID
        $entityUserHandler->bindParam(self::BIND_ENTITY_ID, $this->dbIdNr);
        $entityUserHandler->execute();
        while ($entityUser = $entityUserHandler->fetch(PDO::FETCH_ASSOC)) {
          # Copy userId from pending -> published
          $addEntityUserHandler->bindValue(self::BIND_USER_ID, $entityUser['user_id']);
          $addEntityUserHandler->bindValue(self::BIND_APPROVEDBY, $entityUser['approvedBy']);
          $addEntityUserHandler->bindValue(self::BIND_LASTCHANGED, $entityUser['lastChanged']);
          $addEntityUserHandler->execute();
          $lastUser=$entityUser['user_id'];
        }
        # Set lastValidated on Pending as lastConfirmed on Published
        $updateEntityConfirmationHandler->bindParam(self::BIND_ENTITY_ID, $publishedEntity['id']);
        $updateEntityConfirmationHandler->bindParam(self::BIND_USER_ID, $lastUser);
        $updateEntityConfirmationHandler->bindParam(self::BIND_LASTCONFIRMED, $entity['lastValidated']);
        $updateEntityConfirmationHandler->execute();
      }
      # Move entity to status PendingPublished
      $entityUpdateHandler = $this->metaDb->prepare('UPDATE Entities
        SET `status` = 5, `lastUpdated` = NOW() WHERE `status` = 2 AND `id` = :Id');
      $entityUpdateHandler->bindParam(self::BIND_ID, $this->dbIdNr);
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
      if ($child->nodeName == self::SAML_MD_ENTITYDESCRIPTOR) {
        return $child;
      }
      $child = $child->nextSibling;
    }
    return false;
  }

  #############
  # Return entityID for this entity
  #############
  public function entityID() {
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
  # Return if this entity is an AA
  #############
  public function isAA() {
    return $this->isAA;
  }

  #############
  # Moves a Draft into Pending state
  #############
  public function moveDraftToPending($publishedEntity_id) {
    $this->addRegistrationInfo();
    $entityHandler = $this->metaDb->prepare('UPDATE Entities
      SET `status` = 2, `publishedId` = :PublishedId, `lastUpdated` = NOW(), `xml` = :Xml
      WHERE `status` = 3 AND `id` = :Id');
    $entityHandler->bindParam(self::BIND_ID, $this->dbIdNr);
    $entityHandler->bindParam(self::BIND_PUBLISHEDID, $publishedEntity_id);
    $entityHandler->bindValue(self::BIND_XML, $this->xml->saveXML());

    $entityHandler->execute();
  }

  private function addRegistrationInfo() {
    $entityDescriptor = $this->getEntityDescriptor($this->xml);
    # Find md:Extensions in XML
    $child = $entityDescriptor->firstChild;
    $extensions = false;
    while ($child && ! $extensions) {
      switch ($child->nodeName) {
        case self::SAML_MD_EXTENSIONS :
          $extensions = $child;
          break;
        case self::SAML_MD_SPSSODESCRIPTOR :
        case self::SAML_MD_IDPSSODESCRIPTOR :
        case self::SAML_MD_AUTHNAUTHORITYDESCRIPTOR :
        case self::SAML_MD_ATTRIBUTEAUTHORITYDESCRIPTOR :
        case self::SAML_MD_PDPDESCRIPTOR :
        case self::SAML_MD_AFFILIATIONDESCRIPTOR :
        case self::SAML_MD_ORGANIZATION :
        case self::SAML_MD_CONTACTPERSON :
        case self::SAML_MD_ADDITIONALMETADATALOCATION :
        default :
          $extensions = $this->xml->createElement(self::SAML_MD_EXTENSIONS);
          $entityDescriptor->insertBefore($extensions, $child);
          break;
      }
      $child = $child->nextSibling;
    }
    if (! $extensions) {
      # Add if missing
      $extensions = $this->xml->createElement(self::SAML_MD_EXTENSIONS);
      $entityDescriptor->appendChild($extensions);
    }
    # Find mdattr:EntityAttributes in XML
    $child = $extensions->firstChild;
    $RegistrationInfo = false;
    while ($child && ! $RegistrationInfo) {
      if ($child->nodeName == self::SAML_MDRPI_REGISTRATIONINFO) {
        $RegistrationInfo = $child;
      } else
        $child = $child->nextSibling;
    }
    if (! $RegistrationInfo) {
      # Add if missing
      $ts=date("Y-m-d\TH:i:s\Z");
      $entityDescriptor->setAttributeNS('http://www.w3.org/2000/xmlns/', # NOSONAR Should be http://
        'xmlns:mdrpi', 'urn:oasis:names:tc:SAML:metadata:rpi');
      $RegistrationInfo = $this->xml->createElement(self::SAML_MDRPI_REGISTRATIONINFO);
      $RegistrationInfo->setAttribute('registrationAuthority', 'http://www.swamid.se/'); # NOSONAR Should be http://
      $RegistrationInfo->setAttribute('registrationInstant', $ts);
      $extensions->appendChild($RegistrationInfo);
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
      $RegistrationPolicy = $this->xml->createElement('mdrpi:RegistrationPolicy', 'http://swamid.se/policy/mdrps'); # NOSONAR Should be http://
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
  public function entityExists() {
    return $this->entityExists;
  }

  #############
  # Return ID for this entity in the database
  #############
  public function id() {
    return $this->dbIdNr;
  }

  #############
  # Return feed value for this entity in the database
  #############
  public function feedValue() {
    return $this->feedValue;
  }

  public function entityDisplayName() {
    if (! $this->entityDisplayName ) {
      $displayHandler = $this->metaDb->prepare(
        "SELECT `data` AS DisplayName
        FROM Mdui WHERE entity_id = :Entity_id AND `element` = 'DisplayName' AND `lang` = 'en'");
      $displayHandler->bindParam(self::BIND_ENTITY_ID,$this->dbIdNr);
      $displayHandler->execute();
      if ($displayInfo = $displayHandler->fetch(PDO::FETCH_ASSOC)) {
        $this->entityDisplayName = $displayInfo['DisplayName'];
      } else {
        $this->entityDisplayName = 'Display name missing';
      }
      return $this->entityDisplayName;
    }
  }

  public function getTechnicalAndAdministrativeContacts() {
    $addresses = array();

    # If entity in Published will only match one.
    # If entity in draft, will match both draft and published and get addresses from both.
    $contactHandler = $this->metaDb->prepare("SELECT DISTINCT emailAddress
      FROM `Entities`, `ContactPerson`
      WHERE `Entities`.`id` = `entity_id`
        AND ((`entityID` = :EntityID AND `status` = 1) OR (`Entities`.`id` = :Entity_id AND `status` = 3))
        AND (`contactType`='technical' OR `contactType`='administrative')
        AND `emailAddress` <> ''");
    $contactHandler->bindParam(self::BIND_ENTITYID,$this->entityID);
    $contactHandler->bindParam(self::BIND_ENTITY_ID,$this->dbIdNr);
    $contactHandler->execute();
    while ($address = $contactHandler->fetch(PDO::FETCH_ASSOC)) {
      $addresses[] = substr($address['emailAddress'],7);
    }
    return $addresses;
  }

  #############
  # Return XML for this entity in the database
  #############
  public function xml() {
    return $this->xml->saveXML();
  }

  public function confirmEntity($userId) {
    $entityConfirmHandler = $this->metaDb->prepare('INSERT INTO EntityConfirmation
      (`entity_id`, `user_id`, `lastConfirmed`)
      VALUES (:Id, :User_id, NOW())
      ON DUPLICATE KEY UPDATE  `user_id` = :User_id, `lastConfirmed` = NOW()');
    $entityConfirmHandler->bindParam(self::BIND_ID, $this->dbIdNr);
    $entityConfirmHandler->bindParam(self::BIND_USER_ID, $userId);
    $entityConfirmHandler->execute();
  }

  public function getUser($userID, $email = '', $fullName = '', $add = false) {
    if ($this->user['id'] == 0) {
      $userHandler = $this->metaDb->prepare('SELECT `id`, `email`, `fullName` FROM Users WHERE `userID` = :Id');
      $userHandler->bindValue(self::BIND_ID, strtolower($userID));
      $userHandler->execute();
      if ($this->user = $userHandler->fetch(PDO::FETCH_ASSOC)) {
        if ($add && ($email <> $this->user['email'] || $fullName <>  $this->user['fullName'])) {
          $userHandler = $this->metaDb->prepare('UPDATE Users
            SET `email` = :Email, `fullName` = :FullName WHERE `userID` = :Id');
          $userHandler->bindValue(self::BIND_ID, strtolower($userID));
          $userHandler->bindParam(self::BIND_EMAIL, $email);
          $userHandler->bindParam(self::BIND_FULLNAME, $fullName);
          $userHandler->execute();
        }
      } elseif ($add) {
        $addNewUserHandler = $this->metaDb->prepare('INSERT INTO Users
          (`userID`, `email`, `fullName`) VALUES(:Id, :Email, :FullName)');
        $addNewUserHandler->bindValue(self::BIND_ID, strtolower($userID));
        $addNewUserHandler->bindParam(self::BIND_EMAIL, $email);
        $addNewUserHandler->bindParam(self::BIND_FULLNAME, $fullName);
        $addNewUserHandler->execute();
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
    $userHandler = $this->metaDb->prepare('UPDATE Users
      SET `email` = :Email, `fullName` = :FullName WHERE `userID` = :Id');
    $userHandler->bindValue(self::BIND_ID, strtolower($userID));
    $userHandler->bindValue(self::BIND_EMAIL, $email);
    $userHandler->bindValue(self::BIND_FULLNAME, $fullName);
    $userHandler->execute();
  }

  #############
  # Check if userID is responsible for this entityID
  #############
  public function isResponsible() {
    if ($this->user['id'] > 0) {
      $userHandler = $this->metaDb->prepare('SELECT *
        FROM EntityUser WHERE `user_id` = :User_id AND `entity_id`= :Entity_id' );
      $userHandler->bindParam(self::BIND_USER_ID, $this->user['id']);
      $userHandler->bindParam(self::BIND_ENTITY_ID, $this->dbIdNr);
      $userHandler->execute();
      return $userHandler->fetch(PDO::FETCH_ASSOC);
    } else {
      return false;
    }
  }

  public function getResponsibles() {
    $usersHandler = $this->metaDb->prepare('SELECT `id`, `userID`, `email`, `fullName`
      FROM EntityUser, Users WHERE `entity_id` = :Entity_id AND id = user_id ORDER BY `userID`;');
    $usersHandler->bindParam(self::BIND_ENTITY_ID, $this->dbIdNr);
    $usersHandler->execute();
    return $usersHandler->fetchAll(PDO::FETCH_ASSOC);
  }

  public function createAccessRequest($userId) {
    $hash = hash_hmac('md5',$this->entityID(),time());
    $code = base64_encode(sprintf ('%d:%d:%s', $this->dbIdNr, $userId, $hash));
    $addNewRequestHandler = $this->metaDb->prepare('INSERT INTO `AccessRequests`
      (`entity_id`, `user_id`, `hash`, `requestDate`)
      VALUES (:Entity_id, :User_id, :Hashvalue, NOW())
      ON DUPLICATE KEY UPDATE `hash` = :Hashvalue, `requestDate` = NOW()');
    $addNewRequestHandler->bindParam(self::BIND_ENTITY_ID, $this->dbIdNr);
    $addNewRequestHandler->bindParam(self::BIND_USER_ID, $userId);
    $addNewRequestHandler->bindParam(self::BIND_HASHVALUE, $hash);
    $addNewRequestHandler->execute();
    return $code;
  }

  public function validateCode($userId, $hash, $approvedBy) {
    if ($userId > 0) {
      $userHandler = $this->metaDb->prepare('SELECT *
        FROM EntityUser WHERE `user_id` = :User_id AND `entity_id`= :EntityID' );
      $userHandler->bindParam(self::BIND_USER_ID, $userId);
      $userHandler->bindParam(self::BIND_ENTITYID, $this->dbIdNr);
      $userHandler->execute();
      if ($userHandler->fetch(PDO::FETCH_ASSOC)) {
        $result = array('returnCode' => 1, 'info' => 'User already had access');
      } else {
        $requestHandler = $this->metaDb->prepare('SELECT `requestDate`, NOW() - INTERVAL 1 DAY AS `limit`,
          `email`, `fullName`, `entityID`
          FROM `AccessRequests`, `Users`, `Entities`
          WHERE Users.`id` = `user_id`
            AND `Entities`.`id` = `entity_id`
            AND `entity_id` =  :Entity_id
            AND `user_id` = :User_id
            AND `hash` = :Hashvalue');
        $requestRemoveHandler = $this->metaDb->prepare('DELETE FROM `AccessRequests`
          WHERE `entity_id` =  :Entity_id AND `user_id` = :User_id');
        $requestHandler->bindParam(self::BIND_ENTITY_ID, $this->dbIdNr);
        $requestHandler->bindParam(self::BIND_USER_ID, $userId);
        $requestHandler->bindParam(self::BIND_HASHVALUE, $hash);
        $requestRemoveHandler->bindParam(self::BIND_ENTITY_ID, $this->dbIdNr);
        $requestRemoveHandler->bindParam(self::BIND_USER_ID, $userId);

        $requestHandler->execute();
        if ($request = $requestHandler->fetch(PDO::FETCH_ASSOC)) {
          $requestRemoveHandler->execute();
          if ($request['limit'] < $request['requestDate']) {
            $this->addAccess2Entity($userId, $approvedBy);
            $result = array('returnCode' => 2, 'info' => 'Access granted.',
              'fullName' => $request['fullName'], 'email' => $request['email']);
          } else {
            $result = array('returnCode' => 11, 'info' => 'Code was expired. Please ask user to request new.');
          }
        } else {
          $result = array('returnCode' => 12, 'info' => 'Invalid code');
        }
      }
    } else {
      $result = array('returnCode' => 13, 'info' => 'Error in code');
    }
    return $result;
  }

  public function addAccess2Entity($userId, $approvedBy) {
    $entityUserHandler = $this->metaDb->prepare('INSERT INTO EntityUser
      (`entity_id`, `user_id`, `approvedBy`, `lastChanged`)
      VALUES(:Entity_id, :User_id, :ApprovedBy, NOW())
      ON DUPLICATE KEY UPDATE `lastChanged` = NOW()');
    $entityUserHandler->bindParam(self::BIND_ENTITY_ID, $this->dbIdNr);
    $entityUserHandler->bindParam(self::BIND_USER_ID, $userId);
    $entityUserHandler->bindParam(self::BIND_APPROVEDBY, $approvedBy);
    $entityUserHandler->execute();
  }

  public function removeAccessFromEntity($userId) {
    $entityUserHandler = $this->metaDb->prepare('DELETE FROM EntityUser
      WHERE `entity_id` = :Entity_id AND `user_id` = :User_id');
    $entityUserHandler->bindParam(self::BIND_ENTITY_ID, $this->dbIdNr);
    $entityUserHandler->bindParam(self::BIND_USER_ID, $userId);
    $entityUserHandler->execute();
  }

  public function saveStatus($date = '') {
    if ($date == '') {
      $date = gmdate('Y-m-d');
    }
    $errorsTotal = 0;
    $errorsSPs = 0;
    $errorsIdPs = 0;
    $nrOfEntites = 0;
    $nrOfSPs = 0;
    $nrOfIdPs = 0;
    $changed = 0;
    $entitys = $this->metaDb->prepare("SELECT `id`, `entityID`, `isIdP`, `isSP`, `publishIn`, `lastUpdated`, `errors`
      FROM Entities WHERE status = 1 AND publishIn > 2");
    $entitys->execute();
    while ($row = $entitys->fetch(PDO::FETCH_ASSOC)) {
      switch ($row['publishIn']) {
        case 1 :
          break;
        case 3 :
        case 7 :
          $nrOfEntites ++;
          if ($row['isIdP']) { $nrOfIdPs ++; }
          if ($row['isSP']) { $nrOfSPs ++; }
          if ( $row['errors'] <> '' ) {
            $errorsTotal ++;
            if ($row['isIdP']) { $errorsIdPs ++; }
            if ($row['isSP']) { $errorsSPs ++; }
          }
          if ($row['lastUpdated'] > '2021-12-31') { $changed ++; }
          break;
        default :
          printf ("Can't resolve publishIn = %d for enityID = %s", $row['publishIn'], $row['entityID']);
      }
    }
    $statsUpdate = $this->metaDb->prepare("INSERT INTO EntitiesStatus
      (`date`, `ErrorsTotal`, `ErrorsSPs`, `ErrorsIdPs`, `NrOfEntites`, `NrOfSPs`, `NrOfIdPs`, `Changed`)
      VALUES ('$date', $errorsTotal, $errorsSPs, $errorsIdPs, $nrOfEntites, $nrOfSPs, $nrOfIdPs, '$changed')");
    $statsUpdate->execute();
  }

  public function saveEntitiesStatistics($date = '') {
    if ($date == '') {
      $date = gmdate('Y-m-d');
    }
    $nrOfEntites = 0;
    $nrOfSPs = 0;
    $nrOfIdPs = 0;

    $entitys = $this->metaDb->prepare("SELECT `id`, `entityID`, `isIdP`, `isSP`, `publishIn`
      FROM Entities WHERE status = 1 AND publishIn > 2");
    $entitys->execute();
    while ($row = $entitys->fetch(PDO::FETCH_ASSOC)) {
      switch ($row['publishIn']) {
        case 1 :
          break;
        case 3 :
        case 7 :
          $nrOfEntites ++;
          if ($row['isIdP']) { $nrOfIdPs ++; }
          if ($row['isSP']) { $nrOfSPs ++; }
          break;
        default :
          printf ("Can't resolve publishIn = %d for enityID = %s", $row['publishIn'], $row['entityID']);
      }
    }
    $statsUpdate = $this->metaDb->prepare("INSERT INTO EntitiesStatistics
      (`date`, `NrOfEntites`, `NrOfSPs`, `NrOfIdPs`)
      VALUES ('$date', $nrOfEntites, $nrOfSPs, $nrOfIdPs)");
    $statsUpdate->execute();
  }
}
# vim:set ts=2
