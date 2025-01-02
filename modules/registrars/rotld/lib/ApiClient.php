<?php

namespace WHMCS\Module\Registrar\rotld;


class ApiCall {
	/**
	 *
	 *
	 *
	 */
	protected	$results	= array();
	private		$params		= array();
	private		$fields		= array();
	private		$cid;

	public function ApiCall($configuration_parameters) {
		$this->params['post_fiels']	= '';
		$this->params['host']		= 'undefined';
		$this->fields['lang']		= 'en';
		$this->fields['format']		= 'json';

		if(isset($configuration_parameters['lang']) && $configuration_parameters['lang']!='') {
			$this->fields['lang'] = $configuration_parameters['lang'];
		}

		if(isset($configuration_parameters['format']) && $configuration_parameters['format']!='') {
			$this->fields['format'] = $configuration_parameters['format'];
		}

		if(!isset($configuration_parameters['apiurl'])) throw new \Exception('Invalid apiurl');
		$this->params['url'] = $configuration_parameters['apiurl'];

		if(isset($configuration_parameters['registrar_domain'])) {
			$this->params['host'] = $configuration_parameters['registrar_domain'];
		}

		if(!isset($configuration_parameters['regid'])) throw new \Exception('Invalid regid');
		$this->params['login'] = trim($configuration_parameters['regid']);

		if(!isset($configuration_parameters['password'])) throw new \Exception('Invalid password');
		$this->params['password'] = trim($configuration_parameters['password']);


	}

	public function set_param($param,$value) {
		$this->fields[$param] = trim($value);
	}

	public function reset() {
		$this->fields = array();
	}

	public function commit() {
		$poststr = '';
		$fields = $this->fields;
		if(is_array($fields) && sizeof($fields)) {
			foreach($fields as $key=>$val) {
				$val = urlencode($val);
				$poststr.=$key."=".$val."&";
			}
		}
		$this->params['post_fields']=$poststr;

		$ch = new CurlRequest();
		$ch->init($this->params);
		$result = $ch->exec();
		if ($result['http_code']!='200') {
			switch ($result['http_code']){
				case '401':
					throw new Exception("Authentication Failure. Invalid credentials.");
				case '500':
					throw new Exception("Service not available.");
				default:
					throw new Exception("Service not available.");
			}
		}
		if (!$result['body'])   throw new Exception("Invalid response from server");

		return $result['body'];
	}
}


class ApiClient {
	/**
	 * RoTLD Registrar API Client.
	 * A simple API Client for communicating with an external API endpoint.
	 *
	 * version 0.1
	 *
	 */
	protected $results	= array();
	private $params;
	private $cid;

