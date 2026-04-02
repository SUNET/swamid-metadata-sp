<?php
namespace metadata;

/**
 * Class to hold (and allow extending) attribute definitions
 */
class AttributeDefs {
  /**
   * STANDARD_ATTRIBUTES
   *
   */
  const STANDARD_ATTRIBUTES = array(
    'assurance-certification' => array(
      'http://www.swamid.se/policy/assurance/al1' => array( # NOSONAR Should be http://
        'type' => 'IdP', 'standard' => true
      ),
      'http://www.swamid.se/policy/assurance/al2' => array( # NOSONAR Should be http://
        'type' => 'IdP', 'standard' => true
      ),
      'http://www.swamid.se/policy/assurance/al3' => array( # NOSONAR Should be http://
        'type' => 'IdP', 'standard' => true
      ),
      'https://refeds.org/sirtfi' => array(
        'type' => 'IdP/SP', 'standard' => true
      ),
      'https://refeds.org/sirtfi2' => array(
        'type' => 'IdP/SP', 'standard' => true
      )
    ),
    'entity-category' => array(
      'http://refeds.org/category/research-and-scholarship' => array( # NOSONAR Should be http://
        'type' => 'SP', 'standard' => true
      ),
      'https://refeds.org/category/anonymous' => array(
        'type' => 'SP', 'standard' => true
      ),
      'https://refeds.org/category/pseudonymous' => array(
        'type' => 'SP', 'standard' => true
      ),
      'https://refeds.org/category/personalized' => array(
        'type' => 'SP', 'standard' => true
      ),
      'https://refeds.org/category/code-of-conduct/v2' => array(
        'type' => 'SP', 'standard' => true
      ),
      'https://myacademicid.org/entity-categories/esi' => array(
        'type' => 'SP', 'standard' => true
      ),
      'http://refeds.org/category/hide-from-discovery' => array( # NOSONAR Should be http://
        'type' => 'IdP', 'standard' => true
      )
    ),

    'entity-category-support' => array(
      'https://refeds.org/category/anonymous' => array(
        'type' => 'IdP', 'standard' => true
      ),
      'https://refeds.org/category/pseudonymous' => array(
        'type' => 'IdP', 'standard' => true
      ),
      'https://refeds.org/category/personalized' => array(
        'type' => 'IdP', 'standard' => true
      ),
      'http://refeds.org/category/research-and-scholarship' => array( # NOSONAR Should be http://
        'type' => 'IdP', 'standard' => true
      ),
      'http://www.geant.net/uri/dataprotection-code-of-conduct/v1' => array( # NOSONAR Should be http://
        'type' => 'IdP', 'standard' => true
      ),
      'https://myacademicid.org/entity-categories/esi' => array(
        'type' => 'IdP', 'standard' => true
      ),
      'https://refeds.org/category/code-of-conduct/v2' => array(
        'type' => 'IdP', 'standard' => true
      )
    ),
    'subject-id:req' => array(
      'subject-id' => array(
        'type' => 'SP', 'standard' => true
      ),
      'pairwise-id' => array(
        'type' => 'SP', 'standard' => true
      ),
      'none' => array(
        'type' => 'SP', 'standard' => true
      ),
      'any' => array(
        'type' => 'SP', 'standard' => true
      )
    )
  );
  /**
   * FRIENDLY_NAMES
   *
   */
  const FRIENDLY_NAMES = array(
    'urn:oid:2.5.4.6' => array(
      'desc' => 'c', 'standard' => true
    ),
    'urn:oid:2.5.4.3' => array(
      'desc' => 'cn', 'standard' => true
    ),
    'urn:oid:0.9.2342.19200300.100.1.43' => array(
      'desc' => 'co', 'standard' => true
    ),
    'urn:oid:2.16.840.1.113730.3.1.241' => array(
      'desc' => 'displayName', 'standard' => true
    ),
    'urn:oid:1.3.6.1.4.1.5923.1.1.1.1' => array(
      'desc' => 'eduPersonAffiliation', 'standard' => true
    ),
    'urn:oid:1.3.6.1.4.1.5923.1.1.1.11' => array(
      'desc' => 'eduPersonAssurance', 'standard' => true
    ),
    'urn:oid:1.3.6.1.4.1.5923.1.1.1.7' => array(
      'desc' => 'eduPersonEntitlement', 'standard' => false
    ),
    'urn:oid:1.3.6.1.4.1.5923.1.1.1.16' => array(
      'desc' => 'eduPersonOrcid', 'standard' => true
    ),
    'urn:oid:1.3.6.1.4.1.5923.1.1.1.5' => array(
      'desc' => 'eduPersonPrimaryAffiliation', 'standard' => false
    ),
    'urn:oid:1.3.6.1.4.1.5923.1.1.1.6' => array(
      'desc' => 'eduPersonPrincipalName', 'standard' => true
    ),
    'urn:oid:1.3.6.1.4.1.5923.1.1.1.9' => array(
      'desc' => 'eduPersonScopedAffiliation', 'standard' => true
    ),
    'urn:oid:1.3.6.1.4.1.5923.1.1.1.10' => array(
      'desc' => 'eduPersonTargetedID', 'standard' => true
    ),
    'urn:oid:1.3.6.1.4.1.5923.1.1.1.13' => array(
      'desc' => 'eduPersonUniqueId', 'standard' => true
    ),
    'urn:oid:2.16.840.1.113730.3.1.4' => array(
      'desc' => 'employeeType', 'standard' => false
    ),
    'urn:oid:2.16.840.1.113730.3.1.13' => array(
      'desc' => 'mailLocalAddress', 'standard' => true
    ),
    'urn:oid:2.5.4.42' => array(
      'desc' => 'givenName', 'standard' => true
    ),
    'urn:oid:0.9.2342.19200300.100.1.10' => array(
      'desc' => 'manager', 'standard' => false
    ),
    'urn:oid:0.9.2342.19200300.100.1.3' => array(
      'desc' => 'mail', 'standard' => true
    ),
    'urn:oid:2.5.4.10' => array(
      'desc' => 'o', 'standard' => true
    ),
    'urn:oid:1.2.752.29.4.13' => array(
      'desc' => 'personalIdentityNumber', 'standard' => true
    ),
    'urn:oid:1.3.6.1.4.1.25178.1.2.3' => array(
      'desc' => 'schacDateOfBirth', 'standard' => true
    ),
    'urn:oid:1.3.6.1.4.1.25178.1.2.9' => array(
      'desc' => 'schacHomeOrganization', 'standard' => true
    ),
    'urn:oid:1.3.6.1.4.1.25178.1.2.10' => array(
      'desc' => 'schacHomeOrganizationType', 'standard' => true
    ),
    'urn:oid:2.5.4.4' => array(
      'desc' => 'sn', 'standard' => true
    ),
    'urn:oid:0.9.2342.19200300.100.1.1' => array(
      'desc' => 'uid', 'standard' => false
    ),

    'urn:mace:dir:attribute-def:cn' => array(
      'desc' => 'cn', 'standard' => false
    ),
    'urn:mace:dir:attribute-def:displayName' => array(
      'desc' => 'displayName', 'standard' => false
    ),
    'urn:mace:dir:attribute-def:eduPersonPrincipalName' => array(
      'desc' => 'eduPersonPrincipalName', 'standard' => false
    ),
    'urn:mace:dir:attribute-def:eduPersonScopedAffiliation' => array(
      'desc' => 'eduPersonScopedAffiliation', 'standard' => false
    ),
    'urn:mace:dir:attribute-def:eduPersonTargetedID' => array(
      'desc' => 'eduPersonTargetedID', 'standard' => false
    ),
    'urn:mace:dir:attribute-def:givenName' => array(
      'desc' => 'givenName', 'standard' => false
    ),
    'urn:mace:dir:attribute-def:mail' => array(
      'desc' => 'mail', 'standard' => false
    ),
    'urn:mace:dir:attribute-def:sn' => array(
      'desc' => 'sn', 'standard' => false
    ),

    'urn:oid:1.2.840.113549.1.9.1.1' => array(
      'desc' => 'Wrong - email', 'standard' => false
    )
  );

  /**
   * Returns an associative of entity attribute definitions, indexed by entity attribute name.
   *
   * @return array
   */

  public function getStandardEntityAttributes() {
    return self::STANDARD_ATTRIBUTES;
  }

  /**
   * Returns an associative of attribute definitions, indexed by attribute SAML name.
   *
   * @return array
   */

  public function getAttributeFriendlyNames() {
    return self::FRIENDLY_NAMES;
  }
}
