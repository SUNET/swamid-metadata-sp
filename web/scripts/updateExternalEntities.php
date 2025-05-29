<?php
//Load composer's autoloader
require_once __DIR__ . '/../html/vendor/autoload.php';

$config = new \metadata\Configuration();

const ALG_DIGEST_METHOD = '{urn:oasis:names:tc:SAML:metadata:algsupport}DigestMethod';
const ALG_SIGNING_METHOD = '{urn:oasis:names:tc:SAML:metadata:algsupport}SigningMethod';
const DS_SIGNATURE = '{http://www.w3.org/2000/09/xmldsig#}Signature';
const EDUIDCZ_REPUBLISH_REQUEST = '{http://eduid.cz/schema/metadata/1.0}RepublishRequest';
const MD_AA_DESCRIPTOR = '{urn:oasis:names:tc:SAML:2.0:metadata}AttributeAuthorityDescriptor';
const MD_ATTRIBUTE_CONSUMING_SERVICE = '{urn:oasis:names:tc:SAML:2.0:metadata}AttributeConsumingService';
const MD_CONTACT_PERSON = '{urn:oasis:names:tc:SAML:2.0:metadata}ContactPerson';
const MD_EMAIL_ADDRESS = '{urn:oasis:names:tc:SAML:2.0:metadata}EmailAddress';
const MD_ENTITIES_DESCRIPTOR = '{urn:oasis:names:tc:SAML:2.0:metadata}EntitiesDescriptor';
const MD_ENTITY_DESCRIPTOR = '{urn:oasis:names:tc:SAML:2.0:metadata}EntityDescriptor';
const MD_EXTENSIONS = '{urn:oasis:names:tc:SAML:2.0:metadata}Extensions';
const MD_IDPSSO_DESCRIPTOR = '{urn:oasis:names:tc:SAML:2.0:metadata}IDPSSODescriptor';
const MD_ORGANIZATION_DISPLAYNAME = '{urn:oasis:names:tc:SAML:2.0:metadata}OrganizationDisplayName';
const MD_ORGANIZATION_URL = '{urn:oasis:names:tc:SAML:2.0:metadata}OrganizationURL';
const MD_ORGANIZATION = '{urn:oasis:names:tc:SAML:2.0:metadata}Organization';
const MD_SERVICE_NAME = '{urn:oasis:names:tc:SAML:2.0:metadata}ServiceName';
const MD_SPSSO_DESCRIPTOR = '{urn:oasis:names:tc:SAML:2.0:metadata}SPSSODescriptor';
const MDATTR_ENTITY_ATTRIBUTES = '{urn:oasis:names:tc:SAML:metadata:attribute}EntityAttributes';
const MDRPI_REGISTRATION_INFO = '{urn:oasis:names:tc:SAML:metadata:rpi}RegistrationInfo';
const MDUI_DISPLAYNAME = '{urn:oasis:names:tc:SAML:metadata:ui}DisplayName';
const MDUI_UIINFO = '{urn:oasis:names:tc:SAML:metadata:ui}UIInfo';
const REMD_CONTACT_TYPE_SECURITY = 'http://refeds.org/metadata/contactType/security';
const SAML_ATTRIBUTE = '{urn:oasis:names:tc:SAML:2.0:assertion}Attribute';
const SAML_ATTRIBUTEVALUE = '{urn:oasis:names:tc:SAML:2.0:assertion}AttributeValue';
const SHIBMD_SCOPE = '{urn:mace:shibboleth:metadata:1.0}Scope';
const SWITCHAAI_EXTENSIONS = '{https://rr.aai.switch.ch/}SWITCHaaiExtensions';
const TAAT_TAAT = '{http://www.eenet.ee/EENet/urn}taat';
const XML_LANG = 'xml:lang';

$config->getDb()->query('UPDATE ExternalEntities SET updated = 0');

$xml = new DOMDocument;
$xml->preserveWhiteSpace = false;
$xml->formatOutput = true;
$xml->load($config->getFederation()['metadata_main_path']);
$xml->encoding = 'UTF-8';

checkEntities($xml);
unset($xml);
$config->getDb()->query('DELETE FROM ExternalEntities WHERE updated = 0');

function nodeQName($node) {
  return sprintf("{%s}%s", $node->namespaceURI, $node->localName);
}

function contactType($contact) {
  $contactType = $contact->getAttribute('contactType');
  if ( $contactType == "other" &&
       $contact->getAttribute('remd:contactType') == REMD_CONTACT_TYPE_SECURITY) {
    $contactType  = "security";
  };
  return $contactType;
}

