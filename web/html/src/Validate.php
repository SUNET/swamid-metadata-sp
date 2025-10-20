<?php
namespace metadata;

use PDO;

/**
 * Class to Validate SAML information
 */
class Validate extends Common {

  # Setup
  /**
   * Requests https://refeds.org/category/research-and-scholarship
   */
  protected $isSPandRandS = false;
  /**
   * Requests http://www.geant.net/uri/dataprotection-code-of-conduct/v1
   */
  protected $isSPandCoCov1 = false;
  /**
   * Requests https://refeds.org/category/code-of-conduct/v2
   */
  protected $isSPandCoCov2 = false;
  /**
   * Supports https://refeds.org/sirtfi
   */
  protected $isSIRTFI = false;
  /**
   * Supports https://refeds.org/sirtfi2
   */
  protected $isSIRTFI2 = false;

  const TEXT_COCOV1_REC = 'GÉANT Data Protection Code of Conduct v1 Recomend';
  const TEXT_COCOV1_REQ = 'GÉANT Data Protection Code of Conduct v1 Require';
  const TEXT_COCOV2_REC = 'REFEDS Data Protection Code of Conduct v2 Recommend';
  const TEXT_COCOV2_REQ = 'REFEDS Data Protection Code of Conduct v2 Require';
  const CT_SECURITY = 'other/security';


  /**
   * Setup the class
   * Call parent but without $this->xml
   *
   * @return void
   */
  public function __construct($id) {
    parent::__construct($id, false);
  }

  /**
   * Validate SAML
   *
   * Validates SAML of an Entity.
   *  - Correct EC:s
   *  - ....
   *
   * @param int $entityId Id of Entity in database.
   *
   * @return void
   */
  public function saml(){
    if (! $this->entityExists) {
      return 1;
    }

    $this->getEntityAttributes();

    $this->checkRequiredContactPersonElements();

    if ($this->isSPandRandS) { $this->validateSPRandS(); }

    if ($this->isSPandCoCov1) { $this->validateSPCoCov1(); }
    if ($this->isSPandCoCov2) { $this->validateSPCoCov2(); }
    $this->saveResults();
  }

