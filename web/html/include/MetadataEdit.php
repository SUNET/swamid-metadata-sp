<?php
class MetadataEdit {
  # Setup
  const SAML_DS_SIGNATURE = 'ds:Signature';
  const SAML_MD_ADDITIONALMETADATALOCATION = 'md:AdditionalMetadataLocation';
  const SAML_MD_AFFILIATIONDESCRIPTOR = 'md:AffiliationDescriptor';
  const SAML_MD_ARTIFACTRESOLUTIONSERVICE = 'md:ArtifactResolutionService';
  const SAML_MD_ASSERTIONCONSUMERSERVICE = 'md:AssertionConsumerService';
  const SAML_MD_ATTRIBUTEAUTHORITYDESCRIPTOR = 'md:AttributeAuthorityDescriptor';
  const SAML_MD_ATTRIBUTECONSUMINGSERVICE = 'md:AttributeConsumingService';
  const SAML_MD_AUTHNAUTHORITYDESCRIPTOR = 'md:AuthnAuthorityDescriptor';
  const SAML_MD_COMPANY = 'md:Company';
  const SAML_MD_CONTACTPERSON = 'md:ContactPerson';
  const SAML_MD_EMAILADDRESS = 'md:EmailAddress';
  const SAML_MD_EXTENSIONS = 'md:Extensions';
  const SAML_MD_GIVENNAME = 'md:GivenName';
  const SAML_MD_IDPSSODESCRIPTOR = 'md:IDPSSODescriptor';
  const SAML_MD_KEYDESCRIPTOR = 'md:KeyDescriptor';
  const SAML_MD_MANAGENAMEIDSERVICE = 'md:ManageNameIDService';
  const SAML_MD_NAMEIDFORMAT = 'md:NameIDFormat';
  const SAML_MD_ORGANIZATION = 'md:Organization';
  const SAML_MD_ORGANIZATIONDISPLAYNAME = 'md:OrganizationDisplayName';
  const SAML_MD_ORGANIZATIONNAME = 'md:OrganizationName';
  const SAML_MD_ORGANIZATIONURL = 'md:OrganizationURL';
  const SAML_MD_PDPDESCRIPTOR = 'md:PDPDescriptor';
  const SAML_MD_REQUESTEDATTRIBUTE = 'md:RequestedAttribute';
  const SAML_MD_ROLEDESCRIPTOR = 'md:RoleDescriptor';
  const SAML_MD_SERVICEDESCRIPTION = 'md:ServiceDescription';
  const SAML_MD_SERVICENAME = 'md:ServiceName';
  const SAML_MD_SIGNATURE = 'md:Signature';
  const SAML_MD_SINGLELOGOUTSERVICE = 'md:SingleLogoutService';
  const SAML_MD_SPSSODESCRIPTOR = 'md:SPSSODescriptor';
  const SAML_MD_SURNAME = 'md:SurName';
  const SAML_MD_TELEPHONENUMBER = 'md:TelephoneNumber';
  const SAML_MDATTR_ENTITYATTRIBUTES = 'mdattr:EntityAttributes';
  const SAML_MDRPI_REGISTRATIONINFO = 'mdrpi:RegistrationInfo';
  const SAML_MDUI_DISCOHINTS = 'mdui:DiscoHints';
  const SAML_MDUI_DOMAINHINT = 'mdui:DomainHint';
  const SAML_MDUI_INFORMATIONURL = 'mdui:InformationURL';
  const SAML_MDUI_LOGO = 'mdui:Logo';
  const SAML_MDUI_PRIVACYSTATEMENTURL = 'mdui:PrivacyStatementURL';
  const SAML_MDUI_UIINFO = 'mdui:UIInfo';
  const SAML_SHIBMD_SCOPE = 'shibmd:Scope';
  const SAML_SAMLA_ATTRIBUTE = 'samla:Attribute';
  const SAML_SAMLA_ATTRIBUTEVALUE = 'samla:AttributeValue';

  const SAMLNF_URI = 'urn:oasis:names:tc:SAML:2.0:attrname-format:uri';
  const SAMLXMLNS_URI = 'http://www.w3.org/2000/xmlns/';
  const SAMLXMLNS_DS = 'xmlns:ds';
  const SAMLXMLNS_DS_URL = 'http://www.w3.org/2000/09/xmldsig#';
  const SAMLXMLNS_MDUI = 'xmlns:mdui';
  const SAMLXMLNS_MDUI_URL = 'urn:oasis:names:tc:SAML:metadata:ui';

  const BIND_ATTRIBUTE = ':Attribute';
  const BIND_BITS = ':Bits';
  const BIND_CONTACTTYPE = ':ContactType';
  const BIND_DATA = ':Data';
  const BIND_ELEMENT = ':Element';
  const BIND_EMAIL = ':Email';
  const BIND_FRIENDLYNAME = ':FriendlyName';
  const BIND_FULLNAME = ':FullName';
  const BIND_HEIGHT = ':Height';
  const BIND_ID = ':Id';
  const BIND_INDEX = ':Index';
  const BIND_ISREQUIRED = ':IsRequired';
  const BIND_ISSUER = ':Issuer';
  const BIND_KEY_TYPE = ':Key_type';
  const BIND_LANG = ':Lang';
  const BIND_NAME = ':Name';
  const BIND_NAMEFORMAT = ':NameFormat';
  const BIND_NEWORDER = ':NewOrder';
  const BIND_NEWUSE = ':NewUse';
  const BIND_NOTVALIDAFTER = ':NotValidAfter';
  const BIND_OLDORDER = ':OldOrder';
  const BIND_ORDER = ':Order';
  const BIND_REGEXP = ':Regexp';
  const BIND_SCOPE = ':Scope';
  const BIND_SERIALNUMBER = ':SerialNumber';
  const BIND_SUBJECT = ':Subject';
  const BIND_TYPE = ':Type';
  const BIND_URL = ':URL';
  const BIND_USE = ':Use';
  const BIND_VALUE = ':Value';
  const BIND_WIDTH = ':Width';
  const BIND_XML = ':Xml';

  const HTML_CLASS_ALERT_WARNING = ' class="alert-warning" role="alert"';
  const HTML_CLASS_ALERT_DANGER = ' class="alert-danger" role="alert"';

  const HTML_END_UL = '        </ul>';

  const HTML_DIV_CLASS_ALERT_DANGER = '<div class="row alert alert-danger" role="alert">Error:%s</div>';

  const HTML_COPY = 'Copy"><i class="fas fa-pencil-alt"></i></a> ';
  const HTML_DELETE = 'Delete"><i class="fas fa-trash"></i></a> ';
  const HTML_HREF_BLANK = '<a href="%s" class="text-%s" target="blank">%s</a>';

  public function __construct($newID, $oldID = 0) {
    require __DIR__ . '/../config.php'; #NOSONAR
    require __DIR__ . '/common.php'; #NOSONAR
    try {
      $this->metaDb = new PDO("mysql:host=$dbServername;dbname=$dbName", $dbUsername, $dbPassword);
      // set the PDO error mode to exception
      $this->metaDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch(PDOException $e) {
      echo "Error: " . $e->getMessage();
    }
    $this->dbIdNr = is_numeric($newID) ? $newID : 0;
    $this->dbOldIdNr = is_numeric($oldID) ? $oldID : 0;
    $this->oldExists = false;

    $this->orderAttributeRequestingService = array (self::SAML_MD_SERVICENAME => 1,
      self::SAML_MD_SERVICEDESCRIPTION => 2,
      self::SAML_MD_REQUESTEDATTRIBUTE => 3);

    $this->orderOrganization = array (self::SAML_MD_EXTENSIONS => 1,
      self::SAML_MD_ORGANIZATIONNAME => 2,
      self::SAML_MD_ORGANIZATIONDISPLAYNAME => 3,
      self::SAML_MD_ORGANIZATIONURL => 4);

    $this->orderContactPerson = array (self::SAML_MD_COMPANY => 1,
      self::SAML_MD_GIVENNAME => 2,
      self::SAML_MD_SURNAME => 3,
      self::SAML_MD_EMAILADDRESS => 4,
      self::SAML_MD_TELEPHONENUMBER => 5,
      self::SAML_MD_EXTENSIONS => 6);

    $entityHandler = $this->metaDb->prepare('SELECT entityID, isIdP, isSP, status, xml FROM Entities WHERE id = :Id;');
    $entityHandler->bindValue(self::BIND_ID, $newID);
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
      $this->isSP = $entity['isSP'];
    } else {
      $this->entityExists = false;
      $this->entityID = 'Unknown';
      $this->isIdP = false;
      $this->isSP = false;
    }
    if ($this->entityExists && $oldID > 0) {
      $entityHandler->bindValue(self::BIND_ID, $oldID);
      $entityHandler->execute();
      if ($entity = $entityHandler->fetch(PDO::FETCH_ASSOC)) {
        $this->oldXml = DOMDocument::loadXML($entity['xml']);
        $this->oldentityID = $entity['entityID'];
        $this->oldExists = true;
      }
    }
  }

