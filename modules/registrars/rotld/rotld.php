<?php


/**
 * WHMCS Registrar Module for ROTLD
 *
 * Registrar Modules allow you to create modules that allow for domain
 * registration, management, transfers, and other functionality within
 * WHMCS.
 */
if (!defined("WHMCS")) {
	die("This file cannot be accessed directly");
}

// Use some specific namespaces
use WHMCS\Domains\DomainLookup\ResultsList;
use WHMCS\Domains\DomainLookup\SearchResult;
use WHMCS\Module\Registrar\rotld\ApiClient;


function rotld_MetaData() {
/**
 * function to define module related metadata
 *
 * the function provides module info, including the display name and
 * the API version.
 *
 * @return array
 *
 */
    return array (
		'DisplayName'	=> 'ROTLD',
		'Description'	=> 'Description',
		'APIVersion'	=> '1.1',
	);
}

function rotld_getConfigArray() {
/**
 * function to define the registrar configuration options.
 *
 * the values returned here will define what configuration options
 * we store for the module. These values are made available to
 * each module function.
 *
 * @see https://developers.whmcs.com/domain-registrars/config-options/
 *
 * @return array
 */
	return [
		// Friendly display name for the module
		'FriendlyName'	=> [
			'Type'		=> 'System',
			'Value'		=> 'WHMCS module for ROTLD',
		],
		// Description
		'Description'   => [
			'Type'      => 'System',
			'Value'     => 'WHMCS registrar module for managing Romanian domains; developed under GPLv3 license by Hangar Hosting - https://hangar.hosting',
		],
		// Your registrar URL, as registered at ROTLD
		'RegistrarDomain' => [
			'FriendlyName' => 'Registrar domain',
			'Type' => 'text',
			'Size' => '20',
			'Default' => 'my.whmcs.domain',
			'Description' => 'Your registrar domain (DO NOT include https://)',
		],
		// Your Registrar ID for the live environment
		'APIUsername' => [
			'FriendlyName' => 'Live username (RegID)',
			'Type' => 'text',
			'Size' => '20',
			'Default' => 'myRoTLDLiveRegID',
			'Description' => 'RegID for LIVE environment (provided by ROTLD)',
		],
		// Your registrar password for the live environment
		'APIKey' => [
			'FriendlyName' => 'Live password',
			'Type' => 'password',
			'Size' => '20',
			'Default' => '',
			'Description' => 'RegID password for LIVE environment (provided by ROTLD)',
		],
		// Your registrar URL for the live environment
		'APIURL' => [
			'FriendlyName' => 'Live API URL',
			'Type' => 'text',
			'Size' => '20',
			'Default' => 'https://rest2.rotld.ro:6080',
			'Description' => 'Live URL:port for RoTLD API (do not change unless you know what you are doing)',
		],
		// Your Registrar ID for the test environment
		'APITestUsername' => [
			'FriendlyName' => 'Test username (RegID)',
			'Type' => 'text',
			'Size' => '22',
			'Default' => 'myRoTLDTestRegID',
			'Description' => 'RegID for TEST environment (provided by ROTLD)',
		],
		// Your registrar password for the test environment
		'APITestKey' => [
			'FriendlyName' => 'Test password',
			'Type' => 'password',
			'Size' => '22',
			'Default' => '',
			'Description' => 'RegID password for test environment (provided by ROTLD)',
		],
		// Your registrar URL for the test environment
		'APITestURL' => [
			'FriendlyName' => 'Test API URL',
			'Type' => 'text',
			'Size' => '22',
			'Default' => 'https://rest2-test.rotld.ro:6080',
			'Description' => 'Test URL:port for RoTLD API (do not change unless you know what you are doing)',
		],
		// enable or disable test mode
		'TestMode' => [
			'FriendlyName' => 'Test Mode',
			'Type' => 'yesno',
			'Description' => 'Tick to enable test mode',
		],
    ];
}


/** 
 * Operational functions
 * @see https://developers.whmcs.com/domain-registrars/function-index/
 */

