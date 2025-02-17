<?php
class Metadata {
  # Setup
  private $config;
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
  private $discoveryResponseFound = false;

  private $user = array ('id' => 0, 'email' => '', 'fullname' => '');

  private $swamid6116error;
  # From common.php
  private $FriendlyNames = array();
  private $digestMethods = array();
  private $signingMethods = array();
  private $encryptionMethods = array();

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
  const SAML_ATTRIBUTE_REMD = 'remd:contactType';
  const SAML_BINDING_HTTP_REDIRECT = 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect';
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
  const SAML_PROTOCOL_SAML1 = 'urn:oasis:names:tc:SAML:1.0:protocol';
  const SAML_PROTOCOL_SAML11 = 'urn:oasis:names:tc:SAML:1.1:protocol';
  const SAML_PROTOCOL_SAML2 = 'urn:oasis:names:tc:SAML:2.0:protocol';
  const SAML_PROTOCOL_SHIB = 'urn:mace:shibboleth:1.0';
  const SAML_PSC_REQUESTEDPRINCIPALSELECTION = 'psc:RequestedPrincipalSelection';
  const SAML_SHIBMD_SCOPE = 'shibmd:Scope';
  const SAML_SAMLA_ATTRIBUTE = 'samla:Attribute';
  const SAML_SAMLA_ATTRIBUTEVALUE = 'samla:AttributeValue';

  const SAMLNF_URI = 'urn:oasis:names:tc:SAML:2.0:attrname-format:uri';

  const TEXT_HTTP = 'http://';
  const TEXT_HTTPS = 'https://';
  const TEXT_521 = '5.2.1';
  const TEXT_621 = '6.2.1';
  const TEXT_COCOV2_REQ = 'GÉANT Data Protection Code of Conduct (v2) Require';

  public function __construct() {
    global $config;
    $a = func_get_args();
    $i = func_num_args();
    if (isset($config)) {
      $this->config = $config;
    } else {
      $this->config = new metadata\Configuration();
    }
    require __DIR__ . '/common.php'; #NOSONAR
    if (method_exists($this,$f='construct'.$i)) {
        call_user_func_array(array($this,$f),$a);
    }
  }

