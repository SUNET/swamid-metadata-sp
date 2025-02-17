<?php
namespace metadata;

use PDO;

class ValidateSWAMID extends Validate {
  use SAMLTrait;

  # Setup

  const BIND_DATA = ':Data';
  const BIND_ENTITYID = ':EntityID';
  const BIND_LANG = ':Lang';

  const TEXT_HTTP = 'http://';
  const TEXT_HTTPS = 'https://';
  const TEXT_521 = '5.2.1';
  const TEXT_621 = '6.2.1';

  /**
   * Validate SAML
   *
   * SWAMID Version
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
        $this->error .= $this->selectError('5.1.7', '6.1.7',
          'entityID MUST start with either urn:, https:// or http://.');
    } elseif (substr($this->entityID, 0, 4) == 'urn:' ) {
      $this->warning .= $this->selectError('5.1.7', '6.1.7', 'entityID SHOULD NOT start with urn: for new entities.');
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
    $this->checkRequiredContactPersonElements();

    if ($this->isSPandRandS) { $this->validateSPRandS(); }

    if ($this->isSPandCoCov1) { $this->validateSPCoCov1(); }
    if ($this->isSPandCoCov2) { $this->validateSPCoCov2(); }
    $this->saveResults();
  }

  /**
   * Validate LangElements
   *
   * Validate LangElements in
   *  - MDUI
   *  -
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
      FROM Mdui WHERE `type` <> 'IDPDisco' AND `entity_id` = :Id;");
    $mduiHandler->execute(array(self::BIND_ID => $this->dbIdNr));
    while ($mdui = $mduiHandler->fetch(PDO::FETCH_ASSOC)) {
      $type = $mdui['type'];
      $lang = $mdui['lang'];
      $element = $mdui['element'];
      if (isset(Common::LANG_CODES[$lang])) {
        $usedLangArray[$lang] = $lang;
      } else {
        $usedLangArray[$lang] = $lang;
        if ($type == 'SPSSO') {
          $this->error .= sprintf("SWAMID Tech 6.1.1: Lang (%s) is not a value from ISO 639-1 on mdui:%s in %sDescriptor.\n",
            $lang, $element, $type);
        } else {
          $this->error .= sprintf("SWAMID Tech 5.1.1: Lang (%s) is not a value from ISO 639-1 on mdui:%s in %sDescriptor.\n",
            $lang, $element, $type);
        }
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

    $serviceArray = array();

    $serviceElementHandler = $this->config->getDb()->prepare('SELECT `element`, `lang`, `Service_index`
      FROM AttributeConsumingService_Service WHERE `entity_id` = :Id');
    $serviceElementHandler->bindValue(self::BIND_ID, $this->dbIdNr);
    $serviceElementHandler->execute();
    while ($service = $serviceElementHandler->fetch(PDO::FETCH_ASSOC)) {
      $element = $service['element'];
      $lang = $service['lang'];
      $index = $service['Service_index'];
      $usedLangArray[$lang] = $lang;

      if (isset($serviceArray[$element][$index][$lang])) {
        $this->error .= sprintf(
          "SWAMID Tech 6.1.2: More than one %s with lang=%s in AttributeConsumingService (index=%d).\n",
          $element, $lang, $index);
      } else {
        $serviceArray[$element][$index][$lang] = true;
      }
    }

    $organizationArray = array();
    $organizationHandler = $this->config->getDb()->prepare('SELECT `lang`, `element` FROM Organization WHERE `entity_id` = :Id');
    $organizationHandler->bindValue(self::BIND_ID, $this->dbIdNr);
    $organizationHandler->execute();
    while ($organization = $organizationHandler->fetch(PDO::FETCH_ASSOC)) {
      $lang = $organization['lang'];
      $element = $organization['element'];
      $usedLangArray[$lang] = $lang;
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
          if ( $lang == '' ) {
            unset($usedLangArray[$lang]);
          } elseif (! isset($langArray[$lang])) {
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
    foreach ($serviceArray as $element => $indexArray) {
      foreach ($indexArray as $langArray) {
        foreach ($usedLangArray as $lang) {
          if (! isset($langArray[$lang])) {
            $this->error .= sprintf("SWAMID Tech 6.1.3: Missing lang=%s for %s in AttributeConsumingService with index=%d.\n",
              $lang, $element, $index);
          }
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
    $entityAttributesHandler = $this->config->getDb()->prepare('SELECT `attribute`
      FROM EntityAttributes WHERE `entity_id` = :Id AND `type` = :Type');
    $entityAttributesHandler->bindValue(self::BIND_ID, $this->dbIdNr);

    if ($type == 'IDPSSO' ) {
      //5.1.9 SWAMID Identity Assurance Profile compliance MUST be registered in
      //      the assurance certification entity attribute as defined by the profiles.
      $swamid519error = true;
      $entityAttributesHandler->bindValue(self::BIND_TYPE, 'assurance-certification');
      $entityAttributesHandler->execute();
      while ($entityAttribute = $entityAttributesHandler->fetch(PDO::FETCH_ASSOC)) {
        $swamid519error = $entityAttribute['attribute'] == 'http://www.swamid.se/policy/assurance/al1' ? # NOSONAR Should be http://
          false : $swamid519error ;
      }
      if ($swamid519error) {
        $this->error .= 'SWAMID Tech 5.1.9: SWAMID Identity Assurance Profile compliance MUST';
        $this->error .= " be registered in the assurance certification entity attribute as defined by the profiles.\n";
      }

      // 5.1.10 Entity Categories applicable to the Identity Provider SHOULD be registered in
      ///       the entity category entity attribute as defined by the respective Entity Category.
      // Not handled yet.

      $entityAttributesHandler->bindValue(self::BIND_TYPE, 'entity-category-support');
      $entityAttributesHandler->execute();
      if (! $entityAttributesHandler->fetch(PDO::FETCH_ASSOC)) {
        $this->warning .= 'SWAMID Tech 5.1.11: Support for Entity Categories SHOULD be registered in the';
        $this->warning .= " entity category support entity attribute as defined by the respective Entity Category.\n";
      }
    } else {
      $entityAttributesHandler->bindValue(self::BIND_TYPE, 'entity-category');
      $entityAttributesHandler->execute();
      while ($entityAttribute = $entityAttributesHandler->fetch(PDO::FETCH_ASSOC)) {
        if (isset(Common::STANDARD_ATTRIBUTES['entity-category'][$entityAttribute['attribute']]) &&
          ! Common::STANDARD_ATTRIBUTES['entity-category'][$entityAttribute['attribute']]['standard']) {
            $this->error .= sprintf ("Entity Category Error: The entity category %s is deprecated.\n",
              $entityAttribute['attribute']);
        }
      }
    }
  }

  # 5.1.13 errorURL
  private function checkErrorURL() {
    $errorURLHandler = $this->config->getDb()->prepare("SELECT DISTINCT `URL`
      FROM EntityURLs WHERE `entity_id` = :Id AND `type` = 'error';");
    $errorURLHandler->bindParam(self::BIND_ID, $this->dbIdNr);
    $errorURLHandler->execute();
    if (! $errorURLHandler->fetch(PDO::FETCH_ASSOC)) {
      $this->error .= "SWAMID Tech 5.1.13: IdP:s MUST have a registered errorURL.\n";
    }
  }

  // 5.1.15, 5.1.16 Scope
  private function checkIDPScope() {
    $scopesHandler = $this->config->getDb()->prepare('SELECT `scope`, `regexp` FROM Scopes WHERE `entity_id` = :Id');
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
            $this->error .= sprintf("SWAMID Tech 5.1.17: DisplayName for lang %s is also set on %s.\n",
              $lang, $duplicate['entityID']);
          }
          break;
        case 'Logo' :
          if (substr($mdui['data'],0,8) != self::TEXT_HTTPS) {
            $this->error .= "SWAMID Tech 5.1.17: Logo must start with <b>https://</b> .\n";
          }
          break;
        case 'InformationURL' :
        case 'PrivacyStatementURL' :
          if (substr($mdui['data'],0,8) != self::TEXT_HTTPS && substr($mdui['data'],0,7) != self::TEXT_HTTP) {
            $this->error .= sprintf('SWAMID Tech 5.1.17: %s must be a URL%s', $mdui['element'], ".\n");
          }
          break;
        default :
      }
    }

    foreach ($elementArray as $element => $value) {
      $this->error .= $value ? '' : sprintf("SWAMID Tech 5.1.17: Missing mdui:%s in IDPSSODecriptor.\n", $element);
    }
  }

  // 6.1.12
  private function checkRequiredMDUIelementsSP() {
    $elementArray = array ('DisplayName' => false,
      'Description' => false,
      'InformationURL' => false,
      'PrivacyStatementURL' => false);
    $mduiHandler = $this->config->getDb()->prepare("SELECT DISTINCT `element`, `data`
      FROM Mdui WHERE `entity_id` = :Id AND `type`  = 'SPSSO';");
    $mduiHandler->bindValue(self::BIND_ID, $this->dbIdNr);
    $mduiHandler->execute();
    while ($mdui = $mduiHandler->fetch(PDO::FETCH_ASSOC)) {
      $elementArray[$mdui['element']] = true;
      switch($mdui['element']) {
        case 'Logo' :
          if (substr($mdui['data'],0,8) != self::TEXT_HTTPS) {
            $this->error .= "SWAMID Tech 6.1.13: Logo must start with <b>https://</b> .\n";
          }
          break;
        case 'InformationURL' :
        case 'PrivacyStatementURL' :
          if (substr($mdui['data'],0,8) != self::TEXT_HTTPS && substr($mdui['data'],0,7) != self::TEXT_HTTP) {
            $this->error .= sprintf('SWAMID Tech 6.1.12: %s must be a URL%s', $mdui['element'], ".\n");
          }
          break;
        default :
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
    $keyInfoHandler = $this->config->getDb()->prepare('SELECT `use`, `notValidAfter`, `subject`, `issuer`, `bits`, `key_type`
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
        default :
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
            ($type == 'SPSSO') ? self::TEXT_621 : self::TEXT_521);
          $this->errorNB .= " key strength (in the sense of NIST SP 800-57) than a 2048-bit RSA key.\n";
        } else {
          $this->warning .= sprintf('SWAMID Tech %s:', ($type == 'SPSSO') ? self::TEXT_621 : self::TEXT_521);
          $this->warning .= " Certificate key strength under 4096-bit RSA is NOT RECOMMENDED.\n";
        }
      } elseif ($swamid521error == 2) {
        $this->error .= sprintf('SWAMID Tech %s: Certificate MUST NOT use shorter comparable',
          ($type == 'SPSSO') ? self::TEXT_621 : self::TEXT_521);
        $this->error .= ' key strength (in the sense of NIST SP 800-57) than a 2048-bit RSA key. New certificate';
        $this->error .= " should be have a key strength of at least 4096 bits for RSA or 384 bits for EC.\n";
      }
    } else {
      if ($smalKeyFound) {
        $this->errorNB .= sprintf('SWAMID Tech %s: (NonBreaking) Certificate MUST NOT use shorter comparable',
          ($type == 'SPSSO') ? self::TEXT_621 : self::TEXT_521);
        $this->errorNB .= " key strength (in the sense of NIST SP 800-57) than a 2048-bit RSA key.\n";
      }
    }
    if ($swamid5212030error) {
      $this->warning .= sprintf('SWAMID Tech %s: Certificate MUST NOT use shorter comparable key strength',
        ($type == 'SPSSO') ? self::TEXT_621 : self::TEXT_521);
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

  /* This needs to be checked !!!
  // 6.1.16
  private function checkAssertionConsumerService($data) {
    $binding = $data->getAttribute('Binding');
    if ($binding == self::SAML_BINDING_HTTP_REDIRECT) {
      $this->swamid6116error = true;
    }
  }*/

  // 5.1.22 / 6.1.21
  private function checkRequiredOrganizationElements() {
    $elementArray = array('OrganizationName' => false, 'OrganizationDisplayName' => false, 'OrganizationURL' => false);

    $organizationHandler = $this->config->getDb()->prepare('SELECT DISTINCT `element`
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
  private function checkRequiredContactPersonElements() {
    $usedContactTypes = array();
    $contactPersonHandler = $this->config->getDb()->prepare('SELECT `contactType`, `subcontactType`, `emailAddress`, `givenName`
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
      if ($this->isIdP) {
        $this->error .= $this->selectError('5.1.27', '6.1.26', 'Missing ContactPerson of type support.');
      } else {
        $this->warning .= $this->selectError('5.1.27', '6.1.26', 'Missing ContactPerson of type support.');
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

  /**
   * Select Error
   *
   * Select Error bases on if entity is IdP, SP or both
   *
   * @param string $idpCode error-code if IdP
   *
   * @param string $spCode error-code if SP
   *
   * @param string $error error messege to apend after idp/sp code
   *
   *
   * @return string
   */
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
}
