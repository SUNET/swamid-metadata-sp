<?php
namespace metadata\Display;

/**
 * Common Class to display Metadata Statistics
 */
class Common extends \metadata\Common {

  protected array $collapseIcons = array();

  const SAML_EC_ANONYMOUS = 'https://refeds.org/category/anonymous';
  const SAML_EC_COCOV1 = 'http://www.geant.net/uri/dataprotection-code-of-conduct/v1'; # NOSONAR Should be http://
  const SAML_EC_COCOV2 = 'https://refeds.org/category/code-of-conduct/v2';
  const SAML_EC_ESI = 'https://myacademicid.org/entity-categories/esi';
  const SAML_EC_PERSONALIZED = 'https://refeds.org/category/personalized';
  const SAML_EC_PSEUDONYMOUS = 'https://refeds.org/category/pseudonymous';
  const SAML_EC_RANDS = 'http://refeds.org/category/research-and-scholarship'; # NOSONAR Should be http://

  const HTML_ACTIVE = ' active';
  const HTML_SHOW = ' show';
  const HTML_TABLE_END = "    </table>\n";
  const HTML_TRUE = 'true';

  /**
   * Returns an array of HeadersIcons that should be collapsable
   *
   * @return array
   */
  public function getCollapseIcons() {
    return $this->collapseIcons;
  }
}