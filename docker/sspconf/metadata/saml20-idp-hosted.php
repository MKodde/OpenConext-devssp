<?php

// MANAGED BY ANSIBLE

// This is the IdP that is hosted by this instance.
// It is used as a "remote IdP" by the stepup gateway
// See: authsources.php for a list of accounts that are defined at this IdP

/**
 * SAML 2.0 IdP configuration for simplesaml.
 *
 * See: https://rnd.feide.no/content/idp-hosted-metadata-reference
 */

$metadata['https://ssp.dev.openconext.local/simplesaml/saml2/idp/metadata.php'] = array(
    /*
     * The hostname of the server (VHOST) that will use this SAML entity.
     *
     * Can be '__DEFAULT__', to use this entry by default.
     */
    'host' => '__DEFAULT__',

    'signature.algorithm' => 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256',

    /* X.509 key and certificate. Relative to the cert directory. */
    'privatekey' => 'idp.key',
    'certificate' => 'idp.crt',

    /*
     * Authentication source to use. Must be one that is configured in
     * 'config/authsources.php'.
     */
    'auth' => 'example-userpass',


	//'attributes.NameFormat' => 'urn:oasis:names:tc:SAML:2.0:attrname-format:uri',
	//'attributes.NameFormat' => 'urn:oasis:names:tc:SAML:2.0:attrname-format:basic',
	'attributes.NameFormat' => 'urn:oasis:names:tc:SAML:2.0:attrname-format:unspecified',

	// Sign logout request and logout responses 
	'redirect.sign' => TRUE,

	// Require validate signature on requests
	'redirect.validate' => FALSE,

	// Sign response
	'saml20.sign.response' => FALSE,

	// Sign assertion
	'saml20.sign.assertion' => TRUE,

	// No artifact binding support
	'saml20.sendartifact' => FALSE,

    //'signature.algorithm' => 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256',

    'attributes.NameFormat' => 'urn:oasis:names:tc:SAML:2.0:attrname-format:uri',

    // Use (or 'select') an unspecified NameID. The NameID is generated in the authproc below.
    // This is the NameID that will be but in the Subject of the SAML Assertion
    'NameIDFormat' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified',

    // Authproc to make the output of this IdP sufficiently like OpenConext to allow
    // OpenConext Stepup to work.
    // The configured authsource must provide the required attributes.
    // Required is an "NameID" attribute. This will be used both in the Subject and in the "eduPersonTargetedID"
    //
    'authproc' => array(

        // Generate an unspecified NameID for use by this IdP
        // Note that this NameID won't be used until it is "selected" in the saml20-idp-hosted.php by adding:
        //     'NameIDFormat' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified',
        //
        // Use the value of the "NameID" attribute from the authsource as value for the NameID
        1 => array(
            'class' => 'saml:AttributeNameID',
            'attribute' => 'NameID',
            'identifyingAttribute' => 'NameID',
            'Format' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified',

            // Don't add NameQualifier and SPNameQualifier attributes to the generated NameID
            'NameQualifier' => FALSE,
            'SPNameQualifier' => FALSE,
        ),

        // Copy the NameID to the eduPersonTargetedID attribute
        // Note that this will generate a eduPersonTargetedID with a PERSISTENT targeted NameID
        //
        // This will not work when the RA and Selfservice are behind the Stepup Gateway
        // Using this might be useful to test the Stepup Gateway NameID passing behaviour
        //
        // When the Stepup Selfservice and RA are behind the Stepup Gateway the NameID in the Subject
        // and the NameID in the eduPersonTargetedID must match so that the Stepup Gatway will pass it to
        // the SelfService and RA applications. An eduPersonTargetedID with an UNSPECIFIED NameID
        // (For OpenConext urn:collab:person:etc..) is non standard.
        //
        // If you enable this (2) rule you will want to disable the custom (3) rule below as it overwrites the
        // eduPersonTargetedID generated by this rule.
        2 => array(
            'class' => 'core:TargetedID', // Generate a eduPersonTargetedID attribute
            'attribute' => 'NameID',
            'identifyingAttribute' => 'NameID',
            'nameId' => TRUE,   // Use the "Nested" NameID format
            // Don't add NameQualifier and SPNameQualifier attributes to the generated NameID
            'NameQualifier' => FALSE,
            'SPNameQualifier' => FALSE,
        ),

        // Create an eduPersonTargetedID attribute with an unspecified NameID with the value
        // of the "NameID" attribute from the authsource.
        /*
        3 => array(
            'class' => 'core:PHP',
            'code' =>
                '
                $nameId = new \SAML2\XML\saml\NameID();
                $nameId->value = $attributes["NameID"][0];  // Use value of "NameID" attribute
                $nameId->Format = \SAML2\Constants::NAMEID_UNSPECIFIED; // Unspecified NameID
                //$nameId->NameQualifier = "...";
                //$nameId->SPNameQualifier = "...";
                $doc = \SAML2\DOMDocumentFactory::create();
                $root = $doc->createElement("root");
                $doc->appendChild($root);
                $nameId->toXML($root);
                $eduPersonTargetedID = $doc->saveXML($root->firstChild);
                $attributes["eduPersonTargetedID"] = array($eduPersonTargetedID);
                ',
        ),
        */
        // Remove the NameID attribute to prevent any confusion, it was only there to specify the NameID
        // to use in the Subject and eduPersonTargetedID attribute
        4 => array(
            'class' => 'core:AttributeAlter',
            'subject' => 'NameID',
            'pattern' => '/.*/',
            '%remove',
        ),

        // Convert "short" atribute names (uid, mail, eduPersonTargetedID, ...) to their long urn:mace...
        // equivalent
        10 => array(
            'class' => 'core:AttributeMap',
            'Openconext_short_to_urn'
        ),

    ),

    // Required because the eduPersonTargetedID is a "complex" attribute and not a simple string value.
    'attributeencodings' => array(
        'urn:mace:dir:attribute-def:eduPersonTargetedID' => 'raw'
    ),

);

if ( isset($_COOKIE['testcookie']) ) {
    $metadata['https://ssp.dev.openconext.local/simplesaml/saml2/idp/metadata.php']['publickey'] = '/vagrant/deploy/tests/behat/fixtures/test_public_key.crt';
    $metadata['https://ssp.dev.openconext.local/simplesaml/saml2/idp/metadata.php']['privatekey'] = '/vagrant/deploy/tests/behat/fixtures/test_private_key.key';
}
