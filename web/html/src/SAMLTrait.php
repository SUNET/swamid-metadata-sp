<?php
namespace metadata;

/**
 * A trait collecting all constants used for SAML
 */
trait SAMLTrait {
  const SAML_ALG_DIGESTMETHOD = 'alg:DigestMethod';
  const SAML_ALG_SIGNATUREMETHOD = 'alg:SignatureMethod';
  const SAML_ALG_SIGNINGMETHOD = 'alg:SigningMethod';
  const SAML_ATTRIBUTE_REMD = 'remd:contactType';
  const SAML_BINDING_HTTP_REDIRECT = 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect';
  const SAML_DS_KEYINFO = 'ds:KeyInfo';
  const SAML_DS_KEYNAME = 'ds:KeyName';
  const SAML_DS_X509DATA = 'ds:X509Data';
  const SAML_EC_COCOV1 = 'http://www.geant.net/uri/dataprotection-code-of-conduct/v1'; # NOSONAR Should be http://
  const SAML_IDPDISC_DISCOVERYRESPONSE = 'idpdisc:DiscoveryResponse';
  const SAML_MD_ARTIFACTRESOLUTIONSERVICE = 'md:ArtifactResolutionService';
  const SAML_MD_ASSERTIONCONSUMERSERVICE = 'md:AssertionConsumerService';
  const SAML_MD_ASSERTIONIDREQUESTSERVICE = 'md:AssertionIDRequestService';
  const SAML_MD_ATTRIBUTEAUTHORITYDESCRIPTOR = 'md:AttributeAuthorityDescriptor';
  const SAML_MD_ATTRIBUTECONSUMINGSERVICE = 'md:AttributeConsumingService';
  const SAML_MD_ATTRIBUTESERVICE = 'md:AttributeService';
  const SAML_MD_COMPANY = 'md:Company';
  const SAML_MD_CONTACTPERSON = 'md:ContactPerson';
  const SAML_MD_EMAILADDRESS = 'md:EmailAddress';
  const SAML_MD_ENCRYPTIONMETHOD = 'md:EncryptionMethod';
  const SAML_MD_ENTITYDESCRIPTOR = 'md:EntityDescriptor';
  const SAML_MD_EXTENSIONS = 'md:Extensions';
  const SAML_MD_GIVENNAME = 'md:GivenName';
  const SAML_MD_IDPSSODESCRIPTOR = 'md:IDPSSODescriptor';
  const SAML_MD_KEYDESCRIPTOR = 'md:KeyDescriptor';
  const SAML_MD_MANAGENAMEIDSERVICE = 'md:ManageNameIDService';
  const SAML_MD_NAMEIDFORMAT = 'md:NameIDFormat';
  const SAML_MD_NAMEIDMAPPINGSERVICE = 'md:NameIDMappingService';
  const SAML_MD_ORGANIZATION = 'md:Organization';
  const SAML_MD_ORGANIZATIONDISPLAYNAME = 'md:OrganizationDisplayName';
  const SAML_MD_ORGANIZATIONNAME = 'md:OrganizationName';
  const SAML_MD_ORGANIZATIONURL = 'md:OrganizationURL';
  const SAML_MD_REQUESTEDATTRIBUTE = 'md:RequestedAttribute';
  const SAML_MD_SERVICEDESCRIPTION = 'md:ServiceDescription';
  const SAML_MD_SERVICENAME = 'md:ServiceName';
  const SAML_MD_SINGLELOGOUTSERVICE = 'md:SingleLogoutService';
  const SAML_MD_SINGLESIGNONSERVICE = 'md:SingleSignOnService';
  const SAML_MD_SPSSODESCRIPTOR = 'md:SPSSODescriptor';
  const SAML_MD_SURNAME = 'md:SurName';
  const SAML_MD_TELEPHONENUMBER = 'md:TelephoneNumber';
  const SAML_MDATTR_ENTITYATTRIBUTES = 'mdattr:EntityAttributes';
  const SAML_MDRPI_REGISTRATIONINFO = 'mdrpi:RegistrationInfo';
  const SAML_MDUI_DESCRIPTION = 'mdui:Description';
  const SAML_MDUI_DISCOHINTS = 'mdui:DiscoHints';
  const SAML_MDUI_DISPLAYNAME = 'mdui:DisplayName';
  const SAML_MDUI_DOMAINHINT = 'mdui:DomainHint';
  const SAML_MDUI_GEOLOCATIONHINT = 'mdui:GeolocationHint';
  const SAML_MDUI_IPHINT = 'mdui:IPHint';
  const SAML_MDUI_INFORMATIONURL = 'mdui:InformationURL';
  const SAML_MDUI_KEYWORDS = 'mdui:Keywords';
  const SAML_MDUI_LOGO = 'mdui:Logo';
  const SAML_MDUI_PRIVACYSTATEMENTURL = 'mdui:PrivacyStatementURL';
  const SAML_MDUI_UIINFO = 'mdui:UIInfo';
  const SAML_PROTOCOL_SAML1 = 'urn:oasis:names:tc:SAML:1.0:protocol';
  const SAML_PROTOCOL_SAML11 = 'urn:oasis:names:tc:SAML:1.1:protocol';
  const SAML_PROTOCOL_SAML2 = 'urn:oasis:names:tc:SAML:2.0:protocol';
  const SAML_PROTOCOL_SHIB = 'urn:mace:shibboleth:1.0';
  const SAML_PSC_REQUESTEDPRINCIPALSELECTION = 'psc:RequestedPrincipalSelection';
  const SAML_SHIBMD_SCOPE = 'shibmd:Scope';
  const SAML_SAMLA_ATTRIBUTE = 'samla:Attribute';
  const SAML_SAMLA_ATTRIBUTEVALUE = 'samla:AttributeValue';

  const SAMLNF_URI = 'urn:oasis:names:tc:SAML:2.0:attrname-format:uri';
}