	/** Make external API call to registrar API.
	 *
	 * @param string $action
	 * @param array $postfields
	 *
	 * @throws \Exception Connection error
	 * @throws \Exception Bad API response
	 *
	 * @return array
	 */
    public function call($action, $postfields) {

		if (!isset($action)) throw new \Exception('No action received from caller!');

		/** We need to translate WHMCS actions into ROTLD actions
		 * this is done by magically calling different functions
		 */
		switch ($action) {
			case 'CheckBalance':
				// call rotld 'check_balance' for current reseller
				$this->results = $this->checkBalance($postfields);
				break;
			case 'Renew':
				// call rotld 'domain-renew' for sent domain and period
				$this->results = $this->renewDomain($postfields);
				break;
			case 'GetNameservers':
				// call rotld 'domain-info' for sent domain (verified OK)
				$this->results = $this->getNameServersFromDomainInfo($postfields);
				break;
			case 'SetNameservers':
				// call rotld 'domain-reset-ns' for sent domain (verified OK)
				$this->results = $this->setNameServersFromDomainInfo($postfields);
				break;
			case 'GetLockStatus':
				// locking not implemented at ROTLD, return 'unlocked' without error
				$this->results = 'locked';
				break;
			case 'SetLockStatus':
				// domain locking is not implemented at ROTLD, will return error with explanatory message
				// TODO: implement translation of the error string
				//throw new \Exception('ROTLD does not support domain locking yet, so domain will remain unlocked');
				break;
			case 'EnableIDProtection':
				// ID Protection not implemented at ROTLD, return error with explanatory message
				// TODO: implement translation of the error string
				throw new \Exception('ROTLD does not support domain ID Protection');
				break;
			case 'DisableIDProtection':
				// ID Protection not implemented at ROTLD, return error with explanatory message
				// TODO: implement translation of the error string
				throw new \Exception('ROTLD does not support domain ID Protection');
				break;
			case 'ReleaseDomain':
				// deletion not implemented at ROTLD, return error with explanatory message
				// TODO: implement translation of the error string
				throw new \Exception('Function not supported');
				break;
			case 'DeleteDomain':
				// deletion not implemented at ROTLD, return error with explanatory message
				// TODO: implement translation of the error string
				throw new \Exception('ROTLD does not support domain deletion');
				break;
			case 'RegisterNameserver':
				// call rotld 'nameserver-create' for sent domain
				$this->results = $this->createNameServer($postfields);
				break;
			case 'ModifyNameserver':
				// call rotld 'nameserver-update' for sent domain
				$this->results = $this->updateNameServer($postfields);
				break;			
			case 'DeleteNameserver':
				// call rotld 'nameserver-delete' for sent domain
				$this->results = $this->deleteNameServer($postfields);
				break;
			case 'Sync':
				// call rotld 'domain-info' for sent domain (verified OK)
				$this->results = $this->syncDomainInfo($postfields);
				break;
			case 'GetWhoisInformation':
				// call rotld 'domain-info' and get contact ID (CID)
				// then call 'contact-info' and get contact details
				$this->results		= $this->getContact($postfields);
				break;
			default:
				$this->results = array();
		}

		logModuleCall (
			'rotld',
			$action,
			$postfields,
			$response,
			$this->results
//			array(
//				$postfields['username'],
//				$postfields['password'],
//			)
		);

		if ($this->results === null && json_last_error() !== JSON_ERROR_NONE) {
			throw new \Exception('Bad response received from API');
		}
		
		return $this->results;
	}


	/** OK
	 * Process API response.
	 * @param string $response
	 * @return array
	 */
	public function processResponse($response) {
		return json_decode($response, true);
	}

	/** OK
	 * Get from response results.
	 * @param string $key
	 * @return string
	 */
	public function getFromResponse($key) {
		return isset($this->results[$key]) ? $this->results[$key] : '';
	}

/** #########################################################################
 *
 *	Start of operational funtions
 *
 *	#########################################################################
 */


	/**
	 * Check current balance from check_balance ROTLD command.
	 * @param array $postfields
	 * @return array
	 */
	public function checkBalance($postfields) {
		$this->params['login']		= $postfields['username'];
		$this->params['password']	= $postfields['password'];
		$this->params['apiurl']		= $postfields['apiurl'];
		$this->params['vars']		= array (
										'command' => 'check-balance',
									);
		$ch = new CurlRequest();
		$ch->init($this->params);
		$curl_response = $ch->exec();

		if ($curl_response['http_code']!='200') {
			switch ($curl_response['http_code']){
				case '401':
					throw new \Exception("Authentication Failure. Invalid credentials.");
					break;
				case '500':
					throw new \Exception("Service not available - 500");
					break;
				default:
					throw new \Exception("Service not available.");
			}
		}
		if (!$curl_response['body']) throw new \Exception("Invalid response from server");
			$response_array = $this->processResponse($curl_response['body']);
		$result = array (
			'balance' => $response_array['data']['balance'],
		);
		return $result;
	}

	/** OK
	 * Renew domain via  ROTLD command.
	 * @param array $postfields
	 * @return array
	 */
	public function renewDomain($postfields) {
		$this->params['host']		= $postfields['hostname'];
		$this->params['login']		= $postfields['username'];
		$this->params['password']	= $postfields['password'];
		$this->params['apiurl']		= $postfields['apiurl'];

		$this->params['vars']		= array (
										'command'		=> 'domain-renew',
										'domain'		=> $postfields['domain'],
										'domain_period'	=> $postfields['period'],
									);
		$ch = new CurlRequest();
		$ch->init($this->params);
		$curl_response = $ch->exec();

		if ($curl_response['http_code']!='200') {
			switch ($curl_response['http_code']){
				case '401':
					throw new \Exception("Authentication Failure. Invalid credentials.");
					break;
				case '500':
					throw new \Exception("Service not available - 500");
					break;
				default:
					throw new \Exception("Service not available.");
			}
		}
		if (!$curl_response['body'])	throw new \Exception("Invalid response from server");
		$response_array = $this->processResponse($curl_response['body']);
		
		switch ($response_array['result_code']) {
				case '00200':
					return $response_array;
					break;
				default:
					throw new \Exception($response_array['result_code'].' '.$response_array['result_message']);
		}
		return $result;
	}

