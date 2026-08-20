<?php

require_once __DIR__ . '/../web/html/src/Security.php';

foreach (\metadata\Security::rawXmlHeaders(false) as $header) {
  header($header);
}

print <<<'XML'
<?xml version="1.0"?>
<md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata"
  xmlns:svg="http://www.w3.org/2000/svg" entityID="https://example.org/sp">
  <md:Extensions>
    <svg:svg><svg:script>document.title = 'XSS_EXECUTED';</svg:script></svg:svg>
  </md:Extensions>
</md:EntityDescriptor>
XML;