  private function construct1($id) { #NOSONAR is called from construct above
    $entityHandler = $this->config->getDb()->prepare('
      SELECT `id`, `entityID`, `isIdP`, `isSP`, `isAA`, `publishIn`, `status`, `xml`, `errors`, `errorsNB`, `warnings`
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
      $this->warning = $entity['warnings'];
      $this->error = $entity['errors'];
      $this->errorNB = $entity['errorsNB'];
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

    $entityHandler = $this->config->getDb()->prepare('
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
    $urlHandler = $this->config->getDb()->prepare('SELECT `type` FROM URLs WHERE `URL` = :URL');
    $urlHandler->bindValue(self::BIND_URL, $url);
    $urlHandler->execute();

    if ($currentType = $urlHandler->fetch(PDO::FETCH_ASSOC)) {
      if ($currentType['type'] < $type) {
        // Update type and lastSeen + force revalidate
        $urlUpdateHandler = $this->config->getDb()->prepare("
          UPDATE URLs SET `type` = :Type, `lastValidated` = '1972-01-01', `lastSeen` = NOW() WHERE `URL` = :URL;");
        $urlUpdateHandler->bindParam(self::BIND_URL, $url);
        $urlUpdateHandler->bindParam(self::BIND_TYPE, $type);
        $urlUpdateHandler->execute();
      } else {
        // Update lastSeen
        $urlUpdateHandler = $this->config->getDb()->prepare("UPDATE URLs SET `lastSeen` = NOW() WHERE `URL` = :URL;");
        $urlUpdateHandler->bindParam(self::BIND_URL, $url);
        $urlUpdateHandler->execute();
      }
    } else {
      $urlAddHandler = $this->config->getDb()->prepare("INSERT INTO URLs
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

    $urlUpdateHandler = $this->config->getDb()->prepare("UPDATE URLs
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
    $urlHandler = $this->config->getDb()->prepare($sql);
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
      $this->checkURLStatus($url['URL'], $verbose);
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
    $urlUpdateHandler = $this->config->getDb()->prepare("UPDATE URLs SET `lastValidated` = '1972-01-01' WHERE `URL` = :URL;");
    $urlUpdateHandler->bindParam(self::BIND_URL, $url);
    $urlUpdateHandler->execute();
    $this->validateURLs(5, $verbose);
  }

  public function checkOldURLS($age = 30, $verbose = false) {
    $sql = sprintf("SELECT URL, lastSeen from URLs where lastSeen < ADDTIME(NOW(), '-%d 0:0:0')", $age);
    $urlHandler = $this->config->getDb()->prepare($sql);
    $urlHandler->execute();
    while ($urlInfo = $urlHandler->fetch(PDO::FETCH_ASSOC)) {
      if ($verbose) { printf ("Checking : %s last seen %s\n", $urlInfo['URL'], $urlInfo['lastSeen']); }
      $this->checkURLStatus($urlInfo['URL'], $verbose);
    }
  }

  private function checkURLStatus($url, $verbose = false){
    $urlHandler = $this->config->getDb()->prepare('SELECT `type`, `validationOutput`, `lastValidated`
      FROM URLs WHERE `URL` = :URL');
    $urlHandler->bindValue(self::BIND_URL, $url);
    $urlHandler->execute();
    if ($urlInfo = $urlHandler->fetch(PDO::FETCH_ASSOC)) {
      $missing = true;
      $coCoV1 = false;
      $logo = false;
      $entityHandler = $this->config->getDb()->prepare('SELECT `entity_id`, `entityID`, `status`
        FROM EntityURLs, Entities WHERE entity_id = id AND `URL` = :URL AND `status` < 4');
      $entityHandler->bindValue(self::BIND_URL, $url);
      $entityHandler->execute();
      $ssoUIIHandler = $this->config->getDb()->prepare('SELECT `entity_id`, `type`, `element`, `lang`, `entityID`, `status`
        FROM `Mdui`, `Entities` WHERE `Mdui`.`entity_id` = `Entities`.`id` AND `data` = :URL AND `status`< 4');
      $ssoUIIHandler->bindValue(self::BIND_URL, $url);
      $ssoUIIHandler->execute();
      $organizationHandler = $this->config->getDb()->prepare('SELECT `entity_id`, `element`, `lang`, `entityID`, `status`
        FROM Organization, Entities WHERE entity_id = id AND `data` = :URL AND `status`< 4');
      $organizationHandler->bindValue(self::BIND_URL, $url);
      $organizationHandler->execute();
      $entityAttributesHandler = $this->config->getDb()->prepare("SELECT `attribute`
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
        $urlHandler = $this->config->getDb()->prepare('DELETE FROM URLs WHERE `URL` = :URL');
        $urlHandler->bindValue(self::BIND_URL, $url);
        $urlHandler->execute();
        if ($verbose) { print "Removing URL. Not in use any more\n"; }
      } elseif ($urlInfo['type'] > 2 && !$coCoV1 ) {
        if ($logo) {
          $urlHandler = $this->config->getDb()->prepare('UPDATE URLs SET `type` = 2 WHERE `URL` = :URL');
        } else {
          $urlHandler = $this->config->getDb()->prepare('UPDATE URLs SET `type` = 1 WHERE `URL` = :URL');
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
      $entityHandlerUpdate = $this->config->getDb()->prepare('UPDATE Entities
        SET `isIdP` = 0, `isSP` = 0, `isAA` = 0, `xml` = :Xml , `lastUpdated` = NOW()
        WHERE `entityID` = :Id AND `status` = :Status');
      $entityHandlerUpdate->bindValue(self::BIND_ID, $this->entityID);
      $entityHandlerUpdate->bindValue(self::BIND_STATUS, $this->status);
      $entityHandlerUpdate->bindValue(self::BIND_XML, $this->xml->saveXML());
      $entityHandlerUpdate->execute();
    } else {
      # Add new entity into database
      $entityHandlerInsert = $this->config->getDb()->prepare('INSERT INTO Entities
        (`entityID`, `isIdP`, `isSP`, `publishIn`, `status`, `xml`, `lastUpdated`)
        VALUES(:Id, 0, 0, 0, :Status, :Xml, NOW())');
      $entityHandlerInsert->bindValue(self::BIND_ID, $this->entityID);
      $entityHandlerInsert->bindValue(self::BIND_STATUS, $this->status);
      $entityHandlerInsert->bindValue(self::BIND_XML, $this->xml->saveXML());
      $entityHandlerInsert->execute();
      $this->dbIdNr = $this->config->getDb()->lastInsertId();
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
      $entityHandlerInsert = $this->config->getDb()->prepare('
        INSERT INTO Entities (`entityID`, `isIdP`, `isSP`, `isAA`, `publishIn`, `status`, `xml`, `lastUpdated`)
          VALUES(:Id, 0, 0, 0, 0, 3, :Xml, NOW())');
      $entityHandlerInsert->bindValue(self::BIND_ID, $this->entityID);
      $entityHandlerInsert->bindValue(self::BIND_XML, $this->xml->saveXML());
      $entityHandlerInsert->execute();
      $oldDbNr = $this->dbIdNr;
      $this->warning = '';
      $this->error = '';
      $this->errorNB = '';
      $this->result = '';
      $this->dbIdNr = $this->config->getDb()->lastInsertId();
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

    # Remove old ContactPersons / Organization from previous runs
    $this->config->getDb()->prepare('DELETE FROM EntityAttributes WHERE `entity_id` = :Id')->execute(
      array(self::BIND_ID => $this->dbIdNr));
    $this->config->getDb()->prepare('DELETE FROM Mdui WHERE `entity_id` = :Id')->execute(
      array(self::BIND_ID => $this->dbIdNr));
    $this->config->getDb()->prepare('DELETE FROM KeyInfo WHERE `entity_id` = :Id')->execute(
      array(self::BIND_ID => $this->dbIdNr));
    $this->config->getDb()->prepare('DELETE FROM AttributeConsumingService WHERE `entity_id` = :Id')->execute(
      array(self::BIND_ID => $this->dbIdNr));
    $this->config->getDb()->prepare('DELETE FROM AttributeConsumingService_Service WHERE `entity_id` = :Id')->execute(
      array(self::BIND_ID => $this->dbIdNr));
    $this->config->getDb()->prepare('DELETE FROM AttributeConsumingService_RequestedAttribute
      WHERE `entity_id` = :Id')->execute(array(self::BIND_ID => $this->dbIdNr));
    $this->config->getDb()->prepare('DELETE FROM Organization WHERE `entity_id` = :Id')->execute(
      array(self::BIND_ID => $this->dbIdNr));
    $this->config->getDb()->prepare('DELETE FROM ContactPerson WHERE `entity_id` = :Id')->execute(
      array(self::BIND_ID => $this->dbIdNr));
    $this->config->getDb()->prepare('DELETE FROM EntityURLs WHERE `entity_id` = :Id')->execute(
      array(self::BIND_ID => $this->dbIdNr));
    $this->config->getDb()->prepare('DELETE FROM Scopes WHERE `entity_id` = :Id')->execute(
      array(self::BIND_ID => $this->dbIdNr));
    $this->config->getDb()->prepare('UPDATE Entities SET `isIdP` = 0, `isSP` = 0, `isAA` = 0 WHERE `id` = :Id')->execute(
      array(self::BIND_ID => $this->dbIdNr));

    # https://docs.oasis-open.org/security/saml/v2.0/saml-metadata-2.0-os.pdf 2.4.1
    $entityDescriptor = $this->getEntityDescriptor($this->xml);
    $child = $entityDescriptor->firstChild;
    while ($child) {
      switch ($child->nodeName) {
        case self::SAML_MD_EXTENSIONS :
          $this->parseExtensions($child);
          $this->isIdP = false;
          $this->isSP = false;
          $this->isAA = false;
          break;
        case self::SAML_MD_IDPSSODESCRIPTOR :
          $this->config->getDb()->prepare('UPDATE Entities SET `isIdP` = 1 WHERE `id` = :Id')->execute(
            array(self::BIND_ID => $this->dbIdNr));
          $this->isIdP = true;
          $this->parseIDPSSODescriptor($child);
          break;
        case self::SAML_MD_SPSSODESCRIPTOR :
          $this->config->getDb()->prepare('UPDATE Entities SET `isSP` = 1 WHERE `id` = :Id')->execute(
            array(self::BIND_ID => $this->dbIdNr));
          $this->isSP = true;
          $this->parseSPSSODescriptor($child);
          break;
        #case self::SAML_MD_AUTHNAUTHORITYDESCRIPTOR :
        case self::SAML_MD_ATTRIBUTEAUTHORITYDESCRIPTOR :
          $this->config->getDb()->prepare('UPDATE Entities SET `isAA` = 1 WHERE `id` = :Id')->execute(
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

    $resultHandler = $this->config->getDb()->prepare("UPDATE Entities
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
          $this->validateDigestMethod($child);
          break;
        case self::SAML_ALG_SIGNINGMETHOD :
          $this->validateSigningMethod($child);
          break;
        case self::SAML_ALG_SIGNATUREMETHOD :
          # No signed metadata here
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
    $entityAttributesHandler = $this->config->getDb()->prepare('INSERT INTO EntityAttributes (`entity_id`, `type`, `attribute`)
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
        default :
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
        case 'https://refeds.org/entity-selection-profile' :
          $attributeType = 'entity-selection-profile';
          break;
        default :
          $this->result .= sprintf("Unknown Name (%s) in Extensions/EntityAttributes/Attribute.\n",
            $data->getAttribute('Name'));
          $attributeType = substr($data->getAttribute('Name'),0,30);
      }

      $entityAttributesHandler->bindValue(self::BIND_ID, $this->dbIdNr);
      $entityAttributesHandler->bindValue(self::BIND_TYPE, $attributeType);

      $child = $data->firstChild;
      while ($child) {
        if ($child->nodeName == self::SAML_SAMLA_ATTRIBUTEVALUE) {
          if ($attributeType == 'entity-selection-profile') {
            $profileList = 'No profiles found';
            if ($json_profile = base64_decode($child->textContent, true)) {
              if ($json_profile_array = json_decode($json_profile)) {
                if (isset($json_profile_array->profiles)) {
                  foreach($json_profile_array->profiles as $key => $profile) {
                    $profiles_array[$key] = $key;
                  }
                  if (count($profiles_array) >= 1) {
                    $profileList = implode (', ', $profiles_array);
                  }
                }
              } else {
                $profileList = 'Invalid format of json in profile';
              }
            } else {
              $profileList = 'Invalid BASE64 encoded profile';
            }
            $entityAttributesHandler->bindValue(self::BIND_VALUE, $profileList);
          } else {
            $entityAttributesHandler->bindValue(self::BIND_VALUE, substr(trim($child->textContent),0,256));
          }
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
    $saml2found = false;
    $saml1found = false;
    $shibboleth10found = false;
    $protocolSupportEnumeration = $data->getAttribute('protocolSupportEnumeration');
    foreach (explode(' ',$protocolSupportEnumeration) as $protocol) {
      switch ($protocol) {
        case self::SAML_PROTOCOL_SAML2 :
          $saml2found = true;
          break;
        case self::SAML_PROTOCOL_SAML1 :
        case self::SAML_PROTOCOL_SAML11 :
          $saml1found = true;
          break;
        case self::SAML_PROTOCOL_SHIB :
          $shibboleth10found = true;
          break;
        case '' :
          $this->result .= sprintf("Extra space found in protocolSupportEnumeration for SPSSODescriptor. Please remove.\n");
          break;
        default :
          $this->result .= sprintf("Protocol %s missing in validator for IDPSSODescriptor.\n", $protocol);
      }
    }
    if (! $saml2found) {
      $this->error .= "IDPSSODescriptor is missing support for SAML2.\n";
    } elseif ($saml1found) {
      $this->warning .= "IDPSSODescriptor claims support for SAML1. SWAMID is a SAML2 federation\n";
    }
    if ($shibboleth10found && ! $saml1found) {
      # https://shibboleth.atlassian.net/wiki/spaces/SP3/pages/2065334348/SSO#SAML1
      $this->error .= "IDPSSODescriptor claims support for urn:mace:shibboleth:1.0. This depends on SAML1, no support for SAML1 claimed.\n";
    }
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
          $this->checkSAMLEndpoint($child,'IDPSSO', $saml2found, $saml1found);
          break;
        case self::SAML_MD_NAMEIDFORMAT :
          $this->checkNameIDFormat($child->textContent);
          break;
        # 2.4.3
        case self::SAML_MD_SINGLESIGNONSERVICE :
        case self::SAML_MD_NAMEIDMAPPINGSERVICE :
        case self::SAML_MD_ASSERTIONIDREQUESTSERVICE :
          $this->checkSAMLEndpoint($child,'IDPSSO', $saml2found, $saml1found);
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
    $scopesHandler = $this->config->getDb()->prepare('INSERT INTO Scopes (`entity_id`, `scope`, `regexp`)
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
          $this->validateDigestMethod($child);
          break;
        case self::SAML_ALG_SIGNINGMETHOD :
          $this->validateSigningMethod($child);
          break;
        case self::SAML_ALG_SIGNATUREMETHOD :
          # No signed metadata here
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
    $ssoUIIHandler = $this->config->getDb()->prepare("INSERT INTO Mdui
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
    $saml2found = false;
    $saml1found = false;
    $protocolSupportEnumeration = $data->getAttribute('protocolSupportEnumeration');
    foreach (explode(' ',$protocolSupportEnumeration) as $protocol) {
      switch ($protocol) {
        case self::SAML_PROTOCOL_SAML2 :
          $saml2found = true;
          break;
        case self::SAML_PROTOCOL_SAML1 :
        case self::SAML_PROTOCOL_SAML11 :
          $saml1found = true;
          break;
        case self::SAML_PROTOCOL_SHIB :
          $this->errorNB .= sprintf("Protocol urn:mace:shibboleth:1.0 should only be used on IdP:s protocolSupportEnumeration, found in SPSSODescriptor.\n", $protocol);
          break;
        case '' :
          $this->warning .= sprintf("Extra space found in protocolSupportEnumeration for SPSSODescriptor. Please remove.\n");
          break;
        default :
          $this->result .= sprintf("Protocol %s missing in validator for SPSSODescriptor.\n", $protocol);
      }
    }
    if (! $saml2found) {
      $this->error .= "SPSSODescriptor is missing support for SAML2.\n";
    } elseif ($saml1found) {
      $this->errorNB .= "SPSSODescriptor claims support for SAML1. SWAMID is a SAML2 federation\n";
    }
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
          $this->checkSAMLEndpoint($child,'SPSSO', $saml2found, $saml1found);
          break;
        case self::SAML_MD_NAMEIDFORMAT :
          $this->checkNameIDFormat($child->textContent);
          break;
        # 2.4.4
        case self::SAML_MD_ASSERTIONCONSUMERSERVICE :
          $this->checkSAMLEndpoint($child,'SPSSO', $saml2found, $saml1found);
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
    if (! $this->discoveryResponseFound) {
      $this->warning .= sprintf("SeamlessAccess: No DiscoveryResponse registered. SeamlessAccess will show a warning message if not added. Please consider to add.\n");
    }
  }

  private function parseSPSSODescriptorExtensions($data) {
    $child = $data->firstChild;
    while ($child) {
      switch ($child->nodeName) {
        case self::SAML_IDPDISC_DISCOVERYRESPONSE :
          $this->discoveryResponseFound = true;
          break;
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

    $serviceHandler = $this->config->getDb()->prepare('INSERT INTO AttributeConsumingService
      (`entity_id`, `Service_index`, `isDefault`) VALUES (:Id, :Index, :Default)');
    $serviceElementHandler = $this->config->getDb()->prepare('INSERT INTO AttributeConsumingService_Service
      (`entity_id`, `Service_index`, `lang`, `element`, `data`) VALUES (:Id, :Index, :Lang, :Element, :Data)');
    $requestedAttributeHandler = $this->config->getDb()->prepare('INSERT INTO AttributeConsumingService_RequestedAttribute
      (`entity_id`, `Service_index`, `FriendlyName`, `Name`, `NameFormat`, `isRequired`)
      VALUES (:Id, :Index, :FriendlyName, :Name, :NameFormat, :IsRequired)');

    $serviceHandler->bindValue(self::BIND_ID, $this->dbIdNr);
    $serviceHandler->bindParam(self::BIND_INDEX, $index);
    $serviceHandler->bindValue(self::BIND_DEFAULT, $isDefault);
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

    try {
      $serviceHandler->execute();
    } catch(PDOException $e) {
      $this->error .= sprintf(
        "SPSSODescriptor->AttributeConsumingService[index=%d] is not Uniq!\n",
        $index);
    }

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
    $saml2found = false;
    $saml1found = false;
    $shibboleth10found = false;
    $protocolSupportEnumeration = $data->getAttribute('protocolSupportEnumeration');
    foreach (explode(' ',$protocolSupportEnumeration) as $protocol) {
      switch ($protocol) {
        case self::SAML_PROTOCOL_SAML2 :
          $saml2found = true;
          break;
        case self::SAML_PROTOCOL_SAML1 :
        case self::SAML_PROTOCOL_SAML11 :
          $saml1found = true;
          break;
        case self::SAML_PROTOCOL_SHIB :
          $shibboleth10found = true;
          break;
        case '' :
          $this->warning .= sprintf("Extra space found in protocolSupportEnumeration for AttributeAuthorityDescriptor. Please remove.\n");
          break;
        default :
          $this->result .= sprintf("Protocol %s missing in validator for AttributeAuthorityDescriptor.\n", $protocol);
      }
    }
    if (! $saml2found) {
      $this->error .= "AttributeAuthorityDescriptor is missing support for SAML2.\n";
    } elseif ($saml1found) {
      $this->warning .= "AttributeAuthorityDescriptor claims support for SAML1. SWAMID is a SAML2 federation\n";
    }
    if ($shibboleth10found && ! $saml1found) {
      # https://shibboleth.atlassian.net/wiki/spaces/SP3/pages/2065334348/SSO#SAML1
      $this->error .= "AttributeAuthorityDescriptor claims support for urn:mace:shibboleth:1.0. This depends on SAML1, no support for SAML1 claimed.\n";
    }
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
        case self::SAML_MD_ARTIFACTRESOLUTIONSERVICE :
        case self::SAML_MD_SINGLELOGOUTSERVICE :
        case self::SAML_MD_MANAGENAMEIDSERVICE :
          $this->checkSAMLEndpoint($child,'AttributeAuthority', $saml2found, $saml1found);
          break;
        # 2.4.7
        case self::SAML_MD_ATTRIBUTESERVICE :
        #case self::SAML_MD_ASSERTIONIDREQUESTSERVICE :
          $this->checkSAMLEndpoint($child,'AttributeAuthority', $saml2found, $saml1found);
          break;
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
    $organizationHandler = $this->config->getDb()->prepare('INSERT INTO Organization (`entity_id`, `lang`, `element`, `data`)
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

    $contactPersonHandler = $this->config->getDb()->prepare('INSERT INTO ContactPerson
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
        if ($data->getAttribute(self::SAML_ATTRIBUTE_REMD)) {
          if ($data->getAttribute(self::SAML_ATTRIBUTE_REMD) == 'http://refeds.org/metadata/contactType/security') { # NOSONAR Should be http://
            $subcontactType =  'security';
          } else {
            $subcontactType =  'unknown';
            $this->result .= sprintf("ContactPerson->Unknown subcontactType->%s.\n",
              $data->getAttribute(self::SAML_ATTRIBUTE_REMD));
          }
        } else {
          $this->warning .= sprintf("ContactPerson->other is NOT handled as a SecurityContact.\n");
        }
        break;
      default :
        $contactType = 'Unknown';
        $this->result .= sprintf("Unknown contactType in ContactPerson->%s.\n", $data->getAttribute('contactType'));
    }

    $child = $data->firstChild;
    while ($child) {
      $value = trim($child->textContent);
      switch ($child->nodeName) {
        case self::SAML_MD_EXTENSIONS :
          $extensions = $value;
          break;
        case self::SAML_MD_COMPANY :
          $company = $value;
          break;
        case self::SAML_MD_GIVENNAME :
          $givenName = $value;
          break;
        case self::SAML_MD_SURNAME :
          $surName = $value;
          break;
        case self::SAML_MD_EMAILADDRESS :
          $emailAddress = $value;
          break;
        case self::SAML_MD_TELEPHONENUMBER :
          $telephoneNumber = $value;
          break;
        default :
          $this->result .= sprintf("ContactPerson->%s missing in validator.\n", $child->nodeName);
      }
      if ($value == '') {
        $this->error .= sprintf ("Error in uploaded XML. Element %s in contact type=%s is empty!\n",
          $child->nodeName, $data->getAttribute('contactType'));
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
    $ssoUIIHandler = $this->config->getDb()->prepare('INSERT INTO Mdui
      (`entity_id`, `type`, `lang`, `height`, `width`, `element`, `data`)
      VALUES (:Id, :Type, :Lang, :Height, :Width, :Element, :Value)');
    $urlHandler = $this->config->getDb()->prepare('SELECT `nosize`, `height`, `width`, `status` FROM URLs WHERE `URL` = :URL');

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
        $value = trim($child->textContent);
        switch ($child->nodeName) {
          case self::SAML_MDUI_LOGO :
            $urltype = 2;
            $this->addURL($value, $urltype);
            $element = substr($child->nodeName, 5);
            $height = $child->getAttribute('height') ? $child->getAttribute('height') : 0;
            $width = $child->getAttribute('width') ? $child->getAttribute('width') : 0;
            $urlHandler->execute(array(self::BIND_URL => $value));
            if ($urlInfo = $urlHandler->fetch(PDO::FETCH_ASSOC)) {
              if ($urlInfo['height'] != $height && $urlInfo['status'] == 0 && $urlInfo['nosize'] == 0) {
                if ($urlInfo['height'] == 0) {
                  $this->error .= sprintf(
                    "Logo (%dx%d) lang=%s Image can not be loaded from URL.\n",
                    $height, $width, $lang, $height);
                } else {
                  $this->error .= sprintf(
                    "Logo (%dx%d) lang=%s is marked with height %s in metadata but actual height is %d.\n",
                    $height, $width, $lang, $height, $urlInfo['height']);
                }
              }
              if ($urlInfo['width'] != $width && $urlInfo['status'] == 0 && $urlInfo['nosize'] == 0 && $urlInfo['width'] > 0) {
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
            $this->addURL($value, $urltype);
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
          $this->validateEncryptionMethod($child, $type);
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
    $keyInfoHandler = $this->config->getDb()->prepare('INSERT INTO KeyInfo
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

  private function validateDigestMethod($data) {
    $algorithm = $data->getAttribute('Algorithm') ? $data->getAttribute('Algorithm') : 'Unknown';
    if (isset($this->digestMethods[$algorithm])) {
      switch ($this->digestMethods[$algorithm]) {
        case 'good' :
          break;
        case 'discouraged' :
          $this->warning .= $this->selectError('5.1.29', '6.1.28',
            sprintf("DigestMethod %s is discouraged in xmldsig-core.", $algorithm));
          break;
        case 'obsolete' :
          $this->error .= $this->selectError('5.1.29', '6.1.28',
            sprintf("DigestMethod %s is obsolete in xmldsig-core.", $algorithm));
          break;
        default :
          $this->result .= sprintf("Common.php digestMethod[%s] have unknown status (%s).\n", $algorithm, $this->digestMethods[$algorithm]);
      }
    } else {
      $this->result .= sprintf("Missing DigestMethod[%s].\n", $algorithm);
    }
  }
  private function validateSigningMethod($data) {
    $algorithm = $data->getAttribute('Algorithm') ? $data->getAttribute('Algorithm') : 'Unknown';
    if (isset($this->signingMethods[$algorithm])) {
      switch ($this->signingMethods[$algorithm]) {
        case 'good' :
          break;
        case 'discouraged' :
          $this->warning .= $this->selectError('5.1.29', '6.1.28',
            sprintf("SigningMethod %s is discouraged in xmldsig-core.", $algorithm));
          break;
        case 'obsolete' :
          $this->error .= $this->selectError('5.1.29', '6.1.28',
            sprintf("SigningMethod %s is obsolete in xmldsig-core.", $algorithm));
          break;
        default :
          $this->result .= sprintf("Common.php signingMethods[%s] have unknown status (%s).\n", $algorithm, $this->signingMethods[$algorithm]);
      }
    } else {
      $this->result .= sprintf("Missing SigningMethod[%s].\n", $algorithm);
    }
  }
  private function validateEncryptionMethod($data, $type) {
    $algorithm = $data->getAttribute('Algorithm') ? $data->getAttribute('Algorithm') : 'Unknown';
    if (isset($this->encryptionMethods[$algorithm])) {
      switch ($this->encryptionMethods[$algorithm]) {
        case 'good' :
          break;
        case 'discouraged' :
          $this->warning .= $this->selectError('5.1.29', '6.1.28',
            sprintf("EncryptionMethod %s is discouraged in xmlenc-core.", $algorithm));
          break;
        case 'obsolete' :
          $this->error .= $this->selectError('5.1.29', '6.1.28',
            sprintf("EncryptionMethod %s is obsolete in xmlenc-core.", $algorithm));
          break;
        default :
          $this->result .= sprintf("Common.php encryptionMethods[%s] have unknown status (%s).\n", $algorithm, $this->encryptionMethods[$algorithm]);
      }
    } else {
      $this->result .= sprintf("Missing EncryptionMethod[%s].\n", $algorithm);
    }
  }

  // 5.1.21 / 6.1.15
  private function checkSAMLEndpoint($data,$type, $saml2, $saml1) {
    $name = $data->nodeName;
    $binding = $data->getAttribute('Binding');
    $location =$data->getAttribute('Location');
    if (substr($location,0,8) <> self::TEXT_HTTPS) {
      $this->error .= sprintf(
        "SWAMID Tech %s: All SAML endpoints MUST start with https://. Problem in %sDescriptor->%s[Binding=%s].\n",
        $type == "IDPSSO" ? '5.1.21' : '6.1.15', $type, $name, $binding);
    }
    switch ($binding) {
      #https://groups.oasis-open.org/higherlogic/ws/public/download/3405/oasis-sstc-saml-bindings-1.1.pdf
      # 3.1.1
      case 'urn:oasis:names:tc:SAML:1.0:bindings:SOAP-binding' :
      #4.1.1 Browser/Artifact Profile of SAML1
      case 'urn:oasis:names:tc:SAML:1.0:profiles:artifact-01' :
      #4.1.2 Browser/POST Profile of SAML1
      case 'urn:oasis:names:tc:SAML:1.0:profiles:browser-post' :
        if (! $saml1) {
          $this->error .= sprintf(
            "oasis-sstc-saml-bindings-1.1: SAML1 Binding in %s[Binding=%s], but SAML1 not supported in %sDescriptor.\n",
            $name, $binding, $type);
        }
        break;
      # that's a SAML 1.1 identifier defined by the project in the old days
      # https://shibboleth.net/pipermail/users/2021-January/048837.html
      case 'urn:mace:shibboleth:1.0:profiles:AuthnRequest' :
        if (! $saml1) {
          $this->error .= sprintf(
            "urn:mace:shibboleth:1.0 is depending on SAML1. Found binding in %s[Binding=%s], but SAML1 not supported in %sDescriptor.\n",
            $name, $binding, $type);
        }
        break;
      # https://docs.oasis-open.org/security/saml/v2.0/saml-bindings-2.0-os.pdf
      # 3.2.1
      case 'urn:oasis:names:tc:SAML:2.0:bindings:SOAP' :
      # 3.3.1
      case 'urn:oasis:names:tc:SAML:2.0:bindings:PAOS' :
      # 3.4.1
      case self::SAML_BINDING_HTTP_REDIRECT :
      # 3.5.1
      case 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST' :
      # 3.6.1
      case 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact' :
      # 3.7.1
      case 'urn:oasis:names:tc:SAML:2.0:bindings:URI' :
      # https://docs.oasis-open.org/security/saml/Post2.0/sstc-saml-binding-simplesign-cd-02.html
      # 2.1
      case 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST-SimpleSign' :
        if (! $saml2) {
          $this->error .= sprintf(
            "saml-bindings-2.0-os: SAML2 Binding in %s[Binding=%s], but SAML2 not supported in %sDescriptor.\n",
            $name, $binding, $type);
        }
        break;
      case 'urn:oasis:names:tc:SAML:2.0:bindings:SOAP-binding' :
        $this->error .= sprintf("Binding : %s should be either urn:oasis:names:tc:SAML:2.0:bindings:<b>SOAP</b> or urn:oasis:names:tc:SAML:<b>1.0</b>:bindings:SOAP-binding\n", $binding);
        break;
      case 'urn:oasis:names:tc:SAML:2.0:bindings:artifact-01' :
        $this->error .= sprintf("Binding : %s should be either urn:oasis:names:tc:SAML:2.0:bindings:<b>HTTP-Artifact</b> or urn:oasis:names:tc:SAML:<b>1.0:profiles</b>:artifact-01\n", $binding);
        break;
      case 'urn:oasis:names:tc:SAML:2.0:bindings:browser-post' :
        $this->error .= sprintf("Binding : %s should be either urn:oasis:names:tc:SAML:2.0:bindings:<b>HTTP-POST</b> or urn:oasis:names:tc:SAML:<b>1.0:profiles</b>:browser-post\n", $binding);
        break;
      default :
        $this->result .= sprintf("Missing Binding : %s in validator\n", $binding);
    }
  }

  private function checkNameIDFormat($nameIDFormat) {
    # https://docs.oasis-open.org/security/saml/v2.0/saml-core-2.0-os.pdf
    switch ($nameIDFormat) {
      # SAML1 only (transient in SAML2)
      case 'urn:mace:shibboleth:1.0:nameIdentifier' :
      # 8.3.1
      case 'urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified' :
      # 8.3.2
      case 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress' :
      # 8.3.3
      case 'urn:oasis:names:tc:SAML:1.1:nameid-format:X509SubjectName' :
      # 8.3.4
      case 'urn:oasis:names:tc:SAML:1.1:nameid-format:WindowsDomainQualifiedName' :
      # 8.3.5
      case 'urn:oasis:names:tc:SAML:2.0:nameid-format:kerberos' :
      # 8.3.6
      case 'urn:oasis:names:tc:SAML:2.0:nameid-format:entity' :
      # 8.3.7
      case 'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent' :
      # 8.3.8
      case 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient' :
        break;
      default :
        $this->warning .= sprintf("Unknown NameIDFormat : %s. See saml-core-2.0-os below 8.3 for options.\n", $nameIDFormat);
    }
  }

  // 6.1.16
  private function checkAssertionConsumerService($data) {
    $binding = $data->getAttribute('Binding');
    if ($binding == self::SAML_BINDING_HTTP_REDIRECT) {
      $this->swamid6116error = true;
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
            && $subchild->getAttribute('Binding') == self::SAML_BINDING_HTTP_REDIRECT) {
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

  # Removed SAML1 support from an entity
  public function removeSaml1Support() {
    $entityDescriptor = $this->getEntityDescriptor($this->xml);
    $child = $entityDescriptor->firstChild;
    while ($child) {
      $checkProtocol = 0;
      switch ($child->nodeName) {
        case self::SAML_MD_IDPSSODESCRIPTOR :
          $checkProtocol = 1;
          break;
        case self::SAML_MD_SPSSODESCRIPTOR :
          $checkProtocol = 2;
          break;
        case self::SAML_MD_ATTRIBUTEAUTHORITYDESCRIPTOR :
          $checkProtocol = 1;
          break;
        default :
      }
      if ($checkProtocol) {
        $protocolSupportEnumerations = explode(' ',$child->getAttribute('protocolSupportEnumeration'));
        foreach ($protocolSupportEnumerations as $key => $protocol) {
          if ($protocol == self::SAML_PROTOCOL_SAML1 ||
                $protocol == self::SAML_PROTOCOL_SAML11 ||
                $protocol == self::SAML_PROTOCOL_SHIB ||
                $protocol == '') {
            unset($protocolSupportEnumerations[$key]);
            $this->result .= sprintf("Removed %s from %s\n", $protocol, $child->nodeName);
          }
        }
        if (count($protocolSupportEnumerations)){
          $child->setAttribute('protocolSupportEnumeration', implode(' ',$protocolSupportEnumerations));
          $subchild = $child->firstChild;
          while ($subchild) {
            switch ($subchild->nodeName) {
              # 2.4.1
              case self::SAML_MD_EXTENSIONS :
              case self::SAML_MD_KEYDESCRIPTOR :
              # 2.4.2
              case self::SAML_MD_NAMEIDFORMAT :
              # 2.4.3
              case self::SAML_SAMLA_ATTRIBUTE :
              # 2.4.4
              case self::SAML_MD_ATTRIBUTECONSUMINGSERVICE :
                $subchild = $subchild->nextSibling;
                break;

              # 2.4.2
              case self::SAML_MD_ARTIFACTRESOLUTIONSERVICE :
              case self::SAML_MD_SINGLELOGOUTSERVICE :
              case self::SAML_MD_MANAGENAMEIDSERVICE :
              # 2.4.3
              case self::SAML_MD_SINGLESIGNONSERVICE :
              case self::SAML_MD_NAMEIDMAPPINGSERVICE :
              case self::SAML_MD_ASSERTIONIDREQUESTSERVICE :
              # 2.4.4
              case self::SAML_MD_ASSERTIONCONSUMERSERVICE :
              # 2.4.7
              case self::SAML_MD_ATTRIBUTESERVICE :
                switch ($subchild->getAttribute('Binding')) {
                  #https://groups.oasis-open.org/higherlogic/ws/public/download/3405/oasis-sstc-saml-bindings-1.1.pdf
                  # 3.1.1
                  case 'urn:oasis:names:tc:SAML:1.0:bindings:SOAP-binding' :
                  #4.1.1 Browser/Artifact Profile of SAML1
                  case 'urn:oasis:names:tc:SAML:1.0:profiles:artifact-01' :
                  #4.1.2 Browser/POST Profile of SAML1
                  case 'urn:oasis:names:tc:SAML:1.0:profiles:browser-post' :
                  # https://shibboleth.atlassian.net/wiki/spaces/SP3/pages/2065334348/SSO#SAML1
                  # urn:mace:shibboleth:1.0 depends on SAML1
                  case 'urn:mace:shibboleth:1.0:profiles:AuthnRequest' :
                    $this->result .= sprintf ('Removing %s[%s] in %s<br>', $subchild->nodeName, $subchild->getAttribute('Binding'), $child->nodeName);
                    $remChild = $subchild;
                    $subchild = $subchild->nextSibling;
                    $child->removeChild($remChild);
                    break;
                  default :
                    $subchild = $subchild->nextSibling;
                }
                break;
              default :
                $subchild = $subchild->nextSibling;
            }
          }
          $child = $child->nextSibling;
        } else {
          $this->result .= sprintf("Removed %s since protocolSupportEnumeration was empty\n", $child->nodeName);
          $remChild = $child;
          $child = $child->nextSibling;
          $entityDescriptor->removeChild($remChild);
        }
      } else {
        $child = $child->nextSibling;
      }
    }
  }

  # Remove Obsolete Algorithms
  public function removeObsoleteAlgorithms() {
    $entityDescriptor = $this->getEntityDescriptor($this->xml);
    $child = $entityDescriptor->firstChild;
    while ($child) {
      switch ($child->nodeName) {
        case self::SAML_MD_EXTENSIONS :
          $this->removeObsoleteAlgorithmsExtensions($child);
          break;
        case self::SAML_MD_IDPSSODESCRIPTOR :
        case self::SAML_MD_SPSSODESCRIPTOR :
        case self::SAML_MD_ATTRIBUTEAUTHORITYDESCRIPTOR :
          $this->removeObsoleteAlgorithmsSSODescriptor($child);
          break;
        default :
      }
      $child = $child->nextSibling;
    }
  }
  private function removeObsoleteAlgorithmsExtensions($data) {
    $child = $data->firstChild;
    while ($child) {
      switch ($child->nodeName) {
        case self::SAML_ALG_DIGESTMETHOD :
          $algorithm = $child->getAttribute('Algorithm') ? $child->getAttribute('Algorithm') : 'Unknown';
          if (isset($this->digestMethods[$algorithm]) && $this->digestMethods[$algorithm] == 'obsolete' ) {
            $this->result .= sprintf ('Removing %s[%s] in %s<br>', $child->nodeName, $algorithm, $data->nodeName);
            $remChild = $child;
            $child = $child->nextSibling;
            $data->removeChild($remChild);
          } else {
            $child = $child->nextSibling;
          }
          break;
        case self::SAML_ALG_SIGNINGMETHOD :
          $algorithm = $child->getAttribute('Algorithm') ? $child->getAttribute('Algorithm') : 'Unknown';
          if (isset($this->signingMethods[$algorithm]) && $this->signingMethods[$algorithm] == 'obsolete' ) {
            $this->result .= sprintf ('Removing %s[%s] in %s<br>', $child->nodeName, $algorithm, $data->nodeName);
            $remChild = $child;
            $child = $child->nextSibling;
            $data->removeChild($remChild);
          } else {
            $child = $child->nextSibling;
          }
          break;
        default :
          $child = $child->nextSibling;
      }
    }
  }
  private function removeObsoleteAlgorithmsSSODescriptor($data) {
    $child = $data->firstChild;
    while ($child) {
      switch ($child->nodeName) {
        case self::SAML_MD_EXTENSIONS :
          $this->removeObsoleteAlgorithmsExtensions($child);
          break;
        case self::SAML_MD_KEYDESCRIPTOR :
          $childKeyDescriptor = $child->firstChild;
          while ($childKeyDescriptor) {
            if ($childKeyDescriptor->nodeName == self::SAML_MD_ENCRYPTIONMETHOD) {
              $algorithm = $childKeyDescriptor->getAttribute('Algorithm') ? $childKeyDescriptor->getAttribute('Algorithm') : 'Unknown';
              if (isset($this->encryptionMethods[$algorithm]) && $this->encryptionMethods[$algorithm] == 'obsolete' ) {
                $this->result .= sprintf ('Removing %s[%s] in %s->%s<br>', $childKeyDescriptor->nodeName, $algorithm, $data->nodeName, $child->nodeName);
                $remChild = $childKeyDescriptor;
                $childKeyDescriptor = $childKeyDescriptor->nextSibling;
                $child->removeChild($remChild);
              } else {
                $childKeyDescriptor = $childKeyDescriptor->nextSibling;
              }
            } else {
              $childKeyDescriptor = $childKeyDescriptor->nextSibling;
            }
          }
          break;
        default :
      }
      $child = $child->nextSibling;
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
    $urlHandler = $this->config->getDb()->prepare("INSERT INTO EntityURLs
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
    #2 = SWAMID
    #3 = eduGAIN
    $publishIn = 0;
    foreach (explode(' ', $feeds) as $feed ) {
      switch (strtolower($feed)) {
        case 'swamid' :
          $publishIn += 2;
          break;
        case 'edugain' :
          $publishIn += 4;
          break;
        default :
      }
    }
    $publishedHandler = $this->config->getDb()->prepare('UPDATE Entities SET `publishIn` = :PublishIn WHERE `id` = :Id');
    $publishedHandler->bindValue(self::BIND_ID, $this->dbIdNr);
    $publishedHandler->bindValue(self::BIND_PUBLISHIN, $publishIn);
    $publishedHandler->execute();
    $this->feedValue = $publishIn;
  }

  #############
  # Updates which feeds by value
  #############
  public function updateFeedByValue($publishIn) {
    $publishedHandler = $this->config->getDb()->prepare('UPDATE Entities SET `publishIn` = :PublishIn WHERE `id` = :Id');
    $publishedHandler->bindValue(self::BIND_ID, $this->dbIdNr);
    $publishedHandler->bindValue(self::BIND_PUBLISHIN, $publishIn);
    $publishedHandler->execute();
    $this->feedValue = $publishIn;
  }

  #############
  # Updates which user that is responsible for an entity
  #############
  public function updateResponsible($approvedBy) {
    $entityUserHandler = $this->config->getDb()->prepare('INSERT INTO EntityUser (`entity_id`, `user_id`, `approvedBy`, `lastChanged`) VALUES(:Entity_id, :User_id, :ApprovedBy, NOW()) ON DUPLICATE KEY UPDATE `lastChanged` = NOW()');
    $entityUserHandler->bindParam(self::BIND_ENTITY_ID, $this->dbIdNr);
    $entityUserHandler->bindParam(self::BIND_USER_ID, $this->user['id']);
    $entityUserHandler->bindParam(self::BIND_APPROVEDBY, $approvedBy);
    $entityUserHandler->execute();
  }

  #############
  # Copies which user that is responsible for an entity from another entity
  #############
  public function copyResponsible($otherEntity_id) {
    $entityUserHandler = $this->config->getDb()->prepare(
      'INSERT INTO EntityUser (`entity_id`, `user_id`, `approvedBy`, `lastChanged`)
      VALUES(:Entity_id, :User_id, :ApprovedBy, :LastChanged)
      ON DUPLICATE KEY UPDATE `lastChanged` = :LastChanged');
    $otherEntityUserHandler = $this->config->getDb()->prepare(
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
    $entityHandler = $this->config->getDb()->prepare('SELECT publishedId FROM Entities WHERE id = :Id');
    $entityHandler->bindParam(self::BIND_ID, $dbIdNr);
    $entityHandler->execute();
    if ($entity = $entityHandler->fetch(PDO::FETCH_ASSOC)) {
      if ($entity['publishedId'] > 0) {
        #Remove shadow first
        $this->removeEntityReal($entity['publishedId']);
      }
      # Remove data for this Entity
      $this->config->getDb()->beginTransaction();
      $this->config->getDb()->prepare('DELETE FROM `AccessRequests` WHERE `entity_id` = :Id')->execute(
        array(self::BIND_ID => $dbIdNr));
      $this->config->getDb()->prepare('DELETE FROM `AttributeConsumingService` WHERE `entity_id` = :Id')->execute(
        array(self::BIND_ID => $dbIdNr));
      $this->config->getDb()->prepare('DELETE FROM `AttributeConsumingService_RequestedAttribute`
        WHERE `entity_id` = :Id')->execute(array(self::BIND_ID => $dbIdNr));
      $this->config->getDb()->prepare('DELETE FROM `AttributeConsumingService_Service` WHERE `entity_id` = :Id')->execute(
        array(self::BIND_ID => $dbIdNr));
      $this->config->getDb()->prepare('DELETE FROM `ContactPerson` WHERE `entity_id` = :Id')->execute(
        array(self::BIND_ID => $dbIdNr));
      $this->config->getDb()->prepare('DELETE FROM `EntityAttributes` WHERE `entity_id` = :Id')->execute(
        array(self::BIND_ID => $dbIdNr));
      $this->config->getDb()->prepare('DELETE FROM `EntityConfirmation` WHERE `entity_id` = :Id')->execute(
        array(self::BIND_ID => $dbIdNr));
      $this->config->getDb()->prepare('DELETE FROM `EntityURLs` WHERE `entity_id` = :Id')->execute(array(self::BIND_ID => $dbIdNr));
      $this->config->getDb()->prepare('DELETE FROM `EntityUser` WHERE `entity_id` = :Id')->execute(array(self::BIND_ID => $dbIdNr));
      $this->config->getDb()->prepare('DELETE FROM `IdpIMPS` WHERE `entity_id` = :Id')->execute(array(self::BIND_ID => $dbIdNr));
      $this->config->getDb()->prepare('DELETE FROM `KeyInfo` WHERE `entity_id` = :Id')->execute(array(self::BIND_ID => $dbIdNr));
      $this->config->getDb()->prepare('DELETE FROM `MailReminders` WHERE `entity_id` = :Id')->execute(array(self::BIND_ID => $dbIdNr));
      $this->config->getDb()->prepare('DELETE FROM `Mdui` WHERE `entity_id` = :Id')->execute(array(self::BIND_ID => $dbIdNr));
      $this->config->getDb()->prepare('DELETE FROM `Organization` WHERE `entity_id` = :Id')->execute(
        array(self::BIND_ID => $dbIdNr));
      $this->config->getDb()->prepare('DELETE FROM `Scopes` WHERE `entity_id` = :Id')->execute(array(self::BIND_ID => $dbIdNr));
      $this->config->getDb()->prepare('DELETE FROM `Entities` WHERE `id` = :Id')->execute(array(self::BIND_ID => $dbIdNr));
      $this->config->getDb()->commit();
    }
  }

  #############
  # Check if an entity from pendingQueue exists with same XML in published
  #############
  public function checkPendingIfPublished() {
    $pendingHandler = $this->config->getDb()->prepare('SELECT `entityID`, `xml`, `lastUpdated`
      FROM Entities WHERE `status` = 2 AND `id` = :Id');
    $pendingHandler->bindParam(self::BIND_ID, $this->dbIdNr);
    $pendingHandler->execute();

    $publishedHandler = $this->config->getDb()->prepare('SELECT `xml`, `lastUpdated`
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
    $entityHandler = $this->config->getDb()->prepare('UPDATE Entities
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
      $publishedEntityHandler = $this->config->getDb()->prepare('SELECT `id`
        FROM Entities WHERE `status` = 1 AND `entityID` = :Id');
      # Get id of published version
      $publishedEntityHandler->bindParam(self::BIND_ID, $this->entityID);
      $publishedEntityHandler->execute();
      if ($publishedEntity = $publishedEntityHandler->fetch(PDO::FETCH_ASSOC)) {
        $entityHandler = $this->config->getDb()->prepare('SELECT `lastValidated` FROM Entities WHERE `id` = :Id');
        $entityUserHandler = $this->config->getDb()->prepare('SELECT `user_id`, `approvedBy`, `lastChanged`
          FROM EntityUser WHERE `entity_id` = :Entity_id ORDER BY `lastChanged`');
        $addEntityUserHandler = $this->config->getDb()->prepare('INSERT INTO EntityUser
          (`entity_id`, `user_id`, `approvedBy`, `lastChanged`)
          VALUES(:Entity_id, :User_id, :ApprovedBy, :LastChanged)
          ON DUPLICATE KEY
          UPDATE `lastChanged` =
            IF(lastChanged < VALUES(lastChanged), VALUES(lastChanged), lastChanged)');
        $updateEntityConfirmationHandler = $this->config->getDb()->prepare('INSERT INTO EntityConfirmation
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
      $entityUpdateHandler = $this->config->getDb()->prepare('UPDATE Entities
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
    $entityHandler = $this->config->getDb()->prepare('UPDATE Entities
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
    $registrationInfo = false;
    while ($child && ! $registrationInfo) {
      if ($child->nodeName == self::SAML_MDRPI_REGISTRATIONINFO) {
        $registrationInfo = $child;
      } else {
        $child = $child->nextSibling;
      }
    }
    if (! $registrationInfo) {
      # Add if missing
      $ts=date("Y-m-d\TH:i:s\Z");
      $entityDescriptor->setAttributeNS('http://www.w3.org/2000/xmlns/', # NOSONAR Should be http://
        'xmlns:mdrpi', 'urn:oasis:names:tc:SAML:metadata:rpi');
      $registrationInfo = $this->xml->createElement(self::SAML_MDRPI_REGISTRATIONINFO);
      $registrationInfo->setAttribute('registrationAuthority', 'http://www.swamid.se/'); # NOSONAR Should be http://
      $registrationInfo->setAttribute('registrationInstant', $ts);
      $extensions->appendChild($registrationInfo);
    }

    # Find samla:Attribute in XML
    $child = $registrationInfo->firstChild;
    $registrationPolicy = false;
    while ($child && ! $registrationPolicy) {
      if ($child->nodeName == 'mdrpi:RegistrationPolicy' && $child->getAttribute('xml:lang') == 'en') {
        $registrationPolicy = $child;
      } else {
        $child = $child->nextSibling;
      }
    }
    if (!$registrationPolicy) {
      $registrationPolicy = $this->xml->createElement('mdrpi:RegistrationPolicy', 'http://swamid.se/policy/mdrps'); # NOSONAR Should be http://
      $registrationPolicy->setAttribute('xml:lang', 'en');
      $registrationInfo->appendChild($registrationPolicy);
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
      $displayHandler = $this->config->getDb()->prepare(
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
    $contactHandler = $this->config->getDb()->prepare("SELECT DISTINCT emailAddress
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
    $entityConfirmHandler = $this->config->getDb()->prepare('INSERT INTO EntityConfirmation
      (`entity_id`, `user_id`, `lastConfirmed`)
      VALUES (:Id, :User_id, NOW())
      ON DUPLICATE KEY UPDATE  `user_id` = :User_id, `lastConfirmed` = NOW()');
    $entityConfirmHandler->bindParam(self::BIND_ID, $this->dbIdNr);
    $entityConfirmHandler->bindParam(self::BIND_USER_ID, $userId);
    $entityConfirmHandler->execute();
  }

  public function getUser($userID, $email = '', $fullName = '', $add = false) {
    if ($this->user['id'] == 0) {
      $userHandler = $this->config->getDb()->prepare('SELECT `id`, `email`, `fullName` FROM Users WHERE `userID` = :Id');
      $userHandler->bindValue(self::BIND_ID, strtolower($userID));
      $userHandler->execute();
      if ($this->user = $userHandler->fetch(PDO::FETCH_ASSOC)) {
        $lastSeenUserHandler = $this->config->getDb()->prepare('UPDATE Users
          SET `lastSeen` = NOW() WHERE `userID` = :Id');
        $lastSeenUserHandler->bindValue(self::BIND_ID, strtolower($userID));
        $lastSeenUserHandler->execute();
        if ($add && ($email <> $this->user['email'] || $fullName <>  $this->user['fullName'])) {
          $userHandler = $this->config->getDb()->prepare('UPDATE Users
            SET `email` = :Email, `fullName` = :FullName WHERE `userID` = :Id');
          $userHandler->bindValue(self::BIND_ID, strtolower($userID));
          $userHandler->bindParam(self::BIND_EMAIL, $email);
          $userHandler->bindParam(self::BIND_FULLNAME, $fullName);
          $userHandler->execute();
        }
      } elseif ($add) {
        $addNewUserHandler = $this->config->getDb()->prepare('INSERT INTO Users
          (`userID`, `email`, `fullName`, `lastSeen`) VALUES(:Id, :Email, :FullName, NOW())');
        $addNewUserHandler->bindValue(self::BIND_ID, strtolower($userID));
        $addNewUserHandler->bindParam(self::BIND_EMAIL, $email);
        $addNewUserHandler->bindParam(self::BIND_FULLNAME, $fullName);
        $addNewUserHandler->execute();
        $this->user['id'] = $this->config->getDb()->lastInsertId();
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
    $userHandler = $this->config->getDb()->prepare('UPDATE Users
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
      $userHandler = $this->config->getDb()->prepare('SELECT *
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
    $usersHandler = $this->config->getDb()->prepare('SELECT `id`, `userID`, `email`, `fullName`
      FROM EntityUser, Users WHERE `entity_id` = :Entity_id AND id = user_id ORDER BY `userID`;');
    $usersHandler->bindParam(self::BIND_ENTITY_ID, $this->dbIdNr);
    $usersHandler->execute();
    return $usersHandler->fetchAll(PDO::FETCH_ASSOC);
  }

  public function createAccessRequest($userId) {
    $hash = hash_hmac('md5',$this->entityID(),time());
    $code = base64_encode(sprintf ('%d:%d:%s', $this->dbIdNr, $userId, $hash));
    $addNewRequestHandler = $this->config->getDb()->prepare('INSERT INTO `AccessRequests`
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
      $userHandler = $this->config->getDb()->prepare('SELECT *
        FROM EntityUser WHERE `user_id` = :User_id AND `entity_id`= :EntityID' );
      $userHandler->bindParam(self::BIND_USER_ID, $userId);
      $userHandler->bindParam(self::BIND_ENTITYID, $this->dbIdNr);
      $userHandler->execute();
      if ($userHandler->fetch(PDO::FETCH_ASSOC)) {
        $result = array('returnCode' => 1, 'info' => 'User already had access');
      } else {
        $requestHandler = $this->config->getDb()->prepare('SELECT `requestDate`, NOW() - INTERVAL 1 DAY AS `limit`,
          `email`, `fullName`, `entityID`
          FROM `AccessRequests`, `Users`, `Entities`
          WHERE Users.`id` = `user_id`
            AND `Entities`.`id` = `entity_id`
            AND `entity_id` =  :Entity_id
            AND `user_id` = :User_id
            AND `hash` = :Hashvalue');
        $requestRemoveHandler = $this->config->getDb()->prepare('DELETE FROM `AccessRequests`
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
    $entityUserHandler = $this->config->getDb()->prepare('INSERT INTO EntityUser
      (`entity_id`, `user_id`, `approvedBy`, `lastChanged`)
      VALUES(:Entity_id, :User_id, :ApprovedBy, NOW())
      ON DUPLICATE KEY UPDATE `lastChanged` = NOW()');
    $entityUserHandler->bindParam(self::BIND_ENTITY_ID, $this->dbIdNr);
    $entityUserHandler->bindParam(self::BIND_USER_ID, $userId);
    $entityUserHandler->bindParam(self::BIND_APPROVEDBY, $approvedBy);
    $entityUserHandler->execute();
  }

  public function removeAccessFromEntity($userId) {
    $entityUserHandler = $this->config->getDb()->prepare('DELETE FROM EntityUser
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
    $nrOfEntities = 0;
    $nrOfSPs = 0;
    $nrOfIdPs = 0;
    $changed = 0;
    $entitys = $this->config->getDb()->prepare("SELECT `id`, `entityID`, `isIdP`, `isSP`, `publishIn`, `lastUpdated`, `errors`
      FROM Entities WHERE status = 1 AND publishIn > 1");
    $entitys->execute();
    while ($row = $entitys->fetch(PDO::FETCH_ASSOC)) {
      switch ($row['publishIn']) {
        case 1 :
          break;
        case 2 :
        case 3 :
        case 6 :
        case 7 :
          $nrOfEntities ++;
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
    $statsUpdate = $this->config->getDb()->prepare("INSERT INTO EntitiesStatus
      (`date`, `ErrorsTotal`, `ErrorsSPs`, `ErrorsIdPs`, `NrOfEntites`, `NrOfSPs`, `NrOfIdPs`, `Changed`)
      VALUES ('$date', $errorsTotal, $errorsSPs, $errorsIdPs, $nrOfEntities, $nrOfSPs, $nrOfIdPs, '$changed')");
    $statsUpdate->execute();
  }

  public function saveEntitiesStatistics($date = '') {
    if ($date == '') {
      $date = gmdate('Y-m-d');
    }
    $nrOfEntities = 0;
    $nrOfSPs = 0;
    $nrOfIdPs = 0;

    $entitys = $this->config->getDb()->prepare("SELECT `id`, `entityID`, `isIdP`, `isSP`, `publishIn`
      FROM Entities WHERE status = 1 AND publishIn > 1");
    $entitys->execute();
    while ($row = $entitys->fetch(PDO::FETCH_ASSOC)) {
      switch ($row['publishIn']) {
        case 1 :
          break;
        case 2 :
        case 3 :
        case 6 :
        case 7 :
          $nrOfEntities ++;
          if ($row['isIdP']) { $nrOfIdPs ++; }
          if ($row['isSP']) { $nrOfSPs ++; }
          break;
        default :
          printf ("Can't resolve publishIn = %d for enityID = %s", $row['publishIn'], $row['entityID']);
      }
    }
    $statsUpdate = $this->config->getDb()->prepare("INSERT INTO EntitiesStatistics
      (`date`, `NrOfEntites`, `NrOfSPs`, `NrOfIdPs`)
      VALUES ('$date', $nrOfEntities, $nrOfSPs, $nrOfIdPs)");
    $statsUpdate->execute();
  }
}
# vim:set ts=2