  public function edit($part) {
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
      case 'AAKeyInfo' :
        $this->editKeyInfo('AttributeAuthority');
        break;
      case 'AddIdPKeyInfo' :
        $this->addKeyInfo('IDPSSO');
        break;
      case 'AddSPKeyInfo' :
        $this->addKeyInfo('SPSSO');
        break;
      case 'AddAAKeyInfo' :
        $this->addKeyInfo('AttributeAuthority');
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
        printf('Missing what to edit');
    }
  }

  private function editEntityAttributes() {
    $entityAttributesHandler = $this->metaDb->prepare(
      'SELECT type, attribute FROM EntityAttributes WHERE entity_id = :Id ORDER BY type, attribute;');

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
          printf ('Missing type (%s)', urlencode($_GET['type']));
          exit;
      }
      $entityDescriptor = $this->getEntityDescriptor($this->newXml);

      # Find md:Extensions in XML
      $child = $entityDescriptor->firstChild;
      $extensions = false;
      while ($child && ! $extensions) {
        switch ($child->nodeName) {
          case self::SAML_MD_EXTENSIONS :
            $extensions = $child;
            break;
          case self::SAML_MD_ROLEDESCRIPTOR :
          case self::SAML_MD_SPSSODESCRIPTOR :
          case self::SAML_MD_IDPSSODESCRIPTOR :
          case self::SAML_MD_AUTHNAUTHORITYDESCRIPTOR :
          case self::SAML_MD_ATTRIBUTEAUTHORITYDESCRIPTOR :
          case self::SAML_MD_PDPDESCRIPTOR :
          case self::SAML_MD_AFFILIATIONDESCRIPTOR :
          case self::SAML_MD_ORGANIZATION :
          case self::SAML_MD_CONTACTPERSON :
          case self::SAML_MD_ADDITIONALMETADATALOCATION :
            $extensions = $this->newXml->createElement(self::SAML_MD_EXTENSIONS);
            $entityDescriptor->insertBefore($extensions, $child);
            break;
          default :
        }
        $child = $child->nextSibling;
      }

      switch ($_GET['action']) {
        case 'Add' :
          if (! $extensions) {
            # Add if missing
            $extensions = $this->newXml->createElement(self::SAML_MD_EXTENSIONS);
            $entityDescriptor->appendChild($extensions);
          }

          # Find mdattr:EntityAttributes in XML
          $child = $extensions->firstChild;
          $entityAttributes = false;
          while ($child && ! $entityAttributes) {
            if ($child->nodeName == self::SAML_MDATTR_ENTITYATTRIBUTES) {
              $entityAttributes = $child;
            } else {
              $child = $child->nextSibling;
            }
          }
          if (! $entityAttributes) {
            # Add if missing
            $entityDescriptor->setAttributeNS(
              self::SAMLXMLNS_URI, 'xmlns:mdattr', 'urn:oasis:names:tc:SAML:metadata:attribute');
            $entityAttributes = $this->newXml->createElement(self::SAML_MDATTR_ENTITYATTRIBUTES);
            $extensions->appendChild($entityAttributes);
          }

          # Find samla:Attribute in XML
          $child = $entityAttributes->firstChild;
          $attribute = false;
          while ($child && ! $attribute) {
            if ($child->getAttribute('Name') == $attributeType) {
              $attribute = $child;
            } else {
              $child = $child->nextSibling;
            }
          }
          if (! $attribute) {
            # Add if missing
            $entityDescriptor->setAttributeNS(
              self::SAMLXMLNS_URI, 'xmlns:samla', 'urn:oasis:names:tc:SAML:2.0:assertion');
            $attribute = $this->newXml->createElement(self::SAML_SAMLA_ATTRIBUTE);
            $attribute->setAttribute('Name', $attributeType);
            $attribute->setAttribute('NameFormat', self::SAMLNF_URI);
            $entityAttributes->appendChild($attribute);
          }

          # Find samla:AttributeValue in XML
          $child = $attribute->firstChild;
          $attributeValue = false;
          while ($child && ! $attributeValue) {
            if ($child->nodeValue == trim($_GET['attribute'])) {
              $attributeValue = $child;
            } else {
              $child = $child->nextSibling;
            }
          }
          if (! $attributeValue) {
            # Add if missing
            $attributeValue = $this->newXml->createElement(self::SAML_SAMLA_ATTRIBUTEVALUE);
            $attributeValue->nodeValue = trim($_GET['attribute']);
            $attribute->appendChild($attributeValue);

            $entityAttributesAddHandler = $this->metaDb->prepare(
              'INSERT INTO EntityAttributes (entity_id, type, attribute) VALUES (:Id, :Type, :Attribute) ;');
            $entityAttributesAddHandler->bindParam(self::BIND_ID, $this->dbIdNr);
            $entityAttributesAddHandler->bindParam(self::BIND_TYPE, $_GET['type']);
            $entityAttributesAddHandler->bindValue(self::BIND_ATTRIBUTE, trim($_GET['attribute']));
            $entityAttributesAddHandler->execute();
            $this->saveXML();
          }
          break;
        case 'Delete' :
          if ($extensions) {
            # Find mdattr:EntityAttributes in XML
            $child = $extensions->firstChild;
            $entityAttributes = false;
            while ($child && ! $entityAttributes) {
              if ($child->nodeName == self::SAML_MDATTR_ENTITYATTRIBUTES) {
                $entityAttributes = $child;
              }
              $child = $child->nextSibling;
            }
            if ($entityAttributes) {
              # Find samla:Attribute in XML
              $child = $entityAttributes->firstChild;
              $attribute = false;
              $moreAttributes = false;
              while ($child && ! $attribute) {
                if ($child->getAttribute('Name') == $attributeType) {
                  $attribute = $child;
                }
                $child = $child->nextSibling;
                $moreAttributes = ($moreAttributes) ? true : $child;
              }
              if ($attribute) {
                # Find samla:Attribute in XML
                $child = $attribute->firstChild;
                $attributeValue = false;
                $moreAttributeValues = false;
                while ($child && ! $attributeValue) {
                  if ($child->nodeValue == $_GET['attribute']) {
                    $attributeValue = $child;
                  }
                  $child = $child->nextSibling;
                  $moreAttributeValues = ($moreAttributeValues) ? true : $child;
                }
                if ($attributeValue) {
                  $attribute->removeChild($attributeValue);
                  if (! $moreAttributeValues) {
                    $entityAttributes->removeChild($attribute);
                    if (! $moreAttributes) {
                      $extensions->removeChild($entityAttributes);
                    }
                  }
                  $entityAttributesRemoveHandler = $this->metaDb->prepare(
                    'DELETE FROM EntityAttributes WHERE entity_id=:Id AND type=:Type AND attribute=:Attribute;');
                  $entityAttributesRemoveHandler->bindParam(self::BIND_ID, $this->dbIdNr);
                  $entityAttributesRemoveHandler->bindParam(self::BIND_TYPE, $_GET['type']);
                  $entityAttributesRemoveHandler->bindParam(self::BIND_ATTRIBUTE, $_GET['attribute']);
                  $entityAttributesRemoveHandler->execute();
                  $this->saveXML();
                }
              }
            }
          }
          break;
        default :
      }
    }
    print "\n";
    print '    <div class="row">' . "\n" . '      <div class="col">' . "\n";

    $oldAttributeValues = array();
    $entityAttributesHandler->bindParam(self::BIND_ID, $this->dbOldIdNr);
    $entityAttributesHandler->execute();
    while ($attribute = $entityAttributesHandler->fetch(PDO::FETCH_ASSOC)) {
      $type = $attribute['type'];
      $value = $attribute['attribute'];
      if (! isset($oldAttributeValues[$type])) {
        $oldAttributeValues[$type] = array();
      }
      $oldAttributeValues[$type][$value] = true;
    }

    $existingAttributeValues = array();
    $entityAttributesHandler->bindParam(self::BIND_ID, $this->dbIdNr);
    $entityAttributesHandler->execute();
    if ($attribute = $entityAttributesHandler->fetch(PDO::FETCH_ASSOC)) {
      $type = $attribute['type'];
      $value = $attribute['attribute'];
      $existingAttributeValues[$type] = array();
      $existingAttributeValues[$type][$value] = true;
      $state = isset($oldAttributeValues[$type][$value]) ? 'dark' : 'success';
      $error = self::HTML_CLASS_ALERT_WARNING;
      $entityType = '?';
      if (isset($this->standardAttributes[$type])) {
        foreach ($this->standardAttributes[$type] as $data) {
          if ($data['value'] == $value) {
            $error = ($data['swamidStd']) ? '' : self::HTML_CLASS_ALERT_DANGER;
            $entityType = $data['type'];
          }
        }
      }
      printf('
        <b>%s</b>
        <ul>
          <li>
            <div%s>
              <span class="text-%s">%s</span>
              (%s)
              <a href="?edit=EntityAttributes&Entity=%d&oldEntity=%d&type=%s&attribute=%s&action=Delete">
                <i class="fas fa-trash"></i>
              </a>
            </div>
          </li>',
        $type, $error, $state, $value, $entityType, $this->dbIdNr, $this->dbOldIdNr, $type, $value
      );
      $oldType = $type;
      while ($attribute = $entityAttributesHandler->fetch(PDO::FETCH_ASSOC)) {
        $type = $attribute['type'];
        $value = $attribute['attribute'];
        if (isset($oldAttributeValues[$type][$value])) {
          $state = 'dark';
        } else {
          $state = 'success';
        }
        $error = self::HTML_CLASS_ALERT_WARNING;
        $entityType = '?';
        if (isset($this->standardAttributes[$type])) {
          foreach ($this->standardAttributes[$type] as $data) {
            if ($data['value'] == $value) {
              $error = ($data['swamidStd']) ? '' : self::HTML_CLASS_ALERT_DANGER;
              $entityType = $data['type'];
            }
          }
        }
        if ($oldType != $type) {
          printf ("\n%s\n        <b>%s</b>\n        <ul>", self::HTML_END_UL, $type);
          $oldType = $type;
          if (! isset($existingAttributeValues[$type]) ) {
            $existingAttributeValues[$type] = array();
          }
        }
        printf ('
          <li>
            <div%s>
              <span class="text-%s">%s</span>
              (%s)
              <a href="?edit=EntityAttributes&Entity=%d&oldEntity=%d&type=%s&attribute=%s&action=Delete">
                <i class="fas fa-trash"></i>
              </a>
            </div>
          </li>',
          $error, $state, $value, $entityType, $this->dbIdNr, $this->dbOldIdNr, $type, $value);
        $existingAttributeValues[$type][$value] = true;
      }
      printf("\n%s\n", self::HTML_END_UL);
    }
    print '        <hr>
        Quick-links
        <ul>';
    foreach ($this->standardAttributes as $type => $values) {
      printf ('%s          <li>%s</li><ul>', "\n", $type);
      foreach ($values as $data) {
        $entityType = $data['type'];
        if (
          ($entityType == 'IdP/SP' || ($entityType == 'IdP' && $this->isIdP) || ($entityType == 'SP' && $this->isSP))
          && $data['swamidStd']) {
          $value = $data['value'];
          if (isset($existingAttributeValues[$type]) && isset($existingAttributeValues[$type][$value])) {
            printf ('%s            <li>%s</li>', "\n", $value);
          } else {
            printf ('
            <li>
              <a href="?edit=EntityAttributes&Entity=%d&oldEntity=%d&type=%s&attribute=%s&action=Add">[copy]<a> %s
            </li>', $this->dbIdNr, $this->dbOldIdNr, $type, $value, $value);
          }
        }
      }
      printf ('%s  %s</li>', "\n", self::HTML_END_UL);
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

    $entityAttributesHandler->bindParam(self::BIND_ID, $this->dbOldIdNr);
    $entityAttributesHandler->execute();

    if ($attribute = $entityAttributesHandler->fetch(PDO::FETCH_ASSOC)) {
      if (isset($existingAttributeValues[$attribute['type']][$attribute['attribute']])) {
        $addLink = '';
        $state = 'dark';
      } else {
        $addLink = sprintf(
          '<a href="?edit=EntityAttributes&Entity=%d&oldEntity=%d&type=%s&attribute=%s&action=Add">[copy]</a> ',
          $this->dbIdNr, $this->dbOldIdNr, $attribute['type'], $attribute['type']);
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
          $addLink = sprintf(
            '<a href="?edit=EntityAttributes&Entity=%d&oldEntity=%d&type=%s&attribute=%s&action=Add">[copy]</a> ',
            $this->dbIdNr, $this->dbOldIdNr, $attribute['type'], $attribute['type']);
          $state = 'danger';
        }
        if ($oldType != $attribute['type']) {
          print "\n" . self::HTML_END_UL;
          printf ("\n        <b>%s</b>\n        <ul>", $attribute['type']);
          $oldType = $attribute['type'];
        }
        printf ('%s          <li>%s<span class="text-%s">%s</span></li>', "\n",
          $addLink, $state, $attribute['attribute']);
      }?>

        </ul><?php
    }
    print "\n      </div><!-- end col -->\n    </div><!-- end row -->\n";
  }
  private function editIdPErrorURL() {
    if (isset($_GET['action']) && isset($_GET['errorURL']) && $_GET['errorURL'] != '') {
      $entityDescriptor = $this->getEntityDescriptor($this->newXml);
      $errorURLValue = trim(urldecode($_GET['errorURL']));

      # Find md:IDPSSODescriptor in XML
      $child = $entityDescriptor->firstChild;
      $idpSSODescriptor = false;
      while ($child && ! $idpSSODescriptor) {
        if ($child->nodeName == self::SAML_MD_IDPSSODESCRIPTOR)
          $idpSSODescriptor = $child;
        $child = $child->nextSibling;
      }

      $update = false;
      switch ($_GET['action']) {
        case 'Update' :
          if ($idpSSODescriptor) {
            $idpSSODescriptor->setAttribute('errorURL', $errorURLValue);
            $errorURLUpdateHandler = $this->metaDb->prepare(
              "INSERT INTO EntityURLs (`entity_id`, `URL`, `type`)
              VALUES (:Id, :URL, 'error')  ON DUPLICATE KEY UPDATE `URL` = :URL;");
            $errorURLUpdateHandler->bindParam(self::BIND_ID, $this->dbIdNr);
            $errorURLUpdateHandler->bindParam(self::BIND_URL, $errorURLValue);
            $errorURLUpdateHandler->execute();
            $update = true;
          }
          break;
        case 'Delete' :
          if ($idpSSODescriptor) {
            $idpSSODescriptor->removeAttribute('errorURL');
            $errorURLUpdateHandler = $this->metaDb->prepare(
              "DELETE FROM EntityURLs WHERE entity_id = :Id AND type = 'error';");
            $errorURLUpdateHandler->bindParam(self::BIND_ID, $this->dbIdNr);
            $errorURLUpdateHandler->execute();
            $update = true;
          }
          $errorURLValue = '';
          break;
        default :
      }
      if ($update) {
        $this->saveXML();
      }
    } else {
      $errorURLValue = '';
    }

    $errorURLHandler = $this->metaDb->prepare(
      "SELECT DISTINCT URL FROM EntityURLs WHERE entity_id = :Id AND type = 'error';");
    $errorURLHandler->bindParam(self::BIND_ID, $this->dbIdNr);
    $errorURLHandler->execute();
    $newURL = ($errorURL = $errorURLHandler->fetch(PDO::FETCH_ASSOC)) ? $errorURL['URL'] : 'Missing';
    $errorURLHandler->bindParam(self::BIND_ID, $this->dbOldIdNr);
    $errorURLHandler->execute();
    $oldURL = ($errorURL = $errorURLHandler->fetch(PDO::FETCH_ASSOC)) ? $errorURL['URL'] : 'Missing';

    if ($newURL == $oldURL) {
      $copy = '';
      $newstate = 'dark';
      $oldstate = 'dark';
    } else {
      $copy = sprintf('<a href="?edit=IdPErrorURL&Entity=%d&oldEntity=%d&action=Update&errorURL=%s">[copy]</a> ',
        $this->dbIdNr, $this->dbOldIdNr, urlencode($oldURL));
      $newstate = ($newURL == 'Missing') ? 'dark' : 'success';
      $oldstate = ($oldURL == 'Missing') ? 'dark' :'danger';
    }
    $oldURL = ($oldURL == 'Missing')
      ? 'Missing'
      : sprintf (self::HTML_HREF_BLANK, $oldURL, $oldstate, $oldURL);
    if ($newURL != 'Missing') {
      $baseLink = sprintf('<a href="?edit=IdPErrorURL&Entity=%d&oldEntity=%d&errorURL=%s&action=',
        $this->dbIdNr, $this->dbOldIdNr, urlencode($newURL));
      $links = $baseLink . self::HTML_COPY . $baseLink . self::HTML_DELETE;
      $newURL = sprintf (self::HTML_HREF_BLANK, $newURL, $newstate, $newURL);
    } else {
      $links = '';
    }

    printf('%s    <div class="row">%s      <div class="col">', "\n", "\n");
    printf('%s        <b>errorURL</b>
        <ul>
          <li>%s
            <p class="text-%s" style="white-space: nowrap;overflow: hidden;text-overflow: ellipsis;max-width: 30em;">
              %s
            </p>
          </li>
          </ul>', "\n", $links, $newstate, $newURL);
    printf ('
        <form>
          <input type="hidden" name="edit" value="IdPErrorURL">
          <input type="hidden" name="Entity" value="%d">
          <input type="hidden" name="oldEntity" value="%d">
          New errorURL :
          <input type="text" name="errorURL" value="%s">
          <br>
          <input type="submit" name="action" value="Update">
        </form>
        <a href="./?validateEntity=%d"><button>Back</button></a>
      </div><!-- end col -->
      <div class="col">%s', $this->dbIdNr, $this->dbOldIdNr, htmlspecialchars($errorURLValue), $this->dbIdNr, "\n");
    if ($this->oldExists)
      printf('%s        <b>errorURL</b>
        <ul>
          <li>%s
            <p class="text-%s" style="white-space: nowrap;overflow: hidden;text-overflow: ellipsis;max-width: 30em;">
              %s
            </p>
          </li>
        </ul>',
        "\n", $copy, $oldstate, $oldURL);
    print "\n      </div><!-- end col -->\n    </div><!-- end row -->\n";
  }
  private function editIdPScopes() {
    if (isset($_GET['action']) && isset($_GET['value']) && trim($_GET['value']) != '') {
      $changed = false;
      $entityDescriptor = $this->getEntityDescriptor($this->newXml);
      $scopeValue = trim($_GET['value']);

      # Find md:IDPSSODescriptor in XML
      $child = $entityDescriptor->firstChild;
      $idpSSODescriptor = false;
      while ($child && ! $idpSSODescriptor) {
        if ($child->nodeName == self::SAML_MD_IDPSSODESCRIPTOR)
          $idpSSODescriptor = $child;
        $child = $child->nextSibling;
      }

      switch ($_GET['action']) {
        case 'Add' :
          if ($idpSSODescriptor) {
            $child = $idpSSODescriptor->firstChild;
            $extensions = false;
            while ($child && ! $extensions) {
              switch ($child->nodeName) {
                case self::SAML_DS_SIGNATURE :
                  break;
                case self::SAML_MD_EXTENSIONS :
                  $extensions = $child;
                  break;
                default :
                  $extensions = $this->newXml->createElement(self::SAML_MD_EXTENSIONS);
                  $idpSSODescriptor->insertBefore($extensions, $child);
              }
              $child = $child->nextSibling;
            }
            if (! $extensions) {
              $extensions = $this->newXml->createElement(self::SAML_MD_EXTENSIONS);
              $idpSSODescriptor->appendChild($extensions);
            }

            $child = $extensions->firstChild;
            $beforeChild = false;
            $Scope = false;
            $shibmdFound = false;
            while ($child && ! $Scope) {
              switch ($child->nodeName) {
                case self::SAML_SHIBMD_SCOPE :
                  $shibmdFound = true;
                  if ($child->textContent == $scopeValue)
                    $Scope = $child;
                  break;
                case self::SAML_MDUI_UIINFO :
                case self::SAML_MDUI_DISCOHINTS :
                  $beforeChild = $beforeChild ? $beforeChild : $child;
                  break;
              }
              $child = $child->nextSibling;
            }
            if (! $Scope ) {
              $Scope = $this->newXml->createElement(self::SAML_SHIBMD_SCOPE, $scopeValue);
              $Scope->setAttribute('regexp', 'false');
              if ($beforeChild)
                $extensions->insertBefore($Scope, $beforeChild);
              else
                $extensions->appendChild($Scope);
              $changed = true;
            }

            if (! $shibmdFound) {
              $entityDescriptor->setAttributeNS(self::SAMLXMLNS_URI,
                'xmlns:shibmd', 'urn:mace:shibboleth:metadata:1.0');
            }

            if ($changed) {
              $scopesInsertHandler = $this->metaDb->prepare(
                'INSERT INTO Scopes (`entity_id`, `scope`, `regexp`) VALUES (:Id, :Scope, 0);');
              $scopesInsertHandler->bindParam(self::BIND_ID, $this->dbIdNr);
              $scopesInsertHandler->bindParam(self::BIND_SCOPE, $scopeValue);
              $scopesInsertHandler->execute();
            }
          }
          break;
        case 'Delete' :
          if ($idpSSODescriptor) {
            $child = $idpSSODescriptor->firstChild;
            $extensions = false;
            while ($child && ! $extensions) {
              if ($child->nodeName == self::SAML_MD_EXTENSIONS )
                $extensions = $child;
              $child = $child->nextSibling;
            }

            if ($extensions) {
              $moreElements = false;
              $child = $extensions->firstChild;
              $Scope = false;
              while ($child && ! $Scope) {
                if ($child->nodeName == self::SAML_SHIBMD_SCOPE && $child->textContent == $scopeValue) {
                  $extensions->removeChild($child);
                  $changed = true;
                } else {
                  $moreElements = true;
                }
                $child = $child->nextSibling;
              }
              if (! $moreElements ) {
                $idpSSODescriptor->removeChild($extensions);
              }
              if ($changed) {
                $scopesDeleteHandler = $this->metaDb->prepare(
                  'DELETE FROM Scopes WHERE entity_id = :Id AND scope = :Scope;');
                $scopesDeleteHandler->bindParam(self::BIND_ID, $this->dbIdNr);
                $scopesDeleteHandler->bindParam(self::BIND_SCOPE, $scopeValue);
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
    } else {
      $scopeValue = '';
    }

    $scopesHandler = $this->metaDb->prepare('SELECT `scope`, `regexp` FROM Scopes WHERE `entity_id` = :Id;');
    $scopesHandler->bindParam(self::BIND_ID, $this->dbOldIdNr);
    $scopesHandler->execute();
    $oldScopes = array();
    while ($scope = $scopesHandler->fetch(PDO::FETCH_ASSOC)) {
      $oldScopes[$scope['scope']] = array('regexp' => $scope['regexp'], 'state' => 'removed');
    }

    $scopesHandler->bindParam(self::BIND_ID, $this->dbIdNr);
    $scopesHandler->execute();
    printf('%s    <div class="row">%s      <div class="col">%s        <b>Scopes</b>%s        <ul>%s',
      "\n", "\n", "\n", "\n", "\n");
    while ($scope = $scopesHandler->fetch(PDO::FETCH_ASSOC)) {
      if (isset($oldScopes[$scope['scope']])) {
        $state = 'dark';
        $oldScopes[$scope['scope']]['state'] = 'same';
      } else {
        $state = 'success';
      }
      $baseLink = sprintf('<a href="?edit=IdPScopes&Entity=%d&oldEntity=%d&value=%s&action=',
        $this->dbIdNr, $this->dbOldIdNr, $scope['scope']);
      $links = $baseLink . self::HTML_COPY . $baseLink . self::HTML_DELETE;
      printf ('          <li>%s<span class="text-%s">%s (regexp="%s")</span></li>%s',
        $links, $state, $scope['scope'], $scope['regexp'] ? 'true' : 'false', "\n");
    }
    printf ('        </ul>
        <form>
          <input type="hidden" name="edit" value="IdPScopes">
          <input type="hidden" name="Entity" value="%d">
          <input type="hidden" name="oldEntity" value="%d">
          New Scope :
          <input type="text" name="value" value="%s">
          <br>
          <input type="submit" name="action" value="Add">
        </form>
        <a href="./?validateEntity=%d"><button>Back</button></a>
      </div><!-- end col -->
      <div class="col">',$this->dbIdNr, $this->dbOldIdNr, htmlspecialchars($scopeValue), $this->dbIdNr);
    if ($this->oldExists) {
      print '
        <b>Scopes</b>
        <ul>' . "\n";
      foreach ($oldScopes as $scope => $data) {
        if ($data['state'] == 'same') {
          $copy = '';
          $state = 'dark';
        } else {
          $copy = sprintf('<a href ="?edit=IdPScopes&Entity=%d&oldEntity=%d&action=Add&value=%s">[copy]</a> ',
            $this->dbIdNr, $this->dbOldIdNr, $scope);
          $state = 'danger';
        }
        printf ('          <li>%s<span class="text-%s">%s (regexp="%s")</span></li>%s',
          $copy, $state, $scope, $data['regexp'] ? 'true' : 'false', "\n");
      }
      print (self::HTML_END_UL);
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
        printf (self::HTML_DIV_CLASS_ALERT_DANGER, $error);
      } else {
        $changed = false;
        $entityDescriptor = $this->getEntityDescriptor($this->newXml);

        # Find md:IDPSSODescriptor in XML
        $child = $entityDescriptor->firstChild;
        $ssoDescriptor = false;
        while ($child && ! $ssoDescriptor) {
          if ($child->nodeName == 'md:'.$type.'Descriptor') {
            $ssoDescriptor = $child;
          }
          $child = $child->nextSibling;
        }
        switch ($_GET['action']) {
          case 'Add' :
            if ($ssoDescriptor) {
              $changed = true;
              $child = $ssoDescriptor->firstChild;
              $extensions = false;
              while ($child && ! $extensions) {
                switch ($child->nodeName) {
                  case self::SAML_DS_SIGNATURE :
                    break;
                  case self::SAML_MD_EXTENSIONS :
                    $extensions = $child;
                    break;
                  default :
                    $extensions = $this->newXml->createElement(self::SAML_MD_EXTENSIONS);
                    $ssoDescriptor->insertBefore($extensions, $child);
                }
                $child = $child->nextSibling;
              }
              if (! $extensions) {
                $extensions = $this->newXml->createElement(self::SAML_MD_EXTENSIONS);
                $ssoDescriptor->appendChild($extensions);
              }
              $child = $extensions->firstChild;
              $beforeChild = false;
              $uuInfo = false;
              $mduiFound = false;
              while ($child && ! $uuInfo) {
                switch ($child->nodeName) {
                  case self::SAML_MDUI_UIINFO :
                    $mduiFound = true;
                    $uuInfo = $child;
                    break;
                  case self::SAML_MDUI_DISCOHINTS :
                    $beforeChild = $beforeChild ? $beforeChild : $child;
                    $mduiFound = true;
                    break;
                  default :
                }
                $child = $child->nextSibling;
              }
              if (! $uuInfo ) {
                $uuInfo = $this->newXml->createElement(self::SAML_MDUI_UIINFO);
                if ($beforeChild) {
                  $extensions->insertBefore($uuInfo, $beforeChild);
                } else {
                  $extensions->appendChild($uuInfo);
                }
              }
              if (! $mduiFound) {
                $entityDescriptor->setAttributeNS(self::SAMLXMLNS_URI,
                  self::SAMLXMLNS_MDUI, self::SAMLXMLNS_MDUI_URL);
              }
              # Find mdui:* in XML
              $child = $uuInfo->firstChild;
              $MduiElement = false;
              while ($child && ! $MduiElement) {
                if ($child->nodeName == $elementmd && strtolower($child->getAttribute('xml:lang')) == $langvalue) {
                  if ($elementmd == self::SAML_MDUI_LOGO) {
                    if ( $child->getAttribute('height') == $heightValue && $child->getAttribute('width') == $widthValue)
                      $MduiElement = $child;
                  } else {
                    $MduiElement = $child;
                  }
                }
                $child = $child->nextSibling;
              }
              if ($elementmd == self::SAML_MDUI_LOGO
                || $elementmd == self::SAML_MDUI_INFORMATIONURL
                || $elementmd == self::SAML_MDUI_PRIVACYSTATEMENTURL) {
                $value = str_replace(' ', '+', $value);
              }
              if ($MduiElement) {
                # Update value
                $MduiElement->nodeValue = htmlspecialchars($value);
                if ($elementmd == self::SAML_MDUI_LOGO) {
                  $mduiUpdateHandler = $this->metaDb->prepare(
                    'UPDATE Mdui
                    SET data = :Data
                    WHERE type = :Type
                      AND entity_id = :Id
                      AND lang = :Lang
                      AND height = :Height
                      AND  width = :Width
                      AND element = :Element;');
                  $mduiUpdateHandler->bindParam(self::BIND_HEIGHT, $heightValue);
                  $mduiUpdateHandler->bindParam(self::BIND_WIDTH, $widthValue);
                } else {
                  $mduiUpdateHandler = $this->metaDb->prepare(
                    'UPDATE Mdui
                    SET data = :Data
                    WHERE type = :Type AND entity_id = :Id AND lang = :Lang AND element = :Element;');
                }
                $mduiUpdateHandler->bindParam(self::BIND_ID, $this->dbIdNr);
                $mduiUpdateHandler->bindParam(self::BIND_TYPE, $type);
                $mduiUpdateHandler->bindParam(self::BIND_LANG, $langvalue);
                $mduiUpdateHandler->bindParam(self::BIND_ELEMENT, $elementValue);
                $mduiUpdateHandler->bindParam(self::BIND_DATA, $value);
                $mduiUpdateHandler->execute();
              } else {
                # Add if missing
                $MduiElement = $this->newXml->createElement($elementmd, htmlspecialchars($value));
                $MduiElement->setAttribute('xml:lang', $langvalue);
                if ($elementmd == self::SAML_MDUI_LOGO) {
                  $MduiElement->setAttribute('height', $heightValue);
                  $MduiElement->setAttribute('width', $widthValue);
                  $mduiAddHandler = $this->metaDb->prepare(
                    'INSERT INTO Mdui (entity_id, type, lang, height, width, element, data)
                    VALUES (:Id, :Type, :Lang, :Height, :Width, :Element, :Data);');
                  $mduiAddHandler->bindParam(self::BIND_HEIGHT, $heightValue);
                  $mduiAddHandler->bindParam(self::BIND_WIDTH, $widthValue);
                } else {
                  $mduiAddHandler = $this->metaDb->prepare(
                    'INSERT INTO Mdui (entity_id, type, lang, height, width, element, data)
                    VALUES (:Id, :Type, :Lang, 0, 0, :Element, :Data);');
                }
                $uuInfo->appendChild($MduiElement);
                $mduiAddHandler->bindParam(self::BIND_ID, $this->dbIdNr);
                $mduiAddHandler->bindParam(self::BIND_TYPE, $type);
                $mduiAddHandler->bindParam(self::BIND_LANG, $langvalue);
                $mduiAddHandler->bindParam(self::BIND_ELEMENT, $elementValue);
                $mduiAddHandler->bindParam(self::BIND_DATA, $value);
                $mduiAddHandler->execute();
              }
            }
            break;
          case 'Delete' :
            if ($ssoDescriptor) {
              $child = $ssoDescriptor->firstChild;
              $extensions = false;
              while ($child && ! $extensions) {
                if ($child->nodeName == self::SAML_MD_EXTENSIONS)
                  $extensions = $child;
                $child = $child->nextSibling;
              }
              if ($extensions) {
                $child = $extensions->firstChild;
                $uuInfo = false;
                $moreExtentions = false;
                while ($child && ! $uuInfo) {
                  if ($child->nodeName == self::SAML_MDUI_UIINFO) {
                    $mduiFound = true;
                    $uuInfo = $child;
                  } else {
                    $moreExtentions = true;
                  }
                  $child = $child->nextSibling;
                }
                $moreExtentions = $moreExtentions ? true : $child;
                if ($uuInfo) {
                  # Find mdui:* in XML
                  $child = $uuInfo->firstChild;
                  $MduiElement = false;
                  $moreMduiElement = false;
                  while ($child && ! $MduiElement) {
                    if ($child->nodeName == $elementmd && strtolower($child->getAttribute('xml:lang')) == $langvalue) {
                      if ($elementmd == self::SAML_MDUI_LOGO) {
                        if ($child->getAttribute('height') == $heightValue
                          && $child->getAttribute('width') == $widthValue) {
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
                    if ($elementmd == self::SAML_MDUI_LOGO) {
                      $mduiRemoveHandler = $this->metaDb->prepare(
                        'DELETE FROM Mdui
                        WHERE type = :Type
                        AND entity_id = :Id
                        AND lang = :Lang
                        AND height = :Height
                        AND  width = :Width
                        AND element = :Element;');
                      $mduiRemoveHandler->bindParam(self::BIND_HEIGHT, $heightValue);
                      $mduiRemoveHandler->bindParam(self::BIND_WIDTH, $widthValue);
                    } else {
                      $mduiRemoveHandler = $this->metaDb->prepare(
                        'DELETE FROM Mdui
                        WHERE type = :Type AND entity_id = :Id AND lang = :Lang AND element = :Element;');
                    }
                    $mduiRemoveHandler->bindParam(self::BIND_ID, $this->dbIdNr);
                    $mduiRemoveHandler->bindParam(self::BIND_TYPE, $type);
                    $mduiRemoveHandler->bindParam(self::BIND_LANG, $langvalue);
                    $mduiRemoveHandler->bindParam(self::BIND_ELEMENT, $elementValue);
                    $mduiRemoveHandler->execute();
                    $changed = true;
                    $uuInfo->removeChild($MduiElement);
                    if (! $moreMduiElement) {
                      $extensions->removeChild($uuInfo);
                      if (! $moreExtentions)
                        $ssoDescriptor->removeChild($extensions);
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
    $mduiHandler = $this->metaDb->prepare(
      'SELECT element, lang, height, width, data
      FROM Mdui
      WHERE entity_id = :Id AND type = :Type ORDER BY lang, element;');
    $mduiHandler->bindParam(self::BIND_TYPE, $type);
    $oldMDUIElements = array();
    $mduiHandler->bindParam(self::BIND_ID, $this->dbOldIdNr);
    $mduiHandler->execute();
    while ($mdui = $mduiHandler->fetch(PDO::FETCH_ASSOC)) {
      if (! isset($oldMDUIElements[$mdui['lang']]) ) {
        $oldMDUIElements[$mdui['lang']] = array();
      }
      $oldMDUIElements[$mdui['lang']][$mdui['element']] = array(
        'value' => $mdui['data'],
        'height' => $mdui['height'],
        'width' => $mdui['width'],
        'state' => 'removed');
    }
    $oldLang = 'xxxxxxx';
    $mduiHandler->bindParam(self::BIND_ID, $this->dbIdNr);
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
        printf('%s        <b>Lang = "%s" - %s</b>%s        <ul>',
          $showEndUL ? "\n        </ul>\n" : '', $lang, $fullLang, "\n");
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
            if ($oldMDUIElements[$lang][$element]['height'] == $height
              && $oldMDUIElements[$lang][$element]['width'] == $width) {
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
      $baseLink = sprintf('<a href="?edit=%s&Entity=%d&oldEntity=%d&element=%s&height=%d&width=%d&lang=%s&value=%s&action=',
        $edit, $this->dbIdNr, $this->dbOldIdNr, $element, $height, $width, $lang, urlencode($data));
      $links = $baseLink . self::HTML_COPY . $baseLink . self::HTML_DELETE;
      switch ($element) {
        case 'Logo' :
          printf ('%s          <li>%s<span class="text-%s">%s (%dx%d) = %s</span></li>',
            "\n", $links, $state, $element, $height, $width, sprintf (self::HTML_HREF_BLANK, $data, $state, $data));
        break;
        case 'InformationURL' :
        case 'PrivacyStatementURL' :
          printf ('%s          <li>%s<span class="text-%s">%s = %s</span></li>',
            "\n", $links, $state, $element, sprintf (self::HTML_HREF_BLANK, $data, $state, $data));
          break;
        default :
          printf ('%s          <li>%s<span class="text-%s">%s = %s</span></li>', "\n", $links, $state, $element, $data);
      }
    }
    if ($showEndUL) {
      print "\n" . self::HTML_END_UL;
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
            <div class="col">',
        $edit, $this->dbIdNr, $this->dbOldIdNr, $elementValue == 'DisplayName' ? ' selected' : '',
        $elementValue == 'Description' ? ' selected' : '', $elementValue == 'Keywords' ? ' selected' : '',
        $elementValue == 'Logo' ? ' selected' : '', $elementValue == 'InformationURL' ? ' selected' : '',
        $elementValue == 'PrivacyStatementURL' ? ' selected' : '');
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
      <div class="col">', htmlspecialchars($value), $heightValue, $widthValue, $this->dbIdNr);

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
              $copy = sprintf('<a href ="?edit=%s&Entity=%d&oldEntity=%d&element=%s&lang=%s&value=%s&height=%d&width=%d&action=Add">[copy]</a> ',
                $edit, $this->dbIdNr, $this->dbOldIdNr, $element, $lang,
                $data['value'], $data['height'], $data['width']);
            } else {
              $copy = sprintf('<a href ="?edit=%s&Entity=%d&oldEntity=%d&element=%s&lang=%s&value=%s&action=Add">[copy]</a> ',
                $edit, $this->dbIdNr, $this->dbOldIdNr, $element, $lang, $data['value']);
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
            $value = sprintf (self::HTML_HREF_BLANK, $data['value'], $state, $data['value']);
            break;
          default :
            $value = $data['value'];
        }
        if ($element == 'Logo') {
          printf ('%s          <li>%s<span class="text-%s">%s (%dx%d) = %s</span></li>', "\n",
            $copy, $state, $element, $data['height'], $data['width'], $value);
        } else {
          printf ('%s          <li>%s<span class="text-%s">%s = %s</span></li>', "\n", $copy, $state, $element, $value);
        }
      }
      print "\n" . self::HTML_END_UL;
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
        printf (self::HTML_DIV_CLASS_ALERT_DANGER, $error);
      } else {
        $changed = false;
        $entityDescriptor = $this->getEntityDescriptor($this->newXml);

        # Find md:IDPSSODescriptor in XML
        $child = $entityDescriptor->firstChild;
        $ssoDescriptor = false;
        while ($child && ! $ssoDescriptor) {
          if ($child->nodeName == self::SAML_MD_IDPSSODESCRIPTOR)
            $ssoDescriptor = $child;
          $child = $child->nextSibling;
        }
        switch ($_GET['action']) {
          case 'Add' :
            if ($ssoDescriptor) {
              $changed = true;
              $child = $ssoDescriptor->firstChild;
              $extensions = false;
              while ($child && ! $extensions) {
                switch ($child->nodeName) {
                  case self::SAML_DS_SIGNATURE :
                    break;
                  case self::SAML_MD_EXTENSIONS :
                    $extensions = $child;
                    break;
                  default :
                    $extensions = $this->newXml->createElement(self::SAML_MD_EXTENSIONS);
                    $ssoDescriptor->insertBefore($extensions, $child);
                }
                $child = $child->nextSibling;
              }
              if (! $extensions) {
                $extensions = $this->newXml->createElement(self::SAML_MD_EXTENSIONS);
                $ssoDescriptor->appendChild($extensions);
              }
              $child = $extensions->firstChild;
              $beforeChild = false;
              $DiscoHints = false;
              $mduiFound = false;
              while ($child && ! $DiscoHints) {
                switch ($child->nodeName) {
                  case self::SAML_MDUI_UIINFO :
                    $mduiFound = true;
                    break;
                  case self::SAML_MDUI_DISCOHINTS :
                    $DiscoHints = $child;
                    $mduiFound = true;
                    break;
                }
                $child = $child->nextSibling;
              }
              if (! $DiscoHints ) {
                $DiscoHints = $this->newXml->createElement(self::SAML_MDUI_DISCOHINTS);
                $extensions->appendChild($DiscoHints);
              }
              if (! $mduiFound) {
                $entityDescriptor->setAttributeNS(self::SAMLXMLNS_URI, self::SAMLXMLNS_MDUI, self::SAMLXMLNS_MDUI_URL);
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
                $mduiAddHandler = $this->metaDb->prepare("INSERT INTO Mdui (entity_id, type, element, data)
                  VALUES (:Id, 'IDPDisco', :Element, :Data);");
                $mduiAddHandler->bindParam(self::BIND_ID, $this->dbIdNr);
                $mduiAddHandler->bindParam(self::BIND_ELEMENT, $elementValue);
                $mduiAddHandler->bindParam(self::BIND_DATA, $value);
                $mduiAddHandler->execute();
              }
            }
            break;
          case 'Delete' :
            if ($ssoDescriptor) {
              $child = $ssoDescriptor->firstChild;
              $extensions = false;
              while ($child && ! $extensions) {
                if ($child->nodeName == self::SAML_MD_EXTENSIONS) {
                  $extensions = $child;
                }
                $child = $child->nextSibling;
              }
              if ($extensions) {
                $child = $extensions->firstChild;
                $DiscoHints = false;
                $moreExtentions = false;
                while ($child && ! $DiscoHints) {
                  switch ($child->nodeName) {
                    case self::SAML_MDUI_DISCOHINTS :
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
                    $mduiRemoveHandler = $this->metaDb->prepare(
                      "DELETE FROM Mdui
                      WHERE type = 'IDPDisco' AND entity_id = :Id AND element = :Element AND data = :Data;");
                    $mduiRemoveHandler->bindParam(self::BIND_ID, $this->dbIdNr);
                    $mduiRemoveHandler->bindParam(self::BIND_ELEMENT, $elementValue);
                    $mduiRemoveHandler->bindParam(self::BIND_DATA, $value);
                    $mduiRemoveHandler->execute();
                    $changed = true;
                    $DiscoHints->removeChild($MduiElement);
                    if (! $moreMduiElement) {
                      $extensions->removeChild($DiscoHints);
                      if (! $moreExtentions)
                        $ssoDescriptor->removeChild($extensions);
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
    $mduiHandler = $this->metaDb->prepare(
      "SELECT element, data FROM Mdui WHERE entity_id = :Id AND type = 'IDPDisco' ORDER BY element;");
    $oldMDUIElements = array();
    $mduiHandler->bindParam(self::BIND_ID, $this->dbOldIdNr);
    $mduiHandler->execute();
    while ($mdui = $mduiHandler->fetch(PDO::FETCH_ASSOC)) {
      if (! isset($oldMDUIElements[$mdui['element']]) ) {
        $oldMDUIElements[$mdui['element']] = array();
      }
      $oldMDUIElements[$mdui['element']][$mdui['data']] = 'removed';
    }
    $oldElement = 'xxxxxxx';
    $mduiHandler->bindParam(self::BIND_ID, $this->dbIdNr);
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
      $baseLink = sprintf('<a href="?edit=DiscoHints&Entity=%d&oldEntity=%d&element=%s&value=%s&action=',
        $this->dbIdNr, $this->dbOldIdNr, $element, $data);
      $links = $baseLink . self::HTML_COPY . $baseLink . self::HTML_DELETE;
      printf ('%s          <li>%s<span class="text-%s">%s</span></li>', "\n", $links, $state, $data);
    }
    if ($showEndUL) {
      print "\n" . self::HTML_END_UL;
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
      <div class="col">',
      $this->dbIdNr, $this->dbOldIdNr, $elementValue == 'DomainHint' ? ' selected' : '',
      $elementValue == 'GeolocationHint' ? ' selected' : '', $elementValue == 'IPHint' ? ' selected' : '',
      htmlspecialchars($value), $this->dbIdNr);

    foreach ($oldMDUIElements as $element => $elementValues) {
      printf ('%s        <b>%s</b>%s        <ul>', "\n", $element, "\n");
      foreach ($elementValues as $data => $state) {
        switch ($state) {
          case 'same' :
            $copy = '';
            $state = 'dark';
            break;
          case 'removed' :
            $copy = sprintf('<a href ="?edit=DiscoHints&Entity=%d&oldEntity=%d&element=%s&value=%s&action=Add">[copy]</a> ',
              $this->dbIdNr, $this->dbOldIdNr, $element, $data);
            $state = 'danger';
            break;
          default :
            $copy = '';
            $state = 'danger';
        }
        printf ('%s          <li>%s<span class="text-%s">%s</span></li>', "\n", $copy, $state, $data);
      }
      print "\n" . self::HTML_END_UL;
    }
    print "\n      </div><!-- end col -->\n    </div><!-- end row -->\n";
  }
  private function addKeyInfo($type) {
    $edit = $type == 'IDPSSO' ? 'IdPKeyInfo' : 'SPKeyInfo';
    $edit = $type == 'AttributeAuthority' ? 'AAKeyInfo' : $edit;
    $added = false;
    if (isset($_POST['certificate']) && isset($_POST['use'])) {
      $certificate = str_replace(array("\r") ,array(''), $_POST['certificate']);
      $use = $_POST['use'];
      $cert = "-----BEGIN CERTIFICATE-----\n" .
        chunk_split(str_replace(array(' ',"\n",'&#13;') ,array('','',''),$certificate),64) .
        "-----END CERTIFICATE-----\n";
      if ($certInfo = openssl_x509_parse( $cert)) {
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

        $descriptor = 'md:'.$type.'Descriptor';
        $entityDescriptor = $this->getEntityDescriptor($this->newXml);

        # Find md:SSODescriptor in XML
        $child = $entityDescriptor->firstChild;
        $ssoDescriptor = false;
        while ($child && ! $ssoDescriptor) {
          if ($child->nodeName == $descriptor) {
            $ssoDescriptor = $child;
          }
          $child = $child->nextSibling;
        }
        if ($ssoDescriptor) {
          $child = $ssoDescriptor->firstChild;
          $beforeChild = false;
          while ($child && !$beforeChild) {
            if ($child->nodeName == self::SAML_MD_EXTENSIONS) {
              $child = $child->nextSibling;
            } else {
              $beforeChild = $child;
            }
          }

          if ($child->nodeName != self::SAML_MD_KEYDESCRIPTOR) {
            # No existing KeyDescriptor. Suggest no existing KeyInfo ? Better add xmlns:ds
            $entityDescriptor->setAttributeNS(self::SAMLXMLNS_URI, self::SAMLXMLNS_DS, self::SAMLXMLNS_DS_URL);
          }

          $keyDescriptor = $this->newXml->createElement(self::SAML_MD_KEYDESCRIPTOR);
          if ($use <> "both") $keyDescriptor->setAttribute('use', $use);

          if ($beforeChild)
            $ssoDescriptor->insertBefore($keyDescriptor, $beforeChild);
          else
            $ssoDescriptor->appendChild($keyDescriptor);

          $KeyInfo = $this->newXml->createElement('ds:KeyInfo');
          $keyDescriptor->appendChild($KeyInfo);

          $X509Data = $this->newXml->createElement('ds:X509Data');
          $KeyInfo->appendChild($X509Data);

          $X509Certificate = $this->newXml->createElement('ds:X509Certificate');
          $X509Certificate->nodeValue = $certificate;
          $X509Data->appendChild($X509Certificate);

          $this->saveXML();

          $reorderKeyOrderHandler = $this->metaDb->prepare(
            'UPDATE KeyInfo SET `order` = `order` +1  WHERE entity_id = :Id;');
          $reorderKeyOrderHandler->bindParam(self::BIND_ID, $this->dbIdNr);
          $reorderKeyOrderHandler->execute();

          $KeyInfoHandler = $this->metaDb->prepare(
            'INSERT INTO KeyInfo
            (`entity_id`, `type`, `use`, `order`, `name`, `notValidAfter`,
              `subject`, `issuer`, `bits`, `key_type`, `serialNumber`)
            VALUES (:Id, :Type, :Use, 0, :Name, :NotValidAfter, :Subject, :Issuer, :Bits, :Key_type, :SerialNumber);');
          $KeyInfoHandler->bindValue(self::BIND_ID, $this->dbIdNr);
          $KeyInfoHandler->bindValue(self::BIND_TYPE, $type);
          $KeyInfoHandler->bindValue(self::BIND_USE, $use);
          $KeyInfoHandler->bindValue(self::BIND_NAME, '');
          $KeyInfoHandler->bindValue(self::BIND_NOTVALIDAFTER, date('Y-m-d H:i:s', $certInfo['validTo_time_t']));
          $KeyInfoHandler->bindParam(self::BIND_SUBJECT, $subject);
          $KeyInfoHandler->bindParam(self::BIND_ISSUER, $issuer);
          $KeyInfoHandler->bindParam(self::BIND_BITS, $key_info['bits']);
          $KeyInfoHandler->bindParam(self::BIND_KEY_TYPE, $keyType);
          $KeyInfoHandler->bindParam(self::BIND_SERIALNUMBER, $certInfo['serialNumber']);
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
      printf('    <form action="?edit=Add%s&Entity=%d&oldEntity=%d" method="POST" enctype="multipart/form-data">
      <p>
        <label for="certificate">
          Certificate:<br>
          <i>
            Add the part from certificate <b>BETWEN</b>
            -----BEGIN CERTIFICATE----- and -----END CERTIFICATE----- tags<br>
            -----BEGIN and -----END should not be included in this form
          </i>
        </label>
      </p>
      <textarea id="certificate" name="certificate" rows="10" cols="90" placeholder="
        MIIGMjCCBRqgAwIBAgISBMpHeMtDoua9sjLy4Rcagh+tMA0GCSqGSIb3DQEBCwUA
        MDIxCzAJBgNVBAYTAlVTMRYwFAYDVQQKEw1MZXQncyBFbmNyeXB0MQswCQYDVQQD
        EwJSMzAeFw0yMjA2MTgxMTI5NDVaFw0yMjA5MTYxMTI5NDRaMCExHzAdBgNVBAMT
        Fm1ldGFkYXRhLmxhYi5zd2FtaWQuc2UwggIiMA0GCSqGSIb3DQEBAQUAA4ICDwAw
        ggIKAoICAQDED9gxnL+2CVtcwzwTcveVYV4fAQs8KT/wVYPCBFGfxaek9Rl30ZdZ
        He6HPpFey545PkHwH2RRmHzWCILZrQ692w6kBfgmhl+h1FViWXRJL0/C6HVadj/T
        MvBrS8m6r42oSdp5p3VDmCkHW5ZkHeieVLEEvhjgGwWGXF1BIWxPeiJX5zmQy8HF
        VHnpylWc5T1gkdmuDkNQX4v4nXw7KGl9apyi5ArKy6/J7JeCtsMDsylatfGcaQim
        34ogVeE8MtaHX8LjyjYRKdEZUMQWp9dhD4d2Yp0hAuADV2ybyWbJrc5CPM4C6gof
        ......">%s</textarea>
      <p><label for="use">Type of certificate</label></p>
      <select id="use" name="use">
        <option %svalue="encryption">Encryption</option>
        <option %svalue="signing">Signing</option>
        <option %svalue="both">Encryption & Signing</option>
      </select><br>
      <button type="submit" class="btn btn-primary">Submit</button>%s    </form>',
        $edit, $this->dbIdNr, $this->dbOldIdNr, $certificate, $use == "encryption" ? 'selected ' : '',
        $use == "signing" ? 'selected ' : '', $use == "both" ? 'selected ' : '', "\n");
      printf('    <a href="?edit=%s&Entity=%d&oldEntity=%d"><button>Back</button></a>%s',
        $edit, $this->dbIdNr, $this->dbOldIdNr, "\n");
    }
  }
  private function editKeyInfo($type) {
    $timeNow = date('Y-m-d H:i:00');
    $timeWarn = date('Y-m-d H:i:00', time() + 7776000);  // 90 * 24 * 60 * 60 = 90 days / 3 month

    printf ('%s    <div class="row">%s      <div class="col">', "\n", "\n");
    $edit = $type == 'IDPSSO' ? 'IdPKeyInfo' : 'SPKeyInfo';
    $edit = $type == 'AttributeAuthority' ? 'AAKeyInfo' : $edit;
    $addLink = sprintf('<a href="?edit=Add%s&Entity=%d&oldEntity=%d"><button>Add new certificate</button></a><br>',
      $edit, $this->dbIdNr, $this->dbOldIdNr);
    if (isset($_GET['action'])) {
      $error = '';
      if ($_GET['action'] == 'Delete'
        || $_GET['action'] == 'MoveUp'
        || $_GET['action'] == 'MoveDown'
        || $_GET['action'] == 'Change'
        || $_GET['action'] == 'UpdateUse') {
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
        printf (self::HTML_DIV_CLASS_ALERT_DANGER, $error);
      } else {
        $changed = false;
        $descriptor = 'md:'.$type.'Descriptor';
        $entityDescriptor = $this->getEntityDescriptor($this->newXml);

        # Find md:SSODescriptor in XML
        $child = $entityDescriptor->firstChild;
        $ssoDescriptor = false;
        while ($child && ! $ssoDescriptor) {
          if ($child->nodeName == $descriptor) {
            $ssoDescriptor = $child;
          }
          $child = $child->nextSibling;
        }
        switch ($_GET['action']) {
          case 'MoveUp' :
            if ($ssoDescriptor) {
              $child = $ssoDescriptor->firstChild;
              $moveKeyDescriptor = false;
              $xmlOrder = 0;
              $changed = false;
              $previusKeyDescriptor = false;
              while ($child) {
                // Loop thrue all KeyDescriptor:s not just the first one!
                if ($child->nodeName == self::SAML_MD_KEYDESCRIPTOR) {
                  $usage = $child->getAttribute('use') ? $child->getAttribute('use') : 'both';
                  if ( $usage == $use && $order == $xmlOrder) {
                    $keyDescriptor = $child; // Save to be able to move this KeyDescriptor
                    $descriptorChild = $keyDescriptor->firstChild;
                    while ($descriptorChild && !$moveKeyDescriptor) {
                      if ($descriptorChild->nodeName == 'ds:KeyInfo') {
                        $infoChild = $descriptorChild->firstChild;
                        while ($infoChild && !$moveKeyDescriptor) {
                          if ($infoChild->nodeName == 'ds:X509Data') {
                            $x509Child = $infoChild->firstChild;
                            while ($x509Child&& !$moveKeyDescriptor) {
                              if ($x509Child->nodeName == 'ds:X509Certificate') {
                                $cert = "-----BEGIN CERTIFICATE-----\n" .
                                  chunk_split(str_replace(array(' ',"\n") ,array('',''),
                                    trim($x509Child->textContent)),64) .
                                  "-----END CERTIFICATE-----\n";
                                if ($certInfo = openssl_x509_parse( $cert)) {
                                  if ($certInfo['serialNumber'] == $serialNumber)
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
                  $ssoDescriptor->insertBefore($keyDescriptor, $previusKeyDescriptor);

                  $reorderKeyOrderHandler = $this->metaDb->prepare(
                    'UPDATE KeyInfo SET `order` = :NewOrder WHERE entity_id = :Id AND `order` = :OldOrder;');
                  $reorderKeyOrderHandler->bindParam(self::BIND_ID, $this->dbIdNr);
                  #Move key out of way
                  $reorderKeyOrderHandler->bindValue(self::BIND_OLDORDER, $order);
                  $reorderKeyOrderHandler->bindValue(self::BIND_NEWORDER, 255);
                  $reorderKeyOrderHandler->execute();
                  # Move previus
                  $reorderKeyOrderHandler->bindValue(self::BIND_OLDORDER, $order-1);
                  $reorderKeyOrderHandler->bindValue(self::BIND_NEWORDER, $order);
                  $reorderKeyOrderHandler->execute();
                  #Move into previus place
                  $reorderKeyOrderHandler->bindValue(self::BIND_OLDORDER, 255);
                  $reorderKeyOrderHandler->bindValue(self::BIND_NEWORDER, $order-1);
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
            if ($ssoDescriptor) {
              $child = $ssoDescriptor->firstChild;
              $moveKeyDescriptor = false;
              $xmlOrder = 0;
              $changed = false;
              while ($child && !$changed) {
                // Loop thrue all KeyDescriptor:s not just the first one!
                if ($child->nodeName == self::SAML_MD_KEYDESCRIPTOR) {
                  // Move if found in previus round
                  if ($moveKeyDescriptor) {
                    $ssoDescriptor->insertBefore($child, $keyDescriptor);

                    $reorderKeyOrderHandler = $this->metaDb->prepare(
                      'UPDATE KeyInfo SET `order` = :NewOrder WHERE entity_id = :Id AND `order` = :OldOrder;');
                    $reorderKeyOrderHandler->bindParam(self::BIND_ID, $this->dbIdNr);
                    #Move key out of way
                    $reorderKeyOrderHandler->bindValue(self::BIND_OLDORDER, $order);
                    $reorderKeyOrderHandler->bindValue(self::BIND_NEWORDER, 255);
                    $reorderKeyOrderHandler->execute();
                    # Move previus
                    $reorderKeyOrderHandler->bindValue(self::BIND_OLDORDER, $order+1);
                    $reorderKeyOrderHandler->bindValue(self::BIND_NEWORDER, $order);
                    $reorderKeyOrderHandler->execute();
                    #Move into previus place
                    $reorderKeyOrderHandler->bindValue(self::BIND_OLDORDER, 255);
                    $reorderKeyOrderHandler->bindValue(self::BIND_NEWORDER, $order+1);
                    $reorderKeyOrderHandler->execute();

                    // Reset flag for next KeyDescriptor
                    $changed = true;
                  } else {
                    $usage = $child->getAttribute('use') ? $child->getAttribute('use') : 'both';
                    if ( $usage == $use && $order == $xmlOrder) {
                      $keyDescriptor = $child; // Save to be able to move this KeyDescriptor
                      $descriptorChild = $keyDescriptor->firstChild;
                      while ($descriptorChild && !$moveKeyDescriptor) {
                        if ($descriptorChild->nodeName == 'ds:KeyInfo') {
                          $infoChild = $descriptorChild->firstChild;
                          while ($infoChild && !$moveKeyDescriptor) {
                            if ($infoChild->nodeName == 'ds:X509Data') {
                              $x509Child = $infoChild->firstChild;
                              while ($x509Child&& !$moveKeyDescriptor) {
                                if ($x509Child->nodeName == 'ds:X509Certificate') {
                                  $cert = "-----BEGIN CERTIFICATE-----\n" .
                                    chunk_split(str_replace(array(' ',"\n") ,array('',''),
                                      trim($x509Child->textContent)),64) .
                                    "-----END CERTIFICATE-----\n";
                                  if ($certInfo = openssl_x509_parse( $cert)) {
                                    if ($certInfo['serialNumber'] == $serialNumber) {
                                      $moveKeyDescriptor = true;
                                    }
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
            if ($ssoDescriptor) {
              $child = $ssoDescriptor->firstChild;
              $removeKeyDescriptor = false;
              $xmlOrder = 0;
              $changed = false;
              while ($child) {
                // Loop thrue all KeyDescriptor:s not just the first one!
                if ($child->nodeName == self::SAML_MD_KEYDESCRIPTOR) {
                  $usage = $child->getAttribute('use') ? $child->getAttribute('use') : 'both';
                  if ( $usage == $use && $order == $xmlOrder) {
                    $keyDescriptor = $child; // Save to be able to remove this KeyDescriptor
                    $descriptorChild = $keyDescriptor->firstChild;
                    while ($descriptorChild && !$removeKeyDescriptor) {
                      if ($descriptorChild->nodeName == 'ds:KeyInfo') {
                        $infoChild = $descriptorChild->firstChild;
                        while ($infoChild && !$removeKeyDescriptor) {
                          if ($infoChild->nodeName == 'ds:X509Data') {
                            $x509Child = $infoChild->firstChild;
                            while ($x509Child&& !$removeKeyDescriptor) {
                              if ($x509Child->nodeName == 'ds:X509Certificate') {
                                $cert = "-----BEGIN CERTIFICATE-----\n" .
                                  chunk_split(str_replace(array(' ',"\n") ,array('',''),
                                    trim($x509Child->textContent)),64) .
                                  "-----END CERTIFICATE-----\n";
                                if ($certInfo = openssl_x509_parse( $cert)) {
                                  if ($certInfo['serialNumber'] == $serialNumber) {
                                    $removeKeyDescriptor = true;
                                  }
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

                  $ssoDescriptor->removeChild($keyDescriptor);
                  $keyInfoDeleteHandler = $this->metaDb->prepare(
                    'DELETE FROM KeyInfo
                    WHERE entity_id = :Id AND `type` = :Type AND `use` = :Use AND `serialNumber` = :SerialNumber
                    ORDER BY `order` LIMIT 1;');
                  $keyInfoDeleteHandler->bindParam(self::BIND_ID, $this->dbIdNr);
                  $keyInfoDeleteHandler->bindParam(self::BIND_TYPE, $type);
                  $keyInfoDeleteHandler->bindParam(self::BIND_USE, $use);
                  $keyInfoDeleteHandler->bindParam(self::BIND_SERIALNUMBER, $serialNumber);
                  $keyInfoDeleteHandler->execute();

                  $reorderKeyOrderHandler = $this->metaDb->prepare(
                    'UPDATE KeyInfo SET `order` = `order` -1  WHERE entity_id = :Id AND `order` > :Order;');
                  $reorderKeyOrderHandler->bindParam(self::BIND_ID, $this->dbIdNr);
                  $reorderKeyOrderHandler->bindParam(self::BIND_ORDER, $order);
                  $reorderKeyOrderHandler->execute();

                  // Reset flag for next KeyDescriptor
                  $child = false;
                  $changed = true;
                }
              }
            }
            break;
          case 'Change' :
            if ($ssoDescriptor) {
              $child = $ssoDescriptor->firstChild;
              $changeKeyDescriptor = false;
              $xmlOrder = 0;
              $changed = false;
              while ($child) {
                // Loop thrue all KeyDescriptor:s not just the first one!
                if ($child->nodeName == self::SAML_MD_KEYDESCRIPTOR) {
                  $usage = $child->getAttribute('use') ? $child->getAttribute('use') : 'both';
                  if ( $usage == $use && $order == $xmlOrder) {
                    $keyDescriptor = $child; // Save to be able to update this KeyDescriptor
                    $descriptorChild = $keyDescriptor->firstChild;
                    while ($descriptorChild && !$changeKeyDescriptor) {
                      if ($descriptorChild->nodeName == 'ds:KeyInfo') {
                        $infoChild = $descriptorChild->firstChild;
                        while ($infoChild && !$changeKeyDescriptor) {
                          if ($infoChild->nodeName == 'ds:X509Data') {
                            $x509Child = $infoChild->firstChild;
                            while ($x509Child&& !$changeKeyDescriptor) {
                              if ($x509Child->nodeName == 'ds:X509Certificate') {
                                $cert = "-----BEGIN CERTIFICATE-----\n" .
                                  chunk_split(str_replace(array(' ',"\n"), array('',''),
                                    trim($x509Child->textContent)),64) .
                                  "-----END CERTIFICATE-----\n";
                                if ($certInfo = openssl_x509_parse( $cert)) {
                                  if ($certInfo['serialNumber'] == $serialNumber)
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
                    $keyDescriptor->removeAttribute('use');
                    $newUse = 'both';
                  } else {
                    $keyDescriptor->setAttribute('use', $newUse);
                  }
                  $keyInfoUpdateHandler = $this->metaDb->prepare(
                    'UPDATE KeyInfo SET `use` = :NewUse
                    WHERE entity_id = :Id
                      AND `type` = :Type
                      AND `use` = :Use
                      AND `serialNumber` = :SerialNumber
                      AND `order` = :Order;');
                  $keyInfoUpdateHandler->bindParam(self::BIND_NEWUSE, $newUse);
                  $keyInfoUpdateHandler->bindParam(self::BIND_ID, $this->dbIdNr);
                  $keyInfoUpdateHandler->bindParam(self::BIND_TYPE, $type);
                  $keyInfoUpdateHandler->bindParam(self::BIND_USE, $use);
                  $keyInfoUpdateHandler->bindParam(self::BIND_SERIALNUMBER, $serialNumber);
                  $keyInfoUpdateHandler->bindParam(self::BIND_ORDER, $order);
                  $keyInfoUpdateHandler->execute();

                  // Reset flag for next KeyDescriptor
                  $child = false;
                  $changed = true;
                }
              }
            }
            break;
          default :
        }
        if ($changed) {
          $this->saveXML();
        }
      }
    }

    $KeyInfoStatusHandler = $this->metaDb->prepare(
      'SELECT `use`, `order`, `notValidAfter` FROM KeyInfo WHERE entity_id = :Id AND type = :Type ORDER BY `order`');
    $KeyInfoStatusHandler->bindParam(self::BIND_TYPE, $type);
    $KeyInfoStatusHandler->bindParam(self::BIND_ID, $this->dbIdNr);
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
        default :
      }
      $maxOrder = $keyInfoStatus['order'];
    }
    $keyInfoHandler = $this->metaDb->prepare(
      'SELECT `use`, `order`, `name`, `notValidAfter`, `subject`, `issuer`, `bits`, `key_type`, `serialNumber`
      FROM KeyInfo
      WHERE `entity_id` = :Id AND `type` = :Type ORDER BY `order`;');
    $keyInfoHandler->bindParam(self::BIND_TYPE, $type);
    $oldKeyInfos = array();
    if ($this->oldExists) {
      $keyInfoHandler->bindParam(self::BIND_ID, $this->dbOldIdNr);
      $keyInfoHandler->execute();

      while ($keyInfo = $keyInfoHandler->fetch(PDO::FETCH_ASSOC)) {
        $oldKeyInfos[$keyInfo['serialNumber']][$keyInfo['use']] = 'removed';
      }
    }

    $keyInfoHandler->bindParam(self::BIND_ID, $this->dbIdNr);
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
        default :
      }
      $name = $keyInfo['name'] == '' ? '' : '(' . $keyInfo['name'] .')';

      if ($keyInfo['notValidAfter'] <= $timeNow ) {
        $error = ($validCertExists) ? self::HTML_CLASS_ALERT_WARNING : self::HTML_CLASS_ALERT_DANGER;
      } elseif ($keyInfo['notValidAfter'] <= $timeWarn ) {
        $error = self::HTML_CLASS_ALERT_WARNING;
      }

      if (($keyInfo['bits'] < 2048 && $keyInfo['key_type'] == "RSA") || $keyInfo['bits'] < 256) {
        $error = self::HTML_CLASS_ALERT_DANGER;
      }

      if (isset($oldKeyInfos[$keyInfo['serialNumber']][$keyInfo['use']])) {
        $state = 'dark';
        $oldKeyInfos[$keyInfo['serialNumber']][$keyInfo['use']] = 'same';
      } else {
        $state = 'success';
      }
      $baseLink = sprintf(
        '%s        <a href="?edit=%s&Entity=%d&oldEntity=%d&type=%s&use=%s&serialNumber=%s&order=%d&action=',
        "\n", $edit, $this->dbIdNr, $this->dbOldIdNr, $type,
        $keyInfo['use'], $keyInfo['serialNumber'], $keyInfo['order']);
      $links = $baseLink . 'UpdateUse"><i class="fas fa-pencil-alt"></i></a> ';
      $links .= $okRemove ? sprintf('%sDelete"><i class="fas fa-trash"></i></a> ', $baseLink) : '';
      $links .= $keyInfo['order'] > 0 ? sprintf('%sMoveUp"><i class="fas fa-arrow-up"></i></a> ', $baseLink) : '';
      $links .= $keyInfo['order'] < $maxOrder
        ? sprintf('%sMoveDown"><i class="fas fa-arrow-down"></i></a> ', $baseLink)
        : '';

      if (isset($_GET['action']) && $_GET['action'] == 'UpdateUse' && $keyInfo['order'] == $order) {
        $useLink = sprintf ('
          <form>
            <input type="hidden" name="edit" value="%s">
            <input type="hidden" name="Entity" value="%d">
            <input type="hidden" name="oldEntity" value="%d">
            <input type="hidden" name="type" value="%s">
            <input type="hidden" name="use" value="%s">
            <input type="hidden" name="serialNumber" value="%s">
            <input type="hidden" name="order" value="%d">
            <b>KeyUse = <select name="newUse">
              <option value="encryption"%s>encryption</option>
              <option value="signing"%s>signing</option>
              <option value="encryption & signing"%s>encryption & signing</option>
            </select></b>
            <input type="submit" name="action" value="Change">
          </form>%s       ',
          $edit, $this->dbIdNr, $this->dbOldIdNr, $type, $keyInfo['use'], $keyInfo['serialNumber'], $keyInfo['order'],
          $use == 'encryption' ? ' selected' : '', $use == 'signing' ? ' selected' : '',
          $use == 'encryption & signing' ? ' selected' : '', "\n");
      } else {
        $useLink = sprintf ('<b>KeyUse = "%s"</b>', htmlspecialchars($use));
      }

      printf('%s%s        <span class="text-%s text-truncate">%s %s</span>
        <ul%s>
          <li>notValidAfter = %s</li>
          <li>Subject = %s</li>
          <li>Issuer = %s</li>
          <li>Type / bits = %s / %d</li>
          <li>Serial Number = %s</li>
        </ul>',
        $links, "\n", $state, $useLink, $name, $error, $keyInfo['notValidAfter'], $keyInfo['subject'],
        $keyInfo['issuer'], $keyInfo['key_type'], $keyInfo['bits'], $keyInfo['serialNumber']);
    }

    printf('
        %s
        <a href="./?validateEntity=%d"><button>Back</button></a>
      </div><!-- end col -->
      <div class="col">',
      $addLink, $this->dbIdNr);
    if ($this->oldExists) {
      $keyInfoHandler->bindParam(self::BIND_ID, $this->dbOldIdNr);
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
          default :
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
        </ul>',
          "\n", $state, htmlspecialchars($use), $name, $keyInfo['notValidAfter'], $keyInfo['subject'], $keyInfo['issuer'],
          $keyInfo['key_type'], $keyInfo['bits'], $keyInfo['serialNumber']);
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
        $nextServiceIndexHandler = $this->metaDb->prepare(
          'SELECT MAX(Service_index) AS lastIndex FROM AttributeConsumingService WHERE entity_id = :Id;');
        $nextServiceIndexHandler->bindParam(self::BIND_ID, $this->dbIdNr);

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
          $isRequired = isset($_GET['isRequired'])
            && ($_GET['isRequired'] == 1
            || strtolower($_GET['isRequired']) == 'true')
            ? 1 : 0;
        }
      }
      if ($error) {
        printf (self::HTML_DIV_CLASS_ALERT_DANGER, $error);
      } else {
        $changed = false;
        $entityDescriptor = $this->getEntityDescriptor($this->newXml);

        # Find md:IDPSSODescriptor in XML
        $child = $entityDescriptor->firstChild;
        $ssoDescriptor = false;
        while ($child && ! $ssoDescriptor) {
          if ($child->nodeName == self::SAML_MD_SPSSODESCRIPTOR) {
            $ssoDescriptor = $child;
          }
          $child = $child->nextSibling;
        }
        switch ($_GET['action']) {
          case 'AddIndex' :
            if ($ssoDescriptor) {
              $changed = true;
              $child = $ssoDescriptor->firstChild;
              $attributeConsumingService = false;
              while ($child && ! $attributeConsumingService) {
                switch ($child->nodeName) {
                  case self::SAML_MD_SIGNATURE :
                  case self::SAML_MD_EXTENSIONS :
                  case self::SAML_MD_KEYDESCRIPTOR :
                  case self::SAML_MD_ARTIFACTRESOLUTIONSERVICE :
                  case self::SAML_MD_SINGLELOGOUTSERVICE :
                  case self::SAML_MD_MANAGENAMEIDSERVICE :
                  case self::SAML_MD_NAMEIDFORMAT :
                  case self::SAML_MD_ASSERTIONCONSUMERSERVICE :
                    break;
                  case self::SAML_MD_ATTRIBUTECONSUMINGSERVICE :
                    if ($child->getAttribute('index') == $indexValue)
                      $attributeConsumingService = $child;
                    break;
                  default :
                }
                $child = $child->nextSibling;
              }
              if (! $attributeConsumingService) {
                $attributeConsumingService = $this->newXml->createElement(self::SAML_MD_ATTRIBUTECONSUMINGSERVICE);
                $attributeConsumingService->setAttribute('index', $indexValue);
                $ssoDescriptor->appendChild($attributeConsumingService);

                $addServiceIndexHandler = $this->metaDb->prepare(
                  'INSERT INTO AttributeConsumingService (entity_id, Service_index) VALUES (:Id, :Index);');
                $serviceElementAddHandler = $this->metaDb->prepare(
                  'INSERT INTO AttributeConsumingService_Service (entity_id, Service_index, element, lang, data)
                  VALUES ( :Id, :Index, :Element, :Lang, :Data );');
                $mduiHandler = $this->metaDb->prepare(
                  "SELECT lang, data
                  FROM Mdui
                  WHERE entity_id = :Id AND element = 'DisplayName' AND type = 'SPSSO'
                  ORDER BY lang;");

                $addServiceIndexHandler->bindParam(self::BIND_ID, $this->dbIdNr);
                $addServiceIndexHandler->bindParam(self::BIND_INDEX, $indexValue);
                $addServiceIndexHandler->execute();

                $serviceElementAddHandler->bindParam(self::BIND_ID, $this->dbIdNr);
                $serviceElementAddHandler->bindParam(self::BIND_INDEX, $indexValue);
                $serviceElementAddHandler->bindValue(self::BIND_ELEMENT, 'ServiceName');

                $mduiHandler->bindParam(self::BIND_ID, $this->dbIdNr);
                $mduiHandler->execute();
                while ($mdui = $mduiHandler->fetch(PDO::FETCH_ASSOC)) {
                  $attributeConsumingServiceElement = $this->newXml->createElement(self::SAML_MD_SERVICENAME,
                    $mdui['data']);
                  $attributeConsumingServiceElement->setAttribute('xml:lang', $mdui['lang']);
                  $attributeConsumingService->appendChild($attributeConsumingServiceElement);

                  $serviceElementAddHandler->bindParam(self::BIND_LANG, $mdui['lang']);
                  $serviceElementAddHandler->bindParam(self::BIND_DATA, $mdui['data']);
                  $serviceElementAddHandler->execute();
                }
              }
            }
            break;
          case 'DeleteIndex' :
            if ($ssoDescriptor) {
              $child = $ssoDescriptor->firstChild;
              $attributeConsumingService = false;
              while ($child && ! $attributeConsumingService) {
                if ($child->nodeName == self::SAML_MD_ATTRIBUTECONSUMINGSERVICE
                  && $child->getAttribute('index') == $indexValue) {
                  $attributeConsumingService = $child;
                }
                $child = $child->nextSibling;
              }
              if ($attributeConsumingService) {
                $changed = true;
                $ssoDescriptor->removeChild($attributeConsumingService);
                $serviceRemoveHandler = $this->metaDb->prepare(
                  'DELETE FROM AttributeConsumingService WHERE entity_id = :Id AND Service_index = :Index;');
                $serviceRemoveHandler->bindParam(self::BIND_ID, $this->dbIdNr);
                $serviceRemoveHandler->bindParam(self::BIND_INDEX, $indexValue);
                $serviceRemoveHandler->execute();

                $serviceElementRemoveHandler = $this->metaDb->prepare(
                  'DELETE FROM AttributeConsumingService_Service WHERE entity_id = :Id AND Service_index = :Index;');
                $serviceElementRemoveHandler->bindParam(self::BIND_ID, $this->dbIdNr);
                $serviceElementRemoveHandler->bindParam(self::BIND_INDEX, $indexValue);
                $serviceElementRemoveHandler->execute();

                $requestedAttributeRemoveHandler = $this->metaDb->prepare(
                  'DELETE FROM AttributeConsumingService_RequestedAttribute WHERE entity_id = :Id AND Service_index = :Index;');
                $requestedAttributeRemoveHandler->bindParam(self::BIND_ID, $this->dbIdNr);
                $requestedAttributeRemoveHandler->bindParam(self::BIND_INDEX, $indexValue);
                $requestedAttributeRemoveHandler->execute();
              }
            }
            break;
          case 'Add' :
            if ($ssoDescriptor) {
              $changed = true;
              $child = $ssoDescriptor->firstChild;
              $attributeConsumingService = false;
              while ($child && ! $attributeConsumingService) {
                switch ($child->nodeName) {
                  case self::SAML_MD_SIGNATURE :
                  case self::SAML_MD_EXTENSIONS :
                  case self::SAML_MD_KEYDESCRIPTOR :
                  case self::SAML_MD_ARTIFACTRESOLUTIONSERVICE :
                  case self::SAML_MD_SINGLELOGOUTSERVICE :
                  case self::SAML_MD_MANAGENAMEIDSERVICE :
                  case self::SAML_MD_NAMEIDFORMAT :
                  case self::SAML_MD_ASSERTIONCONSUMERSERVICE :
                    break;
                  case self::SAML_MD_ATTRIBUTECONSUMINGSERVICE :
                    if ($child->getAttribute('index') == $indexValue)
                      $attributeConsumingService = $child;
                    break;
                  default :
                }
                $child = $child->nextSibling;
              }
              if (! $attributeConsumingService) {
                $attributeConsumingService = $this->newXml->createElement(self::SAML_MD_ATTRIBUTECONSUMINGSERVICE);
                $attributeConsumingService->setAttribute('index', $indexValue);
                $ssoDescriptor->appendChild($attributeConsumingService);
                $addServiceIndexHandler = $this->metaDb->prepare(
                  'INSERT INTO AttributeConsumingService (entity_id, Service_index) VALUES (:Id, :Index);');
                $addServiceIndexHandler->bindParam(self::BIND_ID, $this->dbIdNr);
                $addServiceIndexHandler->bindParam(self::BIND_INDEX, $indexValue);
                $addServiceIndexHandler->execute();
              }
              $child = $attributeConsumingService->firstChild;
              $attributeConsumingServiceElement = false;
              $update = false;
              while ($child && ! $attributeConsumingServiceElement) {
                if (
                  ($placement == 3 && $child->nodeName == $elementmd && $child->getAttribute('Name') == $name)
                  || ($placement != 3 && $child->nodeName == $elementmd
                    && strtolower($child->getAttribute('xml:lang')) == $langvalue )
                  ) {
                  $attributeConsumingServiceElement = $child;
                  $update = true;
                } elseif (isset ($this->orderAttributeRequestingService[$child->nodeName])
                  && $this->orderAttributeRequestingService[$child->nodeName] <= $placement) {
                  $child = $child->nextSibling;
                } else {
                  if ($placement < 3 ) {
                    $attributeConsumingServiceElement = $this->newXml->createElement($elementmd, $value);
                  } else {
                    $attributeConsumingServiceElement = $this->newXml->createElement($elementmd);
                  }
                  $attributeConsumingService->insertBefore($attributeConsumingServiceElement, $child);
                }
              }
              if (!$attributeConsumingServiceElement) {
                if ($placement < 3 ) {
                  $attributeConsumingServiceElement = $this->newXml->createElement($elementmd, $value);
                } else {
                  $attributeConsumingServiceElement = $this->newXml->createElement($elementmd);
                }
                $attributeConsumingService->appendChild($attributeConsumingServiceElement);
              }
              if ($update) {
                if ($placement < 3 ) {
                  $attributeConsumingServiceElement->setAttribute('xml:lang', $langvalue);
                  $attributeConsumingServiceElement->nodeValue = $value;
                  $serviceElementUpdateHandler = $this->metaDb->prepare(
                    'UPDATE AttributeConsumingService_Service
                    SET data = :Data
                    WHERE entity_id = :Id AND Service_index = :Index AND element = :Element AND lang = :Lang;');
                  $serviceElementUpdateHandler->bindParam(self::BIND_ID, $this->dbIdNr);
                  $serviceElementUpdateHandler->bindParam(self::BIND_INDEX, $indexValue);
                  $serviceElementUpdateHandler->bindParam(self::BIND_ELEMENT, $elementValue);
                  $serviceElementUpdateHandler->bindParam(self::BIND_LANG, $langvalue);
                  $serviceElementUpdateHandler->bindParam(self::BIND_DATA, $value);
                  $serviceElementUpdateHandler->execute();
                } else {
                  if ($friendlyName != '' ) {
                    $attributeConsumingServiceElement->setAttribute('FriendlyName', $friendlyName);
                  }
                  $attributeConsumingServiceElement->setAttribute('Name', $name);
                  if ($nameFormat != '' )
                    $attributeConsumingServiceElement->setAttribute('NameFormat', $nameFormat);
                  $attributeConsumingServiceElement->setAttribute('isRequired', $isRequired ? 'true' : 'false');
                  $requestedAttributeUpdateHandler = $this->metaDb->prepare(
                    'UPDATE AttributeConsumingService_RequestedAttribute
                    SET FriendlyName = :FriendlyName, NameFormat = :NameFormat, isRequired = :IsRequired
                    WHERE entity_id = :Id AND Service_index = :Index AND Name = :Name;');
                  $requestedAttributeUpdateHandler->bindParam(self::BIND_ID, $this->dbIdNr);
                  $requestedAttributeUpdateHandler->bindParam(self::BIND_INDEX, $indexValue);
                  $requestedAttributeUpdateHandler->bindParam(self::BIND_FRIENDLYNAME, $friendlyName);
                  $requestedAttributeUpdateHandler->bindParam(self::BIND_NAME, $name);
                  $requestedAttributeUpdateHandler->bindParam(self::BIND_NAMEFORMAT, $nameFormat);
                  $requestedAttributeUpdateHandler->bindParam(self::BIND_ISREQUIRED, $isRequired);
                  $requestedAttributeUpdateHandler->execute();
                }
              } else {
                # Added NEW, Insert into DB
                if ($placement < 3 ) {
                  $attributeConsumingServiceElement->setAttribute('xml:lang', $langvalue);
                  $serviceElementAddHandler = $this->metaDb->prepare(
                    'INSERT INTO AttributeConsumingService_Service (entity_id, Service_index, element, lang, data)
                    VALUES ( :Id, :Index, :Element, :Lang, :Data );');
                  $serviceElementAddHandler->bindParam(self::BIND_ID, $this->dbIdNr);
                  $serviceElementAddHandler->bindParam(self::BIND_INDEX, $indexValue);
                  $serviceElementAddHandler->bindParam(self::BIND_ELEMENT, $elementValue);
                  $serviceElementAddHandler->bindParam(self::BIND_LANG, $langvalue);
                  $serviceElementAddHandler->bindParam(self::BIND_DATA, $value);
                  $serviceElementAddHandler->execute();
                } else {
                  if ($friendlyName != '' )
                    $attributeConsumingServiceElement->setAttribute('FriendlyName', $friendlyName);
                  $attributeConsumingServiceElement->setAttribute('Name', $name);
                  if ($nameFormat != '' )
                    $attributeConsumingServiceElement->setAttribute('NameFormat', $nameFormat);
                  $attributeConsumingServiceElement->setAttribute('isRequired', $isRequired ? 'true' : 'false');
                  $requestedAttributeAddHandler = $this->metaDb->prepare(
                    'INSERT INTO AttributeConsumingService_RequestedAttribute
                    (entity_id, Service_index, FriendlyName, Name, NameFormat, isRequired)
                    VALUES ( :Id, :Index, :FriendlyName, :Name, :NameFormat, :IsRequired);');
                  $requestedAttributeAddHandler->bindParam(self::BIND_ID, $this->dbIdNr);
                  $requestedAttributeAddHandler->bindParam(self::BIND_INDEX, $indexValue);
                  $requestedAttributeAddHandler->bindParam(self::BIND_FRIENDLYNAME, $friendlyName);
                  $requestedAttributeAddHandler->bindParam(self::BIND_NAME, $name);
                  $requestedAttributeAddHandler->bindParam(self::BIND_NAMEFORMAT, $nameFormat);
                  $requestedAttributeAddHandler->bindParam(self::BIND_ISREQUIRED, $isRequired);
                  $requestedAttributeAddHandler->execute();
                }
              }
            }
            break;
          case 'Delete' :
            if ($ssoDescriptor) {
              $child = $ssoDescriptor->firstChild;
              $attributeConsumingService = false;
              while ($child && ! $attributeConsumingService) {
                if ($child->nodeName == self::SAML_MD_ATTRIBUTECONSUMINGSERVICE
                  && $child->getAttribute('index') == $indexValue)
                  $attributeConsumingService = $child;
                $child = $child->nextSibling;
              }
              if ($attributeConsumingService) {
                $child = $attributeConsumingService->firstChild;
                $attributeConsumingServiceElement = false;
                $moreElements = false;
                while ($child && ! $attributeConsumingServiceElement) {
                  if (
                    ($placement == 3 && $child->nodeName == $elementmd && $child->getAttribute('Name') == $name)
                    || ($placement != 3 && $child->nodeName == $elementmd
                      && strtolower($child->getAttribute('xml:lang')) == $langvalue )) {
                    $attributeConsumingServiceElement = $child;
                  } else {
                    $moreElements = true;
                  }
                  $child = $child->nextSibling;
                }
                $moreElements = $moreElements ? true : $child;
                if ($attributeConsumingServiceElement) {
                  # Remove Node
                  $changed = true;
                  $attributeConsumingService->removeChild($attributeConsumingServiceElement);
                  if (! $moreElements) {
                    $ssoDescriptor->removeChild($attributeConsumingService);
                    $serviceRemoveHandler = $this->metaDb->prepare(
                      'DELETE FROM AttributeConsumingService WHERE entity_id = :Id AND Service_index = :Index;');
                    $serviceRemoveHandler->bindParam(self::BIND_ID, $this->dbIdNr);
                    $serviceRemoveHandler->bindParam(self::BIND_INDEX, $indexValue);
                    $serviceRemoveHandler->execute();
                  }
                  if ($placement < 3 ) {
                    $serviceElementRemoveHandler = $this->metaDb->prepare(
                      'DELETE FROM AttributeConsumingService_Service
                      WHERE entity_id = :Id AND Service_index = :Index AND element = :Element AND lang = :Lang;');
                    $serviceElementRemoveHandler->bindParam(self::BIND_ID, $this->dbIdNr);
                    $serviceElementRemoveHandler->bindParam(self::BIND_INDEX, $indexValue);
                    $serviceElementRemoveHandler->bindParam(self::BIND_ELEMENT, $elementValue);
                    $serviceElementRemoveHandler->bindParam(self::BIND_LANG, $langvalue);
                    $serviceElementRemoveHandler->execute();
                  } else {
                    $requestedAttributeRemoveHandler = $this->metaDb->prepare(
                      'DELETE FROM AttributeConsumingService_RequestedAttribute
                      WHERE entity_id = :Id AND Service_index = :Index AND Name = :Name;');
                    $requestedAttributeRemoveHandler->bindParam(self::BIND_ID, $this->dbIdNr);
                    $requestedAttributeRemoveHandler->bindParam(self::BIND_INDEX, $indexValue);
                    $requestedAttributeRemoveHandler->bindParam(self::BIND_NAME, $name);
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
          default :
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

    $serviceIndexHandler = $this->metaDb->prepare(
      'SELECT Service_index FROM AttributeConsumingService
      WHERE entity_id = :Id ORDER BY Service_index;');
    $serviceElementHandler = $this->metaDb->prepare(
      'SELECT element, lang, data FROM AttributeConsumingService_Service
      WHERE entity_id = :Id AND Service_index = :Index ORDER BY element DESC, lang;');
    $serviceElementHandler->bindParam(self::BIND_INDEX, $index);
    $requestedAttributeHandler = $this->metaDb->prepare(
      'SELECT FriendlyName, Name, NameFormat, isRequired FROM AttributeConsumingService_RequestedAttribute
      WHERE entity_id = :Id AND Service_index = :Index ORDER BY isRequired DESC, FriendlyName;');
    $requestedAttributeHandler->bindParam(self::BIND_INDEX, $index);

    $serviceIndexHandler->bindParam(self::BIND_ID, $this->dbOldIdNr);
    $serviceElementHandler->bindParam(self::BIND_ID, $this->dbOldIdNr);
    $requestedAttributeHandler->bindParam(self::BIND_ID, $this->dbOldIdNr);
    $serviceIndexHandler->execute();
    while ($serviceIndex = $serviceIndexHandler->fetch(PDO::FETCH_ASSOC)) {
      $index = $serviceIndex['Service_index'];
      $oldServiceIndexes[$index] = $index;
      $oldServiceElements[$index] = array();
      $oldRequestedAttributes[$index] = array();
      $serviceElementHandler->execute();
      while ($serviceElement = $serviceElementHandler->fetch(PDO::FETCH_ASSOC)) {
        $oldServiceElements[$index][$serviceElement['element']][$serviceElement['lang']] = array(
          'value' => $serviceElement['data'], 'state' => 'removed');
      }
      $requestedAttributeHandler->execute();
      while ($requestedAttribute = $requestedAttributeHandler->fetch(PDO::FETCH_ASSOC)) {
        $oldRequestedAttributes[$index][$requestedAttribute['Name']] = array(
          'isRequired' => $requestedAttribute['isRequired'],
          'friendlyName' => $requestedAttribute['FriendlyName'],
          'nameFormat' => $requestedAttribute['NameFormat'],
          'state' => 'removed');
      }
    }
    printf ('%s    <div class="row">%s      <div class="col">', "\n", "\n");
    $serviceIndexHandler->bindParam(self::BIND_ID, $this->dbIdNr);
    $serviceElementHandler->bindParam(self::BIND_ID, $this->dbIdNr);
    $requestedAttributeHandler->bindParam(self::BIND_ID, $this->dbIdNr);
    $serviceIndexHandler->execute();
    while ($serviceIndex = $serviceIndexHandler->fetch(PDO::FETCH_ASSOC)) {
      $index = $serviceIndex['Service_index'];
      if ($indexValue == $index) {
        printf ('
        <b>Index = %d</b>
          <a href="./?edit=AttributeConsumingService&Entity=%d&oldEntity=%d&index=%d&action=DeleteIndex">
          <i class="fa fa-trash"></i></a>
        <ul>',
          $index, $this->dbIdNr, $this->dbOldIdNr, $index);
      } else {
        printf ('
        <b>Index = %d</b>
          <a href="./?edit=AttributeConsumingService&Entity=%d&oldEntity=%d&index=%d&action=SetIndex">
          <i class="fa fa-pencil-alt"></i></a>
        <ul>',
          $index, $this->dbIdNr, $this->dbOldIdNr, $index);
      }
      $serviceElementHandler->execute();
      while ($serviceElement = $serviceElementHandler->fetch(PDO::FETCH_ASSOC)) {
        if (isset($oldServiceElements[$index][$serviceElement['element']][$serviceElement['lang']])
          && $oldServiceElements[$index][$serviceElement['element']][$serviceElement['lang']]['value']
            == $serviceElement['data']) {
          $state = 'dark';
          $oldServiceElements[$index][$serviceElement['element']][$serviceElement['lang']]['state'] = 'same';
        } else {
          $state = 'success';
        }
        $baseLink = sprintf('<a href ="?edit=AttributeConsumingService&Entity=%d&oldEntity=%d&index=%s&element=%s&lang=%s&value=%s&action=',
          $this->dbIdNr, $this->dbOldIdNr, $index, $serviceElement['element'],
          $serviceElement['lang'], urlencode($serviceElement['data']));
        $links = $baseLink . self::HTML_COPY . $baseLink . self::HTML_DELETE;
        printf('%s          <li>%s<span class="text-%s">%s[%s] = %s</span></li>',
          "\n", $links, $state, $serviceElement['element'], $serviceElement['lang'], $serviceElement['data']);
      }
      print "\n" . self::HTML_END_UL;
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
                <div class="col">',
          $this->dbIdNr, $this->dbOldIdNr, $index,
          $elementValue == 'ServiceName' ? ' selected' : '',
          $elementValue == 'ServiceDescription' ? ' selected' : '');
        $this->showLangSelector($langvalue);
        printf('            </div>
              </div>
              <div class="row">
                <div class="col-2">Value: </div>
                <div class="col"><input type="textbox" size="60" name="value" value="%s"></div>
              </div>
              <button type="submit" name="action" value="Add">Add/Update</button>
            </form>', htmlspecialchars($value));
      }
      $requestedAttributeHandler->execute();
      printf("\n        <b>RequestedAttributes</b>\n        <ul>");
      while ($requestedAttribute = $requestedAttributeHandler->fetch(PDO::FETCH_ASSOC)) {
        $error = '';
        if ($requestedAttribute['FriendlyName'] == '') {
          if (isset($this->FriendlyNames[$requestedAttribute['Name']])) {
            $friendlyNameDisplay = sprintf('(%s)', $this->FriendlyNames[$requestedAttribute['Name']]['desc']);
            if (! $this->FriendlyNames[$requestedAttribute['Name']]['swamidStd'])
              $error = self::HTML_CLASS_ALERT_WARNING;
          } else {
            $friendlyNameDisplay = '(Unknown)';
            $error = self::HTML_CLASS_ALERT_WARNING;
          }
        } else {
          $friendlyNameDisplay = $requestedAttribute['FriendlyName'];
          if (isset ($this->FriendlyNames[$requestedAttribute['Name']])) {
            if ($requestedAttribute['FriendlyName'] != $this->FriendlyNames[$requestedAttribute['Name']]['desc']
              || ! $this->FriendlyNames[$requestedAttribute['Name']]['swamidStd']) {
              $error = self::HTML_CLASS_ALERT_WARNING;
            }
          } else {
            $error = self::HTML_CLASS_ALERT_WARNING;
          }
        }
        if (isset($oldRequestedAttributes[$index][$requestedAttribute['Name']])
          && $oldRequestedAttributes[$index][$requestedAttribute['Name']]['isRequired']
            == $requestedAttribute['isRequired']) {
          $state = 'dark';
          $oldRequestedAttributes[$index][$requestedAttribute['Name']]['state'] = 'same';
        } else {
          $state = 'success';
        }
        $baseLink = sprintf('<a href ="?edit=AttributeConsumingService&Entity=%d&oldEntity=%d&index=%s&element=RequestedAttribute&name=%s&isRequired=%d&action=',
          $this->dbIdNr, $this->dbOldIdNr, $index, $requestedAttribute['Name'], $requestedAttribute['isRequired']);
        $links = $baseLink . self::HTML_COPY . $baseLink . self::HTML_DELETE;
        $existingRequestedAttribute[$requestedAttribute['Name']] = true;
        printf('%s            <li%s>%s<span class="text-%s"><b>%s</b> - %s%s</span></li>',
          "\n", $error, $links, $state, $friendlyNameDisplay, $requestedAttribute['Name'],
          $requestedAttribute['isRequired'] == '1' ? ' (Required)' : '');
      }
      print "\n" . self::HTML_END_UL;

      if ($indexValue == $index) {
        print "<h5>Available attributes:</h5>";
        foreach ($this->FriendlyNames as $nameL => $data) {
          if ($data['swamidStd']) {
            if (isset($existingRequestedAttribute[$nameL])) {
              printf('<b>%s</b> - %s<br>%s', $data['desc'], $nameL,  "\n");
            } else {
              printf('<a href="?edit=AttributeConsumingService&Entity=%d&oldEntity=%d&element=RequestedAttribute&index=%d&name=%s&friendlyName=%s&NameFormat=urn:oasis:names:tc:SAML:2.0:attrname-format:uri&isRequired=1&action=Add">[copy]</a> <b>%s</b> - %s<br>%s',
                $this->dbIdNr, $this->dbOldIdNr, $index, $nameL, $data['desc'], $data['desc'], $nameL,  "\n");
            }
          }
        }
        print '<br><h5>Not recommended attributes:</h5>';
        foreach ($this->FriendlyNames as $nameL => $data) {
          if (! $data['swamidStd']) {
            if (substr($nameL, 0, 27) == 'urn:mace:dir:attribute-def:') {
              $samlVer = ' (SAML1)';
              $nf = 'urn:mace:shibboleth:1.0:attributeNamespace:uri';
            } else {
              $samlVer = '';
              $nf = self::SAMLNF_URI;
            }
            if (isset($existingRequestedAttribute[$nameL])) {
              printf('<b>%s%s</b> - %s<br>%s', $data['desc'], $samlVer, $nameL,  "\n");
            } else {
              printf('<a href="?edit=AttributeConsumingService&Entity=%d&oldEntity=%d&element=RequestedAttribute&index=%d&name=%s&friendlyName=%s&NameFormat=%s&isRequired=1&action=Add">[copy]</a> <b>%s%s</b> - %s<br>%s',
                $this->dbIdNr, $this->dbOldIdNr, $index, $nameL, $data['desc'], $nf,
                $data['desc'], $samlVer, $nameL,  "\n");
            }
          }
        }
        printf('
    <br>
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
                <option value="urn:oasis:names:tc:SAML:2.0:attrname-format:uri" %s>urn:oasis:names:tc:SAML:2.0:attrname-format:uri</option>
                <option value="urn:mace:shibboleth:1.0:attributeNamespace:uri" %s>urn:mace:shibboleth:1.0:attributeNamespace:uri</option>
              </select>
            </div>
          </div>
          <button type="submit" name="action" value="Add">Add/Update</button>
        </form>', $this->dbIdNr, $this->dbOldIdNr, $index, htmlspecialchars($name), $isRequired ? " checked" : '', htmlspecialchars($friendlyName), $nameFormat == self::SAMLNF_URI ? ' selected' : '', $nameFormat == 'urn:mace:shibboleth:1.0:attributeNamespace:uri' ? ' selected' : '');
      }
    }
    printf('        <a href="./?validateEntity=%d"><button>Back</button></a>
        <a href="./?edit=AttributeConsumingService&Entity=%d&oldEntity=%d&action=AddIndex"><button>Add Index</button></a>
      </div><!-- end col -->
      <div class="col">',
      $this->dbIdNr, $this->dbIdNr, $this->dbOldIdNr);
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
                $copy = sprintf('<a href ="?edit=AttributeConsumingService&Entity=%d&oldEntity=%d&index=%s&element=%s&lang=%s&value=%s&action=Add">[copy]</a> ',
                  $this->dbIdNr, $this->dbOldIdNr, $index, $element, $lang, urlencode($data['value']));
                $state = 'danger';
                break;
              default :
                $copy = '';
                $state = 'danger';
            }
            printf('%s          <li>%s<span class="text-%s">%s[%s] = %s</span></li>', "\n",
              $copy, $state, $element, $lang, $data['value']);
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
              $copy = sprintf('<a href ="?edit=AttributeConsumingService&Entity=%d&oldEntity=%d&index=%s&element=RequestedAttribute&name=%s&isRequired=%s&NameFormat=%s&action=Add">[copy]</a> ',
                $this->dbIdNr, $this->dbOldIdNr, $index, $name, $data['isRequired'], $data['nameFormat']);
              $state = 'danger';
              break;
            default :
              $copy = '';
              $state = 'danger';
          }
          printf('%s            <li>%s<span class="text-%s"><b>%s</b> - %s%s</span></li>',
            "\n", $copy, $state,
            $data['friendlyName'] == '' ? '(' . $this->FriendlyNames[$name]['desc'] .')' : $data['friendlyName'],
            htmlspecialchars($name), $data['isRequired'] == '1' ? ' (Required)' : '');
        }
        printf("\n  %s</li>\n%s", self::HTML_END_UL, self::HTML_END_UL);
      }
    }
    print "\n      </div><!-- end col -->\n    </div><!-- end row -->\n";
  }
  private function editOrganization() {
    $organizationHandler = $this->metaDb->prepare(
      'SELECT element, lang, data FROM Organization WHERE entity_id = :Id ORDER BY element, lang;');

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
        printf (self::HTML_DIV_CLASS_ALERT_DANGER, $error);
      } else {
        $entityDescriptor = $this->getEntityDescriptor($this->newXml);

        # Find md:Extensions in XML
        $child = $entityDescriptor->firstChild;
        $organization = false;
        while ($child && ! $organization) {
          switch ($child->nodeName) {
            case self::SAML_MD_ORGANIZATION :
              $organization = $child;
              break;
            case self::SAML_MD_CONTACTPERSON :
            case self::SAML_MD_ADDITIONALMETADATALOCATION :
              $organization = $this->newXml->createElement(self::SAML_MD_ORGANIZATION);
              $entityDescriptor->insertBefore($organization, $child);
              break;
            default :
          }
          $child = $child->nextSibling;
        }

        $changed = false;
        switch ($_GET['action']) {
          case 'Add' :
            if (! $organization) {
              # Add if missing
              $organization = $this->newXml->createElement(self::SAML_MD_ORGANIZATION);
              $entityDescriptor->appendChild($organization);
            }

            # Find md:Organization* in XML
            $child = $organization->firstChild;
            $organizationElement = false;
            $newOrg = true;
            if ($elementmd == self::SAML_MD_ORGANIZATIONURL) {
              $value = str_replace(' ', '+', $value);
            }
            while ($child && ! $organizationElement) {
              if (strtolower($child->getAttribute('xml:lang')) == $lang && $child->nodeName == $elementmd) {
                $organizationElement = $child;
                $newOrg = false;
              } elseif (isset ($this->orderOrganization[$child->nodeName])
                && $this->orderOrganization[$child->nodeName] <= $placement) {
                $child = $child->nextSibling;
              } else {
                $organizationElement = $this->newXml->createElement($elementmd, $value);
                $organizationElement->setAttribute('xml:lang', $lang);
                $organization->insertBefore($organizationElement, $child);
              }
            }
            if (! $organizationElement) {
              # Add if missing
              $organizationElement = $this->newXml->createElement($elementmd, $value);
              $organizationElement->setAttribute('xml:lang', $lang);
              $organization->appendChild($organizationElement);
            }
            if ($newOrg) {
              # Add if missing
              $organizationAddHandler = $this->metaDb->prepare(
                'INSERT INTO Organization (entity_id, element, lang, data) VALUES (:Id, :Element, :Lang, :Data) ;');
              $organizationAddHandler->bindParam(self::BIND_ID, $this->dbIdNr);
              $organizationAddHandler->bindParam(self::BIND_ELEMENT, $element);
              $organizationAddHandler->bindParam(self::BIND_LANG, $lang);
              $organizationAddHandler->bindParam(self::BIND_DATA, $value);
              $organizationAddHandler->execute();
              $changed = true;
            } elseif ($organizationElement->nodeValue != $value) {
              $organizationElement->nodeValue = $value;
              $organizationUpdateHandler = $this->metaDb->prepare(
                'UPDATE Organization SET data = :Data WHERE entity_id = :Id AND element = :Element AND lang = :Lang;');
              $organizationUpdateHandler->bindParam(self::BIND_ID, $this->dbIdNr);
              $organizationUpdateHandler->bindParam(self::BIND_ELEMENT, $element);
              $organizationUpdateHandler->bindParam(self::BIND_LANG, $lang);
              $organizationUpdateHandler->bindParam(self::BIND_DATA, $_GET['value']);
              $organizationUpdateHandler->execute();
              $changed = true;
            }
            break;
          case 'Delete' :
            if ($organization) {
              $child = $organization->firstChild;
              $organizationElement = false;
              $moreOrganizationElements = false;
              while ($child && ! $organizationElement) {
                if (strtolower($child->getAttribute('xml:lang')) == $lang && $child->nodeName == $elementmd) {
                  $organizationElement = $child;
                }
                $child = $child->nextSibling;
                $moreOrganizationElements = ($moreOrganizationElements) ? true : $child;
              }

              if ($organizationElement) {
                $organization->removeChild($organizationElement);
                if (! $moreOrganizationElements) $entityDescriptor->removeChild($organization);

                $organizationRemoveHandler = $this->metaDb->prepare(
                  'DELETE FROM Organization
                  WHERE entity_id = :Id AND element = :Element AND lang = :Lang AND data = :Data;');
                $organizationRemoveHandler->bindParam(self::BIND_ID, $this->dbIdNr);
                $organizationRemoveHandler->bindParam(self::BIND_ELEMENT, $element);
                $organizationRemoveHandler->bindParam(self::BIND_LANG, $lang);
                $organizationRemoveHandler->bindParam(self::BIND_DATA, $value);
                $organizationRemoveHandler->execute();
                $changed = true;
              }
            }
            $element = '';
            $elementmd = '';
            $lang = '';
            $value = '';
            break;
          default :
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
    $organizationHandler->bindParam(self::BIND_ID, $this->dbOldIdNr);
    $organizationHandler->execute();
    while ($organization = $organizationHandler->fetch(PDO::FETCH_ASSOC)) {
      if (! isset($oldOrganizationElements[$organization['element']]) )
        $oldOrganizationElements[$organization['element']] = array();
      $oldOrganizationElements[$organization['element']][$organization['lang']] = array(
        'value' => $organization['data'], 'state' => 'removed');
    }

    $existingOrganizationElements = array();
    $organizationHandler->bindParam(self::BIND_ID, $this->dbIdNr);
    $organizationHandler->execute();
    while ($organization = $organizationHandler->fetch(PDO::FETCH_ASSOC)) {
      if (! isset($existingOrganizationElements[$organization['element']]) )
        $existingOrganizationElements[$organization['element']] = array();
      if (isset($oldOrganizationElements[$organization['element']][$organization['lang']])
        && $oldOrganizationElements[$organization['element']][$organization['lang']]['value']
          == $organization['data']) {
        $state = 'dark';
        $oldOrganizationElements[$organization['element']][$organization['lang']]['state'] = 'same';
      } else { $state = 'success'; }
      $baseLink = sprintf('<a href="?edit=Organization&Entity=%d&oldEntity=%d&element=%s&lang=%s&value=%s&action=',
        $this->dbIdNr, $this->dbOldIdNr, $organization['element'], $organization['lang'], $organization['data']);
      $links = $baseLink . self::HTML_COPY . $baseLink . self::HTML_DELETE;
      printf ('%s          <li>%s<span class="text-%s">%s[%s] = %s</span></li>',
        "\n", $links, $state, $organization['element'], $organization['lang'], $organization['data']);
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
            <div class="col">',
      $this->dbIdNr, $this->dbOldIdNr, $element == 'OrganizationName' ? ' selected' : '',
      $element == 'OrganizationDisplayName' ? ' selected' : '', $element == 'OrganizationURL' ? ' selected' : '',
      $element == 'Extensions' ? ' selected' : '');
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
      <div class="col">%s', htmlspecialchars($value), "\n");

    $organizationHandler->bindParam(self::BIND_ID, $this->dbOldIdNr);
    $organizationHandler->execute();
    print ('        <ul>');
    while ($organization = $organizationHandler->fetch(PDO::FETCH_ASSOC)) {
      $state = ($oldOrganizationElements[$organization['element']][$organization['lang']]['state'] == 'same')
        ? 'dark'
        : 'danger';
      $addLink =  (isset($existingOrganizationElements[$organization['element']][$organization['lang']]) )
        ? ''
        : sprintf('<a href="?edit=Organization&Entity=%d&oldEntity=%d&element=%s&lang=%s&value=%s&action=Add">[copy]</a> ',
          $this->dbIdNr, $this->dbOldIdNr, $organization['element'], $organization['lang'],$organization['data']);
      printf ('%s          <li>%s<span class="text-%s">%s[%s] = %s</span></li>',
        "\n", $addLink, $state, $organization['element'], $organization['lang'], $organization['data']);
    }
    print ("\n        <ul>");
    print "\n      </div><!-- end col -->\n    </div><!-- end row -->\n";
  }
  private function editContactPersons(){
    $contactPersonHandler = $this->metaDb->prepare(
      'SELECT * FROM ContactPerson WHERE entity_id = :Id ORDER BY contactType;');

    if (isset($_GET['action'])
      && isset($_GET['type'])
      && isset($_GET['part'])
      && isset($_GET['value'])
      && trim($_GET['value']) != '' ) {
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
        printf ('Missing %s', htmlspecialchars($part));
        exit();
      }

      $value = ($part == 'EmailAddress' && substr($_GET['value'],0,7) <> 'mailto:')
        ? 'mailto:'.trim($_GET['value'])
        : trim($_GET['value']);

      $entityDescriptor = $this->getEntityDescriptor($this->newXml);

      # Find md:Extensions in XML
      $child = $entityDescriptor->firstChild;
      $contactPerson = false;

      switch ($_GET['action']) {
        case 'Add' :
          $value = ($part == 'EmailAddress' && substr($_GET['value'],0,7) <> 'mailto:') ? 'mailto:'.trim($_GET['value']) : trim($_GET['value']);
          while ($child && ! $contactPerson) {
            switch ($child->nodeName) {
              case self::SAML_MD_CONTACTPERSON :
                if ($child->getAttribute('contactType') == $type) {
                  if ($subType) {
                    if ($child->getAttribute('remd:contactType') == $subType) {
                      $contactPerson = $child;
                    }
                  } else {
                    $contactPerson = $child;
                  }
                }
                break;
              case self::SAML_MD_ADDITIONALMETADATALOCATION :
                $contactPerson = $this->newXml->createElement(self::SAML_MD_CONTACTPERSON);
                $contactPerson->setAttribute('contactType', $type);
                if ($subType) {
                  $entityDescriptor->setAttributeNS(self::SAMLXMLNS_URI, 'xmlns:remd', 'http://refeds.org/metadata');
                  $contactPerson->setAttribute('remd:contactType', $subType);
                }
                $entityDescriptor->insertBefore($contactPerson, $child);
                break;
            }
            $child = $child->nextSibling;
          }
          if (! $contactPerson) {
            # Add if missing
            $contactPerson = $this->newXml->createElement(self::SAML_MD_CONTACTPERSON);
            $contactPerson->setAttribute('contactType', $type);
            if ($subType) {
              $entityDescriptor->setAttributeNS(self::SAMLXMLNS_URI, 'xmlns:remd', 'http://refeds.org/metadata');
              $contactPerson->setAttribute('remd:contactType', $subType);
            }
            $entityDescriptor->appendChild($contactPerson);

            $contactPersonAddHandler = $this->metaDb->prepare('INSERT INTO ContactPerson (entity_id, contactType) VALUES (:Id, :ContactType) ;');
            $contactPersonAddHandler->bindParam(self::BIND_ID, $this->dbIdNr);
            $contactPersonAddHandler->bindParam(self::BIND_CONTACTTYPE, $type);
            $contactPersonAddHandler->execute();
          }

          $child = $contactPerson->firstChild;
          $contactPersonElement = false;
          $newContactPerson = true;
          while ($child && ! $contactPersonElement) {
            if ($child->nodeName == $partmd) {
              $contactPersonElement = $child;
              $newContactPerson = false;
            } elseif (isset ($this->orderContactPerson[$child->nodeName]) && $this->orderContactPerson[$child->nodeName] < $placement) {
              $child = $child->nextSibling;
            } else {
              $contactPersonElement = $this->newXml->createElement($partmd);
              $contactPerson->insertBefore($contactPersonElement, $child);
            }
          }
          $changed = false;
          if (! $contactPersonElement) {
            # Add if missing
            $contactPersonElement = $this->newXml->createElement($partmd);
            $contactPerson->appendChild($contactPersonElement);
          }
          if ($contactPersonElement->nodeValue != $value) {
            $contactPersonElement->nodeValue = $value;
            $sql="UPDATE ContactPerson SET $part = :Data WHERE entity_id = :Id AND contactType = :ContactType ;";
            // SONAR Comment : $part is validated above. Must exist as index in in $this->orderContactPerson
            $contactPersonUpdateHandler = $this->metaDb->prepare($sql); # NOSONAR
            $contactPersonUpdateHandler->bindParam(self::BIND_ID, $this->dbIdNr);
            $contactPersonUpdateHandler->bindParam(self::BIND_CONTACTTYPE, $type);
            $contactPersonUpdateHandler->bindParam(self::BIND_DATA, $value);
            $contactPersonUpdateHandler->execute();
            $changed = true;
          }
          if ($changed) {
            $this->saveXML();
          }
          break;
        case 'Delete' :
          $value = trim($_GET['value']);
          while ($child && ! $contactPerson) {
            if ($child->nodeName == self::SAML_MD_CONTACTPERSON && $child->getAttribute('contactType') == $type) {
              if ($subType) {
                if ($child->getAttribute('remd:contactType') == $subType) {
                  $contactPerson = $child;
                }
              } else {
                $contactPerson = $child;
              }
            }
            if ($contactPerson) {
              $childContactPerson = $contactPerson->firstChild;
              $contactPersonElement = false;
              $moreContactPersonElements = false;
              while ($childContactPerson && ! $contactPersonElement) {
                if ($childContactPerson->nodeName == $partmd && $childContactPerson->nodeValue == $value ) {
                  $contactPersonElement = $childContactPerson;
                }
                $childContactPerson = $childContactPerson->nextSibling;
                $moreContactPersonElements = ($moreContactPersonElements) ? true : $childContactPerson;
              }

              if ($contactPersonElement) {
                $contactPerson->removeChild($contactPersonElement);
                if ($moreContactPersonElements) {
                  $sql="UPDATE ContactPerson SET $part = ''
                    WHERE entity_id = :Id AND contactType = :ContactType AND $part = :Value;";
                  // SONAR Comment : $part is validated above. Must exist as index in in $this->orderContactPerson
                  $contactPersonUpdateHandler = $this->metaDb->prepare($sql); # NOSONAR
                  $contactPersonUpdateHandler->bindParam(self::BIND_ID, $this->dbIdNr);
                  $contactPersonUpdateHandler->bindParam(self::BIND_CONTACTTYPE, $type);
                  $contactPersonUpdateHandler->bindParam(self::BIND_VALUE, $value);
                  $contactPersonUpdateHandler->execute();
                } else {
                  $entityDescriptor->removeChild($contactPerson);
                  $contactPersonDeleteHandler = $this->metaDb->prepare(
                    'DELETE FROM ContactPerson WHERE entity_id = :Id AND contactType = :ContactType ;');
                  $contactPersonDeleteHandler->bindParam(self::BIND_ID, $this->dbIdNr);
                  $contactPersonDeleteHandler->bindParam(self::BIND_CONTACTTYPE, $type);
                  $contactPersonDeleteHandler->execute();
                }

                $this->saveXML();
              } else {
                $contactPerson = false;
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

    $contactPersonSAML2DB = array(
      'Company' => 'company',
      'GivenName' => 'givenName',
      'SurName' => 'surName',
      'EmailAddress' => 'emailAddress',
      'TelephoneNumber' => 'telephoneNumber',
      'Extensions' => 'extensions');
    $oldContactPersons = array();
    $contactPersonHandler->bindParam(self::BIND_ID, $this->dbOldIdNr);
    $contactPersonHandler->execute();
    while ($contactPerson = $contactPersonHandler->fetch(PDO::FETCH_ASSOC)) {
      $contactType = $contactPerson['contactType'];
      foreach ($contactPersonSAML2DB as $oldPart) {
        if ($contactPerson[$oldPart]) {
          $oldContactPersons[$contactType][$oldPart] = array ('value' => $contactPerson[$oldPart], 'state' => 'new');
        } else {
          $oldContactPersons[$contactType][$oldPart] = array('value' => '', 'state' => 'new');
        }
      }
    }

    $existingContactPersons = array();
    $contactPersonHandler->bindParam(self::BIND_ID, $this->dbIdNr);
    $contactPersonHandler->execute();
    while ($contactPerson = $contactPersonHandler->fetch(PDO::FETCH_ASSOC)) {
      $contactType = $contactPerson['contactType'];
      if (! isset($existingContactPersons[$contactType])) {
        $existingContactPersons[$contactType] = array();
      }

      if ($contactPerson['subcontactType'] == '') {
        printf ("\n        <b>%s</b><br>\n", $contactType);
      } else {
        printf ("\n        <b>%s[%s]</b><br>\n", $contactType, $contactPerson['subcontactType']);
      }
      print "        <ul>\n";
      if (isset($oldContactPersons[$contactType])) {
        foreach ($contactPersonSAML2DB as $oldPart) {
          if (isset ($contactPerson[$oldPart])
            && $oldContactPersons[$contactType][$oldPart]['value'] == $contactPerson[$oldPart]) {
            $oldContactPersons[$contactType][$oldPart]['state'] = 'same';
          } elseif ($contactPerson[$oldPart] == '' ) {
            $oldContactPersons[$contactType][$oldPart]['state'] = 'removed';
          } else {
            $oldContactPersons[$contactType][$oldPart]['state'] = 'changed';
          }
        }
      } else {
        foreach ($contactPersonSAML2DB as $oldPart) {
          $oldContactPersons[$contactType][$oldPart] = array('state' => 'new');
        }
      }

      foreach ($contactPersonSAML2DB as $samlPart => $dbPart) {
        if ($contactPerson[$dbPart]) {
          $state = ($oldContactPersons[$contactType][$dbPart]['state'] == 'same') ? 'dark' : 'success';
          $baseLink =   sprintf('<a href="?edit=ContactPersons&Entity=%d&oldEntity=%d&type=%s&value=%s&part=%s&action=',
            $this->dbIdNr, $this->dbOldIdNr, $contactType, $contactPerson[$dbPart], $samlPart);
          $links = $baseLink . self::HTML_COPY . $baseLink . self::HTML_DELETE;
          printf ('          <li>%s<span class="text-%s">%s = %s</span></li>%s',
            $links, $state, $samlPart, $contactPerson[$dbPart], "\n");
          $existingContactPersons[$contactType][$dbPart] = true;
        }
      }
      print self::HTML_END_UL;
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
      <div class="col">',
      $this->dbIdNr, $this->dbOldIdNr, $type == 'administrative' ? ' selected' : '',
      $type == 'technical' ? ' selected' : '', $type == 'support' ? ' selected' : '',
      $type == 'other' ? ' selected' : '', $part == 'Company' ? ' selected' : '',
      $part == 'GivenName' ? ' selected' : '', $part == 'SurName' ? ' selected' : '',
      $part == 'EmailAddress' ? ' selected' : '', $part == 'TelephoneNumber' ? ' selected' : '',
      $part == 'Extensions' ? ' selected' : '', htmlspecialchars($value), $this->dbIdNr);

    # Print Old contacts
    $contactPersonHandler->bindParam(self::BIND_ID, $this->dbOldIdNr);
    $contactPersonHandler->execute();
    while ($contactPerson = $contactPersonHandler->fetch(PDO::FETCH_ASSOC)) {
      $contactType = $contactPerson['contactType'];
      if ($contactPerson['subcontactType'] == '') {
        printf ("\n        <b>%s</b><br>\n", $contactType);
        $type = $contactType;
      } else {
        printf ("\n        <b>%s[%s]</b><br>\n", $contactType, $contactPerson['subcontactType']);
        $type = 'security';
      }
      print "        <ul>\n";
      foreach ($contactPersonSAML2DB as $samlPart => $dbPart) {
        if ($contactPerson[$dbPart]) {
          $state = $oldContactPersons[$contactType][$dbPart]['state'] == 'same' ? 'dark' : 'danger';
          $addLink = isset($existingContactPersons[$contactType][$dbPart])
            ? ''
            : sprintf('<a href="?edit=ContactPersons&Entity=%d&oldEntity=%d&type=%s&part=%s&value=%s&action=Add">[copy]</a> ',
              $this->dbIdNr, $this->dbOldIdNr, $type, $samlPart, urlencode($contactPerson[$dbPart]));
          printf ('          <li>%s<span class="text-%s">%s = %s</span></li>%s',
            $addLink, $state, $samlPart, $contactPerson[$dbPart], "\n");
        }
      }
      print self::HTML_END_UL;
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
    if ( !$this->oldExists || $this->entityID <> $this->oldentityID ) {
      return;
    }

    $registrationInstantHandler = $this->metaDb->prepare(
      'SELECT registrationInstant AS ts FROM Entities WHERE id = :Id;');
    $registrationInstantHandler->bindParam(self::BIND_ID, $this->dbOldIdNr);
    $registrationInstantHandler->execute();
    if ($instant = $registrationInstantHandler->fetch(PDO::FETCH_ASSOC)) {
      $entityDescriptor = $this->getEntityDescriptor($this->newXml);
      # Find md:Extensions in XML
      $child = $entityDescriptor->firstChild;
      $extensions = false;
      while ($child && ! $extensions) {
        switch ($child->nodeName) {
          case self::SAML_MD_EXTENSIONS :
            $extensions = $child;
            break;
          case self::SAML_MD_ROLEDESCRIPTOR :
          case self::SAML_MD_SPSSODESCRIPTOR :
          case self::SAML_MD_IDPSSODESCRIPTOR :
          case self::SAML_MD_AUTHNAUTHORITYDESCRIPTOR :
          case self::SAML_MD_ATTRIBUTEAUTHORITYDESCRIPTOR :
          case self::SAML_MD_PDPDESCRIPTOR :
          case self::SAML_MD_AFFILIATIONDESCRIPTOR :
          case self::SAML_MD_ORGANIZATION :
          case self::SAML_MD_CONTACTPERSON :
          case self::SAML_MD_ADDITIONALMETADATALOCATION :
            $extensions = $this->newXml->createElement(self::SAML_MD_EXTENSIONS);
            $entityDescriptor->insertBefore($extensions, $child);
            break;
          default :
        }
        $child = $child->nextSibling;
      }
      if (! $extensions) {
        # Add if missing
        $extensions = $this->newXml->createElement(self::SAML_MD_EXTENSIONS);
        $entityDescriptor->appendChild($extensions);
      }
      # Find mdattr:EntityAttributes in XML
      $child = $extensions->firstChild;
      $registrationInfo = false;
      while ($child && ! $registrationInfo) {
        if ($child->nodeName == self::SAML_MDRPI_REGISTRATIONINFO) {
          $registrationInfo = $child;
        } else
          $child = $child->nextSibling;
      }
      if (! $registrationInfo) {
        # Add if missing
        $entityDescriptor->setAttributeNS(self::SAMLXMLNS_URI, 'xmlns:mdrpi', 'urn:oasis:names:tc:SAML:metadata:rpi');
        $registrationInfo = $this->newXml->createElement(self::SAML_MDRPI_REGISTRATIONINFO);
        $registrationInfo->setAttribute('registrationAuthority', 'http://www.swamid.se/');
        $registrationInfo->setAttribute('registrationInstant', $instant['ts']);
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
        $registrationPolicy = $this->newXml->createElement('mdrpi:RegistrationPolicy', 'http://swamid.se/policy/mdrps');
        $registrationPolicy->setAttribute('xml:lang', 'en');
        $registrationInfo->appendChild($registrationPolicy);
      }
    }
  }
  private function mergeEntityAttributes() {
    if ( !$this->oldExists) {
      return;
    }
    $entityAttributesHandler = $this->metaDb->prepare(
      'SELECT type, attribute FROM EntityAttributes WHERE entity_id = :Id ORDER BY type, attribute;');
    $entityAttributesHandler->bindParam(self::BIND_ID, $this->dbOldIdNr);
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
      if (! isset($oldAttributeValues[$attributeType]) ) {
        $oldAttributeValues[$attributeType] = array();
      }
      $oldAttributeValues[$attributeType][$attribute['attribute']] = $attribute['attribute'];
    }
    if(isset($oldAttributeValues)) {
      $entityDescriptor = $this->getEntityDescriptor($this->newXml);
      # Find md:Extensions in XML
      $child = $entityDescriptor->firstChild;
      $extensions = false;
      while ($child && ! $extensions) {
        switch ($child->nodeName) {
          case self::SAML_MD_EXTENSIONS :
            $extensions = $child;
            break;
          case self::SAML_MD_ROLEDESCRIPTOR :
          case self::SAML_MD_SPSSODESCRIPTOR :
          case self::SAML_MD_IDPSSODESCRIPTOR :
          case self::SAML_MD_AUTHNAUTHORITYDESCRIPTOR :
          case self::SAML_MD_ATTRIBUTEAUTHORITYDESCRIPTOR :
          case self::SAML_MD_PDPDESCRIPTOR :
          case self::SAML_MD_AFFILIATIONDESCRIPTOR :
          case self::SAML_MD_ORGANIZATION :
          case self::SAML_MD_CONTACTPERSON :
          case self::SAML_MD_ADDITIONALMETADATALOCATION :
            $extensions = $this->newXml->createElement(self::SAML_MD_EXTENSIONS);
            $entityDescriptor->insertBefore($extensions, $child);
            break;
        }
        $child = $child->nextSibling;
      }
      if (! $extensions) {
        # Add if missing
        $extensions = $this->newXml->createElement(self::SAML_MD_EXTENSIONS);
        $entityDescriptor->appendChild($extensions);
      }

      # Find mdattr:EntityAttributes in XML
      $child = $extensions->firstChild;
      $entityAttributes = false;
      while ($child && ! $entityAttributes) {
        if ($child->nodeName == self::SAML_MDATTR_ENTITYATTRIBUTES) {
          $entityAttributes = $child;
        } else
          $child = $child->nextSibling;
      }
      if (! $entityAttributes) {
        # Add if missing
        $entityDescriptor->setAttributeNS(self::SAMLXMLNS_URI, 'xmlns:mdattr', 'urn:oasis:names:tc:SAML:metadata:attribute');
        $entityAttributes = $this->newXml->createElement(self::SAML_MDATTR_ENTITYATTRIBUTES);
        $extensions->appendChild($entityAttributes);
      }

      # Find samla:Attribute in XML
      $attribute = $entityAttributes->firstChild;
      while ($attribute) {
        $attributeValue = $attribute->firstChild;
        $type = $attribute->getAttribute('Name');
        while($attributeValue) {
          $value = $attributeValue->textContent;
          if (isset($oldAttributeValues[$type][$value]))
            unset($oldAttributeValues[$type][$value]);
          $attributeValue = $attributeValue->nextSibling;
        }
        foreach ($oldAttributeValues[$type] as $value) {
          $attributeValue = $this->newXml->createElement(self::SAML_SAMLA_ATTRIBUTEVALUE);
          $attributeValue->nodeValue = $value;
          $attribute->appendChild($attributeValue);
          unset($oldAttributeValues[$type][$value]);
        }
        $attribute = $attribute->nextSibling;
      }
      foreach ($oldAttributeValues as $type => $values) {
        if (! empty($values)) {
          $entityDescriptor->setAttributeNS(self::SAMLXMLNS_URI, 'xmlns:samla', 'urn:oasis:names:tc:SAML:2.0:assertion');
          $attribute = $this->newXml->createElement(self::SAML_SAMLA_ATTRIBUTE);
          $attribute->setAttribute('Name', $type);
          $attribute->setAttribute('NameFormat', self::SAMLNF_URI);
          $entityAttributes->appendChild($attribute);
        }
        foreach ($values as $value) {
          $attributeValue = $this->newXml->createElement(self::SAML_SAMLA_ATTRIBUTEVALUE);
          $attributeValue->nodeValue = $value;
          $attribute->appendChild($attributeValue);
        }
      }
    }
  }
  private function mergeIdpErrorURL () {
    if ( !$this->oldExists)
      return;
    $errorURLHandler = $this->metaDb->prepare(
      "SELECT DISTINCT URL FROM EntityURLs WHERE entity_id = :Id AND type = 'error';");
    $errorURLHandler->bindParam(self::BIND_ID, $this->dbOldIdNr);
    $errorURLHandler->execute();
    if ($errorURL = $errorURLHandler->fetch(PDO::FETCH_ASSOC)) {
      $entityDescriptor = $this->getEntityDescriptor($this->newXml);

      # Find md:IDPSSODescriptor in XML
      $child = $entityDescriptor->firstChild;
      $idpSSODescriptor = false;
      while ($child && ! $idpSSODescriptor) {
        if ($child->nodeName == self::SAML_MD_IDPSSODESCRIPTOR)
          $idpSSODescriptor = $child;
        $child = $child->nextSibling;
      }

      if ($idpSSODescriptor  && $idpSSODescriptor->getAttribute('errorURL') == '') {
        $idpSSODescriptor->setAttribute('errorURL', $errorURL['URL']);
        $errorURLUpdateHandler = $this->metaDb->prepare(
          "INSERT INTO EntityURLs (`entity_id`, `URL`, `type` )
          VALUES (:Id, :URL, 'error')
          ON DUPLICATE KEY UPDATE `URL`= :URL;");
        $errorURLUpdateHandler->bindParam(self::BIND_ID, $this->dbIdNr);
        $errorURLUpdateHandler->bindParam(self::BIND_URL, $errorURL['URL']);
        $errorURLUpdateHandler->execute();
      }
    }
  }
  private function mergeIdPScopes() {
    if ( !$this->oldExists)
      return;
    $scopesHandler = $this->metaDb->prepare('SELECT `scope`, `regexp` FROM Scopes WHERE `entity_id` = :Id;');
    $scopesHandler->bindParam(self::BIND_ID, $this->dbOldIdNr);
    $scopesHandler->execute();
    $scopesInsertHandler = $this->metaDb->prepare('INSERT INTO Scopes (`entity_id`, `scope`, `regexp`) VALUES (:Id, :Scope, :Regexp);');
    $scopesInsertHandler->bindParam(self::BIND_ID, $this->dbIdNr);
    while ($scope = $scopesHandler->fetch(PDO::FETCH_ASSOC)) {
      $oldScopes[$scope['scope']] = true;
    }
    if ($oldScopes) {
      $entityDescriptor = $this->getEntityDescriptor($this->newXml);

      # Find md:IDPSSODescriptor in XML
      $child = $entityDescriptor->firstChild;
      $idpSSODescriptor = false;
      while ($child && ! $idpSSODescriptor) {
        if ($child->nodeName == self::SAML_MD_IDPSSODESCRIPTOR)
          $idpSSODescriptor = $child;
        $child = $child->nextSibling;
      }

      if ($idpSSODescriptor) {
        $child = $idpSSODescriptor->firstChild;
        $extensions = false;
        while ($child && ! $extensions) {
          switch ($child->nodeName) {
            case self::SAML_DS_SIGNATURE :
              break;
            case self::SAML_MD_EXTENSIONS :
              $extensions = $child;
              break;
            default :
              $extensions = $this->newXml->createElement(self::SAML_MD_EXTENSIONS);
              $idpSSODescriptor->insertBefore($extensions, $child);
          }
          $child = $child->nextSibling;
        }
        if (! $extensions) {
          $extensions = $this->newXml->createElement(self::SAML_MD_EXTENSIONS);
          $idpSSODescriptor->appendChild($extensions);
        }
        $child = $extensions->firstChild;
        $beforeChild = false;
        $Scope = false;
        $shibmdFound = false;
        while ($child && ! $Scope) {
          switch ($child->nodeName) {
            case self::SAML_SHIBMD_SCOPE :
              $shibmdFound = true;
              if (isset ($oldScopes[$child->textContent]))
                unset ($oldScopes[$child->textContent]);
              break;
            case self::SAML_MDUI_UIINFO :
            case self::SAML_MDUI_DISCOHINTS :
              $beforeChild = $beforeChild ? $beforeChild : $child;
              break;
          }
          $child = $child->nextSibling;
        }
        foreach ($oldScopes as $scopevalue => $value) {
          $Scope = $this->newXml->createElement(self::SAML_SHIBMD_SCOPE, $scopevalue);
          $Scope->setAttribute('regexp', $value);
          if ($beforeChild)
            $extensions->insertBefore($Scope, $beforeChild);
          else
            $extensions->appendChild($Scope);
          $scopesInsertHandler->bindParam(self::BIND_SCOPE, $scopevalue);
          $scopesInsertHandler->bindParam(self::BIND_REGEXP, $value);
          $scopesInsertHandler->execute();
        }

        if (! $shibmdFound) {
          $entityDescriptor->setAttributeNS(self::SAMLXMLNS_URI, 'xmlns:shibmd', 'urn:mace:shibboleth:metadata:1.0');
        }
      }
    }
  }
  private function mergeUIInfo($type) {
    if ( !$this->oldExists)
      return;
    $mduiHandler = $this->metaDb->prepare('SELECT element, lang, height, width, data FROM Mdui WHERE entity_id = :Id AND type = :Type ORDER BY element, lang;');
    $mduiHandler->bindParam(self::BIND_TYPE, $type);
    $mduiHandler->bindParam(self::BIND_ID, $this->dbOldIdNr);
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
      $entityDescriptor = $this->getEntityDescriptor($this->newXml);

      # Find md:IDPSSODescriptor in XML
      $child = $entityDescriptor->firstChild;
      $ssoDescriptor = false;
      while ($child && ! $ssoDescriptor) {
        if ($child->nodeName == 'md:'.$type.'Descriptor')
          $ssoDescriptor = $child;
        $child = $child->nextSibling;
      }
      if ($ssoDescriptor) {
        $changed = false;
        $child = $ssoDescriptor->firstChild;
        $extensions = false;
        while ($child && ! $extensions) {
          switch ($child->nodeName) {
            case self::SAML_DS_SIGNATURE :
              break;
            case self::SAML_MD_EXTENSIONS :
              $extensions = $child;
              break;
            default :
              $extensions = $this->newXml->createElement(self::SAML_MD_EXTENSIONS);
              $ssoDescriptor->insertBefore($extensions, $child);
          }
          $child = $child->nextSibling;
        }
        if (! $extensions) {
          $extensions = $this->newXml->createElement(self::SAML_MD_EXTENSIONS);
          $ssoDescriptor->appendChild($extensions);
        }
        $child = $extensions->firstChild;
        $beforeChild = false;
        $uuInfo = false;
        $mduiFound = false;
        while ($child && ! $uuInfo) {
          switch ($child->nodeName) {
            case self::SAML_MDUI_UIINFO :
              $mduiFound = true;
              $uuInfo = $child;
              break;
            case self::SAML_MDUI_DISCOHINTS :
              $beforeChild = $beforeChild ? $beforeChild : $child;
              $mduiFound = true;
              break;
          }
          $child = $child->nextSibling;
        }
        if (! $uuInfo ) {
          $uuInfo = $this->newXml->createElement(self::SAML_MDUI_UIINFO);
          if ($beforeChild)
            $extensions->insertBefore($uuInfo, $beforeChild);
          else
            $extensions->appendChild($uuInfo);
        }
        if (! $mduiFound) {
          $entityDescriptor->setAttributeNS(self::SAMLXMLNS_URI, self::SAMLXMLNS_MDUI, self::SAMLXMLNS_MDUI_URL);
        }
        # Find mdui:* in XML
        $child = $uuInfo->firstChild;
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
        $mduiAddHandler->bindParam(self::BIND_ID, $this->dbIdNr);
        $mduiAddHandler->bindParam(self::BIND_TYPE, $type);
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
              $uuInfo->appendChild($MduiElement);
              $mduiAddHandler->bindParam(self::BIND_LANG, $lang);
              $mduiAddHandler->bindParam(self::BIND_HEIGHT, $value['height']);
              $mduiAddHandler->bindParam(self::BIND_WIDTH, $value['width']);
              $mduiAddHandler->bindParam(self::BIND_ELEMENT, $element);
              $mduiAddHandler->bindParam(self::BIND_DATA, $value['value']);
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
    $mduiHandler->bindParam(self::BIND_ID, $this->dbOldIdNr);
    $mduiHandler->execute();
    while ($mdui = $mduiHandler->fetch(PDO::FETCH_ASSOC)) {
      $mdelement = 'mdui:'.$mdui['element'];
      $value = $mdui['data'];
      if (! isset($oldMDUIElements[$mdelement]) )
        $oldMDUIElements[$mdelement] = array();
      $oldMDUIElements[$mdelement][$value] = true;
    }
    if (isset($oldMDUIElements)) {
      $entityDescriptor = $this->getEntityDescriptor($this->newXml);

      # Find md:IDPSSODescriptor in XML
      $child = $entityDescriptor->firstChild;
      $ssoDescriptor = false;
      while ($child && ! $ssoDescriptor) {
        if ($child->nodeName == self::SAML_MD_IDPSSODESCRIPTOR)
          $ssoDescriptor = $child;
        $child = $child->nextSibling;
      }
      if ($ssoDescriptor) {
        $changed = false;
        $child = $ssoDescriptor->firstChild;
        $extensions = false;
        while ($child && ! $extensions) {
          switch ($child->nodeName) {
            case self::SAML_DS_SIGNATURE :
              break;
            case self::SAML_MD_EXTENSIONS :
              $extensions = $child;
              break;
            default :
              $extensions = $this->newXml->createElement(self::SAML_MD_EXTENSIONS);
              $ssoDescriptor->insertBefore($extensions, $child);
          }
          $child = $child->nextSibling;
        }
        if (! $extensions) {
          $extensions = $this->newXml->createElement(self::SAML_MD_EXTENSIONS);
          $ssoDescriptor->appendChild($extensions);
        }
        $child = $extensions->firstChild;
        $beforeChild = false;
        $DiscoHints = false;
        $mduiFound = false;
        while ($child && ! $DiscoHints) {
          switch ($child->nodeName) {
            case self::SAML_MDUI_UIINFO :
              $mduiFound = true;
              break;
            case self::SAML_MDUI_DISCOHINTS :
              $uuInfo = $child;
              $mduiFound = true;
              break;
          }
          $child = $child->nextSibling;
        }
        if (! $DiscoHints ) {
          $DiscoHints = $this->newXml->createElement(self::SAML_MDUI_DISCOHINTS);
          $extensions->appendChild($DiscoHints);
        }
        if (! $mduiFound) {
          $entityDescriptor->setAttributeNS(self::SAMLXMLNS_URI, self::SAMLXMLNS_MDUI, self::SAMLXMLNS_MDUI_URL);
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
        $mduiAddHandler->bindParam(self::BIND_ID, $this->dbIdNr);
        foreach ($oldMDUIElements as $element => $valueArray) {
          foreach ($valueArray as $value => $true) {
            # Add if missing
            $MduiElement = $this->newXml->createElement($element, $value);
            $DiscoHints->appendChild($MduiElement);
            $mduiAddHandler->bindParam(self::BIND_ELEMENT, $element);
            $mduiAddHandler->bindParam(self::BIND_DATA, $value);
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
    $serviceIndexHandler->bindParam(self::BIND_ID, $this->dbOldIdNr);

    $serviceElementHandler = $this->metaDb->prepare('SELECT element, lang, data FROM AttributeConsumingService_Service WHERE entity_id = :Id AND Service_index = :Index ORDER BY element DESC, lang;');
    $serviceElementHandler->bindParam(self::BIND_ID, $this->dbOldIdNr);
    $serviceElementHandler->bindParam(self::BIND_INDEX, $index);

    $requestedAttributeHandler = $this->metaDb->prepare('SELECT FriendlyName, Name, NameFormat, isRequired FROM AttributeConsumingService_RequestedAttribute WHERE entity_id = :Id AND Service_index = :Index ORDER BY isRequired DESC, FriendlyName;');
    $requestedAttributeHandler->bindParam(self::BIND_ID, $this->dbOldIdNr);
    $requestedAttributeHandler->bindParam(self::BIND_INDEX, $index);

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

    $entityDescriptor = $this->getEntityDescriptor($this->newXml);

    # Find md:IDPSSODescriptor in XML
    $child = $entityDescriptor->firstChild;
    $ssoDescriptor = false;
    while ($child && ! $ssoDescriptor) {
      if ($child->nodeName == self::SAML_MD_SPSSODESCRIPTOR)
        $ssoDescriptor = $child;
      $child = $child->nextSibling;
    }
    if ($ssoDescriptor && isset($oldServiceIndexes)) {
      $addServiceIndexHandler = $this->metaDb->prepare('INSERT INTO AttributeConsumingService (entity_id, Service_index) VALUES (:Id, :Index);');
      $addServiceIndexHandler->bindParam(self::BIND_ID, $this->dbIdNr);
      $addServiceIndexHandler->bindParam(self::BIND_INDEX, $index);

      $serviceElementAddHandler = $this->metaDb->prepare('INSERT INTO AttributeConsumingService_Service (entity_id, Service_index, element, lang, data) VALUES ( :Id, :Index, :Element, :Lang, :Data );');
      $serviceElementAddHandler->bindParam(self::BIND_ID, $this->dbIdNr);
      $serviceElementAddHandler->bindParam(self::BIND_INDEX, $index);
      $serviceElementAddHandler->bindParam(self::BIND_LANG, $lang);
      $serviceElementAddHandler->bindParam(self::BIND_DATA, $value);

      $requestedAttributeAddHandler = $this->metaDb->prepare('INSERT INTO AttributeConsumingService_RequestedAttribute (entity_id, Service_index, FriendlyName, Name, NameFormat, isRequired) VALUES ( :Id, :Index, :FriendlyName, :Name, :NameFormat, :IsRequired);');
      $requestedAttributeAddHandler->bindParam(self::BIND_ID, $this->dbIdNr);
      $requestedAttributeAddHandler->bindParam(self::BIND_INDEX, $index);
      $requestedAttributeAddHandler->bindParam(self::BIND_FRIENDLYNAME, $friendlyName);
      $requestedAttributeAddHandler->bindParam(self::BIND_NAME, $name);
      $requestedAttributeAddHandler->bindParam(self::BIND_NAMEFORMAT, $nameFormat);
      $requestedAttributeAddHandler->bindParam(self::BIND_ISREQUIRED, $isRequired);

      $child = $ssoDescriptor->firstChild;
      while ($child) {
        if ($child->nodeName == self::SAML_MD_ATTRIBUTECONSUMINGSERVICE ) {
          $index = $child->getAttribute('index');

          $attributeConsumingService = $child;
          $servicechild = $attributeConsumingService->firstChild;
          $nextOrder = 1;
          while ($servicechild) {
            switch ($servicechild->nodeName) {
              case self::SAML_MD_SERVICENAME :
                $lang = $servicechild->getAttribute('xml:lang');
                if (isset($oldServiceElements[$index]['ServiceName'][$lang]))
                  unset ($oldServiceElements[$index]['ServiceName'][$lang]);
                break;
              case self::SAML_MD_SERVICEDESCRIPTION :
                if ($nextOrder < 2) {
                  $serviceElementAddHandler->bindValue(self::BIND_ELEMENT, self::SAML_MD_SERVICENAME);
                  foreach ($oldServiceElements[$index]['ServiceName'] as $lang => $value) {
                    $attributeConsumingServiceElement = $this->newXml->createElement(self::SAML_MD_SERVICENAME, $value);
                    $attributeConsumingServiceElement->setAttribute('xml:lang', $lang);
                    $attributeConsumingService->insertBefore($attributeConsumingServiceElement, $servicechild);
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
              case self::SAML_MD_REQUESTEDATTRIBUTE :
                if ($nextOrder < 3) {
                  if(isset($oldServiceElements[$index]['ServiceName'])) {
                    $serviceElementAddHandler->bindValue(self::BIND_ELEMENT, 'ServiceName');
                    foreach ($oldServiceElements[$index]['ServiceName'] as $lang => $value) {
                      $attributeConsumingServiceElement = $this->newXml->createElement(self::SAML_MD_SERVICENAME, $value);
                      $attributeConsumingServiceElement->setAttribute('xml:lang', $lang);
                      $attributeConsumingService->insertBefore($attributeConsumingServiceElement, $servicechild);
                      $serviceElementAddHandler->execute();
                      unset ($oldServiceElements[$index]['ServiceName'][$lang]);
                    }
                    unset($oldServiceElements[$index]['ServiceName']);
                  }
                  if (isset($oldServiceElements[$index]['ServiceDescription'])) {
                    $serviceElementAddHandler->bindValue(self::BIND_ELEMENT, 'ServiceDescription');
                    foreach ($oldServiceElements[$index]['ServiceDescription'] as $lang => $value) {
                      $attributeConsumingServiceElement = $this->newXml->createElement(self::SAML_MD_SERVICEDESCRIPTION, $value);
                      $attributeConsumingServiceElement->setAttribute('xml:lang', $lang);
                      $attributeConsumingService->insertBefore($attributeConsumingServiceElement, $servicechild);
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
            $serviceElementAddHandler->bindValue(self::BIND_ELEMENT, 'ServiceName');
            foreach ($oldServiceElements[$index]['ServiceName'] as $lang => $value) {
              $attributeConsumingServiceElement = $this->newXml->createElement(self::SAML_MD_SERVICENAME, $value);
              $attributeConsumingServiceElement->setAttribute('xml:lang', $lang);
              $attributeConsumingService->appendChild($attributeConsumingServiceElement);
              $serviceElementAddHandler->execute();
            }
          }
          if (isset($oldServiceElements[$index]['ServiceDescription'])) {
            $serviceElementAddHandler->bindValue(self::BIND_ELEMENT, 'ServiceDescription');
            foreach ($oldServiceElements[$index]['ServiceDescription'] as $lang => $value) {
              $attributeConsumingServiceElement = $this->newXml->createElement(self::SAML_MD_SERVICEDESCRIPTION, $value);
              $attributeConsumingServiceElement->setAttribute('xml:lang', $lang);
              $attributeConsumingService->appendChild($attributeConsumingServiceElement);
              $serviceElementAddHandler->execute();
            }
          }
          unset($oldServiceElements[$index]);

          foreach ($oldRequestedAttributes[$index] as $name => $data) {
            $friendlyName = $data['friendlyName'];
            $nameFormat =  $data['nameFormat'];
            $isRequired = $data['isRequired'];

            $attributeConsumingServiceElement = $this->newXml->createElement(self::SAML_MD_REQUESTEDATTRIBUTE);
            if ($friendlyName != '' )
              $attributeConsumingServiceElement->setAttribute('FriendlyName', $friendlyName);
            $attributeConsumingServiceElement->setAttribute('Name', $name);
            if ($nameFormat != '' )
              $attributeConsumingServiceElement->setAttribute('NameFormat', $nameFormat);
            $attributeConsumingServiceElement->setAttribute('isRequired', $isRequired ? 'true' : 'false');
            $attributeConsumingService->appendChild($attributeConsumingServiceElement);
            $requestedAttributeAddHandler->execute();
          }
          unset ($oldRequestedAttributes[$index]);
          unset($oldServiceIndexes[$index]);
        }
        $child = $child->nextSibling;
      }
      foreach ($oldServiceIndexes as $index) {
        $attributeConsumingService = $this->newXml->createElement(self::SAML_MD_ATTRIBUTECONSUMINGSERVICE);
        $attributeConsumingService->setAttribute('index', $index);
        $ssoDescriptor->appendChild($attributeConsumingService);
        $addServiceIndexHandler->execute();

        if(isset($oldServiceElements[$index]['ServiceName'])) {
          $serviceElementAddHandler->bindValue(self::BIND_ELEMENT, 'ServiceName');
          foreach ($oldServiceElements[$index]['ServiceName'] as $lang => $value) {
            $attributeConsumingServiceElement = $this->newXml->createElement(self::SAML_MD_SERVICENAME, $value);
            $attributeConsumingServiceElement->setAttribute('xml:lang', $lang);
            $attributeConsumingService->appendChild($attributeConsumingServiceElement);
            $serviceElementAddHandler->execute();
          }
        }
        if (isset($oldServiceElements[$index]['ServiceDescription'])) {
          $serviceElementAddHandler->bindValue(self::BIND_ELEMENT, 'ServiceDescription');
          foreach ($oldServiceElements[$index]['ServiceDescription'] as $lang => $value) {
            $attributeConsumingServiceElement = $this->newXml->createElement(self::SAML_MD_SERVICEDESCRIPTION, $value);
            $attributeConsumingServiceElement->setAttribute('xml:lang', $lang);
            $attributeConsumingService->appendChild($attributeConsumingServiceElement);
            $serviceElementAddHandler->execute();
          }
        }
        unset($oldServiceElements[$index]);

        foreach ($oldRequestedAttributes[$index] as $name => $data) {
          $friendlyName = $data['friendlyName'];
          $nameFormat =  $data['nameFormat'];
          $isRequired = $data['isRequired'];

          $attributeConsumingServiceElement = $this->newXml->createElement(self::SAML_MD_REQUESTEDATTRIBUTE);
          if ($friendlyName != '' )
            $attributeConsumingServiceElement->setAttribute('FriendlyName', $friendlyName);
          $attributeConsumingServiceElement->setAttribute('Name', $name);
          if ($nameFormat != '' )
            $attributeConsumingServiceElement->setAttribute('NameFormat', $nameFormat);
          $attributeConsumingServiceElement->setAttribute('isRequired', $isRequired ? 'true' : 'false');
          $attributeConsumingService->appendChild($attributeConsumingServiceElement);
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
    $organizationHandler = $this->metaDb->prepare(
      'SELECT element, lang, data FROM Organization WHERE entity_id = :Id ORDER BY element, lang;');
    $organizationHandler->bindParam(self::BIND_ID, $this->dbOldIdNr);
    $organizationHandler->execute();
    while ($organization = $organizationHandler->fetch(PDO::FETCH_ASSOC)) {
      $order = $this->orderOrganization['md:'.$organization['element']];
      $oldElements[$order][] = $organization;
    }
    if (isset($oldElements)) {
      $entityDescriptor = $this->getEntityDescriptor($this->newXml);

      # Find md:Extensions in XML
      $child = $entityDescriptor->firstChild;
      $organization = false;
      while ($child && ! $organization) {
        switch ($child->nodeName) {
          case self::SAML_MD_ORGANIZATION :
            $organization = $child;
            break;
          case self::SAML_MD_CONTACTPERSON :
          case self::SAML_MD_ADDITIONALMETADATALOCATION :
            $organization = $this->newXml->createElement(self::SAML_MD_ORGANIZATION);
            $entityDescriptor->insertBefore($organization, $child);
            break;
          default :
        }
        $child = $child->nextSibling;
      }

      if (! $organization) {
        # Add if missing
        $organization = $this->newXml->createElement(self::SAML_MD_ORGANIZATION);
        $entityDescriptor->appendChild($organization);
      }

      # Find md:Organization* in XML
      $child = $organization->firstChild;
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
                $organizationElement = $this->newXml->createElement($elementmd);
                $organizationElement->setAttribute('xml:lang', $lang);
                $organizationElement->nodeValue = $value;
                $organization->insertBefore($organizationElement, $child);
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
            $organizationElement = $this->newXml->createElement($elementmd);
            $organizationElement->setAttribute('xml:lang', $lang);
            $organizationElement->nodeValue = $value;
            $organization->appendChild($organizationElement);
          }
        }
        $nextOrder++;
      }
    }
  }
  private function mergeContactPersons() {
    if ( !$this->oldExists) {
      return;
    }
    $contactPersonHandler = $this->metaDb->prepare('SELECT * FROM ContactPerson WHERE entity_id = :Id;');
    $contactPersonHandler->bindParam(self::BIND_ID, $this->dbOldIdNr);
    $contactPersonHandler->execute();
    while ($contactPerson = $contactPersonHandler->fetch(PDO::FETCH_ASSOC)) {
      $contactType = $contactPerson['contactType'];

      $oldContactPersons[$contactType] = array (
        'subcontactType' => ($contactPerson['subcontactType'] == 'security')
          ? 'http://refeds.org/metadata/contactType/security'
          : '',
        1 => array('part' => self::SAML_MD_COMPANY, 'value' => $contactPerson['company']),
        2 => array('part' => self::SAML_MD_GIVENNAME, 'value' => $contactPerson['givenName']),
        3 => array('part' => self::SAML_MD_SURNAME, 'value' => $contactPerson['surName']),
        4 => array('part' => self::SAML_MD_EMAILADDRESS, 'value' => $contactPerson['emailAddress']),
        5 => array('part' => self::SAML_MD_TELEPHONENUMBER, 'value' => $contactPerson['telephoneNumber']),
        6 => array('part' => self::SAML_MD_EXTENSIONS,  'value' => $contactPerson['extensions']));
    }
    if (isset($oldContactPersons)) {
      $entityDescriptor = $this->getEntityDescriptor($this->newXml);

      # Find md:Extensions in XML
      $child = $entityDescriptor->firstChild;
      $contactPerson = false;
      while ($child) {
        switch ($child->nodeName) {
          case self::SAML_MD_CONTACTPERSON :
            $type = $child->getAttribute('contactType');
            if (isset($oldContactPersons[$type])) {
              $subchild = $child->firstChild;
              $nextOrder = 1;
              $order = 1;
              while ($subchild) {
                $order = $this->orderContactPerson[$subchild->nodeName];
                while ($order > $nextOrder) {
                  if (!empty($oldContactPersons[$type][$nextOrder]['value'])) {
                    $contactPersonElement = $this->newXml->createElement($oldContactPersons[$type][$nextOrder]['part']);
                    $contactPersonElement->nodeValue = $oldContactPersons[$type][$nextOrder]['value'];
                    $child->insertBefore($contactPersonElement, $subchild);
                  }
                  $nextOrder++;
                }
                $subchild = $subchild->nextSibling;
                $nextOrder++;
              }
              while ($nextOrder < 7) {
                if (!empty($oldContactPersons[$type][$nextOrder]['value'])) {
                  $contactPersonElement = $this->newXml->createElement($oldContactPersons[$type][$nextOrder]['part']);
                  $contactPersonElement->nodeValue = $oldContactPersons[$type][$nextOrder]['value'];
                  $child->appendChild($contactPersonElement);
                }
                $nextOrder++;
              }
              unset($oldContactPersons[$type]);
            }
            break;
          case self::SAML_MD_ADDITIONALMETADATALOCATION :
            foreach ($oldContactPersons as $type => $oldContactPerson) {
              $contactPerson = $this->newXml->createElement(self::SAML_MD_CONTACTPERSON);
              $contactPerson->setAttribute('contactType', $type);
              if ($oldContactPerson['subcontactType']) {
                $entityDescriptor->setAttributeNS(self::SAMLXMLNS_URI, 'xmlns:remd', 'http://refeds.org/metadata');
                $contactPerson->setAttribute('remd:contactType', $oldContactPerson['subcontactType']);
              }
              $entityDescriptor->insertBefore($contactPerson, $child);
              $nextOrder = 1;
              while ($nextOrder < 7) {
                if (!empty($oldContactPerson[$nextOrder]['value'])) {
                  $contactPersonElement = $this->newXml->createElement($oldContactPerson[$nextOrder]['part']);
                  $contactPersonElement->nodeValue = $oldContactPerson[$nextOrder]['value'];
                  $contactPerson->appendChild($contactPersonElement);
                }
                $nextOrder++;
              }
            }
            break;
          default :
        }
        $child = $child->nextSibling;
      }
      foreach ($oldContactPersons as $type => $oldContactPerson) {
        $contactPerson = $this->newXml->createElement(self::SAML_MD_CONTACTPERSON);
        $contactPerson->setAttribute('contactType', $type);
        if ($oldContactPerson['subcontactType']) {
          $entityDescriptor->setAttributeNS(self::SAMLXMLNS_URI, 'xmlns:remd', 'http://refeds.org/metadata');
          $contactPerson->setAttribute('remd:contactType', $oldContactPerson['subcontactType']);
        }
        $entityDescriptor->appendChild($contactPerson);
        $nextOrder = 1;
        while ($nextOrder < 7) {
          if (!empty($oldContactPerson[$nextOrder]['value'])) {
            $contactPersonElement = $this->newXml->createElement($oldContactPerson[$nextOrder]['part']);
            $contactPersonElement->nodeValue = $oldContactPerson[$nextOrder]['value'];
            $contactPerson->appendChild($contactPersonElement);
          }
          $nextOrder++;
        }
      }
    }
  }

  public function removeSSO($type) {
    switch ($type) {
      case 'SP' :
        $ssoDescriptor = self::SAML_MD_SPSSODESCRIPTOR;
        break;
      case 'IdP' :
        $ssoDescriptor = self::SAML_MD_IDPSSODESCRIPTOR;
        break;
      default :
        printf ("Unknown type : %s", htmlspecialchars($type));
        return;
    }
    $entityDescriptor = $this->getEntityDescriptor($this->newXml);

    # Find SSODecriptor in XML
    $child = $entityDescriptor->firstChild;
    while ($child) {
      if ($child->nodeName == $ssoDescriptor) {
        $entityDescriptor->removeChild($child);
      }
      $child = $child->nextSibling;
    }
    $this->saveXML();
  }
  public function removeKey($type, $use, $serialNumber) {
    switch ($type) {
      case 'SPSSO' :
        $descriptor = self::SAML_MD_SPSSODESCRIPTOR;
        break;
      case 'IDPSSO' :
        $descriptor = self::SAML_MD_IDPSSODESCRIPTOR;
        break;
    }
    $entityDescriptor = $this->getEntityDescriptor($this->newXml);

    # Find SSODecriptor in XML
    $child = $entityDescriptor->firstChild;
    $ssoDescriptor = false;
    while ($child && ! $ssoDescriptor) {
      if ($child->nodeName == $descriptor) {
        $ssoDescriptor = $child;
      }
      $child = $child->nextSibling;
    }
    if ($ssoDescriptor) {
      $child = $ssoDescriptor->firstChild;
      $removeKeyDescriptor = false;
      $changed = false;
      while ($child) {
        // Loop thrue all KeyDescriptor:s not just the first one!
        if ($child->nodeName == self::SAML_MD_KEYDESCRIPTOR) {
          $usage = $child->getAttribute('use') ? $child->getAttribute('use') : 'both';
          if ( $usage == $use ) {
            $keyDescriptor = $child; // Save to be able to remove this KeyDescriptor
            $descriptorChild = $keyDescriptor->firstChild;
            while ($descriptorChild && !$removeKeyDescriptor) {
              if ($descriptorChild->nodeName == 'ds:KeyInfo') {
                $infoChild = $descriptorChild->firstChild;
                while ($infoChild && !$removeKeyDescriptor) {
                  if ($infoChild->nodeName == 'ds:X509Data') {
                    $x509Child = $infoChild->firstChild;
                    while ($x509Child&& !$removeKeyDescriptor) {
                      if ($x509Child->nodeName == 'ds:X509Certificate') {
                        $cert = "-----BEGIN CERTIFICATE-----\n" . chunk_split(str_replace(array(' ',"\n") ,array('',''),trim($x509Child->textContent)),64) . "-----END CERTIFICATE-----\n";
                        if ($certInfo = openssl_x509_parse( $cert)) {
                          if ($certInfo['serialNumber'] == $serialNumber)
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
          $ssoDescriptor->removeChild($keyDescriptor);
          $keyInfoDeleteHandler = $this->metaDb->prepare('DELETE FROM KeyInfo WHERE entity_id = :Id AND `type` = :Type AND `use` = :Use AND `serialNumber` = :SerialNumber;');
          $keyInfoDeleteHandler->bindParam(self::BIND_ID, $this->dbIdNr);
          $keyInfoDeleteHandler->bindParam(self::BIND_TYPE, $type);
          $keyInfoDeleteHandler->bindParam(self::BIND_USE, $use);
          $keyInfoDeleteHandler->bindParam(self::BIND_SERIALNUMBER, $serialNumber);
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
    $entityHandler->bindParam(self::BIND_ID, $this->dbIdNr);
    $entityHandler->bindValue(self::BIND_XML, $this->newXml->saveXML());
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

  public function updateUser($userID, $email, $fullName) {
    $userHandler = $this->metaDb->prepare(
      'UPDATE Users SET `email` = :Email, `fullName` = :FullName WHERE `userID` = :Id');
    $userHandler->bindValue(self::BIND_ID, strtolower($userID));
    $userHandler->bindValue(self::BIND_EMAIL, $email);
    $userHandler->bindValue(self::BIND_FULLNAME, $fullName);
    $userHandler->execute();
  }
}