  /**
   * Checks EntityAttributes
   *
   * Evaluets different EntityCategories and Assurance Certifications
   * and stores in variables to be used later in checks
   *
   * @return void
   */
  protected function getEntityAttributes() {
    $this->isSPandRandS = false;
    $this->isSPandCoCov1 = false;
    $this->isSPandCoCov2 = false;
    $this->isSIRTFI = false;
    $this->isSIRTFI2 = false;

    $entityAttributesHandler = $this->config->getDb()->prepare('SELECT `type`, `attribute`
      FROM `EntityAttributes` WHERE `entity_id` = :Id');
    $entityAttributesHandler->bindValue(self::BIND_ID, $this->dbIdNr);
    $entityAttributesHandler->execute();
    while ($entityAttribute = $entityAttributesHandler->fetch(PDO::FETCH_ASSOC)) {
      if ($entityAttribute['type'] == 'assurance-certification') {
        switch ($entityAttribute['attribute']) {
          case 'https://refeds.org/sirtfi' :
            $this->isSIRTFI = true;
            break;
          case 'https://refeds.org/sirtfi2' :
            $this->isSIRTFI2 = true;
            break;
          default :
        }
      } elseif ($entityAttribute['type'] == 'entity-category' && $this->isSP) {
        switch ($entityAttribute['attribute']) {
          case 'http://refeds.org/category/research-and-scholarship' : # NOSONAR Should be http://
            $this->isSPandRandS = true;
            break;
          case 'http://www.geant.net/uri/dataprotection-code-of-conduct/v1' :  # NOSONAR Should be http://
            $this->isSPandCoCov1 = true;
            break;
          case 'https://refeds.org/category/code-of-conduct/v2' :
            $this->isSPandCoCov2 = true;
            break;
          default :
        }
      }
    }
    if ($this->isSIRTFI2 && ! $this->isSIRTFI) {
      $this->error .= "REFEDS Sirtfi2 Require support for REFEDS Sirtfi.\n";
    }
  }

  /**
   * Validate Required Contact Person Elements
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

      // If a contactType other with remd:contactType http://refeds.org/metadata/contactType/securitythe element is present,
      // a GivenName element MUST be present and the ContactPerson MUST
      // respect the Traffic Light Protocol (TLP) during all incident response correspondence.
      if ($contactType == 'other' &&  $contactPerson['subcontactType'] == 'security' ) {
        $contactType = self::CT_SECURITY;
        if ( $contactPerson['givenName'] == '') {
          $this->error .= "REFEDS Security Contact Metadata Extension Require that a GivenName element MUST be present for security ContactPerson.\n";
        }
        if ( $contactPerson['emailAddress'] == '') {
          $this->error .= "REFEDS Security Contact Metadata Extension Require that a EmailAddress element MUST be present for security ContactPerson.\n";
        }
        $usedContactTypes[$contactType] = true;
      }
    }

    // SIRTFI requires a Security contact
    if (($this->isSIRTFI || $this->isSIRTFI2) && !isset ($usedContactTypes[self::CT_SECURITY])) {
      $this->error .= "REFEDS Sirtfi Require that a security contact is published in the entity’s metadata.\n";
    }
  }

  /**
   * Validate R&S
   *
   * Validate that SP fulfills all rules for R&S
   * - https://refeds.org/category/research-and-scholarship
   *
   * @return void
   */
  protected function validateSPRandS() {
    $mduiArray = array();
    $mduiHandler = $this->config->getDb()->prepare("SELECT `lang`, `element`
      FROM `Mdui` WHERE `type` = 'SPSSO' AND `entity_id` = :Id;");
    $mduiHandler->bindValue(self::BIND_ID, $this->dbIdNr);
    $mduiHandler->execute();
    while ($mdui = $mduiHandler->fetch(PDO::FETCH_ASSOC)) {
      $lang = $mdui['lang'];
      $element = $mdui['element'];
      $mduiArray[$element][$lang] = true;
    }

    if (isset($mduiArray['DisplayName']) && isset($mduiArray['InformationURL'])) {
      if (! (isset($mduiArray['DisplayName']['en']) && isset($mduiArray['InformationURL']['en']))) {
        $this->warning .= 'REFEDS Research and Scholarship 4.3.3 RECOMMEND an MDUI:DisplayName';
        $this->warning .= " and an MDUI:InformationURL with lang=en.\n";
      }
    } else {
      $this->error .= "REFEDS Research and Scholarship 4.3.3 Require an MDUI:DisplayName and an MDUI:InformationURL.\n";
    }

    $contactPersonHandler = $this->config->getDb()->prepare("SELECT `emailAddress`
      FROM `ContactPerson` WHERE `contactType` = 'technical' AND `entity_id` = :Id;");
    $contactPersonHandler->bindValue(self::BIND_ID, $this->dbIdNr);
    $contactPersonHandler->execute();
    if (! $contactPersonHandler->fetch(PDO::FETCH_ASSOC)) {
      $this->error .= 'REFEDS Research and Scholarship 4.3.4 Require that the Service Provider provides';
      $this->error .= " one or more technical contacts in metadata.\n";
    }
  }

  /**
   * Validate CoCoSP v1
   *
   * Validate that SP fulfills all rules for Coco v1
   * - https://geant3plus.archive.geant.net/Pages/uri/V1.html
   *
   * @return void
   */
  protected function validateSPCoCov1() {
    $mduiArray = array();
    $mduiElementArray = array();
    $mduiHandler = $this->config->getDb()->prepare("SELECT `lang`, `element`, `data`
      FROM `Mdui` WHERE `type` = 'SPSSO' AND `entity_id` = :Id;");
    $requestedAttributeHandler = $this->config->getDb()->prepare('SELECT DISTINCT `Service_index`
      FROM `AttributeConsumingService_RequestedAttribute` WHERE `entity_id` = :Id');
    $mduiHandler->bindValue(self::BIND_ID, $this->dbIdNr);
    $requestedAttributeHandler->bindValue(self::BIND_ID, $this->dbIdNr);
    $mduiHandler->execute();
    while ($mdui = $mduiHandler->fetch(PDO::FETCH_ASSOC)) {
      $lang = $mdui['lang'];
      $element = $mdui['element'];
      $data = $mdui['data'];

      $mduiArray[$lang][$element] = $data;
      $mduiElementArray[$element] = true;
      if ($element == 'PrivacyStatementURL' ) {
        $this->addURL($data, 3);
      }
    }

    if (! isset($mduiArray['en']['PrivacyStatementURL'])) {
      $this->error .= self::TEXT_COCOV1_REQ;
      $this->error .= " an MDUI - PrivacyStatementURL with at least lang=en.\n";
    }
    if (! isset($mduiArray['en']['DisplayName'])) {
      $this->warning .= self::TEXT_COCOV1_REC;
      $this->warning .= " an MDUI - DisplayName with at least lang=en.\n";
    }
    if (! isset($mduiArray['en']['Description'])) {
      $this->warning .= self::TEXT_COCOV1_REC;
      $this->warning .= " an MDUI - Description with at least lang=en.\n";
    }
    foreach ($mduiElementArray as $element => $value) {
      if (! isset($mduiArray['en'][$element])) {
        $this->error .= self::TEXT_COCOV1_REQ;
        $this->error .= sprintf(" an MDUI - %s with lang=en for all present elements.\n", $element);
      }
    }
    $requestedAttributeHandler->execute();
    if (! $requestedAttributeHandler->fetch(PDO::FETCH_ASSOC)) {
      $this->error .= "GÉANT Data Protection Code of Conduct Require at least one RequestedAttribute.\n";
    }
  }

  /**
   * Validate CoCoSP v2
   *
   * Validate that SP fulfills all rules for Coco v2
   * - https://refeds.org/category/code-of-conduct/v2
   *
   * @return void
   */
  protected function validateSPCoCov2() {
    $mduiArray = array();
    $mduiElementArray = array();
    $mduiHandler = $this->config->getDb()->prepare("SELECT `lang`, `element`, `data`
      FROM `Mdui` WHERE `type` = 'SPSSO' AND `entity_id` = :Id;");
    $requestedAttributeHandler = $this->config->getDb()->prepare('SELECT DISTINCT `Service_index`
      FROM `AttributeConsumingService_RequestedAttribute` WHERE `entity_id` = :Id');
    $entityAttributesHandler =  $this->config->getDb()->prepare('SELECT attribute
      FROM `EntityAttributes` WHERE `type` = :Type AND `entity_id` = :Id');
    $mduiHandler->bindValue(self::BIND_ID, $this->dbIdNr);
    $requestedAttributeHandler->bindValue(self::BIND_ID, $this->dbIdNr);
    $entityAttributesHandler->bindValue(self::BIND_ID, $this->dbIdNr);
    $mduiHandler->execute();
    while ($mdui = $mduiHandler->fetch(PDO::FETCH_ASSOC)) {
      $lang = $mdui['lang'];
      $element = $mdui['element'];
      $data = $mdui['data'];

      $mduiArray[$lang][$element] = $data;
      $mduiElementArray[$element] = true;
      if ($element == 'PrivacyStatementURL' ) {
        $this->addURL($data, 2);
      }
    }

    if (! isset($mduiArray['en']['PrivacyStatementURL'])) {
      $this->error .= self::TEXT_COCOV2_REQ;
      $this->error .= " an MDUI - PrivacyStatementURL with at least lang=en.\n";
    }
    if (! isset($mduiArray['en']['DisplayName'])) {
      $this->warning .= self::TEXT_COCOV2_REC;
      $this->warning .= " an MDUI - DisplayName with at least lang=en.\n";
    }
    if (! isset($mduiArray['en']['Description'])) {
      $this->warning .= self::TEXT_COCOV2_REC;
      $this->warning .= " an MDUI - Description with at least lang=en.\n";
    }
    foreach ($mduiElementArray as $element => $value) {
      if (! isset($mduiArray['en'][$element])) {
        $this->error .= self::TEXT_COCOV2_REQ;
        $this->error .= sprintf(" an MDUI - %s with lang=en for all present elements.\n", $element);
      }
    }

    $requestedAttributeHandler->execute();
    if (! $requestedAttributeHandler->fetch(PDO::FETCH_ASSOC)) {
      $entityAttributesHandler->bindValue(self::BIND_TYPE, 'subject-id:req');
      $entityAttributesHandler->execute();
      if (! $entityAttributesHandler->fetch(PDO::FETCH_ASSOC)) {
        $this->error .= self::TEXT_COCOV2_REQ;
        $this->error .= " at least one RequestedAttribute OR subject-id:req entity attribute extension.\n";
      }
    }
  }
}
