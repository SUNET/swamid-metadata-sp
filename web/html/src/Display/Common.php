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
  const HTML_SPACER = '  ';
  const HTML_TABLE_END = "    </table>\n";
  const HTML_TRUE = 'true';

  /**
   * Shows Collapsable Header
   *
   * @param string $title Title of header
   *
   * @param string $name Name of header
   *
   * @param bool $haveSub If header have subheaders
   *
   * @param int $step Steps to indent
   *
   * @param bool $expanded If expanded by default
   *
   * @param bool|string $extra if we have extra info
   *
   * @param int $entityId Id of current Entity
   *
   * @param int $oldEntityId Id of old Entity
   *
   * @return void
   */
  protected function showCollapse($title, $name, $haveSub=true, $step=0, $expanded=true,
    $extra = false, $entityId=0, $oldEntityId=0) {
    $spacer = '';
    while ($step > 0 ) {
      $spacer .= self::HTML_SPACER;
      $step--;
    }
    if ($expanded) {
      $icon = 'down';
      $show = 'show ';
    } else {
      $icon = 'right';
      $show = '';
    }
    switch ($extra) {
      case 'SSO' :
        $extraButton = sprintf('<form action="." method="POST" name="removeSSO%s" style="display: inline;"><input type="hidden" name="removeSSO" value="%d"><input type="hidden" name="type" value="%s"><a href="#" onClick="document.forms.removeSSO%s.submit();"><i class="fas fa-trash"></i></a></form>', $name, $entityId, $name, $name);
        break;
      case 'EntityAttributes' :
      case 'IdPMDUI' :
      case 'SPMDUI' :
      case 'SPServiceInfo' :
      case 'DiscoveryResponse' :
      case 'DiscoHints' :
      case 'IdPKeyInfo' :
      case 'SPKeyInfo' :
      case 'AAKeyInfo' :
      case 'AttributeConsumingService' :
      case 'Organization' :
      case 'ContactPersons' :
        $extraButton = sprintf('<a href="?edit=%s&Entity=%d&oldEntity=%d"><i class="fa fa-pencil-alt"></i></a>',
          $extra, $entityId, $oldEntityId);
        break;
      default :
        $extraButton = '';
    }
    printf('
    %s<h4>
      %s<i id="%s-icon" class="fas fa-chevron-circle-%s"></i>
      %s<a data-toggle="collapse" href="#%s" aria-expanded="%s" aria-controls="%s">%s</a>
      %s%s
    %s</h4>
    %s<div class="%scollapse multi-collapse" id="%s">
    %s  <div class="row">%s',
      $spacer, $spacer, $name, $icon, $spacer, $name, $expanded, $name, $title,
      $spacer, $extraButton, $spacer, $spacer, $show, $name, $spacer, "\n");
    if ($haveSub) {
      printf('%s        <span class="border-right"><div class="col-md-auto"></div></span>%s',$spacer, "\n");
    }
    printf('%s        <div class="col%s">', $spacer, $oldEntityId > 0 ? '-6' : '');
    $this->collapseIcons[] = $name;
  }

  /**
   * Creates a new column below header
   *
   * @param int $step Steps to indent
   *
   * @return void
   */
  protected function showNewCol($step = 0) {
    $spacer = '';
    while ($step > 0 ) {
      $spacer .= self::HTML_SPACER;
      $step--;
    } ?>

        <?=$spacer?></div><!-- end col -->
        <?=$spacer?><div class="col-6"><?php
  }

  /**
   * Shows end of Collapseble header
   *
   * @param string $name Name of header to close
   *
   * @param int $step Steps to indent
   *
   * @return void
   */
  protected function showCollapseEnd($name, $step = 0){
    $spacer = '';
    while ($step > 0 ) {
      $spacer .= self::HTML_SPACER;
      $step--;
    }?>

        <?=$spacer?></div><!-- end col -->
      <?=$spacer?></div><!-- end row -->
    <?=$spacer?></div><!-- end collapse <?=$name?>--><?php
  }

  /**
   * Returns an array of HeadersIcons that should be collapsable
   *
   * @return array
   */
  public function getCollapseIcons() {
    return $this->collapseIcons;
  }
}
