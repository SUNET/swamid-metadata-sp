<?php
class NormalizeXML {
  # Setup
  const SAML_MD_ENTITYDESCRIPTOR = 'md:EntityDescriptor';

  public function __construct() {
    $this->nsList = array();
    $this->knownList = array(
      'urn:oasis:names:tc:SAML:2.0:metadata' => 'md',
      'urn:oasis:names:tc:SAML:metadata:rpi' => 'mdrpi',
      'urn:oasis:names:tc:SAML:metadata:attribute' => 'mdattr',
      'urn:oasis:names:tc:SAML:2.0:assertion' => 'samla',
      'urn:oasis:names:tc:SAML:profiles:SSO:request-init' => 'init',
      'urn:oasis:names:tc:SAML:profiles:SSO:idp-discovery-protocol' => 'idpdisc',
      'urn:oasis:names:tc:SAML:metadata:ui' => 'mdui',
      'urn:mace:shibboleth:metadata:1.0' => 'shibmd',
      'http://refeds.org/metadata' => 'remd', # NOSONAR Should be http://
      'http://www.w3.org/2000/09/xmldsig#' => 'ds', # NOSONAR Should be http://
      'http://www.w3.org/2001/XMLSchema' => 'xs', # NOSONAR Should be http://
      'http://www.w3.org/2001/XMLSchema-instance' => 'xsi', # NOSONAR Should be http://
      'urn:oasis:names:tc:SAML:metadata:algsupport' => 'alg',
      'http://id.swedenconnect.se/authn/1.0/principal-selection/ns' => 'psc',
      #ADFS / M$
      'http://docs.oasis-open.org/wsfed/federation/200706' => 'fed', # NOSONAR Should be http://
      'http://docs.oasis-open.org/wsfed/authorization/200706' => 'auth'); # NOSONAR Should be http://

    $this->error = 'No XML loaded';
    $this->status = false;
    $this->nextNS = 1;
    $this->entityID = false;
    $this->newDoc = new DOMDocument('1.0', 'UTF-8');
    $this->newDoc->formatOutput = true;
  }

  # Check Node and sub-nodes
  private function checkNode(&$new, &$data, &$doc) {
    foreach ($data->childNodes as $child) {
      # Remove Signature
      if ($child->namespaceURI == 'http://www.w3.org/2000/09/xmldsig#' && $child->localName == 'Signature') {
        continue;
      }
      # Remove RoleDescriptor
      if ($child->namespaceURI == 'urn:oasis:names:tc:SAML:2.0:metadata' && $child->localName == 'RoleDescriptor') {
        continue;
      }
      if ($this->hasChild($child)) {
        $name = $this->checkNameSpaceNode($child);
        $newChild = $doc->createElement($name);
        $new->appendChild($newChild);
        $this->checkAttributes($child, $newChild);
        $this->checkNode($newChild, $child, $doc);
        if ($name == self::SAML_MD_ENTITYDESCRIPTOR) {
          $this->updateEntityDescriptor($newChild);
        }
      } else {
        switch ($child->nodeType) {
          case 1 :
            $name = $this->checkNameSpaceNode($child);
            $newChild = $doc->createElement($name);
            $new->appendChild($newChild);
            if ($child->nodeValue) {
              $newText = $doc->createTextNode(trim($child->nodeValue));
              $newChild->appendChild($newText);
            }
            $this->checkAttributes($child, $newChild);
            break;
          case 3 :
            // TEXT_NODE
          case 8 :
            // COMMENT_NODE
            break;
          default :
            printf ('-----> Okänd typ %s<br>%s', $child->nodeType, $child->nodeValue);
        }
      }
    }
  }

  # CHeck if a Node has a child
  private function hasChild($node) {
    if ($node->hasChildNodes()) {
      foreach ($node->childNodes as $child) {
        if ($child->nodeType == XML_ELEMENT_NODE) {
          return true;
        }
      }
    }
    return false;
  }

  # Check Attribute
  private function checkAttributes(&$child, &$newChild) {
    if ($child->hasAttributes() )  {
      $nrOfAttribues = $child->attributes->count();
      for ($index = 0; $index < $nrOfAttribues; $index++) {
        $value = $child->attributes->item($index)->value;
        if ($child->attributes->item($index)->namespaceURI == 'http://www.w3.org/2001/XMLSchema-instance'  &&
          $child->attributes->item($index)->name == 'type'){
          $valueParts = explode(':', $value, 2);
          $value = ($valueParts[1] == 'string') ? 'xs:string' : $value;
        }
        if ($child->attributes->item($index)->prefix) {
          $newChild->setAttribute($this->checkNameSpaceAttribute(
            $child->attributes->item($index)).':'.$child->attributes->item($index)->name,
            $value);
        } else {
          $newChild->setAttribute(
            $child->attributes->item($index)->name,
            $value);
        }
      }
    }
  }

  # Check Namespace of node and add to list if missing. Also normalize Namespace
  private function checkNameSpaceNode(&$node) {
    $suggestedNS = $node->prefix;
    $name = $node->localName;
    $uri = $node->namespaceURI;
    while ($uri == '' && $suggestedNS != '') {
      foreach ($this->knownList as $knowURI => $knowNS) {
        if ( $suggestedNS == $knowNS ) {
          $uri = $knowURI;
        }
      }
    }
    if (isset($this->knownList[$uri])) {
      $ns = $this->knownList[$uri];
    } else {
      $ns = 'ns'.$this->nextNS;
      $this->knownList[$uri] = $ns;
      $this->nextNS++;
    }

    if (! isset($this->nsList[$ns])) {
      $this->nsList[$ns] = array('uri' => $uri);
    }
    return $ns . ':' . $name;
  }