	/** OK
	 * Get nameservers from domain-info ROTLD command.
	 * @param array $postfields
	 * @return array
	 */
	public function getNameServersFromDomainInfo($postfields) {
		$this->params['host']		= $postfields['hostname'];
		$this->params['login']		= $postfields['username'];
		$this->params['password']	= $postfields['password'];
		$this->params['apiurl']		= $postfields['apiurl'];

		$this->params['vars']		= array (
										'command'	=> 'domain-info',
										'domain'	=> $postfields['domain'],
									);

		$ch = new CurlRequest();
		$ch->init($this->params);
		$curl_response = $ch->exec();
		if ($curl_response['http_code']!='200') {
			switch ($curl_response['http_code']){
				case '401':
					throw new \Exception("Authentication Failure. Invalid credentials.");
					break;
				case '500':
					throw new \Exception("Service not available - 500");
					break;
				default:
					throw new \Exception("Service not available.");
			}
		}
		if (!$curl_response['body'])	throw new \Exception("Invalid response from server");

		$response_array = $this->processResponse($curl_response['body']);
		
		// we assume domain exists and is managed by this registrar
		// ToDo 
		// add better processing of returned codes
		
		switch ($response_array['result_code']) {
				case '00200':
					$result = array (
								'nameserver1' => $response_array['data']['nameservers'][0],
								'nameserver2' => $response_array['data']['nameservers'][1],
								'nameserver3' => $response_array['data']['nameservers'][2],
								'nameserver4' => $response_array['data']['nameservers'][3],
								'nameserver5' => $response_array['data']['nameservers'][4],
					);
					break;
				default:
					throw new \Exception($response_array['result_code'].' '.$response_array['result_message']);
		}
		return $result;
	}

	/** OK
	 * Set nameservers using domain-reset-ns ROTLD command.
	 * @param array $postfields
	 * @return array
	 */
	public function setNameServersFromDomainInfo($postfields) {
		$this->params['host']		= $postfields['hostname'];
		$this->params['login']		= $postfields['username'];
		$this->params['password']	= $postfields['password'];
		$this->params['apiurl']		= $postfields['apiurl'];
		
		$this->params['vars']		= array (
										'command'		=> 'domain-reset-ns',
										'domain'		=> $postfields['domain'],
										'nameservers'	=> $postfields['nameservers'],
									);
		$ch = new CurlRequest();
		$ch->init($this->params);
		$curl_response = $ch->exec();
		if ($curl_response['http_code']!='200') {
			switch ($curl_response['http_code']){
				case '401':
					throw new \Exception("Authentication Failure. Invalid credentials.");
					break;
				case '500':
					throw new \Exception("Service not available - 500");
					break;
				default:
					throw new \Exception("Service not available.");
			}
		}
		if (!$curl_response['body'])	throw new \Exception("Invalid response from server");

		$response_array = $this->processResponse($curl_response['body']);

		switch ($response_array['result_code']) {
				case '00200':
					return $response_array;
					break;
				default:
					throw new \Exception($response_array['result_code'].' '.$response_array['result_message']);
		}
		return $response_array;	// we do not process data from here
	}
	
	/** OK
	 * Create a nameserver for a domain using nameserver-create ROTLD command.
	 *
	 * @param array $postfields
	 *
	 * @return array
	 */
	public function createNameServer($postfields) {
		$this->params['host']		= $postfields['hostname'];
		$this->params['login']		= $postfields['username'];
		$this->params['password']	= $postfields['password'];
		$this->params['apiurl']		= $postfields['apiurl'];
		
		$this->params['vars']		= array (
										'command'		=> 'nameserver-create',
										'nameserver'	=> $postfields['nameserver'],
										'ips'			=> $postfields['ip'],
									);
		$ch = new CurlRequest();

		$ch->init($this->params);
		$curl_response = $ch->exec();
		if ($curl_response['http_code']!='200') {
			switch ($curl_response['http_code']){
				case '401':
					throw new \Exception("Authentication Failure. Invalid credentials.");
					break;
				case '500':
					throw new \Exception("Service not available - 500");
					break;
				default:
					throw new \Exception("Service not available.");
			}
		}
		if (!$curl_response['body'])	throw new \Exception("Invalid response from server");

		$response_array = $this->processResponse($curl_response['body']);

		switch ($response_array['result_code']) {
				case '00200':
					return $response_array;
					break;
				default:
					throw new \Exception($response_array['result_code'].' '.$response_array['result_message']);
		}
		return $response_array;	// we do not process data from here
	}

