<?php
namespace metadata;

use PDO;
use PDOException;

/**
 * Class to Parse XML for an Entity into Database
 */
class ParseXML extends Common {
  use CommonTrait;

  # Setup
  /* when parsing role descriptors, parseProtocolSupportEnumeration
   * will add entries in format:
   *    'RoleDesciptorName' => array(
   *       'saml2' => true,
   *       'saml1' => false,
   *       'shibboleth10' => false,
   *    ),
   */
  protected $samlProtocolSupportFound = array();
  protected $discoveryResponseFound = false;
  protected $assertionConsumerServiceHTTPRedirectFound = false;

  const BIND_COMPANY = ':Company';
  const BIND_DEFAULT = ':Default';
  const BIND_EXTENSIONS = ':Extensions';
  const BIND_GIVENNAME = ':GivenName';
  const BIND_SUBCONTACTTYPE = ':SubcontactType';
  const BIND_SURNAME = ':SurName';
  const BIND_TELEPHONENUMBER = ':TelephoneNumber';

  const TEXT_HTTPS = 'https://';

  /**
   * Parse XML
   *
   * Parse XML and splitup into database
   *
   * @return void
   */
  public function parseXML() {
    if (! $this->entityExists) {
      $this->result = "$this->entityID doesn't exist!!";
      return 1;
    }

    # Remove old ContactPersons / Organization from previous runs
    $this->config->getDb()->prepare('DELETE FROM `EntityAttributes` WHERE `entity_id` = :Id')->execute(
      array(self::BIND_ID => $this->dbIdNr));
    $this->config->getDb()->prepare('DELETE FROM `DiscoveryResponse` WHERE `entity_id` = :Id')->execute(
      array(self::BIND_ID => $this->dbIdNr));
    $this->config->getDb()->prepare('DELETE FROM `Mdui` WHERE `entity_id` = :Id')->execute(
      array(self::BIND_ID => $this->dbIdNr));
    $this->config->getDb()->prepare('DELETE FROM `KeyInfo` WHERE `entity_id` = :Id')->execute(
      array(self::BIND_ID => $this->dbIdNr));
    $this->config->getDb()->prepare('DELETE FROM `AttributeConsumingService` WHERE `entity_id` = :Id')->execute(
      array(self::BIND_ID => $this->dbIdNr));
    $this->config->getDb()->prepare('DELETE FROM `AttributeConsumingService_Service` WHERE `entity_id` = :Id')->execute(
      array(self::BIND_ID => $this->dbIdNr));
    $this->config->getDb()->prepare('DELETE FROM `AttributeConsumingService_RequestedAttribute`
      WHERE `entity_id` = :Id')->execute(array(self::BIND_ID => $this->dbIdNr));
    $this->config->getDb()->prepare('DELETE FROM `Organization` WHERE `entity_id` = :Id')->execute(
      array(self::BIND_ID => $this->dbIdNr));
    $this->config->getDb()->prepare('DELETE FROM `ContactPerson` WHERE `entity_id` = :Id')->execute(
      array(self::BIND_ID => $this->dbIdNr));
    $this->config->getDb()->prepare('DELETE FROM `EntityURLs` WHERE `entity_id` = :Id')->execute(
      array(self::BIND_ID => $this->dbIdNr));
    $this->config->getDb()->prepare('DELETE FROM `Scopes` WHERE `entity_id` = :Id')->execute(
      array(self::BIND_ID => $this->dbIdNr));
    $this->config->getDb()->prepare('UPDATE `Entities` SET `isIdP` = 0, `isSP` = 0, `isAA` = 0 WHERE `id` = :Id')->execute(
      array(self::BIND_ID => $this->dbIdNr));
    $this->isIdP = false;
    $this->isSP = false;
    $this->isAA = false;

    # https://docs.oasis-open.org/security/saml/v2.0/saml-metadata-2.0-os.pdf 2.4.1
    $child = $this->entityDescriptor->firstChild;
    while ($child) {
      switch ($child->nodeName) {
        case self::SAML_MD_EXTENSIONS :
          $this->parseExtensions($child);
          break;
        case self::SAML_MD_IDPSSODESCRIPTOR :
          $this->config->getDb()->prepare('UPDATE `Entities` SET `isIdP` = 1 WHERE `id` = :Id')->execute(
            array(self::BIND_ID => $this->dbIdNr));
          $this->isIdP = true;
          $this->parseIDPSSODescriptor($child);
          break;
        case self::SAML_MD_SPSSODESCRIPTOR :
          $this->config->getDb()->prepare('UPDATE `Entities` SET `isSP` = 1 WHERE `id` = :Id')->execute(
            array(self::BIND_ID => $this->dbIdNr));
          $this->isSP = true;
          $this->parseSPSSODescriptor($child);
          break;
        #case self::SAML_MD_AUTHNAUTHORITYDESCRIPTOR :
        case self::SAML_MD_ATTRIBUTEAUTHORITYDESCRIPTOR :
          $this->config->getDb()->prepare('UPDATE `Entities` SET `isAA` = 1 WHERE `id` = :Id')->execute(
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
          $this->result .= $child->nodeType == 8 ? '' : sprintf("Unknown element %s found in metadata.\n", $child->nodeName);
      }
      $child = $child->nextSibling;
    }
    $this->saveResults();
  }

  /**
   * Add an URL to EntityURLs
   *
   * Adds an URL to EntityURLs + to DB for URL-checking
   *
   * @param int $type Type of URL
   *
   * @param string $url  URL to add
   *
   * @return void
   */
  protected function addEntityUrl($type, $url) {
    $urlHandler = $this->config->getDb()->prepare("INSERT INTO `EntityURLs`
      (`entity_id`, `URL`, `type`) VALUES (:Id, :URL, :Type);");
    $urlHandler->bindValue(self::BIND_ID, $this->dbIdNr);
    $urlHandler->bindValue(self::BIND_URL, $url);
    $urlHandler->bindValue(self::BIND_TYPE, $type);
    $urlHandler->execute();
    $this->addURL($url, 1);
  }

  /**
   * Parse Extensions
   *
   * @param DOMNode $data XML to parse
   *
   * @return void
   */
  protected function parseExtensions($data) {
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
          $this->result .= sprintf("Unknown element Extensions->%s found in metadata.\n", $child->nodeName);
      }
      $child = $child->nextSibling;
    }
  }

  /**
   * Parse Extensions -> EntityAttributes
   *
   * @param DOMNode $data XML to parse
   *
   * @return void
   */
  protected function parseExtensionsEntityAttributes($data) {
    $child = $data->firstChild;
    while ($child) {
      if ($child->nodeName == self::SAML_SAMLA_ATTRIBUTE ) {
        $this->parseExtensionsEntityAttributesAttribute($child);
      } else {
        $this->result .= $child->nodeType == 8 ? '' :
        sprintf("Unknown element Extensions->EntityAttributes->%s found in metadata.\n", $child->nodeName);
      }
      $child = $child->nextSibling;
    }
  }

  /**
   * Parse Extensions -> EntityAttributes -> Attribute
   *
   * @param DOMNode $data XML to parse
   *
   * @return void
   */
  protected function parseExtensionsEntityAttributesAttribute($data) {
    $entityAttributesHandler = $this->config->getDb()->prepare('INSERT INTO `EntityAttributes` (`entity_id`, `type`, `attribute`)
      VALUES (:Id, :Type, :Value);');

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

  /**
   * Parse IDPSSODescriptor
   *
   * @param DOMNode $data XML to parse
   *
   * @return void
   */
  protected function parseIDPSSODescriptor($data) {
    if ($data->getAttribute('errorURL'))  {
      $this->addEntityUrl('error', $data->getAttribute('errorURL'));
    }
    $keyOrder = 0;
    $this->parseProtocolSupportEnumeration($data);
    $saml2found = $this->samlProtocolSupportFound[self::SAML_MD_IDPSSODESCRIPTOR]['saml2'];
    $saml1found = $this->samlProtocolSupportFound[self::SAML_MD_IDPSSODESCRIPTOR]['saml1'];
    $shibboleth10found = $this->samlProtocolSupportFound[self::SAML_MD_IDPSSODESCRIPTOR]['shibboleth10'];

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
          break;
        default :
        $this->result .= $child->nodeType == 8 ? '' :
          sprintf("Unknown element IDPSSODescriptor->%s found in metadata.\n", $child->nodeName);
      }
      $child = $child->nextSibling;
    }
    if ($shibboleth10found && ! $saml1found) {
      # https://shibboleth.atlassian.net/wiki/spaces/SP3/pages/2065334348/SSO#SAML1
      $this->error .= "IDPSSODescriptor claims support for urn:mace:shibboleth:1.0. This depends on SAML1, no support for SAML1 claimed.\n";
    }
  }

  /**
   * Parse IDPSSODescriptor -> Extensions
   *
   * @param DOMNode $data XML to parse
   *
   * @return void
   */
  protected function parseIDPSSODescriptorExtensions($data) {
    $scopesHandler = $this->config->getDb()->prepare('INSERT INTO `Scopes` (`entity_id`, `scope`, `regexp`)
      VALUES (:Id, :Scope, :Regexp);');
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
          $this->result .= sprintf("Unknown element IDPSSODescriptor->Extensions->%s found in metadata.\n", $child->nodeName);
      }
      $child = $child->nextSibling;
    }
  }

