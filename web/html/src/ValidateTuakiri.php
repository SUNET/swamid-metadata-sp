<?php
namespace metadata;

use PDO;

/**
 * Class to Validate SAML information
 * Tuakiri specific code
 */
class ValidateTuakiri extends Validate {
  use CommonTrait;

  # Setup

  const TEXT_HTTP = 'http://';
  const TEXT_HTTPS = 'https://';
  const TEXT_DATA = 'data:';

  /**
   * Validate SAML
   *
   * Tuakiri Version
   * Validates SAML of an Entity.
   *  - Correct EC:s
   *  - ....
   *
   * @return void
   */
  public function saml(){
    if (! $this->entityExists) {
      return 1;
    }

    $this->getEntityAttributes();

    // 5.1.1 -> 5.1.5 / 6.1.1 -> 6.1.5
    $this->checkLangElements();
    // 5.1.7 /6.1.7
    if (! (substr($this->entityID, 0, 4) == 'urn:' ||
      substr($this->entityID, 0, 8) == self::TEXT_HTTPS ||
      substr($this->entityID, 0, 7) == self::TEXT_HTTP )) {
        $this->error .= "entityID MUST start with either urn:, https:// or http://.\n";
    } elseif (substr($this->entityID, 0, 4) == 'urn:' ) {
      $this->warning .= "entityID SHOULD NOT start with urn: for new entities.\n";
    }

    // 5.1.8 /6.1.8
    if (strlen($this->entityID) > 256) {
      $this->error .= "entityID MUST NOT exceed 256 characters.\n";
    }

    if ($this->isIdP) {
      // 5.1.9 -> 5.1.12
      $this->checkEntityAttributes('IDPSSO');
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

      $this->validateSPServiceInfo();
    }
    if ($this->isAA) {
      // 5.1.20, 5.2.x
      $this->checkRequiredSAMLcertificates('AttributeAuthority');
    }

    // 5.1.22 / 6.1.20
    $this->checkRequiredOrganizationElements();

    // 5.1.23 -> 5.1.28 / 6.1.21 -> 6.1.26
    $this->checkRequiredContactPersonElements();

    if ($this->isSPandRandS) { $this->validateSPRandS(); }

    if ($this->isSPandCoCov1) { $this->validateSPCoCov1(); }
    if ($this->isSPandCoCov2) { $this->validateSPCoCov2(); }
    if (! $this->isSIRTFI2) {
      $this->warning .= 'eduGAIN is in the process of introducing a requirement for all entities published in eduGAIN to support ';
      $this->warning .= "the Security Incident Response Trust Framework for Federated Identity (Sirtfi) Version 2.\n";
    }
    $this->saveResults();
  }

