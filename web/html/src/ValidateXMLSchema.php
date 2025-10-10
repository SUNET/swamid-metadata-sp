<?php
namespace metadata;

use DOMDocument;

/**
 * Class to Validate XML Schemas
 */
class ValidateXMLSchema {
  # Setup
  private string $error = 'No XML loaded';
  private bool $status = false;
  private DOMDocument $doc;

  /**
   * Setup the class
   *
   * @param string $xml XML to schemavalidate
   *
   * @return void
   */
  public function __construct($xml) {
    $this->doc = new DOMDocument('1.0', 'UTF-8');
    libxml_use_internal_errors(true);
    set_error_handler(array($this, 'checkDOMError'));
    if ($this->doc->loadXML($xml)) {
      $this->error = '';
      $this->status = true;
    }
    restore_error_handler();
  }

  /**
   * error-handler to catch errors while loading XML
   *
   * @param int $number numeric error-code not used in this function.
   *
   * @param string $error Error as a string
   *
   * @return void
   */
  public function checkDOMError ($number, $error){ #NOSONAR $number is in call!!!
    $errorParts = explode(' ', $error);
    if ($errorParts[0] == 'DOMDocument::load():') {
      $this->error = preg_replace('/ in .*, line:/', ' line:', substr($error, 21));
      $this->status = false;
    } else {
      $this->error = $error;
      $this->status = false;
    }
  }

  /**
   * Parse error from libXML into more inforamtive text
   *
   * @param string $error Error object
   *
   * @return string Formated string with error information
   */
  private function libxmlDisplayError($error){
    $returnString = "<br/>\n";
    switch ($error->level) {
      case LIBXML_ERR_WARNING:
        $returnString .= '<b>Warning </b>: ';
        break;
      case LIBXML_ERR_ERROR:
        $returnString .= '<b>Error </b>: ';
        break;
      case LIBXML_ERR_FATAL:
        $returnString .= '<b>Fatal Error </b>: ';
        break;
      default :
        $returnString .= '<b> Unkown Error </b>: ';
    }
    $returnString .= htmlspecialchars(trim($error->message));
    $returnString .= " on line <b>$error->line</b>\n";

    return $returnString;
  }

  /**
   * Parse errors from libXML
   *
   * Parse errors ans store result in $this->error
   *
   * @return void
   */
  private function libxmlDisplayErrors() {
    $errors = libxml_get_errors();
    foreach ($errors as $error) {
      $this->error .= $this->libxmlDisplayError($error);
    }
    libxml_clear_errors();
  }

  /**
   * Schema validates an XML document
   *
   * @param string xsd files used for validation
   *
   * @return bool
   */
  public function validateSchema($schema) {
    if ($this->status) {
      set_error_handler(array($this, 'checkDOMError'));
       if (! $this->status = $this->doc->schemaValidate($schema)) {
        $this->libxmlDisplayErrors();
       }
      restore_error_handler();
    }
    return $this->status;
  }

  /**
   * Get Errors
   *
   * @return string
   */
  public function getError() {
    return $this->error;
  }
}