function rotld_RegisterDomain($params) {
    /**
     * NOT yet implemented in this module
     *
     * Register a domain.
     *
     * Attempt to register a domain with the domain registrar.
     *
     * This is triggered when the following events occur:
     * * Payment received for a domain registration order
     * * When a pending domain registration order is accepted
     * * Upon manual request by an admin user
     *
     * @param array $params common module parameters
     *
     * @see https://developers.whmcs.com/domain-registrars/module-parameters/
     *
     * @return array
     */

    // user defined configuration values
    $userIdentifier = $params['APIUsername'];
    $apiKey = $params['APIKey'];
    $testMode = $params['TestMode'];

    // registration parameters
    $sld = $params['sld'];
    $tld = $params['tld'];
    $registrationPeriod = $params['regperiod'];

    /**
     * Nameservers.
     *
     * If purchased with web hosting, values will be taken from the
     * assigned web hosting server. Otherwise uses the values specified
     * during the order process.
     */
    $nameserver1 = $params['ns1'];
    $nameserver2 = $params['ns2'];
    $nameserver3 = $params['ns3'];
    $nameserver4 = $params['ns4'];
    $nameserver5 = $params['ns5'];

    // registrant information
    $firstName = $params["firstname"];
    $lastName = $params["lastname"];
    $fullName = $params["fullname"]; // First name and last name combined
    $companyName = $params["companyname"];
    $email = $params["email"];
    $address1 = $params["address1"];
    $address2 = $params["address2"];
    $city = $params["city"];
    $state = $params["state"]; // eg. TX
    $stateFullName = $params["fullstate"]; // eg. Texas
    $postcode = $params["postcode"]; // Postcode/Zip code
    $countryCode = $params["countrycode"]; // eg. GB
    $countryName = $params["countryname"]; // eg. United Kingdom
    $phoneNumber = $params["phonenumber"]; // Phone number as the user provided it
    $phoneCountryCode = $params["phonecc"]; // Country code determined based on country
    $phoneNumberFormatted = $params["fullphonenumber"]; // Format: +CC.xxxxxxxxxxxx

    /**
     * Admin contact information.
     *
     * Defaults to the same as the client information. Can be configured
     * to use the web hosts details if the `Use Clients Details` option
     * is disabled in Setup > General Settings > Domains.
     */
    $adminFirstName = $params["adminfirstname"];
    $adminLastName = $params["adminlastname"];
    $adminCompanyName = $params["admincompanyname"];
    $adminEmail = $params["adminemail"];
    $adminAddress1 = $params["adminaddress1"];
    $adminAddress2 = $params["adminaddress2"];
    $adminCity = $params["admincity"];
    $adminState = $params["adminstate"]; // eg. TX
    $adminStateFull = $params["adminfullstate"]; // eg. Texas
    $adminPostcode = $params["adminpostcode"]; // Postcode/Zip code
    $adminCountry = $params["admincountry"]; // eg. GB
    $adminPhoneNumber = $params["adminphonenumber"]; // Phone number as the user provided it
    $adminPhoneNumberFormatted = $params["adminfullphonenumber"]; // Format: +CC.xxxxxxxxxxxx

    // domain addon purchase status
    $enableDnsManagement = (bool) $params['dnsmanagement'];
    $enableEmailForwarding = (bool) $params['emailforwarding'];
    $enableIdProtection = (bool) $params['idprotection'];

    /**
     * Premium domain parameters.
     *
     * Premium domains enabled informs you if the admin user has enabled
     * the selling of premium domain names. If this domain is a premium name,
     * `premiumCost` will contain the cost price retrieved at the time of
     * the order being placed. The premium order should only be processed
     * if the cost price now matches the previously fetched amount.
     */
    $premiumDomainsEnabled = (bool) $params['premiumEnabled'];
    $premiumDomainsCost = $params['premiumCost'];

    // Build post data
    $postfields = array(
        'username' => $userIdentifier,
        'password' => $apiKey,
        'testmode' => $testMode,
        'domain' => $sld . '.' . $tld,
        'years' => $registrationPeriod,
        'nameservers' => array(
            'ns1' => $nameserver1,
            'ns2' => $nameserver2,
            'ns3' => $nameserver3,
            'ns4' => $nameserver4,
            'ns5' => $nameserver5,
        ),
        'contacts' => array(
            'registrant' => array(
                'firstname' => $firstName,
                'lastname' => $lastName,
                'companyname' => $companyName,
                'email' => $email,
                'address1' => $address1,
                'address2' => $address2,
                'city' => $city,
                'state' => $state,
                'zipcode' => $postcode,
                'country' => $countryCode,
                'phonenumber' => $phoneNumberFormatted,
            ),
            'tech' => array(
                'firstname' => $adminFirstName,
                'lastname' => $adminLastName,
                'companyname' => $adminCompanyName,
                'email' => $adminEmail,
                'address1' => $adminAddress1,
                'address2' => $adminAddress2,
                'city' => $adminCity,
                'state' => $adminState,
                'zipcode' => $adminPostcode,
                'country' => $adminCountry,
                'phonenumber' => $adminPhoneNumberFormatted,
            ),
        ),
        'dnsmanagement' => $enableDnsManagement,
        'emailforwarding' => $enableEmailForwarding,
        'idprotection' => $enableIdProtection,
    );

    if ($premiumDomainsEnabled && $premiumDomainsCost) {
        $postfields['accepted_premium_cost'] = $premiumDomainsCost;
    }

    try {
        $api = new ApiClient();
        $api->call('Register', $postfields);

        return array(
            'success' => true,
        );

    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}

function rotld_TransferDomain($params) {
    /**
     * NOT yet implemented in this module
     *
     * Initiate domain transfer.
     *
     * Attempt to create a domain transfer request for a given domain.
     *
     * This is triggered when the following events occur:
     * * Payment received for a domain transfer order
     * * When a pending domain transfer order is accepted
     * * Upon manual request by an admin user
     *
     * @param array $params common module parameters
     *
     * @see https://developers.whmcs.com/domain-registrars/module-parameters/
     *
     * @return array
     */

    // user defined configuration values
    $userIdentifier = $params['APIUsername'];
    $apiKey = $params['APIKey'];
    $testMode = $params['TestMode'];
    $accountMode = $params['AccountMode'];
    $emailPreference = $params['EmailPreference'];

    // registration parameters
    $sld = $params['sld'];
    $tld = $params['tld'];
    $registrationPeriod = $params['regperiod'];
    $eppCode = $params['eppcode'];

    /**
     * Nameservers.
     *
     * If purchased with web hosting, values will be taken from the
     * assigned web hosting server. Otherwise uses the values specified
     * during the order process.
     */
    $nameserver1 = $params['ns1'];
    $nameserver2 = $params['ns2'];
    $nameserver3 = $params['ns3'];
    $nameserver4 = $params['ns4'];
    $nameserver5 = $params['ns5'];

    // registrant information
    $firstName = $params["firstname"];
    $lastName = $params["lastname"];
    $fullName = $params["fullname"]; // First name and last name combined
    $companyName = $params["companyname"];
    $email = $params["email"];
    $address1 = $params["address1"];
    $address2 = $params["address2"];
    $city = $params["city"];
    $state = $params["state"]; // eg. TX
    $stateFullName = $params["fullstate"]; // eg. Texas
    $postcode = $params["postcode"]; // Postcode/Zip code
    $countryCode = $params["countrycode"]; // eg. GB
    $countryName = $params["countryname"]; // eg. United Kingdom
    $phoneNumber = $params["phonenumber"]; // Phone number as the user provided it
    $phoneCountryCode = $params["phonecc"]; // Country code determined based on country
    $phoneNumberFormatted = $params["fullphonenumber"]; // Format: +CC.xxxxxxxxxxxx

    /**
     * Admin contact information.
     *
     * Defaults to the same as the client information. Can be configured
     * to use the web hosts details if the `Use Clients Details` option
     * is disabled in Setup > General Settings > Domains.
     */
    $adminFirstName = $params["adminfirstname"];
    $adminLastName = $params["adminlastname"];
    $adminCompanyName = $params["admincompanyname"];
    $adminEmail = $params["adminemail"];
    $adminAddress1 = $params["adminaddress1"];
    $adminAddress2 = $params["adminaddress2"];
    $adminCity = $params["admincity"];
    $adminState = $params["adminstate"]; // eg. TX
    $adminStateFull = $params["adminfullstate"]; // eg. Texas
    $adminPostcode = $params["adminpostcode"]; // Postcode/Zip code
    $adminCountry = $params["admincountry"]; // eg. GB
    $adminPhoneNumber = $params["adminphonenumber"]; // Phone number as the user provided it
    $adminPhoneNumberFormatted = $params["adminfullphonenumber"]; // Format: +CC.xxxxxxxxxxxx

    // domain addon purchase status
    $enableDnsManagement = (bool) $params['dnsmanagement'];
    $enableEmailForwarding = (bool) $params['emailforwarding'];
    $enableIdProtection = (bool) $params['idprotection'];

    /**
     * Premium domain parameters.
     *
     * Premium domains enabled informs you if the admin user has enabled
     * the selling of premium domain names. If this domain is a premium name,
     * `premiumCost` will contain the cost price retrieved at the time of
     * the order being placed. The premium order should only be processed
     * if the cost price now matches that previously fetched amount.
     */
    $premiumDomainsEnabled = (bool) $params['premiumEnabled'];
    $premiumDomainsCost = $params['premiumCost'];

    // Build post data
    $postfields = array(
        'username' => $userIdentifier,
        'password' => $apiKey,
        'testmode' => $testMode,
        'domain' => $sld . '.' . $tld,
        'eppcode' => $eppCode,
        'nameservers' => array(
            'ns1' => $nameserver1,
            'ns2' => $nameserver2,
            'ns3' => $nameserver3,
            'ns4' => $nameserver4,
            'ns5' => $nameserver5,
        ),
        'years' => $registrationPeriod,
        'contacts' => array(
            'registrant' => array(
                'firstname' => $firstName,
                'lastname' => $lastName,
                'companyname' => $companyName,
                'email' => $email,
                'address1' => $address1,
                'address2' => $address2,
                'city' => $city,
                'state' => $state,
                'zipcode' => $postcode,
                'country' => $countryCode,
                'phonenumber' => $phoneNumberFormatted,
            ),
            'tech' => array(
                'firstname' => $adminFirstName,
                'lastname' => $adminLastName,
                'companyname' => $adminCompanyName,
                'email' => $adminEmail,
                'address1' => $adminAddress1,
                'address2' => $adminAddress2,
                'city' => $adminCity,
                'state' => $adminState,
                'zipcode' => $adminPostcode,
                'country' => $adminCountry,
                'phonenumber' => $adminPhoneNumberFormatted,
            ),
        ),
        'dnsmanagement' => $enableDnsManagement,
        'emailforwarding' => $enableEmailForwarding,
        'idprotection' => $enableIdProtection,
    );

    try {
        $api = new ApiClient();
        $api->call('Transfer', $postfields);

        return array(
            'success' => true,
        );

    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}

function rotld_RenewDomain($params) {
    /** OK
     * NOT yet implemented in this module
     *
     *
     * Renew a domain.
     *
     * Attempt to renew/extend a domain for a given number of years.
     *
     * This is triggered when the following events occur:
     * * Payment received for a domain renewal order
     * * When a pending domain renewal order is accepted
     * * Upon manual request by an admin user
     *
     * @param array $params common module parameters
     *
     * @see https://developers.whmcs.com/domain-registrars/module-parameters/
     *
     * @return array
     */

    // user defined configuration values
    $userHost			= $params['RegistrarDomain'];

    // set domain info
    $sld 				= $params['sld'];
    $tld 				= $params['tld'];
    $registrationPeriod = $params['regperiod'];


    // by default set LIVE user configuration values
    $userIdentifier		= $params['APIUsername'];
    $apiKey				= $params['APIKey'];
    $apiURL				= $params['APIURL'];
    $testMode			= $params['TestMode'];

    if ($testMode == 'on') {
        $userIdentifier	= $params['APITestUsername'];
        $apiKey			= $params['APITestKey'];
        $apiURL			= $params['APITestURL'];
    }


    // build post data
    $postfields = array(
        'hostname'			=> $userHost,
        'username'			=> $userIdentifier,
        'password'			=> $apiKey,
        'apiurl'			=> $apiURL,
        'domain'			=> $sld.'.'.$tld,
        'period'			=> $registrationPeriod,
    );

    try {
        $api = new ApiClient();
        $api->call('Renew', $postfields);

        return array(
            'success' => true,
        );

    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}

function rotld_GetDomainInformation($params) {
/**
 * not implemented
 */
}

function rotld_GetEmailForwarding($params) {
/**
 * NOT implemented
 */
}

function rotld_SaveEmailForwarding($params) {
/**
 * NOT implemented
 *
 */
}

function rotld_GetNameservers($params) {
    /**
     * Fetch current nameservers.
     *
     * This function should return an array of nameservers for a given domain.
     *
     * @param array $params common module parameters
     *
     * @see https://developers.whmcs.com/domain-registrars/module-parameters/
     *
     * @return array
     */

    // by default set LIVE user configuration values
    $userIdentifier		= $params['APIUsername'];
    $apiKey				= $params['APIKey'];
    $apiURL				= $params['APIURL'];
    $testMode			= $params['TestMode'];

    if ($testMode == 'on') {
        $userIdentifier	= $params['APITestUsername'];
        $apiKey			= $params['APITestKey'];
        $apiURL			= $params['APITestURL'];
    }

    // user defined configuration values
    $userHost			= $params['RegistrarDomain'];

    // set domain info
    $sld 				= $params['sld'];
    $tld 				= $params['tld'];

    // build post data
    $postfields = array(
        'hostname'			=> $userHost,
        'username'			=> $userIdentifier,
        'password'			=> $apiKey,
        'apiurl'			=> $apiURL,
        'domain'			=> $sld.'.'.$tld,
    );

    try {
        $api = new ApiClient();
        $api->call('GetNameservers',$postfields);

        return array(
            'success' => true,
            'ns1' => $api->getFromResponse('nameserver1'),
            'ns2' => $api->getFromResponse('nameserver2'),
            'ns3' => $api->getFromResponse('nameserver3'),
            'ns4' => $api->getFromResponse('nameserver4'),
            'ns5' => $api->getFromResponse('nameserver5'),
        );

    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}

function rotld_SaveNameservers($params) {
    /**
     * Save nameserver changes.
     *
     * This function should submit a change of nameservers request to the
     * domain registrar.
     *
     * @param array $params common module parameters
     *
     * @see https://developers.whmcs.com/domain-registrars/module-parameters/
     *
     * @return array
     */
    // user defined configuration values
    $userHost			= $params['RegistrarDomain'];

    // by default set LIVE user configuration values
    $userIdentifier		= $params['APIUsername'];
    $apiKey				= $params['APIKey'];
    $apiURL				= $params['APIURL'];
    $testMode			= $params['TestMode'];

    if ($testMode == 'on') {
        $userIdentifier	= $params['APITestUsername'];
        $apiKey			= $params['APITestKey'];
        $apiURL			= $params['APITestURL'];
    }

    // set domain info
    $sld 				= $params['sld'];
    $tld 				= $params['tld'];


    // submitted nameserver values
    $nameservers = "";
    if (isset($params['ns1']) && $params['ns1']!='') $nameservers .= 	$params['ns1'];
    if (isset($params['ns2']) && $params['ns2']!='') $nameservers .= ','.	$params['ns2'];
    if (isset($params['ns3']) && $params['ns3']!='') $nameservers .= ','.	$params['ns3'];
    if (isset($params['ns4']) && $params['ns4']!='') $nameservers .= ','.	$params['ns4'];
    if (isset($params['ns5']) && $params['ns5']!='') $nameservers .= ','.	$params['ns5'];

    $postfields = array(
        'hostname'			=> $userHost,
        'username'			=> $userIdentifier,
        'password'			=> $apiKey,
        'apiurl'			=> $apiURL,
        'domain'			=> $sld.'.'.$tld,
        'nameservers'		=> $nameservers,
    );


    try {
        $api = new ApiClient();
        $api->call('SetNameservers', $postfields);

        return array(
            'success' => true,
        );

    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}

function rotld_GetRegistrarLock($params) {
    /** OK
     * Get registrar lock status.
     *
     * Also known as Domain Lock or Transfer Lock status.
     *
     * @param array $params common module parameters
     *
     * @see https://developers.whmcs.com/domain-registrars/module-parameters/
     *
     * @return string|array Lock status or error message
     */
    // user defined configuration values
    $userHost			= $params['RegistrarDomain'];

    // by default set LIVE user configuration values
    $userIdentifier		= $params['APIUsername'];
    $apiKey				= $params['APIKey'];
    $apiURL				= $params['APIURL'];
    $testMode			= $params['TestMode'];

    if ($testMode == 'on') {
        $userIdentifier	= $params['APITestUsername'];
        $apiKey			= $params['APITestKey'];
        $apiURL			= $params['APITestURL'];
    }

    // set domain info
    $sld 				= $params['sld'];
    $tld 				= $params['tld'];

    // build post data
    $postfields = array(
        'hostname'			=> $userHost,
        'username'			=> $userIdentifier,
        'password'			=> $apiKey,
        'apiurl'			=> $apiURL,
        'domain'			=> $sld.'.'.$tld,
    );

    try {
        $api = new ApiClient();
        $api->call('GetLockStatus', $postfields);

        if ($api->getFromResponse('lockstatus') == 'locked') {
            return 'locked';
        } else {
            return 'unlocked';
        }
        return 'locked';
    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }


}

function rotld_SaveRegistrarLock($params) {
    /** OK
     * Set registrar lock status.
     *
     * @param array $params common module parameters
     *
     * @see https://developers.whmcs.com/domain-registrars/module-parameters/
     *
     * @return array
     */
    // user defined configuration values
    $userHost			= $params['RegistrarDomain'];

    // by default set LIVE user configuration values
    $userIdentifier		= $params['APIUsername'];
    $apiKey				= $params['APIKey'];
    $apiURL				= $params['APIURL'];
    $testMode			= $params['TestMode'];

    if ($testMode == 'on') {
        $userIdentifier	= $params['APITestUsername'];
        $apiKey			= $params['APITestKey'];
        $apiURL			= $params['APITestURL'];
    }

    // set domain info
    $sld 				= $params['sld'];
    $tld 				= $params['tld'];

    // lock status
    $lockStatus = $params['lockenabled'];

    // build post data
    $postfields = array(
        'hostname'			=> $userHost,
        'username'			=> $userIdentifier,
        'password'			=> $apiKey,
        'apiurl'			=> $apiURL,
        'domain'			=> $sld.'.'.$tld,
        'registrarlock'		=> ($lockStatus == 'locked') ? 1 : 0,
    );

    try {
        $api = new ApiClient();
        $api->call('SetLockStatus', $postfields);

        return array(
            'success' => 'success',
        );

    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}

function rotld_GetContactDetails($params) {
    /**
     * Get the current WHOIS Contact Information.
     *
     * Should return a multi-level array of the contacts and name/address
     * fields that be modified.
     *
     * @param array $params common module parameters
     *
     * @see https://developers.whmcs.com/domain-registrars/module-parameters/
     *
     * @return array
     */
    // user defined configuration values
    $userHost			= $params['RegistrarDomain'];

    // by default set LIVE user configuration values
    $userIdentifier		= $params['APIUsername'];
    $apiKey				= $params['APIKey'];
    $apiURL				= $params['APIURL'];
    $testMode			= $params['TestMode'];

    if ($testMode == 'on') {
        $userIdentifier	= $params['APITestUsername'];
        $apiKey			= $params['APITestKey'];
        $apiURL			= $params['APITestURL'];
    }

    // set domain info
    $sld 				= $params['sld'];
    $tld 				= $params['tld'];

    // build post data
    $postfields = array(
        'hostname'			=> $userHost,
        'username'			=> $userIdentifier,
        'password'			=> $apiKey,
        'apiurl'			=> $apiURL,
        'domain'			=> $sld.'.'.$tld,
    );

    try {
        $api = new ApiClient();
        $api->call('GetWhoisInformation', $postfields);

        return array(
            'Registrant' => array(
                'First Name' => $api->getFromResponse('name'),
                                  'Last Name' => $api->getFromResponse('name'),
                                  'Company Name' => $api->getFromResponse('registrant.company'),
                                  'Email Address' => $api->getFromResponse('email'),
                                  'Address 1' => $api->getFromResponse('address1'),
                                  'Address 2' => $api->getFromResponse('address2') . ' ' . $api->getFromResponse('address3'),
                                  'City' => $api->getFromResponse('city'),
                                  'State' => $api->getFromResponse('state_province'),
                                  'Postcode' => $api->getFromResponse('postal_code'),
                                  'Country' => $api->getFromResponse('country_code'),
                                  'Phone Number' => $api->getFromResponse('phone'),
                                  'Fax Number' => $api->getFromResponse('fax'),
            ),
            'Technical' => array(
                'First Name' => $api->getFromResponse('name'),
                                 'Last Name' => $api->getFromResponse('name'),
                                 'Company Name' => $api->getFromResponse('registrant.company'),
                                 'Email Address' => $api->getFromResponse('email'),
                                 'Address 1' => $api->getFromResponse('address1'),
                                 'Address 2' => $api->getFromResponse('address2') . ' ' . $api->getFromResponse('address3'),
                                 'City' => $api->getFromResponse('city'),
                                 'State' => $api->getFromResponse('state_province'),
                                 'Postcode' => $api->getFromResponse('postal_code'),
                                 'Country' => $api->getFromResponse('country_code'),
                                 'Phone Number' => $api->getFromResponse('phone'),
                                 'Fax Number' => $api->getFromResponse('fax'),
            ),
            'Billing' => array(
                'First Name' => $api->getFromResponse('name'),
                               'Last Name' => $api->getFromResponse('name'),
                               'Company Name' => $api->getFromResponse('registrant.company'),
                               'Email Address' => $api->getFromResponse('email'),
                               'Address 1' => $api->getFromResponse('address1'),
                               'Address 2' => $api->getFromResponse('address2') . ' ' . $api->getFromResponse('address3'),
                               'City' => $api->getFromResponse('city'),
                               'State' => $api->getFromResponse('state_province'),
                               'Postcode' => $api->getFromResponse('postal_code'),
                               'Country' => $api->getFromResponse('country_code'),
                               'Phone Number' => $api->getFromResponse('phone'),
                               'Fax Number' => $api->getFromResponse('fax'),
            ),
            'Admin' => array(
                'First Name' => $api->getFromResponse('name'),
                             'Last Name' => $api->getFromResponse('name'),
                             'Company Name' => $api->getFromResponse('registrant.company'),
                             'Email Address' => $api->getFromResponse('email'),
                             'Address 1' => $api->getFromResponse('address1'),
                             'Address 2' => $api->getFromResponse('address2') . ' ' . $api->getFromResponse('address3'),
                             'City' => $api->getFromResponse('city'),
                             'State' => $api->getFromResponse('state_province'),
                             'Postcode' => $api->getFromResponse('postal_code'),
                             'Country' => $api->getFromResponse('country_code'),
                             'Phone Number' => $api->getFromResponse('phone'),
                             'Fax Number' => $api->getFromResponse('fax'),
            ),
        );

    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}

function rotld_SaveContactDetails($params) {
    /**
     * Update the WHOIS Contact Information for a given domain.
     *
     * Called when a change of WHOIS Information is requested within WHMCS.
     * Receives an array matching the format provided via the `GetContactDetails`
     * method with the values from the users input.
     *
     * @param array $params common module parameters
     *
     * @see https://developers.whmcs.com/domain-registrars/module-parameters/
     *
     * @return array
     */
    // user defined configuration values
    $userIdentifier = $params['APIUsername'];
    $apiKey = $params['APIKey'];
    $testMode = $params['TestMode'];
    $accountMode = $params['AccountMode'];
    $emailPreference = $params['EmailPreference'];

    // domain parameters
    $sld = $params['sld'];
    $tld = $params['tld'];

    // whois information
    $contactDetails = $params['contactdetails'];

    // Build post data
    $postfields = array(
        'username' => $userIdentifier,
        'password' => $apiKey,
        'testmode' => $testMode,
        'domain' => $sld . '.' . $tld,
        'contacts' => array(
            'registrant' => array(
                'firstname' => $contactDetails['Registrant']['First Name'],
                'lastname' => $contactDetails['Registrant']['Last Name'],
                'company' => $contactDetails['Registrant']['Company Name'],
                'email' => $contactDetails['Registrant']['Email Address'],
                // etc...
            ),
            'tech' => array(
                'firstname' => $contactDetails['Technical']['First Name'],
                'lastname' => $contactDetails['Technical']['Last Name'],
                'company' => $contactDetails['Technical']['Company Name'],
                'email' => $contactDetails['Technical']['Email Address'],
                // etc...
            ),
            'billing' => array(
                'firstname' => $contactDetails['Billing']['First Name'],
                'lastname' => $contactDetails['Billing']['Last Name'],
                'company' => $contactDetails['Billing']['Company Name'],
                'email' => $contactDetails['Billing']['Email Address'],
                // etc...
            ),
            'admin' => array(
                'firstname' => $contactDetails['Admin']['First Name'],
                'lastname' => $contactDetails['Admin']['Last Name'],
                'company' => $contactDetails['Admin']['Company Name'],
                'email' => $contactDetails['Admin']['Email Address'],
                // etc...
            ),
        ),
    );

    try {
        $api = new ApiClient();
        $api->call('UpdateWhoisInformation', $postfields);

        return array(
            'success' => true,
        );

    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}

function rotld_ResendIRTPVerificationEmail($params) {
    // not implemented
}

function rotld_GetDNS($params) {

    // user defined configuration values
    $userHost			= $params['RegistrarDomain'];

    // by default set LIVE user configuration values
    $userIdentifier		= $params['APIUsername'];
    $apiKey				= $params['APIKey'];
    $apiURL				= $params['APIURL'];
    $testMode			= $params['TestMode'];

    if ($testMode == 'on') {
        $userIdentifier	= $params['APITestUsername'];
        $apiKey			= $params['APITestKey'];
        $apiURL			= $params['APITestURL'];
    }

    // set domain info
    $sld 				= $params['sld'];
    $tld 				= $params['tld'];

    // build post data
    $postfields = array(
        'hostname'			=> $userHost,
        'username'			=> $userIdentifier,
        'password'			=> $apiKey,
        'apiurl'			=> $apiURL,
        'domain'			=> $sld.'.'.$tld,
    );

    try {
        $api = new ApiClient();
        $api->call('GetDNSHostRecords', $postfields);

        $hostRecords = array();
        foreach ($api->getFromResponse('records') as $record) {
            $hostRecords[] = array(
                "hostname" => $record['name'], // eg. www
                "type" => $record['type'], // eg. A
                "address" => $record['address'], // eg. 10.0.0.1
                "priority" => $record['mxpref'], // eg. 10 (N/A for non-MX records)
            );
        }
        return $hostRecords;

    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
    /**
     * Get DNS Records for DNS Host Record Management.
     *
     * @param array $params common module parameters
     *
     * @see https://developers.whmcs.com/domain-registrars/module-parameters/
     *
     * @return array DNS Host Records
     */
}

function rotld_SaveDNS($params) {
    /**
     * Update DNS Host Records.
     *
     * @param array $params common module parameters
     *
     * @see https://developers.whmcs.com/domain-registrars/module-parameters/
     *
     * @return array
     */
    // user defined configuration values
    $userIdentifier = $params['APIUsername'];
    $apiKey = $params['APIKey'];
    $testMode = $params['TestMode'];
    $accountMode = $params['AccountMode'];
    $emailPreference = $params['EmailPreference'];

    // domain parameters
    $sld = $params['sld'];
    $tld = $params['tld'];

    // dns record parameters
    $dnsrecords = $params['dnsrecords'];

    // Build post data
    $postfields = array(
        'username' => $userIdentifier,
        'password' => $apiKey,
        'testmode' => $testMode,
        'domain' => $sld . '.' . $tld,
        'records' => $dnsrecords,
    );

    try {
        $api = new ApiClient();
        $api->call('GetDNSHostRecords', $postfields);

        return array(
            'success' => 'success',
        );

    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}

function rotld_IDProtectToggle($params) {
    /** OK
     * Enable/Disable ID Protection.
     *
     * @param array $params common module parameters
     *
     * @see https://developers.whmcs.com/domain-registrars/module-parameters/
     *
     * @return array
     */
    // user defined configuration values
    $userHost			= $params['RegistrarDomain'];

    // by default set LIVE user configuration values
    $userIdentifier		= $params['APIUsername'];
    $apiKey				= $params['APIKey'];
    $apiURL				= $params['APIURL'];
    $testMode			= $params['TestMode'];

    if ($testMode == 'on') {
        $userIdentifier	= $params['APITestUsername'];
        $apiKey			= $params['APITestKey'];
        $apiURL			= $params['APITestURL'];
    }

    // set domain info
    $sld 				= $params['sld'];
    $tld 				= $params['tld'];

    // id protection parameter
    $protectEnable = (bool) $params['protectenable'];

    // build post data
    $postfields = array(
        'hostname'			=> $userHost,
        'username'			=> $userIdentifier,
        'password'			=> $apiKey,
        'apiurl'			=> $apiURL,
        'domain'			=> $sld.'.'.$tld,
    );

    try {
        $api = new ApiClient();

        if ($protectEnable) {
            $api->call('EnableIDProtection', $postfields);
        } else {
            $api->call('DisableIDProtection', $postfields);
        }

        return array(
            'success' => 'success',
        );

    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}

function rotld_GetEPPCode($params) {
    /**
     * Request EEP Code.
     *
     * Supports both displaying the EPP Code directly to a user or indicating
     * that the EPP Code will be emailed to the registrant.
     *
     * @param array $params common module parameters
     *
     * @see https://developers.whmcs.com/domain-registrars/module-parameters/
     *
     * @return array
     *
     */
    // user defined configuration values
    $userIdentifier = $params['APIUsername'];
    $apiKey = $params['APIKey'];
    $testMode = $params['TestMode'];
    $accountMode = $params['AccountMode'];
    $emailPreference = $params['EmailPreference'];

    // domain parameters
    $sld = $params['sld'];
    $tld = $params['tld'];

    // Build post data
    $postfields = array(
        'username' => $userIdentifier,
        'password' => $apiKey,
        'testmode' => $testMode,
        'domain' => $sld . '.' . $tld,
    );

    try {
        $api = new ApiClient();
        $api->call('RequestEPPCode', $postfields);

        if ($api->getFromResponse('eppcode')) {
            // If EPP Code is returned, return it for display to the end user
            return array(
                'eppcode' => $api->getFromResponse('eppcode'),
            );
        } else {
            // If EPP Code is not returned, it was sent by email, return success
            return array(
                'success' => 'success',
            );
        }

    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}

function rotld_ReleaseDomain($params) {
    /**
     * Release a Domain.
     *
     * Used to initiate a transfer out such as an IPSTAG change for .UK
     * domain names.
     *
     * @param array $params common module parameters
     *
     * @see https://developers.whmcs.com/domain-registrars/module-parameters/
     *
     * @return array
     */
    // user defined configuration values
    $userHost			= $params['RegistrarDomain'];

    // by default set LIVE user configuration values
    $userIdentifier		= $params['APIUsername'];
    $apiKey				= $params['APIKey'];
    $apiURL				= $params['APIURL'];
    $testMode			= $params['TestMode'];

    if ($testMode == 'on') {
        $userIdentifier	= $params['APITestUsername'];
        $apiKey			= $params['APITestKey'];
        $apiURL			= $params['APITestURL'];
    }

    // set domain info
    $sld 				= $params['sld'];
    $tld 				= $params['tld'];

    // transfer tag
    $transferTag = $params['transfertag'];

    // build post data
    $postfields = array(
        'hostname'			=> $userHost,
        'username'			=> $userIdentifier,
        'password'			=> $apiKey,
        'apiurl'			=> $apiURL,
        'domain'			=> $sld.'.'.$tld,
        'newtag' => $transferTag,
    );

    try {
        $api = new ApiClient();
        $api->call('ReleaseDomain', $postfields);

        return array(
            'success' => 'success',
        );

    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}

function rotld_RegisterNameserver($params) {
    /**
     * Register a Nameserver.
     *
     * Adds a child nameserver for the given domain name.
     *
     * @param array $params common module parameters
     *
     * @see https://developers.whmcs.com/domain-registrars/module-parameters/
     *
     * @return array
     */
    // user defined configuration values
    $userHost			= $params['RegistrarDomain'];

    // by default set LIVE user configuration values
    $userIdentifier		= $params['APIUsername'];
    $apiKey				= $params['APIKey'];
    $apiURL				= $params['APIURL'];
    $testMode			= $params['TestMode'];

    if ($testMode == 'on') {
        $userIdentifier	= $params['APITestUsername'];
        $apiKey			= $params['APITestKey'];
        $apiURL			= $params['APITestURL'];
    }

    // set domain info
    $sld 				= $params['sld'];
    $tld 				= $params['tld'];

    // nameserver parameters
    $nameserver = $params['nameserver'];
    $ipAddress = $params['ipaddress'];

    // build post data
    $postfields = array(
        'hostname'			=> $userHost,
        'username'			=> $userIdentifier,
        'password'			=> $apiKey,
        'apiurl'			=> $apiURL,
        'domain'			=> $sld.'.'.$tld,
        'nameserver'		=> $nameserver,
        'ip'				=> $ipAddress,
    );

    try {
        $api = new ApiClient();
        $api->call('RegisterNameserver', $postfields);

        return array(
            'success' => 'success',
        );

    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}

function rotld_ModifyNameserver($params) {
    /**
     * Modify a Nameserver.
     *
     * Modifies the IP of a child nameserver.
     *
     * @param array $params common module parameters
     *
     * @see https://developers.whmcs.com/domain-registrars/module-parameters/
     *
     * @return array
     */
    // user defined configuration values
    $userHost			= $params['RegistrarDomain'];

    // by default set LIVE user configuration values
    $userIdentifier		= $params['APIUsername'];
    $apiKey				= $params['APIKey'];
    $apiURL				= $params['APIURL'];
    $testMode			= $params['TestMode'];

    if ($testMode == 'on') {
        $userIdentifier	= $params['APITestUsername'];
        $apiKey			= $params['APITestKey'];
        $apiURL			= $params['APITestURL'];
    }

    // set domain info
    $sld 				= $params['sld'];
    $tld 				= $params['tld'];

    // nameserver parameters
    $nameserver = $params['nameserver'];
    $currentIpAddress = $params['currentipaddress'];
    $newIpAddress = $params['newipaddress'];

    // build post data
    $postfields = array(
        'hostname'			=> $userHost,
        'username'			=> $userIdentifier,
        'password'			=> $apiKey,
        'apiurl'			=> $apiURL,
        'domain'			=> $sld.'.'.$tld,
        'nameserver'		=> $nameserver,
        'currentip'			=> $currentIpAddress,
        'newip'				=> $newIpAddress,
    );

    try {
        $api = new ApiClient();
        $api->call('ModifyNameserver', $postfields);

        return array(
            'success' => 'success',
        );

    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}

function rotld_DeleteNameserver($params) {
    /**
     * Delete a Nameserver.
     *
     * @param array $params common module parameters
     *
     * @see https://developers.whmcs.com/domain-registrars/module-parameters/
     *
     * @return array
     */
    // user defined configuration values
    $userHost			= $params['RegistrarDomain'];

    // by default set LIVE user configuration values
    $userIdentifier		= $params['APIUsername'];
    $apiKey				= $params['APIKey'];
    $apiURL				= $params['APIURL'];
    $testMode			= $params['TestMode'];

    if ($testMode == 'on') {
        $userIdentifier	= $params['APITestUsername'];
        $apiKey			= $params['APITestKey'];
        $apiURL			= $params['APITestURL'];
    }

    // set domain info
    $sld 				= $params['sld'];
    $tld 				= $params['tld'];

    // nameserver parameters
    $nameserver = $params['nameserver'];

    // build post data
    $postfields = array(
        'hostname'			=> $userHost,
        'username'			=> $userIdentifier,
        'password'			=> $apiKey,
        'apiurl'			=> $apiURL,
        'domain'			=> $sld.'.'.$tld,
        'nameserver'		=> $nameserver,
    );

    try {
        $api = new ApiClient();
        $api->call('DeleteNameserver', $postfields);

        return array(
            'success' => 'success',
        );

    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}

function rotld_RequestDelete($params) {
    /**
     * Delete Domain.
     *
     * @param array $params common module parameters
     *
     * @see https://developers.whmcs.com/domain-registrars/module-parameters/
     *
     * @return array
     */
    // user defined configuration values
    $userHost			= $params['RegistrarDomain'];

    // by default set LIVE user configuration values
    $userIdentifier		= $params['APIUsername'];
    $apiKey				= $params['APIKey'];
    $apiURL				= $params['APIURL'];
    $testMode			= $params['TestMode'];

    if ($testMode == 'on') {
        $userIdentifier	= $params['APITestUsername'];
        $apiKey			= $params['APITestKey'];
        $apiURL			= $params['APITestURL'];
    }

    // set domain info
    $sld 				= $params['sld'];
    $tld 				= $params['tld'];

    // build post data
    $postfields = array(
        'hostname'			=> $userHost,
        'username'			=> $userIdentifier,
        'password'			=> $apiKey,
        'apiurl'			=> $apiURL,
        'domain'			=> $sld.'.'.$tld,
    );

    try {
        $api = new ApiClient();
        $api->call('DeleteDomain', $postfields);

        return array(
            'success' => 'success',
        );

    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}

function rotld_ClientArea($params) {
    /**
     * Client Area Output.
     *
     * This function renders output to the domain details interface within
     * the client area. The return should be the HTML to be output.
     *
     * @param array $params common module parameters
     *
     * @see https://developers.whmcs.com/domain-registrars/module-parameters/
     *
     * @return string HTML Output
     */
    $output = '
    <div class="alert alert-info">
    Your custom HTML output goes here...
    </div>
    ';

        //    return $output;
        return '';
}

function rotld_ClientAreaCustomButtonArray() {
    /**
     * Client Area Custom Button Array.
     *
     * Allows you to define additional actions your module supports.
     * In this example, we register a Push Domain action which triggers
     * the `rotld_push` function when invoked.
     *
     * @return array
     */
    return array(
        // 'Check Balance' => 'CheckBalance',
    );
}

function rotld_ClientAreaAllowedFunctions() {
    /**
     * Client Area Allowed Functions.
     *
     * Only the functions defined within this function or the Client Area
     * Custom Button Array can be invoked by client level users.
     *
     * @return array
     */
    return array(
        // 'Check Balance' => 'CheckBalance',
    );
}

function rotld_Sync($params) {
    /**
     * Sync Domain Status & Expiration Date.
     *
     * Domain syncing is intended to ensure domain status and expiry date
     * changes made directly at the domain registrar are synced to WHMCS.
     * It is called periodically for a domain.
     *
     * @param array $params common module parameters
     * @see https://developers.whmcs.com/domain-registrars/module-parameters/
     * @return array
     *
     * ROTLD command is "domain-info"
     *
     */
    // user defined configuration values
    $userHost			= $params['RegistrarDomain'];

    // by default set LIVE user configuration values
    $userIdentifier		= $params['APIUsername'];
    $apiKey				= $params['APIKey'];
    $apiURL				= $params['APIURL'];
    $testMode			= $params['TestMode'];

    if ($testMode == 'on') {
        $userIdentifier	= $params['APITestUsername'];
        $apiKey			= $params['APITestKey'];
        $apiURL			= $params['APITestURL'];
    }

    // set domain info
    $sld 				= $params['sld'];
    $tld 				= $params['tld'];

    // build post data
    $postfields = array(
        'hostname'			=> $userHost,
        'username'			=> $userIdentifier,
        'password'			=> $apiKey,
        'apiurl'			=> $apiURL,
        'domain'			=> $sld.'.'.$tld,
    );

    try {
        $api = new ApiClient();
        $api->call('Sync', $postfields);

        return array(
            'expirydate' => $api->getFromResponse('expirydate'), // Format: YYYY-MM-DD
                     'active' => (bool) $api->getFromResponse('active'), // Return true if the domain is active
                     'expired' => (bool) $api->getFromResponse('expired'), // Return true if the domain has expired
                     'transferredAway' => (bool) $api->getFromResponse('transferredaway'), // Return true if the domain is transferred out
        );

    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}

function rotld_TransferSync($params) {
    /**
     * Incoming Domain Transfer Sync.
     *
     * Check status of incoming domain transfers and notify end-user upon
     * completion. This function is called daily for incoming domains.
     *
     * @param array $params common module parameters
     *
     * @see https://developers.whmcs.com/domain-registrars/module-parameters/
     *
     * @return array
     */
    // user defined configuration values
    $userIdentifier = $params['APIUsername'];
    $apiKey = $params['APIKey'];
    $testMode = $params['TestMode'];
    $accountMode = $params['AccountMode'];
    $emailPreference = $params['EmailPreference'];

    // domain parameters
    $sld = $params['sld'];
    $tld = $params['tld'];

    // Build post data
    $postfields = array(
        'username' => $userIdentifier,
        'password' => $apiKey,
        'testmode' => $testMode,
        'domain' => $sld . '.' . $tld,
    );

    try {
        $api = new ApiClient();
        $api->call('CheckDomainTransfer', $postfields);

        if ($api->getFromResponse('transfercomplete')) {
            return array(
                'completed' => true,
                'expirydate' => $api->getFromResponse('expirydate'), // Format: YYYY-MM-DD
            );
        } elseif ($api->getFromResponse('transferfailed')) {
            return array(
                'failed' => true,
                'reason' => $api->getFromResponse('failurereason'), // Reason for the transfer failure if available
            );
        } else {
            // No status change, return empty array
            return array();
        }

    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}

function rotld_GetTldPricing($params) {
    /**
     * NOT implemented
     *
     */
    return array();
}


/**********************************
 *  extra functions
 **********************************/

function rotld_CheckBalance($params) {
/**************************************************************
 * function used to check the existing balance in deposit
 *
 * NOT done
 *
 * @return array
 **************************************************************/

    $userHost		= $params['RegistrarDomain'];
    $userIdentifier	= $params['APIUsername'];
    $apiKey			= $params['APIKey'];
    $apiurl			= '';	// add API URL

    $postfields = array(
        'apiusr' => $userIdentifier,
        'apikey' => $apiKey,
        'myhost' => $userHost,
        'domain' => '',
    );

    try {
        $api = new ApiClient();
        $api->call('check_balance', $postfields);
        return array(
            'success' => true,
        );
    } catch (\Exception $e) {
        return array(
        'error' => $e->getMessage(),
        );
    }
}

function rotld_CheckAvailability($params) {
    /**
     * Check Domain Availability.
     *
     * Determine if a domain or group of domains are available for
     * registration or transfer.
     *
     * @param array $params common module parameters
     * @see https://developers.whmcs.com/domain-registrars/module-parameters/
     *
     * @see \WHMCS\Domains\DomainLookup\SearchResult
     * @see \WHMCS\Domains\DomainLookup\ResultsList
     *
     * @throws Exception Upon domain availability check failure.
     *
     * @return \WHMCS\Domains\DomainLookup\ResultsList An ArrayObject based collection of \WHMCS\Domains\DomainLookup\SearchResult results
     */
    // user defined configuration values
    $userIdentifier = $params['APIUsername'];
    $apiKey = $params['APIKey'];
    $testMode = $params['TestMode'];
    $accountMode = $params['AccountMode'];
    $emailPreference = $params['EmailPreference'];

    // availability check parameters
    $searchTerm = $params['searchTerm'];
    $punyCodeSearchTerm = $params['punyCodeSearchTerm'];
    $tldsToInclude = $params['tldsToInclude'];
    $isIdnDomain = (bool) $params['isIdnDomain'];
    $premiumEnabled = (bool) $params['premiumEnabled'];

    // Build post data
    $postfields = array(
        'username' => $userIdentifier,
        'password' => $apiKey,
        'testmode' => $testMode,
        'domain' => $sld . '.' . $tld,
        'searchTerm' => $searchTerm,
        'tldsToSearch' => $tldsToInclude,
        'includePremiumDomains' => $premiumEnabled,
    );

    try {
        $api = new ApiClient();
        $api->call('CheckAvailability', $postfields);

        $results = new ResultsList();
        foreach ($api->getFromResponse('domains') as $domain) {

            // Instantiate a new domain search result object
            $searchResult = new SearchResult($domain['sld'], $domain['tld']);

            // Determine the appropriate status to return
            if ($domain['status'] == 'available') {
                $status = SearchResult::STATUS_NOT_REGISTERED;
            } elseif ($domain['status'] == 'registered') {
                $status = SearchResult::STATUS_REGISTERED;
            } elseif ($domain['status'] == 'reserved') {
                $status = SearchResult::STATUS_RESERVED;
            } else {
                $status = SearchResult::STATUS_TLD_NOT_SUPPORTED;
            }
            $searchResult->setStatus($status);

            // Return premium information if applicable
            if ($domain['isPremiumName']) {
                $searchResult->setPremiumDomain(true);
                $searchResult->setPremiumCostPricing(
                    array(
                        'register' => $domain['premiumRegistrationPrice'],
                        'renew' => $domain['premiumRenewPrice'],
                        'CurrencyCode' => 'USD',
                    )
                );
            }

            // Append to the search results list
            $results->append($searchResult);
        }

        return $results;

    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}

function rotld_DomainSuggestionOptions() {
    /**
     * Domain Suggestion Settings.
     *
     * Defines the settings relating to domain suggestions (optional).
     * It follows the same convention as `getConfigArray`.
     *
     * @see https://developers.whmcs.com/domain-registrars/check-availability/
     *
     * @return array of Configuration Options
     */
    return array(
        'includeCCTlds' => array(
            'FriendlyName' => 'Include Country Level TLDs',
            'Type' => 'yesno',
            'Description' => 'Tick to enable',
        ),
    );
}

function rotld_GetDomainSuggestions($params) {
    /**
     * Get Domain Suggestions.
     *
     * Provide domain suggestions based on the domain lookup term provided.
     *
     * @param array $params common module parameters
     * @see https://developers.whmcs.com/domain-registrars/module-parameters/
     *
     * @see \WHMCS\Domains\DomainLookup\SearchResult
     * @see \WHMCS\Domains\DomainLookup\ResultsList
     *
     * @throws Exception Upon domain suggestions check failure.
     *
     * @return \WHMCS\Domains\DomainLookup\ResultsList An ArrayObject based collection of \WHMCS\Domains\DomainLookup\SearchResult results
     */
    // user defined configuration values
    $userIdentifier = $params['APIUsername'];
    $apiKey = $params['APIKey'];
    $testMode = $params['TestMode'];
    $accountMode = $params['AccountMode'];
    $emailPreference = $params['EmailPreference'];

    // availability check parameters
    $searchTerm = $params['searchTerm'];
    $punyCodeSearchTerm = $params['punyCodeSearchTerm'];
    $tldsToInclude = $params['tldsToInclude'];
    $isIdnDomain = (bool) $params['isIdnDomain'];
    $premiumEnabled = (bool) $params['premiumEnabled'];
    $suggestionSettings = $params['suggestionSettings'];

    // Build post data
    $postfields = array(
        'username' => $userIdentifier,
        'password' => $apiKey,
        'testmode' => $testMode,
        'domain' => $sld . '.' . $tld,
        'searchTerm' => $searchTerm,
        'tldsToSearch' => $tldsToInclude,
        'includePremiumDomains' => $premiumEnabled,
        'includeCCTlds' => $suggestionSettings['includeCCTlds'],
    );

    try {
        $api = new ApiClient();
        $api->call('GetSuggestions', $postfields);

        $results = new ResultsList();
        foreach ($api->getFromResponse('domains') as $domain) {

            // Instantiate a new domain search result object
            $searchResult = new SearchResult($domain['sld'], $domain['tld']);

            // All domain suggestions should be available to register
            $searchResult->setStatus(SearchResult::STATUS_NOT_REGISTERED);

            // Used to weight results by relevance
            $searchResult->setScore($domain['score']);

            // Return premium information if applicable
            if ($domain['isPremiumName']) {
                $searchResult->setPremiumDomain(true);
                $searchResult->setPremiumCostPricing(
                    array(
                        'register' => $domain['premiumRegistrationPrice'],
                        'renew' => $domain['premiumRenewPrice'],
                        'CurrencyCode' => 'USD',
                    )
                );
            }

            // Append to the search results list
            $results->append($searchResult);
        }

        return $results;

    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}


































/**
 * Example Custom Module Function: Push
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 * @return array
 */
function rotld_push($params) {
    // user defined configuration values
    $userIdentifier = $params['APIUsername'];
    $apiKey = $params['APIKey'];
    $testMode = $params['TestMode'];
    $accountMode = $params['AccountMode'];
    $emailPreference = $params['EmailPreference'];

    // domain parameters
    $sld = $params['sld'];
    $tld = $params['tld'];

    // Perform custom action here...

    return 'Not implemented';
}