	/** OK
	 * modify an existing private nameserver for a domain using nameserver-update ROTLD command.
	 *
	 * @param array $postfields
	 *
	 * @return array
	 */
	public function updateNameServer($postfields) {
		$this->params['host']		= $postfields['hostname'];
		$this->params['login']		= $postfields['username'];
		$this->params['password']	= $postfields['password'];
		$this->params['apiurl']		= $postfields['apiurl'];
		
		// BEWARE - ToDo
		// this command is blindly updating NS IP address, without any check
		// a condition should be designed to update IP only if old IP mathes the record
		$this->params['vars']		= array (
										'command'		=> 'nameserver-update',
										'nameserver'	=> $postfields['nameserver'],
										'ips'			=> $postfields['newip'],
									);
		$ch = new CurlRequest();

		$ch->init($this->params);
		$curl_response = $ch->exec();
		if ($curl_response['http_code']!='200') {
			switch ($curl_response['http_code']){
				case '401':
					throw new \Exception("Authentication Failure. Invalid credentials.");
					break;
				case '500':
					throw new \Exception("Service not available - 500");
					break;
				default:
					throw new \Exception("Service not available.");
			}
		}
		if (!$curl_response['body'])	throw new \Exception("Invalid response from server");

		$response_array = $this->processResponse($curl_response['body']);

		switch ($response_array['result_code']) {
				case '00200':
					return $response_array;
					break;
				default:
					throw new \Exception($response_array['result_code'].' '.$response_array['result_message']);
		}
		return $response_array;	// we do not process data from here
	}

	/** OK
	 * delete an existing private nameserver for a domain using nameserver-delete ROTLD command.
	 * @param array $postfields
	 * @return array
	 */
	public function deleteNameServer($postfields) {
		$this->params['host']		= $postfields['hostname'];
		$this->params['login']		= $postfields['username'];
		$this->params['password']	= $postfields['password'];
		$this->params['apiurl']		= $postfields['apiurl'];

		$this->params['vars']		= array (
										'command'		=> 'nameserver-delete',
										'nameserver'	=> $postfields['nameserver'],
									);
		$ch = new CurlRequest();
		$ch->init($this->params);
		$curl_response = $ch->exec();
		if ($curl_response['http_code']!='200') {
			switch ($curl_response['http_code']){
				case '401':
					throw new \Exception("Authentication Failure. Invalid credentials.");
					break;
				case '500':
					throw new \Exception("Service not available - 500");
					break;
				default:
					throw new \Exception("Service not available.");
			}
		}
		if (!$curl_response['body'])	throw new \Exception("Invalid response from server");

		$response_array = $this->processResponse($curl_response['body']);

		switch ($response_array['result_code']) {
				case '00200':
					return $response_array;
					break;
				default:
					throw new \Exception($response_array['result_code'].' '.$response_array['result_message']);
		}
		return $response_array;	// we do not process data from here
	}

