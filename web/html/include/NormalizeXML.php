<?php
Class NormalizeXML {
	# Setup
	function __construct() {
		$this->nsList = array();
		#$this->nsList['xsi'] = array('uri' => 'http://www.w3.org/2001/XMLSchema-instance', 'translate'=>'xsi');
		$this->knownList = array(
			'urn:oasis:names:tc:SAML:2.0:metadata' => 'md',
			'urn:oasis:names:tc:SAML:metadata:rpi' => 'mdrpi',
			'urn:oasis:names:tc:SAML:metadata:attribute' => 'mdattr',
			'urn:oasis:names:tc:SAML:2.0:assertion' => 'samla',
			'urn:oasis:names:tc:SAML:profiles:SSO:request-init' => 'init',
			'urn:oasis:names:tc:SAML:profiles:SSO:idp-discovery-protocol' => 'idpdisc',
			'urn:oasis:names:tc:SAML:metadata:ui' => 'mdui',
			'urn:mace:shibboleth:metadata:1.0' => 'shibmd',
			'http://refeds.org/metadata' => 'remd',
			'http://www.w3.org/2000/09/xmldsig#' => 'ds',
			'http://www.w3.org/2001/XMLSchema' => 'xs',
			'http://www.w3.org/2001/XMLSchema-instance' => 'xsi',
			'urn:oasis:names:tc:SAML:metadata:algsupport' => 'alg',
			#ADFS / M$
			'http://docs.oasis-open.org/wsfed/federation/200706' => 'fed',
			'http://docs.oasis-open.org/wsfed/authorization/200706' => 'auth');

		$this->entityID = '';
		$this->error = 'No XML loaded';
		$this->status = false;
		$this->nextNS = 1;
	}

	private function checkNode(&$new, $data, $doc) {
		foreach ($data->childNodes as $child) {
			if ($this->hasChild($child)) {
				$name = $this->checkNameSpaceNode($child);
				$newChild = $doc->createElement($name);
				$new->appendChild($newChild);
				if ($child->hasAttributes() )  {
					$nrOfAttribues = $child->attributes->count();
					for ($index = 0; $index < $nrOfAttribues; $index++) {
						if ($child->attributes->item($index)->prefix) {
							$newChild->setAttribute($this->checkNameSpaceAttribute($child->attributes->item($index)).':'.$child->attributes->item($index)->name, $child->attributes->item($index)->value);
						} else
							$newChild->setAttribute($child->attributes->item($index)->name, $child->attributes->item($index)->value);
					}
				}
				$this->checkNode($newChild, $child, $doc);
				if ($name == 'md:EntityDescriptor') {
					foreach ($this->nsList as $ns => $uriArray) {
						$newChild->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:'.$ns, $uriArray['uri']);
					}
					if ($newChild->hasAttribute('ID')) $newChild->removeAttribute('ID');
				}
			} else {
				switch ($child->nodeType) {
					case 1 :
						$name = $this->checkNameSpaceNode($child);
						$newChild = $doc->createElement($name);
						$new->appendChild($newChild);
						if ($child->nodeValue) {
							$newText = $doc->createTextNode($child->nodeValue);
							$newChild->appendChild($newText);
						}
						if ($child->hasAttributes() )  {
							$nrOfAttribues = $child->attributes->count();
							for ($index = 0; $index < $nrOfAttribues; $index++) {
								if ($child->attributes->item($index)->prefix) {
									$newChild->setAttribute($this->checkNameSpaceAttribute($child->attributes->item($index)).':'.$child->attributes->item($index)->name, $child->attributes->item($index)->value);
								} else
									$newChild->setAttribute($child->attributes->item($index)->name, $child->attributes->item($index)->value);
							}
						}
						break;
					case 3 :
					case 8 :
						break;
					default :
						printf ('-----> Okänd typ %s<br>%s', $child->nodeType, $child->nodeValue);
				}
			}
		}
	}
	private function hasChild($node) {
		if ($node->hasChildNodes()) {
			foreach ($node->childNodes as $child) {
				if ($child->nodeType == XML_ELEMENT_NODE)
					return true;
			}
		}
		return false;
	}
	private function checkNameSpaceNode($node) {
		$nameParts = explode(':', $node->nodeName, 2);
		if (isset($nameParts[1])) {
			$suggestedNS = $nameParts[0];
			$name = $nameParts[1];
		} else {
			$name = $nameParts[0];
		}
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

	private function checkNameSpaceAttribute($attribute) {
		$uri = $attribute->namespaceURI;
		if ($uri == 'http://www.w3.org/XML/1998/namespace') {
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

	function checkDOMError ($number, $error){
		$errorParts = explode(' ', $error);
		switch ($errorParts[0]) {
			case 'DOMDocument::load():' :
				$this->error = preg_replace('/ in .*, line:/', ' line:', substr($error, 21));
				$this->status = false;
				break;
			default :
				$this->error = $error;
				$this->status = false;
		}
	}

	public function fromFile($filename) {
		$this->nsList = array();
		if (file_exists($filename)) {
			if (is_readable($filename)) {
				$doc = new DOMDocument('1.0', 'UTF-8');
				set_error_handler(array($this, 'checkDOMError'));
				if ( $doc->load($filename) ) {
					$this->newDoc = new DOMDocument('1.0', 'UTF-8');
					$this->newDoc->formatOutput = true;
					$this->checkNode($this->newDoc, $doc, $this->newDoc);
					$this->entityID = false;
					$child = $this->newDoc->firstChild;
					while ($child && ! $this->entityID) {
						if ($child->nodeName == "md:EntityDescriptor") {
							$this->entityID = $child->getAttribute('entityID');
						}
						$child = $child->nextSibling;
					}
					if ($this->entityID) {
						$this->error = '';
						$this->status = true;
					} else {
						$this->error = 'Cant find entityID in EntityDescriptor';
						$this->status = false;
					}
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

	public function fromString($xml) {
		$this->nsList = array();
		$doc = new DOMDocument('1.0', 'UTF-8');
		set_error_handler(array($this, 'checkDOMError'));
		if ($doc->loadXML($xml)) {
			$this->newDoc = new DOMDocument('1.0', 'UTF-8');
			$this->newDoc->formatOutput = true;
			$this->checkNode($this->newDoc, $doc, $this->newDoc);
			$this->entityID = false;
			$child = $this->newDoc->firstChild;
			while ($child && ! $this->entityID) {
				if ($child->nodeName == "md:EntityDescriptor") {
					$this->entityID = $child->getAttribute('entityID');
				}
				$child = $child->nextSibling;
			}
			if ($this->entityID) {
				$this->error = '';
				$this->status = true;
			} else {
				$this->error = 'Cant find entityID in EntityDescriptor';
				$this->status = false;
			}
		}
		restore_error_handler();
	}

	public function getXML() {
		if ($this->status)
			return $this->newDoc->saveXML();
		else
			return false;
	}

	public function getEntityID() {
		if ($this->status)
			return $this->entityID;
		else
			return false;
	}

	public function getStatus() {
		return $this->status;
	}

	public function getError() {
		return $this->error;
	}

	public function cleanOutRegistrationInfo($xml2clean) {
		$continue = true;
		$xml = new DOMDocument;
		$xml->preserveWhiteSpace = false;
		$xml->formatOutput = true;
		$xml->loadXML($xml2clean);
		$xml->encoding = 'UTF-8';
		$EntityDescriptor = false;

		$child = $xml->firstChild;
		while ($child && $continue) {
			if ($child->nodeName == "md:EntityDescriptor") {
				$EntityDescriptor = $child;
				$Extensions = $child->firstChild;
				while ($Extensions && $continue) {
					if ($Extensions->nodeName == 'md:Extensions') {
						$subchild = $Extensions->firstChild;
						while ($subchild && $continue) {
							if ($subchild->nodeName == 'mdrpi:RegistrationInfo') {
								$remChild = $subchild;
								$subchild = $subchild->nextSibling;
								$Extensions->removeChild($remChild);
								$continue = false;
							} else
								$subchild = $subchild->nextSibling;
						}
						$continue = false;
					}
					$Extensions = $Extensions->nextSibling;
				}
			}
			$child = $child->nextSibling;
		}
		return $xml->saveXML();
	}
}