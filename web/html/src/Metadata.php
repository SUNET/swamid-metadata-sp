<?php
namespace metadata;
use DOMDocument;
use PDO;

class Metadata extends Common {
  use CommonTrait;

  /**
   * DisplayName of Entity
   */
  private string $entityDisplayName = '';
  /**
   * Info about logged in user
   */
  private $user = array ('id' => 0, 'email' => '', 'fullname' => '');

  const BIND_APPROVEDBY = ':ApprovedBy';
  const BIND_ENTITY_ID = ':Entity_id';
  const BIND_HASHVALUE = ':Hashvalue';
  const BIND_LASTCHANGED = ':LastChanged';
  const BIND_LASTCONFIRMED = ':LastConfirmed';
  const BIND_OTHERENTITY_ID = ':OtherEntity_Id';
  const BIND_PUBLISHIN = ':PublishIn';
  const BIND_PUBLISHEDID = ':PublishedId';
  const BIND_USER_ID = ':User_id';

  /**
   * Setup the class
   *
   * @param int $id id in database for entity
   *
   * @param string $status as a string (prod , shadow or new)
   *
   * @return void
   */
  public function __construct($id = 0, $status = '') {
    $i = func_num_args();
    if ($i == 1) {
      parent::__construct($id);
    } elseif ($i == 2) {
      parent::__construct();

      switch (strtolower($status)) {
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
        SELECT `id`, `entityID`, `isIdP`, `isSP`, `isAA`, `publishIn`, `xml`
          FROM `Entities` WHERE `entityID` = :Id AND `status` = :Status;');
      $entityHandler->bindValue(self::BIND_ID, $id);
      $entityHandler->bindValue(self::BIND_STATUS, $this->status);
      $entityHandler->execute();
      if ($entity = $entityHandler->fetch(PDO::FETCH_ASSOC)) {
        $this->entityExists = true;
        $this->xml = new DOMDocument;
        $this->xml->preserveWhiteSpace = false;
        $this->xml->formatOutput = true;
        $this->xml->loadXML($entity['xml']);
        $this->xml->encoding = 'UTF-8';
        $this->getEntityDescriptor($this->xml);
        $this->dbIdNr = $entity['id'];
        $this->entityID = $entity['entityID'];
        $this->isIdP = $entity['isIdP'];
        $this->isSP = $entity['isSP'];
        $this->isAA = $entity['isAA'];
        $this->feedValue = $entity['publishIn'];
      }
    } else {
      parent::__construct();
    }
  }

  /**
   * Import XML into database
   *
   * @param string $xml xml to import
   *
   * @return void
   */
  public function importXML($xml) {
    $this->xml = new DOMDocument;
    $this->xml->preserveWhiteSpace = false;
    $this->xml->formatOutput = true;
    $this->xml->loadXML($xml);
    $this->xml->encoding = 'UTF-8';
    $this->getEntityDescriptor($this->xml);
    $newEntityID = $this->cleanOutAttribuesInIDPSSODescriptor();
    if ($this->entityExists && $this->status == 1) {
      # Update entity in database
      $entityHandlerUpdate = $this->config->getDb()->prepare('UPDATE `Entities`
        SET `isIdP` = 0, `isSP` = 0, `isAA` = 0, `xml` = :Xml , `lastUpdated` = NOW()
        WHERE `entityID` = :Id AND `status` = :Status;');
      $entityHandlerUpdate->bindValue(self::BIND_ID, $this->entityID);
      $entityHandlerUpdate->bindValue(self::BIND_STATUS, $this->status);
      $entityHandlerUpdate->bindValue(self::BIND_XML, $this->xml->saveXML());
      $entityHandlerUpdate->execute();
    } else {
      if ($newEntityID) {
        $this->entityID = $newEntityID;
        # Add new entity into database
        $entityHandlerInsert = $this->config->getDb()->prepare("INSERT INTO `Entities`
          (`entityID`, `isIdP`, `isSP`, `isAA`, `publishIn`, `status`, `xml`, `lastUpdated`,
          `warnings`, `errors`, `errorsNB`, `validationOutput`, `registrationInstant`)
          VALUES(:Id, 0, 0, 0, 0, :Status, :Xml, NOW(), '', '', '', '', '');");
        $entityHandlerInsert->bindValue(self::BIND_ID, $this->entityID);
        $entityHandlerInsert->bindValue(self::BIND_STATUS, $this->status);
        $entityHandlerInsert->bindValue(self::BIND_XML, $this->xml->saveXML());
        $entityHandlerInsert->execute();
        $this->dbIdNr = $this->config->getDb()->lastInsertId();
        $this->entityExists = true;
      }
    }
    $this->isIdP = false;
    $this->isSP = false;
    $this->isAA = false;
  }

  /**
   * Creates a draft from existing current entity
   *
   * @return int Id of new draft
   */
  public function createDraft() {
    if ($this->entityExists && ($this->status == 1 || $this->status == 4)) {
      # Add new entity into database
      $entityHandlerInsert = $this->config->getDb()->prepare(
        "INSERT INTO `Entities` (`entityID`, `isIdP`, `isSP`, `isAA`, `publishIn`, `status`, `OrganizationInfo_id`, `xml`,
        `lastUpdated`, `warnings`, `errors`, `errorsNB`, `validationOutput`, `registrationInstant`)
        VALUES(:EntityID, 0, 0, 0, 0, 3, :Id, :Xml, NOW(), '', '', '', '', '');");
      $entityHandlerInsert->execute(array(
        self::BIND_ENTITYID => $this->entityID,
        self::BIND_XML => $this->xml->saveXML(),
        self::BIND_ID => $this->organizationInfoId));
      $oldDbNr = $this->dbIdNr;
      $this->warning = '';
      $this->error = '';
      $this->errorNB = '';
      $this->result = '';
      $this->dbIdNr = $this->config->getDb()->lastInsertId();
      $this->status = 3;
      $this->copyResponsible($oldDbNr);

      # copy over ServiceInfo
      $serviceURL = '';
      $enabled = 0;
      $this->getServiceInfo($oldDbNr, $serviceURL, $enabled);
      if ($serviceURL) {
        $this->storeServiceInfo($this->dbIdNr, $serviceURL, $enabled);
      }

      return $this->dbIdNr;
    } else {
      return false;
    }
  }

  /**
   * Removes Attribues from IDPSSODescriptor.
   *
   * @return void
   */
  private function cleanOutAttribuesInIDPSSODescriptor() {
    $removed = false;
    if (($ssoDescriptor = $this->getSSODecriptor('IDPSSO')) && $this->config->getFederation()['cleanAttribuesFromIDPSSODescriptor']) {
      $subchild = $ssoDescriptor->firstChild;
      while ($subchild) {
        if ($subchild->nodeName == self::SAML_SAMLA_ATTRIBUTE) {
          $remChild = $subchild;
          $subchild = $subchild->nextSibling;
          $ssoDescriptor->removeChild($remChild);
          $removed = true;
        } else {
          $subchild = $subchild->nextSibling;
        }
      }
    }
    if ($removed) {
      $this->error .= 'SWAMID Tech 5.1.31: The Identity Provider IDPSSODescriptor element in metadata';
      $this->error .= " MUST NOT include any Attribute elements. Have been removed.\n";
    }
    return $this->entityDescriptor->hasAttribute('entityID') ? $this->entityDescriptor->getAttribute('entityID') : false;
  }

  /**
   * Removed SAML1 support from an entity
   *
   * @return void
   */
  public function removeSaml1Support() {
    $child = $this->entityDescriptor->firstChild;
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
          $this->entityDescriptor->removeChild($remChild);
        }
      } else {
        $child = $child->nextSibling;
      }
    }
    $this->saveResults();
  }

