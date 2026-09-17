<?php

namespace metadata;

/**
 * Class to hold (and allow extending) attribute definitions
 */
class AttributeDefsSWAMID extends AttributeDefs
{
  /**
   * STANDARD_ATTRIBUTES
   *
   */
  protected const STANDARD_ATTRIBUTES_SWAMID = array(
    'entity-category' => array(
      'http://www.swamid.se/policy/assurance/al2' => array( # NOSONAR Should be http://
        'type' => 'SP', 'standard' => true
      ),
      'http://www.swamid.se/policy/assurance/al3' => array( # NOSONAR Should be http://
        'type' => 'SP', 'standard' => true
      )
    )
  );

  /**
   * FRIENDLY_NAMES
   *
   */
  protected const FRIENDLY_NAMES_SWAMID = array(
    'urn:oid:1.3.6.1.4.1.2428.90.1.6' => array(
      'desc' => 'norEduOrgAcronym', 'standard' => true
    ),
    'urn:oid:1.3.6.1.4.1.2428.90.1.10' => array(
      'desc' => 'norEduPersonLegalName', 'standard' => true
    ),
    'urn:oid:1.3.6.1.4.1.2428.90.1.5' => array(
      'desc' => 'norEduPersonNIN', 'standard' => true
    ),
  );

  /**
   * Returns an associative of entity attribute definitions, indexed by entity attribute name.
   *
   * @return array<mixed>
   */

  public function getStandardEntityAttributes()
  {
    return array_merge_recursive(self::STANDARD_ATTRIBUTES, self::STANDARD_ATTRIBUTES_SWAMID);
  }

  /**
   * Returns attribute definitions customised for Tuakiri
   *
   * @return array<mixed>
   */
  public function getAttributeFriendlyNames()
  {
    return array_merge(self::FRIENDLY_NAMES, self::FRIENDLY_NAMES_SWAMID);
  }
}
