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

  private $user = array ('id' => 0, 'email' => '', 'fullname' => '');

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

  private function saveResults() {
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
    $this->saveResults();
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
    $this->saveResults();
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
  # Return Warning
  #############
  public function getWarning() {
    return $this->warning;
  }

  #############
  # Return Error
  #############
  public function getError() {
    return $this->error . $this->errorNB;
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