  # Check Namespace of Attribute and add to list if missing. Also normalize Namespace
  private function checkNameSpaceAttribute(&$attribute) {
    $uri = $attribute->namespaceURI;
    if ($uri == 'http://www.w3.org/XML/1998/namespace') { # NOSONAR Should be http://
      $ns = 'xml';
    } else {
      if (isset($this->knownList[$uri])) {
        $ns = $this->knownList[$uri];
      } else {
        $ns = 'ns'.$this->nextNS;
        $this->knownList[$uri] = $ns;
        $this->nextNS++;
      }
      if (! isset($this->nsList[$ns])) {
        $this->nsList[$ns] = array('uri' => $uri);
      }
    }
    return $ns;
  }

  # error-handler to catch errors while loading XML
  public function checkDOMError ($number, $error){ #NOSONAR $number is in call!!!
    $errorParts = explode(' ', $error);
    if ($errorParts[0] == 'DOMDocument::load():') {
      $this->error .= preg_replace('/ in .*, line:/', ' line:', substr($error, 21)) . "<br>";
      $this->status = false;
    } else {
      $this->error .= $error;
      $this->status = false;
    }
  }

  # Load XML from file and parse into $this->newDoc
  public function fromFile($filename) {
    $this->nsList = array();
    if (file_exists($filename)) {
      if (is_readable($filename)) {
        $this->error = '';
        $doc = new DOMDocument('1.0', 'UTF-8');
        set_error_handler(array($this, 'checkDOMError'));
        if ( $doc->load($filename) ) {
          $this->parseXML($doc);
        }
        restore_error_handler();
      } else {
        $this->error = sprintf('Can not access %s', $filename);
        $this->status = false;
      }
    } else {
      $this->error = sprintf('Can not find %s', $filename);
      $this->status = false;
    }
  }

  # Load XML from string and parse into $this->newDoc
  public function fromString($xml) {
    $this->nsList = array();
    $doc = new DOMDocument('1.0', 'UTF-8');
    set_error_handler(array($this, 'checkDOMError'));
    if ($doc->loadXML($xml)) {
      $this->parseXML($doc);
    }
    restore_error_handler();
  }

  # Parse XML info $this->newDoc
  private function parseXML($doc) {
    $this->checkNode($this->newDoc, $doc, $this->newDoc);
    if ($this->entityID) {
      $this->error = '';
      $this->status = true;
    } else {
      $this->error = 'Cant find entityID in EntityDescriptor';
      $this->status = false;
    }
  }

  # Updates EntityDescriptor with all Namespaces found in XML
  private function updateEntityDescriptor(&$dom){
    if (isset($this->nsList['xsi']) && ! isset($this->nsList['xs'])) {
      $this->nsList['xs'] = array('uri' =>'http://www.w3.org/2001/XMLSchema'); # NOSONAR Should be http://
    }

    foreach ($this->nsList as $ns => $uriArray) {
      $dom->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:'.$ns, $uriArray['uri']); # NOSONAR Should be http://
    }
    $this->entityID = $dom->getAttribute('entityID');
    # Remove unwanted attributes
    if ($dom->hasAttribute('validUntil')) { $dom->removeAttribute('validUntil'); }
    if ($dom->hasAttribute('cacheDuration')) { $dom->removeAttribute('cacheDuration'); }
    if ($dom->hasAttribute('ID')) { $dom->removeAttribute('ID'); }
  }

  # Return parsed XML
  public function getXML() {
    if ($this->status) {
      return $this->newDoc->saveXML();
    } else {
      return false;
    }
  }

  #Return entityID of parsed XML
  public function getEntityID() {
    if ($this->status) {
      return $this->entityID;
    } else {
      return false;
    }
  }

  # Return status of parsing
  public function getStatus() {
    return $this->status;
  }

  # Return errors from parsing
  public function getError() {
    return $this->error;
  }

  # Loads an XML and return the same but without RegistrationInfo
  public function cleanOutRegistrationInfo($xml2clean) {
    $continue = true;
    $xml = new DOMDocument;
    $xml->preserveWhiteSpace = false;
    $xml->formatOutput = true;
    $xml->loadXML($xml2clean);
    $xml->encoding = 'UTF-8';

    $child = $xml->firstChild;
    while ($child && $continue) {
      if ($child->nodeName == self::SAML_MD_ENTITYDESCRIPTOR) {
        $extensions = $child->firstChild;
        while ($extensions && $continue) {
          if ($extensions->nodeName == 'md:Extensions') {
            $subchild = $extensions->firstChild;
            while ($subchild && $continue) {
              if ($subchild->nodeName == 'mdrpi:RegistrationInfo') {
                $remChild = $subchild;
                $subchild = $subchild->nextSibling;
                $extensions->removeChild($remChild);
                $continue = false;
              } else {
                $subchild = $subchild->nextSibling;
              }
            }
            $continue = false;
          }
          $extensions = $extensions->nextSibling;
        }
      }
      $child = $child->nextSibling;
    }
    return $xml->saveXML();
  }
}
