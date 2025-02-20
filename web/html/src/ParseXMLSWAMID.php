<?php
namespace metadata;

/**
 * Class to Parse XML for an Entity into Database
 * SWAMID specific code
 */
class ParseXMLSWAMID extends ParseXML {
  use SAMLTrait;

  # Setup

  #############
  # IDPSSODescriptor
  # SWAMID
  #############
  /**
   * Parse IDPSSODescriptor
   *
   * SWAMID version
   * - runs parent
   * - error if missing support for SAML2
   * - warning if supporting SAML1
   *
   * @param DOMNode $data XML to parse
   *
   * @return void
   */
  protected function parseIDPSSODescriptor($data) {
    parent::parseIDPSSODescriptor($data);

    if (! $this->saml2found) {
      $this->error .= "IDPSSODescriptor is missing support for SAML2.\n";
    } elseif ($this->saml1found) {
      $this->warning .= "IDPSSODescriptor claims support for SAML1. SWAMID is a SAML2 federation\n";
    }
  }

 /**
   * Parse SPSSODescriptor
   *
   * SWAMID version
   * - runs parent
   * - error if missing support for SAML2
   * - Nonbreaking error if supporting SAML1
   * - Cleans out AssertionConsumerService of type HTTPRedirect (6.1.16)
   *
   * @param DOMNode $data XML to parse
   *
   * @return void
   */
  protected function parseSPSSODescriptor($data) {
    parent::parseSPSSODescriptor($data);

    if (! $this->saml2found) {
      $this->error .= "SPSSODescriptor is missing support for SAML2.\n";
    } elseif ($this->saml1found) {
      $this->errorNB .= "SPSSODescriptor claims support for SAML1. SWAMID is a SAML2 federation\n";
    }
    // 6.1.16
    if ($this->assertionConsumerServiceHTTPRedirectFound) {
      $this->cleanOutAssertionConsumerServiceHTTPRedirect();
    }
  }

  /**
   * Checks SAMLEndpoint
   *
   * Verifies that it's a binding used in protocols of the SSODescriptor
   * SWAMID version
   * - error if not https://  (5.1.21 / 6.1.15)
   * - runs parent
   *
   * @param DOMNode $data XML to parse
   *
   * @param string $type Type of Node
   *  - AttributeAuthority
   *  - IDPSSO
   *  - SPSSO
   *
   * @param boolean $saml2 If SSODescriptor is of type SAML2
   *
   * @param boolean $saml1 If SSODescriptor is of type SAML1
   *
   * @return void
   */
  protected function checkSAMLEndpoint($data,$type, $saml2, $saml1) {
    $name = $data->nodeName;
    $binding = $data->getAttribute('Binding');
    $location =$data->getAttribute('Location');
    if (substr($location,0,8) <> self::TEXT_HTTPS) {
      $this->error .= sprintf(
        "SWAMID Tech %s: All SAML endpoints MUST start with https://. Problem in %sDescriptor->%s[Binding=%s].\n",
        $type == "IDPSSO" ? '5.1.21' : '6.1.15', $type, $name, $binding);
    }
    parent::checkSAMLEndpoint($data,$type, $saml2, $saml1);
  }

  /**
   * Clean out AssertionConsumerService of type HTTPRedirect
   *
   * Removes AssertionConsumerService with binding = HTTP-Redirect.
   *
   * @param DOMNode $data XML to parse
   *
   * @return void
   */
  private function cleanOutAssertionConsumerServiceHTTPRedirect() {
    $removed = false;
    $entityDescriptor = $this->getEntityDescriptor($this->xml);
    $child = $entityDescriptor->firstChild;
    while ($child) {
      if ($child->nodeName == self::SAML_MD_SPSSODESCRIPTOR) {
        $subchild = $child->firstChild;
        while ($subchild) {
          if ($subchild->nodeName == self::SAML_MD_ASSERTIONCONSUMERSERVICE
            && $subchild->getAttribute('Binding') == self::SAML_BINDING_HTTP_REDIRECT) {
            $index = $subchild->getAttribute('index');
            $remChild = $subchild;
            $child->removeChild($remChild);
            $subchild = false;
            $child=false;
            $removed = true;
          } else {
            $subchild = $subchild->nextSibling;
          }
        }
      } else {
        $child = $child->nextSibling;
      }
    }
    if ($removed) {
      $this->error .= 'SWAMID Tech 6.1.16: Binding with value urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect';
      $this->error .= ' is not allowed in';
      $this->error .= sprintf(" SPSSODescriptor->AssertionConsumerService[index=%d]. Have been removed.\n", $index);
    }
  }
}