  /**
   * Parse IDPSSODescriptor -> Extensions -> DiscoHints
   *
   * @param DOMNode $data XML to parse
   *
   * @return void
   */
  protected function parseIDPSSODescriptorExtensionsDiscoHints($data) {
    $ssoUIIHandler = $this->config->getDb()->prepare("INSERT INTO `Mdui`
      (`entity_id`, `type`, `lang`, `height`, `width`, `element`, `data`)
      VALUES (:Id, 'IDPDisco', '', 0, 0, :Element, :Value);");

    $ssoUIIHandler->bindValue(self::BIND_ID, $this->dbIdNr);
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

  /**
   * Parse SPSSODescriptor
   *
   * @param DOMNode $data XML to parse
   *
   * @return void
   */
  protected function parseSPSSODescriptor($data) {
    $this->assertionConsumerServiceHTTPRedirectFound = false;
    $keyOrder = 0;
    $this->parseProtocolSupportEnumeration($data);
    $saml2found = $this->samlProtocolSupportFound[self::SAML_MD_SPSSODESCRIPTOR]['saml2'];
    $saml1found = $this->samlProtocolSupportFound[self::SAML_MD_SPSSODESCRIPTOR]['saml1'];
    $shibboleth10found = $this->samlProtocolSupportFound[self::SAML_MD_SPSSODESCRIPTOR]['shibboleth10'];
    if ($shibboleth10found) {
      $this->errorNB .= sprintf("Protocol urn:mace:shibboleth:1.0 should only be used on IdP:s protocolSupportEnumeration, found in SPSSODescriptor.\n");
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
            sprintf("Unknown element SPSSODescriptor->%s found in metadata.\n", $child->nodeName);
      }
      $child = $child->nextSibling;
    }
  }

  /**
   * Parse SPSSODescriptor -> Extensions
   *
   * @param DOMNode $data XML to parse
   *
   * @return void
   */
  protected function parseSPSSODescriptorExtensions($data) {
    $child = $data->firstChild;
    while ($child) {
      switch ($child->nodeName) {
        case self::SAML_IDPDISC_DISCOVERYRESPONSE :
          $this->parseSSODescriptorExtensionsDiscoveryResponse($child);
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
            sprintf("Unknown element SPSSODescriptor->Extensions->%s found in metadata.\n", $child->nodeName);
      }
      $child = $child->nextSibling;
    }
  }

  /**
   * Parse SPSSODescriptor -> AttributeConsumingService
   *
   * @param DOMNode $data XML to parse
   *
   * @return void
   */
  protected function parseSPSSODescriptorAttributeConsumingService($data) {
    $index = $data->getAttribute('index');
    if ($index == '') {
      $this->error .= "Index is Required in SPSSODescriptor->AttributeConsumingService.\n";
      $index = 0;
    }

    $isDefault = ($data->getAttribute('isDefault') &&
      ($data->getAttribute('isDefault') == 'true' || $data->getAttribute('isDefault') == '1')) ? 1 : 0;

    $serviceHandler = $this->config->getDb()->prepare('INSERT INTO `AttributeConsumingService`
      (`entity_id`, `Service_index`, `isDefault`) VALUES (:Id, :Index, :Default);');
    $serviceElementHandler = $this->config->getDb()->prepare('INSERT INTO `AttributeConsumingService_Service`
      (`entity_id`, `Service_index`, `lang`, `element`, `data`) VALUES (:Id, :Index, :Lang, :Element, :Data);');
    $requestedAttributeHandler = $this->config->getDb()->prepare('INSERT INTO `AttributeConsumingService_RequestedAttribute`
      (`entity_id`, `Service_index`, `FriendlyName`, `Name`, `NameFormat`, `isRequired`)
      VALUES (:Id, :Index, :FriendlyName, :Name, :NameFormat, :IsRequired);');

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
              isset(self::FRIENDLY_NAMES[$name]) &&
              self::FRIENDLY_NAMES[$name]['desc'] != $friendlyName) {
                $this->warning .= sprintf(
                  "FriendlyName for %s %s %d is %s (recomended is %s).\n",
                  $name, 'in RequestedAttribute for index', $index, $friendlyName, self::FRIENDLY_NAMES[$name]['desc']);
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
            sprintf("Unknown element SPSSODescriptor->AttributeConsumingService->%s found in metadata.\n", $child->nodeName);
      }
      $child = $child->nextSibling;
    }
    if ( ! $serviceNameFound ) {
      $this->error .= sprintf(
          "ServiceName is Required in SPSSODescriptor->AttributeConsumingService[index=%d].\n",
          $index);
    }
    if ( ! $requestedAttributeFound ) {
      $this->error .= sprintf(
      "RequestedAttribute is Required in SPSSODescriptor->AttributeConsumingService[index=%d].\n",
      $index);
    }
  }

  /**
   * Parse SPSSODescriptor -> AttributeAuthorityDescriptor
   *
   * @param DOMNode $data XML to parse
   *
   * @return void
   */
  protected function parseAttributeAuthorityDescriptor($data) {
    $keyOrder = 0;
    $this->parseProtocolSupportEnumeration($data);
    $saml2found = $this->samlProtocolSupportFound[self::SAML_MD_ATTRIBUTEAUTHORITYDESCRIPTOR]['saml2'];
    $saml1found = $this->samlProtocolSupportFound[self::SAML_MD_ATTRIBUTEAUTHORITYDESCRIPTOR]['saml1'];
    # https://docs.oasis-open.org/security/saml/v2.0/saml-metadata-2.0-os.pdf 2.4.1 + 2.4.2 + 2.4.7
    $child = $data->firstChild;
    while ($child) {
      switch ($child->nodeName) {
        # 2.4.1
        #case 'Signature' :
        case self::SAML_MD_EXTENSIONS :
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
          break;
        #case self::SAML_MD_ATTRIBUTEPROFILE :
        #case self::SAML_MD_ATTRIBUTE :

        default :
          $this->result .= $child->nodeType == 8 ? '' :
            sprintf("Unknown element AttributeAuthorityDescriptor->%s found in metadata.\n", $child->nodeName);
      }
      $child = $child->nextSibling;
    }
  }

  /**
   * Parse Organization
   *
   * @param DOMNode $data XML to parse
   *
   * @return void
   */
  protected function parseOrganization($data) {
    # https://docs.oasis-open.org/security/saml/v2.0/saml-metadata-2.0-os.pdf 2.3.2.1
    $organizationHandler = $this->config->getDb()->prepare('INSERT INTO `Organization` (`entity_id`, `lang`, `element`, `data`)
      VALUES (:Id, :Lang, :Element, :Value);');

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
          $this->result .= sprintf("Unknown element Organization->%s found in metadata.\n", $child->nodeName);
      }
      $organizationHandler->bindValue(self::BIND_VALUE, trim($child->textContent));
      $organizationHandler->execute();
      $child = $child->nextSibling;
    }
  }

  /**
   * Parse ContactPerson
   *
   * @param DOMNode $data XML to parse
   *
   * @return void
   */
  protected function parseContactPerson($data) {
    # https://docs.oasis-open.org/security/saml/v2.0/saml-metadata-2.0-os.pdf 2.3.2.2
    $extensions = '';
    $company = '';
    $givenName = '';
    $surName = '';
    $emailAddress = '';
    $telephoneNumber = '';
    $contactType = $data->getAttribute('contactType');
    $subcontactType = '';

    $contactPersonHandler = $this->config->getDb()->prepare('INSERT INTO `ContactPerson`
      (`entity_id`, `contactType`, `subcontactType`, `company`, `emailAddress`,
        `extensions`, `givenName`, `surName`, `telephoneNumber`)
      VALUES (:Id, :ContactType, :SubcontactType, :Company, :Email,
        :Extensions, :GivenName, :SurName, :TelephoneNumber);');

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
          $this->result .= sprintf("Unknown element ContactPerson->%s found in metadata.\n", $child->nodeName);
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
    $contactPersonHandler->bindParam(self::BIND_EMAIL, $emailAddress);
    $contactPersonHandler->bindParam(self::BIND_EXTENSIONS, $extensions);
    $contactPersonHandler->bindParam(self::BIND_GIVENNAME, $givenName);
    $contactPersonHandler->bindParam(self::BIND_SURNAME, $surName);
    $contactPersonHandler->bindParam(self::BIND_TELEPHONENUMBER, $telephoneNumber);
    $contactPersonHandler->execute();
  }

  /**
   * Parse SSODescriptor -> Extensions -> DiscoveryResponse
   *
   * Used by SPSSODescriptor
   *
   * @param DOMNode $data XML to parse
   *
   * @return void
   */
  protected function parseSSODescriptorExtensionsDiscoveryResponse($data) {
    # https://docs.oasis-open.org/security/saml/Post2.0/sstc-saml-idp-discovery-cs-01.html
    if ($data->hasAttribute('Binding') && $data->hasAttribute('Location') && $data->hasAttribute('index')) {
      if ($data->getAttribute('Binding') != 'urn:oasis:names:tc:SAML:profiles:SSO:idp-discovery-protocol') {
        $this->error .= sprintf ("Binding in %s with index = %d is NOT urn:oasis:names:tc:SAML:profiles:SSO:idp-discovery-protocol.\n",
          $data->nodeName, $data->getAttribute('index'));
      }
      $discoveryHandler = $this->config->getDb()->prepare('INSERT INTO `DiscoveryResponse`
        (`entity_id`, `index`, `location`) VALUES (:Id, :Index, :URL);');
      $discoveryHandler->execute(array(
        self::BIND_ID => $this->dbIdNr,
        self::BIND_INDEX => $data->getAttribute('index'),
        self::BIND_URL => $data->getAttribute('Location')
      ));
    }
  }

  /**
   * Parse SSODescriptor -> Extensions -> UIInfo
   *
   * Used by IDPSSODescriptor and SPSSODescriptor
   *
   * @param DOMNode $data XML to parse
   *
   * @param string $type Type of Node
   *  - AttributeAuthority
   *  - IDPSSO
   *  - SPSSO
   *
   * @return void
   */
  protected function parseSSODescriptorExtensionsUIInfo($data, $type) {
    $ssoUIIHandler = $this->config->getDb()->prepare('INSERT INTO `Mdui`
      (`entity_id`, `type`, `lang`, `height`, `width`, `element`, `data`)
      VALUES (:Id, :Type, :Lang, :Height, :Width, :Element, :Value);');
    $urlHandler = $this->config->getDb()->prepare('SELECT `nosize`, `height`, `width`, `status` FROM `URLs` WHERE `URL` = :URL;');

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

  /**
   * Parse protocolSupportEnumeration
   *
   * @param DOMNode $data Role descriptor to parse protocolSupportEnumeration in
   *
   * @return void
   */
  protected function parseProtocolSupportEnumeration($data) {
    $name = $data->nodeName;
    $protocolSupportEnumeration = $data->getAttribute('protocolSupportEnumeration');

    $saml2found = false;
    $saml1found = false;
    $shibboleth10found = false;

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
          $this->result .= sprintf("Extra space found in protocolSupportEnumeration for $name. Please remove.\n");
          break;
        default :
          $this->result .= sprintf("Unknown protocol %s found in protocolSupportEnumeration for $name.\n", $protocol);
      }
    }

    $this->samlProtocolSupportFound[$name] = array(
      'saml2' => $saml2found,
      'saml1' => $saml1found,
      'shibboleth10' => $shibboleth10found,
    );
  }

  /**
   * Parse SSODescriptor -> KeyDescriptor
   *
   * Used by AttributeAuthorityDescriptor, IDPSSODescriptor and SPSSODescriptor
   *
   * @param DOMNode $data XML to parse
   *
   * @param string $type Type of Node
   *  - AttributeAuthority
   *  - IDPSSO
   *  - SPSSO
   *
   * @param int $order Key number in XML
   *
   * @return void
   */
  protected function parseKeyDescriptor($data, $type, $order) {
    $use = $data->getAttribute('use') ? $data->getAttribute('use') : 'both';
    #'{urn:oasis:names:tc:SAML:2.0:metadata}EncryptionMethod':
    $child = $data->firstChild;
    while ($child) {
      switch ($child->nodeName) {
        case self::SAML_DS_KEYINFO :
          $this->parseKeyDescriptorKeyInfo($child, $type, $use, $order);
          break;
        case self::SAML_MD_ENCRYPTIONMETHOD :
          $this->validateEncryptionMethod($child);
          break;
        default :
          $this->result .= $child->nodeType == 8 ? '' :
            sprintf("Unknown element %sDescriptor->KeyDescriptor->%s found in metadata.\n", $type, $child->nodeName);
      }
      $child = $child->nextSibling;
    }
  }

  /**
   * Parse SSODescriptor -> KeyDescriptor -> KeyInfo
   *
   * Used by KeyDescriptor in AttributeAuthorityDescriptor, IDPSSODescriptor and SPSSODescriptor
   *
   * @param DOMNode $data XML to parse
   *
   * @param string $type Type of Node
   *  - AttributeAuthority
   *  - IDPSSO
   *  - SPSSO
   *
   * @param string $use Type of key
   *  - sign
   *  - encr
   *  - both
   *
   * @param int $order Key number in XML
   *
   * @return void
   */
  protected function parseKeyDescriptorKeyInfo($data, $type, $use, $order) {
    $name = '';
    $child = $data->firstChild;
    while ($child) {
      switch ($child->nodeName) {
        case self::SAML_DS_KEYNAME :
          $name = trim($child->textContent);
          break;
        case self::SAML_DS_X509DATA :
          $this->parseKeyDescriptorKeyInfoX509Data($child, $type, $use, $order, $name);
          break;
        default :
          $this->result .= $child->nodeType == 8 ? '' :
            sprintf("Unknown element %sDescriptor->KeyDescriptor->KeyInfo->%s found in metadata.\n", $type, $child->nodeName);
      }
      $child = $child->nextSibling;
    }
  }

  /**
   * Parse SSODescriptor -> KeyDescriptor -> KeyInfo -> X509Data
   *
   * Used by KeyInfo in AttributeAuthorityDescriptor, IDPSSODescriptor and SPSSODescriptor
   * Extract Certs and check dates
   *
   * @param DOMNode $data XML to parse
   *
   * @param string $type Type of Node
   *  - AttributeAuthority
   *  - IDPSSO
   *  - SPSSO
   *
   * @param string $use Type of key
   *  - sign
   *  - encr
   *  - both
   *
   * @param int $order Key number in XML
   *
   * @param string $name Name of key
   *
   * @return void
   */
  protected function parseKeyDescriptorKeyInfoX509Data($data, $type, $use, $order, $name) {
    $keyInfoHandler = $this->config->getDb()->prepare('INSERT INTO `KeyInfo`
      (`entity_id`, `type`, `use`, `order`, `name`, `notValidAfter`,
        `subject`, `issuer`, `bits`, `key_type`, `serialNumber`)
      VALUES (:Id, :Type, :Use, :Order, :Name, :NotValidAfter,
        :Subject, :Issuer, :Bits, :Key_type, :SerialNumber);');

    $keyInfoHandler->bindValue(self::BIND_ID, $this->dbIdNr);
    $keyInfoHandler->bindValue(self::BIND_TYPE, $type);
    $keyInfoHandler->bindValue(self::BIND_USE, $use);
    $keyInfoHandler->bindValue(self::BIND_ORDER, $order);
    $keyInfoHandler->bindParam(self::BIND_NAME, $name);

    $child = $data->firstChild;
    while ($child) {
      switch ($child->nodeName) {
        case'ds:X509IssuerSerial' :
          # Skiped since we doesn't need this info
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
            sprintf("Unknown element %sDescriptor->KeyDescriptor->KeyInfo->X509Data->%s found in metadata.\n",
              $type, $child->nodeName);
      }
      $child = $child->nextSibling;
    }
    $keyInfoHandler->execute();
  }

  /**
   * Validate DigestMethod
   *
   * Used by Extensions in EntityDescriptor and IDPSSODescriptor
   *
   * @param DOMNode $data XML to parse
   *
   * @return void
   */
  protected function validateDigestMethod($data) {
    $algorithm = $data->getAttribute('Algorithm') ? $data->getAttribute('Algorithm') : 'Unknown';
    if (isset(self::DIGEST_METHODS[$algorithm])) {
      switch (self::DIGEST_METHODS[$algorithm]) {
        case 'good' :
          break;
        case 'discouraged' :
          $this->warning .= sprintf("DigestMethod %s is discouraged in xmldsig-core.\n", $algorithm);
          break;
        case 'obsolete' :
          $this->error .= sprintf("DigestMethod %s is obsolete in xmldsig-core.\n", $algorithm);
          break;
        default :
          $this->result .= sprintf("CommonTrait.php digestMethod[%s] have unknown status (%s).\n", $algorithm, self::DIGEST_METHODS[$algorithm]);
      }
    } else {
      $this->result .= sprintf("Missing DigestMethod[%s].\n", $algorithm);
    }
  }

  /**
   * Validate SigningMethod
   *
   * Used by Extensions in EntityDescriptor and IDPSSODescriptor
   *
   * @param DOMNode $data XML to parse
   *
   * @return void
   */
  protected function validateSigningMethod($data) {
    $algorithm = $data->getAttribute('Algorithm') ? $data->getAttribute('Algorithm') : 'Unknown';
    if (isset(self::SIGNING_METHODS[$algorithm])) {
      switch (self::SIGNING_METHODS[$algorithm]) {
        case 'good' :
          break;
        case 'discouraged' :
          $this->warning .= sprintf("SigningMethod %s is discouraged in xmldsig-core.\n", $algorithm);
          break;
        case 'obsolete' :
          $this->error .= sprintf("SigningMethod %s is obsolete in xmldsig-core.\n", $algorithm);
          break;
        default :
          $this->result .= sprintf("CommonTrait.php signingMethods[%s] have unknown status (%s).\n", $algorithm, self::SIGNING_METHODS[$algorithm]);
      }
    } else {
      $this->result .= sprintf("Missing SigningMethod[%s].\n", $algorithm);
    }
  }

  /**
   * Validate EncryptionMethod
   *
   * Used by SSODescriptor -> KeyDescriptor in AttributeAuthorityDescriptor, IDPSSODescriptor and SPSSODescriptor
   *
   * @param DOMNode $data XML to parse
   *
   * @return void
   */
  protected function validateEncryptionMethod($data) {
    $algorithm = $data->getAttribute('Algorithm') ? $data->getAttribute('Algorithm') : 'Unknown';
    if (isset(self::ENCRYPTION_METHODS[$algorithm])) {
      switch (self::ENCRYPTION_METHODS[$algorithm]) {
        case 'good' :
          break;
        case 'discouraged' :
          $this->warning .= sprintf("EncryptionMethod %s is discouraged in xmlenc-core.\n", $algorithm);
          break;
        case 'obsolete' :
          $this->error .= sprintf("EncryptionMethod %s is obsolete in xmlenc-core.\n", $algorithm);
          break;
        default :
          $this->result .= sprintf("CommonTrait.php encryptionMethods[%s] have unknown status (%s).\n", $algorithm, self::ENCRYPTION_METHODS[$algorithm]);
      }
    } else {
      $this->result .= sprintf("Missing EncryptionMethod[%s].\n", $algorithm);
    }
  }

  /**
   * Checks SAMLEndpoint
   *
   * Verifies that it's a binding used in protocols of the SSODescriptor
   *
   * @param DOMNode $data XML to parse
   *
   * @param string $type Type of Node
   *  - AttributeAuthority
   *  - IDPSSO
   *  - SPSSO
   *
   * @param bool $saml2 If SSODescriptor is of type SAML2
   *
   * @param bool $saml1 If SSODescriptor is of type SAML1
   *
   * @return void
   */
  protected function checkSAMLEndpoint($data,$type, $saml2, $saml1) {
    $name = $data->nodeName;
    $binding = $data->getAttribute('Binding');
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

  /**
   * Checks AssertionConsumerService
   *
   * Checks if AssertionConsumerService is of thpe Verifies that it's a binding is of type urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect
   * If so flag this via assertionConsumerServiceHTTPRedirectFound
   *
   * @param DOMNode $data XML to parse
   *
   * @return void
   */
  protected function checkAssertionConsumerService($data) {
    $binding = $data->getAttribute('Binding');
    if ($binding == self::SAML_BINDING_HTTP_REDIRECT) {
      $this->assertionConsumerServiceHTTPRedirectFound = true;
    }
  }

  /**
   * Checks NameIDFormat
   *
   * Checks if NameIDFormat is one of the allowed accordording to https://docs.oasis-open.org/security/saml/v2.0/saml-core-2.0-os.pdf
   *
   * @param string $nameIDFormat NameIDFormat
   *
   * @return void
   */
  protected function checkNameIDFormat($nameIDFormat) {
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
}
