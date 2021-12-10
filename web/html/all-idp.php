<?php
include 'include/Html.php';
$html = new HTML();
$html->showHeaders('Metadata SWAMID - eduGAIN - IdP:s');
print "         <a href=\"./\">Alla i SWAMID</a>  | <a href=\".?showIdP\">IdP i SWAMID</a> | <a href=\".?showSP\">SP i SWAMID</a> | <b>IdP via interfederation</b> | <a href=\"all-sp.php\">SP via interfederation</a>\n";
echo <<<EOF
    <table class="table table-striped table-bordered">
      <tr><th>EntityID</th><th>Organization</th><th>Contacts</th><th>Scopes</th><th>Entity category support</th><th>Assurance Certification</th><th>Registration Authority</th></tr>
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
				printf ('%s<br>', $child->nodeName);
		}
	}
}

function checkEntity($xml) {
	$show = false;
	$ECS = '';
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
										case 'http://macedir.org/entity-category-support' :
											foreach ($entAttrChild->childNodes as $attrChild) {
												if (nodeName($attrChild->nodeName) == 'AttributeValue')
													$ECS .= $attrChild->nodeValue . ' ';
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
			case 'IDPSSODescriptor' :
				$show = true;
		}
		
	}

	if ( $show && $hideSwamid) {
		$entityID = $xml->getAttribute('entityID');
		$scope = '';
		$orgURL = '';
		$orgName = '';
		$contacts = array();
		foreach ($xml->childNodes as $child) {
			switch (nodeName($child->nodeName)) {
				case 'Extensions' :
					break;
				case 'IDPSSODescriptor' :
					foreach ($child->childNodes as $SSOChild) {
						if (nodeName($SSOChild->nodeName) == 'Extensions') {
							foreach ($SSOChild->childNodes as $extChild) {
								if (nodeName($extChild->nodeName) == 'Scope')
									$scope .= $extChild->nodeValue . ' ';
							}
						}
					}
					break;
				case 'SPSSODescriptor' :
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
		printf ('<tr><td>%s</td><td><a href="%s">%s</a></td><td>', $entityID, $orgURL, $orgName);
		foreach ($contacts as $contact) {
			printf ('<a href="%s">%s<a><br>', $contact['email'], $contact['type']);
		}
		printf ('</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>%s', $scope, $ECS, $AC, $registrationAuthority, "\n");

	}
}

function nodeName($str) {
	$array = explode(':', $str);
	if (isset($array[1]))
		return $array[1];
	else
		return $array[0];
}