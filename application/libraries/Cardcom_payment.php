<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Cardcompayment Class
 *
 * This class manages the breadcrumb object
 *
 * @package		Cardcompayment
 * @version		1.0
 * @author 		Yuri Ritvin <yuri1992@gmail.com>
 * @copyright 	Copyright (c) 2015, Yuri
 * @link		
 */
class Cardcom_payment {

	 	
	 /**
	  * Constructor
	  *
	  * @access	public
	  *
	  */
	public function __construct()
	{	
		$this->ci =& get_instance();
		// Load config file
		$this->ci->load->config('cardcom_payment');
		$this->terminal_number = $this->ci->config->item('terminal_number');
		$this->username = $this->ci->config->item('username');
		$this->api_level = $this->ci->config->item('api_level');
		$this->codepage = $this->ci->config->item('codepage');

		$this->_logProfileUrl = "https://secure.cardcom.co.il/interface/PerformSimpleCharge.aspx";
		$this->_lowProfileUrlIndicator = "https://secure.cardcom.co.il/Interface/BillGoldGetLowProfileIndicator.aspx";
		$this->_create_invoice = false;
		$this->_invoice = array();
	}
	/*
	 * Return Transaction Status Acording To LowProfileCode
	 * @lowProfileCode - low profile code as been return to success_page
	 */
	public function getLowProfileTransactionStatus($lowProfileCode,$short=false) {
		$vars = array(
			'terminalnumber' => $this->terminal_number,
			'username' => $this->username,
			'lowprofilecode' => $lowProfileCode
		);
		$response_ = $this->_curl($vars,$this->_lowProfileUrlIndicator);
		parse_str($response_,$response);
		if ($short)
			return $this->validateLowProfileCodeResponse($response);
		return $response;
	}
	/*
	 * Return True If Transaction Been accepted, False if Else
	 * @reponse - response from getLowProfileTransactionStatus()
	 */
	public function validateLowProfileCodeResponse($response) {

		if (
			$response['ResponseCode'] == 0 &&
			$response['DealResponse'] == 0 &&
			$response['OperationResponse'] == 0
		) {
			return true;
		}
		return false;
	}
	/*
	 * Return Cardcom Url For Billing
	 *
	 * @params = array
	 * 	Language(defualt:en) =>  en | he | ru 
	 * 	CoinID(defualt:1) =>  1- NIS , 2- USD other , article :  http://kb.cardcom.co.il/article/AA-00247/0
	 * 	SumToBill(Mandatory!) => // Sum To Bill
	 * 	ProductName(Mandatory!) => // Product Name , will how if no invoice will be created.
	 * 	SuccessRedirectUrl(Mandatory!) => // Success Page
	 * 	ErrorRedirectUrl(Mandatory!) => //  value that will be return and save in CardCom system
	 * 	ReturnValue(defualt:1234) => // max num of payments to show  to the user
	 * 	MaxNumOfPayments(defualt:2) => // max num of payments to show  to the user
	 * @redirect = if true will automatic redirect to Url;
	 */
	public function getCardcomPaymentRedirectUrl($params,$redirect=false) {
		$vars = $this->_initialCardcomParamsRequest($params);
		$url = $this->_getLowProfileRedricet($vars);
		if ($redirect)
			redirect($url);
		return $url;
	}

