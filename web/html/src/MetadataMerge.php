<?php
namespace metadata;

use PDO;

class MetadataMerge extends Common {
  # Setup
  private int $dbOldIdNr = 0;
  private bool $oldExists = false;
  private bool $oldentityID = false;

  /**
   * Setup the class
   *
   * @param int $id id in database for new entity
   *
   * @param int $oldID id in database for entity to merge from
   *
   * @return void
   */
  public function __construct($id, $oldID = 0) {
    parent::__construct($id);
    $this->dbOldIdNr = is_numeric($oldID) ? $oldID : 0;

    if ($this->entityExists && $oldID > 0) {
      $entityHandler = $this->config->getDb()->prepare('SELECT `entityID` FROM `Entities` WHERE `id` = :Id;');
      $entityHandler->bindValue(self::BIND_ID, $oldID);
      $entityHandler->execute();
      if ($entity = $entityHandler->fetch(PDO::FETCH_ASSOC)) {
        $this->oldentityID = $entity['entityID'];
        $this->oldExists = true;
      }
    }
  }

  /**
   * Merge infro from Old -> New
   */
  public function mergeFrom() {
    if ( !$this->oldExists) {
      return;
    }
    $this->getEntityDescriptor($this->xml);
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
    $this->saveResults();
  }

  /**
   * Merge RegistrationInfo
   *
   * @return void
   */
  public function mergeRegistrationInfo() {
    # Skip if not same entityID. Only migrate if same!!!!
    if ( !$this->oldExists || $this->entityID <> $this->oldentityID ) {
      return;
    }

    $registrationInstantHandler = $this->config->getDb()->prepare(
      'SELECT `registrationInstant` AS ts FROM `Entities` WHERE `id` = :Id;');
    $registrationInstantHandler->bindParam(self::BIND_ID, $this->dbOldIdNr);
    $registrationInstantHandler->execute();
    if ($instant = $registrationInstantHandler->fetch(PDO::FETCH_ASSOC)) {
      $extensions = $this->getExtensions();
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
        $this->entityDescriptor->setAttributeNS(self::SAMLXMLNS_URI, 'xmlns:mdrpi', 'urn:oasis:names:tc:SAML:metadata:rpi');
        $registrationInfo = $this->xml->createElement(self::SAML_MDRPI_REGISTRATIONINFO);
        $registrationInfo->setAttribute('registrationAuthority', 'http://www.swamid.se/'); # NOSONAR Should be http://
        $registrationInfo->setAttribute('registrationInstant', $instant['ts']);
        $extensions->appendChild($registrationInfo);
      }

      # Find mdrpi:RegistrationPolicy in XML
      $child = $registrationInfo->firstChild;
      $registrationPolicy = false;
      while ($child && ! $registrationPolicy) {
        if ($child->nodeName == self::SAML_MDRPI_REGISTRATIONPOLICY && $child->getAttribute(self::SAMLXML_LANG) == 'en') {
          $registrationPolicy = $child;
        } else {
          $child = $child->nextSibling;
        }
      }
      if (!$registrationPolicy) {
        $registrationPolicy = $this->xml->createElement(self::SAML_MDRPI_REGISTRATIONPOLICY, 'http://swamid.se/policy/mdrps'); # NOSONAR Should be http://
        $registrationPolicy->setAttribute(self::SAMLXML_LANG, 'en');
        $registrationInfo->appendChild($registrationPolicy);
      }
    }
  }

  /**
   * Merge EntityAttributes
   *
   * @return void
   */
  private function mergeEntityAttributes() {
    if ( !$this->oldExists) {
      return;
    }
    $entityAttributesHandler = $this->config->getDb()->prepare(
      'SELECT `type`, `attribute` FROM `EntityAttributes` WHERE `entity_id` = :Id ORDER BY `type`, `attribute`;');
    $entityAttributesHandler->bindParam(self::BIND_ID, $this->dbOldIdNr);
    $entityAttributesHandler->execute();
    while ($attribute = $entityAttributesHandler->fetch(PDO::FETCH_ASSOC)) {
      switch ($attribute['type']) {
        case 'assurance-certification' :
          $attributeType = 'urn:oasis:names:tc:SAML:attribute:assurance-certification';
          break;
        case 'entity-category' :
          $attributeType = 'http://macedir.org/entity-category'; # NOSONAR Should be http://
          break;
        case 'entity-category-support' :
          $attributeType = 'http://macedir.org/entity-category-support'; # NOSONAR Should be http://
          break;
        case 'subject-id:req' :
          $attributeType = 'urn:oasis:names:tc:SAML:profiles:subject-id:req';
          break;
        case 'entity-selection-profile' :
          $attributeType = 'https://refeds.org/entity-selection-profile';
          if (isset($this->config->entitySelectionProfiles()[$attribute['attribute']])) {
            # Update with new value
            $attribute['attribute'] = $this->config->entitySelectionProfiles()[$attribute['attribute']]["base64"];
          }
          break;
        default :
          printf ('Merge EntityAttributes : unknown type %s', $attribute['type']);
          exit;
      }
      if (! isset($oldAttributeValues[$attributeType]) ) {
        $oldAttributeValues[$attributeType] = array();
      }
      $oldAttributeValues[$attributeType][$attribute['attribute']] = $attribute['attribute'];
    }
    if(isset($oldAttributeValues)) {
      $extensions = $this->getExtensions();

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
        $this->entityDescriptor->setAttributeNS(self::SAMLXMLNS_URI, 'xmlns:mdattr', 'urn:oasis:names:tc:SAML:metadata:attribute');
        $entityAttributes = $this->xml->createElement(self::SAML_MDATTR_ENTITYATTRIBUTES);
        $extensions->appendChild($entityAttributes);
      }

      # Find samla:Attribute in XML
      $attribute = $entityAttributes->firstChild;
      while ($attribute) {
        $attributeValue = $attribute->firstChild;
        $type = $attribute->getAttribute('Name');
        while($attributeValue) {
          $value = $attributeValue->textContent;
          if (isset($oldAttributeValues[$type][$value])) {
            unset($oldAttributeValues[$type][$value]);
          }
          $attributeValue = $attributeValue->nextSibling;
        }
        foreach ($oldAttributeValues[$type] as $value) {
          $attributeValue = $this->xml->createElement(self::SAML_SAMLA_ATTRIBUTEVALUE);
          $attributeValue->nodeValue = htmlspecialchars($value);
          $attribute->appendChild($attributeValue);
          unset($oldAttributeValues[$type][$value]);
        }
        $attribute = $attribute->nextSibling;
      }
      foreach ($oldAttributeValues as $type => $values) {
        if (! empty($values)) {
          $this->entityDescriptor->setAttributeNS(self::SAMLXMLNS_URI, 'xmlns:samla', 'urn:oasis:names:tc:SAML:2.0:assertion');
          $attribute = $this->xml->createElement(self::SAML_SAMLA_ATTRIBUTE);
          $attribute->setAttribute('Name', $type);
          $attribute->setAttribute('NameFormat', self::SAMLNF_URI);
          $entityAttributes->appendChild($attribute);
        }
        foreach ($values as $value) {
          $attributeValue = $this->xml->createElement(self::SAML_SAMLA_ATTRIBUTEVALUE);
          $attributeValue->nodeValue = htmlspecialchars($value);
          $attribute->appendChild($attributeValue);
        }
      }
    }
  }

  /**
   * Merge IdpErrorURL
   *
   * @return void
   */
  private function mergeIdpErrorURL () {
    if ( !$this->oldExists) {
      return;
    }
    $errorURLHandler = $this->config->getDb()->prepare(
      "SELECT DISTINCT URL FROM `EntityURLs` WHERE `entity_id` = :Id AND `type` = 'error';");
    $errorURLHandler->bindParam(self::BIND_ID, $this->dbOldIdNr);
    $errorURLHandler->execute();
    if ($errorURL = $errorURLHandler->fetch(PDO::FETCH_ASSOC)) {
      $ssoDescriptor = $this->getSSODecriptor('IDPSSO');
      if ($ssoDescriptor  && $ssoDescriptor->getAttribute('errorURL') == '') {
        $ssoDescriptor->setAttribute('errorURL', htmlspecialchars($errorURL['URL']));
        $errorURLUpdateHandler = $this->config->getDb()->prepare(
          "INSERT INTO `EntityURLs` (`entity_id`, `URL`, `type` )
          VALUES (:Id, :URL, 'error')
          ON DUPLICATE KEY UPDATE `URL`= :URL;");
        $errorURLUpdateHandler->bindParam(self::BIND_ID, $this->dbIdNr);
        $errorURLUpdateHandler->bindParam(self::BIND_URL, $errorURL['URL']);
        $errorURLUpdateHandler->execute();
      }
    }
  }

  /**
   * Merge IdPScopes
   *
   * @return void
   */
  private function mergeIdPScopes() {
    if ( !$this->oldExists) {
      return;
    }
    $scopesHandler = $this->config->getDb()->prepare('SELECT `scope`, `regexp` FROM `Scopes` WHERE `entity_id` = :Id;');
    $scopesHandler->bindParam(self::BIND_ID, $this->dbOldIdNr);
    $scopesHandler->execute();
    $scopesInsertHandler = $this->config->getDb()->prepare('INSERT INTO `Scopes` (`entity_id`, `scope`, `regexp`) VALUES (:Id, :Scope, :Regexp);');
    $scopesInsertHandler->bindParam(self::BIND_ID, $this->dbIdNr);
    while ($scope = $scopesHandler->fetch(PDO::FETCH_ASSOC)) {
      $oldScopes[$scope['scope']] = $scope['regexp'];
    }
    if ($oldScopes && $ssoDescriptor = $this->getSSODecriptor('IDPSSO')) {
      $extensions = $this->getSSODescriptorExtensions($ssoDescriptor);
      $child = $extensions->firstChild;
      $beforeChild = false;
      $shibmdFound = false;
      while ($child) {
        switch ($child->nodeName) {
          case self::SAML_SHIBMD_SCOPE :
            $shibmdFound = true;
            if (isset ($oldScopes[$child->textContent])) {
              unset ($oldScopes[$child->textContent]);
            }
            break;
          case self::SAML_MDUI_UIINFO :
          case self::SAML_MDUI_DISCOHINTS :
            $beforeChild = $beforeChild ? $beforeChild : $child;
            break;
          default :
        }
        $child = $child->nextSibling;
      }
      foreach ($oldScopes as $scopevalue => $value) {
        $scope = $this->xml->createElement(self::SAML_SHIBMD_SCOPE, htmlspecialchars($scopevalue));
        $scope->setAttribute('regexp', $value);
        if ($beforeChild) {
          $extensions->insertBefore($scope, $beforeChild);
        } else {
          $extensions->appendChild($scope);
        }
        $scopesInsertHandler->bindParam(self::BIND_SCOPE, $scopevalue);
        $scopesInsertHandler->bindParam(self::BIND_REGEXP, $value);
        $scopesInsertHandler->execute();
      }

      if (! $shibmdFound) {
        $this->entityDescriptor->setAttributeNS(self::SAMLXMLNS_URI, 'xmlns:shibmd', 'urn:mace:shibboleth:metadata:1.0');
      }
    }
  }

  /**
   * Merge UIInfo
   *
   * @param string $type SSODescript to merge in
   *
   * @return void
   */
  private function mergeUIInfo($type) {
    if ( !$this->oldExists) {
      return;
    }
    $mduiHandler = $this->config->getDb()->prepare('SELECT `element`, `lang`, `height`, `width`, `data` FROM `Mdui` WHERE `entity_id` = :Id AND type = :Type ORDER BY `element`, `lang`;');
    $mduiHandler->bindParam(self::BIND_TYPE, $type);
    $mduiHandler->bindParam(self::BIND_ID, $this->dbOldIdNr);
    $mduiHandler->execute();
    while ($mdui = $mduiHandler->fetch(PDO::FETCH_ASSOC)) {
      $mdelement = self::SAML_MDUI.$mdui['element'];
      $size = $mdui['height'].'x'.$mdui['width'];
      $lang = $mdui['lang'];
      if (! isset($oldMDUIElements[$mdelement]) ) {
        $oldMDUIElements[$mdelement] = array();
      }
      if (! isset($oldMDUIElements[$mdelement][$lang]) ) {
        $oldMDUIElements[$mdelement][$lang] = array();
      }
      $oldMDUIElements[$mdelement][$lang][$size] = array('value' => $mdui['data'], 'height' => $mdui['height'], 'width' => $mdui['width']);
    }
    if (isset($oldMDUIElements) && $ssoDescriptor = $this->getSSODecriptor($type)) {
      $extensions = $this->getSSODescriptorExtensions($ssoDescriptor);
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
        $uuInfo = $this->xml->createElement(self::SAML_MDUI_UIINFO);
        if ($beforeChild) {
          $extensions->insertBefore($uuInfo, $beforeChild);
        } else {
          $extensions->appendChild($uuInfo);
        }
      }
      if (! $mduiFound) {
        $this->entityDescriptor->setAttributeNS(self::SAMLXMLNS_URI, self::SAMLXMLNS_MDUI, self::SAMLXMLNS_MDUI_URL);
      }
      # Find mdui:* in XML
      $child = $uuInfo->firstChild;
      while ($child) {
        if ($child->nodeType != 8) {
          $lang = $child->getAttribute(self::SAMLXML_LANG);
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
      $mduiAddHandler = $this->config->getDb()->prepare('INSERT INTO `Mdui` (`entity_id`, `type`, `lang`, `height`, `width`, `element`, `data`) VALUES (:Id, :Type, :Lang, :Height, :Width, :Element, :Data);');
      $mduiAddHandler->bindParam(self::BIND_ID, $this->dbIdNr);
      $mduiAddHandler->bindParam(self::BIND_TYPE, $type);
      foreach ($oldMDUIElements as $element => $data) {
        foreach ($data as $lang => $sizeValue) {
          foreach ($sizeValue as $size => $value) {
            # Add if missing
            $mduiElement = $this->xml->createElement($element, htmlspecialchars($value['value']));
            if ($lang != '') {
              $mduiElement->setAttribute(self::SAMLXML_LANG, $lang);
            }
            if ($size != '0x0') {
              $mduiElement->setAttribute('height', $value['height']);
              $mduiElement->setAttribute('width', $value['width']);
            }
            $uuInfo->appendChild($mduiElement);
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

  /**
   * Merge DiscoHints
   *
   * @return void
   */
  private function mergeDiscoHints() {
    if ( !$this->oldExists) {
      return;
    }
    $mduiHandler = $this->config->getDb()->prepare("SELECT `element`, `data` FROM `Mdui` WHERE `entity_id` = :Id AND `type` = 'IDPDisco' ORDER BY `element`;");
    $mduiHandler->bindParam(self::BIND_ID, $this->dbOldIdNr);
    $mduiHandler->execute();
    while ($mdui = $mduiHandler->fetch(PDO::FETCH_ASSOC)) {
      $mdelement = self::SAML_MDUI.$mdui['element'];
      $value = $mdui['data'];
      if (! isset($oldMDUIElements[$mdelement]) ) {
        $oldMDUIElements[$mdelement] = array();
      }
      $oldMDUIElements[$mdelement][$value] = true;
    }
    if (isset($oldMDUIElements)) {
      $ssoDescriptor = $this->getSSODecriptor('IDPSSO');
      if ($ssoDescriptor) {
        $extensions = $this->getSSODescriptorExtensions($ssoDescriptor);
        $child = $extensions->firstChild;
        $discoHints = false;
        $mduiFound = false;
        while ($child && ! $discoHints) {
          switch ($child->nodeName) {
            case self::SAML_MDUI_UIINFO :
              $mduiFound = true;
              break;
            case self::SAML_MDUI_DISCOHINTS :
              $discoHints = $child;
              $mduiFound = true;
              break;
          }
          $child = $child->nextSibling;
        }
        if (! $discoHints ) {
          $discoHints = $this->xml->createElement(self::SAML_MDUI_DISCOHINTS);
          $extensions->appendChild($discoHints);
        }
        if (! $mduiFound) {
          $this->entityDescriptor->setAttributeNS(self::SAMLXMLNS_URI, self::SAMLXMLNS_MDUI, self::SAMLXMLNS_MDUI_URL);
        }
        # Find mdui:* in XML
        $child = $discoHints->firstChild;
        while ($child) {
          $element = $child->nodeName;
          $value = $child->nodeValue;
          if (isset($oldMDUIElements[$element][$value])) {
            unset($oldMDUIElements[$element][$value]);
          }
          $child = $child->nextSibling;
        }
        $mduiAddHandler = $this->config->getDb()->prepare("INSERT INTO `Mdui` (`entity_id`, `type`, `element`, `data`) VALUES (:Id, 'IDPDisco', :Element, :Data);");
        $mduiAddHandler->bindParam(self::BIND_ID, $this->dbIdNr);
        foreach ($oldMDUIElements as $element => $valueArray) {
          foreach ($valueArray as $value => $true) {
            # Add if missing
            $mduiElement = $this->xml->createElement($element, htmlspecialchars($value));
            $discoHints->appendChild($mduiElement);
            $mduiAddHandler->bindParam(self::BIND_ELEMENT, $element);
            $mduiAddHandler->bindParam(self::BIND_DATA, $value);
            $mduiAddHandler->execute();
          }
        }
      }
    }
  }

  /**
   * Merge AttributeConsumingService
   *
   * @return void
   */
  private function mergeAttributeConsumingService() {
    if ( !$this->oldExists) {
      return;
    }
    $serviceIndexHandler = $this->config->getDb()->prepare('SELECT `Service_index` FROM `AttributeConsumingService` WHERE `entity_id` = :Id ORDER BY `Service_index`;');
    $serviceIndexHandler->bindParam(self::BIND_ID, $this->dbOldIdNr);

    $serviceElementHandler = $this->config->getDb()->prepare('SELECT `element`, `lang`, `data` FROM `AttributeConsumingService_Service` WHERE `entity_id` = :Id AND `Service_index` = :Index ORDER BY `element` DESC, `lang`;');
    $serviceElementHandler->bindParam(self::BIND_ID, $this->dbOldIdNr);
    $serviceElementHandler->bindParam(self::BIND_INDEX, $index);

    $requestedAttributeHandler = $this->config->getDb()->prepare('SELECT `FriendlyName`, `Name`, `NameFormat`, `isRequired` FROM `AttributeConsumingService_RequestedAttribute` WHERE `entity_id` = :Id AND `Service_index` = :Index ORDER BY `isRequired` DESC, `FriendlyName`;');
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

    $this->getEntityDescriptor($this->xml);
    $ssoDescriptor = $this->getSSODecriptor('SPSSO');
    if ($ssoDescriptor && isset($oldServiceIndexes)) {
      $addServiceIndexHandler = $this->config->getDb()->prepare('INSERT INTO `AttributeConsumingService` (`entity_id`, `Service_index`) VALUES (:Id, :Index);');
      $addServiceIndexHandler->bindParam(self::BIND_ID, $this->dbIdNr);
      $addServiceIndexHandler->bindParam(self::BIND_INDEX, $index);

      $serviceElementAddHandler = $this->config->getDb()->prepare('INSERT INTO `AttributeConsumingService_Service` (`entity_id`, `Service_index`, `element`, `lang`, `data`) VALUES ( :Id, :Index, :Element, :Lang, :Data );');
      $serviceElementAddHandler->bindParam(self::BIND_ID, $this->dbIdNr);
      $serviceElementAddHandler->bindParam(self::BIND_INDEX, $index);
      $serviceElementAddHandler->bindParam(self::BIND_LANG, $lang);
      $serviceElementAddHandler->bindParam(self::BIND_DATA, $value);

      $requestedAttributeAddHandler = $this->config->getDb()->prepare('INSERT INTO `AttributeConsumingService_RequestedAttribute` (`entity_id`, `Service_index`, `FriendlyName`, `Name`, `NameFormat`, `isRequired`) VALUES ( :Id, :Index, :FriendlyName, :Name, :NameFormat, :IsRequired);');
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
                $lang = $servicechild->getAttribute(self::SAMLXML_LANG);
                if (isset($oldServiceElements[$index]['ServiceName'][$lang])) {
                  unset ($oldServiceElements[$index]['ServiceName'][$lang]);
                }
                break;
              case self::SAML_MD_SERVICEDESCRIPTION :
                if ($nextOrder < 2) {
                  $serviceElementAddHandler->bindValue(self::BIND_ELEMENT, self::SAML_MD_SERVICENAME);
                  foreach ($oldServiceElements[$index]['ServiceName'] as $lang => $value) {
                    $attributeConsumingServiceElement = $this->xml->createElement(self::SAML_MD_SERVICENAME, htmlspecialchars($value));
                    $attributeConsumingServiceElement->setAttribute(self::SAMLXML_LANG, $lang);
                    $attributeConsumingService->insertBefore($attributeConsumingServiceElement, $servicechild);
                    $serviceElementAddHandler->execute();
                    unset ($oldServiceElements[$index]['ServiceName'][$lang]);
                  }
                  unset($oldServiceElements[$index]['ServiceName']);
                  $nextOrder = 2;
                }
                $lang = $servicechild->getAttribute(self::SAMLXML_LANG);
                if (isset($oldServiceElements[$index]['ServiceDescription'][$lang])) {
                  unset ($oldServiceElements[$index]['ServiceDescription'][$lang]);
                }
                break;
              case self::SAML_MD_REQUESTEDATTRIBUTE :
                if ($nextOrder < 3) {
                  if(isset($oldServiceElements[$index]['ServiceName'])) {
                    $serviceElementAddHandler->bindValue(self::BIND_ELEMENT, 'ServiceName');
                    foreach ($oldServiceElements[$index]['ServiceName'] as $lang => $value) {
                      $attributeConsumingServiceElement = $this->xml->createElement(self::SAML_MD_SERVICENAME, htmlspecialchars($value));
                      $attributeConsumingServiceElement->setAttribute(self::SAMLXML_LANG, $lang);
                      $attributeConsumingService->insertBefore($attributeConsumingServiceElement, $servicechild);
                      $serviceElementAddHandler->execute();
                      unset ($oldServiceElements[$index]['ServiceName'][$lang]);
                    }
                    unset($oldServiceElements[$index]['ServiceName']);
                  }
                  if (isset($oldServiceElements[$index]['ServiceDescription'])) {
                    $serviceElementAddHandler->bindValue(self::BIND_ELEMENT, 'ServiceDescription');
                    foreach ($oldServiceElements[$index]['ServiceDescription'] as $lang => $value) {
                      $attributeConsumingServiceElement = $this->xml->createElement(self::SAML_MD_SERVICEDESCRIPTION, htmlspecialchars($value));
                      $attributeConsumingServiceElement->setAttribute(self::SAMLXML_LANG, $lang);
                      $attributeConsumingService->insertBefore($attributeConsumingServiceElement, $servicechild);
                      $serviceElementAddHandler->execute();
                      unset ($oldServiceElements[$index]['ServiceDescription'][$lang]);
                    }
                    unset ($oldServiceElements[$index]['ServiceDescription']);
                  }
                  $nextOrder = 3;
                }
                $name = $servicechild->getAttribute('Name');
                if (isset($oldRequestedAttributes[$index][$name])) {
                  unset ($oldRequestedAttributes[$index][$name]);
                }
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
              $attributeConsumingServiceElement = $this->xml->createElement(self::SAML_MD_SERVICENAME, htmlspecialchars($value));
              $attributeConsumingServiceElement->setAttribute(self::SAMLXML_LANG, $lang);
              $attributeConsumingService->appendChild($attributeConsumingServiceElement);
              $serviceElementAddHandler->execute();
            }
          }
          if (isset($oldServiceElements[$index]['ServiceDescription'])) {
            $serviceElementAddHandler->bindValue(self::BIND_ELEMENT, 'ServiceDescription');
            foreach ($oldServiceElements[$index]['ServiceDescription'] as $lang => $value) {
              $attributeConsumingServiceElement = $this->xml->createElement(self::SAML_MD_SERVICEDESCRIPTION, htmlspecialchars($value));
              $attributeConsumingServiceElement->setAttribute(self::SAMLXML_LANG, $lang);
              $attributeConsumingService->appendChild($attributeConsumingServiceElement);
              $serviceElementAddHandler->execute();
            }
          }
          unset($oldServiceElements[$index]);

          foreach ($oldRequestedAttributes[$index] as $name => $data) {
            $friendlyName = $data['friendlyName'];
            $nameFormat =  $data['nameFormat'];
            $isRequired = $data['isRequired'];

            $attributeConsumingServiceElement = $this->xml->createElement(self::SAML_MD_REQUESTEDATTRIBUTE);
            if ($friendlyName != '' ) {
              $attributeConsumingServiceElement->setAttribute('FriendlyName', $friendlyName);
            }
            $attributeConsumingServiceElement->setAttribute('Name', $name);
            if ($nameFormat != '' ) {
              $attributeConsumingServiceElement->setAttribute('NameFormat', $nameFormat);
            }
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
        $attributeConsumingService = $this->xml->createElement(self::SAML_MD_ATTRIBUTECONSUMINGSERVICE);
        $attributeConsumingService->setAttribute('index', $index);
        $ssoDescriptor->appendChild($attributeConsumingService);
        $addServiceIndexHandler->execute();

        if(isset($oldServiceElements[$index]['ServiceName'])) {
          $serviceElementAddHandler->bindValue(self::BIND_ELEMENT, 'ServiceName');
          foreach ($oldServiceElements[$index]['ServiceName'] as $lang => $value) {
            $attributeConsumingServiceElement = $this->xml->createElement(self::SAML_MD_SERVICENAME, htmlspecialchars($value));
            $attributeConsumingServiceElement->setAttribute(self::SAMLXML_LANG, $lang);
            $attributeConsumingService->appendChild($attributeConsumingServiceElement);
            $serviceElementAddHandler->execute();
          }
        }
        if (isset($oldServiceElements[$index]['ServiceDescription'])) {
          $serviceElementAddHandler->bindValue(self::BIND_ELEMENT, 'ServiceDescription');
          foreach ($oldServiceElements[$index]['ServiceDescription'] as $lang => $value) {
            $attributeConsumingServiceElement = $this->xml->createElement(self::SAML_MD_SERVICEDESCRIPTION, htmlspecialchars($value));
            $attributeConsumingServiceElement->setAttribute(self::SAMLXML_LANG, $lang);
            $attributeConsumingService->appendChild($attributeConsumingServiceElement);
            $serviceElementAddHandler->execute();
          }
        }
        unset($oldServiceElements[$index]);

        foreach ($oldRequestedAttributes[$index] as $name => $data) {
          $friendlyName = $data['friendlyName'];
          $nameFormat =  $data['nameFormat'];
          $isRequired = $data['isRequired'];

          $attributeConsumingServiceElement = $this->xml->createElement(self::SAML_MD_REQUESTEDATTRIBUTE);
          if ($friendlyName != '' ) {
            $attributeConsumingServiceElement->setAttribute('FriendlyName', $friendlyName);
          }
          $attributeConsumingServiceElement->setAttribute('Name', $name);
          if ($nameFormat != '' ) {
            $attributeConsumingServiceElement->setAttribute('NameFormat', $nameFormat);
          }
          $attributeConsumingServiceElement->setAttribute('isRequired', $isRequired ? 'true' : 'false');
          $attributeConsumingService->appendChild($attributeConsumingServiceElement);
          $requestedAttributeAddHandler->execute();
        }
        unset($oldRequestedAttributes[$index]);
        unset($oldServiceIndexes[$index]);
      }
    }
  }

  /**
   * Merge Organization
   *
   * @return void
   */
  private function mergeOrganization() {
    if ( !$this->oldExists) {
      return;
    }
    $organizationHandler = $this->config->getDb()->prepare(
      'SELECT element, lang, data FROM `Organization` WHERE entity_id = :Id ORDER BY element, lang;');
    $organizationHandler->bindParam(self::BIND_ID, $this->dbOldIdNr);
    $organizationHandler->execute();
    while ($organization = $organizationHandler->fetch(PDO::FETCH_ASSOC)) {
      $order = self::ORDER_ORGANIZATION['md:'.$organization['element']];
      $oldElements[$order][] = $organization;
    }
    if (isset($oldElements)) {
      # Find md:Organization in XML
      $child = $this->entityDescriptor->firstChild;
      $organization = false;
      while ($child && ! $organization) {
        switch ($child->nodeName) {
          case self::SAML_MD_ORGANIZATION :
            $organization = $child;
            break;
          case self::SAML_MD_CONTACTPERSON :
          case self::SAML_MD_ADDITIONALMETADATALOCATION :
            $organization = $this->xml->createElement(self::SAML_MD_ORGANIZATION);
            $this->entityDescriptor->insertBefore($organization, $child);
            break;
          default :
        }
        $child = $child->nextSibling;
      }

      if (! $organization) {
        # Add if missing
        $organization = $this->xml->createElement(self::SAML_MD_ORGANIZATION);
        $this->entityDescriptor->appendChild($organization);
      }

      # Find md:Organization* in XML
      $child = $organization->firstChild;
      $nextOrder = 1;
      while ($child) {
        if ($child->nodeType != 8) {
          $order = self::ORDER_ORGANIZATION[$child->nodeName];
          while ($order > $nextOrder) {
            if (isset($oldElements[$nextOrder])) {
              foreach ($oldElements[$nextOrder] as $index => $element) {
                $lang = $element['lang'];
                $elementmd = 'md:'.$element['element'];
                $value = $element['data'];
                $organizationElement = $this->xml->createElement($elementmd);
                $organizationElement->setAttribute(self::SAMLXML_LANG, $lang);
                $organizationElement->nodeValue = htmlspecialchars($value);
                $organization->insertBefore($organizationElement, $child);
                unset($oldElements[$nextOrder][$index]);
              }
            }
            $nextOrder++;
          }
          $lang = $child->getAttribute(self::SAMLXML_LANG);
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
            $organizationElement = $this->xml->createElement($elementmd);
            $organizationElement->setAttribute(self::SAMLXML_LANG, $lang);
            $organizationElement->nodeValue = htmlspecialchars($value);
            $organization->appendChild($organizationElement);
          }
        }
        $nextOrder++;
      }
    }
  }

  /**
   * Merge ContactsPersons
   *
   * @return void
   */
  private function mergeContactPersons() {
    if ( !$this->oldExists) {
      return;
    }
    $contactPersonHandler = $this->config->getDb()->prepare('SELECT * FROM `ContactPerson` WHERE `entity_id` = :Id;');
    $contactPersonHandler->bindParam(self::BIND_ID, $this->dbOldIdNr);
    $contactPersonHandler->execute();
    while ($contactPerson = $contactPersonHandler->fetch(PDO::FETCH_ASSOC)) {
      $contactType = $contactPerson['contactType'];

      $oldContactPersons[$contactType] = array (
        'subcontactType' => ($contactPerson['subcontactType'] == 'security')
          ? 'http://refeds.org/metadata/contactType/security' # NOSONAR Should be http://
          : '',
        1 => array('part' => self::SAML_MD_COMPANY, 'value' => $contactPerson['company']),
        2 => array('part' => self::SAML_MD_GIVENNAME, 'value' => $contactPerson['givenName']),
        3 => array('part' => self::SAML_MD_SURNAME, 'value' => $contactPerson['surName']),
        4 => array('part' => self::SAML_MD_EMAILADDRESS, 'value' => $contactPerson['emailAddress']),
        5 => array('part' => self::SAML_MD_TELEPHONENUMBER, 'value' => $contactPerson['telephoneNumber']),
        6 => array('part' => self::SAML_MD_EXTENSIONS,  'value' => $contactPerson['extensions']));
    }
    if (isset($oldContactPersons)) {
      # Find md:Contacts in XML
      $child = $this->entityDescriptor->firstChild;
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
                $order = self::ORDER_CONTACTPERSON[$subchild->nodeName];
                while ($order > $nextOrder) {
                  if (!empty($oldContactPersons[$type][$nextOrder]['value'])) {
                    $contactPersonElement = $this->xml->createElement($oldContactPersons[$type][$nextOrder]['part']);
                    $contactPersonElement->nodeValue = htmlspecialchars($oldContactPersons[$type][$nextOrder]['value']);
                    $child->insertBefore($contactPersonElement, $subchild);
                  }
                  $nextOrder++;
                }
                $subchild = $subchild->nextSibling;
                $nextOrder++;
              }
              while ($nextOrder < 7) {
                if (!empty($oldContactPersons[$type][$nextOrder]['value'])) {
                  $contactPersonElement = $this->xml->createElement($oldContactPersons[$type][$nextOrder]['part']);
                  $contactPersonElement->nodeValue = htmlspecialchars($oldContactPersons[$type][$nextOrder]['value']);
                  $child->appendChild($contactPersonElement);
                }
                $nextOrder++;
              }
              unset($oldContactPersons[$type]);
            }
            break;
          case self::SAML_MD_ADDITIONALMETADATALOCATION :
            foreach ($oldContactPersons as $type => $oldContactPerson) {
              $contactPerson = $this->xml->createElement(self::SAML_MD_CONTACTPERSON);
              $contactPerson->setAttribute('contactType', $type);
              if ($oldContactPerson['subcontactType']) {
                $this->entityDescriptor->setAttributeNS(self::SAMLXMLNS_URI, 'xmlns:remd', 'http://refeds.org/metadata'); # NOSONAR Should be http://
                $contactPerson->setAttribute('remd:contactType', $oldContactPerson['subcontactType']);
              }
              $this->entityDescriptor->insertBefore($contactPerson, $child);
              $nextOrder = 1;
              while ($nextOrder < 7) {
                if (!empty($oldContactPerson[$nextOrder]['value'])) {
                  $contactPersonElement = $this->xml->createElement($oldContactPerson[$nextOrder]['part']);
                  $contactPersonElement->nodeValue = htmlspecialchars($oldContactPerson[$nextOrder]['value']);
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
        $contactPerson = $this->xml->createElement(self::SAML_MD_CONTACTPERSON);
        $contactPerson->setAttribute('contactType', $type);
        if ($oldContactPerson['subcontactType']) {
          $this->entityDescriptor->setAttributeNS(self::SAMLXMLNS_URI, 'xmlns:remd', 'http://refeds.org/metadata'); # NOSONAR Should be http://
          $contactPerson->setAttribute('remd:contactType', $oldContactPerson['subcontactType']);
        }
        $this->entityDescriptor->appendChild($contactPerson);
        $nextOrder = 1;
        while ($nextOrder < 7) {
          if (!empty($oldContactPerson[$nextOrder]['value'])) {
            $contactPersonElement = $this->xml->createElement($oldContactPerson[$nextOrder]['part']);
            $contactPersonElement->nodeValue = htmlspecialchars($oldContactPerson[$nextOrder]['value']);
            $contactPerson->appendChild($contactPersonElement);
          }
          $nextOrder++;
        }
      }
    }
  }
}
