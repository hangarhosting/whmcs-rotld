<?php

namespace WHMCS\Module\Widget;
use WHMCS\Module\Registrar\rotld\ApiClient;

/**
 * Standard add_hook call @see https://developers.whmcs.com/hooks/getting-started/
 */
add_hook('AdminHomeWidgets', 1, function() {
    /**
     * Return a new instance of the widget object for display
     */
    return new RoTLDWidget();
});



class RoTLDWidget extends \WHMCS\Module\AbstractWidget {
    protected $title = 'ROTLD Account';
    protected $description = 'ROTLD account balance';
    protected $weight = 150;
    protected $columns = 1;
    protected $cache = true;
    protected $cacheExpiry = 120;
    protected $requiredPermission = '';

    public function getData() {

	// config

	/**
	 * WARNING 
	 * this code is not safe
	 * it should load the credentials from the registrar module 
	 * 
	 * this is on ToDo list
	 */

	// use test credentials for testing purposes
	$userIdentifier	= 'userTest';
	$apiKey		= 'password';

	// uncomment live credentials for displaying the live data
	$userIdentifier	= 'userLive';
	$apiKey		= 'password';

	// use your host here
	$userHost	= 'my.host.tld';


	// ################################################
	
	
	// build post data
	$postfields = array(
       	'username' => $userIdentifier,
       	'password' => $apiKey,
		'hostname' => $userHost,
		);
	try {
	      	$api = new ApiClient();
        	$api->call('CheckBalance', $postfields);
			return array(
				'success' => true,
				'balance' => $api->getFromResponse('balance'),
			);
	} catch (\Exception $e) {
		return array(
			'error' => $e->getMessage(),
        );
	}

    }

    /**
     * Generate Output.
     *
     * Generate and return the body output for the widget.
     *
     * @param array $data The data returned by the getData method.
     *
     * @return string
     */
    public function generateOutput($data) {
	return <<<HTML
	<div class="widget-billing">
		<div class="row account-overview-widget">
			<div class="col-sm-6 bordered-right">
				<div class="item text-right">
				<div class="data color-green">{$data['balance']} €</div>
				<div class="note">Account Balance</div>
				</div>
			</div>
			<div class="col-sm-6">
				<div class="text-center">
				<img style="width:125px" src="/modules/registrars/rotld/logo.png" />
				</div>
			</div>
		</div>
	</div>
	HTML;
    }
}
?>