	/*
	 * Set Invoice Data For CardCom Request
	 *
	 * @params = array
	 * 	CustName (defualt:Israel Israeli) => "Fisrt Name Last Name" 
	 *	SendByEmail (defualt:true) =>  "true"
	 *	Email (defualt:true) =>  "email@email.com"
	 *	Language (defualt:en) =>  en | he | ru 
	 *	Items - array
	 *		Description (Mandatory!) => String Description
	 *		Price (Mandatory!) => Int Price per Item
	 *		Quantity (Mandatory!) => Quantity	
	 *	
	 */
	public function setInvoiceData($params) {
		$this->_create_invoice = true;
		$vars['IsCreateInvoice'] = "true";
		$vars["InvoiceHead.CustName"] = isset($params['CustName']) ? $params['CustName'] : "Israel Israeli"; // customer name
	    $vars["InvoiceHead.SendByEmail"] = isset($params['SendByEmail']) ? $params['SendByEmail'] : "true"; // will the invoice be send by email to the customer
	    $vars["InvoiceHead.Language"] = isset($params['Language']) ? $params['Language'] : "he"; // he or en only
	    $vars["InvoiceHead.Email"] = $params['Email'];
	    
	    // products info 
	    $i=1;
	    foreach ($params['Items'] as $item) {
		    $vars["InvoiceLines".$i.".Description"] = $item['Description'];
		    $vars["InvoiceLines".$i.".Price"] = $item['Price'];
		    $vars["InvoiceLines".$i.".Quantity"] = $item['Quantity'];
			$i++;
	    }
	    $this->_invoice = $vars;
	}
	public function getInvoiceData() {
		$vars = array();
		if ($this->_create_invoice)
			return $this->_invoice;
		return array("IsCreateInvoice" => false);
	}

	private function _initialCardcomParamsRequest($params) {
		$vars = array();
		$vars['TerminalNumber'] = $this->terminal_number;
		$vars['UserName'] = $this->username;
		$vars["APILevel"] = $this->api_level;
		$vars['codepage'] = $this->codepage;

		$vars["ChargeInfo.Language"] =  isset($params['Language']) ? $params['Language'] : 'en';   // page languge he- hebrew , en - english , ru , ar
		$vars["ChargeInfo.CoinID"] = isset($params['CoinID']) ? $params['CoinID'] : '1'; // billing coin , 
		$vars["ChargeInfo.SumToBill"] = $params['SumToBill']; 
		$vars['ChargeInfo.ProductName'] = $params['ProductName'];  
		$vars['ChargeInfo.SuccessRedirectUrl'] =  $params['SuccessRedirectUrl']; 
		$vars['ChargeInfo.ErrorRedirectUrl'] =  $params['ErrorRedirectUrl'];
		if (isset($params['IndicatorUrl']))
			$vars['ChargeInfo.IndicatorUrl'] = $params['IndicatorUrl'];
		$vars["ChargeInfo.ReturnValue"] = isset($params['ReturnValue']) ? $params['ReturnValue'] : "1234"; 
		$vars["ChargeInfo.MaxNumOfPayments"] = isset($params['MaxNumOfPayments']) ? $params['MaxNumOfPayments'] : "2"; 

		$vars = array_merge($vars,$this->getInvoiceData());
		return $vars;
	}
	private function _curl($vars, $url) {
		$urlencoded = http_build_query($vars);
		if( function_exists("curl_init")) { 
			$CR = curl_init();
			curl_setopt($CR, CURLOPT_URL,$url);
			curl_setopt($CR, CURLOPT_POST, 1);
			curl_setopt($CR, CURLOPT_FAILONERROR, true);
			curl_setopt($CR, CURLOPT_POSTFIELDS, $urlencoded );
			curl_setopt($CR, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($CR, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($CR, CURLOPT_FAILONERROR,true);
			$r = curl_exec( $CR );
			$error = curl_error ( $CR );
			if( !empty( $error )) {
				$this->onError($error);
			 	die();
			}
			curl_close( $CR );
		} else {
			$url .= $urlencoded;
			$r = file_get_contents($url);
			
		}
		return $r;
	}
	private function _getLowProfileRedricet($vars) {
		$r = $this->_curl($vars,$this->_logProfileUrl);
		parse_str($r,$responseArray);
		if ($responseArray['ResponseCode'] == "0") {
			return $responseArray['url'];   
		} else {
			$this->onError($responseArray);
			return false;
		}
	}
	private function onError($error) {
		$this->ci =& get_instance();
		if ($error)
			$this->error_model->log($error);
	}
}