  /**
   * Validate LangElements
   *
   * Validate LangElements in
   *  - MDUI
   *  - AttributeConsumingService
   *  - Organization
   *
   * SWAMID Tech
   *  - 5.1.1 -> 5.1.5
   *  - 6.1.1 -> 6.1.5
   *
   * @return void
   */
  private function checkLangElements() {
    $mduiArray = array();
    $usedLangArray = array();
    $mduiHandler = $this->config->getDb()->prepare("SELECT `type`, `lang`, `element`
      FROM `Mdui` WHERE `type` <> 'IDPDisco' AND `entity_id` = :Id;");
    $mduiHandler->execute(array(self::BIND_ID => $this->dbIdNr));
    while ($mdui = $mduiHandler->fetch(PDO::FETCH_ASSOC)) {
      $type = $mdui['type'];
      $lang = $mdui['lang'];
      $element = $mdui['element'];
      if (isset(self::LANG_CODES[$lang])) {
        $usedLangArray[$lang] = $lang;
      } else {
        $usedLangArray[$lang] = $lang;
        $this->error .= sprintf("Lang (%s) is not a value from ISO 639-1 on mdui:%s in %sDescriptor.\n", $lang, $element, $type);
      }

      if (! isset ($mduiArray[$type])) {
        $mduiArray[$type] = array();
      }
      if (! isset ($mduiArray[$type][$element])) {
        $mduiArray[$type][$element] = array();
      }

      if (isset($mduiArray[$type][$element][$lang])) {
        if ($element != 'Logo') {
          $this->error .= sprintf("More than one mdui:%s with lang=%s in %sDescriptor.\n", $element, $lang, $type);
        }
      } else {
        $mduiArray[$type][$element][$lang] = true;
      }
    }

    $serviceArray = array();

    $serviceElementHandler = $this->config->getDb()->prepare('SELECT `element`, `lang`, `Service_index`
      FROM `AttributeConsumingService_Service` WHERE `entity_id` = :Id');
    $serviceElementHandler->bindValue(self::BIND_ID, $this->dbIdNr);
    $serviceElementHandler->execute();
    while ($service = $serviceElementHandler->fetch(PDO::FETCH_ASSOC)) {
      $element = $service['element'];
      $lang = $service['lang'];
      $index = $service['Service_index'];
      $usedLangArray[$lang] = $lang;

      if (isset($serviceArray[$element][$index][$lang])) {
        $this->error .= sprintf(
          "More than one %s with lang=%s in AttributeConsumingService (index=%d).\n",
          $element, $lang, $index);
      } else {
        $serviceArray[$element][$index][$lang] = true;
      }
    }

    $organizationArray = array();
    $organizationHandler = $this->config->getDb()->prepare('SELECT `lang`, `element` FROM `Organization` WHERE `entity_id` = :Id');
    $organizationHandler->bindValue(self::BIND_ID, $this->dbIdNr);
    $organizationHandler->execute();
    while ($organization = $organizationHandler->fetch(PDO::FETCH_ASSOC)) {
      $lang = $organization['lang'];
      $element = $organization['element'];
      $usedLangArray[$lang] = $lang;
      if (isset($organizationArray[$element][$lang])) {
        $this->error .= sprintf("More than one %s with lang=%s in Organization.\n", $element, $lang);
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
          if ( $lang == '' ) {
            unset($usedLangArray[$lang]);
          } elseif (! isset($langArray[$lang])) {
            $this->error .= sprintf("Missing lang=%s for mdui:%s in %sDescriptor.\n", $lang, $element, $type);
          }
        }
      }
    }
    foreach ($serviceArray as $element => $indexArray) {
      foreach ($indexArray as $langArray) {
        foreach ($usedLangArray as $lang) {
          if (! isset($langArray[$lang])) {
            $this->error .= sprintf("Missing lang=%s for %s in AttributeConsumingService with index=%d.\n",
              $lang, $element, $index);
          }
        }
      }
    }
    foreach ($organizationArray as $element => $langArray) {
      foreach ($usedLangArray as $lang) {
        if (! isset($langArray[$lang])) {
          $this->error .= sprintf("Missing lang=%s for %s in Organization.\n", $lang, $element);
        }
      }
    }

    //5.1.4/6.1.4 Metadata elements that support the lang attribute MUST have a definition with language English (en).
    if (! isset($usedLangArray['en'])) {
      $this->error .= "Missing MDUI/Organization/... with lang=en.\n";
    }
  }

  /**
   * Validate Entity Attributes
   *
   * Validate Entity Attributes
   *  - EntityCategory
   *  - EntityCategorySupport
   *  - Assurance Certification
   *
   * SWAMID Tech
   *  - 5.1.9 -> 5.1.11
   *  - 6.1.9 -> 6.1.11
   *
   * @return void
   */
  private function checkEntityAttributes($type) {
    $entityAttributesHandler = $this->config->getDb()->prepare('SELECT `attribute`
      FROM `EntityAttributes` WHERE `entity_id` = :Id AND `type` = :Type');
    $entityAttributesHandler->bindValue(self::BIND_ID, $this->dbIdNr);

    if ($type == 'IDPSSO' ) {
      // 5.1.10 Entity Categories applicable to the Identity Provider SHOULD be registered in
      ///       the entity category entity attribute as defined by the respective Entity Category.
      // Not handled yet.

      $entityAttributesHandler->bindValue(self::BIND_TYPE, 'entity-category-support');
      $entityAttributesHandler->execute();
      // Issue a warning if no EC support declared: at least RnS should be supported even in Tuakiri
      if (! $entityAttributesHandler->fetch(PDO::FETCH_ASSOC)) {
        $this->warning .= 'Support for Entity Categories SHOULD be registered in the';
        $this->warning .= " entity category support entity attribute as defined by the respective Entity Category.\n";
      }
    } else {
      $entityAttributesHandler->bindValue(self::BIND_TYPE, 'entity-category');
      $entityAttributesHandler->execute();
      while ($entityAttribute = $entityAttributesHandler->fetch(PDO::FETCH_ASSOC)) {
        // detect deprecated Entity Categories
        if (isset(self::STANDARD_ATTRIBUTES['entity-category'][$entityAttribute['attribute']]) &&
          ! self::STANDARD_ATTRIBUTES['entity-category'][$entityAttribute['attribute']]['standard']) {
            $this->error .= sprintf ("Entity Category Error: The entity category %s is deprecated.\n",
              $entityAttribute['attribute']);
        }
      }
    }
  }

  /**
   * Validate IdP Scope
   *
   * Validate IdP ScopeEntity Attributes
   *  - Missing Scope
   *
   * @return void
   */
  private function checkIDPScope() {
    $scopesHandler = $this->config->getDb()->prepare('SELECT `scope`, `regexp` FROM `Scopes` WHERE `entity_id` = :Id');
    $scopesHandler->bindParam(self::BIND_ID, $this->dbIdNr);
    $scopesHandler->execute();
    $missingScope = true;
    while ($scope = $scopesHandler->fetch(PDO::FETCH_ASSOC)) {
      $missingScope = false;
    }
    if ($missingScope) {
      $this->error .= "A SAML IdP MUST have at least one Scope registered.\n";
    }
  }

  /**
   * Validate Required MDUI-elements IdP
   *
   * Validate Required MDUI-elements for an IdP
   *  - DisplayName
   *  - Logo
   *
   * Further validate:
   *  - Logo
   *  - InformationURL
   *  - PrivacyStatementURL
   *
   * @return void
   */
  private function checkRequiredMDUIelementsIdP() {
    $elementArray = array ('DisplayName' => false,
      'Logo' => false);
    $mduiDNUniqHandler = $this->config->getDb()->prepare("SELECT `entityID`
      FROM `Entities`, `Mdui`
      WHERE `Entities`.`id` = `entity_id`
        AND `type`  = 'IDPSSO'
        AND `element` = 'DisplayName'
        AND `data` = :Data
        AND `lang` = :Lang
        AND `status` = 1
        AND `entityID` <> :EntityID;");
    $mduiHandler = $this->config->getDb()->prepare('SELECT `entityID`, `element`, `data`, `lang`
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
          $mduiDNUniqHandler->bindParam(self::BIND_DATA, $data);
          $mduiDNUniqHandler->bindParam(self::BIND_LANG, $lang);
          $mduiDNUniqHandler->bindParam(self::BIND_ENTITYID, $entityID);
          $mduiDNUniqHandler->execute();
          while ($duplicate = $mduiDNUniqHandler->fetch(PDO::FETCH_ASSOC)) {
            $this->error .= sprintf("DisplayName for lang %s is also set on %s.\n",
              $lang, htmlspecialchars($duplicate['entityID']));
          }
          break;
        case 'Logo' :
          if (substr($mdui['data'],0,8) != self::TEXT_HTTPS && substr($mdui['data'],0,5) != self::TEXT_DATA) {
            $this->error .= "Logo must start with <b>https://</b> or <b>data:</b>.\n";
          }
          break;
        case 'InformationURL' :
        case 'PrivacyStatementURL' :
          if (substr($mdui['data'],0,8) != self::TEXT_HTTPS && substr($mdui['data'],0,7) != self::TEXT_HTTP) {
            $this->error .= sprintf('%s must be a URL%s', $mdui['element'], ".\n");
          }
          break;
        default :
      }
    }

    foreach ($elementArray as $element => $value) {
      $this->error .= $value ? '' : sprintf("Missing mdui:%s in IDPSSODecriptor.\n", $element);
    }
  }

  /**
   * Validate Required MDUI-elements SP
   *
   * Validate Required MDUI-elements for a SP
   *  - DisplayName
   *
   * Further validate:
   *  - Logo
   *  - InformationURL
   *  - PrivacyStatementURL
   *
   * @return void
   */
  private function checkRequiredMDUIelementsSP() {
    $elementArray = array ('DisplayName' => false);
    $mduiHandler = $this->config->getDb()->prepare("SELECT DISTINCT `element`, `data`
      FROM `Mdui` WHERE `entity_id` = :Id AND `type`  = 'SPSSO';");
    $mduiHandler->bindValue(self::BIND_ID, $this->dbIdNr);
    $mduiHandler->execute();
    while ($mdui = $mduiHandler->fetch(PDO::FETCH_ASSOC)) {
      $elementArray[$mdui['element']] = true;
      switch($mdui['element']) {
        case 'Logo' :
          if (substr($mdui['data'],0,8) != self::TEXT_HTTPS && substr($mdui['data'],0,5) != self::TEXT_DATA) {
            $this->error .= "Logo must start with <b>https://</b> or <b>data:</b>.\n";
          }
          break;
        case 'InformationURL' :
        case 'PrivacyStatementURL' :
          if (substr($mdui['data'],0,8) != self::TEXT_HTTPS && substr($mdui['data'],0,7) != self::TEXT_HTTP) {
            $this->error .= sprintf('%s must be a URL%s', $mdui['element'], ".\n");
          }
          break;
        default :
      }
    }

    foreach ($elementArray as $element => $value) {
      if (! $value) {
        $this->error .= sprintf("Missing mdui:%s in SPSSODescriptor.\n", $element);
      }
    }
  }

  /**
   * Validate Certificates
   *
   * Validate Certificates
   *  - Length of certs
   *  - Validity of certs
   *  - Required cert exists
   *
   * SWAMID Tech
   *  - 5.1.20, 5.2.x
   *  - 6.1.14, 6.2.x
   *
   * @return void
   */
  private function checkRequiredSAMLcertificates($type) {
    $keyInfoArray = array ('IDPSSO' => false, 'SPSSO' => false, 'AttributeAuthority' => false);
    $keyInfoHandler = $this->config->getDb()->prepare('SELECT `use`, `notValidAfter`, `subject`, `issuer`, `bits`, `key_type`
      FROM `KeyInfo`
      WHERE `entity_id` = :Id AND `type` =:Type
      ORDER BY notValidAfter DESC');
    $keyInfoHandler->bindValue(self::BIND_ID, $this->dbIdNr);
    $keyInfoHandler->bindValue(self::BIND_TYPE, $type);
    $keyInfoHandler->execute();

    $swamid521Level = array ('encryption' => 0, 'signing' => 0, 'both' => 0);
    $swamid521error = 0;
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
        default :
          break;
      }
      switch ($keyInfo['key_type']) {
        case 'RSA' :
        case 'DSA' :
          if ($keyInfo['bits'] >= 3072 ) {
            $swamid521Level[$keyInfo['use']] = 3;
          } elseif ($keyInfo['bits'] >= 2048 && $swamid521Level[$keyInfo['use']] < 2 ) {
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
        default :
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
          $keyInfo['use'], htmlspecialchars($keyInfo['subject']),
          'New certificate should be have a key strength of at least 3072 bits for RSA or 384 bits for EC.');
      }

      if ($keyInfo['subject'] != $keyInfo['issuer']) {
        $swamid523warning = true;
      }
    }

    if (! $keyInfoArray[$type]) {
      if ($type == 'IDPSSO') {
        $this->error .=
          "Identity Providers MUST have at least one valid signing certificate.\n";
      } elseif ($type == 'AttributeAuthority') {
        $this->error .=
          "Attribute Authorities MUST have at least one valid signing certificate.\n";
      } else {
        $this->error .=
          "Service Providers MUST have at least one valid encryption certificate.\n";
      }
    }
    // 5.2.1 Identity Provider credentials (i.e. entity keys)
    //       MUST NOT use shorter comparable key strength
    //       (in the sense of NIST SP 800-57) than a 2048-bit RSA key. 3072-bit is RECOMMENDED.
    // 6.2.1 Relying Party credentials (i.e. entity keys)
    //       MUST NOT use shorter comparable key strength (in the sense of NIST SP 800-57)
    //       than 2048-bit RSA/DSA keysor 256-bit ECC keys. 3072-bit RSA/DSAkeysor 384-bitECC keys are RECOMMENDED
    // At least one cert exist that is used for either signing or encryption,
    //  Error = code for cert with lowest # of bits
    foreach (array('encryption', 'signing') as $use) {
      if ($swamid521Level[$use] > 0) {
        switch ($swamid521Level[$use]) {
          case 3 :
            // Key >= 3072 or >= 384
            // Do nothing. Keep current level.
            break;
          case 2 :
            // Key >= 2048 and < 3072  // >= 256 and <384
            $swamid521error = $swamid521error == 0 ? 1 : $swamid521error;
            break;
          case 1 :
            // To small key
            $swamid521error = 2;
            break;
          default :
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
          // Key >= 3072 or >= 384
          $swamid521error = 0;
          break;
        case 2 :
          // Key >= 2048 and < 3072  // >= 256 and <384
          if ($keyFound) {
            // If already checked enc/signing lower if we are better
            $swamid521error = $swamid521error > 1 ? 1 : $swamid521error;
          } else {
            // No enc/siging found set warning
            $swamid521error = 1;
          }
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
          $this->errorNB .= '(NonBreaking) Certificate MUST NOT use shorter comparable';
          $this->errorNB .= " key strength (in the sense of NIST SP 800-57) than a 2048-bit RSA key.\n";
        } else {
          $this->warning .= "Certificate key strength under 3072-bit RSA is NOT RECOMMENDED.\n";
        }
      } elseif ($swamid521error == 2) {
        $this->error .= 'Certificate MUST NOT use shorter comparable';
        $this->error .= ' key strength (in the sense of NIST SP 800-57) than a 2048-bit RSA key. New certificate';
        $this->error .= " should be have a key strength of at least 3072 bits for RSA or 384 bits for EC.\n";
      }
    } else {
      if ($smalKeyFound) {
        $this->errorNB .= '(NonBreaking) Certificate MUST NOT use shorter comparable';
        $this->errorNB .= " key strength (in the sense of NIST SP 800-57) than a 2048-bit RSA key.\n";
      }
    }

    if ($swamid522error) {
      $this->error .= 'Signing and encryption certificates MUST NOT be expired. New';
      $this->error .= " certificate should be have a key strength of at least 3072 bits for RSA or 384 bits for EC.\n";
    } elseif ($swamid522errorNB) {
      $this->errorNB .= '(NonBreaking) Signing and encryption certificates';
      $this->errorNB .= " MUST NOT be expired.\n";
    }

    if ($oldCertFound) {
      $this->warning .= "One or more old certs found. Please remove when new certs have propagated.\n";
    }

    if ($swamid523warning) {
      $this->warning .= "Signing and encryption certificates SHOULD be self-signed.\n";
    }
  }

  /**
   * Validate Required Organization Elements
   *
   * Validate Required Organization Elements
   *  - OrganizationName
   *  - OrganizationDisplayName
   *  - OrganizationURL
   *
   * @return void
   */
  private function checkRequiredOrganizationElements() {
    $elementArray = array('OrganizationName' => false, 'OrganizationDisplayName' => false, 'OrganizationURL' => false);

    $organizationHandler = $this->config->getDb()->prepare('SELECT DISTINCT `element`
      FROM `Organization` WHERE `entity_id` = :Id');
    $organizationHandler->bindValue(self::BIND_ID, $this->dbIdNr);
    $organizationHandler->execute();
    while ($organization = $organizationHandler->fetch(PDO::FETCH_ASSOC)) {
      $elementArray[$organization['element']] = true;
    }

    foreach ($elementArray as $element => $value) {
      if (! $value) {
        $this->error .= sprintf("Missing %s in Organization.\n", $element);
      }
    }
  }

  /**
   * Validate Required Contact Person Elements
   *
   * Validate Required Contact Person Elements
   *
   * SWAMID Tech
   *  - 5.1.23 -> 5.1.28
   *  - 6.1.22 -> 6.1.26
   *
   * @return void
   */
  protected function checkRequiredContactPersonElements() {
    $usedContactTypes = array();
    $contactPersonHandler = $this->config->getDb()->prepare('SELECT `contactType`, `subcontactType`, `emailAddress`, `givenName`
      FROM `ContactPerson` WHERE `entity_id` = :Id');
    $contactPersonHandler->bindValue(self::BIND_ID, $this->dbIdNr);
    $contactPersonHandler->execute();

    while ($contactPerson = $contactPersonHandler->fetch(PDO::FETCH_ASSOC)) {
      $contactType = $contactPerson['contactType'];

      // 5.1.28 Identity Providers / 6.1.27 A Relying Party SHOULD have one ContactPerson element
      //        of contactType other with remd:contactType http://refeds.org/metadata/contactType/security.
      // If the element is present, a GivenName element MUST be present and the ContactPerson MUST
      //  respect the Traffic Light Protocol (TLP) during all incident response correspondence.
      if ($contactType == 'other' &&  $contactPerson['subcontactType'] == 'security' ) {
        $contactType = self::CT_SECURITY;
        if ( $contactPerson['givenName'] == '') {
          $this->error .= "GivenName element MUST be present for security ContactPerson.\n";
        }
      }

      // 5.1.23/6.1.22 ContactPerson elements MUST have an EmailAddress element
      if ($contactPerson['emailAddress'] == '') {
        $this->error .= sprintf("ContactPerson [%s] elements MUST have an EmailAddress element.\n", $contactType);
      } elseif (substr($contactPerson['emailAddress'], 0, 7) != 'mailto:') {
        $this->error .= sprintf("ContactPerson [%s] EmailAddress MUST start with mailto:.\n", $contactType);
      }
      if ( !isset($usedContactTypes[$contactType])) {
        $usedContactTypes[$contactType] = true;
      }
      $contactEmail[$contactType] = $contactPerson['emailAddress'];
    }

    if ($this->isIdP) {
      // IdPs MUST have one ContactPerson element of type technical.
      if (!isset ($usedContactTypes['technical'])) {
        $this->error .= "Missing ContactPerson of type technical.\n";
      }
    } else {
      // SPs MUST have one ContactPerson element of type administrative and/or technical.
      if (!isset ($usedContactTypes['administrative']) && !isset ($usedContactTypes['technical'])) {
        $this->error .= "Missing ContactPerson of type administrative and/or technical\n";
      }
      // SPs SHOULD have one ContactPerson element of type technical.
      if (!isset ($usedContactTypes['technical'])) {
        $this->warning .= "Missing ContactPerson of type technical.\n";
      }
    }

    // IdP or SP SHOULD have one ContactPerson element of type administrative.
    if (!isset ($usedContactTypes['administrative'])) {
      $this->warning .= "Missing ContactPerson of type administrative.\n";
    }

    // IdP or SP SHOULD have one ContactPerson element of type support.
    if (!isset ($usedContactTypes['support'])) {
      if ($this->isIdP) {
        $this->warning .= "Missing ContactPerson of type support.\n";
      }
    }

    // 5.1.28 / 6.1.26 Identity Providers SHOULD have one ContactPerson element of contactType other
    if (!isset ($usedContactTypes[self::CT_SECURITY])) {
      if ($this->isSIRTFI || $this->isSIRTFI2) {
        $this->error .= "REFEDS Sirtfi Require that a security contact is published in the entity’s metadata.\n";
      } elseif ($this->isIdP) {
        $this->warning .= "Missing security ContactPerson.\n";
        $this->warning .= 'eduGAIN is in the process of introducing a requirement for all entities ';
        $this->warning .= "published in eduGAIN to publish a security contact in metadata.\n";
      }
    } elseif (isset($contactEmail['support']) && $contactEmail['support'] == $contactEmail[self::CT_SECURITY]) {
      $this->warning .= 'Tuakiri advises against using the same email address for both support and security contact, ';
      $this->warning .= "as the security contact is used for sensitive communication and must comply with the Traffic Light Protocol.\n";
    }
  }
}
