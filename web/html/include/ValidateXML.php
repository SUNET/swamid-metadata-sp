<?php
Class ValidateXML {
	# Setup
	function __construct($xml) {
		$this->error = 'No XML loaded';
		$this->status = false;
		$this->doc = new DOMDocument('1.0', 'UTF-8');
		libxml_use_internal_errors(true);
		set_error_handler(array($this, 'checkDOMError'));
		if ($this->doc->loadXML($xml)) {
			$this->error = '';
			$this->status = true;
		} else {
			$this->status = false;
		}
		restore_error_handler();
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

	private function libxml_display_error($error){
		$return = "<br/>\n";
		switch ($error->level) {
			case LIBXML_ERR_WARNING:
				$return .= "<b>Warning </b>: ";
				break;
			case LIBXML_ERR_ERROR:
				$return .= "<b>Error </b>: ";
				break;
			case LIBXML_ERR_FATAL:
				$return .= "<b>Fatal Error </b>: ";
				break;
		}
		$return .= trim($error->message);
		$return .= " on line <b>$error->line</b>\n";

		return $return;
	}

	private function libxml_display_errors() {
		$errors = libxml_get_errors();
		foreach ($errors as $error) {
			$this->error = $this->libxml_display_error($error);
		}
		libxml_clear_errors();
	}

	public function validateXML($schema) {
		if ($this->status) {
			set_error_handler(array($this, 'checkDOMError'));
			 if (! $this->status = $this->doc->schemaValidate($schema)) {
				$this->libxml_display_errors();
			 }
			restore_error_handler();
		}
		return $this->status;
	}

	public function getStatus() {
		return $this->status;
	}

	public function getError() {
		return $this->error;
	}
}