	/** OK
	 * Get nameservers from domain-info ROTLD command.
	 * @param array $postfields
	 * @return array
	 */
	public function syncDomainInfo($postfields) {
		$this->params['host']		= $postfields['hostname'];
		$this->params['login']		= $postfields['username'];
		$this->params['password']	= $postfields['password'];
		$this->params['apiurl']		= $postfields['apiurl'];
		$this->params['vars']		= array (
										'command'	=> 'domain-info',
										'domain'	=> $postfields['domain'],
									);

		$ch = new CurlRequest();
		$ch->init($this->params);
		$curl_response = $ch->exec();
		if ($curl_response['http_code']!='200') {
			switch ($curl_response['http_code']){
				case '401':
					throw new \Exception("Authentication Failure. Invalid credentials.");
					break;
				case '500':
					throw new \Exception("Service not available - 500");
					break;
				default:
					throw new \Exception("Service not available.");
			}
		}
		if (!$curl_response['body'])	throw new \Exception("Invalid response from server");

		$response_array = $this->processResponse($curl_response['body']);
		
		// we assume domain exists and is managed by this registrar
		// if we get the specific code 1002 - set transfered away to true
		// ToDo
		// add better processing of returned codes
		
		switch ($response_array['result_code']) {
				case '00200':
					$result = array (
								'expirydate' 		=> $response_array['data']['expiration_date'],
								'active'			=> $response_array['data']['statuses'][0],
								'expired'			=> $response_array['data']['deletion_date'],
								'transferredaway'	=> FALSE,
					);
					break;
				case '10002':
					$result = array (
								'expirydate' 		=> '',
								'active'			=> '',
								'expired'			=> '',
								'transferredaway'	=> TRUE,
					);
				default:
					throw new \Exception($response_array['result_code'].' '.$response_array['result_message']);
		}
		return $result;
	}

	/** 
	 * Get contact data from domain-info ROTLD command.
	 *
	 * @param array $postfields
	 *
	 * @return array
	 */
	public function getContact($postfields) {
		$this->params['host']		= $postfields['hostname'];
		$this->params['login']		= $postfields['username'];
		$this->params['password']	= $postfields['password'];
		$this->params['apiurl']		= $postfields['apiurl'];

		$this->params['vars']		= array (
										'command'	=> 'domain-info',
										'domain'	=> $postfields['domain'],
									);

		$ch = new CurlRequest();
		$ch->init($this->params);
		$curl_response = $ch->exec();

		if ($curl_response['http_code']!='200') {
			switch ($curl_response['http_code']){
				case '401':
					throw new \Exception("Authentication Failure 01. Invalid credentials.");
					break;
				case '500':
					throw new \Exception("Service not available - 500");
					break;
				default:
					throw new \Exception("Service not available.");
			}
		}
		if (!$curl_response['body'])	throw new \Exception("Invalid response from server");

		$response_array = $this->processResponse($curl_response['body']);
		
		// we assume domain exists and is managed by this registrar
		// ToDo 
		// add better processing of returned codes
		
		// first we take Manhattan, than we take Berlin
		// we need the CID from the domain-info, then we use it to get contact data
		// ROTLD developers seem to have twisted logic
		
		switch ($response_array['result_code']) {
				case '00200':
					$this->params['vars']	= array (
											'command'	=> 'contact-info',
											'cid'		=> $response_array['data']['registrant_id'],
											);
					break;
				default:
					throw new \Exception($response_array['result_code'].' '.$response_array['result_message']);
		}
		
		// now that we have the CID, get contact data
		$ch = new CurlRequest();
		$ch->init($this->params);
		$curl_response = $ch->exec();

		if ($curl_response['http_code']!='200') {
			switch ($curl_response['http_code']){
				case '401':
					throw new \Exception("Authentication Failure. Invalid credentials.");
					break;
				case '500':
					throw new \Exception("Service not available - 500");
					break;
				default:
					throw new \Exception("Service not available.");
			}
		}
		if (!$curl_response['body'])	throw new \Exception("Invalid response from server");

		$response_array = $this->processResponse($curl_response['body']);
		
		// we assume domain exists and is managed by this registrar
		// ToDo 
		// add better processing of returned codes
		switch ($response_array['result_code']) {
			case '00200':
				$result = array(
							'fax' 				=> $response_array['data']['fax'],
							'address1'			=> $response_array['data']['address1'],
							'address2'			=> $response_array['data']['address2'],
							'address3'			=> $response_array['data']['address3'],
							'phone'				=> $response_array['data']['phone'],
							'postal_code'		=> $response_array['data']['postal_code'],
							'country_code'		=> $response_array['data']['country_code'],
							'state_province'	=> $response_array['data']['state_province'],
							'city'				=> $response_array['data']['city'],
							'name'				=> $response_array['data']['name'],
							'person_type'		=> $response_array['data']['person_type'],
							'email'				=> $response_array['data']['email'],
						);
				break;
			default:
				throw new \Exception($response_array['result_code'].' '.$response_array['result_message']);
		}
		return $result;
	}


}

