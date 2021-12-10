<?php
include 'include/Html.php';
$html = new HTML();
$html->showHeaders('Metadata SWAMID - eduGAIN - SP:s');
print "         <a href=\"./\">Alla i SWAMID</a>  | <a href=\".?showIdP\">IdP i SWAMID</a> | <a href=\".?showSP\">SP i SWAMID</a> | <a href=\"all-idp.php\">IdP via interfederation</a> | <b>SP via interfederation</b>\n";
echo <<<EOF
    <table class="table table-striped table-bordered">
      <tr><th>EntityID</th><th>Service Name</th><th>Organization</th><th>Contacts</th><th>Entity Categories</td><th>Assurance Certification</th><th>Registration Authority</th></tr>
EOF;

$xml = new DOMDocument;
$xml->preserveWhiteSpace = false;
$xml->formatOutput = true;
$xml->load('/opt/swamid-metadata/swamid-2.0.xml');
$xml->encoding = 'UTF-8';

checkEntities($xml);
$html->showFooter(array());

function checkEntities($xml) {
	foreach ($xml->childNodes as $child) {
		switch (nodeName($child->nodeName)) {
			case 'EntitiesDescriptor' :
				checkEntities($child);
				break;
			case 'EntityDescriptor' :
				checkEntity($child);
				break;
			case 'Signature' :
			case 'Extensions' :
			case '#comment' :
				break;
			default:
				printf ('Base : %s<br>', $child->nodeName);
		}
	}
}

function checkEntity($xml) {
	$show = false;
	$EC = '';
	$AC = '';
	foreach ($xml->childNodes as $child) {
		switch (nodeName($child->nodeName)) {
			case 'Extensions' :
				foreach ($child->childNodes as $extChild) {
					switch (nodeName($extChild->nodeName)) {
						case 'RegistrationInfo' :
							$registrationAuthority = $extChild->getAttribute('registrationAuthority');
							$hideSwamid = ($registrationAuthority == 'http://www.swamid.se/') ? false : true;
							$hideSwamid = ($registrationAuthority == 'http://www.swamid.se/loop') ? false : $hideSwamid;
							break;
						case 'EntityAttributes' : 
							foreach ($extChild->childNodes as $entAttrChild) {
								if (nodeName($entAttrChild->nodeName) == 'Attribute') {
									switch ($entAttrChild->getAttribute('Name')){
										case 'http://macedir.org/entity-category' :
											foreach ($entAttrChild->childNodes as $attrChild) {
												if (nodeName($attrChild->nodeName) == 'AttributeValue')
													$EC .= $attrChild->nodeValue . ' ';
											}
											break;
										case 'urn:oasis:names:tc:SAML:attribute:assurance-certification' :
											foreach ($entAttrChild->childNodes as $attrChild) {
												if (nodeName($attrChild->nodeName) == 'AttributeValue')
													$AC .= $attrChild->nodeValue . ' ';
											}
											break;
									}
								}
							}
					}
				}
				break;
			case 'SPSSODescriptor' :
				$show = true;
		}
		
	}

	if ( $show && $hideSwamid) {
		$entityID = $xml->getAttribute('entityID');
		$displayName = '';
		$serviceName = '';
		$orgURL = '';
		$orgName = '';
		$contacts = array();
		foreach ($xml->childNodes as $child) {
			switch (nodeName($child->nodeName)) {
				case 'Extensions' :
				case 'IDPSSODescriptor' :
					break;
				case 'SPSSODescriptor' :
						foreach ($child->childNodes as $SSOChild) {
						switch (nodeName($SSOChild->nodeName)) {
							case 'Extensions' :
								foreach ($SSOChild->childNodes as $extChild) {
									if (nodeName($extChild->nodeName) == 'UIInfo') {
										foreach ($extChild->childNodes as $UUIChild) {
											if (nodeName($UUIChild->nodeName) == 'DisplayName') {
												if ($displayName == '')
													$displayName = $UUIChild->nodeValue;
												elseif ($UUIChild->getAttribute('xml:lang') == 'en')
													$displayName = $UUIChild->nodeValue;
											}
										}
									}
								}
								break;
							case 'AttributeConsumingService' :
								foreach ($SSOChild->childNodes as $acsChild) {
									if (nodeName($acsChild->nodeName) == 'ServiceName') {
										if ($serviceName == '')
											$serviceName = $acsChild->nodeValue;
										elseif ($acsChild->getAttribute('xml:lang') == 'en')
											$serviceName = $acsChild->nodeValue;
									}
								}
								break;
						}
					}	
					break;
				case 'AttributeAuthorityDescriptor' :
					break;
				case 'Organization' :
					foreach ($child->childNodes as $orgChild) {
						if (nodeName($orgChild->nodeName) == 'OrganizationURL')
							if ($orgURL == '') 
								$orgURL = $orgChild->nodeValue;
							elseif ($orgChild->getAttribute('xml:lang') == 'en')
								$orgURL = $orgChild->nodeValue;
						if (nodeName($orgChild->nodeName) == 'OrganizationDisplayName')
							if ($orgName == '')
								$orgName = $orgChild->nodeValue;
							elseif ($orgChild->getAttribute('xml:lang') == 'en')
								$orgName = $orgChild->nodeValue;
					}
					break;
				case 'ContactPerson' :
					$email = '';
					foreach ($child->childNodes as $contactChild) {
						if (nodeName($contactChild->nodeName) == 'EmailAddress')
							$email = $contactChild->nodeValue;
					}
					array_push($contacts, array ('type' => $child->getAttribute('contactType'), 'email' => $email));
					break;
				case '#comment' :
					break;
				default:
					printf ('%s<br>', $child->nodeName);
			}
		}
		printf ('<tr><td>%s</td><td>%s<br>%s</td><td><a href="%s">%s</a></td><td>', $entityID, $displayName, $serviceName, $orgURL, $orgName);
		foreach ($contacts as $contact) {
			printf ('<a href="%s">%s<a><br>', $contact['email'], $contact['type']);
		}
		printf ('</td><td>%s</td><td>%s</td><td>%s</td></tr>%s', $EC, $AC, $registrationAuthority, "\n");
	}
}

function nodeName($str) {
	$array = explode(':', $str);
	if (isset($array[1]))
		return $array[1];
	else
		return $array[0];
}