  /**
   * Remove Obsolete Algorithms in Extentions and KeyDescriptors
   *
   * @return void
   */
  public function removeObsoleteAlgorithms() {
    $child = $this->entityDescriptor->firstChild;
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

  /**
   * Remove Obsolete Algorithms in Extentions
   *
   * @param DOMNode $data node to check/remove from
   *
   * @return void
   */
  private function removeObsoleteAlgorithmsExtensions($data) {
    $child = $data->firstChild;
    while ($child) {
      switch ($child->nodeName) {
        case self::SAML_ALG_DIGESTMETHOD :
          $algorithm = $child->getAttribute('Algorithm') ? $child->getAttribute('Algorithm') : 'Unknown';
          if (isset(self::DIGEST_METHODS[$algorithm]) && self::DIGEST_METHODS[$algorithm] == 'obsolete' ) {
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
          if (isset(self::SIGNING_METHODS[$algorithm]) && self::SIGNING_METHODS[$algorithm] == 'obsolete' ) {
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

  /**
   * Remove Obsolete Algorithms in KeyDescriptors
   *
   * @param DOMNode $data node to check/remove from
   *
   * @return void
   */
  private function removeObsoleteAlgorithmsSSODescriptor($data) {
    $child = $data->firstChild;
    $remChild = false;
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
              if (isset(self::ENCRYPTION_METHODS[$algorithm]) && self::ENCRYPTION_METHODS[$algorithm] == 'obsolete' ) {
                $remChild = $childKeyDescriptor;
              }
            }
            $childKeyDescriptor = $childKeyDescriptor->nextSibling;
            if ($remChild) {
              $this->result .= sprintf ('Removing %s[%s] in %s->%s<br>', $remChild->nodeName, $algorithm, $data->nodeName, $child->nodeName);
              $child->removeChild($remChild);
              $remChild = false;
            }
          }
          break;
        default :
      }
      $child = $child->nextSibling;
    }
  }

  /**
   * Update feed for an entity
   *
   * @param string $feeds
   *
   * @return void
   */
  public function updateFeed($feeds) {
    #2 = SWAMID
    #3 = eduGAIN
    $federation = $this->config->getFederation();
    $publishIn = 0;
    foreach (explode(' ', $feeds) as $feed ) {
      switch (strtolower($feed)) {
        case $federation['localFeed'] :
          $publishIn = 2;
          break;
        case $federation['eduGAINFeed'] :
          $publishIn = 6; // localFeed + eduGAIN
          break;
        default :
      }
    }
    $publishedHandler = $this->config->getDb()->prepare('UPDATE `Entities` SET `publishIn` = :PublishIn WHERE `id` = :Id;');
    $publishedHandler->bindValue(self::BIND_ID, $this->dbIdNr);
    $publishedHandler->bindValue(self::BIND_PUBLISHIN, $publishIn);
    $publishedHandler->execute();
    $this->feedValue = $publishIn;
  }

  /**
   * Updates which feeds by value
   *
   * @param int $publishIn feedValue to update to
   *
   * @return void
   */
  public function updateFeedByValue($publishIn) {
    $publishedHandler = $this->config->getDb()->prepare('UPDATE `Entities` SET `publishIn` = :PublishIn WHERE `id` = :Id;');
    $publishedHandler->bindValue(self::BIND_ID, $this->dbIdNr);
    $publishedHandler->bindValue(self::BIND_PUBLISHIN, $publishIn);
    $publishedHandler->execute();
    $this->feedValue = $publishIn;
  }

  /**
   * Updates which user that is responsible for an entity
   *
   * @param int $approvedBy user to set as responsible
   *
   * @return void
   */

  public function updateResponsible($approvedBy) {
    $entityUserHandler = $this->config->getDb()->prepare('INSERT INTO `EntityUser` (`entity_id`, `user_id`, `approvedBy`, `lastChanged`) VALUES(:Entity_id, :User_id, :ApprovedBy, NOW()) ON DUPLICATE KEY UPDATE `lastChanged` = NOW();');
    $entityUserHandler->bindParam(self::BIND_ENTITY_ID, $this->dbIdNr);
    $entityUserHandler->bindParam(self::BIND_USER_ID, $this->user['id']);
    $entityUserHandler->bindParam(self::BIND_APPROVEDBY, $approvedBy);
    $entityUserHandler->execute();
  }

  /**
   * Copies which user that is responsible for an entity from another entity
   *
   * @param int $otherEntity_id id of other Entity to copy responsible users from
   *
   * @return void
   */
  public function copyResponsible($otherEntity_id) {
    $entityUserHandler = $this->config->getDb()->prepare(
      'INSERT INTO `EntityUser` (`entity_id`, `user_id`, `approvedBy`, `lastChanged`)
      VALUES(:Entity_id, :User_id, :ApprovedBy, :LastChanged)
      ON DUPLICATE KEY UPDATE `lastChanged` = :LastChanged;');
    $otherEntityUserHandler = $this->config->getDb()->prepare(
      'SELECT `user_id`, `approvedBy`, `lastChanged`
      FROM `EntityUser`
      WHERE `entity_id` = :OtherEntity_Id;');

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

  /**
   * Removes an entity from database
   *
   * Removed the id of current Entity
   *
   * @return void
   */
  public function removeEntity() {
    $this->removeEntityReal($this->dbIdNr);
  }

  /**
   * Removes an entity from database
   *
   * @param int $dbIdNr Id of Entity to remove
   *
   * @return void
   */
  private function removeEntityReal($dbIdNr) {
    $entityHandler = $this->config->getDb()->prepare('SELECT `publishedId` FROM `Entities` WHERE `id` = :Id;');
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

  /**
   * Check if an entity from pendingQueue exists with same XML in published
   *
   * @return bool
   */
  public function checkPendingIfPublished() {
    $pendingHandler = $this->config->getDb()->prepare('SELECT `entityID`, `xml`, `lastUpdated`
      FROM `Entities` WHERE `status` = 2 AND `id` = :Id;');
    $pendingHandler->bindParam(self::BIND_ID, $this->dbIdNr);
    $pendingHandler->execute();

    $publishedHandler = $this->config->getDb()->prepare('SELECT `xml`, `lastUpdated`
      FROM `Entities` WHERE `status` = 1 AND `entityID` = :EntityID;');
    $publishedHandler->bindParam(self::BIND_ENTITYID, $entityID);

    $normalize = new \metadata\NormalizeXML();

    if ($pendingEntity = $pendingHandler->fetch(PDO::FETCH_ASSOC)) {
      $entityID = $pendingEntity['entityID'];

      $normalize->fromString($pendingEntity['xml']);
      if ($normalize->getStatus() && $normalize->getEntityID() == $entityID) {
        $pendingXML = $normalize->getXML();
        $publishedHandler->execute();
        if ($publishedEntity = $publishedHandler->fetch(PDO::FETCH_ASSOC)) {
          if ($pendingEntity['lastUpdated'] < $publishedEntity['lastUpdated'] &&
            $pendingXML == $publishedEntity['xml']) {
            return true;
          }
        }
      }
    }
    return false;
  }

  /**
   * Moves current entity from Published to SoftDelete state
   *
   * @return void
   */
  public function move2SoftDelete() {
    $entityHandler = $this->config->getDb()->prepare('UPDATE `Entities`
      SET `status` = 4, `lastUpdated` = NOW() WHERE `status` = 1 AND `id` = :Id;');
    $entityHandler->bindParam(self::BIND_ID, $this->dbIdNr);
    $entityHandler->execute();
  }

  /**
   * Moves current entity from pendingQueue to publishedPending state
   *
   * @return void
   */

  public function movePublishedPending() {
    # Check if entity id exist as status pending
    if ($this->status == 2) {
      $publishedEntityHandler = $this->config->getDb()->prepare('SELECT `id`
        FROM `Entities` WHERE `status` = 1 AND `entityID` = :Id;');
      # Get id of published version
      $publishedEntityHandler->bindParam(self::BIND_ID, $this->entityID);
      $publishedEntityHandler->execute();
      if ($publishedEntity = $publishedEntityHandler->fetch(PDO::FETCH_ASSOC)) {
        $entityHandler = $this->config->getDb()->prepare('SELECT `lastValidated` FROM `Entities` WHERE `id` = :Id;');
        $entityUserHandler = $this->config->getDb()->prepare('SELECT `user_id`, `approvedBy`, `lastChanged`
          FROM `EntityUser` WHERE `entity_id` = :Entity_id ORDER BY `lastChanged`;');
        $addEntityUserHandler = $this->config->getDb()->prepare('INSERT INTO `EntityUser`
          (`entity_id`, `user_id`, `approvedBy`, `lastChanged`)
          VALUES(:Entity_id, :User_id, :ApprovedBy, :LastChanged)
          ON DUPLICATE KEY
          UPDATE `lastChanged` =
            IF(lastChanged < VALUES(lastChanged), VALUES(lastChanged), lastChanged);');
        $updateEntityConfirmationHandler = $this->config->getDb()->prepare('INSERT INTO `EntityConfirmation`
          (`entity_id`, `user_id`, `lastConfirmed`)
          VALUES (:Entity_id, :User_id, :LastConfirmed)
          ON DUPLICATE KEY UPDATE `user_id` = :User_id, `lastConfirmed` = :LastConfirmed;');
        $updateEntitiesHandler = $this->config->getDb()->prepare(
          'UPDATE `Entities` SET `OrganizationInfo_id` = :OrgId WHERE `id` = :Id;');

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
        # copy over organizationInfoId from pending
        $updateEntitiesHandler->execute(array(self::BIND_ID => $publishedEntity['id'], ':OrgId' => $this->organizationInfoId ));

        # copy over ServiceInfo
        $serviceURL = '';
        $enabled = 0;
        $this->getServiceInfo($this->dbIdNr, $serviceURL, $enabled);
        if ($serviceURL) {
          $this->storeServiceInfo($publishedEntity['id'], $serviceURL, $enabled);
        } else {
          $this->removeServiceInfo($publishedEntity['id']);
        }
      }
      # Move entity to status PendingPublished
      $entityUpdateHandler = $this->config->getDb()->prepare('UPDATE `Entities`
        SET `status` = 5, `lastUpdated` = NOW() WHERE `status` = 2 AND `id` = :Id;');
      $entityUpdateHandler->bindParam(self::BIND_ID, $this->dbIdNr);
      $entityUpdateHandler->execute();
    }
  }

  /**
   * Return Warnings
   *
   * @return string
   */
  public function getWarning() {
    return $this->warning;
  }

  /**
   * Return Error
   *
   * @return string
   */
  public function getError() {
    return $this->error . $this->errorNB;
  }

  /**
   * Return EntityID of current Entity
   *
   * @return string
   */
  public function entityID() {
    return $this->entityID;
  }

  /**
   * Return if this entity is an IdP
   *
   * @return bool
   */
  public function isIdP() {
    return $this->isIdP;
  }

  /**
   * Return if this entity is an SP
   *
   * @return bool
   */
  public function isSP() {
    return $this->isSP;
  }

  /**
   * Return if this entity is an AA
   *
   * @return bool
   */
  public function isAA() {
    return $this->isAA;
  }

  /**
   * Moves a Draft into Pending state
   *
   * @param int $publishedEntity_id Id of published Entity
   *
   * @return void
   */
  public function moveDraftToPending($publishedEntity_id) {
    $this->addRegistrationInfo();
    $entityHandler = $this->config->getDb()->prepare('UPDATE `Entities`
      SET `status` = 2, `publishedId` = :PublishedId, `lastUpdated` = NOW(), `xml` = :Xml
      WHERE `status` = 3 AND `id` = :Id;');
    $entityHandler->bindParam(self::BIND_ID, $this->dbIdNr);
    $entityHandler->bindParam(self::BIND_PUBLISHEDID, $publishedEntity_id);
    $entityHandler->bindValue(self::BIND_XML, $this->xml->saveXML());

    $entityHandler->execute();
  }

  /**
   * Add RegistrationInfo to XML of current Entity
   *
   * @return void
   */
  private function addRegistrationInfo() {
    $federation = $this->config->getFederation();

    $extensions = $this->getExtensions();
    # Find mdattr:EntityAttributes in XML
    $child = $extensions->firstChild;
    $registrationInfo = false;
    while ($child && ! $registrationInfo) {
      if ($child->nodeName == self::SAML_MDRPI_REGISTRATIONINFO) {
        $registrationInfo = $child;
      }
      $child = $child->nextSibling;
    }
    if (! $registrationInfo) {
      # Add if missing
      $ts=date("Y-m-d\TH:i:s\Z");
      $this->entityDescriptor->setAttributeNS('http://www.w3.org/2000/xmlns/', # NOSONAR Should be http://
        'xmlns:mdrpi', 'urn:oasis:names:tc:SAML:metadata:rpi');
      $registrationInfo = $this->xml->createElement(self::SAML_MDRPI_REGISTRATIONINFO);
      $registrationInfo->setAttribute('registrationAuthority', $federation['metadata_registration_authority']);
      $registrationInfo->setAttribute('registrationInstant', $ts);
      $extensions->appendChild($registrationInfo);
    }

    # Find samla:Attribute in XML
    $child = $registrationInfo->firstChild;
    $registrationPolicy = false;
    while ($child && ! $registrationPolicy) {
      if ($child->nodeName == 'mdrpi:RegistrationPolicy' && $child->getAttribute('xml:lang') == 'en') {
        $registrationPolicy = $child;
      }
      $child = $child->nextSibling;
    }
    if (!$registrationPolicy) {
      $registrationPolicy = $this->xml->createElement('mdrpi:RegistrationPolicy', $federation['metadata_registration_policy']);
      $registrationPolicy->setAttribute('xml:lang', 'en');
      $registrationInfo->appendChild($registrationPolicy);
    }
  }

  /**
   * Return status # for this entity
   *
   * @return int
   */
  public function status() {
    return $this->status;
  }

  /**
   * Return if this entity exists in the database
   *
   * @return bool
   */
  public function entityExists() {
    return $this->entityExists;
  }

  /**
   * Return ID for this entity in the database
   *
   * @return int
   */
  public function id() {
    return $this->dbIdNr;
  }

  /**
   * Return feed value for this entity in the database
   *
   * @return int
   */
  public function feedValue() {
    return $this->feedValue;
  }

  /**
   * Return entityDisplayName for this entity
   *
   * @return string
   */
  public function entityDisplayName() {
    if ($this->entityDisplayName == '' ) {
      $displayHandler = $this->config->getDb()->prepare(
        "SELECT `data` AS DisplayName
        FROM `Mdui` WHERE `entity_id` = :Entity_id AND `element` = 'DisplayName' AND `lang` = 'en';");
      $displayHandler->bindParam(self::BIND_ENTITY_ID,$this->dbIdNr);
      $displayHandler->execute();
      if ($displayInfo = $displayHandler->fetch(PDO::FETCH_ASSOC)) {
        $this->entityDisplayName = $displayInfo['DisplayName'];
      } else {
        $this->entityDisplayName = 'Display name missing';
      }
    }
    return $this->entityDisplayName;
  }

  /**
   * Return emailadresses of Technical and Administrative Contacts
   *
   * @return array
   */
  public function getTechnicalAndAdministrativeContacts() {
    $addresses = array();

    # If entity in Published will only match one.
    # If entity in draft, will match both draft and published and get addresses from both.
    $contactHandler = $this->config->getDb()->prepare("SELECT DISTINCT emailAddress
      FROM `Entities`, `ContactPerson`
      WHERE `Entities`.`id` = `entity_id`
        AND ((`entityID` = :EntityID AND `status` = 1) OR (`Entities`.`id` = :Entity_id AND `status` = 3))
        AND (`contactType`='technical' OR `contactType`='administrative')
        AND `emailAddress` <> '';");
    $contactHandler->bindParam(self::BIND_ENTITYID,$this->entityID);
    $contactHandler->bindParam(self::BIND_ENTITY_ID,$this->dbIdNr);
    $contactHandler->execute();
    while ($address = $contactHandler->fetch(PDO::FETCH_ASSOC)) {
      $addresses[] = substr($address['emailAddress'],7);
    }
    return $addresses;
  }

  /**
   * Return XML for current Entity
   *
   * return string
   */
  public function xml() {
    return $this->xml->saveXML();
  }

  /**
   * Confirmes status for the current Entity
   *
   * @return void
   */
  public function confirmEntity($userId) {
    $entityConfirmHandler = $this->config->getDb()->prepare('INSERT INTO `EntityConfirmation`
      (`entity_id`, `user_id`, `lastConfirmed`)
      VALUES (:Id, :User_id, NOW())
      ON DUPLICATE KEY UPDATE  `user_id` = :User_id, `lastConfirmed` = NOW();');
    $entityConfirmHandler->bindParam(self::BIND_ID, $this->dbIdNr);
    $entityConfirmHandler->bindParam(self::BIND_USER_ID, $userId);
    $entityConfirmHandler->execute();
  }

  /**
   * Return info about current user from database
   *
   * @param string $userID Id of user
   *
   * @param string $email Email of user
   *
   * @param string $fullName Fullname of user
   *
   * @param bool $add If we should add user if missing
   *
   * @return array
   */
  public function getUser($userID, $email = '', $fullName = '', $add = false) {
    if ($this->user['id'] == 0) {
      $userHandler = $this->config->getDb()->prepare('SELECT `id`, `email`, `fullName` FROM `Users` WHERE `userID` = :Id;');
      $userHandler->bindValue(self::BIND_ID, strtolower($userID));
      $userHandler->execute();
      if ($this->user = $userHandler->fetch(PDO::FETCH_ASSOC)) {
        $lastSeenUserHandler = $this->config->getDb()->prepare('UPDATE `Users`
          SET `lastSeen` = NOW() WHERE `userID` = :Id;');
        $lastSeenUserHandler->bindValue(self::BIND_ID, strtolower($userID));
        $lastSeenUserHandler->execute();
        if ($add && ($email <> $this->user['email'] || $fullName <>  $this->user['fullName'])) {
          $userHandler = $this->config->getDb()->prepare('UPDATE `Users`
            SET `email` = :Email, `fullName` = :FullName WHERE `userID` = :Id;');
          $userHandler->bindValue(self::BIND_ID, strtolower($userID));
          $userHandler->bindParam(self::BIND_EMAIL, $email);
          $userHandler->bindParam(self::BIND_FULLNAME, $fullName);
          $userHandler->execute();
        }
      } elseif ($add) {
        $addNewUserHandler = $this->config->getDb()->prepare('INSERT INTO `Users`
          (`userID`, `email`, `fullName`, `lastSeen`) VALUES(:Id, :Email, :FullName, NOW());');
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

  /**
   * Return id of current user in database
   *
   * @param string $userID Id of user
   *
   * @param string $email Email of user
   *
   * @param string $fullName Fullname of user
   *
   * @param bool $add If we should add user if missing
   *
   * @return int
   */
  public function getUserId($userID, $email = '', $fullName = '', $add = false) {
    if ($this->user['id'] == 0) {
      $this->getUser($userID, $email, $fullName, $add);
    }
    return $this->user['id'];
  }

  /**
   * Update info for user in database
   *
   * @param string $userID Id of user
   *
   * @param string $email Email of user
   *
   * @param string $fullName Fullname of user
   *
   * @return void
   */
  public function updateUser($userID, $email, $fullName) {
    $userHandler = $this->config->getDb()->prepare('UPDATE `Users`
      SET `email` = :Email, `fullName` = :FullName WHERE `userID` = :Id;');
    $userHandler->bindValue(self::BIND_ID, strtolower($userID));
    $userHandler->bindValue(self::BIND_EMAIL, $email);
    $userHandler->bindValue(self::BIND_FULLNAME, $fullName);
    $userHandler->execute();
  }

  /**
   * Check if userID is responsible for this entityID
   *
   * @return bool
   */
  public function isResponsible() {
    if ($this->user['id'] > 0) {
      $userHandler = $this->config->getDb()->prepare('SELECT *
        FROM `EntityUser` WHERE `user_id` = :User_id AND `entity_id`= :Entity_id' );
      $userHandler->bindParam(self::BIND_USER_ID, $this->user['id']);
      $userHandler->bindParam(self::BIND_ENTITY_ID, $this->dbIdNr);
      $userHandler->execute();
      return $userHandler->fetch(PDO::FETCH_ASSOC);
    } else {
      return false;
    }
  }

  /**
   * Get list of responsible users for currentr Entity
   *
   * @return array
   */
  public function getResponsibles() {
    $usersHandler = $this->config->getDb()->prepare('SELECT `id`, `userID`, `email`, `fullName`
      FROM `EntityUser`, `Users` WHERE `entity_id` = :Entity_id AND id = user_id ORDER BY `userID`;');
    $usersHandler->bindParam(self::BIND_ENTITY_ID, $this->dbIdNr);
    $usersHandler->execute();
    return $usersHandler->fetchAll(PDO::FETCH_ASSOC);
  }

  /**
   * Creates an access request in the database for current Entity
   *
   * @param int $userId Id of user requesting access
   *
   * @return string code for access
   */
  public function createAccessRequest($userId) {
    $hash = hash_hmac('md5',$this->entityID(),time());
    $code = base64_encode(sprintf ('%d:%d:%s', $this->dbIdNr, $userId, $hash));
    $addNewRequestHandler = $this->config->getDb()->prepare('INSERT INTO `AccessRequests`
      (`entity_id`, `user_id`, `hash`, `requestDate`)
      VALUES (:Entity_id, :User_id, :Hashvalue, NOW())
      ON DUPLICATE KEY UPDATE `hash` = :Hashvalue, `requestDate` = NOW();');
    $addNewRequestHandler->bindParam(self::BIND_ENTITY_ID, $this->dbIdNr);
    $addNewRequestHandler->bindParam(self::BIND_USER_ID, $userId);
    $addNewRequestHandler->bindParam(self::BIND_HASHVALUE, $hash);
    $addNewRequestHandler->execute();
    return $code;
  }

  /**
   * Validate code from Accessrequest
   *
   * Resturns an array with the result
   *
   * @param int $userId Id of user to give access
   *
   * @param string $hash Hash of Code to compare
   *
   * @param string $approvedBy Person approving this request
   *
   * @return array
   */
  public function validateCode($userId, $hash, $approvedBy) {
    if ($userId > 0) {
      $userHandler = $this->config->getDb()->prepare('SELECT *
        FROM `EntityUser` WHERE `user_id` = :User_id AND `entity_id`= :EntityID' );
      $userHandler->bindParam(self::BIND_USER_ID, $userId);
      $userHandler->bindParam(self::BIND_ENTITYID, $this->dbIdNr);
      $userHandler->execute();
      if ($userHandler->fetch(PDO::FETCH_ASSOC)) {
        $result = array('returnCode' => 1, 'info' => 'User already had access');
      } else {
        $requestHandler = $this->config->getDb()->prepare('SELECT `requestDate`, NOW() - INTERVAL 1 DAY AS `limit`,
          `email`, `fullName`, `entityID`
          FROM `AccessRequests`, `Users`, `Entities`
          WHERE `Users`.`id` = `user_id`
            AND `Entities`.`id` = `entity_id`
            AND `entity_id` =  :Entity_id
            AND `user_id` = :User_id
            AND `hash` = :Hashvalue;');
        $requestRemoveHandler = $this->config->getDb()->prepare('DELETE FROM `AccessRequests`
          WHERE `entity_id` =  :Entity_id AND `user_id` = :User_id;');
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

  /**
   * Add access to current Entity
   *
   * @param int $userId Id of user to give access
   *
   * @param string $approvedBy Person approving this request
   *
   * @return void
   */
  public function addAccess2Entity($userId, $approvedBy) {
    $entityUserHandler = $this->config->getDb()->prepare('INSERT INTO `EntityUser`
      (`entity_id`, `user_id`, `approvedBy`, `lastChanged`)
      VALUES(:Entity_id, :User_id, :ApprovedBy, NOW())
      ON DUPLICATE KEY UPDATE `lastChanged` = NOW();');
    $entityUserHandler->bindParam(self::BIND_ENTITY_ID, $this->dbIdNr);
    $entityUserHandler->bindParam(self::BIND_USER_ID, $userId);
    $entityUserHandler->bindParam(self::BIND_APPROVEDBY, $approvedBy);
    $entityUserHandler->execute();
  }

  /**
   * Remove access from current Entity for a user
   *
   * @param @userId Id of user to remove access for
   *
   * @return void
   */
  public function removeAccessFromEntity($userId) {
    $entityUserHandler = $this->config->getDb()->prepare('DELETE FROM `EntityUser`
      WHERE `entity_id` = :Entity_id AND `user_id` = :User_id;');
    $entityUserHandler->bindParam(self::BIND_ENTITY_ID, $this->dbIdNr);
    $entityUserHandler->bindParam(self::BIND_USER_ID, $userId);
    $entityUserHandler->execute();
  }

  /**
   * Save statistics into database
   *
   * @return void
   */
  public function saveEntitiesStatistics($date = '') {
    if ($date == '') {
      $date = gmdate('Y-m-d');
    }
    $nrOfEntities = 0;
    $nrOfSPs = 0;
    $nrOfIdPs = 0;

    $entitys = $this->config->getDb()->prepare("SELECT `id`, `entityID`, `isIdP`, `isSP`, `publishIn`
      FROM `Entities` WHERE `status` = 1 AND `publishIn` > 1;");
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
    $statsUpdate = $this->config->getDb()->prepare("INSERT INTO `EntitiesStatistics`
      (`date`, `NrOfEntites`, `NrOfSPs`, `NrOfIdPs`)
      VALUES ('$date', $nrOfEntities, $nrOfSPs, $nrOfIdPs);");
    $statsUpdate->execute();
  }
}
