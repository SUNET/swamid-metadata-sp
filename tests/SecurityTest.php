<?php

require_once __DIR__ . '/../web/html/src/Security.php';
require_once __DIR__ . '/../web/html/src/NormalizeXML.php';

use metadata\NormalizeXML;
use metadata\Security;

function assertSameValue($expected, $actual, $message) {
  if ($expected !== $actual) {
    throw new RuntimeException(sprintf(
      "%s\nExpected: %s\nActual:   %s",
      $message,
      var_export($expected, true),
      var_export($actual, true)
    ));
  }
}

assertSameValue(
  '&lt;script&gt;&quot;&amp;&#039;',
  Security::escape('<script>"&\''),
  'HTML metacharacters must be encoded in text and quoted attributes.'
);
assertSameValue(true, Security::isAllowedLanguage('EN', array('en', 'sv')), 'Configured languages must be accepted.');
assertSameValue(false, Security::isAllowedLanguage('en"><script>', array('en', 'sv')), 'Unconfigured languages must be rejected.');

$safeLink = Security::httpLink('https://example.org/path?q=1&x=2');
assertSameValue(true, strpos($safeLink, '<a href="https://example.org/path?q=1&amp;x=2"') === 0, 'HTTPS URLs must remain links.');
assertSameValue('javascript:alert(1)', Security::httpLink('javascript:alert(1)'), 'Active URL schemes must be inert text.');
assertSameValue('JaVaScRiPt:alert(1)', Security::httpLink('JaVaScRiPt:alert(1)'), 'Scheme checks must be case-insensitive.');
assertSameValue('data:text/html,&lt;script&gt;', Security::httpLink('data:text/html,<script>'), 'Alternate active schemes must be inert text.');
assertSameValue(' https://example.org', Security::httpLink(' https://example.org'), 'Leading whitespace must prevent link creation.');
assertSameValue(
  '&lt;svg onload=alert(1)&gt;',
  Security::httpLink('javascript:alert(1)', '<svg onload=alert(1)>'),
  'Rejected links must still encode their visible text.'
);
assertSameValue(
  array('X-Content-Type-Options: nosniff', 'Content-Type: text/plain; charset=utf-8'),
  Security::rawXmlHeaders(false),
  'Inline raw metadata must be served as non-executable plain text.'
);
assertSameValue(
  array(
    'X-Content-Type-Options: nosniff',
    'Content-Type: application/xml; charset=utf-8',
    'Content-Disposition: attachment; filename=metadata.xml'
  ),
  Security::rawXmlHeaders(true),
  'XML downloads must be forced to an attachment.'
);
$xml = <<<'XML'
<?xml version="1.0"?>
<?attack <script>alert(1)</script>?>
<md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata" entityID="https://example.org/sp">
  <md:Extensions/>
</md:EntityDescriptor>
XML;
$normalizer = new NormalizeXML();
ob_start();
$normalizer->fromString($xml);
$unexpectedOutput = ob_get_clean();
assertSameValue('', $unexpectedOutput, 'Processing instructions must not be reflected to the response.');
assertSameValue(true, $normalizer->getStatus(), 'Discarding a processing instruction must preserve valid metadata.');
assertSameValue(false, strpos($normalizer->getXML(), 'attack') !== false, 'Processing instructions must not survive normalization.');

print "Security regression checks passed.\n";