class RotldApiClient {
	private $params = array();
	private $fields = array();

	public function RotldApiClient($config_params) {
		$this->params['post_fields']	='';
		$this->params['host']			='undefined';
		$this->fields['lang']			='en';
		$this->fields['format']			='json';

		if(isset($config_params['lang']) && $config_params['lang']!='') $this->fields['lang'] = $config_params['lang'];

		if(isset($config_params['format']) && $config_params['format']!='') $this->fields['format'] = $config_params['format'];

		if(!isset($config_params['apiurl'])) throw new Exception('Invalid apiurl');
		$this->params['url'] = $config_params['apiurl'];

		if(isset($config_params['registrar_domain'])) $this->params['host'] = $config_params['registrar_domain'];

		if(!isset($config_params['regid'])) throw new Exception('Invalid regid');
		$this->params['login'] = trim($config_params['regid']);

		if(!isset($config_params['password'])) throw new Exception('Invalid password');
		$this->params['password'] = trim($config_params['password']);
	}


	public function set_param($param,$value) {
		$this->fields[$param] = trim($value);
	}

	public function commit() {
		$poststr = '';
		$fields = $this->fields;
		if(is_array($fields) && sizeof($fields)) {
			foreach($fields as $key=>$val) {
				$val = urlencode($val);
				$poststr.=$key."=".$val."&";
			}
		}
		$this->params['post_fields']=$poststr;

		$ch = new CurlRequest();
		$ch->init($this->params);
		$result = $ch->exec();
		if ($result['http_code']!='200') {
			switch ($result['http_code']){
				case '401':
					throw new Exception("Authentication Failure. Invalid credentials.");
				case '500':
					throw new Exception("Service not available.");
				default:
					throw new Exception("Service not available.");
			}
		}
		if (!$result['body'])   throw new Exception("Invalid response from server");

		return $result['body'];
	}


	public function reset() {
		$this->fields = array();
	}
}

class CurlRequest {

	private $ch;
	
	public function init($params) {
		$this->ch = curl_init();

		$user_agent	= 'ROTLD RESTAPI CLIENT 2.0';
		$header		= array("Accept-Charset: utf-8;q=0.7,*;q=0.7","Keep-Alive: 60");
		$apiurl		= 'https://rest2-test.rotld.ro:6080';

		if (isset($params['host']) && $params['host'])		$header[]	= "Host: ".$params['host'];
		if (isset($params['header']) && $params['header'])	$header[]	= $params['header'];
		if (isset($params['apiurl']) && $params['apiurl'])	$apiurl		= $params['apiurl'];

		@curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
		@curl_setopt($this->ch, CURLOPT_VERBOSE, 0);
		@curl_setopt($this->ch, CURLOPT_HEADER, 1);

		@curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, 1);
		@curl_setopt($this->ch, CURLOPT_HTTPHEADER, $header);
		@curl_setopt($this->ch, CURLOPT_POST, true);
		@curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($params['vars']));

		@curl_setopt($this->ch, CURLOPT_URL, $apiurl);
		
		@curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, 0);
		@curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, 0);

		@curl_setopt($this->ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
		@curl_setopt($this->ch, CURLOPT_USERPWD,$params['login'].':'.$params['password']);
		@curl_setopt($this->ch, CURLOPT_TIMEOUT, 30);
	}

	public function exec() {
		$response	= curl_exec($this->ch);
		$error		= curl_error($this->ch);
		$result		= array( 'header' => '',
						'body' => '',
						'curl_error' => '',
						'http_code' => '',
						'last_url' => ''
						);
		curl_close($ch);

		if ( $error != "" ) {
			$result['curl_error'] = $error;
			return $result;
		}

		$header_size			= curl_getinfo($this->ch,CURLINFO_HEADER_SIZE);
		$result['header']		= substr($response, 0, $header_size);
		$result['body']			= substr( $response, $header_size );
		$result['http_code']	= curl_getinfo($this -> ch,CURLINFO_HTTP_CODE);
		$result['last_url']		= curl_getinfo($this -> ch,CURLINFO_EFFECTIVE_URL);

		return $result;
	}
	
	
}

