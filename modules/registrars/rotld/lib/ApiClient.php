<?php

namespace WHMCS\Module\Registrar\rotld;


/**
 * RoTLD Registrar API Client.
 * A simple API Client for communicating with an external API endpoint.
 */
class ApiClient {
	protected $results = array();
	private $params;

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
				$this->results = 'unlocked';
				break;
			case 'SetLockStatus':
				// locking not implemented at ROTLD, return error with explanatory message
				// TODO: implement translation of the error string
				throw new \Exception('ROTLD does not support domain locking yet, so domain will remain unlocked');
			case 'EnableIDProtection':
				// ID Protection not implemented at ROTLD, return error with explanatory message
				// TODO: implement translation of the error string
				throw new \Exception('ROTLD does not support domain ID Protection');
			case 'DisableIDProtection':
				// ID Protection not implemented at ROTLD, return error with explanatory message
				// TODO: implement translation of the error string
				throw new \Exception('ROTLD does not support domain ID Protection');
			case 'ReleaseDomain':
				// deletion not implemented at ROTLD, return error with explanatory message
				// TODO: implement translation of the error string
				throw new \Exception('Function not supported');
			case 'DeleteDomain':
				// deletion not implemented at ROTLD, return error with explanatory message
				// TODO: implement translation of the error string
				throw new \Exception('ROTLD does not support domain deletion');
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
				$this->results = $this->getCID($postfields);
				break;
			default:
				$this->results = array();
		}

		logModuleCall (
			'rotld',
			$action,
			$postfields,
			$response,
			$this->results,
			array(
				$postfields['username'],
				$postfields['password'],
			)
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
		$this->params['host']		= $postfields['hostname'];
		$this->params['login']		= $postfields['username'];
		$this->params['password']	= $postfields['password'];
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

	/** seems OK
	 * Renew domain via  ROTLD command.
	 *
	 * @param array $postfields
	 *
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
		// throw new \Exception($this->params['vars']['domain_period']);
		
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

	/** seems OK
	 * Get nameservers from domain-info ROTLD command.
	 *
	 * @param array $postfields
	 *
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

	/** seems OK
	 * Set nameservers using domain-reset-ns ROTLD command.
	 *
	 * @param array $postfields
	 *
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
	
	/** seems OK
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

	/** seems OK
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

	/** seems OK
	 * delete an existing private nameserver for a domain using nameserver-delete ROTLD command.
	 *
	 * @param array $postfields
	 *
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

	/**
	 * Get nameservers from domain-info ROTLD command.
	 *
	 * @param array $postfields
	 *
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
		// ToDo 
		// add better processing of returned codes
		
		switch ($response_array['result_code']) {
				case '00200':
					$result = array (
								'expirydate' 		=> $response_array['data']['expiration_date'],
								'active'			=> $response_array['data']['statuses'][0],
								'expired'			=> $response_array['data']['deletion_date'],
								'transferredaway'	=> '',
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

	// const API_URL = 'https://rest.rotld.ro:6080';
	// API_URL = 'https://rest2-test.rotld.ro:6080';
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
		//@curl_setopt($this->ch, CURLOPT_URL, self::API_URL);
		@curl_setopt($this->ch, CURLOPT_URL, $apiurl);
		
		@curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, 0);
		@curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, 0);

		@curl_setopt($this->ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
		@curl_setopt($this->ch, CURLOPT_USERPWD,$params['login'].':'.$params['password']);
		@curl_setopt($this->ch, CURLOPT_TIMEOUT, 30);
	}

	public function exec() {
		$response = curl_exec($this->ch);
		$error = curl_error($this->ch);
		$result = array( 'header' => '',
					'body' => '',
					'curl_error' => '',
					'http_code' => '',
					'last_url' => '');
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