function checkEntities(&$xml) {
  global $config;

  $entityID = '';
  $isIdP = 0;
  $isSP = 0;
  $isAA = 0;
  $serviceName = '';
  $organization = '';
  $contacts = '';
  $scopes = '';
  $eC = '';
  $eCS = '';
  $assuranceC = '';
  $registrationAuthority = 'Not Set';

  $updateHandler = $config->getDb()->prepare('UPDATE ExternalEntities
    SET
      `updated` = 1,
      `isIdP` = :IsIdP,
      `isSP` = :IsSP,
      `isAA` = :IsAA,
      `displayName` = :DisplayName,
      `serviceName` = :ServiceName,
      `organization` = :Organization,
      `contacts` = :Contacts,
      `scopes` = :Scopes,
      `ecs` = :Ecs,
      `ec` = :Ec,
      `assurancec` = :Assurancec,
      `ra` = :RegistrationAuthority
    WHERE `entityID` = :EntityID LIMIT 1');
  $updateHandler->bindParam(':EntityID', $entityID);
  $updateHandler->bindParam(':IsIdP', $isIdP);
  $updateHandler->bindParam(':IsSP', $isSP);
  $updateHandler->bindParam(':IsAA', $isAA);
  $updateHandler->bindParam(':DisplayName', $displayName);
  $updateHandler->bindParam(':ServiceName', $serviceName);
  $updateHandler->bindParam(':Organization', $organization);
  $updateHandler->bindParam(':Contacts', $contacts);
  $updateHandler->bindParam(':Scopes', $scopes);
  $updateHandler->bindParam(':Ecs', $eCS);
  $updateHandler->bindParam(':Ec', $eC);
  $updateHandler->bindParam(':Assurancec', $assuranceC);
  $updateHandler->bindParam(':RegistrationAuthority', $registrationAuthority);

  $insertHandler = $config->getDb()->prepare('INSERT INTO ExternalEntities
      (`entityID`, `updated`, `isIdP`, `isSP`, `isAA`, `displayName`, `serviceName`, `organization`,
      `contacts`, `scopes`, `ecs`, `ec`, `assurancec`,`ra`)
    VALUES
      (:EntityID, 1, :IsIdP, :IsSP, :IsAA, :DisplayName, :ServiceName, :Organization,
      :Contacts, :Scopes, :Ecs, :Ec, :Assurancec, :RegistrationAuthority)');
  $insertHandler->bindParam(':EntityID', $entityID);
  $insertHandler->bindParam(':IsIdP', $isIdP);
  $insertHandler->bindParam(':IsSP', $isSP);
  $insertHandler->bindParam(':IsAA', $isAA);
  $insertHandler->bindParam(':DisplayName', $displayName);
  $insertHandler->bindParam(':ServiceName', $serviceName);
  $insertHandler->bindParam(':Organization', $organization);
  $insertHandler->bindParam(':Contacts', $contacts);
  $insertHandler->bindParam(':Scopes', $scopes);
  $insertHandler->bindParam(':Ecs', $eCS);
  $insertHandler->bindParam(':Ec', $eC);
  $insertHandler->bindParam(':Assurancec', $assuranceC);
  $insertHandler->bindParam(':RegistrationAuthority', $registrationAuthority);

  $child = $xml->firstChild;
  while ($child) {
    switch (nodeQName($child)) {
      case MD_ENTITIES_DESCRIPTOR :
        checkEntities($child);
        break;
      case DS_SIGNATURE :
      case MD_EXTENSIONS :
        break;
      case MD_ENTITY_DESCRIPTOR :
        $saveEntity = true;
        $entityID = $child->getAttribute('entityID'); #NOSONAR used above
        $isIdP = 0;
        $isSP = 0;
        $isAA = 0;
        $displayName = '';
        $serviceName = '';
        $organization = '';
        $contactsArray = array();
        $scopes = '';
        $eC = '';
        $eCS = '';
        $assuranceC = '';
        $registrationAuthority = '';

        $entityChild =  $child->firstChild;
        while ($entityChild && $saveEntity) {
          switch (nodeQName($entityChild)) {
            case MD_EXTENSIONS :
              foreach ($entityChild->childNodes as $extChild) {
                switch (nodeQName($extChild)) {
                  case MDRPI_REGISTRATION_INFO :
                    $registrationAuthority = $extChild->getAttribute('registrationAuthority');
                    $saveEntity = ($registrationAuthority == 'http://www.swamid.se/') ? false : true;  # NOSONAR Should be http://
                    $saveEntity = ($registrationAuthority == 'http://www.swamid.se/loop') ? false : $saveEntity; # NOSONAR Should be http://
                    break;
                  case MDATTR_ENTITY_ATTRIBUTES :
                    foreach ($extChild->childNodes as $entAttrChild) {
                      if (nodeQName($entAttrChild) == SAML_ATTRIBUTE) {
                        switch ($entAttrChild->getAttribute('Name')){
                          case 'http://macedir.org/entity-category' : # NOSONAR Should be http://
                            foreach ($entAttrChild->childNodes as $attrChild) {
                              if (nodeQName($attrChild) == SAML_ATTRIBUTEVALUE) {
                                $eC .= $attrChild->nodeValue . ' ';
                              }
                            }
                            break;
                          case 'http://macedir.org/entity-category-support' : # NOSONAR Should be http://
                            foreach ($entAttrChild->childNodes as $attrChild) {
                              if (nodeQName($attrChild) == SAML_ATTRIBUTEVALUE) {
                                $eCS .= $attrChild->nodeValue . ' ';
                              }
                            }
                            break;
                          case 'urn:oasis:names:tc:SAML:attribute:assurance-certification' :
                            foreach ($entAttrChild->childNodes as $attrChild) {
                              if (nodeQName($attrChild) == SAML_ATTRIBUTEVALUE) {
                                $assuranceC .= $attrChild->nodeValue . ' ';
                              }
                            }
                            break;
                          case 'https://refeds.org/entity-selection-profile' :
                            break;
                          case 'urn:oasis:names:tc:SAML:profiles:subject-id:req' :
                          case 'http://www.swamid.se/assurance-requirement' : # NOSONAR Should be http://
                          case 'https://federation.renater.fr/member-of' :
                          case 'urn:oid:2.16.756.1.2.5.1.1.4' :
                          case 'urn:oid:2.16.756.1.2.5.1.1.5' :
                          case 'http://kafe.kreonet.net/jurisdiction' : # NOSONAR Should be http://
                            break;
                          default :
                            printf ("Unknown EntityAttribute name %s in entAttrChild->Attribute(Name) in %s\n",
                              $entAttrChild->getAttribute('Name'), $entityID);
                        }
                      }
                    }
                    break;
                  case ALG_SIGNING_METHOD :
                  case ALG_DIGEST_METHOD :
                  case EDUIDCZ_REPUBLISH_REQUEST :
                  case TAAT_TAAT :
                  case SWITCHAAI_EXTENSIONS :
                  case SHIBMD_SCOPE :
                    break;
                  default :
                    printf ("Unknown element %s in md:Extensions in %s\n",
                      nodeQName($extChild), $entityID);
                }
              }
              break;
            case MD_IDPSSO_DESCRIPTOR :
              $isIdP = 1;
              foreach ($entityChild->childNodes as $SSOChild) {
                if (nodeQName($SSOChild) == MD_EXTENSIONS) {
                  foreach ($SSOChild->childNodes as $extChild) {
                    if (nodeQName($extChild) == SHIBMD_SCOPE) {
                      $scopes .= $extChild->nodeValue . ' ';
                    }
                  }
                }
              }
              break;
            case MD_SPSSO_DESCRIPTOR :
              $isSP = 1;
              foreach ($entityChild->childNodes as $SSOChild) {
                switch (nodeQName($SSOChild)) {
                  case MD_EXTENSIONS :
                    foreach ($SSOChild->childNodes as $extChild) {
                      if (nodeQName($extChild) == MDUI_UIINFO) {
                        foreach ($extChild->childNodes as $UUIChild) {
                          if (nodeQName($UUIChild) == MDUI_DISPLAYNAME &&
                            ($displayName == '' || $UUIChild->getAttribute(XML_LANG) == 'en')) {
                            $displayName = $UUIChild->nodeValue;
                          }
                        }
                      }
                    }
                    break;
                  case MD_ATTRIBUTE_CONSUMING_SERVICE :
                    foreach ($SSOChild->childNodes as $acsChild) {
                      if (nodeQName($acsChild) == MD_SERVICE_NAME &&
                        ($serviceName == '' || $acsChild->getAttribute(XML_LANG) == 'en')) {
                          $serviceName = $acsChild->nodeValue;
                        }
                      }
                    break;
                  default :
                    break;
                }
              }
              break;
            case MD_AA_DESCRIPTOR :
              break;
            case MD_ORGANIZATION :
              $orgURL = '';
              $orgName = '';
              foreach ($entityChild->childNodes as $orgChild) {
                switch (nodeQName($orgChild)) {
                  case MD_ORGANIZATION_URL :
                    if ($orgURL == '' || $orgChild->getAttribute(XML_LANG) == 'en') {
                      $orgURL = $orgChild->nodeValue;
                    }
                    break;
                  case MD_ORGANIZATION_DISPLAYNAME :
                    if ($orgName == '' || $orgChild->getAttribute(XML_LANG) == 'en') {
                      $orgName = $orgChild->nodeValue;
                    }
                    break;
                  default:
                    break;
                }
                $organization = sprintf('<a href="%s">%s</a>', $orgURL, $orgName); #NOSONAR used above
              }
              break;
            case MD_CONTACT_PERSON :
              $email = '';
              foreach ($entityChild->childNodes as $contactChild) {
                if (nodeQName($contactChild) == MD_EMAIL_ADDRESS) {
                  $email = $contactChild->nodeValue;
                }
              }
              array_push($contactsArray, array ('type' => contactType($entityChild),
                 'email' => $email));
              break;
            default :
              printf ("Unknown element %s in entityChild in %s\n", nodeQName($entityChild), $entityID);
          }
          $entityChild = $entityChild->nextSibling;
        }
        if ($saveEntity) {
          $contacts = '';
          foreach ($contactsArray as $contact) {
            $contacts .= sprintf ('<a href="%s">%s<a><br>', $contact['email'], $contact['type']);
          }
          $updateHandler->execute();
          if (! $updateHandler->rowCount()) {
            $insertHandler->execute();
          }
        }
        break;
      default:
        printf ("Unknown element %s in first child node\n", nodeQName($child));
    }
    $child = $child->nextSibling;
  }
}
