<?php
//Load composer's autoloader
require_once __DIR__ . '/../html/vendor/autoload.php';

$config = new metadata\Configuration();

const MD_EXTENSIONS = 'md:Extensions';
const SAML_ATTRIBUTEVALUE = 'saml:AttributeValue';
const XML_LANG = 'xml:lang';

$config->getDb()->query('UPDATE ExternalEntities SET updated = 0');

$xml = new DOMDocument;
$xml->preserveWhiteSpace = false;
$xml->formatOutput = true;
$xml->load('/opt/metadata/swamid-2.0.xml');
$xml->encoding = 'UTF-8';

checkEntities($xml);
unset($xml);
$config->getDb()->query('DELETE FROM ExternalEntities WHERE updated = 0');

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
    switch ($child->nodeName) {
      case 'md:EntitiesDescriptor' :
        checkEntities($child);
        break;
      case 'ds:Signature' :
      case MD_EXTENSIONS :
        break;
      case 'md:EntityDescriptor':
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
          switch ($entityChild->nodeName) {
            case MD_EXTENSIONS :
              foreach ($entityChild->childNodes as $extChild) {
                switch ($extChild->nodeName) {
                  case 'mdrpi:RegistrationInfo' :
                    $registrationAuthority = $extChild->getAttribute('registrationAuthority');
                    $saveEntity = ($registrationAuthority == 'http://www.swamid.se/') ? false : true;  # NOSONAR Should be http://
                    $saveEntity = ($registrationAuthority == 'http://www.swamid.se/loop') ? false : $saveEntity; # NOSONAR Should be http://
                    break;
                  case 'mdattr:EntityAttributes' :
                    foreach ($extChild->childNodes as $entAttrChild) {
                      if ($entAttrChild->nodeName == 'saml:Attribute') {
                        switch ($entAttrChild->getAttribute('Name')){
                          case 'http://macedir.org/entity-category' : # NOSONAR Should be http://
                            foreach ($entAttrChild->childNodes as $attrChild) {
                              if ($attrChild->nodeName == SAML_ATTRIBUTEVALUE) {
                                $eC .= $attrChild->nodeValue . ' ';
                              }
                            }
                            break;
                          case 'http://macedir.org/entity-category-support' : # NOSONAR Should be http://
                            foreach ($entAttrChild->childNodes as $attrChild) {
                              if ($attrChild->nodeName == SAML_ATTRIBUTEVALUE) {
                                $eCS .= $attrChild->nodeValue . ' ';
                              }
                            }
                            break;
                          case 'urn:oasis:names:tc:SAML:attribute:assurance-certification' :
                            foreach ($entAttrChild->childNodes as $attrChild) {
                              if ($attrChild->nodeName == SAML_ATTRIBUTEVALUE) {
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
                            printf ("Missing %s in entAttrChild->Attribute(Name) in %s\n",
                              $entAttrChild->getAttribute('Name'), $entityID);
                        }
                      }
                    }
                    break;
                  case 'alg:SigningMethod' :
                  case 'alg:DigestMethod' :
                  case 'eduidmd:RepublishRequest' :
                  case 'taat:taat' :
                  case 'mdext:SWITCHaaiExtensions' :
                  case 'shibmd:Scope' :
                    break;
                  default :
                    printf ("Missing %s in md:Extensions in %s\n",
                      $extChild->nodeName, $entityID);
                }
              }
              break;
            case 'md:IDPSSODescriptor' :
              $isIdP = 1;
              foreach ($entityChild->childNodes as $SSOChild) {
                if ($SSOChild->nodeName == MD_EXTENSIONS) {
                  foreach ($SSOChild->childNodes as $extChild) {
                    if ($extChild->nodeName == 'shibmd:Scope') {
                      $scopes .= $extChild->nodeValue . ' ';
                    }
                  }
                }
              }
              break;
            case 'md:SPSSODescriptor' :
              $isSP = 1;
              foreach ($entityChild->childNodes as $SSOChild) {
                switch ($SSOChild->nodeName) {
                  case MD_EXTENSIONS :
                    foreach ($SSOChild->childNodes as $extChild) {
                      if ($extChild->nodeName == 'mdui:UIInfo') {
                        foreach ($extChild->childNodes as $UUIChild) {
                          if ($UUIChild->nodeName == 'mdui:DisplayName' &&
                            ($displayName == '' || $UUIChild->getAttribute(XML_LANG) == 'en')) {
                            $displayName = $UUIChild->nodeValue;
                          }
                        }
                      }
                    }
                    break;
                  case 'md:AttributeConsumingService' :
                    foreach ($SSOChild->childNodes as $acsChild) {
                      if ($acsChild->nodeName == 'md:ServiceName' &&
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
            case 'md:AttributeAuthorityDescriptor' :
              break;
            case 'md:Organization' :
              $orgURL = '';
              $orgName = '';
              foreach ($entityChild->childNodes as $orgChild) {
                switch ($orgChild->nodeName) {
                  case 'md:OrganizationURL' :
                    if ($orgURL == '' || $orgChild->getAttribute(XML_LANG) == 'en') {
                      $orgURL = $orgChild->nodeValue;
                    }
                    break;
                  case 'md:OrganizationDisplayName' :
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
            case 'md:ContactPerson' :
              $email = '';
              foreach ($entityChild->childNodes as $contactChild) {
                if ($contactChild->nodeName == 'md:EmailAddress') {
                  $email = $contactChild->nodeValue;
                }
              }
              array_push($contactsArray, array ('type' => $entityChild->getAttribute('contactType'),
                 'email' => $email));
              break;
            default :
              printf ("Missing %s in entityChild in %s\n", $entityChild->nodeName, $entityID);
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
        printf ("Missing %s in child in %s\n", $child->nodeName, $entityID);
    }
    $child = $child->nextSibling;
  }
}
